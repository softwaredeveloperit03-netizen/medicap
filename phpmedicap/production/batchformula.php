<?php
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
    if($result->num_rows > 0){
    while($row = $result->fetch_assoc()){
	    $string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
	    $string = explode("$",$string);
	    $_GET["emp_id"] = $string[0];
	    $_GET["department"] = $string[1];
	    break;
    }

    $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."')";
    $conn->query($sql);
    
    if ($_GET["type"] == "getBOMs") {
        $output = array();
        $sql = "SELECT * FROM product WHERE user_no='".$_GET["user_no"]."' AND status='approve'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = array();
                $sql1 = "SELECT * FROM stages WHERE user_no='".$_GET["user_no"]."' AND product_code='".$row["product_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                    $row["stage_status"] = "yes";
                } else {
                    $row["stage_status"] = "no";
                }
                $row["stages"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getDosages") {
        $output = Array();
        $sql = "SELECT * FROM dosage_form";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = Array();
                $sql1 = "SELECT * FROM product WHERE user_no='".$_GET["user_no"]."' AND dosage_form='".$row["dosage_form"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output2 = Array();
                        $sql2 = "SELECT * FROM unitformula WHERE user_no='".$_GET["user_no"]."' AND product_code='".$row1["product_code"]."' AND status='approve'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $output3 = Array();
                                $sql3 = "SELECT u.*, m.material_subtype, m.material_name, m.grade FROM unit_materials u LEFT JOIN material m ON u.material_code=m.material_code WHERE u.mfr_no='".$row2["id"]."'";
                                $result3 = $conn->query($sql3);
                                if ($result3->num_rows > 0) {
                                    while ($row3 = $result3->fetch_assoc()) {
                                        $row3["batch_qty"] = 0;
                                        $row3["batch_unit"] = $row3["unit"];
                                        $row3["lot_qty"] = 0;
                                        $row3["lot_unit"] = $row3["unit"];
                                        $output3[] = $row3;
                                    }
                                }
                                $row2["materials"] = $output3;
                                $row2["lots"] = [];
                                $output2[] = $row2;
                            }
                        }
                        $row1["mfrs"] = $output2;
                        $output1[] = $row1;
                    }
                }
                $row["products"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "saveBatchFormula") {
        $sql = "INSERT INTO batch_formula (user_no, product_code, mfr_no, batch_size, lots, entry_by, entry_date) VALUES ('".$_GET["user_no"]."','".$input["product_code"]."', '".$input["mfr_no"]."', '".$input["batch_size"]."', '".$input["lots"]."', '".$_GET["emp_id"]."', '$entry_date')";
        if ($conn->query($sql)) {
            $last_id = $conn->insert_id;
            $materials = $input["materials"];
            for ($i = 0; $i < count($materials); $i++) {
                $material = $materials[$i];
                $sql1 = "INSERT INTO batch_materials (no, material_code, overages, qty, unit, batch_qty, batch_unit, lot_qty, lot_unit, role, process) VALUES ('$last_id', '".$material["material_code"]."', '".$material["overages"]."', '".$material["qty"]."', '".$material["unit"]."', '".$material["batch_qty"]."', '".$material["batch_unit"]."', '".$material["lot_qty"]."', '".$material["lot_unit"]."', '".$material["role"]."', '".$material["process"]."')";
                $conn->query($sql1);
            }
            
            echo "{\"status\":\"success\",\"count\":\"count($materials)\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getPendingBatchFormula") {
        $output = Array();
        $sql = "SELECT b.*, p.product_name, p.grade, p.generic_name, p.dosage_form, p.label_claim FROM batch_formula b LEFT JOIN product p ON b.product_code=p.product_code WHERE b.user_no='".$_GET["user_no"]."' AND b.status='pending' GROUP BY b.id";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $output1 = Array();
                $sql1 = "SELECT b.*, m.material_name, m.grade FROM batch_materials b LEFT JOIN material m ON b.material_code=m.material_code WHERE b.no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row["materials"] = $output1;
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "checkBatchFormula") {
        $sql = "UPDATE batch_formula SET status='".$_GET["status"]."', check_by='".$_GET["emp_id"]."', check_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    } else if ($_GET["type"] == "getCheckedBatchFormula") {
        $output = Array();
        $sql = "SELECT b.*, p.product_name, p.grade, p.generic_name, p.dosage_form, p.label_claim FROM batch_formula b LEFT JOIN product p ON b.product_code=p.product_code WHERE b.user_no='".$_GET["user_no"]."' AND b.status='checked' GROUP BY b.id";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $output1 = Array();
                $sql1 = "SELECT b.*, m.material_name, m.grade FROM batch_materials b LEFT JOIN material m ON b.material_code=m.material_code WHERE b.no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row["materials"] = $output1;
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "approveBatchFormula") {
        $sql = "UPDATE batch_formula SET status='".$_GET["status"]."', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    } else if ($_GET["type"] == "getProducts") {
        $output = array();
        $sql = "SELECT b.*, p.product_name, p.grade, p.generic_name, p.dosage_form, p.label_claim FROM batch_formula b LEFT JOIN product p ON b.product_code=p.product_code WHERE b.user_no='".$_GET["user_no"]."' AND b.status='approve' AND p.dosage_form LIKE '%".$_GET["dosage_form"]."%' AND b.product_code LIKE '%".$_GET["product_code"]."' GROUP BY b.id";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM instructions WHERE user_no='".$_GET["user_no"]."' AND product_code='".$row["product_code"]."' AND status='approve'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row1["instructions"] = json_decode($row1["instructions"]);
                        $row["instructions"] = $row1["instructions"];
                    }
                } else {
                    $output1 = array();
                    $row["instructions"] = $output1;
                }
                
                $output1 = Array();
                $sql1 = "SELECT b.*, m.material_name, m.grade FROM unit_materials b LEFT JOIN material m ON b.material_code=m.material_code WHERE b.mfr_no=(SELECT id FROM unitformula WHERE mfr_no='".$row["mfr_no"]."')";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row["unit_materials"] = $output1;
                
                $output1 = Array();
                $sql1 = "SELECT b.*, m.material_name, m.grade FROM batch_materials b LEFT JOIN material m ON b.material_code=m.material_code WHERE b.no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row["batch_materials"] = $output1;
                
                $output1 = array();
                $sql1 = "SELECT * FROM stages WHERE user_no='".$_GET["user_no"]."' AND product_code='".$row["product_code"]."' AND status='approve'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row1["details"] = json_decode($row1["details"]);
                        $row1["instructions"] = json_decode($row1["instructions"]);
                        $row1["procedures"] = json_decode($row1["procedures"]);
                        $row1["equipments"] = json_decode($row1["equipments"]);
                        $row1["clearances"] = json_decode($row1["clearances"]);
                        
                        if ($row1["istest"] == "true") {
                            $output2 = array();
                            $sql2 = "SELECT * FROM spec_tests WHERE specification_no = (SELECT specification_no FROM specification WHERE user_no='".$_GET["user_no"]."' AND spec_type='Inprocess Specification' AND product_code='".$row["product_code"]."' AND stage='".$row1["stage"]."' ORDER BY id DESC LIMIT 1)";
                            $result2 = $conn->query($sql2);
                            if ($result2->num_rows > 0) {
                                while ($row2 = $result2->fetch_assoc()) {
                                    $output2[] = $row2;
                                }
                            }
                            $row1["spec_tests"] = $output2;
                        }
                        
                        if ($row1["isinprocess"] == "true") {
                            $output2 = array();
                            $sql2 = "SELECT * FROM spec_tests WHERE specification_no = (SELECT specification_no FROM specification WHERE user_no='".$_GET["user_no"]."' AND spec_type='Inprocess Specification' AND product_code='".$row["product_code"]."' AND stage='".$row1["stage"]."' ORDER BY id DESC LIMIT 1)";
                            $result2 = $conn->query($sql2);
                            if ($result2->num_rows > 0) {
                                while ($row2 = $result2->fetch_assoc()) {
                                    $output2[] = $row2;
                                }
                            }
                            $row1["spec_tests"] = $output2;
                        }
                        $output1[] = $row1;
                    }
                }
                $row["stages"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if($_GET['type'] == 'bmrMasterPDF'){
        $sql = "SELECT b.*, p.product_name, p.grade, p.generic_name, p.dosage_form, p.label_claim FROM batch_formula b LEFT JOIN product p ON b.product_code=p.product_code WHERE b.id='".$_GET['id']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                require '../tcpdf/tcpdf.php'; $_GET['pdffonts'] = '11'; $_GET['pdftype'] = 'headfoot'; include("../pdfimp.php");
                $sql1 = "SELECT * FROM instructions WHERE user_no='".$_GET["user_no"]."' AND product_code='".$row["product_code"]."' AND status='approve'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row1["instructions"] = json_decode($row1["instructions"]);
                        $row["instructions"] = $row1["instructions"];
                    }
                } else {
                    $output1 = array();
                    $row["instructions"] = $output1;
                }
                
                $output1 = Array();
                $sql1 = "SELECT b.*, m.material_name, m.grade FROM unit_materials b LEFT JOIN material m ON b.material_code=m.material_code WHERE b.mfr_no=(SELECT id FROM unitformula WHERE mfr_no='".$row["mfr_no"]."')";
                $result1 = $conn->query($sql1);
                if ($result->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row["unit_materials"] = $output1;
                
                $output1 = Array();
                $sql1 = "SELECT b.*, m.material_name, m.grade FROM batch_materials b LEFT JOIN material m ON b.material_code=m.material_code WHERE b.no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row["batch_materials"] = $output1;
                
                $output1 = array();
                $sql1 = "SELECT * FROM stages WHERE user_no='".$_GET["user_no"]."' AND product_code='".$row["product_code"]."' AND status='approve'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row1["details"] = json_decode($row1["details"]);
                        $row1["instructions"] = json_decode($row1["instructions"]);
                        $row1["procedures"] = json_decode($row1["procedures"]);
                        $row1["equipments"] = json_decode($row1["equipments"]);
                        $row1["clearances"] = json_decode($row1["clearances"]);
                        
                        if ($row1["istest"] == "true") {
                            $output2 = array();
                            $sql2 = "SELECT * FROM spec_tests WHERE specification_no = (SELECT specification_no FROM specification WHERE user_no='".$_GET["user_no"]."' AND spec_type='Inprocess Specification' AND product_code='".$row["product_code"]."' AND stage='".$row1["stage"]."' ORDER BY id DESC LIMIT 1)";
                            $result2 = $conn->query($sql2);
                            if ($result2->num_rows > 0) {
                                while ($row2 = $result2->fetch_assoc()) {
                                    $output2[] = $row2;
                                }
                            }
                            $row1["spec_tests"] = $output2;
                        }
                        
                        if ($row1["isinprocess"] == "true") {
                            $output2 = array();
                            $sql2 = "SELECT * FROM spec_tests WHERE specification_no = (SELECT specification_no FROM specification WHERE user_no='".$_GET["user_no"]."' AND spec_type='Inprocess Specification' AND product_code='".$row["product_code"]."' AND stage='".$row1["stage"]."' ORDER BY id DESC LIMIT 1)";
                            $result2 = $conn->query($sql2);
                            if ($result2->num_rows > 0) {
                                while ($row2 = $result2->fetch_assoc()) {
                                    $output2[] = $row2;
                                }
                            }
                            $row1["spec_tests"] = $output2;
                        }
                        $output1[] = $row1;
                    }
                }
                $row["stages"] = $output1;
      $html='
      <style>table tr td { border:solid 1px BCBBBA; }</style>
      <table cellpadding="3">
        <tr>
            <td align="center">BMR FORMAT APPROVED BY</td>
        </tr>
        <tr>
            <td style="width:20%;">Particulars</td>
            <td style="width:40%;">Production</td>
            <td style="width:40%;">Quality Assurance</td>
        </tr>
        <tr>
            <td>Name</td>
            <td></td>
            <td></td>
        </tr>
        <tr>
            <td>Designation</td>
            <td>Manager - Production</td>
            <td>Manager - Quality Assurance</td>
        </tr>
        <tr>
            <td>Sign/date</td>
            <td></td>
            <td></td>
        </tr>
    </table>
    <div>
	    <p>Manufacturing Commencement Date: (dd/mm/yy)	: _____________________</p>
        <p>Manufacturing Completion Date: (dd/mm/yy)	: ____________________</p>
        <p><b>Shelf Life:</b></p>
    </div>
    <h4>BATCH  DETAILS :</h4>
    <table cellpadding="3">
        <tr>
            <td style="width:50%;border:none;"><b>BATCH NO. : </b></td>
            <td style="width:50%;border:none;"><b>BATCH SIZE : '.$row['batch_size'].'</b></td>
        </tr>
        <tr>
            <td style="border:none;"><b>MFG.  DATE :</b></td>
            <td style="border:none;"><b>EXP. DATE :</b></td>
        </tr>
    </table>
    <div></div>
    <table cellpadding="3">
        <tr>
            <td align="center"><b>BMR ISSUANCE</b></td>
        </tr>
        <tr>
            <td style="width:50%;"><b>BMR issued by</b></td>
            <td></td>
        </tr>
        <tr>
            <td style="width:50%;"><b>Sign /Date</b></td>
            <td></td>
        </tr>
    </table>
    <div></div>
    <table cellpadding="3">
        <tr>
            <td><b>Reason for Change</b></td>
            <td><b>New BMR</b></td>
        </tr>
    </table>
    <h3 align="center">Dispensing and Manufacturing Commencement Details</h3>
    <table cellpadding="3">
        <tr style="text-align:center; font-weight:bold;">
            <td>Operations</td>
            <td>Date of Commencement</td>
            <td>Date of completion</td>
        </tr>
        <tr>
            <td></td>
            <td></td>
            <td></td>
        </tr>
    </table>
    <div></div>
    <table cellpadding="3">
        <tr>
            <td><b>BMR release and review status:</b></td>
        </tr>
        <tr>
            <td align="center"><b>APPROVED</b></td>
        </tr>
        <tr>
            <td align="center" style="width:33.33%"><b>Particulars</b></td>
            <td align="center" style="width:33.33%"><b>Production</b></td>
            <td align="center" style="width:33.33%"><b>Quality Control</b></td>
        </tr>
        <tr>
            <td><b>Name</b></td>
            <td></td>
            <td></td>
        </tr>
        <tr>
            <td><b>Designation</b></td>
            <td></td>
            <td></td>
        </tr>
        <tr>
            <td><b>Sign and Date</b></td>
            <td></td>
            <td></td>
        </tr>
        <tr>
            <td colspan="3"><b>BMR review by Quality Assurance</b></td>
        </tr>
        <tr>
            <td><b>Particulars</b></td>
            <td><b>Reviewed by</b></td>
            <td><b>Checked by</b></td>
        </tr>
        <tr>
            <td><b>Name</b></td>
            <td></td>
            <td></td>
        </tr>
        <tr>
            <td><b>Designation</b></td>
            <td></td>
            <td></td>
        </tr>
        <tr>
            <td><b>Sign and Date</b></td>
            <td></td>
            <td></td>
        </tr>
    </table>
    <div>
        <b>LABEL CLAIM:</b><br>
        <p>Each ml contains:</p>
        <p>Ferric hydroxide in complex with Sucrose equivalent to elemental Iron  …………. 20mg/ml.</p>
        <p>Water for Injection USP ……. Q.S</p>
    </div>
    <h3>MANUFACTURING FORMULA:</h3>
    <table cellpadding="3">
        <tr style="font-weight:bold;">
            <td>Sr. no.</td>
            <td>Materials</td>
            <td>Material code</td>
            <td>Contents mg/ml</td>
            <td>Overages</td>
            <td>Qty for 10 lts.</td>
        </tr>
        <tr>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
        <tr>
            <td colspan="6">* Calculated with respect to assay content.</td>
        </tr>
    </table>
    <div></div>
    <table>
        <tr>
            <td>
                <b>CALCULATION: Refer to the below calculation in case the Assay has been provided:</b><br>
                If only one batch number of  Iron sucrose to be used, calculate the quantity as follow:<br>
                A.R. number :              Assay of iron :<br>
                                            2
                Quantity required = ---------------- X 10 =<br>
                                        Iron Assay<br>
                <b>If two A. R. numbers of Iron sucrose are to be used, calculate the quantity as follow:</b><br>
                A) First  A.R. number : ___________________               Quantity available (X) =  _______________ kg<br>
                Assay of iron : ______________<br>
            </td>
        </tr>
    </table>

