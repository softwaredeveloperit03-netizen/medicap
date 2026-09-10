<?php 



// error_reporting(E_ALL);
// ini_set('display_errors', 1);


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
         if($_GET['type'] == 'BMR2'){
               $_GET['filename'] = 'A. R. Report'; $_GET['pdftype'] = 'onlyheader1'; include("../pdfimp1.php");
               $output = array();
        $fifo_method='';
        $sql ="select param_value from software_customization where param_label ='Fifo Method' 
        and module ='Dispensing Activity' and plant_id = '".$_GET["plant_id"]."' ";
      
       $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
            $fifo_method = $row["param_value"];
                
            }
            
        }
        $lod_status='';
        $assay_status='';
         $sql ="SELECT a.id,a.oprpccp1,a.oprpccp2,a.blending,a.sifting,a.batch_number,a.work_order_no,a.rm_qa_dislc_date as palnned_by,
         a.approved_by, a.lod_status,a.calculation_type,a.batch_commence_date,a.stage_checked_by,a.tr_to_packing_dept_by,a.batch_complete_date,
             a.stability,a.stability_reason, a.process_validation,a.qa_person,a.qa_date,a.no_of_lots,uf.min_per as 
             min_yeild,uf.max_per as max_yeild,
             a.approved_by, b.plan_no, b.bfr_no,b.mfr_no,b.product_code,b.batch_size, p.product_name,
             p.product_type,p.shelf_life, p.dosage_form,
             p.grade,b.pack_size,b.pack_unit,a.dispense_request_sent_by, a.dispense_request_sent_on,
             a.dispensing_status,rm_disp_completed_by, ";
             if($_GET["material_type"] == 'Raw Material'){
                $sql = $sql." a.rm_qa_dislc_status as lc_status,a.bmr_no,a.mfg_date,a.exp_date,a.rm_qa_dislc_by as lc_by,a.rm_qa_dislc_date as lc_date,a.bmr_status ";
             }else{
                $sql = $sql." a.pm_qa_dislc_status as lc_status,a.bmr_no,a.mfg_date,a.exp_date,a.pm_qa_dislc_by as lc_by,a.pm_qa_dislc_date as lc_date,a.bmr_status ";
             }
             
             
            $sql = $sql."FROM mfg_work_order_hdr a JOIN batch_planning b on a.batch_plan_id = 
             b.id and a.plant_id = b.plant_id JOIN product p on b.product_code = p.product_code and 
             b.plant_id = p.plant_id 
             left join unitformula uf on uf.mfr_no= b.mfr_no and uf.plant_id= b.plant_id
             where  a.plant_id ='".$_GET["plant_id"]."'  
             and dispensing_status ='Request Sent' and b.plan_no='" . $_GET["plan_no"] . "'   "; 
            // if($_GET["material_type"] == 'Raw Material'){
            //     //$sql = $sql." and rm_disp_completed_by!='' and rm_qa_dislc_status='Approved' ";
                       
            // //new vivek 06.03.23
            //   $sql = $sql." and rm_qa_dislc_status='Approved' ";
            //                         //old  06.03.23
            //   // $sql = $sql." and rm_disp_completed_by!='' and rm_qa_dislc_status='Approved' ";
            // }
            // else{
            //      $sql = $sql." and pm_store_status_entry_by!='' and pm_qa_dislc_status='Approved' ";
            // }

           $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $calculation_type = $row['calculation_type'];
                $row["checkpoints"] = json_decode($row["checkpoints"]);
                $output1 = array();
                $sql1 = "select a.*,b.avbl_stock  from (SELECT a.*,b.material_type,b.category,b.material_subtype,b.material_name,
                wd.lod_status,wd.assay_status,
                IFNULL(dd.id,0) as dispence_id,dd.qa_status,dd.prod_status,dd.qa_checking,dd.prod_checking FROM work_order_batch_lots  a 
                 join mfg_work_order_hdr c on a.work_order_id = c.id
                 left join mfg_work_order_dtl wd on wd.work_order_id = c.id and wd.material_code = a.material_code 
                 left join material b on a.material_code = b.material_code and c.plant_id = b.plant_id 
                 left join dispensing_details_hdr dd on a.id = dd.lot_id
                 where a.work_order_id = '".$row["id"]."') as a left join
                 (SELECT material_code,sum(qty) as avbl_stock FROM stock_book
                 WHERE plant_id='".$_GET["plant_id"]."' and material_code in(SELECT material_code from work_order_batch_lots where work_order_id ='".$row["id"]."') 
                 GROUP by material_code) as b on a.material_code = b.material_code and a.material_type='".$_GET["material_type"]."' ";
                      
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                      $output2 = array();
                      $category = $row1["category"];
                      $sql2="SELECT a.*, IFNULL(b.issued_qty,0) as issued_qty, (a.qty-IFNULL(b.issued_qty,0)) as balance_qty,
                        floor((a.qty-IFNULL(b.issued_qty,0))/a.pack_size) as intact_containers,
                        (a.qty-IFNULL(b.issued_qty,0))-(a.pack_size*(floor((a.qty-IFNULL(b.issued_qty,0))/a.pack_size))) as loose_Qty, 
                        case when '".$calculation_type."' = 'Lod Basis' AND  '".$category."' = 'Active' then  ROUND((a.qty-IFNULL(b.issued_qty,0)) -(((a.qty-IFNULL(b.issued_qty,0))*lod_per)/100),2) else 0 end as dry_qty,
                        case when '".$calculation_type."' ='Assay Basis' AND '".$category."' = 'Active' then  ROUND((a.qty-IFNULL(b.issued_qty,0)) -(((a.qty-IFNULL(b.issued_qty,0))*assay)/100),2) else 0 end as pure_qty
                        from (SELECT IFNULL(SUM(qty), 0) as qty, ar_no ,pack_size ,batch_no,containers,lod_per,assay
                        FROM stock_book
                        WHERE material_code='".$row1["material_code"]."' AND status='Approved' GROUP BY ar_no,pack_size, batch_no,containers,lod_per,assay) a
                        left join (SELECT ar_no, IFNULL(SUM(qty), 0) as issued_qty FROM material_issue Group by ar_no) b on a.ar_no = b.ar_no"; 
  
                          $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $output2[] = $row2;
                            }
                        }
                        $row1["available_ars"] = $output2;
                        
                        $row1["containers"] = json_decode($row1["containers"]);
                        $row1["ars"] = json_decode($row1["ars"]);
                        if ($row1["status"] == "pending") {
                            $flag = 1;
                        }
                      
                                      $output1[] = $row1;
                    }}
                 //   echo $sql;
       $html ="";
         $html.= '
    <table cellpadding="5" border="1">
    <tr>
        <td colspan="4" style=" text-align:centre;">BATCH MANUFACTURING RECORD</td>
    </tr>
    <tr>
        <td colspan="2">Product: '.$row['product_name'].'</td>
        <td>B.No.:  '.$row['batch_number'].'</td>
        <td>Copy No.: '.$row['id'].'</td>
    </tr>
    <tr>
        <td>Standard Batch Size :  '.$row['batch_size'].'</td>
        <td rowspan="2">Mfg.Date: '.$row['mfg_date'].'</td>
        <td rowspan="2">Exp.Date: '.$row['exp_date'].'</td>
        <td rowspan="2">Page No. Page 1 of 38</td>
    </tr>
    <tr>
        <td>Actual Batch Size: Nos</td>
    </tr>
</table>

<h1 style=" text-align:centre;">BATCH MANUFACTURING RECORD</h1>
<h4 style=" text-align:centre;">CONFIDENTIAL–NOT TO BE REPRODUCED WITHOUT PERMISSION</h4>

<table border="1" cellpadding="5">

    <tr>
        <td colspan="2">Product Code:</td>
        <td colspan="2">'.$row['product_code'].'</td>
        <td colspan="2">Shelf Life:  '.$row['shelf_life'].' </td>
    </tr>
    <tr>
        <td colspan="2">BMR Number </td>
        <td colspan="2">'.$row['bmr_no'].'</td>
        <td colspan="2">Effective Date :</td>
    </tr>
    <tr>
        <td colspan="2">Supersedes BMR Number </td>
        <td colspan="2"></td>
        <td colspan="2">Manufacturing Licence No.:</td>
    </tr>
    <tr>
        <td colspan="4">
            <p><strong>Label Claim:</strong></p>
            <p>Each Uncoated Tablet Contains:</p>
            <ul>
                <li>Paracetamol BP............. 500 mg</li>
                <li>Excipient ........................... q.s.</li>
            </ul>
        </td>
        <td colspan="2">
            <p><strong>Description:</strong></p>
            <p>White circular, flat, uncoated tablets with a break line on one side and the other side plain of each
                tablet.</p>
        </td>
    </tr>
    <tr>
        <td colspan="2">Document Issued By QA Officer Sign/Date</td>
        <td colspan="2">
            Document Checked By Competent Technical Person /Head Production Sign/Date
        </td>
        <td colspan="2">Document Approve By Head QA/Designee Sign/Date KD -622</td>
    </tr>
    <tr>
        <td style=" text-align:centre;" colspan="6">Batch Manufacturing Schedule
        </td>
    </tr>
    <tr>
        <td colspan="2"></td>
        <td colspan="2">Date of Commencement</td>
        <td colspan="2">Date of Completion</td>
    </tr>
    <tr>
        <td colspan="2">Granulation</td>
        <td colspan="2"></td>
        <td colspan="2"></td>
    </tr>
    <tr>
        <td colspan="2">Compression</td>
        <td colspan="2"></td>
        <td colspan="2"></td>
    </tr>


</table>

<div></div>

<table border="1" cellpadding="5">

    <tr>
        <td colspan="3" style=" text-align:centre;">Document Revision History</td>
    </tr>
    <tr>
        <td>Version No</td>
        <td>Effective Date</td>
        <td>Reason For Change:</td>
    </tr>
    <tr>
        <td></td>
        <td></td>
        <td></td>
    </tr>

</table>
<br pagebreak="true" />



<table>
    <tr>
        <td style="line-height:20px;width:540px;text-align:left;font-weight:bold;">STEP 1.0 –CALCULATIONS-</td>

    </tr>
    <tr>
        <td style="line-height:20px;width:540px;text-align:left; font-weight:bold;"> To be prepared by Production
            Chemist-
            Calculate the quantity of all ingredients for this batch size as against following standard formula
        </td>
    </tr>

</table>
<div></div>

<table style="width:540px;" border="1">
      <tr>
        <td style="line-height:25px; text-align: center;width: 40px;" rowspan="2">S r. No </td>
        <td style="line-height:25px; text-align: center;width: 60px;" rowspan="2">R. M. CODE</td> 
        <td style="line-height:25px; text-align: center;width: 90px;" rowspan="2">Ingredients</td>
        <td style="line-height:25px; text-align: center;width: 60px;" rowspan="2">Specification</td>
        <td style="line-height:25px; text-align: center;width: 100px;">Quantity per tablets</td>
        <td style="line-height:25px; text-align: center;width: 60px;">Standard Batch Size 13.75 L </td> 
        <td style="line-height:25px; text-align: center;width: 80px;">Qty issued for Batch Size</td>
        <td style="line-height:25px; text-align: center;width: 50px;">UOM</td>
    </tr>
     <tr>
        <td style="line-height:25px; text-align: center;width: 50px;">label claim mg</td>
        <td style="line-height:25px; text-align: center;width: 50px;">Qty with Ovgs mg</td>  
         <td style="line-height:25px; text-align: center;width: 60px;"></td> 
        <td style="line-height:25px; text-align: center;width: 80px;"></td>
        <td style="line-height:25px; text-align: center;width: 50px;"></td>    
    </tr>
    <tr>
        <td style="line-height:25px; text-align: left;width: 100px; font-weight:bold;">STEP I</td>
        <td style="line-height:25px; text-align: left;width: 440px;font-weight:bold;">GRANULATION :</td>      
    </tr>
    <tr>
        <td style="line-height:25px; text-align: left;width: 540px;font-weight:bold;"> DRY MIX & WET GRANULATION :</td>      
    </tr>
         <tr>
        <td style="line-height:25px; text-align: center;width: 40px;"></td>
        <td style="line-height:25px; text-align: center;width: 60px;"></td> 
        <td style="line-height:25px; text-align: center;width: 90px;"></td>
        <td style="line-height:25px; text-align: center;width: 60px;"></td>
        <td style="line-height:25px; text-align: center;width: 50px;"></td>
        <td style="line-height:25px; text-align: center;width: 50px;"></td> 
        <td style="line-height:25px; text-align: center;width: 60px;"></td> 
        <td style="line-height:25px; text-align: center;width: 80px;"></td>
        <td style="line-height:25px; text-align: center;width: 50px;"></td>
    </tr>
      <tr>
        <td style="line-height:25px; text-align: center;width: 40px;"></td>
        <td style="line-height:25px; text-align: center;width: 60px;"></td> 
        <td style="line-height:25px; text-align: center;width: 90px;"></td>
        <td style="line-height:25px; text-align: center;width: 160px; font-weight:bold;" colspan="3">TOTAL</td>
        <td style="line-height:25px; text-align: center;width: 60px;"></td> 
        <td style="line-height:25px; text-align: center;width: 80px;"></td>
        <td style="line-height:25px; text-align: center;width: 50px; font-weight:bold;">kg</td>
    </tr>
    <tr>
        <td style="line-height:25px; text-align: left;width: 100px; font-weight:bold;">STEP II</td>
        <td style="line-height:25px; text-align: left;width: 440px;font-weight:bold;">LUBRICATION  :</td>      
    </tr>
         <tr>
        <td style="line-height:25px; text-align: center;width: 40px;"></td>
        <td style="line-height:25px; text-align: center;width: 60px;"></td> 
        <td style="line-height:25px; text-align: center;width: 90px;"></td>
        <td style="line-height:25px; text-align: center;width: 60px;"></td>
        <td style="line-height:25px; text-align: center;width: 50px;"></td>
        <td style="line-height:25px; text-align: center;width: 50px;"></td> 
        <td style="line-height:25px; text-align: center;width: 60px;"></td> 
        <td style="line-height:25px; text-align: center;width: 80px;"></td>
        <td style="line-height:25px; text-align: center;width: 50px;"></td>
    </tr>
</table>
<div style="page-break-before: always;"></div>


<table border="1" style="width:540px;">
    <tr>
        <td style="line-height:20px;text-align:left; font-weight:bold;"> Final quantity of Paracetamol &
            Maize Starch
        </td>
    </tr>
</table>
<div></div>
<table border="1" style="width: 540px;">
    <tr>
        <td style="text-align: center; width: 40px;">Sr. No.</td>
        <td style="text-align: center; width: 60px;">R. M. CODE</td>
        <td style="text-align: center; width: 100px;">INGREDIENTS</td>
        <td style="text-align: center; width: 100px;">Specifications</td>
        <td style="text-align: center; width: 90px;">Medicap lot no</td>
        <td style="text-align: center; width: 100px;">Qty issued for Batch Size</td>
        <td style="text-align: center; width: 50px;">UOM</td>
    </tr>
    <tr>
        <td style="text-align: center;  width: 40px;" rowspan="3"></td>
        <td style="text-align: center;  width: 60px;" rowspan="3"></td>
        <td style="text-align: left;  width: 100px;" rowspan="3"></td>
        <td style="text-align: center;  width: 100px;" rowspan="3"></td>
        <td style="text-align: center;  width: 90px;"></td>
        <td style="text-align: center;  width: 100px;"></td>
        <td style="text-align: center;  width: 50px;">kg </td>
    </tr>
     <tr>
       
        <td style="text-align: center;  width: 90px;"></td>
        <td style="text-align: center;  width: 100px;"></td>
        <td style="text-align: center;  width: 50px;"> kg</td>
    </tr>
     <tr>
      
        <td style="text-align: center;  width: 90px;"></td>
        <td style="text-align: center;  width: 100px;"></td>
        <td style="text-align: center;  width: 50px;">kg</td>
    </tr>
   
</table>

<div></div>


<table border="1">
    <tr style="text-align:center; font-weight: bold;">
        <th style="width:540">Master BMR Digitally signed by</th>
    </tr>
     <tr style="text-align:center;">
         <td style="text-align:center; width:100px"></td>
        <th style="width:120px">Prepared By</th>
        <th style="width:200px">Checked By</th>
        <th style="width:120px">Approved by</th>
    </tr>
    <tr style="text-align:center;">
        <td style="width:100px;  font-weight: bold;">Name</td>
        <td style="width:120px"></td>
        <td style="width:200px"></td>
        <td style="width:120px"></td>
    </tr>

    <tr style="text-align:center;">
        <td style="width:100px;  font-weight: bold;">User Id</td>
          <td style="width:120px"></td>
        <td style="width:200px"></td>
        <td style="width:120px"></td>
    </tr>

    <tr style="text-align:center;">
        <td style="width:100px;  font-weight: bold;">Date</td>
        <td style="width:120px"></td>
        <td style="width:200px"></td>
        <td style="width:120px"></td>
    </tr>

    <tr style="text-align:center;">
        <td style="width:100px;  font-weight: bold;">Time</td>
        <td style="width:120px"></td>
        <td style="width:200px"></td>
        <td style="width:120px"></td>
    </tr>
   
</table>

<div style="page-break-before: always;"></div>

<h3>2.0 Dispensing of Raw Material</h3><br>
<h4>2.1 Line Clearance for Material Dispensing:</h4><br>
<ul>
    <li>Ensure that dispensing area and Dispensing Laminar Air Flow unit is cleaned properly and there are no traces of
        Previous product of material.</li>
    <li>Ensure that balance being used are calibrated and verified for calibration.</li>
    <li>Ensure that Temperature and humidity of the area is as prescribed limits.</li>
</ul>


<div></div>

