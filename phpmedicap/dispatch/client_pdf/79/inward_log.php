<?php
    require '../../db.php';
    require '../../token.php';
    require '../../tcpdf/tcpdf.php';
    header('Access-Control-Allow-Origin: *');
    date_default_timezone_set("Asia/Kolkata");
    $token = $_GET["token"];
     $currentUrl =$_GET["description"];



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
     $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR,frontend_url) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."','".$currentUrl."')";
    $conn->query($sql);

    // $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    // $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);

    if($_GET["type"] == "downloadInwordLog"){
         include '../../tcpdf/tcpdf.php';
        $_GET['filename'] = 'MATERIAL INWARD REGISTER'; $_GET['pdftype'] = 'onlyheader';  include('../pdfimp6.php');
        $html.="";
        $html.='
        <div></div> <div></div> <div></div> 
        <table cellpadding="5" border="0.1">
        <tr>
        <td style="width:100%;text-align:center;color:#330C00"><h3>MATERIAL INWARD REGISTER</h3></td>
        </tr>
       <tr style="text-align: center; background-color:#DDDAD9;">
          <td style="width:12%;text-align:center"  rowspan="2"><b>Date</b></td>
          <td style="width:8%;text-align:center" rowspan="2"><b>Inward No</b></td>
          <td style="width:10%;text-align:center" rowspan="2"><b>In Time</b></td>
          <td style="width:10%;text-align:center" rowspan="2"><b>Party Name</b></td>
          <td style="width:8%;text-align:center" rowspan="2"><b>Challan No</b></td>
          <td style="width:8%;text-align:center" rowspan="2"><b>Vehicle No</b></td>
          <td style="width:10%;text-align:center" rowspan="2"><b>Material Description</b></td>
          <td style="width:14%;text-align:center"><b>Quantity</b></td>
          <td style="width:10%;text-align:center" rowspan="2"><b>Security Signature</b></td>
          <td style="width:10%;text-align:center" rowspan="2"><b>Remarks/ Out time of Vehicle</b></td>
        </tr>
         <tr>
        
          
          <td style="width:7%;text-align:center;background-color:#DDDAD9;">Total Nos</td>
              <td style="width:7%;text-align:center;background-color:#DDDAD9;">Total Weight</td>
        
        </tr>';
        $sql = "SELECT  *  FROM challan   WHERE challan_no
        LIKE '%".$_GET["search_text"]."%' OR  po_no 
        LIKE '%".$_GET["search_text"]."%' OR vendor_no LIKE '%".$_GET["search_text"]."%' ";
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
           
            while ($row = $result->fetch_assoc()) {
                 
         $html.=' <tr>
          <td style="width:12%;">'.date('d-m-Y',strtotime($row['entry_date'])).'</td>
          <td style="width:8%;">'.$row['inward_no'].'</td>
          <td style="width:10%;">'.$row['entry_time'].'</td>
          <td style="width:10%;">'.$row['person'].'</td>
          <td style="width:8%;">'.$row['challan_no'].'</td>
          <td style="width:8%;">'.$row['vehicle_no'].'</td>
          <td style="width:10%;">'.$row['material_type'].'</td>
         <td style="width:7%;">'.$row['qty'].'</td>
           <td style="width:7%;">'.$row['unit'].'</td>
          <td style="width:10%;">'.$row[''].'</td>
          <td style="width:10%;">'.$row['remark'].'</td>
         </tr>';
                
            }
        }
        
         $html.=' </table>
         
        <div></div><div></div>
        
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
               <tr>
        <td style="width:35%;"></td>
         <td style="width:30%;"></td>
          <td style="width:35%;"></td>
          </tr>
        </table>';
         
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('downloadInwordLog','I');
        
  
    }


} else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
?>