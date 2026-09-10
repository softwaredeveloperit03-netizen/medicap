<?php

// require swiftmailer/swiftmailer;
// require 'vendor/autoload.php';
// use Swift_SmtpTransport;
// use Swift_Mailer;
// use Swift_Message;
// Include the necessary PHPMailer filesf
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/SMTP.php';

// Import namespaces
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

try{
    
    
    

    
    
require '../../db.php';
require '../../token.php'; 


//     ini_set('display_errors', 1);
// error_reporting(E_ALL);

 


require '../../tcpdf/tcpdf.php'; 




header('Access-Control-Allow-Origin: *');
date_default_timezone_set("Asia/Kolkata");
$token = $_GET["token"];
    $currentUrl =$_GET["description"];
$timestamp = time();
$entry_date = date("Y-m-d h:i:s", $timestamp);
$input = json_decode(file_get_contents('php://input') , true);

$sql = "SELECT * FROM token WHERE token='" . $_GET["token"] . "'";
$result = $conn->query($sql);
$_GET["emp_id"] = "";
$_GET["department"] = "";
if ($result->num_rows > 0)
{
    while ($row = $result->fetch_assoc())
    {
        $string = decrypt('decrypt', $_GET["token"], $row["key1"], $row["key2"]);
        $string = explode("$", $string);
        $_GET["emp_id"] = $string[0];
        $_GET["department"] = $string[1];
        break;
    }
     $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR,frontend_url) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."','".$currentUrl."')";
    $conn->query($sql);
    

    // $txt = '{"process": "FRONTEND", "token": "' . $token . '", "action": "' . $_GET["type"] . '", "actiontime": "' . $entry_date . '", "department": "' . $_GET["department"] . '", "emp_id": "' . $_GET["emp_id"] . '", "method": "' . $_SERVER['REQUEST_METHOD'] . '", "REMOTE_ADDR": "' . $_SERVER['REMOTE_ADDR'] . '"}';
    // $myfile = file_put_contents('../../logs.txt', $txt . PHP_EOL, FILE_APPEND | LOCK_EX);

    function getIndianCurrency(float $number, string $words_val)
    {
        $decimal = round($number - ($no = floor($number)) , 2) * 100;
        $hundred = null;
        $digits_length = strlen($no);
        $i = 0;
        $str = array();
        $words = array(
            0 => '',
            1 => 'One',
            2 => 'Two',
            3 => 'Three',
            4 => 'Four',
            5 => 'Five',
            6 => 'Six',
            7 => 'Seven',
            8 => 'Eight',
            9 => 'Nine',
            10 => 'Ten',
            11 => 'Eleven',
            12 => 'Twelve',
            13 => 'Thirteen',
            14 => 'Fourteen',
            15 => 'Fifteen',
            16 => 'Sixteen',
            17 => 'Seventeen',
            18 => 'Eighteen',
            19 => 'Nineteen',
            20 => 'Twenty',
            30 => 'Thirty',
            40 => 'Forty',
            50 => 'Fifty',
            60 => 'Sixty',
            70 => 'Seventy',
            80 => 'Eighty',
            90 => 'Ninety'
        );
        $digits = array(
            '',
            'Hundred',
            'Thousand',
            'Lakh',
            'Crore'
        );
        while ($i < $digits_length)
        {
            $divider = ($i == 2) ? 10 : 100;
            $number = floor($no % $divider);
            $no = floor($no / $divider);
            $i += $divider == 10 ? 1 : 2;
            if ($number)
            {
                $plural = (($counter = count($str)) && $number > 9) ? 's' : null;
                $hundred = ($counter == 1 && $str[0]) ? ' and ' : null;
                $str[] = ($number < 21) ? $words[$number] . ' ' . $digits[$counter] . $plural . ' ' . $hundred : $words[floor($number / 10) * 10] . ' ' . $words[$number % 10] . ' ' . $digits[$counter] . $plural . ' ' . $hundred;
            }
            else $str[] = null;
        }
        $Rupees = implode('', array_reverse($str));
        $paise = ($decimal > 0) ? "." . ($words[$decimal / 10] . " " . $words[$decimal % 10]) . ' Paise' : '';
        return ($Rupees ? $Rupees . '' . $words_val : '') . $paise . 'Only';
        //return ($Rupees ? $Rupees . 'Rupees ' : '') . $paise .'Only';
        
    }

     if ($_GET["type"] == "approvePO")
    {
       
            $sql0 = "SELECT p.id,p.user_no,p.plant_id,p.po_no,p.vendor_no,p.net_total,p.entry_date,v.vendor_name,v.email,a.plant_full_name
            FROM purchaseorder p INNER JOIN vendor v ON p.vendor_no = v.vendor_no 
            INNER JOIN plant a ON p.plant_id = a.plant_id
            WHERE p.id ='" . $_GET["id"] . "'";
            
                 $result0 = $conn->query($sql0);
                if ($result0->num_rows > 0)
              
                    while ($row0 = $result0->fetch_assoc())
                    {
                          $pid = $row0['id'];
                          $po_no = $row0['po_no'];
                          $user_no = $row0['user_no'];
                          $plant_id = $row0['plant_id'];
                          $vendor_no = $row0['vendor_no']; 
                          $net_total = $row0['net_total'];
                          $approve_date = $row0['entry_date']; //podate
                          $vendor_name = $row0['vendor_name'];
                          $email = $row0['email'];
                          $plant_full_name = $row0['plant_full_name'];
                    }

            $msg = '
            
            
            <!DOCTYPE html
    PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="x-apple-disable-message-reformatting" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="color-scheme" content="light dark" />
    <meta name="supported-color-schemes" content="light dark" />
    <title></title>
    <style type="text/css" rel="stylesheet" media="all">
        
        @import url("https://fonts.googleapis.com/css?family=Nunito+Sans:400,700&display=swap");
        body {
            width: 100% !important;
            height: 100%;
            margin: 0;
            -webkit-text-size-adjust: none;
        }
        a {
            color: #3869D4;
        }
        a img {
            border: none;
        }
        td {
            word-break: break-word;
        }
        body,
        td,
        th {
            font-family: "Nunito Sans", Helvetica, Arial, sans-serif;
        }
        h1 {
            margin-top: 0;
            color: #333333;
            font-size: 22px;
            font-weight: bold;
            text-align: left;
        }
        h2 {
            margin-top: 0;
            color: #333333;
            font-size: 16px;
            font-weight: bold;
            text-align: left;
        }
        h3 {
            margin-top: 0;
            color: #333333;
            font-size: 14px;
            font-weight: bold;
            text-align: left;
        }
        td,
        th {
            font-size: 16px;
        }
        p,
        ul,
        ol,
        blockquote {
            margin: .4em 0 1.1875em;
            font-size: 16px;
            line-height: 1.625;
        }

        p.sub {
            font-size: 13px;
        }
        /* Utilities ------------------------------ */
        .align-right {
            text-align: right;
        }

        .align-left {
            text-align: left;
        }

        .align-center {
            text-align: center;
        }

        .u-margin-bottom-none {
            margin-bottom: 0;
        }
        /* Buttons ------------------------------ */
        @media only screen and (max-width: 500px) {
            .button {
                width: 100% !important;
                text-align: center !important;
            }
        }
        /* Attribute list ------------------------------ */
        .attributes {
            margin: 0 0 21px;
        }

        .attributes_content {
            background-color: #F4F4F7;
            padding: 16px;
        }

        .attributes_item {
            padding: 0;
        }
        /* Related Items ------------------------------ */
        .related {
            width: 100%;
            margin: 0;
            padding: 25px 0 0 0;
            -premailer-width: 100%;
            -premailer-cellpadding: 0;
            -premailer-cellspacing: 0;
        }

        .related_item {
            padding: 10px 0;
            color: #CBCCCF;
            font-size: 15px;
            line-height: 18px;
        }

        .related_item-title {
            display: block;
            margin: .5em 0 0;
        }

        .related_item-thumb {
            display: block;
            padding-bottom: 10px;
        }

        .related_heading {
            border-top: 1px solid #CBCCCF;
            text-align: center;
            padding: 25px 0 10px;
        }
        /* Social Icons ------------------------------ */
        .social {
            width: auto;
        }
        .social td {
            padding: 0;
            width: auto;
        }
        .social_icon {
            height: 20px;
            margin: 0 8px 10px 8px;
            padding: 0;
        }
        /* Data table ------------------------------ */
        .purchase {
            width: 100%;
            margin: 0;
            padding: 35px 0;
            -premailer-width: 100%;
            -premailer-cellpadding: 0;
            -premailer-cellspacing: 0;
        }
        .purchase_content {
            width: 100%;
            margin: 0;
            padding: 25px 0 0 0;
            -premailer-width: 100%;
            -premailer-cellpadding: 0;
            -premailer-cellspacing: 0;
        }
        .purchase_item {
            padding: 10px 0;
https://github.com/ajaxorg/ace/wiki/Default-Keyboard-Shortcuts            color: #51545E;
            font-size: 15px;
            line-height: 18px;
        }
        .purchase_heading {
            padding-bottom: 8px;
            border-bottom: 1px solid #EAEAEC;
        }
        .purchase_heading p {
            margin: 0;
            color: #85878E;
            font-size: 12px;
        }
        .purchase_footer {
            padding-top: 15px;
            border-top: 1px solid #EAEAEC;
        }
        .purchase_total {
            margin: 0;
            text-align: right;
            font-weight: bold;
            color: #333333;
        }
        .purchase_total--label {
            padding: 0 15px 0 0;
        }
        body {
            background-color: #F2F4F6;
            color: #51545E;
        }
        p {
            color: #51545E;
        }
        .email-wrapper {
            width: 100%;
            margin: 0;
            padding: 0;
            -premailer-width: 100%;
            -premailer-cellpadding: 0;
            -premailer-cellspacing: 0;
            background-color: #F2F4F6;
        }
        .email-content {
            width: 100%;
            margin: 0;
            padding: 0;
            -premailer-width: 100%;
            -premailer-cellpadding: 0;
            -premailer-cellspacing: 0;
        }
        .email-masthead {
            padding: 25px 0;
            text-align: center;
        }
        .email-masthead_logo {
            width: 94px;
        }
        .email-masthead_name {
            font-size: 16px;
            font-weight: bold;
            color: #A8AAAF;
            text-decoration: none;
            text-shadow: 0 1px 0 white;
        }
        .email-body {
            width: 100%;
            margin: 0;
            padding: 0;
            -premailer-width: 100%;
            -premailer-cellpadding: 0;
            -premailer-cellspacing: 0;
        }
        .email-body_inner {
            width: 570px;
            margin: 0 auto;
            padding: 0;
            -premailer-width: 570px;
            -premailer-cellpadding: 0;
            -premailer-cellspacing: 0;
            background-color: #FFFFFF;
        }
        .email-footer {
            width: 570px;
            margin: 0 auto;
            padding: 0;
            -premailer-width: 570px;
            -premailer-cellpadding: 0;
            -premailer-cellspacing: 0;
            text-align: center;
        }
        .email-footer p {
            color: #A8AAAF;
        }
        .body-action {
            width: 100%;
            margin: 30px auto;
            padding: 0;
            -premailer-width: 100%;
            -premailer-cellpadding: 0;
            -premailer-cellspacing: 0;
            text-align: center;
        }
        .body-sub {
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid #EAEAEC;
        }
        .content-cell {
            padding: 45px;
        }
        @media only screen and (max-width: 600px) {

            .email-body_inner,
            .email-footer {
                width: 100% !important;
            }
        }
        @media (prefers-color-scheme: dark) {
            body,
            .email-body,.email-body_inner,.email-content,.email-wrapper,.email-masthead,.email-footer {
                background-color: #333333 !important;
                color: #FFF !important;
            }
            p,ul,ol,blockquote, h1, h2,h3,span,
            .purchase_item {
                color: #FFF !important;
            }
            .attributes_content,
            .discount {
                background-color: #222 !important;
            }
            .email-masthead_name {
                text-shadow: none !important;
            }
        }
        :root {
            color-scheme: light dark;
            supported-color-schemes: light dark;
        }
    </style>
</head>
<body>
    <table class="email-wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation">
        <tr>
            <td align="center">
                <table class="email-content" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                    <tr>
                        <td class="email-masthead">
                            <a href="https://techtalenttrack.com" class="f-fallback email-masthead_name">
                                '.$plant_full_name.'
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td class="email-body" width="570" cellpadding="0" cellspacing="0">
                            <table class="email-body_inner" align="center" width="570" cellpadding="0" cellspacing="0"
                                role="presentation">
                                <tr>
                                    <td class="content-cell">
                                        <div class="f-fallback">
                                            <h1>Dear Sir/Mam,</h1>
                                            <p> Here With Find Attached Purchase Order From 
                                            <strong>'.$plant_full_name.'</strong> .</p>
                                            <table class="attributes" width="100%" cellpadding="0" cellspacing="0"
                                                role="presentation">
                                                <tr>
                                                    <td class="attributes_content">
                                                        <table width="100%" cellpadding="0" cellspacing="0"
                                                            role="presentation">
                                                            <tr>
                                                                <td class="attributes_item">
                                                                    <span class="f-fallback">
                                                                        <strong>PO NO. : </strong> '.$po_no.'                                                                    </span>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td class="attributes_item">
                                                                    <span class="f-fallback">
                                                                        <strong>PO Value :</strong> '.$net_total.'
                                                                    </span>
                                                                </td>
                                                            <tr>
                                                                <td class="attributes_item">
                                                                    <span class="f-fallback">
                                                                        <strong>PO Date :</strong> '.$approve_date.'
                                                                    </span>
                                                                </td>
                                                            </tr>
                                                </tr>
                                            </table>
                                    </td>
                                </tr>
                            </table>
                            <p>Thanks & Regards,
                                <br>Purchase Manager
                                <br>'.$plant_full_name.'
                                <br>'.$email.'
                            </p>
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td>
                <table class="email-footer" align="center" width="570" cellpadding="0" cellspacing="0"
                    role="presentation">
                    <tr>
                        <td class="content-cell" align="center">
                            <p class="f-fallback sub align-center">
                                GMP Software Pvt. Ltd. <br>
                                <img src="http://demo.gmpsoftwareindia.com/assets/logo1.png" width="150px" height="70px">
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    </td>
    </tr>
    </table>
</body>
</html>
            ';
            
          //  $sub = ' Purchase Order From  '.$plant_full_name.' ';
          //$pdf_url = "https://gmpsoftwareindia.com/php/phpdevlop/gmptotal/purchase/po_print.php?type=downloadPOReport&id=$pid";
//$pdf_url= "https://gmpsoftwareindia.com/php/phpdevlop/gmptotal/purchase/po_print.php?type=downloadPOReport&id=$pid&token=$token&user_no=$user_no&plant_id=$plant_id";
            
            // $binary_content = file_get_contents($pdf_url);
          //  require '../../phpmailer/class.phpmailer.php';
    // 		$mail = new PHPMailer();
    //         // $mail->IsSMTP();  
    //         // $mail->Mailer = "smtp";
    //         $mail->SMTPDebug = 0;
    //         $mail->SMTPAuth = true;
    //         $mail->SMTPSecure = 'ssl';
    //         $mail->Host = "mail.gmpsoftwareindia.com";
    //         $mail->Port = 465; // or 587
    //         $mail->IsHTML(true);
    //         $mail->Username = "info@gmpsoftwareindia.com";  
    //         $mail->Password = "Cyclone@2020";
    //         $mail->SetFrom("info@gmpsoftwareindia.com", "Paperless GMP");
    //         $mail->Subject = $sub;
    //         $mail->Body = $msg ;
    //         $mail->ContentType = 'text/html';
    //         // $mail->AddStringAttachment($binary_content, "po.pdf", $encoding = 'base64', $type = 'application/pdf');
    //         //$mail->AddAttachment($pdf_url);
    //         //$mail->AddAddress($email);
    //         $mail->AddAddress("softwaredeveloperit07@gmail.com");
    //         $mail->Send();
        
 
    
    
 

 
    }
     
    else if ($_GET["type"] == "poapprovemail")
    {
        
        
             $sql0 = "SELECT p.id,p.user_no,p.plant_id,p.po_no,p.vendor_no,p.net_total,p.entry_date,v.vendor_name,v.email,a.plant_full_name
            FROM purchaseorder p INNER JOIN vendor v ON p.vendor_no = v.vendor_no 
            INNER JOIN plant a ON p.plant_id = a.plant_id
            WHERE p.po_no ='" . $input['po_no'] . "'";
            
                 $result0 = $conn->query($sql0);
                if ($result0->num_rows > 0)
              
                    while ($row0 = $result0->fetch_assoc())
                    {
                          $pid = $row0['id'];
                          $po_no = $row0['po_no'];
                          $user_no = $row0['user_no'];
                          $plant_id = $row0['plant_id'];
                          $vendor_no = $row0['vendor_no']; 
                          $net_total = $row0['net_total'];
                          $approve_date = $row0['entry_date']; //podate
                          $vendor_name = $row0['vendor_name'];
                          $plant_full_name = $row0['plant_full_name'];
                    }
        
         $email = $input['client_email'];

        
        
            $msg = '
            
            
            <!DOCTYPE html
    PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="x-apple-disable-message-reformatting" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="color-scheme" content="light dark" />
    <meta name="supported-color-schemes" content="light dark" />
    <title></title>
    <style type="text/css" rel="stylesheet" media="all">
        
        @import url("https://fonts.googleapis.com/css?family=Nunito+Sans:400,700&display=swap");
        body {
            width: 100% !important;
            height: 100%;
            margin: 0;
            -webkit-text-size-adjust: none;
        }
        a {
            color: #3869D4;
        }
        a img {
            border: none;
        }
        td {
            word-break: break-word;
        }
        body,
        td,
        th {
            font-family: "Nunito Sans", Helvetica, Arial, sans-serif;
        }
        h1 {
            margin-top: 0;
            color: #333333;
            font-size: 22px;
            font-weight: bold;
            text-align: left;
        }
        h2 {
            margin-top: 0;
            color: #333333;
            font-size: 16px;
            font-weight: bold;
            text-align: left;
        }
        h3 {
            margin-top: 0;
            color: #333333;
            font-size: 14px;
            font-weight: bold;
            text-align: left;
        }
        td,
        th {
            font-size: 16px;
        }
        p,
        ul,
        ol,
        blockquote {
            margin: .4em 0 1.1875em;
            font-size: 16px;
            line-height: 1.625;
        }

        p.sub {
            font-size: 13px;
        }
        /* Utilities ------------------------------ */
        .align-right {
            text-align: right;
        }

        .align-left {
            text-align: left;
        }

        .align-center {
            text-align: center;
        }

        .u-margin-bottom-none {
            margin-bottom: 0;
        }
        /* Buttons ------------------------------ */
        @media only screen and (max-width: 500px) {
            .button {
                width: 100% !important;
                text-align: center !important;
            }
        }
        /* Attribute list ------------------------------ */
        .attributes {
            margin: 0 0 21px;
        }

        .attributes_content {
            background-color: #F4F4F7;
            padding: 16px;
        }

        .attributes_item {
            padding: 0;
        }
        /* Related Items ------------------------------ */
        .related {
            width: 100%;
            margin: 0;
            padding: 25px 0 0 0;
            -premailer-width: 100%;
            -premailer-cellpadding: 0;
            -premailer-cellspacing: 0;
        }

        .related_item {
            padding: 10px 0;
            color: #CBCCCF;
            font-size: 15px;
            line-height: 18px;
        }

        .related_item-title {
            display: block;
            margin: .5em 0 0;
        }

        .related_item-thumb {
            display: block;
            padding-bottom: 10px;
        }

        .related_heading {
            border-top: 1px solid #CBCCCF;
            text-align: center;
            padding: 25px 0 10px;
        }
        /* Social Icons ------------------------------ */
        .social {
            width: auto;
        }
        .social td {
            padding: 0;
            width: auto;
        }
        .social_icon {
            height: 20px;
            margin: 0 8px 10px 8px;
            padding: 0;
        }
        /* Data table ------------------------------ */
        .purchase {
            width: 100%;
            margin: 0;
            padding: 35px 0;
            -premailer-width: 100%;
            -premailer-cellpadding: 0;
            -premailer-cellspacing: 0;
        }
        .purchase_content {
            width: 100%;
            margin: 0;
            padding: 25px 0 0 0;
            -premailer-width: 100%;
            -premailer-cellpadding: 0;
            -premailer-cellspacing: 0;
        }
        .purchase_item {
            padding: 10px 0;
            color: #51545E;
            font-size: 15px;
            line-height: 18px;
        }
        .purchase_heading {
            padding-bottom: 8px;
            border-bottom: 1px solid #EAEAEC;
        }
        .purchase_heading p {
            margin: 0;
            color: #85878E;
            font-size: 12px;
        }
        .purchase_footer {
            padding-top: 15px;
            border-top: 1px solid #EAEAEC;
        }
        .purchase_total {
            margin: 0;
            text-align: right;
            font-weight: bold;
            color: #333333;
        }
        .purchase_total--label {
            padding: 0 15px 0 0;
        }
        body {
            background-color: #F2F4F6;
            color: #51545E;
        }
        p {
            color: #51545E;
        }
        .email-wrapper {
            width: 100%;
            margin: 0;
            padding: 0;
            -premailer-width: 100%;
            -premailer-cellpadding: 0;
            -premailer-cellspacing: 0;
            background-color: #F2F4F6;
        }
        .email-content {
            width: 100%;
            margin: 0;
            padding: 0;
            -premailer-width: 100%;
            -premailer-cellpadding: 0;
            -premailer-cellspacing: 0;
        }
        .email-masthead {
            padding: 25px 0;
            text-align: center;
        }
        .email-masthead_logo {
            width: 94px;
        }
        .email-masthead_name {
            font-size: 16px;
            font-weight: bold;
            color: #A8AAAF;
            text-decoration: none;
            text-shadow: 0 1px 0 white;
        }
        .email-body {
            width: 100%;
            margin: 0;
            padding: 0;
            -premailer-width: 100%;
            -premailer-cellpadding: 0;
            -premailer-cellspacing: 0;
        }
        .email-body_inner {
            width: 570px;
            margin: 0 auto;
            padding: 0;
            -premailer-width: 570px;
            -premailer-cellpadding: 0;
            -premailer-cellspacing: 0;
            background-color: #FFFFFF;
        }
        .email-footer {
            width: 570px;
            margin: 0 auto;
            padding: 0;
            -premailer-width: 570px;
            -premailer-cellpadding: 0;
            -premailer-cellspacing: 0;
            text-align: center;
        }
        .email-footer p {
            color: #A8AAAF;
        }
        .body-action {
            width: 100%;
            margin: 30px auto;
            padding: 0;
            -premailer-width: 100%;
            -premailer-cellpadding: 0;
            -premailer-cellspacing: 0;
            text-align: center;
        }
        .body-sub {
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid #EAEAEC;
        }
        .content-cell {
            padding: 45px;
        }
        @media only screen and (max-width: 600px) {

            .email-body_inner,
            .email-footer {
                width: 100% !important;
            }
        }
        @media (prefers-color-scheme: dark) {
            body,
            .email-body,.email-body_inner,.email-content,.email-wrapper,.email-masthead,.email-footer {
                background-color: #333333 !important;
                color: #FFF !important;
            }
            p,ul,ol,blockquote, h1, h2,h3,span,
            .purchase_item {
                color: #FFF !important;
            }
            .attributes_content,
            .discount {
                background-color: #222 !important;
            }
            .email-masthead_name {
                text-shadow: none !important;
            }
        }
        :root {
            color-scheme: light dark;
            supported-color-schemes: light dark;
        }
    </style>
</head>
<body>
    <table class="email-wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation">
        <tr>
            <td align="center">
                <table class="email-content" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                    <tr>
                        <td class="email-masthead">
                            <a href="https://techtalenttrack.com" class="f-fallback email-masthead_name">
                                '.$plant_full_name.'
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td class="email-body" width="570" cellpadding="0" cellspacing="0">
                            <table class="email-body_inner" align="center" width="570" cellpadding="0" cellspacing="0"
                                role="presentation">
                                <tr>
                                    <td class="content-cell">
                                        <div class="f-fallback">
                                            <h1>Dear Sir/Mam,</h1>
                                            <p> Here With Find Attached Purchase Order From 
                                            <strong>'.$plant_full_name.'</strong> .</p>
                                            <table class="attributes" width="100%" cellpadding="0" cellspacing="0"
                                                role="presentation">
                                                <tr>
                                                    <td class="attributes_content">
                                                        <table width="100%" cellpadding="0" cellspacing="0"
                                                            role="presentation">
                                                            <tr>
                                                                <td class="attributes_item">
                                                                    <span class="f-fallback">
                                                                        <strong>PO NO. : </strong> '.$po_no.'                                                                    </span>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td class="attributes_item">
                                                                    <span class="f-fallback">
                                                                        <strong>PO Value :</strong> '.$net_total.'
                                                                    </span>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td class="attributes_item">
                                                                    <span class="f-fallback">
                                                                        <strong>PO Date :</strong> '.$approve_date.'
                                                                    </span>
                                                                </td>
                                                            </tr>
                                                        
                                                </tr>
                                            </table>
                                    </td>
                                </tr>
                            </table>
                            <p>Thanks & Regards,
                                <br>Purchase Manager
                                <br>'.$plant_full_name.'
                                <br>'.$email.'
                            </p>
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td>
                <table class="email-footer" align="center" width="570" cellpadding="0" cellspacing="0"
                    role="presentation">
                    <tr>
                        <td class="content-cell" align="center">
                            <p class="f-fallback sub align-center">
                                GMP Software Pvt. Ltd. <br>
                                <img src="http://demo.gmpsoftwareindia.com/assets/logo1.png" width="150px" height="70px">
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    </td>
    </tr>
    </table>
</body>
</html>
            ';
        
        
        
        
        
        
        
  $sub = ' Purchase Order From  '.$plant_full_name.' ';
   
   
    $pdf_url ="https://cpplgmp.com/php/phpdevlop/gmptotal/purchase/po_print.php?type=downloadPOReport&id=$pid&token=$token&user_no=gmpdemo1&plant_id=$plant_id";
   
 //  $binary_content = file_get_contents($pdf_url);
  $ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $pdf_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$binary_content = curl_exec($ch);
curl_close($ch);

             
                    try {
                        
                        $mail = new PHPMailer();
                        
                        // Server settings
                        $mail->isSMTP();
                        $mail->Host = 'mail.cpplgmp.com';
                        $mail->SMTPAuth = true;
                        $mail->Username = 'info@cpplgmp.com';
                         $mail->Password = 'Cppl@1979';
                        $mail->SMTPSecure = 'tls';  
                        $mail->Port = 587;  
                        //$mail->SMTPDebug = 2;
                        // Recipients
                        $mail->setFrom('info@cpplgmp.com', 'Paperless GMP');
                        $mail->addAddress($email);
                        
                        // Content
                        $mail->isHTML(true);
                        $mail->Subject = $sub;
                        $mail->Body    = $msg;
                        
                        $mail->ContentType = 'text/html';
                        $mail->AddStringAttachment($binary_content, "po.pdf", $encoding = 'base64', $type = 'application/pdf');
                        $mail->AddAttachment($pdf_url);
                        
                        $mail->send();
                       // echo 'Email sent successfully!';
                        echo "{\"status\":\"success\"}";
                    } catch (Exception $e) {
                        echo "Email sending failed. Error: {$mail->ErrorInfo}";
                    }
                    
//                     try {
//     $mail = new PHPMailer();

    
//     $mail->isSMTP();

      
//     $mail->Host = 'mail.cpplgmp.com';
//     $mail->SMTPAuth = true;
//     $mail->Username = 'info@cpplgmp.com';
//      $mail->Password = 'Cppl@1979';
//     $mail->SMTPSecure = 'tls';  
//     $mail->Port = 587;  
//     //$mail->SMTPDebug = 2;
    
//     // Recipients
//     $mail->setFrom('info@cpplgmp.com', 'Paperless GMP');
//     $mail->addAddress($email);
    
//     // Content
//     $mail->isHTML(true);
//     $mail->Subject = $sub;
//     $mail->Body    = $msg;
    
//     $mail->ContentType = 'text/html';
    
//     // Attach the PDF file
//     $mail->AddStringAttachment($binary_content, "po.pdf", $encoding = 'base64', $type = 'application/pdf');
    
//     if(!$mail->send()) {
//         echo "Email sending failed. Error: {$mail->ErrorInfo}";
//     } else {
//         echo 'Email sent suessfully!';
//     }
// } catch (Exception $e) {
//     echo "An error occurred: {$e->getMessage()}";
// }

// create a new object
// $mail = new PHPMailer();
// // configure an SMTP
// $mail->isSMTP();
// $mail->Host = 'live.smtp.mailtrap.io';
// $mail->SMTPAuth = true;
// $mail->Username = 'api';
// $mail->Password = '1a2b3c4d5e6f7g';
// $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
// $mail->Port = 587;

// $mail->setFrom('info@cpplgmp.com', 'Your Hotel');
// $mail->addAddress('softwaredeveloperit04@gmail.com', 'Me');
// $mail->Subject = 'Thanks for choosing Our Hotel!';
// // Set HTML 
// $mail->isHTML(TRUE);
// $mail->Body = '<html>Hi there, we are happy to <br>confirm your booking.</br> Please check the document in the attachment.</html>';
// $mail->AltBody = 'Hi there, we are happy to confirm your booking. Please check the document in the attachment.';


// // send the message
// if(!$mail->send()){
//     echo 'Message could not be sent.';
//     echo 'Mailer Error: ' . $mail->ErrorInfo;
// } else {
//     echo 'Message has been sent';
// }

                            
        
        
        
    }
    else if ($_GET["type"] == "sendfollowupEmail"){
        
        
             $sql0 = "SELECT p.id,p.user_no,p.plant_id,p.po_no,p.vendor_no,p.net_total,p.entry_date,v.vendor_name,v.email,a.plant_full_name
            FROM purchaseorder p INNER JOIN vendor v ON p.vendor_no = v.vendor_no 
            INNER JOIN plant a ON p.plant_id = a.plant_id
            WHERE p.po_no ='" . $input['po_no'] . "'";
            
                 $result0 = $conn->query($sql0);
                if ($result0->num_rows > 0)
              
                    while ($row0 = $result0->fetch_assoc())
                    {
                          $pid = $row0['id'];
                          $po_no = $row0['po_no'];
                          $user_no = $row0['user_no'];
                          $plant_id = $row0['plant_id'];
                          $vendor_no = $row0['vendor_no']; 
                          $net_total = $row0['net_total'];
                          $approve_date = $row0['entry_date']; //podate
                          $vendor_name = $row0['vendor_name'];
                          $email = $row0['email'];
                          $plant_full_name = $row0['plant_full_name'];
                    }
        
        $message = $input["message"];
        $deliverydate = $input["deliverydate"];
        
        
            $msg = '
            
            
            <!DOCTYPE html
    PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="x-apple-disable-message-reformatting" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="color-scheme" content="light dark" />
    <meta name="supported-color-schemes" content="light dark" />
    <title></title>
    <style type="text/css" rel="stylesheet" media="all">
        
        @import url("https://fonts.googleapis.com/css?family=Nunito+Sans:400,700&display=swap");
        body {
            width: 100% !important;
            height: 100%;
            margin: 0;
            -webkit-text-size-adjust: none;
        }
        a {
            color: #3869D4;
        }
        a img {
            border: none;
        }
        td {
            word-break: break-word;
        }
        body,
        td,
        th {
            font-family: "Nunito Sans", Helvetica, Arial, sans-serif;
        }
        h1 {
            margin-top: 0;
            color: #333333;
            font-size: 22px;
            font-weight: bold;
            text-align: left;
        }
        h2 {
            margin-top: 0;
            color: #333333;
            font-size: 16px;
            font-weight: bold;
            text-align: left;
        }
        h3 {
            margin-top: 0;
            color: #333333;
            font-size: 14px;
            font-weight: bold;
            text-align: left;
        }
        td,
        th {
            font-size: 16px;
        }
        p,
        ul,
        ol,
        blockquote {
            margin: .4em 0 1.1875em;
            font-size: 16px;
            line-height: 1.625;
        }

        p.sub {
            font-size: 13px;
        }
        /* Utilities ------------------------------ */
        .align-right {
            text-align: right;
        }

        .align-left {
            text-align: left;
        }

        .align-center {
            text-align: center;
        }

        .u-margin-bottom-none {
            margin-bottom: 0;
        }
        /* Buttons ------------------------------ */
        @media only screen and (max-width: 500px) {
            .button {
                width: 100% !important;
                text-align: center !important;
            }
        }
        /* Attribute list ------------------------------ */
        .attributes {
            margin: 0 0 21px;
        }

        .attributes_content {
            background-color: #F4F4F7;
            padding: 16px;
        }

        .attributes_item {
            padding: 0;
        }
        /* Related Items ------------------------------ */
        .related {
            width: 100%;
            margin: 0;
            padding: 25px 0 0 0;
            -premailer-width: 100%;
            -premailer-cellpadding: 0;
            -premailer-cellspacing: 0;
        }

        .related_item {
            padding: 10px 0;
            color: #CBCCCF;
            font-size: 15px;
            line-height: 18px;
        }

        .related_item-title {
            display: block;
            margin: .5em 0 0;
        }

        .related_item-thumb {
            display: block;
            padding-bottom: 10px;
        }

        .related_heading {
            border-top: 1px solid #CBCCCF;
            text-align: center;
            padding: 25px 0 10px;
        }
        /* Social Icons ------------------------------ */
        .social {
            width: auto;
        }
        .social td {
            padding: 0;
            width: auto;
        }
        .social_icon {
            height: 20px;
            margin: 0 8px 10px 8px;
            padding: 0;
        }
        /* Data table ------------------------------ */
        .purchase {
            width: 100%;
            margin: 0;
            padding: 35px 0;
            -premailer-width: 100%;
            -premailer-cellpadding: 0;
            -premailer-cellspacing: 0;
        }
        .purchase_content {
            width: 100%;
            margin: 0;
            padding: 25px 0 0 0;
            -premailer-width: 100%;
            -premailer-cellpadding: 0;
            -premailer-cellspacing: 0;
        }
        .purchase_item {
            padding: 10px 0;
            color: #51545E;
            font-size: 15px;
            line-height: 18px;
        }
        .purchase_heading {
            padding-bottom: 8px;
            border-bottom: 1px solid #EAEAEC;
        }
        .purchase_heading p {
            margin: 0;
            color: #85878E;
            font-size: 12px;
        }
        .purchase_footer {
            padding-top: 15px;
            border-top: 1px solid #EAEAEC;
        }
        .purchase_total {
            margin: 0;
            text-align: right;
            font-weight: bold;
            color: #333333;
        }
        .purchase_total--label {
            padding: 0 15px 0 0;
        }
        body {
            background-color: #F2F4F6;
            color: #51545E;
        }
        p {
            color: #51545E;
        }
        .email-wrapper {
            width: 100%;
            margin: 0;
            padding: 0;
            -premailer-width: 100%;
            -premailer-cellpadding: 0;
            -premailer-cellspacing: 0;
            background-color: #F2F4F6;
        }
        .email-content {
            width: 100%;
            margin: 0;
            padding: 0;
            -premailer-width: 100%;
            -premailer-cellpadding: 0;
            -premailer-cellspacing: 0;
        }
        .email-masthead {
            padding: 25px 0;
            text-align: center;
        }
        .email-masthead_logo {
            width: 94px;
        }
        .email-masthead_name {
            font-size: 16px;
            font-weight: bold;
            color: #A8AAAF;
            text-decoration: none;
            text-shadow: 0 1px 0 white;
        }
        .email-body {
            width: 100%;
            margin: 0;
            padding: 0;
            -premailer-width: 100%;
            -premailer-cellpadding: 0;
            -premailer-cellspacing: 0;
        }
        .email-body_inner {
            width: 570px;
            margin: 0 auto;
            padding: 0;
            -premailer-width: 570px;
            -premailer-cellpadding: 0;
            -premailer-cellspacing: 0;
            background-color: #FFFFFF;
        }
        .email-footer {
            width: 570px;
            margin: 0 auto;
            padding: 0;
            -premailer-width: 570px;
            -premailer-cellpadding: 0;
            -premailer-cellspacing: 0;
            text-align: center;
        }
        .email-footer p {
            color: #A8AAAF;
        }
        .body-action {
            width: 100%;
            margin: 30px auto;
            padding: 0;
            -premailer-width: 100%;
            -premailer-cellpadding: 0;
            -premailer-cellspacing: 0;
            text-align: center;
        }
        .body-sub {
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid #EAEAEC;
        }
        .content-cell {
            padding: 45px;
        }
        @media only screen and (max-width: 600px) {

            .email-body_inner,
            .email-footer {
                width: 100% !important;
            }
        }
        @media (prefers-color-scheme: dark) {
            body,
            .email-body,.email-body_inner,.email-content,.email-wrapper,.email-masthead,.email-footer {
                background-color: #333333 !important;
                color: #FFF !important;
            }
            p,ul,ol,blockquote, h1, h2,h3,span,
            .purchase_item {
                color: #FFF !important;
            }
            .attributes_content,
            .discount {
                background-color: #222 !important;
            }
            .email-masthead_name {
                text-shadow: none !important;
            }
        }
        :root {
            color-scheme: light dark;
            supported-color-schemes: light dark;
        }
    </style>
</head>
<body>
    <table class="email-wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation">
        <tr>
            <td align="center">
                <table class="email-content" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                    <tr>
                        <td class="email-masthead">
                            <a href="https://techtalenttrack.com" class="f-fallback email-masthead_name">
                                '.$plant_full_name.'
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td class="email-body" width="570" cellpadding="0" cellspacing="0">
                            <table class="email-body_inner" align="center" width="570" cellpadding="0" cellspacing="0"
                                role="presentation">
                                <tr>
                                    <td class="content-cell">
                                        <div class="f-fallback">
                                            <h1>Dear Sir/Mam,</h1>
                                         
                                            <table class="attributes" width="100%" cellpadding="0" cellspacing="0"
                                                role="presentation">
                                                <tr>
                                                    <td class="attributes_content">
                                                        <table width="100%" cellpadding="0" cellspacing="0"
                                                            role="presentation">
                                                            <tr>
                                                                <td class="attributes_item">
                                                                    <span class="f-fallback">
                                                                        <strong>PO NO. : </strong> '.$po_no.'                                                                    </span>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td class="attributes_item">
                                                                    <span class="f-fallback">
                                                                        <strong>PO Value :</strong> '.$net_total.'
                                                                    </span>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td class="attributes_item">
                                                                    <span class="f-fallback">
                                                                        <strong>PO Date :</strong> '.$approve_date.'
                                                                    </span>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td class="attributes_item">
                                                                    <span class="f-fallback">
                                                                        <strong>Delivery Date :</strong> '.$deliverydate.'
                                                                    </span>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td class="attributes_item">
                                                                    <span class="f-fallback">
                                                                        <strong>Message :</strong> '.$message.'
                                                                    </span>
                                                                </td>
                                                            </tr>
                                                </tr>
                                            </table>
                                    </td>
                                </tr>
                            </table>
                            <p>Thanks & Regards,
                                <br>Purchase Manager
                                <br>'.$plant_full_name.'
                                <br>'.$email.'
                            </p>
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td>
                <table class="email-footer" align="center" width="570" cellpadding="0" cellspacing="0"
                    role="presentation">
                    <tr>
                        <td class="content-cell" align="center">
                            <p class="f-fallback sub align-center">
                                GMP Software Pvt. Ltd. <br>
                                <img src="http://demo.gmpsoftwareindia.com/assets/logo1.png" width="150px" height="70px">
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    </td>
    </tr>
    </table>
</body>
</html>
            ';
        
        
        
        
        
        
        
   $sub = ' Purchase Order From  '.$plant_full_name.' ';
   
   $pdf_url= "https://cpplgmp.com/php/phpdevlop/gmptotal1/purchase/po_print.php?type=downloadPOReport&id=$pid&token=$token&user_no=gmpdemo1&plant_id=$plant_id";

             
             $flag = 0;
                    try {
                        
                        $mail = new PHPMailer();
                        
                        // Server settings
                        $mail->isSMTP();
                        $mail->Host = 'mail.cpplgmp.com';
                        $mail->SMTPAuth = true;
                        $mail->Username = 'info@cpplgmp.com';
                         $mail->Password = 'Cppl@1979';
                        $mail->SMTPSecure = 'tls';  
                        $mail->Port = 587;  
                        //$mail->SMTPDebug = 2;
                        // Recipients
                        $mail->setFrom('info@cpplgmp.com', 'Paperless GMP');
                        $mail->addAddress($email);
                        
                        // Content
                        $mail->isHTML(true);
                        $mail->Subject = $sub;
                        $mail->Body    = $msg;
                        
   
                        
                        $mail->send();
                        // echo 'Email sent successfully!';
                        
                    } catch (Exception $e) {
                        $flag =1;
                        // echo "Email sending failed. Error: {$mail->ErrorInfo}";
                    }
                         
                         
                         
                if ($flag == 0) {    
                    
                    
                    
                $sql2="INSERT INTO fllowup_data(`message`,po_no,  entry_by,entry_date) VALUES 
                ('$message','" . $input['po_no'] . "','".$_GET["emp_id"]."', '$entry_date')";
                $conn->query($sql2);
                   
                   
                }   
        
                if ($flag == 0) {    
                    echo "{\"status\":\"success\"}";
                } else {
                     echo "{\"status\":\"".$conn->error."\"}";
                }
        
        
    } else if($_GET['type'] == 'followuphistory'){
         $output = Array();
        
        $sql = "SELECT * FROM fllowup_data where po_no='".$_GET["po_no"]."' ";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
}

else
{
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
} catch (Exception $e) {
    echo 'Caught exception: ',  $e->getMessage(), "\n";
} 
?>
