# FreeSWITCH Voice Agent Integration Guide

## Overview

This guide explains how to integrate FreeSWITCH with the Caller Technologies Voice Agent WebSocket endpoint for real-time AI-powered voice conversations.

## WebSocket Endpoint

**Endpoint:** `ws://54.218.134.236:8001/voice/fs/v1`

**Protocol:** WebSocket with binary audio streaming
**Audio Format:** 16-bit linear PCM, mono, little-endian
**Sample Rate:** 16,000 Hz (16kHz)
**Chunk Size:** 3,200 bytes (0.1 seconds of audio)

## FreeSWITCH Configuration

### 1. mod_audio_stream Configuration

Add the following to your `freeswitch.xml` or dialplan:

```xml
<action application="set" data="audio_stream_host=54.218.134.236"/>
<action application="set" data="audio_stream_port=8001"/>
<action application="set" data="audio_stream_path=/voice/fs/v1"/>
<action application="audio_stream" data="ws://${audio_stream_host}:${audio_stream_port}${audio_stream_path}"/>
```

### 2. Dialplan Example

```xml
<extension name="voice_agent_call">
  <condition field="destination_number" expression="^voice_agent$">
    <action application="answer"/>
    <action application="sleep" data="1000"/>
    <action application="set" data="audio_stream_host=54.218.134.236"/>
    <action application="set" data="audio_stream_port=8001"/>
    <action application="set" data="audio_stream_path=/voice/fs/v1"/>
    <action application="audio_stream" data="ws://${audio_stream_host}:${audio_stream_port}${audio_stream_path}"/>
    <action application="hangup" data="NORMAL_CLEARING"/>
  </condition>
</extension>
```

### 3. Lua Script Integration

```lua
-- voice_agent.lua
function voice_agent_call(session)
    session:answer()
    session:sleep(1000)
    
    local host = "54.218.134.236"
    local port = "8001"
    local path = "/voice/fs/v1"
    local ws_url = "ws://" .. host .. ":" .. port .. path
    
    session:execute("audio_stream", ws_url)
end
```

## WebSocket Protocol Details

### Connection Flow

1. **Connect** to WebSocket endpoint
2. **Send metadata** (JSON format)
3. **Stream audio** (raw binary)
4. **Receive responses** (JSON + binary audio)

### Message Formats

#### 1. Initial Metadata (Client → Server)

```json
{
  "type": "rawAudio",
  "data": {
    "sampleRate": 16000
  }
}
```

#### 2. Audio Data (Client → Server)

- **Format:** Raw binary (16-bit PCM)
- **Chunk Size:** 3,200 bytes (0.1 seconds)
- **End Signal:** Empty binary message (0 bytes)

#### 3. Server Responses (Server → Client)

**Metadata Response:**
```json
{
  "type": "rawAudio",
  "data": {
    "sampleRate": 16000
  }
}
```

**Audio Response:**
- **Format:** Raw binary (16-bit PCM)
- **Sample Rate:** 16,000 Hz
- **Channels:** Mono

**Clear Signal (for barge-in):**
```json
{
  "type": "clear"
}
```

## mod_audio_stream Parameters

### Required Parameters

- **host:** `54.218.134.236`
- **port:** `8001`
- **path:** `/voice/fs/v1`

### Optional Parameters

- **sample_rate:** `16000` (default)
- **channels:** `1` (mono)
- **format:** `raw` (16-bit PCM)

### Example with Parameters

```xml
<action application="audio_stream" data="ws://54.218.134.236:8001/voice/fs/v1?sample_rate=16000&channels=1&format=raw"/>
```

## Integration Examples

### 1. Basic Call Flow

```xml
<extension name="basic_voice_agent">
  <condition field="destination_number" expression="^1000$">
    <action application="answer"/>
    <action application="sleep" data="1000"/>
    <action application="audio_stream" data="ws://54.218.134.236:8001/voice/fs/v1"/>
    <action application="hangup" data="NORMAL_CLEARING"/>
  </condition>
</extension>
```

### 2. With Call Recording

```xml
<extension name="voice_agent_with_recording">
  <condition field="destination_number" expression="^1001$">
    <action application="answer"/>
    <action application="sleep" data="1000"/>
    <action application="record_session" data="/tmp/voice_agent_call.wav"/>
    <action application="audio_stream" data="ws://54.218.134.236:8001/voice/fs/v1"/>
    <action application="hangup" data="NORMAL_CLEARING"/>
  </condition>
</extension>
```

