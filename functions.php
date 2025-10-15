<?php

include_once 'settings.php';
include_once 'db_connect.php';


function logger_addLogEntry($severity,$source,$account,$message, $mysqli,$customer = 0){

    if ($severity < LOGGER_MIN_LEVEL) return true;



    if ($stmt = $mysqli->prepare("INSERT INTO log_operation (severity, source, account, message,customer) VALUES (
                      ?,?,?,?,?)")) {
        // Bind "$user_id" to parameter.
        $stmt->bind_param('ssssi', $severity,$source,$account,$message,$customer);
        $stmt->execute();   // Execute the prepared query.
        $stmt->close();
        if($mysqli->error){
            return false;
        }
    } else {
        return true;
    }

    return true;
}


// =====

 // check the input string

function test_input($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}





function get2DArrayFromCsv($file,$delimiter) {
    if (($handle = fopen($file, "r")) !== FALSE) {
        $i = 0;
        while (($lineArray = fgetcsv($handle, 4000, $delimiter)) !== FALSE) {
            for ($j=0; $j<count($lineArray); $j++) {
                $data2DArray[$i][$j] = trim(str_replace('""','',$lineArray[$j]));
            }
            $i++;
        }
        fclose($handle);
    }
    return $data2DArray;
}





function tz_list() {
    $zones_array = array();
    $timestamp = time();
    foreach(timezone_identifiers_list() as $key => $zone) {
        date_default_timezone_set($zone);
        $zones_array[$key]['zone'] = $zone;
        $zones_array[$key]['diff_from_GMT'] = 'UTC/GMT ' . date('P', $timestamp);
    }
    return $zones_array;
}




function generateRandomString($length = 10) {
    return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) )),1,$length);
}


//using php curl (sudo apt-get install php-curl)
function httpPostViaCURL($url, $data){
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($curl);
    curl_close($curl);

    $dataJSON = json_encode($data);
    error_log("httpPostViaCURL URL: [$url]   DATA: [$dataJSON]");


    return $response;
}









?>