<table border="1">
    <tr>
        <td>Previous Product :</td>
        <td></td>
        <td>Batch No. :</td>
        <td></td>
    </tr>
    <tr>
        <td>Area Cleaning Done By :</td>
        <td></td>
        <td>Checked By :</td>
        <td></td>
    </tr>
    <tr>
        <td>Line Clearance Given By QA :</td>
        <td></td>
        <td>Date :</td>
        <td></td>
    </tr>
    <tr>
        <td>Line Clearance SOP Ref No :</td>
        <td></td>
        <td>Time :</td>
        <td></td>
    </tr>
    <tr>
        <td colspan="4">Pressure differential in Reverse laminar air flow :</td>
    </tr>
    <tr>
        <td colspan="4">(Limit 10.0 to 25.00 mm of W.G.)</td>
    </tr>
</table>
<div style="page-break-before: always;"></div>

<table>
    <tr>
        <td style="line-height:20px;width:380px;text-align:left; font-weight:bold;"> 2.2 DISPENSING RECORD (BMR COPY)
        </td>
    </tr>
</table>
<table border="1">
    <tr>
        <td style="line-height:25px; text-align: center;width: 180px;">Dispensing Date</td>
        <td style="line-height:25px; text-align: center;width: 180px;">Time</td>
        <td style="line-height:25px; text-align: center;width: 180px;">RLAF</td> 

    </tr>

        <tr>
        <td style="line-height:25px; text-align: center;width: 180px;"></td> 
        <td style="line-height:25px; text-align: center;width: 45px;">Start Time</td>
        <td style="line-height:25px; text-align: center;width: 45px;"></td>
        <td style="line-height:25px; text-align: center;width: 45px;">End Time</td> 
        <td style="line-height:25px; text-align: center;width: 45px;"></td>
        <td style="line-height:25px; text-align: center;width: 45px;">Start Time</td>
        <td style="line-height:25px; text-align: center;width: 45px;"></td> 
        <td style="line-height:25px; text-align: center;width: 45px;">End Time</td>
        <td style="line-height:25px; text-align: center;width: 45px;"></td>
        </tr>
</table>

<div></div>

<table border="1">
      <tr>
        <td style="line-height:25px; text-align: center;width: 45px;" rowspan="2">RM Code</td>
        <td style="line-height:25px; text-align: center;width: 60px;" rowspan="2">Material Name</td>
        <td style="line-height:25px; text-align: center;width: 40px;" rowspan="2">SPC</td> 
        <td style="line-height:25px; text-align: center;width: 40px;" rowspan="2">LOT</td>
        <td style="line-height:25px; text-align: center;width: 40px;"  rowspan="2">Batch Qty. (Kg)</td>
        <td style="line-height:25px; text-align: center;width: 45px;"  rowspan="2">A.R. No.</td> 
        <td style="line-height:25px; text-align: center;width: 120px;">Weight in KG</td>
        <td style="line-height:25px; text-align: center;width: 50px;" rowspan="2">Done By Stores</td>
        <td style="line-height:25px; text-align: center;width: 50px;" rowspan="2">Checked By Product</td>
        <td style="line-height:25px; text-align: center;width: 50px;" rowspan="2">Verified By QA</td>
    </tr>

        <tr>
        <td style="line-height:25px; text-align: center;width: 40px;">Gross Wt</td> 
        <td style="line-height:25px; text-align: center;width: 40px;">Tare Wt</td>
        <td style="line-height:25px; text-align: center;width: 40px;">Net Wt</td>
        </tr>

    
        <tr>
        <td style="line-height:25px; text-align: center;width: 45px;"></td>
        <td style="line-height:25px; text-align: center;width: 60px;"></td>
        <td style="line-height:25px; text-align: center;width: 40px;"></td> 
        <td style="line-height:25px; text-align: center;width: 40px;"></td>
        <td style="line-height:25px; text-align: center;width: 40px;"></td>
        <td style="line-height:25px; text-align: center;width: 45px;"></td>
        <td style="line-height:25px; text-align: center;width: 40px;"></td> 
        <td style="line-height:25px; text-align: center;width: 40px;"></td>
        <td style="line-height:25px; text-align: center;width: 40px;"></td>
        <td style="line-height:25px; text-align: center;width: 50px;"></td>
        <td style="line-height:25px; text-align: center;width: 50px;"></td>
        <td style="line-height:25px; text-align: center;width: 50px;"></td>
    </tr>

</table>
<div></div>

<div></div>

<table border="1">
    <tr>
        <td colspan="2">Total Quantity of Active</td>
    </tr>
    <tr>
        <td>RM Code</td>
        <td>Total Batch Qty. (Kg)</td>
    </tr>
    <tr>
        <td>RP034</td>
        <td></td>
    </tr>
    <tr>
        <td>Total</td>
        <td></td>
    </tr>
</table>
<div></div>
<div style="page-break-before: always;"></div>

<table>
    <tr>
        <td style="line-height:20px;width:380px;text-align:left; font-weight:bold;"> 2.2 DISPENSING RECORD (STORE COPY)
        </td>
    </tr>

</table>
<table border="1">
    <tr>
        <td style="line-height:25px; text-align: center;width: 180px;">Dispensing Date</td>
        <td style="line-height:25px; text-align: center;width: 180px;">Time</td>
        <td style="line-height:25px; text-align: center;width: 180px;">RLAF</td> 

    </tr>

        <tr>
        <td style="line-height:25px; text-align: center;width: 180px;"></td> 
        <td style="line-height:25px; text-align: center;width: 45px;">Start Time</td>
        <td style="line-height:25px; text-align: center;width: 45px;"></td>
        <td style="line-height:25px; text-align: center;width: 45px;">End Time</td> 
        <td style="line-height:25px; text-align: center;width: 45px;"></td>
        <td style="line-height:25px; text-align: center;width: 45px;">Start Time</td>
        <td style="line-height:25px; text-align: center;width: 45px;"></td> 
        <td style="line-height:25px; text-align: center;width: 45px;">End Time</td>
        <td style="line-height:25px; text-align: center;width: 45px;"></td>
        </tr>
</table>

<div></div>

<table border="1">
      <tr>
        <td style="line-height:25px; text-align: center;width: 45px;" rowspan="2">RM Code</td>
        <td style="line-height:25px; text-align: center;width: 60px;" rowspan="2">Material Name</td>
        <td style="line-height:25px; text-align: center;width: 40px;" rowspan="2">SPC</td> 
        <td style="line-height:25px; text-align: center;width: 40px;" rowspan="2">LOT</td>
        <td style="line-height:25px; text-align: center;width: 40px;"  rowspan="2">Batch Qty. (Kg)</td>
        <td style="line-height:25px; text-align: center;width: 45px;"  rowspan="2">A.R. No.</td> 
        <td style="line-height:25px; text-align: center;width: 120px;">Weight in KG</td>
        <td style="line-height:25px; text-align: center;width: 50px;" rowspan="2">Done By Stores</td>
        <td style="line-height:25px; text-align: center;width: 50px;" rowspan="2">Checked By Product</td>
        <td style="line-height:25px; text-align: center;width: 50px;" rowspan="2">Verified By QA</td>
    </tr>

        <tr>
        <td style="line-height:25px; text-align: center;width: 40px;">Gross Wt</td> 
        <td style="line-height:25px; text-align: center;width: 40px;">Tare Wt</td>
        <td style="line-height:25px; text-align: center;width: 40px;">Net Wt</td>
        </tr>

    
        <tr>
        <td style="line-height:25px; text-align: center;width: 45px;"></td>
        <td style="line-height:25px; text-align: center;width: 60px;"></td>
        <td style="line-height:25px; text-align: center;width: 40px;"></td> 
        <td style="line-height:25px; text-align: center;width: 40px;"></td>
        <td style="line-height:25px; text-align: center;width: 40px;"></td>
        <td style="line-height:25px; text-align: center;width: 45px;"></td>
        <td style="line-height:25px; text-align: center;width: 40px;"></td> 
        <td style="line-height:25px; text-align: center;width: 40px;"></td>
        <td style="line-height:25px; text-align: center;width: 40px;"></td>
        <td style="line-height:25px; text-align: center;width: 50px;"></td>
        <td style="line-height:25px; text-align: center;width: 50px;"></td>
        <td style="line-height:25px; text-align: center;width: 50px;"></td>
    </tr>

</table>


<!-- one end -->
<div></div>
<div></div>

<table border="1">

    <tr>
        <td colspan="2">Total Quantity of Active</td>
    </tr>
    <tr>
        <td>RM Code</td>
        <td>Total Batch Qty. (Kg)</td>
    </tr>
    <tr>
        <td>RP034</td>
        <td></td>
    </tr>
    <tr>
        <td>Total</td>
        <td></td>
    </tr>


</table>
<div></div>
<div></div>
    <table cellpadding="10" border="1">

        <tr>
            <th style="text-align:center; font-weight:bold;">Dispensed Material Transfer Details</th>
        </tr>
    </table>
    <table cellpadding="5" border="1">

        <tr>
            <th style="text-align:center;">Transferred By Sign/Date</th>
            <th style="text-align:center;">Received By Sign/Date</th>
            <th style="text-align:center;">Checked By Sign/Date</th>
        </tr>
        <tr height="50px">
            <td></td>
            <td></td>
            <td></td>
        </tr>
    </table>
    

