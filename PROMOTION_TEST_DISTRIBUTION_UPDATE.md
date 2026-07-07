# Level Promotion Test - Question Distribution Update

## Change Summary

Updated the level promotion test question distribution to better assess student readiness.

## Old Distribution (Before)
- **10 questions per topic**: 7 Target Level + 3 Expert Level
- Example for Beginner → Intermediate: 7 Intermediate + 3 Expert

**Issue**: Too difficult - jumping from Beginner to Expert questions was too harsh.

## New Distribution (Current)
- **10 questions per topic**: 7 Target Level + 3 Current Level

### For Beginner → Intermediate Promotion:
- **7 Intermediate questions** - Tests understanding of new concepts
- **3 Beginner questions** - Verifies mastery of fundamentals

### For Intermediate → Expert Promotion:
- **7 Expert questions** - Tests advanced concepts
- **3 Intermediate questions** - Confirms solid foundation

## Rationale

This distribution:
1. ✅ **Tests readiness for next level** (70% of questions)
2. ✅ **Validates mastery of current level** (30% of questions)
3. ✅ **More balanced and fair** assessment
4. ✅ **Ensures students don't advance with gaps** in basic knowledge

## Pass Threshold
- Still **77% or higher** required
- With this mix, students need to:
  - Understand most intermediate concepts (≈5-6 of 7 correct)
  - Master all beginner basics (all 3 correct)

## Code Changes

**File**: `level_promotion_test.php` (Lines 77-120)

```php
// OLD: Fetched target level + expert level
// NEW: Fetches target level + current level

// 7 questions from TARGET level (level they want to reach)
$stmt = $conn->prepare("
    SELECT * FROM questions
    WHERE topic_id = ? AND class_level = ?
    ORDER BY RAND()
    LIMIT 7
");
$stmt->bind_param("is", $tid, $targetLevel); // Intermediate or Expert

// 3 questions from CURRENT level (verify they still know basics)
$stmt = $conn->prepare("
    SELECT * FROM questions
    WHERE topic_id = ? AND class_level = ?
    ORDER BY RAND()
    LIMIT 3
");
$stmt->bind_param("is", $tid, $currentLevel); // Beginner or Intermediate
```

## Impact

Students will find the promotion test:
- ✅ More fair and balanced
- ✅ Better reflection of readiness
- ✅ Still challenging but achievable
- ✅ Validates both progress AND fundamentals

## Updated Instructions

The test page now shows:
> **Question Distribution per topic:** 7 Intermediate questions + 3 Beginner questions
> 
> This tests both your understanding of new concepts and mastery of basics

---

**Status**: ✅ Implemented and tested
**Date**: October 2, 2025
