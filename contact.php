<?php

declare(strict_types=1);

session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Strict',
    'cookie_secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
]);

const MAX_NAME_LENGTH = 120;
const MAX_EMAIL_LENGTH = 254;
const MAX_MESSAGE_LENGTH = 5000;
const RATE_LIMIT_SECONDS = 30;

function respond(int $status, string $message): never
{
    http_response_code($status);

    if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => $status < 400, 'message' => $message], JSON_UNESCAPED_SLASHES);
        exit;
    }

    $result = $status < 400 ? 'sent' : 'error';
    header("Location: /contact.html?status={$result}", true, 303);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    respond(405, 'Please submit the contact form.');
}

// A hidden field catches basic form bots without inconveniencing real visitors.
if (trim((string) ($_POST['website'] ?? '')) !== '') {
    respond(200, 'Thanks — your enquiry has been received.');
}

$lastSubmission = (int) ($_SESSION['last_contact_submission'] ?? 0);
if ($lastSubmission > 0 && time() - $lastSubmission < RATE_LIMIT_SECONDS) {
    respond(429, 'Please wait a moment before sending another enquiry.');
}

$name = trim((string) ($_POST['name'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$message = trim((string) ($_POST['message'] ?? ''));

if ($name === '' || strlen($name) > MAX_NAME_LENGTH) {
    respond(422, 'Please enter a valid name.');
}

if (strlen($email) > MAX_EMAIL_LENGTH || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
    respond(422, 'Please enter a valid email address.');
}

if ($message === '' || strlen($message) > MAX_MESSAGE_LENGTH) {
    respond(422, 'Please enter a message of no more than 5,000 characters.');
}

// Remove control characters so user input cannot inject additional mail headers.
$safeName = preg_replace('/[\r\n\x00-\x1F\x7F]+/u', ' ', $name) ?? 'Website visitor';
$safeEmail = str_replace(["\r", "\n"], '', $email);
$recipient = getenv('CONTACT_TO') ?: 'info@nnamdi.ng';
$subject = 'Website enquiry from ' . $safeName;
$body = "Name: {$safeName}\nEmail: {$safeEmail}\n\nMessage:\n{$message}\n";
$headers = [
    'From: Nnamdi website <noreply@nnamdi.ng>',
    "Reply-To: {$safeEmail}",
    'Content-Type: text/plain; charset=UTF-8',
    'X-Mailer: PHP/' . PHP_VERSION,
];

if (!mail($recipient, $subject, $body, implode("\r\n", $headers))) {
    respond(503, 'Your enquiry could not be sent right now. Please email info@nnamdi.ng instead.');
}

$_SESSION['last_contact_submission'] = time();
respond(200, 'Thanks — your enquiry has been sent to Nnamdi.');