<div style="page-break-before: always;"></div>

    <h3 style="text-align:center;">General Instruction</h3>
    <table cellpadding="5" border="1">
        <tr>
            <td width="30px">1</td>
            <td width="509px">Ensure that Every Equipment is Cleaned, calibrated and Qualified for use.</td>
        </tr>
        <tr>
            <td width="30px">2</td>
            <td width="509px">Ensure that area is cleaned properly.</td>
        </tr>
        <tr>
            <td width="30px">3</td>
            <td width="509px">Gloves, nose masks & safety wares are to be worn during manufacturing operations.</td>
        </tr>
        <tr>
            <td width="30px">4</td>
            <td width="509px">Every area and equipment shall be Labelled for its status.</td>
        </tr>
        <tr>
            <td width="30px">5</td>
            <td width="509px">Ensure proper Line clearance by Quality Unit before starting the operations.</td>
        </tr>
        <tr>
            <td width="30px">6</td>
            <td width="509px">Ensure that all the material weights will be checked before addition by competent officer
                of Production.</td>
        </tr>
        <tr>
            <td width="30px">7</td>
            <td width="509px">Containers used for manufacturing shall be cleaned ,covered and labelled properly.</td>
        </tr>
        <tr>
            <td width="30px">8</td>
            <td width="509px">All storage conditions shall be followed as per material storage specifications.</td>
        </tr>
        <tr>
            <td width="30px">9</td>
            <td width="509px">Each stage of process shall be reconciled and losses shall be reported in BMR.</td>
        </tr>
        <tr>
            <td width="30px">10</td>
            <td width="509px">If any deviation, Incident or abnormal process behaviour shall be reported Immediately to
                QA Department.</td>
        </tr>
        <tr>
            <td width="30px">11</td>
            <td width="509px">Wherever necessary sieve integrity shall be checked.</td>
        </tr>


    </table>

    <div style="page-break-before: always;"></div>

    <h3 style="text-align:left;">List of Equipments to be used for Manufacturing:</h3>
    <table cellpadding="5" border="1">
        <tr style="border: solid 1px black;">
            <th width="30px" style="border: solid 1px black;">Sr. No</th>
            <th width="150px" style="border: solid 1px black;">Name of Equipment</th>
            <th width="50px" style="border: solid 1px black;">Capacity</th>
            <th width="150px" style="border: solid 1px black;">Equipment ID</th>
            <th width="158px" style="border: solid 1px black;">Operation/Cleaning SOP No</th>
        </tr>
        <tr style="border: solid 1px black;">
            <td width="30px" style="border: solid 1px black;"></td>
            <td width="150px" style="border: solid 1px black;"> </td>
            <td width="50px" style="border: solid 1px black;"></td>
            <td width="150px" style="border: solid 1px black;"></td>
            <td width="158px" style="border: solid 1px black;">
               </td>
        </tr>
      <tr style="border: solid 1px black;">
            <td width="30px" style="border: solid 1px black;"></td>
            <td width="150px" style="border: solid 1px black;"> </td>
            <td width="50px" style="border: solid 1px black;"></td>
            <td width="150px" style="border: solid 1px black;"></td>
            <td width="158px" style="border: solid 1px black;">
               </td>
        </tr>
       <tr style="border: solid 1px black;">
            <td width="30px" style="border: solid 1px black;"></td>
            <td width="150px" style="border: solid 1px black;"> </td>
            <td width="50px" style="border: solid 1px black;"></td>
            <td width="150px" style="border: solid 1px black;"></td>
            <td width="158px" style="border: solid 1px black;">
               </td>
        </tr>
      <tr style="border: solid 1px black;">
            <td width="30px" style="border: solid 1px black;"></td>
            <td width="150px" style="border: solid 1px black;"> </td>
            <td width="50px" style="border: solid 1px black;"></td>
            <td width="150px" style="border: solid 1px black;"></td>
            <td width="158px" style="border: solid 1px black;">
               </td>
        </tr>
       <tr style="border: solid 1px black;">
            <td width="30px" style="border: solid 1px black;"></td>
            <td width="150px" style="border: solid 1px black;"> </td>
            <td width="50px" style="border: solid 1px black;"></td>
            <td width="150px" style="border: solid 1px black;"></td>
            <td width="158px" style="border: solid 1px black;">
               </td>
        </tr>

      <tr style="border: solid 1px black;">
            <td width="30px" style="border: solid 1px black;"></td>
            <td width="150px" style="border: solid 1px black;"> </td>
            <td width="50px" style="border: solid 1px black;"></td>
            <td width="150px" style="border: solid 1px black;"></td>
            <td width="158px" style="border: solid 1px black;">
               </td>
        </tr>
      <tr style="border: solid 1px black;">
            <td width="30px" style="border: solid 1px black;"></td>
            <td width="150px" style="border: solid 1px black;"> </td>
            <td width="50px" style="border: solid 1px black;"></td>
            <td width="150px" style="border: solid 1px black;"></td>
            <td width="158px" style="border: solid 1px black;">
               </td>
        </tr>
       <tr style="border: solid 1px black;">
            <td width="30px" style="border: solid 1px black;"></td>
            <td width="150px" style="border: solid 1px black;"> </td>
            <td width="50px" style="border: solid 1px black;"></td>
            <td width="150px" style="border: solid 1px black;"></td>
            <td width="158px" style="border: solid 1px black;">
               </td>
        </tr>
        <tr style="border: solid 1px black;">
            <td width="30px" style="border: solid 1px black;"></td>
            <td width="150px" style="border: solid 1px black;"> </td>
            <td width="50px" style="border: solid 1px black;"></td>
            <td width="150px" style="border: solid 1px black;"></td>
            <td width="158px" style="border: solid 1px black;">
               </td>
        </tr>
    </table>

    <div></div>
    <div style="page-break-before: always;"></div>

    <table style="width: 540px;border: none;">
        <tr>
            <td style="width: 60px; font-weight:bold;border: none;">STAGE 3.0</td>
            <td style="text-align: left;width: 460px; font-weight:bold;border: none;">GRANULATION:</td>
        </tr><br>
        <tr>
            <td style="width: 60px; font-weight:bold;border: none;">STAGE 3.1</td>
            <td style="text-align: left;width: 460px; font-weight:bold;border: none;">Line Clearance for Granulation:
            </td>
        </tr>
        <br>
        <tr>
            <td style="width: 10px;border: none;"></td>
            <td style="width: 20px;border: none;">1</td>
            <td style="text-align: left; width: 520px;border: none;">Ensure that Granulation area and all equipments are
                cleaned properly and there are no traces of Previous product of material.</td>
        </tr>
        <tr>
            <td style="width: 10px;border: none;"></td>
            <td style="width: 20px;border: none;">2</td>
            <td style="text-align: left; width: 520px;border: none;">Ensure that balance being used are calibrated and
                verified for calibration.</td>
        </tr>
        <tr>
            <td style="width: 10px;border: none;"></td>
            <td style="width: 20px;border: none;">3</td>
            <td style="text-align: left; width: 520px;border: none;">Ensure that Temperature and humidity of the area is
                as prescribed limits. </td>
        </tr>
    </table>

    <div></div>

    <table cellpadding="5" border="1">
        <tr style="border: solid 1px black;">
            <th>Previous Product</th>
            <td></td>
            <th>Batch No </th>
            <td></td>
        </tr>
        <tr style="border: solid 1px black;">
            <th>Area Cleaning Done By</th>
            <td></td>
            <th>Checked By</th>
            <td></td>
        </tr>
        <tr>
            <th>Line Clearance Given By QA</th>

            <td></td>
            <th>Date</th>
            <td></td>
        </tr>
        <tr>
            <th>Line Clearance SOP Ref No : SOP/QA/GE/</th>

            <td></td>
            <th>Time</th>
            <td></td>
        </tr>
        <tr>
            <td width="538px">Ensure That Temp. Shall NMT 25째C, % RH. Shall NMT 55% during the processing of the
                materials in Granulation area </td>
        </tr>

    </table>
    <div></div>
    <table style="width:540px;border:none;">
        <tr>
            <td style="font-weight:bold;border:none;text-align:left">Equipment Cleanliness Checks:</td>
        </tr>
    </table>
    <div></div>


    <table cellpadding="5" border="1">
        <tr style="border: solid 1px black;">
            <th>Name of Equipment</th>
            <th>Equipment ID</th>
            <th>Cleaned By</th>
            <th>Checked By</th>
        </tr>
        <tr style="border: solid 1px black;">
            <td>Mechanical Sifter</td>
            <td>PD/EQ/005/</td>
            <td></td>
            <td></td>
        </tr>
        <tr style="border: solid 1px black;">
            <td>Rapid Mixer Granulator (RMG)</td>
            <td>PD/EQ/007/</td>
            <td></td>
            <td></td>
        </tr>
    </table>


    <table cellpadding="5" border="1">
        <tr style="border: solid 1px black;">
            <td>Fluidised Bed Dryer (FBD)</td>
            <td>PD/EQ/008/</td>
            <td></td>
            <td></td>
        </tr>
        <tr style="border: solid 1px black;">
            <td>Multimill</td>
            <td>PD/EQ/009/</td>
            <td></td>
            <td></td>
        </tr>
        <tr style="border: solid 1px black;">
            <td>Octagonal Blender</td>
            <td>PD/EQ/049</td>
            <td></td>
            <td></td>
        </tr>
        <tr style="border: solid 1px black;">
            <td>Tipper</td>
            <td>PD/EQ/038</td>
            <td></td>
            <td></td>
        </tr>
    </table>
    <div></div>
    <div style="page-break-before: always;"></div>
    <table style="width: 540px;border: none;">
        <tr>
            <td style="width: 60px; font-weight:bold;border: none;">STAGE 3.2</td>
            <td style="text-align: left;width: 460px; font-weight:bold;border: none;">MILLING OF ACTIVE INGRADIENT AND
                SIFTING OF EXCIPIENTS:</td>
        </tr><br>

        <tr>
            <td style="width: 10px;border: none;"></td>
            <td style="width: 40px;border: none;">3.2.1</td>
            <td style="text-align: left; width: 490px;border: none;">Assemble & Operate Sifter as per SOP No.
                SOP/PR/EQP/003-02.</td>
        </tr>
        <tr>
            <td style="width: 10px;border: none;"></td>
            <td style="width: 40px;border: none;">3.2.2</td>
            <td style="text-align: left; width: 490px;border: none;">Fix the specified mesh in the sifter and place a
                HDPE drum lined with polythene bag at the discharge end and tie the bag properly.</td>
        </tr>
        <tr>
            <td style="width: 10px;border: none;"></td>
            <td style="width: 40px;border: none;">3.2.3</td>
            <td style="text-align: left; width: 490px;border: none;">Sift the materials through respective mesh
                specified. Collect the sifted materials in polythene bag in HDPE drum. </td>
        </tr>

        <tr>
            <td style="width: 50px;border: none;"></td>
            <td style="text-align: left; width: 490px;border: none;"><b>Note : </b>Pregelatinised Starch (Universal) and
                Sodium Starch Glycolate (Primogel )of dry mixing to be pass through 60#.</td>
        </tr>
        <tr>
            <td style="width: 10px;border: none;"></td>
            <td style="width: 40px;border: none;">3.2.4</td>
            <td style="text-align: left; width: 490px;border: none;">Check the integrity of sifter sieves before and
                after sifting the materials.</td>
        </tr>
        <tr>
            <td style="width: 10px;border: none;"></td>
            <td style="width: 40px;border: none;">3.2.5</td>
            <td style="text-align: left; width: 490px;border: none;">Mill the Paracetamol using 0.5 mm screen. Mill at
                impact forward Position. </td>
        </tr>
    </table>

    <div></div>
    <table style="width: 540px;border: none;">
        <tr>
            <td style="width: 540px; font-weight:bold;border: none;text-align:left;">Equipment No:</td>
        </tr>
    </table>


    <div></div>
    <table style="width: 540px;" border="1">

        <tr>
            <td style="line-height:25px; text-align: center;width: 70px;" rowspan="2">Material</td>
            <td style="line-height:25px; text-align: center;width: 70px;" rowspan="2">LOT No</td>
            <td style="line-height:25px; text-align: center;width: 60px;" rowspan="2">Weight In Kg</td>
            <td style="line-height:25px; text-align: center;width: 60px;" rowspan="2">Sieve/Mesh Size</td>
            <td style="line-height:25px; text-align: center;width: 80px;">Time</td>
            <td style="line-height:25px; text-align: center;width: 85px;">Integrity</td>
            <td style="line-height:25px; text-align: center;width: 55px;" rowspan="2">Done By</td>
            <td style="line-height:25px; text-align: center;width: 60px;" rowspan="2">Checked By</td>
        </tr>

        <tr>

            <td style="line-height:25px; text-align: center;width: 40px;">Start Time</td>
            <td style="line-height:25px; text-align: center;width: 40px;">End Time</td>
            <td style="line-height:25px; text-align: center;width: 45px;">Before</td>
            <td style="line-height:25px; text-align: center;width: 40px;">After</td>

        </tr>

        <tr>
            <td style="line-height:25px; text-align: center;width: 70px;" rowspan="5"></td>
            <td style="line-height:25px; text-align: center;width: 70px;">LOT- I</td>
            <td style="line-height:25px; text-align: center;width: 60px;"></td>
            <td style="line-height:25px; text-align: center;width: 60px;"></td>
            <td style="line-height:25px; text-align: center;width: 40px;"></td>
            <td style="line-height:25px; text-align: center;width: 40px;"></td>
            <td style="line-height:25px; text-align: center;width: 45px;"></td>
            <td style="line-height:25px; text-align: center;width: 40px;"></td>
            <td style="line-height:25px; text-align: center;width: 55px;"></td>
            <td style="line-height:25px; text-align: center;width: 60px;"></td>
        </tr>

        <tr>
            <td style="line-height:25px; text-align: center;width: 70px;">LOT- II</td>
            <td style="line-height:25px; text-align: center;width: 60px;"></td>
            <td style="line-height:25px; text-align: center;width: 60px;"></td>
            <td style="line-height:25px; text-align: center;width: 40px;"></td>
            <td style="line-height:25px; text-align: center;width: 40px;"></td>
            <td style="line-height:25px; text-align: center;width: 45px;"></td>
            <td style="line-height:25px; text-align: center;width: 40px;"></td>
            <td style="line-height:25px; text-align: center;width: 55px;"></td>
            <td style="line-height:25px; text-align: center;width: 60px;"></td>
        </tr>

        <tr>
            <td style="line-height:25px; text-align: center;width: 70px;">LOT- III</td>
            <td style="line-height:25px; text-align: center;width: 60px;"></td>
            <td style="line-height:25px; text-align: center;width: 60px;"></td>
            <td style="line-height:25px; text-align: center;width: 40px;"></td>
            <td style="line-height:25px; text-align: center;width: 40px;"></td>
            <td style="line-height:25px; text-align: center;width: 45px;"></td>
            <td style="line-height:25px; text-align: center;width: 40px;"></td>
            <td style="line-height:25px; text-align: center;width: 55px;"></td>
            <td style="line-height:25px; text-align: center;width: 60px;"></td>
        </tr>
        <tr>
            <td style="line-height:25px; text-align: center;width: 70px;">LOT- IV</td>
            <td style="line-height:25px; text-align: center;width: 60px;"></td>
            <td style="line-height:25px; text-align: center;width: 60px;"></td>
            <td style="line-height:25px; text-align: center;width: 40px;"></td>
            <td style="line-height:25px; text-align: center;width: 40px;"></td>
            <td style="line-height:25px; text-align: center;width: 45px;"></td>
            <td style="line-height:25px; text-align: center;width: 40px;"></td>
            <td style="line-height:25px; text-align: center;width: 55px;"></td>
            <td style="line-height:25px; text-align: center;width: 60px;"></td>
        </tr>
        <tr>
            <td style="line-height:25px; text-align: center;width: 70px;">LOT- V</td>
            <td style="line-height:25px; text-align: center;width: 60px;"></td>
            <td style="line-height:25px; text-align: center;width: 60px;"></td>
            <td style="line-height:25px; text-align: center;width: 40px;"></td>
            <td style="line-height:25px; text-align: center;width: 40px;"></td>
            <td style="line-height:25px; text-align: center;width: 45px;"></td>
            <td style="line-height:25px; text-align: center;width: 40px;"></td>
            <td style="line-height:25px; text-align: center;width: 55px;"></td>
            <td style="line-height:25px; text-align: center;width: 60px;"></td>
        </tr>
    </table>
    <div></div>

    <div></div>
    <div style="page-break-before: always;"></div>

    <table style="width: 540px;border: none;">
        <tr>
            <td style="width: 60px; font-weight:bold;border: none;">STAGE 3.3</td>
            <td style="text-align: left;width: 460px; font-weight:bold;border: none;">DRY MIXING & WET GRANULATION:</td>
        </tr><br>

        <tr>
            <td style="width: 10px;border: none;"></td>
            <td style="width: 40px;border: none;">3.3.1</td>
            <td style="text-align: left; width: 490px;border: none;">Start and Operate RMG as per SOP No.
                SOP/PR/EQP/004-02 </td>
        </tr>
        <tr>
            <td style="width: 10px;border: none;"></td>
            <td style="width: 40px;border: none;">3.3.2</td>
            <td style="text-align: left; width: 490px;border: none;">Load the previously milled material from Step -
                3.2.5 to RMG and mix at slow speed for 10 minutes. .</td>
        </tr>
        <tr>
            <td style="width: 10px;border: none;"></td>
            <td style="width: 40px;border: none;">3.3.3</td>
            <td style="text-align: left; width: 490px;border: none;">Load the previously Sifted material from Step -
                3.2.3 to RMG and mix at slow speed for 10 minutes. </td>
        </tr>

        <tr>
            <td style="width: 10px;border: none;"></td>
            <td style="width: 40px;border: none;">3.3.4</td>
            <td style="text-align: left; width: 490px;border: none;">Observe ammeter reading to achieve 18A + 0.5A
                followed by fast mixing and chopper for 5 minutes</td>
        </tr>
        <tr>
            <td style="width: 10px;border: none;"></td>
            <td style="width: 40px;border: none;">3.3.5</td>
            <td style="text-align: left; width: 490px;border: none;">Add the 30 kg of Purified water gradually to RMG
                and mix at slow speed for 10 minutes with Chopper ‘off’ and at high speed for 10 minutes with Chopper
                ‘on’</td>
        </tr>
        <tr>
            <td style="width: 10px;border: none;"></td>
            <td style="width: 40px;border: none;">3.3.6</td>
            <td style="text-align: left; width: 490px;border: none;">Add 2 kg of water for every 10 min till the water
                volume reaches to 34 kg and mix it at high speed with Chopper ‘on’.</td>
        </tr>
        <tr>
            <td style="width: 10px;border: none;"></td>
            <td style="width: 40px;border: none;">3.3.7</td>
            <td style="text-align: left; width: 490px;border: none;">After complete addition of water Continue mixing at
                high speed till granules of desired consistency is obtained and ammeter reading reaches 18A±0.5A If
                required add additional quantity of water to get granules of desired consistency.</td>
        </tr>
    </table>
    <div></div>
    <div></div>


    <table style="width: 540px;" border="1">

        <tr>
            <td style="line-height:25px; text-align: center;width: 70px;" rowspan="2">LOT No</td>
            <td style="line-height:25px; text-align: center;width: 70px;" rowspan="2">Ingredients</td>
            <td style="line-height:25px; text-align: center;width: 60px;" rowspan="2">Date</td>
            <td style="line-height:25px; text-align: center;width: 80px;">Dry Mixing Time</td>
            <td style="line-height:25px; text-align: center;width: 85px;">Wet Mixing Time</td>
            <td style="line-height:25px; text-align: center;width: 60px;" rowspan="2">Ampere Reading</td>
            <td style="line-height:25px; text-align: center;width: 55px;" rowspan="2">Operator</td>
            <td style="line-height:25px; text-align: center;width: 60px;" rowspan="2">Checked By Production</td>

        </tr>

        <tr>

            <td style="line-height:25px; text-align: center;width: 40px;">From</td>
            <td style="line-height:25px; text-align: center;width: 40px;">To</td>
            <td style="line-height:25px; text-align: center;width: 45px;">From</td>
            <td style="line-height:25px; text-align: center;width: 40px;">To</td>

        </tr>

        <tr>
            <td style="line-height:25px; text-align: center;width: 70px;">LOT- I</td>
            <td style="line-height:25px; text-align: center;width: 70px;"></td>
            <td style="line-height:25px; text-align: center;width: 60px;"></td>
            <td style="line-height:25px; text-align: center;width: 40px;"></td>
            <td style="line-height:25px; text-align: center;width: 40px;"></td>
            <td style="line-height:25px; text-align: center;width: 45px;"></td>
            <td style="line-height:25px; text-align: center;width: 40px;"></td>
            <td style="line-height:25px; text-align: center;width: 60px;"></td>
            <td style="line-height:25px; text-align: center;width: 55px;"></td>
            <td style="line-height:25px; text-align: center;width: 60px;"></td>
        </tr>

        <tr>
            <td style="line-height:25px; text-align: center;width: 70px;">LOT- II</td>
            <td style="line-height:25px; text-align: center;width: 70px;"></td>
            <td style="line-height:25px; text-align: center;width: 60px;"></td>
            <td style="line-height:25px; text-align: center;width: 40px;"></td>
            <td style="line-height:25px; text-align: center;width: 40px;"></td>
            <td style="line-height:25px; text-align: center;width: 45px;"></td>
            <td style="line-height:25px; text-align: center;width: 40px;"></td>
            <td style="line-height:25px; text-align: center;width: 60px;"></td>
            <td style="line-height:25px; text-align: center;width: 55px;"></td>
            <td style="line-height:25px; text-align: center;width: 60px;"></td>
        </tr>

        <tr>
            <td style="line-height:25px; text-align: center;width: 70px;">LOT- III</td>
            <td style="line-height:25px; text-align: center;width: 70px;"></td>
            <td style="line-height:25px; text-align: center;width: 60px;"></td>
            <td style="line-height:25px; text-align: center;width: 40px;"></td>
            <td style="line-height:25px; text-align: center;width: 40px;"></td>
            <td style="line-height:25px; text-align: center;width: 45px;"></td>
            <td style="line-height:25px; text-align: center;width: 40px;"></td>
            <td style="line-height:25px; text-align: center;width: 60px;"></td>
            <td style="line-height:25px; text-align: center;width: 55px;"></td>
            <td style="line-height:25px; text-align: center;width: 60px;"></td>
        </tr>
        <tr>
            <td style="line-height:25px; text-align: center;width: 70px;">LOT- IV</td>
            <td style="line-height:25px; text-align: center;width: 70px;"></td>
            <td style="line-height:25px; text-align: center;width: 60px;"></td>
            <td style="line-height:25px; text-align: center;width: 40px;"></td>
            <td style="line-height:25px; text-align: center;width: 40px;"></td>
            <td style="line-height:25px; text-align: center;width: 45px;"></td>
            <td style="line-height:25px; text-align: center;width: 40px;"></td>
            <td style="line-height:25px; text-align: center;width: 60px;"></td>
            <td style="line-height:25px; text-align: center;width: 55px;"></td>
            <td style="line-height:25px; text-align: center;width: 60px;"></td>
        </tr>
        <tr>
            <td style="line-height:25px; text-align: center;width: 70px;">LOT- V</td>
            <td style="line-height:25px; text-align: center;width: 70px;"></td>
            <td style="line-height:25px; text-align: center;width: 60px;"></td>
            <td style="line-height:25px; text-align: center;width: 40px;"></td>
            <td style="line-height:25px; text-align: center;width: 40px;"></td>
            <td style="line-height:25px; text-align: center;width: 45px;"></td>
            <td style="line-height:25px; text-align: center;width: 40px;"></td>
            <td style="line-height:25px; text-align: center;width: 60px;"></td>
            <td style="line-height:25px; text-align: center;width: 55px;"></td>
            <td style="line-height:25px; text-align: center;width: 60px;"></td>
        </tr>
    </table>


    <div></div>

    <div style="page-break-before: always;"></div>

    <table style="width: 540px;" border="1">

        <tr>
            <td style="line-height:20px; text-align: center;width: 70px;">DATE</td>
            <td style="line-height:20px; text-align: center;width: 70px;">Lot No</td>
            <td style="line-height:20px; text-align: center;width: 60px;">Time</td>
            <td style="line-height:20px; text-align: center;width: 60px;">Sampling Intimation Given By</td>
            <td style="line-height:20px; text-align: center;width: 80px;">@Sample Collected By IPQA</td>
            <td style="line-height:20px; text-align: center;width: 85px;">Results Assay(Limits )</td>
            <td style="line-height:20px; text-align: center;width: 55px;"> A.R.No</td>
            <td style="line-height:20px; text-align: center;width: 60px;">Checked By Production Officer </td>
        </tr>
        <tr>
            <td style="line-height:20px; text-align: left;width: 540px;font-weight:bold" colspan="8">Sampling After Dry
                Mixing for Assay :</td>
        </tr>
        <tr>
            <td style="line-height:20px; text-align: center;width: 70px;"></td>
            <td style="line-height:20px; text-align: center;width: 70px;">Lot- I</td>
            <td style="line-height:20px; text-align: center;width: 60px;"></td>
            <td style="line-height:20px; text-align: center;width: 60px;" rowspan="5"></td>
            <td style="line-height:20px; text-align: center;width: 80px;" rowspan="5"></td>
            <td style="line-height:20px; text-align: center;width: 85px;" rowspan="5"></td>
            <td style="line-height:20px; text-align: center;width: 55px;" rowspan="5"></td>
            <td style="line-height:20px; text-align: center;width: 60px;" rowspan="5"></td>
        </tr>

        <tr>
            <td style="line-height:20px; text-align: center;width: 70px;"></td>
            <td style="line-height:20px; text-align: center;width: 70px;">Lot- II</td>
            <td style="line-height:20px; text-align: center;width: 60px;"></td>

        </tr>
        <tr>
            <td style="line-height:20px; text-align: center;width: 70px;"></td>
            <td style="line-height:20px; text-align: center;width: 70px;">Lot- III</td>
            <td style="line-height:20px; text-align: center;width: 60px;"></td>

        </tr>
        <tr>
            <td style="line-height:20px; text-align: center;width: 70px;"></td>
            <td style="line-height:20px; text-align: center;width: 70px;">Lot- IV</td>
            <td style="line-height:20px; text-align: center;width: 60px;"></td>

        </tr>
        <tr>
            <td style="line-height:20px; text-align: center;width: 70px;"></td>
            <td style="line-height:20px; text-align: center;width: 70px;">Lot- V</td>
            <td style="line-height:20px; text-align: center;width: 60px;"></td>

        </tr>

        <tr>
            <td style="line-height:20px; text-align: left;width: 540px;font-weight:bold" colspan="8">Sampling After Wet
                Mixing for Assay :</td>
        </tr>
        <tr>
            <td style="line-height:20px; text-align: center;width: 70px;"></td>
            <td style="line-height:20px; text-align: center;width: 70px;">Lot- I</td>
            <td style="line-height:20px; text-align: center;width: 60px;"></td>
            <td style="line-height:20px; text-align: center;width: 60px;" rowspan="5"></td>
            <td style="line-height:20px; text-align: center;width: 80px;" rowspan="5"></td>
            <td style="line-height:20px; text-align: center;width: 85px;" rowspan="5"></td>
            <td style="line-height:20px; text-align: center;width: 55px;" rowspan="5"></td>
            <td style="line-height:20px; text-align: center;width: 60px;" rowspan="5"></td>
        </tr>

        <tr>
            <td style="line-height:20px; text-align: center;width: 70px;"></td>
            <td style="line-height:20px; text-align: center;width: 70px;">Lot- II</td>
            <td style="line-height:20px; text-align: center;width: 60px;"></td>

        </tr>
        <tr>
            <td style="line-height:20px; text-align: center;width: 70px;"></td>
            <td style="line-height:20px; text-align: center;width: 70px;">Lot- III</td>
            <td style="line-height:20px; text-align: center;width: 60px;"></td>

        </tr>
        <tr>
            <td style="line-height:20px; text-align: center;width: 70px;"></td>
            <td style="line-height:20px; text-align: center;width: 70px;">Lot- IV</td>
            <td style="line-height:20px; text-align: center;width: 60px;"></td>

        </tr>
        <tr>
            <td style="line-height:20px; text-align: center;width: 70px;"></td>
            <td style="line-height:20px; text-align: center;width: 70px;">Lot- V</td>
            <td style="line-height:20px; text-align: center;width: 60px;"></td>

        </tr>

    </table>
    <div></div>
    <table tyle="width: 540px;border: none;">
        <tr>
            <td style="width: 20px;border: none;"></td>
            <td style="text-align: left; width: 520px;border: none;"><b>Note : </b>Pregelatinised Starch (Universal) and
                Sodium Starch Glycolate (Primogel )of dry mixing to be pass through 60#.</td>
        </tr>
    </table>
    <div></div>
    <div style="page-break-before: always;"></div>

    <table style="width: 540px;border: none;">
        <tr>
            <td style="width: 60px; font-weight:bold;border: none;">STAGE 3.4</td>
            <td style="text-align: left;width: 460px; font-weight:bold;border: none;">DRYING OF GRANULES</td>
        </tr><br>

        <tr>
            <td style="width: 10px;border: none;"></td>
            <td style="width: 40px;border: none;">3.4.1</td>
            <td style="text-align: left; width: 490px;border: none;">Setup & operate FBD as per SOP No.:
                SOP/PR/EQP/005-02</td>
        </tr>
        <tr>
            <td style="width: 10px;border: none;"></td>
            <td style="width: 40px;border: none;">3.4.2</td>
            <td style="text-align: left; width: 490px;border: none;">Load the granules into FBD bowl and air dry for 20
                min For Fluidisation with in between raking.</td>
        </tr>
        <tr>
            <td style="width: 10px;border: none;"></td>
            <td style="width: 40px;border: none;">3.4.3</td>
            <td style="text-align: left; width: 490px;border: none;">Set the inlet temperature at 80°C - 85°C.& start
                the drying . After 10 min of drying </td>
        </tr>

        <tr>
            <td style="width: 10px;border: none;"></td>
            <td style="width: 40px;border: none;">3.4.4</td>
            <td style="text-align: left; width: 490px;border: none;">remove FBD Bowel and rake the granules in FBD Bowel
                also scrap the materials from the sides of FBD Bowel </td>
        </tr>
        <tr>
            <td style="width: 10px;border: none;"></td>
            <td style="width: 40px;border: none;">3.4.5</td>
            <td style="text-align: left; width: 490px;border: none;"> Continue drying until the LOD of dried granules
                reaches in between 2.0-2.6% w/w when checked on IR Balance at 105°C for 10 min.</td>
        </tr>
        <tr>
            <td style="width: 10px;border: none;"></td>
            <td style="width: 40px;border: none;">3.4.6</td>
            <td style="text-align: left; width: 490px;border: none;">Remove FBD trolley & take granules for sifting.
            </td>
        </tr>
        <tr>
            <td style="width: 10px;border: none;"></td>
            <td style="width: 40px;border: none;">3.4.7</td>
            <td style="text-align: left; width: 490px;border: none;">Repeat the above procedure for all other lots..
            </td>
        </tr>
    </table>
    <div></div>
    <div></div>


    <table style="width: 540px;" border="1">

        <tr>
            <td style="line-height:20px; text-align: center;width: 50px;" rowspan="2">Lot </td>
            <td style="line-height:20px; text-align: center;width: 50px;" rowspan="2">Date</td>
            <td style="line-height:20px; text-align: center;width: 80px;">Drying Time</td>
            <td style="line-height:20px; text-align: center;width: 220px;">Parameters</td>
            <td style="line-height:20px; text-align: center;width: 80px;" rowspan="2">LOD</td>
            <td style="line-height:20px; text-align: center;width: 60px;" rowspan="2">Checked by</td>

        </tr>
        <tr>

            <td style="line-height:20px; text-align: center;width: 40px;"></td>
            <td style="line-height:20px; text-align: center;width: 40px;"></td>
            <td style="line-height:20px; text-align: center;width: 55px;"></td>
            <td style="line-height:20px; text-align: center;width: 55px;"></td>
            <td style="line-height:20px; text-align: center;width: 55px;"></td>
            <td style="line-height:20px; text-align: center;width: 55px;"></td>


        </tr>


        <tr>
            <td style="line-height:20px; text-align: center;width: 50px;" rowspan="2">LOT-I</td>
            <td style="line-height:20px; text-align: center;width: 50px;" rowspan="2"></td>
            <td style="line-height:20px; text-align: center;width: 40px;"></td>
            <td style="line-height:20px; text-align: center;width: 40px;"></td>
            <td style="line-height:20px; text-align: center;width: 55px;"></td>
            <td style="line-height:20px; text-align: center;width: 55px;" rowspan="2"></td>
            <td style="line-height:20px; text-align: center;width: 55px;" rowspan="2"></td>
            <td style="line-height:20px; text-align: center;width: 55px;" rowspan="2"></td>
            <td style="line-height:20px; text-align: center;width: 80px;" rowspan="2"></td>
            <td style="line-height:20px; text-align: center;width: 60px;" rowspan="2"></td>

        </tr>

        <tr>

            <td style="line-height:20px; text-align: center;width: 40px;"></td>
            <td style="line-height:20px; text-align: center;width: 40px;"></td>
            <td style="line-height:20px; text-align: center;width: 55px;"></td>


        </tr>

        <tr>
            <td style="line-height:20px; text-align: center;width: 50px;" rowspan="2">LOT-II</td>
            <td style="line-height:20px; text-align: center;width: 50px;" rowspan="2"></td>
            <td style="line-height:20px; text-align: center;width: 40px;"></td>
            <td style="line-height:20px; text-align: center;width: 40px;"></td>
            <td style="line-height:20px; text-align: center;width: 55px;"></td>
            <td style="line-height:20px; text-align: center;width: 55px;" rowspan="2"></td>
            <td style="line-height:20px; text-align: center;width: 55px;" rowspan="2"></td>
            <td style="line-height:20px; text-align: center;width: 55px;" rowspan="2"></td>
            <td style="line-height:20px; text-align: center;width: 80px;" rowspan="2"></td>
            <td style="line-height:20px; text-align: center;width: 60px;" rowspan="2"></td>

        </tr>

        <tr>

            <td style="line-height:20px; text-align: center;width: 40px;"></td>
            <td style="line-height:20px; text-align: center;width: 40px;"></td>
            <td style="line-height:20px; text-align: center;width: 55px;"></td>


        </tr>


        <tr>
            <td style="line-height:20px; text-align: center;width: 50px;" rowspan="2">LOT-III</td>
            <td style="line-height:20px; text-align: center;width: 50px;" rowspan="2"></td>
            <td style="line-height:20px; text-align: center;width: 40px;"></td>
            <td style="line-height:20px; text-align: center;width: 40px;"></td>
            <td style="line-height:20px; text-align: center;width: 55px;"></td>
            <td style="line-height:20px; text-align: center;width: 55px;" rowspan="2"></td>
            <td style="line-height:20px; text-align: center;width: 55px;" rowspan="2"></td>
            <td style="line-height:20px; text-align: center;width: 55px;" rowspan="2"></td>
            <td style="line-height:20px; text-align: center;width: 80px;" rowspan="2"></td>
            <td style="line-height:20px; text-align: center;width: 60px;" rowspan="2"></td>

        </tr>

        <tr>

            <td style="line-height:20px; text-align: center;width: 40px;"></td>
            <td style="line-height:20px; text-align: center;width: 40px;"></td>
            <td style="line-height:20px; text-align: center;width: 55px;"></td>


        </tr>


        <tr>
            <td style="line-height:20px; text-align: center;width: 50px;" rowspan="2">LOT-IV</td>
            <td style="line-height:20px; text-align: center;width: 50px;" rowspan="2"></td>
            <td style="line-height:20px; text-align: center;width: 40px;"></td>
            <td style="line-height:20px; text-align: center;width: 40px;"></td>
            <td style="line-height:20px; text-align: center;width: 55px;"></td>
            <td style="line-height:20px; text-align: center;width: 55px;" rowspan="2"></td>
            <td style="line-height:20px; text-align: center;width: 55px;" rowspan="2"></td>
            <td style="line-height:20px; text-align: center;width: 55px;" rowspan="2"></td>
            <td style="line-height:20px; text-align: center;width: 80px;" rowspan="2"></td>
            <td style="line-height:20px; text-align: center;width: 60px;" rowspan="2"></td>

        </tr>

        <tr>

            <td style="line-height:20px; text-align: center;width: 40px;"></td>
            <td style="line-height:20px; text-align: center;width: 40px;"></td>
            <td style="line-height:20px; text-align: center;width: 55px;"></td>


        </tr>


        <tr>
            <td style="line-height:20px; text-align: center;width: 50px;" rowspan="2">LOT-V</td>
            <td style="line-height:20px; text-align: center;width: 50px;" rowspan="2"></td>
            <td style="line-height:20px; text-align: center;width: 40px;"></td>
            <td style="line-height:20px; text-align: center;width: 40px;"></td>
            <td style="line-height:20px; text-align: center;width: 55px;"></td>
            <td style="line-height:20px; text-align: center;width: 55px;" rowspan="2"></td>
            <td style="line-height:20px; text-align: center;width: 55px;" rowspan="2"></td>
            <td style="line-height:20px; text-align: center;width: 55px;" rowspan="2"></td>
            <td style="line-height:20px; text-align: center;width: 80px;" rowspan="2"></td>
            <td style="line-height:20px; text-align: center;width: 60px;" rowspan="2"></td>

        </tr>

        <tr>

            <td style="line-height:20px; text-align: center;width: 40px;"></td>
            <td style="line-height:20px; text-align: center;width: 40px;"></td>
            <td style="line-height:20px; text-align: center;width: 55px;"></td>


        </tr>

    </table>
    <div></div>
    <div style="page-break-before: always;"></div>

    <table style="width: 540px;border: none;">
        <tr>
            <td style="width: 60px; font-weight:bold;border: none;">STAGE 3.5</td>
            <td style="text-align: left;width: 460px; font-weight:bold;border: none;">SIFTING MILLING OF DRY GRANULES
            </td>
        </tr><br>

        <tr>
            <td style="width: 10px;border: none;"></td>
            <td style="width: 40px;border: none;">3.5.1</td>
            <td style="text-align: left; width: 490px;border: none;">Assemble & Operate Sifter as per SOP No.
                SOP/PR/EQP/003-02.</td>
        </tr>
        <tr>
            <td style="width: 10px;border: none;"></td>
            <td style="width: 40px;border: none;">3.5.2</td>
            <td style="text-align: left; width: 490px;border: none;">Pass the dried granules through 16# mesh S.S Screen
                fitted with Mechanical Sifter and collect them in HDPE drums lined with polyethylene bags.</td>
        </tr>
        <tr>
            <td style="width: 10px;border: none;"></td>
            <td style="width: 40px;border: none;">3.5.3</td>
            <td style="text-align: left; width: 490px;border: none;">Collect the oversize granules retained on the 16#
                S.S. Screen and pass it through 2.0 mm screen of Multi Mill at medium speed, knife forward position and
                collect the milled granules in HDPE drums lined with polyethylene bags.</td>
        </tr>

        <tr>
            <td style="width: 10px;border: none;"></td>
            <td style="width: 40px;border: none;">3.5.4</td>
            <td style="text-align: left; width: 490px;border: none;">remove FBD Bowel and rake the granules in FBD Bowel
                also scrap the materials from the sides of FBD Bowel </td>
        </tr>
        <tr>
            <td style="width: 10px;border: none;"></td>
            <td style="width: 40px;border: none;">3.5.5</td>
            <td style="text-align: left; width: 490px;border: none;">Pass the milled granules through 16# mesh S.S
                Screen fitted with Mechanical Sifter and collect them in HDPE drums lined with polyethylene bags.</td>
        </tr>

    </table>

    <div></div>
    <div></div>

    <table style="width: 540px;" border="1">

        <tr>
            <td style="line-height:20px; text-align: center;width: 270px;">SIFTING DETAILS </td>
            <td style="line-height:20px; text-align: center;width: 270px;"> MILLING DETAILS </td>
        </tr>
        <tr>
            <td style="line-height:20px; text-align: center;width: 270px;">Equipment ID :</td>
            <td style="line-height:20px; text-align: center;width: 270px;">Equipment ID:</td>
        </tr>
    </table>

    <div></div>

    <table style="width: 540px;" border="1">

        <tr>
            <td style="line-height:20px; text-align: center;width: 70px;" colspan="2"></td>
            <td style="line-height:20px; text-align: center;width: 70px;" colspan="2">Time</td>
            <td style="line-height:20px; text-align: center;width: 80px;" colspan="2">Sieve integrity</td>
            <td style="line-height:20px; text-align: center;width: 40px;">Done By</td>
            <td style="line-height:20px; text-align: center;width: 40px;">Checked By</td>
            <td style="line-height:20px; text-align: center;width: 80px;" colspan="2">Time</td>
            <td style="line-height:20px; text-align: center;width: 80px;" colspan="2">Screen Integrity</td>
            <td style="line-height:20px; text-align: center;width: 40px;">Done By</td>
            <td style="line-height:20px; text-align: center;width: 40px;">Checked By </td>
        </tr>
        <tr>
            <td style="line-height:20px; text-align: center;width: 35px;">I</td>
            <td style="line-height:20px; text-align: center;width: 35px;"></td>
            <td style="line-height:20px; text-align: center;width: 35px;"></td>
            <td style="line-height:20px; text-align: center;width: 35px;"></td>
            <td style="line-height:20px; text-align: center;width: 40px;"></td>
            <td style="line-height:20px; text-align: center;width: 40px;"></td>
            <td style="line-height:20px; text-align: center;width: 40px;"></td>
            <td style="line-height:20px; text-align: center;width: 40px;"></td>
            <td style="line-height:20px; text-align: center;width: 40px;"></td>
            <td style="line-height:20px; text-align: center;width: 40px;"></td>
            <td style="line-height:20px; text-align: center;width: 40px;"></td>
            <td style="line-height:20px; text-align: center;width: 40px;"></td>
            <td style="line-height:20px; text-align: center;width: 40px;"></td>
            <td style="line-height:20px; text-align: center;width: 40px;"></td>
        </tr>
        <tr>
            <td style="line-height:20px; text-align: center;width: 35px;">II</td>
            <td style="line-height:20px; text-align: center;width: 35px;"></td>
            <td style="line-height:20px; text-align: center;width: 35px;"></td>
            <td style="line-height:20px; text-align: center;width: 35px;"></td>
            <td style="line-height:20px; text-align: center;width: 40px;"></td>
            <td style="line-height:20px; text-align: center;width: 40px;"></td>
            <td style="line-height:20px; text-align: center;width: 40px;"></td>
            <td style="line-height:20px; text-align: center;width: 40px;"></td>
            <td style="line-height:20px; text-align: center;width: 40px;"></td>
            <td style="line-height:20px; text-align: center;width: 40px;"></td>
            <td style="line-height:20px; text-align: center;width: 40px;"></td>
            <td style="line-height:20px; text-align: center;width: 40px;"></td>
            <td style="line-height:20px; text-align: center;width: 40px;"></td>
            <td style="line-height:20px; text-align: center;width: 40px;"></td>
        </tr>
        <tr>
            <td style="line-height:20px; text-align: center;width: 35px;">III</td>
            <td style="line-height:20px; text-align: center;width: 35px;"></td>
            <td style="line-height:20px; text-align: center;width: 35px;"></td>
            <td style="line-height:20px; text-align: center;width: 35px;"></td>
            <td style="line-height:20px; text-align: center;width: 40px;"></td>
            <td style="line-height:20px; text-align: center;width: 40px;"></td>
            <td style="line-height:20px; text-align: center;width: 40px;"></td>
            <td style="line-height:20px; text-align: center;width: 40px;"></td>
            <td style="line-height:20px; text-align: center;width: 40px;"></td>
            <td style="line-height:20px; text-align: center;width: 40px;"></td>
            <td style="line-height:20px; text-align: center;width: 40px;"></td>
            <td style="line-height:20px; text-align: center;width: 40px;"></td>
            <td style="line-height:20px; text-align: center;width: 40px;"></td>
            <td style="line-height:20px; text-align: center;width: 40px;"></td>
        </tr>
        <tr>
            <td style="line-height:20px; text-align: center;width: 35px;">IV</td>
            <td style="line-height:20px; text-align: center;width: 35px;"></td>
            <td style="line-height:20px; text-align: center;width: 35px;"></td>
            <td style="line-height:20px; text-align: center;width: 35px;"></td>
            <td style="line-height:20px; text-align: center;width: 40px;"></td>
            <td style="line-height:20px; text-align: center;width: 40px;"></td>
            <td style="line-height:20px; text-align: center;width: 40px;"></td>
            <td style="line-height:20px; text-align: center;width: 40px;"></td>
            <td style="line-height:20px; text-align: center;width: 40px;"></td>
            <td style="line-height:20px; text-align: center;width: 40px;"></td>
            <td style="line-height:20px; text-align: center;width: 40px;"></td>
            <td style="line-height:20px; text-align: center;width: 40px;"></td>
            <td style="line-height:20px; text-align: center;width: 40px;"></td>
            <td style="line-height:20px; text-align: center;width: 40px;"></td>
        </tr>
        <tr>
            <td style="line-height:20px; text-align: center;width: 35px;">V</td>
            <td style="line-height:20px; text-align: center;width: 35px;"></td>
            <td style="line-height:20px; text-align: center;width: 35px;"></td>
            <td style="line-height:20px; text-align: center;width: 35px;"></td>
            <td style="line-height:20px; text-align: center;width: 40px;"></td>
            <td style="line-height:20px; text-align: center;width: 40px;"></td>
            <td style="line-height:20px; text-align: center;width: 40px;"></td>
            <td style="line-height:20px; text-align: center;width: 40px;"></td>
            <td style="line-height:20px; text-align: center;width: 40px;"></td>
            <td style="line-height:20px; text-align: center;width: 40px;"></td>
            <td style="line-height:20px; text-align: center;width: 40px;"></td>
            <td style="line-height:20px; text-align: center;width: 40px;"></td>
            <td style="line-height:20px; text-align: center;width: 40px;"></td>
            <td style="line-height:20px; text-align: center;width: 40px;"></td>
        </tr>
    </table>
    <div style="page-break-before: always;"></div>

    <table style="width: 540px;border: none;">
        <tr>
            <td style="text-align: left;width: 50px; font-weight:bold;border: none;">STEP 3.6 </td>
            <td style="text-align: left;width: 490px; font-weight:bold;border: none;">DRY GRANULES WEIGHING RECORD</td>
        </tr><br>
        <tr>
            <td style="text-align: left;width: 270px; font-weight:bold;border: none;">Weighing Balance ID:</td>
            <td style="text-align: left;width: 270px; font-weight:bold;border: none;">Date:____________</td>
        </tr>
    </table>
    <div></div>
    <table style="width: 540px;" border="1">

        <tr>

            <td style="text-align: center;width: 54px;">Drum No</td>
            <td style="text-align: center;width: 54px;">Gross Wt (Kg)</td>
            <td style="text-align: center;width: 54px;">Tare Wt (Kg)</td>
            <td style="text-align: center;width: 54px;">Net Wt (Kg)</td>
            <td style="text-align: center;width: 54px;">Done By</td>
            <td style="text-align: center;width: 54px;">Drum No</td>
            <td style="text-align: center;width: 54px;">Gross Wt (Kg)</td>
            <td style="text-align: center;width: 54px;">Tare Wt (Kg)</td>
            <td style="text-align: center;width: 54px;">Net Wt (Kg)</td>
            <td style="text-align: center;width: 54px;">Done By</td>
        </tr>
        <tr>

            <td style="text-align: center;width: 54px;">01</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;">11</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
        </tr>
        <tr>

            <td style="text-align: center;width: 54px;">02</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;">12</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
        </tr>
        <tr>

            <td style="text-align: center;width: 54px;">03</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;">13</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
        </tr>
        <tr>

            <td style="text-align: center;width: 54px;">04</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;">14</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
        </tr>
        <tr>

            <td style="text-align: center;width: 54px;">05</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;">15</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
        </tr>
        <tr>

            <td style="text-align: center;width: 54px;">06</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;">16</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
        </tr>
        <tr>

            <td style="text-align: center;width: 54px;">07</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;">17</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
        </tr>
        <tr>

            <td style="text-align: center;width: 54px;">08</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;">18</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
        </tr>
        <tr>

            <td style="text-align: center;width: 54px;">09</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;">19</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
        </tr>
        <tr>

            <td style="text-align: center;width: 54px;">10</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;">20</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
        </tr>
        <tr>

            <td style="line-height:20px; text-align: center;width: 216px;">No of Containers</td>
            <td style="line-height:20px; text-align: center;width: 108px;"></td>
            <td style="line-height:20px; text-align: center;width: 108px;" rowspan="2">Weighing Checked By/Sign Date
            </td>
            <td style="line-height:20px; text-align: center;width: 108px;" rowspan="2"></td>
        </tr>
        <tr>

            <td style="line-height:20px; text-align: center;width: 216px;">Total Weight of Granules (KG)</td>
            <td style="line-height:20px; text-align: center;width: 108px;"></td>

        </tr>

    </table>

    <div style="page-break-before: always;"></div>
    <table style="width: 540px;border: none;">
        <tr>
            <td style="text-align: left;width: 50px; font-weight:bold;border: none;">STEP 3.7 </td>
            <td style="text-align: left;width: 490px; font-weight:bold;border: none;">Line Clearance for Blending and
                Lubrication :</td>
        </tr><br>

    </table>
    <div></div>
    <table border="1" style="width: 540px;">

        <tr>
            <td style="text-align: left;width: 130;">Previous Product</td>
            <td style="text-align: left;width: 10px;">:</td>
            <td style="text-align: left;width: 130;"></td>
            <td style="text-align: left;width: 130;">Batch No.</td>
            <td style="text-align: left;width: 10px;">:</td>
            <td style="text-align: left; width: 130;"></td>
        </tr>

        <tr>
            <td style="text-align: left;width: 130;">Area Cleaning Done By</td>
            <td style="text-align: left;width: 10px;">:</td>
            <td style="text-align: left;width: 130;"></td>
            <td style="text-align: left;width: 130;">Checked By</td>
            <td style="text-align: left;width: 10px;">:</td>
            <td style="text-align: left; width: 130;"></td>
        </tr>
        <tr>
            <td style="text-align: left;width: 130;">Line Clearance Given By QA</td>
            <td style="text-align: left;width: 10px;">:</td>
            <td style="text-align: left;width: 130;"></td>
            <td style="text-align: left;width: 130;">Date</td>
            <td style="text-align: left;width: 10px;">:</td>
            <td style="text-align: left; width: 130;"></td>
        </tr>
        <tr>
            <td style="text-align: left;width: 130;">Line Clearance SOP Ref No</td>
            <td style="text-align: left;width: 10px;">:</td>
            <td style="text-align: left;width: 130;"></td>
            <td style="text-align: left;width: 130;">Time</td>
            <td style="text-align: left;width: 10px;">:</td>
            <td style="text-align: left; width: 130;"></td>
        </tr>

    </table>
    <div></div>
    <div></div>

    <table style="width: 540px;border:none;">
        <tr>
            <td style="text-align: left;width: 540px;border:none;"><b>Note: </b>Ensure That Temp. Shall NMT 25°C, % RH.
                Shall NMT 55% during the processing of the materials in Compression area. </td>
        </tr>
    </table>
    <div></div>
    <div></div>
    <div style="page-break-before: always;"></div>

    <table style="width: 540px;border: none;">
        <tr>
            <td style="width: 60px; font-weight:bold;border: none;">STAGE 3.8</td>
            <td style="text-align: left;width: 460px; font-weight:bold;border: none;">SIFTING OF LUBRICATING AGENTS</td>
        </tr><br>

        <tr>
            <td style="width: 10px;border: none;"></td>
            <td style="width: 40px;border: none;">3.8.1</td>
            <td style="text-align: left; width: 490px;border: none;">Assemble & Operate Sifter as per SOP No:
                SOP/PR/EQP/003-02.</td>
        </tr>
        <tr>
            <td style="width: 10px;border: none;"></td>
            <td style="width: 40px;border: none;">3.8.2</td>
            <td style="text-align: left; width: 490px;border: none;">Pass the lubricants through 60# mesh S.S Screen
                fitted with and collect them in HDPE drums lined with LDPE bags.</td>
        </tr>


    </table>

    <div></div>
    <div></div>

    <table style="width: 540px;" border="1">

        <tr>
            <td style="line-height:25px; text-align: center;width: 25px;" rowspan="2">Sr. No.</td>
            <td style="line-height:25px; text-align: center;width: 60px;" rowspan="2">Item</td>
            <td style="line-height:25px; text-align: center;width: 50px;" rowspan="2">Qty For 13.75 L</td>
            <td style="line-height:25px; text-align: center;width: 50px;" rowspan="2">Qty(Kg)</td>
            <td style="line-height:25px; text-align: center;width: 50px;" rowspan="2">Mesh</td>
            <td style="line-height:25px; text-align: center;width: 45px;" rowspan="2">Date</td>
            <td style="line-height:25px; text-align: center;width: 80px;">Sifting time</td>
            <td style="line-height:25px; text-align: center;width: 80px;">Mesh Integrity</td>
            <td style="line-height:25px; text-align: center;width: 50px;" rowspan="2">Done by</td>
            <td style="line-height:25px; text-align: center;width: 50px;" rowspan="2">Checked by</td>
        </tr>

        <tr>

            <td style="line-height:25px; text-align: center;width: 40px;">From</td>
            <td style="line-height:25px; text-align: center;width: 40px;">To</td>
            <td style="line-height:25px; text-align: center;width: 45px;">Before</td>
            <td style="line-height:25px; text-align: center;width: 35px;">After</td>

        </tr>
        <tr>
            <td style="line-height:25px; text-align: left;width: 540px;" colspan="12"> LUBRICATION MATERIAL</td>

        </tr>

        <tr>
            <td style="line-height:25px; text-align: center;width: 25px;"></td>
            <td style="line-height:25px; text-align: center;width: 60px;"></td>
            <td style="line-height:25px; text-align: center;width: 50px;"></td>
            <td style="line-height:25px; text-align: center;width: 50px;"></td>
            <td style="line-height:25px; text-align: center;width: 50px;"></td>
            <td style="line-height:25px; text-align: center;width: 45px;"></td>
            <td style="line-height:25px; text-align: center;width: 40px;"></td>
            <td style="line-height:25px; text-align: center;width: 40px;"></td>
            <td style="line-height:25px; text-align: center;width: 45px;"></td>
            <td style="line-height:25px; text-align: center;width: 35px;"></td>
            <td style="line-height:25px; text-align: center;width: 50px;"></td>
            <td style="line-height:25px; text-align: center;width: 50px;"></td>
        </tr>

    </table>
    <div></div>
    <div style="page-break-before: always;"></div>

    <table style="width: 540px;border: none;">
        <tr>
            <td style="width: 60px; font-weight:bold;border: none;">STAGE 3.9</td>
            <td style="text-align: left;width: 460px; font-weight:bold;border: none;"> BLENDING WITH LUBRICATION OF
                DRIED (sifted) GRANULES</td>
        </tr><br>

        <tr>
            <td style="width: 10px;border: none;"></td>
            <td style="width: 40px;border: none;">3.9.1</td>
            <td style="text-align: left; width: 490px;border: none;"> Weigh the dried granules. (Theoretical wt 771.375
                kg)/td>
        </tr>
        <tr>
            <td style="width: 10px;border: none;"></td>
            <td style="width: 40px;border: none;">3.9.2</td>
            <td style="text-align: left; width: 490px;border: none;">Load the dried granules from step 3.6 and add
                sifted lubricants except Magnesium Stearate
                step 3.8 into Octagonal Blender and Close the lid of Octagonal Blender and mix for 20 minutes</td>
        </tr>
        <tr>
            <td style="width: 10px;border: none;"></td>
            <td style="width: 40px;border: none;">3.9.3</td>
            <td style="text-align: left; width: 490px;border: none;">Then add magnesium stearate into Octagonal Blender
                and Close the lid of Octagonal Blender and mix for 3 minutes at slow speed.</td>
        </tr>

        <tr>
            <td style="width: 10px;border: none;"></td>
            <td style="width: 40px;border: none;">3.9.4</td>
            <td style="text-align: left; width: 490px;border: none;">Off load the granules in containers lined with poly
                bags & record weights. </td>
        </tr>
        <tr>
            <td style="width: 10px;border: none;"></td>
            <td style="width: 40px;border: none;">3.9.5</td>
            <td style="text-align: left; width: 490px;border: none;">Intimate QC for Blend sampling.</td>
        </tr>

    </table>
    <div></div>

    <table style="width: 540px;" border="1">

        <tr>
            <td style="line-height:25px; text-align: center;width: 50px;" rowspan="2">Item</td>
            <td style="line-height:25px; text-align: center;width: 60px;" rowspan="2">Qty. (Kg) </td>
            <td style="line-height:25px; text-align: center;width: 100px;">Time</td>
            <td style="line-height:25px; text-align: center;width: 50px;" rowspan="2">Loaded by</td>
            <td style="line-height:25px; text-align: center;width: 50px;" rowspan="2">Checked By</td>
            <td style="line-height:25px; text-align: center;width: 150px;">IPQC Sampling Details</td>
            <td style="line-height:25px; text-align: center;width: 80px;">QC A.R.No.</td>
        </tr>

        <tr>

            <td style="line-height:25px; text-align: center;width: 50px;">From</td>
            <td style="line-height:25px; text-align: center;width: 50px;">To</td>
            <td style="line-height:25px; text-align: center;width: 75px;">Before</td>
            <td style="line-height:25px; text-align: center;width: 75px;">After</td>
        </tr>


        <tr>
            <td style="line-height:25px; text-align: center;width: 50px;"></td>
            <td style="line-height:25px; text-align: center;width: 60px;"></td>
            <td style="line-height:25px; text-align: center;width: 50px;"></td>
            <td style="line-height:25px; text-align: center;width: 50px;"></td>
            <td style="line-height:25px; text-align: center;width: 50px;"></td>
            <td style="line-height:25px; text-align: center;width: 50px;"></td>
            <td style="line-height:25px; text-align: center;width: 75px;"></td>
            <td style="line-height:25px; text-align: center;width: 75px;"></td>
            <td style="line-height:25px; text-align: center;width: 80px;"></td>
        </tr>
        <tr>
            <td style="line-height:25px; text-align: center;width: 50px;"></td>
            <td style="line-height:25px; text-align: center;width: 60px;"></td>
            <td style="line-height:25px; text-align: center;width: 50px;"></td>
            <td style="line-height:25px; text-align: center;width: 50px;"></td>
            <td style="line-height:25px; text-align: center;width: 50px;"></td>
            <td style="line-height:25px; text-align: center;width: 50px;"></td>
            <td style="line-height:25px; text-align: center;width: 75px;"></td>
            <td style="line-height:25px; text-align: center;width: 75px;"></td>
            <td style="line-height:25px; text-align: center;width: 80px;"></td>
        </tr>
        <tr>
            <td style="line-height:25px; text-align: center;width: 50px;"></td>
            <td style="line-height:25px; text-align: center;width: 60px;"></td>
            <td style="line-height:25px; text-align: center;width: 50px;"></td>
            <td style="line-height:25px; text-align: center;width: 50px;"></td>
            <td style="line-height:25px; text-align: center;width: 50px;"></td>
            <td style="line-height:25px; text-align: center;width: 50px;"></td>
            <td style="line-height:25px; text-align: center;width: 75px;"></td>
            <td style="line-height:25px; text-align: center;width: 75px;"></td>
            <td style="line-height:25px; text-align: center;width: 80px;"></td>
        </tr>
        <tr>
            <td style="line-height:25px; text-align: left;width: 50px; font-weight:bold;">TOTAL</td>
            <td style="line-height:25px; text-align: left;width: 60px;"></td>
            <td style="line-height:25px; text-align: left;width: 430px; font-weight:bold;">Result: Sample is analysed
                for Assay and as per Analytical Report
                No.
                Compression Stage can be Proceeded.<br> Sign/Date IPQA
            </td>

        </tr>

    </table>
    <div></div>

    <div style="page-break-before: always;"></div>


    <table style="width: 540px;">
        <tr>
            <td style="text-align: left;width: 540px; font-weight:bold;border: none;">STEP 3.10 LUBRICATED GRANULES
                CONTAINERS WEIGHING RECORD</td>
        </tr><br>
        <tr>
            <td style="text-align: left;width: 270px; font-weight:bold;border: none;">Balance ID:</td>
            <td style="text-align: left;width: 270px; font-weight:bold;border: none;">Date:____________</td>
        </tr>
    </table>
    <div></div>
    <table style="width: 540px;" border="1">

        <tr>

            <td style="text-align: center;width: 54px;">Drum No</td>
            <td style="text-align: center;width: 54px;">Gross Wt (Kg)</td>
            <td style="text-align: center;width: 54px;">Tare Wt (Kg)</td>
            <td style="text-align: center;width: 54px;">Net Wt (Kg)</td>
            <td style="text-align: center;width: 54px;">Done By</td>
            <td style="text-align: center;width: 54px;">Drum No</td>
            <td style="text-align: center;width: 54px;">Gross Wt (Kg)</td>
            <td style="text-align: center;width: 54px;">Tare Wt (Kg)</td>
            <td style="text-align: center;width: 54px;">Net Wt (Kg)</td>
            <td style="text-align: center;width: 54px;">Done By</td>
        </tr>
        <tr>

            <td style="text-align: center;width: 54px;">01</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;">11</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
        </tr>
        <tr>

            <td style="text-align: center;width: 54px;">02</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;">12</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
        </tr>
        <tr>

            <td style="text-align: center;width: 54px;">03</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;">13</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
        </tr>
        <tr>

            <td style="text-align: center;width: 54px;">04</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;">14</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
        </tr>
        <tr>

            <td style="text-align: center;width: 54px;">05</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;">15</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
        </tr>
        <tr>

            <td style="text-align: center;width: 54px;">06</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;">16</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
        </tr>
        <tr>

            <td style="text-align: center;width: 54px;">07</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;">17</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
        </tr>
        <tr>

            <td style="text-align: center;width: 54px;">08</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;">18</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
        </tr>
        <tr>

            <td style="text-align: center;width: 54px;">09</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;">19</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
        </tr>
        <tr>

            <td style="text-align: center;width: 54px;">10</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;">20</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
        </tr>
    </table>
    <div></div>
    <table style="width: 540px;">
        <tr>
            <td style="text-align: left;width: 540px; font-weight:bold;border: none;">Note: Store the blend in duly
                labelled double poly bag inside an airtight HDPE container at temperature NMT 25 ºC and Relative
                Humidity NMT 55 % until released for compression.</td>
        </tr><br>

    </table>

    <div></div>

    <div style="page-break-before: always;"></div>
    <table style="width: 540px;">
        <tr>
            <td style="text-align: left;width: 80px; font-weight:bold;border: none;">STEP 3.11</td>
            <td style="text-align: left;width: 460px; font-weight:bold;border: none;">YIELD RECONCILIATION-</td>
        </tr><br>

    </table>

    <div></div>

    <table style="width: 540px;" border="1">

        <tr>
            <td style="line-height:25px; text-align: center;width: 90px;"> Theoretical Batch Size (Kg) (A)</td>
            <td style="line-height:25px; text-align: center;width: 90px;">Actual Yield After Lubrication (Kg) (B)</td>
            <td style="line-height:25px; text-align: center;width: 90px;">% YIELD = B X 100 A</td>
            <td style="line-height:25px; text-align: center;width: 90px;">Limit</td>
            <td style="line-height:25px; text-align: center;width: 90px;">Checked By (PROD)</td>
            <td style="line-height:25px; text-align: center;width: 90px;">Verified By (QA)</td>

        </tr>
        <tr>
            <td style="line-height:25px; text-align: center;width: 90px;"></td>
            <td style="line-height:25px; text-align: center;width: 90px;"></td>
            <td style="line-height:25px; text-align: center;width: 90px;"></td>
            <td style="line-height:25px; text-align: center;width: 90px;"></td>
            <td style="line-height:25px; text-align: center;width: 90px;"></td>
            <td style="line-height:25px; text-align: center;width: 90px;"></td>

        </tr>
    </table>

    <div></div>
    <div></div>
    <table style="width: 540px;" border="1">

        <tr>
            <td style="line-height:25px; text-align: center;width: 540px;">Lubricated Granules are Further Transferred
                for Compression </td>


        </tr>
        <tr>
            <td style="line-height:25px; text-align: center;width: 135px;">Production Chemist <br> Sign/Date </td>
            <td style="line-height:25px; text-align: center;width: 135px;"></td>
            <td style="line-height:25px; text-align: center;width: 135px;">IPQA Officer <br> Sign/Date </td>
            <td style="line-height:25px; text-align: center;width: 135px;"></td>


        </tr>
    </table>

    <div style="page-break-before: always;"></div>

    <table style="width: 540px;border: none;">
        <tr>
            <td style="width: 60px; font-weight:bold;border: none;">STEP 3.12 </td>
            <td style="text-align: left;width: 460px; font-weight:bold;border: none;">LUBRICATED GRANULES APPROVAL FOR
                COMPRESSION</td>
        </tr><br>

        <tr>
            <td style="width: 10px;border: none;"></td>
            <td style="width: 40px;border: none;">3.12.1</td>
            <td style="text-align: left; width: 490px;border: none;">QC Report: The Lubricated Granules has been
                analysed and released for Compression as
                per the Analytical Report no _________</td>
        </tr>


    </table>

    <div></div>

    <div style="page-break-before: always;"></div>

    <table style="width: 540px; margin: 0 auto;border: none;">
        <tr>
            <td style="text-align: left; font-weight:bold;border: none;"> STEP - 4: COMPRESSION</td>
        </tr>
        <div></div>
        <tr>
            <td style="text-align: left; font-weight:bold;border: none;"> STEP 4.1 Line Clearances - Tablets Compression
                Area</td>
        </tr>
    </table>
    <div></div>


    <table border="1" style="width: 540px;">
        <tr>
            <td style="text-align: left; width: 270px; font-weight:bold">Equipment ID :</td>
            <td style="text-align: left; width: 270px; font-weight:bold">Capacity (Station ) :</td>
        </tr>

        <tr>
            <td style="text-align: left;width: 540px; font-weight:bold;">Line Clearance :</td>
        </tr>
        <tr>
            <td style="text-align: left; width: 540px;">• Check area ,walls, doors, flowerings is Cleaned Properly and
                no Traces of previous Product </td>
        </tr>
        <tr>
            <td style="text-align: left; width: 540px;">• Ensure QC release of Granules before taking for compression.
            </td>
        </tr>
        <tr>
            <td style="text-align: left; width: 540px;">• Ensure that compression Machine, dust extractor, dedusting
                unit is cleaned and arranged properly</td>
        </tr>
        <tr>
            <td style="text-align: left;width: 130;">Previous Product</td>
            <td style="text-align: left;width: 10px;">:</td>
            <td style="text-align: left;width: 130;"></td>
            <td style="text-align: left;width: 130;">Batch No.</td>
            <td style="text-align: left;width: 10px;">:</td>
            <td style="text-align: left; width: 130;"></td>
        </tr>

        <tr>
            <td style="text-align: left;width: 130;">Area Cleaning Done By</td>
            <td style="text-align: left;width: 10px;">:</td>
            <td style="text-align: left;width: 130;"></td>
            <td style="text-align: left;width: 130;">Checked By</td>
            <td style="text-align: left;width: 10px;">:</td>
            <td style="text-align: left; width: 130;"></td>
        </tr>
        <tr>
            <td style="text-align: left;width: 130;">Line Clearance Given By QA</td>
            <td style="text-align: left;width: 10px;">:</td>
            <td style="text-align: left;width: 130;"></td>
            <td style="text-align: left;width: 130;">Date</td>
            <td style="text-align: left;width: 10px;">:</td>
            <td style="text-align: left; width: 130;"></td>
        </tr>
        <tr>
            <td style="text-align: left;width: 130;">Line Clearance SOP Ref No</td>
            <td style="text-align: left;width: 10px;">:</td>
            <td style="text-align: left;width: 130;"></td>
            <td style="text-align: left;width: 130;">Time</td>
            <td style="text-align: left;width: 10px;">:</td>
            <td style="text-align: left; width: 130;"></td>
        </tr>
        <tr>
            <td style="text-align: left;width: 540;"><b>Note: </b>Ensure That Temp. Shall NMT 25°C, % RH. Shall NMT 55%
                during the processing of the materials in Compression area. </td>
        </tr>
    </table>
    <div></div>
    <div style="page-break-before: always;"></div>

    <table style="width: 540px;border: none;">
        <tr>
            <td style="width: 40px; font-weight:bold;border: none;">4.2</td>
            <td style="text-align: left;width: 500px; font-weight:bold;border: none;">Instruction:</td>
        </tr>
        <br>
        <tr>
            <td style="width: 10px;border: none;"></td>
            <td style="width: 40px;border: none;">4.2.1 </td>
            <td style="text-align: left; width: 490px;border: none;">Set up and Operate Compression machine as per SOP .
            </td>
        </tr>
        <tr>
            <td style="width: 10px;border: none;"></td>
            <td style="width: 40px;border: none;">4.2.2 </td>
            <td style="text-align: left; width: 490px;border: none;">Compress the blend after due QC approval,
                Machines-29 Station/51 Station/47 Station </td>
        </tr>
        <tr>
            <td style="width: 10px;border: none;"></td>
            <td style="width: 40px;border: none;">4.2.3</td>
            <td style="text-align: left; width: 490px;border: none;">Compress the blend as per the specification and
                carryout in process checks at specified interval.</td>
        </tr>
    </table>

    <div></div>

    <table style="width: 540px;border: none;">
        <tr>
            <td style="text-align: left;width: 540px; font-weight:bold;border: none;">PUNCHES AND DIES – CHECK RECORD :
                For 29 Station/47 Station/51 Station :</td>
        </tr>

    </table>

    <div></div>

    <table style="width: 540px;" border="1">

        <tr>
            <td style="text-align: center;width: 250;">Description</td>
            <td style="text-align: left;width: 140px;">Fitted by Operator</td>
            <td style="text-align: left;width: 150px;">Checked by Production</td>
        </tr>
        <tr>
            <td style="text-align: left;width: 250;">Upper punches</td>
            <td style="text-align: left;width: 140px;"></td>
            <td style="text-align: left;width: 150px;"></td>

        </tr>
        <tr>
            <td style="text-align: left;width: 250;">Lower Punches</td>
            <td style="text-align: left;width: 140px;"></td>
            <td style="text-align: left;width: 150px;"></td>
        </tr>
        <tr>
            <td style="text-align: left;width: 250;">Dies:</td>
            <td style="text-align: left;width: 140px;"></td>
            <td style="text-align: left;width: 150px;"></td>
        </tr>
    </table>
    <div></div>
    <div></div>
    <table style="width: 540px;" border="1">
        <tr>
            <td style="text-align: center;width: 65;" rowspan="6">Upper Punches</td>
            <td style="text-align: center;width: 25;">1</td>
            <td style="text-align: center;width: 25;">2</td>
            <td style="text-align: center;width: 25;">3</td>
            <td style="text-align: center;width: 25;">4</td>
            <td style="text-align: center;width: 25;">5</td>
            <td style="text-align: center;width: 25;">6</td>
            <td style="text-align: center;width: 25;">7</td>
            <td style="text-align: center;width: 25;">8</td>
            <td style="text-align: center;width: 25;">9</td>
            <td style="text-align: center;width: 25;">10</td>
            <td style="text-align: center;width: 25;">11</td>
            <td style="text-align: center;width: 25;">12</td>
            <td style="text-align: center;width: 25;">13</td>
            <td style="text-align: center;width: 25;">14</td>
            <td style="text-align: center;width: 25;">15</td>
            <td style="text-align: center;width: 25;">16</td>
            <td style="text-align: center;width: 25;">17</td>
            <td style="text-align: center;width: 25;">18</td>
            <td style="text-align: center;width: 25;">19</td>
        </tr>
        <tr>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
        </tr>
        <tr>
            <td style="text-align: center;width: 25;">20</td>
            <td style="text-align: center;width: 25;">21</td>
            <td style="text-align: center;width: 25;">22</td>
            <td style="text-align: center;width: 25;">23</td>
            <td style="text-align: center;width: 25;">24</td>
            <td style="text-align: center;width: 25;">25</td>
            <td style="text-align: center;width: 25;">26</td>
            <td style="text-align: center;width: 25;">27</td>
            <td style="text-align: center;width: 25;">28</td>
            <td style="text-align: center;width: 25;">29</td>
            <td style="text-align: center;width: 25;">30</td>
            <td style="text-align: center;width: 25;">31</td>
            <td style="text-align: center;width: 25;">32</td>
            <td style="text-align: center;width: 25;">33</td>
            <td style="text-align: center;width: 25;">34</td>
            <td style="text-align: center;width: 25;">35</td>
            <td style="text-align: center;width: 25;">36</td>
            <td style="text-align: center;width: 25;">37</td>
            <td style="text-align: center;width: 25;">38</td>
        </tr>
        <tr>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
        </tr>
        <tr>
            <td style="text-align: center;width: 25;">39</td>
            <td style="text-align: center;width: 25;">40</td>
            <td style="text-align: center;width: 25;">41</td>
            <td style="text-align: center;width: 25;">42</td>
            <td style="text-align: center;width: 25;">43</td>
            <td style="text-align: center;width: 25;">44</td>
            <td style="text-align: center;width: 25;">45</td>
            <td style="text-align: center;width: 25;">46</td>
            <td style="text-align: center;width: 25;">47</td>
            <td style="text-align: center;width: 25;">48</td>
            <td style="text-align: center;width: 25;">49</td>
            <td style="text-align: center;width: 25;">50</td>
            <td style="text-align: center;width: 25;">51</td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
        </tr>
        <tr>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
        </tr>

        <tr>
            <td style="text-align: center;width: 65;" rowspan="6">Upper Punches</td>
            <td style="text-align: center;width: 25;">1</td>
            <td style="text-align: center;width: 25;">2</td>
            <td style="text-align: center;width: 25;">3</td>
            <td style="text-align: center;width: 25;">4</td>
            <td style="text-align: center;width: 25;">5</td>
            <td style="text-align: center;width: 25;">6</td>
            <td style="text-align: center;width: 25;">7</td>
            <td style="text-align: center;width: 25;">8</td>
            <td style="text-align: center;width: 25;">9</td>
            <td style="text-align: center;width: 25;">10</td>
            <td style="text-align: center;width: 25;">11</td>
            <td style="text-align: center;width: 25;">12</td>
            <td style="text-align: center;width: 25;">13</td>
            <td style="text-align: center;width: 25;">14</td>
            <td style="text-align: center;width: 25;">15</td>
            <td style="text-align: center;width: 25;">16</td>
            <td style="text-align: center;width: 25;">17</td>
            <td style="text-align: center;width: 25;">18</td>
            <td style="text-align: center;width: 25;">19</td>
        </tr>
        <tr>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
        </tr>
        <tr>
            <td style="text-align: center;width: 25;">20</td>
            <td style="text-align: center;width: 25;">21</td>
            <td style="text-align: center;width: 25;">22</td>
            <td style="text-align: center;width: 25;">23</td>
            <td style="text-align: center;width: 25;">24</td>
            <td style="text-align: center;width: 25;">25</td>
            <td style="text-align: center;width: 25;">26</td>
            <td style="text-align: center;width: 25;">27</td>
            <td style="text-align: center;width: 25;">28</td>
            <td style="text-align: center;width: 25;">29</td>
            <td style="text-align: center;width: 25;">30</td>
            <td style="text-align: center;width: 25;">31</td>
            <td style="text-align: center;width: 25;">32</td>
            <td style="text-align: center;width: 25;">33</td>
            <td style="text-align: center;width: 25;">34</td>
            <td style="text-align: center;width: 25;">35</td>
            <td style="text-align: center;width: 25;">36</td>
            <td style="text-align: center;width: 25;">37</td>
            <td style="text-align: center;width: 25;">38</td>
        </tr>
        <tr>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
        </tr>
        <tr>
            <td style="text-align: center;width: 25;">39</td>
            <td style="text-align: center;width: 25;">40</td>
            <td style="text-align: center;width: 25;">41</td>
            <td style="text-align: center;width: 25;">42</td>
            <td style="text-align: center;width: 25;">43</td>
            <td style="text-align: center;width: 25;">44</td>
            <td style="text-align: center;width: 25;">45</td>
            <td style="text-align: center;width: 25;">46</td>
            <td style="text-align: center;width: 25;">47</td>
            <td style="text-align: center;width: 25;">48</td>
            <td style="text-align: center;width: 25;">49</td>
            <td style="text-align: center;width: 25;">50</td>
            <td style="text-align: center;width: 25;">51</td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
        </tr>
        <tr>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
        </tr>

        <tr>
            <td style="text-align: center;width: 65;" rowspan="6">Upper Punches</td>
            <td style="text-align: center;width: 25;">1</td>
            <td style="text-align: center;width: 25;">2</td>
            <td style="text-align: center;width: 25;">3</td>
            <td style="text-align: center;width: 25;">4</td>
            <td style="text-align: center;width: 25;">5</td>
            <td style="text-align: center;width: 25;">6</td>
            <td style="text-align: center;width: 25;">7</td>
            <td style="text-align: center;width: 25;">8</td>
            <td style="text-align: center;width: 25;">9</td>
            <td style="text-align: center;width: 25;">10</td>
            <td style="text-align: center;width: 25;">11</td>
            <td style="text-align: center;width: 25;">12</td>
            <td style="text-align: center;width: 25;">13</td>
            <td style="text-align: center;width: 25;">14</td>
            <td style="text-align: center;width: 25;">15</td>
            <td style="text-align: center;width: 25;">16</td>
            <td style="text-align: center;width: 25;">17</td>
            <td style="text-align: center;width: 25;">18</td>
            <td style="text-align: center;width: 25;">19</td>
        </tr>
        <tr>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
        </tr>
        <tr>
            <td style="text-align: center;width: 25;">20</td>
            <td style="text-align: center;width: 25;">21</td>
            <td style="text-align: center;width: 25;">22</td>
            <td style="text-align: center;width: 25;">23</td>
            <td style="text-align: center;width: 25;">24</td>
            <td style="text-align: center;width: 25;">25</td>
            <td style="text-align: center;width: 25;">26</td>
            <td style="text-align: center;width: 25;">27</td>
            <td style="text-align: center;width: 25;">28</td>
            <td style="text-align: center;width: 25;">29</td>
            <td style="text-align: center;width: 25;">30</td>
            <td style="text-align: center;width: 25;">31</td>
            <td style="text-align: center;width: 25;">32</td>
            <td style="text-align: center;width: 25;">33</td>
            <td style="text-align: center;width: 25;">34</td>
            <td style="text-align: center;width: 25;">35</td>
            <td style="text-align: center;width: 25;">36</td>
            <td style="text-align: center;width: 25;">37</td>
            <td style="text-align: center;width: 25;">38</td>
        </tr>
        <tr>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
        </tr>
        <tr>
            <td style="text-align: center;width: 25;">39</td>
            <td style="text-align: center;width: 25;">40</td>
            <td style="text-align: center;width: 25;">41</td>
            <td style="text-align: center;width: 25;">42</td>
            <td style="text-align: center;width: 25;">43</td>
            <td style="text-align: center;width: 25;">44</td>
            <td style="text-align: center;width: 25;">45</td>
            <td style="text-align: center;width: 25;">46</td>
            <td style="text-align: center;width: 25;">47</td>
            <td style="text-align: center;width: 25;">48</td>
            <td style="text-align: center;width: 25;">49</td>
            <td style="text-align: center;width: 25;">50</td>
            <td style="text-align: center;width: 25;">51</td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
        </tr>
        <tr>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
            <td style="text-align: center;width: 25;"></td>
        </tr>
    </table>
    <div style="page-break-before: always;"></div>

    <table style="width: 540px; border: none;">
        <tr>
            <td style="text-align: left;width: 540px; font-weight:bold;border: none;">STEP 4.3 COMPRESSION PARAMETERS –
                Specifications</td>
        </tr>
    </table>
    <div></div>
    <table style="width: 540px;" border="1">
        <tr>
            <td style="text-align: center;width: 70px;">Parameter & Frequency</td>
            <td style="text-align: center;width: 105px;">Description Every 30 min.</td>
            <td style="text-align: center;width: 105px;">Avg. wt of 20 tablets Every 30 min.</td>
            <td style="text-align: center;width: 70px;">Thickness Every 30 min.</td>
            <td style="text-align: center;width: 60px;">Hardness Every 30 min.</td>
            <td style="text-align: center;width: 60px;">DT Every 120 min.</td>
            <td style="text-align: center;width: 70px;">Friability Every 120 min.</td>
        </tr>
        <tr>
            <td style="text-align: center;width: 70px;">Standard</td>
            <td style="text-align: center;width: 105px;" rowspan="2">White, circular flat bevel edged uncoated tablet
                with break line on one side & other side plain</td>
            <td style="text-align: center;width: 105px;">11.40g ± 5 %</td>
            <td style="text-align: center;width: 70px;" rowspan="2">4.1 ± 0.5 mm 3.6mm to 4.6 mm</td>
            <td style="text-align: center;width: 60px;" rowspan="2">NLT 3 Kg/cm2</td>
            <td style="text-align: center;width: 60px;" rowspan="2">NMT 15min</td>
            <td style="text-align: center;width: 70px;" rowspan="2">NMT 1 % w/w </td>
        </tr>
        <tr>
            <td style="text-align: center;width: 70px;">Limits</td>
            <td style="text-align: center;width: 105px;">10.830 g to 11.970 g</td>

        </tr>
    </table>
    <div></div>
    <div></div>

    <table style="width: 540px;" border="1">
        <tr>
            <td style="text-align: center;width: 135px;">Operator</td>
            <td style="text-align: center;width: 135px;">Cubicle No.</td>
            <td style="text-align: center;width: 135px;">Equip. No.</td>
            <td style="text-align: center;width: 135px;">M/C speed RPM</td>
        </tr>
        <tr>
            <td style="text-align: center;width: 135px;"></td>
            <td style="text-align: center;width: 135px;"></td>
            <td style="text-align: center;width: 135px;">PD/EQ/ _______</td>
            <td style="text-align: center;width: 135px;"></td>
        </tr>
        <tr>
            <td style="text-align: center;width: 135px;">Compression Start Date </td>
            <td style="text-align: center;width: 135px;"></td>
            <td style="text-align: center;width: 135px;">Compression End Date </td>
            <td style="text-align: center;width: 135px;"></td>
        </tr>
    </table>
    <div></div>
    <div style="page-break-before: always;"></div>

    <table style="width: 540px;" border="1">
        <tr>
            <td style="text-align: center;width: 33px;" rowspan="2">Date</td>
            <td style="text-align: center;width: 35px;" rowspan="2">Time</td>
            <td style="text-align: center;width: 66px;" rowspan="2">Description</td>
            <td style="text-align: center;width: 62px;">Wt.of 20tabs.(gm)</td>
            <td style="text-align: center;width: 62px;">Thickness (mm)</td>
            <td style="text-align: center;width: 62px;">Hardness Kg/cm2 </td>
            <td style="text-align: center;width: 62px;">DT.(min) </td>
            <td style="text-align: center;width: 62px;">Friability %</td>
            <td style="text-align: center;width: 96px;">Checked by Production</td>

        </tr>
        <tr>

            <td style="text-align: center;width: 30px;">LHS</td>
            <td style="text-align: center;width: 32px;">RHS</td>
            <td style="text-align: center;width: 30px;">LHS</td>
            <td style="text-align: center;width: 32px;">RHS</td>
            <td style="text-align: center;width: 30px;">LHS</td>
            <td style="text-align: center;width: 32px;">RHS</td>
            <td style="text-align: center;width: 30px;">LHS</td>
            <td style="text-align: center;width: 32px;">RHS</td>
            <td style="text-align: center;width: 30px;">LHS</td>
            <td style="text-align: center;width: 32px;">RHS</td>
            <td style="text-align: center;width: 54px;">Operator</td>
            <td style="text-align: center;width: 42px;">Officer</td>
        </tr>
        <tr>
            <td style="text-align: center;width: 33px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 66px;"></td>
            <td style="text-align: center;width: 30px;"></td>
            <td style="text-align: center;width: 32px;"></td>
            <td style="text-align: center;width: 30px;"></td>
            <td style="text-align: center;width: 32px;"></td>
            <td style="text-align: center;width: 30px;" rowspan="2"></td>
            <td style="text-align: center;width: 32px;" rowspan="2"></td>
            <td style="text-align: center;width: 30px;" rowspan="4"></td>
            <td style="text-align: center;width: 32px;" rowspan="4"></td>
            <td style="text-align: center;width: 30px;" rowspan="4"></td>
            <td style="text-align: center;width: 32px;" rowspan="4"></td>
            <td style="text-align: center;width: 54px;" rowspan="4"></td>
            <td style="text-align: center;width: 42px;" rowspan="4"></td>
        </tr>
        <tr>
            <td style="text-align: center;width: 33px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 66px;"></td>
            <td style="text-align: center;width: 30px;"></td>
            <td style="text-align: center;width: 32px;"></td>
            <td style="text-align: center;width: 30px;"></td>
            <td style="text-align: center;width: 32px;"></td>

        </tr>
        <tr>
            <td style="text-align: center;width: 33px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 66px;"></td>
            <td style="text-align: center;width: 30px;"></td>
            <td style="text-align: center;width: 32px;"></td>
            <td style="text-align: center;width: 30px;"></td>
            <td style="text-align: center;width: 32px;"></td>
            <td style="text-align: center;width: 30px;" rowspan="2"></td>
            <td style="text-align: center;width: 32px;" rowspan="2"></td>

        </tr>
        <tr>
            <td style="text-align: center;width: 33px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 66px;"></td>
            <td style="text-align: center;width: 30px;"></td>
            <td style="text-align: center;width: 32px;"></td>
            <td style="text-align: center;width: 30px;"></td>
            <td style="text-align: center;width: 32px;"></td>


        </tr>
    </table>
    <div></div>
    <div style="page-break-before: always;"></div>

    <table style="width: 540px;" border="1">
        <tr>
            <td style="text-align: center;width: 33px;" rowspan="2">Date</td>
            <td style="text-align: center;width: 35px;" rowspan="2">Time</td>
            <td style="text-align: center;width: 66px;" rowspan="2">Description</td>
            <td style="text-align: center;width: 62px;">Wt.of 20tabs.(gm)</td>
            <td style="text-align: center;width: 62px;">Thickness (mm)</td>
            <td style="text-align: center;width: 62px;">Hardness Kg/cm2 </td>
            <td style="text-align: center;width: 62px;">DT.(min) </td>
            <td style="text-align: center;width: 62px;">Friability %</td>
            <td style="text-align: center;width: 96px;">Checked by Production</td>

        </tr>
        <tr>

            <td style="text-align: center;width: 30px;">LHS</td>
            <td style="text-align: center;width: 32px;">RHS</td>
            <td style="text-align: center;width: 30px;">LHS</td>
            <td style="text-align: center;width: 32px;">RHS</td>
            <td style="text-align: center;width: 30px;">LHS</td>
            <td style="text-align: center;width: 32px;">RHS</td>
            <td style="text-align: center;width: 30px;">LHS</td>
            <td style="text-align: center;width: 32px;">RHS</td>
            <td style="text-align: center;width: 30px;">LHS</td>
            <td style="text-align: center;width: 32px;">RHS</td>
            <td style="text-align: center;width: 54px;">Operator</td>
            <td style="text-align: center;width: 42px;">Officer</td>
        </tr>
        <tr>
            <td style="text-align: center;width: 33px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 66px;"></td>
            <td style="text-align: center;width: 30px;"></td>
            <td style="text-align: center;width: 32px;"></td>
            <td style="text-align: center;width: 30px;"></td>
            <td style="text-align: center;width: 32px;"></td>
            <td style="text-align: center;width: 30px;" rowspan="2"></td>
            <td style="text-align: center;width: 32px;" rowspan="2"></td>
            <td style="text-align: center;width: 30px;" rowspan="4"></td>
            <td style="text-align: center;width: 32px;" rowspan="4"></td>
            <td style="text-align: center;width: 30px;" rowspan="4"></td>
            <td style="text-align: center;width: 32px;" rowspan="4"></td>
            <td style="text-align: center;width: 54px;" rowspan="4"></td>
            <td style="text-align: center;width: 42px;" rowspan="4"></td>
        </tr>
        <tr>
            <td style="text-align: center;width: 33px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 66px;"></td>
            <td style="text-align: center;width: 30px;"></td>
            <td style="text-align: center;width: 32px;"></td>
            <td style="text-align: center;width: 30px;"></td>
            <td style="text-align: center;width: 32px;"></td>

        </tr>
        <tr>
            <td style="text-align: center;width: 33px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 66px;"></td>
            <td style="text-align: center;width: 30px;"></td>
            <td style="text-align: center;width: 32px;"></td>
            <td style="text-align: center;width: 30px;"></td>
            <td style="text-align: center;width: 32px;"></td>
            <td style="text-align: center;width: 30px;" rowspan="2"></td>
            <td style="text-align: center;width: 32px;" rowspan="2"></td>

        </tr>
        <tr>
            <td style="text-align: center;width: 33px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 66px;"></td>
            <td style="text-align: center;width: 30px;"></td>
            <td style="text-align: center;width: 32px;"></td>
            <td style="text-align: center;width: 30px;"></td>
            <td style="text-align: center;width: 32px;"></td>


        </tr>
    </table>
    <div></div>
    <div style="page-break-before: always;"></div>

    <table style="width: 540px;" border="1">
        <tr>
            <td style="text-align: center;width: 33px;" rowspan="2">Date</td>
            <td style="text-align: center;width: 35px;" rowspan="2">Time</td>
            <td style="text-align: center;width: 66px;" rowspan="2">Description</td>
            <td style="text-align: center;width: 62px;">Wt.of 20tabs.(gm)</td>
            <td style="text-align: center;width: 62px;">Thickness (mm)</td>
            <td style="text-align: center;width: 62px;">Hardness Kg/cm2 </td>
            <td style="text-align: center;width: 62px;">DT.(min) </td>
            <td style="text-align: center;width: 62px;">Friability %</td>
            <td style="text-align: center;width: 96px;">Checked by Production</td>

        </tr>
        <tr>

            <td style="text-align: center;width: 30px;">LHS</td>
            <td style="text-align: center;width: 32px;">RHS</td>
            <td style="text-align: center;width: 30px;">LHS</td>
            <td style="text-align: center;width: 32px;">RHS</td>
            <td style="text-align: center;width: 30px;">LHS</td>
            <td style="text-align: center;width: 32px;">RHS</td>
            <td style="text-align: center;width: 30px;">LHS</td>
            <td style="text-align: center;width: 32px;">RHS</td>
            <td style="text-align: center;width: 30px;">LHS</td>
            <td style="text-align: center;width: 32px;">RHS</td>
            <td style="text-align: center;width: 54px;">Operator</td>
            <td style="text-align: center;width: 42px;">Officer</td>
        </tr>
        <tr>
            <td style="text-align: center;width: 33px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 66px;"></td>
            <td style="text-align: center;width: 30px;"></td>
            <td style="text-align: center;width: 32px;"></td>
            <td style="text-align: center;width: 30px;"></td>
            <td style="text-align: center;width: 32px;"></td>
            <td style="text-align: center;width: 30px;" rowspan="2"></td>
            <td style="text-align: center;width: 32px;" rowspan="2"></td>
            <td style="text-align: center;width: 30px;" rowspan="4"></td>
            <td style="text-align: center;width: 32px;" rowspan="4"></td>
            <td style="text-align: center;width: 30px;" rowspan="4"></td>
            <td style="text-align: center;width: 32px;" rowspan="4"></td>
            <td style="text-align: center;width: 54px;" rowspan="4"></td>
            <td style="text-align: center;width: 42px;" rowspan="4"></td>
        </tr>
        <tr>
            <td style="text-align: center;width: 33px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 66px;"></td>
            <td style="text-align: center;width: 30px;"></td>
            <td style="text-align: center;width: 32px;"></td>
            <td style="text-align: center;width: 30px;"></td>
            <td style="text-align: center;width: 32px;"></td>
        </tr>
        <tr>
            <td style="text-align: center;width: 33px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 66px;"></td>
            <td style="text-align: center;width: 30px;"></td>
            <td style="text-align: center;width: 32px;"></td>
            <td style="text-align: center;width: 30px;"></td>
            <td style="text-align: center;width: 32px;"></td>
            <td style="text-align: center;width: 30px;" rowspan="2"></td>
            <td style="text-align: center;width: 32px;" rowspan="2"></td>
        </tr>
        <tr>
            <td style="text-align: center;width: 33px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 66px;"></td>
            <td style="text-align: center;width: 30px;"></td>
            <td style="text-align: center;width: 32px;"></td>
            <td style="text-align: center;width: 30px;"></td>
            <td style="text-align: center;width: 32px;"></td>
        </tr>
    </table>
    <div></div>
    <div style="page-break-before: always;border: none;"></div>
    <table style="width: 540px;">
        <tr>
            <td style="text-align: left;width: 540px; font-weight:bold;border: none;">STEP 4.4 INDIVIDUAL WEIGHT
                VARIATION RECORD</td>
        </tr><br>
        <tr>
            <td style="text-align: left;width: 540px;border: none;">To be done by Production Supervisor and IPQA officer
                Alternatively Means Production Chemist will perform checks Initial,08,16 ....and IPQA Officer at
                04,12,...so on</td>
        </tr><br>
        <tr>
            <td style="text-align: left;width: 540px; font-weight:bold;border: none;">Individual wt. variation: 570 mg ±
                5% (541.5 mg – 598.5 mg) Frequency: 240 min.</td>
        </tr><br>
    </table>
    <div></div>
    <div></div>
    <table style="width: 540px;" border="1">
        <tr>
            <td style="text-align: center;width: 50px;" rowspan="3">No. of Tablets</td>
            <td style="text-align: center;width: 70px;">Wt. (mg) Date:</td>
            <td style="text-align: center;width: 70px;">Wt. (mg) Date: </td>
            <td style="text-align: center;width: 70px;">Wt. (mg) Date:</td>
            <td style="text-align: center;width: 70px;">Wt. (mg) Date:</td>
            <td style="text-align: center;width: 70px;">Wt. (mg) Date: </td>
            <td style="text-align: center;width: 70px;">Wt. (mg) Date: </td>
            <td style="text-align: center;width: 70px;">Wt. (mg) Date:</td>

        </tr>
        <tr>
            <td style="text-align: center;width: 70px;">Time:</td>
            <td style="text-align: center;width: 70px;">Time:</td>
            <td style="text-align: center;width: 70px;">Time:</td>
            <td style="text-align: center;width: 70px;">Time:</td>
            <td style="text-align: center;width: 70px;">Time:</td>
            <td style="text-align: center;width: 70px;">Time:</td>
            <td style="text-align: center;width: 70px;">Time:</td>
        </tr>
        <tr>
            <td style="text-align: center;width: 35px;">LHS</td>
            <td style="text-align: center;width: 35px;">RHS</td>
            <td style="text-align: center;width: 35px;">LHS</td>
            <td style="text-align: center;width: 35px;">RHS</td>
            <td style="text-align: center;width: 35px;">LHS</td>
            <td style="text-align: center;width: 35px;">RHS</td>
            <td style="text-align: center;width: 35px;">LHS</td>
            <td style="text-align: center;width: 35px;">RHS</td>
            <td style="text-align: center;width: 35px;">LHS</td>
            <td style="text-align: center;width: 35px;">RHS</td>
            <td style="text-align: center;width: 35px;">LHS</td>
            <td style="text-align: center;width: 35px;">RHS</td>
            <td style="text-align: center;width: 35px;">LHS</td>
            <td style="text-align: center;width: 35px;">RHS</td>
        </tr>
        <tr>
            <td style="text-align: center;width: 50px;">1</td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
        </tr>
        <tr>
            <td style="text-align: center;width: 50px;">Total (T)</td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
        </tr>
        <tr>
            <td style="text-align: center;width: 50px;">Avg(A)=T/20</td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
        </tr>
        <tr>
            <td style="text-align: center;width: 50px;">Min Wt. </td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
        </tr>
        <tr>
            <td style="text-align: center;width: 50px;">Max Wt.</td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
        </tr>
        <tr>
            <td style="text-align: center;width: 50px;">- %*</td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
        </tr>
        <tr>
            <td style="text-align: center;width: 50px;">+ %*</td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
        </tr>
        <tr>
            <td style="text-align: center;width: 50px;">No. of tab above 5%#</td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
        </tr>
        <tr>
            <td style="text-align: center;width: 50px;">No. of tab below5%#</td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
        </tr>
        <tr>
            <td style="text-align: center;width: 50px;">Check By</td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
            <td style="text-align: center;width: 35px;"></td>
        </tr>
    </table>
    <div></div>
    <div></div>
    <table style="width: 540px;border: none;">
        <tr>
            <td style="text-align: left;width: 540px;border: none;">* + 5% of the average weight. # NMT 2 tablets are
                more than 5% & none more than 10%.</td>
        </tr>
        <tr>
            <td style="text-align: left;width: 540px;border: none;">Calculate as -% = A- Min x 100/A +% = Max- A x 100/A
            </td>
        </tr>

    </table>
    <div style="page-break-before: always;"></div>
    <table style="width: 540px;">
        <tr>
            <td style="text-align: left;width: 540px; font-weight:bold;border: none;">STEP 4.5 COMPRESSED TABLETS
                CONTAINE</td>
        </tr><br>
        <tr>
            <td style="text-align: left;width: 540px; font-weight:bold;border: none;">RS WEIGHING RECORD</td>
        </tr><br>
        <tr>
            <td style="text-align: left;width: 270px; font-weight:bold;border: none;">Balance ID:</td>
            <td style="text-align: left;width: 270px; font-weight:bold;border: none;">Date:____________</td>
        </tr>
    </table>
    <div></div>
    <table style="width: 540px;" border="1">

        <tr>

            <td style="text-align: center;width: 54px;">Drum No</td>
            <td style="text-align: center;width: 54px;">Gross Wt (Kg)</td>
            <td style="text-align: center;width: 54px;">Tare Wt (Kg)</td>
            <td style="text-align: center;width: 54px;">Net Wt (Kg)</td>
            <td style="text-align: center;width: 54px;">Done By</td>
            <td style="text-align: center;width: 54px;">Drum No</td>
            <td style="text-align: center;width: 54px;">Gross Wt (Kg)</td>
            <td style="text-align: center;width: 54px;">Tare Wt (Kg)</td>
            <td style="text-align: center;width: 54px;">Net Wt (Kg)</td>
            <td style="text-align: center;width: 54px;">Done By</td>
        </tr>
        <tr>

            <td style="text-align: center;width: 54px;">01</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;">11</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
        </tr>
        <tr>

            <td style="text-align: center;width: 54px;">02</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;">12</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
        </tr>
        <tr>

            <td style="text-align: center;width: 54px;">03</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;">13</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
        </tr>
        <tr>

            <td style="text-align: center;width: 54px;">04</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;">14</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
        </tr>
        <tr>

            <td style="text-align: center;width: 54px;">05</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;">15</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
        </tr>
        <tr>

            <td style="text-align: center;width: 54px;">06</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;">16</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
        </tr>
        <tr>

            <td style="text-align: center;width: 54px;">07</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;">17</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
        </tr>
        <tr>

            <td style="text-align: center;width: 54px;">08</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;">18</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
        </tr>
        <tr>

            <td style="text-align: center;width: 54px;">09</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;">19</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
        </tr>
        <tr>

            <td style="text-align: center;width: 54px;">10</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;">20</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
        </tr>
    </table>
    <div></div>
    <div style="page-break-before: always;"></div>

    <table style="width: 540px;border: none;">
        <tr>
            <td style="text-align: left;width: 540px; font-weight:bold;border: none;">COMPRESSED TABLETS CONTAINERS
                WEIGHING RECORD</td>
        </tr><br>
        <tr>
            <td style="text-align: left;width: 270px; font-weight:bold;border: none;">Balance ID:</td>
            <td style="text-align: left;width: 270px; font-weight:bold;border: none;">Date:____________</td>
        </tr>
    </table>
    <div></div>
    <table style="width: 540px;" border="1">

        <tr>

            <td style="text-align: center;width: 54px;">Drum No</td>
            <td style="text-align: center;width: 54px;">Gross Wt (Kg)</td>
            <td style="text-align: center;width: 54px;">Tare Wt (Kg)</td>
            <td style="text-align: center;width: 54px;">Net Wt (Kg)</td>
            <td style="text-align: center;width: 54px;">Done By</td>
            <td style="text-align: center;width: 54px;">Drum No</td>
            <td style="text-align: center;width: 54px;">Gross Wt (Kg)</td>
            <td style="text-align: center;width: 54px;">Tare Wt (Kg)</td>
            <td style="text-align: center;width: 54px;">Net Wt (Kg)</td>
            <td style="text-align: center;width: 54px;">Done By</td>
        </tr>
        <tr>

            <td style="text-align: center;width: 54px;">01</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;">11</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
        </tr>
        <tr>

            <td style="text-align: center;width: 54px;">02</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;">12</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
        </tr>
        <tr>

            <td style="text-align: center;width: 54px;">03</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;">13</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
        </tr>
        <tr>

            <td style="text-align: center;width: 54px;">04</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;">14</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
        </tr>
        <tr>

            <td style="text-align: center;width: 54px;">05</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;">15</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
        </tr>
        <tr>

            <td style="text-align: center;width: 54px;">06</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;">16</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
        </tr>
        <tr>

            <td style="text-align: center;width: 54px;">07</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;">17</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
        </tr>
        <tr>

            <td style="text-align: center;width: 54px;">08</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;">18</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
        </tr>
        <tr>

            <td style="text-align: center;width: 54px;">09</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;">19</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
        </tr>
        <tr>

            <td style="text-align: center;width: 54px;">10</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;">20</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
        </tr>
    </table>
    <div></div>
    <table style="width: 540px;border: none;">
        <tr>
            <td style="text-align: left;width: 540px; font-weight:bold;border: none;">Total Number of Containers:
                _______________ </td>
        </tr><br>
        <tr>
            <td style="text-align: left;width: 540px; font-weight:bold;border: none;">Total Net weight of the Tablets:
                _______________Kg. = No. of Tablets: _______________</td>
        </tr><br>
        <tr>
            <td style="text-align: left;width: 270px; font-weight:bold;border: none;"> INPROCESS YIELD: </td>
        </tr><br>
    </table>
    <div></div>

    <table style="width: 540px;" border="1">
        <tr>
            <td style="text-align: center; font-weight:bold;width: 70px;">Theoretical batch Yield (Kg) A</td>
            <td style="text-align: center; font-weight:bold;width: 70px;">Weight of Granules Received (B)</td>
            <td style="text-align: center; font-weight:bold;width: 70px;">No of Tablets to be Compressed (C)</td>
            <td style="text-align: center; font-weight:bold;width: 70px;">Weight of Tablets After Compression (D)</td>
            <td style="text-align: center; font-weight:bold;width: 65px;">No of tablets Compressed (E)</td>
            <td style="text-align: center; font-weight:bold;width: 65px;">Loss during Compression = B- D </td>
            <td style="text-align: center; font-weight:bold;width: 65px;">Recoverable Tablets</td>
            <td style="text-align: center; font-weight:bold;width: 65px;">Percentage Yield = D X 100/A</td>
        </tr>
        <tr>
            <td style="text-align: center;width: 70px;"></td>
            <td style="text-align: center;width: 70px;"></td>
            <td style="text-align: center;width: 70px;"></td>
            <td style="text-align: center;width: 70px;"></td>
            <td style="text-align: center;width: 65px;"></td>
            <td style="text-align: center;width: 65px;"></td>
            <td style="text-align: center;width: 65px;"></td>
            <td style="text-align: center;width: 65px;"></td>
        </tr>
    </table>
    <div style="page-break-before: always;"></div>
    <table style="width: 540px;border: none;">
        <tr>
            <td style="text-align: left;width: 540px; font-weight:bold;border: none;">STEP 4 .6 COMPRESSED TABLETS
                INSPECTION RECORD</td>
        </tr><br>
        <tr>
            <td style="text-align: left;width: 540px;border: none;">Transfer the tablets to inspection area and inspect
                the tablets for broken, chipped, black spots and defective tablets on inspection belt or on SS tray
                using butter paper.</td>
        </tr><br>
        <tr>
            <td style="text-align: left;width: 540px;border: none;"> Operation done by: - ______________From:- _________
                to: - ____________</td>
        </tr><br>
        <tr>
            <td style="text-align: left;width: 540px;border: none;"> Total tablets taken for inspection (A):-
                ______________ kg.</td>
        </tr><br>
        <tr>
            <td style="text-align: left;width: 540px;border: none;"> Total recoverable tablets after inspection (B):-
                ______________ kg.</td>
        </tr><br>
        <tr>
            <td style="text-align: left;width: 540px;border: none;"> Total Good tablets after inspection (A-B):
                ______________ kg</td>
        </tr><br>
        <tr>
            <td style="text-align: left;width: 540px;border: none;"> % Yield _______________ (NLT 98.0 %) </td>
        </tr>
    </table>
    <div style="page-break-before: always;"></div>
    <table style="width: 540px;border: none;">
        <tr>
            <td style="text-align: left;width: 540px; font-weight:bold;border: none;">4.6.1 WEIGHT OF SORTED TABLETS
            </td>
        </tr><br>
        <tr>
            <td style="text-align: left;width: 270px; font-weight:bold;border: none;">Balance ID:</td>
            <td style="text-align: left;width: 270px; font-weight:bold;border: none;">Date:____________</td>
        </tr>
    </table>
    <div></div>
    <table style="width: 540px;" border="1">

        <tr>

            <td style="text-align: center;width: 54px;">Drum No</td>
            <td style="text-align: center;width: 54px;">Gross Wt (Kg)</td>
            <td style="text-align: center;width: 54px;">Tare Wt (Kg)</td>
            <td style="text-align: center;width: 54px;">Net Wt (Kg)</td>
            <td style="text-align: center;width: 54px;">Done By</td>
            <td style="text-align: center;width: 54px;">Drum No</td>
            <td style="text-align: center;width: 54px;">Gross Wt (Kg)</td>
            <td style="text-align: center;width: 54px;">Tare Wt (Kg)</td>
            <td style="text-align: center;width: 54px;">Net Wt (Kg)</td>
            <td style="text-align: center;width: 54px;">Done By</td>
        </tr>
        <tr>

            <td style="text-align: center;width: 54px;">01</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;">11</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
        </tr>
        <tr>

            <td style="text-align: center;width: 54px;">02</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;">12</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
        </tr>
        <tr>

            <td style="text-align: center;width: 54px;">03</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;">13</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
        </tr>
        <tr>

            <td style="text-align: center;width: 54px;">04</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;">14</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
        </tr>
        <tr>

            <td style="text-align: center;width: 54px;">05</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;">15</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
        </tr>
        <tr>

            <td style="text-align: center;width: 54px;">06</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;">16</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
        </tr>
        <tr>

            <td style="text-align: center;width: 54px;">07</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;">17</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
        </tr>
        <tr>

            <td style="text-align: center;width: 54px;">08</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;">18</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
        </tr>
        <tr>

            <td style="text-align: center;width: 54px;">09</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;">19</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
        </tr>
        <tr>

            <td style="text-align: center;width: 54px;">10</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;">20</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
        </tr>
    </table>
    <div></div>

    <div style="page-break-before: always;"></div>
    <table style="width: 540px;border: none;">
        <tr>
            <td style="text-align: left;width: 540px; font-weight:bold;border: none;">WEIGHT OF SORTED TABLETS </td>
        </tr><br>
        <tr>
            <td style="text-align: left;width: 270px; font-weight:bold;border: none;">Balance ID:</td>
            <td style="text-align: left;width: 270px; font-weight:bold;border: none;">Date:____________</td>
        </tr>
    </table>
    <div></div>
    <table style="width: 540px;" border="1">
        <tr>
            <td style="text-align: center;width: 54px;">Drum No</td>
            <td style="text-align: center;width: 54px;">Gross Wt (Kg)</td>
            <td style="text-align: center;width: 54px;">Tare Wt (Kg)</td>
            <td style="text-align: center;width: 54px;">Net Wt (Kg)</td>
            <td style="text-align: center;width: 54px;">Done By</td>
            <td style="text-align: center;width: 54px;">Drum No</td>
            <td style="text-align: center;width: 54px;">Gross Wt (Kg)</td>
            <td style="text-align: center;width: 54px;">Tare Wt (Kg)</td>
            <td style="text-align: center;width: 54px;">Net Wt (Kg)</td>
            <td style="text-align: center;width: 54px;">Done By</td>
        </tr>
        <tr>
            <td style="text-align: center;width: 54px;">01</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;">11</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
        </tr>
        <tr>
            <td style="text-align: center;width: 54px;">02</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;">12</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
        </tr>
        <tr>
            <td style="text-align: center;width: 54px;">03</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;">13</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
        </tr>
        <tr>
            <td style="text-align: center;width: 54px;">04</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;">14</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
        </tr>
        <tr>
            <td style="text-align: center;width: 54px;">05</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;">15</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
        </tr>
        <tr>
            <td style="text-align: center;width: 54px;">06</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;">16</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
        </tr>
        <tr>
            <td style="text-align: center;width: 54px;">07</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;">17</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
        </tr>
        <tr>
            <td style="text-align: center;width: 54px;">08</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;">18</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
        </tr>
        <tr>
            <td style="text-align: center;width: 54px;">09</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;">19</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
        </tr>
        <tr>
            <td style="text-align: center;width: 54px;">10</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;">20</td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
            <td style="text-align: center;width: 54px;"></td>
        </tr>
    </table>
    <div></div>
    <div></div>
    <table style="width: 540px;border: none;">
        <tr>
            <td style="text-align: left;width: 540px; font-weight:bold;border: none;">Total Number of Containers:
                _______________ </td>
        </tr><br>
        <tr>
            <td style="text-align: left;width: 540px; font-weight:bold;border: none;">Total Net weight of the Tablets:
                _______________Kg. = No. of Tablets: _______________</td>
        </tr>
    </table>
    <div style="page-break-before: always;"></div>

    <table  border="1" style="width: 540px;">
        <tr>
            <td style="text-align: left;width: 540px; font-weight:bold;border: none;">STEP 4.7 COMPRESSED TABLETS
                SAMPLING DETAILS</td>
        </tr><br>
        <tr>
            <td style="text-align: left;width: 180px;border: none;">Intimation given by:</td>
            <td style="text-align: left;width: 180px;border: none;"> Sign/Date: _____________</td>
            <td style="text-align: left;width: 180px;border: none;">Time: ___________</td>
        </tr><br>
        <tr>
            <td style="text-align: left;width: 180px;border: none;">Sampled by (IPQA):</td>
            <td style="text-align: left;width: 180px;border: none;"> Sign/Date: _____________</td>
            <td style="text-align: left;width: 180px;border: none;">Time: ___________</td>
        </tr>
    </table>
    <div></div>
    <table style="width: 540px; border: none;">
        <tr>
            <td style="text-align: left;width: 540px; font-weight:bold; border: none;">STEP 4.8 YIELD RECONCILIATION
            </td>
        </tr>
    </table>
    <div></div>
    <table style="width: 540px;" border="1">
        <tr>
            <td style="text-align: center; font-weight:bold;width: 90px;">STAGE </td>
            <td style="text-align: center; font-weight:bold;width: 90px;">THEORETICAL BATCH SIZE(A)</td>
            <td style="text-align: center; font-weight:bold;width: 90px;">ACTUAL YIELD (B)</td>
            <td style="text-align: center; font-weight:bold;width: 90px;">% YIELD = B X 100 A</td>
            <td style="text-align: center; font-weight:bold;width: 90px;">CHECKED BY (PROD)</td>
            <td style="text-align: center; font-weight:bold;width: 90px;">VERIFIED BY (QA)</td>
        </tr>
        <tr>
            <td style="text-align: center;width: 90px;"></td>
            <td style="text-align: center;width: 90px;"></td>
            <td style="text-align: center;width: 90px;"></td>
            <td style="text-align: center;width: 90px;"></td>
            <td style="text-align: center;width: 90px;"></td>
            <td style="text-align: center;width: 90px;"></td>
        </tr>
    </table>
    <div style="page-break-before: always;"></div>
    <table style="width: 540px;" border="1">
        <tr>
            <td style="text-align: left;width: 540px; font-weight:bold">Document checked by:</td>
        </tr>
        <tr>
            <td style="text-align: left;width: 270px;">Production Chemist: </td>
            <td style="text-align: left;width: 270px;">Quality Assurance: </td>
        </tr>
        <tr>
            <td style="text-align: left;width: 270px;">Sign / Date:</td>
            <td style="text-align: left;width: 270px;">Sign / Date:</td>
        </tr>
    </table>
    <div></div>
    <div></div>
    <table style="width: 540px;">
        <tr>
            <td style="text-align: left;width: 540px; font-weight:bold;border: none;">STEP 4.9 Destruct Irrecoverable
                Rejection Tablet by putting in water in presence of QA. </td>
        </tr><br>
        <tr>
            <td style="text-align: left;width: 270px; font-weight:bold; border: none;">SOP No.:</td>
        </tr>
    </table>
    <div></div>
    <table style="width: 540px;" border="1">
        <tr>
            <td style="text-align: left;width: 270px;">Destruction supervised done by (Production): </td>
            <td style="text-align: left;width: 270px;"></td>
        </tr>
        <tr>
            <td style="text-align: left;width: 270px;">In presence of QA: </td>
            <td style="text-align: left;width: 270px;"></td>

        </tr>
        <tr>
            <td style="text-align: left;width: 270px;">Sign & Date:</td>
            <td style="text-align: left;width: 270px;"></td>
        </tr>
    </table>
    <div></div>
    <table style="width: 540px;border: none;">
        <tr>
            <td style="text-align: left;width: 540px; font-weight:bold;border: none;">STEP 4.10 DEVIATION APPROVAL SHEET
            </td>
        </tr>
    </table>
    <div></div>
    <table style="width: 540px;" border="1">
        <tr>
            <td style="text-align: center; font-weight:bold;width: 135px;">DEVIATION </td>
            <td style="text-align: center; font-weight:bold;width: 135px;">REASON & JUSTIFICATION</td>
            <td style="text-align: center; font-weight:bold;width: 135px;">PROPOSED BY Production Chemist</td>
            <td style="text-align: center; font-weight:bold;width: 135px;">APPROVED BY QA</td>
        </tr>
        <tr>
            <td style="text-align: center;width: 135px;"></td>
            <td style="text-align: center;width: 135px;"></td>
            <td style="text-align: center;width: 135px;"></td>
            <td style="text-align: center;width: 135px;"></td>
        </tr>
    </table>

';}}
           $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('BMR2.pdf', 'I');
    
  }
 }
else{
    echo "Invalid Token";
}
?>