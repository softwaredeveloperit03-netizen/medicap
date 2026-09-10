
<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

    require '../../db.php';
    require '../../token.php';
     require '../../tcpdf/tcpdf.php';
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
    $myfile = file_put_contents('../../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    
     if ($_GET["type"] == "saveAllocations") {
        $sql = "INSERT INTO testing (material_code,grn_no,material_grade,ar_no,status) 
        VALUES('".$input["material_code"]."','".$input["grn_no"]."','".$input["material_grade"]."','".$input["ar_no"]."','".$input["status"]."' )";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    
   
   }else if ($_GET["type"] == "getPendingAllocations") {
        $output = Array();
        $sql = "SELECT t.*, m.material_name, m.grade, s.specification_no FROM testing t LEFT JOIN master_material m ON t.material_code=m.material_code LEFT JOIN specification s ON t.material_code=s.material_code WHERE t.status='pending' order by 1 desc ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = Array();
                $sql2 = "SELECT * FROM spec_tests WHERE specification_no in(SELECT specification_no FROM specification WHERE material_code='".$row["material_code"]."')";
                
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $output1[] = $row2;
                    }
                }
                $row["spec_tests"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
        
 } 
 
else if ($_GET["type"] == "getCheckedTestingReport") {
       $sql = "SELECT t.*, m.material_type, m.material_subtype, m.material_name, m.grade FROM testing t LEFT JOIN material m ON t.material_code=m.material_code";
        $output = Array();
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $sql1 = "SELECT * FROM testing_tests WHERE user_no='".$_GET["user_no"]."' AND testing_no='".$row["testing_no"]."'";
                $output1 = Array();
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                if (count($output1) > 0) {
                    $row["tests"] = $output1;
                    $output[] = $row;
                }
            }
        }
        echo json_encode($output);
    }else if ($_GET["type"] == "getTestingReport") {
        
        //   $q= "SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('".  $row['grade']."')";
        //      $resQ = $conn->query($q);
        //       $prodLatest = $resQ->fetch_assoc(); 
        //      $row['gradeName'] = $prodLatest['gradeName']; 

          $sql = "SELECT t.*, m.material_type, m.material_subtype, m.material_name, m.grade FROM testing t 
        LEFT JOIN material m ON t.material_code=m.material_code WHERE t.status='Approved' AND m.material_type='Packing Material' ";
        $output = Array();
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $sql1 = "SELECT * FROM testing_tests WHERE user_no='".$_GET["user_no"]."' AND testing_no='".$row["testing_no"]."'";
                $output1 = Array();
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                if (count($output1) > 0) {
                    $row["spec_tests"] = $output1;
                    $output[] = $row;
                }
            }
        }
        echo json_encode($output);
    }else if ($_GET["type"] == "saveTestingForm") {
        error_reporting(0);
        $sql = "UPDATE testing_tests SET result='".$input["result"]."', remark='".$input["remark"]."', start_time='".$input["start_time"]."', end_time='".$input["end_time"]."', status='active', descriptions='".json_encode($input["descriptions"])."' WHERE id='".$input["test_no"]."'";
        if ($conn->query($sql) == TRUE) {
            $descriptions = $input["descriptions"];
            for ($i = 0; $i < count($descriptions); $i++) {
                $data = $descriptions[$i];
                if ($data['option'] == "chemical") {
                    $chemicals = $data['list'];
                    for ($j = 0; $j < count($chemicals); $j++) {
                        $chemical = $chemicals[$j];
    
                        $sql1 = "INSERT INTO chemical_issue (chemical_no, batch_no, qty, purpose, status, entry_by, entry_date) VALUES ('".$chemical['id']."', '".$chemical['batch_no']."', '".$chemical['qty']."', 'TESTING', 'approve', '".$_GET["emp_id"]."', '$entry_date')"; 
                        $conn->query($sql1);
    
                        $sql1 = "SELECT * FROM chemicals WHERE chemical_no='".$chemical['id']."' AND batch_no='".$chemical['batch_no']."'";
                        $result = $conn->query($sql1);
                        if ($result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                $received_qty = $row["received_qty"];
                                $issue_qty = $row["issue_qty"];
                                $issue_qty += +$chemical['qty'];
                                $sql1 = "UPDATE chemicals SET issue_qty='$issue_qty' WHERE id='".$row['id']."'";
                                $conn->query($sql1);
                                break;
                            }
                        }
                    }
                }
            }
            /* $sql = "INSERT INTO equipment_uses (equipment_no, batch_no, activity, cleaning_type, start_time, end_time, operator, entry_by, entry_date) VALUES ('".$input["equipment"]."', '', 'Testing', '".$input["cleaning_type"]."', '".$start_time."', '".$end_time."', '".$_GET["emp_id"]."', '".$_GET["emp_id"]."', '".$entry_date."')";
            $conn->query($sql); */
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
        
        $sql = "SELECT * FROM testing_tests WHERE testing_no='".$input["testing_no"]."' AND status='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            
        } else {
            $sql = "UPDATE testing SET status='active' WHERE testing_no='".$input["testing_no"]."'";
            $conn->query($sql);
        }
    } else if ($_GET["type"] == "approveTesting") {
        $sql = "UPDATE testing SET status='".$_GET["status"]."', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
            $sql = "UPDATE stock_book SET status='Approved' WHERE material_code='".$_GET["material_code"]."' AND batch_no='".$_GET["batch_no"]."'";
            $conn->query($sql);
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }else if ($_GET["type"] == "getPendingTestingReport") {
         $sql = "SELECT t.*,s.method_details, m.material_type, m.material_subtype, m.material_name, m.grade FROM testing t LEFT JOIN material m ON t.material_code=m.material_code LEFT JOIN spec_tests s ON t.specification_no=s.specification_no AND m.material_type='Raw Material' ORDER BY t.id DESC";
        $output = Array();
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $row["observation"] = "";
                $sql1 = "SELECT * FROM testing_tests WHERE user_no='".$_GET["user_no"]."' AND testing_no='".$row["testing_no"]."'";
                $output1 = Array();
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT * FROM spec_tests WHERE specification_no=(SELECT specification_no FROM testing WHERE testing_no='".$row["testing_no"]."') AND test='".$row1["test"]."' AND subtest='".$row1["subtest"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                
                                if ($row1["observation"] !== "pass") {
                                    if ($row2["limit_type"] == "Limits") {
                                        if ($row1["result"] >= $row2["lower_limit"] && $row1["result"] <= $row2["upper_limit"]) {
                                            $row1["observation"] = "pass";
                                        } else {
                                            $row1["observation"] = "fail";
                                        }
                                    } else if ($row2["limit_type"] == "LessThan") {
                                        if ($row1["result"] <= $row2["lessthan"]) {
                                            $row1["observation"] = "pass";
                                        } else {
                                            $row1["observation"] = "fail";
                                        }
                                    } else if ($row2["limit_type"] == "MoreThan") {
                                        if ($row1["result"] >= $row2["morethan"]) {
                                            $row1["observation"] = "pass";
                                        } else {
                                            $row1["observation"] = "fail";
                                        }
                                    } else if ($row2["limit_type"] == "Compliances") {
                                        if ($row1["result"] == "complies") {
                                            $row1["observation"] = "pass";
                                        } else {
                                            $row1["observation"] = "fail";
                                        }
                                    }
                                }
                            }
                        }
                        if ($row1["observation"] == "fail") {
                            $row["observation"] = "fail";
                        }
                        $output1[] = $row1;
                    }
                }
                $row["tests"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    
   else if($_GET['type'] == 'downloadTestingRDSReport'){
        $_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader'; include("../../pdfimp2.php");
        
        
        $sql = "SELECT * FROM testing WHERE testing_no='".$_GET['testing_no']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $html .= '<h3 style="text-align:center;">Raw Data Sheet</h3>
                        <table  style="text-align:left;">
                        <tr>
                            <td style="width:23%">Department</td>
                             <td style="width:31%">Quality Control Department</td>
                            <td style="width:29%">Material Code:</td>
                             <td style="width:17%">'.$row["material_code"].'</td>
                        </tr>';
                
                $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $html.='<tr>
                            <td style="width:23%" rowspan="2">Name of Material / Product</td>
                             <td style="width:31%;vertical-align: middle;" rowspan="2">'.$row1["material_name"].'</td>
                            <td style="width:29%">SAP No.</td>
                             <td style="width:17%">Jul,2018</td>
                        </tr>';
                    }
                }
                $html.='
                <tr>
                    <td style="width:29%">Version No.</td>
                     <td style="width:17%">02</td>
                </tr>
                <tr>
                    <td style="width:23%" rowspan="2">Batch Size</td>
                     <td style="width:31%;vertical-align: middle;" rowspan="2">ATP003</td>
                    <td style="width:29%">Mfg Date</td>
                     <td style="width:17%">Jul,2018</td>
                </tr>
                <tr>
                    <td style="width:29%">Exp. Date</td>
                     <td style="width:17%">Jul,2018</td>
                </tr>
                <tr>
                    <td style="width:23%" rowspan="2">Sample Quantity</td>
                     <td style="width:31%;vertical-align: middle;" rowspan="2">ATP003</td>
                    <td style="width:29%">Specification Refrence No.</td>
                     <td style="width:17%">'.$row['specification_no'].'</td>
                </tr>
                <tr>
                    <td style="width:29%">SAP Reference No.</td>
                     <td style="width:17%">23</td>
                </tr>
                <tr>
                    <td style="width:23%">Sample By / Date</td>
                     <td style="width:31%">'.$row['entry_by'].' '.$row['entry_date'].'</td>
                    <td style="width:29%">Analysis Completion Date</td>
                     <td style="width:17%">20/08/2016</td>
                </tr>
                <tr>
                    <td style="width:23%">Reference</td>
                     <td style="width:31%">test</td>
                    <td style="width:29%">Effective Date</td>
                     <td style="width:17%">20/08/2016</td>
                </tr>
            </table>
            <h2 style="text-align: center;">ANALYTICAL REPORT SUMMARY</h2>
            
                        <table border="1" cellpadding="2">
                            <tr>
                                <td style="width:40%;text-align:center; height:20px;"><b>Test</b></td>
                                <td style="width:30%; text-align:center; height:20px;"><b>Specification</b></td>
                                <td style="width:30%; text-align:center; height:20px;"><b>Result</b></td>
                            </tr>';
                            $output1 = array();
                             $sql1 = "SELECT * FROM testing_tests WHERE testing_no='".$row["testing_no"]."'";
                            $result1 = $conn->query($sql1);
                            $j=1;
                            if ($result1->num_rows > 0) {
                                while ($row1 = $result1->fetch_assoc()) {
                        $html.='<tr>
                                    <td style="width:40%; height:20px;"> '.$row1['test'].'</td>
                                    <td style="width:30%; height:20px;"> '.$row1['subtest'].'</td>
                                    <td style="width:30%; height:20px;"> '.$row1['result'].'</td>
                                </tr>';
                                }
                            }
                       
                 
            
            $html.='</table>';
            }
        }
        
        $sql = "SELECT * FROM testing_tests WHERE testing_no='T-01' GROUP BY test";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $i = 1;
            $alphabet = range('A', 'Z');
            while ($row = $result->fetch_assoc()) {
                $html .= '<h3>'.$i.'. '.$row["test"].'</h3>';
                $sql1 = "SELECT * FROM testing_tests WHERE testing_no='T-01' WHERE test='".$row["test"]."' AND subtest !=''";
                // $sql1 = "SELECT * FROM testing_tests WHERE testing_no='T-01' AND test='".$row["test"]."' AND subtest !=''";

                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $html.='<span><b>'.$alphabet[$i].'. Observation:</b> '.$row["result"].'</span><br>';
                        $html.='<span><b>Acceptance criteria:</b><br>'.$row["description"].'</span><br>';
                        if ($row["status"] == "approve") {
                            $html.='<table cellpadding="5"><tr><td colspan="2" style="text-align: center;">The Test complies</td></tr><tr><td style="text-align: center;">Analysed By/Date: '.$row["person"].'</td><td style="text-align: center;">Checked By/Date:</td></tr></table>';
                        } else {
                            $html.='<table cellpadding="5"><tr><td colspan="2" style="text-align: center;">The Test Not Comply</td></tr><tr><td style="text-align: center;">Analysed By/Date: '.$row["person"].'</td><td style="text-align: center;">Checked By/Date:</td></tr></table>';
                        }
                    }
                } else {
                    $html.='<span><b>Observation:</b> '.$row["result"].'</span><br>';
                    $html.='<span><b>Acceptance criteria:</b><br>'.$row["description"].'</span><br>';
                    if ($row["status"] == "approve") {
                        $html.='<table cellpadding="5"><tr><td colspan="2" style="text-align: center;">The Test complies</td></tr><tr><td style="text-align: center;">Analysed By/Date: '.$row["person"].'</td><td style="text-align: center;">Checked By/Date:</td></tr></table>';
                    } else {
                        $html.='<table cellpadding="5"><tr><td colspan="2" style="text-align: center;">The Test Not Comply</td></tr><tr><td style="text-align: center;">Analysed By/Date: '.$row["person"].'</td><td style="text-align: center;">Checked By/Date:</td></tr></table>';
                    }
                }
                $i++;
            }
        }
        

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Testing RDS Report.pdf', 'I');
    }


    }else if ($_GET["type"] == "downloadTestingReport") {
        $_GET['filename'] = 'A.R.Report'; $_GET['pdftype'] = 'onlyheader'; include("../../pdfimp2.php");
        $html= "";

        $html.='
        <h2 style="text-align:center">A.R.Report</h2>
        <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 10%;">A. R. No.</td>
                    <td style="width: 10%;"> Sampling No</td>
                    <td style="width: 20%;">Specification No</td>
                    <td style="width: 20%;">Material Name</td>
                    <td style="width: 10%;">Material Code</td>
                    <td style="width: 15%;">Material Grade</td>
                    <td style="width: 15%;">view</td>
                </tr>
            </thead>';
              $output = Array();
                $sql = "SELECT * FROM testing_tests ORDER BY name";
                $result = $conn->query($sql);
                $i=1;
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $output[] = $row;
                $html.='<tr nobr="true">
                        <td style="width: 10%; ">'.$i.'.</td>
                        <td style="width: 10%; ">'.$row['sampling_no'].'</td>
                        <td style="width: 20%; ">'.$row['specification_no'].'</td>
                        <td style="width: 20%; ">'.$row['material_name'].'</td>
                        <td style="width: 10%; ">'.$row['material_code'].'</td>
                        <td style="width: 15%;">'.$row['material Grade'].'</td>
                        <td style="width: 15%;">'.$row['view'].'</td>
                        
                    </tr>';
                $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Glasswares Log.pdf', 'I');
}

}
$conn->close();
?>