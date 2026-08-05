<?php
use PHPMailer\PHPMailer\PHPMailer;
require 'vendor/autoload.php';
$mail = new PHPMailer;
$mail->isSMTP();
$mail->SMTPDebug = 2;
$mail->Host = 'smtp.hostinger.com';
$mail->Port = 465;
$mail->SMTPAuth = true;
$mail->Username = 'account-info@grav-tech.com';
$mail->Password = '?D3v3L0p3R';
$mail->setFrom('account-info@grav-tech.com', 'Gravitti Technology');
$mail->addReplyTo('account-info@grav-tech.com', 'Gravitti Technology');
$mail->addAddress('irvxxdhanty@gmail.com', 'Irpi');
$mail->Subject = 'Testing PHPMailer';
$mail->msgHTML(file_get_contents('message.html'), __DIR__);
$mail->Body = 'Coba';
//$mail->addAttachment('test.txt');
if (!$mail->send()) {
echo 'Mailer Error: ' . $mail->ErrorInfo;
} else {
echo 'The email message was sent.';
}
?>