<?php



function getCustomersList($mysqli){

    $sql = " SELECT c.id, c.name, c.type, (select count(*) from users where customer = c.id), c.limit_extensions FROM customers c ;";
    $stmt = $mysqli->prepare($sql);


    if($stmt){


        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();
        //$log->LogDebug('SELECT statement executed, binding result.');
        $stmt->bind_result($id,$name,$type,$users, $max_users);

        $s = '';
        while($stmt->fetch()){

            $statusIcon = 'done';  // $retType
            if($type & 1  == 1) {
                $statusIcon = 'done';
            }else{
                $statusIcon = 'clear';
            }

            if($max_users == 0) $max_users = "unlimited";



            $listOfDomains = getCustomerDomainsListAsText($id,$mysqli);




            $oneRow = '<tr>'.
                '<td>'.$id.'</td>'.
                '<td>'.$name.'</td>'.
                '<td>'. $users . ' / '.$max_users.'</td>'.
                '<td>'.$listOfDomains.'</td>'.
                '<td><div class="btn-group btn-group-sm" role="group" aria-label="Table row actions">'.
                '<button type="button" class="btn btn-white">'.
                '<i class="material-icons">'.$statusIcon.'</i>'.
                '</button>'.
                '</div></td>'.
                '<td><div class="btn-group btn-group-sm" role="group" aria-label="Table row actions">'.
                '<a href="dashboard.php?p=customer_details&id='.$id.'"><button type="button" class="btn btn-white">'.
                'Show details <i class="material-icons"></i>'.
                '</button></a>'.
                '<button type="button" class="btn btn-white" data-toggle="modal" data-target="#deletePBXModal" data-id="'.$id.'" data-name="'.$name.'">'.
                '<i class="material-icons"></i>'.
                '</button>'.
                '</div></td>'.
                '</tr>';

            $s .= $oneRow;

        }
        return $s;
    }else{
        error_log("VTPBX: error getting list of customers.");
    }
}


function getCustomersListForSelectView($mysqli){



    $sql = "select id, name from customers;";
    $stmt = $mysqli->prepare($sql);


    if($stmt){

        $stmt->bind_param('i', $dialerID);
        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();
        $stmt->bind_result($id, $name);

        $s = '';
        while($stmt->fetch()){

                $oneRow = '<option value="'. $id.'">'.$name.'</option>';
                $s .= $oneRow;

        }
        return $s;
    }else{
        error_log("VT30: error getCustomersListForSelectView : ". $mysqli->error);
    }
}






function getCustomerIDsList($mysqli){

    $sql = " SELECT id  FROM customers ;";
    $stmt = $mysqli->prepare($sql);


    if($stmt){


        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();
        //$log->LogDebug('SELECT statement executed, binding result.');
        $stmt->bind_result($id);

        $s = array();
        while($stmt->fetch()){

            $s[] = $id;

        }
        return $s;
    }else{
        error_log("VTPBX: error in getCustomerIDsList.");
    }
}


function getCustomersListMT($userDialers, $mysqli){

    $sql = "select id, name, type, max_channels, date(date_added) from customers WHERE id IN (".$userDialers.");";
    $stmt = $mysqli->prepare($sql);


    if($stmt){


        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();
        //$log->LogDebug('SELECT statement executed, binding result.');
        $stmt->bind_result($id,$name,$type,$max_channels,$dateadded);

        $s = '';
        while($stmt->fetch()){

            $statusIcon = 'done';  // $retType
            if($type & 1  == 1) {
                $statusIcon = 'done';
            }else{
                $statusIcon = 'clear';
            }

            $oneRow = '<tr>'.
                '<td>'.$id.'</td>'.
                '<td>'.$name.'</td>'.
                '<td>'.$max_channels.'</td>'.
                '<td><div class="btn-group btn-group-sm" role="group" aria-label="Table row actions">'.
                '<button type="button" class="btn btn-white">'.
                '<i class="material-icons">'.$statusIcon.'</i>'.
                '</button>'.
                '</div></td>'.
                '<td>'.$dateadded.'</td>'.
                '<td><div class="btn-group btn-group-sm" role="group" aria-label="Table row actions">'.

                '<a href="dashboard.php?p=dialer_details&id='.$id.'"><button type="button" class="btn btn-white">'.
                'Show details <i class="material-icons"></i>'.
                '</button></a>'.
                '<button type="button" class="btn btn-white" disabled>'.
                '<i class="material-icons"></i>'.
                '</button>'.
                '</div></td>'.
                '</tr>';

            $s .= $oneRow;

        }
        return $s;
    }else{
        error_log("VT30: error getting list of customers.");
    }
}



function getCustomersListForReseller($reseller, $mysqli){

    $sql = "select id, name, type, max_channels, date(date_added) from customers WHERE reseller = ? ;";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $reseller);



    if($stmt){


        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();
        //$log->LogDebug('SELECT statement executed, binding result.');
        $stmt->bind_result($id,$name,$type,$max_channels,$dateadded);

        $s = '';
        while($stmt->fetch()){

            $statusIcon = 'done';  // $retType
            if($type & 1  == 1) {
                $statusIcon = 'done';
            }else{
                $statusIcon = 'clear';
            }

            $oneRow = '<tr>'.
                '<td>'.$id.'</td>'.
                '<td>'.$name.'</td>'.
                '<td>'.$max_channels.'</td>'.
                '<td><div class="btn-group btn-group-sm" role="group" aria-label="Table row actions">'.
                '<button type="button" class="btn btn-white">'.
                '<i class="material-icons">'.$statusIcon.'</i>'.
                '</button>'.
                '</div></td>'.
                '<td>'.$dateadded.'</td>'.
                '<td><div class="btn-group btn-group-sm" role="group" aria-label="Table row actions">'.

                '<a href="dashboard.php?p=dialer_details&id='.$id.'"><button type="button" class="btn btn-white">'.
                'Show details <i class="material-icons"></i>'.
                '</button></a>'.
                '<button type="button" class="btn btn-white" disabled>'.
                '<i class="material-icons"></i>'.
                '</button>'.
                '</div></td>'.
                '</tr>';

            $s .= $oneRow;

        }
        return $s;
    }else{
        error_log("VT30: error getting list of customers.");
    }
}




function getCampaignsForCustomerList($dialerID,$mysqli){

    $sql = " select id, name,channels,caller_id,phonebooks,vm_ivr,ok_input,ok_extension,ok_trunk,ok_ivr,dnc_input,dnc_ivr,status,date_added,round(progress,0) FROM dialer_campaigns WHERE customer = ? order by id desc;";
    $stmt = $mysqli->prepare($sql);


    if($stmt){

        $stmt->bind_param('i', $dialerID);
        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();
        $stmt->bind_result($id, $name,$channels,$caller_id, $phonebooks,$vm_ivr,$ok_input,$ok_extension,$ok_trunk,$ok_ivr,$dnc_input,$dnc_ivr,$status,$date_added,$progress);

        $s = '';
        while($stmt->fetch()){

            $statusIcon = 'done';  // $retType
            $startStopIcon = "play_circle_outline";
            $statusText = "Stopped";

            /*
             *  Icons:
             *      -  play_circle_outline    :   button used to START campaign. Displayed when the campaign is stopped.
             *      - pause_circle_outline   : button used to STOP/PAUSE campaign. Displayed when the campaign is stopped.
             *      - snooze :  clock, the campaign is working according to schedule
             *
             *
             *      -  query_builder    :  clock icon, Link to set up the schedule
             *
             *
             *
             *  Campaign status:
             *    0   - stopped, not active  (manual or shedule - it's stopped anyway)
             *    1   - running now   (when running manually)
             *    2   - Schedule    (campaign is in schedule mode, it will run unless it's stopped.
             */
            $newStatus = 1;

            if($status  == 1) {
                // the campaign is running now
                $statusText = "Running";
                $startStopIcon = "pause_circle_outline";
                $newStatus = 0;
            }


            if($status  == 2) {
                // the campaign is working according to schedule
                $statusText = "Schedule : ";
                $startStopIcon = "stop";
                $newStatus = 0;
                //TODO:  find out what should be the campaign status now according to schedule.



                if( campaignCheckDialingHoursNow($id,$mysqli)){
                    $statusText = $statusText . "active";
                }else{

                    $statusText = $statusText . "<small>waiting</small>";
                }


            }

            //$progress = rand(0,100);

           // $progress = getCampaignProgress($id,$mysqli );


            $phonebooksArr = json_decode($phonebooks,true);

            $phonebooksView = "";
            foreach ($phonebooksArr as $item) {
                $phonebooksView = $phonebooksView . getPhonebookNameByID($item,$mysqli);
                $phonebooksView = $phonebooksView . ",";
            }

            $phonebooksView = substr($phonebooksView,0,-1);


            $oneRow = '<tr>'.
                '<td>'.$name.'</td>'.
                '<td>'.$channels.'</td>'.
                '<td>'.$phonebooksView.'</td>'.
                '<td>'.$statusText. '</td>'.
                '<td><div class="progress progress-sm"><div name="progress-bar-for-campaign'.$id.'" class="progress-bar bg-warning" role="progressbar" style="width: '.$progress.'%" aria-valuenow="'.$progress.'" aria-valuemin="0" aria-valuemax="100"> </div></div>['. $progress .'%]</td>'.
                '<td class="text-center"><div class="btn-group btn-group-sm" role="group" aria-label="Table row actions">'.
                '<button type="button" class="btn btn-white campaignAction" data-id="'.$id.'" data-current_status="'.$status.'" data-new_status="'.$newStatus.'">'.
                'Start/stop <i class="material-icons">'.$startStopIcon.'</i>'.
                '</button>'. '<div class="btn-group btn-group-sm" role="group" aria-label="Table row actions">'.
                '<a href="dashboard.php?p=campaign_schedule&id='.$id.'"><button type="button" class="btn btn-white">'.
                'Scheduler <i class="material-icons">query_builder</i>'.
                '</button></a>'.
                '<a href="dashboard.php?p=dialer_campaign_report&id='.$id.'"><button type="button" class="btn btn-white">'.
                'Report <i class="material-icons">description</i>'.
                '</button></a>'.
                '<a href="dashboard.php?p=dialer_campaign_details&id='.$id.'"><button type="button" class="btn btn-white">'.
                ' <i class="material-icons"></i>'.
                '</button></a>'.
                '<button  class="btn btn-white" data-toggle="modal" data-target="#deleteCampaignModal" data-id="'.$id.'" data-name="'.$name.'"><i class="material-icons"></i></button>'.
                '</div></td>'.
                '</tr>';

            $s .= $oneRow;

        }
        return $s;
    }else{
        error_log("VT30: error getting list of campaigns for customer.");
    }


}



// =======

function  getListOfCustomerCampaignsArr($dialerID,$mysqli){

    $returnArray = array();


    $sql = "select id  from dialer_campaigns where customer = ?;";

    if ($stmt = $mysqli->prepare($sql)) {

        $stmt->bind_param('i', $dialerID);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($id);

        while($stmt->fetch()){
            $returnArray[] = $id;

        }

        $stmt->close();

        if($mysqli->error){
            return false;
        }
    } else {
        return false;
    }
    return $returnArray;

}


function getCustomerQueuesListView($customerID,$mysqli){

    $sql = "select id,name from queues where customer  = ?;";
    $stmt = $mysqli->prepare($sql);


    if($stmt){

        $stmt->bind_param('i', $customerID);
        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();
        $stmt->bind_result($id, $name);

        $s = '';
        while($stmt->fetch()){



            $oneRow = '<tr>'.
                '<td>'.$id.'</td>'.
                '<td>' . $name .'</td>'.
                '<td class="text-center"><div class="btn-group btn-group-sm" role="group" aria-label="Table row actions">'.
                '<a href="dashboard.php?p=customer_queue_status&id='.$id.'"><button type="button" class="btn btn-white">Monitor <i class="material-icons">assessment</i></a>'.
                '<a href="dashboard.php?p=customer_queue_reports&id='.$id.'"><button type="button" class="btn btn-white">Reports <i class="material-icons">table_chart</i></a>'.
                '<a href="dashboard.php?p=customer_queue_details&id='.$id.'"><button type="button" class="btn btn-white" >Edit <i class="material-icons"></i></button></a>'.
                '<button type="button" class="btn btn-white"   data-toggle="modal" data-target="#deleteQueueModal" data-id="'.$id.'" data-name="'.$name.'">Delete <i class="material-icons"></i></button>'.
                '</div></td>'.
                '</tr>';

            $s .= $oneRow;

        }
        return $s;
    }else{
        error_log("VTPBX: error getting list of queues for customer.");
    }


}

// getPhonebooksForCustomerList


function getPhonebooksForCustomerList($dialerID,$mysqli){

    $sql = "select pb.id, pb.records_count ,pb.name, pb.description, pb.date_added from phonebooks pb where pb.customer  = ? order by pb.id DESC;";
    $stmt = $mysqli->prepare($sql);


    if($stmt){

        $stmt->bind_param('i', $dialerID);
        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();
        $stmt->bind_result($id, $contacts,$name, $description, $date_added);
        $s = '';
        while($stmt->fetch()){



            $oneRow = '<tr>'.
                '<td>'.$name.'</td>'.
                '<td>'.$contacts.'</td>'.
                '<td>' . $description .'</td>'.
                '<td>' . $date_added . '</td>'.
                '<td class="text-center"><div class="btn-group btn-group-sm" role="group" aria-label="Table row actions">'.
                '<a href="dashboard.php?p=dialer_phonebook_details&id='.$id.'"><button type="button" class="btn btn-white">'.
                'Edit <i class="material-icons"></i>'.
                '</button></a>'.
                '<button  class="btn btn-white" data-toggle="modal" data-target="#deletePhonebookModal" data-id="'.$id.'" data-name="'.$name.'"><i class="material-icons"></i></button>'.
                '</div></td>'.
                '</tr>';

            $s .= $oneRow;

        }
        return $s;
    }else{
        error_log("VT30: error getting list of phonebooks for customer.");
    }


}

function setPhonebookModifiedDateToNow($phonebookID, $mysqli){


    $sql = "UPDATE phonebooks set date_updated = now() WHERE id = ?;";


    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('i', $phonebookID);
    $stmt->execute();


    return true;


}



function portal_updatePhonebookRowsCount($phonebookID,$mysqli){


    $sql = "update phonebooks set records_count = (select count(*) from phonebook_data WHERE phonebook = ?) WHERE id = ?;";

    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('ii', $phonebookID,$phonebookID);
    $stmt->execute();

    return true;


}



function getPhonebooksForCustomerArr($dialerID,$mysqli){

    $sql = "select pb.id,pb.name, pb.description, pb.date_added from phonebooks pb where pb.customer  = ? order by pb.id DESC;";
    $stmt = $mysqli->prepare($sql);

    $returnArr = array();

    if($stmt){

        $stmt->bind_param('i', $dialerID);
        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();
        $stmt->bind_result($id,$name, $description, $date_added);
        $s = '';
        while($stmt->fetch()){

            $returnArr[] = $id;


        }
        return $returnArr;
    }else{
        error_log("Error: getPhonebooksForCustomerArr");
        return null;
    }


}



function getPhonebookNameByID($phonebook,$mysqli){

    $sql = "select name from phonebooks where id = ?;";


    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('i', $phonebook);
    $stmt->execute();
    $stmt->bind_result($retName);
    $stmt->fetch();

    return $retName;

}


function getTotalCountInPhonebookByID($phonebookID,$mysqli){

    $sql = "select count(*) from phonebook_data  where phonebook = ?;";


    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('i', $phonebookID);
    $stmt->execute();
    $stmt->bind_result($retTotal);
    $stmt->fetch();

    return $retTotal;

}


function getDialerIDByPhonebookByID($phonebookID,$mysqli){

    $sql = "select customer from phonebooks  where id = ?;";


    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('i', $phonebookID);
    $stmt->execute();
    $stmt->bind_result($retTotal);
    $stmt->fetch();

    return $retTotal;

}


function getDialerIDByCampaignID($campaignID,$mysqli){

    $sql = "select customer from dialer_campaigns  where id = ?;";


    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('i', $campaignID);
    $stmt->execute();
    $stmt->bind_result($retTotal);
    $stmt->fetch();

    return $retTotal;

}



function getPhonebookDataList($phonebookID,$mysqli){

    $sql = "select id,number from phonebook_data where phonebook  = ? LIMIT 1000;";
    $stmt = $mysqli->prepare($sql);


    if($stmt){

        $stmt->bind_param('i', $phonebookID);
        $stmt->execute();   // Execute the prepared query.
       // $stmt->store_result();
        $stmt->bind_result($id, $number);

        $s = '';
        while($stmt->fetch()){



            $oneRow = '<tr>'.
                '<td>'.$number.'</td>'.
                '<td class="text-center"><div class="btn-group btn-group-sm" role="group" aria-label="Table row actions">'.
                '<a href="dashboard.php?p=dialer_phonebook_data_edit&id='.$id.'"><button type="button" class="btn btn-white">'.
                'Edit <i class="material-icons"></i>'.
                '</button></a>'.
                '<button type="button" class="btn btn-white removePhonebookData" data-id="'.$id.'">'.
                'Delete <i class="material-icons"></i>'.
                '</button>'.
                '</div></td>'.
                '</tr>';

            $s .= $oneRow;

        }
        return $s;
    }else{
        error_log("VT30: error getting list of PBX SIP Trunks for customer.");
    }


}


function getPhonebookDataExcerptList($phonebookID,$mysqli){

    $sql = "select id,number from phonebook_data where phonebook  = ? LIMIT 10;";
    $stmt = $mysqli->prepare($sql);


    if($stmt){

        $stmt->bind_param('i', $phonebookID);
        $stmt->execute();   // Execute the prepared query.
        // $stmt->store_result();
        $stmt->bind_result($id, $number);

        $s = '';
        while($stmt->fetch()){



            $s  .= $number."<br/>";


        }
        return $s;
    }else{
        error_log("VT30: error getting list of PBX SIP Trunks for customer.");
    }


}



function getPhonebookNumbersCount($phonebookID,$mysqli){

    $sql = "select count(*) from phonebook_data WHERE phonebook = ?;";


    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('i', $phonebookID);
    $stmt->execute();
    $stmt->bind_result($retTotal);
    $stmt->fetch();

    return $retTotal;

}

function checkIfPhonebookIsUsedWithCampaigns($dialerID,$phonebookID,$mysqli){
    $phonebooksArr = array();


    $sql = "select phonebooks from dialer_campaigns WHERE customer = ?;";
    $stmt = $mysqli->prepare($sql);


    if($stmt){

        $stmt->bind_param('i', $dialerID);
        $stmt->execute();   // Execute the prepared query.
        // $stmt->store_result();
        $stmt->bind_result($phonebooksJsonString);


        while($stmt->fetch()){

                $phonebooksJsonArr = json_decode($phonebooksJsonString);
                foreach($phonebooksJsonArr as $phonebook){
                    $phonebooksArr[] = $phonebook;
                }

        }

    }else{
        error_log("checkIfPhonebookIsUsedWithCampaigns: error .");
    }
    //error_log("Array before de-duplicate:". json_encode($phonebooksArr));

    // de-duplicate
    $phonebooksArr = array_values(array_unique($phonebooksArr));


    //error_log("Array after de-duplicate:". json_encode($phonebooksArr));

    if(in_array($phonebookID,$phonebooksArr)){
        return true;
    }else{
        return false;
    }



}


function checkIfPhoneNumberIsUsedWithCampaigns($campaign,$phone_number,$mysqli){
    $tableName = "dialer_list_" . $campaign;

    $sql = "select count(*) from ".$tableName." WHERE campaign = ? AND phone_number = ?;";
    $stmt = $mysqli->prepare($sql);


    if($stmt){

        $stmt->bind_param('is', $campaign,$phone_number);
        $stmt->execute();   // Execute the prepared query.
        // $stmt->store_result();
        $stmt->bind_result($resultValue);
        $stmt->fetch();

        if($resultValue == 1) return true;


    }else{
        return false;
    }


    return false;


}



function getIVRfilesForCustomerList($dialerID,$mysqli){

    $sql = "select id,file_description from ivr_files where customer  = ?;";
    $stmt = $mysqli->prepare($sql);


    if($stmt){

        $stmt->bind_param('i', $dialerID);
        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();
        $stmt->bind_result($id, $file_description);

        $s = '';
        while($stmt->fetch()){

            /*

            	<audio controls="controls" src="/examples/audio/birds.mp3">
                    Your browser does not support the HTML5 audio element.
                </audio>

            */

            $oneRow = '<tr>'.
                '<td>'.$file_description.'</td>'.
                '<td><audio controls="controls" src="ivrplayback.php?file='.$id.'">Your browser does not support the HTML5 audio element</audio> </td>'.
                '<td class="text-center"><div class="btn-group btn-group-sm" role="group" aria-label="Table row actions">'.
                '<button type="button" class="btn btn-white removeIVRfile" data-toggle="modal" data-target="#deleteIVRfileModal" data-id="'.$id.'" data-name="'.$file_description.'">'.
                'Delete <i class="material-icons"></i>'.
                '</button>'.
                '</div></td>'.
                '</tr>';

            $s .= $oneRow;

        }
        return $s;
    }else{
        error_log("Error getIVRfilesForCustomerList" . $mysqli->error);
    }


}



function getIVRfilesArrayForCustomer($dialerID,$mysqli){

    $sql = "select id,file_description,tts_text,s3_url,file_name from ivr_files where customer  = ?;";
    $stmt = $mysqli->prepare($sql);


    if($stmt){

        $stmt->bind_param('i', $dialerID);
        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();
        $stmt->bind_result($id, $file_description,$tts_text,$s3_url,$file_name);

        $s = array();
        while($stmt->fetch()){

            /*

            	<audio controls="controls" src="/examples/audio/birds.mp3">
                    Your browser does not support the HTML5 audio element.
                </audio>

            */
            $oneRow = array(
                "file_id"=> $id,
                "file_description"=>$file_description,
                "file_name" => $file_name,
                "tts_text" => $tts_text,
                "s3_url" => $s3_url
            );


            $s[] = $oneRow;

        }
        return $s;
    }else{
        error_log("Error getIVRfilesArrayForCustomer" . $mysqli->error);
    }


}



function getAllIVRfilesArray($mysqli){

    $sql = "select id,file_description,tts_text,s3_url,file_name from ivr_files;";
    $stmt = $mysqli->prepare($sql);


    if($stmt){

        //$stmt->bind_param('i', $dialerID);
        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();
        $stmt->bind_result($id, $file_description,$tts_text,$s3_url,$file_name);

        $s = array();
        while($stmt->fetch()){

            /*

            	<audio controls="controls" src="/examples/audio/birds.mp3">
                    Your browser does not support the HTML5 audio element.
                </audio>

            */
            $oneRow = array(
                "file_id"=> $id,
                "file_description"=>$file_description,
                "file_name" => $file_name,
                "tts_text" => $tts_text,
                "s3_url" => $s3_url
            );


            $s[] = $oneRow;

        }
        return $s;
    }else{
        error_log("Error getIVRfilesArrayForCustomer" . $mysqli->error);
    }


}



function dialerUploadPhonebookData($phonebookID,$dataArray,$mysqli){



    $sql = "INSERT INTO phonebook_data(phonebook,number) VALUES ";

    foreach($dataArray as $oneRow){

        $sql .= '('.$phonebookID.',"'.$oneRow[0].'"),';
    }

    $sql = substr($sql, 0, strlen($sql) - 1);  //(remove last ,)

    $sql .= ";";


    //error_log($sql);

    mysqli_query($mysqli,$sql);

    if($mysqli->error){
        error_log("VTDial:  Error uploading Phonebook data, details: " . $mysqli->error);
        return false;
    }

    return true;

}



function dialerDeletePhonebook($phonebookID,$mysqli){


    $sql = "delete from phonebook_data WHERE phonebook = ?;";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $phonebookID);
    $stmt->execute();


    // phonebooks


    $sql = "delete from phonebooks WHERE id = ?;";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $phonebookID);
    $stmt->execute();



    return true;
}




function dialerAddNewSipTrunk($dialerID,$name,$dst_address,$dst_extension,$mysqli){

    $newTrunkID = 0;

    $sql = "INSERT INTO dialer_sip_trunks(customer,name,dst_address,dst_extension) VALUES (?,?,?,?);";

    $stmt = $mysqli->prepare($sql);
    if($mysqli->error){
        error_log("VTDial:  Error in dialerAddNewSipTrunk 1, details: ". $mysqli->error);
        return false;
    }
    $stmt->bind_param('isss', $dialerID,$name,$dst_address,$dst_extension);
    $stmt->execute();

    $newTrunkID = $stmt->insert_id;

    if($mysqli->error){
        error_log("VTDial:  Error in dialerAddNewSipTrunk 2, details: ". $mysqli->error);
        return false;
    }


    if($newTrunkID>0){
        // copy the value to the "sip_peers" table (Asterisk RT table for SIP Peers)

        // Asterisk Trunk name:  "PBX" + trunk ID , for example:  PBX1053
        $trunkName = "PBX" . $newTrunkID;
        $outboundproxy = getDialerOutboundproxyIP($dialerID,$mysqli);

        $addressSplit = explode(":",$dst_address);

        $host  = $addressSplit[0];
        $port = $addressSplit[1];



        // INSERT into sip_peers (vtd_dialer,vtd_trunk_id,name,type,host,port) VALUES(11,15,"PBX0000015","peer","1.2.3.4",5060);

        $sql = "INSERT INTO sip_peers (vtd_dialer,vtd_trunk_id,name,type,host,port,outboundproxy) VALUES(?,?,?,'peer',?,?,?);";

        $stmt = $mysqli->prepare($sql);

        $stmt->bind_param('iissss', $dialerID,$newTrunkID,$trunkName,$host,$port,$outboundproxy);
        $stmt->execute();

        $newSipPeerID = $stmt->insert_id;
        error_log("Added SIP Peer.  dialer_sip_trunks ID [$newTrunkID] , sip_peers ID: [$newSipPeerID]");
        if($mysqli->error){
            error_log("VTDial:  Error in dialerAddNewSipTrunk 3, details: ". $mysqli->error);
            return false;
        }



    }




    return true;

}


function dialerEditSipTrunk($name,$dst_address,$dst_extension,$trunkID,$mysqli){

    $sql = "UPDATE dialer_sip_trunks set name = ?, dst_address = ?, dst_extension = ? , date_updated = now() WHERE id = ?;";
    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('sssi', $name,$dst_address,$dst_extension,$trunkID);

    $stmt->execute();

    if($mysqli->error){
        error_log("VTDial:  Error in dialerEditSipTrunk, details: ". $mysqli->error);
        return false;
    }

    return true;

}









function getDialerPbxSipTrunkForSelectView($dialerID,$mysqli,$selected = null){
    $trunkID = 0;

    if($selected>0) {
        $trunkID = $selected;
    }


    $sql = "select id, name from dialer_sip_trunks WHERE customer = ?;";
    $stmt = $mysqli->prepare($sql);


    if($stmt){

        $stmt->bind_param('i', $dialerID);
        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();
        $stmt->bind_result($id, $name);

        $s = '';
        while($stmt->fetch()){

            if($trunkID == $id){
                $oneRow = '<option value="'. $id.'" selected="selected">'.$name.'</option>';
                $s .= $oneRow;
            }else{

                $oneRow = '<option value="'. $id.'">'.$name.'</option>';
                $s .= $oneRow;
            }

        }
        return $s;
    }else{
        error_log("VT30: error getDialerPbxSipTrunkForSelectView : ". $mysqli->error);
    }
}





function dialerRemoveAllSIPtrunks($dialerID,$mysqli){

    // 1.delete from DB ONLY! - no need to delete from FileSystem, why should we ?

    $sql = "DELETE FROM dialer_sip_trunks WHERE customer  = ? ;";

    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('i', $dialerID);
    $stmt->execute();


    return true;

}







function getDialerContactListsForSelectView($dialerID,$mysqli,$phonebooks = null){

    $phonebooksARR = json_decode($phonebooks,true);
    //error_log(json_encode($phonebooksARR));





    $sql = "select id, name from phonebooks WHERE customer = ? order by id desc;";
    $stmt = $mysqli->prepare($sql);


    if($stmt){

        $stmt->bind_param('i', $dialerID);
        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();
        $stmt->bind_result($id, $name);

        $s = '';
        while($stmt->fetch()){
            $used = checkIfPhonebookIsUsedWithCampaigns($dialerID,$id, $mysqli);
            $usedN = "";

            if($used) $usedN = " (used)";


            if(in_array($id,$phonebooksARR)){



                $oneRow = '<option value="'. $id.'" selected="selected">'.$name.' '.$usedN.'</option>';
                $s .= $oneRow;

            }else{

                $oneRow = '<option value="'. $id.'">'.$name.' '.$usedN.'</option>';
                $s .= $oneRow;
            }



        }
        return $s;
    }else{
        error_log("VT30: error getDialerContactListsForSelectView : ". $mysqli->error);
    }
}



function getCustomerIVRfilesForSelectView($dialerID,$mysqli,$selected = null){
    $fileID = 0;

    //if($selected) {
        // get the ID of the file
      //  $fileID = getDialerIvrFileIDbyName($dialerID, $selected, $mysqli);

//    }

    //error_log($selected . "|" .$fileID);

    $sql = "select id, file_description from  ivr_files WHERE customer = ?;";
    $stmt = $mysqli->prepare($sql);


    if($stmt){

        $stmt->bind_param('i', $dialerID);
        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();
        $stmt->bind_result($id, $name);

        $s = '';
        while($stmt->fetch()){

            if($selected == $id){
                $oneRow = '<option value="'. $id.'" selected="selected">'.$name.'</option>';
                $s .= $oneRow;
            }else{

                $oneRow = '<option value="'. $id.'">'.$name.'</option>';
                $s .= $oneRow;
            }




        }
        return $s;
    }else{
        error_log("VT30: error getDialerIVRfilesForSelectView : ". $mysqli->error);
    }
}


function getRecordingFileDetails($id,$mysqli){

    $retArray = array();

    $sql = "select cl.call_uuid, d.domain_name from call_logs cl LEFT JOIN domains d ON cl.domain = d.id  where cl.id = ?;";


    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->bind_result($call_uuid, $domain_name);
    $stmt->fetch();


    $retArray["call_uuid"] = $call_uuid;
    $retArray["domain_name"] = $domain_name;

    return $retArray;

}



function getIVRFileDetails($id,$mysqli){

    $retArray = array();

    $sql = "select id,customer,file_description,file_name,tts_text,s3_url FROM ivr_files WHERE id = ?;";


    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->bind_result($id,$customer,$file_description,$file_name,$tts_text,$s3_url);
    $stmt->fetch();


    $retArray["id"] = $id;
    $retArray["customer"] = $customer;
    $retArray["file_description"] = $file_description;
    $retArray["file_name"] = $file_name;

    $retArray["tts_text"] = $tts_text;
    $retArray["s3_url"] = $s3_url;



    return $retArray;

}



function getDialerIvrFileIDbyName($dialerID,$fileName,$mysqli){

    $sql = "select id from dialer_ivr_files  where customer = ? AND file_name = ?;";


    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('is', $dialerID,$fileName);
    $stmt->execute();
    $stmt->bind_result($ret);
    $stmt->fetch();

    return $ret;

}


function getDialerSIPtrunkExtension($SIPtrunk,$mysqli){

    $sql = "select dst_extension from dialer_sip_trunks  where id = ?;";


    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('i', $SIPtrunk);
    $stmt->execute();
    $stmt->bind_result($retTotal);
    $stmt->fetch();

    return $retTotal;

}

function getDialerSIPtrunkDetails($SIPtrunk,$mysqli){

    $sql = "select customer, name, dst_address,dst_extension from dialer_sip_trunks WHERe id = ?;";


    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('i', $SIPtrunk);
    $stmt->execute();
    $stmt->bind_result($dialerID, $name, $dst_address, $dst_extension);
    $stmt->fetch();

    return array("name"=> $name,"dst_address"=> $dst_address, "dst_extension" => $dst_extension, "dialerid"=>$dialerID);

}






