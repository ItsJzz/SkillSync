# Quick Setup Guide - Level Promotion System

## Step 1: Create the Tracking Table (Optional but Recommended)

Run this SQL in your phpMyAdmin or MySQL client:

```sql
CREATE TABLE IF NOT EXISTS level_promotion_tests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    from_level ENUM('Beginner', 'Intermediate') NOT NULL,
    to_level ENUM('Intermediate', 'Expert') NOT NULL,
    score DECIMAL(5,2) NOT NULL,
    passed BOOLEAN NOT NULL,
    test_data JSON,
    taken_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES login_credentials(id) ON DELETE CASCADE,
    INDEX idx_student_level (student_id, from_level)
);
```

Or run: `mysql -u root skillsync < add_level_promotion_table.sql`

## Step 2: Update Existing Students

If you have existing students who are already showing "Intermediate" incorrectly, reset them:

```sql
UPDATE students 
SET assessment_details = JSON_SET(
    COALESCE(assessment_details, '{}'),
    '$.class_level', 'Beginner'
)
WHERE id > 0;
```

## Step 3: Test the System

1. **Login as a student** who has completed activities
2. **Check the dashboard**:
   - Should show "Beginner" level (not auto-promoted)
   - If progress is 100%, should see gold "Take Level Promotion Test" button
3. **Click the button** to start the promotion test
4. **Complete the test** with at least 77% to pass
5. **Return to dashboard**:
   - Should now show "Intermediate" level
   - Progress should be at 0%

## Step 4: Verify Database Changes

After a student passes the promotion test, check:

```sql
SELECT 
    id,
    JSON_EXTRACT(assessment_details, '$.class_level') as class_level,
    JSON_EXTRACT(assessment_details, '$.progress_to_next') as progress,
    JSON_EXTRACT(assessment_details, '$.level_promotion_date') as promotion_date
FROM students
WHERE id = <student_id>;
```

## Troubleshooting

### Issue: Dashboard still shows "Intermediate" without test
**Solution**: The `assessment_details` field needs to have `class_level` set. Run:
```sql
UPDATE students 
SET assessment_details = JSON_SET(
    COALESCE(assessment_details, '{}'),
    '$.class_level', 'Beginner'
)
WHERE JSON_EXTRACT(assessment_details, '$.class_level') IS NULL;
```

### Issue: Promotion button not showing
**Check**:
1. `$progressToNext` variable should be >= 100
2. `$classLevel` should be 'Beginner'
3. View page source and search for "promotion-test-alert"

### Issue: Test not saving results
**Check**:
1. Browser console for errors
2. Network tab to see if request to `save_level_promotion_test.php` succeeds
3. Check PHP error logs in `c:\xampp\apache\logs\error.log`

## Files Modified/Created

✅ **Modified**:
- `student_dashboard.php` - Fixed auto-promotion, added button

✅ **Created**:
- `level_promotion_test.php` - The promotion test
- `save_level_promotion_test.php` - Results handler
- `add_level_promotion_table.sql` - Table creation
- `LEVEL_PROMOTION_SYSTEM.md` - Documentation

## Next Steps

After confirming everything works:
1. Consider adding email notifications when students are ready for promotion
2. Add a results page showing detailed feedback
3. Implement retry cooldown (e.g., wait 24 hours between attempts)
4. Create Intermediate → Expert promotion test