Actual quantity after calculation of the Iron sucrose for per batch (A) ____________________  


The quantity of Iron Sucrose required from the next batch is to be calculated as follows                                                 


A (____________________kg) – X(___________________kg) = __________________kg (Y)



                  Assay of first batch no.
Y (kg) X  …………………………........................  =   ________________ kg (Z) from next batch no.
                  Assay of second batch no.                                                           



Total qty. of Iron Sucrose to be dispensed =                                                       








Calculation done by:                                                                                      Checked by QA:  
<br>
<h3>Dispensing</h3>
<table cellpadding="3">
    <tr>
        <td><b>Previous Product:</b></td>
        <td><b>Batch No.:</b></td>
    </tr>
    <tr>
        <td><b>Date:</b></td>
        <td><b>Time:</b></td>
    </tr>
</table>
<table cellpadding="3">
    <tr style="font-weight:bold;">
        <td>Sr. No.</td>
        <td>Area / Equipment</td>
        <td>ID No.</td>
        <td>Cleaning SOP No.</td>
        <td>Cleaned By</td>
        <td>Checked By (Store)</td>
        <td>Verified By QA</td>
    </tr>
    <tr>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
    </tr>
</table>
<div></div>
<table cellpadding="3">
    <tr style="font-weight:bold;">
        <td>Environmental Conditions</td>
        <td>Limit</td>
        <td>Observations</td>
        <td>Observed By sign./date</td>
    </tr>
    <tr>
        <td>Temperature</td>
        <td>23°C ± 2°C</td>
        <td>°C</td>
        <td></td>
    </tr>
    <tr>
        <td>Humidity</td>
        <td>55% ± 5%</td>
        <td>%</td>
        <td></td>
    </tr>
    <tr>
        <td colspan="4"><b>Differential pressure of Filters</b></td>
    </tr>
    <tr>
        <td>Pre-filter 10micron</td>
        <td>1 to 5 MMWC</td>
        <td>MMWC</td>
        <td></td>
    </tr>
    <tr>
        <td>Fine filter 5micron</td>
        <td>2 to 7 MMWC</td>
        <td>MMWC</td>
        <td></td>
    </tr>
    <tr>
        <td>HEPA filter</td>
        <td>8 to 16 MMWC</td>
        <td>MMWC</td>
        <td></td>
    </tr>
