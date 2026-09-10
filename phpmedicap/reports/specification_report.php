<?php
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
    

if($result->num_rows > 0) {
    while($row = $result->fetch_assoc()){
	    $string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
	    $string = explode("$",$string);
	    $_GET["emp_id"] = $string[0];
	    $_GET["department"] = $string[1];
	    break;
    }
}

    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    
    if ($_GET["type"]=="getStock") {
        if (!isset($_GET["material_name"])) {
            $_GET["material_name"] = "";
        }
        $sql = "SELECT s.*, m.material_type, m.material_subtype, m.grade, m.material_name, v.vendor_name FROM stock_book s LEFT JOIN material m ON s.material_code=m.material_code LEFT JOIN vendor v ON s.vendor_no=v.vendor_no WHERE s.user_no='".$_GET["user_no"]."' AND m.material_type='Raw Material' AND s.material_code LIKE '%".$_GET["material_code"]."%' AND s.vendor_no LIKE '%".$_GET["vendor_no"]."' AND s.status LIKE '%".$_GET["status"]."' AND m.material_name LIKE '%".$_GET["material_name"]."%'AND m.material_subtype LIKE '%".$_GET["material_subtype"]."%'AND m.grade LIKE '%".$_GET["grade"]."%' ORDER BY s.grn_no";
    	//echo $sql;
    	$result = $conn->query($sql);
    	$output = Array();
    	if($result->num_rows > 0){
    		while ($row = $result->fetch_assoc()) {
    		    $output[] = $row;
    		}
    	}
    	echo json_encode($output);
    }else if ($_GET["type"]=="getAllStock") {
        if (!isset($_GET["material_name"])) {
            $_GET["material_name"] = "";
        }
        $sql = "SELECT s.*, m.material_type, m.material_subtype, m.grade, m.material_name, v.vendor_name FROM stock_book s LEFT JOIN material m ON s.material_code=m.material_code LEFT JOIN vendor v ON s.vendor_no=v.vendor_no WHERE s.user_no='".$_GET["user_no"]."' AND m.material_type='Raw Material' AND s.material_code LIKE '%".$_GET["material_code"]."%' AND s.vendor_no LIKE '%".$_GET["vendor_no"]."' AND s.status LIKE '%".$_GET["status"]."' AND m.material_name LIKE '%".$_GET["material_name"]."%'";
    	$result = $conn->query($sql);
    	$output = Array();
    	if($result->num_rows > 0){
    		while ($row = $result->fetch_assoc()) {
    		    $output[] = $row;
    		}
    	}
    	echo json_encode($output);
    }if ($_GET["type"]=="getApprovedStock") {
        if (!isset($_GET["material_name"])) {
            $_GET["material_name"] = "";
        }
        $sql = "SELECT s.*, m.material_type, m.material_subtype, m.grade, m.material_name, v.vendor_name FROM stock_book s LEFT JOIN material m ON s.material_code=m.material_code LEFT JOIN vendor v ON s.vendor_no=v.vendor_no WHERE s.user_no='".$_GET["user_no"]."' AND m.material_type='Raw Material' AND s.status='Approved'ORDER BY id DESC";
    	$result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["batches"] = json_decode($row["batches"]);
                $row["receiving_details"] = json_decode($row["receiving_details"]);
                $row["weighing_details"] = json_decode($row["weighing_details"]);
                $row["grn_details"] = json_decode($row["grn_details"]);
                
                $output1 = array();
                $sql1 = "SELECT material_code, grade FROM material WHERE material_name='".$row["material_name"]."' AND material_code!='".$row["material_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row["grades"] = $output1;
                $output[] = $row;
            }
        }
    	echo json_encode($output);
    }
    
    if($_GET['type'] == 'SpecificationPDF'){
        $_GET['filename'] = ' '; $_GET['pdftype']= 'onlyheader';  include('../pdfimp2.php');
         $sql = "SELECT s.*, m.material_type, m.material_subtype, m.material_name, m.grade FROM specification s LEFT JOIN material m ON s.material_code=m.material_code WHERE specification_no='".$_GET['specification_no']."' LIMIT 1";
        $result = $conn->query($sql);
        $row = $result->fetch_assoc();
      $html.='
        <h3 style="text-align:center; ">RAW MATERIAL SPECIFICATION</h3>
        <style>
            .tdall { border:solid 1px BCBBBA; }
            .tdb { border-bottom:solid 1px BCBBBA; }
            .tdbr { border-bottom:solid 1px BCBBBA; border-right:solid 1px BCBBBA; }
        </style>
        <table style="border:solid 1px BCBBBA;" cellpadding="5">
            <tr>
                <td class="tdb" style="width:20%;"><b>Department</b></td>
                <td class="tdb" style="width:80%;"> </td>
            </tr>
            <tr>
                <td class="tdb" style="width:20%;"><b>Quality Control</b></td>
                <td class="tdb" style="width:80%;"> </td>
            </tr>
            <tr>
                <td class="tdb" style="width:20%;"><b>Material Name</b></td>
                 <td class="tdbr" style="width:80%;"> '.$row[""].'</td>
            </tr>
            <tr>
                <td class="tdb" style="width:20%;"><b>Material Type</b></td>
                <td class="tdb" style="width:43%;"> '.$row[""].'</td>
                <td class="tdb" style="width:17%;"><b>Material Code</b></td>
                <td class="tdb" style="width:20%;"> '.$row[""].'</td>
                 
                
            </tr>
            <tr>
                <td class="tdb" style="width:20%;"><b>CAS Name</b></td>
                <td class="tdbr" style="width:43%;"> '.$row[''].'</td>
                <td class="tdb" style="width:17%;"><b>Chemical sample Qty</b></td>
                 <td class="tdb" style="width:20%;">'.$row[""].' '.$row["unit"].' </td>
                
                
            </tr>
            <tr>
            <td class="tdb" style="width:20%;"><b>Molecular Formula</b></td>
                <td class="tdbr" style="width:43%;"> '.$row[""].'</td>
                <td class="tdb" style="width:17%;"><b>Molecular Weight</b></td>
                <td class="tdbr" style="width:20%;"> '.$row[''].' </td>
                
            </tr>
            <tr>
                <td class="tdb" style="width:20%;"><b>Specification No</b></td>
                <td class="tdbr" style="width:43%;"> '.$row[''].'</td>
                <td class="tdb" style="width:17%;"><b>Grade</b></td>
                <td class="tdb" style="width:20%;"> '.$row[''].'</td>
            </tr>
            <tr>
                <td class="tdb" style="width:20%;"><b>Version No</b></td>
                <td class="tdbr" style="width:43%;"> '.$row[''].'</td>
                <td class="tdb" style="width:17%;"><b>Supersedes No</b></td>
                <td class="tdb" style="width:20%;"> '.$row[''].'</td>
                </tr>
                <tr>
              <td class="tdb" style="width:20%;"><b>Effective Date</b></td>
                <td class="tdbr" style="width:43%;"> </td>                                   
                <td class="tdb" style="width:17%;"><b>Review Date</b></td>
                <td class="tdb" style="width:20%;">  </td>
            </tr>
            <tr>
                
                <td class="tdb" style="width:20%;"><b>Storage Condition</b></td>
                <td class="tdb" style="width:80%;"> </td>
            </tr>
            
        </table>
        <div></div>
       <h3 style="text-align:center;">Pharmacopoeial Tests</h3>
            <table cellpadding="5" border="1">
                 <tr style="background-color:#DDDAD9; text-align:center;">
                    <td style="width:15%; text-align:centre;"><b>Test Type</b></td>
                    <td style="width:15%; text-align:centre;"><b>Test</b></td>
                    <td style="width:10%; text-align:centre;"><b>Sub Test</b></td>
                    <td style="width:15%; text-align:centre;"><b>Reference Type</b></td>
                    <td style="width:15%; text-align:centre;"><b>Limit Type</b></td>
                    <td style="width:30%; text-align:centre;"><b>Description/Limits</b></td>
                </tr>';
                 
                 $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                       
                    
                 $html.=' <tr>
                    <td style="width:15%; text-align:centre;">'.$row1[''].'</td>
                    <td style="width:15%; text-align:centre;">'.$row1[''].'</td>
                    <td style="width:10%; text-align:centre;">'.$row1[''].'</td>
                    <td style="width:15%; text-align:centre;">'.$row1[''].'</td>
                    <td style="width:15%; text-align:centre;">'.$row1[''].'</td>
                    <td style="width:30%; text-align:centre;">'.$row1[''].'</td>
                </tr>';
                    }
                }
                
        $html.='
        </table><div></div>
        
        
      <h3 style="text-align:center;">Revision History:</h3>
                        <table border="1" cellpadding="3">
                          <tr style="background-color:#DDDAD9; text-align:center;">
                                <td style="width:20%;font-weight:bold;">Sr</td>
                                <td style="width:20%;font-weight:bold;">Revision No</td>
                                <td style="width:20%;font-weight:bold;">Version No</td>
                                <td style="width:20%;font-weight:bold;">Change Mode</td>
                                <td style="width:20%;font-weight:bold;">Reason for change</td>
                            </tr>';
                        $i=1;
                        $sql2 = "SELECT * FROM spec_revision WHERE spec_no='".$row["specification_no"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $html.='<tr>
                                    <td style="width:20%;"></td>
                                    <td style="width:20%;">'.$row2[''].'</td>
                                    <td style="width:20%;">'.$row2[''].'</td>
                                    <td style="width:20%;">'.$row2[''].'</td>
                                    <td style="width:20%;">'.$row2[''].'</td>
                                </tr>';
                            }
                        }
                        $html.='
                        
               
                        
                        </table>';
    
        EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Specification.pdf', 'I');
    }
   
 else  if($_GET['type'] == 'SpecificationDigitalPDF'){
        $_GET['filename'] = ' '; $_GET['pdftype']= 'onlyheader';  include('../pdfimp2.php');
         $sql = "SELECT s.*, m.material_type, m.material_subtype, m.material_name, m.grade FROM specification s LEFT JOIN material m ON s.material_code=m.material_code WHERE specification_no='".$_GET['specification_no']."' LIMIT 1";
        $result = $conn->query($sql);
        $row = $result->fetch_assoc();
      $html.='
        <h3 style="text-align:center; ">RAW MATERIAL SPECIFICATION</h3>
        <style>
            .tdall { border:solid 1px BCBBBA; }
            .tdb { border-bottom:solid 1px BCBBBA; }
            .tdbr { border-bottom:solid 1px BCBBBA; border-right:solid 1px BCBBBA; }
        </style>
        <table style="border:solid 1px BCBBBA;" cellpadding="5">
            <tr>
                <td class="tdb" style="width:20%;"><b>Department</b></td>
                <td class="tdb" style="width:80%;"> </td>
            </tr>
            <tr>
                <td class="tdb" style="width:20%;"><b>Quality Control</b></td>
                <td class="tdb" style="width:80%;"> </td>
            </tr>
            <tr>
                <td class="tdb" style="width:20%;"><b>Material Name</b></td>
                 <td class="tdbr" style="width:80%;"> '.$row[""].'</td>
            </tr>
            <tr>
                <td class="tdb" style="width:20%;"><b>Material Type</b></td>
                <td class="tdb" style="width:43%;"> '.$row[""].'</td>
                <td class="tdb" style="width:17%;"><b>Material Code</b></td>
                <td class="tdb" style="width:20%;"> '.$row[""].'</td>
                 
                
            </tr>
            <tr>
                <td class="tdb" style="width:20%;"><b>CAS Name</b></td>
                <td class="tdbr" style="width:43%;"> '.$row[''].'</td>
                <td class="tdb" style="width:17%;"><b>Chemical sample Qty</b></td>
                 <td class="tdb" style="width:20%;">'.$row[""].' '.$row["unit"].' </td>
                
                
            </tr>
            <tr>
            <td class="tdb" style="width:20%;"><b>Molecular Formula</b></td>
                <td class="tdbr" style="width:43%;"> '.$row[""].'</td>
                <td class="tdb" style="width:17%;"><b>Molecular Weight</b></td>
                <td class="tdbr" style="width:20%;"> '.$row[''].' </td>
                
            </tr>
            <tr>
                <td class="tdb" style="width:20%;"><b>Specification No</b></td>
                <td class="tdbr" style="width:43%;"> '.$row[''].'</td>
                <td class="tdb" style="width:17%;"><b>Grade</b></td>
                <td class="tdb" style="width:20%;"> '.$row[''].'</td>
            </tr>
            <tr>
                <td class="tdb" style="width:20%;"><b>Version No</b></td>
                <td class="tdbr" style="width:43%;"> '.$row[''].'</td>
                <td class="tdb" style="width:17%;"><b>Supersedes No</b></td>
                <td class="tdb" style="width:20%;"> '.$row[''].'</td>
                </tr>
                <tr>
              <td class="tdb" style="width:20%;"><b>Effective Date</b></td>
                <td class="tdbr" style="width:43%;"> </td>                                   
                <td class="tdb" style="width:17%;"><b>Review Date</b></td>
                <td class="tdb" style="width:20%;">  </td>
            </tr>
            <tr>
                
                <td class="tdb" style="width:20%;"><b>Storage Condition</b></td>
                <td class="tdb" style="width:80%;"> </td>
            </tr>
            
        </table>
        <div></div>
       <h3 style="text-align:center;">Pharmacopoeial Tests</h3>
            <table cellpadding="5" border="1">
                 <tr style="background-color:#DDDAD9; text-align:center;">
                    <td style="width:15%; text-align:centre;"><b>Test Type</b></td>
                    <td style="width:15%; text-align:centre;"><b>Test</b></td>
                    <td style="width:10%; text-align:centre;"><b>Sub Test</b></td>
                    <td style="width:15%; text-align:centre;"><b>Reference Type</b></td>
                    <td style="width:15%; text-align:centre;"><b>Limit Type</b></td>
                    <td style="width:30%; text-align:centre;"><b>Description/Limits</b></td>
                </tr>';
                 
                 $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                       
                    
                 $html.=' <tr>
                    <td style="width:15%; text-align:centre;">'.$row1[''].'</td>
                    <td style="width:15%; text-align:centre;">'.$row1[''].'</td>
                    <td style="width:10%; text-align:centre;">'.$row1[''].'</td>
                    <td style="width:15%; text-align:centre;">'.$row1[''].'</td>
                    <td style="width:15%; text-align:centre;">'.$row1[''].'</td>
                    <td style="width:30%; text-align:centre;">'.$row1[''].'</td>
                </tr>';
                    }
                }
                
        $html.='
        </table><div></div>
        
        
      <h3 style="text-align:center;">Revision History:</h3>
                        <table border="1" cellpadding="3">
                          <tr style="background-color:#DDDAD9; text-align:center;">
                                <td style="width:20%;font-weight:bold;">Sr</td>
                                <td style="width:20%;font-weight:bold;">Revision No</td>
                                <td style="width:20%;font-weight:bold;">Version No</td>
                                <td style="width:20%;font-weight:bold;">Change Mode</td>
                                <td style="width:20%;font-weight:bold;">Reason for change</td>
                            </tr>';
                        $i=1;
                        $sql2 = "SELECT * FROM spec_revision WHERE spec_no='".$row["specification_no"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $html.='<tr>
                                    <td style="width:20%;"></td>
                                    <td style="width:20%;">'.$row2[''].'</td>
                                    <td style="width:20%;">'.$row2[''].'</td>
                                    <td style="width:20%;">'.$row2[''].'</td>
                                    <td style="width:20%;">'.$row2[''].'</td>
                                </tr>';
                            }
                        }
                        $html.='
                        
               
                        
                        </table>';
    
        EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Specification.pdf', 'I');
    }

$conn->close();
?>