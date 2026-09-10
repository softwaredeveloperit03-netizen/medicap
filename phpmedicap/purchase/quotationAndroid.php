<?php
 
    require '../db.php';
    require '../tcpdf/tcpdf.php';
    header('Access-Control-Allow-Origin: *');
    date_default_timezone_set("Asia/Kolkata");

    try {    
      //  $timestamp = time();
        // $entry_date = date("Y-m-d h:i:s", $timestamp);
        $input = json_decode(file_get_contents('php://input'), true);

        // Logging the request
        $txt = '{"process": "FRONTEND", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
        file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    
         if ($_GET["type"] == "getPendingQuotations") {
            $output = [];
    
            $sql = "SELECT q.*, v.vendor_name, v.email, v.city, v.gst_no 
                    FROM quotation_hdr q 
                    LEFT JOIN vendor v ON q.vendor_id = v.id 
                    WHERE q.status='pending' AND q.plant_id = '".$_GET["plant_id"]."' 
                    ORDER BY q.id DESC";
    
            $result = $conn->query($sql);
            while ($row = $result->fetch_assoc()) {
                $output1 = [];
    
                $sql1 = "SELECT DISTINCT q.id, q.*, v.material_name, v.material_type, v.material_subtype, ve.vendor_no  
                         FROM quotation_dtl q 
                         JOIN quotation_hdr qh ON q.quotation_hdr_id = qh.id 
                         LEFT JOIN my_view v ON qh.plant_id = v.plant_id AND q.material_code = v.material_code
                         LEFT JOIN vendor ve ON ve.id = qh.vendor_id 
                         WHERE q.quotation_hdr_id = '".$row['id']."'";
    
                $result1 = $conn->query($sql1);
                while ($row1 = $result1->fetch_assoc()) {
                    $output1[] = $row1;
                }
    
                $row["materials"] = $output1;
                $output[] = $row;
            }
    
            echo json_encode($output);
        }
        
        else if ($_GET["type"] == "updateQuotation") {
           $sql = "UPDATE quotation_hdr SET status='".$_GET["status"]."', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date' WHERE id='".$_GET["id"]."'";
           if ($conn->query($sql)) {
           echo "{\"status\":\"success\"}";
            } else {
           echo "{\"status\":\"".$conn->error."\"}";
           }
        }
        
        
        
        
        
    } catch (\Throwable $e) {
        echo "{\"status\":\"".$e->getMessage()."\"}";
    }

    $conn->close();
?>