</table>
<h3>Take line clearance from QA as per checklist given for dispensing. (As Per SOP/QA/20)</h3>
<table cellpadding="3">
    <tr style="font-weight:bold;">
        <td>Sr. No.</td>
        <td>Checklist</td>
        <td>Done by Sign./date</td>
        <td>Checked by Sign./date</td>
    </tr>
	<tr>
	    <td></td>
	    <td></td>
	    <td></td>
	    <td></td>
	</tr>
</table>
<h3>Cleaning Verification and Line Clearance</h3>
<table cellpadding="3">
    <tr>
        <td rowspan="2"><b>Activity</b></td>
        <td colspan="2"><b>Line Clearance</b></td>
    </tr>
    <tr>
        <td><b>Done by Stores (Sign./Date)</b></td>
        <td><b>Verified by QA  (Sign./Date)</b></td>
    </tr>
    <tr>
        <td></td>
        <td></td>
        <td></td>
    </tr>
</table>
<table cellpadding="3">
    <tr>
        <td><b>Magnehelic Gauge Differential Pressure Reading</b></td>
    </tr>
    <tr style="font-weight:bold;">
        <td style="width:25%">Area</td>
        <td style="width:25%">Limit in pascal</td>
        <td style="width:25%">At the beginning of  dispensing in pascal</td>
        <td style="width:25%">At the end of the dispensing in pascal</td>
    </tr>
    <tr>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
    </tr>
 	<tr>
 	    <td colspan="2"><b>Done by Stores:</b></td>
 	    <td colspan="2"><b>Checked by QA:</b></td>
 	</tr>
 	<tr>
 	    <td colspan="2"><b>(Sign./Date)</b></td>
 	    <td colspan="2"><td>(Sign. /Date)</td></td>
 	</tr>
