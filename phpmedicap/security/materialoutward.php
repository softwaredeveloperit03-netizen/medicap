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

    if ($_GET["type"] == "saveMaterialOutForm") {
		$sql = "INSERT INTO outword (department,user_no, material_code,transport_company, send_to,pin_code,address, reason, qty, unit, outword_type, request_by, transport_by, vehicle_no,materials,requiredQty, entry_by, entry_date, driver_name, driver_mobile) VALUES ('".$input["department"]."','".$_GET["user_no"]."', '".$input["material_code"]."','".$input["transport_company"]."', '".$input["send_to"]."','".$input["pin_code"]."','".$input["address"]."', '".$input["reason"]."', '".$input["qty"]."', '".$input["unit"]."', '".$input["outword_type"]."', '".$input["request_by"]."', '".$input["transport_by"]."', '".$input["vehicle_no"]."','".json_encode($input["materials"])."','".$input["requiredQty"]."', '".$_GET["emp_id"]."', '$entry_date','".$input["driver_name"]."','".$input["driver_mobile"]."')";
		if ($conn->query($sql)) {
		    echo "{\"status\":\"success\"}";
		    $materials = $input["materials"];
            for ($i = 0; $i < count($materials); $i++) {
                $material = $materials[$i];
                 $sql = "INSERT INTO material_issue (grn_no, ar_no,batch_no, material_code, issue_for, qty, unit, entry_by, entry_date,status) VALUES ('".$material["grn_no"]."', '".$material["ar_no"]."','".$material["batch_no"]."', '".$material["material_code"]."', 'JOB WORK', '".$material["qty"]."', '".$material["unit"]."', '".$_GET["emp_id"]."', '".$_GET["issue_date"]."','approve')";
                $conn->query($sql);
            }
		} else {
		    echo "{\"status\":\"".$conn->error."\"}";
		}
	}else if ($_GET["type"] == "getMaterials") {
        $output = Array();
      $sql = "SELECT * FROM material WHERE material_subtype='".$_GET["material_subtype"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $received_qty = 0;
                $issue_qty = 0;
                $balance_qty = 0;
                $output1 = array();
                $sql1 = "SELECT s.*,v.vendor_name FROM stock_book s LEFT JOIN vendor v ON s.vendor_no=v.vendor_no WHERE s.user_no='".$_GET["user_no"]."' AND s.material_code='".$row["material_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {

                        $used_qty = 0;
                        
                        $output2 = array();
                        $sql2 = "SELECT * FROM material_issue WHERE ar_no='".$row1["ar_no"]."' AND status='approve'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $output2[] = $row2;
                                $issue_qty += +$row2["qty"];
                                $used_qty += +$row2["qty"];
                            }
                        }
                        $row1["issued"] = $output2;
                        $row1["issue_qty"] = $used_qty;
                        $balance_qty = +$row1["qty"] - $used_qty;
                        $balance_qty = round($balance_qty, 2);
                        $row1["balance_qty"] = $balance_qty;
                        $row1["issues"] = $output2;
                        $output1[] = $row1;
                        
                        $received_qty += +$row1["qty"];
                    }
                    $balance_qty = $received_qty - $issue_qty;
                    $balance_qty = round($balance_qty, 2);
                    $row["received_qty"] = $received_qty;
                    $row["issue_qty"] = $issue_qty;
                    $row["balance_qty"] = $balance_qty;
                    $row["grns"] = $output1;
                    $output[] = $row;
                }
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getOutwordLog") {
	    $output = array();
	    $sql = "SELECT o.* FROM outword o WHERE plant_id =  '".$_GET["plant_id"]."' AND DATE(o.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ";
	    $result = $conn->query($sql);
	    if ($result->num_rows > 0) {
	        while ($row = $result->fetch_assoc()) {
	             $row = array_map('utf8_encode', $row);
	            $row["materials"]=json_decode($row["materials"]);
	            $output[] = $row;
	        }
	    }
	    echo json_encode($output);
	}
	else if ($_GET["type"] == "UpdateOutwordLog") {
	    
	     
                 
                 $prefix = "GP/";

            $timestampComponent = date("d/m/Y");
            $randomBytes = random_bytes(4); // 4 bytes = 32 bits
            $randomComponent = '/'.$_GET["id"];
            $gatepassNumber = $prefix . $timestampComponent . $randomComponent;
            
              
             



        	    
        	    $sql = "UPDATE outword SET gatepass_no = '$gatepassNumber', status ='verify' WHERE id = '".$_GET["id"]."'";
        		
        		if ($conn->query($sql)) {
        		    
        		    echo "{\"status\":\"success\"}";
        		    
        	
                    
        		} else {
        		    echo "{\"status\":\"".$conn->error."\"}";
        		}
		
	    
	    


    
    
    
    
    
    
    
	}
	else if ($_GET["type"] == "getAllOutwordLog") {
	    $output = array();
	    $sql = "SELECT o.*, DATE(o.entry_date) as entry_date, m.material_type, m.material_subtype, m.material_name, m.grade FROM outword o LEFT JOIN material m ON o.material_code=m.material_code WHERE m.material_type='Raw Material'";
	    $result = $conn->query($sql);
	    if ($result->num_rows > 0) {
	        while ($row = $result->fetch_assoc()) {
	       $row = array_map('utf8_encode', $row);
	            $output[] = $row;
	        }
	    }
	    echo json_encode($output);
    
    
	}
	else if($_GET["type"] == "downloadoutwardLog"){
        $_GET['filename'] = 'MATERIAL OUTWARD REGISTER'; $_GET['pdftype'] = 'onlyheader';  include('../pdfimp6.php');
        $html.="";
        
        $html.='
        
        <div></div> <div></div> <div></div> 
        
        <table cellpadding="5" border="0.1">';
        
       $html.='  <tr>
        <td style="width:100%;text-align:center;color:#330C00"><h3> MATERIAL OUTWARD REGISTER</h3></td>
        </tr>
        <tr style="text-align: center; background-color:#DDDAD9;">
          <td style="width:11%;text-align:center"  rowspan="2"><b>Date</b></td>
          <td style="width:8%;text-align:center" rowspan="2"><b>Outward No</b></td>
          <td style="width:10%;text-align:center" rowspan="2"><b>Out Time</b></td>
          <td style="width:10%;text-align:center" rowspan="2"><b>Party Name</b></td>
          <td style="width:8%;text-align:center" rowspan="2"><b>Challan No</b></td>
          <td style="width:8%;text-align:center" rowspan="2"><b>Vehicle No</b></td>
          <td style="width:13%;text-align:center" rowspan="2"><b>Material Description</b></td>
          <td style="width:14%;text-align:center"><b>Quantity</b></td>
          <td style="width:10%;text-align:center" rowspan="2"><b>Security Signature</b></td>
          <td style="width:8%;text-align:center" rowspan="2"><b>Remarks/ Out time of Vehicle</b></td>
        </tr>
         <tr>
        
          
          <td style="width:7%;text-align:center; background-color:#DDDAD9;">Total Nos</td>
           <td style="width:7%;text-align:center; background-color:#DDDAD9;">Total Weight</td>
        
        </tr>';
         $sql = "SELECT o.*, DATE(o.entry_date) as entry_date, c.challan_no,m.order_unit,m.order_qty,m.weight,m.material_type, m.material_subtype, m.material_name, m.grade 
	    FROM outword o LEFT JOIN material m ON o.material_code=m.material_code  LEFT JOIN challan c ON 
	   o.material_code=c.material_code ";
	    $result = $conn->query($sql);
	    if ($result->num_rows > 0) {
	        while ($row = $result->fetch_assoc()) {
	          $html.=' <tr>
          <td style="width:11%;">'.date('d-m-Y',strtotime($row['entry_date'])).'</td>
          <td style="width:8%;">'.$row['outward_no'].'</td>
          <td style="width:10%;">'.$row['out_time'].'</td>
          <td style="width:10%;">'.$row['send_to'].'</td>
          <td style="width:8%;">'.$row['challan_no'].'</td>
          <td style="width:8%;">'.(trim((string)$row['vehicle_no']) !== '' ? $row['vehicle_no'] : 'NA').'</td>
          <td style="width:13%;">'.$row['material_name'].','.$row['material_type'].','.$row['material_code'].'</td>
         <td style="width:7%;">'.$row['order_qty'].' '.$row['order_unit'].'</td>
           <td style="width:7%;">'.$row['weight'].''.$row['order_unit'].'</td>
          <td style="width:10%;">'.$row[''].'</td>
          <td style="width:8%;">'.$row['remark'].'</td>
         </tr>';
	        
	        }}
	        
	    
        
            
         $html.=' </table>
         
        
        <div></div><div></div><div></div>';
  
	      
         $html.=' <table cellpadding="5" border="0.1">
       <tr style="text-align: center; background-color:#DDDAD9;">
        <td style="width:35%;"><b>Sign/Date</b></td>
         <td style="width:30%;"><b>Sign/Date</b></td>
          <td style="width:35%;"><b>Sign/Date</b></td>
          </tr>
           <tr >
        <td style="width:35%;"></td>
         <td style="width:30%;"></td>
          <td style="width:35%;"></td>
          </tr>
         <tr style="text-align: center; background-color:#DDDAD9;">
           <td style="width:35%;"><b>Prepared By</b></td>
            <td style="width:30%;"><b>Checked By</b></td>
             <td style="width:35%;"><b>Approved By</b></td>
              </tr>
               <tr  >
        <td style="width:35%;"></td>
         <td style="width:30%;"></td>
          <td style="width:35%;"></td>
          </tr>
        </table>';
	        
	        
	    
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('downloadInwordLog','I');
	        
	    
	    
	} 
	else if($_GET["type"]=="downloadMaterialLog"){
    $_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader';  include('../pdfimp2.php');
    $html.="";
    $html.='<h3 style="text-align:center;">Material / Equipment Outward</h3>
    <table cellpadding="3" border="1">
        <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
            <td style="width:10%;">Sr</td>
            <td style="width:20%;">Date</td>
            <td style="width:20%;">Outward Type</td>
            <td style="width:20%;">Request By</td>
            <td style="width:15%;">Transport</td>
            <td style="width:15%;">Vehicle No.</td>
        </tr>';
    $i=1;
$sql = "SELECT * from outword";
	$result = $conn->query($sql);
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
        $html.='
        <tr>
            <td style="width:10%;">'.$i++.'</td>
            <td style="width:20%;">'.$row['entry_date'].'</td>
            <td style="width:20%;">'.$row['outword_type'].'</td>
            <td style="width:20%;">'.$row['request_by'].'</td>
            <td style="width:15%;">'.$row['transport_by'].'</td>
            <td style="width:15%;">'.(trim((string)$row['vehicle_no']) !== '' ? $row['vehicle_no'] : 'NA').'</td>
        </tr>';
		}
	}
    $html.='   
    </table>';
    $pdf->writeHTML($html, true, false, false, false, '');
    $pdf->Output('','I');
    }
	
	else if($_GET["type"] == "downloadOutwordStamp"){
        $_GET['filename'] = ' OUTWARD STAMP'; $_GET['pdftype'] = 'onlyheader';  include('../pdfimp6.php');
        $html.="";
         $html.='
         <div></div> <div></div> <div></div> <div></div>
         <table cellpadding="8" border="1">
         <tr>
         <td style="width:20%"></td>
         <td style="width:80%;text-align:center"><b>AMGIS LIFESCIENCE LTD. PANOLI (Unit-2)</b></td>
         </tr>
        </table>
       <table cellpadding="5" border="01" >
         <h3 style="text-align:center;color:brown;">SECURITY OUTWARD</h3>';
          $sql = "SELECT o.*, DATE(o.entry_date) as entry_date, c.challan_no,c.challan_date,c.entry_time,m.order_unit,m.order_qty,m.weight,m.material_type, m.material_subtype, m.material_name, m.grade 
	    FROM outword o LEFT JOIN material m ON o.material_code=m.material_code  LEFT JOIN challan c ON 
	   o.material_code=c.material_code ";
	    $result = $conn->query($sql);
	    $i=1;
	    if ($result->num_rows > 0) {
	        while ($row = $result->fetch_assoc()) {
         $html.=' <tr>
       <td style="width:100%"><b>  Sr No. </b> : '.$i.'</td>
        </tr>
          <tr>
        <td style="width:100%"><b>  Challan No & Date </b> : '.$row['challan_no'].' &
        '.date('d-m-Y',strtotime($row['challan_date'])).'</td>
       </tr>
          <tr>
        <td style="width:100%"><b>  Vehicle No</b> : '.(trim((string)$row['vehicle_no']) !== '' ? $row['vehicle_no'] : 'NA').'</td>
       </tr>
          <tr>
        <td style="width:100%"><b>  In Time </b> : '.$row['entry_time'].'</td>
      </tr>
          <tr>
        <td style="width:100%"><b>  Out Time</b> : '.$row['out_time'].'</td>
        </tr>
          <tr>
        <td style="width:100%"><b>  Date</b> :     '.date('d-m-Y',strtotime($row['entry_date'])).'</td>
         </tr>
           <tr>
        <td style="width:100%"><b> Security In-charge Signature</b> :</td>
         </tr>
           <tr>
        <td style="width:100%;text-align:right"><b> Format No</b>: HR/015/FM-002/00</td>
         </tr>
      </table><div></div><div></div><div></div><div></div>
     
      </table>
      <div></div><div></div><div></div> <div></div><div></div><div></div><div></div> <div></div><div></div><div></div><div></div> 
          <table cellpadding="5" border="0.1">
       <tr style="text-align: center; background-color:#DDDAD9;">
        <td style="width:35%;"><b>Sign/Date</b></td>
         <td style="width:30%;"><b>Sign/Date</b></td>
          <td style="width:35%;"><b>Sign/Date</b></td>
          </tr>
           <tr >
        <td style="width:35%;"></td>
         <td style="width:30%;"></td>
          <td style="width:35%;"></td>
          </tr>
         <tr style="text-align: center; background-color:#DDDAD9;">
           <td style="width:35%;"><b>Prepared By</b></td>
            <td style="width:30%;"><b>Checked By</b></td>
             <td style="width:35%;"><b>Approved By</b></td>
              </tr>
               <tr  >
        <td style="width:35%;"></td>
         <td style="width:30%;"></td>
          <td style="width:35%;"></td>
          </tr>
        </table>';
  $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('downloadInwordStamp','I');
	        }  
}
}

} else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
?>