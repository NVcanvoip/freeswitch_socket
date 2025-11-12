# JSON Messaging Reference for `fs_custom_v2`

This guide explains how `fs_custom_v2` exchanges JSON control messages with the remote WebSocket service. It focuses exclusively on structured messages (non-audio) that accompany each call.

## 1. Handshake and Readiness

1. **Deepgram sends `ready_to_greet`.**
   ```json
   {
     "type": "ready_to_greet",
     "timestamp": 1700000000
   }
   ```
   *Marks the point when the remote assistant is ready to start the conversation.*

2. **We reply with an acknowledgment.**
   ```json
   {
     "type": "acknowledgment",
     "command": "ready_to_greet",
     "command_timestamp": 1700000000,
     "status": "success",
     "timestamp": 1700000001
   }
   ```
   *Unlocks audio streaming and resolves the pending readiness promise. Duplicate acknowledgments are suppressed.*

3. **Optional `response` payloads follow.**
   ```json
   {
     "response": {
       "type": "transcript",
       "channel": { "alternatives": [ { "transcript": "Hello" } ] },
       "is_final": true
     }
   }
   ```
   *These are speech-to-text updates. They are logged for visibility but do not require a reply.*

## 2. Command Messages We Receive

| Command `type` | Purpose | Key fields | Result |
| --- | --- | --- | --- |
| `clear` | Flush any buffered outbound audio frames. | `timestamp` (optional) | Clears the queue and returns an acknowledgment with a custom message. |
| `transfer` | Move the caller to another destination. | `timestamp`, `destination`, optional routing overrides (e.g., timeouts, external gateway hints). | Validates routing data, pauses streaming, initiates the bridge, notifies Deepgram of progress via acknowledgments, and sends a final `hangup` event if the session is moving away. |
| `end_call` | Request an immediate hangup. | `timestamp` (optional) | Drains pending audio, acknowledges the request, hangs up the call leg, and performs cleanup. |

Each command is answered with the standard acknowledgment envelope shown below. Errors (such as missing destinations) change the `status` to `error` and include a diagnostic message.

```json
{
  "type": "acknowledgment",
  "command": "transfer",
  "command_timestamp": 1700000002,
  "status": "error",
  "message": "missing destination",
  "timestamp": 1700000003
}
```

## 3. Outbound Notifications We Initiate

### 3.1 Call Lifecycle Events

The channel reports key state changes to the WebSocket peer. Duplicate events are suppressed.

- **Call answered**
  ```json
  {
    "type": "call_event",
    "event": "answered",
    "timestamp": 1700000001
  }
  ```

- **Call hangup**
  ```json
  {
    "type": "call_event",
    "event": "hangup",
    "reason": "NORMAL_CLEARING",
    "timestamp": 1700000100
  }
  ```
  When a transfer succeeds, the hangup event is sent before the Deepgram socket closes so the assistant knows the original session is ending.

### 3.2 Administrative Acknowledgments

All acknowledgments share the same structure:

```json
{
  "type": "acknowledgment",
  "command": "clear",
  "command_timestamp": 1700000005,
  "status": "success",
  "message": "custom message",
  "timestamp": 1700000006
}
```

- `command` mirrors the incoming request type.
- `command_timestamp` echoes the sender’s timestamp when available; otherwise, the server fills it with the receipt time.
- `status` is `success` unless a validation or execution error occurred.
- `message` is optional and is used for human-readable diagnostics.

## 4. Typical Call Timeline

1. FreeSWITCH connects to the remote service and waits for `ready_to_greet`.
2. The server acknowledges readiness and begins forwarding PCM frames once the acknowledgment is sent.
3. Throughout the call, the assistant may send interim `response` transcripts. These are informational only.
4. If the assistant needs the call leg to change state, it sends `clear`, `transfer`, or `end_call`. Each receives an acknowledgment detailing the outcome.
5. The channel emits `call_event` messages for `answered` and `hangup` so the assistant can align its internal state with FreeSWITCH.
6. When the session ends—either via `end_call`, a transfer, or a hangup initiated by FreeSWITCH—cleanup stops audio streaming and closes the WebSocket.

This reference should equip integrators with the payload formats and sequencing rules required to script or troubleshoot the JSON layer of `fs_custom_v2`.
