<?php
require 'db.php';
require 'token.php';
// ini_set('display_errors', 1);
// error_reporting(E_ALL);
$token = $_GET["token"];
$sql = "SELECT * FROM token WHERE token='".$_GET["token"]."'";
$result = $conn->query($sql);
$_GET["emp_id"] = "";
$_GET["department"] = "";
if($result->num_rows > 0){
while($row = $result->fetch_assoc()) {
	$string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
	$string = explode("$",$string);
	$_GET["emp_id"] = $string[0];
	$_GET["department"] = $string[1];
	break;
}

$sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."')";
$conn->query($sql);
    $output  = Array();

if ($_GET["type"] == "getDosages") {
    $output = Array();
    $sql = "SELECT * FROM dosage_form";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $ouptut1 = Array();
            $sql1 = "SELECT * FROM product WHERE user_no='".$_GET["user_no"]."' AND dosage_form='".$row["dosage_form"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $output1[] = $row1;
                }
            }
            $row["products"] = $output1;
            $output[] = $row;
        }
    }
    echo json_encode($output);
    
} else if ($_GET["type"] == "saveDosages") {
    $sql = "INSERT INTO dosage_form (type,dosage_form, product_code, checkpoints, entry_by, entry_date) VALUES ('".$input["type"]."','".$input["dosage_form"]."', '".$input["product_code"]."', '".json_encode($input["checkpoints"])."', '".$_GET["emp_id"]."', '$entry_date')";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\": \"failed\"}";
    }
} else if ($_GET["type"] == "getProducts") {
    $output = Array();
    $sql = "SELECT product_code, product_name, grade FROM product WHERE status='approve'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
    
}
// else if ($_GET["type"] == "saveChecklist") {
//     $sql = "INSERT INTO batch_checklist (dosage_form,dosage_type, product_code,  entry_by, entry_date,plant_id) 
//     VALUES ('".$input["dosage_form"]."','".$input["dosage_type"]."', '".$input["product_code"]."',
//     '".$_GET["emp_id"]."', '$entry_date', '".$_GET["plant_id"]."')";
   
//  if ($conn->query($sql)) {
    	
//         $last_id = $conn->insert_id;

//         foreach($input['checkpoints'] as $input){
    		
//     	$sql="INSERT INTO mst_batch_checklist (chklist_id ,checkpoints)
//                                 value(".$last_id.",
//                                       '".$input["checkpoint"]."')";
//     	 $conn->query($sql);
//         }
    
    
//          echo "{\"status\":\"success\"}";	
//     	} else {
//         echo "{\"status\":\"failed\"}";
//     }
// }



else if ($_GET["type"] == "saveChecklist") {
    $dosage_form = $input["dosage_form"];
    $dosage_type = $input["dosage_type"];
    $product_code = $input["product_code"];
    $emp_id = $_GET["emp_id"];
    $entry_date = $entry_date; 
    $plant_id = $_GET["plant_id"];

    $sql = "INSERT INTO batch_checklist (dosage_form, dosage_type, product_code, entry_by, entry_date, plant_id) 
            VALUES ('$dosage_form', '$dosage_type', '$product_code', '$emp_id', '$entry_date', '$plant_id')";

    if ($conn->query($sql)) {
        $last_id = $conn->insert_id;

        foreach ($input['checkpoints'] as $checkpoint) {
               $sql = "INSERT INTO mst_batch_checklist (chklist_id, checkpoints)
                    VALUES ('$last_id', '$checkpoint')";

            $conn->query($sql);
        }

        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
}


else if ($_GET["type"] == "getChecklists") {
    $output = Array();
    $sql = "SELECT * FROM batch_checklist";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
             $sql2 = "SELECT * FROM mst_batch_checklist WHERE chklist_id='".$row["id"]."'";
            $result2 = $conn->query($sql2);

            // Initialize an array to store checkpoints data
            $checkpoint2 = array();

            if ($result2->num_rows > 0) {
                while ($row2 = $result2->fetch_assoc()) {
                  
                    $checkpoints[] = $row2["checkpoints"];
                }
            }

            // Add checkpoints array to the main row
            $row["checkpoints"] = $checkpoints;
            
            
            $sql1 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."' and status='approve'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["product_name"] = $row1["product_name"];
                    $row["grade"] = $row1["grade"];
                }
            }

            // $row["checkpoints"] = json_decode($row["checkpoints"]);
            $output[] = $row;
        }
    }
    echo json_encode($output);
} 
// else if ($_GET["type"] == "getPendingChecklists") {
//     $output = Array();
//     $sql = "SELECT * FROM batch_checklist WHERE status='pending'";
//     // $sql = "SELECT a.*, b.checkpoints,b.chklist_id FROM batch_checklist a LEFT JOIN mst_batch_checklist b ON a.id = b.chklist_id WHERE a.status = 'pending'";
//     $result = $conn->query($sql);
//     if ($result->num_rows > 0) {
//         while ($row = $result->fetch_assoc()) {
//             $sql1 = "SELECT * FROM mst_batch_checklist WHERE chklist_id='".$row["id"]."'";
//             $result1 = $conn->query($sql1);
//             if ($result1->num_rows > 0) {
//                 while ($row1 = $result1->fetch_assoc()) {
//                     $row["product_name"] = $row1["product_name"];
//                     $row["grade"] = $row1["grade"];
//                 }
//             }

