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
    if($result->num_rows > 0){
    while($row = $result->fetch_assoc()){
	    $string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
	    $string = explode("$",$string);
	    $_GET["emp_id"] = $string[0];
	    $_GET["department"] = $string[1];
	    break;
    }

    $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."')";
    $conn->query($sql);
    if ($_GET["type"] == "OfferLetter") {
        
       $_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp.php");
 
       
       
        $html= '  <h3 style="text-align:center;">APPOINTMENT LETTER</h3>
        <table>
                <tr>
                     <td style="width:100%; text-align:right;"><b>Date:</b><label>'.$row['joining_date'].'</label></td>
                     <li><b> To,</b><li>
                       <li><b> Near Indian Education-,</b><li>
                         <li><b>society school, Lohar chawl,</b><li>
                         <li><b>Wadgaon Sheri, Pune – 411014.</b><li>
                         <div></div>
                           <li><b>Dear Bhavana,</b><li>
                           <li>Further to our letter offer / interview dated <b>02 Feb 2022</b>, we are pleased to inform you that you are hereby appointed as <b>“Receptionist”</b> in <b>Cyclone Pharmaceuticals Pvt. Ltd.</b> based in Pune HQ as per term and conditions discussed and agreed upon as under:</li>
                         <div></div>
                         <li>1.	This appointment is effective from 03 Feb 2022 the date of your joining the Organization</li>
                         <div></div>
                         <b>2.	Probation Period:</b>
                           <div></div>
                         <li>i.	You will be placed on <b> probation</b> for a period of <b>six months></b></li>
                         <br></br>
                         <li>ii  During probation, the notice period for termination / resignation will be 45 days from either side, if notice period has not served by employee or if terminated for any reason by company employee has to pay <b>45 days</b> salary as a compensation ,you are agreed that if  because of in disciplinary  behaviors ,misbehavior with any staff ,abusive behavior or any damage to company property or reputation by any means during probation company may ask you to leave with immediate effect in that case your dues will not be cleared and it will be compensated as loss of company . </li>
                           <br></br>
                         <li>iii.	If Employee is leaving job without notice during probation or during training period company is not liable to pay any dues or pending salary it will be compensated from employee as training expenses.</li>
                         <div></div>
                         <li>iv.	Any absenteeism without notice or prior permission during Probation/Training period for more than 4 days except medical reason or any other emergency reason, shall be proved with evidences; your services will be ending without notice and company will not liable to pay any kind of dues or pending salaries and this will be considered as irresponsible behaviors.</li>
                         <div></div>
                        <li><b>3.	Confirmation of  Employment :</b></li>
                         <div></div>
                         <li>i.	  After successful completion of your probation, you will be confirmed in writing as a permanent employee of the company. You will be entitled to statutory and service benefit and be governed by discipline and other rules existing or many come into existence from time to time, as and when applicable as per rules of the Company and such other benefits as applicable to employed in force from time to time to the location / place wherever you are working. The decision are totally depend on the management and not mandatory to company
                         </li>
                         <div></div>
                         <li>ii.	Your future increments or promotion or any other salary increase shall be based on merit and performance considering your periodic and consistent overall performance, business condition and other  parameter fixed from time to time at the discretion of the management and shall not be consider merely as a mattered right.</li>
                          <div></div>
                         <li> iii.	During the period of service with the company, you shall not indulge and/ or take part in any activity of formation of council and / or association or become a member being part of management staffs which are found to be determine in the interest of the company in any way. Such an action shall be deemed as infringement to service condition of the company and amount to causing damaged to its interest and shall call or disciplinary action being taken against you, as it may deem fit and appropriate.</li>
                       <div></div>
                       <li>iv.	You shall retire from the service of the company on attending 58 years of age.</il>
                       <div></div>
                       <li>v.	During the tenure of your services, you will wholly devote yourself to the work assigned to you and will not undertake any other employment either on fu11 or part time basis,or undertake any similar kind of business which company runs, without prior permission of the company in writing. Any contravention of this condition will entail termination of your services from the company</li>
                        <div></div>
                        <li><b>4.	Legal:</b><li>
                        <div></div>
                        <li>i.Your services are liable to be transferred or loaned or assigned with / without transfer, wholly or partially, from one department to another or to office /branch and vice-versa or office branch to another office/ branch of an associate company, existing or to come into existence in future or any of the company’s branch office or location anywhere in India or abroad or any other concern where this company has any interest. In such case, you will abide by responsibilities expressly vested or implied or communicated and shall follow rules and regulations of the department / office established, jointly or separately, without any compensation or extra remuneration or provision of accommodation. You thereupon, may be governed by service condition and other terms of the said concern as may be applicable</li>
                        <div></div>
                        <li>ii.	The above said clause (i) will not give you any right to claim employment in any associate or sister concern or ask for a common seniority with the employee of the sister associate concern.</li>
                        <div></div>
                        <li>iii.In the event you are absent from duty without information or permission of leave for more than 4 days or you overstay your sanctions leave more than 4 days , the management will treat you as having voluntarily abandons the services of the company and you cannot claim any dues or pending salaries from company .</li>
                          <div></div>
                          <li><b>5.	Your service liable to be terminated at any time:</b></li>
                          <div></div>
                          <li>i.	During probation or after confirmation, In case you are found to be medically unfit by the Company\'s Authored Medical practitioner, on examination.</li>
                          <li>ii.	As and when the company come to know of any conviction by the Court of Law during the tenure of your service with us or conviction and / or any bad record in the past under the previous employer, or because of your giving false information at the time of your appointment or cancelled any material information or given any false details in the applicable form or otherwise as regard age, education qualification, experience, salary etc.</li>
                          <div></div>
                          <li>iii.	if you are found to be not possessing desired qualification which do not conform to custom authority and / govt. regulation as may to require from time to time and necessary for continuation of business or its exigencies or on account of redundancy.</li>
                          <div></div>
                          <li>iv.	In any circumstance, your act found harmful for company reputation and company assets or employees.</li>
                          <div></div>
                          <li>v.	If you found to be involved in any other employment, directorship, business related to company nature of business, involved in commercial or commission relation with client.</li>
                          <div></div>
                          <li>vi.	If any of outside person in your relation, family member, friends are found to be interfering in your work or in your company matters, or threatening to company employee’s management on your behalf this will be considered as indiscipline.</li>
                          <div></div>
                          <li>vii.	If it is found that you are not performing your duties as per your job responsibilities or you are not completing the given task, you refused to work, you refused to give support to client, you refused to perform your duty</li>
                          <div></div>
                          <li>6.You will keep the company informed of any change in our residential address that may happen during the course of employment of your service with the company.</li>
                          <div></div>
                          <li>7.All document, plans, drawing, prints trade secrets, technical information, report, statement, corresponding, source code, database, website codes or any other software information etc, written and also information and instruction that pass through you or come to your knowledge shall be treated as confidential. You shall not utilize them for your own use or disclose to other person during or after your employment. During the course of employment with the company, you will acquire, gain generate, gather and development knowledge of and be given access to business information about product activities, know-how, methods for refinement and business secrets and other information concerning the products/ business of the company, and hereinafter called the "SECRETS". You will be liable for prosecution for damages for divulgence, sharing or parting any of such information during course of employment and on cessation for at least 2 years period.</li>
                          <div></div>
                          <li>8.	You shall faithfully and to the best of your ability perform your duties the may be entrusted to you from time to time by the management. You will be bound by rules, regulation and orders promulgated by the management in relation to conduct, discipline and policy matter, You will not give out to by one, by word of mouth or otherwise, particulars of our business or administrative or organization matters of a confidential nature which may be your privilege to know by virtue of your being our employee.</li>
                          <div></div>
                          <li>9.	While you are in employment of the company, you may be given or handed over company property and/ or equipment for official use and you shall take care of them including their upkeep. On cessation of employment with the Company, you shall return all documents, books, papers relating to the affairs of the Company, purchase with the Companies money, which may have come to you, and also any property of the company in your possession.</li>
                          <div></div>
                          <li>10.	Any balance of advance or loan taken by you from the Company, shall be fully recovered from your salary and any other legal dues Including Gratuity, at the time your leaving the services in your possession.</li>
                          <div></div>
                          <li>11.	While working as an employee If you enter Into any business transaction with any party on behalf of the company within your permissible limits, It shall be your responsibility to ensure recovery of outstanding. If any outstanding remains at the time of leaving the service of the company, It shall be your responsibility to recover for remittance to the company before you proceed to settle your legal duel in full and final statement of your account.</li>
                          <div></div>
                          <li>12.	The company is obliged to deduct Income Tax at source as per provision of Income Tax Act/ Rules. Accordingly, you are required to submit all required proof of permitted saving / investment and other details from time to time to enable the company to comply with the provisions of law. In the event of non compliance by you as aforesaid if the company is required to pay any interest or payment under income Tax Act, it shall the amount as may be paid or payable from your salary or other payment and you shall allow the company to amply within the company to comply the prevision of the law. In the event of non compliance by you as aforesaid if the company is required to pay any interest or payment under Income Tax Act, it shall deduct to amount as may in paid or payable from your salary or other payment and you shall the company to comply with these requirements without objection.</li>
                          <div></div>
                          <li><b>13.	Salary Deductions or compensation recovery :</b></li>
                          <div></div>
                          <li>i.	Company has right reserved for deducting or keeping on hold or recovering loses or recovering as compensation of losses, or expenses in following circumstances.</li>
                          <div></div>
                          <li>•	If you are not serving notice period and leaving company during notice period </li>
                          <div></div>
                          <li>•	Company is paying for holidays also as per norms but if you are leaving company during training period in first two months without notice period your all holiday payment will be recovered by company. </li>
                          <div></div>
                          <li>•	You will not liable to get any dues or salaries if you are not completing 3 Months tenure in company </li>
                          <div></div>
                          <li>ii.	Salary will be paid to employee on bank accounts on or before 7th of every month however if any financial crisis circumstance arise it may get delayed by 30 days to 45 days and will be paid immediately on crisis overcome. </li>
                          <div></div>
                          <li><b>14. Notice Period : </b></li>
                          <div></div>
                          <li>i.	Your Notice Period for resignation and relieving is of 45 days however if you are working on any project or module independently or in clients support you will be relieved in 45 days or after completion of or after handing over complete status to any other employee whichever is later </li>
                          <div></div>
                          <li>ii.If there is any ongoing project or any clients pending work is going on if you are leaving company without any intimation or without completing notice period company may file prosecution against you in court of law for recovering company losses particular to project assigned to you ,it also involves if any refunds to be given to client because of your non support to client.</li>
                          <div></div>
                          <li><b>15.Resignation and Relieving :</b></li>
                          <div></div>
                          <li>i.Whenever you are willing to leave the job you have to tender your resignation in writing or on companies official email ID, resignations without any acknowledgement from appropriate authority of company will be considered as invalid. </li>
                          <div></div>
                          <li>ii.	Your notice period will be counted from the date of acceptance of resignation</li>
                          <div></div>
                          <li>iii.	During Notice period you are not allowed to take leaves except medical or extreme emergency leave and you have to submit evidences for the same, the leave days will not be considered in notice period day count.</li>
                          <div></div>
                          <li>iv.	If you wish to continue the job and change your decision of resignation you have to send application to management in writing for the same, subjected to acceptance and approval by management.</li>
                          <div></div>
                          <li>v.	Your one month salary (First 30 days salary from the date of start of notice period ) will be retained by company and will be paid as Post dated cheque of 30 days from the date of relieving, you have to give support related to your work after relieving if it was observed that you are not giving support  the given cheque will stands to cancel and this will be considered as non support recovery of the work loss by company. </li>
                          <div></div>
                          <li>vi.	If employee leave company within 8th month from joining for any reason the payment of paid holiday and paid weekly off will be deducted from final settlement.</li>
                          <div></div>
                          <li>16.	All disputes arising out of this letter will be subject to the jurisdiction of the Pune Court. And that to courts tribunals and/or authorities at Pune shall have or pertaining to this contract of employment, irrespective of your working HQ being elsewhere at that times. You are requested to return the enclosed copy duly signed as a token of your acceptance of the term and condition of your employment.</li>
                          <div></div>
                          <li>Hope that this will be the beginning of a long and successful career with us.</li>
                          <div></div>
                          <li><b>Yours Faithfully,</b></li>
                          <div><div><div></div>
                          <li>Cyclone Pharmaceuticals Pvt. Ltd. </li>
                          <div></div>
                          <li><b>  Authorized Signatory</b></li>
                          <td style="text-align:right;"> I accept and agree to the above terms & conditions <div></div>(Signature of an Employee)</td>
                          </tr>
                         </table>';
                        
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('appintment Letter.pdf', 'I');
    }
     
   }

$conn->close();
?>