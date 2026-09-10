<?php
try {
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
    

if($result->num_rows >= 0) {
    while($row = $result->fetch_assoc()){
	    $string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
	    $string = explode("$",$string);
	    $_GET["emp_id"] = $string[0];
	    $_GET["department"] = $string[1];
	    break;
    }
    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    
    if ($_GET["type"] == "appoinmentletter") {
          class MYPDF extends TCPDF {
            public function Header() {
                $table='
                <table>
                    <tr>
                         <td style="width:20%;">';
                           $this->Image('@'.file_get_contents('https://amardeepgmp.com/assets/deep.png'),15,6,25,18);
                        $table.='
                        </td>
                    </tr>
                </table>';
                 $this->SetY('5'); $this->writeHTML($table, true, false, false, false, '');
                $this->SetY(29); $this->SetFont('helvetica', 'B', 14); $this->Cell(0, 0,'', 0, false, 'L', 0, '', 0, false, 'M', 'M');
            
            }
            public function Footer(){}
        }
        $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetMargins(10,15,10,10);
        $pdf->SetAutoPageBreak(TRUE,10);
        $pdf->AddPage($_GET['pdfpage']);
       $pdf->SetY(25);
       $pdf->SetFont('helvetica', '', 10);
      
       $pdf->SetFont ($_GET['pdffont'], '', $_GET['pdffonts'] , '', 'default', true );
        $html="";
        $sql = "SELECT * FROM employee  WHERE emp_id='".$_GET['id']."' ORDER BY id DESC";
       
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    		
    		    
    		   $date =date('Y-m-d',strtotime($row['interview_date']));
    		   $sql1="SELECT * From salary_annexure WHERE emp_id='".$row["id"]."'";
    		   $result1 = $conn->query($sql1);
                if($result1->num_rows > 0){
            		while($row1 = $result1->fetch_assoc()){
            		    //$ctc=$row1['ctc']*12;
            		    $hra=$row1['hra']*12;
                        $basic=$row1['basic']*12;
                        $specialallowance=$row1['specialallowance']*12;
                        $education=$row1['specialallowance']*12;
                        $conveyance=$row1['conveyance']*12;
                        $gross=$row1['gross']*12;
                        $ptax=$row1['ptax']*12;
                        $deduction=$row1['deduction']*12;
                        $bonus=$row1['bonus']*12;
                        $contribution=$row1['contribution']*12;
                        $medical=$row1['medical']*12;
                        $c=$bonus+$contribution+$medical;
                        $c_month=$row1['bonus']+$row1['contribution']+$row1['medical'];
                        $net_total=$row1['gross']-$row1['deduction'];
                        $net_annual=$gross-$deduction;
                        $ac_annual= $gross+$deduction;
                        $ac=$row1['gross']+$row1['deduction'];
                        
                         $html.='
                   <table>
       
        <td style="width:65%;">File No.:-'.$row1[''].'</td>
        <td style="width:30%;"> Date:<b>'.date('d/m/Y',strtotime($row['interview_date'])).'</b> </td>
        
         <div></div>
          <tr>
                        <td style="width:50%;"><br>';
                        if($row['gender']=='Female'){$html.='Miss';}else{$html.='Mr';}
                        $html.='&nbsp;
                       <b> '.$row['emp_name'].'</b></td><br>
                       </tr> <div></div>
                        
        <tr>
         <td style="width:12%;font-size:12px">Address </td> <td style="width: 1%; text-align:right;" >&#58;</td><td style="width:13.3%; text-align: right; font-size:12px">  '.$row['address_permanent'].' </td>
         </tr>
         <tr>
         <td style="width:25.3%; text-align: right; font-size:12px"> '.$row['permanant_state'].' </td>
         </tr>
         <tr>
          <td style="width:25.8%; text-align: right; font-size:12px"> '.$row['permanant_district'].' </td>
         </tr>
      <tr>
         <td style="width:12%;font-size:12px">Mobile No.</td><td style="width:1%; text-align:right;" >&#58;</td> <td style="width:14.4%; text-align: right; font-size:12px">'.$row['emp_contact'].' </td>
       </tr>
       <tr>
       <td style="width:12%;font-size:12px">Email ID</td> <td style="width: 1%; text-align:right;" >&#58;</td><td style="width:22.3%; text-align: right; font-size:12px">'.$row['email'].'</td>
       </tr><br><br>
       <tr>
      <td style="width:100%;"><b>Sub: Appointment Letter</b></td>
       </tr><br><br>
       <tr>
         <td style="width:100%;">Dear '.$row['emp_name'].',</td> 
       </tr><br>
        
          
          This has reference to your interview <b>'.date('d/m/Y',strtotime($row['interview_date'])).'</b>  we are pleased to inform 
          you that you are appointed <b>'.$row['department'].'</b>
            As a General Manager</b> at <b>Panoli Unit</b> based at <b>Panoli (Gujarat)</b>. Your date of joining is <b>'.date('d/m/Y',strtotime($row['joining_date'])).'</b></font>
            
            <h4 style="width:100%;"><u>Salary Authorization from (SAF):</u></h4>
       <span style="text-align:justifiy">
       The compensation and benefits you are entitled to have been detailed in the Salary Authorization form 
      (SAF)attached herewith at <b>Annexure ‘A’</b>. The entitlements detailed in the SAF are subject to change from time 
      to time. Any changes in your compensation and benefits will be communicated to you by the company in writing 
      by issuing you a revised SAF.</span><br><br>
      
   <span><u><b>Confirmation of Services:</u></b>
        <p> Your services will be confirmed after satisfactory completion of 6 months’ probation.</p></span><br>
         
         <h4 style="width:100%;"><u>Other Terms and Conditions:</u></h4>
       
       <span>Detailed terms & conditions at <b>Annexure ‘B’</b>.</span><br><br>
        
       <span style="font-size:9px">Please signify your acceptance by signing and returning the copy of this appointment order, along with the annexure ‘B’.
         </span><br><br>
         <table>
         <tr>
         <td style="width:100%;  font-size: 10px;">Yours Sincerely,</td>
         </tr><br>
         <tr>
        <td style="width:100%;  font-size: 10px;"><b>For Amardeep Chemical Industries Pvt Ltd</b>.</td>
        </tr><br><br>
        <tr>
     <td style="width:100%; font-size: 12px;">Mr.Dharmendra Patel </td>
      </tr><br>
      <tr>
      <td style="width:100%;  font-size: 12px;">Managing Director</td>
       </tr><br>
       <tr>
       <td style="width:100%;  font-size: 12px;">Vapi & Panoli Unit</td>
       </tr>
        </table>
        
        <br pagebreak="true"/>';
          
       $html.='
       <div></div><div></div>
            <table cellpadding="1" border="0.1">
        
         <tr>
        <th style="width:95%;" align="center"><b>Salary Authorization Form (SAF):</b></th></tr>
        <tr>
         <th style="width:95%;"align="cenetr"><b>Annexure A to Appointment Letter dated:</b></th> 
        </tr>
         </table><br><br>
          <table style="width:100%;"cellpadding="3" border="0.1">
         <tr>
         <td style="width:55%;"><b>Name</b></td>
          <td style="width:40%;">'.$row['emp_name'].'</td>
         </tr>
         
         <tr>
         <td style="width:55%;"> <b>Designation</b></td>
          <td style="width:40%;">'.$row['designation'].'</td>
         </tr>
         <tr>
         <td style="width:55%;"><b>Department</b></td>
          <td style="width:40%;">'.$row['department'].'</td>
         </tr>
         <tr>
         <td style="width:55%;"><b>Location</b></td>
          <td style="width:40%;">'.$row['location'].'</td>
         </tr>
         <tr>
         <td style="width:55%;"><b>Probation Period </b></td>
          <td style="width:40%;"><b>'.$row1[''].'</b></td>
         </tr>
         <tr>
         <td style="width:55%;"><b>Monthly CTC Rs.</b></td>
          <td style="width:20%;"><b>'.$row1['ctc'].'</b></td>
          <td style="width:20%;"><b></b></td>
          </tr>
          <tr>
         <td style="width:55%;"align="center"><b>Cost To Company (CTC)</b></td>
         </tr>
         <tr>
         <th style="width:55%;"><b>Salary Heads </b></th>
          <th style="width:20%;"><b>NR Per Month </b></th>
          <th style="width:20%;"><b>INR Per Annum</b></th>
          </tr>
          <tr>
         <td style="width:55%;">Basic</td>
          <td style="width:20%; text-align:right;">'.$row1['basic'].'</td>
          <td style="width:20%; text-align:right;">'.$basic.'</td>
          </tr>
          <tr>
         <td style="width:55%;">House Rent Allowance</td>
          <td style="width:20%;  text-align:right;">'.$row1['hra'].'</td>
          <td style="width:20%;  text-align:right;">'.$hra .'</td>
          </tr>
          <tr>
         <td style="width:55%;">Conveyance Allowance</td>
          <td style="width:20%;  text-align:right;">'.$row1['conveyance'].'</td>
          <td style="width:20%;  text-align:right;">'.$conveyance.'</td>
          </tr>
          <tr>
         <td style="width:55%;  text-align:right;">Education Allowance </td>
          <td style="width:20%;">'.$row1[''].'</td>
          <td style="width:20%;  text-align:right;"></td>
          </tr>
          <tr>
         <td style="width:55%;">Special Allowance</td>
          <td style="width:20%;  text-align:right;">'.$row1['specialallowance'].'</td>
          <td style="width:20%;  text-align:right;">'.$specialallowance.'</td>
          </tr>
          <tr>
         <td style="width:55%;  text-align:right;"><b>Gross Salary</b></td>
          <td style="width:20%;  text-align:right;"><b>'.$row1['gross'].'</b></td>
          <td style="width:20%;  text-align:right;"><b>'.$gross.'</b></td>
          </tr>
           <tr>
         <td style="width:55%;"><b></b></td>
          <td style="width:20%;"><b></b></td>
          <td style="width:20%;"><b></b></td>
          </tr>
           <tr>
         <td style="width:55%;"><b>Employer Benefits </b></td>
          <td style="width:20%;  text-align:right;">'.$row1[''].'</td>
          <td style="width:20%;  text-align:right;"></td>
          </tr>
           <tr>
         <td style="width:55%;">Mediclaim Contribution</td>
          <td style="width:20%;  text-align:right;">'.$row1['medical'].'</td>
          <td style="width:20%;"></td>
          </tr>
           <tr>
         <td style="width:55%;">EPF Contribution (Employers_Basic *13%)</td>
          <td style="width:20%;">'.$row1[''].'</td>
          <td style="width:20%;  text-align:right;"></td>
          </tr>
           <tr>
         <td style="width:55%;">Bonus (As Per Govt. Rules)</td>
          <td style="width:20%;  text-align:right;">'.$row1['gratuity'].'</td>
          <td style="width:20%;  text-align:right;">'.$gratuity.'</td>
          </tr>
           <tr>
         <td style="width:55%;">Gratuity</td>
          <td style="width:20%;  text-align:right;">'.$row1['gratuity'].'</td>
          <td style="width:20%;  text-align:right;">'.$gratuity.'</td>
          </tr>
           <tr>
         <td style="width:55%;">Total Deduction </td>
          <td style="width:20%;">'.$row1[''].'</td>
          <td style="width:20%;"></td>
          </tr>
           <tr>
         <td style="width:55%;"><b>Fixed CTC</b></td>
          <td style="width:20%;  text-align:right;"><b>'.$row1['ctc'].'</b></td>
          <td style="width:20%;  text-align:right;"><b>'.$ctc.'</b></td>
          </tr>
           <tr>
         <td style="width:95%;"><b>Employee Deduction </b></td>
         </tr>
          <tr>
         <td style="width:55%;">PF Cont 12%</td>
          <td style="width:20%;  text-align:right;"><b>'.$row1['PF_EMP'].'</b></td>
          <td style="width:20%;  text-align:right;"><b>'.$PF_EMP.'</b></td>
          </tr>
           <tr>
         <td style="width:55%;">Professional Tax</td>
          <td style="width:20%;  text-align:right;">'.$row1[''].'</td>
          <td style="width:20%;"></td>
          </tr>
            <tr>
         <td style="width:55%;"><b>Total Cost to Company</b></td>
          <td style="width:20%; "><b>'.$row1[''].'</b></td>
          <td style="width:20%;"><b></b></td>
          </tr>
          </table><br><br>
          <table cellpadding="1" border="0.1">
           <tr>
         <td style="width:55%;"><b>Net Take Home Salary after PF & Tax deduction </b></td>
          <td style="width:20%;"><b>'.$row1[''].'</b></td>
          </tr>
         
         </table>
          <br pagebreak="true"/>';
         
          $html.='
          <table>
          <h2 style="text-align: center;">‘Annexure B’</h2><br>
        <span style="text-align:justify;"> In continuation to our offer of employment with Amardeep Chemicals Industries Pvt Ltd .,a summary of the major benefits available to all employees is detailed below along with other terms and conditions of employment.</span><br>
           <h5 style="width:25%;">WORKING HOURS</h5><br>
           The standard work-week will be Monday through Saturday from 09:15 hours to 17:45 hours. Depending on the nature of the work schedule the standard work hours may be different for employees in some functions or practices.
           <br>
           <h5 style="width:100%;">PROBATION</h5><br>
            You will be on probation for period of Six months from the date of joining and your services are deemed to be on probation till your services are confirmed in writing .<br>
            Probation period can be extended for a period of three months or more on the advice of your reporting manager, and at the discretion of the Company depending on your performance.
           <br>
           <h5 style="width:100%;">WORK RULES</h5><br>
           You will also be entitled to and governed at all times by the policies, procedures, regulations and rules of the company in effect from time to time whether such policies are specified in the letter of appointment or elsewhere. You would be required to apply & maintain the highest standards of personal conduct and integrity and comply with all the policies and procedures of the company with punctuality.
          <br>
          <h5 style="width:100%;">EMPLOYMENT</h5><br>
         <span>You will devote your whole working time to the service of the company and will not engage in any other employment. Failure to comply with the above will subject you to immediate termination without notice or payment in lieu of notice.</span>
          <br>
           <h5 style="width:100%;">BACKGROUND REFERENCE CHECK</h5><br>
          <span>The Company, at any time (or as part of the joining formalities) conduct reference/ background check (including but not limited to the previous employers, education qualifications etc.) in the event the statements / particulars furnished by you is found to be false or misleading, Company reserves its right to terminate your services forthwith on the grounds of misrepresentation of the facts. Further in the event if it is found that you had indulged / been indulging in drugs and narcotics abuse or any other criminal activities or had any criminal records, Company shall have the right to terminate your services forthwith. You shall have no objection if the company makes it’s inquires in this regard as a pre-employment check.</span>
           <br>
          
            <h5 style="text-align: center;">EMPLOYEE BENEFITS</h5><br>
            <h5 style="width:100%;">HOLIDAYS</h5><br>
          <span>We observe 10 National and Festival Holidays per year.4 National Holidays are observed every year and you would be entitled to 6 other Festival Holidays from an Optional List.</span>
          <br>
          <h5 style="width:100%;">LEAVE</h5><br>
          <span> On completing one year’s continuous service with Amardeep every employee will be eligible for 30 days of leave (inclusive of 07 casual leave and 07 sick leaves). The leave will be proportionate to the number of days actually worked during the calendar yea</span><br>
         <br>
          <span>In the event, if you are absent from work for 24 hours or more then you are forthwith required to notify AMARDEEP about your absence along with reasons for the absence from work.</span><br>
         
         <h5 style="width:100%;">GRATUITY</h5><br>
       <span>As per the Payment of gratuity act 1972 , upon completing 5 years of continuous Service with AMARDEEP every employee will be eligible for the receipt of Gratuity a social security measure. The amount, equivalent to half month’s basic pay for every completed year of service will be paid to you at the time of your separation from</span><br>
         <div></div>   
            <span>AMARDEEP, be it by resignation, termination or retirement. AMARDEEP will not be liable to pay Gratuity to any employee</spa><br>
         <br>
         <span>who causes damage to the company through willful negligence & omission, destruction of Company property or misconduct including leaving the services of the company without proper notice.</span>
            
          <h5 style="width:100%;">COMPENSATION PACKAGE</h5><br>
         <span>We aim at paying attractive and competitive salaries to all our employees. Your compensation & benefits will be reviewed and revised annually, and any adjustments will be based on a thorough review of market conditions. Your individual performance and your contributions to AMARDEEP and Organization performance.</span>
         <br>
         <h5 style="width:100%;">PERFORMANCE REVIEW</h5><br>
         <span>At the discretion of the Company, your services will be reviewed on quarterly basis on set KRAs. However, the salary revisions will be done annually as per Company’s policy.</span>
        <br>
          <h5 style="width:100%;" align="center">TERMS OF SERVICE</h5><br>
         <h5 style="width:100%;">INTEGRITY</h5><br>
         <span>It must be specifically understood that this offer is made based on the professional skills. You have declared to possess as per your resume.</span>
            <br>
            <span>During the term of your employment with AMARDEEP currently or in the future or may be in conflict with the terms of your employment with AMARDEEP either directly or indirectly. This includes personal details viz, name, age, father name, contact address or professional information like qualification. Ability or previous or any other matter germane to employment at the time of employment or during the course of employment.</span>
            <br>
            <span>Should AMARDEEP at a later date during the term of your employment become aware that you have either suppressed any particulars or relevant information required to be disclosed by you or that you have furnished false/misleading information AMARDEEP reserves the right to terminate your services forthwith without any notice and without any obligation or liability to pay any remuneration or other dues to you irrespective of the period that you may have been employed by AMARDEEP.</span>
            <br>
            <span>Every employee is expected to follow the taxation laws rules and philosophy of compensation and benefits in the letter and spirit and uphold the values of honesty and integrity in all his/her actions in the course of doing so. Every employee shall claim only actual expenses and ensure compliance with the tax laws of the land in letter and spirit</span>
            <div></div>
            <h5 style="width:100%;">CONFIDENTIALITY</h5><br>
         <span>You are expected to maintain utmost secrecy with regard to the affairs of AMARDEEP and shall keep any information, instruments, manuals, relating to the company that may come to your professional knowledge as on associate of the company.</span><br>
           <br><br>
          <span>The position held by you is of a strictly confidential nature. As a result of employment at AMARDEEP the company may from time to time need to impart you with certain information/material pertaining to its business or its associate companies or any company. Firm or person with whom AMARDEEP or its associate companies may at any time be in technical. Commercial or financial cooperation or association. Which is to be treated as secret and confidential.</span>
          <br><br>
          <span>You shall not disclose to either during or after employment with the company any information about the interests or business of the company or any affiliated company or client.</span>
          <br><br>
          <span>During your employment or at any time after the termination of employment. you will not divulge to any unauthorized person any trade of manufacturing process or any knowledge or information concerning any matter or thing relating to the business or interests of AMARDEEP and its subsidiaries/associate companies or of any company firm or person with whom the AMARDEEP or its subsidiaries /associate companies may it any time be in technical commercial or financial cooperation or association. You will not utilize any secret or confidential information or knowledge acquired in consequence of your employment.</span>
           <br><br>
           <span>You shall keep confidential any information or manuals relating to the Company’s compensation and benefits schemes <div></div><div></div> that may come to your professional knowledge as an associate of the Company. You should maintain</span>
           <span>utmost secrecy with regard to compensation and benefits package and treat it as a highly individual and confidential matter not to be discussed with any colleague, other than your Manager.</span>
          <br><br>
          <span>You shall not except in accordance with any general or special order of the Company of in the performance, in good faith of the duties assigned to you communicate directly or indirectly any official document or any part thereof of information (including your salary to any other Officer or other associate or any other person to whom you are reporting).</span>
           <br><br>
           <span>You shall not either during employment with AMARDEEP or for a period of two years thereafter approach AMARDEEP business contacts. Business partners or customers for business of a similar nature either individually or as a company or organization where you have an investment an advisory role or whole –time employment in a decision-making capacity</span>
           <br><br>
           <span>You will be required to execute and be bound by a Non-Disclosure Agreement given to you along with the Employment Letter and such Agreement shall be co-extensive with this Employment Letter.</span>
           <div></div>
           <h5 style="width:100%;">AUTHORIZATION</h5><br>
        <span>The management of AMARDEEP shall be the only authorized signatory to sign any legal documents and shall only at its discretion may speak about the company, its business plans & current projects.</span>
          <br>
        <h5 style="width:100%;">SECURITY</h5><br>
        <span>The data/information held on organization’s systems is deemed to be the property of AMARDEEP. You shall be responsible for the protection of data/information and security of passwords. The data/information/passwords should not be shared even with your colleagues. You shall use the company’s email for official purpose only.</span>
        <br>
        <span>Information shall be available to you on a need-to-know basis/based on the roles and responsibilities. You shall be provided with a worktable and storage space which you shall ensure that such storage spaces are locked when attended. Duplicate keys will be maintained with security/Administration, you may take a duplicate key after signing for it for your own or a team member’s table or storage.</span>
            <br>
            <span>In case you work outside Office hours on the premises you are requested to produce your identity card to the Security personnel on demand. Any equipment taken out of the Office premises will require a gate pass duly authorized by the appropriate authority.</span>
            <br>
          <h5 style="width:100%;"><b>USE OF COMPANY RESOURCES</b></h5><br>
          <span>You shall be responsible for the safekeeping and good condition and order of all the AMARDEEP property entrusted to your care and charge. You may use the AMARDEEP resources only for Official purposes</span>
          <br>
          <h5 style="width:100%;">RETIREMENT AGE</h5><br>
          <span>The age of retirement for every associate of AMARDEEP is 60 years. You shall however during the tenure of the services be required to be medically fit for work. AMARDEEP may at its discretion request you to undergo periodic medical examination to enable professional determination of medical fitness for employment.</span>
         <br>
         <h5 style="width:100%;">TERMINATION</h5><br>
         <span>Your Service with the company may be terminated at anytime, after confirmation or during probation by giving written notice of 30 days or payment of one month’s salary if your performance is not upto to the satisfaction or expectation or if there is any misconduct against to the Policies, and interest of the company.</span>
         <br>
           <h5 style="width:100%;">HEALTH INSURANCE</h5>
           <span>All the employees are eligible for the medical benefit under Medi-claim Policy. It covers employee, spouse and two children (Dependents as per the policy) will be covered under the Company Medi-Claim.</span>
          <br>
          <span>All the employees are covered under the Group Term Life Insurance Policy.</span>
           <br>
           <span>Company has right to discontinue it, without giving any reasonable justification/reason.</span>
          <div></div><div></div><div></div>
          
            <h5 style="width:100%;">CODE OF CONDUCT</h5><br>
            <span>It is condition of this Appointment letter and your acceptance that in terms of your business activities and personal endeavors, your conduct will be in accordance with Company’s policies and code of conduct. You should comply with the legal requirements of each State in which, the Company conducts business and shall enjoy the highest ethical standards in any business dealings.</span>
            <br><br>
            <span>You will treat your colleagues, subordinates, superiors and female co workers with respect and dignity at the workplace.</span>
             <br><br>
             <span>Violation of these or any of the codes of conduct & discipline of the Company will result in immediate termination.</span>
           <br><br>
           <span>Whenever you change your present or local residence, or permanent address for any reason, you shall intimate the change to the Management immediately.</span>
            <br><br>
         <span>You will not leave the station of your place of employment without prior intimation to the immediate superior or Officer in charge of your department, as the case may be.</span>
          <div></div>
           <h5 style="width:100%;">ALLOWANCES & PERQUISITE</h5><br>
         <span>The Company will reimburse authorized reasonable expenses you incur on Company business during the course of employment. Claims for expenses will be subject to the Company’s Policy from time to time and approval from the Concerned Authority in writing. The Claim should be accompanies by reasonable proof of the expenditure. You will not be entitled to authorize your own expenses.</span>
          <br>
          <h5 style="width:100%;">INFRASTRUCTURE AND OFFICE EQUIPMENT</h5><br>
         <span>You will be provided with the basis Infrastructure facilities like laptop/desktop, SIM Card, Access Card, ID Card etc., depending upon the need and nature of your services. The IT team reserves the right to control & maintain the designed information and access to sites. Access to information will be provided depending upon the specific requirement of the user. Though the access to network is authorized through access privileges approved by the HOD and IT Dept.</span>
         <br>
         <span>Use of Company resources for personal use is strictly restricted. This includes usage of computer resources, information, internet service, and working time of the Company for any personal use.</span>
          <br>
        <h5 style="width:100%;">NOTICE PERIOD FOR RESIGNATION</h5><br>
         <span>This employment is directed towards a career at AMARDEEP. However, employment at AMARDEEP will always entail the conditions of satisfactory performance and satisfactory market conditions for AMARDEEP’S products and services (as it may determine at its sole discretion).The employee need to serve 7 days of notice period if leaving withing three months of Probation and 15 days notice period if leaving after three months of Joining during Probation.</span>
         <br><br>
         <span>For all the employees post confirmation the notice period for relieving form your services with AMARDEEP shall be 90 days or basic salary in lieu of notice period on part of AMARDEEP only.</span>
           <br><br>
           <span>Amardeep reserves the right to terminate your services without any notice or salary in lieu thereof on grounds of misconduct, disloyalty and negligence, commission of any act involving moral turpitude or any act of indiscipline or inefficiency or loss of confidence. In the event of any breach of the code of conduct or non-performance of contractual obligation or the terms and notwithstanding any other terms and conditions stipulated herein. AMARDEEP further reserves the right to invoke other legal remedies as it deems fit to protect its legitimate interests.</span>
          <br><br>
          <span>In case of employment termination for any reason the year-end performance incentive (if applicable) a part of your compensation structure will not be processed as part of full & final settlement.</span>
             <br>
          <h5 style="width:100%;">RETURN OF PROPERTY</h5>
          <span>On Separation of your employment or upon the demand of the Company, you should deliver to the Company all keys, identification cards and other related documents or materials in your possession provided by the Company. Furthermore, the Employee warrants and undertakes that he/ she, or through a third person, will not make, or allow to be made, any copy or records in any form of the above mentioned materials.</span>
         <div></div><div></div><div></div>
          <span>You have to settle all the advances taken by you during your employment with the Company or the same shall be recovered / settled during Full & Final calculations.</span>
           <br>
         <h5 style="width:100%;">TRANSFERS</h5><br>
           <span>Every employee of AMARDEEP is liable for transfer/deputation/secondment/training to any office of AMARDEEP or it’s associate companies’ client locations or third parties in India or abroad in such an event you will be governed by the terms and conditions of service applicable to the new assignment.</span>
           <br><br>
           <span>In all service matters, including those not specifically covered here such as travel etc. employees will be governed by the rules and policies of AMARDEEP in force from time to time.</span>
          <br>
         <h5 style="width:100%;">BUSINESS CONDUCT</h5><br>
         <span>You shall at all times maintain office decorum including in dealing with colleagues both with office premises and at client locations. Practices such as reading newspaper or magazines in the reception having obscene posters/work station screen servers at your work place standing in groups and having refreshments in common areas playing games at your work premises etc. should be strictly avoided.</span>
       <br>
      <h5 style="width:100%;">SEXUAL HARASSMENT</h5><br>
        <span>Any act or language with sexual overtones or implications proving offensive to colleague of the opposite or same sex will be construed as sexual misconduct and should be strictly avoided Offensive posters / screen savers/mails or magazines and books at your work place should be strictly avoided.</span>
        <br>
          <h5 style="width:100%;">INTELLECTUAL PROPERTY RIGHTS</h5>
         <span>You hereby expressly acknowledge and agree that any work that you may be conducting either on the premises of AMARDEEP or otherwise with regard to patents, improvements discoveries or any other form of intellectual property whether protected under law or not you are working on the express or implied instructions of AMARDEEP and on behalf of AMARDEEP .</span>
         <br><br>
          <span>Any invention, development, process, discovery, formulae, plan, specification program component, process adaptation or improvement in procedure or other matters or work including any artistic literary or other work which the subject matter of copyright may be whatsoever made. Developed or discovered by you, either alone or jointly with any person or persons while in employment with AMARDEEP. capable of being used or adapted for use therewith shall forthwith be disclosed to AMARDEEP and shall belong to and be the absolute property of AMARDEEP and shall be deemed to be “work made for hire”.</span>
          <br><br>
         <span>You also hereby irrevocably transfer and assign to AMARDEEP and waive and agree never to assert any and all Moral Rights you may have in or with respect to any work, documentations, designs and materials patents copyright or any other form of intellectual property where protected under law or not even after termination of your work during or after the tenure of your employment.</span>
         <br><br>
          <span>You shall not communicate to any public papers, journals, pamphlets or leaflets or cause to be disclosed at any time any information or documents official or otherwise relating to AMARDEEP expect with the prior approval (in writing) of the management.</span>
          <br><br>
         <h5 style="width:100%;">OTHER TERMS AND CONDITIONS</h5><br>
         <span>In addition, you shall be subjected to such other existing general terms and conditions of service as may be laid down by the Company to govern all members of its staff and to any changes to the terms and conditions of employment that may be introduced by the Company from time to time.</span>
         <br>
          <span>The terms of its appointment letter do not and or not intended to create either an express and / or implied contract of employment with the Company, and the Board of Directors of the Company reserves the right to change the terms of the letter unconditionally.</span>
          <br><br>
           <span>With acceptance of this employment, you accept that the restraints specified in this letter are reasonable in all the <div></div><div></div>circumstances for the protection of the company and its other group company’s legitimate interest.</span>
           <br><br>
          <span>By signing this document, you confirm that you have not entered into any other agreement with or undertaken obligations to others, including agreement with and obligation to previous employment that are in conflict with the terms herein.</span>
          <br><br>
          <span>All the above briefed terms and conditions are based on AMARDEEP’s policies, procedures and other rules currently applicable.</span>
         <br>
         <span>For,<b>AMARDEEP CHEMICAL INDUSTRIES PVT. LTD.</b></span>
         <div></div><div></div>
         <table>
         <tr>
             <td style="width:100%;  font-size: 12px;"><b>Mr. Dharmendra Patel</b></td>
             </tr><br>
             <tr>
         
         <td style="width:100%; font-size: 12px;"><b>Managing Director</b></td>
         </tr><br>
         <tr>
          
           <td style="width:100%;font-size: 12px;"><b>Vapi & Panoli</b></td>
        </tr>
         
         <h5 style="text-align: center;">Employee Acknowledgement</h5>
       
        <p style="width:40%;  font-size: 10px;">I accept all terms and Conditions of the company as stipulated above.</p>
                     <p style="width:40%; font-size: 10px;">I hereby accept the position on the terms and conditions of employment offered.</p>
                    
         <table>
         <tr>
            < td style="width:100%;font-size: 10px;">Name:</td>
         </tr>
         </table><div></div>
         <table>
         <tr>
        <td style="width:10%;text-align:right;font-size: 10px;">Signature:</td>
         <td style="width:50%;text-align:right;font-size: 10px;">Date:</td>
        </tr>
        
        </table>';
              	}
                }
    		
    		}
    	}
   	
    		
    	
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('appoinmentletter.pdf', 'I');
        
    }
    
    
     else if ($_GET["type"] == "generate_offer_letter") {
         if($_GET["plant_id"] == 67) { //Saipro
	   
	    $_GET['filename'] = ' PO';
		$_GET['pdftype']= 'onlyheader';  include('../pdfimp2.php');
        $html= "";
        
        $sql = "SELECT * FROM candidate WHERE isSalary = 'Yes' AND dept_remark='selected' AND id='".$_GET['id']."' ORDER BY id DESC";
     
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    		      $sql1="SELECT * FROM salary_annexure a left join plant b on a.plant_id=b.plant_id WHERE  a.emp_id='".$row["id"]."'";
    		      
    		    $salary_details;
    		   $result1 = $conn->query($sql1);
                if($result1->num_rows > 0){
            		while($row1 = $result1->fetch_assoc()){
            		   $salary_details = $row1;
            		}}
            
             $html.='
                <table cellpadding="1">
              <tr>
                    <td style="width: 25%;"></td>
                    <td style="width: 25%; text-align:center; color:brown"><h2><strong><u>Offer Letter</u></strong></h2></td>
                    <td style="width: 25%; text-align:right; font-weight: bold;">Date:</td>
                    <td style="width: 25%;"><b>'.date('d/m/Y',strtotime($row['entry_date'])).'</b></td>
                </tr>
                <br>
                
                
                <tr>
                    <td style="width:540;"> To,<br>';
                     if($row['gender']=='Female'){$html.='Miss';}else{$html.='Mr';}
                      $html.='&nbsp;
                        '.$row['candidate_name'].'<br>
                        '.$row['address'].'</td>
                </tr>
               
               
                <br>
                <tr>
                    <td  style="width:540; font-weight: bold;">Congratulations!!</td>
                </tr>
                <br><br>
                <tr>
                    <td style="width:540;">We are pleased to offer you an Employment with Saipro Industries Pvt. Ltd. based on the Discussions you had with us. Details of the terms and conditions of offer are as under :</td>
                </tr>
                <br>
                <br>
                <tr>
                    <td style="width:540;"> 1. You will be designated as <b>'.$row['designation'].'</b> - <b>'.$row['department'].'</b> .</td>
                </tr>
                <tr>
                    <td style="width:540;"> 2. You will be based at our Pune in Food Manufacturing Unit Kasaramboli.</td>
                </tr>
                <tr>
                    <td style="width:540;"> 3. Your date of commencement of Employment will be on or Date of Joining.</td>
                </tr>
                <tr>
                    <td style="width:540;"> 4. Your Administrative & Functional reporting will be to General Manager & Plant Manager as well as MD Sir</td>
                </tr>
                <tr>
                    <td style="width:540;"> 5. Your <b> Salary </b>is as per the <b> annexure</b> attached with this letter.</td>
                </tr>
                <tr>
                    <td style="width:540;"> 6. You will be on probation for a period of <b> One Month </b>from the date of joining. (PF will not get)</td>
                </tr>
                <tr>
                    <td style="width:540;"> 7. Please bring along the below listed documents / details on your day of joining.</td>
                </tr>
                <tr>
                    <td style="width:540;"> 8. Minimum <b> 24 months </b> need to work for company from <b> date of joining. </b></td>
                </tr>
                <tr>
                    <td style="width:540;"> 9. 2 months’ notice period before leaving job.</td>
                </tr>
                <tr>
                    <td style="width:540;"> 10 Yearly <b> 24 months </b> Paid leaves</td>
                </tr>
                <tr>
                    <td style="width:540; font-weight: bold;"> 11 Accommodation & travel are in your scope.</td>
                </tr>
                <br><br>
                <tr>
                    <td style="width:540;"> a. Date of Birth proof certificate (Copy of passport / birth certificate / S.S.C Certificate)</td>
                </tr>
                <tr>
                    <td style="width:540;"> b. True Copy of Academic Certificates (all from 10th to Highest)</td>
                </tr>
                <tr>
                    <td style="width:540;"> c. True Copy of Resignation Letter of Last Employer with acknowledgement</td>
                </tr>
                <tr>
                    <td style="width:540;"> d. Relieving letter from previous employer (Original)</td>
                </tr>
                <tr>
                    <td style="width:540;"> e. Proof of compensation last drawn (3 Months - Original)</td>
                </tr>
                <tr>
                    <td style="width:540;"> f. 2 passport size photographs (Recent)</td>
                </tr>
                <br><br>
                <tr>
                    <td style="width:540;">  12.  Kindly sign a copy of this letter as a token of your acceptance of this offer.
                        </td>
                </tr>
                <br>
                <tr>
                    <td style="width:540;"> Looking forward to a long and mutually beneficial career with us</td>
                </tr>
                <br><br><br><br>
                <tr>
                    <td style="width:180px;text-align: center;"> Yours sincerely</td>
                    <td style="width:180px;"> </td>
                    <td style="width:180px;"> </td>
                </tr>
                <br>
                <tr>
                    <td style="width:180px;text-align: center; font-weight: bold;"> For, Saipro Industries Pvt.Ltd.</td>
                    <td style="width:180px;"> </td>
                    <td style="width:180px;"> </td>
                </tr>
                <tr>
                    <td style="width:180px; ;text-align: center;"> Vijay B. Chougule</td>
                    <td style="width:180px;"> </td>
                    <td style="width:180px;"> </td>
                </tr>
                <tr>
                    <td style="width:180px;text-align: center;"> HR Manager</td>
                    <td style="width:180px;"> </td>
                    <td style="width:180px;"> </td>
                </tr>

            </table> 
            <br pagebreak="true"/>';
            
            
            $html.='<table cellpadding="1" border="0.1">
               
                <tr>
                    <td style="width:540px; text-align: center; font-weight: bold;">ANNEXURE</td>
                </tr>
                <tr>
                    <td style="width: 220px;"> Name Of Candidate</td>
                    <td style="width: 320px;"> '.$row['candidate_name'].'  </td>
                </tr>
                <tr>
                    <td style="width: 220px;"> Designation</td>
                    <td style="width: 320px;"> '.$row['designation'].' </td>
                </tr>
                <tr>
                    <td style="width: 220px;"> Department</td>
                    <td style="width: 320px;"> '.$row['department'].' </td>
                </tr>
                <tr>
                    <td style="width: 220px;font-weight: bold; text-align: center; "> Salary Components</td>
                    <td style="width: 160px;font-weight: bold; text-align: center; ">Monthly Salary</td>
                    <td style="width: 160px;font-weight: bold;  text-align: center;">Yearly</td>
                </tr>';
                
         $sql2="SELECT * FROM salary_annexure_details WHERE  salary_annexure_id= '" .$salary_details["id"]."' AND  salary_group ='Earnings' ";
    
    	   $result2 = $conn->query($sql2);
                if($result2->num_rows > 0){
            		while($row2= $result2->fetch_assoc()){
            		    
        $html.='  <tr>
                    <td style="width: 220px;"> '.$row2['description'].' </td>
                    <td style="width: 160px; text-align: center; ">'.$row2['per_month'].'</td>
                    <td style="width: 160px; text-align: center;">'.$row2['per_annum'].'</td>
                </tr>';
            	}
        
            $html.='    <tr>
                    <td style="width: 220px; font-weight: bold;"> Gross Salary (A)</td>
                    <td style="width: 160px; font-weight: bold; text-align: center;">'.$salary_details['total_earnings'].'</td>
                    <td style="width: 160px; font-weight: bold; text-align: center;">'.($salary_details['total_earnings'] * 12).'</td>
                </tr>
                <tr>
                    <td style="width: 220px; font-weight: bold;"> Company Liability</td>
                    <td style="width: 320px;"></td>
                </tr>';
            }
            
             $sql2="SELECT * FROM salary_annexure_details WHERE  salary_annexure_id= '" .$salary_details["id"]."' AND  salary_group ='CTC Calculations' ";
    	   $result2 = $conn->query($sql2);
                if($result2->num_rows > 0){
            		while($row2= $result2->fetch_assoc()){	
                
             $html.='
                
                <tr>
                    <td style="width: 220px;"> '.$row2['description'].' </td>
                    <td style="width: 160px; text-align: center; ">'.$row2['per_month'].'</td>
                    <td style="width: 160px; text-align: center;">'.$row2['per_annum'].'</td>
                </tr>';
            		}
              $html.=' 
               
                <tr>
                    <td style="width: 220px; font-weight: bold;"> Cost To Company</td>
                    <td style="width: 160px; font-weight: bold; text-align: center;">'.$salary_details['total_ctc'].'</td>
                    <td style="width: 160px; font-weight: bold; text-align: center;">'.($salary_details['total_ctc'] * 12).'</td>
                </tr>';
                }
            
         $html.='    </table>';
        
         
        $html.=' <table>
                <tr>
                    <td style=" width: 540px;"> 1. Your Salary is Confidential please do not disclose it with anyone.</td>
                </tr>
                <tr>
                    <td style=" width: 540px;"> 2. And Your Net Salary Is <b> Rs '.$salary_details['take_home_salary'].'./- </b>Per month.</td>
                </tr>
                <tr>
                    <td style=" width: 540px;"> 3. Tax deduction at sources (TDS) is borne by employee.</td>
                </tr>
                <tr>
                    <td style=" width: 540px;"> 4. Yearly Bonus: Equal to basic pay of month if the performance is good and company doing profit</td>
                </tr>
                <br><br><br><br>
                <tr>
                    <td style="width:180px; .text-align: center;"> Yours sincerely</td>
                    <td style="width:180px; ."> </td>
                    <td style="width:180px; ."> </td>
                </tr>
                <br>
                <tr>
                    <td style="width:180px; .text-align: center; font-weight: bold;"> For, Saipro Industries Pvt.Ltd.</td>
                    <td style="width:180px; font-size: 8;"> </td>
                    <td style="width:180px; font-size: 8;"> </td>
                </tr>
                <tr>
                    <td style="width:180px; .text-align: center;"> Vijay B. Chougule</td>
                    <td style="width:180px; font-size: 8;"> </td>
                    <td style="width:180px; font-size: 8;"> </td>
                </tr>
                <tr>
                    <td style="width:180px; .text-align: center;"> HR Manager</td>
                    <td style="width:180px; font-size: 8;"> </td>
                    <td style="width:180px; font-size: 8;"> </td>
                </tr>
        </table>';
            
            
            		
            		
    		}
    	}
    
        	$pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('','I');
    }else { 
         
         $_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";
        
        $sql = "SELECT * FROM candidate WHERE isSalary = 'Yes' AND dept_remark='selected' AND id='".$_GET['id']."' ORDER BY id DESC";
     
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    		      $sql1="SELECT * FROM salary_annexure a left join plant b on a.plant_id=b.plant_id WHERE  a.emp_id='".$row["id"]."'";
    		      
    		    $salary_details;
    		   $result1 = $conn->query($sql1);
                if($result1->num_rows > 0){
            		while($row1 = $result1->fetch_assoc()){
            		   $salary_details = $row1;
            		}}
    		
    		
           $html.='
                <table cellpadding="1">
                    <tr>
                        <td style="width:25%;"><b>File No</b>: ALL/HR/PANOLI/01</td>
                         <td style="width:25%;"></td>
                        <td style="width:25%; text-align:right;"><b>Date:</b></td>
                        <td style="width:25%;"><b>'.date('d/m/Y',strtotime($row['entry_date'])).'</b></td>
                    </tr><br>
                    <tr>
                        <td style="width:100%;">To,<br>';
                        if($row['gender']=='Female'){$html.='Miss';}else{$html.='Mr';}
                        $html.='&nbsp;
                        '.$row['candidate_name'].'<br>
                        '.$row['address'].'</td>
                         </tr>
                        <tr>
                        <td style="width:100%;text-align:center;color:brown"><h2><strong><u>Offer Letter</u></strong></h2></td>
                        </tr>
                    <tr>
                    <td style="width:100%; font-weight:bold;">Dear &nbsp;'; if($row['gender']=='Female'){$html.='Miss';}
                    else{$html.='Mr';}
                    $html.='&nbsp;'.$row['candidate_name'].'</td>
                    </tr>
                    <tr>
                    <td style="width:100%;">This has reference to your application for employment in our company and the subsequent 
                    interview you had with us on <b>'.date('d/m/Y',strtotime($row['interview_date'])).'</b> . We are pleased to offer you an employment with our 
                    organization as <b>'.$row['designation'].'</b> - <b>'.$row['department'].'</b> at <b>Panoli,</b> 
                    on the following conditions:</td>
                    </tr>
                    <ul>
                            <li>Please note that this is an offer letter only. The company’s standard appointment letter 
                                  containing detailed CTC components   and terms & conditions of your employment will be issued to 
                                  you upon you joining the company which shall be binding on you.</li>
                                <li>You would be on probation period of 6 (Six Months) from the date of your joining and your services would be confirmed subsequent to a satisfactory performance and assessment.</li>
                               <li>You are requested to join us on or before _________________ failing which this offer will stand automatically withdrawn. Kindly also confirm your exact date of joining within seven days from the receipt of this offer.</li>
                                <li>You are requested to bring the following documents in original at the time of reporting for duty:</li>
                                </ul>
                               <ul>
                               <ul>
                             
                              <li>  Education Certificates – SSC, Inter, Degree, PG and others, if any.</li>
                                    <li>  Relieving letter from the Previous Employer & Experience Certificates.</li>
                                    <li>  Pay slips for last three months.</li>
                                    <li>  Proof of Date of Birth / SSLC / HSC certificate stating Date of Birth.</li>
                                    <li>  Photocopy of Bank A/c Details, PAN & Aadhar card.</li>
                                    <li>  Photocopy of Address Proof. </li>
                                    <li>  Passport Size Photos – 03 Nos. </li>
                                    
                              </ul>
                            </ul>
                      <tr>
                      <td style="width:100%">Your commencement of employment shall be subject to you fulfilling the following conditions:</td>
                      </tr>
                   <ul>
                    <ol type="a">
                      <li>An appropriate relieving letter from your immediately previous employer is required, if employed previousl;and</li>
                     <li>By signing this offer you hereby consent to any background investigations and/or reference checks that may be carried out in relation to you by the Company.</li>
                      </ol>
                     </ul>
                     <tr>
                     <td style="width:100%">Please indicate your acceptance of this position by signing below and returning a signed copy of this letter and the attached addendum. We look forward to a mutually rewarding relationship.</td>
                     </tr><div></div>
                      <tr>
                   <td style="width:50%"><b>With Best Wishes,</b></td>
                   </tr>
                    <tr>
                   <td style="width:50%"><b>For, '.$salary_details['plant_full_name'].'</b></td>
                   </tr><div></div>
                   <tr>
                   <td style="width:100%"><b>Authorized Signatory</b></td>
                   </tr>
                
                  
                </table>
                <br pagebreak="true"/>';
                
        
              $html.='<table cellpadding="1" border="0.1">
        
        <tr style="background-color:#DDDAD9;">
        <th style="width:100%;" align="center;"><b>Salary Authorization Form (SAF):</b></th></tr>
         <tr style="background-color:#DDDAD9;">
         <th style="width:100%;"align="cenetr"><b>Annexure A to Appointment Letter dated:</b></th> 
        </tr>
         </table>
          <table style="width:100%;"cellpadding="3" border="0.1">
         <tr>
         <td style="width:50%;"><b>Name</b></td>
          <td style="width:50%;">'.$row['candidate_name'].' </td>
         </tr>
         
         <tr>
         <td style="width:50%;"><b>Designation</b></td>
          <td style="width:50%;">'.$row['designation'].'</td>
         </tr>
         <tr>
         <td style="width:50%;"><b>Department</b></td>
          <td style="width:50%;">'.$row['department'].'</td>
         </tr>
         <tr>
         <td style="width:50%;"><b>Location</b></td>
          <td style="width:50%;">Panoli</td>
         </tr>
         <tr>
         <td style="width:50%;"><b>Probation Period </b></td>
          <td style="width:50%;"><b> 6 Months</b></td>
         </tr>
         <tr>
         <td style="width:50%;"><b>Monthly CTC Rs.</b></td>
          <td style="width:25%;"><b>'.$salary_details['total_ctc'].'</b></td>
          <td style="width:25%;"><b></b></td>
          </tr>
          <tr>
         <td style="width:50%;"align="center"><b>Cost To Company (CTC)</b></td>
         </tr>
         <tr>
         <th style="width:50%;"><b>Salary Heads </b></th>
          <th style="width:25%;"><b>INR Per Month </b></th>
          <th style="width:25%;"><b>INR Per Annum</b></th>
         </tr>';
         
    $sql2="SELECT * FROM salary_annexure_details WHERE  salary_annexure_id= '" .$salary_details["id"]."' AND  salary_group ='Earnings' ";
    
    	   $result2 = $conn->query($sql2);
                if($result2->num_rows > 0){
            		while($row2= $result2->fetch_assoc()){	
                
      
        $html.=' <tr>
         <td style="width:50%;">'.$row2['description'].'</td>
          <td style="width:25%; text-align:right;">'.$row2['per_month'].'</td>
          <td style="width:25%; text-align:right;">'.$row2['per_annum'].'</td>
         </tr>';
    	}
    		$html.=' 	<tr>
          <td style="width:50%;"><strong>Gross Salary</strong></td>
          <td style="width:25%; text-align:right;"><strong>'.$salary_details['total_earnings'].'</strong></td>
          <td style="width:25%; text-align:right;"><strong>'.($salary_details['total_earnings'] * 12).'</strong></td>
         </tr>
         <tr>
          <td style="width:50%;"><strong>Employer Benefits </strong></td>
          <td style="width:25%; text-align:right;"></td>
          <td style="width:25%; text-align:right;"></td>
         </tr>
         ';
            		
                }
                $sql2="SELECT * FROM salary_annexure_details WHERE  salary_annexure_id= '" .$salary_details["id"]."' AND  salary_group ='CTC Calculations' ";
    	   $result2 = $conn->query($sql2);
                if($result2->num_rows > 0){
            		while($row2= $result2->fetch_assoc()){	
                
      
        $html.=' <tr>
         <td style="width:50%;">'.$row2['description'].'</td>
          <td style="width:25%; text-align:right;">'.$row2['per_month'].'</td>
          <td style="width:25%; text-align:right;">'.$row2['per_annum'].'</td>
         </tr>';
            		}
            		
            	$html.=' 	<tr>
          <td style="width:50%;"><strong>Fixed CTC</strong></td>
          <td style="width:25%; text-align:right;"><strong>'.$salary_details['total_ctc'].'</strong></td>
          <td style="width:25%; text-align:right;"><strong>'.($salary_details['total_ctc'] * 12).'</strong></td>
         </tr>
          
         	<tr>
          <td style="width:50%;"><strong>Employee Deduction  </strong></td>
          <td style="width:25%; text-align:right;"></td>
          <td style="width:25%; text-align:right;"></td>
         </tr>
         ';
            		
                }
        
         $sql2="SELECT * FROM salary_annexure_details WHERE  salary_annexure_id= '" .$salary_details["id"]."' AND  salary_group ='Deductions' ";
    	   $result2 = $conn->query($sql2);
                if($result2->num_rows > 0){
            		while($row2= $result2->fetch_assoc()){	
                
      
        $html.=' <tr>
         <td style="width:50%;">'.$row2['description'].'</td>
          <td style="width:25%; text-align:right;">'.$row2['per_month'].'</td>
          <td style="width:25%; text-align:right;">'.$row2['per_annum'].'</td>
         </tr>';
            		}
            		
            	$html.=' 	<tr>
          <td style="width:50%;"><strong>Total Deductions</strong></td>
          <td style="width:25%; text-align:right;"><strong>'.$salary_details['ctc_deductions'].'</strong></td>
          <td style="width:25%; text-align:right;"><strong>'.($salary_details['ctc_deductions'] * 12).'</strong></td>
         </tr>';
            		
                }
                
        	$html.=' 	<tr>
          <td style="width:50%;"><strong>Net Take Home Salary after PF & Tax deduction</strong></td>
          <td style="width:25%; text-align:right;"><strong>'.$salary_details['take_home_salary'].'</strong></td>
          <td style="width:25%; text-align:right;"><strong>'.($salary_details['take_home_salary'] * 12).'</strong></td>
         </tr>';
         $html.='</table>
        <div></div>
          <tr>
                   <td style="width:50%"><b>With Best Wishes,</b></td>
                   </tr>
                    <tr>
                   <td style="width:50%"><b>For, '.$salary_details['plant_full_name'].'</b></td>
                   </tr><br>
                   <tr>
                   <td style="width:100%"><b>Authorized Signatory</b></td>
                   </tr>
                  <hr style="color:blue"></hr>
                  <tr>
                   <td style="width:100%;text-align:center;color:brown"><b><u>
                  <h3>Acceptance</h3></u></b></td>
                  </tr>
                  <tr>
                  <td style="width:70%;">I have read and understood the above Terms & Conditions and hereby signify my acceptance. I hereby confirm my date of joining as: _________________.</td>
                  </tr><div></div><div></div>
                  <tr>
                  <td style="width:35%;">Name:</td>
                  <td style="width:30%;">Signature:</td>
                   <td style="width:35%;">Date:</td>
                  </tr>';
    		
    	
    		}
    	}
         $html.='  </table>';
              	
           $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('generateHrOffer.pdf', 'I');
    
    }
    
}
    
    else{
        echo "{\"status\":\"Method Not Found\"}";
    }
    
             
    
$conn->close();    
}else{
        echo "{\"status\":\"invalid token\"}";
}
} catch(Exception $e) {
  echo $e->getMessage();
}  
?>
