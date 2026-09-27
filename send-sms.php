<?php
/**
 * Badshah Property - SMS Notification System using Vonage
 * Sends SMS to property owner when contact form is submitted
 * 
 * Vonage offers €2 FREE credit = 50-100 FREE SMS!
 * Sign up: https://dashboard.nexmo.com/sign-up
 */

// This endpoint must return JSON only. Printing PHP warnings/notices into the
// response body breaks response.json() on the front end, so log them instead.
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php-errors.log');

require_once __DIR__ . '/send-email.php';

// Enable CORS for local development
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// ===== VONAGE CONFIGURATION (€2 FREE CREDIT) =====
// Sign up at: https://dashboard.nexmo.com/sign-up
// You get €2 FREE credit = approximately 50-100 SMS!

// Credentials are loaded from secrets.php, which is gitignored so that API keys
// never end up in version control. Copy secrets.example.php to secrets.php.
$secretsFile = __DIR__ . '/secrets.php';

if (!file_exists($secretsFile)) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server is not configured. Copy secrets.example.php to secrets.php and add your credentials.',
    ]);
    exit;
}

$secrets = require $secretsFile;

$VONAGE_API_KEY    = $secrets['vonage_key'];
$VONAGE_API_SECRET = $secrets['vonage_secret'];
$VONAGE_FROM       = $secrets['vonage_from'];    // Brand name (max 11 chars) or phone number

// Owner's phone number (country code, no + sign for Vonage)
$OWNER_PHONE       = $secrets['owner_phone'];

// Get form data
$rawData = file_get_contents('php://input');
$data = json_decode($rawData, true);

if (!$data) {
    $data = $_POST;
}

$name = isset($data['name']) ? htmlspecialchars($data['name']) : '';
$phone = isset($data['phone']) ? htmlspecialchars($data['phone']) : '';
$email = isset($data['email']) ? htmlspecialchars($data['email']) : 'Not provided';
$area = isset($data['area']) ? htmlspecialchars($data['area']) : 'Not specified';
$interest = isset($data['interest']) ? htmlspecialchars($data['interest']) : 'Not specified';
$budget = isset($data['budget']) ? htmlspecialchars($data['budget']) : 'Not specified';
$message = isset($data['message']) ? htmlspecialchars($data['message']) : 'No message';

// Validate required fields
if (empty($name) || empty($phone)) {
    echo json_encode(['success' => false, 'message' => 'Name and phone are required']);
    exit;
}

// Prepare SMS message for owner
$smsMessage = "New Property Inquiry - Badshah Property\n\n";
$smsMessage .= "Name: $name\n";
$smsMessage .= "Phone: $phone\n";
$smsMessage .= "Email: $email\n";
$smsMessage .= "Area: $area\n";
$smsMessage .= "Interest: $interest\n";
$smsMessage .= "Budget: $budget\n";
$smsMessage .= "Message: $message\n\n";
$smsMessage .= "Contact customer immediately!";

/**
 * Send SMS using Vonage API
 */
function sendSMS_Vonage($apiKey, $apiSecret, $from, $to, $message) {
    $url = 'https://rest.nexmo.com/sms/json';
    
    $data = [
        'api_key' => $apiKey,
        'api_secret' => $apiSecret,
        'from' => $from,
        'to' => $to,
        'text' => $message,
        'type' => 'unicode'  // Support for all characters
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        return ['success' => false, 'error' => $error];
    }
    
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    if (isset($result['messages'][0]['status']) && $result['messages'][0]['status'] == '0') {
        return [
            'success' => true,
            'message_id' => $result['messages'][0]['message-id'],
            'remaining_balance' => isset($result['messages'][0]['remaining-balance']) ? $result['messages'][0]['remaining-balance'] : 'N/A'
        ];
    } else {
        return [
            'success' => false,
            'error' => isset($result['messages'][0]['error-text']) ? $result['messages'][0]['error-text'] : 'Unknown error'
        ];
    }
}

