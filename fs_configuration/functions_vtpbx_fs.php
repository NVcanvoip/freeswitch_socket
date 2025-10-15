<?php


function getDomainIDbyName($domain_name,$mysqli){
    $response = 0;

    $sql = "select id from domains where domain_name = ?;";


    if ($stmt = $mysqli->prepare($sql)){

        $stmt->bind_param('s', $domain_name);
        $stmt->execute();
        $stmt->bind_result($response);
        $stmt->fetch();
        $stmt->close();


        if($mysqli->error){
            error_log("MySQL Query error. [$sql]. " . $mysqli->error );
            return false;
        }

        return $response;


    } else {
        return false;
    }




}


function getSingleDomainNamebyCustomer($customerID,$mysqli){
    $response = "";

    $sql = "select domain_name from domains where customer = ? order by id ASC limit 1;";


    if ($stmt = $mysqli->prepare($sql)){

        $stmt->bind_param('i', $customerID);
        $stmt->execute();
        $stmt->bind_result($response);
        $stmt->fetch();
        $stmt->close();


        if($mysqli->error){
            error_log("MySQL Query error. [$sql]. " . $mysqli->error );
            return false;
        }

        return $response;


    } else {
        return false;
    }




}



/*  Returns array:   dDetails[id]  dDetails[customer]   */
function getDomainDetailsByName($domain_name,$mysqli){
   // error_log("getDomainDetailsByName , domain [$domain_name]  ");
    $response = array();

    $sql = "select id, customer, domain_name from domains where domain_name = ?;";


    if ($stmt = $mysqli->prepare($sql)){

        $stmt->bind_param('s', $domain_name);
        $stmt->execute();
        $stmt->bind_result($id, $customer,$name );
        $stmt->fetch();



        if($mysqli->error){
            error_log("MySQL Query error. [$sql]. " . $mysqli->error );
            return false;
        }

        $stmt->close();

        $response["id"] = $id;
        $response["customer"] = $customer;
        $response["name"] = $name;


        return $response;


    } else {
        return false;
    }


}

//




function getCustomerDetailsByID($customerID,$mysqli){
    $response = array();

    $sql = "select vt_client_id,name,type,limit_extensions,limit_channels_internal,limit_channels_incoming,limit_channels_external,extension_length,sip_provider,sip_provider_prefix,external_caller_id, moh,sip_provider_tf,sip_provider_prefix_tf, webhook_url_for_cdrs, webhook_url_for_incoming_calls, webhook_token  FROM customers where id = ?;";

    if ($stmt = $mysqli->prepare($sql)){

        $stmt->bind_param('i', $customerID);
        $stmt->execute();
        $stmt->bind_result($vt_client_id,$name,$type,$limit_extensions,$limit_channels_internal,$limit_channels_incoming,$limit_channels_external,$extension_length, $sip_provider, $sip_provider_prefix, $external_caller_id, $moh, $sip_provider_tf,$sip_provider_prefix_tf, $webhook_url_for_cdrs, $webhook_url_for_incoming_calls, $webhook_token);
        $stmt->fetch();
        $stmt->close();

        $response["id"] = $customerID;
        $response["vt_client_id"] = $vt_client_id;
        $response["name"] = $name;
        $response["type"] = $type;
        $response["limit_extensions"] = $limit_extensions;
        $response["limit_channels_internal"] = $limit_channels_internal;
        $response["limit_channels_incoming"] = $limit_channels_incoming;
        $response["limit_channels_external"] = $limit_channels_external;
        $response["extension_length"] = $extension_length;
        $response["sip_provider"] = $sip_provider;
        $response["sip_provider_prefix"] = $sip_provider_prefix;
        $response["external_caller_id"] = $external_caller_id;
        $response["moh"] = $moh;
        $response["sip_provider_tf"] = $sip_provider_tf;
        $response["sip_provider_prefix_tf"] = $sip_provider_prefix_tf;

        $response["webhook_url_for_cdrs"] = $webhook_url_for_cdrs;
        $response["webhook_url_for_incoming_calls"] = $webhook_url_for_incoming_calls;
        $response["webhook_token"] = $webhook_token;



        if($mysqli->error){
            error_log("MySQL Query error in getCustomerDetailsByID. [$sql]. " . $mysqli->error );
            return false;
        }

        return $response;


    } else {
        if($mysqli->error){
            error_log("MySQL Query error in getCustomerDetailsByID. [$sql]. " . $mysqli->error );
            return false;
        }


        return false;
    }


}




// DID provider, customer and the DID itself.
function getDomainByCustomerDidProvider($did_provider,$customerID,$did_number,$mysqli){
    $response = 0;

    $sql = "SELECT d.domain_name FROM did_numbers dn JOIN domains d ON dn.domain = d.id WHERE dn.customer = ? AND dn.did_provider >0 AND dn.did_number = ?;";


    if ($stmt = $mysqli->prepare($sql)){

        $stmt->bind_param('is', $customerID,$did_number);
        $stmt->execute();
        $stmt->bind_result($response);
        $stmt->fetch();
        $stmt->close();


        if($mysqli->error){
            error_log("MySQL Query error. [$sql]. " . $mysqli->error );
            return false;
        }

        return $response;


    } else {
        return false;
    }



}



function getCustomerIDbyDomainName($domain_name,$mysqli){
    $response = 0;

    $sql = "SELECT customer from domains where domain_name = ?;";


    if ($stmt = $mysqli->prepare($sql)){

        $stmt->bind_param('s', $domain_name);
        $stmt->execute();
        $stmt->bind_result($response);
        $stmt->fetch();
        $stmt->close();


        if($mysqli->error){
            error_log("MySQL Query error. [$sql]. " . $mysqli->error );
            return false;
        }

        return $response;


    } else {
        return false;
    }



}


