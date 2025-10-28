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


// Whatever will happen the response will be in text/xml format
header( 'Content-Type: text/xml' );



// LOG request:
//error_log(json_encode($_REQUEST));


/*


// 1st request  - default context, need to forward to the right tenant
{
   "hostname":"vtpbx-fs",
   "section":"dialplan",
   "tag_name":"",
   "key_name":"",
   "key_value":"",
   "Event-Name":"REQUEST_PARAMS",
   "Core-UUID":"536ca6e9-4da3-423c-9808-a853d8b78b09",
   "FreeSWITCH-Hostname":"vtpbx-fs",
   "FreeSWITCH-Switchname":"vtpbx-fs",
   "FreeSWITCH-IPv4":"209.97.147.156",
   "FreeSWITCH-IPv6":"::1",
   "Event-Date-Local":"2019-03-27 11:37:59",
   "Event-Date-GMT":"Wed, 27 Mar 2019 11:37:59 GMT",
   "Event-Date-Timestamp":"1553686679208019",
   "Event-Calling-File":"mod_dialplan_xml.c",
   "Event-Calling-Function":"dialplan_xml_locate",
   "Event-Calling-Line-Number":"608",
   "Event-Sequence":"530",
   "Channel-State":"CS_ROUTING",
   "Channel-Call-State":"RINGING",
   "Channel-State-Number":"2",
   "Channel-Name":"sofia\\/internal\\/104@d1.vtpbx.com",
   "Unique-ID":"6932f492-73cd-4956-b5ad-7a00ee1854b4",
   "Call-Direction":"inbound",
   "Presence-Call-Direction":"inbound",
   "Channel-HIT-Dialplan":"true",
   "Channel-Presence-ID":"104@d1.vtpbx.com",
   "Channel-Call-UUID":"6932f492-73cd-4956-b5ad-7a00ee1854b4",
   "Answer-State":"ringing",
   "Caller-Direction":"inbound",
   "Caller-Logical-Direction":"inbound",
   "Caller-Username":"104",
   "Caller-Dialplan":"XML",
   "Caller-Caller-ID-Name":"D1 Polycom",
   "Caller-Caller-ID-Number":"104",
   "Caller-Orig-Caller-ID-Name":"D1 Polycom",
   "Caller-Orig-Caller-ID-Number":"104",
   "Caller-Network-Addr":"138.197.105.36",
   "Caller-ANI":"104",
   "Caller-Destination-Number":"101",
   "Caller-Unique-ID":"6932f492-73cd-4956-b5ad-7a00ee1854b4",
   "Caller-Source":"mod_sofia",
   "Caller-Context":"default",
   "Caller-Channel-Name":"sofia\\/internal\\/104@d1.vtpbx.com",
   "Caller-Profile-Index":"1",
   "Caller-Profile-Created-Time":"1553686679208019",
   "Caller-Channel-Created-Time":"1553686679208019",
   "Caller-Channel-Answered-Time":"0",
   "Caller-Channel-Progress-Time":"0",
   "Caller-Channel-Progress-Media-Time":"0",
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
   "variable_uuid":"6932f492-73cd-4956-b5ad-7a00ee1854b4",
   "variable_session_id":"1",
   "variable_sip_from_user":"104",
   "variable_sip_from_uri":"104@d1.vtpbx.com",
   "variable_sip_from_host":"d1.vtpbx.com",
   "variable_video_media_flow":"disabled",
   "variable_audio_media_flow":"disabled",
   "variable_text_media_flow":"disabled",
   "variable_channel_name":"sofia\\/internal\\/104@d1.vtpbx.com",
   "variable_sip_call_id":"4e6e932e-c9e47d37-4f879f20@192.168.1.46",
   "variable_sip_local_network_addr":"209.97.147.156",
   "variable_sip_network_ip":"138.197.105.36",
   "variable_sip_network_port":"50600",
   "variable_sip_invite_stamp":"1553686679208019",
   "variable_sip_received_ip":"138.197.105.36",
   "variable_sip_received_port":"50600",
   "variable_sip_via_protocol":"udp",
   "variable_sip_authorized":"true",
   "variable_sip_acl_authed_by":"domains",
   "variable_sip_from_user_stripped":"104",
   "variable_sip_from_tag":"E3A7C9CA-A7FFBC53",
   "variable_sofia_profile_name":"internal",
   "variable_sofia_profile_url":"sip:mod_sofia@209.97.147.156:5566",
   "variable_recovery_profile_name":"internal",
   "variable_sip_invite_route_uri":"<sip:138.197.105.36:50600;r2=on;lr>,<sip:138.197.105.36:50600;transport=tcp;r2=on;lr>",
   "variable_sip_invite_record_route":"<sip:138.197.105.36:50600;transport=tcp;r2=on;lr>,<sip:138.197.105.36:50600;r2=on;lr>",
   "variable_sip_full_via":"SIP\\/2.0\\/UDP 138.197.105.36:50600;branch=z9hG4bK1514.df0ceb36.0;i=875fb756,SIP\\/2.0\\/TCP 192.168.1.46;rport=45147;received=188.137.44.25;branch=z9hG4bK504adce927689A92",
   "variable_sip_recover_via":"SIP\\/2.0\\/UDP 138.197.105.36:50600;branch=z9hG4bK1514.df0ceb36.0;i=875fb756,SIP\\/2.0\\/TCP 192.168.1.46;rport=45147;received=188.137.44.25;branch=z9hG4bK504adce927689A92",
   "variable_sip_from_display":"D1 Polycom",
   "variable_sip_full_from":"\\"   D1 Polycom\\" <sip:104   @d1.vtpbx.com>;tag=E3A7C9CA-A7FFBC53",
   "variable_sip_full_to":"<sip:101@d1.vtpbx.com;user=phone>",
   "variable_sip_allow":"INVITE, ACK, BYE, CANCEL, OPTIONS, INFO, MESSAGE, SUBSCRIBE, NOTIFY, PRACK, UPDATE, REFER",
   "variable_sip_req_params":"user=phone;transport=tcp",
   "variable_sip_req_user":"101",
   "variable_sip_req_uri":"101@d1.vtpbx.com",
   "variable_sip_req_host":"d1.vtpbx.com",
   "variable_sip_to_params":"user=phone",
   "variable_sip_to_user":"101",
   "variable_sip_to_uri":"101@d1.vtpbx.com",
   "variable_sip_to_host":"d1.vtpbx.com",
   "variable_sip_contact_params":"transport=tcp",
   "variable_sip_contact_user":"104",
   "variable_sip_contact_port":"45147",
   "variable_sip_contact_uri":"104@188.137.44.25:45147",
   "variable_sip_contact_host":"188.137.44.25",
   "variable_rtp_use_codec_string":"OPUS,G722,PCMU,PCMA,VP8",
   "variable_sip_user_agent":"PolycomSoundPointIP-SPIP_335-UA\\/4.0.14.1388",
   "variable_sip_accept_language":"en",
   "variable_sip_accept_language_0_value":"en",
   "variable_sip_accept_language_count":"1",
   "variable_sip_via_host":"138.197.105.36",
   "variable_sip_via_port":"50600",
   "variable_max_forwards":"69",
   "variable_presence_id":"104@d1.vtpbx.com",
   "variable_switch_r_sdp":"v=0\\r\\no=- 1553686678 1553686678 IN IP4 188.137.44.25\\r\\ns=Polycom IP Phone\\r\\nc=IN IP4 188.137.44.25\\r\\nt=0 0\\r\\na=sendrecv\\r\\nm=audio 2230 RTP\\/AVP 9 0 8 18 127\\r\\na=rtpmap:9 G722\\/8000\\r\\na=rtpmap:0 PCMU\\/8000\\r\\na=rtpmap:8 PCMA\\/8000\\r\\na=rtpmap:18 G729\\/8000\\r\\na=fmtp:18 annexb=no\\r\\na=rtpmap:127 telephone-event\\/8000\\r\\na=oldoip:192.168.1.46\\r\\na=oldcip:192.168.1.46\\r\\n",
   "variable_ep_codec_string":"mod_spandsp.G722@8000h@20i@64000b,CORE_PCM_MODULE.PCMU@8000h@20i@64000b,CORE_PCM_MODULE.PCMA@8000h@20i@64000b",
   "variable_endpoint_disposition":"DELAYED NEGOTIATION",
   "variable_call_uuid":"6932f492-73cd-4956-b5ad-7a00ee1854b4",
   "Hunt-Direction":"inbound",
   "Hunt-Logical-Direction":"inbound",
   "Hunt-Username":"104",
   "Hunt-Dialplan":"XML",
   "Hunt-Caller-ID-Name":"D1 Polycom",
   "Hunt-Caller-ID-Number":"104",
   "Hunt-Orig-Caller-ID-Name":"D1 Polycom",
   "Hunt-Orig-Caller-ID-Number":"104",
   "Hunt-Network-Addr":"138.197.105.36",
   "Hunt-ANI":"104",
   "Hunt-Destination-Number":"101",
   "Hunt-Unique-ID":"6932f492-73cd-4956-b5ad-7a00ee1854b4",
   "Hunt-Source":"mod_sofia",
   "Hunt-Context":"default",
   "Hunt-Channel-Name":"sofia\\/internal\\/104@d1.vtpbx.com",
   "Hunt-Profile-Index":"1",
   "Hunt-Profile-Created-Time":"1553686679208019",
   "Hunt-Channel-Created-Time":"1553686679208019",
   "Hunt-Channel-Answered-Time":"0",
   "Hunt-Channel-Progress-Time":"0",
   "Hunt-Channel-Progress-Media-Time":"0",
   "Hunt-Channel-Hangup-Time":"0",
   "Hunt-Channel-Transfer-Time":"0",
   "Hunt-Channel-Resurrect-Time":"0",
   "Hunt-Channel-Bridged-Time":"0",
   "Hunt-Channel-Last-Hold":"0",
   "Hunt-Channel-Hold-Accum":"0",
   "Hunt-Screen-Bit":"true",
   "Hunt-Privacy-Hide-Name":"false",
   "Hunt-Privacy-Hide-Number":"false"
}

*/


