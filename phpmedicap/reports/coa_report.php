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
    
  if ($_GET["type"] == "downloadCOAReport") {
           $_GET['filename'] = ' '; $_GET['pdftype']= 'onlyheader';  include('../pdfimp2.php');
         
         $html= "";
          $html.='
        <table>
        <tr>
        <td style="width:100%;">For :<u></u></td>
        </tr>
        </table>
         <h3 style="text-align:center;">CERTIFICATE OF ANALYSIS</h3>
          <table>
          <tr>
          <td style="width:30%;"><b>Product Name :</b> </td>
          <td style="width:35%; text-align:center;"><b>Colour :</b> </td>
          <td style="width:35%;"><b>Code No. : </b></td>
          </tr><br>
          <tr>
          <td style="width:30%;"><b>A R No. :</b> </td>
          <td style="width:35%; text-align:center;"><b>Batch No. : </b> </td>
          <td style="width:35%;"><b>Batch Size: </b> </td>
          </tr><br>
          <tr>
          <td style="width:30%;"><b>Mfg Date :</b></td>
          <td style="width:35%; text-align:center;"><b>Retest Date/Shelf life :</b> </td>
          <td style="width:35%;"><b>Page No. :</b></td>
          </tr>
        </table>
            
        
      <table>
         <h4>Quality Fourmula:</h4><br><br>
         <tr>
         <td style="width:100%;">1. Hydroxypropyl Methyl cellulose IP.</td>
         </tr>
         <tr>
         <td style="width:100%;">2. Polyethylene Glycol IP.</td>
         </tr>
         <tr>
         <td style="width:100%;">3. Talc IP.</td>
         </tr>
         <tr>
         <td style="width:100%;">4. Titanium Dloxide</td>
         </tr>
         <tr>
         <td style="width:100%;">5. Lake Erythrosive C.I. No.45430</td>
         </tr>
          </table><div></div>';
         $html.='<table cellpadding="5" border="0.1">
         <tr>
         <th style="width:10%;"><b>Sr.No.</b></th>
         <th style="width:20%;"><b>Test</b></th>
         <th style="width:35%;"><b>Specification</b></th>
         <th style="width:35%;"><b>Observation</b></th>
         </tr>
          <tr>
         <td style="width:10%;"></td>
         <td style="width:20%;"></td>
         <td style="width:35%;"></td>
         <td style="width:35%;"></td>
         </tr>
         <tr>
         <td style="width:10%;"></td>
         <td style="width:20%;"></td>
         <td style="width:35%;"></td>
         <td style="width:35%;"></td>
         </tr>
         <tr>
         <td style="width:10%;"></td>
         <td style="width:20%;"></td>
         <td style="width:35%;"></td>
         <td style="width:35%;"></td>
         </tr>
         </table><div></div>
         <table>
         <tr>
         <td style="width:100%;">Remark:</td>
         </tr>
       </table><br><br>
        <table cellpadding="5" border="0.1">
           <tr>
            <td style="width:25%"></td>
           <td style="width:25%"><b>Prepared by</b></td>
             <td style="width:25%"><b>Checked by</b></td>
               <td style="width:25%"><b>Released by</b></td>
            </tr>
           <tr>
           <td style="width:25%"><b>Sign/Date</b></td>
             <td style="width:25%"></td>
               <td style="width:25%"></td>
                 <td style="width:25%"></td>
           </tr>
           <tr>
           <td style="width:25%"><b>Name</b></td>
             <td style="width:25%"></td>
               <td style="width:25%"></td>
                 <td style="width:25%"></td>
           </tr>
            <tr>
           <td style="width:25%"><b>Designation</b></td>
             <td style="width:25%"></td>
               <td style="width:25%"></td>
                 <td style="width:25%"></td>
                </tr>
           </table><div></div>
          
           <tr>
           <td style="width:100%;">Reference:</td>
           </tr>
            <hr>';
     
    $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Specification.pdf', 'I');
    }

$conn->close();
?>