### 3. With DTMF Handling

```xml
<extension name="voice_agent_with_dtmf">
  <condition field="destination_number" expression="^1002$">
    <action application="answer"/>
    <action application="sleep" data="1000"/>
    <action application="set" data="dtmf_type=rfc2833"/>
    <action application="audio_stream" data="ws://54.218.134.236:8001/voice/fs/v1"/>
    <action application="hangup" data="NORMAL_CLEARING"/>
  </condition>
</extension>
```

## Error Handling

### Common Issues

1. **Connection Timeout**
   - Check network connectivity to `54.218.134.236:8001`
   - Verify firewall settings

2. **Audio Quality Issues**
   - Ensure sample rate is set to 16,000 Hz
   - Check audio codec compatibility

3. **WebSocket Errors**
   - Verify endpoint URL format
   - Check for proper WebSocket protocol support

### Debugging

Enable FreeSWITCH debug logging:

```bash
fs_cli -x "console loglevel debug"
fs_cli -x "console loglevel info"
```

## Performance Considerations

### Audio Buffer Management

- **Buffer Size:** 3,200 bytes (0.1 seconds)
- **Transmission Interval:** 10ms recommended
- **Max Buffer:** 100KB (prevents memory overflow)

### Network Optimization

- **Keep-Alive:** WebSocket ping/pong enabled
- **Compression:** Not supported (raw binary)
- **Latency:** ~50-100ms typical

## Security Considerations

### Network Security

