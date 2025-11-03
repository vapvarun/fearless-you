# Shortcode Audit Report
**Generated:** 2025-11-03
**Site:** Fearless You

---

## Executive Summary

- **Total Pages Scanned:** 44
- **Pages with Shortcodes:** 23
- **Total Unique Shortcodes Found:** 31
- **Registered (Active) Shortcodes:** 104
- **Orphaned Shortcodes:** 22 unique shortcodes (25 instances)

---

## Pages with Orphaned Shortcodes (REQUIRES ACTION)

### 1. Big Bird Dashboard (ID: 229248)
**URL:** http://you.local/lccp-dashboard/bigbird-dashboard/
**Status:** ❌ BROKEN - Shortcode not working

**Orphaned Shortcodes:**
- `[lccp_bigbird_dashboard]` - NOT REGISTERED
  - **Issue:** Code exists in LCCP Systems plugin but shortcode is not registering
  - **Location:** `/lccp-systems/modules/class-dashboards-module.php` line 77
  - **Action:** Check LCCP module settings - dashboards module may not be loading correctly

---

### 2. BigBird Dashboard (ID: 229222)
**URL:** http://you.local/bigbird-dashboard/
**Status:** ⚠️ PARTIALLY WORKING

**Shortcodes:**
- ❌ `[dasher_bigbird_dashboard]` - NOT REGISTERED (ORPHANED)
- ✅ `[ld_course_list]` - ACTIVE (LearnDash)

**Action:** Replace `[dasher_bigbird_dashboard]` with working alternative

---

### 3. BigBird Dashboard (ID: 228352)
**URL:** http://you.local/dashboard-bb/
**Status:** ❌ BROKEN

**Orphaned Shortcodes:**
- `[dasher_bigbird_dashboard]` - NOT REGISTERED

**Action:** Consolidate with other BigBird dashboard pages or fix shortcode

---

### 4. Courses (ID: 224639)
**URL:** http://you.local/courses/
**Status:** ❌ BROKEN

**Orphaned Shortcodes:**
- `[phunk_courses_by_category]` - NOT REGISTERED
  - **Issue:** No plugin found providing this shortcode
  - **Action:** Replace with `[learndash_course_grid]` or `[ld_course_list]`

---

### 5. Current Programs (ID: 220068)
**URL:** http://you.local/current-programs/
**Status:** ⚠️ FALSE POSITIVES - Not actual shortcodes

**Orphaned "Shortcodes":**
- `[7347]`, `[7029]`, `[7253]`, `[7035]` - These are WP Fusion tag IDs in HTML attributes, not shortcodes

**Action:** None required - these are part of Block Visibility settings, not actual shortcodes

---

### 6. Document Library (ID: 218999)
**URL:** http://you.local/document-library/
**Status:** ❌ BROKEN

**Orphaned Shortcodes:**
- `[doc_library]` - NOT REGISTERED
  - **Issue:** No plugin found providing this shortcode
  - **Note:** LCCP Systems has document manager module but it's currently DISABLED
  - **Action:** Enable "Document Manager" module in LCCP Systems OR implement alternative

---

### 7. Hour Submission (ID: 229219)
**URL:** http://you.local/hour-submission/
**Status:** ❌ BROKEN

**Orphaned Shortcodes:**
- `[lccp-hour-form]` - NOT REGISTERED
  - **Issue:** Code exists in LCCP Systems but shortcode not registering
  - **Location:** `/lccp-systems/modules/class-hour-tracker-module.php`
  - **Module Status:** Hour Tracker is ENABLED but shortcodes not registering
  - **Action:** Debug LCCP hour tracker module loading

---

### 8. Instructor Dashboard (ID: 225034)
**URL:** http://you.local/instructor-dashboard/
**Status:** ⚠️ FALSE POSITIVES

**Orphaned "Shortcodes":**
- `[0]`, `[data-active]`, `[data-completed]`, `[rel]`, `[offset]`, `[data-selected]`
  - **Issue:** These are CSS selectors in the page HTML, not shortcodes
  - **Action:** Clean up page HTML/CSS to avoid confusion

---

### 9. LCCP Test Page (ID: 229251)
**URL:** http://you.local/lccp-test-page/
**Status:** ❌ BROKEN

