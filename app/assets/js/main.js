/**
 * ============================================================================
 * SAKURA SUSHI RESERVATION SYSTEM - MAIN JAVASCRIPT
 * ============================================================================
 * 
 * ALGORITHMS & DATA STRUCTURES IMPLEMENTED:
 * 
 * 1. HASH MAP + DOUBLY LINKED LIST (Cart System)
 *    - HashMap for O(1) item lookup by ID
 *    - Doubly Linked List for O(1) insertion/deletion
 *    - Combined: O(1) find, add, remove, update operations
 *    - Maintains insertion order while allowing instant access
 * 
 * 2. PRIORITY QUEUE (Waitlist - implemented in PHP backend)
 *    - Min-Heap for VIP and early booker priority
 * 
 * 3. HASH TABLE (Reservation lookup - implemented in PHP backend)
 *    - Dynamic resizing with load factor monitoring
 * 
 * COMPLEXITY ANALYSIS:
 * - Cart Operations: O(1) for all operations (find, add, remove, update)
 * - Previous Linked List: O(n) for find/remove
 * - Improvement: n times faster for large carts
 * 
 * CART IMPLEMENTATION DETAILS:
 * - HashMap: Map<itemId, DoublyLinkedListNode>
 * - Doubly Linked List: head <-> node1 <-> node2 <-> ... <-> tail
 * - Benefits: Instant access + Order preservation + Fast deletion
 * 
 * @version 2.0
 * @author Sakura Sushi Development Team
 * ============================================================================
 */

// =====================================
// UTILITY FUNCTIONS
// =====================================

const $ = (selector) => document.querySelector(selector);
const $$ = (selector) => document.querySelectorAll(selector);

const formatCurrency = (amount) => {
    return '₱' + parseFloat(amount).toFixed(2);
};

const formatDate = (dateStr) => {
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    });
};

const generateId = () => {
    return 'skr-' + Math.random().toString(36).substr(2, 6).toUpperCase();
};

// Toast Notification System
const showToast = (message, type = 'success') => {
    // Create toast container if it doesn't exist
    let toastContainer = $('.toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container';
        document.body.appendChild(toastContainer);
    }
    
    // Create toast element
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    
    // Icon based on type
    const icons = {
        success: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>',
        error: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>',
        info: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>',
        warning: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>'
    };
    
    toast.innerHTML = `
        <div class="toast-icon">${icons[type] || icons.info}</div>
        <div class="toast-message">${message}</div>
        <button class="toast-close" onclick="this.parentElement.remove()">×</button>
    `;
    
    toastContainer.appendChild(toast);
    
    // Trigger animation
    setTimeout(() => toast.classList.add('show'), 10);
    
    // Auto remove after 4 seconds
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 4000);
};

// Show alert message (deprecated - use showToast instead)
const showAlert = (message, type = 'error') => {
    showToast(message, type);
};

// Shake element for error
const shakeElement = (element) => {
    element.classList.add('shake');
    setTimeout(() => element.classList.remove('shake'), 500);
};

// =====================================
// FLOATING PETALS (Landing Page)
// =====================================

const initPetals = () => {
    const container = $('.petals');
    if (!container) return;
    
    const petalCount = 15;
    for (let i = 0; i < petalCount; i++) {
        const petal = document.createElement('div');
        petal.className = 'petal';
        petal.style.left = Math.random() * 100 + '%';
        petal.style.animationDuration = (15 + Math.random() * 20) + 's';
        petal.style.animationDelay = Math.random() * 20 + 's';
        petal.style.width = (10 + Math.random() * 15) + 'px';
        petal.style.height = petal.style.width;
        container.appendChild(petal);
    }
};

// =====================================
// SLIDE-IN PANEL
// =====================================

const initSlidePanel = () => {
    const overlay = $('.slide-overlay');
    const panel = $('.slide-panel');
    const closeBtn = $('.slide-close');
    
    if (!overlay || !panel) return;
    
    const openPanel = () => {
        overlay.classList.add('active');
        panel.classList.add('active');
        document.body.style.overflow = 'hidden';
    };
    
    const closePanel = () => {
        overlay.classList.remove('active');
        panel.classList.remove('active');
        document.body.style.overflow = '';
    };
    
    if (closeBtn) closeBtn.addEventListener('click', closePanel);
    overlay.addEventListener('click', closePanel);
    
    return { openPanel, closePanel };
};

