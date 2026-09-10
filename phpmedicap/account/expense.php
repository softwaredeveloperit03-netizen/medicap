<?php
require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';
header('Access-Control-Allow-Origin: *');
date_default_timezone_set("Asia/Kolkata");
$token = $_GET["token"];
 $currentUrl =$_GET["description"];



$timestamp = time();
$entry_date = date("Y-m-d h:i:s", $timestamp);
$input = json_decode(file_get_contents('php://input'),true);

$sql = "SELECT * FROM token WHERE token='".$_GET["token"]."'";
$result = $conn->query($sql);
$_GET["emp_id"] = "";
$_GET["department"] = "";

if($result->num_rows > 0) {
    while($row = $result->fetch_assoc()){
    	$string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
    	$string = explode("$",$string);
    	$_GET["emp_id"] = $string[0];
    	$_GET["department"] = $string[1];
    	break;
    }
     $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR,frontend_url) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."','".$currentUrl."')";
    $conn->query($sql);
    
    // $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    // $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    
    if($_GET["type"]=="getExpenseCategories") {
        $output = array();
        $sql = "SELECT * FROM expense_categories WHERE user_no='".$_GET["user_no"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "saveExpenseCategory") {
        $sql = "INSERT INTO expense_categories (plant_id,user_no, category) VALUES ('".$_GET["plant_id"]."','".$_GET["user_no"]."', '".$input["category"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "saveExpense") {
        $sql = "INSERT INTO expense (user_no,category, pay_mode, amount, particulars, notes, entry_by, entry_date) VALUES ('".$_GET["user_no"]."', '".$input["category"]."', '".$input["pay_mode"]."', '".$input["amount"]."', '".json_encode($input["particulars"])."', '".$input["note"]."', '".$_GET["emp_id"]."', '$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "getExpenses") {
        $output = array();
        $sql = "SELECT * FROM expense WHERE user_no='".$_GET["user_no"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["particulars"] = json_decode($row["particulars"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getEmployeeMonthSalaryMeha") {
        $output = array();
         $sql = "SELECT * from emp_salary_details where monthYear='".$_GET['month1']."' and credit_date is NULL";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getEmployeeMonthSalaryMehaApproval") {
        $output = array();
         $sql = "SELECT * FROM emp_salary_details WHERE monthYear='".$_GET['month1']."' AND credit_date IS NOT NULL";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getEmployeeMonthSalary") {
        $output = array();
         $sql = "SELECT s.take_home_salary , s.emp_id , e.department,e.middlename,e.designation,e.firstname,e.lastname FROM salary_annexure s left join employee e ON s.emp_id = e.emp_id  where e.plant_id = '".$_GET['plant_id']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "updateExpense") {
        $sql = "UPDATE expense SET status='".$_GET["status"]."', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "save_assets") {
        
         $sql = "UPDATE equipment SET price='".$input["amount"]."', assets_status='1'  WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
        
    }
    else if ($_GET["type"] == "getPendingExpenses") {
        $output = array();
        $sql = "SELECT * FROM expense WHERE user_no='".$_GET["user_no"]."' AND status='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["particulars"] = json_decode($row["particulars"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }if ($_GET["type"] == "downloadExpenses") {
        $_GET['filename'] = 'Expenses Log'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";
        $html.='<table cellpadding="5" border="1">
                <tr>
                    <td style="width:10%; text-align:centre;"><b>Sr.</b></td>
                    <td style="width:25%; text-align:centre;"><b>Date</b></td>
                    <td style="width:25%; text-align:centre;"><b>Category</b></td>
                    <td style="width:20%; text-align:centre;"><b>Pay mode</b></td>
                    <td style="width:20%; text-align:centre;"><b>Amount</b></td>
                </tr>';
            $i=1;
            $sql = "SELECT * FROM expense WHERE user_no='".$_GET["user_no"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
            $html.='<tr>
                    <td style="width:10%;">'.$i.'.</td>
                    <td style="width:25%;">'.date('d-m-Y',strtotime($row['entry_date'])).'</td>
                    <td style="width:25%;">'.$row['category'].'</td>
                    <td style="width:20%;">'.$row['pay_mode'].'</td>
                    <td style="width:20%;">'.$row['amount'].'</td>
                </tr>';
            $i++;
            }
        }
            $html.='</table>';
        
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('downloadExpenses.pdf', 'I');
        
    }
    
    
}

$conn->close();
?>