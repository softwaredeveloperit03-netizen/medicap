<?php 
require '../db.php';
require_once('../tcpdf/tcpdf.php');
require '../token.php';


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
    $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."')";
    $conn->query($sql);
    $sql = "SELECT * FROM material_received WHERE receiving_no= '".$_GET['receiving_no']."'";
    $result = $conn->query($sql);
    if($result->num_rows > 0){
        $_GET['type'] = 'empdetail';
                include("pdfimp.php");
                class MYPDF extends TCPDF {
                    public function Header() {
                        $_GET['type'] = 'header';
                        include("pdfimp.php");
                    }
                    public function Footer() {
                        $_GET['type'] = 'footer';
                        include("pdfimp.php");
                }
            }
            $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            $pdf->SetCreator(PDF_CREATOR);
            $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
            $pdf->SetMargins(15, 45, 15, 15);
            $pdf->SetAutoPageBreak(TRUE, 52);
            $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
            if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
                require_once(dirname(__FILE__).'/lang/eng.php');
                $pdf->setLanguageArray($l);
            }
            $pdf->AddPage('P', 'A4');
            $pdf->SetY(45);
            $pdf->SetFont ('Times', '', '11' , '', 'default', true );
    	while($row = $result->fetch_assoc()){
    	    
        $sql1 = "SELECT * FROM inword_material WHERE id='".$row['inword_no']."'";
        $result1 = $conn->query($sql1);
        $row1 = $result1->fetch_assoc();
    	    
        $sql2 = "SELECT * FROM material WHERE material_code='".$row['material_code']."'";
        $result2 = $conn->query($sql2);
        $row2 = $result2->fetch_assoc();
        	    
        $sql3 = "SELECT vendor_status, vendor_name FROM vendor WHERE vendor_no='".$row1['vendor_no']."'";
        $result3 = $conn->query($sql3);
        $row3 = $result3->fetch_assoc();
            $html.='
            <table cellpadding="5" style="text-align:center;">
                <tr>
                    <td style="border:solid 1px BCBBBA; background-color:#DDDAD9;">Annexure II :  Raw Material / Packing Material Receiving Checklist</td>
                </tr>
            </table>
            <div></div>
            <table cellpadding="5">
                <tr>
                    <td style="width:100%; text-align:center; border:none;">Checklist for Receiving of Raw Material/Packing Material</td>
                </tr>
                <tr>
                    <td style="border:none;">Reference No. : '.$row['referance_no'].'</td>
                </tr>
                <br>
                <tr>
                    <td style="width:5%;">1)</td>
                    <td style="width:95%;"><b>Name of Material</b>	: '.$row2['material_name'].'</td>
                </tr>
                <tr>
                    <td>2)</td>
                    <td><b>Ownership</b>: '.$row['ownership'].'</td>
                </tr>
                <tr>
                    <td>3)</td>
                    <td><b>Standard (IP / BP / USP / In-House Specification)</b> : '.$row['standard'].'</td>
                </tr>
                <tr>
                    <td>4)</td>
                    <td><b>Labeling Details</b>: <br>
                        <table cellpadding="5">
                            <tr>
                                <td style="border:none;">a. <b>Batch No</b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: '.$row['batch_no'].' </td>
                                <td style="border:none;">b. <b>Qty Received</b> :  '.$row['qty_received'].'</td>
                            </tr>
                            <tr>
                                <td style="border:none; width:100%">c. <b>Total No. of Containers Received</b> : '.$row['total_containers'].' </td>
                            </tr>
                            <tr>
                                <td style="border:none; width:50%;">d. <b>Mfg. Date</b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: '.$row['mfg_date'].' </td>
                                <td style="border:none; width:50%;">e. <b>Exp. Date</b> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: '.$row['exp_date'].' </td>
                            </tr>
                            <tr>
                                <td style="border:none;">f. <b>Manufacturer</b>	: '.$row['manufacturer'].' </td>
                                <td style="border:none;">g. <b>Supplier</b> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: '.$row3['vendor_name'].' </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="width:5%;">5)</td>
                    <td style="width:95%;"><b>Packing Details</b>: <br>
                        <table cellpadding="5">
                             <tr>
                               <td style="border:none; width:35%">a. <b>Packing Intactness / Condition</b></td>
                               <td style="border:none; width:65%;">:  '.$row['packing_condition'].'</td>
                            </tr>
                            <tr>
                                <td style="border:none;">b. <b>Outer Packaging</b></td>
                                <td style="border:none;">: 	'.$row['outer_packing'].'</td>
                            </tr>
                            <tr>
                                <td style="border:none;">c. <b>'.$row['container_type'].'</b></td>
                                <td style="border:none;">: '.$row['container_subtype'].'</td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td>6)</td>
                    <td><b>Transporter’s Details</b>: <br>
                        <table cellpadding="5">
                            <tr>
                                <td style="border:none; width:35%;">a) <b>Vehicle No. & Time Entered</b></td>
                                <td style="border:none; width:65%;">:  '.$row1['vehicle_no'].' &  '.$row1['entry_date'].' </td>
                            </tr>
                            <tr>
                                <td style="border:none;">b) <b>Challan No. & Date </b></td>
                                <td style="border:none;">: '.$row1['challan_no'].' & '.$row1['challan_date'].'</td>
                            </tr>
                            <tr>
                                <td style="border:none;">c) <b>Vehicle Condition</b> : '.$row['vehicle_condition'].'</td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td>7)</td> 
                    <td><b>Is material Received From '.$row3['vendor_status'].' Vendor</b></td>
                </tr>
                <tr>
                    <td>8)</td>
                    <td><b>COA Received</b> : '.$row['coa_received'].'</td>
                </tr>
                <tr>
                    <td>9)</td>
                    <td><b>Remark (If Any)</b>: '.$row['remark'].'</td>
                </tr>
                <tr>
                    <td style="width:100%;">Consignment Received By:   Sign : & Date : </td>
                </tr>
            </table>
            <div></div>';
            require 'footer.php';
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('receivingofmaterial.pdf', 'I');
        } 
    }else {
        echo "{\"status\":\"invalid\"}";
    }
}
?>