<?php
$dir = __DIR__ ;
$dir = str_replace("local_scripts","",$dir);


include_once $dir.'/settings.php';
include_once $dir.'/db_connect.php';




function getListOfActiveOutboundSIPprovidersModifiedRecently($mysqli){
    // get the ids and IPs (interconnection details)

    $providers = array();

    $sql = "select id, ip_address from outbound_sip_providers WHERE is_deleted = 0 AND date_updated > date_sub(now(), INTERVAL 2 minute);";

    $stmt = $mysqli->prepare($sql);
    //$stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->bind_result($id,$ip);

    while($stmt->fetch()){
        $providers[$id] = $ip;



    }

    return $providers;


}




function getListOfDeletedOutboundSIPprovidersModifiedRecently($mysqli){
    // get the ids and IPs (interconnection details)

    $providers = array();

    $sql = "select id from outbound_sip_providers WHERE is_deleted = 1 AND date_updated > date_sub(now(), INTERVAL 2 minute);";

    $stmt = $mysqli->prepare($sql);
    //$stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->bind_result($id);

    while($stmt->fetch()){
        $providers[] = $id;



    }

    return $providers;


}





function saveQueuesRtData($domain,$queue,$consumer_count,$caller_count,$waiting_count,$outbound_strategy,$outbound_priority,$ring_timeout,$outbound,$callers,$consumers,$bridges,$mysqli){


    $sql = "INSERT INTO queues_rt_data(domain,queue,consumer_count,caller_count,waiting_count,outbound_strategy,outbound_priority,ring_timeout,outbound,callers,consumers,bridges) VALUES (?,?,?,?,?,?,?,?,?,?,?,?) 
   ON DUPLICATE KEY UPDATE  consumer_count = ? , caller_count = ?, waiting_count = ? , outbound_strategy = ?, outbound_priority = ?, ring_timeout = ?, outbound = ? , callers = ?, consumers = ?, bridges = ? , date_updated = now();";

    if($stmt = $mysqli->prepare($sql)){

        $stmt->bind_param('iiiiisiissssiiisiissss', $domain,$queue,$consumer_count,$caller_count,$waiting_count,$outbound_strategy,$outbound_priority,$ring_timeout,$outbound,$callers,$consumers,$bridges  , $consumer_count,$caller_count,$waiting_count,$outbound_strategy,$outbound_priority,$ring_timeout,$outbound,$callers,$consumers,$bridges );
        $stmt->execute();


        return true;

    }else{
        return false;
    }



}





function saveAppServerRTstats($appServerID,$meter_name,$meter_value,$mysqli){
    // get the details of one number to dial


    $sql = "INSERT INTO app_server_rt_stats (app_srv_id,meter_name,meter_value) VALUES (?,?,?) ON DUPLICATE KEY UPDATE meter_value = ?;";

    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('isii', $appServerID,$meter_name,$meter_value,$meter_value);
    $stmt->execute();
    // $stmt->bind_result($val1,$val2);
    //$stmt->fetch();
    $stmt->close();
    return true;


}

function updateVMmessagesListForOneUser($userID,$messagesArray,$mysqli){


        $mysqli->autocommit(FALSE);

        $mysqli->begin_transaction();

        // 1. delete channels of given app server
        $sql = "DELETE from  voicemail_messages WHERE user_id = ?; ";

        if ($stmt = $mysqli->prepare($sql)) {
            $stmt->bind_param('i', $userID);
            $stmt->execute();
            $stmt->close();
        } else {
            //nothing
        }

        // =========================


        // 2. insert channelsData
        $sql = "INSERT into  voicemail_messages(uuid, customer, domain_id, domain_name, username, user_id, time_created, time_read, folder, path, cid_name, cid_number, message_len) VALUES (?,?,?,?,?,?,  FROM_UNIXTIME(?),FROM_UNIXTIME(?)   ,?,?,?,?,?); ";
        if ($stmt = $mysqli->prepare($sql)) {


            
            
            foreach($messagesArray as $message){

                
                
                
                $uuid = $message["uuid"];
                $customer = $message["customer"];
                $domain_id = $message["domain_id"];
                $domain_name = $message["domain_name"];
                $username = $message["username"];
                $user_id = $message["user_id"];
                $time_created = $message["time_created"];
                $time_read = $message["time_read"];
                $folder = $message["folder"];
                $path = $message["path"];
                $cid_name = $message["cid_name"];
                $cid_number = $message["cid_number"];
                $message_len = $message["message_len"];
                
                $stmt->bind_param('siissiiissssi', $uuid, $customer, $domain_id, $domain_name, $username, $user_id, $time_created, $time_read, $folder, $path, $cid_name, $cid_number, $message_len);

                // FIX for $time_read
                if($time_read == 0){
                    $time_read  = 1;
                }


                $stmt->execute();

                if($mysqli->error){
                    error_log("PBX:  Error in updateVMmessagesListForOneUser, details: [$uuid]". $mysqli->error);
                    error_log(json_encode($message) . PHP_EOL);

                    return false;
                }
            }


            $stmt->close();
        } else {
        //nothing
        }

        // close prepared statement
        // $stmt->close();

        // commit transaction
        $mysqli->commit();




        return true;



}






