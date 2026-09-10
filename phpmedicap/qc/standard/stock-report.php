<?php
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
    $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    
    
    
    if ($_GET["type"] == "getAllStock_pdf") {
        
        $_GET['filename'] = 'Standards Stock Book'; $_GET['pdftype'] = 'onlyheader'; include("../../pdfimp2.php");
        $html= "";
        $html.='
        <h2 style="text-align:center">Standards Stock Book</h2>
        <table cellpadding="5" border="1">
            <tr>
                <td style="width:10%; text-align:centre;"><b>Sr.</b></td>
                <td style="width:10%; text-align:centre;"><b>GRN No.</b></td>
                <td style="width:15%; text-align:centre;"><b>Standard Type</b></td>
                <td style="width:10%; text-align:centre;"><b>Standard Code</b></td>
                <td style="width:15%; text-align:centre;"><b>Standard Name</b></td>
                <td style="width:10%; text-align:centre;"><b>Grade</b></td>
                <td style="width:10%; text-align:centre;"><b>Batch No.</b></td>
                <td style="width:10%; text-align:centre;"><b>Available Qty</b></td>
                <td style="width:10%; text-align:centre;"><b>View Cerificate</b></td>
            </tr>
            <tr>
                <td style="width:10%;"></td>
                <td style="width:10%;"></td>
                <td style="width:15%;"></td>
                <td style="width:10%;"></td>
                <td style="width:15%;"></td>
                <td style="width:10%;"></td>
                <td style="width:10%;"></td>
                <td style="width:10%;"></td>
                <td style="width:10%;"></td>
            </tr>
        </table>';
        
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Standards Stock Book.pdf', 'I');
    }
     else if ($_GET["type"] == "downloadStandardstockorder") {
                $_GET['filename'] = 'Standards Stock Book'; $_GET['pdftype'] = 'onlyheader'; include("../../pdfimp2.php");

        $i=1;
           $sql = "SELECT * FROM primary_standard_stock  where stock_type= '".$_GET["material_type"]."' and plant_id='".$_GET["plant_id"]."'";
       
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()){
         
        
        $html.='
        <h2 style="text-align:center">Standards Stock Book</h2>
        <table cellpadding="5" border="1">
            <tr>
                <td style="width:20%; text-align:left;"><b>Standard Name:</b></td>
                <td style="width:30%; text-align:centre;">'.$row['s_title'].'</td>
                <td style="width:20%; text-align:left;"><b>Standard Identifier Number/Code</b></td>
                <td style="width:30%; text-align:centre;">'.$row['s_identifier'].'</td>
            </tr>
            <tr>
                <td style="width:20%; text-align:left;"><b>Grade.</b></td>
                <td style="width:30%; text-align:centre;">'.$row['grade'].'</td>
                <td style="width:20%; text-align:left;"><b>Batch No</b></td>
                <td style="width:30%; text-align:centre;">'.$row['batch_no'].'</td>
            </tr>
            <tr>
                <td style="width:20%; text-align:left;"><b>Record Keeping Details::</b></td>
                <td style="width:30%; text-align:centre;">'.$row['record_details'].'</td>
                <td style="width:20%; text-align:left;"><b>Date Of Entry: </b></td>
                <td style="width:30%; text-align:centre;">'.$row['record_date'].'</td>
            </tr>
            <tr>
                <td style="width:20%; text-align:left;"><b>Received Qty.</b></td>
                <td style="width:30%; text-align:centre;">'.$row['received_qty'].'</td>
                <td style="width:20%; text-align:left;"><b>Available Qty.</b></td>
                <td style="width:30%; text-align:centre;">'.$row['available_qty'].'</td>
            </tr>
            <tr>
                <td style="width:20%; text-align:left;"><b>UOM:</b></td>
                <td style="width:30%; text-align:centre;">'.$row['available_qty'].'</td>
                <td style="width:20%; text-align:left;"><b>Storage Location</b></td>
                <td style="width:30%; text-align:centre;">'.$row['storage_location'].'</td>
            </tr>
            <tr>
                <td style="width:20%; text-align:left;"><b>Prepared By</b></td>
                <td style="width:30%; text-align:centre;">'.$row['prepared_by'].'</td>
                <td style="width:20%; text-align:left;"><b>Prepared Date</b></td>
                <td style="width:30%; text-align:centre;">'. date('d-m-Y', strtotime($row['prepared_date'])).'</td>
            </tr>
            <tr>
                <td style="width:20%; text-align:left;"><b>Review By::</b></td>
                <td style="width:30%; text-align:centre;">'.$row['review_by'].'</td>
                <td style="width:20%; text-align:left;"><b>Review Date</b></td>
                <td style="width:30%; text-align:centre;">'. date('d-m-Y', strtotime($row['review_date'])).'</td>
            </tr>
            
        
        
        </table>';
            }
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Standards Stock Book.pdf', 'I');
     
     }
     }
   
  else if ($_GET["type"] == "getAllStock") {
        $output=Array();
          //$sql="SELECT s.*, DATE(s.entry_date) as entry_date, m.media_name FROM media_stock s LEFT JOIN media m ON 
        //s.media_code=m.media_code WHERE DATE(s.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' order by 1 desc ";
         $sql="SELECT * FROM   primary_standard_stock  where stock_type='".$_GET["material_type"]."' and grade='".$_GET["grade"]."'  and s_title like '%".$_GET["standard_name"]."%' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output = array();
             $output[] = $row;
            }
         }
        
        echo json_encode($output);
        
    }  
    
    
    
    }
$conn->close();
?>