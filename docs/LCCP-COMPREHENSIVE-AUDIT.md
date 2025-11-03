# LCCP Systems - Comprehensive Plugin Audit

**Date:** October 28, 2025
**Purpose:** Full audit of plugin structure, unused code, broken functionality, and actual site usage

---

## Executive Summary

**Problem:** Plugin is severely broken with:
- 8+ files that exist but are NEVER loaded (dead code)
- Multiple orphaned templates
- Shortcodes registered but never used
- Modules enabled but not properly initialized
- Inconsistent file loading

**Size:** 1.8 MB | 30,771 lines of code
**Estimated Dead Code:** ~15-20% of codebase

---

## 1. Module Status (Database vs Files)

### Enabled Modules (ON)
1. ✅ **hour_tracker** - `class-hour-tracker.php` - LOADED & WORKING
2. ✅ **performance** - `class-performance-optimizer.php` - LOADED & WORKING
3. ✅ **learndash_integration** - `class-learndash-integration.php` - LOADED & WORKING
4. ✅ **roles** - `class-roles-manager.php` - LOADED & WORKING
5. ✅ **events_integration** - `class-events-integration.php` - LOADED & WORKING
6. ✅ **accessibility** - `class-accessibility-manager.php` - LOADED & WORKING
7. ✅ **autologin** - `class-ip-autologin.php` - LOADED & WORKING
8. ✅ **dashboards** - `class-enhanced-dashboards.php` - LOADED & WORKING
9. ✅ **advanced_checklist** - `class-checklist-manager.php` - LOADED & WORKING
10. ⚠️ **hour_tracker_advanced** - NOT IN MODULE FILES MAP (probably refers to class-hour-tracker-frontend.php)
11. ⚠️ **learndash_advanced** - NOT IN MODULE FILES MAP (probably refers to class-learndash-compatibility.php)
12. ⚠️ **performance_advanced** - NOT IN MODULE FILES MAP (no corresponding file?)

### Disabled Modules (OFF)
- **mentor_system** - OFF (probably refers to class-mentor-hour-review.php)
- **checklists** - OFF (might be old version of advanced_checklist)
- **messages** - OFF - class-message-system.php exists

---

## 2. Dead Code - Files That Exist But Are NEVER Loaded

### Critical: Files Not in Module Manager Map

These files exist but are NOT in the module manager's file loading array:

1. ❌ **class-audio-review-enhanced.php** (7KB)
   - Registers shortcodes: `lccp_audio_review_player`, `lccp_student_feedback_notes`
   - Never loaded by module manager
   - **Dead code**

2. ❌ **class-dashboard-customizer.php** (unknown size)
   - Not referenced anywhere
   - **Dead code**

3. ❌ **class-hour-tracker-frontend.php** (unknown size)
   - Registers shortcodes: `lccp_hour_tracker`, `lccp_hours_dashboard`
   - NOT in module manager map
   - Module `hour_tracker_advanced` is enabled but doesn't map to this file
   - **Dead code or misconfigured**

4. ❌ **class-learndash-compatibility.php** (unknown size)
   - IN module manager map (`learndash_compatibility`)
   - But module `learndash_advanced` is enabled, not `learndash_compatibility`
   - **May not be loading**

5. ❌ **class-mentor-hour-review.php** (unknown size)
   - Registers shortcodes: `lccp_mentor_hour_reviews`, `lccp_hour_notification_bubble`
   - Module `mentor_system` is OFF
   - **Not loading (but shortcodes may be needed?)**

6. ❌ **class-message-system.php** (unknown size)
   - IN module manager map
   - Module `messages` is OFF
   - **Not loading**

7. ❌ **class-lccp-system-status.php** (unknown size)
   - IN module manager map (`system_status`)
   - No enabled module maps to this
   - **Dead code**

8. ❌ **modules/class-dasher.php** (unknown size)
   - Registers shortcode: `lccp_dasher`
   - Registers admin page: `lccp-dasher`
   - Self-instantiates at bottom: `LCCP_Dasher_Dashboard::get_instance();`
   - BUT file is NEVER included by module manager
   - **Dead code - entire file never loads**

9. ❌ **modules/class-dashboards-module.php** (unknown size)
   - Registers dashboard shortcodes
   - NOT in module manager map
   - **May be dead code or loads differently**

10. ❌ **document-manager.php** (unknown size)
    - IN module manager map
    - But no `document_manager` module enabled in database
    - **Not loading**

11. ❌ **lccp-integration.php** (unknown size)
    - Not in module manager map
    - Registers shortcode: `dasher_pc_dashboard`
    - **Status unknown**

12. ❌ **dashboard-settings.php** (unknown size)
    - Not in module manager map
    - Registers admin page: `dasher-dashboard-settings`
    - Self-instantiates but never included
    - **Dead code**

