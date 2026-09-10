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

    //$txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    //$myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    
    $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."')";
    $conn->query($sql);
    
     if ($_GET["type"] == "savemaster") {
       $sql = "INSERT INTO unitformula (user_no, product_type, product_code, raw_materials, packing_materials, stages, yield_qty, entry_by, entry_date) VALUES 
       ('".$_GET["user_no"]."', '".$input["product_type"]."', '".$input["product_code"]."',  '".json_encode($input["raw_materials"])."', '".json_encode($input["packing_materials"])."', '".json_encode($input["stages"])."', '".$input["yield_qty"]."', '".$_GET["emp_id"]."', '$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
     } else if ($_GET["type"] == "getmaster") {
         $output = array();
         $sql = "SELECT * FROM unitformula  WHERE id='".$_GET['id']."' "; 
   	$result = $conn->query($sql);
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
	}
	echo json_encode($output);
   }else if ($_GET["type"] == "downloadmasterreference")
    {
        $_GET['filename'] = '';
         $_GET['filename'] = ' '; $_GET['pdftype']= 'onlyheader';  include('../pdfimp2.php');
        $html = "";
         $html .= '
        <table>
        <tr>
        <td style="width:50%"><b>PRODUCT NAME:</b></td>
         <td style="width:50%"><b>PRODUCT CODE NO.:</b></td>
        </tr>
        <tr>
        <td style="width:50%"><b>MRF NO.:</b></td>
         <td style="width:50%"><b>REFERENCE MRF NO. :</b></td>
        </tr>
        <tr>
        <td style="width:50%"><b>COLOUR:</b></td>
         <td style="width:50%"><b>REFERENCE SAMPLE BATCH NO. :</b></td>
        </tr>
        <tr>
        <td style="width:50%"><b>SHELF LIFE:</b></td>
         <td style="width:50%"><b>STD LOT SIZE:</b></td>
        </tr>
      
        </table><div></div>
        <tr>
       <td style="width:100%;text-align:center"><b>COMPLYING WITH SPECIFICATION :</b></td>
       </tr>
       <table cellpadding="5" border="0.1">
       <tr>
       <td style="width:10%;text-align:center"><b>SR NO.</b></td>
        <td style="width:30%;text-align:center"><b>INGREDENTS</b></td>
         <td style="width:20%;text-align:center"><b>RAW MATERIAL CODE</b></td>
          <td style="width:10%;text-align:center"><b>*GRADE</b></td>
           <td style="width:10%;text-align:center"><b>%W/W</b></td>
            <td style="width:20%;text-align:center"><b>STD QTY PER KG</b></td>
       </tr>';
       
       $sql = "SELECT * FROM unitformula  WHERE id='".$_GET['id']."' "; 
   	$result = $conn->query($sql);
   	 $i=1;
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
	
      $html.=' <tr>
      
       <td style="width:10%;text-align:center">'.$i.'.</td>
        <td style="width:30%;text-align:center">'.$row['product_type'].'</td>
         <td style="width:20%;text-align:center">'.$row['product_code'].'</td>
          <td style="width:10%;text-align:center"></td>
           <td style="width:10%;text-align:center"></td>
            <td style="width:20%;text-align:center"></td>
       </tr>';
        $i++;
		}
	}
         $html.='<tr>
       <td style="width:70%;text-align:center"><b>TOTAL</b></td>
        <td style="width:10%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
       </tr>
        
        
        </table>
            
     
     
      <tr>
      <td style="width:100%">*Pharmacopoeial grade of RM will be used for manufacturing as per customer requirement.</td>
      </tr>
      <h3>LIST OF CUSTOMER:</h3>
      <tr>
      <ul>
      <ol>
      <li>LUPIN LTD</li>
      <li>ALKEM HEALTH SCIENCE</li>
       <li>M/S THE PHARMACUTICAL PRODUCTS OF INDIA LTD A/C T&T PHARMACARE </li>
        <li>ZEON LIFE SCIENCE LTD (HEALTHCARE ll) </li>
      </ol>
      </ul>
      </tr><div></div>
     
       <h3>REVISION HISTORY:</h3>
       <table cellpadding="3" border="0.1">';
        $sql = "SELECT * FROM spec_revision  WHERE id='".$_GET['id']."' "; 
   	$result = $conn->query($sql);
   	 $i=1;
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
        $html.=' <tr>
       <td style="width:10%;text-align:center"><b>Sr No.</b></td>
       <td style="width:20%;text-align:center"><b>Revision No.</b></td>
       <td style="width:20%;text-align:center"><b>Effective Date</b></td>
       <td style="width:50%;text-align:center"><b>Revision Description</b></td>
       </tr>
        
        <tr>
       <td style="width:10%;text-align:center">'.$i.'.</td>
       <td style="width:20%;text-align:center">'.$row['spec_no'].'</td>
       <td style="width:20%;text-align:center">'.$row['effective_date'].'</td>
       <td style="width:50%;text-align:center">'.$row['reason'].'</td>
         </tr>';
       $i++;
		}
	}
        $html.=' </table><div></div><div></div>
      <table cellpadding="5" border="0.1">
      <tr>
      <td style="width:25%;text-align:center"><b></b></td>
      <td style="width:25%;text-align:center"><b>PREPARED BY</b></td>
      <td style="width:25%;text-align:center"><b>REVIEWED BY</b></td>
      <td style="width:25%;text-align:center"><b>APPROVED BY</b></td>
      </tr>
      <tr>
      <td style="width:25%;text-align:center"><b>Name</b></td>
      <td style="width:25%;text-align:center"></td>
      <td style="width:25%;text-align:center"></td>
      <td style="width:25%;text-align:center"></td>
      </tr>
       <tr>
      <td style="width:25%;text-align:center"><b>Sign/Date</b></td>
      <td style="width:25%;text-align:center"></td>
      <td style="width:25%;text-align:center"></td>
      <td style="width:25%;text-align:center"></td>
      </tr>
       <tr>
      <td style="width:25%;text-align:center"><b>Designation</b></td>
      <td style="width:25%;text-align:center"></td>
      <td style="width:25%;text-align:center"></td>
      <td style="width:25%;text-align:center"></td>
      </tr>
       <tr>
      <td style="width:25%;text-align:center"><b>Department</b></td>
      <td style="width:25%;text-align:center"></td>
      <td style="width:25%;text-align:center"></td>
      <td style="width:25%;text-align:center"></td>
      </tr>
      </table>
      ';
		
	
        
         $pdf->writeHTML($html, true, false, false, false, '');
                $pdf->Output('Po Report_new.pdf', 'I');
    
               $conn->close();
    }
     
?>