function dialerAddNewCampaign($customer,$name,$caller_id,$phonebooks,$main_ivr,$vm_ivr ,$ok_input,$ok_extension,$ok_trunk,$ok_ivr,$dnc_input, $dnc_ivr,$channels_limit,$mysqli){

    $sql = "INSERT INTO dialer_campaigns(customer,name,caller_id,phonebooks,main_ivr,vm_ivr ,ok_input,ok_extension,ok_trunk,ok_ivr,dnc_input, dnc_ivr, channels) 
VALUES (?,?,?,?,? ,?,?,?,?,? ,?,?,?);";

    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('isssssssisssi', $customer,$name,$caller_id,$phonebooks,$main_ivr,$vm_ivr ,$ok_input,$ok_extension,$ok_trunk,$ok_ivr,$dnc_input, $dnc_ivr,$channels_limit);
    $stmt->execute();
    $newcampaign = $stmt->insert_id;

    if($mysqli->error){
        error_log("VTDial:  Error in dialerAddNewCampaign, details: ". $mysqli->error);
        return false;
    }



    if($newcampaign>0){
        // campaign created, let's add dedicated table to keep campaign data


        if(dialerAddNewCampaignTable($newcampaign,$mysqli)){
            return $newcampaign;
        }else{
            return false;
        }

    }

    return false;
}


function dialerAddNewCampaignTable($campaignID,$mysqli){

    $table_name = "dialer_list_" . $campaignID;

    $sql = "CREATE TABLE `".$table_name."` (`id` int(11) NOT NULL AUTO_INCREMENT, `campaign` int(11) NOT NULL DEFAULT 0, `phonebook` INT(11) NOT NULL,`phone_number` varchar(16) NOT NULL, `call_answer` tinyint(1) NOT NULL DEFAULT 0,  `amd` tinyint(4) NOT NULL DEFAULT 0,  `call_result` tinyint(4) NOT NULL DEFAULT 0,  `dll_token` varchar(64) NOT NULL DEFAULT '',  `date_queued` datetime DEFAULT '0000-00-00 00:00:00',  `date_processed` datetime DEFAULT '0000-00-00 00:00:00',  `date_added` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,   PRIMARY KEY (`id`),KEY `campaign` (`campaign`),KEY `date_queued` (`date_queued`),KEY `phone_number` (`phone_number`),KEY `phonebook` (`phonebook`),KEY `date_added` (`date_added`)  ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;";
    $stmt = $mysqli->prepare($sql);
    //$stmt->bind_param('i', $SIPtrunk);
    $stmt->execute();

    if($mysqli->error){
        error_log("VTDial:  Error in dialerAddNewCampaignTable, details: ". $mysqli->error);
        return false;
    }

    return true;

}





function dialerUpdateCampaignDetails($campaign,$name,$caller_id,$phonebooks,$main_ivr,$vm_ivr ,$ok_input,$ok_extension,$ok_trunk,$ok_ivr,$dnc_input, $dnc_ivr,$channels_limit,$timeout_limit,$time_boundaries,$mysqli){


    $sql = "UPDATE dialer_campaigns SET name = ? ,caller_id =?,phonebooks =?,main_ivr =?,vm_ivr =? ,ok_input =?,ok_extension =?,ok_trunk =?,ok_ivr =?,dnc_input =?, dnc_ivr =?, channels =?, timeout_limit=?, time_boundaries=?  WHERE id = ?;";

    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('sssssssisssiiii',$name,$caller_id,$phonebooks,$main_ivr,$vm_ivr ,$ok_input,$ok_extension,$ok_trunk,$ok_ivr,$dnc_input, $dnc_ivr,$channels_limit,$timeout_limit,$time_boundaries,$campaign);
    $stmt->execute();

    if($mysqli->error){
        error_log("VTDial:  Error in dialerUpdateCampaignDetails, details: ". $mysqli->error);
        return false;
    }

    return true;

}




function dialerUpdateCampaignCallFlowControl($dialer, $campaign,$flow_control_enabled,$required_transfers,$max_channels,  $mysqli){

    $sql = "INSERT INTO dialer_campaigns_flow_control(customer,campaign,flow_control_enabled,required_transfers,max_channels) VALUES(?,?,?,?,?) ON DUPLICATE KEY   UPDATE  flow_control_enabled = ? , required_transfers = ? , max_channels = ?;";
    //$sql = "UPDATE dialer_campaigns_flow_control SET flow_control_enabled = ? , required_transfers = ? , max_channels = ? WHERE customer = ? AND campaign = ?;";

    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('iiiiiiii',$dialer,$campaign,$flow_control_enabled,$required_transfers,$max_channels,     $flow_control_enabled,$required_transfers,$max_channels );
    $stmt->execute();

    if($mysqli->error){
        error_log("VTDial:  Error in dialerUpdateCampaignCallFlowControl, details: ". $mysqli->error);
        return false;
    }

    return true;

}


// delete campaign


function dialerDeleteCampaign($campaignID,$mysqliRT,$mysqli){


    // 1. RT database -> delete everything related to this campaign.

    removeCampaignRTcampaignData($campaignID, $mysqliRT);

    // 2. Standard DB -> delete from dialer_list  (list of numbers for the campaign)

    removeDialerListItemsForOneCampaign($campaignID,$mysqli);

    // 3. Standard DB -> delete the campaign from "dialer_campaigns"

    removeCampaign($campaignID,$mysqli);

    return true;
}



function removeDialerListItemsForOneCampaign($campaignID,$mysqli){


    $table_name = "dialer_list_" . $campaignID;

    $sql = "DELETE from ".$table_name." where campaign = ?;";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $campaignID);
    $stmt->execute();


    return true;

}


function removeCampaign($campaignID,$mysqli){

    $sql = "DELETE from dialer_campaigns where id = ?;";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $campaignID);
    $stmt->execute();

    // 2nd stage - remove table


    $table_name = "dialer_list_" . $campaignID;
    $sql = "DROP TABLE " . $table_name . ";";
    $stmt = $mysqli->prepare($sql);
    //$stmt->bind_param('i', $campaignID);
    $stmt->execute();

    return true;

}


function removeDialerByID($dialerID,$mysqli){

    $sql = "DELETE from customers where id = ?;";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $dialerID);
    $stmt->execute();

    return true;

}



function getFullCampaignReportToTempFile($campaignID,$mysqli){
    $table_name = "dialer_list_" . $campaignID;


    $hashPart =  uniqid()."_".bin2hex(openssl_random_pseudo_bytes(12));

    $tempFilePath = UPLOAD_TEMP_FOLDER ."/". $hashPart ."_campaign_report.csv";

    $sql = "select phone_number, call_answer,amd,call_result from ".$table_name." WHERE campaign = ?;";

    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $campaignID);

    if($stmt){

        $retPhoneNumber = '';

        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();
        $stmt->bind_result($phone_number, $call_answer, $amd, $call_result);

        $fp = fopen($tempFilePath, 'w');


        $row1 = array("Phone number","Call result");
        fputcsv($fp, $row1);
        //error_log("FILE: adding first row: ".json_encode($row1));

        while($stmt->fetch()) {

            $callResultFinal = "NA";  // NA = No Answer
            if($call_answer==1){
                $callResultFinal = "AN";// AN = Answer

                if($amd == "1") $callResultFinal = "HU";  // HU - Human Answer
                if($amd == "2") $callResultFinal = "AM";  // AM - Answering Machine

                if($call_result == "1") $callResultFinal = "PRESS";  // PRESS - Press OK
                if($call_result == "2") $callResultFinal = "DNC";  // DNC - Press DNC or number on DNC list already
                if($call_result == "3") $callResultFinal = "TM";  // TM - Press timeout
                if($call_result == "4") $callResultFinal = "VM";  // VM - Voicemail message recorded
                if($call_result == "5") $callResultFinal = "SALE";  // VM - Voicemail message recorded


                /*
                 *

                       Possible values for call disposition:
                            NA - no answer
                            AN - answer
                            HU - Human answer
                            AM - answering machine
                            PRESS - press "1"
                            DNC - press DNC or number on DNC list already
                            TM - press timeout
                            VM - voicemail message recorded
                            SALE - sale made (marked manually)
                            OP_INT - Operator intercept, call disconnected
                            OO_TIME - out of the time boundaries

                 *
                 */


            }

            if($call_result == "6") $callResultFinal = "OP_INT";  // OP_INT - Operator intercept
            if($call_result == "7") $callResultFinal = "OO_TIME";  // OO_TIME - Out of the time boundaries

            $row = array($phone_number,$callResultFinal);
            fputcsv($fp, $row);
            //error_log("FILE: adding row for [$phone_number]: ".json_encode($row));
        }


        fclose($fp);


        return $tempFilePath;


    }
}












function customerAddNewIVRMediaFile($customerID,$file_description,$file_name,$ttsText,$s3URL,$mysqli){

    $sql = "INSERT INTO ivr_files(customer,file_description,file_name,tts_text,s3_url) VALUES (?,?,?,?,?);";

    $stmt = $mysqli->prepare($sql);
    if($mysqli->error){
        error_log("VTPBX:  Error in customerAddNewIVRMediaFile, details: ". $mysqli->error);
        return false;
    }
    $stmt->bind_param('issss', $customerID,$file_description,$file_name,$ttsText,$s3URL);
    $stmt->execute();

    $newIVRfileID = $stmt->insert_id;

    if($mysqli->error){
        error_log("VTPBX:  Error in customerAddNewIVRMediaFile, details: ". $mysqli->error);
        return false;
    }

    return $newIVRfileID;

}



function dialerRemoveIVRMediaFile($dialerID,$fileID,$mysqli){



    // 1.delete from DB ONLY! - no need to delete from FileSystem, why should we ?


    $sql = "DELETE FROM dialer_ivr_files WHERE id = ? AND customer  = ? ;";

    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('ii', $fileID,$dialerID);
    $stmt->execute();


    return true;

}



function removeIVRmediaFileByID($fileID,$mysqli){

    // 1.delete from DB ONLY! - no need to delete from FileSystem, why should we ?

    $sql = "DELETE FROM ivr_files WHERE id = ? ;";

    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('i', $fileID);
    $stmt->execute();


    return true;

}


function dialerRemoveAllIVRMediaFiles($dialerID,$mysqli){

    // 1.delete from DB ONLY! - no need to delete from FileSystem, why should we ?

    $sql = "DELETE FROM dialer_ivr_files WHERE customer  = ? ;";

    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('i', $dialerID);
    $stmt->execute();


    return true;

}




function updateCustomerGeneralDetails($name,$type,$limit_extensions,$limit_channels_internal,$limit_channels_incoming,$limit_channels_external, $sip_provider, $sip_provider_prefix,$external_caller_id, $moh,$customerID,$mysqli){

    $sql = "UPDATE customers set name = ?, type = ?, limit_extensions = ?,  limit_channels_internal = ?, limit_channels_incoming=?, limit_channels_external = ?, sip_provider = ? ,sip_provider_prefix = ?, external_caller_id = ?, moh = ?, date_updated=now() WHERE id = ?;";

    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('siiiiiissii', $name,$type,$limit_extensions,$limit_channels_internal,$limit_channels_incoming,$limit_channels_external, $sip_provider, $sip_provider_prefix,$external_caller_id, $moh, $customerID);
    $stmt->execute();

    if($mysqli->error){
        error_log("VTDial:  Error in updateCustomerGeneralDetails, details: ". $mysqli->error);
        return false;
    }

    return true;

}

function updateCustomerExternalTFproviderDetails($sip_provider_tf, $sip_provider_prefix_tf  ,$customerID,$mysqli){

    $sql = "UPDATE customers set  sip_provider_tf = ? ,sip_provider_prefix_tf = ? ,date_updated=now() WHERE id = ?;";

    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('isi', $sip_provider_tf, $sip_provider_prefix_tf , $customerID);
    $stmt->execute();

    if($mysqli->error){
        error_log("VTDial:  Error in updateCustomerExternalTFproviderDetails, details: ". $mysqli->error);
        return false;
    }

    return true;

}




function updateDialerGeneralDetails($name,$type,$max_channels,$dialerID,$outboundSIPprovider,$mysqli){

    $sql = "UPDATE customers set name = ?, type = ?, max_channels = ?, sip_provider = ? , date_updated=now() WHERE id = ?;";

    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('siiii', $name,$type,$max_channels,$outboundSIPprovider,$dialerID);
    $stmt->execute();

    if($mysqli->error){
        error_log("VTDial:  Error in updatedialerGeneralDetails, details: ". $mysqli->error);
        return false;
    }

    return true;

}


function updateDialerSetIPAddress($dialerID,$newIPAddress,$mysqli){

    $sql = "UPDATE customers set ip_address = ?, date_updated=now() WHERE id = ?;";

    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('si', $newIPAddress,$dialerID);
    $stmt->execute();

    if($mysqli->error){
        error_log("VTDial:  Error in updateDialerSetIPAddress, details: ". $mysqli->error);
        return false;
    }

    return true;

}

function dialerAddNewPhonebook($customer,$name,$description,$mysqli){

    $sql = "INSERT INTO phonebooks(customer,name,description) VALUES (?,?,?);";

    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('iss', $customer,$name,$description);
    $stmt->execute();

    if($mysqli->error){
        error_log("VTDial:  Error in dialerAddNewPhonebook, details: ". $mysqli->error);
        return false;
    }

    return true;

}



function updateCampaignSchedule($campaignID,$scheduleData,$mysqli){

    $sql = "UPDATE dialer_campaigns set dialing_hours = ? WHERE id = ? ;";

    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('si', $scheduleData,$campaignID);
    $stmt->execute();

    if($mysqli->error){
        error_log("VTDial:  Error in updateCampaignSchedule, details: ". $mysqli->error);
        return false;
    }

    return true;

}


function updateCampaignStatus($campaignID,$newStatus,$mysqli){

    $sql = "UPDATE dialer_campaigns set status = ? WHERE id = ? ;";

    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('ii', $newStatus,$campaignID);
    $stmt->execute();

    if($mysqli->error){
        error_log("VTDial:  Error in updateCampaignStatus, details: ". $mysqli->error);
        return false;
    }

    return true;

}




function loadPhonebooksFromCampaignToDialerList($campaign, $mysqli){

    // get campaign phonebooks
    $campaignDetails = getCampaignGeneralDetails($campaign,$mysqli);
    $phonebooks = $campaignDetails["phonebooks"];
    $phonebooksARR = json_decode($phonebooks , true);


    // load phonebooks to the dialer list.
    foreach($phonebooksARR as $phonebook){
        loadPhonebookDataToDialerList($phonebook,$campaign,$mysqli);
    }

}

function loadPhonebookDataToDialerList($phonebook,$campaign,$mysqli){

    $table_name = "dialer_list_" . $campaign;



    $sql = "INSERT into ".$table_name."(campaign,phone_number,phonebook) SELECT ?, number,phonebook FROM phonebook_data WHERE phonebook = ?;";

    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('ii', $campaign,$phonebook);
    $stmt->execute();

    if($mysqli->error){
        error_log("VTDial:  Error in loadPhonebookDataToDialerList, details: ". $mysqli->error);
        return false;
    }

    return true;





}

// getCampaignProgress($id,$mysqli )

function getCampaignProgress($id,$mysqli ){
    $table_name = "dialer_list_" . $id;

    $sql = "select count(*), SUM(IF(date_queued >0,1,0))  from ".$table_name." where campaign = ?;";


    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->bind_result($retTotal,$retProcessed);
    $stmt->fetch();



    return round( ($retProcessed*100)/$retTotal , 2);



}




function getDialerOutboundproxyIP($dialerId,$mysqli){

    $sql = "select ip_address from customers  where id = ?;";


    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('i', $dialerId);
    $stmt->execute();
    $stmt->bind_result($retValue);
    $stmt->fetch();

    return $retValue;

}


function getNocCustomersCount($mysqli){

    $sql = "select count(id), count(case when type = 1 then 1 else null end) from customers;";


    $stmt = $mysqli->prepare($sql);

    //$stmt->bind_param('i', $dialerId);
    $stmt->execute();
    $stmt->bind_result($retValue1,$retValue2);
    $stmt->fetch();


    $percActive = round($retValue2 / ($retValue1) * 100 , 0 ) . "% active";

    return array($retValue1,$retValue2,$percActive);


}





function getNocCampaignsCount($mysqli){

    $sql = "select count(id), count(case when status = 1 then 1 else null end) from dialer_campaigns;";


    $stmt = $mysqli->prepare($sql);

    //$stmt->bind_param('i', $dialerId);
    $stmt->execute();
    $stmt->bind_result($retValue1,$retValue2);
    $stmt->fetch();


    $percActive = "out of $retValue1";

    return array($retValue1,$retValue2,$percActive);




}



//


function getPublicChannelsCount($mysqli){

    $sql = 'SELECT count(*) FROM dialer_rt_channels WHERE dp_context = "default";';


    $stmt = $mysqli->prepare($sql);

    //$stmt->bind_param('i', $dialerId);
    $stmt->execute();
    $stmt->bind_result($retValue1);
    $stmt->fetch();

    return $retValue1;

}


function getAllChannelsCount($mysqli){

    //$sql = 'SELECT count(*) FROM dialer_rt_channels;';
    //$sql = 'SELECT sum(meter_value) FROM proxy_rt_stats;';
    $sql = 'select sum(meter_value) from app_server_rt_stats where meter_name = "channels";';


    $stmt = $mysqli->prepare($sql);

    //$stmt->bind_param('i', $dialerId);
    $stmt->execute();
    $stmt->bind_result($retValue1);
    $stmt->fetch();

    return $retValue1;

}
//







function getProxyRegCount($mysqli){

    //$sql = 'SELECT count(*) FROM dialer_rt_channels;';
    //$sql = 'SELECT sum(meter_value) FROM proxy_rt_stats;';
    $sql = 'select count(username) from location;';


    $stmt = $mysqli->prepare($sql);

    //$stmt->bind_param('i', $dialerId);
    $stmt->execute();
    $stmt->bind_result($retValue1);
    $stmt->fetch();

    return $retValue1;

}
//



function getNumbersQueueCount($mysqli){

    $sql = "SELECT count(*) FROM dialer_rt_list WHERE dialer_session_key = \"\";";

    $stmt = $mysqli->prepare($sql);

    //$stmt->bind_param('i', $dialerId);
    $stmt->execute();
    $stmt->bind_result($retValue1);
    $stmt->fetch();

    return $retValue1;

}


//


function getTransfersCount($mysqli){

    $sql = 'select sum(meter_value) from proxy_rt_stats WHERE meter_name = "transfers";';

    $stmt = $mysqli->prepare($sql);

    //$stmt->bind_param('i', $dialerId);
    $stmt->execute();
    $stmt->bind_result($retValue1);
    $stmt->fetch();

    return $retValue1;

}

function getTransfersCountMT($userDialers,$mysqli){
    if(!$userDialers){
        return 0;
    }


    $sql = 'select sum(meter_value) from proxy_rt_stats ps JOIN dialer_campaigns dc ON ps.meter_id = dc.id WHERE dc.customer IN ('.$userDialers.') AND ps.meter_name = "transfers";';

    $stmt = $mysqli->prepare($sql);

    //$stmt->bind_param('i', $dialerId);
    $stmt->execute();
    $stmt->bind_result($retValue1);
    $stmt->fetch();

    return $retValue1;

}





function getTransfersCountForServer($serverID, $mysqli){

    $sql = 'select count(vtd_id) from dialer_rt_channels WHERE dp_context = "TRANSFER" AND app_srv_id = ?;';

    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('i', $serverID);
    $stmt->execute();
    $stmt->bind_result($retValue1);
    $stmt->fetch();

    return $retValue1;

}



function getApplicationServersStats($mysqli){
    $s = "";
    // 1. Get a list of application servers

    $appServersList = getListOfApplicationServersArr($mysqli);
        // $returnArr[] = array("id"=>$id,"name"=>$name,"type"=>$type,"ip_address"=>$ip_address,"max_channels"=>$max_channels);

    // 2. loop over the list of application servers to get their capacity stats

    foreach($appServersList as $appServer){

        $id = $appServer["id"];
        $name = $appServer["name"];
        $type = $appServer["type"];
        $ip_address = $appServer["ip_address"];
        $max_channels = $appServer["max_channels"];

        // get some extras:
        $serverChannelsRT = getChannelsForAppServerPR($id,$mysqli);
        if(test_input($serverChannelsRT) == "") $serverChannelsRT = "-";

        //$transfers = getTransfersCountForServer($id, $mysqliRT);

        $s .=  '<div class="col-lg-6 col-md-6 col-sm-6 mb-6">  <div class="file-manager__item file-manager__item--directory card card-small mb-3"><div class="card-body"> <span class="file-manager__item-icon"><i class="material-icons">settings</i> '.$name.' channels: ['.$serverChannelsRT.'/'.$max_channels.']</span></div></div></div>';

    }

    // <div class="col-lg-3 col-md-3 col-sm-4 mb-4">  <div class="file-manager__item file-manager__item--directory card card-small mb-3"><div class="card-body"> <span class="file-manager__item-icon"><i class="material-icons">settings</i> FS1 [0 / 0]</span></div></div></div>



    // 3. return the value1

    return $s;
}



function getChannelsForAppServerRT($appServerID, $mysqli){

    $sql = "SELECT count(*) FROM dialer_rt_channels WHERE app_srv_id = ?;";


    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('i', $appServerID);
    $stmt->execute();
    $stmt->bind_result($retValue);
    $stmt->fetch();

    return $retValue;

}


function getChannelsForAppServerPR($appServerID, $mysqli){

    $sql = 'SELECT meter_value FROM app_server_rt_stats WHERE meter_name = "channels" AND app_srv_id = ?;';


    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('i', $appServerID);
    $stmt->execute();
    $stmt->bind_result($retValue);
    $stmt->fetch();

    return $retValue;

}

function getSettingsDNClist($dialerID, $mysqli){

    $sql = "select destination_number,date_added from dnc_list WHERE customer = ?;";

    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $dialerID);

    if($stmt){
        $destination_number = '';

        $date_added = '';

        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();
        $stmt->bind_result($destination_number,$date_added);

        $s = '';
        while($stmt->fetch()){

            $s .= '<tr><td>' .$destination_number. '</td><td>' .$date_added. '</td><td> <button  class="btn btn-white" data-toggle="modal" data-target="#deleteDNCModal" data-number="'.$destination_number.'" ><i class="material-icons"></i></button>   </td></tr>';
        }
        return $s;
    }
}




function getSettingsDNCcount($dialerID, $mysqli){

    $sql = "select count(*) from dnc_list WHERE customer = ?;";

    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $dialerID);

    if($stmt){

        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();
        $stmt->bind_result($retValue);
        $stmt->fetch();

        return $retValue;
    }
}






function  removeDNCNumber($dialerID,$destination_number, $mysqli){

    $sql = "DELETE from dnc_list WHERE customer = ? AND destination_number = ?;";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('is', $dialerID,$destination_number);
    $stmt->execute();
    return true;
}




function  removeDNClistForDialer($dialerID,$mysqli){

    $sql = "DELETE from dnc_list WHERE customer = ? ;";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $dialerID);
    $stmt->execute();
    return true;
}



function addDNCNumber($destination_number,$dialerID,$mysqli){

    $sql = "INSERT INTO dnc_list(destination_number,customer) VALUES (?,?); ";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('si', $destination_number,$dialerID);
    $stmt->execute();
    return true;

}



function addDNCNumberFromSale($destination_number,$dialerID,$mysqli){

    /*
     *
     *  type:
     *      0 - regular DNC number
     *      1 - DNC added because of sale
     */

    $sql = "INSERT INTO dnc_list(destination_number,customer,type) VALUES (?,?,1); ";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('si', $destination_number,$dialerID);
    $stmt->execute();
    if($mysqli->error){
        error_log("addDNCNumberFromSale error, details: " . $mysqli->error);
        return false;
    }
    return true;

}


function checkNumberOnDNClistAdminPanel($customer,$number,$mysqli){
    $sql = 'select count(*) from dnc_list WHERE customer = ? AND destination_number = ?;';


    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('is', $customer,$number);
    $stmt->execute();
    $stmt->bind_result($retValue1);
    $stmt->fetch();

    if($retValue1 > 0 ) return true;
    else return false;
}




function dialerUploadDNCData($dialerID,$dataArray,$mysqli){



    $sql = "INSERT INTO dnc_list(customer,destination_number) VALUES ";

    foreach($dataArray as $oneRow){

        $sql .= '('.$dialerID.',"'.$oneRow[0].'"),';
    }

    $sql = substr($sql, 0, strlen($sql) - 1);  //(remove last ,)

    $sql .= " ON DUPLICATE KEY UPDATE date_added=date_added;";


    //error_log($sql);

    mysqli_query($mysqli,$sql);

    if($mysqli->error){
        error_log("VTDial:  Error uploading DNC data, details: " . $mysqli->error);
        return false;
    }

    return true;

}



function getFullDNCForClientToTempFile($dialerID,$mysqli){

    $hashPart =  uniqid()."_".bin2hex(openssl_random_pseudo_bytes(12));

    $tempFilePath = UPLOAD_TEMP_FOLDER ."/". $hashPart ."_dnc_list.csv";

    $sql = "select destination_number FROM dnc_list WHERE customer=?;";

    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $dialerID);

    if($stmt){

        $retPhoneNumber = '';

        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();
        $stmt->bind_result($retPhoneNumber);

        $fp = fopen($tempFilePath, 'w');


        while($stmt->fetch()) {

            $row = array($retPhoneNumber);
            fputcsv($fp, $row);
        }


        fclose($fp);


        return $tempFilePath;


    }
}







function checkDuplicatePhoneBookData($dialerID,$importedLine,$mysqli){

    $sql = 'select count(*) from phonebook_data pd JOIN phonebooks p ON pd.phonebook = p.id AND p.customer = ? AND pd.number = ?;';


    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('is', $dialerID,$importedLine);
    $stmt->execute();
    $stmt->bind_result($retValue1);
    $stmt->fetch();

    if($retValue1 > 0 ) return true;
    else return false;

}

function checkCampaignRTnumbersTotal($campaignID,$mysqli){

    $sql = 'select count(*) from dialer_rt_list WHERE campaign = ?;';


    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('i', $campaignID);
    $stmt->execute();
    $stmt->bind_result($retValue1);
    $stmt->fetch();

    return $retValue1;

}


function checkCampaignNumbersTotal($campaignID,$mysqli){

    $tableName = "dialer_list_" . $campaignID;

    $sql = 'select count(*) from '.$tableName.' WHERE campaign = ?;';


    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('i', $campaignID);
    $stmt->execute();
    $stmt->bind_result($retValue1);
    $stmt->fetch();

    return $retValue1;

}



function checkCampaignNumbersTotalP($campaignID,$mysqli, $phonebook){
    $tableName = "dialer_list_" . $campaignID;

    $sql = 'select count(*) from '.$tableName.' WHERE campaign = ? AND phonebook = ?;';


    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('ii', $campaignID,$phonebook);
    $stmt->execute();
    $stmt->bind_result($retValue1);
    $stmt->fetch();

    return $retValue1;

}

function checkCampaignNumbersAttempt($campaignID,$mysqli){
    $tableName = "dialer_list_" . $campaignID;

    $sql = 'select count(*) from '.$tableName.' WHERE campaign = ? AND date_queued > 0;';


    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('i', $campaignID);
    $stmt->execute();
    $stmt->bind_result($retValue1);
    $stmt->fetch();

    return $retValue1;

}


function checkCampaignNumbersAttemptP($campaignID,$mysqli, $phonebook){

    $tableName = "dialer_list_" . $campaignID;


    $sql = 'select count(*) from '.$tableName.' WHERE campaign = ? AND date_queued > 0 AND phonebook = ?;';


    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('ii', $campaignID,$phonebook);
    $stmt->execute();
    $stmt->bind_result($retValue1);
    $stmt->fetch();

    return $retValue1;

}



function checkCampaignRTanswers($campaignID,$mysqli){

    $sql = 'select count(*) from dialer_rt_list WHERE campaign = ? AND call_answer = 1;';


    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('i', $campaignID);
    $stmt->execute();
    $stmt->bind_result($retValue1);
    $stmt->fetch();

    return $retValue1;

}

function checkCampaignAnswers($campaignID,$mysqli){

    $tableName = "dialer_list_" . $campaignID;


    $sql = 'select count(*) from '.$tableName.' WHERE campaign = ? AND call_answer = 1;';


    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('i', $campaignID);
    $stmt->execute();
    $stmt->bind_result($retValue1);
    $stmt->fetch();

    return $retValue1;

}


function checkCampaignAnswersP($campaignID,$mysqli, $phonebook){
    $tableName = "dialer_list_" . $campaignID;

    $sql = 'select count(*) from '.$tableName.' WHERE campaign = ? AND call_answer = 1 AND phonebook = ?;';


    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('ii', $campaignID,$phonebook);
    $stmt->execute();
    $stmt->bind_result($retValue1);
    $stmt->fetch();

    return $retValue1;

}




function checkCampaignRThumanAnswers($campaignID,$mysqli){

    $sql = 'select count(*) from dialer_rt_list WHERE campaign = ? AND call_answer = 1 AND amd = 1;';


    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('i', $campaignID);
    $stmt->execute();
    $stmt->bind_result($retValue1);
    $stmt->fetch();

    return $retValue1;

}

function checkCampaignHumanAnswers($campaignID,$mysqli){
    $tableName = "dialer_list_" . $campaignID;

    $sql = 'select count(*) from '.$tableName.' WHERE campaign = ? AND call_answer = 1 AND amd = 1;';


    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('i', $campaignID);
    $stmt->execute();
    $stmt->bind_result($retValue1);
    $stmt->fetch();

    return $retValue1;

}


function checkCampaignHumanAnswersP($campaignID,$mysqli,$phonebook){
    $tableName = "dialer_list_" . $campaignID;

    $sql = 'select count(*) from '.$tableName.' WHERE campaign = ? AND call_answer = 1 AND amd = 1 ANd phonebook = ?;';


    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('ii', $campaignID, $phonebook);
    $stmt->execute();
    $stmt->bind_result($retValue1);
    $stmt->fetch();

    return $retValue1;

}




function checkCampaignRTtransfers($campaignID,$mysqli){

    $sql = 'select count(*) from dialer_rt_list WHERE campaign = ? AND call_answer = 1 AND call_result = 1;';


    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('i', $campaignID);
    $stmt->execute();
    $stmt->bind_result($retValue1);
    $stmt->fetch();

    return $retValue1;

}


function checkCampaignTransfers($campaignID,$mysqli){
    $tableName = "dialer_list_" . $campaignID;

    $sql = 'select count(*) from '.$tableName.' WHERE campaign = ? AND call_answer = 1 AND call_result = 1;';


    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('i', $campaignID);
    $stmt->execute();
    $stmt->bind_result($retValue1);
    $stmt->fetch();

    return $retValue1;

}


function checkCampaignSales($campaignID,$mysqli){
    $tableName = "dialer_list_" . $campaignID;

    $sql = 'select count(*) from '.$tableName.' WHERE campaign = ? AND  call_result = 5;';


    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('i', $campaignID);
    $stmt->execute();
    $stmt->bind_result($retValue1);
    $stmt->fetch();

    return $retValue1;

}



function checkCampaignSalesP($campaignID,$mysqli,$phonebook){
    $tableName = "dialer_list_" . $campaignID;


    $sql = 'select count(*) from '.$tableName.' WHERE campaign = ? AND phonebook = ? AND  call_result = 5;';


    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('ii', $campaignID,$phonebook);
    $stmt->execute();
    $stmt->bind_result($retValue1);
    $stmt->fetch();

    return $retValue1;

}

//
function checkCampaignTransfersP($campaignID,$mysqli,$phonebook){
    $tableName = "dialer_list_" . $campaignID;


    $sql = 'select count(*) from '.$tableName.' WHERE campaign = ? AND call_answer = 1 AND call_result = 1 AND phonebook = ?;';


    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('ii', $campaignID,$phonebook);
    $stmt->execute();
    $stmt->bind_result($retValue1);
    $stmt->fetch();

    return $retValue1;

}


function getActiveCampaignsRTstats($mysqli){

    $returnArr = array();

   //  $sql = 'select customer, campaign,  sum( case when dp_context = "TRANSFER" then 1 else 0 end), count(*) from dialer_rt_channels GROUP by customer, campaign;';

    $sql = 'SELECT dc.customer, ps.meter_id, sum(case when meter_name = "transfers" then meter_value else 0 end), sum(case when meter_name = "outgoing" then meter_value else 0 end) FROM proxy_rt_stats ps JOIN dialer_campaigns dc ON ps.meter_id = dc.id  GROUP BY dc.customer,dc.id;';


    //$sql = 'SELECT dc.customer, ps.meter_id, sum(case when meter_name = "transfers" then meter_value else 0 end), sum(case when meter_name = "outgoing" then meter_value else 0 end) FROM proxy_rt_stats ps JOIN dialer_campaigns dc ON ps.meter_id = dc.id WHERE dc.customer IN('.  $userDialers  .') GROUP BY dc.customer,dc.id;';

   // SELECT dc.customer, ps.meter_id, sum(case when meter_name = "transfers" then meter_value else 0 end), sum(case when meter_name = "outgoing" then meter_value else 0 end) FROM proxy_rt_stats ps JOIN dialer_campaigns dc ON ps.meter_id = dc.id WHERE dc.customer IN(1,8) GROUP BY dc.customer,dc.id;
    // SELECT dc.customer, ps.meter_id, sum(case when meter_name = "transfers" then meter_value else 0 end), sum(case when meter_name = "outgoing" then meter_value else 0 end) FROM proxy_rt_stats ps JOIN dialer_campaigns dc ON ps.meter_id = dc.id  GROUP BY dc.customer,dc.id;


    $stmt = $mysqli->prepare($sql);
    //$stmt->bind_param('i', $dialerID);

    if($stmt){
        $destination_number = '';

        $date_added = '';

        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();
        $stmt->bind_result($customer,$campaign,$xfers,$channels);

        $s = '';
        while($stmt->fetch()){

            $returnArr[] = array($customer,$campaign,$xfers,$channels);
        }
        return $returnArr;
    }



}



