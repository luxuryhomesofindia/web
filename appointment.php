

<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Include PHPMailer classes
require 'vendor/autoload.php'; // PHPMailer autoloader (if using Composer)

header('Content-Type: application/json; charset=utf-8');

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'fail', 'message' => 'Invalid request method.']);
    exit;
}

// Sanitizing input
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$project_area = isset($_POST['project_area']) ? trim($_POST['project_area']) : '';
$project_details = isset($_POST['project_details']) ? trim($_POST['project_details']) : '';

// Validate required fields
if (empty($name) || empty($email) || empty($phone)) {
    echo json_encode(['status' => 'fail', 'message' => 'Please fill all required fields.']);
    exit;
}

try {
    // PHPMailer Setup
    $mail = new PHPMailer(true);

    // SMTP Settings (Brevo SMTP)
    $mail->isSMTP();
    $mail->Host = 'smtp-relay.brevo.com';  // Brevo SMTP server
    $mail->SMTPAuth = true;
    $mail->Username = '9b7f39001@smtp-brevo.com'; // Brevo SMTP username (login)
    $mail->Password = 'kExdFNHKfnf18stBw'; // Brevo SMTP password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;  // Use TLS encryption
    $mail->Port = 587;  // TLS Port (use 465 for SSL)

    // Sender and recipient info
    $mail->setFrom($email, $name);  // Sender's email address and name
    // Multiple recipients
    $recipients = ['info@luxuryhomesofindia.in', 'luxuryhomesofindia@gmail.com']; 
    foreach ($recipients as $recipient) {
        $mail->addAddress($recipient);  // Add each recipient
    }
    $mail->addReplyTo($email);  // Reply-to address

    // Email subject and body
    $mail->Subject = "New Appointment Request from $name";
    $mail->isHTML(true);
    $mail->Body = "
        <html>
        <head>
            <title>New Appointment Request</title>
        </head>
        <body>
            <p><strong>Name:</strong> $name</p>
            <p><strong>Email:</strong> $email</p>
            <p><strong>Phone:</strong> $phone</p>
            <p><strong>Project Area (sq ft):</strong> $project_area</p>
            <p><strong>Project Details:</strong><br>" . nl2br(htmlspecialchars($project_details)) . "</p>
        </body>
        </html>
    ";

    // Send the email
    if ($mail->send()) {
        echo json_encode(['status' => 'success', 'message' => 'Your appointment has been booked successfully!']);
    } else {
        echo json_encode(['status' => 'fail', 'message' => 'Failed to send the email.']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'fail', 'message' => 'Mailer Error: ' . $mail->ErrorInfo]);
}
?>
