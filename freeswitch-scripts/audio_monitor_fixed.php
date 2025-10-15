<?php
$dir = __DIR__;
$dir = str_replace("freeswitch-scripts", "", $dir);

include_once $dir . "/local_scripts/functions_event_socket.php";

echo "Starting audio playback monitor...\n";

// Create event socket connection
$host = "127.0.0.1";
$port = "8021"; 
$password = "ClueCon";

$fp = event_socket_create($host, $port, $password);

if (!$fp) {
    die("Failed to create event socket connection\n");
}

echo "Connected to FreeSWITCH event socket\n";

// Subscribe to events using direct socket writes (not event_socket_request)
fputs($fp, "events plain CUSTOM\n\n");
echo "Subscribed to CUSTOM events\n";

// Wait a moment for subscription to take effect
usleep(100000);

echo "Monitoring for mod_audio_stream::play events...\n";

$buffer = "";
while (true) {
    // Use non-blocking read to get available data
    $data = fgets($fp, 4096);
    
    if ($data !== false && strlen($data) > 0) {
        $buffer .= $data;
        echo "Read: " . trim($data) . "\n";
        
        // Look for complete event blocks
        $eventParts = explode("\n\n", $buffer);
        
        // Process all complete events (except the last partial one)
        for ($i = 0; $i < count($eventParts) - 1; $i++) {
            $event = $eventParts[$i];
            
            if (strpos($event, 'mod_audio_stream::play') !== false) {
                echo "\n=== PLAY EVENT DETECTED ===\n";
                echo "Event content:\n$event\n";
                
                // Extract UUID
                if (preg_match('/Unique-ID: ([a-f0-9\-]+)/', $event, $matches)) {
                    $uuid = $matches[1];
                    echo "UUID: $uuid\n";
                    
                    // Extract file path from JSON body
                    if (preg_match('/"file":"([^"]+)"/', $event, $fileMatches)) {
                        $audioFile = $fileMatches[1];
                        echo "Audio file: $audioFile\n";
                        
                        // Create new socket for API command (recommended approach)
                        $apiFp = event_socket_create($host, $port, $password);
                        if ($apiFp) {
                            $playbackCmd = "api uuid_broadcast $uuid $audioFile both";
                            echo "Executing: $playbackCmd\n";
                            
                            $result = event_socket_request($apiFp, $playbackCmd);
                            echo "Playback result: $result\n";
                            
                            fclose($apiFp);
                        }
                    }
                }
                echo "==========================\n";
            }
        }
        
        // Keep the last partial event in buffer
        $buffer = $eventParts[count($eventParts) - 1];
    }
    
    usleep(50000); // Sleep 50ms between reads
}

// Cleanup
if ($fp) {
    fclose($fp);
}

echo "Audio monitor ended\n";
?>