function getActiveCampaignsRTstatsMT($userDialers,$mysqli){

    $returnArr = array();

    //$sql = 'select customer, campaign,  sum( case when dp_context = "TRANSFER" then 1 else 0 end), count(*) from dialer_rt_channels GROUP by customer, campaign;';

    $sql = 'SELECT dc.customer, ps.meter_id, sum(case when meter_name = "transfers" then meter_value else 0 end), sum(case when meter_name = "outgoing" then meter_value else 0 end) FROM proxy_rt_stats ps JOIN dialer_campaigns dc ON ps.meter_id = dc.id WHERE dc.customer IN('.  $userDialers  .') GROUP BY dc.customer,dc.id;';

    // SELECT dc.customer, ps.meter_id, sum(case when meter_name = "transfers" then meter_value else 0 end), sum(case when meter_name = "outgoing" then meter_value else 0 end) FROM proxy_rt_stats ps JOIN dialer_campaigns dc ON ps.meter_id = dc.id WHERE dc.customer IN(1,8) GROUP BY dc.customer,dc.id;






    $stmt = $mysqli->prepare($sql);
    //$stmt->bind_param('i', $dialerID);

    if($stmt){
        $destination_number = '';

        $date_added = '';

        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();
        $stmt->bind_result($customer,$campaign,$xfers,$channels);

        $s = '';
        while($stmt->fetch()){

            $returnArr[] = array($customer,$campaign,$xfers,$channels);
        }
        return $returnArr;
    }



}


function getActivCampaignsView($mysqli,$mysqliRT){
    $s = "";
    $data = getActiveCampaignsRTstats($mysqli);


    foreach($data as $row){
        //error_log("getActivCampaignsView ROW :    ". json_encode($row));



        $dialerID = $row[0];
        $campaignID = $row[1];

        $dialerData = getDialerGeneralDetails($dialerID,$mysqli);
        $customer = $dialerData["name"];


        $campaignData = getCampaignGeneralDetails($campaignID,$mysqli);
        $campaignName  = $campaignData["name"];
        $campaignChannels  = intval($campaignData["channels"]);
        $campaignChannelsMultiplier = $campaignData["channels_multiplier"];

        $flowControlIndication = '';

        $percentages = round($campaignChannelsMultiplier * 100,0);

        if($campaignChannelsMultiplier > 1){

            $flowControlIndication = '<span class="stats-small__percentage stats-small__percentage--increase">'.$percentages.'%</span>';

        }

        if($campaignChannelsMultiplier < 1){
            $flowControlIndication = '<span class="stats-small__percentage stats-small__percentage--decrease">'.$percentages.'%</span>';


        }



        //
        // Active or not?

        $status = $campaignData["status"];
        $statusText = "(stopped)";

        /*
         *  Icons:
         *      -  play_circle_outline    :   button used to START campaign. Displayed when the campaign is stopped.
         *      - pause_circle_outline   : button used to STOP/PAUSE campaign. Displayed when the campaign is stopped.
         *      - snooze :  clock, the campaign is working according to schedule
         *
         *
         *      -  query_builder    :  clock icon, Link to set up the schedule
         *
         *
         *
         *  Campaign status:
         *    0   - stopped, not active  (manual or shedule - it's stopped anyway)
         *    1   - running now   (when running manually)
         *    2   - Schedule    (campaign is in schedule mode, it will run unless it's stopped.
         */

        if($status  == 1) $statusText = "";

        if($status  == 2) {
            // the campaign is working according to schedule
            $statusText = "Schedule : ";

            if( campaignCheckDialingHoursNow($campaignID,$mysqli)){
                $statusText = $statusText . "active";
            }else{

                $statusText = $statusText . "<small>waiting</small>";
            }

        }

        $progressPart = "(".$campaignData["progress"]."%)";








        //


        //$campaignTransfers = checkCampaignTransfers($campaignID,$mysqli);

        $campaignTransfers = "x";

        $transfers = $row[2];
        $channels = $row[3];

        $sum = $transfers + $channels;

        if($sum > 0)
            // Version without transfers count
            $s .= '<ul class="list-group list-group-small list-group-flush"><li class="list-group-item d-flex px-3"><span class="text-semibold">'.$customer.'</span>&nbsp;&nbsp;|&nbsp;&nbsp;<span class="text-semibold text-fiord-blue text-center">'.$campaignName.' '. $progressPart . ' '.$statusText.'</span>'.$flowControlIndication.'<span class="ml-auto text-right text-semibold text-reagent-gray"><strong class="text-success">'.$transfers.'</strong> '.$channels.'/'.$campaignChannels.'</span></li></ul>';



            // Version with total transfers count
            // $s .= '<ul class="list-group list-group-small list-group-flush"><li class="list-group-item d-flex px-3"><span class="text-semibold">'.$customer.'</span>&nbsp;&nbsp;|&nbsp;&nbsp;<span class="text-semibold text-fiord-blue text-center">'.$campaignName.'</span><span class="text-semibold text-success text-center">['.$campaignTransfers.']</span><span class="ml-auto text-right text-semibold text-reagent-gray"><strong class="text-success">'.$transfers.'</strong> '.$channels.'/'.$campaignChannels.'</span></li></ul>';


    }

    return $s;





}



// getDialersCapacityView


function getDialersCapacityView($mysqli){
// select c.id, c.name, c.max_channels,( select sum(channels) FROM dialer_campaigns WHERE customer = c.id AND status = 1) as CAMPAIGN_CHANNELS , (select sum(meter_value) from proxy_rt_stats WHERE meter_id IN (select id from dialer_campaigns WHERE customer = c.id) AND meter_name IN ("outgoing","transfers" )) as OUT_CHANNELS from customers c;
    $sql = 'select c.id, c.name, c.max_channels,( select sum(channels) FROM dialer_campaigns WHERE customer = c.id AND status = 1) as CAMPAIGN_CHANNELS , (select sum(meter_value) from proxy_rt_stats WHERE meter_id IN (select id from dialer_campaigns WHERE customer = c.id) AND meter_name IN ("outgoing","transfers" )) as OUT_CHANNELS, (select sum(meter_value) from proxy_rt_stats WHERE meter_id IN (select id from dialer_campaigns WHERE customer = c.id) AND meter_name IN ("transfers" )) as TRANSFERS from customers c;';

    /*

    +----+-----------------------+--------------+-------------------+--------------+
    | id | name                  | max_channels | CAMPAIGN_CHANNELS | OUT_CHANNELS |
    +----+-----------------------+--------------+-------------------+--------------+
    |  1 | _TEST_DIALER_         |         4500 |              NULL |            0 |
    |  4 | Li-Huzaif             |         1500 |               400 |          332 |
    |  8 | The Call BPO          |          600 |              NULL |            0 |
    | 12 | DELTA                 |         1000 |              1800 |          828 |
    | 14 | 5RAD                  |          300 |              NULL |            0 |
    | 16 | BSS                   |          500 |               130 |           93 |
    | 19 | GCS Demo              |          500 |              NULL |            0 |
    | 23 | Join LLC              |          500 |              NULL |         NULL |
    | 27 | Infuse Communications |          400 |              NULL |            0 |
    | 31 | Core360               |          600 |              NULL |            0 |
    | 35 | Bizworld              |         1000 |               400 |          172 |
    | 38 | Ionrg                 |         1500 |              NULL |         NULL |
    | 44 | Skytech               |          600 |              NULL |            0 |
    +----+-----------------------+--------------+-------------------+--------------+


    */

    $stmt = $mysqli->prepare($sql);

    if($stmt){


        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();
        $stmt->bind_result($id,$name,$max_channels,$campaigns_channels,$active_channels, $transfers);

        $s = '';
        while($stmt->fetch()){

            if($campaigns_channels>0){

                /*
                <div class="card-body p-0" id="noc_activeCampaigns"><ul class="list-group list-group-small list-group-flush"><li class="list-group-item d-flex px-3"><span class="text-semibold">Li-Huzaif</span>&nbsp;&nbsp;|&nbsp;&nbsp;<span class="text-semibold text-fiord-blue text-center">31st December 2018 (68.53%) </span><span class="ml-auto text-right text-semibold text-reagent-gray"><strong class="text-success">6</strong> 355/400</span></li></ul><ul class="list-group list-group-small list-group-flush"><li class="list-group-item d-flex px-3"><span class="text-semibold">Li-Huzaif</span>&nbsp;&nbsp;|&nbsp;&nbsp;<span class="text-semibold text-fiord-blue text-center">D1. Reign (77.23%) (stopped)</span><span class="ml-auto text-right text-semibold text-reagent-gray"><strong class="text-success">3</strong> 402/400</span></li></ul><ul class="list-group list-group-small list-group-flush"><li class="list-group-item d-flex px-3"><span class="text-semibold">Li-Huzaif</span>&nbsp;&nbsp;|&nbsp;&nbsp;<span class="text-semibold text-fiord-blue text-center">February 2nd Million_2 (100.00%) (stopped)</span><span class="ml-auto text-right text-semibold text-reagent-gray"><strong class="text-success">0</strong> 2/500</span></li></ul><ul class="list-group list-group-small list-group-flush"><li class="list-group-item d-flex px-3"><span class="text-semibold">Li-Huzaif</span>&nbsp;&nbsp;|&nbsp;&nbsp;<span class="text-semibold text-fiord-blue text-center">February 2nd Million_3 (100.00%) (stopped)</span><span class="ml-auto text-right text-semibold text-reagent-gray"><strong class="text-success">2</strong> 3/400</span></li></ul><ul class="list-group list-group-small list-group-flush"><li class="list-group-item d-flex px-3"><span class="text-semibold">Li-Huzaif</span>&nbsp;&nbsp;|&nbsp;&nbsp;<span class="text-semibold text-fiord-blue text-center">February 2nd Million_4 (99.81%) </span><span class="ml-auto text-right text-semibold text-reagent-gray"><strong class="text-success">1</strong> 68/300</span></li></ul><ul class="list-group list-group-small list-group-flush"><li class="list-group-item d-flex px-3"><span class="text-semibold">Li-Huzaif</span>&nbsp;&nbsp;|&nbsp;&nbsp;<span class="text-semibold text-fiord-blue text-center">February 3rd Million_2 (16.50%) </span><span class="ml-auto text-right text-semibold text-reagent-gray"><strong class="text-success">2</strong> 357/400</span></li></ul><ul class="list-group list-group-small list-group-flush"><li class="list-group-item d-flex px-3"><span class="text-semibold">Li-Huzaif</span>&nbsp;&nbsp;|&nbsp;&nbsp;<span class="text-semibold text-fiord-blue text-center">February 3rd Million_2 (14.55%) </span><span class="ml-auto text-right text-semibold text-reagent-gray"><strong class="text-success">0</strong> 552/500</span></li></ul><ul class="list-group list-group-small list-group-flush"><li class="list-group-item d-flex px-3"><span class="text-semibold">Li-Huzaif</span>&nbsp;&nbsp;|&nbsp;&nbsp;<span class="text-semibold text-fiord-blue text-center">13th March (13.79%) </span><span class="ml-auto text-right text-semibold text-reagent-gray"><strong class="text-success">6</strong> 532/500</span></li></ul><ul class="list-group list-group-small list-group-flush"><li class="list-group-item d-flex px-3"><span class="text-semibold">DELTA</span>&nbsp;&nbsp;|&nbsp;&nbsp;<span class="text-semibold text-fiord-blue text-center">12 Mar 18 (56.42%) </span><span class="ml-auto text-right text-semibold text-reagent-gray"><strong class="text-success">5</strong> 97/100</span></li></ul><ul class="list-group list-group-small list-group-flush"><li class="list-group-item d-flex px-3"><span class="text-semibold">DELTA</span>&nbsp;&nbsp;|&nbsp;&nbsp;<span class="text-semibold text-fiord-blue text-center">8 mar 19 1 RC (75.96%) </span><span class="ml-auto text-right text-semibold text-reagent-gray"><strong class="text-success">0</strong> 211/200</span></li></ul><ul class="list-group list-group-small list-group-flush"><li class="list-group-item d-flex px-3"><span class="text-semibold">DELTA</span>&nbsp;&nbsp;|&nbsp;&nbsp;<span class="text-semibold text-fiord-blue text-center">18 Apr 5 RC (54.06%) </span><span class="ml-auto text-right text-semibold text-reagent-gray"><strong class="text-success">0</strong> 92/100</span></li></ul><ul class="list-group list-group-small list-group-flush"><li class="list-group-item d-flex px-3"><span class="text-semibold">DELTA</span>&nbsp;&nbsp;|&nbsp;&nbsp;<span class="text-semibold text-fiord-blue text-center">9 Apr 1 RC (55.35%) </span><span class="ml-auto text-right text-semibold text-reagent-gray"><strong class="text-success">6</strong> 91/100</span></li></ul><ul class="list-group list-group-small list-group-flush"><li class="list-group-item d-flex px-3"><span class="text-semibold">DELTA</span>&nbsp;&nbsp;|&nbsp;&nbsp;<span class="text-semibold text-fiord-blue text-center">Lounge 27 Nov 6 RC (17.22%) </span><span class="ml-auto text-right text-semibold text-reagent-gray"><strong class="text-success">5</strong> 97/100</span></li></ul><ul class="list-group list-group-small list-group-flush"><li class="list-group-item d-flex px-3"><span class="text-semibold">DELTA</span>&nbsp;&nbsp;|&nbsp;&nbsp;<span class="text-semibold text-fiord-blue text-center">Lounge 14 Nov 4 RC (13.25%) </span><span class="ml-auto text-right text-semibold text-reagent-gray"><strong class="text-success">11</strong> 88/100</span></li></ul><ul class="list-group list-group-small list-group-flush"><li class="list-group-item d-flex px-3"><span class="text-semibold">DELTA</span>&nbsp;&nbsp;|&nbsp;&nbsp;<span class="text-semibold text-fiord-blue text-center">Lounge 23 Oct 2 RC (63.14%) </span><span class="ml-auto text-right text-semibold text-reagent-gray"><strong class="text-success">9</strong> 87/100</span></li></ul><ul class="list-group list-group-small list-group-flush"><li class="list-group-item d-flex px-3"><span class="text-semibold">DELTA</span>&nbsp;&nbsp;|&nbsp;&nbsp;<span class="text-semibold text-fiord-blue text-center">L 5 Mar 2 (53.37%) </span><span class="ml-auto text-right text-semibold text-reagent-gray"><strong class="text-success">7</strong> 93/100</span></li></ul><ul class="list-group list-group-small list-group-flush"><li class="list-group-item d-flex px-3"><span class="text-semibold">DELTA</span>&nbsp;&nbsp;|&nbsp;&nbsp;<span class="text-semibold text-fiord-blue text-center">19 Mar 5 RC (4.66%) (stopped)</span><span class="ml-auto text-right text-semibold text-reagent-gray"><strong class="text-success">0</strong> 1/100</span></li></ul><ul class="list-group list-group-small list-group-flush"><li class="list-group-item d-flex px-3"><span class="text-semibold">DELTA</span>&nbsp;&nbsp;|&nbsp;&nbsp;<span class="text-semibold text-fiord-blue text-center">2 Apr 200k 2 (3.46%) </span><span class="ml-auto text-right text-semibold text-reagent-gray"><strong class="text-success">7</strong> 78/100</span></li></ul><ul class="list-group list-group-small list-group-flush"><li class="list-group-item d-flex px-3"><span class="text-semibold">BSS</span>&nbsp;&nbsp;|&nbsp;&nbsp;<span class="text-semibold text-fiord-blue text-center">BSS mar 7 (78.17%) </span><span class="ml-auto text-right text-semibold text-reagent-gray"><strong class="text-success">2</strong> 195/200</span></li></ul><ul class="list-group list-group-small list-group-flush"><li class="list-group-item d-flex px-3"><span class="text-semibold">BSS</span>&nbsp;&nbsp;|&nbsp;&nbsp;<span class="text-semibold text-fiord-blue text-center">BSS NEW 1c (78.71%) </span><span class="ml-auto text-right text-semibold text-reagent-gray"><strong class="text-success">3</strong> 191/200</span></li></ul><ul class="list-group list-group-small list-group-flush"><li class="list-group-item d-flex px-3"><span class="text-semibold">Core360</span>&nbsp;&nbsp;|&nbsp;&nbsp;<span class="text-semibold text-fiord-blue text-center">Core2-7-3-2019 (49.01%) </span><span class="ml-auto text-right text-semibold text-reagent-gray"><strong class="text-success">13</strong> 146/160</span></li></ul><ul class="list-group list-group-small list-group-flush"><li class="list-group-item d-flex px-3"><span class="text-semibold">Core360</span>&nbsp;&nbsp;|&nbsp;&nbsp;<span class="text-semibold text-fiord-blue text-center">Core3-7-3-2019 (76.38%) </span><span class="ml-auto text-right text-semibold text-reagent-gray"><strong class="text-success">9</strong> 120/140</span></li></ul><ul class="list-group list-group-small list-group-flush"><li class="list-group-item d-flex px-3"><span class="text-semibold">Bizworld</span>&nbsp;&nbsp;|&nbsp;&nbsp;<span class="text-semibold text-fiord-blue text-center">Bizzworld(Frsh) (64.55%) </span><span class="ml-auto text-right text-semibold text-reagent-gray"><strong class="text-success">4</strong> 49/50</span></li></ul><ul class="list-group list-group-small list-group-flush"><li class="list-group-item d-flex px-3"><span class="text-semibold">Bizworld</span>&nbsp;&nbsp;|&nbsp;&nbsp;<span class="text-semibold text-fiord-blue text-center">Bizzworld(Frsh) (80.98%) </span><span class="ml-auto text-right text-semibold text-reagent-gray"><strong class="text-success">2</strong> 51/50</span></li></ul><ul class="list-group list-group-small list-group-flush"><li class="list-group-item d-flex px-3"><span class="text-semibold">Bizworld</span>&nbsp;&nbsp;|&nbsp;&nbsp;<span class="text-semibold text-fiord-blue text-center">Bizzworld(Frsh) (88.72%) </span><span class="ml-auto text-right text-semibold text-reagent-gray"><strong class="text-success">1</strong> 56/50</span></li></ul><ul class="list-group list-group-small list-group-flush"><li class="list-group-item d-flex px-3"><span class="text-semibold">Ionrg</span>&nbsp;&nbsp;|&nbsp;&nbsp;<span class="text-semibold text-fiord-blue text-center">File 74-08-18 (18.90%) </span><span class="ml-auto text-right text-semibold text-reagent-gray"><strong class="text-success">4</strong> 409/500</span></li></ul><ul class="list-group list-group-small list-group-flush"><li class="list-group-item d-flex px-3"><span class="text-semibold">Ionrg</span>&nbsp;&nbsp;|&nbsp;&nbsp;<span class="text-semibold text-fiord-blue text-center">IonRG.1 (35.42%) (stopped)</span><span class="ml-auto text-right text-semibold text-reagent-gray"><strong class="text-success">0</strong> 2/400</span></li></ul><ul class="list-group list-group-small list-group-flush"><li class="list-group-item d-flex px-3"><span class="text-semibold">Ionrg</span>&nbsp;&nbsp;|&nbsp;&nbsp;<span class="text-semibold text-fiord-blue text-center">IonRG.3 (10.47%) </span><span class="ml-auto text-right text-semibold text-reagent-gray"><strong class="text-success">4</strong> 479/500</span></li></ul><ul class="list-group list-group-small list-group-flush"><li class="list-group-item d-flex px-3"><span class="text-semibold">Ionrg</span>&nbsp;&nbsp;|&nbsp;&nbsp;<span class="text-semibold text-fiord-blue text-center">4th Million-2331J_3 (83.09%) </span><span class="ml-auto text-right text-semibold text-reagent-gray"><strong class="text-success">7</strong> 375/400</span></li></ul><ul class="list-group list-group-small list-group-flush"><li class="list-group-item d-flex px-3"><span class="text-semibold">Ionrg</span>&nbsp;&nbsp;|&nbsp;&nbsp;<span class="text-semibold text-fiord-blue text-center">4th Million-2331J_4 (100.00%) </span><span class="ml-auto text-right text-semibold text-reagent-gray"><strong class="text-success">2</strong> 5/400</span></li></ul><ul class="list-group list-group-small list-group-flush"><li class="list-group-item d-flex px-3"><span class="text-semibold">Ionrg</span>&nbsp;&nbsp;|&nbsp;&nbsp;<span class="text-semibold text-fiord-blue text-center">Ionrg 19-24-08_3 (54.76%) (stopped)</span><span class="ml-auto text-right text-semibold text-reagent-gray"><strong class="text-success">1</strong> 1/400</span></li></ul><ul class="list-group list-group-small list-group-flush"><li class="list-group-item d-flex px-3"><span class="text-semibold">Skytech</span>&nbsp;&nbsp;|&nbsp;&nbsp;<span class="text-semibold text-fiord-blue text-center">2222019004_ALL AGED HOT (64.27%) </span><span class="ml-auto text-right text-semibold text-reagent-gray"><strong class="text-success">0</strong> 59/80</span></li></ul><ul class="list-group list-group-small list-group-flush"><li class="list-group-item d-flex px-3"><span class="text-semibold">Skytech</span>&nbsp;&nbsp;|&nbsp;&nbsp;<span class="text-semibold text-fiord-blue text-center">21220191_11th Feb Firdousi HOT (17.00%) </span><span class="ml-auto text-right text-semibold text-reagent-gray"><strong class="text-success">1</strong> 47/60</span></li></ul><ul class="list-group list-group-small list-group-flush"><li class="list-group-item d-flex px-3"><span class="text-semibold">Skytech</span>&nbsp;&nbsp;|&nbsp;&nbsp;<span class="text-semibold text-fiord-blue text-center">30K AGED_Kyle Patrick 11th Dec (95.74%) </span><span class="ml-auto text-right text-semibold text-reagent-gray"><strong class="text-success">0</strong> 38/50</span></li></ul><ul class="list-group list-group-small list-group-flush"><li class="list-group-item d-flex px-3"><span class="text-semibold">James</span>&nbsp;&nbsp;|&nbsp;&nbsp;<span class="text-semibold text-fiord-blue text-center">Nfar New hampshire (42.80%) </span><span class="ml-auto text-right text-semibold text-reagent-gray"><strong class="text-success">0</strong> 350/400</span></li></ul><ul class="list-group list-group-small list-group-flush"><li class="list-group-item d-flex px-3"><span class="text-semibold">James</span>&nbsp;&nbsp;|&nbsp;&nbsp;<span class="text-semibold text-fiord-blue text-center">CHI new (94.52%) </span><span class="ml-auto text-right text-semibold text-reagent-gray"><strong class="text-success">3</strong> 897/1200</span></li></ul><ul class="list-group list-group-small list-group-flush"><li class="list-group-item d-flex px-3"><span class="text-semibold">James</span>&nbsp;&nbsp;|&nbsp;&nbsp;<span class="text-semibold text-fiord-blue text-center">MD CARP new (16.92%) </span><span class="ml-auto text-right text-semibold text-reagent-gray"><strong class="text-success">0</strong> 556/600</span></li></ul><ul class="list-group list-group-small list-group-flush"><li class="list-group-item d-flex px-3"><span class="text-semibold">James</span>&nbsp;&nbsp;|&nbsp;&nbsp;<span class="text-semibold text-fiord-blue text-center">MA a (17.04%) </span><span class="ml-auto text-right text-semibold text-reagent-gray"><strong class="text-success">0</strong> 381/400</span></li></ul><ul class="list-group list-group-small list-group-flush"><li class="list-group-item d-flex px-3"><span class="text-semibold">James</span>&nbsp;&nbsp;|&nbsp;&nbsp;<span class="text-semibold text-fiord-blue text-center">FL miami1 (41.53%) </span><span class="ml-auto text-right text-semibold text-reagent-gray"><strong class="text-success">2</strong> 410/400</span></li></ul><ul class="list-group list-group-small list-group-flush"><li class="list-group-item d-flex px-3"><span class="text-semibold">James</span>&nbsp;&nbsp;|&nbsp;&nbsp;<span class="text-semibold text-fiord-blue text-center">testtx (71.28%) </span><span class="ml-auto text-right text-semibold text-reagent-gray"><strong class="text-success">1</strong> 257/300</span></li></ul><ul class="list-group list-group-small list-group-flush"><li class="list-group-item d-flex px-3"><span class="text-semibold">James</span>&nbsp;&nbsp;|&nbsp;&nbsp;<span class="text-semibold text-fiord-blue text-center">Nfar CT (13.56%) </span><span class="ml-auto text-right text-semibold text-reagent-gray"><strong class="text-success">0</strong> 334/400</span></li></ul><ul class="list-group list-group-small list-group-flush"><li class="list-group-item d-flex px-3"><span class="text-semibold">James</span>&nbsp;&nbsp;|&nbsp;&nbsp;<span class="text-semibold text-fiord-blue text-center">CA adct (73.88%) </span><span class="ml-auto text-right text-semibold text-reagent-gray"><strong class="text-success">0</strong> 58/400</span></li></ul><ul class="list-group list-group-small list-group-flush"><li class="list-group-item d-flex px-3"><span class="text-semibold">AG Vacations</span>&nbsp;&nbsp;|&nbsp;&nbsp;<span class="text-semibold text-fiord-blue text-center">File15 (46.08%) (stopped)</span><span class="ml-auto text-right text-semibold text-reagent-gray"><strong class="text-success">1</strong> 1/150</span></li></ul><ul class="list-group list-group-small list-group-flush"><li class="list-group-item d-flex px-3"><span class="text-semibold">AG Vacations</span>&nbsp;&nbsp;|&nbsp;&nbsp;<span class="text-semibold text-fiord-blue text-center">Data3 (18.51%) (stopped)</span><span class="ml-auto text-right text-semibold text-reagent-gray"><strong class="text-success">1</strong> 1/200</span></li></ul><ul class="list-group list-group-small list-group-flush"><li class="list-group-item d-flex px-3"><span class="text-semibold">SS Solutions</span>&nbsp;&nbsp;|&nbsp;&nbsp;<span class="text-semibold text-fiord-blue text-center">Shiraz million 26th feb 1 (2.18%) </span><span class="ml-auto text-right text-semibold text-reagent-gray"><strong class="text-success">9</strong> 62/80</span></li></ul><ul class="list-group list-group-small list-group-flush"><li class="list-group-item d-flex px-3"><span class="text-semibold">Intact COMMUNICATIONS</span>&nbsp;&nbsp;|&nbsp;&nbsp;<span class="text-semibold text-fiord-blue text-center">LI Campaign 77 (87.68%) </span><span class="ml-auto text-right text-semibold text-reagent-gray"><strong class="text-success">5</strong> 164/200</span></li></ul><ul class="list-group list-group-small list-group-flush"><li class="list-group-item d-flex px-3"><span class="text-semibold">Zion Solution</span>&nbsp;&nbsp;|&nbsp;&nbsp;<span class="text-semibold text-fiord-blue text-center">13/03/19 02 (50.80%) </span><span class="ml-auto text-right text-semibold text-reagent-gray"><strong class="text-success">17</strong> 157/200</span></li></ul><ul class="list-group list-group-small list-group-flush"><li class="list-group-item d-flex px-3"><span class="text-semibold">groensolution</span>&nbsp;&nbsp;|&nbsp;&nbsp;<span class="text-semibold text-fiord-blue text-center">03/11/2019 EST A (4.88%) </span><span class="ml-auto text-right text-semibold text-reagent-gray"><strong class="text-success">0</strong> 253/250</span></li></ul><ul class="list-group list-group-small list-group-flush"><li class="list-group-item d-flex px-3"><span class="text-semibold">groensolution</span>&nbsp;&nbsp;|&nbsp;&nbsp;<span class="text-semibold text-fiord-blue text-center">03/13/2019 EST C (58.97%) </span><span class="ml-auto text-right text-semibold text-reagent-gray"><strong class="text-success">0</strong> 318/250</span></li></ul><ul class="list-group list-group-small list-group-flush"><li class="list-group-item d-flex px-3"><span class="text-semibold">Red Falcon</span>&nbsp;&nbsp;|&nbsp;&nbsp;<span class="text-semibold text-fiord-blue text-center">New York (11.87%) </span><span class="ml-auto text-right text-semibold text-reagent-gray"><strong class="text-success">1</strong> 95/100</span></li></ul><ul class="list-group list-group-small list-group-flush"><li class="list-group-item d-flex px-3"><span class="text-semibold">Red Falcon</span>&nbsp;&nbsp;|&nbsp;&nbsp;<span class="text-semibold text-fiord-blue text-center">Nevada (1.13%) </span><span class="ml-auto text-right text-semibold text-reagent-gray"><strong class="text-success">0</strong> 93/100</span></li></ul><ul class="list-group list-group-small list-group-flush"><li class="list-group-item d-flex px-3"><span class="text-semibold">Red Falcon</span>&nbsp;&nbsp;|&nbsp;&nbsp;<span class="text-semibold text-fiord-blue text-center">New Mexico (4.39%) </span><span class="ml-auto text-right text-semibold text-reagent-gray"><strong class="text-success">0</strong> 91/100</span></li></ul></div>
                */

                $s .= '<li class="list-group-item d-flex px-3"><span class="text-semibold text-fiord-blue">'.$name.' ['.$max_channels.']</span><span class="ml-auto text-right text-semibold text-reagent-gray"> <strong class="text-success">'.$transfers.'</strong> &nbsp;&nbsp;&nbsp; '.$campaigns_channels.' |  '.$active_channels.'</span></li>';
            }

        }

        return $s;
    }

    return "";



}








function getActivCampaignsViewMT($userDialers,$mysqli,$mysqliRT){

    if(!$userDialers){
        return "";
    }

    $s = "";
    $data = getActiveCampaignsRTstatsMT($userDialers,$mysqli);


    foreach($data as $row){
        //error_log("getActivCampaignsView ROW :    ". json_encode($row));



        $dialerID = $row[0];
        $campaignID = $row[1];

        $dialerData = getDialerGeneralDetails($dialerID,$mysqli);
        $customer = $dialerData["name"];


        $campaignData = getCampaignGeneralDetails($campaignID,$mysqli);
        $campaignName  = $campaignData["name"];
        $campaignChannels  = $campaignData["channels"];

        $transfers = $row[2];
        $channels = $row[3];


        $sum = $transfers + $channels;

        if($sum>0){  // SHOW only active (with channels >0)

            $s .= '<ul class="list-group list-group-small list-group-flush"><li class="list-group-item d-flex px-3"><span class="text-semibold">'.$customer.'</span>&nbsp;&nbsp;|&nbsp;&nbsp;<span class="text-semibold text-fiord-blue text-center">'.$campaignName.'</span><span class="ml-auto text-right text-semibold text-reagent-gray"><strong class="text-success">'.$transfers.'</strong> '.$channels.'/'.$campaignChannels.'</span></li></ul>';


        }




    }

    return $s;





}


function updateDialerListEntry($dlid,$call_answer,$amd,$call_result,$date_processed,$mysqli){


    //TODO:  date_processed should be also updated. Need this info in RT database first to query and pass here.


$sql = "UPDATE dialer_list SET call_answer = ?, amd = ?, call_result = ? , date_processed = now() WHERE id = ?;";

    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('iiii', $call_answer,$amd,$call_result,$dlid);
    $stmt->execute();


    return true;


}



function  removeCampaignRTdata($dlid, $mysqli){

    $sql = "DELETE from dialer_rt_list WHERE dlid = ? ;";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $dlid);
    $stmt->execute();
    return true;
}



function  removeCampaignRTcampaignData($campaignID, $mysqli){

    $sql = "DELETE from dialer_rt_list WHERE campaign = ? ;";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $campaignID);
    $stmt->execute();
    return true;
}








