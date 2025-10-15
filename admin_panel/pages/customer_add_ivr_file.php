<?php
$userId = login_check_get_user_ID($mysqli);
$reseller = 0;

if ($userId>0 && isset($_GET["id"])){

$customerID = test_input($_GET["id"]);













    ?>

    <script>

        function senddata(filename,file_description) {
            var customerID = "<?php echo $customerID; ?>";
            $.ajax({
                type: "POST",
                url: "/engine/executeCommand.php?op=uploadIvrFile",
                data: {
                    file: filename,
                    customer:  customerID,
                    file_description: file_description
                },
                async: true,
                success: function (res) {
                    window.location = window.location.href;
                }
            })
        }


    </script>

<!-- Page Header -->
<div class="page-header row no-gutters py-4">
    <div class="col-12 col-sm-4 text-center text-sm-left mb-0">
        <span class="text-uppercase page-subtitle"> <a href="dashboard.php?p=customer_details&id=<?php echo $customerID;  ?>"> <- back</a>    | Add new IVR media file</span>
        <h3 class="page-title"><?php  echo $dialerName; ?></h3>
    </div>
</div>
<!-- End Page Header -->




            <form id="dialer_details_general_form" method="post" action="#" enctype="multipart/form-data" >

                <div class="form-row mx-4">
                    <div class="col-lg-6 col-md-6 col-sm-12 ">
                        <div class="form-row">
                            <div class="form-group col-lg-6 col-md-6 col-sm-12">
                                <label for="firstName">File friendly name</label>
                                <input type="text" class="form-control" id="ivrFileName" name="ivrFileName" required> </div>

                            <div class="form-group col-lg-6 col-md-6 col-sm-12">

                            </div>


                        </div>
                    </div>

                </div>


                <div class="form-row mx-4">
                    <div class="col-lg-6 col-sm-12 col-md-6">

                        <input type="file" class="form-control"  name="file" id="file"  />


                    </div>
                </div>


                <br/>

                <?php

                if ( isset($_POST["submit"]) ) {

                    // get params:

                    $ivrFileName= test_input($_POST["ivrFileName"]);


                    if (!empty($_FILES["file"])) {
                        $myFile = $_FILES["file"];

                        if ($myFile["error"] !== UPLOAD_ERR_OK) {

                            echo   json_encode($myFile);
                            echo "<p>An error occurred, was the file selected properly?</p>";
                            exit;
                        }

                        // ensure a safe filename
                        $name = preg_replace("/[^A-Z0-9._-]/i", "_", $myFile["name"]);

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

                        $targetFileName = $fileDatePart . $fileName;


                        $uploadTargetFile = PBX_IVR_FILES_BASE .  $customerID . '/'. $fileDatePart .$fileName;

                        // create directory if it doesn't exist yet.
                        mkdir(PBX_IVR_FILES_BASE . $customerID,0777);



                        // preserve file from temporary directory
                        $success = move_uploaded_file($myFile["tmp_name"],
                            $uploadTargetFile);

                        if (!$success) {
                            echo "<p>Unable to save file.</p>";
                            exit;
                        }

                        // set proper permissions on the new file
                        chmod($uploadTargetFile, 0777);






                        echo "<script> senddata('". $uploadTargetFile."','". $ivrFileName ."'); </script>";

                        echo "File uploaded successfully.";



                    }else{
                        echo "Upload error. No file selected!";

                    }


                }




                ?>

             <br/>
                <button type="submit" name="submit" class="mb-2 btn btn-primary mr-2">Upload file</button>

            <br/>
            </form>














<script language="JavaScript">
    $(document).ready( function () {



    } );



</script>
<?php


}
?>
