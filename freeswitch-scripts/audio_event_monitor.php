<?php
$dir = __DIR__;
$dir = str_replace("freeswitch-scripts", "", $dir);

include_once $dir . "/local_scripts/functions_event_socket.php";

echo "Starting audio playback monitor...\n";

// Create event socket connection using existing function
$host = "127.0.0.1";
$port = "8021"; 
$password = "ClueCon";

$fp = event_socket_create($host, $port, $password);

if (!$fp) {
    die("Failed to create event socket connection\n");
}

echo "Connected to FreeSWITCH event socket\n";

// Subscribe to events using the same method as existing code
$cmd = "events plain CUSTOM";
$response = event_socket_request($fp, $cmd);
echo "Events subscription response: $response\n";

echo "Monitoring for mod_audio_stream::play events...\n";

// Monitor events in a loop
while (true) {
    // Check for incoming events using non-blocking reads
    $eventData = "";
    $buffer = "";

    // Read available data
    while (!feof($fp)) {
        $line = fgets($fp, 4096);
        echo "Event data: $line\n";
        if ($line === false) {
            break; // No more data available
        }
        
        $buffer .= $line;
        echo "\n=== PLAY EVENT DETECTED ===\n";
        
        // Check if we have a complete event (ends with double newline)
        if (strpos($buffer, "\n\n") !== false) {
            $eventData = $buffer;
            $buffer = "";
            
            // Process the event
            if (strpos($eventData, 'mod_audio_stream::play') !== false) {
                echo "\n=== PLAY EVENT DETECTED ===\n";
                
                // Extract UUID
                if (preg_match('/Unique-ID: ([a-f0-9\-]+)/', $eventData, $matches)) {
                    $uuid = $matches[1];
                    echo "UUID: $uuid\n";
                    
                    // Extract file path from the JSON body at the end
                    if (preg_match('/"file":"([^"]+)"/', $eventData, $fileMatches)) {
                        $audioFile = $fileMatches[1];
                        echo "Audio file: $audioFile\n";
                        
                        // Trigger playback using the existing event socket
                        $playbackCmd = "api uuid_broadcast $uuid $audioFile both";
                        echo "Executing: $playbackCmd\n";
                        
                        $playbackResult = event_socket_request($fp, $playbackCmd);
                        echo "Result: $playbackResult\n";
                    }
                }
                echo "==========================\n";
            }
        }
    }
    
    usleep(10000); // Sleep 10ms between checks
}

// Cleanup
if ($fp) {
    fclose($fp);
}

echo "Audio playback monitor ended\n";
?>