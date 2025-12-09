<?php
/**
 * Authentication helpers using the SQLite Database.
 *
 * Minimal helper functions for authenticating users, creating accounts, and
 * simple session-based login helpers used across the site.
 *
 * @package RigetZoo
 * @author Snat
 * @link https://snat.co.uk
 */
require_once __DIR__ . '/../../db/Database.php';

/**
 * Determine whether a user is currently logged in (session based).
 *
 * @return bool True if a user is stored in $_SESSION
 */
function is_logged_in()
{
    return isset($_SESSION['user']);
}

/**
 * Get current authenticated user data from session.
 *
 * @return array|null Return user associative array or null if not logged in
 */
function current_user()
{
    if (!is_logged_in()) return null;
    return $_SESSION['user'];
}

/**
 * Simple gate to require a login. Returns false rather than redirect.
 * Application pages may choose to redirect to login based on this helper.
 *
 * @return bool True if logged in, false if not
 */
function require_login()
{
    if (!is_logged_in()) {
        // no redirect by default
        return false;
    }
    return true;
}

/**
 * Create a user account in the database and return the inserted user row.
 *
 * @param string $email Email address
 * @param string $password Plaintext password (will be hashed)
 * @param string $first_name First name
 * @param string $surname Surname
 * @param string $address1 Address line 1
 * @param string $postcode Postal code
 * @param int $is_admin Whether the user is an admin (1) or regular (0)
 * @return array|false User row array on success, false on failure
 */
function create_user($email, $password, $first_name, $surname, $address1, $postcode, $is_admin = 0)
{
    $pdo = Database::pdo();
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO users (email,password_hash,first_name,surname,address1,postcode,is_admin,created_at) VALUES (?,?,?,?,?,?,?,?)');
    $now = date('c');
    try {
        $stmt->execute([$email, $hash, $first_name, $surname, $address1, $postcode, $is_admin, $now]);
        $id = $pdo->lastInsertId();
        return load_user_by_id($id);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Authenticate credentials against the users table.
 *
 * Returns the user row if authenticated, or false if authentication fails.
 *
 * @param string $email Candidate email
 * @param string $password Candidate plaintext password
 * @return array|false User row array on success, false otherwise
 */
function authenticate_user($email, $password)
{
    $pdo = Database::pdo();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row && password_verify($password, $row['password_hash'])) {
        return $row;
    }
    return false;
}

/**
 * Load a user's row by their numeric ID.
 *
 * @param int $id User ID
 * @return array|null User associative array or null if not found
 */
function load_user_by_id($id)
{
    $pdo = Database::pdo();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Store minimal user data into session after successful authentication.
 *
 * @param array $user User row - must include at least id, email, first_name, surname
 * @return void
 */
function login_user($user)
{
    // store minimal user data in session
    $_SESSION['user'] = [
        'id' => $user['id'],
        'email' => $user['email'],
        'first_name' => $user['first_name'],
        'surname' => $user['surname']
        ,'is_admin' => isset($user['is_admin']) ? (bool)$user['is_admin'] : false
    ];
}

/**
 * Log out the user by clearing the session entry.
 *
 * @return void
 */
function logout_user()
{
    unset($_SESSION['user']);
}

?>