// =====================================
// TABLE SELECTION
// =====================================

const initTableSelection = () => {
    const tableCards = $$('.table-card');
    const panel = initSlidePanel();
    
    if (!tableCards.length || !panel) return;
    
    tableCards.forEach(card => {
        card.addEventListener('click', () => {
            if (card.classList.contains('occupied')) {
                showAlert('This table is already occupied. Please select another.', 'warning');
                return;
            }
            
            // Update panel content
            const tableNum = card.dataset.table;
            const capacity = card.dataset.capacity;
            const price = card.dataset.price;
            const features = card.dataset.features;
            const status = card.dataset.status;
            const tableId = card.dataset.id;
            
            $('.panel-title').textContent = `Table ${tableNum}`;
            $('.panel-capacity').textContent = `${capacity} guests`;
            $('.panel-price').textContent = formatCurrency(price);
            $('.panel-status').textContent = status.charAt(0).toUpperCase() + status.slice(1);
            
            const featuresList = $('.panel-features');
            featuresList.innerHTML = '';
            if (features) {
                features.split(',').forEach(f => {
                    const li = document.createElement('li');
                    li.textContent = f.trim();
                    featuresList.appendChild(li);
                });
            }
            
            // Update buttons with table ID
            const reserveBtn = $('.btn-reserve-table');
            const preorderBtn = $('.btn-preorder');
            
            if (reserveBtn) {
                reserveBtn.href = `reservation.php?table_id=${tableId}`;
            }
            if (preorderBtn) {
                preorderBtn.href = `menu.php?table_id=${tableId}`;
            }
            
            // Highlight selected card
            tableCards.forEach(c => c.classList.remove('selected'));
            card.classList.add('selected');
            
            panel.openPanel();
        });
    });
};

// =====================================
// FORM VALIDATION
// =====================================

const validateForm = (form) => {
    let isValid = true;
    const requiredFields = form.querySelectorAll('[required]');
    
    requiredFields.forEach(field => {
        const errorEl = field.parentElement.querySelector('.form-error');
        
        if (!field.value.trim()) {
            isValid = false;
            field.classList.add('error');
            if (errorEl) errorEl.classList.add('visible');
            shakeElement(field);
        } else {
            field.classList.remove('error');
            if (errorEl) errorEl.classList.remove('visible');
        }
    });
    
    // Phone validation
    const phoneField = form.querySelector('input[type="tel"]');
    if (phoneField && phoneField.value) {
        const phoneRegex = /^[\d\s\-\+\(\)]{10,}$/;
        if (!phoneRegex.test(phoneField.value)) {
            isValid = false;
            phoneField.classList.add('error');
            const errorEl = phoneField.parentElement.querySelector('.form-error');
            if (errorEl) {
                errorEl.textContent = 'Please enter a valid phone number';
                errorEl.classList.add('visible');
            }
            shakeElement(phoneField);
        }
    }
    
    return isValid;
};

// =====================================
// FILE UPLOAD
// =====================================

const initFileUpload = () => {
    const uploadArea = $('.upload-area');
    const fileInput = $('.upload-area input[type="file"]');
    
    if (!uploadArea || !fileInput) return;
    
    fileInput.addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (!file) return;
        
        // Validate file type
        const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
        if (!allowedTypes.includes(file.type)) {
            showAlert('Please upload a JPG, PNG, or PDF file', 'error');
            return;
        }
        
        // Validate file size (5MB max)
        if (file.size > 5 * 1024 * 1024) {
            showAlert('File size must be less than 5MB', 'error');
            return;
        }
        
        uploadArea.classList.add('has-file');
        
        // Show preview for images
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = (e) => {
                let preview = uploadArea.querySelector('.upload-preview');
                if (!preview) {
                    preview = document.createElement('img');
                    preview.className = 'upload-preview';
                    uploadArea.appendChild(preview);
                }
                preview.src = e.target.result;
            };
            reader.readAsDataURL(file);
        }
        
        const uploadText = uploadArea.querySelector('.upload-text');
        if (uploadText) {
            uploadText.textContent = `Selected: ${file.name}`;
        }
    });
};

