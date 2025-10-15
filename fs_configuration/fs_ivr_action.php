<?php
/**


 */

$dir = __DIR__ ;
$dir = str_replace("fs_configuration","",$dir);
require_once $dir . "/settings.php";
require_once $dir . "/db_connect.php";
require_once $dir . "/functions.php";


require_once "functions_vtpbx_fs.php";
require_once "fs_configuration_functions.php";



// Whatever will happen the response will be in text/xml format
header( 'Content-Type: text/xml' );



error_log("GET: ".json_encode($_GET));
//error_log("POST: ".json_encode($_POST));

// // GET: {"phonenumber":"442080895080","action":"5","customer":"7","domain":"d7.callertech.net"}
// check if there is any Webhook to run for the IVR destination "ACTION"


    if( intval($_GET["action"]) >0 ){
        $action_id = intval($_GET["action"]) ;
        // extract ivr action details:
        $actionDetails = getActionDetailsByID($action_id,$mysqli);

        $api_phonenumber = test_input($_GET["phonenumber"]) ;
        $customer_id = intval($_GET["customer"]) ;
        $domain = test_input($_GET["domain"]) ;

        $actionWebhookURL = $actionDetails["webhook_url"];
        $actionData = array(
            "phonenumber" => $api_phonenumber,
            "note" => "ivr_action",
            "customer" => $customer_id,
            "domain" => $domain,
            "action" => $action_id
        );

        $curlActionResponse  = httpPostViaCURL($actionWebhookURL, $actionData);
        $curlDataJSON = json_encode($actionData);
        error_log("--IVR_ACTION webhook-- : [$uuid] POST: $curlDataJSON curl Response: [$curlActionResponse] .");

    }else{
        error_log("this Webhook request includes ACTION but the action id is not > 0 ");

    }







?>





