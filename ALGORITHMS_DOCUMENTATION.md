# Sakura Sushi Reservation System - Algorithms & Data Structures Documentation

## Table of Contents
1. [Hash Table Implementation](#1-hash-table-implementation)
2. [Queue (FIFO) Implementation](#2-queue-fifo-implementation)
3. [Linked List Implementation](#3-linked-list-implementation)
4. [Hash-Based Code Generation](#4-hash-based-code-generation)
5. [Linear Search & Filtering](#5-linear-search--filtering)
6. [Time Slot Availability Algorithm](#6-time-slot-availability-algorithm)

---

## 1. Hash Table Implementation

### Purpose
Fast O(1) average case lookup for reservation confirmation codes.

### Location
**File:** `app/confirmation.php`  
**Lines:** 17-52

### Implementation Details

```php
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
    
    public function insert($code, $reservation) { ... }
    public function search($code) { ... }
}
```

### Algorithm Analysis
- **Hash Function:** Polynomial rolling hash with multiplier 31
- **Collision Handling:** Chaining (using PHP arrays)
- **Table Size:** 97 (prime number for better distribution)
- **Time Complexity:**
  - Insert: O(1) average case
  - Search: O(1) average case
  - Worst case: O(n) if all keys collide
- **Space Complexity:** O(n) where n is number of reservations

### Usage
```php
$hashTable = new ReservationHashTable();
$hashTable->insert($reservation['confirmation_code'], $reservation);
$verifiedReservation = $hashTable->search($code);
```

**Lines:** 70-75

---

## 2. Queue (FIFO) Implementation

### Purpose
Manage waitlist for occupied tables using First-In-First-Out principle.

### Location
**File:** `app/api/create-reservation.php`  
**Lines:** 10-85

### Implementation Details

```php
class WaitlistQueue {
    private $front = null;
    private $rear = null;
    private $size = 0;
    
    public function enqueue($data) {
        $newNode = ['data' => $data, 'next' => null];
        if ($this->rear === null) {
            $this->front = $this->rear = $newNode;
        } else {
            $this->rear['next'] = $newNode;
            $this->rear = $newNode;
        }
        $this->size++;
        $this->save();
    }
    
    public function dequeue() {
        if ($this->front === null) return null;
        $data = $this->front['data'];
        $this->front = $this->front['next'];
        if ($this->front === null) $this->rear = null;
        $this->size--;
        $this->save();
        return $data;
    }
}
```

### Algorithm Analysis
- **Data Structure:** Linked List-based Queue
- **Operations:**
  - `enqueue()`: Add to rear - **O(1)**
  - `dequeue()`: Remove from front - **O(1)**
  - `peek()`: View front - **O(1)**
  - `isEmpty()`: Check if empty - **O(1)**
  - `getSize()`: Get queue size - **O(1)**
  - `toArray()`: Convert to array - **O(n)**
- **Space Complexity:** O(n) where n is waitlist size
- **Persistence:** Saves to JSON file after each operation

### Usage Example
**Lines:** 174-189
```php
$waitlist = new WaitlistQueue();
$waitlist->load();
$waitlist->enqueue($waitlistData);
// Customer added to position #N
```

---

## 3. Linked List Implementation

### Purpose
Shopping cart management for pre-order menu items.

### Location
**File:** `app/assets/js/main.js`  
**Lines:** 323-445

### Implementation Details

```javascript
class CartNode {
    constructor(data) {
        this.data = data;
        this.next = null;
    }
}

class CartLinkedList {
    constructor() {
        this.head = null;
        this.size = 0;
        this._total = 0;
    }
    
    // Insert at end - O(n)
    append(item) {
        const newNode = new CartNode(item);
        if (!this.head) {
            this.head = newNode;
        } else {
            let current = this.head;
            while (current.next) {
                current = current.next;
            }
            current.next = newNode;
        }
        this.size++;
        this._updateTotal();
        this._saveToStorage();
    }
    
    // Delete by item ID - O(n)
    remove(itemId) {
        if (!this.head) return false;
        if (this.head.data.id === itemId) {
            this.head = this.head.next;
            this.size--;
            this._updateTotal();
            this._saveToStorage();
            return true;
        }
        let current = this.head;
        while (current.next && current.next.data.id !== itemId) {
            current = current.next;
        }
        if (current.next) {
            current.next = current.next.next;
            this.size--;
            this._updateTotal();
            this._saveToStorage();
            return true;
        }
        return false;
    }
    
    // Find by ID - O(n)
    find(itemId) {
        let current = this.head;
        while (current) {
            if (current.data.id === itemId) {
                return current.data;
            }
            current = current.next;
        }
        return null;
    }
    
    // Update quantity - O(n)
    updateQuantity(itemId, quantity) {
        let current = this.head;
        while (current) {
            if (current.data.id === itemId) {
                current.data.quantity = quantity;
                current.data.subtotal = current.data.price * quantity;
                this._updateTotal();
                this._saveToStorage();
                return true;
            }
            current = current.next;
        }
        return false;
    }
    
    // Convert to array for display
    toArray() {
        const items = [];
        let current = this.head;
        while (current) {
            items.push(current.data);
            current = current.next;
        }
        return items;
    }
}
```

### Algorithm Analysis
- **Data Structure:** Singly Linked List
- **Operations:**
  - `append()`: Add item to end - **O(n)**
  - `remove()`: Delete by ID - **O(n)**
  - `find()`: Search by ID - **O(n)**
  - `updateQuantity()`: Update item - **O(n)**
  - `toArray()`: Convert to array - **O(n)**
  - `getTotal()`: Get cart total - **O(1)**
- **Space Complexity:** O(n) where n is number of cart items
- **Persistence:** LocalStorage integration

### Usage
**Lines:** 447-450
```javascript
const cart = new CartLinkedList();
cart.loadFromStorage();
cart.append(item);
cart.updateQuantity(itemId, newQuantity);
```

---

## 4. Hash-Based Code Generation

### Purpose
Generate unique confirmation codes for reservations.

### Location
**File:** `app/api/create-reservation.php`  
**Lines:** 87-110

### Implementation Details

```php
function generateConfirmationCode($pdo) {
    $maxAttempts = 10;
    $attempts = 0;
    
    while ($attempts < $maxAttempts) {
        // Generate 6-character alphanumeric code
        $code = 'SKR-' . strtoupper(substr(str_shuffle('0123456789ABCDEFGHJKLMNPQRSTUVWXYZ'), 0, 6));
        
        // Check uniqueness - Hash Table approach using DB index (O(1) with index)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE confirmation_code = ?");
        $stmt->execute([$code]);
        
        if ($stmt->fetchColumn() == 0) {
            return $code; // Unique code found
        }
        
        $attempts++;
    }
    
    // Fallback with timestamp
    return 'SKR-' . strtoupper(dechex(time()));
}
```

### Algorithm Analysis
- **Method:** Random string generation with uniqueness validation
- **Character Set:** 0-9, A-Z (excluding I, O for clarity)
- **Format:** `SKR-XXXXXX` (6 characters)
- **Uniqueness Check:** Database index lookup - **O(1)** with B-tree index
- **Time Complexity:** 
  - Best case: O(1) - first code is unique
  - Average case: O(k) where k is number of attempts (typically 1-2)
  - Worst case: O(10) - max 10 attempts
- **Collision Handling:** Retry with new random code
- **Fallback:** Timestamp-based hex code if all attempts fail

### Usage
**Line:** 260
```php
$confirmationCode = generateConfirmationCode($pdo);
```

---

## 5. Linear Search & Filtering

### Purpose
Search and filter reservations in admin panel.

### Location
**File:** `app/admin/admin.php`  
**Lines:** 43-47

### Implementation Details

```php
$search = trim($_GET['search'] ?? '');
$allRes = $pdo->query("SELECT r.*, t.table_number, t.capacity, t.price as table_price, 
    (SELECT COUNT(*) FROM pre_orders WHERE reservation_id=r.id) as po_count 
    FROM reservations r LEFT JOIN tables t ON r.table_id=t.id 
    ORDER BY FIELD(r.status,'pending','confirmed','cancelled'), r.created_at DESC")->fetchAll();

if ($search !== '') {
    $allRes = array_values(array_filter($allRes, fn($r) =>
        stripos($r['name'],$search)!==false || 
        stripos($r['phone'],$search)!==false || 
        stripos($r['confirmation_code'],$search)!==false
    ));
}
```

### JavaScript Client-Side Filtering
**File:** `app/admin/admin.php`  
**Lines:** 680-693

```javascript
let currentFilter = 'all';
function setFilter(status, btn) {
    currentFilter = status;
    document.querySelectorAll('.filter-chips .chip').forEach(c => c.classList.remove('active'));
    btn.classList.add('active');
    filterTable();
}

function filterTable() {
    const q = (document.getElementById('searchInput')?.value || '').toLowerCase();
    document.querySelectorAll('#resBody tr[data-status]').forEach(row => {
        const matchStatus = currentFilter === 'all' || row.dataset.status === currentFilter;
        const matchSearch = !q || row.dataset.search.includes(q);
        row.style.display = matchStatus && matchSearch ? '' : 'none';
    });
}
```

### Algorithm Analysis
- **Search Method:** Linear search with substring matching
- **Search Fields:** Name, phone, confirmation code
- **Case Sensitivity:** Case-insensitive (`stripos`)
- **Time Complexity:** O(n × m) where:
  - n = number of reservations
  - m = average length of search fields
- **Space Complexity:** O(n) for filtered results
- **Client-Side Filtering:** Real-time filtering without page reload

---

## 6. Time Slot Availability Algorithm

### Purpose
Calculate table availability for specific date and time slots.

### Location
**File:** `app/tables.php`  
**Lines:** 9-56

### Implementation Details

```php
// Time slots (2.5 hours each, operating 7 PM - 11 PM)
$timeSlots = [
    ['start' => '19:00:00', 'end' => '21:30:00', 'label' => '7:00 PM - 9:30 PM'],
    ['start' => '20:30:00', 'end' => '23:00:00', 'label' => '8:30 PM - 11:00 PM']
];

// Get all reservations for selected date
$stmt = $pdo->prepare("
    SELECT table_id, reservation_time, status 
    FROM reservations 
    WHERE reservation_date = ? AND status != 'cancelled'
");
$stmt->execute([$selectedDate]);
$reservations = $stmt->fetchAll();

// Build availability map
$availability = [];
foreach ($tables as $table) {
    $availability[$table['id']] = [];
    foreach ($timeSlots as $slot) {
        $availability[$table['id']][$slot['start']] = 'available';
    }
}

// Mark booked/pending slots
foreach ($reservations as $res) {
    $resTime = $res['reservation_time'];
    foreach ($timeSlots as $slot) {
        // Check if reservation time falls within this slot
        if ($resTime >= $slot['start'] && $resTime < $slot['end']) {
            $status = $res['status'] === 'confirmed' ? 'booked' : 'pending';
            $availability[$res['table_id']][$slot['start']] = $status;
        }
    }
}
```

### Algorithm Analysis
- **Data Structure:** 2D associative array (hash map)
- **Time Complexity:** 
  - Initialize availability: O(t × s) where t = tables, s = slots
  - Mark reservations: O(r × s) where r = reservations
  - Total: O((t + r) × s)
- **Space Complexity:** O(t × s) for availability map
- **Optimization:** Uses database index on `reservation_date` for fast filtering

### Availability States
- `available` - Table is free for booking
- `pending` - Reservation awaiting admin confirmation
- `booked` - Confirmed reservation

---

## Summary of Algorithms Used

| Algorithm/Data Structure | File | Lines | Time Complexity | Space Complexity |
|-------------------------|------|-------|-----------------|------------------|
| **Hash Table** | `app/confirmation.php` | 17-52 | O(1) avg | O(n) |
| **Queue (FIFO)** | `app/api/create-reservation.php` | 10-85 | O(1) | O(n) |
| **Linked List** | `app/assets/js/main.js` | 323-445 | O(n) | O(n) |
| **Hash Code Generation** | `app/api/create-reservation.php` | 87-110 | O(1) avg | O(1) |
| **Linear Search** | `app/admin/admin.php` | 43-47 | O(n×m) | O(n) |
| **Time Slot Algorithm** | `app/tables.php` | 9-56 | O((t+r)×s) | O(t×s) |

---

## Performance Considerations

### Database Indexing
- **Primary Keys:** Auto-indexed on `id` columns
- **Foreign Keys:** Indexed on `table_id`, `reservation_id`, `menu_item_id`
- **Search Optimization:** Index on `confirmation_code` for O(1) lookup
- **Date Filtering:** Index on `reservation_date` for fast queries

### Caching Strategies
- **LocalStorage:** Cart data persisted client-side
- **Session Storage:** Admin authentication state
- **File System:** Waitlist queue persisted to JSON

### Scalability Notes
- Hash table size (97) can be increased for larger datasets
- Queue implementation supports unlimited waitlist size
- Linked list suitable for typical cart sizes (< 50 items)
- Time slot algorithm scales linearly with number of tables

---

## Algorithm Selection Rationale

### Why Hash Table for Reservations?
- **Fast Lookup:** O(1) average case for confirmation code verification
- **Unique Keys:** Confirmation codes are unique identifiers
- **Collision Rare:** Prime table size and good hash function minimize collisions

### Why Queue for Waitlist?
- **Fair Ordering:** FIFO ensures first-come-first-served
- **Simple Operations:** Only need enqueue/dequeue
- **Real-world Model:** Matches physical waitlist behavior

### Why Linked List for Cart?
- **Dynamic Size:** Cart grows/shrinks frequently
- **Frequent Modifications:** Easy insertion/deletion
- **Memory Efficient:** No pre-allocated array size needed
- **Sequential Access:** Cart items typically accessed in order

---

## Future Optimization Opportunities

1. **Binary Search Tree** for sorted table availability
2. **Priority Queue** for VIP reservations
3. **Trie** for autocomplete in search functionality
4. **Bloom Filter** for quick duplicate detection
5. **LRU Cache** for frequently accessed reservations

---

**Document Version:** 1.0  
**Last Updated:** 2026-05-14  
**System Version:** v1.2
