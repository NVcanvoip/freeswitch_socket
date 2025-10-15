#!/bin/bash
# subscribe to mod_audio_stream::play and keep stdin alive
( echo "events plain CUSTOM mod_audio_stream::play"; cat ) | fs_cli -q -R

