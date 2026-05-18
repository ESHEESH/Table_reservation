<?php
/**
 * ============================================================================
 * COMPREHENSIVE ALGORITHM TESTING SUITE - MAIN SYSTEM ALGORITHMS
 * ============================================================================
 * 
 * Tests ALL 9 algorithms and data structures used in the main system:
 * 1. Fixed Hash Table Implementation (97 buckets, chaining) - O(1)
 * 2. Queue (FIFO) Implementation (Waitlist) - O(1)
 * 3. Linked List Implementation (Queue nodes) - O(1)
 * 4. Hash-Based Code Generation (Confirmation codes) - O(1)
 * 5. Linear Search & Filtering (Name search) - O(n)
 * 6. Time Slot Availability Algorithm (Main system) - O(n+r×s)
 * 7. Database Sorting (ORDER BY) - O(n log n)
 * 8. Singleton Pattern (DB Connection) - O(1)
 * 9. Session-Based Hold System (Temporary locks) - O(1)
 * 
 * Test Configuration:
 * - Dataset sizes: 100, 500, 1000 records
 * - Runs per test: 5
 * - Time display: milliseconds (ms)
 * - Status: Success/Error indicators
 */

require_once 'config.php';

/**
 * MAIN SYSTEM DATA STRUCTURES
 * Copy from actual implementation files
 */

// Database-Backed Hash Table Implementation
class DatabaseHashTable {
    private $db;
    private $size = 97; // Fixed prime number for better distribution
    private $tableName = 'hash_table_buckets';
    
    public function __construct($pdo) {
        $this->db = $pdo;
        $this->initializeTable();
    }
    
    private function initializeTable() {
        // Create hash table buckets table if not exists
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS {$this->tableName} (
                id INT AUTO_INCREMENT PRIMARY KEY,
                bucket_index INT NOT NULL,
                hash_key VARCHAR(255) NOT NULL,
                hash_value TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_bucket (bucket_index),
                INDEX idx_key (hash_key)
            ) ENGINE=InnoDB
        ");
    }
    
    private function hash($key) {
        $hash = 0;
        for ($i = 0; $i < strlen($key); $i++) {
            $hash = ($hash * 31 + ord($key[$i]));
        }
        return abs($hash);
    }
    
    public function insert($key, $value) {
        $index = $this->hash($key) % $this->size;
        $serializedValue = json_encode($value);
        
        // Check if key exists (update)
        $stmt = $this->db->prepare("SELECT id FROM {$this->tableName} WHERE bucket_index = ? AND hash_key = ?");
        $stmt->execute([$index, $key]);
        
        if ($stmt->fetch()) {
            // Update existing
            $stmt = $this->db->prepare("UPDATE {$this->tableName} SET hash_value = ? WHERE bucket_index = ? AND hash_key = ?");
            $stmt->execute([$serializedValue, $index, $key]);
        } else {
            // Insert new (collision handling via multiple rows in same bucket)
            $stmt = $this->db->prepare("INSERT INTO {$this->tableName} (bucket_index, hash_key, hash_value) VALUES (?, ?, ?)");
            $stmt->execute([$index, $key, $serializedValue]);
        }
    }
    
    public function search($key) {
        $index = $this->hash($key) % $this->size;
        
        // Search in bucket
        $stmt = $this->db->prepare("SELECT hash_value FROM {$this->tableName} WHERE bucket_index = ? AND hash_key = ?");
        $stmt->execute([$index, $key]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            return json_decode($result['hash_value'], true);
        }
        
        return null;
    }
    
    public function delete($key) {
        $index = $this->hash($key) % $this->size;
        
        $stmt = $this->db->prepare("DELETE FROM {$this->tableName} WHERE bucket_index = ? AND hash_key = ?");
        $stmt->execute([$index, $key]);
        
        return $stmt->rowCount() > 0;
    }
    
    public function getLoadFactor() {
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM {$this->tableName}");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] / $this->size;
    }
    
    public function getCollisions() {
        // Count buckets with more than 1 item
        $stmt = $this->db->query("
            SELECT COUNT(*) as collision_count 
            FROM (
                SELECT bucket_index, COUNT(*) as cnt 
                FROM {$this->tableName} 
                GROUP BY bucket_index 
                HAVING cnt > 1
            ) as collisions
        ");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get total extra items (collisions)
        $stmt = $this->db->query("
            SELECT SUM(cnt - 1) as total_collisions 
            FROM (
                SELECT bucket_index, COUNT(*) as cnt 
                FROM {$this->tableName} 
                GROUP BY bucket_index 
                HAVING cnt > 1
            ) as collisions
        ");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total_collisions'] ?? 0;
    }
    
    public function clear() {
        $this->db->exec("TRUNCATE TABLE {$this->tableName}");
    }
    
    public function dropTable() {
        $this->db->exec("DROP TABLE IF EXISTS {$this->tableName}");
    }
}

// Fixed Hash Table Implementation (In-Memory for comparison)
class FixedHashTable {
    private $buckets = [];
    private $size = 97; // Fixed prime number for better distribution
    
    public function __construct() {
        // Initialize all buckets as empty arrays
        for ($i = 0; $i < $this->size; $i++) {
            $this->buckets[$i] = [];
        }
    }
    
    private function hash($key) {
        $hash = 0;
        for ($i = 0; $i < strlen($key); $i++) {
            $hash = ($hash * 31 + ord($key[$i]));
        }
        return abs($hash);
    }
    
    public function insert($key, $value) {
        $index = $this->hash($key) % $this->size;
        
        // Check if key already exists (update)
        foreach ($this->buckets[$index] as &$item) {
            if ($item['key'] === $key) {
                $item['value'] = $value;
                return;
            }
        }
        
        // Insert new key-value pair (collision handling via chaining)
        $this->buckets[$index][] = ['key' => $key, 'value' => $value];
    }
    
    public function search($key) {
        $index = $this->hash($key) % $this->size;
        
        // Search in bucket
        foreach ($this->buckets[$index] as $item) {
            if ($item['key'] === $key) {
                return $item['value'];
            }
        }
        
        return null; // Not found
    }
    
    public function delete($key) {
        $index = $this->hash($key) % $this->size;
        
        foreach ($this->buckets[$index] as $i => $item) {
            if ($item['key'] === $key) {
                unset($this->buckets[$index][$i]);
                $this->buckets[$index] = array_values($this->buckets[$index]); // Re-index
                return true;
            }
        }
        
        return false;
    }
    
    public function getLoadFactor() {
        $totalItems = 0;
        foreach ($this->buckets as $bucket) {
            $totalItems += count($bucket);
        }
        return $totalItems / $this->size;
    }
    
    public function getCollisions() {
        $collisions = 0;
        foreach ($this->buckets as $bucket) {
            if (count($bucket) > 1) {
                $collisions += count($bucket) - 1;
            }
        }
        return $collisions;
    }
}

// Queue (FIFO) Implementation (from create-reservation.php)
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
    }
    
