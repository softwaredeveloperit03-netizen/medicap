
<?php
  require '../../db.php';
    require '../../token.php';
    header('Access-Control-Allow-Origin: *');
    date_default_timezone_set("Asia/Kolkata");
    $token = $_GET["token"];
    $timestamp = time();
    $entry_date = date("Y-m-d h:i:s", $timestamp);
    $input = json_decode(file_get_contents('php://input'),true);

// ini_set('display_errors', 1);
// error_reporting(E_ALL);



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
    $myfile = file_put_contents('../../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    
     if($_GET["type"] == "testPdf"){
         
         
         
         
    $sql = "SELECT * FROM challan WHERE id='".$_GET["challan_id"]."' limit 1";
       require './tcpdf/tcpdf.php';
        $_GET['filename'] = 'Test  Log'; $_GET['pdftype'] = 'landscape'; include("./pdfimp2.php");

       
             $html.='
      
    <table border="1">
    
        <tr style="background-color:gray;">
       
        <td style="text-align:center;width:70px; line-height:30px;">Material Code</td>
        <td style="text-align:center;width:80px; line-height:30px;">Material Name</td>
        <td style="text-align:center;width:60px; line-height:30px;">Department</td>
        <td style="text-align:center;width:60px; line-height:30px;">Indent Qty</td>
        <td style="text-align:center;width:40px; line-height:30px;">Unit</td>
        <td style="text-align:center;width:50px; line-height:30px;">Order Qty</td>
        <td style="text-align:center;width:50px; line-height:30px;">Grade</td>
        <td style="text-align:center;width:80px; line-height:30px;">Quotation Amount</td>
        <td style="text-align:center;width:50px; line-height:30px;">GST %</td>
        <td style="text-align:center;width:60px; line-height:30px;">Gross</td>
        <td style="text-align:center;width:60px; line-height:30px;">GST Amt</td>
        <td style="text-align:center;width:60px; line-height:30px;">Net</td>
        <td style="text-align:center;width:50px; line-height:30px;">Status</td>
        </tr>';
        
        
      $sql="SELECT i.*,m.material_code,m.material_name,g.material_name as gm_material ,m.grade  FROM indend_raw i LEFT JOIN material m ON i.material_code=m.material_code  left join general_material g ON i.material_code = g.material_code ";
             

                
                                 
                $i=1;
        $result = $conn->query($sql);
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                // echo $row;
                
            $html.='
    <tr> 
       
        <td style="text-align:center;width:70px; line-height:30px;">'.$row['material_code'].' </td>
        <td style="text-align:center;width:80px; line-height:30px;">'.$row['material_name'].'  '.$row['gm_material'].'</td>
        <td style="text-align:center;width:60px; line-height:30px;">'.$row['department'].' </td>
        <td style="text-align:center;width:60px; line-height:30px;">'.$row['req_qty'].' </td>
        <td style="text-align:center;width:40px; line-height:30px;">'.$row['unit'].' </td>
        <td style="text-align:center;width:50px; line-height:30px;">'.$row['order_qty'].' </td>
    </tr>';
    }
}
$html.= '</table>';
 $pdf->writeHTML($html, true, false, false, false, '');
 $pdf->Output('test.pdf', 'I');
        
  
    }
    
    
}

$conn->close();
?>