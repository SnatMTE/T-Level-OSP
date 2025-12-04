<?php
/**
 * Authentication Functions
 * 
 * @author Maitiú Ellis
 * @package Functions
 * @description Authentication and authorization utilities
 */

/**
 * Check if user is logged in
 * 
 * @return bool
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

/**
 * Check if user is not logged in and redirect to login
 * 
 * @param string $redirectTo URL to redirect to after login
 * @return void
 */
function requireLogin(string $redirectTo = ''): void {
    if (!isLoggedIn()) {
        setFlash('error', 'You must be logged in to access this page.');
        redirect('login.php?redirect=' . urlencode($redirectTo ?: $_SERVER['REQUEST_URI']));
    }
}

/**
 * Get current logged-in user ID
 * 
 * @return int|null User ID or null if not logged in
 */
function getCurrentUserId(): ?int {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Logout user and destroy session
 * 
 * @return void
 */
function logout(): void {
    session_destroy();
    redirect('index.php');
}

/**
 * Set user session after login
 * 
 * @param int $userId User ID to set
 * @param array $userData Additional user data to store
 * @return void
 */
function setUserSession(int $userId, array $userData = []): void {
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_data'] = $userData;
}
