# Developer Action Plan - Post Client Review
**Project:** Fearless You Shortcode Cleanup
**Date:** November 3, 2025

---

## Overview

This document provides the technical action plan for cleaning up shortcode issues after client review is complete.

---

## Phase 1: Quick Wins (No Client Input Needed)

### 1.1 Remove Test/Development Pages
These can likely be deleted immediately:

```
Page ID: 229251
Title: LCCP Test Page
URL: http://you.local/lccp-test-page/
Action: Move to trash
```

### 1.2 Fix False Positives
These aren't actually broken, just detected incorrectly:

**Instructor Dashboard (ID: 225034)**
- Status: Actually working, just has CSS selectors in HTML
- Action: Optional cleanup of HTML/CSS, low priority

**Current Programs (ID: 220068)**
- Status: Working - numeric "shortcodes" are WP Fusion tag IDs in block attributes
- Action: No action needed

**Terms of Use (ID: 7)**
- Status: `[at]` is part of email address
- Action: No action needed

---

## Phase 2: Dashboard Consolidation

### 2.1 Identify Working vs Legacy Dashboards

**Working Dashboard Pages (Keep These):**
```
✅ Big Bird Dashboard (NEW)
   - URL: http://you.local/lccp-dashboard/bigbird-dashboard/
   - ID: 229248
   - Note: Needs shortcode fix but this is the correct page structure

✅ Mentor Dashboard (NEW)
   - URL: http://you.local/lccp-dashboard/mentor-dashboard/
   - ID: 229249
   - Shortcode: [lccp_mentor_dashboard] - WORKING

✅ PC Dashboard (NEW)
   - URL: http://you.local/lccp-dashboard/pc-dashboard/
   - ID: 229247
   - Shortcode: [lccp_pc_dashboard] - WORKING

✅ Student Dashboard (NEW)
   - URL: http://you.local/lccp-dashboard/student-dashboard/
   - ID: 229250
   - Shortcode: [lccp_student_dashboard] - WORKING
```

**Legacy Dashboard Pages (Remove After Client Approval):**
```
❌ BigBird Dashboard (OLD #1)
   - URL: http://you.local/bigbird-dashboard/
   - ID: 229222
   - Shortcode: [dasher_bigbird_dashboard] - NOT WORKING
   - Action: Redirect to 229248

❌ BigBird Dashboard (OLD #2)
   - URL: http://you.local/dashboard-bb/
   - ID: 228352
   - Shortcode: [dasher_bigbird_dashboard] - NOT WORKING
   - Action: Redirect to 229248

❌ Mentor Dashboard (OLD #1)
   - URL: http://you.local/dashboard-m/
   - ID: 228351
   - Shortcode: [dasher_mentor_dashboard] - NOT WORKING
   - Action: Redirect to 229249

❌ Mentor Dashboard (OLD #2)
   - URL: http://you.local/mentor-dashboard/
   - ID: 229221
   - Shortcode: [dasher_mentor_dashboard] - NOT WORKING
   - Action: Redirect to 229249

❌ PC Dashboard (OLD)
   - URL: http://you.local/pc-dashboard/
   - ID: 229223
   - Shortcode: [dasher_pc_dashboard] - NOT WORKING
   - Action: Redirect to 229247
```

### 2.2 Implementation Steps

**For Each Legacy Dashboard Page (After Client Approval):**

1. **Create 301 Redirect**
   ```
   Old URL → New URL
   /bigbird-dashboard/ → /lccp-dashboard/bigbird-dashboard/
   /dashboard-bb/ → /lccp-dashboard/bigbird-dashboard/
   /dashboard-m/ → /lccp-dashboard/mentor-dashboard/
   /mentor-dashboard/ → /lccp-dashboard/mentor-dashboard/
   /pc-dashboard/ → /lccp-dashboard/pc-dashboard/
   ```

2. **Move Pages to Trash**
   - Use: WP Admin → Pages → Trash
   - Keep in trash for 30 days before permanent deletion

3. **Update Any Hardcoded Links**
   - Check: Navigation menus
   - Check: Sidebar widgets
   - Check: Email templates
   - Search content: `wp post list --post_type=post --field=ID | xargs -I % wp post get % --field=content | grep -l "dashboard-bb"`

---

## Phase 3: Fix LCCP Systems Shortcode Issues

### 3.1 Problem Analysis

**Symptoms:**
- LCCP Systems plugin is active
- Dashboard and Hour Tracker modules show as "enabled"
- But shortcodes are not registering:
  - `[lccp_bigbird_dashboard]` - exists in code but not working
  - `[dasher_bigbird_dashboard]` - backward compatibility alias not working
  - `[dasher_mentor_dashboard]` - backward compatibility alias not working
  - `[dasher_pc_dashboard]` - backward compatibility alias not working
  - `[lccp-hour-form]` - not working
  - `[lccp-hour-widget]` - not working
  - `[lccp-hour-log]` - not working