</table>
<div>
PRECAUTION:<br>1. All the balances should be checked for zero error.
</div>
<h3 align="center">BMR Copy</h3>
<h3 align="center">RAW MATERIAL ISSUANCE NOTE</h3>
<table cellpadding="3">
    <tr style="font-weight:bold;">
        <td rowspan="2">Sr. No.</td>
        <td rowspan="2">Mat. Code</td>
        <td rowspan="2">Ingredient</td>
        <td rowspan="2">Std. Qty. Per Batch In Kgs</td>
        <td rowspan="2">A.R. No.</td>
        <td rowspan="2">Balance I.D. no.</td>
        <td colspan="3">Qty Used/ Issued</td>
        <td rowspan="2">Dispensed by</td>
        <td rowspan="2">Checked by</td>
    </tr>
    <tr>
        <td>Tare Wt. in Kgs</td>
        <td>Net Wt. in Kgs</td>
        <td>Gross Wt. in Kgs</td>
    </tr>
    <tr>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
    </tr>
    <tr>
        <td style="width:50%">Stores:</td>
        <td style="width:50%">Quality Assurance:</td>
    </tr>
    <tr>
        <td>Sign./date:</td>
        <td>Sign./date:</td>
    </tr>
</table>
Stage- Autoclave 
Line Clearance of Autoclave for Sterilization of Garments and Components.(As per SOP/QA/20)

<h3>List of equipments</h3>
<table cellpadding="3">
    <tr>
        <td rowspan="2">Sr. no.</td>
        <td rowspan="2">Equipment name</td>
        <td rowspan="2">Equipment I.D.</td>
        <td rowspan="2">SOP No.</td>
        <td rowspan="2">Cleaned By</td>
        <td colspan="2">Checked by</td>
    </tr>
    <tr>
        <td>PRD.</td>
        <td>QA</td>
    </tr>
    <tr nobr="true">
        <td>1.</td>
        <td>Double door Autoclave</td>
        <td>SBP/PR/AT/11</td>
        <td>SOP/PR/10</td>
        <td></td>
        <td></td>
        <td></td>
    </tr>
</table>

Activity	Line Clearance
	Done by Production (Sign/Date)	Verified by QA (Sign/Date)
Area and Equipment cleanliness.		
Verification of updated logs.		

<h3 align="center">Sterilization of Garments and Components</h3>
<table cellpadding="3">
    <tr>
        <td>Date</td>
        <td>Cycle No.</td>
        <td>Load Details</td>
        <td>Cycle started at</td>
        <td>Hold time start</td>
        <td>Steam Pressure (psi)</td>
        <td>Hold time end</td>
        <td>Operated by</td>
    </tr>
    <tr>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
    </tr>
    <tr>
        <td colspan="4">Production Chemist:</td>
        <td colspan="4">Checked by QA</td>
    </tr>
    <tr>
        <td>Sign./Date:</td>
        <td>Sign./Date:</td>
    </tr>
</table>
<h4>Note: Attach sterilization cycle print out and strip chart recorder printout to BMR</h4>
<h3>Stage: - Manufacturing Operation</h3>
<table>
    <tr>
        <td>Previous Product:</td>
        <td>Batch No.: </td>
    </tr>
    <tr>
        <td>Date:</td>
        <td>Time:</td>
    </tr>
</table>
<table>
    <tr style="font-weight:bold;">
        <td>Sr. No.</td>
        <td>Checklist</td>
        <td>Observation</td>
        <td>Checked By PRD</td>
        <td>Verified By QA</td>
    </tr>
    <tr>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
    </tr>
