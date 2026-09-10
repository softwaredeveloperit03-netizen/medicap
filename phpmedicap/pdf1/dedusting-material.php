<?php 
require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';

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
    
            $_GET['type'] = 'empdetail';
                include("pdfimp.php");
                class MYPDF extends TCPDF {
                    public function Header() {
                        $_GET['type'] = 'header';
                        include("pdfimp.php");
                    }
                    public function Footer() {
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
        $html.='
        <table cellpadding="5" style="text-align:center;">
            <tr>
                <td style="background-color:#DDDAD9;"><b>Annexure III:  Material Dedusting  Record</b></td>
            </tr>
        </table>
        <p style="text-align:right;">Month: </p>
        <table cellpadding="5" style="text-align:center;">
            <tr>
                <td style="width:24%"><b>Name of Material</b></td>
                <td style="width:11%"><b>Challan No</b></td>
                <td style="width:11%"><b>Total Quantity</b></td>
                <td style="width:11%"><b>No of Containers</b></td>
                <td style="width:14%"><b>Dedusting Done By</b></td>
                <td style="width:15%"><b>No of Damaged Containers</b></td>
                <td style="width:14%"><b>Checked By</b></td>
            </tr>';
        $sql = "SELECT * FROM dedusting_material";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
        while($row = $result->fetch_assoc()){
            $sql1 = "SELECT * FROM employee WHERE emp_id='".$row["check_by"]."'";
            $result1 = $conn->query($sql1);
            $row1 = $result1->fetch_assoc();
            
            $sql2 = "SELECT material_code FROM material_received WHERE receiving_no='".$row["receiving_no"]."'";
		    $result2 = $conn->query($sql2);
		    if ($result2->num_rows > 0) {
		        while ($row2 = $result2->fetch_assoc()) {
		            $row["material_code"] = $row2["material_code"];
		            $sql3 = "SELECT * FROM material WHERE material_code='".$row2["material_code"]."'";
        		    $result3 = $conn->query($sql3);
        		    if ($result3->num_rows > 0) {
        		        while ($row3 = $result3->fetch_assoc()) {
        		            $row["material_name"] = $row3["material_name"];
        		            break;
        		        }
        		    }
		        }
		    }
		    
		    $sql3 = "SELECT * FROM labour WHERE labour_id='".$row["operator"]."'";
            $result3 = $conn->query($sql3);
            $row3 = $result3->fetch_assoc();
            $html.='
            <tr>
                <td style="text-align:left;">'.$row['material_name'].'</td>
                <td>'.$row['challan_no'].'</td>
                <td style="text-align:right;">'.$row['qty'].' '.$row['unit'].'</td>
                <td>'.$row['no_of_containers'].'</td>
                <td>'.$row["operator"].'</td>
                <td>'.$row['no_of_damage_containers'].'</td>
                <td>'.$row1['emp_name'].'</td>
            </tr>';
        }
        }
        $html.='
        </table>';
        EOD;
    $pdf->writeHTML($html, true, false, false, false, '');
    $pdf->Output('DedustingMaterial.pdf', 'I');
}else {
    echo "Invalid Token";
}
?>