//             $row["checkpoints"] = json_decode($row["checkpoints"]);
//              $output[] = $row;
//         }
//     }
//     echo json_encode($output);
// } 


else if ($_GET["type"] == "getPendingChecklists") {
    $output = Array();
    $sql = "SELECT * FROM batch_checklist WHERE status='pending'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM mst_batch_checklist WHERE chklist_id='".$row["id"]."'";
            $result1 = $conn->query($sql1);

            // Initialize an array to store checkpoints data
            $checkpoints = array();

            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    // Assuming "checkpoints" is a column in the mst_batch_checklist table
                    // Append checkpoint data to the array
                    $checkpoints[] = $row1["checkpoints"];
                }
            }

            // Add checkpoints array to the main row
            $row["checkpoints"] = $checkpoints;
            $output[] = $row;
        }
    }

    echo json_encode($output);
}

else if ($_GET["type"] == "updateChecklist") {
    $sql = "UPDATE batch_checklist SET status='".$_GET["status"]."', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date' WHERE id='".$_GET["id"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\": \"failed\"}";
    }
} else if ($_GET["type"] == "getPendingBatchRelease") {
    
    
    
        $output = Array();
      
                 $sql="SELECT s.*, DATE(s.prepared_date) as prepared_date, p.product_name, p.grade, p.product_type,p.dosage_form FROM samplingfg s LEFT JOIN product p ON s.product_code=p.product_code WHERE  s.status='COAA Aproved' ";


         
          
        $result = $conn->query($sql);
         
       
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                   $row["ar_no"] = $row["arno"];
                $output1 = Array();
               $sql1 = "SELECT * FROM samplingfg WHERE product_code='".$row["product_code"]."'";
                     

                 
                
                $result2 = $conn->query($sql1);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {

                   $row["chemical_analysis"] = $row2["chemical_analysis"];
                        $row["microbiology_analysis"] = $row2["microbiology_analysis"];
                        $row["reserve_analysis"] = $row2["reserve_analysis"];
                        $row["sample_qty"] = $row2["total_qty"];
                    }
                }

                $output2 =  Array();
                //  $sql3 = "SELECT * ,t.result as t_result FROM testing_tests t where specification_no='".$row["specification_no"]."' and status='inprocess' ";
                 $sql3 = "SELECT * FROM batch_checklist WHERE dosage_form='".$row["dosage_form"]."' ";
                $result3 = $conn->query($sql3);
                if ($result3->num_rows > 0) {
                    while ($row3 = $result3->fetch_assoc()) {
                        $row3["checkpoints"] = json_decode($row3["checkpoints"]);
                        $output2[] = $row3;
                       
                       
                    }
                }

                $row["checklist"] = $output2;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    
    
    
    
    
//     $output = Array();
//     $sql = "SELECT * FROM dosage_form";
//     $result = $conn->query($sql);
//     if ($result->num_rows > 0) {
//         while ($row = $result->fetch_assoc()) {
//             $output1 = Array();
//             $sql1 = "SELECT * FROM finish_product WHERE product_code IN (SELECT product_code FROM product WHERE dosage_form='".$row["dosage_form"]."')";
//             $result1 = $conn->query($sql1);
//             if ($result1->num_rows > 0) {
//                 while ($row1 = $result1->fetch_assoc()) {
//                     $sql2 = "SELECT * FROM product WHERE product_code='".$row1["product_code"]."'";
//                     $result2 = $conn->query($sql2);
//                     if ($result2->num_rows > 0) {
//                         while ($row2 = $result2->fetch_assoc()) {
//                             $row1["product_name"] = $row2["product_name"];
//                             $row1["grade"] = $row2["grade"];
//                         }
//                     }

