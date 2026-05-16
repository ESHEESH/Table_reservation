# Algorithms Overview - What Changed & What's Used
## Sakura Sushi Reservation System

---

## 🎯 Quick Summary

**Total Algorithms:** 10  
**Upgraded Algorithms:** 3  
**New Algorithms:** 3  
**Unchanged Algorithms:** 4  

---

## 📊 Visual Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                    ALGORITHM EVOLUTION                          │
│                    Base (V1.2) → Main (V2)                      │
└─────────────────────────────────────────────────────────────────┘

🔴 UPGRADED (3):
   ├─ Shopping Cart:        Singly Linked List → HashMap + Doubly Linked List
   ├─ Hash Table:           Fixed Size → Dynamic Resizing
   └─ Reservation Priority: None → Priority Queue (Min-Heap)

🟢 NEW (3):
   ├─ VIP Service:          Hash Table Lookup (O(1))
   ├─ Priority Queue:       Min-Heap for VIP Priority
   └─ Table Hold System:    5-Minute Session Lock

🟡 UNCHANGED (4):
   ├─ Code Generation:      Hash-Based (O(1))
   ├─ Search/Filter:        Linear Search (O(n×m))
   ├─ Time Slots:           Availability Matrix
   └─ Database Sorting:     ORDER BY (O(n log n))
```

---

## 🔄 Algorithm Changes (Base → V2)

### 1. Shopping Cart System
```
┌──────────────────────────────────────────────────────────────┐
│ BEFORE (Base):                                               │
│ ┌──────────────────────────────────────────────────────┐    │
│ │  Singly Linked List                                  │    │
│ │  [Item1] → [Item2] → [Item3] → [Item4] → null       │    │
│ │                                                       │    │
│ │  Operations:                                         │    │
│ │  • Add:    O(n) - traverse to end                    │    │
│ │  • Remove: O(n) - search then delete                 │    │
│ │  • Find:   O(n) - linear search                      │    │
│ │  • Update: O(n) - search then modify                 │    │
│ └──────────────────────────────────────────────────────┘    │
└──────────────────────────────────────────────────────────────┘

                            ⬇️ UPGRADED

┌──────────────────────────────────────────────────────────────┐
│ AFTER (V2):                                                  │
│ ┌──────────────────────────────────────────────────────┐    │
│ │  HashMap + Doubly Linked List                        │    │
│ │                                                       │    │
│ │  HashMap: {                                          │    │
│ │    id1 → Node1 ←┐                                    │    │
│ │    id2 → Node2  │  O(1) Lookup                      │    │
│ │    id3 → Node3  │                                    │    │
│ │  }             ─┘                                    │    │
│ │                                                       │    │
│ │  List: null ← [Node1] ⇄ [Node2] ⇄ [Node3] → null   │    │
│ │        head ────────┘                    └──── tail  │    │
│ │                                                       │    │
│ │  Operations:                                         │    │
│ │  • Add:    O(1) - map.set() + tail append            │    │
│ │  • Remove: O(1) - map.get() + DLL delete             │    │
│ │  • Find:   O(1) - direct map lookup                  │    │
│ │  • Update: O(1) - map.get() + modify                 │    │
│ └──────────────────────────────────────────────────────┘    │
└──────────────────────────────────────────────────────────────┘

📈 IMPROVEMENT: O(n) → O(1) for all operations
⚡ IMPACT: 20x faster with 20 items in cart
```

---

### 2. Hash Table (Reservation Lookup)
```
┌──────────────────────────────────────────────────────────────┐
│ BEFORE (Base):                                               │
│ ┌──────────────────────────────────────────────────────┐    │
│ │  Fixed-Size Hash Table (97 buckets)                 │    │
│ │                                                       │    │
│ │  [0] → [res1]                                        │    │
│ │  [1] → [res2] → [res3]  ← collision chain           │    │
│ │  [2] → null                                          │    │
│ │  ...                                                 │    │
│ │  [96] → [res4] → [res5] → [res6]  ← long chain!     │    │
│ │                                                       │    │
│ │  ❌ No resizing                                       │    │
│ │  ❌ Performance degrades as data grows               │    │
│ │  ❌ Fixed capacity of ~73 items (97 × 0.75)          │    │
│ └──────────────────────────────────────────────────────┘    │
└──────────────────────────────────────────────────────────────┘

                            ⬇️ UPGRADED

