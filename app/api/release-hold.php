<?php
/**
 * Release Table Hold
 * 
 * Releases a temporary table hold when:
 * - User navigates away
 * - Timer expires
 * - User cancels
 */

session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Clear current hold
if (isset($_SESSION['current_hold'])) {
    $hold = $_SESSION['current_hold'];
    $holdKey = $hold['table_id'] . '_' . $hold['date'] . '_' . $hold['time'];
    
    if (isset($_SESSION['table_holds'][session_id()][$holdKey])) {
        unset($_SESSION['table_holds'][session_id()][$holdKey]);
    }
    
    unset($_SESSION['current_hold']);
}

echo json_encode(['success' => true]);
