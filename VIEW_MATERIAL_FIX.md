# View Material Error Fix - October 6, 2025

## Issue Identified
**Error**: Duplicate entry '6-50' for key 'uniq_watch' in student_video_progress table  
**Location**: `view_material.php` line 40

## Root Cause
The video watch logging system was using a regular `INSERT` statement which failed when:
- A student viewed the same video material more than once
- The `student_video_progress` table has a unique constraint on (student_id, material_id)
- Each subsequent view attempt caused a fatal error

## Solution Applied

### Files Fixed:
1. ✅ `view_material.php` (student view)
2. ✅ `admin/view_material.php` (admin view)

### Change Made:
```php
// BEFORE (causes error on duplicate):
INSERT INTO student_video_progress (student_id, material_id) VALUES (?, ?)

// AFTER (handles duplicates gracefully):
INSERT IGNORE INTO student_video_progress (student_id, material_id) VALUES (?, ?)
```

## How It Works Now

### INSERT IGNORE Behavior:
- ✅ **First View**: Record is inserted successfully
- ✅ **Subsequent Views**: Duplicate is silently ignored, no error
- ✅ **Page Loads**: Material displays without errors
- ✅ **Progress Tracking**: Still tracks first view accurately

### Alternative Considered:
Could have used `INSERT ... ON DUPLICATE KEY UPDATE` but since we only need to track "watched/not watched" (not view count), `INSERT IGNORE` is simpler and more efficient.

## Testing Checklist

### Test Cases:
- [ ] Student views a video for the first time → Should log progress
- [ ] Student views the same video again → Should work without errors
- [ ] Admin views a video (if role='student') → Should log progress
- [ ] Admin views a video (if role='admin') → Should NOT log progress
- [ ] PDF material view → Should work (no logging)
- [ ] Simulation material view → Should work (no logging)

## Database Schema

The `student_video_progress` table has:
```sql
CREATE TABLE student_video_progress (
  student_id INT,
  material_id INT,
  watched_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_watch (student_id, material_id)
);
```

The unique constraint prevents duplicate tracking, which is correct behavior.

## Impact

### Before Fix:
❌ Fatal error on second video view  
❌ Students couldn't rewatch videos  
❌ Poor user experience  

### After Fix:
✅ Students can view videos multiple times  
✅ No errors on page load  
✅ Progress still tracked correctly  
✅ Better user experience  

## Additional Notes

### Why Track Video Progress?
- Shows which students are engaging with video materials
- Helps identify popular/unpopular content
- Supports progress reporting in dashboard
- Can be used for completion requirements

### Future Enhancement Ideas:
1. Track view count (how many times watched)
2. Track watch duration (how long they watched)
3. Track completion percentage (did they finish?)
4. Add last_viewed_at timestamp

## Files Modified

```
c:\xampp\htdocs\SkillSync\view_material.php
c:\xampp\htdocs\SkillSync\admin\view_material.php
```

**Status**: ✅ Fixed and Ready for Testing

---

**Fix Date**: October 6, 2025  
**Issue**: Duplicate entry error on video rewatch  
**Resolution**: Changed INSERT to INSERT IGNORE
