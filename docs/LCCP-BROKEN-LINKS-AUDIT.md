# LCCP Systems - Broken Admin Links Audit

**Date:** October 28, 2025
**Issue:** Multiple broken admin page links throughout the plugin

---

## Summary

Found **15+ broken admin page links** that reference non-existent admin pages. These will cause 404 errors or blank pages when clicked.

---

## Registered Admin Pages (Actually Work)

These are the ONLY admin pages that actually exist:

1. ✅ `lccp-systems` - Main settings page
2. ✅ `lccp-settings` - Settings manager page
3. ✅ `lccp-module-manager` - Module management page
4. ✅ `lccp-roles` - Roles & capabilities page
5. ✅ `lccp-events-integration` - Events integration settings
6. ✅ `lccp-membership-roles` - Membership roles (module disabled)
7. ✅ `dasher-pc-dashboard` - Program Candidate dashboard
8. ✅ `dasher-document-converter` - Document converter (module disabled)
9. ✅ `dasher-dashboard-settings` - Dashboard settings
10. ❌ `lccp-dasher` - Student dashboard (file never loaded, effectively broken)

---

## Broken Links Found

### 1. Enhanced Dashboards Widget (class-enhanced-dashboards.php)

**File:** `includes/class-enhanced-dashboards.php`

**Broken links:**
- ❌ `admin.php?page=lccp-reports` - "View Detailed Reports" button
- ❌ `admin.php?page=lccp-export` - "Export Data" button
- ❌ `admin.php?page=lccp-student-details&student_id=X` - "View Progress" links
- ❌ `admin.php?page=lccp-my-courses` - "View All Courses" button
- ❌ `admin.php?page=lccp-log-hours` - "Log Hours" button

**Impact:** These buttons appear in dashboard widgets but all lead to non-existent pages.

---

### 2. Hour Tracker Module (class-hour-tracker.php)

**File:** `includes/class-hour-tracker.php`

**Broken link:**
- ❌ `admin.php?page=lccp-hour-tracker` - Referenced but page never registered

**Impact:** Any links to hour tracker admin page will fail.

---

### 3. Performance Optimizer (class-performance-optimizer.php)

**File:** `includes/class-performance-optimizer.php`

**Broken links:**
- ❌ `admin.php?page=lccp-performance&action=clean_db` - "Clean database" button
- ❌ `admin.php?page=lccp-performance&action=clear_cache` - "Clear cache" button
- ❌ `admin.php?page=lccp-performance&action=optimize_tables` - "Optimize database" button

**Impact:** Performance optimization buttons don't work. The render method exists but is never hooked to an admin page.

---

### 4. Module Manager (class-module-manager.php)

**File:** `includes/class-module-manager.php`

**Broken link:**
- ❌ `admin.php?page=lccp-module-settings` - Link to module settings page

**Impact:** Link to dedicated module settings page doesn't work.

---

### 5. Dasher Dashboard (class-dasher.php)

**File:** `modules/class-dasher.php`

**Broken links:**
- ❌ `admin.php?page=lccp-hour-tracker` - "View Hour Log" button
- ❌ `admin.php?page=lccp-messages` - "View Messages" button

**Impact:** Entire dasher module is never loaded, so page won't render anyway.

**Note:** File instantiates itself at bottom but is never included by module manager.

---

### 6. Template Files (Wrong Page Slug)

**Files:**
- `templates/mentor-dashboard.php`
- `templates/big-bird-dashboard-enhanced.php`

**Broken link:**
- ❌ `admin.php?page=lccp-pc-dashboard` - Should be `dasher-pc-dashboard`

**Impact:** Links to PC dashboard use wrong slug (missing "dasher-" prefix).

---

## Recommended Actions

### Priority 1: Remove Broken Links from Active Widgets

The Enhanced Dashboards module is **active and visible** to users. Remove these broken buttons:

1. **File:** `includes/class-enhanced-dashboards.php`
2. **Lines to fix:**
   - Remove "View Detailed Reports" button (~line with lccp-reports)
   - Remove "Export Data" button (~line with lccp-export)
   - Remove "View Progress" links (~line with lccp-student-details)
   - Remove "View All Courses" button (~line with lccp-my-courses)
   - Remove "Log Hours" button (~line with lccp-log-hours)

### Priority 2: Fix PC Dashboard Links

**Files to fix:**
- `templates/mentor-dashboard.php`
- `templates/big-bird-dashboard-enhanced.php`

**Change:** Replace `lccp-pc-dashboard` with `dasher-pc-dashboard`

### Priority 3: Document Dead Code

**Files with unreachable code:**
- `modules/class-dasher.php` - Never loaded, entire file is dead code
- `includes/class-performance-optimizer.php` - Render method never hooked

---

## Root Causes

1. **No admin page registration** - Links reference pages that were never created
2. **Module never loaded** - class-dasher.php instantiates but file never included
3. **Incomplete implementation** - Buttons added but handlers never implemented
4. **Wrong page slugs** - Inconsistent naming (lccp- vs dasher- prefixes)

---

## Testing Checklist

After fixes, verify these pages work:

- [ ] LCCP Systems main page (`admin.php?page=lccp-systems`)
- [ ] Settings page (`admin.php?page=lccp-settings`)
- [ ] Module Manager (`admin.php?page=lccp-module-manager`)
- [ ] Roles page (`admin.php?page=lccp-roles`)
- [ ] Events Integration (`admin.php?page=lccp-events-integration`)
- [ ] PC Dashboard with correct slug (`admin.php?page=dasher-pc-dashboard`)

All dashboard widgets should have no broken buttons.

---

## Status: Requires Fix

**Next Step:** Remove or fix all broken links identified above.