</table>
		                                                                  
Temperature		:  NMT 250C<br>
Relative Humidity	:  55 ± 5 % ______ °C ______  %<br>		
7.	Check and ensure that all employees of the respective area are in proper gowning.	Complies/      Doesn’t Comply<br>		
8.	Swab/Rinse analysis report no. _____________	Complies/   Doesn’t Comply<br>	

Line clearance
Take line clearance for manufacturing as per below checklist (Ref. Sop no.-SOP/QA/20)
Activity	Line Clearance
	Done by Production (Sign./Date)	Verified by QA (Sign./Date)
Area and Equipment free from previous materials, product and records.		
Verification of updated logs<br>		
<table cellpadding="3">
    <tr>
        <td colspan="8">List of Equipment and Instrument and Cleaning Status</td>
    </tr>
    <tr>
        <td>Sr. no.</td>
        <td>Equipment name</td>
        <td>Equipment I.D.</td>
        <td>SOP No.</td>
        <td>Cleaned By</td>
        <td>Checked by</td>
        <td>Prod. Sign./Date</td>
        <td>QA Sign./Date</td>
    </tr>
    <tr>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
    </tr>
</table>
Pressure Differential Reading

Area	Limit in pascal	At the start of mfg. in pascal	At the End of mfg. in pascal	Checked by prd. Sign./date
Air Lock -1	15 ± 5			
Air Lock -2	7.5 ± 5			
Air Lock -3	7.5 ± 5			
Exit Air Lock	7.5 ± 5			
Component preparation room	7.5 ± 5			
Core manufacturing area	7.5 ± 5			
Component washing	22.5 ±5			

<h3>BMR Copy</h3>
<h3>Water for Injection test request form and Analysis Status</h3>
<table cellpadding="3">
    <tr>
        <td>Mfg. date	:</td>
    </tr>
    <tr>
        <td>Exp. date	:</td>
    </tr>
    <tr>
        <td>Stage of sample	:</td>
    </tr>
    <tr>
        <td>Requested by	:	Sign./date</td>
    </tr>
    <tr>
        <td>Quantity sampled	:</td>
    </tr>
    <tr>
        <td>Sampled by	:	Sign./date</td>
    </tr>
    <tr>
        <td>Sampling time	:</td>
    </tr>
    <tr>
        <td>A.R. No.	:</td>
    </tr>
</table>
<table cellpadding="3">
    <tr>
        <td>Test</td>
        <td>Specification</td>
        <td>Observations</td>
    </tr>
    <tr>
        <td>Description</td>
        <td>Clear, colorless and odorless liquid</td>
        <td></td>
    </tr>
    <tr>
        <td>pH</td>
        <td>5.0 to 7.0</td>
        <td></td>
    </tr>
    <tr>
        <td>Bacterial Endotoxin Test</td>
        <td>Less than 0.25EU/ml</td>
        <td></td>
    </tr>
    <tr>
        <td style="width:100%">Remarks: The Sample Complies / Does not Comply as per Specification</td>
    </tr>
    <tr>
        <td style="width:50%">Analyzed  by /date:</td>
        <td style="width:50%">Checked By/ Date :</td>
    </tr>
</table>

BULK MANUFACTURING STAGE
Equipment I.D. for Manufacturing: 
Line clearance of Area and Equipment given by______________ Date: __________ Time: _________
Processing steps	Time	Done By Sign/date	Checked By Sign/date
	From	To		
Standardize and calibrate the pH meter.				
				
Close the vessel after blanketing the overhead space with nitrogen.
Take the sample for bulk analysis and transfer to QC.
Verified by (QA):
Sign./date

Volume	Specific Gravity 	Load cell reading (kg.)(Specific gravity X volume)
_____________Lts.	  ____________Wt/ml	________________________Kgs.






Silicone Tube Issuance and Destruction Record
(Destroy silicone tubes after completion of each product campaign)
Item name	 Size	Issuance	Destruction 
		Qty. issued	Issued by/date	Checked by/date	Qty. destroyed	Destroyed by/date	Checked by/date
Silicon tube							
Silicon tube							
Silicon tube							
<div>
    <h3>BMR Copy</h3>
    <h3>Bulk test request form and Analysis Status</h3>
</div>
<table cellpadding="3">
    <tr>
        <td>Mfg. date	:</td>
    </tr>
    <tr>
        <td>Exp. date	:</td>
    </tr>
    <tr>
        <td>Stage of sample	:</td>
    </tr>
    <tr>
        <td>Requested by	:	Sign./date</td>
    </tr>
    <tr>
        <td>Quantity sampled	:</td>
    </tr>
    <tr>
        <td>Sampled by	:	Sign./date</td>
    </tr>
    <tr>
        <td>Sampling time	:</td>
    </tr>
    <tr>
        <td>A.R. No.	:</td>
    </tr>
</table>
<table cellpadding="3">
    <tr>
        <td style="width:33%">Test</td>
        <td style="width:34%">Specification</td>
        <td style="width:33%">Observations</td>
	</tr>
	<tr>
	    <td>Description</td>
	    <td>A dark brownish liquid.</td>
	    <td></td>
	</tr>
	<tr>
	    <td>Alkalinity</td>
	    <td>Not less than 0.5ml and not more than 0.8ml of 0.1N HCL is consumed per ml of solution.</td>
	    <td></td>
	 </tr>
	 <tr>
	    <td style="width:100%;">Remarks: The Sample Complies / Does not Comply as per Specification.</td>
	 </tr>
	 <tr>
	    <td style="width:50%;">Analyzed  by /date:</td>
	    <td style="width:50%;">Checked By/ Date :</td>
	 </tr>
</table>


PRE-FILTRATION

PREPARATION OF MEMBRANE FILTER ASSEMBLY FOR PRE-FILTRATION:

Pre-filter the bulk solution with 2/2.0micron glass fibre pre-filter in pressure vessel.
NOTE: Take batch for pre-filtration immediately after manufacturing.