//                     $sql2 = "SELECT * FROM batch_checklist WHERE product_code='".$row1["product_code"]."'";
//                     $result2 = $conn->query($sql2);
//                     if ($result2->num_rows > 0) {
//                         while ($row2 = $result2->fetch_assoc()) {
//                             $checkpoints = json_decode($row2["checkpoints"]);
//                             $output2 = Array();
//                             for ($i = 0; $i < count($checkpoints); $i++) {
//                                 $temp = Array();
//                                 $temp["checkpoint"] = $checkpoints[$i];
//                                 $temp["status"] = "";
//                                 $temp["remark"] = "";
//                                 $output2[] = $temp;
//                             }
//                             $row1["checklist"] = $output2;
//                             break;
//                         }
//                     }
//                     $output1[] = $row1;
//                 }
//             }
//             $row["batches"] = $output1;
//             $output[] = $row;
//         }
//     }
//     echo json_encode($output);
} else if ($_GET["type"] == "saveBatchRelease") {
  echo  $sql = "INSERT INTO batch_release (product_code, batch_no, qty, unit, mfg_date, exp_date, checkpoints, entry_by, entry_date) VALUES ('".$_GET["product_code"]."', '".$input["batch_no"]."', '".$input["qty"]."', '".$input["pack_unit"]."', '".$input["mfg_date"]."', '".$input["exp_date"]."', '".json_encode($input["checkpoints"])."', '".$_GET["emp_id"]."', '$entry_date')";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if($_GET["type"] == "getbatchrelease") {
    $output = Array();
    $sql = "SELECT * FROM batch_release where status='approve'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["product_name"] = $row1["product_name"];
                    $row["grade"] = $row1["grade"];
                }
            }

            $row["checkpoints"] = json_decode($row["checkpoints"]);
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if($_GET["type"] == "getBatchReleaseLog") {
    $output = Array();
    $sql = "SELECT * FROM batch_release WHERE product_code LIKE '%".$_GET["product_code"]."%' AND DATE(entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["product_name"] = $row1["product_name"];
                    $row["grade"] = $row1["grade"];
                }
            }

            $row["checkpoints"] = json_decode($row["checkpoints"]);
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if($_GET["type"] == "getCheckingBatchRelease") {
    $output = Array();
    $sql = "SELECT * FROM batch_release WHERE status='pending'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["product_name"] = $row1["product_name"];
                    $row["grade"] = $row1["grade"];
                }
            }

            $row["checkpoints"] = json_decode($row["checkpoints"]);
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "updateBatchRelease") {
    $sql = "UPDATE batch_release SET status='".$_GET["status"]."', check_by='".$_GET["emp_id"]."', check_date='$entry_date' WHERE id='".$_GET["id"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "getChecklistRecord") {
    $sql = "SELECT * FROM batch_checklist WHERE id='".$_GET["id"]."'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["product_name"] = $row1["product_name"];
                    $row["grade"] = $row1["grade"];
                }
            }

            $row["checkpoints"] = json_decode($row["checkpoints"]);
            echo json_encode($row);
            break;
        }
    } else {
        echo "{}";
    }
} else if ($_GET["type"] == "reviseChecklist") {
    $sql = "UPDATE batch_checklist SET status='revised' WHERE id='".$input["id"]."'";
    if ($conn->query($sql)) {
        $sql = "INSERT INTO batch_checklist (dosage_form, product_code, checkpoints, entry_by, entry_date) VALUES ('".$input["dosage_form"]."', '".$input["product_code"]."', '".json_encode($input["checkpoints"])."', '".$_GET["emp_id"]."', '$entry_date')";
        $conn->query($sql);
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
}
else if($_GET['type'] == 'getchartsdata'){
    $output = Array();
    $series = Array();
    $lables = Array();
    $sql = "SELECT COUNT(id) as total, product_code  FROM batch_release GROUP BY product_code";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
            $result1 = $conn->query($sql1);
            $row1 = $result1->fetch_assoc();
            
            $lables[] = $row1["product_name"];
            $series[] = +$row["total"];
        }
    }
    $output["series"] = $series;
    $output["lables"] = $lables;
    echo json_encode($output);
}
} else {
    echo "[]";
}

$conn->close();
?>