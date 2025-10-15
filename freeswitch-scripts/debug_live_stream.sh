#!/bin/bash

echo "🔍 Debugging Live Audio Stream..."
echo "=================================="

# Check if named pipe exists
echo "1. Checking named pipe:"
if [ -e /tmp/live_audio.pcm ]; then
    echo "   ✅ Named pipe exists: /tmp/live_audio.pcm"
    ls -la /tmp/live_audio.pcm
else
    echo "   ❌ Named pipe NOT found: /tmp/live_audio.pcm"
    echo "   Creating it now..."
    mkfifo /tmp/live_audio.pcm
    chmod 666 /tmp/live_audio.pcm
    echo "   ✅ Created: /tmp/live_audio.pcm"
fi

echo ""
echo "2. Testing pipe write/read:"
echo "   Writing test data to pipe..."
echo "test audio data" > /tmp/live_audio.pcm &
sleep 1
echo "   Reading from pipe..."
timeout 2 cat /tmp/live_audio.pcm || echo "   ⚠️ Pipe read timeout (this is normal for FIFO)"

echo ""
echo "3. Checking FreeSWITCH local_stream configuration:"
echo "   Looking for local_stream.conf.xml..."
if [ -f /usr/local/freeswitch/conf/autoload_configs/local_stream.conf.xml ]; then
    echo "   ✅ Found: /usr/local/freeswitch/conf/autoload_configs/local_stream.conf.xml"
    grep -A 5 -B 5 "live_stream\|live_audio" /usr/local/freeswitch/conf/autoload_configs/local_stream.conf.xml || echo "   ⚠️ No live_stream configuration found"
elif [ -f /opt/freeswitch/conf/autoload_configs/local_stream.conf.xml ]; then
    echo "   ✅ Found: /opt/freeswitch/conf/autoload_configs/local_stream.conf.xml"
    grep -A 5 -B 5 "live_stream\|live_audio" /opt/freeswitch/conf/autoload_configs/local_stream.conf.xml || echo "   ⚠️ No live_stream configuration found"
else
    echo "   ❌ local_stream.conf.xml not found in standard locations"
    echo "   You may need to create it manually"
fi

echo ""
echo "4. Checking Node.js bridge process:"
ps aux | grep -i "node.*bridge\|bridge.*node" | grep -v grep || echo "   ⚠️ Node.js bridge process not found"

echo ""
echo "5. Checking FreeSWITCH process:"
ps aux | grep -i freeswitch | grep -v grep || echo "   ⚠️ FreeSWITCH process not found"

echo ""
echo "6. Checking WebSocket connection:"
netstat -an | grep :7001 || echo "   ⚠️ No process listening on port 7001"

echo ""
echo "=================================="
echo "🔧 Next steps:"
echo "1. If pipe doesn't exist, run: mkfifo /tmp/live_audio.pcm && chmod 666 /tmp/live_audio.pcm"
echo "2. If local_stream.conf.xml is missing, create it with live_stream directory"
echo "3. Restart Node.js bridge: cd node-voice-agent && npm start"
echo "4. Check FreeSWITCH logs for local_stream errors"
