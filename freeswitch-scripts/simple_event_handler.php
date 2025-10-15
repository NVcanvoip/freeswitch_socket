#!/usr/bin/env php
<?php
/**
 * Production FreeSWITCH Event Handler for mod_audio_stream::play events
 * Automatically broadcasts audio files back to callers
 * 
 * Usage: php simple_event_handler.php
 * Production: Run as systemd service
 */

// Configuration
define('FS_HOST', '127.0.0.1');
define('FS_PORT', 8021);
define('FS_PASSWORD', 'ClueCon');
define('LOG_FILE', '/var/log/freeswitch/audio_event_handler.log');
define('PID_FILE', '/var/run/audio_event_handler.pid');

// Logging function
function logMessage($message) {
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] $message\n";
    
    // Log to file if writable, otherwise to stdout
    if (is_writable(dirname(LOG_FILE))) {
        file_put_contents(LOG_FILE, $logEntry, FILE_APPEND | LOCK_EX);
    } else {
        echo $logEntry;
    }
}

// Signal handlers for graceful shutdown
function signalHandler($signal) {
    global $socket, $running;
    
    logMessage("Received signal $signal, shutting down gracefully...");
    $running = false;
    
    if ($socket) {
        fclose($socket);
    }
    
    if (file_exists(PID_FILE)) {
        unlink(PID_FILE);
    }
    
    exit(0);
}

// Install signal handlers
pcntl_signal(SIGTERM, 'signalHandler');
pcntl_signal(SIGINT, 'signalHandler');
pcntl_signal(SIGHUP, 'signalHandler');

// Write PID file
if (is_writable(dirname(PID_FILE))) {
    file_put_contents(PID_FILE, getmypid());
}

logMessage("Starting FreeSWITCH Audio Event Handler (PID: " . getmypid() . ")");

$running = true;
$reconnectAttempts = 0;
$maxReconnectAttempts = 10;

