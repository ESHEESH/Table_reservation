<?php
/**
 * Emergency cleanup script to remove all test data
 * Run this to clean up test reservations from the database
 */

require_once 'app/config.php';

$db = getDBConnection();

echo "<h2>Cleaning up test data...</h2>";

// First, let's see what we have
$total = $db->query("SELECT COUNT(*) FROM reservations")->fetchColumn();
echo "Total reservations before cleanup: <strong>$total</strong><br><br>";

// Strategy: Keep only reservations with your real phone number pattern
// Delete everything else that looks like test data

echo "<h3>Deleting test data...</h3>";

// Delete by phone patterns
$count1 = $db->exec("DELETE FROM reservations WHERE phone LIKE '099%'");
echo "✅ Deleted $count1 records with phone starting with 099<br>";

// Delete by CART confirmation codes
$count2 = $db->exec("DELETE FROM reservations WHERE confirmation_code LIKE 'CART%'");
echo "✅ Deleted $count2 records with CART codes<br>";

// Delete records with 8-character hex confirmation codes (test pattern)
$count3 = $db->exec("DELETE FROM reservations WHERE LENGTH(confirmation_code) = 8 AND confirmation_code NOT LIKE 'SKR-%'");
echo "✅ Deleted $count3 records with 8-char hash codes<br>";

// Delete records with status 'pending' and recent dates (likely test data)
$count4 = $db->exec("DELETE FROM reservations WHERE status = 'pending' AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
echo "✅ Deleted $count4 recent pending records<br>";

// Clean VIP test data
$vipCount = $db->exec("DELETE FROM vip_customers WHERE phone LIKE '099%'");
echo "✅ Deleted $vipCount VIP test records<br>";

$totalDeleted = $count1 + $count2 + $count3 + $count4;

$remaining = $db->query("SELECT COUNT(*) FROM reservations")->fetchColumn();

echo "<br><hr>";
echo "<h3>Summary</h3>";
echo "Total deleted: <strong style='color: red;'>$totalDeleted</strong> reservations<br>";
echo "Remaining: <strong style='color: green;'>$remaining</strong> reservations<br>";
echo "<br>✅ Cleanup complete!<br>";
echo "<br><a href='app/admin/admin.php' style='padding: 10px 20px; background: #c9964f; color: #000; text-decoration: none; border-radius: 5px;'>Go to Admin Panel</a>";
?>
