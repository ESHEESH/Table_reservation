<?php
/**
 * ============================================================================
 * VIP CUSTOMER SERVICE - ALGORITHM OVERVIEW
 * ============================================================================
 * 
 * PRIMARY ALGORITHMS:
 * 1. Hash Table Lookup (O(1)) - VIP customer identification by phone
 * 2. Priority Score Calculation (O(1)) - Determines reservation priority
 * 3. Auto-Promotion Algorithm (O(1)) - Upgrades customers based on history
 * 
 * DATA STRUCTURES:
 * - Database Index (Hash-based) on phone column for O(1) lookups
 * - Associative Array for VIP tier mapping
 * 
 * COMPLEXITY ANALYSIS:
 * - isVIP(): O(1) - Indexed database lookup
 * - calculatePriorityScore(): O(1) - Constant time tier lookup
 * - checkAutoPromotion(): O(1) - Single aggregation query with index
 * - updateVIPStats(): O(1) - Single UPDATE query
 * 
 * VIP TIER SYSTEM:
 * Priority Score (Lower = Higher Priority):
 * - Platinum: 1000 (20+ bookings, ₱10,000+ spent)
 * - Gold: 2000 (12+ bookings, ₱6,000+ spent)
 * - Silver: 3000 (6+ bookings, ₱3,000+ spent)
 * - Bronze: 4000 (3+ bookings, ₱1,500+ spent)
 * - Regular: 5000 + timestamp (FIFO for non-VIP)
 * 
 * INTEGRATION:
 * - Used by: app/api/create-reservation.php (priority calculation)
 * - Used by: app/admin/admin.php (VIP status display)
 * - Database: vip_customers table with phone index
 * 
 * ============================================================================
 */

/**
 * VIP Customer Service
 * Handles VIP customer identification and benefits
 */

class VIPService {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Check if customer is VIP by phone number - O(1) with index
     */
    public function isVIP($phone) {
        $stmt = $this->pdo->prepare("SELECT vip_level FROM vip_customers WHERE phone = ?");
        $stmt->execute([$phone]);
        $result = $stmt->fetch();
        return $result ? $result['vip_level'] : null;
    }
    
    /**
     * Get VIP customer details
     */
    public function getVIPCustomer($phone) {
        $stmt = $this->pdo->prepare("SELECT * FROM vip_customers WHERE phone = ?");
        $stmt->execute([$phone]);
        return $stmt->fetch();
    }
    
    /**
     * Calculate priority score for reservation
     * Lower score = Higher priority
     */
    public function calculatePriorityScore($phone, $bookingTimestamp) {
        $vipLevel = $this->isVIP($phone);
        
        if (!$vipLevel) {
            // Regular customer: 5000 + timestamp
            return 5000 + $bookingTimestamp;
        }
        
        // VIP priority levels
        $vipScores = [
            'platinum' => 1000,
            'gold' => 2000,
            'silver' => 3000,
            'bronze' => 4000
        ];
        
        return $vipScores[$vipLevel] ?? 5000;
    }
    
    /**
     * Update VIP customer stats after booking
     */
    public function updateVIPStats($phone, $amount) {
        $stmt = $this->pdo->prepare("
            UPDATE vip_customers 
            SET total_bookings = total_bookings + 1,
                total_spent = total_spent + ?,
                last_booking_date = NOW()
            WHERE phone = ?
        ");
        $stmt->execute([$amount, $phone]);
    }
    
    /**
     * Auto-promote customer to VIP based on booking history
     */
    public function checkAutoPromotion($phone) {
        // Count customer's confirmed reservations
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as booking_count, SUM(total_amount) as total_spent
            FROM reservations 
            WHERE phone = ? AND status = 'confirmed'
        ");
        $stmt->execute([$phone]);
        $stats = $stmt->fetch();
        
        $bookingCount = $stats['booking_count'] ?? 0;
        $totalSpent = $stats['total_spent'] ?? 0;
        
        // Auto-promotion rules
        $newLevel = null;
        if ($bookingCount >= 20 && $totalSpent >= 10000) {
            $newLevel = 'platinum';
        } elseif ($bookingCount >= 12 && $totalSpent >= 6000) {
            $newLevel = 'gold';
        } elseif ($bookingCount >= 6 && $totalSpent >= 3000) {
            $newLevel = 'silver';
        } elseif ($bookingCount >= 3 && $totalSpent >= 1500) {
            $newLevel = 'bronze';
        }
        
        if ($newLevel) {
            // Check if already VIP
            $existing = $this->getVIPCustomer($phone);
            if (!$existing) {
                // Create new VIP
                $stmt = $this->pdo->prepare("
                    INSERT INTO vip_customers (phone, name, vip_level, total_bookings, total_spent)
                    SELECT phone, name, ?, ?, ?
                    FROM reservations 
                    WHERE phone = ? 
                    LIMIT 1
                ");
                $stmt->execute([$newLevel, $bookingCount, $totalSpent, $phone]);
                return ['promoted' => true, 'level' => $newLevel];
            }
        }
        
        return ['promoted' => false];
    }
    
    /**
     * Get VIP benefits description
     */
    public function getVIPBenefits($vipLevel) {
        $benefits = [
            'platinum' => [
                'priority' => 'Highest Priority',
                'discount' => '20% off all bookings',
                'perks' => ['Free dessert', 'Priority seating', 'Complimentary drinks', 'Personal concierge']
            ],
            'gold' => [
                'priority' => 'High Priority',
                'discount' => '15% off all bookings',
                'perks' => ['Free appetizer', 'Priority seating', 'Complimentary tea']
            ],
            'silver' => [
                'priority' => 'Medium Priority',
                'discount' => '10% off all bookings',
                'perks' => ['Priority seating', 'Birthday special']
            ],
            'bronze' => [
                'priority' => 'Low Priority',
                'discount' => '5% off all bookings',
                'perks' => ['Early booking access']
            ]
        ];
        
        return $benefits[$vipLevel] ?? null;
    }
}
