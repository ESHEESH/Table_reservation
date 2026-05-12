<?php
/**
 * Database Migration Script
 * Adds new columns to existing tables without losing data
 */
require_once 'config.php';

try {
    $pdo = getDBConnection();
    echo "Connected to database successfully!\n\n";
    
    // Add columns to menu_items table
    echo "Adding columns to menu_items table...\n";
    
    try {
        $pdo->exec("ALTER TABLE menu_items ADD COLUMN stock INT(11) DEFAULT 100 AFTER image");
        echo "✓ Added 'stock' column\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "- 'stock' column already exists\n";
        } else {
            throw $e;
        }
    }
    
    try {
        $pdo->exec("ALTER TABLE menu_items ADD COLUMN available TINYINT(1) DEFAULT 1 AFTER stock");
        echo "✓ Added 'available' column\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "- 'available' column already exists\n";
        } else {
            throw $e;
        }
    }
    
    // Add columns to tables table
    echo "\nAdding columns to tables table...\n";
    
    try {
        $pdo->exec("ALTER TABLE tables ADD COLUMN location VARCHAR(50) DEFAULT 'Main Hall' AFTER features");
        echo "✓ Added 'location' column\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "- 'location' column already exists\n";
        } else {
            throw $e;
        }
    }
    
    try {
        $pdo->exec("ALTER TABLE tables ADD COLUMN table_type ENUM('standard','booth','counter','vip') DEFAULT 'standard' AFTER location");
        echo "✓ Added 'table_type' column\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "- 'table_type' column already exists\n";
        } else {
            throw $e;
        }
    }
    
    try {
        $pdo->exec("ALTER TABLE tables ADD COLUMN is_smoking TINYINT(1) DEFAULT 0 AFTER table_type");
        echo "✓ Added 'is_smoking' column\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "- 'is_smoking' column already exists\n";
        } else {
            throw $e;
        }
    }
    
    // Update existing menu items with default stock values
    echo "\nUpdating existing menu items with stock values...\n";
    $pdo->exec("UPDATE menu_items SET stock = 100 WHERE stock IS NULL OR stock = 0");
    $pdo->exec("UPDATE menu_items SET available = 1 WHERE available IS NULL");
    echo "✓ Updated menu items\n";
    
    // Update existing tables with default values
    echo "\nUpdating existing tables with default values...\n";
    $pdo->exec("UPDATE tables SET location = 'Main Hall' WHERE location IS NULL OR location = ''");
    $pdo->exec("UPDATE tables SET table_type = 'standard' WHERE table_type IS NULL OR table_type = ''");
    $pdo->exec("UPDATE tables SET is_smoking = 0 WHERE is_smoking IS NULL");
    echo "✓ Updated tables\n";
    
    echo "\n✅ Migration completed successfully!\n";
    echo "\nYou can now refresh your admin panel.\n";
    
} catch (PDOException $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