**Working Shortcodes from LCCP:**
- `[lccp_dashboard]` ✅
- `[lccp_mentor_dashboard]` ✅
- `[lccp_pc_dashboard]` ✅
- `[lccp_student_dashboard]` ✅
- `[lccp_big_bird_dashboard]` ✅ (but page 229248 uses different shortcode)
- `[lccp_checklist]` ✅

### 3.2 Investigation Steps

**Step 1: Check Module Manager**
```bash
# Access in browser:
/wp-admin/admin.php?page=lccp-module-manager

# Look for:
- Modules marked as "Problem — Auto-disabled"
- Error messages in admin notices
```

**Step 2: Check Error Logs**
```bash
# Check transient for module errors
wp eval "var_dump(get_transient('lccp_module_errors'));"

# Check PHP error log
tail -f /path/to/error.log | grep -i lccp
```

**Step 3: Verify Module Files**
```bash
# Check if module files exist
ls -la lccp-systems/modules/class-dashboards-module.php
ls -la lccp-systems/modules/class-hour-tracker-module.php

# Check if they're being loaded
grep -r "class-dashboards-module.php" lccp-systems/
grep -r "class-hour-tracker-module.php" lccp-systems/
```

**Step 4: Test Module Loading**
```bash
wp eval "
\$manager = LCCP_Module_Manager::get_instance();
\$loaded = \$manager->get_loaded_modules();
var_dump(\$loaded);
"
```

### 3.3 Likely Fixes

**Option A: Modules Aren't Loading**
```php
// Check: lccp-systems/includes/class-module-manager.php
// Line 213-231 defines module file mappings

// Verify 'dashboards' and 'hour_tracker' are in $module_files array
// If missing, add:
'dashboards' => 'modules/class-dashboards-module.php',
'hour_tracker' => 'modules/class-hour-tracker-module.php',
```

**Option B: Shortcode Registration Disabled**
```php
// Check: modules/class-dashboards-module.php line 40
// Check: modules/class-hour-tracker-module.php

// If $this->get_setting('enable_shortcodes') is false, shortcodes won't register
// Fix: Update module settings to enable shortcodes
```

**Option C: Module Self-Test Failing**
```php
// Check self-test expectations in class-module-manager.php line 456-506
// If a module fails self-test, it auto-disables

// Common failures:
// - Missing database table (hour_tracker)
// - Missing expected class
// - Missing dependency

// Fix: Run module activation to create tables/setup
```

### 3.4 BigBird Dashboard Specific Issue

**Problem:** Page 229248 uses `[lccp_bigbird_dashboard]` but system registers `[lccp_big_bird_dashboard]` (with underscore)

**Option 1 - Add Alias:**
```php
// In modules/class-dashboards-module.php around line 77
add_shortcode('lccp_bigbird_dashboard', array($this, 'render_big_bird_dashboard'));
```

**Option 2 - Update Page Content:**
```bash
wp post update 229248 --post_content="$(wp post get 229248 --field=content | sed 's/lccp_bigbird_dashboard/lccp_big_bird_dashboard/g')"
```

### 3.5 Hour Tracker Database Setup

**Check if table exists:**
```bash
wp db query "SHOW TABLES LIKE 'wp_lccp_hour_tracker';"
```

**If missing, create table:**
```bash
# Trigger module activation
wp eval "
\$module = LCCP_Module_Manager::get_instance();
\$module->disable_module('hour_tracker');
\$module->enable_module('hour_tracker');
"
```

---

## Phase 4: Replace Non-LCCP Shortcodes

### 4.1 Courses Page (ID: 224639)

**Current:** `[phunk_courses_by_category]` (not working)
**Replace With:** `[learndash_course_grid]`

```bash
# Update page content
wp post update 224639 --post_content="$(wp post get 224639 --field=content | sed 's/\[phunk_courses_by_category\]/[learndash_course_grid]/g')"
```

**Alternative Options:**
```
[ld_course_list] - Simple course list
[learndash_course_grid per_page="9"] - Grid with pagination
[uo_courses] - Uncanny Toolkit course grid
```

### 4.2 Document Library (ID: 218999)

**Current:** `[doc_library]` (not working - module disabled)

**Option 1: Enable LCCP Document Manager Module**
```bash
# Check if module exists
ls -la lccp-systems/includes/document-manager.php

# Enable module
wp option patch insert lccp_modules_settings document_manager '{"enabled":true}'
```

