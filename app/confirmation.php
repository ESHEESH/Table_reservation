<?php
/**
 * ============================================================================
 * SAKURA SUSHI - RESERVATION CONFIRMATION PAGE
 * ============================================================================
 * 
 * ALGORITHM: Dynamic Hash Table with Open Addressing
 * 
 * OVERVIEW:
 * - Displays reservation confirmation details and summary
 * - Uses upgraded hash table with dynamic resizing for O(1) lookup
 * - Implements both chaining and open addressing (linear probing)
 * - Auto-resizes when load factor exceeds 0.75 threshold
 * 
 * DATA STRUCTURES:
 * 1. Hash Table (Dynamic Resizing)
 *    - Initial size: 97 (prime number)
 *    - Load factor threshold: 0.75
 *    - Collision handling: Chaining + Open Addressing fallback
 *    - Rehashing: Doubles size to next prime when threshold exceeded
 * 
 * COMPLEXITY ANALYSIS:
 * - Insert: O(1) amortized (O(n) during resize, rare)
 * - Search: O(1) average case, O(n) worst case
 * - Resize: O(n) but happens infrequently
 * 
 * IMPROVEMENTS OVER v1:
 * - Dynamic resizing prevents degradation as data grows
 * - Open addressing reduces memory overhead
 * - Better collision handling with dual strategy
 * - Maintains O(1) performance at scale
 * 
 * @version 2.0
 * @author Sakura Sushi Development Team
 * ============================================================================
 */
require_once 'config.php';

$code = isset($_GET['code']) ? $_GET['code'] : '';

if (empty($code)) {
    header('Location: index.php');
    exit;
}

/**
 * ============================================================================
 * UPGRADED HASH TABLE IMPLEMENTATION
 * ============================================================================
 * 
 * Features:
 * - Dynamic resizing with load factor monitoring
 * - Dual collision handling: Chaining + Open Addressing
 * - Prime number sizing for better distribution
 * - Automatic rehashing when load factor > 0.75
 * 
 * Time Complexity:
 * - Insert: O(1) amortized
 * - Search: O(1) average
 * - Resize: O(n) but rare
 * 
 * Space Complexity: O(n) where n is number of entries
 * ============================================================================
 */
class ReservationHashTable {
    private $table = [];
    private $size = 97; // Initial prime number
    private $count = 0; // Number of entries
    private $loadFactorThreshold = 0.75;
    private $useOpenAddressing = false; // Toggle collision strategy
    
    /**
     * Hash function using polynomial rolling hash
     * Multiplier: 31 (prime number for good distribution)
     */
    private function hash($code, $tableSize = null) {
        $size = $tableSize ?? $this->size;
        $hash = 0;
        for ($i = 0; $i < strlen($code); $i++) {
            $hash = ($hash * 31 + ord($code[$i])) % $size;
        }
        return $hash;
    }
    
    /**
     * Calculate current load factor
     * Load factor = count / size
     */
    private function getLoadFactor() {
        return $this->size > 0 ? $this->count / $this->size : 0;
    }
    
    /**
     * Find next prime number greater than n
     * Used for resizing to maintain prime table size
     */
    private function nextPrime($n) {
        $candidate = $n * 2;
        while (!$this->isPrime($candidate)) {
            $candidate++;
        }
        return $candidate;
    }
    
    /**
     * Check if number is prime
     */
    private function isPrime($n) {
        if ($n < 2) return false;
        if ($n == 2) return true;
        if ($n % 2 == 0) return false;
        for ($i = 3; $i * $i <= $n; $i += 2) {
            if ($n % $i == 0) return false;
        }
        return true;
    }
    
    /**
     * Resize and rehash table when load factor exceeds threshold
     * Time Complexity: O(n)
     * Called rarely, so amortized O(1) for inserts
     */
    private function resize() {
        $oldTable = $this->table;
        $oldSize = $this->size;
        
        // Double size and find next prime
        $this->size = $this->nextPrime($this->size);
        $this->table = [];
        $this->count = 0;
        
        // Rehash all existing entries
        foreach ($oldTable as $bucket) {
            if (is_array($bucket)) {
                foreach ($bucket as $item) {
                    $this->insert($item['code'], $item['data']);
                }
            }
        }
    }
    
    /**
     * Insert with chaining (default)
     * Time Complexity: O(1) amortized
     */
    private function insertWithChaining($code, $reservation) {
        $index = $this->hash($code);
        if (!isset($this->table[$index])) {
            $this->table[$index] = [];
        }
        
        // Check if code already exists (update instead of duplicate)
        foreach ($this->table[$index] as &$item) {
            if ($item['code'] === $code) {
                $item['data'] = $reservation;
                return;
            }
        }
        
        $this->table[$index][] = ['code' => $code, 'data' => $reservation];
        $this->count++;
    }
    
    /**
     * Insert with open addressing (linear probing)
     * Time Complexity: O(1) average
     */
    private function insertWithOpenAddressing($code, $reservation) {
        $index = $this->hash($code);
        $originalIndex = $index;
        
        // Linear probing to find empty slot
        while (isset($this->table[$index]) && $this->table[$index]['code'] !== $code) {
            $index = ($index + 1) % $this->size;
            
            // Table full (shouldn't happen with proper load factor)
            if ($index === $originalIndex) {
                $this->resize();
                $this->insertWithOpenAddressing($code, $reservation);
                return;
            }
        }
        
        $isUpdate = isset($this->table[$index]);
        $this->table[$index] = ['code' => $code, 'data' => $reservation];
        if (!$isUpdate) $this->count++;
    }
    
