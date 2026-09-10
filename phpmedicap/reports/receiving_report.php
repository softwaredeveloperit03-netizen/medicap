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
    
    
  
    
     } else if($_GET['type'] == 'ReceivingLog2'){
        $_GET['filename'] = 'Receiving of Material Log'; $_GET['pdftype'] = 'onlyheader'; include('../pdfimp2.php');
        $html.='
        
        <table cellpadding="3">
             <tr>
             <td style="width:100%;text-align:cenetr;"><b>Material Receiving Checklist</b></td>
              </tr>
                <tr style="text-align: center; background-color:#DDDAD9;">
                    <td style="width: 4%;">Sr.</td>
                    <td style="width: 10%;">Receiving Date</td>
                    <td style="width: 10%;">Receiving No</td>
                    <td style="width: 10%;">Challan No</td>
                    <td style="width: 10%;">Material Type</td>
                    <td style="width: 8%;">Grade</td>
                    <td style="width: 8%;">Material Code</td>
                    <td style="width: 8%;">Material Name</td>
                    <td style="width: 10%;">No Of Containers</td>
                    <td style="width: 10%;">Short / Extra Qty</td>
                    <td style="width: 12%;">Qty Received</td>
                </tr><tbody>';
              
          
                    $html.='
                     <tr >
                        <td style="width: 4%;"></td>
                        <td style="width: 10%;"></td>
                        <td style="width: 10%;">'.$row[''].'</td>
                        <td style="width: 10%;">'.$row[''].'</td>
                        <td style="width: 10%;">'.$row[''].'</td>
                        <td style="width: 8%;">'.$row[''].'</td>
                        <td style="width: 8%;">'.$row[''].'</td>
                        <td style="width: 8%;">'.$row[''].'</td>
                        <td style="width: 10%;">'.$row[''].'</td>
                        <td style="width: 10%;">'.$row[''].'</td>
                        <td style="width: 12%;">'.$row[''].'<td>'.$row[''].'</td></td>
                    </tr>
                    <tr >
                        <td style="width: 4%;"></td>
                        <td style="width: 10%;"></td>
                        <td style="width: 10%;">'.$row[''].'</td>
                        <td style="width: 10%;">'.$row[''].'</td>
                        <td style="width: 10%;">'.$row[''].'</td>
                        <td style="width: 8%;">'.$row[''].'</td>
                        <td style="width: 8%;">'.$row[''].'</td>
                        <td style="width: 8%;">'.$row[''].'</td>
                        <td style="width: 10%;">'.$row[''].'</td>
                        <td style="width: 10%;">'.$row[''].'</td>
                        <td style="width: 12%;">'.$row[''].'<td>'.$row[''].'</td></td>
                    </tr>';
    		     $html.='</table>
    		     <h3 style="text-align:center;">Batch Details:</h3>
                <table cellpadding="3" style="text-align:center;">
                    <tr style="background-color:#DDDAD9; text-align:center;">
                        <td style="width:10%">Sr</td>
                        <td style="width:20%;">Batch No	</td>
                        <td style="width:10%;">Received Qty</td>
                        <td style="width:20%">Containers </td>
                        <td style="width:20%">MFG Date</td>
                        <td style="width:20%">Exp Date</td>
               </tr>
                    <tr>
                       <td style="width:10%"></td>
                       <td  style="width:20%"></td>
                       <td style="width:10%"></td>
                        <td style="width:20%"></td>
                        <td style="width:20%"></td>
                        <td style="width:20%"></td>
                    </tr>
                 
              
                </table><div><div>
                
                <tr>
                <td style="width:25%;text-align:cenetr"><b>Receiving Chack Points :</b></td>
                </tr>
              
               
                <ol>
                <li>Damaged Containers Recived <br>If Yes No. of containers: <br> If yes Damaged container inspecture remark:</li>
                <li>Vehicle condition : <br>Vechile</li>
                 <li>Material condition : </li>
                 <li>Storage condition of Transit storage condition: </li>
                  <li>All papers received : </li>
                   <li>COA received : </li>
                </ol>
                <div></div>
                <table cellpadding="5" border="0.1">
                <tr>
                <td style="width:35%;text-align:center;"><b>Received By</b></td>
                <td style="width:30%;text-align:center;"><b>Checked By</b></td>
                <td style="width:35%;text-align:center;"><b>Date for time of received</b></td>
                </tr>
                 <tr>
                <td style="width:35%;text-align:center;"></td>
                <td style="width:30%;text-align:center;"></td>
                <td style="width:35%;text-align:center;"></td>
                </tr>
                </table ><div></div>
               
                <tr>
                <td style="width:30%;text-align:center"><b>Formate No. :</b></td>
                <td style="width:40%;text-align:right"><b>As per SOP NO. :</b></td>
                </tr> ';
                
               
               
              
    		     
    		     
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('','I');
    
    }
    
    
    $conn->close();
?>