function getDIDNumberDetailsForCustomer($did_number,$customerID,$domainID,$mysqli){
    error_log("getDIDNumberDetailsForCustomer: number: [$did_number] cust: [$customerID] domain:[$domainID]");
    $response = array();

    $sql = "select id,did_provider, action_type, action_def,pre_answer_playback,pre_answer_playback_file FROM did_numbers WHERE customer = ? AND domain = ? AND did_number = ? limit 1;";


    if ($stmt = $mysqli->prepare($sql)){

        $stmt->bind_param('iis', $customerID,$domainID,$did_number);
        $stmt->execute();
        $stmt->bind_result($id, $did_provider, $action_type, $action_def, $pre_answer_playback, $pre_answer_playback_file );
        $stmt->fetch();
        $stmt->close();


        if($mysqli->error){
            error_log("MySQL Query error. [$sql]. " . $mysqli->error );
            return false;
        }

        $response["id"] = $id;
        $response["action_type"] = $action_type;
        $response["action_def"] = $action_def;

        $response["did_number"] = $did_number;
        $response["customer_id"] = $customerID;
        $response["domain_id"] = $domainID;

        $response["pre_answer_playback"] = $pre_answer_playback;
        $response["pre_answer_playback_file"] = $pre_answer_playback_file;



    } else {
        if($mysqli->error){
            error_log("MySQL Query error. [$sql]. " . $mysqli->error );
            return false;
        }
        return false;
    }


    return $response;

}




function getDIDnumberDetailsByNumber($number_dialed,$mysqli){
    $returnArr = array();





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









    $sql = "select d.id, d.domain, dom.domain_name, d.did_provider, dp.name,d.did_number, d.action_type, d.action_def,d.sip_registration,d.pre_answer_playback,d.pre_answer_playback_file FROM did_numbers d JOIN domains dom ON d.domain = dom.id  JOIN did_providers dp ON d.did_provider = dp.id WHERE  d.did_number = ? ;";
    $stmt = $mysqli->prepare($sql);
    if($stmt){

        $stmt->bind_param('s', $number_dialed);
        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();
        $stmt->bind_result($id, $domain, $domain_name, $did_provider, $did_provider_name, $did_number, $action_type, $action_def,$sip_registration,$pre_answer_playback,$pre_answer_playback_file);

        while($stmt->fetch()){

            $sip_registrationArr = json_decode($sip_registration,true);

            $tts_text = "";

            if($pre_answer_playback_file >0 ){
                $ivrFileDetails = getIVRFileDetailsFS($pre_answer_playback_file, $mysqli);
                /*
                            $retArray["id"] = $id;
                            $retArray["customer"] = $customer;
                            $retArray["file_description"] = $file_description;
                            $retArray["file_name"] = $file_name;

                            $retArray["tts_text"] = $tts_text;
                            $retArray["s3_url"] = $s3_url;
                 */

                $tts_text = $ivrFileDetails["tts_text"];


            }





            $returnArr = array(
                "id" => $id,
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
            );

        }

    }else{
        error_log("CTPBX: error in getDIDnumberDetailsByID.");
    }



    return $returnArr;
}





function getIVRFileDetailsFS($id,$mysqli){

    $retArray = array();

    $sql = "select id,customer,file_description,file_name,tts_text,s3_url FROM ivr_files WHERE id = ?;";


    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->bind_result($id,$customer,$file_description,$file_name,$tts_text,$s3_url);
    $stmt->fetch();


    $retArray["id"] = $id;
    $retArray["customer"] = $customer;
    $retArray["file_description"] = $file_description;
    $retArray["file_name"] = $file_name;

    $retArray["tts_text"] = $tts_text;
    $retArray["s3_url"] = $s3_url;



    return $retArray;

}






function getExtensionNumberDetailsForCustomer($extension,$customerID,$domainID,$mysqli){
    error_log("getExtensionNumberDetailsForCustomer - ext: [$extension] cust: [$customerID] domain:[$domainID]");
    $response = array();

    $sql = "select id,action_type,action_def FROM extension_numbers WHERE customer = ? AND domain = ? AND extension = ? limit 1;";


    if ($stmt = $mysqli->prepare($sql)){

        $stmt->bind_param('iis', $customerID,$domainID,$extension);
        $stmt->execute();
        $stmt->bind_result($id, $action_type, $action_def );
        $stmt->fetch();
        $stmt->close();


        if($mysqli->error){
            error_log("MySQL Query error. [$sql]. " . $mysqli->error );
            return false;
        }

        $response["id"] = $id;
        $response["action_type"] = $action_type;
        $response["action_def"] = $action_def;



    } else {
        if($mysqli->error){
            error_log("MySQL Query error. [$sql]. " . $mysqli->error );
            return false;
        }
        return false;
    }


    return $response;

}



