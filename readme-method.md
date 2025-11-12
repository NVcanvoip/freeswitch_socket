# JSON Message Reference for `fs.js`

This document describes how `fs.js` exchanges control JSON messages with the remote WebSocket service. Only structured (non-audio) messages that accompany each call are covered here.

## 1. Handshake and Readiness

1. **Deepgram sends `ready_to_greet`.**
   ```json
   {
     "type": "ready_to_greet",
     "timestamp": 1700000000
   }
   ```
   *Indicates that the remote assistant is ready to start the conversation.*

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
   *Unlocks the audio stream and ends the readiness wait. Duplicate acknowledgments are not sent.*


## 2. Commands We Accept

| Command `type` | Purpose | Key Fields | Result |
| --- | --- | --- | --- |
| `clear` | Flush the buffer of outgoing audio frames. | `timestamp` | Clears the queue and sends an acknowledgment with an optional message. |
| `transfer` | Transfer the caller to another number. | `timestamp`, `destination`, additional routing parameters. | Validates the data, pauses streaming, starts the bridge, notifies Deepgram with acknowledgments, and sends `hangup`. |
| `end_call` | Request immediate call termination. | `timestamp` | Drains remaining audio, sends an acknowledgment, ends the call, and performs cleanup. |

Every command receives the standard acknowledgment envelope shown below. On errors (for example, no destination) the `status` field becomes `error`, and `message` carries the diagnostic text.

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

## 3. Outgoing Notifications

### 3.1 Call Lifecycle Events

The channel informs the remote side about key state changes. Duplicates are not allowed.

- **Call answered**
  ```json
  {
    "type": "call_event",
    "event": "answered",
    "timestamp": 1700000001
  }
  ```

- **Call finished**
  ```json
  {
    "type": "call_event",
    "event": "hangup",
    "reason": "NORMAL_CLEARING",
    "timestamp": 1700000100
  }
  ```
  On a successful transfer, the `hangup` is sent before the WebSocket closes so the assistant knows the original session ended.

### 3.2 Administrative Acknowledgments

All acknowledgments share a common structure:

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

- `command` repeats the received command type.
- `command_timestamp` copies the sender's timestamp.
- `status` equals `success` when validation and execution complete without errors.
- `message` is optional and used for human-readable comments.

## 4. Typical Call Timeline

1. FreeSWITCH connects to the remote service and waits for `ready_to_greet`.
2. After acknowledging readiness, PCM frames start flowing.
3. To change the call state the assistant sends `clear`, `transfer`, or `end_call`. Each message receives an acknowledgment with the outcome.
4. The channel sends `call_event` notifications for `answered` and `hangup` to keep the assistant's state in sync.
5. When the session ends—via `end_call`, a transfer, or an internal FreeSWITCH `hangup`—cleanup is performed, the audio stream stops, and the WebSocket closes.
