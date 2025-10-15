<?php
$dir = __DIR__;
$dir = str_replace("local_scripts", "", $dir);

include_once $dir . "/settings.php";
include_once $dir . "/db_connect.php";
include_once $dir . "/functions.php";
include_once $dir . "admin_panel/engine/functions_admin_portal.php";
include_once $dir . "/fs_configuration/functions_vtpbx_fs.php";
include_once 'functions_local_scripts.php';
include_once 'functions_event_socket.php';


while(true){
  $log_enabled   = false;
  $log_dir       = "/var/log/";
  $log_file_pref = "ctpbx_vm_mon";
  $log_file_name = $log_dir . $log_file_pref . date("Ymd") . '.log';
  $log_date_fmt  = "Y.m.d H:i:s";

  $start_time = microtime(true);
  if ($log_enabled) {  
    $log_msg = date($log_date_fmt) . " - Starting Voice Mail parser loop.\n";
    file_put_contents($log_file_name, $log_msg, FILE_APPEND);
  }  
  // create socket to talk to FreeSwitch
  $password   = "ClueCon";
  $port       = "8021";
  $host       = "127.0.0.1";
  $fp         = event_socket_create($host, $port, $password);

  if ( !$fp ) {
    if ($log_enabled) {
      $log_msg = date($log_date_fmt) . " - Failed to create API socket.\n";
      file_put_contents($log_file_name, $log_msg, FILE_APPEND);
    }
    break;
  } else {
    if ($log_enabled) {
      $log_msg = date($log_date_fmt) . " - API socket created.\n";
      file_put_contents($log_file_name, $log_msg, FILE_APPEND);
    }
  }

  //1. Get the list customer (SELECT id FROM customers)
  if ($log_enabled) {
    $log_msg = date($log_date_fmt) . " - Getting list of PBXs.\n";
    file_put_contents($log_file_name, $log_msg, FILE_APPEND);
  }
  
  $customerIDs = getCustomerIDsList($mysqli);
  if ($log_enabled) {
    $log_msg = date($log_date_fmt) . " - Got " . count ($customerIDs) . "PBXs.\n";
    file_put_contents($log_file_name, $log_msg, FILE_APPEND);
  }
  
  foreach($customerIDs as $customerID){
    if ($log_enabled) {
      $log_msg = date($log_date_fmt) . " - Getting list of users for PBXs $customerID.\n";
      file_put_contents($log_file_name, $log_msg, FILE_APPEND);
    }
    // Get list of users of this PBX (select id FROM users    WHERE customer = ?;)
    $userIDs = getUserIdsArrayForOneCustomer($customerID, $mysqli);
    if ($log_enabled) {
      $log_msg = date($log_date_fmt) . " - Got " . count ($userIDs) . "users in PBX $customerID.\n";
      file_put_contents($log_file_name, $log_msg, FILE_APPEND);
    }
    
    foreach($userIDs as $userID ){
      $messagesArray = array();
      $userDetails   = getUserDetailsByID($userID,$mysqli);
      $username      = $userDetails["username"];
      $domainID      = $userDetails["domain"];
      $domainName    = getDomainNameByID($domainID,$mysqli);
      $userID        = $userDetails["id"];

      // get VM messages for one user:
      $cmd = "api vm_list $username@$domainName xml";
      if ($log_enabled) {
        $log_msg = date($log_date_fmt) . " - calling [$cmd] from fs API.\n";
        file_put_contents($log_file_name, $log_msg, FILE_APPEND);
      }
      $response = event_socket_request($fp, $cmd);
      $xmlVMlist = simplexml_load_string($response);
      if ($xmlVMlist === false) {
        if ($log_enabled) {
          $log_msg = date($log_date_fmt) . " - ERR - failed to load XML from response.\n";
          file_put_contents($log_file_name, $log_msg, FILE_APPEND);
        };
      } else {
        if ($log_enabled) {
          $log_msg = date($log_date_fmt) . " - Loaded XML from response.\n";
          file_put_contents($log_file_name, $log_msg, FILE_APPEND);
        };

        foreach($xmlVMlist->message as $message) {
          $created_epoch = $message->created_epoch;
          $read_epoch    = $message->read_epoch;
          $folder        = $message->folder;
          $path          = $message->path;
          $uuid          = $message->uuid;
          $cid_name      = $message->{'cid-name'};
          $cid_number    = $message->{'cid-number'};
          $message_len   = $message->{'message-len'};

          //if ($log_enabled) {
          //  $log_msg = "VM-Parsed: [$uuid] created: [$created_epoch] folder [$folder] path [$path].\n";
          //  file_put_contents($log_file_name, $log_msg, FILE_APPEND);
          //};
          // build an array to insert data of one user:
          $userVMmessage = array(
              "uuid"         => $uuid,
              "customer"     => $customerID,
              "domain_id"    => $domainID,
              "domain_name"  => $domainName,
              "username"     => $username,
              "user_id"      =>  $userID,
              "time_created" => $created_epoch,
              "time_read"    => $read_epoch,
              "folder"       => $folder,
              "path"         => $path,
              "cid_name"     => $cid_name,
              "cid_number"   => $cid_number,
              "message_len"  => $message_len
          );
          $messagesArray[] = $userVMmessage;
        }
      }
      
      if ($log_enabled) {
        $log_msg = date($log_date_fmt) . " - Got " . count ($messagesArray) . " messages for user $userID ($username).\n";
        file_put_contents($log_file_name, $log_msg, FILE_APPEND);
      };
    
      // DELETE from  voicemail_messages WHERE user_id = ?
      // INSERT into  voicemail_messages (uuid, customer, domain_id, domain_name, username, user_id, time_created, time_read, folder, path, cid_name, cid_number, message_len) 
      //          VALUES (?,?,?,?,?,?,  FROM_UNIXTIME(?),FROM_UNIXTIME(?)   ,?,?,?,?,?); "
      updateVMmessagesListForOneUser($userID, $messagesArray, $mysqli);
    }  // foreach - users
  } // foreach - customers
  
  // Done
  $end_time = microtime(true);
  $execTime = round( (($end_time - $start_time)*1000), 3);

  if ($log_enabled) {
    $log_msg = date($log_date_fmt) . " - Finished processing VoiceMails. Exec time: " . $execTime . "ms.\n";
    file_put_contents($log_file_name, $log_msg, FILE_APPEND);
  }

  if ( $fp ) {
    if ($log_enabled) {
      $log_msg = date($log_date_fmt) . " - Closing API socket.\n";
      file_put_contents($log_file_name, $log_msg, FILE_APPEND);
    }
    
    if ( fclose($fp) ) {
      if ($log_enabled) {
        $log_msg = date($log_date_fmt) . " - Done OK. Finished loop.\n";
        file_put_contents($log_file_name, $log_msg, FILE_APPEND);
      }
    } else {
      if ($log_enabled) {
        $log_msg = date($log_date_fmt) . " - Failed to close socket. Finished loop.\n";
        file_put_contents($log_file_name, $log_msg, FILE_APPEND);
      }
    }
  }

  //usleep (10000000); // PAUSE 1s
  sleep (10); // PAUSE 10s
  //break;
} // while (true)
?>
