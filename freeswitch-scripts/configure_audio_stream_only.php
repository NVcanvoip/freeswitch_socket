#!/usr/bin/env php
<?php
/**
 * Configure FreeSWITCH to log ONLY mod_audio_stream events
 * Disable noisy RTP and other debug logs
 */

$dir = __DIR__;
$dir = str_replace("freeswitch-scripts", "", $dir);
require_once $dir . "local_scripts/functions_event_socket.php";

function logMessage($message) {
    $timestamp = date('Y-m-d H:i:s');
    echo "[$timestamp] $message\n";
}

logMessage("Configuring FreeSWITCH for ONLY mod_audio_stream logging...");

$fp = event_socket_create('127.0.0.1', 8021, 'ClueCon');
if (!$fp) {
    logMessage("ERROR: Could not connect to FreeSWITCH");
    exit(1);
}

logMessage("Connected to FreeSWITCH");

// REDUCE overall log level but enable specific module logging
$commands = [
    // Set general log level to ERROR (less noise)
    "api console loglevel ERROR",
    "api log ERROR",
    
    // Disable RTP stats (the noisy audio stat messages)
    "api fsctl debug_level 0", 
    
    // Enable specific logging for mod_audio_stream only
    "api sofia loglevel mod_audio_stream 9",
    
    // Make sure mod_audio_stream is loaded
    "api load mod_audio_stream",
    
    // Set custom log level for specific events
    "api console loglevel mod_audio_stream DEBUG"
];

foreach ($commands as $cmd) {
    logMessage("Executing: $cmd");
    $result = event_socket_request($fp, $cmd);
    logMessage("Result: " . trim($result));
}

logMessage("\n--- Testing Configuration ---");

// Check if we can see any current channels with mod_audio_stream
$channels = event_socket_request($fp, "api show channels");
if (strpos($channels, 'uuid') !== false) {
    logMessage("Found active channels - mod_audio_stream events should now be logged");
} else {
    logMessage("No active channels currently");
}

fclose($fp);

logMessage("\n=== CONFIGURATION COMPLETE ===");
logMessage("Now only mod_audio_stream events should be logged to /var/log/freeswitch/freeswitch.log");
logMessage("");
logMessage("Monitor with:");
logMessage("tail -f /var/log/freeswitch/freeswitch.log | grep -i audio_stream");
logMessage("");
logMessage("Or run the audio event monitor:");
logMessage("php log_monitor_audio_events.php");

?>