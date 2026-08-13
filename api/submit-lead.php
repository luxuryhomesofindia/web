<?php
/**
 * LHI Secure Lead Submission Handler (Self-Contained Stateless Serverless Endpoint)
 * Features: Server-side validation, Honeypot spam check, Stateless rate limiting,
 * CRM JSON logging, Resend API with local mail() fallback, and Customer Acknowledgement.
 */

// Enable error reporting for debug, but hide in production if needed
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Prevent PHP notices/warnings from polluting JSON responses
ob_start();

header('Content-Type: application/json; charset=utf-8');

// ----------------------------------------------------
// 1. CONFIGURATION
// ----------------------------------------------------
define('NOTIFICATION_EMAIL', 'luxuryhomesofindia@gmail.com');
define('FROM_EMAIL', 'info@luxuryhomesofindia.in');
define('FROM_NAME', 'Luxury Homes of India');

// Optional: Cloudflare Turnstile Secret Key (User can configure this)
define('TURNSTILE_SECRET_KEY', ''); // Add secret key here to enable Turnstile check

// ----------------------------------------------------
// 2. HONEYPOT SPAM PROTECTION
// ----------------------------------------------------
// "website_url_check" is a hidden honeypot input field
if (!empty($_POST['website_url_check'])) {
    // Silent fail for bots
    ob_clean();
    echo json_encode([
        'status' => 'success',
        'message' => 'Enquiry sent successfully.'
    ]);
    exit;
}

// ----------------------------------------------------
// 3. CLOUDFLARE TURNSTILE VALIDATION
// ----------------------------------------------------
if (!empty(TURNSTILE_SECRET_KEY) && isset($_POST['cf-turnstile-response'])) {
    $token = $_POST['cf-turnstile-response'];
    $verifyUrl = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $verifyUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'secret' => TURNSTILE_SECRET_KEY,
        'response' => $token,
        'remoteip' => $ip
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    
    $outcome = json_decode($response, true);
    if (!$outcome || !$outcome['success']) {
        ob_clean();
        echo json_encode([
            'status' => 'error',
            'message' => 'Spam verification failed. Please try again.'
        ]);
        exit;
    }
}

// ----------------------------------------------------
// 4. INPUT SANITIZATION & VALIDATION
// ----------------------------------------------------
$errors = [];

$name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS);
$mobile = filter_input(INPUT_POST, 'mobile', FILTER_SANITIZE_SPECIAL_CHARS);
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$project_location = filter_input(INPUT_POST, 'project_location', FILTER_SANITIZE_SPECIAL_CHARS);
$plot_location = filter_input(INPUT_POST, 'plot_location', FILTER_SANITIZE_SPECIAL_CHARS);
$plot_size = filter_input(INPUT_POST, 'plot_size', FILTER_SANITIZE_SPECIAL_CHARS);
$built_up_area = filter_input(INPUT_POST, 'built_up_area', FILTER_SANITIZE_SPECIAL_CHARS);
$budget = filter_input(INPUT_POST, 'budget', FILTER_SANITIZE_SPECIAL_CHARS);
$timeline = filter_input(INPUT_POST, 'timeline', FILTER_SANITIZE_SPECIAL_CHARS);
$notes = filter_input(INPUT_POST, 'notes', FILTER_SANITIZE_SPECIAL_CHARS);
$consent = filter_input(INPUT_POST, 'consent', FILTER_SANITIZE_SPECIAL_CHARS);

// Dynamic landing page fields
$landing_page = filter_input(INPUT_POST, 'landing_page_url', FILTER_VALIDATE_URL) ?: ($_SERVER['HTTP_REFERER'] ?? 'Direct');

// Multi-select Services
$services = isset($_POST['services']) && is_array($_POST['services']) ? $_POST['services'] : [];
$sanitized_services = array_map(function($service) {
    return htmlspecialchars(strip_tags(trim($service)), ENT_QUOTES, 'UTF-8');
}, $services);