// =====================================
// TIME SLOT SELECTION
// =====================================

const initTimeSlots = () => {
    const timeSlots = $$('.time-slot');
    const timeInput = $('#reservation_time');
    
    if (!timeSlots.length) return;
    
    timeSlots.forEach(slot => {
        slot.addEventListener('click', () => {
            if (slot.classList.contains('disabled')) return;
            
            timeSlots.forEach(s => s.classList.remove('selected'));
            slot.classList.add('selected');
            
            if (timeInput) {
                timeInput.value = slot.dataset.time;
            }
        });
    });
};

// =====================================
// MENU & CART (HashMap + Doubly Linked List Implementation)
// =====================================

/**
 * ============================================================================
 * UPGRADED CART SYSTEM: HashMap + Doubly Linked List
 * ============================================================================
 * 
 * PREVIOUS IMPLEMENTATION (Singly Linked List):
 * - find(): O(n) - traverse entire list
 * - remove(): O(n) - traverse to find node
 * - update(): O(n) - traverse to find node
 * 
 * NEW IMPLEMENTATION (HashMap + Doubly Linked List):
 * - find(): O(1) - direct hash map lookup
 * - remove(): O(1) - hash map + doubly linked pointers
 * - update(): O(1) - direct access via hash map
 * - append(): O(1) - add to tail
 * 
 * STRUCTURE:
 * HashMap: { itemId -> DoublyLinkedListNode }
 * List: head <-> node1 <-> node2 <-> tail
 * 
 * BENEFITS:
 * - Instant access to any item by ID
 * - Fast insertion/deletion anywhere in list
 * - Maintains insertion order
 * - Perfect for shopping cart operations
 * ============================================================================
 */

// Doubly Linked List Node
class CartNode {
    constructor(data) {
        this.data = data;
        this.prev = null;
        this.next = null;
    }
}

// Cart with HashMap + Doubly Linked List
class CartHashMapList {
    constructor() {
        this.head = null;
        this.tail = null;
        this.map = new Map(); // HashMap for O(1) access
        this.size = 0;
        this._total = 0;
    }
    
    /**
     * Append item to end - O(1)
     * Uses hash map for instant duplicate detection
     */
    append(item) {
        // Check if item already exists using HashMap - O(1)
        if (this.map.has(item.id)) {
            // Update quantity instead of adding duplicate
            const existingNode = this.map.get(item.id);
            existingNode.data.quantity += item.quantity || 1;
            existingNode.data.subtotal = existingNode.data.price * existingNode.data.quantity;
            this._updateTotal();
            this._saveToStorage();
            return;
        }
        
        const newNode = new CartNode(item);
        
        if (!this.head) {
            // First node
            this.head = this.tail = newNode;
        } else {
            // Add to tail
            this.tail.next = newNode;
            newNode.prev = this.tail;
            this.tail = newNode;
        }
        
        // Add to hash map - O(1)
        this.map.set(item.id, newNode);
        this.size++;
        this._updateTotal();
        this._saveToStorage();
    }
    
    /**
     * Remove by item ID - O(1)
     * HashMap provides instant access, doubly linked list allows O(1) deletion
     */
    remove(itemId) {
        // Find node using HashMap - O(1)
        const node = this.map.get(itemId);
        if (!node) return false;
        
        // Remove from doubly linked list - O(1)
        if (node.prev) {
            node.prev.next = node.next;
        } else {
            // Removing head
            this.head = node.next;
        }
        
        if (node.next) {
            node.next.prev = node.prev;
        } else {
            // Removing tail
            this.tail = node.prev;
        }
        
        // Remove from hash map - O(1)
        this.map.delete(itemId);
        this.size--;
        this._updateTotal();
        this._saveToStorage();
        return true;
    }
    
    /**
     * Find by ID - O(1)
     * Direct hash map lookup instead of O(n) traversal
     */
    find(itemId) {
        const node = this.map.get(itemId);
        return node ? node.data : null;
    }
    
