<?php
// Simple PHP script to handle mod_audio_stream::play events
// Run this script in the background to automatically trigger audio playback

$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
if (!$socket) {
    die("Could not create socket\n");
}

// Connect to FreeSWITCH event socket (default: localhost:8021)
$result = socket_connect($socket, '127.0.0.1', 8021);
if (!$result) {
    die("Could not connect to FreeSWITCH event socket\n");
}

echo "Connected to FreeSWITCH event socket\n";

// Authenticate (default password: ClueCon)
socket_write($socket, "auth ClueCon\n\n");
$response = socket_read($socket, 1024);
echo "Auth response: $response\n";

// Subscribe to ALL CUSTOM events (since the PHP parser might be different)
socket_write($socket, "events plain CUSTOM\n\n");
$response = socket_read($socket, 1024);
echo "Events subscription: $response\n";

echo "Listening for mod_audio_stream::play events...\n";

while (true) {
    $data = socket_read($socket, 1024);
    if ($data === false) {
        echo "Connection lost\n";
        break;
    }

    echo "Event data: $data\n";
    
    if (strpos($data, 'mod_audio_stream::play') !== false) {
        echo "\n=== PLAY EVENT DETECTED ===\n";
        
        // Extract UUID from event
        if (preg_match('/Unique-ID: ([a-f0-9\-]+)/', $data, $matches)) {
            $uuid = $matches[1];
            echo "Found UUID: $uuid\n";
            
            // Extract file path from JSON body
            if (preg_match('/"file":"([^"]+)"/', $data, $fileMatches)) {
                $audioFile = $fileMatches[1];
                echo "Found audio file: $audioFile\n";
                
                // Send playback command to FreeSWITCH
                $playbackCommand = "api uuid_broadcast $uuid $audioFile both\n\n";
                echo "Sending: $playbackCommand";
                
                socket_write($socket, $playbackCommand);
                $playbackResponse = socket_read($socket, 1024);
                echo "Playback response: $playbackResponse\n";
                
            } else {
                echo "Could not extract file path from event\n";
            }
        } else {
            echo "Could not extract UUID from event\n";
        }
        echo "==========================\n";
    }
}

socket_close($socket);
?>