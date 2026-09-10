 <?php
require '../db.php';
require '../tcpdf/tcpdf.php';
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
date_default_timezone_set("Asia/Kolkata");

try {    
    $timestamp = time();
    $entry_date = date("Y-m-d H:i:s", $timestamp);
    $input = json_decode(file_get_contents('php://input'), true);

    file_put_contents('../logs.txt', json_encode(["process" => "FRONTEND", "action" => $_GET["type"], "actiontime" => $entry_date, "method" => $_SERVER['REQUEST_METHOD'], "REMOTE_ADDR" => $_SERVER['REMOTE_ADDR']]) . PHP_EOL, FILE_APPEND | LOCK_EX);

      if ($_GET["type"] == "getCheckedIndends") {
 
        $output = Array();
        
          $sql = "SELECT max(id) as id,max(indend_no) as indend_no,max(material_type) as material_type,max(no) as no,max(entry_date) as entry_date,
          max(entry_by) as entry_by, max(status) as status, request_no  FROM indend_raw i
        WHERE status ='pending' and i.plant_id='".$_GET["plant_id"]."'  and  i.material_type != 'Raw Material' AND  i.material_type != 'Packing Material' 
        GROUP BY request_no order by id desc";
        
        $result = $conn->query($sql);
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                
                $output8 = array();
                
                  
             $sql8 = "SELECT distinct v.vendor_name, i.*,m.material_name,m.grade  FROM indend_raw i LEFT JOIN my_view m ON i.material_code=m.material_code
            LEFT JOIN vendor v ON i.specific_vendor=v.vendor_no WHERE i.no='".$row["no"]."' and i.status='pending' and (i.material_type!='' or i.material_type!=NULL) limit 20";
            
                
                
                 $result8 = $conn->query($sql8);
                if ($result8->num_rows > 0) {
                    while ($row8 = $result8->fetch_assoc()) {
                        $lowest = array();
                        
                        
            $q= "SELECT GROUP_CONCAT(grade)  as grade FROM    grade where id in ('".  $row8['grade']."')";
            $resQ = $conn->query($q);
            $prodLatest = $resQ->fetch_assoc();
            $row8['grade'] = $prodLatest['grade']; 
                        
                        
                        $highest = array();
        
                        $lowest["rate"] = 0;
                        $highest["rate"] = 0;
                        $sql1 = "SELECT * FROM challan_materials WHERE user_no='".$_GET["user_no"]."' AND material_code='".$row8["material_code"]."'"; //ORDER BY id DESC";
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                $sql2 = "SELECT c.*, v.vendor_name FROM challan c LEFT JOIN vendor v ON c.vendor_no=v.vendor_no WHERE c.user_no='".$_GET["user_no"]."' AND c.challan_no='".$row1["challan_no"]."'";
                                $result2 = $conn->query($sql2);
                                if ($result2->num_rows > 0) {
                                    while ($row2 = $result2->fetch_assoc()) {
                                        $row["last_purchase"] = $row2["vendor_no"];
                                        
                                        $flag = 0;
                                        if ($lowest["rate"] < $lowest["rate"]) {
                                            $flag = 1;
                                        }
                                        if ($flag == 0) {
                                            $lowest["rate"] = $row1["rate"];
                                            $lowest["vendor_name"] = $row2["vendor_name"];
                                            $lowest["vendor_no"] = $row2["vendor_no"];
                                            $lowest["challan_no"] = $row1["challan_no"];
                                            $lowest["challan_date"] = $row2["challan_date"];
                                        }
                                        
                                        $flag = 0;
                                        if ($highest["rate"] < $highest["rate"]) {
                                            $flag = 1;
                                        }
                                        if ($flag == 0) {
                                            $highest["rate"] = $row1["rate"];
                                            $highest["vendor_name"] = $row2["vendor_name"];
                                            $highest["vendor_no"] = $row2["vendor_no"];
                                            $highest["challan_no"] = $row1["challan_no"];
                                            $highest["challan_date"] = $row2["challan_date"];
                                        }
                                    }
                                }
                            }
                        } else {
                            $row8["last_purchase"] = "";
                        }
                        
                        $row8["lowest"] = $lowest;
                        $row8["highest"] = $highest;
                        
                        $output1 = Array();
                       // $sql1 = "SELECT q.*, v.vendor_name FROM quotation q LEFT JOIN vendor v ON q.vendor_no=v.vendor_no WHERE q.user_no='".$_GET["user_no"]."' AND q.materials LIKE '%".$row8["material_code"]."%' AND q.status='approve'";
                        $sql1 = "SELECT a.id,a.quotation_no,c.vendor_no,c.vendor_name,b.gst_per,
                        b.material_code, b.pack_size,b.quotation_type,b.quotation_amt,b.quotation_per from quotation_dtl b
                        JOIN quotation_hdr a on b.quotation_hdr_id = a.id LEFT JOIN vendor c on a.vendor_id = c.id 
                         where a.status='approve' and b.material_code = '".$row8["material_code"]."' ";    
                        // echo $sql1;
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                
                                if ($row1["vendor_no"] == $row["expected_vendor"]) {
                                    $row1["last_purchase"] = "yes";
                                } else {
                                    $row1["last_purchase"] = "no";
                                }
                                $output1[] = $row1;
                                 
                            }
                        }
                      
                        
                        $row8["vendors"] = $output1;
                        
                        $output8[] = $row8;
                    }
                }
                $row["materials"] = $output8;
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    
     else if ($_GET["type"] == "getIndentByplantHead") {
 
        $output = Array();
        
          $sql = "SELECT max(id) as id,max(indend_no) as indend_no,max(material_type) as material_type,max(no) as no,max(entry_date) as entry_date,
          max(entry_by) as entry_by, max(status) as status, request_no  FROM indend_raw i
        WHERE   i.plant_id='".$_GET["plant_id"]."'  
        GROUP BY request_no order by id desc";
        
        $result = $conn->query($sql);
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                
               
            $output8 = array();
            $sql8 = "SELECT distinct v.vendor_name, i.*,m.material_name,m.grade  FROM indend_raw i LEFT JOIN my_view m ON i.material_code=m.material_code
            LEFT JOIN vendor v ON i.specific_vendor=v.vendor_no WHERE i.no='".$row["no"]."' and  (i.material_type!='' or i.material_type!=NULL) limit 20";
                
                 $result8 = $conn->query($sql8);
                if ($result8->num_rows > 0) {
                    while ($row8 = $result8->fetch_assoc()) {
                        
                        $output8[] = $row8;
                    }
                }
                $row["materials"] = $output8;
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    
    else if ($_GET["type"] == "getIndentForApprovalPlantHead") {
 
        $output = Array();
        
          $sql = "SELECT max(id) as id,max(indend_no) as indend_no,max(material_type) as material_type,max(no) as no,max(entry_date) as entry_date ,
          max(entry_by) as entry_by, max(status) as status,max(purpose) as plant_head_remark,max(dept_head_remark) as dept_head_remark, request_no  FROM indend_raw i
        WHERE   i.plant_id='".$_GET["plant_id"]."'   AND status = '".$_GET["status"]."'
        GROUP BY request_no order by id desc";
        
        $result = $conn->query($sql);
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                
            $output8 = array();
            $sql8 = "SELECT distinct v.vendor_name, i.*,m.material_name,m.grade  FROM indend_raw i LEFT JOIN my_view m ON i.material_code=m.material_code
            LEFT JOIN vendor v ON i.specific_vendor=v.vendor_no WHERE i.no='".$row["no"]."' AND i.status = '".$_GET["status"]."' and  (i.material_type!='' or i.material_type!=NULL) limit 20";
                
                 $result8 = $conn->query($sql8);
                if ($result8->num_rows > 0) {
                    while ($row8 = $result8->fetch_assoc()) {
                        
            $q= "SELECT GROUP_CONCAT(grade)  as grade FROM    grade where id in ('".  $row8['grade']."')";
            $resQ = $conn->query($q);
            $prodLatest = $resQ->fetch_assoc();
            $row8['grade'] = $prodLatest['grade']; 
                        
                        $output8[] = $row8;
                    }
                }
                $row["materials"] = $output8;
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    
      else if ($_GET["type"] == "approvePOAndriod") {
           $sql = "UPDATE indend_raw SET status='".$_GET["status"]."', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date' WHERE id='".$_GET["id"]."'";
           if ($conn->query($sql)) {
           echo "{\"status\":\"success\"}";
            } else {
           echo "{\"status\":\"".$conn->error."\"}";
           }
        }
        
         else if ($_GET["type"] == "directorApprovePOAndriod") {
           $sql = "UPDATE indend_raw SET status='".$_GET["status"]."', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date' WHERE id='".$_GET["id"]."'";
           if ($conn->query($sql)) {
           echo "{\"status\":\"success\"}";
            } else {
           echo "{\"status\":\"".$conn->error."\"}";
           }
        }
       
  

} catch (\Throwable $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}

$conn->close();
?>
