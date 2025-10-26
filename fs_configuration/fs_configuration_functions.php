<?php
include_once "functions_vtpbx_fs.php";





function serveDynamicContextConfiguration($domain, $request, $domainDetails, $customerDetails, $caller,$mysqli){

    $channel_uuid = $request["Channel-Call-UUID"];
    $destination_number = $request["Caller-Destination-Number"];

    $caller_id_name = $request["Caller-Caller-ID-Name"];
    $caller_id_number = $request["Caller-Caller-ID-Number"];

    $domainID = $domainDetails["id"];
    $customerID = $customerDetails["id"];

    $external_gateway_id = $customerDetails["sip_provider"];
    $external_gateway_prefix = $customerDetails["sip_provider_prefix"];






    $external_caller_id = $customerDetails["external_caller_id"];


    $extensionsRegex = '^([1-9][0-9][0-9])$';  // 3 digits, default value, starting from 1-9


    $extensionLength = $customerDetails["extension_length"];
    switch($extensionLength){
        case 2:{
            // 2 digits, default, starting from 1-9
            $extensionsRegex = '^([1-9][0-9])$';
        }break;
        case 4:{
            // 4 digits, default, starting from 1-9
            $extensionsRegex = '^([1-9][0-9][0-9][0-9])$';
        }break;
        case 5:{
            // 5 digits, default, starting from 1-9
            $extensionsRegex = '^([1-9][0-9][0-9][0-9][0-9])$';
        }break;

    }




    $recordingDestinationDefinition = '2 b s record_session::/opt/ctpbx/recordings/' . $domain . '/'.$channel_uuid.'.wav' ;
    //TODO: test call recording, make sure channel_uuid is really unique and can work here.  Can it create directories???
    $recordingDestinationShort  = '/opt/ctpbx/recordings/' . $domain . '/'.$channel_uuid.'.wav' ;



    $didNumberDetails = getDIDNumberDetailsForCustomer($destination_number,$customerID,$domainID,$mysqli);


    // Detect destination -> where the call should be routed?
    $detectedDestination = "USER";
    $detectedDestinationDefinition = "";


    if($dest = detectDestinationType($destination_number,$extensionLength,$customerDetails,$domainID,$mysqli) ){
        $detectedDestination = $dest["type"];
        if(isset($dest["def"]))
            $detectedDestinationDefinition = $dest["def"];
    }


    error_log("destination type: " .$detectedDestination);


    switch($detectedDestination){
        case "USER" : {
            // call routed to another user in the tenant
            serveConfigurationLocalExtension($domain,$destination_number,$recordingDestinationDefinition,$recordingDestinationShort,$customerID,$mysqli);


        }break;
        case "AI":{
            // Route call to AI websocket handler
            serveWebsocketEsl($domain,$destination_number,$domain,$customerID,$detectedDestinationDefinition,$domainID);


        }break;
        case "QUEUE":{
            // Queue park call
            // $detectedDestinationDefinition  keeps the ID of the queue
            serveConfigurationSendToQueue($domain,$destination_number,$domain,$detectedDestinationDefinition,$customerID,$didNumberDetails,$mysqli);

            // $channel_uuid  = queue caller uuid
            insertQueueCallLogs( $customerID,$domainID,$detectedDestinationDefinition,$channel_uuid,$caller_id_number,$caller_id_name,$mysqli  );





        }break;
        case "QUEUE_PICKUP":{
            // Queue pickup call
            // $detectedDestinationDefinition  keeps the ID of the queue
            serveConfigurationQueuePickup($domain,$destination_number,$detectedDestinationDefinition,$customerID);


        }break;
        case "CONFERENCE":{
            // Voice Conference room

            /*
                Quality:   default, wideband, ultrawideband, cdquality
                Default value:  default
            */
            $conferenceQuality = "default";
            //TODO: Add support for better voice quality in the conference
            serveConfigurationVoiceConference($domain,$domain,$destination_number,$detectedDestinationDefinition,$conferenceQuality,$customerID,$didNumberDetails,$mysqli);


        }break;
        case "PARKING":{
            // parking lot





        }break;
        case "GROUP":{
            // reach the group of users





        }break;




        /*  =========================================  */
        /*              FEATURE CODES BELOW            */
        /*  =========================================  */




        case "VOICEMAIL":{
            // reach the personal voicemail box

            serveConfigurationVoicemail($domain,$destination_number,$caller,$customerID);




        }break;

        case "EAVESDROP":{
            // default:  *33[ext]  |    Listen to the active call on [ext]
            // [ext] is returned as $detectedDestinationDefinition

            $destToListen = $detectedDestinationDefinition;
            serveConfigurationEavesdrop($domain,$destination_number, $destToListen,$customerID);




        }break;

        case "INTERCEPT_EXT":{
            // default:  **[ext]  |    Retrieve the incoming call to [ext]
            //



            $extension_to_intercept = str_replace("*","",$destination_number);
            serveConfigurationIntercept($domain,$destination_number, $extension_to_intercept,$customerID);




        }break;

        case "CALL_RETURN":{
            // default:  *69  |   call back the last number who called me

            serveConfigurationCallReturn($domain,$destination_number,$customerID);



        }break;

        case "QUEUE_LOGIN":{
            // default:  *22  |   agent login into the queue

            //serveConfigurationCallReturn($domain,$destination_number,$customerID);

            // feature_code_extension    / feature_code_suffix

            $feature_code_extension = $dest["feature_code_extension"];
            $feature_code_suffix = $dest["feature_code_suffix"];



            serveConfigurationQueueLogin($domain,$destination_number,$feature_code_suffix,$caller,$customerID);


            // save agent presence information.
            $userDetails = getUserDetailsByUsernameDomain($caller,$domainID,$mysqli);
            $userID = $userDetails["id"];

            saveNewQueueSessionDetails($customerID,$domainID,$feature_code_suffix,$userID ,$mysqli);





        }break;

        case "QUEUE_LOGOUT":{
            // default:  *23  |   agent logout from the queue

            //serveConfigurationCallReturn($domain,$destination_number,$customerID);

            $feature_code_extension = $dest["feature_code_extension"];
            $feature_code_suffix = $dest["feature_code_suffix"];

            serveConfigurationQueueLogout($domain,$destination_number,$feature_code_suffix,$caller,$customerID);


            // save agent presence information.
            $userDetails = getUserDetailsByUsernameDomain($caller,$domainID,$mysqli);
            $userID = $userDetails["id"];

            saveQueueSessionEndDetails($feature_code_suffix,$userID,$mysqli);



        }break;



        case "VALET_PARK":{
            // default:  *5900  |    Valet Autoselect Parking Stall
            //

            serveConfigurationValetPark($domain,$destination_number,$customerID,$mysqli);




        }break;


        case "VALET_UNPARK":{
            // default:  *59xx  |    Valet unpark call
            //
            $feature_code_extension = $dest["feature_code_extension"];
            $feature_code_suffix = $dest["feature_code_suffix"];  //

            $valet_id = str_replace("*","",$feature_code_extension) . $feature_code_suffix;


          //  error_log("ext [$feature_code_extension]  suffix [$feature_code_suffix] valed id [$valet_id]");

            serveConfigurationValetUnPark($domain,$feature_code_extension, $valet_id,$customerID);



        }break;







        /*  =========================================  */
        /*              EXTERNAL NUMBER                */
        /*  =========================================  */




        case "EXTERNAL_NUMBER":{
            // Forward call to the external number


            // $external_caller_id  -  this is the customer's DEFAULT caller ID for outbound calls.
            // external_caller_id_for_user    -  USER's external caller id  ???

            //$caller  - is this the extension number of the source user????

            error_log("About to serve configuration for external number. IS this source extension caller:[$caller] domainID:[$domainID] customerid:[$customerID]   ");
            // About to serve configuration for external number. IS this source extension caller:[105]  customerid:[1]

            //$caller_user_id = getUserIDByLogin($caller,$mysqli);

            $caller_details = getUserDetailsByUsernameDomain($caller,$domainID,$mysqli);
            /*
                        $response["id"] = $id;
                        $response["customer"] = $customer;
                        $response["name"] = $name;
                        $response["username"] = $username;
                        $response["type"] = $type;
                        $response["sip_password"] = $sip_password;
                        $response["record_internal"] = $record_internal;
                        $response["record_incoming"] = $record_incoming;
                        $response["record_external"] = $record_external;
                        $response["vm_password"] = $vm_password;
                        $response["domain"] = $domain;

                        $response["vm_enable"] = $vm_enable;
                        $response["vm_greeting"] = $vm_greeting;
                        $response["vm_timeout"] = $vm_timeout;
                        $response["call_forwarding"] = $call_forwarding;
                        $response["external_caller_id"] = $external_caller_id;
             */


            // first of all check if this user can make external calls, if not:   serveConfigurationNotFound();

            if($caller_details["disable_external_calls"] == "1"){
                serveConfigurationNotFound();
                exit;
            }






            //Extract external caller ID in this order:
            // $external_caller_id  -  this is the customer's DEFAULT caller ID for outbound calls.

            //error_log(json_encode($caller_details));

            $external_caller_id_for_user = $caller_details["external_caller_id"];

            if(test_input($external_caller_id_for_user) != ""){
                error_log("[CALLER_ID] Extension :[$caller] from domain [$domain]  has outbound caller ID : [$external_caller_id_for_user]  default user caller ID:[$external_caller_id] . The extension's own caller ID will be used at this time as it's not empty.  ");

                // user seems to have proper caller ID, let's use it instead of default caller ID for the customer (PBX)
                $external_caller_id = $external_caller_id_for_user;
            }else{

                error_log("[CALLER_ID] Extension :[$caller] from domain [$domain]  has empty outbound caller ID  default PBX caller ID:[$external_caller_id] will be used at this time.  ");

            }

            // Toll-Free gateway detection:

            $destination_number_prefix = substr($destination_number,0,4);
            if(substr($destination_number_prefix,0,1) == "1"){
                $destination_number_prefix = substr($destination_number_prefix,1,3);
            }else{
                $destination_number_prefix = substr($destination_number_prefix,0,3);
            }

            if($destination_number_prefix == "800" || $destination_number_prefix == "888" || $destination_number_prefix == "877" || $destination_number_prefix == "866" || $destination_number_prefix == "855" || $destination_number_prefix == "844" || $destination_number_prefix == "833"){

                error_log("USA Toll-Free number detected, prefix [$destination_number_prefix].");

                $external_gateway_id = $customerDetails["sip_provider_tf"];
                $external_gateway_prefix = $customerDetails["sip_provider_prefix_tf"];

            }




            // Is someone calling a DID number of another PBX tenant?

            $didNumberDetails = getDIDnumberDetailsByNumber($destination_number,$mysqli);

            if(isset($didNumberDetails["id"])){
                /*
                 *  "id" => $id,
                        "domain" => $domain,
                        "domain_name" => $domain_name,
                        "did_provider" => $did_provider,
                        "did_provider_name" => $did_provider_name,
                        "did_number" => $did_number,
                        "action_type" => $action_type,
                        "action_def" => $action_def,

                        "pre_answer_playback" => $pre_answer_playback,
                        "pre_answer_playback_file" => $pre_answer_playback_file,
                        "tts_text" => $tts_text,

                        "sip_registration" => $sip_registrationArr
                 */

                $didNumberID = $didNumberDetails["id"];
                $didNumberDomainName = $didNumberDetails["domain_name"];
                error_log("User from customer [$customerID] domain [$domain] is calling [$destination_number] and it's recognized as a DID number ID [$didNumberID] from domain [$didNumberDomainName]");





                serveConfigurationExternalNumberDIDloop($domain,$destination_number,$external_gateway_id,$external_gateway_prefix,$customerID,$external_caller_id,$recordingDestinationShort,$didNumberDomainName,$mysqli);

                error_log("--  Config served.");


            }else{





                serveConfigurationExternalNumber($domain,$destination_number,$external_gateway_id,$external_gateway_prefix,$customerID,$external_caller_id,$recordingDestinationShort,$mysqli);

            }

           // serveConfigurationExternalNumber($domain,$destination_number,$external_gateway_id,$external_gateway_prefix,$customerID,$external_caller_id,$recordingDestinationShort,$mysqli);





            //  CT  CT    CT  CT    CT  CT    CT  CT    CT  CT    CT  CT    CT  CT    CT  CT    CT  CT
            // DID call -> send to CT API

            $url = CT_POST_CALL_URL;
            /*
             *
                OUTGOING call from our DID to an EXTERNAL number
                CallEvent Request{
                    did: "our number",
                    token: "",
                    phonenumber: "other number",
                    direction: 'outbound',
                    uuid: ""
                }
             *
             */

           // $uuid = test_input($_REQUEST["Unique-ID"]);

            // let's extract the caller ID to put into "did" field:
            $external_caller_id = $customerDetails["external_caller_id"];  // this is "default PBX" caller ID



            $url = CT_POST_CALL_URL;
            $url_token = CT_API_TOKEN;

            $webhookDetails_arr = get_CT_webhook_url_and_token_by_customer_and_type($customerID,CT_API_WEBHOOK_TYPE_INCOMING_CALLS,$mysqli);
            $url = $webhookDetails_arr["webhook_url"];
            $url_token = $webhookDetails_arr["webhook_token"];





            $data = array(
                "token"=> $url_token,
                "did" => $external_caller_id,   // DID here...
                "phonenumber" => $destination_number,
                "note" => "sip",
                "direction" => "outbound-api",
                "uuid" => $channel_uuid,

                "customer" => $customerID,
                "domain" => $domain

            );

            $curlResponse  = httpPostViaCURL($url, $data);

            $curlPostJSON = json_encode($data);
            error_log("--CallEvent-- : DID number/CID: [$external_caller_id] is calling EXTERNAL number: $destination_number curl request: [$curlPostJSON]    Response: [$curlResponse] .");
            //  CT  CT    CT  CT    CT  CT    CT  CT    CT  CT    CT  CT    CT  CT    CT  CT    CT  CT




        }break;



        default:{
            serveConfigurationNotFound();

        }break;
    }





}

// ====================================================================================================================

function serveDynamicDIDConfiguration($domainOriginal, $request, $domainDetails, $customerDetails, $caller,$mysqli){

    $channel_uuid = $request["Channel-Call-UUID"];
    $destination_number = $request["Caller-Destination-Number"];
    $domainID = $domainDetails["id"]; // destination domain, ie. d1.vtpbx.com
    $destinationDomain = $domainDetails["name"];
    $customerID = $customerDetails["id"];

    $caller_id_name = $request["Caller-Caller-ID-Name"];
    $caller_id_number = $request["Caller-Caller-ID-Number"];


    $recordingDestinationDefinition = '2 b s record_session::$${recordings_dir}/' . $destinationDomain . '/'.$channel_uuid.'.wav' ;
    //TODO: test call recording, make sure channel_uuid is really unique and can work here.  Can it create directories???
    $recordingDestinationShort  = '/opt/ctpbx/recordings/' . $destinationDomain . '/'.$channel_uuid.'.wav' ;



    $didNumberDetails = getDIDNumberDetailsForCustomer($destination_number,$customerID,$domainID,$mysqli);


    $detectedDestination = $didNumberDetails["action_type"];
    $detectedDestinationDefinition= $didNumberDetails["action_def"];


    $pre_answer_playback = $didNumberDetails["pre_answer_playback"];
    $pre_answer_playback_file = $didNumberDetails["pre_answer_playback_file"];



    error_log(" inside serveDynamicDIDConfiguration, DID destination type: [$detectedDestination] , def [$detectedDestinationDefinition]. Pre-answer playback [$pre_answer_playback]  IVR file [$pre_answer_playback_file]");


    switch($detectedDestination){
        case "USER" : {
            // call routed to another user in the tenant
            //serveConfigurationLocalExtension($domainOriginal,$destination_number,$recordingDestinationDefinition,$recordingDestinationShort);
            serveConfigurationLocalExtensionViaDID($domainOriginal,$destination_number,$recordingDestinationDefinition,$recordingDestinationShort,$destinationDomain,$detectedDestinationDefinition,$customerID,$mysqli);

        }break;
        case "AI":{
            // Route DID call to AI websocket handler
            serveWebsocketEsl($domainOriginal,$destination_number,$destinationDomain,$customerID,$detectedDestinationDefinition,$domainID);

        }break;
        case "QUEUE":{
            // Queue park call
            // $detectedDestinationDefinition  keeps the ID of the queue
            serveConfigurationSendToQueue($domainOriginal,$destination_number,$destinationDomain,$detectedDestinationDefinition,$customerID,$didNumberDetails,$mysqli);

            // $channel_uuid  = queue caller uuid
            insertQueueCallLogs( $customerID,$domainID,$detectedDestinationDefinition,$channel_uuid,$caller_id_number,$caller_id_name,$mysqli  );


        }break;

        case "CONFERENCE":{
            // Voice Conference room

            /*
                Quality:   default, wideband, ultrawideband, cdquality
                Default value:  default
            */
            $conferenceQuality = "default";
            //TODO: Add support for better voice quality in the conference
            serveConfigurationVoiceConference($domainOriginal,$destinationDomain,$destination_number,$detectedDestinationDefinition,$conferenceQuality,"",$didNumberDetails,$mysqli);


        }break;
        case "IVR":{
            // play the IVR

            serveConfigurationSendToIVR($domainOriginal,$destination_number,$destinationDomain,$detectedDestinationDefinition,$customerID,$recordingDestinationDefinition,$recordingDestinationShort,$didNumberDetails,$mysqli);


        }break;
        case "PARKING":{
            // parking lot





        }break;
        case "GROUP":{
            // reach the group of users





        }break;


        default:{
            serveConfigurationNotFound();

        }break;
    }





}


function serveDynamicDIDtoIVRconfiguration($domainOriginal, $request, $domainDetails, $customerDetails, $caller,$detectedDestination,$detectedDestinationDefinition,$mysqli){

    $channel_uuid = $request["Channel-Call-UUID"];
    $destination_number = $request["Caller-Destination-Number"];
    $domainID = $domainDetails["id"]; // destination domain, ie. d1.vtpbx.com
    $destinationDomain = $domainDetails["name"];
    $customerID = $customerDetails["id"];

    $caller_id_name = $request["Caller-Caller-ID-Name"];
    $caller_id_number = $request["Caller-Caller-ID-Number"];


    $recordingDestinationDefinition = '2 b s record_session::$${recordings_dir}/' . $destinationDomain . '/'.$channel_uuid.'.wav' ;

    $recordingDestinationShort  = '/opt/ctpbx/recordings/' . $destinationDomain . '/'.$channel_uuid.'.wav' ;


    //$didNumberDetails = getDIDNumberDetailsForCustomer($destination_number,$customerID,$domainID,$mysqli);

    error_log("-=-=-=-=-=-=-=-Sending DID call to IVR , recording short:  [$recordingDestinationShort]");
    error_log(" inside serveDynamicDIDConfiguration, DID destination type: [$detectedDestination] , def [$detectedDestinationDefinition]");


    $didNumberDetails = getDIDNumberDetailsForCustomer($destination_number,$customerID,$domainID,$mysqli);


    switch($detectedDestination){
        case "USER" : {
            // call routed to another user in the tenant
            //serveConfigurationLocalExtension($domainOriginal,$destination_number,$recordingDestinationDefinition,$recordingDestinationShort);
            serveConfigurationLocalExtensionViaDID($domainOriginal,$destination_number,$recordingDestinationDefinition,$recordingDestinationShort,$destinationDomain,$detectedDestinationDefinition,$customerID,$mysqli);

        }break;
        case "AI":{
            // Route IVR transfer to AI websocket handler
            serveWebsocketEsl($domainOriginal,$destination_number,$destinationDomain,$customerID,$detectedDestinationDefinition,$domainID);

        }break;
        case "QUEUE":{
            // Queue park call
            // $detectedDestinationDefinition  keeps the ID of the queue
            serveConfigurationSendToQueue($domainOriginal,$destination_number,$destinationDomain,$detectedDestinationDefinition,$customerID,$didNumberDetails,$mysqli);

            // $channel_uuid  = queue caller uuid
            insertQueueCallLogs( $customerID,$domainID,$detectedDestinationDefinition,$channel_uuid,$caller_id_number,$caller_id_name,$mysqli  );


        }break;

        case "CONFERENCE":{
            // Voice Conference room

            /*
                Quality:   default, wideband, ultrawideband, cdquality
                Default value:  default
            */
            $conferenceQuality = "default";
            //TODO: Add support for better voice quality in the conference
            serveConfigurationVoiceConference($domainOriginal,$destinationDomain,$destination_number,$detectedDestinationDefinition,$conferenceQuality,"",$didNumberDetails,$mysqli);


        }break;
        case "IVR":{
            // play the IVR

            serveConfigurationSendToIVR($domainOriginal,$destination_number,$destinationDomain,$detectedDestinationDefinition,$customerID,$recordingDestinationDefinition,$recordingDestinationShort,$didNumberDetails,$mysqli);


        }break;
        case "PARKING":{
            // parking lot





        }break;
        case "GROUP":{
            // reach the group of users
            error_log("Serve dynamic configuration of IVR->Group.");

            serveConfigurationGroupViaDID($domainOriginal,$destination_number,$recordingDestinationDefinition,$recordingDestinationShort,$destinationDomain,$detectedDestinationDefinition, $customerID,$mysqli);



        }break;


        default:{
            serveConfigurationNotFound();

        }break;
    }





}
// ====================================================================================================================

function detectDestinationType($destinationNumber,$extensionLength,$customerDetails,$domainID,$mysqli){
    $destinationType = array();
    $destinationType["type"] = "USER";

    error_log("detectDestinationType: [$destinationNumber][$extensionLength][$domainID] ");


    $customerID = $customerDetails["id"];
    $dialedNumberLength = strlen($destinationNumber);
    $firstDialedDigit = substr($destinationNumber,0,1);


    // 0. Emergency number - 911

    if($destinationNumber == "911"){

        $destinationType["type"]  ="EXTERNAL_NUMBER";

        return $destinationType;

    }




    //
    //
    //  1. Destionation = Extension
    //
    //

    if($dialedNumberLength == $extensionLength && $firstDialedDigit != '*'  ||  ($firstDialedDigit != '*' &&  $dialedNumberLength < 6 ) ){


        // check this company extension numbers to find out if that extension has some entry so it should go to a
        // different type of destination than the default user destination.

        $extensionNumberDetails = getExtensionNumberDetailsForCustomer($destinationNumber,$customerID,$domainID,$mysqli);
        error_log("extensionNumberDetail : " . json_encode($extensionNumberDetails));


        if(isset($extensionNumberDetails["id"])){
            $extensionNumberActionType = $extensionNumberDetails["action_type"];

            switch($extensionNumberActionType){
                case "USER":{
                    // probably an alias to reach the user?
                    $destinationType["type"] = $extensionNumberActionType;
                    $destinationType["def"] = $extensionNumberDetails["action_def"];
                    $destinationType["id"] = $extensionNumberDetails["id"];

                }break;
                case "QUEUE":{
                    // Queue
                    $destinationType["type"] = $extensionNumberActionType;
                    $destinationType["def"] = $extensionNumberDetails["action_def"];
                    $destinationType["id"] = $extensionNumberDetails["id"];

                }break;
                case "QUEUE_PICKUP":{
                    // Queue
                    $destinationType["type"] = $extensionNumberActionType;
                    $destinationType["def"] = $extensionNumberDetails["action_def"];
                    $destinationType["id"] = $extensionNumberDetails["id"];

                }break;
                case "CONFERENCE":{
                    // Conference room
                    $destinationType["type"] = $extensionNumberActionType;
                    $destinationType["def"] = $extensionNumberDetails["action_def"];
                    $destinationType["id"] = $extensionNumberDetails["id"];

                }break;
                case "PARKING":{
                    //  parking lot
                    $destinationType["type"] = $extensionNumberActionType;
                    $destinationType["def"] = $extensionNumberDetails["action_def"];
                    $destinationType["id"] = $extensionNumberDetails["id"];

                }break;
                case "GROUP":{
                    // reach the group of users
                    $destinationType["type"] = $extensionNumberActionType;
                    $destinationType["def"] = $extensionNumberDetails["action_def"];
                    $destinationType["id"] = $extensionNumberDetails["id"];

                }break;
                case "AI":{
                    // AI websocket destination
                    $destinationType["type"] = $extensionNumberActionType;
                    $destinationType["def"] = $extensionNumberDetails["action_def"];
                    $destinationType["id"] = $extensionNumberDetails["id"];

                }break;

            }


        } // if not - just return default "USER" destination type


        return $destinationType;
    }

    //
    //
    //  2. Destionation = Feature code
    //
    //

    // Perhaps it is the feature code starting with * ?
    if($firstDialedDigit == '*'){
        $destinationType = array();  // default to "NOT_FOUND", just in case the feature code is invalid


        $extensionNumberDetails = getFeatureCodeDetailsForCustomer($destinationNumber,$customerID,$domainID,$mysqli);
        error_log("featureCodeDetail : " . json_encode($extensionNumberDetails));

        if(!isset($extensionNumberDetails["id"])){
            $extensionNumberDetails = getFeatureCodeDetailsDefaultList($destinationNumber,$mysqli);
        }



        if(isset($extensionNumberDetails["id"])){
            $extensionNumberActionType = $extensionNumberDetails["action_type"];
            $extensionNumberActionDef = $extensionNumberDetails["action_def"];
            $extensionNumberActionID = $extensionNumberDetails["id"];


            if($extensionNumberActionType == "VOICEMAIL" ||
                $extensionNumberActionType == "CALL_RETURN" ||
                $extensionNumberActionType == "VALET_PARK" ||
                $extensionNumberActionType == "VALET_UNPARK" ||
                $extensionNumberActionType == "INTERCEPT_EXT" ||
                $extensionNumberActionType == "EAVESDROP" ||
                $extensionNumberActionType == "QUEUE_LOGIN" ||
                $extensionNumberActionType == "QUEUE_LOGOUT"

                //TODO: add all the remaining feature codes
            )


            // For some feature codes we need to extract some parameters out.








            $destinationType["type"] = $extensionNumberActionType;
            $destinationType["def"] = $extensionNumberActionDef;
            $destinationType["id"] = $extensionNumberActionID;

            $destinationType["feature_code_extension"] = $extensionNumberDetails["extension"]  ;
            $destinationType["feature_code_suffix"] =  str_replace($extensionNumberDetails["extension"],"",$destinationNumber);



        }
        // if it was feature code (staring from *) but wrong number/pattern then reject that call
        return $destinationType;
    }



    // Finally - it will be external number which should be routed through the external provider (namely: VT)


    $destinationType["type"]  ="EXTERNAL_NUMBER";



    return $destinationType;
}






