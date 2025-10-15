#!/usr/bin/env php
<?php
/**
 * Test script to verify FreeSWITCH event socket connectivity
 * Uses the existing event socket functions to test basic connectivity
 */

$dir = __DIR__;
$dir = str_replace("freeswitch-scripts", "", $dir);
require_once $dir . "local_scripts/functions_event_socket.php";

echo "=== FreeSWITCH Event Socket Test ===\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";

// Configuration
$host = '127.0.0.1';
$port = 8021;
$password = 'ClueCon';

echo "1. Testing basic connection to $host:$port\n";

// Test 1: Basic connection
$fp = event_socket_create($host, $port, $password);

if ($fp) {
    echo "   ✓ Connected successfully!\n\n";
    
    // Test 2: Basic API command
    echo "2. Testing basic API command (show status)\n";
    $response = event_socket_request($fp, "api show status");
    
    if ($response) {
        echo "   ✓ Got response (" . strlen($response) . " bytes):\n";
        echo "   " . substr(str_replace("\n", "\n   ", trim($response)), 0, 200) . "...\n\n";
    } else {
        echo "   ✗ No response to API command\n\n";
    }
    
    // Test 3: Show channels
    echo "3. Testing 'show channels' command\n";
    $channels = event_socket_request($fp, "api show channels");
    
    if ($channels) {
        $channelCount = substr_count($channels, 'uuid');
        echo "   ✓ Got channels response (" . strlen($channels) . " bytes)\n";
        echo "   Found approximately $channelCount active channels\n";
        
        if (strlen($channels) < 500) {
            echo "   Response preview:\n   " . str_replace("\n", "\n   ", trim($channels)) . "\n";
        }
        echo "\n";
    } else {
        echo "   ✗ No response to show channels\n\n";
    }
    
    // Test 4: Try to get events (different approach)
    echo "4. Testing event subscription (events plain ALL)\n";
    $eventResponse = event_socket_request($fp, "events plain ALL");
    
    if ($eventResponse) {
        echo "   ✓ Event subscription response: " . trim($eventResponse) . "\n";
        
        // Try to read some events
        echo "   Waiting 5 seconds for events...\n";
        $startTime = time();
        $eventCount = 0;
        
        while (time() - $startTime < 5) {
            $buffer = fgets($fp, 4096);
            if ($buffer && strlen(trim($buffer)) > 0) {
                $eventCount++;
                if ($eventCount <= 3) {
                    echo "   Event data: " . substr(str_replace(["\r", "\n"], ["\\r", "\\n"], $buffer), 0, 100) . "...\n";
                }
            }
            usleep(10000); // 10ms sleep
        }
        
        echo "   Total event lines received: $eventCount\n\n";
    } else {
        echo "   ✗ No response to event subscription\n\n";
    }
    
    // Test 5: Test uuid_broadcast (if we have active channels)
    if (isset($channels) && strpos($channels, 'uuid') !== false) {
        echo "5. Testing uuid_broadcast with active channel\n";
        
        // Extract a UUID from channels
        if (preg_match('/([a-f0-9\-]{36})/', $channels, $matches)) {
            $uuid = $matches[1];
            echo "   Found UUID: $uuid\n";
            
            // Test uuid_broadcast (this won't work without a real audio file, but we can see the response)
            $broadcastResponse = event_socket_request($fp, "api uuid_broadcast $uuid /tmp/nonexistent.wav both");
            echo "   Broadcast response: " . trim($broadcastResponse) . "\n\n";
        }
    } else {
        echo "5. Skipping uuid_broadcast test (no active channels)\n\n";
    }
    
    // Close connection
    fclose($fp);
    echo "6. Connection closed\n";
    
} else {
    echo "   ✗ Failed to connect!\n";
    echo "   Possible issues:\n";
    echo "   - FreeSWITCH not running\n";
    echo "   - Event socket not enabled (mod_event_socket)\n";
    echo "   - Wrong host/port (should be 127.0.0.1:8021)\n";
    echo "   - Wrong password (should be 'ClueCon' by default)\n";
    echo "   - Firewall blocking connection\n\n";
}

echo "=== Test Complete ===\n";
?>