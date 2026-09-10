<?php
    require '../db.php';
    require '../token.php';
     require '../tcpdf/tcpdf.php';
    require '../phpmailer/class.phpmailer.php';
    require '../PHPExcel/Classes/PHPExcel.php';
    try{
    header('Access-Control-Allow-Origin: *');
    date_default_timezone_set("Asia/Kolkata");
    $token = $_GET["token"];
    $timestamp = time();
    $entry_date = date("Y-m-d h:i:s", $timestamp);
    $input = json_decode(file_get_contents('php://input'),true);

    $sql = "SELECT * FROM token WHERE token='".$_GET["token"]."'";
    $result = $conn->query($sql);
    $_GET["plant_id"] = "";
    $_GET["emp_id"] = "";
    $_GET["department"] = "";
    if($result->num_rows > 0){
    while($row = $result->fetch_assoc()){
	    $string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
	    $string = explode("$",$string);
	    $_GET["plant_id"] = $string[0];
	    $_GET["emp_id"] = $string[0];
	    $_GET["department"] = $string[1];
	    break;
    }

    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    
    
    function checkHoliday($date){
          if(date('l', strtotime($date)) == 'Saturday'){
            return "Saturday";
          }else if(date('l', strtotime($date)) == 'Sunday'){
            return "Sunday";
          }else{
            $receivedDate = date('d M', strtotime($date));
        
            $holiday = array(
              '01 Jan' => 'New Year Day',
              '18 Jan' => 'Martin Luther King Day',
              '22 Feb' => 'Washington\'s Birthday',
              '05 Jul' => 'Independence Day',
              '11 Nov' => 'Veterans Day',
              '24 Dec' => 'Christmas Eve',
              '25 Dec' => 'Christmas Day',
              '31 Dec' => 'New Year Eve'
            );
        
            foreach($holiday as $key => $value){
              if($receivedDate == $key){
                return $value;
              }
            }
          }
    }
    
    
    
    if ($_GET["type"] == "getAttendance") {
        $output = Array();
        $sql = "SELECT * FROM employee WHERE status='active'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = Array();
                $entry_date = date("Y-m-d", $timestamp);
                $curdate=strtotime(date("d", $timestamp)."-".date("m", $timestamp)."-".date("Y", $timestamp));
                $days = cal_days_in_month(CAL_GREGORIAN,date("m", $timestamp),date("Y", $timestamp));
                for ($i = 1; $i <= $days; $i++) {
                    $temp = Array();
                    $temp["date"] = date("Y", $timestamp)."-".date("m", $timestamp)."-".$i;
                    $temp["holiday"] = checkHoliday($i + "/" + date("m", $timestamp) + "/"+ date("Y", $timestamp));
                    
                    $today = date("Y", $timestamp)."-".date("m", $timestamp)."-".$i;
                    
                    $mydate=strtotime($i."-".date("m", $timestamp)."-".date("Y", $timestamp));
                    
                    
                    if($curdate > $mydate) {
                        $sql1 = "SELECT * FROM attendence WHERE DATE(indate)='$today' AND emp_id='".$row["emp_id"]."'";
                        echo $sql1;
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                $temp["indate"] = $row1["indate"];
                                $temp["outdate"] = $row1["outdate"];
                                $temp["attendance"] = "P";
                            }
                        } else {
                            $temp["attendance"] = "A";
                        }
                    } else {
                        $temp["attendance"] = "";
                    }
                    
                    $output1[] = $temp;
                }
                $row["attendance"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getIndividualAttendace") {
        $output = Array();
        $earlier = new DateTime($_GET["from_date"]);
        $later = new DateTime($_GET["to_date"]);
        $diff = $later->diff($earlier)->format("%a");
        $fromdate = $earlier->format('Y-m-d');

        for ($i = 1; $i <= $diff; $i++) {
            $temp = Array();
            
            $sql1 = "SELECT * FROM attendence WHERE indate='$fromdate' AND emp_id='".$_GET["emp_code"]."'";
        
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $temp["id"] = $row1["id"];
                    $temp["intime"] = $row1["intime"];
                    $temp["outtime"] = $row1["outtime"];
                    $temp["date"] = $fromdate;
                    $temp["status"] = "P";
                }
            } else {
                $temp["id"] = $row1["id"];
                $temp["intime"] = "";
                $temp["outtime"] = "";
                $temp["date"] = $fromdate;
                $temp["status"] = "A";
            }
            $output[] = $temp;
            $fromdate = date('Y-m-d', strtotime($fromdate. ' + 1 days'));
        }
        echo json_encode($output);
    } 
     else if ($_GET["type"] == "saveIndividualAttendance"){
    
       if($input["id"]>0)     {
       $sql = "delete from attendence WHERE id='".$input["id"]."'";
       }else{
        $sql = "INSERT INTO attendence (emp_id,indate,outdate,intime,outtime,inentry_by,outentry_by) VALUES 
        ('".$input["emp_code"]."','".$input["date"]."','".$input["date"]."','".$input["in_time"]."',
        '".$input["out_time"]."','".$_GET["inentry_by"]."','".$_GET["outentry_by"]."')";
       }

       
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    /*********************Date 06 Dec 2022************************/
    else if ($_GET["type"] == "saveMultipleAttendance"){
        $input = $_POST;
        $returnArr = array();
            if (isset($_FILES["attendance_file"])) {
                $rand_no = date("YmdHis", $timestamp);
                $file = "../upload/attendance_excel/".$rand_no.basename($_FILES["attendance_file"]["name"]);
                move_uploaded_file($_FILES["attendance_file"]["tmp_name"], $file);
                $file = $rand_no.basename($_FILES["attendance_file"]["name"]);
                $excelFile = "/upload/attendance_excel/".$file;
    
                $inputFileName = "../upload/attendance_excel/".$file;
        
        		//  Read your Excel workbook
        		try {
        		    $inputFileType = PHPExcel_IOFactory::identify($inputFileName);
        		    $objReader = PHPExcel_IOFactory::createReader($inputFileType);
        		    $objPHPExcel = $objReader->load($inputFileName);
        		} catch(Exception $e) {
        		    die('Error loading file "'.pathinfo($inputFileName,PATHINFO_BASENAME).'": '.$e->getMessage());
        		}
        		$sheet = $objPHPExcel->getSheet(0); 
        		$highestRow = $sheet->getHighestRow(); 
        		$highestColumn = $sheet->getHighestColumn();
        		$Exceldata =  $objPHPExcel->getActiveSheet()->toArray();
        		
        		array_shift($Exceldata);
                $Exceldata = array_values($Exceldata);
                // print_r($Exceldata);exit;
                
        		foreach($Exceldata as $rowdata){
            	    $indate = $rowdata[1];
                    $newIndate = date("Y-m-d", strtotime($indate));
                    $outdate = $rowdata[2];
                    $newOutdate = date("Y-m-d", strtotime($outdate));
        		   $sql = "INSERT INTO attendence (emp_id,indate,outdate,intime,outtime,inentry_by,outentry_by) VALUES 
                ('".$rowdata[0]."','".$newIndate."','".$outdate."','".$rowdata[3]."',
                '".$rowdata[4]."','".$rowdata[5]."','".$rowdata[6]."')"; 
                    if ($conn->query($sql) === TRUE) {
                      $last_id = $conn->insert_id;
                      $sql2 = "SELECT * FROM attendence WHERE id =".$last_id;
                      $result = $conn->query($sql2);
                      if ($result->num_rows > 0){
                          array_push($returnArr,$result->fetch_assoc());
                      }
                    }
        		}
                
        
                if (!empty($returnArr)) {
                    $res['status'] = "success";
                    $res['data'] = $returnArr;
                   echo json_encode($res); exit;
                } else {
                    echo "{\"status\":\"".$conn->error."\"}";
                }
            } else {
                echo "{\"status\":\"error:file not found\"}";
            }
        
    }
    /*********************************************************/
    else if ($_GET["type"] == "getMonthlyAttendance") {
        $output = Array();
        $sql = "SELECT * FROM employee WHERE status='active' AND department LIKE '%".$_GET["department_name"]."%'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $temp = Array();
                $temp["emp_id"] = $row["emp_id"];
                $mon = explode("-", $_GET["month"]);
                $curdate=strtotime(date("d", $timestamp)."-".$mon[1]."-".$mon[0]);
                $days = cal_days_in_month(CAL_GREGORIAN,$mon[1],$mon[0]);
                for ($i = 1; $i <= $days; $i++) {
                  $temp["date"] = date("Y", $timestamp)."-".date("m", $timestamp)."-".$i;
                   
                    $temp["holiday"] = checkHoliday($i + "/" + date("m", $timestamp) + "/"+ date("Y", $timestamp));
                    
                    $today = $mon[0]."-".$mon[1]."-".$i;
                    
                    $mydate=strtotime($i."-".$mon[1]."-".$mon[0]);
                   // echo $today.'-'.$mydate.' '.$curdate;
                    if($curdate >= $mydate) {
                        $sql1 = "SELECT * , DATEDIFF(outtime,intime) as working_hrs FROM attendence WHERE indate='$today' AND emp_id='".$row["emp_id"]."'";
                      
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                // $temp["indate"] = $row1["intime"];
                               //  $temp["outdate"] = $row1["outtime"];
                                // $temp["working_hrs"] = $row1["working_hrs"];
                                $temp[$today] = "P";
                            }
                        } else {
                            $temp[$today] = "Ab";
                        }
                    } else {
                        $temp[$today] = "";
                    }
                }
                $output[] = $temp;
            }
        }
        echo json_encode($output);
    }else if ($_GET["type"] == "getAllMonthlyAttendance") {
        $output = Array();
        $sql = "SELECT * FROM employee WHERE status='active'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $temp = Array();
                $temp["emp_id"] = $row["emp_id"];
                $mon = explode("-", $_GET["month"]);
                $curdate=strtotime(date("d", $timestamp)."-".$mon[1]."-".$mon[0]);
                $days = cal_days_in_month(CAL_GREGORIAN,$mon[1],$mon[0]);
                for ($i = 1; $i <= $days; $i++) {
                    // $temp["date"] = date("Y", $timestamp)."-".date("m", $timestamp)."-".$i;
                    // $temp["holiday"] = checkHoliday($i + "/" + date("m", $timestamp) + "/"+ date("Y", $timestamp));
                    
                    $today = $mon[0]."-".$mon[1]."-".$i;
                    
                    $mydate=strtotime($i."-".$mon[1]."-".$mon[0]);
                    
                    if($curdate > $mydate) {
                        $sql1 = "SELECT * FROM attendence WHERE DATE(indate)='$today' AND emp_id='".$row["emp_id"]."'";
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                // $temp["indate"] = $row1["indate"];
                                // $temp["outdate"] = $row1["outdate"];
                                $temp[$today] = "P";
                            }
                        } else {
                            $temp[$today] = "Ab";
                        }
                    } else {
                        $temp[$today] = "";
                    }
                }
                $output[] = $temp;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getMonthlyChart") {
        $output = Array();
        $sql = "SELECT department_name FROM department";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT IFNULL(count(id), 0) as id FROM employee WHERE status='active' AND department_name='".$row["department_name"]."'";
                $result1 = $conn->query($sql1);
                while ($row1 = $result1->fetch_assoc()) {
                    $row["employees"] = $row1["id"];
                    break;
                }
                $output[] = $row;
            }
        }
    } else if ($_GET["type"] == "getDepartmentEmployees") {
        $output = Array();
        $sql = "SELECT emp_id, firstname,lastname FROM employee WHERE status='active' AND department='".$_GET["department_name"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }else if($_GET['type'] == 'downloadAttendance'){
        
        $date = strtotime($_GET['month']);
        $month=date("m",$date);
        $year=date("Y",$date);
        
        $objPHPExcel = new PHPExcel();
        $style1 = array(
            'alignment' => array( 'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER, )
        );
    	$style = array(
            'alignment' => array( 'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER, ),
            'fill' => array( 'type' => PHPExcel_Style_Fill::FILL_SOLID, 'color' => array('rgb' => '	ffbf00') )
        );
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', 'GMP Software Pvt Ltd' );
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A2', 'Attendance Report - '.$month.'-'.$year );
        
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A3', 'Employee Id');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('B3', 'Employee Name');
        $mon = explode("-", $_GET["month"]);
        $curdate=strtotime(date("d", $timestamp)."-".$mon[1]."-".$mon[0]);
        $days = cal_days_in_month(CAL_GREGORIAN,$mon[1],$mon[0]);
        $b = 'C'; 
        $counter = 1;
        for ($i =1; $i <= $days; $i++) {
            $datenum = $counter++;
            $today = date("$year-$month-$datenum", strtotime($_GET['month']));
            $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue($b++. + 3, $today);
            $objPHPExcel->setActiveSheetIndex(0)->mergeCells('A1:'.$b. + 1);
            $objPHPExcel->setActiveSheetIndex(0)->getStyle('A1:'.$b. + 1)->applyFromArray($style1);
            $objPHPExcel->setActiveSheetIndex(0)->mergeCells('A2:'.$b. + 2);
            $objPHPExcel->setActiveSheetIndex(0)->getStyle('A3:'.$b. + 3)->getFont()->setBold( true );
            $objPHPExcel->setActiveSheetIndex(0)->getStyle('A2:'.$b. + 2)->applyFromArray($style);
        }
        $ii = 4;
        $sql = "SELECT * FROM employee WHERE status='active' AND department LIKE '%".$_GET["department_name"]."%'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValue('A'.$ii, $row["emp_id"]);
                $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValue('B'.$ii, $row["emp_name"]);
                $b1 = 'C';
                
                $temp = Array();
                $temp["emp_id"] = $row["emp_id"];
                for ($i = 1; $i <= $days; $i++) {
                    $today = $mon[0]."-".$mon[1]."-".$i;
                    $mydate=strtotime($i."-".$mon[1]."-".$mon[0]);
                    if($curdate > $mydate) {
                        $sql1 = "SELECT * FROM attendence WHERE DATE(indate)='$today' AND emp_id='".$row["emp_id"]."'";
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                $temp[$today] = "P";
                            }
                        } else {
                            $temp[$today] = "A";
                        }
                    } else {
                        $temp[$today] = "";
                    }
                    $objPHPExcel->setActiveSheetIndex(0)
                    ->setCellValue($b1++. + $ii, $temp[$today]);
                }
                $ii++;
            }
        }
                
        $objPHPExcel->getActiveSheet()->setTitle('Simple');
        $objPHPExcel->setActiveSheetIndex(0);
        
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save(__DIR__."/employeesalaryreport.xls");
        
        $file = 'employeesalaryreport.xls';
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.basename($file).'"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        unlink('employeereport.xls');
    }
    else if ($_GET["type"] == "downloadIndividualAttendace") {
        $_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";
        
        $html.='<h3 style="text-align:center;">Individual Attendance</h3>
        <table style="background-color:#DCDCDC;"cellpadding="5" border="1">
            <tr>
                <td style="width:20%; text-align:centre;"><b>Sr.</b></td>
                <td style="width:20%; text-align:centre;"><b>Date</b></td>
                <td style="width:20%; text-align:centre;"><b>In Date</b></td>
                <td style="width:20%; text-align:centre;"><b>Out Date</b></td>
                <td style="width:20%; text-align:centre;"><b>Status</b></td>
           
            </tr>';
        $i=1;
        $output = Array();
        $earlier = new DateTime($_GET["from_date"]);
        $later = new DateTime($_GET["to_date"]);
        $diff = $later->diff($earlier)->format("%a");
        $fromdate = $earlier->format('Y-m-d');

        for ($i=1; $i <= $diff; $i++) {
            $temp = Array();
            
            $sql1 = "SELECT * FROM attendence WHERE DATE(intime)='$fromdate' AND emp_id='".$_GET["emp_id"]."'";
           
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                     $temp["indate"] = $row1["indate"];
                    $temp["outdate"] = $row1["outdate"];
                    $temp["date"] = $fromdate;
                    $temp["status"] = "P";
                    $html.='<tr>
                        <td style="width:20%;">'.$i.'</td>
                        <td style="width:20%;">'.$temp["date"].'</td>
                        <td style="width:20%;">'.date('d-m-Y',strtotime($temp["indate"])).'</td>
                        <td style="width:20%;">'.date('d-m-Y',strtotime($temp["outdate"])).'</td>
                        <td style="width:20%;">'.$temp["status"].'</td>
                    </tr>';
                    $i++;
                }
            } else {
                $temp["indate"] =  $row1["indate"];
                 $temp["outdate"] =  $row1["outdate"];
                 $temp["date"] = $fromdate;
                $temp["status"] = "A";
               $html.='<tr>
                    <td style="width:20%;">'.$i.'</td>
                    <td style="width:20%;">'.$temp["date"].'</td>
                    <td style="width:20%;">'.date('d-m-Y',strtotime($temp["indate"])).'</td>
                    <td style="width:20%;">'.date('d-m-Y',strtotime($temp["outdate"])).'</td>
                    <td style="width:20%;">'.$temp["status"].'</td>
               </tr>';
                $i++;
            } 
            
            // $output[] = $temp;
            // $fromdate = date('Y-m-d', strtotime($fromdate. ' + 1 days'));
        
        }
        $html.="</table>";
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Attaendence.pdf', 'I');
    }


}
  } catch (\Throwable $e) {
               echo "{\"statuse\":\"".$e."\"}";
             
            }

$conn->close();
?>