// ====================================================================================================================



function serveConfigurationLocalExtension($domain,$extension,$recordingDestinationDefinition,$recordingDestinationShort = "",$customerID = "",$mysqli){

        // Initial values (defaults)
        $call_timeout = TIMEOUT_CALL;     // default timeout when trying to reach a destination. After this amount of seconds system will failover to 2nd destination / voicemail, etc.
        $vm_timeout  = TIMEOUT_VM;


    $forwarding_separator = "|";   // by default we do "one by one" or "sequential" ring so the separator is |   . For simultaneous ring the separator will be ,

        $destination_number = '^' . $extension . '$';
                                    
        $dialed_extension = 'dialed_extension='.$extension;

        $vmDetails = getUserVMdetails($domain,$extension,$mysqli);

        $vm_timeout_candidate = intval($vmDetails["vm_timeout"]);

        if($vm_timeout_candidate>0 && $vm_timeout_candidate < 61){
            error_log("VM:  override vm_timeout to [".$vm_timeout_candidate."]");
            $vm_timeout = $vm_timeout_candidate;
        }

        $vm_enable = intval($vmDetails["vm_enable"]);


        // MOH
        $moh = 0;
        $moh_candidate =  getMOHbyDomainName($domain,$mysqli);
        if($moh_candidate>0 ){
            error_log("MOH:  override MOH to [".$moh_candidate."]");
            $moh = $moh_candidate;
        }




        // Forwarding rules:
        //   - this will be added AFTER the first and default destination which is the local extension number (in this function we generate the configuration to reach a local extension)
        $forwardingNumbers = "";

        $callForwardingDetailsJSON = getUserCFdetails($domain,$extension,$mysqli);
        $callForwardingDetails = json_decode($callForwardingDetailsJSON, true);

        error_log($callForwardingDetailsJSON);
        /*
        {
        "data":{
          "user_id" : "1",
          "forwarding_strategy":"SIMULTANEOUS",
          "call_timeout":"22",
          "forwarding_p1_enable":"on",
          "forwarding_p1_type":"USER",
          "forwarding_p1_number":"1",
          "forwarding_p1_screening":"on",

          "forwarding_p2_enable":"on",
          "forwarding_p2_type":"USER",
          "forwarding_p2_number":"2",
          "forwarding_p2_screening":"on",
          "forwarding_p3_enable":"on",
          "forwarding_p3_type":"EXT_NUMBER",
          "forwarding_p3_number":"3",
          "forwarding_p3_screening":"on",
          "forwarding_p4_enable":"on",
          "forwarding_p4_type":"USER",
          "forwarding_p4_number":"4",
          "forwarding_p4_screening":"on",

          "forwarding_p5_enable":"on",
          "forwarding_p5_type":"EXT_NUMBER",
          "forwarding_p5_number":"5",
          "forwarding_p5_screening":"on"

            (...)

          "forwarding_p10_enable":"on",
          "forwarding_p10_type":"EXT_NUMBER",
          "forwarding_p10_number":"5",
          "forwarding_p10_screening":"on"

        }
        }

        {"forwarding_strategy":"SEQUENTIAL","call_timeout":"5","forwarding_p1_enable":"on","forwarding_p1_type":"USER","forwarding_p1_number":"123","forwarding_p2_enable":"on","forwarding_p2_type":"USER","forwarding_p2_number":"101","forwarding_p3_type":"EXT_NUMBER","forwarding_p3_number":"33333","forwarding_p4_type":"EXT_NUMBER","forwarding_p4_number":"44444","forwarding_p5_type":"EXT_NUMBER","forwarding_p5_number":"55555","user_id":"23"}


        */


        if($callForwardingDetails["forwarding_strategy"] == "SIMULTANEOUS"){
            error_log("CF:  override forwarding_strategy to [".$callForwardingDetails["forwarding_strategy"]."]");
            $forwarding_separator = ",";
        }else{
            $vm_timeout = 60;
        }


        $call_timeout_candidate = intval($callForwardingDetails["call_timeout"]);

        if($call_timeout_candidate> 0 && $call_timeout_candidate < 60){
            error_log("CF:  override call_timeout to [".$callForwardingDetails["call_timeout"]."]");
            $call_timeout = $call_timeout_candidate;
        }






        // 2. Forward to external number:




    /*

      <extension name="Local_Extension">
         <condition field="destination_number" expression="^(1[0-9][0-9])$">
            <action application="export" data="dialed_extension=$1"/>
            <action application="bind_meta_app" data="1 b s execute_extension::dx XML features"/>
            <action application="bind_meta_app" data="2 b s record_session::$${recordings_dir}/${caller_id_number}.${strftime(%Y-%m-%d-%H-%M-%S)}.wav"/>
            <action application="bind_meta_app" data="3 b s execute_extension::cf XML features"/>
            <action application="bind_meta_app" data="4 b s execute_extension::att_xfer XML features"/>
            <action application="set" data="ringback=${us-ring}"/>
            <action application="set" data="transfer_ringback=$${hold_music}"/>
            <action application="set" data="call_timeout=30"/>
            <action application="set" data="hangup_after_bridge=true"/>
            <action application="set" data="continue_on_fail=true"/>
            <action application="hash" data="insert/${domain_name}-call_return/${dialed_extension}/${caller_id_number}"/>
            <action application="hash" data="insert/${domain_name}-last_dial_ext/${dialed_extension}/${uuid}"/>
            <action application="set" data="called_party_callgroup=${user_data(${dialed_extension}@${domain_name} var callgroup)}"/>
            <action application="hash" data="insert/${domain_name}-last_dial_ext/${called_party_callgroup}/${uuid}"/>
            <action application="hash" data="insert/${domain_name}-last_dial_ext/global/${uuid}"/>
            <action application="hash" data="insert/${domain_name}-last_dial/${called_party_callgroup}/${uuid}"/>
            <action application="bridge" data="sofia/external/${dialed_extension}@${domain_name};fs_path=sip:$${vtpbx_proxy}:50600"/>
           <action application="answer"/>
           <action application="sleep" data="1000"/>
           <action application="bridge" data="loopback/app=voicemail:default ${domain_name} ${dialed_extension}"/>
         </condition>
       </extension>


      */

    // --------

    $defaultContextConfiguration = new XMLWriter();

    $defaultContextConfiguration->openMemory();

    $defaultContextConfiguration->setIndent( TRUE );
    $defaultContextConfiguration->setIndentString( '  ' );
    $defaultContextConfiguration->startDocument( '1.0', 'UTF-8', 'no' );
    //set the freeswitch document type
    $defaultContextConfiguration->startElement( 'document' );
    $defaultContextConfiguration->writeAttribute( 'type', 'freeswitch/xml' );

    $defaultContextConfiguration->startElement( 'section' );
    $defaultContextConfiguration->writeAttribute( 'name', 'dialplan' );



    $defaultContextConfiguration->startElement( 'context' );
    $defaultContextConfiguration->writeAttribute( 'name', $domain );



// -- Global -> prepare parameters for other features (spymap, last dial, etc.)

    $defaultContextConfiguration->startElement( 'extension' );
    $defaultContextConfiguration->writeAttribute( 'name', 'global' );
    $defaultContextConfiguration->writeAttribute( 'continue', 'true' );

    $defaultContextConfiguration->startElement( 'condition' );

    /*
    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'hash' );
        $hashValue = 'insert/${domain_name}-spymap/${caller_id_number}/${uuid}';
    $defaultContextConfiguration->writeAttribute( 'data', $hashValue );
    $defaultContextConfiguration->endElement(); // action


    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'hash' );
    $hashValue = 'insert/${domain_name}-spymap/'.$extension.'/${uuid}';
    $defaultContextConfiguration->writeAttribute( 'data', $hashValue );
    $defaultContextConfiguration->endElement(); // action
*/


    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'hash' );
        $hashValue = 'insert/${domain_name}-last_dial/${caller_id_number}/${destination_number}';
    $defaultContextConfiguration->writeAttribute( 'data', $hashValue );
    $defaultContextConfiguration->endElement(); // action

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'hash' );
        $hashValue = 'insert/${domain_name}-last_dial/global/${uuid}';
    $defaultContextConfiguration->writeAttribute( 'data', $hashValue );
    $defaultContextConfiguration->endElement(); // action

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'export' );
        $exportValue = 'RFC2822_DATE=${strftime(%a, %d %b %Y %T %z)}';
    $defaultContextConfiguration->writeAttribute( 'data', $exportValue );
    $defaultContextConfiguration->endElement(); // action



    $defaultContextConfiguration->endElement(); // condition







    $defaultContextConfiguration->endElement();  //extension



//






    $defaultContextConfiguration->startElement( 'extension' );
    $defaultContextConfiguration->writeAttribute( 'name', 'Local_Extension' );

    $defaultContextConfiguration->startElement( 'condition' );
    $defaultContextConfiguration->writeAttribute( 'field', 'destination_number' );
    $defaultContextConfiguration->writeAttribute( 'expression', $destination_number );



    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'export' );
    $defaultContextConfiguration->writeAttribute( 'data', $dialed_extension );
    $defaultContextConfiguration->endElement(); // action


    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'export' );
    $defaultContextConfiguration->writeAttribute( 'data', "force_transfer_context=" . $domain );
    $defaultContextConfiguration->endElement(); // action


    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'export' );
    $defaultContextConfiguration->writeAttribute( 'data', "domain_name=" . $domain );
    $defaultContextConfiguration->endElement(); // action





    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'bind_meta_app' );
    $defaultContextConfiguration->writeAttribute( 'data', '1 b s execute_extension::dx XML features' );
    $defaultContextConfiguration->endElement(); // action

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'bind_meta_app' );
    $defaultContextConfiguration->writeAttribute( 'data', $recordingDestinationDefinition );
    $defaultContextConfiguration->endElement(); // action

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'bind_meta_app' );
    $defaultContextConfiguration->writeAttribute( 'data', '3 b s execute_extension::cf XML features' );
    $defaultContextConfiguration->endElement(); // action

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'bind_meta_app' );
    $defaultContextConfiguration->writeAttribute( 'data', '4 b s execute_extension::att_xfer XML features' );
    $defaultContextConfiguration->endElement(); // action


    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'set' );
    $defaultContextConfiguration->writeAttribute( 'data', 'vtpbx_destination_type=EXTENSION' );
    $defaultContextConfiguration->endElement(); // action

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'set' );
    $defaultContextConfiguration->writeAttribute( 'data', 'vtpbx_destination_def=' . $extension );
    $defaultContextConfiguration->endElement(); // action

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'set' );
    $defaultContextConfiguration->writeAttribute( 'data', 'vtpbx_customer_id=' . $customerID );
    $defaultContextConfiguration->endElement(); // action

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'set' );
    $defaultContextConfiguration->writeAttribute( 'data', 'ringback=${us-ring}' );
    $defaultContextConfiguration->endElement(); // action

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'set' );
    $defaultContextConfiguration->writeAttribute( 'data', 'transfer_ringback=$${hold_music}' );
    $defaultContextConfiguration->endElement(); // action

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'set' );
    $defaultContextConfiguration->writeAttribute( 'data', 'ringback=ringback=${us-ring}' );
    $defaultContextConfiguration->endElement(); // action


    // MOH
    // <action application="set" data="hold_music=/sounds/holdmusic.wav" />
    // <action application="bridge_export" data="hold_music=$${sounds_dir}/music/company-a.mp3"/>
    if($moh > 0){
        $mohFileName = PBX_IVR_FILES_BASE. getIVRFileNameByID($moh,$mysqli);


        $defaultContextConfiguration->startElement( 'action' );
        $defaultContextConfiguration->writeAttribute( 'application', 'set' );
        $defaultContextConfiguration->writeAttribute( 'data', 'hold_music=' . $mohFileName );
        $defaultContextConfiguration->endElement(); // action

        $defaultContextConfiguration->startElement( 'action' );
        $defaultContextConfiguration->writeAttribute( 'application', 'bridge_export' );
        $defaultContextConfiguration->writeAttribute( 'data', 'hold_music=' . $mohFileName );
        $defaultContextConfiguration->endElement(); // action

    }



    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'set' );
    $defaultContextConfiguration->writeAttribute( 'data', 'call_timeout='.$vm_timeout);
    $defaultContextConfiguration->endElement(); // action

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'set' );
    $defaultContextConfiguration->writeAttribute( 'data', 'hangup_after_bridge=true' );
    $defaultContextConfiguration->endElement(); // action

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'set' );
    $defaultContextConfiguration->writeAttribute( 'data', 'continue_on_fail=true' );
    $defaultContextConfiguration->endElement(); // action

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'hash' );
    $defaultContextConfiguration->writeAttribute( 'data', 'insert/${domain_name}-call_return/${dialed_extension}/${caller_id_number}' );
    $defaultContextConfiguration->endElement(); // action


    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'hash' );
    $defaultContextConfiguration->writeAttribute( 'data', 'insert/${domain_name}-last_dial_ext/${dialed_extension}/${uuid}' );
    $defaultContextConfiguration->endElement(); // action

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'set' );
    $defaultContextConfiguration->writeAttribute( 'data', 'called_party_callgroup=${user_data(${dialed_extension}@${domain_name} var callgroup)}' );
    $defaultContextConfiguration->endElement(); // action

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'hash' );
    $defaultContextConfiguration->writeAttribute( 'data', 'insert/${domain_name}-last_dial_ext/${called_party_callgroup}/${uuid}' );
    $defaultContextConfiguration->endElement(); // action

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'hash' );
    $defaultContextConfiguration->writeAttribute( 'data', 'insert/${domain_name}-last_dial_ext/global/${uuid}' );
    $defaultContextConfiguration->endElement(); // action

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'hash' );
    $defaultContextConfiguration->writeAttribute( 'data', 'insert/${domain_name}-last_dial/${called_party_callgroup}/${uuid}' );
    $defaultContextConfiguration->endElement(); // action



   // $defaultContextConfiguration->startElement('action');
   // $defaultContextConfiguration->writeAttribute('application', 'set');
   // $defaultContextConfiguration->writeAttribute('data', 'ignore_early_media=true');
   // $defaultContextConfiguration->endElement(); // action


    $defaultContextConfiguration->startElement('action');
    $defaultContextConfiguration->writeAttribute('application', 'set');
    $defaultContextConfiguration->writeAttribute('data', 'RECORD_STEREO=true');
    $defaultContextConfiguration->endElement(); // action


    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'record_session' );
    $defaultContextConfiguration->writeAttribute( 'data', $recordingDestinationShort );
    $defaultContextConfiguration->endElement(); // action

/*
    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'export' );
    $defaultContextConfiguration->writeAttribute( 'data', 'nolocal:rtp_secure_media=optional:AES_CM_128_HMAC_SHA1_80');
    $defaultContextConfiguration->writeAttribute( 'inline', 'true' );
    $defaultContextConfiguration->endElement(); // action

*/
    // forwarding goes here:
    $callForwardingStrategy = $callForwardingDetails["forwarding_strategy"];


    // required items for call-forwarding to external numbers:

    $callerid= determineUserExternalCallerID($domain,$extension,$mysqli);



    $customerID = getCustomerIDbyDomainName($domain,$mysqli);
    $customerDetails = getCustomerDetailsByID($customerID,$mysqli);

    $external_gateway_id = $customerDetails["sip_provider"];
    $external_gateway_prefix = $customerDetails["sip_provider_prefix"];


    $domainID = getDomainIDbyName($domain,$mysqli);
    $userDetails = getUserDetailsByUsernameDomain($extension,$domainID,$mysqli);
    $disable_external_calls = $userDetails["disable_external_calls"];



    switch($callForwardingStrategy){
        case "SIMULTANEOUS":{ // also called "Ring all"
            $forwardingNumbers = "";

            $offset = 1;

            do{
                // start

                $forwarding_enable = "forwarding_p".$offset."_enable";
                $forwarding_type = "forwarding_p".$offset."_type";
                $forwarding_number = "forwarding_p".$offset."_number";
                $forwarding_screening = "forwarding_p".$offset."_screening";    // TODO:  add support for call screening for forwarded calls.



                if(isset($callForwardingDetails[$forwarding_enable]) && $callForwardingDetails[$forwarding_enable] == "on"){

                    switch($callForwardingDetails[$forwarding_type]){
                        case "USER":{

                            //Forward to another extension

                            //   in this case the bridge data (string) should have second extension defined after the separator , example:
                            //
                            //  <action application="bridge" data="sofia/external/${dialed_extension}@${domain_name};fs_path=sip:$${vtpbx_proxy}:50600|sofia/external/FORWARDING_EXTENSION@${domain_name};fs_path=sip:$${vtpbx_proxy}:5060"/>
                            //

                            $forwardingNumbers .= ',[leg_timeout='.$call_timeout.']sofia/external/'.$callForwardingDetails[$forwarding_number].'@${domain_name};fs_path=sip:$${vtpbx_proxy}:'.VTPBX_PROXY_SIP_PORT;







                        }break;
                        case "EXT_NUMBER":{



                            // $callData =   '{sip_from_uri=sip:'.$callerid.'@'.$domain.', origination_caller_id_number='.$callerid.'}sofia/gateway/gw'.$external_gateway_id . '/' . $external_gateway_prefix . $number_dialed;


                            if($disable_external_calls != "1"){

                                $forwardingNumbers .= ',[leg_timeout='.$call_timeout.',sip_from_uri=sip:'.$callerid.'@'.$domain.', origination_caller_id_number='.$callerid.',ignore_early_media=true]sofia/gateway/gw'.$external_gateway_id.'/' . $external_gateway_prefix . $callForwardingDetails[$forwarding_number];
                                //  '.$callForwardingDetails[$forwarding_number].'@${domain_name};fs_path=sip:$${vtpbx_proxy}:'.VTPBX_PROXY_SIP_PORT;

                            }



                        }break;

                    }






                }


                $offset++;
            }while($offset < 11);


            //  Forwarding numbers prepared, let's inject them to the bridge command:


            $defaultContextConfiguration->startElement('action');
            $defaultContextConfiguration->writeAttribute('application', 'bridge');
            $defaultContextConfiguration->writeAttribute('data', '{originate_timeout=' . $call_timeout. '}sofia/external/${dialed_extension}@${domain_name};fs_path=sip:$${vtpbx_proxy}:'.VTPBX_PROXY_SIP_PORT . $forwardingNumbers);
            $defaultContextConfiguration->endElement(); // action



        }break;
        case "SEQUENTIAL":{

            // First we just dial the regular extension (user) by SIP:



            $defaultContextConfiguration->startElement('action');
            $defaultContextConfiguration->writeAttribute('application', 'bridge');
            $defaultContextConfiguration->writeAttribute('data', '{originate_timeout=' . $call_timeout .'}sofia/external/${dialed_extension}@${domain_name};fs_path=sip:$${vtpbx_proxy}:'.VTPBX_PROXY_SIP_PORT);
            $defaultContextConfiguration->endElement(); // action





            $offset = 1;

            do{
                // start

                $forwarding_enable = "forwarding_p".$offset."_enable";
                $forwarding_type = "forwarding_p".$offset."_type";
                $forwarding_number = "forwarding_p".$offset."_number";
                $forwarding_screening = "forwarding_p".$offset."_screening";    // TODO:  add support for call screening for forwarded calls.



                if(isset($callForwardingDetails[$forwarding_enable]) && $callForwardingDetails[$forwarding_enable] == "on"){

                    switch($callForwardingDetails[$forwarding_type]){
                        case "USER":{

                            //Forward to another extension

                            //   in this case the bridge data (string) should have second extension defined after the separator , example:
                            //
                            //  <action application="bridge" data="sofia/external/${dialed_extension}@${domain_name};fs_path=sip:$${vtpbx_proxy}:50600|sofia/external/FORWARDING_EXTENSION@${domain_name};fs_path=sip:$${vtpbx_proxy}:50600"/>
                            //

                            //$forwardingNumbers .= $forwarding_separator . '{call_timeout=' . $call_timeout. '}sofia/external/'.$callForwardingDetails[$forwarding_number].'@${domain_name};fs_path=sip:$${vtpbx_proxy}:'.VTPBX_PROXY_SIP_PORT;

                            $defaultContextConfiguration->startElement('action');
                            $defaultContextConfiguration->writeAttribute('application', 'bridge');
                            $defaultContextConfiguration->writeAttribute('data', '{originate_timeout=' . $call_timeout. '}sofia/external/'.$callForwardingDetails[$forwarding_number].'@${domain_name};fs_path=sip:$${vtpbx_proxy}:'.VTPBX_PROXY_SIP_PORT);
                            $defaultContextConfiguration->endElement(); // action


                        }break;
                        case "EXT_NUMBER":{




                            if($disable_external_calls != "1"){

                                // $callData =   '{sip_from_uri=sip:'.$callerid.'@'.$domain.', origination_caller_id_number='.$callerid.'}sofia/gateway/gw'.$external_gateway_id . '/' . $external_gateway_prefix . $number_dialed;

                                $defaultContextConfiguration->startElement('action');
                                $defaultContextConfiguration->writeAttribute('application', 'bridge');
                                $defaultContextConfiguration->writeAttribute('data', '{leg_timeout=' . $call_timeout . ', sip_from_uri=sip:' . $callerid . '@' . $domain . ', origination_caller_id_number=' . $callerid . ',ignore_early_media=true}sofia/gateway/gw' . $external_gateway_id . '/' . $external_gateway_prefix . $callForwardingDetails[$forwarding_number]);
                                $defaultContextConfiguration->endElement(); // action


                            }

                        }break;

                    }






                }


                $offset++;
            }while($offset < 11);




        }break;
        default:{

            //We are just dialing the regular extension (user) by SIP, no forwarding, no forking at all...

            $defaultContextConfiguration->startElement('action');
            $defaultContextConfiguration->writeAttribute('application', 'bridge');
            $defaultContextConfiguration->writeAttribute('data', '{originate_timeout=' . $call_timeout. '}sofia/external/${dialed_extension}@${domain_name};fs_path=sip:$${vtpbx_proxy}:'.VTPBX_PROXY_SIP_PORT);
            $defaultContextConfiguration->endElement(); // action




        }break;



    }


    // in case bridge failed we will answer the call and launch the voicemail application.
    // Voicemail for incoming calls to the extension:

    if($vm_enable == 1){



        $defaultContextConfiguration->startElement( 'action' );
        $defaultContextConfiguration->writeAttribute( 'application', 'answer' );
        $defaultContextConfiguration->endElement(); // action

        $defaultContextConfiguration->startElement( 'action' );
        $defaultContextConfiguration->writeAttribute( 'application', 'sleep' );
        $defaultContextConfiguration->writeAttribute( 'data', '1000' );
        $defaultContextConfiguration->endElement(); // action


        // VM greeting:
        //
        if(isset($vmDetails["vm_greeting"])  &&   intval($vmDetails["vm_greeting"]) >0 ){
            $vm_file_id = intval($vmDetails["vm_greeting"]);
            $greetingFileName = PBX_IVR_FILES_BASE. getIVRFileNameByID($vm_file_id,$mysqli);


            $defaultContextConfiguration->startElement( 'action' );
            $defaultContextConfiguration->writeAttribute( 'application', 'export' );
            $defaultContextConfiguration->writeAttribute( 'data', 'skip_greeting=true' );
            $defaultContextConfiguration->endElement(); // action


            $defaultContextConfiguration->startElement( 'action' );
            $defaultContextConfiguration->writeAttribute( 'application', 'playback' );
            $defaultContextConfiguration->writeAttribute( 'data', $greetingFileName );
            $defaultContextConfiguration->endElement(); // action


        }

        $defaultContextConfiguration->startElement('action');
        $defaultContextConfiguration->writeAttribute('application', 'set');
        $defaultContextConfiguration->writeAttribute('data', 'ignore_early_media=false');
        $defaultContextConfiguration->endElement(); // action



        $defaultContextConfiguration->startElement( 'action' );
        $defaultContextConfiguration->writeAttribute( 'application', 'export' );
        $defaultContextConfiguration->writeAttribute( 'data', 'vtpbx_base_call_uuid=${uuid}' );
        $defaultContextConfiguration->endElement(); // action

        $defaultContextConfiguration->startElement( 'action' );
        $defaultContextConfiguration->writeAttribute( 'application', 'bridge' );
        $defaultContextConfiguration->writeAttribute( 'data', 'loopback/app=voicemail:default ${domain_name} ${dialed_extension}' );
        $defaultContextConfiguration->endElement(); // action

    }



    $defaultContextConfiguration->endElement(); // condition

    $defaultContextConfiguration->endElement();  //extension







    $defaultContextConfiguration->endElement();  // context

    $defaultContextConfiguration->endElement(); // section
    $defaultContextConfiguration->endElement(); // document



    //Echo the whole XML document
    echo $defaultContextConfiguration->outputMemory();






}







