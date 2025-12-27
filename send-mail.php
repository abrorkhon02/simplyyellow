<?php
/**
 * Simply Yellow Catering - Form Handler
 * Processes inquiry form submissions and sends email notifications
 */

// ============================================
// CONFIGURATION - Update these values
// ============================================
$recipient_email = "catering@simplyyellow.de";  // Where to receive form submissions
$email_subject_prefix = "[Simply Yellow] ";   // Prefix for email subject
// ============================================

// Set headers for JSON response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Get form data
$data = $_POST;

// If JSON was sent, decode it
$content_type = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
if (strpos($content_type, 'application/json') !== false) {
    $data = json_decode(file_get_contents('php://input'), true);
}

// Validate required fields
$required_fields = ['name', 'email', 'phone'];
foreach ($required_fields as $field) {
    if (empty($data[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
        exit();
    }
}

// Sanitize inputs
$name = htmlspecialchars(strip_tags($data['name'] ?? ''));
$email = filter_var($data['email'] ?? '', FILTER_SANITIZE_EMAIL);
$phone = htmlspecialchars(strip_tags($data['phone'] ?? ''));
$company = htmlspecialchars(strip_tags($data['company'] ?? 'Not provided'));
$eventType = htmlspecialchars(strip_tags($data['eventType'] ?? 'Not specified'));
$serviceFocus = is_array($data['serviceFocus'] ?? '') ? implode(', ', $data['serviceFocus']) : ($data['serviceFocus'] ?? 'Not specified');
$experienceAddOns = is_array($data['experienceAddOns'] ?? '') ? implode(', ', $data['experienceAddOns']) : ($data['experienceAddOns'] ?? 'None');
$eventDate = htmlspecialchars(strip_tags($data['eventDate'] ?? 'Not specified'));
$eventTime = htmlspecialchars(strip_tags($data['eventTime'] ?? 'Not specified'));
$guestCount = htmlspecialchars(strip_tags($data['guestCount'] ?? 'Not specified'));
$postalCode = htmlspecialchars(strip_tags($data['postalCode'] ?? 'Not specified'));
$menuFormats = is_array($data['menuFormats'] ?? '') ? implode(', ', $data['menuFormats']) : ($data['menuFormats'] ?? 'Not specified');
$specialRequests = htmlspecialchars(strip_tags($data['specialRequests'] ?? 'None'));
$needStaff = htmlspecialchars(strip_tags($data['needStaff'] ?? 'Not specified'));
$language = htmlspecialchars(strip_tags($data['language'] ?? 'en'));

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid email address']);
    exit();
}

// Build email subject
$subject = $email_subject_prefix . "New Inquiry from $name";

// Build HTML email body
$email_body = "
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #FFD54F 0%, #FFC107 100%); padding: 30px; border-radius: 10px 10px 0 0; }
        .header h1 { margin: 0; color: #1a1a1a; font-size: 24px; }
        .content { background: #f9f9f9; padding: 30px; border: 1px solid #e0e0e0; border-top: none; border-radius: 0 0 10px 10px; }
        .section { margin-bottom: 25px; }
        .section-title { font-size: 14px; font-weight: 600; color: #666; text-transform: uppercase; margin-bottom: 10px; border-bottom: 2px solid #FFD54F; padding-bottom: 5px; }
        .field { margin-bottom: 12px; }
        .label { font-weight: 600; color: #555; }
        .value { color: #333; }
        .highlight { background: #fff; padding: 15px; border-radius: 8px; border-left: 4px solid #FFC107; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>☀️ New Catering Inquiry</h1>
        </div>
        <div class='content'>
            <div class='section'>
                <div class='section-title'>Contact Information</div>
                <div class='highlight'>
                    <div class='field'><span class='label'>Name:</span> <span class='value'>$name</span></div>
                    <div class='field'><span class='label'>Email:</span> <span class='value'><a href='mailto:$email'>$email</a></span></div>
                    <div class='field'><span class='label'>Phone:</span> <span class='value'><a href='tel:$phone'>$phone</a></span></div>
                    <div class='field'><span class='label'>Company:</span> <span class='value'>$company</span></div>
                </div>
            </div>
            
            <div class='section'>
                <div class='section-title'>Event Details</div>
                <div class='field'><span class='label'>Event Type:</span> <span class='value'>$eventType</span></div>
                <div class='field'><span class='label'>Service Focus:</span> <span class='value'>$serviceFocus</span></div>
                <div class='field'><span class='label'>Add-ons:</span> <span class='value'>$experienceAddOns</span></div>
                <div class='field'><span class='label'>Date:</span> <span class='value'>$eventDate</span></div>
                <div class='field'><span class='label'>Time:</span> <span class='value'>$eventTime</span></div>
                <div class='field'><span class='label'>Guests:</span> <span class='value'>$guestCount</span></div>
                <div class='field'><span class='label'>Location (PLZ):</span> <span class='value'>$postalCode</span></div>
            </div>
            
            <div class='section'>
                <div class='section-title'>Menu & Requirements</div>
                <div class='field'><span class='label'>Menu Format:</span> <span class='value'>$menuFormats</span></div>
                <div class='field'><span class='label'>Staff Needed:</span> <span class='value'>$needStaff</span></div>
                <div class='field'><span class='label'>Special Requests:</span><br><span class='value'>$specialRequests</span></div>
            </div>
            
            <div class='section' style='font-size: 12px; color: #888; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e0e0e0;'>
                <p>This inquiry was submitted from the Simply Yellow website.</p>
                <p>Language: " . strtoupper($language) . " | Submitted: " . date('Y-m-d H:i:s') . "</p>
            </div>
        </div>
    </div>
</body>
</html>
";

// Email headers
$headers = "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";
$headers .= "From: Simply Yellow Website <noreply@" . $_SERVER['HTTP_HOST'] . ">\r\n";
$headers .= "Reply-To: $name <$email>\r\n";
$headers .= "X-Mailer: PHP/" . phpversion();

// Send email
$mail_sent = mail($recipient_email, $subject, $email_body, $headers);

if ($mail_sent) {
    echo json_encode([
        'success' => true, 
        'message' => $language === 'de' 
            ? 'Vielen Dank! Wir melden uns innerhalb von zwei Werktagen bei Ihnen.' 
            : 'Thank you! We will get back to you within two business days.'
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to send email. Please try again or contact us directly.']);
}
?>
