<?php
// sendmail.php — LHI Contact Form handler

// -------- SETTINGS --------
$TO_EMAIL = "info@luxuryhomesofindia.in, luxuryhomesofindia@gmail.com";  // Send to both emails
$THANK_YOU_PAGE = "thank-you.html";        // Relative path
$ALLOWED_ORIGINS = [
    "https://www.luxuryhomesofindia.in",
    "http://www.luxuryhomesofindia.in",
    "https://luxuryhomesofindia.in",
    "http://luxuryhomesofindia.in",
    "http://localhost",
    "http://127.0.0.1"
];
// --------------------------

// Basic CORS for AJAX fallbacks (safe-list only)
if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $ALLOWED_ORIGINS)) {
    header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
    header("Vary: Origin");
}

// Ensure the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo "Method Not Allowed";
    exit;
}

// Sanitize form inputs
$name = isset($_POST['name']) ? strip_tags(trim($_POST['name'])) : '';
$email = isset($_POST['email']) ? filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL) : '';
$phone = isset($_POST['phone']) ? strip_tags(trim($_POST['phone'])) : '';
$location = isset($_POST['location']) ? strip_tags(trim($_POST['location'])) : '';
$budget = isset($_POST['budget']) ? strip_tags(trim($_POST['budget'])) : '';
$message = isset($_POST['message']) ? strip_tags(trim($_POST['message'])) : '';

// Validate required fields
if (empty($name) || empty($email) || empty($phone) || empty($location) || empty($budget) || empty($message)) {
    http_response_code(400);
    echo "Please fill all required fields.";
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo "Invalid email format.";
    exit;
}

// Validate phone number format
if (!preg_match("/^\+?\d{10,15}$/", $phone)) {
    http_response_code(400);
    echo "Phone number should be between 10-15 digits.";
    exit;
}

// Set email headers
$headers = "From: info@luxuryhomesofindia.in\r\n";
$headers .= "Reply-To: " . $email . "\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";

// Email subject
$subject = "New Contact Form Submission from " . $name;

// Email body
$email_body = "
    <html>
    <head>
        <title>$subject</title>
    </head>
    <body>
        <h2>Contact Form Submission</h2>
        <p><strong>Name:</strong> $name</p>
        <p><strong>Email:</strong> $email</p>
        <p><strong>Phone:</strong> $phone</p>
        <p><strong>Location:</strong> $location</p>
        <p><strong>Budget:</strong> $budget</p>
        <p><strong>Message:</strong><br>$message</p>
    </body>
    </html>
";

// Send email (separately for each recipient to guarantee delivery)
$success1 = mail("info@luxuryhomesofindia.in", $subject, $email_body, $headers);
$success2 = mail("luxuryhomesofindia@gmail.com", $subject, $email_body, $headers);

if ($success1 || $success2) {
    // Redirect to thank you page
    header("Location: $THANK_YOU_PAGE");
    exit;
} else {
    http_response_code(500);
    echo "Something went wrong. Please try again later.";
}
?>
