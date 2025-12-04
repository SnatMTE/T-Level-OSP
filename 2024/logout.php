<?php
/**
 * Logout Page
 * 
 * @author Maitiú Ellis
 * @description Handles user logout
 */

// Start session
session_start();

// Define root directory for consistent path resolution
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', __DIR__);
}

// Include necessary files
require_once ROOT_DIR . '/functions/helpers.php';
require_once ROOT_DIR . '/functions/auth.php';

// Logout user
logout();
