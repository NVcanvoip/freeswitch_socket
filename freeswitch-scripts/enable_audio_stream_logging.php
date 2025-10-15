#!/usr/bin/env php
<?php
/**
 * Enable mod_audio_stream logging in FreeSWITCH
 * This script configures FreeSWITCH to log mod_audio_stream events
 */

$dir = __DIR__;
$dir = str_replace("freeswitch-scripts", "", $dir);
require_once $dir . "local_scripts/functions_event_socket.php";

function logMessage($message) {
    $timestamp = date('Y-m-d H:i:s');
    echo "[$timestamp] $message\n";
}

logMessage("Configuring FreeSWITCH for mod_audio_stream logging...");

// Connect to FreeSWITCH
$fp = event_socket_create('127.0.0.1', 8021, 'ClueCon');
if (!$fp) {
    logMessage("ERROR: Could not connect to FreeSWITCH");
    exit(1);
}

logMessage("Connected to FreeSWITCH");

// Enable debug level logging
$commands = [
    "api console loglevel DEBUG",
    "api log DEBUG", 
    "api fsctl debug_level 10",
    "api global_setvar console_loglevel=DEBUG",
    "api global_setvar default_loglevel=DEBUG"
];

foreach ($commands as $cmd) {
    logMessage("Executing: $cmd");
    $result = event_socket_request($fp, $cmd);
    logMessage("Result: " . trim($result));
}

// Check current log levels
logMessage("\n--- Checking Current Configuration ---");
$statusCommands = [
    "api console loglevel",
    "api fsctl debug_level", 
    "api global_getvar console_loglevel",
    "api global_getvar default_loglevel"
];

foreach ($statusCommands as $cmd) {
    logMessage("Checking: $cmd");
    $result = event_socket_request($fp, $cmd);
    logMessage("Current: " . trim($result));
}

// Test if mod_audio_stream is loaded
logMessage("\n--- Checking mod_audio_stream ---");
$modulesResult = event_socket_request($fp, "api show modules");
if (strpos($modulesResult, 'mod_audio_stream') !== false) {
    logMessage("✓ mod_audio_stream is loaded");
} else {
    logMessage("✗ mod_audio_stream is NOT loaded");
    logMessage("Attempting to load mod_audio_stream...");
    $loadResult = event_socket_request($fp, "api load mod_audio_stream");
    logMessage("Load result: " . trim($loadResult));
}

fclose($fp);

// Instructions for permanent configuration
logMessage("\n=== PERMANENT CONFIGURATION ===");
logMessage("To make logging permanent, add these to FreeSWITCH config files:");
logMessage("");
logMessage("1. In /etc/freeswitch/autoload_configs/logfile.conf.xml:");
logMessage('   <param name="logfile" value="/var/log/freeswitch/freeswitch.log"/>');
logMessage('   <param name="rollover" value="10485760"/>');
logMessage('   <param name="maximum-rotate" value="32"/>');
logMessage("");
logMessage("2. In /etc/freeswitch/autoload_configs/console.conf.xml:");
logMessage('   <param name="loglevel" value="DEBUG"/>');
logMessage("");
logMessage("3. In /etc/freeswitch/autoload_configs/modules.conf.xml:");
logMessage('   <load module="mod_audio_stream"/>');
logMessage("");
logMessage("4. Restart FreeSWITCH: sudo systemctl restart freeswitch");

logMessage("\nConfiguration complete!");
?>