while ($running) {
    try {
        logMessage("Connecting to FreeSWITCH event socket at " . FS_HOST . ":" . FS_PORT);
        
        $socket = fsockopen(FS_HOST, FS_PORT, $errno, $errstr, 10);
        if (!$socket) {
            throw new Exception("Connection failed: $errstr ($errno)");
        }
        
        logMessage("Connected successfully");
        $reconnectAttempts = 0;
        
        // Set socket timeout
        stream_set_timeout($socket, 30);
        
        // Set non-blocking mode for reading
        stream_set_blocking($socket, false);
        
        // Authenticate
        fwrite($socket, "auth " . FS_PASSWORD . "\n\n");
        usleep(100000); // Wait 100ms
        $response = fread($socket, 1024);
        
        if (strpos($response, '+OK') === false) {
            throw new Exception("Authentication failed: $response");
        }
        
        logMessage("Authentication successful");
        
        // Subscribe to CUSTOM events (mod_audio_stream::play events are CUSTOM)
        fwrite($socket, "events plain CUSTOM\n\n");
        usleep(100000); // Wait 100ms
        $response = fread($socket, 1024);
        
        if (strpos($response, '+OK') === false) {
            throw new Exception("Event subscription failed: $response");
        }
        
        logMessage("Subscribed to CUSTOM events - listening for mod_audio_stream::play events...");
        
        $buffer = '';
        $lastHeartbeat = time();
        $loopCount = 0;
        
        while ($running && $socket) {
            // Process signals
            pcntl_signal_dispatch();
            
            $loopCount++;
            if ($loopCount % 1000 == 0) {
                logMessage("DEBUG: Still running, loop count: $loopCount, buffer size: " . strlen($buffer));
            }
            
            $data = fread($socket, 4096);
            
            if ($data !== false && strlen($data) > 0) {
                $buffer .= $data;
                $lastHeartbeat = time();
                
                // DEBUG: Log all data received
                logMessage("DEBUG: Received " . strlen($data) . " bytes: " . substr(str_replace(["\r", "\n"], ["\\r", "\\n"], $data), 0, 200) . "...");
                
                // Process complete events (terminated by double newline)
                while (($pos = strpos($buffer, "\n\n")) !== false) {
                    $event = substr($buffer, 0, $pos);
                    $buffer = substr($buffer, $pos + 2);
                    
                    // DEBUG: Log every event type we see
                    if (preg_match('/Event-Name: ([^\r\n]+)/', $event, $eventMatches)) {
                        $eventName = trim($eventMatches[1]);
                        logMessage("DEBUG: Processing event: $eventName");
                        
                        if (preg_match('/Event-Subclass: ([^\r\n]+)/', $event, $subclassMatches)) {
                            $subclass = trim($subclassMatches[1]);
                            logMessage("DEBUG: Event subclass: $subclass");
                        }
                    }
                    
                    // Look for mod_audio_stream::play events
                    if (strpos($event, 'Event-Subclass: mod_audio_stream::play') !== false) {
                        logMessage("=== AUDIO PLAY EVENT DETECTED ===");
                        
                        // Extract UUID
                        if (preg_match('/Unique-ID: ([a-f0-9\-]+)/', $event, $matches)) {
                            $uuid = $matches[1];
                            logMessage("Found UUID: $uuid");
                            
                            // Extract file path from JSON body at end of event
                            if (preg_match('/"file":"([^"]+)"/', $event, $fileMatches)) {
                                $audioFile = $fileMatches[1];
                                logMessage("Found audio file: $audioFile");
                                
                                // Validate file exists and is readable
                                if (file_exists($audioFile) && is_readable($audioFile)) {
                                    // Send playback command
                                    $command = "api uuid_broadcast $uuid $audioFile both\n\n";
                                    logMessage("Sending broadcast command: uuid_broadcast $uuid $audioFile both");
                                    
                                    fwrite($socket, $command);
                                    usleep(50000); // Wait 50ms for response
                                    
                                    $playbackResponse = fread($socket, 1024);
                                    if ($playbackResponse) {
                                        logMessage("Broadcast response: " . trim($playbackResponse));
                                    }
                                } else {
                                    logMessage("WARNING: Audio file not found or not readable: $audioFile");
                                }
                            } else {
                                logMessage("WARNING: Could not extract file path from event");
                            }
                        } else {
                            logMessage("WARNING: Could not extract UUID from event");
                        }
                        logMessage("=== EVENT PROCESSING COMPLETE ===");
                    }
                }
            }
            
            // Check for connection timeout (no data for 60 seconds)
            if (time() - $lastHeartbeat > 60) {
                logMessage("No data received for 60 seconds, connection may be dead");
                break;
            }
            
            // Check socket status
            $socketInfo = stream_get_meta_data($socket);
            if ($socketInfo['eof']) {
                logMessage("Socket EOF detected, connection closed");
                break;
            }
            
            usleep(10000); // Sleep 10ms between reads to prevent high CPU usage
        }
        
    } catch (Exception $e) {
        logMessage("ERROR: " . $e->getMessage());
    }
    
    // Close socket if open
    if ($socket) {
        fclose($socket);
        $socket = null;
    }
    
    if ($running) {
        $reconnectAttempts++;
        
        if ($reconnectAttempts >= $maxReconnectAttempts) {
            logMessage("Max reconnection attempts ($maxReconnectAttempts) reached, exiting");
            break;
        }
        
        $sleepTime = min(30, $reconnectAttempts * 5); // Progressive backoff, max 30 seconds
        logMessage("Connection lost, reconnecting in $sleepTime seconds (attempt $reconnectAttempts/$maxReconnectAttempts)...");
        sleep($sleepTime);
    }
}

// Cleanup
if (file_exists(PID_FILE)) {
    unlink(PID_FILE);
}

logMessage("Audio Event Handler stopped");
?>