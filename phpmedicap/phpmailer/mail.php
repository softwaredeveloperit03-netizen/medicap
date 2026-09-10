<?php

require 'phpmailer/class.phpmailer.php';	

$mail = new PHPMailer();
$mail->IsSMTP();
$mail->Mailer = "smtp";

$mail->SMTPDebug  = 1;  
$mail->SMTPAuth   = TRUE;
$mail->SMTPSecure = "tls";
$mail->Port       = 587;
$mail->Host       = "smtp.gmail.com";
$mail->Username   = "support@paperlessgmp.com";
$mail->Password   = "cppl@2021";
//$mail->Username   = "cyclonepharma@gmail.com";
//$mail->Password   = "cppl@1979";

$mail->IsHTML(true);
$mail->AddAddress("support@paperlessgmp.com", "PaperLess GMP Software");
$mail->SetFrom("support@paperlessgmp.com", "PaperlessGMP");
$mail->Subject = "Test is Test Email sent via Gmail SMTP Server using PHP Mailer";
$content = "<b>This is a Test Email sent via Gmail SMTP Server using PHP mailer class.</b>";

$mail->MsgHTML($content); 
if(!$mail->Send()) {
  echo "Error while sending Email.";
} else {
  echo "Email sent successfully";
}

?>