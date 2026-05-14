# Seed Data Upload Guide

## Overview

The admin panel now includes a **Seed Data** feature that allows you to quickly populate the database with test reservations by uploading a JSON file. This is perfect for testing, demos, and development.

---

## How to Use

### 1. Access the Feature

1. Log into the admin panel (`app/admin/admin.php`)
2. Navigate to the **Reservations** tab
3. Look for the **"Seed Data"** button in the toolbar (next to the filter chips)

### 2. Prepare Your JSON File

Create a JSON file with the following structure:

```json
{
  "reservations": [
    {
      "name": "Customer Name",
      "phone": "555-0001",
      "people_count": 4,
      "table_id": 3,
      "reservation_date": "2026-05-20",
      "reservation_time": "18:00:00",
      "special_requests": "Optional notes",
      "status": "pending",
      "total_amount": 25.00
    }
  ]
}
```

### 3. Upload the File

1. Click the **"Seed Data"** button
2. Select your JSON file
3. The system will automatically:
   - Generate unique confirmation codes
   - Calculate VIP priority scores
   - Insert all reservations
   - Show success/error messages

---

## JSON Field Reference

| Field | Type | Required | Description | Example |
|-------|------|----------|-------------|---------|
| `name` | string | ✅ Yes | Customer full name | "Alice Johnson" |
| `phone` | string | ✅ Yes | Phone number (VIP lookup key) | "555-0001" |
| `people_count` | integer | ✅ Yes | Number of guests | 4 |
| `table_id` | integer | ❌ No | Table ID (1-12), null for no table | 3 |
| `reservation_date` | string | ✅ Yes | Date in YYYY-MM-DD format | "2026-05-20" |
| `reservation_time` | string | ✅ Yes | Time in HH:MM:SS format | "18:00:00" |
| `special_requests` | string | ❌ No | Customer notes/requests | "Window seat" |
| `status` | string | ❌ No | pending, confirmed, or cancelled | "pending" |
| `total_amount` | decimal | ❌ No | Total fee amount | 25.00 |

---

## Sample Data File

A sample file `seed_reservations_sample.json` is included with 20 test reservations. You can use it directly or modify it for your needs.

**Location:** `seed_reservations_sample.json` (root directory)

---

## Features

### Automatic Processing

✅ **Confirmation Codes**: Auto-generated (format: SKR-XXXXXX)  
✅ **Priority Scores**: Calculated based on VIP status  
✅ **Timestamps**: Automatically set to current time  
✅ **VIP Detection**: Checks phone number against VIP database  

### VIP Priority Calculation

The system automatically assigns priority scores:

- **Platinum VIP** (phone in VIP table): 1000
- **Gold VIP**: 2000
- **Silver VIP**: 3000
- **Bronze VIP**: 4000
- **Regular Customer**: 5000 + timestamp

### Error Handling

- Invalid JSON format → Error message displayed
- Missing required fields → Skips that reservation
- Database errors → Shows error for specific reservation
- Continues processing remaining reservations even if some fail

---

## Example Use Cases

### 1. Testing VIP Priority System

Create reservations with phone numbers that exist in `vip_customers` table:

```json
{
  "reservations": [
    {
      "name": "VIP Customer",
      "phone": "555-VIP-001",
      "people_count": 2,
      "table_id": 9,
      "reservation_date": "2026-05-20",
      "reservation_time": "19:00:00",
      "status": "pending",
      "total_amount": 50.00
    }
  ]
}
```

### 2. Load Testing

Upload 100+ reservations to test:
- Admin panel performance
- Priority queue sorting
- Search functionality
- Filter performance

### 3. Demo Data

Populate the system with realistic data for:
- Client demonstrations
- Screenshots
- Training sessions
- Feature showcases

---

## Tips & Best Practices

### Date Ranges

- Use future dates for active reservations
- Use past dates to test history tab
- Mix dates to test calendar functionality

### Table IDs

Valid table IDs: 1-12 (based on default seed data)
- Tables 1-2: 2 seats (₱12-15)
- Tables 3-5: 4 seats (₱25)
- Tables 6-8: 6 seats (₱35)
- Tables 9-10: 8 seats (₱50)
- Tables 11-12: 2 seats counter (₱12)

### Status Values

- `"pending"` - Appears in Reservations tab, awaiting confirmation
- `"confirmed"` - Appears in Reservations tab, table marked as reserved
- `"cancelled"` - Appears in History tab

### Phone Numbers

- Use consistent format (e.g., "555-XXXX")
- Same phone number = same customer (for VIP tracking)
- Use real VIP phone numbers to test priority

---

## Success Messages

After upload, you'll see:

✅ **Success**: "20 reservations inserted successfully."

✗ **Partial Success**: "15 reservations inserted successfully. Errors: [details]"

✗ **Failure**: "Invalid JSON format" or "File upload failed"

---

## Troubleshooting

### "Invalid JSON format"

- Check JSON syntax (use JSONLint.com)
- Ensure `"reservations"` array exists
- Verify all quotes are double quotes
- Check for trailing commas

### "File upload failed"

- Check file size (should be < 2MB)
- Ensure file extension is `.json`
- Verify file permissions

### Some reservations not inserted

- Check required fields are present
- Verify table_id exists (1-12)
- Check date format (YYYY-MM-DD)
- Check time format (HH:MM:SS)

### No priority scores

- Ensure VIPService class is loaded
- Check database connection
- Verify vip_customers table exists

---

## Database Impact

### Tables Affected

- `reservations` - New rows inserted
- `vip_customers` - Checked for VIP status (read-only)

### Not Affected

- `tables` - Status not changed during seed
- `pre_orders` - Not created by seed data
- `menu_items` - Not modified

---

## Advanced: Bulk VIP Testing

To test VIP system, first insert VIP customers:

```sql
INSERT INTO vip_customers (phone, name, vip_level, total_bookings, total_spent)
VALUES 
('555-0001', 'Alice Johnson', 'platinum', 25, 12500),
('555-0002', 'Bob Smith', 'gold', 15, 7800),
('555-0003', 'Carol Williams', 'silver', 8, 4200);
```

Then upload seed data with matching phone numbers to see priority sorting in action!

---

## File Size Limits

- **Recommended**: < 100 reservations per file
- **Maximum**: Limited by PHP upload_max_filesize (default 2MB)
- **Large datasets**: Split into multiple files

---

## Security Notes

- Only accessible to logged-in admins
- File type restricted to `.json`
- SQL injection protected (prepared statements)
- Input validation on all fields

---

## Quick Start

1. Download `seed_reservations_sample.json`
2. Go to Admin Panel → Reservations tab
3. Click "Seed Data" button
4. Select the sample file
5. See 20 test reservations appear instantly!

---

**Happy Testing! 🎉**
