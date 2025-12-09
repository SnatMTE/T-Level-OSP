<?php
/**
 * Shared header template.
 *
 * Contains the opening HTML + navigation. Included at the top of pages.
 *
 * @package RigetZoo
 * @author Snat
 * @link https://snat.co.uk
 */
// Include a site init file when present (installs ROOT_DIR, BASE_URL and starts session)
if (file_exists(dirname(__DIR__) . '/init.php')) {
    require_once dirname(__DIR__) . '/init.php';
}
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', dirname(__DIR__));
}
// Make auth + helper functions available in the header so current_user() and esc() work on every page
require_once ROOT_DIR . '/functions/php/helpers.php';
require_once ROOT_DIR . '/functions/php/auth.php';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?php echo isset($pageTitle) ? esc($pageTitle) : 'Riget Zoo Adventures'; ?></title>
    <link rel="stylesheet" href="<?php echo (defined('BASE_URL') && BASE_URL) ? BASE_URL : ''; ?>/assets/css/site.css">
    <style>/* Inline CSS is intentionally minimal; site.css is the primary theme */
        .animal-list{display:flex;flex-wrap:wrap;gap:8px}
    </style>
</head>
<body>
<header>
    <div class="container header-inner">
        <div class="brand-wrap">
            <a class="brand" href="<?php echo BASE_URL ?: '/'; ?>/index.php"><img src="<?php echo BASE_URL ?: '/'; ?>/assets/images/logo.svg" alt="Riget Zoo logo"></a>
        </div>

        <div class="right-controls" style="display:flex;align-items:center;gap:12px">
            <nav>
                <ul>
                    <li><a href="<?php echo BASE_URL ?: '/'; ?>/index.php">Home</a></li>
                    <li><a href="<?php echo BASE_URL ?: '/'; ?>/animals.php">Animals</a></li>
                    <li><a href="<?php echo BASE_URL ?: '/'; ?>/education.php">Education</a></li>
                    <li><a href="<?php echo BASE_URL ?: '/'; ?>/booking.php">Book</a></li>
                    <li><a href="<?php echo BASE_URL ?: '/'; ?>/contact.php">Contact</a></li>
                    <?php if (function_exists('current_user') && current_user()): ?>
                        <li><a href="<?php echo BASE_URL ?: '/'; ?>/my-bookings.php">My Bookings</a></li>
                        <?php if (!empty(current_user()['is_admin'])): ?>
                            <li><a href="<?php echo BASE_URL ?: '/'; ?>/admin/index.php">Admin</a></li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>
            </nav>

            <div class="login-box">
                <?php if (session_status() == PHP_SESSION_NONE) { session_start(); } ?>
                <?php if (function_exists('current_user') && current_user()):
                    $u = current_user();
                ?>
                    <div>Signed in as <?php echo htmlspecialchars($u['first_name'] . ' ' . $u['surname'], ENT_QUOTES, 'UTF-8'); ?> | <a href="<?php echo BASE_URL ?: '/'; ?>/functions/php/logout.php">Logout</a></div>
                <?php else: ?>
                    <form action="<?php echo BASE_URL ?: '/'; ?>/functions/php/login.php" method="post" style="display:flex;gap:6px;flex-wrap:wrap">
                        <input type="email" name="email" placeholder="Email" aria-label="Email">
                        <input type="password" name="password" placeholder="Password" aria-label="Password">
                        <button type="submit">Sign In</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>
<main id="main-content" class="container" tabindex="-1" role="main">
    <?php
    $isHome = (isset($_SERVER['REQUEST_URI']) && ($_SERVER['REQUEST_URI'] === '/' || basename($_SERVER['SCRIPT_NAME']) === 'index.php'));
    if (!$isHome):
    ?>
    <!-- Site hero banner (for visual appeal) -->
    <div class="hero" role="banner" aria-hidden="true">
        <div class="container">
            <h2>Welcome to Riget Zoo Adventures</h2>
        </div>
    </div>
    <?php endif; ?>
<?php
// Pop and render any flash messages if present
if (function_exists('pop_flash_messages')) {
    $fmsgs = pop_flash_messages();
    if (!empty($fmsgs)) {
        foreach ($fmsgs as $m) {
            $t = isset($m['type']) ? $m['type'] : 'info';
            $msg = isset($m['message']) ? $m['message'] : '';
            $class = 'alert';
            if ($t === 'success') $class .= ' alert-success';
            elseif ($t === 'error' || $t === 'danger') $class .= ' alert-error';
            elseif ($t === 'notice') $class .= ' alert-notice';
            echo '<div class="' . $class . '" role="status" aria-live="polite">' . htmlspecialchars($msg) . '</div>';
        }
    }
}
?>

<!-- 2025-12-03 15:00 - Refactor to use templates (header/footer) - author: Snat -->

