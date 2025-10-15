<?php
setlocale(LC_ALL, "en_US.UTF-8");

include_once '../settings.php';



include_once '../db_connect.php';
include_once '../functions.php';
include_once 'engine/functions_login.php';
include_once 'engine/functions_admin_portal.php';

include_once '../fs_configuration/functions_vtpbx_fs.php';


sec_session_start();

if(login_check($mysqli)) {
    $userID = login_check_get_user_ID($mysqli);



    $pageName = "nocd";

    if(isset($_GET['p'])){
        $pageName = test_input($_GET['p']);

    }


    $userName = getUserNameByID($userID,$mysqli);











    ?>

<!doctype html>
<html class="no-js h-100" lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>CTPBX - Dashboard</title>
    <meta name="description" content="CTDial Admin">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
      <script src="https://code.jquery.com/jquery-3.3.1.min.js" integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8=" crossorigin="anonymous"></script>
    <link href="https://use.fontawesome.com/releases/v5.0.6/css/all.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
    <link rel="stylesheet" id="main-stylesheet" data-version="1.0.0" href="styles/shards-dashboards.1.0.0.min.css">
    <link rel="stylesheet" href="styles/extras.1.0.0.min.css">


      <link rel="stylesheet" href="https://cdn.datatables.net/1.10.19/css/jquery.dataTables.min.css">
      <link rel="stylesheet" href="https://cdn.datatables.net/1.10.19/css/dataTables.bootstrap4.min.css">

      <!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/material-design-lite/1.1.0/material.min.css">
      <link rel="stylesheet" href="https://cdn.datatables.net/1.10.19/css/dataTables.material.min.css">   -->


      <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
      <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
      <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.1/Chart.min.js"></script>
      <script src="https://unpkg.com/shards-ui@latest/dist/js/shards.min.js"></script>
      <script src="https://cdnjs.cloudflare.com/ajax/libs/Sharrre/2.0.1/jquery.sharrre.min.js"></script>
      <script src="scripts/extras.1.0.0.min.js"></script>
      <script src="scripts/shards-dashboards.1.0.0.min.js"></script>
      <script src="https://cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js"></script>


      <script async defer src="https://buttons.github.io/buttons.js"></script>


  </head>
  <body class="h-100">


    <div class="container-fluid">
      <div class="row">
        <!-- Main Sidebar -->
        <aside class="main-sidebar col-12 col-md-3 col-lg-2 px-0">
          <div class="main-navbar">
            <nav class="navbar align-items-stretch navbar-light bg-white flex-md-nowrap border-bottom p-0">
              <a class="navbar-brand w-100 mr-0" href="#" style="line-height: 25px;">
                <div class="d-table m-auto">
                  <span class="d-none d-md-inline ml-1">CTPBX Admin</span>
                </div>
              </a>
              <a class="toggle-sidebar d-sm-inline d-md-none d-lg-none">
                <i class="material-icons">&#xE5C4;</i>
              </a>
            </nav>
          </div>
          <form action="#" class="main-sidebar__search w-100 border-right d-sm-flex d-md-none d-lg-none">
            <div class="input-group input-group-seamless ml-3">
              <div class="input-group-prepend">
                <div class="input-group-text">

                </div>
              </div>
              </div>
          </form>
          <div class="nav-wrapper">
            <ul class="nav flex-column">


              <li class="nav-item">
                <a class="nav-link <?php  if($pageName == "nocd" ) echo "active"; ?>" href="dashboard.php?p=nocd">
                  <i class="material-icons">assignment</i>
                  <span>Dashboard</span>
                </a>
              </li>


              <li class="nav-item">
                <a class="nav-link  <?php  if($pageName == "customers" || $pageName == "dialer_details" ||$pageName == "dialer_phonebook_details" || $pageName == "dialer_add_trunk"  || $pageName == "dialer_add_campaign"  || $pageName == "dialer_add_contact_list" || $pageName == "dialer_add_ivr_file" || $pageName == "dialer_add_new" || $pageName == "campaign_schedule") echo "active"; ?>" href="dashboard.php?p=customers">
                  <i class="material-icons">ring_volume</i>
                  <span>PBXes</span>
                </a>
              </li>





            <li class="nav-item">
                <a class="nav-link <?php  if($pageName == "dids_inventory") echo "active"; ?>" href="dashboard.php?p=dids_inventory">
                    <i class="material-icons">phone_callback</i>
                    <span>DIDs Inventory</span>
                </a>
            </li>


            <li class="nav-item">
                <a class="nav-link <?php  if($pageName == "sip_providers") echo "active"; ?>" href="dashboard.php?p=sip_providers">
                    <i class="material-icons">dialer_sip</i>
                    <span>SIP providers</span>
                </a>
            </li>



              <li class="nav-item">
                <a class="nav-link <?php  if($pageName == "settings") echo "active"; ?>" href="dashboard.php?p=settings">
                  <i class="material-icons">settings</i>
                  <span>Settings</span>
                </a>
              </li>

                <li class="nav-item">
                    <a class="nav-link <?php  if($pageName == "users") echo "active"; ?>" href="dashboard.php?p=users">
                        <i class="material-icons">group</i>
                        <span>Users</span>
                    </a>
                </li>

            </ul>
          </div>
        </aside>
        <!-- End Main Sidebar -->
        <main class="main-content col-lg-10 col-md-9 col-sm-12 p-0 offset-lg-2 offset-md-3">
          <div class="main-navbar sticky-top bg-white">
            <!-- Main Navbar -->
            <nav class="navbar align-items-stretch navbar-light flex-md-nowrap p-0">
              <form action="#" class="main-navbar__search w-100 d-none d-md-flex d-lg-flex">
                <div class="input-group input-group-seamless ml-3">
                  <div class="input-group-prepend">
                    <div class="input-group-text">



                    </div>
                  </div>
                   </div>
              </form>
              <ul class="navbar-nav border-left flex-row ">

                <li class="nav-item dropdown">
                  <a class="nav-link dropdown-toggle text-nowrap px-3" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">
                    <img class="user-avatar rounded-circle mr-2" src="images/avatars/avatar0.jpg" alt="User Avatar">
                    <span class="d-none d-md-inline-block"><?php echo $userName;  ?></span>
                  </a>
                  <div class="dropdown-menu dropdown-menu-small">
                    <a class="dropdown-item" href="user-profile-lite.html">
                      <i class="material-icons">&#xE7FD;</i> Profile</a>

                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item text-danger" href="logout.php">
                      <i class="material-icons text-danger">&#xE879;</i> Logout </a>
                  </div>
                </li>
              </ul>
              <nav class="nav">
                <a href="#" class="nav-link nav-link-icon toggle-sidebar d-md-inline d-lg-none text-center border-left" data-toggle="collapse" data-target=".header-navbar" aria-expanded="false" aria-controls="header-navbar">
                  <i class="material-icons">&#xE5D2;</i>
                </a>
              </nav>
            </nav>
          </div>
          <!-- / .main-navbar -->
          <div class="main-content-container container-fluid px-4">

<?php


    switch($pageName){


        case 'nocd': include 'pages/nocd.php';  break;

        case 'customers': include 'pages/customers.php';     break;

        case 'customer_details': include 'pages/customer_details.php';     break;

        case 'dids_inventory': include 'pages/dids_inventory.php';     break;

        case 'customer_add_new': include 'pages/customer_add_new.php';     break;

        case 'customer_queue_status': include 'pages/customer_queue_status.php';     break;

        case 'customer_queue_reports': include 'pages/customer_queue_reports.php';     break;

        case 'customer_ivr_menu_details': include 'pages/customer_ivr_menu_details.php';     break;
        case 'customer_add_ivr_file': include 'pages/customer_add_ivr_file.php';     break;

        case 'customer_user_details': include 'pages/customer_user_details.php';     break;






        case 'dialer_phonebook_details': include 'pages/dialer_phonebook_details.php';     break;
        case 'dialer_add_trunk': include 'pages/dialer_add_trunk.php';     break;
        case 'dialer_edit_trunk': include 'pages/dialer_edit_trunk.php';     break;



        case 'dialer_add_campaign': include 'pages/dialer_add_campaign.php';     break;
        case 'dialer_campaign_details': include 'pages/dialer_campaign_details.php';     break;
        case 'campaign_schedule': include 'pages/campaign_schedule.php';     break;

        case 'dialer_add_contact_list': include 'pages/dialer_add_contact_list.php';     break;
        case 'dialer_add_ivr_file': include 'pages/dialer_add_ivr_file.php';     break;

        case 'dialer_campaign_report': include 'pages/dialer_campaign_report.php';     break;

        case 'settings': include 'pages/settings.php';     break;
        case 'settings_app_server_edit': include 'pages/settings_app_server_edit.php';     break;
        case 'settings_user_edit': include 'pages/settings_user_edit.php';     break;

        case 'users': include 'pages/users.php';     break;
        case 'user_add_new': include 'pages/user_add_new.php';     break;

        case 'sip_providers': include 'pages/sip_providers.php';     break;
        case 'sip_provider_add_new': include 'pages/sip_provider_add_new.php';     break;






        default :   include_once('pages/nocd.php');
    }














?>




          </div>
          <footer class="main-footer d-flex p-2 px-3 bg-white border-top">
            <ul class="nav">
              <li class="nav-item">
                <a class="nav-link" href="#">Home</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="#">Services</a>
              </li>

            </ul>
            <span class="copyright ml-auto my-auto mr-2">Copyright © 2019
              <a href="https://voipterminator.com" rel="nofollow">VoipTerminator INC</a>
            </span>
          </footer>




        </main>
      </div>
    </div>


  </body>
</html>

    <?php

}
else{
    header("Location: index.php");
    exit();
}



?>
