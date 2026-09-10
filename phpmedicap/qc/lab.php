<?php
    require '../db.php';
    require '../token.php';
    require '../tcpdf/tcpdf.php';
    header('Access-Control-Allow-Origin: *');
    header('Content-Type: application/json');
    date_default_timezone_set("Asia/Kolkata");
    $token = $_GET["token"];
    $timestamp = time();
    $entry_date = date("Y-m-d h:i:s", $timestamp);
    $input = json_decode(file_get_contents('php://input'),true);

    $sql = "SELECT * FROM token WHERE token='".$_GET["token"]."'";
    $result = $conn->query($sql);
    $_GET["emp_id"] = "";
    $_GET["department"] = "";
    if($result->num_rows > 0){
    while($row = $result->fetch_assoc()){
	    $string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
	    $string = explode("$",$string);
	    $_GET["emp_id"] = $string[0];
	    $_GET["department"] = $string[1];
	    break;
    }

    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);

    if ($_GET["type"] == "saveLab") {
         $input=$_POST;
        $id = $input["lab_name"];
    
        
        
                      
                 
        $lic = "";
        $certificate = "";
        if(isset($_FILES["lic"])) {
            $file_tmp =$_FILES['lic']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['lic']['name'])));
            $file_name = $id."lic.".$file_ext;
            $lic = $file_name;
            move_uploaded_file($file_tmp,"../../../upload/qc/".$file_name);
        }
        
        if(isset($_FILES["certificate"])) {
            $file_tmp =$_FILES['certificate']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['certificate']['name'])));
            $file_name1 = $id."certificate.".$file_ext;
            $certificate = $file_name1;
            move_uploaded_file($file_tmp,"../../../upload/qc/".$file_name1);
        }
        
        if (($input["country"] ?? '') === 'Other') {
            $country = $input["ocountry"] ?? '';
        } else {
            $country = $input["country"] ?? '';
        }
        
        
        
     
        
       $sql = "INSERT INTO labs (plant_id,lab_name, contact_no, email, address, contact_person, entry_by, 
        entry_date, acreditation, fda_lic_no, lic_validity, international_acreditation, branch,fda_approved,certificate,fda_lic_file,
        pincode,permanent_state,city,country,tax) VALUES 
        ('".$_GET["plant_id"]."','".$input["lab_name"]."', '".$input["contact_no"]."', 
        '".$input["email"]."', '".$input["address"]."', '".$input["contact_person"]."', 
        '".$_GET["emp_id"]."', '$entry_date','".$input["acreditation"]."', '".$input["fda_lic_no"]."', 
        '".$input["lic_validity"]."', '".$input["international_acreditation"]."', '".$input['branch']."','".$input['fda_approved']."','$certificate','$lic'
        ,'".$input['pincode']."','".$input['permanent_state']."','".$input['city']."','$country','".$input['tax']."')";
        
        
        
       // echo $sql;
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
      else if ($_GET["type"] == "save_equpment") {
  $sql = "INSERT INTO new_equpment ( plant_id,checked_by,cleaning_date,start_time,end_time,utilence_type,done_by) VALUES 
  ( '".$_GET["plant_id"]."','".$input["checked_by"]."', 
        '".$input["cleaning_date"]."', '".$input["start_time"]."', '".$input["end_time"]."', '".$input["utilence_type"]."', '".$input["done_by"]."')";       
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
      else if ($_GET["type"] == "save_new") {
  $sql = "INSERT INTO new_checklist ( plant_id,apprasil_type,apprasil_code,department,checklist,designation) VALUES 
  ( '".$_GET["plant_id"]."','".$input["apprasil_type"]."', 
        '".$input["apprasil_code"]."', '".$input["department"]."', '".$input["checklist"]."', '".$input["designation"]."')";       
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
      else if ($_GET["type"] == "master_checklist") {
        $output = Array();
        $sql = "SELECT * FROM new_checklist  ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    
    
    else if ($_GET["type"] == "getPendingLabs") {
        $output = Array();
        $plant = $conn->real_escape_string($_GET["plant_id"]);
        $sql = "SELECT * FROM labs WHERE status='pending' AND plant_id='".$plant."' ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $branchArr = json_decode($row["branch"], true);
                if (!is_array($branchArr)) {
                    $branchArr = array();
                }
                $row["branch"] = $branchArr;
                $row["branches"] = $branchArr;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "updateLab") {
        $plant = $conn->real_escape_string($_GET["plant_id"]);
        $sql = "UPDATE labs SET status='".$_GET["status"]."', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date' WHERE id='".$_GET["id"]."' AND plant_id='".$plant."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getLabsLog") {
        $output = Array();
        $plant=$_GET['plant_id'];
        if($plant==0){
        $sql = "SELECT * FROM labs WHERE lab_name='".$_GET["lab_name"]."' ORDER BY id DESC";
        }else{
        $sql = "SELECT * FROM labs WHERE plant_id='".$_GET["plant_id"]."' ORDER BY lab_no DESC";    
        }
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
             $row["branch"] = json_decode($row["branch"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }else if ($_GET["type"] == "downloadLabsLog") {
        $_GET['filename'] = 'Lab Master'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html.='
        <h2 style="text-align:center">Lab Master</h2>
        <table border="1" cellpadding="5">
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:5%;">Sr.</td>
                    <td style="width:15%;">Lab No</td>
                    <td style="width:15%;">Lab Name</td>
                    <td style="width:15%;">Contact No</td>
                    <td style="width:20%;">Email</td>
                    <td style="width:15%;">Contact Person Name</td>
                    <td style="width:15%;">Address</td>
                </tr>';
        $i=1;
       $sql = "SELECT * FROM labs ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
            $html.='<tr nobr="true">
                        <td style="width:5%;">'.$i.'.</td>
                        <td style="width:15%;">'.$row['lab_no'].'</td>
                        <td style="width:15%;">'.$row['lab_name'].'</td>
                        <td style="width:15%;">'.$row['contact_no'].'</td>
                        <td style="width:20%;">'.$row['email'].'</td>
                        <td style="width:15%;">'.$row['contact_person'].'</td>
                        <td style="width:15%;">'.$row['address'].'</td>
                    </tr>';
                $i++;
            }
        }
        $html.="</table>";
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Lab Master.pdf', 'I');
    } else if ($_GET["type"] == "getLabsRevisionHistory") {
        $output = array();
        $plant = $conn->real_escape_string($_GET['plant_id']);
        $sql = "SELECT h.*, l.lab_name, l.lab_no FROM labs_revision_history h LEFT JOIN labs l ON l.id = h.lab_id WHERE h.plant_id = '".$plant."' ORDER BY h.id DESC LIMIT 500";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "appendLabBranches") {
        $input = $_POST;
        if (empty($input['digital_ack']) || $input['digital_ack'] !== '1') {
            echo json_encode(array("status" => "invalid", "msg" => "Digital signature confirmation required"));
            exit;
        }
        $id = intval($input['id'] ?? 0);
        $plant = $conn->real_escape_string($_GET['plant_id']);
        if ($id < 1) {
            echo json_encode(array("status" => "invalid"));
            exit;
        }
        $res = $conn->query("SELECT * FROM labs WHERE id='".$id."' AND plant_id='".$plant."' LIMIT 1");
        if (!$res || $res->num_rows == 0) {
            echo json_encode(array("status" => "notfound"));
            exit;
        }
        $row = $res->fetch_assoc();
        $arr = json_decode($row['branch'], true);
        if (!is_array($arr)) {
            $arr = array();
        }
        $new = json_decode($input['new_branches'] ?? '[]', true);
        if (!is_array($new)) {
            $new = array();
        }
        $merged = array_merge($arr, $new);
        $jmerged = $conn->real_escape_string(json_encode($merged));
        $sqlu = "UPDATE labs SET branch='".$jmerged."' WHERE id='".$id."' AND plant_id='".$plant."'";
        if ($conn->query($sqlu)) {
            $payload = $conn->real_escape_string(json_encode(array(
                "added_branches" => $new,
                "branch_count_after" => count($merged)
            )));
            $emp = $conn->real_escape_string($_GET['emp_id']);
            $dept = $conn->real_escape_string($input['sign_department'] ?? $_GET['department'] ?? '');
            $un = $conn->real_escape_string($input['sign_user_name'] ?? '');
            @$conn->query("INSERT INTO labs_revision_history (lab_id, plant_id, action_type, payload_json, emp_id, department, user_name, signed_at, digital_ack) VALUES ('".$id."', '".$plant."', 'append_branch', '".$payload."', '".$emp."', '".$dept."', '".$un."', '".$entry_date."', 1)");
            echo "{\"status\":\"success\"}";
        } else {
            echo json_encode(array("status" => $conn->error));
        }
    } else if ($_GET["type"] == "updateLabMaster") {
        $input = $_POST;
        if (empty($input['digital_ack']) || $input['digital_ack'] !== '1') {
            echo json_encode(array("status" => "invalid", "msg" => "Digital signature confirmation required"));
            exit;
        }
        $id = intval($input['id'] ?? 0);
        $plant = $conn->real_escape_string($_GET['plant_id']);
        if ($id < 1) {
            echo json_encode(array("status" => "invalid"));
            exit;
        }
        $res = $conn->query("SELECT * FROM labs WHERE id='".$id."' AND plant_id='".$plant."' LIMIT 1");
        if (!$res || $res->num_rows == 0) {
            echo json_encode(array("status" => "notfound"));
            exit;
        }
        $old = $res->fetch_assoc();
        $e = function ($s) use ($conn) {
            return $conn->real_escape_string($s ?? '');
        };
        $labKey = $e($input["lab_name"] ?? '');
        if (isset($_FILES["certificate"]) && isset($_FILES["certificate"]["tmp_name"]) && $_FILES["certificate"]["error"] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['certificate']['tmp_name'];
            $file_ext = strtolower(pathinfo($_FILES['certificate']['name'], PATHINFO_EXTENSION));
            $file_name1 = $labKey."certificate.".$file_ext;
            $certificate = $file_name1;
            move_uploaded_file($file_tmp, "../../../upload/qc/".$file_name1);
        } else {
            $certificate = $e($old['certificate'] ?? '');
        }
        $country = ($input["country"] ?? '') === 'Other'
            ? $e($input["ocountry"] ?? '')
            : $e($input["country"] ?? '');
        $taxVal = ($input["gst_applicable"] ?? '') === 'Applicable' && !empty($input["tax"]) ? $e($input["tax"]) : (($input["gst_applicable"] ?? '') === 'Not Applicable' ? '' : $e($input["tax"] ?? $old['tax'] ?? ''));
        $branchJson = $input['branch'] ?? '';
        if ($branchJson === '' || $branchJson === null) {
            $branchJson = $old['branch'] ?? '[]';
        }
        $branchEsc = $conn->real_escape_string(is_string($branchJson) ? $branchJson : json_encode($branchJson));
        $fda = $e($input['fda_lic_no'] ?? $old['fda_lic_no'] ?? '');
        $sqlu = "UPDATE labs SET lab_name='".$e($input["lab_name"] ?? '')."', contact_no='".$e($input["contact_no"] ?? '')."', email='".$e($input["email"] ?? '')."', address='".$e($input["address"] ?? '')."', contact_person='".$e($input["contact_person"] ?? '')."', fda_approved='".$e($input["fda_approved"] ?? '')."', pincode='".$e($input["pincode"] ?? '')."', permanent_state='".$e($input["permanent_state"] ?? '')."', city='".$e($input["city"] ?? '')."', country='".$country."', tax='".$taxVal."', branch='".$branchEsc."', fda_lic_no='".$fda."', certificate='".$certificate."' WHERE id='".$id."' AND plant_id='".$plant."'";
        if ($conn->query($sqlu)) {
            $snap = array(
                "before" => $old,
                "after" => array(
                    "lab_name" => $input["lab_name"] ?? '',
                    "contact_no" => $input["contact_no"] ?? '',
                    "email" => $input["email"] ?? ''
                ),
                "signature" => array(
                    "emp_id" => $_GET['emp_id'],
                    "department" => $input['sign_department'] ?? $_GET['department'] ?? '',
                    "user_name" => $input['sign_user_name'] ?? '',
                    "signed_at" => $entry_date
                )
            );
            $payload = $conn->real_escape_string(json_encode($snap));
            $emp = $e($_GET['emp_id']);
            $dept = $e($input['sign_department'] ?? $_GET['department'] ?? '');
            $un = $e($input['sign_user_name'] ?? '');
            @$conn->query("INSERT INTO labs_revision_history (lab_id, plant_id, action_type, payload_json, emp_id, department, user_name, signed_at, digital_ack) VALUES ('".$id."', '".$plant."', 'update_master', '".$payload."', '".$emp."', '".$dept."', '".$un."', '".$entry_date."', 1)");
            echo "{\"status\":\"success\"}";
        } else {
            echo json_encode(array("status" => $conn->error));
        }
    }

}

$conn->close();
?>