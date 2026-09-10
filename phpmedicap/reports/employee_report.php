<?php
    require '../db.php';
    require '../token.php';
    require '../tcpdf/tcpdf.php';
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
    

if($result->num_rows > 0) {
    while($row = $result->fetch_assoc()){
	    $string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
	    $string = explode("$",$string);
	    $_GET["emp_id"] = $string[0];
	    $_GET["department"] = $string[1];
	    break;
    }
}

    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    
    if ($_GET["type"]=="getStock") {
        if (!isset($_GET["material_name"])) {
            $_GET["material_name"] = "";
        }
        $sql = "SELECT s.*, m.material_type, m.material_subtype, m.grade, m.material_name, v.vendor_name FROM stock_book s LEFT JOIN material m ON s.material_code=m.material_code LEFT JOIN vendor v ON s.vendor_no=v.vendor_no WHERE s.user_no='".$_GET["user_no"]."' AND m.material_type='Raw Material' AND s.material_code LIKE '%".$_GET["material_code"]."%' AND s.vendor_no LIKE '%".$_GET["vendor_no"]."' AND s.status LIKE '%".$_GET["status"]."' AND m.material_name LIKE '%".$_GET["material_name"]."%'AND m.material_subtype LIKE '%".$_GET["material_subtype"]."%'AND m.grade LIKE '%".$_GET["grade"]."%' ORDER BY s.grn_no";
    	//echo $sql;
    	$result = $conn->query($sql);
    	$output = Array();
    	if($result->num_rows > 0){
    		while ($row = $result->fetch_assoc()) {
    		    $output[] = $row;
    		}
    	}
    	echo json_encode($output);
    }else if ($_GET["type"]=="getAllStock") {
        if (!isset($_GET["material_name"])) {
            $_GET["material_name"] = "";
        }
        $sql = "SELECT s.*, m.material_type, m.material_subtype, m.grade, m.material_name, v.vendor_name FROM stock_book s LEFT JOIN material m ON s.material_code=m.material_code LEFT JOIN vendor v ON s.vendor_no=v.vendor_no WHERE s.user_no='".$_GET["user_no"]."' AND m.material_type='Raw Material' AND s.material_code LIKE '%".$_GET["material_code"]."%' AND s.vendor_no LIKE '%".$_GET["vendor_no"]."' AND s.status LIKE '%".$_GET["status"]."' AND m.material_name LIKE '%".$_GET["material_name"]."%'";
    	$result = $conn->query($sql);
    	$output = Array();
    	if($result->num_rows > 0){
    		while ($row = $result->fetch_assoc()) {
    		    $output[] = $row;
    		}
    	}
    	echo json_encode($output);
    }if ($_GET["type"]=="getApprovedStock") {
        if (!isset($_GET["material_name"])) {
            $_GET["material_name"] = "";
        }
        $sql = "SELECT s.*, m.material_type, m.material_subtype, m.grade, m.material_name, v.vendor_name FROM stock_book s LEFT JOIN material m ON s.material_code=m.material_code LEFT JOIN vendor v ON s.vendor_no=v.vendor_no WHERE s.user_no='".$_GET["user_no"]."' AND m.material_type='Raw Material' AND s.status='Approved'ORDER BY id DESC";
    	$result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["batches"] = json_decode($row["batches"]);
                $row["receiving_details"] = json_decode($row["receiving_details"]);
                $row["weighing_details"] = json_decode($row["weighing_details"]);
                $row["grn_details"] = json_decode($row["grn_details"]);
                
                $output1 = array();
                $sql1 = "SELECT material_code, grade FROM material WHERE material_name='".$row["material_name"]."' AND material_code!='".$row["material_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row["grades"] = $output1;
                $output[] = $row;
            }
        }
    	echo json_encode($output);
    
    } else if ($_GET["type"] == "Employeeform") {
        $_GET['filename'] = 'Employee List '; $_GET['pdftype']= 'onlyheader';  include('../pdfimp2.php');
       
        
        $html.='
         <h4>Employee Details</h4>
        <table border="0.1" cellpadding="4">
           
                <tr>
                   <td style="width:25%">Employee Name</td>
                   <td style="width:25%"></td>
                   <td style="width:25%">Employee ID</td>
                   <td style="width:25%"></td>
                </tr>
                <tr>
                <td style="width:25%">Email ID</td>
                   <td style="width:25%"></td>
                   <td style="width:25%">Mobaile No.</td>
                   <td style="width:25%"></td>
                
                </tr>
                 <tr>
                <td style="width:25%">Gender</td>
                   <td style="width:25%"></td>
                   <td style="width:25%">Birth Date</td>
                   <td style="width:25%"></td>
                
                </tr>
                <tr>
                <td style="width:25%">Address</td>
                   <td style="width:75%"></td>
                  </tr>
                   <tr>
                <td style="width:25%">Department</td>
                   <td style="width:25%"></td>
                   <td style="width:25%">Designation</td>
                   <td style="width:25%"></td>
                 </tr>
                 <tr>
                <td style="width:25%">Joining Date</td>
                   <td style="width:75%"></td>
                  </tr>
            </table><div></div>
            <h3>Present Address Details:</h3><div></div>
            <table cellpadding="2" border="0.1">
           <tr>
            <td style="width:20%;text-align:center"><b>Flat/House No.</b></td>
             <td style="width:20%;text-align:center"><b>Country</b></td>
              <td style="width:20%;text-align:cenetr"><b>State</b></td>
               <td style="width:20%;text-align:center"><b>City</b></td>
               <td style="width:20%;text-align:center"><b>Postal Code</b></td>
            </tr>
            <tr>
            <td style="width:20%"></td>
             <td style="width:20%"></td>
              <td style="width:20%"></td>
               <td style="width:20%"></td>
                <td style="width:20%"></td>
            </tr>
            <tr>
            <td style="width:20%;"><b>Telephone(R):</b></td>
            <td style="width:40%;"><b>Mobile NO. :</b></td>
            <td style="width:40%;"><b>Emergancy No.</b></td>
            
            </tr>
            </table><div></div>
             <h3>Permanent Address Details:</h3><div></div>
           <table cellpadding="2" border="0.1">
           <tr>
            <td style="width:20%;text-align:center"><b>Flat/House No.</b></td>
             <td style="width:20%;text-align:center"><b>Country</b></td>
              <td style="width:20%;text-align:center"><b>State</b></td>
               <td style="width:20%;text-align:center"><b>City</b></td>
               <td style="width:20%;text-align:center"><b>Postal Code</b></td>
            </tr>
            <tr>
            <td style="width:20%"></td>
             <td style="width:20%"></td>
              <td style="width:20%"></td>
               <td style="width:20%"></td>
                <td style="width:20%"></td>
            </tr>
            <tr>
            <td style="width:20%;"><b>Telephone(R):</b></td>
            <td style="width:40%;"><b>Mobile NO. :</b></td>
            <td style="width:40%;"><b>Emergancy No.</b></td>
            
            </tr>
            </table><div></div>
            <h3>Family Details:</h3>
            <table cellpadding="2" border="0.1">
            <tr>
            <td style="width:20%;text-align:center"><b>Sr no.</b></td>
             <td style="width:20%;text-align:center"><b>Name</b></td>
              <td style="width:20%;text-align:center"><b>Relationship</b></td>
               <td style="width:20%;text-align:center"><b>Occupation</b></td>
               <td style="width:20%;text-align:center"><b>Date of Birth</b></td>
            </tr>
            <tr>
            <td style="width:20%"></td>
              <td style="width:20%"></td>
               <td style="width:20%"></td>
               <td style="width:20%"></td>
               <td style="width:20%"></td>
            </tr>
            </table>
            <h3>Academic Details:</h3>
            <table cellpadding="2" border="0.1">
            <tr>
            <td style="width:19%;text-align:center"><b>Degree/Diploma</b></td>
             <td style="width:15%;text-align:center"><b>Subject of specialization</b></td>
              <td style="width:20%;text-align:center"><b>School/college institute</b></td>
               <td style="width:20%;text-align:center"><b>University/Board</b></td>
               <td style="width:10%;text-align:center"><b>Marks Obtains</b></td>
                <td style="width:8%;text-align:center"><b>From</b></td>
                 <td style="width:8%;text-align:center"><b>To</b></td>
            </tr>
            <tr>
            <td style="width:19%"></td>
              <td style="width:15%"></td>
               <td style="width:20%"></td>
               <td style="width:20%"></td>
               <td style="width:10%"></td>
                <td style="width:8%"></td>
                 <td style="width:8%"></td>
            </tr>
             </table>
             <h3>Languages :</h3>
            <table cellpadding="3" border="0.1">
            <tr>
            <td style="width:25%;text-align:cenetr"><b>Language</b></td>
             <td style="width:25%;text-align:center"><b>Speak</b></td>
              <td style="width:25%;text-align:cenetr"><b>Read</b></td>
               <td style="width:25%;text-align:cenetr"><b>Write</b></td>
              </tr>
            <tr>
            <td style="width:25%"></td>
             <td style="width:25%"></td>
              <td style="width:25%"></td>
               <td style="width:25%"></td>
              </tr></table>
             <h3>Employment Details:</h3>
           <table cellpadding="3" border="0.1">
            <tr>
            <td style="width:15%;text-align:center"><b>Company Name</b></td>
             <td style="width:13%;text-align:center"><b>Position</b></td>
              <td style="width:15%;text-align:center"><b>Duties Performed</b></td>
               <td style="width:15%;text-align:center"><b>Address</b></td>
                 <td style="width:8%;text-align:center"><b>From</b></td>
                 <td style="width:8%;text-align:center"><b>To</b></td>
                  <td style="width:8%;text-align:center"><b>Gross Salary</b></td>
                   <td style="width:8%;text-align:center"><b>Last Salary</b></td>
                    <td style="width:10%;text-align:center"><b>Reason for Leaving</b></td>
            </tr>
            <tr>
            <td style="width:15%"></td>
              <td style="width:13%"></td>
               <td style="width:15%"></td>
               <td style="width:15%"></td>
               <td style="width:8%"></td>
                <td style="width:8%"></td>
                 <td style="width:8%"></td>
                 <td style="width:8%"></td>
                 <td style="width:10%"></td>
                 </tr>
                 </table>
                   <h4>References</h4>
                 <table cellpadding="3" border="0.1">
               <tr>
               <td style="width:100%;text-align:center">Whether Know To Any Person Employed In This Organization</td>
               </tr>
               <tr>
               <td style="width:25%;text-align:cenetr"><b>Name</b></td>
                <td style="width:25%;text-align:cenetr"><b>Designation</b></td>
                 <td style="width:25%;text-align:cenetr"><b>Department</b></td>
                  <td style="width:25%;text-align:cenetr"><b>Relationship</b></td>
               </tr>
               <tr>
               <td style="width:25%;text-align:cenetr"></td>
                <td style="width:25%;text-align:cenetr"></td>
                 <td style="width:25%;text-align:cenetr"></td>
                  <td style="width:25%;text-align:cenetr"></td>
                  </tr>
                   <tr>
               <td style="width:100%;text-align:center">Reference(Other Than Relative)</td>
                </tr>
                <tr>
               <td style="width:25%;text-align:cenetr"><b>Name</b></td>
                <td style="width:25%;text-align:cenetr"><b>Ratnala Present Employment</b></td>
                 <td style="width:25%;text-align:cenetr"><b>Address</b></td>
                  <td style="width:25%;text-align:cenetr"><b>Telephone</b></td>
               </tr>
                <tr>
               <td style="width:25%;text-align:cenetr"></td>
                <td style="width:25%;text-align:cenetr"></td>
                 <td style="width:25%;text-align:cenetr"></td>
                  <td style="width:25%;text-align:cenetr"></td>
                  </tr>
                  </table>
                  <h4>Documents</h4>
                  <table cellpadding="3" border="0.1">
                  <tr>
                  <td style="width:50%;text-align:center"><b>Document Type</b></td>
                   <td style="width:50%;text-align:center"><b>Documents Description</b></td>
                  </tr>
                  <tr>
                  <td style="width:50%;text-align:center"><b></b></td>
                   <td style="width:50%;text-align:center"><b></b></td>
                  </tr>
                  </table>
                  <h4>Last 3 Company Salary Details</h4>
                  <table cellpadding="3" border="0.1">
                  <tr>
               <td style="width:25%;text-align:cenetr"><b>Company Name</b></td>
                <td style="width:25%;text-align:cenetr"><b>Department</b></td>
                 <td style="width:25%;text-align:cenetr"><b>Inhand Salary</b></td>
                  <td style="width:25%;text-align:cenetr"><b>CTC per Annum</b></td>
               </tr>
                <tr>
               <td style="width:25%;text-align:cenetr"></td>
                <td style="width:25%;text-align:cenetr"></td>
                 <td style="width:25%;text-align:cenetr"></td>
                  <td style="width:25%;text-align:cenetr"></td>
                  </tr>
                  </table>
                  <h4>Bank Details</h4>
                  <table cellpadding="3" border="0.1">
                  <tr>
               <td style="width:20%;text-align:cenetr"><b>Account Number</b></td>
                <td style="width:20%;text-align:cenetr"><b>Bank Name</b></td>
                 <td style="width:20%;text-align:cenetr"><b>Brance Name</b></td>
                  <td style="width:20%;text-align:cenetr"><b>IFSC/NEFT Code</b></td>
                  <td style="width:20%;text-align:cenetr"><b>Account Type</b></td>
               </tr>
                <tr>
               <td style="width:20%;text-align:cenetr"></td>
                <td style="width:20%;text-align:cenetr"></td>
                 <td style="width:20%;text-align:cenetr"></td>
                  <td style="width:20%;text-align:cenetr"></td>
                   <td style="width:20%;text-align:cenetr"></td>
                  </tr>
            </table>';
          
           
            

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Employee List.pdf', 'I');
    }
   
    $conn->close();
?>