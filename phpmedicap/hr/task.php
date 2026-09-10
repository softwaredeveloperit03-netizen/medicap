<?php


//  ini_set('display_errors', 1);
//  error_reporting(E_ALL);
 
 
   require '../db.php';
    require '../token.php';
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
    
    
    if ($_GET["type"] == "getEmployees") {
        // echo "1";exit;
        $output = Array();
        $sql = "SELECT * FROM employee where status = 'active' AND plant_id = '".$_GET["plant_id"]."' ";
        $result = $conn->query($sql);
        // print_r($result);exit;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "HOgetEmployees") {
        // echo "1";exit;
        $output = Array();
        $sql = "SELECT * FROM employee where status = 'active' AND plant_id = '".$_GET["plantID"]."' ";
        $result = $conn->query($sql);
        // print_r($result);exit;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "saveTask") {
        $sql = "INSERT INTO task (plant_id,employee, task, estimated_start, estimated_complete, assign_by, assign_date,description,evaluation) VALUES ('".$_GET["plant_id"]."','".$input["employee"]."', '".$input["task"]."', '".$input["estimated_start"]."', '".$input["estimated_complete"]."', '".$_GET["emp_id"]."', '$entry_date', '".$input["description"]."', '".$input["evaluation"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    else if ($_GET["type"] == "myTodaysTasks_time") {
        $sql = "update task set estimated_start='".$_GET["start_time"]."' where id='".$_GET["id"]."'  ";
        // $sql = "update task set estimated_start='".$_GET["start_time"]."'  where id='".$_GET["emp_id"]."' ";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    else if ($_GET["type"] == "check_task") {
                          
         $doc_type = $_POST["task"];
         $id = $_GET["id"];
                 
                 	if(isset($_FILES["task"])) {
            $file_tmp =$_FILES['task']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['task']['name'])));
            $file_name = $id."doc.".$file_ext;
            $doc = $file_name;
            move_uploaded_file($file_tmp,"../../../upload/task/".$file_name);
        }
        
        
         $sql = "update task set check_by='".$_GET["emp_id"]."',check_date='$entry_date',task_file = '$doc' where id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    else if ($_GET["type"] == "myTodaysTasks_time2") {
        // $sql = "update task set estimated_complete='".$_GET["end_time"]."' where id='".$_GET["id"]."'";
         $sql = "update task set estimated_complete='".$_GET["end_time"]."' where id='".$_GET["id"]."' ";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    else if ($_GET["type"] == "getTasks") {
        $output = Array();
        $sql = "SELECT * FROM task where check_by!='0'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                if ($row["status"] == "inprocess" || $row["status"] == "complete") {
                    $datetime1 = $row["estimated_start"];
                    $datetime2 = $row["start_date"];
                    $interval = $datetime1->diff($datetime2);
                    $row["delay"] = $interval->format('%H:%i');
                } else {
                    $datetime1 = new DateTime($row["estimated_start"]);
                    $datetime2 = new DateTime();
                    $interval = $datetime1->diff($datetime2);
                    $row["delay"] = $interval->format('%i');
                }
                
                if ($row["status"] == "inprocess") {
                    $datetime1 = $row["start_date"];
                    $datetime2 = new DateTime();
                    $interval = $datetime1->diff($datetime2);
                    $row["spend_time"] = $interval->format('%H:%i');
                }
                
                if ($row["status"] == "complete") {
                    $datetime1 = $row["start_date"];
                    $datetime2 = $row["complete_date"];
                    $interval = $datetime1->diff($datetime2);
                    $row["spend_time"] = $interval->format('%H:%i');
                    
                    $datetime1 = $row["estimated_start"];
                    $datetime2 = $row["estimated_complete"];
                    $interval = $datetime1->diff($datetime2);
                    $row["estimate_time"] = $interval->format('%i');
                }
                
                // $sql1 = "SELECT * FROM employee WHERE emp_id='".$row["employee"]."'";
                // $result1 = $conn->query($sql1);
                // if ($result1->num_rows > 0) {
                //     while ($row1 = $result1->fetch_assoc()) {
                //         $row["emp_name"] = $row1["firstname"];
                //         $row["contact_no"] = $row1["emp_contact"];
                //         if ($row1["department"] == $_GET["department"]) {
                            $output[] = $row;
                //         }
                //     }
                // }
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "myTodaysTasks") {
        
        $output = Array();
    // $sql="SELECT  t.employee, t.task,e.firstname from task t LEFT JOIN employee e on t.employee=e.emp_id WHERE employee='".$_GET["emp_id"]."'" ;       
    // $sql="SELECT  t.employee,t.task,e.firstname from task t LEFT JOIN employee e on t.employee='".$_GET["emp_id"]."'  " ;       
  $sql = "SELECT DISTINCT t.employee,t.task, t. *,e.firstname FROM task t left JOIN employee e on t.employee=e.emp_id   ";
  
     
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
  
                            $output[] = $row;
             
            }
        }
        echo json_encode($output);
    
    }
    else if ($_GET["type"] == "TodaysTaskscheck") {
        
        $output = Array();
        $sql = "SELECT DISTINCT t.employee, t. *,e.firstname FROM task t left JOIN employee e on t.employee=e.emp_id where  
        estimated_complete!='' and check_by='0' ";
     
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
  
                            $output[] = $row;
             
            }
        }
        echo json_encode($output);
    
    }
    else if ($_GET["type"] == "getTodaysTasks") {
        $output = Array();
        $sql = "SELECT DISTINCT t.employee, t. *,e.firstname FROM task t left JOIN employee e on t.employee=e.emp_id";
        // $sql = "SELECT t. *,e.firstname FROM task t left JOIN employee e on t.employee=e.emp_id WHERE DATE(t.estimated_start)= CURDATE() OR DATE(t.start_date) = CURDATE()";
        //  $sql = "SELECT * FROM task ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                if ($row["status"] == "inprocess" || $row["status"] == "complete") {
                    $datetime1 = $row["estimated_start"];
                    $datetime2 = $row["start_date"];
                    $interval = $datetime1->diff($datetime2);
                    $row["delay"] = $interval->format('%H:%i');
                } else {
                    $datetime1 = new DateTime($row["estimated_start"]);
                    $datetime2 = new DateTime();
                    $interval = $datetime1->diff($datetime2);
                    $row["delay"] = $interval->format('%i');
                }
                
                if ($row["status"] == "inprocess") {
                    $datetime1 = $row["start_date"];
               echo     $datetime2 = new DateTime();
                    $interval = $datetime1->diff($datetime2);
                    $row["spend_time"] = $interval->format('%H:%i');
                }
                
                if ($row["status"] == "complete") {
                    $datetime1 = $row["start_date"];
                    $datetime2 = $row["complete_date"];
                    $interval = $datetime1->diff($datetime2);
                    $row["spend_time"] = $interval->format('%H:%i');
                    
                    $datetime1 = $row["estimated_start"];
                    $datetime2 = $row["estimated_complete"];
                    $interval = $datetime1->diff($datetime2);
                    $row["estimate_time"] = $interval->format('%i');
                }
                
                // $sql1 = "SELECT * FROM employee WHERE emp_id='".$row["employee"]."'";
                // $result1 = $conn->query($sql1);
                // if ($result1->num_rows > 0) {
                //     while ($row1 = $result1->fetch_assoc()) {
                //         $row["emp_name"] = $row1["emp_name"];
                //         $row["contact_no"] = $row1["emp_contact"];
                //         if ($row1["department"] == $_GET["department"]) {
                            $output[] = $row;
                //         }
                //     }
                // }
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getEmployeePendingTasks") {
        $output = Array();
        $sql = "SELECT * FROM task WHERE status IN ('pending' || 'inprocess') AND employee='".$_GET["emp_id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getEmployeeCompletedTasks") {
        $output = Array();
        $sql = "SELECT * FROM task WHERE status='complete' AND employee='".$_GET["emp_id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getEmployeeRejectedTasks") {
        $output = Array();
        $sql = "SELECT * FROM task WHERE status='rejected' AND employee='".$_GET["emp_id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getEmployeeCancelTasks") {
        $output = Array();
        $sql = "SELECT * FROM task WHERE status='cancel' AND employee='".$_GET["emp_id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
              while ($row = $result->fetch_assoc()) {
                   $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "startTask") {
        $sql = "UPDATE task SET start_date='$entry_date', status='inprocess' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "completeTask") {
        $sql = "UPDATE task SET complete_date='$entry_date', status='complete', remark='".$_GET["remark"]."' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "updateTask") {
        $sql = "UPDATE task SET status='".$_GET["status"]."', check_by='".$_GET["emp_id"]."' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
   


}

$conn->close();
?>