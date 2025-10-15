<?php


//DB settings
define("HOST", "freeswitch.clthqqzsvi0y.us-west-2.rds.amazonaws.com");     // The host you want to connect to.


define("USER", "ct_system");    // The database username.
define("PASSWORD", "FE32ffahrwqu934jw0451u8ainbODETjt");    // The database password.
define("DATABASE", "ctpbx");    // The database name.

// DB Read-Only on port 4008
define("HOSTRO", "freeswitch.clthqqzsvi0y.us-west-2.rds.amazonaws.com");


// DB Proxy access
define("HOSTPROXY", "freeswitch.clthqqzsvi0y.us-west-2.rds.amazonaws.com");     // The host you want to connect to.
define("USERPROXY", "ct_system");    // The database username.
define("PASSWORDPROXY", "FE32ffahrwqu934jw0451u8ainbODETjt");    // The database password.
define("DATABASEPROXY", "proxy3");    // The database name.


define("VTPBX_IN_PROXY", "54.184.27.79");
define("VTPBX_PROXY_SIP_PORT", "5060");


define("VTPBX_OUT_PROXY", "54.184.27.79");  // can be the same

// Domain name
define("PBX_DOMAIN_NAME", ".callertech.net");


define("TIMEOUT_VM", "90");  // default timeout before transferring the simple call to a Voicemail
define("TIMEOUT_CALL", "80");  // default timeout for external number forwarding

define("IVR_DELAY", "2000");  // default timeout for external number forwarding


// Application server ID
define("APP_SRV_ID", "1");

// S3 for call recordings

// CallerTechnologies API specific details
define("CT_POST_CDR_URL", "https://callertech.com/client_api/call_status");
define("CT_POST_CALL_URL", "https://callertech.com/client_api/lookup_number");
define("CT_API_TOKEN", "dQIfVVujDdS7URIbgo8U6R73dO8EDwc3bR5s8HQnl7n2pIMgqD");

define("CTPBX_API_GENERIC_TOKEN", "dQIfVVujDdS7URIbgo8U6R73dO8EDwc3bR5s8HQnl7n2pIMgqD");

define("CT_API_WEBHOOK_TYPE_CDRS", 1);
define("CT_API_WEBHOOK_TYPE_INCOMING_CALLS", 2);





define("SECURE", FALSE);    // FALSE = SSL disabled,   TRUE = SSL enabled.



// folder for ZIP files storage (temporary)
define("UPLOAD_TEMP_FOLDER", "/tmp/");

define("PBX_IVR_FILES_BASE", "/opt/ctpbx/ivrs/");
define("PBX_RECORDING_FILES_BASE", "/opt/ctpbx/recordings");
define("PBX_GATEWAY_FILES_BASE", "/opt/ctpbx/gateways/");
define("PBX_VOICEMAIL_FILES_BASE", "/opt/ctpbx/voicemail/");


// Default IVR files for various actions:

define("PBX_IVR_DEFAULT_WHISPER", "/opt/ctpbx/ivrs/whisper_default.wav");







// other settings
// Logger minimum level (0 - log everything, 10 - log only critical errors)
define("LOGGER_MIN_LEVEL",0);



// DEFINITIONS OF COMMON PARAMETERS
define("LOG_SYSTEM",0);
define("LOG_ADMIN_PANEL",1);
define("LOG_SERVICES",2);
define("LOG_MONITORING",3);
define("LOG_CDR_ENGINE",4);
define("LOG_API",5);
define("LOG_FS_CURL",6);




