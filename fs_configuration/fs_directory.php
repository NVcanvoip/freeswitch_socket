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



// LOG request:
error_log("== FS DIRECTORY: " . json_encode($_REQUEST));


/*


// Three initial requests  - called when FS starts

{
   "hostname":"vtpbx-fs",
   "section":"directory",
   "tag_name":"",
   "key_name":"",
   "key_value":"",
   "Event-Name":"REQUEST_PARAMS",
   "Core-UUID":"82af13ef-84ae-4969-b9db-8226958a3dae",
   "FreeSWITCH-Hostname":"vtpbx-fs",
   "FreeSWITCH-Switchname":"vtpbx-fs",
   "FreeSWITCH-IPv4":"209.97.147.156",
   "FreeSWITCH-IPv6":"::1",
   "Event-Date-Local":"2019-04-04 07:40:34",
   "Event-Date-GMT":"Thu, 04 Apr 2019 07:40:34 GMT",
   "Event-Date-Timestamp":"1554363634063383",
   "Event-Calling-File":"sofia.c",
   "Event-Calling-Function":"launch_sofia_worker_thread",
   "Event-Calling-Line-Number":"3048",
   "Event-Sequence":"39",
   "purpose":"gateways",
   "profile":"external"
}

// *******************************************

{
   "hostname":"vtpbx-fs",
   "section":"directory",
   "tag_name":"",
   "key_name":"",
   "key_value":"",
   "Event-Name":"REQUEST_PARAMS",
   "Core-UUID":"82af13ef-84ae-4969-b9db-8226958a3dae",
   "FreeSWITCH-Hostname":"vtpbx-fs",
   "FreeSWITCH-Switchname":"vtpbx-fs",
   "FreeSWITCH-IPv4":"209.97.147.156",
   "FreeSWITCH-IPv6":"::1",
   "Event-Date-Local":"2019-04-04 07:40:34",
   "Event-Date-GMT":"Thu, 04 Apr 2019 07:40:34 GMT",
   "Event-Date-Timestamp":"1554363634060586",
   "Event-Calling-File":"sofia.c",
   "Event-Calling-Function":"launch_sofia_worker_thread",
   "Event-Calling-Line-Number":"3048",
   "Event-Sequence":"34",
   "purpose":"gateways",
   "profile":"internal"
}

// *******************************************

{
   "hostname":"vtpbx-fs",
   "section":"directory",
   "tag_name":"domain",
   "key_name":"name",
   "key_value":"209.97.147.156",
   "Event-Name":"GENERAL",
   "Core-UUID":"82af13ef-84ae-4969-b9db-8226958a3dae",
   "FreeSWITCH-Hostname":"vtpbx-fs",
   "FreeSWITCH-Switchname":"vtpbx-fs",
   "FreeSWITCH-IPv4":"209.97.147.156",
   "FreeSWITCH-IPv6":"::1",
   "Event-Date-Local":"2019-04-04 07:40:36",
   "Event-Date-GMT":"Thu, 04 Apr 2019 07:40:36 GMT",
   "Event-Date-Timestamp":"1554363636220015",
   "Event-Calling-File":"switch_core.c",
   "Event-Calling-Function":"switch_load_network_lists",
   "Event-Calling-Line-Number":"1610",
   "Event-Sequence":"523",
   "domain":"209.97.147.156",
   "purpose":"network-list"
}


*/


/*

// ******************************************


// Request coming when there is call TO user 101  :


{
   "hostname":"vtpbx-fs",
   "section":"directory",
   "tag_name":"domain",
   "key_name":"name",
   "key_value":"d1.vtpbx.com",
   "Event-Name":"REQUEST_PARAMS",
   "Core-UUID":"82af13ef-84ae-4969-b9db-8226958a3dae",
   "FreeSWITCH-Hostname":"vtpbx-fs",
   "FreeSWITCH-Switchname":"vtpbx-fs",
   "FreeSWITCH-IPv4":"209.97.147.156",
   "FreeSWITCH-IPv6":"::1",
   "Event-Date-Local":"2019-04-04 08:21:08",
   "Event-Date-GMT":"Thu, 04 Apr 2019 08:21:08 GMT",
   "Event-Date-Timestamp":"1554366068859611",
   "Event-Calling-File":"mod_commands.c",
   "Event-Calling-Function":"user_data_function",
   "Event-Calling-Line-Number":"1321",
   "Event-Sequence":"1053",
   "type":"var",
   "key":"id",
   "user":"101",
   "domain":"d1.vtpbx.com"
}








*/


if (!is_array($_REQUEST)) {
    trigger_error('$_REQUEST is not an array');
}

if(!test_input($_REQUEST["section"]) == "directory"){
    error_log("== FS DIRECTORY: this request is not for directory section! exiting.");
    exit;
}



// Main request parameters:

$domainName = test_input($_REQUEST["domain"]);
$userName = test_input($_REQUEST["user"]);

$domainDetails = getDomainDetailsByName($domainName,$mysqli);
$domainID = $domainDetails["id"];






if($domainID > 0){
    error_log("== FS DIRECTORY: domain [$domainName] id[$domainID], fetching details of user [$userName]");

    $userDetails = getUserDetailsByUsernameDomain($userName,$domainID,$mysqli);
    $userDetailsArr = array();
    $userDetailsArr[] = $userDetails;


    serveDirectoryUserDetails($domainName,$userDetailsArr);

    exit;





}else{
    //ERROR! This domain doesn't exist in the system.
    error_log("== FS DIRECTORY: This domain [$domainName] doesn't exist in the system.! exiting.");
    exit;


}