function getFeatureCodeDetailsForCustomer($extensionDialed,$customerID,$domainID,$mysqli){
    error_log("getFeatureCodeDetailsForCustomer: ext: [$extensionDialed] cust: [$customerID] domain:[$domainID]");


    $extensionForSelect = transformDestinationNumberIntoListForSQLqueryIN($extensionDialed);




    $response = array();

    $sql = 'select id,extension,action_type,action_def FROM feature_codes WHERE customer = ? AND domain = ?  AND extension IN  (' . $extensionForSelect . ') ORDER by extension DESC limit 1;';


    error_log("getFeatureCodeDetailsForCustomer : QUERY [$sql] ");



// select id,extension,action_type,action_def FROM feature_codes WHERE ((customer = 1 AND domain = 1) OR (customer = 0 AND domain = 0)) AND extension IN ('*', '*5', '*59', '*590', '*5901')  ORDER by extension DESC, customer DESC limit 1;
// select id,extension,action_type,action_def FROM feature_codes WHERE customer = 1 AND domain = 1  AND extension IN ('*', '*5', '*59', '*590', '*5901') ORDER by extension DESC limit 1;

    if ($stmt = $mysqli->prepare($sql)){

        $stmt->bind_param('ii', $customerID,$domainID);
        $stmt->execute();
        $stmt->bind_result($id, $extension, $action_type, $action_def );
        $stmt->fetch();
        $stmt->close();


        if($mysqli->error){
            error_log("MySQL Query error. [$sql]. " . $mysqli->error );
            return false;
        }

        // Additional parameters for some feature codes:
        switch($action_type){
            case "EAVESDROP":{
                // extract [ext] from dialed number

                $targetExtension = str_replace($extension,'',$extensionDialed);
                $action_def = $targetExtension;

            }break;




        }



        $response["id"] = $id;
        $response["action_type"] = $action_type;
        $response["action_def"] = $action_def;



    } else {
        if($mysqli->error){
            error_log("MySQL Query error. [$sql]. " . $mysqli->error );
            return false;
        }
        return false;
    }


    return $response;

}




function getFeatureCodeDetailsDefaultList($extensionDialed,$mysqli){
    error_log("getFeatureCodeDetailsDefaultList: ext: [$extensionDialed] ");


    $extensionForSelect = transformDestinationNumberIntoListForSQLqueryIN($extensionDialed);




    $response = array();

    $sql = 'select id,extension,action_type,action_def FROM feature_codes WHERE customer = 0 AND domain = 0  AND extension IN  (' . $extensionForSelect . ') ORDER by extension DESC limit 1;';

    error_log("getFeatureCodeDetailsDefaultList : QUERY [$sql] ");

    if ($stmt = $mysqli->prepare($sql)){

        $stmt->execute();
        $stmt->bind_result($id, $extension, $action_type, $action_def );
        $stmt->fetch();
        $stmt->close();


        if($mysqli->error){
            error_log("MySQL Query error. [$sql]. " . $mysqli->error );
            return false;
        }


        // Additional parameters for some feature codes:
        switch($action_type){
            case "EAVESDROP":{
                // extract [ext] from dialed number
                $count = 1;
                $targetExtension = str_replace($extension,'',$extensionDialed,$count);
                $action_def = $targetExtension;
                error_log("getFeatureCodeDetailsDefaultList , [$extensionDialed], [$extension], [$targetExtension]");
            }break;




        }




        $response["id"] = $id;
        $response["extension"] = $extension;
        $response["action_type"] = $action_type;
        $response["action_def"] = $action_def;



    } else {
        if($mysqli->error){
            error_log("MySQL Query error. [$sql]. " . $mysqli->error );
            return false;
        }
        return false;
    }


    return $response;

}


function transformDestinationNumberIntoListForSQLqueryIN($destination){
    $s = '';

    while(strlen($destination)>0){
        $s .= '"'.$destination.'",';
        $destination = substr($destination,0,-1);
    }
    $s = substr($s,0,-1);  // remove last comma from the string


    return $s;
}




//




function getUserDetailsByUsernameDomain($username,$domainID,$mysqli){
    $response = array();

    $sql = "SELECT id,customer,name,username,type,sip_password,record_internal,record_incoming,record_external,vm_password, domain , vm_enable,vm_greeting,vm_timeout,call_forwarding,external_caller_id,disable_external_calls FROM users WHERE username = ? AND domain = ?;";

    if ($stmt = $mysqli->prepare($sql)){

        $stmt->bind_param('si', $username,$domainID);
        $stmt->execute();
        $stmt->bind_result($id,$customer,$name,$username,$type,$sip_password,$record_internal,$record_incoming,$record_external,$vm_password,$domain,$vm_enable,$vm_greeting,$vm_timeout,$call_forwarding,$external_caller_id, $disable_external_calls);
        $stmt->fetch();
        $stmt->close();

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

        $response["disable_external_calls"] = $disable_external_calls;




        if($mysqli->error){
            error_log("MySQL Query error. [$sql]. " . $mysqli->error );
            return false;
        }

        return $response;


    } else {
        return false;
    }


}

function getUserDetailsByID($userID,$mysqli){
    $response = array();

    $sql = "SELECT id,customer,name,username,type,sip_password,record_internal,record_incoming,record_external,vm_password, domain, vm_enable,vm_greeting,vm_timeout,call_forwarding,external_caller_id, disable_external_calls FROM users WHERE id = ?;";

    if ($stmt = $mysqli->prepare($sql)){

        $stmt->bind_param('i', $userID);
        $stmt->execute();
        $stmt->bind_result($id,$customer,$name,$username,$type,$sip_password,$record_internal,$record_incoming,$record_external,$vm_password,$domain   ,$vm_enable,$vm_greeting,$vm_timeout,$call_forwarding,$external_caller_id, $disable_external_calls);
        $stmt->fetch();
        $stmt->close();








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
        $response["disable_external_calls"] = $disable_external_calls;





        if($mysqli->error){
            error_log("MySQL Query error. [$sql]. " . $mysqli->error );
            return false;
        }

        return $response;


    } else {
        return false;
    }


}

//