function mergeCampaignRTdataToPermDB($campaignID,$mysqliRT, $mysqli){

    $doIt = true;


    while($doIt){
    // 1. query RT database and get up to 1000 entries from finished campaign
    $sql = 'select dlid, call_answer,amd,call_result FROM dialer_rt_list WHERE campaign = ?  LIMIT 1000;';

    $stmt = $mysqliRT->prepare($sql);
    $stmt->bind_param('i', $campaignID);
    $stmt->execute();
    $stmt->store_result();

    if($stmt->num_rows == 0) $doIt = false;


    $stmt->bind_result($dlid, $call_answer, $amd, $call_result);

    while($stmt->fetch()){  // loop over the results
        $date_processed = 0;
        updateDialerListEntry($dlid,$call_answer,$amd,$call_result,$date_processed,$mysqli);   // MOVE
        removeCampaignRTdata($dlid, $mysqliRT);  // DELETE original

    }


    }



}


/// Application servers:
///
///

function getListOfApplicationServers($mysqli){

    $sql = "select id,name,type,ip_address,max_channels from dialer_app_servers;";

    $stmt = $mysqli->prepare($sql);


    if($stmt){


        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();
        //$log->LogDebug('SELECT statement executed, binding result.');
        $stmt->bind_result($id,$name,$type,$ip_address,$max_channels);

        $s = '';
        while($stmt->fetch()){

            $statusIcon = 'done';  // $retType
            if($type & 1  == 1) {
                $statusIcon = 'done';
            }else{
                $statusIcon = 'clear';
            }

            $oneRow = '<tr>'.
                '<td>'.$id.'</td>'.
                '<td>'.$name.'</td>'.

                '<td><div class="btn-group btn-group-sm" role="group" aria-label="Table row actions">'.
                '<button type="button" class="btn btn-white">'.
                '<i class="material-icons">'.$statusIcon.'</i>'.
                '</button>'.
                '</div></td>'.
                '<td>'.$ip_address.'</td>'.
                '<td>'.$max_channels.'</td>'.
                '<td>'.
                '<a href="dashboard.php?p=settings_app_server_edit&id='.$id.'"><button type="button" class="btn btn-white">'.
                'Edit <i class="material-icons"></i>'.
                '</button></a>'.
                '<button type="button" class="btn btn-white">'.
                'Delete <i class="material-icons"></i>'.
                '</button>'.
                '</div></td>'.
                '</tr>';

            $s .= $oneRow;

        }
        return $s;
    }else{
        error_log("VT30: error getting list of getListOfApplicationServers.");
    }
}


function getListOfApplicationServersArr($mysqli){

    $sql = "select id,name,type,ip_address,max_channels from dialer_app_servers;";

    $stmt = $mysqli->prepare($sql);

    $returnArr = array();

    if($stmt){


        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();
        //$log->LogDebug('SELECT statement executed, binding result.');
        $stmt->bind_result($id,$name,$type,$ip_address,$max_channels);

        $s = '';
        while($stmt->fetch()){

            $returnArr[] = array("id"=>$id,"name"=>$name,"type"=>$type,"ip_address"=>$ip_address,"max_channels"=>$max_channels);


        }
        return $returnArr;
    }else{
        error_log("VT30: error getting list of getListOfApplicationServers.");
    }
    return null;
}


function getApplicationServerDetails($serverID,$mysqli){

    $sql = "select id,name,type,ip_address,max_channels from dialer_app_servers where id = ?;";

    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('i', $serverID);
    $stmt->execute();
    $stmt->bind_result($id,$name,$type,$ip_address,$max_channels);
    $stmt->fetch();

    return array("name"=> $name,"type"=> $type, "ip_address" => $ip_address, "max_channels"=>$max_channels, "id"=> $id);

}



function applicationServerEdit($name,$type,$max_channels,$id,$mysqli){

    $sql = "UPDATE dialer_app_servers set name = ? , type = ?, max_channels = ? WHERE id = ?;";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('siii', $name,$type,$max_channels,$id);
    $stmt->execute();

    if($mysqli->error){
        error_log("VTDial:  Error in applicationServerEdit, details: ". $mysqli->error);
        return false;
    }
    return true;

}



// select id, name, max_channels from dialer_app_servers order by id asc;


// -- Outbound SIP trunks


function getListOfOutboundSIPproviders($mysqli){

    $sql = "select id,name,ip_address from outbound_sip_providers  WHERE is_deleted = 0 order by id asc;";

    $stmt = $mysqli->prepare($sql);


    if($stmt){


        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();
        //$log->LogDebug('SELECT statement executed, binding result.');
        $stmt->bind_result($id,$name,$ip_address);

        $s = '';
        while($stmt->fetch()){


            $oneRow = '<tr>'.
                '<td>'.$id.'</td>'.
                '<td>'.$name.'</td>'.
                '<td>'.$ip_address.'</td>'.
                '<td>'.
                '<a href="dashboard.php?p=settings_outbound_sip_provider_edit&id='.$id.'"><button type="button" class="btn btn-white" disabled>'.
                'Edit <i class="material-icons"></i>'.
                '</button></a>'.
                '<button type="button" class="btn btn-white" data-toggle="modal" data-target="#deleteSIPproviderModal" data-id="'.$id.'" data-name="'.$name.'">'.
                'Delete <i class="material-icons"></i>'.
                '</button>'.
                '</div></td>'.
                '</tr>';

            $s .= $oneRow;

        }
        return $s;
    }else{
        error_log("VT30: error getting list of getListOfOutboundSIPproviders.");
    }
}



/*

<button type="button" class="btn btn-white" data-toggle="modal" data-target="#deleteDialerModal" data-id="1" data-name="_TEST_DIALER_"><i class="material-icons"></i></button>
*/

function getListOfOutboundSIPprovidersForReseller($reseller,$mysqli){

    $sql = "select id,name,ip_address from outbound_sip_providers WHERE (reseller = ? OR reseller = 0 ) AND  is_deleted = 0  order by id asc;";

    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $reseller);

    if($stmt){


        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();
        //$log->LogDebug('SELECT statement executed, binding result.');
        $stmt->bind_result($id,$name,$ip_address);

        $s = '';
        while($stmt->fetch()){


            $oneRow = '<tr>'.
                '<td>'.$id.'</td>'.
                '<td>'.$name.'</td>'.
                '<td>'.$ip_address.'</td>'.
                '<td>'.
                '<a href="dashboard.php?p=settings_outbound_sip_provider_edit&id='.$id.'"><button type="button" class="btn btn-white" disabled>'.
                'Edit <i class="material-icons"></i>'.
                '</button></a>'.
                '<button type="button" class="btn btn-white" data-toggle="modal" data-target="#deleteSIPproviderModal" data-id="'.$id.'" data-name="'.$name.'">'.
                'Delete <i class="material-icons"></i>'.
                '</button>'.
                '</div></td>'.
                '</tr>';

            $s .= $oneRow;

        }
        return $s;
    }else{
        error_log("VT30: error getting list of getListOfOutboundSIPproviders.");
    }
}



function getListOfOutboundSIPprovidersForSelectView($mysqli,$selected = null){

   // error_log("getListOfOutboundSIPprovidersForSelectView [$selected] ");

    $sql = "select id,name from outbound_sip_providers WHERE  is_deleted = 0 ;";
    $stmt = $mysqli->prepare($sql);


    if($stmt){

        //$stmt->bind_param('i', $dialerID);
        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();
        $stmt->bind_result($id, $name);

        $s = '';
        while($stmt->fetch()){

            if($selected == $id){
                $oneRow = '<option value="'. $id.'" selected="selected">'.$name.'</option>';
                $s .= $oneRow;
            }else{

                $oneRow = '<option value="'. $id.'">'.$name.'</option>';
                $s .= $oneRow;
            }




        }
        return $s;
    }else{
        error_log("VT30: error getListOfOutboundSIPprovidersForSelectView : ". $mysqli->error);
    }
}



function getListOfOutboundSIPprovidersForSelectViewUnderReseller($mysqli,$reseller,$selected = null){

    $sql = "select id,name from outbound_sip_providers WHERE reseller = ?  AND is_deleted = 0 ;";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $reseller);

    if($stmt){

        //$stmt->bind_param('i', $dialerID);
        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();
        $stmt->bind_result($id, $name);

        $s = '';
        while($stmt->fetch()){

            if($selected == $id){
                $oneRow = '<option value="'. $id.'" selected="selected">'.$name.'</option>';
                $s .= $oneRow;
            }else{

                $oneRow = '<option value="'. $id.'">'.$name.'</option>';
                $s .= $oneRow;
            }




        }
        return $s;
    }else{
        error_log("VT30: error getListOfOutboundSIPprovidersForSelectView : ". $mysqli->error);
    }
}




function updateDialerListCallResultOnly($call_result, $campaign,$phone_number,$mysqli){
    $tableName = "dialer_list_" . $campaign;


    $sql = "update ".$tableName." set call_result  = ? WHERE campaign = ? and phone_number = ?;";

    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param('iis', $call_result, $campaign,$phone_number);
        $stmt->execute();
        $stmt->close();

        if($mysqli->error){
            error_log("updateDialerListCallResultOnly " . $mysqli->error);
            return false;
        }
    } else {
        return false;
    }

    return true;

}

//Users:
///
///
function getListOfUsersForDataTable($mysqli,$reseller = 0){

    $sql = "select id,name,type,reseller,dialers,account_status  from users WHERE reseller = ?;";

    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $reseller);

    if($stmt){


        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();
        //$log->LogDebug('SELECT statement executed, binding result.');
        $stmt->bind_result($id,$name,$type,$reseller,$dialers,$account_status);

        $s = '';
        while($stmt->fetch()){

            $statusIcon = 'done';  // $retType
            if($account_status & 1  == 1) {
                $statusIcon = 'done';
            }else{
                $statusIcon = 'clear';
            }

            $dialersArr = json_decode($dialers);

            $dialersList = "";

            if( count($dialersArr)>0 ){

                foreach($dialersArr as $oneDialer){
                    $dialerDetails = getDialerGeneralDetails($oneDialer,$mysqli);
                    $dialerFriendlyName = $dialerDetails["name"];

                    $dialersList .= $dialerFriendlyName . ', ';
                }

                $dialersList = substr($dialersList,0,-2);

            }

            $roleName = "-disabled-";
            switch($type){
                case 0:{  $roleName = "-disabled-";   }break;
                case 1:{  $roleName = "Super Admin";   }break;
                case 2:{  $roleName = "Dialer Admin";   }break;
                case 3:{  $roleName = "Reseller";   }break;
            }


            $resellerName = "-";
            if($reseller>0) $resellerName = getUserNameByID($reseller,$mysqli);

            $oneRow = '<tr>'.

                '<td>'.$name.'</td>'.

                '<td><div class="btn-group btn-group-sm" role="group" aria-label="Table row actions">'.
                '<button type="button" class="btn btn-white">'.
                '<i class="material-icons">'.$statusIcon.'</i>'.
                '</button>'.
                '</div></td>'.

                '<td>'.$roleName.'</td>'.
                '<td>'.$resellerName.'</td>'.
                '<td>'.$dialersList.'</td>'.

                '<td>'.
                '<a href="dashboard.php?p=settings_user_edit&id='.$id.'"><button type="button" class="btn btn-white">'.
                'Edit <i class="material-icons"></i>'.
                '</button></a>'.
                '<button type="button" class="btn btn-white" disabled>'.
                'Delete <i class="material-icons"></i>'.
                '</button>'.
                '</div></td>'.
                '</tr>';

            $s .= $oneRow;

        }
        return $s;
    }else{
        error_log("Error in getListOfUsersForDataTable.");
    }
}





function getUserNameByID($userID,$mysqli){

    $sql = "select name from admin_portal_users where id = ?;";

    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('i', $userID);
    $stmt->execute();
    $stmt->bind_result($name);
    $stmt->fetch();

    return $name;

}



function getUserTypeByID($userID,$mysqli){

    $sql = "select type from users where id = ?;";

    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('i', $userID);
    $stmt->execute();
    $stmt->bind_result($name);
    $stmt->fetch();


    //TODO:  do we need users?
    return 1;
    //return $name;

}



function getUserResellerID($userID,$mysqli){

    $sql = "select reseller from users where id = ?;";

    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('i', $userID);
    $stmt->execute();
    $stmt->bind_result($returnVal);
    $stmt->fetch();

    return $returnVal;

}

function getUserIDByLogin($username,$mysqli){

    $sql = "select id from admin_portal_users where username = ?;";

    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('s', $username);
    $stmt->execute();
    $stmt->bind_result($id);
    $stmt->fetch();

    return $id;

}




function getUserLoginByID($userID,$mysqli){

    $sql = "select username from admin_portal_users where id = ?;";

    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('i', $userID);
    $stmt->execute();
    $stmt->bind_result($name);
    $stmt->fetch();

    return $name;

}



function getUserStatusByID($userID,$mysqli){

    $sql = "select account_status from admin_portal_users where id = ?;";

    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('i', $userID);
    $stmt->execute();
    $stmt->bind_result($result);
    $stmt->fetch();

    return $result;

}





function getUserManagedDialersArrayByID($userID,$mysqli){
    $returnArr = array();

    $sql = "select dialers from admin_portal_users where id = ?;";

    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('i', $userID);
    $stmt->execute();
    $stmt->bind_result($dialers);
    $stmt->fetch();

    $dialersArr = json_decode($dialers);

    if(count($dialersArr) > 0 ){
        foreach($dialersArr as $oneDialer){

            $returnArr[] = $oneDialer;

        }

        return $returnArr;
    }else{

        return null;
    }



}








function getListOfDialersForSelectViewWithExcludes($excludedDialersArr,$mysqli){


    $s = '';

    $sql = "select id, name from customers;";
    $stmt = $mysqli->prepare($sql);


    if($stmt){

        //$stmt->bind_param('i', $dialerID);
        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();
        $stmt->bind_result($id, $name);


        while($stmt->fetch() ){

            if( count($excludedDialersArr) >0 ){

                if( !in_array($id,$excludedDialersArr) ) {

                    $oneRow = '<option value="' . $id . '">' . $name . '</option>';
                    $s .= $oneRow;

                }
            }else{
                $oneRow = '<option value="' . $id . '">' . $name . '</option>';
                $s .= $oneRow;
            }




        }
        return $s;

    }else{
        error_log("Error getListOfDialersForSelectViewWithExcludes : ". $mysqli->error);
    }



return '';


}



function getListOfDialersForResellerForSelectViewWithExcludes($excludedDialersArr,$reseller,$mysqli){


    $s = '';

    $sql = "select id, name from customers WHERE reseller = ?;";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $reseller);


    if($stmt){

        //$stmt->bind_param('i', $dialerID);
        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();
        $stmt->bind_result($id, $name);


        while($stmt->fetch() ){


            if( count($excludedDialersArr) >0 ){

                if( !in_array($id,$excludedDialersArr) ) {

                    $oneRow = '<option value="' . $id . '">' . $name . '</option>';
                    $s .= $oneRow;

                }
            }else{
                $oneRow = '<option value="' . $id . '">' . $name . '</option>';
                $s .= $oneRow;
            }




        }
        return $s;

    }else{
        error_log("Error getListOfDialersForSelectViewWithExcludes : ". $mysqli->error);
    }



    return '';


}






function addDialerToTheUser($userID,$newDialerID,$mysqli){

    $existingDialers = getUserManagedDialersArrayByID($userID,$mysqli);

    // add the new one:

    $existingDialers[] = $newDialerID;

    $newDailersList = json_encode($existingDialers);
   // error_log($newDailersList);

    // Proper method now:

    $sql = "update users set dialers = ? WHERE id = ?;";

    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('si', $newDailersList,$userID);
    $stmt->execute();
    //$stmt->bind_result($dialers);
    //$stmt->fetch();



    return true;

}


function removeDialerFromTheUser($userID,$dialerID,$mysqli){

    $existingDialers = getUserManagedDialersArrayByID($userID,$mysqli);

    $newDialers = array();

    foreach($existingDialers as $oneDialer){

        if($oneDialer != $dialerID) $newDialers[] = $oneDialer;

    }


    $newDailersList = json_encode($newDialers);
    //error_log($newDailersList);

    // Proper method now:

    $sql = "update users set dialers = ? WHERE id = ?;";

    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('si', $newDailersList,$userID);
    $stmt->execute();
    //$stmt->bind_result($dialers);
    //$stmt->fetch();



    return true;

}

function showUserPermissions($userID, $mysqli){
    $sql = "select DISTINCT permission_task from user_permissions;";



    $stmt = $mysqli->prepare($sql);

    if($stmt){


        $permission = '';

        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();
        $stmt->bind_result($permission);

        $s = '';
        while($stmt->fetch()){

            $retType = getUserPermission($userID,$permission,$mysqli);


            $checkbox = '<input class="userPermissionClass" id="'.$permission.'"  name="'.$permission.'" type="checkbox" ';  // $retType
            if($retType == "1") {
                $checkbox .= ' checked="checked"> Active';
            }else{
                $checkbox .= '> Not active';
            }

            $s .= '<tr><td>'.$permission.'</td> <td>'.$checkbox.'</td>   </tr>';

        }

        echo $s;


    }




}


function  getUserPermission($userID,$permission,$mysqli){
    $retValue = '';

    $sql = "select permission_value from user_permissions WHERE user_id = ? AND permission_task = ? LIMIT 1;";

    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('is', $userID,$permission);
    $stmt->execute();
    $stmt->bind_result($retValue);
    $stmt->fetch();



    return $retValue;

}



function  setUserPermission($userID,$permission,$newValue,$mysqli){
    $retValue = '';

    $sql = "INSERT into user_permissions (user_id,permission_task,permission_value) VALUES (?,?,?) ON DUPLICATE KEY UPDATE permission_value = ?; ";


    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('isii', $userID,$permission,$newValue,$newValue);
    $stmt->execute();
    //$stmt->bind_result($retValue);
    //$stmt->fetch();


    if($mysqli->error){
        error_log("setUserPermission error: " . $mysqli->error);
        return false;
    }
    return true;

}



function updateUserAccountDetails($userID,$name,$login,$status,$userStatus,$mysqli){

    $sql = "update users set name  = ?, username = ?, type = ?,account_status = ? , date_updated=now()  WHERE id = ?;";

    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param('ssiii', $name,$login,$status,$userStatus,$userID);
        $stmt->execute();
        $stmt->close();

        if($mysqli->error){
            error_log("updateUserAccountDetails " . $mysqli->error);
            return false;
        }
    } else {
        if($mysqli->error){
            error_log("updateUserAccountDetails 2" . $mysqli->error);
            return false;
        }
        return false;
    }

    return true;

}

// Outbound SIP proxy


function getListOfSIPproxyAddresses($mysqli){

    $sql = "select dp.id, dp.name,dp.in_ip_address,dp.out_ip_address,c.name FROM dialer_proxy_interfaces dp LEFT JOIN customers c ON dp.out_ip_address = c.ip_address order by dp.id ASC;";

    $stmt = $mysqli->prepare($sql);


    if($stmt){


        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();
        //$log->LogDebug('SELECT statement executed, binding result.');
        $stmt->bind_result($id,$name,$in_ip,$out_ip,$dialer);

        $s = '';
        while($stmt->fetch()){
            if( is_null($dialer)) $dialer = "-";

            $oneRow = '<tr>'.
                '<td>'.$id.'</td>'.
                '<td>'.$name.'</td>'.
                '<td>'.$in_ip.'</td>'.
                '<td>'.$out_ip.'</td>'.
                '<td>'.$dialer.'</td>'.
                '</tr>';

            $s .= $oneRow;

        }
        return $s;
    }else{
        error_log("error getting list of getListOfSIPproxyAddresses.");
    }
}



function getListOfSIPproxyAvailableIPAddressesForSelect($mysqli){

    $sql = "select dp.id, dp.name,dp.in_ip_address,dp.out_ip_address,c.name FROM dialer_proxy_interfaces dp LEFT JOIN customers c ON dp.out_ip_address = c.ip_address WHERE c.name IS NULL order by dp.id ASC;";

    $stmt = $mysqli->prepare($sql);
    if($mysqli->error){
        error_log("getListOfSIPproxyAvailableIPAddressesForSelect " . $mysqli->error);

    }

    if($stmt){


        $stmt->execute();   // Execute the prepared query.
        if($mysqli->error){
            error_log("getListOfSIPproxyAvailableIPAddressesForSelect " . $mysqli->error);

        }
        $stmt->store_result();
        if($mysqli->error){
            error_log("getListOfSIPproxyAvailableIPAddressesForSelect " . $mysqli->error);

        }
        //$log->LogDebug('SELECT statement executed, binding result.');
        $stmt->bind_result($id,$name,$in_ip,$out_ip,$dialer);
        if($mysqli->error){
            error_log("getListOfSIPproxyAvailableIPAddressesForSelect " . $mysqli->error);

        }
        $s = '';
        while($stmt->fetch()){


            $oneRow = '<option value="'.$out_ip.'">'. $out_ip .'</option>';


            $s .= $oneRow;

        }
        return $s;
    }else{
        error_log("error getting list of getListOfSIPproxyAvailableIPAddressesForSelect.");
    }
}



// Campaigns re-cycle



function defineCampaignHistoryNewRevision($dialerID,$campaignID,$mysqli){

    $newRevision = 1;
    $existingRevision = -1;

    $sql = "select max(increment) from dialer_campaigns_history WHERE customer = ? AND campaign = ?;";
    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('ii', $dialerID,$campaignID);

    $stmt->execute();

    $stmt->store_result();
    $stmt->bind_result($existingRevision);
    $stmt->fetch();
    $stmt->close();


    if (is_int($existingRevision)){

        if($existingRevision >0){

            $newRevision = $existingRevision+1;
        }
    }else{


    }


    return $newRevision;

}


function saveCampaignHistoryItem($dialerID,$campaignID,$campaignStatsJSON,$campaignRecycleRequest,$mysqli){

    // define history increment for given campaign.

    $newIncrement = defineCampaignHistoryNewRevision($dialerID,$campaignID,$mysqli);



    $sql = "INSERT INTO dialer_campaigns_history(customer,campaign,increment,campaign_result,campaign_recycle_request) VALUES (?,?,?,?,?); ";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('iiiss', $dialerID,$campaignID,$newIncrement,$campaignStatsJSON,$campaignRecycleRequest);
    $stmt->execute();


    return $newIncrement;




}


function  getCampaignHistoryResults($campaignID,$mysqli){

    $s="";

    $sql = "select increment, campaign_result from dialer_campaigns_history WHERE campaign = ? order by increment ASC;";
    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('i', $campaignID);

    $stmt->execute();

    $stmt->store_result();
    $stmt->bind_result($increment,$campaign_result);

    while($stmt->fetch()){


        $cResult = json_decode($campaign_result,true);
        // {"phonebooks":"[\"316\"]","campaignName":"11 Dec 5","allNumbers":98953,"attempts":98892,"answers":32438,"answersHuman":10057,"transfers":20,"sales":0}

        $attempts = $cResult["attempts"];
        $answers = $cResult["answers"];
        $human = $cResult["answersHuman"];
        $transfers = $cResult["transfers"];
        $sales = $cResult["sales"];


        $row = '<tr><td>'.$increment.'</td><td>'.$attempts.'</td><td>'.$answers.'</td><td>'.$human.'</td><td>'.$transfers.'</td><td>'.$sales.'</td></tr>';

        $s .= $row;
    }



    return $s;


}








function campaignRecycleListBy_Answer_Result($campaign,$call_answer,$call_result,$mysqli){
    $tableName = "dialer_list_" . $campaign;



    $sql = "UPDATE ".$tableName." set date_queued = 0, call_result = 0, amd = 0 , call_answer = 0 WHERE campaign = ? AND call_answer = ? AND call_result = ?; ";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('iii', $campaign,$call_answer,$call_result);
    $stmt->execute();

    return true;


}


function campaignRecycleListBy_Answer_AMD($campaign,$call_answer,$amd_result,$mysqli){
    $tableName = "dialer_list_" . $campaign;


    $sql = "UPDATE ".$tableName." set date_queued = 0, call_result = 0, amd = 0 , call_answer = 0 WHERE campaign = ? AND call_answer = ? AND amd = ?; ";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('iii', $campaign,$call_answer,$amd_result);
    $stmt->execute();

    return true;


}


function canUserListenIVRrecording($userID,$fileID,$mysqli){

    $userType = getUserTypeByID($userID,$mysqli);

    if($userType == 1){
        // the user is "super admin", can listen all
        return true;
    }


    $managedDialersForSelect = getUserManagedDialersListForSelect($userID,$mysqli);

    $sql = "SELECT count(*) from dialer_ivr_files WHERE id = ? AND customer IN($managedDialersForSelect);";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $fileID);
    $stmt->execute();
    $stmt->bind_result($fileFound);
    $stmt->fetch();
    $stmt->close();

    if($fileFound > 0) return true;
    else return false;

}



function addNewUserAccount($name,$login,$password,$accountType,$reseller,$mysqli){

    // INSERT INTO users (username,password,type) VALUES ("intactcomm",sha2("12Age93knFwqo3",256),2);
    $sql = "INSERT INTO users(name,username,password,type, reseller) VALUES (?,?, sha2(?,256), ?,?);";

    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('sssii', $name,$login,$password,$accountType,$reseller);
    $stmt->execute();
    $newUserID = $stmt->insert_id;

    if($mysqli->error){
        error_log("VTDial:  Error in addNewUserAccount, details: ". $mysqli->error);
        return false;
    }

    return $newUserID;

}

function random_str( $length ) {

    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    return substr(str_shuffle($chars),0,$length);

}



function resetUserPassword($id,$newPassword,$mysqli){

    $sql = "UPDATE users SET password =  sha2(?,256) WHERE id = ?;";

    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('si', $newPassword,$id);
    $stmt->execute();


    if($mysqli->error){
        error_log("Error in resetUserPassword, details: ". $mysqli->error);
        return false;
    }

    return true;



}



function getSumChannelsAllocatedByClientsViaReseller($reseller,$mysqli){

    // define history increment for given campaign.


    $sql = "SELECT sum(max_channels) FROM customers WHERE reseller = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $reseller);
    $stmt->execute();
    $stmt->bind_result($returnedValue);
    $stmt->fetch();
    $stmt->close();

    return $returnedValue;

}


function getCountOfClientsManagedByReseller($reseller,$mysqli){

    // define history increment for given campaign.


    $sql = "SELECT count(*) FROM customers WHERE reseller = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $reseller);
    $stmt->execute();
    $stmt->bind_result($returnedValue);
    $stmt->fetch();
    $stmt->close();

    return $returnedValue;

}



// reseller_channels_limit


function getResellerChannelsLimit($reseller,$mysqli){

    // define history increment for given campaign.


    $sql = "SELECT reseller_channels_limit FROM users WHERE id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $reseller);
    $stmt->execute();
    $stmt->bind_result($returnedValue);
    $stmt->fetch();
    $stmt->close();

    return $returnedValue;

}


function getResellerClientsLimit($reseller,$mysqli){

    // define history increment for given campaign.


    $sql = "SELECT reseller_dialers_limit FROM users WHERE id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $reseller);
    $stmt->execute();
    $stmt->bind_result($returnedValue);
    $stmt->fetch();
    $stmt->close();

    return $returnedValue;

}


function addNewOutboundSIPprovider($name,$ip_address,$reseller,$mysqli){

    // INSERT INTO users (username,password,type) VALUES ("intactcomm",sha2("12Age93knFwqo3",256),2);
    $sql = "INSERT INTO outbound_sip_providers(name,ip_address, reseller) VALUES (?,?,?);";

    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('ssi', $name,$ip_address,$reseller);
    $stmt->execute();
    $newID = $stmt->insert_id;

    if($mysqli->error){
        error_log("VTDial:  Error in addNewOutboundSIPprovider, details: ". $mysqli->error);
        return false;
    }

    return $newID;

}

function isTheProviderUsedWithAnyCampaign($providerID,$mysqli){
    $usedBy = 0;


    $sql = "select count(*) from customers WHERE sip_provider = ?;";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $providerID);
    $stmt->execute();
    $stmt->bind_result($usedBy);
    $stmt->fetch();
    $stmt->close();


    return $usedBy;
}

function deleteSIPprovider($providerID,$mysqli){
    $usedBy = 0;

    $sql = "UPDATE outbound_sip_providers set is_deleted = 1 WHERE id = ?;";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $providerID);
    $stmt->execute();
    $stmt->close();

    return $usedBy;
}


function getOutboundSIPproviderResellerID($providerID,$mysqli){
    $reseller = 0;


    $sql = "select reseller from outbound_sip_providers WHERE id = ?;";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $providerID);
    $stmt->execute();
    $stmt->bind_result($reseller);
    $stmt->fetch();
    $stmt->close();


    return $reseller;
}


// =======================================

function getCustomerDomainsListAsText($customerID,$mysqli){
    $domains = "";


    $sql = "select domain_name from domains WHERE customer = ?;";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $customerID);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($domain_name);

    while($stmt->fetch()){
        $domains = $domains . " " . $domain_name;



    }
    $stmt->close();




    return $domains;
}




function getUsersListForCustomerDetailsPage($customerID,$mysqli,$mysqli_proxy){

    $sql = "select u.id,u.name,u.username,d.domain_name FROM users u JOIN domains d ON u.domain = d.id WHERE u.customer = ?;";
    $stmt = $mysqli->prepare($sql);


    if($stmt){

        $stmt->bind_param('i', $customerID);
        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();
        $stmt->bind_result($id, $name,$extension, $domain_name);
        $s = '';
        while($stmt->fetch()){

            $status = "Offline";

            $activeRegistrations =  getSIPuserRegistrationDetailsFromProxyDB($extension,$domain_name,$mysqli_proxy);

            if(count($activeRegistrations) > 0){
                $how_many_registrations = count($activeRegistrations);

                $status = "Online (" . $how_many_registrations . ")";


            }



            $oneRow = '<tr>'.
                '<td>'.$name.'</td>'.
                '<td>'.$extension.'</td>'.
                '<td>' . $domain_name .'</td>'.
                '<td>' . $status . '</td>'.
                '<td class="text-center"><div class="btn-group btn-group-sm" role="group" aria-label="Table row actions">'.
                '<a href="dashboard.php?p=customer_user_details&id='.$id.'"><button type="button" class="btn btn-white">'.
                'Edit <i class="material-icons"></i>'.
                '</button></a>'.
                '<button  class="btn btn-white" data-toggle="modal" data-target="#deleteUserModal" data-id="'.$id.'" data-name="'.$name.'"><i class="material-icons"></i></button>'.
                '</div></td>'.
                '</tr>';

            $s .= $oneRow;

        }
        return $s;
    }else{
        error_log("VTPBX: error getting list of users for customer.");
    }


}


function getUsersListForSelectView($customerID,$mysqli,$selectedExtension = null){

    $sql = "select u.id,u.name,u.username FROM users u   WHERE u.customer = ?;";
    $stmt = $mysqli->prepare($sql);


    if($stmt){

        $stmt->bind_param('i', $customerID);
        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();
        $stmt->bind_result($id, $name,$extension);
        $s = '';
        while($stmt->fetch()){

            $status = "Online/Offline";



            $oneRow = '<option value="' . $extension . '" ';

            if(isset($selectedExtension) && $selectedExtension == $extension)
                $oneRow .= ' selected="selected" ';

            $oneRow .= '>' . $extension . ' - ' . $name . '</option>';



            $s .= $oneRow;

        }
        return $s;
    }else{
        error_log("VTPBX: error getting list of users for select view.");
    }


}


function getUserIdsArrayForOneCustomer($customerID,$mysqli){

    $sql = "select id FROM users    WHERE customer = ?;";
    $retArray = array();
    $stmt = $mysqli->prepare($sql);


    if($stmt){

        $stmt->bind_param('i', $customerID);
        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();
        $stmt->bind_result($id);

        while($stmt->fetch()){

            $retArray[] = $id;

        }
        return $retArray;
    }else{
        error_log("CTPBX: error in getUserIdsArrayForOneCustomer.");
        return $retArray;
    }

}


function getUserIdsArray($mysqli){

    $sql = "select id FROM users;";
    $retArray = array();
    $stmt = $mysqli->prepare($sql);


    if($stmt){


        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();
        $stmt->bind_result($id);

        while($stmt->fetch()){

            $retArray[] = $id;

        }
        return $retArray;
    }else{
        error_log("CTPBX: error in getUserIdsArray.");
        return $retArray;
    }

}







