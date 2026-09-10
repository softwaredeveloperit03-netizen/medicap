<?php
require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';

// ini_set('display_errors', 1);
// error_reporting(E_ALL);

$token = $_GET["token"];
$sql = "SELECT * FROM token WHERE token='".$_GET["token"]."'";
$result = $conn->query($sql);
$_GET["emp_id"] = "";
$_GET["department"] = "";
if($result->num_rows > 0){
while($row = $result->fetch_assoc()) {
	$string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
	$string = explode("$",$string);
	$_GET["emp_id"] = $string[0];
	$_GET["department"] = $string[1];
	break;
}

$sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."')";
$conn->query($sql);
    if($_GET["type"]=="checkingrecord"){
        $sql = "SELECT * FROM batch_release WHERE id='".$_GET['id']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
            $checkpoints = json_decode($row['checkpoints']);
            $_GET['filename'] = 'Finished Product Pack Stock checking Record'; $_GET['pdftype'] = 'headfoot';  include("../pdfimp.php");
            $html.='
        	    <table cellpadding="5">
        	        <tr>
        	            <td><b>Product Name:</b> '.$row['product'].'</td>
        	            <td><b>Batch No.:</b> '.$row['batch_no'].'</td>
        	        </tr>
        	        <tr>
        	            <td style="width:100%;"><b>No of FP Shippers Checked:</b> '.$row['shippers'].'</td>
        	        </tr>
        	        <tr><td style="border:none;"></td></tr>
        	        <tr style="background-color:#DDDAD9; text-align:center;">
        	            <td style="width:7%;"><b>Sr. No</b></td>
        	            <td style="width:54%;"><b>Check points</b></td>
        	            <td style="width:7%;"><b>Ok</b></td>
        	            <td style="width:8%;"><b>Not Ok</b></td>
        	            <td style="width:24%;"><b>Remark/Any Deviation</b></td>
        	        </tr>';
        	        $counter = 1;
        	        $j = 0;
                    for ($i=1; $i <= count($checkpoints); $i++) {
                        $html.='<tr>
            	            <td style="text-align:center;">'.$counter++.'</td>
            	            <td>'.$checkpoints[$j]->checkpoint.'</td>
            	            <td style="text-align:center;">';
            	                if($checkpoints[$j]->status == "Ok"){
            	                    $html.='<span style="font-family:zapfdingbats;">3</span>';
            	                }
            	            $html.='
            	            </td>
            	            <td style="text-align:center;">';
            	                if($checkpoints[$j]->status == "Not Ok"){
            	                    $html.='<span style="font-family:zapfdingbats;">3</span>';
            	                }
            	            $html.='</td>
            	            <td>'.$checkpoints[$j]->remark.'</td>
            	        </tr>';
            	        $j++;
                    }
                    $html.='
                    <tr>
                        <td style="text-align:center;">'.$counter.'</td>
                        <td style="width:93%;"><b>Other Observations:</b>'.$row['observation'].'</td>
                    </tr>
                </table>
                <table style="border:solid 1px BCBBBA;" cellpadding="5">
                    <tr>
                        <td style="border:none; width:50%;">Checklist Filled By: '.$row['entry_by'].'</td>
                        <td style="border:none; width:50%;">Checklist Verified and Batch Released By:</td>
                    </tr>
                    <br><br>
                    <tr>
                        <td style="border:none;">(IPQA):Sign/ Date:</td>
                        <td style="border:none;">Head QA /Designee Sign/ Date</td>
                    </tr>
        	    </table>
                <div></div>';
            }
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('BatchRelease.pdf', 'I');
        }else{
            echo 'Invalid Release id';
        }
    }
    else if($_GET["type"]=="certificate"){
        $sql = "SELECT * FROM batch_release WHERE id='".$_GET['id']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM product WHERE product_code='".$row['product_code']."'";
                $result1 = $conn->query($sql1);
                $row1 = $result1->fetch_assoc();
                $_GET['filename'] = 'BATCH RELEASE CERTIFICATE'; $_GET['pdftype'] = 'headfoot';  include("../pdfimp.php");
                $html.='
                <table cellpadding="5">
            	    <tr><td style="text-align:center; border:none; text-decoration: underline;"><b>BATCH RELEASE CERTIFICATE</b></td></tr>
        	    </table>
        	    <div><br><br></div>
        	    <table cellpadding="5">
        	        <tr>
        	            <td style="width:25%;"><b>Product Name</b></td>
        	            <td><b>: '.$row1['product_name'].'</b></td>
        	        </tr>
        	        <tr>
        	            <td><b>Batch No.</b></td>
        	            <td><b>: '.$row['batch_no'].'</b></td>
        	        </tr>
        	        <tr>
        	            <td><b>Mfg. Date</b></td>
        	            <td><b>: '.$row['mfg_date'].'</b></td>
        	        </tr>
        	        <tr>
        	            <td><b>Exp. Date</b></td>
        	            <td><b>: '.$row['exp_date'].'</b></td>
        	        </tr>
        	        <tr>
        	            <td><b>Batch Size</b></td>
        	            <td><b>: '.$row['batch_size'].'</b></td>
        	        </tr>
                </table>
                <p style="line-height:1.7; text-align:justify;">Following documents of this Batch has been reviewed by Quality Assurance department of GMP Software Pvt Ltd</p>
                <ol style="font-weight:bold; line-height:1.7;">
                    <li>Batch Manufacturing Record</li>
                    <li>Batch Packing Record</li>
                    <li>Finished product COA and In process analysis reports along with raw data.</li>
                </ol>
                <p style="line-height:1.7; text-align:justify;">This is to be certified that this batch is meeting all the quality parameters as per specification. This Batch can be distributed on or After Date: ____________<br>This certificate is issued by Quality Assurance Department of GMP Software Pvt Ltd.</p>
                <div></div>
                <table>
                    <br><br><br><br><br><br>
                    <tr>
                        <td><b>Head Quality Assurance</b><br>GMP Software Pvt Ltd</td>
                        <td style="text-align:right;">&nbsp;<br><b>Date : </b>____________</td>
                    </tr>
                </table>';
            }
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('BatchRelease.pdf', 'I');
        }
        else{
            echo 'Invalid Release Id';
        }
    }
    // else if($_GET["type"]=="printrecord") {
    //     $sql = "SELECT * FROM batch_release";
    //     $result = $conn->query($sql);
    //     if ($result->num_rows > 0) {
    //         $_GET['filename'] = 'Batch Release Record'; $_GET['pdftype'] = 'onlyheader';  include("../pdfimp.php");
    //         $html.='
    //         <style>td { border:solid 1px BCBBBA;}</style>
    // 	    <table cellpadding="5" style="text-align:center;">
    // 	        <tr style="background-color:#DDDAD9; font-weight:bold;">
    // 	            <td style="width:10%;">Date</td>
    // 	            <td style="width:25%;">Product Name</td>
    // 	            <td style="width:12%;">Grade</td>
    // 	            <td style="width:12%;">Batch No.</td>
    // 	            <td style="width:15%;">Batch Release Date</td>
    // 	            <td style="width:12%;">Quantity</td>
    // 	            <td style="width:14%;">Batch Released By</td>
    // 	        </tr>';
    //             while ($row = $result->fetch_assoc()) {
    //                 $sql1 = "SELECT * FROM product WHERE product_code='".$row['product_code']."'";
    //                 $result1 = $conn->query($sql1);
    //                 $row1 = $result1->fetch_assoc();
    //                 $html.='
    //             <tr>
    // 	            <td>'.date('d/m/Y', strtotime($row['entry_date'])).'</td>
    // 	            <td>'.$row1['product_name'].'</td>
    // 	            <td>'.$row1['grade'].'</td>
    // 	            <td>'.$row['batch_no'].'</td>
    // 	            <td>'.date('d/m/Y', strtotime($row['release_date'])).'</td>
    // 	            <td>'.$row['qty'].'</td>
    // 	            <td>'.$row['entry_by'].'</td>
	   //         </tr>
    //         ';
    //         }
            
    //         $html.='</table>';
    //     }
       
    //     $pdf->writeHTML($html, true, false, false, false, '');
    //     $pdf->Output('BatchRelease.pdf', 'I');
    // }
    
    else if($_GET["type"]=="printrecord") {
        $_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
         $html= "";
          
    		      $html.='
    		     
       <table cellpadding="5" style="text-align:center;">
    	        <tr style="background-color:#DDDAD9; font-weight:bold;">
    	            <td style="width:10%;">Date</td>
    	            <td style="width:25%;">Product Name</td>
    	            <td style="width:12%;">Grade</td>
    	            <td style="width:12%;">Batch No.</td>
    	            <td style="width:15%;">Batch Release Date</td>
    	            <td style="width:12%;">Quantity</td>
    	            <td style="width:14%;">Batch Released By</td>
    	        </tr>';
                while ($row = $result->fetch_assoc()) {
                    $sql1 = "SELECT * FROM product WHERE product_code='".$row['product_code']."'";
                    $result1 = $conn->query($sql1);
                    $row1 = $result1->fetch_assoc();
                    $html.='
                <tr>
    	            <td>'.date('d/m/Y', strtotime($row['entry_date'])).'</td>
    	            <td>'.$row1['product_name'].'</td>
    	            <td>'.$row1['grade'].'</td>
    	            <td>'.$row['batch_no'].'</td>
    	            <td>'.date('d/m/Y', strtotime($row['release_date'])).'</td>
    	            <td>'.$row['qty'].'</td>
    	            <td>'.$row['entry_by'].'</td>
	            </tr>
            ';
            }
             $html.='</table>';
    
            
        
     
     $pdf->writeHTML($html, true, false, false, false, '');
        
        $pdf->Output('downloadCandidatesList.pdf', 'I');
     
     
     
   }
    
    else if($_GET["type"]=="checkingrecorddigital"){
        $sql = "SELECT * FROM batch_release WHERE id='".$_GET['id']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
            $checkpoints = json_decode($row['checkpoints']);
            $_GET['filename'] = 'Finished Product Pack Stock checking Record'; $_GET['pdftype'] = 'headfootdigital';  include("../../pdfimp.php");
            $html='
            <style>td { border:solid 1px BCBBBA;}</style>
        	    <table cellpadding="5">
        	        <tr>
        	            <td><b>Product Name:</b> '.$row['product'].'</td>
        	            <td><b>Batch No.:</b> '.$row['batch_no'].'</td>
        	        </tr>
        	        <tr>
        	            <td style="width:100%;"><b>No of FP Shippers Checked:</b> '.$row['shippers'].'</td>
        	        </tr>
        	        <tr><td style="border:none;"></td></tr>
        	        <tr style="background-color:#DDDAD9; text-align:center;">
        	            <td style="width:7%;"><b>Sr. No</b></td>
        	            <td style="width:54%;"><b>Check points</b></td>
        	            <td style="width:7%;"><b>Ok</b></td>
        	            <td style="width:8%;"><b>Not Ok</b></td>
        	            <td style="width:24%;"><b>Remark/Any Deviation</b></td>
        	        </tr>';
        	        $counter = 1;
        	        $j = 0;
                    for ($i=1; $i <= count($checkpoints); $i++) {
                        $html.='<tr>
            	            <td style="text-align:center;">'.$counter++.'</td>
            	            <td>'.$checkpoints[$j]->checkpoint.'</td>
            	            <td style="text-align:center;">';
            	                if($checkpoints[$j]->status == "Ok"){
            	                    $html.='<span style="font-family:zapfdingbats;">3</span>';
            	                }
            	            $html.='
            	            </td>
            	            <td style="text-align:center;">';
            	                if($checkpoints[$j]->status == "Not Ok"){
            	                    $html.='<span style="font-family:zapfdingbats;">3</span>';
            	                }
            	            $html.='</td>
            	            <td>'.$checkpoints[$j]->remark.'</td>
            	        </tr>';
            	        $j++;
                    }
                    $html.='
                    <tr>
                        <td style="text-align:center;">'.$counter.'</td>
                        <td style="width:93%;"><b>Other Observations:</b>'.$row['observation'].'</td>
                    </tr>
                </table>
                <table style="border:solid 1px BCBBBA;" cellpadding="5">
                    <tr>
                        <td style="border:none; width:50%;">Checklist Filled By: '.$row['entry_by'].'</td>
                        <td style="border:none; width:50%;">Checklist Verified and Batch Released By:</td>
                    </tr>
                    <br><br>
                    <tr>
                        <td style="border:none;">(IPQA):Sign/ Date:</td>
                        <td style="border:none;">Head QA /Designee Sign/ Date</td>
                    </tr>
        	    </table>
                <div></div>
            ';
            require 'footer.php';
            }
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('BatchRelease.pdf', 'I');
        }else{
            echo 'Invalid Release id';
        }
    }
    else if($_GET["type"]=="certificatedigital"){
        $sql = "SELECT * FROM batch_release WHERE id='".$_GET['id']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
            $_GET['filename'] = 'BATCH RELEASE CERTIFICATE'; $_GET['pdftype'] = 'headfootdigital';  include("../../pdfimp.php");
            $html.='
        	    <table cellpadding="5">
        	        <tr>
        	            <td style="width:25%;"><b>Product Name</b></td>
        	            <td><b>: '.$row['product'].'</b></td>
        	        </tr>
        	        <tr>
        	            <td><b>Batch No.</b></td>
        	            <td><b>: '.$row['batch_no'].'</b></td>
        	        </tr>
        	        <tr>
        	            <td><b>Mfg. Date</b></td>
        	            <td><b>: '.$row['mfg_date'].'</b></td>
        	        </tr>
        	        <tr>
        	            <td><b>Exp. Date</b></td>
        	            <td><b>: '.$row['exp_date'].'</b></td>
        	        </tr>
        	        <tr>
        	            <td><b>Batch Size</b></td>
        	            <td><b>: '.$row['batch_size'].'</b></td>
        	        </tr>
                </table>
                <p style="line-height:1.7; text-align:justify;">Following documents of this Batch has been reviewed by Quality Assurance department of GMP Software Pvt Ltd</p>
                <ol style="font-weight:bold; line-height:1.7;">
                    <li>Batch Manufacturing Record</li>
                    <li>Batch Packing Record</li>
                    <li>Finished product COA and In process analysis reports along with raw data.</li>
                </ol>
                <p style="line-height:1.7; text-align:justify;">This is to be certified that this batch is meeting all the quality parameters as per specification. This Batch can be distributed on or After Date: ____________<br>This certificate is issued by Quality Assurance Department of GMP Software Pvt Ltd.</p>
                <div></div>
                <table>
                    <br><br><br><br><br><br>
                    <tr>
                        <td><b>Head Quality Assurance</b><br>GMP Software Pvt Ltd</td>
                        <td style="text-align:right;">&nbsp;<br><b>Date : </b>____________</td>
                    </tr>
                </table>';
            }
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('BatchRelease.pdf', 'I');
        }
        else{
            echo 'Invalid Release Id';
        }
    }
} else {
    echo "[]";
}
?>