function insertCallLogItem($customer,$domain,$call_uuid,$call_from,$call_to,$destination_type,$destination,$call_status,$hangup_cause,$qos_mos,$qos_quality,$call_time,$answer_time,$end_time,$duration,$mysqli){


    $sql = "INSERT INTO call_logs(customer,domain,call_uuid,call_from,call_to,destination_type,destination,call_status,hangup_cause,qos_mos,qos_quality,call_time,answer_time,end_time,duration) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?);";

    if ($stmt = $mysqli->prepare($sql)){
        if($mysqli->error){
            error_log("--CDR-- [insertCallLogItem] MySQL Query error. [$sql]. " . $mysqli->error );
            return false;
        }
        $stmt->bind_param('iisssssssddsssi', $customer,$domain,$call_uuid,$call_from,$call_to,$destination_type,$destination,$call_status,$hangup_cause,$qos_mos,$qos_quality,$call_time,$answer_time,$end_time,$duration);
        if($mysqli->error){
            error_log("--CDR-- [insertCallLogItem] MySQL Query error. [$sql]. " . $mysqli->error );
            return false;
        }
        $stmt->execute();
        if($mysqli->error){
            error_log("--CDR-- [insertCallLogItem] MySQL Query error. [$sql]. " . $mysqli->error );
            return false;
        }
        $stmt->close();
        if($mysqli->error){
            error_log("--CDR-- [insertCallLogItem] MySQL Query error. [$sql]. " . $mysqli->error );
            return false;
        }


        if($mysqli->error){
            error_log("--CDR-- [insertCallLogItem] MySQL Query error. [$sql]. " . $mysqli->error );
            return false;
        }

        return true;


    } else {
        error_log("--CDR-- [insertCallLogItem] MySQL Query error. [$sql]. " . $mysqli->error );
        return false;

    }


}


function insertCallLogExtraItem($call_uuid,$base_call_uuid,  $call_from,$call_to,$call_status,$hangup_cause,$qos_mos,$qos_quality,$call_time,$answer_time,$end_time,$duration,$mysqli){


    $sql = "INSERT INTO call_logs_extra(call_uuid,base_call_uuid,call_from,call_to,call_status,hangup_cause,qos_mos,qos_quality,call_time,answer_time,end_time,duration) VALUES (?,?,?,?,?,?,?,?,?,?,?,?);";

    if ($stmt = $mysqli->prepare($sql)){
        if($mysqli->error){
            error_log("[insertCallLogExtraItem] MySQL Query error. [$sql]. " . $mysqli->error );
            return false;
        }
        $stmt->bind_param('ssssssddsssi', $call_uuid,$base_call_uuid,  $call_from,$call_to,$call_status,$hangup_cause,$qos_mos,$qos_quality,$call_time,$answer_time,$end_time,$duration);
        if($mysqli->error){
            error_log("--CDR-- [insertCallLogExtraItem] MySQL Query error. [$sql]. " . $mysqli->error );
            return false;
        }
        $stmt->execute();
        if($mysqli->error){
            error_log("--CDR-- [insertCallLogExtraItem] MySQL Query error. [$sql]. " . $mysqli->error );
            return false;
        }
        $stmt->close();
        if($mysqli->error){
            error_log("--CDR-- [insertCallLogExtraItem] MySQL Query error. [$sql]. " . $mysqli->error );
            return false;
        }


        if($mysqli->error){
            error_log("--CDR-- [insertCallLogExtraItem] MySQL Query error. [$sql]. " . $mysqli->error );
            return false;
        }

        return true;


    } else {
        error_log("--CDR-- [insertCallLogExtraItem] MySQL Query error. [$sql]. " . $mysqli->error );
        return false;

    }


}


// INSERT queue_call_logs

function insertQueueCallLogs( $customer,$domain,$queue,$call_uuid,$caller_id_number,$caller_id_name,$mysqli  ){

    #error_log("insertQueueCallLogs: $customer,$domain,$queue,$call_uuid,$disposition,$agent");
    error_log("insertQueueCallLogs: $customer,$domain,$queue,$call_uuid");

    $sql = "INSERT INTO queue_call_logs(customer,domain,queue,call_uuid,call_time,caller_id_number,caller_id_name) VALUES ( ?,?,?,?,now(),?,? );";

    if ($stmt = $mysqli->prepare($sql)){
        if($mysqli->error){
            error_log("[insertQueueCallLogs] MySQL Query error. [$sql]. " . $mysqli->error );
            return false;
        }
        $stmt->bind_param('iiisss', $customer,$domain,$queue,$call_uuid,$caller_id_number,$caller_id_name);
        if($mysqli->error){
            error_log("[insertQueueCallLogs] MySQL Query error. [$sql]. " . $mysqli->error );
            return false;
        }
        $stmt->execute();
        if($mysqli->error){
            error_log("[insertQueueCallLogs] MySQL Query error. [$sql]. " . $mysqli->error );
            return false;
        }
        $stmt->close();
        if($mysqli->error){
            error_log("[insertQueueCallLogs] MySQL Query error. [$sql]. " . $mysqli->error );
            return false;
        }


        if($mysqli->error){
            error_log("[insertQueueCallLogs] MySQL Query error. [$sql]. " . $mysqli->error );
            return false;
        }

        return true;


    } else {
        error_log("[insertQueueCallLogs] MySQL Query error. [$sql]. " . $mysqli->error );
        return false;

    }


}



