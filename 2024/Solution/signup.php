<?php
/**
 * Signup page - registration form UI.
 *
 * This page renders the registration form and posts to the signup-submit
 * endpoint which handles user creation.
 *
 * @package RigetZoo
 * @author Snat
 * @link https://snat.co.uk
 */
require 'templates/header.php';
$redirectTarget = isset($_GET['redirect']) ? $_GET['redirect'] : '/';
?>
<style>
    .signup-wrap{display:flex;gap:20px}
    .signup-info{flex:2}
    .signup-form-col{flex:1;border:1px solid #ddd;padding:12px;background:#fafafa}
    .signup-form-col label{display:block;margin-bottom:8px}
    .signup-form-col input{width:100%;box-sizing:border-box;padding:6px;margin-top:4px}
</style>

<div class="signup-wrap">
    <div class="signup-info">
        <h2>Sign Up</h2>
        <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer nec odio. Praesent libero. Sed cursus ante dapibus diam.</p>
    </div>

    <aside class="signup-form-col">
        <h3>Create an account</h3>
        <form method="post" action="/functions/php/signup-submit.php">
            <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirectTarget, ENT_QUOTES, 'UTF-8'); ?>">
            <label>First name
                <input type="text" name="first_name" required>
            </label>
            <label>Surname
                <input type="text" name="surname" required>
            </label>
            <label>Email address
                <input type="email" name="email" required>
            </label>
            <label>Password
                <input type="password" name="password" required>
            </label>
            <label>First line of address
                <input type="text" name="address1" required>
            </label>
            <label>Post code
                <input type="text" name="postcode" required>
            </label>
            <button type="submit">Sign Up</button>
        </form>
    </aside>
</div>

<?php require 'templates/footer.php'; ?>

<!-- 2025-12-03 10:00 - Add signup and login form UIs - author: Snat -->

