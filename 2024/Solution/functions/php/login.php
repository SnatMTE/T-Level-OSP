<?php
/**
 * Login handler.</n+ *
 * Accepts email/password POST and authenticates with the database.
 * On success, the user session is populated.
 *
 * @package RigetZoo
 * @author Snat
 * @link https://snat.co.uk
 */
session_start();
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../functions/php/helpers.php';
// Respect BASE_URL when redirecting
$redirectBase = (defined('BASE_URL') && BASE_URL) ? BASE_URL : '';

/**
 * Read a trimmed POST value if present.
 *
 * @param string $k POST key
 * @return string Trimmed post value or empty string
 */
function post_val($k) { return isset($_POST[$k]) ? trim($_POST[$k]) : ''; }

/**
 * Return a safe redirect url (internal only) or null.
 * Accepts only URLs that begin with a single leading slash and no host/scheme.
 */
function safe_redirect($url) {
    if (empty($url)) return null;
    // Disallow double slashes, scheme, or full host
    if (strpos($url, '//') !== false) return null;
    if (strpos($url, '/') !== 0) return null;
    // Basic protect against CRLF injection
    if (preg_match('/[\r\n]/', $url)) return null;
    return $url;
}

if (!empty($_POST)) {
    $email = post_val('email');
    $password = post_val('password');
    $errors = [];
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters';
    if (!empty($errors)) {
        add_flash('error', 'Login errors: ' . implode(' | ', array_map('htmlspecialchars', $errors)));
        header('Location: ' . $redirectBase . '/');
        exit;
    }
    $user = authenticate_user($email, $password);
    if ($user) {
        login_user($user);
        add_flash('success', 'Login successful — welcome back, ' . htmlspecialchars($user['first_name'], ENT_QUOTES, 'UTF-8'));
        $redirect = post_val('redirect');
        $safe = safe_redirect($redirect);
        header('Location: ' . ($safe ?: $redirectBase . '/'));
        exit;
    } else {
        add_flash('error', 'Login failed — invalid credentials.');
        $redirect = post_val('redirect');
        $safe = safe_redirect($redirect);
        header('Location: ' . ($safe ?: $redirectBase . '/'));
        exit;
    }
} else {
    add_flash('error', 'No POST data received for login.');
    header('Location: ' . $redirectBase . '/');
    exit;
}

require_once __DIR__ . '/../../templates/footer.php';

