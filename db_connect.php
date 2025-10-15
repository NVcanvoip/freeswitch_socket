<?php


include_once 'settings.php';



$mysqli = new mysqli(HOST, USER, PASSWORD, DATABASE);

$mysqliRO = new  mysqli(HOSTRO, USER, PASSWORD, DATABASE);

$mysqli_proxy = new mysqli(HOSTPROXY, USERPROXY, PASSWORDPROXY, DATABASEPROXY);


if($mysqli->connect_error){

   error_log($mysqli->connect_error);
}



if($mysqliRO->connect_error){
    error_log("RO database connection error"  . $mysqliRO->connect_error);
    $mysqliRO = new mysqli(HOST, USER, PASSWORD, DATABASE);
}


if($mysqli_proxy->connect_error){
    error_log("PROXY database connection error"  . $mysqli_proxy->connect_error);
}





?>