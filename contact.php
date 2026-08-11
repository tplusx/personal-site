<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

function respond(int $status, string $message): never
{
    http_response_code($status);
    echo json_encode(['ok' => $status < 400, 'message' => $message], JSON_UNESCAPED_SLASHES);
    exit;
}

function smtpRead($socket, array $expected): void
{
    do {
        $line = fgets($socket, 515);
        if ($line === false) {
            throw new RuntimeException('SMTP connection closed unexpectedly.');
        }
    } while (strlen($line) >= 4 && $line[3] === '-');

    if (!in_array((int) substr($line, 0, 3), $expected, true)) {
        throw new RuntimeException('SMTP rejected a command: ' . trim($line));
    }
}

function smtpWrite($socket, string $command, array $expected): void
{
    if (fwrite($socket, $command . "\r\n") === false) {
        throw new RuntimeException('Unable to write to the SMTP server.');
    }
    smtpRead($socket, $expected);
}

function cleanHeader(string $value): string
{
    return trim(str_replace(["\r", "\n"], '', $value));
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    respond(405, 'Method not allowed.');
}

$siteHost = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
$originHost = strtolower((string) parse_url((string) ($_SERVER['HTTP_ORIGIN'] ?? ''), PHP_URL_HOST));
if ($originHost !== '' && preg_replace('/:\d+$/', '', $siteHost) !== $originHost) {
    respond(403, 'Request origin was not accepted.');
}

// Bots commonly fill this visually hidden field; return success without mailing.
if (!empty($_POST['website'])) {
    respond(200, 'Thanks — your enquiry has been received.');
}

$name = trim((string) ($_POST['name'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$message = trim((string) ($_POST['message'] ?? ''));
if (
    $name === '' || strlen($name) > 120
    || strlen($email) > 254 || filter_var($email, FILTER_VALIDATE_EMAIL) === false
    || $message === '' || strlen($message) > 5000
) {
    respond(422, 'Please check each field and try again.');
}

// Lock-protected, per-IP hourly throttling works across PHP sessions on one host.
$key = hash('sha256', (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
$rateFile = sys_get_temp_dir() . '/nnamdi-contact-' . $key;
$handle = fopen($rateFile, 'c+');
if ($handle === false || !flock($handle, LOCK_EX)) {
    respond(503, 'Mail is temporarily unavailable. Please try again later.');
}
$stored = json_decode(stream_get_contents($handle) ?: '{}', true);
$stored = is_array($stored) ? $stored : [];
$now = time();
$inWindow = isset($stored['start']) && $now - (int) $stored['start'] < 3600;
$count = $inWindow ? (int) ($stored['count'] ?? 0) + 1 : 1;
$start = $inWindow ? (int) $stored['start'] : $now;
ftruncate($handle, 0);
rewind($handle);
fwrite($handle, json_encode(['start' => $start, 'count' => $count]));
fflush($handle);
flock($handle, LOCK_UN);
fclose($handle);
if ($count > 5) {
    respond(429, 'Too many messages. Please try again later.');
}

// On an addon domain, DOCUMENT_ROOT is the addon's public directory, so this
// resolves to a private configuration file one level above the web root.
$configPath = dirname((string) ($_SERVER['DOCUMENT_ROOT'] ?? __DIR__)) . '/contact-config.php';
if (!is_readable($configPath)) {
    error_log('Nnamdi contact: SMTP config not found outside the document root.');
    respond(503, 'Mail is temporarily unavailable. Please try again later.');
}
try {
    $config = require $configPath;
    if (!is_array($config)) {
        throw new RuntimeException('Mail configuration did not return an array.');
    }
    foreach (['smtp_host', 'smtp_port', 'smtp_security', 'smtp_username', 'smtp_password', 'from_email', 'from_name', 'to_email'] as $required) {
        if (!isset($config[$required]) || $config[$required] === '') {
            throw new RuntimeException('Incomplete mail configuration.');
        }
    }
    if (!in_array($config['smtp_security'], ['ssl', 'tls'], true)) {
        throw new RuntimeException('SMTP security must be ssl or tls.');
    }
    if (filter_var($config['from_email'], FILTER_VALIDATE_EMAIL) === false || filter_var($config['to_email'], FILTER_VALIDATE_EMAIL) === false) {
        throw new RuntimeException('Invalid mail address in configuration.');
    }

    $transport = $config['smtp_security'] === 'ssl' ? 'ssl://' : '';
    $socket = stream_socket_client(
        $transport . $config['smtp_host'] . ':' . (int) $config['smtp_port'],
        $errorNumber,
        $errorMessage,
        15,
        STREAM_CLIENT_CONNECT,
    );
    if ($socket === false) {
        throw new RuntimeException("SMTP connection failed: {$errorNumber} {$errorMessage}");
    }
    stream_set_timeout($socket, 15);
    smtpRead($socket, [220]);
    $serverName = preg_replace('/[^a-z0-9.-]/i', '', (string) ($_SERVER['SERVER_NAME'] ?? 'localhost')) ?: 'localhost';
    smtpWrite($socket, 'EHLO ' . $serverName, [250]);
    if ($config['smtp_security'] === 'tls') {
        smtpWrite($socket, 'STARTTLS', [220]);
        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            throw new RuntimeException('Unable to start TLS.');
        }
        smtpWrite($socket, 'EHLO ' . $serverName, [250]);
    }
    smtpWrite($socket, 'AUTH LOGIN', [334]);
    smtpWrite($socket, base64_encode((string) $config['smtp_username']), [334]);
    smtpWrite($socket, base64_encode((string) $config['smtp_password']), [235]);

    $fromEmail = cleanHeader((string) $config['from_email']);
    $toEmail = cleanHeader((string) $config['to_email']);
    smtpWrite($socket, "MAIL FROM:<{$fromEmail}>", [250]);
    smtpWrite($socket, "RCPT TO:<{$toEmail}>", [250, 251]);
    smtpWrite($socket, 'DATA', [354]);

    $safeName = cleanHeader($name);
    $safeEmail = cleanHeader($email);
    $subject = 'Website enquiry from ' . $safeName;
    $normalizedMessage = str_replace(["\r\n", "\r"], "\n", $message);
    $body = "Name: {$name}\nEmail: {$email}\n\nMessage:\n{$normalizedMessage}";
    // SMTP dot-stuffing prevents a line beginning with a dot from ending DATA.
    $body = preg_replace('/^\./m', '..', $body) ?? $body;
    $headers = [
        'From: ' . cleanHeader((string) $config['from_name']) . " <{$fromEmail}>",
        "Reply-To: {$safeEmail}",
        "To: {$toEmail}",
        "Subject: {$subject}",
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
    ];
    $payload = implode("\r\n", $headers) . "\r\n\r\n" . str_replace("\n", "\r\n", $body) . "\r\n.\r\n";
    if (fwrite($socket, $payload) === false) {
        throw new RuntimeException('Unable to send the message data.');
    }
    smtpRead($socket, [250]);
    smtpWrite($socket, 'QUIT', [221]);
    fclose($socket);
} catch (Throwable $error) {
    if (isset($socket) && is_resource($socket)) {
        fclose($socket);
    }
    error_log('Nnamdi contact SMTP error: ' . $error->getMessage());
    respond(502, 'Mail is temporarily unavailable. Please try again later.');
}

respond(200, 'Thanks — your enquiry has been sent to Nnamdi.');