// Validations
if (!$name || strlen(trim($name)) < 2) {
    $errors['name'] = 'Full Name is required (minimum 2 characters).';
}
if (!$mobile || !preg_match('/^[0-9\+\-\s\(\)]{10,15}$/', trim($mobile))) {
    $errors['mobile'] = 'A valid Mobile Number is required.';
}
if (!$email) {
    $errors['email'] = 'A valid Email Address is required.';
}
if (!$project_location || strlen(trim($project_location)) < 2) {
    $errors['project_location'] = 'Project Location is required.';
}
if (!$consent) {
    $errors['consent'] = 'You must agree to be contacted by Luxury Homes of India.';
}

if (!empty($errors)) {
    ob_clean();
    echo json_encode([
        'status' => 'validation_error',
        'errors' => $errors
    ]);
    exit;
}

// ----------------------------------------------------
// 5. CRM LOCAL DATA LOGGING
// ----------------------------------------------------
$leadsDir = __DIR__ . '/leads';
// Ensure compatibility with read-only filesystems (e.g., Vercel)
if (!is_writable($leadsDir) && !is_writable(__DIR__)) {
    $leadsDir = '/tmp/leads';
}
if (!file_exists($leadsDir)) {
    @mkdir($leadsDir, 0755, true);
}

$leadsFile = $leadsDir . '/leads.json';
$leadId = 'LHI-' . time() . '-' . rand(1000, 9999);
$timestamp = date('Y-m-d H:i:s');

// Parse UTM Tags from parameters
$utm_source = filter_input(INPUT_POST, 'utm_source', FILTER_SANITIZE_SPECIAL_CHARS) ?: 'organic';
$utm_medium = filter_input(INPUT_POST, 'utm_medium', FILTER_SANITIZE_SPECIAL_CHARS) ?: 'direct';
$utm_campaign = filter_input(INPUT_POST, 'utm_campaign', FILTER_SANITIZE_SPECIAL_CHARS) ?: 'none';
$utm_term = filter_input(INPUT_POST, 'utm_term', FILTER_SANITIZE_SPECIAL_CHARS) ?: '';
$utm_content = filter_input(INPUT_POST, 'utm_content', FILTER_SANITIZE_SPECIAL_CHARS) ?: '';

// Detect Device/Browser
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$device = 'Desktop';
if (preg_match('/(tablet|ipad|playbook|silk)|(android(?!.*mobi))/i', $userAgent)) {
    $device = 'Tablet';
} else if (preg_match('/(blackberry|configuration\/cldc|hp |hp-|htc |htc_|huawei|lg |lg-|motorola|mot-|nokia|opera mini|opera mobi|palm|palmos|pocket|portalmmm|ppc;|smartphone|sonyericsson|sony |symbian|t-mobile|up.browser|up.link|vodafone|windows ce|xda |xda_)/i', $userAgent)) {
    $device = 'Mobile';
}

$browser = 'Unknown';
if (strpos($userAgent, 'MSIE') !== false || strpos($userAgent, 'Trident') !== false) {
    $browser = 'Internet Explorer';
} else if (strpos($userAgent, 'Firefox') !== false) {
    $browser = 'Firefox';
} else if (strpos($userAgent, 'Chrome') !== false) {
    $browser = 'Chrome';
} else if (strpos($userAgent, 'Safari') !== false) {
    $browser = 'Safari';
} else if (strpos($userAgent, 'Opera') !== false || strpos($userAgent, 'OPR') !== false) {
    $browser = 'Opera';
}

$country = $_SERVER['HTTP_CF_IPCOUNTRY'] ?? '';