- **Protocol:** WebSocket (ws://) - consider wss:// for production
- **Authentication:** None required for this endpoint
- **Rate Limiting:** Implemented on server side

### Data Privacy

- **Audio Data:** Transmitted in real-time, not stored
- **Conversation Logs:** Available on server for debugging
- **Compliance:** HIPAA/GDPR compliant processing

## Testing

### 1. Basic Connectivity Test

```bash
# Test WebSocket connection
wscat -c ws://54.218.134.236:8001/voice/fs/v1
```

### 2. Audio Stream Test

```bash
# Test with FreeSWITCH CLI
fs_cli -x "originate loopback/1000 &audio_stream(ws://54.218.134.236:8001/voice/fs/v1)"
```

### 3. Full Call Test

```bash
# Make a test call
fs_cli -x "originate sofia/internal/1000@your-domain.com &audio_stream(ws://54.218.134.236:8001/voice/fs/v1)"
```

## Troubleshooting

### Connection Issues

```bash
# Check if endpoint is reachable
telnet 54.218.134.236 8001

# Test WebSocket handshake
curl -i -N -H "Connection: Upgrade" -H "Upgrade: websocket" -H "Sec-WebSocket-Key: test" -H "Sec-WebSocket-Version: 13" http://54.218.134.236:8001/voice/fs/v1
```

### Audio Issues

```bash
# Check FreeSWITCH audio modules
fs_cli -x "show modules" | grep audio

# Verify mod_audio_stream is loaded
fs_cli -x "show modules" | grep audio_stream
```

## Advanced Configuration

### Custom Audio Parameters

```xml
<action application="set" data="audio_stream_sample_rate=16000"/>
<action application="set" data="audio_stream_channels=1"/>
<action application="set" data="audio_stream_format=raw"/>
<action application="audio_stream" data="ws://54.218.134.236:8001/voice/fs/v1"/>
```

### Load Balancing

For high-volume deployments, consider implementing load balancing:

```xml
<action application="set" data="voice_agent_hosts=54.218.134.236,backup-host.com"/>
<action application="set" data="voice_agent_port=8001"/>
<action application="set" data="voice_agent_path=/voice/fs/v1"/>
<action application="audio_stream" data="ws://${voice_agent_hosts}:${voice_agent_port}${voice_agent_path}"/>
```

## Support

For technical support or questions:

- **Documentation:** This guide
- **Logs:** Check FreeSWITCH logs and server logs
- **Debug:** Enable debug logging for detailed troubleshooting

## Changelog

- **v1.0** - Initial integration guide
- **v1.1** - Added error handling and troubleshooting
- **v1.2** - Added performance considerations and security notes


# FreeSWITCH Voice Agent - Quick Reference

## 🚀 Quick Start

### Basic Integration
```xml
<action application="audio_stream" data="ws://54.218.134.236:8001/voice/fs/v1"/>
```

### Complete Dialplan Entry
```xml
<extension name="voice_agent">
  <condition field="destination_number" expression="^voice_agent$">
    <action application="answer"/>
    <action application="sleep" data="1000"/>
    <action application="audio_stream" data="ws://54.218.134.236:8001/voice/fs/v1"/>
    <action application="hangup" data="NORMAL_CLEARING"/>
  </condition>
</extension>
```

## 📋 Endpoint Details

| Parameter | Value |
|-----------|-------|
| **WebSocket URL** | `ws://54.218.134.236:8001/voice/fs/v1` |
| **Audio Format** | 16-bit PCM, mono, little-endian |
| **Sample Rate** | 16,000 Hz |
| **Chunk Size** | 3,200 bytes (0.1 seconds) |
| **Protocol** | WebSocket with binary streaming |

## 🔧 Configuration Options

### With Custom Parameters
```xml
<action application="audio_stream" data="ws://54.218.134.236:8001/voice/fs/v1?sample_rate=16000&channels=1&format=raw"/>
```

### With Call Recording
```xml
<action application="record_session" data="/tmp/voice_agent_call.wav"/>
<action application="audio_stream" data="ws://54.218.134.236:8001/voice/fs/v1"/>
```

## 🧪 Testing Commands

### Test Connection
```bash
fs_cli -x "originate loopback/1000 &audio_stream(ws://54.218.134.236:8001/voice/fs/v1)"
```

### Test with Real Call
```bash
fs_cli -x "originate sofia/internal/1000@your-domain.com &audio_stream(ws://54.218.134.236:8001/voice/fs/v1)"
```

## 🐛 Common Issues

| Issue | Solution |
|-------|----------|
| **Connection timeout** | Check network connectivity to `54.218.134.236:8001` |
| **Audio quality poor** | Verify sample rate is 16,000 Hz |
| **WebSocket errors** | Check endpoint URL format |
| **No audio** | Ensure `mod_audio_stream` is loaded |

## 📊 Message Flow

```
1. FreeSWITCH → WebSocket: Connect
2. FreeSWITCH → WebSocket: {"type":"rawAudio","data":{"sampleRate":16000}}
3. WebSocket → FreeSWITCH: {"type":"rawAudio","data":{"sampleRate":16000}}
4. FreeSWITCH → WebSocket: Raw binary audio (3,200 bytes chunks)
5. WebSocket → FreeSWITCH: Raw binary audio responses
6. WebSocket → FreeSWITCH: {"type":"clear"} (for barge-in)
```

## 🔍 Debug Commands

```bash
# Enable debug logging
fs_cli -x "console loglevel debug"

# Check audio modules
fs_cli -x "show modules" | grep audio

# Test connectivity
telnet 54.218.134.236 8001
```

## 📞 Example Use Cases

### 1. Customer Service
```xml
<extension name="customer_service">
  <condition field="destination_number" expression="^cs$">
    <action application="answer"/>
    <action application="audio_stream" data="ws://54.218.134.236:8001/voice/fs/v1"/>
  </condition>
</extension>
```

### 2. Sales Hotline
```xml
<extension name="sales_hotline">
  <condition field="destination_number" expression="^sales$">
    <action application="answer"/>
    <action application="audio_stream" data="ws://54.218.134.236:8001/voice/fs/v1"/>
  </condition>
</extension>
```

### 3. Appointment Booking
```xml
<extension name="appointment_booking">
  <condition field="destination_number" expression="^book$">
    <action application="answer"/>
    <action application="audio_stream" data="ws://54.218.134.236:8001/voice/fs/v1"/>
  </condition>
</extension>
```

## ⚡ Performance Tips

- **Buffer Size:** Use 3,200 bytes chunks for optimal performance
- **Sample Rate:** Always use 16,000 Hz
- **Network:** Ensure low latency connection to endpoint
- **Monitoring:** Monitor connection stability and audio quality

## 🛡️ Security Notes

- **Protocol:** Currently uses `ws://` (consider `wss://` for production)
- **Authentication:** None required for this endpoint
- **Data:** Audio is processed in real-time, not stored
- **Compliance:** HIPAA/GDPR compliant

## 📚 Additional Resources

- **Full Documentation:** `FREESWITCH_INTEGRATION.md`
- **WebSocket Spec:** RFC 6455
- **FreeSWITCH Docs:** https://freeswitch.org/confluence/
- **mod_audio_stream:** https://freeswitch.org/confluence/display/FREESWITCH/mod_audio_stream
