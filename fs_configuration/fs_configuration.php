<?php
/**


 */

$dir = __DIR__ ;
$dir = str_replace("fs_configuration","",$dir);
require_once $dir . "/settings.php";
require_once $dir . "/db_connect.php";
require_once $dir . "/functions.php";

require_once $dir . "/admin_panel/engine/functions_admin_portal.php";

require_once "functions_vtpbx_fs.php";
require_once "fs_configuration_functions.php";


// Whatever will happen the response will be in text/xml format
header( 'Content-Type: text/xml' );



// LOG request:
//error_log("-=-=-=- CONFIGURATION -=-=-=-=-=-=- : ".json_encode($_REQUEST));

//  ================================= REQUEST =================================
/*

{
   "hostname":"vtpbx-fs",
   "section":"configuration",
   "tag_name":"configuration",
   "key_name":"name",
   "key_value":"ivr.conf",
   "Event-Name":"REQUEST_PARAMS",
   "Core-UUID":"624ba469-60cb-46f2-bdc5-49110a7db37a",
   "FreeSWITCH-Hostname":"vtpbx-fs",
   "FreeSWITCH-Switchname":"vtpbx-fs",
   "FreeSWITCH-IPv4":"209.97.147.156",
   "FreeSWITCH-IPv6":"::1",
   "Event-Date-Local":"2019-05-08 21:28:04",
   "Event-Date-GMT":"Wed, 08 May 2019 21:28:04 GMT",
   "Event-Date-Timestamp":"1557350884351127",
   "Event-Calling-File":"mod_dptools.c",
   "Event-Calling-Function":"ivr_application_function",
   "Event-Calling-Line-Number":"2095",
   "Event-Sequence":"552",
   "Menu-Name":"d1.vtpbx.com-IVR-1",
   "Channel-State":"CS_EXECUTE",
   "Channel-Call-State":"ACTIVE",
   "Channel-State-Number":"4",
   "Channel-Name":"sofia\\/internal\\/7162684897@162.212.218.137",
   "Unique-ID":"35ce1865-899d-451c-beeb-60e922b2ffce",
   "Call-Direction":"inbound",
   "Presence-Call-Direction":"inbound",
   "Channel-HIT-Dialplan":"true",
   "Channel-Presence-ID":"7162684897@162.212.218.137",
   "Channel-Call-UUID":"35ce1865-899d-451c-beeb-60e922b2ffce",
   "Answer-State":"answered",
   "Channel-Read-Codec-Name":"PCMU",
   "Channel-Read-Codec-Rate":"8000",
   "Channel-Read-Codec-Bit-Rate":"64000",
   "Channel-Write-Codec-Name":"PCMU",
   "Channel-Write-Codec-Rate":"8000",
   "Channel-Write-Codec-Bit-Rate":"64000",
   "Caller-Direction":"inbound",
   "Caller-Logical-Direction":"inbound",
   "Caller-Username":"7162684897",
   "Caller-Dialplan":"XML",
   "Caller-Caller-ID-Name":"7162684897",
   "Caller-Caller-ID-Number":"7162684897",
   "Caller-Orig-Caller-ID-Name":"7162684897",
   "Caller-Orig-Caller-ID-Number":"7162684897",
   "Caller-Network-Addr":"138.197.105.36",
   "Caller-ANI":"7162684897",
   "Caller-Destination-Number":"17162688707",
   "Caller-Unique-ID":"35ce1865-899d-451c-beeb-60e922b2ffce",
   "Caller-Source":"mod_sofia",
   "Caller-Context":"default",
   "Caller-Channel-Name":"sofia\\/internal\\/7162684897@162.212.218.137",
   "Caller-Profile-Index":"1",
   "Caller-Profile-Created-Time":"1557350884331142",
   "Caller-Channel-Created-Time":"1557350884331142",
   "Caller-Channel-Answered-Time":"1557350884351127",
   "Caller-Channel-Progress-Time":"0",
   "Caller-Channel-Progress-Media-Time":"1557350884351127",
   "Caller-Channel-Hangup-Time":"0",
   "Caller-Channel-Transfer-Time":"0",
   "Caller-Channel-Resurrect-Time":"0",
   "Caller-Channel-Bridged-Time":"0",
   "Caller-Channel-Last-Hold":"0",
   "Caller-Channel-Hold-Accum":"0",
   "Caller-Screen-Bit":"true",
   "Caller-Privacy-Hide-Name":"false",
   "Caller-Privacy-Hide-Number":"false",
   "variable_direction":"inbound",
   "variable_uuid":"35ce1865-899d-451c-beeb-60e922b2ffce",
   "variable_session_id":"1",
   "variable_sip_from_user":"7162684897",
   "variable_sip_from_uri":"7162684897@162.212.218.137",
   "variable_sip_from_host":"162.212.218.137",
   "variable_video_media_flow":"disabled",
   "variable_text_media_flow":"disabled",
   "variable_channel_name":"sofia\\/internal\\/7162684897@162.212.218.137",
   "variable_sip_call_id":"0163ca1d-ec7b-1237-d785-afc1ef59ca02",
   "variable_sip_local_network_addr":"209.97.147.156",
   "variable_sip_network_ip":"138.197.105.36",
   "variable_sip_network_port":"50600",
   "variable_sip_invite_stamp":"1557350884331142",
   "variable_sip_received_ip":"138.197.105.36",
   "variable_sip_received_port":"50600",
   "variable_sip_via_protocol":"udp",
   "variable_sip_authorized":"true",
   "variable_sip_acl_authed_by":"domains",
   "variable_sip_from_user_stripped":"7162684897",
   "variable_sip_from_tag":"g468KrS8j4vva",
   "variable_sofia_profile_name":"internal",
   "variable_sofia_profile_url":"sip:mod_sofia@209.97.147.156:5566",
   "variable_recovery_profile_name":"internal",
   "variable_sip_Remote-Party-ID":"\\"   7162684897   \\" <sip:7162684897   @162.212.218.137>;party=calling;screen=yes;privacy=off",
   "variable_sip_cid_type":"rpid",
   "variable_sip_invite_route_uri":"<sip:138.197.105.36:50600;lr>,<sip:162.212.218.52;lr=on;ftag=g468KrS8j4vva;did=88dc.d7a61>",
   "variable_sip_invite_record_route":"<sip:162.212.218.52;lr=on;ftag=g468KrS8j4vva;did=88dc.d7a61>,<sip:138.197.105.36:50600;lr>",
   "variable_sip_full_via":"SIP\\/2.0\\/UDP 138.197.105.36:50600;branch=z9hG4bKc38c.b3f69383.0;rport=50600,SIP\\/2.0\\/UDP 162.212.218.52;branch=z9hG4bKc38c.3d86b1e3b97f1a4bcebc76e1a6d9cca9.0,SIP\\/2.0\\/UDP 162.212.218.137:5004;received=162.212.218.137;rport=5004;branch=z9hG4bK79NUSBy30Qtcm",
   "variable_sip_from_display":"7162684897",
   "variable_sip_full_from":"\\"   7162684897   \\" <sip:7162684897   @162.212.218.137>;tag=g468KrS8j4vva",
   "variable_sip_full_to":"<sip:17162688707@138.197.105.36:50600>",
   "variable_sip_allow":"INVITE, ACK, BYE, CANCEL, OPTIONS, MESSAGE, INFO, UPDATE, NOTIFY",
   "variable_sip_req_user":"17162688707",
   "variable_sip_req_port":"50600",
   "variable_sip_req_uri":"17162688707@138.197.105.36:50600",
   "variable_sip_req_host":"138.197.105.36",
   "variable_sip_to_user":"17162688707",
   "variable_sip_to_port":"50600",
   "variable_sip_to_uri":"17162688707@138.197.105.36:50600",
   "variable_sip_to_host":"138.197.105.36",
   "variable_sip_contact_user":"AlcazarNetSIP",
   "variable_sip_contact_port":"5004",
   "variable_sip_contact_uri":"AlcazarNetSIP@162.212.218.137:5004",
   "variable_sip_contact_host":"162.212.218.137",
   "variable_sip_user_agent":"AlcazarSBC 1.40",
   "variable_sip_via_host":"138.197.105.36",
   "variable_sip_via_port":"50600",
   "variable_sip_via_rport":"50600",
   "variable_max_forwards":"66",
   "variable_presence_id":"7162684897@162.212.218.137",
   "variable_sip_h_X-VT-DIDPROVIDER":"3",
   "variable_sip_h_X-VT-CUSTOMER":"1",
   "variable_sip_h_x-media":"false",
   "variable_sip_h_x-timers":"true",
   "variable_switch_r_sdp":"v=0\\r\\no=FreeSWITCH 1557300796 1557300797 IN IP4 162.212.218.137\\r\\ns=FreeSWITCH\\r\\nc=IN IP4 162.212.218.137\\r\\nt=0 0\\r\\nm=audio 50088 RTP\\/AVP 0 18 101 13\\r\\na=rtpmap:0 PCMU\\/8000\\r\\na=rtpmap:18 G729\\/8000\\r\\na=fmtp:18 annexb=no\\r\\na=rtpmap:101 telephone-event\\/8000\\r\\na=fmtp:101 0-16\\r\\na=ptime:20\\r\\n",
   "variable_ep_codec_string":"CORE_PCM_MODULE.PCMU@8000h@20i@64000b",
   "variable_call_uuid":"35ce1865-899d-451c-beeb-60e922b2ffce",
   "variable_vtpbx_destination_def":"1",
   "variable_vtpbx_customer_id":"1",
   "variable_rtp_use_codec_string":"OPUS,G722,PCMU,PCMA,VP8",
   "variable_remote_audio_media_flow":"sendrecv",
   "variable_audio_media_flow":"sendrecv",
   "variable_rtp_audio_recv_pt":"0",
   "variable_rtp_use_codec_name":"PCMU",
   "variable_rtp_use_codec_rate":"8000",
   "variable_rtp_use_codec_ptime":"20",
   "variable_rtp_use_codec_channels":"1",
   "variable_rtp_last_audio_codec_string":"PCMU@8000h@20i@1c",
   "variable_read_codec":"PCMU",
   "variable_original_read_codec":"PCMU",
   "variable_read_rate":"8000",
   "variable_original_read_rate":"8000",
   "variable_write_codec":"PCMU",
   "variable_write_rate":"8000",
   "variable_dtmf_type":"rfc2833",
   "variable_local_media_ip":"209.97.147.156",
   "variable_local_media_port":"23246",
   "variable_advertised_media_ip":"209.97.147.156",
   "variable_rtp_use_timer_name":"soft",
   "variable_rtp_use_pt":"0",
   "variable_rtp_use_ssrc":"2094406924",
   "variable_rtp_2833_send_payload":"101",
   "variable_rtp_2833_recv_payload":"101",
   "variable_remote_media_ip":"162.212.218.137",
   "variable_remote_media_port":"50088",
   "variable_rtp_local_sdp_str":"v=0\\r\\no=FreeSWITCH 1557327638 1557327639 IN IP4 209.97.147.156\\r\\ns=FreeSWITCH\\r\\nc=IN IP4 209.97.147.156\\r\\nt=0 0\\r\\nm=audio 23246 RTP\\/AVP 0 101\\r\\na=rtpmap:0 PCMU\\/8000\\r\\na=rtpmap:101 telephone-event\\/8000\\r\\na=fmtp:101 0-16\\r\\na=ptime:20\\r\\na=sendrecv\\r\\n",
   "variable_endpoint_disposition":"ANSWER",
   "variable_current_application_data":"d1.vtpbx.com-IVR-1",
   "variable_current_application":"ivr"
}


*/


