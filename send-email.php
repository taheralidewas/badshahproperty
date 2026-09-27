<?php
/**
 * Badshah Property - Minimal SMTP mailer (no Composer / no PHPMailer required).
 *
 * Talks SMTP directly over a socket, so it works on WAMP where mail() fails.
 * Returns ['success' => bool, 'error' => string].
 */

if (!function_exists('bp_send_email')) {

    /**
     * Read one full SMTP reply (handles multi-line replies like EHLO).
     */
    function bp_smtp_read($socket)
    {
        $data = '';
        while (($line = fgets($socket, 1024)) !== false) {
            $data .= $line;
            // Last line of a reply has a space (not a dash) at position 3.
            if (strlen($line) < 4 || $line[3] !== '-') {
                break;
            }
        }
        return $data;
    }

    /**
     * Send a command and assert the reply code.
     */
    function bp_smtp_cmd($socket, $command, $expected, &$error)
    {
        if ($command !== null) {
            fwrite($socket, $command . "\r\n");
        }
        $reply = bp_smtp_read($socket);
        $code  = (int) substr(trim($reply), 0, 3);

        if (!in_array($code, (array) $expected, true)) {
            // Never echo the AUTH payload back in an error message.
            $shown = (strpos($command ?? '', 'AUTH') === 0 || $expected === 334 || $expected === [334])
                ? '[auth step]'
                : trim((string) $command);
            $error = 'SMTP error after "' . $shown . '": ' . trim($reply);
            return false;
        }
        return true;
    }

    /**
     * Send an email via SMTP.
     *
     * @param array  $config  mail-config.php array
     * @param string $subject
     * @param string $body    plain text
     * @param string $replyTo optional reply-to address
     */
    function bp_send_email(array $config, $subject, $body, $replyTo = '')
    {
        $error = '';

        $host       = $config['host'];
        $port       = (int) $config['port'];
        $encryption = strtolower($config['encryption']);
        $timeout    = isset($config['timeout']) ? (int) $config['timeout'] : 15;

        if (strpos($config['user'], 'YOUR_EMAIL') !== false
            || strpos($config['pass'], 'YOUR_16_CHAR') !== false) {
            return [
                'success' => false,
                'error'   => 'SMTP not configured. Fill in your credentials in mail-config.php.',
            ];
        }

        // Google displays App Passwords in groups of four ("abcd efgh ijkl mnop").
        // The spaces are for readability only and must not be sent.
        $config['pass'] = preg_replace('/\s+/', '', $config['pass']);

        // Gmail rejects normal account passwords over SMTP (disabled May 2022),
        // so catch that before spending a round trip on it.
        $isGmail = stripos($config['host'], 'gmail.com') !== false;
        if ($isGmail && !preg_match('/^[a-z]{16}$/', $config['pass'])) {
            return [
                'success' => false,
                'error'   => 'This does not look like a Gmail App Password. Gmail requires a '
                    . '16-character App Password (lowercase letters only) — your normal Google '
                    . 'account password will always be rejected. Yours is '
                    . strlen($config['pass']) . ' characters. Generate one at '
                    . 'https://myaccount.google.com/apppasswords (2-Step Verification must be ON first).',
            ];
        }

        if ($isGmail && strcasecmp($config['user'], $config['from']) !== 0) {
            return [
                'success' => false,
                'error'   => 'Gmail requires "from" to be the same address as "user" in mail-config.php.',
            ];
        }

        // ssl:// connects encrypted immediately (port 465).
        // tls:// requires a plaintext connect then STARTTLS (port 587).
        $remote = ($encryption === 'ssl' ? 'ssl://' : '') . $host . ':' . $port;

        $context = stream_context_create([
            'ssl' => [
                'verify_peer'       => true,
                'verify_peer_name'  => true,
                'allow_self_signed' => false,
            ],
        ]);

        $socket = @stream_socket_client(
            $remote,
            $errNo,
            $errStr,
            $timeout,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (!$socket) {
            return [
                'success' => false,
                'error'   => 'Could not connect to ' . $remote . ' (' . $errNo . ': ' . $errStr . ')',
            ];
        }

        stream_set_timeout($socket, $timeout);

        $helo = 'localhost';

        // Greeting
        if (!bp_smtp_cmd($socket, null, 220, $error)) { fclose($socket); return ['success' => false, 'error' => $error]; }
        if (!bp_smtp_cmd($socket, 'EHLO ' . $helo, 250, $error)) { fclose($socket); return ['success' => false, 'error' => $error]; }

        if ($encryption === 'tls') {
            if (!bp_smtp_cmd($socket, 'STARTTLS', 220, $error)) { fclose($socket); return ['success' => false, 'error' => $error]; }

            if (!@stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                fclose($socket);
                return ['success' => false, 'error' => 'STARTTLS handshake failed.'];
            }
            // Must re-issue EHLO after upgrading the connection.
            if (!bp_smtp_cmd($socket, 'EHLO ' . $helo, 250, $error)) { fclose($socket); return ['success' => false, 'error' => $error]; }
        }

        // AUTH LOGIN
        if (!bp_smtp_cmd($socket, 'AUTH LOGIN', 334, $error)) { fclose($socket); return ['success' => false, 'error' => $error]; }
        if (!bp_smtp_cmd($socket, base64_encode($config['user']), 334, $error)) { fclose($socket); return ['success' => false, 'error' => 'Username rejected by server.']; }
        if (!bp_smtp_cmd($socket, base64_encode($config['pass']), 235, $error)) {
            fclose($socket);
            // $error holds the server's own reply, which names the real reason
            // (bad credentials, App Password required, account locked, etc.).
            return [
                'success' => false,
                'error'   => 'Authentication rejected by ' . $config['host'] . '. Server said: ' . $error,
            ];
        }

        // Envelope
        if (!bp_smtp_cmd($socket, 'MAIL FROM:<' . $config['from'] . '>', 250, $error)) { fclose($socket); return ['success' => false, 'error' => $error]; }
        if (!bp_smtp_cmd($socket, 'RCPT TO:<' . $config['to'] . '>', [250, 251], $error)) { fclose($socket); return ['success' => false, 'error' => $error]; }
        if (!bp_smtp_cmd($socket, 'DATA', 354, $error)) { fclose($socket); return ['success' => false, 'error' => $error]; }

        // Strip CR/LF from header values to prevent header injection.
        $clean = function ($value) {
            return trim(str_replace(["\r", "\n"], ' ', (string) $value));
        };

        // Display names come from visitor input, so they may contain commas,
        // quotes or non-ASCII characters. Anything outside plain ASCII gets
        // RFC 2047 encoded; otherwise it is quoted so specials stay literal.
        $encodeName = function ($name) use ($clean) {
            $name = $clean($name);
            if ($name === '') {
                return '';
            }
            if (preg_match('/[^\x20-\x7E]/', $name)) {
                return '=?UTF-8?B?' . base64_encode($name) . '?=';
            }
            return '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], $name) . '"';
        };

        $headers = [];
        $headers[] = 'Date: ' . date('r');
        $headers[] = 'From: ' . $encodeName($config['from_name']) . ' <' . $clean($config['from']) . '>';
        $headers[] = 'To: <' . $clean($config['to']) . '>';
        $headers[] = 'Subject: =?UTF-8?B?' . base64_encode($clean($subject)) . '?=';

        if ($replyTo !== '' && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
            $headers[] = 'Reply-To: <' . $clean($replyTo) . '>';
        }

        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: text/plain; charset=UTF-8';
        $headers[] = 'Content-Transfer-Encoding: 8bit';

        // Normalise line endings to CRLF and dot-stuff the body.
        $body = str_replace(["\r\n", "\r", "\n"], "\n", $body);
        $body = str_replace("\n", "\r\n", $body);
        $body = preg_replace('/^\./m', '..', $body);

        fwrite($socket, implode("\r\n", $headers) . "\r\n\r\n" . $body . "\r\n.\r\n");

        if (!bp_smtp_cmd($socket, null, 250, $error)) { fclose($socket); return ['success' => false, 'error' => $error]; }

        @bp_smtp_cmd($socket, 'QUIT', [221, 250], $error);
        fclose($socket);

        return ['success' => true, 'error' => ''];
    }
}
