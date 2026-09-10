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
    
    if($_GET["type"] == 'sampling'){
        $_GET['filename'] = 'Sampling Check List'; $_GET['pdftype'] = 'headfoot';  include("../pdfimp.php");
        
       /* $sql = "SELECT * FROM sampling WHERE id='".$_GET["id"]."'";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
              //  $_GET['filename'] = 'Sampling Check List'; $_GET['pdftype'] = 'headfoot';  include("../pdfimp.php");
                
                $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                $result1 = $conn->query($sql1);
                $row1 = $result1->fetch_assoc();
                
                $sql2 = "SELECT * FROM grn WHERE grn_no='".$row["grn_no"]."'";
                $result2 = $conn->query($sql2);
                $row2 = $result2->fetch_assoc();
                
                $sql3 = "SELECT * FROM material_received WHERE receiving_no='".$row2["receiving_no"]."'";
                $result3 = $conn->query($sql3);
                $row3 = $result3->fetch_assoc();
                
                $sql5 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                $result5 = $conn->query($sql5);
                $row5 = $result5->fetch_assoc();
                
                $sql9 = "SELECT * FROM grn_material WHERE grn_no='".$row["grn_no"]."'";
                $result9 = $conn->query($sql9);
                $row9 = $result9->fetch_assoc();*/
            $html.='
            <table cellpadding="5" style="text-align:left;">
                <tr><td style="width:100%"><b>Line Clearance By QA</b></td></tr>
                <tr>
                    <td style="width:25%"><b>Previous Material Name</b></td>
                    <td style="width:75%">'.$row5['material_name'].'</td>
                </tr>
                <tr>
                    <td><b>Previous Material GRN No.</b></td>
                    <td>'.$row['grn_no'].'</td>
                </tr>
                <tr>
                    <td style="width:25%"><b>Area Cleaned By</b></td>
                    <td style="width:25%">'.$row['sampling_person'].'</td>
                    <td style="width:25%"><b>Cleaning Date</b></td>
                    <td style="width:25%">'.date("d/m/Y", strtotime($row['entry_date'])).'</td>
                </tr>
                <tr>
                    <td style="width:100%"><b>QA Person has to ensure that all Traces of Previous Material Shall be Cleaned and Ensure that area is cleaned.</b><br><br>
                        <table>
                            <tr>
                                <td style="border:none;"><b>Time of Line Clearance :</b></td>
                                <td style="border:none;"><b>Sign and Date of IPQA</b></td>
                            </tr>
                            <tr>
                                <td style="width:100%; border:none;"><b>Person</b></td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
            <div></div>
            <table cellpadding="5">
                <tr>
                    <td style="width:100%;">Name of Raw Material: '.$row1['material_name'].'</td>
                </tr>
                <tr>
                    <td style="width:50%;">Item Code : '.$row['material_code'].'</td>
                    <td style="width:50%;">A.R. No : '.$row9['ar_no'].'</td>
                </tr>
                <tr>
                    <td>Mfg Date: '.date("d/m/Y", strtotime($row3['mfg_date'])).'</td>
                    <td>Exp Date: '.date("d/m/Y", strtotime($row3['exp_date'])).'</td>
                </tr>
                <tr>
                    <td>Manufacturer ( Vendor) : '.$row3['manufacturer'].'</td>
                    <td>Approved Not Approved</td>
                </tr>
                <tr><td style="width:100%;">Supplier Name:</td></tr>
                <tr><td>Cleaning of surrounding area of consignment : '.$row['cleaning_area'].'</td></tr>
                <tr><td>Proper consignment label affixed by vendor: '.$row['label_affixed'].'</td></tr>
                <tr><td>Material’s Physical Description: '.$row['physical_description'].'</td></tr>
                <tr>
                    <td style="width:50%">Total No. of containers: '.$row['total_containers'].'</td>
                    <td style="width:50%">No. Of Containers Sampled: '.$row['sampled_containers'].'</td>
                </tr>
                <tr><td style="width:100%;">Containers number Sampled (For n+1 criteria):</td></tr>
                <tr><td>Temperature /Rel. Humidity of Sampling Room: '.$row['room_temperature'].'°C / '.$row['humidity'].'%</td></tr>
                <tr><td>After Sampling Containers sealed and closed by: '.$row6['emp_name'].'</td></tr>
                <tr><td>Remark : '.$row['remark'].'</td></tr>
                <tr><td>Sampled by : '.$row6['emp_name'].' &nbsp;&nbsp;&nbsp; Sampled Date : '.date("d/m/Y", strtotime($row['entry_date'])).'</td></tr>
                <tr><td>Reviewed by QA (If any Abnormality) sign/Date:</td></tr>
            </table>
            <div></div>';
            
          //  }
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('samplingsopannexure.pdf', 'I');
       // }
    }
    if($_GET["type"] == 'samplingdigital'){
        $_GET['filename'] = 'Sampling Check List'; $_GET['pdftype'] = 'headfootdigital';  include("../pdfimp.php");
       /* $sql = "SELECT * FROM sampling WHERE id='".$_GET["id"]."'";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                //$_GET['filename'] = 'Sampling Check List'; $_GET['pdftype'] = 'headfootdigital';  include("../pdfimp.php");
                        
                $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                $result1 = $conn->query($sql1);
                $row1 = $result1->fetch_assoc();
                
                $sql2 = "SELECT * FROM grn WHERE grn_no='".$row["grn_no"]."'";
                $result2 = $conn->query($sql2);
                $row2 = $result2->fetch_assoc();
                
                $sql3 = "SELECT * FROM material_received WHERE receiving_no='".$row2["receiving_no"]."'";
                $result3 = $conn->query($sql3);
                $row3 = $result3->fetch_assoc();
                
                $sql5 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                $result5 = $conn->query($sql5);
                $row5 = $result5->fetch_assoc();
                
                $sql9 = "SELECT * FROM grn_material WHERE grn_no='".$row["grn_no"]."'";
                $result9 = $conn->query($sql9);
                $row9 = $result9->fetch_assoc();*/
            $html.='
            <table cellpadding="5" style="text-align:center;">
                <tr>
                    <td style="background-color:#DDDAD9;">Annexure I : Sampling Check List</td>
                </tr>
            </table>
            <div></div>
            <table cellpadding="5" style="text-align:left;">
                <tr><td style="width:100%"><b>Line Clearance By QA</b></td></tr>
                <tr>
                    <td style="width:25%"><b>Previous Material Name</b></td>
                    <td style="width:75%">'.$row5['material_name'].'</td>
                </tr>
                <tr>
                    <td><b>Previous Material GRN No.</b></td>
                    <td>'.$row['grn_no'].'</td>
                </tr>
                <tr>
                    <td style="width:25%"><b>Area Cleaned By</b></td>
                    <td style="width:25%">'.$row['sampling_person'].'</td>
                    <td style="width:25%"><b>Cleaning Date</b></td>
                    <td style="width:25%">'.date("d/m/Y", strtotime($row['entry_date'])).'</td>
                </tr>
                <tr>
                    <td style="width:100%"><b>QA Person has to ensure that all Traces of Previous Material Shall be Cleaned and Ensure that area is cleaned.</b><br><br>
                        <table>
                            <tr>
                                <td style="border:none;"><b>Time of Line Clearance :</b></td>
                                <td style="border:none;"><b>Sign and Date of IPQA</b></td>
                            </tr>
                            <tr>
                                <td style="width:100%; border:none;"><b>Person</b></td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
            <div></div>
            <table cellpadding="5">
                <tr>
                    <td style="width:100%;">Name of Raw Material: '.$row1['material_name'].'</td>
                </tr>
                <tr>
                    <td style="width:50%;">Item Code : '.$row['material_code'].'</td>
                    <td style="width:50%;">A.R. No : '.$row9['ar_no'].'</td>
                </tr>
                <tr>
                    <td>Mfg Date: '.date("d/m/Y", strtotime($row3['mfg_date'])).'</td>
                    <td>Exp Date: '.date("d/m/Y", strtotime($row3['exp_date'])).'</td>
                </tr>
                <tr>
                    <td>Manufacturer ( Vendor) : '.$row3['manufacturer'].'</td>
                    <td>Approved Not Approved</td>
                </tr>
                <tr><td style="width:100%;">Supplier Name:</td></tr>
                <tr><td>Cleaning of surrounding area of consignment : '.$row['cleaning_area'].'</td></tr>
                <tr><td>Proper consignment label affixed by vendor: '.$row['label_affixed'].'</td></tr>
                <tr><td>Material’s Physical Description: '.$row['physical_description'].'</td></tr>
                <tr>
                    <td style="width:50%">Total No. of containers: '.$row['total_containers'].'</td>
                    <td style="width:50%">No. Of Containers Sampled: '.$row['sampled_containers'].'</td>
                </tr>
                <tr><td style="width:100%;">Containers number Sampled (For n+1 criteria):</td></tr>
                <tr><td>Temperature /Rel. Humidity of Sampling Room: '.$row['room_temperature'].'°C / '.$row['humidity'].'%</td></tr>
                <tr><td>After Sampling Containers sealed and closed by: '.$row6['emp_name'].'</td></tr>
                <tr><td>Remark : '.$row['remark'].'</td></tr>
                <tr><td>Sampled by : '.$row6['emp_name'].' &nbsp;&nbsp;&nbsp; Sampled Date : '.date("d/m/Y", strtotime($row['entry_date'])).'</td></tr>
                <tr><td>Reviewed by QA (If any Abnormality) sign/Date:</td></tr>
            </table>
            <div></div>';
            
                //}
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('samplingsopannexure.pdf', 'I');
       // }
    }
    else if($_GET['type'] == 'samplinglog'){
        $_GET['filename'] = 'Sampling Log'; $_GET['pdftype'] = 'landscape';  include("../pdfimp.php");
        
        
        if($result->num_rows > 0){
            $html.='
            <table cellpadding="5">
                <tr style="background-color: #d3d3d3; text-align:center;">
                    <td>Material Code</td>
                    <td>Batch No.</td>
                    <td>Containers</td>
                    <td>GRN No.</td>
                    <td>GRN Date</td>
                    <td>Sampling Person</td>
                    <td>Status</td>
                </tr>';
            while($row = $result->fetch_assoc()){
            $html.='
                <tr nobr="true">
                    <td>'.$row['material_code'].'</td>
                    <td>'.$row['batch_no'].'</td>
                    <td>'.$row['containers'].'</td>
                    <td>'.$row['grn_no'].'</td>
                    <td>'.$row['grn_date'].'</td>
                    <td>'.$row['sampling_person'].'</td>
                     <td>'.$row['status'].'</td>
                </tr>';
            }
            $html.='</table>';
        }
        EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('samplinglog.pdf', 'I');
    
    }
}else{
    echo "Invalid Token";
}
?>