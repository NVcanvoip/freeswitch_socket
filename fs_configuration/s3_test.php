<?php




$dir = __DIR__ ;
$dir = str_replace("fs_configuration","",$dir);
//require_once $dir . "/lib/aws/vendor/autoload.php";
require_once $dir . "/settings.php";
require_once $dir . "/db_connect.php";
require_once $dir . "/functions.php";


require_once "functions_vtpbx_fs.php";




require_once "functions_s3.php";



$domain = "d1.callertech.net";
$fileName = "49a4c0e3-e4ef-453b-968c-ca8b3729e825.wav";



$s3fileURL = s3_putObject_recording_file($domain,$fileName,"private");


echo $s3fileURL . PHP_EOL;























?>