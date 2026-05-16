# Algorithm Testing Guide

## Overview
This testing suite evaluates the performance of all optimized algorithms in the sushi reservation system across different dataset sizes (100, 500, 1000 records) with 5 runs per test.

---

## Files Included

### 1. **algorithm_test.php**
Main testing file that runs all algorithm performance tests.

**Tests Included:**
- ✅ Hash Table (Insertion & Lookup)
- ✅ Priority Queue Min-Heap (VIP Management)
- ✅ VIP Service Hash Lookup
- ✅ Database Sorting (ORDER BY)
- ✅ Linear Search with Filtering

### 2. **generate_seed_data.php**
Generates test data files for bulk insertion testing.

**Generates:**
- `seed_test_100.json` - 100 test reservations
- `seed_test_500.json` - 500 test reservations
- `seed_test_1000.json` - 1000 test reservations

---

## How to Run Tests

### Step 1: Generate Seed Data (Optional)
If you want to manually test with seed files:

```bash
cd C:\xampp\htdocs\sushi_reservations
php generate_seed_data.php
```

This creates three JSON files with test data.

### Step 2: Run Algorithm Tests

1. **Start XAMPP** (Apache + MySQL)

2. **Open browser** and navigate to:
   ```
   http://localhost/sushi_reservations/app/algorithm_test.php
   ```

3. **Wait for tests to complete** (may take 1-2 minutes for large datasets)

4. **View results** showing:
   - Execution time in milliseconds (ms)
   - Success/failure status
   - Average times across 5 runs
   - Performance metrics for each algorithm

---

## Test Details

### Test 1: Hash Table (Dynamic Resizing)
- **Algorithm**: Hash Table with automatic resizing
- **Complexity**: O(1) average
- **Operations**:
  - Insert N reservations with confirmation codes
  - Lookup all reservations by confirmation code
- **Measures**: Insert time + Lookup time

### Test 2: Priority Queue (Min-Heap)
- **Algorithm**: Binary Min-Heap
- **Complexity**: O(log n)
- **Operations**:
  - Insert N reservations with priority scores
  - Extract top 50 highest priority reservations
- **Measures**: Insert time + Extract time

### Test 3: VIP Service Hash Lookup
- **Algorithm**: Hash-based customer lookup
- **Complexity**: O(1)
- **Operations**:
  - Create N VIP customers
  - Lookup all customers by phone number
- **Measures**: Create time + Lookup time

### Test 4: Sorting Algorithm
- **Algorithm**: Database ORDER BY (QuickSort/MergeSort)
- **Complexity**: O(n log n)
- **Operations**:
  - Sort N reservations by priority score
- **Measures**: Sort time

### Test 5: Search Algorithm
- **Algorithm**: Linear search with pattern matching
- **Complexity**: O(n)
- **Operations**:
  - Search for reservations by name pattern
- **Measures**: Search time + Results found

---

## Test Sizes

| Size | Records | Runs | Total Operations |
|------|---------|------|------------------|
| Small | 100 | 5 | 500 |
| Medium | 500 | 5 | 2,500 |
| Large | 1000 | 5 | 5,000 |

**Total Tests**: 75 test runs (3 sizes × 5 algorithms × 5 runs)

---

## Expected Results

### Hash Table Performance
- **100 records**: ~10-30 ms insert, ~5-15 ms lookup
- **500 records**: ~50-100 ms insert, ~20-40 ms lookup
- **1000 records**: ~100-200 ms insert, ~40-80 ms lookup

### Priority Queue Performance
- **100 records**: ~5-15 ms insert, ~2-5 ms extract
- **500 records**: ~25-50 ms insert, ~10-20 ms extract
- **1000 records**: ~50-100 ms insert, ~20-40 ms extract

### VIP Service Performance
- **100 records**: ~15-30 ms create, ~5-10 ms lookup
- **500 records**: ~75-150 ms create, ~25-50 ms lookup
- **1000 records**: ~150-300 ms create, ~50-100 ms lookup

### Sorting Performance
- **100 records**: ~5-15 ms
- **500 records**: ~20-50 ms
- **1000 records**: ~40-100 ms

### Search Performance
- **100 records**: ~2-5 ms
- **500 records**: ~10-20 ms
- **1000 records**: ~20-40 ms