PROCEDURE:<br>
1.	Remove membrane filter (2.0 µ) from packet and wet it in WFI.<br>
2.	Place the 2.2 µ membrane filters on the mesh of assembly.<br>
3.	Put the lid on filters and tie screws uniformly.<br>
4.	Connect the silicon tubing’s.<br>
5.	Use this assembly for pre-filtration of the batch.<br>


Details of filter to be used for Pre-filtration:<br> 

Make: ____________________<br>                           

Lot number/Batch no.:  ___________________<br> 
<table>
    <tr>
        <td>Date</td>
        <td>Pressure vessel I.D. No.</td>
        <td>Membrane Holder I.D.</td>
        <td>Filtration Time</td>
        <td>Done By</td>
        <td>Checked By</td>
    </tr>
</table>
FINAL FILTRATION
NOTE: Take batch for final filtration not later than 24 hours from end of manufacturing
PROCEDURE:<br>

1.0	Remove capsule filter (0.2 µ) from packet and wet it in WFI.<br>
2.0	Operate as per the instruction given on it.<br>
3.0	Sterilize in double door Autoclave as per validated sterilization cycle.<br>
4.0	Sterilize filling vessels also, to collect sterile product.<br>
5.0	Assemble the capsule filtration assembly in the filtration room.<br>
6.0	Check the integrity of the filter and filter the batch as per the SOP/PR/09.<br>
7.0	Filtration by membrane filter<br>
7.1	Remove membrane filter (2.0/2.2 µ and 0.2 µ) from packet and wet it in WFI.<br>
7.2	Place the 0.2 µ membrane filters on the mesh of assembly.<br>
7.3	Then put 2.0 µ filters over 0.2 µ filter.<br>
7.4	Put the lid on filters and tie screws uniformly. Connect the silicon tubing’s.<br>
7.5	Wrap the open ends of tubes with aluminum foil.<br>
7.6	Sterilize in double door Autoclave as per validated sterilization cycle.<br>
7.7	Sterilize filling vessels also, to collect sterile product.<br>
7.8	Assemble the filtration assembly in the filtration room.<br>
7.9	Check the integrity of the filter and filter the pre-filter batch as per the SOP/PR/09<br>

Details of filter to be used for Final-filtration:  <br>

Make: ___________________          <br>                  

Lot no./Batch no. of 0.2µ filters used: ___________________, ___________________	<br>

Make: ________________
Lot no./Batch no. of 2.0/2.2µ Membrane filters used: ___________________, ___________________<br>
 
Bubble point test results before and after filtration:<br>
Date	Time	Test result	Done by
Sign /date	Checked by
Sign./date
BEFORE FILTRATION
		Complies /does not comply		
		Complies /does not comply		
		Complies /does not comply		
AFTER FILTRATION
		Complies /does not comply		
		Complies /does not comply		
		Complies / does not comply		
 
Details of Filtration:
For capsule filtration:
Date	Filling vessel I.D. No.	Capsule filter I.D.	Filtration Time	Done By	Checked By
			From	To		
	SBP/PR/FV/					
	SBP/PR/FV/					
	SBP/PR/FV/					


Date	Filling vessel I.D. No.	Membrane Holder I.D.	Filtration Time	Done By	Checked By
			From	To		
	SBP/PR/FV/	SBP/PR/MF/				
	SBP/PR/FV/	SBP/PR/MF/				
	SBP/PR/FV/	SBP/PR/MF/				

Note: Take batch for filtration immediately not later than 30 hours from end of manufacturing.

1.	Filling to be started only after filter passes Bubble Point test.
2.	In case of failure in post filtration bubble point test, carry out re-filtration, using fresh membrane filter assembly. Use additional copy of relevant pages for documenting re-filtration.

BMR Copy
Primary Packaging Material Issuance Note

Item	Item Code	UOM	Std. Qty.	Medicap lot no	Issued
Qty.	Issued By/Date	Received By/date
5ml ampoules ___________________________________________________		Nos.					



<h3>Primary Packaging Material Returned Note</h3>
<table>
    <tr>
        <td>Item</td>
        <td>Item Code</td>
        <td>UOM</td>
        <td>Medicap lot no</td>
        <td>Qty. Returned</td>
        <td>Returned By/Date</td>
        <td>Received By/date</td>
    </tr>
    <tr>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
    </tr>
</table>
<h3>Stage: - Ampoule Decartoning and Washing Operation.</h3>
<h3>List of equipments</h3>
<table>
    <tr>
        <td rowspan="2">Sr. no.</td>
        <td rowspan="2">Equipment name</td>
        <td rowspan="2">Equipment I.D.</td>
        <td rowspan="2">SOP No.</td>
        <td rowspan="2">Cleaned By</td>
        <td colspan="2">Checked by</td>
    </tr>
    <tr>
        <td>PRD.</td>
        <td>QA</td>
    </tr>
	<tr>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
    </tr>
</table>
<h3>Take line clearance for manufacturing as per below checklist (Ref. Sop no.-SOP/QA/20)</h3>
<table>
    <tr>
        <td>Previous Product:</td>
        <td>Batch No.: 	</td>
    </tr>
    <tr>
        <td>Date:</td>
        <td>Time:</td>
    </tr>
<table>
<table>
    <tr>
        <td style="width:10%;">Sr. no.</td>
        <td style="width:30%;">Checklist</td>
        <td style="width:20%;">Observations</td>
        <td style="width:20%;">Checked by (Prd.)</td>
        <td style="width:20%;">Verified by (QA)</td>
    </tr>
    <tr>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
    </tr>
</table>
<table>
    <tr>
        <td rowspan="2">Activity</td>
        <td colspan="2">Line Clearance</td>
    </tr>
    <tr>
	    <td>Done by Production (Sign/Date)</td>
	    <td>Verified by QA (Sign/Date)</td>
	</tr>
	<tr>
	    <td>Area and Equipment free from previous materials, product and records.</td>
	    <td>Verification of updated logs</td>
	</tr>
