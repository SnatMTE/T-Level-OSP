<?php
/**
 * Helper Functions
 * 
 * @author Maitiú Ellis
 * @package Functions
 * @description Common utility functions for the application
 */

/**
 * Redirect to a specified URL
 * 
 * @param string $location File name or relative path to redirect to
 * @return void
 */
function redirect(string $location): void {
    // If location doesn't start with http/https and isn't absolute, make it relative
    if (!preg_match('/^(https?:\/\/|\/)/', $location)) {
        $location = basename($location); // Ensure it's just the filename
    }
    header("Location: {$location}");
    exit();
}

/**
 * Check if request method matches
 * 
 * @param string $method HTTP method to check (GET, POST, etc.)
 * @return bool
 */
function isRequestMethod(string $method): bool {
    return $_SERVER['REQUEST_METHOD'] === strtoupper($method);
}

/**
 * Get a value from GET parameters
 * 
 * @param string $key Parameter key
 * @param mixed $default Default value if not set
 * @return mixed
 */
function getGet(string $key, mixed $default = null): mixed {
    return $_GET[$key] ?? $default;
}

/**
 * Get a value from POST parameters
 * 
 * @param string $key Parameter key
 * @param mixed $default Default value if not set
 * @return mixed
 */
function getPost(string $key, mixed $default = null): mixed {
    return $_POST[$key] ?? $default;
}

/**
 * Safely display output to prevent XSS
 * 
 * @param mixed $value Value to escape
 * @return string Escaped value
 */
function esc(mixed $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/**
 * Set a session flash message
 * 
 * @param string $key Message key
 * @param string $message Message content
 * @return void
 */
function setFlash(string $key, string $message): void {
    $_SESSION['flash'][$key] = $message;
}

/**
 * Get and clear a session flash message
 * 
 * @param string $key Message key
 * @return string|null Flash message or null if not set
 */
function getFlash(string $key): ?string {
    $message = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $message;
}

/**
 * Check if flash message exists
 * 
 * @param string $key Message key
 * @return bool
 */
function hasFlash(string $key): bool {
    return isset($_SESSION['flash'][$key]);
}
