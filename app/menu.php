<?php
/**
 * ============================================================================
 * SAKURA SUSHI - PRE-ORDER MENU SYSTEM
 * ============================================================================
 * 
 * ALGORITHM: Category-Based Menu Display with Cart Integration
 * 
 * OVERVIEW:
 * - Displays menu items grouped by category
 * - Integrates with HashMap + Doubly Linked List cart (client-side)
 * - Requires table selection before access (enforced flow)
 * - Real-time cart updates with instant item lookup
 * 
 * DATA STRUCTURES:
 * 1. Database Query Result (Array of menu items)
 *    - Sorted by category, then name
 *    - SQL: ORDER BY category, name
 * 
 * 2. Client-Side Cart (HashMap + Doubly Linked List)
 *    - Implemented in main.js
 *    - O(1) add, remove, find, update operations
 *    - Maintains insertion order
 * 
 * CART OPERATIONS (Client-Side):
 * - Add item: O(1) via HashMap
 * - Remove item: O(1) via HashMap + doubly linked pointers
 * - Find item: O(1) via HashMap lookup
 * - Update quantity: O(1) via HashMap
 * - Get all items: O(n) traverse doubly linked list
 * 
 * COMPLEXITY ANALYSIS:
 * - Load menu: O(n log n) - database sort
 * - Display items: O(n) - iterate and render
 * - Cart operations: O(1) - hash map access
 * 
 * FEATURES:
 * - Category filtering (All, Sushi, Sashimi, Rolls, Appetizers, Drinks)
 * - Image fallback with emoji icons
 * - Real-time cart total calculation
 * - LocalStorage persistence
 * 
 * VALIDATION:
 * - Requires table_id parameter
 * - Redirects to tables.php if no table selected
 * - Validates table exists in database
 * 
 * @version 2.0
 * @author Sakura Sushi Development Team
 * ============================================================================
 */
require_once 'config.php';

$tableId = isset($_GET['table_id']) ? (int)$_GET['table_id'] : 0;
$selectedDate = isset($_GET['date']) ? $_GET['date'] : '';
$selectedTime = isset($_GET['time']) ? $_GET['time'] : '';

// Redirect to tables page if no table selected
if ($tableId === 0) {
    header('Location: tables.php');
    exit;
}

// Get table details
$pdo = getDBConnection();
$stmt = $pdo->prepare("SELECT * FROM tables WHERE id = ?");
$stmt->execute([$tableId]);
$table = $stmt->fetch();

// If table not found, redirect to tables page
if (!$table) {
    header('Location: tables.php');
    exit;
}

// Fetch menu items from database
$pdo = getDBConnection();
$stmt = $pdo->query("SELECT * FROM menu_items ORDER BY category, name");
$menuItems = $stmt->fetchAll();

// Group by category
$categories = ['sushi', 'sashimi', 'rolls', 'appetizers', 'drinks'];

