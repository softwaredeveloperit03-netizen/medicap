<?php
    require '../../db.php';
    require '../../token.php';
    require '../../tcpdf/tcpdf.php';
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

if ($_GET["type"] == "getMaterials") {
    $output = Array();
    $sql = "SELECT * FROM material WHERE status='active' AND material_code NOT IN (SELECT material_code FROM moa WHERE status='approve' OR status='pending' OR status='checked')";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {

            $sql1 = "SELECT * FROM specification WHERE material_code='".$row["material_code"]."' AND status='approve'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["specification"] = true;

                    $output1 = Array();
                    $sql2 = "SELECT * FROM spec_tests WHERE specification_no='".$row1["specification_no"]."'";
                    $result2 = $conn->query($sql2);
                    if ($result2->num_rows > 0) {
                        while ($row2 = $result2->fetch_assoc()) {
                            $output3 = Array();
                            $sql3 = "SELECT * FROM test_method WHERE test='".$row2["test"]."' AND subtest='".$row2["subtest"]."' AND status='approve'";
                            $result3 = $conn->query($sql3);
                            if ($result3->num_rows > 0) {
                                while ($row3 = $result3->fetch_assoc()) {
                                    $output3[] = $row3;
                                }
                            }
                            $row2["methods"] = $output3;

                            $output1[] = $row2;
                        }
                    }
                    $row1["tests"] = $output1;

                    $row["specifications"] = $row1;
                }
            } else {
                $row["specification"] = false;
            }

            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "saveMOA") {
    $sql = "INSERT INTO moa (material_type, material_code, specification_no, tests, entry_by, entry_date) VALUES ('Raw Material', '".$input["material_code"]."', '".$input["specification_no"]."', '".json_encode($input["tests"])."', '".$_GET["emp_id"]."', '$entry_date')";
    if ($conn->query($sql) === TRUE) {

        $sql = "UPDATE specification SET ismoa = 'active' WHERE specification_no = '".$input["specification_no"]."' LIMIT 1";
        if ($conn->query($sql) === TRUE) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "getReports") {
    $output = Array();
    $sql = "SELECT * FROM moa";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {

            $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["material_name"] = $row1["material_name"];
                    $row["grade"] = $row1["grade"];
                }
            }
            $row["tests"] = json_decode($row["tests"]);
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getMoaSpecification") {
    
    $sql ="SELECT sp.*, m.material_name, m.grade FROM specification sp JOIN material m ON sp.material_code = m.material_code WHERE sp.ismoa='pending'";
    $result = $conn->query($sql);
    $output = array();

    if ($result->num_rows > 0) {
        while ($row = $result-> fetch_assoc()) {

            $msql = "SELECT * FROM spec_tests WHERE specification_no = '".$row["specification_no"]."'";
            $mresult = $conn->query($msql);
            $moutput = array();

            if ($mresult->num_rows > 0) {
                while ($mrow = $mresult-> fetch_assoc()) {

                    $nsql = "SELECT * FROM test_method WHERE test='".$mrow["test"]."' AND subtest='".$mrow["subtest"]."' AND status='approve'";
                    $nresult = $conn->query($nsql);
                    $data = array();

                    if ($nresult->num_rows > 0) {
                        while ($nrow = $nresult->fetch_assoc()) {
                            $data[] = $nrow;
                        }
                    }

                    $mrow["methods"] = $data;
                    $moutput[] = $mrow; 
                }
            }

            $row["tests"] = $moutput;
            $output[] = $row;
        }
    }
    echo json_encode($output);

}



} else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
?>