function setQueueCallAsAnswered($answer_time_epoch,$agentUsername,$call_uuid,$queueID,$domainID,$mysqli){

    //update queue_call_logs SET answer_time=from_unixtime(1556138244), agent=105 WHERE call_uuid = "eaa1e473-169c-4f36-9fb6-c5c8b2b52317" AND queue = 1 AND answer_time = 0;
    $sql = "UPDATE queue_call_logs SET answer_time=from_unixtime(?), agent= ( SELECT id from users WHERE domain = ? AND username = ? ), disposition = 1 WHERE call_uuid = ? AND queue = ? AND answer_time = 0;";


    if ($stmt = $mysqli->prepare($sql)){

        $stmt->bind_param('iissi', $answer_time_epoch,$domainID,$agentUsername,$call_uuid,$queueID);
        $stmt->execute();
        $stmt->close();


        if($mysqli->error){
            error_log("[setQueueCallAsAnswered 1]  MySQL Query error. [$sql]. " . $mysqli->error );
            return false;
        }



        // 2nd step, re-calculate

        // update queue_call_logs SET wait_time = TIME_TO_SEC(TIMEDIFF(answer_time, call_time)) WHERE call_uuid = "eaa1e473-169c-4f36-9fb6-c5c8b2b52317" AND queue = 1 AND wait_time = 0;

        $sql = "UPDATE queue_call_logs SET wait_time = TIME_TO_SEC(TIMEDIFF(answer_time, call_time)) WHERE call_uuid = ? AND queue = ? AND wait_time = 0;";


        if ($stmt = $mysqli->prepare($sql)) {

            $stmt->bind_param('si', $call_uuid, $queueID);
            $stmt->execute();
            $stmt->close();


            if ($mysqli->error) {
                error_log("[setQueueCallAsAnswered 2]  MySQL Query error. [$sql]. " . $mysqli->error);
                return false;
            }



            return true;
        }



        return false;


    } else {
        return false;
    }



}

//
function updateQueueCallDisconnectionTime($end_time,$customer,$domain,$queue,$call_uuid,$mysqli){


    $sql = "UPDATE queue_call_logs set end_time = ?  WHERE customer = ?  AND queue = ? AND call_uuid = ?;";


    if ($stmt = $mysqli->prepare($sql)){

        $stmt->bind_param('siis', $end_time,$customer,$queue,$call_uuid );
        $stmt->execute();
        $stmt->close();


        if($mysqli->error){
            error_log("[updateQueueCallDisconnectionTime 1]  MySQL Query error. [$sql]. " . $mysqli->error );
            return false;
        }

        // 2nd step, re-calculate

        // update queue_call_logs SET wait_time = TIME_TO_SEC(TIMEDIFF(answer_time, call_time)) WHERE call_uuid = "eaa1e473-169c-4f36-9fb6-c5c8b2b52317" AND queue = 1 AND wait_time = 0;

        $sql = "UPDATE queue_call_logs set duration = TIME_TO_SEC(TIMEDIFF(end_time, answer_time))  WHERE customer = ?  AND queue = ? AND call_uuid = ? AND answer_time > 0 ;";


        if ($stmt = $mysqli->prepare($sql)) {

            $stmt->bind_param('iis', $customer,$queue,$call_uuid);
            $stmt->execute();
            $stmt->close();


            if ($mysqli->error) {
                error_log("[updateQueueCallDisconnectionTime 2]  MySQL Query error. [$sql]. " . $mysqli->error);
                return false;
            }




            // 3rd step  - Consider call dropped due to no agent available.
            // Update wait time accordingly.

            // UPDATE queue_call_logs set wait_time = end_time - call_time WHERE     ....   AND answer_time = 0 AND duration = 0 and wait_time = 0;


            $sql = "UPDATE queue_call_logs set wait_time = TIME_TO_SEC(TIMEDIFF(end_time, call_time)), disposition = 2  WHERE  domain = ?  AND queue = ? AND call_uuid = ?  AND answer_time = 0 AND duration = 0 and wait_time = 0;";


            if ($stmt = $mysqli->prepare($sql)) {

                $stmt->bind_param('iis', $customer,$queue,$call_uuid);
                $stmt->execute();
                $stmt->close();


                if ($mysqli->error) {
                    error_log("[updateQueueCallDisconnectionTime 31]  MySQL Query error. [$sql]. " . $mysqli->error);
                    return false;
                }




                return true;
            }else{
                if ($mysqli->error) {
                    error_log("[updateQueueCallDisconnectionTime 32]  MySQL Query error. [$sql]. " . $mysqli->error);
                    return false;
                }

            }





            return true;
        }else{
            if ($mysqli->error) {
                error_log("[updateQueueCallDisconnectionTime 3]  MySQL Query error. [$sql]. " . $mysqli->error);
                return false;
            }

        }










        return false;


    } else {

        if($mysqli->error){
            error_log("[updateQueueCallDisconnectionTime]  MySQL Query error. [$sql]. " . $mysqli->error );
            return false;
        }


        return false;
    }



}



// =====================



function saveNewQueueSessionDetails($customer,$domain,$queue,$agent ,$mysqli){
    saveQueueSessionEndDetails($queue,$agent, $mysqli);
    $sql = "INSERT into queue_agent_sessions(customer,domain,queue,agent) VALUES (?,?,?,?);";


    if ($stmt = $mysqli->prepare($sql)){

        $stmt->bind_param('iiii', $customer,$domain,$queue,$agent);
        $stmt->execute();
        $stmt->close();


        if($mysqli->error){
            error_log("[saveNewQueueSessionDetails 1]  MySQL Query error. [$sql]. " . $mysqli->error );
            return false;
        }


        return true;


    } else {
        error_log("[saveNewQueueSessionDetails 2]  MySQL Query error. [$sql]. " . $mysqli->error );
        return false;
    }

}