// Image mapping for generated assets
$imageMap = [
    'salmon-nigiri.jpg' => 'assets/images/sushi/salmon-nigiri.jpg',
    'tuna-nigiri.jpg' => 'assets/images/sushi/salmon-nigiri.jpg',
    'ebi-nigiri.jpg' => 'assets/images/sushi/salmon-nigiri.jpg',
    'tamago-nigiri.jpg' => 'assets/images/sushi/salmon-nigiri.jpg',
    'tuna-sashimi.jpg' => 'assets/images/sushi/tuna-sashimi.jpg',
    'salmon-sashimi.jpg' => 'assets/images/sushi/tuna-sashimi.jpg',
    'yellowtail-sashimi.jpg' => 'assets/images/sushi/tuna-sashimi.jpg',
    'mixed-sashimi.jpg' => 'assets/images/sushi/tuna-sashimi.jpg',
    'dragon-roll.jpg' => 'assets/images/sushi/dragon-roll.jpg',
    'california-roll.jpg' => 'assets/images/sushi/dragon-roll.jpg',
    'spicy-tuna-roll.jpg' => 'assets/images/sushi/dragon-roll.jpg',
    'rainbow-roll.jpg' => 'assets/images/sushi/dragon-roll.jpg',
    'edamame.jpg' => 'assets/images/sushi/tempura.jpg',
    'miso-soup.jpg' => 'assets/images/sushi/tempura.jpg',
    'gyoza.jpg' => 'assets/images/sushi/tempura.jpg',
    'tempura.jpg' => 'assets/images/sushi/tempura.jpg',
    'sake.jpg' => 'assets/images/sushi/tempura.jpg',
    'green-tea.jpg' => 'assets/images/sushi/tempura.jpg',
    'ramune.jpg' => 'assets/images/sushi/tempura.jpg',
    'matcha-latte.jpg' => 'assets/images/sushi/tempura.jpg',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pre-Order Menu - Sakura Sushi</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="page-transition">
    <!-- Navigation -->
    <nav class="nav">
        <button class="nav-back">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M19 12H5M12 19l-7-7 7-7"/>
            </svg>
            Back
        </button>
        <div class="nav-logo">Sakura Sushi</div>
        <button class="cart-toggle btn btn-glass" style="padding: 10px 16px;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 2L6 9H3l2.5 9h13L21 9h-3l-3-7H9z"/>
                <circle cx="9" cy="20" r="1.5"/>
                <circle cx="17" cy="20" r="1.5"/>
            </svg>
            <span class="cart-count" style="position: static; margin-left: 8px; width: auto; height: auto; background: none; color: var(--color-accent);">0</span>
        </button>
    </nav>

    <!-- Page Header -->
    <header class="page-header">
        <div style="background: rgba(201,150,79,.1); border: 1px solid rgba(201,150,79,.3); border-radius: 12px; padding: 12px 16px; margin-bottom: 20px; max-width: 600px; margin-left: auto; margin-right: auto;">
            <div style="display: flex; align-items: center; gap: 10px; color: var(--color-accent); font-size: 14px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="16" x2="12" y2="12"/>
                    <line x1="12" y1="8" x2="12.01" y2="8"/>
                </svg>
                <span>Pre-ordering is optional. You can skip this and proceed directly to reservation.</span>
            </div>
        </div>
        
        <h1 class="page-title">Pre-Order Your Meal</h1>
        <p class="page-subtitle">Select your favorite dishes - they'll be ready when you arrive</p>
        
        <div class="info-card" style="max-width: 500px; margin: 20px auto;">
            <div class="info-card-label">Selected Table</div>
            <div class="info-card-value">
                Table <?php echo htmlspecialchars($table['table_number']); ?> 
                &middot; <?php echo $table['capacity']; ?> seats 
                &middot; ₱<?php echo number_format($table['price'], 2); ?>
            </div>
            <div style="margin-top: 12px;">
                <a href="reservation.php?table_id=<?php echo $tableId; ?><?php echo $selectedDate ? '&date='.urlencode($selectedDate) : ''; ?><?php echo $selectedTime ? '&time='.urlencode($selectedTime) : ''; ?>&from_menu=1" class="btn btn-primary" style="display: inline-block; padding: 10px 20px; text-decoration: none;">
                    Skip to Reservation →
                </a>
            </div>
        </div>
    </header>

    <!-- Menu Container -->
    <div class="table-grid-container">
        <!-- Category Tabs -->
        <div class="category-tabs">
            <button class="tab-btn active" data-category="all">All</button>
            <?php foreach ($categories as $cat): ?>
                <button class="tab-btn" data-category="<?php echo $cat; ?>">
                    <?php echo ucfirst($cat); ?>
                </button>
            <?php endforeach; ?>
        </div>

        <!-- Menu Grid -->
        <div class="menu-grid">
            <?php foreach ($menuItems as $item): 
                $imgPath = $imageMap[$item['image']] ?? '';
                $hasImage = !empty($imgPath) && file_exists($imgPath);
            ?>
                <div class="menu-item" data-category="<?php echo $item['category']; ?>">
                    <?php if ($hasImage): ?>
                        <img src="<?php echo $imgPath; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="menu-item-image">
                    <?php else: ?>
                        <div class="menu-item-image" style="background: linear-gradient(135deg, #1a1a24 0%, #14141B 100%); display: flex; align-items: center; justify-content: center;">
                            <div style="font-size: 60px; opacity: 0.3;">
                                <?php 
                                $emojiMap = [
                                    'sushi' => '\1f363', 'sashimi' => '\1f41f', 'rolls' => '\1f359',
                                    'appetizers' => '\1f95f', 'drinks' => '\1f376'
                                ];
                                echo $emojiMap[$item['category']] ?? '\1f37d';
                                ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="menu-item-content">
                        <h3 class="menu-item-name"><?php echo htmlspecialchars($item['name']); ?></h3>
                        <p class="menu-item-desc"><?php echo htmlspecialchars($item['description']); ?></p>
                        <div class="menu-item-footer">
                            <span class="menu-item-price">₱<?php echo number_format($item['price'], 2); ?></span>
                            <button class="btn btn-primary btn-add-to-cart" 
                                    data-id="<?php echo $item['id']; ?>"
                                    data-name="<?php echo htmlspecialchars($item['name']); ?>"
                                    data-price="<?php echo $item['price']; ?>"
                                    data-image="<?php echo $imgPath; ?>"
                                    style="padding: 8px 16px; font-size: 14px;">
                                Add to Order
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Cart Slide Panel -->
    <div class="cart-overlay slide-overlay"></div>
    <div class="cart-panel slide-panel">
        <button class="cart-close slide-close">&times;</button>
        <h2 class="panel-title">Your Pre-Order</h2>
        
        <div class="cart-items" style="min-height: 200px;">
            <p style="text-align: center; color: var(--color-text-muted); padding: 40px 0;">
                Your cart is empty<br>
                <small>Add items from the menu</small>
            </p>
        </div>
        
        <div class="cart-summary">
            <div class="summary-row">
                <span>Subtotal</span>
                <span class="cart-subtotal">₱0.00</span>
            </div>
            <div class="summary-row">
                <span>Tax (8%)</span>
                <span class="cart-tax">₱0.00</span>
            </div>
            <div class="summary-row total">
                <span>Total</span>
                <span class="cart-total">₱0.00</span>
            </div>
        </div>
        
        <div class="panel-buttons">
            <a href="reservation.php?table_id=<?php echo $tableId; ?><?php echo $selectedDate ? '&date='.urlencode($selectedDate) : ''; ?><?php echo $selectedTime ? '&time='.urlencode($selectedTime) : ''; ?>&from_menu=1" class="btn btn-primary btn-full">
                Proceed to Reservation
            </a>
            <button class="btn btn-glass btn-full btn-clear-cart">Clear Cart</button>
        </div>
    </div>

    <script src="assets/js/main.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Clear cart button - using cart.clear() which shows toast
            document.querySelector('.btn-clear-cart')?.addEventListener('click', () => {
                if (cart && cart.items.length > 0) {
                    cart.clear();
                    showToast('Cart cleared successfully', 'success');
                    setTimeout(() => location.reload(), 500);
                } else {
                    showToast('Cart is already empty', 'info');
                }
            });
        });
    </script>
</body>
</html>
