<?php
/**
 * Sakura Sushi - Landing Page
 * Glassmorphism hero with animated background
 */
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sakura Sushi - Table Reservation</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="nav">
        <div class="nav-logo">Sakura Sushi</div>
        <ul class="nav-links">
            <li><a href="index.php">Home</a></li>
            <li><a href="tables.php">Reserve Table</a></li>
        </ul>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-bg"></div>
        <div class="hero-pattern"></div>
        <div class="petals"></div>
        
        <div class="hero-content">
            <div class="hero-logo">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#E8D5B7" stroke-width="1.5">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z"/>
                    <path d="M8 14s1.5 2 4 2 4-2 4-2"/>
                    <line x1="9" y1="9" x2="9.01" y2="9"/>
                    <line x1="15" y1="9" x2="15.01" y2="9"/>
                </svg>
            </div>
            <h1 class="hero-title">Sakura Sushi</h1>
            <p class="hero-subtitle">Experience authentic Japanese cuisine</p>
            <div class="hero-cta">
                <a href="tables.php" class="btn btn-primary btn-large">Reserve Table</a>
            </div>
            <p class="hero-info">Walk-ins welcome &middot; Open daily 11AM&ndash;10PM</p>
        </div>
    </section>

    <script src="assets/js/main.js"></script>
</body>
</html>