function saveQueueSessionEndDetails($queue,$agent ,$mysqli){
    $sql = "UPDATE queue_agent_sessions SET session_end = now(), session_duration = session_end - session_start, date_updated = now() WHERE queue = ? AND agent = ? AND session_end is null;";


    if ($stmt = $mysqli->prepare($sql)){

        $stmt->bind_param('ii', $queue,$agent);
        $stmt->execute();
        $stmt->close();


        if($mysqli->error){
            error_log("[saveQueueSessionEndDetails 1]  MySQL Query error. [$sql]. " . $mysqli->error );
            return false;
        }


        return true;


    } else {
        error_log("[saveQueueSessionEndDetails 2]  MySQL Query error. [$sql]. " . $mysqli->error );
        return false;
    }

}








function getCustomerIVRMenusListFull($customerID,$mysqli){
    $returnArray = array();
    $sql = "select id,customer,domain,name,menu_details  from ivr_menus where customer = ?;";

    $stmt = $mysqli->prepare($sql);


    if ($stmt) {
        $stmt->bind_param("i", $customerID);
        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();

        $stmt->bind_result($id,$customer,$domain,$name,$menu_details);


        while ($stmt->fetch()) {  // 1 row only!!!
            $oneMenu = array(
                "id" => $id,
                "customer"=> $customer,
                "domain"=> $domain,
                "name"=> $name,
                "menu_details"=> $menu_details
            );

            $returnArray[] = $oneMenu;




        }

        return $returnArray;
    }else{
        error_log("VTPBX:  Error in getCustomerIVRMenusListFull, details: ". $mysqli->error);
        return false;
    }





}



function getIVRFileNameByID($id,$mysqli){


    $sql = "select file_name FROM ivr_files WHERE id = ?;";


    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->bind_result($file_name);
    $stmt->fetch();



    return $file_name;

}



function getDomainNameByID($domainID,$mysqli){
    $response = 0;

    $sql = "select domain_name from domains where id = ?;";


    if ($stmt = $mysqli->prepare($sql)){

        $stmt->bind_param('i', $domainID);
        $stmt->execute();
        $stmt->bind_result($response);
        $stmt->fetch();
        $stmt->close();


        if($mysqli->error){
            error_log("MySQL Query error. [$sql]. " . $mysqli->error );
            return false;
        }

        return $response;


    } else {
        return false;
    }




}


function getDomainIdByDIDnumber($did_number,$mysqli){
    $response = 0;

    $sql = "select domain from did_numbers where did_number = ?;";


    if ($stmt = $mysqli->prepare($sql)){

        $stmt->bind_param('s', $did_number);
        $stmt->execute();
        $stmt->bind_result($response);
        $stmt->fetch();
        $stmt->close();


        if($mysqli->error){
            error_log("MySQL Query error. [$sql]. " . $mysqli->error );
            return false;
        }

        return $response;


    } else {
        return false;
    }




}



function getUserCFdetails($domain_name,$username,$mysqli){
    //error_log("getUserCFdetails:  [$domain_name][$username]");
    $customerID = getCustomerIDbyDomainName($domain_name,$mysqli);

    $sql = "select call_forwarding  from users  where customer = ? AND username = ?;";

    $stmt = $mysqli->prepare($sql);


    if ($stmt) {
        $stmt->bind_param("is", $customerID,$username);
        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();

        $stmt->bind_result($call_forwardingJSON);

        $stmt->fetch();


        return $call_forwardingJSON;
    }else{
        error_log("VTPBX:  Error in getUserCFdetails, details: ". $mysqli->error);
        return false;
    }





}




function getUserVMdetails($domain_name,$username,$mysqli){
    //error_log("getUserVMdetails:  [$domain_name][$username]");


    $customerID = getCustomerIDbyDomainName($domain_name,$mysqli);

    $sql = "select vm_enable,vm_password,vm_greeting,vm_timeout  from users  where customer = ? AND username = ?;";

    $stmt = $mysqli->prepare($sql);


    if ($stmt) {
        $stmt->bind_param("is", $customerID,$username);
        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();

        $stmt->bind_result($vm_enable,$vm_password,$vm_greeting,$vm_timeout);

        $stmt->fetch();


        $returnArr = array(
            "vm_enable" => $vm_enable,
            "vm_password" => $vm_password,
            "vm_greeting" => $vm_greeting,
            "vm_timeout" => $vm_timeout
        );



        return $returnArr;
    }else{
        error_log("VTPBX:  Error in getUserVMdetails, details: ". $mysqli->error);
        return false;
    }





}


function updateVoicemailMessageOriginalCallUUID($uuid,$original_call_uuid ,$mysqli){
    $sql = "UPDATE voicemail_messages SET original_call_uuid = ? WHERE uuid = ?;";


    if ($stmt = $mysqli->prepare($sql)){

        $stmt->bind_param('ss', $original_call_uuid,$uuid);
        $stmt->execute();
        $stmt->close();


        if($mysqli->error){
            error_log("[updateVoicemailMessageOriginalCallUUID 1]  MySQL Query error. [$sql]. " . $mysqli->error );
            return false;
        }


        return true;


    } else {
        error_log("[updateVoicemailMessageOriginalCallUUID 2]  MySQL Query error. [$sql]. " . $mysqli->error );
        return false;
    }

}


function updateCallLogItemWithVMDetails($uuid,$original_call_uuid ,$mysqli){
    $sql = "UPDATE call_logs SET is_vm = 1, vm_uuid = ? WHERE call_uuid = ?;";


    if ($stmt = $mysqli->prepare($sql)){

        $stmt->bind_param('ss', $uuid,$original_call_uuid);
        $stmt->execute();
        $stmt->close();


        if($mysqli->error){
            error_log("[updateCallLogItemWithVMDetails 1]  MySQL Query error. [$sql]. " . $mysqli->error );
            return false;
        }


        return true;


    } else {
        error_log("[updateCallLogItemWithVMDetails 2]  MySQL Query error. [$sql]. " . $mysqli->error );
        return false;
    }

}





