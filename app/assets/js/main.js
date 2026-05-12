/**
 * Sakura Sushi Reservation System - Main JavaScript
 * Data Structures & Algorithms Project
 * 
 * Data Structures Used:
 * - Hash Table: Fast reservation lookup
 * - Queue: Waitlist management
 * - Linked List: Cart management
 * - QuickSort: Table/menu sorting
 * - Binary Search: Fast lookups
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

// Show alert message
const showAlert = (message, type = 'error') => {
    const existing = $('.alert');
    if (existing) existing.remove();
    
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.textContent = message;
    
    const container = $('.page-header') || $('.two-column') || $('.confirmation-container');
    if (container) {
        container.insertBefore(alert, container.firstChild);
        setTimeout(() => alert.remove(), 5000);
    }
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
// MENU & CART (Linked List Implementation)
// =====================================

// Linked List Node for cart items
class CartNode {
    constructor(data) {
        this.data = data;
        this.next = null;
    }
}

// Linked List for cart management
class CartLinkedList {
    constructor() {
        this.head = null;
        this.size = 0;
        this._total = 0;
    }
    
    // Insert at end - O(n)
    append(item) {
        const newNode = new CartNode(item);
        
        if (!this.head) {
            this.head = newNode;
        } else {
            let current = this.head;
            while (current.next) {
                current = current.next;
            }
            current.next = newNode;
        }
        this.size++;
        this._updateTotal();
        this._saveToStorage();
    }
    
    // Delete by item ID - O(n)
    remove(itemId) {
        if (!this.head) return false;
        
        if (this.head.data.id === itemId) {
            this.head = this.head.next;
            this.size--;
            this._updateTotal();
            this._saveToStorage();
            return true;
        }
        
        let current = this.head;
        while (current.next && current.next.data.id !== itemId) {
            current = current.next;
        }
        
        if (current.next) {
            current.next = current.next.next;
            this.size--;
            this._updateTotal();
            this._saveToStorage();
            return true;
        }
        
        return false;
    }
    
    // Find by ID - O(n)
    find(itemId) {
        let current = this.head;
        while (current) {
            if (current.data.id === itemId) {
                return current.data;
            }
            current = current.next;
        }
        return null;
    }
    
    // Update quantity - O(n)
    updateQuantity(itemId, quantity) {
        let current = this.head;
        while (current) {
            if (current.data.id === itemId) {
                current.data.quantity = quantity;
                current.data.subtotal = current.data.price * quantity;
                this._updateTotal();
                this._saveToStorage();
                return true;
            }
            current = current.next;
        }
        return false;
    }
    
    // Convert to array for display
    toArray() {
        const items = [];
        let current = this.head;
        while (current) {
            items.push(current.data);
            current = current.next;
        }
        return items;
    }
    
    // Get total
    getTotal() {
        return this._total;
    }
    
    _updateTotal() {
        this._total = 0;
        let current = this.head;
        while (current) {
            this._total += current.data.subtotal || 0;
            current = current.next;
        }
    }
    
    _saveToStorage() {
        localStorage.setItem('sakura_cart', JSON.stringify(this.toArray()));
    }
    
    loadFromStorage() {
        const stored = localStorage.getItem('sakura_cart');
        if (stored) {
            const items = JSON.parse(stored);
            items.forEach(item => this.append(item));
        }
    }
    
    clear() {
        this.head = null;
        this.size = 0;
        this._total = 0;
        localStorage.removeItem('sakura_cart');
    }
}

// Global cart instance
const cart = new CartLinkedList();

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
                // Clear cart on success
                cart.clear();
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
