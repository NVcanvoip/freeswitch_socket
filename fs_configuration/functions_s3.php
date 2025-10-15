<?php
$dir = __DIR__ ;
$dir = str_replace("fs_configuration","",$dir);
require_once $dir . "/lib/aws/vendor/autoload.php";

use Aws\S3\S3Client;

function s3_putObject_recording_file($domain,$fileName,$acl="public-read"){

  $aws_key    = AWS_S3_KEY;
  $aws_secret = AWS_S3_SECRET;
  $region     = AWS_S3_REGION;
  $s3bucket   = AWS_S3_BUCKET;

  $keyname    = "recordings/" . $domain . "/" . $fileName;
  $sourceFile = PBX_RECORDING_FILES_BASE . "/" . $domain . "/" .$fileName ;    // '/opt/ctpbx/recordings/d1.callertech.net/49a4c0e3-e4ef-453b-968c-ca8b3729e825.wav'

  // Instantiate an Amazon S3 client.
  $s3 = new S3Client([
    'version'     => 'latest',
    'region'      => $region,
    // 'credentials' => array(
    //   'key'         => $aws_key,
    //   'secret'      => $aws_secret,
    //   )
  ]);

  // 1. Upload a privately accessible file. The file size and type are determined by the SDK.
  try {
    $result = $s3->putObject([
      'Bucket' => $s3bucket,
      'Key'    => $keyname,
      'Body'   => fopen($sourceFile, 'r'),
      'ACL'    => $acl,
    ]);
    $resultArr = $result->toArray();
    //error_log(json_encode($resultArr));
    $objectURL = $result['ObjectURL'];
    return $objectURL;
  } catch (Aws\S3\Exception\S3Exception $e) {
    error_log("There was an error uploading the file $sourceFile. " . $e->getMessage());
    return "";
  }
}

function s3_putObject_vm_file($domain, $srcName, $dstName, $acl="public-read"){
  $aws_key    = AWS_S3_KEY;
  $aws_secret = AWS_S3_SECRET;
  $region     = AWS_S3_REGION;
  $s3bucket   = AWS_S3_BUCKET;
  $keyname    = "voicemail/" . $domain . "/" . $dstName;

  // Instantiate an Amazon S3 client.
  $s3 = new S3Client([
    'version'     => 'latest',
    'region'      => $region,
    // 'credentials' => array(
    // 'key'         => $aws_key,
    // 'secret'      => $aws_secret,
    // )
  ]);

  // 1. Upload a privately accessible file. The file size and type are determined by the SDK.
  try {
    $result = $s3->putObject([
      'Bucket' => $s3bucket,
      'Key'    => $keyname,
      'Body'   => fopen($srcName, 'r'),
      'ACL'    => $acl,
    ]);
    $resultArr = $result->toArray();
    //error_log(json_encode($resultArr));
    $objectURL = $result['ObjectURL'];
    return $objectURL;
  } catch (Aws\S3\Exception\S3Exception $e) {
    error_log("There was an error uploading the file $srcName. " . $e->getMessage());
    return "";
  }
}
