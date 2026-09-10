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
    
    if ($_GET["type"] == "getDepartments") {
        $output = Array();
        $sql = "SELECT * FROM department ORDER by department_name ASC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getDesignations") {
        $output = Array();
        $sql = "SELECT * FROM designation";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getQualifications") {
        $output = Array();
        $sql = "SELECT * FROM qualification WHERE status='active'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
     else if($_GET["type"]=="saveIncrement") {
        $input = $_POST;
    	$target_dir = "../upload/employee/";
    // 	$emp_id = "";
    	$_POST["user"]= "false";
    	$_POST["checker"]= "false";
    	$_POST["approver"]= "false";
    
    	$password = encrypt('encrypt', $input["password"]);
    
    	if($_POST["telephone"]=="true"){
    		$_POST["telephone"]=true;
    	}
    	if($_POST["transport"]=="true"){
    		$_POST["transport"]=true;
    	}
    	if($_POST["cantine"]=="true"){
    		$_POST["cantine"]=true;
    	}
    
     	$emp_id;
     	$sql = "SELECT MAX(id)+1 as emp_id FROM employee";
     	$result = $conn->query($sql);
     	if ($result->num_rows > 0) {
     		while ($row = $result->fetch_assoc()) {
     			$emp_id = $row["emp_id"];
     		}
     	}
    // 	$emp_id1++;
    // 	$test_id = "";
    // 	if (strlen((string)$emp_id1) >= 100) {
    // 		$test_id = $emp_id1;
    // 	} else if (strlen((string)$emp_id1) == 2) {
    // 		$test_id = "0".$emp_id1;
    // 	} else if (strlen((string)$emp_id1) == 1) {
    // 		$test_id = "00".$emp_id1;
    // 	}
    // 	$emp_id = "EMP".$test_id;
        echo json_encode($_FILES);
    
    	if(isset($_FILES["photo"]["name"])) {
        	$target_file = $target_dir.$emp_id."_".basename($_FILES["photo"]["name"]);
        	$user_photo = $emp_id."_".basename($_FILES["photo"]["name"]);
        	$file3 = basename($_FILES["photo"]["name"]);
        	move_uploaded_file($_FILES["photo"]["tmp_name"], $target_file);
    	}

    	if(isset($_FILES["resume"]["name"])) {
        	$target_file = $target_dir.$emp_id."_".basename($_FILES["resume"]["name"]);
        	$user_resume= $emp_id."_".basename($_FILES["resume"]["name"]);
        	$file3 = basename($_FILES["resume"]["name"]);
        	move_uploaded_file($_FILES["resume"]["tmp_name"], $target_file);
    	}
    	
    	$sal_details = json_decode(json_encode($input["amtList"]));
    	echo $sal_details;
    	$document_list = json_decode(json_encode($input["document_list"]));
    	echo $document_list;
    	
    	foreach($sal_details as $item) { //foreach element in $arr
            $file = $item['var1']; //etc
            $target_file = $target_dir.$emp_id."_".basename($_FILES["resume"]["name"]);
        	$user_resume= $emp_id."_".basename($_FILES["resume"]["name"]);
        	$file3 = basename($_FILES["resume"]["name"]);
        	move_uploaded_file($_FILES["resume"]["tmp_name"], $target_file);
        }
    	$target_file = substr($target_file, 2);
    	
    	
    	/*$sql = "INSERT INTO employee (plant_id,employee_type,joining_status,trainee_period,probation_period,firstname,
    	middlename,lastname,emp_no,contact_no,emp_email,qualification,department,section,designation,password,
    	gender,joining_date,emergency_no,experience,birthdate,nationality,category,account_no,marital_status,
    	blood_group,reg_no,anni_date,registration_date,renewal_date,induction,result,photo,resume,permanent_flat,
    	permanent_country,permanent_state,permanent_city,permanent_pincode, permanent_telephone,permanent_mobile_no,permanent_contact, tempflat_no,temp_country,temp_state,
    	temp_city,temp_pincode, temp_telephone,temp_contact,familyList,qualification1,specialization,institute_name,institute_add,board,obtained_mark,
    	year_from,year_to,qualification2,subject,clg_name,institute_add1,location,from_year,to_year,time,Languages,
    	company_name,position,duties,address,year_from1,year_to1,gross_sal,last_salary,reason,training,
    	activity,general,name,designation1,department1,relation,min_salary,pf_no,esi_no,ref_name,
    	emp_present,ref_add,tel_no,document,description,doc,emp_name,department2,basic,qualiList,
    	document_list,per_annum,leave1,bonus,salary_details
    	,acc_type,esic_no,academics,lunch_duration,
    	acc_name,bank_name,branch_name,ifsc_neft,salary_list,resignation_date,employeement_list) 
    	VALUES('".$input["plant_id"]."','".$input["employee_type"]."','".$input["joining_status"]."','".$input["trainee_period"]."',
    	        '".$input["probation_period"]."','".$input["firstname"]."','".$input["middlename"]."','".$input["lastname"]."',
    	        '".$input["emp_no"]."','".$input["contact_no"]."','".$input["emp_email"]."','".$input["qualification"]."',
    	        '".$input["department"]."','".$input["section"]."','".$input["designation"]."','".$input["password"]."',
    	        '".$input["gender"]."','".$input["joining_date"]."','".$input["emergency_no"]."','".$input["experience"]."',
    	        '".$input["birthdate"]."','".$input["nationality"]."','".$input["category"]."',
    	        '".$input["account_no"]."','".$input["marital_status"]."','".$input["blood_group"]."','".$input["reg_no"]."',
    	        '".$input["anni_date"]."','".$input["registration_date"]."','".$input["renewal_date"]."','".$input["induction"]."',
    	        '".$input["result"]."','".$user_photo."','".$user_resume."','".$input["permanent_flat"]."',
    	        '".$input["permanent_country"]."','".$input["permanent_state"]."','".$input["permanent_city"]."',
    	        '".$input["permanent_pincode"]."','".$input["permanent_telephone"]."','".$input["permanent_mobile_no"]."',
    	        '".$input["permanent_contact"]."','".$input["tempflat_no"]."','".$input["temp_country"]."','".$input["temp_state"]."',
    	        '".$input["temp_city"]."','".$input["temp_pincode"]."','".$input["temp_telephone"]."','".$input["temp_contact"]."',
    	        '".$input["familyList"]."','".$input["qualification1"]."','".$input["specialization"]."',
    	        '".$input["institute_name"]."','".$input["institute_add"]."','".$input["board"]."','".$input["obtained_mark"]."',
    	        '".$input["year_from"]."','".$input["year_to"]."','".$input["qualification2"]."','".$input["subject"]."','".$input["clg_name"]."',
    	        '".$input["institute_add1"]."','".$input["location"]."','".$input["from_year"]."','".$input["to_year"]."','".$input["Languages"]."',
    	        '".$input["qualiList"]."','".$input["company_name"]."','".$input["position"]."','".$input["duties"]."','".$input["address"]."',
    	        '".$input["year_from1"]."','".$input["year_to1"]."','".$input["gross_sal"]."','".$input["last_salary"]."','".$input["reason"]."','".$input["training"]."',
    	        '".$input["activity"]."','".$input["general"]."','".$input["name"]."','".$input["designation1"]."','".$input["department1"]."','".$input["relation"]."',
    	        '".$input["min_salary"]."','".$input["pf_no"]."','".$input["esi_no"]."','".$input["ref_name"]."','".$input["emp_present"]."','".$input["ref_add"]."',
    	        '".$input["tel_no"]."','".$input["document"]."','".$input["description"]."','".$input["doc"]."','".$input["emp_name"]."',
    	        '".$input["department2"]."','".$input["esic_no"]."','".$input["academics"]."','".$input["document_list"]."',
    	        '".$input["salary_detail"]."','".$input["leave1"]."','".$input["bank_name"]."','".$input["ifsc_neft"]."','".$input["acc_type"]."'
    	        '".$input["basic"]."','".$input["time"]."','".$input["lunch_duration"]."','".$input["per_annum"]."','".$input["acc_name"]."',
    	        '".$input["bank_name"]."','".$input["branch_name"]."','".$input["ifsc_neft"]."','".$input["amtList"]."','".$input["resignation_date"]."','".$input["employeement"]."')";
    	        
    	        
        //$sql = "INSERT INTO employee (employee_type,firstname,middlename,lastname,contact_no,email,department,qualification,birthdate,gender,address_permanent,permanant_state,permanant_district,permanant_taluka,address_temporary,temporary_district, temporary_state,temporary_taluka,emergency_no,joining_date,section,resume,photo,isinduction,designation) VALUES
    	//('".$input["category"]."','".$input["firstname"]."','".$input["middlename"]."','".$input["lastname"]."','".$input["contact_no"]."','".$input["email"]."', '".$input["department"]."', '".$input["qualification"]."', '".$input["birthdate"]."', '".$input["gender"]."', '".$input["address_permanent"]."', '".$input["permanant_state"]."', '".$input["permanant_district"]."', '".$input["permanant_taluka"]."', '".$input["address_temporary"]."','".$input["temporary_district"]."', '".$input["temporary_state"]."', '".$input["temporary_taluka"]."', '".$input["emergency_no"]."','".$input["joining_date"]."','".$input["section"]."','".$input["resume"]."','".$input["photo"]."','".$input["isiduction"]."','".$input["designation"]."')";
    //	echo $sql;
    	/*if($conn->query($sql)){
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}*/
    }
    else if ($_GET["type"] == "getPendingEmployees") {
        $output = Array();
        $sql = "SELECT * FROM employee WHERE status='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["details"] = json_decode($row["details"]);
                $row["familyList"] = json_decode($row["familyList"]);
                $row["amtList"] = json_decode($row["amtList"]);
                $row["qualiList"] = json_decode($row["qualiList"]);
                $row["result"] = json_decode($row["result"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "updateEmployee") {
        $sql = "UPDATE employee SET status='".$_GET["status"]."', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getCurrentEmployees") {
        $output = Array();
        $sql = "SELECT * FROM employee WHERE status='active'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["details"] = json_decode($row["details"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getResignedEmployees") {
        $output = Array();
        $sql = "SELECT * FROM employee WHERE status='resign'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["details"] = json_decode($row["details"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "delEmployee") {
        $sql = "SELECT * FROM employee WHERE emp_id='".$_GET["emp_id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $passcode = $row["emp_password"];
                $password = dec_enc("decrypt",$passcode);
                if($password == $input["password"]) {
                    $sql = "UPDATE employee SET status='delete' WHERE emp_id='".$_GET["emp_code"]."'";
                    if ($conn->query($sql)) {
                        echo "{\"status\":\"success\"}";
                    } else {
                        echo "{\"status\":\"".$conn->error."\"}";
                    }
                } else {
                    echo "{\"status\":\"failed\"}";
                }
            }
        }
    } else if ($_GET["type"] == "getEmployees") {
        $output = Array();
        $sql = "SELECT * FROM employee";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["details"] = json_decode($row["details"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getEmployeesList") {
        $output = Array();
        $sql = "SELECT * FROM employee WHERE department LIKE '%".$_GET["department_name"]."%' AND designation LIKE '%".$_GET["designation"]."%' AND status LIKE '%".$_GET["status"]."%' order by 1 desc";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["details"] = json_decode($row["details"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }  else if ($_GET["type"] == "getAllEmployeesList") {
        $output = Array();
        $sql = "SELECT * FROM employee ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["details"] = json_decode($row["details"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getSalesEmployees") {
        $output = Array();
        $sql = "SELECT * FROM employee WHERE status='active'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "get_emp_by_id") {
        $output = null;
        $sql = "SELECT id,employee_type,emp_id, firstname,lastname,contact_no,emp_email,department,ai_data_object FROM employee WHERE emp_id='".$_GET["emp_code"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output = $row;
            }
        }
        echo json_encode($output);
    }
     else if ($_GET["type"] == "update_employee_ai_data") {
        $sql = "UPDATE employee SET ai_data_object='".$_GET["ai_data_object"]."', ai_data_object_updated_by='".$_GET["emp_id"]."', ai_data_object_updated_on= CURRENT_TIMESTAMP WHERE id='".$_GET["id"]."'";
       // echo $sql;
        if ($conn->query($sql)) {
            echo "{\"status\":\"AI Data Inserted Successfully\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    else if ($_GET["type"] == "getIndividualAttendace") {
        $output = Array();
        
        $earlier = new DateTime($_GET["from_date"]);
        $later = new DateTime($_GET["to_date"]);
        
        $diff = $later->diff($earlier)->format("%a");
        
        $fromdate = $earlier->format('Y-m-d');

        for ($i = 1; $i <= $diff; $i++) {
            $temp = Array();
            
            $sql1 = "SELECT * FROM attendence WHERE DATE(indate)='$fromdate' AND emp_id='".$_GET["emp_code"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $temp["indate"] = $row1["indate"];
                    $temp["outdate"] = $row1["outdate"];
                    $temp[$fromdate] = "P";
                }
            } else {
                $temp[$fromdate] = "A";
            }
            $output[] = $temp;
            $fromdate = date('Y-m-d', strtotime($fromdate. ' + 1 days'));
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getMonthlyPerformance") {
        $output = Array();
        $sql = "SELECT emp_id, department, designation, emp_name FROM employee WHERE status='active' AND department LIKE '%".$_GET["department_name"]."%' AND designation LIKE '%".$_GET["designation"]."%'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $second = 0;
                $holidays = 0;
                $curdate=strtotime(date("d", $timestamp)."-".date("m", $timestamp)."-".date("Y", $timestamp));
                $days = cal_days_in_month(CAL_GREGORIAN,date("m", $timestamp),date("Y", $timestamp));
                for ($i = 1; $i <= $days; $i++) {
                    
                    $today = date("Y", $timestamp)."-".date("m", $timestamp)."-".$i;
                    $holiday = isWeekend($today);
                    if ($holiday) {
                        $holidays++;
                    }
                    
                    $mydate=strtotime($i."-".date("m", $timestamp)."-".date("Y", $timestamp));
                    
                    if($curdate >= $mydate) {
                        $sql1 = "SELECT indate, outdate, TIMESTAMPDIFF(SECOND, indate,outdate) as second FROM attendence WHERE DATE(indate)='$today' AND emp_id='".$row["emp_id"]."'";
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                $second += +$row1["second"];
                            }
                        }
                    }
                }
                
                $work_days = $days - $holidays;
                $actual_hr = 28800 * $work_days;
                $diff = $actual_hr - $second;
                $per = $diff / $actual_hr *100;
                $row["performace"] = number_format(100 - $per, 2);
                
                if (+$row["performace"] >= 90) {
                    $row["remark"] = "Above Average";
                } else if (+$row["performace"] < 90 && +$row["performace"] >= 75) {
                    $row["remark"] = "Average";
                } else if (+$row["performace"] < 75 && +$row["performace"] >= 60) {
                    $row["remark"] = "Below Average";
                } else if (+$row["performace"] < 60) {
                    $row["remark"] = "Unsatisfactory";
                }
                $row["working_hours"] = gmdate("H:i:s", $second);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getDepartmentEmployees") {
        $output = Array();
        $sql = "SELECT * FROM employee WHERE status='active' AND department='".$_GET["department_name"]."' AND designation='".$_GET["designation"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if($_GET['type']=='generatedappointment'){
        $sql = "SELECT * FROM employee WHERE isAppointment = 'active' ORDER BY id DESC";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    		    $output[] = $row; 
    		}
    		echo json_encode($output);
    	} else {
    	   echo "[]";
    	}
    } else if ($_GET["type"] == "getRights") {
        $output = array();
        $sql = "SELECT employee_type, emp_id, firstname,middlename,lastname, department, designation FROM employee WHERE  status NOT IN ('resign', 'delete') AND department LIKE '%".$_GET["department_name"]."%' AND designation LIKE '%".$_GET["designation"]."%'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT isuser, ischecker, isapprover, additional FROM emp_rights WHERE emp_id='".$row["emp_id"]."' ORDER BY id DESC LIMIT 1";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["additional"] = json_decode($row1["additional"]);
                        $row["rights"] = "assigned";
                        $row["user"] = $row1["isuser"];
                        $row["checker"] = $row1["ischecker"];
                        $row["approver"] = $row1["isapprover"];
                    }
                } else {
                    $row["rights"] = "pending";
                    $row["user"] = "false";
                    $row["checker"] = "false";
                    $row["approver"] = "false";
                }
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "updateRights") {
        $user = "false";
        $checker = "false";
        $approver = "false";
        $auditor = "false";
        
        if ($input["user"] == true) {
            $user = "true";
        }
        if ($input["checker"] == true) {
            $checker = "true";
        }
        if ($input["approver"] == true) {
            $approver = "true";
        }
        $sql = "INSERT INTO emp_rights (user_no, emp_id, isuser, ischecker, isapprover, entry_by, entry_date, status, additional) VALUES ('".$_GET["user_no"]."', '".$_GET["id"]."', '".$user."', '".$checker."', '".$approver."', '".$_GET["emp_id"]."', '$entry_date','approve', '".json_encode($input["additional"])."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "changeRights") {
        $user = "false";
        $checker = "false";
        $approver = "false";
        $auditor = "false";
        
        if ($input["user"] == true) {
            $user = "true";
        }
        if ($input["checker"] == true) {
            $checker = "true";
        }
        if ($input["approver"] == true) {
            $approver = "true";
        }
        $sql = "INSERT INTO emp_rights (user_no, emp_id, isuser, ischecker, isapprover, entry_by, entry_date, status, additional) VALUES ('".$_GET["user_no"]."', '".$_GET["id"]."', '".$user."', '".$checker."', '".$approver."', '".$_GET["emp_id"]."', '$entry_date','approve', '".json_encode($input["additional"])."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getEmployeeDetails") {
        $sql = "SELECT * FROM employee WHERE user_no='".$_GET["user_no"]."' AND emp_id='".$_GET["emp_code"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo json_encode($row);
            }
        } else {
            echo "{}";
        }
    } else if($_GET['type']=='pendingappointment') {
        $output = array();
        $sql = "SELECT * FROM employee WHERE isAppointment = 'pending' ORDER BY id DESC";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    		    $output[] = $row; 
    		}
    	}
    	echo json_encode($output);
    } else if($_GET['type']=='generatenewappointment') {
        // require '../phpmailer/class.phpmailer.php';
        // require '../tcpdf/tcpdf.php';
        $id = $input['emp_id'];
        $sql = "INSERT INTO salary_annexure (emp_id,type,isMetro,isPF,isESIC,basic,hra,conveyance,specialallowance, gross, PF_EMP, c_PF, ESIC, c_ESIC, p_tax, medical, gratuity, bonus, contribution, contribution_annual, inhand, deduction, ctc, ctc_annual, entry_by, entry_date)VALUE('$id','".$input['type']."','".$input['isMetro']."','".$input['isPF']."','".$input['isESIC']."','".$input['basic']."','".$input['hra']."','".$input['conveyance']."','".$input['specialallowance']."','".$input['gross']."','".$input['PF_EMP']."','".$input['c_EMP']."','".$input['ESIC']."','".$input['c_ESIC']."','".$input['p_tax']."','".$input['medical']."','".$input['gratuity']."','".$input['bonus']."','".$input['contribution']."','".$input['contribution_annual']."','".$input['inhand']."','".$input['deduction']."','".$input['ctc']."','".$input['ctc_annual']."','".$_GET["emp_id"]."','$entry_date')"; 
        if($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
            $sql1 = "UPDATE employee SET isAppointment='active' WHERE emp_id='".$input["emp_id"]."'";
            $conn->query($sql1);
           
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
   
    }
    else if ($_GET["type"] == "appointment") {
        //$_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp.php");
        class MYPDF extends TCPDF {
            public function Header() {
                $table='
                <style>td { border:solid 1px BCBBBA;}</style>
                <table>
                    <tr>
                        <td style="width:20%;text-align:center;">An ISO 9001-2015 Company <br> CIN U24100 <br> MH2005 <br>PTC 156121</td>
                        <td style="width:80%;text-align:center;font-weight:bold;">
                            <span style="font-family:times;font-size:17px;">DNS FINE CHEMICALS & LABORATORIES(P) Ltd.</span><br>
                            <span style="font-family:times;font-size:14px;">|REAL ESTATE|SPECIALIY CHEMICALS & API\'S|SIPOREX SOLUTIONS|</span><br>
                            <span style="font-size:9px;">Corporate Off:-Ghanshyam Krupa,R.B.Kadam Marg,Bhatwadi,Ghatkopar(W),Mumbai 400084,india.<br>Tel-+91-22-2515/25133164 Fax-+91-22-2509-1719 Mob-9967606855/57<br><b>Email-sales@dnsfine.com Website-www.dnsfine.com/www.dnsgrp.com</b></span>
                        </td>
                    </tr>
                </table>
                ';
            $this->SetY('5'); 
            $this->writeHTML($table, true, false, false, false, '');  
            }
            public function Footer() {
                
            }
        }
        $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetHeaderData(10,5,10,10);
        $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setPrintFooter(false);
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $pdf->SetMargins(PDF_MARGIN_LEFT,35, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
            require_once(dirname(__FILE__).'/lang/eng.php');
            $pdf->setLanguageArray($l);
        }
        $pdf->AddPage();
        $pdf->SetY(35);
        $pdf->SetFont ('times', '', '12');
       /* $html= "";
        $id = $_GET['id'];
        $sql = "SELECT * FROM employee WHERE emp_id='$id'"; 
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $html.='
                <h3 style="text-align:center;">APPOINTMENT LETTER</h3>
                <table cellpadding="3" style="text-align:left; width:100%;">
                    <tr>
                        <td style="width:100%; text-align:right;"><b>Date:</b><label>'.$row['joining_date'].'</label></td>
                    </tr>
                    <tr>
                        <td colspan="3"><b>To,</b></td>
                    </tr>
                    <tr>
                        <td style="width:50%;"><b>'.$title.' '.$row['emp_name'].' <br>'.$row['address_temporary'].'</b></td>
                    </tr>
                </table>
                <h3>Dear '.$title.' '.$row['emp_name'].',</h3><br>
                 <table>
                    <tr>
                        <td style="width:100%; text-align:justify;">We are very pleased to inform you that you have selected to work as “<b> '.$row["department"].'-'.$row['designation'].'</b> ” with us. The terms & conditions are as follows.</td>
                    </tr>
                    <tr>
                        <ol>
                            <li>You will work as Quality Assurance Trainee looking after all the documentation of plant, auditing the process caried in plant on daily basis, ensuring the process been followed from start to end, report preparation daily as and when required.</li>
                            <li>You will provide solution to respective dept post conducting the required audits, whenever it is necessary; at the same time, you will provide process technology for assuring the process been adhered in the plant.</li>
                            <li>You will be working in accordance with policies, which are made by management.</li>
                            <li>You are posted at present at <b>DNS Fine Chemicals & Laboratories Pvt Ltd</b> situated at W-15, MIDC Badlapur(E), Thane-421503. </li>
                            <li>Initially, you will be based in the Badlapur Thane area. The job is transferable, and depending on the exigencies of business, we may transfer or relocate you elsewhere in the country. The job will involve touring in India if required abroad to prompt business of company.</li>
                            <li>You will provide complete technology for <b>';$html.=''.$row['department'].'';$html.='</b> of the all the products. You will generate & use it for company’s benefit.</li>
                            <li>This is composite job. While you will be in employment of this company, we shall be free to loan your services to or concurrently utilize them otherwise for any of the establishments of our associate, subsidiary or sister concerns.</li>
                            <li>You shall devote the whole of your time, energy and attention to our business, as directed by us and you shall not devote or apply yourself to any other work or activity either as a source of income or so as to interfere in any way the performance of your duties. In particular, you shall not in any way associate yourself with any political activity, local or otherwise.</li>
                            <li>In all your communication with the outside world, you will represent us only to the extent you have been specifically authorized. When expressing outside your personal views on any matter concerning/ affecting us, you will abundantly clarify that your views may not necessarily our thinking or views in that behalf. </li>
                            <li>With your confirmation, the employer-employee relationship shall be subject to termination by either side giving to the other written notice of not less than 30 days, provided that, at our option, any Privilege Leave then due shall be adjusted with notice period. Besides, we shall have the further option of paying you in lieu of notice.</li>
                            <li>In case any difference/ dispute arising out of or in connection with the terms and conditions of your service, leads to litigation. It shall be subject to jurisdiction of the appropriate Court exclusively in the district of Thane & State of Maharashtra.</li>
                            <li>The company will pay Rs.';
                            $sql1 = "SELECT * FROM salary_annexure WHERE emp_id='".$_GET["id"]."' Group By emp_id"; 
                            $result1 = $conn->query($sql1);
                            if ($result1->num_rows > 0) {
                                while ($row1 = $result1->fetch_assoc()) {
                                $html.='<b>'.$row1['ctc'].'/Month</b>';
                                }
                            }//Rs. 10,500/Month</b>
                            $html.='&nbsp;(all inclusive) as remuneration for your services to company. Your salary will be paid in every 7th to 10thday of month. Your joining date in this organization will be from &nbsp;<b>'.$row['joining_date'].'</b>&nbsp;We look forward to your joining in our company.</li>
                            <li>You will be strictly liable & entitled to keep all the company information confidential. If anyone found any sharing such information with any third party the company have all the rights to terminate his or her service & you will be liable to legal action at any point of time without any prior notice. </li>
                        </ol>
                    </tr>
                    <tr>
                        <td style="width:100%; text-align:justify;">We hope you will give us your fullest co-operation & we hope to have long lasting & fruit full relationship with you. </td>
                    </tr>
                    <tr>
                        <td style="width:100%; text-align:justify;">Please sign one copy of the letter as acknowledgment of appointment. </td>
                    </tr>
                    <tr>
                        <td style="width:100%; text-align:justify;">Thanking you,</td>
                    </tr>
                    <tr>
                        <td style="width:100%; text-align:justify;">Sudhir D. Sawant</td>
                    </tr>
                    <tr>
                        <td style="width:100%; text-align:justify;">Managing Director </td>
                    </tr>
                </table>';
            }
        }
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('appintment Letter.pdf', 'I');*/
   
    
    }else if ($_GET["type"] == "newincrementpromotion") {
        //$_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp.php");
         class MYPDF extends TCPDF {
            public function Header() {
                $table.='
                <style>td { border:solid 1px BCBBBA;}</style>
                <table>
                    <tr>
                        <td style="width:80%;text-align:center;font-weight:bold;">
                            <span style="font-family:times;font-size:17px;">Amardeep Pharmaceuticals Pvt. Ltd</span><br>
                            <span style="font-size:9px;">Location:Plot Number A 2/8, Phase-1, Near UPL, Daman Ganga Road, GIDC, <br>VAPI, Gujarat 396195, India.<br></span>
                        </td>
                        <td style="width:20%;">';
                            //$this->Image('@'.file_get_contents('../gmptotal/upload/User/DNS.jpg'),162,8,39);
                         // $this->Image('@'.file_get_contents('https://'.$_SERVER['SERVER_NAME'].'/assets/amardeep-logo.jpg'),160,10,30);
                             //$this->Image('@'.file_get_contents('http://'.$_SERVER['SERVER_NAME'].'/gmptotal/upload/User/dns.png'),169,8,25);
                            $table.='
                        </td>
                    </tr>
                </table>';
                $this->SetY('5'); 
                $this->writeHTML($table, true, false, false, false, '');  
            }
            public function Footer() {
                
            }
        }
        $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetHeaderData(10,5,10,10);
        $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setPrintFooter(false);
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $pdf->SetMargins(PDF_MARGIN_LEFT,35, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
            require_once(dirname(__FILE__).'/lang/eng.php');
            $pdf->setLanguageArray($l);
        }
        $pdf->AddPage();
        $pdf->SetY(35);
        $pdf->SetFont ('times', '', '12');
   
        $html.="";
        
           
            if($input['letter_type'] == 'Promotion Letter'){
                    $html='
                    <br><br><br>
                    <h3 style="text-align:center;">PROMOTION LETTER</h3>
                    <table cellpadding="5" style="text-align:left; width:100%;">
                        <tr>
                            <td style="width:70%;"><b><label>'.$letter_no.'</label></b></td>
                            <td style="width:30%; text-align:right;"><b>Date : </b><label>'.$newDate.'</label></td>
                        </tr>
                        <tr>
                            <td colspan="3"><b>To,</b></td>
                        </tr>
                        <tr>
                            <td style="width:50%;"><b>'.$title.' '.$row2['emp_name'].' <br>'.$address.'</b></td>
                        </tr>
                    </table>
                    <h3>Dear '.$title.' '.$name.' , </h3><br>
                    <table>
                        <tr>
                            <td style="width:5%;"></td>
                            <td style="width:95%;">Management of Cyclone Pharmaceuticals Pvt Ltd is happy to inform you that your
                                performance in Month May 2019 to '.$lastmonth.' appreciable .This appreciation Letter is being given for your hard working and Learning attitude.<br>
                                Keep it up and accept this letter along with a small token of Love for your performance from Cyclone Pharmaceuticals Pvt Ltd Management<br>
                                Management has decided to upgrade your post to give you more opportunity to show your abilities<br>
                                Current Designation: '.$input['current_designation'].'<br>
                                Promoted Designation: '.$promoted_designation.'<br>
                                Your roles and Responsibilities will be inform you by Management<br><br>
                                Best of Luck for your Bright Future<br>
                                Thanks and Regards
                            </td>
                        </tr>
                    </table>
                    <br><br><br>
                    <table cellpadding="5" style="text-align:left; width:100%;">
                        <tr>
                            <td style="width:40%; text-align:center;"><b>Yours Faithfully,</b></td>
                          </tr>
                          <tr>
                            <td style="width:40%; text-align:center;"><b>Cyclone Pharmaceuticals Pvt Ltd </b></td>
                            <td style="width:10%;"></td>
                            <td style="width:60%;"></td>
                          </tr>
                          <tr><td></td></tr>
                          <tr>
                            <td style="width:40%; text-align:center;"><b>Mr. Sachin Bhalekar</b></td>
                            <td style="width:10%;"></td>
                            <td style="width:60%; text-align:center;">(Signature of Employee)</td>
                        </tr>
                    </table>';
                }
                if($input['letter_type'] == 'Increment Letter'){
                    $html='
                    <br><br><br>
                    <h3 style="text-align:center;">INCREMENT LETTER</h3>
                    <table cellpadding="5" style="text-align:left; width:100%;">
                        <tr>
                            <td style="width:70%;"><b><label>'.$letter_no.'</label></b></td>
                            <td style="width:30%; text-align:right;"><b>Date : </b><label>'.$newDate.'</label></td>
                        </tr>
                        <tr>
                            <td colspan="3"><b>To,</b></td>
                        </tr>
                        <tr>
                            <td style="width:50%;"><b>'.$title.' '.$row2['emp_name'].' <br>'.$address.'</b></td>
                        </tr>
                    </table>
                    <h3>Dear '.$row2['emp_name'].' , </h3><br>
                    <table>
                        <tr>
                            <td style="width:5%;"></td>
                            <td style="width:95%;">Management of Cyclone Pharmaceuticals Pvt Ltd is happy to inform you that your
                                performance in Month May 2019 to '.$lastmonth.' appreciable .This appreciation Letter is being given for your hard working and Learning attitude.<br>
                                Keep it up and accept this letter along with a small token of Love for your performance from Cyclone Pharmaceuticals Pvt Ltd Management<br>
                                Also management has decided to revise your salary structure which will be effective from '.$monthname.' please find Annexure attached with this Letter.<br>
                                Best of Luck for your Bright Future<br>
                                Thanks and Regards
                            </td>
                        </tr>
                    </table>
                    <br><br><br>
                    <table cellpadding="5" style="text-align:left; width:100%;">
                        <tr>
                            <td style="width:40%; text-align:center;"><b>Yours Faithfully,</b></td>
                          </tr>
                          <tr>
                            <td style="width:40%; text-align:center;"><b>GMP Software Pvt Ltd</b></td>
                            <td style="width:10%;"></td>
                            <td style="width:60%;">I accept and agree to the above terms & conditions	</td>
                          </tr>
                          <tr><td></td></tr>
                          <tr>
                            <td style="width:40%; text-align:center;"><b>Mr. Sachin Bhalekar</b></td>
                            <td style="width:10%;"></td>
                            <td style="width:60%; text-align:center;">(Signature of an '.$input['type'].')</td>
                        </tr>
                    </table>
                    <br pagebreak="true"/>
                    <br><br></br>
                    <h4 style="text-align:center;">Salary Annexure : '.$row2['emp_name'].' </h4>
                    <br></br><br>
                    <table cellpadding="7" style="border: 1px solid #DCDCDC; text-align:left; ">
                        <tr style="background-color:#DCDCDC;">
                            <td  border="1" style="width:10%; text-align:center;"><b>Sr No</b></td>
                            <td  border="1" style="width:40%; text-align:center;"><b>Particulars</b></td>
                            <td border="1" style="width:25%; text-align:center;"><b>Salary Per Month</b></td>
                            <td border="1" style="width:25%; text-align:center;"><b>Annual Salary</b></td>
                        </tr>
                        <tr>
                            <td border="1" style="width:10%; text-align:center;">1</td>
                            <td border="1" style="width:40%;">Basic</td>
                            <td border="1" style="width:25%; text-align:right;">'.number_format((float) $updated_gross*0.40, 2, '.', '').'</td>
                            <td border="1" style="width:25%; text-align:right;">'.number_format((float) ($updated_gross*0.40)*12, 2, '.', '').'</td>
                        </tr>
                        <tr>
                            <td border="1" style="text-align:center;">2</td>
                            <td border="1">HRA</td>
                            <td border="1" style="text-align:right;">'.number_format((float) $updated_gross*0.20, 2, '.', '').'</td>
                            <td border="1" style="text-align:right;">'.number_format((float) ($updated_gross*0.20)*12, 2, '.', '').'</td>
                        </tr>
                        <tr>
                            <td border="1" style="text-align:center;">3</td>
                            <td border="1">Conveyance</td>
                            <td border="1" style="text-align:right;">'.number_format((float) $updated_gross*0.10, 2, '.', '').'</td>
                            <td border="1" style="text-align:right;">'.number_format((float) ($updated_gross*0.10)*12, 2, '.', '').'</td>
                        </tr>
                        <tr>
                            <td border="1"style="text-align:center;">5</td>
                            <td border="1">Medical allowance</td>
                            <td border="1" style="text-align:right;">'.number_format((float) $updated_gross*0.10, 2, '.', '').'</td>
                            <td border="1" style="text-align:right;">'.number_format((float) ($updated_gross*0.10)*12, 2, '.', '').'</td>
                        </tr>
                        <tr>
                            <td border="1"style="text-align:center;">4</td>
                            <td border="1">Education allowance</td>
                            <td border="1" style="text-align:right;">'.number_format((float) $updated_gross*0.10, 2, '.', '').'</td>
                            <td border="1" style="text-align:right;">'.number_format((float) ($updated_gross*0.10)*12, 2, '.', '').'</td>
                        </tr>
                        <tr>
                            <td border="1"style="text-align:center;">5</td>
                            <td border="1">Travelling allowance</td>
                            <td border="1" style="text-align:right;">'.number_format((float) $updated_gross*0.10, 2, '.', '').'</td>
                            <td border="1" style="text-align:right;">'.number_format((float) ($updated_gross*0.10)*12, 2, '.', '').'</td>
                        </tr>
                        <tr>
                            <td border="1" colspan="2" style="text-align:right;"><b>Gross Total (A)</b></td>
                            <td border="1" style="text-align:right;">'.number_format((float) $updated_gross, 2, '.', '').'</td>
                            <td border="1" style="text-align:right;">'.number_format((float) $updated_gross*12, 2, '.', '').'</td>
                        </tr>
                        <tr>
                            <td border="1" colspan="4">Deductions</td>
                        </tr>
                        <tr>
                            <td border="1" colspan="2" style="text-align:right;">Prof Tax</td>
                            <td border="1" style="text-align:right;">'.number_format((float) $deduction, 2, '.', '').'</td>
                            <td border="1" style="text-align:right;">'.number_format((float) $annualdeduction, 2, '.', '').'</td>
                        </tr>
                        <tr>
                            <td border="1" colspan="2" style="text-align:right;"><b>Deduction Total (B)</b></td>
                            <td border="1" style="text-align:right;">'.number_format((float) $deduction, 2, '.', '').'</td>
                            <td border="1" style="text-align:right;">'.number_format((float) $annualdeduction, 2, '.', '').'</td>
                        </tr>
                        <tr>
                            <td border="1" colspan="2" style="text-align:right;"><b>Net Income (A - B)</b></td>
                            <td border="1" style="text-align:right;"><b>'.number_format((float) $net, 2, '.', '').'</b></td>
                            <td border="1" style="text-align:right;"><b>'.number_format((float) $netannual, 2, '.', '').'</b></td>
                        </tr>
                    </table> 
                    <br><br><br><br><br><br>
                    <table cellpadding="5" style="text-align:left; width:100%;">
                        <tr>
                            <td style="width:40%; text-align:center;"><b>Cyclone Pharmaceuticals Pvt Ltd</b></td>
                            <td style="width:10%;"></td>
                            <td style="width:60%; text-align:center;">Accepted</td>
                        </tr>
                        <br><br><br>
                        <tr>
                            <td style="width:40%; text-align:center;">Mr. Sachin Bhalekar <br>Managing Director</td>
                            <td style="width:10%;"></td>
                            <td style="width:60%; text-align:center;">(Signature of an Employee)</td>
                        </tr>
                    </table>';
                }
              
                    $html='
                    <br><br><br>
                    <h3 style="text-align:center;">INCREMENT AND PROMOTION LETTER</h3>
                    <table cellpadding="5" style="text-align:left; width:100%;">
                        <tr>
                            <td style="width:70%;"><b><label>'.$letter_no.'</label></b></td>
                            <td style="width:30%; text-align:right;"><b>Date : </b><label>'.$newDate.'</label></td>
                        </tr>
                        <tr>
                            <td colspan="3"><b>To,</b></td>
                        </tr>
                        <tr>
                            <td style="width:50%;"><b>'.$row2['emp_name'].' <br>'.$address.'</b></td>
                        <tr>
                    </table>
                    <h3>Dear '.$title.' '.$name.' , </h3><br>
                    <table>
                        <tr>
                            <td style="width:5%;"></td>
                            <td style="width:95%;">Management of Cyclone Pharmaceuticals Pvt Ltd is happy to inform you that your performance in Month May 2019 to '.$lastmonth.' appreciable .This appreciation Letter is being given for your hard working and Learning attitude.<br>Keep it up and accept this letter along with a small token of Love for your performance from Cyclone Pharmaceuticals Pvt Ltd Management<br>Also management has decided to revise your salary structure which will be effective from 01 '.$monthname.' please find Annexure attached with this Letter.<br>Along with this management has decided to upgrade your post to give you more opportunity to show your abilities<br>Current Designation: '.$input['current_designation'].'<br>Promoted Designation: '.$updated_designation.'<br>Your roles and Responsibilities will be inform you by Management<br><br>Best of Luck for your Bright Future<br>Thanks and Regards,
                            </td>
                        </tr>
                    </table>
                    <br><br><br>
                    <table cellpadding="5" style="text-align:left; width:100%;">
                        <tr>
                            <td style="width:40%; text-align:center;"><b>Yours Faithfully,</b></td>
                          </tr>
                          <tr>
                            <td style="width:40%; text-align:center;"><b>Cyclone Pharmaceuticals Pvt Ltd</b></td>
                            <td style="width:10%;"></td>
                            <td style="width:60%;">I accept and agree to the above terms & conditions	</td>
                          </tr>
                          <tr><td></td></tr>
                          <tr>
                            <td style="width:40%; text-align:center;"><b>Mr. Sachin Bhalekar</b></td>
                            <td style="width:10%;"></td>
                            <td style="width:60%; text-align:center;">Signature of an Employee</td>
                        </tr>
                    </table>
                    <br pagebreak="true"/>
                    <br><br></br>
                    <h4 style="text-align:center;">Salary Annexure : '.$row2['emp_name'].' </h4>
                    <br></br><br>
                    <table cellpadding="7" style="border: 1px solid #DCDCDC; text-align:left; ">
                        <tr style="background-color:#DCDCDC;">
                            <td  border="1" style="width:10%; text-align:center;"><b>Sr No</b></td>
                            <td  border="1" style="width:40%; text-align:center;"><b>Particulars</b></td>
                            <td border="1" style="width:25%; text-align:center;"><b>Salary Per Month</b></td>
                            <td border="1" style="width:25%; text-align:center;"><b>Annual Salary</b></td>
                        </tr>
                        <tr>
                            <td border="1" style="width:10%; text-align:center;">1</td>
                            <td border="1" style="width:40%;">Basic</td>
                            <td border="1" style="width:25%; text-align:right;">'.number_format((float) $updated_gross*0.40, 2, '.', '').'</td>
                            <td border="1" style="width:25%; text-align:right;">'.number_format((float) ($updated_gross*0.40)*12, 2, '.', '').'</td>
                        </tr>
                        <tr>
                            <td border="1" style="text-align:center;">2</td>
                            <td border="1">HRA</td>
                            <td border="1" style="text-align:right;">'.number_format((float) $updated_gross*0.20, 2, '.', '').'</td>
                            <td border="1" style="text-align:right;">'.number_format((float) ($updated_gross*0.20)*12, 2, '.', '').'</td>
                        </tr>
                        <tr>
                            <td border="1" style="text-align:center;">3</td>
                            <td border="1">Conveyance</td>
                            <td border="1" style="text-align:right;">'.number_format((float) $updated_gross*0.10, 2, '.', '').'</td>
                            <td border="1" style="text-align:right;">'.number_format((float) ($updated_gross*0.10)*12, 2, '.', '').'</td>
                        </tr>
                        <tr>
                            <td border="1"style="text-align:center;">5</td>
                            <td border="1">Medical allowance</td>
                            <td border="1" style="text-align:right;">'.number_format((float) $updated_gross*0.10, 2, '.', '').'</td>
                            <td border="1" style="text-align:right;">'.number_format((float) ($updated_gross*0.10)*12, 2, '.', '').'</td>
                        </tr>
                        <tr>
                            <td border="1"style="text-align:center;">4</td>
                            <td border="1">Education allowance</td>
                            <td border="1" style="text-align:right;">'.number_format((float) $updated_gross*0.10, 2, '.', '').'</td>
                            <td border="1" style="text-align:right;">'.number_format((float) ($updated_gross*0.10)*12, 2, '.', '').'</td>
                        </tr>
                        <tr>
                            <td border="1"style="text-align:center;">5</td>
                            <td border="1">Travelling allowance</td>
                            <td border="1" style="text-align:right;">'.number_format((float) $updated_gross*0.10, 2, '.', '').'</td>
                            <td border="1" style="text-align:right;">'.number_format((float) ($updated_gross*0.10)*12, 2, '.', '').'</td>
                        </tr>
                        <tr>
                            <td border="1" colspan="2" style="text-align:right;"><b>Gross Total (A)</b></td>
                            <td border="1" style="text-align:right;">'.number_format((float) $updated_gross, 2, '.', '').'</td>
                            <td border="1" style="text-align:right;">'.number_format((float) $updated_gross*12, 2, '.', '').'</td>
                        </tr>
                        <tr>
                            <td border="1" colspan="4">Deductions</td>
                        </tr>
                        <tr>
                            <td border="1" colspan="2" style="text-align:right;">Prof Tax</td>
                            <td border="1" style="text-align:right;">'.number_format((float) $deduction, 2, '.', '').'</td>
                            <td border="1" style="text-align:right;">'.number_format((float) $annualdeduction, 2, '.', '').'</td>
                        </tr>
                        <tr>
                            <td border="1" colspan="2" style="text-align:right;"><b>Deduction Total (B)</b></td>
                            <td border="1" style="text-align:right;">'.number_format((float) $deduction, 2, '.', '').'</td>
                            <td border="1" style="text-align:right;">'.number_format((float) $annualdeduction, 2, '.', '').'</td>
                        </tr>
                        <tr>
                            <td border="1" colspan="2" style="text-align:right;"><b>Net Income (A - B)</b></td>
                            <td border="1" style="text-align:right;"><b>'.number_format((float) $net, 2, '.', '').'</b></td>
                            <td border="1" style="text-align:right;"><b>'.number_format((float) $netannual, 2, '.', '').'</b></td>
                        </tr>
                    </table> 
                    <br><br><br><br><br><br>
                    <table cellpadding="5" style="text-align:left; width:100%;">
                        <tr>
                            <td style="width:40%; text-align:center;"><b>Cyclone Pharmaceuticals Pvt Ltd</b></td>
                            <td style="width:10%;"></td>
                            <td style="width:60%; text-align:center;">Accepted</td>
                        </tr>
                        <br><br><br>
                        <tr>
                            <td style="width:40%; text-align:center;">Mr. Sachin Bhalekar <br>Managing Director</td>
                            <td style="width:10%;"></td>
                            <td style="width:60%; text-align:center;">Signature of an Employee</td>
                        </tr>
                    </table>';
                }
            
        
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('appintment Letter.pdf', 'I');
        
  
      
    }


$conn->close();

function isWeekend($date) {
    $weekDay = date('w', strtotime($date));
    return ($weekDay == 0);
}
?>