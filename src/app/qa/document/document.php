<?php
// ini_set('display_errors', 1);
//  error_reporting(E_ALL);
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

function ensureDocumentRevisionTable($conn) {
    $sql = "CREATE TABLE IF NOT EXISTS doc_management_revision_log (
        id INT(11) NOT NULL AUTO_INCREMENT,
        plant_id VARCHAR(100) NOT NULL,
        document_id INT(11) NOT NULL,
        department_name VARCHAR(255) DEFAULT NULL,
        document_name VARCHAR(255) DEFAULT NULL,
        document_no VARCHAR(255) DEFAULT NULL,
        document_type VARCHAR(255) DEFAULT NULL,
        previous_version VARCHAR(100) DEFAULT NULL,
        revised_version VARCHAR(100) DEFAULT NULL,
        change_control_no VARCHAR(100) DEFAULT NULL,
        remarks TEXT,
        upload VARCHAR(255) DEFAULT NULL,
        effective_date DATE DEFAULT NULL,
        next_review_date DATE DEFAULT NULL,
        entry_by VARCHAR(100) DEFAULT NULL,
        entry_date DATETIME DEFAULT NULL,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    return $conn->query($sql);
}


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
} else if ($_GET["type"] == "saveCheckpoints") {
    $sql = "UPDATE section SET checkpoints='".json_encode($input["checklist"])."', checkpoints_status='inprocess' WHERE section_code='".$input["section"]."' AND department='".$input["department"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
}
else if ($_GET["type"] == "getAreaChecklists") {
    $output = Array();
    $sql = "SELECT * FROM section WHERE checkpoints_status !='pending'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row["checkpoints"] = json_decode($row["checkpoints"]);
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getInprocessAreaChecklists") {
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
}
else if ($_GET["type"] == "saveAsset") {
        $sql = "INSERT INTO restation_save (plant_id,format_no,copies_req,revistion_date,document_type,document_name,department) VALUES 
        ('".$_GET['plant_id']."','".$input["format_no"]."', '".$input["copies_req"]."', '".$input["revistion_date"]."', '".$input["document_type"]."', '".$input["document_name"]."', '".$input["department"]."' )";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    
}


else if ($_GET["type"] == "SaveDocIndex") {
    
    $input = $_POST;
     
     $target_dir = "../../../upload/documentIndex/";
    
        $file_name = "";
        
        
        $ver = $input["document_version"];
        $nm = $input["document_name"];
        $pl = $_GET['plant_id'];
        
        if(isset($_FILES["document"]["name"])){
            $target_file = $target_dir.$ver.$nm.$pl.$_FILES["document"]["name"];
            $file_name = $ver.$nm.$pl.basename($_FILES["document"]["name"]);
        }
     
    
        $sql = "INSERT INTO doc_management (plant_id,department_name,document_name,document_type,document_no,document_version,
        effective_date,next_review_date,upload) VALUES('".$_GET['plant_id']."','".$input["department_name"]."','".$input["document_name"]."', '".$input["document_type"]."',
        '".$input["document_no"]."', '".$input["document_version"]."', '".$input["effective_date"]."' ,
        '".$input["next_review_date"]."', '$file_name' )";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
            move_uploaded_file($_FILES["document"]["tmp_name"], $target_file);

        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    
}

else if ($_GET["type"] == "SaveDocIndexForTrainingMaster") {
    
    $input = $_POST;
     
     $target_dir = "../../../upload/documentIndex/";
    
        $file_name = "";
        
        
        $ver = $input["department_name"];
        $nm = $input["document_name"];
        $pl = $_GET['plant_id'];
        
        if(isset($_FILES["document"]["name"])){
            $target_file = $target_dir.$ver.$nm.$pl.$_FILES["document"]["name"];
            $file_name = $ver.$nm.$pl.basename($_FILES["document"]["name"]);
        }
      
    
        $sql = "INSERT INTO content_master (plant_id,department_name,document_name,entry_by,entry_date,upload) 
        VALUES('".$_GET['plant_id']."','".$input["department_name"]."','".$input["document_name"]."', '".$_GET["emp_id"]."' ,
        '$entry_date', '$file_name' )";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
            move_uploaded_file($_FILES["document"]["tmp_name"], $target_file);

        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    
}



else if ($_GET["type"]=="getContentMaster") {
    $sql = "SELECT * FROM content_master WHERE plant_id = '".$_GET['plant_id']."'";
    $result = $conn->query($sql);
    $output = Array();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
} 
else if ($_GET["type"]=="getContentMasterForDept") {
    $sql = "SELECT * FROM content_master WHERE plant_id = '".$_GET['plant_id']."' AND department_name = '".$_GET['deptName']."'";
    $result = $conn->query($sql);
    $output = Array();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
} 
else if ($_GET["type"]=="getDocuments") {
    $sql = "SELECT * FROM doc_management WHERE plant_id = '".$_GET['plant_id']."'";
    $result = $conn->query($sql);
    $output = Array();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
} 

