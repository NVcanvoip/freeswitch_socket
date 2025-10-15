<?php


ini_set("error_log", "/tmp/php-vtpbx_fs_update_gateways.log");


$dir = __DIR__ ;
$dir = str_replace("local_scripts","",$dir);

include_once $dir."/settings.php";
include_once $dir."/db_connect.php";
include_once $dir."/functions.php";


include_once 'functions_local_scripts.php';


/*
    INSTALL instruction:

    vi /etc/freeswitch/sip_profiles/external.xml


    + add line:

    <X-PRE-PROCESS cmd="include" data="/opt/vtdial/gateways/*.xml"/>


    // most probably it's already there...
    mkdir /opt/vtpbx


    // create missing directories
    mkdir /opt/vtpbx/gateways &&     chmod +777 /opt/vtpbx -R






*/

// First, let's add gateways which are active and were modified within last 2 minutes

$gatewaysActiveAndModifiedRecently = getListOfActiveOutboundSIPprovidersModifiedRecently($mysqli);

if(count($gatewaysActiveAndModifiedRecently) >0 ){

    foreach($gatewaysActiveAndModifiedRecently as $gatewayID => $gatewayIP){
        // need to create configuration file for each fresh gateway

        $filePath = PBX_GATEWAY_FILES_BASE;   //  "/opt/vtpbx/gateways/"
        $filePathFull = $filePath  . "gw" . $gatewayID . ".xml";

        // build file and override whatever was there earlier.
        /*
                    <include>
                      <gateway name="vtb">
                      <param name="username" value="vtpbx"/>
                      <param name="password" value="secret"/>
                      <param name="proxy" value="54.36.176.220"/>
                      <param name="outbound-proxy" value="51.68.39.17"/>
                      <param name="register" value="false"/>
                      <param name="caller-id-in-from" value="false"/>
                      </gateway>
                    </include>
        */


        $fileContent =  '<include>' . PHP_EOL
            .'<gateway name="gw' . $gatewayID . '">' . PHP_EOL
            .'<param name="username" value="vtpbx"/>' . PHP_EOL
            .'<param name="password" value="secret"/>' . PHP_EOL
            .'<param name="proxy" value="' . $gatewayIP . '"/>' . PHP_EOL
            .'<param name="outbound-proxy" value="' . VTPBX_OUT_PROXY . '"/>' . PHP_EOL
            .'<param name="register" value="false"/>' . PHP_EOL
            .'<param name="caller-id-in-from" value="false"/>' . PHP_EOL
            .'</gateway>' . PHP_EOL
            .'</include>' . PHP_EOL;



        try{
            $file = fopen($filePathFull,'w');
            fwrite($file,$fileContent);
            fclose($file);

        }catch(Exception $ex){
            error_log("Exception in saving the gateway file details for GW [$gatewayID]. Details: ". $ex->getMessage());
        }


    }


    // Files prepared, let's "rescan" external SIP profile on Freeswitch
    // system("fs_cli", "-x", "sofia profile external rescan");


    $command = '/usr/bin/fs_cli -x "sofia profile external rescan"';

    $command = escapeshellcmd($command);

    echo $command . PHP_EOL;

    try{
        system($command);

    }catch(Exception $ex){
        error_log("Exception in rescanning of Freeswitch external profile. Details: ". $ex->getMessage());
    }






}





// Second, make sure to kill (remove) gateways which are not active and were altered within last 2 minutes

$gatewaysDeletedAndModifiedRecently = getListOfDeletedOutboundSIPprovidersModifiedRecently($mysqli);


if(count($gatewaysDeletedAndModifiedRecently) > 0 ){

    foreach($gatewaysDeletedAndModifiedRecently as $gatewayID) {


        $filePath = PBX_GATEWAY_FILES_BASE;   //  "/opt/vtpbx/gateways/"
        $filePathFull = $filePath  . "gw" . $gatewayID . ".xml";


        try{
            unlink($filePathFull);

        }catch(Exception $ex){
            error_log("Exception in deleting the gateway file for GW [$gatewayID]. Details: ". $ex->getMessage());
        }



        $command = '/usr/bin/fs_cli -x "sofia profile external killgw gw'. $gatewayID .'"';

        $command = escapeshellcmd($command);

        echo $command . PHP_EOL;

        try{
            shell_exec($command);

        }catch(Exception $ex){
            error_log("Exception in killing the gateway fia fs_cli for GW [$gatewayID]. Details: ". $ex->getMessage());
        }




    }




}












?>