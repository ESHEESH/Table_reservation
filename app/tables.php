<?php
/**
 * Sakura Sushi - Table Selection Page
 * Displays tables in a grid with slide-in detail panel
 * Uses QuickSort and Binary Search algorithms
 */
require_once 'config.php';

/**
 * QuickSort Algorithm - Sorts tables by capacity
 * Time Complexity: O(n log n) average case
 * Space Complexity: O(log n)
 */
function quickSortTables(&$arr, $low, $high, $key = 'capacity') {
    if ($low < $high) {
        $pi = partition($arr, $low, $high, $key);
        quickSortTables($arr, $low, $pi - 1, $key);
        quickSortTables($arr, $pi + 1, $high, $key);
    }
}

function partition(&$arr, $low, $high, $key) {
    $pivot = $arr[$high][$key];
    $i = $low - 1;
    for ($j = $low; $j < $high; $j++) {
        if ($arr[$j][$key] <= $pivot) {
            $i++;
            $temp = $arr[$i];
            $arr[$i] = $arr[$j];
            $arr[$j] = $temp;
        }
    }
    $temp = $arr[$i + 1];
    $arr[$i + 1] = $arr[$high];
    $arr[$high] = $temp;
    return $i + 1;
}

/**
 * Binary Search - Find table by number
 * Time Complexity: O(log n)
 * Requires sorted array
 */
function binarySearchTable($arr, $target, $key = 'table_number') {
    $left = 0;
    $right = count($arr) - 1;
    while ($left <= $right) {
        $mid = floor(($left + $right) / 2);
        if ($arr[$mid][$key] === $target) {
            return $mid;
        }
        if ($arr[$mid][$key] < $target) {
            $left = $mid + 1;
        } else {
            $right = $mid - 1;
        }
    }
    return -1;
}

// Fetch tables from database
$pdo = getDBConnection();
$stmt = $pdo->query("SELECT * FROM tables ORDER BY table_number");
$tables = $stmt->fetchAll();

// Sort tables by capacity using QuickSort
quickSortTables($tables, 0, count($tables) - 1, 'capacity');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Table - Sakura Sushi</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="page-transition">
    <!-- Navigation -->
    <nav class="nav">
        <button class="nav-back">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M19 12H5M12 19l-7-7 7-7"/>
            </svg>
            Back to Home
        </button>
        <div class="nav-logo">Sakura Sushi</div>
        <div></div>
    </nav>

    <!-- Page Header -->
    <header class="page-header">
        <h1 class="page-title">Select Your Table</h1>
        <p class="page-subtitle">Click on an available table to view details and reserve</p>
        
        <!-- Legend -->
        <div class="table-legend">
            <div class="legend-item">
                <div class="legend-dot" style="background: var(--color-success);"></div>
                Available
            </div>
            <div class="legend-item">
                <div class="legend-dot" style="background: var(--color-error);"></div>
                Occupied
            </div>
            <div class="legend-item">
                <div class="legend-dot" style="background: var(--color-warning);"></div>
                Selected
            </div>
        </div>
    </header>

    <!-- Table Grid -->
    <div class="table-grid-container">
        <div class="table-grid">
            <?php foreach ($tables as $table): ?>
                <div class="table-card <?php echo $table['status'] === 'occupied' ? 'occupied' : ''; ?>" 
                     data-id="<?php echo $table['id']; ?>"
                     data-table="<?php echo htmlspecialchars($table['table_number']); ?>"
                     data-capacity="<?php echo $table['capacity']; ?>"
                     data-price="<?php echo $table['price']; ?>"
                     data-status="<?php echo $table['status']; ?>"
                     data-features="<?php echo htmlspecialchars($table['features'] ?? ''); ?>">
                    <div class="table-status <?php echo $table['status'] === 'occupied' ? 'occupied' : 'available'; ?>"></div>
                    <div class="table-number"><?php echo htmlspecialchars($table['table_number']); ?></div>
                    <div class="table-capacity">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:inline;vertical-align:middle;margin-right:4px;">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                        <?php echo $table['capacity']; ?> seats
                    </div>
                    <div class="table-price">$<?php echo number_format($table['price'], 2); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Slide-in Panel -->
    <div class="slide-overlay"></div>
    <div class="slide-panel">
        <button class="slide-close">&times;</button>
        <h2 class="panel-title">Table Details</h2>
        
        <div class="panel-section">
            <div class="panel-label">Table Number</div>
            <div class="panel-value panel-table-num">-</div>
        </div>
        
        <div class="panel-section">
            <div class="panel-label">Capacity</div>
            <div class="panel-value panel-capacity">-</div>
        </div>
        
        <div class="panel-section">
            <div class="panel-label">Reservation Fee</div>
            <div class="panel-value panel-price">-</div>
        </div>
        
        <div class="panel-section">
            <div class="panel-label">Status</div>
            <div class="panel-value panel-status">-</div>
        </div>
        
        <div class="panel-section">
            <div class="panel-label">Features</div>
            <ul class="panel-features">
                <li>Premium seating</li>
            </ul>
        </div>
        
        <div class="panel-buttons">
            <a href="reservation.php" class="btn btn-primary btn-full btn-reserve-table">Reserve This Table</a>
            <a href="menu.php" class="btn btn-secondary btn-full btn-preorder">Pre-Order Food</a>
        </div>
    </div>

    <script src="assets/js/main.js"></script>
</body>
</html>
