#!/bin/bash

# Audio Event Monitor Startup Script
# This script starts both the FreeSWITCH event capture and PHP monitoring

# Get the directory where this script is located
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Function to log messages with timestamp
log_message() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1"
}

log_message "Starting Audio Event Monitor..."

# Start FreeSWITCH CLI to capture custom audio events
log_message "Starting FreeSWITCH CLI event capture..."
( echo "/events plain CUSTOM mod_audio_stream::play"; tail -f /dev/null ) | fs_cli -q -R >> /tmp/custom_events.log 2>&1 &
FS_CLI_PID=$!

# Give fs_cli a moment to start
sleep 2

# Start the PHP monitoring script
log_message "Starting PHP log monitor..."
cd "$SCRIPT_DIR"
php log_monitor_audio_events.php >> /tmp/audio_monitor.log 2>&1 &
PHP_PID=$!

log_message "Audio Event Monitor started successfully!"
log_message "FreeSWITCH CLI PID: $FS_CLI_PID"
log_message "PHP Monitor PID: $PHP_PID"
log_message "Custom events log: /tmp/custom_events.log"
log_message "PHP monitor log: /tmp/audio_monitor.log"

# Create a PID file to track the processes
echo "$FS_CLI_PID" > /tmp/audio_monitor_fs_cli.pid
echo "$PHP_PID" > /tmp/audio_monitor_php.pid

log_message "PID files created in /tmp/"
log_message "To stop the monitor, run: kill $FS_CLI_PID $PHP_PID"

# Wait for both processes (optional - remove if you want the script to exit immediately)
wait