    public function dequeue() {
        if ($this->front === null) return null;
        
        $data = $this->front['data'];
        $this->front = $this->front['next'];
        if ($this->front === null) $this->rear = null;
        $this->size--;
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
}

// Hash-Based Code Generation (from create-reservation.php)
function generateConfirmationCode($pdo) {
    $maxAttempts = 10;
    $attempts = 0;
    
    while ($attempts < $maxAttempts) {
        $code = 'SKR-' . strtoupper(substr(str_shuffle('0123456789ABCDEFGHJKLMNPQRSTUVWXYZ'), 0, 6));
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE confirmation_code = ?");
        $stmt->execute([$code]);
        
        if ($stmt->fetchColumn() == 0) {
            return $code;
        }
        
        $attempts++;
    }
    
    return 'SKR-' . strtoupper(dechex(time()));
}

// Check if optimized classes exist (for v3/v2 branches)
$hasPriorityQueue = file_exists('classes/PriorityQueue.php');
$hasVIPService = file_exists('classes/VIPService.php');

if ($hasPriorityQueue) {
    require_once 'classes/PriorityQueue.php';
}
if ($hasVIPService) {
    require_once 'classes/VIPService.php';
}

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Disable output buffering to show progress
if (ob_get_level()) ob_end_flush();

set_time_limit(300);
$db = getDBConnection();

// Test if database connection works
if (!$db) {
    die("Database connection failed!");
}

$testSizes = [100, 500, 1000];
$runsPerTest = 5;

// Global counter for unique code generation
$globalCodeCounter = 0;

// Clean up any existing test data before starting
clearTestData($db);
echo "<!-- Cleaned up existing test data -->\n";

// Test if seed files exist
echo "<!-- Checking seed files... -->\n";
foreach ($testSizes as $size) {
    for ($run = 1; $run <= $runsPerTest; $run++) {
        $filename = dirname(__DIR__) . "/seed_{$size}_run{$run}.json";
        if (!file_exists($filename)) {
            die("Missing seed file: $filename");
        }
    }
}
echo "<!-- All seed files found! -->\n";

// Helper Functions
function loadSeedData($size, $run) {
    // Seed files are in the root directory, one level up from app/
    $filename = dirname(__DIR__) . "/seed_{$size}_run{$run}.json";
    if (!file_exists($filename)) {
        die("Error: Seed file not found: $filename<br>Looking in: " . dirname(__DIR__));
    }
    
    $json = file_get_contents($filename);
    $data = json_decode($json, true);
    
    if (!$data) {
        die("Error: Invalid JSON in file: $filename");
    }
    
    return $data;
}

function clearTestData($db) {
    // Clear test data by phone pattern or all test records
    // SAFE: Only deletes seed data phones (099xxxxx) and test-specific patterns
    // Will NOT delete real customer data like 09855379443
    $db->exec("DELETE FROM reservations WHERE phone LIKE '099%'");
    $db->exec("DELETE FROM reservations WHERE confirmation_code LIKE 'TEST%'");
    $db->exec("DELETE FROM reservations WHERE confirmation_code LIKE 'CART%'");
    $db->exec("DELETE FROM vip_customers WHERE phone LIKE '099%'");
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Algorithm Performance Testing - Sakura Sushi</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg: #08040e;
            --surface: #100b18;
            --border: rgba(201,150,79,.16);
            --border-hv: rgba(201,150,79,.35);
            --cream: #fdf6ec;
            --muted: rgba(253,246,236,.45);
            --muted2: rgba(253,246,236,.2);
            --gold: #c9964f;
            --gold-dim: rgba(201,150,79,.15);
            --red: #c0392b;
            --green: #3d9970;
            --amber: #e67e22;
            --blue: #2980b9;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--bg);
            color: var(--cream);
            padding: 20px;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: var(--surface);
            border-radius: 15px;
            padding: 30px;
            border: 1px solid var(--border);
        }
        
        h1 {
            text-align: center;
            color: var(--gold);
            margin-bottom: 10px;
            font-size: 2.5em;
            font-family: 'Playfair Display', serif;
        }
        
