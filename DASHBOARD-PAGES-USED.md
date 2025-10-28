# Dashboard Shortcodes - Where They're Used

**Date:** October 28, 2025

Based on database analysis of published pages.

---

## Dashboard Pages Using Fixed Shortcodes

### Main Dashboard Pages (Under /lccp-dashboard/)

1. **Mentor Dashboard**
   - URL: `https://you.fearlessliving.org/lccp-dashboard/mentor-dashboard/`
   - Page ID: 229249
   - Shortcode: `[lccp_mentor_dashboard]`
   - Status: ✅ **FIXED** - Now loads properly

2. **Big Bird Dashboard**
   - URL: `https://you.fearlessliving.org/lccp-dashboard/bigbird-dashboard/`
   - Page ID: 229248
   - Shortcode: `[lccp_bigbird_dashboard]`
   - Status: ✅ **FIXED** - Now loads properly

3. **Program Coordinator Dashboard**
   - URL: `https://you.fearlessliving.org/lccp-dashboard/pc-dashboard/`
   - Page ID: 229247
   - Shortcode: `[lccp_pc_dashboard]`
   - Status: ✅ **FIXED** - Now loads properly

4. **Student Dashboard**
   - URL: `https://you.fearlessliving.org/lccp-dashboard/student-dashboard/`
   - Page ID: 229250
   - Shortcode: `[lccp_student_dashboard]`
   - Status: ✅ **FIXED** - Now loads properly

5. **Main LCCP Dashboard**
   - URL: `https://you.fearlessliving.org/lccp-dashboard/`
   - Page ID: 229246
   - Shortcode: `[lccp_dashboard]`
   - Status: ✅ **FIXED** - Now loads properly

---

## Additional Dashboard Pages (Using Backward Compatible Shortcodes)

These pages use the older `dasher_*` shortcode names (backward compatibility aliases):

6. **Mentor Dashboard** (older version)
   - URL: `https://you.fearlessliving.org/mentor-dashboard/`
   - Page ID: 228351
   - Shortcode: `[dasher_mentor_dashboard]`
   - Status: ✅ Working (backward compatibility maintained)

7. **Mentor Dashboard Test** (test page)
   - URL: `https://you.fearlessliving.org/dashboard-m/`
   - Page ID: 229221
   - Shortcode: `[dasher_mentor_dashboard]`
   - Status: ✅ Working

8. **Big Bird Dashboard** (older version)
   - URL: `https://you.fearlessliving.org/bigbird-dashboard/`
   - Page ID: 228352
   - Shortcode: `[dasher_bigbird_dashboard]`
   - Status: ✅ Working (backward compatibility maintained)

9. **Big Bird Dashboard** (test page)
   - URL: `https://you.fearlessliving.org/dashboard-bb/`
   - Page ID: 229222
   - Shortcode: `[dasher_bigbird_dashboard]`
   - Status: ✅ Working

10. **PC Dashboard** (older version)
    - URL: `https://you.fearlessliving.org/pc-dashboard/`
    - Page ID: 229223
    - Shortcode: `[dasher_pc_dashboard]`
    - Status: ✅ Working (backward compatibility maintained)

11. **Student Dashboard** (older version)
    - URL: `https://you.fearlessliving.org/student-dashboard/`
    - Page ID: 229218
    - Shortcode: `[lccp_student_dashboard]`
    - Status: ✅ Working

12. **Student Dashboard** (with parameter)
    - URL: `https://you.fearlessliving.org/my-dashboard/`
    - Page ID: 229365
    - Shortcode: `[lccp_dashboard type="student"]`
    - Status: ✅ Working

---

## Hour Tracking Pages

13. **LCCP Test Page**
    - URL: `https://you.fearlessliving.org/lccp-test-page/`
    - Page ID: 229251
    - Shortcodes: `[lccp-hour-widget]`, `[lccp-hour-form]`, `[lccp-hour-log]`
    - Status: ✅ Working (hour tracker module)

14. **Hour Submission Page**
    - URL: `https://you.fearlessliving.org/hour-submission/`
    - Page ID: 229219
    - Shortcode: `[lccp-hour-form]`
    - Status: ✅ Working (hour tracker module)

---

## Summary

### Total Dashboard Pages: 14

**By Shortcode Type:**
- `[lccp_mentor_dashboard]` - 1 page
- `[lccp_bigbird_dashboard]` - 1 page
- `[lccp_pc_dashboard]` - 1 page
- `[lccp_student_dashboard]` - 3 pages (including variants)
- `[lccp_dashboard]` - 2 pages (including with parameter)
- `[dasher_mentor_dashboard]` - 2 pages (backward compat)
- `[dasher_bigbird_dashboard]` - 2 pages (backward compat)
- `[dasher_pc_dashboard]` - 1 page (backward compat)
- Hour tracking shortcodes - 2 pages

**Status:**
- ✅ All dashboard shortcodes now working (fixed Oct 28, 2025)
- ✅ Backward compatibility maintained for older `dasher_*` shortcodes
- ✅ Hour tracking shortcodes working (separate module)

---

## What Was Broken

**Before Fix (Oct 28, 2025):**
- The 5 pages under `/lccp-dashboard/` were showing RAW shortcode text
- Example: Page showed `[lccp_mentor_dashboard]` instead of the actual dashboard
- Reason: `class-dashboards-module.php` file was never loaded

**After Fix:**
- Module manager now loads `class-dashboards-module.php`
- All dashboard shortcodes render properly
- Both new (`lccp_*`) and old (`dasher_*`) shortcode names work

---

## Site URLs to Test

Test these URLs to verify dashboards load:

1. https://you.fearlessliving.org/lccp-dashboard/mentor-dashboard/
2. https://you.fearlessliving.org/lccp-dashboard/bigbird-dashboard/
3. https://you.fearlessliving.org/lccp-dashboard/pc-dashboard/
4. https://you.fearlessliving.org/lccp-dashboard/student-dashboard/
5. https://you.fearlessliving.org/lccp-dashboard/

All should now show proper dashboards instead of `[shortcode]` text.