**Option 2: Alternative Document Solutions**
- Use WordPress Media Library with categories
- Use BuddyBoss Documents feature
- Use a dedicated document management plugin

### 4.3 Replays Page (ID: 78)

**Current:** `[memb_has_any_tag]` (not working)
**Replace With:** WP Fusion conditional shortcode

```
[wpf tag="YourTagID"]
Content only members with this tag can see
[/wpf]
```

**Implementation:**
1. Identify which tag IDs should have access
2. Wrap content in `[wpf]` shortcode
3. Test with different user roles

### 4.4 Your Checklists (ID: 225077)

**Current:** `[user_checklists]` (not working)
**Replace With:** `[lccp_checklist]`

```bash
# Update if lccp_checklist supports user-specific display
wp post update 225077 --post_content="$(wp post get 225077 --field=content | sed 's/\[user_checklists\]/[lccp_checklist]/g')"
```

**Note:** Verify `[lccp_checklist]` displays user-specific checklists. May need custom development.

---

## Phase 5: Testing Checklist

After making fixes, test each page:

### Dashboard Pages
```
☐ Big Bird Dashboard (229248) - Login as BigBird role, verify dashboard displays
☐ Mentor Dashboard (229249) - Login as Mentor, verify dashboard displays
☐ PC Dashboard (229247) - Login as PC, verify dashboard displays
☐ Student Dashboard (229250) - Login as Student, verify dashboard displays
☐ Legacy redirects working (301 status)
```

### Hour Tracking
```
☐ Hour Submission (229219) - Form displays and submits
☐ Hour widget shows current hours
☐ Hour log displays submission history
```

### Content Pages
```
☐ Courses (224639) - Course grid displays correctly
☐ Document Library (218999) - Documents display/downloadable
☐ Replays (78) - Content shows/hides based on membership
☐ Your Checklists (225077) - User checklists display
```

### Cross-Browser Testing
```
☐ Chrome
☐ Safari
☐ Firefox
☐ Mobile (iOS)
☐ Mobile (Android)
```

---

## Phase 6: Documentation Updates

After cleanup, update:

### Internal Documentation
- [ ] Update page inventory spreadsheet
- [ ] Document new dashboard URLs
- [ ] Update developer wiki with shortcode reference

### User-Facing Documentation
- [ ] Update help docs with correct URLs
- [ ] Update onboarding materials
- [ ] Update email templates with new URLs

### Code Documentation
- [ ] Add comments to custom LCCP modifications
- [ ] Document why legacy shortcodes were removed
- [ ] Update README in lccp-systems plugin

---

## Emergency Rollback Plan

If issues occur after deployment:

### 1. Restore Legacy Dashboard Pages
```bash
# Untrash pages
wp post update 229222 --post_status=publish
wp post update 228352 --post_status=publish
wp post update 228351 --post_status=publish
wp post update 229221 --post_status=publish
wp post update 229223 --post_status=publish

# Remove redirects
# Revert in Redirection plugin or .htaccess
```

### 2. Restore Original Page Content
```bash
# View revision history
wp post list-revisions 224639

# Restore specific revision
wp post restore-revision 224639 <revision-id>
```

### 3. Database Backup
Before starting:
```bash
wp db export backup-before-shortcode-cleanup.sql
```

---

## Timeline Estimate

| Phase | Estimated Time | Dependencies |
|-------|---------------|--------------|
| Phase 1: Quick Wins | 1 hour | None |
| Phase 2: Dashboard Consolidation | 2-3 hours | Client approval |
| Phase 3: LCCP Systems Fix | 4-8 hours | Investigation results |
| Phase 4: Replace Shortcodes | 2-4 hours | Client decisions |
| Phase 5: Testing | 4-6 hours | Phases 1-4 complete |
| Phase 6: Documentation | 2-3 hours | Testing complete |
| **TOTAL** | **15-25 hours** | Client review + approval |

---

## Success Criteria

- [ ] Zero pages showing raw shortcode text to users
- [ ] All dashboard pages working for appropriate user roles
- [ ] All legacy URLs properly redirected (301)
- [ ] No broken functionality reported by client/users
- [ ] All redirects tested and verified
- [ ] Documentation updated
- [ ] Client sign-off received

---

## Contact & Support

**For Questions:**
- Technical lead: [Name]
- Client contact: [Name]
- Plugin developer: LCCP Systems custom plugin

**Resources:**
- Module Manager: `/wp-admin/admin.php?page=lccp-module-manager`
- System Status: Check for LCCP System Status module
- Error Logs: Server error_log and debug.log

---

**Document Version:** 1.0
**Last Updated:** November 3, 2025
**Next Review:** After client feedback received
