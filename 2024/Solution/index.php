<?php
/**
 * Homepage for Riget Zoo Adventures.
 *
 * Renders hero section and highlights; entry point for public site content.
 *
 * @package RigetZoo
 * @author Snat
 * @link https://snat.co.uk
 */
session_start();

if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', __DIR__);
}

require_once ROOT_DIR . '/db/Database.php';
require_once ROOT_DIR . '/functions/php/helpers.php';

Database::initialize();

$pageTitle = 'Home - Riget Zoo Adventures';
require_once ROOT_DIR . '/templates/header.php';
?>
<div class="hero">
    <div class="container">
        <h2>Explore the world of animals — from birds to big cats</h2>
        <p style="color:#fff;max-width:760px">Adventure awaits at Riget Zoo — explore wildlife, educational programs and family-friendly activities. Book tickets and tours online.</p>
        <p style="margin-top:12px"><a class="btn" href="<?php echo BASE_URL ?: '/'; ?>/booking.php">Book now</a> <a class="btn secondary" href="<?php echo BASE_URL ?: '/'; ?>/animals.php">See animals</a></p>
    </div>
</div>

<?php
require_once ROOT_DIR . '/templates/footer.php';
?>

<!-- 2025-12-03 08:00 - Initial wireframe: Basic HTML skeleton and header/footer - author: Snat -->


<!-- 2025-12-03 09:00 - Implement basic homepage and navigation - author: Snat -->

