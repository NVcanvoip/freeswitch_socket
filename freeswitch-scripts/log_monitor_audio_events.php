#!/usr/bin/env php
<?php
/**
 * Monitor FreeSWITCH logs for mod_audio_stream::play events
 * This is often more reliable than event sockets for specific events
 */

$dir = __DIR__;
$dir = str_replace("freeswitch-scripts", "", $dir);
require_once $dir . "local_scripts/functions_event_socket.php";

function logMessage($message) {
    $timestamp = date('Y-m-d H:i:s');
    echo "[$timestamp] $message\n";
}

// Use the custom events log file created by fs_cli
$logFile = '/tmp/custom_events.log';

if (!file_exists($logFile)) {
    logMessage("ERROR: Custom events log file not found: $logFile");
    logMessage("Please start the event capture first:");
    logMessage('fs_cli -x "/events plain CUSTOM mod_audio_stream::play" >> /tmp/custom_events.log &');
    exit(1);
}

if (!is_readable($logFile)) {
    logMessage("ERROR: Cannot read custom events log file: $logFile");
    exit(1);
}

logMessage("Monitoring FreeSWITCH log: $logFile");
logMessage("Looking for mod_audio_stream::play events...");

// Get current file size (to start reading from end)
$lastSize = filesize($logFile);
$running = true;
$lastModTime = filemtime($logFile);

logMessage("Starting with file size: $lastSize");

// Signal handlers
pcntl_signal(SIGTERM, function() use (&$running) { $running = false; });
pcntl_signal(SIGINT, function() use (&$running) { $running = false; });

while ($running) {
    pcntl_signal_dispatch();
    
    // Clear file stat cache to get fresh data
    clearstatcache(false, $logFile);
    
    $currentSize = filesize($logFile);
    $currentModTime = filemtime($logFile);
    
    // Check both size and modification time
    if ($currentSize > $lastSize || $currentModTime > $lastModTime) {
        logMessage("File changed - Size: $lastSize -> $currentSize, ModTime: $lastModTime -> $currentModTime");
        // File has grown, read new content
        $fp = fopen($logFile, 'r');
        if ($fp) {
            fseek($fp, $lastSize);
            
            while (($line = fgets($fp)) !== false) {
                // Skip empty lines and connection messages
                if (empty(trim($line)) || strpos($line, 'Connected to FreeSWITCH') !== false) {
                    continue;
                }
                
                // Look for the Event-Subclass line that indicates mod_audio_stream::play
                if (strpos($line, 'Event-Subclass: mod_audio_stream::play') !== false) {
                    logMessage("🎵 === AUDIO PLAY EVENT DETECTED ===");
                    
                    // We found the event start, now collect the full event
                    $eventData = $line;
                    $uuid = null;
                    $audioFile = null;
                    
                    // Continue reading until we have the complete event
                    while (($eventLine = fgets($fp)) !== false) {
                        $eventData .= $eventLine;
                        
                        // Extract UUID from the event
                        if (preg_match('/Unique-ID: ([a-f0-9\-]+)/', $eventLine, $uuidMatches)) {
                            $uuid = $uuidMatches[1];
                            logMessage("🎵 Found UUID: $uuid");
                        }
                        
                        // Look for JSON data with audio file
                        if (preg_match('/"file":"([^"]+)"/', $eventLine, $fileMatches)) {
                            $audioFile = $fileMatches[1];
                            logMessage("🎵 Found audio file: $audioFile");
                        }
                        
                        // Break when we reach the end of this event (empty line or next event)
                        if (trim($eventLine) === '' || strpos($eventLine, 'Event-Name:') !== false) {
                            break;
                        }
                    }
                    
                    // Now broadcast the audio if we have both UUID and file
                    if ($uuid && $audioFile) {
                        if (file_exists($audioFile) && is_readable($audioFile)) {
                            logMessage("🎵 Broadcasting audio file...");
                            $fp_event = event_socket_create('127.0.0.1', 8021, 'ClueCon');
                            if ($fp_event) {
                                $result = event_socket_request($fp_event, "api uuid_broadcast $uuid $audioFile both");
                                logMessage("🎵 Broadcast result: " . trim($result));
                                fclose($fp_event);
                            } else {
                                logMessage("🎵 ERROR: Could not connect to FreeSWITCH for broadcast");
                            }
                        } else {
                            logMessage("🎵 WARNING: Audio file not found or not readable: $audioFile");
                        }
                    } else {
                        logMessage("🎵 WARNING: Missing UUID or audio file from event");
                        logMessage("🎵 UUID: " . ($uuid ?: 'NOT FOUND'));
                        logMessage("🎵 File: " . ($audioFile ?: 'NOT FOUND'));
                    }
                    
                    logMessage("🎵 ===============================");
                }
                
                // Also look for any JSON containing "file" and UUID patterns
                if (strpos($line, '"file":') !== false && strpos($line, '.tmp.r8') !== false) {
                    logMessage("🎵 JSON AUDIO EVENT: " . trim($line));
                    
                    if (preg_match('/([a-f0-9\-]{36})/', $line, $uuidMatches) && 
                        preg_match('/"file":"([^"]+)"/', $line, $fileMatches)) {
                        
                        $uuid = $uuidMatches[1];
                        $audioFile = $fileMatches[1];
                        
                        logMessage("🎵 Extracted from JSON - UUID: $uuid, File: $audioFile");
                        
                        if (file_exists($audioFile)) {
                            $fp_event = event_socket_create('127.0.0.1', 8021, 'ClueCon');
                            if ($fp_event) {
                                $result = event_socket_request($fp_event, "api uuid_broadcast $uuid $audioFile both");
                                logMessage("🎵 JSON Broadcast result: " . trim($result));
                                fclose($fp_event);
                            }
                        }
                    }
                }
            }
            
            fclose($fp);
            $lastSize = $currentSize;
            $lastModTime = $currentModTime;
        }
    } else {
        // Only log periodically when there are no changes to reduce noise
        static $logCounter = 0;
        $logCounter++;
        if ($logCounter % 10 == 0) {
            logMessage("Still monitoring... (checked $logCounter times)");
        }
    }
    
    sleep(1); // Check every second
}

logMessage("Log monitor stopped");
?>