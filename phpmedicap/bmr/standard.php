<?php 
require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';
$output = Array();
$token = $_GET["token"];
 $currentUrl =$_GET["description"];



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
    if ($_GET["type"] == "download") {
        require '../tcpdf/tcpdf.php';
        $_GET['filename'] = 'Standard Formula'; $_GET['pdftype'] = 'onlyheader';  include('../pdfimp.php');
        $sql = "SELECT m.*, p.product_name, p.grade FROM mfr m LEFT JOIN product p ON m.product_code=p.product_code WHERE m.id='".$_GET["id"]."'";
        $result = $conn->query($sql);
        
        $html="";
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $steps = json_decode($row["steps"]);
                
                $html.='<h3 style="text-align:center;">BATCH MANUFACTURING RECORD</h3>
                        <table border="1" cellpadding="5" >';
              $html.='
                        <tr>
                          <td style="width:20%; text-align:center;">Product Code</td>
                          <td style="width:20%;text-align:center;">'.$row['product_code'].'</td>
                          <td style="width:20%;text-align:center;">Effective Batch No</td>
                          <td style="width:20%;text-align:center;"></td>
                          <td style="width:15%;text-align:center;">Version No</td>
                          <td style="width:5%;text-align:center;"></td>
                        </tr>
                        <tr>
                          <td style="width:20%;text-align:center;">Document No</td>
                          <td style="width:20%;text-align:center;"></td>
                          <td style="width:20%;text-align:center;">Effective Date</td>
                          <td style="width:20%;text-align:center;"></td>
                          <td  colspan="2" style="width=20%;text-align:center;">Page 1 OF 12</td>
                        </tr>
                        <tr>
                          <td style="width:20%;text-align:center;">Product Name</td>
                          <td style="width:40%;text-align:center;">'.$row['product_name'].'</td>
                          <td style="width:20%;text-align:center;">Shelf Life</td>
                          <td style="width:20%;text-align:center;">'.$row['shelf_life'].'</td>
                        </tr>
                        <tr>
                          <td style="width:20%;text-align:center;">Generic Name</td>
                          <td style="width:80%;text-align:center;">'.$row['generic_name'].'</td>
                        </tr>
                        <tr>
                          <td style="width:20%;text-align:center;">Label Claim</td>
                          <td style="width:80%;text-align:center;">'.$row['label_claim'].'<br><br><br><br></td>
                        </tr>
                        <tr>
                          <td style="width:20%;text-align:center;">Batch No.</td>
                          <td style="width:27%;text-align:center;"></td>
                          <td style="width:26%;text-align:center;">Batch Size</td>
                          <td style="width:27%;text-align:center;">'.$row['batch_size'].'</td>
                        </tr>
                        <tr>
                          <td style="width:20%;text-align:center;">MFG. Date</td>
                          <td style="width:27%;text-align:center;"></td>
                          <td style="width:26%;text-align:center;">EXP. Date</td>
                          <td style="width:27%;text-align:center;"></td>
                        </tr>
                        <tr>
                          <td style="width:20%;text-align:center;">STD. Batch Size<br>(In Units)</td>
                          <td style="width:27%;text-align:center;"></td>
                          <td style="width:26%;text-align:center;">STD. Batch Size<br>(In kg)</td>
                          <td style="width:27%;text-align:center;"></td>
                        </tr>
                        <tr>
                          <td style="width:20%;text-align:center;text-align:center;text-align:center;text-align:center;text-align:center;">MARKET</td>
                          <td style="width:27%;text-align:center;text-align:center;text-align:center;text-align:center;">'.$row['market'].'</td>
                          <td style="width:26%;text-align:center;text-align:center;text-align:center;">Mfg. Lic. No.</td>
                          <td style="width:14%;text-align:center;text-align:center;">'.$row['mfg_lic'].'</td>
                          <td style="width:13%;text-align:center;">Validity up to:<br></td>
                        </tr>
                        <tr>
                          <td style="width:20%;text-align:center;">Issued By QA<br> Sign & Date</td>
                          <td style="width:27%;text-align:center;"></td>
                          <td style="width:26%;text-align:center;">Received By Production <br>Sign & Date</td>
                          <td style="width:27%;text-align:center;"></td>
                        </tr>
                        <tr>
                          <td style="width:20%;text-align:center;">Total No.of BMR<br> Pages Issued</td>
                          <td style="width:27%;text-align:center;"></td>
                          <td style="width:26%;text-align:center;">No.of Autoclavable<br> Pages Issued</td>
                          <td style="width:27%;text-align:center;"></td>
                        </tr>
                        <tr>
                          <td style="width:20%;text-align:center;">MFG. Commenced On</td>
                          <td style="width:27%;text-align:center;"></td>
                          <td style="width:26%;text-align:center;">MFG. Completed On</td>
                          <td style="width:27%;text-align:center;"></td>
                        </tr>
                        <tr>
                          <td rowspan="2"style=text-align:center;"width:20%;">Yield Limit : (97.00 %<br> to 99.5%) & (87 % to<br> 96.99 % for Batch size below 5000 )</td>
                          <td rowspan="2"style=text-align:center;"width:27%;"></td>
                          <td style="width:26%;text-align:center;">Released Date</td>
                          <td rowspan="2"style=text-align:center;"width:27%;"></td>
                        </tr>
                        <tr>
                           <td style:"width:26%;text-align:center;">No. of Autoclavable Pages Retrieved :</td>
                        </tr>
                        <tr>
                           <td colspan="4"style:"width:100%;text-align:center;">BATCH MANUFACTURING RECORD REVIEW [AFTER COMPLETION OF MFG. ACTIVITY]</td>
                        </tr>
                        <tr>
                          <td style="width:20%;text-align:center;"></td>
                          <td style="width:27%;text-align:center;"></td>
                          <td style="width:26%;text-align:center;"></td>
                          <td style="width:27%;text-align:center;"></td>
                        </tr>
                        <tr>
                          <td style="width:20%;text-align:center;">Production Officer<br> Sign/Date</td>
                          <td style="width:27%;text-align:center;">Production Head<br>Sign/Date</td>
                          <td style="width:26%;text-align:center;">Reviewed By Q.A.<br>Sign/ Date</td>
                          <td style="width:27%;text-align:center;">Approved by - QA Head<br>Sign/Date</td>
                        </tr>
                        
                        
                       ';
                       $html.="</table><div></div>
                       <div></div>
                       <div></div>";
                        
                 $html.='<table border="1" cellpadding="5">
                         <tr>
                            <td style="width:10%;text-align:center;">Section</td>
                            <td style="width:80%;text-align:center;">Content</td>
                            <td style="width:10%;text-align:center;">Page No.</td>
                         </tr>
                         <tr>
                            <td style="width:10%;text-align:center;">1.</td>
                            <td style="width:80%;">General instruction </td>
                            <td style="width:10%;text-align:center;">03 to 03</td>
                         </tr>
                         <tr>
                            <td style="width:10%;text-align:center;">2.</td>
                            <td style="width:80%;">List of Equipment</td>
                            <td style="width:10%;">04 to 04</td>
                         </tr>
                         <tr>
                            <td style="width:10%;text-align:center;">3.</td>
                            <td style="width:80%;">Calculation for fill value</td>
                            <td style="width:10%;">05 to 06</td>
                         </tr>
                         <tr>
                            <td style="width:10%;text-align:center;">4.</td>
                            <td style="width:80%;">Dispensing of Raw material </td>
                            <td style="width:10%;text-align:center;">07 to 09</td>
                         </tr>
                         <tr>
                            <td style="width:10%;text-align:center;">5.</td>
                            <td style="width:80%;">Dispensing of  primary packing material</td>
                            <td style="width:10%;text-align:center;">10 to 11</td>
                         </tr>
                         <tr>
                            <td style="width:10%;text-align:center;">6.</td>
                            <td style="width:80%;">Revision History</td>
                            <td style="width:10%;text-align:center;">12</td>
                         </tr>
                         </table><div></div>
                                <div></div>';
                                
                $html.='<h3 style="text-align:center;">LIST OF EQUIPMENTS</h3>
                        <table border="1" cellpadding="5">
                        <tr>
                            <td style="width:10%">SR.NO.</td>
                            <td style="width:50%">MAJOR EQUIPMENT</td>
                            <td style="width:40%">EQUIPMENT NO.</td>
                        </tr>';
                         for ($i = 0; $i < count($steps); $i++) {
                                $step = $steps[$i];
                                if ($step->stage == "COATING-DISPENSING") {
                                    if ($step->isequipment == 'yes') {
                                        $equipments = $step->equipment;
                                        for ($j = 0; $j < count($equipments); $j++) {
                                            $equipment = $equipments[$j];
                                            $k = $j + 1;
                                            $html.='
                                                <tr>
                                                    <td style="width:10%">'.$k.'</td>
                                                    <td style="width:50%">'.$equipment->equipment.'</td>
                                                    <td style="width:40%"></td>
                                                </tr>';
                                        }
                                    }
                                }
                            }
                         
                        $html.='</table><div></div>';
                 
                 
                 $html.='<h3 style="text-align:center;">LINE CLEARANCE CHECKLIST SOP-QAD/008</h3>
                            <table border="1" cellpadding="5">
                            <tr>
                                <td style="width:10%; text-align:center;">Sr.No</td>
                                <td style="width:70%; text-align:center;">Check point</td>
                                <td style="width:10%; text-align:center;">Stores</td>
                                <td style="width:10%; text-align:center;">QA</td>
                            </tr>';
                            for ($i = 0; $i < count($steps); $i++) {
                                $step = $steps[$i];
                                if ($step->stage == "DISPENSING") {
                                    if ($step->isclearance == 'yes') {
                                        $clearances = $step->clearances;
                                        for ($j = 0; $j < count($clearances); $j++) {
                                            $clearance = $clearances[$j];
                                            $k = $j + 1;
                                            $html.='
                                                <tr>
                                                    <td style="width:10%; text-align:center;">'.$k.'.</td>
                                                    <td style="width:70%;">'.$clearance->checkpoint.'</td>
                                                    <td style="width:10%;"></td>
                                                    <td style="width:10%;"></td>
                                                </tr>';
                                        }
                                    }
                                }
                            }
                $html.='</table><div></div>';
                
                $html.='<h3 style="text-align:center;">BILL OF MATERIAL (MATERIAL FOR BULK)</h3>
                        <div>Manufacturing Date of Blend: ___________ Total Hold time of Blend:_____________ (Limit: NMT 30 Days)</div>
                        <div></div>
                        <table border="1" cellpadding="5">
                        <tr>
                            <td style="width:10%;">Material Code</td>
                            <td style="width:10%;">Vendor Name</td>
                            <td style="width:20%;">Item Name</td>
                            <td style="width:10%;">Standard Quantity<br>(For 10000) Vials</td>
                            <td style="width:10%;">Required<br>Qty.<br>(In Kg)</td>
                            <td style="width:10%;">Issued<br>Qty.<br>(In Kg)</td>
                            <td style="width:10%;">A.R. No.</td>
                            <td style="width:10%;">Mfg. Date</td>
                            <td style="width:10%;">Exp. Date</td>
                        </tr>
                        <tr>
                            <td style="width:10%;"></td>
                            <td style="width:10%;"></td>
                            <td style="width:20%;"></td>
                            <td style="width:10%;"></td>
                            <td style="width:10%;"></td>
                            <td style="width:10%;"></td>
                            <td style="width:10%;"></td>
                            <td style="width:10%;"></td>
                            <td style="width:10%;"></td>
                        </tr>';
                $html.='</table><div></div>
                                <div></div>
                                <div></div>';
                
                $html.='<h3 style="text-align:center;">WEIGHT OF STERILE BULK</h3>
                        <table border="1" cellpadding="5">
                            <tr>
                                <td rowspan="2" style="width:10%;text-align:center;">Sr.No.</td>
                                <td rowspan="2" style="width:10%;text-align:center;">Container No.</td>
                                <td style="width:80%;text-align:center;">Wt. Of Material</td>
                            </tr>
                            <tr>
                                <td style="width:25%;text-align:center;">Gross Wt.(in kg)</td>
                                <td style="width:25%;text-align:center;">Tare Wt.(in kg)</td>
                                <td style="width:30%;text-align:center;">Net Wt.(in kg)</td>
                            </tr>
                            <tr><td style="width:10%;"></td>
                                <td style="width:10%;"></td>
                                <td style="width:25%;"></td>
                                <td style="width:25%;"></td>
                                <td style="width:30%;"></td>
                            </tr>
                        </table>
';
                }
        }
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('','I');
    }else if ($_GET["type"] == "") {
        $_GET['filename'] = 'MFR'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp.php");
        $html= "";
        $sql = "SELECT m.*, DATE(m.entry_date) as entry_date, p.product_name, p.grade, p.dosage_form, p.generic_name, p.shelf_life, p.label_claim FROM mfr m LEFT JOIN product p ON m.product_code=p.product_code WHERE m.id='".$_GET["id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["raw_materials"] = json_decode($row["raw_materials"]);
                $row["additional_materials"] = json_decode($row["additional_materials"]);
                $row["packing_materials"] = json_decode($row["packing_materials"]);
                $row["equipments"] = json_decode($row["equipments"]);
        $html.='<table border="1" cellpadding="5" style="font-size:12px;">
                <tr>
                    <td style="width:30%;"><b>Label Claim:</b</td>
                    <td style="width:70%;"><b>Each ml contains:</b>>'.$row['label_claim'].'</td>
                </tr>
                <tr>
                    <td style="width:30%;"><b>Primary pack description:</b></td>
                    <td style="width:70%;">'.$row['pack_description'].'</td>
                </tr>
                <tr>
                    <td style="width:30%;"><b>Storage Condition:</b></td>
                    <td style="width:70%;"></td>
                </tr>
                <tr>
                    <td style="width:30%;"><b>Product Appearance:</b></td>
                    <td style="width:70%;"></td>
                </tr>
                <tr>
                    <td style="width:30%;"><b>Effective Date:</b></td>
                    <td style="width:70%;"></td>
                </tr>
                <tr>
                    <td style="width:30%;"><b>Issued By (QA) Sign/Date:</b></td>
                    <td style="width:70%;"></td>
                </tr>
                <tr>
                    <td style="width:30%;"><b>Received By (Production) Sign/Date:</b></td>
                    <td style="width:70%;"></td>
                </tr>
                <tr>
                    <td style="width:30%;"><b>Batch Commencement Date:</b></td>
                    <td style="width:70%;"></td>
                </tr>
                <tr>
                    <td style="width:30%;"><b>Batch Completion Date:</b></td>
                    <td style="width:70%;"></td>
                </tr>
                ';
        $html.='</table><div></div>';
        
        $html.='<table border="1" cellpadding="5" style=" font-size:12px;">
                <tr>
                    <td rowspan="2" style="width:20%;"><b></b></td>
                    <td style="width:15%;"><b>Prepared By</b></td>
                    <td style="width:15%;"><b>Checked By</b></td>
                    <td colspan="2" style="width:30%; text-align:center;"><b>Reviewed By</b></td>
                    <td style="width:20%;"><b>Approved By</b></td>
                </tr>
                <tr>
                    <td style="width:15%;"><b>QA</b></td>
                    <td style="width:15%;"><b>Production</b></td>
                    <td style="width:15%;"><b>Production</b></td>
                    <td style="width:15%;"><b>QA</b></td>
                    <td style="width:20%;"><b>Head QA</b></td>
                </tr>
                <tr >
                    <td style="width:20%;"><b>Name</b></td>
                    <td style="width:15%;"></td>
                    <td style="width:15%;"></td>
                    <td style="width:15%;"></td>
                    <td style="width:15%;"></td>
                    <td style="width:20%;"></td>
                </tr>
                <tr>
                    <td style="width:20%;"><b>Sign & Date</b></td>
                    <td style="width:15%;"></td>
                    <td style="width:15%;"></td>
                    <td style="width:15%;"></td>
                    <td style="width:15%;"></td>
                    <td style="width:20%;"></td>
                </tr>';
        $html.='</table><div></div>';
        
        $html.='<h3>STAGE 1.0 GENERAL INSTRUCTION</h3>
                <ul type="square" style="font-size:12px;">
                    <li>Do not alter or over write letters and numbers.</li>
                    <li>All the entries should be correct and legible.</li>
                    <li>Do not use staples/paper clip in packing material.</li>
                    <li>Follow GDP practices in case of wrong entry, cut single line on entry error and write the correct data. Write the entry error remark along with signature and date.</li>
                    <li>“Checked by” or “Reviewed by” cannot be signed prior to the “Done by/performed by”.</li>
                    <li>Check the availability of packing materials of specified batch before packing process.</li>
                    <li>Before starting the packing activity check the cleanliness of areas and equipment’s as per the current version SOP’s practices.</li>
                    <li>Line clearance shall be performed by QA before operation of the each & every stage as mentioned in BPR.</li>
                    <li>Follow Good Documentation Practice (GDP) during execution of BPR</li>
                    <li>Do not keep blank page, Strike the blank space & Put “NA” acknowledge with signature /Date. </li>
                    <li>Record all data by using blue ball pen for production person & green ball pen for IPQA person.</li>
                    <li>Record time as HH:MM format or HH:MM:SS in 24 hours format.</li>
                    <li>Record Date in DD/MM/YYYY or DD/MM/YY or DD-MM-YYYY or DD-MM-YY format. Do not leave any column in document unfilled. If any column in a document is not applicable, write ‘Not Applicable’ (NA) along with sign & date. If any column used for recording quantity write the number, if the quantity is zero then write the number “00”.</li>
                    <li>Encircle the correct choice, if choice is given.</li>
                    <li>Personnel signing the document shall put the ‘Date’ along with the signature and remark for better clarity. </li>
                    <li>Record discrepancies and deviation in defined summary place.</li>
                    <li>All operation must be performed in accordance with current Good Manufacturing Practices.</li>
                    <li>Any deviation observed during batch processing should be informed to Production Head, QA Head and duly recorded.</li>
                    <li>Machine breakdown pertaining to packing equipment’s during processing assessed for its impact on product quality by Production Head & to be logged under deviation, if required.</li>
                    <li>Record the details of following activities along with the date & time in BPR.<br> &nbsp;&nbsp;a) Trial taken b) Unusual observation c) If any correction done.</li>
                    <li>Quality Control Department must approve all packing materials before dispensing.</li>
                    <li>Equipment’s to be suitably labeled indicating the current status with date.</li>
                    <li>In- process control must be strictly followed and ensured the data must be recorded at regular interval in the batch packing record.</li>
                    <li>All entries should be legible, correct and signatures are with their corresponding dates.</li>
                    <li>After Completion of BMR, Reviewed by the concerned HOD & Submitted to QA.</li>
                   
                </ul>
                <div></div>';
                
        $html.='<h3>STAGE: 2.0 DISPENSING OF PACKING MATERIAL</h3>
                <span style="font-size:12px;"> &nbsp;&nbsp;2.1 Take line clearance of packing material dispensing area as per SOP No. BPL/GEN/QAI/004.</span><br>
                <div></div>
                <table border="1" cellpadding="5" style="font-size:12px;">
                <tr>
                    <td style=" width:100%; text-align:center;"><b>Table Number : 2.1</b></td>
                </tr>
                <tr>
                    <td rowspan="2" style="width:10%; text-align:center;"><b>Sr. No.</b></td>
                    <td rowspan="2" style="width:50%; text-align:center;"><b>Checks Points</b></td>
                    <td style="width:20%;"><b>Date:</b></td>
                    <td style="width:20%;"><b>Time:</b></td>
                </tr>
                <tr>
                    <td style="width:20%;"><b>Date:</b></td>
                    <td style="width:20%;"><b>Time:</b></td>
                </tr>
                <tr>
                    <td style="width:10%;">01</td>
                    <td style="width:50%;">Previous Product Name</td>
                    <td style="width:20%;"></td>
                    <td style="width:20%;"></td>
                </tr>
                <tr>
                    <td style="width:10%;">02</td>
                    <td style="width:50%;">Previous Product Batch .No.</td>
                    <td style="width:20%;"></td>
                    <td style="width:20%;"></td>
                </tr>
                <tr>
                    <td style="width:10%;">03</td>
                    <td style="width:50%;">Record the temperature of dispensing areaTemperature (NMT 27°C)  </td>
                    <td style="width:20%;"></td>
                    <td style="width:20%;"></td>
                </tr>
                <tr>
                    <td style="width:10%"><b>Sr. No.</b></td>
                    <td style="width:50%"><b>Checks Points: YES / NO</b></td>
                    <td style="width:10%"><b> YES / NO (Prod.)</b></td>
                    <td style="width:10%"><b> YES / NO (QA)</b></td>
                    <td style="width:10%"><b> YES / NO (Prod.)</b></td>
                    <td style="width:10%"><b> YES / NO (QA)</b></td>
                </tr>
                <tr>
                    <td style="width:10%">01</td>
                    <td style="width:50%">Ensure the materials of previous products removed from area.</td>
                    <td style="width:10%"></td>
                    <td style="width:10%"></td>
                    <td style="width:10%"></td>
                    <td style="width:10%"></td>
                </tr>
                <tr>
                    <td style="width:10%">02</td>
                    <td style="width:50%">Check the QC approve label of Packing material to be dispense.</td>
                    <td style="width:10%"></td>
                    <td style="width:10%"></td>
                    <td style="width:10%"></td>
                    <td style="width:10%"></td>
                </tr>
                <tr>
                    <td rowspan="2" style="width:10%;"></td>
                    <td style="width:50%;"><b>Checked By (Store)</b></td>
                    <td colspan="2" style="width:20%;"></td>
                    <td colspan="2"style="width:20%;"></td>
                </tr>
                <tr>
                    <td style="width:50%;"><b>Verified By (QA)</b></td>
                    <td colspan="2" style="width:20%;"></td>
                    <td colspan="2" style="width:20%;"></td>
                </tr>
                </table>
                <div></div>
                <span style="font-size:12px;">&nbsp;&nbsp;Note: Physically check the packaging materials code, A.R. No, Quantity as per the dispensing slips and attach the dispensed labels to BMR. </span>
                <div></div>
                <span style="font-size:12px;">&nbsp;&nbsp;Attached By Prod. Sign & Date _____________________. </span>
                <div></div>
                <div></div>
                <div></div>
                ';
                
        $html.='<h2>STAGE: 3.0 DISPENSING AND VERIFICATION OF PACKAGING MATERIALS </h2>
                <h3>&nbsp;&nbsp;3.1	Packaging Material Details:</h3>
                <div></div>
                <table border="1" cellpadding="5">
                <tr>
                    <td style="width:100%; text-align:center;"><b>Table Number : 3.1</b></td>
                </tr>
                <tr>
                    <td style="width:5%;"><b>Sr.No</b></td>
                    <td style="width:10%;"><b>Item Code</b></td>
                    <td style="width:10%;"><b>Packing Material</b></td>
                    <td style="width:5%;"><b>Spec</b></td>
                    <td style="width:5%;"><b>Unit</b></td>
                    <td style="width:10%;"><b>Standard  Batch Size-120 Lit.</b></td>
                    <td style="width:5%;"><b>OA %</b></td>
                    <td style="width:10%;"><b>Actual qty. per batch including % O.A</b></td>
                    <td style="width:10%;"><b>Qty. received from store</b></td>
                    <td style="width:5%;"><b>A.R. NO</b></td>
                    <td style="width:9%;"><b>Issued by(Store)</b></td>
                    <td style="width:8%;"><b>Checked by (Prod.)</b></td>
                    <td style="width:8%;"><b>Verify by (QA)</b></td>
                </tr>
                <tr>
                    <td style="width:5%;"><b></b></td>
                    <td style="width:10%;"><b></b></td>
                    <td style="width:10%;"><b></b></td>
                    <td style="width:5%;"><b></b></td>
                    <td style="width:5%;"><b></b></td>
                    <td style="width:10%;"><b></b></td>
                    <td style="width:5%;"><b></b></td>
                    <td style="width:10%;"><b></b></td>
                    <td style="width:10%;"><b></b></td>
                    <td style="width:5%;"><b></b></td>
                    <td style="width:9%;"><b></b></td>
                    <td style="width:8%;"><b></b></td>
                    <td style="width:8%;"><b></b></td>
                </tr>
                </table>
                ';
        
        $html.='<h2>3.2	Dispensing of Additional Packing Materials:</h2>
                <span style="font-size:12px;"> &nbsp;&nbsp;Dispense additional packaging materials required as per current version of SOP No: SPK/OP/04.  & enter the details in following table.</span><br>
                <div></div>
                <table border="1" cellpadding="5">
                <tr>
                    <td style="width:100%; text-align:center;"><b>Table Number : 3.2</b></td>
                </tr>
                <tr >
                    <td style="width:5%;"><b>Sr.No</b></td>
                    <td style="width:10%;"><b>Item Code</b></td>
                    <td style="width:10%;"><b>Packing Material Name</b></td>
                    <td style="width:10%;"><b>Spec</b></td>
                    <td style="width:5%;"><b>Unit.( Nos.)</b></td>
                    <td style="width:10%;"><b>Req. Additional qty.</b></td>
                    <td style="width:10%;"><b>Qty. Issued By store</b></td>
                    <td style="width:10%;"><b>A.R. NO</b></td>
                    <td style="width:10%;"><b>Issued by(Store)</b></td>
                    <td style="width:10%;"><b>Checked by (Prod.)</b></td>
                    <td style="width:10%;"><b>Verify by (QA)</b></td>
                </tr>
                <tr>
                    <td style="width:5%;"><b></b></td>
                    <td style="width:10%;"><b></b></td>
                    <td style="width:10%;"><b></b></td>
                    <td style="width:10%;"><b></b></td>
                    <td style="width:5%;"><b></b></td>
                    <td style="width:10%;"><b></b></td>
                    <td style="width:10%;"><b></b></td>
                    <td style="width:10%;"><b></b></td>
                    <td style="width:10%;"><b></b></td>
                    <td style="width:10%;"><b></b></td>
                    <td style="width:10%;"><b></b></td>
                </tr>
                </table>
                <div></div>
                ';
                
        $html.='<h2>STAGE: 4.0  PACK STYLE PHOTO VIEW:</h2>
                    <div></div>
                    <div></div>
                    <div></div>';
                    
        $html.='<h2>4.1 PACKING PROCEDURE:</h2>
                <table border="1" cellpadding="5" style="font-size:12px;">
                <tr>
                    <td style=" width:100%; text-align:center;";><b>Table Number : 4.1</b></td>
                </tr>
                 <tr>
                    <td style="width:10%;"><b>Sr.No</b></td>
                    <td style="width:90%;"><b>Packing profile                             ( Pack style : 20x10x10x2ml )</b></td>
                </tr>
                <tr>
                    <td style="width:10%;">1.</td>
                    <td style="width:90%;">Affix overprinted label on each filled ampoule.</td>
                </tr>
                <tr>
                    <td style="width:10%;">2.</td>
                    <td style="width:90%;">Pack such 10 proper labelled ampoules in a transparent PVC tray. </td>
                </tr>
                <tr>
                    <td style="width:10%;">3.</td>
                    <td style="width:90%;">Check the overprinting of carton specimen details. Pack one filled ampoule tray with one leaflet in a carton and close it properly.</td>
                </tr>
                <tr>
                    <td style="width:10%;">4.</td>
                    <td style="width:90%;">Pack 10 filled cartons in a shrink sleeve and Pass through the hot tunnel.</td>
                </tr>
                <tr>
                    <td style="width:10%;">5.</td>
                    <td style="width:90%;">Pack the 20 nos. of such shrink sleeves cartons in to a shipper and check the shipper weight.</td>
                </tr>
                <tr>
                    <td style="width:10%;">6.</td>
                    <td style="width:90%;">Affix one handle with care label and shipper label on each 5-ply shipper boxes.</td>
                </tr>
                <tr>
                    <td style="width:10%;">7.</td>
                    <td style="width:90%;">Close and seal the 5-ply shipper boxes with the help of BOPP BPL Logo Printed tape.</td>
                </tr>
                <tr>
                    <td style="width:10%;">8.</td>
                    <td style="width:90%;">After seal , strapping the 5-ply shipper boxes with the help of strapping machine.</td>
                </tr>
                <tr>
                    <td style="width:10%;">9.</td>
                    <td style="width:90%;">Numbers the each 5- ply shippers sequence wise. Record the shipper’s weight in BPR log sheet and on that same shipper label.</td>
                </tr>
                
                </table>
                ';
                
        $html.='<h2>STAGE: 5.0 Details of specimen printing and frequency check of Product.</h2>
                <div></div>
                <table border="1" cellpadding="5" style="font-size:12px;">
                <tr>
                    <td style="width:100%; text-align:center;"><b>Table Number : 5.0</b></td>
                </tr>
                <tr>
                    <td style="width:10%; text-align:center;"><b>Sr. No.</b></td>
                    <td style="width:70%; text-align:center;"><b>Checks Points</b></td>
                    <td style="width:20%; text-align:center;"><b>Checked by(Prod.)</b></td>
                </tr>
                <tr>
                    <td style="width:10%; ">1.</td>
                    <td style="width:70%; "><b>Label specimen details:</b><br>
                                            B. No.<br>
                                            Mfg. Date: <br>
                                            Exp. Date:<br>
                                            <b>Frequency:</b> Check and attach the specimen details of label, start of every roll and start & end of the day. In case of label roll A.R.No. Changes attach the specimen detail. The specimen should be sign duly (QA & production officer) during printing activity.
                    </td>
                    <td style="width:20%; "></td>
                </tr>
                <tr>
                    <td style="width:10%; ">2.</td>
                    <td style="width:70%; "><b>Barcode print on carton:</b><br>
                                            GTIN No.<br>
                                            Exp. Date :<br>
                                            B. No.<br>
                                            Serial No. _______________________ to _________________________.<br>
                                            Before start of packing activity generate the barcode label form PD department with reference of requisition slip of product. Print the barcode details on carton after specimen verify by QA.<br>
                    </td>
                    <td style="width:20%; "></td>
                </tr>
                <tr>
                    <td style="width:10%; ">3.</td>
                    <td style="width:70%; "><b>Carton specimen details:</b>
                                            B. No.<br>
                                            Mfg. Date:<br>
                                            Exp. Date:<br>
                                            <b>Frequency:</b> Check and attach the specimen details of carton, start & end of the over printing of the day. In case of change in A.R. No of cartons attach the specimen details of same. The specimen should be sign duly (QA& production officer) during printing activity.

                    </td>
                    <td style="width:20%; "></td>
                </tr>
                <tr>
                    <td style="width:10%; ">4.</td>
                    <td style="width:70%; "><b>Leaflet</b><br>
                                            <b>Frequency:</b> Check the text matter details of leaflet before start & end of the day. In case of change in A.R. No of leaflet attach the specimen details of same to verify any change in text matter, color, folding size etc. The specimen should be sign duly (QA& production officer) before attachment.

                    </td>
                    <td style="width:20%; "></td>
                </tr>
                <tr>
                    <td style="width:10%; ">4.</td>
                    <td style="width:70%; "><b>Shipper label specimen details:</b><br>
                                            Pack profile :<br>
                                            B. No.<br>
                                            Mfg. date:<br>
                                            Exp. Date:<br>
                                            <b>Frequency:</b> At the start of packing activity checks the printed specimen details of shipper label as per the BPR and duly sign on same shipper label both Production and QA officer. Attach the specimen signed shipper label start and end of the batch.

                    </td>
                    <td style="width:20%; "></td>
                </tr>
                </table>
                <div></div>';
                
        $html.='<h3>STAGE 6.0: LABEL OVERPRINTING AND LABELING OPERATION: </h3>
                <span style="font-size:12px;">&nbsp;&nbsp;6.1 Line clearance of Ampoule labeling area.</span><br>
                <span style="font-size:12px;">&nbsp;&nbsp;6.1.1 Take line clearance of labeling area as per SOP No.: BPL/GEN/QAI/04</span><br>
                <div></div>
                <table border="1" cellpadding="5" style="font-size:12px;">
                <tr>
                    <td style=" width:100%; text-align:center;"><b>Table Number : 6.1</b></td>
                </tr>
                <tr>
                    <td rowspan="2" style="width:10%; text-align:center;"><b>Sr. No.</b></td>
                    <td rowspan="2" style="width:30%; text-align:center;"><b>Checks Points</b></td>
                    <td style="width:20%;"><b>Date:</b></td>
                    <td style="width:20%;"><b>Time:</b></td>
                    <td style="width:20%;"><b>Date:</b></td>
                </tr>
                <tr>
                    <td style="width:20%;"><b>Date:</b></td>
                    <td style="width:20%;"><b>Time:</b></td>
                    <td style="width:20%;"><b>Date:</b></td>
                </tr>
                <tr>
                    <td style="width:10%;">01</td>
                    <td style="width:30%;">Previous Product Name</td>
                    <td style="width:20%;"></td>
                    <td style="width:20%;"></td>
                    <td style="width:20%;"></td>
                </tr>
                <tr>
                    <td style="width:10%;">02</td>
                    <td style="width:30%;">Previous Product Batch .No.</td>
                    <td style="width:20%;"></td>
                    <td style="width:20%;"></td>
                    <td style="width:20%;"></td>
                </tr>
                <tr>
                    <td style="width:10%;">03</td>
                    <td style="width:30%;">Labeling machine ID No.</td>
                    <td style="width:20%;"><b>ID No.___________</b></td>
                    <td style="width:20%;"><b>ID No.___________</b></td>
                    <td style="width:20%;"><b>ID No.___________</b></td>
                </tr>
                <tr>
                    <td style="width:10%;">04</td>
                    <td style="width:30%;">Record the temperature and relative humidity of labeling area.Temperature NMT 27°C.</td>
                    <td style="width:20%;">______°C</td>
                    <td style="width:20%;">______°C</td>
                    <td style="width:20%;">______°C</td>
                </tr>
                
                <tr>
                    <td style="width:10%"><b>Sr. No.</b></td>
                    <td style="width:30%"><b>Checks Points: YES / NO</b></td>
                    <td style="width:10%"><b> YES / NO (Prod.)</b></td>
                    <td style="width:10%"><b> YES / NO (QA)</b></td>
                    <td style="width:10%"><b> YES / NO (Prod.)</b></td>
                    <td style="width:10%"><b> YES / NO (QA)</b></td>
                    <td style="width:10%"><b> YES / NO (Prod.)</b></td>
                    <td style="width:10%"><b> YES / NO (QA)</b></td>
                </tr>
                <tr>
                    <td style="width:10%">01</td>
                    <td style="width:30%">Ensure the area should be absence of previous product materials.</td>
                    <td style="width:10%"></td>
                    <td style="width:10%"></td>
                    <td style="width:10%"></td>
                    <td style="width:10%"></td>
                    <td style="width:10%"></td>
                    <td style="width:10%"></td>
                </tr>
                <tr>
                    <td style="width:10%">02</td>
                    <td style="width:30%">Check the cleanness of labeling machine and surrounding area</td>
                    <td style="width:10%"></td>
                    <td style="width:10%"></td>
                    <td style="width:10%"></td>
                    <td style="width:10%"></td>
                    <td style="width:10%"></td>
                    <td style="width:10%"></td>
                </tr>
                <tr>
                    <td style="width:10%">03</td>
                    <td style="width:30%">Ensure the machine changeover is done as per the ampoule size</td>
                    <td style="width:10%"></td>
                    <td style="width:10%"></td>
                    <td style="width:10%"></td>
                    <td style="width:10%"></td>
                    <td style="width:10%"></td>
                    <td style="width:10%"></td>
                </tr>
                <tr>
                    <td style="width:10%">04</td>
                    <td style="width:30%">Check the cleanness of waste bin</td>
                    <td style="width:10%"></td>
                    <td style="width:10%"></td>
                    <td style="width:10%"></td>
                    <td style="width:10%"></td>
                    <td style="width:10%"></td>
                    <td style="width:10%"></td>
                </tr>
                <tr>
                    <td style="width:10%">05</td>
                    <td style="width:30%">Update the product details on status board.</td>
                    <td style="width:10%"></td>
                    <td style="width:10%"></td>
                    <td style="width:10%"></td>
                    <td style="width:10%"></td>
                    <td style="width:10%"></td>
                    <td style="width:10%"></td>
                </tr>
                <tr>
                    <td style="width:10%">06</td>
                    <td style="width:30%">Check the received labels quantity from store as per the requisition slip.</td>
                    <td style="width:10%"></td>
                    <td style="width:10%"></td>
                    <td style="width:10%"></td>
                    <td style="width:10%"></td>
                    <td style="width:10%"></td>
                    <td style="width:10%"></td>
                </tr>
                <tr>
                    <td rowspan="2" style="width:10%;"></td>
                    <td style="width:30%;"><b>Checked By (Prod.)</b></td>
                    <td colspan="2" style="width:20%;"></td>
                    <td colspan="2"style="width:20%;"></td>
                    <td colspan="2"style="width:20%;"></td>
                </tr>
                <tr>
                    <td style="width:30%;"><b>Verified By (QA)</b></td>
                    <td colspan="2" style="width:20%;"></td>
                    <td colspan="2" style="width:20%;"></td>
                    <td colspan="2" style="width:20%;"></td>
                </tr>
                </table><div></div>';
                
        $html.='<h3>6.2 Sticker label Overprinting & Labeling operation:</h3>
                <span style="font-size:12px;">&nbsp;&nbsp;6.2.1 Check the quantity & any damage of sticker label roll before labeling activity.</span><br>
                <span style="font-size:12px;">&nbsp;&nbsp;6.2.2 Check and attach the specimen details of label start of every roll and start & end of the day.</span><br>
                <span style="font-size:12px;">&nbsp;&nbsp;6.2.3 The specimen should be sign duly (QA & production officer) start of printing & labeling activity.</span><br>
                <span style="font-size:12px;">&nbsp;&nbsp;6.2.4During specimen signature verification check the artwork code of label & write the time along with date on same label.</span><br>
                <span style="font-size:12px;">&nbsp;&nbsp;6.2.5 After initial specimen signature sign by QA, start the labeling activity as per SOP No.: BPL/GEN/PAR/070 & 074.</span><br>
                <div></div>
                <h3>6.3	Attach the specimen of overprinted Labels: </h3>
                <span style="font-size:12px;">&nbsp;&nbsp;6.3.1 Labeling start Date &time: ____________________      End Date &Time: ___________________  </span><br>
                <div></div>
                <table border="1" cellpadding="5">
                <tr>
                    <td style="width:100%; text-align:center;"><b>Table Number : 6.2</b></td>
                </tr>
                <tr>
                    <td style="width:100%;"><div></div><div></div><div></div></td>
                </tr>
                </table>
                <div></div>
                <h3>6.3	Attach the specimen of overprinted Labels:</h3>
                <table border="1" cellpadding="5">
                <tr>
                    <td style="width:100%; text-align:center;"><b>Table Number : 6.3</b></td>
                </tr>
                <tr>
                    <td style="width:100%;"><div></div><div></div><div></div></td>
                </tr>
                </table>
                <div></div>
                ';
        $html.='<h3>6.4 In-process checks during label overprinting and labeling: </h3>
                <span style="font-size:12px;">&nbsp;&nbsp;Check 05-10 Ampoules randomly during in-process.<br>(Frequency- Hourly for Production persons and after every two hours for Q.A person’s)
                 </span><br>
                 <div></div>
                 <table border="1" cellpadding="5">
                 <tr>
                    <td style="width:100%; text-align:center;">Table Number : 6.4</td>
                 </tr>
                 <tr>
                    <td style="width:11%;"><b>Date</b></td>
                    <td style="width:11%;"><b>Time</b></td>
                    <td style="width:11%;"><b>Crack / Unclean ampoules</b></td>
                    <td style="width:11%;"><b>Ampoule identification(2ml Clear Glass Ampoule With White C/B Snep Off.)</b></td>
                    <td style="width:11%;"><b>Quality of labels.(Cross, Smudge, folding, Without label, Double labels)</b></td>
                    <td style="width:12%;"><b>Correctness of specimens (B. No., Mfg. Date, Exp. Date)</b></td>
                    <td style="width:11%;"><b>Legible of Overprinting Details and without print labels</b></td>
                    <td style="width:11%;"><b>Checked By(Prod.)</b></td>
                    <td style="width:11%;"><b>Checked By(QA)</b></td>
                 </tr>
                 <tr>
                    <td style="width:11%;"></td>
                    <td style="width:11%;"></td>
                    <td style="width:11%;"></td>
                    <td style="width:11%;"></td>
                    <td style="width:11%;"></td>
                    <td style="width:12%;"></td>
                    <td style="width:11%;"></td>
                    <td style="width:11%;"></td>
                    <td style="width:11%;"></td>
                 </tr>
                 <tr>
                    <td style="width:11%;"></td>
                    <td style="width:11%;"></td>
                    <td style="width:11%;"></td>
                    <td style="width:11%;"></td>
                    <td style="width:11%;"></td>
                    <td style="width:12%;"></td>
                    <td style="width:11%;"></td>
                    <td style="width:11%;"></td>
                    <td style="width:11%;"></td>
                 </tr>
                 </table>
                 
                 <span style="width:100%;">“√” mark means nil defects and “×” mark means defects identify during in-process checks and do the needful corrective action and put the remark with proper justification.</span>
                 <div></div>';
                 
        $html.= '<h3>6.5 Reconciliation of ampoules after labelling activity:</h3>
                 <table border="1" cellpadding="5">
                 <tr>
                    <td style="width:100%; text-align:center;">Table Number : 6.5</td>
                 </tr>
                 <tr>
                    <td rowspan="2" style="width:11%;"><b>Date</b></td>
                    <td rowspan="2" style="width:20%;"><b>Inspected good ampoules received from inspection</b></td>
                    <td style="width:20%;"><b>Labelling Started</b></td>
                    <td rowspan="2" style="width:10%;"><b>No.of ampoules labelled</b></td>
                    <td rowspan="2" style="width:20%;"><b>No.of ampoules rejected during labelling</b></td>
                    <td rowspan="2" style="width:9%;"><b>Done By(Operator)</b></td>
                    <td rowspan="2" style="width:10%;"><b>Checked By</b></td>
                 </tr>
                 <tr>
                    <td style="width:10%;"><b>From</b></td>
                    <td style="width:10%;"><b>To</b></td>
                 </tr>
                 <tr>
                    <td style="width:11%;"></td>
                    <td style="width:20%;"></td>
                    <td style="width:20%;"></td>
                    <td style="width:10%;"></td>
                    <td style="width:20%;"></td>
                    <td style="width:9%;"></td>
                    <td style="width:10%;"></td>
                 </tr>
                 <tr>
                    <td style="width:11%;">Total</td>
                    <td style="width:20%;"></td>
                    <td style="width:20%;"></td>
                    <td style="width:10%;"></td>
                    <td style="width:20%;"></td>
                    <td style="width:9%;"></td>
                    <td style="width:10%;"></td>
                 </tr>
                 </table>
                 <div></div>
                 <table>
                    <tr>
                        <td style="width:35%; border:none;">Rejection amps. destruction Done By(Prod.)</td>
                        <td style="width:10%; border:none;"></td>
                        <td style="width:15%; border:none;">Checked By.(Prod.): </td>
                        <td style="width:15%; border:none;"></td>
                        <td style="width:15%; border:none;">Verify By (QA):</td>
                        <td style="width:10%; border:none;"></td>
                    </tr>
                    <tr>
                        <td style="width:30%; border:none;">(Sign / Date)</td>
                        <td style="width:15%; border:none;"></td>
                        <td style="width:10%; border:none;">(Sign / Date)</td>
                        <td style="width:10%; border:none;"></td>
                        <td style="width:15%; border:none;">(Sign / Date)</td>
                        <td style="width:10%; border:none;"></td>
                    </tr>
                 </table>
                 <div></div>
                 ';
        $html.='<h3>6.6 Reconciliation of labels after labelling activity:</h3>
                <table border="1" cellpadding="5">
                    <tr>
                        <td style="width:100%; text-align:center;"><b>Table Number : 6.6</b></td>
                    </tr>
                    <tr>
                        <td style="width:10%;">Date</td>
                        <td style="width:10%;">Received labels quantity from store(A)</td>
                        <td style="width:10%;">Additional labels taken during labelling (B)</td>
                        <td style="width:10%;">Labels used in finished product (C)</td>
                        <td style="width:10%;">Labels used for specimen(D)</td>
                        <td style="width:10%;">Labels reject during Labelling (E)</td>
                        <td style="width:10%;">Excess (without print) labels return to store (F)</td>
                        <td style="width:10%;">Rejection percentage (G) =E ÷ (A + B) – F x 100NMT 3%</td>
                        <td style="width:10%;">Checked By</td>
                        <td style="width:10%;">Verified By (QA)</td>
                    </tr>
                    <tr>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                </table>
                <div></div>
                <table>
                    <tr>
                        <td style="width:100%; border:none;">In case of labels returned, Note the return slip Number:</td>
                    </tr>
                    <tr>
                        <td style="width:100%; border:none;">Return By (Prod.) Sign & Date: _____________</td>
                    </tr>
                </table>
                <div></div>
                ';
                
        $html.='<h3>STAGE: 7.0 CARTON OVERPRINTING OPERATION:</h3>
                &nbsp;&nbsp;<h3>7.1 Line clearance for carton Overprinting area. </h3>
                &nbsp;&nbsp;<h4>7.1.1.	Take line clearance of area as per SOP No.: BPL/GEN/QAI/04.</h4>
                <table border="1" cellpadding="5">
                    <tr>
                        <td style="width:100%; text-align:center;"><b></b></td>
                    </tr>
                    <tr>
                        <td style="width:5%;"><b>Sr. No</b></td>
                        <td style="width:35%;"><b>Table Number : 7.1</b></td>
                        <td style="width:20%;"><b>Date:__________</b><br><b>Time:___________</b></td>
                        <td style="width:20%;"><b>Date:__________</b><br><b>Time:___________</b></td>
                        <td style="width:20%;"><b>Date:__________</b><br><b>Time:___________</b></td>
                    </tr>
                    <tr>
                        <td style="width:5%;">01</td>
                        <td style="width:35%;">Previous Product Name</td>
                        <td style="width:20%;"></td>
                        <td style="width:20%;"></td>
                        <td style="width:20%;"></td>
                    </tr>
                    <tr>
                        <td style="width:5%;">02</td>
                        <td style="width:35%;">Previous Product Batch .No.</td>
                        <td style="width:20%;"></td>
                        <td style="width:20%;"></td>
                        <td style="width:20%;"></td>
                    </tr>
                    <tr>
                        <td style="width:5%;">03</td>
                        <td style="width:35%;">Carton over printing machine ID No.</td>
                        <td style="width:20%;"><b>ID No.___________</b></td>
                        <td style="width:20%;"><b>ID No.___________</b></td>
                        <td style="width:20%;"><b>ID No.___________</b></td>
                    </tr>
                    <tr>
                        <td style="width:5%;">04</td>
                        <td style="width:35%;">Record the temperature of  carton overprinting area Temperature. NMT 27°C  </td>
                        <td style="width:20%;">___°C</td>
                        <td style="width:20%;">___°C</td>
                        <td style="width:20%;">___°C</td>
                    </tr>
                </table>
                <table border="1" cellpadding="5">
                    <tr>
                        <td style="width:5%;"><b>Sr No</b></td>
                        <td style="width:35%;"><b>Checks Points: YES (√) / NO (X)</b></td>
                        <td style="width:10%;">(√) / (X)(Prod.)</td>
                        <td style="width:10%;">(√) / (X)(QA.)</td>
                        <td style="width:10%;">(√) / (X)(Prod.)</td>
                        <td style="width:10%;">(√) / (X)(QA.)</td>
                        <td style="width:10%;">(√) / (X)(Prod.)</td>
                        <td style="width:10%;">(√) / (X)(QA.)</td>
                    </tr>
                    <tr>
                        <td style="width:5%;">01</td>
                        <td style="width:35%;">Ensure the area should be absence of previous product materials</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td style="width:5%;">02</td>
                        <td style="width:35%;">Check the cleanness of carton overprinting machine and surrounding area.</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td style="width:5%;">01</td>
                        <td style="width:35%;">Ensure the machine setting is done as per the carton size.</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td style="width:5%;">01</td>
                        <td style="width:35%;">Check the cleanness of waste bin.</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td style="width:5%;">01</td>
                        <td style="width:35%;">Status board of area updated.</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td style="width:5%;">01</td>
                        <td style="width:35%;">Check the received cartons quantity from store as per the requisition slip.</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td style="width:5%;" rowspan="2"></td>
                        <td style="width:35%;">Checked By (Prod.)</td>
                        <td style="width:20%;"></td>
                        <td style="width:20%;"></td>
                        <td style="width:20%;"></td>
                    </tr>
                    <tr>
                        <td style="width:35%;">Verified By (QA)</td>
                        <td style="width:20%;"></td>
                        <td style="width:20%;"></td>
                        <td style="width:20%;"></td>
                    </tr>
                </table>
                <h3>7.2 Carton Overprinting operation:</h3>
                <table>
                    <tr>
                        <td style="width:100%; border:none;">7.2.1 Check the quantity & any damage of carton before printing activity.</td>
                    </tr>
                    <tr>
                        <td style="width:100%; border:none;">7.2.2 Check and attach the specimen details of carton start & end of the day and in case of A.R No. change.</td>
                    </tr>
                    <tr>
                        <td style="width:100%; border:none;">7.2.3 The specimen should be sign duly (QA & production officer) before printing activity.</td>
                    </tr>
                    <tr>
                        <td style="width:100%; border:none;">7.2.4 During specimen signature verification check the artwork code of carton & write the time along with date.</td>
                    </tr>
                    <tr>
                        <td style="width:100%; border:none;">7.2.5 After initial specimen signature sign from QA, start the printing activity as per carton overprinting SOP.</td>
                    </tr>
                </table>
                <div></div>';
                
        $html.='<h3>7.3 Attach the specimen of overprinted Cartons. : </h3>
                <table cellpadding="5">
                    <tr>
                        <td style="width:60%; border:none;">7.3.1 Carton overprinting start Date &time: </td>
                        <td style="width:40%; border:none;">End Date &Time: </td>
                    </tr>
                </table>
                <div></div>
                <table border="1" cellpadding="5">
                    <tr>
                        <td style="width:100%; text-align:center;font-weight:bold;">Table Number : 7.3</td>
                    </tr>
                    <tr>
                        <td style="width:100%; "></td>
                    </tr>
                </table>';
                
         $html.=' <h3>7.4 In-process checks during carton overprinting: </h3>
                <table cellpadding="5">
                    <tr>
                        <td style="100%; font-weight:bold;">7.4.1 Check 05-10 cartons randomly during in-process checks</td>
                    </tr>
                    <tr>
                        <td style="width:5%;"></td>
                        <td style="width:95%;">(Frequency- Hourly for Production persons and after every two hours for Q.A persons) </td> 
                    </tr>
                <table>
                <table border="1" cellpadding="5">
                    <tr>
                        <td style="width:100%; text-align:center; font-weight:bold;">Table Number : 7.4</td>
                    </tr>
                    <tr>
                        <td style="width:11%; font-weight:bold;">Date</td>
                        <td style="width:14%; font-weight:bold;">Time</td>
                        <td style="width:15%; font-weight:bold;">Correct Art work number of Carton</td>
                        <td style="width:15%; font-weight:bold;">Correctness of specimens(B. No., Mfg. Date, Exp. Date)</td>
                        <td style="width:15%; font-weight:bold;">Legible of Overprinting details and without print cartons.</td>
                        <td style="width:15%; font-weight:bold;">Checked By(Prod.)</td>
                        <td style="width:15%; font-weight:bold;">Checked By(QA)</td>
                    </tr>
                    <tr>
                        <td style="width:11%;"></td>
                        <td style="width:14%;"></td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                    </tr>
                </table>
                <span style="width:100%">“√” mark means nil defects and “×” mark means defects identify. If any discrepancy observes during in-process checks do the needful corrective action and put the remark with proper justification.</span>
                ';
        
        $html.='<h3>7.5 Reconciliation of Cartons after overprinting: </h3>
                <table border="1" cellpadding="5">
                    <tr>
                        <td style="width:100%; text-align:center; font-weight:bold;">Table Number : 7.5</td>
                    </tr>
                    <tr>
                        <td style="width:10%; font-weight:bold;">Date</td>
                        <td style="width:10%; font-weight:bold;">Received cartons quantity from store (A)</td>
                        <td style="width:10%; font-weight:bold;">Additional cartons taken during printing (B)</td>
                        <td style="width:10%; font-weight:bold;">Cartons use in batch (C)</td>
                        <td style="width:10%; font-weight:bold;">Cartons use for specimen (D)</td>
                        <td style="width:10%; font-weight:bold;">Cartons reject during overprinting (E)</td>
                        <td style="width:10%; font-weight:bold;">Excess (without print) cartons return to store (F)</td>
                        <td style="width:10%; font-weight:bold;">Rejection percentage (G) =E x100 ÷ (A + B) - F(NMT 2%)</td>
                        <td style="width:10%; font-weight:bold;">Done By </td>
                        <td style="width:10%; font-weight:bold;">Checked By</td>
                    </tr>
                    <tr>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                </table>';
        $html.='<table cellpadding="5">
                    <tr>
                        <td style="width:70%;">In case cartons returned, Note the return slip Number </td>
                        <td style="width:30%;">& Date:___________</td>
                    </tr>
                    <tr>
                        <td style="width:50%;">Rejection carton destruction Done By (Prod.) </td>
                        <td style="width:25%;">Checked By. (Prod.): </td>
                        <td style="width:25%;">Verify By (QA):_________</td>
                    </tr>
                    <tr>
                        <td style="width:50%;">(Sign / Date)</td>
                        <td style="width:25%;">(Sign / Date)</td>
                        <td style="width:25%;">(Sign / Date)</td>
                    </tr>
                </table>
                <br pagebreak="true"/>';
                
        $html.='<h3>STAGE: 8.0 PACKING OPERATION: </h3>
                <table cellpadding="5">
                    <tr>
                        <td style="width:5%;"></td>
                        <td style="width:95%; font-weight:bold;">8.1 Line clearance of packing area:</td>
                    </tr>
                    <tr>
                        <td style="width:5%;"></td>
                        <td style="width:95%;">8.1.1 Take line clearance of packing operation as per Ref. SOP Number: BPL/GEN/QAI/04.</td>
                    </tr>
                </table>
                <table border="1" cellpadding="5">
                    <tr>
                        <td style="width:100%; text-align:center;"><b>Table Number : 8.1</b></td>
                    </tr>
                    <tr>
                        <td style="width:5%;"><b>Sr. No</b></td>
                        <td style="width:35%;"><b>Table Number : 7.1</b></td>
                        <td style="width:20%;"><b>Date:__________</b><br><b>Time:___________</b></td>
                        <td style="width:20%;"><b>Date:__________</b><br><b>Time:___________</b></td>
                        <td style="width:20%;"><b>Date:__________</b><br><b>Time:___________</b></td>
                    </tr>
                    <tr>
                        <td style="width:5%;">01</td>
                        <td style="width:35%;">Previous Product Name</td>
                        <td style="width:20%;"></td>
                        <td style="width:20%;"></td>
                        <td style="width:20%;"></td>
                    </tr>
                    <tr>
                        <td style="width:5%;">02</td>
                        <td style="width:35%;">Previous Product Batch .No.</td>
                        <td style="width:20%;"></td>
                        <td style="width:20%;"></td>
                        <td style="width:20%;"></td>
                    </tr>
                    <tr>
                        <td style="width:5%;">03</td>
                        <td style="width:35%;">Packing Line ID No.</td>
                        <td style="width:20%;"><b>ID No.___________</b></td>
                        <td style="width:20%;"><b>ID No.___________</b></td>
                        <td style="width:20%;"><b>ID No.___________</b></td>
                    </tr>
                    <tr>
                        <td style="width:5%;">04</td>
                        <td style="width:35%;">Record the temperature of  packing area.Temperature NMT 27°C</td>
                        <td style="width:20%;">___°C</td>
                        <td style="width:20%;">___°C</td>
                        <td style="width:20%;">___°C</td>
                    </tr>
                </table>
                <table border="1" cellpadding="5">
                    <tr>
                        <td style="width:5%;"><b>Sr No</b></td>
                        <td style="width:35%;"><b>Checks Points: YES (√) / NO (X)</b></td>
                        <td style="width:10%;">(√) / (X)(Prod.)</td>
                        <td style="width:10%;">(√) / (X)(QA.)</td>
                        <td style="width:10%;">(√) / (X)(Prod.)</td>
                        <td style="width:10%;">(√) / (X)(QA.)</td>
                        <td style="width:10%;">(√) / (X)(Prod.)</td>
                        <td style="width:10%;">(√) / (X)(QA.)</td>
                    </tr>
                    <tr>
                        <td style="width:5%;">01</td>
                        <td style="width:35%;">Ensure the removal of previous product material from area. </td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td style="width:5%;">02</td>
                        <td style="width:35%;">Check the cleanliness of conveyor belt and surrounding area. </td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td style="width:5%;">03</td>
                        <td style="width:35%;">Check the cleanliness of waste bin</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td style="width:5%;">04</td>
                        <td style="width:35%;">Status board of area updated.</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td style="width:5%;" rowspan="2"></td>
                        <td style="width:35%;">Checked By (Prod.)</td>
                        <td style="width:20%;"></td>
                        <td style="width:20%;"></td>
                        <td style="width:20%;"></td>
                    </tr>
                    <tr>
                        <td style="width:35%;">Verified By (QA)</td>
                        <td style="width:20%;"></td>
                        <td style="width:20%;"></td>
                        <td style="width:20%;"></td>
                    </tr>
                </table>
                <table cellpadding="5">
                    <tr>
                        <td style="width:5%;"></td>
                        <td style="width:95%; font-weight:bold;">8.2   Packing process: </td>
                    </tr>
                    <tr>
                        <td style="width:5%;"></td>
                        <td style="width:95%;">8.2.1 Perform Packing operation as per SOP No. BPL/GEN/PAR/053</td>
                    </tr>
                    <tr>
                        <td style="width:5%;"></td>
                        <td style="width:95%;">8.2.2	Packing started date & time__________________ and end date & time _____________ </td>
                    </tr>
                    <tr>
                        <td style="width:5%;"></td>
                        <td style="width:95%;">8.2.3	Record the in-process check details as mention in table.</td>
                    </tr>
                    <tr>
                        <td style="width:5%;"></td>
                        <td style="width:95%;">8.2.4	Check the packing activity is carry on as per pack profile instruction in BPR.</td>
                    </tr>
                    <tr>
                        <td style="width:5%;"></td>
                        <td style="width:95%;">8.2.5   Record the person’s name involve in different packing activity in below Table Number. 8.2</td>
                    </tr>
                </table>';
                
        $html.='<table cellpadding="5">
                    <tr>
                        <td style="width:5%;"></td>
                        <td style="width:95%; font-weight:bold;">8.3 Name of person involve in packing activity:</td>
                    </tr>
                </table>
                <table border="1" cellpadding="5">
                    <tr>
                        <td style="width:100%; font-weight:bold; text-align:center;">Table Number : 8.2</td>
                    </tr>
                    <tr>
                        <td style="width:40%; font-weight:bold;">Date</td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                    </tr>
                    <tr>
                        <td style="width:40%; font-weight:bold;">Packing activity details</td>
                        <td style="width:60%;"></td>
                    </tr>
                    <tr>
                        <td style="width:40%;">Labelled ampoules checking</td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                    </tr>
                    <tr>
                        <td style="width:40%;">Ampoules fill in trays</td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                    </tr>
                    <tr>
                        <td style="width:40%;">Filled tray and leaflet pack in carton.</td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                    </tr>
                    <tr>
                        <td style="width:40%;">Packed cartons weighing on balance</td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                    </tr>
                    <tr>
                        <td style="width:40%;">Packed cartons fill in shrink sleeves</td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                    </tr>
                    <tr>
                        <td style="width:40%;">Filled shrink sleeves packs in shipper.</td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                    </tr>
                    <tr>
                        <td style="width:40%;">Shipper weighing, shipper label sticking and strapping of shippers</td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                    </tr>
                    <tr>
                        <td style="width:40%;">No. of Shippers packed.</td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                    </tr>
                    <tr>
                        <td style="width:40%;">Checked by<br>(Sign & Date)</td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                    </tr>
                </table>';
                
        $html.='<table cellpadding="5">
                    <tr>
                        <td style="width:5%;"></td>
                        <td style="width:95%; font-weight:bold;">8.4 Attach the specimen of leaflet:</td>
                    </tr>
                    <tr>
                        <td style="width:5%;"></td>
                        <td style="width:95%;">8.4.1 Attach the specimen details of leaflet start & end of the day and in case of A.R No. change.</td>
                    </tr>
                </table>
                <table border="1" cellpading="5">
                    <tr>
                        <td style="width:100%; text-align:center; font-weight:bold;">Table Number : 8.4</td>
                    </tr>
                    <tr>
                        <td style="width:100%;"></td>
                    </tr>
                </table>
                <span style="width:100%; font-weight:bold;">(Remark: start of packing activity check the art work number as per the BPR BOM page & duly sign on same leaflet both Production and QA officer along with date & time.)</span>
                <br><br>';
    
        $html.='<table cellpadding="5">
                    <tr>
                        <td style="width:5%;"></td>
                        <td style="width:95%; font-weight:bold;">8.5 Attach the specimen of shipper label:</td>
                    </tr>
                    <tr>
                        <td style="width:5%;"></td>
                        <td style="width:95%;">8.5.1 Attach the specimen signed shipper label start & end of the batch. (Duly sign on same label both Production and QA officer along with date & time.)</td>
                    </tr>
                </table>
                <table border="1" cellpadding="5">
                    <tr>
                        <td style="width:100%; text-align:center; font-weight:bold;">Table Number : 8.5</td>
                    </tr>
                    <tr>
                        <td style="width:100%;"></td>
                    </tr>
                </table>';
        
        $html.='<table cellpadding="5">
                    <tr>
                        <td style="width:5%;"></td>
                        <td style="width:95%; font-weight:bold;">8.6 (A) Lower weight and higher weight calculation of packed carton:</td>
                    </tr>
                    <tr>
                        <td style="width:5%;"></td>
                        <td style="width:95%; font-weight:bold;">05 Nos. individual weight of leaflet.</td>
                    </tr>
                    <tr>
                        <td style="width:10%;font-weight:bold;"></td>
                        <td style="width:40%;font-weight:bold;">1) _________ gm.</td>
                        <td style="width:50%;font-weight:bold;">Average wt. of ampoule (b) = ________ gm. (a) / 5</td>
                    </tr>
                    <tr>
                        <td style="width:10%;font-weight:bold;"></td>
                        <td style="width:40%;font-weight:bold;">2) _________ gm.</td>
                        <td style="width:50%;font-weight:bold;">Half of average wt. (c) = _______ gm. (b) / 2</td>
                    </tr>
                    <tr>
                        <td style="width:10%;font-weight:bold;"></td>
                        <td style="width:40%;font-weight:bold;">3) _________ gm.</td>
                        <td style="width:50%;font-weight:bold;">Lower limit = Ave. wt. pack carton - (c) = __________ gm.</td>
                    </tr>
                    <tr>
                        <td style="width:10%;font-weight:bold;"></td>
                        <td style="width:40%;font-weight:bold;">4) _________ gm. </td>
                        <td style="width:50%;font-weight:bold;">Higher limit = Ave. wt. pack carton + (c) = __________ gm.</td>
                    </tr>
                    <tr>
                        <td style="width:10%;font-weight:bold;"></td>
                        <td style="width:40%;font-weight:bold;">5) _________ gm. </td>
                        <td style="width:50%;font-weight:bold;"></td>
                    </tr>
                    <tr>
                        <td style="width:10%;"></td>
                        <td style="width:90%;font-weight:bold;">Total wt. of ampoule (a) = _______ gm.</td>
                    </tr>
                    <tr>
                        <td style="width:5%;"></td>
                        <td style="width:95%; font-weight:bold;">8.6(B)Lower weight and higher weight calculation of packed shipper:</td>
                    </tr>
                </table>';
                
        $html.='<table border="1" cellpadding="5">
                    <tr>
                        <td style="width:100%; text-align:center; font-weight:bold;">Table Number : 8.6</td>
                    </tr>
                    <tr>
                        <td style="width:10%;font-weight:bold;">Sr. No.</td>
                        <td style="width:15%;font-weight:bold;">Weight of  packed carton</td>
                        <td style="width:15%;font-weight:bold;">Weight By(sign / date)</td>
                        <td style="width:15%;font-weight:bold;">Weight of Empty Shipper</td>
                        <td style="width:15%;font-weight:bold;">Weight By(sign / date)</td>
                        <td style="width:15%;font-weight:bold;">Weight of Filled Shipper</td>
                        <td style="width:15%;font-weight:bold;">Weight By(sign / date)</td>
                    </tr>
                    <tr>
                        <td style="width:10%;">01</td>
                        <td style="width:15%;"></td>
                        <td rowspan="6" style="width:15%;"></td>
                        <td style="width:15%;"></td>
                        <td rowspan="6" style="width:15%;"></td>
                        <td style="width:15%;"></td>
                        <td rowspan="6" style="width:15%;"></td>
                    </tr>
                    <tr>
                        <td style="width:10%;">02</td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                    </tr>
                    <tr>
                        <td style="width:10%;">03</td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                    </tr>
                    <tr>
                        <td style="width:10%;">04</td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                    </tr>
                    <tr>
                        <td style="width:10%;">05</td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                    </tr>
                     <tr>
                        <td style="width:10%;">Total</td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                    </tr>
                    <tr>
                        <td style="width:40%;">Avg. wt. of pack carton________ gm.</td>
                        <td style="width:30%;">Avg. wt. of empty shipper_______Kg.</td>
                        <td style="width:30%;">Avg. wt.  filled shipper________Kg.</td>
                    </tr>
                    <tr>
                        <td style="width:100%;">Lower Limit of filled Shipper = Average wt. of filled shipper – average weight of one packed carton</td>
                    </tr>
                    <tr>
                        <td style="width:100%;">Upper Limit of filled shipper = Average wt. of filled shipper + average weight of one packed carton.</td>
                    </tr>
                </table>
                ';
                
        $html.='<table cellpadding="5">
                    <tr>
                        <td style="width:5%;"></td>
                        <td style="width:95%;"><b>8.7	In-process checks during packing:</b> Check 05-10 unit packs randomly during in-process checks.(Frequency- Hourly for Production persons and after every two hours for Q.A person’s.) </td>
                    </tr>
                </table>
                <table border="1" cellpadding="5">
                    <tr>
                        <td style="width:100%; text-align:center; font-weight:bold;">Table Number : 8.8</td>
                    </tr>
                    <tr>
                        <td rowspan="2" style="width:10%;">Defect Time</td>
                        <td style="width:30%;">Date</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td style="width:30%;">Time</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td rowspan="2" style="width:10%;">Product</td>
                        <td style="width:30%;">Identification (Amber Amp. white C/B snap off)</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td style="width:30%;">Seal/cracked of Ampoules</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td rowspan="3" style="width:10%;">Label</td>
                        <td style="width:30%;">Defective printing / text missing</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td style="width:30%;">Improper sticking / dirty/ folded label</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td style="width:30%;">Wrong / Missing Batch. No., Mfg. Dt. Exp. Dt. /  Correct Art work No.</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td style="width:10%;">Amps. Trays</td>
                        <td style="width:30%;"> Dirty / Damaged trays, Less Qty. of Ampoules in tray.</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td rowspan="2" style="width:10%;">Cartons</td>
                        <td style="width:30%;">Defective /Correct Art work No./without print/Printing smudge/weight of pack unit. </td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td style="width:30%;"> Text missing (Batch. No., Mfg. Dt. Exp. Dt.)If any additional details specify.</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td style="width:10%;">Leaflet</td>
                        <td style="width:30%;">Dirty / Moist / Torn /  Text missing / Improper folding / Correct Art work No.</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td style="width:10%;">Shrink sleeves</td>
                        <td style="width:30%;">Dirty /Torn /  Improper folding </td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td style="width:10%;">5 ply Shipper</td>
                        <td style="width:30%;">Dirty / Moist / Torn / Improper sealing</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td style="width:10%;">Shipper Label</td>
                        <td style="width:30%;">Legible printing (B. No., Mfg., Exp. Date,  If any additional details specify.) / Pack qty. / Address</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td rowspan="3" style="width:10%;"></td>
                        <td style="width:30%;">Signature of Packing officer</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td style="width:30%;">Signature of QA officer</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td style="width:30%;">Remarks</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                </table>
                <span style="width:100%;"><b>(Remark: In case of online labeling and packing strike out the product and label in-process rows)</b> “√” mark means nil defects and “×” mark means defects identify. If any discrepancy identify during in-process checks do the needful corrective action and put the remark with proper justification.</span><br>
                <br>';
                
        $html.='<table cellpadding="5">
                    <tr>
                        <td style="width:5%;"></td>
                        <td style="width:95%;"><b>8.9    In-process checks during packing:</b> check 05-10 unit packs randomly during in-process checks.
                         (Frequency- Hourly for Production persons and after every two hours for Q.A person’s.)  
                        </td>
                    </tr>
                </table>';
        $html.='<table border="1" cellpadding="5">
                    <tr>
                        <td style="width:100%; text-align:center; font-weight:bold;">Table Number : 8.9</td>
                    </tr>
                    <tr>
                        <td rowspan="2" style="width:10%;">Defect Time</td>
                        <td style="width:30%;">Date</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td style="width:30%;">Time</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td rowspan="2" style="width:10%;">Product</td>
                        <td style="width:30%;">Identification (Amber Amp. white C/B snap off)</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td style="width:30%;">Seal/cracked of Ampoules</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td rowspan="3" style="width:10%;">Label</td>
                        <td style="width:30%;">Defective printing / text missing</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td style="width:30%;">Improper sticking / dirty/ folded label</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td style="width:30%;">Wrong / Missing Batch. No., Mfg. Dt. Exp. Dt. /  Correct Art work No.</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td style="width:10%;">Amps. Trays</td>
                        <td style="width:30%;"> Dirty / Damaged trays, Less Qty. of Ampoules in tray.</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td rowspan="2" style="width:10%;">Cartons</td>
                        <td style="width:30%;">Defective /Correct Art work No./without print/Printing smudge/weight of pack unit. </td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td style="width:30%;"> Text missing (Batch. No., Mfg. Dt. Exp. Dt.)If any additional details specify.</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td style="width:10%;">Leaflet</td>
                        <td style="width:30%;">Dirty / Moist / Torn /  Text missing / Improper folding / Correct Art work No.</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td style="width:10%;">Shrink sleeves</td>
                        <td style="width:30%;">Dirty /Torn /  Improper folding </td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td style="width:10%;">5 ply Shipper</td>
                        <td style="width:30%;">Dirty / Moist / Torn / Improper sealing</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td style="width:10%;">Shipper Label</td>
                        <td style="width:30%;">Legible printing (B. No., Mfg., Exp. Date,  If any additional details specify.) / Pack qty. / Address</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td rowspan="3" style="width:10%;"></td>
                        <td style="width:30%;">Signature of Packing officer</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td style="width:30%;">Signature of QA officer</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td style="width:30%;">Remarks</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                </table>
                <span style="width:100%;"><b>(Remark: In case of online labeling and packing strike out the product and label in-process rows)</b> “√” mark means nil defects and “×” mark means defects identify. If any discrepancy identify during in-process checks do the needful corrective action and put the remark with proper justification.</span><br>
                <br pagebreak="true"/>';
                
        $html.='<h3>STAGE: 9.0 Reconciliation of Secondary packing materials.</h3>
                <table cellpadding="5">
                    <tr>
                        <td style="width:5%;"></td>
                        <td style="width:95%;">9.1	After completion of batch packing process calculate the quantity of used packing material as per sop no.  BPL/GEN/PAR/109.</td>
                    </tr>
                    <tr>
                        <td style="width:5%;"></td>
                        <td style="width:95%;">9.2	Count the rejection quantity & unused printed packing material and segregate. </td>
                    </tr>
                    <tr>
                        <td style="width:5%;"></td>
                        <td style="width:95%;">9.3	Note down the received quantity, used qty., return qty., rejected qty. in BPR.</td>
                    </tr>
                    <tr>
                        <td style="width:5%;"></td>
                        <td style="width:95%;">9.4	Discard the rejected & unused printed material by tear/cut under supervision of Packing and QA officer. </td>
                    </tr>
                </table>
                <div></div>';
            }
        }
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('MFR.pdf', 'I');
    }
    if ($_GET["type"] == "downloadMFR") {
        require '../tcpdf/tcpdf.php';
        $_GET['filename'] = 'Standard Formula'; $_GET['pdftype'] = 'onlyheader';  include('../pdfimp.php');
        $sql = "SELECT m.*, p.product_name, p.grade FROM mfr m LEFT JOIN product p ON m.product_code=p.product_code WHERE m.id='".$_GET["id"]."'";
        $result = $conn->query($sql);
        
        $html="";
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $steps = json_decode($row["steps"]);
                
                $html.='<h3 style="text-align:center;">BATCH MANUFACTURING RECORD</h3>
                        <table border="1" cellpadding="5" >';
              $html.='
                        <tr>
                          <td style="width:20%; text-align:center;">Product Code</td>
                          <td style="width:20%;text-align:center;">'.$row['product_code'].'</td>
                          <td style="width:20%;text-align:center;">Effective Batch No</td>
                          <td style="width:20%;text-align:center;"></td>
                          <td style="width:15%;text-align:center;">Version No</td>
                          <td style="width:5%;text-align:center;"></td>
                        </tr>
                        <tr>
                          <td style="width:20%;text-align:center;">Document No</td>
                          <td style="width:20%;text-align:center;"></td>
                          <td style="width:20%;text-align:center;">Effective Date</td>
                          <td style="width:20%;text-align:center;"></td>
                          <td  colspan="2" style="width=20%;text-align:center;">Page 1 OF 12</td>
                        </tr>
                        <tr>
                          <td style="width:20%;text-align:center;">Product Name</td>
                          <td style="width:40%;text-align:center;">'.$row['product_name'].'</td>
                          <td style="width:20%;text-align:center;">Shelf Life</td>
                          <td style="width:20%;text-align:center;">'.$row['shelf_life'].'</td>
                        </tr>
                        <tr>
                          <td style="width:20%;text-align:center;">Generic Name</td>
                          <td style="width:80%;text-align:center;">'.$row['generic_name'].'</td>
                        </tr>
                        <tr>
                          <td style="width:20%;text-align:center;">Label Claim</td>
                          <td style="width:80%;text-align:center;">'.$row['label_claim'].'<br><br><br><br></td>
                        </tr>
                        <tr>
                          <td style="width:20%;text-align:center;">Batch No.</td>
                          <td style="width:27%;text-align:center;"></td>
                          <td style="width:26%;text-align:center;">Batch Size</td>
                          <td style="width:27%;text-align:center;">'.$row['batch_size'].'</td>
                        </tr>
                        <tr>
                          <td style="width:20%;text-align:center;">MFG. Date</td>
                          <td style="width:27%;text-align:center;"></td>
                          <td style="width:26%;text-align:center;">EXP. Date</td>
                          <td style="width:27%;text-align:center;"></td>
                        </tr>
                        <tr>
                          <td style="width:20%;text-align:center;">STD. Batch Size<br>(In Units)</td>
                          <td style="width:27%;text-align:center;"></td>
                          <td style="width:26%;text-align:center;">STD. Batch Size<br>(In kg)</td>
                          <td style="width:27%;text-align:center;"></td>
                        </tr>
                        <tr>
                          <td style="width:20%;text-align:center;text-align:center;text-align:center;text-align:center;text-align:center;">MARKET</td>
                          <td style="width:27%;text-align:center;text-align:center;text-align:center;text-align:center;">'.$row['market'].'</td>
                          <td style="width:26%;text-align:center;text-align:center;text-align:center;">Mfg. Lic. No.</td>
                          <td style="width:14%;text-align:center;text-align:center;">'.$row['mfg_lic'].'</td>
                          <td style="width:13%;text-align:center;">Validity up to:<br></td>
                        </tr>
                        <tr>
                          <td style="width:20%;text-align:center;">Issued By QA<br> Sign & Date</td>
                          <td style="width:27%;text-align:center;"></td>
                          <td style="width:26%;text-align:center;">Received By Production <br>Sign & Date</td>
                          <td style="width:27%;text-align:center;"></td>
                        </tr>
                        <tr>
                          <td style="width:20%;text-align:center;">Total No.of BMR<br> Pages Issued</td>
                          <td style="width:27%;text-align:center;"></td>
                          <td style="width:26%;text-align:center;">No.of Autoclavable<br> Pages Issued</td>
                          <td style="width:27%;text-align:center;"></td>
                        </tr>
                        <tr>
                          <td style="width:20%;text-align:center;">MFG. Commenced On</td>
                          <td style="width:27%;text-align:center;"></td>
                          <td style="width:26%;text-align:center;">MFG. Completed On</td>
                          <td style="width:27%;text-align:center;"></td>
                        </tr>
                        <tr>
                          <td rowspan="2"style=text-align:center;"width:20%;">Yield Limit : (97.00 %<br> to 99.5%) & (87 % to<br> 96.99 % for Batch size below 5000 )</td>
                          <td rowspan="2"style=text-align:center;"width:27%;"></td>
                          <td style="width:26%;text-align:center;">Released Date</td>
                          <td rowspan="2"style=text-align:center;"width:27%;"></td>
                        </tr>
                        <tr>
                           <td style:"width:26%;text-align:center;">No. of Autoclavable Pages Retrieved :</td>
                        </tr>
                        <tr>
                           <td colspan="4"style:"width:100%;text-align:center;">BATCH MANUFACTURING RECORD REVIEW [AFTER COMPLETION OF MFG. ACTIVITY]</td>
                        </tr>
                        <tr>
                          <td style="width:20%;text-align:center;"></td>
                          <td style="width:27%;text-align:center;"></td>
                          <td style="width:26%;text-align:center;"></td>
                          <td style="width:27%;text-align:center;"></td>
                        </tr>
                        <tr>
                          <td style="width:20%;text-align:center;">Production Officer<br> Sign/Date</td>
                          <td style="width:27%;text-align:center;">Production Head<br>Sign/Date</td>
                          <td style="width:26%;text-align:center;">Reviewed By Q.A.<br>Sign/ Date</td>
                          <td style="width:27%;text-align:center;">Approved by - QA Head<br>Sign/Date</td>
                        </tr>
                        
                        
                       ';
                       $html.="</table><div></div>
                       <div></div>
                       <div></div>";
                        
                 $html.='<table border="1" cellpadding="5">
                         <tr>
                            <td style="width:10%;text-align:center;">Section</td>
                            <td style="width:80%;text-align:center;">Content</td>
                            <td style="width:10%;text-align:center;">Page No.</td>
                         </tr>
                         <tr>
                            <td style="width:10%;text-align:center;">1.</td>
                            <td style="width:80%;">General instruction </td>
                            <td style="width:10%;text-align:center;">03 to 03</td>
                         </tr>
                         <tr>
                            <td style="width:10%;text-align:center;">2.</td>
                            <td style="width:80%;">List of Equipment</td>
                            <td style="width:10%;">04 to 04</td>
                         </tr>
                         <tr>
                            <td style="width:10%;text-align:center;">3.</td>
                            <td style="width:80%;">Calculation for fill value</td>
                            <td style="width:10%;">05 to 06</td>
                         </tr>
                         <tr>
                            <td style="width:10%;text-align:center;">4.</td>
                            <td style="width:80%;">Dispensing of Raw material </td>
                            <td style="width:10%;text-align:center;">07 to 09</td>
                         </tr>
                         <tr>
                            <td style="width:10%;text-align:center;">5.</td>
                            <td style="width:80%;">Dispensing of  primary packing material</td>
                            <td style="width:10%;text-align:center;">10 to 11</td>
                         </tr>
                         <tr>
                            <td style="width:10%;text-align:center;">6.</td>
                            <td style="width:80%;">Revision History</td>
                            <td style="width:10%;text-align:center;">12</td>
                         </tr>
                         </table><div></div>
                                <div></div>';
                                
                $html.='<h3 style="text-align:center;">LIST OF EQUIPMENTS</h3>
                        <table border="1" cellpadding="5">
                        <tr>
                            <td style="width:10%">SR.NO.</td>
                            <td style="width:50%">MAJOR EQUIPMENT</td>
                            <td style="width:40%">EQUIPMENT NO.</td>
                        </tr>';
                         for ($i = 0; $i < count($steps); $i++) {
                                $step = $steps[$i];
                                if ($step->stage == "COATING-DISPENSING") {
                                    if ($step->isequipment == 'yes') {
                                        $equipments = $step->equipment;
                                        for ($j = 0; $j < count($equipments); $j++) {
                                            $equipment = $equipments[$j];
                                            $k = $j + 1;
                                            $html.='
                                                <tr>
                                                    <td style="width:10%">'.$k.'</td>
                                                    <td style="width:50%">'.$equipment->equipment.'</td>
                                                    <td style="width:40%"></td>
                                                </tr>';
                                        }
                                    }
                                }
                            }
                         
                        $html.='</table><div></div>';
                 
                 
                 $html.='<h3 style="text-align:center;">LINE CLEARANCE CHECKLIST SOP-QAD/008</h3>
                            <table border="1" cellpadding="5">
                            <tr>
                                <td style="width:10%; text-align:center;">Sr.No</td>
                                <td style="width:70%; text-align:center;">Check point</td>
                                <td style="width:10%; text-align:center;">Stores</td>
                                <td style="width:10%; text-align:center;">QA</td>
                            </tr>';
                            for ($i = 0; $i < count($steps); $i++) {
                                $step = $steps[$i];
                                if ($step->stage == "DISPENSING") {
                                    if ($step->isclearance == 'yes') {
                                        $clearances = $step->clearances;
                                        for ($j = 0; $j < count($clearances); $j++) {
                                            $clearance = $clearances[$j];
                                            $k = $j + 1;
                                            $html.='
                                                <tr>
                                                    <td style="width:10%; text-align:center;">'.$k.'.</td>
                                                    <td style="width:70%;">'.$clearance->checkpoint.'</td>
                                                    <td style="width:10%;"></td>
                                                    <td style="width:10%;"></td>
                                                </tr>';
                                        }
                                    }
                                }
                            }
                $html.='</table><div></div>';
                
                $html.='<h3 style="text-align:center;">BILL OF MATERIAL (MATERIAL FOR BULK)</h3>
                        <div>Manufacturing Date of Blend: ___________ Total Hold time of Blend:_____________ (Limit: NMT 30 Days)</div>
                        <div></div>
                        <table border="1" cellpadding="5">
                        <tr>
                            <td style="width:10%;">Material Code</td>
                            <td style="width:10%;">Vendor Name</td>
                            <td style="width:20%;">Item Name</td>
                            <td style="width:10%;">Standard Quantity<br>(For 10000) Vials</td>
                            <td style="width:10%;">Required<br>Qty.<br>(In Kg)</td>
                            <td style="width:10%;">Issued<br>Qty.<br>(In Kg)</td>
                            <td style="width:10%;">A.R. No.</td>
                            <td style="width:10%;">Mfg. Date</td>
                            <td style="width:10%;">Exp. Date</td>
                        </tr>
                        <tr>
                            <td style="width:10%;"></td>
                            <td style="width:10%;"></td>
                            <td style="width:20%;"></td>
                            <td style="width:10%;"></td>
                            <td style="width:10%;"></td>
                            <td style="width:10%;"></td>
                            <td style="width:10%;"></td>
                            <td style="width:10%;"></td>
                            <td style="width:10%;"></td>
                        </tr>';
                $html.='</table><div></div>
                                <div></div>
                                <div></div>';
                
                $html.='<h3 style="text-align:center;">WEIGHT OF STERILE BULK</h3>
                        <table border="1" cellpadding="5">
                            <tr>
                                <td rowspan="2" style="width:10%;text-align:center;">Sr.No.</td>
                                <td rowspan="2" style="width:10%;text-align:center;">Container No.</td>
                                <td style="width:80%;text-align:center;">Wt. Of Material</td>
                            </tr>
                            <tr>
                                <td style="width:25%;text-align:center;">Gross Wt.(in kg)</td>
                                <td style="width:25%;text-align:center;">Tare Wt.(in kg)</td>
                                <td style="width:30%;text-align:center;">Net Wt.(in kg)</td>
                            </tr>
                            <tr><td style="width:10%;"></td>
                                <td style="width:10%;"></td>
                                <td style="width:25%;"></td>
                                <td style="width:25%;"></td>
                                <td style="width:30%;"></td>
                            </tr>
                        </table>';
                }
        }
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('','I');
    }else if ($_GET["type"] == "downloadMFRRecord") {
        //$_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp.php");
        //$sql = "SELECT b.*, DATE(b.start_date) as start_date, p.product_name, p.grade, p.generic_name, p.dosage_form, p.shelf_life, p.label_claim ,u.instructions ,u.abbreviation,u.raw_materials ,u.packing_materials ,u.bmr_checklist FROM bmr b LEFT JOIN product p ON b.product_code=p.product_code LEFT JOIN unitformula u ON b.mfr_no = u.mfr_no WHERE b.status='COMPLETED' AND b.id='".$_GET["id"]."'";
        $sql = "SELECT b.*, DATE(b.start_date) as start_date, p.product_name, p.grade, p.generic_name, p.dosage_form, p.shelf_life, p.label_claim FROM bmr b LEFT JOIN product p ON b.product_code=p.product_code WHERE b.status='COMPLETED' AND b.id='".$_GET["id"]."'";
        if($result = $conn->query($sql)){
            while($row = $result->fetch_assoc()){
            $_GET['product']=$row['product_name'];
            $_GET['batch_no']=$row['batch_no'];
            $_GET['batch_size']=$row['batch_size'];
            $_GET['bmr_no']=$row['bmr_no'];
            class MYPDF extends TCPDF {
                public function Header() {
                    if($this->page==1){
                        $table.='
                        <style>td { border:solid 1px BCBBBA;}</style>
                        <table cellpadding="3">
                            <tr>
                                 <td style="width:20%;">';
                                    $this->Image('@'.file_get_contents('https://'.$_SERVER['SERVER_NAME'].'/gmptotal/upload/User/logo.png'),16,12,16);
                                $table.='
                                </td>
                                <td style="width:80%;text-align:center;font-weight:bold;">
                                    <span style="font-family:times;font-size:17px;">Cyclone Pharmaceuticals Pvt. Ltd.</span><br>
                                    <span style="font-size:9px;">Location:104 Garnet Bay, Near Shereton Hotel,Behind Chandhere Complex,Viman Nagar,Pune 411014</span>
                                </td>
                            </tr>
                        </table>';
                    $this->SetY('10'); $this->writeHTML($table, true, false, false, false, '');
                    $this->SetY(29); $this->SetFont('helvetica', 'B', 14); $this->Cell(0, 0,'', 0, false, 'L', 0, '', 0, false, 'M', 'M');
                    }else{
                        $table.='
                        <style>td { border:solid 1px BCBBBA;}</style>
                        <table cellpadding="3">
                            <tr>
                                 <td style="width:20%;">';
                                    $this->Image('@'.file_get_contents('https://'.$_SERVER['SERVER_NAME'].'/gmptotal/upload/User/logo.png'),16,12,16);
                                $table.='
                                </td>
                                <td style="width:80%;text-align:center;font-weight:bold;">
                                    <span style="font-family:times;font-size:17px;">Cyclone Pharmaceuticals Pvt. Ltd.</span><br>
                                    <span style="font-size:9px;">Location:104 Garnet Bay, Near Shereton Hotel,Behind Chandhere Complex,Viman Nagar,Pune 411014</span>
                                </td>
                            </tr>
                            <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                                <td style="width:100%;text-align:center;font-weight:bold;">BATCH MANUFACTURING RECORD</td>
                            </tr>
                            <tr>
                                <td style="width:100%;">
                                    <table>
                                        <tr>
                                            <td style="width:17%;font-weight:bold;border:none;">DEPARTMENT</td>
                                            <td style="width:3%;border:none;">:</td>
                                            <td style="width:30%;border:none;"></td>
                                            <td style="width:22%;font-weight:bold;border:none;">COMMENCE DATE  </td>
                                            <td style="width:3%;border:none;">:</td>
                                            <td style="width:25%;border:none;"></td>
                                        </tr>
                                        <tr>
                                            <td style="width:17%;font-weight:bold;border:none;">PROD.NAME</td>
                                            <td style="width:3%;border:none;">:</td>
                                            <td style="width:30%;border:none;">'.$_GET['product'].'</td>
                                            <td style="width:22%;font-weight:bold;border:none;">MFG.DATE</td>
                                            <td style="width:3%;border:none;">:</td>
                                            <td style="width:25%;border:none;"></td>
                                        </tr>
                                        <tr>
                                            <td style="width:17%;font-weight:bold;border:none;">MAST CARD</td>
                                            <td style="width:3%;border:none;">:</td>
                                            <td style="width:30%;border:none;"></td>
                                            <td style="width:22%;font-weight:bold;border:none;">EXP.DATE</td>
                                            <td style="width:3%;border:none;">:</td>
                                            <td style="width:25%;border:none;"></td>
                                        </tr>
                                        <tr>
                                            <td style="width:17%;font-weight:bold;border:none;">BATCH NO</td>
                                            <td style="width:3%;border:none;">:</td>
                                            <td style="width:30%;border:none;">'.$_GET['batch_no'].'</td>
                                            <td style="width:22%;font-weight:bold;border:none;">BATCH SIZE</td>
                                            <td style="width:3%;border:none;">:</td>
                                            <td style="width:25%;border:none;">'.$_GET['batch_size'].'Nos</td>
                                        </tr>
                                        <tr>
                                            <td style="width:17%;font-weight:bold;border:none;">SLIP NO</td>
                                            <td style="width:3%;border:none;">:</td>
                                            <td style="width:30%;border:none;"></td>
                                            <td style="width:22%;font-weight:bold;border:none;">PAGE NO</td>
                                            <td style="width:3%;border:none;">:</td>
                                            <td style="width:25%;border:none;">'.$this->getAliasNumPage().' of '.$this->getAliasNbPages().'</td>
                                        </tr>
                                        <tr>
                                            <td style="width:17%;font-weight:bold;border:none;">BMR NO</td>
                                            <td style="width:3%;border:none;">:</td>
                                            <td style="width:80%;border:none;">'.$_GET['bmr_no'].'</td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>';
                        $this->SetY('10'); $this->writeHTML($table, true, false, false, false, '');
                        $this->SetY(29); $this->SetFont('helvetica', 'B', 14); $this->Cell(0, 0,'', 0, false, 'L', 0, '', 0, false, 'M', 'M');
                        //$this->SetY(49); $this->SetFont('helvetica', '', 10); $this->Cell(0, 0, ''.$this->getAliasNumPage().' of '.$this->getAliasNbPages(), 0, false, 'R', 0, '', 0, false, 'T', 'M');
                    }
                }
                
                public function Footer() {
                    if($this->page==1){
                         $table='
                        <style>td { border:solid 1px BCBBBA;}</style>
                        <table cellpadding="5" border="1" >
                            <tr style="text-align:center;background-color:#DDDAD9;">
                                <td style="width:25%"></td>
                                <td style="width:25%">Checked By</td>
                                <td style="width:25%">Verified By</td>
                                <td style="width:25%">Approved By</td>
                            </tr>
                            <tr>
                                <td style="width:25%">Designation</td>
                                <td style="width:25%">Dept. In-charge</td>
                                <td style="width:25%">Production In-charge</td>
                                <td style="width:25%">QA In-charge</td>
                            </tr>
                            <tr>
                                <td style="width:25%">Sign</td>
                                <td style="width:25%"></td>
                                <td style="width:25%"></td>
                                <td style="width:25%"></td>
                            </tr>
                            <tr>
                                <td style="width:25%">Date</td>
                                <td style="width:25%"></td>
                                <td style="width:25%"></td>
                                <td style="width:25%"></td>
                            </tr>
                           
                        </table>';
                        $this->SetY(-50);
                        $this->SetFont('Times', '', 10);
                        $this->writeHTML($table, true, false, false, false, '');
                        $this->SetY(-10); $this->SetFont('helvetica', '', 10); $this->Cell(0, 0, 'Page '.$this->getAliasNumPage().' of '.$this->getAliasNbPages(), 0, false, 'L', 0, '', 0, false, 'T', 'M');
                        $this->SetY(-10); $this->SetFont('helvetica', '', 10); $this->Cell(0, 0, $_GET['sop'], 0, false, 'R', 0, '', 0, false, 'M', 'M');
                    }else{
                        
                    }
                }
            }
            $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING);
            $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
            $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
            $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
            if(page==1){
                $pdf->SetMargins(PDF_MARGIN_LEFT,30, PDF_MARGIN_RIGHT);
            }else{
                $pdf->SetMargins(PDF_MARGIN_LEFT,65, PDF_MARGIN_RIGHT);
            }
            $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
            $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
            $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
            $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
            if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
                require_once(dirname(__FILE__).'/lang/eng.php');
                $pdf->setLanguageArray($l);
            }
            $pdf->SetFont('times', '', 10);
            $pdf->AddPage();
            $html= "";
            $html.='
            <table border="1" cellpadding="3" style="font-size:12px;">
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:100%;text-align:center;">Batch Manufacture Record</td>
                </tr>
                <tr>
                    <td style="width:22%;font-weight:bold;">Product Name</td>
                    <td style="width:3%;font-weight:bold;">:</td>
                    <td style="width:75%;">'.$row['product_name'].'</td>
                </tr>
                <tr>
                    <td style="width:22%;font-weight:bold;">Generic Name</td>
                    <td style="width:3%;font-weight:bold;">:</td>
                    <td style="width:75%;">'.$row['generic_name'].'</td>
                </tr>
                <tr>
                    <td style="width:22%;font-weight:bold;">Label Name</td>
                    <td style="width:3%;font-weight:bold;">:</td>
                    <td style="width:75%;">'.$row['label_claim'].'</td>
                </tr>
                <tr>
                    <td style="width:22%;font-weight:bold;">Manufactured for </td>
                    <td style="width:3%;font-weight:bold;">:</td>
                    <td style="width:25%;"></td>
                    <td style="width:22%;font-weight:bold;">MFR No</td>
                    <td style="width:3%;font-weight:bold;">:</td>
                    <td style="width:25%;">'.$row['mfr_no'].'</td>
                </tr>
                <tr>
                    <td style="width:22%;font-weight:bold;">Shelf Life </td>
                    <td style="width:3%;font-weight:bold;">:</td>
                    <td style="width:25%;">'.$row['shelf_life'].'</td>
                    <td style="width:22%;font-weight:bold;">Mfg.Lic.No</td>
                    <td style="width:3%;font-weight:bold;">:</td>
                    <td style="width:25%;"></td>
                </tr>
                <tr>
                    <td style="width:22%;font-weight:bold;">Specification </td>
                    <td style="width:3%;font-weight:bold;">:</td>
                    <td style="width:25%;"></td>
                    <td style="width:22%;font-weight:bold;">Packing size</td>
                    <td style="width:3%;font-weight:bold;">:</td>
                    <td style="width:25%;"></td>
                </tr>
                <tr>
                    <td style="width:22%;font-weight:bold;">Batch No</td>
                    <td style="width:3%;font-weight:bold;">:</td>
                    <td style="width:25%;">'.$row['batch_no'].'</td>
                    <td style="width:22%;font-weight:bold;">Batch Size</td>
                    <td style="width:3%;font-weight:bold;">:</td>
                    <td style="width:25%;">'.$row['batch_size'].'Nos</td>
                </tr>
                <tr>
                    <td style="width:22%;font-weight:bold;">Mfg.Date</td>
                    <td style="width:3%;font-weight:bold;">:</td>
                    <td style="width:25%;"></td>
                    <td style="width:22%;font-weight:bold;">Exp.Date</td>
                    <td style="width:3%;font-weight:bold;">:</td>
                    <td style="width:25%;"></td>
                </tr>
                <tr>
                    <td style="width:22%;font-weight:bold;">Batch Commenced on </td>
                    <td style="width:3%;font-weight:bold;">:</td>
                    <td style="width:25%;"></td>
                    <td style="width:22%;font-weight:bold;">Batch Manufacturing Completed on </td>
                    <td style="width:3%;font-weight:bold;">:</td>
                    <td style="width:25%;">'.date('d-m-Y',strtotime($row['complete_date'])).'</td>
                </tr>
            </table>
            <div></div>
            <table cellpadding="3" border="1">
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:22%;font-weight:bold;text-align:center;">Stage</td>
                    <td style="width:38%;font-weight:bold;text-align:center;">Stage Date</td>
                    <td style="width:40%;font-weight:bold;text-align:center;">Complated Date</td>
                </tr>';
                $sql1 = "SELECT * FROM bmr_stages WHERE bmr_no='".$row["bmr_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                    $html.='
                    <tr>
                        <td style="width:22%;font-weight:bold;">'.$row1['stage'].'</td>
                        <td style="width:38%;">'.date('d-m-Y',strtotime($row1['expected_start_date'])).'</td>
                        <td style="width:40%;">'.date('d-m-Y',strtotime($row1['expected_complete_date'])).'</td>
                    </tr>';
                    }
                }
                $html.='
            </table>
            <div></div>
            <table border="1" cellpadding="3" style="font-size:12px;">
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:100%;text-align:center;">Batch Manufacturing Record Reviewed by</td>
                </tr>
                <tr>
                    <td style="width:22%;font-weight:bold;">Production Incharge Sign</td>
                    <td style="width:3%;font-weight:bold;">:</td>
                    <td style="width:25%;">'.$row['start_by'].'</td>
                    <td style="width:22%;font-weight:bold;">Date</td>
                    <td style="width:3%;font-weight:bold;">:</td>
                    <td style="width:25%;">'.date('d-m-Y',strtotime($row['start_date'])).'</td>
                </tr>
                <tr>
                    <td style="width:22%;font-weight:bold;">Q.A. Department Sign </td>
                    <td style="width:3%;font-weight:bold;">:</td>
                    <td style="width:25%;">'.$row['entry_by'].'</td>
                    <td style="width:22%;font-weight:bold;">Date</td>
                    <td style="width:3%;font-weight:bold;">:</td>
                    <td style="width:25%;">'.date('d-m-Y',strtotime($row['entry_date'])).'</td>
                </tr>
            </table>
            <div></div>
            <table border="1" cellpadding="3" style="font-size:12px;">
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:100%;text-align:center;">Batch Released for Packing</td>
                </tr>
                <tr>
                    <td style="width:22%;font-weight:bold;">Incharge Sign</td>
                    <td style="width:3%;font-weight:bold;">:</td>
                    <td style="width:25%;"></td>
                    <td style="width:22%;font-weight:bold;">Date</td>
                    <td style="width:3%;font-weight:bold;">:</td>
                    <td style="width:25%;"></td>
                </tr>
            </table>
            <br pagebreak="true"/>';
                    
            $html.='
            <table cellpadding="3" border="1">
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:100%;font-weight:bold;text-align:center;">ABBREVIATIONS</td>
                </tr>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:10%;">SR.NO</td>
                    <td style="width:45%;">ABBREVIATIONS</td>
                    <td style="width:45%;">NAME</td>
                </tr>';
                $sql2="SELECT * FROM unitformula WHERE mfr_no='".$row["mfr_no"]."'";
                $result2 = $conn->query($sql2);
                $j=1;
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                    $row2["abbreviation"] = json_decode($row2["abbreviation"]);
                    $abbreviations=$row2["abbreviation"];
                        for($i=0;$i< count($abbreviations);$i++){
                            $abbreviation=$abbreviations[$i];
                            $html.='
                            <tr>
                                <td style="width:10%;">'.$j++.'</td>
                                <td style="width:45%;">'.$abbreviation->short_form.'</td>
                                <td style="width:45%;">'.$abbreviation->full_form.'</td>
                            </tr>';
                        }
                    }
                }
            $html.='
            </table>
            <br pagebreak="true"/>';
                        
            $html.='<table cellpadding="3" border="1">
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:100%;text-align:center;font-weight:bold;">INDEX AND CHECK LIST FOR FINAL REVIEW OF B.M.R.</td>
                </tr>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:5%;">SR.No</td>
                    <td style="width:60%;"></td>
                    <td style="width:35%;">Status</td>
                </tr>';
                $sql2="SELECT * FROM unitformula WHERE mfr_no='".$row["mfr_no"]."'";
                $result2 = $conn->query($sql2);
                $j=1;
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                    $row2["bmr_checklist"] = json_decode($row2["bmr_checklist"]);
                    $checklist=$row2["bmr_checklist"];
                        for($i=0;$i< count($checklist);$i++){
                            $checklists=$checklist[$i];
                            $html.='
                            <tr>
                                <td style="width:5%;">'.$j++.'</td>
                                <td style="width:60%;">'.$checklists->bmr_check.'</td>
                                <td style="width:35%;"></td>
                            </tr>';
                        }
                    }
                }
            $html.='
            </table>
            <br pagebreak="true"/>';
            $html.='<table cellpadding="3" border="1">
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:100%;text-align:center;font-weight:bold;">DESCRIPTION OF THE PRODUCT</td>
                </tr>
                <tr>
                   <td style="width:25%;font-weight:bold;">Name of the Product</td>
                   <td style="width:75%;">'.$row['product_name'].'</td>
                </tr>
                <tr>
                   <td style="width:25%;font-weight:bold;">Generic Name</td>
                   <td style="width:75%;">'.$row['generic_name'].'</td>
                </tr>
                <tr>
                   <td style="width:25%;font-weight:bold;">Category</td>
                   <td style="width:75%;"></td>
                </tr>
                <tr>
                   <td style="width:25%;font-weight:bold;">Dosage Form</td>
                   <td style="width:75%;">'.$row['dosage_form'].'</td>
                </tr>
                <tr>
                   <td style="width:25%;font-weight:bold;">Description</td>
                   <td style="width:75%;"></td>
                </tr>
                <tr>
                   <td style="width:25%;font-weight:bold;">Specification</td>
                   <td style="width:75%;"></td>
                </tr>
                <tr>
                   <td style="width:25%;font-weight:bold;">Packing Size</td>
                   <td style="width:75%;"></td>
                </tr>
                <tr>
                   <td style="width:25%;font-weight:bold;">Label Claim</td>
                   <td style="width:75%;">'.$row['label_claim'].'</td>
                </tr>
            </table>
            <br pagebreak="true"/>';
                            
            $html.='<table cellpadding="3" border="1">
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:100%;text-align:center;font-weight:bold;">SPECIMEN SIGNATURE DETAIL</td>
                </tr>
                <tr>
                    <td style="width:100%;text-align:center;">Note : employee involved in production activity should specimen their sign in following table.</td>
                </tr>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:5%;">Sr.No</td>
                    <td style="width:20%;">Date</td>
                    <td style="width:30%;">Name Of Employee</td>
                    <td style="width:25%;">Department</td>
                    <td style="width:10%;">Initial Sign</td>
                    <td style="width:10%;">Full Sign</td>
                </tr>
                <tr>
                    <td style="width:5%;"></td>
                    <td style="width:20%;"></td>
                    <td style="width:30%;"></td>
                    <td style="width:25%;"></td>
                    <td style="width:10%;"></td>
                    <td style="width:10%;"></td>
                </tr>';
            $html.='   
            </table>
            <br pagebreak="true"/>';
                     
            $html.='<table cellpadding="3" border="1">
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:100%;text-align:center;font-weight:bold;">PRODUCT FORMULA</td>
                </tr>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:22%;">Material Description</td>
                    <td style="width:13%;">Qty. Reqd. Per Tablet</td>
                    <td style="width:13%;">Equivalent Qty</td>
                    <td style="width:13%;">Required Qty</td>
                    <td style="width:13%;">Overages %</td>
                    <td style="width:13%;">Total Qty Required</td>
                    <td style="width:13%;">T.R. No</td>
                </tr>';
                $sql2="SELECT * FROM unitformula WHERE mfr_no='".$row["mfr_no"]."'";
                $result2 = $conn->query($sql2);
                $j=1;
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                    $row2['raw_materials'] = json_decode($row2['raw_materials']);
                    $materials= $row2['raw_materials'];
                        for($i=0;$i< count($materials);$i++){
                            $material=$materials[$i];
                            $html.='
                            <tr>
                                <td style="width:100%;text-align:center;" font-weight:bold;></td>
                            </tr>
                            <tr>
                                <td style="width:22%;">'.$material->material_code.'</td>
                                <td style="width:13%;"></td>
                                <td style="width:13%;"></td>
                                <td style="width:13%;">'.$material->qty.''.$material->unit.'</td>
                                <td style="width:13%;">'.$material->overages.'</td>
                                <td style="width:13%;"></td>
                                <td style="width:13%;"></td> 
                            </tr>';
                        }
                    }
                }
                $html.='
                <tr>
                    <td style="width:100%;font-weight:bold;">Check the weight of all raw materials as per the formulation sheet. Check the T.R.No of all the materials per the issue slip. Active material assay calculation must be done with the potency given by quality control department to make the active material potency 100% for vitamins. This Calculationcan not apply. Depending on the sensitivity & Stability of Material, overages must be added.</td>
                </tr>
             </table>
             <br pagebreak="true"/>';
            $html.='<table cellpadding="3" border="1">
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:100%;text-align:center;font-weight:bold;">PRE-PRODUCTION REVIEW </td>
                </tr>
                <tr>
                    <td style="width:100%;font-weight:bold;">The complete Pre-Production Batch Record has been reviewed for completeness and accuracy.</td>
                </tr>
                <tr>
                    <td style="width:25%;"></td>
                    <td style="width:25%;font-weight:bold;">Name</td>
                    <td style="width:25%;font-weight:bold;">Signature</td>
                    <td style="width:25%;font-weight:bold;">Date</td>
                </tr>
                <tr>
                    <td>Production</td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <td>Quality Assurance</td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <td style="width:100%;border:none;">The batch will be manufactured in	lot/s. Details of each lot are as under. </td>
                </tr>
                <tr>
                    <td style="width:100%;">
                        <table>
                            <tr>
                                <td style="width:100%;">Lot: '.$row['lots'].'  </td>
                            </tr>
                            <tr>
                                <td style="width:100%;">Lot Size: '.$row['batch_size'].' </td>
                            </tr>
                            <tr>
                                <td style="width:100%;">Extra Pages Issued for</td>
                            </tr>
                            <tr>
                                <td style="width:100%;">
                                    <ul>
                                        <li>Dispensing:</li>
                                        <li>Granulation:</li>
                                        <li>Coating:</li>
                                    </ul>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
            <br pagebreak="true"/>';
            $html.='<table cellpadding="3" border="1" >
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:100%;font-weight:bold;text-align:center;">GENERAL PROCEDURAL INSTRUCTIONS</td>
                </tr>
                <tr>
                    <td style="width:100%;"></td>
                </tr>
            </table>
            <br pagebreak="true"/>';
                            
            $html.='<table cellpadding="3" border="1" >
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:100%;font-weight:bold;text-align:center;">RM DISPENSING</td>
                </tr>
                <tr>
                    <td style="width:100%;font-weight:bold;">INSTRUCTIONS:</td>
                </tr>
                <tr>
                    <td style="width:100%;font-weight:bold;">
                        <ul>
                            <li>Use nose mask and hand gloves during operation</li>
                            <li>Check for line clearance as per SOP No.	, cleaning & sanitization as per SOP No. 	</li>
                            <li>Check for quality control approved label and necessary details like Material name, item code, Vendors name, B.No., T.R. No., Retest Date etc on Raw material before dispensing.</li>
                            <li>Check & transfer the Primary raw material required for the Batch to the dispensing area dispense the material under dispensing booth</li>
                            <li>Make necessary entries in the BMR.</li>
                            <li>Attach duly filled dispensed material label to the dispensed raw material.</li>
                        </ul>
                    </td>
                </tr>
                <tr>
                    <td style="width:100%;font-weight:bold;">LINE CLEARANCE:</td>
                </tr>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:35%;">Description</td>
                    <td style="width:25%;">Observation</td>
                    <td style="width:20%;">Chkd By (Prod) /Date</td>
                    <td style="width:20%;">Ver. By(QA) /Date</td>
                </tr>
                <tr>
                    <td style="width:35%;"></td>
                    <td style="width:25%;"></td>
                    <td style="width:20%;"></td>
                    <td style="width:20%;"></td>
                </tr>
                <tr>
                    <td style="width:100%;font-weight:bold;">Note :Please note down Yes or No for observation </td>
                </tr>
                <tr>
                    <td style="width:100%;font-weight:bold;">Line Clearance given by:  </td>
                </tr>
                <tr>
                    <td style="width:100%;font-weight:bold;">Date:</td>
                </tr>
            </table>
            <br pagebreak="true"/>';
                            
            $html.='<table cellpadding="3" border="1">
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:100%;font-weight:bold;text-align:center;">WASH WATER FOR R.M. DISPENSING</td>
                </tr>
                <tr>
                    <td style="width:100%;">Reference Page No of other documents :</td>
                </tr>
                <tr>
                    <td style="width:100%;">Reference wash water report No	:</td>
                </tr>
            </table>
            <br pagebreak="true"/>';
                            
            $html.='<table cellpadding="3" border="1" >
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:100%;font-weight:bold;text-align:center;">CLEAN EQUIPMENT STATUS	LABEL FOR R.M. DISPENSING</td>
                </tr>
            </table>
            <br pagebreak="true"/>';
                    
            $html.='<table cellpadding="3" border="1" >
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:100%;font-weight:bold;text-align:center;">RM DISPENSING SHEET</td>
                </tr>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:30%;">Material Description</td>
                    <td style="width:10%;">T.R. No</td>
                    <td style="width:10%;">Net Wt.</td>
                    <td style="width:10%;">Tare Wt.</td>
                    <td style="width:10%;">Gross Wt.</td>
                    <td style="width:10%;">Wt. By</td>
                    <td style="width:10%;">Chk. By (RM) /Dt</td>
                    <td style="width:10%;">Ver. By (QA) /Dt</td>
                </tr>';
                $sql5 = "SELECT d.*, DATE(d.request_date) as request_date, p.dosage_form, p.product_name, p.grade FROM dispensing d LEFT JOIN product p ON d.product_code=p.product_code WHERE d.document_no='".$row["bmr_no"]."' ORDER BY id DESC";
                $result5 = $conn->query($sql5);
                if ($result5->num_rows > 0) {
                    while ($row5 = $result5->fetch_assoc()) {
                        $row["checkpoints"] = json_decode["checkpoints"];
                        $output5 = array();
                        $sql6 = "SELECT d.*, m.material_type, m.material_subtype, m.material_name, m.grade FROM dispensing_materials d LEFT JOIN material m ON d.material_code=m.material_code WHERE d.dispensing_no='".$row5["id"]."'";
                        $result6 = $conn->query($sql6);
                        if ($result6->num_rows > 0) {
                            while ($row6 = $result6->fetch_assoc()) {
                            $html.='
                            <tr>
                                <td style="width:100%;">'.$row2['stage'].'</td>
                            </tr>
                            <tr>
                                <td style="width:30%;">'.$row6['material_name'].'</td>
                                <td style="width:10%;"></td>
                                <td style="width:10%;">'.$row6['net_wt'].'</td>
                                <td style="width:10%;">'.$row6['tare_wt'].'</td>
                                <td style="width:10%;">'.$row6['gross_wt'].'</td>
                                <td style="width:10%;">'.$row6['done_by'].'</td>
                                <td style="width:10%;">'.$row6['check_by'].'</td>
                                <td style="width:10%;">'.$row6['request_by'].'</td>
                            </tr>';
                            }
                        }
                    }
                }
            $html.='
            </table>
            <br pagebreak="true"/>';
    
            $html.='<table cellpadding="3" border="1" >
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:100%;font-weight:bold;text-align:center;">ACTIVE MATERIAL CALCULATION</td>
                </tr>
                <tr>
                    <td style="width:100%;"><br>
                        <div></div>
                    </td>
                </tr>
            </table>
            <br pagebreak="true"/>';
            
            $html.='<table cellpadding="3" border="1" >
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:100%;font-weight:bold;text-align:center;">GRANULATION</td>
                </tr>
                <tr>
                    <td style="width:100%;font-weight:bold;">INSTRUCTIONS:</td>
                </tr>
                <tr>
                    <td style="width:100%;font-weight:bold;">
                        <ul>
                            <li>Use nose mask & hand gloves during operation.</li>
                            <li>Check for the line clearance per SOP No.	, cleaning & sanitization as per SOP No.and Cleanliness of equipment.</li>
                        </ul>
                    </td>
                </tr>
                <tr>
                    <td style="width:100%;font-weight:bold;">LINE CLEARANCE:</td>
                </tr>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:35%;">Description</td>
                    <td style="width:25%;">Observation</td>
                    <td style="width:20%;">Chkd By (Prod) /Date</td>
                    <td style="width:20%;">Ver. By(QA) /Date</td>
                </tr>';
                $sql5 = "SELECT * FROM lineclearance WHERE bmr_no='".$row["bmr_no"]."'AND stage='GRANULATION'";
                $result5 = $conn->query($sql5);
                if ($result5->num_rows > 0) {
                    while ($row5 = $result5->fetch_assoc()) {
                    $html.='
                    <tr>
                        <td style="width:35%;"></td>
                        <td style="width:25%;">'.$row5['remark'].'</td>
                        <td style="width:20%;">'.$row5['request_by'].'/'.date('d-m-Y',strtotime($row5['request_date'])).'</td>
                        <td style="width:20%;">'.$row5['entry_by'].'/'.date('d-m-Y',strtotime($row5['entry_date'])).'</td>
                    </tr>
                    <tr>
                        <td style="width:100%;font-weight:bold;">Note :Please note down Yes or No for observation </td>
                    </tr>
                    <tr>
                        <td style="width:100%;font-weight:bold;">Line Clearance given by: '.$row['entry_by'].' </td>
                    </tr>
                    <tr>
                        <td style="width:100%;font-weight:bold;">Date:'.date('d-m-Y',strtotime($row['entry_date'])).'</td>
                    </tr>';
                    }
                }
            $html.='        
            </table>
            <br pagebreak="true"/>';
            
            $html.='<table cellpadding="3" border="1">
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:100%;font-weight:bold;text-align:center;">WASH WATER FOR GRANULATION</td>
                </tr>
                <tr>
                    <td style="width:100%;">Reference Page No of other documents :</td>
                </tr>
                <tr>
                    <td style="width:100%;">Reference wash water report No	:</td>
                </tr>
            </table>
            <br pagebreak="true"/>';
            
            $html.='<table cellpadding="3" border="1" >
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:100%;font-weight:bold;text-align:center;">CLEAN EQUIPMENT STATUS LABEL FOR GRANULATION</td>
                </tr>
            </table>
            <br pagebreak="true"/>';
            
            $html.='<table cellpadding="3" border="1" >
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:100%;font-weight:bold;text-align:center;">RAW MATERIAL WEIGHT VERIFICATION</td>
                </tr>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:28%;">Item</td>
                    <td style="width:12%;">T.R. No</td>
                    <td style="width:12%;">Qty req. kg</td>
                    <td style="width:12%;">Gross Wt of Material</td>
                    <td style="width:12%;">Wt. By</td>
                    <td style="width:12%;">Chk. By (Prod.) /Date</td>
                    <td style="width:12%;">Ver.	By (QA) /Date</td>
                </tr>';
                $sql5 = "SELECT d.*, DATE(d.request_date) as request_date, p.dosage_form, p.product_name, p.grade FROM dispensing d LEFT JOIN product p ON d.product_code=p.product_code WHERE d.document_no='".$row["bmr_no"]."' ORDER BY id DESC";
                $result5 = $conn->query($sql5);
                if ($result5->num_rows > 0) {
                    while ($row5 = $result5->fetch_assoc()) {
                        $row["checkpoints"] = json_decode["checkpoints"];
                        $output5 = array();
                        $sql6 = "SELECT d.*, m.material_type, m.material_subtype, m.material_name, m.grade FROM dispensing_materials d LEFT JOIN material m ON d.material_code=m.material_code WHERE d.dispensing_no='".$row5["id"]."'";
                        $result6 = $conn->query($sql6);
                        if ($result6->num_rows > 0) {
                            while ($row6 = $result6->fetch_assoc()) {
                            $html.='
                            <tr>
                                <td style="width:100%;">'.$row2['stage'].'</td>
                            </tr>
                            <tr>
                                <td style="width:28%;">'.$row6['material_name'].'</td>
                                <td style="width:12%;"></td>
                                <td style="width:12%;">'.$row6['qty'].''.$row['unit'].'</td>
                                <td style="width:12%;">'.$row6['gross_wt'].'</td>
                                <td style="width:12%;">'.$row6['done_by'].'</td>
                                <td style="width:12%;">'.$row6['check_by'].'</td>
                                <td style="width:12%;">'.$row6['receive_by'].'</td>
                            </tr>';
                            }
                        }
                    }
                }
            $html.='
            </table>
            <br pagebreak="true"/>';
            
            $html.='<table cellpadding="3" border="1" >
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:100%;font-weight:bold;text-align:center;">RM ISSUING  LABEL</td>
                </tr>
                <tr>
                    <td style="width:100%;">Note : Dispensed material label should be paste here after opening container/bag</td>
                </tr>
            </table>
            <br pagebreak="true"/>';
            
            $html.='<table cellpadding="3" border="1" >
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:100%;font-weight:bold;text-align:center;">WET MIXING DETAIL</td>
                </tr>
                <tr>
                    <td style="width:100%;font-weight:bold;">Equipment Details:</td>
                </tr>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:20%;">Machine No/ Machine Name</td>
                    <td style="width:10%;">SOP Cleaning/ SOP Operating </td>
                    <td style="width:10%;">Date</td>
                    <td style="width:10%;">Time From</td>
                    <td style="width:10%;">To Time</td>
                    <td style="width:10%;">Total Time</td>
                    <td style="width:10%;">Temp / Mesh Size</td>
                    <td style="width:10%;">Oprator Name</td>
                    <td style="width:10%;">Chemist Name</td>
                </tr>';
                $sql2 = "SELECT u.*, e.equipment_name FROM equipment_usages u LEFT JOIN equipment e ON u.equipment_code=e.equipment_code WHERE u.bmr_no='".$row["bmr_no"]."' AND u.stage='WET MIXING'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                    $html.='
                    <tr>
                        <td style="width:20%;">'.$row2['equipment_code'].'</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;">'.date('d/m/Y',strtotime($row2['entry_date'])).'</td>
                        <td style="width:10%;">'.date('H:i',strtotime($row2['usage_from'])).'</td>
                        <td style="width:10%;">'.date('H:i',strtotime($row2['usage_to'])).'</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;">'.$row2[''].'</td>
                        <td style="width:10%;"></td>
                    </tr>';
                    }
                }
                $html.='
                <tr>
                    <td style="width:50%;">Checked by (Prod)/Dt.</td>
                    <td style="width:50%;">Ver. by (QA) /Dt : '.$row2['entry_by'].'/ '.date('d-m-Y',strtotime($row2['entry_date'])).'</td>
                </tr>';
            $html.='
            </table>
            <div></div>
            <table cellpadding="3" border="1" >
                <tr>
                    <td style="width:100%;font-weight:bold;">Material Details:</td>
                </tr>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:20%;">Ingredient Specifications</td>
                    <td style="width:10%;">Each Tablet Contents</td>
                    <td style="width:10%;">Equivalent Qty</td>
                    <td style="width:10%;">Tot. Qty Reqd. Kg</td>
                    <td style="width:10%;">Overages[%]</td>
                    <td style="width:10%;">Tot. Qty. Used</td>
                    <td style="width:10%;">T. R. NO</td>
                    <td style="width:10%;">Oprator Name</td>
                    <td style="width:10%;">Chemist Name</td>
                </tr>';
                $sql1 = "SELECT b.*, m.material_name, m.grade,m.order_qty,m.order_unit,m.standard_qty FROM bmr_material b LEFT JOIN material m ON b.material_code=m.material_code WHERE b.no='".$row["bmr_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                    $html.='
                    <tr>
                        <td style="width:20%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;">'.$row1['standard_qty'].'</td>
                        <td style="width:10%;">'.$row1['order_qty'].' '.$row1['order_unit'].'</td>
                        <td style="width:10%;">'.$row1['overages'].'</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>';
                    }
                }
                $html.='
                <tr>
                    <td style="width:50%;">Checked by (Prod)/Dt.: '.$row['entry_by'].'/ '.date('d-m-Y',strtotime($row['entry_date'])).'</td>
                    <td style="width:50%;">Ver. by (QA) /Dt</td>
                </tr>';
            $html.='
            </table>
            <div></div>
            <table cellpadding="3" border="1" >
                <tr>
                    <td style="width:100%;font-weight:bold;">Environmental Details:</td>
                </tr>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:25%;">Date</td>
                    <td style="width:25%;">Time</td>
                    <td style="width:25%;">Temp(Limit)</td>
                    <td style="width:25%;">Liquid(Limit)</td>
                </tr>';
                $sql2 = "SELECT * FROM environment_check WHERE bmr_no='".$row["bmr_no"]."' AND batch_no='".$row["batch_no"]."' AND stage='".$row1["WET MIXING"]."'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                    $html.='
                    <tr>
                        <td style="width:25%;">'.$row2['entry_date'].'</td>
                        <td style="width:25%;">'.$row2['entry_time'].'</td>
                        <td style="width:25%;"></td>
                        <td style="width:25%;"></td>
                    </tr>';
                    }
                }
                $html.='
                <tr>
                    <td style="width:50%;">Checked by (Prod)/Dt.</td>
                    <td style="width:50%;">Ver. by (QA) /Dt</td>
                </tr>
            </table>
            <br pagebreak="true"/>';
             
            $html.='<table cellpadding="3" border="1" >
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:100%;font-weight:bold;text-align:center;">BINDING DETAIL</td>
                </tr>
                <tr>
                    <td style="width:100%;font-weight:bold;">Equipment Details:</td>
                </tr>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:20%;">Machine No/ Machine Name</td>
                    <td style="width:10%;">SOP Cleaning/ SOP Operating </td>
                    <td style="width:10%;">Date</td>
                    <td style="width:10%;">Time From</td>
                    <td style="width:10%;">To Time</td>
                    <td style="width:10%;">Total Time</td>
                    <td style="width:10%;">Temp / Mesh Size</td>
                    <td style="width:10%;">Oprator Name</td>
                    <td style="width:10%;">Chemist Name</td>
                </tr>';
                $sql2 = "SELECT u.*, e.equipment_name FROM equipment_usages u LEFT JOIN equipment e ON u.equipment_code=e.equipment_code WHERE u.bmr_no='".$row["bmr_no"]."' AND u.stage='BINDING'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                    $html.='
                    <tr>
                        <td style="width:20%;">'.$row2['equipment_code'].'</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;">'.date('d/m/Y',strtotime($row2['entry_date'])).'</td>
                        <td style="width:10%;">'.date('H:i',strtotime($row2['usage_from'])).'</td>
                        <td style="width:10%;">'.date('H:i',strtotime($row2['usage_to'])).'</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;">'.$row2[''].'</td>
                        <td style="width:10%;"></td>
                    </tr>';
                    }
                }
                $html.='
                <tr>
                    <td style="width:50%;">Checked by (Prod)/Dt.</td>
                    <td style="width:50%;">Ver. by (QA) /Dt : '.$row2['entry_by'].'/ '.date('d-m-Y',strtotime($row2['entry_date'])).'</td>
                </tr>';
            $html.='
            </table>
            <div></div>
            <table cellpadding="3" border="1" >
                <tr>
                    <td style="width:100%;font-weight:bold;">Material Details:</td>
                </tr>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:20%;">Ingredient Specifications</td>
                    <td style="width:10%;">Each Tablet Contents</td>
                    <td style="width:10%;">Equivalent Qty</td>
                    <td style="width:10%;">Tot. Qty Reqd. Kg</td>
                    <td style="width:10%;">Overages[%]</td>
                    <td style="width:10%;">Tot. Qty. Used</td>
                    <td style="width:10%;">T. R. NO</td>
                    <td style="width:10%;">Oprator Name</td>
                    <td style="width:10%;">Chemist Name</td>
                </tr>';
                $sql1 = "SELECT b.*, m.material_name, m.grade,m.order_qty,m.order_unit,m.standard_qty FROM bmr_material b LEFT JOIN material m ON b.material_code=m.material_code WHERE b.no='".$row["bmr_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                    $html.='
                    <tr>
                        <td style="width:20%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;">'.$row1['standard_qty'].'</td>
                        <td style="width:10%;">'.$row1['order_qty'].' '.$row1['order_unit'].'</td>
                        <td style="width:10%;">'.$row1['overages'].'</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>';
                    }
                }
                $html.='
                <tr>
                    <td style="width:50%;">Checked by (Prod)/Dt.: '.$row['entry_by'].'/ '.date('d-m-Y',strtotime($row['entry_date'])).'</td>
                    <td style="width:50%;">Ver. by (QA) /Dt</td>
                </tr>';
            $html.='
            </table>
            <div></div>
            <table cellpadding="3" border="1" >
                <tr>
                    <td style="width:100%;font-weight:bold;">Environmental Details:</td>
                </tr>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:25%;">Date</td>
                    <td style="width:25%;">Time</td>
                    <td style="width:25%;">Temp(Limit)</td>
                    <td style="width:25%;">Liquid(Limit)</td>
                </tr>';
                $sql2 = "SELECT * FROM environment_check WHERE bmr_no='".$row["bmr_no"]."' AND batch_no='".$row["batch_no"]."' AND stage='BINDING'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                    $html.='
                    <tr>
                        <td style="width:25%;">'.$row2['entry_date'].'</td>
                        <td style="width:25%;">'.$row2['entry_time'].'</td>
                        <td style="width:25%;"></td>
                        <td style="width:25%;"></td>
                    </tr>';
                    }
                }
                $html.='
                <tr>
                    <td style="width:50%;">Checked by (Prod)/Dt.</td>
                    <td style="width:50%;">Ver. by (QA) /Dt</td>
                </tr>
            </table>
            <br pagebreak="true"/>';
            
            $html.='<table cellpadding="3" border="1" >
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:100%;font-weight:bold;text-align:center;">LUBRICATION DETAIL</td>
                </tr>
                <tr>
                    <td style="width:100%;font-weight:bold;">Equipment Details:</td>
                </tr>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:20%;">Machine No/ Machine Name</td>
                    <td style="width:10%;">SOP Cleaning/ SOP Operating </td>
                    <td style="width:10%;">Date</td>
                    <td style="width:10%;">Time From</td>
                    <td style="width:10%;">To Time</td>
                    <td style="width:10%;">Total Time</td>
                    <td style="width:10%;">Temp / Mesh Size</td>
                    <td style="width:10%;">Oprator Name</td>
                    <td style="width:10%;">Chemist Name</td>
                </tr>';
                 $sql2 = "SELECT u.*, e.equipment_name FROM equipment_usages u LEFT JOIN equipment e ON u.equipment_code=e.equipment_code WHERE u.bmr_no='".$row["bmr_no"]."' AND u.stage='LUBRICATION'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                    $html.='
                    <tr>
                        <td style="width:20%;">'.$row2['equipment_code'].'</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;">'.date('d/m/Y',strtotime($row2['entry_date'])).'</td>
                        <td style="width:10%;">'.date('H:i',strtotime($row2['usage_from'])).'</td>
                        <td style="width:10%;">'.date('H:i',strtotime($row2['usage_to'])).'</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;">'.$row2[''].'</td>
                        <td style="width:10%;"></td>
                    </tr>';
                    }
                }
                $html.='
                <tr>
                    <td style="width:50%;">Checked by (Prod)/Dt.</td>
                    <td style="width:50%;">Ver. by (QA) /Dt : '.$row2['entry_by'].'/ '.date('d-m-Y',strtotime($row2['entry_date'])).'</td>
                </tr>';
            $html.='
            </table>
            <div></div>
            <table cellpadding="3" border="1" >
                <tr>
                    <td style="width:100%;font-weight:bold;">Material Details:</td>
                </tr>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:20%;">Ingredient Specifications</td>
                    <td style="width:10%;">Each Tablet Contents</td>
                    <td style="width:10%;">Equivalent Qty</td>
                    <td style="width:10%;">Tot. Qty Reqd. Kg</td>
                    <td style="width:10%;">Overages[%]</td>
                    <td style="width:10%;">Tot. Qty. Used</td>
                    <td style="width:10%;">T. R. NO</td>
                    <td style="width:10%;">Oprator Name</td>
                    <td style="width:10%;">Chemist Name</td>
                </tr>';
                 $sql1 = "SELECT b.*, m.material_name, m.grade,m.order_qty,m.order_unit,m.standard_qty FROM bmr_material b LEFT JOIN material m ON b.material_code=m.material_code WHERE b.no='".$row["bmr_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                    $html.='
                    <tr>
                        <td style="width:20%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;">'.$row1['standard_qty'].'</td>
                        <td style="width:10%;">'.$row1['order_qty'].' '.$row1['order_unit'].'</td>
                        <td style="width:10%;">'.$row1['overages'].'</td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>';
                    }
                }
                $html.='
                <tr>
                    <td style="width:50%;">Checked by (Prod)/Dt.: '.$row['entry_by'].'/ '.date('d-m-Y',strtotime($row['entry_date'])).'</td>
                    <td style="width:50%;">Ver. by (QA) /Dt</td>
                </tr>';
            $html.='
            </table>
            <div></div>
            <table cellpadding="3" border="1" >
                <tr>
                    <td style="width:100%;font-weight:bold;">Environmental Details:</td>
                </tr>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:25%;">Date</td>
                    <td style="width:25%;">Time</td>
                    <td style="width:25%;">Temp(Limit)</td>
                    <td style="width:25%;">Liquid(Limit)</td>
                </tr>';
                $sql2 = "SELECT * FROM environment_check WHERE bmr_no='".$row["bmr_no"]."' AND batch_no='".$row["batch_no"]."' AND stage='LUBRICATION'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                    $html.='
                    <tr>
                        <td style="width:25%;">'.$row2['entry_date'].'</td>
                        <td style="width:25%;">'.$row2['entry_time'].'</td>
                        <td style="width:25%;"></td>
                        <td style="width:25%;"></td>
                    </tr>';
                    }
                }
                $html.='
                <tr>
                    <td style="width:50%;">Checked by (Prod)/Dt.</td>
                    <td style="width:50%;">Ver. by (QA) /Dt</td>
                </tr>
            </table>
            <br pagebreak="true"/>';
            
            $html.='<table cellpadding="3" border="1" >
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:100%;font-weight:bold;text-align:center;">IN PROCESS CHECK GROUP FOR QA CHEMIST AT GRANULATION STAGE</td>
                </tr>
                <tr>
                    <td style="width:100%;"><b>Granulation Stage :</b></td>
                </tr>
                <tr>
                    <td style="width:100%;">Granulation sampling date: '.$row1['expected_start_date'].'</td>
                </tr>
                <tr>
                    <td style="width:50%;">Appearance :</td>
                    <td style="width:50%;">Sign. of QA :'.$row1['complete_by'].'</td>
                </tr>
            </table>
            <br pagebreak="true"/>';
         
            $html.='<table cellpadding="3" border="1" >
                <tr>
                    <td style="width:100%;">Note :All the lubricant granules should be kept in Container with double labels in polythene bags</td>
                </tr>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:100%;font-weight:bold;text-align:center;">Yield Statement FOR RECONCILIATION OF GRANULES</td>
                </tr>
                <tr>
                    <td style="width:10%;"></td>
                    <td style="width:15%;">Gross wt</td>
                    <td style="width:15%;">Tare wt</td>
                    <td style="width:15%;">Net wt</td>
                    <td style="width:15%;">Done by(Operator)/Date</td>
                    <td style="width:15%;">Checked By(Prod)/Date</td>
                    <td style="width:15%;">Date</td>
                </tr>';
                $sql1 = "SELECT * FROM bmr_stages WHERE bmr_no='".$row["bmr_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row1["yields"] = json_decode($row1["yields"]);
                        $yields=$row1["yields"];
                        for($i=0;$i<count($yields);$i++){
                            $yield=$yields[$i];
                            $html.='
                            <tr>
                                <td style="width:10%;">'.$yield->yield.'</td>
                                <td style="width:15%;">'.$yield->gross.'</td>
                                <td style="width:15%;">'.$yield->tare.'</td>
                                <td style="width:15%;">'.$yeild->net.'</td>
                                <td style="width:15%;"></td>
                                <td style="width:15%;"></td>
                                <td style="width:15%;"></td>
                            </tr>';
                        }
                    }
                }
                $html.='
                <tr>
                    <td style="width:100%;">Total of Theoretical Wt.</td>
                </tr>
                <tr>
                    <td style="width:100%;">Actual wt. = Total Net wt. (D1+D2+D3+D4+D5+D6+D7)</td>
                </tr>
                <tr>
                    <td style="width:100%;">Yield=(Actual Wt/Theoretical wt.)x100 </td>
                </tr>
                <tr>
                    <td style="width:100%;">No. of tablet destroyed</td>
                </tr>
                <tr>
                    <td style="width:50%;">Ver By (QA):</td>
                    <td style="width:50%;">Date:</td>
                </tr>
            </table>
            <br pagebreak="true"/>';
                
            
            $html.='<table cellpadding="3" border="1" >
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:100%;font-weight:bold;text-align:center;">COMPRESSION</td>
                </tr>
                <tr>
                    <td style="width:100%;">Reference Page No of other documents :   	</td>
                </tr>
            </table>
            <br pagebreak="true"/>';
            
            $html.='<table cellpadding="3" border="1" >
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:100%;font-weight:bold;text-align:center;">IN PROCESS CHECK GROUP FOR QA CHEMIST AT GRANULATION STAGE</td>
                </tr>
                 <tr>
                    <td style="width:100%;font-weight:bold;">INSTRUCTIONS:</td>
                </tr>
                <tr>
                    <td style="width:100%;font-weight:bold;">
                        <ul>
                            <li>Use nose mask & hand gloves during operation.</li>
                            <li>Check for the line clearance per SOP No.	, cleaning & sanitization as per SOP No.and Cleanliness of equipment.</li>
                            <li>Initially after setting the machine, individually check the appearance, weight and thickness of all tablets corresponding to all punches.</li>
                            <li>All the compressed tablets should be packed in the plastic container</li>
                            <li>Drums are weighed and shifted to quarantine</li>
                            <li>Compression m/c (st) Machine ID No. 	</li>
                        </ul>
                    </td>
                </tr>
                <tr>
                    <td style="width:100%;font-weight:bold;">LINE CLEARANCE:</td>
                </tr>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:35%;">Description</td>
                    <td style="width:25%;">Observation</td>
                    <td style="width:20%;">Chkd By (Prod) /Date</td>
                    <td style="width:20%;">Ver. By(QA) /Date</td>
                </tr>';
                $sql2 = "SELECT * FROM lineclearance WHERE bmr_no='".$row["bmr_no"]."' AND stage='GRANULATION'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                    $row2["checkpoints"] = json_decode($row2["checkpoints"]);
                    $checkpoint=$row2["checkpoints"];
                        for($i=0;$i<count($checkpoint);$i++){
                        $checkpoint=$checkpoint[$i]; 
                        $html.='
                        <tr>
                            <td style="width:35%;">'.$checkpoint->checkpoints.'</td>
                            <td style="width:25%;">'.$checkpoint->production_status.'</td>
                            <td style="width:20%;"></td>
                            <td style="width:20%;">'.$row['entry_by'].'</td>
                        </tr>';
                        }
                    }
                }
                $html.='
                <tr>
                    <td style="width:100%;font-weight:bold;">Note :Please note down Yes or No for observation </td>
                </tr>
                <tr>
                    <td style="width:100%;font-weight:bold;">Line Clearance given by:  </td>
                </tr>
                <tr>
                    <td style="width:100%;font-weight:bold;">Date:</td>
                </tr>
            </table>
            <br pagebreak="true"/>';
            
            $html.='<table cellpadding="3" border="1">
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:100%;font-weight:bold;text-align:center;">WASH WATER FOR COMPRESSION</td>
                </tr>
                <tr>
                    <td style="width:100%;">Reference Page No of other documents :</td>
                </tr>
                <tr>
                    <td style="width:100%;">Reference wash water report No	:</td>
                </tr>
            </table>
            <br pagebreak="true"/>';
            
            $html.='<table cellpadding="3" border="1">
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:100%;font-weight:bold;text-align:center;">CLEAN EQUIPMENT STATUS LABEL FOR COMPRESSION</td>
                </tr>
            </table>
            <br pagebreak="true"/>';
            $html.='<table cellpadding="3" border="1">
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:100%;font-weight:bold;text-align:center;">COMPRESSION	DETAIL</td>
                </tr>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:15%;">Machine No/ Machine Name</td>
                    <td style="width:13%;">SOP Cleaning/ SOP Operating</td>
                    <td style="width:12%;">Date</td>
                    <td style="width:10%;">Time From</td>
                    <td style="width:10%;">To Time</td>
                    <td style="width:10%;">Total Time</td>
                    <td style="width:10%;">Temp .C</td>
                    <td style="width:10%;">Checked By (Prod) /Date</td>
                    <td style="width:10%;">Ver. By (QA) /Date</td>
                </tr>
                <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <td style="width:33%;border:none;">Upper Punch </td>
                    <td style="width:34%;border:none;">Lower Punch</td>
                    <td style="width:33%;border:none;">Specification</td>
                </tr>
            </table>
            <div></div>';
            $sql2 = "SELECT * FROM spec_tests WHERE specification_no IN (SELECT specification_no FROM specification WHERE spec_type='Inprocess Specification' AND product_code='".$row["product_code"]."')";
            $result2 = $conn->query($sql2);
            $row2 = $result2->fetch_assoc();
            $html.='
            <table cellpadding="3" border="1">
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:100%;font-weight:bold;text-align:center;">SPECIFICATION</td>
                </tr>
                <tr style="border:none;">
                    <td style="width:50%;">Appearance</td>
                    <td style="width:50%;">Color</td>
                </tr>
                <tr>
                    <td style="width:50%;">Size</td>
                    <td style="width:50%;">Marking</td>
                </tr>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:20%;">Test Name</td>
                    <td style="width:20%;">Specification</td>
                    <td style="width:20%;">InHouse Limit</td>
                    <td style="width:20%;">Caution Limit</td>
                    <td style="width:20%;">Frequency</td>
                </tr>
                 <tr>
                    <td style="width:20%;">'.$row2['test'].'</td>
                    <td style="width:20%;">'.$row2['specification_no'].'</td>
                    <td style="width:20%;"></td>
                    <td style="width:20%;"></td>
                    <td style="width:20%;"></td>
                </tr>
            </table>
            <div></div>
            <table>
                <tr>
                    <td style="width:50%;">Checked By (Prod) /Date:</td>
                    <td style="width:50%;">Ver. By (QA) /Date:'.$row['entry_by'].''.$row['entry_date'].'</td>
                </tr>
            </table>
            <br pagebreak="true"/>';
    
            $html.='<table cellpadding="3" border="1">
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:100%;font-weight:bold;text-align:center;">IN PROCESS CHECK GROUPTO BE JOINTLY CHECKED BY PRODUCTION & QA CHEMIST</td>
                </tr>
                <tr>
                    <td style="width:100%;">
                        <table>
                            <tr>
                                <td style="width:25%;">Unit Wt.of each tablet:_____mg.</td>
                                <td style="width:25%;">Limit +:________%</td>
                                <td style="width:25%;">Max Wt.:	mg</td>
                                <td style="width:25%;">Mini Wt: 	</td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="width:100%;"><b>APPEARANCE:</b> </td>
                </tr>
            </table>
            <div></div>
            <table cellpadding="3" border="1">
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:20%;">Wt. of tab.(mg)</td>
                    <td style="width:20%;">Hardness kg/cm2 Limit : N.L.T. 2</td>
                    <td style="width:20%;">Thickness mm Limit : + 0.3mm</td>
                    <td style="width:10%;">Diameter mm Limit : + 0.3mm</td>
                    <td style="width:10%;">Length mm Limit : + 0.3mm</td>
                    <td style="width:10%;">Width mm limit : + 0.3mm</td>
                    <td style="width:10%;">Appearance</td>
                </tr>
                <tr>
                    <td style="width:10%;">L.H.S</td>
                    <td style="width:10%;">R.H.S</td>
                    <td style="width:10%;">L.H.S</td>
                    <td style="width:10%;">R.H.S</td>
                    <td style="width:10%;">L.H.S</td>
                    <td style="width:10%;">R.H.S</td>
                    <td style="width:10%;"></td>
                    <td style="width:10%;"></td>
                    <td style="width:10%;"></td>
                    <td style="width:10%;"></td>
                </tr>
                <tr>';
                $sql1 = "SELECT * FROM inprocess_check WHERE stage='GRANULATION' AND bmr_no='".$row["bmr_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                     $row1["checkpoints"] = json_decode($row1["checkpoints"]);
                     $checkpoint= $row1["checkpoints"];
                        for($i=0;$i<count($checkpoint);$i++){
                          $checkpoints=$checkpoint[$i];
                         $html.='
                            <td style="width:10%;">'.$checkpoints->display.'</td>';
                            
                        }
                    }
                }
            $html.='
            </tr>
            </table>
            <table>
                <tr>
                    <td style="width:50%;">Checked	by (Prod):</td>
                    <td style="width:50%;">Ver. By (QA):</td>
                </tr>
                <tr>
                    <td style="width:50%;">Date:  	</td>
                    <td style="width:50%;">Date:  	</td>
                </tr>
                <tr>
                    <td style="width:50%;">Time: 	</td>
                    <td style="width:50%;">Time: 	</td>
                </tr>
                
            </table>
            <br pagebreak="true"/>';
            
            $html.='<table cellpadding="3" border="1">
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:100%;font-weight:bold;text-align:center;">IN PROCESS CHECK GROUP	FOR PRODUCTION CHEMIST AT COMPRESSION STAGE</td>
                </tr>
                <tr>
                    <td style="width:100%;font-weight:bold;">Check following parameter</td>
                </tr>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:10%;">Date& Time</td>
                    <td style="width:9%;">Temp.& RH</td>
                    <td style="width:9%;">Wt. of 20 tablet (gm)</td>
                    <td style="width:9%;">Hardness kg/cm2 Limit :N.L.T. 2</td>
                    <td style="width:9%;">DT min/sec Limit: N.M.T.15min/sec</td>
                    <td style="width:9%;">Friability % W/WLimit :N.M.T. 1%</td>
                    <td style="width:9%;">Thickness mm Limit : + 0.3mm</td>
                    <td style="width:9%;">Diameter/ Width mm Limit : + 0.3mm</td>
                    <td style="width:9%;">Length mm	Li: + 0.3mm</td>
                    <td style="width:9%;">No. of tablets defected</td>
                    <td style="width:9%;">Checked by (Prod)/Date</td>
                </tr>
                <tr>
                  <td></td> 
                  <td></td>
                  <td></td>
                  <td></td>
                  <td></td>
                  <td></td>
                  <td></td>
                  <td></td>
                  <td></td>
                  <td></td>
                  <td></td>
                  <td></td>
                </tr>
            </table>
            <table>
                <tr>
                    <td style="width:100%;">Note: No of tablets rejected = Tablets having Black Spots, Foreign Particles, Broken Tablet, Chipped Tablets</td>
                </tr>
                <tr>
                    <td style="width:50%;">Checked	by	(Prod):  </td>
                    <td style="width:50%;">Date: </td>
                </tr>
            </table>
            <br pagebreak="true"/>'; 
            
            $html.='<table cellpadding="3" border="1">
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:100%;font-weight:bold;text-align:center;">IN PROCESS CHECK GROUP	FOR QA CHEMIST AT COMPRESSION STAGE</td>
                </tr>
                <tr>
                    <td style="width:100%;font-weight:bold;">Check following parameter</td>
                </tr>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:10%;">Date& Time</td>
                    <td style="width:9%;">Temp.& RH</td>
                    <td style="width:9%;">Wt. of 20 tablet (gm)</td>
                    <td style="width:9%;">Hardness kg/cm2 Limit :N.L.T. 2</td>
                    <td style="width:9%;">DT min/sec Limit: N.M.T.15min/sec</td>
                    <td style="width:9%;">Friability % W/WLimit :N.M.T. 1%</td>
                    <td style="width:9%;">Thickness mm Limit : + 0.3mm</td>
                    <td style="width:9%;">Diameter/ Width mm Limit : + 0.3mm</td>
                    <td style="width:9%;">Length mm	Li: + 0.3mm</td>
                    <td style="width:9%;">No. of tablets defected</td>
                    <td style="width:9%;">Checked by (Prod)/Date</td>
                </tr>
                <tr>
                  <td></td> 
                  <td></td>
                  <td></td>
                  <td></td>
                  <td></td>
                  <td></td>
                  <td></td>
                  <td></td>
                  <td></td>
                  <td></td>
                  <td></td>
                  <td></td>
                </tr>
            </table>
            <table>
                <tr>
                    <td style="width:100%;">Note: No of tablets rejected = Tablets having Black Spots, Foreign Particles, Broken Tablet, Chipped Tablets</td>
                </tr>
                <tr>
                    <td style="width:50%;">Ver. By (QA) :  </td>
                    <td style="width:50%;">Date: </td>
                </tr>
            </table>
            <br pagebreak="true"/>';
            
            $html.='<table cellpadding="3" border="1">
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:100%;font-weight:bold;text-align:center;">INSTRUCTION REGARDING WEIGHT VARIATION</td>
                </tr>
                <tr>
                    <td style="width:100%;font-weight:bold;">Note:</td>
                </tr>
                <tr>
                    <td style="width:100%;">
                        <ol>
                            <li>Total Wt.=Total weight of 20 Tablet</li>
                            <li>Avg .Wt.=Total  weight /  20</li>
                            <li>Mini Wt. = Minimum weight of 20 observations</li>
                            <li>Max. Wt.= Maximum weight of 20 observations</li>
                        </ol>
                    </td>
                </tr>
                <tr>
                    <td style="width:100%;font-weight:bold;">Theoretical Standard Calculation:</td>
                </tr>
                <tr>
                    <td style="width:25%;">Wt.of each tablet:____mg.</td>
                    <td style="width:25%;">Limit ±:____%</td>
                    <td style="width:25%;">Max Wt.:____mg</td>
                    <td style="width:25%;">Mini Wt:____</td>
                </tr>
            </table>
            <br pagebreak="true"/>';
            
            $html.='<table cellpadding="3" border="1">
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:100%;font-weight:bold;text-align:center;">IN PROCESS CHECK GROUP TO BE JOINTLY CHECKED BY PRODUCTION & QA CHEMIST(WEIGHT VARIATION)</td>
                </tr>
                <tr>
                    <td style="width:100%;font-weight:bold;">Check Weight variations</td>
                </tr>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:20%;">Date &Time :</td>
                    <td style="width:20%;">Date &Time :</td>
                    <td style="width:20%;">Date &Time :</td>
                    <td style="width:20%;">Date &Time :</td>
                    <td style="width:20%;">Date &Time :</td>
                </tr>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:4%;"></td>
                    <td style="width:16%;">Wt. of tab.(mg)</td>
                    <td style="width:4%;"></td>
                    <td style="width:16%;">Wt. of tab.(mg)</td>
                    <td style="width:4%;"></td>
                    <td style="width:16%;">Wt. of tab.(mg)</td>
                    <td style="width:4%;"></td>
                    <td style="width:16%;">Wt. of tab.(mg)</td>
                    <td style="width:4%;"></td>
                    <td style="width:16%;">Wt. of tab.(mg)</td>
                </tr>
                <tr>
                    <td style="width:4%;"></td>
                    <td style="width:8%;">L.H.S</td>
                    <td style="width:8%;">R.H.S</td>
                    <td style="width:4%;"></td>
                    <td style="width:8%;">L.H.S</td>
                    <td style="width:8%;">R.H.S</td>
                    <td style="width:4%;"></td>
                    <td style="width:8%;">L.H.S</td>
                    <td style="width:8%;">R.H.S</td>
                    <td style="width:4%;"></td>
                    <td style="width:8%;">L.H.S</td>
                    <td style="width:8%;">R.H.S</td>
                    <td style="width:4%;"></td>
                    <td style="width:8%;">L.H.S</td>
                    <td style="width:8%;">R.H.S</td>
                </tr>
                <tr>
                    <td style="width:4%;"></td>
                    <td style="width:8%;"></td>
                    <td style="width:8%;"></td>
                    <td style="width:4%;"></td>
                    <td style="width:8%;"></td>
                    <td style="width:8%;"></td>
                    <td style="width:4%;"></td>
                    <td style="width:8%;"></td>
                    <td style="width:8%;"></td>
                    <td style="width:4%;"></td>
                    <td style="width:8%;"></td>
                    <td style="width:8%;"></td>
                    <td style="width:4%;"></td>
                    <td style="width:8%;"></td>
                    <td style="width:8%;"></td>
                </tr>
                <tr>
                    <td style="width:4%;">Total Wt.</td>
                    <td style="width:8%;"></td>
                    <td style="width:8%;"></td>
                    <td style="width:4%;">Total Wt.</td>
                    <td style="width:8%;"></td>
                    <td style="width:8%;"></td>
                    <td style="width:4%;">Total Wt.</td>
                    <td style="width:8%;"></td>
                    <td style="width:8%;"></td>
                    <td style="width:4%;">Total Wt.</td>
                    <td style="width:8%;"></td>
                    <td style="width:8%;"></td>
                    <td style="width:4%;">Total Wt.</td>
                    <td style="width:8%;"></td>
                    <td style="width:8%;"></td>
                </tr>
                <tr>
                    <td style="width:4%;">Avg. Wt.</td>
                    <td style="width:8%;"></td>
                    <td style="width:8%;"></td>
                    <td style="width:4%;">Avg. Wt.</td>
                    <td style="width:8%;"></td>
                    <td style="width:8%;"></td>
                    <td style="width:4%;">Avg. Wt.</td>
                    <td style="width:8%;"></td>
                    <td style="width:8%;"></td>
                    <td style="width:4%;">Avg. Wt.</td>
                    <td style="width:8%;"></td>
                    <td style="width:8%;"></td>
                    <td style="width:4%;">Avg. Wt.</td>
                    <td style="width:8%;"></td>
                    <td style="width:8%;"></td>
                </tr>
                <tr>
                    <td style="width:4%;">Mini. Wt.</td>
                    <td style="width:8%;"></td>
                    <td style="width:8%;"></td>
                    <td style="width:4%;">Mini. Wt.</td>
                    <td style="width:8%;"></td>
                    <td style="width:8%;"></td>
                    <td style="width:4%;">Mini. Wt.</td>
                    <td style="width:8%;"></td>
                    <td style="width:8%;"></td>
                    <td style="width:4%;">Mini. Wt.</td>
                    <td style="width:8%;"></td>
                    <td style="width:8%;"></td>
                    <td style="width:4%;">Mini. Wt.</td>
                    <td style="width:8%;"></td>
                    <td style="width:8%;"></td>
                </tr>
                <tr>
                    <td style="width:4%;">Max. Wt.</td>
                    <td style="width:8%;"></td>
                    <td style="width:8%;"></td>
                    <td style="width:4%;">Max. Wt.</td>
                    <td style="width:8%;"></td>
                    <td style="width:8%;"></td>
                    <td style="width:4%;">Max. Wt.</td>
                    <td style="width:8%;"></td>
                    <td style="width:8%;"></td>
                    <td style="width:4%;">Max. Wt.</td>
                    <td style="width:8%;"></td>
                    <td style="width:8%;"></td>
                    <td style="width:4%;">Max. Wt.</td>
                    <td style="width:8%;"></td>
                    <td style="width:8%;"></td>
                </tr>
            </table>
            <table>
                <tr>
                    <td style="width:50%;">Checked	by(Prod)  	 </td>
                    <td style="width:50%;">Ver. By (QA) 	</td>
                </tr>
                <tr>
                    <td style="width:50%;">Date:</td>
                    <td style="width:50%;">Date:</td>
                </tr>
            </table>
            <br pagebreak="true"/>';
            
            $html.='<table cellpadding="3" border="1" >
                <tr>
                    <td style="width:100%;">Note :All the lubricant granules should be kept in Container with double labels in polythene bags</td>
                </tr>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:100%;font-weight:bold;text-align:center;">RECONCILIATION OF COMPRESSED TABLETS</td>
                </tr>
                <tr>
                    <td style="width:10%;"></td>
                    <td style="width:15%;">Gross wt</td>
                    <td style="width:15%;">Tare wt</td>
                    <td style="width:15%;">Net wt</td>
                    <td style="width:15%;">Done by(Operator)/Date</td>
                    <td style="width:15%;">Checked By(Prod)/Date</td>
                    <td style="width:15%;">Date</td>
                </tr>
                <tr>
                    <td style="width:10%;">Drum1</td>
                    <td style="width:15%;"></td>
                    <td style="width:15%;"></td>
                    <td style="width:15%;"></td>
                    <td style="width:15%;"></td>
                    <td style="width:15%;"></td>
                    <td style="width:15%;"></td>
                </tr>
                <tr>
                    <td style="width:100%;">Total of Theoretical Wt.</td>
                </tr>
                <tr>
                    <td style="width:100%;">Actual wt. = Total Net wt. (D1+D2+D3+D4+D5+D6+D7)</td>
                </tr>
                <tr>
                    <td style="width:100%;">Yield=(Actual Wt/Theoretical wt.)x100 </td>
                </tr>
                <tr>
                    <td style="width:100%;">No. of tablet destroyed</td>
                </tr>
                <tr>
                    <td style="width:50%;">Ver By (QA):</td>
                    <td style="width:50%;">Date:</td>
                </tr>
            </table>
            <br pagebreak="true"/>';
            
            $html.='<table cellpadding="3" border="1">
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:100%; font-weight:bold; text-align:center;">IN   PROCESS QC SLIP (COMPRESSED TABLET)</td>
                </tr>
                <tr>
                    <td style="width:100%;">Reference Page No of other documents :   	</td>
                </tr>
            </table>
            <br pagebreak="true"/>';
            
            $html.='<table cellpadding="3" border="1">
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:100%; font-weight:bold; text-align:center;">GENERAL INSTRUCTION FOR PACKING</td>
                </tr>
                <tr>
                    <td style="width:100%;">PACKING INSTRUCTION</td>
                </tr>
            </table>
            <br pagebreak="true"/>';
            
            $html.='<table cellpadding="3" border="1">
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:100%; font-weight:bold; text-align:center;">PACKING MATERIAL REQUISITION SLIP</td>
                </tr>
                <tr>
                    <td style="width:100%;">Reference Page No of other documents :   	</td>
                </tr>
            </table>
            <br pagebreak="true"/>';
            
            $html.='<table cellpadding="3" border="1" >
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:100%;font-weight:bold;text-align:center;">PACKING </td>
                </tr>
                 <tr>
                    <td style="width:100%;font-weight:bold;">INSTRUCTIONS:</td>
                </tr>
                <tr>
                    <td style="width:100%;font-weight:bold;">
                        <ul>
                            <li>Issue batch details for over printing the label</li>
                            <li>Check for the line clearance as per SOP No.	, cleanliness of area as per SOP No. 	</li>
                            <li>Check the print for batch coding details.</li>
                            <li>Check the in process for the printing every hour</li>
                            <li>Record the printing rejection analysis, total quantity taken for printing, rejection obtained & total quantity given for packing, after completion of printing operation.</li>
                            <li>Attach checked & approved specimens of inserts, labels & shipper printing matter details to the batch record.</li>
                        </ul>
                    </td>
                </tr>
                <tr>
                    <td style="width:100%;font-weight:bold;">LINE CLEARANCE:</td>
                </tr>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:35%;">Description</td>
                    <td style="width:25%;">Observation</td>
                    <td style="width:20%;">Chkd By (Prod) /Date</td>
                    <td style="width:20%;">Ver. By(QA) /Date</td>
                </tr>
                <tr>
                    <td style="width:35%;"></td>
                    <td style="width:25%;"></td>
                    <td style="width:20%;"></td>
                    <td style="width:20%;"></td>
                </tr>
                <tr>
                    <td style="width:100%;font-weight:bold;">Note :Please note down Yes or No for observation </td>
                </tr>
                <tr>
                    <td style="width:100%;font-weight:bold;">Line Clearance given by:  </td>
                </tr>
                <tr>
                    <td style="width:100%;font-weight:bold;">Date:</td>
                </tr>
            </table>
            <br pagebreak="true"/>';
            
            $html.='<table cellpadding="3" border="1">
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:100%; font-weight:bold; text-align:center;">CLEAN EQUIPMENT STATUS LABEL FOR PACKING STAGE</td>
                </tr>
            </table>
            <br pagebreak="true"/>';
            
            $html.='<table cellpadding="3" border="1">
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:100%; font-weight:bold; text-align:center;">FINAL SPECIMEN OF PACKING SAMPLES    MATERIAL</td>
                </tr>
            </table>
            <br pagebreak="true"/>';
    
            $html.='<table cellpadding="3" border="1">
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:100%; font-weight:bold; text-align:center;">VERIFICATION OF PACKING MATERIAL ON RECEIPT AT PACKING ROOM</td>
                </tr>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:10%;">Sr. No./ Specimen No</td>
                    <td style="width:15%;">Packing Material Name</td>
                    <td style="width:15%;">No. of Packing Received</td>
                    <td style="width:15%;">Packing (wt of PM)</td>
                    <td style="width:15%;">Total Packing Qty</td>
                    <td style="width:15%;">Packing Chemist/Incharge Sign /Date</td>
                    <td style="width:15%;">Ver. by(QA) /Date</td>
                </tr>';
                $sql2="SELECT * FROM unitformula WHERE mfr_no='".$row["mfr_no"]."'";
                $result2 = $conn->query($sql2);
                $j=1;
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                    $row2['packing_materials'] = json_decode($row2['packing_materials']);
                    $packing_materials=$row2['packing_materials'];
                        for($i=0;$i<count($packing_materials);$i++){
                        $packing=$packing_materials[$i];
                        $html.='
                        <tr>
                            <td style="width:10%;">'.$j++.'</td>
                            <td style="width:15%;">'.$packing->material_name.'</td>
                            <td style="width:15%;"></td>
                            <td style="width:15%;">'.$packing->qty.''.$packing->unit.'</td>
                            <td style="width:15%;"></td>
                            <td style="width:15%;"></td>
                            <td style="width:15%;"></td>
                        </tr>'; 
                        }
                    }
                }
                $html.='
                <tr>
                    <td style="width:100%;font-weight:bold;">Packing supervisor should fill the annexure and the received packing material should be verify in presence of QA, Packing Chemist/In-charge should attach the specimen of each packing material.</td>
                </tr>
            </table>
            <br pagebreak="true"/>';
            
            $html.='<table cellpadding="3" border="1">
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:100%; font-weight:bold; text-align:center;">STEREO CHECKING</td>
                </tr>
                <tr>
                    <td style="width:100%;font-weight:bold;">OVERPRINTED / PREPRINTED LABELS DETAILS</td>
                </tr>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:20%;"></td>
                    <td style="width:40%;">Details</td>
                    <td style="width:20%;">Checked by (Prod) /Date</td>
                    <td style="width:20%;">Ver. by (QA) /Date</td>
                </tr>
                <tr>
                    <td style="width:20%;">Name of the Product</td>
                    <td style="width:40%;">'.$row['product_name'].'</td>
                    <td style="width:20%;">'.$row['entry_by'].'/'.date('d-m-Y',strtotime($row['entry_date'])).'</td>
                    <td style="width:20%;"></td>
                </tr>
                <tr>
                    <td style="width:20%;">Batch No.</td>
                    <td style="width:40%;">'.$row['batch_no'].'</td>
                    <td style="width:20%;"></td>
                    <td style="width:20%;"></td>
                </tr>
                <tr>
                    <td style="width:20%;">Mfg. date</td>
                    <td style="width:40%;"></td>
                    <td style="width:20%;"></td>
                    <td style="width:20%;"></td>
                </tr>
                <tr>
                    <td style="width:20%;">Exp. date</td>
                    <td style="width:40%;"></td>
                    <td style="width:20%;"></td>
                    <td style="width:20%;"></td>
                </tr>
                <tr>
                    <td style="width:20%;">Mfg. Lic. No.</td>
                    <td style="width:40%;"></td>
                    <td style="width:20%;"></td>
                    <td style="width:20%;"></td>
                </tr>
                <tr>
                    <td style="width:20%;">Artwork code No.</td>
                    <td style="width:40%;"></td>
                    <td style="width:20%;"></td>
                    <td style="width:20%;"></td>
                </tr>
                <tr>
                    <td style="width:20%;">Specimen overprinted</td>
                    <td style="width:40%;"></td>
                    <td style="width:20%;"></td>
                    <td style="width:20%;"></td>
                </tr>
                <tr>
                    <td style="width:100%;font-weight:bold;">Packing supervisor should fill the annexure and the received packing material should be verify in presence of QA, Packing Chemist/In-charge should attach the specimen of each packing material.</td>
                </tr>
            </table>
            <br pagebreak="true"/>';
    
            $html.='<table cellpadding="3" border="1">
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:100%; font-weight:bold; text-align:center;">STEREO IMPRINT PAGE</td>
                </tr>
                <tr>
                    <td style="width:100%;font-weight:bold;">Reference Page No of other documents :   	</td>
                </tr>
            </table>
            <br pagebreak="true"/>';
            
            $html.='<table cellpadding="3" border="1">
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:100%; font-weight:bold; text-align:center;">STEREO CODED PACKING SAMPLE   MATERIAL</td>
                </tr>
            </table>
            <br pagebreak="true"/>';
            
            $html.='<table cellpadding="3" border="1">
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:100%; font-weight:bold; text-align:center;">LINE ALLOCATION FOR PRIMARY PACKING</td>
                </tr>
                <tr>
                    <td style="width:100%;font-weight:bold;">Primary packing</td>
                </tr>
                <tr>
                    <td style="width:100%;font-weight:bold;">Following parameter to be filled when shift change</td>
                </tr>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:12%;">Date</td>
                    <td style="width:11%;">Time</td>
                    <td style="width:11%;">Bottle clean by</td>
                    <td style="width:11%;">Tablet weighed by</td>
                    <td style="width:11%;">Cotton added by</td>
                    <td style="width:11%;">Sealing done by</td>
                    <td style="width:11%;">Stripping done by</td>
                    <td style="width:11%;">Tablet filled by</td>
                    <td style="width:11%;">Checked by (Prod) /Date</td>
                </tr>
                <tr>
                    <td style="width:12%;"></td>
                    <td style="width:11%;"></td>
                    <td style="width:11%;"></td>
                    <td style="width:11%;"></td>
                    <td style="width:11%;"></td>
                    <td style="width:11%;"></td>
                    <td style="width:11%;"></td>
                    <td style="width:11%;"></td>
                    <td style="width:11%;"></td>
                </tr>
            </table>
            <br pagebreak="true"/>';
            
            $html.='<table cellpadding="3" border="1">
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:100%; font-weight:bold; text-align:center;">IN PROCESS CHECK GROUP FOR PRODUCTION CHEMIST AT PACKING STAGE</td>
                </tr>
                <tr>
                    <td style="width:100%;">Check following parameter</td>
                </tr>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:10%;">Date& Time</td>
                    <td style="width:9%;">Wt. of pack</td>
                    <td style="width:9%;">No. of Pack checked</td>
                    <td style="width:9%;">Sealing OR Leak test</td>
                    <td style="width:9%;">No. of Defective Packs</td>
                    <td style="width:9%;">Batch No.</td>
                    <td style="width:9%;">Mfg. Date</td>
                    <td style="width:9%;">Exp. Date</td>
                    <td style="width:9%;">M.R.P/Export</td>
                    <td style="width:9%;">Forming Temp./ Sealing Temp.</td>
                    <td style="width:9%;">Checked by (Prod)/Date</td>
                </tr>
                <tr>
                    <td style="width:10%;"></td>
                    <td style="width:9%;"></td>
                    <td style="width:9%;"></td>
                    <td style="width:9%;"></td>
                    <td style="width:9%;"></td>
                    <td style="width:9%;"></td>
                    <td style="width:9%;"></td>
                    <td style="width:9%;"></td>
                    <td style="width:9%;"></td>
                    <td style="width:9%;"></td>
                    <td style="width:9%;"></td>
                </tr>
                <tr>
                    <td style="width:50%;">Checked by (Prod)</td>
                    <td style="width:50%;">Date</td>
                </tr>
            </table>
            <br pagebreak="true"/>';
            
            $html.='<table cellpadding="3" border="1">
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:100%; font-weight:bold; text-align:center;">IN PROCESS CHECK GROUP FOR QA CHEMIST AT PACKING STAGE</td>
                </tr>
                <tr>
                    <td style="width:100%;">Initially Check following parameter</td>
                </tr>
                <tr>
                    <td style="width:25%;">License No</td>
                    <td style="width:25%;"></td>
                    <td style="width:25%;">Mfg. date</td>
                    <td style="width:25%;"></td>
                </tr>
                <tr>
                    <td style="width:25%;">Batch No</td>
                    <td style="width:25%;"></td>
                    <td style="width:25%;">Exp. date</td>
                    <td style="width:25%;"></td>
                </tr>
                <tr>
                    <td style="width:25%;">Packing Style</td>
                    <td style="width:25%;"></td>
                    <td style="width:25%;">Colour of PVC</td>
                    <td style="width:25%;"></td>
                </tr>
                <tr>
                    <td style="width:40%;">Ver. by (QA): </td>
                    <td style="width:30%;">Date :</td>
                    <td style="width:30%;">Time:</td>
                </tr>
            </table>
            <div></div>
            <table cellpadding="3" border="1" >
                <tr>
                    <td style="width:100%;font-weight:bold;">Check following parameter</td>
                </tr>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:12%;">Date& Time</td>
                    <td style="width:11%;">Foil</td>
                    <td style="width:11%;">Stereo</td>
                    <td style="width:11%;">Strip</td>
                    <td style="width:11%;">Leak test</td>
                    <td style="width:11%;">No. of Carton In Shipper</td>
                    <td style="width:11%;">Number of shipper</td>
                    <td style="width:11%;">Strip inspection</td>
                    <td style="width:11%;">Ver. By (QA) /Date</td>
                </tr>
                <tr>
                    <td style="width:12%;"></td>
                    <td style="width:11%;"></td>
                    <td style="width:11%;"></td>
                    <td style="width:11%;"></td>
                    <td style="width:11%;"></td>
                    <td style="width:11%;"></td>
                    <td style="width:11%;"></td>
                    <td style="width:11%;"></td>
                    <td style="width:11%;"></td>
                </tr>
                 <tr>
                    <td style="width:50%;">Ver. by (QA Incharge)</td>
                    <td style="width:50%;">Date</td>
                </tr>
            </table>
            <br pagebreak="true"/>';
    
            $html.='<table cellpadding="3" border="1">
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:100%; font-weight:bold; text-align:center;">DEFOILING DETAIL</td>
                </tr>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:5%;">Sr No</td>
                    <td style="width:15%;">Date</td>
                    <td style="width:15%;">No. of Tablets Rejection</td>
                    <td style="width:13%;">No. of Tablets Defoiling</td>
                    <td style="width:13%;">Status of Defoiling Tablet</td>
                    <td style="width:13%;">Done By</td>
                    <td style="width:13%;">Checked By (Prod.) /Date</td>
                    <td style="width:13%;">Ver. By (QA)/Date</td>
                </tr>
                <tr>
                    <td style="width:5%;"></td>
                    <td style="width:15%;"></td>
                    <td style="width:15%;"></td>
                    <td style="width:13%;"></td>
                    <td style="width:13%;"></td>
                    <td style="width:13%;"></td>
                    <td style="width:13%;"></td>
                    <td style="width:13%;"></td>
                </tr>
            </table>
            <br pagebreak="true"/>';
    
            $html.='<table cellpadding="3" border="1">
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:100%; font-weight:bold; text-align:center;">INPROCESS CHECKS - CARTON WEIGHING</td>
                </tr>
                <tr>
                    <td style="width:33%;">Standard Weight:</td>
                    <td style="width:34%;">Min. Weight:  </td>
                    <td style="width:33%;">Carton Pack Style:</td>
                </tr>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:10%;">Date</td>
                    <td style="width:10%;">Time</td>
                    <td style="width:10%;">No. of Cartons Checked</td>
                    <td style="width:50%;">Weight of Carton</td>
                    <td style="width:10%;">Weighing Done By</td>
                    <td style="width:10%;">Checked By /Date</td>
                </tr>
                <tr>
                    <td style="width:10%;"></td>
                    <td style="width:10%;"></td>
                    <td style="width:10%;"></td>
                    <td style="width:10%;"></td>
                    <td style="width:10%;"></td>
                    <td style="width:10%;"></td>
                    <td style="width:10%;"></td>
                    <td style="width:10%;"></td>
                    <td style="width:10%;"></td>
                    <td style="width:10%;"></td>
                </tr>
                <tr>
                    <td style="width:100%;"><b>Remarks:</b></td>
                </tr>
            </table>
            <br pagebreak="true"/>';        
            
            $html.='<table cellpadding="3" border="1">
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:100%; font-weight:bold; text-align:center;">ISHIPPER BOX WEIGHING RECORD</td>
                </tr>
                <tr>
                    <td style="width:100%;">Shipper box pack style :   </td>
                </tr>
                <tr>
                    <td style="width:50%;">Actual weight of filled shipper :</td>
                    <td style="width:50%;">Minimum weight :</td>
                </tr>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:5%;">Shipper Box No.</td>
                    <td style="width:15%;">Date</td>
                    <td style="width:10%;">Wt. of Shipper Box</td>
                    <td style="width:10%;">Weighing Done By</td>
                    <td style="width:10%;">Checked By</td>
                    <td style="width:10%;">Shipper Box No</td>
                    <td style="width:10%;">Date</td>
                    <td style="width:10%;">Wt. of Shipper Box</td>
                    <td style="width:10%;">Weighing Done By</td>
                    <td style="width:10%;">Checked By /Date</td>
                </tr>
                <tr>
                    <td style="width:5%;"></td>
                    <td style="width:15%;"></td>
                    <td style="width:10%;"></td>
                    <td style="width:10%;"></td>
                    <td style="width:10%;"></td>
                    <td style="width:10%;"></td>
                    <td style="width:10%;"></td>
                    <td style="width:10%;"></td>
                    <td style="width:10%;"></td>
                    <td style="width:10%;"></td>
                </tr>
                <tr>
                    <td style="width:100%;"><b>Remark:</b></td>
                </tr>
                <tr>
                    <td style="width:50%;"><b>Checked By (Packing Incharge) :</b>	</td>
                    <td style="width:50%;"><b>Verified By (QA Chemist) :</b> 	</td>
                </tr>
            </table>
            <br pagebreak="true"/>'; 
            
            $html.='<table cellpadding="3" border="1">
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:100%; font-weight:bold; text-align:center;">INCOMPLETE PACKING DETAIL</td>
                </tr>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:5%;">Sr No</td>
                    <td style="width:10%;">Date</td>
                    <td style="width:8%;">Received Drum</td>
                    <td style="width:8%;">Net Weight</td>
                    <td style="width:8%;">Batch Status</td>
                    <td style="width:8%;">Reason for Incomplete</td>
                    <td style="width:8%;">No. of Tablets Incomplete</td>
                    <td style="width:8%;">No. of Drums Incomplete</td>
                    <td style="width:8%;">Weight by</td>
                    <td style="width:9%;">Transfer to Quarantine</td>
                    <td style="width:10%;">Checked by (Prod)/Date</td>
                    <td style="width:10%;">Ver. by (QA) /Date</td>
                </tr>
                <tr>
                    <td style="width:5%;"></td>
                    <td style="width:10%;"></td>
                    <td style="width:8%;"></td>
                    <td style="width:8%;"></td>
                    <td style="width:8%;"></td>
                    <td style="width:8%;"></td>
                    <td style="width:8%;"></td>
                    <td style="width:8%;"></td>
                    <td style="width:8%;"></td>
                    <td style="width:9%;"></td>
                    <td style="width:10%;"></td>
                    <td style="width:10%;"></td>
                </tr>
                <tr>
                    <td style="width:100%;"><b>Remark:</b></td>
                </tr>
                <tr>
                    <td style="width:50%;"><b>Checked By (Packing Incharge) :</b>	</td>
                    <td style="width:50%;"><b>Verified By (QA Chemist) :</b> 	</td>
                </tr>
            </table>
            <br pagebreak="true"/>';
    
            $html.='<table cellpadding="3" border="1">
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:100%; font-weight:bold; text-align:center;">LINE ALLOCATION FOR SECONDARY PACKING</td>
                </tr>
                <tr>
                    <td style="width:100%;font-weight:bold;">Secondary Packing</td>
                </tr>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:10%;">Date</td>
                    <td style="width:5%;">Time</td>
                    <td style="width:8%;">Bottle labeled by</td>
                    <td style="width:8%;">Hologram adhered by</td>
                    <td style="width:8%;">Shrinking done by</td>
                    <td style="width:8%;">Visual inspection done by</td>
                    <td style="width:8%;">Carton packed by</td>
                    <td style="width:8%;">Packing insert added by</td>
                    <td style="width:8%;">Carton weighed by</td>
                    <td style="width:9%;">Shipper packed by</td>
                    <td style="width:10%;">Shipper weighed by</td>
                    <td style="width:10%;">Checked by (Prod) /Date</td>
                </tr>
                <tr>
                    <td style="width:10%;"></td>
                    <td style="width:5%;"></td>
                    <td style="width:8%;"></td>
                    <td style="width:8%;"></td>
                    <td style="width:8%;"></td>
                    <td style="width:8%;"></td>
                    <td style="width:8%;"></td>
                    <td style="width:8%;"></td>
                    <td style="width:8%;"></td>
                    <td style="width:9%;"></td>
                    <td style="width:10%;"></td>
                    <td style="width:10%;"></td>
                </tr>
            </table>
            <br pagebreak="true"/>';
            
            $html.='<table cellpadding="3" border="1">
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:100%; font-weight:bold; text-align:center;">INTERMEDIATE YIELD DATA SHEET</td>
                </tr>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:5%;">Sr.No.</td>
                    <td style="width:9%;">Stage</td>
                    <td style="width:9%;">Drum ID No.</td>
                    <td style="width:9%;">Net weight</td>
                    <td style="width:9%;">Yield</td>
                    <td style="width:9%;">Loss</td>
                    <td style="width:9%;">No of tablets loss</td>
                    <td style="width:9%;">Weight by operator</td>
                    <td style="width:9%;">Checked by (Prod)</td>
                    <td style="width:11%;">Ver.by (QA) /Date</td>
                    <td style="width:12%;">Date</td>
                </tr>';
                $k=1;
                $sql1 = "SELECT * FROM bmr_stages WHERE bmr_no='".$row["bmr_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                    $row1["yields"] = json_decode($row1["yields"]);
                    $yield=$row1["yields"];
                        for($i=0;$i<count($yield);$i++){
                            $yields=$yield[$i];
                            $html.='
                            <tr>
                                <td style="width:5%;">'.$k++.'</td>
                                <td style="width:9%;">'.$row1['stage'].'</td>
                                <td style="width:9%;"></td>
                                <td style="width:9%;">'.$yields->net.'</td>
                                <td style="width:9%;">'.$yields->yield.'</td>
                                <td style="width:9%;"></td>
                                <td style="width:9%;"></td>
                                <td style="width:9%;"></td>
                                <td style="width:9%;">'.$row1['completed_by'].'</td>
                                <td style="width:11%;"></td>
                                <td style="width:12%;">'.date('d-m-Y',strtotime($row1['completed_date'])).'</td>
                            </tr>';
                        }
                    }
                }
                $html.='
                <tr>
                    <td style="width:100%;">% yield = (Actual weight /Theoretical weight) x 100 </td>
                </tr>
                <tr>
                    <td style="width:100%;">Total loss of tablet T=heoretical weight-Actual weight * 1000 *	1000</td>
                </tr>
            </table>
            <br pagebreak="true"/>';
            
            $html.='<table cellpadding="3" border="1">
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:100%; font-weight:bold; text-align:center;">BATCH YIELD DATA SHEET</td>
                </tr>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:20%;">Sr.No.</td>
                    <td style="width:20%;">DESCRIPTION</td>
                    <td style="width:20%;">QUANTITY</td>
                    <td style="width:20%;">PACKING</td>
                    <td style="width:20%;">REMARKS</td>
                </tr>
                <tr>
                    <td style="width:20%;"></td>
                    <td style="width:20%;"></td>
                    <td style="width:20%;"></td>
                    <td style="width:20%;"></td>
                    <td style="width:20%;"></td>
                </tr>
                <tr>
                    <td style="width:33%;">Result : Report No.  </td>
                    <td style="width:34%;">Date</td>
                    <td style="width:33%;">Sub Standard/Standard Quality </td>
                </tr>
                <tr>
                    <td style="width:50%;">Date of Release </td>
                    <td style="width:50%;">SIGN OF Q.A. INCHARGE /Date :</td>
                </tr>
            </table>
            <br pagebreak="true"/>';
            
            $html.='<table cellpadding="3" border="1">
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:100%; font-weight:bold; text-align:center;">STEREO DISTRUCTION PAGE</td>
                </tr>
                <tr>
                    <td style="width:100%;">Reference Page No of other documents : </td>
                </tr>
            </table>
            <br pagebreak="true"/>';
            
            $html.='<table cellpadding="3" border="1">
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:100%; font-weight:bold; text-align:center;">SCRAP MATERIAL DETAIL</td>
                </tr>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:33%;">No Of Bags</td>
                    <td style="width:34%;">Weight Of Scrap</td>
                    <td style="width:33%;">Checked By</td>
                </tr>
                <tr>
                    <td style="width:33%;"></td>
                    <td style="width:34%;"></td>
                    <td style="width:33%;"></td>
                </tr>
            </table>
            <br pagebreak="true"/>';
            
            $html.='<table cellpadding="3" border="1">
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:100%; font-weight:bold; text-align:center;">Requisition Slip For Packed Product Testing</td>
                </tr>
                <tr>
                    <td style="width:100%;">Reference Page No of other documents :   </td>
                </tr>
            </table>
            <br pagebreak="true"/>';
            
            $html.='<table cellpadding="3" border="1">
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:100%; font-weight:bold; text-align:center;">Release Order - QA</td>
                </tr>
                <tr>
                    <td style="width:100%;">
                        <table>
                            <tr>
                                <td style="width:100%;">To whomsoever it may concern,</td>
                            </tr>
                            <tr>
                                <td style="width:100%;">I have checked all details of the batch, and release below stated product in market on following criteria:</td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="width:80%;">Criterion:</td>
                    <td style="width:20%;">Status</td>
                </tr>
                <tr>
                    <td style="width:80%;">1. All quality control tests of product have passed.</td>
                    <td style="width:20%;"></td>
                </tr>
                <tr>
                    <td style="width:80%;">2. Physical Verification and IPQA tests have been carried out and recorded satisfactorily.</td>
                    <td style="width:20%;"></td>
                </tr>
                <tr>
                    <td style="width:80%;">3. I have personally carried out physical verification of product and found satisfactory.</td>
                    <td style="width:20%;"></td>
                </tr>
                <tr>
                    <td style="width:80%;">4. All information in the batch processing record is true to the best of my knowledge.</td>
                    <td style="width:20%;"></td>
                </tr>
                <tr>
                    <td style="width:25%;">Product Name:</td>
                    <td style="width:75%;">'.$row['product_name'].'</td>
                </tr>
                <tr>
                    <td style="width:25%;">Packing:</td>
                    <td style="width:75%;"></td>
                </tr>
                <tr>
                    <td style="width:25%;">Batch No:</td>
                    <td style="width:75%;">'.$row['batch_no'].'</td>
                </tr>
                <tr>
                    <td style="width:25%;">Batch Size:</td>
                    <td style="width:75%;">'.$row['batch_size'].'</td>
                </tr>
                <tr>
                    <td style="width:25%;">Cleared Quantity :</td>
                    <td style="width:75%;"></td>
                </tr>
                <tr>
                    <td style="width:25%;">Description:</td>
                    <td style="width:75%;"></td>
                </tr>
                <tr>
                    <td style="width:25%;">Test Report Number:</td>
                    <td style="width:75%;"></td>
                </tr>
                <tr>
                    <td style="width:25%;">Test Report Date:</td>
                    <td style="width:75%;"></td>
                </tr>
                <tr>
                    <td style="width:100%;">Release Order Issued By :</td>
                </tr>
                <tr>
                    <td style="width:50%;">QA In-charge (Name & Signature)</td>
                    <td style="width:50%;">Date</td>
                </tr>
                <tr>
                    <td style="width:50%;"></td>
                    <td style="width:50%;"></td>
                </tr>
            </table>
            <table cellpadding="3" border="1">
                <tr>
                    <td style="width:33%;">Release Order Approved By:</td>
                    <td style="width:34%;"></td>
                    <td style="width:33%;"></td>
                </tr>
                <tr>
                    <td style="width:33%;">Date:</td>
                    <td style="width:34%;"></td>
                    <td style="width:33%;"></td>
                </tr>
                <tr>
                    <td style="width:33%;">Sign:</td>
                    <td style="width:34%;"></td>
                    <td style="width:33%;"></td>
                </tr>
                <tr>
                    <td style="width:33%;"></td>
                    <td style="width:34%;">Production In-Charge</td>
                    <td style="width:33%;">Plant Head</td>
                </tr>
            </table>
            <br pagebreak="true"/>';
            
            $html.='<table border="1" cellpadding="3">
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:100%; font-weight:bold; text-align:center;">POST PRODUCTION REVIEW</td>
                </tr>
                <tr>
                    <td style="width:100%;">The Complete Production Batch Record has been reviewed for completeness and accuracy. All pages are complete and all entries conform to Good Documentation Practices.</td>
                </tr>
                <tr>
                    <td style="width:25%;"></td>
                    <td style="width:25%;font-weight:bold;">Name</td>
                    <td style="width:25%;font-weight:bold;">Signature</td>
                    <td style="width:25%;font-weight:bold;">Date</td>
                </tr>
                <tr>
                    <td style="width:25%;font-weight:bold;">Quality Assurance</td>
                    <td style="width:25%;">'.$row['entry_by'].'</td>
                    <td style="width:25%;"></td>
                    <td style="width:25%;">'.$row['entry_date'].'</td>
                </tr>
            </table>
            <br pagebreak="true"/>';
            
            $html.='<table border="1" cellpadding="3">
                <tr>
                    <td style="width:100%;font-weight:bold;">Name of the Product : '.$row['product_name'].'</td>
                </tr>
                <tr>
                    <td style="width:100%;font-weight:bold;">Generic Name :'.$row['generic_name'].' </td>
                </tr>
                <tr>
                    <td style="width:100%;font-weight:bold;">Description :  </td>
                </tr>
                <tr>
                    <td style="width:100%;font-weight:bold;">Manufactured for :</td>
                </tr>
                <tr>
                    <td style="width:100%;font-weight:bold;">MFR No. : '.$row['mfr_no'].'</td>
                </tr>
                <tr>
                    <td style="width:100%;font-weight:bold;">Mfg. Lic. No. :'.$row['mfg_lic'].'</td>
                </tr>
                <tr>
                    <td style="width:100%;font-weight:bold;">Packing Size :</td>
                </tr>
                <tr>
                    <td style="width:100%;font-weight:bold;">Batch No. : '.$row['batch_no'].'</td>
                </tr>
                <tr>
                    <td style="width:50%;font-weight:bold;">Mfg. Date </td>
                    <td style="width:50%;font-weight:bold;">Exp. Date : </td>
                </tr>
            </table>';
            }
        }
        
        
                        
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('standard MFR.pdf', 'I');
        
    }

}

$conn->close();
?>