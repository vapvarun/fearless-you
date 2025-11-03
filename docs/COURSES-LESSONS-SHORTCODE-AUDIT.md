# Courses & Lessons Shortcode Audit
**Date:** November 3, 2025
**Scope:** All LearnDash Courses, Lessons, and Topics

---

## Executive Summary

| Type | Total | With Shortcodes | With Orphaned |
|------|-------|----------------|---------------|
| **Courses** | 62 | 13 | 2 |
| **Lessons** | 913 | 7 | 7 |
| **Topics** | 147 | 0 | 0 |
| **Total Orphaned** | - | - | **19 instances** |

---

## 🔴 CRITICAL ISSUES

### Courses with Orphaned Shortcodes

#### 1. From Concept to Outline (ID: 221561)
**URL:** http://you.local/courses/from-concept-to-outline/
**Issue:** Divi Builder shortcodes (4 orphaned)
**Status:** ❌ BROKEN - Page likely looks broken or shows raw shortcode text

**Orphaned Shortcodes:**
- `[et_pb_section]` - Divi page builder section
- `[et_pb_row]` - Divi page builder row
- `[et_pb_column]` - Divi page builder column
- `[et_pb_text]` - Divi page builder text module

**Root Cause:** Divi Builder plugin is not active or was removed
**Action Required:**
- [ ] Rebuild course description using WordPress block editor
- [ ] OR activate Divi Builder plugin if it's needed site-wide
- [ ] Priority: HIGH

---

#### 2. Master Coach Mindset | Season 3 (ID: 339)
**URL:** http://you.local/courses/season-3/
**Issue:** Divi Builder shortcodes (4 orphaned)
**Status:** ❌ BROKEN

**Orphaned Shortcodes:**
- `[et_pb_section]` - Divi page builder section
- `[et_pb_row]` - Divi page builder row
- `[et_pb_column]` - Divi page builder column
- `[et_pb_text]` - Divi page builder text module

**Root Cause:** Divi Builder plugin is not active
**Action Required:**
- [ ] Rebuild course description using WordPress block editor
- [ ] OR activate Divi Builder if needed
- [ ] Priority: HIGH (legacy course, may be actively used)

---

### Lessons with Orphaned Shortcodes

#### 3. How to Get Over Fear of Change (ID: 1513)
**URL:** http://you.local/modules/how-to-get-over-fear-of-change/
**Issue:** Thrive Builder shortcodes (6 orphaned)
**Status:** ❌ BROKEN

**Orphaned Shortcodes:**
- `[tcb_post_list]` - Thrive Content Builder post list
- `[tcb_post_list_dynamic_style]` - Thrive dynamic styling
- `[tcb_the_id]` - Thrive ID output
- `[tcb_featured_image_url]` - Thrive featured image
- `[tcb_post_title]` - Thrive post title
- `[tcb_post_the_permalink]` - Thrive permalink

**Root Cause:** Thrive Architect/Content Builder plugin not active
**Action Required:**
- [ ] Rebuild lesson content without Thrive
- [ ] Check if other lessons use Thrive Builder
- [ ] Priority: HIGH

---

#### 4. Overview: 1-on-1 Audio Review Session with Rhonda (ID: 222870)
**URL:** http://you.local/modules/1-on-1-client-study-session-review-with-rhonda-pdf/
**Issue:** Divi Module Builder shortcode (1 orphaned)
**Status:** ❌ BROKEN

**Orphaned Shortcode:**
- `[dsm_button]` - Divi Supreme Modules button

**Root Cause:** Divi Supreme Modules plugin not active
**Action Required:**
- [ ] Replace with standard WordPress button block
- [ ] Priority: MEDIUM

---

#### 5. Q&A Your Questions Answered (ID: 224631)
**URL:** http://you.local/courses/how-to-say-good-bye-to-imposter-syndrome/modules/qa-your-questions-answered-7/
**Course:** How to Say Good-Bye to Imposter Syndrome
**Issue:** Numeric shortcodes (false positives)
**Status:** ⚠️ LIKELY FALSE POSITIVE

**"Shortcodes":**
- `[00]` - Not a real shortcode, likely timestamp or formatting
- `[01]` - Not a real shortcode, likely timestamp or formatting

**Action Required:**
- [ ] Review lesson content
- [ ] If these are timestamps, consider reformatting
- [ ] Priority: LOW

---

#### 6. Session 12: The Fearless Path (ID: 224119)
**URL:** http://you.local/modules/session-12-the-fearless-path/
**Issue:** Numeric shortcode (false positive)
**Status:** ⚠️ FALSE POSITIVE

**"Shortcode":**
- `[225141]` - Likely a document/resource ID, not a shortcode

**Action Required:**
- [ ] Review and reformat if needed
- [ ] Priority: LOW

---

#### 7. Welcome Packet (ID: 224101)
**URL:** http://you.local/modules/welcome-packet/
**Issue:** Numeric shortcode (false positive)
**Status:** ⚠️ FALSE POSITIVE

**"Shortcode":**
- `[224121]` - Likely a document/resource ID, not a shortcode

**Action Required:**
- [ ] Review and reformat if needed
- [ ] Priority: LOW

---

## ✅ WORKING COURSES (No Action Needed)

The following courses have shortcodes that are working correctly:

