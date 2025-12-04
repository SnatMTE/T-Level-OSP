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
</head>
<body>
    <!-- Header Navigation -->
    <header>
        <nav>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="booking.php">Booking</a></li>
                <?php if (isLoggedIn()): ?>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="register.php">Register</a></li>
                <?php endif; ?>
            </ul>
        </nav>
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
