<?php
$dir = __DIR__;
$dir = str_replace("local_scripts", "", $dir);

include_once $dir . "/settings.php";
include_once $dir . "/db_connect.php";
include_once $dir . "/functions.php";
include_once $dir . "/fs_configuration/functions_vtpbx_fs.php";
include_once 'functions_local_scripts.php';
include_once 'functions_event_socket.php';

while (true) {
  $log_enabled   = true;
  $log_dir       = "/var/tmp/";
  $log_file_pref = "ctpbx_queue_mon";
  $log_file_name = $log_dir . $log_file_pref . date("Ymd") . '.log';
  $log_date_fmt  = "Y.m.d H:i:s";
  $cmd = "api fifo list";
  
  $start_time = microtime(true);
  
  $log_msg = date($log_date_fmt) . " - Starting FS FIFO Queue parser loop.\n";
  file_put_contents($log_file_name, $log_msg, FILE_APPEND);
    
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
  
  //1.  Get fifo list from FS
  if ($log_enabled) {
    $log_msg = date($log_date_fmt) . " - Reading FIFO List from FreeSwitch.\n";
    file_put_contents($log_file_name, $log_msg, FILE_APPEND);
  }
  
  $response = event_socket_request($fp, $cmd);
  
  if ( empty ($response) ) {
      if ($log_enabled) {
        $log_msg = date($log_date_fmt) . " - Socket was empty.\n";
        file_put_contents($log_file_name, $log_msg, FILE_APPEND);
      }
      break;
  } else {
      if ($log_enabled) {
        $log_msg = date($log_date_fmt) . " - Socket returned " . strlen ($response) . " bytes.\n";
        file_put_contents($log_file_name, $log_msg, FILE_APPEND);
      }
  }
  // echo json_encode($response);

  // 2. Processing response as XML
  $xmlFIFOlist = simplexml_load_string($response);
  
  if ($xmlFIFOlist === false) {
    if ($log_enabled) {
      $log_msg = date($log_date_fmt) . " - Failed loading XML.\n";
      file_put_contents($log_file_name, $log_msg, FILE_APPEND);
    }
  } else {
    if ($log_enabled) {
      $log_msg = date($log_date_fmt) . " - Loaded XML.\n";
      file_put_contents($log_file_name, $log_msg, FILE_APPEND);
    }
    //////////////////////////////////////////////////////////////////////////////////////////////
    //                                      for Every FIFO
    // going through returned XML
    foreach ($xmlFIFOlist->fifo as $fifo) {
      // print_r($fifo);
      
      $name            = $fifo["name"];
      $nameArray       = explode("-FIFO-", $name);
      $queueDomainName = $nameArray[0];
      $queueID         = 0;
      
      if ( isset($nameArray[1])) {
        $queueID = intval($nameArray[1]);
      }

      // Continue only if domain is found and queue ID is > 0
      if ($queueDomainName != "" && $queueID > 0) {
        $queueDomainID  = getDomainIDbyName ($queueDomainName, $mysqli);
        $outboundArray  = array();
        $callersArray   = array();
        $consumersArray = array();
        $bridgesArray   = array();

        $consumer_count    = $fifo["consumer_count"];
        $caller_count      = $fifo["caller_count"];
        $waiting_count     = $fifo["waiting_count"];
        $outbound_strategy = $fifo["outbound_strategy"];
        $outbound_priority = $fifo["outbound_priority"];
        $ring_timeout      = $fifo["ring_timeout"];

        $outboundMembersCount = 0;
        $outboundMembersArr   = array();

        if ($log_enabled) {
          $log_msg = date($log_date_fmt) . " - Processing queue: name=" . $name . "\n";
          file_put_contents($log_file_name, $log_msg, FILE_APPEND);
        }
        //////////////////////////////////////////////////////////////////////////////////////////////
        //                                      for every MEMBER
        // loop on all outbound members and collect their data
        $outboundMembers  = $fifo->outbound->member;
        foreach ($outboundMembers as $member) {
          $mTimeout       = intval($member["timeout"]);
          $mStatus        = strval($member["status"]);
          $mStartTime     = strval($member["start-time"]);
          $mStopTime      = strval($member["stop-time"]);
          $mNextAvailable = strval($member["next-available"]);
          $mLoggedOnSince = strval($member["logged-on-since"]);
          $outboundMembersCount++;

          // echo "member:" . $member . "   -  ";
          $memberUserDomain = explode("@", $member);
          $domain           = $memberUserDomain[1]; // d1.vtpbx.com
          $userNotClean     = $memberUserDomain[0];
          $userAndParams    = explode("/", $userNotClean);

          $user = $userAndParams[1]; // 101
          $outboundArray[] = array(
            "user"            => $user,
            "domain"          => $domain,
            "timeout"         => $mTimeout,
            "status"          => $mStatus,
            "start-time"      => $mStartTime,
            "stop-time"       => $mStopTime,
            "next-available"  => $mNextAvailable,
            "logged-on-since" => $mLoggedOnSince
          );

          // echo "member:  [$user]@[$domain]  \r\n";
          if ($log_enabled) {
            $log_msg = date($log_date_fmt) . " - $name queue member: [$user]@[$domain].\n";
            file_put_contents($log_file_name, $log_msg, FILE_APPEND);
          }
        
        } // end loop on outbound members

        //////////////////////////////////////////////////////////////////////////////////////////////
        //                                      for every CALLER
        // loop on all callers and collect their data
        $fifoCallers = $fifo->callers->caller;
        foreach ($fifoCallers as $caller) {
          $caller_callerID      = strval($caller["caller_id_number"]);
          $caller_callerIDname  = strval($caller["caller_id_name"]);
          $caller_status        = strval($caller["status"]);
          $caller_position      = strval($caller["position"]);
          $caller_timestamp     = strval($caller["timestamp"]);
          $caller_uuid          = strval($caller["uuid"]);
          $caller_slot          = strval($caller["slot"]);

          // echo "caller:" . $caller_callerID . "   -  $caller_status  \r\n";

          $callersArray[] = array(
            "position"          => $caller_position,
            "uuid"              => $caller_uuid,
            "caller_id_number"  => $caller_callerID,
            "caller_id_name"    => $caller_callerIDname,
            "status"            => $caller_status,
            "timestamp"         => $caller_timestamp,
            "slot"              => $caller_slot
          );
          if ($log_enabled) {
            $log_msg = date($log_date_fmt) . " - $name queue caller: $caller_callerID; status=$caller_status.\n";
            file_put_contents($log_file_name, $log_msg, FILE_APPEND);
          }
        } // end loop on all callers

        //////////////////////////////////////////////////////////////////////////////////////////////
        //                                      for every CONSUMER
        // loop on all consumers and collect their data
        $fifoConsumers = $fifo->consumers->consumer;
        foreach ($fifoConsumers as $consumer) {
          $consumer_uuid              = strval($consumer["uuid"]);
          $consumer_status            = strval($consumer["status"]);
          $consumer_caller_id_name    = strval($consumer["caller_id_name"]);
          $consumer_caller_id_number  = strval($consumer["caller_id_number"]);
          $consumer_timestamp         = strval($consumer["timestamp"]);

          $consumersArray[] = array(
            "uuid"             => $consumer_uuid,
            "status"           => $consumer_status,
            "caller_id_name"   => $consumer_caller_id_name,
            "caller_id_number" => $consumer_caller_id_number,
            "timestamp"        => $consumer_timestamp
          );
          if ($log_enabled) {
            $log_msg = date($log_date_fmt) . " - $name queue consumer: $consumer_uuid; status=$consumer_status; caller=$consumer_caller_id_number.\n";
            file_put_contents($log_file_name, $log_msg, FILE_APPEND);
          }
        } // end loop on all consumers

        //////////////////////////////////////////////////////////////////////////////////////////////
        //                                      for every BRIDGE
        // loop on all bridges and collect their data
        $fifoBridges = $fifo->bridges;
        foreach ($fifoBridges->bridge as $bridge) {
          $bridge_fifo_name        = strval($bridge["fifo_name"]);
          $bridge_start_epoch      = strval($bridge["bridge_start_epoch"]);
          $caller                  = $bridge->caller;
          $bridge_caller_uuid      = strval($bridge->caller["uuid"]);
          $bridge_caller_id_name   = strval($bridge->caller["caller_id_name"]);
          $bridge_caller_id_number = strval($bridge->caller["caller_id_number"]);
          $bridge_consumer_uuid    = strval($bridge->consumer->uuid);
          $bridge_consumer_outgoing_uuid = strval($bridge->consumer->outgoing_uuid);

          $bridgesArray[] = array(
            "fifo_name"              => $bridge_fifo_name,
            "bridge_start_epoch"     => $bridge_start_epoch,
            "caller_uuid"            => $bridge_caller_uuid,
            "caller_id_name"         => $bridge_caller_id_name,
            "caller_id_number"       => $bridge_caller_id_number,
            "consumer_uuid"          => $bridge_consumer_uuid,
            "consumer_outgoing_uuid" => $bridge_consumer_outgoing_uuid
          );

          if ($log_enabled) {
            $log_msg = date($log_date_fmt) . " - $name queue bridge: $bridge_fifo_name; caller=$bridge_caller_id_number; consumer=$bridge_consumer_uuid.\n";
            file_put_contents($log_file_name, $log_msg, FILE_APPEND);
          }
          if ($log_enabled) {
            $log_msg = date($log_date_fmt) . " - Updating queue_call_logs table in DB.\n";
            file_put_contents($log_file_name, $log_msg, FILE_APPEND);
          }
          setQueueCallAsAnswered($bridge_start_epoch, $bridge_caller_id_number, $bridge_caller_uuid, $queueID, $queueDomainID, $mysqli);
        } // end loop on all bridges 

        if ($log_enabled) {
          // echo "Queue [$name] , callers: [$caller_count], consumers: [$consumer_count], waiting: [$waiting_count], outbound members: [$outboundMembersCount]\r\n ";
          $log_msg = date($log_date_fmt) . " - Finished queue: name=$name; callers=$caller_count; consumers=$consumer_count; waiting=$waiting_count; outbound members=$outboundMembersCount.\n";
          file_put_contents($log_file_name, $log_msg, FILE_APPEND);
        }
        
        $outboundArrayJson  = json_encode($outboundArray);
        $callersArrayJson   = json_encode($callersArray);
        $consumersArrayJson = json_encode($consumersArray);
        $bridgesArrayJson   = json_encode($bridgesArray);
        
        if ($log_enabled) {
          $log_msg = date($log_date_fmt) . " - Updating queues_rt_data table in DB.\n";
          file_put_contents($log_file_name, $log_msg, FILE_APPEND);
        }
        saveQueuesRtData($queueDomainID, $queueID, $consumer_count, $caller_count, $waiting_count, $outbound_strategy, $outbound_priority, $ring_timeout, $outboundArrayJson, $callersArrayJson, $consumersArrayJson, $bridgesArrayJson, $mysqli);
      } // end-if: fifo processing when domain is found and queue ID is > 0
    } // end going through returned XML with fifo queues
  } // end-if: XML was parsed

  $end_time = microtime(true);
  $execTime = round((($end_time - $start_time) * 1000) , 3);
  if ($log_enabled) {
    $log_msg = date($log_date_fmt) . " - Finished processing FIFO list. Exec time: " . $execTime . "ms.\n";
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
  //usleep(100000);  // sleep 0.1 sec
  break;
}
?>

