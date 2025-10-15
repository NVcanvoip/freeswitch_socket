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
            serveConfigurationLocalExtension($domain,$destination_number,$recordingDestinationDefinition,$recordingDestinationShort,$customerID);


        }break;
        case "QUEUE":{
            // Queue park call
            // $detectedDestinationDefinition  keeps the ID of the queue
            serveConfigurationSendToQueue($domain,$destination_number,$domain,$detectedDestinationDefinition,$customerID);

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
            serveConfigurationVoiceConference($domain,$domain,$destination_number,$detectedDestinationDefinition,$conferenceQuality,$customerID);


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


            //Extract external caller ID in this order:
            // $external_caller_id  -  this is the customer's DEFAULT caller ID for outbound calls.

            error_log(json_encode($caller_details));

            $external_caller_id_for_user = $caller_details["external_caller_id"];

            if(test_input($external_caller_id_for_user) != ""){
                error_log("[CALLER_ID] Extension :[$caller] from domain [$domain]  has outbound caller ID : [$external_caller_id_for_user]  default user caller ID:[$external_caller_id] . The extension's own caller ID will be used at this time as it's not empty.  ");

                // user seems to have proper caller ID, let's use it instead of default caller ID for the customer (PBX)
                $external_caller_id = $external_caller_id_for_user;
            }else{

                error_log("[CALLER_ID] Extension :[$caller] from domain [$domain]  has empty outbound caller ID  default PBX caller ID:[$external_caller_id] will be used at this time.  ");

            }






            serveConfigurationExternalNumber($domain,$destination_number,$external_gateway_id,$external_gateway_prefix,$customerID,$external_caller_id,$recordingDestinationShort);



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



            $data = array(
                "token"=> CT_API_TOKEN,
                "did" => $external_caller_id,   // DID here... TODO: (what the hell I should use here????
                "phonenumber" => $destination_number,
                "note" => "sip",
                "direction" => "outbound-api",
                "uuid" => $channel_uuid,

                "customer" => $customerID,
                "domain" => $domainName

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


    error_log(" inside serveDynamicDIDConfiguration, DID destination type: [$detectedDestination] , def [$detectedDestinationDefinition]");


    switch($detectedDestination){
        case "USER" : {
            // call routed to another user in the tenant
            //serveConfigurationLocalExtension($domainOriginal,$destination_number,$recordingDestinationDefinition,$recordingDestinationShort);
            serveConfigurationLocalExtensionViaDID($domainOriginal,$destination_number,$recordingDestinationDefinition,$recordingDestinationShort,$destinationDomain,$detectedDestinationDefinition,$customerID);

        }break;
        case "QUEUE":{
            // Queue park call
            // $detectedDestinationDefinition  keeps the ID of the queue
            serveConfigurationSendToQueue($domainOriginal,$destination_number,$destinationDomain,$detectedDestinationDefinition,$customerID);

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
            serveConfigurationVoiceConference($domainOriginal,$destinationDomain,$destination_number,$detectedDestinationDefinition,$conferenceQuality);


        }break;
        case "IVR":{
            // play the IVR

            serveConfigurationSendToIVR($domainOriginal,$destination_number,$destinationDomain,$detectedDestinationDefinition,$customerID,$recordingDestinationDefinition,$recordingDestinationShort);


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
    //TODO: test call recording, make sure channel_uuid is really unique and can work here.  Can it create directories???
    $recordingDestinationShort  = '/opt/ctpbx/recordings/' . $destinationDomain . '/'.$channel_uuid.'.wav' ;


    //$didNumberDetails = getDIDNumberDetailsForCustomer($destination_number,$customerID,$domainID,$mysqli);

    error_log("-=-=-=-=-=-=-=-Sending DID call to IVR , recording short:  [$recordingDestinationShort]");
    error_log(" inside serveDynamicDIDConfiguration, DID destination type: [$detectedDestination] , def [$detectedDestinationDefinition]");


    switch($detectedDestination){
        case "USER" : {
            // call routed to another user in the tenant
            //serveConfigurationLocalExtension($domainOriginal,$destination_number,$recordingDestinationDefinition,$recordingDestinationShort);
            serveConfigurationLocalExtensionViaDID($domainOriginal,$destination_number,$recordingDestinationDefinition,$recordingDestinationShort,$destinationDomain,$detectedDestinationDefinition,$customerID);

        }break;
        case "QUEUE":{
            // Queue park call
            // $detectedDestinationDefinition  keeps the ID of the queue
            serveConfigurationSendToQueue($domainOriginal,$destination_number,$destinationDomain,$detectedDestinationDefinition,$customerID);

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
            serveConfigurationVoiceConference($domainOriginal,$destinationDomain,$destination_number,$detectedDestinationDefinition,$conferenceQuality);


        }break;
        case "IVR":{
            // play the IVR

            serveConfigurationSendToIVR($domainOriginal,$destination_number,$destinationDomain,$detectedDestinationDefinition,$customerID,$recordingDestinationDefinition,$recordingDestinationShort);


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
// ====================================================================================================================

function detectDestinationType($destinationNumber,$extensionLength,$customerDetails,$domainID,$mysqli){
    $destinationType = array();
    $destinationType["type"] = "USER";

    error_log("detectDestinationType: [$destinationNumber][$extensionLength][$domainID] ");


    $customerID = $customerDetails["id"];
    $dialedNumberLength = strlen($destinationNumber);
    $firstDialedDigit = substr($destinationNumber,0,1);

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
                    // parking lot
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



function serveConfigurationLocalExtension($domain,$extension,$recordingDestinationDefinition,$recordingDestinationShort = "",$customerID = ""){

        $destination_number = '^' . $extension . '$';
                                    
        $dialed_extension = 'dialed_extension='.$extension;
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
    $defaultContextConfiguration->writeAttribute( 'name', 'Local_Extension' );

    $defaultContextConfiguration->startElement( 'condition' );
    $defaultContextConfiguration->writeAttribute( 'field', 'destination_number' );
    $defaultContextConfiguration->writeAttribute( 'expression', $destination_number );



    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'export' );
    $defaultContextConfiguration->writeAttribute( 'data', $dialed_extension );
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
    $defaultContextConfiguration->writeAttribute( 'data', 'call_timeout=30' );
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

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'record_session' );
    $defaultContextConfiguration->writeAttribute( 'data', $recordingDestinationShort );
    $defaultContextConfiguration->endElement(); // action


    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'bridge' );
    $defaultContextConfiguration->writeAttribute( 'data', 'sofia/external/${dialed_extension}@${domain_name};fs_path=sip:$${vtpbx_proxy}:5060' );
    $defaultContextConfiguration->endElement(); // action

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'answer' );
    $defaultContextConfiguration->endElement(); // action

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'sleep' );
    $defaultContextConfiguration->writeAttribute( 'data', '1000' );
    $defaultContextConfiguration->endElement(); // action

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'bridge' );
    $defaultContextConfiguration->writeAttribute( 'data', 'loopback/app=voicemail:default ${domain_name} ${dialed_extension}' );
    $defaultContextConfiguration->endElement(); // action





    $defaultContextConfiguration->endElement(); // condition

    $defaultContextConfiguration->endElement();  //extension







    $defaultContextConfiguration->endElement();  // context

    $defaultContextConfiguration->endElement(); // section
    $defaultContextConfiguration->endElement(); // document



    //Echo the whole XML document
    echo $defaultContextConfiguration->outputMemory();






}







function serveConfigurationLocalExtensionViaDID($domain,$extension,$recordingDestinationDefinition,$recordingDestinationShort = "",$destinationDomain = "",$destinationExtension = "", $customerID = ""){

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
    $defaultContextConfiguration->writeAttribute( 'name', 'Local_Extension' );

    $defaultContextConfiguration->startElement( 'condition' );
    $defaultContextConfiguration->writeAttribute( 'field', 'destination_number' );
    $defaultContextConfiguration->writeAttribute( 'expression', $destination_number );



    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'export' );
    $defaultContextConfiguration->writeAttribute( 'data', $dialed_extension );
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
    $defaultContextConfiguration->writeAttribute( 'data', 'transfer_ringback=$${hold_music}' );
    $defaultContextConfiguration->endElement(); // action

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'set' );
    $defaultContextConfiguration->writeAttribute( 'data', 'call_timeout=30' );
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

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'record_session' );
    $defaultContextConfiguration->writeAttribute( 'data', $recordingDestinationShort );
    $defaultContextConfiguration->endElement(); // action


    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'bridge' );
    $defaultContextConfiguration->writeAttribute( 'data', 'sofia/external/${dialed_extension}@'.$destinationDomain.';fs_path=sip:$${vtpbx_proxy}:5060' );
    $defaultContextConfiguration->endElement(); // action

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'answer' );
    $defaultContextConfiguration->endElement(); // action

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'sleep' );
    $defaultContextConfiguration->writeAttribute( 'data', '1000' );
    $defaultContextConfiguration->endElement(); // action

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'bridge' );
    $defaultContextConfiguration->writeAttribute( 'data', 'loopback/app=voicemail:default '.$destinationDomain.' ${dialed_extension}' );
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


