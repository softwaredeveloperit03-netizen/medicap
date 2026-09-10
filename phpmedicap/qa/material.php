<?php
//  ini_set('display_errors', 1);
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


if ($_GET["type"] == "getMaterialReqDetails") {
    $data = array();
    
    $output = array();
    $sql = "SELECT * FROM storage_condition";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    $data["storages"] = $output;
    
    $output = array();
    $sql = "SELECT * FROM shelf";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    $data["shelfs"] = $output;
    
    $output = array();
    $sql = "SELECT * FROM room WHERE material_type='".$_GET["material_type"]."'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    $data["rooms"] = $output;
    
    $output = Array();
    $sql = "SELECT * FROM color";
    $result = $conn->query($sql);
    if($result->num_rows > 0){
        while($row = $result->fetch_assoc()){
            $output[] = $row;
        }
    }
    $data["colors"] = $output;
    
    $output = array();
    $sql = "SELECT * FROM packing_style";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    $data["styles"] = $output;
    echo json_encode($output);
}
// if ($_GET["type"] == "saveMaterial") {
//     if(!isset($input["material_subtype"])) {
// 		$input["material_subtype"] = "";
// 	}
// 	if(!isset($input["cas_name"])) {
// 		$input["cas_name"] = "";
// 	}
// 	if(!isset($input["upac_name"])) {
// 		$input["upac_name"] = "";
// 	}
// 	if(!isset($input["grade"])) {
// 		$input["grade"] = "";
// 	}
// 	$material_type = $input["material_type"];
// 	$value1 = $material_type[0];
// 	$material_subtype = $input["material_subtype"];
// 	$value2 = $material_subtype[0];
// 	$material_name = $input["material_name"];
// 	$value3 = $material_name[0] . $material_name[1];
	
// 	$m_no = "";
// 	$m_id1 = 0;
// 	$input["upac_name"] = str_replace("'","\'",$input["upac_name"]);
// 	$sql = "SELECT IFNULL(MAX(m_id1), 0) as m_id1 FROM material WHERE material_type='".$input["material_type"]."' AND material_subtype='".$input["material_subtype"]."'";
// 	$result = $conn->query($sql);
// 	if ($result->num_rows > 0) {
// 	    while ($row = $result->fetch_assoc()) {
// 	        $m_id1 = $row["m_id1"];
// 	        break;
// 	    }
// 	}
// 	$m_id1++;
// 	$val1 = (int)(strlen($m_id1));
// 	if ($val1 ==1) {
// 	    $m_no = $value1."".$value2."".$value3."00".$m_id1;
// 	} else if ($val1 ==2) {
// 	    $m_no = $value1."".$value2."".$value3."0".$m_id1;
// 	} else if ($val1 >= 3) {
// 	    $m_no = $value1."".$value2."".$value3."".$m_id1;
// 	}
// 	$m_no = strtoupper($m_no);
// 	$sql = "INSERT INTO material (user_no, material_type,material_subtype,material_code,material_name,cas_name,upac_name,grade,material_nature,entry_by,entry_date, m_id1, location, thera, storage_condition, special_grade, order_qty, category, retest, shelf_life, min_shelf, lead_time, equivalent, unit, hsn, isprinted, for_product, product_code, inventory) VALUES ('".$_GET["user_no"]."','".$input["material_type"]."', '".$input["material_subtype"]."','".$m_no."','".$input["material_name"]."','".$input["cas_name"]."','".$input["upac_name"]."','".$input["grade"]."','".$input["material_nature"]."','".$_GET["emp_id"]."','$entry_date','$m_id1', '".$input["location"]."', '".$input["thera"]."', '".$input["storage_condition"]."', '".$input["special_grade"]."', '".$input["order_qty"]."', '".$input["category"]."', '".$input["retest"]."', '".$input["shelf_life"]."', '".$input["min_shelf"]."', '".$input["lead_time"]."', '".json_encode($input["equivalent"])."', '".$input["unit"]."', '".$input["hsn"]."', '".$input["isprinted"]."', '".$input["for_product"]."', '".$input["product_code"]."', '".$input["inventory"]."')";
// 	if($conn->query($sql)) {
// 		echo "{\"status\":\"success\"}";
// 	} else {
// 		echo "{\"status\":\"".$conn->error."\"}";
// 	}
// }

