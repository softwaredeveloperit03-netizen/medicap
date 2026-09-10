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
    while($row = $result->fetch_assoc()) {
    	$string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
    	$string = explode("$",$string);
    	$_GET["emp_id"] = $string[0];
    	$_GET["department"] = $string[1];
    	break;
    }
    
    $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."')";
    $conn->query($sql);
    
    if ($_GET["type"] == "downloadBMRRecord") {
        //$_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp.php");
        $sql = "SELECT b.*, DATE(b.start_date) as start_date, p.product_name, p.grade, p.generic_name, p.dosage_form, p.shelf_life, p.label_claim FROM bmr b LEFT JOIN product p ON b.product_code=p.product_code WHERE b.status='COMPLETED' AND b.id='".$_GET["id"]."'";
        $result = $conn->query($sql);
        $row = $result->fetch_assoc();
        $_GET['product']=$row['product_name'];
        $_GET['batch_no']=$row['batch_no'];
        $_GET['batch_size']=$row['batch_size'];
        $_GET['bmr_no']=$row['bmr_no'];
            class MYPDF extends TCPDF {
                public function Header() {
                    $table.='
                    <style>td { border:solid 1px BCBBBA;}</style>
                    <table cellpadding="3">
                        <tr>
                             <td style="width:20%;">';
                                $this->Image('@'.file_get_contents('https://'.$_SERVER['SERVER_NAME'].'/gmptotal/upload/User/wastcost.png'),16,12,33);
                            $table.='
                            </td>
                            <td style="width:80%;text-align:center;font-weight:bold;">
                                <span style="font-family:times;font-size:17px;">WEST COAST PHARMACEUTICAL WORKS LTD</span><br>
                                <span style="font-size:9px;">Location:Opp Sola Bhagwat, Near Prasang Party Plot,,Near Meldi Mata Temple, Meldi Estate,,GOTA,AHMEDABAD  INDIA-382481.</span>
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
                                        <td style="width:25%;border:none;">'.$_GET['batch_size'].'</td>
                                    </tr>
                                    <tr>
                                        <td style="width:17%;font-weight:bold;border:none;">SLIP NO</td>
                                        <td style="width:3%;border:none;">:</td>
                                        <td style="width:30%;border:none;"></td>
                                        <td style="width:22%;font-weight:bold;border:none;">PAGE NO</td>
                                        <td style="width:3%;border:none;">:</td>
                                        <td style="width:25%;border:none;"></td>
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
                    $this->SetY(49); $this->SetFont('helvetica', '', 10); $this->Cell(0, 0, ''.$this->getAliasNumPage().' of '.$this->getAliasNbPages(), 0, false, 'R', 0, '', 0, false, 'T', 'M');
                }
                
                public function Footer() {
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
                }
            }
            $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING);
            $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
            $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
            $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
            $pdf->SetMargins(PDF_MARGIN_LEFT,65, PDF_MARGIN_RIGHT);
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
            
            $sql="SELECT w.*,p.product_name ,e.equipment_name FROM washWater w LEFT JOIN product p ON w.product_code=p.product_code LEFT JOIN equipment e ON w.equipment_code=e.equipment_code WHERE w.id='".$_GET['id']."'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    // $row["raw_materials"] = json_decode($row["raw_materials"]);
                    // $row_materials=$row["$row_material"];
                    // //for($i=1;$i<count($row_materials);$i++){
                    // $row_material=$row_materials->
                $html.='
                <table cellpadding="3" border="1">
                    <tr>
                        <td style="width:100%;font-weight:bold;text-align:center;">WASH WATER FOR R.M. DISPENSING</td>
                    </tr>
                    <tr>
                        <td style="width:33%;"><b>Date :</b>'.$row['sample_date'].'</td>
                        <td style="width:34%;"><b>Report No.:</b></td>
                        <td style="width:33%;"><b>Room No. :</b></td>
                    </tr>
                    <tr>
                        <td style="width:67%;">Previous Product Name:'.$row['product_name'].'</td>
                        <td style="width:33%;">Batch No.:'.$row['batch_no'].'</td>
                    </tr>
                    <tr>
                        <td style="width:67%;">Current Product Name : </td>
                        <td style="width:33%;">Batch No:</td>
                    </tr>
                    <tr>
                        <td style="width:33%;">Cleaning Done By : </td>
                        <td style="width:34%;">Cleaning Checked By Prod. /Date</td>
                        <td style="width:33%;">Sample Collected By Q.A /Date:</td>
                    </tr>
                    <tr>
                        <td rowspan="2" style="width:5%;font-weight:bold;">Sr.No :</td>
                        <td rowspan="2" style="width:20%;font-weight:bold;">Name Of Equipment</td>
                        <td rowspan="2" style="width:15%;font-weight:bold;">Cleaning Done By</td>
                        <td style="width:30%;font-weight:bold;">Cleaning Visually verification</td>
                        <td rowspan="2" style="width:15%;font-weight:bold;">Q.C. Result</td>
                        <td rowspan="2" style="width:15%;font-weight:bold;">Remarks</td>
                    </tr>
                    <tr>
                        <td style="width:15%;font-weight:bold;">By Prod.</td>
                        <td style="width:15%;font-weight:bold;">By QA</td>
                    </tr>
                    <tr>
                        <td style="width:5%;">'.$i++.'</td>
                        <td style="width:20%;">'.$row['equipment_name'].'</td>
                        <td style="width:15%;">'.$row['clean_by'].'</td>
                        <td style="width:15%;">'.$row['entry_by'].'</td>
                        <td style="width:15%;">'.$row['sample_collect_by'].'</td>
                        <td style="width:15%;">'.$row['result'].'</td>
                        <td style="width:15%;">'.$row['remark'].'</td>
                    </tr>
                </table>
                <br pagebreak="true"/>';
                }
            }
            
            $html.='
            <table cellpadding="3" border="1">
                <tr>
                    <td style="width:100%;font-weight:bold;text-align:center;">WASH WATER FOR GRANULATION</td>
                </tr>
                <tr>
                    <td style="width:33%;">Date :</td>
                    <td style="width:34%;">Report No.:</td>
                    <td style="width:33%;">Room No. :</td>
                </tr>
                <tr>
                    <td style="width:67%;">Previous Product Name:</td>
                    <td style="width:33%;">Batch No.:</td>
                </tr>
                <tr>
                    <td style="width:67%;">Current Product Name : </td>
                    <td style="width:33%;">Batch No:</td>
                </tr>
                <tr>
                    <td style="width:33%;">Cleaning Done By : </td>
                    <td style="width:34%;">Cleaning Checked By Prod. /Date</td>
                    <td style="width:33%;">Sample Collected By Q.A /Date:</td>
                </tr>
                <tr>
                    <td rowspan="2" style="width:5%;">Sr.No :</td>
                    <td rowspan="2" style="width:20%;">Name Of Equipment</td>
                    <td rowspan="2" style="width:15%;">Cleaning Done By</td>
                    <td style="width:30%;">Cleaning Visually verification</td>
                    <td rowspan="2" style="width:15%;">Q.C. Result</td>
                    <td rowspan="2" style="width:15%;">Remarks</td>
                </tr>
                <tr>
                    <td style="width:15%;">By Prod.</td>
                    <td style="width:15%;">By QA</td>
                </tr>
                <tr>
                    <td style="width:5%;"></td>
                    <td style="width:20%;"></td>
                    <td style="width:15%;"></td>
                    <td style="width:15%;"></td>
                    <td style="width:15%;"></td>
                    <td style="width:15%;"></td>
                    <td style="width:15%;"></td>
                </tr>
            </table>
            <br pagebreak="true"/>';
            
            $html.='
            <table cellpadding="3" border="1">
                <tr>
                    <td style="width:100%;font-weight:bold;text-align:center;">IN PROCESS QC SLIP (GRANULES)</td>
                </tr>
                 <tr>
                    <td style="width:100%;font-weight:bold;">TO BE FILLED BY QA</td>
                </tr>
                <tr>
                    <td style="width:100%;">Date:</td>
                </tr>
                <tr>
                    <td style="width:100%;">The below mentioned  sample is drawn for analysis as per the following detail</td>
                </tr>
                <tr>
                    <td style="width:25%;">Qty of Samples</td>
                    <td style="width:25%;"></td>
                    <td style="width:25%;">Detail Of Analysis as per</td>
                    <td style="width:25%;">SPECIFICATION</td>
                </tr>
                <tr>
                    <td style="width:25%;">Mfg.Licence No:</td>
                    <td style="width:25%;"></td>
                    <td style="width:25%;">Average Weight of Tablet No:</td>
                    <td style="width:25%;"></td>
                </tr>
                <tr>
                    <td style="width:25%;">Containers:</td>
                    <td style="width:75%;"></td>
                </tr>
                
                <tr>
                    <td style="width:100%;font-weight:bold;">Composition</td>
                </tr>
                <tr>
                    <td style="width:100%;">
                        <table cellpadding="3">
                            <tr>
                                <td style="width:100%;">EACH UNCOATED TABLET CONTAINS:</td>
                            </tr>
                            <tr>
                                <td style="width:100%;">CALCIUM (CALCIUM CARBONATE)......600MG </td>
                            </tr>
                            <tr>
                                <td style="width:100%;">VITAMIN D (AS D3 400IU)..........10MCG</td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="width:50%;"><b>Sign of Q.A. Chemist :</b></td>
                    <td style="width:50%;"><b>Date:</b></td>
                </tr>
            </table>
            <div></div>
            <table cellpadding="3" border="1" >
                <tr>
                    <td style="width:100%;text-align:center;">TO BE FILLED BY QC</td>
                </tr>
                <tr>
                    <td style="width:100%;">
                        <table>
                            <tr>
                                <td style="width:100%; text-align:center;">WEST COAST PHARMACEUTICAL WORKS LTD</td>
                            </tr>
                            <tr>
                                <td style="width:100%;">Department:</td>
                            </tr>
                             <tr>
                                <td style="width:100%;">PROD.NAME:</td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="width:100%;">
                        <table>
                            <tr>
                                <td style="width:10%;font-weight:bold;">Batch No:</td>
                                <td style="width:10%;"></td>
                                <td style="width:10%;font-weight:bold;">Batch Size:</td>
                                <td style="width:10%;"></td>
                                <td style="width:15%;font-weight:bold;">MFG.DT:</td>
                                <td style="width:15%;"></td>
                                <td style="width:15%;font-weight:bold;">EXP.DT:</td>
                                <td style="width:15%;"></td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="width:100%;text-align:center;">IN PROCESS RESULT</td>
                </tr>
                <tr>
                    <td style="width:100%;">The sample stated is RELEASED/NOT RELEASED as per the details given below :</td>
                </tr>
                <tr>
                    <td style="width:25%;">ASSAY</td>
                    <td style="width:25%;">:</td>
                    <td style="width:25%;">LOD/WATER MOISTURE CONTENT</td>
                    <td style="width:25%;">:</td>
                </tr>
                <tr>
                    <td style="width:25%;">Av.Weight of Tablet:</td>
                    <td style="width:75%;"></td>
                </tr>
                <tr>
                    <td style="width:25%;">Sign of Q.C. Chemist</td>
                    <td style="width:25%;"></td>
                    <td style="width:25%;">Sign of Q.C. Incharge</td>
                    <td style="width:25%;"></td>
                </tr>
                <tr>
                    <td style="width:25%;">Date</td>
                    <td style="width:25%;"></td>
                    <td style="width:25%;">Date</td>
                    <td style="width:25%;"></td>
                </tr>
                <tr>
                    <td style="width:100%;">Released For Compression:</td>
                </tr>
            </table>
            <br pagebreak="true"/>';
            
            $html.='
            <table cellpadding="3" border="1">
                <tr>
                    <td style="width:100%;font-weight:bold;text-align:center;">WASH WATER FOR COMPRESSION</td>
                </tr>
                <tr>
                    <td style="width:33%;">Date :</td>
                    <td style="width:34%;">Report No.:</td>
                    <td style="width:33%;">Room No. :</td>
                </tr>
                <tr>
                    <td style="width:67%;">Previous Product Name:</td>
                    <td style="width:33%;">Batch No.:</td>
                </tr>
                <tr>
                    <td style="width:67%;">Current Product Name : </td>
                    <td style="width:33%;">Batch No:</td>
                </tr>
                <tr>
                    <td style="width:33%;">Cleaning Done By : </td>
                    <td style="width:34%;">Cleaning Checked By Prod. /Date</td>
                    <td style="width:33%;">Sample Collected By Q.A /Date:</td>
                </tr>
                <tr>
                    <td rowspan="2" style="width:5%;">Sr.No :</td>
                    <td rowspan="2" style="width:20%;">Name Of Equipment</td>
                    <td rowspan="2" style="width:15%;">Cleaning Done By</td>
                    <td style="width:30%;">Cleaning Visually verification</td>
                    <td rowspan="2" style="width:15%;">Q.C. Result</td>
                    <td rowspan="2" style="width:15%;">Remarks</td>
                </tr>
                <tr>
                    <td style="width:15%;">By Prod.</td>
                    <td style="width:15%;">By QA</td>
                </tr>
                <tr>
                    <td style="width:5%;"></td>
                    <td style="width:20%;"></td>
                    <td style="width:15%;"></td>
                    <td style="width:15%;"></td>
                    <td style="width:15%;"></td>
                    <td style="width:15%;"></td>
                    <td style="width:15%;"></td>
                </tr>
            </table>
            <br pagebreak="true"/>';
            
            $html.='
            <table cellpadding="3" border="1">
                <tr>
                    <td style="width:100%; text-align:center;">IN PROCESS QC SLIP (COMPRESSED TABLET)</td>
                </tr>
                <tr>
                    <td style="width:100%;text-align:center;">TO BE FILLED BY QA DEPATRMENT</td>
                </tr>
                <tr>
                    <td style="width:100%;">Date</td>
                </tr>
                <tr>
                    <td style="width:100%;">The below mentioned  sample is drawn for analysis as per the following detail</td>
                </tr>
                <tr>
                    <td style="width:25%;">Qty of Samples</td>
                    <td style="width:25%;"></td>
                    <td style="width:25%;">Detail Of Analysis as per</td>
                    <td style="width:25%;">SPECIFICATION</td>
                </tr>
                <tr>
                    <td style="width:25%;">Mfg.Licence No</td>
                    <td style="width:25%;"></td>
                    <td style="width:25%;">Average Weight of Tablet</td>
                    <td style="width:25%;"></td>
                </tr>
                <tr>
                    <td style="width:25%;">No of Containers:</td>
                    <td style="width:75%;"></td>
                </tr>
                <tr>
                    <td style="width:100%;text-align:center;">TO BE FILLED BY QC DEPATRMENT </td>
                </tr>
                <tr>
                    <td style="width:100%;">WEST COAST PHARMACEUTICAL WORKS </td>
                </tr>
                <tr>
                    <td style="width:100%;">
                        <table>
                            <tr>
                                <td style="width:100%;">Department:</td>
                            </tr>
                            <tr>
                                <td style="width:100%;">PROD. NAME :</td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="width:100%;">
                        <table>
                            <tr>
                                <td style="width:10%;font-weight:bold;">Batch No:</td>
                                <td style="width:10%;"></td>
                                <td style="width:10%;font-weight:bold;">Batch Size:</td>
                                <td style="width:10%;"></td>
                                <td style="width:15%;font-weight:bold;">MFG.DT:</td>
                                <td style="width:15%;"></td>
                                <td style="width:15%;font-weight:bold;">EXP.DT:</td>
                                <td style="width:15%;"></td>
                            </tr>
                        </table>
                    </td>
                </tr>
                 <tr>
                    <td style="width:100%;text-align:center;">IN PROCESS RESULT</td>
                </tr>
                <tr>
                    <td style="width:100%;">The sample stated is RELEASED/NOT RELEASED as per the details given below :</td>
                </tr>
                <tr>
                    <td style="width:25%;">ASSAY</td>
                    <td style="width:75%;">:</td>
                </tr>
                <tr>
                    <td style="width:25%;">Av.Weight of Tablet:</td>
                    <td style="width:25%;"></td>
                    <td style="width:25%;">DT:</td>
                    <td style="width:25%;"></td>
                </tr>
                <tr>
                    <td style="width:25%;">Hardness</td>
                    <td style="width:25%;"></td>
                    <td style="width:25%;">Thickness </td>
                    <td style="width:25%;"></td>
                </tr>
                <tr>
                    <td style="width:25%;">Diameter/ Width/ Length</td>
                    <td style="width:25%;"></td>
                    <td style="width:25%;">Friability</td>
                    <td style="width:25%;"></td>
                </tr>
                <tr>
                    <td style="width:25%;">Test Report No</td>
                    <td style="width:25%;"></td>
                    <td style="width:25%;">Test Report Date</td>
                    <td style="width:25%;"></td>
                </tr>
                <tr>
                    <td style="width:25%;">No of Release Slip Issued</td>
                    <td style="width:75%;"></td>
                </tr>
                <tr>
                    <td style="width:25%;">Sign of Q.C. Chemist  </td>
                    <td style="width:25%;"></td>
                    <td style="width:25%;">Sign of Q.C. Incharge   </td>
                    <td style="width:25%;"></td>
                </tr>
                <tr>
                    <td style="width:25%;">Date</td>
                    <td style="width:25%;"></td>
                    <td style="width:25%;">Date</td>
                    <td style="width:25%;"></td>
                </tr>
                <tr>
                    <td style="width:100%;">Released For Coating/ Packing</td>
                </tr>
            </table>
            <br pagebreak="true"/>
            ';
            
            $html.='
            <table cellpadding="3" border="1">
                <tr>
                    <td style="width:100%; text-align:center;font-weight:bold;"> In Process  QC Slip (Technical Information Sheet)</td>
                </tr>
                <tr>
                    <td style="width:25%;">TI Sheet No</td>
                    <td style="width:25%;"></td>
                    <td style="width:25%;">Date</td>
                    <td style="width:25%;"></td>
                </tr>
                <tr>
                    <td style="width:25%;">Product Name</td>
                    <td style="width:25%;"></td>
                    <td style="width:25%;">Batch No:</td>
                    <td style="width:25%;"></td>
                </tr>
                <tr>
                    <td style="width:25%;">Lot No</td>
                    <td style="width:25%;"></td>
                    <td style="width:25%;">Specification No</td>
                    <td style="width:25%;"></td>
                </tr>
                <tr>
                    <td style="width:25%;">Stage</td>
                    <td style="width:25%;"></td>
                    <td style="width:25%;">Sample Qty</td>
                    <td style="width:25%;"></td>
                </tr>
                <tr>
                    <td style="width:25%">Sampled By</td>
                    <td style="width:25%;"></td>
                    <td style="width:25%;">Received In Qc By</td>
                    <td style="width:25%;"></td>
                </tr>
                <tr>
                    <td style="width:15%;">Date</td>
                    <td style="width:15%;"></td>
                    <td style="width:10%;">Time</td>
                    <td style="width:10%;"></td>
                    <td style="width:15%;">Date</td>
                    <td style="width:15%;"></td>
                    <td style="width:10%;">Time</td>
                    <td style="width:10%;"></td>
                </tr>
            </table>
            <div></div>
            <table cellpadding="3" border="1">
                <tr>
                    <td style="width:100%;text-align:center;font-weight:bold;">QC Result</td>
                </tr>
                <tr>
                    <td style="width:15%;">Stage</td>
                    <td style="width:16%;">Test</td>
                    <td style="width:15%;">Specification</td>
                    <td style="width:15%;">Limit</td>
                    <td style="width:15%;">Result</td>
                    <td style="width:12%;">Analysis By</td>
                    <td style="width:12%;">Check By</td>
                </tr>
                <tr>
                    <td style="width:15%;"></td>
                    <td style="width:16%;"></td>
                    <td style="width:15%;"></td>
                    <td style="width:15%;"></td>
                    <td style="width:15%;"></td>
                    <td style="width:12%;"></td>
                    <td style="width:12%;"></td>
                </tr>
                <tr>
                    <td style="width:33%;">Production on</td>
                    <td style="width:34%;">Check By QA</td>
                    <td style="width:33%;">Check By QC</td>
                </tr>
                <tr>
                    <td style="width:33%;"></td>
                    <td style="width:34%;"></td>
                    <td style="width:33%;"></td>
                </tr>
            </table>
            <br pagebreak="true"/>';
            
            $html.='
            <table cellpadding="3" border="1">
                <tr>
                    <td style="width:100%;font-weight:bold;text-align:center;">WASH WATER FOR COATING</td>
                </tr>
                <tr>
                    <td style="width:33%;">Date :</td>
                    <td style="width:34%;">Report No.:</td>
                    <td style="width:33%;">Room No. :</td>
                </tr>
                <tr>
                    <td style="width:67%;">Previous Product Name:</td>
                    <td style="width:33%;">Batch No.:</td>
                </tr>
                <tr>
                    <td style="width:67%;">Current Product Name : </td>
                    <td style="width:33%;">Batch No:</td>
                </tr>
                <tr>
                    <td style="width:33%;">Cleaning Done By : </td>
                    <td style="width:34%;">Cleaning Checked By Prod. /Date</td>
                    <td style="width:33%;">Sample Collected By Q.A /Date:</td>
                </tr>
                <tr>
                    <td rowspan="2" style="width:5%;">Sr.No :</td>
                    <td rowspan="2" style="width:20%;">Name Of Equipment</td>
                    <td rowspan="2" style="width:15%;">Cleaning Done By</td>
                    <td style="width:30%;">Cleaning Visually verification</td>
                    <td rowspan="2" style="width:15%;">Q.C. Result</td>
                    <td rowspan="2" style="width:15%;">Remarks</td>
                </tr>
                <tr>
                    <td style="width:15%;">By Prod.</td>
                    <td style="width:15%;">By QA</td>
                </tr>
                <tr>
                    <td style="width:5%;"></td>
                    <td style="width:20%;"></td>
                    <td style="width:15%;"></td>
                    <td style="width:15%;"></td>
                    <td style="width:15%;"></td>
                    <td style="width:15%;"></td>
                    <td style="width:15%;"></td>
                </tr>
            </table>
            <br pagebreak="true"/>';
            
            $html.='
            <table cellpadding="3" border="1">
                <tr>
                    <td style="width:100%; text-align:center;font-weight:bold;"> In Process  QC Slip (COATED TABLET)</td>
                </tr>
                <tr>
                    <td style="width:25%;">TI Sheet No</td>
                    <td style="width:25%;"></td>
                    <td style="width:25%;">Date</td>
                    <td style="width:25%;"></td>
                </tr>
                <tr>
                    <td style="width:25%;">Product Name</td>
                    <td style="width:25%;"></td>
                    <td style="width:25%;">Batch No:</td>
                    <td style="width:25%;"></td>
                </tr>
                <tr>
                    <td style="width:25%;">Lot No</td>
                    <td style="width:25%;"></td>
                    <td style="width:25%;">Specification No</td>
                    <td style="width:25%;"></td>
                </tr>
                <tr>
                    <td style="width:25%;">Stage</td>
                    <td style="width:25%;"></td>
                    <td style="width:25%;">Sample Qty</td>
                    <td style="width:25%;"></td>
                </tr>
                <tr>
                    <td style="width:25%">Sampled By</td>
                    <td style="width:25%;"></td>
                    <td style="width:25%;">Received In Qc By</td>
                    <td style="width:25%;"></td>
                </tr>
                <tr>
                    <td style="width:15%;">Date</td>
                    <td style="width:15%;"></td>
                    <td style="width:10%;">Time</td>
                    <td style="width:10%;"></td>
                    <td style="width:15%;">Date</td>
                    <td style="width:15%;"></td>
                    <td style="width:10%;">Time</td>
                    <td style="width:10%;"></td>
                </tr>
            </table>
            <div></div>
            <table cellpadding="3" border="1">
                <tr>
                    <td style="width:100%;text-align:center;font-weight:bold;">QC Result</td>
                </tr>
                <tr>
                    <td style="width:15%;">Stage</td>
                    <td style="width:16%;">Test</td>
                    <td style="width:15%;">Specification</td>
                    <td style="width:15%;">Limit</td>
                    <td style="width:15%;">Result</td>
                    <td style="width:12%;">Analysis By</td>
                    <td style="width:12%;">Check By</td>
                </tr>
                <tr>
                    <td style="width:15%;"></td>
                    <td style="width:16%;"></td>
                    <td style="width:15%;"></td>
                    <td style="width:15%;"></td>
                    <td style="width:15%;"></td>
                    <td style="width:12%;"></td>
                    <td style="width:12%;"></td>
                </tr>
                <tr>
                    <td style="width:33%;">Production on</td>
                    <td style="width:34%;">Check By QA</td>
                    <td style="width:33%;">Check By QC</td>
                </tr>
                <tr>
                    <td style="width:33%;"></td>
                    <td style="width:34%;"></td>
                    <td style="width:33%;"></td>
                </tr>
            </table>
            <br pagebreak="true"/>';
            
            $html.='
            <table cellpadding="3" border="1">
                <tr>
                    <td style="width:100%; text-align:center;">STEREO IMPRINT PAGE</td>
                </tr>
                <tr>
                    <td style="width:100%;">INSTRUCTIONS:</td>
                </tr>
                <tr>
                    <td style="width:100%;">
                        <table>
                            <td>1.Open all stereo to be checked and make impression of same in below given boxes before using in packing stage.</td>
                            <td>2.All stereo impressions must be checked by production chemists and approved by QA chemists.</td>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="width:50%;"></td>
                    <td style="width:50%;"></td>
                </tr>
            </table>
            <br pagebreak="true"/>';
            
            $html.='
            <table cellpadding="3" border="1">
                <tr>
                    <td style="width:100%;">Requisition Slip For Packed Product Testing</td>
                </tr>
                <tr>
                    <td style="width:100%;">
                        <table>
                            <tr>
                                <td>To,</td>
                            </tr>
                            <tr>
                                <td>Q.C Department</td>
                            </tr>
                            <tr>
                                <td>Please find herewith a retain and final packing sample for test under:</td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="width:10%;">Sr</td>
                    <td style="width:30%;">Product Name</td>
                    <td style="width:20%;">Batch No</td>
                    <td style="width:20%;">Packing</td>
                    <td style="width:20%;">Pack Qty</td>
                </tr>
                <tr>
                    <td style="width:10%;"></td>
                    <td style="width:30%;"></td>
                    <td style="width:20%;"></td>
                    <td style="width:20%;"></td>
                    <td style="width:20%;"></td>
                </tr>
                <tr>
                    <td style="width:100%;">
                        <table>
                            <tr>
                                <td>Please analys finish product and final packing and issue release slip for dispatch. </td>
                            </tr>
                            <tr>
                                <td>Date :</td>
                            </tr>
                            <tr>
                                <td style="width:33%;">Packing supervisor Sign /Date</td>
                                <td style="width:33%;">Prod. In-Charge Sign /Date</td>
                                <td style="width:33%;">Q.A.Chemist Sign /Date</td>
                            </tr>
                            <tr>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
            <div></div>
            <table cellpadding="3" border="1">
                <tr>
                    <td style="width:100%;">Final Finish Product Release Slip</td>
                </tr>
                <tr>
                    <td style="width:100%;">
                        <table>
                            <tr>
                                <td style="width:100%;">To,</td>
                            </tr>
                            <tr>
                                <td style="width:100%;">Production Department</td>
                            </tr>
                            <tr>
                                <td style="width:100%;">Please find the result of Finish product sample as under</td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="width:25%;">Array</td>
                    <td style="width:75%;"></td>
                </tr>
                <tr>
                    <td style="width:25%;">Identification</td>
                    <td style="width:75%;"></td>
                </tr>
                <tr>
                    <td style="width:25%;">PH</td>
                    <td style="width:75%;"></td>
                </tr>
                <tr>
                    <td style="width:25%;">Final Result</td>
                    <td style="width:75%;"></td>
                </tr>
                <tr>
                    <td style="width:50%;">Q. C. Analysis Chemit Sign /Date</td>
                    <td style="width:50%;">Q. C. in-Charge Sign /Date</td>
                </tr>
                <tr>
                    <td style="width:50%;"></td>
                    <td style="width:50%;"></td>
                </tr>
            </table>
           <br pagebreak="true"/>';
           
           $html.='
           <table cellpadding="3" border="1">
                <tr>
                    <td style="width:100%; text-align:center;font-weight:bold;">PACKING MATERIAL REQUISITION SLIP</td>
                </tr>
                <tr>
                    <td style="width:20%;font-weight:bold;">Packing Material</td>
                    <td style="width:20%;font-weight:bold;">Quantity Issued</td>
                    <td style="width:20%;font-weight:bold;">Quantity Used</td>
                    <td style="width:20%;font-weight:bold;">Quantity Returned</td>
                    <td style="width:20%;font-weight:bold;">Quantity Destroyed</td>
                </tr>
                <tr>
                    <td style="width:20%;"></td>
                    <td style="width:20%;"></td>
                    <td style="width:20%;"></td>
                    <td style="width:20%;"></td>
                    <td style="width:20%;"></td>
                </tr>
                <tr>
                    <td style="width:25%;">Packing Supervisor</td>
                    <td style="width:25%;"></td>
                    <td style="width:25%;">Sign /Date : </td>
                    <td style="width:25%;"></td>
                </tr>
                <tr>
                    <td style="width:25%;">Entry Done In Computer By</td>
                    <td style="width:25%;"></td>
                    <td style="width:25%;">Sign /Date : </td>
                    <td style="width:25%;"></td>
                </tr>
                <tr>
                    <td style="width:100%;">Mode of destruction:-</td>
                </tr>
                <tr>
                    <td style="width:33%;">Destruction carried out under the supervision of :-</td>
                    <td style="width:34%;">Production</td>
                    <td style="width:33%;">Quality Assurance</td>
                </tr>
                <tr>
                    <td style="width:33%;"></td>
                    <td style="width:34%;"></td>
                    <td style="width:33%;"></td>
                </tr>
                <tr>
                    <td style="width:33%;">Date of destruction:-</td>
                    <td style="width:34%;">Sign /Date:</td>
                    <td style="width:33%;">Sign /Date:</td>
                </tr>
                <tr>
                    <td style="width:33%;"></td>
                    <td style="width:34%;"></td>
                    <td style="width:33%;"></td>
                </tr>
           </table>
           ';
            
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('bmr.pdf', 'I');
    }
}
?>