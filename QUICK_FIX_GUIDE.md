# Quick Fix Guide

## Current Status

Based on your screenshots, here's what I see:

### ✅ Working:
1. **Admin Panel** - Menu items showing with Stock and Available columns
2. **Tables Page** - Showing all tables with prices
3. **Table Details Modal** - Shows table info

### ⚠️ Issues to Fix:

## Issue 1: Prices Too Low

Your database still has old prices (₱15, ₱25 instead of ₱150, ₱250).

**Fix:** Run the price update script:
```
http://localhost:8000/update_prices.php
```

This will:
- Update table prices: ₱15→₱150, ₱25→₱250, ₱35→₱350, ₱50→₱500
- Update menu prices to realistic PHP amounts (₱80-₱650)

## Issue 2: Calendar & Time Picker

The reservation page DOES have a date picker and time slots. Here's how it works:

### Date Picker
- Standard HTML5 date input
- Located on: `http://localhost:8000/reservation.php?table_id=1`
- Allows dates from today to 30 days ahead

### Time Slots
- Shows clickable time buttons from **7:00 PM to 11:00 PM**
- Grid layout with 30-minute intervals
- Click a time slot to select it (turns gold/yellow)

**If you don't see them:**
1. Make sure you're on the reservation page (not tables page)
2. You must select a table first from tables.php
3. The URL should be: `reservation.php?table_id=X`

## Issue 3: Menu Items Not Showing

If menu items aren't displaying on `menu.php`:

1. Check you selected a table first
2. URL should be: `http://localhost:8000/menu.php?table_id=1`
3. Run migration if you haven't: `http://localhost:8000/migrate_database.php`

## Complete Setup Checklist

### Step 1: Run Migration (if not done)
```
http://localhost:8000/migrate_database.php
```
Expected output:
```
✓ Added 'stock' column
✓ Added 'available' column
✓ Added 'location' column
✓ Added 'table_type' column
✓ Added 'is_smoking' column
```

### Step 2: Update Prices
```
http://localhost:8000/update_prices.php
```
Expected output:
```
✓ Table prices updated
✓ Menu item prices updated
```

### Step 3: Test the Flow

1. **Start at Home**
   ```
   http://localhost:8000/index.php
   ```

2. **Select a Table**
   ```
   http://localhost:8000/tables.php
   ```
   - Click any green (available) table
   - Modal opens showing table details

3. **Reserve Table**
   - Click "Reserve This Table" button
   - Goes to: `reservation.php?table_id=X`
   - You should see:
     - ✅ Date picker (calendar icon)
     - ✅ Time slots grid (7 PM - 11 PM)
     - ✅ Name, phone, guest count fields

4. **Pre-Order (Optional)**
   - Click "Pre-Order Food" button
   - Goes to: `menu.php?table_id=X`
   - You should see:
     - ✅ Category tabs (All, Sushi, Sashimi, etc.)
     - ✅ Menu items grid with images
     - ✅ Add to cart buttons
     - ✅ Cart icon in top right

## Troubleshooting

### "No calendar showing"
- You're probably on tables.php (table selection page)
- Calendar is on reservation.php (next step after selecting table)
- Click "Reserve This Table" to go to reservation page

### "Time slots not clickable"
- Check browser console for JavaScript errors (F12)
- Make sure main.js is loaded
- Try hard refresh (Ctrl + F5)

### "Menu items blank"
- Run migration script first
- Check if menu_items table has data:
  ```
  http://localhost:8000/admin/admin.php
  ```
  Go to "Menu Items" tab

### "Prices still wrong"
- Run update_prices.php script
- Clear browser cache
- Hard refresh (Ctrl + F5)

## What Each Page Does

| Page | Purpose | URL Example |
|------|---------|-------------|
| index.php | Landing page | http://localhost:8000/ |
| tables.php | Select table | http://localhost:8000/tables.php |
| reservation.php | **DATE/TIME PICKER HERE** | http://localhost:8000/reservation.php?table_id=1 |
| menu.php | Pre-order food | http://localhost:8000/menu.php?table_id=1 |
| confirmation.php | Booking confirmed | http://localhost:8000/confirmation.php?code=SKR-XXX |
| admin/admin.php | Admin panel | http://localhost:8000/admin/admin.php |

## Expected Behavior

### Reservation Page (Where Calendar Is)
```
┌─────────────────────────────────────┐
│ Complete Your Reservation          │
├─────────────────────────────────────┤
│ Full Name: [____________]           │
│ Phone: [____________]               │
│ Guests: [▼ Select]                  │
│                                     │
│ Date: [📅 05/12/2026]  ← CALENDAR  │
│                                     │
│ Time: ← TIME SLOTS BELOW            │
│ ┌────┬────┬────┬────┐              │
│ │7:00│7:30│8:00│8:30│              │
│ ├────┼────┼────┼────┤              │
│ │9:00│9:30│10:0│10:3│              │
│ ├────┼────┼────┼────┤              │
│ │11:0│    │    │    │              │
│ └────┴────┴────┴────┘              │
│                                     │
│ [Confirm Reservation]               │
└─────────────────────────────────────┘
```

## Still Having Issues?

1. Check server is running: http://localhost:8000
2. Check MySQL is running in XAMPP
3. Clear browser cache completely
4. Try different browser
5. Check browser console (F12) for errors
