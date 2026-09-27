<?php
/**
 * Badshah Property - credentials TEMPLATE
 *
 * Copy this file to `secrets.php` and fill in the real values.
 * `secrets.php` is listed in .gitignore and must never be committed.
 *
 *     copy secrets.example.php secrets.php
 *
 * ---- GMAIL APP PASSWORD ----
 * 1. Turn ON 2-Step Verification: https://myaccount.google.com/security
 * 2. Create an App Password:      https://myaccount.google.com/apppasswords
 * 3. Paste the 16-character code as 'smtp_pass' below.
 *    A normal Google account password will NOT work over SMTP.
 *
 * ---- VONAGE SMS ----
 * Dashboard: https://dashboard.nexmo.com/
 * Copy the API key and secret from the dashboard home page.
 */

return [
    // Gmail / SMTP
    'smtp_user' => 'YOUR_EMAIL@gmail.com',
    'smtp_pass' => 'YOUR_16_CHAR_APP_PASSWORD',
    'smtp_from' => 'YOUR_EMAIL@gmail.com',

    // Where inquiry notifications are delivered
    'notify_to' => 'YOUR_EMAIL@gmail.com',

    // Vonage SMS
    'vonage_key'    => 'YOUR_VONAGE_API_KEY',
    'vonage_secret' => 'YOUR_VONAGE_API_SECRET',
    'vonage_from'   => 'Badshah',
    'owner_phone'   => '91XXXXXXXXXX',
];
