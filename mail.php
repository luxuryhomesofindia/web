<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/PHPMailer/src/Exception.php';
require __DIR__ . '/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/PHPMailer/src/SMTP.php';

header('Content-Type: text/plain; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method not allowed'); }

$name    = trim($_POST['name']    ?? '');
$email   = trim($_POST['email']   ?? '');
$message = trim($_POST['message'] ?? '');

// Basic validation
if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $message === '') {
  http_response_code(400);
  exit('Invalid input.');
}

$mail = new PHPMailer(true);

try {
  // Use native PHP mail() function (robust, server-dependent, no password needed)
  $mail->isMail();

  // From must be your domain address
  $mail->setFrom('info@luxuryhomesofindia.in', 'Luxury Homes Website');
  $mail->addReplyTo($email, $name);                 // user who submitted the form

  // Recipients
  $mail->addAddress('info@luxuryhomesofindia.in', 'LHI');
  $mail->addAddress('luxuryhomesofindia@gmail.com');

  // Message
  $mail->isHTML(true);
  $mail->Subject = "New enquiry from $name";
  $mail->Body    = "
    <h3>New contact enquiry</h3>
    <p><b>Name:</b> ".htmlspecialchars($name)."</p>
    <p><b>Email:</b> ".htmlspecialchars($email)."</p>
    <p><b>Message:</b><br>".nl2br(htmlspecialchars($message))."</p>
  ";
  $mail->AltBody = "Name: $name\nEmail: $email\n\n$message";

  if (!$mail->send()) {
    http_response_code(500);
    exit('Mailer error: '.$mail->ErrorInfo);
  }

  echo 'Thank you! Your message has been sent.';
} catch (Exception $e) {
  http_response_code(500);
  echo 'Mailer exception: '.$mail->ErrorInfo;
}