else if ($_GET["type"]=="getDocumentsByDept") {
    $sql = "SELECT * FROM doc_management WHERE plant_id = '".$_GET['plant_id']."' AND department_name = '".$_GET['deptName']."'";
    $result = $conn->query($sql);
    $output = Array();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
} 
else if ($_GET["type"]=="getDocumentIndexLog") {
    ensureDocumentRevisionTable($conn);
    $sql = "SELECT dm.*,
            (
                SELECT COUNT(*)
                FROM doc_management_revision_log dr
                WHERE dr.document_id = dm.id
            ) AS revision_count
            FROM doc_management dm
            WHERE dm.plant_id = '".$_GET['plant_id']."'
            ORDER BY dm.id DESC";
    $result = $conn->query($sql);
    $output = Array();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
}
else if ($_GET["type"]=="saveDocumentRevision") {
    ensureDocumentRevisionTable($conn);
    $input = $_POST;
    $document_id = intval($_GET["document_id"]);
    $sql = "SELECT * FROM doc_management WHERE id='".$document_id."' AND plant_id='".$_GET['plant_id']."'";
    $result = $conn->query($sql);
    if ($result->num_rows == 0) {
        echo "{\"status\":\"Document not found\"}";
    } else {
        $document = $result->fetch_assoc();
        $target_dir = "../../../upload/documentIndex/";
        $revised_file_name = $document["upload"];
        if(isset($_FILES["document"]["name"]) && $_FILES["document"]["name"] != ""){
            $revised_file_name = "REV".$document_id.time().basename($_FILES["document"]["name"]);
            $target_file = $target_dir.$revised_file_name;
        }

        $revised_version = isset($input["revised_version"]) ? $input["revised_version"] : $document["document_version"];
        $effective_date = isset($input["effective_date"]) ? $input["effective_date"] : $document["effective_date"];
        $next_review_date = isset($input["next_review_date"]) ? $input["next_review_date"] : $document["next_review_date"];

        $sql1 = "INSERT INTO doc_management_revision_log (
                plant_id, document_id, department_name, document_name, document_no, document_type,
                previous_version, revised_version, change_control_no, remarks, upload,
                effective_date, next_review_date, entry_by, entry_date
            ) VALUES (
                '".$_GET['plant_id']."', '".$document_id."', '".$document["department_name"]."', '".$document["document_name"]."',
                '".$document["document_no"]."', '".$document["document_type"]."', '".$document["document_version"]."',
                '".$revised_version."', '".$input["change_control_no"]."', '".$input["remarks"]."',
                '".$revised_file_name."', '".$effective_date."', '".$next_review_date."', '".$_GET["emp_id"]."', '".$entry_date."'
            )";

        if ($conn->query($sql1)) {
            $sql2 = "UPDATE doc_management SET
                    document_version = '".$revised_version."',
                    effective_date = '".$effective_date."',
                    next_review_date = '".$next_review_date."',
                    upload = '".$revised_file_name."'
                    WHERE id='".$document_id."' AND plant_id='".$_GET['plant_id']."'";
            if ($conn->query($sql2)) {
                if(isset($_FILES["document"]["name"]) && $_FILES["document"]["name"] != ""){
                    move_uploaded_file($_FILES["document"]["tmp_name"], $target_file);
                }
                echo "{\"status\":\"success\"}";
            } else {
                echo "{\"status\":\"".$conn->error."\"}";
            }
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
}
else if ($_GET["type"]=="getDocumentRevisionHistory") {
    ensureDocumentRevisionTable($conn);
    $sql = "SELECT * FROM doc_management_revision_log WHERE plant_id='".$_GET['plant_id']."'";
    if (isset($_GET["document_id"]) && $_GET["document_id"] != "") {
        $sql .= " AND document_id='".intval($_GET["document_id"])."'";
    }
    $sql .= " ORDER BY id DESC";
    $result = $conn->query($sql);
    $output = Array();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
}
else if ($_GET["type"]=="getPendingLineClearance") {
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


else if ($_GET["type"]=="updateLineClearance") {
    
    
    
    $sql = "UPDATE lineclearance SET status='".$_GET["action"]."', accept_remark='".$_GET["remark"]."' WHERE id='".$_GET["id"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} 



else if ($_GET["type"]=="getLineClearanceLog") {
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
}else if ($_GET["type"] == "downloadDeptRequisitions") {
        $_GET['filename'] = ' Document Requisition'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp.php");
        $html= "";

        $html.='<table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 5%; text-align:center;">Sr.</td>
                    <td style="width: 15%; text-align:center;">Document Name</td>
                    <td style="width: 10%; text-align:center;">Document Type</td>
                    <td style="width: 10%; text-align:center;">Format No</td>
                    <td style="width: 15%; text-align:center;">Required Copies	</td>
                    <td style="width: 10%; text-align:center;">Revision No.</td>
                    <td style="width: 15%; text-align:center;">Effective Date</td>
                    <td style="width: 10%; text-align:center;">Request By</td>
                    <td style="width: 10%; text-align:center;">Request Date</td>
                </tr>
            </thead>';
    $sql = "SELECT * FROM lineclearance WHERE user_no='".$_GET["user_no"]."' AND department LIKE '%".$_GET["department_name"]."%' AND section LIKE '%".$_GET["section"]."%' AND DATE(entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
    $result = $conn->query($sql);
    $output = Array();
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
            $row["checkpoints"] = json_decode($row["checkpoints"]);
            $output[] = $row;
               
                
                $html.='<tr nobr="true">
                        <td style="width: 5%; text-align:center;">'.$i.'.</td>
                        <td style="width: 15%; text-align:center;">'.$row[''].'</td>
                        <td style="width: 10%; text-align:center;">'.$row[''].'</td>
                        <td style="width: 10%; text-align:center;">'.$row[''].'</td>
                        <td style="width: 15%; text-align:center;">'.$row[''].'</td>
                        <td style="width: 10%; text-align:center;">'.$row[''].'</td>
                        <td style="width: 15%; text-align:center;">'.$row[''].'</td>
                        <td style="width: 10%; text-align:center;">'.$row[''].'</td>
                        <td style="width: 10%; text-align:center;">'.$row[''].'</td>
                       
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