#!/bin/bash

# Setup script for live audio streaming via named pipe

echo "Setting up live audio streaming..."

# Create the named pipe (FIFO) for live audio
echo "Creating named pipe: /tmp/live_audio.pcm"
mkfifo /tmp/live_audio.pcm

# Set proper permissions
chmod 666 /tmp/live_audio.pcm

# Create backup of original pipe if it exists
if [ -e /tmp/live_audio.pcm.backup ]; then
    echo "Backup already exists"
else
    echo "Creating backup of original pipe"
    cp /tmp/live_audio.pcm /tmp/live_audio.pcm.backup 2>/dev/null || true
fi

echo "✅ Named pipe created: /tmp/live_audio.pcm"
echo "✅ Permissions set: 666"
echo ""
echo "Next steps:"
echo "1. Update FreeSWITCH dialplan to use local_stream://live_audio.pcm"
echo "2. Restart Node.js bridge with live streaming enabled"
echo ""
echo "To test the pipe:"
echo "  echo 'test audio data' > /tmp/live_audio.pcm"
echo "  cat /tmp/live_audio.pcm"