function serveConfigurationLocalExtensionViaDID($domain,$extension,$recordingDestinationDefinition,$recordingDestinationShort = "",$destinationDomain = "",$destinationExtension = "", $customerID = "",$mysqli){

    $destination_number = '^' . $extension . '$';

    $dialed_extension = 'dialed_extension='.$destinationExtension;
    /*

      <extension name="Local_Extension">
         <condition field="destination_number" expression="^(1[0-9][0-9])$">
            <action application="export" data="dialed_extension=$1"/>
            <action application="bind_meta_app" data="1 b s execute_extension::dx XML features"/>
            <action application="bind_meta_app" data="2 b s record_session::$${recordings_dir}/${caller_id_number}.${strftime(%Y-%m-%d-%H-%M-%S)}.wav"/>
            <action application="bind_meta_app" data="3 b s execute_extension::cf XML features"/>
            <action application="bind_meta_app" data="4 b s execute_extension::att_xfer XML features"/>
            <action application="set" data="ringback=${us-ring}"/>
            <action application="set" data="transfer_ringback=$${hold_music}"/>
            <action application="set" data="call_timeout=30"/>
            <action application="set" data="hangup_after_bridge=true"/>
            <action application="set" data="continue_on_fail=true"/>
            <action application="hash" data="insert/${domain_name}-call_return/${dialed_extension}/${caller_id_number}"/>
            <action application="hash" data="insert/${domain_name}-last_dial_ext/${dialed_extension}/${uuid}"/>
            <action application="set" data="called_party_callgroup=${user_data(${dialed_extension}@${domain_name} var callgroup)}"/>
            <action application="hash" data="insert/${domain_name}-last_dial_ext/${called_party_callgroup}/${uuid}"/>
            <action application="hash" data="insert/${domain_name}-last_dial_ext/global/${uuid}"/>
            <action application="hash" data="insert/${domain_name}-last_dial/${called_party_callgroup}/${uuid}"/>
            <action application="bridge" data="sofia/external/${dialed_extension}@${domain_name};fs_path=sip:$${vtpbx_proxy}:50600"/>
           <action application="answer"/>
           <action application="sleep" data="1000"/>
           <action application="bridge" data="loopback/app=voicemail:default ${domain_name} ${dialed_extension}"/>
         </condition>
       </extension>


      */


    $destinationDomainID = getDomainIDbyName($destinationDomain,$mysqli);


    // Initial values (defaults)
    $call_timeout = TIMEOUT_CALL;     // default timeout when trying to reach a destination. After this amount of seconds system will failover to 2nd destination / voicemail, etc.
    $vm_timeout  = TIMEOUT_VM;

    $forwarding_separator = "|";   // by default we do "one by one" or "sequential" ring so the separator is |   . For simultaneous ring the separator will be ,



    $vmDetails = getUserVMdetails($destinationDomain,$destinationExtension,$mysqli);

    $vm_timeout_candidate = intval($vmDetails["vm_timeout"]);

    if($vm_timeout_candidate>0 && $vm_timeout_candidate < 61){
        error_log("VM:  override vm_timeout to [".$vm_timeout_candidate."]");
        $vm_timeout = $vm_timeout_candidate;
    }

    $vm_enable = intval($vmDetails["vm_enable"]);


    // MOH
    $moh = 0;
    $moh_candidate =  getMOHbyDomainName($destinationDomain,$mysqli);
    if($moh_candidate>0 ){
        error_log("MOH:  override MOH to [".$moh_candidate."]");
        $moh = $moh_candidate;
    }




    $ivr_delay = IVR_DELAY;

    // Forwarding rules:
    //   - this will be added AFTER the first and default destination which is the local extension number (in this function we generate the configuration to reach a local extension)
    $forwardingNumbers = "";

    $callForwardingDetailsJSON = getUserCFdetails($destinationDomain,$destinationExtension,$mysqli);
    $callForwardingDetails = json_decode($callForwardingDetailsJSON, true);

    error_log($callForwardingDetailsJSON);




    if($callForwardingDetails["forwarding_strategy"] == "SIMULTANEOUS"){
        error_log("CF:  override forwarding_strategy to [".$callForwardingDetails["forwarding_strategy"]."]");
        $forwarding_separator = ",";
    }else{
        //$vm_timeout = 60;
    }


    $call_timeout_candidate = intval($callForwardingDetails["call_timeout"]);

    if($call_timeout_candidate> 0 && $call_timeout_candidate < 60){
        error_log("CF:  override call_timeout to [".$callForwardingDetails["call_timeout"]."]");
        $call_timeout = $call_timeout_candidate;
    }



    // pre_answer_playback  section  - Play the IVR file as soon as the call comes in to the PBX via DID number.

    $pre_answer_playback = 0;
    $pre_answer_playback_file = PBX_IVR_DEFAULT_WHISPER;
    error_log("inside serveConfigurationLocalExtensionViaDID, extension [$extension] , customerid [$customerID] ,  destinationdomain [$destinationDomain], destinationdomainid [$destinationDomainID]");
    $didDetails = getDIDNumberDetailsForCustomer($extension,$customerID,$destinationDomainID,$mysqli);

    if(isset($didDetails["pre_answer_playback"])){
        $pre_answer_playback = $didDetails["pre_answer_playback"]  ;
        $pre_answer_playback_file = $didDetails["pre_answer_playback_file"]  ;


        if($pre_answer_playback > 0){
            // if the pre-answer-playback is being played we should find out if we play the default file or some custom one

            if($pre_answer_playback_file > 0 ){
                $fileName= getIVRFileNameByID($pre_answer_playback_file,$mysqli);

                $pre_answer_playback_file = PBX_IVR_FILES_BASE  . $fileName ;

            }


        }


    }


    error_log("pre-answer details:  [$pre_answer_playback][$pre_answer_playback_file]  ");


    // --------

    $defaultContextConfiguration = new XMLWriter();

    $defaultContextConfiguration->openMemory();

    $defaultContextConfiguration->setIndent( TRUE );
    $defaultContextConfiguration->setIndentString( '  ' );
    $defaultContextConfiguration->startDocument( '1.0', 'UTF-8', 'no' );
    //set the freeswitch document type
    $defaultContextConfiguration->startElement( 'document' );
    $defaultContextConfiguration->writeAttribute( 'type', 'freeswitch/xml' );

    $defaultContextConfiguration->startElement( 'section' );
    $defaultContextConfiguration->writeAttribute( 'name', 'dialplan' );



    $defaultContextConfiguration->startElement( 'context' );
    $defaultContextConfiguration->writeAttribute( 'name', $domain );



// -- Global -> prepare parameters for other features (spymap, last dial, etc.)

    $defaultContextConfiguration->startElement( 'extension' );
    $defaultContextConfiguration->writeAttribute( 'name', 'global' );
    $defaultContextConfiguration->writeAttribute( 'continue', 'true' );

    $defaultContextConfiguration->startElement( 'condition' );

    /*

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'hash' );
    $hashValue = 'insert/${domain_name}-spymap/${caller_id_number}/${uuid}';
    $defaultContextConfiguration->writeAttribute( 'data', $hashValue );
    $defaultContextConfiguration->endElement(); // action

    */

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'hash' );
    $hashValue = 'insert/${domain_name}-last_dial/${caller_id_number}/${destination_number}';
    $defaultContextConfiguration->writeAttribute( 'data', $hashValue );
    $defaultContextConfiguration->endElement(); // action

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'hash' );
    $hashValue = 'insert/${domain_name}-last_dial/global/${uuid}';
    $defaultContextConfiguration->writeAttribute( 'data', $hashValue );
    $defaultContextConfiguration->endElement(); // action

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'export' );
    $exportValue = 'RFC2822_DATE=${strftime(%a, %d %b %Y %T %z)}';
    $defaultContextConfiguration->writeAttribute( 'data', $exportValue );
    $defaultContextConfiguration->endElement(); // action



    $defaultContextConfiguration->endElement(); // condition







    $defaultContextConfiguration->endElement();  //extension



//






    $defaultContextConfiguration->startElement( 'extension' );
    $defaultContextConfiguration->writeAttribute( 'name', 'Local_Extension' );

    $defaultContextConfiguration->startElement( 'condition' );
    $defaultContextConfiguration->writeAttribute( 'field', 'destination_number' );
    $defaultContextConfiguration->writeAttribute( 'expression', $destination_number );



    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'export' );
    $defaultContextConfiguration->writeAttribute( 'data', $dialed_extension );
    $defaultContextConfiguration->endElement(); // action


    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'export' );
    $defaultContextConfiguration->writeAttribute( 'data', "force_transfer_context=" . $destinationDomain );
    $defaultContextConfiguration->endElement(); // action


    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'set' );
    $defaultContextConfiguration->writeAttribute( 'data', 'vtpbx_destination_type=EXTENSION' );
    $defaultContextConfiguration->endElement(); // action

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'set' );
    $defaultContextConfiguration->writeAttribute( 'data', 'vtpbx_destination_def=' . $dialed_extension );
    $defaultContextConfiguration->endElement(); // action


    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'set' );
    $defaultContextConfiguration->writeAttribute( 'data', 'vtpbx_customer_id=' . $customerID );
    $defaultContextConfiguration->endElement(); // action


    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'set' );
    $defaultContextConfiguration->writeAttribute( 'data', 'ringback=${us-ring}' );
    $defaultContextConfiguration->endElement(); // action

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'set' );
    $defaultContextConfiguration->writeAttribute( 'data', 'transfer_ringback=${us-ring}' );
    $defaultContextConfiguration->endElement(); // action



    // MOH
    // <action application="set" data="hold_music=/sounds/holdmusic.wav" />
    // <action application="bridge_export" data="hold_music=$${sounds_dir}/music/company-a.mp3"/>
    if($moh > 0){
        $mohFileName = PBX_IVR_FILES_BASE. getIVRFileNameByID($moh,$mysqli);


        $defaultContextConfiguration->startElement( 'action' );
        $defaultContextConfiguration->writeAttribute( 'application', 'set' );
        $defaultContextConfiguration->writeAttribute( 'data', 'hold_music=' . $mohFileName );
        $defaultContextConfiguration->endElement(); // action

        $defaultContextConfiguration->startElement( 'action' );
        $defaultContextConfiguration->writeAttribute( 'application', 'bridge_export' );
        $defaultContextConfiguration->writeAttribute( 'data', 'hold_music=' . $mohFileName );
        $defaultContextConfiguration->endElement(); // action

    }






    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'set' );
    $defaultContextConfiguration->writeAttribute( 'data', 'call_timeout=120');  // Whole ringing can have up to 120 seconds. Individual endpoints can have different ringing timeout.
    $defaultContextConfiguration->endElement(); // action

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'set' );
    $defaultContextConfiguration->writeAttribute( 'data', 'hangup_after_bridge=true' );
    $defaultContextConfiguration->endElement(); // action

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'set' );
    $defaultContextConfiguration->writeAttribute( 'data', 'continue_on_fail=true' );
    $defaultContextConfiguration->endElement(); // action

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'hash' );
    $defaultContextConfiguration->writeAttribute( 'data', 'insert/'. $destinationDomain . '-call_return/${dialed_extension}/${caller_id_number}' );
    $defaultContextConfiguration->endElement(); // action


    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'hash' );
    $hashValue = 'insert/'. $destinationDomain . '-spymap/'.$destinationExtension.'/${uuid}';
    $defaultContextConfiguration->writeAttribute( 'data', $hashValue );
    $defaultContextConfiguration->endElement(); // action



    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'hash' );
    $defaultContextConfiguration->writeAttribute( 'data', 'insert/${domain_name}-last_dial_ext/${dialed_extension}/${uuid}' );
    $defaultContextConfiguration->endElement(); // action

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'set' );
    $defaultContextConfiguration->writeAttribute( 'data', 'called_party_callgroup=${user_data(${dialed_extension}@${domain_name} var callgroup)}' );
    $defaultContextConfiguration->endElement(); // action

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'hash' );
    $defaultContextConfiguration->writeAttribute( 'data', 'insert/${domain_name}-last_dial_ext/${called_party_callgroup}/${uuid}' );
    $defaultContextConfiguration->endElement(); // action

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'hash' );
    $defaultContextConfiguration->writeAttribute( 'data', 'insert/${domain_name}-last_dial_ext/global/${uuid}' );
    $defaultContextConfiguration->endElement(); // action

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'hash' );
    $defaultContextConfiguration->writeAttribute( 'data', 'insert/${domain_name}-last_dial/${called_party_callgroup}/${uuid}' );
    $defaultContextConfiguration->endElement(); // action

    $defaultContextConfiguration->startElement('action');
    $defaultContextConfiguration->writeAttribute('application', 'set');
    $defaultContextConfiguration->writeAttribute('data', 'RECORD_STEREO=true');
    $defaultContextConfiguration->endElement(); // action


    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'record_session' );
    $defaultContextConfiguration->writeAttribute( 'data', $recordingDestinationShort );
    $defaultContextConfiguration->endElement(); // action

    /*
    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'export' );
    $defaultContextConfiguration->writeAttribute( 'data', 'nolocal:rtp_secure_media=optional:AES_CM_128_HMAC_SHA1_80');
    $defaultContextConfiguration->writeAttribute( 'inline', 'true' );
    $defaultContextConfiguration->endElement(); // action
    */



    // Playback pre-answer IVR (Whisper)

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'answer' );
    $defaultContextConfiguration->endElement(); // action

    if($pre_answer_playback>0){




        $defaultContextConfiguration->startElement( 'action' );
        $defaultContextConfiguration->writeAttribute( 'application', 'sleep' );
        $defaultContextConfiguration->writeAttribute( 'data', $ivr_delay );
        $defaultContextConfiguration->endElement(); // action


        $defaultContextConfiguration->startElement( 'action' );
        $defaultContextConfiguration->writeAttribute( 'application', 'playback' );
        $defaultContextConfiguration->writeAttribute( 'data', $pre_answer_playback_file );
        $defaultContextConfiguration->endElement(); // action


    }



    // forwarding goes here:
    $callForwardingStrategy = $callForwardingDetails["forwarding_strategy"];


    // required items for call-forwarding to external numbers:

    $callerid= determineUserExternalCallerID($destinationDomain,$destinationExtension,$mysqli);

    //OVERRIDE caller-id for forwarded calls from the users's CID to the original CID of the call
    // requested by Chip on 2020-8-20
    $callerid = '${caller_id_number}';



    $customerID = getCustomerIDbyDomainName($destinationDomain,$mysqli);
    $customerDetails = getCustomerDetailsByID($customerID,$mysqli);

    $external_gateway_id = $customerDetails["sip_provider"];
    $external_gateway_prefix = $customerDetails["sip_provider_prefix"];


    $domainID = getDomainIDbyName($domain,$mysqli);
    $userDetails = getUserDetailsByUsernameDomain($extension,$domainID,$mysqli);
    $disable_external_calls = $userDetails["disable_external_calls"];

    error_log("In this case the disable_external_calls flag value is [$disable_external_calls]  ");



    switch($callForwardingStrategy){
        case "SIMULTANEOUS":{ // also called "Ring all"
            $forwardingNumbers = "";

            $offset = 1;

            do{
                // start

                $forwarding_enable = "forwarding_p".$offset."_enable";
                $forwarding_type = "forwarding_p".$offset."_type";
                $forwarding_number = "forwarding_p".$offset."_number";
                $forwarding_screening = "forwarding_p".$offset."_screening";    // TODO:  add support for call screening for forwarded calls.



                if(isset($callForwardingDetails[$forwarding_enable]) && $callForwardingDetails[$forwarding_enable] == "on"){

                    switch($callForwardingDetails[$forwarding_type]){
                        case "USER":{

                            //Forward to another extension

                            //   in this case the bridge data (string) should have second extension defined after the separator , example:
                            //
                            //  <action application="bridge" data="sofia/external/${dialed_extension}@${domain_name};fs_path=sip:$${vtpbx_proxy}:50600|sofia/external/FORWARDING_EXTENSION@${domain_name};fs_path=sip:$${vtpbx_proxy}:5060"/>
                            //

                            $forwardingNumbers .= ',[leg_timeout='.$call_timeout.']sofia/external/'.$callForwardingDetails[$forwarding_number].'@'.$destinationDomain.';fs_path=sip:$${vtpbx_proxy}:'.VTPBX_PROXY_SIP_PORT;







                        }break;
                        case "EXT_NUMBER":{



                            // $callData =   '{sip_from_uri=sip:'.$callerid.'@'.$domain.', origination_caller_id_number='.$callerid.'}sofia/gateway/gw'.$external_gateway_id . '/' . $external_gateway_prefix . $number_dialed;


                            if($disable_external_calls != "1"){


                                $forwardingNumbers .= ',[leg_timeout='.$call_timeout.',sip_from_uri=sip:'.$callerid.'@'.$destinationDomain.', origination_caller_id_number='.$callerid.',ignore_early_media=true]sofia/gateway/gw'.$external_gateway_id.'/' . $external_gateway_prefix . $callForwardingDetails[$forwarding_number];
                                //  '.$callForwardingDetails[$forwarding_number].'@${domain_name};fs_path=sip:$${vtpbx_proxy}:'.VTPBX_PROXY_SIP_PORT;


                            }



                        }break;

                    }






                }


                $offset++;
            }while($offset < 11);


            //  Forwarding numbers prepared, let's inject them to the bridge command:

             $defaultContextConfiguration->startElement('action');
             $defaultContextConfiguration->writeAttribute('application', 'set');
             $defaultContextConfiguration->writeAttribute('data', 'ignore_early_media=true');
             $defaultContextConfiguration->endElement(); // action



            $defaultContextConfiguration->startElement('action');
            $defaultContextConfiguration->writeAttribute('application', 'bridge');
            $defaultContextConfiguration->writeAttribute('data', '{originate_timeout=' . $vm_timeout. '}sofia/external/${dialed_extension}@'.$destinationDomain.';fs_path=sip:$${vtpbx_proxy}:'.VTPBX_PROXY_SIP_PORT . $forwardingNumbers);
            $defaultContextConfiguration->endElement(); // action



        }break;
        case "SEQUENTIAL":{

            // First we just dial the regular extension (user) by SIP:



            $defaultContextConfiguration->startElement('action');
            $defaultContextConfiguration->writeAttribute('application', 'bridge');
            $defaultContextConfiguration->writeAttribute('data', '{originate_timeout=' . $vm_timeout .'}sofia/external/${dialed_extension}@'.$destinationDomain.';fs_path=sip:$${vtpbx_proxy}:'.VTPBX_PROXY_SIP_PORT);
            $defaultContextConfiguration->endElement(); // action





            $offset = 1;

            do{
                // start

                $forwarding_enable = "forwarding_p".$offset."_enable";
                $forwarding_type = "forwarding_p".$offset."_type";
                $forwarding_number = "forwarding_p".$offset."_number";
                $forwarding_screening = "forwarding_p".$offset."_screening";    // TODO:  add support for call screening for forwarded calls.



                if(isset($callForwardingDetails[$forwarding_enable]) && $callForwardingDetails[$forwarding_enable] == "on"){

                    switch($callForwardingDetails[$forwarding_type]){
                        case "USER":{

                            //Forward to another extension

                            //   in this case the bridge data (string) should have second extension defined after the separator , example:
                            //
                            //  <action application="bridge" data="sofia/external/${dialed_extension}@${domain_name};fs_path=sip:$${vtpbx_proxy}:50600|sofia/external/FORWARDING_EXTENSION@${domain_name};fs_path=sip:$${vtpbx_proxy}:50600"/>
                            //

                            //$forwardingNumbers .= $forwarding_separator . '{call_timeout=' . $call_timeout. '}sofia/external/'.$callForwardingDetails[$forwarding_number].'@${domain_name};fs_path=sip:$${vtpbx_proxy}:'.VTPBX_PROXY_SIP_PORT;

                            $defaultContextConfiguration->startElement('action');
                            $defaultContextConfiguration->writeAttribute('application', 'bridge');
                            $defaultContextConfiguration->writeAttribute('data', '{originate_timeout=' . $call_timeout. '}sofia/external/'.$callForwardingDetails[$forwarding_number].'@'.$destinationDomain.';fs_path=sip:$${vtpbx_proxy}:'.VTPBX_PROXY_SIP_PORT);
                            $defaultContextConfiguration->endElement(); // action


                        }break;
                        case "EXT_NUMBER":{



                            if($disable_external_calls != "1"){


                                // $callData =   '{sip_from_uri=sip:'.$callerid.'@'.$domain.', origination_caller_id_number='.$callerid.'}sofia/gateway/gw'.$external_gateway_id . '/' . $external_gateway_prefix . $number_dialed;

                                $defaultContextConfiguration->startElement('action');
                                $defaultContextConfiguration->writeAttribute('application', 'bridge');
                                $defaultContextConfiguration->writeAttribute('data', '{leg_timeout=' . $call_timeout . ', sip_from_uri=sip:' . $callerid . '@' . $destinationDomain . ', origination_caller_id_number=' . $callerid . ',ignore_early_media=true}sofia/gateway/gw' . $external_gateway_id . '/' . $external_gateway_prefix . $callForwardingDetails[$forwarding_number]);
                                $defaultContextConfiguration->endElement(); // action


                            }

                        }break;

                    }






                }


                $offset++;
            }while($offset < 11);




        }break;
        default:{

            //We are just dialing the regular extension (user) by SIP, no forwarding, no forking at all...

            $defaultContextConfiguration->startElement('action');
            $defaultContextConfiguration->writeAttribute('application', 'bridge');
            $defaultContextConfiguration->writeAttribute('data', '{originate_timeout=' . $vm_timeout. '}sofia/external/${dialed_extension}@'.$destinationDomain.';fs_path=sip:$${vtpbx_proxy}:'.VTPBX_PROXY_SIP_PORT);
            $defaultContextConfiguration->endElement(); // action




        }break;



    }















    if($vm_enable == 1){



        $defaultContextConfiguration->startElement( 'action' );
        $defaultContextConfiguration->writeAttribute( 'application', 'answer' );
        $defaultContextConfiguration->endElement(); // action

        $defaultContextConfiguration->startElement( 'action' );
        $defaultContextConfiguration->writeAttribute( 'application', 'sleep' );
        $defaultContextConfiguration->writeAttribute( 'data', '1000' );
        $defaultContextConfiguration->endElement(); // action




        // VM greeting:
        //
        if(isset($vmDetails["vm_greeting"])  &&   intval($vmDetails["vm_greeting"]) >0 ){
            $vm_file_id = intval($vmDetails["vm_greeting"]);
            $greetingFileName = PBX_IVR_FILES_BASE. getIVRFileNameByID($vm_file_id,$mysqli);


            $defaultContextConfiguration->startElement( 'action' );
            $defaultContextConfiguration->writeAttribute( 'application', 'export' );
            $defaultContextConfiguration->writeAttribute( 'data', 'skip_greeting=true' );
            $defaultContextConfiguration->endElement(); // action


            $defaultContextConfiguration->startElement( 'action' );
            $defaultContextConfiguration->writeAttribute( 'application', 'playback' );
            $defaultContextConfiguration->writeAttribute( 'data', $greetingFileName );
            $defaultContextConfiguration->endElement(); // action


        }


        $defaultContextConfiguration->startElement('action');
        $defaultContextConfiguration->writeAttribute('application', 'set');
        $defaultContextConfiguration->writeAttribute('data', 'ignore_early_media=false');
        $defaultContextConfiguration->endElement(); // action




        $defaultContextConfiguration->startElement( 'action' );
        $defaultContextConfiguration->writeAttribute( 'application', 'export' );
        $defaultContextConfiguration->writeAttribute( 'data', 'vtpbx_base_call_uuid=${uuid}' );
        $defaultContextConfiguration->endElement(); // action




        $defaultContextConfiguration->startElement( 'action' );
        $defaultContextConfiguration->writeAttribute( 'application', 'bridge' );
        $defaultContextConfiguration->writeAttribute( 'data', 'loopback/app=voicemail:default '.$destinationDomain.' ${dialed_extension}' );
        $defaultContextConfiguration->endElement(); // action


    }


    $defaultContextConfiguration->endElement(); // condition

    $defaultContextConfiguration->endElement();  //extension







    $defaultContextConfiguration->endElement();  // context

    $defaultContextConfiguration->endElement(); // section
    $defaultContextConfiguration->endElement(); // document



    //Echo the whole XML document
    echo $defaultContextConfiguration->outputMemory();






}





