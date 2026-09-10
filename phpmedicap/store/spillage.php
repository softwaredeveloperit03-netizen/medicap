<?php 
 require '../db.php';
    require '../token.php';
    require '../tcpdf/tcpdf.php';
$output = Array();
$token = $_GET["token"];
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

    if ($_GET["type"] == "getMaterials") {
        $output = array();
         $sql = "SELECT s.*, m.material_name,m.material_subtype, m.grade FROM stock_book s LEFT JOIN material m ON s.material_code=m.material_code 
        WHERE s.status='Approved' AND m.material_subtype='".$_GET["material_subtype"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = array();
                $sql1 = "SELECT * FROM stock_book WHERE material_code='".$row["material_code"]."' AND status='Approved'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row["batches"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getMaterialsByType") {
        $output = array();
        $sql = "SELECT * FROM material WHERE material_subtype='".$_GET["material_subtype"]."' AND status='approve'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "saveSpillage") {
        $sql = "INSERT INTO spillage (material_code, batch_no, qty, unit, reason, incident_type, incident_cause, description, entry_by, entry_date) VALUES ('".$input["material_code"]."', '".$input["batch_no"]."', '".$input["qty"]."', '".$input["unit"]."', '".$input["reason"]."', '".$input["incident_type"]."', '".$input["incident_cause"]."', '".$input["description"]."', '".$_GET["emp_id"]."', '$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "checkSpillage") {
        $sql = "UPDATE spillage SET status='".$_GET["status"]."', check_by='".$_GET["emp_id"]."', check_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "approveSpillage") {
        $sql = "UPDATE spillage SET status='".$_GET["status"]."', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getPendingRawSpillages") {
        $output = array();
        $sql = "SELECT s.*, m.material_type, m.material_subtype, m.material_name, m.grade, b.mfg_date, b.exp_date FROM spillage s LEFT JOIN material m ON s.material_code=m.material_code LEFT JOIN stock_book b ON s.material_code=m.material_code WHERE s.status='pending' AND m.material_type='Raw Material' AND s.batch_no=b.batch_no";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getCheckedRawSpillages") {
        $output = array();
        $sql = "SELECT s.*, m.material_type, m.material_subtype, m.material_name, m.grade, b.mfg_date, b.exp_date FROM spillage s LEFT JOIN material m ON s.material_code=m.material_code LEFT JOIN stock_book b ON s.material_code=m.material_code WHERE s.status='checked' AND m.material_type='Raw Material' AND s.batch_no=b.batch_no";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getRawSpillagesLog") {
        $output = array();
         $sql = "SELECT s.*, m.material_type, m.material_subtype, m.material_name, m.grade, b.mfg_date, b.exp_date FROM spillage s LEFT JOIN material m ON s.material_code=m.material_code LEFT JOIN stock_book b ON s.material_code=m.material_code WHERE m.material_type='Raw Material' AND s.batch_no=b.batch_no AND m.material_subtype LIKE '%".$_GET["material_type"]."%' AND m.material_name LIKE '%".$_GET["material_name"]."%' AND DATE(s.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getPendingPackingSpillages") {
        $output = array();
        $sql = "SELECT s.*, m.material_type, m.material_subtype, m.material_name, m.grade, b.mfg_date, b.exp_date FROM spillage s LEFT JOIN material m ON s.material_code=m.material_code LEFT JOIN stock_book b ON s.material_code=m.material_code WHERE s.status='pending' AND m.material_type='Packing Material' AND s.batch_no=b.batch_no";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getRawMaterial") {
        $output = array();
        $sql = "SELECT  * FROM material  where plant_id = '".$_GET["plant_id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getCheckedPackingSpillages") {
        $output = array();
        $sql = "SELECT s.*, m.material_type, m.material_subtype, m.material_name, m.grade, b.mfg_date, b.exp_date FROM spillage s LEFT JOIN material m ON s.material_code=m.material_code LEFT JOIN stock_book b ON s.material_code=m.material_code WHERE s.status='checked' AND m.material_type='Packing Material' AND s.batch_no=b.batch_no";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getPackingSpillagesLog") {
        $output = array();
        $sql = "SELECT s.*, m.material_type, m.material_subtype, m.material_name, m.grade, b.mfg_date, b.exp_date FROM spillage s LEFT JOIN material m ON s.material_code=m.material_code LEFT JOIN stock_book b ON s.material_code=m.material_code WHERE m.material_type='Packing Material' AND s.batch_no=b.batch_no AND m.material_subtype LIKE '%".$_GET["material_type"]."%' AND m.material_name LIKE '%".$_GET["material_name"]."%' AND DATE(s.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    
    
        
		
			else if($_GET['type'] == 'downloadPackingSpillagesLog')	{
     if($_GET["plant_id"] == 59){//amerdeep
        $_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
         $html= "";
          
    		      $html.='
    		     
        <table cellpadding="5" border="0.1">
      <tr>
         <td style="width:5%;text-align:center"><b>Sr No.</b></td>
          <td style="width:15%;text-align:center"><b>Material Type</b></td>
           <td style="width:20%;text-align:center"><b>Material Sub type</b></td>
            <td style="width:15%;text-align:center"><b>Material Code</b></td>
             <td style="width:15%;text-align:center"><b>Material Name</b></td>
             <td style="width:15%;text-align:center"><b>Grade</b></td>
             <td style="width:15%;text-align:center"><b>Av.Qty.Kg </b></td>
         </tr>';
         
         
         $sql = "SELECT s.*, m.material_type, m.material_subtype, m.material_name, m.grade, b.mfg_date, b.exp_date FROM spillage s LEFT JOIN material m ON s.material_code=m.material_code LEFT JOIN stock_book b ON s.material_code=m.material_code WHERE m.material_type='Raw Material' AND s.batch_no=b.batch_no AND m.material_subtype LIKE '%".$_GET["material_type"]."%' AND m.material_name LIKE '%".$_GET["material_name"]."%' AND DATE(s.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
 
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
             $i=1;
            
              $html.='  <tr>
         <td style="width:5%;text-align:center">'.$i.'</td>
          <td style="width:15%;text-align:center">'.$row['material_type'].'</td>
           <td style="width:20%;text-align:center">'.$row['material_subtype'].'</td>
            <td style="width:15%;text-align:center">'.$row['material_code'].'</td>
             <td style="width:15%;text-align:center">'.$row['material_name'].'</td>
             <td style="width:15%;text-align:center">'.$row['grade'].'</td>
             <td style="width:15%;text-align:center">'.$row['EOU_STOCK'].''.$row['uom'].'</td>
         </tr>';
          $i++;
            }
        
         
         $html.='  </table>';
            
        
     
     $pdf->writeHTML($html, true, false, false, false, '');
        
        $pdf->Output('downloadCandidatesList.pdf', 'I');
     
     
     
   }else if($_GET["plant_id"] == 64){//Novo
                $_GET['filename'] = 'Receiving of Material Log'; $_GET['pdftype'] = 'onlyheader'; include('../pdfimp2.php');
        $html.='<table border="1">
    

    <tr>
    <td style="width: 100px;"> Format Title :</td>
    <td style="width: 440px;"> Packing material, packing material and finished good rejection and destruction format.</td>
    </tr>
   
    <tr>
    <td style="width: 100px;"> Format No.:</td>
    <td style="width: 170px;"> F/SOP/WR/007/02-01</td>
    <td style="width: 100px;"> Page No.:</td>
    <td style="width: 170px;"> 1 of 1

</td>
   
    </tr>
    <tr>
    <td style="width: 100px;"> Ref. SOP No.:</td>
    <td style="width: 440px;"> SOP/WR/007</td>
    </tr>
</table><div></div>
         <table border="1">
        <tr style="text-align: center; background-color:#DDDAD9;">
            <td style="width: 20px;text-align:center;font-size:9px;" >Sr. No</td>
            <td style="width: 48px;text-align:center;font-size:9px;" >Date of Rejection</td>
            <td style="width: 48px;text-align:center;font-size:9px;" >Material Description</td>
            <td style="width: 38px;text-align:center;font-size:9px;" >Material Code</td>
            <td style="width: 38px;text-align:center;font-size:9px;" >Medicap lot no</td>
            <td style="width: 38px;text-align:center;font-size:9px;" >Mfg. Batch No.</td>
            <td style="width: 33px;text-align:center;font-size:9px;" >UOM</td>
            <td style="width: 38px;text-align:center;font-size:9px;" >Receipt Qty.</td>
            <td style="width: 38px;text-align:center;font-size:9px;" >Rejection Qty.</td>
            <td style="width: 48px;text-align:center;font-size:9px;" >Manufacturer / Supplier Name</td>
            <td style="width: 38px;text-align:center;font-size:9px;" >Reason for Rejection</td>
            <td style="width: 38px;text-align:center;font-size:9px;" >Done By</td>
            <td style="width: 38px;text-align:center;font-size:9px;" >Return /Destro-yed Qty.</td>
            <td style="width: 38px;text-align:center;font-size:9px;" >Checked By (QA)</td>
        </tr>
        </table>
        ';
              
           $sql = "SELECT c.*,c1.entry_by,c1.approve_by,c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,m.material_name, m.grade,r.arno,r.rejected_quantity,r.rejection_reason,r.remark,m.description,r.entry_date,r.store_approve_by FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN rejection_rawpacking r on m.user_no=r.user_no LEFT JOIN stock_book s on m.material_code=s.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE m.material_type ='Packing Material' order by id DESC";
            $result = $conn->query($sql);
           
    	    if($result->num_rows > 0){
                $counter = 1;
    		    while ($row = $result->fetch_assoc()) {
                    $inword_details = json_decode($row["inword_details"]);
                    $html.='
                     <table border="1">
        <tr>
            <td style="width: 20px;text-align:center;font-size:9px;" >'.$counter++.'</td>
            <td style="width: 48px;text-align:center;font-size:9px;" >'.$row["entry_date"].'</td>
            <td style="width: 48px;text-align:center;font-size:9px;" >'.$row["description"].' </td>
            <td style="width: 38px;text-align:center;font-size:9px;" > '.$row["material_code"].' </td>
            <td style="width: 38px;text-align:center;font-size:9px;" >'.$row["arno"].'</td>
            <td style="width: 38px;text-align:center;font-size:9px;" >'.$row["batch_no"].'</td>
            <td style="width: 33px;text-align:center;font-size:9px;" >'.$row["unit"].'</td>
            <td style="width: 38px;text-align:center;font-size:9px;" >'.$row["received_qty"].'</td>
            <td style="width: 38px;text-align:center;font-size:9px;" >'.$row["rejected_quantity"].'</td>
            <td style="width: 48px;text-align:center;font-size:9px;" > 
            '.$row["vendor_name"].'</td>
            <td style="width: 38px;text-align:center;font-size:9px;" >'.$row["rejection_reason"].'</td>
            <td style="width: 38px;text-align:center;font-size:9px;" >'.$row["entry_by"].'</td>
            <td style="width: 38px;text-align:center;font-size:9px;" >'.$row["remark"].'</td>
            <td style="width: 38px;text-align:center;font-size:9px;" >'.$row["store_approve_by"].'</td>
        </tr>
        </table>
       
                    ';
    		    }
    	        
    	    }
    		    $html.=' <div></div>
        
        


<table border="1">
    <tr>
    <td style="text-align:center;width: 100px;"></td>
    <td style="text-align:center;width: 146.6px;">PREPARED BY</td>
    <td style="text-align:center;width: 146.6px;">REVIEWED BY</td>
    <td style="text-align:center;width: 146.6px;">APPROVED BY</td>
    </tr>
    <tr>
    <td style="text-align:center;width: 100px;">Name</td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    </tr>
    <tr>
    <td style="text-align:center;width: 100px;">Sign/Date</td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    </tr>
    <tr>
    <td style="text-align:center;width: 100px;">Designation</td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    </tr>
    <tr>
    <td style="text-align:center;width: 100px;">Department</td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    </tr>
</table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('','I');
    
            
            }
            else if($_GET["plant_id"] == 28){//GMP
                $_GET['filename'] = 'Receiving of Material Log'; $_GET['pdftype'] = 'onlyheader'; include('../pdfimp2.php');
        $html.='
         <table border="1">
        <tr>
            <td style="width: 20px;text-align:center;font-size:9px;" >Sr. No</td>
            <td style="width: 48px;text-align:center;font-size:9px;" >Date of Rejection</td>
            <td style="width: 48px;text-align:center;font-size:9px;" >Material Description</td>
            <td style="width: 38px;text-align:center;font-size:9px;" >Material Code</td>
            <td style="width: 38px;text-align:center;font-size:9px;" >Medicap lot no</td>
            <td style="width: 38px;text-align:center;font-size:9px;" >Mfg. Batch No.</td>
            <td style="width: 33px;text-align:center;font-size:9px;" >UOM</td>
            <td style="width: 38px;text-align:center;font-size:9px;" >Receipt Qty.</td>
            <td style="width: 38px;text-align:center;font-size:9px;" >Rejection Qty.</td>
            <td style="width: 48px;text-align:center;font-size:9px;" >Manufacturer / Supplier Name</td>
            <td style="width: 38px;text-align:center;font-size:9px;" >Reason for Rejection</td>
            <td style="width: 38px;text-align:center;font-size:9px;" >Done By</td>
            <td style="width: 38px;text-align:center;font-size:9px;" >Return /Destro-yed Qty.</td>
            <td style="width: 38px;text-align:center;font-size:9px;" >Checked By (QA)</td>
        </tr>
        </table>
        ';
              
          $sql = "SELECT c.*,c1.entry_by,c1.approve_by,c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,m.material_name, m.grade,r.arno,r.rejected_quantity,r.rejection_reason,r.remark,m.description,r.entry_date,r.store_approve_by FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN rejection_rawpacking r on m.user_no=r.user_no LEFT JOIN stock_book s on m.material_code=s.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE m.material_type ='Packing Material' order by id DESC";
           echo hello;
            $result = $conn->query($sql);
    	    if($result->num_rows > 0){
                $counter = 1;
    		    while ($row = $result->fetch_assoc()) {
                    $inword_details = json_decode($row["inword_details"]);
                    $html.='
                     <table border="1">
        <tr>
            <td style="width: 20px;text-align:center;font-size:9px;" >'.$counter++.'</td>
            <td style="width: 48px;text-align:center;font-size:9px;" >'.$row["entry_date"].'</td>
            <td style="width: 48px;text-align:center;font-size:9px;" >'.$row["description"].' </td>
            <td style="width: 38px;text-align:center;font-size:9px;" > '.$row["material_code"].' </td>
            <td style="width: 38px;text-align:center;font-size:9px;" >'.$row["arno"].'</td>
            <td style="width: 38px;text-align:center;font-size:9px;" >'.$row["batch_no"].'</td>
            <td style="width: 33px;text-align:center;font-size:9px;" >'.$row["unit"].'</td>
            <td style="width: 38px;text-align:center;font-size:9px;" >'.$row["received_qty"].'</td>
            <td style="width: 38px;text-align:center;font-size:9px;" >'.$row["rejected_quantity"].'</td>
            <td style="width: 48px;text-align:center;font-size:9px;" > 
            '.$row["vendor_name"].'</td>
            <td style="width: 38px;text-align:center;font-size:9px;" >'.$row["rejection_reason"].'</td>
            <td style="width: 38px;text-align:center;font-size:9px;" >'.$row["entry_by"].'</td>
            <td style="width: 38px;text-align:center;font-size:9px;" >'.$row["remark"].'</td>
            <td style="width: 38px;text-align:center;font-size:9px;" >'.$row["store_approve_by"].'</td>
        </tr>
        </table>
                    ';
    		    }
    	        
    	    }
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('','I');
    
            
            }
			}
    
}


$conn->close();
?>