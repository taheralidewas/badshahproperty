<?php
/**
 * Badshah Property - Email (SMTP) configuration
 *
 * Credentials live in secrets.php, which is gitignored. Copy
 * secrets.example.php to secrets.php and fill it in before using this.
 *
 * PHP's built-in mail() does NOT work on WAMP/localhost because there is no
 * local mail server on port 25. Real delivery requires an SMTP account.
 */

$secretsFile = __DIR__ . '/secrets.php';

if (!file_exists($secretsFile)) {
    throw new RuntimeException(
        'secrets.php is missing. Copy secrets.example.php to secrets.php and add your credentials.'
    );
}

$secrets = require $secretsFile;

return [
    // SMTP server settings (Gmail shown; see alternatives below)
    'host'       => 'smtp.gmail.com',
    'port'       => 587,
    'encryption' => 'tls',            // 'tls' for port 587, 'ssl' for port 465

    // Your SMTP login
    'user'       => $secrets['smtp_user'],
    'pass'       => $secrets['smtp_pass'],

    // Envelope
    'from'       => $secrets['smtp_from'],
    'from_name'  => 'Badshah Property Website',

    // Where inquiry notifications are delivered
    'to'         => $secrets['notify_to'],

    // Connection timeout in seconds
    'timeout'    => 15,
];

/*
 * OTHER PROVIDERS
 *
 * Outlook / Hotmail:  host smtp-mail.outlook.com  port 587  encryption tls
 * Zoho:               host smtp.zoho.in           port 587  encryption tls
 * Brevo (Sendinblue): host smtp-relay.brevo.com   port 587  encryption tls
 * Hostinger/cPanel:   host smtp.yourdomain.com    port 465  encryption ssl
 */
