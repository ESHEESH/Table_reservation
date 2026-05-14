<?php
/**
 * Table Hold System - Temporary Reservation Lock
 * 
 * Creates a temporary "hold" on a table for 5 minutes while user fills form.
 * Prevents double booking and bot abuse.
 * 
 * ALGORITHM: Session-based temporary lock with expiration
 * - Creates session with table_id, time, date, and expiration
 * - Marks table as "held" in availability check
 * - Auto-expires after 5 minutes
 * - Released on form submission or timeout
 */

require_once '../config.php';
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$tableId = isset($_POST['table_id']) ? (int)$_POST['table_id'] : 0;
$date = isset($_POST['date']) ? $_POST['date'] : '';
$time = isset($_POST['time']) ? $_POST['time'] : '';

if (!$tableId || !$date || !$time) {
    echo json_encode(['success' => false, 'error' => 'Missing parameters']);
    exit;
}

// Check if table is already booked or held by someone else
$pdo = getDBConnection();

// Check for existing confirmed/pending reservations
$stmt = $pdo->prepare("
    SELECT id FROM reservations 
    WHERE table_id = ? 
    AND reservation_date = ? 
    AND reservation_time = ? 
    AND status IN ('confirmed', 'pending')
");
$stmt->execute([$tableId, $date, $time]);

if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'error' => 'Table already booked']);
    exit;
}

// Check if table is held by another session
if (isset($_SESSION['table_holds'])) {
    foreach ($_SESSION['table_holds'] as $sessionId => $holds) {
        foreach ($holds as $hold) {
            if ($hold['table_id'] == $tableId && 
                $hold['date'] == $date && 
                $hold['time'] == $time &&
                $hold['expires_at'] > time() &&
                $sessionId !== session_id()) {
                echo json_encode(['success' => false, 'error' => 'Table is being held by another user']);
                exit;
            }
        }
    }
}

// Create hold
$holdKey = $tableId . '_' . $date . '_' . $time;
$expiresAt = time() + (5 * 60); // 5 minutes from now

if (!isset($_SESSION['table_holds'])) {
    $_SESSION['table_holds'] = [];
}

if (!isset($_SESSION['table_holds'][session_id()])) {
    $_SESSION['table_holds'][session_id()] = [];
}

$_SESSION['table_holds'][session_id()][$holdKey] = [
    'table_id' => $tableId,
    'date' => $date,
    'time' => $time,
    'created_at' => time(),
    'expires_at' => $expiresAt
];

// Store in session for easy access
$_SESSION['current_hold'] = [
    'table_id' => $tableId,
    'date' => $date,
    'time' => $time,
    'expires_at' => $expiresAt
];

echo json_encode([
    'success' => true,
    'expires_at' => $expiresAt,
    'expires_in' => 300 // 5 minutes in seconds
]);
