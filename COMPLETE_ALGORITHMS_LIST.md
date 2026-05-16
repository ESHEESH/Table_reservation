# Complete Algorithms & Data Structures List
## Sakura Sushi Reservation System

---

## 📋 Table of Contents
1. [Overview](#overview)
2. [Main Branch Algorithms (V2 - Improved)](#main-branch-algorithms-v2---improved)
3. [Base Branch Algorithms (V1.2 - Original)](#base-branch-algorithms-v12---original)
4. [Algorithm Comparison](#algorithm-comparison)
5. [Performance Analysis](#performance-analysis)
6. [Why These Algorithms?](#why-these-algorithms)

---

## Overview

The Sakura Sushi Reservation System implements **10 different algorithms and data structures** across the main branch (V2), with significant improvements over the base branch (V1.2).

### Branch Purpose:
- **Base Branch**: Original implementation with basic algorithms (for comparison)
- **Main/V2 Branch**: Optimized implementation with advanced algorithms

---

## Main Branch Algorithms (V2 - Improved)

### 1. Dynamic Hash Table with Resizing
**File:** `app/confirmation.php`  
**Lines:** 17-120  
**Complexity:** O(1) average, O(n) resize  

**Description:**
- Hash table for reservation confirmation code lookup
- **Automatic resizing** when load factor exceeds 0.75
- **Open addressing** (linear probing) as fallback
- Prevents performance degradation as data grows

**Key Features:**
```php
- Initial size: 97 (prime number)
- Load factor threshold: 0.75
- Resizing: Doubles size and rehashes all entries
- Collision handling: Chaining + open addressing
```

**Improvements over Base:**
- ✅ Auto-resizes to maintain O(1) performance
- ✅ Handles unlimited reservations efficiently
- ✅ No performance degradation

---

### 2. HashMap + Doubly Linked List (Cart)
**File:** `app/assets/js/main.js`  
**Lines:** 200-560  
**Complexity:** O(1) for all operations  

**Description:**
- Hybrid data structure for shopping cart
- **HashMap** for O(1) item lookup by ID
- **Doubly Linked List** maintains insertion order
- Allows O(1) add, remove, find, and update

**Key Features:**
```javascript
class CartHashMapList {
    map: Map<itemId, node>     // O(1) lookup
    head: Node                  // List start
    tail: Node                  // List end
    
    Operations:
    - add(item): O(1)          // HashMap + tail append
    - remove(id): O(1)         // HashMap find + DLL delete
    - find(id): O(1)           // Direct HashMap lookup
    - update(id, qty): O(1)    // HashMap access
}
```

**Improvements over Base:**
- ✅ O(n) → O(1) for all cart operations
- ✅ Instant duplicate detection
- ✅ Fast quantity updates
- ✅ Maintains display order

---

### 3. Priority Queue (Min-Heap)
**File:** `app/classes/PriorityQueue.php`  
**Lines:** 1-276  
**Complexity:** O(log n) insert/extract  

**Description:**
- Min-Heap implementation for VIP priority system
- Lower priority score = Higher priority
- Ensures fair ordering of reservations

**Priority Scores:**
```
Platinum VIP:  1000
Gold VIP:      2000
Silver VIP:    3000
Bronze VIP:    4000
Regular:       5000 + timestamp
```

**Key Operations:**
```php
insert($priority, $data)    // O(log n) - heapify up
extractMin()                // O(log n) - heapify down
peek()                      // O(1) - view top
isEmpty()                   // O(1)
```

**Improvements over Base:**
- ✅ NEW: VIP priority system
- ✅ Fair queue management
- ✅ O(log n) vs O(n log n) sorting

---

### 4. VIP Service with Hash Table Lookup
**File:** `app/classes/VIPService.php`  
**Lines:** 1-183  
**Complexity:** O(1) lookup  

**Description:**
- VIP customer identification by phone number
- Database B-tree index for O(1) lookup
- Auto-promotion based on booking history

**Key Features:**
```php
isVIP($phone)                    // O(1) - indexed lookup
calculatePriorityScore()         // O(1) - tier mapping
checkAutoPromotion($phone)       // O(1) - aggregation query
updateVIPStats()                 // O(1) - single UPDATE
```

**Auto-Promotion Rules:**
```
Platinum: 20+ bookings, ₱10,000+ spent
Gold:     12+ bookings, ₱6,000+ spent
Silver:   6+ bookings,  ₱3,000+ spent
Bronze:   3+ bookings,  ₱1,500+ spent
```

**Improvements over Base:**
- ✅ NEW: VIP system
- ✅ O(1) customer lookup
- ✅ Automatic tier upgrades

---

### 5. Hash-Based Confirmation Code Generation
**File:** `app/api/create-reservation.php`  
**Lines:** 87-110  
**Complexity:** O(1) average  

**Description:**
- Generates unique 6-character alphanumeric codes
- Format: `SKR-XXXXXX`
- Database index ensures O(1) uniqueness check

**Algorithm:**
```php
1. Generate random 6-char code from [0-9A-Z]
2. Check uniqueness via indexed DB query (O(1))
3. If collision, retry (max 10 attempts)
4. Fallback: timestamp-based hex code
```

**Character Set:** `0123456789ABCDEFGHJKLMNPQRSTUVWXYZ` (33 chars, excluding I/O)

**Collision Probability:**
- Total combinations: 33^6 = 1,291,467,969
- Very low collision rate

---

### 6. Linear Search with Filtering
**File:** `app/admin/admin.php`  
**Lines:** 143-147, 1103-1115  
**Complexity:** O(n × m)  

**Description:**
- Search reservations by name, phone, or code
- Case-insensitive substring matching
- Real-time client-side filtering

**Implementation:**
```php
// Server-side (PHP)
$allRes = array_filter($allRes, fn($r) =>
    stripos($r['name'], $search) !== false || 
    stripos($r['phone'], $search) !== false || 
    stripos($r['confirmation_code'], $search) !== false
);

// Client-side (JavaScript)
function filterTable() {
    rows.forEach(row => {
        const matchStatus = currentFilter === 'all' || row.dataset.status === currentFilter;
        const matchSearch = !q || row.dataset.search.includes(q);
        row.style.display = matchStatus && matchSearch ? '' : 'none';
    });
}
```

**Where:**
- n = number of reservations
- m = average field length

---

### 7. Time Slot Availability Matrix
**File:** `app/tables.php`  
**Lines:** 23-56  
**Complexity:** O((t + r) × s)  

**Description:**
- 2D associative array for table availability
- Checks availability for each table-time combination
- Color-coded status display

**Data Structure:**
```php
$availability = [
    table_id => [
        '14:00:00' => 'available',
        '17:00:00' => 'booked',
        '20:00:00' => 'pending'
    ]
]
```

**Time Slots (3 hours each):**
```
Slot 1: 2:00 PM - 5:00 PM
Slot 2: 5:00 PM - 8:00 PM
Slot 3: 8:00 PM - 11:00 PM
```

**Algorithm:**
```
1. Initialize: O(t × s) - all tables × all slots = available
2. Query reservations: O(r) - filtered by date
3. Mark booked/pending: O(r × s) - check each reservation against slots
4. Display: O(t × s) - render grid
```

**Where:**
- t = number of tables (12)
- r = number of reservations
- s = number of time slots (3)

---

### 8. Session-Based Table Hold System
**File:** `app/api/hold-table.php`, `app/api/release-hold.php`  
**Lines:** 1-80  
**Complexity:** O(1)  

**Description:**
- Temporary 5-minute lock on table during form fill
- Prevents double booking and bot abuse
- Session-based with automatic expiration

**Algorithm:**
```php
1. User selects table → Create hold
2. Store in session: [table_id, date, time, expires_at]
3. Check on page load: if expired → redirect
4. Countdown timer: updates every second
5. On submit: release hold
6. On timeout: auto-release + redirect
```

**Hold Structure:**
```php
$_SESSION['current_hold'] = [
    'table_id' => 5,
    'date' => '2026-05-20',
    'time' => '17:00:00',
    'expires_at' => timestamp + 300  // 5 minutes
];
```

**Features:**
- ✅ 5-minute countdown timer
- ✅ Visual warning at 1 minute
- ✅ Auto-release on navigation away
- ✅ Prevents concurrent bookings

---

### 9. Database Sorting (ORDER BY)
**Files:** Multiple  
**Complexity:** O(n log n)  

**Description:**
- MySQL built-in sorting (typically QuickSort/MergeSort)
- Uses database indexes for optimization

**Usage Examples:**

**Tables:**
```sql
SELECT * FROM tables ORDER BY table_number
```

**Menu Items:**
```sql
SELECT * FROM menu_items ORDER BY category, name
```

**Reservations (with priority):**
```sql
SELECT * FROM reservations 
ORDER BY priority_score ASC, created_at DESC
```

**Reservations (by status):**
```sql
SELECT * FROM reservations 
ORDER BY FIELD(status, 'pending', 'confirmed', 'cancelled'), created_at DESC
```

**Optimization:**
- Uses B-tree indexes on sorted columns
- Complexity: O(n log n) or O(n) with index

---

### 10. Singleton Pattern (Database Connection)
**File:** `app/config.php`  
**Lines:** 15-30  
**Complexity:** O(1)  

**Description:**
- Single database connection reused across requests
- Prevents connection overhead
- Static variable caching

**Implementation:**
```php
function getDBConnection() {
    static $pdo = null;  // Singleton instance
    
    if ($pdo === null) {
        $pdo = new PDO($dsn, $user, $pass, $options);
    }
    
    return $pdo;  // Reuse existing connection
}
```

**Benefits:**
- ✅ O(1) connection retrieval
- ✅ Reduces database overhead
- ✅ Memory efficient

---

## Base Branch Algorithms (V1.2 - Original)

### Algorithms in Base (No VIP/Priority features):

| # | Algorithm | File | Complexity | Notes |
|---|-----------|------|------------|-------|
| 1 | **Hash Table (Fixed)** | `app/confirmation.php` | O(1) avg | No resizing, degrades |
| 2 | **Singly Linked List** | `app/assets/js/main.js` | O(n) | Cart operations |
| 3 | **Hash Code Generation** | `app/api/create-reservation.php` | O(1) avg | Same as V2 |
| 4 | **Linear Search** | `app/admin/admin.php` | O(n×m) | Same as V2 |
| 5 | **Time Slot Matrix** | `app/tables.php` | O((t+r)×s) | Same as V2 |
| 6 | **Table Hold System** | `app/api/hold-table.php` | O(1) | Same as V2 |
| 7 | **Database Sorting** | Multiple | O(n log n) | Same as V2 |

### Missing in Base:
- ❌ Priority Queue (no VIP system)
- ❌ HashMap + Doubly Linked List (cart is O(n))
- ❌ Dynamic Hash Table Resizing
- ❌ VIP Service with O(1) lookup

---

## Algorithm Comparison

### Cart Operations:

| Operation | Base (Singly Linked List) | V2 (HashMap + DLL) | Improvement |
|-----------|---------------------------|---------------------|-------------|
| Add Item | O(n) - traverse to end | O(1) - tail pointer | **n times faster** |
| Remove Item | O(n) - search + delete | O(1) - map lookup | **n times faster** |
| Find Item | O(n) - linear search | O(1) - map lookup | **n times faster** |
| Update Qty | O(n) - search + update | O(1) - map access | **n times faster** |
| Check Duplicate | O(n) - search | O(1) - map.has() | **n times faster** |

**Example:** With 20 items in cart:
- Base: 20 operations to find item
- V2: 1 operation to find item
- **20x faster!**

---

### Reservation Priority:

| Feature | Base | V2 | Improvement |
|---------|------|-----|-------------|
| VIP Detection | ❌ None | ✅ O(1) indexed lookup | **NEW** |
| Priority Scoring | ❌ None | ✅ O(1) tier mapping | **NEW** |
| Queue Management | ❌ FIFO only | ✅ O(log n) min-heap | **Fair & Fast** |
| Auto-Promotion | ❌ None | ✅ O(1) aggregation | **NEW** |

---

### Hash Table Performance:

| Scenario | Base (Fixed Size) | V2 (Dynamic) | Improvement |
|----------|-------------------|--------------|-------------|
| 50 reservations | O(1) | O(1) | Same |
| 100 reservations | O(1.5) - collisions | O(1) | **Better** |
| 500 reservations | O(5) - many collisions | O(1) - resized | **5x faster** |
| 1000 reservations | O(10) - degraded | O(1) - resized | **10x faster** |

---

## Performance Analysis

### Time Complexity Summary:

| Algorithm | Base | V2 | Winner |
|-----------|------|-----|--------|
| Cart Add/Remove/Find | O(n) | O(1) | ✅ **V2** |
| VIP Lookup | O(n) scan | O(1) index | ✅ **V2** |
| Hash Table (large data) | O(k) degrades | O(1) maintained | ✅ **V2** |
| Priority Queue | ❌ None | O(log n) | ✅ **V2** |
| Confirmation Lookup | O(1) | O(1) | 🟰 **Same** |
| Search/Filter | O(n×m) | O(n×m) | 🟰 **Same** |
| Time Slot Check | O((t+r)×s) | O((t+r)×s) | 🟰 **Same** |
| Table Hold | O(1) | O(1) | 🟰 **Same** |

### Space Complexity:

| Data Structure | Base | V2 | Notes |
|----------------|------|-----|-------|
| Cart | O(n) | O(2n) | V2 uses map + list |
| Hash Table | O(n) | O(n) | Same, but V2 resizes |
| Priority Queue | ❌ | O(n) | New in V2 |
| VIP Data | ❌ | O(v) | New in V2 |

---

## Why These Algorithms?

### 1. Why HashMap + Doubly Linked List for Cart?
**Problem:** Singly linked list requires O(n) traversal for every operation.

**Solution:** 
- HashMap provides O(1) lookup by item ID
- Doubly linked list maintains insertion order
- Allows O(1) deletion (no need to find previous node)

**Real-world benefit:** Instant cart updates, no lag with large carts.

---

### 2. Why Priority Queue (Min-Heap) for VIP?
**Problem:** Need to prioritize VIP customers fairly.

**Solution:**
- Min-heap ensures lowest priority score is always at top
- O(log n) insert/extract is efficient
- Maintains fairness within each tier

**Real-world benefit:** VIPs get priority, but system remains fair.

---

### 3. Why Dynamic Hash Table Resizing?
**Problem:** Fixed-size hash table degrades as data grows.

**Solution:**
- Monitor load factor (items / size)
- When > 0.75, double size and rehash
- Maintains O(1) performance indefinitely

**Real-world benefit:** System scales to thousands of reservations without slowdown.

---

### 4. Why Session-Based Hold System?
**Problem:** Multiple users can try to book same table simultaneously.

**Solution:**
- Lock table for 5 minutes during form fill
- Session-based prevents conflicts
- Auto-expires to prevent abandoned holds

**Real-world benefit:** Prevents double booking, stops bot abuse.

---

### 5. Why NOT Bubble Sort?
**Problem:** Bubble sort is O(n²) - too slow!

**Solution:**
- Use database ORDER BY (O(n log n))
- Database has optimized sorting algorithms
- Can use indexes for even faster sorting

**Real-world benefit:** Fast sorting even with thousands of records.

---

## Algorithms NOT Used (and Why)

| Algorithm | Why NOT Used |
|-----------|--------------|
| **Bubble Sort** | O(n²) - too slow, database sorting is faster |
| **Insertion Sort** | O(n²) - database handles sorting better |
| **Selection Sort** | O(n²) - inefficient for large datasets |
| **Binary Search** | Database indexes provide O(1) lookup |
| **Merge Sort** | Database already uses optimized sorting |
| **Quick Sort** | Database already uses optimized sorting |
| **AVL Tree** | Database B-tree indexes are sufficient |
| **Red-Black Tree** | Database B-tree indexes are sufficient |

---

## Summary

### Total Algorithms Implemented:

**Main/V2 Branch:** **10 algorithms**
1. Dynamic Hash Table with Resizing
2. HashMap + Doubly Linked List
3. Priority Queue (Min-Heap)
4. VIP Service with Hash Lookup
5. Hash-Based Code Generation
6. Linear Search & Filtering
7. Time Slot Availability Matrix
8. Session-Based Hold System
9. Database Sorting (ORDER BY)
10. Singleton Pattern

**Base Branch:** **7 algorithms** (missing VIP features)

### Key Improvements in V2:
- ✅ **4 new/upgraded algorithms**
- ✅ **O(n) → O(1)** cart operations
- ✅ **VIP priority system** with O(1) lookup
- ✅ **Scalable hash table** with auto-resizing
- ✅ **Fair queue management** with min-heap

### Performance Gains:
- **Cart operations:** Up to **20x faster** with large carts
- **VIP lookup:** **Instant** vs full table scan
- **Hash table:** **10x faster** with 1000+ reservations
- **Priority handling:** **Fair & efficient** O(log n)

---

**Document Version:** 1.0  
**Last Updated:** May 15, 2026  
**System Version:** V2.0 (Main Branch)