function getExtensionsForCustomerARR($customerID,$mysqli){
    $returnArray = array();




    // =========

    $sql = "select id,extension, action_type, action_def from extension_numbers WHERE customer = ?;;";
    $stmt = $mysqli->prepare($sql);
    if($stmt){

        $stmt->bind_param('i', $customerID);
        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();
        $stmt->bind_result($id,$extension, $action_type,$action_def);

        while($stmt->fetch()){

            $action_definition = $action_def;

            switch($action_type){
                case "QUEUE":{

                    $action_definition = getQueueNameByID($action_def,$mysqli);


                }break;
                case "QUEUE_PICKUP":{

                    $action_definition = getQueueNameByID($action_def,$mysqli);


                }break;
                case "CONFERENCE":{
                    $action_definition = getConferenceNameByID($action_def,$mysqli);


                }break;
                case "PARKING":{


                }break;
                case "GROUP":{


                }break;

                default: {$action_definition = "";}
            }

            $returnArray[] = array(
                "id" =>   $id,
                "extension" => $extension,
                "action_type" => $action_type,
                "action_def" => $action_def,
                "action_def_name" => $action_definition
            );

        }


    }else{
        error_log("CTPBX: error getting ARR of extensions for extensions list on the customer details page.");
    }


    return $returnArray;
}

function getAllExtensionsARR($mysqli){
    $returnArray = array();




    // =========

    $sql = "select id,customer, extension, action_type, action_def from extension_numbers ;";
    $stmt = $mysqli->prepare($sql);
    if($stmt){

       // $stmt->bind_param('i', $customerID);
        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();
        $stmt->bind_result($id,$customer,$extension, $action_type,$action_def);

        while($stmt->fetch()){

            $action_definition = $action_def;

            switch($action_type){
                case "QUEUE":{

                    $action_definition = getQueueNameByID($action_def,$mysqli);


                }break;
                case "QUEUE_PICKUP":{

                    $action_definition = getQueueNameByID($action_def,$mysqli);


                }break;
                case "CONFERENCE":{
                    $action_definition = getConferenceNameByID($action_def,$mysqli);


                }break;
                case "PARKING":{


                }break;
                case "GROUP":{


                }break;

                default: {$action_definition = "";}
            }

            $returnArray[] = array(
                "id" =>   $id,
                "tenant" => $customer,
                "extension" => $extension,
                "action_type" => $action_type,
                "action_def" => $action_def,
                "action_def_name" => $action_definition
            );

        }


    }else{
        error_log("CTPBX: error getting ARR of extensions for extensions list on the customer details page.");
    }


    return $returnArray;
}



function getExtensionDetailsForCustomerByExtensionNumberARR($customerID,$extension_number,$mysqli){
    $returnArray = array();

    // =========

    $sql = "select id,extension, action_type, action_def from extension_numbers WHERE customer = ? AND extension = ?;";
    $stmt = $mysqli->prepare($sql);
    if($stmt){

        $stmt->bind_param('is', $customerID, $extension_number);
        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();
        $stmt->bind_result($id,$extension, $action_type,$action_def);

        if( $stmt->fetch() ){

            $action_definition = $action_def;

            switch($action_type){
                case "QUEUE":{

                    $action_definition = getQueueNameByID($action_def,$mysqli);


                }break;
                case "QUEUE_PICKUP":{

                    $action_definition = getQueueNameByID($action_def,$mysqli);


                }break;
                case "CONFERENCE":{
                    $action_definition = getConferenceNameByID($action_def,$mysqli);


                }break;
                case "PARKING":{


                }break;
                case "GROUP":{


                }break;

                default: {$action_definition = "";}
            }

            $returnArray = array(
                "id" =>   $id,
                "extension" => $extension,
                "action_type" => $action_type,
                "action_def" => $action_def,
                "action_def_name" => $action_definition
            );

        }


    }else{
        error_log("CTPBX: error getting ARR of extensions for extensions list on the customer details page.");
    }


    return $returnArray;
}


function getExtensionDetailsARRbyID($cextension_id,$mysqli){
    $returnArray = array();

    // =========

    $sql = "select id,customer,domain,extension, action_type, action_def from extension_numbers WHERE id = ?;";
    $stmt = $mysqli->prepare($sql);
    if($stmt){

        $stmt->bind_param('i', $cextension_id);
        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();
        $stmt->bind_result($id,$customer,$domain, $extension, $action_type,$action_def);

        if( $stmt->fetch() ){

            $action_definition = $action_def;

            switch($action_type){
                case "QUEUE":{

                    $action_definition = getQueueNameByID($action_def,$mysqli);


                }break;
                case "QUEUE_PICKUP":{

                    $action_definition = getQueueNameByID($action_def,$mysqli);


                }break;
                case "CONFERENCE":{
                    $action_definition = getConferenceNameByID($action_def,$mysqli);


                }break;
                case "PARKING":{


                }break;
                case "GROUP":{


                }break;

                default: {$action_definition = "";}
            }

            $returnArray = array(
                "id" =>   $id,
                "customer" => $customer,
                "domain" => $domain,
                "extension" => $extension,
                "action_type" => $action_type,
                "action_def" => $action_def,
                "action_def_name" => $action_definition
            );

        }


    }else{
        error_log("CTPBX: error getting ARR of extensions for extensions list on the customer details page.");
    }


    return $returnArray;
}















function getExtensionsListForCustomerDetailsPage($customerID,$mysqli){
    $s = '';


    $sql = "select id, username, name from users WHERE customer = ?;;";
    $stmt = $mysqli->prepare($sql);
    if($stmt){

        $stmt->bind_param('i', $customerID);
        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();
        $stmt->bind_result($id,$extension, $name);

        while($stmt->fetch()){

            $status = "Online/Offline";

            $oneRow = '<tr>'.
                '<td>'.$extension.'</td>'.
                '<td>User</td>'.
                '<td>' . $name .'</td>'.

                '<td class="text-center"><div class="btn-group btn-group-sm" role="group" aria-label="Table row actions">'.
                ''.
                ''.
                '</div></td>'.
                '</tr>';

            $s .= $oneRow;

        }

    }else{
        error_log("VTPBX: error getting list of users for extensions list on the customer details page.");
    }


    // =========

    $sql = "select id,extension, action_type, action_def from extension_numbers WHERE customer = ?;;";
    $stmt = $mysqli->prepare($sql);
    if($stmt){

        $stmt->bind_param('i', $customerID);
        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();
        $stmt->bind_result($id,$extension, $action_type,$action_def);

        while($stmt->fetch()){

            $action_definition = $action_def;

            switch($action_type){
                case "QUEUE":{

                    $action_definition = getQueueNameByID($action_def,$mysqli);


                }break;
                case "QUEUE_PICKUP":{

                    $action_definition = getQueueNameByID($action_def,$mysqli);


                }break;
                case "CONFERENCE":{
                    $action_definition = getConferenceNameByID($action_def,$mysqli);


                }break;
                case "PARKING":{


                }break;
                case "GROUP":{


                }break;

                default: {$action_definition = "";}
            }


            $oneRow = '<tr>'.
                '<td>'.$extension.'</td>'.

                '<td>' . $action_type .'</td>'.
                '<td>' . $action_definition .'</td>'.
                '<td class="text-center"><div class="btn-group btn-group-sm" role="group" aria-label="Table row actions">'.
                '<button  class="btn btn-white" data-toggle="modal" data-target="#editExtensionModal" data-id="'.$id.'" data-name="'.$extension.'"   data-action_type="'.$action_type.'"  data-action_def="'.$action_def.'"     >Edit <i class="material-icons"> </i></button>'.
                '<button  class="btn btn-white" data-toggle="modal" data-target="#deleteExtensionModal" data-id="'.$id.'" data-name="'.$extension.'"><i class="material-icons"></i></button>'.
                '</div></td>'.
                '</tr>';

            $s .= $oneRow;

        }

    }else{
        error_log("VTPBX: error getting list of users for extensions list on the customer details page.");
    }











    return $s;
}



function getActionsListForCustomerDetailsPage($customerID,$mysqli){
    $s = '';


    $sql = "select id, name, ivr_playback_file,webhook_url from actions WHERE customer = ?;";
    $stmt = $mysqli->prepare($sql);
    if($stmt){

        $stmt->bind_param('i', $customerID);
        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();
        $stmt->bind_result($id, $name, $ivr_playback_file,$webhook_url);

        while($stmt->fetch()){

            $members = "";

            $ivr_playback_file_details = getIVRFileDetails($ivr_playback_file, $mysqli);
            $ivr_playback_file_name = $ivr_playback_file_details["file_description"];


            //$ivr_playback_file_name = getIVRFileNameByID($ivr_playback_file, $mysqli);


            $oneRow = '<tr>'.
                '<td>'.$name.'</td>'.
                '<td>' . $webhook_url .'</td>'.
                '<td>'.$ivr_playback_file_name.'</td>'.



                '<td class="text-center"><div class="btn-group btn-group-sm" role="group" aria-label="Table row actions">'.
                '<a href="dashboard.php?p=customer_action_details&id='.$id.'"><button type="button" class="btn btn-white" data-toggle="modal"  >Edit <i class="material-icons"></i>'.
                '</button></a>'.
                 '<button  class="btn btn-white" data-toggle="modal" data-target="#deleteActionModal" data-id="'.$id.'" data-name="'.$name.'"         ><i class="material-icons"></i></button>'.
                '</div></td>'.



                '</tr>';

            $s .= $oneRow;

        }

    }else{
        error_log("VTPBX: error  in getActionsListForCustomerDetailsPage");
    }




    return $s;
}




function getGroupsListForCustomerDetailsPage($customerID,$mysqli){
    $s = '';


    $sql = "select id, name, ring_strategy,group_members from groups WHERE customer = ?;";
    $stmt = $mysqli->prepare($sql);
    if($stmt){

        $stmt->bind_param('i', $customerID);
        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();
        $stmt->bind_result($id, $name, $ring_strategy,$group_members);

        while($stmt->fetch()){

            $members = "";

            $ring_strategyName = getGroupRingStrategyNameByID($ring_strategy);



            $oneRow = '<tr>'.
                '<td>'.$name.'</td>'.
                '<td>'.$ring_strategyName.'</td>'.
                '<td>' . $members .'</td>'.


                '<td class="text-center"><div class="btn-group btn-group-sm" role="group" aria-label="Table row actions">'.
                '<a href="dashboard.php?p=customer_group_details&id='.$id.'"><button type="button" class="btn btn-white" data-toggle="modal"  >Edit <i class="material-icons"></i>'.
                '</button></a>'.
                // '<button  class="btn btn-white" data-toggle="modal" data-target="#deleteGroupModal" data-id="'.$id.'" data-name="'.$name.'"         ><i class="material-icons"></i></button>'.
                '</div></td>'.



                '</tr>';

            $s .= $oneRow;

        }

    }else{
        error_log("VTPBX: error  in getGroupsListForCustomerDetailsPage");
    }




    return $s;
}




function updateGroupDetails($groupID,$ring_strategy,$group_members,$group_failover_extension,$mysqli){


    $sql = "UPDATE groups set ring_strategy = ?, group_members = ?,group_failover_extension=?  WHERE id = ?;";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('isis', $ring_strategy,$group_members,$group_failover_extension, $groupID );
    $stmt->execute();
    $stmt->close();

    return true;
}


function updateActionDetails($actionID,$name,$webhook_url,$ivr_playback_file,$mysqli){


    $sql = "UPDATE actions set name = ?, webhook_url = ?,ivr_playback_file=?  WHERE id = ?;";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('ssii', $name,$webhook_url,$ivr_playback_file, $actionID );
    $stmt->execute();
    $stmt->close();

    return true;
}











function getDIDsListForCustomerDetailsPage($customerID,$mysqli){
    $s = '';


    $sql = "select d.id, dom.domain_name, d.did_number, d.action_type, d.action_def,d.pre_answer_playback,d.pre_answer_playback_file FROM did_numbers d JOIN domains dom ON d.domain = dom.id WHERE d. customer = ?;";
    $stmt = $mysqli->prepare($sql);
    if($stmt){

        $stmt->bind_param('i', $customerID);
        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();
        $stmt->bind_result($id, $domain, $did_number, $action_type, $action_def,$pre_answer_playback, $pre_answer_playback_file);

        while($stmt->fetch()){

            $action_definition = $action_def;

            switch($action_type){
                case "QUEUE":{

                    $action_definition = getQueueNameByID($action_def,$mysqli);


                }break;

                case "GROUP":{


                }break;

                case "IVR":{
                    $action_definition = getIVRmenuNameByID($action_def,$mysqli);

                }break;

                case "CONFERENCE":{
                    $action_definition = getConferenceNameByID($action_def,$mysqli);

                }break;

            }





            $oneRow = '<tr>'.
                '<td>'.$did_number.'</td>'.
                '<td>'.$domain .'</td>'.
                '<td>' . $action_type .'</td>'.
                '<td>' . $action_definition .'</td>'.
                '<td class="text-center"><div class="btn-group btn-group-sm" role="group" aria-label="Table row actions">'.
                '<button type="button" class="btn btn-white" data-toggle="modal" data-target="#editDIDModal" data-id="'.$id.'" data-name="'.$did_number.'" 
                data-action_type="'.$action_type.'"   data-action_def="'.$action_def.'"     data-pre_answer_playback="'.$pre_answer_playback.'"    data-pre_answer_playback_file="'.$pre_answer_playback_file.'"             >Edit <i class="material-icons"></i>'.
                '</button></a>'.
                '<button  class="btn btn-white" data-toggle="modal" data-target="#deleteDIDModal" data-id="'.$id.'" data-name="'.$did_number.'"         ><i class="material-icons"></i></button>'.
                '</div></td>'.
                '</tr>';

            $s .= $oneRow;

        }

    }else{
        error_log("VTPBX: error getting list of DIDs for the list on the customer details page.");
    }



    return $s;
}


function getListOfDIDnumbers($mysqli){
    $returnArr = array();


    $sql = "select d.id, d.domain, dom.domain_name, d.did_provider, dp.name,d.did_number, d.action_type, d.action_def,d.sip_registration,d.pre_answer_playback,d.pre_answer_playback_file FROM did_numbers d JOIN domains dom ON d.domain = dom.id  JOIN did_providers dp ON d.did_provider = dp.id   ;";
    $stmt = $mysqli->prepare($sql);
    if($stmt){

        //$stmt->bind_param('i', $customerID);
        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();
        $stmt->bind_result($id, $domain, $domain_name, $did_provider, $did_provider_name, $did_number, $action_type, $action_def,$sip_registration,$pre_answer_playback,$pre_answer_playback_file);

        while($stmt->fetch()){

            $sip_registrationArr = json_decode($sip_registration,true);
            $tts_text = "";

            if($pre_answer_playback_file >0 ){
                $ivrFileDetails = getIVRFileDetails($pre_answer_playback_file, $mysqli);
                /*
                            $retArray["id"] = $id;
                            $retArray["customer"] = $customer;
                            $retArray["file_description"] = $file_description;
                            $retArray["file_name"] = $file_name;

                            $retArray["tts_text"] = $tts_text;
                            $retArray["s3_url"] = $s3_url;
                 */

                $tts_text = $ivrFileDetails["tts_text"];


            }



            $returnArr[] = array(
                "id" => $id,
                "domain" => $domain,
                "domain_name" => $domain_name,
                "did_provider" => $did_provider,
                "did_provider_name" => $did_provider_name,
                "did_number" => $did_number,
                "action_type" => $action_type,
                "action_def" => $action_def,


                "pre_answer_playback" => $pre_answer_playback,
                "pre_answer_playback_file" => $pre_answer_playback_file,
                "tts_text" => $tts_text,


                "sip_registration" => $sip_registrationArr

            );

        }

    }else{
        error_log("CTPBX: error in getListOfDIDnumbers.");
    }



    return $returnArr;
}



function getListOfDIDnumbersForCustomer($customerID,$mysqli){
    $returnArr = array();


    $sql = "select d.id, d.domain, dom.domain_name, d.did_provider, dp.name,d.did_number, d.action_type, d.action_def,d.sip_registration,d.pre_answer_playback,d.pre_answer_playback_file FROM did_numbers d JOIN domains dom ON d.domain = dom.id  JOIN did_providers dp ON d.did_provider = dp.id WHERE  d.customer = ? ;";
    $stmt = $mysqli->prepare($sql);
    if($stmt){

        $stmt->bind_param('i', $customerID);
        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();
        $stmt->bind_result($id, $domain, $domain_name, $did_provider, $did_provider_name, $did_number, $action_type, $action_def,$sip_registration,$pre_answer_playback,$pre_answer_playback_file);

        while($stmt->fetch()){

            $sip_registrationArr = json_decode($sip_registration,true);


            $tts_text = "";

            if($pre_answer_playback_file >0 ){
                $ivrFileDetails = getIVRFileDetails($pre_answer_playback_file, $mysqli);
                /*
                            $retArray["id"] = $id;
                            $retArray["customer"] = $customer;
                            $retArray["file_description"] = $file_description;
                            $retArray["file_name"] = $file_name;

                            $retArray["tts_text"] = $tts_text;
                            $retArray["s3_url"] = $s3_url;
                 */

                $tts_text = $ivrFileDetails["tts_text"];


            }





            $returnArr = array(
                "id" => $id,
                "domain" => $domain,
                "domain_name" => $domain_name,
                "did_provider" => $did_provider,
                "did_provider_name" => $did_provider_name,
                "did_number" => $did_number,
                "action_type" => $action_type,
                "action_def" => $action_def,

                "pre_answer_playback" => $pre_answer_playback,
                "pre_answer_playback_file" => $pre_answer_playback_file,
                "tts_text" => $tts_text,


                "sip_registration" => $sip_registrationArr
            );

        }

    }else{
        error_log("CTPBX: error in getListOfDIDnumbersForCustomer.");
    }



    return $returnArr;
}


function getDIDnumberDetailsByID($did_id,$mysqli){
    $returnArr = array();


    $sql = "select d.id, d.domain, dom.domain_name, d.did_provider, dp.name,d.did_number, d.action_type, d.action_def,d.sip_registration,d.pre_answer_playback,d.pre_answer_playback_file FROM did_numbers d JOIN domains dom ON d.domain = dom.id  JOIN did_providers dp ON d.did_provider = dp.id WHERE  d.id = ? ;";
    $stmt = $mysqli->prepare($sql);
    if($stmt){

        $stmt->bind_param('i', $did_id);
        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();
        $stmt->bind_result($id, $domain, $domain_name, $did_provider, $did_provider_name, $did_number, $action_type, $action_def,$sip_registration,$pre_answer_playback,$pre_answer_playback_file);

        while($stmt->fetch()){

            $sip_registrationArr = json_decode($sip_registration,true);

            $tts_text = "";

            if($pre_answer_playback_file >0 ){
                $ivrFileDetails = getIVRFileDetails($pre_answer_playback_file, $mysqli);
                /*
                            $retArray["id"] = $id;
                            $retArray["customer"] = $customer;
                            $retArray["file_description"] = $file_description;
                            $retArray["file_name"] = $file_name;

                            $retArray["tts_text"] = $tts_text;
                            $retArray["s3_url"] = $s3_url;
                 */

                $tts_text = $ivrFileDetails["tts_text"];


            }





            $returnArr = array(
                "id" => $id,
                "domain" => $domain,
                "domain_name" => $domain_name,
                "did_provider" => $did_provider,
                "did_provider_name" => $did_provider_name,
                "did_number" => $did_number,
                "action_type" => $action_type,
                "action_def" => $action_def,

                "pre_answer_playback" => $pre_answer_playback,
                "pre_answer_playback_file" => $pre_answer_playback_file,
                "tts_text" => $tts_text,

                "sip_registration" => $sip_registrationArr
            );

        }

    }else{
        error_log("CTPBX: error in getDIDnumberDetailsByID.");
    }



    return $returnArr;
}











function getQueueNameByID($queueID,$mysqli){
    $returnVal = 0;


    $sql = "select name from queues WHERE id = ?;";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $queueID);
    $stmt->execute();
    $stmt->bind_result($returnVal);
    $stmt->fetch();
    $stmt->close();


    return $returnVal;
}





function getQueueDetailsByIDARR($id,$mysqli){

    $retArray = array();

    $sql = "SELECT id, customer, domain, name, params FROM queues WHERE id = ?;";


    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->bind_result($id, $customer, $domain, $name, $params);
    $stmt->fetch();


    $retArray["id"] = $id;
    $retArray["customer"] = $customer;
    $retArray["domain"] = $domain;
    $retArray["name"] = $name;
    $retArray["params"] = json_decode($params,true);

    return $retArray;

}


function getConferenceExtensionsARR($customer_id,$conference_id,$mysqli){

    $retArray = array();

    $action_type = "CONFERENCE";

    $sql = "SELECT extension FROM extension_numbers WHERE customer = ? AND action_type = ? AND action_def = ?;";


    $stmt = $mysqli->prepare($sql);




    if($mysqli->error){
        error_log("VTPBX:  Error in getConferenceExtensionsARR, details: ". $mysqli->error);
        return false;
    }




    $stmt->bind_param('isi', $customer_id,$action_type,$conference_id);
    $stmt->execute();
    $stmt->bind_result($extension);


    while($stmt->fetch()){

        $retArray[] = $extension;

    }


    return $retArray;

}






function getConferenceDetailsByIDARR($id,$mysqli){

    $retArray = array();

    $sql = "SELECT id, customer, domain, name, params FROM conferences WHERE id = ?;";


    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->bind_result($id, $customer, $domain, $name, $params);
    $stmt->store_result();



    $stmt->fetch();


    $retArray["id"] = $id;
    $retArray["customer"] = $customer;
    $retArray["domain"] = $domain;
    $retArray["name"] = $name;
    $retArray["params"] = json_decode($params,true);
    $retArray["extensions"]= getConferenceExtensionsARR($customer,$id,$mysqli);
    return $retArray;

}



function getAllQueuesDetails($mysqli){

    $returnArray = array();

    $sql = "SELECT id, customer, domain, name, params FROM queues;";


    $stmt = $mysqli->prepare($sql);

   // $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->bind_result($id, $customer, $domain, $name, $params);
    $stmt->store_result();
    while($stmt->fetch() ){

        $retArray = array();
            $retArray["id"] = $id;
            $retArray["customer"] = $customer;
            $retArray["domain"] = $domain;
            $retArray["name"] = $name;
            $retArray["params"] = json_decode($params,true);

        $returnArray[] =     $retArray;

    }




    return $returnArray;

}


function getAllConferencesDetails($mysqli){

    $returnArray = array();

    $sql = "SELECT id, customer, domain, name, params FROM conferences;";


    $stmt = $mysqli->prepare($sql);

    // $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->bind_result($id, $customer, $domain, $name, $params);
    $stmt->store_result();

    while($stmt->fetch() ){

        $retArray = array();
        $retArray["id"] = $id;
        $retArray["customer"] = $customer;
        $retArray["domain"] = $domain;
        $retArray["name"] = $name;
        $retArray["params"] = json_decode($params,true);

        $retArray["extensions"]= getConferenceExtensionsARR($customer,$id,$mysqli);

        $returnArray[] =     $retArray;

    }




    return $returnArray;

}

function getQueuesDetailsForOneCustomer($customerID, $mysqli){

    $returnArray = array();

    $sql = "SELECT id, customer, domain, name, params FROM queues where customer = ?;";


    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('i', $customerID);
    $stmt->execute();
    $stmt->bind_result($id, $customer, $domain, $name, $params);
    while($stmt->fetch()){

        $retArray = array();
        $retArray["id"] = $id;
        $retArray["customer"] = $customer;
        $retArray["domain"] = $domain;
        $retArray["name"] = $name;
        $retArray["params"] = json_decode($params,true);

        $returnArray[] =     $retArray;

    }




    return $returnArray;

}

function getConferencesDetailsForOneCustomer($customerID, $mysqli){

    $returnArray = array();

    $sql = "SELECT id, customer, domain, name, params FROM conferences where customer = ?;";


    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('i', $customerID);
    $stmt->execute();
    $stmt->bind_result($id, $customer, $domain, $name, $params);
    $stmt->store_result();
    while($stmt->fetch()){

        $retArray = array();
        $retArray["id"] = $id;
        $retArray["customer"] = $customer;
        $retArray["domain"] = $domain;
        $retArray["name"] = $name;
        $retArray["params"] = json_decode($params,true);
        $retArray["extensions"]= getConferenceExtensionsARR($customer,$id,$mysqli);


        $returnArray[] =     $retArray;

    }




    return $returnArray;

}


function getQueueCallLogs($customer,$queue,$call_uuid,$mysqli){

    $retArray = array();

    $sql = "SELECT id, customer, domain, queue, call_uuid,caller_id_number, caller_id_name, disposition, agent, call_time, answer_time, end_time, wait_time, duration, date_updated FROM queue_call_logs WHERE customer = ? AND queue = ? AND call_uuid = ?;";


    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('iis', $customer,$queue,$call_uuid);
    $stmt->execute();
    $stmt->bind_result($id, $customer, $domain, $queue, $call_uuid, $caller_id_number, $caller_id_name, $disposition, $agent, $call_time, $answer_time, $end_time, $wait_time, $duration, $date_updated);
    $stmt->fetch();


    $retArray["id"] = $id;
    $retArray["customer"] = $customer;
    $retArray["domain"] = $domain;
    $retArray["queue"] = $queue;
    $retArray["call_uuid"] = $call_uuid;
    $retArray["caller_id_number"] = $caller_id_number;
    $retArray["caller_id_name"] = $caller_id_name;
    $retArray["disposition"] = $disposition;
    $retArray["agent"] = $agent;
    $retArray["call_time"] = $call_time;
    $retArray["answer_time"] = $answer_time;
    $retArray["end_time"] = $end_time;
    $retArray["wait_time"] = $wait_time;
    $retArray["duration"] = $duration;
    $retArray["date_updated"] = $date_updated;



    return $retArray;

}






function getQueuesListForSelectView($customerID,$mysqli,$selected = null){

    $sql = "select id, name FROM queues   WHERE customer = ?;";
    $stmt = $mysqli->prepare($sql);


    if($stmt){

        $stmt->bind_param('i', $customerID);
        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();
        $stmt->bind_result($id, $name);
        $s = '';
        while($stmt->fetch()){


            $oneRow = '<option value="' . $id . '" ';

            if( $selected == $id)
                $oneRow .= ' selected="selected" ';

            $oneRow .= '>' . $name . '</option>';




            $s .= $oneRow;

        }
        return $s;
    }else{
        error_log("VTPBX: error getting list of queues for select view.");
    }


}




function getConferencesListForSelectView($customerID,$mysqli,$selected = null){

    $sql = "select id, name FROM conferences   WHERE customer = ?;";
    $stmt = $mysqli->prepare($sql);


    if($stmt){

        $stmt->bind_param('i', $customerID);
        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();
        $stmt->bind_result($id, $name);
        $s = '';
        while($stmt->fetch()){



            $oneRow = '<option value="' . $id . '" ';
            if( $selected == $id)
                $oneRow .= ' selected="selected" ';

            $oneRow .= ' >' . $name . '</option>';

            $s .= $oneRow;

        }
        return $s;
    }else{
        error_log("VTPBX: error getting list of conferences for select view.");
    }


}


function updateDIDnumberAction($did_number_id,$customer_id,$action_type,$action_def,$mysqli){


    $sql = "UPDATE did_numbers set action_type = ? , action_def = ?  WHERE id = ? AND customer = ?;";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('ssii', $action_type,$action_def,$did_number_id,$customer_id);
    $stmt->execute();
    $stmt->close();

    if($mysqli->error){
        error_log("VTPBX:  Error in updateDIDnumberAction, details: ". $mysqli->error);
        return false;
    }
    return true;
}


function updateDIDnumberPreAnswerSettings($did_number_id,$customer_id,$pre_answer_playback,$pre_answer_playback_file,$mysqli){


    $sql = "UPDATE did_numbers set pre_answer_playback = ? , pre_answer_playback_file = ?  WHERE id = ? AND customer = ?;";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('iiii', $pre_answer_playback,$pre_answer_playback_file,$did_number_id,$customer_id);
    $stmt->execute();
    $stmt->close();

    if($mysqli->error){
        error_log("VTPBX:  Error in updateDIDnumberPreAnswerSettings, details: ". $mysqli->error);
        return false;
    }
    return true;
}

function updateExtensionNumberAction($extension_number_id,$customer_id,$action_type,$action_def,$mysqli){


    $sql = "UPDATE extension_numbers set action_type = ? , action_def = ?  WHERE id = ? AND customer = ?;";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('ssii', $action_type,$action_def,$extension_number_id,$customer_id);
    $stmt->execute();
    $stmt->close();

    if($mysqli->error){
        error_log("VTPBX:  Error in updateExtensionNumberAction, details: ". $mysqli->error);
        return false;
    }
    return true;
}

function getCustomerConferencesListView($customerID,$mysqli){

    $sql = "select id,name from conferences where customer  = ?;";
    $stmt = $mysqli->prepare($sql);


    if($stmt){

        $stmt->bind_param('i', $customerID);
        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();
        $stmt->bind_result($id, $name);

        $s = '';
        while($stmt->fetch()){



            $oneRow = '<tr>'.
                '<td>'.$id.'</td>'.
                '<td>' . $name .'</td>'.
                '<td class="text-center"><div class="btn-group btn-group-sm" role="group" aria-label="Table row actions">'.
                '<button type="button" class="btn btn-white" disabled>Edit <i class="material-icons"></i>'.
                '<button  class="btn btn-white" data-toggle="modal" data-target="#deleteConferenceModal" data-id="'.$id.'" data-name="'.$name.'"         ><i class="material-icons"></i></button>'.

                '</div></td>'.
                '</tr>';

            $s .= $oneRow;

        }
        return $s;
    }else{
        error_log("VTPBX: error getting list of conferences for customer.");
    }


}





function getConferenceNameByID($conferenceID,$mysqli){
    $returnVal = 0;


    $sql = "select name from conferences WHERE id = ?;";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $conferenceID);
    $stmt->execute();
    $stmt->bind_result($returnVal);
    $stmt->fetch();
    $stmt->close();


    return $returnVal;
}




function isExtensionFree($customerID,$new_extension,$mysqli){
    $returnVal = 0;


    $sql = "select count(*) from extension_numbers WHERE customer = ? AND extension = ?;";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('is', $customerID,$new_extension);
    $stmt->execute();
    $stmt->bind_result($returnVal);
    $stmt->fetch();
    $stmt->close();

    if($returnVal>0) return false;
    else return true;

}




function isUsernameFree($customerID,$new_extension,$mysqli){
    $returnVal = 0;


    $sql = "select count(*) from users WHERE customer = ? AND username = ?;";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('is', $customerID,$new_extension);
    $stmt->execute();
    $stmt->bind_result($returnVal);
    $stmt->fetch();
    $stmt->close();

    if($returnVal>0) return false;
    else return true;

}





function getDomainsListForSelectView($customerID,$mysqli){

    $sql = "select id, domain_name FROM domains   WHERE customer = ?;";
    $stmt = $mysqli->prepare($sql);


    if($stmt){

        $stmt->bind_param('i', $customerID);
        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();
        $stmt->bind_result($id, $name);
        $s = '';
        while($stmt->fetch()){

            $oneRow = '<option value="' . $id . '">' . $name . '</option>';

            $s .= $oneRow;

        }
        return $s;
    }else{
        error_log("VTPBX: error getting list of domains for select view.");
    }


}



function getDidProvidersListForSelectView($mysqli){

    $sql = "select id, name FROM did_providers;";
    $stmt = $mysqli->prepare($sql);


    if($stmt){

        //$stmt->bind_param('i', $customerID);
        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();
        $stmt->bind_result($id, $name);
        $s = '';
        while($stmt->fetch()){

            $oneRow = '<option value="' . $id . '">' . $name . '</option>';

            $s .= $oneRow;

        }
        return $s;
    }else{
        error_log("VTPBX: error in getDidProvidersListForSelectView.");
    }


}




function getDomainsArrayByCustomer($customerID,$mysqli){

    $returnArr = array();

    $sql = "select id, domain_name FROM domains   WHERE customer = ?;";
    $stmt = $mysqli->prepare($sql);


    if($stmt){

        $stmt->bind_param('i', $customerID);
        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();
        $stmt->bind_result($id, $name);
        $s = '';
        while($stmt->fetch()){

            $returnArr[] = $name;

        }
        return $returnArr;
    }else{
        error_log("VTPBX: error getting list of domains for array.");
    }


}



function addNewExtensionNumberAction($customer_id,$domain_id,$extension_number,$action_type,$action_def,$mysqli){


    $sql = "INSERT INTO extension_numbers(customer,domain,extension,action_type,action_def) VALUES (?,?,?,?,?) ;";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('iisss', $customer_id,$domain_id,$extension_number,$action_type,$action_def);
    $stmt->execute();
    $newExtensionID = $stmt->insert_id;
    $stmt->close();

    if($mysqli->error){
        error_log("VTPBX:  Error in addNewExtensionNumberAction, details: ". $mysqli->error);
        return false;
    }

    return $newExtensionID;
}