if (!is_array($_REQUEST)) {
    trigger_error('$_REQUEST is not an array');
}


$context = "default";
if(isset($_REQUEST["Caller-Context"]))
    $context = test_input($_REQUEST["Caller-Context"]);

$isItIVRcall = false;

 if(isset($_REQUEST["variable_ivr_menu_status"])){
     if( $_REQUEST["variable_ivr_menu_status"] == "success" ){
         $isItIVRcall = true;
     }
 }













if($isItIVRcall){
    // If it is IVR call then it should have also these two parameters:
    if(isset($_REQUEST["variable_sip_h_X-VT-DIDPROVIDER"])  &&  isset($_REQUEST["variable_sip_h_X-VT-CUSTOMER"])){
        // this call seems to be coming from the DID provider and then via IVR goes to different destination.

        $DIDprovider = intval($_REQUEST["variable_sip_h_X-VT-DIDPROVIDER"]);
        $customerID = intval($_REQUEST["variable_sip_h_X-VT-CUSTOMER"]);

        $destination = "";
        $destination = test_input($_REQUEST["Caller-Destination-Number"]);


        $ivr_destination_type = $_REQUEST["variable_ivr_destination_type"];
        $ivr_destination_def = $_REQUEST["variable_ivr_destination_def"];



        error_log("-=-=-=-=- IVR parameters:   [$ivr_destination_type]   [$ivr_destination_def] ");

        // Let's verify the DID provider, customer and the DID itself. We need domain name by return

        //Domain name is in the context:
        $domainName = $context;

        $domainDetails = getDomainDetailsByName($domainName,$mysqli);
        $domainID = $domainDetails["id"];

        $customerDetails = getCustomerDetailsByID($customerID,$mysqli);

        $caller = "";
        $caller = test_input($_REQUEST["Caller-Username"]);


        error_log("====== FS CONFIGURATION:  Returning [$context] context data for DID->IVR call. Domain [$domainName]");
        //serveDefaultContextConfigurationForSpecificDomain($domainName);


        serveDynamicDIDtoIVRconfiguration($context,$_REQUEST,$domainDetails,$customerDetails,$caller,$ivr_destination_type,$ivr_destination_def,$mysqli);






    }else{
        // this call is not coming from the DID provider


        // Got request for Default context -> serve the generic configuration to switch to the right tenant-specific context
        error_log("====== FS CONFIGURATION:  Returning default context data.");
        serveDefaultContextConfiguration();

    }



    exit;











// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
}elseif($context == "default"){

    // let's see if it is a call from DID provider or not:

    if(isset($_REQUEST["variable_sip_h_X-VT-DIDPROVIDER"])  &&  isset($_REQUEST["variable_sip_h_X-VT-CUSTOMER"])){
        // this call seems to be coming from the DID provider

        $DIDprovider = intval($_REQUEST["variable_sip_h_X-VT-DIDPROVIDER"]);
        $customerID = intval($_REQUEST["variable_sip_h_X-VT-CUSTOMER"]);

        $destination = "";
        $destination = test_input($_REQUEST["Caller-Destination-Number"]);

        // Let's verify the DID provider, customer and the DID itself. We need domain name by return


        $domainName = "";

        $dnCandidate = test_input(  getDomainByCustomerDidProvider($DIDprovider,$customerID,$destination,$mysqli)  );

        if($dnCandidate != ""){

            $domainName = $dnCandidate;
            $domainDetails = getDomainDetailsByName($domainName,$mysqli);
            $domainID = $domainDetails["id"];

            $customerDetails = getCustomerDetailsByID($customerID,$mysqli);

            $caller = "";
            $caller = test_input($_REQUEST["Caller-Username"]);

            error_log("====== FS CONFIGURATION: Destination: " . $destination);
            error_log("====== FS CONFIGURATION: Domain name: " . $domainName);

            $didNumberDetails =  getDIDNumberDetailsForCustomer($destination,$customerID,$domainID,$mysqli);



            error_log("====== FS CONFIGURATION:  Returning default context data for DID call. Domain [$domainName]");
            //serveDefaultContextConfigurationForSpecificDomain($domainName);


            serveDynamicDIDConfiguration($context,$_REQUEST,$domainDetails,$customerDetails,$caller,$mysqli);



            // Flush all output.
            ob_end_flush();
            ob_flush();
            flush();


            // DID call -> send to CT API

            $url = CT_POST_CALL_URL;
            /*
             *
                Status: completed/no-answer/failed
                Direction: inbound/outbound
                From
                To
                Recording URL
             *
             */

           $uuid = test_input($_REQUEST["Unique-ID"]);

            $data = array(
                "token"=> CT_API_TOKEN,
                "did" => $destination,
                "phonenumber" => $caller,
                "note" => "sip",
                "direction" => "inbound",
                "uuid" => $uuid,

                "customer" => $customerID,
                "domain" => $domainName

            );

            $curlResponse  = httpPostViaCURL($url, $data);

            $curlPostJSON = json_encode($data);
            error_log("--CDR-- : DID number is calling POST: $curlPostJSON curl Response: [$curlResponse] .");





        }else{
            error_log("====== FS CONFIGURATION:  Returning default context data for a call when DID is not really recognized.");



            serveDefaultContextConfiguration();
        }





    }else{
        // this call is not coming from the DID provider


        // Got request for Default context -> serve the generic configuration to switch to the right tenant-specific context
        error_log("====== FS CONFIGURATION:  Returning default context data. This call is not coming from a DID provider");
        serveDefaultContextConfiguration();

    }



    exit;

}else{

    // this is non-default context so let's proceed with the response based on the domain and the user who made the call
    /*

    {
   "hostname":"vtpbx-fs",
   "section":"dialplan",
   "tag_name":"",
   "key_name":"",
   "key_value":"",
   "Event-Name":"REQUEST_PARAMS",
   "Core-UUID":"536ca6e9-4da3-423c-9808-a853d8b78b09",
   "FreeSWITCH-Hostname":"vtpbx-fs",
   "FreeSWITCH-Switchname":"vtpbx-fs",
   "FreeSWITCH-IPv4":"209.97.147.156",
   "FreeSWITCH-IPv6":"::1",
   "Event-Date-Local":"2019-03-27 12:11:30",
   "Event-Date-GMT":"Wed, 27 Mar 2019 12:11:30 GMT",
   "Event-Date-Timestamp":"1553688690608001",
   "Event-Calling-File":"mod_dialplan_xml.c",
   "Event-Calling-Function":"dialplan_xml_locate",
   "Event-Calling-Line-Number":"608",
   "Event-Sequence":"1065",
   "Channel-State":"CS_ROUTING",
   "Channel-Call-State":"RINGING",
   "Channel-State-Number":"2",
   "Channel-Name":"sofia\\/internal\\/104@d1.vtpbx.com",
   "Unique-ID":"f0f33df0-8218-4922-a00c-7f85a074708f",
   "Call-Direction":"inbound",
   "Presence-Call-Direction":"inbound",
   "Channel-HIT-Dialplan":"true",
   "Channel-Presence-ID":"104@d1.vtpbx.com",
   "Channel-Call-UUID":"f0f33df0-8218-4922-a00c-7f85a074708f",
   "Answer-State":"ringing",
   "Caller-Direction":"inbound",
   "Caller-Logical-Direction":"inbound",
   "Caller-Username":"104",
   "Caller-Dialplan":"XML",
   "Caller-Caller-ID-Name":"D1 Polycom",
   "Caller-Caller-ID-Number":"104",
   "Caller-Orig-Caller-ID-Name":"D1 Polycom",
   "Caller-Orig-Caller-ID-Number":"104",
   "Caller-Network-Addr":"138.197.105.36",
   "Caller-ANI":"104",
   "Caller-Destination-Number":"101",
   "Caller-Unique-ID":"f0f33df0-8218-4922-a00c-7f85a074708f",
   "Caller-Source":"mod_sofia",
   "Caller-Transfer-Source":"1553688690:4d063b6b-faad-46b5-818b-74efb0650349:bl_xfer:101\\/d1.vtpbx.com\\/XML",
   "Caller-Context":"d1.vtpbx.com",
   "Caller-RDNIS":"101",
   "Caller-Channel-Name":"sofia\\/internal\\/104@d1.vtpbx.com",
   "Caller-Profile-Index":"2",
   "Caller-Profile-Created-Time":"1553688690608001",
   "Caller-Channel-Created-Time":"1553688690608001",
   "Caller-Channel-Answered-Time":"0",
   "Caller-Channel-Progress-Time":"0",
   "Caller-Channel-Progress-Media-Time":"0",
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
   "variable_uuid":"f0f33df0-8218-4922-a00c-7f85a074708f",
   "variable_session_id":"7",
   "variable_sip_from_user":"104",
   "variable_sip_from_uri":"104@d1.vtpbx.com",
   "variable_sip_from_host":"d1.vtpbx.com",
   "variable_video_media_flow":"disabled",
   "variable_audio_media_flow":"disabled",
   "variable_text_media_flow":"disabled",
   "variable_channel_name":"sofia\\/internal\\/104@d1.vtpbx.com",
   "variable_sip_call_id":"5b2ce3ec-892b9435-78e0f45e@192.168.1.46",
   "variable_sip_local_network_addr":"209.97.147.156",
   "variable_sip_network_ip":"138.197.105.36",
   "variable_sip_network_port":"50600",
   "variable_sip_invite_stamp":"1553688690608001",
   "variable_sip_received_ip":"138.197.105.36",
   "variable_sip_received_port":"50600",
   "variable_sip_via_protocol":"udp",
   "variable_sip_authorized":"true",
   "variable_sip_acl_authed_by":"domains",
   "variable_sip_from_user_stripped":"104",
   "variable_sip_from_tag":"5D8DB188-DCA28A51",
   "variable_sofia_profile_name":"internal",
   "variable_sofia_profile_url":"sip:mod_sofia@209.97.147.156:5566",
   "variable_recovery_profile_name":"internal",
   "variable_sip_invite_route_uri":"<sip:138.197.105.36:50600;r2=on;lr>,<sip:138.197.105.36:50600;transport=tcp;r2=on;lr>",
   "variable_sip_invite_record_route":"<sip:138.197.105.36:50600;transport=tcp;r2=on;lr>,<sip:138.197.105.36:50600;r2=on;lr>",
   "variable_sip_full_via":"SIP\\/2.0\\/UDP 138.197.105.36:50600;branch=z9hG4bKaf97.089f1df6.0;i=875fb756,SIP\\/2.0\\/TCP 192.168.1.46;rport=45147;received=188.137.44.25;branch=z9hG4bK5408e8674E86D450",
   "variable_sip_recover_via":"SIP\\/2.0\\/UDP 138.197.105.36:50600;branch=z9hG4bKaf97.089f1df6.0;i=875fb756,SIP\\/2.0\\/TCP 192.168.1.46;rport=45147;received=188.137.44.25;branch=z9hG4bK5408e8674E86D450",
   "variable_sip_from_display":"D1 Polycom",
   "variable_sip_full_from":"\\"   D1 Polycom\\" <sip:104   @d1.vtpbx.com>;tag=5D8DB188-DCA28A51",
   "variable_sip_full_to":"<sip:101@d1.vtpbx.com;user=phone>",
   "variable_sip_allow":"INVITE, ACK, BYE, CANCEL, OPTIONS, INFO, MESSAGE, SUBSCRIBE, NOTIFY, PRACK, UPDATE, REFER",
   "variable_sip_req_params":"user=phone;transport=tcp",
   "variable_sip_req_user":"101",
   "variable_sip_req_uri":"101@d1.vtpbx.com",
   "variable_sip_req_host":"d1.vtpbx.com",
   "variable_sip_to_params":"user=phone",
   "variable_sip_to_user":"101",
   "variable_sip_to_uri":"101@d1.vtpbx.com",
   "variable_sip_to_host":"d1.vtpbx.com",
   "variable_sip_contact_params":"transport=tcp",
   "variable_sip_contact_user":"104",
   "variable_sip_contact_port":"45147",
   "variable_sip_contact_uri":"104@188.137.44.25:45147",
   "variable_sip_contact_host":"188.137.44.25",
   "variable_rtp_use_codec_string":"OPUS,G722,PCMU,PCMA,VP8",
   "variable_sip_user_agent":"PolycomSoundPointIP-SPIP_335-UA\\/4.0.14.1388",
   "variable_sip_accept_language":"en",
   "variable_sip_accept_language_0_value":"en",
   "variable_sip_accept_language_count":"1",
   "variable_sip_via_host":"138.197.105.36",
   "variable_sip_via_port":"50600",
   "variable_presence_id":"104@d1.vtpbx.com",
   "variable_switch_r_sdp":"v=0\\r\\no=- 1553688689 1553688689 IN IP4 188.137.44.25\\r\\ns=Polycom IP Phone\\r\\nc=IN IP4 188.137.44.25\\r\\nt=0 0\\r\\na=sendrecv\\r\\nm=audio 2236 RTP\\/AVP 9 0 8 18 127\\r\\na=rtpmap:9 G722\\/8000\\r\\na=rtpmap:0 PCMU\\/8000\\r\\na=rtpmap:8 PCMA\\/8000\\r\\na=rtpmap:18 G729\\/8000\\r\\na=fmtp:18 annexb=no\\r\\na=rtpmap:127 telephone-event\\/8000\\r\\na=oldoip:192.168.1.46\\r\\na=oldcip:192.168.1.46\\r\\n",
   "variable_ep_codec_string":"mod_spandsp.G722@8000h@20i@64000b,CORE_PCM_MODULE.PCMU@8000h@20i@64000b,CORE_PCM_MODULE.PCMA@8000h@20i@64000b",
   "variable_endpoint_disposition":"DELAYED NEGOTIATION",
   "variable_DP_MATCH":"ARRAY::101|:101",
   "variable_domain_name":"d1.vtpbx.com",
   "variable_current_application_data":"101 XML d1.vtpbx.com",
   "variable_current_application":"transfer",
   "variable_max_forwards":"68",
   "variable_transfer_history":"1553688690:4d063b6b-faad-46b5-818b-74efb0650349:bl_xfer:101\\/d1.vtpbx.com\\/XML",
   "variable_transfer_source":"1553688690:4d063b6b-faad-46b5-818b-74efb0650349:bl_xfer:101\\/d1.vtpbx.com\\/XML",
   "variable_call_uuid":"f0f33df0-8218-4922-a00c-7f85a074708f",
   "Hunt-Direction":"inbound",
   "Hunt-Logical-Direction":"inbound",
   "Hunt-Username":"104",
   "Hunt-Dialplan":"XML",
   "Hunt-Caller-ID-Name":"D1 Polycom",
   "Hunt-Caller-ID-Number":"104",
   "Hunt-Orig-Caller-ID-Name":"D1 Polycom",
   "Hunt-Orig-Caller-ID-Number":"104",
   "Hunt-Network-Addr":"138.197.105.36",
   "Hunt-ANI":"104",
   "Hunt-Destination-Number":"101",
   "Hunt-Unique-ID":"f0f33df0-8218-4922-a00c-7f85a074708f",
   "Hunt-Source":"mod_sofia",
   "Hunt-Transfer-Source":"1553688690:4d063b6b-faad-46b5-818b-74efb0650349:bl_xfer:101\\/d1.vtpbx.com\\/XML",
   "Hunt-Context":"d1.vtpbx.com",
   "Hunt-RDNIS":"101",
   "Hunt-Channel-Name":"sofia\\/internal\\/104@d1.vtpbx.com",
   "Hunt-Profile-Index":"2",
   "Hunt-Profile-Created-Time":"1553688690608001",
   "Hunt-Channel-Created-Time":"1553688690608001",
   "Hunt-Channel-Answered-Time":"0",
   "Hunt-Channel-Progress-Time":"0",
   "Hunt-Channel-Progress-Media-Time":"0",
   "Hunt-Channel-Hangup-Time":"0",
   "Hunt-Channel-Transfer-Time":"0",
   "Hunt-Channel-Resurrect-Time":"0",
   "Hunt-Channel-Bridged-Time":"0",
   "Hunt-Channel-Last-Hold":"0",
   "Hunt-Channel-Hold-Accum":"0",
   "Hunt-Screen-Bit":"true",
   "Hunt-Privacy-Hide-Name":"false",
   "Hunt-Privacy-Hide-Number":"false"
}

    */











    $caller = "";
        $caller = test_input($_REQUEST["Caller-Username"]);

    $destination = "";
        $destination = test_input($_REQUEST["Caller-Destination-Number"]);


    //TODO: check if the domain is valid, if the destination number is allowed, etc.

    $domainDetails = getDomainDetailsByName($context,$mysqli);
    error_log("Caller [$caller], destination [$destination] , context [$context] ,  domain details:" . json_encode($domainDetails));



    if($domainDetails["id"] > 0 ) {  // configuration for valid domain (context) and allowed destination number
        $customerID = $domainDetails["customer"];
        // get the customer details, etc.
        $customerDetails = getCustomerDetailsByID($customerID, $mysqli);


        // This is the place when we dynamically return dial plan for various destinations users are calling to.






        serveDynamicContextConfiguration($context, $_REQUEST, $domainDetails, $customerDetails, $caller, $mysqli);
        error_log("===== FS CONFIGURATION served for context [$context] and call from [$caller] to-> [$destination]");


















    }else{
        // Can't find destination in the routing logic -> let's return NotFound!


        error_log("====== FS CONFIGURATION:  Returning NOT FOUND config data.");
        serveConfigurationNotFound();

    }










    exit();
}



