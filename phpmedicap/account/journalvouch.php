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
    
    
       if ($_GET["type"] == "savevouchev") {
        $sql = "INSERT INTO vouchev(narration,created_by) VALUES ( '".$input["narration"]."','".$_GET["user_no"]."')"; 
           
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
       }
       else if ($_GET["type"] == "update_vouchev") {
        $sql = "UPDATE vouchev SET narration='".$input["narration"]."' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    
    }
       
   else if ($_GET["type"] == "delete_vouchev") {
        $sql = "update  vouchev set status=0  WHERE id='".$_GET["id"]."'";
         if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
 else if ($_GET["type"] == "get_vouchev") {
        $output = array();
        $sql = "SELECT * FROM vouchev";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["particulars"] = json_decode($row["particulars"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
     }
     
      if ($_GET["type"] == "savejournalvouch") {
        $sql = "INSERT INTO journal_vouch(jv_no,dr_total,cr_total,narration,created_by) VALUES ('".$input["jv_no"]."','".$input["dr_total"]."',
        '".$input["cr_total"]."','".$input["narration"]."','".$_GET["user_no"]."')"; 
           
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
       }
       else if ($_GET["type"] == "update_journal_vouch") {
        $sql = "UPDATE journal_vouch SET narration='".$input["narration"]."' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    
    }
       
   else if ($_GET["type"] == "delete_journal_vouch") {
        $sql = "update  journal_vouch set status=1 WHERE id='".$_GET["id"]."'";
         if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
 else if ($_GET["type"] == "get_journal_vouch") {
        $output = array();
        $sql = "SELECT * FROM journal_vouch";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["particulars"] = json_decode($row["particulars"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
     }
     
     if ($_GET["type"] == "saveju_drk") {
        $sql = "INSERT INTO ju_drk( debit,credit,narration) VALUES ('".$input["debit"]."','".$input["credit"]."', '".$_GET["user_no"]."')"; 
           
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
    
        
    } 
    else if ($_GET["type"] == "update_ju_drk") {
        $sql = "UPDATE ju_drk SET narration='".$input["narration"]."'WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    
    } 
 else if ($_GET["type"] == "delete_ju_drk") {
        $sql = "update ju_drk set status=1  WHERE id='".$_GET["id"]."'";
         if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
     else if ($_GET["type"] == "get_ju_drk") {
        $output = array();
        $sql = "SELECT * FROM ju_drk";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["particulars"] = json_decode($row["particulars"]);
                $output[] = $row;
            }
        }
        
         echo json_encode($output);
    }else if ($_GET["type"] == "downloadvouchev") {
        $_GET['filename'] = 'Journal vouch'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp.php");
          $html="";
           $sql1 = "SELECT  * FROM  journal_vouch join  ju_drk on journal_vouch.jv_no= ju_drk.jv_id ";
           $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
             while ($row1 = $result1->fetch_assoc()) {
           $html.='<table>
          <tr><br><br>
         <td style="width:10%;text-align:right;"><b>Jv No. :  '.$row1['jv_no'].'</b></td>
        
            <td style="width:60%;text-align:right;"><b>Aff</b></td>
            </tr>';
          $html.='</table><br><br><br>';
    
          $html.='<table cellpadding="5" border="0.1">
           <tr>
        <th style="width:10%;text-align:center;"><b>S/No.</b></th>
         <th style="width:50%;text-align:center;"><b>Ac Hed</b></th>
         <th style="width:20%;text-align:center;"><b>Dr</b></th>
           <th style="width:20%;text-align:center;"><b>Cr</b></th>
          </tr>';
         $sql = "SELECT * FROM ju_drk WHERE jv_id=account_code";
           $result = $conn->query($sql);
            $i=1;
            if ($result->num_rows > 0) {
               while ($row = $result->fetch_assoc()) {
                   $html.='<tr >
             <td style="width:10%;text-align:center;">'.$i.'.</td>
             <td style="width:50%;text-align:center;">'.$row1[''].'</td>
             <td style="width:20%;text-align:center;">'.$row['debit'].'</td>
              <td style="width:20%;text-align:center;">'.$row['credit'].'</td>
              </tr>';
              $i++;
                
             }
            }
              
             $sql = "SELECT  * FROM  journal_vouch join  ju_drk on journal_vouch.jv_no= ju_drk.jv_id ";
           $result = $conn->query($sql);
            if ($result->num_rows > 0) {
             while ($row = $result->fetch_assoc()) {
                
           $html.= '<tr>
          
           <td></td><td col align=’center’><strong>Total:
       </strong></td><td style="text-align:center;">'.$row['dr_total'].'</td>
       <td  style="text-align:center;" >'.$row['cr_total'].'</td>
             </tr>';
            $html.='<tr>
            
         <td></td><td colspan="6"><strong>Rupees:
         </strong><b style="width:25%;" >Five hundred only</b></td>
         
        </tr>
            
         </table><br><br><br><br>';
            
        $html.='<table cellpadding="5" border="0.1">
        <tr>
        <td style="width:35%; text-align:center;"><b> Prepared by</b></td>
         <td style="width:30%; text-align:center;"><b>Approved by</b></td>
        <td style="width:35%; text-align:center;"><b>Signed by</b></td>
        </tr>
        <tr>
        <td style="width:35%; text-align:center;"><b></b></td>
         <td style="width:30%; text-align:center;"><b></b></td>
        <td style="width:35%; text-align:center;"><b></b></td>
        </tr>
          
       </table>';
           
             }
            }
    }
            }
             
         $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('journal.pdf','I');
       }
        }

$conn->close();

?>