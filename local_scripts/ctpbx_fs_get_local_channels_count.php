<?php
$dir = __DIR__ ;
$dir = str_replace("local_scripts","",$dir);

include_once $dir."/settings.php";
include_once $dir."/db_connect.php";
include_once $dir."/functions.php";
include_once $dir."/fs_configuration/functions_vtpbx_fs.php";

include_once 'functions_local_scripts.php';
include_once 'functions_event_socket.php';




while(true){

/*
 *
 * Procedure:
 *  - get a list of active channels from asterisk ARI
 *  - parse the list and upload to real-time DB
 *
 *    END IF
 *
 */
//1. Get a list of active channels

    $start_time = microtime(true);




    $password = "ClueCon";  // default
    $port = "8021";  // default
    $host = "127.0.0.1";  // default

    $fp = event_socket_create($host, $port, $password);

    $cmd = "api show calls count";


    //get list of calls from FS:
    $response = event_socket_request_fast($fp, $cmd);

    // convert to Array
    $responseArr = explode("\n",$response);


    $channels = 0;

    foreach($responseArr as $responseLine){
        $responseLine = trim($responseLine);
        //echo "response line: [" . $responseLine . "] \r\n";

        if($responseLine ==""){ // no need to analyze the header line again, move to the next line


        }else{
            // there is some content

            $channels = str_replace(" total.","",$responseLine);
            //echo "channels 1: [" . $channels . "] \r\n";
            $channels = intval($channels);
           // echo "channels 2: [" . $channels . "] \r\n";
        }

    }


    // delete "old" data:
    $app_srv_id = APP_SRV_ID;

    saveAppServerRTstats($app_srv_id,"channels",$channels,$mysqli);


    // Done - channels saved in the DB
    $end_time = microtime(true);
    $execTime = round( (($end_time - $start_time)*1000), 3);


    echo " totalChannels: [$channels]   | exec time:[$execTime]ms \r\n";


    //error_log("dialer_get_local_channels, total [$totalChannels] , public [$publicChannels] , pbx [$pbxChannels] , time : [$execTime]ms");

    usleep(250000); // PAUSE 0,25s


}
logger_addLogEntry(5,LOG_CDR_ENGINE,"dialer_get_local_channels","Closing...", $mysqli);

?>