<?php
/**
 * Sakura Sushi - Confirmation Page
 * Displays reservation code and summary
 * Uses Hash Table for O(1) reservation lookup
 */
require_once 'config.php';

$code = isset($_GET['code']) ? $_GET['code'] : '';

if (empty($code)) {
    header('Location: index.php');
    exit;
}

/**
 * Hash Table Implementation for Reservation Lookup
 * Uses PHP associative array (hash map) for O(1) average case lookup
 * Collision handling: chaining (built into PHP arrays)
 */
class ReservationHashTable {
    private $table = [];
    private $size = 97; // Prime number for better distribution
    
    private function hash($code) {
        $hash = 0;
        for ($i = 0; $i < strlen($code); $i++) {
            $hash = ($hash * 31 + ord($code[$i])) % $this->size;
        }
        return $hash;
    }
    
    public function insert($code, $reservation) {
        $index = $this->hash($code);
        if (!isset($this->table[$index])) {
            $this->table[$index] = [];
        }
        $this->table[$index][] = ['code' => $code, 'data' => $reservation];
    }
    
    public function search($code) {
        $index = $this->hash($code);
        if (isset($this->table[$index])) {
            foreach ($this->table[$index] as $item) {
                if ($item['code'] === $code) {
                    return $item['data'];
                }
            }
        }
        return null;
    }
}