**Orphaned Shortcodes:**
- `[lccp-hour-widget]` - NOT REGISTERED
- `[lccp-hour-form]` - NOT REGISTERED
- `[lccp-hour-log]` - NOT REGISTERED
  - **Issue:** Hour tracker module enabled but shortcodes not registering
  - **Action:** Debug LCCP hour tracker module

---

### 10. Mentor Dashboard (ID: 228351, 229221)
**URL:** http://you.local/dashboard-m/ and http://you.local/mentor-dashboard/
**Status:** ❌ BROKEN

**Orphaned Shortcodes:**
- `[dasher_mentor_dashboard]` - NOT REGISTERED
  - **Note:** There's a working page with `[lccp_mentor_dashboard]` at ID 229249
  - **Action:** Update these pages to use `[lccp_mentor_dashboard]` instead

---

### 11. PC Dashboard (ID: 229223)
**URL:** http://you.local/pc-dashboard/
**Status:** ❌ BROKEN

**Orphaned Shortcodes:**
- `[dasher_pc_dashboard]` - NOT REGISTERED
  - **Note:** There's a working page with `[lccp_pc_dashboard]` at ID 229247
  - **Action:** Update to use `[lccp_pc_dashboard]` instead

---

### 12. Replays (ID: 78)
**URL:** http://you.local/replays/
**Status:** ⚠️ PARTIALLY WORKING

**Shortcodes:**
- ✅ `[ld_lesson_list]` - ACTIVE (LearnDash)
- ✅ `[ld_topic_list]` - ACTIVE (LearnDash)
- ❌ `[memb_has_any_tag]` - NOT REGISTERED
  - **Issue:** No plugin found providing this shortcode
  - **Action:** Replace with WP Fusion conditional shortcodes `[wpf]` or Block Visibility

---

### 13. Terms of Use (ID: 7)
**URL:** http://you.local/terms-of-use/
**Status:** ⚠️ FALSE POSITIVE

**Orphaned "Shortcodes":**
- `[at]` - Likely part of an email address, not a shortcode
  - **Action:** Review page content, probably no action needed

---

### 14. Your Checklists (ID: 225077)
**URL:** http://you.local/your-checklists/
**Status:** ❌ BROKEN

**Orphaned Shortcodes:**
- `[user_checklists]` - NOT REGISTERED
  - **Note:** LCCP Systems has checklist module with `[lccp_checklist]` shortcode
  - **Action:** Update to use `[lccp_checklist]` or create custom shortcode

---

## Pages with Working Shortcodes (No Action Needed)

### ✅ Faculty Dashboard (ID: 229366)
- `[fys_faculty_dashboard]` - Fearless You Systems

### ✅ LCCP Dashboard (ID: 229246)
- `[lccp_dashboard]` - LCCP Systems

### ✅ Love Notes Setup (ID: 221194)
- `[user_meta]` - WP Fusion

### ✅ Mentor Dashboard (ID: 229249)
- `[lccp_mentor_dashboard]` - LCCP Systems

### ✅ My Dashboard (ID: 229365)
- `[lccp_dashboard]` - LCCP Systems

### ✅ Program Coordinator Dashboard (ID: 229247)
- `[lccp_pc_dashboard]` - LCCP Systems

### ✅ Student Dashboard (ID: 229218, 229250)
- `[lccp_student_dashboard]` - LCCP Systems
- `[ld_course_list]` - LearnDash

---

## Active Plugins Providing Shortcodes

### LCCP Systems (9 shortcodes - Some not working)
**Status:** ✅ Plugin Active, ⚠️ Some modules may have issues

**Working Shortcodes:**
- `[lccp_dashboard]`
- `[lccp_big_bird_dashboard]`
- `[lccp_mentor_dashboard]`
- `[lccp_pc_dashboard]`
- `[lccp_student_dashboard]`
- `[lccp_checklist]`
- `[lccp_events]`
- `[lccp_event_calendar]`
- `[checklist_in_post]`

**NOT Working (Code exists but not registering):**
- `[lccp_bigbird_dashboard]` - Alias, should work but doesn't
- `[dasher_bigbird_dashboard]` - Backward compatibility, not registering
- `[dasher_mentor_dashboard]` - Backward compatibility, not registering
- `[dasher_pc_dashboard]` - Backward compatibility, not registering
- `[lccp-hour-form]` - Hour tracker module issue
- `[lccp-hour-widget]` - Hour tracker module issue
- `[lccp-hour-log]` - Hour tracker module issue

