<?php
/**
 * Animals listing page - displays animal cards grouped by category.
 *
 * This page queries the database for animals and uses helpers to render the
 * correct image for each animal. Inline credits are shown when available.
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
require_once ROOT_DIR . '/functions/php/auth.php';

Database::initialize();

$pageTitle = 'Animals - Riget Zoo Adventures';
require_once ROOT_DIR . '/templates/header.php';

$groups = Database::getAnimalsGrouped();
?>

<h1>Our Animals</h1>

<section class="animals">
<?php foreach ($groups as $category => $animals): ?>
    <div class="animal-category">
        <h2><?php echo esc($category); ?></h2>
        <div class="animal-list">
            <?php foreach ($animals as $a): ?>
            <div class="animal-item card">
                <?php $imgInfo = get_animal_image_info($a['name'], $a['category']); ?>
                <img src="<?php echo esc($imgInfo['src']); ?>" alt="<?php echo esc($a['name']); ?>" style="width:100%;max-height:160px;object-fit:cover;border-radius:8px;margin-bottom:8px">
                <?php if (!empty($_GET['debug_images'])): ?><!-- image: <?php echo esc($imgInfo['src']); ?> credits: <?php echo esc(strip_tags($imgInfo['credit'])); ?> --><?php endif; ?>
                <?php if (!empty($imgInfo['credit'])): ?>
                    <div style="font-size:0.85rem;color:#666;margin-top:6px"><?php echo $imgInfo['credit']; ?></div>
                <?php endif; ?>
                <h3><?php echo esc($a['name']); ?></h3>
                <p><strong>Habitat:</strong> <?php echo esc($a['habitat']); ?><br>
                <strong>Diet:</strong> <?php echo esc($a['diet']); ?><br>
                <strong>Status:</strong> <?php echo esc($a['status']); ?><br>
                <?php echo esc($a['description']); ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endforeach; ?>
</section>

<?php
require_once ROOT_DIR . '/templates/footer.php';
?>