function serveConfigurationVoiceConference($context,$targetDomain,$extension,$detectedDestinationDefinition,$quality = "default",$customerID = ""){

    // handy variables

    $destination_number = '^' . $extension . '$';
    $conferenceName = $detectedDestinationDefinition . '-' . $targetDomain . '@'. $quality;  // 300-d1.vtpbx.net@default



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

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'conference' );
    $defaultContextConfiguration->writeAttribute( 'data', $conferenceName );
    $defaultContextConfiguration->endElement(); // action





    $defaultContextConfiguration->endElement(); // condition

    $defaultContextConfiguration->endElement();  //extension







    $defaultContextConfiguration->endElement();  // context

    $defaultContextConfiguration->endElement(); // section
    $defaultContextConfiguration->endElement(); // document



    //Echo the whole XML document
    echo $defaultContextConfiguration->outputMemory();






}


function serveConfigurationSendToQueue($contextDomain,$extension,$queueDomain,$queueName,$customerID = ""){

    // handy variables

    $destination_number = '^' . $extension . '$';
    $queueFifoData = $queueDomain . '-FIFO-' . $queueName .  ' in';  // d1.vtpbx.net-FIFO-1 , d1.vtpbx.net-FIFO-2, d1.vtpbx.net-FIFO-3 .....



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

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'set' );
    $defaultContextConfiguration->writeAttribute( 'data', 'fifo_music=$${hold_music}' );
    $defaultContextConfiguration->endElement(); // action

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'playback' );
    $defaultContextConfiguration->writeAttribute( 'data', 'ivr/ivr-hold_connect_call.wav' );
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