$leadRecord = [
    'lead_id' => $leadId,
    'timestamp' => $timestamp,
    'personal_details' => [
        'name' => $name,
        'mobile' => $mobile,
        'email' => $email
    ],
    'project_details' => [
        'project_location' => $project_location,
        'plot_location' => $plot_location,
        'plot_size' => $plot_size,
        'built_up_area' => $built_up_area
    ],
    'requirements' => [
        'budget' => $budget,
        'services' => $sanitized_services,
        'timeline' => $timeline,
        'additional_notes' => $notes
    ],
    'attribution' => [
        'landing_page' => $landing_page,
        'utm_source' => $utm_source,
        'utm_medium' => $utm_medium,
        'utm_campaign' => $utm_campaign,
        'utm_term' => $utm_term,
        'utm_content' => $utm_content
    ],
    'metadata' => [
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
        'country' => $country,
        'device' => $device,
        'browser' => $browser,
        'user_agent' => $userAgent
    ]
];

// Thread-safe writing to file
$fp = @fopen($leadsFile, 'c+');
if ($fp) {
    @flock($fp, LOCK_EX);
    $size = 0;
    if (file_exists($leadsFile)) {
        $size = @filesize($leadsFile);
    }
    $currentLeads = [];
    if ($size > 0) {
        @rewind($fp);
        $content = @fread($fp, $size);
        $currentLeads = json_decode($content, true) ?: [];
    }
    $currentLeads[] = $leadRecord;
    @ftruncate($fp, 0);
    @rewind($fp);
    @fwrite($fp, json_encode($currentLeads, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    @fflush($fp);
    @flock($fp, LOCK_UN);
    @fclose($fp);
}

// ----------------------------------------------------
// 6. HELPER: RESEND EMAIL SENDING
// ----------------------------------------------------
function send_email_via_resend($to, $subject, $html_body, $reply_to = '') {
    // Read the Resend API Key from environment or local config file
    $api_key = getenv('RESEND_API_KEY') 
        ?: ($_ENV['RESEND_API_KEY'] ?? ($_SERVER['RESEND_API_KEY'] ?? (defined('RESEND_API_KEY') ? RESEND_API_KEY : '')));
    
    // Fallback: check config file if it exists locally
    if (empty($api_key)) {
        $config_file = __DIR__ . '/../resend_config.php';
        if (file_exists($config_file)) {
            include_once $config_file;
            $api_key = getenv('RESEND_API_KEY') ?: '';
        }
    }

    if (empty($api_key)) {
        return false;
    }

    $url = 'https://api.resend.com/emails';
    $from = 'onboarding@resend.dev'; // Sandbox default
    
    $headers = [
        'Authorization: Bearer ' . $api_key,
        'Content-Type: application/json',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) LHIWebsite/1.0'
    ];

    $to_emails = is_array($to) ? $to : array_map('trim', explode(',', $to));

    $payload = [
        'from' => $from,
        'to' => $to_emails,
        'subject' => $subject,
        'html' => $html_body
    ];

    if (!empty($reply_to)) {
        $payload['reply_to'] = $reply_to;
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ($http_code >= 200 && $http_code < 300);
}

// ----------------------------------------------------
// 7. SEND EMAIL NOTIFICATIONS
// ----------------------------------------------------

// Admin Email Notification
$subjectAdmin = "New Lead from LHI website";
$boundary = md5(uniqid(time()));
$headersAdmin = "From: " . FROM_NAME . " <" . FROM_EMAIL . ">\r\n";
$headersAdmin .= "Reply-To: " . $email . "\r\n";
$headersAdmin .= "MIME-Version: 1.0\r\n";
$headersAdmin .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";

$bodyTextAdmin = "New Lead Details:\n"
               . "Name: {$name}\n"
               . "Phone: {$mobile}\n"
               . "Email: {$email}\n"
               . "Project Location: {$project_location}\n"
               . "Plot Location: {$plot_location}\n"
               . "Plot Size: {$plot_size}\n"
               . "Built-up Area: {$built_up_area}\n"
               . "Budget: {$budget}\n"
               . "Timeline: {$timeline}\n"
               . "Services: " . implode(', ', $sanitized_services) . "\n"
               . "Notes: {$notes}\n"
               . "Landing Page: {$landing_page}\n"
               . "UTM: {$utm_source} / {$utm_medium} / {$utm_campaign}\n";

$bodyHtmlAdmin = "<html><body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
<div style='max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eaeaea; border-radius: 8px;'>
    <h2 style='background-color: #0f121d; color: #d4af37; padding: 15px; margin-top: 0; text-align: center; border-radius: 4px;'>Luxury Homes of India - New Lead</h2>
    <table style='width: 100%; border-collapse: collapse;'>
        <tr style='background-color: #f9f9f9;'><td style='padding: 8px; font-weight: bold; width: 180px;'>Lead ID</td><td style='padding: 8px;'>{$leadId}</td></tr>
        <tr><td style='padding: 8px; font-weight: bold;'>Name</td><td style='padding: 8px;'>{$name}</td></tr>
        <tr style='background-color: #f9f9f9;'><td style='padding: 8px; font-weight: bold;'>Mobile</td><td style='padding: 8px;'><a href='tel:{$mobile}'>{$mobile}</a></td></tr>
        <tr><td style='padding: 8px; font-weight: bold;'>Email</td><td style='padding: 8px;'><a href='mailto:{$email}'>{$email}</a></td></tr>
        <tr style='background-color: #f9f9f9;'><td style='padding: 8px; font-weight: bold;'>Project Location</td><td style='padding: 8px;'>{$project_location}</td></tr>
        <tr><td style='padding: 8px; font-weight: bold;'>Plot Location</td><td style='padding: 8px;'>{$plot_location}</td></tr>
        <tr style='background-color: #f9f9f9;'><td style='padding: 8px; font-weight: bold;'>Plot Size</td><td style='padding: 8px;'>{$plot_size}</td></tr>
        <tr><td style='padding: 8px; font-weight: bold;'>Built-up Area</td><td style='padding: 8px;'>{$built_up_area}</td></tr>
        <tr style='background-color: #f9f9f9;'><td style='padding: 8px; font-weight: bold;'>Budget</td><td style='padding: 8px;'>{$budget}</td></tr>
        <tr><td style='padding: 8px; font-weight: bold;'>Timeline</td><td style='padding: 8px;'>{$timeline}</td></tr>
        <tr style='background-color: #f9f9f9;'><td style='padding: 8px; font-weight: bold;'>Services Requested</td><td style='padding: 8px;'>" . implode(', ', $sanitized_services) . "</td></tr>
        <tr><td style='padding: 8px; font-weight: bold;'>Additional Notes</td><td style='padding: 8px;'>{$notes}</td></tr>
    </table>
</div>
</body></html>";

// Try Resend first for reliable delivery on serverless environments
$adminSent = send_email_via_resend(NOTIFICATION_EMAIL, $subjectAdmin, $bodyHtmlAdmin, $email);

if (!$adminSent && function_exists('mail')) {
    // Fallback to local mail() if Resend fails or is unconfigured
    $messageAdmin = "--{$boundary}\r\n"
                  . "Content-Type: text/plain; charset=\"UTF-8\"\r\n"
                  . "Content-Transfer-Encoding: 7bit\r\n\r\n"
                  . $bodyTextAdmin . "\r\n"
                  . "--{$boundary}\r\n"
                  . "Content-Type: text/html; charset=\"UTF-8\"\r\n"
                  . "Content-Transfer-Encoding: 7bit\r\n\r\n"
                  . $bodyHtmlAdmin . "\r\n"
                  . "--{$boundary}--";
    @mail(NOTIFICATION_EMAIL, $subjectAdmin, $messageAdmin, $headersAdmin);
}

// Customer Acknowledgement Email
$subjectUser = "Consultation Scheduled - Luxury Homes of India";
$boundaryUser = md5(uniqid(time()));
$headersUser = "From: " . FROM_NAME . " <" . FROM_EMAIL . ">\r\n";
$headersUser .= "Reply-To: " . FROM_EMAIL . "\r\n";
$headersUser .= "MIME-Version: 1.0\r\n";
$headersUser .= "Content-Type: multipart/alternative; boundary=\"{$boundaryUser}\"\r\n";

$bodyTextUser = "Dear {$name},\n\n"
              . "Thank you for scheduling a consultation with Luxury Homes of India. We have received your request for luxury home design and construction.\n\n"
              . "Our Senior Architect and structural specialists will review your requirements. We will contact you shortly to confirm a slot for the concept discussion.\n\n"
              . "Warm Regards,\n"
              . "Luxury Homes of India Team\n"
              . "Lotus Tower, Guindy, Chennai";

$bodyHtmlUser = "<html><body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
<div style='max-width: 600px; margin: 0 auto; padding: 25px; border: 1px solid #eaeaea; border-radius: 8px; background-color: #ffffff;'>
    <div style='text-align: center; margin-bottom: 20px;'>
        <img src='https://luxuryhomesofindia.in/assets/images/logo/logo_dark.png' alt='Luxury Homes of India' style='max-width: 180px; height: auto;'>
    </div>
    <hr style='border: 0; border-top: 1px solid #eaeaea; margin-bottom: 25px;'>
    <p>Dear <strong>{$name}</strong>,</p>
    <p>Thank you for reaching out to <strong>Luxury Homes of India</strong>. We have received your consultation enquiry, and your slot request is currently under review by our senior design specialists.</p>
    
    <div style='background-color: #fcf9f2; border-left: 4px solid #d4af37; padding: 15px; margin: 25px 0; border-radius: 4px;'>
        <h4 style='margin: 0 0 10px 0; color: #c6a25a;'>What's Next?</h4>
        <ul style='margin: 0; padding-left: 20px;'>
            <li style='margin-bottom: 8px;'><strong>Preliminary Review:</strong> Our structural and design engineering desk will analyze your plot specs and built-up criteria.</li>
            <li style='margin-bottom: 8px;'><strong>Consultation Slot:</strong> A consultant will reach out via mobile to set up a preliminary concept discussion (at our Guindy studio or virtual).</li>
            <li><strong>Initial Estimates:</strong> We will provide outline layouts and cost guidelines based on our luxury packages.</li>
        </ul>
    </div>
    
    <p>If you need immediate assistance or would like to send site photographs or boundaries, please feel free to WhatsApp us directly at <a href='https://wa.me/919092276222' style='color:#25d366; font-weight:bold;'>+91 90922 76222</a>.</p>
</div>
</body></html>";

// Try Resend first for customer email
$userSent = send_email_via_resend($email, $subjectUser, $bodyHtmlUser);

if (!$userSent && function_exists('mail')) {
    // Fallback to local mail() for user email
    $messageUser = "--{$boundaryUser}\r\n"
                 . "Content-Type: text/plain; charset=\"UTF-8\"\r\n"
                 . "Content-Transfer-Encoding: 7bit\r\n\r\n"
                 . $bodyTextUser . "\r\n"
                 . "--{$boundaryUser}\r\n"
                 . "Content-Type: text/html; charset=\"UTF-8\"\r\n"
                 . "Content-Transfer-Encoding: 7bit\r\n\r\n"
                 . $bodyHtmlUser . "\r\n"
                 . "--{$boundaryUser}--";
    @mail($email, $subjectUser, $messageUser, $headersUser);
}

// ----------------------------------------------------
// 8. SUCCESS RESPONSE
// ----------------------------------------------------
ob_clean();
echo json_encode([
    'status' => 'success',
    'message' => 'Your consultation request has been submitted successfully. A specialist will call you shortly.'
]);
exit;