function serveConfigurationGroupViaDID($domain,$extension,$recordingDestinationDefinition,$recordingDestinationShort = "",$destinationDomain = "",$destinationExtension = "", $customerID = "",$mysqli){

    error_log("Inside serveConfigurationGroupViaDID,    domain  [$domain], extension [$extension] ,recordingDestinationDefinition [$recordingDestinationDefinition],recordingDestinationShort [$recordingDestinationShort], destinationDomain [$destinationDomain],destinationExtension  [$destinationExtension],customerID [$customerID]        ");

   // Inside serveConfigurationGroupViaDID,    domain  [d1.callertech.net], extension [1] ,recordingDestinationDefinition [2 b s record_session::$${recordings_dir}/d1.callertech.net/897ab8d0-7f9f-490d-8a96-7341aa7d37e0.wav],recordingDestinationShort [/opt/ctpbx/recordings/d1.callertech.net/897ab8d0-7f9f-490d-8a96-7341aa7d37e0.wav], destinationDomain [d1.callertech.net],destinationExtension  [1],customerID [1]


    $destination_number = '^' . $extension . '$';

    $dialed_extension = 'dialed_extension='.$destinationExtension;
    /*

      <extension name="Local_Extension">
         <condition field="destination_number" expression="^(1[0-9][0-9])$">
            <action application="export" data="dialed_extension=$1"/>
            <action application="bind_meta_app" data="1 b s execute_extension::dx XML features"/>
            <action application="bind_meta_app" data="2 b s record_session::$${recordings_dir}/${caller_id_number}.${strftime(%Y-%m-%d-%H-%M-%S)}.wav"/>
            <action application="bind_meta_app" data="3 b s execute_extension::cf XML features"/>
            <action application="bind_meta_app" data="4 b s execute_extension::att_xfer XML features"/>
            <action application="set" data="ringback=${us-ring}"/>
            <action application="set" data="transfer_ringback=$${hold_music}"/>
            <action application="set" data="call_timeout=30"/>
            <action application="set" data="hangup_after_bridge=true"/>
            <action application="set" data="continue_on_fail=true"/>
            <action application="hash" data="insert/${domain_name}-call_return/${dialed_extension}/${caller_id_number}"/>
            <action application="hash" data="insert/${domain_name}-last_dial_ext/${dialed_extension}/${uuid}"/>
            <action application="set" data="called_party_callgroup=${user_data(${dialed_extension}@${domain_name} var callgroup)}"/>
            <action application="hash" data="insert/${domain_name}-last_dial_ext/${called_party_callgroup}/${uuid}"/>
            <action application="hash" data="insert/${domain_name}-last_dial_ext/global/${uuid}"/>
            <action application="hash" data="insert/${domain_name}-last_dial/${called_party_callgroup}/${uuid}"/>
            <action application="bridge" data="sofia/external/${dialed_extension}@${domain_name};fs_path=sip:$${vtpbx_proxy}:50600"/>
           <action application="answer"/>
           <action application="sleep" data="1000"/>
           <action application="bridge" data="loopback/app=voicemail:default ${domain_name} ${dialed_extension}"/>
         </condition>
       </extension>


      */


    $destinationDomainID = getDomainIDbyName($destinationDomain,$mysqli);


    // Initial values (defaults)
    $call_timeout = TIMEOUT_CALL;     // default timeout when trying to reach a destination. After this amount of seconds system will failover to 2nd destination / voicemail, etc.
    $vm_timeout  = TIMEOUT_VM;

    $forwarding_separator = "|";   // by default we do "one by one" or "sequential" ring so the separator is |   . For simultaneous ring the separator will be ,



    // Group members instead of forwarding rules:
    $forwardingNumbers = "";


    $groupDetails = getGroupDetailsByID($destinationExtension,$mysqli);
    /*
                    "id" => $groupID,
                    "customer" => $customer,
                    "domain" => $domain,
                    "name" => $name,
                    "ring_strategy" => $ring_strategy,
                    "group_members" => $group_membersArr


     */

    $ring_strategy = $groupDetails["ring_strategy"];
    $group_members = $groupDetails["group_members"];




    // Failover destination after the group:
    $group_failover_extension = 0;

    if(isset($groupDetails["group_failover_extension"])){
        $group_failover_extension = intval($groupDetails["group_failover_extension"]);
    }



    // VM settings for extension used for failover (after the group)
    $vmDetails = array();

    $failoverVMuserDetails = getUserDetailsByUsernameDomain($group_failover_extension,$destinationDomainID,$mysqli);


    $userID = $failoverVMuserDetails["id"];

    if( $userID > 0 ){

        $vmDetails  =  getUserVMdetails($destinationDomain,$group_failover_extension,$mysqli);

    }






    // --------  CONFIGURATION

    $defaultContextConfiguration = new XMLWriter();

    $defaultContextConfiguration->openMemory();

    $defaultContextConfiguration->setIndent( TRUE );
    $defaultContextConfiguration->setIndentString( '  ' );
    $defaultContextConfiguration->startDocument( '1.0', 'UTF-8', 'no' );
    //set the freeswitch document type
    $defaultContextConfiguration->startElement( 'document' );
    $defaultContextConfiguration->writeAttribute( 'type', 'freeswitch/xml' );

    $defaultContextConfiguration->startElement( 'section' );
    $defaultContextConfiguration->writeAttribute( 'name', 'dialplan' );



    $defaultContextConfiguration->startElement( 'context' );
    $defaultContextConfiguration->writeAttribute( 'name', $domain );


// -- Global -> prepare parameters for other features (spymap, last dial, etc.)
    //TODO:  check if this is required....

    $defaultContextConfiguration->startElement( 'extension' );
    $defaultContextConfiguration->writeAttribute( 'name', 'global' );
    $defaultContextConfiguration->writeAttribute( 'continue', 'true' );

    $defaultContextConfiguration->startElement( 'condition' );

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'hash' );
    $hashValue = 'insert/${domain_name}-spymap/${caller_id_number}/${uuid}';
    $defaultContextConfiguration->writeAttribute( 'data', $hashValue );
    $defaultContextConfiguration->endElement(); // action

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'hash' );
    $hashValue = 'insert/${domain_name}-last_dial/${caller_id_number}/${destination_number}';
    $defaultContextConfiguration->writeAttribute( 'data', $hashValue );
    $defaultContextConfiguration->endElement(); // action

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'hash' );
    $hashValue = 'insert/${domain_name}-last_dial/global/${uuid}';
    $defaultContextConfiguration->writeAttribute( 'data', $hashValue );
    $defaultContextConfiguration->endElement(); // action

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'export' );
    $exportValue = 'RFC2822_DATE=${strftime(%a, %d %b %Y %T %z)}';
    $defaultContextConfiguration->writeAttribute( 'data', $exportValue );
    $defaultContextConfiguration->endElement(); // action

    $defaultContextConfiguration->endElement(); // condition

    $defaultContextConfiguration->endElement();  //extension

//


    $defaultContextConfiguration->startElement( 'extension' );
    $defaultContextConfiguration->writeAttribute( 'name', 'Group' );

    $defaultContextConfiguration->startElement( 'condition' );
    $defaultContextConfiguration->writeAttribute( 'field', 'destination_number' );
    $defaultContextConfiguration->writeAttribute( 'expression', $destination_number );



    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'export' );
    $defaultContextConfiguration->writeAttribute( 'data', $dialed_extension );
    $defaultContextConfiguration->endElement(); // action




    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'set' );
    $defaultContextConfiguration->writeAttribute( 'data', 'vtpbx_destination_type=GROUP' );
    $defaultContextConfiguration->endElement(); // action

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'set' );
    $defaultContextConfiguration->writeAttribute( 'data', 'vtpbx_destination_def=' . $extension );   //group ID goes here
    $defaultContextConfiguration->endElement(); // action


    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'set' );
    $defaultContextConfiguration->writeAttribute( 'data', 'vtpbx_customer_id=' . $customerID );
    $defaultContextConfiguration->endElement(); // action


    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'set' );
    $defaultContextConfiguration->writeAttribute( 'data', 'ringback=${us-ring}' );
    $defaultContextConfiguration->endElement(); // action

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'set' );
    $defaultContextConfiguration->writeAttribute( 'data', 'transfer_ringback=${us-ring}' );
    $defaultContextConfiguration->endElement(); // action

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'set' );
    $defaultContextConfiguration->writeAttribute( 'data', 'call_timeout=180');  // Whole ringing can have up to 180 seconds. Individual endpoints can have different ringing timeout.
    $defaultContextConfiguration->endElement(); // action

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'set' );
    $defaultContextConfiguration->writeAttribute( 'data', 'hangup_after_bridge=true' );
    $defaultContextConfiguration->endElement(); // action

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'set' );
    $defaultContextConfiguration->writeAttribute( 'data', 'continue_on_fail=true' );
    $defaultContextConfiguration->endElement(); // action

    /*
    //   This won't work here, we're not dialing just one extension = user but a group...

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'hash' );
    $defaultContextConfiguration->writeAttribute( 'data', 'insert/'. $destinationDomain . '-call_return/${dialed_extension}/${caller_id_number}' );
    $defaultContextConfiguration->endElement(); // action
    */

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'hash' );
    $defaultContextConfiguration->writeAttribute( 'data', 'insert/${domain_name}-last_dial_ext/${dialed_extension}/${uuid}' );
    $defaultContextConfiguration->endElement(); // action

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'set' );
    $defaultContextConfiguration->writeAttribute( 'data', 'called_party_callgroup=${user_data(${dialed_extension}@${domain_name} var callgroup)}' );
    $defaultContextConfiguration->endElement(); // action

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'hash' );
    $defaultContextConfiguration->writeAttribute( 'data', 'insert/${domain_name}-last_dial_ext/${called_party_callgroup}/${uuid}' );
    $defaultContextConfiguration->endElement(); // action

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'hash' );
    $defaultContextConfiguration->writeAttribute( 'data', 'insert/${domain_name}-last_dial_ext/global/${uuid}' );
    $defaultContextConfiguration->endElement(); // action

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'hash' );
    $defaultContextConfiguration->writeAttribute( 'data', 'insert/${domain_name}-last_dial/${called_party_callgroup}/${uuid}' );
    $defaultContextConfiguration->endElement(); // action

    /*
    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'export' );
    $defaultContextConfiguration->writeAttribute( 'data', 'nolocal:rtp_secure_media=optional:AES_CM_128_HMAC_SHA1_80');
    $defaultContextConfiguration->writeAttribute( 'inline', 'true' );
    $defaultContextConfiguration->endElement(); // action
    */





    // required items for call-forwarding to external numbers:

    $callerid= determineUserExternalCallerID($destinationDomain,$destinationExtension,$mysqli);
    $customerID = getCustomerIDbyDomainName($destinationDomain,$mysqli);
    $customerDetails = getCustomerDetailsByID($customerID,$mysqli);

    $external_gateway_id = $customerDetails["sip_provider"];
    $external_gateway_prefix = $customerDetails["sip_provider_prefix"];


    switch($ring_strategy){

        case "0":{ // SEQUENTIAL - one by one in the order


            $offset = 1;

            do{
                // start

                $forwarding_enable = $offset."_enable";
                $forwarding_type = $offset."_type";
                $forwarding_number = $offset."_number";
                $forwarding_timeout = $offset."_timeout";

                if(isset($group_members[$forwarding_enable]) && $group_members[$forwarding_enable] == "on"){

                    switch($group_members[$forwarding_type]){
                        case "USER":{

                            $defaultContextConfiguration->startElement( 'action' );
                            $defaultContextConfiguration->writeAttribute( 'application', 'hash' );
                            $hashValue = 'insert/${domain_name}-spymap/'.$group_members[$forwarding_number].'/${uuid}';
                            $defaultContextConfiguration->writeAttribute( 'data', $hashValue );
                            $defaultContextConfiguration->endElement(); // action

                            $defaultContextConfiguration->startElement( 'action' );
                            $defaultContextConfiguration->writeAttribute( 'application', 'hash' );
                            $defaultContextConfiguration->writeAttribute( 'data', 'insert/'. $destinationDomain . '-call_return/'.$group_members[$forwarding_number].'/${caller_id_number}' );
                            $defaultContextConfiguration->endElement(); // action


                            $defaultContextConfiguration->startElement('action');
                            $defaultContextConfiguration->writeAttribute('application', 'bridge');
                            $defaultContextConfiguration->writeAttribute('data', '{originate_timeout=' . $group_members[$forwarding_timeout]. '}sofia/external/'.$group_members[$forwarding_number].'@'.$destinationDomain.';fs_path=sip:$${vtpbx_proxy}:'.VTPBX_PROXY_SIP_PORT);
                            $defaultContextConfiguration->endElement(); // action

                        }break;
                        case "EXT_NUMBER":{

                            $defaultContextConfiguration->startElement('action');
                            $defaultContextConfiguration->writeAttribute('application', 'bridge');
                            // MODIFICATION
                            // OLD:   (Caller ID changed)
                            //$defaultContextConfiguration->writeAttribute('data', '{leg_timeout=' . $group_members[$forwarding_timeout]. ', sip_from_uri=sip:'.$callerid.'@'.$destinationDomain.', origination_caller_id_number='.$callerid.',ignore_early_media=true}sofia/gateway/gw'.$external_gateway_id.'/' . $external_gateway_prefix . $group_members[$forwarding_number]);

                            // NEW:  (caller ID not changed)
                            $defaultContextConfiguration->writeAttribute('data', '{leg_timeout=' . $group_members[$forwarding_timeout]. ', sip_from_uri=sip:${caller_id_number}@'.$destinationDomain.', ignore_early_media=true}sofia/gateway/gw'.$external_gateway_id.'/' . $external_gateway_prefix . $group_members[$forwarding_number]);



                            $defaultContextConfiguration->endElement(); // action

                        }break;
                    }
                }
                $offset++;
            }while($offset < 11);


        }break;

        case "1":{ // SIMULTANEOUS also called "Ring all"
            $forwardingNumbers = "";
            $offset = 1;

            do{
                // start

                $forwarding_enable = $offset."_enable";
                $forwarding_type = $offset."_type";
                $forwarding_number = $offset."_number";
                $forwarding_timeout = $offset."_timeout";


                if(isset($group_members[$forwarding_enable]) && $group_members[$forwarding_enable] == "on"){

                    switch($group_members[$forwarding_type]){
                        case "USER":{

                            $defaultContextConfiguration->startElement( 'action' );
                            $defaultContextConfiguration->writeAttribute( 'application', 'hash' );
                            $hashValue = 'insert/${domain_name}-spymap/'.$group_members[$forwarding_number].'/${uuid}';
                            $defaultContextConfiguration->writeAttribute( 'data', $hashValue );
                            $defaultContextConfiguration->endElement(); // action


                            $defaultContextConfiguration->startElement( 'action' );
                            $defaultContextConfiguration->writeAttribute( 'application', 'hash' );
                            $defaultContextConfiguration->writeAttribute( 'data', 'insert/'. $destinationDomain . '-call_return/'.$group_members[$forwarding_number].'/${caller_id_number}' );
                            $defaultContextConfiguration->endElement(); // action


                            //Forward to another extension
                            $forwardingNumbers .= ',[leg_timeout='.$group_members[$forwarding_timeout].']sofia/external/'.$group_members[$forwarding_number].'@'.$destinationDomain.';fs_path=sip:$${vtpbx_proxy}:'.VTPBX_PROXY_SIP_PORT;

                        }break;
                        case "EXT_NUMBER":{


                            // MODIFICATION
                            // OLD:   (Caller ID changed)
                            //$forwardingNumbers .= ',[leg_timeout='.$group_members[$forwarding_timeout].',sip_from_uri=sip:'.$callerid.'@'.$destinationDomain.', origination_caller_id_number='.$callerid.',ignore_early_media=true]sofia/gateway/gw'.$external_gateway_id.'/' . $external_gateway_prefix . $group_members[$forwarding_number];

                            // NEW:  (caller ID not changed)
                            $forwardingNumbers .= ',[leg_timeout='.$group_members[$forwarding_timeout].',sip_from_uri=sip:${caller_id_number}@'.$destinationDomain.',ignore_early_media=true]sofia/gateway/gw'.$external_gateway_id.'/' . $external_gateway_prefix . $group_members[$forwarding_number];


                        }break;

                    }

                }

                $offset++;
            }while($offset < 11);


            //  Forwarding numbers prepared, let's inject them to the bridge command:
            // first character should be removed (it's the ",").

            $forwardingNumbers = substr($forwardingNumbers,1);

            $defaultContextConfiguration->startElement('action');
            $defaultContextConfiguration->writeAttribute('application', 'bridge');
            $defaultContextConfiguration->writeAttribute('data',  $forwardingNumbers);
            $defaultContextConfiguration->endElement(); // action


        }break;


        case "2":{ // ROUND-ROBIN - one by one in the random order

            $offsets = array(1,2,3,4,5,6,7,8,9,10);
            shuffle($offsets);

            error_log("Round-robin offsets array:" . json_encode($offsets));


            foreach($offsets as $offset){
                // start
                error_log("Round-robin offset [$offset]");
                $forwarding_enable = $offset."_enable";
                $forwarding_type = $offset."_type";
                $forwarding_number = $offset."_number";
                $forwarding_timeout = $offset."_timeout";


                if(isset($group_members[$forwarding_enable]) && $group_members[$forwarding_enable] == "on"){

                    switch($group_members[$forwarding_type]){
                        case "USER":{

                            $defaultContextConfiguration->startElement( 'action' );
                            $defaultContextConfiguration->writeAttribute( 'application', 'hash' );
                            $hashValue = 'insert/${domain_name}-spymap/'.$group_members[$forwarding_number].'/${uuid}';
                            $defaultContextConfiguration->writeAttribute( 'data', $hashValue );
                            $defaultContextConfiguration->endElement(); // action


                            $defaultContextConfiguration->startElement( 'action' );
                            $defaultContextConfiguration->writeAttribute( 'application', 'hash' );
                            $defaultContextConfiguration->writeAttribute( 'data', 'insert/'. $destinationDomain . '-call_return/'.$group_members[$forwarding_number].'/${caller_id_number}' );
                            $defaultContextConfiguration->endElement(); // action


                            //Forward to another extension
                            $defaultContextConfiguration->startElement('action');
                            $defaultContextConfiguration->writeAttribute('application', 'bridge');
                            $defaultContextConfiguration->writeAttribute('data', '{originate_timeout=' . $group_members[$forwarding_timeout]. '}sofia/external/'.$group_members[$forwarding_number].'@'.$destinationDomain.';fs_path=sip:$${vtpbx_proxy}:'.VTPBX_PROXY_SIP_PORT);
                            $defaultContextConfiguration->endElement(); // action

                        }break;
                        case "EXT_NUMBER":{

                            $defaultContextConfiguration->startElement('action');
                            $defaultContextConfiguration->writeAttribute('application', 'bridge');

                            // MODIFICATION
                            // OLD:   (Caller ID changed)
                            //$defaultContextConfiguration->writeAttribute('data', '{leg_timeout=' . $group_members[$forwarding_timeout]. ', sip_from_uri=sip:'.$callerid.'@'.$destinationDomain.', origination_caller_id_number='.$callerid.',ignore_early_media=true}sofia/gateway/gw'.$external_gateway_id.'/' . $external_gateway_prefix . $group_members[$forwarding_number]);

                            // NEW:  (caller ID not changed)
                            $defaultContextConfiguration->writeAttribute('data', '{leg_timeout=' . $group_members[$forwarding_timeout]. ', sip_from_uri=sip:${caller_id_number}@'.$destinationDomain.', ignore_early_media=true}sofia/gateway/gw'.$external_gateway_id.'/' . $external_gateway_prefix . $group_members[$forwarding_number]);

                            $defaultContextConfiguration->endElement(); // action

                        }break;

                    }

                }

            }

        }break;

        default:{

            error_log("ERROR in the group: ring strategy not recognized");

        }break;

    }


    // VM greeting:
    //
    if(isset($vmDetails["vm_greeting"])  &&   intval($vmDetails["vm_greeting"]) >0 ){
        $vm_file_id = intval($vmDetails["vm_greeting"]);
        $greetingFileName = PBX_IVR_FILES_BASE. getIVRFileNameByID($vm_file_id,$mysqli);

        $defaultContextConfiguration->startElement( 'action' );
        $defaultContextConfiguration->writeAttribute( 'application', 'export' );
        $defaultContextConfiguration->writeAttribute( 'data', 'skip_greeting=true' );
        $defaultContextConfiguration->endElement(); // action

        $defaultContextConfiguration->startElement( 'action' );
        $defaultContextConfiguration->writeAttribute( 'application', 'playback' );
        $defaultContextConfiguration->writeAttribute( 'data', $greetingFileName );
        $defaultContextConfiguration->endElement(); // action

    }



    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'export' );
    $defaultContextConfiguration->writeAttribute( 'data', 'vtpbx_base_call_uuid=${uuid}' );
    $defaultContextConfiguration->endElement(); // action


    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'bridge' );
    $defaultContextConfiguration->writeAttribute( 'data', 'loopback/app=voicemail:default '.$destinationDomain.' ' . $group_failover_extension );
    $defaultContextConfiguration->endElement(); // action








    $defaultContextConfiguration->endElement(); // condition

    $defaultContextConfiguration->endElement();  //extension



    $defaultContextConfiguration->endElement();  // context

    $defaultContextConfiguration->endElement(); // section
    $defaultContextConfiguration->endElement(); // document



    //Echo the whole XML document
    echo $defaultContextConfiguration->outputMemory();


}