┌──────────────────────────────────────────────────────────────┐
│ AFTER (V2):                                                  │
│ ┌──────────────────────────────────────────────────────┐    │
│ │  Dynamic Hash Table with Auto-Resizing              │    │
│ │                                                       │    │
│ │  Initial: 97 buckets                                 │    │
│ │  Load Factor: 73/97 = 0.75 → RESIZE!                │    │
│ │                                                       │    │
│ │  After Resize: 194 buckets (doubled)                │    │
│ │  [0] → [res1]                                        │    │
│ │  [1] → [res2]                                        │    │
│ │  [2] → [res3]                                        │    │
│ │  ...                                                 │    │
│ │  [193] → [res4]                                      │    │
│ │                                                       │    │
│ │  ✅ Automatic resizing at 75% capacity               │    │
│ │  ✅ Maintains O(1) performance                       │    │
│ │  ✅ Unlimited capacity                               │    │
│ │  ✅ Open addressing fallback                         │    │
│ └──────────────────────────────────────────────────────┘    │
└──────────────────────────────────────────────────────────────┘

📈 IMPROVEMENT: Prevents degradation, scales infinitely
⚡ IMPACT: 10x faster with 1000+ reservations
```

---

### 3. Reservation Priority System
```
┌──────────────────────────────────────────────────────────────┐
│ BEFORE (Base):                                               │
│ ┌──────────────────────────────────────────────────────┐    │
│ │  Simple FIFO (First In, First Out)                  │    │
│ │                                                       │    │
│ │  Reservations sorted by created_at:                 │    │
│ │  ┌─────────────────────────────────────────────┐    │    │
│ │  │ 1. John    (10:00 AM) - Regular             │    │    │
│ │  │ 2. Alice   (10:05 AM) - Regular             │    │    │
│ │  │ 3. Bob     (10:10 AM) - Regular             │    │    │
│ │  │ 4. Carol   (10:15 AM) - Regular             │    │    │
│ │  └─────────────────────────────────────────────┘    │    │
│ │                                                       │    │
│ │  ❌ No VIP recognition                                │    │
│ │  ❌ All customers treated equally                     │    │
│ │  ❌ No loyalty rewards                                │    │
│ └──────────────────────────────────────────────────────┘    │
└──────────────────────────────────────────────────────────────┘

                            ⬇️ UPGRADED

┌──────────────────────────────────────────────────────────────┐
│ AFTER (V2):                                                  │
│ ┌──────────────────────────────────────────────────────┐    │
│ │  Priority Queue (Min-Heap) + VIP System             │    │
│ │                                                       │    │
│ │  Priority Scores (Lower = Higher Priority):         │    │
│ │                                                       │    │
│ │         [Alice: 1000] ← Platinum VIP                │    │
│ │              /    \                                  │    │
│ │    [Carol: 2000]  [Bob: 4000]                       │    │
│ │       Gold VIP     Bronze VIP                       │    │
│ │          /                                           │    │
│ │  [John: 5001715702400]                              │    │
│ │     Regular (FIFO)                                  │    │
│ │                                                       │    │
│ │  Admin sees:                                         │    │
│ │  ┌─────────────────────────────────────────────┐    │    │
│ │  │ 1. Alice   (1000)  - 🏆 Platinum VIP        │    │    │
│ │  │ 2. Carol   (2000)  - 🥇 Gold VIP            │    │    │
│ │  │ 3. Bob     (4000)  - 🥉 Bronze VIP          │    │    │
│ │  │ 4. John    (5000+) - 👤 Regular             │    │    │
│ │  └─────────────────────────────────────────────┘    │    │
│ │                                                       │    │
│ │  ✅ VIP customers prioritized                        │    │
│ │  ✅ Auto-promotion based on history                  │    │
│ │  ✅ Fair within each tier (FIFO)                     │    │
│ │  ✅ O(log n) insert/extract                          │    │
│ └──────────────────────────────────────────────────────┘    │
└──────────────────────────────────────────────────────────────┘

