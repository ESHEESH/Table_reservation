<?php
/**
 * ============================================================================
 * SAKURA SUSHI - CREATE RESERVATION API
 * ============================================================================
 * 
 * ALGORITHMS IMPLEMENTED:
 * 
 * 1. PRIORITY QUEUE (Min-Heap) for Waitlist
 *    - VIP customers get highest priority
 *    - Early bookers get priority over late bookers
 *    - O(log n) enqueue/dequeue operations
 *    - Priority scores: 1000 (Platinum) to 5000+ (Regular)
 * 
 * 2. VIP IDENTIFICATION SYSTEM
 *    - Phone number-based lookup via database index
 *    - O(1) VIP status check using B-tree index
 *    - Auto-promotion based on booking history
 * 
 * 3. HASH-BASED CODE GENERATION
 *    - Generates unique confirmation codes
 *    - Format: SKR-XXXXXX (6 alphanumeric characters)
 *    - Collision detection via database uniqueness check
 *    - O(1) validation with indexed column
 * 
 * 4. TABLE HOLD SYSTEM
 *    - 5-minute temporary lock on table during form fill
 *    - Prevents double booking and bot abuse
 *    - Auto-expires if form not submitted
 * 
 * OVERVIEW:
 * - Handles reservation form submission
 * - Validates customer details and table availability
 * - Manages file upload for payment receipts
 * - Integrates with Priority Queue for waitlist
 * - Calculates priority scores for VIP/early bookers
 * - Releases table hold on successful submission
 * 
 * PRIORITY CALCULATION:
 * - VIP Platinum: priority = 1000
 * - VIP Gold: priority = 2000
 * - VIP Silver: priority = 3000
 * - VIP Bronze: priority = 4000
 * - Regular: priority = 5000 + timestamp
 * 
 * COMPLEXITY ANALYSIS:
 * - VIP check: O(1) - database index lookup
 * - Priority calculation: O(1) - constant time
 * - Waitlist enqueue: O(log n) - min-heap operation
 * - Code generation: O(1) average - hash-based with retry
 * - Insert reservation: O(1) - database insert
 * 
 * VALIDATION:
 * - Required fields: name, phone, people_count, date, time
 * - Phone format: 10+ digits with optional formatting
 * - Date range: Today to +30 days

session_start(); // Start session to access hold data
 * - People count: 1-10 guests
 * - Table capacity check
 * - Payment receipt: JPG, PNG, PDF (max 5MB)
 * 
 * FEATURES:
 * - VIP priority in waitlist
 * - Early booker advantage
 * - Pre-order integration
 * - File upload with SHA-256 hashing
 * - Automatic table status management
 * 
 * @version 2.0
 * @author Sakura Sushi Development Team
 * ============================================================================
 */
require_once '../config.php';
require_once '../classes/PriorityQueue.php';
require_once '../classes/VIPService.php';

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
            // Table is occupied - add to PRIORITY waitlist queue
            $vipService = new VIPService($pdo);
            $priorityQueue = new PriorityQueue();
            $priorityQueue->load();
            
            // Check if customer is VIP
            $vipLevel = $vipService->isVIP($phone);
            $isVip = ($vipLevel !== null);
            
            $waitlistData = [
                'name' => $name,
                'phone' => $phone,
                'people_count' => $peopleCount,
                'table_id' => $tableId,
                'reservation_date' => $reservationDate,
                'reservation_time' => $reservationTime,
                'requested_at' => date('Y-m-d H:i:s'),
                'is_vip' => $isVip,
                'vip_level' => $vipLevel
            ];
            
            $priority = $priorityQueue->enqueue($waitlistData, $isVip, $vipLevel);
            
            $priorityMessage = $isVip ? 
                "As a VIP $vipLevel member, you have priority access!" : 
                "Early bookers get priority!";
            
            echo json_encode([
                'success' => false, 
                'message' => "This table is currently occupied. You have been added to the priority waitlist (position #" . $priorityQueue->getSize() . "). $priorityMessage We will contact you when it becomes available.",
                'waitlisted' => true,
                'is_vip' => $isVip,
                'priority' => $priority
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
    
    // Check VIP status and calculate priority
    $vipService = new VIPService($pdo);
    $vipLevel = $vipService->isVIP($phone);
    $isVip = ($vipLevel !== null) ? 1 : 0;
    $bookingTimestamp = time();
    $priorityScore = $vipService->calculatePriorityScore($phone, $bookingTimestamp);
    
    // Insert reservation with VIP and priority data
    $stmt = $pdo->prepare("
        INSERT INTO reservations 
        (name, phone, people_count, table_id, confirmation_code, payment_receipt, 
         reservation_date, reservation_time, special_requests, status, is_vip, 
         priority_score, booking_timestamp, has_pre_order, total_amount) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $name, $phone, $peopleCount, $tableId, $confirmationCode, $receiptPath,
        $reservationDate, $reservationTime, $specialRequests, $isVip, 
        $priorityScore, $bookingTimestamp, $hasPreOrder, $totalAmount
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
    
    // Release table hold after successful reservation
    if (isset($_SESSION['current_hold'])) {
        $hold = $_SESSION['current_hold'];
        $holdKey = $hold['table_id'] . '_' . $hold['date'] . '_' . $hold['time'];
        
        if (isset($_SESSION['table_holds'][session_id()][$holdKey])) {
            unset($_SESSION['table_holds'][session_id()][$holdKey]);
        }
        
        unset($_SESSION['current_hold']);
    }
    
    // Success response
    echo json_encode([
        'success' => true,
        'message' => 'Reservation created successfully',
        'confirmation_code' => $confirmationCode,
        'reservation_id' => $reservationId,
        'table_id' => $tableId,
        'redirect_url' => '../preorder-prompt.php?code=' . urlencode($confirmationCode) . '&table_id=' . $tableId
    ]);
    
} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred. Please try again.']);
} catch (Exception $e) {
    error_log('Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An unexpected error occurred. Please try again.']);
}