// ====================================================================================================================

function serveConfigurationNotFound(){

    /*

    <?xml version="1.0" encoding="UTF-8" standalone="no"?>
    <document type="freeswitch/xml">
        <section name="result">
            <result status="not found"/>
        </section>
    </document>



    */

    $not_found = new XMLWriter();
    $not_found->openMemory();
    $not_found->setIndent( TRUE );
    $not_found->setIndentString( '  ' );
    $not_found->startDocument( '1.0', 'UTF-8', 'no' );
//set the freeswitch document type
    $not_found->startElement( 'document' );
    $not_found->writeAttribute( 'type', 'freeswitch/xml' );
    $not_found->startElement( 'section' );
    $not_found->writeAttribute( 'name', 'default' );

    $not_found->startElement( 'result' );
    $not_found->writeAttribute( 'status', 'not found' );
    $not_found->endElement();
    $not_found->endElement();




    $not_found->endElement();
    echo $not_found->outputMemory();



}







function serveDefaultContextConfiguration(){

    //OLD:
    $defaultContextConfiguration = new XMLWriter();
    $defaultContextConfiguration->openMemory();

    //NEW:
    //$defaultContextConfiguration = xmlwriter_open_memory();


    $defaultContextConfiguration->setIndent( TRUE );
    $defaultContextConfiguration->setIndentString( '  ' );
    $defaultContextConfiguration->startDocument( '1.0', 'UTF-8', 'no' );
    //set the freeswitch document type
    $defaultContextConfiguration->startElement( 'document' );
    $defaultContextConfiguration->writeAttribute( 'type', 'freeswitch/xml' );


    $defaultContextConfiguration->startElement( 'context' );
    $defaultContextConfiguration->writeAttribute( 'name', 'default' );

//  -- unloop

    $defaultContextConfiguration->startElement( 'extension' );
    $defaultContextConfiguration->writeAttribute( 'name', 'unloop' );

    $defaultContextConfiguration->startElement( 'condition' );
    $defaultContextConfiguration->writeAttribute( 'field', '${unroll_loops}' );
    $defaultContextConfiguration->writeAttribute( 'expression', '^true$' );
    $defaultContextConfiguration->endElement(); // condition

    $defaultContextConfiguration->startElement( 'condition' );
    $defaultContextConfiguration->writeAttribute( 'field', '${sip_looped_call}' );
    $defaultContextConfiguration->writeAttribute( 'expression', '^true$' );
    $defaultContextConfiguration->endElement(); // condition

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'deflect' );
    $defaultContextConfiguration->writeAttribute( 'data', '${destination_number}' );
    $defaultContextConfiguration->endElement(); // action



    $defaultContextConfiguration->endElement();  //extension



// -- default -> specific domain/context transfer.


    $defaultContextConfiguration->startElement( 'extension' );
    $defaultContextConfiguration->writeAttribute( 'name', 'from_opensips' );

    $defaultContextConfiguration->startElement( 'condition' );
    $defaultContextConfiguration->writeAttribute( 'field', 'network_addr' );
    $defaultContextConfiguration->writeAttribute( 'expression', '^54\.184\.27\.79$' ); //TODO: this should be always the local IP of the PROXY server
    $defaultContextConfiguration->endElement(); // condition

    $defaultContextConfiguration->startElement( 'condition' );
    $defaultContextConfiguration->writeAttribute( 'field', 'destination_number' );
    $defaultContextConfiguration->writeAttribute( 'expression', '^(.+)$' );
    $defaultContextConfiguration->endElement(); // condition

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'set' );
    $defaultContextConfiguration->writeAttribute( 'data', 'domain_name=${sip_to_host}' );
    $defaultContextConfiguration->endElement(); // action

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'transfer' );
    $defaultContextConfiguration->writeAttribute( 'data', '$1 XML ${sip_to_host}' );
    $defaultContextConfiguration->endElement(); // action


    $defaultContextConfiguration->endElement();  //extension


//



    $defaultContextConfiguration->endElement();  // context


    $defaultContextConfiguration->endElement(); // document



    //Echo the whole XML document
    echo $defaultContextConfiguration->outputMemory();



}



function serveFixedContextConfiguration($source_context,$fixed_context){

    //OLD:
    $defaultContextConfiguration = new XMLWriter();
    $defaultContextConfiguration->openMemory();

    //NEW:
    //$defaultContextConfiguration = xmlwriter_open_memory();


    $defaultContextConfiguration->setIndent( TRUE );
    $defaultContextConfiguration->setIndentString( '  ' );
    $defaultContextConfiguration->startDocument( '1.0', 'UTF-8', 'no' );
    //set the freeswitch document type
    $defaultContextConfiguration->startElement( 'document' );
    $defaultContextConfiguration->writeAttribute( 'type', 'freeswitch/xml' );


    $defaultContextConfiguration->startElement( 'context' );
    $defaultContextConfiguration->writeAttribute( 'name', $source_context );

//  -- unloop

    $defaultContextConfiguration->startElement( 'extension' );
    $defaultContextConfiguration->writeAttribute( 'name', 'unloop' );

    $defaultContextConfiguration->startElement( 'condition' );
    $defaultContextConfiguration->writeAttribute( 'field', '${unroll_loops}' );
    $defaultContextConfiguration->writeAttribute( 'expression', '^true$' );
    $defaultContextConfiguration->endElement(); // condition

    $defaultContextConfiguration->startElement( 'condition' );
    $defaultContextConfiguration->writeAttribute( 'field', '${sip_looped_call}' );
    $defaultContextConfiguration->writeAttribute( 'expression', '^true$' );
    $defaultContextConfiguration->endElement(); // condition

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'deflect' );
    $defaultContextConfiguration->writeAttribute( 'data', '${destination_number}' );
    $defaultContextConfiguration->endElement(); // action



    $defaultContextConfiguration->endElement();  //extension



// -- default -> specific domain/context transfer.


    $defaultContextConfiguration->startElement( 'extension' );
    $defaultContextConfiguration->writeAttribute( 'name', 'from_opensips' );

    $defaultContextConfiguration->startElement( 'condition' );
    $defaultContextConfiguration->writeAttribute( 'field', 'network_addr' );
    $defaultContextConfiguration->writeAttribute( 'expression', '^54\.184\.27\.79$' ); //TODO: this should be always the local IP of the PROXY server
    $defaultContextConfiguration->endElement(); // condition

    $defaultContextConfiguration->startElement( 'condition' );
    $defaultContextConfiguration->writeAttribute( 'field', 'destination_number' );
    $defaultContextConfiguration->writeAttribute( 'expression', '^(.+)$' );
    $defaultContextConfiguration->endElement(); // condition

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'set' );
    $defaultContextConfiguration->writeAttribute( 'data', 'domain_name=' . $fixed_context );
    $defaultContextConfiguration->endElement(); // action

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'transfer' );
    $defaultContextConfiguration->writeAttribute( 'data', '$1 XML '. $fixed_context );
    $defaultContextConfiguration->endElement(); // action


    $defaultContextConfiguration->endElement();  //extension


//



    $defaultContextConfiguration->endElement();  // context


    $defaultContextConfiguration->endElement(); // document



    //Echo the whole XML document
    echo $defaultContextConfiguration->outputMemory();



}



function serveDefaultContextConfigurationForSpecificDomain($domainName){

    $domainName = "domain_name=" . $domainName;   // domain_name=d1.vtpbx.com
    $domainNameTransfer = "$1 XML " . $domainName;   // '$1 XML d1.vtpbx.com'



    $defaultContextConfiguration = new XMLWriter();


    $defaultContextConfiguration->openMemory();

    $defaultContextConfiguration->setIndent( TRUE );
    $defaultContextConfiguration->setIndentString( '  ' );
    $defaultContextConfiguration->startDocument( '1.0', 'UTF-8', 'no' );
    //set the freeswitch document type
    $defaultContextConfiguration->startElement( 'document' );
    $defaultContextConfiguration->writeAttribute( 'type', 'freeswitch/xml' );


    $defaultContextConfiguration->startElement( 'context' );
    $defaultContextConfiguration->writeAttribute( 'name', 'default' );

//  -- unloop

    $defaultContextConfiguration->startElement( 'extension' );
    $defaultContextConfiguration->writeAttribute( 'name', 'unloop' );

    $defaultContextConfiguration->startElement( 'condition' );
    $defaultContextConfiguration->writeAttribute( 'field', '${unroll_loops}' );
    $defaultContextConfiguration->writeAttribute( 'expression', '^true$' );
    $defaultContextConfiguration->endElement(); // condition

    $defaultContextConfiguration->startElement( 'condition' );
    $defaultContextConfiguration->writeAttribute( 'field', '${sip_looped_call}' );
    $defaultContextConfiguration->writeAttribute( 'expression', '^true$' );
    $defaultContextConfiguration->endElement(); // condition

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'deflect' );
    $defaultContextConfiguration->writeAttribute( 'data', '${destination_number}' );
    $defaultContextConfiguration->endElement(); // action



    $defaultContextConfiguration->endElement();  //extension



// -- default -> specific domain/context transfer.


    $defaultContextConfiguration->startElement( 'extension' );
    $defaultContextConfiguration->writeAttribute( 'name', 'from_opensips' );

    $defaultContextConfiguration->startElement( 'condition' );
    $defaultContextConfiguration->writeAttribute( 'field', 'network_addr' );
    $defaultContextConfiguration->writeAttribute( 'expression', '^54\.184\.27\.79$' );  //TODO:  need to put proxy IP here in more intelligent way...
    $defaultContextConfiguration->endElement(); // condition

    $defaultContextConfiguration->startElement( 'condition' );
    $defaultContextConfiguration->writeAttribute( 'field', 'destination_number' );
    $defaultContextConfiguration->writeAttribute( 'expression', '^(.+)$' );
    $defaultContextConfiguration->endElement(); // condition

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'set' );
    $defaultContextConfiguration->writeAttribute( 'data', $domainName  );
    $defaultContextConfiguration->endElement(); // action

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'transfer' );
    $defaultContextConfiguration->writeAttribute( 'data', $domainNameTransfer );
    $defaultContextConfiguration->endElement(); // action


    $defaultContextConfiguration->endElement();  //extension

//

    $defaultContextConfiguration->endElement();  // context


    $defaultContextConfiguration->endElement(); // document



    //Echo the whole XML document
    echo $defaultContextConfiguration->outputMemory();



}


function serveConfigurationVoiceConference($context,$targetDomain,$extension,$detectedDestinationDefinition,$quality = "default",$customerID = "",$didNumberDetails,$mysqli){

    // handy variables

    $destination_number = '^' . $extension . '$';
    $conferenceName = $detectedDestinationDefinition . '-' . $targetDomain . '@'. $quality;  // 300-d1.vtpbx.net@default

    $ivr_delay = IVR_DELAY;

    // pre_answer_playback  section  - Play the IVR file as soon as the call comes in to the PBX via DID number.

    $pre_answer_playback = 0;
    $pre_answer_playback_file = PBX_IVR_DEFAULT_WHISPER;

    if(isset($didNumberDetails["pre_answer_playback"])){
        $pre_answer_playback = $didNumberDetails["pre_answer_playback"]  ;
        $pre_answer_playback_file_candidate = $didNumberDetails["pre_answer_playback_file"]  ;

        if($pre_answer_playback > 0){
            // if the pre-answer-playback is being played we should find out if we play the default file or some custom one

            if($pre_answer_playback_file_candidate > 0 ){
                $fileName= getIVRFileNameByID($pre_answer_playback_file_candidate,$mysqli);
                $pre_answer_playback_file = PBX_IVR_FILES_BASE  . $fileName ;

            }


        }


    }

    error_log("pre-answer details:  [$pre_answer_playback][$pre_answer_playback_file]  ");


    // --------




    // --------

    $defaultContextConfiguration = new XMLWriter();

    $defaultContextConfiguration->openMemory();

    $defaultContextConfiguration->setIndent( TRUE );
    $defaultContextConfiguration->setIndentString( '  ' );
    $defaultContextConfiguration->startDocument( '1.0', 'UTF-8', 'no' );
    //set the freeswitch document type
    $defaultContextConfiguration->startElement( 'document' );
    $defaultContextConfiguration->writeAttribute( 'type', 'freeswitch/xml' );

    $defaultContextConfiguration->startElement( 'section' );
    $defaultContextConfiguration->writeAttribute( 'name', 'dialplan' );



    $defaultContextConfiguration->startElement( 'context' );
    $defaultContextConfiguration->writeAttribute( 'name', $context );



    $defaultContextConfiguration->startElement( 'extension' );
    $defaultContextConfiguration->writeAttribute( 'name', 'conference_room' );

    $defaultContextConfiguration->startElement( 'condition' );
    $defaultContextConfiguration->writeAttribute( 'field', 'destination_number' );
    $defaultContextConfiguration->writeAttribute( 'expression', $destination_number );






    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'set' );
    $defaultContextConfiguration->writeAttribute( 'data', 'vtpbx_destination_type=CONFERENCE' );
    $defaultContextConfiguration->endElement(); // action

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'set' );
    $defaultContextConfiguration->writeAttribute( 'data', 'vtpbx_destination_def=' . $detectedDestinationDefinition );
    $defaultContextConfiguration->endElement(); // action


    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'set' );
    $defaultContextConfiguration->writeAttribute( 'data', 'vtpbx_customer_id=' . $customerID );
    $defaultContextConfiguration->endElement(); // action

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'answer' );
    $defaultContextConfiguration->endElement(); // action



    // Playback pre-answer IVR (Whisper) only when the call comes via DID


    if($pre_answer_playback>0){

        $defaultContextConfiguration->startElement( 'action' );
        $defaultContextConfiguration->writeAttribute( 'application', 'sleep' );
        $defaultContextConfiguration->writeAttribute( 'data', $ivr_delay );
        $defaultContextConfiguration->endElement(); // action


        $defaultContextConfiguration->startElement( 'action' );
        $defaultContextConfiguration->writeAttribute( 'application', 'playback' );
        $defaultContextConfiguration->writeAttribute( 'data', $pre_answer_playback_file );
        $defaultContextConfiguration->endElement(); // action

    }






    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'conference' );
    $defaultContextConfiguration->writeAttribute( 'data', $conferenceName );
    $defaultContextConfiguration->endElement(); // action



    // <action application="set" data="void_result=${conference(${conference_name} set endconference_grace_time 300)}"/>

    $endconference_grace_time = 'void_result=${conference(${'.$conferenceName.'} set endconference_grace_time 10)}';


    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'set' );
    $defaultContextConfiguration->writeAttribute( 'data',   $endconference_grace_time );
    $defaultContextConfiguration->endElement(); // action


    $defaultContextConfiguration->endElement(); // condition

    $defaultContextConfiguration->endElement();  //extension







    $defaultContextConfiguration->endElement();  // context

    $defaultContextConfiguration->endElement(); // section
    $defaultContextConfiguration->endElement(); // document



    //Echo the whole XML document
    echo $defaultContextConfiguration->outputMemory();






}


function serveConfigurationSendToQueue($contextDomain,$extension,$queueDomain,$queueName,$customerID = "",$didNumberDetails,$mysqli){

    // handy variables

    $destination_number = '^' . $extension . '$';
    $queueFifoData = $queueDomain . '-FIFO-' . $queueName .  ' in';  // d1.vtpbx.net-FIFO-1 , d1.vtpbx.net-FIFO-2, d1.vtpbx.net-FIFO-3 .....

    $ivr_delay = IVR_DELAY;

    $queueDetails = getQueueDetailsByID($queueName,$mysqli);
    $queueParams = json_decode($queueDetails["params"], true);

    $queue_timeout = "60";
    $queue_exit_button = "0";
    $queue_failover_extension = "101";


    if(isset($queueParams["queue_exit_button"])){
        $queue_exit_button = intval($queueParams["queue_exit_button"]);

    }



    if(isset($queueParams["queue_failover_extension"])){
        $queue_failover_extension = intval($queueParams["queue_failover_extension"]);
    }



    if(isset($queueParams["queue_failover_extension"])){
        $queue_failover_extension = intval($queueParams["queue_failover_extension"]);
    }



    if (isset($queueParams["queue_timeout"])) {
        $queue_timeout = intval($queueParams["queue_timeout"]);
    }



    // Queue IVR files


    $queue_welcome_ivr = "0";
    $queue_music_ivr = "0";
    $queue_announce_ivr = "0";



    if(isset($queueParams["queue_welcome_ivr"])){
        $queue_welcome_ivr = intval($queueParams["queue_welcome_ivr"]);

    }

    if(isset($queueParams["queue_music_ivr"])){
        $queue_music_ivr = intval($queueParams["queue_music_ivr"]);

    }

    if(isset($queueParams["queue_announce_ivr"])){
        $queue_announce_ivr = intval($queueParams["queue_announce_ivr"]);

    }







    // VM settings for extension used for failover (after queue_exit_button)
    $vmDetails = array();

    $queueDomainID = getDomainIDbyName($queueDomain,$mysqli);
    $failoverVMuserDetails = getUserDetailsByUsernameDomain($queue_failover_extension,$queueDomainID,$mysqli);


    $userID = $failoverVMuserDetails["id"];

    if( $userID > 0 ){

        $vmDetails  =  getUserVMdetails($queueDomain,$queue_failover_extension,$mysqli);




    }


    // MOH
    $moh = 0;
    $moh_candidate =  getMOHbyDomainName($queueDomain,$mysqli);
    if($moh_candidate>0 ){
        error_log("MOH:  override MOH to [".$moh_candidate."]");
        $moh = $moh_candidate;
    }




    // pre_answer_playback  section  - Play the IVR file as soon as the call comes in to the PBX via DID number.
    $pre_answer_playback = $didNumberDetails["pre_answer_playback"];
    $pre_answer_playback_file = $didNumberDetails["pre_answer_playback_file"];



    $pre_answer_playback = 0;
    $pre_answer_playback_file = PBX_IVR_DEFAULT_WHISPER;


    if(isset($didNumberDetails["pre_answer_playback"])){
        $pre_answer_playback = $didNumberDetails["pre_answer_playback"]  ;
        $pre_answer_playback_file_candidate = $didNumberDetails["pre_answer_playback_file"]  ;

        if($pre_answer_playback > 0){
            // if the pre-answer-playback is being played we should find out if we play the default file or some custom one

            if($pre_answer_playback_file_candidate > 0 ){
                $fileName= getIVRFileNameByID($pre_answer_playback_file_candidate,$mysqli);
                $pre_answer_playback_file = PBX_IVR_FILES_BASE  . $fileName ;

            }


        }


    }


    error_log("pre-answer details:  [$pre_answer_playback][$pre_answer_playback_file]  ");


    // --------




    // --------

    $defaultContextConfiguration = new XMLWriter();

    $defaultContextConfiguration->openMemory();

    $defaultContextConfiguration->setIndent( TRUE );
    $defaultContextConfiguration->setIndentString( '  ' );
    $defaultContextConfiguration->startDocument( '1.0', 'UTF-8', 'no' );
    //set the freeswitch document type
    $defaultContextConfiguration->startElement( 'document' );
    $defaultContextConfiguration->writeAttribute( 'type', 'freeswitch/xml' );

    $defaultContextConfiguration->startElement( 'section' );
    $defaultContextConfiguration->writeAttribute( 'name', 'dialplan' );



    $defaultContextConfiguration->startElement( 'context' );
    $defaultContextConfiguration->writeAttribute( 'name', $contextDomain );



    $defaultContextConfiguration->startElement( 'extension' );
    $defaultContextConfiguration->writeAttribute( 'name', 'send caller to FIFO' );

    $defaultContextConfiguration->startElement( 'condition' );
    $defaultContextConfiguration->writeAttribute( 'field', 'destination_number' );
    $defaultContextConfiguration->writeAttribute( 'expression', $destination_number );


    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'set' );
    $defaultContextConfiguration->writeAttribute( 'data', 'vtpbx_destination_type=QUEUE' );
    $defaultContextConfiguration->endElement(); // action

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'set' );
    $defaultContextConfiguration->writeAttribute( 'data', 'vtpbx_destination_def=' . $queueName );
    $defaultContextConfiguration->endElement(); // action

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'set' );
    $defaultContextConfiguration->writeAttribute( 'data', 'vtpbx_customer_id=' . $customerID );
    $defaultContextConfiguration->endElement(); // action



    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'answer' );
    $defaultContextConfiguration->endElement(); // action


    // Playback pre-answer IVR (Whisper) only when the call comes via DID


    if($pre_answer_playback>0){



        $defaultContextConfiguration->startElement( 'action' );
        $defaultContextConfiguration->writeAttribute( 'application', 'sleep' );
        $defaultContextConfiguration->writeAttribute( 'data', $ivr_delay );
        $defaultContextConfiguration->endElement(); // action


        $defaultContextConfiguration->startElement( 'action' );
        $defaultContextConfiguration->writeAttribute( 'application', 'playback' );
        $defaultContextConfiguration->writeAttribute( 'data', $pre_answer_playback_file );
        $defaultContextConfiguration->endElement(); // action

    }



    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'set' );
    $defaultContextConfiguration->writeAttribute( 'data', 'fifo_orbit_exten=_continue_:' . $queue_timeout );
    $defaultContextConfiguration->endElement(); // action


    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'set' );
    $defaultContextConfiguration->writeAttribute( 'data', 'fifo_orbit_dialplan=XML'  );
    $defaultContextConfiguration->endElement(); // action

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'set' );
    $defaultContextConfiguration->writeAttribute( 'data', 'fifo_orbit_context=' . $queueDomain  );
    $defaultContextConfiguration->endElement(); // action

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'set' );
    $defaultContextConfiguration->writeAttribute( 'data', 'fifo_caller_exit_key=' . $queue_exit_button );
    $defaultContextConfiguration->endElement(); // action

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'set' );
    $defaultContextConfiguration->writeAttribute( 'data', 'fifo_caller_exit_to_orbit=true');
    $defaultContextConfiguration->endElement(); // action



    // Custom IVR files in the queue:
    // 1. fifo_music

    if($queue_music_ivr > 0 ) {

        $queue_music_ivr_file  = PBX_IVR_FILES_BASE . getIVRFileNameByID($queue_music_ivr,$mysqli);


        $defaultContextConfiguration->startElement( 'action' );
        $defaultContextConfiguration->writeAttribute( 'application', 'set' );
        $defaultContextConfiguration->writeAttribute( 'data', 'fifo_music=' . strval($queue_music_ivr_file)     );
        $defaultContextConfiguration->endElement(); // action


    }else{



        // MOH
        // <action application="set" data="hold_music=/sounds/holdmusic.wav" />
        // <action application="bridge_export" data="hold_music=$${sounds_dir}/music/company-a.mp3"/>
        if($moh > 0){
            $mohFileName = PBX_IVR_FILES_BASE. getIVRFileNameByID($moh,$mysqli);


            $defaultContextConfiguration->startElement( 'action' );
            $defaultContextConfiguration->writeAttribute( 'application', 'set' );
            $defaultContextConfiguration->writeAttribute( 'data', 'hold_music=' . $mohFileName );
            $defaultContextConfiguration->endElement(); // action

            $defaultContextConfiguration->startElement( 'action' );
            $defaultContextConfiguration->writeAttribute( 'application', 'bridge_export' );
            $defaultContextConfiguration->writeAttribute( 'data', 'hold_music=' . $mohFileName );
            $defaultContextConfiguration->endElement(); // action

        }else{

            // DEFAULT:  FS default music on hold

            $defaultContextConfiguration->startElement( 'action' );
            $defaultContextConfiguration->writeAttribute( 'application', 'set' );
            $defaultContextConfiguration->writeAttribute( 'data', 'fifo_music=$${hold_music}' );
            $defaultContextConfiguration->endElement(); // action

        }








    }




    // 2. welcome playback

    if($queue_welcome_ivr > 0 ){
        $queue_welcome_ivr_file  = PBX_IVR_FILES_BASE . getIVRFileNameByID($queue_welcome_ivr,$mysqli);


        $defaultContextConfiguration->startElement( 'action' );
        $defaultContextConfiguration->writeAttribute( 'application', 'playback' );
        $defaultContextConfiguration->writeAttribute( 'data', strval($queue_welcome_ivr_file) );
        $defaultContextConfiguration->endElement(); // action




    }else{

        // DEFAULT:  "Please hold while I connect your call"

        $defaultContextConfiguration->startElement( 'action' );
        $defaultContextConfiguration->writeAttribute( 'application', 'playback' );
        $defaultContextConfiguration->writeAttribute( 'data', 'ivr/ivr-hold_connect_call.wav' );
        $defaultContextConfiguration->endElement(); // action



    }






    // 3. inject announce file (before connecting to agent)

    if($queue_announce_ivr> 0 ){
        $queue_announce_ivr_file  = PBX_IVR_FILES_BASE . getIVRFileNameByID($queue_announce_ivr,$mysqli);

        $queueFifoData = $queueFifoData . " " . strval($queue_announce_ivr_file);
    }


    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'fifo' );
    $defaultContextConfiguration->writeAttribute( 'data', $queueFifoData );
    $defaultContextConfiguration->endElement(); // action




    // VM greeting:
    //
    if(isset($vmDetails["vm_greeting"])  &&   intval($vmDetails["vm_greeting"]) >0 ){
        $vm_file_id = intval($vmDetails["vm_greeting"]);
        $greetingFileName = PBX_IVR_FILES_BASE. getIVRFileNameByID($vm_file_id,$mysqli);


        $defaultContextConfiguration->startElement( 'action' );
        $defaultContextConfiguration->writeAttribute( 'application', 'export' );
        $defaultContextConfiguration->writeAttribute( 'data', 'skip_greeting=true' );
        $defaultContextConfiguration->endElement(); // action


        $defaultContextConfiguration->startElement( 'action' );
        $defaultContextConfiguration->writeAttribute( 'application', 'playback' );
        $defaultContextConfiguration->writeAttribute( 'data', $greetingFileName );
        $defaultContextConfiguration->endElement(); // action


    }



    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'export' );
    $defaultContextConfiguration->writeAttribute( 'data', 'vtpbx_base_call_uuid=${uuid}' );
    $defaultContextConfiguration->endElement(); // action


    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'bridge' );
    $defaultContextConfiguration->writeAttribute( 'data', 'loopback/app=voicemail:default '.$queueDomain.' ' . $queue_failover_extension );
    $defaultContextConfiguration->endElement(); // action




    $defaultContextConfiguration->endElement(); // condition

    $defaultContextConfiguration->endElement();  //extension




    $defaultContextConfiguration->endElement();  // context

    $defaultContextConfiguration->endElement(); // section
    $defaultContextConfiguration->endElement(); // document



    //Echo the whole XML document
    echo $defaultContextConfiguration->outputMemory();


}




