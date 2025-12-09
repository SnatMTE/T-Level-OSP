<?php
/**
 * Contact submission handler.
 *
 * Receives POSTed contact form data and validates; stores a local test file
 * as a substitute for an email send in the development environment.
 *
 * @package RigetZoo
 * @author Snat
 * @link https://snat.co.uk
 */
session_start();
if (!defined('ROOT_DIR')) { define('ROOT_DIR', dirname(dirname(__DIR__))); }
require_once ROOT_DIR . '/functions/php/helpers.php';
require_once ROOT_DIR . '/functions/php/auth.php';
// Respect BASE_URL when redirecting
$redirectBase = (defined('BASE_URL') && BASE_URL) ? BASE_URL : '';
// Load Database helper so we can read reCAPTCHA secret if configured
if (!class_exists('Database')) { require_once ROOT_DIR . '/db/Database.php'; }

/**
 * Verify Google reCAPTCHA v2 response server-side.
 */
function verify_recaptcha_response($secret, $response, $remoteip = null)
{
    if (empty($secret) || empty($response)) return false;
    $url = 'https://www.google.com/recaptcha/api/siteverify';
    $postdata = http_build_query(['secret' => $secret, 'response' => $response, 'remoteip' => $remoteip]);
    $opts = ['http' => ['method' => 'POST', 'header' => 'Content-Type: application/x-www-form-urlencoded', 'content' => $postdata]];
    $context = stream_context_create($opts);
    $result = @file_get_contents($url, false, $context);
    if ($result === false) return false;
    $json = json_decode($result, true);
    return is_array($json) && !empty($json['success']);
}

/**
 * Sanitize a value for safe use in output and storage.
 *
 * @param mixed $v Input value
 * @return string Sanitized string
 */
function sanitize($v){ return trim(htmlspecialchars((string)$v)); }

if (empty($_POST)) {
    add_flash('error', 'No POST data received.');
    header('Location: ' . $redirectBase . '/contact.php');
    exit;
}

$u = function_exists('current_user') ? current_user() : null;
$name = $u ? ($u['first_name'] . ' ' . $u['surname']) : sanitize(isset($_POST['name']) ? $_POST['name'] : '');
$email = $u ? $u['email'] : sanitize(isset($_POST['email']) ? $_POST['email'] : '');
$subject = sanitize(isset($_POST['subject']) ? $_POST['subject'] : '');
$message = sanitize(isset($_POST['message']) ? $_POST['message'] : '');
$honeypot = sanitize(isset($_POST['phone']) ? $_POST['phone'] : '');
// Check reCAPTCHA if configured (site will store secret in settings 'recaptcha_secret_key')
$recaptcha_secret = Database::getSetting('recaptcha_secret_key', '');
$recaptcha_response = isset($_POST['g-recaptcha-response']) ? sanitize($_POST['g-recaptcha-response']) : '';

// Basic validation
$errors = [];
if ($name === '') $errors[] = 'Name required';
if (!is_valid_email($email)) $errors[] = 'Valid email required';
if ($subject === '') $errors[] = 'Subject required';
if ($message === '') $errors[] = 'Message required';
// Bot check: honeypot should be blank
if ($honeypot !== '') $errors[] = 'Bot detection triggered';
// Length checks
if (strlen($subject) > 255) $errors[] = 'Subject is too long';
if (strlen($message) > 2000) $errors[] = 'Message is too long';

// If reCAPTCHA secret is configured, require successful reCAPTCHA validation
if (!empty($recaptcha_secret)) {
    if ($recaptcha_response === '') {
        $errors[] = 'Please complete the reCAPTCHA challenge.';
    } else {
        $remoteip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
        if (!verify_recaptcha_response($recaptcha_secret, $recaptcha_response, $remoteip)) {
            $errors[] = 'reCAPTCHA verification failed. Please try again.';
            $logfn = __DIR__ . '/../../test/mail.log';
            file_put_contents($logfn, '[' . date('c') . "] reCAPTCHA failed: $email - $subject\n", FILE_APPEND);
        }
    }
}

if (!empty($errors)) {
    add_flash('error', 'Please correct the following: ' . implode(' | ', array_map('htmlspecialchars', $errors)));
    header('Location: ' . $redirectBase . '/contact.php');
    exit;
}

// Dummy send: write to a file in the test/ directory with timestamp
$payload = "From: $name <$email>\nSubject: $subject\nMessage:\n$message\n\n--\nReceived: " . date('c') . "\n";
$dir = __DIR__ . '/../../test/';
if (!is_dir($dir)) { mkdir($dir, 0755, true); }
$fn = $dir . 'contact_' . time() . '_' . bin2hex(random_bytes(4)) . '.txt';
file_put_contents($fn, $payload);

// Log to a mail log too
$logfn = $dir . 'mail.log';
file_put_contents($logfn, '[' . date('c') . "] Contact form: $email - $subject\n", FILE_APPEND);

// Success: set a success flash and redirect back to contact page
add_flash('success', 'Thank you — your message has been received. We will contact you shortly.');
header('Location: ' . $redirectBase . '/contact.php');
exit;
?>

<!-- 2025-12-03 12:30 - Add contact processing (test logs, handler) - author: Snat -->


<!-- 2025-12-03 14:30 - Add reCAPTCHA client placeholder for contact form - author: Snat -->

