<?php
// Centralized Resend Email API Helper for Luxury Homes of India (LHI)

if (file_exists(__DIR__ . '/resend_config.php')) {
    include_once __DIR__ . '/resend_config.php';
}

function send_via_resend($to, $subject, $html_body, $reply_to = '', $attachments = []) {
    $api_key = getenv('RESEND_API_KEY') 
        ?: ($_ENV['RESEND_API_KEY'] ?? ($_SERVER['RESEND_API_KEY'] ?? (defined('RESEND_API_KEY') ? RESEND_API_KEY : '')));
    
    if (empty($api_key)) {
        error_log("Resend API key is not configured.");
        return false;
    }

    $url = 'https://api.resend.com/emails';

    // From Address Configuration:
    // NOTE: While in testing/onboarding mode, you must use 'onboarding@resend.dev'.
    // Once you add and verify your custom domain 'luxuryhomesofindia.in' inside your Resend.com dashboard,
    // you can change this to 'info@luxuryhomesofindia.in' or 'luxuryhomesofindia@gmail.com'.
    $from = 'onboarding@resend.dev'; 

    $headers = [
        'Authorization: Bearer ' . $api_key,
        'Content-Type: application/json',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
    ];

    // Format recipients array: Resend 'to' parameter accepts either a string or an array of strings.
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

    if (!empty($attachments)) {
        $payload['attachments'] = $attachments;
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15); // increased timeout to accommodate file uploads

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ($http_code >= 200 && $http_code < 300);
}
?>