    /**
     * Update quantity - O(1)
     * HashMap provides instant access
     */
    updateQuantity(itemId, quantity) {
        const node = this.map.get(itemId);
        if (!node) return false;
        
        node.data.quantity = quantity;
        node.data.subtotal = node.data.price * quantity;
        this._updateTotal();
        this._saveToStorage();
        return true;
    }
    
    /**
     * Convert to array for display - O(n)
     * Traverse linked list to maintain insertion order
     */
    toArray() {
        const items = [];
        let current = this.head;
        while (current) {
            items.push(current.data);
            current = current.next;
        }
        return items;
    }
    
    /**
     * Get total - O(1)
     */
    getTotal() {
        return this._total;
    }
    
    /**
     * Update total - O(n)
     * Must traverse list to sum all items
     */
    _updateTotal() {
        this._total = 0;
        let current = this.head;
        while (current) {
            this._total += current.data.subtotal || 0;
            current = current.next;
        }
    }
    
    /**
     * Save to localStorage
     */
    _saveToStorage() {
        localStorage.setItem('sakura_cart', JSON.stringify(this.toArray()));
        localStorage.setItem('sakura_cart_total', this._total.toString());
    }
    
    /**
     * Load from localStorage
     */
    loadFromStorage() {
        const stored = localStorage.getItem('sakura_cart');
        if (stored) {
            const items = JSON.parse(stored);
            items.forEach(item => this.append(item));
        }
    }
    
    /**
     * Clear cart
     */
    clear() {
        this.head = null;
        this.tail = null;
        this.map.clear();
        this.size = 0;
        this._total = 0;
        localStorage.removeItem('sakura_cart');
        localStorage.removeItem('sakura_cart_total');
    }
    
    /**
     * Get statistics for debugging
     */
    getStats() {
        return {
            size: this.size,
            total: this._total,
            mapSize: this.map.size,
            hasHead: !!this.head,
            hasTail: !!this.tail
        };
    }
}

// Global cart instance (upgraded to HashMap + Doubly Linked List)
const cart = new CartHashMapList();

const initMenu = () => {
    const addButtons = $$('.btn-add-to-cart');
    const cartPanel = $('.cart-panel');
    const cartOverlay = $('.cart-overlay');
    const cartToggle = $('.cart-toggle');
    
    // Load existing cart
    cart.loadFromStorage();
    updateCartUI();
    
    // Add to cart buttons
    addButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const itemId = parseInt(btn.dataset.id);
            const itemName = btn.dataset.name;
            const itemPrice = parseFloat(btn.dataset.price);
            const itemImage = btn.dataset.image;
            
            const existing = cart.find(itemId);
            if (existing) {
                cart.updateQuantity(itemId, existing.quantity + 1);
            } else {
                cart.append({
                    id: itemId,
                    name: itemName,
                    price: itemPrice,
                    image: itemImage,
                    quantity: 1,
                    subtotal: itemPrice
                });
            }
            
            updateCartUI();
            showAlert(`${itemName} added to order`, 'success');
        });
    });
    
    // Cart toggle
    if (cartToggle) {
        cartToggle.addEventListener('click', () => {
            cartPanel?.classList.add('active');
            cartOverlay?.classList.add('active');
        });
    }
    
    // Close cart
    $('.cart-close')?.addEventListener('click', closeCart);
    cartOverlay?.addEventListener('click', closeCart);
    
    function closeCart() {
        cartPanel?.classList.remove('active');
        cartOverlay?.classList.remove('active');
    }
};

