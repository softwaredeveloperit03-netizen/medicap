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
      
      
  
      if($_GET["type"]=="saveEmployee") {
       
       $sql = "INSERT INTO employee (department, designation;firstname,middlename,lastname,joining_date) VALUES ('".$input["department"]."', 
       '".$input["designation"]."', '".$input["firstname"]."', '".$input["middlename"]."' , 
       '".$input["lastname"]."' ,  '".$input["interview_date"]."')";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
        	
      
} else if ($_GET["type"] == "getEmployee") {
    $output = Array();
    $sql="SELECT * FROM employee WHERE emp_id='".$_GET['emp_id']."' and plant_id= '".$_GET['plant_id']."'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output); 
      
      }else if($_GET['type'] == 'downloadagreementpdf'){
        $_GET['filename'] = 'agreement'; $_GET['pdftype'] = 'onlyheader'; include('../pdfimp2.php');
           $html= "";
           
         $html.='
        <h3 style="text-align:center"> EMPLOYEE NON-DISCLOSURE AGREEMENT</h3>
            <div></div><div></div> 
            <li>In consideration of being employed by M/s Amgis Lifescience Ltd , the undersigned employee hereby agrees and acknowledges:</li>
         <div></div>
          <ol>
          <li> That during the course of my employment I may come across certain trade secrets of the Company; said trade secrets consisting but not necessarily limited to:</li>
         
          <br>
           <ol type="a">
           <li> Technical information: Methods, processes, formulas, compositions, systems, techniques, inventions, documentation, machines, computer programs and research projects.
           </li><br>
         <li>Business information: Customer lists, pricing data, sources of supply, financial data and marketing, production, or merchandising systems or plans.</li>
          </ol>
        <br>
          <li> I assure that I shall not during my employment, or at any time after the last working day of my employment with the Company, use for myself or others, or disclose or divulge to others including future employees, any trade secrets, confidential information, or any other proprietary data of the Company in violation of this agreement.</li>
        <br>
         <li> That upon the relieving of my employment from the Company:</li>
       <br>
        <ol type="a">
         <li>I shall return to the Company all documents and property of the Company, including but not necessarily 
         limited to: drawings, blueprints, reports, manuals, correspondence, customer lists, computer programs, 
         and all other materials and all copies thereof relating in any way to the Companys business, or in any way 
         obtained by me during the course of employ. I further agree that I shall not retain copies, intentionally 
         withhold information notes or abstracts of the fore going.
         </li>
         <br>
         <li> The Company may notify any future or prospective employer or third party of the existence of this agreement, and shall be entitled to full injunctive relief for any breach.</li>
        <br>
        <li> This agreement shall be binding upon me and Company only.</li>
         </ol>
         </ol>
         <div></div>
          <table>
         <tr>
         <td style="width:40%;text-align:center"> Dated: 24th August , 2022</td>
         </tr>
         <div></div><div></div>
        
         <tr>
         <td style="width:50%;text-align:cenetr"> <b> HR & Admin Manager </b></td>
          <td style="width:40%;text-align:center"> <b> Sales Coordinator   </b> </td>
         </tr>
         <tr>
         <td style="width:50%;text-align:cenetr"> <b> Vapi & Panoli Unit     </b></td>
          <td style="width:40%;text-align:center"> <b>    Panoli  Unit   </b> </td>
         </tr>
         <div></div>
        <tr>
        <td style="width:30%;text-align:center" ><b>Amgis Lifescience Ltd</b> </td>
      </tr>
          </table> ';
         $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('downloadagreementpdf.pdf','I');
        
        
       }else if($_GET['type'] == 'downloadconformationletterpdf'){
        $_GET['filename'] = 'conformation'; $_GET['pdftype'] = 'onlyheader'; include('../pdfimp2.php');
           $html= "";
            $sql = "SELECT * FROM employee WHERE emp_id='".$_GET['emp_id']."' and plant_id= '".$_GET['plant_id']."'";
      
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
         $html.='
         <table>
         <tr>
         <td style="width:30%;text-align:center">Ref: ALL/HR/PANOLI/05</td>
           <td style="width:60%;text-align:right"> '.$row['joining_date'].'</td>
         </tr>
         <div></div>
         <tr>
         <td style="width:25%;text-align:center"><b>'.$row['firstname'].' ,'.$row['middlename'].' ,'.$row['lastname'].' </b></td>
         </tr>
         <tr>
          <td style="width:17%;text-align:center"> E-Code:  2010</td>
         </tr>
           <tr>
          <td style="width:15%;text-align:center"> Panoli Unit </td>
         </tr>
         <div></div>
           <tr>
         <td style="width:30%;text-align:center"><u><b>Sub : Confirmation of service</b></u></td>
         </tr>
       <div></div>
       <tr>
      
         <span style="font-family:times;font-size:10px;">This has reference to your appointment with our 
         company with effect from 01st March , 2022, as <b>“'.$row['designation'].' as Manager” in</b> our <b>'.$row['department'].'.</b>
         Your annual performance was evaluated and the management is pleased to confirm your services with effect
         <b>'.$row['joining_date'].' </b> with capacity Quality Assurance  –  Officer.
         <div></div>
         All other terms and conditions of your employment remain same.
         <div></div>
         
        We hope you will continue to work in future with the same zeal, zest and in the interest of the company.<div></div><div></div>
        Congratulations!
        <div></div>
        With all the best wishes<div></div>
        Yours sincerely <div></div>
        <b>For, Amgis Lifescience Ltd</b>
        <div></div><div></div>
       <b> Authorized Signatory</b>
        
         </span>
       </tr>
           </table>';
    		}
    	}
         
      $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('downloadagreementpdf.pdf','I');
        }
    $conn->close();
?>