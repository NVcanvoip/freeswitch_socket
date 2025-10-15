<?php
$dir = __DIR__ ;
$dir = str_replace("local_scripts","",$dir);


include_once $dir.'/settings.php';
include_once $dir.'/db_connect.php';


function event_socket_create($host, $port, $password) {
    $fp = fsockopen($host, $port, $errno, $errdesc)
    or die("Connection to $host failed");
    socket_set_blocking($fp,false);

    if ($fp) {
        while (!feof($fp)) {
            $buffer = fgets($fp, 1024);
            usleep(100); //allow time for response
            if (trim($buffer) == "Content-Type: auth/request") {
                fputs($fp, "auth $password\n\n");
                break;
            }
        }
        return $fp;
    }
    else {
        return false;
    }
}


function event_socket_request($fp, $cmd) {

    if ($fp) {
        fputs($fp, $cmd."\n\n");
        usleep(500000); //allow time for response

        $response = "";
        $i = 0;
        $contentlength = 0;
        while (!feof($fp)) {
            $buffer = fgets($fp, 4096);
            if ($contentlength > 0) {
                $response .= $buffer;
            }

            if ($contentlength == 0) { //if contentlenght is already don't process again
                if (strlen(trim($buffer)) > 0) { //run only if buffer has content
                    $temparray = explode(":", trim($buffer));
                    if ($temparray[0] == "Content-Length") {
                        $contentlength = trim($temparray[1]);
                    }
                }
            }

            usleep(100); //allow time for reponse

            //optional because of script timeout //don't let while loop become endless
            if ($i > 1000) { break; }

            if ($contentlength > 0) { //is content length set
                //stop reading if all content has been read.
                if (strlen($response) >= $contentlength) {
                    break;
                }
            }
            $i++;
        }

        return $response;
    }
    else {
        echo "no handle";
    }
}


function event_socket_request_average($fp, $cmd) {

    if ($fp) {
        fputs($fp, $cmd."\n\n");
        usleep(500000); //allow time for response

        $response = "";
        $i = 0;
        $contentlength = 0;
        while (!feof($fp)) {
            $buffer = fgets($fp, 4096);
            if ($contentlength > 0) {
                $response .= $buffer;
            }

            if ($contentlength == 0) { //if contentlenght is already don't process again
                if (strlen(trim($buffer)) > 0) { //run only if buffer has content
                    $temparray = explode(":", trim($buffer));
                    if ($temparray[0] == "Content-Length") {
                        $contentlength = trim($temparray[1]);
                    }
                }
            }

            usleep(100); //allow time for response

            //optional because of script timeout //don't let while loop become endless
            if ($i > 100) { break; }

            if ($contentlength > 0) { //is content length set
                //stop reading if all content has been read.
                if (strlen($response) >= $contentlength) {
                    break;
                }
            }
            $i++;
        }

        return $response;
    }
    else {
        echo "no handle";
    }
}


function event_socket_request_fast($fp, $cmd) {

    if ($fp) {
        fputs($fp, $cmd."\n\n");
        usleep(50); //allow time for response

        $response = "";
        $i = 0;
        $contentlength = 0;
        while (!feof($fp)) {
            $buffer = fgets($fp, 4096);
            if ($contentlength > 0) {
                $response .= $buffer;
            }

            if ($contentlength == 0) { //if contentlenght is already don't process again
                if (strlen(trim($buffer)) > 0) { //run only if buffer has content
                    $temparray = explode(":", trim($buffer));
                    if ($temparray[0] == "Content-Length") {
                        $contentlength = trim($temparray[1]);
                    }
                }
            }

            usleep(100); //allow time for response

            //optional because of script timeout //don't let while loop become endless
            if ($i > 100) { break; }

            if ($contentlength > 0) { //is content length set
                //stop reading if all content has been read.
                if (strlen($response) >= $contentlength) {
                    break;
                }
            }
            $i++;
        }

        return $response;
    }
    else {
        echo "no handle";
    }
}








?>