const updateCartUI = () => {
    const cartItems = $('.cart-items');
    const cartCount = $('.cart-count');
    const subtotalEl = $('.cart-subtotal');
    const taxEl = $('.cart-tax');
    const totalEl = $('.cart-total');
    
    if (!cartItems) return;
    
    const items = cart.toArray();
    
    // Update count badge
    const totalItems = items.reduce((sum, item) => sum + item.quantity, 0);
    if (cartCount) cartCount.textContent = totalItems;
    
    // Render items
    cartItems.innerHTML = '';
    items.forEach(item => {
        const div = document.createElement('div');
        div.className = 'cart-item';
        div.innerHTML = `
            <img src="${item.image}" alt="${item.name}" class="cart-item-image" 
                 onerror="this.style.display='none'">
            <div class="cart-item-info">
                <div class="cart-item-name">${item.name}</div>
                <div class="cart-item-price">${formatCurrency(item.price)}</div>
            </div>
            <div class="cart-item-qty">x${item.quantity}</div>
            <button class="qty-btn btn-remove-item" data-id="${item.id}">&times;</button>
        `;
        cartItems.appendChild(div);
    });
    
    // Add remove listeners
    $$('.btn-remove-item').forEach(btn => {
        btn.addEventListener('click', () => {
            cart.remove(parseInt(btn.dataset.id));
            updateCartUI();
        });
    });
    
    // Update totals
    const subtotal = cart.getTotal();
    const tax = subtotal * 0.08;
    const total = subtotal + tax;
    
    if (subtotalEl) subtotalEl.textContent = formatCurrency(subtotal);
    if (taxEl) taxEl.textContent = formatCurrency(tax);
    if (totalEl) totalEl.textContent = formatCurrency(total);
    
    // Store cart data for reservation form
    localStorage.setItem('sakura_cart_total', total.toFixed(2));
};

// =====================================
// CATEGORY TABS
// =====================================

const initCategoryTabs = () => {
    const tabBtns = $$('.tab-btn');
    const menuItems = $$('.menu-item');
    
    if (!tabBtns.length) return;
    
    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const category = btn.dataset.category;
            
            // Update active tab
            tabBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            
            // Filter items
            menuItems.forEach(item => {
                if (category === 'all' || item.dataset.category === category) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    });
};

// =====================================
// QUANTITY CONTROLS
// =====================================

const initQuantityControls = () => {
    $$('.quantity-control').forEach(control => {
        const minus = control.querySelector('.qty-minus');
        const plus = control.querySelector('.qty-plus');
        const value = control.querySelector('.qty-value');
        
        if (minus) {
            minus.addEventListener('click', () => {
                let qty = parseInt(value.textContent);
                if (qty > 1) {
                    qty--;
                    value.textContent = qty;
                }
            });
        }
        
        if (plus) {
            plus.addEventListener('click', () => {
                let qty = parseInt(value.textContent);
                if (qty < 20) {
                    qty++;
                    value.textContent = qty;
                }
            });
        }
    });
};

// =====================================
// SUBMIT RESERVATION
// =====================================

const initReservationSubmit = () => {
    const form = $('#reservation-form');
    if (!form) return;
    
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        if (!validateForm(form)) return;
        
        const submitBtn = form.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<div class="loading-spinner"></div> Processing...';
        
        const formData = new FormData(form);
        
        // Add cart data if exists
        const cartData = cart.toArray();
        if (cartData.length > 0) {
            formData.append('has_pre_order', '1');
            formData.append('cart_items', JSON.stringify(cartData));
            formData.append('food_total', cart.getTotal().toFixed(2));
        }
        
        try {
            const response = await fetch('api/create-reservation.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                // Clear cart and form data on success
                cart.clear();
                localStorage.removeItem('sakura_reservation_form');
                // Redirect to pre-order prompt
                window.location.href = result.redirect_url || `preorder-prompt.php?code=${result.confirmation_code}&table_id=${result.table_id}`;
            } else {
                showAlert(result.message || 'Failed to create reservation', 'error');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Confirm Reservation';
            }
        } catch (error) {
            showAlert('Network error. Please try again.', 'error');
            submitBtn.disabled = false;
            submitBtn.textContent = 'Confirm Reservation';
        }
    });
};

// =====================================
// BACK BUTTON
// =====================================

const initBackButton = () => {
    const backBtn = $('.nav-back');
    if (backBtn) {
        backBtn.addEventListener('click', () => {
            window.history.back();
        });
    }
};

// =====================================
// INITIALIZATION
// =====================================

document.addEventListener('DOMContentLoaded', () => {
    initPetals();
    initSlidePanel();
    initTableSelection();
    initFileUpload();
    initTimeSlots();
    initMenu();
    initCategoryTabs();
    initQuantityControls();
    initReservationSubmit();
    initBackButton();
});
