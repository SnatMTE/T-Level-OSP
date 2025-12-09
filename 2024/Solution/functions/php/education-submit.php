<?php
/**
 * Education submission debug page.
 *
 * Simple handler that dumps POSTed data from the education form - used for
 * development and debugging, not a production endpoint.
 *
 * @package RigetZoo
 * @author Snat
 * @link https://snat.co.uk
 */
require_once __DIR__ . '/../../functions/php/helpers.php';
// Respect BASE_URL when redirecting
$redirectBase = (defined('BASE_URL') && BASE_URL) ? BASE_URL : '';
if (!empty($_POST)) {
    add_flash('success', 'Education form submitted (debug) — data received.');
} else {
    add_flash('error', 'No POST data received for education form.');
}
header('Location: ' . $redirectBase . '/education.php');
exit;

