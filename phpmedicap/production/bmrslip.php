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
    $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."')";
    $conn->query($sql);
     if($_GET["type"]=="bmrslip") {
               $_GET['filename'] = '../BATCH MANUFACTURING RECORD ISSUE REQUISITION SLIP';
                include("pdfimp2.php");
                class MYPDF extends TCPDF {
                public function Header() {
                    $_GET['type'] = 'header';
                    include("pdfimp2.php");
                }
                public function Footer() {
                    $_GET['type'] = 'footer';
                    include("pdfimp2.php");
                }
            }
            $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            $pdf->SetMargins(15, 15, 15, 15);
            $pdf->SetAutoPageBreak(TRUE, 15);
            $pdf->AddPage('P');
            $pdf->SetY(45);
            $pdf->SetFont ('Times', '', '11' , '', 'default', true );
            $html='
                    
                    <table cellpadding="1">
                     <style>td { border:solid 1px BCBBBA;}</style>
                            <td style="width:100%;" align="center"><b>BATCH MANUFACTURING RECORD ISSUE REQUISITION SLIP</b></td>
                            <td> </td>
                            <td> </td>
                            <td> </td>
                        </tr>
                        <tr>
                            <td style="width:20%;"><b>From:Production Department</b> </td>
                            <td> </td>
                            <td> </td>
                            <td style="width:20%;"><b>To: Q.A.Department</b> </td>
                        </tr>
                        <tr>
                            <td style="width:20%;"><b>Requisition Date:</b></td>
                            <td>15-03-2021 </td>
                            <td> </td>
                            <td> </td>
                           
                        </tr>
                        <tr>
                            <td> <b>Kindly Issue BMR for following Item. </b></td>
                            <td> </td>
                            <td> </td>
                            <td> </td>
                        </tr>
                        <tr>
                            <td style="width:20%;"> <b>Product Name: </b> </td>
                            <td> OMEPRAZOLE CAPSULES IP 20MG FOR GMSCL </td>
                            <td> <b>Master Formula No:</b></td>
                            <td> C/OMP/20/01</td>
                        </tr>
                        <tr>
                            <td><b>Product Code:</b> </td>
                            <td>FGC00038 </td>
                            <td><b>Item Type:  </b> </td>
                            <td>FG </td>
                        </tr>
                        <tr>
                            <td><b>Generic Name:  </b></td>
                            <td>OMEPRAZOLE CAPSULES IP 20MG. </td>
                        <td> </td>
                        <td> </td>
                        </tr>
                        <tr>
                            <td><b>Batch No:</b></td>
                            <td>C0177</td>
                            <td><b>Batch Size:</b>  </td>
                            <td>500000.00 CAPSULE (5000 CARTON) </td>
                        </tr>
                        <tr>
                            <td><b>Mfg. Date:</b> </td>
                            <td>04/2020 </td>
                            <td><b> Exp. Date:</b></td>
                            <td>03/2022 </td>
                        </tr>
                        <tr>
                            <td><b>Mfg. By:</b></td>
                            <td> BHARAT PARENTERALS LTD.</td>
                            <td> </td>
                            <td> </td>
                        </tr>
                        <tr>
                            <td><b> Mkt. By: </b> </td>
                            <td>BHARAT PARENTERALS LTD. </td>
                            <td><b>Mfg. Licence No: </b> </td>
                            <td>G/1269 </td>
                        </tr>
                        <tr>
                            <td><b> Party Ref. No.:</b></td>
                            <td> </td>
                            <td><b>Avg. Weight </b></td>
                            <td> 0.00 MILLIGRAM </td>
                        </tr>
                        <tr>
                            <td><b> Party Ref. Date: </b> </td>
                            <td> </td>
                            <td> </td>
                            <td> </td>
                        </tr>
                        <tr> 
                            <td><b>Primary Pack:  </b> </td>
                            <td>1 BLISTER *10 CAPSULE </td>
                            <td><b> Shipper Weight:</b></td>
                            <td>6.35kg </td>
                        </tr>
                        <tr> 
                            <td><b>Secondary Pack : </b> </td>
                            <td> 1 CARTON*10 BLISTER </td>
                            <td><b> Tare Weight:</b></td>
                            <td> 0.70kg</td>
                        </tr>
                        <tr> 
                            <td><b>Final Pack: </b> </td>
                            <td> 1 SHIPPER*100 CARTON </td>
                            <td> </td>
                             <td> </td>
                        </tr>
                        <tr> 
                            <td><b>Packing Size : </b> </td>
                            <td>10x10 CAPSULE </td>
                            <td> </td>     
                            <td> </td>
                        </tr>
                        <tr> 
                            <td><b>Issue Date : </b> </td>
                            <td>15-03-2021 </td>
                            <td> </td>
                             <td> </td>
                        </tr>
                        <tr> 
                            <td><b>Label Claim: </b> </td>
                            <td>Each capsule contains: Omeprazole lP 20mg (Enteric Coated Granules) Excipients … q. s. </td>
                            <td> </td>
                             <td> </td>
                        </tr>
                        <tr>
                            <td> </td>
                            <td> </td>
                            <td> </td>
                            <td> </td>
                        </tr>
                        <tr>
                            <td> </td>
                            <td> </td>
                            <td> </td>
                            <td> </td>
                        </tr>
                        <tr>
                            <td> </td>
                            <td> </td>
                            <td> </td>
                            <td><b>Received By:</b></td>
                        </tr>
                        <tr>
                            <td> </td>
                            <td> </td>
                            <td></td>
                            <td>Quality Assurance Department</td>
                        </tr>
                        <tr style="font-weight:bold;">
                            <td style="width:30%">Prepared By</td>
                            <td style="width:30%">Checked By </td>
                            <td style="width:40%">Approved By</td>
                        </tr><br>
                    </table> ';
    
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('bmrslip.pdf', 'I');  

}
}
    
$conn->close();
?>