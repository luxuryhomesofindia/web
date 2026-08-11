<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $to = "info@luxuryhomesofindia.in, luxuryhomesofindia@gmail.com";
    $subject = "New JV Form Submission";

    $name = $_POST["name"];
    $email = $_POST["email"];
    $phone = $_POST["phone"];
    $plot_address = $_POST["plot_address"];
    $type = isset($_POST["type"]) ? $_POST["type"] : "Not specified";
    $expected_ratio = $_POST["expected_ratio"];
    $land_size = $_POST["land_size"];
    $land_facing = $_POST["land_facing"];
    $road_width = $_POST["road_width"];

    $message = "
    New Joint Venture Submission:\n
    Full Name: $name\n
    Email: $email\n
    Phone: $phone\n
    Plot Address: $plot_address\n
    Ownership Type: $type\n
    Expected JV Ratio: $expected_ratio\n
    Land Size: $land_size\n
    Land Facing: $land_facing\n
    Road Width: $road_width
    ";

    $headers = "From: info@luxuryhomesofindia.in\r\n";
    $headers .= "Reply-To: " . $email . "\r\n";

    // Send separately to each recipient
    $success1 = mail("info@luxuryhomesofindia.in", $subject, $message, $headers);
    $success2 = mail("luxuryhomesofindia@gmail.com", $subject, $message, $headers);

    if ($success1 || $success2) {
        echo "success";
    } else {
        echo "fail";
    }
}
?>
