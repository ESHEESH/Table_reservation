# Database Migration Instructions

## Quick Fix for "Undefined array key 'stock'" Error

Your database is missing the new columns. Follow these steps:

### Option 1: Run Migration Script (Recommended - Keeps Your Data)

1. Open your browser and go to:
   ```
   http://localhost:8000/migrate_database.php
   ```

2. You should see output like:
   ```
   Connected to database successfully!
   
   Adding columns to menu_items table...
   ✓ Added 'stock' column
   ✓ Added 'available' column
   
   Adding columns to tables table...
   ✓ Added 'location' column
   ✓ Added 'table_type' column
   ✓ Added 'is_smoking' column
   
   ✅ Migration completed successfully!
   ```

3. Refresh your admin panel - the errors should be gone!

### Option 2: Reimport Database (Fresh Start - Loses Your Data)

If you want to start fresh:

1. Open phpMyAdmin: http://localhost/phpmyadmin
2. Select `sakura_sushi` database
3. Click **Drop** to delete it
4. Create a new database named `sakura_sushi`
5. Click **Import** tab
6. Select `app/database.sql`
7. Click **Go**

## What Changed?

### Menu Items Table
- Added `stock` column (tracks inventory)
- Added `available` column (toggle for pre-orders)

### Tables Table
- Added `location` column (Window Side, Main Hall, etc.)
- Added `table_type` column (Standard, Booth, Counter, VIP)
- Added `is_smoking` column (Smoking area flag)

### Auto-Cleanup Feature
- Tables automatically reset to "available" 4 hours after reservation time
- Runs every time admin panel loads
- Prevents tables from being stuck as "occupied"

## Troubleshooting

**Error: "Access denied for user"**
- Check your `app/config.php` database credentials
- Make sure MySQL is running in XAMPP

**Migration script shows blank page**
- Check if MySQL service is running
- Verify database name is correct in `config.php`

**Still seeing errors after migration**
- Clear your browser cache (Ctrl + Shift + Delete)
- Hard refresh the page (Ctrl + F5)
