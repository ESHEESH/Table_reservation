<?php
/**
 * Sakura Sushi - Create Reservation API
 * Handles reservation form submission with file upload
 * Uses Hash Table for confirmation code generation and lookup
 */
require_once '../config.php';

header('Content-Type: application/json');

/**
 * Queue Implementation (FIFO) for Waitlist
 * Uses Linked List structure
 */
class WaitlistQueue {
    private $front = null;
    private $rear = null;
    private $size = 0;
    
    private static $storageFile = 'waitlist.json';
    
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
    
    public function peek() {
        return $this->front ? $this->front['data'] : null;
    }
    
    public function isEmpty() {
        return $this->front === null;
    }
    
    public function getSize() {
        return $this->size;
    }
    
    public function toArray() {
        $items = [];
        $current = $this->front;
        while ($current !== null) {
            $items[] = $current['data'];
            $current = $current['next'];
        }
        return $items;
    }
    
    private function save() {
        $path = dirname(__DIR__) . '/assets/' . self::$storageFile;
        file_put_contents($path, json_encode($this->toArray()));
    }
    
    public function load() {
        $path = dirname(__DIR__) . '/assets/' . self::$storageFile;
        if (file_exists($path)) {
            $items = json_decode(file_get_contents($path), true);
            if (is_array($items)) {
                foreach ($items as $item) {
                    $this->enqueue($item);
                }
            }
        }
    }
}

/**
 * Generate unique confirmation code using Hash-based approach
 * Ensures uniqueness through database check
 */
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