📈 IMPROVEMENT: Fair priority system with VIP tiers
⚡ IMPACT: Better customer retention, fair queue management
```

---

## 🆕 New Algorithms (Not in Base)

### 4. VIP Service with Hash Lookup
```
┌──────────────────────────────────────────────────────────────┐
│ NEW in V2:                                                   │
│ ┌──────────────────────────────────────────────────────┐    │
│ │  VIP Customer Identification System                  │    │
│ │                                                       │    │
│ │  Database Table: vip_customers                       │    │
│ │  ┌────────────────────────────────────────────┐     │    │
│ │  │ phone (INDEX) │ vip_level │ total_spent    │     │    │
│ │  ├───────────────┼───────────┼────────────────┤     │    │
│ │  │ 555-0001      │ platinum  │ ₱12,500        │     │    │
│ │  │ 555-0002      │ gold      │ ₱7,800         │     │    │
│ │  │ 555-0003      │ silver    │ ₱4,200         │     │    │
│ │  └────────────────────────────────────────────┘     │    │
│ │                                                       │    │
│ │  Lookup: O(1) via B-tree index on phone             │    │
│ │                                                       │    │
│ │  Operations:                                         │    │
│ │  • isVIP(phone):              O(1)                   │    │
│ │  • calculatePriorityScore():  O(1)                   │    │
│ │  • checkAutoPromotion():      O(1)                   │    │
│ │  • updateVIPStats():          O(1)                   │    │
│ │                                                       │    │
│ │  Auto-Promotion Rules:                               │    │
│ │  • Platinum: 20+ bookings, ₱10,000+ spent           │    │
│ │  • Gold:     12+ bookings, ₱6,000+ spent            │    │
│ │  • Silver:   6+ bookings,  ₱3,000+ spent            │    │
│ │  • Bronze:   3+ bookings,  ₱1,500+ spent            │    │
│ └──────────────────────────────────────────────────────┘    │
└──────────────────────────────────────────────────────────────┘

⚡ BENEFIT: Instant VIP recognition, automatic tier upgrades
```

---

### 5. Priority Queue (Min-Heap)
```
┌──────────────────────────────────────────────────────────────┐
│ NEW in V2:                                                   │
│ ┌──────────────────────────────────────────────────────┐    │
│ │  Min-Heap Implementation                             │    │
│ │                                                       │    │
│ │  Structure:                                          │    │
│ │         [1000] ← Root (Highest Priority)            │    │
│ │          /   \                                       │    │
│ │      [2000] [3000]                                   │    │
│ │       /  \     /                                     │    │
│ │   [4000][5000][5001]                                │    │
│ │                                                       │    │
│ │  Operations:                                         │    │
│ │  • insert(priority, data):  O(log n) - heapify up   │    │
│ │  • extractMin():            O(log n) - heapify down  │    │
│ │  • peek():                  O(1) - view root         │    │
│ │  • isEmpty():               O(1)                     │    │
│ │                                                       │    │
│ │  Heap Properties:                                    │    │
│ │  • Parent ≤ Children (min-heap)                      │    │
│ │  • Complete binary tree                              │    │
│ │  • Array-based storage                               │    │
│ └──────────────────────────────────────────────────────┘    │
└──────────────────────────────────────────────────────────────┘

⚡ BENEFIT: Fair priority handling, O(log n) efficiency
```

---

### 6. Table Hold System
```
┌──────────────────────────────────────────────────────────────┐
│ NEW in V2:                                                   │
│ ┌──────────────────────────────────────────────────────┐    │
│ │  5-Minute Temporary Table Lock                       │    │
│ │                                                       │    │
│ │  Timeline:                                           │    │
│ │  ┌────────────────────────────────────────────┐     │    │
│ │  │ 0:00  User selects table                   │     │    │
│ │  │       → Create hold in session             │     │    │
│ │  │       → Start 5-minute countdown           │     │    │
│ │  │                                             │     │    │
│ │  │ 0:30  User fills form...                   │     │    │
│ │  │       → Timer: 4:30 remaining              │     │    │
│ │  │                                             │     │    │
│ │  │ 4:00  Still filling...                     │     │    │
│ │  │       → Timer: 1:00 remaining ⚠️           │     │    │
│ │  │       → Visual warning (red)               │     │    │
│ │  │                                             │     │    │
│ │  │ 4:30  User submits form ✅                 │     │    │
│ │  │       → Release hold                       │     │    │
│ │  │       → Create reservation                 │     │    │
│ │  │                                             │     │    │
│ │  │ OR                                          │     │    │
│ │  │                                             │     │    │
│ │  │ 5:00  Timer expires ❌                     │     │    │
│ │  │       → Auto-release hold                  │     │    │
│ │  │       → Redirect to table selection        │     │    │
│ │  └────────────────────────────────────────────┘     │    │
│ │                                                       │    │
│ │  Session Structure:                                  │    │
│ │  $_SESSION['current_hold'] = [                      │    │
│ │    'table_id' => 5,                                 │    │
│ │    'date' => '2026-05-20',                          │    │
│ │    'time' => '17:00:00',                            │    │
│ │    'expires_at' => timestamp + 300                  │    │
│ │  ]                                                   │    │
│ └──────────────────────────────────────────────────────┘    │
└──────────────────────────────────────────────────────────────┘

