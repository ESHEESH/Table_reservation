<?php
/**
 * Clear All Reservation Data
 * This script will clear all reservations, pre-orders, and hash table data
 */

require_once 'app/config.php';

try {
    $pdo = getDBConnection();
    
    // Disable foreign key checks temporarily
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    
    // Clear all tables
    echo "Clearing data...\n\n";
    
    // Clear pre-orders
    $result = $pdo->exec('TRUNCATE TABLE pre_orders');
    echo " Cleared pre_orders table\n";
    
    // Clear reservations
    $result = $pdo->exec('TRUNCATE TABLE reservations');
    echo " Cleared reservations table\n";
    
    // Clear hash table buckets (if exists)
    try {
        $result = $pdo->exec('TRUNCATE TABLE hash_table_buckets');
        echo " Cleared hash_table_buckets table\n";
    } catch (PDOException $e) {
        echo "ℹ hash_table_buckets table doesn't exist (skipped)\n";
    }
    
    // Clear VIP customers (optional - uncomment if you want to clear this too)
    // $result = $pdo->exec('TRUNCATE TABLE vip_customers');
    // echo "✓ Cleared vip_customers table\n";
    
    // Re-enable foreign key checks
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    
    echo "\n All reservation data cleared successfully!\n";
    echo "Database is now empty and ready for fresh data.\n";
    
} catch (PDOException $e) {
    echo " Error: " . $e->getMessage() . "\n";
    exit(1);
}