function serveConfigurationSendToIVR($contextDomain,$extension,$ivrDomain,$ivrID,$customerID = "",$recordingDestinationDefinition,$recordingDestinationShort,$didNumberDetails,$mysqli){

    // handy variables

    $destination_number = '^' . $extension . '$';
    $ivrFSfriendlyName = $ivrDomain . '-IVR-' . $ivrID ;  // d1.vtpbx.net-IVR-1 , d1.vtpbx.net-IVR-2, d1.vtpbx.net-IVR-3 .....


    $ivr_delay = IVR_DELAY;

    $pre_answer_playback = 0;
    $pre_answer_playback_file = PBX_IVR_DEFAULT_WHISPER;


    if(isset($didNumberDetails["pre_answer_playback"])){
        $pre_answer_playback = $didNumberDetails["pre_answer_playback"]  ;
        $pre_answer_playback_file_candidate = $didNumberDetails["pre_answer_playback_file"]  ;

        if($pre_answer_playback > 0){
            // if the pre-answer-playback is being played we should find out if we play the default file or some custom one

            if($pre_answer_playback_file_candidate > 0 ){
                $fileName= getIVRFileNameByID($pre_answer_playback_file_candidate,$mysqli);
                $pre_answer_playback_file = PBX_IVR_FILES_BASE  . $fileName ;

            }


        }


    }


    error_log("pre-answer details:  [$pre_answer_playback][$pre_answer_playback_file]  ");


    // --------






    // --------

    $defaultContextConfiguration = new XMLWriter();

    $defaultContextConfiguration->openMemory();

    $defaultContextConfiguration->setIndent( TRUE );
    $defaultContextConfiguration->setIndentString( '  ' );
    $defaultContextConfiguration->startDocument( '1.0', 'UTF-8', 'no' );
    //set the freeswitch document type
    $defaultContextConfiguration->startElement( 'document' );
    $defaultContextConfiguration->writeAttribute( 'type', 'freeswitch/xml' );

    $defaultContextConfiguration->startElement( 'section' );
    $defaultContextConfiguration->writeAttribute( 'name', 'dialplan' );



    $defaultContextConfiguration->startElement( 'context' );
    $defaultContextConfiguration->writeAttribute( 'name', $contextDomain );



    $defaultContextConfiguration->startElement( 'extension' );
    $defaultContextConfiguration->writeAttribute( 'name', 'send caller to IVR' );

    $defaultContextConfiguration->startElement( 'condition' );
    $defaultContextConfiguration->writeAttribute( 'field', 'destination_number' );
    $defaultContextConfiguration->writeAttribute( 'expression', $destination_number );


    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'set' );
    $defaultContextConfiguration->writeAttribute( 'data', 'vtpbx_destination_def=' . $ivrID );
    $defaultContextConfiguration->endElement(); // action

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'set' );
    $defaultContextConfiguration->writeAttribute( 'data', 'vtpbx_customer_id=' . $customerID );
    $defaultContextConfiguration->endElement(); // action



   // $defaultContextConfiguration->startElement( 'action' );
    //$defaultContextConfiguration->writeAttribute( 'application', 'bind_meta_app' );
    //$defaultContextConfiguration->writeAttribute( 'data', $recordingDestinationDefinition );
    //$defaultContextConfiguration->endElement(); // action

    $defaultContextConfiguration->startElement('action');
    $defaultContextConfiguration->writeAttribute('application', 'set');
    $defaultContextConfiguration->writeAttribute('data', 'RECORD_STEREO=true');
    $defaultContextConfiguration->endElement(); // action



    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'answer' );
    $defaultContextConfiguration->endElement(); // action

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'record_session' );
    $defaultContextConfiguration->writeAttribute( 'data', $recordingDestinationShort );
    $defaultContextConfiguration->endElement(); // action


    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'sleep' );
    $defaultContextConfiguration->writeAttribute( 'data', $ivr_delay );
    $defaultContextConfiguration->endElement(); // action


    if($pre_answer_playback>0){





        $defaultContextConfiguration->startElement( 'action' );
        $defaultContextConfiguration->writeAttribute( 'application', 'playback' );
        $defaultContextConfiguration->writeAttribute( 'data', $pre_answer_playback_file );
        $defaultContextConfiguration->endElement(); // action

    }






    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'ivr' );
    $defaultContextConfiguration->writeAttribute( 'data', $ivrFSfriendlyName );
    $defaultContextConfiguration->endElement(); // action




    $defaultContextConfiguration->endElement(); // condition

    $defaultContextConfiguration->endElement();  //extension


    $defaultContextConfiguration->endElement();  // context

    $defaultContextConfiguration->endElement(); // section
    $defaultContextConfiguration->endElement(); // document



    //Echo the whole XML document
    echo $defaultContextConfiguration->outputMemory();


}




function serveConfigurationQueuePickup($domain,$extension,$queueName,$customerID = ""){

    // handy variables

    $destination_number = '^' . $extension . '$';
    $queueFifoData = $domain . '-FIFO-' . $queueName .  ' out wait';  // d1.vtpbx.net-FIFO-1 , d1.vtpbx.net-FIFO-2, d1.vtpbx.net-FIFO-3 .....



    // --------

    $defaultContextConfiguration = new XMLWriter();

    $defaultContextConfiguration->openMemory();

    $defaultContextConfiguration->setIndent( TRUE );
    $defaultContextConfiguration->setIndentString( '  ' );
    $defaultContextConfiguration->startDocument( '1.0', 'UTF-8', 'no' );
    //set the freeswitch document type
    $defaultContextConfiguration->startElement( 'document' );
    $defaultContextConfiguration->writeAttribute( 'type', 'freeswitch/xml' );

    $defaultContextConfiguration->startElement( 'section' );
    $defaultContextConfiguration->writeAttribute( 'name', 'dialplan' );



    $defaultContextConfiguration->startElement( 'context' );
    $defaultContextConfiguration->writeAttribute( 'name', $domain );



    $defaultContextConfiguration->startElement( 'extension' );
    $defaultContextConfiguration->writeAttribute( 'name', 'pick up caller from FIFO' );

    $defaultContextConfiguration->startElement( 'condition' );
    $defaultContextConfiguration->writeAttribute( 'field', 'destination_number' );
    $defaultContextConfiguration->writeAttribute( 'expression', $destination_number );



    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'set' );
    $defaultContextConfiguration->writeAttribute( 'data', 'vtpbx_destination_type=QUEUE_PICKUP' );
    $defaultContextConfiguration->endElement(); // action

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'set' );
    $defaultContextConfiguration->writeAttribute( 'data', 'vtpbx_destination_def=' . $queueName );
    $defaultContextConfiguration->endElement(); // action


    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'set' );
    $defaultContextConfiguration->writeAttribute( 'data', 'vtpbx_customer_id=' . $customerID );
    $defaultContextConfiguration->endElement(); // action

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'set' );
    $defaultContextConfiguration->writeAttribute( 'data', 'fifo_music=$${hold_music}' );
    $defaultContextConfiguration->endElement(); // action

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'answer' );
    $defaultContextConfiguration->endElement(); // action


    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'fifo' );
    $defaultContextConfiguration->writeAttribute( 'data', $queueFifoData );
    $defaultContextConfiguration->endElement(); // action



    $defaultContextConfiguration->endElement(); // condition

    $defaultContextConfiguration->endElement();  //extension


    $defaultContextConfiguration->endElement();  // context

    $defaultContextConfiguration->endElement(); // section
    $defaultContextConfiguration->endElement(); // document



    //Echo the whole XML document
    echo $defaultContextConfiguration->outputMemory();


}



function serveConfigurationVoicemail($domain,$extension, $caller,$customerID = ""){

    // handy variables

    $destination_number = '^\\' . $extension . '$';
    $voicemailData =   'check default ' . $domain . ' ' . $caller;



    // --------

    $defaultContextConfiguration = new XMLWriter();

    $defaultContextConfiguration->openMemory();

    $defaultContextConfiguration->setIndent( TRUE );
    $defaultContextConfiguration->setIndentString( '  ' );
    $defaultContextConfiguration->startDocument( '1.0', 'UTF-8', 'no' );
    //set the freeswitch document type
    $defaultContextConfiguration->startElement( 'document' );
    $defaultContextConfiguration->writeAttribute( 'type', 'freeswitch/xml' );

    $defaultContextConfiguration->startElement( 'section' );
    $defaultContextConfiguration->writeAttribute( 'name', 'dialplan' );



    $defaultContextConfiguration->startElement( 'context' );
    $defaultContextConfiguration->writeAttribute( 'name', $domain );



    $defaultContextConfiguration->startElement( 'extension' );
    $defaultContextConfiguration->writeAttribute( 'name', 'Check voicemail' );

    $defaultContextConfiguration->startElement( 'condition' );
    $defaultContextConfiguration->writeAttribute( 'field', 'destination_number' );
    $defaultContextConfiguration->writeAttribute( 'expression', $destination_number );



    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'set' );
    $defaultContextConfiguration->writeAttribute( 'data', 'vtpbx_destination_type=VOICEMAIL' );
    $defaultContextConfiguration->endElement(); // action

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'set' );
    $defaultContextConfiguration->writeAttribute( 'data', 'vtpbx_destination_def=' . $caller );
    $defaultContextConfiguration->endElement(); // action


    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'set' );
    $defaultContextConfiguration->writeAttribute( 'data', 'vtpbx_customer_id=' . $customerID );
    $defaultContextConfiguration->endElement(); // action

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'answer' );
    $defaultContextConfiguration->endElement(); // action


    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'sleep' );
    $defaultContextConfiguration->writeAttribute( 'data', '1000' );
    $defaultContextConfiguration->endElement(); // action




    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'voicemail' );
    $defaultContextConfiguration->writeAttribute( 'data', $voicemailData );
    $defaultContextConfiguration->endElement(); // action



    $defaultContextConfiguration->endElement(); // condition

    $defaultContextConfiguration->endElement();  //extension


    $defaultContextConfiguration->endElement();  // context

    $defaultContextConfiguration->endElement(); // section
    $defaultContextConfiguration->endElement(); // document



    //Echo the whole XML document
    echo $defaultContextConfiguration->outputMemory();


}



function serveConfigurationEavesdrop($domain,$extension, $destToListen, $customerID){

    // handy variables

    $destination_number = '^\\' . $extension . '$';
    $eavesdropData =   '${hash(select/' . $domain . '-spymap/' . $destToListen.')}';



    // --------

    $defaultContextConfiguration = new XMLWriter();

    $defaultContextConfiguration->openMemory();

    $defaultContextConfiguration->setIndent( TRUE );
    $defaultContextConfiguration->setIndentString( '  ' );
    $defaultContextConfiguration->startDocument( '1.0', 'UTF-8', 'no' );
    //set the freeswitch document type
    $defaultContextConfiguration->startElement( 'document' );
    $defaultContextConfiguration->writeAttribute( 'type', 'freeswitch/xml' );

    $defaultContextConfiguration->startElement( 'section' );
    $defaultContextConfiguration->writeAttribute( 'name', 'dialplan' );



    $defaultContextConfiguration->startElement( 'context' );
    $defaultContextConfiguration->writeAttribute( 'name', $domain );



    $defaultContextConfiguration->startElement( 'extension' );
    $defaultContextConfiguration->writeAttribute( 'name', 'Eavesdrop' );

    $defaultContextConfiguration->startElement( 'condition' );
    $defaultContextConfiguration->writeAttribute( 'field', 'destination_number' );
    $defaultContextConfiguration->writeAttribute( 'expression', $destination_number );



    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'set' );
    $defaultContextConfiguration->writeAttribute( 'data', 'vtpbx_destination_type=EAVESDROP' );
    $defaultContextConfiguration->endElement(); // action

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'set' );
    $defaultContextConfiguration->writeAttribute( 'data', 'vtpbx_destination_def=' . $destToListen);
    $defaultContextConfiguration->endElement(); // action


    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'set' );
    $defaultContextConfiguration->writeAttribute( 'data', 'vtpbx_customer_id=' . $customerID );
    $defaultContextConfiguration->endElement(); // action


    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'answer' );
    $defaultContextConfiguration->endElement(); // action


    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'eavesdrop' );
    $defaultContextConfiguration->writeAttribute( 'data', $eavesdropData );
    $defaultContextConfiguration->endElement(); // action



    $defaultContextConfiguration->endElement(); // condition

    $defaultContextConfiguration->endElement();  //extension


    $defaultContextConfiguration->endElement();  // context

    $defaultContextConfiguration->endElement(); // section
    $defaultContextConfiguration->endElement(); // document



    //Echo the whole XML document
    echo $defaultContextConfiguration->outputMemory();


}


function serveConfigurationIntercept($domain,$extension, $destToListen, $customerID){

    // handy variables

    $destination_number = '^\\' . $extension . '$';
    $eavesdropData =   '${hash(select/' . $domain . '-spymap/' . $destToListen.')}';



    // --------

    $defaultContextConfiguration = new XMLWriter();

    $defaultContextConfiguration->openMemory();

    $defaultContextConfiguration->setIndent( TRUE );
    $defaultContextConfiguration->setIndentString( '  ' );
    $defaultContextConfiguration->startDocument( '1.0', 'UTF-8', 'no' );
    //set the freeswitch document type
    $defaultContextConfiguration->startElement( 'document' );
    $defaultContextConfiguration->writeAttribute( 'type', 'freeswitch/xml' );

    $defaultContextConfiguration->startElement( 'section' );
    $defaultContextConfiguration->writeAttribute( 'name', 'dialplan' );



    $defaultContextConfiguration->startElement( 'context' );
    $defaultContextConfiguration->writeAttribute( 'name', $domain );



    $defaultContextConfiguration->startElement( 'extension' );
    $defaultContextConfiguration->writeAttribute( 'name', 'Eavesdrop' );

    $defaultContextConfiguration->startElement( 'condition' );
    $defaultContextConfiguration->writeAttribute( 'field', 'destination_number' );
    $defaultContextConfiguration->writeAttribute( 'expression', $destination_number );



    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'set' );
    $defaultContextConfiguration->writeAttribute( 'data', 'vtpbx_destination_type=INTERCEPT_EXT' );
    $defaultContextConfiguration->endElement(); // action

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'set' );
    $defaultContextConfiguration->writeAttribute( 'data', 'vtpbx_destination_def=' . $destToListen);
    $defaultContextConfiguration->endElement(); // action


    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'set' );
    $defaultContextConfiguration->writeAttribute( 'data', 'vtpbx_customer_id=' . $customerID );
    $defaultContextConfiguration->endElement(); // action


    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'answer' );
    $defaultContextConfiguration->endElement(); // action


    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'intercept' );
    $defaultContextConfiguration->writeAttribute( 'data', $eavesdropData );
    $defaultContextConfiguration->endElement(); // action



    $defaultContextConfiguration->endElement(); // condition

    $defaultContextConfiguration->endElement();  //extension


    $defaultContextConfiguration->endElement();  // context

    $defaultContextConfiguration->endElement(); // section
    $defaultContextConfiguration->endElement(); // document



    //Echo the whole XML document
    echo $defaultContextConfiguration->outputMemory();


}


function serveConfigurationValetPark($domain,$extension, $customerID,$mysqli){

    // handy variables

    $destination_number = '^\\' . $extension . '$';
    $actionData =   'lot-' . $domain . ' auto in 5901 5999';



    // MOH
    $moh = 0;
    $moh_candidate =  getMOHbyDomainName($domain,$mysqli);
    if($moh_candidate>0 ){
        error_log("MOH:  override MOH to [".$moh_candidate."]");
        $moh = $moh_candidate;
    }




    // --------

    $defaultContextConfiguration = new XMLWriter();

    $defaultContextConfiguration->openMemory();

    $defaultContextConfiguration->setIndent( TRUE );
    $defaultContextConfiguration->setIndentString( '  ' );
    $defaultContextConfiguration->startDocument( '1.0', 'UTF-8', 'no' );
    //set the freeswitch document type
    $defaultContextConfiguration->startElement( 'document' );
    $defaultContextConfiguration->writeAttribute( 'type', 'freeswitch/xml' );

    $defaultContextConfiguration->startElement( 'section' );
    $defaultContextConfiguration->writeAttribute( 'name', 'dialplan' );



    $defaultContextConfiguration->startElement( 'context' );
    $defaultContextConfiguration->writeAttribute( 'name', $domain );



    $defaultContextConfiguration->startElement( 'extension' );
    $defaultContextConfiguration->writeAttribute( 'name', 'Valet autoselect parking stall' );

    $defaultContextConfiguration->startElement( 'condition' );
    $defaultContextConfiguration->writeAttribute( 'field', 'destination_number' );
    $defaultContextConfiguration->writeAttribute( 'expression', $destination_number );



    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'set' );
    $defaultContextConfiguration->writeAttribute( 'data', 'vtpbx_destination_type=VALET_PARK' );
    $defaultContextConfiguration->endElement(); // action

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'set' );
    $defaultContextConfiguration->writeAttribute( 'data', 'vtpbx_destination_def=empty');
    $defaultContextConfiguration->endElement(); // action


    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'set' );
    $defaultContextConfiguration->writeAttribute( 'data', 'vtpbx_customer_id=' . $customerID );
    $defaultContextConfiguration->endElement(); // action


    // MOH
    // <action application="set" data="hold_music=/sounds/holdmusic.wav" />
    // <action application="bridge_export" data="hold_music=$${sounds_dir}/music/company-a.mp3"/>
    if($moh > 0){
        $mohFileName = PBX_IVR_FILES_BASE. getIVRFileNameByID($moh,$mysqli);


        $defaultContextConfiguration->startElement( 'action' );
        $defaultContextConfiguration->writeAttribute( 'application', 'set' );
        $defaultContextConfiguration->writeAttribute( 'data', 'hold_music=' . $mohFileName );
        $defaultContextConfiguration->endElement(); // action

        $defaultContextConfiguration->startElement( 'action' );
        $defaultContextConfiguration->writeAttribute( 'application', 'bridge_export' );
        $defaultContextConfiguration->writeAttribute( 'data', 'hold_music=' . $mohFileName );
        $defaultContextConfiguration->endElement(); // action

    }




    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'answer' );
    $defaultContextConfiguration->endElement(); // action


    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'valet_park' );
    $defaultContextConfiguration->writeAttribute( 'data', $actionData );
    $defaultContextConfiguration->endElement(); // action



    $defaultContextConfiguration->endElement(); // condition

    $defaultContextConfiguration->endElement();  //extension


    $defaultContextConfiguration->endElement();  // context

    $defaultContextConfiguration->endElement(); // section
    $defaultContextConfiguration->endElement(); // document



    //Echo the whole XML document
    echo $defaultContextConfiguration->outputMemory();


}


function serveConfigurationValetUnPark($domain,$extension, $valet_id,$customerID){

    // handy variables

    $destination_number = '^\\' . $extension . '\d\d$';
    $actionData =   'lot-' . $domain . ' '.$valet_id;



    // --------

    $defaultContextConfiguration = new XMLWriter();

    $defaultContextConfiguration->openMemory();

    $defaultContextConfiguration->setIndent( TRUE );
    $defaultContextConfiguration->setIndentString( '  ' );
    $defaultContextConfiguration->startDocument( '1.0', 'UTF-8', 'no' );
    //set the freeswitch document type
    $defaultContextConfiguration->startElement( 'document' );
    $defaultContextConfiguration->writeAttribute( 'type', 'freeswitch/xml' );

    $defaultContextConfiguration->startElement( 'section' );
    $defaultContextConfiguration->writeAttribute( 'name', 'dialplan' );



    $defaultContextConfiguration->startElement( 'context' );
    $defaultContextConfiguration->writeAttribute( 'name', $domain );



    $defaultContextConfiguration->startElement( 'extension' );
    $defaultContextConfiguration->writeAttribute( 'name', 'Valet autoselect parking stall' );

    $defaultContextConfiguration->startElement( 'condition' );
    $defaultContextConfiguration->writeAttribute( 'field', 'destination_number' );
    $defaultContextConfiguration->writeAttribute( 'expression', $destination_number );



    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'set' );
    $defaultContextConfiguration->writeAttribute( 'data', 'vtpbx_destination_type=VALET_UNPARK' );
    $defaultContextConfiguration->endElement(); // action

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'set' );
    $defaultContextConfiguration->writeAttribute( 'data', 'vtpbx_destination_def='.$valet_id);
    $defaultContextConfiguration->endElement(); // action


    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'set' );
    $defaultContextConfiguration->writeAttribute( 'data', 'vtpbx_customer_id=' . $customerID );
    $defaultContextConfiguration->endElement(); // action


    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'answer' );
    $defaultContextConfiguration->endElement(); // action


    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'valet_park' );
    $defaultContextConfiguration->writeAttribute( 'data', $actionData );
    $defaultContextConfiguration->endElement(); // action



    $defaultContextConfiguration->endElement(); // condition

    $defaultContextConfiguration->endElement();  //extension


    $defaultContextConfiguration->endElement();  // context

    $defaultContextConfiguration->endElement(); // section
    $defaultContextConfiguration->endElement(); // document



    //Echo the whole XML document
    echo $defaultContextConfiguration->outputMemory();


}