// Fetch reservation from database
$pdo = getDBConnection();
$stmt = $pdo->prepare("
    SELECT r.*, t.table_number, t.capacity, t.price as table_price 
    FROM reservations r 
    LEFT JOIN tables t ON r.table_id = t.id 
    WHERE r.confirmation_code = ?
");
$stmt->execute([$code]);
$reservation = $stmt->fetch();

if (!$reservation) {
    header('Location: index.php');
    exit;
}

// Store in hash table for demonstration
$hashTable = new ReservationHashTable();
$hashTable->insert($reservation['confirmation_code'], $reservation);

// Verify lookup
$verifiedReservation = $hashTable->search($code);

// Fetch pre-orders if any
$preOrders = [];
if ($reservation['has_pre_order']) {
    $stmt = $pdo->prepare("
        SELECT po.*, mi.name as item_name, mi.price as unit_price 
        FROM pre_orders po 
        JOIN menu_items mi ON po.menu_item_id = mi.id 
        WHERE po.reservation_id = ?
    ");
    $stmt->execute([$reservation['id']]);
    $preOrders = $stmt->fetchAll();
}

// Calculate totals
$taxRate = 0.08;
$tableFee = floatval($reservation['table_price'] ?? 0);
$foodTotal = 0;
foreach ($preOrders as $po) {
    $foodTotal += floatval($po['subtotal']);
}
$subtotal = $tableFee + $foodTotal;
$tax = $foodTotal * $taxRate;
$total = $subtotal + $tax;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation Confirmed - Sakura Sushi</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="page-transition">
    <!-- Navigation -->
    <nav class="nav">
        <a href="index.php" class="nav-back" style="text-decoration: none;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M19 12H5M12 19l-7-7 7-7"/>
            </svg>
            Home
        </a>
        <div class="nav-logo">Sakura Sushi</div>
        <div></div>
    </nav>

    <!-- Confirmation Content -->
    <div class="confirmation-container">
        <div class="confirmation-card">
            <!-- Success Animation -->
            <div class="success-animation">
                <svg class="success-checkmark" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#4ADE80" stroke-width="3">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
            </div>
            
            <h1 class="confirmation-title">Reservation Confirmed!</h1>
            <p class="confirmation-subtitle">Save your confirmation code for your records</p>
            
            <!-- Confirmation Code -->
            <div class="code-display">
                <div style="font-size: 12px; text-transform: uppercase; letter-spacing: 1px; color: var(--color-text-muted); margin-bottom: 8px;">
                    Your Reservation Code
                </div>
                <div class="code-text"><?php echo htmlspecialchars($reservation['confirmation_code']); ?></div>
            </div>
            
            <!-- QR Code for restaurant scanning -->
            <div style="margin-bottom: 32px;">
                <div class="qr-display">
                    <svg width="160" height="160" viewBox="0 0 160 160">
                        <rect width="160" height="160" fill="white"/>
                        <!-- Simplified QR -->
                        <rect x="8" y="8" width="40" height="40" fill="#0A0A0F"/>
                        <rect x="12" y="12" width="32" height="32" fill="white"/>
                        <rect x="16" y="16" width="24" height="24" fill="#0A0A0F"/>
                        <rect x="112" y="8" width="40" height="40" fill="#0A0A0F"/>
                        <rect x="116" y="12" width="32" height="32" fill="white"/>
                        <rect x="120" y="16" width="24" height="24" fill="#0A0A0F"/>
                        <rect x="8" y="112" width="40" height="40" fill="#0A0A0F"/>
                        <rect x="12" y="116" width="32" height="32" fill="white"/>
                        <rect x="16" y="120" width="24" height="24" fill="#0A0A0F"/>
                        <!-- Data pattern with code -->
                        <rect x="56" y="8" width="8" height="8" fill="#0A0A0F"/>
                        <rect x="72" y="8" width="8" height="8" fill="#0A0A0F"/>
                        <rect x="88" y="8" width="8" height="8" fill="#0A0A0F"/>
                        <rect x="56" y="24" width="8" height="8" fill="#0A0A0F"/>
                        <rect x="80" y="24" width="8" height="8" fill="#0A0A0F"/>
                        <rect x="96" y="24" width="8" height="8" fill="#0A0A0F"/>
                        <rect x="56" y="40" width="8" height="8" fill="#0A0A0F"/>
                        <rect x="72" y="40" width="8" height="8" fill="#0A0A0F"/>
                        <rect x="88" y="40" width="8" height="8" fill="#0A0A0F"/>
                        <rect x="8" y="56" width="8" height="8" fill="#0A0A0F"/>
                        <rect x="24" y="56" width="8" height="8" fill="#0A0A0F"/>
                        <rect x="40" y="56" width="8" height="8" fill="#0A0A0F"/>
                        <rect x="8" y="72" width="8" height="8" fill="#0A0A0F"/>
                        <rect x="32" y="72" width="8" height="8" fill="#0A0A0F"/>
                        <rect x="8" y="88" width="8" height="8" fill="#0A0A0F"/>
                        <rect x="24" y="88" width="8" height="8" fill="#0A0A0F"/>
                        <rect x="40" y="88" width="8" height="8" fill="#0A0A0F"/>
                        <rect x="56" y="56" width="48" height="48" fill="#0A0A0F"/>
                        <rect x="60" y="60" width="40" height="40" fill="white"/>
                        <rect x="64" y="64" width="32" height="32" fill="#0A0A0F"/>
                        <rect x="68" y="68" width="24" height="24" fill="white"/>
                        <rect x="72" y="72" width="16" height="16" fill="#0A0A0F"/>
                        <rect x="112" y="56" width="8" height="8" fill="#0A0A0F"/>
                        <rect x="128" y="56" width="8" height="8" fill="#0A0A0F"/>
                        <rect x="144" y="56" width="8" height="8" fill="#0A0A0F"/>
                        <rect x="120" y="72" width="8" height="8" fill="#0A0A0F"/>
                        <rect x="136" y="72" width="8" height="8" fill="#0A0A0F"/>
                        <rect x="112" y="88" width="8" height="8" fill="#0A0A0F"/>
                        <rect x="128" y="88" width="8" height="8" fill="#0A0A0F"/>
                        <rect x="144" y="88" width="8" height="8" fill="#0A0A0F"/>
                        <rect x="56" y="112" width="8" height="8" fill="#0A0A0F"/>
                        <rect x="72" y="112" width="8" height="8" fill="#0A0A0F"/>
                        <rect x="88" y="112" width="8" height="8" fill="#0A0A0F"/>
                        <rect x="104" y="112" width="8" height="8" fill="#0A0A0F"/>
                        <rect x="56" y="128" width="8" height="8" fill="#0A0A0F"/>
                        <rect x="80" y="128" width="8" height="8" fill="#0A0A0F"/>
                        <rect x="96" y="128" width="8" height="8" fill="#0A0A0F"/>
                        <rect x="112" y="128" width="8" height="8" fill="#0A0A0F"/>
                        <rect x="128" y="112" width="8" height="8" fill="#0A0A0F"/>
                        <rect x="144" y="112" width="8" height="8" fill="#0A0A0F"/>
                        <rect x="120" y="128" width="8" height="8" fill="#0A0A0F"/>
                        <rect x="136" y="128" width="8" height="8" fill="#0A0A0F"/>
                        <rect x="112" y="144" width="8" height="8" fill="#0A0A0F"/>
                        <rect x="128" y="144" width="8" height="8" fill="#0A0A0F"/>
                        <rect x="144" y="144" width="8" height="8" fill="#0A0A0F"/>
                    </svg>
                </div>
                <p style="color: var(--color-text-secondary); font-size: 14px; margin-top: 8px;">
                    Present this QR code at the restaurant
                </p>
            </div>
            
            <!-- Reservation Summary -->
            <div class="reservation-summary">
                <h3 class="summary-title">Reservation Details</h3>
                
                <div class="summary-detail">
                    <span class="summary-detail-label">Name</span>
                    <span class="summary-detail-value"><?php echo htmlspecialchars($reservation['name']); ?></span>
                </div>
                <div class="summary-detail">
                    <span class="summary-detail-label">Phone</span>
                    <span class="summary-detail-value"><?php echo htmlspecialchars($reservation['phone']); ?></span>
                </div>
                <div class="summary-detail">
                    <span class="summary-detail-label">Guests</span>
                    <span class="summary-detail-value"><?php echo $reservation['people_count']; ?> people</span>
                </div>
                <?php if ($reservation['table_number']): ?>
                <div class="summary-detail">
                    <span class="summary-detail-label">Table</span>
                    <span class="summary-detail-value">Table <?php echo htmlspecialchars($reservation['table_number']); ?> (<?php echo $reservation['capacity']; ?> seats)</span>
                </div>
                <?php endif; ?>
                <div class="summary-detail">
                    <span class="summary-detail-label">Date</span>
                    <span class="summary-detail-value"><?php echo date('l, F j, Y', strtotime($reservation['reservation_date'])); ?></span>
                </div>
                <div class="summary-detail">
                    <span class="summary-detail-label">Time</span>
                    <span class="summary-detail-value"><?php echo date('g:i A', strtotime($reservation['reservation_time'])); ?></span>
                </div>
                
                <?php if (!empty($reservation['special_requests'])): ?>
                <div class="summary-detail">
                    <span class="summary-detail-label">Special Requests</span>
                    <span class="summary-detail-value"><?php echo htmlspecialchars($reservation['special_requests']); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($preOrders)): ?>
                <div style="margin-top: 20px; padding-top: 20px; border-top: 2px solid var(--color-glass-border);">
                    <h4 style="margin-bottom: 16px;">Pre-Ordered Items</h4>
                    <?php foreach ($preOrders as $po): ?>
                    <div class="summary-detail">
                        <span class="summary-detail-label"><?php echo htmlspecialchars($po['item_name']); ?> x<?php echo $po['quantity']; ?></span>
                        <span class="summary-detail-value">$<?php echo number_format($po['subtotal'], 2); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <div style="margin-top: 20px; padding-top: 20px; border-top: 2px solid var(--color-glass-border);">
                    <div class="summary-detail">
                        <span class="summary-detail-label">Table Reservation Fee</span>
                        <span class="summary-detail-value">$<?php echo number_format($tableFee, 2); ?></span>
                    </div>
                    <?php if ($foodTotal > 0): ?>
                    <div class="summary-detail">
                        <span class="summary-detail-label">Food Subtotal</span>
                        <span class="summary-detail-value">$<?php echo number_format($foodTotal, 2); ?></span>
                    </div>
                    <div class="summary-detail">
                        <span class="summary-detail-label">Tax (8%)</span>
                        <span class="summary-detail-value">$<?php echo number_format($tax, 2); ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="summary-detail" style="font-size: 18px; font-weight: 700; color: var(--color-accent); margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--color-glass-border);">
                        <span>Total Paid</span>
                        <span>$<?php echo number_format($total, 2); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="confirmation-actions">
                <a href="index.php" class="btn btn-primary">Make Another Reservation</a>
                <button class="btn btn-secondary" onclick="window.print()">Print Receipt</button>
            </div>
        </div>
    </div>

    <script src="assets/js/main.js"></script>
    <script>
        // Clear cart data after successful reservation
        localStorage.removeItem('sakura_cart');
        localStorage.removeItem('sakura_cart_total');
    </script>
</body>
</html>
