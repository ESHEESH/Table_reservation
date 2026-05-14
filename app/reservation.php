<?php
/**
 * ============================================================================
 * RESERVATION FORM - ALGORITHM OVERVIEW
 * ============================================================================
 * 
 * PRIMARY ALGORITHMS:
 * 1. Form State Persistence (O(1)) - LocalStorage save/restore
 * 2. Cart Integration (O(1)) - Pre-order data retrieval
 * 3. Input Validation (O(1)) - Client-side form validation
 * 
 * DATA STRUCTURES:
 * - LocalStorage (Key-Value Store) for form persistence
 * - JSON for cart data serialization
 * - DOM Tree for dynamic UI updates
 * 
 * COMPLEXITY ANALYSIS:
 * - saveFormData(): O(1) - Constant time localStorage write
 * - restoreFormData(): O(1) - Constant time localStorage read
 * - Cart Display: O(n) - Linear iteration over n cart items
 * - Form Validation: O(1) - Fixed number of field checks
 * 
 * WORKFLOW:
 * 1. User selects table from tables.php (required)
 * 2. Form pre-fills with table details and selected time
 * 3. Optional: Navigate to menu.php for pre-order (form data saved)
 * 4. Return from menu: Form data restored from localStorage
 * 5. Upload payment receipt and submit
 * 6. POST to api/create-reservation.php with multipart/form-data
 * 
 * FORM PERSISTENCE:
 * - Saves on every input change to prevent data loss
 * - Includes file upload (converted to base64)
 * - Cleared after successful submission
 * 
 * INTEGRATION:
 * - Receives: table_id, date, time from tables.php
 * - Sends to: api/create-reservation.php (creates pending reservation)
 * - Links to: menu.php (optional pre-order with state preservation)
 * 
 * ============================================================================
 */

/**
 * Sakura Sushi - Reservation Form
 * Customer details, payment QR code, receipt upload
 */
require_once 'config.php';

$tableId = isset($_GET['table_id']) ? (int)$_GET['table_id'] : 0;
$selectedDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$selectedTime = isset($_GET['time']) ? $_GET['time'] : '';

// Redirect to tables page if no table selected
if ($tableId === 0) {
    header('Location: tables.php');
    exit;
}

// Get table details
$pdo = getDBConnection();
$stmt = $pdo->prepare("SELECT * FROM tables WHERE id = ?");
$stmt->execute([$tableId]);
$table = $stmt->fetch();