</table>
<div></div>
<table cellpadding="3">
    <tr>
        <td>Decartoning</td>
    </tr>
    <tr>
        <td>Decartoning the ampoules from cardboard boxes and load to hopper of through the decartoning area ampoule washing machine as per 
            SOP No.: SOP/PR/14
        </td>
    </tr>
    <tr>
        <td>Date</td>
        <td>Shift</td>
        <td>Decartoning done by</td>
        <td>Checked by Prd. (Sign./Date)</td>
    </tr>
    <tr>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
    </tr>
</table>
Ampoule Washing and Sterilization<br>
No. of ampoules issued for washing: ___________________ no’s.<br>
Carry out washing of ampoules (with Recycled water, Purified Water, Water for Injection and Compressed air) using Automatic Ampoule Washing Machine. The washed ampoules are then subjected to depyrogenation/sterilization tunnel as per validated cycle. Record washing machine parameters every one hour in the following table.<br>
Type of Ampoules (____________________________________________)	Complies / Does not Comply<br>
Washing / sterilization/depyrogenation date :  ____________          Washing started at:   ___________ <br>
Water jets checked by: (prior to washing)  __________Washing &sterilization carried out by _______<br>
Date & Time	Recycled water jet (1.5– 2.5 kg/cm2)	Fresh purified water pulse  (1.5 -2.5kg/cm2)	Compressed air pressure        (2.0-3.0kg/cm2)	Fresh WFI pulse            (1.5-2.5 kg/cm2)	Checked by Prd.<br>
					

Washing Completed at: Date   _______________, Time: _____________<br>


Washing rejections: __________________nos.<br>


Depyrogenation /Sterilization Tunnel record<br>
Tunnel Heaters Put ON at ________hours and wait till it attains the required temperature as per validated cycle.<br> 
Tunnel Temperature 3050C attained Date________   & Time _________.  Temperature  _________<br>
Tunnel Heaters OFF at ________ hours. Tunnel stopped at______________ hours.<br> 
Record the parameters in the below table at every one hour.<br>

Date /Time	Tunnel pressure (mm of water)	Tunnel temp.     (limit: 305 to 3350C)	Conveyor speed (mm/min)	Checked by Prd.<br>
	Drying zone
(7-15mm WC)	Hot zone
(18-24mm WC)	Cooling zone
(7-15 mm WC)			
				Entry temp.	Exit temp.		
							
Stage: - Ampoule Filling and Sealing Operation
List of equipment
<table>
    <tr>
        <td rowspan="2">Sr. no.</td>
        <td rowspan="2">Equipment name</td>
        <td rowspan="2">Equipment I.D.</td>
        <td rowspan="2">SOP No.</td>
        <td rowspan="2">Cleaned By</td>
        <td colspan="2">Checked by</td>
    </tr>
    <tr>
        <td>PRD.</td>
        <td>QA</td>
    </tr>
    <tr>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
    </tr>
</table>			
<h3>Checklist given for line clearance of ampoule filling and sealing.</h3>
<table cellpadding="3">
    <tr>
        <td>Previous Product:</td>
        <td>Batch No.:</td>
    </tr>
    <tr>
        <td>Date:</td>
        <td.Time:</td>
    </tr>
</table>
<table cellpadding="3">
    <tr>
        <td>Sr. no.</td>
        <td>Checklist</td>
        <td>Observation</td>
        <td>Checked. By (Prod.)</td>
        <td>Verified By (QA)</td>
    </tr>
    <tr>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
    </tr>
</table>
<h3>Line Clearance ForAmpoule Filling and Sealing (as per SOP/QA/20)</h3>
<table>
    <tr>
        <td rowspan="2">Activity</td>
        <td colspan="2">Line Clearance</td>
    </tr>
    <tr>
        <td>Done by Production (Sign/Date)</td>
        <td>Verified by QA (Sign/Date)</td>
    </tr>
    <tr>
        <td></td>
        <td></td>
        <td></td>
    </tr>
</table>
<h3>No. of ampoules given for filling: _____________________nos.</h3>
<table>
    <tr>
        <td>Magnehelic Gauge Differential Pressure Reading</td>
    </tr>
    <tr>
        <td style="width:25%;">Area</td>
        <td style="width:25%;">At the beginning of filling pascal</td>
        <td style="width:25%;">At the end of filling pascal</td>
        <td style="width:25%;">Limit in pascal</td>
    </tr>
    <tr>
        <td>Observed by Prd. Sign./date</td>
        <td></td>
        <td></td>
        <td rowspan="2"></td>
    </tr>
    <tr>
        <td>Checked by QA sign./date</td>
        <td></td>
        <td></td>
    </tr>
    <tr>
        <td>Environment condition</td>
        <td>Start  of filling</td>
        <td>Middle of filling</td>
        <td>End of filling</td>
    </tr>
    <tr>
        <td>Temperature in 0C</td>
        <td></td>
        <td></td>
        <td></td>
    </tr>
    <tr>
        <td>Relative humidity %</td>
        <td></td>
        <td></td>
        <td></td>
 	</tr>
 	<tr>
 	    <td>Prd. Chemist sign./date:</td>
 	    <td></td>
 	    <td></td>
 	    <td></td>
 	</tr>
</table>		
<h3>Ampoule Filling and sealing</h3>
<table>
    <tr>
        <td>Machine ID No.</td>
        <td>Filling started at</td>
        <td>Filling completed at</td>
        <td>Duration of filling</td>
    </tr>
    <tr>
        <td colspan="2">Production Chemist:</td>
        <td colspan="2">Filling done by:</td>
    </tr>
</table>



Parameter checking:
Parameter	Oxygen  pressure	LPG pressure	Nitrogen Pressure	Done by
PRD.         Sign.	Checked by QA Sign.
	Limits		
Date/Time	Intervals	0.3 to 2 kg/cm²	0.3 to 1 kg/cm²	NLT 0.3  kg/cm²		
	Initial					
	Middle					
	End				
<div>
    In Process Checks during Ampoules Filling & Sealing
    <p>Check:  Fill volume (limit: 5.0ml to 5.75ml) at every 30 minutes. Target volume - 5.2 ml</p>
