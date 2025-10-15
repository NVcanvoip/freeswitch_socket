
<?php


include_once '../settings.php';

include_once '../db_connect.php';
include_once '../functions.php';

include_once 'engine/functions_login.php';
include_once 'engine/functions_admin_portal.php';

sec_session_start();

if(login_check($mysqli)) {
    $userID = login_check_get_user_ID($mysqli);
    $recordingFileID = test_input($_GET["file"]);

        header("Content-Transfer-Encoding: binary");


        $recordingFileDetails  = getIVRFileDetails($recordingFileID,$mysqli);

        $IVRfile_name= $recordingFileDetails["file_name"];


        $fullRecordingFilePath = PBX_IVR_FILES_BASE . $IVRfile_name ;

        $uniqueFilePath = PBX_IVR_FILES_BASE . '/_temp/' . uniqid() . uniqid() . ".mp3";

        $commandLine = "ffmpeg -i " . $fullRecordingFilePath . " -ar 16000 " . $uniqueFilePath;
        exec($commandLine);


        // DEBUG


        $mimeType = mime_content_type($uniqueFilePath);


        header("Content-Type: $mimeType");

        header('Pragma: no-cache');
        header("Expires: 0");
        header("Content-Length: ".filesize($uniqueFilePath));
        ob_end_flush();
        @readfile($uniqueFilePath);







}else{
    header("Location: index.php");
    exit();
}

?>