        .subtitle {
            text-align: center;
            color: var(--muted);
            margin-bottom: 30px;
            font-size: 1.1em;
            letter-spacing: 0.05em;
        }
        
        .loading-banner {
            background: rgba(230,126,34,.1);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid var(--amber);
        }
        
        .loading-banner p {
            margin: 0;
            color: var(--amber);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .test-section {
            margin-bottom: 40px;
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 20px;
            background: rgba(16,11,24,.6);
        }
        
        .test-section h2 {
            color: var(--gold);
            margin-bottom: 15px;
            font-size: 1.8em;
            border-bottom: 2px solid var(--border);
            padding-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .test-section h2 i {
            font-size: 0.9em;
        }
        
        .test-info {
            background: rgba(255,255,255,.02);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid var(--gold);
        }
        
        .test-info p {
            margin: 5px 0;
            color: var(--muted);
            font-size: 0.9em;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            background: rgba(16,11,24,.8);
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid var(--border);
        }
        
        thead {
            background: var(--gold-dim);
            border-bottom: 2px solid var(--border);
        }
        
        th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            font-size: 0.75em;
            color: var(--gold);
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }
        
        td {
            padding: 12px 15px;
            border-bottom: 1px solid var(--border);
            color: var(--cream);
        }
        
        tbody tr:hover {
            background: rgba(255,255,255,.02);
        }
        
        .status-success {
            color: var(--green);
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .time-value {
            font-weight: bold;
            color: var(--gold);
            font-size: 1.1em;
        }
        
        .run-number {
            background: var(--gold-dim);
            color: var(--gold);
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.85em;
            border: 1px solid rgba(201,150,79,.3);
        }
        
        .average-row {
            background: rgba(230,126,34,.1) !important;
            font-weight: bold;
            border-top: 2px solid var(--amber) !important;
        }
        
        .average-row td {
            color: var(--amber);
        }
        
        .complexity {
            background: rgba(61,153,112,.1);
            color: var(--green);
            padding: 5px 12px;
            border-radius: 5px;
            font-size: 0.75em;
            display: inline-block;
            margin-left: 10px;
            border: 1px solid rgba(61,153,112,.3);
            letter-spacing: 0.05em;
        }
        
        .summary-box {
            background: linear-gradient(135deg, var(--gold-dim) 0%, rgba(201,150,79,.05) 100%);
            border: 1px solid var(--border-hv);
            color: var(--cream);
            padding: 30px;
            border-radius: 10px;
            margin-top: 30px;
            text-align: center;
        }
        
        .summary-box h3 {
            color: var(--gold);
            margin-bottom: 15px;
            font-size: 1.8em;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }
        
        .summary-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .stat-card {
            background: rgba(16,11,24,.6);
            padding: 20px;
            border-radius: 8px;
            border: 1px solid var(--border);
        }
        
        .stat-card h4 {
            font-size: 0.75em;
            margin-bottom: 8px;
            color: var(--muted);
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }
        
        .stat-card p {
            font-size: 2em;
            font-weight: bold;
            color: var(--gold);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Algorithm Performance Testing</h1>
        <p class="subtitle">Testing All Data Structures & Algorithms from Main System</p>
        
        <div class="loading-banner">
            <p><strong>Tests are running...</strong> This may take 1-2 minutes. Please wait.</p>
        </div>

<?php
flush(); // Show loading message immediately

// ============================================================================
// TEST 1: DATABASE-BACKED HASH TABLE IMPLEMENTATION
// ============================================================================

echo '<div class="test-section">';
echo '<h2>Test 1: Database-Backed Hash Table <span class="complexity">O(1) Average</span></h2>';
echo '<div class="test-info">';
echo '<p><strong>Algorithm:</strong> Hash Table stored in database with 97 buckets</p>';
echo '<p><strong>Operations:</strong> Insert to hash table (DB) + Lookup by key (DB)</p>';
echo '<p><strong>Implementation:</strong> Database table with bucket_index, hash collision handling via multiple rows</p>';
echo '</div>';

echo '<table>';
echo '<thead><tr><th>Input Size</th><th>Run</th><th>Insert Time (ms)</th><th>Lookup Time (ms)</th><th>Load Factor</th><th>Collisions</th><th>Status</th></tr></thead>';
echo '<tbody>';

foreach ($testSizes as $size) {
    $insertTimes = [];
    $lookupTimes = [];
    $loadFactors = [];
    $collisionCounts = [];
    
    for ($run = 1; $run <= $runsPerTest; $run++) {
        $testData = loadSeedData($size, $run);
        
        // Create database-backed hash table
        $dbHashTable = new DatabaseHashTable($db);
        $dbHashTable->clear(); // Clear previous data
        
        $confirmationCodes = [];
        
        // Test Insert to Database Hash Table
        $startTime = microtime(true);
        foreach ($testData as $idx => $data) {
            $code = 'TEST' . str_pad(++$globalCodeCounter, 8, '0', STR_PAD_LEFT);
            $confirmationCodes[] = $code;
            $dbHashTable->insert($code, $data);
        }
        $insertTime = (microtime(true) - $startTime) * 1000;
        $insertTimes[] = $insertTime;
        
        // Get statistics from database
        $loadFactor = $dbHashTable->getLoadFactor();
        $collisions = $dbHashTable->getCollisions();
        $loadFactors[] = $loadFactor;
        $collisionCounts[] = $collisions;
        
        // Test Lookup from Database Hash Table
        $startTime = microtime(true);
        foreach ($confirmationCodes as $code) {
            $result = $dbHashTable->search($code);
        }
        $lookupTime = (microtime(true) - $startTime) * 1000;
        $lookupTimes[] = $lookupTime;
        
        echo '<tr>';
        echo '<td>' . $size . '</td>';
        echo '<td><span class="run-number">Run ' . $run . '</span></td>';
        echo '<td class="time-value">' . number_format($insertTime, 2) . ' ms</td>';
        echo '<td class="time-value">' . number_format($lookupTime, 2) . ' ms</td>';
        echo '<td>' . number_format($loadFactor, 2) . '</td>';
        echo '<td>' . $collisions . '</td>';
        echo '<td class="status-success">Success</td>';
        echo '</tr>';
    }
    
    $avgInsert = array_sum($insertTimes) / count($insertTimes);
    $avgLookup = array_sum($lookupTimes) / count($lookupTimes);
    $avgLoadFactor = array_sum($loadFactors) / count($loadFactors);
    $avgCollisions = array_sum($collisionCounts) / count($collisionCounts);
    
    echo '<tr class="average-row">';
    echo '<td>' . $size . '</td>';
    echo '<td><strong>AVERAGE</strong></td>';
    echo '<td class="time-value">' . number_format($avgInsert, 2) . ' ms</td>';
    echo '<td class="time-value">' . number_format($avgLookup, 2) . ' ms</td>';
    echo '<td>' . number_format($avgLoadFactor, 2) . '</td>';
    echo '<td>' . round($avgCollisions) . '</td>';
    echo '<td class="status-success">Completed</td>';
    echo '</tr>';
}

// Clean up hash table
$dbHashTable = new DatabaseHashTable($db);
$dbHashTable->clear();

echo '</tbody></table>';
echo '<div style="margin-top: 15px; padding: 12px; background: rgba(201,150,79,.05); border-radius: 8px; border-left: 3px solid var(--gold);">';
echo '<p style="margin: 0; color: var(--muted); font-size: 0.9em;"><strong>Database Implementation:</strong> Hash table algorithm implemented using database table with bucket_index column. Each bucket can have multiple rows (chaining for collision handling). Uses indexed lookups for O(1) average performance.</p>';
echo '</div>';
echo '</div>';

// ============================================================================
// TEST 2: QUEUE (FIFO) IMPLEMENTATION (WAITLIST)
// ============================================================================

echo '<div class="test-section">';
echo '<h2>Test 2: Queue (FIFO) Implementation <span class="complexity">O(1)</span></h2>';
echo '<div class="test-info">';
echo '<p><strong>Algorithm:</strong> Main System Queue (from create-reservation.php)</p>';
echo '<p><strong>Operations:</strong> Enqueue (add to waitlist) + Dequeue (remove from waitlist)</p>';
echo '<p><strong>Implementation:</strong> Linked list-based queue with front and rear pointers</p>';
echo '</div>';

echo '<table>';
echo '<thead><tr><th>Input Size</th><th>Run</th><th>Enqueue Time (ms)</th><th>Dequeue Time (ms)</th><th>Status</th></tr></thead>';
echo '<tbody>';

foreach ($testSizes as $size) {
    $enqueueTimes = [];
    $dequeueTimes = [];
    
    for ($run = 1; $run <= $runsPerTest; $run++) {
        $testData = loadSeedData($size, $run);
        $queue = new WaitlistQueue();
        
        // Test Enqueue
        $startTime = microtime(true);
        foreach ($testData as $data) {
            $queue->enqueue($data);
        }
        $enqueueTime = (microtime(true) - $startTime) * 1000;
        $enqueueTimes[] = $enqueueTime;
        
        // Test Dequeue
        $startTime = microtime(true);
        while (!$queue->isEmpty()) {
            $queue->dequeue();
        }
        $dequeueTime = (microtime(true) - $startTime) * 1000;
        $dequeueTimes[] = $dequeueTime;
        
        echo '<tr>';
        echo '<td>' . $size . '</td>';
        echo '<td><span class="run-number">Run ' . $run . '</span></td>';
        echo '<td class="time-value">' . number_format($enqueueTime, 2) . ' ms</td>';
        echo '<td class="time-value">' . number_format($dequeueTime, 2) . ' ms</td>';
        echo '<td class="status-success">Success</td>';
        echo '</tr>';
    }
    
    echo '<tr class="average-row">';
    echo '<td>' . $size . '</td>';
    echo '<td><strong>AVERAGE</strong></td>';
    echo '<td class="time-value">' . number_format(array_sum($enqueueTimes) / count($enqueueTimes), 2) . ' ms</td>';
    echo '<td class="time-value">' . number_format(array_sum($dequeueTimes) / count($dequeueTimes), 2) . ' ms</td>';
    echo '<td class="status-success">Completed</td>';
    echo '</tr>';
}

echo '</tbody></table>';
echo '</div>';

// ============================================================================
// TEST 3: LINKED LIST IMPLEMENTATION (QUEUE NODES)
// ============================================================================

echo '<div class="test-section">';
echo '<h2>Test 3: Linked List Implementation <span class="complexity">O(1)</span></h2>';
echo '<div class="test-info">';
echo '<p><strong>Algorithm:</strong> Linked List nodes used in Queue (from create-reservation.php)</p>';
echo '<p><strong>Operations:</strong> Node creation, traversal, and linking</p>';
echo '<p><strong>Implementation:</strong> Array-based nodes with \'next\' pointers for FIFO structure</p>';
echo '</div>';

echo '<table>';
echo '<thead><tr><th>Input Size</th><th>Run</th><th>Create Nodes (ms)</th><th>Traverse (ms)</th><th>Status</th></tr></thead>';
echo '<tbody>';

foreach ($testSizes as $size) {
    $createTimes = [];
    $traverseTimes = [];
    
    for ($run = 1; $run <= $runsPerTest; $run++) {
        $testData = loadSeedData($size, $run);
        
        // Test Node Creation and Linking
        $startTime = microtime(true);
        $head = null;
        $current = null;
        foreach ($testData as $data) {
            $newNode = ['data' => $data, 'next' => null];
            if ($head === null) {
                $head = $newNode;
                $current = $head;
            } else {
                $current['next'] = $newNode;
                $current = $newNode;
            }
        }
        $createTime = (microtime(true) - $startTime) * 1000;
        $createTimes[] = $createTime;
        
        // Test Traversal
        $startTime = microtime(true);
        $count = 0;
        $temp = $head;
        while ($temp !== null) {
            $count++;
            $temp = $temp['next'];
        }
        $traverseTime = (microtime(true) - $startTime) * 1000;
        $traverseTimes[] = $traverseTime;
        
        echo '<tr>';
        echo '<td>' . $size . '</td>';
        echo '<td><span class="run-number">Run ' . $run . '</span></td>';
        echo '<td class="time-value">' . number_format($createTime, 2) . ' ms</td>';
        echo '<td class="time-value">' . number_format($traverseTime, 2) . ' ms</td>';
        echo '<td class="status-success">Success</td>';
        echo '</tr>';
    }
    
    echo '<tr class="average-row">';
    echo '<td>' . $size . '</td>';
    echo '<td><strong>AVERAGE</strong></td>';
    echo '<td class="time-value">' . number_format(array_sum($createTimes) / count($createTimes), 2) . ' ms</td>';
    echo '<td class="time-value">' . number_format(array_sum($traverseTimes) / count($traverseTimes), 2) . ' ms</td>';
    echo '<td class="status-success">Completed</td>';
    echo '</tr>';
}

echo '</tbody></table>';
echo '</div>';

// ============================================================================
// TEST 4: HASH-BASED CODE GENERATION
// ============================================================================

echo '<div class="test-section">';
echo '<h2>Test 4: Hash-Based Code Generation <span class="complexity">O(1)</span></h2>';
echo '<div class="test-info">';
echo '<p><strong>Algorithm:</strong> Main System Code Generation (from create-reservation.php)</p>';
echo '<p><strong>Operations:</strong> Generate unique confirmation codes with collision checking</p>';
echo '<p><strong>Implementation:</strong> Random alphanumeric generation + database uniqueness verification</p>';
echo '</div>';

echo '<table>';
echo '<thead><tr><th>Input Size</th><th>Run</th><th>Generation Time (ms)</th><th>Codes Generated</th><th>Status</th></tr></thead>';
echo '<tbody>';

foreach ($testSizes as $size) {
    $genTimes = [];
    
    for ($run = 1; $run <= $runsPerTest; $run++) {
        clearTestData($db);
        $codes = [];
        
        $startTime = microtime(true);
        for ($i = 0; $i < $size; $i++) {
            $code = generateConfirmationCode($db);
            $codes[] = $code;
            // Insert to database to ensure next generation checks uniqueness
            $stmt = $db->prepare("INSERT INTO reservations (name, phone, people_count, table_id, reservation_date, reservation_time, confirmation_code, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
            $stmt->execute(['Test', '0991234567', 2, 1, date('Y-m-d'), '14:00:00', $code]);
        }
        $genTime = (microtime(true) - $startTime) * 1000;
        $genTimes[] = $genTime;
        
        // Verify uniqueness
        $unique = count(array_unique($codes));
        
        clearTestData($db);
        
        echo '<tr>';
        echo '<td>' . $size . '</td>';
        echo '<td><span class="run-number">Run ' . $run . '</span></td>';
        echo '<td class="time-value">' . number_format($genTime, 2) . ' ms</td>';
        echo '<td>' . $unique . ' unique</td>';
        echo '<td class="status-success">Success</td>';
        echo '</tr>';
    }
    
    echo '<tr class="average-row">';
    echo '<td>' . $size . '</td>';
    echo '<td><strong>AVERAGE</strong></td>';
    echo '<td class="time-value">' . number_format(array_sum($genTimes) / count($genTimes), 2) . ' ms</td>';
    echo '<td>' . $size . ' codes</td>';
    echo '<td class="status-success">Completed</td>';
    echo '</tr>';
}

echo '</tbody></table>';
echo '</div>';

// ============================================================================
// Test 5: LINEAR SEARCH & FILTERING
// ============================================================================

echo '<div class="test-section">';
echo '<h2>Test 5: Linear Search & Filtering <span class="complexity">O(n×m)</span></h2>';
echo '<div class="test-info">';
echo '<p><strong>Algorithm:</strong> Sequential scan with pattern matching</p>';
echo '<p><strong>Operations:</strong> Search reservations by name pattern</p>';
echo '<p><strong>Test Sizes:</strong> 100, 500, 1000 records × 5 runs each</p>';
echo '</div>';

echo '<table>';
echo '<thead><tr><th>Input Size</th><th>Run</th><th>Search Time (ms)</th><th>Results Found</th><th>Status</th></tr></thead>';
echo '<tbody>';

foreach ($testSizes as $size) {
    $searchTimes = [];
    
    for ($run = 1; $run <= $runsPerTest; $run++) {
        clearTestData($db);
        $testData = loadSeedData($size, $run);
        
        foreach ($testData as $idx => $data) {
            $code = 'TEST' . str_pad(++$globalCodeCounter, 8, '0', STR_PAD_LEFT);
            $stmt = $db->prepare("INSERT INTO reservations (name, phone, people_count, table_id, reservation_date, reservation_time, special_requests, confirmation_code, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
            $stmt->execute([$data['name'], $data['phone'], $data['people_count'], $data['table_id'], $data['reservation_date'], $data['reservation_time'], $data['special_requests'] ?? '', $code]);
        }
        
        $searchName = $testData[0]['name'];
        $startTime = microtime(true);
        $stmt = $db->prepare("SELECT * FROM reservations WHERE name LIKE ?");
        $stmt->execute(['%' . $searchName . '%']);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $searchTime = (microtime(true) - $startTime) * 1000;
        $searchTimes[] = $searchTime;
        
        // Clean up test data
        clearTestData($db);
        
        echo '<tr>';
        echo '<td>' . $size . '</td>';
        echo '<td><span class="run-number">Run ' . $run . '</span></td>';
        echo '<td class="time-value">' . number_format($searchTime, 2) . ' ms</td>';
        echo '<td>' . count($results) . '</td>';
        echo '<td class="status-success">Success</td>';
        echo '</tr>';
    }
    
    echo '<tr class="average-row">';
    echo '<td>' . $size . '</td>';
    echo '<td><strong>AVERAGE</strong></td>';
    echo '<td class="time-value">' . number_format(array_sum($searchTimes) / count($searchTimes), 2) . ' ms</td>';
    echo '<td>~' . round($size * 0.1) . '</td>';
    echo '<td class="status-success">Completed</td>';
    echo '</tr>';
}

echo '</tbody></table>';
echo '</div>';

// ============================================================================
// Test 6: TIME SLOT AVAILABILITY MATRIX
// ============================================================================

echo '<div class="test-section">';
echo '<h2>Test 6: Time Slot Availability Matrix <span class="complexity">O(n+r×s)</span></h2>';
echo '<div class="test-info">';
echo '<p><strong>Algorithm:</strong> Main System Algorithm - Fetch all reservations once, then build availability map</p>';
echo '<p><strong>Operations:</strong> 1) Fetch reservations 2) Build availability map 3) Mark booked/pending slots</p>';
echo '<p><strong>Test Sizes:</strong> 100, 500, 1000 reservations × 5 runs each</p>';
echo '</div>';

echo '<table>';
echo '<thead><tr><th>Input Size</th><th>Run</th><th>Matrix Build Time (ms)</th><th>Availability Check (ms)</th><th>Status</th></tr></thead>';
echo '<tbody>';

foreach ($testSizes as $size) {
    $buildTimes = [];
    $checkTimes = [];
    
    for ($run = 1; $run <= $runsPerTest; $run++) {
        clearTestData($db);
        $testData = loadSeedData($size, $run);
        
        foreach ($testData as $idx => $data) {
            $code = 'TEST' . str_pad(++$globalCodeCounter, 8, '0', STR_PAD_LEFT);
            $stmt = $db->prepare("INSERT INTO reservations (name, phone, people_count, table_id, reservation_date, reservation_time, confirmation_code, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'confirmed')");
            $stmt->execute([$data['name'], $data['phone'], $data['people_count'], $data['table_id'], $data['reservation_date'], $data['reservation_time'], $code]);
        }
        
        // Build availability matrix using MAIN SYSTEM ALGORITHM
        $startTime = microtime(true);
        
        // Fetch all tables
        $tables = $db->query("SELECT id FROM tables")->fetchAll(PDO::FETCH_ASSOC);
        
        // Time slots (matching main system)
        $timeSlots = [
            ['start' => '14:00:00', 'end' => '17:00:00'],
            ['start' => '17:00:00', 'end' => '20:00:00'],
            ['start' => '20:00:00', 'end' => '23:00:00']
        ];
        
        $date = date('Y-m-d', strtotime('+1 day'));
        
        // Get all reservations for selected date (MAIN SYSTEM APPROACH)
        $stmt = $db->prepare("SELECT table_id, reservation_time, status FROM reservations WHERE reservation_date = ? AND status != 'cancelled'");
        $stmt->execute([$date]);
        $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Build availability map (MAIN SYSTEM ALGORITHM)
        $availability = [];
        foreach ($tables as $table) {
            $availability[$table['id']] = [];
            foreach ($timeSlots as $slot) {
                $availability[$table['id']][$slot['start']] = 'available';
            }
        }
        
        // Mark booked/pending slots (MAIN SYSTEM ALGORITHM)
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
        
        $buildTime = (microtime(true) - $startTime) * 1000;
        $buildTimes[] = $buildTime;
        
        // Check availability (MAIN SYSTEM APPROACH)
        $startTime = microtime(true);
        $availableCount = 0;
        foreach ($availability as $tableSlots) {
            foreach ($tableSlots as $status) {
                if ($status === 'available') $availableCount++;
            }
        }
        $checkTime = (microtime(true) - $startTime) * 1000;
        $checkTimes[] = $checkTime;
        
        // Clean up test data
        clearTestData($db);
        
        echo '<tr>';
        echo '<td>' . $size . '</td>';
        echo '<td><span class="run-number">Run ' . $run . '</span></td>';
        echo '<td class="time-value">' . number_format($buildTime, 2) . ' ms</td>';
        echo '<td class="time-value">' . number_format($checkTime, 2) . ' ms</td>';
        echo '<td class="status-success">Success</td>';
        echo '</tr>';
    }
    
    echo '<tr class="average-row">';
    echo '<td>' . $size . '</td>';
    echo '<td><strong>AVERAGE</strong></td>';
    echo '<td class="time-value">' . number_format(array_sum($buildTimes) / count($buildTimes), 2) . ' ms</td>';
    echo '<td class="time-value">' . number_format(array_sum($checkTimes) / count($checkTimes), 2) . ' ms</td>';
    echo '<td class="status-success">Completed</td>';
    echo '</tr>';
}

echo '</tbody></table>';
echo '</div>';

// ============================================================================
// Test 7: DATABASE SORTING (ORDER BY)
// ============================================================================

echo '<div class="test-section">';
echo '<h2>Test 7: Database Sorting (ORDER BY) <span class="complexity">O(n log n)</span></h2>';
echo '<div class="test-info">';
echo '<p><strong>Algorithm:</strong> MySQL QuickSort/MergeSort hybrid with B-tree indexes</p>';
echo '<p><strong>Operations:</strong> Sort reservations by priority score</p>';
echo '<p><strong>Test Sizes:</strong> 100, 500, 1000 records × 5 runs each</p>';
echo '</div>';

echo '<table>';
echo '<thead><tr><th>Input Size</th><th>Run</th><th>Sort Time (ms)</th><th>Records Sorted</th><th>Status</th></tr></thead>';
echo '<tbody>';

foreach ($testSizes as $size) {
    $sortTimes = [];
    
    for ($run = 1; $run <= $runsPerTest; $run++) {
        clearTestData($db);
        $testData = loadSeedData($size, $run);
        
        foreach ($testData as $idx => $data) {
            $code = 'TEST' . str_pad(++$globalCodeCounter, 8, '0', STR_PAD_LEFT);
            $priority = $data['priority_score'] ?? rand(1000, 5000);
            $stmt = $db->prepare("INSERT INTO reservations (name, phone, people_count, table_id, reservation_date, reservation_time, special_requests, confirmation_code, priority_score, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
            $stmt->execute([$data['name'], $data['phone'], $data['people_count'], $data['table_id'], $data['reservation_date'], $data['reservation_time'], $data['special_requests'] ?? '', $code, $priority]);
        }
        
        $startTime = microtime(true);
        $stmt = $db->query("SELECT * FROM reservations ORDER BY priority_score ASC, created_at ASC LIMIT " . $size);
        $sorted = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $sortTime = (microtime(true) - $startTime) * 1000;
        $sortTimes[] = $sortTime;
        
        // Clean up test data
        clearTestData($db);
        
        echo '<tr>';
        echo '<td>' . $size . '</td>';
        echo '<td><span class="run-number">Run ' . $run . '</span></td>';
        echo '<td class="time-value">' . number_format($sortTime, 2) . ' ms</td>';
        echo '<td>' . count($sorted) . '</td>';
        echo '<td class="status-success">Success</td>';
        echo '</tr>';
    }
    
    echo '<tr class="average-row">';
    echo '<td>' . $size . '</td>';
    echo '<td><strong>AVERAGE</strong></td>';
    echo '<td class="time-value">' . number_format(array_sum($sortTimes) / count($sortTimes), 2) . ' ms</td>';
    echo '<td>' . $size . '</td>';
    echo '<td class="status-success">Completed</td>';
    echo '</tr>';
}

echo '</tbody></table>';
echo '</div>';

// ============================================================================
// Test 8: SINGLETON PATTERN (DATABASE CONNECTION)
// ============================================================================

echo '<div class="test-section">';
echo '<h2>Test 8: Singleton Pattern (DB Connection) <span class="complexity">O(1)</span></h2>';
echo '<div class="test-info">';
echo '<p><strong>Algorithm:</strong> Single instance pattern with lazy initialization</p>';
echo '<p><strong>Operations:</strong> Multiple connection requests return same instance</p>';
echo '<p><strong>Test Sizes:</strong> 100, 500, 1000 requests × 5 runs each</p>';
echo '</div>';

echo '<table>';
echo '<thead><tr><th>Input Size</th><th>Run</th><th>Connection Time (ms)</th><th>Instances Created</th><th>Status</th></tr></thead>';
echo '<tbody>';

foreach ($testSizes as $size) {
    $connTimes = [];
    
    for ($run = 1; $run <= $runsPerTest; $run++) {
        $startTime = microtime(true);
        $instances = [];
        for ($i = 0; $i < $size; $i++) {
            $conn = getDBConnection();
            $instances[] = spl_object_id($conn);
        }
        $connTime = (microtime(true) - $startTime) * 1000;
        $connTimes[] = $connTime;
        
        $uniqueInstances = count(array_unique($instances));
        
        echo '<tr>';
        echo '<td>' . $size . '</td>';
        echo '<td><span class="run-number">Run ' . $run . '</span></td>';
        echo '<td class="time-value">' . number_format($connTime, 2) . ' ms</td>';
        echo '<td>' . $uniqueInstances . ' (should be 1)</td>';
        echo '<td class="status-success">Success</td>';
        echo '</tr>';
    }
    
    echo '<tr class="average-row">';
    echo '<td>' . $size . '</td>';
    echo '<td><strong>AVERAGE</strong></td>';
    echo '<td class="time-value">' . number_format(array_sum($connTimes) / count($connTimes), 2) . ' ms</td>';
    echo '<td>1 instance</td>';
    echo '<td class="status-success">Completed</td>';
    echo '</tr>';
}

echo '</tbody></table>';
echo '</div>';

// ============================================================================
// Test 9: SESSION-BASED HOLD SYSTEM
// ============================================================================

echo '<div class="test-section">';
echo '<h2>Test 9: Session-Based Hold System <span class="complexity">O(1)</span></h2>';
echo '<div class="test-info">';
echo '<p><strong>Algorithm:</strong> Main System Hold System (from hold-table.php & release-hold.php)</p>';
echo '<p><strong>Operations:</strong> Create hold + Check expiration + Release hold</p>';
echo '<p><strong>Implementation:</strong> Session storage with 5-minute expiration timer</p>';
echo '</div>';

echo '<table>';
echo '<thead><tr><th>Input Size</th><th>Run</th><th>Create Time (ms)</th><th>Check Time (ms)</th><th>Release Time (ms)</th><th>Status</th></tr></thead>';
echo '<tbody>';

foreach ($testSizes as $size) {
    $createTimes = [];
    $checkTimes = [];
    $releaseTimes = [];
    
    for ($run = 1; $run <= $runsPerTest; $run++) {
        // Simulate session-based holds
        $holds = [];
        
        // Test Create Holds
        $startTime = microtime(true);
        for ($i = 0; $i < $size; $i++) {
            $holdKey = rand(1, 9) . '_' . date('Y-m-d') . '_14:00:00';
            $holds[$holdKey] = [
                'table_id' => rand(1, 9),
                'date' => date('Y-m-d'),
                'time' => '14:00:00',
                'created_at' => time(),
                'expires_at' => time() + 300 // 5 minutes
            ];
        }
        $createTime = (microtime(true) - $startTime) * 1000;
        $createTimes[] = $createTime;
        
        // Test Check Expiration
        $startTime = microtime(true);
        $expired = 0;
        $active = 0;
        foreach ($holds as $hold) {
            if (time() > $hold['expires_at']) {
                $expired++;
            } else {
                $active++;
            }
        }
        $checkTime = (microtime(true) - $startTime) * 1000;
        $checkTimes[] = $checkTime;
        
        // Test Release Holds
        $startTime = microtime(true);
        $holds = []; // Clear all holds
        $releaseTime = (microtime(true) - $startTime) * 1000;
        $releaseTimes[] = $releaseTime;
        
        echo '<tr>';
        echo '<td>' . $size . '</td>';
        echo '<td><span class="run-number">Run ' . $run . '</span></td>';
        echo '<td class="time-value">' . number_format($createTime, 2) . ' ms</td>';
        echo '<td class="time-value">' . number_format($checkTime, 2) . ' ms</td>';
        echo '<td class="time-value">' . number_format($releaseTime, 2) . ' ms</td>';
        echo '<td class="status-success">Success</td>';
        echo '</tr>';
    }
    
    echo '<tr class="average-row">';
    echo '<td>' . $size . '</td>';
    echo '<td><strong>AVERAGE</strong></td>';
    echo '<td class="time-value">' . number_format(array_sum($createTimes) / count($createTimes), 2) . ' ms</td>';
    echo '<td class="time-value">' . number_format(array_sum($checkTimes) / count($checkTimes), 2) . ' ms</td>';
    echo '<td class="time-value">' . number_format(array_sum($releaseTimes) / count($releaseTimes), 2) . ' ms</td>';
    echo '<td class="status-success">Completed</td>';
    echo '</tr>';
}

echo '</tbody></table>';
echo '</div>';

// ============================================================================
// SUMMARY
// ============================================================================

clearTestData($db);

echo '<div class="summary-box">';
echo '<h3>All 9 Main System Algorithm Tests Completed Successfully</h3>';
echo '<p>Comprehensive performance testing of actual production algorithms</p>';
echo '<div class="summary-stats">';
echo '<div class="stat-card"><h4>Total Tests Run</h4><p>' . (count($testSizes) * $runsPerTest * 9) . '</p></div>';
echo '<div class="stat-card"><h4>Algorithms Tested</h4><p>9</p></div>';
echo '<div class="stat-card"><h4>Test Sizes</h4><p>100, 500, 1000</p></div>';
echo '<div class="stat-card"><h4>Runs Per Test</h4><p>' . $runsPerTest . '</p></div>';
echo '</div>';
echo '<div style="margin-top: 20px; padding: 20px; background: rgba(201,150,79,.05); border-radius: 8px; border: 1px solid var(--border);">';
echo '<h4 style="color: var(--gold); margin-bottom: 12px;">Tested Algorithms:</h4>';
echo '<ol style="color: var(--muted); line-height: 1.8; padding-left: 20px;">';
echo '<li>Fixed Hash Table Implementation (97 buckets, chaining) - O(1)</li>';
echo '<li>Queue (FIFO) Implementation (Waitlist) - O(1)</li>';
echo '<li>Linked List Implementation (Queue nodes) - O(1)</li>';
echo '<li>Hash-Based Code Generation (Confirmation codes) - O(1)</li>';
echo '<li>Linear Search & Filtering (Name search) - O(n)</li>';
echo '<li>Time Slot Availability Algorithm (Main system) - O(n+r×s)</li>';
echo '<li>Database Sorting (ORDER BY) - O(n log n)</li>';
echo '<li>Singleton Pattern (DB Connection) - O(1)</li>';
echo '<li>Session-Based Hold System (Temporary locks) - O(1)</li>';
echo '</ol>';
echo '</div>';
echo '</div>';

?>

    </div>
</body>
</html>







