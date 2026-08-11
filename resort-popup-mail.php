<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $to = "info@luxuryhomesofindia.in";
    $subject = "Lead from home page resort popup";

    function clean($data){
        return htmlspecialchars(trim($data));
    }

    $name        = clean($_POST['name'] ?? '');
    $mobile      = clean($_POST['mobile'] ?? '');
    $email       = clean($_POST['email'] ?? '');
    $location    = clean($_POST['location'] ?? '');
    $projectType = clean($_POST['project_type'] ?? '');
    $budget      = clean($_POST['budget'] ?? '');
    $requirement = clean($_POST['requirement'] ?? '');

    $message = "
New Lead Received from Resort Popup

Name: $name
Mobile: $mobile
Email: $email
Location: $location
Project Type: $projectType
Budget: $budget

Requirement:
$requirement
";

    $headers  = "From: Luxury Homes Website <noreply@luxuryhomesofindia.in>\r\n";
    $headers .= "Reply-To: $email\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    if(mail($to, $subject, $message, $headers)){
        echo "success";
    } else {
        echo "error";
    }
}
?>