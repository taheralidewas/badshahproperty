<?php
/**
 * Quick Test Script for Vonage SMS
 * Open in browser: http://localhost/Badshah Property/test-sms.php
 */

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Vonage SMS Test - Badshah Property</title>
    <style>
        body { font-family: Arial; padding: 40px; background: #f5f5dc; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        h1 { color: #2d5016; }
        .status { padding: 15px; margin: 20px 0; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        button { background: #d2691e; color: white; padding: 12px 24px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
        button:hover { background: #8b4513; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🏡 Badshah Property - SMS Test</h1>
        
        <?php
        // Configuration comes from secrets.php (gitignored).
        $secretsFile = __DIR__ . '/secrets.php';

        if (!file_exists($secretsFile)) {
            echo '<div class="error"><strong>secrets.php is missing.</strong><br>'
               . 'Copy secrets.example.php to secrets.php and add your credentials.</div>';
            echo '</div></body></html>';
            exit;
        }

        $secrets = require $secretsFile;

        $VONAGE_API_KEY = $secrets['vonage_key'];
        $VONAGE_API_SECRET = $secrets['vonage_secret'];
        $VONAGE_FROM = $secrets['vonage_from'];
        $OWNER_PHONE = $secrets['owner_phone'];
        
        echo '<div class="info">';
        echo '<strong>✅ Configuration Loaded:</strong><br>';
        // Only show a masked fragment; never print the full key or the secret.
        echo 'API Key: ' . substr($VONAGE_API_KEY, 0, 3) . str_repeat('*', max(0, strlen($VONAGE_API_KEY) - 3)) . '<br>';
        echo 'From: ' . $VONAGE_FROM . '<br>';
        echo 'To: ' . $OWNER_PHONE . '<br>';
        echo '</div>';
        
        // Check if test button was clicked
        if (isset($_POST['send_test'])) {
            $testMessage = "TEST SMS from Badshah Property Website\n\n";
            $testMessage .= "This is a test message to verify SMS is working.\n";
            $testMessage .= "Time: " . date('Y-m-d H:i:s') . "\n\n";
            $testMessage .= "If you receive this, SMS system is working perfectly!";
            
            // Send SMS using Vonage
            $url = 'https://rest.nexmo.com/sms/json';
            
            $data = [
                'api_key' => $VONAGE_API_KEY,
                'api_secret' => $VONAGE_API_SECRET,
                'from' => $VONAGE_FROM,
                'to' => $OWNER_PHONE,
                'text' => $testMessage
            ];
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if (curl_errno($ch)) {
                echo '<div class="error">';
                echo '<strong>❌ CURL Error:</strong><br>';
                echo curl_error($ch);
                echo '</div>';
            } else {
                $result = json_decode($response, true);
                
                if (isset($result['messages'][0]['status']) && $result['messages'][0]['status'] == '0') {
                    echo '<div class="success">';
                    echo '<strong>✅ SMS SENT SUCCESSFULLY!</strong><br><br>';
                    echo 'Message ID: ' . $result['messages'][0]['message-id'] . '<br>';
                    echo 'To: ' . $result['messages'][0]['to'] . '<br>';
                    echo 'Remaining Balance: €' . (isset($result['messages'][0]['remaining-balance']) ? $result['messages'][0]['remaining-balance'] : 'N/A') . '<br>';
                    echo '<br><strong>Check your phone (9893372352) for the test SMS!</strong>';
                    echo '</div>';
                } else {
                    echo '<div class="error">';
                    echo '<strong>❌ SMS Failed:</strong><br>';
                    echo 'Status: ' . $result['messages'][0]['status'] . '<br>';
                    echo 'Error: ' . (isset($result['messages'][0]['error-text']) ? $result['messages'][0]['error-text'] : 'Unknown error') . '<br>';
                    echo '<br>Response: <pre>' . print_r($result, true) . '</pre>';
                    echo '</div>';
                }
            }
            
            curl_close($ch);
        }
        
        // Check if curl is enabled
        if (!function_exists('curl_init')) {
            echo '<div class="error">';
            echo '<strong>❌ CURL NOT ENABLED!</strong><br>';
            echo 'Please enable php_curl extension in WAMP:<br>';
            echo '1. Click WAMP icon → PHP → PHP Extensions<br>';
            echo '2. Check php_curl<br>';
            echo '3. Restart WAMP';
            echo '</div>';
        } else {
            echo '<div class="success">';
            echo '✅ CURL is enabled';
            echo '</div>';
        }
        ?>
        
        <form method="POST">
            <button type="submit" name="send_test">📱 Send Test SMS to 8770576053</button>
        </form>
        
        <div style="margin-top: 30px; padding: 20px; background: #fff3cd; border: 1px solid #ffc107; border-radius: 5px;">
            <strong>💡 Next Steps:</strong>
            <ol>
                <li>Click the button above to send a test SMS</li>
                <li>Check your phone (8770576053) for the message</li>
                <li>If successful, your contact form will work automatically!</li>
                <li>Go back to your website and test the contact form</li>
            </ol>
        </div>
    </div>
</body>
</html>


