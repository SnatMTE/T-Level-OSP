<?php
/**
 * Header Template
 * 
 * @author Maitiú Ellis
 * @package Templates
 * @description Main header and navigation template
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc($pageTitle ?? 'RZA'); ?></title>
    <!-- Site stylesheet -->
    <link rel="stylesheet" href="static/styles.css">
</head>
<body>
    <!-- Header Navigation (CSS Grid layout) -->
    <header class="site-header">
        <div class="site-nav">
            <nav>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="booking.php">Booking</a></li>
                    <?php if (isLoggedIn()): ?>
                        <li><a href="dashboard.php">Dashboard</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>

        <div class="auth-area">
            <?php if (isLoggedIn()): ?>
                <div class="status">Logged in as <?php echo esc($_SESSION['user_data']['name'] ?? 'User'); ?></div>
                <a class="auth-link" href="logout.php">Logout</a>
            <?php else: ?>
                <div class="status">Not logged in</div>
                <a class="auth-link" href="login.php">Login</a>
                <a class="auth-link" href="register.php">Register</a>
            <?php endif; ?>
        </div>
    </header>

    <!-- Flash Messages Display -->
    <?php if (hasFlash('success')): ?>
        <div class="flash flash-success">
            <?php echo esc(getFlash('success')); ?>
        </div>
    <?php endif; ?>

    <?php if (hasFlash('error')): ?>
        <div class="flash flash-error">
            <?php echo esc(getFlash('error')); ?>
        </div>
    <?php endif; ?>

    <!-- Main Content -->
    <main>