# Voice Agent Metadata Integration Guide

## Overview

This guide explains how to send caller metadata when establishing WebSocket connections to the voice agent, allowing the AI to identify and personalize interactions with callers.

## Implementation Approaches

### 1. Query Parameters Method (Recommended)

The easiest way to send metadata is through URL query parameters in the WebSocket connection URL.

#### FreeSWITCH Configuration

The `serveWebsocketStreamingConfiguration()` function now automatically includes caller metadata:

```php
// Example generated URL:
ws://54.218.134.236:8001/voice/fs/v1?caller_id=15551234567&caller_name=John%20Doe&uuid=abc123&destination=18188673471&customer_id=123&domain_id=456&direction=inbound&network_addr=192.168.1.100&timestamp=1673888400
```

#### Metadata Fields Included

| Field | Description | Example |
|-------|-------------|---------|
| `caller_id` | Caller's phone number | `15551234567` |
| `caller_name` | Caller ID name | `John Doe` |
| `uuid` | FreeSWITCH channel UUID | `abc123-def456-ghi789` |
| `destination` | Called number | `18188673471` |
| `customer_id` | Customer ID in system | `123` |
| `domain_id` | Domain ID | `456` |
| `direction` | Call direction | `inbound`/`outbound` |
| `network_addr` | Caller's IP address | `192.168.1.100` |
| `timestamp` | Call timestamp | `1673888400` |

### 2. WebSocket Headers Method

You can also send metadata via custom WebSocket headers:

```php
// In FreeSWITCH configuration, you could add:
$headers = [
    'X-Caller-ID: ' . $caller_id_number,
    'X-Caller-Name: ' . $caller_id_name,
    'X-Customer-ID: ' . $customer_id,
    'X-Call-UUID: ' . $channel_uuid
];
```

### 3. Initial JSON Message Method

Send metadata as the first message after WebSocket connection:

```json
{
  "type": "caller_metadata",
  "data": {
    "caller_id": "15551234567",
    "caller_name": "John Doe",
    "uuid": "abc123-def456-ghi789",
    "destination": "18188673471",
    "customer_id": "123",
    "domain_id": "456",
    "direction": "inbound",
    "network_addr": "192.168.1.100",
    "timestamp": 1673888400
  }
}
```

## Voice Agent Server Implementation

### Parsing URL Parameters (Node.js Example)

```javascript
wss.on('connection', async (fsSocket, req) => {
  // Parse caller metadata from URL parameters
  const url = new URL(req.url || '', 'ws://localhost');
  const callerMetadata = {
    caller_id: url.searchParams.get('caller_id') || 'unknown',
    caller_name: url.searchParams.get('caller_name') || 'unknown',
    uuid: url.searchParams.get('uuid') || '',
    destination: url.searchParams.get('destination') || '',
    customer_id: url.searchParams.get('customer_id') || '',
    domain_id: url.searchParams.get('domain_id') || '',
    direction: url.searchParams.get('direction') || 'inbound',
    network_addr: url.searchParams.get('network_addr') || '',
    timestamp: url.searchParams.get('timestamp') || Date.now()
  };
  
  console.log('Caller metadata:', callerMetadata);
  
  // Use metadata to personalize AI agent configuration
  const personalizedPrompt = buildPersonalizedPrompt(callerMetadata);
  
  // Configure Deepgram agent with caller context
  dgAgent.configure({
    agent: {
      think: {
        prompt: personalizedPrompt
      }
    }
  });
});

function buildPersonalizedPrompt(metadata) {
  return `You are a phone assistant for CallerTechnologies. 
  The caller is ${metadata.caller_name} (${metadata.caller_id}). 
  They called ${metadata.destination}. 
  Customer ID: ${metadata.customer_id}. 
  Provide personalized assistance based on this context.`;
}
```

### Parsing Headers (Node.js Example)

```javascript
wss.on('connection', async (fsSocket, req) => {
  const callerMetadata = {
    caller_id: req.headers['x-caller-id'] || 'unknown',
    caller_name: req.headers['x-caller-name'] || 'unknown',
    customer_id: req.headers['x-customer-id'] || '',
    uuid: req.headers['x-call-uuid'] || ''
  };
  
  console.log('Caller metadata from headers:', callerMetadata);
});
```

