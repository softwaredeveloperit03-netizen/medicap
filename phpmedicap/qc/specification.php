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
        
    if ($_GET["type"] == "getAwaitingRevisions") {
        $output = array();
        $effectiveDate = date('Y-m-d', strtotime("+15 days", strtotime($entry_date)));
        $sql = "SELECT * FROM specification"; 
        //WHERE review_date BETWEEN DATE('".$entry_date."') AND '".$effectiveDate."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $date1 = new DateTime($row["review_date"]);
                $date2 = new DateTime(date("Y-m-d", $timestamp));
                $interval = $date1->diff($date2);
                $row["due_days"] = $interval->days;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if($_GET["type"]=='packingReport') {
            $output = Array();
            $sql = "SELECT * FROM specification WHERE spec_type='Packing Material Specification' AND status !='obsolate'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["material_subtype"] = $row1["material_subtype"];
                            $row["material_name"] = $row1["material_name"];
                            $row["grade"] = $row1["grade"];
                        }
                    }
                    $sql2 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
                    $result2 = $conn->query($sql2);
                    if ($result2->num_rows > 0) {
                        while ($row2 = $result2->fetch_assoc()) {
                            $row["product_name"] = $row2["product_name"];
                            $row["generic_name"] = $row2["generic_name"];
                        }
                    }
                    $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
                    $result1 = $conn->query($sql1);
                    $output1 = array();
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $sql2 = "SELECT * FROM spec_tests WHERE test='".$row1["test"]."' AND subtest!='' AND specification_no='".$row1["specification_no"]."'";
                            $result2 = $conn->query($sql2);
                            $output2 = Array();
                            if ($result2->num_rows > 0) {
                                while ($row2 = $result2->fetch_assoc()) {
                                    $output2[] = $row2;
                                }
                            }
                            $row1["subtests"] = $output2;
                            $output1[] = $row1;
                        }
                    }
                    $row['spec_tests'] = $output1;
                    
                    $output1 = array();
                    $sql1 = "SELECT * FROM spec_revision WHERE spec_no='".$row["specification_no"]."'";
                    $result1 = $conn->query($sql1);
                    $output1 = Array();
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $output1[] = $row1;
                        }
                    }
                    $row['revision_history'] = $output1;
                    $output[] = $row;
                }
            }
            echo json_encode($output);
        } 
        else if($_GET['type']=='packingObsolate'){
            $output = Array();
            $sql = "SELECT * FROM specification WHERE spec_type='Packing Material Specification' AND status='obsolate'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["material_subtype"] = $row1["material_subtype"];
                            $row["material_name"] = $row1["material_name"];
                            $row["grade"] = $row1["grade"];
                        }
                    }
                    $sql2 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
                    $result2 = $conn->query($sql2);
                    if ($result2->num_rows > 0) {
                        while ($row2 = $result2->fetch_assoc()) {
                            $row["product_name"] = $row2["product_name"];
                            $row["generic_name"] = $row2["generic_name"];
                        }
                    }
                    $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
                    $result1 = $conn->query($sql1);
                    $output1 = array();
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $sql2 = "SELECT * FROM spec_tests WHERE test='".$row1["test"]."' AND subtest!='' AND specification_no='".$row1["specification_no"]."'";
                            $result2 = $conn->query($sql2);
                            $output2 = Array();
                            if ($result2->num_rows > 0) {
                                while ($row2 = $result2->fetch_assoc()) {
                                    $output2[] = $row2;
                                }
                            }
                            $row1["subtests"] = $output2;
                            $output1[] = $row1;
                        }
                    }
                    $row['spec_tests'] = $output1;
                    
                    $output1 = array();
                    $sql1 = "SELECT * FROM spec_revision WHERE spec_no='".$row["specification_no"]."'";
                    $result1 = $conn->query($sql1);
                    $output1 = Array();
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $output1[] = $row1;
                        }
                    }
                    $row['revision_history'] = $output1;
                    $output[] = $row;
                }
            }
            echo json_encode($output);
        }
        else if($_GET["type"]=='packingMaterial'){
            $output = Array();
            $sql = "SELECT * FROM specification WHERE spec_type='Raw Material Specification' AND status!='obsolate' GROUP by material_code";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["material_subtype"] = $row1["material_subtype"];
                            $row["material_name"] = $row1["material_name"];
                            $row["grade"] = $row1["grade"];
                        }
                    }
                    $output[] = $row;
                }
            }
            echo json_encode($output);
        }
        else if($_GET["type"]=='packingObsolateMaterial'){
            $output = Array();
            $sql = "SELECT * FROM specification WHERE spec_type='Raw Material Specification' AND status='obsolate' GROUP by material_code";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["material_subtype"] = $row1["material_subtype"];
                            $row["material_name"] = $row1["material_name"];
                            $row["grade"] = $row1["grade"];
                        }
                    }
                    $output[] = $row;
                }
            }
            echo json_encode($output);
        }
        
        else if($_GET["type"]=='finishReport'){
            $output = Array();
            $sql = "SELECT * FROM specification WHERE spec_type='Finish Product' AND status!='obsolate'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["material_subtype"] = $row1["material_subtype"];
                            $row["material_name"] = $row1["material_name"];
                            $row["grade"] = $row1["grade"];
                        }
                    }
                    $sql2 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
                    $result2 = $conn->query($sql2);
                    if ($result2->num_rows > 0) {
                        while ($row2 = $result2->fetch_assoc()) {
                            $row["product_name"] = $row2["product_name"];
                            $row["generic_name"] = $row2["generic_name"];
                            $row["grade"] = $row2["grade"];
                        }
                    }
                    
                    $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
                    $result1 = $conn->query($sql1);
                    $output1 = array();
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $sql2 = "SELECT * FROM spec_tests WHERE test='".$row1["test"]."' AND subtest!='' AND specification_no='".$row1["specification_no"]."'";
                            $result2 = $conn->query($sql2);
                            $output2 = Array();
                            if ($result2->num_rows > 0) {
                                while ($row2 = $result2->fetch_assoc()) {
                                    $output2[] = $row2;
                                }
                            }
                            $row1["subtests"] = $output2;
                            $output1[] = $row1;
                        }
                    }
                    $row['spec_tests'] = $output1;
                    
                    $output1 = array();
                    $sql1 = "SELECT * FROM spec_revision WHERE spec_no='".$row["specification_no"]."'";
                    $result1 = $conn->query($sql1);
                    $output1 = Array();
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $output1[] = $row1;
                        }
                    }
                    $row['revision_history'] = $output1;
                    $output[] = $row;
                }
            }
            echo json_encode($output);
        }
        else if($_GET["type"]=='finishHistory'){
            $output = Array();
            $sql = "SELECT * FROM specification WHERE spec_type='Finish Product' AND status!='obsolate'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["material_subtype"] = $row1["material_subtype"];
                            $row["material_name"] = $row1["material_name"];
                            $row["grade"] = $row1["grade"];
                        }
                    }
                    $sql2 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
                    $result2 = $conn->query($sql2);
                    if ($result2->num_rows > 0) {
                        while ($row2 = $result2->fetch_assoc()) {
                            $row["product_name"] = $row2["product_name"];
                            $row["generic_name"] = $row2["generic_name"];
                            $row["grade"] = $row2["grade"];
                        }
                    }
                    
                    $output1 = array();
                    $sql1 = "SELECT * FROM spec_revision WHERE spec_no='".$row["specification_no"]."'";
                    $result1 = $conn->query($sql1);
                    $output1 = Array();
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $output1[] = $row1;
                        }
                    }
                    $row['revision_history'] = $output1;
                    $output[] = $row;
                }
            }
            echo json_encode($output);
        }
        else if($_GET['type']=='finishObsolate'){
            $output = Array();
            $sql = "SELECT * FROM specification WHERE spec_type='Finish Product' AND status='obsolate'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["material_subtype"] = $row1["material_subtype"];
                            $row["material_name"] = $row1["material_name"];
                            $row["grade"] = $row1["grade"];
                        }
                    }
                    $sql2 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
                    $result2 = $conn->query($sql2);
                    if ($result2->num_rows > 0) {
                        while ($row2 = $result2->fetch_assoc()) {
                            $row["product_name"] = $row2["product_name"];
                            $row["generic_name"] = $row2["generic_name"];
                            $row["grade"] = $row2["grade"];
                        }
                    }
                    $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
                    $result1 = $conn->query($sql1);
                    $output1 = array();
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $sql2 = "SELECT * FROM spec_tests WHERE test='".$row1["test"]."' AND subtest!='' AND specification_no='".$row1["specification_no"]."'";
                            $result2 = $conn->query($sql2);
                            $output2 = Array();
                            if ($result2->num_rows > 0) {
                                while ($row2 = $result2->fetch_assoc()) {
                                    $output2[] = $row2;
                                }
                            }
                            $row1["subtests"] = $output2;
                            $output1[] = $row1;
                        }
                    }
                    $row['spec_tests'] = $output1;
                    
                    $output1 = array();
                    $sql1 = "SELECT * FROM spec_revision WHERE spec_no='".$row["specification_no"]."'";
                    $result1 = $conn->query($sql1);
                    $output1 = Array();
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $output1[] = $row1;
                        }
                    }
                    $row['revision_history'] = $output1;
                    $output[] = $row;
                }
            }
            echo json_encode($output);
        }
        else if($_GET["type"]=='finishMaterial'){
            $output = Array();
            $sql = "SELECT * FROM specification WHERE spec_type='Raw Material Specification' AND status!='obsolate' GROUP by material_code";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["material_subtype"] = $row1["material_subtype"];
                            $row["material_name"] = $row1["material_name"];
                            $row["grade"] = $row1["grade"];
                        }
                    }
                    $output[] = $row;
                }
            }
            echo json_encode($output);
        }
        else if($_GET["type"]=='finishObsolateMaterial'){
            $output = Array();
            $sql = "SELECT * FROM specification WHERE spec_type='Raw Material Specification' AND status='obsolate' GROUP by material_code";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["material_subtype"] = $row1["material_subtype"];
                            $row["material_name"] = $row1["material_name"];
                            $row["grade"] = $row1["grade"];
                        }
                    }
                    $output[] = $row;
                }
            }
            echo json_encode($output);
        }
        
        else if($_GET["type"]=='rawReport'){
            $output = Array();
            if($_GET['fromdate'] != '' && $_GET['material_code'] != ''){
                $sql = "SELECT * FROM specification WHERE spec_type='Raw Material Specification' AND status!='obsolate' AND material_code='".$_GET['material_code']."' AND DATE(entry_date) BETWEEN '".$_GET["fromdate"]."' AND '".$_GET["todate"]."'";
            } else if($_GET['fromdate'] != '' && $_GET['material_code'] == ''){
                $sql = "SELECT * FROM specification WHERE spec_type='Raw Material Specification' AND status!='obsolate' AND DATE(entry_date) BETWEEN '".$_GET["fromdate"]."' AND '".$_GET["todate"]."'";
            } else if($_GET['fromdate'] == '' && $_GET['material_code'] !== ''){
                $sql = "SELECT * FROM specification WHERE spec_type='Raw Material Specification' AND status!='obsolate' AND material_code='".$_GET['material_code']."'";
            }else{
                $sql = "SELECT * FROM specification WHERE spec_type='Raw Material Specification' AND status !='obsolate'";
            }
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["material_subtype"] = $row1["material_subtype"];
                            $row["material_name"] = $row1["material_name"];
                            $row["grade"] = $row1["grade"];
                        }
                    }
                    
                    $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
                    $result1 = $conn->query($sql1);
                    $output1 = array();
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $sql2 = "SELECT * FROM spec_tests WHERE test='".$row1["test"]."' AND subtest!='' AND specification_no='".$row1["specification_no"]."'";
                            $result2 = $conn->query($sql2);
                            $output2 = Array();
                            if ($result2->num_rows > 0) {
                                while ($row2 = $result2->fetch_assoc()) {
                                    $output2[] = $row2;
                                }
                            }
                            $row1["subtests"] = $output2;
                            $output1[] = $row1;
                        }
                    }
                    $row['spec_tests'] = $output1;
                    
                    $output1 = array();
                    $sql1 = "SELECT * FROM spec_revision WHERE spec_no='".$row["specification_no"]."'";
                    $result1 = $conn->query($sql1);
                    $output1 = Array();
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $output1[] = $row1;
                        }
                    }
                    $row['revision_history'] = $output1;
                    $output[] = $row;
                }
            }
            echo json_encode($output);
        }
        else if($_GET["type"]=='rawHistory'){
            $output = Array();
            if($_GET['fromdate'] != '' && $_GET['material_code'] != ''){
                $sql = "SELECT * FROM specification WHERE spec_type='Raw Material Specification' AND status!='obsolate' AND material_code='".$_GET['material_code']."' AND DATE(entry_date) BETWEEN '".$_GET["fromdate"]."' AND '".$_GET["todate"]."'";
            } else if($_GET['fromdate'] != '' && $_GET['material_code'] == ''){
                $sql = "SELECT * FROM specification WHERE spec_type='Raw Material Specification' AND status!='obsolate' AND DATE(entry_date) BETWEEN '".$_GET["fromdate"]."' AND '".$_GET["todate"]."'";
            } else if($_GET['fromdate'] == '' && $_GET['material_code'] !== ''){
                $sql = "SELECT * FROM specification WHERE spec_type='Raw Material Specification' AND status!='obsolate' AND material_code='".$_GET['material_code']."'";
            }else{
                $sql = "SELECT * FROM specification WHERE spec_type='Raw Material Specification' AND status !='obsolate'";
            }
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["material_subtype"] = $row1["material_subtype"];
                            $row["material_name"] = $row1["material_name"];
                            $row["grade"] = $row1["grade"];
                        }
                    }
                    
                    $output1 = array();
                    $sql1 = "SELECT * FROM spec_revision WHERE spec_no='".$row["specification_no"]."'";
                    $result1 = $conn->query($sql1);
                    $output1 = Array();
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $output1[] = $row1;
                        }
                    }
                    $row['revision_history'] = $output1;
                    $output[] = $row;
                }
            }
            echo json_encode($output);
        }
        else if($_GET['type']=='rawObsolate'){
            $output = Array();
            $sql = "SELECT * FROM specification WHERE spec_type='Raw Material Specification' AND status='obsolate'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["material_subtype"] = $row1["material_subtype"];
                            $row["material_name"] = $row1["material_name"];
                            $row["grade"] = $row1["grade"];
                        }
                    }
                    
                    $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
                    $result1 = $conn->query($sql1);
                    $output1 = array();
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $sql2 = "SELECT * FROM spec_tests WHERE test='".$row1["test"]."' AND subtest!='' AND specification_no='".$row1["specification_no"]."'";
                            $result2 = $conn->query($sql2);
                            $output2 = Array();
                            if ($result2->num_rows > 0) {
                                while ($row2 = $result2->fetch_assoc()) {
                                    $output2[] = $row2;
                                }
                            }
                            $row1["subtests"] = $output2;
                            $output1[] = $row1;
                        }
                    }
                    $row['spec_tests'] = $output1;
                    
                    $output1 = array();
                    $sql1 = "SELECT * FROM spec_revision WHERE spec_no='".$row["specification_no"]."'";
                    $result1 = $conn->query($sql1);
                    $output1 = Array();
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $output1[] = $row1;
                        }
                    }
                    $row['revision_history'] = $output1;
                    $output[] = $row;
                }
            }
            echo json_encode($output);
        }
        else if($_GET["type"]=='rawMaterial'){
            $output = Array();
            $sql = "SELECT * FROM specification WHERE spec_type='Raw Material Specification' AND status!='obsolate' GROUP by material_code";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["material_subtype"] = $row1["material_subtype"];
                            $row["material_name"] = $row1["material_name"];
                            $row["grade"] = $row1["grade"];
                        }
                    }
                    $output[] = $row;
                }
            }
            echo json_encode($output);
        }
        else if($_GET["type"]=='rawObsolateMaterial'){
            $output = Array();
            $sql = "SELECT * FROM specification WHERE spec_type='Raw Material Specification' AND status='obsolate' GROUP by material_code";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["material_subtype"] = $row1["material_subtype"];
                            $row["material_name"] = $row1["material_name"];
                            $row["grade"] = $row1["grade"];
                        }
                    }
                    $output[] = $row;
                }
            }
            echo json_encode($output);
        }
        
        else if($_GET["type"]=='retestReport'){
            $output = Array();
            if($_GET['fromdate'] != '' && $_GET['material_code'] != ''){
                $sql = "SELECT * FROM specification WHERE spec_type='Retest Specification' AND status!='obsolate' AND material_code='".$_GET['material_code']."' AND DATE(entry_date) BETWEEN '".$_GET["fromdate"]."' AND '".$_GET["todate"]."'";
            } else if($_GET['fromdate'] != '' && $_GET['material_code'] == ''){
                $sql = "SELECT * FROM specification WHERE spec_type='Retest Specification' AND status!='obsolate' AND DATE(entry_date) BETWEEN '".$_GET["fromdate"]."' AND '".$_GET["todate"]."'";
            } else if($_GET['fromdate'] == '' && $_GET['material_code'] !== ''){
                $sql = "SELECT * FROM specification WHERE spec_type='Retest Specification' AND status!='obsolate' AND material_code='".$_GET['material_code']."'";
            }else{
                $sql = "SELECT * FROM specification WHERE spec_type='Retest Specification' AND status !='obsolate'";
            }
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["material_subtype"] = $row1["material_subtype"];
                            $row["material_name"] = $row1["material_name"];
                            $row["grade"] = $row1["grade"];
                            $row["chemical_name"] = $row1["upac_name"];
                        }
                    }
                    
                    $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
                    $result1 = $conn->query($sql1);
                    $output1 = array();
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $output1[] = $row1;
                        }
                    }
                    $row['spec_tests'] = $output1;
                    
                    $output1 = array();
                    $sql1 = "SELECT * FROM spec_revision WHERE spec_no='".$row["specification_no"]."'";
                    $result1 = $conn->query($sql1);
                    $output1 = Array();
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $output1[] = $row1;
                        }
                    }
                    $row['revision_history'] = $output1;
                    
                    $output[] = $row;
                }
            }
            echo json_encode($output);
        }
        else if($_GET["type"]=='retestHistory'){
            $output = Array();
            if($_GET['fromdate'] != '' && $_GET['material_code'] != ''){
                $sql = "SELECT * FROM specification WHERE spec_type='Retest Specification' AND status!='obsolate' AND material_code='".$_GET['material_code']."' AND DATE(entry_date) BETWEEN '".$_GET["fromdate"]."' AND '".$_GET["todate"]."'";
            } else if($_GET['fromdate'] != '' && $_GET['material_code'] == ''){
                $sql = "SELECT * FROM specification WHERE spec_type='Retest Specification' AND status!='obsolate' AND DATE(entry_date) BETWEEN '".$_GET["fromdate"]."' AND '".$_GET["todate"]."'";
            } else if($_GET['fromdate'] == '' && $_GET['material_code'] !== ''){
                $sql = "SELECT * FROM specification WHERE spec_type='Retest Specification' AND status!='obsolate' AND material_code='".$_GET['material_code']."'";
            }else{
                $sql = "SELECT * FROM specification WHERE spec_type='Retest Specification' AND status !='obsolate'";
            }
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["material_subtype"] = $row1["material_subtype"];
                            $row["material_name"] = $row1["material_name"];
                            $row["grade"] = $row1["grade"];
                            $row["chemical_name"] = $row1["upac_name"];
                        }
                    }
                    
                    $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
                    $result1 = $conn->query($sql1);
                    $output1 = array();
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $output1[] = $row1;
                        }
                    }
                    $row['spec_tests'] = $output1;
                    
                    $output1 = array();
                    $sql1 = "SELECT * FROM spec_revision WHERE spec_no='".$row["specification_no"]."'";
                    $result1 = $conn->query($sql1);
                    $output1 = Array();
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $output1[] = $row1;
                        }
                    }
                    $row['revision_history'] = $output1;
                    
                    $output[] = $row;
                }
            }
            echo json_encode($output);
        }
        else if($_GET['type']=='retestObsolate'){
            $output = Array();
            $sql = "SELECT * FROM specification WHERE spec_type='Retest Specification' AND status='obsolate'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["material_subtype"] = $row1["material_subtype"];
                            $row["material_name"] = $row1["material_name"];
                            $row["grade"] = $row1["grade"];
                        }
                    }
                    $sql2 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
                    $result2 = $conn->query($sql2);
                    if ($result2->num_rows > 0) {
                        while ($row2 = $result2->fetch_assoc()) {
                            $row["product_name"] = $row2["product_name"];
                            $row["generic_name"] = $row2["generic_name"];
                        }
                    }
                    $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
                    $result1 = $conn->query($sql1);
                    $output1 = array();
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $sql2 = "SELECT * FROM spec_tests WHERE test='".$row1["test"]."' AND subtest!='' AND specification_no='".$row1["specification_no"]."'";
                            $result2 = $conn->query($sql2);
                            $output2 = Array();
                            if ($result2->num_rows > 0) {
                                while ($row2 = $result2->fetch_assoc()) {
                                    $output2[] = $row2;
                                }
                            }
                            $row1["subtests"] = $output2;
                            $output1[] = $row1;
                        }
                    }
                    $row['spec_tests'] = $output1;
                    
                    $output1 = array();
                    $sql1 = "SELECT * FROM spec_revision WHERE spec_no='".$row["specification_no"]."'";
                    $result1 = $conn->query($sql1);
                    $output1 = Array();
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $output1[] = $row1;
                        }
                    }
                    $row['revision_history'] = $output1;
                    $output[] = $row;
                }
            }
            echo json_encode($output);
        }
        else if($_GET["type"]=='retestMaterial'){
            $output = Array();
            $sql = "SELECT * FROM specification WHERE spec_type='Raw Material Specification' AND status!='obsolate' GROUP by material_code";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["material_subtype"] = $row1["material_subtype"];
                            $row["material_name"] = $row1["material_name"];
                            $row["grade"] = $row1["grade"];
                        }
                    }
                    $output[] = $row;
                }
            }
            echo json_encode($output);
        }
        else if($_GET["type"]=='retestObsolateMaterial'){
            $output = Array();
            $sql = "SELECT * FROM specification WHERE spec_type='Raw Material Specification' AND status='obsolate' GROUP by material_code";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["material_subtype"] = $row1["material_subtype"];
                            $row["material_name"] = $row1["material_name"];
                            $row["grade"] = $row1["grade"];
                        }
                    }
                    $output[] = $row;
                }
            }
            echo json_encode($output);
        }
        
        else if($_GET["type"]=='stabilityReport'){
            $output = Array();
            $sql = "SELECT * FROM specification WHERE spec_type='Stability Specification'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["material_subtype"] = $row1["material_subtype"];
                            $row["material_name"] = $row1["material_name"];
                            $row["grade"] = $row1["grade"];
                            $row["chemical_name"] = $row1["upac_name"];
                        }
                    }
                    
                    $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
                    $result1 = $conn->query($sql1);
                    $output1 = array();
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $output1[] = $row1;
                        }
                    }
                    $row['spec_tests'] = $output1;
                    $sql2 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
                    $result2 = $conn->query($sql2);
                    if ($result2->num_rows > 0) {
                        while ($row2 = $result2->fetch_assoc()) {
                            $row["product_name"] = $row2["product_name"];
                            $row["generic_name"] = $row2["generic_name"];
                            $row["grade"] = $row2["grade"];
                        }
                    }
                    $output1 = array();
                    $sql1 = "SELECT * FROM spec_revision WHERE spec_no='".$row["specification_no"]."'";
                    $result1 = $conn->query($sql1);
                    $output1 = Array();
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $output1[] = $row1;
                        }
                    }
                    $row['revision_history'] = $output1;
                    
                    $output[] = $row;
                }
            }
            echo json_encode($output);
        }
        else if($_GET["type"]=='stabilityHistory'){
            $output = Array();
            $sql = "SELECT * FROM specification WHERE spec_type='Stability Specification'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["material_subtype"] = $row1["material_subtype"];
                            $row["material_name"] = $row1["material_name"];
                            $row["grade"] = $row1["grade"];
                            $row["chemical_name"] = $row1["upac_name"];
                        }
                    }
                    
                    $sql2 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
                    $result2 = $conn->query($sql2);
                    if ($result2->num_rows > 0) {
                        while ($row2 = $result2->fetch_assoc()) {
                            $row["product_name"] = $row2["product_name"];
                            $row["generic_name"] = $row2["generic_name"];
                            $row["grade"] = $row2["grade"];
                        }
                    }
                    $output1 = array();
                    $sql1 = "SELECT * FROM spec_revision WHERE spec_no='".$row["specification_no"]."'";
                    $result1 = $conn->query($sql1);
                    $output1 = Array();
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $output1[] = $row1;
                        }
                    }
                    $row['revision_history'] = $output1;
                    
                    $output[] = $row;
                }
            }
            echo json_encode($output);
        }
        else if($_GET['type']=='stabilityObsolate'){
            $output = Array();
            $sql = "SELECT * FROM specification WHERE spec_type='Stability Specification' AND status='obsolate'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["material_subtype"] = $row1["material_subtype"];
                            $row["material_name"] = $row1["material_name"];
                            $row["grade"] = $row1["grade"];
                        }
                    }
                    $sql2 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
                    $result2 = $conn->query($sql2);
                    if ($result2->num_rows > 0) {
                        while ($row2 = $result2->fetch_assoc()) {
                            $row["product_name"] = $row2["product_name"];
                            $row["generic_name"] = $row2["generic_name"];
                        }
                    }
                    $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
                    $result1 = $conn->query($sql1);
                    $output1 = array();
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $sql2 = "SELECT * FROM spec_tests WHERE test='".$row1["test"]."' AND subtest!='' AND specification_no='".$row1["specification_no"]."'";
                            $result2 = $conn->query($sql2);
                            $output2 = Array();
                            if ($result2->num_rows > 0) {
                                while ($row2 = $result2->fetch_assoc()) {
                                    $output2[] = $row2;
                                }
                            }
                            $row1["subtests"] = $output2;
                            $output1[] = $row1;
                        }
                    }
                    $row['spec_tests'] = $output1;
                    
                    $output1 = array();
                    $sql1 = "SELECT * FROM spec_revision WHERE spec_no='".$row["specification_no"]."'";
                    $result1 = $conn->query($sql1);
                    $output1 = Array();
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $output1[] = $row1;
                        }
                    }
                    $row['revision_history'] = $output1;
                    $output[] = $row;
                }
            }
            echo json_encode($output);
        }
        else if($_GET["type"]=='stabilityMaterial'){
            $output = Array();
            $sql = "SELECT * FROM specification WHERE spec_type='Raw Material Specification' AND status!='obsolate' GROUP by material_code";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["material_subtype"] = $row1["material_subtype"];
                            $row["material_name"] = $row1["material_name"];
                            $row["grade"] = $row1["grade"];
                        }
                    }
                    $output[] = $row;
                }
            }
            echo json_encode($output);
        }
        else if($_GET["type"]=='stabilityObsolateMaterial'){
            $output = Array();
            $sql = "SELECT * FROM specification WHERE spec_type='Raw Material Specification' AND status='obsolate' GROUP by material_code";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["material_subtype"] = $row1["material_subtype"];
                            $row["material_name"] = $row1["material_name"];
                            $row["grade"] = $row1["grade"];
                        }
                    }
                    $output[] = $row;
                }
            }
            echo json_encode($output);
        }
        
        else if($_GET["type"]=='inprocessReport'){
            $output = Array();
            $sql = "SELECT * FROM specification WHERE spec_type='Inprocess Specification'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["material_subtype"] = $row1["material_subtype"];
                            $row["material_name"] = $row1["material_name"];
                            $row["grade"] = $row1["grade"];
                            $row["chemical_name"] = $row1["upac_name"];
                        }
                    }
                    
                    $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
                    $result1 = $conn->query($sql1);
                    $output1 = array();
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $output1[] = $row1;
                        }
                    }
                    $row['spec_tests'] = $output1;
                    $sql2 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
                    $result2 = $conn->query($sql2);
                    if ($result2->num_rows > 0) {
                        while ($row2 = $result2->fetch_assoc()) {
                            $row["product_name"] = $row2["product_name"];
                            $row["generic_name"] = $row2["generic_name"];
                        }
                    }
                    $output1 = array();
                    $sql1 = "SELECT * FROM spec_revision WHERE spec_no='".$row["specification_no"]."'";
                    $result1 = $conn->query($sql1);
                    $output1 = Array();
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $output1[] = $row1;
                        }
                    }
                    $row['revision_history'] = $output1;
                    
                    $output[] = $row;
                }
            }
            echo json_encode($output);
        }
        else if($_GET["type"]=='inprocessHistory'){
            $output = Array();
            $sql = "SELECT * FROM specification WHERE spec_type='Inprocess Specification'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["material_subtype"] = $row1["material_subtype"];
                            $row["material_name"] = $row1["material_name"];
                            $row["grade"] = $row1["grade"];
                            $row["chemical_name"] = $row1["upac_name"];
                        }
                    }
                    
                    $sql2 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
                    $result2 = $conn->query($sql2);
                    if ($result2->num_rows > 0) {
                        while ($row2 = $result2->fetch_assoc()) {
                            $row["product_name"] = $row2["product_name"];
                            $row["generic_name"] = $row2["generic_name"];
                        }
                    }
                    $output1 = array();
                    $sql1 = "SELECT * FROM spec_revision WHERE spec_no='".$row["specification_no"]."'";
                    $result1 = $conn->query($sql1);
                    $output1 = Array();
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $output1[] = $row1;
                        }
                    }
                    $row['revision_history'] = $output1;
                    
                    $output[] = $row;
                }
            }
            echo json_encode($output);
        }
        else if($_GET['type']=='inprocessObsolate'){
            $output = Array();
            $sql = "SELECT * FROM specification WHERE spec_type='Inprocess Specification' AND status='obsolate'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["material_subtype"] = $row1["material_subtype"];
                            $row["material_name"] = $row1["material_name"];
                            $row["grade"] = $row1["grade"];
                        }
                    }
                    $sql2 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
                    $result2 = $conn->query($sql2);
                    if ($result2->num_rows > 0) {
                        while ($row2 = $result2->fetch_assoc()) {
                            $row["product_name"] = $row2["product_name"];
                            $row["generic_name"] = $row2["generic_name"];
                        }
                    }
                    $sql2 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
                    $result2 = $conn->query($sql2);
                    if ($result2->num_rows > 0) {
                        while ($row2 = $result2->fetch_assoc()) {
                            $row["product_name"] = $row2["product_name"];
                            $row["generic_name"] = $row2["generic_name"];
                        }
                    }
                    $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
                    $result1 = $conn->query($sql1);
                    $output1 = array();
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $sql2 = "SELECT * FROM spec_tests WHERE test='".$row1["test"]."' AND subtest!='' AND specification_no='".$row1["specification_no"]."'";
                            $result2 = $conn->query($sql2);
                            $output2 = Array();
                            if ($result2->num_rows > 0) {
                                while ($row2 = $result2->fetch_assoc()) {
                                    $output2[] = $row2;
                                }
                            }
                            $row1["subtests"] = $output2;
                            $output1[] = $row1;
                        }
                    }
                    $row['spec_tests'] = $output1;
                    
                    $output1 = array();
                    $sql1 = "SELECT * FROM spec_revision WHERE spec_no='".$row["specification_no"]."'";
                    $result1 = $conn->query($sql1);
                    $output1 = Array();
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $output1[] = $row1;
                        }
                    }
                    $row['revision_history'] = $output1;
                    $output[] = $row;
                }
            }
            echo json_encode($output);
        }
        else if($_GET["type"]=='inprocessMaterial'){
            $output = Array();
            $sql = "SELECT product_code FROM specification WHERE spec_type='Inprocess Specification' AND status!='obsolate' GROUP by product_code";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $sql2 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
                    $result2 = $conn->query($sql2);
                    if ($result2->num_rows > 0) {
                        while ($row2 = $result2->fetch_assoc()) {
                            $row["product_name"] = $row2["product_name"];
                        }
                    }
                    $output[] = $row;
                }
            }
            echo json_encode($output);
        }
        else if($_GET["type"]=='inprocessObsolateMaterial'){
            $output = Array();
            $sql = "SELECT * FROM specification WHERE spec_type='Raw Material Specification' AND status='obsolate' GROUP by material_code";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["material_subtype"] = $row1["material_subtype"];
                            $row["material_name"] = $row1["material_name"];
                            $row["grade"] = $row1["grade"];
                        }
                    }
                    $output[] = $row;
                }
            }
            echo json_encode($output);
        }
        
        else if ($_GET["type"] == "getUnitByMaterial") {
            $output = Array();
            if ($_GET["material_nature"] == 'SOLID' || $_GET["material_nature"] == 'SEMI-SOLID') {
                $temp = Array();
                $temp["unit"] = "mg";
                $output[] = $temp;
                
                $temp = Array();
                $temp["unit"] = "gm";
                $output[] = $temp;
            } else if ($_GET["material_nature"] == 'LIQUID') {
                $temp = Array();
                $temp["unit"] = "ml";
                $output[] = $temp;
                
                $temp = Array();
                $temp["unit"] = "ltr";
                $output[] = $temp;
            }
            echo json_encode($output);
        }
    }
?>