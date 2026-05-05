<?php
/**
 * Sakura Sushi Reservation System - Database Configuration
 * Data Structures & Algorithms Project
 */

// Database credentials for XAMPP (default)
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');         // Default XAMPP has no password
define('DB_NAME', 'sakura_sushi');

/**
 * Get database connection
 * @return PDO
 */
function getDBConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
    
    return $pdo;
}

/**
 * Initialize database tables if they don't exist
 */
function initDatabase() {
    $pdo = getDBConnection();
    
    // Create tables
    $pdo->exec("CREATE TABLE IF NOT EXISTS tables (
        id INT AUTO_INCREMENT PRIMARY KEY,
        table_number VARCHAR(10) NOT NULL UNIQUE,
        capacity INT NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        status ENUM('available', 'occupied', 'reserved') DEFAULT 'available',
        position_x INT DEFAULT 0,
        position_y INT DEFAULT 0,
        features TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS reservations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        phone VARCHAR(20) NOT NULL,
        people_count INT NOT NULL,
        table_id INT,
        confirmation_code VARCHAR(20) NOT NULL UNIQUE,
        payment_receipt VARCHAR(255),
        reservation_date DATE NOT NULL,
        reservation_time TIME NOT NULL,
        special_requests TEXT,
        status ENUM('pending', 'confirmed', 'cancelled') DEFAULT 'pending',
        has_pre_order TINYINT(1) DEFAULT 0,
        total_amount DECIMAL(10,2) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (table_id) REFERENCES tables(id)
    ) ENGINE=InnoDB");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS menu_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        price DECIMAL(10,2) NOT NULL,
        category ENUM('sushi', 'sashimi', 'rolls', 'appetizers', 'drinks') NOT NULL,
        image VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS pre_orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        reservation_id INT NOT NULL,
        menu_item_id INT NOT NULL,
        quantity INT NOT NULL DEFAULT 1,
        subtotal DECIMAL(10,2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (reservation_id) REFERENCES reservations(id),
        FOREIGN KEY (menu_item_id) REFERENCES menu_items(id)
    ) ENGINE=InnoDB");
    
    // Seed tables if empty
    $stmt = $pdo->query("SELECT COUNT(*) FROM tables");
    if ($stmt->fetchColumn() == 0) {
        seedTables($pdo);
    }
    
    // Seed menu if empty
    $stmt = $pdo->query("SELECT COUNT(*) FROM menu_items");
    if ($stmt->fetchColumn() == 0) {
        seedMenu($pdo);
    }
}

/**
 * Seed table data
 */
function seedTables($pdo) {
    $tables = [
        ['T01', 2, 15.00, 'available', 0, 0, 'Window view'],
        ['T02', 2, 15.00, 'available', 1, 0, 'Window view'],
        ['T03', 4, 25.00, 'available', 2, 0, 'Private booth'],
        ['T04', 4, 25.00, 'available', 0, 1, 'Center stage'],
        ['T05', 4, 25.00, 'available', 1, 1, 'Standard'],
        ['T06', 6, 35.00, 'available', 2, 1, 'Family size'],
        ['T07', 6, 35.00, 'available', 0, 2, 'Corner private'],
        ['T08', 6, 35.00, 'available', 1, 2, 'Sushi bar view'],
        ['T09', 8, 50.00, 'available', 2, 2, 'VIP section'],
        ['T10', 8, 50.00, 'available', 0, 3, 'VIP section'],
        ['T11', 2, 12.00, 'available', 1, 3, 'Counter seating'],
        ['T12', 2, 12.00, 'available', 2, 3, 'Counter seating'],
    ];
    
    $stmt = $pdo->prepare("INSERT INTO tables (table_number, capacity, price, status, position_x, position_y, features) VALUES (?, ?, ?, ?, ?, ?, ?)");
    foreach ($tables as $table) {
        $stmt->execute($table);
    }
}

/**
 * Seed menu items
 */
function seedMenu($pdo) {
    $items = [
        ['Salmon Nigiri', 'Fresh Norwegian salmon over seasoned rice', 8.50, 'sushi', 'salmon-nigiri.jpg'],
        ['Tuna Nigiri', 'Premium bluefin tuna over seasoned rice', 10.00, 'sushi', 'tuna-nigiri.jpg'],
        ['Ebi Nigiri', 'Sweet shrimp over seasoned rice', 9.00, 'sushi', 'ebi-nigiri.jpg'],
        ['Tamago Nigiri', 'Japanese sweet egg omelette over rice', 6.00, 'sushi', 'tamago-nigiri.jpg'],
        ['Tuna Sashimi', 'Thinly sliced premium bluefin tuna', 14.00, 'sashimi', 'tuna-sashimi.jpg'],
        ['Salmon Sashimi', 'Thinly sliced fresh Norwegian salmon', 12.00, 'sashimi', 'salmon-sashimi.jpg'],
        ['Yellowtail Sashimi', 'Butter-soft yellowtail slices', 15.00, 'sashimi', 'yellowtail-sashimi.jpg'],
        ['Mixed Sashimi', 'Assorted chef selection of fresh fish', 22.00, 'sashimi', 'mixed-sashimi.jpg'],
        ['Dragon Roll', 'Shrimp tempura inside, eel and avocado on top', 16.00, 'rolls', 'dragon-roll.jpg'],
        ['California Roll', 'Crab, avocado, and cucumber roll', 10.00, 'rolls', 'california-roll.jpg'],
        ['Spicy Tuna Roll', 'Spicy tuna with cucumber and sesame', 11.00, 'rolls', 'spicy-tuna-roll.jpg'],
        ['Rainbow Roll', 'California roll topped with assorted fish', 15.00, 'rolls', 'rainbow-roll.jpg'],
        ['Edamame', 'Steamed soybeans with sea salt', 5.00, 'appetizers', 'edamame.jpg'],
        ['Miso Soup', 'Traditional soybean paste soup with tofu and seaweed', 4.00, 'appetizers', 'miso-soup.jpg'],
        ['Gyoza', 'Pan-fried pork and vegetable dumplings (6 pcs)', 7.00, 'appetizers', 'gyoza.jpg'],
        ['Tempura', 'Assorted shrimp and vegetable tempura', 9.00, 'appetizers', 'tempura.jpg'],
        ['Japanese Sake', 'Warm premium junmai sake (180ml)', 8.00, 'drinks', 'sake.jpg'],
        ['Green Tea', 'Traditional Japanese green tea', 3.00, 'drinks', 'green-tea.jpg'],
        ['Ramune', 'Japanese marble soda (original)', 4.00, 'drinks', 'ramune.jpg'],
        ['Matcha Latte', 'Creamy matcha green tea latte', 5.50, 'drinks', 'matcha-latte.jpg'],
    ];
    
    $stmt = $pdo->prepare("INSERT INTO menu_items (name, description, price, category, image) VALUES (?, ?, ?, ?, ?)");
    foreach ($items as $item) {
        $stmt->execute($item);
    }
}

// Initialize database on first load
initDatabase();
