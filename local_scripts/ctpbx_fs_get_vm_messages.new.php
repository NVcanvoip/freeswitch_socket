<?php
$dir = __DIR__;
$dir = str_replace("local_scripts", "", $dir);

include_once $dir . "/settings.php";
include_once $dir . "/db_connect.php";
include_once $dir . "/functions.php";
include_once $dir . "admin_panel/engine/functions_admin_portal.php";
include_once $dir . "/fs_configuration/functions_vtpbx_fs.php";
include_once $dir . "/fs_configuration/functions_s3.php";
include_once 'functions_local_scripts.php';
include_once 'functions_event_socket.php';

$log_enabled   = true;
$log_dir       = "/var/tmp/";
$log_file_pref = "ctpbx_vm_mon";
$log_file_name = $log_dir . $log_file_pref . date("Ymd") . '.log';
$log_date_fmt  = "Y.m.d H:i:s";
  

function myLog ($level, $msg)
{
global $log_enabled;
global $log_dir;
global $log_file_pref;
global $log_file_name;
global $log_date_fmt;


  if ($log_enabled) {  
    file_put_contents($log_file_name, $msg, FILE_APPEND);
  }
}

while(true){

  $max_con_fail  = 10;
  $con_fail      = 0;
  $s3_vm_msgs    = "/var/tmp/s3_vm_messages.lst";
  $fs_vm_msgs    = "/var/tmp/fs_vm_messages.lst";
  $s3_msg_array  = [];
  $fs_msg_array  = [];

  $start_time = microtime(true);

  $fp = @fopen($s3_vm_msgs, 'r'); 
  if ($fp) {
    $s3_msg_array = explode ("\n", fread($fp, filesize($s3_vm_msgs)));
    myLog (0, date($log_date_fmt) . " - Got array of " . count ($s3_msg_array) . " messages already in S3.\n");
  fclose($fp);
  } else {
    myLog (0, date($log_date_fmt) . "Could not open file to read list of S3 messages.\n");
  }
  
  myLog (0, date($log_date_fmt) . " - Starting Voice Mail parser loop.\n");

  // create socket to talk to FreeSwitch
  $password   = "ClueCon";
  $port       = "8021";
  $host       = "127.0.0.1";
  $fp         = event_socket_create($host, $port, $password);

  if ( !$fp ) {
    // we probably don't want to break attempts to connect completly, 
    // instead we'll keep trying to connect for configured number of times
    $con_fail++;
    if ( $con_fail > $max_con_fail) {
      myLog (0, date($log_date_fmt) . " - Failed to create API socket more than $max_con_fail times, will exit.\n");
      break;
    }  else {
      myLog (0, date($log_date_fmt) . " - Failed to create API socket $con_fail out of $max_con_fail times, will sleep and try again.\n");
      sleep (10);
      continue;
    }
  } else {
    myLog (0, date($log_date_fmt) . " - API socket created.\n");
    $con_fail = 0;
  }

  //1. Get the list customer (SELECT id FROM customers)
  myLog (0, date($log_date_fmt) . " - Getting list of PBXs.\n");
  $customerIDs = getCustomerIDsList($mysqli);
  myLog (0, date($log_date_fmt) . " - Got " . count ($customerIDs) . " PBXs.\n");
  
  foreach($customerIDs as $customerID){
    myLog (0, date($log_date_fmt) . " - Getting list of users for PBXs $customerID.\n");
    // Get list of users of this PBX (select id FROM users    WHERE customer = ?;)
    $userIDs = getUserIdsArrayForOneCustomer($customerID, $mysqli);
    myLog (0, date($log_date_fmt) . " - Got " . count ($userIDs) . " users in PBX $customerID.\n");
    
    foreach($userIDs as $userID ){
      $messagesArray = array();
      $userDetails   = getUserDetailsByID($userID,$mysqli);
      $username      = $userDetails["username"];
      $domainID      = $userDetails["domain"];
      $domainName    = getDomainNameByID($domainID,$mysqli);
      $userID        = $userDetails["id"];

      // get VM messages for one user:
      $cmd = "api vm_list $username@$domainName";
      myLog (0,  date($log_date_fmt) . " - calling [$cmd] from fs API.\n");
      $response = event_socket_request($fp, $cmd);
      
      $line = strtok ($response, "\r\n");
      while ($line !== false) {
        myLog (0, date($log_date_fmt) . " - Processing $line.\n");
        $line_arr = explode (':', $line);
        if ( count($line_arr) != 10 ) {
          myLog (0, date($log_date_fmt) . " - ERR - $line exploded into " . count($line_arr) . " elements. Skip.\n");
          break;
        } else {
          $created_epoch = $line_arr[0];
          $read_epoch    = $line_arr[1];
          $folder        = $line_arr[4];
          $path          = $line_arr[5];
          $uuid          = $line_arr[6];
          $cid_name      = $line_arr[7];
          $cid_number    = $line_arr[8];
          $message_len   = $line_arr[9];
          $call_uuid     = "";
          myLog (0, "VM-Parsed: [$uuid] created: [$created_epoch] folder [$folder] path [$path].\n");
          
          if (file_exists ($path)) {
            $call_uuid = getCalluuidByVMCallUUID($uuid,$mysqli);
            if ( strlen($call_uuid) < 16 ) {
              myLog (0, date($log_date_fmt) . " - Failed to retriev call-uuid for $username@$domainName $uuid. Skipping.\n");
              break;
            }
            
            $fs_msg_array[] = "$path";
            
            if ( in_array ("$domainName/$call_uuid.wav", $s3_msg_array) ) {
              myLog (0, date($log_date_fmt) . " - File $domainName/$call_uuid.wav is already in S3, skipping.\n");
            } else {
              myLog (0, date($log_date_fmt) . " - Uploading $domainName/$call_uuid.wav.\n");
              $retUrl = s3_putObject_vm_file($domainName, $path, $call_uuid . ".wav");
              if ( $retUrl === "") {
                myLog (0, date($log_date_fmt) . " - Failed to upload file to s3.\n");
              } else {
                myLog (0, date($log_date_fmt) . " - Uploaded file to s3.\n");
              }
            }
          } else {
            myLog (0, date($log_date_fmt) . " - ERR - $path does not exist.\n");
          }
        }
        $line = strtok( "\r\n" );
      }
    }  // foreach - users
  } // foreach - customers
  
  if ( count ($fs_msg_array) > 0 ) {
    myLog (0, date($log_date_fmt) . " - Got " . count ($fs_msg_array) . " messages in FS, will dump them into file.\n");
    file_put_contents($fs_vm_msgs, implode("\n", $fs_msg_array));
  }
  // Done
  $end_time = microtime(true);
  $execTime = round( (($end_time - $start_time)*1000), 3);
  myLog (0, date($log_date_fmt) . " - Finished processing VoiceMails. Exec time: $execTime ms.\n");

  if ( $fp ) {
    myLog (0, date($log_date_fmt) . " - Closing API socket.\n");
    
    if ( fclose($fp) ) {
      myLog (0, date($log_date_fmt) . " - Done OK. Finished loop.\n");
    } else {
      myLog (0, date($log_date_fmt) . " - Failed to close socket. Finished loop.\n");
    }
  }
  //usleep (10000000); // PAUSE 1s
  //sleep (10); // PAUSE 10s
  break;
} // while (true)
?>
