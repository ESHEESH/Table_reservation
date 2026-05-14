# Algorithm Improvements: V1.2 vs V2.0

## Executive Summary

This document compares the algorithms used in **V1.2 (Old System)** versus **V2.0 (Improved System)**, highlighting performance improvements, complexity analysis, and the rationale behind each upgrade.

---

## 1. Shopping Cart System

### File: `app/assets/js/main.js`

#### V1.2 - Singly Linked List
```javascript
class CartLinkedList {
    constructor() {
        this.head = null;
        this.size = 0;
    }
    
    // Operations:
    append(item)         // O(n) - traverse to end
    remove(itemId)       // O(n) - search then delete
    find(itemId)         // O(n) - linear search
    updateQuantity(id)   // O(n) - search then update
}
```

**Complexity:**
- Add item: **O(n)** - Must traverse to end of list
- Remove item: **O(n)** - Must search through list
- Find item: **O(n)** - Linear search
- Update quantity: **O(n)** - Search then modify

**Problems:**
- Slow operations as cart grows
- No quick duplicate detection
- Must traverse entire list for every operation
- Poor user experience with large carts

---

#### V2.0 - HashMap + Doubly Linked List
```javascript
class CartHashMapList {
    constructor() {
        this.head = null;
        this.tail = null;
        this.map = new Map();  // HashMap for O(1) access
        this.size = 0;
    }
    
    // Operations:
    append(item)         // O(1) - direct tail access + map insert
    remove(itemId)       // O(1) - map lookup + doubly linked delete
    find(itemId)         // O(1) - direct map lookup
    updateQuantity(id)   // O(1) - map lookup + update
}
```

**Complexity:**
- Add item: **O(1)** - HashMap provides instant duplicate check
- Remove item: **O(1)** - HashMap finds node, doubly linked list removes it
- Find item: **O(1)** - Direct HashMap lookup
- Update quantity: **O(1)** - HashMap access

**Improvements:**
- ✅ **Instant operations** regardless of cart size
- ✅ **Duplicate detection** in constant time
- ✅ **Maintains insertion order** via linked list
- ✅ **Better UX** - no lag with large carts

**Why the upgrade?**
- Users can add/remove items instantly
- Prevents duplicate items efficiently
- Scales to hundreds of items without slowdown
- Best of both worlds: HashMap speed + List ordering

---

## 2. Reservation Lookup System

### File: `app/confirmation.php`

#### V1.2 - Fixed-Size Hash Table
```php
class ReservationHashTable {
    private $size = 97;  // Fixed size
    private $buckets;
    
    function hash($code) {
        return crc32($code) % $this->size;
    }
    
    function insert($code, $data) {
        $index = $this->hash($code);
        // Chaining for collisions
        $this->buckets[$index][] = $data;
    }
}
```

**Complexity:**
- Lookup: **O(1) average**, **O(n) worst case** when many collisions
- Insert: **O(1) average**
- No resizing capability

**Problems:**
- ❌ **Fixed capacity** of 97 reservations
- ❌ **Performance degrades** as reservations grow
- ❌ **No load factor monitoring**
- ❌ **Collision chains grow** over time
- ❌ **Silent failure** - no warning when full

---

#### V2.0 - Dynamic Hash Table with Resizing
```php
class DynamicHashTable {
    private $size = 97;
    private $count = 0;
    private $loadFactorThreshold = 0.75;
    private $buckets;
    
    function insert($code, $data) {
        // Check load factor
        if (($this->count / $this->size) > $this->loadFactorThreshold) {
            $this->resize();  // Double size and rehash
        }
        
        $index = $this->hash($code);
        
        // Try chaining first
        if (!isset($this->buckets[$index])) {
            $this->buckets[$index] = [];
        }
        
        // Fallback to open addressing if chain too long
        if (count($this->buckets[$index]) > 3) {
            $this->openAddressing($code, $data);
        } else {
            $this->buckets[$index][] = $data;
        }
        
        $this->count++;
    }
    
    function resize() {
        $oldBuckets = $this->buckets;
        $this->size = $this->size * 2;
        $this->buckets = [];
        $this->count = 0;
        
        // Rehash all items
        foreach ($oldBuckets as $bucket) {
            foreach ($bucket as $item) {
                $this->insert($item['code'], $item);
            }
        }
    }
}
```

