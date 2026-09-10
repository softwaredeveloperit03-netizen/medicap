<?php

//  ini_set('display_errors', 1);
//  error_reporting(E_ALL);

    require 'db.php';
    require 'token.php';
    require 'tcpdf/tcpdf.php';
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

    $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."')";
    $conn->query($sql);
    
    if ($_GET["type"] == "getDeptEmployees1") {
        $output = array();
        $sql = "";
        if ($_GET["department"] == "Master") {
            $sql = "SELECT * FROM employee WHERE department='Store' AND plant_id='".$_GET["plant_id"]."'";
        } else { 
            $sql = "SELECT * FROM employee WHERE department='".$_GET["department"]."' AND plant_id='".$_GET["plant_id"]."'";
        }
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    if ($_GET["type"] == "getDeptEmployees") {
        $output = array();
        
        $sql = "SELECT * FROM employee WHERE department='".$_GET["department_name"]."' AND plant_id='".$_GET["plant_id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    if ($_GET["type"] == "getDesigEmployees") {
        $output = array();
        
        $sql = "SELECT * FROM employee WHERE designation='".$_GET["designation"]."' AND plant_id='".$_GET["plant_id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getEmployeesbyDpt") {
        $output = array();
        
         $sql = "SELECT designation,operator_category,department,lastname,middlename,firstname,emp_id,id FROM employee WHERE department='".$_GET["selecteddepartment"]."'
         AND status = 'active' AND plant_id='".$_GET["plant_id"]."' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row['emp_name'] = $row['firstname'].' '.$row['middlename'].' '.$row['lastname'];
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getEmployees") {
        $output = array();
        
        $sql = "SELECT * FROM employee WHERE status = 'active' AND  plant_id='".$_GET["plant_id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getQCPersons") {
        $output = array();
        $sql = "SELECT * FROM employee WHERE department='Quality Control' OR department='master'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }else if ($_GET["type"] == "getPurchasePersons") {
        $output = array();
        $sql = "SELECT * FROM employee WHERE department='Purchase' AND isapprover='true'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getQAPersons") {
        $output = array();
        $sql = "SELECT * FROM employee WHERE department='Quality Assurance'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
        
    }
    else if ($_GET["type"] == "getTechnicalEmployees") {
        $output = array();
        $sql = "SELECT * FROM employee WHERE employee_type='TECHNICAL' AND  plant_id='".$_GET["plant_id"]."'  ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getInductionTrainingEmployee") {
        $output = array();
        $sql = "SELECT * FROM employee WHERE isinduction='Yes' AND induction_training='Pending' AND  plant_id='".$_GET["plant_id"]."'  ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
  
    
    else if ($_GET["type"] == "getProductionExecutiveOfficers") {
        $output = array();
        $sql = "SELECT * FROM employee WHERE department='Production' AND designation IN ('Executive', 'Officer')";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getApproverOfficers") {
        $output = array();
        $sql = "SELECT * FROM employee WHERE department='".$_GET["department_name"]."' AND designation= 'Manager'";
       
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row = array_map('utf8_encode', $row);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getEngineeringPersons") {
        $output = array();
        $sql = "SELECT * FROM employee WHERE department='Engineering'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getUtilityTechAssistancePersons") {
        $output = array();
        $sql = "SELECT * FROM employee WHERE department='Engineering' AND designation='Technical Assistant'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getUtilitySelectedPersons") {
        $output = array();
        $sql = "SELECT * FROM employee WHERE department='Engineering' AND emp_id IN ('289', '313', '260')";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getAllEmployees") {
        $output = array();
        $sql = "SELECT * FROM employee WHERE status IN ('active', 'approve')";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getStorePersons") {
        $output = array();
        $sql = "SELECT * FROM employee WHERE department='Store'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getSrEmployees") {
        $output = array();
        $sql = "SELECT * FROM employee WHERE status IN ('active', 'approve') AND designation IN ('Manager', 'Executive', 'Asst.Manager')";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getOperators") {
        $output = array();
        $sql = "SELECT * FROM labour WHERE labour_type='Operator'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getEmployeeByDepartment") {
        $output = array();
        $sql = "SELECT * FROM employee WHERE department='".$_GET["department_name"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row = array_map('utf8_encode', $row);
                $output[] = $row;
            }
        }
        echo json_encode($output);
        
        
    }
    else if ($_GET["type"] == "getEmp") {
        $output = array();
        $sql = "SELECT * FROM employee ";//WHERE department='".$_GET["department_name"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row = array_map('utf8_encode', $row);
                $output[] = $row;
            }
        }
        echo json_encode($output);
        
        
    }
    else if ($_GET["type"] == "getDeptByManagers") {
        
        
        $output = array();
        $sql = "SELECT * FROM employee WHERE department='".$_GET["department_name"]."' AND status='active' AND designation LIKE '%".$_GET['designation']."%'";
        //echo $sql;
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
        
        
    }
    else if ($_GET["type"] == "getdeptHead") {
        
        
        $output = array();
        $sql = "SELECT e.* FROM employee e left join  emp_rights r ON e.emp_id = r.emp_id WHERE r.department='".$_GET["department_name"]."' AND e.status='active' AND r.dept_head  = 'Yes'";
        //echo $sql;
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
        
        
    }
    
    
    else if ($_GET["type"] == "getDeptHandover") {
        $output = array();
        //$sql = "SELECT * FROM employee WHERE department='".$_GET["department_name"]."' AND status='active'";
        $sql = "SELECT * FROM employee WHERE department='".$_GET["department_name"]."' AND status='active' AND designation NOT LIKE '%Manager%'";
        //echo $sql;
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getDeptalternateperson") {
        $output = array();

         $sql = "SELECT * FROM employee WHERE department='".$_GET["department_name"]."' AND emp_id != '".$_GET["empid"]."' 
        AND status='active' AND designation NOT LIKE '%Manager%'";
        
        //echo $sql;
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getDeptalternateperson1") {
        $output = array();

        $sql = "SELECT * FROM employee WHERE department='Quality Assurance' AND status='active' AND designation NOT LIKE '%Manager%'";
        
        //echo $sql;
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getDeptalternateperson1Meha") {
        $output = array();

        $sql = "SELECT * FROM employee WHERE department='".$_GET["department_name"]."'  AND status='active'  ";
        
        //echo $sql;
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }


}

$conn->close();
?>