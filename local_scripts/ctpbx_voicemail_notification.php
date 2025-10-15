<?php


ini_set("error_log", "/tmp/php-ctpbx_voicemail_notification.log");


$dir = __DIR__ ;
$dir = str_replace("local_scripts","",$dir);

include_once $dir."/settings.php";
include_once $dir."/db_connect.php";
include_once $dir."/functions.php";
include_once $dir."/fs_configuration/functions_vtpbx_fs.php";

include_once 'functions_local_scripts.php';


$fd = fopen("php://stdin", "r");
$email_content = file_get_contents ("php://stdin");
fclose($fd);

ob_end_clean();
ob_start();




error_log("email :" . $email_content , 3, "/tmp/my-errors.log");

/*

        {
 "from_name": "111",
 "from_number": "111",
 "date_added": "Tuesday, May 18 2021, 12 21 PM",
 "to_user": "105",
 "to_domain": "d1.callertech.net",
 "customer": "",
 "message_len":"00:00:06",
 "uuid": "3d26b0ae-40da-4a1a-b510-1c018d08c664",
 "call_uuid": "3e1e75a7-59a5-4f18-995d-78232159b0a2"
}



 */


$email_content = substr($email_content,0,strpos($email_content,'X-Voicemail-Length'));

error_log("email  content after filtering: :" . $email_content , 3, "/tmp/my-errors.log");


$emailContentParsed = json_decode($email_content,true);

$from_name = $emailContentParsed["from_name"];
$from_number = $emailContentParsed["from_number"];
$date_added = $emailContentParsed["date_added"];
$to_user = $emailContentParsed["to_user"];
$to_domain = $emailContentParsed["to_domain"];

$message_len = $emailContentParsed["message_len"];
$uuid = $emailContentParsed["uuid"];
$call_uuid = $emailContentParsed["call_uuid"];

$customer = getCustomerIDbyDomainName($to_domain,$mysqli);



// CTPBX: Forward the event


$url = CT_POST_CDR_URL;
/*
 *
    Status: completed/no-answer/failed
    Direction: inbound/outbound
    From
    To
    Recording URL
 *
 */


$direction = "inbound";
$api_status = "completed";


$webhookDetails_arr = get_CT_webhook_url_and_token_by_customer_and_type($customer,CT_API_WEBHOOK_TYPE_CDRS,$mysqli);
$url = $webhookDetails_arr["webhook_url"];
$url_token = $webhookDetails_arr["webhook_token"];

$data = array(
    "token" => $url_token,
    "phonenumber" => $to_user,
    "note" => "sip_voicemail",
    "direction" => $direction,

    "duration" => $message_len,

    "from_name" => $from_name,
    "from_number" => $from_number,

    "uuid" => $uuid,
    "original_call_uuid" => $call_uuid,
    "customer" => $customer,
    "domain" => $to_domain,
    "status" => $api_status

);

$curlResponse  = httpPostViaCURL($url, $data);

$curlPostJSON = json_encode($data);
error_log("--CDR-- : [$uuid] POST: $curlPostJSON curl Response: [$curlResponse] .", 3, "/tmp/my-errors.log");




// UPDATE voicemail_messages table and insert original_call_uuid for the message with specific uuid;
sleep(5);


if(!updateCallLogItemWithVMDetails($uuid,$call_uuid ,$mysqli)){
    error_log("Error while trying to update VM message UUID in call_logs: vm uuid [$uuid]  call uuid: [$call_uuid]" , 3, "/tmp/my-errors.log");
}else{
    error_log("Updated VM message   UUID  in call logs. call uuid: [$call_uuid]  , vm uuid [$uuid]  " , 3, "/tmp/my-errors.log");
}




?>