</div>
<table cellpadding="3">
    <tr>
        <td style="width:12%" rowspan="2">Date/Time</td>
        <td style="width:40%" colspan="8">Volume at Each station(ml)</td>
        <td style="width:12%" rowspan="2">itrogen Purging Before filling (ok/not ok)</td>
        <td style="width:12%" rowspan="2">Nitrogen Purging After filling (ok/not ok)</td>	
        <td style="width:12%" rowspan="2">Done By</td>
        <td style="width:12%" rowspan="2">Ckd.  By</td>
    </tr>
    <tr>
        <td style="width:5%">1</td>
        <td style="width:5%">2</td>
        <td style="width:5%">3</td>
        <td style="width:5%">4</td>
        <td style="width:5%">5</td>
        <td style="width:5%">6</td>
        <td style="width:5%">7</td>
        <td style="width:5%">8</td>
    </tr>
	<tr>
	    <td></td>
	    <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
	    <td>Ok / Not ok</td>
	    <td>Ok / Not ok</td>
	    <td></td>
	    <td></td>
    </tr>
</table>


In Process Checks: Record Ampoules Sealing Height at Every one hour
Date/Time	Sealing Height (Limit 76 - 78mm)	Done By	Ckd. by
	1	2	3	4	5	6	7	8		
										
									
Number of ampoules filled and sealed: ________________<br>

Rejections during filling and sealing: _________________<br>

Samples taken during filling and sealing: ______________<br>

Stage - Autoclave Operation for ampoule leak test of filled and sealed ampoules.<br>

Checklist for Line Clearance For Autoclave (Line clearance as per SOP/QA/20)<br>
<table>
    <tr>
        <td>Sr. no.</td>
        <td>Equipment name</td>
        <td>Equipment I.D.</td>
        <td>SOP No.</td>
        <td>Cleaned By</td>
        <td>Checked by</td>
        <td>PRD.</td>
        <td>QA</td>
    </tr>
    <tr>
        <td>1.</td>
        <td>Double Door Autoclave</td>
        <td>SBP/PR/AT/11</td>
        <td>SOP/PR/10</td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
    </tr>
</table>
Previous Product:  Batch No.:<br>
Date:		       Time:<br>
<table>
    <tr>
        <td>Sr. no.</td>
        <td>Checklist</td>
        <td>Observation</td>
        <td>Checked By (Prod.)</td>
        <td>Verified By (QA)</td>
    </tr>
    <tr>
        <td>1.</td>
        <td>Check and ensure proper working of  Air locking System, Pressure Differentials, HVAC system</td>
        <td>Satisfactory /    Not satisfactory</td>
        <td></td>
        <td></td>
    </tr>
    <tr>
        <td>2.</td>
        <td>Check and ensure that all employees of the respective area are in proper gowning</td>
        <td>Satisfactory /   Not satisfactory</td>
        <td></td>
        <td></td>
    </tr>
</table>

Activity	Line Clearance
	Done by Production
(Sign/Date)	Verified by QA
(Sign/Date)
Area and Equipment free from previous materials, product and records.		
Verification of updated logs.		



Leak Test of Filled and Sealed Ampoules
Date	Cycle no.	Time	Hold time	Vacuum
					
					
					
Production Chemist:                                                         Checked by QA:
Sign./Date:                                                                          Sign./Date:



<h3>BATCH RECONCILIATION</h3>
<table cellpadding="3">
    <tr>
        <td style="width:10%">Sr. no.</td>
        <td style="width:40%">Particulars</td>
        <td style="width:25%">Volume</td>
        <td style="width:25%">Number of Ampoules</td>
    </tr>
    <tr>
        <td>1.</td>
        <td>Batch Size</td>
        <td></td>
        <td></td>
    </tr>
    <tr>
        <td>2.</td>
        <td>Quantity Manufactured</td>
        <td></td>
        <td></td>
    </tr>
    <tr>
        <td>3.</td>
        <td>Sample taken during Manufacturing</td>
        <td></td>
        <td></td>
    </tr>
    <tr>
        <td>4.</td>
        <td>Rejections during Manufacturing</td>
        <td></td>
        <td></td>
    </tr>
    <tr>
        <td>5.</td>
        <td>Sample taken during filtration</td>
        <td></td>
        <td></td>
    </tr>
    <tr>
        <td>6.</td>
        <td>Total (3+4+5) - 2</td>
        <td></td>
        <td></td>
    </tr>
    <tr>
        <td>7.</td>
        <td>Quantity Filled</td>
        <td></td>
        <td></td>
    </tr>
    <tr>
        <td>8.</td>
        <td>Rejections during Filling</td>
        <td></td>
        <td></td>
    </tr>
    <tr>
        <td>9.</td>
        <td>Samples taken during Filling</td>
        <td></td>
        <td></td>
    </tr>
    <tr>
        <td>10.</td>
        <td>Sample taken after leak test</td>
        <td></td>
        <td></td>
    </tr>
    <tr>
        <td>11.</td>
        <td>Total (8+9+10)</td>
        <td></td>
        <td></td>
    </tr>
    <tr>
        <td>12.</td>
        <td>Total Qty.mfg. (6+11)</td>
        <td></td>
        <td></td>
    </tr>
    <tr>
        <td>13.</td>
        <td>Manufacturing yield (12/1x100)</td>
        <td></td>
        <td></td>
    </tr>
</table>
Calculation done by PRD.:              			             Checked by QA:
Date/sign:							  Date/sign:

DOCUMENT REVIEW:
Enclosures	Attached
Yes / No	Production (Sign)	QA            (Sign)
1. 	Release Slips of QC Water For Injections.			
2.  	Issue Slips of dispensed material.			
3.	Ampoule sterilization/Depyrogenation Print out.			
4.	Garment, Machine parts & Accessories sterilization print out.			
5.    Print out of ampoules leak test.			
6.     Deviation: Yes / No			
Remarks (if any) :


COMPLETED BMR CHECKED BY  :
PRODUCTION 


(Signature & Date) 	QUALITY ASSURANCE 


(Signature & Date)

';
EOD;
$pdf->writeHTML($html, true, false, false, false, '');
$pdf->Output('BMR.pdf', 'I');
            }
        }
    }
    
} else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
?>