function serveConfigurationCallReturn($domain,$extension,$customerID = ""){

    // handy variables

    $destination_number = '^\\' . $extension . '$';
    $callReturnData =   '${hash(select/' . $domain . '-call_return/${caller_id_number})}';



    // --------

    $defaultContextConfiguration = new XMLWriter();

    $defaultContextConfiguration->openMemory();

    $defaultContextConfiguration->setIndent( TRUE );
    $defaultContextConfiguration->setIndentString( '  ' );
    $defaultContextConfiguration->startDocument( '1.0', 'UTF-8', 'no' );
    //set the freeswitch document type
    $defaultContextConfiguration->startElement( 'document' );
    $defaultContextConfiguration->writeAttribute( 'type', 'freeswitch/xml' );

    $defaultContextConfiguration->startElement( 'section' );
    $defaultContextConfiguration->writeAttribute( 'name', 'dialplan' );



    $defaultContextConfiguration->startElement( 'context' );
    $defaultContextConfiguration->writeAttribute( 'name', $domain );



    $defaultContextConfiguration->startElement( 'extension' );
    $defaultContextConfiguration->writeAttribute( 'name', 'Call_return' );

    $defaultContextConfiguration->startElement( 'condition' );
    $defaultContextConfiguration->writeAttribute( 'field', 'destination_number' );
    $defaultContextConfiguration->writeAttribute( 'expression', $destination_number );



    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'set' );
    $defaultContextConfiguration->writeAttribute( 'data', 'vtpbx_destination_type=CALL_RETURN' );
    $defaultContextConfiguration->endElement(); // action

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'set' );
    $defaultContextConfiguration->writeAttribute( 'data', 'vtpbx_destination_def=' . $extension );
    $defaultContextConfiguration->endElement(); // action


    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'set' );
    $defaultContextConfiguration->writeAttribute( 'data', 'vtpbx_customer_id=' . $customerID );
    $defaultContextConfiguration->endElement(); // action

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'transfer' );
    $defaultContextConfiguration->writeAttribute( 'data', $callReturnData );
    $defaultContextConfiguration->endElement(); // action




    $defaultContextConfiguration->endElement(); // condition

    $defaultContextConfiguration->endElement();  //extension


    $defaultContextConfiguration->endElement();  // context

    $defaultContextConfiguration->endElement(); // section
    $defaultContextConfiguration->endElement(); // document



    //Echo the whole XML document
    echo $defaultContextConfiguration->outputMemory();


}



function serveConfigurationQueueLogin($domain,$extension,$queueName,$username,$customerID = ""){

    // handy variables

    $ivr_delay = IVR_DELAY;

    $destination_number = '^\\' . $extension . '$';

    $FIFOqueueName = $domain . '-FIFO-' . $queueName;  // d1.vtpbx.net-FIFO-1 , d1.vtpbx.net-FIFO-2, d1.vtpbx.net-FIFO-3 .....



    // add <fifo_name> <originate_string> [<simo_count>] [<timeout>] [<lag>] [expires] [taking-calls]

    //$queueLoginData =   'result=${fifo_member(add '.$FIFOqueueName.' {fifo_member_wait=nowait}loopback/'.$username.'/'.$domain.'/XML )}';
    $queueLoginData =   'result=${fifo_member(add '.$FIFOqueueName.' {fifo_member_wait=nowait}user/'.$username.'@'.$domain.' 1 60 15 )}';


    // --------

    $defaultContextConfiguration = new XMLWriter();

    $defaultContextConfiguration->openMemory();

    $defaultContextConfiguration->setIndent( TRUE );
    $defaultContextConfiguration->setIndentString( '  ' );
    $defaultContextConfiguration->startDocument( '1.0', 'UTF-8', 'no' );
    //set the freeswitch document type
    $defaultContextConfiguration->startElement( 'document' );
    $defaultContextConfiguration->writeAttribute( 'type', 'freeswitch/xml' );

    $defaultContextConfiguration->startElement( 'section' );
    $defaultContextConfiguration->writeAttribute( 'name', 'dialplan' );



    $defaultContextConfiguration->startElement( 'context' );
    $defaultContextConfiguration->writeAttribute( 'name', $domain );



    $defaultContextConfiguration->startElement( 'extension' );
    $defaultContextConfiguration->writeAttribute( 'name', 'Agent login' );

    $defaultContextConfiguration->startElement( 'condition' );
    $defaultContextConfiguration->writeAttribute( 'field', 'destination_number' );
    $defaultContextConfiguration->writeAttribute( 'expression', $destination_number );


    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'answer' );
    $defaultContextConfiguration->endElement(); // action


    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'set' );
    $defaultContextConfiguration->writeAttribute( 'data', $queueLoginData );
    $defaultContextConfiguration->endElement(); // action


    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'sleep' );
    $defaultContextConfiguration->writeAttribute( 'data', $ivr_delay );
    $defaultContextConfiguration->endElement(); // action


    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'playback' );
    $defaultContextConfiguration->writeAttribute( 'data', 'ivr/ivr-you_are_now_logged_in.wav' );
    $defaultContextConfiguration->endElement(); // action

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'sleep' );
    $defaultContextConfiguration->writeAttribute( 'data', '300' );
    $defaultContextConfiguration->endElement(); // action


    $defaultContextConfiguration->endElement(); // condition

    $defaultContextConfiguration->endElement();  //extension


    $defaultContextConfiguration->endElement();  // context

    $defaultContextConfiguration->endElement(); // section
    $defaultContextConfiguration->endElement(); // document



    //Echo the whole XML document
    echo $defaultContextConfiguration->outputMemory();


}

function serveConfigurationQueueLogout($domain,$extension,$queueName,$username,$customerID = ""){

    // handy variables

    $destination_number = '^\\' . $extension . '$';

    $FIFOqueueName = $domain . '-FIFO-' . $queueName;  // d1.vtpbx.net-FIFO-1 , d1.vtpbx.net-FIFO-2, d1.vtpbx.net-FIFO-3 .....


    $ivr_delay = IVR_DELAY;

    //$queueLoginData =   'result=${fifo_member(add '.$FIFOqueueName.' {fifo_member_wait=nowait}loopback/'.$username.'/'.$domain.'/XML )}';
    $queueLoginData =   'result=${fifo_member(del '.$FIFOqueueName.' {fifo_member_wait=nowait}user/'.$username.'@'.$domain.' )}';


    // --------

    $defaultContextConfiguration = new XMLWriter();

    $defaultContextConfiguration->openMemory();

    $defaultContextConfiguration->setIndent( TRUE );
    $defaultContextConfiguration->setIndentString( '  ' );
    $defaultContextConfiguration->startDocument( '1.0', 'UTF-8', 'no' );
    //set the freeswitch document type
    $defaultContextConfiguration->startElement( 'document' );
    $defaultContextConfiguration->writeAttribute( 'type', 'freeswitch/xml' );

    $defaultContextConfiguration->startElement( 'section' );
    $defaultContextConfiguration->writeAttribute( 'name', 'dialplan' );



    $defaultContextConfiguration->startElement( 'context' );
    $defaultContextConfiguration->writeAttribute( 'name', $domain );



    $defaultContextConfiguration->startElement( 'extension' );
    $defaultContextConfiguration->writeAttribute( 'name', 'Agent logout' );

    $defaultContextConfiguration->startElement( 'condition' );
    $defaultContextConfiguration->writeAttribute( 'field', 'destination_number' );
    $defaultContextConfiguration->writeAttribute( 'expression', $destination_number );


    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'answer' );
    $defaultContextConfiguration->endElement(); // action


    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'set' );
    $defaultContextConfiguration->writeAttribute( 'data', $queueLoginData );
    $defaultContextConfiguration->endElement(); // action


    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'sleep' );
    $defaultContextConfiguration->writeAttribute( 'data', $ivr_delay );
    $defaultContextConfiguration->endElement(); // action


    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'playback' );
    $defaultContextConfiguration->writeAttribute( 'data', 'ivr/ivr-you_are_now_logged_out.wav' );
    $defaultContextConfiguration->endElement(); // action




    $defaultContextConfiguration->endElement(); // condition

    $defaultContextConfiguration->endElement();  //extension


    $defaultContextConfiguration->endElement();  // context

    $defaultContextConfiguration->endElement(); // section
    $defaultContextConfiguration->endElement(); // document



    //Echo the whole XML document
    echo $defaultContextConfiguration->outputMemory();


}


function serveConfigurationExternalNumber($domain,$number_dialed,$external_gateway_id,$external_gateway_prefix,$customerID,$callerid="anonymous",$recordingDestinationShort="",$mysqli){

    // handy variables

    $prefixDialed = substr($number_dialed,0,5);
    //$destination_number = '^' . $prefixDialed . '(\d+)$';

    $destination_number = '^(\+?)' . $prefixDialed . '(\d+)$';



    //{sip_invite_req_uri=sip:'.$number_dialed.'@'.$domain.'}






    /*          // Parse external number -> remove some unused data:
     *
     *      011 -> remove
     *      00 -> remove
     *      +  -> remove
     *
     *
     *
     *
     */

    if(substr($number_dialed,0,3) == "011"){

        $number_dialed = substr($number_dialed,3);
        error_log("[DESTINATION_NUMBER] 011 prefix detected , new destination number is [$number_dialed] ")   ;
    }elseif(substr($number_dialed,0,2) == "00"){

        $number_dialed = substr($number_dialed,2);
        error_log("[DESTINATION_NUMBER] 00 prefix detected , new destination number is [$number_dialed] ")   ;
    }elseif(substr($number_dialed,0,1) == "+"){

        $number_dialed = substr($number_dialed,1);
        error_log("[DESTINATION_NUMBER] + prefix detected , new destination number is [$number_dialed] ")   ;
    }



    // MOH
    $moh = 0;
    $moh_candidate =  getMOHbyDomainName($domain,$mysqli);
    if($moh_candidate>0 ){
        error_log("MOH:  override MOH to [".$moh_candidate."]");
        $moh = $moh_candidate;
    }






    $callData =   '{sip_from_uri=sip:'.$callerid.'@'.$domain.', origination_caller_id_number='.$callerid.'}sofia/gateway/gw'.$external_gateway_id . '/' . $external_gateway_prefix . $number_dialed;



    // --------

    $defaultContextConfiguration = new XMLWriter();

    $defaultContextConfiguration->openMemory();

    $defaultContextConfiguration->setIndent( TRUE );
    $defaultContextConfiguration->setIndentString( '  ' );
    $defaultContextConfiguration->startDocument( '1.0', 'UTF-8', 'no' );
    //set the freeswitch document type
    $defaultContextConfiguration->startElement( 'document' );
    $defaultContextConfiguration->writeAttribute( 'type', 'freeswitch/xml' );

    $defaultContextConfiguration->startElement( 'section' );
    $defaultContextConfiguration->writeAttribute( 'name', 'dialplan' );



    $defaultContextConfiguration->startElement( 'context' );
    $defaultContextConfiguration->writeAttribute( 'name', $domain );



    $defaultContextConfiguration->startElement( 'extension' );
    $defaultContextConfiguration->writeAttribute( 'name', 'External_Call' );

    $defaultContextConfiguration->startElement( 'condition' );
    $defaultContextConfiguration->writeAttribute( 'field', 'destination_number' );
    $defaultContextConfiguration->writeAttribute( 'expression', $destination_number );


    // add spymap value for evyesdrop


    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'hash' );
    $hashValue = 'insert/${domain_name}-spymap/${caller_id_number}/${uuid}';
    //

    $defaultContextConfiguration->writeAttribute( 'data', $hashValue );
    $defaultContextConfiguration->endElement(); // action



    // call recording for external calls:

    $defaultContextConfiguration->startElement('action');
    $defaultContextConfiguration->writeAttribute('application', 'set');
    $defaultContextConfiguration->writeAttribute('data', 'RECORD_STEREO=true');
    $defaultContextConfiguration->endElement(); // action


    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'record_session' );
    $defaultContextConfiguration->writeAttribute( 'data', $recordingDestinationShort );
    $defaultContextConfiguration->endElement(); // action




    // MOH
    // <action application="set" data="hold_music=/sounds/holdmusic.wav" />
    // <action application="bridge_export" data="hold_music=$${sounds_dir}/music/company-a.mp3"/>
    if($moh > 0){
        $mohFileName = PBX_IVR_FILES_BASE. getIVRFileNameByID($moh,$mysqli);


        $defaultContextConfiguration->startElement( 'action' );
        $defaultContextConfiguration->writeAttribute( 'application', 'set' );
        $defaultContextConfiguration->writeAttribute( 'data', 'hold_music=' . $mohFileName );
        $defaultContextConfiguration->endElement(); // action

        $defaultContextConfiguration->startElement( 'action' );
        $defaultContextConfiguration->writeAttribute( 'application', 'bridge_export' );
        $defaultContextConfiguration->writeAttribute( 'data', 'hold_music=' . $mohFileName );
        $defaultContextConfiguration->endElement(); // action

    }



    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'set' );
    $defaultContextConfiguration->writeAttribute( 'data', 'vtpbx_destination_type=EXTERNAL_CALL' );
    $defaultContextConfiguration->endElement(); // action

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'set' );
    $defaultContextConfiguration->writeAttribute( 'data', 'vtpbx_destination_def=' . $number_dialed );
    $defaultContextConfiguration->endElement(); // action


    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'set' );
    $defaultContextConfiguration->writeAttribute( 'data', 'vtpbx_customer_id=' . $customerID );
    $defaultContextConfiguration->endElement(); // action

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'bridge' );
    $defaultContextConfiguration->writeAttribute( 'data', $callData );
    $defaultContextConfiguration->endElement(); // action




    $defaultContextConfiguration->endElement(); // condition

    $defaultContextConfiguration->endElement();  //extension


    $defaultContextConfiguration->endElement();  // context

    $defaultContextConfiguration->endElement(); // section
    $defaultContextConfiguration->endElement(); // document



    //Echo the whole XML document
    echo $defaultContextConfiguration->outputMemory();


}









function serveWebsocketEsl($contextDomain,$destinationNumber,$targetDomain,$customerID = "",$destinationDefinition = "",$domainID = ""){

    $expression = '^(.*)$';
    if($destinationNumber !== ''){
        $expression = '^' . preg_quote($destinationNumber, '/') . '$';
    }

    $defaultContextConfiguration = new XMLWriter();
    $defaultContextConfiguration->openMemory();
    $defaultContextConfiguration->setIndent(TRUE);
    $defaultContextConfiguration->setIndentString('  ');
    $defaultContextConfiguration->startDocument('1.0', 'UTF-8', 'no');

    $defaultContextConfiguration->startElement('document');
    $defaultContextConfiguration->writeAttribute('type', 'freeswitch/xml');

    $defaultContextConfiguration->startElement('section');
    $defaultContextConfiguration->writeAttribute('name', 'dialplan');

    $defaultContextConfiguration->startElement('context');
    $defaultContextConfiguration->writeAttribute('name', $contextDomain);

    $defaultContextConfiguration->startElement('extension');
    $defaultContextConfiguration->writeAttribute('name', 'ai_websocket');

    $defaultContextConfiguration->startElement('condition');
    $defaultContextConfiguration->writeAttribute('field', 'destination_number');
    $defaultContextConfiguration->writeAttribute('expression', $expression);

    if(!empty($targetDomain)){
        $defaultContextConfiguration->startElement('action');
        $defaultContextConfiguration->writeAttribute('application', 'export');
        $defaultContextConfiguration->writeAttribute('data', 'domain_name=' . $targetDomain);
        $defaultContextConfiguration->endElement(); // action
    }

    $defaultContextConfiguration->startElement('action');
    $defaultContextConfiguration->writeAttribute('application', 'set');
    $defaultContextConfiguration->writeAttribute('data', 'vtpbx_destination_type=AI');
    $defaultContextConfiguration->endElement(); // action

    if($destinationDefinition !== ''){
        $defaultContextConfiguration->startElement('action');
        $defaultContextConfiguration->writeAttribute('application', 'set');
        $defaultContextConfiguration->writeAttribute('data', 'vtpbx_destination_def=' . $destinationDefinition);
        $defaultContextConfiguration->endElement(); // action
    }

    if($customerID !== ''){
        $defaultContextConfiguration->startElement('action');
        $defaultContextConfiguration->writeAttribute('application', 'set');
        $defaultContextConfiguration->writeAttribute('data', 'vtpbx_customer_id=' . $customerID);
        $defaultContextConfiguration->endElement(); // action
    }

    if($domainID !== ''){
        $defaultContextConfiguration->startElement('action');
        $defaultContextConfiguration->writeAttribute('application', 'set');
        $defaultContextConfiguration->writeAttribute('data', 'vtpbx_domain_id=' . $domainID);
        $defaultContextConfiguration->endElement(); // action
    }

    $defaultContextConfiguration->startElement('action');
    $defaultContextConfiguration->writeAttribute('application', 'set_audio_level');
    $defaultContextConfiguration->writeAttribute('data', 'read -1');
    $defaultContextConfiguration->endElement(); // action

    $defaultContextConfiguration->startElement('action');
    $defaultContextConfiguration->writeAttribute('application', 'set_audio_level');
    $defaultContextConfiguration->writeAttribute('data', 'write 1');
    $defaultContextConfiguration->endElement(); // action

    $defaultContextConfiguration->startElement('action');
    $defaultContextConfiguration->writeAttribute('application', 'socket');
    $defaultContextConfiguration->writeAttribute('data', '127.0.0.1:8085 async full');
    $defaultContextConfiguration->endElement(); // action

    $defaultContextConfiguration->endElement(); // condition
    $defaultContextConfiguration->endElement(); // extension
    $defaultContextConfiguration->endElement(); // context
    $defaultContextConfiguration->endElement(); // section
    $defaultContextConfiguration->endElement(); // document

    echo $defaultContextConfiguration->outputMemory();

}

function serveConfigurationExternalNumberDIDloop($domain,$number_dialed,$external_gateway_id,$external_gateway_prefix,$customerID,$callerid="anonymous",$recordingDestinationShort="",$destinationDomain,$mysqli){

    // handy variables

    $prefixDialed = substr($number_dialed,0,5);
    //$destination_number = '^' . $prefixDialed . '(\d+)$';

    $destination_number = '^(\+?)' . $prefixDialed . '(\d+)$';



    //{sip_invite_req_uri=sip:'.$number_dialed.'@'.$domain.'}






    /*          // Parse external number -> remove some unused data:
     *
     *      011 -> remove
     *      00 -> remove
     *      +  -> remove
     *
     *
     *
     *
     */

    if(substr($number_dialed,0,3) == "011"){

        $number_dialed = substr($number_dialed,3);
        error_log("[DESTINATION_NUMBER] 011 prefix detected , new destination number is [$number_dialed] ")   ;
    }elseif(substr($number_dialed,0,2) == "00"){

        $number_dialed = substr($number_dialed,2);
        error_log("[DESTINATION_NUMBER] 00 prefix detected , new destination number is [$number_dialed] ")   ;
    }elseif(substr($number_dialed,0,1) == "+"){

        $number_dialed = substr($number_dialed,1);
        error_log("[DESTINATION_NUMBER] + prefix detected , new destination number is [$number_dialed] ")   ;
    }




    // MOH
    $moh = 0;
    $moh_candidate =  getMOHbyDomainName($domain,$mysqli);
    if($moh_candidate>0 ){
        error_log("MOH:  override MOH to [".$moh_candidate."]");
        $moh = $moh_candidate;
    }



    $callData =   '{sip_from_uri=sip:'.$callerid.'@'.$domain.', origination_caller_id_number='.$callerid.'}sofia/external/' . $number_dialed . ''. '@$${vtpbx_proxy};fs_path=sip:$${vtpbx_proxy}:'.VTPBX_PROXY_SIP_PORT;



    // --------

    $defaultContextConfiguration = new XMLWriter();

    $defaultContextConfiguration->openMemory();

    $defaultContextConfiguration->setIndent( TRUE );
    $defaultContextConfiguration->setIndentString( '  ' );
    $defaultContextConfiguration->startDocument( '1.0', 'UTF-8', 'no' );
    //set the freeswitch document type
    $defaultContextConfiguration->startElement( 'document' );
    $defaultContextConfiguration->writeAttribute( 'type', 'freeswitch/xml' );

    $defaultContextConfiguration->startElement( 'section' );
    $defaultContextConfiguration->writeAttribute( 'name', 'dialplan' );



    $defaultContextConfiguration->startElement( 'context' );
    $defaultContextConfiguration->writeAttribute( 'name', $domain );



    $defaultContextConfiguration->startElement( 'extension' );
    $defaultContextConfiguration->writeAttribute( 'name', 'External_Call' );

    $defaultContextConfiguration->startElement( 'condition' );
    $defaultContextConfiguration->writeAttribute( 'field', 'destination_number' );
    $defaultContextConfiguration->writeAttribute( 'expression', $destination_number );


    // call recording for external calls:

    $defaultContextConfiguration->startElement('action');
    $defaultContextConfiguration->writeAttribute('application', 'set');
    $defaultContextConfiguration->writeAttribute('data', 'RECORD_STEREO=true');
    $defaultContextConfiguration->endElement(); // action


    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'record_session' );
    $defaultContextConfiguration->writeAttribute( 'data', $recordingDestinationShort );
    $defaultContextConfiguration->endElement(); // action

    // add spymap value for evyesdrop


    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'hash' );
    $hashValue = 'insert/${domain_name}-spymap/${caller_id_number}/${uuid}';
    $defaultContextConfiguration->writeAttribute( 'data', $hashValue );
    $defaultContextConfiguration->endElement(); // action



    // MOH
    // <action application="set" data="hold_music=/sounds/holdmusic.wav" />
    // <action application="bridge_export" data="hold_music=$${sounds_dir}/music/company-a.mp3"/>
    if($moh > 0){
        $mohFileName = PBX_IVR_FILES_BASE. getIVRFileNameByID($moh,$mysqli);


        $defaultContextConfiguration->startElement( 'action' );
        $defaultContextConfiguration->writeAttribute( 'application', 'set' );
        $defaultContextConfiguration->writeAttribute( 'data', 'hold_music=' . $mohFileName );
        $defaultContextConfiguration->endElement(); // action

        $defaultContextConfiguration->startElement( 'action' );
        $defaultContextConfiguration->writeAttribute( 'application', 'bridge_export' );
        $defaultContextConfiguration->writeAttribute( 'data', 'hold_music=' . $mohFileName );
        $defaultContextConfiguration->endElement(); // action

    }





    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'set' );
    $defaultContextConfiguration->writeAttribute( 'data', 'vtpbx_destination_type=EXTERNAL_CALL' );
    $defaultContextConfiguration->endElement(); // action

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'set' );
    $defaultContextConfiguration->writeAttribute( 'data', 'vtpbx_destination_def=' . $number_dialed );
    $defaultContextConfiguration->endElement(); // action


    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'set' );
    $defaultContextConfiguration->writeAttribute( 'data', 'vtpbx_customer_id=' . $customerID );
    $defaultContextConfiguration->endElement(); // action

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'bridge' );
    $defaultContextConfiguration->writeAttribute( 'data', $callData );
    $defaultContextConfiguration->endElement(); // action




    $defaultContextConfiguration->endElement(); // condition

    $defaultContextConfiguration->endElement();  //extension


    $defaultContextConfiguration->endElement();  // context

    $defaultContextConfiguration->endElement(); // section
    $defaultContextConfiguration->endElement(); // document



    //Echo the whole XML document
    echo $defaultContextConfiguration->outputMemory();


}





//
//  ==============================================================================================================
//              D I R E C T O R Y
//  ==============================================================================================================
//