## Advanced Usage Examples

### 1. Customer Database Lookup

```javascript
async function enrichCallerMetadata(metadata) {
  // Look up customer information
  const customer = await database.getCustomer(metadata.customer_id);
  const callerHistory = await database.getCallerHistory(metadata.caller_id);
  
  return {
    ...metadata,
    customer_name: customer.name,
    customer_tier: customer.tier,
    previous_calls: callerHistory.length,
    last_call_date: callerHistory[0]?.date,
    preferred_language: customer.language || 'en'
  };
}
```

### 2. Dynamic AI Personality

```javascript
function buildPersonalizedPrompt(metadata) {
  let prompt = "You are a phone assistant for CallerTechnologies.";
  
  if (metadata.customer_tier === 'premium') {
    prompt += " This is a premium customer - provide priority support.";
  }
  
  if (metadata.previous_calls > 5) {
    prompt += " This is a returning caller - be familiar and reference their history.";
  }
  
  if (metadata.direction === 'outbound') {
    prompt += " This is an outbound call - be proactive and sales-focused.";
  }
  
  return prompt;
}
```

### 3. Call Routing Based on Metadata

```javascript
function routeCall(metadata) {
  if (metadata.customer_tier === 'enterprise') {
    return 'enterprise_agent_prompt';
  } else if (metadata.destination.includes('support')) {
    return 'support_agent_prompt';
  } else if (metadata.destination.includes('sales')) {
    return 'sales_agent_prompt';
  }
  return 'default_agent_prompt';
}
```

## Security Considerations

1. **Sanitize Input**: Always validate and sanitize metadata before use
2. **Limit Sensitive Data**: Avoid sending sensitive information in URLs
3. **Use HTTPS/WSS**: Ensure encrypted connections in production
4. **Rate Limiting**: Implement rate limiting based on caller_id or customer_id

## Testing Your Implementation

### 1. Test WebSocket URL Generation

Call the voice agent number and check FreeSWITCH logs for the generated URL:

```bash
tail -f /var/log/freeswitch/freeswitch.log | grep "VOICE AGENT: WebSocket URL"
```

### 2. Test Voice Agent Reception

Monitor your voice agent server logs to see received metadata:

```bash
# Example log output:
Caller metadata: {
  caller_id: '15551234567',
  caller_name: 'John Doe',
  uuid: 'abc123-def456',
  destination: '18188673471',
  customer_id: '123'
}
```

### 3. Test AI Personalization

Make test calls with different caller IDs and verify the AI responds appropriately based on the metadata.

## Troubleshooting

### Common Issues

1. **URL Encoding**: Ensure special characters in names are properly encoded
2. **Empty Values**: Handle cases where metadata fields might be empty
3. **URL Length Limits**: Be aware of URL length restrictions (typically 2048 characters)
4. **WebSocket Connection Failures**: Check if metadata parsing errors cause connection issues

### Debug Commands

```bash
# Check FreeSWITCH logs for URL generation
grep "VOICE AGENT: WebSocket URL" /var/log/freeswitch/freeswitch.log

# Test WebSocket connection manually
curl -i -N -H "Connection: Upgrade" -H "Upgrade: websocket" -H "Sec-WebSocket-Key: test" -H "Sec-WebSocket-Version: 13" "ws://54.218.134.236:8001/voice/fs/v1?caller_id=test&caller_name=Test%20User"
```

## Integration Checklist

- [ ] FreeSWITCH configuration updated to include metadata in WebSocket URL
- [ ] Voice agent server modified to parse and use metadata
- [ ] AI prompts personalized based on caller information
- [ ] Logging implemented for debugging metadata flow
- [ ] Security measures implemented (input validation, sanitization)
- [ ] Testing completed with various caller scenarios
- [ ] Production monitoring set up for metadata processing

## Next Steps

1. **Database Integration**: Connect metadata to your customer database for richer context
2. **Analytics**: Track how metadata improves AI interactions
3. **A/B Testing**: Test different personalization strategies
4. **Scaling**: Optimize metadata processing for high call volumes
