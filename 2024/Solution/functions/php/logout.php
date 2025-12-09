<?php
/**
 * Logout handler.
 *
 * Logs the user out of the session and redirects to the home page.
 *
 * @package RigetZoo
 * @author Snat
 * @link https://snat.co.uk
 */
session_start();
require_once __DIR__ . '/auth.php';
logout_user();
// Respect BASE_URL when redirecting
$redirectBase = (defined('BASE_URL') && BASE_URL) ? BASE_URL : '';
// Redirect to homepage after logout
header('Location: ' . $redirectBase . '/index.php');
exit;