function serveConfigurationSendToIVR($contextDomain,$extension,$ivrDomain,$ivrID,$customerID = "",$recordingDestinationDefinition,$recordingDestinationShort){

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





    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'answer' );
    $defaultContextConfiguration->endElement(); // action

    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'record_session' );
    $defaultContextConfiguration->writeAttribute( 'data', $recordingDestinationShort );
    $defaultContextConfiguration->endElement(); // action



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
    $defaultContextConfiguration->writeAttribute( 'application', 'playback' );
    $defaultContextConfiguration->writeAttribute( 'data', 'ivr/ivr-you_are_now_logged_in.wav' );
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


function serveConfigurationExternalNumber($domain,$number_dialed,$external_gateway_id,$external_gateway_prefix,$customerID,$callerid="anonymous",$recordingDestinationShort=""){

    // handy variables

    $prefixDialed = substr($number_dialed,0,5);
    $destination_number = '^' . $prefixDialed . '(\d+)$';
    //{sip_invite_req_uri=sip:'.$number_dialed.'@'.$domain.'}




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


    // call recording for external calls:




    $defaultContextConfiguration->startElement( 'action' );
    $defaultContextConfiguration->writeAttribute( 'application', 'record_session' );
    $defaultContextConfiguration->writeAttribute( 'data', $recordingDestinationShort );
    $defaultContextConfiguration->endElement(); // action



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



                   // $dial_string = "${sofia_contact(${dialed_user}@${$domain})}";
                    $dial_string = 'sofia/external/'.$username.'@'.$domain.';fs_path=sip:$${vtpbx_proxy}:5060';







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
                            $defaultContextConfiguration->writeAttribute( 'name', 'dial-string' );
                            $defaultContextConfiguration->writeAttribute( 'value', $dial_string );  // dial string
                            $defaultContextConfiguration->endElement(); // param







                        $defaultContextConfiguration->endElement(); // params

                        $defaultContextConfiguration->startElement( 'variables' );

                            $defaultContextConfiguration->startElement( 'variable' );
                            $defaultContextConfiguration->writeAttribute( 'name', 'toll_allow' );
                            $defaultContextConfiguration->writeAttribute( 'value', 'domestic,international,local' );  // SIP password
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


function serveConfigurationOfIVRscenariosForOneClient($customerID,$mysqli){

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

                        $execParam1 = "execute_extension limit:'hash ivr in',set:domain_name=".$domainName.",set:ivr_destination_type=USER,set:ivr_destination_def=".$action_details.",transfer:'".$action_details." XML ".$domainName."' inline";

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