*Note: Actual times may vary based on system performance*

---

## Understanding the Results

### Time Display
- All times shown in **milliseconds (ms)**
- Lower time = Better performance
- Each test runs **5 times** and shows average

### Status Indicators
- ✅ **Success** - Test completed without errors
- ❌ **Error** - Test failed (check database connection)

### Average Rows
- Highlighted in **yellow**
- Shows **average time** across 5 runs
- Most reliable performance indicator

---

## Troubleshooting

### Issue: "Database connection failed"
**Solution**: 
- Check XAMPP MySQL is running
- Verify database credentials in `app/config.php`
- Ensure `sushi_reservations` database exists

### Issue: "Tests taking too long"
**Solution**:
- Normal for 1000 records (1-2 minutes)
- Check system resources (CPU, RAM)
- Close other applications

### Issue: "Memory limit exceeded"
**Solution**:
- Edit `php.ini`: `memory_limit = 256M`
- Restart Apache
- Run tests again

### Issue: "Seed data not loading"
**Solution**:
- Run `generate_seed_data.php` first
- Check file permissions
- Verify JSON format is valid

---

## Manual Seed Data Upload

If you want to test the admin panel seed upload feature:

1. **Login to admin panel**:
   ```
   http://localhost/sushi_reservations/app/admin/admin.php
   Password: sakura2024
   ```

2. **Go to Reservations tab**

3. **Click "Seed Data" button**

4. **Upload one of these files**:
   - `seed_test_100.json`
   - `seed_test_500.json`
   - `seed_test_1000.json`

5. **View inserted reservations** in the table

---

## Comparing with Baseline

To compare optimized vs baseline performance:

1. **Checkout base branch**:
   ```bash
   git checkout base
   ```

2. **Run tests** and record times

3. **Checkout main branch**:
   ```bash
   git checkout main
   ```

4. **Run tests** again and compare

5. **Calculate improvement**:
   ```
   Improvement % = ((Baseline - Optimized) / Baseline) × 100
   ```

---

## Test Data Cleanup

The test automatically cleans up test data after completion. Test records are identified by:
- Email containing `@test.com`
- Phone numbers starting with `09`

To manually clean up:
```sql
DELETE FROM reservations WHERE email LIKE '%@test.com';
DELETE FROM vip_customers WHERE phone LIKE '09%';
```

---

## Performance Metrics to Record

For your documentation, record these metrics:

### For Each Test Size (100, 500, 1000):
1. **Average Insert Time** (ms)
2. **Average Lookup/Search Time** (ms)
3. **Average Sort Time** (ms)
4. **Average Extract Time** (ms)
5. **Success Rate** (should be 100%)

### Create a Table Like This:

| Input Size | Test Scenario | Time (ms) | Runs |
|------------|---------------|-----------|------|
| Small (100) | Hash Insert | 25.43 | 5 |
| Small (100) | Hash Lookup | 12.87 | 5 |
| Medium (500) | Hash Insert | 98.21 | 5 |
| Medium (500) | Hash Lookup | 45.33 | 5 |
| Large (1000) | Hash Insert | 187.65 | 5 |
| Large (1000) | Hash Lookup | 89.12 | 5 |

---

## Tips for Best Results

1. **Close other applications** to reduce system load
2. **Run tests multiple times** and use average
3. **Clear browser cache** before testing
4. **Use same environment** for baseline vs optimized comparison
5. **Record system specs** (CPU, RAM) for context
6. **Test at same time of day** to avoid system load variations

---

## Questions?

If you encounter issues:
1. Check XAMPP is running
2. Verify database connection
3. Check PHP error logs: `C:\xampp\php\logs\php_error_log`
4. Check Apache error logs: `C:\xampp\apache\logs\error.log`

---

## Summary

This testing suite provides comprehensive performance evaluation of all optimized algorithms:
- ✅ Automated testing across multiple dataset sizes
- ✅ Multiple runs for statistical accuracy
- ✅ Clear visual presentation of results
- ✅ Easy comparison between baseline and optimized
- ✅ Real-world performance metrics

Use these results to demonstrate the effectiveness of your algorithm optimizations in your Deliverable 3 documentation!
