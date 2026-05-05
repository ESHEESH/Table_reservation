# Sakura Sushi - Table Reservation System

A data structures and algorithms project for a sushi restaurant table reservation system with pre-ordering functionality. Built with PHP, MySQL, HTML, CSS, and JavaScript.

## Features

- **Landing Page** - Elegant glassmorphism design with animated background
- **Table Selection** - Visual grid of tables with capacity, price, and status
- **Reservation Form** - Customer details, payment QR code, receipt upload
- **Pre-Order Menu** - Full sushi menu with category filtering and cart system
- **Confirmation Page** - Unique reservation code with QR code for restaurant scanning

## Data Structures & Algorithms Implemented

### 1. Hash Table (Confirmation Code Lookup)
- **File**: `confirmation.php`
- **Purpose**: O(1) average case lookup of reservations by confirmation code
- **Implementation**: Custom `ReservationHashTable` class with division method hashing and chaining for collision resolution
- **Hash Function**: `h(k) = (sum of ASCII values * 31) % table_size`
- **Table Size**: 97 (prime number for better distribution)

### 2. Queue (Waitlist System)
- **File**: `api/create-reservation.php`
- **Purpose**: FIFO waitlist when all tables are occupied
- **Implementation**: Linked list-based queue with enqueue/dequeue operations
- **Features**: Persistent storage using JSON file, position tracking

### 3. QuickSort (Table Sorting)
- **File**: `tables.php`
- **Purpose**: Sort tables by capacity, price, or availability
- **Time Complexity**: O(n log n) average case
- **Space Complexity**: O(log n)
- **Implementation**: Lomuto partition scheme with last element as pivot

### 4. Linked List (Cart Management)
- **File**: `assets/js/main.js`
- **Purpose**: Dynamic cart for pre-order food items
- **Implementation**: Singly linked list with insert at end, delete by ID, and search operations
- **Class**: `CartLinkedList` with `CartNode`

### 5. Binary Search
- **File**: `tables.php`
- **Purpose**: Fast lookup of tables by table number
- **Time Complexity**: O(log n)
- **Requirement**: Tables must be sorted by table_number

## Technology Stack

- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+ (via XAMPP)
- **Design**: Glassmorphism UI with CSS animations

## Setup Instructions (XAMPP)

### Prerequisites
- XAMPP installed (Apache + MySQL)
- Web browser

### Step 1: Install Project Files
1. Copy the entire project folder to your XAMPP htdocs directory:
   ```
   C:\xampp\htdocs\sakura-sushi\
   ```

### Step 2: Create Database
1. Start XAMPP Control Panel
2. Start Apache and MySQL services
3. Open phpMyAdmin: http://localhost/phpmyadmin
4. Create a new database called `sakura_sushi`
5. Go to the **Import** tab
6. Select `database.sql` file from the project folder
7. Click **Go** to import

