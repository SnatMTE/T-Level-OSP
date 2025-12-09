<?php
/**
 * Signup submission HTTP handler.
 *
 * Validates form input and creates a user, storing them in the database via
 * the `create_user` helper. On success, logs the user in and renders a
 * success message.
 *
 * @package RigetZoo
 * @author Snat
 * @link https://snat.co.uk
 */
session_start();
require_once __DIR__ . '/../../functions/php/helpers.php';
require_once __DIR__ . '/auth.php';
// respect BASE_URL
$redirectBase = (defined('BASE_URL') && BASE_URL) ? BASE_URL : '';

/**
 * Read a POST value trimmed, or return empty string if not set.
 *
 * @param string $k POST key name
 * @return string Trimmed value or empty string
 */
function post_val($k) { return isset($_POST[$k]) ? trim($_POST[$k]) : ''; }

// Accept a safe redirect parameter to return users to the intended page
function safe_redirect($url) {
    if (empty($url)) return null;
    if (strpos($url, '//') !== false) return null;
    if (strpos($url, '/') !== 0) return null;
    if (preg_match('/[\r\n]/', $url)) return null;
    return $url;
}

if (!empty($_POST)) {
    $email = post_val('email');
    $password = post_val('password');
    $first_name = post_val('first_name');
    $surname = post_val('surname');
    $address1 = post_val('address1');
    $postcode = post_val('postcode');

    $errors = [];
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !filter_var($email, FILTER_SANITIZE_EMAIL)) $errors[] = 'Invalid email';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters';
    if ($first_name === '') $errors[] = 'First name required';
    if ($surname === '') $errors[] = 'Surname required';
    if ($address1 === '') $errors[] = 'Address required';
    if ($postcode === '') $errors[] = 'Postcode required';

    if (empty($errors)) {
        $user = create_user($email, $password, $first_name, $surname, $address1, $postcode);
        if ($user) {
            login_user($user);
            add_flash('success', 'Signup successful — welcome back, ' . htmlspecialchars($user['first_name'], ENT_QUOTES, 'UTF-8'));
            $redirect = post_val('redirect');
            $safe = safe_redirect($redirect);
            header('Location: ' . ($safe ?: '/index.php'));
            exit;
        } else {
            add_flash('error', 'Signup failed — email may already be registered.');
            $redirect = post_val('redirect');
            $safe = safe_redirect($redirect);
                header('Location: ' . $redirectBase . '/signup.php');
            exit;
        }
    } else {
        add_flash('error', 'Signup errors: ' . implode(' | ', array_map('htmlspecialchars', $errors)));
        $redirect = post_val('redirect');
        $safe = safe_redirect($redirect);
          header('Location: ' . $redirectBase . '/signup.php');
        exit;
    }
} else {
    add_flash('error', 'No POST data received for signup.');
    header('Location: /signup.php');
    exit;
}

require_once __DIR__ . '/../../templates/footer.php';