// ===== SAVE TO LOG FILE =====
function saveInquiryToLog($data) {
    $logFile = 'inquiries.json';
    
    // Add timestamp
    $data['timestamp'] = date('Y-m-d H:i:s');
    
    // Load existing inquiries
    $inquiries = [];
    if (file_exists($logFile)) {
        $content = file_get_contents($logFile);
        $inquiries = json_decode($content, true) ?: [];
    }
    
    // Add new inquiry
    $inquiries[] = $data;
    
    // Save back to file
    file_put_contents($logFile, json_encode($inquiries, JSON_PRETTY_PRINT));
    
    return true;
}

// Save inquiry to log file
$inquiryData = [
    'name' => $name,
    'phone' => $phone,
    'email' => $email,
    'area' => $area,
    'interest' => $interest,
    'budget' => $budget,
    'message' => $message
];
saveInquiryToLog($inquiryData);

// ===== SEND SMS USING VONAGE =====
$smsSent = false;
$smsError = '';
$smsId = '';
$remainingBalance = '';

// Check if Vonage is configured
if ($VONAGE_API_KEY !== 'YOUR_VONAGE_API_KEY' && $VONAGE_API_SECRET !== 'YOUR_VONAGE_API_SECRET') {
    $smsResult = sendSMS_Vonage($VONAGE_API_KEY, $VONAGE_API_SECRET, $VONAGE_FROM, $OWNER_PHONE, $smsMessage);
    
    if ($smsResult['success']) {
        $smsSent = true;
        $smsId = $smsResult['message_id'];
        $remainingBalance = $smsResult['remaining_balance'];
    } else {
        $smsError = $smsResult['error'];
    }
}

// ===== SEND EMAIL NOTIFICATION (via SMTP) =====
// mail() is not used: WAMP has no local mail server on port 25, so it always
// fails and emits a warning. send-email.php talks to a real SMTP server.
$emailSent = false;
$emailError = '';

try {
    $mailConfig = require __DIR__ . '/mail-config.php';

    $replyTo = filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : '';

    // Show the inquiry as coming from the visitor in the inbox list, and make
    // Reply go straight to them.
    //
    // The From ADDRESS must stay as the authenticated account: Gmail rejects
    // sending as an address you do not own, and SPF/DMARC would flag it as
    // spoofing. Only the display NAME carries the visitor's identity.
    $mailConfig['from_name'] = $name . ' via Badshah Property';

    $emailResult = bp_send_email(
        $mailConfig,
        "New Property Inquiry - $name",
        $smsMessage,
        $replyTo
    );

    $emailSent  = $emailResult['success'];
    $emailError = $emailResult['error'];
} catch (Throwable $e) {
    // Email failed, but the inquiry is already saved to the log.
    $emailError = $e->getMessage();
}

if (!$emailSent && $emailError !== '') {
    error_log('[Badshah Property] Email failed: ' . $emailError);
}

// ===== SEND RESPONSE =====
if ($smsSent) {
    echo json_encode([
        'success' => true,
        'message' => 'Thank you! SMS sent to our team. We will contact you within 24 hours.',
        'sms_sent' => true,
        'sms_id' => $smsId,
        'remaining_balance' => $remainingBalance,
        'email_sent' => $emailSent,
        'email_error' => $emailError,
        'saved_to_log' => true
    ]);
} else if ($VONAGE_API_KEY === 'YOUR_VONAGE_API_KEY') {
    // Vonage not configured yet
    echo json_encode([
        'success' => true,
        'message' => 'Thank you! Your inquiry has been saved. We will contact you within 24 hours.',
        'sms_sent' => false,
        'note' => 'SMS service not configured yet. Please set up Vonage API credentials.',
        'email_sent' => $emailSent,
        'email_error' => $emailError,
        'saved_to_log' => true
    ]);
} else {
    // SMS failed but inquiry saved
    echo json_encode([
        'success' => true,
        'message' => 'Thank you! Your inquiry has been received. We will contact you within 24 hours.',
        'sms_sent' => false,
        'sms_error' => $smsError,
        'email_sent' => $emailSent,
        'email_error' => $emailError,
        'saved_to_log' => true
    ]);
}
?>

