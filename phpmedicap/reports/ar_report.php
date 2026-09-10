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
    

if($result->num_rows > 0) {
    while($row = $result->fetch_assoc()){
	    $string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
	    $string = explode("$",$string);
	    $_GET["emp_id"] = $string[0];
	    $_GET["department"] = $string[1];
	    break;
    }
}

    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    
    if ($_GET["type"]=="getStock") {
        if (!isset($_GET["material_name"])) {
            $_GET["material_name"] = "";
        }
        $sql = "SELECT s.*, m.material_type, m.material_subtype, m.grade, m.material_name, v.vendor_name FROM stock_book s LEFT JOIN material m ON s.material_code=m.material_code LEFT JOIN vendor v ON s.vendor_no=v.vendor_no WHERE s.user_no='".$_GET["user_no"]."' AND m.material_type='Raw Material' AND s.material_code LIKE '%".$_GET["material_code"]."%' AND s.vendor_no LIKE '%".$_GET["vendor_no"]."' AND s.status LIKE '%".$_GET["status"]."' AND m.material_name LIKE '%".$_GET["material_name"]."%'AND m.material_subtype LIKE '%".$_GET["material_subtype"]."%'AND m.grade LIKE '%".$_GET["grade"]."%' ORDER BY s.grn_no";
    	//echo $sql;
    	$result = $conn->query($sql);
    	$output = Array();
    	if($result->num_rows > 0){
    		while ($row = $result->fetch_assoc()) {
    		    $output[] = $row;
    		}
    	}
    	echo json_encode($output);
    }else if ($_GET["type"]=="getAllStock") {
        if (!isset($_GET["material_name"])) {
            $_GET["material_name"] = "";
        }
        $sql = "SELECT s.*, m.material_type, m.material_subtype, m.grade, m.material_name, v.vendor_name FROM stock_book s LEFT JOIN material m ON s.material_code=m.material_code LEFT JOIN vendor v ON s.vendor_no=v.vendor_no WHERE s.user_no='".$_GET["user_no"]."' AND m.material_type='Raw Material' AND s.material_code LIKE '%".$_GET["material_code"]."%' AND s.vendor_no LIKE '%".$_GET["vendor_no"]."' AND s.status LIKE '%".$_GET["status"]."' AND m.material_name LIKE '%".$_GET["material_name"]."%'";
    	$result = $conn->query($sql);
    	$output = Array();
    	if($result->num_rows > 0){
    		while ($row = $result->fetch_assoc()) {
    		    $output[] = $row;
    		}
    	}
    	echo json_encode($output);
    }if ($_GET["type"]=="getApprovedStock") {
        if (!isset($_GET["material_name"])) {
            $_GET["material_name"] = "";
        }
        $sql = "SELECT s.*, m.material_type, m.material_subtype, m.grade, m.material_name, v.vendor_name FROM stock_book s LEFT JOIN material m ON s.material_code=m.material_code LEFT JOIN vendor v ON s.vendor_no=v.vendor_no WHERE s.user_no='".$_GET["user_no"]."' AND m.material_type='Raw Material' AND s.status='Approved'ORDER BY id DESC";
    	$result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["batches"] = json_decode($row["batches"]);
                $row["receiving_details"] = json_decode($row["receiving_details"]);
                $row["weighing_details"] = json_decode($row["weighing_details"]);
                $row["grn_details"] = json_decode($row["grn_details"]);
                
                $output1 = array();
                $sql1 = "SELECT material_code, grade FROM material WHERE material_name='".$row["material_name"]."' AND material_code!='".$row["material_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row["grades"] = $output1;
                $output[] = $row;
            }
        }
    	echo json_encode($output);
    }
    
   else if($_GET['type'] == "ARReportlog"){
       $_GET['filename'] = 'A. R. Report'; $_GET['pdftype'] = 'onlyheader';  include("../../pdfimp2.php");
        $html= '
        <h2 style="text-align:center">A. R. Report</h2>
        <style>td { border:solid 1px BCBBBA;}</style>
        <table cellpadding="5">
            <thead>
                <tr>
                    <td style="background-color:#DDDAD9; width:100%; text-align:center;"><b>A. R. Report</b></td>
                </tr>
                <tr><td style="border:none;"></td></tr>
                <tr style="background-color:#DDDAD9;">
                    <td style="width:15%;">A R No.</td>
                    <td style="width:15%;">Sampling No</td>
                    <td style="width:20%;">Specification No</td>
                    <td style="width:20%;">Material Name</td>
                    <td style="width:15%;">Material Code</td>
                    <td style="width:15%;">Material Grade</td>
                   
                </tr>
            </thead>
            <tbody>';
            $sql = "SELECT t.*, m.material_type, m.material_subtype, m.material_name, m.grade FROM testing t LEFT JOIN material m ON t.material_code=m.material_code WHERE t.status='approve' AND m.material_type='Raw Material'";
            $output = Array();
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    
                  
                $html.='
                    <tr>
                        <td style="width:15%;">'.$row['ar_no'].'</td>
                        <td style="width:15%">'.$row['sampling_no'].'</td>
                        <td style="width:20%">'.$row['specification_no'].'</td>
                        <td style="width:20%">'.$row['material_name'].'</td>
                        <td style="width:15%">'.$row['material_code'].'</td>
                        <td style="width:15%">'.$row['grade'].'</td>
                       
                    </tr>';
                }
            }
            $html.='
            </tbody>
        </table>
        <div></div>';
        EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('coa.pdf', 'I');
        
        
   }else if ($_GET["type"] == "ARReport_new") {
          $_GET['filename'] = ' '; $_GET['pdftype']= 'onlyheader';  include('../pdfimp2.php');
       
        
                
        $html.='
        <h2 style="text-align:center">AR REPORT</h2>
        <table cellpadding="1" border="0.1">
        <tr>
        <td style="width=100%;text-align:center;"><b>FINISHED PRODUCT ANALYTICAL REPORT</b></td>
        </tr>
        </table>
           <table cellpadding="3" border="0.1">
          <tr>
         <td style="width:25%; text-align:cenetr;" rowspan="2"> </td>
          <td style="width:35%;text-align:center;" rowspan="2" ><b>Product Name:<br>NOVOMIX<br>10001</b></td>
          <td style="width:20%;"><b> Page No.:</b></td>
           <td style="width:20%;"></td>
          </tr>
          <tr>
               <td style="width:20%;"><b>Reference.</b></td>
            <td style="width:20%;"></td>
          </tr>
          
          <tr>
          <td style="width:25%;"><b>Department:</b></td>
         <td style="width:35%;"><b>Colour:</b></td>
         <td style="width:20%;"><b>Ref. ARD No.:</b></td>
           <td style="width:20%;">FP/NOVOMATR/10001/102</td>
          </tr>
          
          <tr>
          <td style="width:25%;"><b>ART No.:</b></td>
           <td style="width:20%;">FP/NOVOMATR/10001/102</td>
           <td style="width:35%;"><b>Supersedes No.:</b></td>
           <td style="width:20%;"></td>
          </tr>
           <tr>
          <td style="width:25%;"><b>Effective Date:</b></td>
           <td style="width:20%;"></td>
           <td style="width:35%;"><b>Review Date:</b></td>
           <td style="width:20%;"></td>
          </tr>
          <tr>
          <td style="width:25%;"><b>Ref.SOP No.:</b></td>
           <td style="width:20%;">SOP/QC/030</td>
           <td style="width:35%;"><b>Annexure No.:</b></td>
           <td style="width:20%;">A/SOP/QC/030/15-01</td>
          </tr>
         </table><div></div>';
         
         $html.='
         <table cellpadding="3" border="0.1">
         <tr>
         <td style="width:45%;">Batch No. :</td>
         <td style="width:45%;">Batch Size : '.$row[''].'</td>
         </tr>
         <tr>
         <td style="width:45%;">Date of Mfg :</td>
         <td style="width:45%;">Retest Date/Shelf Life :</td>
         </tr>
         <tr>
         <td style="width:45%;">A.R No. : '.$row['ar_no'].'</td>
         <td style="width:45%;">Sampled By :</td>
         </tr>
         <tr>
         <td style="width:45%;">Sampled Qty. :</td>
         <td style="width:45%;">Date of Relese :</td>
         </tr>
         </table>
         <table>
         <h4 >Qualitative Fourmula:</h4>
         <tr>
         <ol>
         <li > Hyderoxypropyl methyl cellulose IP</li>
         <li > Polyethylene Glycol USP<li>
          <li>Talc IP</li>
          <li > Titanium dioxide IP</li>
        </ol>
          </tr>
        </table>';
       
       
        $html.='<table cellpadding="5" border="0.1">
        <tr>
        <th style="width:10%;"><b>Sr No.</b></th>
         <th style="width:25%;"><b>Test</b></th>
          <th style="width:25%;"><b>Specification</b></th>
           <th style="width:40%;"><b>Observation</b></th>
        </tr>
     
        <tr>
        <td style="width:10%;"></td>
         <td style="width:25%;"></td>
          <td style="width:25%;"></td>
           <td style="width:40%;"></td>
        </tr>
                            
         </table><div></div><div></div><div></div>
                            
        <table cellpadding="5" border="0.1">
        <tr>
        <th style="width:25%;"><b></b></th>
         <th style="width:25%;"><b>PREPARED BY</b></th>
          <th style="width:25%;"><b>REVIEWED BY</b></th>
           <th style="width:25%;"><b>APPROVED BY</b></th>
        </tr>
         <tr>
        <td style="width:25%;"><b>Name</b></td>
         <td style="width:25%;"></td>
          <td style="width:25%;"></td>
           <td style="width:25%;"></td>
        </tr>
         <tr>
        <td style="width:25%;"><b>Sign/Date</b></td>
         <td style="width:25%;"></td>
          <td style="width:25%;"></td>
           <td style="width:25%;"></td>
        </tr>
         <tr>
        <td style="width:25%;"><b>Designation</b></td>
         <td style="width:25%;"></td>
          <td style="width:25%;"></td>
           <td style="width:25%;"></td>
        </tr>
         <tr>
        <td style="width:25%;"><b>Department</b></td>
         <td style="width:25%;"></td>
          <td style="width:25%;"></td>
           <td style="width:25%;"></td>
        </tr>
        </table>
                            
         <br pagebreak="true"/> ';              
            


  $html.='
        <table cellpadding="1" border="0.1">
        <tr>
        <td style="width=100%;text-align:center;"><b>FINISHED PRODUCT ANALYTICAL REPORT</b></td>
        </tr>
        </table>
           <table cellpadding="3" border="0.1">
          <tr>
         <td style="width:25%; text-align:cenetr;" rowspan="2"> </td>
          <td style="width:35%;text-align:center;" rowspan="2" ><b>Product Name:<br>NOVOMIX<br>10001</b></td>
          <td style="width:20%;"><b> Page No.:</b></td>
           <td style="width:20%;"></td>
          </tr>
          <tr>
               <td style="width:20%;"><b>Reference.</b></td>
            <td style="width:20%;"></td>
          </tr>
          
          <tr>
          <td style="width:25%;"><b>Department:</b></td>
         <td style="width:35%;"><b>Colour:</b></td>
         <td style="width:20%;"><b>Ref. ARD No.:</b></td>
           <td style="width:20%;">FP/NOVOMATR/10001/102</td>
          </tr>
          
          <tr>
          <td style="width:25%;"><b>ART No.:</b></td>
           <td style="width:20%;">FP/NOVOMATR/10001/102</td>
           <td style="width:35%;"><b>Supersedes No.:</b></td>
           <td style="width:20%;"></td>
          </tr>
           <tr>
          <td style="width:25%;"><b>Effective Date:</b></td>
           <td style="width:20%;"></td>
           <td style="width:35%;"><b>Review Date:</b></td>
           <td style="width:20%;"></td>
          </tr>
          <tr>
          <td style="width:25%;"><b>Ref.SOP No.:</b></td>
           <td style="width:20%;">SOP/QC/030</td>
           <td style="width:35%;"><b>Annexure No.:</b></td>
           <td style="width:20%;">A/SOP/QC/030/15-01</td>
          </tr>
         </table><br><br>';
        $html.='
         <table cellpadding="5" border="0.1">
         <tr>
         <th style="width:10%;"><b>Sr No.</b></th>
         <th style="width:20%;"><b>Test</b></th>
         <th style="width:30%;"><b>Specification</b></th>
         <th style="width:40%;"><b>Observation</b></th>
         </tr>
         <tr>
         <td style="width:10%;"></td>
         <td style="width:20%;"></td>
         <td style="width:30%;"></td>
         <td style="width:40%;"></td>
         </tr>
         <tr>
         <td style="width:10%;"></td>
         <td style="width:20%;"></td>
         <td style="width:30%;"></td>
         <td style="width:40%;"></td>
         </tr>
         <tr>
         <td style="width:10%;"></td>
         <td style="width:20%;"></td>
         <td style="width:30%;"></td>
         <td style="width:40%;"></td>
         </tr>
         </table><br>
         <tr>
         <td style="width: 100%;"><b>Remark:</b></td>
         </tr><br>
         <table cellpadding="5" border="0.1">
          <tr>
         <th style="width:35%;" rowspan="3"><b>Analyzed By:</b>
           <br><b>Date:</b></th>
          <th style="width:35%;" rowspan="3"><b>Checked By:</b>
          <br><b>Date:</b></th>
        <th style="width:30%;" rowspan="3"><b>Approved By:</b>
         <br><b>Date:</b></th>
        </tr>
       </table><br><br>';
        $html.='<h4 style="text-align:center;">REVISION HISTORY FOR ANALYTICAL REPORT:</h4><br><br>
       <table cellpadding="5" border="0.1">
       <tr>
         <th style="width:20%;" ><b>Revision No.</b></th>
          <th style="width:40%;" ><b>Revision Description</b></th>
        <th style="width:40%;"><b>Effective Date</b></th>
        </tr>
        <tr>
         <td style="width:20%;"></td>
          <td style="width:40%;"></td>
        <td style="width:40%;"></td>
        </tr>
        <tr>
         <td style="width:20%;" ></td>
          <td style="width:40%;" ></td>
        <td style="width:40%;"></td>
        </tr>
        <tr>
         <td style="width:20%;" ></td>
          <td style="width:40%;" ></td>
        <td style="width:40%;"></td>
        </tr>
       </table><br><br><br><br><br>
       <table cellpadding="5" border="0.1">
        <tr>
        <th style="width:25%;"><b></b></th>
         <th style="width:25%;"><b>PREPARED BY</b></th>
          <th style="width:25%;"><b>REVIEWED BY</b></th>
           <th style="width:25%;"><b>APPROVED BY</b></th>
        </tr>
         <tr>
        <td style="width:25%;"><b>Sign/Date</b></td>
         <td style="width:25%;"></td>
          <td style="width:25%;"></td>
           <td style="width:25%;"></td>
        </tr>
        </table>
         <br pagebreak="true"/>';
         $html.=
          '<h4 style="text-align:center;">FINISHED PRODUCT ANALYTICAL REPORT</h4>
         <table cellpading="3" border="0.1">
        <tr>
        <td style="width:30%; text-align:center;"rowspan="5">Product Name:</td>
        <td style="width:30%;"  >Reference STP No.:</td>
         <td style="width:40%;text-align:center;"rowspan="5">Page No.: </td>
         </tr>
         <tr>
          <td style="width:30%;" >Analytical Raw Data No.:</td>
          </tr>
        <tr>
        <td style="width:30%;" >Supersedes No.:</td>
          </tr>
          <tr>
        <td style="width:30%" >Effective Date:</td>
          </tr>
           <tr>
        <td style="width:30%;" >Review Month:</td>
          </tr>
           <tr>
         <td style="width:30%; text-align:center;" >Ref SOP No.:</td>
           <td style="width:15%;"></td>
          <td style="width:15%;">Annexure</td>
           <td style="width:40%;"></td>
         </tr>
         </table><br>
           <table cellpadding="5" border="0.1">
           <tr>
           <td style="width:50%;"><b>Product Name  :</b></td>
           <td style="width:50%;"><b>Colour  :</b></td>
           </tr>
            <tr>
           <td style="width:50%;"><b>Batch No.  :</b></td>
           <td style="width:50%;"><b>Batch Size  :</b></td>
           </tr>
            <tr>
           <td style="width:50%;"><b>Date Of Mfg  :</b></td>
           <td style="width:50%;"><b>Retest Date/Shelf Life  :</b></td>
           </tr>
            <tr>
           <td style="width:50%;"><b>A.R No.  :</b></td>
           <td style="width:50%;"><b>Sampled By   :</b></td>
           </tr>
            <tr>
           <td style="width:50%;"><b>Sampled Qty.  :</b></td>
           <td style="width:50%;"><b>Date Of Release   :</b></td>
           </tr>
          </table>
          <table>
          <tr>
          <td style="width:5%"><b>01.</b></td>
          <td style="width:55%" rowspan="8"><b>Appearance:</b></td>
          <td style="width:40%" rowspan="8" ><b>Balance No.:</b></td>
          </tr>
          <tr>
           
          <td style="width:100% text-align:left;">Take about________________(2 to 3 g) of sample and transfered into a clean and dry petri dish. 
          Spread it uniformaly by spatula and observe.</td>
          </tr>
          <tr>
          <td style="width:100%; ">Observation____________________________________</td>
          </tr>
           <tr>
          <td style="width:100%; ">Specification:</td>
          </tr>
          <tr>
          <td style="width:100%; ">Result:</td>
          </tr>
          <tr>
          <td style="width:50%; ">Analyzed By/Date:</td>
            <td style="width:50%;">Checked By/Date:</td>
          </tr><br>
          </table>
          <hr>
        <table>
          <tr>
          <td style="width:5%"><b>02.</b></td>
          <td style="width:55%" rowspan="8"><b>Colour Shade(Visual Comparison of colour shade of power):</b></td>
          <td style="width:40%" rowspan="8" ><b>Balance No.:</b></td>
          </tr>
          <tr>
           <td style="width:100% text-align:left;">Standared Spread________________(1 g) of standared *on a piece of white card.</td>
          </tr>
          <tr>
           <td style="width:100% text-align:left;">Sample Spread________________(2 g) of sample on a piece of white card.</td>
          </tr>
          <tr>
           <td style="width:100% text-align:left;">Observe visually the colour different of the sample with respect to the standared*.</td>
          </tr>
          <tr>
           <td style="width:100% text-align:left;">Standared*-Most recent batch___________(B NO.).</td>
          </tr>
          <tr>
          <td style="width:100%; ">Observation____________________________________</td>
          </tr>
          <tr>
          <td style="width:100%; ">Specification:</td>
          </tr>
          <tr>
          <td style="width:100%; ">Result:</td>
          </tr>
          <tr>
          <td style="width:50%; ">Analyzed By/Date:</td>
           <td style="width:50%; ">Checked By Date:</td>
          </tr>
        </table>
       <table cellpadding="5" border="0.1">
        <tr>
        <th style="width:25%;"><b></b></th>
         <th style="width:25%;"><b>PREPARED BY</b></th>
          <th style="width:25%;"><b>REVIEWED BY</b></th>
           <th style="width:25%;"><b>APPROVED BY</b></th>
        </tr>
        <tr>
        <td style="width:25%;"><b>Name</b></td>
         <td style="width:25%;"></td>
          <td style="width:25%;"></td>
           <td style="width:25%;"></td>
        </tr>
         <tr>
        <td style="width:25%;"><b>Sign/Date</b></td>
         <td style="width:25%;"></td>
          <td style="width:25%;"></td>
           <td style="width:25%;"></td>
        </tr>
        <tr>
        <td style="width:25%;"><b>Designation</b></td>
         <td style="width:25%;"></td>
          <td style="width:25%;"></td>
           <td style="width:25%;"></td>
        </tr>
        <tr>
        <td style="width:25%;"><b>Department</b></td>
         <td style="width:25%;"></td>
          <td style="width:25%;"></td>
           <td style="width:25%;"></td>
        </tr>
        </table>
         <br pagebreak="true"/>';
           $html.=
          '<h4 style="text-align:center;">FINISHED PRODUCT ANALYTICAL REPORT</h4>
         <table cellpading="3" border="0.1">
        <tr>
        <td style="width:30%; text-align:center;"rowspan="5">Product Name:</td>
        <td style="width:30%;"  >Reference STP No.:</td>
         <td style="width:40%;text-align:center;"rowspan="5">Page No.: </td>
         </tr>
         <tr>
          <td style="width:30%;" >Analytical Raw Data No.:</td>
          </tr>
        <tr>
        <td style="width:30%;" >Supersedes No.:</td>
          </tr>
          <tr>
        <td style="width:30%" >Effective Date:</td>
          </tr>
           <tr>
        <td style="width:30%;" >Review Month:</td>
          </tr>
           <tr>
         <td style="width:30%; text-align:center;" >Ref SOP No.:</td>
           <td style="width:15%;"></td>
          <td style="width:15%;">Annexure</td>
           <td style="width:40%;"></td>
         </tr>
         </table><br><br>
         <table cellpadding="3" border="0.1">
         <tr>
         <td style="width:50%;">Batch No.:</td>
          <td style="width:50%;">Medicap lot no:</td>
         </tr>
         </table><br>
         <hr>
         <table>
         <tr>
         <td style="width:5%;"><b>03.</b></td>
          <td style="width:15%"><b>pH(2% slurry in water):</b></td>
           <td style="width:35%;">Balance No.:_______________,</td>
           <td style="width:35%;">pH Meter No.:____________</td>
         </tr>
         <td style="width:5%;"></td>
         <td style="width:75%">Take____________(2 g)of sample, add______ml(100 ml)distilled water stir for 10 to 15 minutes 
           with stirrer. Dip the electrode of the pH meter in it and measure the pH.</td>
           </tr><br>
           <tr>
           <td style="width:5%;"></td>
           <td style="width:75%;">Observation:__________</td>
           </tr>
            <tr>
           <td style="width:5%;"></td>
           <td style="width:75%;">Specification:__________</td>
           </tr>
           <tr>
           <td style="width:5%;"></td>
           <td style="width:75%;">Result:__________</td>
           </tr>
           <tr>
           <td style="width:5%;"></td>
           <td style="width:35%;">Analyezed By/Date:__________</td>
           <td style="width:35%;">Checked By/Date:__________</td>
           </tr><br><br>
         </table>
         <hr>
          <table>
         <tr>
         <td style="width:5%;"><b>04.</b></td>
          <td style="width:90%"><b>Partical Size(Done on wet slurry):</b></td>
           </tr>
           <tr>
            <td style="width:5%;"></td>
           <td style="width:45%;">Balance No.:_______________________</td>
           <td style="width:45%;">Hot air oven No.:_______________________</td>
           </tr>
            <tr>
            <td style="width:5%;"></td>
           <td style="width:90%;">Prepared a 5% dispersion of sample using the dispersion media of dichloromethane and isopropyl alchol </td>
           </tr>
            <tr>
            <td style="width:5%;"></td>
           <td style="width:90%;">in proportion of 65:35(w/w). Take_________________(5 g) sample in_____________g(61.75 g) of</td>
           </tr>
           <tr>
            <td style="width:5%;"></td>
           <td style="width:90%;">dichloromethane and_____________g(33.25g) of isopropyl alcohol Stir the dispersion for about 45 minutes</td>
           </tr>
           <tr>
            <td style="width:5%;"></td>
           <td style="width:90%;">with constant stirring. Pour this dispersion on the top of the 100 mesh tarred mesh in a form of fine stream.
           </td>
           </tr>
           <tr>
            <td style="width:5%;"></td>
           <td style="width:90%;">Immediately rinse the mesh with three consecutive_____________ml(500 ml) portion of the dispersion media</td>
           </tr>
           <tr>
            <td style="width:5%;"></td>
           <td style="width:90%;">i.e. CH2Cl2 and API in proportion of 65:35; before there is any film formation on the mesh.</td>
           </tr>
           <tr>
            <td style="width:5%;"></td>
           <td style="width:90%;">Mesh the remaning particles on the mesh without applying any pressure with the help of running water.</td>
           </tr>
           <tr>
            <td style="width:5%;"></td>
           <td style="width:90%;">Continue the wet mesh process until the emerging liquid appears free of particles.</td>
           </tr>
           <tr>
            <td style="width:5%;"></td>
           <td style="width:90%;">Remove the mesh and dry it at 105&#176;C. Allow the mesh to come to room temperature in desiccstor before 
           weighing.</td>
           </tr>
           <tr>
            <td style="width:5%;"></td>
           <td style="width:90%;">Repeart the operation until two successive weighing do not differ by more than 1mg.</td>
           </tr>
            <tr>
            <td style="width:5%;"></td>
           <td style="width:90%;">Determine the weight of dried material on the mesh.</td>
           </tr>
            <tr>
            <td style="width:5%;"></td>
           <td style="width:90%;">Percentage w/w passed through 100 mesh=100XW1-W5/W1.</td>
           </tr>
            <tr>
            <td style="width:5%;"></td>
           <td style="width:90%;">Where W1= Weight of sample in g taken for mesh test.=______________g</td>
          </tr>
          <tr>
            <td style="width:5%;"></td>
           <td style="width:90%;"> W2= Weight of empety mesh.=______________g</td>
          </tr>
           <tr>
            <td style="width:5%;"></td>
           <td style="width:90%;"> W3= Weight of mesh plus residue after drying.=______________g</td>
          </tr>
           <tr>
            <td style="width:5%;"></td>
           <td style="width:90%;"> W4= Weight of mesh plus residue after drying(constant weight).=______________g</td>
          </tr>
          <tr>
          <td style="width:5%;"></td>
           <td style="width:35%;">Analyezed By/Date:__________</td>
           <td style="width:35%;">Checked By/Date:__________</td>
           </tr>
         </table><br><br>
        <table cellpadding="5" border="0.1">
        <tr>
        <th style="width:25%;"><b></b></th>
         <th style="width:25%;"><b>PREPARED BY</b></th>
          <th style="width:25%;"><b>REVIEWED BY</b></th>
           <th style="width:25%;"><b>APPROVED BY</b></th>
        </tr>
        <tr>
        <td style="width:25%;"><b>Sign/Date</b></td>
         <td style="width:25%;"></td>
          <td style="width:25%;"></td>
           <td style="width:25%;"></td>
        </tr>
        </table>
         <br pagebreak="true"/>';
         
          $html.=
          '<h4 style="text-align:center;">FINISHED PRODUCT ANALYTICAL REPORT</h4>
         <table cellpading="3" border="0.1">
        <tr>
        <td style="width:30%; text-align:center;"rowspan="3">Product Name:</td>
        <td style="width:30%;"  >Reference STP No.:</td>
         <td style="width:40%;text-align:center;"rowspan="3">Page No.: </td>
         </tr>
         <tr>
          <td style="width:30%;" >Analytical Raw Data No.:</td>
          </tr>
        <tr>
        <td style="width:30%;" >Supersedes No.:</td>
          </tr>
         
           <tr>
         <td style="width:30%; text-align:center;" >Ref SOP No.:</td>
           <td style="width:15%;"></td>
          <td style="width:15%;">Annexure No.:</td>
           <td style="width:40%;"></td>
         </tr>
         </table><br><br>
         <table cellpadding="3" border="0.1">
         <tr>
         <td style="width:50%;">Batch No.:</td>
          <td style="width:50%;">Medicap lot no:</td>
         </tr>
         </table><br>
         <hr>
         <table>
         <tr>
         <td style="width:5%"></td>
         <td style="width:95%;">W4=_______________________g</td>
         </tr>
         <tr>
         <td style="width:5%"></td>
         <td style="width:95%;">W5= weight of material remained on the mesh after drying to constant weight= W4-W2</td>
         </tr>
         <tr>
         <td style="width:5%"></td>
         <td style="width:95%;">W5=_______________________g</td>
         </tr>
         <tr>
         <td style="width:5%"></td>
         <td style="width:95%;">=_______________________g</td>
         </tr>
          <tr>
         <td style="width:5%"></td>
         <td style="width:95%;">%Passed through 100 mesh=100xW1-W5/W1</td>
         </tr>
          <tr>
         <td style="width:5%"></td>
         <td style="width:45%;">=_____________________</td>
         </tr>
          <tr>
         <td style="width:5%"></td>
         <td style="width:45%;">=_____________________</td>
         </tr>
          <tr>
         <td style="width:5%"></td>
         <td style="width:95%;">=_____________________%</td>
         </tr>
          <tr>
         <td style="width:5%"></td>
          <td style="width:95%;">Specification:</td>
         </tr>
         <tr>
         <td style="width:5%"></td>
          <td style="width:95%;">Result:</td>
         </tr>
         <tr>
         <td style="width:5%"></td>
          <td style="width:45%;">Analyzed By/Date:</td>
            <td style="width:45%;">Checked By/Date:</td>
         </tr>
         </table><br>
         <hr>
         <table>
         <tr>
         <td style="width:5%"><b>05.</b></td>
         <td style="width:95%"><b>Tapped Density:</b></td>
         </tr>
          <tr>
         <td style="width:5%"></td>
         <td style="width:45%">Balance No.:_________________</td>
           <td style="width:45%">Bulk Densitometer No.:_________________</td>
         </tr>
         <tr>
            <td style="width:5%"></td>
         <td style="width:95%">Weight ____________ (10 g) of sample. Pour it in a 50 ml Graduated stoppered measuring cylinder.</td>
           </tr>
          <tr>
            <td style="width:5%"></td>
         <td style="width:95%">Set 100 taps for the bulk densitometer. Note the volume (A) occupied by 10 g of the powder after 100 taps on the graduated cyclender.</td>
           </tr>
            <tr>
          <td style="width:5%"></td>
         <td style="width:95%">(A) Volume after 100 taps=___________________</td>
           </tr>
         </table><br><br>
        
          <table cellpadding="5" border="0.1">
        <tr>
        <th style="width:25%;"><b></b></th>
         <th style="width:25%;"><b>PREPARED BY</b></th>
          <th style="width:25%;"><b>REVIEWED BY</b></th>
           <th style="width:25%;"><b>APPROVED BY</b></th>
        </tr>
        <tr>
        <td style="width:25%;"><b>Sign/Date</b></td>
         <td style="width:25%;"></td>
          <td style="width:25%;"></td>
           <td style="width:25%;"></td>
        </tr>
        </table>
         <br pagebreak="true"/>';
          $html.=
          '<h4 style="text-align:center;">FINISHED PRODUCT ANALYTICAL REPORT</h4>
         <table cellpading="3" border="0.1">
        <tr>
        <td style="width:30%; text-align:center;"rowspan="3">Product Name:</td>
        <td style="width:30%;"  >Reference STP No.:</td>
         <td style="width:40%;text-align:center;"rowspan="3">Page No.: </td>
         </tr>
         <tr>
          <td style="width:30%;" >Analytical Raw Data No.:</td>
          </tr>
        <tr>
        <td style="width:30%;" >Supersedes No.:</td>
          </tr>
         
           <tr>
         <td style="width:30%; text-align:center;" >Ref SOP No.:</td>
           <td style="width:15%;"></td>
          <td style="width:15%;">Annexure No.:</td>
           <td style="width:40%;"></td>
         </tr>
         </table><br><br>
         <table cellpadding="3" border="0.1">
         <tr>
         <td style="width:50%;">Batch No.:</td>
          <td style="width:50%;">Medicap lot no:</td>
         </tr>
         </table><br>
         <hr>
         <table>
         <tr>
         <td style="width:5%;"></td>
          <td style="width:90%;">Calculations:</td>
         </tr>
         <tr>
         <td style="width:5%;"></td>
          <td style="width:90%;">Tap Density in g/cc= Weight of sample</td>
          </tr>
           <tr>
         <td style="width:5%;"></td>
          <td style="width:32%; text-align:right;">_________________</td>
          </tr>
          <tr>
         <td style="width:5%;"></td>
          <td style="width:28%; text-align:right;">Volume A</td>
          </tr>
          <tr>
         <td style="width:5%;"></td>
          <td style="width:32%; text-align:right;">=_________________</td>
          </tr>
          <tr>
         <td style="width:5%;"></td>
          <td style="width:35%; text-align:right;">=_________________g/cc</td>
          </tr>
          <tr>
           <td style="width:5%;"></td>
          <td style="width:95%;">Specification:</td>
          </tr>
          <tr>
           <td style="width:5%;"></td>
          <td style="width:95%;">Result:</td>
          </tr>
           <tr>
           <td style="width:5%;"></td>
          <td style="width:45%;">Analyzed By/Date:</td>
          <td style="width:45%;">Checked By/Date:</td>
          </tr>
          <hr>
          <tr>
          <td style="width:5%;"><b>06.</b></td>
         <td style="width:90%;"><b>Ash Content:</b></td>
          </tr>
          <tr>
          <td style="width:5%;"></td>
          <td style="width:45%;">Balance No.:____________</td>
           <td style="width:45%;">Muffle Furance No.:___________</td>
          </tr>
          <tr>
          <td style="width:5%;"></td>
          <td style="width:90%;">Heat a silica crucible at__________(600&plusmn; 25&#176;C) for 15 minutes, allow to cool in a desiccator and weight.</td>
           </tr>
            <tr>
          <td style="width:5%;"></td>
          <td style="width:90%;">Take____________g(1-2 g) of sample to the crucible and weight the crucible. Ignite the</td>
           </tr>
            <tr>
          <td style="width:5%;"></td>
          <td style="width:90%;">sample at__________(600&plusmn; 25&#176;C) for_________ minutes (45 minutes). Allow the silica curicible to</td>
           </tr>
            <tr>
          <td style="width:5%;"></td>
          <td style="width:90%;">come to room temperature in a desiccator before weighing. Repeart the operation until two successive  </td>
           </tr>
           <tr>
          <td style="width:5%;"></td>
          <td style="width:90%;">weighing do not differ by more than 0.5mg.</td>
           </tr>
           <tr>
          <td style="width:5%;"></td>
          <td style="width:90%";>Calculation:     %Ash=(W4-W1) X 100</td>
           </tr>
            <tr>
          <td style="width:5%;"></td>
          <td style="width:25%;text-align:right:">_________</td>
           </tr>
           <tr>
          <td style="width:5%;"></td>
          <td style="width:25%;text-align:right:">(W2-W1)</td>
           </tr>
            <tr>
          <td style="width:5%;"></td>
          <td style="width:95%";>Where,</td>
           </tr>
           <tr>
          <td style="width:5%;"></td>
          <td style="width:90";>Where,</td>
           </tr>
           <tr>
          <td style="width:5%;"></td>
          <td style="width:95%";>W1= Weight of the crucible(g)=______________ g</td>
           </tr>
            <tr>
          <td style="width:5%;"></td>
          <td style="width:90%";>W2=Weight of the crucible with test sample before ignition(g)=_______________g</td>
           </tr>
            <tr>
          <td style="width:5%;"></td>
          <td style="width:95%";>W3=Weight of the crucible  with residue after ignition(g)=_______________g</td>
           </tr>
            <tr>
          <td style="width:5%;"></td>
          <td style="width:90%";>W4=Weight of the crucible  with residue after ignition(g)=_______________g</td>
           </tr>
            <tr>
          <td style="width:5%;"></td>
          <td style="width:90%";>Difference between W3 and W4=_______________</td>
           </tr>
          </table>
          <hr>
         
        <table></table>
         <br><br><br>
        <table cellpadding="5" border="0.1">
          
        <tr>
        <th style="width:25%;"><b></b></th>
         <th style="width:25%;"><b>PREPARED BY</b></th>
          <th style="width:25%;"><b>REVIEWED BY</b></th>
           <th style="width:25%;"><b>APPROVED BY</b></th>
        </tr>
        <tr>
        <td style="width:25%;"><b>Sign/Date</b></td>
         <td style="width:25%;"></td>
          <td style="width:25%;"></td>
           <td style="width:25%;"></td>
        </tr>
        </table>
         <br pagebreak="true"/>';
         
         $html.=
          '<h4 style="text-align:center;">FINISHED PRODUCT ANALYTICAL REPORT</h4>
         <table cellpading="3" border="0.1">
        <tr>
        <td style="width:30%; text-align:center;"rowspan="3">Product Name:</td>
        <td style="width:30%;"  >Reference STP No.:</td>
         <td style="width:40%;text-align:center;"rowspan="3">Page No.: </td>
         </tr>
         <tr>
          <td style="width:30%;" >Analytical Raw Data No.:</td>
          </tr>
        <tr>
        <td style="width:30%;" >Supersedes No.:</td>
          </tr>
         
           <tr>
         <td style="width:30%; text-align:center;" >Ref SOP No.:</td>
           <td style="width:15%;"></td>
          <td style="width:15%;">Annexure No.:</td>
           <td style="width:40%;"></td>
         </tr>
         </table><br><br>
         <table cellpadding="3" border="0.1">
         <tr>
         <td style="width:50%;">Batch No.:</td>
          <td style="width:50%;">Medicap lot no:</td>
         </tr>
         </table><br>
         <hr>
         <table>
       <tr>
          <td style="width:5%;"></td>
          <td style="width:90%";> %Ash=(W4-W1) X 100</td>
           </tr>
            <tr>
          <td style="width:5%;"></td>
          <td style="width:15%;text-align:right:">_________</td>
           </tr>
           <tr>
          <td style="width:5%;"></td>
          <td style="width:15%;text-align:right:">(W2-W1)</td>
           </tr>
         <tr>
         <td style="width:5%"></td>
         <td style="width:15%;text-align:right:">=______________</td>
         </tr>
         <tr>
         <td style="width:5%"></td>
         <td style="width:15%;text-align:right:">=______________</td>
         </tr>
         <tr>
         <td style="width:5%"></td>
         <td style="width:15%;text-align:right:">=______________</td>
         </tr>
         
         <td style="width:5%"></td>
          <td style="width:95%;">Specification:</td>
         </tr>
         <tr>
         <td style="width:5%"></td>
          <td style="width:95%;">Result:</td>
         </tr>
         <tr>
         <td style="width:5%"></td>
          <td style="width:45%;">Analyzed By/Date:</td>
            <td style="width:45%;">Checked By/Date:</td>
         </tr>
         </table><br>
         <hr>
         <table>
          <tr>
         <td style="width:5%"><b>07.</b></td>
         <td style="width:45%"><b>Arsenic:</b></td>
           <td style="width:45%">Balance No.:_________________</td>
         </tr>
        <tr>
         <td style="width:5%"></td>
         <td style="width:90%">The limit of arsenic is indicated in terms of ppm,i.e. the parts of arsenic, As,per million parts (by)</td>
          </tr>
           <tr>
         <td style="width:5%"></td>
         <td style="width:90%">weight) of substance under examination.</td>
          </tr>
          <tr>
         <td style="width:5%"></td>
         <td style="width:90%"><b>Standered Arsenic Solution:</b></td>
          </tr>
          <tr>
         <td style="width:5%"></td>
         <td style="width:90%">Transfer______ml(10.0 ml) of Arsenic Trioxide Stock Solution to a _______ml(1000ml) volumetric flask, </td>
          </tr>
          <tr>
         <td style="width:5%"></td>
         <td style="width:90%">add _____ml(10 ml) of 2N sulfuric acid, then add recently boiled and cooled water to volume and mix.</td>
          </tr>
          <tr>
         <td style="width:5%"></td>
         <td style="width:90%">Each ml of Standered Arsenic Solution contains the equivalent of &#956; g of arsenic (As)</td>
          </tr><br>
           <tr>
         <td style="width:5%"></td>
         <td style="width:90%"><b> Standered Preparation:</b></td>
          </tr>
          <tr>
         <td style="width:5%"></td>
         <td style="width:90%">Pipette ____ml(1.5ml) of Standered Arsenic Solution into a generator flask, add ____ml(2ml) of </td>
          </tr>
           <tr>
         <td style="width:5%"></td>
         <td style="width:90%">sulfuric acid, mix and add _________ml(Total amount of 30 % hydrogen peroxide used in preparing the test preparation) </td>
          </tr>
          <tr>
         <td style="width:5%"></td>
         <td style="width:90%">of 30% hydrogen peroxide. Heat the mixture to strong fuming, cool, and cautiously__________ml(10ml) of water, and again heat to strong fumes.</td>
          </tr>
          <tr>
         <td style="width:5%"></td>
         <td style="width:90%">Repeat this procedure with another __________ml(10 ml) of water remove any traces of hydrogen peroxide. Cool and dilute with water to 35 ml.</td>
          </tr>
         </table>
          <hr>
         
        <table></table>
         <br><br><br>
        
          <table cellpadding="5" border="0.1">
        <tr>
        <th style="width:25%;"><b></b></th>
         <th style="width:25%;"><b>PREPARED BY</b></th>
          <th style="width:25%;"><b>REVIEWED BY</b></th>
           <th style="width:25%;"><b>APPROVED BY</b></th>
        </tr>
        <tr>
        <td style="width:25%;"><b>Sign/Date</b></td>
         <td style="width:25%;"></td>
          <td style="width:25%;"></td>
           <td style="width:25%;"></td>
        </tr>
        </table>
         <br pagebreak="true"/>';
          $html.=
          '<h4 style="text-align:center;">FINISHED PRODUCT ANALYTICAL REPORT</h4>
         <table cellpading="3" border="0.1">
        <tr>
        <td style="width:30%; text-align:center;"rowspan="3">Product Name:</td>
        <td style="width:30%;"  >Reference STP No.:</td>
         <td style="width:40%;text-align:center;"rowspan="3">Page No.: </td>
         </tr>
         <tr>
          <td style="width:30%;" >Analytical Raw Data No.:</td>
          </tr>
        <tr>
        <td style="width:30%;" >Supersedes No.:</td>
          </tr>
         
           <tr>
         <td style="width:30%; text-align:center;" >Ref SOP No.:</td>
           <td style="width:15%;"></td>
          <td style="width:15%;">Annexure No.:</td>
           <td style="width:40%;"></td>
         </tr>
         </table><br><br>
         <table cellpadding="3" border="0.1">
         <tr>
         <td style="width:50%;">Batch No.:</td>
          <td style="width:50%;">Medicap lot no:</td>
         </tr>
         </table><br>
         <hr>
         <table>
          <tr>
         <td style="width:5%"></td>
         <td style="width:90%"><b>Test Preparation:</b></td>
         </tr>
          <tr>
         <td style="width:5%"></td>
         <td style="width:90%">Take_________(1 g) of the sample in a genertator flask. Add_______ml(5 ml) of sulfuric acid and a few glass 
         beads and digest in a fume hood, at a temperature not exceeding 120&#176;C, untill charring begins. Cautiously, add drop-wise, 30% hydrogen peroxide, allowing the reaction to subside and again heating between drops of hydrogen peroxide.
         Add the first few drops slowly with sufficient mixing, in order to prevent a rapid reaction. Discontinue heating if foaming become excessive. Maintain oxidizing
         condition at all times during the digestion by adding small quantities of hydrogen peroxide solution whenever he mixture turns brown or darknes.
         Continue the designation untill the organic matter is destroyed and sulfuric trioxide fumes copiously evolved and the solution becomes colourless or only a light straw color.
         Total_________ml of 30% hydrogen peroxide is added. Cool add cautiously________ml(10 ml) of water, mix and again evaporate to strong fuming repeating
         the producer to remove any trace of hydrogen peroxide  cool, and cautiously__________ml(10ml) of water, wash the sides of the flask with a few ml of water and dilute with water to 35 ml.
         </td>
         </tr>
         <tr>
         <td style="width:5%"></td>
         <td style="width:90%"><b>Procedure:</b></td>
         </tr>
         <tr>
         <td style="width:5%"></td>
         <td style="width:90%">To standard preparation, add_____ml(5ml) of 1 M potassium iodide and g(10g) of Zanic.
         Immediately assemble the apparatus and immerse the flask in a water-bath at a temperature such that a uniform evalution of gas maintained, allow the colour development to process for 40 minutes.
         To test Preparation, add______ml(5ml) of 1 M potassium iodide and_________(10 g) of Zinc Immediately assemble the apparatus and immerse the flask in a water-bath at a temperature such that a uniform evalution of gas maintained, allow the colour development to process for 40 minutes.
         After 40 minutes, observe the stain produced on mercuric chloride paper of Standard and test preparation and campare.
         </td>
         </tr>
           <tr>
         <td style="width:5%"></td>
         <td style="width:90%"><b>Observation:____________________________________________________</b></td>
         </tr>
         <hr>
         
        <table></table>
         <br><br><br>
        
          <table cellpadding="5" border="0.1">
        <tr>
        <th style="width:25%;"><b></b></th>
         <th style="width:25%;"><b>PREPARED BY</b></th>
          <th style="width:25%;"><b>REVIEWED BY</b></th>
           <th style="width:25%;"><b>APPROVED BY</b></th>
        </tr>
        <tr>
        <td style="width:25%;"><b>Sign/Date</b></td>
         <td style="width:25%;"></td>
          <td style="width:25%;"></td>
           <td style="width:25%;"></td>
        </tr>
        </table>
         <br pagebreak="true"/>';
          $html.=
          '<h4 style="text-align:center;">FINISHED PRODUCT ANALYTICAL REPORT</h4>
         <table cellpading="3" border="0.1">
        <tr>
        <td style="width:30%; text-align:center;"rowspan="3">Product Name:</td>
        <td style="width:30%;"  >Reference STP No.:</td>
         <td style="width:40%;text-align:center;"rowspan="3">Page No.: </td>
         </tr>
         <tr>
          <td style="width:30%;" >Analytical Raw Data No.:</td>
          </tr>
        <tr>
        <td style="width:30%;" >Supersedes No.:</td>
          </tr>
         
           <tr>
         <td style="width:30%; text-align:center;" >Ref SOP No.:</td>
           <td style="width:15%;"></td>
          <td style="width:15%;">Annexure No.:</td>
           <td style="width:40%;"></td>
         </tr>
         </table><br><br>
         <table cellpadding="3" border="0.1">
         <tr>
         <td style="width:50%;">Batch No.:</td>
          <td style="width:50%;">Medicap lot no:</td>
         </tr>
         </table><br>
         <hr>
         <table>
          <tr>
         <td style="width:5%"></td>
         <td style="width:90%">Specification:</td>
         </tr>
         <tr>
         <td style="width:5%"></td>
         <td style="width:90%">Result:</td>
         </tr>
         <tr>
         <td style="width:5%"></td>
         <td style="width:45%">Analyzed By/Date:</td>
         <td style="width:45%">Checked By/Date:</td>
         </tr>
         <hr>
         <tr>
         <td style="width:5%"><b>08.</b></td>
         <td style="width:90%"><b>Heavy Metals:</b></td>
         </tr>
          <tr>
         <td style="width:5%"></td>
         <td style="width:90%">The limits of heavy metals is indicated in terms of ppm, i.e.the parts of lead,Pb per million parts (by weight)
         of the substance under examination.</td>
         </tr>
         <tr>
         <td style="width:5%"></td>
         <td style="width:90%"><b>Standard Lead Solution(20ppm):</b></td>
         </tr>
          <tr>
         <td style="width:5%"></td>
         <td style="width:90%">On the day of use, dilute______ml(20ml) of Lead Nitrate Stock Solution with water to______ml(100ml)
         Each ml of Standard Lead Solution contain the equvalent of 20 ug of lead (Pb).
         </td>
         </tr>
          <tr>
         <td style="width:5%"></td>
         <td style="width:90%"><b>Standard Solution:</b></td>
         </tr>
          <tr>
         <td style="width:5%"></td>
         <td style="width:90%">Into a 50ml Nessler cylinder pipette out______ml(1.0ml) of standard lead standard solution
         (20ppm Pb) and dilute with water to 25 ml. Adjust with dilute acetic acid and dilute ammonia solution to a pH between 3.0 to 4.0, dilute with water to about 35 ml and mix.
         </td>
         </tr><br>
          <tr>
         <td style="width:5%"></td>
         <td style="width:90%"><b>Test Solution:</b></td>
         </tr>
         <tr>
         <td style="width:5%"></td>
         <td style="width:90%">Weight______1.0g sample in a silica crucible, add sufficent sulphuric acid to wet the sample 
         ignite carefully at a low temperature untill thoroughly charred. Add to the charred mass______ml(2ml) of nitric acid and 5 drops sulphuric acid and heat cantinously untill 
         white fumes are no longer evolved . Ignite in a muffle furnance, at_______&#176;C(500&#176;C TO 600&#176;C), untill the carbon is completely burnt off.
         Cool, add_______ml(4ml) of hydrochloric acid cover, digest on a water bath for 15 minutes, uncover and slowly evaporate 
         to dryness on a water bath. Moisten the residue with 1 drop of hydrochloric acid, add ml(10ml) of hot water and digest for 2 minites.
         Add ammonia solution drop wise untill the solution is just alkaline to litmus paper, dilute to________ml(25ml) with water and adjust with dilute acetic 
         acid to a pH between 3.0 to 4.0 by using pH paper. Filter if, necessary, rinse the crucible and the filter with 10 ml of water, combine the filter and washings
         in a 50 ml Nessler Cylinder, dilute with water to about 35 ml and mix.
         </td>
         </tr>
        </table>
          <hr>
         
        <table></table>
         <br><br><br>
        
          <table cellpadding="5" border="0.1">
        <tr>
        <th style="width:25%;"><b></b></th>
         <th style="width:25%;"><b>PREPARED BY</b></th>
          <th style="width:25%;"><b>REVIEWED BY</b></th>
           <th style="width:25%;"><b>APPROVED BY</b></th>
        </tr>
        <tr>
        <td style="width:25%;"><b>Sign/Date</b></td>
         <td style="width:25%;"></td>
          <td style="width:25%;"></td>
           <td style="width:25%;"></td>
        </tr>
        </table>
         <br pagebreak="true"/>';
          $html.=
          '<h4 style="text-align:center;">FINISHED PRODUCT ANALYTICAL REPORT</h4>
         <table cellpading="3" border="0.1">
        <tr>
        <td style="width:30%; text-align:center;"rowspan="3">Product Name:</td>
        <td style="width:30%;"  >Reference STP No.:</td>
         <td style="width:40%;text-align:center;"rowspan="3">Page No.: </td>
         </tr>
         <tr>
          <td style="width:30%;" >Analytical Raw Data No.:</td>
          </tr>
        <tr>
        <td style="width:30%;" >Supersedes No.:</td>
          </tr>
         
           <tr>
         <td style="width:30%; text-align:center;" >Ref SOP No.:</td>
           <td style="width:15%;"></td>
          <td style="width:15%;">Annexure No.:</td>
           <td style="width:40%;"></td>
         </tr>
         </table><br><br>
         <table cellpadding="3" border="0.1">
         <tr>
         <td style="width:50%;">Batch No.:</td>
          <td style="width:50%;">Medicap lot no:</td>
         </tr>
         </table><br>
         <hr>
         <table>
          </table>
          <hr>
         
        <table>
        <tr>
        <td style="width:5%"></td>
        <td style="width:95%"><b>Procedure:</b>
        To each of the cylinders containing standar solution and test solution respectively add_______ml(10ml) of freshly prepared hydrogen
        sulphide solution, mix, dilute to________ml(50ml) with water, allow to stand for 15 minutes and view downwards over a white surface.</td>
        </tr><br>
        <tr>
         <td style="width:5%"></td>
        <td style="width:95%"><b>Observation:</b>___________________________________________________________________</td>
        </tr>
        <hr>
         <table></table>
         <br><br>
        <tr>
        <td style="width:5%"></td>
        <td style="width:95%;">Specification:</td>
        </tr>
        <tr>
        <td style="width:5%"></td>
        <td style="width:95%;">Result:</td>
        </tr>
         </tr>
        <tr>
        <td style="width:5%"></td>
        <td style="width:45%;">Analyzed By/Date:</td>
        </tr>
         <tr>
         <td style="width:5%"></td>
        <td style="width:45%;">Checked By/Date:</td>
        </tr>
        </table>
        <hr>
         <table></table>
         <br><br><br>
        <table cellpadding="5" border="0.1">
          <tr>
         <td style="width: 100%;"><b>Remark:</b></td>
         </tr>
         </table><br><br>
         <table cellpadding="5" border="0.1">
          <tr>
         <th style="width:35%;" rowspan="3"><b>Analyzed By:</b>
           <br><b>Date:</b></th>
          <th style="width:35%;" rowspan="3"><b>Checked By:</b>
          <br><b>Date:</b></th>
        <th style="width:30%;" rowspan="3"><b>Approved By:</b>
         <br><b>Date:</b></th>
        </tr>
       </table><br>';
        $html.='<h4 style="text-align:center;">REVISION HISTORY FOR ANALYTICAL RAW DATA:</h4><br><br>
       <table cellpadding="5" border="0.1">
       <tr>
         <th style="width:20%;" ><b>Revision No.</b></th>
          <th style="width:40%;" ><b>Revision Description</b></th>
        <th style="width:40%;"><b>Effective Date</b></th>
        </tr>
        <tr>
         <td style="width:20%;"></td>
          <td style="width:40%;"></td>
        <td style="width:40%;"></td>
        </tr>
        <tr>
         <td style="width:20%;"></td>
          <td style="width:40%;"></td>
        <td style="width:40%;"></td>
        </tr>
        <tr>
         <td style="width:20%;"></td>
          <td style="width:40%;"></td>
        <td style="width:40%;"></td>
        </tr>
       </table><br><br>
       <table cellpadding="5" border="0.1">
        <tr>
        <th style="width:25%;"><b></b></th>
         <th style="width:25%;"><b>PREPARED BY</b></th>
          <th style="width:25%;"><b>REVIEWED BY</b></th>
           <th style="width:25%;"><b>APPROVED BY</b></th>
        </tr>
         <tr>
        <td style="width:25%;"><b>Sign/Date</b></td>
         <td style="width:25%;"></td>
          <td style="width:25%;"></td>
           <td style="width:25%;"></td>
        </tr>
        </table>
         <br pagebreak="true"/>';
          $html.=
          '<h4 style="text-align:center;">FINISHED PRODUCT ANALYTICAL REPORT</h4>
         <table cellpading="3" border="0.1">
        <tr>
        <td style="width:30%; text-align:center;"rowspan="3">Product Name:</td>
        <td style="width:30%;"  >Reference STP No.:</td>
         <td style="width:40%;text-align:center;"rowspan="3">Page No.: </td>
         </tr>
         <tr>
          <td style="width:30%;" >Analytical Raw Data No.:</td>
          </tr>
        <tr>
        <td style="width:30%;" >Supersedes No.:</td>
          </tr>
         
           <tr>
         <td style="width:30%; text-align:center;" >Ref SOP No.:</td>
           <td style="width:15%;"></td>
          <td style="width:15%;">Annexure No.:</td>
           <td style="width:40%;"></td>
         </tr>
         </table><br><br>
         <table cellpadding="3" border="0.1">
         <tr>
         <td style="width:50%;">Batch No.:</td>
          <td style="width:50%;">Medicap lot no:</td>
         </tr>
         </table><br>
         <hr>
         <table>
          </table>
          <hr>
         
        <table>
        <tr>
        <td style="width:5%"></td>
        <td style="width:95%"><b>Procedure:</b>
        To each of the cylinders containing standar solution and test solution respectively add_______ml(10ml) of freshly prepared hydrogen
        sulphide solution, mix, dilute to________ml(50ml) with water, allow to stand for 15 minutes and view downwards over a white surface.</td>
        </tr><br>
        <tr>
         <td style="width:5%"></td>
        <td style="width:95%"><b>Observation:</b>___________________________________________________________________</td>
        </tr>
        <hr>
         <table></table>
         <br><br>
        <tr>
        <td style="width:5%"></td>
        <td style="width:95%;">Specification:</td>
        </tr>
        <tr>
        <td style="width:5%"></td>
        <td style="width:95%;">Result:</td>
        </tr>
         </tr>
        <tr>
        <td style="width:5%"></td>
        <td style="width:45%;">Analyzed By/Date:</td>
        </tr>
         <tr>
         <td style="width:5%"></td>
        <td style="width:45%;">Checked By/Date:</td>
        </tr>
        </table>
        <hr>
         <table></table>
         <br><br><br>
        <table cellpadding="5" border="0.1">
          <tr>
         <td style="width: 100%;"><b>Remark:</b></td>
         </tr>
         </table><br><br>
         <table cellpadding="5" border="0.1">
          <tr>
         <th style="width:35%;" rowspan="3"><b>Analyzed By:</b>
           <br><b>Date:</b></th>
          <th style="width:35%;" rowspan="3"><b>Checked By:</b>
          <br><b>Date:</b></th>
        <th style="width:30%;" rowspan="3"><b>Approved By:</b>
         <br><b>Date:</b></th>
        </tr>
       </table><br>';
        $html.='<h4 style="text-align:center;">REVISION HISTORY FOR ANALYTICAL RAW DATA:</h4><br><br>
       <table cellpadding="5" border="0.1">
       <tr>
         <th style="width:20%;" ><b>Revision No.</b></th>
          <th style="width:40%;" ><b>Revision Description</b></th>
        <th style="width:40%;"><b>Effective Date</b></th>
        </tr>
        <tr>
         <td style="width:20%;"></td>
          <td style="width:40%;"></td>
        <td style="width:40%;"></td>
        </tr>
        <tr>
         <td style="width:20%;"></td>
          <td style="width:40%;"></td>
        <td style="width:40%;"></td>
        </tr>
        <tr>
         <td style="width:20%;"></td>
          <td style="width:40%;"></td>
        <td style="width:40%;"></td>
        </tr>
       </table><br><br>
       <table cellpadding="5" border="0.1">
        <tr>
        <th style="width:25%;"><b></b></th>
         <th style="width:25%;"><b>PREPARED BY</b></th>
          <th style="width:25%;"><b>REVIEWED BY</b></th>
           <th style="width:25%;"><b>APPROVED BY</b></th>
        </tr>
         <tr>
        <td style="width:25%;"><b>Sign/Date</b></td>
         <td style="width:25%;"></td>
          <td style="width:25%;"></td>
           <td style="width:25%;"></td>
        </tr>
        
        </table>';
       
        $pdf->writeHTML($html, true, false, false, false, '');
       $pdf->addPage();
        $pdf->Output('ARReport_new.pdf', 'I'); 
        
        
        
        
    }
    else if($_GET['type'] == 'ARReportdigital'){
        $sql = "SELECT * FROM testing WHERE ar_no='".$_GET["ar_no"]."'";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $_GET['filename'] = 'A. R. Report'; $_GET['pdftype'] = 'onlyheader';  include("../../pdfimp2.php");
                
                $sql2 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                $result2 = $conn->query($sql2);
                $row2 = $result2->fetch_assoc();
                $html.= '
                <h2 style="text-align:center">A. R. Report</h2>
                <style>td { border:solid 1px BCBBBA;}</style>
                <table cellpadding="5">
                    <thead>
                        <tr>
                            <td style="background-color:#DDDAD9; width:100%; text-align:center;"><b>A. R. Report</b></td>
                        </tr>
                        <tr>
                            <td rowspan="2" style="width:30%;"></td>
                            <td rowspan="2" style="width:30%;"></td>
                            <td style="width:40%;">Copy No :</td>
                        </tr>
                        <tr>
                            <td>Issued By :</td>
                        </tr>
                        <tr>
                            <td>Product Name:</td>
                            <td>A.R. NO.: '.$row['ar_no'].'</td>
                            <td>Batch No. :</td>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="width:20%;"><b>A. R. No.</b></td>
                            <td style="width:30%">'.$row['ar_no'].'</td>
                            <td style="width:20%"><b>Material Code</b></td>
                            <td style="width:30%">'.$row['material_code'].'</td>
                        </tr>
                        <tr>
                            <td rowspan="2"><b>Name of Material / Product</b></td>
                            <td rowspan="2">';
                            $sql4 = "SELECT * FROM material WHERE material_code='".$row['material_code']."'";
                            $result4 = $conn->query($sql4);
                            if($result4->num_rows > 0){
                                while ($row4 = $result4->fetch_assoc()) {
                                    $html.=''.$row4['material_name'].'';
                                }
                            }
                            $html.='</td>
                            <td><b>Receiving no</b></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td><b>Version No.</b></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td><b>Batch No /Lot No</b></td>
                            <td></td>
                            <td><b>Supersedes</b></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td rowspan="2"><b>Batch Size</b></td>
                            <td rowspan="2"></td>
                            <td><b>Mfg Date</b></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td><b>Exp. Date</b></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td rowspan="2"><b>Sample Quntity</b></td>
                            <td rowspan="2"></td>
                            <td><b>Specification Reference No.</b></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td><b>SAP Reference No.</b></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td><b>Sample By /Date</b></td>
                            <td></td>
                            <td><b>Analysis Completion Date</b></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td><b>Reference</b></td>
                            <td></td>
                            <td><b>Effective Date</b></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td colspan="4" style="border:none; text-align:center;"><br><br><b>TESTS</b><br></td>
                        </tr>
                        <tr style="font-weight:bold">
                            <td style="width:7%;">Sr No.</td>
                            <td style="width:20%;">Test</td>
                            <td style="width:20%;">Subtest</td>
                            <td style="width:27%;">Specification</td>
                            <td style="width:26%;">Result</td>
                        </tr>';
                        $sql1 = "SELECT * FROM testing_tests WHERE testing_no='".$row["testing_no"]."'";
                        $result1 = $conn->query($sql1);
                        if($result1->num_rows > 0){
                            $counter = 1;
                            while ($row1 = $result1->fetch_assoc()) {
                            $html.='<tr>
                                    <td>'.$counter++.'</td>
                                    <td><b>'.$row1['test'].'</b></td>
                                    <td>'.$row1['subtest'].'</td>
                                    <td>'.$row1['description'].'</td>
                                    <td>'.$row1['result'].'</td>
                                </tr>';
                            }
                        }
                        $sql1 = "SELECT * FROM testing_tests WHERE testing_no='".$row["testing_no"]."'";
                        $result1 = $conn->query($sql1);
                        if($result1->num_rows > 0){
                            $counter = 1;
                            while ($row1 = $result1->fetch_assoc()) {
                            $html.='
                            <tr>
                                <td style="width:3%; border:none;">'.$counter++.'</td>
                                <td style="width:97%; border:none;"><b>'.$row1['test'].'</b> : </td>
                            </tr>
                            <tr>
                                <td style="border:none;"></td>
                                <td style="border:none;"><b>Observation</b> - '.$row1['observation'].'</td>
                            </tr>
                            <tr>
                                <td style="border:none;"></td>
                                <td style="border:none;">Acceptance criteria:';
                                $sql3 = "SELECT * spec_tests WHERE 	specification_no='".$row['specification_no']."' ";
                                $result3 = $conn->query($sql3);
                                if($result3->num_rows > 0){
                                    while ($row3 = $result3->fetch_assoc()) {
                                        
                                    }
                                }
                                $html.='</td>
                            </tr>
                            <tr>
                                <td style="border:none;"></td>
                                <td style="text-align:center;"><b>The Test complies/ Does not Comply</b></td>
                            </tr>
                            <tr>
                                <td style="border:none;"></td>
                                <td style="width:48%;"><b>Analysed By / Date</b></td>
                                <td style="width:49%;"><b>Checked By / Date</b></td>
                            </tr>';
                        }
                    }
                     $html.='
            </table>
            <div></div>';
            }
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('coa.pdf', 'I');
        }else{
            echo "Invalid Testing No.";
        }
    
 }

$conn->close();
?>