if (!is_array($_REQUEST)) {
    trigger_error('$_REQUEST is not an array');
}

$context = "default";
if(isset($_REQUEST["Caller-Context"]))
    $context = test_input($_REQUEST["Caller-Context"]);


// ========-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=--=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=--=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-
$key_value = test_input($_REQUEST["key_value"]);

switch($key_value){
    // ========-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-
    case "ivr.conf":{

        $ivr_menu_name = test_input($_REQUEST["Menu-Name"]);
        $DIDproviderID = test_input($_REQUEST["variable_sip_h_X-VT-DIDPROVIDER"]);
        $customerID = test_input($_REQUEST["variable_vtpbx_customer_id"]);

        $destination = test_input($_REQUEST["Caller-Destination-Number"]);
        $ivr_menu_id = test_input($_REQUEST["variable_vtpbx_destination_def"]);



        error_log("-=-=-=- CONFIGURATION for [IVR], customer: [$customerID], IVR menu name: [$ivr_menu_name], ivr_menu ID [$ivr_menu_id] ");
        // let's prepare and echo the configuration of ALL IVR scenarios of Customer $customerID


        serveConfigurationOfIVRscenariosForOneClient($customerID, $destination, $ivr_menu_name, $ivr_menu_id, $mysqli);











        //exit at the end
        exit;
    }break;


    // ========-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-
    default :{



        //exit at the end
        exit;
    }break;






}


exit;