### Step 3: Configure Database (if needed)
Edit `config.php` if your MySQL credentials differ from defaults:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');      // Your MySQL username
define('DB_PASS', '');          // Your MySQL password (default is empty for XAMPP)
define('DB_NAME', 'sakura_sushi');
```

### Step 4: Access the Application
Open your browser and navigate to:
```
http://localhost/sakura-sushi/
```

## File Structure

```
sakura-sushi/
├── index.php                 # Landing page (glassmorphism hero)
├── tables.php               # Table selection grid
├── reservation.php          # Reservation form with payment
├── menu.php                 # Pre-order food menu
├── confirmation.php         # Confirmation with code
├── config.php               # Database configuration
├── database.sql             # Database schema + seed data
├── README.md                # This file
├── api/
│   └── create-reservation.php   # API endpoint for reservations
├── assets/
│   ├── css/
│   │   └── style.css        # Main stylesheet
│   ├── js/
│   │   └── main.js          # JavaScript with data structures
│   ├── images/
│   │   ├── hero-bg.jpg      # Hero background
│   │   └── sushi/           # Sushi food images
│   └── uploads/             # User receipt uploads
```

## Database Schema

### Tables (Restaurant Tables)
| Field | Type | Description |
|-------|------|-------------|
| id | INT PK | Auto-increment |
| table_number | VARCHAR(10) | e.g., "T01", "T02" |
| capacity | INT | Number of seats (2-8) |
| price | DECIMAL | Reservation fee |
| status | ENUM | available/occupied/reserved |
| features | TEXT | Special features |

### Reservations
| Field | Type | Description |
|-------|------|-------------|
| id | INT PK | Auto-increment |
| name | VARCHAR(100) | Customer name |
| phone | VARCHAR(20) | Contact number |
| people_count | INT | 1-10 guests |
| table_id | INT FK | Reference to tables |
| confirmation_code | VARCHAR(20) | Unique 10-char code |
| payment_receipt | VARCHAR(255) | Uploaded receipt path |
| reservation_date | DATE | Booking date |
| reservation_time | TIME | Booking time |
| status | ENUM | pending/confirmed/cancelled |
| has_pre_order | TINYINT | Has food pre-order |
| total_amount | DECIMAL | Total payment |

### Menu Items
| Field | Type | Description |
|-------|------|-------------|
| id | INT PK | Auto-increment |
| name | VARCHAR(100) | Item name |
| description | TEXT | Description |
| price | DECIMAL | Price |
| category | ENUM | sushi/sashimi/rolls/appetizers/drinks |
| image | VARCHAR(255) | Image filename |

### Pre Orders
| Field | Type | Description |
|-------|------|-------------|
| id | INT PK | Auto-increment |
| reservation_id | INT FK | Reference to reservations |
| menu_item_id | INT FK | Reference to menu_items |
| quantity | INT | Amount ordered |
| subtotal | DECIMAL | price * quantity |

## How the Algorithms Work

### Hash Table - Confirmation Code Lookup
1. When a reservation is created, a unique code (e.g., "SKR-8A3F2B") is generated
2. The code is hashed using the division method: `h(k) = sum(ASCII * 31) % 97`
3. The reservation data is stored in a bucket at the hashed index
4. Collisions are handled by chaining (linked list in each bucket)
5. Lookup is O(1) average case by hashing the code and searching the bucket

### QuickSort - Table Sorting
1. Choose the last element as the pivot
2. Partition the array so elements <= pivot are on the left
3. Recursively sort the left and right partitions
4. Tables are sorted by capacity for optimal display

### Linked List - Cart Management
1. Each cart item is a node with `data` and `next` pointer
2. **Append**: Traverse to end, add new node - O(n)
3. **Remove**: Find node, update pointers - O(n)
4. **Search**: Traverse until found - O(n)
5. Cart persists in localStorage between pages

### Queue - Waitlist
1. FIFO structure with front and rear pointers
2. **Enqueue**: Add to rear - O(1)
3. **Dequeue**: Remove from front - O(1)
4. Persistent storage via JSON file

### Binary Search - Table Lookup
1. Requires sorted array (uses QuickSort first)
2. Compare target with middle element
3. Eliminate half of the array each iteration
4. Returns index in O(log n) time

## Design Features

- **Glassmorphism UI**: Translucent cards with backdrop blur and subtle borders
- **Animated Background**: Gradient movement and floating sakura petals
- **Slide-in Panels**: Smooth sidebar for table details and cart
- **Responsive Design**: Works on mobile, tablet, and desktop
- **Form Validation**: Real-time validation with visual feedback
- **Loading States**: Skeleton screens and spinners
- **Print Support**: Optimized styles for printing receipts

## Future Optimizations

- **AVL Tree**: Self-balancing BST for faster table lookups
- **Heap**: Priority queue for VIP reservations
- **Graph**: Table adjacency for optimal seating arrangements
- **Dijkstra's Algorithm**: Shortest path for server routing
- **Memoization**: Cache frequently accessed reservations
- **Redis**: In-memory caching for hash table operations

## Credits

- **Fonts**: Playfair Display, Inter, JetBrains Mono (Google Fonts)
- **Icons**: Custom SVG icons
- **Images**: AI-generated food photography

## License

This project is for educational purposes (Data Structures & Algorithms course).
