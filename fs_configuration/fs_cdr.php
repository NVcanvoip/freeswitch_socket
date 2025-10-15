<?php
/**


 */

$dir = __DIR__ ;
$dir = str_replace("fs_configuration","",$dir);
require_once $dir . "/settings.php";
require_once $dir . "/db_connect.php";
require_once $dir . "/functions.php";


require_once "functions_vtpbx_fs.php";
require_once "fs_configuration_functions.php";
require_once "Parse_XML_CDR.php";
require_once "functions_s3.php";



// Whatever will happen the response will be in text/xml format
header( 'Content-Type: text/xml' );



$xml = new Parse_CDR_XML($_POST['cdr']);
$cdr=$xml->ReturnArray();



$direction = $cdr[""]["direction"];


//error_log(json_encode($cdr));


switch($direction){
    case "inbound": {

        /*

        {
   "":{
      "channel_data":"\\n    ",
      "state":"CS_REPORTING",
      "direction":"inbound",
      "state_number":"11",
      "flags":"0=1;1=1;3=1;38=1;39=1;41=1;44=1;49=1;54=1;76=1;96=1;113=1;114=1;123=1;160=1;165=1",
      "caps":"1=1;2=1;3=1;4=1;5=1;6=1;8=1;9=1",
      "call-stats":"\\n    ",
      "audio":"\\n      ",
      "inbound":"\\n        ",
      "raw_bytes":"21844",
      "media_bytes":"21844",
      "packet_count":"127",
      "media_packet_count":"127",
      "skip_packet_count":"67",
      "jitter_packet_count":"0",
      "dtmf_packet_count":"0",
      "cng_packet_count":"0",
      "flush_packet_count":"0",
      "largest_jb_size":"0",
      "jitter_min_variance":"92.20",
      "jitter_max_variance":"199.00",
      "jitter_loss_rate":"0.00",
      "jitter_burst_rate":"0.00",
      "mean_interval":"19.82",
      "flaw_total":"0",
      "quality_percentage":"100.00",
      "mos":"4.50",
      "outbound":"\\n        ",
      "rtcp_packet_count":"0",
      "rtcp_octet_count":"0"
   },
   "variables":{
      "direction":"inbound",
      "uuid":"44dc566a-3657-4fa8-872d-81839524157e",
      "session_id":"24",
      "sip_from_user":"102",
      "sip_from_uri":"102%40d1.vtpbx.com",
      "sip_from_host":"d1.vtpbx.com",
      "video_media_flow":"disabled",
      "text_media_flow":"disabled",
      "channel_name":"sofia\\/internal\\/102%40d1.vtpbx.com",
      "sip_local_network_addr":"209.97.147.156",
      "sip_network_ip":"138.197.105.36",
      "sip_network_port":"50600",
      "sip_invite_stamp":"1554461443064073",
      "sip_received_ip":"138.197.105.36",
      "sip_received_port":"50600",
      "sip_via_protocol":"udp",
      "sip_authorized":"true",
      "sip_acl_authed_by":"domains",
      "sip_from_user_stripped":"102",
      "sofia_profile_name":"internal",
      "sofia_profile_url":"sip%3Amod_sofia%40209.97.147.156%3A5566",
      "recovery_profile_name":"internal",
      "sip_invite_route_uri":"%3Csip%3A138.197.105.36%3A50600%3Blr%3E",
      "sip_invite_record_route":"%3Csip%3A138.197.105.36%3A50600%3Blr%3E",
      "sip_allow":"OPTIONS,%20SUBSCRIBE,%20NOTIFY,%20INVITE,%20ACK,%20CANCEL,%20BYE,%20REFER,%20INFO",
      "sip_req_user":"101",
      "sip_req_uri":"101%40d1.vtpbx.com",
      "sip_req_host":"d1.vtpbx.com",
      "sip_to_user":"101",
      "sip_to_uri":"101%40d1.vtpbx.com",
      "sip_to_host":"d1.vtpbx.com",
      "sip_contact_params":"rinstance%3Dc2b500ff2525ab79%3Btransport%3Dtcp",
      "sip_contact_user":"102",
      "sip_contact_port":"52795",
      "sip_contact_uri":"102%40188.137.44.25%3A52795",
      "sip_contact_host":"188.137.44.25",
      "sip_user_agent":"X-Lite%20release%205.5.0%20stamp%2097576",
      "sip_via_host":"138.197.105.36",
      "sip_via_port":"50600",
      "presence_id":"102%40d1.vtpbx.com",
      "switch_r_sdp":"v%3D0%0D%0Ao%3D-%201554461442898334%201%20IN%20IP4%20188.137.44.25%0D%0As%3DX-Lite%20release%205.5.0%20stamp%2097576%0D%0Ac%3DIN%20IP4%20188.137.44.25%0D%0At%3D0%200%0D%0Am%3Daudio%2054052%20RTP\\/AVP%209%208%20120%200%2084%20101%0D%0Aa%3Drtpmap%3A120%20opus\\/48000\\/2%0D%0Aa%3Dfmtp%3A120%20useinbandfec%3D1%3B%20usedtx%3D1%3B%20maxaveragebitrate%3D64000%0D%0Aa%3Drtpmap%3A84%20speex\\/16000%0D%0Aa%3Drtpmap%3A101%20telephone-event\\/8000%0D%0Aa%3Dfmtp%3A101%200-15%0D%0Aa%3Doldoip%3A192.168.1.60%0D%0Aa%3Doldcip%3A192.168.1.60%0D%0A",
      "ep_codec_string":"mod_spandsp.G722%408000h%4020i%4064000b,CORE_PCM_MODULE.PCMA%408000h%4020i%4064000b,mod_opus.opus%4048000h%4020i%402c,CORE_PCM_MODULE.PCMU%408000h%4020i%4064000b",
      "DP_MATCH":"101",
      "domain_name":"d1.vtpbx.com",
      "max_forwards":"68",
      "transfer_history":"1554461443%3Adf3debc5-cf1f-431d-ae1a-b07b0cb1a254%3Abl_xfer%3A101\\/d1.vtpbx.com\\/XML",
      "transfer_source":"1554461443%3Adf3debc5-cf1f-431d-ae1a-b07b0cb1a254%3Abl_xfer%3A101\\/d1.vtpbx.com\\/XML",
      "call_uuid":"44dc566a-3657-4fa8-872d-81839524157e",
      "RFC2822_DATE":"Fri,%2005%20Apr%202019%2010%3A50%3A43%20%2B0000",
      "dialed_extension":"101",
      "export_vars":"RFC2822_DATE,dialed_extension",
      "vtpbx_destination":"EXTENSION",
      "ringback":"%25(2000,4000,440,480)",
      "transfer_ringback":"local_stream%3A\\/\\/moh",
      "call_timeout":"30",
      "hangup_after_bridge":"true",
      "continue_on_fail":"true",
      "rtp_use_codec_string":"OPUS,G722,PCMU,PCMA,VP8",
      "remote_audio_media_flow":"sendrecv",
      "rtp_audio_recv_pt":"9",
      "rtp_use_codec_name":"G722",
      "rtp_use_codec_rate":"8000",
      "rtp_use_codec_ptime":"20",
      "rtp_use_codec_channels":"1",
      "rtp_last_audio_codec_string":"G722%408000h%4020i%401c",
      "original_read_codec":"G722",
      "original_read_rate":"16000",
      "write_codec":"G722",
      "write_rate":"16000",
      "dtmf_type":"rfc2833",
      "local_media_ip":"209.97.147.156",
      "local_media_port":"24992",
      "advertised_media_ip":"209.97.147.156",
      "rtp_use_timer_name":"soft",
      "rtp_use_pt":"9",
      "rtp_use_ssrc":"346686699",
      "rtp_2833_send_payload":"101",
      "rtp_2833_recv_payload":"101",
      "remote_media_ip":"188.137.44.25",
      "remote_media_port":"54052",
      "current_application_data":"sofia\\/external\\/101%40d1.vtpbx.com%3Bfs_path%3Dsip%3A138.197.105.36%3A50600",
      "current_application":"bridge",
      "originated_legs":"ba27d90e-f679-4b38-bb39-07b9d3448abf%3BOutbound%20Call%3B101",
      "switch_m_sdp":"v%3D0%0D%0Ao%3Dsdp_admin%202016111840%2068005313%20IN%20IP4%20192.168.1.40%0D%0As%3DA%20conversation%0D%0Ac%3DIN%20IP4%20192.168.1.40%0D%0At%3D0%200%0D%0Am%3Daudio%2010228%20RTP\\/AVP%209%20101%0D%0Aa%3Drtpmap%3A9%20G722\\/8000%0D%0Aa%3Drtpmap%3A101%20telephone-event\\/8000%0D%0Aa%3Dfmtp%3A101%200-15%0D%0A",
      "audio_media_flow":"sendrecv",
      "read_codec":"G722",
      "read_rate":"16000",
      "originate_disposition":"SUCCESS",
      "DIALSTATUS":"SUCCESS",
      "originate_causes":"ba27d90e-f679-4b38-bb39-07b9d3448abf%3BNONE",
      "rtp_local_sdp_str":"v%3D0%0D%0Ao%3DFreeSWITCH%201554436451%201554436453%20IN%20IP4%20209.97.147.156%0D%0As%3DFreeSWITCH%0D%0Ac%3DIN%20IP4%20209.97.147.156%0D%0At%3D0%200%0D%0Am%3Daudio%2024992%20RTP\\/AVP%209%20101%0D%0Aa%3Drtpmap%3A9%20G722\\/8000%0D%0Aa%3Drtpmap%3A101%20telephone-event\\/8000%0D%0Aa%3Dfmtp%3A101%200-16%0D%0Aa%3Dptime%3A20%0D%0Aa%3Dsendrecv%0D%0A",
      "endpoint_disposition":"ANSWER",
      "last_bridge_to":"ba27d90e-f679-4b38-bb39-07b9d3448abf",
      "bridge_channel":"sofia\\/external\\/101%40d1.vtpbx.com",
      "bridge_uuid":"ba27d90e-f679-4b38-bb39-07b9d3448abf",
      "signal_bond":"ba27d90e-f679-4b38-bb39-07b9d3448abf",
      "sip_to_tag":"p53ggg5NFB97m",
      "sip_from_tag":"c322083b",
      "sip_cseq":"2",
      "sip_call_id":"97576ZWRlZGM5MjZlZDM0MzY0MzFjYTZmNjAwOTY4NTY1Yjc",
      "sip_full_via":"SIP\\/2.0\\/UDP%20138.197.105.36%3A50600%3Bbranch%3Dz9hG4bKbf01.48264ae7.2,SIP\\/2.0\\/UDP%20192.168.1.60%3A52795%3Breceived%3D188.137.44.25%3Bbranch%3Dz9hG4bK-524287-1---6672a52b85f73045%3Brport%3D52795",
      "sip_from_display":"Mac",
      "sip_full_from":"%22Mac%22%20%3Csip%3A102%40d1.vtpbx.com%3E%3Btag%3Dc322083b",
      "sip_full_to":"%3Csip%3A101%40d1.vtpbx.com%3E%3Btag%3Dp53ggg5NFB97m",
      "last_sent_callee_id_name":"Outbound%20Call",
      "last_sent_callee_id_number":"101",
      "sip_hangup_phrase":"OK",
      "last_bridge_hangup_cause":"NORMAL_CLEARING",
      "last_bridge_proto_specific_hangup_cause":"sip%3A200",
      "bridge_hangup_cause":"NORMAL_CLEARING",
      "record_file_size":"110124",
      "record_samples":"55040",
      "record_seconds":"3",
      "record_ms":"3440",
      "record_completion_cause":"success-silence",
      "hangup_cause":"NORMAL_CLEARING",
      "hangup_cause_q850":"16",
      "digits_dialed":"none",
      "start_stamp":"2019-04-05%2010%3A50%3A43",
      "profile_start_stamp":"2019-04-05%2010%3A50%3A43",
      "answer_stamp":"2019-04-05%2010%3A50%3A44",
      "bridge_stamp":"2019-04-05%2010%3A50%3A44",
      "progress_stamp":"2019-04-05%2010%3A50%3A43",
      "progress_media_stamp":"2019-04-05%2010%3A50%3A44",
      "end_stamp":"2019-04-05%2010%3A50%3A46",
      "start_epoch":"1554461443",
      "start_uepoch":"1554461443064073",
      "profile_start_epoch":"1554461443",
      "profile_start_uepoch":"1554461443064073",
      "answer_epoch":"1554461444",
      "answer_uepoch":"1554461444604069",
      "bridge_epoch":"1554461444",
      "bridge_uepoch":"1554461444604069",
      "last_hold_epoch":"0",
      "last_hold_uepoch":"0",
      "hold_accum_seconds":"0",
      "hold_accum_usec":"0",
      "hold_accum_ms":"0",
      "resurrect_epoch":"0",
      "resurrect_uepoch":"0",
      "progress_epoch":"1554461443",
      "progress_uepoch":"1554461443484084",
      "progress_media_epoch":"1554461444",
      "progress_media_uepoch":"1554461444604069",
      "end_epoch":"1554461446",
      "end_uepoch":"1554461446944059",
      "last_app":"bridge",
      "last_arg":"sofia\\/external\\/101%40d1.vtpbx.com%3Bfs_path%3Dsip%3A138.197.105.36%3A50600",
      "caller_id":"%22Mac%22%20%3C102%3E",
      "duration":"3",
      "billsec":"2",
      "progresssec":"0",
      "answersec":"1",
      "waitsec":"1",
      "progress_mediasec":"1",
      "flow_billsec":"3",
      "mduration":"3880",
      "billmsec":"2340",
      "progressmsec":"420",
      "answermsec":"1540",
      "waitmsec":"1540",
      "progress_mediamsec":"1540",
      "flow_billmsec":"3880",
      "uduration":"      38

        */


        $uuid = $cdr["variables"]["uuid"];
        $callerUsername = $cdr["variables"]["sip_from_user"];  // %2B
        $callerUsername = str_replace("%2B", "", $callerUsername);


        $callerDisplayName = "";
        if (isset($cdr["variables"]["sip_from_display"]))
            $callerDisplayName = $cdr["variables"]["sip_from_display"];


        $destinationNumber = $cdr["variables"]["sip_req_user"];

        $domain = "";
        if (isset($cdr["variables"]["domain_name"]))
            $domain = $cdr["variables"]["domain_name"];


        $domainID = intval(getDomainIDbyName($domain, $mysqli));

        $callStatus = ""; // SUCCESS - connected , CANCEL - cancelled
        if (isset($cdr["variables"]["DIALSTATUS"]))
            $callStatus = test_input($cdr["variables"]["DIALSTATUS"]);


        $call_start = urldecode($cdr["variables"]["start_stamp"]);


        $call_answer = "";
        if (isset($cdr["variables"]["answer_stamp"]))
            $call_answer = urldecode($cdr["variables"]["answer_stamp"]);

        if ($call_answer == "") $call_answer = "0000-00-00 00:00:00";

        $call_end = urldecode($cdr["variables"]["end_stamp"]);
        if ($call_end == "") $call_end = "0000-00-00 00:00:00";
        $billsec = $cdr["variables"]["billsec"];

        $hangup_cause = $cdr["variables"]["hangup_cause"] . ' (' . $cdr["variables"]["hangup_cause_q850"] . ')';

        $customer_id = '';
        if (isset($cdr["variables"]["vtpbx_customer_id"])) {
            $customer_id = $cdr["variables"]["vtpbx_customer_id"];
        } else {
            // if vtpbx_customer_id is not available then let's try to find out based on the domain...
            $customer_id = getCustomerIDbyDomainName($domain, $mysqli);


        }

        if (is_null($customer_id) || $customer_id == "") {
            error_log("Dropping the CDR");
            exit;
        }


        // For DID numbers we don't have the domain name but we have the customer ID, let's fix:

        if ($customer_id > 0 && $domainID == 0) {

            $domainID = getDomainIdByDIDnumber($destinationNumber, $mysqli);
            $domain = getDomainNameByID($domainID, $mysqli);
        }


        $vtpbx_destination_type = test_input($cdr["variables"]["vtpbx_destination_type"]);
        $vtpbx_destination_def = test_input($cdr["variables"]["vtpbx_destination_def"]);
            // remove "dialed_extension%3D" from the destination_def
        $vtpbx_destination_def = str_replace("dialed_extension%3D","",$vtpbx_destination_def);



        $user_agent = $cdr["variables"]["sip_user_agent"];

        $qos_quality = floatval($cdr[""]["quality_percentage"]);
        $qos_mos = floatval($cdr[""]["mos"]);


        //error_log("== FS CDR :   " . json_encode($cdr));
        //error_log("--CDR-- inserting: [$uuid] Customer [$customer_id],  From [$callerUsername][$domain] to [$destinationNumber] Type [$vtpbx_destination_type]-[$vtpbx_destination_def]   at [$call_start] answered [$call_answer] end [$call_end] dur[$billsec] Status [$callStatus]    hangup [$hangup_cause]   ");

        insertCallLogItem($customer_id, $domainID, $uuid, $callerUsername, $destinationNumber, $vtpbx_destination_type, $vtpbx_destination_def, $callStatus, $hangup_cause, $qos_mos, $qos_quality, $call_start, $call_answer, $call_end, $billsec, $mysqli);


        // If this is call landing in the QUEUE then need to update "queue_call_logs" table with proper "end_time" and call duraiton.
        //
        //
        //error_log("--CDR-- Is it QUEUE [$vtpbx_destination_type]  -> $call_end,$customer_id,$domainID,$vtpbx_destination_def,$uuid");
        if ($vtpbx_destination_type == "QUEUE") {

            $val = updateQueueCallDisconnectionTime($call_end, $customer_id, $domainID, $vtpbx_destination_def, $uuid, $mysqli);
            //  error_log("--CDR--  after call dc time update:  [$val] ");
        }


        error_log("--CDR-- : Response given, it's time to upload recording and do other magic...");

        // Flush all output.
        ob_end_flush();
        ob_flush();
        flush();


        // 1. if the call was RECORDED then upload the recording to S3 storage:
        $sourceFile = PBX_RECORDING_FILES_BASE . "/" . $domain . "/" . $uuid . ".wav";    // '/opt/ctpbx/recordings/d1.callertech.net/49a4c0e3-e4ef-453b-968c-ca8b3729e825.wav'

        $potentialRecordingFileName = PBX_RECORDING_FILES_BASE . "/" . $domain . "/" . $uuid . ".wav";
        $s3fileURL = "";
        if (file_exists($potentialRecordingFileName) && $billsec > 0) {


            $fileName = $uuid . ".wav";
            $s3fileURL = s3_putObject_recording_file($domain, $fileName, "public-read");



            if(unlink($potentialRecordingFileName)){
                error_log("--CDR-- : [$uuid]  Recording file uploaded to S3 and deleted locally:  " . $s3fileURL);
            }else{
                error_log("--CDR-- : [$uuid]  Recording file uploaded to S3 but not deleted locally:  " . $s3fileURL);
            }


        } else {

            error_log("--CDR-- : [$uuid]  Recording file [$potentialRecordingFileName] doesn't exist or the call duration is zero.");
        }


        // --CDR-- : [0a4bbe03-f0e0-4de8-bdf4-120589aecd51]  Recording file uploaded to S3:  https://callertech-files.s3.us-west-2.amazonaws.com/recordings/d1.callertech.net/0a4bbe03-f0e0-4de8-bdf4-120589aecd51.wav


        // 2. Forward call details to the API:

        $url = CT_POST_CDR_URL;
        /*
         *
            Status: completed/no-answer/failed
            Direction: inbound/outbound
            From
            To
            Recording URL
         *
         */


        $direction = "inbound";
        $api_did = $destinationNumber;
        $api_phonenumber = $callerUsername; // Phonenumber of the other person,for incoming call, the caller’s number for outgoing call, the receiver’s number
        $api_status = "completed";

        //
        // call_status based on the duration for incoming calls and $callStatus - original call status:
        //
        switch ($callStatus) {
            // ANSWERED/SUCCESS
            case "ANSWERED":{
                $api_status = "completed";
            }break;
            case "SUCCESS":{
                $api_status = "completed";
            }break;



            // CANCEL / timeout
            case "CANCEL":{
                $api_status = "no-answer";

            }break;
            case "NO_USER_RESPONSE":{
                $api_status = "no-answer";

            }break;




            // BUSY
            case "BUSY":{
                $api_status = "busy";

            }break;



            // All other cases: fail
            default:{
                $api_status = "failed";
            }

        }




        if($vtpbx_destination_type=="EXTERNAL_CALL"){
            $direction="outbound-api";

            // extract DID mapped to this user? That should be the user caller ID
            //$api_did = $callerUsername;
            $userDetails = getUserDetailsByUsernameDomain($callerUsername,$domainID,$mysqli);
            $api_did = $userDetails["external_caller_id"];

            $api_phonenumber = $destinationNumber; // Phonenumber of the other person,for incoming call, the caller’s number for outgoing call, the receiver’s number



        }





       // error_log(json_encode($cdr["variables"]));

        /*
         *      vtpbx_destination_type
         *      vtpbx_destination_def
         */

        $vtpbx_destination_type = "";
        $vtpbx_destination_def = "";


        if(isset($cdr["variables"]["vtpbx_destination_type"]))
            $vtpbx_destination_type = $cdr["variables"]["vtpbx_destination_type"];

        if(isset($cdr["variables"]["vtpbx_destination_def"]))
            $vtpbx_destination_def = $cdr["variables"]["vtpbx_destination_def"];
            $vtpbx_destination_def = str_replace("dialed_extension%3D","",$vtpbx_destination_def);




        $url = CT_POST_CALL_URL;
        $url_token = CT_API_TOKEN;

        $webhookDetails_arr = get_CT_webhook_url_and_token_by_customer_and_type($customer_id,CT_API_WEBHOOK_TYPE_CDRS,$mysqli);
        $url = $webhookDetails_arr["webhook_url"];
        $url_token = $webhookDetails_arr["webhook_token"];




        $data = array(
            "did" => $api_did,
            "token" => $url_token,
            "phonenumber" => $api_phonenumber,
            "note" => "sip",
            "direction" => $direction,
            "recording_url" => $s3fileURL,
            "duration" => $billsec,

            "uuid" => $uuid,
            "customer" => $customer_id,

            "destination_type" => $vtpbx_destination_type,
            "destination_def" => $vtpbx_destination_def,

            "domain" => $domain,
            "status" => $api_status

        );



        // SLEEP for 2 seconds to let the other system put together the call details
        sleep(2);

        $curlResponse  = httpPostViaCURL($url, $data);

        $curlPostJSON = json_encode($data);
        error_log("--CDR-- : [$uuid] POST: $curlPostJSON curl Response: [$curlResponse] .");



    }break;
    case "outbound":{

        /*
{
   "":{
      "channel_data":"\\n    ",
      "state":"CS_REPORTING",
      "direction":"outbound",
      "state_number":"11",
      "flags":"0=1;1=1;2=1;3=1;21=1;38=1;39=1;41=1;44=1;49=1;54=1;76=1;96=1;107=1;113=1;114=1;123=1;160=1;165=1",
      "caps":"1=1;2=1;3=1;4=1;5=1;6=1;8=1;9=1",
      "call-stats":"\\n    ",
      "audio":"\\n      ",
      "inbound":"\\n        ",
      "raw_bytes":"16856",
      "media_bytes":"16856",
      "packet_count":"98",
      "media_packet_count":"98",
      "skip_packet_count":"18",
      "jitter_packet_count":"0",
      "dtmf_packet_count":"0",
      "cng_packet_count":"0",
      "flush_packet_count":"0",
      "largest_jb_size":"0",
      "jitter_min_variance":"44.44",
      "jitter_max_variance":"172.05",
      "jitter_loss_rate":"0.00",
      "jitter_burst_rate":"0.00",
      "mean_interval":"19.78",
      "flaw_total":"0",
      "quality_percentage":"100.00",
      "mos":"4.50",
      "outbound":"\\n        ",
      "rtcp_packet_count":"0",
      "rtcp_octet_count":"0"
   },
   "variables":{
      "direction":"outbound",
      "is_outbound":"true",
      "uuid":"ba27d90e-f679-4b38-bb39-07b9d3448abf",
      "session_id":"25",
      "sip_publicofile_name":"external",
      "video_media_flow":"disabled",
      "text_media_flow":"disabled",
      "channel_name":"sofia\\/external\\/101%40d1.vtpbx.com",
      "sip_destination_url":"sip%3A101%40d1.vtpbx.com%3Bfs_path%3Dsip%3A138.197.105.36%3A50600",
      "max_forwards":"67",
      "originator_codec":"G722%408000h%4020i",
      "originator":"44dc566a-3657-4fa8-872d-81839524157e",
      "switch_m_sdp":"v%3D0%0D%0Ao%3D-%201554461442898334%201%20IN%20IP4%20188.137.44.25%0D%0As%3DX-Lite%20release%205.5.0%20stamp%2097576%0D%0Ac%3DIN%20IP4%20188.137.44.25%0D%0At%3D0%200%0D%0Am%3Daudio%2054052%20RTP\\/AVP%209%208%20120%200%2084%20101%0D%0Aa%3Drtpmap%3A120%20opus\\/48000\\/2%0D%0Aa%3Dfmtp%3A120%20useinbandfec%3D1%3B%20usedtx%3D1%3B%20maxaveragebitrate%3D64000%0D%0Aa%3Drtpmap%3A84%20speex\\/16000%0D%0Aa%3Drtpmap%3A101%20telephone-event\\/8000%0D%0Aa%3Dfmtp%3A101%200-15%0D%0Aa%3Doldoip%3A192.168.1.60%0D%0Aa%3Doldcip%3A192.168.1.60%0D%0A",
      "export_vars":"RFC2822_DATE,dialed_extension",
      "RFC2822_DATE":"Fri,%2005%20Apr%202019%2010%3A50%3A43%20%2B0000",
      "dialed_extension":"101",
      "originate_early_media":"true",
      "originating_leg_uuid":"44dc566a-3657-4fa8-872d-81839524157e",
      "audio_media_flow":"sendrecv",
      "rtp_local_sdp_str":"v%3D0%0D%0Ao%3DFreeSWITCH%201554443943%201554443944%20IN%20IP4%20209.97.147.156%0D%0As%3DFreeSWITCH%0D%0Ac%3DIN%20IP4%20209.97.147.156%0D%0At%3D0%200%0D%0Am%3Daudio%2017500%20RTP\\/AVP%209%20101%0D%0Aa%3Drtpmap%3A9%20G722\\/8000%0D%0Aa%3Drtpmap%3A101%20telephone-event\\/8000%0D%0Aa%3Dfmtp%3A101%200-16%0D%0Aa%3Dptime%3A20%0D%0Aa%3Dsendrecv%0D%0A",
      "sip_outgoing_contact_uri":"%3Csip%3Amod_sofia%40209.97.147.156%3A5080%3E",
      "sip_req_uri":"101%40d1.vtpbx.com",
      "sofia_profile_name":"external",
      "recovery_profile_name":"external",
      "sofia_profile_url":"sip%3Amod_sofia%40209.97.147.156%3A5080",
      "sip_local_network_addr":"209.97.147.156",
      "sip_reply_host":"138.197.105.36",
      "sip_reply_port":"50600",
      "sip_network_ip":"138.197.105.36",
      "sip_network_port":"50600",
      "sip_allow":"INVITE,%20ACK,%20OPTIONS,%20BYE,%20CANCEL,%20REFER,%20NOTIFY,%20INFO,%20PRACK,%20UPDATE,%20MESSAGE",
      "sip_recover_contact":"%3Csip%3A101%40188.137.44.25%3A5574%3E",
      "sip_invite_route_uri":"%3Csip%3A138.197.105.36%3A50600%3Blr%3E",
      "sip_invite_record_route":"%3Csip%3A138.197.105.36%3A50600%3Blr%3E",
      "sip_full_via":"SIP\\/2.0\\/UDP%20209.97.147.156%3A5080%3Breceived%3D209.97.147.156%3Brport%3D5080%3Bbranch%3Dz9hG4bKS05Baep9vUm9p",
      "sip_recover_via":"SIP\\/2.0\\/UDP%20209.97.147.156%3A5080%3Breceived%3D209.97.147.156%3Brport%3D5080%3Bbranch%3Dz9hG4bKS05Baep9vUm9p",
      "sip_from_display":"Mac",
      "sip_full_from":"%22Mac%22%20%3Csip%3A102%40209.97.147.156%3E%3Btag%3DDcBKZySDSHaBj",
      "sip_full_to":"%3Csip%3A101%40d1.vtpbx.com%3E%3Btag%3D2181623476",
      "sip_from_user":"102",
      "sip_from_uri":"102%40209.97.147.156",
      "sip_from_host":"209.97.147.156",
      "sip_to_user":"101",
      "sip_to_uri":"101%40d1.vtpbx.com",
      "sip_to_host":"d1.vtpbx.com",
      "sip_contact_user":"101",
      "sip_contact_port":"5574",
      "sip_contact_uri":"101%40188.137.44.25%3A5574",
      "sip_contact_host":"188.137.44.25",
      "sip_to_tag":"2181623476",
      "sip_from_tag":"DcBKZySDSHaBj",
      "sip_cseq":"2676929",
      "sip_call_id":"8039320b-d233-1237-d093-2a90f36cef6c",
      "switch_r_sdp":"v%3D0%0D%0Ao%3Dsdp_admin%202016111840%2068005313%20IN%20IP4%20192.168.1.40%0D%0As%3DA%20conversation%0D%0Ac%3DIN%20IP4%20192.168.1.40%0D%0At%3D0%200%0D%0Am%3Daudio%2010228%20RTP\\/AVP%209%20101%0D%0Aa%3Drtpmap%3A9%20G722\\/8000%0D%0Aa%3Drtpmap%3A101%20telephone-event\\/8000%0D%0Aa%3Dfmtp%3A101%200-15%0D%0A",
      "ep_codec_string":"mod_spandsp.G722%408000h%4020i%4064000b",
      "rtp_use_codec_string":"G722%408000h%4020i",
      "remote_audio_media_flow":"sendrecv",
      "rtp_audio_recv_pt":"9",
      "rtp_use_codec_name":"G722",
      "rtp_use_codec_rate":"8000",
      "rtp_use_codec_ptime":"20",
      "rtp_use_codec_channels":"1",
      "rtp_last_audio_codec_string":"G722%408000h%4020i%401c",
      "read_codec":"G722",
      "original_read_codec":"G722",
      "read_rate":"16000",
      "original_read_rate":"16000",
      "write_codec":"G722",
      "write_rate":"16000",
      "dtmf_type":"rfc2833",
      "local_media_ip":"209.97.147.156",
      "local_media_port":"17500",
      "advertised_media_ip":"209.97.147.156",
      "rtp_use_timer_name":"soft",
      "rtp_use_pt":"9",
      "rtp_use_ssrc":"279995547",
      "rtp_2833_send_payload":"101",
      "rtp_2833_recv_payload":"101",
      "remote_media_ip":"192.168.1.40",
      "remote_media_port":"10228",
      "endpoint_disposition":"ANSWER",
      "last_bridge_to":"44dc566a-3657-4fa8-872d-81839524157e",
      "bridge_channel":"sofia\\/internal\\/102%40d1.vtpbx.com",
      "bridge_uuid":"44dc566a-3657-4fa8-872d-81839524157e",
      "signal_bond":"44dc566a-3657-4fa8-872d-81839524157e",
      "last_sent_callee_id_name":"Mac",
      "last_sent_callee_id_number":"102",
      "remote_audio_ip_reported":"192.168.1.40",
      "remote_audio_ip":"188.137.44.25",
      "remote_audio_port_reported":"10228",
      "remote_audio_port":"10228",
      "rtp_auto_adjust_audio":"true",
      "sip_term_status":"200",
      "proto_specific_hangup_cause":"sip%3A200",
      "sip_term_cause":"16",
      "last_bridge_role":"originatee",
      "sip_user_agent":"Fanvil%20X4%202.6.0.5850%200c383e1d78c0",
      "sip_hangup_disposition":"recv_bye",
      "call_uuid":"ba27d90e-f679-4b38-bb39-07b9d3448abf",
      "hangup_cause":"NORMAL_CLEARING",
      "hangup_cause_q850":"16",
      "digits_dialed":"none",
      "start_stamp":"2019-04-05%2010%3A50%3A43",
      "profile_start_stamp":"2019-04-05%2010%3A50%3A43",
      "answer_stamp":"2019-04-05%2010%3A50%3A44",
      "bridge_stamp":"2019-04-05%2010%3A50%3A44",
      "progress_stamp":"2019-04-05%2010%3A50%3A43",
      "progress_media_stamp":"2019-04-05%2010%3A50%3A44",
      "end_stamp":"2019-04-05%2010%3A50%3A46",
      "start_epoch":"1554461443",
      "start_uepoch":"1554461443124059",
      "profile_start_epoch":"1554461443",
      "profile_start_uepoch":"1554461443124059",
      "answer_epoch":"1554461444",
      "answer_uepoch":"1554461444604069",
      "bridge_epoch":"1554461444",
      "bridge_uepoch":"1554461444604069",
      "last_hold_epoch":"0",
      "last_hold_uepoch":"0",
      "hold_accum_seconds":"0",
      "hold_accum_usec":"0",
      "hold_accum_ms":"0",
      "resurrect_epoch":"0",
      "resurrect_uepoch":"0",
      "progress_epoch":"1554461443",
      "progress_uepoch":"1554461443484084",
      "progress_media_epoch":"1554461444",
      "progress_media_uepoch":"1554461444604069",
      "end_epoch":"1554461446",
      "end_uepoch":"1554461446944059",
      "caller_id":"%22Mac%22%20%3C102%3E",
      "duration":"3",
      "billsec":"2",
      "progresssec":"0",
      "answersec":"1",
      "waitsec":"1",
      "progress_mediasec":"1",
      "flow_billsec":"3",
      "mduration":"3820",
      "billmsec":"2340",
      "progressmsec":"360",
      "answermsec":"1480",
      "waitmsec":"1480",
      "progress_mediamsec":"1480",
      "flow_billmsec":"3820",
      "uduration":"3820000",
      "billusec":"2339990",
      "progressusec":"360025",
      "answerusec":"1480010",
      "waitusec":"1480010",
      "progress_mediausec":"1480010",
      "flow_billusec":"3820000",
      "rtp_audio_in_raw_bytes":"16856",
      "rtp_audio_in_media_bytes":"16856",
      "rtp_audio_in_packet_count":"98",
      "rtp_audio_in_media_packet_count":"98",
      "rtp_audio_in_skip_packet_count":"18",
      "rtp_audio_in_jitter_packet_count":"0",
      "rtp_audio_in_dtmf_packet_count":"0",
      "rtp_audio_in_cng_packet_count":"0",
      "rtp_audio_in_flush_packet_count":"0",
      "rtp_audio_in_largest_jb_size":"0",
      "rtp_audio_in_jitter_min_variance":"44.44",
      "rtp_audio_in_jitter_max_variance":"172.05",
      "rtp_audio_in_jitter_loss_rate":"0.00",
      "rtp_audio_in_jitter_burst_rate":"0.00",
      "rtp_audio_in_mean_interval":"19.78",
      "rtp_audio_in_flaw_total":"0",
      "rtp_audio_in_quality_percentage":"100.00",
      "rtp_audio_in_mos":"4.50",
      "      rtp_audio_out_raw_b


        */





        // B-leg call in the group  /  group + transfer
        // the original call uuid is in the    "call_uuid"

        $inbound_uuid  = $cdr["variables"]["originator"];


        // B-leg call in the queue
        if($inbound_uuid == "" && isset($cdr["variables"]["fifo_bridge_uuid"])){  // for calls coming to the QUEUE the "originator" parameter is not populated
            $inbound_uuid  = $cdr["variables"]["fifo_bridge_uuid"];
        }




        $uuid = $cdr["variables"]["uuid"];




        $callerUsername = $cdr["variables"]["sip_from_user"];
            $callerUsername = str_replace("%2B", "", $callerUsername); // remove %2B from the number

        $destinationNumber = $cdr["variables"]["sip_to_user"];

        $callStatus = test_input($cdr["variables"]["endpoint_disposition"]); // ANSWER - connected , .... - cancelled

        $call_start = urldecode($cdr["variables"]["start_stamp"] );
        $call_answer = urldecode($cdr["variables"]["answer_stamp"] );
            if($call_answer == "")  $call_answer = "0000-00-00 00:00:00";
        $call_end =urldecode( $cdr["variables"]["end_stamp"] );
            if($call_end == "")  $call_end ="0000-00-00 00:00:00";

        $billsec = $cdr["variables"]["billsec"];

        $customer_id = $cdr["variables"]["vtpbx_customer_id"];

        $hangup_cause = $cdr["variables"]["hangup_cause"] . ' (' . $cdr["variables"]["hangup_cause_q850"]. ')';

        $qos_quality = floatval($cdr[""]["quality_percentage"]) ;
        $qos_mos = floatval($cdr[""]["mos"]);



        error_log("== Sub-CDR for original call [$inbound_uuid]: [$uuid] From [$callerUsername] to [$destinationNumber], at [$call_start] answered [$call_answer] end [$call_end]  dur[$billsec] Status [$callStatus]    hangup [$hangup_cause]   ");


        insertCallLogExtraItem($uuid,$inbound_uuid,  $callerUsername,$destinationNumber,$callStatus,$hangup_cause,$qos_mos,$qos_quality,$call_start,$call_answer,$call_end,$billsec,$mysqli);


        //



        // Flush all output.
        ob_end_flush();
        ob_flush();
        flush();



        // 2. Forward call details to the API:

        $url = CT_POST_CDR_URL;
        /*
         *
            Status: completed/no-answer/failed
            Direction: inbound/outbound
            From
            To
            Recording URL
         *
         */

        // error_log(json_encode($cdr["variables"]));

        /*
         *      vtpbx_destination_type
         *      vtpbx_destination_def
         */

        $vtpbx_destination_type = "";
        $vtpbx_destination_def = "";




        $direction = "inbound";
        $api_did = $destinationNumber;
        $api_phonenumber = $callerUsername; // Phonenumber of the other person,for incoming call, the caller’s number for outgoing call, the receiver’s number
        $api_status = "completed";

        //
        // call_status based on the duration for incoming calls and $callStatus - original call status:
        //
        switch ($callStatus) {
            // ANSWERED/SUCCESS
            case "ANSWER":{
                $api_status = "completed";
            }break;
            case "SUCCESS":{
                $api_status = "completed";
            }break;



            // CANCEL / timeout
            case "CANCEL":{
                $api_status = "no-answer";

            }break;
            case "NO_USER_RESPONSE":{
                $api_status = "no-answer";

            }break;




            // BUSY
            case "BUSY":{
                $api_status = "busy";

            }break;



            // All other cases: fail
            default:{
                $api_status = "failed";
            }

        }


        if($customer_id>0){
            // ok


        }else{

           $domain_name_candidate =  $cdr["variables"]["sip_to_host"];

           $customer_id_candidate = getCustomerIDbyDomainName($domain_name_candidate, $mysqli);

           if($customer_id_candidate>0){
               $customer_id = $customer_id_candidate;
               $domain  = $domain_name_candidate;

               // let's check if the destination ("did") is a username from this Customer account
               $domainID = getDomainIDbyName($domain_name_candidate,$mysqli);
               $userDetails = getUserDetailsByUsernameDomain($api_did,$domainID,$mysqli);


                if(isset($userDetails["id"])){

                    $vtpbx_destination_type = "USER";
                    $vtpbx_destination_def = $api_did;


                }

               error_log(" --CDR Extra: Domain ID [$domainID], username [$api_did] , the user details are:  " . json_encode($userDetails));

           }else{
               // Last resort... perhaps the call is forwarded from one of the PBXes...

               $domain_name_candidate =  $cdr["variables"]["force_transfer_context"];

               $customer_id_candidate = getCustomerIDbyDomainName($domain_name_candidate, $mysqli);

               if($customer_id_candidate>0){
                   $customer_id = $customer_id_candidate;
                   $domain  = $domain_name_candidate;

                   // let's check if the destination ("did") is a username from this Customer account
                   $domainID = getDomainIDbyName($domain_name_candidate,$mysqli);
                   $userDetails = getUserDetailsByUsernameDomain($api_did,$domainID,$mysqli);


                   if(isset($userDetails["id"])){

                       $vtpbx_destination_type = "USER";
                       $vtpbx_destination_def = $api_did;


                   }

                   error_log(" --CDR Extra: Domain ID [$domainID], username [$api_did] , the user details are:  " . json_encode($userDetails));

               }



           }

            error_log(" --CDR Extra: Customer ID should be deducted from sip_to_host variable [$domain_name_candidate], the customer id is [$customer_id]");
        }






        if($vtpbx_destination_type=="EXTERNAL_CALL"){
            $direction="outbound-api";

            // extract DID mapped to this user? That should be the user caller ID
            //$api_did = $callerUsername;
            $domain_name = getSingleDomainNamebyCustomer($customer_id,$mysqli);
            $domainID = getDomainIDbyName($domain_name,$mysqli);
                $domain = $domain_name;


            $userDetails = getUserDetailsByUsernameDomain($callerUsername,$domainID,$mysqli);
            $api_did = $userDetails["external_caller_id"];

            $api_phonenumber = $destinationNumber; // Phonenumber of the other person,for incoming call, the caller’s number for outgoing call, the receiver’s number



        }





        $url = CT_POST_CALL_URL;
        $url_token = CT_API_TOKEN;

        $webhookDetails_arr = get_CT_webhook_url_and_token_by_customer_and_type($customer_id,CT_API_WEBHOOK_TYPE_CDRS,$mysqli);
        $url = $webhookDetails_arr["webhook_url"];
        error_log(" --CDR-- Extra: Webhook URL: [$url]");
        $url_token = $webhookDetails_arr["webhook_token"];


        $data = array(
            "did" => $api_did,
            "token" => $url_token,
            "phonenumber" => $api_phonenumber,
            "note" => "sip",
            "call_type" => "BLEG",
            "direction" => $direction,
            "duration" => $billsec,

            "uuid" => $inbound_uuid,
            "customer" => $customer_id,

            "destination_type" => $vtpbx_destination_type,
            "destination_def" => $vtpbx_destination_def,

            "domain" => $domain,
            "status" => $api_status

        );

        $curlResponse  = httpPostViaCURL($url, $data);

        $curlPostJSON = json_encode($data);


     //   error_log(json_encode($cdr));


        error_log("--CDR Extra-- : [$inbound_uuid] POST: $curlPostJSON curl Response: [$curlResponse] .");







    }break;
}


//error_log("RAW CDR: " . json_encode($cdr) );

// INSERT CDR




?>





