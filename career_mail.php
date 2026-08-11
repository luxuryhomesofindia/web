<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo "Method Not Allowed";
    exit;
}

// Read input (check if $_POST is populated from multipart form data, else fallback to JSON)
if (!empty($_POST)) {
    $data = $_POST;
} else {
    $input_raw = file_get_contents('php://input');
    $data = json_decode($input_raw, true);
}

if (!$data) {
    http_response_code(400);
    echo "Invalid request payload.";
    exit;
}

$name = isset($data['name']) ? strip_tags(trim($data['name'])) : '';
$email = isset($data['email']) ? filter_var(trim($data['email']), FILTER_SANITIZE_EMAIL) : '';
$phone = isset($data['phone']) ? strip_tags(trim($data['phone'])) : '';
$city = isset($data['city']) ? strip_tags(trim($data['city'])) : '';
$job_title = isset($data['job_title']) ? strip_tags(trim($data['job_title'])) : '';
$experience = isset($data['experience']) ? strip_tags(trim($data['experience'])) : '';
$current_company = isset($data['current_company']) ? strip_tags(trim($data['current_company'])) : '';
$current_designation = isset($data['current_designation']) ? strip_tags(trim($data['current_designation'])) : '';
$notice_period = isset($data['notice_period']) ? strip_tags(trim($data['notice_period'])) : '';
$highest_qualification = isset($data['highest_qualification']) ? strip_tags(trim($data['highest_qualification'])) : '';
$linkedin = isset($data['linkedin']) ? filter_var(trim($data['linkedin']), FILTER_SANITIZE_URL) : '';
$portfolio = isset($data['portfolio']) ? filter_var(trim($data['portfolio']), FILTER_SANITIZE_URL) : '';
$key_skills = isset($data['key_skills']) ? strip_tags(trim($data['key_skills'])) : '';
$cover_letter = isset($data['cover_letter']) ? strip_tags(trim($data['cover_letter'])) : '';
$resume_url = isset($data['resume_url']) ? filter_var(trim($data['resume_url']), FILTER_SANITIZE_URL) : '';
$application_number = isset($data['application_number']) ? strip_tags(trim($data['application_number'])) : '';

if (empty($name) || empty($email) || empty($phone) || empty($job_title) || empty($resume_url) || empty($application_number)) {
    http_response_code(400);
    echo "Missing required application details.";
    exit;
}

require_once __DIR__ . '/resend_helper.php';

// =========================================================================
// 1. APPLICANT CONFIRMATION EMAIL
// =========================================================================
$applicant_subject = "Application Received - " . $application_number . " | Luxury Homes of India";
$applicant_body = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background: #0f1012; color: #ffffff; border: 1px solid #1f2022; border-radius: 8px;'>
        <div style='text-align: center; margin-bottom: 25px;'>
            <h2 style='color: #c5a880; margin: 0 0 10px 0; font-family: serif;'>Luxury Homes of India</h2>
            <p style='color: #808080; font-size: 14px; margin: 0;'>Bespoke Luxury Residential Architecture</p>
        </div>
        <hr style='border: 0; border-top: 1px solid #1f2022; margin-bottom: 25px;'>
        <h3 style='color: #ffffff; margin-top: 0;'>Dear " . htmlspecialchars($name) . ",</h3>
        <p style='color: #d0d0d0; line-height: 1.6;'>Thank you for applying for the <strong>" . htmlspecialchars($job_title) . "</strong> position at Luxury Homes of India. We have successfully received your application.</p>
        
        <div style='background: #141517; border: 1px solid #1f2022; border-radius: 6px; padding: 15px 20px; margin: 20px 0;'>
            <p style='margin: 0 0 8px 0; color: #808080;'><strong>Application Details:</strong></p>
            <p style='margin: 0 0 6px 0; color: #d0d0d0;'><strong>Application No:</strong> " . htmlspecialchars($application_number) . "</p>
            <p style='margin: 0; color: #d0d0d0;'><strong>Job Position:</strong> " . htmlspecialchars($job_title) . "</p>
        </div>
        
        <p style='color: #d0d0d0; line-height: 1.6;'>Our HR and technical team are currently reviewing candidate profiles. If your qualifications and experiences align with our requirements, we will contact you directly to schedule an interview.</p>
        <p style='color: #d0d0d0; line-height: 1.6;'>Please keep this email for your reference.</p>
        
        <hr style='border: 0; border-top: 1px solid #1f2022; margin: 25px 0;'>
        <div style='text-align: center; color: #808080; font-size: 12px;'>
            <p style='margin: 0;'>This is an automated confirmation email. Please do not reply directly to this message.</p>
            <p style='margin: 5px 0 0 0;'>&copy; " . date('Y') . " Luxury Homes of India. All rights reserved.</p>
        </div>
    </div>
