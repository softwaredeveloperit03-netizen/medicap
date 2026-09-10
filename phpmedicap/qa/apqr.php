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
                $sql1 = "SELECT material_code, grade FROM material WHERE material_name='".$row["material_name"]."' 
                AND material_code!='".$row["material_code"]."'";
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
    
    } else if ($_GET["type"] == "getProducts") {
        $output = Array();
        $sql = "SELECT * FROM product WHERE plant_id='".$_GET["plant_id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        
        echo json_encode($output);
        
        
        
    
   } else if ($_GET["type"] == "Annexure1training") {
        $_GET['filename'] = ' '; $_GET['pdftype']= 'onlyheader';  include('../pdfimp2.php');
         $html.='
          <tr>
         <td style="width:100%;"><b>Name of Department    : _____________________________________</b></td> 
         </tr><br>
          <tr>
         <td style="width:100%"><b>Year   : ______________________</b></td>
         </tr><div></div>
         <tr>
         <td style="width:100%;"><b>Details of Training needs:</b></td>
         </tr><div></div>
         <table cellpadding="5" border="0.1">
         <tr>
         <td style="width:10%;text-align:center"><b>Sr. No</b></td>
          <td style="width:20%;text-align:center"><b>Training subject</b></td>
           <td style="width:20%;text-align:center"><b>Modul</b></td>
            <td style="width:20%;text-align:center"><b>Training month</b></td>
             <td style="width:30%;text-align:center"><b>Name of persons to be Trained</b></td>
         </tr>
         <tr>
         <td style="width:10%;text-align:center"></td>
          <td style="width:20%;text-align:center"></td>
           <td style="width:20%;text-align:center"></td>
            <td style="width:20%;text-align:center"></td>
             <td style="width:30%;text-align:center"></td>
         </tr>
          <tr>
         <td style="width:10%;text-align:center"></td>
          <td style="width:20%;text-align:center"></td>
           <td style="width:20%;text-align:center"></td>
            <td style="width:20%;text-align:center"></td>
             <td style="width:30%;text-align:center"></td>
         </tr>
          <tr>
         <td style="width:10%;text-align:center"></td>
          <td style="width:20%;text-align:center"></td>
           <td style="width:20%;text-align:center"></td>
            <td style="width:20%;text-align:center"></td>
             <td style="width:30%;text-align:center"></td>
         </tr>
          <tr>
         <td style="width:10%;text-align:center"></td>
          <td style="width:20%;text-align:center"></td>
           <td style="width:20%;text-align:center"></td>
            <td style="width:20%;text-align:center"></td>
             <td style="width:30%;text-align:center"></td>
         </tr>
         </table>
        <br pagebreak="true"/> ';
        $html.='
        <table cellpadding="3" border="0.1">
        <tr>
        <td style="width:100%"><b>Annexurell : Training Schedule</b></td>
        </tr>
        </table><div></div>
        <table cellpadding="2"border="0.1">
       <tr>
        <td style="text-align:cenetr"><b>DEPARTMENT</b></td>
       </tr>
        </table>
         <table cellpadding="4" border="0.1">
         <tr>
        <td style="width:5%;text-align:center"><b>SL NO.</b></td>
        <td style="width:10%;text-align:center"><b>TRANING</b></td>
        <td style="width:10%;text-align:center"><b>FACULTY</b></td>
        <td style="width:10%;text-align:center"><b>JOB RESPONSIBILITY</b></td>
         <td style="width:10%;text-align:center"><b>MONTH</b></td>
         <td style="width:10%;text-align:center"><b>MONTH</b></td>
         <td style="width:10%;text-align:center"><b>MONTH</b></td>
         <td style="width:10%;text-align:center"><b>MONTH</b></td>
         <td style="width:10%;text-align:center"><b>MONTH</b></td>
         <td style="width:10%;text-align:center"><b>MONTH</b></td>
        </tr>
        </table>
       <br pagebreak="true"/> ';
        $html.='
        <table cellpadding= "5" border="0.1" style="width:100%">
       <tr>
       <tr>
        <td style="width:100%;text-align:center"><b>Training Announcement</b></td>
       </tr>
       </tr>
       <tr>
       <tr>
        <td style="width:20%;text-align:center">Training Date :</td>
       </tr>
       </tr>
        <tr>
       <tr>
        <td style="width:18%;text-align:center">Department:</td>
       </tr>
       </tr>
       <tr>
        <tr>
        <td style="width:22%;text-align:center">Name of Trainer  :</td>
       </tr>
       </tr>
        <tr>
        <tr>
        <td style="width:15%;text-align:center">Subject :</td>
       </tr>
       </tr>
       <tr>
        <tr>
        <td style="width:24%;text-align:center">Timing of Training :</td>
       </tr>
       </tr>
       <tr>
        <tr>
        <td style="width:24%;text-align:center">Venue of Training : </td>
       </tr>
       </tr>
        </table><br>
        <table cellpadding="5" border="0.1">
        <tr>
        <td style="width:20%;text-align:center"><b>Name of Participant </b></td>
         <td style="width:20%;text-align:center"><b>Department </b></td>
          <td style="width:10%;text-align:center"><b>Sign/Date </b></td>
           <td style="width:20%;text-align:center"><b>Name of Participant </b></td>
            <td style="width:20%;text-align:center"><b>Department</b></td>
             <td style="width:10%;text-align:center"><b>Sign/Date </b></td>
        </tr>
        <tr>
        <td style="width:20%;text-align:center"></td>
         <td style="width:20%;text-align:center"></td>
          <td style="width:10%;text-align:center"></td>
           <td style="width:20%;text-align:center"></td>
            <td style="width:20%;text-align:center"></td>
             <td style="width:10%;text-align:center"></td>
        </tr>
        <tr>
        <td style="width:20%;text-align:center"></td>
         <td style="width:20%;text-align:center"></td>
          <td style="width:10%;text-align:center"></td>
           <td style="width:20%;text-align:center"></td>
            <td style="width:20%;text-align:center"></td>
             <td style="width:10%;text-align:center"></td>
        </tr>
        </table><div></div>
        <tr>
        <td style="width:40%;text-align:cenetr">Sign/ Date:  ____________________</td>
        </tr><div></div>
         <tr>
        <td style="width:20%;text-align:cenetr">Head QA                   </td>
        </tr>
        
          <br pagebreak="true"/> ';
        $html.='
        <h3>Employee Details</h3>
        <tr>
        <td style="width:20%;">Name	</td><td style="width:10%;">:</td><td style="width:70%;"> _____________________________________	</td>
        </tr><div></div> 
        <tr>
        <td style="width:20%;">Designation</td> <td style="width:10%;">:</td><td style="width:70%;"> _____________________________________	</td>
        </tr><div></div> 
        <tr>
        <td style="width:20%;">Qualification</td> <td style="width:10%;">:</td><td style="width:70%;"> _____________________________________	</td>
        </tr> <div></div>
        <tr>
        <td style="width:20%;">Total period of Industrial experience</td><td style="width:10%;">:</td><td style="width:70%;"> _____________________________________	</td>
        </tr><div></div> 
        <tr>
        <td style="width:100%;">Details of Previous Experience:  	</td>
        </tr> 
        <table cellpadding="5" border="0.1">
        <tr>
        <td style="width:10%;text-align:center"><b>Sr. No.</b></td>
         <td style="width:30%;text-align:center"><b>Name of the Company </b></td>
          <td style="width:20%;text-align:center"><b>Designation</b></td>
           <td style="width:20%;text-align:center"><b>No. of Years</b></td>
            <td style="width:20%;text-align:center"><b>Job Responsibilities</b></td>
        </tr>
        <tr>
        <td style="width:10%;text-align:center"></td>
         <td style="width:30%;text-align:center"></td>
          <td style="width:20%;text-align:center"></td>
           <td style="width:20%;text-align:center"></td>
            <td style="width:20%;text-align:center"></td>
        </tr>
         <tr>
        <td style="width:10%;text-align:center"></td>
         <td style="width:30%;text-align:center"></td>
          <td style="width:20%;text-align:center"></td>
           <td style="width:20%;text-align:center"></td>
            <td style="width:20%;text-align:center"></td>
        </tr>
         <tr>
        <td style="width:10%;text-align:center"></td>
         <td style="width:30%;text-align:center"></td>
          <td style="width:20%;text-align:center"></td>
           <td style="width:20%;text-align:center"></td>
            <td style="width:20%;text-align:center"></td>
        </tr>
        </table><div></div>
        <tr>
        <td style="width:20%">Sign & Date</td> <td style="width:10%">:</td><td style="width:50%">____________</td>
        </tr>
         <tr>
        <td style="width:20%"> New Employee </td> <td style="width:10%">:</td><td style="width:50%">____________</td>
        </tr>
        <tr>
        <td style="width:20%"> Contact Number </td> <td style="width:10%">:</td><td style="width:50%">____________</td>
        </tr>';
        $html.='
        <h3>Staff Detail:</h3>
        <tr>
        <td style="width:50%">From : Human Resource Dept.</td>
        <td style="width:50%"> Date	: ____________     </td>
        </tr>
        <tr>
        <td style="width:100%">  ________________ has joined our organization as _____________ from __________ </td>
        </tr><div></div>
        <tr>
        <td style="width:100%"> All Head of Departments are requested to introduce him/her to the activities in your department </td>
        </tr><div></div>
         <tr>
        <td style="width:100%"> Induction Training schedule:  </td>
        </tr><div></div>
        <table cellpadding="5" border="0.1">
        <tr>
        <td style="width:10%;text-align:center"><b>Sr. No.</b></td>
        <td style="width:20%;text-align:center"><b>Department</b></td>
        <td style="width:20%;text-align:center"><b>Date</b></td>
        <td style="width:30%;text-align:center"><b>Name of HOD / Sign</b></td>
        <td style="width:20%;text-align:center"><b>Remark ( if any)</b></td>
        </tr>
        <tr>
        <td style="width:10%;text-align:center"><b></b></td>
        <td style="width:20%;text-align:center"><b></b></td>
        <td style="width:20%;text-align:center"><b></b></td>
        <td style="width:30%;text-align:center"><b></b></td>
        <td style="width:20%;text-align:center"><b></b></td>
        </tr>
         <tr>
        <td style="width:10%;text-align:center"><b></b></td>
        <td style="width:20%;text-align:center"><b></b></td>
        <td style="width:20%;text-align:center"><b></b></td>
        <td style="width:30%;text-align:center"><b></b></td>
        <td style="width:20%;text-align:center"><b></b></td>
        </tr>
        </table>
        <tr>
        <td style="width:100%">* For Head Office employees and all factory Managers /Asst. Managers</td>
        </tr><div></div>
        <tr>
        <td style="width:100%"><b> 3)   Induction Summary Report:  (To Be Prepared By Trainee)</b></td>
        </tr><div></div><div></div><div></div><div></div><div></div>
        <tr>
        <td style="width:100%">Attach Separate Sheet If required </td>
        </tr><div></div>
        <tr>
        <td style="width:20%">Sign & Date  </td><td style="width:40%">___________   </td><td style="width:40%">___________   </td>
        </tr>
        <tr>
        <td style="width:20%"></td><td style="width:40%"> New Recruit</td><td style="width:40%">HR Personnel</td>
        </tr>
        <br pagebreak="true"/> ';
        $html.='
        <tr>
        <td style="width:30%">Name of Manager /Executive</td> <td style="width:10%"> :</td>
        </tr><div></div>
        <tr>
        <td style="width:30%">Department</td><td style="width:10%"> :</td>
        </tr><div></div>
        <tr>
        <td style="width:30%">Date of Joining </td><td style="width:10%"> :</td>
        </tr><div></div>
        <td style="width:100%">I hereby Declare and certify that I have read bellow mentioned Standard Operating procedures and understood the same </td>
        </tr>
        <table cellpadding="5" border="0.1">
        <tr>
        <td style="width:10%;text-align:center"><b>Date</b></td>
        <td style="width:20%;text-align:center"><b>SOP No</b></td>
        <td style="width:30%;text-align:center"><b>Title of SOP</b></td>
        <td style="width:20%;text-align:center"><b>Department</b></td>
        <td style="width:10%;text-align:center"><b>Date / Sign</b></td>
        <td style="width:10%;text-align:center"><b>Remarks</b></td>
        </tr>
        <tr>
        <td style="width:10%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:30%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:10%;text-align:center"></td>
        <td style="width:10%;text-align:center"></td>
        </tr>
        <tr>
        <td style="width:10%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:30%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:10%;text-align:center"></td>
        <td style="width:10%;text-align:center"></td>
        </tr>
        <tr>
        <td style="width:10%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:30%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:10%;text-align:center"></td>
        <td style="width:10%;text-align:center"></td>
        </tr>
        </table><div></div>
        <tr>
        <td style="width:20%"><b>QA Heads Remark </b></td> <td style="width:10%">:</td>
        </tr><div></div>
         <tr>
        <td style="width:20%"><b>Sign/ Date </b></td><td style="width:10%">:</td>
        </tr><div></div>
         <tr>
        <td style="width:20%"><b>Head QA </b></td><td style="width:10%">:</td>
        </tr>
        <br pagebreak="true"/> ';
        $html.='
        <tr>
        <td style="width:50%">Name of Employee:</td>
        <td style="width:50%">Designation:</td>
        </tr><div></div>
        <tr>
        <td style="width:50%">Job responsibilities to be assigned:</td>
        <td style="width:50%">Training Schedule Year :</td>
        </tr>
        <table cellpadding="5" border="0.1">
        <tr>
        <td style="width:8%;text-align:center"><b>Sr.No  </b></td>
        <td style="width:15%;text-align:center"><b>Training subject</b></td>
        <td style="width:25%;text-align:center"><b>SOP/Demonstration</b></td>
        <td style="width:12%;text-align:center"><b>Scheduled Date</b></td>
        <td style="width:10%;text-align:center"><b>Date of Training </b></td>
        <td style="width:10%;text-align:center"><b>Trainees Signature</b></td>
        <td style="width:20%;text-align:center"><b>Trainers Name/Signature </b></td>
        </tr>
        <tr>
        <td style="width:8%;text-align:center"></td>
        <td style="width:15%;text-align:center"></td>
        <td style="width:25%;text-align:center"></td>
        <td style="width:12%;text-align:center"></td>
        <td style="width:10%;text-align:center"></td>
        <td style="width:10%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        </tr>
         <tr>
        <td style="width:8%;text-align:center"></td>
        <td style="width:15%;text-align:center"></td>
        <td style="width:25%;text-align:center"></td>
        <td style="width:12%;text-align:center"></td>
        <td style="width:10%;text-align:center"></td>
        <td style="width:10%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        </tr>
         <tr>
        <td style="width:8%;text-align:center"></td>
        <td style="width:15%;text-align:center"></td>
        <td style="width:25%;text-align:center"></td>
        <td style="width:12%;text-align:center"></td>
        <td style="width:10%;text-align:center"></td>
        <td style="width:10%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        </tr>
        <tr>
        <td style="width:8%;text-align:center"></td>
        <td style="width:15%;text-align:center"></td>
        <td style="width:25%;text-align:center"></td>
        <td style="width:12%;text-align:center"></td>
        <td style="width:10%;text-align:center"></td>
        <td style="width:10%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        </tr>
        <tr>
        <td style="width:8%;text-align:center"></td>
        <td style="width:15%;text-align:center"></td>
        <td style="width:25%;text-align:center"></td>
        <td style="width:12%;text-align:center"></td>
        <td style="width:10%;text-align:center"></td>
        <td style="width:10%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        </tr>
        </table>
         <br pagebreak="true"/> ';
        $html.='
        
        <tr>
        <td style="width:20%">Subject of Training</td><td style="width:10%">:</td><td style="width:50%"></td>
       </tr><div></div>
         <tr>
        <td style="width:20%">Date of Training</td><td style="width:10%">:</td><td style="width:50%"></td>
        </tr><div></div>
        <tr>
        <td style="width:20%">Department</td><td style="width:10%">:</td><td style="width:50%"></td>
        </tr><div></div>
        <tr>
        <td style="width:20%">Training given by</td><td style="width:10%">:</td><td style="width:50%"></td>
        </tr><div></div>
         <tr>
        <td style="width:20%">Time Of Training </td><td style="width:10%">:</td><td style="width:50%"></td>
        </tr><div></div>
       <tr>
        <td style="width:20%">Duration of Training</td><td style="width:10%">:</td><td style="width:50%"></td>
       </tr><div></div>
         <tr>
        <td style="width:20%">Training Subject </td><td style="width:10%">:</td><td style="width:50%"></td>
        </tr><div></div>
        <tr>
        <td style="width:20%">Topics Covered </td><td style="width:10%">:</td><td style="width:50%"></td>
        </tr><div></div>
        <table cellpadding="5" border="0.1">
        <tr>
        <td style="width:10%;text-align:center"><b>Sr. No.</b></td>
        <td style="width:20%;text-align:center"><b>Name of the Trainee</b></td>
        <td style="width:20%;text-align:center"><b>Department</b></td>
        <td style="width:20%;text-align:center"><b>Name of Trainee</b></td>
        <td style="width:20%;text-align:center"><b>Department </b></td>
        <td style="width:10%;text-align:center"><b>Remarks</b></td>
        </tr>
         <tr>
        <td style="width:10%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:10%;text-align:center"></td>
        </tr>
         <tr>
        <td style="width:10%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:10%;text-align:center"></td>
        </tr>
         <tr>
        <td style="width:10%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:10%;text-align:center"></td>
        </tr>
         <tr>
        <td style="width:10%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:10%;text-align:center"></td>
        </tr>
        </table><div></div>
        <tr>
        <td style="width:50%"><b>Trainer:</b></td>
        <td style="width:50%"><b>Remark: </b></td>
        </tr><div></div>
         <tr>
        <td style="width:10%"><b>Sign/ Date:</b></td>
        <td style="width:50%">____________________  </td>
        </tr>
        <tr>
        <td style="width:10%"></td>
        <td style="width:50%"><b>Trainer</b></td>
        </tr>
        <br pagebreak="true"/> ';
        $html.='
        
        <tr>
        <td style="width:20%">Name of Employee </td><td style="width:10%">:</td><td style="width:50%"></td>
       </tr><div></div>
         <tr>
        <td style="width:20%">Department</td><td style="width:10%">:</td><td style="width:50%"></td>
        </tr><div></div>
        <tr>
        <td style="width:20%">Date of Joining </td><td style="width:10%">:</td><td style="width:50%"></td>
        </tr><div></div>
        <tr>
        <td style="width:100%">Above mentioned Employee has read the following Standard operating procedures</td>
        </tr><div></div>
        <table cellpadding="5" border="0.1">
        <tr>
        <td style="width:10%;text-align:center"><b>Date</b></td>
        <td style="width:20%;text-align:center"><b>SOP No</b></td>
        <td style="width:20%;text-align:center"><b>Title of SOP</b></td>
        <td style="width:20%;text-align:center"><b>Department</b></td>
        <td style="width:20%;text-align:center"><b>Date / Sign </b></td>
        <td style="width:10%;text-align:center"><b>Remarks</b></td>
        </tr>
         <tr>
        <td style="width:10%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:10%;text-align:center"></td>
        </tr>
         <tr>
        <td style="width:10%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:10%;text-align:center"></td>
        </tr>
         <tr>
        <td style="width:10%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:10%;text-align:center"></td>
        </tr>
         <tr>
        <td style="width:10%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:10%;text-align:center"></td>
        </tr>
        </table><div></div>
        <tr>
        <td style="width:50%"><b>Department Heads  Remark: </b></td>
        
        </tr><div></div>
         <tr>
        <td style="width:10%"><b>Sign/ Date:</b></td>
        <td style="width:50%">____________________  </td>
        </tr>
        <tr>
        <td style="width:10%"></td>
        <td style="width:50%"><b>Department Head </b></td>
        </tr>
         <br pagebreak="true"/> ';
        $html.='
        
        <tr>
        <td style="width:20%">Name of Department </td><td style="width:10%">:</td><td style="width:50%"></td>
       </tr><div></div>
         <tr>
        <td style="width:20%">Year</td><td style="width:10%">:</td><td style="width:50%"></td>
        </tr><div></div>
       
        <tr>
        <td style="width:100%">Details of Re-Training needs:  Due to less than 80% mark in training evaluation questioners</td>
        </tr><div></div>
        <table cellpadding="5" border="0.1">
        <tr>
        <td style="width:10%;text-align:center"><b>Training subject </b></td>
        <td style="width:20%;text-align:center"><b>Training month</b></td>
        <td style="width:20%;text-align:center"><b>Name of persons to be trained</b></td>
        <td style="width:20%;text-align:center"><b>Trainer name</b></td>
        <td style="width:18%;text-align:center"><b>Before retraining marks (&lt;80%) </b></td>
        <td style="width:12%;text-align:center"><b>Date of Retraining </b></td>
        </tr>
         <tr>
        <td style="width:10%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:18%;text-align:center"></td>
        <td style="width:12%;text-align:center"></td>
        </tr>
         <tr>
        <td style="width:10%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:18%;text-align:center"></td>
        <td style="width:12%;text-align:center"></td>
        </tr>
         <tr>
        <td style="width:10%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:18%;text-align:center"></td>
        <td style="width:12%;text-align:center"></td>
        </tr>
         <tr>
        <td style="width:10%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:18%;text-align:center"></td>
        <td style="width:12%;text-align:center"></td>
        </tr>
        </table><div></div>
        <br pagebreak="true"/> ';
        $html.='
        
        <tr>
        <td style="width:20%">Training Date  </td><td style="width:10%">:</td><td style="width:50%"></td>
       </tr><div></div>
         <tr>
        <td style="width:20%">Department</td><td style="width:10%">:</td><td style="width:50%"></td>
        </tr><div></div>
        <tr>
        <td style="width:20%">Name of Trainer  </td><td style="width:10%">:</td><td style="width:50%"></td>
        </tr><div></div>
        <tr>
        <td style="width:20%">Subject Covered  </td><td style="width:10%">:</td><td style="width:50%"></td>
        </tr><div></div>
       <tr>
        <td style="width:20%">No of Participants   </td><td style="width:10%">:</td><td style="width:50%"></td>
        </tr><div></div>
         <tr>
        <td style="width:20%">Duration of Training    </td><td style="width:10%">:</td><td style="width:50%"></td>
        </tr><div></div>
         <tr>
        <td style="width:20%">No of Participants   </td><td style="width:10%">:</td><td style="width:50%"></td>
        </tr><div></div>
        
        <table cellpadding="5" border="0.1">
        <tr>
        <td style="width:10%;text-align:center"><b>Date</b></td>
        <td style="width:20%;text-align:center"><b>SOP No</b></td>
        <td style="width:20%;text-align:center"><b>Title of SOP</b></td>
        <td style="width:20%;text-align:center"><b>Department</b></td>
        <td style="width:20%;text-align:center"><b>Date / Sign </b></td>
        <td style="width:10%;text-align:center"><b>Remarks</b></td>
        </tr>
         <tr>
        <td style="width:10%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:10%;text-align:center"></td>
        </tr>
         <tr>
        <td style="width:10%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:10%;text-align:center"></td>
        </tr>
         <tr>
        <td style="width:10%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:10%;text-align:center"></td>
        </tr>
         <tr>
        <td style="width:10%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:10%;text-align:center"></td>
        </tr>
        </table><div></div>
        <tr>
        <td style="width:50%"><b>Department Heads  Remark: </b></td>
        
        </tr><div></div>
         <tr>
        <td style="width:10%"><b>Sign/ Date:</b></td>
        <td style="width:50%">____________________  </td>
        </tr>
        <tr>
        <td style="width:10%"></td>
        <td style="width:50%"><b>Department Head </b></td>
        </tr>
         <br pagebreak="true"/> ';
        $html.='
        
        <tr>
        <td style="width:20%">Name of Department </td><td style="width:10%">:</td><td style="width:50%"></td>
       </tr><div></div>
         <tr>
        <td style="width:20%">Year</td><td style="width:10%">:</td><td style="width:50%"></td>
        </tr><div></div>
       
        <tr>
        <td style="width:100%">Details of Re-Training needs:  Due to less than 80% mark in training evaluation questioners</td>
        </tr><div></div>
        <table cellpadding="5" border="0.1">
        <tr>
        <td style="width:10%;text-align:center"><b>Training subject </b></td>
        <td style="width:20%;text-align:center"><b>Training month</b></td>
        <td style="width:20%;text-align:center"><b>Name of persons to be trained</b></td>
        <td style="width:20%;text-align:center"><b>Trainer name</b></td>
        <td style="width:18%;text-align:center"><b>Before retraining marks (&lt;80%) </b></td>
        <td style="width:12%;text-align:center"><b>Date of Retraining </b></td>
        </tr>
         <tr>
        <td style="width:10%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:18%;text-align:center"></td>
        <td style="width:12%;text-align:center"></td>
        </tr>
         <tr>
        <td style="width:10%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:18%;text-align:center"></td>
        <td style="width:12%;text-align:center"></td>
        </tr>
         <tr>
        <td style="width:10%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:18%;text-align:center"></td>
        <td style="width:12%;text-align:center"></td>
        </tr>
         <tr>
        <td style="width:10%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:18%;text-align:center"></td>
        <td style="width:12%;text-align:center"></td>
        </tr>
        </table><div></div>
        <br pagebreak="true"/> ';
        $html.='
        
        <tr>
        <td style="width:20%">Training Date  </td><td style="width:10%">:</td><td style="width:50%"></td>
       </tr><div></div>
         <tr>
        <td style="width:20%">Department</td><td style="width:10%">:</td><td style="width:50%"></td>
        </tr><div></div>
        
        <tr>
        <td style="width:20%">Subject Covered   </td><td style="width:10%">:</td><td style="width:50%"></td>
        </tr><div></div>
        <tr>
        <td style="width:20%">No of Participants    </td><td style="width:10%">:</td><td style="width:50%"></td>
        </tr><div></div>
       <tr>
        <td style="width:20%">Duration of Training     </td><td style="width:10%">:</td><td style="width:50%"></td>
        </tr><div></div>
         <tr>
        <td style="width:20%">Feedback from Participant     </td><td style="width:10%">:</td><td style="width:50%"></td>
        </tr><div></div><div></div>
         
        <table cellpadding="5" border="0.1">
        <tr>
        <td style="width:20%;text-align:center"><b>Name of Participant  </b></td>
        <td style="width:30%;text-align:center"><b>Feedback </b></td>
        <td style="width:30%;text-align:center"><b>Suggestion for Improvement </b></td>
        <td style="width:20%;text-align:center"><b>Sign/Date </b></td>
        </tr>
        <tr>
        <td style="width:20%;text-align:center"></td>
        <td style="width:30%;text-align:center"></td>
        <td style="width:30%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        </tr>
          <tr>
        <td style="width:20%;text-align:center"></td>
        <td style="width:30%;text-align:center"></td>
        <td style="width:30%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        </tr>
         <tr>
        <td style="width:20%;text-align:center"></td>
        <td style="width:30%;text-align:center"></td>
        <td style="width:30%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        </tr>
          <tr>
        <td style="width:20%;text-align:center"></td>
        <td style="width:30%;text-align:center"></td>
        <td style="width:30%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        </tr>
        </table><div></div>
         <tr>
        <td style="width:50%"><b>QA Heads Remark: </b></td>
        
        </tr><div></div>
         <tr>
        <td style="width:10%"><b>Sign/ Date:</b></td>
        <td style="width:50%">____________________  </td>
        </tr>
        <tr>
        <td style="width:10%"></td>
        <td style="width:50%"><b>Head QA</b></td>
        </tr>
         <br pagebreak="true"/> ';
        $html.='
        
        <tr>
        <td style="width:20%">Department </td><td style="width:10%">:</td><td style="width:50%"></td>
       </tr><div></div>
         <tr>
        <td style="width:20%">Date</td><td style="width:10%">:</td><td style="width:50%"></td>
        </tr><div></div>
         <tr>
        <td style="width:20%">Time</td><td style="width:10%">:</td><td style="width:50%"></td>
        </tr><div></div>
         <tr>
        <td style="width:20%">Hr</td><td style="width:10%">:</td><td style="width:50%"></td>
        </tr><div></div>
       <tr>
        <td style="width:20%">Name of trainee</td><td style="width:10%">:</td><td style="width:20%"></td>
         <td style="width:20%">Designation</td><td style="width:10%">:</td><td style="width:20%"></td>
        </tr><div></div>
        <tr>
        <td style="width:20%">Training subject</td><td style="width:10%">:</td><td style="width:50%"></td>
         </tr><div></div>
         <tr>
        <td style="width:20%">Evaluators Name</td><td style="width:10%">:</td><td style="width:20%"></td>
         <td style="width:20%">Duration</td><td style="width:10%">:</td><td style="width:20%"></td>
        </tr><div></div>
         <tr>
        <td style="width:20%">Total Marks</td><td style="width:10%">:</td><td style="width:20%"></td>
         <td style="width:20%">Marks Scored</td><td style="width:10%">:</td><td style="width:20%"></td>
        </tr><div></div>
        <tr>
        <td style="width:20%">Evaluators Assessment</td><td style="width:10%">:</td><td style="width:20%"></td>
         <td style="width:20%">% Marks </td><td style="width:10%">:</td><td style="width:20%"></td>
        </tr><div></div>
        <table cellpadding="5" border="0.1">
        <tr><tr>
        <td style="width:80%;text-align:cenetr">(100%-Excellent, Above 90%-Very Good,80-90%-Satisfactory, Less than 80%-Retraining Required)</td>
        </tr></tr>
       
        <tr>
        <td style="width:10%;text-align:center"><b>SR.NO </b></td>
        <td style="width:40%;text-align:center"><b>QUESTIONS</b></td>
        <td style="width:25%;text-align:center"><b>TICK MARK</b></td>
        <td style="width:25%;text-align:center"><b>MARKS</b></td>
       </tr>
         <tr>
        <td style="width:10%;text-align:center"></td>
        <td style="width:40%;text-align:center"></td>
        <td style="width:25%;text-align:center"></td>
        <td style="width:25%;text-align:center"></td>
       </tr>
          <tr>
        <td style="width:10%;text-align:center"></td>
        <td style="width:40%;text-align:center"></td>
        <td style="width:25%;text-align:center"></td>
        <td style="width:25%;text-align:center"></td>
       </tr>
          <tr>
        <td style="width:10%;text-align:center"></td>
        <td style="width:40%;text-align:center"></td>
        <td style="width:25%;text-align:center"></td>
        <td style="width:25%;text-align:center"></td>
       </tr>
          <tr>
        <td style="width:10%;text-align:center"></td>
        <td style="width:40%;text-align:center"></td>
        <td style="width:25%;text-align:center"></td>
        <td style="width:25%;text-align:center"></td>
       </tr>
       <tr>
        <td style="width:100%;text-align:center"><b>TOTAL MARKS</b></td>
        </tr>
        
        <tr>
        <td style="width:50%"><b>REMARKS:</b></td>
        <td style="width:50%"><b>Satisfactory/ Re- training:</b></td>
        </tr>
        </table><div></div>
        <tr>
        <td style="width:50%">Evaluator’s Signature :</td>
        <td style="width:50%">Trainees Signature :</td>
        </tr>
        <tr>
        <td style="width:50%">Date :</td>
        <td style="width:50%">Date :</td>
        </tr>
        <br pagebreak="true"/> ';
        $html.='
        
        <tr>
        <td style="width:20%">Name of the Employee </td><td style="width:10%">:</td><td style="width:50%"></td>
       </tr><div></div>
         <tr>
        <td style="width:20%">Department</td><td style="width:10%">:</td><td style="width:50%"></td>
        </tr><div></div>
         
        
        <table cellpadding="5" border="0.1">
        <tr>
        <td style="width:100%;text-align:center">IN THE COURSE OF EMPLOYMENT FOLLOWING TRAINING ARE ATTAINED-</td>
        </tr>
       
        <tr>
        <td style="width:10%;text-align:center"><b>Date </b></td>
        <td style="width:20%;text-align:center"><b>Subject of Training</b></td>
        <td style="width:20%;text-align:center"><b>Reference </b></td>
        <td style="width:20%;text-align:center"><b>Trainer’s name</b></td>
        <td style="width:10%;text-align:center"><b>Remarks</b></td>
         <td style="width:10%;text-align:center"><b>Trainers Signature</b></td>
          <td style="width:10%;text-align:center"><b>Employee Signature</b></td>
       </tr>
         <tr>
        <td style="width:10%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:10%;text-align:center"></td>
         <td style="width:10%;text-align:center"></td>
          <td style="width:10%;text-align:center"></td>
       </tr>
          <tr>
        <td style="width:10%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:10%;text-align:center"></td>
         <td style="width:10%;text-align:center"></td>
          <td style="width:10%;text-align:center"></td>
       </tr>
          <tr>
        <td style="width:10%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:10%;text-align:center"></td>
         <td style="width:10%;text-align:center"></td>
          <td style="width:10%;text-align:center"></td>
       </tr>
          <tr>
        <td style="width:10%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:10%;text-align:center"></td>
         <td style="width:10%;text-align:center"></td>
          <td style="width:10%;text-align:center"></td>
       </tr></table>
        <br pagebreak="true"/> ';
        $html.='
        
        <tr>
        <td style="width:20%">Subject of Training </td><td style="width:10%">:</td><td style="width:50%"></td>
       </tr><div></div>
         <tr>
        <td style="width:20%">Date of Training</td><td style="width:10%">:</td><td style="width:50%"></td>
        </tr><div></div>
         <tr>
        <td style="width:20%">Department </td><td style="width:10%">:</td><td style="width:50%"></td>
        </tr><div></div>
         <tr>
        <td style="width:20%">Training given by</td><td style="width:10%">:</td><td style="width:50%"></td>
        </tr><div></div>
         <tr>
        <td style="width:20%">Time Of Training </td><td style="width:10%">:</td><td style="width:50%"></td>
        </tr><div></div>
         <tr>
        <td style="width:20%">Duration of Training</td><td style="width:10%">:</td><td style="width:50%"></td>
        </tr><div></div>
        <tr>
        <td style="width:20%">Training Subject </td><td style="width:10%">:</td><td style="width:50%"></td>
        </tr><div></div>
        <tr>
        <td style="width:20%">Topics Covered</td><td style="width:10%">:</td><td style="width:50%"></td>
        </tr><div></div>
         <table cellpadding="5" border="0.1">
         <tr>
        <td style="width:10%;text-align:center"><b>Sr. No. </b></td>
        <td style="width:30%;text-align:center"><b>Name of the Trainee</b></td>
        <td style="width:20%;text-align:center"><b>Department </b></td>
        <td style="width:20%;text-align:center"><b>Designation</b></td>
        <td style="width:10%;text-align:center"><b>Date / Sign</b></td>
         <td style="width:10%;text-align:center"><b>Remarks</b></td>
         
       </tr>
         <tr>
        <td style="width:10%;text-align:center"></td>
        <td style="width:30%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:10%;text-align:center"></td>
         <td style="width:10%;text-align:center"></td>
         
       </tr>
          <tr>
        <td style="width:10%;text-align:center"></td>
        <td style="width:30%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:10%;text-align:center"></td>
         <td style="width:10%;text-align:center"></td>
         
       </tr>
          <tr>
        <td style="width:10%;text-align:center"></td>
        <td style="width:30%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:10%;text-align:center"></td>
         <td style="width:10%;text-align:center"></td>
          
       </tr>
          <tr>
        <td style="width:10%;text-align:center"></td>
        <td style="width:30%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:10%;text-align:center"></td>
         <td style="width:10%;text-align:center"></td>
          
       </tr></table>
       <br pagebreak="true"/> ';
        $html.='
       
       <table cellpadding="5" border="0.1">
         <tr>
        <td style="width:10%;text-align:center"><b>Sr. No. </b></td>
        <td style="width:10%;text-align:center"><b>Date of training</b></td>
        <td style="width:20%;text-align:center"><b>Departmen </b></td>
        <td style="width:20%;text-align:center"><b>Name of Trainee</b></td>
        <td style="width:20%;text-align:center"><b>Name of Trainer</b></td>
         <td style="width:20%;text-align:center"><b>Training Regarding</b></td>
         </tr>
          <tr>
        <td style="width:10%;text-align:center"></td>
        <td style="width:10%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
         <td style="width:20%;text-align:center"></td>
         </tr>
         
     
          <tr>
        <td style="width:10%;text-align:center"></td>
        <td style="width:10%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
         <td style="width:20%;text-align:center"></td>
         </tr>
         
      
          <tr>
        <td style="width:10%;text-align:center"></td>
        <td style="width:10%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
         <td style="width:20%;text-align:center"></td>
         </tr>
          <tr>
        <td style="width:10%;text-align:center"></td>
        <td style="width:10%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
        <td style="width:20%;text-align:center"></td>
         <td style="width:20%;text-align:center"></td>
         </tr>
         </table>
          <br pagebreak="true"/> ';
        $html.='
       <h2 style="text-align:cenetr">Certification</h2>
      <tr>
        <td style="width:20%">Trainer </td><td style="width:3%">:</td><td style="width:50%">_____________________________________________________</td>
       </tr><div></div>
         <tr>
        <td style="width:20%">Date of Training </td><td style="width:3%">:</td><td style="width:50%">_____________________________________________________</td>
        </tr><div></div>
         <tr>
        <td style="width:20%">Trainee </td><td style="width:3%">:</td><td style="width:50%">_____________________________________________________</td>
        </tr><div></div>
         <tr>
        <td style="width:20%">Topic</td><td style="width:3%">:</td><td style="width:50%">_____________________________________________________</td>
        </tr><div></div>
         <tr>
        <td style="width:20%"> </td><td style="width:3%"></td><td style="width:50%">_____________________________________________________</td>
        </tr><div></div>
         <tr>
        <td style="width:20%"></td><td style="width:3%"></td><td style="width:50%">_____________________________________________________</td>
        </tr><div></div>
        <div></div>
        <tr>
        <td style="width:100%;"><b>This is to certify that ,</b></td>
        </tr> <div></div>
         <tr>
        <td style="width:100%;"><b>Mr. / Ms.  ___________________________________________________________was</b></td>
        </tr> 
        <tr>
        <td style="width:100%;"><b>Given training on the above topic and has been evaluated for the training by written / verbal test.</b></td>
        </tr> <div></div>
         <tr> 
        <td style="width:40%;text-align:center"><b>The Trainer  is Qualified   / Not Qualified   </b></td>
        </tr> <div></div>
         <tr> 
        <td style="width:40%;text-align:center"><b> Required  :- Retraining  Yes   / No    </b></td>
        </tr> <div></div><div></div>
        <tr> 
        <td style="width:50%;"><b>  ______________________     </b></td>
       
        <td style="width:50%;"><b>  ______________________     </b></td>
        </tr> 
        <tr> 
        <td style="width:50%;">Sign/Date of Trainer </td>
        
        <td style="width:50%;"> Sign/Date of Head QA</td>
        </tr> ';
       
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Employee List.pdf', 'I');
    }
   
    $conn->close();
?>