// If table not found, redirect to tables page
if (!$table) {
    header('Location: tables.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Reservation - Sakura Sushi</title>
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
            Back
        </button>
        <div class="nav-logo">Sakura Sushi</div>
        <div></div>
    </nav>

    <!-- Page Header -->
    <header class="page-header">
        <h1 class="page-title">Complete Your Reservation</h1>
        <p class="page-subtitle">Fill in your details and complete payment to confirm</p>
    </header>

    <!-- Two Column Layout -->
    <div class="two-column">
        <!-- Left: Form -->
        <div class="column-left">
            <h2 class="section-title">Your Details</h2>
            
            <?php if ($table): ?>
            <div class="info-card">
                <div class="info-card-label">Selected Table</div>
                <div class="info-card-value">
                    Table <?php echo htmlspecialchars($table['table_number']); ?> 
                    &middot; <?php echo $table['capacity']; ?> seats 
                    &middot; ₱<?php echo number_format($table['price'], 2); ?>
                </div>
            </div>
            <?php endif; ?>
            
            <form id="reservation-form" action="api/create-reservation.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="table_id" value="<?php echo $tableId; ?>">
                <input type="hidden" name="reservation_time" id="reservation_time" value="">
                
                <div class="form-group">
                    <label class="form-label" for="name">Full Name *</label>
                    <input type="text" id="name" name="name" class="form-input" placeholder="John Doe" required>
                    <span class="form-error">Please enter your full name</span>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="phone">Phone Number *</label>
                    <input type="tel" id="phone" name="phone" class="form-input" placeholder="+1 (555) 000-0000" required>
                    <span class="form-error">Please enter a valid phone number</span>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="people_count">Number of Guests *</label>
                    <select id="people_count" name="people_count" class="form-select" required>
                        <option value="">Select number of guests</option>
                        <?php for ($i = 1; $i <= 10; $i++): ?>
                            <option value="<?php echo $i; ?>"><?php echo $i; ?> <?php echo $i === 1 ? 'person' : 'people'; ?></option>
                        <?php endfor; ?>
                    </select>
                    <span class="form-error">Please select number of guests</span>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="reservation_date">Reservation Date *</label>
                    <input type="date" id="reservation_date" name="reservation_date" class="form-input" 
                           value="<?php echo htmlspecialchars($selectedDate); ?>"
                           min="<?php echo date('Y-m-d'); ?>" 
                           max="<?php echo date('Y-m-d', strtotime('+30 days')); ?>" required readonly>
                    <span class="form-error">Please select a date</span>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Reservation Time *</label>
                    <input type="hidden" name="reservation_time" id="reservation_time_input" value="<?php echo htmlspecialchars($selectedTime); ?>">
                    <div class="info-card" style="padding: 16px; text-align: center;">
                        <div style="font-size: 1.2rem; color: var(--color-accent); font-weight: 600;">
                            <?php 
                            if ($selectedTime) {
                                $timeObj = DateTime::createFromFormat('H:i:s', $selectedTime);
                                $endTime = clone $timeObj;
                                $endTime->modify('+2 hours 30 minutes');
                                echo $timeObj->format('g:i A') . ' - ' . $endTime->format('g:i A');
                            } else {
                                echo 'No time selected';
                            }
                            ?>
                        </div>
                        <div style="font-size: 0.85rem; color: rgba(253,246,236,.6); margin-top: 4px;">
                            2.5 hour reservation
                        </div>
                    </div>
                    <span class="form-error" id="time-error">Please select a time</span>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="special_requests">Special Requests (Optional)</label>
                    <textarea id="special_requests" name="special_requests" class="form-textarea" 
                              placeholder="Any dietary restrictions, special occasions, seating preferences..."></textarea>
                </div>
                
                <!-- Cart Summary (if pre-order exists) -->
                <div id="cart-summary" style="display: none;">
                    <h3 style="font-size: 18px; margin-bottom: 16px;">Pre-Order Summary</h3>
                    <div id="cart-items-list"></div>
                </div>
                
                <!-- Add Pre-Order Button -->
                <div style="margin-top: 16px; padding: 12px; background: rgba(201,150,79,.05); border: 1px solid rgba(201,150,79,.2); border-radius: 12px; text-align: center;">
                    <p style="font-size: 14px; color: rgba(253,246,236,.7); margin-bottom: 8px;">Want to pre-order food?</p>
                    <a href="menu.php?table_id=<?php echo $tableId; ?><?php echo $selectedDate ? '&date='.urlencode($selectedDate) : ''; ?><?php echo $selectedTime ? '&time='.urlencode($selectedTime) : ''; ?>&from_reservation=1" class="btn btn-secondary" id="preorder-link" style="display: inline-block; text-decoration: none;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 6px;">
                            <path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2M7 2v20M21 15V2v0a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3zm0 0v7"/>
                        </svg>
                        Add Pre-Order Items
                    </a>
                </div>
                
                <button type="submit" class="btn btn-primary btn-full" style="margin-top: 16px;">
                    Confirm Reservation
                </button>
            </form>
        </div>
        
        <!-- Right: Payment -->
        <div class="column-right">
            <h2 class="section-title">Payment</h2>
            
            <div class="glass-card" style="text-align: center;">
                <div class="panel-label">Scan to Pay</div>
                <div class="qr-display">
                    <!-- QR Code for payment - Using a placeholder that represents a payment QR -->
                    <svg width="200" height="200" viewBox="0 0 200 200">
                        <rect width="200" height="200" fill="white"/>
                        <!-- QR Code pattern simulation -->
                        <rect x="10" y="10" width="50" height="50" fill="#0A0A0F"/>
                        <rect x="15" y="15" width="40" height="40" fill="white"/>
                        <rect x="20" y="20" width="30" height="30" fill="#0A0A0F"/>
                        <rect x="140" y="10" width="50" height="50" fill="#0A0A0F"/>
                        <rect x="145" y="15" width="40" height="40" fill="white"/>
                        <rect x="150" y="20" width="30" height="30" fill="#0A0A0F"/>
                        <rect x="10" y="140" width="50" height="50" fill="#0A0A0F"/>
                        <rect x="15" y="145" width="40" height="40" fill="white"/>
                        <rect x="20" y="150" width="30" height="30" fill="#0A0A0F"/>
                        <!-- Data modules -->
                        <rect x="70" y="10" width="10" height="10" fill="#0A0A0F"/>
                        <rect x="90" y="10" width="10" height="10" fill="#0A0A0F"/>
                        <rect x="110" y="10" width="10" height="10" fill="#0A0A0F"/>
                        <rect x="70" y="30" width="10" height="10" fill="#0A0A0F"/>
                        <rect x="100" y="30" width="10" height="10" fill="#0A0A0F"/>
                        <rect x="120" y="30" width="10" height="10" fill="#0A0A0F"/>
                        <rect x="70" y="50" width="10" height="10" fill="#0A0A0F"/>
                        <rect x="90" y="50" width="10" height="10" fill="#0A0A0F"/>
                        <rect x="110" y="50" width="10" height="10" fill="#0A0A0F"/>
                        <rect x="10" y="70" width="10" height="10" fill="#0A0A0F"/>
                        <rect x="30" y="70" width="10" height="10" fill="#0A0A0F"/>
                        <rect x="50" y="70" width="10" height="10" fill="#0A0A0F"/>
                        <rect x="10" y="90" width="10" height="10" fill="#0A0A0F"/>
                        <rect x="40" y="90" width="10" height="10" fill="#0A0A0F"/>
                        <rect x="10" y="110" width="10" height="10" fill="#0A0A0F"/>
                        <rect x="30" y="110" width="10" height="10" fill="#0A0A0F"/>
                        <rect x="50" y="110" width="10" height="10" fill="#0A0A0F"/>
                        <rect x="70" y="70" width="60" height="60" fill="#0A0A0F"/>
                        <rect x="75" y="75" width="50" height="50" fill="white"/>
                        <rect x="80" y="80" width="40" height="40" fill="#0A0A0F"/>
                        <rect x="85" y="85" width="30" height="30" fill="white"/>
                        <rect x="90" y="90" width="20" height="20" fill="#0A0A0F"/>
                        <rect x="140" y="70" width="10" height="10" fill="#0A0A0F"/>
                        <rect x="160" y="70" width="10" height="10" fill="#0A0A0F"/>
                        <rect x="180" y="70" width="10" height="10" fill="#0A0A0F"/>
                        <rect x="150" y="90" width="10" height="10" fill="#0A0A0F"/>
                        <rect x="170" y="90" width="10" height="10" fill="#0A0A0F"/>
                        <rect x="140" y="110" width="10" height="10" fill="#0A0A0F"/>
                        <rect x="160" y="110" width="10" height="10" fill="#0A0A0F"/>
                        <rect x="180" y="110" width="10" height="10" fill="#0A0A0F"/>
                        <rect x="70" y="140" width="10" height="10" fill="#0A0A0F"/>
                        <rect x="90" y="140" width="10" height="10" fill="#0A0A0F"/>
                        <rect x="110" y="140" width="10" height="10" fill="#0A0A0F"/>
                        <rect x="130" y="140" width="10" height="10" fill="#0A0A0F"/>
                        <rect x="70" y="160" width="10" height="10" fill="#0A0A0F"/>
                        <rect x="100" y="160" width="10" height="10" fill="#0A0A0F"/>
                        <rect x="120" y="160" width="10" height="10" fill="#0A0A0F"/>
                        <rect x="140" y="160" width="10" height="10" fill="#0A0A0F"/>
                        <rect x="160" y="140" width="10" height="10" fill="#0A0A0F"/>
                        <rect x="180" y="140" width="10" height="10" fill="#0A0A0F"/>
                        <rect x="150" y="160" width="10" height="10" fill="#0A0A0F"/>
                        <rect x="170" y="160" width="10" height="10" fill="#0A0A0F"/>
                        <rect x="140" y="180" width="10" height="10" fill="#0A0A0F"/>
                        <rect x="160" y="180" width="10" height="10" fill="#0A0A0F"/>
                        <rect x="180" y="180" width="10" height="10" fill="#0A0A0F"/>
                        <text x="100" y="198" text-anchor="middle" font-size="8" fill="#0A0A0F" font-family="monospace">SAKURA SUSHI</text>
                    </svg>
                </div>
                <p style="color: var(--color-text-secondary); font-size: 14px; margin-top: 12px;">
                    Scan with your banking app to pay
                </p>
            </div>
            
            <div class="glass-card" style="margin-top: 24px;">
                <div class="panel-label">Amount Due</div>
                <div style="font-size: 36px; font-weight: 700; color: var(--color-accent); margin: 12px 0;">
                    ₱<?php echo $table ? number_format($table['price'], 2) : '0.00'; ?>
                </div>
                <div id="preorder-total" style="display: none;">
                    <div style="display: flex; justify-content: space-between; padding: 8px 0; border-top: 1px solid var(--color-glass-border);">
                        <span style="color: var(--color-text-secondary); font-size: 14px;">Table Reservation</span>
                        <span style="font-weight: 600;">₱<?php echo $table ? number_format($table['price'], 2) : '0.00'; ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 8px 0;">
                        <span style="color: var(--color-text-secondary); font-size: 14px;">Pre-order Food</span>
                        <span id="food-total-display" style="font-weight: 600;">₱0.00</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 12px 0; border-top: 2px solid var(--color-glass-border); font-size: 20px; font-weight: 700; color: var(--color-accent);">
                        <span>Total</span>
                        <span id="grand-total">₱<?php echo $table ? number_format($table['price'], 2) : '0.00'; ?></span>
                    </div>
                </div>
            </div>
            
            <div class="glass-card" style="margin-top: 24px;">
                <div class="panel-label">Upload Payment Receipt *</div>
                <div class="upload-area" style="margin-top: 12px;">
                    <input type="file" name="payment_receipt" id="payment_receipt" accept="image/jpeg,image/png,image/jpg,application/pdf" required>
                    <div class="upload-icon">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                            <polyline points="17 8 12 3 7 8"/>
                            <line x1="12" y1="3" x2="12" y2="15"/>
                        </svg>
                    </div>
                    <div class="upload-text">Click to upload payment receipt</div>
                    <div style="font-size: 12px; color: var(--color-text-muted); margin-top: 8px;">JPG, PNG, or PDF (max 5MB)</div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/main.js"></script>
    <script>
        // Save form data to localStorage before leaving page
        async function saveFormData() {
            console.log('=== Saving Form Data ===');
            const fileInput = document.getElementById('payment_receipt');
            let receiptData = null;
            
            // Save file if uploaded
            if (fileInput && fileInput.files && fileInput.files[0]) {
                console.log('Saving receipt file:', fileInput.files[0].name);
                const file = fileInput.files[0];
                const reader = new FileReader();
                
                receiptData = await new Promise((resolve) => {
                    reader.onload = (e) => {
                        resolve({
                            name: file.name,
                            type: file.type,
                            data: e.target.result
                        });
                    };
                    reader.readAsDataURL(file);
                });
            }
            
            const formData = {
                name: document.getElementById('name')?.value || '',
                phone: document.getElementById('phone')?.value || '',
                people_count: document.getElementById('people_count')?.value || '',
                special_requests: document.getElementById('special_requests')?.value || '',
                receipt: receiptData
            };
            console.log('Form data to save:', formData);
            localStorage.setItem('sakura_reservation_form', JSON.stringify(formData));
            console.log('Form data saved to localStorage');
        }
        
        // Restore form data from localStorage
        function restoreFormData() {
            const savedData = localStorage.getItem('sakura_reservation_form');
            if (savedData) {
                try {
                    const formData = JSON.parse(savedData);
                    if (formData.name) document.getElementById('name').value = formData.name;
                    if (formData.phone) document.getElementById('phone').value = formData.phone;
                    if (formData.people_count) document.getElementById('people_count').value = formData.people_count;
                    if (formData.special_requests) document.getElementById('special_requests').value = formData.special_requests;
                    
                    // Restore file if exists
                    if (formData.receipt) {
                        const fileInput = document.getElementById('payment_receipt');
                        if (fileInput) {
                            // Convert base64 back to File
                            fetch(formData.receipt.data)
                                .then(res => res.blob())
                                .then(blob => {
                                    const file = new File([blob], formData.receipt.name, { type: formData.receipt.type });
                                    const dataTransfer = new DataTransfer();
                                    dataTransfer.items.add(file);
                                    fileInput.files = dataTransfer.files;
                                    
                                    // Trigger change event to update UI
                                    const event = new Event('change', { bubbles: true });
                                    fileInput.dispatchEvent(event);
                                });
                        }
                    }
                } catch (e) {
                    console.error('Error restoring form data:', e);
                }
            }
        }
        
        // Clear old cart data if coming directly (not from menu)
        const urlParams = new URLSearchParams(window.location.search);
        const fromMenu = urlParams.get('from_menu');
        
        // If not coming from menu, clear any old cart data
        if (!fromMenu || fromMenu === 'null' || fromMenu === '') {
            localStorage.removeItem('sakura_cart');
            localStorage.removeItem('sakura_cart_total');
        }
        
        // Check for cart data from pre-order
        document.addEventListener('DOMContentLoaded', () => {
            // Debug: Check what's in localStorage
            console.log('=== Reservation Page Loaded ===');
            console.log('from_menu parameter:', fromMenu);
            console.log('Saved form data:', localStorage.getItem('sakura_reservation_form'));
            
            // Restore form data if coming back from menu
            if (fromMenu) {
                console.log('Restoring form data...');
                restoreFormData();
            } else {
                console.log('Not from menu, skipping restore');
            }
            
            // Save form data whenever inputs change
            const formInputs = ['name', 'phone', 'people_count', 'special_requests'];
            formInputs.forEach(id => {
                const element = document.getElementById(id);
                if (element) {
                    element.addEventListener('input', saveFormData);
                    element.addEventListener('change', saveFormData);
                }
            });
            
            // Also save when file is uploaded
            const fileInput = document.getElementById('payment_receipt');
            if (fileInput) {
                fileInput.addEventListener('change', saveFormData);
            }
            
            // Handle pre-order link click - save form data first
            const preorderLink = document.getElementById('preorder-link');
            if (preorderLink) {
                preorderLink.addEventListener('click', async (e) => {
                    e.preventDefault();
                    await saveFormData();
                    window.location.href = preorderLink.href;
                });
            }
            
            const cartData = localStorage.getItem('sakura_cart');
            const cartTotal = localStorage.getItem('sakura_cart_total');
            
            if (cartData) {
                const items = JSON.parse(cartData);
                if (items.length > 0) {
                    document.getElementById('cart-summary').style.display = 'block';
                    document.getElementById('preorder-total').style.display = 'block';
                    
                    const listContainer = document.getElementById('cart-items-list');
                    let html = '';
                    items.forEach(item => {
                        html += `<div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--color-glass-border);">
                            <span>${item.name} x${item.quantity}</span>
                            <span>₱${(item.price * item.quantity).toFixed(2)}</span>
                        </div>`;
                    });
                    listContainer.innerHTML = html;
                    
                    const tablePrice = <?php echo $table ? $table['price'] : 0; ?>;
                    const foodTotal = parseFloat(cartTotal) || 0;
                    const grandTotal = tablePrice + foodTotal;
                    
                    document.getElementById('food-total-display').textContent = '₱' + foodTotal.toFixed(2);
                    document.getElementById('grand-total').textContent = '₱' + grandTotal.toFixed(2);
                }
            }
            
            // Time slot selection
            const timeSlots = document.querySelectorAll('.time-slot');
            const timeInput = document.getElementById('reservation_time_input');
            
            timeSlots.forEach(slot => {
                slot.addEventListener('click', () => {
                    timeSlots.forEach(s => s.classList.remove('selected'));
                    slot.classList.add('selected');
                    timeInput.value = slot.dataset.time;
                });
            });
            
            // Form validation with time check
            const form = document.getElementById('reservation-form');
            form.addEventListener('submit', (e) => {
                if (!timeInput.value) {
                    e.preventDefault();
                    document.getElementById('time-error').classList.add('visible');
                    return false;
                }
            });
        });
    </script>
</body>
</html>