";

// =========================================================================
// 2. HR ALERT EMAIL
// =========================================================================
$hr_subject = "New Recruitment Application - " . htmlspecialchars($job_title) . " | " . htmlspecialchars($name) . " [" . htmlspecialchars($application_number) . "]";
$hr_body = "
    <div style='font-family: Arial, sans-serif; max-width: 650px; margin: 0 auto; padding: 20px; background: #ffffff; color: #333333; border: 1px solid #e0e0e0; border-radius: 8px;'>
        <h2 style='color: #0f1012; margin-top: 0; font-family: serif; border-bottom: 2px solid #c5a880; padding-bottom: 10px;'>New Job Application Alert</h2>
        
        <table style='width: 100%; border-collapse: collapse; margin-top: 20px;'>
            <tr style='background: #f9f9f9;'>
                <td style='padding: 10px; border-bottom: 1px solid #eee; font-weight: bold; width: 180px;'>Application No:</td>
                <td style='padding: 10px; border-bottom: 1px solid #eee;'>" . htmlspecialchars($application_number) . "</td>
            </tr>
            <tr>
                <td style='padding: 10px; border-bottom: 1px solid #eee; font-weight: bold;'>Applying For:</td>
                <td style='padding: 10px; border-bottom: 1px solid #eee;'>" . htmlspecialchars($job_title) . "</td>
            </tr>
            <tr style='background: #f9f9f9;'>
                <td style='padding: 10px; border-bottom: 1px solid #eee; font-weight: bold;'>Full Name:</td>
                <td style='padding: 10px; border-bottom: 1px solid #eee;'>" . htmlspecialchars($name) . "</td>
            </tr>
            <tr>
                <td style='padding: 10px; border-bottom: 1px solid #eee; font-weight: bold;'>Email:</td>
                <td style='padding: 10px; border-bottom: 1px solid #eee;'><a href='mailto:" . htmlspecialchars($email) . "'>" . htmlspecialchars($email) . "</a></td>
            </tr>
            <tr style='background: #f9f9f9;'>
                <td style='padding: 10px; border-bottom: 1px solid #eee; font-weight: bold;'>Phone:</td>
                <td style='padding: 10px; border-bottom: 1px solid #eee;'>" . htmlspecialchars($phone) . "</td>
            </tr>
            <tr>
                <td style='padding: 10px; border-bottom: 1px solid #eee; font-weight: bold;'>Location/City:</td>
                <td style='padding: 10px; border-bottom: 1px solid #eee;'>" . htmlspecialchars($city) . "</td>
            </tr>
            <tr style='background: #f9f9f9;'>
                <td style='padding: 10px; border-bottom: 1px solid #eee; font-weight: bold;'>Total Experience:</td>
                <td style='padding: 10px; border-bottom: 1px solid #eee;'>" . htmlspecialchars($experience) . "</td>
            </tr>
            <tr>
                <td style='padding: 10px; border-bottom: 1px solid #eee; font-weight: bold;'>Current Company:</td>
                <td style='padding: 10px; border-bottom: 1px solid #eee;'>" . htmlspecialchars($current_company) . "</td>
            </tr>
            <tr style='background: #f9f9f9;'>
                <td style='padding: 10px; border-bottom: 1px solid #eee; font-weight: bold;'>Current Designation:</td>
                <td style='padding: 10px; border-bottom: 1px solid #eee;'>" . htmlspecialchars($current_designation) . "</td>
            </tr>
            <tr>
                <td style='padding: 10px; border-bottom: 1px solid #eee; font-weight: bold;'>Notice Period:</td>
                <td style='padding: 10px; border-bottom: 1px solid #eee;'>" . htmlspecialchars($notice_period) . "</td>
            </tr>
            <tr style='background: #f9f9f9;'>
                <td style='padding: 10px; border-bottom: 1px solid #eee; font-weight: bold;'>Highest Qualification:</td>
                <td style='padding: 10px; border-bottom: 1px solid #eee;'>" . htmlspecialchars($highest_qualification) . "</td>
            </tr>
            <tr>
                <td style='padding: 10px; border-bottom: 1px solid #eee; font-weight: bold;'>LinkedIn Profile:</td>
                <td style='padding: 10px; border-bottom: 1px solid #eee;'><a href='" . htmlspecialchars($linkedin) . "' target='_blank'>" . htmlspecialchars($linkedin) . "</a></td>
            </tr>
            <tr style='background: #f9f9f9;'>
                <td style='padding: 10px; border-bottom: 1px solid #eee; font-weight: bold;'>Portfolio Link:</td>
                <td style='padding: 10px; border-bottom: 1px solid #eee;'><a href='" . htmlspecialchars($portfolio) . "' target='_blank'>" . htmlspecialchars($portfolio) . "</a></td>
            </tr>
            <tr>
                <td style='padding: 10px; border-bottom: 1px solid #eee; font-weight: bold;'>Key Skills:</td>
                <td style='padding: 10px; border-bottom: 1px solid #eee;'>" . htmlspecialchars($key_skills) . "</td>
            </tr>
        </table>
        
        <div style='margin-top: 20px; padding: 15px; background: #f5f5f5; border-radius: 6px;'>
            <p style='margin: 0 0 8px 0; font-weight: bold;'>Cover Letter:</p>
            <p style='margin: 0; white-space: pre-wrap; line-height: 1.5; font-size: 14px;'>" . htmlspecialchars($cover_letter) . "</p>
        </div>
        
        <div style='margin-top: 25px; text-align: center;'>
            <a href='" . htmlspecialchars($resume_url) . "' target='_blank' style='background: #0f1012; color: #ffffff; padding: 12px 25px; text-decoration: none; border-radius: 4px; font-weight: bold; display: inline-block; border: 1px solid #c5a880;'>Download / View Candidate Resume</a>
        </div>
    </div>