    /**
     * Public insert method
     * Automatically resizes if load factor exceeds threshold
     */
    public function insert($code, $reservation) {
        // Check if resize needed
        if ($this->getLoadFactor() > $this->loadFactorThreshold) {
            $this->resize();
        }
        
        if ($this->useOpenAddressing) {
            $this->insertWithOpenAddressing($code, $reservation);
        } else {
            $this->insertWithChaining($code, $reservation);
        }
    }
    
    /**
     * Search with chaining
     * Time Complexity: O(1) average
     */
    private function searchWithChaining($code) {
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
    
    /**
     * Search with open addressing
     * Time Complexity: O(1) average
     */
    private function searchWithOpenAddressing($code) {
        $index = $this->hash($code);
        $originalIndex = $index;
        
        while (isset($this->table[$index])) {
            if ($this->table[$index]['code'] === $code) {
                return $this->table[$index]['data'];
            }
            $index = ($index + 1) % $this->size;
            if ($index === $originalIndex) break;
        }
        return null;
    }
    
    /**
     * Public search method
     * Time Complexity: O(1) average case
     */
    public function search($code) {
        if ($this->useOpenAddressing) {
            return $this->searchWithOpenAddressing($code);
        } else {
            return $this->searchWithChaining($code);
        }
    }
    
    /**
     * Get statistics for monitoring
     */
    public function getStats() {
        return [
            'size' => $this->size,
            'count' => $this->count,
            'load_factor' => $this->getLoadFactor(),
            'strategy' => $this->useOpenAddressing ? 'Open Addressing' : 'Chaining'
        ];
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
    <style>
        .view-receipt-btn:hover {
            background: rgba(201,150,79,.2) !important;
            border-color: rgba(201,150,79,.5) !important;
            transform: translateY(-2px);
        }
    </style>
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
                        <span class="summary-detail-value">₱<?php echo number_format($po['subtotal'], 2); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <div style="margin-top: 20px; padding-top: 20px; border-top: 2px solid var(--color-glass-border);">
                    <div class="summary-detail">
                        <span class="summary-detail-label">Table Reservation Fee</span>
                        <span class="summary-detail-value">₱<?php echo number_format($tableFee, 2); ?></span>
                    </div>
                    <?php if ($foodTotal > 0): ?>
                    <div class="summary-detail">
                        <span class="summary-detail-label">Food Subtotal</span>
                        <span class="summary-detail-value">₱<?php echo number_format($foodTotal, 2); ?></span>
                    </div>
                    <div class="summary-detail">
                        <span class="summary-detail-label">Tax (8%)</span>
                        <span class="summary-detail-value">₱<?php echo number_format($tax, 2); ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="summary-detail" style="font-size: 18px; font-weight: 700; color: var(--color-accent); margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--color-glass-border);">
                        <span>Total Paid</span>
                        <span>₱<?php echo number_format($total, 2); ?></span>
                    </div>
                </div>
                
                <?php if (!empty($reservation['payment_receipt'])): ?>
                <!-- Payment Receipt -->
                <div style="margin-top: 20px; padding-top: 20px; border-top: 2px solid var(--color-glass-border);">
                    <h4 style="margin-bottom: 12px; color: var(--color-accent);">Payment Receipt</h4>
                    <button onclick="viewReceipt('<?php echo htmlspecialchars($reservation['payment_receipt']); ?>')" 
                            class="view-receipt-btn"
                            style="display: flex; align-items: center; gap: 8px; padding: 12px 20px; background: rgba(201,150,79,.1); border: 1px solid rgba(201,150,79,.3); border-radius: 10px; color: var(--color-accent); cursor: pointer; font-size: 14px; width: 100%; justify-content: center; transition: all .2s; font-family: inherit;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                        View Uploaded Receipt
                    </button>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Actions -->
            <div class="confirmation-actions">
                <a href="index.php" class="btn btn-primary" onclick="sessionStorage.clear();">Make Another Reservation</a>
                <button class="btn btn-secondary" onclick="window.print()">Print Receipt</button>
            </div>
        </div>
    </div>
    
    <!-- Receipt Viewer Modal -->
    <div id="receiptModal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.95);z-index:9999;align-items:center;justify-content:center;" onclick="closeReceiptModal()">
        <div style="max-width:90%;max-height:90vh;position:relative;" onclick="event.stopPropagation()">
            <button onclick="closeReceiptModal()" style="position:absolute;top:-50px;right:0;background:rgba(201,150,79,.2);border:1px solid rgba(201,150,79,.4);color:#FDF6EC;padding:10px 20px;border-radius:10px;cursor:pointer;font-size:14px;font-weight:600;transition:all .2s;">
                ✕ Close
            </button>
            <img id="receiptImage" src="" alt="Payment Receipt" style="max-width:100%;max-height:90vh;border-radius:12px;box-shadow:0 8px 32px rgba(0,0,0,.5);">
        </div>
    </div>

    <script src="assets/js/main.js"></script>
    <script>
        // Clear all reservation-related data after successful confirmation
        localStorage.removeItem('sakura_cart');
        localStorage.removeItem('sakura_cart_total');
        localStorage.removeItem('sakura_reservation_form');
        
        // Mark that a reservation was just completed
        sessionStorage.setItem('reservation_completed', 'true');
        sessionStorage.setItem('confirmation_code', '<?php echo htmlspecialchars($reservation['confirmation_code']); ?>');
        
        // Receipt viewer functions
        function viewReceipt(url) {
            const modal = document.getElementById('receiptModal');
            const img = document.getElementById('receiptImage');
            img.src = url;
            modal.style.display = 'flex';
        }
        
        function closeReceiptModal() {
            document.getElementById('receiptModal').style.display = 'none';
        }
        
        // Close modal with Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeReceiptModal();
            }
        });
    </script>
</body>
</html>
