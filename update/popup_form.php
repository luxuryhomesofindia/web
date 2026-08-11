<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $mobile = isset($_POST['mobile']) ? trim($_POST['mobile']) : '';
    $location = isset($_POST['location']) ? trim($_POST['location']) : 'Website Popup';
    $project_type = isset($_POST['project_type']) ? trim($_POST['project_type']) : '';
    $budget = isset($_POST['budget']) ? trim($_POST['budget']) : '';
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';

    // Recipient email addresses
    $to = "info@luxuryhomesofindia.in, luxuryhomesofindia@gmail.com";

    // Subject of the email
    $subject = "New Lead/Contact Form Submission";

    // Email body content
    $body = "You have received a new message from your website contact form.\n\n";
    $body .= "Name: " . htmlspecialchars($name) . "\n";
    $body .= "Email: " . htmlspecialchars($email) . "\n";
    $body .= "Mobile Number: " . htmlspecialchars($mobile) . "\n";
    $body .= "Origin Location: " . htmlspecialchars($location) . "\n";
    if ($project_type !== '') {
        $body .= "Project Type: " . htmlspecialchars($project_type) . "\n";
    }
    if ($budget !== '') {
        $body .= "Budget Band: " . htmlspecialchars($budget) . "\n";
    }
    $body .= "\nMessage:\n" . htmlspecialchars($message) . "\n";

    // Additional headers (DMARC compliant)
    $headers = "From: info@luxuryhomesofindia.in\r\n";
    $headers .= "Reply-To: " . $email . "\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    // Send the email (separately for each recipient to guarantee delivery)
    $success1 = mail("info@luxuryhomesofindia.in", $subject, $body, $headers);
    $success2 = mail("luxuryhomesofindia@gmail.com", $subject, $body, $headers);

    if ($success1 || $success2) {
        echo "Thank you for contacting us! Your message has been sent.";
    } else {
        echo "Sorry, there was an error sending your message. Please try again later.";
    }
}
?>