### LearnDash (16+ shortcodes)
**Status:** ✅ All Working

Key shortcodes:
- `[ld_course_list]` - Course listings
- `[ld_lesson_list]` - Lesson listings
- `[ld_profile]` - User profile
- `[learndash_course_grid]` - Course grid display

### WP Fusion (9 shortcodes)
**Status:** ✅ All Working

Key shortcodes:
- `[wpf]` - Conditional content
- `[user_meta]` - Display user metadata
- `[wpf_loggedin]` / `[wpf_loggedout]` - Conditional display

### Fearless You Systems (3 shortcodes)
**Status:** ✅ All Working
- `[fys_faculty_dashboard]`
- `[fys_member_dashboard]`
- `[fys_ambassador_dashboard]`

### Other Active Plugins
- **Gravity Forms:** `[gravityform]`, `[gravityforms]`
- **The Events Calendar:** `[tribe:event-details]`, `[tec_event_qr]`
- **Uncanny Toolkit:** `[uo_courses]`, `[uo_lessons_topics_grid]`
- **BuddyBoss:** `[chat]`, `[bookmarks]`
- **Fivo Docs:** `[fivo_docs]`

---

## Root Cause Analysis

### LCCP Systems Module Issues

The LCCP Systems plugin uses a modular system where features can be enabled/disabled. Current status:

**Enabled Modules:**
- ✅ Dashboards (but some shortcodes not registering)
- ✅ Hour Tracker (but shortcodes not registering)
- ✅ Checklist
- ✅ Events Integration

**Disabled Modules:**
- ❌ Document Manager (explains missing `[doc_library]`)
- ❌ Membership Roles

**Issue:** Even though modules are marked as "enabled", some shortcodes are not registering. This suggests:
1. Module initialization error
2. Self-test failure causing auto-disable
3. Missing dependency

**Check:** `/wp-admin/admin.php?page=lccp-module-manager`

---

## Recommended Actions

### Priority 1: Critical Fixes (Pages Currently Broken)

1. **Fix LCCP Hour Tracker Shortcodes**
   - Pages affected: 229219, 229251
   - Action: Debug why hour tracker module shortcodes aren't registering
   - Check LCCP module manager for errors

2. **Fix BigBird Dashboard**
   - Pages affected: 229248, 229222, 228352
   - Action: Debug dashboards module shortcode registration
   - Alternative: Use the working LCCP dashboard system

3. **Fix Courses Page**
   - Page affected: 224639
   - Action: Replace `[phunk_courses_by_category]` with `[learndash_course_grid]`

4. **Fix Document Library**
   - Page affected: 218999
   - Action: Enable LCCP Document Manager module OR implement alternative

### Priority 2: Update Legacy Shortcodes

1. **Mentor Dashboard Pages (228351, 229221)**
   - Replace `[dasher_mentor_dashboard]` with `[lccp_mentor_dashboard]`

2. **PC Dashboard Page (229223)**
   - Replace `[dasher_pc_dashboard]` with `[lccp_pc_dashboard]`

### Priority 3: Replace Missing Functionality

1. **Replays Page (78)**
   - Replace `[memb_has_any_tag]` with WP Fusion `[wpf]` shortcode

2. **Your Checklists Page (225077)**
   - Replace `[user_checklists]` with `[lccp_checklist]` or create custom

### Priority 4: Cleanup

1. **Instructor Dashboard (225034)**
   - Clean up HTML to remove CSS selectors being detected as shortcodes

---

## Debugging Steps for LCCP Issues

1. Go to: `/wp-admin/admin.php?page=lccp-module-manager`
2. Check if modules show "Active" or "Problem — Auto-disabled"
3. Check for error messages in WordPress admin
4. Review module error log: Check for `lccp_module_errors` transient
5. Try disabling and re-enabling affected modules
6. Check browser console and PHP error logs

---

## Summary by Status

**✅ Working Pages:** 10 pages
**⚠️ Partially Working:** 3 pages
**❌ Broken Pages:** 10 pages
**📝 False Positives:** 2 pages

**Total Issues:** 13 pages require attention
