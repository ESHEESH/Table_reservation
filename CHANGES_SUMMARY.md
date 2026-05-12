# Restaurant System Updates

## Changes Implemented

### 1. Restaurant Operating Hours (7 PM - 11 PM)
**File: `app/reservation.php`**
- Updated time slot generation to only show 7:00 PM to 11:00 PM
- Time slots: 19:00, 19:30, 20:00, 20:30, 21:00, 21:30, 22:00, 22:30, 23:00

### 2. Enhanced Table Management
**File: `app/database.sql`**

Added new columns to `tables` table:
- `location` VARCHAR(50) - Table location (e.g., "Window Side", "Main Hall", "Private Area", "VIP Section")
- `table_type` ENUM - Type of table: 'standard', 'booth', 'counter', 'vip'
- `is_smoking` TINYINT(1) - Smoking area flag (0 = non-smoking, 1 = smoking)

Updated table prices to Philippine Peso (₱):
- 2-seater: ₱150.00
- 4-seater: ₱250.00
- 6-seater: ₱350.00
- 8-seater: ₱500.00

**File: `app/admin/admin.php`**
- Updated table edit form to include new fields:
  - Location input field
  - Table Type dropdown (Standard/Booth/Counter/VIP)
  - Smoking Area checkbox
- Updated backend handler to save new table columns

### 3. Menu Stock Management System
**File: `app/database.sql`**

Added new columns to `menu_items` table:
- `stock` INT(11) - Current stock quantity (default: 100)
- `available` TINYINT(1) - Availability flag for pre-orders (default: 1)

Updated menu prices to Philippine Peso (₱):
- Prices range from ₱80 (Green Tea) to ₱650 (Mixed Sashimi)

**File: `app/admin/admin.php`**

Enhanced admin panel with stock management:
- **Add Menu Item Form**: Added stock quantity field and availability checkbox
- **Menu Table Display**: 
  - New "Stock" column with color-coded pills:
    - Red (≤10 pcs): Low stock warning
    - Yellow (≤30 pcs): Medium stock warning
    - Green (>30 pcs): Good stock
  - New "Available" column showing Yes/No status
- **Edit Menu Item Form**: Added stock and availability fields
- **Backend Handlers**: Updated to save/update stock and availability data

### 4. Database Schema Updates

**Tables Structure:**
```sql
CREATE TABLE `tables` (
    ...existing fields...
    `location` VARCHAR(50) DEFAULT 'Main Hall',
    `table_type` ENUM('standard','booth','counter','vip') DEFAULT 'standard',
    `is_smoking` TINYINT(1) DEFAULT 0,
    ...
);
```

**Menu Items Structure:**
```sql
CREATE TABLE `menu_items` (
    ...existing fields...
    `stock` INT(11) DEFAULT 100,
    `available` TINYINT(1) DEFAULT 1,
    ...
);
```

## How to Apply Changes

1. **Backup your current database** before applying changes
2. Run the updated `app/database.sql` to recreate tables with new structure
3. The admin panel will automatically show new fields for managing:
   - Table locations, types, and smoking areas
   - Menu item stock levels and availability

## Admin Panel Features

### Stock Management Overview
- Visual stock indicators with color coding
- Easy stock quantity updates
- Toggle availability for pre-orders
- Real-time stock tracking

### Table Management
- Categorize tables by location
- Set table types (Standard/Booth/Counter/VIP)
- Mark smoking/non-smoking areas
- Better organization for restaurant layout

### Operating Hours
- Reservation system now restricted to 7 PM - 11 PM
- Time slots displayed in 30-minute intervals
- Prevents bookings outside operating hours
