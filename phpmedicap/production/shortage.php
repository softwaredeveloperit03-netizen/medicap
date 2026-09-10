<?php
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

    $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."')";
    $conn->query($sql);
    
    if ($_GET["type"] == "getProducts") {
        $output1 = Array();
        $sql1 = "SELECT * FROM product WHERE status='approve'";
        $result1 = $conn->query($sql1);
        if ($result1->num_rows > 0) {
            while ($row1 = $result1->fetch_assoc()) {
                $output2 = Array();
                $sql2 = "SELECT * FROM batch_formula WHERE product_code='".$row1["product_code"]."' AND status='approve'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $output3 = Array();
                        $sql3 = "SELECT b.*, m.material_name, m.grade FROM batch_materials b LEFT JOIN material m ON b.material_code=m.material_code WHERE b.no='".$row2["id"]."'";
                        $result3 = $conn->query($sql3);
                        if ($result3->num_rows > 0) {
                            while ($row3 = $result3->fetch_assoc()) {
                                
                                $row3["req_qty"] = number_format(+$row3["batch_qty"] + ((+$row3["overages"] * +$row3["batch_qty"]) / 100), 2);
                                
                                $sql4 = "SELECT IFNULL(SUM(qty), 0) as qty FROM stock_book WHERE material_code='".$row3["material_code"]."' AND status='Approved'";
                                $result4 = $conn->query($sql4);
                                if ($result4->num_rows > 0) {
                                    $row3["status"] = "available";
                                    while ($row4 = $result4->fetch_assoc()) {
                                        $row3["available_qty"] = $row4["qty"];
                                        if (+$row4["qty"] == 0) {
                                            $row3["avl_qty"] = 0.00;
                                            $row3["status"] = "na";
                                        } else if (+$row4["qty"] < +$row3["batch_qty"]) {
                                            $row3["avl_qty"] = +$row3["qty"];
                                            $row3["status"] = "short";
                                        } else if (+$row4["qty"] >= +$row3["batch_qty"]) {
                                            $row3["avl_qty"] = +$row3["batch_qty"];
                                            $row3["status"] = "available";
                                        }
                                    }
                                } else {
                                    $row3["avl_qty"] = 0;
                                    $row3["status"] = "not available";
                                }
                                $output3[] = $row3;
                            }
                        }
                        $row2["materials"] = $output3;
                        
                        $output3 = Array();
                        $sql3 = "SELECT * FROM batch_stages WHERE no='".$row2["id"]."'";
                        $result3 = $conn->query($sql3);
                        if ($result3->num_rows > 0) {
                            while ($row3 = $result3->fetch_assoc()) {
                                $output3[] = $row3;
                            }
                        }
                        $row2["stages"] = $output3;
                        $output2[] = $row2;
                    }
                }
                $row1["batches"] = $output2;
                $output1[] = $row1;
            }
        }
        echo json_encode($output1);
    }


}

$conn->close();
?>