function deleteExtensionNumber($customer_id,$extension_id,$mysqli){


    $sql = "DELETE from extension_numbers WHERE customer = ? AND id = ?;";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('ii', $customer_id,$extension_id);
    $stmt->execute();
    $stmt->close();

    if($mysqli->error){
        error_log("VTPBX:  Error in deleteExtensionNumber, details: ". $mysqli->error);
        return false;
    }
    return true;
}

function deleteExtensionNumberByID($extension_id,$mysqli){


    $sql = "DELETE from extension_numbers WHERE id = ?;";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $extension_id);
    $stmt->execute();
    $stmt->close();

    if($mysqli->error){
        error_log("VTPBX:  Error in deleteExtensionNumber, details: ". $mysqli->error);
        return false;
    }
    return true;
}
function addNewUser($customer,$domainID,$domainName,$name,$username,$sip_password,$mysqli,$mysqli_proxy){
    // vtpbx database
    $newUserID = 0;


    $sql = "INSERT INTO users(customer,domain,name,username,sip_password) VALUES (?,?,?,?,?) ;";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('iisss', $customer,$domainID,$name,$username,$sip_password);
    $stmt->execute();
    $newUserID = $stmt->insert_id;
    $stmt->close();

    if($mysqli->error){
        error_log("CPBX:  Error in addNewUser (VTPBX DB), details: ". $mysqli->error);
        return false;
    }


// proxy database
    if($newUserID >0){


        $sql = "INSERT INTO subscriber(username,domain,password,rpid) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE password = ? ;";
        $stmt = $mysqli_proxy->prepare($sql);
        $stmt->bind_param('sssss', $username,$domainName,$sip_password,$customer, $sip_password);
        $stmt->execute();
        $newUserIDProxy = $stmt->insert_id;
        $stmt->close();

        if($mysqli_proxy->error){
            error_log("CTPBX:  Error in addNewUser (PROXY DB), details: ". $mysqli_proxy->error);
            return false;
        }

        return $newUserID;

    }else{

        return false;

    }



}



function updateSubscriberPasswordProxyDB($domainName,$username,$sip_password,$mysqli_proxy){



        $sql = "UPDATE subscriber set password = ? WHERE domain = ? AND username = ? ;";
        $stmt = $mysqli_proxy->prepare($sql);
        $stmt->bind_param('sss', $sip_password,$domainName,$username);
        $stmt->execute();

        $stmt->close();

        if($mysqli_proxy->error){
            error_log("CTPBX:  Error in updateSubscriberPasswordProxyDB (PROXY DB), details: ". $mysqli_proxy->error);
            return false;
        }

        return true;




}


function removeUser($user_id,$username,$domainName,$mysqli,$mysqli_proxy){

    // vtpbx database



    $sql = "DELETE from users WHERE id = ? ;";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();

    $stmt->close();

    if($mysqli->error){
        error_log("CPBX:  Error in removeUser (CTPBX DB), details: ". $mysqli->error);
        return false;
    }


    // proxy database


        $sql = "DELETE from subscriber where domain = ? AND username = ? ;";
        $stmt = $mysqli_proxy->prepare($sql);
        $stmt->bind_param('ss', $domainName,$username);
        $stmt->execute();
        $stmt->close();
        if($mysqli_proxy->error){
            error_log("CTPBX:  Error in removeUser (PROXY DB), details: ". $mysqli_proxy->error);
            return false;
        }

        return true;





}







function getDIDproviderNameByID($did_providerID,$mysqli){
    $response = 0;

    $sql = "select name from did_providers where id = ?;";


    if ($stmt = $mysqli->prepare($sql)){

        $stmt->bind_param('i', $did_providerID);
        $stmt->execute();
        $stmt->bind_result($response);
        $stmt->fetch();
        $stmt->close();


        if($mysqli->error){
            error_log("MySQL Query error. [$sql]. " . $mysqli->error );
            return false;
        }

        return $response;


    } else {
        return false;
    }




}



function addNewDIDnumber($did_number,$did_provider,$customer_id,$domain_id,$action_type,$action_def,$mysqli){


    $sql = "INSERT INTO did_numbers(did_number,did_provider,customer,domain,action_type,action_def) VALUES (?,?,?,?,?,?) ;";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('siiiss', $did_number,$did_provider,$customer_id,$domain_id,$action_type,$action_def);
    $stmt->execute();
    $newNumberID = $stmt->insert_id;
    $stmt->close();

    if($mysqli->error){
        error_log("VTPBX:  Error in addNewDIDnumber, details: ". $mysqli->error);
        return false;
    }

    return $newNumberID;
}



function updateDIDnumberSIPregistrationParameters($sip_username,$sip_password,$sip_registrar, $proxydb_registrant_id, $did_id, $mysqli){

    $sip_registrationARR = array(
        "sip_username" =>   $sip_username,
        "sip_password" =>   $sip_password,
        "sip_registrar" =>   $sip_registrar,
        "proxydb_registrant_id" =>   $proxydb_registrant_id
    );



    $sip_registration = json_encode($sip_registrationARR);

    $sql = "UPDATE did_numbers set sip_registration = ? WHERE id = ? ;";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('si', $sip_registration,$did_id);
    $stmt->execute();

    $stmt->close();

    if($mysqli->error){
        error_log("CTPBX:  Error in updateDIDnumberSIPregistrationParameters, details: ". $mysqli->error);
        return false;
    }

    return true;
}






function addNewQueue($customerID,$domainID,$name,$mysqli){


    $sql = "INSERT INTO queues(customer,domain,name) VALUES (?,?,?) ;";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('iis', $customerID,$domainID,$name);
    $stmt->execute();
    $newNumberID = $stmt->insert_id;
    $stmt->close();

    if($mysqli->error){
        error_log("VTPBX:  Error in addNewQueue, details: ". $mysqli->error);
    return false;
    }

return $newNumberID;
}





function updateQueueParameters($queue_id,$params,$mysqli){


    $sql = "update queues set params = ? WHERE id = ?;";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('si', $params,$queue_id);
    $stmt->execute();

    $stmt->close();

    if($mysqli->error){
        error_log("VTPBX:  Error in updateQueueParameters, details: ". $mysqli->error);
        return false;
    }

    return true;
}










function addNewConferenceRoom($customerID,$domainID,$name,$mysqli){


    $sql = "INSERT INTO conferences(customer,domain,name) VALUES (?,?,?) ;";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('iis', $customerID,$domainID,$name);
    $stmt->execute();
    $newNumberID = $stmt->insert_id;
    $stmt->close();

    if($mysqli->error){
        error_log("VTPBX:  Error in addNewConferenceRoom, details: ". $mysqli->error);
        return false;
    }

    return $newNumberID;
}







function getCallLogsForCustomerList($customerID,$mysqli){


   $s = '';


    $sql = "select id,domain, call_uuid, call_from, call_to, destination_type, destination, call_status, hangup_cause, qos_mos, qos_quality, call_time, answer_time,end_time,  duration from call_logs WHERE    customer  = ? AND call_time > date_sub(now(), INTERVAL 60 day);";
    $stmt = $mysqli->prepare($sql);


    if($stmt){

        $stmt->bind_param('i', $customerID);
        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();
        $stmt->bind_result($id,$domain, $call_uuid, $call_from, $call_to, $destination_type, $destination, $call_status, $hangup_cause, $qos_mos, $qos_quality, $call_time, $answer_time,$end_time,  $duration);

        $s = '';
        while($stmt->fetch()){


            $call_type = "phone_in_talk";

            if($domain == 0 ){
                if($duration > 0) $call_type = $call_type_incoming_success;
                else $call_type = $call_type_missed;
            }else{
                // calls originated from the domain (internal profile)




            }


            $call_type_incoming_success = 'phone_callback';
            $call_type_missed = 'phone_missed';
            $call_type_outgoing_success = 'phone_in_talk';

            $downloadRecording = '';
            $playbackRecording = '';

            if($duration>0) {



                $playbackRecording = '<audio controls="controls" src="playback.php?file='.$id.'" >Your browser does not support the HTML5 audio element</audio>';

                $playbackRecording = '<button type="button"  class="btn btn-white play_recording_file" onclick="callSomeFunction('.$id.');" ><i class="material-icons">play_circle_outline</i></button>';

                //$downloadRecording = '<button type="button" class="btn btn-white"><i class="material-icons">cloud_download</i></button>';        // Download recording
               // $playbackRecording = '<button type="button" class="btn btn-white"><i class="material-icons">play_circle_outline</i></button>';
            }

            switch($destination_type){
                case "EXTENSION":{
                    $destination = getExtensionNameByUsernameAndCustomer($destination,$customerID,$mysqli);

                }break;
                case "CONFERENCE":{
                    $destination = getConferenceNameByID($destination,$mysqli);

                }break;
                case "QUEUE":{
                    $destination = getQueueNameByID($destination,$mysqli);

                }break;
                case "QUEUE_PICKUP":{
                    $destination = getQueueNameByID($destination,$mysqli);

                }break;


            }







            $oneRow = '<tr>'.
                '<td><i class="material-icons">'.$call_type.'</i></td>'.
                '<td>'.$call_from.'</td>'.
                '<td>'.$call_to.'</td>'.
                //'<td>'.$destination_type.'</td>'.  // extension (what should be here???)
                '<td>'.$destination_type.'</td>'.  // forwarded to
                '<td>'.$destination.'</td>'.    // name
                '<td>'.$call_time.'</td>'.    // date/time
                '<td id="playbackRow'.$id.'">'.
                $playbackRecording.        // Download recording
                //$playbackRecording.        // play recording
                '</td>'.  // link to the recording
                '<td>'.$call_status.'</td>'.
                '<td>' . $duration .'</td>'.
                '<td class="text-center"><div class="btn-group btn-group-sm" role="group" aria-label="Table row actions">'.
                '<button type="button" class="btn btn-white">Details <i class="material-icons">assignment</i>'.

                '</div></td>'.
                '</tr>';

            $s .= $oneRow;

        }
        return $s;
    }else{
        error_log("VTPBX: error in getCallLogsForCustomerList.");
    }





    return null;




}



function addNewCustomerAccount($customerName,$outboundsipprovider,$outboundsipproviderprefix,$channelsLimit,$limit_extensions,$mysqli,$mysqli_proxy){


    $sql = "INSERT INTO customers(name,limit_extensions,limit_channels_internal,limit_channels_incoming,limit_channels_external,sip_provider,sip_provider_prefix) VALUES (?,?,?,?,?,?,?);";



    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('siiiiis', $customerName,$limit_extensions,$channelsLimit,$channelsLimit,$channelsLimit,$outboundsipprovider,$outboundsipproviderprefix);
    $stmt->execute();
    $newCustomerID = $stmt->insert_id;

    if($mysqli->error){
        error_log("VTPBX:  Error in addNewCustomerAccount, details: ". $mysqli->error);
        return false;
    }

    if($newCustomerID > 0 ){
        // Insert proxy -> domain
        $newCustomerDomainName = "d" .$newCustomerID . PBX_DOMAIN_NAME;   // d1000.vtpbx.net


        // add domain to vtpbx database
        $domainID = addNewDomainVTPBX($newCustomerID,$newCustomerDomainName,$mysqli);

        if($domainID>0){
            // add domain to proxy database
            $domainIDproxy = addNewDomainPROXY($newCustomerDomainName,$mysqli_proxy);

            if($domainIDproxy>0){
                // SUCCESS!
                return $newCustomerID;
                // SUCCESS!
            }else{
                error_log("Proxy domain [$newCustomerDomainName] cannot be created, customer account failed to create!");
                return 0;
            }

        }else{

            error_log("Domain [$newCustomerDomainName] cannot be created, customer account failed to create!");
            return 0;
        }


    }else{
        error_log("Customer [$customerName] cannot be created, customer account failed to create!");
        return 0;

    }



}






function updateCustomerAccountDetails($customerName,$outboundsipprovider,$outboundsipproviderprefix,$channelsLimit,$limit_extensions,$customer_id,$mysqli){


    $sql = "UPDATE customers set name = ?, limit_extensions = ?, limit_channels_internal=?,limit_channels_incoming =?,   limit_channels_external=?,  sip_provider=?,   sip_provider_prefix=? WHERE id = ?;";



    $stmt = $mysqli->prepare($sql);

    if($stmt){

        $stmt->bind_param('siiiiisi', $customerName,$limit_extensions,$channelsLimit,$channelsLimit,$channelsLimit,$outboundsipprovider,$outboundsipproviderprefix,$customer_id);
        $stmt->execute();

        if($mysqli->error){
            error_log("CTPBX:  Error in updateCustomerAccountDetails 1, details: ". $mysqli->error);
            return false;
        }


        return true;



    }else{
        error_log("CTPBX:  Error in updateCustomerAccountDetails 2, details: ". $mysqli->error);
        return false;
    }





}






function addNewDomainPROXY($domainName,$mysqli){


    $sql = "INSERT INTO domain(domain) VALUES (?) ;";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('s', $domainName);
    $stmt->execute();
    $newNumberID = $stmt->insert_id;
    $stmt->close();

    if($mysqli->error){
        error_log("VTPBX:  Error in addNewDomainPROXY, details: ". $mysqli->error);
        return false;
    }

    return $newNumberID;
}


function addNewDomainVTPBX($customerID,$domainName,$mysqli){


    $sql = "INSERT INTO domains(customer, domain_name) VALUES (?,?) ;";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('is', $customerID,$domainName);
    $stmt->execute();
    $newNumberID = $stmt->insert_id;
    $stmt->close();

    if($mysqli->error){
        error_log("VTPBX:  Error in addNewDomainVTPBX, details: ". $mysqli->error);
        return false;
    }

    return $newNumberID;
}



function getListOfDIDproviders($mysqli,$mysqli_proxy){

    $sql = "select id, name from did_providers;";

    $stmt = $mysqli->prepare($sql);


    if($stmt){


        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();
        //$log->LogDebug('SELECT statement executed, binding result.');
        $stmt->bind_result($id,$name);

        $s = '';
        while($stmt->fetch()){


            $ipAddresses = getListOfDIDproviderIPaddressesFromPROXYdb($id,$mysqli_proxy);



            $oneRow = '<tr>'.
                '<td>'.$id.'</td>'.
                '<td>'.$name.'</td>'.
                '<td>'.$ipAddresses.'</td>'.

                '<td><div class="btn-group btn-group-sm" role="group" aria-label="Table row actions">'.
                '<a href="dashboard.php?p=settings_did_provider_details&id='.$id.'"><button type="button" class="btn btn-white">'.
                'Edit <i class="material-icons"></i>'.
                '</button></a>'.
                '</div></td>'.
                '</tr>';

            $s .= $oneRow;


        }
        return $s;
    }else{
        error_log("error getting list of getListOfSIPproxyAddresses.");
    }
}


function getListOfDIDproviderIPaddressesFromPROXYdb($did_providerID,$mysqli){

    $sql = "select ip from address WHERE grp = ?;";

    $stmt = $mysqli->prepare($sql);


    if($stmt){
        $stmt->bind_param('i', $did_providerID);

        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();
        //$log->LogDebug('SELECT statement executed, binding result.');
        $stmt->bind_result($ip_address);

        $s = '';

        while($stmt->fetch()){

            $s .= $ip_address  . " ,";

        }

        $s = substr($s,0,-1);

        return $s;
    }else{
        error_log("error getting list of getListOfDIDproviderIPaddressesFromPROXYdb.");
    }
}



function getListOfDIDsInventory($mysqli){

    $sql = "select id, did_number,did_provider,customer,domain,action_type,action_def FROM did_numbers;";

    $stmt = $mysqli->prepare($sql);


    if($stmt){


        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();

        $stmt->bind_result($id, $did_number,$did_provider,$customer,$domain,$action_type,$action_def);

        $s = '';
        while($stmt->fetch()){


            $domainName = getDomainNameByID($domain,$mysqli);
            $customerDetails = getCustomerDetailsByID($customer,$mysqli);
            $customerName = $customerDetails["name"];

            $action_def_NAME = $action_def;

            switch($action_type){
                case "QUEUE":{

                    $action_def_NAME = getQueueNameByID($action_def,$mysqli);


                }break;

                case "GROUP":{


                }break;

                case "CONFERENCE":{
                    $action_def_NAME = getConferenceNameByID($action_def,$mysqli);

                }break;

            }


            $providerName = "-";

            if($did_provider>0){
                $providerName = getDIDproviderNameByID($did_provider,$mysqli);
            }




            $oneRow = '<tr>'.
                '<td>'.$did_number.'</td>'.
                '<td>'.$providerName.'</td>'.
                '<td>'.$customerName.'</td>'.
                '<td>'.$domainName.'</td>'.
                '<td>'.$action_type.' -> '. $action_def_NAME  .'</td>'.
                '<td><div class="btn-group btn-group-sm" role="group" aria-label="Table row actions">'.
                '<button type="button" class="btn btn-white">Edit <i class="material-icons"></i></button>'.
                ''.
                ''.
                '</div></td>'.
                '</tr>';

            $s .= $oneRow;


        }
        return $s;
    }else{
        error_log("error getting list of getListOfDIDsInventory.");
    }
}


function getExtensionNameByID($userID,$mysqli){

    $sql = "select name from users where id = ?;";

    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('i', $userID);
    $stmt->execute();
    $stmt->bind_result($returnVal);
    $stmt->fetch();

    return $returnVal;

}


function getExtensionUsernameByID($userID,$mysqli){

    $sql = "select username from users where id = ?;";

    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('i', $userID);
    $stmt->execute();
    $stmt->bind_result($returnVal);
    $stmt->fetch();

    return $returnVal;

}

function getExtensionFriendlyExtAndNameByID($userID,$mysqli){

    $sql = "select username,name from users where id = ?;";

    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('i', $userID);
    $stmt->execute();
    $stmt->bind_result($username,$name);
    $stmt->fetch();


    return $username . ' - ' .$name;

}


function getExtensionNameByUsernameAndCustomer($username,$customer,$mysqli){

    $sql = "select name from users where customer  = ? AND username = ?;";

    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('is', $customer,$username);
    $stmt->execute();
    $stmt->bind_result($returnVal);
    $stmt->fetch();

    return $returnVal;

}




//  =============== Queue Supervisor RealTime Dashboard



function getQueueAgentsAndCallsView($queueID,$customerID,$mysqli){

    $sql = "SELECT  domain,queue,consumer_count,caller_count,waiting_count,outbound_strategy,outbound_priority,ring_timeout,outbound,callers,consumers, bridges  FROM queues_rt_data WHERE queue = ? AND domain IN (select id  from domains where customer = ?) and date_updated > date_sub(now(), INTERVAL 60 second) LIMIT 1;";

    $stmt = $mysqli->prepare($sql);


    if($stmt){
        $stmt->bind_param("ii",$queueID,$customerID);
        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();

        $stmt->bind_result($domain,$queue,$consumer_count,$caller_count,$waiting_count,$outbound_strategy,$outbound_priority,$ring_timeout,$outbound,$callers,$consumers, $bridges);

        $s = '';
        if($stmt->fetch()){  // 1 row only!!!


            // 1 - Calls waiting to answer (CALLERS)
            /*
             *
             *
             *

             <tr style=" line-height: 0px;  min-height: 8px;   height: 8px; background-color: lightskyblue; color: #1a1d21; font-size: 11px;">
              <td>New</td>
              <td>107-0339</td>
              <td>0:21:04</td>
              <td></td>

             </tr>

             *
             *
             *
             */

            $callersArr =  json_decode($callers,true);

            foreach($callersArr as $caller){
                // bridged calls are active
                $call_status = "New";
                // bridged calls have call duration
                $timestamp = $caller["timestamp"];



                //$call_start_epoch_DT  = new DateTime(@$timestamp);
                $call_start_epoch_DT = date_create_from_format('Y-m-d H:i:s',$timestamp);
                $time_now = new DateTime();
                $time_diff = $time_now->diff($call_start_epoch_DT);
                $diffMinutes = $time_diff->format("%i");
                $diffSeconds = sprintf("%02d", $time_diff->format("%s"));

                $call_duration = "$diffMinutes:$diffSeconds";



                // phone number

                $caller_id_number = $caller["caller_id_number"];
                $caller_id_name = $caller["caller_id_name"];

                $phoneNumber = $caller_id_number;

                if($caller_id_number != $caller_id_name)
                    $phoneNumber = $phoneNumber . ' (' .$caller_id_name . ')';




                $oneRow = '<tr style=" line-height: 0px;  min-height: 8px;   height: 8px; background-color: lightskyblue; color: #1a1d21; font-size: 11px;">'.
                    '<td>'.$call_status.'</td>'.
                    '<td>'.$phoneNumber.'</td>'.
                    '<td>'.$call_duration.'</td>'.
                    '<td></td>'.

                    '</tr>';

                $s .= $oneRow;


            }







            // 1 - BRIDGES
            /*
             *
             *
             *

             <tr style=" line-height: 0px;  min-height: 8px;   height: 8px; background-color: lightgreen; color: #1a1d21; font-size: 11px;">
              <td>On Call</td>
              <td>107-0339</td>
              <td>0:21:04</td>
              <td>Kerry (104)</td>

             </tr>

             *
             *
             *
             */

            $bridgesArr = json_decode($bridges,true);

            foreach($bridgesArr as $bridge){
                // bridged calls are active
                $call_status = "On call";
                // bridged calls have call duration
                    $call_start_epoch = $bridge["bridge_start_epoch"];
                    $call_start_epoch_DT  = new DateTime("@$call_start_epoch");
                    $time_now = new DateTime();
                    $time_diff = $time_now->diff($call_start_epoch_DT);
                    $diffMinutes = $time_diff->format("%i");
                    $diffSeconds = sprintf("%02d", $time_diff->format("%s"));

                $call_duration = "$diffMinutes:$diffSeconds";


                //error_log("CALL DURATION [$call_duration]   |  min [$diffMinutes][$diffSeconds]  ");


                // agent friendly name
                    $caller_id_number = $bridge["caller_id_number"];

                    $agentName = getExtensionNameByUsernameAndCustomer($caller_id_number,$customerID,$mysqli);
                $agentFriendlyName = $caller_id_number . ' - ' . $agentName;


                // phone number
                $call_uuid = $bridge["caller_uuid"];
                $callLogDetails = getQueueCallLogs($customerID,$queueID,$call_uuid,$mysqli);

                $caller_id_number = $callLogDetails["caller_id_number"];
                $caller_id_name = $callLogDetails["caller_id_name"];

                $phoneNumber = $caller_id_number;

                if($caller_id_number != $caller_id_name)
                    $phoneNumber = $phoneNumber . ' (' .$caller_id_name . ')';




                $oneRow = '<tr style=" line-height: 0px;  min-height: 8px;   height: 8px; background-color: lightgreen; color: #1a1d21; font-size: 11px;">'.
                    '<td>'.$call_status.'</td>'.
                    '<td>'.$phoneNumber.'</td>'.
                    '<td>'.$call_duration.'</td>'.
                    '<td>'.$agentFriendlyName.'</td>'.

                    '</tr>';

                $s .= $oneRow;


            }

            // AGENTS




            // AGENTS WAITING   /  WRAP-UP    - ON HOOK

            // $outbound


            $outboundArr = json_decode($outbound,true);

            foreach($outboundArr as $out){
                // agents are waiting
                $call_status = "?";
                $idleColor = 'background-color: lightgreen;';


                $secondsIdle = 0;
                $phoneNumber  = "";


                $start_time =  $out["start-time"];
                $stop_time =  $out["stop-time"];
                $next_available =  $out["next-available"];
                $logged_on_since =  $out["logged-on-since"];
                $time_now = strtotime("now");


                // start-time = never   (Never ever had a call after joining the queue...
                if($start_time == "never"){
                    $call_status = "WAITING - on hook";
                    $idleColor = 'background-color: lightsalmon;';
                    // how long he is waiting?
                    // get it from the queue login time (queue_agent_sessions table)

                    $logged_on_sinceT = strtotime($logged_on_since);
                    $secondsIdle = $time_now - $logged_on_sinceT;





                }else{
                    // start-time is not never, it means it had some call, let's see if it's still active...
                    if($stop_time == "never"){
                        // start-time is not never AND stop-time is NEVER, it means the agent is TALKING right now - skip it... It will be considered in different options.
                        continue;

                    }else{
                        // start time is not never, stop time is not never ->
                        //
                        //  "start-time":"2019-04-29 11:27:31",     "stop-time":"2019-04-29 11:27:35",      "next-available":"2019-04-29 11:27:41"
                        //
                        //  next-available can be  "now" or some fixed date/time
                        //

                        // get higher date from stop-time and next-available, get difference from now

                        $stop_timeT = strtotime($stop_time);


                        if($next_available == "now"){
                            $secondsIdle = $time_now - $stop_timeT;
                            $call_status = "WAITING - on hook";
                            $idleColor = 'background-color: lightsalmon;';



                        }else{
                            $next_availableT = strtotime($next_available);

                            if($next_availableT > $time_now){
                                $call_status = "WRAP-UP";
                                $idleColor = 'background-color: dodgerblue;';
                                $secondsIdle = $next_availableT - $time_now;

                            }else{
                                $call_status = "WAITING - on hook";
                                $idleColor = 'background-color: lightsalmon;';
                                $secondsIdle =  $time_now - $next_availableT ;

                            }



                        }

                    }

                }


                $idleMinutes = sprintf("%02d", intval($secondsIdle/60)   );
                $idleSeconds =    sprintf("%02d",  intval(($secondsIdle - $idleMinutes*60))   );

                $timeIdle = $idleMinutes .":". $idleSeconds;



                //
                //               WRAP-UP:  dodgerblue
                //               WAITING  lightsalmon   , lightcoral   ,   lightcoral
                //



                if($secondsIdle > 120)
                    $idleColor = 'background-color: lightcoral;';

                if($secondsIdle > 300)
                    $idleColor = 'background-color: orangered;';


                // agent friendly name
                $caller_id_number = $out["user"];

                $agentName = getExtensionNameByUsernameAndCustomer($caller_id_number,$customerID,$mysqli);
                $agentFriendlyName = $caller_id_number . ' - ' . $agentName;






                $oneRow = '<tr style=" line-height: 0px;  min-height: 8px;   height: 8px; '.$idleColor.' color: #1a1d21; font-size: 11px;">'.
                    '<td>'.$call_status.'</td>'.
                    '<td>'.$phoneNumber.'</td>'.
                    '<td>'.$timeIdle.'</td>'.
                    '<td>'.$agentFriendlyName.'</td>'.

                    '</tr>';

                $s .= $oneRow;


            }





            // consumers


            $consumersArr = json_decode($consumers,true);

            foreach($consumersArr as $consumer){

                // agents are waiting
                $statusFIFO =  $consumer["status"];

                if($statusFIFO == "WAITING" || $statusFIFO == "WRAPUP"){

                    $call_status = $statusFIFO . " - off hook";
                    $idleColor = 'background-color: lightsalmon;';

                    $phoneNumber  = "";


                    $timestamp =  strtotime($consumer["timestamp"]);
                    $time_now  = strtotime("now");
                    $secondsIdle = $time_now - $timestamp;


                    $idleMinutes = sprintf("%02d", intval($secondsIdle/60)   );
                    $idleSeconds =    sprintf("%02d",  intval(($secondsIdle - $idleMinutes*60))   );

                    $timeIdle = $idleMinutes .":". $idleSeconds;




                    if($secondsIdle > 120)
                        $idleColor = 'background-color: lightcoral;';

                    if($secondsIdle > 300)
                        $idleColor = 'background-color: orangered;';


                    // agent friendly name
                    $caller_id_number = $consumer["caller_id_number"];

                    $agentName = getExtensionNameByUsernameAndCustomer($caller_id_number,$customerID,$mysqli);
                    $agentFriendlyName = $caller_id_number . ' - ' . $agentName;






                    $oneRow = '<tr style=" line-height: 0px;  min-height: 8px;   height: 8px; '.$idleColor.' color: #1a1d21; font-size: 11px;">'.
                        '<td>'.$call_status.'</td>'.
                        '<td>'.$phoneNumber.'</td>'.
                        '<td>'.$timeIdle.'</td>'.
                        '<td>'.$agentFriendlyName.'</td>'.

                        '</tr>';

                    $s .= $oneRow;

                }
            }

















        }
        return $s;
    }else{
        error_log("error getting list of getListOfDIDsInventory.");
    }
}