function determineUserExternalCallerID($domain_name,$username,$mysqli){

    $domainID = getDomainIDbyName($domain_name,$mysqli);
    $customerID = getCustomerIDbyDomainName($domain_name,$mysqli);

    $customerDetails = getCustomerDetailsByID($customerID,$mysqli);

    $external_caller_id = $customerDetails["external_caller_id"];


    // $external_caller_id  -  this is the customer's DEFAULT caller ID for outbound calls.
    // external_caller_id_for_user    -  USER's external caller id  ???

    //$caller  - is this the extension number of the source user????

    //error_log("About to serve configuration for external number. IS this source extension caller:[$caller] domainID:[$domainID] customerid:[$customerID]   ");
    // About to serve configuration for external number. IS this source extension caller:[105]  customerid:[1]

    //$caller_user_id = getUserIDByLogin($caller,$mysqli);

    $caller_details = getUserDetailsByUsernameDomain($username,$domainID,$mysqli);
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

   // error_log(json_encode($caller_details));

    $external_caller_id_for_user = $caller_details["external_caller_id"];

    if(test_input($external_caller_id_for_user) != ""){
        //error_log("[CALLER_ID] Extension :[$caller] from domain [$domain]  has outbound caller ID : [$external_caller_id_for_user]  default user caller ID:[$external_caller_id] . The extension's own caller ID will be used at this time as it's not empty.  ");

        // user seems to have proper caller ID, let's use it instead of default caller ID for the customer (PBX)
        $external_caller_id = $external_caller_id_for_user;
    }else{

        //error_log("[CALLER_ID] Extension :[$caller] from domain [$domain]  has empty outbound caller ID  default PBX caller ID:[$external_caller_id] will be used at this time.  ");

    }


    return $external_caller_id;


}



function getQueueDetailsByID($id,$mysqli){

    $retArray = array();

    $sql = "SELECT id, customer, domain, name, params FROM queues WHERE id = ?;";


    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->bind_result($id, $customer, $domain, $name, $params);
    $stmt->fetch();


    $retArray["id"] = $id;
    $retArray["customer"] = $customer;
    $retArray["domain"] = $domain;
    $retArray["name"] = $name;
    $retArray["params"] = $params;

    return $retArray;

}

function getGroupRingStrategyNameByID($ring_strategy){

    switch($ring_strategy){
        case 0:{
            return "SEQUENTIAL";
        }break;
        case 1:{
            return "RING-ALL";
        }break;
        case 2:{
            return "ROUND-ROBIN";
        }break;



        default:{
            return "";
        }

    }

}


function getGroupDetailsByID($groupID, $mysqli){
    $returnArr = array();


    $sql = "select customer,domain,name,ring_strategy,group_members,group_failover_extension from groups WHERE id = ?;";
    $stmt = $mysqli->prepare($sql);
    if($stmt){

        $stmt->bind_param('i', $groupID);
        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();
        $stmt->bind_result($customer,$domain,$name,$ring_strategy,$group_members, $group_failover_extension);

        if($stmt->fetch()){

            $group_membersArr = json_decode($group_members,true);



            $returnArr = array(
                "id" => $groupID,
                "customer" => $customer,
                "domain" => $domain,
                "name" => $name,
                "ring_strategy" => $ring_strategy,
                "ring_strategy_name" => getGroupRingStrategyNameByID($ring_strategy),
                "group_members" => $group_membersArr,
                "group_failover_extension" => $group_failover_extension
            );


            return $returnArr;
        }else{
            return null;
        }

    }else{
        error_log("PBX: error in getGroupDetailsByID.");
    }



    return null;
}

function getActionDetailsByID($action_id, $mysqli){
    $returnArr = array();


    $sql = "select customer,domain,name,webhook_url,ivr_playback_file from actions WHERE id = ?;";
    $stmt = $mysqli->prepare($sql);
    if($stmt){

        $stmt->bind_param('i', $action_id);
        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();
        $stmt->bind_result($customer,$domain,$name,$webhook_url, $ivr_playback_file);

        if($stmt->fetch()){




            $returnArr = array(
                "id" => $action_id,
                "customer" => $customer,
                "domain" => $domain,
                "name" => $name,
                "webhook_url" => $webhook_url,
                "ivr_playback_file" => $ivr_playback_file
            );


            return $returnArr;
        }else{
            return null;
        }

    }else{
        error_log("PBX: error in getActionDetailsByID.");
    }



    return null;
}

function getActionDetailsForOneCustomer($customer, $mysqli){
    $returnArr = array();


    $sql = "select id,customer,domain,name,webhook_url,ivr_playback_file from actions  WHERE customer = ?;";
    $stmt = $mysqli->prepare($sql);
    if($stmt){

        $stmt->bind_param('i', $customer);
        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();
        $stmt->bind_result($action_id,$customer,$domain,$name,$webhook_url, $ivr_playback_file );

        while($stmt->fetch()){


            $returnArr[] = array(
                "id" => $action_id,
                "customer" => $customer,
                "domain" => $domain,
                "name" => $name,
                "webhook_url" => $webhook_url,
                "ivr_playback_file" => $ivr_playback_file

            );



        }

        return $returnArr;

    }else{
        error_log("PBX: error in getActionDetailsForOneCustomer.");
    }



    return null;
}

