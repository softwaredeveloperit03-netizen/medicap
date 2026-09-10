<?php

//   ini_set('display_errors', 1);
// error_reporting(E_ALL);

    require '../db.php';
    require '../token.php';
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

if($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
	    $string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
	    $string = explode("$",$string);
	    $_GET["emp_id"] = $string[0];
	    $_GET["department"] = $string[1];
	    break;
    }

    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    
    if ($_GET["type"] == "saveComplaint") {
        $sql = "INSERT INTO complaint (user_no, client_code, complaint_nature, complaint_date, product_name, batch_no, mfg_date, exp_date,
        complaint_description, entry_by, entry_date) 
        VALUES ('".$_GET["user_no"]."','".$input["client_code"]."', '".$input["complaint_nature"]."', 
        '".$input["complaint_date"]."', '".$input["product_name"]."', '".$input["batch_no"]."', 
        '".$input["mfg_date"]."', '".$input["exp_date"]."', '".$input["complaint_description"]."', 
        '".$_GET["emp_id"]."', '$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    else if ($_GET["type"] == "saveComplaintZuma") {
        
         $sql = "INSERT INTO `complaint`(`plant_id`, `product_name`, `productCode`, `batch_no`, `mfg_date`, `exp_date`, `dateOfComplaint`, 
      `complaintReceivedFrom`, `detailsOfComplainer`, `descNatureOdComplaint`, `complaintType`, `complaintSampleReceived`, `complaintSampleQty`, 
      `immediateAction`, `status`, `entry_by`, `entry_date`)VALUES ('".$_GET["plantID"]."','".$input["product_name"]."', '".$input["productCode"]."', 
        '".$input["batch_no"]."', '".$input["mfg_date"]."', '".$input["exp_date"]."','".$input["dateOfComplaint"]."', '".$input["complaintReceivedFrom"]."', 
        '".$input["detailsOfComplainer"]."', '".$input["descNatureOdComplaint"]."','".$input["complaintType"]."','".$input["complaintSampleReceived"]."',
        '".$input["complaintSampleQty"]."','".$input["immediateAction"]."','Pending','".$_GET["emp_id"]."', '$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
    } 
    else if ($_GET["type"] == "saveComplaintZumaPlant") {
        
         $sql = "INSERT INTO `complaint`(`plant_id`, `product_name`, `productCode`, `batch_no`, `mfg_date`, `exp_date`, `dateOfComplaint`, 
      `complaintReceivedFrom`, `detailsOfComplainer`, `descNatureOdComplaint`, `complaintType`, `complaintSampleReceived`, `complaintSampleQty`, 
      `immediateAction`, `status`, `entry_by`, `entry_date`)VALUES ('".$_GET["plant_id"]."','".$input["product_name"]."', '".$input["productCode"]."', 
        '".$input["batch_no"]."', '".$input["mfg_date"]."', '".$input["exp_date"]."','".$input["dateOfComplaint"]."', '".$input["complaintReceivedFrom"]."', 
        '".$input["detailsOfComplainer"]."', '".$input["descNatureOdComplaint"]."','".$input["complaintType"]."','".$input["complaintSampleReceived"]."',
        '".$input["complaintSampleQty"]."','".$input["immediateAction"]."','Pending','".$_GET["emp_id"]."', '$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
    } 
    else if ($_GET["type"] == "saveForQaUse") {
        
         $sql = "UPDATE `complaint` SET `marketComplentCategory` = '".$input["marketComplentCategory"]."',`histRepetition` = '".$input["histRepetition"]."',
         `prevRefNo` = '".$input["prevRefNo"]."', status = 'QA_HEAD_RECOMO',   qaUseBy = '".$_GET["emp_id"]."', qaUseOn = '$entry_date' where id  = '".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
    } 
    else if ($_GET["type"] == "saveComplaintRemark") {
        
         $sql = "UPDATE `complaint` SET `Remark` = '".$input["remark"]."', status = 'TO_QA_HEAD',   qaHeadBy = '".$_GET["emp_id"]."', qaHeadOn = '$entry_date' where id  = '".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
    } 
      else if ($_GET["type"] == "saveComplaintForAprrvl") {
        
         $sql = "UPDATE `complaint` SET  status = 'TO_LOG',   qaHeadAppvlBy = '".$_GET["emp_id"]."', qaHeadAppvlOn = '$entry_date' where id  = '".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
    } 
    
    
      else if ($_GET["type"] == "saveComplaintConclusion") {
   

        $input = $_POST;
        $sid = $_GET["id"];
        $pid = $_GET["plant_id"];
        $name = 'Investigation';
        if (isset($_FILES["selectedFile"])) {
            $file_tmp = $_FILES['selectedFile']['tmp_name'];
            $file_ext = strtolower(end(explode('.', $_FILES['selectedFile']['name'])));
            $file_name = $name.$sid.$pid.".".$file_ext;
            move_uploaded_file($file_tmp, "../../../upload/complaint/" . $file_name);}
             $entry_date = date('Y-m-d H:i:s');
             $sql = "UPDATE `complaint` SET 
            `ComplaintBelongs` = '".$input["ComplaintBelongs"]."',
            `CorrectiveAction` = '".$input["CorrectiveAction"]."',
            `InvestigationNo` = '".$input["InvestigationNo"]."',
            `InvestigationSummary` = '".$input["InvestigationSummary"]."',
            `MarketCompNO` = '".$input["MarketCompNO"]."',
            `PackDetails` = '".$input["PackDetails"]."',
            `PreventiveAction` = '".$input["PreventiveAction"]."',
            `RootCause` = '".$input["RootCause"]."',
            `SummaryAndConclusion` = '".$input["SummaryAndConclusion"]."',
            `SummaryConclusion` = '".$input["SummaryConclusion"]."',
            `SummaryReport` = '".$input["SummaryReport"]."',
            `complaintSampleRepeate` = '".$input["complaintSampleRepeate"]."',
            `complaintSampleRepeated` = '".$input["complaintSampleRepeated"]."',
            `selectedFile` = '$file_name',
             status = 'To_Prod_Head',   
             InvestigationBy = '".$_GET["emp_id"]."', 
             InvestigationOn = '$entry_date' 
             where id  = '".$_GET["id"]."'";
            if ($conn->query($sql)) {
                echo "{\"status\":\"success\"}";
            } else {
                echo "{\"status\":\"".$conn->error."\"}";
            }
        
    } 
    
    else if ($_GET["type"] == "saveQaHeadRecc") {
        
         $sql = "UPDATE `complaint` SET `qaHeadRecc` = '".$input["qaHeadRecc"]."', status = 'For_Conclusion', 
         qaHeadReccBy = '".$_GET["emp_id"]."', qaHeadReccOn = '$entry_date' where id  = '".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
    } 
    else if ($_GET["type"] == "getPendingComplaints") {
        $output = Array();
        $sql = "SELECT c.*, c1.LglNm, c1.TrdNm,
                IFNULL(NULLIF(c1.LglNm,''), c1.TrdNm) AS company,
                IFNULL(NULLIF(c1.LglNm,''), c1.TrdNm) AS client_name
                FROM complaint c LEFT JOIN client c1 ON c.client_code=c1.client_code
                WHERE c.user_no='".$_GET["user_no"]."' AND c.status='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getComplaints") {
        $output = Array();
        $sql = "SELECT c.*, c1.LglNm, c1.TrdNm,
                IFNULL(NULLIF(c1.LglNm,''), c1.TrdNm) AS company,
                IFNULL(NULLIF(c1.LglNm,''), c1.TrdNm) AS client_name
                FROM complaint c LEFT JOIN client c1 ON c.client_code=c1.client_code
                WHERE c.user_no='".$_GET["user_no"]."' GROUP BY c.id ORDER BY c.id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "updateComplaint") {
        $sql = "INSERT INTO complaint_action (user_no, complaint_no, action_no, action, status, entry_by, entry_date) VALUES ('".$_GET["user_no"]."','".$_GET["complaint_no"]."', '$action_no', '".$input["action"]."', '".$input["status"]."', '".$_GET["emp_id"]."', '$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
            $sql = "UPDATE complaint SET status='".$input["status"]."' WHERE id='".$_GET["complaint_no"]."'";
            $conn->query($sql);
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getInprocessComplaints") {
        $output = Array();
           $sql = "SELECT c.*, c1.LglNm, c1.TrdNm,
                IFNULL(NULLIF(c1.LglNm,''), c1.TrdNm) AS company,
                IFNULL(NULLIF(c1.LglNm,''), c1.TrdNm) AS client_name
                FROM complaint c LEFT JOIN client c1 ON c.client_code=c1.client_code 
        WHERE c.user_no='".$_GET["user_no"]."' AND c.status='inprocess' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = Array();
                $sql1 = "SELECT * FROM complaint_action WHERE complaint_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row["actions"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getComplaintsLog") {
        $output = Array();
        $sql = "SELECT c.*, c1.LglNm, c1.TrdNm,
                IFNULL(NULLIF(c1.LglNm,''), c1.TrdNm) AS company,
                IFNULL(NULLIF(c1.LglNm,''), c1.TrdNm) AS client_name
                FROM complaint c LEFT JOIN client c1 ON c.client_code = c1.client_code 
        WHERE c.user_no='".$_GET["user_no"]."' AND c.client_code LIKE '%".$_GET["client_code"]."%'"; 
       
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = Array();
                $sql1 = "SELECT * FROM complaint_action WHERE complaint_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row["actions"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getComplaintsLogZuma") {
        $output = Array();
        $sql = "SELECT c.*,p.plant_name FROM complaint c left join plant p ON c.plant_id = p.plant_id "; 
       
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getComplaintForQaUse") {
        $output = Array();
         $sql = "SELECT * FROM complaint where status = 'Pending' AND plant_id = '".$_GET['plant_id']."'"; 
       
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getComplaintForQAHeadRecc") {
        $output = Array();
         $sql = "SELECT * FROM complaint where status = 'QA_HEAD_RECOMO' AND plant_id = '".$_GET['plant_id']."'"; 
       
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 
            $sql1 = "SELECT 
            CONCAT(e1.firstname, ' ', e1.lastname) AS entryByName, 
            CONCAT(e2.firstname, ' ', e2.lastname) AS qaUseByName
            FROM 
                employee e1
            LEFT JOIN 
                employee e2 ON e2.emp_id = '".$row["qaUseBy"]."' AND e2.plant_id = '".$_GET["plant_id"]."'
            WHERE 
                e1.emp_id = '".$row["entry_by"]."' AND e1.plant_id = '".$_GET["plant_id"]."' ";
            
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["entryByName"] =  $row1["entryByName"];
                        $row["qaUseByName"] =  $row1["qaUseByName"];
                    }
                }
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getComplaintForConclusion") {
        $output = Array();
         $sql = "SELECT * FROM complaint where status = 'For_Conclusion' AND plant_id = '".$_GET['plant_id']."'"; 
       
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 
            $sql1 = "SELECT 
            CONCAT(e1.firstname, ' ', e1.lastname) AS entryByName, 
            CONCAT(e2.firstname, ' ', e2.lastname) AS qaUseByName,
            CONCAT(e3.firstname, ' ', e3.lastname) AS qaHeadReccByName
            FROM 
                employee e1
            LEFT JOIN 
                employee e2 ON e2.emp_id = '".$row["qaUseBy"]."' AND e2.plant_id = '".$_GET["plant_id"]."'
            LEFT JOIN 
                employee e3 ON e3.emp_id = '".$row["qaHeadReccBy"]."' AND e3.plant_id = '".$_GET["plant_id"]."'
            WHERE 
                e1.emp_id = '".$row["entry_by"]."' AND e1.plant_id = '".$_GET["plant_id"]."' ";
            
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["entryByName"] =  $row1["entryByName"];
                        $row["qaUseByName"] =  $row1["qaUseByName"];
                        $row["qaHeadReccByName"] =  $row1["qaHeadReccByName"];
                    }
                }
                 
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }

    else if ($_GET["type"] == "getComplaintForProdRemark") {
        $output = Array();
         $sql = "SELECT * FROM complaint where status = 'To_Prod_Head' AND plant_id = '".$_GET['plant_id']."'"; 
       
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 
        //   $sql1 = "SELECT 
        //     CONCAT(e1.firstname, ' ', e1.lastname) AS entryByName, 
        //     CONCAT(e2.firstname, ' ', e2.lastname) AS qaUseByName,
        //     CONCAT(e3.firstname, ' ', e3.lastname) AS qaHeadReccByName,
        //     CONCAT(e4.firstname, ' ', e4.lastname) AS qaConclusionByName
        //     FROM 
        //         employee e1
        //     LEFT JOIN 
        //         employee e2 ON e2.emp_id = '".$row["qaUseBy"]."' AND e2.plant_id = '".$_GET["plant_id"]."'
        //     LEFT JOIN 
        //         employee e3 ON e3.emp_id = '".$row["qaHeadReccBy"]."' AND e3.plant_id = '".$_GET["plant_id"]."'
        //     LEFT JOIN 
        //         employee e4 ON e4.emp_id = '".$row["qaConclusionByName"]."' AND e4.plant_id = '".$_GET["plant_id"]."'
        //     WHERE 
        //         e1.emp_id = '".$row["entry_by"]."' AND e1.plant_id = '".$_GET["plant_id"]."'";

        //         $result1 = $conn->query($sql1);
        //         if ($result1->num_rows > 0) {
        //             while ($row1 = $result1->fetch_assoc()) {
        //                 $row["entryByName"] =  $row1["entryByName"];
        //                 $row["qaUseByName"] =  $row1["qaUseByName"];
        //                 $row["qaHeadReccByName"] =  $row1["qaHeadReccByName"];
        //                 $row["qaConclusionByName"] =  $row1["qaConclusionByName"];
        //             }
        //         }
                 
                
        //         $output[] = $row;
        // The original query logic remains unchanged
            $sql1 = "SELECT 
                        CONCAT(e1.firstname, ' ', e1.lastname) AS entryByName, 
                        CONCAT(e2.firstname, ' ', e2.lastname) AS qaUseByName,
                        CONCAT(e3.firstname, ' ', e3.lastname) AS qaHeadReccByName,
                        CONCAT(e4.firstname, ' ', e4.lastname) AS qaConclusionByName
                      FROM 
                        employee e1
                      LEFT JOIN 
                        employee e2 ON e2.emp_id = '".$row["qaUseBy"]."' AND e2.plant_id = '".$_GET["plant_id"]."'
                      LEFT JOIN 
                        employee e3 ON e3.emp_id = '".$row["qaHeadReccBy"]."' AND e3.plant_id = '".$_GET["plant_id"]."'
                      LEFT JOIN 
                        employee e4 ON e4.emp_id = '".$row["InvestigationBy"]."' AND e4.plant_id = '".$_GET["plant_id"]."'
                      WHERE 
                        e1.emp_id = '".$row["entry_by"]."' AND e1.plant_id = '".$_GET["plant_id"]."'";
            
            // Execute the query
            $result1 = $conn->query($sql1);
            
            // If the query returns results, fetch the data
            if ($result1->num_rows > 0) {
                // Since we expect one row, we can directly fetch it
                $row1 = $result1->fetch_assoc();
                
                // Assign values from $row1 to $row
                $row["entryByName"] = $row1["entryByName"];
                $row["qaUseByName"] = $row1["qaUseByName"];
                $row["qaHeadReccByName"] = $row1["qaHeadReccByName"];
                $row["qaConclusionByName"] = $row1["qaConclusionByName"];
            }
            
            // Add the row data to the output array
            $output[] = $row;

            }
        }
        echo json_encode($output);
    }
        else if ($_GET["type"] == "getComplaintForAprrvl") {
        $output = Array();
         $sql = "SELECT * FROM complaint where status = 'TO_QA_HEAD' AND plant_id = '".$_GET['plant_id']."'"; 
       
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 
            $sql1 = "SELECT 
            CONCAT(e1.firstname, ' ', e1.lastname) AS entryByName, 
            CONCAT(e2.firstname, ' ', e2.lastname) AS qaUseByName,
            CONCAT(e3.firstname, ' ', e3.lastname) AS qaHeadReccByName,
            CONCAT(e4.firstname, ' ', e4.lastname) AS qaConclusionByName,
            CONCAT(e5.firstname, ' ', e5.lastname) AS qaRemarkByName
            FROM 
                employee e1
            LEFT JOIN 
                employee e2 ON e2.emp_id = '".$row["qaUseBy"]."' AND e2.plant_id = '".$_GET["plant_id"]."'
            LEFT JOIN 
                employee e3 ON e3.emp_id = '".$row["qaHeadReccBy"]."' AND e3.plant_id = '".$_GET["plant_id"]."'
                LEFT JOIN 
                employee e4 ON e4.emp_id = '".$row["InvestigationBy"]."' AND e3.plant_id = '".$_GET["plant_id"]."'
                LEFT JOIN 
                employee e5 ON e5.emp_id = '".$row["qaHeadBy"]."' AND e3.plant_id = '".$_GET["plant_id"]."'
            WHERE 
                e1.emp_id = '".$row["entry_by"]."' AND e1.plant_id = '".$_GET["plant_id"]."' ";
            
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["entryByName"] =  $row1["entryByName"];
                        $row["qaUseByName"] =  $row1["qaUseByName"];
                        $row["qaHeadReccByName"] =  $row1["qaHeadReccByName"];
                         $row["qaConclusionByName"] =  $row1["qaConclusionByName"];
                           $row["qaRemarkByName"] =  $row1["qaRemarkByName"];
                    }
                }
                 
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
        else if ($_GET["type"] == "getComplaintLog") {
        $output = Array();
         $sql = "SELECT * FROM complaint where status = 'TO_LOG' AND plant_id = '".$_GET['plant_id']."'"; 
       
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 
            $sql1 = "SELECT 
            CONCAT(e1.firstname, ' ', e1.lastname) AS entryByName, 
            CONCAT(e2.firstname, ' ', e2.lastname) AS qaUseByName,
            CONCAT(e3.firstname, ' ', e3.lastname) AS qaHeadReccByName,
            CONCAT(e4.firstname, ' ', e4.lastname) AS qaConclusionByName,
            CONCAT(e5.firstname, ' ', e5.lastname) AS qaRemarkByName,
            CONCAT(e6.firstname, ' ', e6.lastname) AS qaHeadByName
            FROM 
                employee e1
            LEFT JOIN 
                employee e2 ON e2.emp_id = '".$row["qaUseBy"]."' AND e2.plant_id = '".$_GET["plant_id"]."'
            LEFT JOIN 
                employee e3 ON e3.emp_id = '".$row["qaHeadReccBy"]."' AND e3.plant_id = '".$_GET["plant_id"]."'
                LEFT JOIN 
                employee e4 ON e4.emp_id = '".$row["InvestigationBy"]."' AND e3.plant_id = '".$_GET["plant_id"]."'
                LEFT JOIN 
                employee e5 ON e5.emp_id = '".$row["qaHeadBy"]."' AND e3.plant_id = '".$_GET["plant_id"]."'
                 LEFT JOIN 
                employee e6 ON e6.emp_id = '".$row["qaHeadAppvlBy"]."' AND e3.plant_id = '".$_GET["plant_id"]."'
                
            WHERE 
                e1.emp_id = '".$row["entry_by"]."' AND e1.plant_id = '".$_GET["plant_id"]."' ";
            
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["entryByName"] =  $row1["entryByName"];
                        $row["qaUseByName"] =  $row1["qaUseByName"];
                        $row["qaHeadReccByName"] =  $row1["qaHeadReccByName"];
                         $row["qaConclusionByName"] =  $row1["qaConclusionByName"];
                           $row["qaRemarkByName"] =  $row1["qaRemarkByName"];
                            $row["qaHeadByName"] =  $row1["qaHeadByName"];
                    }
                }
                 
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }

}

$conn->close();
?>