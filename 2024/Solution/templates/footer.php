<?php
/**
 * Footer template for Riget Zoo Adventures.
 *
 * Contains the closing HTML for the page, including the footer section.
 *
 * @package RigetZoo
 * @author Snat
 * @link https://snat.co.uk
 */
?>

</main>
    <footer id="site-footer" class="footer" role="contentinfo" aria-label="Riget Zoo footer">
        <div class="container footer-inner">
                <div>
                    <a href="<?php echo defined('BASE_URL') && BASE_URL ? BASE_URL : ''; ?>/signup.php" class="btn" aria-label="Sign up for Riget Zoo Adventures">Sign Up</a>
                </div>
            <div class="small-muted">&copy; <?php echo date('Y'); ?> Riget Zoo Adventures • <a href="<?php echo defined('BASE_URL') && BASE_URL ? BASE_URL : ''; ?>/contact.php">Contact</a></div>
            <div>
                <a class="back-to-top" href="#main-content" aria-label="Back to top">Back to top ↑</a>
            </div>
        </div>
    </footer>
    <script>
        // If a hash target exists or changes, focus the target to ensure keyboard/screen reader users
        (function () {
            function focusHashTarget() {
                try {
                    var hash = window.location.hash ? window.location.hash.substring(1) : null;
                    if (!hash) return;
                    var el = document.getElementById(hash);
                    if (el && typeof el.focus === 'function') {
                        el.focus();
                    }
                } catch (e) {
                    // No-op: best-effort focus
                }
            }
            document.addEventListener('DOMContentLoaded', focusHashTarget);
            window.addEventListener('hashchange', focusHashTarget);
        })();
    </script>
<?php if (function_exists('current_user') && current_user() && !empty(current_user()['is_admin'])): ?>

<?php endif; ?>
</body>
</html>

<!-- 2025-12-03 17:00 - Add accessibility updates, ARIA and focus behavior - author: Snat -->


<!-- 2025-12-03 17:30 - Polish footer contrast and layout; minor UI tweaks - author: Snat -->

