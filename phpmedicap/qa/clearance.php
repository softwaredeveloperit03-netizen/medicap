<?php

// ini_set('display_errors', 1);
// error_reporting(E_ALL);
    require '../db.php';
    require '../token.php';
    require '../tcpdf/tcpdf.php';
    header('Access-Control-Allow-Origin: *');
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


if ($_GET["type"] == "getPendingDepartments") {
    $output = Array();
     $sql = "SELECT * FROM department WHERE status='active'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output1 = Array();
            $sql1 = "SELECT * FROM section WHERE department='".$row["department_name"]."' AND checkpoints_status='pending'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $output1[] = $row1;
                }
                $row["sections"] = $output1;
                $output[] = $row;
            }
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getPendingAreaChecklists") {
    $output = Array();
    $sql = "SELECT * FROM section WHERE checkpoints_status IN ('pending', 'reject')";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
}
else if ($_GET["type"] == "saveCheckpoints") {
    $sql = "UPDATE section SET checkpoints='".json_encode($input["checklist"])."', checkpoints_status='inprocess' WHERE section_code='".$input["section"]."' AND department='".$input["department"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
}
else if ($_GET["type"] == "updatechecklist") {
    $sql = "UPDATE section SET check_status='".$_GET["status"]."'  WHERE section_code='".$_GET["area_code"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
}
else if ($_GET["type"] == "getAreaChecklists") {
    $output = Array();
    $sql = "SELECT * FROM section WHERE checkpoints_status !='pending' ";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row["checkpoints"] = json_decode($row["checkpoints"]);
            $output[] = $row;
        }
    }
    echo json_encode($output);
} 
else if ($_GET["type"] == "getAreaChecklistslog") {
    $output = Array();
    $sql = "SELECT * FROM section WHERE checkpoints_status ='approve' ";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row["checkpoints"] = json_decode($row["checkpoints"]);
            $output[] = $row;
        }
    }
    echo json_encode($output);
} 

else if ($_GET["type"] == "getInprocessAreaChecklists") {
    $output = Array();
    $sql = "SELECT * FROM section WHERE checkpoints_status ='inprocess'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row["checkpoints"] = json_decode($row["checkpoints"]);
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "updateAreaChecklist") {
    $sql = "UPDATE section SET checkpoints_status= '".$_GET["status"]."' WHERE id='".$_GET["id"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "getQCSamplingCheckpoints") {
    $output = Array();
    $sql = "SELECT * FROM section WHERE department='Quality Control' AND section_name='Sampling' AND checkpoints_status='approve'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output = json_decode($row["checkpoints"]);
            break;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"]=="getPendingLineClearance_old") {
    $sql = "SELECT * FROM lineclearance WHERE status='pending'";
    $result = $conn->query($sql);
    $output = Array();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["material_name"] = $row1["material_name"];
                    $row["material_code"] = $row1["material_code"];
                    $row["grade"] = $row1["grade"];
                }
            }
            $row["checkpoints"] = json_decode($row["checkpoints"]);
            $output[] = $row;
        }
    }
    echo json_encode($output);
} 
else if ($_GET["type"]=="getPendingLineClearance") {
       $sql = "SELECT a.id,d.product_name,a.work_order_id,a.clearance_no,a.checkpoints, b.firstname as request_by,a.batch_no,
            a.request_date as request_date,a.status ,b.department FROM lineclearance a 
            left join employee b on a.entry_by = b.emp_id and a.plant_id = b.plant_id left  join product d on 
            a.product_code=d.product_code order by 1 desc";
            
            
           // WHERE a.status='pending' order by 1 desc";
    $result = $conn->query($sql);
    $output = Array();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["material_name"] = $row1["material_name"];
                    $row["material_code"] = $row1["material_code"];
                    $row["grade"] = $row1["grade"];
                }
            }
            $row["checkpoints"] = json_decode($row["checkpoints"]);
            $output[] = $row;
        }
    }
    echo json_encode($output);
}

else if ($_GET["type"]=="updateLineClearance") {
     $currentTimestamp = date("Y-m-d H:i:s");

   $sql = "UPDATE lineclearance SET status='".$_GET["status"]."', 
            qc_checkpoints = '".json_encode($input)."',
            accept_remark='".$_GET["remark"]."' WHERE id='".$_GET["id"]."'";
 
    if ($conn->query($sql)) {
        if($_GET["material_type"] == 'Raw Material'){
             $sql = "update mfg_work_order_hdr  set 
                    rm_qa_dislc_status='".$_GET["status"]."', 
                    rm_qa_dislc_by = '".$_GET["emp_id"]."',
                    rm_qa_dislc_date='".$entry_date."'
                    where id = '".$_GET["work_order_id"]."' ";
                    
                    $conn->query($sql);
        }else{
            
           $sql = " update mfg_work_order_hdr  set 
                    pm_qa_dislc_status='".$_GET["status"]."', 
                    pm_qa_dislc_by = '".$_GET["emp_id"]."',
                    pm_qa_dislc_date='".$entry_date."'
                    where id = '".$_GET["work_order_id"]."' ";
                    
                    $conn->query($sql);
        }
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"]=="getLineClearanceLog") {
    $sql = "SELECT * FROM lineclearance WHERE user_no='".$_GET["user_no"]."' AND department LIKE '%".$_GET["department_name"]."%' AND section LIKE '%".$_GET["section"]."%' AND DATE(entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
    $result = $conn->query($sql);
    $output = Array();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["material_name"] = $row1["material_name"];
                    $row["material_code"] = $row1["material_code"];
                    $row["grade"] = $row1["grade"];
                }
            }
            $row["checkpoints"] = json_decode($row["checkpoints"]);
            $output[] = $row;
        }
    }
    echo json_encode($output);
}else if ($_GET["type"] == "downloadLineClearanceLog") {
        $_GET['filename'] = 'Clearance Log'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='<table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 5%; text-align:center;">Sr.</td>
                    <td style="width: 15%; text-align:center;">Date</td>
                    <td style="width: 15%; text-align:center;">Depatment</td>
                    <td style="width: 15%; text-align:center;">Section</td>
                    <td style="width: 15%; text-align:center;">Material Code</td>
                    <td style="width: 20%; text-align:center;">Line clearance given at time</td>
                    <td style="width: 15%; text-align:center;">Remark</td>
                </tr>
            </thead>';
    $sql = "SELECT * FROM lineclearance WHERE user_no='".$_GET["user_no"]."' AND department LIKE '%".$_GET["department_name"]."%' 
    AND section LIKE '%".$_GET["section"]."%' AND DATE(entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
    $result = $conn->query($sql);
    
    $i=1;
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            
            $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["material_name"] = $row1["material_name"];
                    $row["material_code"] = $row1["material_code"];
                    $row["grade"] = $row1["grade"];
                }
            }
           
               
                
                $html.='<tr nobr="true">
                        <td style="width: 5%; text-align:center;">'.$i.'</td>
                        <td style="width: 15%; text-align:center;">'.date('d-m-Y',strtotime($row['entry_date'])).'</td>
                        <td style="width: 15%; text-align:center;">'.$row['department'].'</td>
                        <td style="width: 15%; text-align:center;">'.$row['section'].'</td>
                        <td style="width: 15%; text-align:center;">'.$row1['material_code'].'</td>
                        <td style="width: 20%; text-align:center;">'.date('d-m-Y',strtotime($row['request_date'])).'</td>
                        <td style="width: 15%; text-align:center;">'.$row['accept_remark'].'</td>
                    </tr>';
                $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Clearance Log.pdf', 'I');
    }


} else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
?>