";

// Prepare attachment for HR email (use standard file upload if present, fallback to URL download)
$attachments = [];
if (isset($_FILES['resume_file']) && $_FILES['resume_file']['error'] === UPLOAD_ERR_OK) {
    $file_tmp = $_FILES['resume_file']['tmp_name'];
    $file_name = $_FILES['resume_file']['name'];
    $file_data = @file_get_contents($file_tmp);
    if (!empty($file_data)) {
        $clean_name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $name);
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        if (empty($file_ext)) {
            $file_ext = 'pdf';
        }
        $attachment_filename = $application_number . "_" . $clean_name . "_Resume." . $file_ext;
        
        $attachments[] = [
            'filename' => $attachment_filename,
            'content' => base64_encode($file_data)
        ];
    }
} elseif (!empty($resume_url)) {
    $ch = curl_init($resume_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $file_data = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code === 200 && !empty($file_data)) {
        $url_path = parse_url($resume_url, PHP_URL_PATH);
        $file_ext = strtolower(pathinfo($url_path, PATHINFO_EXTENSION));
        if (empty($file_ext)) {
            $file_ext = 'pdf';
        }
        
        $clean_name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $name);
        $attachment_filename = $application_number . "_" . $clean_name . "_Resume." . $file_ext;

        $attachments[] = [
            'filename' => $attachment_filename,
            'content' => base64_encode($file_data)
        ];
    }
}

// Send candidate confirmation email
$applicant_sent = send_via_resend($email, $applicant_subject, $applicant_body);

// Send HR alert email (to both requested emails, with resume attached)
$hr_to = "info@luxuryhomesofindia.com, luxuryhomesofindia@gmail.com";
$hr_sent = send_via_resend($hr_to, $hr_subject, $hr_body, $email, $attachments);

header('Content-Type: application/json');
if ($applicant_sent && $hr_sent) {
    http_response_code(200);
    echo json_encode(["status" => "success", "message" => "Emails sent successfully."]);
} else {
    http_response_code(200); // return 200 with partial failure flags so the client can handle gracefully
    echo json_encode(["status" => "partial_failure", "applicant_sent" => $applicant_sent, "hr_sent" => $hr_sent]);
}
?>