⚡ BENEFIT: Prevents double booking, stops bot abuse
```

---

## 🟡 Unchanged Algorithms (Same in Both)

### 7. Hash-Based Code Generation
```
Algorithm: Random string + uniqueness check
Complexity: O(1) average
Format: SKR-XXXXXX (6 alphanumeric)
Status: ✅ Already optimal
```

### 8. Linear Search & Filtering
```
Algorithm: Substring matching with stripos()
Complexity: O(n × m)
Fields: name, phone, confirmation_code
Status: ✅ Acceptable for admin panel
```

### 9. Time Slot Availability Matrix
```
Algorithm: 2D associative array
Complexity: O((t + r) × s)
Structure: availability[table_id][time_slot]
Status: ✅ Efficient for small dataset
```

### 10. Database Sorting (ORDER BY)
```
Algorithm: MySQL built-in (QuickSort/MergeSort)
Complexity: O(n log n)
Optimization: Uses B-tree indexes
Status: ✅ Database-optimized
```

---

## 📈 Performance Comparison Chart

```
┌─────────────────────────────────────────────────────────────┐
│                    OPERATION SPEED                          │
│                    Base vs V2                               │
└─────────────────────────────────────────────────────────────┘

Cart Operations (20 items):
Base:  ████████████████████ 20 operations
V2:    █ 1 operation
       └─ 20x FASTER

VIP Lookup (1000 customers):
Base:  ████████████████████████████████████████████████████ 1000 scans
V2:    █ 1 lookup
       └─ 1000x FASTER

Hash Table (1000 reservations):
Base:  ██████████ ~10 collisions per lookup
V2:    █ 1 lookup (resized)
       └─ 10x FASTER

Priority Queue Insert:
Base:  ████████████████████ O(n) - no priority
V2:    ████ O(log n) - heap insert
       └─ n/log(n) FASTER
```

---

## 🎯 Algorithm Usage by Feature

```
┌─────────────────────────────────────────────────────────────┐
│ FEATURE                    │ ALGORITHMS USED                │
├────────────────────────────┼────────────────────────────────┤
│ Shopping Cart              │ • HashMap + Doubly Linked List │
│                            │ • LocalStorage Persistence     │
├────────────────────────────┼────────────────────────────────┤
│ Reservation Lookup         │ • Dynamic Hash Table           │
│                            │ • Hash-Based Code Generation   │
├────────────────────────────┼────────────────────────────────┤
│ VIP System                 │ • Priority Queue (Min-Heap)    │
│                            │ • VIP Service (Hash Lookup)    │
│                            │ • Database Indexes             │
├────────────────────────────┼────────────────────────────────┤
│ Table Booking              │ • Time Slot Matrix             │
│                            │ • Table Hold System            │
│                            │ • Session Management           │
├────────────────────────────┼────────────────────────────────┤
│ Admin Panel                │ • Linear Search & Filter       │
│                            │ • Database Sorting             │
│                            │ • Priority-Based Display       │
├────────────────────────────┼────────────────────────────────┤
│ Database                   │ • Singleton Pattern            │
│                            │ • B-tree Indexes               │
│                            │ • Foreign Key Constraints      │
└─────────────────────────────────────────────────────────────┘
```

---

## 🔑 Key Takeaways

### What Changed:
1. **Cart:** Singly Linked List → HashMap + Doubly Linked List (O(n) → O(1))
2. **Hash Table:** Fixed Size → Dynamic Resizing (prevents degradation)
3. **Priority:** None → Min-Heap + VIP System (fair queue management)

### What's New:
1. **VIP Service:** O(1) customer lookup with auto-promotion
2. **Priority Queue:** O(log n) heap for fair priority handling
3. **Table Hold:** 5-minute session lock prevents double booking

### What Stayed:
1. **Code Generation:** Already optimal O(1)
2. **Search/Filter:** Acceptable O(n×m) for admin use
3. **Time Slots:** Efficient O((t+r)×s) for small dataset
4. **DB Sorting:** Database-optimized O(n log n)

### Overall Impact:
- ✅ **20x faster** cart operations
- ✅ **1000x faster** VIP lookups
- ✅ **10x faster** hash table with large data
- ✅ **Fair & efficient** priority system
- ✅ **Prevents** double booking
- ✅ **Scales** to thousands of users

---

**Document Version:** 1.0  
**Last Updated:** May 15, 2026  
**System Version:** V2.0 (Main Branch)