### Using LCCP Checklist (Working)
- Courage to Choose (228149) - `[checklist_in_post]` ✅
- Creating Space for Growth (228157) - `[checklist_in_post]` ✅
- Discover Your Inner Guide (227806) - `[checklist_in_post]` ✅
- Embracing the Unknown (228132) - `[checklist_in_post]` ✅
- How to Say Good-Bye to Imposter Syndrome (222141) - `[checklist_in_post]` ✅
- Owning Your Worth (228127) - `[checklist_in_post]` ✅
- Reclaiming Your Confidence (228117) - `[checklist_in_post]` ✅
- Rising from Setbacks (228122) - `[checklist_in_post]` ✅
- The Gratitude Magnet (228137) - `[checklist_in_post]` ✅
- The Power of Now (228102) - `[checklist_in_post]` ✅
- The Stories We Carry (226779) - `[checklist_in_post]` ✅

### Using WordPress Core Audio (Working)
- Getting Your Needs Met with Rhonda Britten (1923) - `[audio]` ✅
- How to Get Over Fear of Being Judged (1515) - `[audio]` ✅

---

## Root Cause Analysis

### Divi Builder Issues
**Affected:** 2 courses (8 orphaned shortcodes)
**Problem:** Divi Builder plugin is not active
**Courses:**
- From Concept to Outline (221561)
- Master Coach Mindset | Season 3 (339)

**Options:**
1. **Activate Divi Builder** - If site uses Divi extensively
2. **Rebuild Course Descriptions** - Convert to block editor (recommended)
3. **Export/Import Content** - May lose formatting

### Thrive Architect Issues
**Affected:** 1 lesson (6 orphaned shortcodes)
**Problem:** Thrive Architect/Content Builder plugin not active
**Lesson:** How to Get Over Fear of Change (1513)

**Options:**
1. **Activate Thrive Architect** - If other content uses it
2. **Rebuild Lesson** - Convert to block editor (recommended)

### Divi Supreme Modules Issues
**Affected:** 1 lesson (1 orphaned shortcode)
**Problem:** Divi Supreme Modules plugin not active
**Lesson:** Overview: 1-on-1 Audio Review Session (222870)

**Options:**
1. **Replace Button** - Use WordPress button block (easiest)
2. **Activate Plugin** - If needed for other content

---

## Recommendations

### Priority 1: Fix Broken Course Descriptions (HIGH)
These courses show broken content to students:
1. **From Concept to Outline** (221561)
2. **Master Coach Mindset | Season 3** (339)

**Action:** Rebuild course descriptions using WordPress block editor

### Priority 2: Fix Broken Lesson Content (HIGH)
1. **How to Get Over Fear of Change** (1513) - Complex Thrive Builder layout

**Action:** Rebuild lesson content or activate Thrive Architect

### Priority 3: Simple Fixes (MEDIUM)
1. **1-on-1 Audio Review Session** (222870) - Replace button shortcode

**Action:** Replace `[dsm_button]` with WordPress button block

### Priority 4: Review False Positives (LOW)
1. **Q&A Your Questions Answered** (224631) - Numeric timestamps
2. **Session 12: The Fearless Path** (224119) - Resource ID
3. **Welcome Packet** (224101) - Resource ID

**Action:** Review content formatting, likely no action needed

---

## Implementation Plan

### Step 1: Quick Assessment (30 min)
```bash
# View course content to assess damage
wp post get 221561 --field=content
wp post get 339 --field=content
wp post get 1513 --field=content
wp post get 222870 --field=content
```

### Step 2: Fix Simple Issues (1-2 hours)
```bash
# Replace Divi button with WordPress button
# Edit lesson 222870 manually in block editor
```

### Step 3: Rebuild Course Descriptions (2-4 hours)
- Course 221561: Rebuild description
- Course 339: Rebuild description

### Step 4: Rebuild Complex Lesson (2-4 hours)
- Lesson 1513: Rebuild Thrive Content layout

### Step 5: Test Everything (1-2 hours)
- [ ] View each course as student
- [ ] Verify no raw shortcodes visible
- [ ] Check enrollment/purchase flows still work

**Total Estimated Time:** 6-13 hours

---

## Technical Notes

### Check for More Divi/Thrive Content
```bash
# Search all lessons for Divi shortcodes
wp post list --post_type=sfwd-lessons --field=ID | xargs -I % wp post get % --field=content | grep -c "et_pb_"

# Search for Thrive shortcodes
wp post list --post_type=sfwd-lessons --field=ID | xargs -I % wp post get % --field=content | grep -c "tcb_"

# Search for Divi Supreme
wp post list --post_type=sfwd-lessons --field=ID | xargs -I % wp post get % --field=content | grep -c "dsm_"
```

### Page Builder Decision
**If considering reactivating page builders:**
- **Divi Builder** - Heavy plugin, impacts site performance
- **Thrive Architect** - Another heavy plugin
- **Recommendation:** Convert to native WordPress blocks for better performance and compatibility

---

## Client Questions

1. **Are these courses still actively used?**
   - From Concept to Outline
   - Master Coach Mindset | Season 3

2. **Do you have backups of the original formatted content?**
   - May help with rebuilding

3. **Were you previously using Divi/Thrive Architect site-wide?**
   - Determines if we should check other content types

4. **Can these course descriptions be simplified?**
   - Maybe just need basic text, not complex layouts

---

## Summary Statistics

- **🔴 Broken Courses:** 2 (Divi Builder issues)
- **🔴 Broken Lessons:** 3 (Thrive + Divi issues)
- **⚠️ False Positives:** 3 (Numeric references)
- **✅ Working Content:** 11 courses + 2 lessons
- **⏱️ Estimated Fix Time:** 6-13 hours

---

**Status:** Awaiting client review
**Priority:** HIGH for active courses
**Next Step:** Verify which courses are actively enrolled/being used
