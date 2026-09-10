<?php


// ini_set('display_errors', 1);
// error_reporting(E_ALL);




    require '../db.php';
    
    
    
    
    
    
function getGrdeValue($grade , $conn){
    
            if ($grade == 'NA') {
                $grd = [0]; // Default value as an array containing 0
            } else {
                $grd = $grade;
            }
            
            // Ensure $grd is properly formatted as a comma-separated list
            if (!is_array($grd)) {
                $grd = explode(',', $grd); // Convert to an array if it is a string
            }
            
            // Validate $grd to contain only integers
            $grd = array_filter($grd, function($value) {
                return is_numeric($value) && intval($value) > 0; // Allow only positive integers
            });
            
            // Convert back to a comma-separated string for SQL
            $grdList = implode(',', $grd);
            
            if (!empty($grdList)) {
                // Only execute the query if $grdList is not empty
                $q = "SELECT GROUP_CONCAT(grade) as gradeName FROM grade WHERE id IN ($grdList)";
               // echo $q; // Debugging: Display the query
                
                $resQ = $conn->query($q);
                if ($resQ) {
                    $prodLatest = $resQ->fetch_assoc();
                    $gradeName = $prodLatest['gradeName'];
                } else {
                    // Handle SQL query errors
                    echo "SQL Error: " . $conn->error;
                }
            } else {
                // Handle case where $grdList is empty
                $gradeName = "NA"; // Set a default value or handle it appropriately
              //  echo "No valid grades to fetch.";
            }     
            
            
            return $gradeName;
            
            
}
    
    
     
 
                    $sql = "SELECT  s.*,s.total_containers as containers, m.material_type, m.material_subtype, m.grade,c.grn_date,c.receiving_no,
                         m.material_name,m.storage_condition AS storageCondition,DATE(c.receiving_date) as receiving_date 
                         FROM sampling_batches s  
                         LEFT JOIN material m ON s.material_code = m.material_code  
                         left join  challan_materials c ON c.grn_no = s.grn_no   AND s.material_code = c.material_code
                         LEFT JOIN challan c1 ON c.challan_no = c1.challan_no
                         WHERE s.challan_no='".$_GET['challan_no']."' AND s.batch_no='".$_GET['batch_no']."' AND s.material_code='".$_GET['material_code']."' AND s.plant_id='".$_GET['plant_id']."' ";  
                        
                           $result = $conn->query($sql);
                           if ($result->num_rows > 0) {
                             $row = $result->fetch_assoc();
                            $row['gradeName'] = getGrdeValue($row['grade'] , $conn);
                             
                           }



             $sql1 = "SELECT  * FROM stock_book WHERE batch_no='".$row['batch_no']."' AND material_code='".$row['material_code']."' 
             AND plant_id='".$_GET['plant_id']."'  AND grn_no='".$row['grn_no']."' ";  
                        
                           $result1 = $conn->query($sql1);
                           if ($result1->num_rows > 0) {
                             $row1 = $result1->fetch_assoc();
                           }
                           
             $sql12 = "SELECT vendor_name,vendor_no FROM vendor  WHERE  plant_id='".$_GET['plant_id']."'  AND vendor_no='".$row['mfg_by']."' ";  
                        
                           $result12 = $conn->query($sql12);
                           if ($result12->num_rows > 0) {
                             $row12 = $result12->fetch_assoc();
                           }
                           
                           
             $sql123 = "SELECT * , DATE(check_date) as sampDate FROM sampling   WHERE  batch_no='".$row['batch_no']."' AND material_code='".$row['material_code']."' 
             AND plant_id='".$_GET['plant_id']."'  AND grn_no='".$row['grn_no']."' ";  
                        
                           $result123 = $conn->query($sql123);
                           if ($result123->num_rows > 0) {
                             $sampData = $result123->fetch_assoc();
                           }
                           
                           
                           
             $sql1234 = "SELECT * , DATE(approve_date) as releseDate FROM testing   WHERE  sampling_no='".$sampData['sampling_no']."' AND material_code='".$sampData['material_code']."' 
             AND plant_id='".$_GET['plant_id']."'   ";  
                        
                           $result1234 = $conn->query($sql1234);
                           if ($result1234->num_rows > 0) {
                             $testData = $result1234->fetch_assoc();
                           }

  


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paperless GMP By Cyclone Pharma</title>
    <style>
        /* General Styling */
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #ff9a9e, #fad0c4);
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            animation: fadeIn 0.8s ease-in-out;
        }

        .container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 5px 15px rgba(0, 0, 0, 0.15);
            width: 90%;
            max-width: 600px;
            text-align: center;
            animation: slideIn 0.8s ease-in-out;
        }

        h1 {
            color: #444;
            font-size: 22px;
            margin-bottom: 15px;
            text-transform: uppercase;
            font-weight: 600;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .table td, .table th {
            padding: 8px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }

        .table th {
            font-weight: 600;
            color: #333;
        }

        .table td span {
            background: #f0f0f0;
            padding: 5px 8px;
            border-radius: 5px;
            display: inline-block;
        }

        /* Dynamic Status Colors */
        <?php
            $statusColor = "#008000"; // Default Green (In Stock)

            if (strcasecmp($row1['status'], "quarantine") == 0) {
                $statusColor = "#FF0000"; // Red
            } elseif (strcasecmp($row1['status'], "Under Test") == 0) {
                $statusColor = "#FFA500"; // Orange
            } elseif (strcasecmp($row1['status'], "Approved") == 0) {
                $statusColor = "#0000FF"; // Blue
            }
        ?>
        #status {
            background: <?php echo $statusColor; ?>;
            color: white;
            padding: 6px 12px;
            font-weight: bold;
            border-radius: 5px;
            animation: pulse 1.5s infinite alternate;
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideIn {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            100% { transform: scale(1.05); }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Material Status</h1>
        <table class="table">
            <tr><th>Material Name:</th><td><span><?php echo $row['material_name']; ?></span></td></tr>
            <tr><th>Material Code:</th><td><span><?php echo $row['material_code']; ?></span></td></tr>
            <tr><th>Material Grade:</th><td><span><?php echo $row['gradeName']; ?></span></td></tr>
            <tr><th>Challan No:</th><td><span><?php echo $row['ch_no']; ?></span></td></tr>
            <tr><th>Vendor Name:</th><td><span><?php echo $row12['vendor_name']; ?></span></td></tr>
            <tr><th>Batch No.:</th><td><span><?php echo $row['batch_no']; ?></span></td></tr>
            <tr><th>Mfg. / Exp. Date:</th><td><span><?php echo $row['mfg_date']." / ".$row['exp_date']; ?></span></td></tr>
            <tr><th>Receiving No:</th><td><span><?php echo $row['receiving_no']; ?></span></td></tr>
            <tr><th>Receiving Date:</th><td><span><?php echo $row['receiving_date']; ?></span></td></tr>
            <tr><th>GRN No:</th><td><span><?php echo $row['grn_no']; ?></span></td></tr>
            <tr><th>GRN Date:</th><td><span><?php echo $row['grn_date']; ?></span></td></tr>
            <tr><th>Sampling No.:</th><td><span><?php echo $sampData['sampling_no']; ?></span></td></tr>
            <tr><th>Sampling Date:</th><td><span><?php echo $sampData['sampDate']; ?></span></td></tr>
            <tr><th>Testing No:</th><td><span><?php echo $testData['testing_no']; ?></span></td></tr>
            <tr><th>Medicap lot no:</th><td><span><?php echo $testData['ar_no']; ?></span></td></tr>
            <tr><th>Relese Date:</th><td><span><?php echo $testData['releseDate']; ?></span></td></tr>
            <tr><th>Status:</th><td><span id="status"><?php echo $row1['status']; ?></span></td></tr>
        </table>
    </div>
</body>
</html>