**Complexity:**
- Lookup: **O(1) average**, maintained even with growth
- Insert: **O(1) amortized** (occasional O(n) during resize)
- Resize: **O(n)** but rare

**Improvements:**
- ✅ **Automatic resizing** when 75% full
- ✅ **Maintains O(1) performance** as data grows
- ✅ **Hybrid collision handling** (chaining + open addressing)
- ✅ **Scales to thousands** of reservations
- ✅ **Load factor monitoring** prevents degradation

**Why the upgrade?**
- System won't slow down as business grows
- Handles peak seasons with many reservations
- Prevents collision chain buildup
- Professional-grade hash table implementation

---

## 3. VIP Priority System

### File: `app/api/create-reservation.php`, `app/admin/admin.php`

#### V1.2 - No Priority System
```php
// Simple FIFO (First In, First Out)
$stmt = $pdo->prepare("
    SELECT * FROM reservations 
    WHERE status = 'pending'
    ORDER BY created_at ASC
");
```

**Complexity:**
- Query: **O(n log n)** for sorting by timestamp
- No VIP differentiation
- No priority handling

**Problems:**
- ❌ **No VIP recognition**
- ❌ **All customers treated equally**
- ❌ **No loyalty rewards**
- ❌ **Can't prioritize high-value customers**
- ❌ **No competitive advantage**

---

#### V2.0 - Priority Queue with VIP Tiers
```php
// Priority Queue (Min-Heap) Implementation
class PriorityQueue {
    private $heap = [];
    
    function insert($priority, $data) {
        $this->heap[] = ['priority' => $priority, 'data' => $data];
        $this->heapifyUp(count($this->heap) - 1);
    }
    
    function extractMin() {
        // Get highest priority (lowest number)
        $min = $this->heap[0];
        $this->heap[0] = array_pop($this->heap);
        $this->heapifyDown(0);
        return $min;
    }
}

// VIP Service with O(1) Lookup
class VIPService {
    function calculatePriorityScore($phone, $timestamp) {
        $vipLevel = $this->isVIP($phone);  // O(1) with index
        
        $scores = [
            'platinum' => 1000,
            'gold' => 2000,
            'silver' => 3000,
            'bronze' => 4000
        ];
        
        return $scores[$vipLevel] ?? (5000 + $timestamp);
    }
}

// Database query with priority
$stmt = $pdo->prepare("
    SELECT * FROM reservations 
    WHERE status = 'pending'
    ORDER BY priority_score ASC, created_at ASC
");
```

**Complexity:**
- Insert: **O(log n)** - Heap insertion
- Extract min: **O(log n)** - Heap extraction
- VIP lookup: **O(1)** - Database index on phone
- Priority calculation: **O(1)** - Simple lookup

**VIP Tier System:**
| Tier | Priority Score | Requirements |
|------|---------------|--------------|
| Platinum | 1000 | 20+ bookings, ₱10,000+ spent |
| Gold | 2000 | 12+ bookings, ₱6,000+ spent |
| Silver | 3000 | 6+ bookings, ₱3,000+ spent |
| Bronze | 4000 | 3+ bookings, ₱1,500+ spent |
| Regular | 5000 + timestamp | New customers (FIFO) |

**Improvements:**
- ✅ **VIP recognition** with 4 tiers
- ✅ **Auto-promotion** based on history
- ✅ **Fair priority handling** via Min-Heap
- ✅ **O(1) VIP lookup** with phone index
- ✅ **Loyalty rewards** encourage repeat business
- ✅ **Competitive advantage** for restaurant

**Why the upgrade?**
- Reward loyal customers automatically
- Increase customer retention
- Higher-value customers get priority
- Still fair to new customers (FIFO within tier)
- Encourages more bookings for tier upgrades

---

## 4. Database Indexing

### V1.2 - Basic Indexes
```sql
CREATE TABLE reservations (
    id INT PRIMARY KEY,
    confirmation_code VARCHAR(20) UNIQUE,
    -- No phone index
    -- No priority_score column
);
```

**Problems:**
- ❌ VIP lookup by phone: **O(n)** - Full table scan
- ❌ No priority tracking
- ❌ Slow queries as data grows

---