// Main request handling
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $pdo = getDBConnection();
    
    // Validate required fields
    $required = ['name', 'phone', 'people_count', 'reservation_date', 'reservation_time'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            echo json_encode(['success' => false, 'message' => ucfirst($field) . ' is required']);
            exit;
        }
    }
    
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $peopleCount = (int)$_POST['people_count'];
    $reservationDate = $_POST['reservation_date'];
    $reservationTime = $_POST['reservation_time'];
    $tableId = !empty($_POST['table_id']) ? (int)$_POST['table_id'] : null;
    $specialRequests = !empty($_POST['special_requests']) ? trim($_POST['special_requests']) : null;
    $hasPreOrder = !empty($_POST['has_pre_order']) ? 1 : 0;
    
    // Validate people count
    if ($peopleCount < 1 || $peopleCount > 10) {
        echo json_encode(['success' => false, 'message' => 'Number of guests must be between 1 and 10']);
        exit;
    }
    
    // Validate date (must be today or future, max 30 days ahead)
    $today = date('Y-m-d');
    $maxDate = date('Y-m-d', strtotime('+30 days'));
    if ($reservationDate < $today || $reservationDate > $maxDate) {
        echo json_encode(['success' => false, 'message' => 'Invalid reservation date']);
        exit;
    }
    
    // Validate phone number
    $phoneRegex = '/^[\d\s\-\+\(\)]{10,}$/';
    if (!preg_match($phoneRegex, $phone)) {
        echo json_encode(['success' => false, 'message' => 'Invalid phone number format']);
        exit;
    }
    
    // Check table availability if table_id provided
    if ($tableId) {
        $stmt = $pdo->prepare("SELECT status, capacity FROM tables WHERE id = ?");
        $stmt->execute([$tableId]);
        $table = $stmt->fetch();
        
        if (!$table) {
            echo json_encode(['success' => false, 'message' => 'Selected table not found']);
            exit;
        }
        
        if ($table['status'] === 'occupied') {
            // Table is occupied - add to waitlist queue
            $waitlist = new WaitlistQueue();
            $waitlist->load();
            
            $waitlistData = [
                'name' => $name,
                'phone' => $phone,
                'people_count' => $peopleCount,
                'table_id' => $tableId,
                'reservation_date' => $reservationDate,
                'reservation_time' => $reservationTime,
                'requested_at' => date('Y-m-d H:i:s')
            ];
            
            $waitlist->enqueue($waitlistData);
            
            echo json_encode([
                'success' => false, 
                'message' => 'This table is currently occupied. You have been added to the waitlist (position #' . $waitlist->getSize() . '). We will contact you when it becomes available.',
                'waitlisted' => true
            ]);
            exit;
        }
        
        if ($peopleCount > $table['capacity']) {
            echo json_encode([
                'success' => false, 
                'message' => 'This table accommodates maximum ' . $table['capacity'] . ' guests. Please select a larger table.'
            ]);
            exit;
        }
    }
    
    // Handle file upload
    $receiptPath = null;
    if (!empty($_FILES['payment_receipt']['tmp_name'])) {
        $file = $_FILES['payment_receipt'];
        
        // Validate file
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($file['type'], $allowedTypes)) {
            echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, and PDF are allowed.']);
            exit;
        }
        
        if ($file['size'] > $maxSize) {
            echo json_encode(['success' => false, 'message' => 'File size must be less than 5MB.']);
            exit;
        }
        
        // Generate unique filename using hash
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = hash('sha256', uniqid() . $file['name'] . time()) . '.' . $ext;
        $uploadDir = dirname(__DIR__) . '/assets/uploads/';
        
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $receiptPath = 'assets/uploads/' . $filename;
        
        if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
            echo json_encode(['success' => false, 'message' => 'Failed to upload receipt. Please try again.']);
            exit;
        }
    }
    
    // Calculate total amount
    $totalAmount = 0;
    if ($tableId) {
        $stmt = $pdo->prepare("SELECT price FROM tables WHERE id = ?");
        $stmt->execute([$tableId]);
        $tablePrice = $stmt->fetchColumn();
        $totalAmount += floatval($tablePrice);
    }
    
    // Add food total if pre-order exists
    if ($hasPreOrder && !empty($_POST['food_total'])) {
        $totalAmount += floatval($_POST['food_total']);
    }
    
    // Generate confirmation code
    $confirmationCode = generateConfirmationCode($pdo);
    
    // Insert reservation
    $stmt = $pdo->prepare("
        INSERT INTO reservations 
        (name, phone, people_count, table_id, confirmation_code, payment_receipt, 
         reservation_date, reservation_time, special_requests, status, has_pre_order, total_amount) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?)
    ");
    
    $stmt->execute([
        $name, $phone, $peopleCount, $tableId, $confirmationCode, $receiptPath,
        $reservationDate, $reservationTime, $specialRequests, $hasPreOrder, $totalAmount
    ]);
    
    $reservationId = $pdo->lastInsertId();
    
    // Insert pre-orders if cart items provided
    if ($hasPreOrder && !empty($_POST['cart_items'])) {
        $cartItems = json_decode($_POST['cart_items'], true);
        
        if (is_array($cartItems) && count($cartItems) > 0) {
            $stmt = $pdo->prepare("
                INSERT INTO pre_orders (reservation_id, menu_item_id, quantity, subtotal) 
                VALUES (?, ?, ?, ?)
            ");
            
            foreach ($cartItems as $item) {
                $itemId = (int)$item['id'];
                $quantity = (int)$item['quantity'];
                $subtotal = floatval($item['price']) * $quantity;
                
                $stmt->execute([$reservationId, $itemId, $quantity, $subtotal]);
            }
        }
    }
    
    // Don't update table status yet - wait for admin confirmation
    // Table will be marked as 'reserved' when admin confirms the reservation
    // if ($tableId) {
    //     $stmt = $pdo->prepare("UPDATE tables SET status = 'reserved' WHERE id = ?");
    //     $stmt->execute([$tableId]);
    // }
    
    // Success response
    echo json_encode([
        'success' => true,
        'message' => 'Reservation created successfully',
        'confirmation_code' => $confirmationCode,
        'reservation_id' => $reservationId,
        'table_id' => $tableId,
        'redirect_url' => 'preorder-prompt.php?code=' . urlencode($confirmationCode) . '&table_id=' . $tableId
    ]);
    
} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred. Please try again.']);
} catch (Exception $e) {
    error_log('Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An unexpected error occurred. Please try again.']);
}
