<?php 
        use PHPMailer\PHPMailer\PHPMailer;
        use PHPMailer\PHPMailer\Exception;
        require '../../phpmailer1/src/Exception.php';
        require '../..//phpmailer1/src/PHPMailer.php';
        require '../../phpmailer1/src/SMTP.php';





     $mail = new PHPMailer(true);
    
    $mail->isSMTP();// Set mailer to use SMTP
    $mail->CharSet = "utf-8";// set charset to utf8
    $mail->SMTPAuth = true;// Enable SMTP authentication
    $mail->SMTPSecure = 'ssl';// Enable TLS encryption, `ssl` also accepted
    $mail->Host = 'mail.gmpsoftwareindia.com';// Specify main and backup SMTP servers
    $mail->Port = 465;// TCP port to connect to
     $mail->SMTPKeepAlive = true;
      $mail->Mailer = "smtp";
    $mail->isHTML(true);// Set email format to HTML
    $mail->Username = 'info@gmpsoftwareindia.com';// SMTP username
    $mail->Password = 'Cyclone@2020';// SMTP password
    $mail->SMTPDebug  = 2;
    $mail->setfrom('info@gmpsoftwareindia.com');
    $mail->addAddress('softwaredeveloperit06@gmail.com');
    $mail->IsHTML(true);
    $mail->Subject = 'TEST MAIL';
    $mail->Body =  'test sms';
		  if($mail->Send()){
		  echo " <script> alert('sent successfully!!!'); 
		  </script> ";
		  }else{
			  echo 'error';
		  }















?>