<?php
// sendmail.php — LHI Contact Form handler
// Place this file in the same folder as contact.html and thank_you.html

// -------- SETTINGS --------
$TO_EMAIL = "info@luxuryhomesofindia.in";  // Change if you truly use a different inbox
$THANK_YOU_PAGE = "thank_you.html";        // Relative path
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo "Method Not Allowed";
    exit;
}

// Helper: guard against header injection
function hasHeaderInjection($str) {
    return preg_match("/[\r\n]/", $str);
}

// Collect & sanitize
$name    = isset($_POST['name']) ? trim($_POST['name']) : "";
$email   = isset($_POST['email']) ? trim($_POST['email']) : "";
$message = isset($_POST['message']) ? trim($_POST['message']) : "";

// Validate
$errors = [];
if ($name === "" || hasHeaderInjection($name)) { $errors[] = "Invalid name."; }
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || hasHeaderInjection($email)) { $errors[] = "Invalid email."; }
if ($message === "" || hasHeaderInjection($message)) { $errors[] = "Invalid message."; }

if (!empty($errors)) {
    http_response_code(400);
    echo implode("\n", $errors);
    exit;
}

// Compose
$subject = "New enquiry from Luxury Homes of India website";
$body = "You have received a new enquiry via the contact form.\n\n"
      . "Name   : {$name}\n"
      . "Email  : {$email}\n"
      . "Message:\n{$message}\n\n"
      . "--\nSent from luxuryhomesofindia.in Contact Form";

// IMPORTANT: Use a domain-based 'From' to avoid SPF/DMARC rejections on hosts like GoDaddy
$from_address = "no-reply@luxuryhomesofindia.in";
$headers = [];
$headers[] = "From: Luxury Homes of India <{$from_address}>";
$headers[] = "Reply-To: {$email}";
$headers[] = "MIME-Version: 1.0";
$headers[] = "Content-Type: text/plain; charset=UTF-8";
$headers_str = implode("\r\n", $headers);

// Attempt to send
$sent = @mail($TO_EMAIL, $subject, $body, $headers_str);

if ($sent) {
    // Redirect to thank-you page on success (non-AJAX form submits)
    header("Location: {$THANK_YOU_PAGE}");
    exit;
} else {
    // If mail() fails on the host, show a helpful message
    http_response_code(500);
    echo "We couldn't send your message right now. Please email us directly at info@luxuryhomesofindia.in.";
    exit;
}
?>
