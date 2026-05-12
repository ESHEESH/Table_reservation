<?php
/**
 * Update Prices Script
 * Updates table and menu prices to Philippine Peso amounts
 */
require_once 'config.php';

try {
    $pdo = getDBConnection();
    echo "Connected to database successfully!\n\n";
    
    // Update table prices (multiply by 10)
    echo "Updating table prices...\n";
    $pdo->exec("UPDATE tables SET price = price * 10");
    echo "✓ Table prices updated\n";
    
    // Update menu item prices (multiply by 10 for realistic PHP amounts)
    echo "\nUpdating menu item prices...\n";
    $updates = [
        "UPDATE menu_items SET price = 220.00 WHERE name = 'Salmon Nigiri'",
        "UPDATE menu_items SET price = 280.00 WHERE name = 'Tuna Nigiri'",
        "UPDATE menu_items SET price = 250.00 WHERE name = 'Ebi Nigiri'",
        "UPDATE menu_items SET price = 180.00 WHERE name = 'Tamago Nigiri'",
        "UPDATE menu_items SET price = 450.00 WHERE name = 'Tuna Sashimi'",
        "UPDATE menu_items SET price = 380.00 WHERE name = 'Salmon Sashimi'",
        "UPDATE menu_items SET price = 480.00 WHERE name = 'Yellowtail Sashimi'",
        "UPDATE menu_items SET price = 650.00 WHERE name = 'Mixed Sashimi'",
        "UPDATE menu_items SET price = 520.00 WHERE name = 'Dragon Roll'",
        "UPDATE menu_items SET price = 320.00 WHERE name = 'California Roll'",
        "UPDATE menu_items SET price = 350.00 WHERE name = 'Spicy Tuna Roll'",
        "UPDATE menu_items SET price = 480.00 WHERE name = 'Rainbow Roll'",
        "UPDATE menu_items SET price = 150.00 WHERE name = 'Edamame'",
        "UPDATE menu_items SET price = 120.00 WHERE name = 'Miso Soup'",
        "UPDATE menu_items SET price = 220.00 WHERE name = 'Gyoza'",
        "UPDATE menu_items SET price = 280.00 WHERE name = 'Tempura'",
        "UPDATE menu_items SET price = 250.00 WHERE name = 'Japanese Sake'",
        "UPDATE menu_items SET price = 80.00 WHERE name = 'Green Tea'",
        "UPDATE menu_items SET price = 120.00 WHERE name = 'Ramune'",
        "UPDATE menu_items SET price = 180.00 WHERE name = 'Matcha Latte'"
    ];
    
    foreach ($updates as $sql) {
        $pdo->exec($sql);
    }
    echo "✓ Menu item prices updated\n";
    
    // Show updated prices
    echo "\n=== Updated Table Prices ===\n";
    $tables = $pdo->query("SELECT table_number, capacity, price FROM tables ORDER BY table_number")->fetchAll();
    foreach ($tables as $t) {
        echo sprintf("%-5s | %d seats | ₱%.2f\n", $t['table_number'], $t['capacity'], $t['price']);
    }
    
    echo "\n=== Sample Menu Prices ===\n";
    $items = $pdo->query("SELECT name, price FROM menu_items ORDER BY price DESC LIMIT 10")->fetchAll();
    foreach ($items as $item) {
        echo sprintf("%-25s | ₱%.2f\n", $item['name'], $item['price']);
    }
    
    echo "\n✅ All prices updated successfully!\n";
    
} catch (PDOException $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
