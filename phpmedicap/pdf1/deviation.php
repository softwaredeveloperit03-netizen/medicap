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
    
    if($_GET['type'] == 'deviation'){
        $sql = "SELECT * FROM deviation WHERE dev_no='".$_GET["dev_no"]."'";
	    $result = $conn->query($sql);
	    if ($result->num_rows > 0) {
	        while ($row = $result->fetch_assoc()) {
	            $product_detail = json_decode($row['product_details']);
	            $_GET['filename'] = 'Deviation Form'; $_GET['pdftype'] = 'headfoot';  include("../pdfimp.php");
                $html.='
                <style>td { border:solid 1px BCBBBA;}</style>
    	        <table cellpadding="5">
    	        <tr>
    	            <td style="width:50%;"><b>Deviation No :</b> '.$_GET["dev_no"].'</td>
    	            <td style="width:50%;"><b>Received Date / Sign of QA :</b></td>
    	        </tr>
    	        <tr>
    	            <td style="border-right:none;"><b>Name of the Initiator :</b> '.$row['entry_by'].' / '.$rowemp['emp_name'].'</td>
    	            <td><b>Department : </b>'.$row['department'].'</td>
    	        </tr>
    	        <tr>
    	            <td style="width:70%;"><b>Name of Product&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:</b> '; 
    	                if($product_detail->product_code == ''){
    	                    $html.='Not Applicable';
    	                }else{
    	                    $html.=$product_detail->product_code;
    	                }
    	                $html.='</td>
    	            <td style="width:30%;"><b>Mfg. Date :</b> ';
    	            if($product_detail->mfg_date == ''){
    	                    $html.='Not Applicable';
    	                }else{
    	                    $html.=date('M-Y', strtotime($product_detail->mfg_date));
    	                }
    	                $html.='</td>
    	        </tr>
    	        <tr>
    	            <td style="width:70%;"><b>Batch No. &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:</b> '; 
    	                if($product_detail->batch_no == ''){
    	                    $html.='Not Applicable';
    	                }else{
    	                    $html.=$product_detail->batch_no;
    	                }
    	                $html.='</td>
    	            <td style="width:30%;"><b>Exp. Date :</b> '; 
    	                if($product_detail->exp_date == ''){
    	                    $html.='Not Applicable';
    	                }else{
    	                    $html.=date('M-Y', strtotime($product_detail->exp_date));
    	                }
    	                $html.='
    	            </td>
    	        </tr>
    	    </table>
    	    <table cellpadding="5">
    	        <tr>
    	            <td style="width:100%;">
    	                <table cellpadding="3">
    	                    <tr>
    	                        <td style="width:100%; border:none;"><b>Description of Deviation / Root Cause :</b></td>
    	                    </tr>
    	                    <tr>
    	                        <td style="width:30%; border:none;">Description of Deviation: </td>
    	                        <td style="width:70%; border:none;">'.$row['description'].'</td>
    	                    </tr>
    	                    <tr>
    	                        <td style="border:none;">Root Cause :</td>
    	                        <td style="border:none;">'.$row['cause'].'</td>
    	                    </tr>
    	                    <tr>
    	                        <td style="border:none; width:50%;"><b>Types of deviation :</b> '.$row['type'].'</td>
    	                        <td style="border:none; width:50%;"><b>Justification for type :</b> '.$row['justification'].'</td>
    	                    </tr>
    	                    <tr>
    	                        <td style="border:none; width:30%;"><b>Impact of quantity:</b></td>
    	                        <td style="border:none; width:70%;"></td>
    	                    </tr>
    	                    <tr>
    	                        <td style="border:none; width:50%;"><br><br><br>Initiat by :
        	                        &nbsp;<br>Digital Signed - <img src="1.png"> '.$row['entry_by'].'<br>Date : '.date('d/m/Y', strtotime($row['entry_date'])).'
    	                        </td>
    	                        <td style="border:none; width:50%;">&nbsp;<br><br>Sign of Head of Initiating Department:</td>
    	                    </tr>
    	                </table>
    	            </td>
    	        </tr>
    	    </table>
    	    <div></div>
    	    <table cellpadding="5" style="width:100%;">
    	        <tr>
    	            <td style="width:100%"><b>Comments (Heads of concerned department):</b></td>
    	        </tr>
    	        <tr>
    	            <td style="width:20%; text-align:center;"><b>Department</b></td>
    	            <td style="width:40%; text-align:center;"><b>Comments</b></td>
    	            <td style="width:20%; text-align:center;"><b>Digitaly Signed</b></td>
    	            <td style="width:20%; text-align:center;"><b>Sign and Date</b></td>
    	        </tr>';
    	        $sql2 = "SELECT * FROM deviation_comments WHERE dev_no='".$_GET["dev_no"]."'";
    		    $result2 = $conn->query($sql2);
    		    if ($result2->num_rows > 0) {
    		        while ($row2 = $result2->fetch_assoc()) {
        	        $html.='
        	        <tr>
        	            <td>'.$row2['department'].'</td>
        	            <td>'.$row2['comment'].'</td>
        	            <td>';if($row2['entry_date'] != ''){
        	               $html.='<img src="1.png"> '.$row2['entry_by'].'<br>'.date('d/m/Y', strtotime($row2['entry_date'])).'';
        	               } $html.='
        	            </td>
        	            <td></td>
        	        </tr>';
    		        }
    		    }
    		    $html.='
    		    <tr>
    		        <td style="width:100%;"><b>Risk Evaluation: </b> Required <input type="checkbox" name="box" value="1"  /> Not Required <input type="checkbox" name="box" value="1"  />'.$row['risk_evalution'].'</td>
    		    </tr>
    		    <tr>
    		        <td>
    		            <b>Risk Assessment conclusion:</b><br>'.$row['risk_assessment'].'<br><br>
    		            <p style="text-aligm:right;">Head QA / Sign Date</p>
    		        </td>
    		    </tr>
    		    <tr>
    		        <td style="width:50%;">Classify Deviation : <br>
    		        Critical <input type="checkbox" name="box1" value="1"  />
    		        Major <input type="checkbox" name="box2" value="1"  />
    		        Minor <input type="checkbox" name="box3" value="1"  /></td>
    		        <td style="width:50%;">Sign / Date of QA Manager :</td>
    		    </tr>
    		    <tr>
    		        <td style="width:100%">
    		            Clients / Vendors comments if any: <br><br><br>
    		            <table>
    		                <td>Name of Vendor : </td>
    		                <td>Head of QA / Sign Date : </td>
    		            </table>
    		        </td>
    		    </tr>
    		    <tr>
    		        <td>Corrective action required : Yes <input type="checkbox" name="box2" value="1"  /> No <input type="checkbox" name="box2" value="1"  /></td>
    		    </tr>
    		    <tr>
    		        <td>Preventive action required : Yes <input type="checkbox" name="box2" value="1"  /> No <input type="checkbox" name="box2" value="1"  /></td>
    		    </tr>
    		    <tr>
    		        <td>CAPA No.</td>
    		    </tr>
    		    <tr>
    		        <td>
    		            <table cellpadding="5">
        		            <tr>
        		                <td style="border:none;"><b>Deviation Closure:</b></td>
        		            </tr>
    		                <tr>
    		                    <td style="border:none;">CAPA Remark : </td>
    		                </tr>
    		                <tr>
    		                    <td style="border:none;">
    		                        Deviation Implementation :  
    		                        Yes <input type="checkbox" name="box2" value="1"  /> 
    		                        No <input  type="checkbox" name="box2" value="1"  /></td>
    		                </tr>
    		                <tr>
    		                    <td style="border:none;">Remark : </td>
    		                </tr>
    		                <tr>
    		                    <td style="border:none;">Implementation Department Head</td>
    		                    <td style="border:none;">Sign Date QA Head</td>
    		                </tr>
    		            </table>
    		        </td>
    		    </tr>
    	    </table>';
    	    EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('Deviation.pdf', 'I');
	    }
	    }else{
	        echo 'Invalid Deviation';
	    }
    }
    else if($_GET['type'] == 'deviationdigital'){
        $sql = "SELECT * FROM deviation WHERE dev_no='".$_GET["dev_no"]."'";
	    $result = $conn->query($sql);
	    if ($result->num_rows > 0) {
	        while ($row = $result->fetch_assoc()) {
	            $product_detail = json_decode($row['product_details']);
	            $_GET['filename'] = 'Deviation Form'; $_GET['pdftype'] = 'headfootdigital';  include("../pdfimp.php");
                $html.='
                <style>td { border:solid 1px BCBBBA;}</style>
    	        <table cellpadding="5">
        	        <tr>
        	            <td style="width:50%;"><b>Deviation No :</b> '.$_GET["dev_no"].'</td>
        	            <td style="width:50%;"><b>Received Date / Sign of QA :</b></td>
        	        </tr>
        	        <tr>
        	            <td style="border-right:none;"><b>Name of the Initiator :</b> '.$row['entry_by'].' / '.$rowemp['emp_name'].'</td>
        	            <td><b>Department : </b>'.$row['department'].'</td>
        	        </tr>
        	        <tr>
        	            <td style="width:70%;"><b>Name of Product&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:</b> '; 
        	                if($product_detail->product_code == ''){
        	                    $html.='Not Applicable';
        	                }else{
        	                    $html.=$product_detail->product_code;
        	                }
        	                $html.='</td>
        	            <td style="width:30%;"><b>Mfg. Date :</b> ';
        	            if($product_detail->mfg_date == ''){
        	                    $html.='Not Applicable';
        	                }else{
        	                    $html.=date('M-Y', strtotime($product_detail->mfg_date));
        	                }
        	                $html.='</td>
        	        </tr>
    	        <tr>
    	            <td style="width:70%;"><b>Batch No. &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:</b> '; 
    	                if($product_detail->batch_no == ''){
    	                    $html.='Not Applicable';
    	                }else{
    	                    $html.=$product_detail->batch_no;
    	                }
    	                $html.='</td>
    	            <td style="width:30%;"><b>Exp. Date :</b> '; 
    	                if($product_detail->exp_date == ''){
    	                    $html.='Not Applicable';
    	                }else{
    	                    $html.=date('M-Y', strtotime($product_detail->exp_date));
    	                }
    	                $html.='
    	            </td>
    	        </tr>
    	    </table>
    	    <table cellpadding="5">
    	        <tr>
    	            <td style="width:100%;">
    	                <table cellpadding="3">
    	                    <tr>
    	                        <td style="width:100%; border:none;"><b>Description of Deviation / Root Cause :</b></td>
    	                    </tr>
    	                    <tr>
    	                        <td style="width:30%; border:none;">Description of Deviation: </td>
    	                        <td style="width:70%; border:none;">'.$row['description'].'</td>
    	                    </tr>
    	                    <tr>
    	                        <td style="border:none;">Root Cause :</td>
    	                        <td style="border:none;">'.$row['cause'].'</td>
    	                    </tr>
    	                    <tr>
    	                        <td style="border:none; width:50%;"><b>Types of deviation :</b> '.$row['type'].'</td>
    	                        <td style="border:none; width:50%;"><b>Justification for type :</b> '.$row['justification'].'</td>
    	                    </tr>
    	                    <tr>
    	                        <td style="border:none; width:30%;"><b>Impact of quantity:</b></td>
    	                        <td style="border:none; width:70%;"></td>
    	                    </tr>
    	                    <tr>
    	                        <td style="border:none; width:50%;"><br><br><br>Initiat by :
        	                        &nbsp;<br>Digital Signed - <img src="1.png"> '.$row['entry_by'].'<br>Date : '.date('d/m/Y', strtotime($row['entry_date'])).'
    	                        </td>
    	                        <td style="border:none; width:50%;">&nbsp;<br><br>Sign of Head of Initiating Department:</td>
    	                    </tr>
    	                </table>
    	            </td>
    	        </tr>
    	    </table>
    	    <div></div>
    	    <table cellpadding="5" style="width:100%;">
    	        <tr>
    	            <td style="width:100%"><b>Comments (Heads of concerned department):</b></td>
    	        </tr>
    	        <tr>
    	            <td style="width:20%; text-align:center;"><b>Department</b></td>
    	            <td style="width:50%; text-align:center;"><b>Comments</b></td>
    	            <td style="width:30%; text-align:center;"><b>Digitaly Sign and Date</b></td>
    	        </tr>';
    	        $sql2 = "SELECT * FROM deviation_comments WHERE dev_no='".$_GET["dev_no"]."'";
    		    $result2 = $conn->query($sql2);
    		    if ($result2->num_rows > 0) {
    		        while ($row2 = $result2->fetch_assoc()) {
        	        $html.='
        	        <tr>
        	            <td>'.$row2['department'].'</td>
        	            <td>'.$row2['comment'].'</td>
        	            <td>';if($row2['entry_date'] != ''){
        	               $html.='<img src="1.png"> '.$row2['entry_by'].' - '.date('d/m/Y', strtotime($row2['entry_date'])).'';
        	               } $html.='
        	            </td>
        	        </tr>';
    		        }
    		    }
    		    $html.='
    		    <tr>
    		        <td style="width:100%;"><b>Risk Evaluation: </b> Required <input type="checkbox" name="box" value="1"  /> Not Required <input type="checkbox" name="box" value="1"  />'.$row['risk_evalution'].'</td>
    		    </tr>
    		    <tr>
    		        <td>
    		            <b>Risk Assessment conclusion:</b><br>'.$row['risk_assessment'].'<br><br>
    		            <p style="text-aligm:right;">Head QA / Sign Date</p>
    		        </td>
    		    </tr>
    		    <tr>
    		        <td style="width:50%;">Classify Deviation : <br>
    		        Critical <input type="checkbox" name="box1" value="1"  />
    		        Major <input type="checkbox" name="box2" value="1"  />
    		        Minor <input type="checkbox" name="box3" value="1"  /></td>
    		        <td style="width:50%;">Sign / Date of QA Manager :</td>
    		    </tr>
    		    <tr>
    		        <td style="width:100%">
    		            Clients / Vendors comments if any: <br><br><br>
    		            <table>
    		                <td>Name of Vendor : </td>
    		                <td>Head of QA / Sign Date : </td>
    		            </table>
    		        </td>
    		    </tr>
    		    <tr>
    		        <td>Corrective action required : Yes <input type="checkbox" name="box2" value="1"  /> No <input type="checkbox" name="box2" value="1"  /></td>
    		    </tr>
    		    <tr>
    		        <td>Preventive action required : Yes <input type="checkbox" name="box2" value="1"  /> No <input type="checkbox" name="box2" value="1"  /></td>
    		    </tr>
    		    <tr>
    		        <td>CAPA No.</td>
    		    </tr>
    		    <tr>
    		        <td>
    		            <table cellpadding="5">
        		            <tr>
        		                <td style="border:none;"><b>Deviation Closure:</b></td>
        		            </tr>
    		                <tr>
    		                    <td style="border:none;">CAPA Remark : </td>
    		                </tr>
    		                <tr>
    		                    <td style="border:none;">
    		                        Deviation Implementation :  
    		                        Yes <input type="checkbox" name="box2" value="1"  /> 
    		                        No <input  type="checkbox" name="box2" value="1"  /></td>
    		                </tr>
    		                <tr>
    		                    <td style="border:none;">Remark : </td>
    		                </tr>
    		                <tr>
    		                    <td style="border:none;">Implementation Department Head</td>
    		                    <td style="border:none;">Sign Date QA Head</td>
    		                </tr>
    		            </table>
    		        </td>
    		    </tr>
    	    </table>';
	    }
    	    EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('Deviation.pdf', 'I');
	    }else{
	        echo 'Invalid Deviation';
	    }
    }
    else if($_GET["type"] == 'deviationlog'){
        $_GET['pdfpagebr'] = '15'; $_GET['pdfpage'] = 'L';
        if(isset($_GET['fromdate']) && $_GET['category'] != ''){
            $sql = "SELECT * FROM deviation WHERE category = '".$_GET['category']."' AND  DATE(entry_date) BETWEEN '".$_GET["fromdate"]."' AND '".$_GET["todate"]."' ORDER by id DESC";
        }else if(isset($_GET['fromdate']) && $_GET['category'] == ''){
            $sql = "SELECT * FROM deviation WHERE DATE(entry_date) BETWEEN '".$_GET["fromdate"]."' AND '".$_GET["todate"]."' ORDER by id DESC";
        }else if(isset($_GET['fromdate'])){
            $sql = "SELECT * FROM deviation WHERE DATE(entry_date) BETWEEN '".$_GET["fromdate"]."' AND '".$_GET["todate"]."' ORDER by id DESC";
        } else{
            $sql = "SELECT * FROM deviation ORDER by id DESC";
        }
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $_GET['filename'] = 'Deviation Log'; $_GET['pdftype'] = 'headfoot';  include("../pdfimp.php");
            $html.='
                <table cellpadding="5">
                    <tr>
                        <td><b>From Date :</b> '.$_GET['fromdate'].'</td>
                        <td><b>Department : </b>';
                            if($_GET['getdepartment'] != ''){
                                $html.=''.$_GET['getdepartment'].'';
                            }else{
                                $html.='All Department';
                            }
                            $html.='
                        </td>
                    </tr>
                    <tr>
                        <td><b>To Date &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:</b> '.$_GET['todate'].'</td>
                        
                        <td><b>Category  &nbsp;&nbsp;&nbsp;&nbsp;: </b>';
                            if($_GET['category'] != ''){
                                $html.=''.$_GET['category'].'';
                            }else{
                                $html.='All';
                            }
                            $html.='
                        </td>
                    </tr>
                </table>
                <div></div>
                <table cellpadding="5">
                    <thead>
                        <tr style="background-color:#DDDAD9; font-weight:bold;">
                            <td style="width:4%;">Sr No.</td>
                            <td style="width:12%;">Department</td>
                            <td style="width:9%;">Deviation No</td>
                            <td style="width:15%;">Category</td>
                            <td style="width:10%;">Initiated By</td>
                            <td style="width:10%;">Initiated Date</td>
                            <td style="width:10%;">Status</td>
                            <td style="width:10%;">Approved By (QA Head)</td>
                            <td style="width:10%;">Date of Approve</td>
                            <td style="width:10%;">Date of Closure</td>
                        </tr>
                    </thead>
                    <tbody>';
                    $counter = 1;
                    while ($row = $result->fetch_assoc()) {
                    $html.='<tr nobr="true">
                            <td style="width:4%;">'.$counter++.'</td>
                            <td style="width:12%;">'.$row['department'].'</td>
                            <td style="width:9%;">'.$row['dev_no'].'</td>
                            <td style="width:15%; text-align:left;">'.$row['category'].'</td>
                            <td style="width:10%;">'.$row["entry_by"].'</td>
                            <td style="width:10%;">'.date('d/m/Y', strtotime($row['entry_date'])).'</td>
                            <td style="width:10%;">'.$row["status"].'</td>
                            <td style="width:10%;">'.$row["approve_by"].'</td>
                            <td style="width:10%;">';
                                if($row['approve_date'] != null){
                                $html.=''.date('d/m/Y', strtotime($row['approve_date'])).'';
                                }
                                $html.='</td>
                            <td style="width:10%;">';
                                if($row['close_date'] != null){
                                $html.=''.date('d/m/Y', strtotime($row['close_date'])).'';
                                }
                                $html.='</td>
                            </tr>';
                    }
                    $html.='</tbody>
                </table>';
        }
        EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('deviation log.pdf', 'I');
    }
    else if($_GET["type"] == 'deviationlogDept'){
        if(isset($_GET['fromdate']) && $_GET['category'] != ''){
            $sql = "SELECT * FROM deviation WHERE department = '".$_GET['department']."' AND category = '".$_GET['category']."' AND  DATE(entry_date) BETWEEN '".$_GET["fromdate"]."' AND '".$_GET["todate"]."' ORDER by id DESC";
        }else if(isset($_GET['fromdate']) && $_GET['category'] == ''){
            $sql = "SELECT * FROM deviation WHERE department = '".$_GET['department']."' AND  DATE(entry_date) BETWEEN '".$_GET["fromdate"]."' AND '".$_GET["todate"]."' ORDER by id DESC";
        }else if(isset($_GET['fromdate'])){
            $sql = "SELECT * FROM deviation WHERE DATE(entry_date) BETWEEN '".$_GET["fromdate"]."' AND '".$_GET["todate"]."' ORDER by id DESC";
        } else{
            $sql = "SELECT * FROM deviation ORDER by id DESC";
        }
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $_GET['filename'] = 'Deviation Log'; $_GET['pdftype'] = 'headfoot';  include("../pdfimp.php");
            $html='
                <table cellpadding="5">
                    <tr>
                        <td><b>From Date :</b> '.$_GET['fromdate'].'</td>
                        <td><b>Department : </b>'.$_GET["department"].'</td>
                    </tr>
                    <tr>
                        <td><b>To Date &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:</b> '.$_GET['todate'].'</td>
                        <td><b>Category &nbsp;&nbsp;&nbsp;&nbsp;: </b>';
                            if($_GET['category'] != ''){ $html.=''.$_GET['category'].'';
                            }else{ $html.='All'; }
                            $html.='
                        </td>
                    </tr>
                </table>
                <div></div>
                <table cellpadding="5">
                    <thead>
                        <tr style="background-color:#DDDAD9; font-weight:bold;">
                            <td style="width:4%;">Sr No.</td>
                            <td style="width:12%;">Department</td>
                            <td style="width:9%;">Deviation No</td>
                            <td style="width:15%;">Category</td>
                            <td style="width:10%;">Initiated By</td>
                            <td style="width:10%;">Initiated Date</td>
                            <td style="width:10%;">Status</td>
                            <td style="width:10%;">Approved By (QA Head)</td>
                            <td style="width:10%;">Date of Approve</td>
                            <td style="width:10%;">Date of Closure</td>
                        </tr>
                    </thead>
                    <tbody>';
                    $counter = 1;
                    while ($row = $result->fetch_assoc()) {
                    $html.='<tr nobr="true">
                            <td style="width:4%;">'.$counter++.'</td>
                            <td style="width:12%;">'.$row['department'].'</td>
                            <td style="width:9%;">'.$row['dev_no'].'</td>
                            <td style="width:15%; text-align:left;">'.$row['category'].'</td>
                            <td style="width:10%;">'.$row["entry_by"].'</td>
                            <td style="width:10%;">'.date('d/m/Y', strtotime($row['entry_date'])).'</td>
                            <td style="width:10%;">'.$row["status"].'</td>
                            <td style="width:10%;">'.$row["approve_by"].'</td>
                            <td style="width:10%;">';
                                if($row['approve_date'] != null){
                                $html.=''.date('d/m/Y', strtotime($row['approve_date'])).'';
                                }
                                $html.='</td>
                            <td style="width:10%;">';
                                if($row['close_date'] != null){
                                $html.=''.date('d/m/Y', strtotime($row['close_date'])).'';
                                }
                                $html.='</td>
                            </tr>';
                    }
                    $html.='</tbody>
                </table>';
        }
        EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('deviation log.pdf', 'I');
    }
}else {
    echo "Invalid Token";
}
?>