if ($_GET["type"] == "saveMaterial") {

    if ($input["material_type"] == "Packing Material") {
        $input["upac_name"] = "";
        $input["cas_name"] = "";
        $input["grade"] = "";
        $input["material_nature"] = "";
    } else {
        $input["category"] = "";
    }

	$input["upac_name"] = str_replace("'","\'",$input["upac_name"]);
	  if($material_type=="Raw Material")
        {   
            $material_code="RM00".$last_id;
        }
        else if($material_type=="Packing Material")
        {   
            $material_code="PM00".$last_id;
        }
	$sql = "INSERT INTO material (user_no, material_type,material_subtype,material_name,cas_name,upac_name,grade,material_nature,entry_by,entry_date, location, storage_condition, order_qty, retest, shelf_life, min_shelf, lead_time, unit, hsn, inventory, category) VALUES ('".$_GET["user_no"]."','".$input["material_type"]."', '".$input["material_subtype"]."','".$input["material_name"]."','".$input["cas_name"]."','".$input["upac_name"]."','".$input["grade"]."','".$input["material_nature"]."','".$_GET["emp_id"]."','$entry_date', '".$input["location"]."', '".$input["storage_condition"]."', '".$input["order_qty"]."', '".$input["retest"]."', '".$input["shelf_life"]."', '".$input["min_shelf"]."', '".$input["lead_time"]."', '".$input["unit"]."', '".$input["hsn"]."', '".$input["inventory"]."', '".$input["category"]."')";
     //echo $sql;
	if($conn->query($sql)) {
		echo "{\"status\":\"success\"}";
	} else {
		echo "{\"status\":\"".$conn->error."\"}";
	}
}
else if ($_GET["type"] == "getUnits") {
    $output = Array();
    $sql = "SELECT * FROM unit";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getMaterialsLog") {
    $output = Array();
    $sql = "SELECT m.*, p.product_name, v.vendor_name FROM material m LEFT JOIN product p ON m.product_code=p.product_code LEFT JOIN vendor v ON m.cylinder_with=v.vendor_no WHERE m.material_type LIKE'%".$_GET["material_type"]."%' AND m.material_subtype LIKE '%".$_GET["material_subtype"]."%' AND m.material_name LIKE '%".$_GET["material_name"]."%' AND m.status='approve' ORDER BY m.material_type, m.material_subtype, m.material_name, m.grade";
      $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $output[] = $row;
            }
        }
    echo json_encode($output);
}else if ($_GET["type"] == "getMaterialsDetails") {
    $sql = "SELECT * FROM material WHERE id='".$_GET["id"]."'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row["equivalent"] = json_decode($row["equivalent"]);
            echo json_encode($row);
            break;
        }
    } else {
        echo "{}";
    }
} else if ($_GET["type"] == "getMaterials") {
    $output = Array();
    $sql = "SELECT * FROM material WHERE user_no='".$_GET["user_no"]."' AND status='approve' ORDER BY material_name";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row["equivalent"] = json_decode($row["equivalent"]);
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getPackingMaterials") {
    $output = Array();
    $sql = "SELECT * FROM material WHERE user_no='".$_GET["user_no"]."' AND material_type='Packing Material' AND status='approve' AND material_subtype LIKE '%".$_GET["material_type"]."%' AND status LIKE '%".$_GET["status"]."%' ORDER BY material_name";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row["equivalent"] = json_decode($row["equivalent"]);
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "deleteMaterial") {
    $sql = "UPDATE material SET status='Deleted' WHERE id='".$_GET["id"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"".$conn->error."\"}";
    }
} else if ($_GET["type"] == "updateMaterial") {
    $sql = "UPDATE material SET material_type='".$input["material_type"]."', material_category='".$input["material_category"]."', material_subtype='".$input["material_subtype"]."', material_nature='".$input["material_nature"]."', category='".$input["category"]."', cylinder_with='".$input["cylinder_with"]."', for_product='".$input["for_product"]."', product_code='".$input["product_code"]."', material_name='".$input["material_name"]."', grade='".$input["grade"]."', special_grade='".$input["special_grade"]."', order_qty='".$input["order_qty"]."', inventory='".$input["inventory"]."', unit='".$input["unit"]."', thera='".$input["thera"]."', retest='".$input["retest"]."', lead_time='".$input["lead_time"]."', shelf_life='".$input["shelf_life"]."', storage_condition='".$input["storage_condition"]."', room_no='".$input["room_no"]."', equivalent='".json_encode($input["equivalent"])."' WHERE id='".$input["id"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"".$conn->error."\"}";
    }
} else if ($_GET["type"] == "deleteMaterial") {
    $sql = "UPDATE material SET status='DELETED' WHERE id='".$_GET["id"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"".$conn->error."\"}";
    }
} else if ($_GET["type"] == "getApprovedMaterials") {
    $output = Array();
    $sql = "SELECT * FROM material WHERE user_no='".$_GET["user_no"]."' AND status='approve' ORDER BY material_name";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row["equivalent"] = json_decode($row["equivalent"]);
            $output[] = $row;
        }
    }
    echo json_encode($output);
}
    else if($_GET['type'] == 'materialmasterpdf') {
        $sql = "SELECT * FROM material WHERE id='".$_GET['id']."'";
        $result = $conn->query($sql);
        $row = $result->fetch_assoc();
        class MYPDF extends TCPDF {
            public function Header() {
                $_GET['type'] = 'header';
                include '../pdfimp.php';
            }
            public function Footer() {
                $_GET['type'] = 'footer';
                include '../pdfimp.php';
            }
        }
        $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetMargins(15, 45, 15, 15);
        $pdf->SetAutoPageBreak(TRUE, 15);
        $pdf->AddPage('P');
        $pdf->SetFont ('', '', '9' , '', 'default', true );
        $html.='
        <style>td { }</style>
        <table style="border:solid 1px BCBBBA;" cellpadding="5">
            <tr>
                <td style="background-color:#DDDAD9;font-weight:bold;text-align:center;"><b>Material Master</b></td>
            </tr>
        </table>
        <div></div>
        <table style="border:solid 1px BCBBBA;" cellpadding="2">
            <tr>
                <td style="width:19%;"><b>Material ID</b></td>
                <td style="width:15%">:</td>
                <td style="width:14%"><b>Category Type</b></td>
                <td style="width:13%">: '.$row['material_type'].'</td>
                <td style="width:9%"><b>Obsolete</b></td>
                <td style="width:9%"> :</td>
                <td style="width:11%"><b>Item Group</b></td>
                <td style="width:10%">: </td>
            </tr>
            <tr>
                <td><b>Material Name</b></td>
                <td colspan="7">: '.$row['material_name'].'</td>
            </tr>
            <tr>
                <td><b>Short Name</b></td>
                <td colspan="5">:</td>
                <td><b>Spec.</b></td>
                <td>: IP</td>
            </tr>
            <tr>
                <td><b>Unit of Measurement</b></td>
                <td colspan="7">: '.$row['unit'].'</td>
            </tr>
            <tr>
                <td><b>Item Type</b></td>
                <td colspan="3">: RM &nbsp;&nbsp;&nbsp;<b>Sub</b>: ACTIVE &nbsp;&nbsp;&nbsp;<b>Series</b>: ACTIVE</td>
                <td colspan="2"><b>Code Requested By</b></td>
                <td colspan="2">: '.$row['entry_by'].'</td>
            </tr>
            <tr>
                <td><b>Material Code</b></td>
                <td style="width:33.33%">: '.$row['material_code'].' &nbsp;&nbsp;&nbsp;<b>Map Code</b> : </td>
                <td style="width:33.33%"><b>Department</b> : </td>
            </tr>
            <tr>
                <td colspan="3" style="font-size:16px;"><b>QA Details</b></td>
            </tr>
        </table>
        <table style="border:solid 1px BCBBBA;" cellpadding="2">
            <tr>
                <td style="width:23%;"><b>For Which</b></td>
                <td style="width:77%;">:</td>
            </tr>
            <tr>
                <td><b>Generic Name</b></td>
                <td>:</td>
            </tr>
            <tr>
                <td><b>CAS No. :</b></td>
                <td>: '.$row['cas_name'].' &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>HS Code :</b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Safety Data</b></td>
            </tr>
            <tr>
                <td><b>Therapeutic Category</b></td>
                <td>:</td>
            </tr>
            <tr>
                <td><b>Storage Condition</b></td>
                <td>: '.$row['storage_condition'].'<b>Avg. Temperature</b> :</td>
            </tr>
            <tr>
                <td><b>Shelf Life</b></td>
                <td style="width:17%;">: '.$row['shelf_life'].'</td>
                <td style="width:30%;"><b>Retest After</b> : '.$row['retest'].'</td>
                <td style="width:30%;"><b>Min.Shelf Life</b> : '.$row['min_shelf'].'</td>
            </tr>
            <tr>
                <td><b>Sample Qty.</b></td>
                <td style="width:77%;">: 26.000 GRAM &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Testing Charges</b>: INR</td>
            </tr>
            <tr>
                <td><b>Re-Test Sample Qty</b></td>
                <td>: 26.000 GRAM</td>
            </tr>
            <tr>
                <td><b>Q.C. Lead Time</b></td>
                <td>: 14 Days</td>
            </tr>
            <tr>
                <td><b>Mfg. Dt. Required</b></td>
                <td>: Yes <b>Retest Dt. Required</b> : No</td>
            </tr>
            <tr>
                <td><b>Expiry Dt. Required</b></td>
                <td>: Yes <b>Best Before Dt. Required</b> : No   <b>Date Format</b> : MON-RRRR</td>
            </tr>
            <tr>
                <td><b>COA Rule</b></td>
                <td>:</td>
            </tr>
            <tr>
                <td><b>Purchase Specification</b></td>
                <td>: </td>
            </tr>
            <tr>
                <td>Equivalent to Factor</td>
            </tr>
            <tr>
                <td>CEFTRIAXONE 1.07900</td>
            </tr>
            <tr>
                <td style="font-size:16px; width:100%;"><b>Purchase Details</b></td>
            </tr>
        </table>
        <table style="border:solid 1px BCBBBA;" cellpadding="2"> 
            <tr>
                <td style="width:25%;">
                    <b>Excess Qty For Planning</b><br>
                    <b>Min. Available Pack</b><br>
                    <b>Art Work No.</b>
                </td>
                <td style="width:25%;">:  %<br>: NOS<br>: ALFC020/15-16</td>
                <td style="width:50%;">
                    <b>Maximum Tolerance Qty.</b> : 10.000 KG<br>
                    <table cellpadding="2" align="center">
                        <tr><td style="border:solid 1px BCBBBA;">Over Qty Delivery Tolerance Range</td></tr>
                        <tr><td style="border:solid 1px BCBBBA;">From - To Tolerance %</td></tr>
                        <tr><td style="border:solid 1px BCBBBA;">1 500 100.00</td></tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td>DPCO Rate INR</td>
            </tr>
            <tr>
                <td style="width:100%;">
                    <table style="border:solid 1px BCBBBA;" border="1" cellpadding="2">
                        <tr style="background-color:#DDDAD9;font-weight:bold;font-size:9px;">
                            <td>Location</td>
                            <td>Company</td>
                            <td>Code By Loan Licensee</td>
                            <td>Min Stk. Level KG</td>
                            <td>Re-Order Level KG</td>
                            <td>Min Order Qty KG</td>
                            <td>Std. Cost (NOM) Bank Rate</td>
                            <td>Avg.Proc. Apr.Mfgr</td>
                            <td>Approve UnderTest</td>
                            <td>Quarantine Rejected</td>
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
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td style="font-size:16px;">Additional Information</td>
            </tr>
        </table>';
        EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('material.pdf', 'I');
    }
    else if($_GET['type'] == 'materialmasterlogrd') {
        $_GET['filename'] = 'Service List'; 	$_GET['pdftype']= 'onlyheader';  include('../pdfimp2.php');
        $html.="";
        $html.='<table cellpadding="5" border="1">
            <thead>
                <tr style="background-color:#DCDCDC;font-weight:bold;">
                    <td style="width:10%;text-align:center;">Sr No.</td>
                    <td style="width:20%;text-align:center;">Date</td>
                    <td style="width:20%;text-align:center;">Service Code</td>
                    <td style="width:20%;text-align:center;">Department </td>
                    <td style="width:15%;text-align:center;">Service Type</td>
                    <td style="width:15%;text-align:center;">Service Title</td>
                    

                </tr>
            </thead>';
            $i=1;
        // $sql = "SELECT * FROM service WHERE status!='Deleted' ";
        // $sql = "SELECT m.*,c.LglNm FROM material m LEFT JOIN client c ON m.client_code=c.client_code WHERE m.material_type='".$_GET["material_type"]."' AND m.status='approve' AND m.material_subtype LIKE '%".$_GET["material_subtype"]."%' AND m.material_nature LIKE '%".$_GET["material_nature"]."%' AND grade LIKE '%".$_GET["grade"]."%'";
        // $sql = "SELECT m.*,c.LglNm FROM material m LEFT JOIN client c ON m.client_code=c.client_code 
        // WHERE m.plant_id= '".$_GET["plant_id"]."' AND m.material_type='".$_GET["material_type"]."' AND m.status='approve' ORDER BY m.id DESC";
           $sql = "SELECT * FROM service ";

        $result = $conn->query($sql);
        $i = 1;
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $row = array_map('utf8_encode', $row);
            $html.='
                <tbody>
                    <tr nobr="true">
                        <td style="width:10%;text-align:center;">'.$i.'.</td>
                        <td style="width:20%;text-align:center;">'.date('d-m-y',strtotime($row['entry_date'])).'</td>
                        <td style="width:20%;text-align:center;">'.$row['service_code'].'</td>
                        <td style="width:20%;text-align:center;">'.$row['department'].'</td>
                        <td style="width:15%;text-align:center;">'.$row['service_type'].'</td>';
                        if($row['material_nature']){
                         $html.='<td style="width:15%;">'.$row['service_title'].'</td>';
                        }else{
                        $html.='<td style="width:15%;text-align:center;">NA</td>';

                        }
                        $i++;
                        
                        
                   $html.='  </tr>
                </tbody>';
                }
            }
            $html.='
        </table>';
       // EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('material.pdf', 'I');
    }
    
        else if($_GET['type'] == 'OtherMaterialMasterLog') {
        $_GET['filename'] = 'Service List'; 	$_GET['pdftype']= 'onlyheader';  include('../pdfimp2.php');
        $html.="";
        $html.='<table cellpadding="5" border="1">
            <thead>
                <tr style="background-color:#DCDCDC;font-weight:bold;">
                   
                    <td style="width:16.67%;">Material Type</td>
                    <td style="width:18.67%;">Material SubType</td>
                    <td style="width:16.67%;">Material Code</td>
                    <td style="width:16.67%;">Material Name</td>
                    <td style="width:16.67%;">Entry Date</td>
                    <td style="width:14.67%;">Entry By</td>
                </tr>
            </thead>';
         
            $sql = "SELECT * FROM others_material WHERE plant_id='".$_GET["plant_id"]."' 
                 order by 1 desc";
               $result = $conn->query($sql);
          if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
            $html.='
             <tbody><tr>
                <td style="width:16.67%;">' . $row['material_type'] . '</td>
                <td style="width:18.67%;">' . $row['material_subtype'] . '</td>
                <td style="width:16.67%;">' . $row['material_code'] . '</td>
                <td style="width:16.67%;">' . $row['material_name'] . '</td>
                  <td style="width:16.67%;">' . date('d-m-y', strtotime($row['entry_date'])) . '</td>
                <td style="width:14.67%;">' . $row['entry_by'] . '</td>
             </tr></tbody>';
                }
            }
            $html.='
        </table>';
      
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('OtherMaterial.pdf', 'I');
    }
    
    
    else if($_GET['type'] == 'materialmasterlog') {
        $_GET['filename'] = 'Service List'; 	$_GET['pdftype']= 'onlyheader';  include('../pdfimp2.php');
        $html.="";
        $html.='<table cellpadding="5" border="1">
            <thead>
                <tr style="background-color:#DCDCDC;font-weight:bold;">
                    <td style="width:10%;">Sr.No</td>
                    <td style="width:12%;">Date</td>
                    <td style="width:23%;">Material Code</td>
                    <td style="width:12%;">Material Type</td>
                    <td style="width:23%;">Material Name</td>
                    <td style="width:20%;">Entry By</td>


                    
                   
                </tr>
            </thead>';
            $i=1;

   $sql = "SELECT DISTINCT m.material_code, m.*,c.LglNm FROM material m LEFT JOIN client c ON m.client_code=c.client_code 
        WHERE m.plant_id= '".$_GET["plant_id"]."' AND m.material_type='".$_GET["material_type"]."' AND m.status='approve' ORDER BY m.id DESC";
$result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
            $html.='
             
                    <tr >
                        <td style="width:10%;">'.$i++.'.</td>
                        <td style="width:12%;">'.date('d-m-y',strtotime($row['entry_date'])).'</td>
                        <td style="width:23%;">'.$row['material_code'].'</td>
                        <td style="width:12%;">'.$row['material_type'].'</td>
                        <td style="width:23%;">'.$row['material_name'].'</td>
                        <td style="width:20%;">'.$row['entry_by'].'</td>


                    
                        
                  </tr>';
                }
            }
            $html.='
        </table>';
      
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('material.pdf', 'I');
    }
    else if($_GET['type'] == 'materialmasterlogabc') {
        $_GET['filename'] = 'Service List'; 	$_GET['pdftype']= 'onlyheader';  include('../pdfimp2.php');
        $html.="";
        $html.='<table cellpadding="5" border="1">
            <thead>
                <tr style="background-color:#DCDCDC;font-weight:bold;">
                    <td style="width:10%;">Sr.No</td>
                    <td style="width:20%;">Date</td>
                    <td style="width:20%;">Material Code</td>
                    <td style="width:20%;">Material Type</td>
                    <td style="width:15%;">Material Name</td>
                    <td style="width:15%;">	Material Nature</td>
                </tr>
            </thead>';
            $i=1;
        // $sql = "SELECT * FROM service WHERE status!='Deleted' ";
       // $sql = "SELECT m.*,c.LglNm FROM material m LEFT JOIN client c ON m.client_code=c.client_code WHERE m.material_type='".$_GET["material_type"]."' AND m.status='approve' AND m.material_subtype LIKE '%".$_GET["material_subtype"]."%' AND m.material_nature LIKE '%".$_GET["material_nature"]."%' AND grade LIKE '%".$_GET["grade"]."%'";

    $sql="SELECT * FROM `material` WHERE material_type='Packing Material' order by id desc";

 
$result = $conn->query($sql);
$i=1;
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $row = array_map('utf8_encode', $row);
            $html.='
                <tbody>
                    <tr nobr="true">
                        <td style="width:10%;">'.$i.'.</td>
                        <td style="width:20%;">'.date('d-m-y',strtotime($row['entry_date'])).'</td>
                        <td style="width:20%;">'.$row['material_code'].'</td>
                        <td style="width:20%;">'.$row['material_type'].'</td>
                        <td style="width:15%;">'.$row['material_name'].'</td>';
                        if($row['material_nature']){
                         $html.='<td style="width:15%;">'.$row['material_nature'].'</td>';
                        }else{
                        $html.='<td style="width:15%;">NA</td>';

                        }
                        
                        
                   $html.='  </tr>
                </tbody>';
                $i++;
                }
            }
            $html.='
        </table>';
        // EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('material.pdf', 'I');
    }
    else if($_GET['type'] == 'materialmasterlogpr') {
        $_GET['filename'] = 'Service List'; 	$_GET['pdftype']= 'onlyheader';  include('../pdfimp2.php');
        $html.="";
        $html.='<table cellpadding="5" border="1">
            <thead>
                <tr style="background-color:#DCDCDC;font-weight:bold;">
                    <td style="width:10%;">Sr.No</td>
                    <td style="width:20%;">Date</td>
                    <td style="width:20%;">Product Code</td>
                    <td style="width:20%;">Product name</td>
                    <td style="width:15%;">Product Nature</td>
                    <td style="width:15%;">Product Name</td>
                </tr>
            </thead>';
            $i=1;
        // $sql = "SELECT * FROM service WHERE status!='Deleted' ";
        // $sql = "SELECT m.*,c.LglNm FROM material m LEFT JOIN client c ON m.client_code=c.client_code WHERE m.material_type='".$_GET["material_type"]."' AND m.status='approve' AND m.material_subtype LIKE '%".$_GET["material_subtype"]."%' AND m.material_nature LIKE '%".$_GET["material_nature"]."%' AND grade LIKE '%".$_GET["grade"]."%'";
         $sql = "SELECT * FROM product  WHERE plant_id='".$_GET["plant_id"]."'  order by 1 desc";
 
        $result = $conn->query($sql);
        $i=1;
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $row = array_map('utf8_encode', $row);
            $html.='
                <tbody>
                    <tr nobr="true">
                        <td style="width:10%;">'.$i.'.</td>
                        <td style="width:20%;">'.date('d-m-y',strtotime($row['entry_date'])).'</td>
                        <td style="width:20%;">'.$row['product_code'].'</td>
                        <td style="width:20%;">'.$row['product_name'].'</td>
                        <td style="width:15%;">'.$row['product_nature'].'</td>';
                        if($row['material_nature']){
                         $html.='<td style="width:15%;">'.$row['material_nature'].'</td>';
                        }else{
                        $html.='<td style="width:15%;">NA</td>';

                        }
                        
                        
                   $html.='  </tr>
                </tbody>';
                $i++;
                }
            }
            $html.='
        </table>';
        // EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('material.pdf', 'I');
    }
    else if ($_GET["type"] == "saveCategory") {
        $sql = "INSERT INTO packing_category (category,plant_id) VALUES ('".$_GET["category"]."','".$_GET["plant_id"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getCategories") {
        $output = Array();
        $sql = "SELECT * FROM packing_category where plant_id='".$_GET["plant_id"]."' order by category";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "editMaterial") {
        $sql = "UPDATE material SET material_type='".$input["material_type"]."', material_category='".$input["material_category"]."',material_subtype= '".$input["material_subtype"]."', material_name='".$input["material_name"]."', cas_name='".$input["cas_name"]."', upac_name='".$input["upac_name"]."', grade='".$input["grade"]."', material_nature='".$input["material_nature"]."', location='".$input["location"]."', thera='".$input["thera"]."',storage_condition='".$input["storage_condition"]."', special_grade='".$input["special_grade"]."', order_qty='".$input["order_qty"]."',category='".$input["category"]."',retest='".$input["retest"]."', shelf_life='".$input["shelf_life"]."',min_shelf='".$input["min_shelf"]."',lead_time='".$input["lead_time"]."', equivalent='".json_encode($input["equivalent"])."',unit='".$input["unit"]."', hsn='".$input["hsn"]."',isprinted='".$input["isprinted"]."',for_product='".$input["for_product"]."', product_code='".$input["product_code"]."',inventory='".$input["inventory"]."',room_no='".$input["room_no"]."' WHERE id='".$input["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }else if($_GET['type'] == 'materialpackinglog') {
        $_GET['filename'] = 'Packing Materials'; $_GET['pdftype'] = 'landscape'; include("../pdfimp.php");
        $html.="";
        $html.='
        <table cellpadding="5" border="1">
            <thead>
                <tr style="background-color:#DCDCDC;font-weight:bold;">
                    <td style="width:5%; text-align:centre;"><b>Sr.</b></td>
                    <td style="width:15%; text-align:centre;"><b>Material Type</b></td>
                    <td style="width:10%; text-align:centre;"><b>Material Code</b></td>
                    <td style="width:20%; text-align:centre;"><b>Material Name</b></td>
                    <td style="width:10%; text-align:centre;"><b>Category</b></td>
                    <td style="width:10%; text-align:centre;"><b>Min. Order Qty</b></td>
                    <td style="width:15%; text-align:centre;"><b>Min. Inventory</b></td>
                    <td style="width:15%; text-align:centre;"><b>Storage Location</b></td>
                </tr>
            </thead>
            <tbody>';
            $i=1;
            $sql = "SELECT m.*,c.LglNm FROM material m LEFT JOIN client c ON m.client_code=c.client_code WHERE m.material_type='Packing Material' AND m.status='approve' AND m.material_subtype LIKE '%".$_GET["material_subtype"]."%' AND m.material_nature LIKE '%".$_GET["material_nature"]."%' AND m.category LIKE '%".$_GET["category"]."%'";
        //$sql = "SELECT m.*,c.LglNm FROM material m LEFT JOIN client c ON m.client_code=c.client_code WHERE m.material_subtype LIKE '%".$_GET["material_subtype"]."%' AND m.category LIKE '%".$_GET["category"]."%'";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
            $html.='<tr>
                        <td style="width:5%;">'.$i++.'.</td>
                        <td style="width:15%;">'.$row['material_subtype'].'</td>
                        <td style="width:10%;">'.$row['material_code'].'</td>
                        <td style="width:20%;">'.$row['material_name'].'</td>
                        <td style="width:10%;">'.$row['category'].'</td>
                        <td style="width:10%;">'.$row['order_qty'].'</td>
                        <td style="width:15%;">'.$row['inventory'].'</td>
                        <td style="width:15%;">'.$row['location'].'</td>
                    </tr>
                </tbody>';
            }
        }
        $html.='</table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('materialpackinglog.pdf', 'I');
    } 

} else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
?>