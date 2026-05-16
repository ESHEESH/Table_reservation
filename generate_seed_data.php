<?php
/**
 * Generate seed data files for algorithm testing
 * Creates JSON files with 100, 500, and 1000 test records
 */

function generateSeedData($count, $filename) {
    $names = [
        'John Doe', 'Jane Smith', 'Mike Johnson', 'Sarah Williams', 'David Brown',
        'Emily Davis', 'Chris Wilson', 'Amanda Taylor', 'James Anderson', 'Lisa Martinez',
        'Robert Garcia', 'Maria Rodriguez', 'William Martinez', 'Jennifer Lopez', 'Michael Lee',
        'Jessica White', 'Daniel Harris', 'Ashley Clark', 'Matthew Lewis', 'Stephanie Walker'
    ];
    
    $timeSlots = ['14:00:00', '17:00:00', '20:00:00'];
    $data = [];
    
    for ($i = 0; $i < $count; $i++) {
        $data[] = [
            'name' => $names[array_rand($names)] . ' #' . ($i + 1),
            'phone' => '09' . str_pad(rand(100000000, 999999999), 9, '0', STR_PAD_LEFT),
            'email' => 'test' . ($i + 1) . '@example.com',
            'guest_count' => rand(2, 8),
            'table_id' => rand(1, 10),
            'reservation_date' => date('Y-m-d', strtotime('+' . rand(1, 30) . ' days')),
            'reservation_time' => $timeSlots[array_rand($timeSlots)],
            'special_requests' => 'Test reservation #' . ($i + 1)
        ];
    }
    
    file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT));
    echo "✓ Generated $filename with $count records\n";
}

// Generate seed files
generateSeedData(100, 'seed_test_100.json');
generateSeedData(500, 'seed_test_500.json');
generateSeedData(1000, 'seed_test_1000.json');

echo "\n✅ All seed data files generated successfully!\n";
echo "Files created:\n";
echo "  - seed_test_100.json (100 records)\n";
echo "  - seed_test_500.json (500 records)\n";
echo "  - seed_test_1000.json (1000 records)\n";
?>