### V2.0 - Optimized Indexes
```sql
CREATE TABLE reservations (
    id INT PRIMARY KEY,
    confirmation_code VARCHAR(20) UNIQUE,
    phone VARCHAR(20),
    priority_score INT DEFAULT 5000,
    INDEX idx_phone (phone),           -- O(1) VIP lookup
    INDEX idx_priority (priority_score) -- O(log n) priority sort
);

CREATE TABLE vip_customers (
    id INT PRIMARY KEY,
    phone VARCHAR(20) UNIQUE,
    vip_level ENUM('platinum','gold','silver','bronze'),
    INDEX idx_phone (phone)  -- O(1) VIP status check
);
```

**Improvements:**
- ✅ Phone lookup: **O(n) → O(1)** with B-tree index
- ✅ Priority sorting: **O(n log n) → O(log n)** with index
- ✅ VIP status check: **O(1)** with dedicated table + index

---

## Performance Comparison Summary

| Operation | V1.2 Complexity | V2.0 Complexity | Improvement |
|-----------|----------------|-----------------|-------------|
| **Cart: Add Item** | O(n) | O(1) | ⚡ **Instant** |
| **Cart: Remove Item** | O(n) | O(1) | ⚡ **Instant** |
| **Cart: Find Item** | O(n) | O(1) | ⚡ **Instant** |
| **Cart: Update Qty** | O(n) | O(1) | ⚡ **Instant** |
| **Reservation Lookup** | O(1) avg, degrades | O(1) maintained | ✅ **Scalable** |
| **Hash Table Capacity** | Fixed 97 | Dynamic (auto-resize) | ✅ **Unlimited** |
| **VIP Lookup** | O(n) table scan | O(1) indexed | ⚡ **Instant** |
| **Priority Handling** | None | O(log n) heap | ✅ **Fair & Fast** |
| **Reservation Sort** | O(n log n) | O(log n) indexed | ✅ **Faster** |

---

## Real-World Impact

### Scenario 1: Large Cart (50 items)
- **V1.2:** Each operation takes 50 iterations → Noticeable lag
- **V2.0:** Each operation takes 1 lookup → Instant response
- **User Experience:** ⭐⭐⭐ → ⭐⭐⭐⭐⭐

### Scenario 2: Peak Season (500 reservations)
- **V1.2:** Hash table overloaded, long collision chains
- **V2.0:** Auto-resized to 776 buckets, maintains O(1)
- **System Performance:** 🐌 Slow → ⚡ Fast

### Scenario 3: VIP Customer Booking
- **V1.2:** Treated same as new customer, no recognition
- **V2.0:** Instant VIP detection, priority score 1000-4000
- **Customer Satisfaction:** 😐 → 😊 Valued & Recognized

### Scenario 4: Admin Processing 100 Pending Reservations
- **V1.2:** Manual sorting by timestamp only
- **V2.0:** Auto-sorted by priority, VIPs at top
- **Admin Efficiency:** ⏱️ Time-consuming → ⚡ Streamlined

---

## Code Quality Improvements

### V1.2
- ❌ No algorithm documentation
- ❌ No complexity analysis
- ❌ Limited scalability planning
- ❌ Basic data structures

### V2.0
- ✅ Comprehensive headers on every file
- ✅ Complexity analysis documented
- ✅ Scalability built-in
- ✅ Advanced data structures
- ✅ Professional-grade implementation

---

## Migration Path

### For Existing V1.2 Users:

1. **Backup database**
   ```bash
   mysqldump -u root sakura_sushi > backup_v1.2.sql
   ```

2. **Run migration**
   ```bash
   mysql -u root sakura_sushi < app/migrations/add_vip_system.sql
   ```

3. **Test VIP system**
   - Create test VIP customer
   - Make reservation
   - Verify priority score

4. **Monitor performance**
   - Check cart operations in browser console
   - Verify hash table resizing logs
   - Test with large datasets

---

## Conclusion

**V2.0 represents a significant algorithmic upgrade:**

1. **Performance:** O(n) → O(1) for critical operations
2. **Scalability:** Fixed limits → Dynamic growth
3. **Features:** Basic FIFO → Advanced VIP priority system
4. **Code Quality:** Undocumented → Fully documented with complexity analysis
5. **User Experience:** Functional → Professional & Fast

**Recommended for:**
- ✅ Production deployments
- ✅ Growing businesses
- ✅ High-traffic periods
- ✅ Customer loyalty programs
- ✅ Professional restaurant operations

**V1.2 suitable for:**
- 📚 Educational purposes
- 🧪 Small-scale testing
- 📖 Learning basic algorithms

---

**Version:** 2.0  
**Date:** May 14, 2026  
**Status:** Production Ready ✅
