<?php
/**
 * Helper functions used across the application.
 *
 * Generic helpers for escaping, asset paths, basic validation utilities,
 * and application-specific helpers like price parsing and animal image
 * lookup.
 *
 * @package RigetZoo
 * @author Snat
 * @link https://snat.co.uk
 */
/**
 * Escape a string for safe HTML output.
 *
 * Wrapper for htmlspecialchars using UTF-8 and ENT_QUOTES to help avoid XSS.
 *
 * @param string $s Input string to escape
 * @return string Escaped string safe for HTML
 */
function esc($s)
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

/**
 * Returns a web path to an asset. A placeholder for centralizing asset
 * path logic should the application later support CDN or cache busting.
 *
 * @param string $path Asset path relative to the web root
 * @return string Web path used in templates
 */
function asset($path)
{
    return $path;
}

// Helper to fetch a numeric price from settings
/**
 * Get a numeric price from site settings, casting to float.
 *
 * @param string $key Settings key name
 * @param float $default Default numeric value when setting is absent
 * @return float Parsed numeric price value
 */
function get_price($key, $default = 0.0)
{
    if (!class_exists('Database')) {
        require_once __DIR__ . '/../../db/Database.php';
    }
    $val = Database::getSetting($key, (string)$default);
    return floatval($val);
}

// Format a number as GBP currency string (e.g., £10.00)
/**
 * Format a numeric amount as GBP currency string (e.g., "£10.00").
 *
 * @param float|int|string $amount Numeric value to format
 * @return string Formatted currency string
 */
function format_money($amount)
{
    return '£' . number_format(floatval($amount), 2, '.', '');
}

// Simple validation helpers
/**
 * Check if a string is a valid email address.
 *
 * @param string $email Candidate email address
 * @return bool True if valid, false otherwise
 */
function is_valid_email($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Check if a value is a positive integer.
 *
 * @param mixed $v Input value
 * @return bool True if value is an integer > 0
 */
function is_positive_int($v)
{
    return is_numeric($v) && intval($v) == $v && intval($v) > 0;
}

/**
 * Check whether a value represents a non-negative floating point number.
 *
 * @param mixed $v Input value
 * @return bool True if numeric and >= 0.0
 */
function is_non_negative_float($v)
{
    return is_numeric($v) && floatval($v) >= 0.0;
}

/**
 * Flash message utilities: add and pop messages to be displayed to the user
 * across redirects.
 * Usage: add_flash('success', 'Message'); then after redirect header, the
 * templates/header.php will display the flash via pop_flash_messages().
 */
function add_flash($type, $message)
{
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['flash_messages']) || !is_array($_SESSION['flash_messages'])) $_SESSION['flash_messages'] = [];
    $_SESSION['flash_messages'][] = ['type' => $type, 'message' => (string)$message];
}

function pop_flash_messages()
{
    if (session_status() === PHP_SESSION_NONE) session_start();
    $msgs = isset($_SESSION['flash_messages']) && is_array($_SESSION['flash_messages']) ? $_SESSION['flash_messages'] : [];
    unset($_SESSION['flash_messages']);
    return $msgs;
}

/**
 * Validate a string as a YYYY-MM-DD date.
 *
 * @param string $date Date string to validate
 * @return bool True if the date string is valid and in YYYY-MM-DD format
 */
