<?php


$dir = __DIR__;
$dir = str_replace("admin_panel/engine","",$dir);
require_once $dir . "lib/aws/vendor/autoload.php";


use Aws\Polly\PollyClient;
use Aws\Credentials\Credentials;




function crateIVRfileViaTTS($customerID,$ivrFriendlyFileName,$tts_text,$mysqli){

    $ivrFileID = 0;

    // get params:

    $ivrFileName= $ivrFriendlyFileName;

    if(strlen($ivrFileName) > 20){
        $ivrFileName = substr($ivrFileName,0,20);
    }



    $tts= $tts_text;



    // convert the file
    $outputFilePATH_s3 = textToSpeech($tts);



    // copy to desired destination

    // ensure a safe filename
    $name = preg_replace("/[^A-Z0-9._-]/i", "_", $ivrFileName);

    // don't overwrite an existing file
    $i = 0;
    $parts = pathinfo($name);
    while (file_exists(PBX_IVR_FILES_BASE .  $customerID . '/' . $name)) {
        $i++;
        $name = $parts["filename"] . "-" . $i . "." . $parts["extension"];
    }


    $pathI = pathinfo($name);

    $fileDatePart = date('YmdGis') ;
    $fileName =   $pathI["basename"];


    $uploadTargetFile = PBX_IVR_FILES_BASE .  $customerID . '/'. $fileDatePart .$fileName.".wav";
    $dbShortFilePath =  $customerID . '/'. $fileDatePart .$fileName.".wav";

    // create directory if it doesn't exist yet.
    mkdir(PBX_IVR_FILES_BASE . $customerID,0766);



    copy($outputFilePATH_s3,$uploadTargetFile);
    chmod($uploadTargetFile, 0666);
    // save info in the DB


    $ivrFileID = customerAddNewIVRMediaFile($customerID,$ivrFileName,$dbShortFilePath,$tts,$outputFilePATH_s3,$mysqli);











    return $ivrFileID;


}

















//How to use the function:

/*
 *
 *
 *


  $inputText = "I want this text to be converted to WAV file. That's it.";
  $outputFilePATH = textToSpeech($inputText);
  echo "The file has been saved as : [$outputFilePATH] ";



 *
 */





/**
 * @param $text the text to convert into speech
 * @param $filename (optional)
 * @param $voice the voice to use for the speech
 * @return string url of the recording
 */
function textToSpeech($text, $filename = null, $voice = "Joanna")
{
    $aws_key = AWS_S3_KEY;
    $aws_secret = AWS_S3_SECRET;
    $region = AWS_S3_REGION;
    $s3bucket = AWS_S3_BUCKET;

    // $credentials    = new Credentials($aws_key, $aws_secret);

    $client_polly = new PollyClient([
        'version'     => '2016-06-10',
        // 'credentials' => $credentials,
        'region'      => $region
    ]);

    $result_polly = $client_polly->synthesizeSpeech([
        'OutputFormat' => 'pcm',
        'Text'         => $text,
        'Engine' => 'neural',
        'TextType'     => 'text',
        'VoiceId'      => $voice
    ]);

    $resultData_polly = $result_polly->get('AudioStream')->getContents();
    $content = convertPcmToWav($resultData_polly);




    $filename = "/text-to-speech/" . uniqid(time()) . ".wav";

    $client_s3 = new Aws\S3\S3Client([
        'version'     => 'latest',
        'credentials' => $credentials,
        'region'      => $region
    ]);

    $result_s3 = $client_s3->putObject([
        'Key'         => $filename,
        'ACL'         => 'public-read',
        'Body'        => $content,
        'Bucket'      => $s3bucket,
        'ContentType' => 'audio/wave',
        'SampleRate'  => '8000'
    ]);

    return $result_s3['ObjectURL'];
}

function convertPcmToWav($pcm)
{
    //Output file
    $pcm_size = strlen($pcm);
    $size = 36 + $pcm_size;
    $chunk_size = 16;
    $audio_format = 1;
    $channels = 1; //mono
    /**From the AWS Polly documentation: Valid values for pcm are "8000" and "16000" The default value is "16000".
     * https://docs.aws.amazon.com/polly/latest/dg/API_SynthesizeSpeech.html#polly-SynthesizeSpeech-request-OutputFormat
     **/
    $sample_rate = 16000; //Hz
    $bits_per_sample = 16;
    $block_align = $channels * $bits_per_sample / 8;
    $byte_rate = $sample_rate * $channels * $bits_per_sample / 8;

    $content = 'RIFF';

    $content .= pack('I', $size);
    $content .= 'WAVE';

    //fmt sub-chunk
    $content .= 'fmt ';

    $content .= pack('I', $chunk_size);
    $content .= pack('v', $audio_format);
    $content .= pack('v', $channels);
    $content .= pack('I', $sample_rate);
    $content .= pack('I', $byte_rate);
    $content .= pack('v', $block_align);
    $content .= pack('v', $bits_per_sample);

    //data sub-chunk

    $content .= 'data';
    $content .= pack('i', $pcm_size);
    $content .= $pcm;
    return $content;
}