function serveDirectoryUserDetails($domain,$userDetailsArr){

/*

<include>
  <domain name="d1.vtpbx.com">
    <variables>
      <variable name="record_stereo" value="true"/>
      <variable name="transfer_fallback_extension" value="operator"/>
      <variable name="user_context" value="d1.vtpbx.com"/>
   </variables>

    <groups>
      <group name="default">
        <users>
            <user id="101">
                <params>
                  <param name="password" value="SIP_PASSWORD"/>
                  <param name="vm-password" value="101"/>

                  <param name="dial-string" value="{presence_id=${dialed_user}@${dialed_domain},transfer_fallback_extension=${dialed_user}}${sofia_contact(${dialed_user}@${dialed_domain})}"/>


                </params>
                <variables>
                  <variable name="toll_allow" value="domestic,international,local"/>
                  <variable name="accountcode" value="101"/>
                  <variable name="effective_caller_id_name" value="Extension 1000"/>
                  <variable name="effective_caller_id_number" value="1000"/>
                  <variable name="outbound_caller_id_name" value="$${outbound_caller_name}"/>
                  <variable name="outbound_caller_id_number" value="$${outbound_caller_id}"/>
                </variables>
          </user>
        </users>
      </group>
    </groups>

  </domain>
</include>

*/






    // --------

    $defaultContextConfiguration = new XMLWriter();

    $defaultContextConfiguration->openMemory();

    $defaultContextConfiguration->setIndent( TRUE );
    $defaultContextConfiguration->setIndentString( '  ' );
    $defaultContextConfiguration->startDocument( '1.0', 'UTF-8', 'no' );
    //set the freeswitch document type
    $defaultContextConfiguration->startElement( 'document' );
    $defaultContextConfiguration->writeAttribute( 'type', 'freeswitch/xml' );

    $defaultContextConfiguration->startElement( 'section' );
    $defaultContextConfiguration->writeAttribute( 'name', 'directory' );



    $defaultContextConfiguration->startElement( 'domain' );
    $defaultContextConfiguration->writeAttribute( 'name', $domain );

        $defaultContextConfiguration->startElement( 'variables' );


            $defaultContextConfiguration->startElement( 'variable' );
            $defaultContextConfiguration->writeAttribute( 'name', 'record_stereo' );
            $defaultContextConfiguration->writeAttribute( 'value', 'true' );
            $defaultContextConfiguration->endElement(); // variable

            $defaultContextConfiguration->startElement( 'variable' );
            $defaultContextConfiguration->writeAttribute( 'name', 'transfer_fallback_extension' );
            $defaultContextConfiguration->writeAttribute( 'value', 'operator' );
            $defaultContextConfiguration->endElement(); // variable

            $defaultContextConfiguration->startElement( 'variable' );
            $defaultContextConfiguration->writeAttribute( 'name', 'user_context' );
            $defaultContextConfiguration->writeAttribute( 'value', $domain);
            $defaultContextConfiguration->endElement(); // variable

        $defaultContextConfiguration->endElement(); // variables

        // groups

        $defaultContextConfiguration->startElement( 'groups' );

            $defaultContextConfiguration->startElement( 'group' );
            $defaultContextConfiguration->writeAttribute( 'name', 'default' );
                $defaultContextConfiguration->startElement( 'users' );

                // list of users

                foreach($userDetailsArr as $userDetails){
                    // List accounts for this domain
                    /*

                    <user id="101">
                        <params>
                          <param name="password" value="SIP_PASSWORD"/>
                          <param name="vm-password" value="101"/>

                  <param name="dial-string" value="{presence_id=${dialed_user}@${dialed_domain},transfer_fallback_extension=${dialed_user}}${sofia_contact(${dialed_user}@${dialed_domain})}"/>



                        </params>
                        <variables>
                          <variable name="toll_allow" value="domestic,international,local"/>
                          <variable name="accountcode" value="101"/>
                          <variable name="effective_caller_id_name" value="Extension 1000"/>
                          <variable name="effective_caller_id_number" value="1000"/>
                          <variable name="outbound_caller_id_name" value="$${outbound_caller_name}"/>
                          <variable name="outbound_caller_id_number" value="$${outbound_caller_id}"/>
                        </variables>
                  </user>

                    */


                    $username=test_input($userDetails["username"]);
                    $sip_password=test_input($userDetails["sip_password"]);
                    $vm_password=test_input($userDetails["vm_password"]);
                    $name = test_input($userDetails["name"]);


                    // $vm_storage_dir                        <param name="vm-storage-dir" value="/home/voicemail/test.com/100"/>

                    $vm_storage_dir = PBX_VOICEMAIL_FILES_BASE . 'default/'.$domain .'/'.$username;


                   // $dial_string = "${sofia_contact(${dialed_user}@${$domain})}";
                    $dial_string = 'sofia/external/'.$username.'@'.$domain.';fs_path=sip:$${vtpbx_proxy}:'.VTPBX_PROXY_SIP_PORT;




                    $defaultContextConfiguration->startElement( 'user' );
                    $defaultContextConfiguration->writeAttribute( 'id', $username );

                        $defaultContextConfiguration->startElement( 'params' );

                            $defaultContextConfiguration->startElement( 'param' );
                            $defaultContextConfiguration->writeAttribute( 'name', 'password' );
                            $defaultContextConfiguration->writeAttribute( 'value', $sip_password );  // SIP password
                            $defaultContextConfiguration->endElement(); // param

                            $defaultContextConfiguration->startElement( 'param' );
                            $defaultContextConfiguration->writeAttribute( 'name', 'vm-password' );
                            $defaultContextConfiguration->writeAttribute( 'value', $vm_password );  // VM password
                            $defaultContextConfiguration->endElement(); // param

                            $defaultContextConfiguration->startElement( 'param' );
                            $defaultContextConfiguration->writeAttribute( 'name', 'vm-storage-dir' );
                            $defaultContextConfiguration->writeAttribute( 'value', $vm_storage_dir );  // vm-storage-dir
                            $defaultContextConfiguration->endElement(); // param

                            // Required for Voicemail notifications

                            /*


                                        <param name="vm-email-all-messages" value="true" />
                                        <param name="vm-mailto" value="user.name@mydomain.com" />
                                            <!-- or just notify -->
                                        <param name="vm-notify-mailto" value="user.name@mydomain.com" />
                                            <!-- don't need notify if you have the full voicemail -->
                                        <param name="vm-attach-file" value="true" />
                                            <!-- You need this if you want the voicemail attached -->
                                        <param name="vm-message-ext" value="wav" />
                                            <!-- Can be 'mp3' but needs mod_lame to be loaded. -->

                             */

                            $defaultContextConfiguration->startElement( 'param' );
                            $defaultContextConfiguration->writeAttribute( 'name', 'vm-notify-email-all-messages' );
                            $defaultContextConfiguration->writeAttribute( 'value', "true" );
                            $defaultContextConfiguration->endElement(); // param

                            $defaultContextConfiguration->startElement( 'param' );
                            $defaultContextConfiguration->writeAttribute( 'name', 'vm-mailto' );
                            $defaultContextConfiguration->writeAttribute( 'value', strval($username . "@" . $domain) );
                            $defaultContextConfiguration->endElement(); // param

                            $defaultContextConfiguration->startElement( 'param' );
                            $defaultContextConfiguration->writeAttribute( 'name', 'vm-mailfrom' );
                            $defaultContextConfiguration->writeAttribute( 'value', strval("voicemail@" . $domain) );
                            $defaultContextConfiguration->endElement(); // param

                            $defaultContextConfiguration->startElement( 'param' );
                            $defaultContextConfiguration->writeAttribute( 'name', 'vm-notify-mailto' );
                            $defaultContextConfiguration->writeAttribute( 'value', strval($username . "@" . $domain) );
                            $defaultContextConfiguration->endElement(); // param

                            $defaultContextConfiguration->startElement( 'param' );
                            $defaultContextConfiguration->writeAttribute( 'name', 'vm-attach-file' );
                            $defaultContextConfiguration->writeAttribute( 'value', "false" );
                            $defaultContextConfiguration->endElement(); // param

                            $defaultContextConfiguration->startElement( 'param' );
                            $defaultContextConfiguration->writeAttribute( 'name', 'vm-message-ext' );
                            $defaultContextConfiguration->writeAttribute( 'value', "wav" );
                            $defaultContextConfiguration->endElement(); // param







                            $defaultContextConfiguration->startElement( 'param' );
                            $defaultContextConfiguration->writeAttribute( 'name', 'dial-string' );
                            $defaultContextConfiguration->writeAttribute( 'value', $dial_string );  // dial string
                            $defaultContextConfiguration->endElement(); // param







                        $defaultContextConfiguration->endElement(); // params

                        $defaultContextConfiguration->startElement( 'variables' );

                            $defaultContextConfiguration->startElement( 'variable' );
                            $defaultContextConfiguration->writeAttribute( 'name', 'toll_allow' );
                            $defaultContextConfiguration->writeAttribute( 'value', 'domestic,international,local' );  // SIP password
                            $defaultContextConfiguration->endElement(); // variable

                            // <variable name="timezone" value="America/New_York"/>
                            //TODO: This can be a per-user parameter

                            $defaultContextConfiguration->startElement( 'variable' );
                            $defaultContextConfiguration->writeAttribute( 'name', 'timezone' );
                            $defaultContextConfiguration->writeAttribute( 'value', 'America/Los_Angeles' );  // America/Los_Angeles
                            $defaultContextConfiguration->endElement(); // variable



                            $defaultContextConfiguration->startElement( 'variable' );
                            $defaultContextConfiguration->writeAttribute( 'name', 'accountcode' );
                            $defaultContextConfiguration->writeAttribute( 'value', $username );
                            $defaultContextConfiguration->endElement(); // variable
                            //
                            $defaultContextConfiguration->startElement( 'variable' );
                            $defaultContextConfiguration->writeAttribute( 'name', 'effective_caller_id_name' );
                            $defaultContextConfiguration->writeAttribute( 'value', $name );
                            $defaultContextConfiguration->endElement(); // variable

                            $defaultContextConfiguration->startElement( 'variable' );
                            $defaultContextConfiguration->writeAttribute( 'name', 'effective_caller_id_number' );
                            $defaultContextConfiguration->writeAttribute( 'value', $username );
                            $defaultContextConfiguration->endElement(); // variable

                            $defaultContextConfiguration->startElement( 'variable' );
                            $defaultContextConfiguration->writeAttribute( 'name', 'outbound_caller_id_name' );
                            $defaultContextConfiguration->writeAttribute( 'value', '$${outbound_caller_name}' );
                            $defaultContextConfiguration->endElement(); // variable

                            $defaultContextConfiguration->startElement( 'variable' );
                            $defaultContextConfiguration->writeAttribute( 'name', 'outbound_caller_id_number' );
                            $defaultContextConfiguration->writeAttribute( 'value', '$${outbound_caller_id}' );
                            $defaultContextConfiguration->endElement(); // variable

                        $defaultContextConfiguration->endElement(); // variables

                    $defaultContextConfiguration->endElement(); // user

                }

                // end : list of users

                $defaultContextConfiguration->endElement(); // users
            $defaultContextConfiguration->endElement(); // group
        $defaultContextConfiguration->endElement(); // groups






        $defaultContextConfiguration->endElement();  //domain


    $defaultContextConfiguration->endElement(); // section
    $defaultContextConfiguration->endElement(); // document



    //Echo the whole XML document
    echo $defaultContextConfiguration->outputMemory();


}



/*
 *
 *  -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-  IVR CONFIGURATION
 *
 */


function serveConfigurationOfIVRscenariosForOneClient($customerID, $extension, $ivrDomain, $ivrID, $mysqli){

    // handy variables

    $destination_number = '^' . $extension . '$';
    $ivrFSfriendlyName = $ivrDomain . '-IVR-' . $ivrID ;  // d1.vtpbx.net-IVR-1 , d1.vtpbx.net-IVR-2, d1.vtpbx.net-IVR-3 .....



    // --------

    $defaultContextConfiguration = new XMLWriter();

    $defaultContextConfiguration->openMemory();

    $defaultContextConfiguration->setIndent( TRUE );
    $defaultContextConfiguration->setIndentString( '  ' );
    $defaultContextConfiguration->startDocument( '1.0', 'UTF-8', 'no' );
    //set the freeswitch document type
    $defaultContextConfiguration->startElement( 'document' );
    $defaultContextConfiguration->writeAttribute( 'type', 'freeswitch/xml' );

    $defaultContextConfiguration->startElement( 'section' );
    $defaultContextConfiguration->writeAttribute( 'name', 'configuration' );



    $defaultContextConfiguration->startElement( 'configuration' );
    $defaultContextConfiguration->writeAttribute( 'name', 'ivr.conf' );

    $defaultContextConfiguration->startElement( 'menus' );
    // -=-=-=-=-=-=-=-=-=-=     -=-=-=-=-=-=-=-=-=-=   -=-=-=-=-=-=-=-=-=-=   -=-=-=-=-=-=-=-=-=-=   -=-=-=-=-=-=-=-=-=-=
    // LOOP over IVR menus

    $ivrMenusArr = getCustomerIVRMenusListFull($customerID,$mysqli);

    foreach($ivrMenusArr as $ivrMenuData){

        $menuID = $ivrMenuData["id"];
        $domainID = $ivrMenuData["domain"];
        $menuName = $ivrMenuData["name"];
        $menuDetailsJSON = $ivrMenuData["menu_details"];

        $domainName = getDomainNameByID($domainID,$mysqli);

        $ivrMenuFSname = $domainName . '-IVR-' . $menuID;

        $menuDetails = json_decode($menuDetailsJSON,true);   // main menu parameters
        $menuEntriesArr = $menuDetails["entries"];   //  menu entries Array


        // IVR menu attributes:

         $greet_longID =  $menuDetails["greet-long"];
        $greet_long = strval(PBX_IVR_FILES_BASE . getIVRFileNameByID($greet_longID,$mysqli) );

         $greet_shortID =  $menuDetails["greet-short"];
        $greet_short = strval(PBX_IVR_FILES_BASE . getIVRFileNameByID($greet_shortID,$mysqli) );

        $invalid_soundID =  $menuDetails["invalid-sound"];
        $invalid_sound = strval(PBX_IVR_FILES_BASE . getIVRFileNameByID($invalid_soundID,$mysqli) );

        $exit_soundID =  $menuDetails["exit-sound"];
        $exit_sound = strval(PBX_IVR_FILES_BASE . getIVRFileNameByID($exit_soundID,$mysqli) );

        $timeout =  $menuDetails["timeout"];
        $inter_digit_timeout =  $menuDetails["inter-digit-timeout"];
        $max_failures =  $menuDetails["max-failures"];
        $digit_len =  $menuDetails["digit-len"];


        /*

         <menu name="demo_ivr"
              greet-long="phrase:demo_ivr_main_menu"  // "http_cache://http://example.com/media/hello_world.wav"
              greet-short="phrase:demo_ivr_main_menu_short"
              invalid-sound="ivr/ivr-that_was_an_invalid_entry.wav"
              exit-sound="voicemail/vm-goodbye.wav"
              timeout ="10000"
              inter-digit-timeout="2000"
              max-failures="3"
              digit-len="4">

          <entry action="menu-exec-app" digits="1" param="bridge sofia/$${domain}/888@conference.freeswitch.org"/>
          <entry action="menu-exec-app" digits="2" param="transfer 9996 XML default"/>    <!-- FS echo -->
          <entry action="menu-exec-app" digits="3" param="transfer 9999 XML default"/>    <!-- MOH -->
          <entry action="menu-sub" digits="4" param="demo_ivr_submenu"/>  <!-- demo sub menu -->
          <entry action="menu-exec-app" digits="5" param="transfer 1234*256 enum"/>    <!-- Screaming monkeys -->
          <entry action="menu-exec-app" digits="/^(10[01][0-9])$/" param="transfer $1 XML default"/> <!-- dial ext & x-fer -->
          <entry action="menu-top" digits="9"/>    <!-- Repeat this menu -->

        </menu>

        */


        $defaultContextConfiguration->startElement( 'menu' );
        $defaultContextConfiguration->writeAttribute( 'name', $ivrMenuFSname );
        $defaultContextConfiguration->writeAttribute( 'greet-long', $greet_long );
        $defaultContextConfiguration->writeAttribute( 'greet-short', $greet_short );
        $defaultContextConfiguration->writeAttribute( 'invalid-sound', $invalid_sound );
        $defaultContextConfiguration->writeAttribute( 'exit-sound', $exit_sound );
        $defaultContextConfiguration->writeAttribute( 'timeout', $timeout );
        $defaultContextConfiguration->writeAttribute( 'inter-digit-timeout', $inter_digit_timeout );
        $defaultContextConfiguration->writeAttribute( 'max-failures', $max_failures );
        $defaultContextConfiguration->writeAttribute( 'digit-len', $digit_len );

        foreach($menuEntriesArr as $menuEntryData){

            if($menuEntryData["enabled"] == "on"){


                $digits = $menuEntryData["digits"];
                $action = $menuEntryData["action"];
                $action_details = $menuEntryData["action_details"];

                switch($action){
                    case "USER":{
                        //  <entry action="menu-exec-app" digits="1" param="bridge sofia/internal/1001%10.10.10.10"/>


                        /*
                            $defaultContextConfiguration->startElement( 'action' );
                            $defaultContextConfiguration->writeAttribute( 'application', 'hash' );
                            $hashValue = 'insert/${domain_name}-spymap/'.$group_members[$forwarding_number].'/${uuid}';
                            $defaultContextConfiguration->writeAttribute( 'data', $hashValue );
                            $defaultContextConfiguration->endElement(); // action


                            $defaultContextConfiguration->startElement( 'action' );
                            $defaultContextConfiguration->writeAttribute( 'application', 'hash' );
                            $defaultContextConfiguration->writeAttribute( 'data', 'insert/'. $destinationDomain . '-call_return/'.$group_members[$forwarding_number].'/${caller_id_number}' );
                            $defaultContextConfiguration->endElement(); // action



                           hash:'insert/'. $action_details . '-call_return/'.$action_details.'/${caller_id_number}'




                         */










                        $execParam1 = "execute_extension hash:'insert/". $action_details . "-call_return/".$action_details."/\${caller_id_number}',limit:'hash ivr in',set:domain_name=".$domainName.",set:ivr_destination_type=USER,set:ivr_destination_def=".$action_details.",transfer:'".$action_details." XML ".$domainName."' inline";

                        $defaultContextConfiguration->startElement( 'entry' );
                        $defaultContextConfiguration->writeAttribute( 'action', 'menu-exec-app' );
                        $defaultContextConfiguration->writeAttribute( 'digits', $digits );
                        $defaultContextConfiguration->writeAttribute( 'param', $execParam1 );
                        $defaultContextConfiguration->endElement();


                    }break;
                    case "CONFERENCE":{
                        // <entry action="menu-exec-app" digits="1" param="bridge sofia/$${domain}/888@conference.freeswitch.org"/>


                        $execParam1 = "execute_extension limit:'hash ivr in',set:domain_name=".$domainName.",set:ivr_destination_type=CONFERENCE,set:ivr_destination_def=".$action_details.",transfer:'".$action_details." XML ".$domainName."' inline";

                        $defaultContextConfiguration->startElement( 'entry' );
                        $defaultContextConfiguration->writeAttribute( 'action', 'menu-exec-app' );
                        $defaultContextConfiguration->writeAttribute( 'digits', $digits );
                        $defaultContextConfiguration->writeAttribute( 'param', $execParam1 );
                        $defaultContextConfiguration->endElement();




                    }break;
                    case "QUEUE":{


                        $execParam1 = "execute_extension limit:'hash ivr in',set:domain_name=".$domainName.",set:ivr_destination_type=QUEUE,set:ivr_destination_def=".$action_details.",transfer:'".$action_details." XML ".$domainName."' inline";

                        $defaultContextConfiguration->startElement( 'entry' );
                        $defaultContextConfiguration->writeAttribute( 'action', 'menu-exec-app' );
                        $defaultContextConfiguration->writeAttribute( 'digits', $digits );
                        $defaultContextConfiguration->writeAttribute( 'param', $execParam1 );
                        $defaultContextConfiguration->endElement();




                    }break;
                    case "IVR":{
                       // <entry action="menu-sub" digits="4" param="demo_ivr_submenu"/>

                        $subMenuFSfriendlyName = $domainName . '-IVR-' . $action_details;

                        $defaultContextConfiguration->startElement( 'entry' );
                        $defaultContextConfiguration->writeAttribute( 'action', 'menu-sub' );
                        $defaultContextConfiguration->writeAttribute( 'digits', $digits );
                        $defaultContextConfiguration->writeAttribute( 'param', $subMenuFSfriendlyName );
                        $defaultContextConfiguration->endElement();


                    }break;



                    case "GROUP":{


                        $execParam1 = "execute_extension limit:'hash ivr in',set:domain_name=".$domainName.",set:ivr_destination_type=GROUP,set:ivr_destination_def=".$action_details.",transfer:'".$action_details." XML ".$domainName."' inline";

                        $defaultContextConfiguration->startElement( 'entry' );
                        $defaultContextConfiguration->writeAttribute( 'action', 'menu-exec-app' );
                        $defaultContextConfiguration->writeAttribute( 'digits', $digits );
                        $defaultContextConfiguration->writeAttribute( 'param', $execParam1 );
                        $defaultContextConfiguration->endElement();



                    }break;


                    case "ACTION":{
                        // Running serveral actions within one IVR menu button pressed:
                        //   https://freeswitch.org/confluence/display/FREESWITCH/mod_dptools:+IVR+Menu#mod_dptools:IVRMenu-Howtorunseveralappswithonedigit

                        $ivrActionDetails = getActionDetailsByID($action_details, $mysqli);
                        $ivr_file_id = $ivrActionDetails["ivr_playback_file"];

                        if($ivr_file_id > 0){
                            $actionIVRFileName = PBX_IVR_FILES_BASE. getIVRFileNameByID($ivr_file_id,$mysqli);


                            // [{"did":"18163718790","phonenumber":"442080895080","uuid":"944b560e-084e-4096-bf88-2df0f6257ca5","note":"ivr_action","customer":"7","domain":"d7.callertech.net","action":5}]

                            $curl_string = 'http://127.0.0.1/fs_ivr_action.php?phonenumber=${caller_id_number}&action='.$action_details.'&customer='.$customerID.'&domain='.$domainName;

                            $execParam1 = "execute_extension limit:'hash ivr in',set:domain_name=".$domainName.",set:continue_on_fail=true,set:ivr_destination_type=ACTION,set:ivr_destination_def=".$action_details.",playback:'".$actionIVRFileName."',curl:'".$curl_string."' inline";

                            $defaultContextConfiguration->startElement( 'entry' );
                            $defaultContextConfiguration->writeAttribute( 'action', 'menu-exec-app' );
                            $defaultContextConfiguration->writeAttribute( 'digits', $digits );
                            $defaultContextConfiguration->writeAttribute( 'param', $execParam1 );
                            $defaultContextConfiguration->endElement();

                        }


                        // a copy of MENU-TOP to not let the call to drop after performing the action
                        $defaultContextConfiguration->startElement( 'entry' );
                        $defaultContextConfiguration->writeAttribute( 'action', 'menu-top' );
                        $defaultContextConfiguration->writeAttribute( 'digits', $digits );
                        $defaultContextConfiguration->endElement();
                    }break;









                    case "MENU-TOP":{
                        //<entry action="menu-top" digits="9"/>

                        $defaultContextConfiguration->startElement( 'entry' );
                        $defaultContextConfiguration->writeAttribute( 'action', 'menu-top' );
                        $defaultContextConfiguration->writeAttribute( 'digits', $digits );
                        $defaultContextConfiguration->endElement();
                    }break;

                }









            }








        }








        $defaultContextConfiguration->endElement();  // end - menu
    }













    // END of LOOP over IVR menus
    // -=-=-=-=-=-=-=-=-=-= -=-=-=-=-=-=-=-=-=-=   -=-=-=-=-=-=-=-=-=-=   -=-=-=-=-=-=-=-=-=-=   -=-=-=-=-=-=-=-=-=-=

    $defaultContextConfiguration->endElement();  // menus
    $defaultContextConfiguration->endElement();  // configuration

    $defaultContextConfiguration->endElement(); // section (documentation
    $defaultContextConfiguration->endElement(); // document



    //Echo the whole XML document
    echo $defaultContextConfiguration->outputMemory();


}





?>
