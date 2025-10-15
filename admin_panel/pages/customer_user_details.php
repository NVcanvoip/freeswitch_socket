<?php
$userId = login_check_get_user_ID($mysqli);
if ($userId>0){

    // SECURITY - need to check user ID and user type
    $userId = login_check_get_user_ID($mysqli);


    $userID= test_input($_GET["id"]);
    $userName = getUserNameByID($userID,$mysqli);

    $userDetails = getUserDetailsByID($userID,$mysqli);


    /*
     *
            $response["id"] = $id;
            $response["customer"] = $customer;
            $response["name"] = $name;
            $response["username"] = $username;
            $response["type"] = $type;
            $response["sip_password"] = $sip_password;
            $response["record_internal"] = $record_internal;
            $response["record_incoming"] = $record_incoming;
            $response["record_external"] = $record_external;
            $response["vm_password"] = $vm_password;
     *
     */





    ?>

    <br/>

    <!-- Small Stats Blocks -->
    <div class="row">
        <div class="col-lg-6 col-md-6 col-sm-6">
            <!-- Left side -->
            <span class="text-uppercase page-subtitle"><a href="dashboard.php?p=customer_details&id=<?php echo "1";  ?>"> <- back</a>    |     PBX details</span><br/>
            <div class="row">
                <div class="col-lg col-md-12 col-sm-12 mb-12">
                    <h5><?php echo $userName; ?>  -  User details</h5>
                </div>

            </div>


            <br/>









        </div>

        <div class="col-lg-6 col-md-6 col-sm-6">
            <!-- Right side -->








        </div>
    </div>


    <?php

}
?>


<script>
    $(document).ready(function() {
        $.ajaxSetup({ cache: false }); // This part addresses an IE bug.







    });



</script>