function validate_date($date)
{
    if (empty($date)) return false;
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

// Returns an array with keys: 'src' => web path to image and 'credit' => HTML credit (may be empty)
/**
 * Resolve a suitable image path and credit for an animal name/category.
 *
 * This function attempts to resolve a preferred image for an animal by:
 * - First checking known canonical mappings (e.g., elephant, zebra, giraffe)
 * - Attempting token to filename matching (e.g., 'lion' => lion.jpg)
 * - Checking category placeholder SVGs where applicable
 * - Falling back to a generic logo image
 *
 * @param string $name Animal display name (e.g., "Lion")
 * @param string $category Optional category name to select category placeholder
 * @return array{src:string,credit:string} Array with web path in 'src' and HTML credit in 'credit'
 */
function get_animal_image_info($name, $category = '')
{
    // possible image directory from root of the project
    $rootDir = dirname(__DIR__, 2); // ../../ from functions/php
    $imgDir = $rootDir . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR;
    $webBase = '/assets/images/';

    $lower = strtolower($name);
    $lowerCategory = strtolower($category);

    // explicit known images with attribution
    $known = [
        'elephant' => [
            'file' => 'elephant_wm.jpg',
            'credit' => 'Photo credit: <a href="https://commons.wikimedia.org/wiki/File:Asian_Elephant_Herd_at_the_Saint_Louis_Zoo.jpg" target="_blank">HeatherMarieKosur</a> (CC BY-SA 4.0)'
        ],
        'zebra' => [
            'file' => 'zebra_pixabay.jpg',
            'credit' => 'Photo credit: <a href="https://pixabay.com/photos/zebra-africa-friendship-friends-7746719/" target="_blank">Vira</a> (Pixabay License)'
        ],
        'giraffe' => [
            'file' => 'giraffe_pixabay.jpg',
            'credit' => 'Photo credit: <a href="https://pixabay.com/photos/giraffe-animal-africa-sunset-2073609/" target="_blank">ambquinn</a> (Pixabay License)'
        ],
    ];

    foreach ($known as $k => $meta) {
        if (strpos($lower, $k) !== false) {
            // Known mapping - return the expected web path for known animals.
            return ['src' => $webBase . $meta['file'], 'credit' => $meta['credit']];
        }
    }

    // Try to match on a filename generated from the animal name
    // split words and check for files that match words
    $words = preg_split('/[^a-zA-Z0-9]+/', $lower);
    $exts = ['.jpg', '.jpeg', '.png', '.webp', '.svg'];
    $candidates = [];
    foreach ($words as $w) {
        if (strlen($w) < 3) continue; // skip very short tokens
        foreach ($exts as $ext) {
            $candidates[] = $w . $ext;
        }
        // also check with some common suffixes
        foreach (['_pixabay', '_wm', '_photo'] as $suf) {
            foreach ($exts as $ext) {
                $candidates[] = $w . $suf . $ext;
            }
        }
    }
    foreach ($candidates as $c) {
        $p = $imgDir . $c;
        if (file_exists($p) || file_exists(realpath($p))) {
            return ['src' => $webBase . $c, 'credit' => ''];
        }
    }
    // Try wildcard matching by token
    foreach ($words as $w) {
        if (strlen($w) < 3) continue;
        $pattern = $imgDir . '*' . $w . '*.*';
        $g = glob($pattern);
        if (!empty($g)) {
            $found = basename($g[0]);
            return ['src' => $webBase . $found, 'credit' => ''];
        }
    }

    // fallback: try category-based placeholders, then generic placeholders
    $categoryMap = [
        'big cats' => 'big-cats.svg',
        'primates' => 'primates.svg',
        'birds' => 'birds.svg',
        'reptiles' => 'reptiles.svg',
        'mammals - other' => 'mammals-other.svg',
        'default' => 'logo.svg'
    ];
    if (!empty($lowerCategory) && isset($categoryMap[$lowerCategory])) {
        $fb = $categoryMap[$lowerCategory];
        if (file_exists($imgDir . $fb) || file_exists(realpath($imgDir . $fb))) {
            return ['src' => $webBase . $fb, 'credit' => ''];
        }
    }
    $fallbacks = ['logo.svg', 'elephant.svg', 'hero.svg'];
    foreach ($fallbacks as $fb) {
        $p = $imgDir . $fb;
        if (file_exists($p) || file_exists(realpath($p))) {
            return ['src' => $webBase . $fb, 'credit' => ''];
        }
    }

    // last fallback: a small data URI or empty
    return ['src' => $webBase . 'logo.svg', 'credit' => ''];
}

?>
<!-- 2025-12-03 15:30 - Add flash helpers and redirect patterns for forms - author: Snat -->

