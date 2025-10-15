#!/usr/bin/env php
<?php
/**
 * Continuous FreeSWITCH Event Listener for mod_audio_stream::play events
 * Uses the proven event socket functions from functions_event_socket.php
 */

$dir = __DIR__;
$dir = str_replace("freeswitch-scripts", "", $dir);
require_once $dir . "local_scripts/functions_event_socket.php";

// Configuration
$host = '127.0.0.1';
$port = 8021;
$password = 'ClueCon';

function logMessage($message) {
    $timestamp = date('Y-m-d H:i:s');
    echo "[$timestamp] $message\n";
}

// Signal handlers for graceful shutdown
$running = true;
pcntl_signal(SIGTERM, function() use (&$running) { $running = false; });
pcntl_signal(SIGINT, function() use (&$running) { $running = false; });

logMessage("Starting Continuous Event Listener");

while ($running) {
    try {
        logMessage("Connecting to FreeSWITCH event socket...");
        
        // Use the proven connection function
        $fp = event_socket_create($host, $port, $password);
        
        if (!$fp) {
            throw new Exception("Failed to connect to FreeSWITCH");
        }
        
        logMessage("Connected successfully");
        
        // Subscribe to CUSTOM events only (mod_audio_stream::play is CUSTOM)
        logMessage("Subscribing to CUSTOM events...");
        fputs($fp, "events plain CUSTOM\n\n");
        
        // Read the subscription response
        $response = "";
        $i = 0;
        while (!feof($fp) && $i < 100) {
            $buffer = fgets($fp, 1024);
            if ($buffer) {
                $response .= $buffer;
                if (strpos($response, "\n\n") !== false) {
                    break;
                }
            }
            usleep(1000);
            $i++;
        }
        
        if (strpos($response, 'Content-Type: text/event-plain') !== false) {
            logMessage("Successfully subscribed to events");
        } else {
            logMessage("WARNING: Unexpected subscription response: " . trim($response));
        }
        
        // Now continuously read events
        logMessage("Listening for mod_audio_stream::play events...");
        
        $buffer = '';
        $eventCount = 0;
        $lastHeartbeat = time();
        
        while ($running && !feof($fp)) {
            // Process signals
            pcntl_signal_dispatch();
            
            // Read data from socket
            $data = fgets($fp, 4096);
            
            if ($data !== false && strlen($data) > 0) {
                $buffer .= $data;
                $lastHeartbeat = time();
                
                // Process complete events (look for double newline)
                while (($pos = strpos($buffer, "\n\n")) !== false) {
                    $event = substr($buffer, 0, $pos);
                    $buffer = substr($buffer, $pos + 2);
                    
                    $eventCount++;
                    
                    // Log every 10th event to show we're receiving data
                    if ($eventCount % 10 == 0) {
                        logMessage("DEBUG: Processed $eventCount events total");
                    }
                    
                    // Look for specific event types
                    if (preg_match('/Event-Name: ([^\r\n]+)/', $event, $eventMatches)) {
                        $eventName = trim($eventMatches[1]);
                        
                        // Log interesting events
                        if (in_array($eventName, ['CUSTOM', 'CHANNEL_CREATE', 'CHANNEL_DESTROY'])) {
                            logMessage("DEBUG: Event type: $eventName");
                            
                            // Check for mod_audio_stream subclass
                            if (preg_match('/Event-Subclass: ([^\r\n]+)/', $event, $subclassMatches)) {
                                $subclass = trim($subclassMatches[1]);
                                logMessage("DEBUG: Event subclass: $subclass");
                                
                                // THIS IS WHAT WE'RE LOOKING FOR!
                                if ($subclass === 'mod_audio_stream::play') {
                                    logMessage("🎵 === AUDIO PLAY EVENT DETECTED ===");
                                    
                                    // Extract UUID
                                    if (preg_match('/Unique-ID: ([a-f0-9\-]+)/', $event, $uuidMatches)) {
                                        $uuid = $uuidMatches[1];
                                        logMessage("🎵 UUID: $uuid");
                                        
                                        // Extract file path from JSON body
                                        if (preg_match('/"file":"([^"]+)"/', $event, $fileMatches)) {
                                            $audioFile = $fileMatches[1];
                                            logMessage("🎵 Audio file: $audioFile");
                                            
                                            // Validate file exists
                                            if (file_exists($audioFile) && is_readable($audioFile)) {
                                                // Create new connection for API command
                                                logMessage("🎵 Sending uuid_broadcast command...");
                                                
                                                $apiFp = event_socket_create($host, $port, $password);
                                                if ($apiFp) {
                                                    $broadcastCmd = "api uuid_broadcast $uuid $audioFile both";
                                                    $result = event_socket_request($apiFp, $broadcastCmd);
                                                    
                                                    logMessage("🎵 Broadcast result: " . trim($result));
                                                    fclose($apiFp);
                                                } else {
                                                    logMessage("🎵 ERROR: Could not create API connection");
                                                }
                                            } else {
                                                logMessage("🎵 WARNING: Audio file not found: $audioFile");
                                            }
                                        } else {
                                            logMessage("🎵 WARNING: Could not extract audio file from event");
                                        }
                                    } else {
                                        logMessage("🎵 WARNING: Could not extract UUID from event");
                                    }
                                    
                                    logMessage("🎵 === EVENT PROCESSING COMPLETE ===");
                                }
                            }
                        }
                    }
                }
            }
            
            // Check for connection timeout
            if (time() - $lastHeartbeat > 30) {
                logMessage("No data received for 30 seconds, connection may be dead");
                break;
            }
            
            // Small sleep to prevent high CPU usage
            usleep(1000); // 1ms
        }
        
    } catch (Exception $e) {
        logMessage("ERROR: " . $e->getMessage());
    }
    
    // Close connection
    if (isset($fp) && $fp) {
        fclose($fp);
        logMessage("Connection closed");
    }
    
    if ($running) {
        logMessage("Connection lost, reconnecting in 5 seconds...");
        sleep(5);
    }
}

logMessage("Event listener stopped");
?>