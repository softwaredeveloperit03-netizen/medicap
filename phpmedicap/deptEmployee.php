<?php
    //  ini_set('display_errors', 1);
    // error_reporting(E_ALL); 
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
 



if ($_GET["type"] == "getEmployeeHumanResource") {
    $output = array();
    $sql = "SELECT firstname as emp_name,emp_id  FROM employee WHERE department='Human Resource'";
       $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
}
else if ($_GET["type"] == "getEmployeeQualityControl") {
    $output = array();
    $sql = "SELECT firstname as emp_name,emp_id  FROM employee WHERE department='Quality Control'";
       $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
}
else if ($_GET["type"] == "getEmployeePurchase") {
    $output = array();
    $sql = "SELECT firstname as emp_name,emp_id  FROM employee WHERE department='Purchase'";
       $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
}
else if ($_GET["type"] == "getEmployeeQualityAssurance") {
    $output = array();
    $sql = "SELECT firstname as emp_name,emp_id  FROM employee WHERE department='Quality Assurance'";
       $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
}
else if ($_GET["type"] == "getEmployeeMicrobiology") {
    $output = array();
    $sql = "SELECT firstname as emp_name,emp_id  FROM employee WHERE department='Microbiology'";
       $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
}
else if ($_GET["type"] == "getEmployeeEngineering") {
    $output = array();
    $sql = "SELECT firstname as emp_name,emp_id  FROM employee WHERE department='Engineering'";
       $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
}
else if ($_GET["type"] == "getEmployeeStore") {
    $output = array();
    $sql = "SELECT firstname as emp_name,emp_id  FROM employee WHERE department='Store'";
       $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
}
else if ($_GET["type"] == "getEmployeePacking") {
    $output = array();
    $sql = "SELECT firstname as emp_name,emp_id  FROM employee WHERE department='Packing'";
       $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
}
else if ($_GET["type"] == "getEmployeeRANDD") {
    $output = array();
    $sql = "SELECT firstname as emp_name,emp_id  FROM employee WHERE department='R AND D'";
       $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
}
else if ($_GET["type"] == "getEmployeeAdmin") {
    $output = array();
    $sql = "SELECT firstname as emp_name,emp_id  FROM employee WHERE department='Admin'";
       $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
}
else if ($_GET["type"] == "getEmployeeRegulatory") {
    $output = array();
    $sql = "SELECT firstname as emp_name,emp_id  FROM employee WHERE department='Regulatory'";
       $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
}
else if ($_GET["type"] == "getEmployeeProduction") {
    $output = array();
    $sql = "SELECT firstname as emp_name,emp_id  FROM employee WHERE department='Production'";
       $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
}
else if ($_GET["type"] == "getEmployeeIT") {
    $output = array();
    $sql = "SELECT firstname as emp_name,emp_id  FROM employee WHERE department='IT'";
       $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
}
else if ($_GET["type"] == "getEmployeeADL") {
    $output = array();
    $sql = "SELECT firstname as emp_name,emp_id  FROM employee WHERE department='ADL'";
       $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
}
else if ($_GET["type"] == "getEmployeemaster") {
    $output = array();
    $sql = "SELECT firstname as emp_name,emp_id  FROM employee WHERE department='master'";
       $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
}
else if ($_GET["type"] == "getEmployeeManagement") {
    $output = array();
    $sql = "SELECT firstname as emp_name,emp_id  FROM employee WHERE department='Management'";
       $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
}
else if ($_GET["type"] == "getEmployeeAccounts") {
    $output = array();
    $sql = "SELECT firstname as emp_name,emp_id  FROM employee WHERE department='Accounts'";
       $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
}
else if ($_GET["type"] == "getEmployeeFGStore") {
    $output = array();
    $sql = "SELECT firstname as emp_name,emp_id  FROM employee WHERE department='FG Store'";
       $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
}
else if ($_GET["type"] == "getEmployeeGeneralStore") {
    $output = array();
    $sql = "SELECT firstname as emp_name,emp_id  FROM employee WHERE department='General Store'";
       $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
}
else if ($_GET["type"] == "getEmployeeIPQC") {
    $output = array();
    $sql = "SELECT firstname as emp_name,emp_id  FROM employee WHERE department='IPQC'";
       $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
}
else if ($_GET["type"] == "getEmployeeEHS") {
    $output = array();
    $sql = "SELECT firstname as emp_name,emp_id  FROM employee WHERE department='EHS'";
       $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
}
else if ($_GET["type"] == "getEmployeeSecurity") {
    $output = array();
    $sql = "SELECT firstname as emp_name,emp_id  FROM employee WHERE department='Security'";
       $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
}
else if ($_GET["type"] == "getEmployeeMarketing") {
    $output = array();
    $sql = "SELECT firstname as emp_name,emp_id  FROM employee WHERE department='Marketing'";
       $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
}
else if ($_GET["type"] == "getEmployeePlanning") {
    $output = array();
    $sql = "SELECT firstname as emp_name,emp_id  FROM employee WHERE department='Planning'";
       $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
}
else if ($_GET["type"] == "getEmployeeLogistics") {
    $output = array();
    $sql = "SELECT firstname as emp_name,emp_id  FROM employee WHERE department='Logistics'";
       $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
}
else if ($_GET["type"] == "getEmployeeOperations") {
    $output = array();
    $sql = "SELECT firstname as emp_name,emp_id  FROM employee WHERE department='Operations'";
       $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
}
else if ($_GET["type"] == "getEmployeeSCM") {
    $output = array();
    $sql = "SELECT firstname as emp_name,emp_id  FROM employee WHERE department='SCM'";
       $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
}
else if ($_GET["type"] == "getEmployeeGuestHouse") {
    $output = array();
    $sql = "SELECT firstname as emp_name,emp_id  FROM employee WHERE department='Guest House'";
       $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
}

   
 





}

$conn->close();
?>