13. ❌ **admin/module-settings.php** (unknown size)
    - Not in module manager map
    - **Dead code**

14. ❌ **widgets/** folder (4 files)
    - `class-course-progress-widget.php`
    - `class-learning-streak-widget.php`
    - `class-resource-library-widget.php`
    - `class-upcoming-sessions-widget.php`
    - None referenced in module manager
    - **Dead code**

---

## 3. Shortcodes Audit

### Shortcodes Registered by Plugin

1. `lccp_checklist` - class-checklist-manager.php
2. `checklist_in_post` - class-checklist-manager.php
3. `dasher_document_library` - document-manager.php (module OFF)
4. `dasher_document_viewer` - document-manager.php (module OFF)
5. `lccp_audio_review_player` - class-audio-review-enhanced.php (NEVER LOADED)
6. `lccp_student_feedback_notes` - class-audio-review-enhanced.php (NEVER LOADED)
7. ✅ `lccp-hour-widget` - class-hour-tracker.php (USED ON SITE)
8. ✅ `lccp-hour-form` - class-hour-tracker.php (USED ON SITE)
9. ✅ `lccp-hour-log` - class-hour-tracker.php (USED ON SITE)
10. `lccp_hour_tracker` - class-hour-tracker-frontend.php (NEVER LOADED)
11. `lccp_hours_dashboard` - class-hour-tracker-frontend.php (NEVER LOADED)
12. `lccp_mentor_hour_reviews` - class-mentor-hour-review.php (module OFF)
13. `lccp_hour_notification_bubble` - class-mentor-hour-review.php (module OFF)
14. `dasher_pc_dashboard` - lccp-integration.php (status unknown)
15. `lccp_events` - class-events-integration.php
16. `lccp_event_calendar` - class-events-integration.php
17. `lccp_dasher` - class-dasher.php (NEVER LOADED)
18. ✅ `lccp_mentor_dashboard` - class-dashboards-module.php (USED ON SITE)
19. ✅ `dasher_mentor_dashboard` - class-dashboards-module.php (USED ON SITE - backward compat)
20. ✅ `lccp_big_bird_dashboard` - class-dashboards-module.php (USED ON SITE)
21. ✅ `dasher_bigbird_dashboard` - class-dashboards-module.php (USED ON SITE - backward compat)
22. ✅ `lccp_pc_dashboard` - class-dashboards-module.php (USED ON SITE)
23. ✅ `dasher_pc_dashboard` - class-dashboards-module.php (USED ON SITE - backward compat)
24. `lccp_student_dashboard` - class-dashboards-module.php

### Shortcodes Actually Used on Site (from database)

1. ✅ `[lccp_mentor_dashboard]`
2. ✅ `[lccp_pc_dashboard]`
3. ✅ `[lccp_student_dashboard]`
4. ✅ `[lccp_bigbird_dashboard]`
5. ✅ `[dasher_mentor_dashboard]`
6. ✅ `[dasher_bigbird_dashboard]`
7. ✅ `[dasher_pc_dashboard]`
8. ✅ `[lccp-hour-form]`
9. ✅ `[lccp-hour-log]`
10. ✅ `[lccp-hour-widget]`
11. ✅ `[lccp_dashboard]` (probably maps to student dashboard)

### Shortcodes Registered But NEVER Used
- `lccp_checklist`, `checklist_in_post`
- `dasher_document_library`, `dasher_document_viewer`
- `lccp_audio_review_player`, `lccp_student_feedback_notes`
- `lccp_hour_tracker`, `lccp_hours_dashboard`
- `lccp_mentor_hour_reviews`, `lccp_hour_notification_bubble`
- `lccp_events`, `lccp_event_calendar`
- `lccp_dasher`

---

## 4. Templates Audit

### Templates That Exist
1. `big-bird-dashboard-enhanced.php`
2. `big-bird-dashboard.php`
3. `mentor-dashboard-enhanced.php`
4. `mentor-dashboard.php`
5. `pc-dashboard-enhanced.php`

### Templates Actually Loaded
- ✅ `mentor-dashboard.php` - Loaded by class-dashboards-module.php
- ✅ `big-bird-dashboard.php` - Loaded by class-dashboards-module.php
- ✅ `pc-dashboard-enhanced.php` - Loaded by class-dashboards-module.php

### Orphaned Templates (Never Loaded)
- ❌ `big-bird-dashboard-enhanced.php` - EXISTS but NEVER loaded
- ❌ `mentor-dashboard-enhanced.php` - EXISTS but NEVER loaded

**Issue:** Code loads the non-enhanced versions but "enhanced" versions exist and are unused.

---

## 5. Database Tables

### Tables Created by Plugin
1. ✅ `wp_lccp_assignments`
2. ✅ `wp_lccp_checklist_progress`
3. ✅ `wp_lccp_completions`
4. ✅ `wp_lccp_hour_submissions`
5. ✅ `wp_lccp_hour_tracker`

All tables appear to be in use.

---

## 6. Critical Issues Found

### Issue 1: Module Loading Inconsistency

**Problem:** Modules enabled in database don't map to actual files:

- `lccp_module_hour_tracker_advanced` = ON → No file mapping
- `lccp_module_learndash_advanced` = ON → No file mapping
- `lccp_module_performance_advanced` = ON → No file mapping

These modules are "on" but the module manager doesn't know which file to load.

### Issue 2: Files Self-Instantiate But Never Included

**Files that have `new ClassName()` or `::get_instance()` at the bottom:**
- `class-dasher.php` - Instantiates but file never included
- `class-membership-roles.php` - Instantiates at line 754
- `class-settings-manager.php` - Instantiates at line 943
- `class-roles-manager.php` - Instantiates at line 579
- `lccp-integration.php` - Probably instantiates
- `dashboard-settings.php` - Probably instantiates

**This means:**
- If file is loaded by module manager → class instantiates ✅
- If file is NOT in module manager map → never loads, dead code ❌

### Issue 3: Dashboards Module Mystery

**class-dashboards-module.php:**
- NOT in module manager file map
- Registers all dashboard shortcodes
- Shortcodes ARE used on site
- HOW is this file loading?

**Possible explanations:**
1. Loaded elsewhere (not in module manager)
2. Loaded by class-enhanced-dashboards.php?
3. Manually included somewhere?

**Status:** NEEDS INVESTIGATION

### Issue 4: Missing Hour Tracker Frontend

**Problem:**
- `class-hour-tracker-frontend.php` exists
- Registers shortcodes `lccp_hour_tracker`, `lccp_hours_dashboard`
- Module `hour_tracker_advanced` is ON
- But NO mapping in module manager
- **File never loads, shortcodes never work**

---

## 7. Recommended Actions

### Priority 1: Fix Module Loading Map

Add missing modules to module manager:
```php
'hour_tracker_advanced' => 'includes/class-hour-tracker-frontend.php',
'learndash_advanced' => 'includes/class-learndash-compatibility.php',
'dashboards_module' => 'modules/class-dashboards-module.php', // If not already loading
```

### Priority 2: Delete Dead Code Files

**Safe to delete (never loaded, never used):**
1. `class-audio-review-enhanced.php`
2. `class-dashboard-customizer.php`
3. `modules/class-dasher.php`
4. `admin/module-settings.php`
5. `widgets/` folder (all 4 files)
6. `dashboard-settings.php`

**Delete if confirmed unused:**
7. `class-lccp-system-status.php`
8. `lccp-integration.php` (check first)

### Priority 3: Delete Orphaned Templates

**Safe to delete:**
1. `big-bird-dashboard-enhanced.php`
2. `mentor-dashboard-enhanced.php`

### Priority 4: Fix or Remove Unused Shortcodes

Either:
- Delete files that register unused shortcodes
- OR enable the modules so shortcodes work

### Priority 5: Investigate Dashboards Module Loading

Figure out HOW class-dashboards-module.php is loading if it's not in module manager.

---

## 8. Plugin Structure Issues

### Design Problems

1. **Inconsistent loading mechanism**
   - Some files in module manager
   - Some files self-instantiate
   - Some files loaded manually somewhere
   - No clear pattern

2. **Module manager doesn't match reality**
   - Database has modules not in file map
   - File map has modules not in database
   - Mismatch causes confusion

3. **No dead code detection**
   - Files exist for years without being used
   - No way to know what's actually running

4. **Template inconsistency**
   - Enhanced vs non-enhanced versions
   - Code only loads non-enhanced
   - Enhanced versions are orphaned

---

## 9. Testing Needed

After cleanup, verify these work:
- [ ] All dashboard shortcodes render
- [ ] Hour tracking forms work
- [ ] Role management works
- [ ] Events integration works
- [ ] Performance optimization works
- [ ] Settings pages load
- [ ] No fatal errors

---

## 10. Estimated Impact

**If we delete dead code:**
- Remove ~5-8 files (15-20% of codebase)
- Reduce plugin size by ~200-400 KB
- Eliminate confusion about what's active
- Easier to maintain going forward

**Current state: FRAGILE**
- Too much dead code
- Unclear what's working vs broken
- Hard to maintain
- Easy to break

---

## Next Steps

1. **Investigate** how dashboards-module.php loads
2. **Fix** module manager mapping for enabled modules
3. **Delete** confirmed dead code files
4. **Test** all functionality after cleanup
5. **Document** what remains and how it works

**Status:** Requires immediate attention