function getAllActionsDetails($mysqli){
    $returnArr = array();


    $sql = "select id,customer,domain,name,webhook_url,ivr_playback_file from actions;";
    $stmt = $mysqli->prepare($sql);
    if($stmt){

        //$stmt->bind_param('i', $customer);
        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();
        $stmt->bind_result($action_id,$customer,$domain,$name,$webhook_url, $ivr_playback_file );

        while($stmt->fetch()){




            $returnArr[] = array(
                "id" => $action_id,
                "customer" => $customer,
                "domain" => $domain,
                "name" => $name,
                "webhook_url" => $webhook_url,
                "ivr_playback_file" => $ivr_playback_file

            );



        }

        return $returnArr;

    }else{
        error_log("PBX: error in getAllActionsDetails.");
    }



    return null;
}





function getGroupDetailsForOneCustomer($customer, $mysqli){
    $returnArr = array();


    $sql = "select id,customer,domain,name,ring_strategy,group_members, group_failover_extension from groups WHERE customer = ?;";
    $stmt = $mysqli->prepare($sql);
    if($stmt){

        $stmt->bind_param('i', $customer);
        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();
        $stmt->bind_result($groupID,$customer,$domain,$name,$ring_strategy,$group_members,$group_failover_extension);

        while($stmt->fetch()){

            $group_membersArr = json_decode($group_members,true);



            $returnArr[] = array(
                "id" => $groupID,
                "customer" => $customer,
                "domain" => $domain,
                "name" => $name,
                "ring_strategy" => $ring_strategy,
                "ring_strategy_name" => getGroupRingStrategyNameByID($ring_strategy),
                "group_failover_extension" => $group_failover_extension,
                "group_members" => $group_membersArr

            );



        }

        return $returnArr;

    }else{
        error_log("PBX: error in getGroupDetailsByID.");
    }



    return null;
}



function getAllGroupsDetails($mysqli){
    $returnArr = array();


    $sql = "select id,customer,domain,name,ring_strategy,group_members, group_failover_extension from groups;";
    $stmt = $mysqli->prepare($sql);
    if($stmt){

        //$stmt->bind_param('i', $customer);
        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();
        $stmt->bind_result($groupID,$customer,$domain,$name,$ring_strategy,$group_members, $group_failover_extension);

        while($stmt->fetch()){

            $group_membersArr = json_decode($group_members,true);



            $returnArr[] = array(
                "id" => $groupID,
                "customer" => $customer,
                "domain" => $domain,
                "name" => $name,
                "ring_strategy" => $ring_strategy,
                "ring_strategy_name" => getGroupRingStrategyNameByID($ring_strategy),
                "group_failover_extension" => $group_failover_extension,
                "group_members" => $group_membersArr

            );



        }

        return $returnArr;

    }else{
        error_log("PBX: error in getGroupDetailsByID.");
    }



    return null;
}


// - MOH


function getMOHbyDomainName($domain_name,$mysqli){
    $moh = 0;

    $sql = "SELECT c.moh FROM domains d JOIN customers c  ON d.customer = c.id WHERE d.domain_name = ?;";

    $stmt = $mysqli->prepare($sql);


    if ($stmt) {
        $stmt->bind_param("s", $domain_name);
        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();

        $stmt->bind_result($moh);

        $stmt->fetch();


        return $moh;
    }else{
        error_log("VTPBX:  Error in getMOHbyDomainName, details: ". $mysqli->error);
        return $moh;
    }


}

function get_CT_webhook_url_and_token_by_customer_and_type($customer_id,$webhook_type,$mysqli){
    $webhook_url_default = CT_POST_CDR_URL;
    $webhook_token_default = CT_API_TOKEN;

    $sql = "SELECT webhook_url_for_cdrs, webhook_token FROM customers  WHERE id = ?;";

    if($webhook_type == CT_API_WEBHOOK_TYPE_INCOMING_CALLS){

        $sql = "SELECT webhook_url_for_incoming_calls, webhook_token FROM customers  WHERE id = ?;";
        $webhook_url_default = CT_POST_CALL_URL;
    }



    $stmt = $mysqli->prepare($sql);


    if ($stmt) {
        $stmt->bind_param("i", $customer_id);
        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();

        $stmt->bind_result($webhook_url, $webhook_token);

        $stmt->fetch();

        if($webhook_url != "" && $webhook_token != ""){

            return array(
                "webhook_url" => $webhook_url,
                "webhook_token" => $webhook_token
            );

        }else{
            return array(
                "webhook_url" => $webhook_url_default,
                "webhook_token" => $webhook_token_default
            );

        }


    }else{
        error_log("CTPBX:  Error in get_CT_webhook_url_and_token_by_customer_and_type, details: ". $mysqli->error);
        return array(
            "webhook_url" => $webhook_url_default,
            "webhook_token" => $webhook_token_default
        );
    }


}


function updateCustomerWebhookDetails($customer_id,$webhook_url_for_cdrs, $webhook_url_for_incoming_calls,$webhook_token ,$mysqli){
    $sql = "UPDATE customers SET webhook_url_for_cdrs = ? ,webhook_url_for_incoming_calls = ? , webhook_token =?  WHERE id=?;";


    if ($stmt = $mysqli->prepare($sql)){

        $stmt->bind_param('sssi', $webhook_url_for_cdrs, $webhook_url_for_incoming_calls,$webhook_token,  $customer_id);
        $stmt->execute();
        $stmt->close();


        if($mysqli->error){
            error_log("[updateCustomerWebhookDetails 1]  MySQL Query error. [$sql]. " . $mysqli->error );
            return false;
        }


        return true;


    } else {
        error_log("[updateCustomerWebhookDetails 2]  MySQL Query error. [$sql]. " . $mysqli->error );
        return false;
    }

}





?>
