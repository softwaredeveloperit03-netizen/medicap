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

    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);

 if ($_GET["type"] == "saveProduct") {
        $input = $_POST;
        
        $id = date("YmdHis", $timestamp);
        
        $product_lic = "";
        $fsc = "";
        $copp = "";
        $artwork = "";
        if(isset($_FILES['product_lic'])) {
            $file_tmp =$_FILES['product_lic']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['product_lic']['name'])));
            $file_name = $id."product_lic.".$file_ext;
            $product_lic = $file_name;
            move_uploaded_file($file_tmp,"../upload/product/".$file_name);
        }
        
        if(isset($_FILES['fsc'])) {
            $file_tmp =$_FILES['fsc']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['fsc']['name'])));
            $file_name = $id."fsc.".$file_ext;
            $fsc = $file_name;
            move_uploaded_file($file_tmp,"../upload/product/".$file_name);
        }
        
        if(isset($_FILES['copp'])) {
            $file_tmp =$_FILES['copp']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['copp']['name'])));
            $file_name = $id."copp.".$file_ext;
            $copp = $file_name;
            move_uploaded_file($file_tmp,"../upload/product/".$file_name);
        }
        
        if(isset($_FILES['artwork'])) {
            $file_tmp =$_FILES['artwork']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['artwork']['name'])));
            $file_name = $id."artwork.".$file_ext;
            $artwork = $file_name;
            move_uploaded_file($file_tmp,"../upload/product/".$file_name);
        }
        
        
        
        $sql = "INSERT INTO product (user_no,product_code, product_name, grade, dosage_type, dosage_form, generic_name, packing_style, packing_mode, label_claim, shelf_life,min_shelf, thera, testing_time, retest_period, division, sale_ts, manufactured_under, manufactured_for, manufactured_type, product_lic, fsc, copp, artwork, entry_by, entry_date, hsn, gtin, gst, cess, category, mfg_lic, pack_desc, apperance, storage_condition, market, mrp) VALUES
        ('".$_GET["user_no"]."','".$input["product_code"]."', '".$input["product_name"]."', '".$input["grade"]."', '".$input["dosage_type"]."', '".$input["dosage_form"]."', '".$input["generic_name"]."', '".$input["packing_style"]."','".$input["packing_mode"]."', '".$input["label_claim"]."', '".$input["shelf_life"]."', '".$input["min_shelf"]."', '".$input["thera"]."', '".$input["testing_time"]."', '".$input["retest_period"]."', '".$input["division"]."', '".$input["sale_ts"]."', '".$input["manufactured_under"]."', '".$input["manufactured_for"]."', '".$input["manufactured_type"]."','$product_lic', '$fsc', '$copp', '$artwork','".$_GET["emp_id"]."', '$entry_date', '".$input["hsn"]."', '".$input["gtin"]."', '".$input["gst"]."', '".$input["cess"]."', '".$input["category"]."', '".$input["mfg_lic"]."', '".$input["pack_desc"]."', '".$input["apperance"]."', '".$input["storage_condition"]."', '".$input["market"]."', '".$input["mrp"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
   else if ($_GET['type'] == 'downloadSatbilityProtocol') {
            if($_GET["plant_id"] == 96){
          $_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
         $html= "";
         $html = '
        <table style="width: 540px" border="1">
            <tr style="width: 540px">
            <td style="font-weight: bold; width: 540px; text-align:center;">TITLE: STABILITY STUDY PROTOCOL FOR PARACETAMOL 300 MG WITH DEXTROMETHORPHAN HYDROBROMIDE 12 MG AND PSEUDOEPHEDRINE HYDROCHLORIDE 30 MG SOFT GELATIN CAPSULES [PAR+DEX+PSE (300MG+12MG+ 30MG)]</td>
            </tr>';  
        $html.='</table>';
        $html.='<div></div>';
            $html.='<table>
            <tr>
                <td style="width: 20;"><b>1.0</b></td>
                <td style="width:520;"><b>STABILITY PROTOCOL:</b></td>
                 </tr>';
                  $html.='</table>';
                     $html.='<div></div>';
         $html.='<table border="1" style="width=540px">
                    
                    <tr>
                        <td style="text-align: left;width:170px;line-height:20px;"> Name of Product:</td>
                        <td style="text-align: left;width:370px;line-height:20px;"></td>
                    </tr>
                    <tr>
                        <td style="text-align: left;width:170px;line-height:20px;">Generic Name:</td>
                        <td style="text-align: left;width:370px;line-height:20px;"></td>
                    </tr>
                    <tr>
                        <td style="text-align: left;width:170px;line-height:20px;">Protocol No.:</td>
                        <td style="text-align: left;width:370px;line-height:20px;"></td>
                    </tr>
                    <tr>
                        <td style="text-align: left;width:170px;line-height:20px;">Version No.:</td>
                        <td style="text-align: left;width:370px;line-height:20px;">   </td>                
                    </tr>
                    <tr>
                        <td style="text-align: left;width:170px;line-height:20px;">Stage:</td>
                        <td style="text-align: left;width:370px;line-height:20px;"></td>
                    </tr>
                    <tr>
                        <td style="text-align: left;width:170px;line-height:20px;">Pack Style:</td>
                        <td style="text-align: left;width:370px;line-height:20px;"></td>
                    </tr>
                    <tr>
                        <td style="text-align: left;width:170px;line-height:20px;">Product Code:</td>
                        <td style="text-align: left;width:370px;line-height:20px;"></td>
                    </tr>
                    <tr>
                        <td style="text-align: left;width:170px;line-height:20px;">Finished Product Code:</td>
                        <td style="text-align: left;width:370px;line-height:20px;"></td>
                    </tr>
                    <tr>
                        <td style="text-align: left;width:170px;line-height:20px;">Effective Date:</td>
                        <td style="text-align: left;width:370px;line-height:20px;"></td>
                    </tr>';
                 $html.='</table>';
        $html.='<div></div>';
          $html.='<table>
                <tr>
                    <td style="width: 20;"><b>2.0</b></td>
                    <td style="width:520;"><b>PURPOSE:</b></td>
                </tr>
                <br>
            
                 <tr>
                    <td style="width: 20;"></td>
                    <td style="width:520;">The purpose of this stability study is to demonstrate and establish documented evidence that Paracetamol 300 mg with Dextromethorphan Hydrobromide 12 mg and Pseudoephedrine Hydrochloride 30 mg Soft Gelatin Capsules shall meet the acceptance criteria for Physical, Chemical and Microbiological parameters throughout its shelf life under the influence of specific temperature and humidity.
            
            This would give high degree of assurance about the Quality, Safety, Identity and Efficacy of Paracetamol 300 mg with Dextromethorphan Hydrobromide 12 mg and Pseudoephedrine Hydrochloride 30 mg Soft Gelatin Capsules at a specified Storage condition and throughout its Shelf Life (storage period), within which the Product meets its pre-determined specifications and Quality Attributes on market pack of product.
            Based on the outcome of the study, shelf life & storage condition for the product in proposed container & closure system shall be finalized.
            </td>
                </tr>
            
                <br>
                   <tr>
                        <td style="width: 20;"><b>3.0</b></td>
            
                       <td style="width:520;"><b>	SCOPE:</b></td>
            
                </tr>
                <br>
            
                 <tr>
                    <td style="width: 20;"></td>
                    <td style="width:520;">This stability study protocol is applicable for initial three exhibit scale batches Paracetamol 300 mg with Dextromethorphan Hydrobromide 12 mg and Pseudoephedrine Hydrochloride 30 mg Soft Gelatin Capsules manufactured and packed at Olive Healthcare, Unit II, 163/2, Dabhel, Village, Nani Daman, India – 396210.
            The Batches shall be charged for stability on following Stability conditions based on intended market:
            	Long Term stability study
            •	 (Temperature 25°C ± 2°C, Relative Humidity 60 % ± 5%)
            •	(Temperature 30°C ± 2°C, Relative Humidity 65 % ± 5%)
            •	(Temperature 30°C ± 2°C, Relative Humidity 75 % ± 5%)
            	Accelerated stability study
            •	(Temperature 40°C ± 2°C, Relative Humidity: 75% ± 5%)
            </td>    </tr>
                <br>
                   <tr>
                    <td style="width: 20;"><b>4.0</b></td>
                    <td style="width:520;"><b>REASON FOR STABILITY PROGRAM:</b></td>
                </tr>
                    <br>
            
                <tr>
                    <td style="width: 20;"></td>
                    <td style="width:520;">Exhibit scale validation batch manufacturing of Paracetamol 300 mg with Dextromethorphan Hydrobromide 12 mg and Pseudoephedrine Hydrochloride 30 mg Soft Gelatin Capsules at Manufacturing Site of Olive Healthcare, Unit II, 163/2, Dabhel Village, Nani Daman, India - 396 210.
            (Ref. Change Control No.: CCP-U2-PR-24-0013).
            </td>
                </tr>
                <br>
               <tr>
                    <td style="width: 20;"><b>5.0</b></td>
                    <td style="width:520;"><b>RESPONSIBILITY</b></td>
                </tr>
                <br>
            
                   <tr>
                    <td style="width: 20;"></td>
            
                    <td style="width:520;"><b>Quality Assurance Department (QA):</b></td>
                </tr>
            
             <tr>
                    <td style="width: 20;"></td>
                    <td style="width: 20;">=></td>
                    <td style="width:500;">QA to prepare and review the protocol.</td>
                </tr>
                <tr>
                    <td style="width: 20;"></td>
                    <td style="width: 20;">=></td>
                    <td style="width:500;">QA to perform the sampling as per SOP (OHC/II/SOP/QA/027), stability protocol and submit the samples to QC department.</td>
                </tr>
                <tr>
                    <td style="width: 20;"></td>
                    <td style="width: 20;">=></td>
                    <td style="width:500;">QA to fill the API details in the protocol.</td>
                </tr>
                <tr>
                    <td style="width: 20;"></td>
                    <td style="width: 20;">=></td>
                    <td style="width:500;">To impart training for execution of the protocol.</td>
                </tr>
                <tr>
                    <td style="width: 20;"></td>
                    <td style="width: 20;">=></td>
                    <td style="width:500;">QA to verify the stability charging details.</td>
                </tr>
                <br>
                      <tr>
                    <td style="width: 20;"></td>
            
                    <td style="width:520;"><b>Quality Control  Department (QC):</b></td>
                </tr>
             <tr>
                    <td style="width: 20;"></td>
                    <td style="width: 20;">=></td>
                    <td style="width:500;">QC to review the protocol.</td>
                </tr>
                <tr>
                    <td style="width: 20;"></td>
                    <td style="width: 20;">=></td>
                    <td style="width:500;">QC to charge the samples as per stability protocol in the stability chamber.</td>
                </tr>
                <tr>
                    <td style="width: 20;"></td>
                    <td style="width: 20;">=></td>
                    <td style="width:500;">QC to withdraw the samples as per stability protocol time schedule from the stability chamber.</td>
                </tr>
                <tr>
                    <td style="width: 20;"></td>
                    <td style="width: 20;">=></td>
                    <td style="width:500;">QC to carry out testing of the samples stored for the stability study, after each specified time interval, as per the approved stability protocol and specifications.</td>
                </tr>
                <tr>
                    <td style="width: 20;"></td>
                    <td style="width: 20;">=></td>
                    <td style="width:500;">To summarize the analytical data at every stated time-point.</td>
                </tr>
                 <tr>
                    <td style="width: 20;"></td>
                    <td style="width: 20;">=></td>
                    <td style="width:500;">To check correctness and completeness of analytical data-before submission to QA.</td>
                </tr>
                 <tr>
                    <td style="width: 20;"></td>
                    <td style="width: 20;">=></td>
                    <td style="width:500;">To prepare the report and evaluate the results..</td>
                </tr>
                 <tr>
                    <td style="width: 20;"></td>
                    <td style="width: 20;">=></td>
                    <td style="width:500;">To evaluate stability study results.</td>
                </tr>
                    <br>
                   <tr>
                    <td style="width: 20;"></td>
            
                    <td style="width:520;"><b>Microbiology:</b></td>
                </tr>
             <tr>
                    <td style="width: 20;"></td>
                    <td style="width: 20;">=></td>
                    <td style="width:500;">Microbiology to carry out testing of the stored samples retained for the stability study, after each specified time interval, as per the approved stability protocol and specifications.</td>
                </tr>
                <tr>
                    <td style="width: 20;"></td>
                    <td style="width: 20;">=></td>
                    <td style="width:500;">To summarize the analytical data at each applicable time-point. </td>
                </tr>
                <tr>
                    <td style="width: 20;"></td>
                    <td style="width: 20;">=></td>
                    <td style="width:500;">To check correctness and completeness of analytical data-before submission to QC for the stability study summary.</td>
                </tr>
             <br>
                   <tr>
                    <td style="width: 20;"></td>
            
                    <td style="width:520;"><b>Packing:</b></td>
                </tr>
             <tr>
                    <td style="width: 20;"></td>
                    <td style="width: 20;">=></td>
                    <td style="width:500;">To review the protocol.</td>
                </tr>
                <tr>
                    <td style="width: 20;"></td>
                    <td style="width: 20;">=></td>
                    <td style="width:500;">To ensure that the stability samples are collected as per the stability protocol.</td>
                </tr>
                 <br>
                   <tr>
                    <td style="width: 20;"></td>
            
                    <td style="width:520;"><b>Head Quality:</b></td>
                </tr>
                <tr>
                    <td style="width: 20;"></td>
                    <td style="width: 20;">=></td>
                    <td style="width:500;">To approve the stability protocol and to authorize the summary report.</td>
                </tr>
                 <br>
             <tr>
                    <td style="width: 20;"><b>6.0</b></td>
                    <td style="width:520;"><b>TRAINING DETAILS:</b></td>
                </tr>
                    <br>
             <tr>
                    <td style="width: 20;"></td>
                    <td style="width:520;">Training shall be imparted for all concerned personnel prior to execution of protocol.</td>    
                    </tr>
                            <br>
                 <tr>
                        <td style="width: 20;"><b>7.0</b></td>
                       <td style="width:520;"><b>PRODUCT DETAILS:  </b></td>
                </tr>
                <br>
             <table border="1" style="width=540px">
            <tr>
                <td style="line-height:20px;text-align:left;">Name of the product:</td>
                <td style="line-height:20px;text-align:left;"></td>
            </tr>
            <tr>
                <td style="line-height:20px;text-align:left;">Product Code:</td>
                <td style="line-height:20px;text-align:left;"></td>
            </tr>
            <tr>
                <td style="line-height:20px;text-align:left;">Finished Product Code:</td>
                <td style="line-height:20px;text-align:left;"></td>
            </tr>
            <tr>
                <td style="line-height:20px;text-align:left;">Shelf life:</td>
                <td style="line-height:20px;text-align:left;"></td>
            </tr>
            <tr>
                <td style="line-height:20px;text-align:left;">Label claim:</td>
                <td style="line-height:20px;text-align:left;"></td>
            </tr>
            <tr>
                <td style="line-height:20px;text-align:left;">Primary pack:</td>
                <td style="line-height:20px;text-align:left;"></td>
            </tr>
            <tr>
                <td style="line-height:20px;text-align:left;">Proposed Labeled Storage Condition:</td>
                <td style="line-height:20px;text-align:left;"></td>
            </tr>
            <tr>
                <td style="line-height:20px;text-align:left;">Manufactured by:</td>
                <td style="line-height:20px;text-align:left;"></td>
            </tr>
            </table>';
              $html.='<div></div>';
              $html.='<table>
            <tr>
                <td style="line-height:20px;width:20px;text-align:left;font-weight:bold;">8.0</td>
                <td style="line-height:20px;width:520px;text-align:left; font-weight:bold;">ACTIVE PHARMACEUTICAL INGREDIENT (API) DETAILS*:</td>
                </tr>';
                $html.='</table>';
                  $html.=' <table border="1" style="width=540px">
            
            <tr>
                <td style="line-height:20px;text-align:left;" colspan="3">Product Batch No.</td>
                <td style="line-height:20px;text-align:left;"></td>
                
                </tr>
            <tr>
                <td style="line-height:20px;text-align:left;">Name of the API </td>
                <td style="line-height:20px;text-align:center;">Paracetamol Ph. Eur.</td>
                <td style="line-height:20px;text-align:center;">Dextromethorphan HBr Ph. Eur.</td>
                <td style="line-height:20px;text-align:center;">Pseudoephedrine HCl Ph. Eur.</td>
            
                </tr>
            <tr>
                <td style="line-height:20px;text-align:left;">Item Code</td>
                <td style="line-height:20px;text-align:left;"></td>
                <td style="line-height:20px;text-align:left;"></td>
                <td style="line-height:20px;text-align:left;"></td>
            
                </tr>
            <tr>
                <td style="line-height:20px;text-align:left;">B. No. / Lot No.</td>
                <td style="line-height:20px;text-align:left;"></td>
                <td style="line-height:20px;text-align:left;"></td>
                <td style="line-height:20px;text-align:left;"></td>
            
                </tr>
            <tr>
                <td style="line-height:20px;text-align:left;">Mfg.</td>
                <td style="line-height:20px;text-align:left;"></td>
                <td style="line-height:20px;text-align:left;"></td>
                <td style="line-height:20px;text-align:left;"></td>
            
                </tr>
            <tr>
                <td style="line-height:20px;text-align:left;">Exp.</td>
                <td style="line-height:20px;text-align:left;"></td>
                <td style="line-height:20px;text-align:left;"></td>
                <td style="line-height:20px;text-align:left;"></td>
            
                </tr>
            <tr>
                <td style="line-height:20px;text-align:left;">A. R. No.</td>
                <td style="line-height:20px;text-align:left;"></td>
                <td style="line-height:20px;text-align:left;"></td>
                <td style="line-height:20px;text-align:left;"></td>
            
                </tr>
            <tr>
                <td style="line-height:20px;text-align:left;">Name of the Manufacturer</td>
                <td style="line-height:20px;text-align:left;"></td>
                <td style="line-height:20px;text-align:left;"></td>
                <td style="line-height:20px;text-align:left;"></td>
                </tr>
                <tr>
                <td style="line-height:20px;text-align:left;">Details Entered By (QA)</td>
                <td style="line-height:20px;text-align:left;"></td>
                <td style="line-height:20px;text-align:left;"></td>
                <td style="line-height:20px;text-align:left;"></td>
                </tr>
                <tr>
                <td style="line-height:20px;text-align:left;">Details Checked By (QA)</td>
                <td style="line-height:20px;text-align:left;"></td>
                <td style="line-height:20px;text-align:left;"></td>
                <td style="line-height:20px;text-align:left;"></td>
                </tr>';
                    $html.='</table>';
                    $html.='<div></div>';
                      $html.='<table>
            <tr>
                <td style="line-height:20px;width:20px;text-align:left;font-weight:bold;">9.0</td>
                <td style="line-height:20px;width:520px;text-align:left; font-weight:bold;">PLACEBO DETAILS:</td>
                </tr>
                <tr>
                <td style="line-height:20px;width:20px;text-align:left;"></td>
                <td style="line-height:20px;width:30px;text-align:left;">9.1</td>
                <td style="line-height:20px;width:490px;text-align:left; font-weight:bold;">Placebo for Paracetamol 300 mg with Dextromethorphan Hydrobromide 12 mg and Pseudoephedrine Hydrochloride 30 mg Soft Gelatin Capsules (Without Paracetamol): </td>
                </tr>';
                        $html.='</table>';
                        $html.='<table border="1" style="width=540px">
            <tr>
                <td style="line-height:20px;">Name of the product:</td>
                <td style="line-height:20px;"></td>
            </tr>
            
            <tr>
                <td style="line-height:20px;">Finished Product Code:</td>
                <td style="line-height:20px;"></td>
            </tr>
            
            <tr>
                <td style="line-height:20px;">Primary pack:</td>
                <td style="line-height:20px;"></td>
            </tr>
            
            <tr>
                <td style="line-height:20px;">Manufactured by:</td>
                <td style="line-height:20px;"></td>
            </tr>';
                        $html.='</table>';
                          $html.='<div></div>';
                        $html.='<table>
            <tr>
                <td style="line-height:20px;width:20px;text-align:left;font-weight:bold;">10.0</td>
                <td style="line-height:20px;width:520px;text-align:left; font-weight:bold;"> PRIMARY PACKING MATERIAL DETAILS:</td>
                </tr>';
                $html.='</table>';
                
                            $html.='  <table border="1" style="width=540px">
                            <tr>
                                <th style="text-align:left;">Particulars</th>
                                <th style="text-align:left;">Item Code</th>
                                <th style="text-align:left;">Details</th>
                            </tr>

                            <tr>
                                <td style="text-align:left;" rowspan="2">Primary Packing Material</td>
                                <td style="text-align:left;">6135000325</td>
                                <td style="text-align:left;">Plain Alu. Foil 0.030 x 250 mm</td>
                            </tr>
                            <tr>
                                <td style="text-align:left;">6535000274</td>
                                <td style="text-align:left;">PVDC Coated PVC Film 0.350 x 252 mm 90 GSM</td>
                              
                            </tr>';
                             $html.='</table>';
                             $html.='<div></div>';
                              $html.='<table>
            <tr>
                <td style="line-height:20px;width:20px;text-align:left;font-weight:bold;">10.1</td>
                <td style="line-height:20px;width:520px;text-align:left; font-weight:bold;">  Primary Packing Material used*:</td>
                </tr>';
                $html.='</table>';
                             $html.='  <table border="1" style="width=540px">
                              <tr>
                                <th style="text-align:left;line-height:20px;">Particulars</th>
                                <th style="text-align:left;line-height:20px;" colspan="2">1 st Batch</th>
                                <th style="text-align:left;line-height:20px;" colspan="2">2 nd Batch</th>
                                <th style="text-align:left;line-height:20px;" colspan="2">3 rd Batch</th>
                            </tr>
                            <tr>
                                <th>Product Batch
                                    No.</th>
                                <td colspan="2"></td>
                                <td colspan="2"></td>
                                <td colspan="2"></td>
                            </tr>
                            <tr>
                                <th>Item Code</th>
                                <td style="text-align:left;line-height:20px;"></td>
                                <td style="text-align:left;line-height:20px;"></td>
                                <td style="text-align:left;line-height:20px;"></td>
                                <td style="text-align:left;line-height:20px;"></td>
                                <td style="text-align:left;line-height:20px;"></td>
                                <td style="text-align:left;line-height:20px;"></td>
                            </tr>
                            <tr>
                                <th>B. No./Lot No.</th>
                                <td style="text-align:left;line-height:20px;"></td>
                                <td style="text-align:left;line-height:20px;"></td>
                                <td style="text-align:left;line-height:20px;"></td>
                                <td style="text-align:left;line-height:20px;"></td>
                                <td style="text-align:left;line-height:20px;"></td>
                                <td style="text-align:left;line-height:20px;"></td>
                            </tr>
                            <tr>
                                <th>A. R. No.</th>
                                <td style="text-align:left;line-height:20px;"></td>
                                <td style="text-align:left;line-height:20px;"></td>
                                <td style="text-align:left;line-height:20px;"></td>
                                <td style="text-align:left;line-height:20px;"></td>
                                <td style="text-align:left;line-height:20px;"></td>
                                <td style="text-align:left;line-height:20px;"></td>
                            </tr>
                            <tr>
                                <th>Name of the
                                    manufacturer</th>
                                <td style="text-align:left;line-height:20px;"></td>
                                <td style="text-align:left;line-height:20px;"></td>
                                <td style="text-align:left;line-height:20px;"></td>
                                <td style="text-align:left;line-height:20px;"></td>
                                <td style="text-align:left;line-height:20px;"></td>
                                <td style="text-align:left;line-height:20px;"></td>
                            </tr>
                            <tr>
                                <th>Details Entered
                                    By (QA)</th>
                                <td style="text-align:left;line-height:20px;"></td>
                                <td style="text-align:left;line-height:20px;"></td>
                                <td style="text-align:left;line-height:20px;"></td>
                                <td style="text-align:left;line-height:20px;"></td>
                                <td style="text-align:left;line-height:20px;"></td>
                                <td style="text-align:left;line-height:20px;"></td>
                            </tr>
                            <tr>
                                <th>Details Checked
                                    By (QA)</th>
                                <td style="text-align:left;line-height:20px;"></td>
                                <td style="text-align:left;line-height:20px;"></td>
                                <td style="text-align:left;line-height:20px;"></td>
                                <td style="text-align:left;line-height:20px;"></td>
                                <td style="text-align:left;line-height:20px;"></td>
                                <td style="text-align:left;line-height:20px;"></td>
                            </tr>';
                $html.='</table>';
                 $html.='<div></div>';
                 $html.='<table>
            <tr>
                <td style="line-height:20px;width:20px;text-align:left;font-weight:bold;">11.0</td>
                <td style="line-height:20px;width:520px;text-align:left; font-weight:bold;">STORAGE CONDITION AND FREQUENCY FOR STABILITY STUDY:</td>
                </tr>';
                $html.='</table>';
                 $html.='<div></div>';
                 $html.='<table border="1" style="width=540px">
            <tr>
                                <th style="text-align:left;">Sr. No.</th>
                                <th style="text-align:left;">Stability Study</th>
                                <th style="text-align:left;">Storage Condition</th>
                                <th style="text-align:left;">Storage Period</th>
                                <th style="text-align:left;">Testing Frequency
                                    (months)</th>
                            </tr>

                            <tr>
                                <td style="text-align:left;">1</td>
                                <td style="text-align:left;">Long Term
                                    condition</td>
                                <td style="text-align:left;">Temperature: 25°C ± 2°C
                                    Relative Humidity: 60% ± 5%</td>
                                <td style="text-align:left;">For 60 Months</td>
                                <td style="text-align:left;">0*, 3, 6, 9, 12*, 18, 24*, 30*,
                                    36*, 48*, 60*</td>
                            </tr>
                            <tr>
                                <td style="text-align:left;">2</td>
                                <td style="text-align:left;">Long Term
                                    condition#</td>
                                <td style="text-align:left;">Temperature: 30°C ± 2°C
                                    Relative Humidity: 65% ± 5%</td>
                                <td style="text-align:left;">For 60 Months</td>
                                <td style="text-align:left;">0*, 3, 6, 9, 12*, 18, 24*, 30*,
                                    36*, 48*, 60*</td>
                            </tr>
                            <tr>
                                <td style="text-align:left;">3</td>
                                <td style="text-align:left;">Long Term
                                    condition</td>
                                <td style="text-align:left;">Temperature: 30°C ± 2°C
                                    Relative Humidity: 75% ± 5%</td>
                                <td style="text-align:left;">For 60 Months</td>
                                <td style="text-align:left;">0*, 3, 6, 9, 12*, 18, 24*, 30*,
                                    36*, 48*, 60*</td>
                            </tr>
                            <tr>
                                <td style="text-align:left;">4</td>
                                <td style="text-align:left;">Accelerated
                                    condition</td>
                                <td style="text-align:left;">Temperature: 40°C ± 2°C
                                    Relative Humidity: 75% ± 5%</td>
                                <td style="text-align:left;">For 6 Months</td>
                                <td style="text-align:left;">0*, 3, 6*</td>
                            </tr>';
                $html.='</table>';  
                  $html.='<div></div>';
                 $html.='<table>
            <tr>
                <td style="line-height:20px;width:20px;text-align:left;font-weight:bold;">12.0</td>
                <td style="line-height:20px;width:520px;text-align:left; font-weight:bold;"> STABILITY STUDY SAMPLE QUANTITY:</td>
                </tr>';
                $html.='</table>';  $html.='<div></div>';
                 $html.='<table border="1" style="width=540px">
          <tr>
                                <th style="text-align:left;  width: 10%;">Sr. No.</th>
                                <th style="text-align:left; width: 45%;">Test Parameters</th>
                                <th style="text-align:left; width: 45%;">Sample Quantity</th>
                            </tr>

                            <tr>
                                <td style="text-align:left;">1</td>
                                <td style="text-align:left;">Description</td>
                                <td style="text-align:left;">10</td>
                            </tr>
                            <tr>
                                <td rowspan="2" style="text-align:left;">2</td>
                                <td style="text-align:left;">Identification Test for Paracetamol</td>
                                <td></td>
                            </tr>
                            <tr>
                                <td style="text-align:left;">By HPLC</td>
                                <td style="text-align:left;">Nil (Results obtain directly from
                                    chromatogram of Assay)</td>
                            </tr>
                            <tr>
                                <td rowspan="2" style="text-align:left;">3</td>
                                <td style="text-align:left;"> Identification Test for Dextromethorphan HBr</td>
                                <td></td>
                            </tr>
                            <tr>
                                <td style="text-align:left;">By HPLC</td>
                                <td style="text-align:left;">Nil (Results obtain directly from
                                    chromatogram of Assay)</td>
                            </tr>
                            <tr>
                                <td rowspan="2" style="text-align:left;">4</td>
                                <td style="text-align:left;">3. Identification Test for Pseudoephedrine HCL</td>
                                <td></td>
                            </tr>
                            <tr>
                                <td style="text-align:left;">By HPLC</td>
                                <td style="text-align:left;">Nil (Results obtain directly from
                                    chromatogram of Assay)</td>
                            </tr>
                            <tr>
                                <td style="text-align:left;">5</td>
                                <td style="text-align:left;">
                                    <h4>Dissolution (By HPLC)</h4>
                                    <ul>
                                        <li>Paracetamol</li>
                                        <li>Dextromethorphan HBr</li>
                                        <li>Pseudoephedrine Hydrochloride</li>
                                    </ul>
                                </td>
                                <td style="text-align:left;">18</td>
                            </tr>
                            <tr>
                                <td style="text-align:left;">6</td>
                                <td style="text-align:left;">Assay By HPLC</td>
                                <td style="text-align:left;">20</td>
                            </tr>
                            <tr>
                                <td rowspan="3" style="text-align:left;">7</td>
                                <td style="text-align:left;">Related substances (By HPLC) for Paracetamol</td>
                                <td rowspan="3" style="text-align:left;">80</td>
                            </tr>
                            <tr>
                                <td>
                                    Related substances (By HPLC) for Dextromethorphan HBr
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    Related substances (By HPLC) for Pseudoephedrine HCl
                                </td>
                            </tr>
                            <tr>
                                <td style="text-align:left;">8</td>
                                <td style="text-align:left;">Disintegration Time</td>
                                <td style="text-align:left;">06</td>
                            </tr>
                            <tr>
                                <td style="text-align:left;">9</td>
                                <td style="text-align:left;">Loss on drying (% m/m)</td>
                                <td style="text-align:left;">05</td>
                            </tr>
                            <tr>
                                <td style="text-align:left;">10</td>
                                <td style="text-align:left;"><h4>Microbial Enumeration</h4>
                                <ul>
                                    <li>Total Aerobic Microbial Count (TAMC)</li>
                                    <li>Total Combined Yeasts/Mold Count (TYMC)</li>
                                    <li>Specified microorganisms:
                                        <ul>
                                            <li>Escherichia coli</li>
                                        </ul>
                                    </li>
                                </ul></td>
                                <td  style="text-align:left;">10</td>
                            </tr>
                            <tr>
                                <td colspan="2" style="text-align:left;"><b>Total Quantity:
                                    </b></td>
                                <td style="text-align:left;"><b>150 Capsules for Chemical Analysis and 10 capsules for
                                        Microbial Analysis.</b>
                                </td>
                            </tr>';
                $html.='</table>';
                  $html.='<div></div>';
                 $html.='<table>
            <tr>
                <td style="line-height:20px;width:20px;text-align:left;font-weight:bold;">13.0</td>
                <td style="line-height:20px;width:520px;text-align:left; font-weight:bold;"> STABILITY CHARGING DETAILS:</td>
                </tr>';
                $html.='</table>';
                  $html.='<div></div>';
                             $html.='<table>
                             <ul>
                            <li> <b>Attachment - I :</b> Stability Study Sample Quantity &amp; Schedule for Paracetamol 300 mg
                                with Dextromethorphan
                                Hydrobromide 12 mg and Pseudoephedrine Hydrochloride 30 mg Soft Gelatin Capsules.</li>
                            <li><b>Attachment - II :</b> Stability Study Sample Quantity &amp; Schedule for Placebo for
                                Paracetamol 300 mg with
                                Dextromethorphan Hydrobromide 12 mg and Pseudoephedrine Hydrochloride 30 mg Soft Gelatin
                                Capsules (Without
                                Paracetamol).</li>
                            <li> <b>Attachment - III :</b> Stability Study Sample Quantity &amp; Schedule for Placebo for
                                Paracetamol 300 mg with
                                Dextromethorphan Hydrobromide 12 mg and Pseudoephedrine Hydrochloride 30 mg Soft Gelatin
                                Capsules (Without
                                Dextromethorphan HBr).</li>
                            <li> <b>Attachment - IV :</b>  Stability Study Sample Quantity &amp; Schedule for Placebo for
                                Paracetamol 300 mg with
                                Dextromethorphan Hydrobromide 12 mg and Pseudoephedrine Hydrochloride 30 mg Soft Gelatin
                                Capsules (Without
                                Pseudoephedrine Hydrochloride).</li>
                        </ul>';
                $html.='</table>';
                  $html.='<div></div>';
                  $html.='<table>
            <tr>
                <td style="line-height:20px;width:20px;text-align:left;font-weight:bold;">14.0</td>
                <td style="line-height:20px;width:520px;text-align:left; font-weight:bold;"> DETAILS OF DEVIATIONS, OUT OF SPECIFICATION, AND OUT OF TREND:</td>
                </tr>';
                $html.='</table>';
                  $html.='<div></div>';
                  $html.='<table border="1" style="width=540px">
            <tr>
                                <th>Stability Condition</th>
                                <th>Station No.</th>
                                <th>Event Type and Event No.</th>
                                <th>Description of Event</th>
                                <th>Entered By Sign/Date</th>
                            </tr>
                            <tr>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>';
                $html.='</table>';
                  $html.='<div></div>';
                 $html.='<table>
            <tr>
                <td style="line-height:20px;width:20px;text-align:left;font-weight:bold;">15.0</td>
                <td style="line-height:20px;width:520px;text-align:left; font-weight:bold;"> CHANGE CONTROL:</td>
                </tr>';
                $html.='</table>';
                 $html.='<table border="1" style="width=540px">
            <tr>
                                <th>Change control No.</th>
                                <th>Description of Change</th>
                                <th>Remark</th>
                                <th>Entered By
                                    Sign and Date</th>

                            </tr>
                            <tr>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>

                            </tr>';
                $html.='</table>';
                  $html.='<div></div>';
                  $html.='<table>
            <tr>
                <td style="line-height:20px;width:20px;text-align:left;font-weight:bold;">16.0</td>
                <td style="line-height:20px;width:520px;text-align:left; font-weight:bold;"> SUMMARY AND CONCLUSION:</td>
                </tr>';
                $html.='</table>';
                  $html.='<div></div>';
                $html.='<table border="1" style="width=540px">
           <tr>
                                <td colspan="7">Observation</td>
                            </tr>
                            <tr>
                                <td>
                                    Batch No.
                                </td>
                                <td colspan="6"></td>
                            </tr>
                            <tr>
                                <td rowspan="2">Stability Condition</td>
                                <td>Temperature</td>
                                <td>25°C± 2°C</td>
                                <td>30°C ± 2°C</td>
                                <td>30°C ± 2°C</td>
                                <td>40°C ± 2°C</td>
                                <td rowspan="2">Sign/Date</td>
                            </tr>
                            <tr>
                                <td>Relative Humidity</td>
                                <td>60% ± 5%</td>
                                <td>65% ± 5%</td>
                                <td>
                                    75% ± 5%
                                </td>
                                <td>
                                    75% ± 5%
                                </td>
                            </tr>
                           
                            <tr>
                                <td colspan="2">Initial*</td>
                                <td colspan="1"></td>
                                <td colspan="1"></td>
                                <td colspan="1"></td>
                                <td colspan="1"></td>
                                <td colspan="1"></td>
                            </tr>

                            <tr>
                                <td colspan="2">3M</td>
                                <td colspan="1"></td>
                                <td colspan="1"></td>
                                <td colspan="1"></td>
                                <td colspan="1"></td>
                                <td colspan="1"></td>
                            </tr>
                            <tr>
                                <td colspan="2">6M</td>
                                <td colspan="1"></td>
                                <td colspan="1"></td>
                                <td colspan="1"></td>
                                <td colspan="1"></td>
                                <td colspan="1"></td>
                            </tr>
                            <tr>
                                <td colspan="2">9M</td>
                                <td colspan="1"></td>
                                <td colspan="1"></td>
                                <td colspan="1"></td>
                                <td colspan="1">NA</td>
                                <td colspan="1"></td>
                            </tr>
                            <tr>
                                <td colspan="2">12M</td>
                                <td colspan="1"></td>
                                <td colspan="1"></td>
                                <td colspan="1"></td>
                                <td colspan="1">NA</td>
                                <td colspan="1"></td>
                            </tr>
                            <tr>
                                <td colspan="2">18M</td>
                                <td colspan="1"></td>
                                <td colspan="1"></td>
                                <td colspan="1"></td>
                                <td colspan="1">NA</td>
                                <td colspan="1"></td>
                            </tr>
                            <tr>
                                <td colspan="2">24M</td>
                                <td colspan="1"></td>
                                <td colspan="1"></td>
                                <td colspan="1"></td>
                                <td colspan="1">NA</td>
                                <td colspan="1"></td>
                            </tr>
                            <tr>
                                <td colspan="2">30M</td>
                                <td colspan="1"></td>
                                <td colspan="1"></td>
                                <td colspan="1"></td>
                                <td colspan="1">NA</td>
                                <td colspan="1"></td>
                            </tr>
                            <tr>
                                <td colspan="2">36M</td>
                                <td colspan="1"></td>
                                <td colspan="1"></td>
                                <td colspan="1"></td>
                                <td colspan="1">NA</td>
                                <td colspan="1"></td>
                            </tr>
                            <tr>
                                <td colspan="2">48M</td>
                                <td colspan="1"></td>
                                <td colspan="1"></td>
                                <td colspan="1"></td>
                                <td colspan="1">NA</td>
                                <td colspan="1"></td>
                            </tr>
                            <tr>
                                <td colspan="2">60M</td>
                                <td colspan="1"></td>
                                <td colspan="1"></td>
                                <td colspan="1"></td>
                                <td colspan="1">NA</td>
                                <td colspan="1"></td>
                            </tr>';
                $html.='</table>';
                  $html.='<div></div>';
                $html.='<table>
                    <tr>
                        <td style="line-height:20px;width:20px;text-align:left;font-weight:bold;">17.0</td>
                        <td style="line-height:20px;width:520px;text-align:left; font-weight:bold;"> REVISION HISTORY:</td>
                        </tr>';
                        $html.='</table>';
                        $html.='<table border="1" style="width=540px">
                     <tr>
                                <th>Version No.</th>
                                <th>Reason for Revision</th>
                                <th>Effective Date</th>
                            </tr>
                            <tr>
                                <td>00</td>
                                <td>New Stability Protocol for exhibit scale validation batch manufacturing of Paracetamol 300 mg with Dextromethorphan
                                Hydrobromide 12 mg and Pseudoephedrine Hydrochloride 30 mg Soft Gelatin Capsules.
                                (Reference Change Control No.: CCP-U2-PR-24-0013).</td>
                                <td></td>
                            </tr>';
                $html.='</table>';
        $EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('download.pdf', 'I');
            }
    }
}
 else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
?>