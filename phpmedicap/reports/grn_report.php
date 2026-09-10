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
    else if($_GET["type"] == "GRNPDF1"){
        $_GET['filename'] = 'Goods Receipt Notes '; $_GET['pdftype']= 'onlyheader';  include('../pdfimp2.php');
        $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name,v.address,m.material_type, 
          m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 
          ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v 
          ON c1.vendor_no=v.vendor_no WHERE c.id='".$_GET['id']."' ";
        $result = $conn->query($sql);
       if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
           $html.='
          
            <h3 style="text-align:center">Good Receipt Note</h3>
               <table cellpadding="3" border="1">
              <tr>
              <td style="width:25%;">Material Name:</td>
               <td style="width:75%;">'.$row[''].'</td>
              </tr>
               <tr>
               <td style="width:25%">Vendor Name:</td>
               <td style="width:75%">'.$row[''].'</td>
               </tr>
               <tr>
               <td style="width:25%">Vendor Location:</td>
               <td style="width:30%">'.$row[''].'</td>
                <td style="width:25%">Manufacturer:</td>
               <td style="width:20%">'.$row[''].'</td>
               </tr>
               <tr>
               <td style="width:25%">Challan No:</td>
               <td style="width:30%">'.$row[''].'</td>
                <td style="width:25%">Challan Date:</td>
              <td></td>
               </tr>
                <tr>
               <td style="width:25%">PO No.:</td>
                 <td style="width:30%">'.$row[''].'</td>
                <td style="width:25%">PO Date :</td>
               <td ></td>
               </tr>
               <tr>
               <td style="width:25%">Material Type:</td>
               <td style="width:30%">'.$row[''].'</td>
                <td style="width:25%">Material Subtype :</td>
               <td style="width:20%">'.$row[''].'</td>
               </tr>
                <tr>
               <td style="width:25%">Material Code:</td>
               <td style="width:30%">'.$row[''].'</td>
                <td style="width:25%">Material Grade :</td>
               <td style="width:20%">'.$row[''].'</td>
               </tr>
               <tr>
               <td style="width:25%">PO Qty:</td>
               <td style="width:75%">'.$row[''].' </td>
               </tr>
               </table> <div></div>
                 <h3 style="text-align:center;">Labeling Details:</h3>
                <table cellpadding="4" style="text-align:center;">
                    <tr style="background-color:#DDDAD9; text-align:center;">
                        <td style="width:10%">Sr</td>
                         <td style="width:10%">Medicap Lot No</td>
                        <td style="width:10%;">Qty	</td>
                        <td style="width:20%">No Of Containers	</td>
                        <td style="width:20%">Mfg Date</td>
                        <td style="width:20%">Exp Date</td>
                        
                    </tr>';
                
                          $i=1;
            $sql1 = "SELECT * FROM sampling_batches WHERE challan_no = '".$row["challan_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                $row1["weight"] = json_decode($row1["weight"]);
                $html.='
                <tr>
                    <td style="width:10%;"></td>
                    <td style="width:10%;">'.$row1[""].'</td>
                    <td style="width:10%;">'.$row1[""].'</td>
                    <td style="width:10%;">'.$row1[""].'</td>
                    <td style="width:20%;">'.$row[""].'</td>
                    <td style="width:20%;"></td>
                    <td style="width:20%;"></td>
                </tr>';
                }
            }
           
                $html.='</table>
                
                <h3 style="text-align:center;">Batch Details:</h3>
                <table cellpadding="3" style="text-align:center;">
                    <tr style="background-color:#DDDAD9; text-align:center;">
                        <td style="width:10%">Sr</td>
                        <td style="width:20%;">Batch No	</td>
                        <td style="width:10%;">Received Qty		</td>
                        <td style="width:20%">Containers	</td>
                        <td style="width:20%">MFG Date</td>
                        <td style="width:20%">Exp Date</td>
                        </tr>';
                  $i=1;
                $sql1 = "SELECT * FROM sampling_batches WHERE challan_no = '".$row["challan_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                    $row1["weight"] = json_decode($row1["weight"]);
                    $html.='
                    <tr>
                       <td ></td>
                       <td>'.$row1[''].'</td>
                       <td>'.$row1[''].'</td>
                        <td>'.$row1[''].'</td>
                        <td></td>
                        <td></td>
                    </tr>';
                    $i++;
                    }
                }
                $html.='
                </table>';
               
                $html.='
              
               <h4 >Other Details</h4>
               <tr>
               <td style="width:50%;">Damaged Containers: observed </td>
               </tr>
                <tr>
               <td style="width:50%;">COA Recieved :  </td>
               </tr>
                <tr>
               <td style="width:42%;">Total Recieved as per Grade: </td>
               </tr>
                <tr>
               <td style="width:44%;">Any short Qty Recieved : </td>
               </tr><div></div>
               <table cellpadding="5" border="1">
               <tr>
               <td style="width:20%;text-align:center"><b>Material Recieved Date</b></td> 
               <td style="width:20%;text-align:center"><b>Material Weighted Date</b></td>
               <td style="width:20%;text-align:center"><b>Material Recieved By</b></td>
               <td style="width:20%;text-align:center"><b>GRN Prepared By</b></td>
                <td style="width:20%;text-align:center"><b>GRN Approved By</b></td>
               </tr>
                <tr>
               <td style="width:20%;text-align:center"></td> 
               <td style="width:20%;text-align:center"></td>
               <td style="width:20%;text-align:center"></td>
               <td style="width:20%;text-align:center"></td>
                <td style="width:20%;text-align:center"></td>
               </tr>
               </table>';
                 $pdf->writeHTML($html, true, false, false, false, '');
                 $pdf->Output('Grnpdf1','I');
            
    		}
    	}
    
    		
    	
                } else if($_GET["type"] == "GRNPDF2"){
                 $_GET['filename'] = 'Goods Receipt Notes '; $_GET['pdftype']= 'onlyheader';  include('../pdfimp2.php');
               $html.='
                 <h3 style="text-align:center">Good Receipt Note</h3>
               <table cellpadding="3" border="1">
              <tr>
              <td style="width:25%;">Material Name:</td>
               <td style="width:75%;"></td>
              </tr>
               <tr>
               <td style="width:25%">Vendor Name:</td>
               <td style="width:75%"></td>
               </tr>
               <tr>
               <td style="width:25%">Vendor Location:</td>
               <td style="width:30%"></td>
                <td style="width:25%">Manufacturer:</td>
               <td style="width:20%"></td>
               </tr>
               <tr>
               <td style="width:25%">Challan No:</td>
               <td style="width:30%"></td>
                <td style="width:25%">Challan Date:</td>
               <td style="width:20%"></td>
               </tr>
                <tr>
               <td style="width:25%">PO No.:</td>
               <td style="width:30%"></td>
                <td style="width:25%">PO Date :</td>
               <td style="width:20%"></td>
               </tr>
               <tr>
               <td style="width:25%">Material Type:</td>
               <td style="width:30%"></td>
                <td style="width:25%">Material Subtype :</td>
               <td style="width:20%"></td>
               </tr>
                <tr>
               <td style="width:25%">Material Code:</td>
               <td style="width:30%">'.$row['material_code'].'</td>
                <td style="width:25%">Material Grade :</td>
               <td style="width:20%"></td>
               </tr>
               <tr>
               <td style="width:25%">PO Qty:</td>
               </tr>
               </table><div></div>
               <h4 style="text-align:center">Labeling Details</h4>
             <table cellpadding="3" border="1" >
               <tr style="text-align: center; background-color:#DDDAD9;">
              <td style="width:10%;text-align:center"><b>Sr No.</b></td>
              <td style="width:10%;text-align:center"><b>Medicap Lot No</b></td>
               <td style="width:10%;text-align:center"><b>Qty</b></td>
               <td style="width:10%;text-align:center"><b>No. of container</b></td>
                <td style="width:20%;text-align:center"><b>Mfg Date</b></td>
                <td style="width:20%;text-align:center"><b> Exp Date</b></td>
              </tr>
              <tr>
              <td style="width:10%"></td>
              <td style="width:10%"></td>
               <td style="width:20%"></td>
               <td style="width:20%"></td>
                <td style="width:20%"></td>
                <td style="width:20%"></td>
              </tr>
               <tr>
              <td style="width:10%"></td>
              <td style="width:10%"></td>
               <td style="width:20%"></td>
               <td style="width:20%"></td>
                <td style="width:20%"></td>
                <td style="width:20%"></td>
              </tr>
               </table><div></div>
               
               <h4 style="text-align:center">Batch Details</h4>
             <table cellpadding="3" border="1" >
               <tr style="text-align: center; background-color:#DDDAD9;">
              <td style="width:10%;text-align:center"><b>Sr No.</b></td>
              <td style="width:10%;text-align:center"><b>Medicap Lot No</b></td>
               <td style="width:20%;text-align:center"><b>Pack Size</b></td>
               <td style="width:20%;text-align:center"><b>No. of container</b></td>
                <td style="width:20%;text-align:center"><b>Total container</b></td>
                <td style="width:20%;text-align:center"><b> Grade</b></td>
              </tr>
              <tr>
              <td style="width:10%"></td>
              <td style="width:10%"></td>
               <td style="width:20%"></td>
               <td style="width:20%"></td>
                <td style="width:20%"></td>
                <td style="width:20%"></td>
              </tr>
               <tr>
              <td style="width:10%"></td>
              <td style="width:10%"></td>
               <td style="width:20%"></td>
               <td style="width:20%"></td>
                <td style="width:20%"></td>
                <td style="width:20%"></td>
              </tr>
               </table>
               <h4 >Other Details</h4>
               <tr>
               <td style="width:50%;">Damaged Containers: observed </td>
               </tr>
                <tr>
               <td style="width:50%;">COA Recieved :  </td>
               </tr>
                <tr>
               <td style="width:42%;">Total Recieved as per Grade: </td>
               </tr>
                <tr>
               <td style="width:44%;">Any short Qty Recieved : </td>
               </tr><div></div><div></div>
               <table cellpadding="5" border="1">
               <tr>
               <td style="width:20%;text-align:center"><b>Material Recieved Date</b></td> 
               <td style="width:20%;text-align:center"><b>Material Weighted Date</b></td>
               <td style="width:20%;text-align:center"><b>Material Recieved By</b></td>
               <td style="width:20%;text-align:center"><b>GRN Prepared By</b></td>
                <td style="width:20%;text-align:center"><b>GRN Approved By</b></td>
               </tr>
                <tr>
               <td style="width:20%;text-align:center"></td> 
               <td style="width:20%;text-align:center"></td>
               <td style="width:20%;text-align:center"></td>
               <td style="width:20%;text-align:center"></td>
                <td style="width:20%;text-align:center"></td>
               </tr>
               </table>';
                 $pdf->writeHTML($html, true, false, false, false, '');
                 $pdf->Output('Grnpdf1','I');
    
                }
    
    
    
    $conn->close();
?>