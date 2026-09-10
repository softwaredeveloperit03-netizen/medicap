<?php
require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';
$output = Array();
$token = $_GET["token"];
$sql = "SELECT * FROM token WHERE token='".$_GET["token"]."'";
$result = $conn->query($sql);
$_GET["emp_id"] = "";
$_GET["department"] = "";
 try{
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
    
    if ($_GET["type"] == "saveSalaryHead") {
        
        $sql = "Select * from salary_head where emp_type = '".$input["emp_type"]."' and plant_id =   '".$_GET["plant_id"]."'";
        
         $result = $conn->query($sql);
        if ($result->num_rows > 0) {
          	echo "{\"status\":\"Salary Type Already Exists. Duplicate Values are not allowed\"}";
        }else{
            $sql = "INSERT INTO salary_head (plant_id, entry_by, entry_date, emp_type, pf_applicable) VALUES (
                '".$_GET["plant_id"]."', '".$_GET["emp_id"]."', '".$entry_date."','".$input["emp_type"]."', '".$input["pf_applicable"]."')";
              
            if ($conn->query($sql)) {
                 $sal_hdr_id = $conn->insert_id;
                 $earnings = json_encode($input["earning"]);   
                 $arr = json_decode($earnings,true);
                 foreach($arr as $item) { 
                    echo $item['deduction_head'];
                    $sql = "Insert into salary_head_details(salary_hdr_id,salary_head_group,salary_head,calc_type,percent,amount,type_flag) 
                    values('".$sal_hdr_id."','Earnings','".$item["salary_head"]."','".$item["calc_type"]."','".$item["gross_perc"]."','".$item["gross_amt"]."','".$item["type_flag"]."')";
                    $conn->query($sql);
                     
                 }
                 
                 
                 $deductions = json_encode($input["deduction"]);   
                 $arr = json_decode($deductions,true);
                 foreach($arr as $item) { 
                    /*$sql = "Insert into salary_head_details(salary_hdr_id,salary_head_group,salary_head,percent,amount) 
                    values('".$sal_hdr_id."','Deductions','".$item["deduction_head"]."','0','".$item["d_per_month"]."')";
                    $conn->query($sql);*/
                     $sql = "Insert into salary_head_details(salary_hdr_id,salary_head_group,salary_head,calc_type,percent,amount,type_flag) 
                    values('".$sal_hdr_id."','Deductions','".$item["deduction_head"]."','".$item["calc_type"]."','".$item["gross_perc"]."','".$item["gross_amt"]."','".$item["type_flag"]."')";
                    $conn->query($sql);
                    
                 }
                 
                     
                 $bonuses = json_encode($input["ctc"]);   
                 $arr = json_decode($bonuses,true);
                 foreach($arr as $item) { 
                   /* $sql = "Insert into salary_head_details(salary_hdr_id,salary_head_group,salary_head,percent,amount) 
                    values('".$sal_hdr_id."','CTC Calculation','".$item["salary_head"]."','0','".$item["b_per_month"]."')";
                    $conn->query($sql);*/
                     $sql = "Insert into salary_head_details(salary_hdr_id,salary_head_group,salary_head,calc_type,percent,amount,type_flag) 
                    values('".$sal_hdr_id."','CTC Calculation','".$item["salary_head"]."','".$item["calc_type"]."','".$item["gross_perc"]."','".$item["gross_amt"]."','".$item["type_flag"]."')";
                    $conn->query($sql);
                     
                 }
                 echo "{\"status\":\"success\"}";
            } else {
                echo "{\"status\":\"".$conn->error."\"}";
            }
        }
    } else if ($_GET["type"] == "deleteSalaryHead") {
        $sql = "UPDATE salary_head SET status='".$_GET["status"]."', entry_by='".$_GET["emp_id"]."', entry_date='$entry_date'
        WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "updateSalaryHead") {
        $sql = "UPDATE salary_head SET department='".$input["department"]."', emp_type='".$input["emp_type"]."', earning='".$input["earning"]."',deduction='".$input["deduction"]."', bonus='".$input["bonus"]."' WHERE id='".$input["id"]."'";
        
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "getSalaryHead") {
        try{
        $output = array();
        $output1 = array();
        $plant = $_GET["plant_id"];
        $id=0;
        $sql = "Select * from salary_head where emp_type = '".$_GET["emp_type"]."' 
        and plant_id = '".$_GET["plant_id"]."' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
               $id  = $row['id'];
            }
            $sql = "select * from salary_head_details where salary_hdr_id= '".$id."'  
            AND salary_head_group='Earnings'";
            $result = $conn->query($sql);
            while ($row = $result->fetch_assoc()) {
               $output[]  = $row;
            }
            $output1["Earnings"] = $output;
             
            $output = array();
            $sql = "select * from salary_head_details where salary_hdr_id= '".$id."'  AND salary_head_group='Deductions'";
            $result = $conn->query($sql);
            while ($row = $result->fetch_assoc()) {
               $output[]  = $row;
            }
             $output1["Deductions"] = $output;
             
            $output = array();
            $sql = "select * from salary_head_details where salary_hdr_id= '".$id."'  AND salary_head_group='CTC Calculation'";
            $result = $conn->query($sql);
            while ($row = $result->fetch_assoc()) {
               $output[]  = $row;
            }
             $output1["CTC"] = $output; 
        }
        echo json_encode($output1);
        }  catch (\Throwable $e) {
               echo "{\"statuse\":\"".$e."\"}";
            }
    } 
    else if ($_GET["type"] == "getSalaryHeadMEHA") {
        try{
        $output = array();
        $output1 = array();
        $plant = $_GET["plant_id"];
        $id=0;
        $sql = "Select * from salary_head where id = '".$_GET["id"]."' 
        and plant_id = '".$_GET["plant_id"]."' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
               $id  = $row['id'];
            }
            $sql = "select * from salary_head_details where salary_hdr_id= '".$id."'  
            AND salary_head_group='Earnings'";
            $result = $conn->query($sql);
            while ($row = $result->fetch_assoc()) {
               $output[]  = $row;
            }
            $output1["Earnings"] = $output;
             
            $output = array();
            $sql = "select * from salary_head_details where salary_hdr_id= '".$id."'  AND salary_head_group='Deductions'";
            $result = $conn->query($sql);
            while ($row = $result->fetch_assoc()) {
               $output[]  = $row;
            }
             $output1["Deductions"] = $output;
             
            $output = array();
            $sql = "select * from salary_head_details where salary_hdr_id= '".$id."'  AND salary_head_group='CTC Calculation'";
            $result = $conn->query($sql);
            while ($row = $result->fetch_assoc()) {
               $output[]  = $row;
            }
             $output1["CTC"] = $output; 
        }
        echo json_encode($output1);
        }  catch (\Throwable $e) {
               echo "{\"statuse\":\"".$e."\"}";
            }
    } 
    else if ($_GET["type"] == "save_salary_type") {
        
        $sql = "Select * from salary_types where payroll_type = '".$input["typeField"]."' and plant_id =   '".$_GET["plant_id"]."'";
        
         $result = $conn->query($sql);
        if ($result->num_rows > 0) {
          	echo "{\"status\":\"Salary Type Already Exists. Duplicate Values are not allowed\"}";
        }else{
            $sql = "INSERT INTO salary_types (plant_id, entry_by,payroll_type) VALUES (
                '".$_GET["plant_id"]."', '".$_GET["emp_id"]."','".$input["typeField"]."')";
            if ($conn->query($sql)) {
                 echo "{\"status\":\"success\"}";
            } else {
                echo "{\"status\":\"".$conn->error."\"}";
            }
        }
    }
    //else if ($_GET["type"] == "get_salary_types") {
       
          //  $output = array();
           // $plant = $_GET["plant_id"];
          //  $id=0;
          //  $sql = "Select * from salary_types where plant_id = '".$_GET["plant_id"]."' order by payroll_type asc ";
          //  $result = $conn->query($sql);
          //  if ($result->num_rows > 0) {
           //     while ($row = $result->fetch_assoc()) {
            //       $output[]  = $row;
            //    }
                
         //   }
        //echo json_encode($output);
       
   // }
   else if ($_GET["type"] == "getSalaryTypes") {
        $output = Array();
        $sql = "SELECT * FROM salary_types order by 2 ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
               
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    // else if ($_GET["type"] == "get_salary_heads") {
    //     $output = Array();
    //     $sql = "SELECT * FROM salary_head where plant_id =   '".$_GET["plant_id"]."' order by emp_type ";
    //     $result = $conn->query($sql);
    //     if ($result->num_rows > 0) {
    //         while ($row = $result->fetch_assoc()) {
               
    //             $output[] = $row;
    //         }
    //     }
    //     echo json_encode($output);
    // }
    else if ($_GET["type"] == "get_salary_heads") {
        $output = Array();
         $sql = "SELECT * FROM salary_head where plant_id =   '".$_GET["plant_id"]."' order by emp_type ";
        $result = $conn->query($sql);
        //$result = $conn->query($sql);
            if($result->num_rows > 0){ 
                while($row = $result->fetch_assoc()){
                     $output1 = Array();
                        $sql1 = "SELECT * FROM salary_head_details WHERE salary_hdr_id='".$row["id"]."' and salary_head_group='Earnings' ";
                        
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                $output1[] = $row1;
                            }
                        }
                        $output2 = Array();
                        $sql1 = "SELECT * FROM salary_head_details WHERE salary_hdr_id='".$row["id"]."' and salary_head_group='CTC Calculation' ";
                           $result2 = $conn->query($sql1);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $output2[] = $row2;
                            }
                        }
                        $output3 = Array();
                        $sql1 = "SELECT * FROM salary_head_details WHERE salary_hdr_id='".$row["id"]."' and salary_head_group='Deductions' ";
                           $result3 = $conn->query($sql1);
                        if ($result3->num_rows > 0) {
                            while ($row3 = $result3->fetch_assoc()) {
                                $output3[] = $row3;
                            }
                        }
                        $row["Deductions"] = $output3;
                        $row["CTC Calculation"] = $output2;
                        $row["Earnings"] = $output1;
                        $output[] = $row;
                }
            }
            //  $data["material_types"] = $output;
        echo json_encode($output);
    }
    else if ($_GET["type"] == "downloadSalaryHead") {
        $_GET['filename'] = 'Salary Head'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";
        $html.='
        <h2 style="text-align:center"></h2>
        <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:5%;">Sr No</td>
                    <td style="width:15%;">Material Type</td>
                    <td style="width:12%;">Material Code</td>
                    <td style="width:15%;">Material Name</td>
                    <td style="width:8%;">Unit</td>
                    <td style="width:10%;">Lead Time</td>
                    <td style="width:15%;">Purchase Exceed Limit</td>
                    <td style="width:10%;">Gst</td>
                    <td style="width:10%;">Hsn</td>
                </tr>
            </thead>';
            $sql = "SELECT * FROM service";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                $i=1;
                while ($row = $result->fetch_assoc()) {
                $html.='<tr nobr="true">
                            <td style="width:5%;">'.$i.'.</td>
                            <td style="width:15%;">'.$row['material_type'].'</td>
                            <td style="width:12%;">'.$row['material_code'].'</td>
                            <td style="width:15%;">'.$row['material_name'].'</td>
                            <td style="width:8%;">'.$row['unit'].'</td>
                            <td style="width:10%;">'.$row['lead_time'].'</td>
                            <td style="width:15%;">'.$row['exceed_limit'].'</td>
                            <td style="width:10%;">'.$row['gst'].'</td>
                            <td style="width:10%;">'.$row['hsn'].'</td>
                        </tr>';
                    $i++;
                }
            }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Salary Head.pdf', 'I');
    }

}
}  catch (\Throwable $e) {
       echo "{\"statuse\":\"".$e."\"}";
}
$conn->close();
?>