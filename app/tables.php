<?php
/**
 * Sakura Sushi - Table Reservation with Calendar & Time Slots
 * Shows availability grid like pickleball court booking
 */
require_once 'config.php';

$pdo = getDBConnection();
$selectedDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Validate date
$today = date('Y-m-d');
$maxDate = date('Y-m-d', strtotime('+30 days'));
if ($selectedDate < $today || $selectedDate > $maxDate) {
    $selectedDate = $today;
}

// Fetch all tables
$stmt = $pdo->query("SELECT * FROM tables ORDER BY table_number");
$tables = $stmt->fetchAll();

// Time slots (3 hours each, operating 2 PM - 11 PM)
$timeSlots = [
    ['start' => '14:00:00', 'end' => '17:00:00', 'label' => '2:00 PM - 5:00 PM'],
    ['start' => '17:00:00', 'end' => '20:00:00', 'label' => '5:00 PM - 8:00 PM'],
    ['start' => '20:00:00', 'end' => '23:00:00', 'label' => '8:00 PM - 11:00 PM']
];

// Get all reservations for selected date
$stmt = $pdo->prepare("
    SELECT table_id, reservation_time, status 
    FROM reservations 
    WHERE reservation_date = ? AND status != 'cancelled'
");
$stmt->execute([$selectedDate]);
$reservations = $stmt->fetchAll();

// Build availability map
$availability = [];
foreach ($tables as $table) {
    $availability[$table['id']] = [];
    foreach ($timeSlots as $slot) {
        $availability[$table['id']][$slot['start']] = 'available';
    }
}

// Mark booked/pending slots
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book a Table - Sakura Sushi</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --bg: #0A0A0F;
            --surface: #14141B;
            --border: rgba(201,150,79,.16);
            --cream: #FDF6EC;
            --gold: #C9964F;
            --green: #27AE60;
            --red: #E74C3C;
            --yellow: #F1C40F;
            --blue: #3498DB;
            --gray: #95A5A6;
        }
        body {
            background: var(--bg);
            color: var(--cream);
            font-family: 'Inter', sans-serif;
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            margin-bottom: 40px;
        }
        .header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            color: var(--gold);
            margin-bottom: 10px;
        }
        .header p {
            color: rgba(253,246,236,.6);
            font-size: 1rem;
        }
        
        /* Date Picker */
        .date-picker {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }
        .date-display {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.3rem;
            font-weight: 600;
        }
        .date-display svg {
            color: var(--gold);
        }
        .date-controls {
            display: flex;
            gap: 10px;
        }
        .date-btn {
            background: rgba(201,150,79,.1);
            border: 1px solid var(--border);
            color: var(--cream);
            padding: 10px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            transition: all .2s;
        }
        .date-btn:hover {
            background: rgba(201,150,79,.2);
            border-color: var(--gold);
        }
        
        /* Availability Grid */
        .availability-grid {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 24px;
            overflow-x: auto;
        }
        .grid-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }
        .grid-table th {
            padding: 16px 12px;
            text-align: center;
            font-size: 0.9rem;
            font-weight: 600;
            border-bottom: 2px solid var(--border);
        }
        .grid-table th:first-child {
            text-align: left;
            width: 180px;
        }
        .table-header {
            font-size: 1.1rem;
            color: var(--gold);
        }
        .table-subtext {
            font-size: 0.75rem;
            color: rgba(253,246,236,.5);
            font-weight: 400;
            display: block;
            margin-top: 4px;
        }
        .grid-table td {
            padding: 12px;
            text-align: center;
            border-bottom: 1px solid rgba(201,150,79,.08);
        }
        .grid-table td:first-child {
            text-align: left;
            font-weight: 500;
        }
        .time-label {
            font-size: 0.95rem;
            color: var(--cream);
        }
        .price-label {
            font-size: 0.85rem;
            color: var(--gold);
            display: block;
            margin-top: 4px;
        }
        
        /* Slot Buttons */
        .slot {
            padding: 12px 20px;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: all .2s;
            border: 1px solid;
            display: inline-block;
            min-width: 100px;
            min-height: 44px;
            position: relative;
        }
        .slot.available {
            background: rgba(39,174,96,.05);
            border-color: rgba(39,174,96,.2);
            color: transparent;
        }
        .slot.available::after {
            content: 'Select';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: var(--green);
            opacity: 0;
            transition: opacity .2s;
        }
        .slot.available:hover {
            background: rgba(39,174,96,.15);
            border-color: var(--green);
            transform: translateY(-2px);
        }
        .slot.available:hover::after {
            opacity: 1;
        }
        .slot.booked {
            background: rgba(231,76,60,.1);
            border-color: rgba(231,76,60,.3);
            color: var(--red);
            cursor: not-allowed;
        }
        .slot.pending {
            background: rgba(241,196,15,.1);
            border-color: rgba(241,196,15,.3);
            color: #F1C40F;
            cursor: not-allowed;
        }
        .slot.selected {
            background: rgba(201,150,79,.2);
            border-color: var(--gold);
            color: var(--gold);
        }
        
        /* Legend */
        .legend {
            display: flex;
            gap: 24px;
            justify-content: center;
            margin-top: 24px;
            flex-wrap: wrap;
        }
        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.85rem;
        }
        .legend-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,.8);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .modal.active {
            display: flex;
        }
        .modal-content {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 32px;
            max-width: 500px;
            width: 100%;
        }
        .modal-header {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            color: var(--gold);
            margin-bottom: 24px;
        }
        .modal-section {
            margin-bottom: 20px;
        }
        .modal-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: rgba(253,246,236,.5);
            margin-bottom: 8px;
        }
        .modal-value {
            font-size: 1.1rem;
            color: var(--cream);
        }
        .modal-buttons {
            display: flex;
            gap: 12px;
            margin-top: 32px;
        }
        .btn {
            padding: 14px 24px;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all .2s;
            border: none;
            flex: 1;
            text-decoration: none;
            text-align: center;
            display: inline-block;
        }
        .btn-primary {
            background: var(--gold);
            color: var(--bg);
        }
        .btn-primary:hover {
            opacity: .9;
            transform: translateY(-2px);
        }
        .btn-secondary {
            background: rgba(255,255,255,.05);
            color: var(--cream);
            border: 1px solid var(--border);
        }
        .btn-secondary:hover {
            background: rgba(255,255,255,.08);
        }
        
        @media (max-width: 768px) {
            .date-picker {
                flex-direction: column;
                align-items: stretch;
            }
            .header h1 {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Book a Table</h1>
            <p>Select a date and available time slot for your reservation</p>
        </div>
        
        <!-- Date Picker -->
        <div class="date-picker">
            <div class="date-display">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2"/>
                    <line x1="16" y1="2" x2="16" y2="6"/>
                    <line x1="8" y1="2" x2="8" y2="6"/>
                    <line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
                <span><?php echo date('D, M j, Y', strtotime($selectedDate)); ?></span>
            </div>
            <div class="date-controls">
                <button class="date-btn" onclick="changeDate(-1)">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:inline;vertical-align:middle;">
                        <polyline points="15 18 9 12 15 6"/>
                    </svg>
                    Previous
                </button>
                <input type="date" id="datePicker" value="<?php echo $selectedDate; ?>" 
                       min="<?php echo $today; ?>" max="<?php echo $maxDate; ?>"
                       onchange="window.location.href='tables.php?date='+this.value"
                       style="padding:10px;border-radius:8px;border:1px solid var(--border);background:var(--bg);color:var(--cream);">
                <button class="date-btn" onclick="changeDate(1)">
                    Next
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:inline;vertical-align:middle;">
                        <polyline points="9 18 15 12 9 6"/>
                    </svg>
                </button>
            </div>
        </div>
        
        <!-- Availability Grid -->
        <div class="availability-grid">
            <table class="grid-table">
                <thead>
                    <tr>
                        <th>Time Slot</th>
                        <?php foreach ($tables as $table): ?>
                            <th>
                                <div class="table-header"><?php echo htmlspecialchars($table['table_number']); ?></div>
                                <span class="table-subtext">
                                    <?php echo $table['capacity']; ?> seats<br>
                                    ₱<?php echo number_format($table['price'], 0); ?>
                                </span>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($timeSlots as $slot): ?>
                        <tr>
                            <td>
                                <div class="time-label"><?php echo $slot['label']; ?></div>
                                <span class="price-label">2.5 hours</span>
                            </td>
                            <?php foreach ($tables as $table): ?>
                                <td>
                                    <?php 
                                    $status = $availability[$table['id']][$slot['start']];
                                    $disabled = ($status === 'booked' || $status === 'pending') ? 'disabled' : '';
                                    $label = $status === 'booked' ? 'Booked' : ($status === 'pending' ? 'Pending' : '');
                                    ?>
                                    <button class="slot <?php echo $status; ?>" 
                                            <?php echo $disabled; ?>
                                            onclick="selectSlot(<?php echo $table['id']; ?>, '<?php echo $slot['start']; ?>', '<?php echo $slot['label']; ?>', '<?php echo htmlspecialchars($table['table_number']); ?>', <?php echo $table['capacity']; ?>, <?php echo $table['price']; ?>)">
                                        <?php echo $label; ?>
                                    </button>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="legend">
                <div class="legend-item">
                    <div class="legend-dot" style="background: var(--green);"></div>
                    <span>Available</span>
                </div>
                <div class="legend-item">
                    <div class="legend-dot" style="background: var(--yellow);"></div>
                    <span>Pending</span>
                </div>
                <div class="legend-item">
                    <div class="legend-dot" style="background: var(--red);"></div>
                    <span>Booked</span>
                </div>
                <div class="legend-item">
                    <div class="legend-dot" style="background: var(--gold);"></div>
                    <span>Selected</span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Confirmation Modal -->
    <div class="modal" id="confirmModal">
        <div class="modal-content">
            <h2 class="modal-header">Confirm Reservation</h2>
            
            <div class="modal-section">
                <div class="modal-label">Table</div>
                <div class="modal-value" id="modalTable">-</div>
            </div>
            
            <div class="modal-section">
                <div class="modal-label">Date</div>
                <div class="modal-value"><?php echo date('l, F j, Y', strtotime($selectedDate)); ?></div>
            </div>
            
            <div class="modal-section">
                <div class="modal-label">Time Slot</div>
                <div class="modal-value" id="modalTime">-</div>
            </div>
            
            <div class="modal-section">
                <div class="modal-label">Reservation Fee</div>
                <div class="modal-value" id="modalPrice">-</div>
            </div>
            
            <div class="modal-buttons">
                <button class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <a href="#" id="confirmBtn" class="btn btn-primary">Continue to Details</a>
            </div>
        </div>
    </div>
    
    <script>
        const selectedDate = '<?php echo $selectedDate; ?>';
        let selectedSlot = null;
        
        function changeDate(days) {
            const current = new Date(selectedDate);
            current.setDate(current.getDate() + days);
            const newDate = current.toISOString().split('T')[0];
            window.location.href = 'tables.php?date=' + newDate;
        }
        
        function selectSlot(tableId, time, timeLabel, tableNum, capacity, price) {
            // Remove previous selection
            document.querySelectorAll('.slot.selected').forEach(el => {
                if (el.classList.contains('available')) {
                    el.textContent = '';
                }
                el.classList.remove('selected');
            });
            
            // Mark as selected
            event.target.classList.add('selected');
            event.target.textContent = 'Selected';
            
            // Update modal
            document.getElementById('modalTable').textContent = tableNum + ' (' + capacity + ' seats)';
            document.getElementById('modalTime').textContent = timeLabel;
            document.getElementById('modalPrice').textContent = '₱' + price.toLocaleString();
            document.getElementById('confirmBtn').href = 'reservation.php?table_id=' + tableId + '&date=' + selectedDate + '&time=' + time;
            
            // Show modal
            document.getElementById('confirmModal').classList.add('active');
        }
        
        function closeModal() {
            document.getElementById('confirmModal').classList.remove('active');
            // Reset selection
            document.querySelectorAll('.slot.selected').forEach(el => {
                el.classList.remove('selected');
                if (el.classList.contains('available')) {
                    el.textContent = '';
                }
            });
        }
        
        // Close modal on outside click
        document.getElementById('confirmModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html>
