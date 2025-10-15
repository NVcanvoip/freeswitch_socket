<?php
$userId = login_check_get_user_ID($mysqli);
$reseller = 0;

if ($userId>0){




?>

    <!-- addDIDModal -->
    <div class="modal fade" id="addDIDModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="exampleModalLabel">Add virtual DID number</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <form id="addDIDModalForm">

                        <div class="form-group">
                            <label>Domain</label>
                            <select class="form-control" name="add_did_customer_id" id="add_did_customer_id">
                                <?php
                                echo getCustomersListForSelectView($mysqli);
                                ?>
                            </select>
                        </div>






                        <div class="form-group">
                            <label>Domain</label>
                            <select class="form-control" name="new_did_domain" id="new_did_domain">
                                <?php
                                //echo getDomainsListForSelectView($customerID,$mysqli);

                                ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>DID provider</label>
                            <select class="form-control" name="new_did_provider" id="new_did_provider">
                                <?php
                                echo getDidProvidersListForSelectView($mysqli);
                                ?>
                            </select>
                        </div>

                        <hr/>
                        <div class="form-group">
                            <label class="form-control" for="did_registration_requierd">
                                <input type="checkbox" id="did_registration_requierd" name="did_registration_requierd"> DID provider requires registration
                            </label>

                        </div>
                        <div id="sip_registration_area">


                            <div class="form-group">
                                <label>SIP username</label>
                                <input type="text" class="form-control" id="did_sip_username" name="did_sip_username" placeholder="SIP username"/>
                            </div>
                            <div class="form-group">
                                <label>SIP password</label>
                                <input type="text" class="form-control" id="did_sip_password" name="did_sip_password" placeholder="SIP password"/>
                            </div>
                            <div class="form-group">
                                <label>SIP Registrar address</label>
                                <input type="text" class="form-control" id="did_sip_registrar" name="did_sip_registrar" placeholder="SIP registrar address"/>
                            </div>

                        </div>
                        <hr/>

                        <div class="form-group">
                            <label>DID number</label>
                            <input type="text" class="form-control" id="did_number" name="did_number" placeholder="DID number"/>
                            <input type="hidden" name="add_did_number_selected_action_def" id="add_did_number_selected_action_def" value="" minlength="4" />
                        </div>
                        <div class="form-group">
                            <select class="form-control" name="add_did_number_action_type" id="add_did_number_action_type" required>
                                <option value="QUEUE">Queue</option>
                                <option value="USER">User</option>
                                <option value="IVR">IVR menu</option>
                                <option value="CONFERENCE">Conference</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <select class="form-control" name="add_did_number_action_def" id="add_did_number_action_def" required>

                            </select>
                        </div>

                </div>
                <div class="modal-footer">

                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <input type="submit" name="submit" class="btn btn-primary" id="submitAddDIDModal" value="Add virtual DID number"/>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- /addDIDModal -->







    <!-- Page Header -->
<div class="page-header row no-gutters py-4">
    <div class="col-12 col-sm-4 text-center text-sm-left mb-0">
        <span class="text-uppercase page-subtitle">Inventory</span>
        <h3 class="page-title">DIDs</h3>
    </div>
</div>
<!-- End Page Header -->



    <div class="border-bottom clearfix d-flex">
        <ul class="nav nav-tabs border-0 mt-auto mx-4 pt-2" id="didsInventoryTabs">

            <li class="nav-item">
                <a data-toggle="tab" class="nav-link active" href="#provisioned_numbers">Provisioned numbers</a>
            </li>

            <li class="nav-item">
                <a data-toggle="tab" class="nav-link" href="#countries_areas">Countries / Areas</a>
            </li>

            <li class="nav-item">
                <a data-toggle="tab" class="nav-link" href="#internal_inventory">Internal inventory</a>
            </li>

            <li class="nav-item">
                <a data-toggle="tab" class="nav-link" href="#external_inventory">External inventory</a>
            </li>




        </ul>
    </div>


    <div class="tab-content">


        <!--   Tab - provisioned_numbers   -->
        <div id="provisioned_numbers" class="tab-pane active">

            <br/>
            <div class="col-12 col-sm-12">
                <table id="provisionedDIDsTable" class=" display compact"  style="width:100%">
                    <thead class="bg-light">
                    <tr>
                        <th>DID number</th>
                        <th>Provider</th>
                        <th>Customer</th>
                        <th>Domain</th>
                        <th>Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                    </thead>
                    <tbody>




                    <?php

                    $didsInventoryList= getListOfDIDsInventory($mysqli);

                    echo $didsInventoryList;


                    ?>



                    </tbody>
                    <tfoot>

                    </tfoot>
                </table>
            </div>

            <br/><br/>
            <div class="form-group mb-0">

                <button class="btn btn-accent" data-toggle="modal" data-target="#addDIDModal">Add new DID</button>

            </div>
        </div>


        <!--   Tab - countries_areas   -->
        <div id="countries_areas" class="tab-pane fade">

            <br/>
            <div class="col-12 col-sm-12">
                <table id="customerUsersTable" class=" display compact"  style="width:100%">
                    <thead class="bg-light">
                    <tr>
                        <th>Name</th>
                        <th>Extension</th>
                        <th>Domain</th>
                        <th>Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                    </thead>
                    <tbody>




                    <?php

                    //$listOfUsers= getUsersListForCustomerDetailsPage($customerID,$mysqli);

                    //echo $listOfUsers;


                    ?>



                    </tbody>
                    <tfoot>

                    </tfoot>
                </table>
            </div>

            <br/><br/>
            <div class="form-group mb-0">
                <button class="btn btn-accent" data-toggle="modal" data-target="#addUserModal">Provision new number</button>

            </div>
        </div>



        <!--   Tab - internal_inventory   -->
        <div id="internal_inventory" class="tab-pane fade">

            <br/>
            <div class="col-12 col-sm-12">
                <table id="customerUsersTable" class=" display compact"  style="width:100%">
                    <thead class="bg-light">
                    <tr>
                        <th>Name</th>
                        <th>Extension</th>
                        <th>Domain</th>
                        <th>Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                    </thead>
                    <tbody>




                    <?php

                    //$listOfUsers= getUsersListForCustomerDetailsPage($customerID,$mysqli);

                    //echo $listOfUsers;


                    ?>



                    </tbody>
                    <tfoot>

                    </tfoot>
                </table>
            </div>

            <br/><br/>
            <div class="form-group mb-0">
                <button class="btn btn-accent" data-toggle="modal" data-target="#addUserModal">Provision new number</button>

            </div>
        </div>

        <!--   Tab - external_inventory   -->
        <div id="external_inventory" class="tab-pane fade">

            <br/>
            <div class="col-12 col-sm-12">
                <table id="customerUsersTable" class=" display compact"  style="width:100%">
                    <thead class="bg-light">
                    <tr>
                        <th>Name</th>
                        <th>Extension</th>
                        <th>Domain</th>
                        <th>Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                    </thead>
                    <tbody>




                    <?php

                    //$listOfUsers= getUsersListForCustomerDetailsPage($customerID,$mysqli);

                    //echo $listOfUsers;


                    ?>



                    </tbody>
                    <tfoot>

                    </tfoot>
                </table>
            </div>

            <br/><br/>
            <div class="form-group mb-0">
                <button class="btn btn-accent" data-toggle="modal" data-target="#addUserModal">Provision new number</button>

            </div>
        </div>





    </div>

















<script>
        function getFormData($form){
            var unindexed_array = $form.serializeArray();
            var indexed_array = {};

            $.map(unindexed_array, function(n, i){
                indexed_array[n['name']] = n['value'];
            });

            return indexed_array;
        }



</script>



        <script language="JavaScript">
    $(document).ready( function () {

        $('#provisionedDIDsTable').DataTable({
            responsive: true,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
            searching: true,
            ordering:  false,
            paging: true,
            pagingType: "simple_numbers"
        });



        $("#addDIDModalForm").on('submit', function(e){
            e.preventDefault();
            $('#submitEditDIDModal').prop("disabled", true);
            var $form = $("#addDIDModalForm");
            var data = getFormData($form);

            $.ajax({
                type: "POST",
                url: "engine/executeCommand.php?op=addVDID",
                dataType: "json",
                data: data,
                success: function (msg) {
                    $("#addDIDModal").modal('hide');

                    if(msg.status == "error"){
                        alert(msg.message);
                    }

                    location.reload();
                },
                error: function () {
                    $("#addDIDModal").modal('hide');
                    alert("Error when saving the data. Please check the logs or contact administrator.");
                }
            });


        });




    } );

</script>
<?php


}
?>