function getQueueAgentsAndCallsARR($queueID,$customerID,$mysqli,$mysqli_proxy){




    $returnArray = array();


    $sql = "SELECT  domain,queue,consumer_count,caller_count,waiting_count,outbound_strategy,outbound_priority,ring_timeout,outbound,callers,consumers, bridges  FROM queues_rt_data WHERE queue = ? AND domain IN (select id  from domains where customer = ?) and date_updated > date_sub(now(), INTERVAL 60 second) LIMIT 1;";

    $stmt = $mysqli->prepare($sql);


    if($stmt){
        $stmt->bind_param("ii",$queueID,$customerID);
        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();

        $stmt->bind_result($domain,$queue,$consumer_count,$caller_count,$waiting_count,$outbound_strategy,$outbound_priority,$ring_timeout,$outbound,$callers,$consumers, $bridges);

        $s = '';
        if($stmt->fetch()){  // 1 row only!!!


            // 1 - Calls waiting to answer (CALLERS)
            /*
             *
             *
             *

             <tr style=" line-height: 0px;  min-height: 8px;   height: 8px; background-color: lightskyblue; color: #1a1d21; font-size: 11px;">
              <td>New</td>
              <td>107-0339</td>
              <td>0:21:04</td>
              <td></td>

             </tr>

             *
             *
             *
             */

            $callersArr =  json_decode($callers,true);


            $calls_waiting = array();


            foreach($callersArr as $caller){
                // bridged calls are active
                $call_status = "New";
                // bridged calls have call duration
                $timestamp = $caller["timestamp"];



                //$call_start_epoch_DT  = new DateTime(@$timestamp);
                $call_start_epoch_DT = date_create_from_format('Y-m-d H:i:s',$timestamp);
                $time_now = new DateTime();
                $time_diff = $time_now->diff($call_start_epoch_DT);
                $diffMinutes = $time_diff->format("%i");
                $diffSeconds = sprintf("%02d", $time_diff->format("%s"));

                $call_duration = "$diffMinutes:$diffSeconds";



                // phone number

                $caller_id_number = $caller["caller_id_number"];
                $caller_id_name = $caller["caller_id_name"];

                $phoneNumber = $caller_id_number;

                if($caller_id_number != $caller_id_name)
                    $phoneNumber = $phoneNumber . ' (' .$caller_id_name . ')';



                $calls_waiting[] = array(
                    "call_status" => $call_status,
                    "phone_number" => $phoneNumber,
                    "call_duration" =>$call_duration
                );







            }

            $returnArray["calls_waiting"] = $calls_waiting;





                // 1 - BRIDGES
            /*
             *
             *
             *

             <tr style=" line-height: 0px;  min-height: 8px;   height: 8px; background-color: lightgreen; color: #1a1d21; font-size: 11px;">
              <td>On Call</td>
              <td>107-0339</td>
              <td>0:21:04</td>
              <td>Kerry (104)</td>

             </tr>

             *
             *
             *
             */

            $bridgesArr = json_decode($bridges,true);

            $calls_in_progress = array();

            foreach($bridgesArr as $bridge){
                // bridged calls are active
                $call_status = "On call";
                // bridged calls have call duration
                $call_start_epoch = $bridge["bridge_start_epoch"];
                $call_start_epoch_DT  = new DateTime("@$call_start_epoch");
                $time_now = new DateTime();
                $time_diff = $time_now->diff($call_start_epoch_DT);
                $diffMinutes = $time_diff->format("%i");
                $diffSeconds = sprintf("%02d", $time_diff->format("%s"));

                $call_duration = "$diffMinutes:$diffSeconds";


                //error_log("CALL DURATION [$call_duration]   |  min [$diffMinutes][$diffSeconds]  ");


                // agent friendly name
                $caller_id_number = $bridge["caller_id_number"];

                $agentName = getExtensionNameByUsernameAndCustomer($caller_id_number,$customerID,$mysqli);
                $agentFriendlyName = $caller_id_number . ' - ' . $agentName;
                $agent_username = $caller_id_number;

                // phone number
                $call_uuid = $bridge["caller_uuid"];
                $callLogDetails = getQueueCallLogs($customerID,$queueID,$call_uuid,$mysqli);

                $caller_id_number = $callLogDetails["caller_id_number"];
                $caller_id_name = $callLogDetails["caller_id_name"];

                $phoneNumber = $caller_id_number;

                if($caller_id_number != $caller_id_name)
                    $phoneNumber = $phoneNumber . ' (' .$caller_id_name . ')';




                $oneRow = '<tr style=" line-height: 0px;  min-height: 8px;   height: 8px; background-color: lightgreen; color: #1a1d21; font-size: 11px;">'.
                    '<td>'.$call_status.'</td>'.
                    '<td>'.$phoneNumber.'</td>'.
                    '<td>'.$call_duration.'</td>'.
                    '<td>'.$agentFriendlyName.'</td>'.

                    '</tr>';

                $s .= $oneRow;



                $calls_in_progress[]  = array(
                    "call_status" => $call_status,
                    "phone_number" => $phoneNumber,
                    "call_duration" => $call_duration,
                    "agent_username" => $agent_username,
                    "agent_name" => $agentName,
                    "agent_name_full" => $agentFriendlyName,


                );


            }




            $returnArray["calls_in_progress"] = $calls_in_progress;
            // AGENTS




            // AGENTS WAITING   /  WRAP-UP    - ON HOOK

            // $outbound

            $agents_waiting = array();
            $outboundArr = json_decode($outbound,true);

            foreach($outboundArr as $out){
                // agents are waiting
                $call_status = "?";
                $idleColor = 'background-color: lightgreen;';


                $secondsIdle = 0;
                $phoneNumber  = "";


                $start_time =  $out["start-time"];
                $stop_time =  $out["stop-time"];
                $next_available =  $out["next-available"];
                $logged_on_since =  $out["logged-on-since"];
                $time_now = strtotime("now");


                // start-time = never   (Never ever had a call after joining the queue...
                if($start_time == "never"){
                    $call_status = "WAITING - on hook";
                    $idleColor = 'background-color: lightsalmon;';
                    // how long he is waiting?
                    // get it from the queue login time (queue_agent_sessions table)

                    $logged_on_sinceT = strtotime($logged_on_since);
                    $secondsIdle = $time_now - $logged_on_sinceT;





                }else{
                    // start-time is not never, it means it had some call, let's see if it's still active...
                    if($stop_time == "never"){
                        // start-time is not never AND stop-time is NEVER, it means the agent is TALKING right now - skip it... It will be considered in different options.
                        continue;

                    }else{
                        // start time is not never, stop time is not never ->
                        //
                        //  "start-time":"2019-04-29 11:27:31",     "stop-time":"2019-04-29 11:27:35",      "next-available":"2019-04-29 11:27:41"
                        //
                        //  next-available can be  "now" or some fixed date/time
                        //

                        // get higher date from stop-time and next-available, get difference from now

                        $stop_timeT = strtotime($stop_time);


                        if($next_available == "now"){
                            $secondsIdle = $time_now - $stop_timeT;
                            $call_status = "WAITING - on hook";
                            $idleColor = 'background-color: lightsalmon;';



                        }else{
                            $next_availableT = strtotime($next_available);

                            if($next_availableT > $time_now){
                                $call_status = "WRAP-UP";
                                $idleColor = 'background-color: dodgerblue;';
                                $secondsIdle = $next_availableT - $time_now;

                            }else{
                                $call_status = "WAITING - on hook";
                                $idleColor = 'background-color: lightsalmon;';
                                $secondsIdle =  $time_now - $next_availableT ;

                            }



                        }

                    }

                }


                $idleMinutes = sprintf("%02d", intval($secondsIdle/60)   );
                $idleSeconds =    sprintf("%02d",  intval(($secondsIdle - $idleMinutes*60))   );

                $timeIdle = $idleMinutes .":". $idleSeconds;



                //
                //               WRAP-UP:  dodgerblue
                //               WAITING  lightsalmon   , lightcoral   ,   lightcoral
                //



                if($secondsIdle > 120)
                    $idleColor = 'background-color: lightcoral;';

                if($secondsIdle > 300)
                    $idleColor = 'background-color: orangered;';


                // agent friendly name
                $caller_id_number = $out["user"];

                $agentName = getExtensionNameByUsernameAndCustomer($caller_id_number,$customerID,$mysqli);
                $agentFriendlyName = $caller_id_number . ' - ' . $agentName;






                $oneRow = '<tr style=" line-height: 0px;  min-height: 8px;   height: 8px; '.$idleColor.' color: #1a1d21; font-size: 11px;">'.
                    '<td>'.$call_status.'</td>'.
                    '<td>'.$phoneNumber.'</td>'.
                    '<td>'.$timeIdle.'</td>'.
                    '<td>'.$agentFriendlyName.'</td>'.

                    '</tr>';

                $s .= $oneRow;

                $domain_name = getDomainNameByID($domain,$mysqli);
                $registration_status = getSIPuserRegistrationStatus($caller_id_number,$domain_name,$mysqli_proxy);




                $agents_waiting[] = array(
                    "call_status" => $call_status,
                    "phone_number" => $phoneNumber,
                    "time_idle" => $timeIdle,
                    "agent_username" => $caller_id_number,
                    "agent_name" => $agentName,
                    "agent_name_friendly" => $agentFriendlyName,
                    "sip_user_status" => $registration_status

                );

            }


            $returnArray["agents_waiting_on_hook"] = $agents_waiting;



            // consumers

            $consumersResponse= array();
            $consumersArr = json_decode($consumers,true);

            foreach($consumersArr as $consumer){

                // agents are waiting
                $statusFIFO =  $consumer["status"];

                if($statusFIFO == "WAITING" || $statusFIFO == "WRAPUP"){

                    $call_status = $statusFIFO . " - off hook";
                    $idleColor = 'background-color: lightsalmon;';

                    $phoneNumber  = "";


                    $timestamp =  strtotime($consumer["timestamp"]);
                    $time_now  = strtotime("now");
                    $secondsIdle = $time_now - $timestamp;


                    $idleMinutes = sprintf("%02d", intval($secondsIdle/60)   );
                    $idleSeconds =    sprintf("%02d",  intval(($secondsIdle - $idleMinutes*60))   );

                    $timeIdle = $idleMinutes .":". $idleSeconds;




                    if($secondsIdle > 120)
                        $idleColor = 'background-color: lightcoral;';

                    if($secondsIdle > 300)
                        $idleColor = 'background-color: orangered;';


                    // agent friendly name
                    $caller_id_number = $consumer["caller_id_number"];

                    $agentName = getExtensionNameByUsernameAndCustomer($caller_id_number,$customerID,$mysqli);
                    $agentFriendlyName = $caller_id_number . ' - ' . $agentName;






                    $oneRow = '<tr style=" line-height: 0px;  min-height: 8px;   height: 8px; '.$idleColor.' color: #1a1d21; font-size: 11px;">'.
                        '<td>'.$call_status.'</td>'.
                        '<td>'.$phoneNumber.'</td>'.
                        '<td>'.$timeIdle.'</td>'.
                        '<td>'.$agentFriendlyName.'</td>'.

                        '</tr>';

                    $s .= $oneRow;


                    $domain_name = getDomainNameByID($domain,$mysqli);
                    $registration_status = getSIPuserRegistrationStatus($caller_id_number,$domain_name,$mysqli_proxy);




                    $consumersResponse[] = array(
                        "call_status" => $call_status,
                        "phone_number" => $phoneNumber,
                        "time_idle" => $timeIdle,
                        "agent_name" => $agentName,
                        "agent_username" => $caller_id_number,
                        "agent_name_friendly" => $agentFriendlyName,
                        "sip_user_status" => $registration_status


                    );


                }
            }



            $returnArray["agents_waiting_off_hook"] = $consumersResponse;













        }
        return $returnArray;
    }else{
        error_log("error getting list of getListOfDIDsInventory.");
    }
}



function getQueueCallsTotal($customer,$queue, $dateRangeCode, $timeOffset,$mysqli){
    $response = 0;

    $columnName = "call_time";
    $dateRangeSQL = convertDateRangeCodeToSQLdateRange($dateRangeCode,$columnName,$timeOffset);

    //    select count(*) from queue_call_logs WHERE customer =  1 and queue =? and  date(call_time) = date(date_add(now(), INTERVAL 0 hour) ;
    // select count(*) from queue_call_logs WHERE customer =  1 AND domain = 1 and queue =1 and date(call_time) = date(date_sub(now(), INTERVAL 5 hour) );
    $sql = "select count(*) from queue_call_logs WHERE customer =  ? and queue =? and $dateRangeSQL; ";
    //error_log($sql);

    if ($stmt = $mysqli->prepare($sql)){

        $stmt->bind_param('ii', $customer,$queue);
        $stmt->execute();
        $stmt->bind_result($response);
        $stmt->fetch();
        $stmt->close();


        if($mysqli->error){
            error_log("MySQL Query error. [$sql]. " . $mysqli->error );
            return false;
        }

        return $response;


    } else {
        if($mysqli->error){
            error_log("MySQL Query error. [$sql]. " . $mysqli->error );
            return false;
        }

        return false;
    }




}

function getQueueCallsAnsweredTotal($customer,$queue, $dateRangeCode, $timeOffset,$mysqli){
    $response = 0;

    $columnName = "call_time";
    $dateRangeSQL = convertDateRangeCodeToSQLdateRange($dateRangeCode,$columnName,$timeOffset);

    //    select count(*) from queue_call_logs WHERE customer =  1 and queue =? and  date(call_time) = date(date_add(now(), INTERVAL 0 hour) ;
    // select count(*) from queue_call_logs WHERE customer =  1 AND domain = 1 and queue =1 and date(call_time) = date(date_sub(now(), INTERVAL 5 hour) );
    $sql = "select count(*) from queue_call_logs WHERE answer_time>0 AND customer =  ? and queue =? and $dateRangeSQL; ";
    //error_log($sql);

    if ($stmt = $mysqli->prepare($sql)){

        $stmt->bind_param('ii', $customer,$queue);
        $stmt->execute();
        $stmt->bind_result($response);
        $stmt->fetch();
        $stmt->close();


        if($mysqli->error){
            error_log("MySQL Query error. [$sql]. " . $mysqli->error );
            return false;
        }

        return $response;


    } else {
        if($mysqli->error){
            error_log("MySQL Query error. [$sql]. " . $mysqli->error );
            return false;
        }

        return false;
    }




}


function getQueueCallsAnsweredTotal20Sec($customer,$queue, $dateRangeCode, $timeOffset,$mysqli){
    $response = 0;

    $columnName = "call_time";
    $dateRangeSQL = convertDateRangeCodeToSQLdateRange($dateRangeCode,$columnName,$timeOffset);

    //    select count(*) from queue_call_logs WHERE customer =  1 and queue =? and  date(call_time) = date(date_add(now(), INTERVAL 0 hour) ;
    // select count(*) from queue_call_logs WHERE customer =  1 AND domain = 1 and queue =1 and date(call_time) = date(date_sub(now(), INTERVAL 5 hour) );
    $sql = "select count(*) from queue_call_logs WHERE answer_time>0 AND wait_time <= 20 AND customer =  ? and queue =? and $dateRangeSQL; ";
    //error_log($sql);

    if ($stmt = $mysqli->prepare($sql)){

        $stmt->bind_param('ii', $customer,$queue);
        $stmt->execute();
        $stmt->bind_result($response);
        $stmt->fetch();
        $stmt->close();


        if($mysqli->error){
            error_log("MySQL Query error. [$sql]. " . $mysqli->error );
            return false;
        }

        return $response;


    } else {
        if($mysqli->error){
            error_log("MySQL Query error. [$sql]. " . $mysqli->error );
            return false;
        }

        return false;
    }

}


function getQueueCallsAnsweredTotal30Sec($customer,$queue, $dateRangeCode, $timeOffset,$mysqli){
    $response = 0;

    $columnName = "call_time";
    $dateRangeSQL = convertDateRangeCodeToSQLdateRange($dateRangeCode,$columnName,$timeOffset);

    //    select count(*) from queue_call_logs WHERE customer =  1 and queue =? and  date(call_time) = date(date_add(now(), INTERVAL 0 hour) ;
    // select count(*) from queue_call_logs WHERE customer =  1 AND domain = 1 and queue =1 and date(call_time) = date(date_sub(now(), INTERVAL 5 hour) );
    $sql = "select count(*) from queue_call_logs WHERE answer_time>0 AND wait_time <= 30 AND customer =  ? and queue =? and $dateRangeSQL; ";
    //error_log($sql);

    if ($stmt = $mysqli->prepare($sql)){

        $stmt->bind_param('ii', $customer,$queue);
        $stmt->execute();
        $stmt->bind_result($response);
        $stmt->fetch();
        $stmt->close();


        if($mysqli->error){
            error_log("MySQL Query error. [$sql]. " . $mysqli->error );
            return false;
        }

        return $response;


    } else {
        if($mysqli->error){
            error_log("MySQL Query error. [$sql]. " . $mysqli->error );
            return false;
        }

        return false;
    }

}



function getQueueCallsDropped($customer,$queue, $dateRangeCode, $timeOffset,$mysqli){
    $response = 0;

    $columnName = "call_time";
    $dateRangeSQL = convertDateRangeCodeToSQLdateRange($dateRangeCode,$columnName,$timeOffset);

    //    select count(*) from queue_call_logs WHERE customer =  1 and queue =? and  date(call_time) = date(date_add(now(), INTERVAL 0 hour) ;
    // select count(*) from queue_call_logs WHERE customer =  1 AND domain = 1 and queue =1 and date(call_time) = date(date_sub(now(), INTERVAL 5 hour) );
    $sql = "select count(*) from queue_call_logs WHERE customer =  ? and queue =? AND disposition = 2 AND agent = 0    and $dateRangeSQL; ";
    //error_log($sql);

    if ($stmt = $mysqli->prepare($sql)){

        $stmt->bind_param('ii', $customer,$queue);
        $stmt->execute();
        $stmt->bind_result($response);
        $stmt->fetch();
        $stmt->close();


        if($mysqli->error){
            error_log("MySQL Query error. [$sql]. " . $mysqli->error );
            return false;
        }

        return $response;


    } else {
        if($mysqli->error){
            error_log("MySQL Query error. [$sql]. " . $mysqli->error );
            return false;
        }

        return false;
    }




}



function getQueueAverageHoldTime($customer,$queue, $dateRangeCode, $timeOffset,$mysqli){
    $response = 0;

    $columnName = "call_time";
    $dateRangeSQL = convertDateRangeCodeToSQLdateRange($dateRangeCode,$columnName,$timeOffset);

    //    select count(*) from queue_call_logs WHERE customer =  1 and queue =? and  date(call_time) = date(date_add(now(), INTERVAL 0 hour) ;
    // select count(*) from queue_call_logs WHERE customer =  1 AND domain = 1 and queue =1 and date(call_time) = date(date_sub(now(), INTERVAL 5 hour) );
    $sql = "select avg(wait_time) from queue_call_logs WHERE customer =  ? and queue =? AND    $dateRangeSQL; ";
    //error_log($sql);

    if ($stmt = $mysqli->prepare($sql)){

        $stmt->bind_param('ii', $customer,$queue);
        $stmt->execute();
        $stmt->bind_result($response);
        $stmt->fetch();
        $stmt->close();


        if($mysqli->error){
            error_log("MySQL Query error. [$sql]. " . $mysqli->error );
            return false;
        }


        $ahtMinutes = sprintf("%02d", intval($response/60)   );
        $ahtSeconds =    sprintf("%02d",  intval(($response - $ahtMinutes*60))   );
        $ahtFriendly = $ahtMinutes . ':'. $ahtSeconds;

        return $ahtFriendly;


    } else {
        if($mysqli->error){
            error_log("MySQL Query error. [$sql]. " . $mysqli->error );
            return false;
        }

        return false;
    }

}


function getQueueAverageHoldTimeAnsweredOnly($customer,$queue, $dateRangeCode, $timeOffset,$mysqli){
    $response = 0;

    $columnName = "call_time";
    $dateRangeSQL = convertDateRangeCodeToSQLdateRange($dateRangeCode,$columnName,$timeOffset);

    //    select count(*) from queue_call_logs WHERE customer =  1 and queue =? and  date(call_time) = date(date_add(now(), INTERVAL 0 hour) ;
    // select count(*) from queue_call_logs WHERE customer =  1 AND domain = 1 and queue =1 and date(call_time) = date(date_sub(now(), INTERVAL 5 hour) );
    $sql = "select avg(wait_time) from queue_call_logs WHERE answer_time > 0 AND customer =  ? and queue =? AND    $dateRangeSQL; ";
    //error_log($sql);

    if ($stmt = $mysqli->prepare($sql)){

        $stmt->bind_param('ii', $customer,$queue);
        $stmt->execute();
        $stmt->bind_result($response);
        $stmt->fetch();
        $stmt->close();


        if($mysqli->error){
            error_log("MySQL Query error. [$sql]. " . $mysqli->error );
            return false;
        }


        $ahtMinutes = sprintf("%02d", intval($response/60)   );
        $ahtSeconds =    sprintf("%02d",  intval(($response - $ahtMinutes*60))   );
        $ahtFriendly = $ahtMinutes . ':'. $ahtSeconds;

        return $ahtFriendly;


    } else {
        if($mysqli->error){
            error_log("MySQL Query error. [$sql]. " . $mysqli->error );
            return false;
        }

        return false;
    }

}

function getQueueMaximumHoldTimeAnsweredOnly($customer,$queue, $dateRangeCode, $timeOffset,$mysqli){
    $response = 0;

    $columnName = "call_time";
    $dateRangeSQL = convertDateRangeCodeToSQLdateRange($dateRangeCode,$columnName,$timeOffset);

    //    select count(*) from queue_call_logs WHERE customer =  1 and queue =? and  date(call_time) = date(date_add(now(), INTERVAL 0 hour) ;
    // select count(*) from queue_call_logs WHERE customer =  1 AND domain = 1 and queue =1 and date(call_time) = date(date_sub(now(), INTERVAL 5 hour) );
    $sql = "select max(wait_time) from queue_call_logs WHERE answer_time > 0 AND customer =  ? and queue =? AND    $dateRangeSQL; ";
    //error_log($sql);

    if ($stmt = $mysqli->prepare($sql)){

        $stmt->bind_param('ii', $customer,$queue);
        $stmt->execute();
        $stmt->bind_result($response);
        $stmt->fetch();
        $stmt->close();


        if($mysqli->error){
            error_log("MySQL Query error. [$sql]. " . $mysqli->error );
            return false;
        }


        $ahtMinutes = sprintf("%02d", intval($response/60)   );
        $ahtSeconds =    sprintf("%02d",  intval(($response - $ahtMinutes*60))   );
        $ahtFriendly = $ahtMinutes . ':'. $ahtSeconds;

        return $ahtFriendly;


    } else {
        if($mysqli->error){
            error_log("MySQL Query error. [$sql]. " . $mysqli->error );
            return false;
        }

        return false;
    }

}


function getQueueAverageHoldTimeDroppedOnly($customer,$queue, $dateRangeCode, $timeOffset,$mysqli){
    $response = 0;

    $columnName = "call_time";
    $dateRangeSQL = convertDateRangeCodeToSQLdateRange($dateRangeCode,$columnName,$timeOffset);

    //    select count(*) from queue_call_logs WHERE customer =  1 and queue =? and  date(call_time) = date(date_add(now(), INTERVAL 0 hour) ;
    // select count(*) from queue_call_logs WHERE customer =  1 AND domain = 1 and queue =1 and date(call_time) = date(date_sub(now(), INTERVAL 5 hour) );
    $sql = "select avg(wait_time) from queue_call_logs WHERE disposition = 2 AND customer =  ? and queue =? AND    $dateRangeSQL; ";
    //error_log($sql);

    if ($stmt = $mysqli->prepare($sql)){

        $stmt->bind_param('ii', $customer,$queue);
        $stmt->execute();
        $stmt->bind_result($response);
        $stmt->fetch();
        $stmt->close();


        if($mysqli->error){
            error_log("MySQL Query error. [$sql]. " . $mysqli->error );
            return false;
        }


        $ahtMinutes = sprintf("%02d", intval($response/60)   );
        $ahtSeconds =    sprintf("%02d",  intval(($response - $ahtMinutes*60))   );
        $ahtFriendly = $ahtMinutes . ':'. $ahtSeconds;

        return $ahtFriendly;


    } else {
        if($mysqli->error){
            error_log("MySQL Query error. [$sql]. " . $mysqli->error );
            return false;
        }

        return false;
    }

}


function getQueueMaxHoldTimeDroppedOnly($customer,$queue, $dateRangeCode, $timeOffset,$mysqli){
    $response = 0;

    $columnName = "call_time";
    $dateRangeSQL = convertDateRangeCodeToSQLdateRange($dateRangeCode,$columnName,$timeOffset);

    //    select count(*) from queue_call_logs WHERE customer =  1 and queue =? and  date(call_time) = date(date_add(now(), INTERVAL 0 hour) ;
    // select count(*) from queue_call_logs WHERE customer =  1 AND domain = 1 and queue =1 and date(call_time) = date(date_sub(now(), INTERVAL 5 hour) );
    $sql = "select max(wait_time) from queue_call_logs WHERE disposition = 2 AND customer =  ? and queue =? AND    $dateRangeSQL; ";
    //error_log($sql);

    if ($stmt = $mysqli->prepare($sql)){

        $stmt->bind_param('ii', $customer,$queue);
        $stmt->execute();
        $stmt->bind_result($response);
        $stmt->fetch();
        $stmt->close();


        if($mysqli->error){
            error_log("MySQL Query error. [$sql]. " . $mysqli->error );
            return false;
        }


        $ahtMinutes = sprintf("%02d", intval($response/60)   );
        $ahtSeconds =    sprintf("%02d",  intval(($response - $ahtMinutes*60))   );
        $ahtFriendly = $ahtMinutes . ':'. $ahtSeconds;

        return $ahtFriendly;


    } else {
        if($mysqli->error){
            error_log("MySQL Query error. [$sql]. " . $mysqli->error );
            return false;
        }

        return false;
    }

}


function getQueueAverageDurationAnsweredOnly($customer,$queue, $dateRangeCode, $timeOffset,$mysqli){
    $response = 0;

    $columnName = "call_time";
    $dateRangeSQL = convertDateRangeCodeToSQLdateRange($dateRangeCode,$columnName,$timeOffset);

    //    select count(*) from queue_call_logs WHERE customer =  1 and queue =? and  date(call_time) = date(date_add(now(), INTERVAL 0 hour) ;
    // select count(*) from queue_call_logs WHERE customer =  1 AND domain = 1 and queue =1 and date(call_time) = date(date_sub(now(), INTERVAL 5 hour) );
    $sql = "select avg(duration) from queue_call_logs WHERE answer_time > 0 AND end_time > 0 AND customer =  ? and queue =? AND    $dateRangeSQL; ";
    //error_log($sql);

    if ($stmt = $mysqli->prepare($sql)){

        $stmt->bind_param('ii', $customer,$queue);
        $stmt->execute();
        $stmt->bind_result($response);
        $stmt->fetch();
        $stmt->close();


        if($mysqli->error){
            error_log("MySQL Query error. [$sql]. " . $mysqli->error );
            return false;
        }


        $ahtMinutes = sprintf("%02d", intval($response/60)   );
        $ahtSeconds =    sprintf("%02d",  intval(($response - $ahtMinutes*60))   );
        $ahtFriendly = $ahtMinutes . ':'. $ahtSeconds;

        return $ahtFriendly;


    } else {
        if($mysqli->error){
            error_log("MySQL Query error. [$sql]. " . $mysqli->error );
            return false;
        }

        return false;
    }

}





function getQueueMaximumHoldTime($customer,$queue, $dateRangeCode, $timeOffset,$mysqli){
    $response = 0;

    $columnName = "call_time";
    $dateRangeSQL = convertDateRangeCodeToSQLdateRange($dateRangeCode,$columnName,$timeOffset);

    //    select count(*) from queue_call_logs WHERE customer =  1 and queue =? and  date(call_time) = date(date_add(now(), INTERVAL 0 hour) ;
    // select count(*) from queue_call_logs WHERE customer =  1 AND domain = 1 and queue =1 and date(call_time) = date(date_sub(now(), INTERVAL 5 hour) );
    $sql = "select max(wait_time) from queue_call_logs WHERE customer =  ? and queue =? AND    $dateRangeSQL; ";
    //error_log($sql);

    if ($stmt = $mysqli->prepare($sql)){

        $stmt->bind_param('ii', $customer,$queue);
        $stmt->execute();
        $stmt->bind_result($response);
        $stmt->fetch();
        $stmt->close();


        if($mysqli->error){
            error_log("MySQL Query error. [$sql]. " . $mysqli->error );
            return false;
        }


        $ahtMinutes = sprintf("%02d", intval($response/60)   );
        $ahtSeconds =    sprintf("%02d",  intval(($response - $ahtMinutes*60))   );
        $ahtFriendly = 'max: '.$ahtMinutes . ':'. $ahtSeconds;

        return $ahtFriendly;


    } else {
        if($mysqli->error){
            error_log("MySQL Query error. [$sql]. " . $mysqli->error );
            return false;
        }

        return false;
    }

}







function getQueueCallsOnHoldCount($queueID,$mysqli){

    $sql = "SELECT  callers  FROM queues_rt_data WHERE queue = ? and date_updated > date_sub(now(), INTERVAL 60 second) LIMIT 1;";

    $stmt = $mysqli->prepare($sql);


    if($stmt){
        $stmt->bind_param("i",$queueID);
        $stmt->execute();   // Execute the prepared query.
        //$stmt->store_result();

        $stmt->bind_result($callers);

        $s = '';
        if($stmt->fetch()){  // 1 row only!!!

            $callersArr =  json_decode($callers,true);


            $s = count($callersArr);


        }
        return $s;
    }else{
        error_log("error getQueueCallsOnHoldCount.");
    }
}






function getQueueAgentsCount($queueID,$mysqli){
    $at =  0;

    $sql = "select count(*) from queue_agent_sessions WHERE queue = ? AND session_start < now() AND session_end is null;";

    $stmt = $mysqli->prepare($sql);


    if($stmt){
        $stmt->bind_param("i",$queueID);
        $stmt->execute();   // Execute the prepared query.
        //$stmt->store_result();

        $stmt->bind_result($at);
        $stmt->fetch();



        return $at;
    }else{
        error_log("error getQueueAgentsCount.");
    }
}



function getQueueAgentsWaitingCount($queueID,$mysqli){
    $at =  0;

    $sql = "select count(*) from queue_agent_sessions WHERE queue = ? AND session_start < now() AND session_end is null;";

    $stmt = $mysqli->prepare($sql);


    if($stmt){
        $stmt->bind_param("i",$queueID);
        $stmt->execute();   // Execute the prepared query.
        //$stmt->store_result();

        $stmt->bind_result($at);
        $stmt->fetch();



        return $at;
    }else{
        error_log("error getQueueAgentsCount.");
    }
}




















function convertDateRangeCodeToSQLdateRange($dateRangeCode,$columnName,$timeOffset){
    $mysql_week_mode = 1;


    $finalValue = "";

    switch($dateRangeCode){
        case 0:{ //today

            $finalValue = " date(".$columnName.") = date(date_add(now(), INTERVAL ".$timeOffset." hour)) ";

        }break;
        case 1:{ //yesterday

            $finalValue = " date(".$columnName.") =  date_sub(  date(date_add(now(), INTERVAL ".$timeOffset." hour)) , INTERVAL 1 day)";

        }break;
        case 2:{  // this week

            $finalValue = " week(".$columnName.", ". $mysql_week_mode .") = week(date_add(now(), INTERVAL ".$timeOffset." hour), 1) AND year(".$columnName.") = year(date_add(now(), INTERVAL ".$timeOffset." hour))";

        }break;
        case 3:{  // previous week
            $finalValue = " week(".$columnName.", ". $mysql_week_mode .") = week(  date_sub(  date_add(now(), INTERVAL ".$timeOffset." hour) , INTERVAL 1 week) , ". $mysql_week_mode ."   ) AND year(".$columnName.") = year(   date_sub(     date_add(now(), INTERVAL ".$timeOffset." hour) , INTERVAL 1 week)    )";

        }break;
        case 4:{  // this month

            $finalValue = " month(".$columnName.") = month(date_add(now(), INTERVAL ".$timeOffset." hour)) AND year(".$columnName.") = year(date_add(now(), INTERVAL ".$timeOffset." hour))";

        }break;
        case 5:{ // previous month

            $finalValue = " month(".$columnName.") = month(date_add(now(), INTERVAL ".$timeOffset." hour)) AND year(".$columnName.") = year(date_add(now(), INTERVAL ".$timeOffset." hour))";

        }break;
        case 6:{  // last 7 days


        }break;
        case 7:{   // last 14 days


        }break;
        case 8:{   // last 30 days


        }break;




    }




    return $finalValue;
}


function convertDateRangeCodeToFriendlyName($dateRangeCode,$timeOffset){
    $finalValue = "";

    switch($dateRangeCode){
        case 0:{ //today
            $finalValue = "Today, (GMT $timeOffset)";

        }break;
        case 1:{ //yesterday
            $finalValue = "Yesterday, (GMT $timeOffset)";
        }break;
        case 2:{  // this week

            $finalValue = "This week, (GMT $timeOffset)";
        }break;
        case 3:{  // previous week
            $finalValue = "Previous week, (GMT $timeOffset)";

        }break;
        case 4:{  // this month

            $finalValue = "This month, (GMT $timeOffset)";
        }break;
        case 5:{ // previous month

            $finalValue = "Previous month, (GMT $timeOffset)";
        }break;
        case 6:{  // last 7 days

            $finalValue = "Last 7 days, (GMT $timeOffset)";

        }break;
        case 7:{   // last 14 days

            $finalValue = "Last 14 days, (GMT $timeOffset)";

        }break;
        case 8:{   // last 30 days

            $finalValue = "Last 30 days, (GMT $timeOffset)";

        }break;




    }




    return $finalValue;
}




function getQueueRTdata($queueID,$mysqli){

    $sql = "SELECT  domain,queue,consumer_count,caller_count,waiting_count,outbound_strategy,outbound_priority,ring_timeout,outbound,callers,consumers, bridges  FROM queues_rt_data WHERE queue = ? AND date_updated > date_sub(now(), INTERVAL 60 SECOND) LIMIT 1;";

    $stmt = $mysqli->prepare($sql);


    if ($stmt) {
        $stmt->bind_param("i", $queueID);
        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();

        $stmt->bind_result($domain, $queue, $consumer_count, $caller_count, $waiting_count, $outbound_strategy, $outbound_priority, $ring_timeout, $outbound, $callers, $consumers, $bridges);


        if ($stmt->fetch()) {  // 1 row only!!!
            $returnArray = array(
                "domain" => $domain,
                "queue"=> $queue,
                "consumer_count"=> $consumer_count,
                "caller_count"=> $caller_count,
                "waiting_count"=> $waiting_count,
                "outbound_strategy"=> $outbound_strategy,
                "outbound_priority"=> $outbound_priority,
                "ring_timeout"=> $ring_timeout,
                "outbound"=> $outbound,
                "callers"=> $callers,
                "consumers"=> $consumers,
                "bridges"=> $bridges
            );

            return $returnArray;

        }
    }


}



function getQueueConsumersCount($queueID,$mysqli){

    $queueRTdata = getQueueRTdata($queueID,$mysqli);

    return $queueRTdata["consumer_count"];


}




function getQueueCallLogsReportForAgents($customer,$queue, $dateRangeCode, $timeOffset,$mysqli){
    $response = array();

    $columnName = "call_time";
    $dateRangeSQL = convertDateRangeCodeToSQLdateRange($dateRangeCode,$columnName,$timeOffset);

    //    select count(*) from queue_call_logs WHERE customer =  1 and queue =? and  date(call_time) = date(date_add(now(), INTERVAL 0 hour) ;
    // select count(*) from queue_call_logs WHERE customer =  1 AND domain = 1 and queue =1 and date(call_time) = date(date_sub(now(), INTERVAL 5 hour) );
    $sql = "select agent, count(*),sum(duration),avg(duration) from queue_call_logs WHERE customer =  ? and queue =? AND  answer_time > 0  AND end_time >0    AND    $dateRangeSQL  GROUP by agent; ";
    //error_log($sql);

    if ($stmt = $mysqli->prepare($sql)){

        $stmt->bind_param('ii', $customer,$queue);
        $stmt->execute();
        $stmt->bind_result($agent,$calls,$duration,$agerageDuration);


        if($mysqli->error){
            error_log("MySQL Query error. [$sql]. " . $mysqli->error );
            return false;
        }

        while( $stmt->fetch()){

            // total talk time

            $ttHours = sprintf("%02d", intval($duration/3600)   );
            $ttMinutes = sprintf("%02d", intval(($duration - $ttHours*3600)/60)   );
            $ttSeconds =    sprintf("%02d",  intval(($duration - $ttHours*3600 - $ttMinutes*60))   );
            $ttFriendly = $ttHours.':'.$ttMinutes . ':'. $ttSeconds;


            // average
            $ahtMinutes = sprintf("%02d", intval($agerageDuration/60)   );
            $ahtSeconds =    sprintf("%02d",  intval(($agerageDuration - $ahtMinutes*60))   );
            $averageFriendly = $ahtMinutes . ':'. $ahtSeconds;



            $response[] = array(
                "agent" => $agent,
                "calls" => $calls,
                "talk_time" => $ttFriendly,
                "average" => $averageFriendly

            );




        }


        $stmt->close();



        return $response;







    } else {
        if($mysqli->error){
            error_log("MySQL Query error. [$sql]. " . $mysqli->error );
            return false;
        }

        return false;
    }

}



