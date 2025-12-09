<?php
/**
 * Initialization for site when running from subdirectory `2024/Solution`.
 * This is NOT part of the original OSP, it was added so that the solution could be placed in a subdirectory for GitHub.
 * 
 * Defines ROOT_DIR (filesystem root path) and BASE_URL (web path) used
 * throughout the site to make the application relocatable under a subpath.
 */
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', __DIR__);
}

if (!defined('BASE_URL')) {
    $scriptName = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
    $base = rtrim(dirname($scriptName), '/\\');
    // If script is at the webroot, dirname returns '\\' or '.', normalize to ''
    if ($base === '/' || $base === '.' || $base === '\\') $base = '';
    define('BASE_URL', $base);
}

// Make sure session is started for flash messages / auth
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

?>
