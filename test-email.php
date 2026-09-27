<?php
/**
 * Badshah Property - Email test page
 * Open in browser: http://localhost/Badshah%20Property/test-email.php
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/send-email.php';
$config = require __DIR__ . '/mail-config.php';

$result = null;
if (isset($_GET['send'])) {
    $result = bp_send_email(
        $config,
        'Test Email - Badshah Property',
        "This is a test email from your Badshah Property website.\n\n"
        . "If you are reading this, SMTP is working and the contact form\n"
        . "will now deliver inquiries to " . $config['to'] . ".\n\n"
        . 'Sent at: ' . date('Y-m-d H:i:s'),
        ''
    );
}

$configured = strpos($config['user'], 'YOUR_EMAIL') === false
    && strpos($config['pass'], 'YOUR_16_CHAR') === false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Test - Badshah Property</title>
    <style>
        body { font-family: system-ui, Arial, sans-serif; max-width: 720px; margin: 40px auto; padding: 0 20px; color: #1f2937; line-height: 1.6; }
        h1 { color: #166534; }
        .box { border: 1px solid #d1d5db; border-radius: 8px; padding: 16px 20px; margin: 20px 0; background: #f9fafb; }
        .ok { border-color: #16a34a; background: #f0fdf4; color: #15803d; }
        .bad { border-color: #dc2626; background: #fef2f2; color: #b91c1c; }
        .warn { border-color: #d97706; background: #fffbeb; color: #b45309; }
        code { background: #e5e7eb; padding: 2px 6px; border-radius: 4px; }
        a.btn { display: inline-block; background: #166534; color: #fff; padding: 12px 24px; border-radius: 6px; text-decoration: none; font-weight: 600; }
        a.btn:hover { background: #14532d; }
        dt { font-weight: 600; }
        dd { margin: 0 0 8px 0; }
    </style>
</head>
<body>
    <h1>Email (SMTP) Test</h1>

    <div class="box">
        <dl>
            <dt>SMTP host</dt><dd><code><?= htmlspecialchars($config['host'] . ':' . $config['port']) ?></code> (<?= htmlspecialchars($config['encryption']) ?>)</dd>
            <dt>Login user</dt><dd><code><?= htmlspecialchars($config['user']) ?></code></dd>
            <dt>Password</dt><dd><?= $configured ? 'set (hidden)' : '<em>not set</em>' ?></dd>
            <dt>Sends to</dt><dd><code><?= htmlspecialchars($config['to']) ?></code></dd>
        </dl>
    </div>

<?php if (!$configured): ?>
    <div class="box warn">
        <strong>Not configured yet.</strong><br>
        Open <code>mail-config.php</code> and fill in <code>user</code>, <code>pass</code> and <code>from</code>.
        For Gmail, create an App Password at
        <a href="https://myaccount.google.com/apppasswords" target="_blank" rel="noopener">myaccount.google.com/apppasswords</a>
        (2-Step Verification must be on first).
    </div>
<?php endif; ?>

<?php if ($result !== null): ?>
    <?php if ($result['success']): ?>
        <div class="box ok"><strong>Success.</strong> Email accepted by the SMTP server. Check the inbox for <?= htmlspecialchars($config['to']) ?> (and the spam folder).</div>
    <?php else: ?>
        <div class="box bad"><strong>Failed.</strong><br><?= htmlspecialchars($result['error']) ?></div>
    <?php endif; ?>
<?php endif; ?>

    <p><a class="btn" href="?send=1">Send test email</a></p>

    <div class="box">
        <strong>Common failures</strong>
        <ul>
            <li><em>Could not connect</em> — your network or antivirus is blocking outbound port <?= (int) $config['port'] ?>. Try port 465 with <code>ssl</code>.</li>
            <li><em>Authentication failed</em> — you used your normal Gmail password. You need a 16-character App Password.</li>
            <li><em>STARTTLS handshake failed</em> — enable the <code>openssl</code> extension in <code>php.ini</code>, then restart WAMP.</li>
        </ul>
    </div>
</body>
</html>