function getQueueTotalDuration($customer,$queue, $dateRangeCode, $timeOffset,$mysqli){


    $columnName = "call_time";
    $dateRangeSQL = convertDateRangeCodeToSQLdateRange($dateRangeCode,$columnName,$timeOffset);

    //    select count(*) from queue_call_logs WHERE customer =  1 and queue =? and  date(call_time) = date(date_add(now(), INTERVAL 0 hour) ;
    // select count(*) from queue_call_logs WHERE customer =  1 AND domain = 1 and queue =1 and date(call_time) = date(date_sub(now(), INTERVAL 5 hour) );
    $sql = "select sum(duration) from queue_call_logs WHERE customer =  ? and queue =? AND  answer_time > 0  AND end_time >0    AND    $dateRangeSQL; ";
    //error_log($sql);

    if ($stmt = $mysqli->prepare($sql)){

        $stmt->bind_param('ii', $customer,$queue);
        $stmt->execute();
        $stmt->bind_result($totalDuration);


        if($mysqli->error){
            error_log("MySQL Query error. [$sql]. " . $mysqli->error );
            return false;
        }

         $stmt->fetch();

            // total talk time

            $ttHours = sprintf("%02d", intval($totalDuration/3600)   );
            $ttMinutes = sprintf("%02d", intval(($totalDuration - $ttHours*3600)/60)   );
            $ttSeconds =    sprintf("%02d",  intval(($totalDuration - $ttHours*3600 - $ttMinutes*60))   );
            $ttFriendly = $ttHours.':'.$ttMinutes . ':'. $ttSeconds;





        $stmt->close();



        return $ttFriendly;







    } else {
        if($mysqli->error){
            error_log("MySQL Query error. [$sql]. " . $mysqli->error );
            return false;
        }

        return false;
    }

}


// ====== IVR menus


function getIVRmenuNameByID($id,$mysqli){

    $sql = "select name from ivr_menus where id = ?;";

    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->bind_result($returnVal);
    $stmt->fetch();

    return $returnVal;

}




function getCustomerIDByIVRmenuID($id,$mysqli){

    $sql = "select customer from ivr_menus where id = ?;";

    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->bind_result($returnVal);
    $stmt->fetch();

    return $returnVal;

}



function getIVRmenusListForSelectView($customerID,$mysqli,$selected = null){

    $sql = "select id, name FROM ivr_menus   WHERE customer = ?;";
    $stmt = $mysqli->prepare($sql);


    if($stmt){

        $stmt->bind_param('i', $customerID);
        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();
        $stmt->bind_result($id, $name);
        $s = '';
        while($stmt->fetch()){

            $oneRow = '<option value="' . $id . '" ';

            if($selected == $id)
                $oneRow .= '  selected="selected" ';


            $oneRow .= ' >' . $name . '</option>';


            $s .= $oneRow;

        }
        return $s;
    }else{
        error_log("VTPBX: error getting list of IVR menus for select view.");
    }


}


function getCustomerIVRmenusListView($customerID,$mysqli){

    $sql = "select id,name from ivr_menus where customer  = ?;";
    $stmt = $mysqli->prepare($sql);


    if($stmt){

        $stmt->bind_param('i', $customerID);
        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();
        $stmt->bind_result($id, $name);

        $s = '';
        while($stmt->fetch()){



            $oneRow = '<tr>'.

                '<td>' . $name .'</td>'.
                '<td class="text-center"><div class="btn-group btn-group-sm" role="group" aria-label="Table row actions">'.
                '<a href="dashboard.php?p=customer_ivr_menu_details&id=' . $id . '"><button type="button" class="btn btn-white">Edit <i class="material-icons"></i></button></a>'.
                '<button type="button" class="btn btn-white deleteIVRmenuBtn" data-id="'.$id.'">Delete <i class="material-icons"></i></button>'.
                '</div></td>'.
                '</tr>';

            $s .= $oneRow;

        }
        return $s;
    }else{
        error_log("VTPBX: error getting list of IVR menus for customer.");
    }


}



function getIVRMenuFullDetails($IVRmenuID,$mysqli){

    $sql = "select id,customer,domain,name,menu_details  from ivr_menus where id = ?;";

    $stmt = $mysqli->prepare($sql);


    if ($stmt) {
        $stmt->bind_param("i", $IVRmenuID);
        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();

        $stmt->bind_result($id,$customer,$domain,$name,$menu_details);


        if ($stmt->fetch()) {  // 1 row only!!!
            $returnArray = array(
                "id" => $id,
                "customer"=> $customer,
                "domain"=> $domain,
                "name"=> $name,
                "menu_details"=> json_decode($menu_details,true)
            );

            return $returnArray;

        }
    }
}


function getIVRAllMenuDetailsForOneCustomer($customer,$mysqli){

    $sql = "select id,customer,domain,name,menu_details  from ivr_menus where customer = ?;";

    $stmt = $mysqli->prepare($sql);
    $returnArray = array();

    if ($stmt) {
        $stmt->bind_param("i", $customer);
        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();

        $stmt->bind_result($id,$customer,$domain,$name,$menu_details);


        while($stmt->fetch()) {  // multiple rows
            $returnArray[] = array(
                "id" => $id,
                "customer"=> $customer,
                "domain"=> $domain,
                "name"=> $name,
                "menu_details"=> json_decode($menu_details,true)
            );



        }

        return $returnArray;
    }

    return null;
}

function getIVRAllMenuDetails($mysqli){

    $sql = "select id,customer,domain,name,menu_details  from ivr_menus;";

    $stmt = $mysqli->prepare($sql);
    $returnArray = array();

    if ($stmt) {
        //$stmt->bind_param("i", $customer);
        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();

        $stmt->bind_result($id,$customer,$domain,$name,$menu_details);


        while($stmt->fetch()) {  // multiple rows
            $returnArray[] = array(
                "id" => $id,
                "customer"=> $customer,
                "domain"=> $domain,
                "name"=> $name,
                "menu_details"=> json_decode($menu_details,true)
            );

        }

        return $returnArray;
    }

    return null;
}




function updateIVRmenuDetails($id,$name,$ivrMenu,$mysqli){

    $sql = "UPDATE ivr_menus set name = ?, menu_details = ? WHERE id = ?;";

    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('ssi', $name,$ivrMenu,$id);
    $stmt->execute();


    return true;

}




// ============

function displayIVRActionSelect($actionTypeSelected){

    $s = "";



    $s .='<option value="">- select -</option>';

    $s .='<option value="USER" ' ;
        if($actionTypeSelected == "USER")  $s .= ' selected="selected" ' ;
    $s .='>User</option>';

    $s .='<option value="CONFERENCE" ' ;
        if($actionTypeSelected == "CONFERENCE")  $s .= ' selected="selected" ' ;
    $s .='>Conference</option>';

    $s .='<option value="QUEUE" ' ;
        if($actionTypeSelected == "QUEUE")  $s .= ' selected="selected" ' ;
    $s .='>Queue</option>';

    $s .='<option value="IVR" ' ;
        if($actionTypeSelected == "IVR")  $s .= ' selected="selected" ' ;
    $s .='>IVR</option>';


    $s .='<option value="GROUP" ' ;
    if($actionTypeSelected == "GROUP")  $s .= ' selected="selected" ' ;
    $s .='>Group</option>';

    $s .='<option value="ACTION" ' ;
    if($actionTypeSelected == "ACTION")  $s .= ' selected="selected" ' ;
    $s .='>Action</option>';




    $s .='<option value="MENU-TOP" ' ;
        if($actionTypeSelected == "MENU-TOP")  $s .= ' selected="selected" ' ;
    $s .='>Go back or repeat</option>';



    return $s;
}


function displayIVRActionDefinitionSelect($customerID,$actionTypeSelected,$actionDefinitionSelected,$mysqli){
    $s = "";
    switch($actionTypeSelected){
        case "USER":{

            $s = getUsersListForSelectView($customerID,$mysqli,$actionDefinitionSelected);

        }break;
        case "QUEUE":{

            $s = getQueuesListForSelectView($customerID,$mysqli,$actionDefinitionSelected);

        }break;
        case "CONFERENCE":{

            $s = getConferencesListForSelectView($customerID,$mysqli,$actionDefinitionSelected);

        }break;
        case "IVR":{

            $s = getIVRmenusListForSelectView($customerID,$mysqli,$actionDefinitionSelected);

        }break;
        case "GROUP":{

            $s = getGroupsListForSelectView($customerID,$mysqli,$actionDefinitionSelected);

        }break;
        case "ACTION":{

            $s = getActionsListForSelectView($customerID,$mysqli,$actionDefinitionSelected);

        }break;


        //MENU-TOP
        case "MENU-TOP":{

            $s = '<option value="">-</option>';

        }break;






    }



    return $s;


}



function addNewIVRmenu($customerID,$domainID,$name,$mysqli){


    $sql = "INSERT INTO ivr_menus(customer,domain,name) VALUES (?,?,?) ;";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('iis', $customerID,$domainID,$name);
    $stmt->execute();
    $newNumberID = $stmt->insert_id;
    $stmt->close();

    if($mysqli->error){
        error_log("VTPBX:  Error in addNewIVRmenu, details: ". $mysqli->error);
        return false;
    }

    return $newNumberID;
}






function addNewIVRMediaFile($dialerID,$file_description,$file_name,$mysqli){

    $sql = "INSERT INTO ivr_files(customer,file_description,file_name) VALUES (?,?,?);";

    $stmt = $mysqli->prepare($sql);
    if($mysqli->error){
        error_log("Error in dialerAddNewSipTrunk, details: ". $mysqli->error);
        return false;
    }
    $stmt->bind_param('iss', $dialerID,$file_description,$file_name);
    $stmt->execute();

    if($mysqli->error){
        error_log("Error in dialerAddNewIVRMediaFile, details: ". $mysqli->error);
        return false;
    }

    return true;

}




function getListOfOutboundSIPprovidersForResellerArr($reseller,$mysqli){

    $sql = "select id,name,ip_address from outbound_sip_providers WHERE (reseller = ? OR reseller = 0 ) AND  is_deleted = 0  order by id asc;";

    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $reseller);

    if($stmt){


        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();
        //$log->LogDebug('SELECT statement executed, binding result.');
        $stmt->bind_result($id,$name,$ip_address);

        $s = array();
        while($stmt->fetch()){




            $oneRow = array(
                "id"=> $id,
                "name" => $name,
                "ip_address" => $ip_address
            );

            $s[] = $oneRow;

        }
        return $s;

    }else{
        error_log("Error getting list of getListOfOutboundSIPprovidersForResellerArr.");
    }
}



function getListOfOutboundSIPprovidersArr($mysqli){

    $sql = "select id,name,ip_address from outbound_sip_providers  WHERE is_deleted = 0 order by id asc;";

    $stmt = $mysqli->prepare($sql);


    if($stmt){


        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();
        //$log->LogDebug('SELECT statement executed, binding result.');
        $stmt->bind_result($id,$name,$ip_address);

        $s = array();
        while($stmt->fetch()){




            $oneRow = array(
                "id"=> $id,
                "name" => $name,
                "ip_address" => $ip_address
            );

            $s[] = $oneRow;

        }
        return $s;
    }else{
        error_log("Error getting list of getListOfOutboundSIPprovidersArr.");
    }
}

function checkIfOutboundSIPproviderCanBeUsedByClient($dialerID,$providerID,$mysqli){
    $result = 0;


    $sql = "select count(*) from outbound_sip_providers WHERE (customer = ? OR (customer = 0 AND reseller = 0)) AND id = ?;";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('ii', $dialerID,$providerID);
    $stmt->execute();
    $stmt->bind_result($result);
    $stmt->fetch();
    $stmt->close();


    return $result;
}




function updateCustomerOutboundSIPprovider($dialerID,$outboundSIPprovider,$mysqli){

    $sql = "UPDATE customers set  sip_provider = ? , date_updated=now() WHERE id = ?;";

    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('ii', $outboundSIPprovider,$dialerID);
    $stmt->execute();

    if($mysqli->error){
        error_log("VTDial:  Error in updateDialerOutboundSIPprovider, details: ". $mysqli->error);
        return false;
    }

    return true;

}




function addNewOutboundSIPproviderForCustomer($name,$ip_address,$reseller,$customer,$mysqli){

    // INSERT INTO users (username,password,type) VALUES ("intactcomm",sha2("12Age93knFwqo3",256),2);
    $sql = "INSERT INTO outbound_sip_providers(name,ip_address, reseller, customer) VALUES (?,?,?,?);";

    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('ssii', $name,$ip_address,$reseller,$customer);
    $stmt->execute();
    $newID = $stmt->insert_id;

    if($mysqli->error){
        error_log("VTDial:  Error in addNewOutboundSIPprovider, details: ". $mysqli->error);
        return false;
    }

    return $newID;

}


function getOutboundSIPproviderCustomerID($providerID,$mysqli){
    $customer = 0;


    $sql = "select customer from outbound_sip_providers WHERE id = ?;";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $providerID);
    $stmt->execute();
    $stmt->bind_result($customer);
    $stmt->fetch();
    $stmt->close();


    return $customer;
}



function getOutboundSIPproviderName($providerID,$mysqli){
    $name = "";


    $sql = "select name from outbound_sip_providers WHERE id = ?;";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $providerID);
    $stmt->execute();
    $stmt->bind_result($name);
    $stmt->fetch();
    $stmt->close();


    return $name;
}



function getListOfOutboundSIPprovidersForCustomerArr($customerID,$mysqli){

    $sql = "select id,name,ip_address from outbound_sip_providers WHERE  reseller = 0  AND  is_deleted = 0 AND customer = ? order by id asc;";

    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $customerID);

    if($stmt){


        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();
        //$log->LogDebug('SELECT statement executed, binding result.');
        $stmt->bind_result($id,$name,$ip_address);

        $s = array();
        while($stmt->fetch()){




            $oneRow = array(
                "id"=> $id,
                "name" => $name,
                "ip_address" => $ip_address
            );

            $s[] = $oneRow;

        }
        return $s;

    }else{
        error_log("Error getting list of getListOfOutboundSIPprovidersForResellerArr.");
    }
}


function updateUserMainSettings($name,$type,$sip_password,$user_id,$mysqli){

    $sql = "update users set name = ?, type = ? , sip_password = ? WHERE id = ?;";

    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('sisi', $name,$type,$sip_password,$user_id );
    $stmt->execute();

    if($mysqli->error){
        error_log("VTDial:  Error in updateUserMainSettings, details: ". $mysqli->error);
        return false;
    }

    return true;

}




function updateUserVMsettings($vm_enable,$vm_greeting,$vm_password,$vm_timeout,$user_id,$mysqli){

    $sql = "update users set vm_enable = ?, vm_greeting = ? , vm_password = ?, vm_timeout =? WHERE id = ?;";

    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('iisii', $vm_enable,$vm_greeting,$vm_password,$vm_timeout,$user_id );
    $stmt->execute();

    if($mysqli->error){
        error_log("VTDial:  Error in updateUserVMsettings, details: ". $mysqli->error);
        return false;
    }

    return true;

}






function updateUserCFsettings($cf_settings_json,$user_id,$mysqli){

    $sql = "update users set call_forwarding =? WHERE id = ?;";

    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('si', $cf_settings_json,  $user_id );
    $stmt->execute();

    if($mysqli->error){
        error_log("VTDial:  Error in updateUserCFsettings, details: ". $mysqli->error);
        return false;
    }

    return true;

}

function updateUserExternalCallerID($external_caller_id,$user_id,$mysqli){

    $sql = "update users set external_caller_id =? WHERE id = ?;";

    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('si', $external_caller_id,$user_id );
    $stmt->execute();

    if($mysqli->error){
        error_log("VTDial:  Error in updateUserExternalCallerID, details: ". $mysqli->error);
        return false;
    }

    return true;

}


function updateUserRecordInternalFlag($value,$user_id,$mysqli){

    if($value == "on")
        $value = 1;


    $sql = "update users set record_internal =? WHERE id = ?;";

    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('ii', $value,$user_id );
    $stmt->execute();

    if($mysqli->error){
        error_log("VTDial:  Error in updateUserRecordInternalFlag, details: ". $mysqli->error);
        return false;
    }

    return true;

}

function updateUserRecordIncomingFlag($value,$user_id,$mysqli){

    if($value == "on")
        $value = 1;


    $sql = "update users set record_incoming =? WHERE id = ?;";

    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('ii', $value,$user_id );
    $stmt->execute();

    if($mysqli->error){
        error_log("VTDial:  Error in updateUserRecordIncomingFlag, details: ". $mysqli->error);
        return false;
    }

    return true;

}

function updateUserRecordExternalFlag($value,$user_id,$mysqli){

    if($value == "on")
        $value = 1;


    $sql = "update users set record_external =? WHERE id = ?;";

    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('ii', $value,$user_id );
    $stmt->execute();

    if($mysqli->error){
        error_log("VTDial:  Error in updateUserRecordExternalFlag, details: ". $mysqli->error);
        return false;
    }

    return true;

}







function updateUserDisableExternalCallsFlag($value,$user_id,$mysqli){

    if($value == "on")
        $value = 1;


    $sql = "update users set disable_external_calls =? WHERE id = ?;";

    $stmt = $mysqli->prepare($sql);

    $stmt->bind_param('ii', $value,$user_id );
    $stmt->execute();

    if($mysqli->error){
        error_log("VTDial:  Error in updateUserDisableExternalCallsFlag, details: ". $mysqli->error);
        return false;
    }

    return true;

}












function deleteCustomer($dialerID,$mysqli){

    // Users

    $sql = "DELETE from users where customer = ?;";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $dialerID);
    $stmt->execute();


    // extensions
    $sql = "DELETE from extension_numbers where customer = ?;";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $dialerID);
    $stmt->execute();


    // Queues
    $sql = "DELETE from queues where customer = ?;";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $dialerID);
    $stmt->execute();

    // Conferences
    $sql = "DELETE from conferences where customer = ?;";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $dialerID);
    $stmt->execute();

    // domains
    $sql = "DELETE from domains where customer = ?;";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $dialerID);
    $stmt->execute();

    // did numbers
    $sql = "DELETE from did_numbers where customer = ?;";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $dialerID);
    $stmt->execute();






    $sql = "DELETE from customers where id = ?;";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $dialerID);
    $stmt->execute();

    return true;

}








function deleteDIDnumber($did_id,$mysqli){


    $sql = "DELETE from did_numbers where id = ?;";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $did_id);
    $stmt->execute();

    return true;

}



function addProxyRegistrantItem($sip_username,$sip_password,$sip_registrar,$did_number,$mysqli){
/*

                    id: 5
             registrar: sip:callertech-63dd5e0cccbc.sip.signalwire.com
                 proxy:
                   aor: sip:ctpbx@callertech-63dd5e0cccbc.sip.signalwire.com
third_party_registrant:
              username: ctpbx
              password: 32jkGEJKRfejwGEoqBle
           binding_URI: sip:13309202558@54.184.27.79
        binding_params: NULL
                expiry: 180
         forced_socket: NULL
         cluster_shtag: NULL


 *
 */

    $registrar = "sip:" . $sip_registrar;
    $aor = "sip:" . $sip_username . "@" . $sip_registrar;
    $binding_URI = "sip:" . $did_number .'@' . VTPBX_IN_PROXY;

    $expiry = 121;


    $sql = "INSERT INTO registrant(registrar, aor, username, password, binding_URI,expiry) VALUES (?,?,?,?,?,?);";
    $stmt = $mysqli->prepare($sql);


    $stmt->bind_param('sssssi', $registrar,$aor,$sip_username,$sip_password,$binding_URI,$expiry);
    $stmt->execute();
    $newItemID = $stmt->insert_id;
    $stmt->close();

    if($mysqli->error){
        error_log("CTPBX:  Error in addProxyRegistrantItem, details: ". $mysqli->error);
        return false;
    }

    return $newItemID;
}






function deleteProxyRegistrantItem($registrant_id,$mysqli){
    /*

                        id: 5
                 registrar: sip:callertech-63dd5e0cccbc.sip.signalwire.com
                     proxy:
                       aor: sip:ctpbx@callertech-63dd5e0cccbc.sip.signalwire.com
    third_party_registrant:
                  username: ctpbx
                  password: 32jkGEJKRfejwGEoqBle
               binding_URI: sip:13309202558@54.184.27.79
            binding_params: NULL
                    expiry: 180
             forced_socket: NULL
             cluster_shtag: NULL


     *
     */
    error_log("CTPBX:  deleteProxyRegistrantItem [$registrant_id]: ");

    $sql = "DELETE FROM registrant WHERE id = ?;";
    $stmt = $mysqli->prepare($sql);


    $stmt->bind_param('i', $registrant_id );
    $stmt->execute();

    $stmt->close();

    if($mysqli->error){
        error_log("CTPBX:  Error in deleteProxyRegistrantItem, details: ". $mysqli->error);
        return false;
    }

    return true;
}



function addNewGroup($customerID,$domainID,$name,$mysqli){


    $sql = "INSERT INTO groups(customer,domain,name) VALUES (?,?,?) ;";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('iis', $customerID,$domainID,$name);
    $stmt->execute();
    $newNumberID = $stmt->insert_id;
    $stmt->close();

    if($mysqli->error){
        error_log("VTPBX:  Error in addNewGroup, details: ". $mysqli->error);
        return false;
    }

    return $newNumberID;
}


function addNewAction($customerID,$domainID,$name,$webhook_url, $ivr_playback_file,$mysqli){


    $sql = "INSERT INTO actions(customer,domain,name,webhook_url,ivr_playback_file) VALUES (?,?,?,?,?) ;";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('iissi', $customerID,$domainID,$name,$webhook_url, $ivr_playback_file);
    $stmt->execute();
    $newNumberID = $stmt->insert_id;
    $stmt->close();

    if($mysqli->error){
        error_log("VTPBX:  Error in addNewAction, details: ". $mysqli->error);
        return false;
    }

    return $newNumberID;
}




function getGroupsListForSelectView($customerID,$mysqli,$selected = null){

    $sql = "select id, name FROM groups   WHERE customer = ?;";
    $stmt = $mysqli->prepare($sql);


    if($stmt){

        $stmt->bind_param('i', $customerID);
        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();
        $stmt->bind_result($id, $name);
        $s = '';
        while($stmt->fetch()){

            $oneRow = '<option value="' . $id . '" ';

            if($selected == $id)
                $oneRow .= '  selected="selected" ';


            $oneRow .= ' >' . $name . '</option>';


            $s .= $oneRow;

        }
        return $s;
    }else{
        error_log("VTPBX: error getting list of groups for select view.");
    }


}




function getActionsListForSelectView($customerID,$mysqli,$selected = null){

    $sql = "select id, name FROM actions   WHERE customer = ?;";
    $stmt = $mysqli->prepare($sql);


    if($stmt){

        $stmt->bind_param('i', $customerID);
        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();
        $stmt->bind_result($id, $name);
        $s = '';
        while($stmt->fetch()){

            $oneRow = '<option value="' . $id . '" ';

            if($selected == $id)
                $oneRow .= '  selected="selected" ';


            $oneRow .= ' >' . $name . '</option>';


            $s .= $oneRow;

        }
        return $s;
    }else{
        error_log("VTPBX: error getting list of actions for select view.");
    }


}


function deleteIVRMenu($ivr_id,$mysqli){


    $sql = "DELETE from ivr_menus where id = ?;";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $ivr_id);
    $stmt->execute();

    return true;

}




function deleteGroup($group_id,$mysqli){


    $sql = "DELETE from groups where id = ?;";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $group_id);
    $stmt->execute();

    return true;

}


function deleteAction($action_id,$mysqli){


    $sql = "DELETE from actions where id = ?;";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $action_id);
    $stmt->execute();

    return true;

}


function deleteQueue($queue_id,$mysqli){


    $sql = "DELETE from queues where id = ?;";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $queue_id);
    $stmt->execute();

    return true;

}


function deleteConference($id,$mysqli){


    $sql = "DELETE from conferences where id = ?;";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();

    return true;

}


// Voicemail




function getVoicemailMessageDetailsByUUID($uuid,$mysqli){

    $sql = "select uuid, customer, domain_id, domain_name,username,user_id,time_created,time_read,folder,path,cid_name,cid_number,message_len FROM   voicemail_messages WHERE uuid = ?;";

    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('s', $uuid);

    if($stmt){


        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();
        //$log->LogDebug('SELECT statement executed, binding result.');
        $stmt->bind_result($uuid, $customer, $domain_id, $domain_name, $username,   $user_id,   $time_created,   $time_read,   $folder,   $path,   $cid_name,   $cid_number, $message_len);

        $stmt->fetch();


        $original_call_uuid = getCalluuidByVMCallUUID($uuid,$mysqli);



        $oneRow = array(
            "uuid"=> $uuid,
            "original_call_uuid"=> $original_call_uuid,
            "customer" => $customer,
            "domain_id" => $domain_id,
            "domain_name" => $domain_name,
            "username" => $username,
            "user_id" => $user_id,
            "time_created" => $time_created,
            "time_read" => $time_read,
            "folder" => $folder,
            "path" => $path,
            "cid_name" => $cid_name,
            "cid_number" => $cid_number,
            "message_len" => $message_len


        );



        return $oneRow;


    }else{
        error_log("Error in  getVoicemailMessageDetailsByUUID.");
    }
}


function getVoicemailMessageDetailsForTenant($customer,$mysqli,$row_count = 20,  $page = 1){
    $offset = ($page - 1)*$row_count ;




    $sql = "select uuid, customer, domain_id, domain_name,username,user_id,time_created,time_read,folder,path,cid_name,cid_number,message_len FROM   voicemail_messages WHERE customer = ? ORDER by time_created DESC LIMIT ? OFFSET ?;";

    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('iii', $customer, $row_count, $offset);

    if($stmt){



        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();
        //$log->LogDebug('SELECT statement executed, binding result.');
        $stmt->bind_result($uuid, $customer, $domain_id, $domain_name, $username,   $user_id,   $time_created,   $time_read,   $folder,   $path,   $cid_name,   $cid_number, $message_len);





        $returnArr = array();

        while($stmt->fetch() ){


            $original_call_uuid = getCalluuidByVMCallUUID($uuid,$mysqli);


            $oneRow = array(
                "uuid"=> $uuid,
                "original_call_uuid"=> $original_call_uuid,
                "customer" => $customer,
                "domain_id" => $domain_id,
                "domain_name" => $domain_name,
                "username" => $username,
                "user_id" => $user_id,
                "time_created" => $time_created,
                "time_read" => $time_read,
                "folder" => $folder,
                "path" => $path,
                "cid_name" => $cid_name,
                "cid_number" => $cid_number,
                "pmessage_lenath" => $message_len
            );

            $returnArr[] = $oneRow;

        }






        return $returnArr;


    }else{
        error_log("Error in  getVoicemailMessageDetailsForTenant." . $mysqli->error);
    }
}


function getCalluuidByVMCallUUID($vm_uuid,$mysqli){

    $call_uuid = "";


    $sql = "select call_uuid from call_logs where vm_uuid = ?;";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('s', $vm_uuid);
    $stmt->execute();
    $stmt->bind_result($call_uuid);
    $stmt->fetch();
    $stmt->close();


    return $call_uuid;
}




function getVoicemailMessageDetailsForOneUser($user_id,$mysqli,$row_count = 20,  $page = 1){
    $offset = ($page - 1)*$row_count ;




    $sql = "select uuid, customer, domain_id, domain_name,username,user_id,time_created,time_read,folder,path,cid_name,cid_number,message_len FROM   voicemail_messages WHERE user_id = ? ORDER by time_created DESC LIMIT ? OFFSET ?;";

    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('iii', $user_id, $row_count, $offset);

    if($stmt){



        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();
        //$log->LogDebug('SELECT statement executed, binding result.');
        $stmt->bind_result($uuid, $customer, $domain_id, $domain_name, $username,   $user_id,   $time_created,   $time_read,   $folder,   $path,   $cid_name,   $cid_number, $message_len);




        $returnArr = array();

        while($stmt->fetch() ){

            $original_call_uuid = getCalluuidByVMCallUUID($uuid,$mysqli);


            $oneRow = array(
                "uuid"=> $uuid,
                "original_call_uuid"=> $original_call_uuid,
                "customer" => $customer,
                "domain_id" => $domain_id,
                "domain_name" => $domain_name,
                "username" => $username,
                "user_id" => $user_id,
                "time_created" => $time_created,
                "time_read" => $time_read,
                "folder" => $folder,
                "path" => $path,
                "cid_name" => $cid_name,
                "cid_number" => $cid_number,
                "pmessage_lenath" => $message_len
            );

            $returnArr[] = $oneRow;

        }






        return $returnArr;


    }else{
        error_log("Error in  getVoicemailMessageDetailsForTenant." . $mysqli->error);
    }
}





// Agent sessions (queues)




function getAgentLoggedQueues($user_id,$mysqli){





    $sql = 'select customer, domain, queue, agent, session_start from queue_agent_sessions WHERE agent = ? AND session_end is null;';

    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $user_id);

    if($stmt){



        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();
        //$log->LogDebug('SELECT statement executed, binding result.');
        $stmt->bind_result($customer, $domain, $queue, $agent, $session_start);


        $returnArr = array();

        while($stmt->fetch() ){


            $oneRow = array(
                "customer" => $customer,
                "domain_id" => $domain,
                "queue" => $queue,
                "user_id" => $agent,
                "session_start" => $session_start
            );

            $returnArr[] = $oneRow;

        }






        return $returnArr;


    }else{
        error_log("Error in  getAgentLoggedQueues." . $mysqli->error);
    }
}


function getSIPuserRegistrationDetailsFromProxyDB($username,$domain_name,$mysqli){

    $sql = "select contact_id, username, domain, contact, received, path, expires, q, callid, cseq, last_modified, flags, cflags, user_agent, socket, methods, sip_instance, kv_store, attr from location where username = ? AND domain = ?;";

    $stmt = $mysqli->prepare($sql);


    if ($stmt) {
        $stmt->bind_param("ss", $username,$domain_name);
        $stmt->execute();   // Execute the prepared query.
        $stmt->store_result();

        $stmt->bind_result($contact_id, $username, $domain, $contact, $received, $path, $expires, $q, $callid, $cseq, $last_modified, $flags, $cflags, $user_agent, $socket, $methods, $sip_instance, $kv_store, $attr);
        if($mysqli->error){
            error_log("getSIPuserRegistrationDetailsFromProxyDB: ". $mysqli->error);
            return false;
        }
        $returnArray = array();

        while($stmt->fetch()) {


            $returnArray[] = array(
                "contact_id" => $contact_id,
                "username"=> $username,
                "domain"=> $domain,
                "contact"=> $contact,
                "received"=> $received,
                "path"=> $path,
                "expires"=> $expires,
                "q"=> $q,
                "callid"=> $callid,
                "cseq"=> $cseq,
                "last_modified"=> $last_modified,
                "flags"=> $flags,
                "cflags"=> $cflags,
                "user_agent"=> $user_agent,
                "socket"=> $socket,
                "methods"=> $methods,
                "sip_instance"=> $sip_instance,
                "kv_store"=> $kv_store,
                "attr"=> $attr
            );



        }
        return $returnArray;
    }else{


            error_log("getSIPuserRegistrationDetailsFromProxyDB: ". $mysqli->error);
            return false;



    }
}




function getSIPuserRegistrationStatus($username,$domain_name,$mysqli){
    $result = "0";


    $sql = "select count(*) from location where username = ? AND domain = ?;";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('ss', $username,$domain_name);
    $stmt->execute();
    $stmt->bind_result($result);
    $stmt->fetch();
    $stmt->close();


    return $result;
}



?>


