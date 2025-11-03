# LCCP Systems - Actual Module Usage Analysis

**Date:** October 28, 2025

---

## Enabled Modules vs File Mapping

### ✅ Working Modules (9 of 12)

These modules are enabled AND have correct file mappings:

| Database Setting | Module ID | File | Status |
|-----------------|-----------|------|---------|
| `lccp_module_hour_tracker` | `hour_tracker` | `includes/class-hour-tracker.php` | ✅ WORKING |
| `lccp_module_performance` | `performance` | `includes/class-performance-optimizer.php` | ✅ WORKING |
| `lccp_module_learndash_integration` | `learndash_integration` | `includes/class-learndash-integration.php` | ✅ WORKING |
| `lccp_module_roles` | `roles` | `includes/class-roles-manager.php` | ✅ WORKING |
| `lccp_module_events_integration` | `events_integration` | `modules/class-events-integration.php` | ✅ WORKING |
| `lccp_module_accessibility` | `accessibility` | `includes/class-accessibility-manager.php` | ✅ WORKING |
| `lccp_module_autologin` | `ip_autologin` | `includes/class-ip-autologin.php` | ✅ WORKING |
| `lccp_module_dashboards` | `dashboards` | `includes/class-enhanced-dashboards.php` | ✅ WORKING |
| `lccp_module_advanced_checklist` | `checklist` | `includes/class-checklist-manager.php` | ✅ WORKING |

### ❌ Broken Modules (3 of 12)

These modules are enabled but have NO file mapping - they don't load:

| Database Setting | Status | File Exists? |
|-----------------|--------|--------------|
| `lccp_module_hour_tracker_advanced` | ❌ NO MAPPING | ✅ `class-hour-tracker-frontend.php` exists |
| `lccp_module_learndash_advanced` | ❌ NO MAPPING | ✅ `class-learndash-compatibility.php` exists |
| `lccp_module_performance_advanced` | ❌ NO MAPPING | ❌ NO FILE (or not obvious) |

**Impact:** These modules are "on" but do nothing. Users think features are enabled but they're not working.

---

## Module Naming Inconsistency Problem

The database uses `lccp_module_*` but the module manager uses different internal names:

```
DATABASE                        MODULE MANAGER ID
---------------------------------------------
lccp_module_dashboards    →    'dashboards'
lccp_module_hour_tracker  →    'hour_tracker'
lccp_module_accessibility →    'accessibility_manager' (inconsistent!)
lccp_module_autologin     →    'ip_autologin' (inconsistent!)
```

**How it works:**
1. Database stores: `lccp_module_dashboards = 'on'`
2. Module manager calls: `is_module_enabled('dashboards')`
3. Code strips `lccp_module_` prefix to match

**Problem:** Some module IDs don't match after stripping prefix!

---

## Critical Missing: Dashboard Shortcodes Module

**Database:** `lccp_module_dashboards = 'on'`
**File Loaded:** `includes/class-enhanced-dashboards.php` ✅

**BUT:** This only adds dashboard WIDGETS, NOT shortcodes!

**Shortcodes users expect:**
- `[lccp_mentor_dashboard]` - USED on site, page ID 229249
- `[lccp_big_bird_dashboard]` - USED on site, page ID 229248
- `[lccp_pc_dashboard]` - USED on site, page ID 229247
- `[lccp_student_dashboard]` - USED on site, page ID 229250

**Where shortcodes are registered:**
- File: `modules/class-dashboards-module.php`
- Status: ❌ NEVER LOADED (not in module manager)

**Result:** Pages show raw shortcode text `[lccp_mentor_dashboard]` instead of actual dashboard.

---

## Files That Self-Instantiate (Always Load)

These files instantiate themselves at the bottom, so they load whenever included:

| File | Loads Via | Always On? |
|------|-----------|------------|
| `class-membership-roles.php` | Module manager | ❌ Module must be enabled |
| `class-settings-manager.php` | Self-instantiates at line 943 | ✅ YES (always runs) |
| `class-roles-manager.php` | Self-instantiates at line 579 | ❌ Module must be enabled |
| `class-events-integration.php` | `::get_instance()` at line 786 | ❌ Module must be enabled |

**Note:** `class-settings-manager.php` ALWAYS loads because it instantiates itself immediately when included.

---

## What's Actually Being Used on Site

### Shortcodes in Active Use

From database analysis:

1. ✅ `[lccp-hour-widget]` - Working (hour_tracker module)
2. ✅ `[lccp-hour-form]` - Working (hour_tracker module)
3. ✅ `[lccp-hour-log]` - Working (hour_tracker module)
4. ❌ `[lccp_mentor_dashboard]` - BROKEN (file never loads)
5. ❌ `[lccp_pc_dashboard]` - BROKEN (file never loads)
6. ❌ `[lccp_student_dashboard]` - BROKEN (file never loads)
7. ❌ `[lccp_bigbird_dashboard]` - BROKEN (file never loads)
8. ❌ `[dasher_mentor_dashboard]` - BROKEN (file never loads)
9. ❌ `[dasher_bigbird_dashboard]` - BROKEN (file never loads)
10. ❌ `[dasher_pc_dashboard]` - BROKEN (file never loads)

**4 out of 10 active shortcodes are broken!**

### Pages Using Broken Shortcodes

These published pages are showing RAW SHORTCODE TEXT right now:

1. Page ID 229247: `/lccp-dashboard/pc-dashboard/`
2. Page ID 229248: `/lccp-dashboard/bigbird-dashboard/`
3. Page ID 229249: `/lccp-dashboard/mentor-dashboard/`
4. Page ID 229250: `/lccp-dashboard/student-dashboard/`

---

## Unused Modules (Files Exist, Never Load)

These files exist but are NEVER loaded by any mechanism:

1. ❌ `class-audio-review-enhanced.php` - Not in module manager
2. ❌ `class-dashboard-customizer.php` - Not in module manager
3. ❌ `class-hour-tracker-frontend.php` - Module ON but no mapping
4. ❌ `class-learndash-compatibility.php` - Module ON but no mapping
5. ❌ `class-mentor-hour-review.php` - Module OFF
6. ❌ `class-message-system.php` - Module OFF
7. ❌ `class-lccp-system-status.php` - Not referenced (maybe used by admin page?)
8. ❌ `modules/class-dasher.php` - Not in module manager
9. ❌ `modules/class-dashboards-module.php` - **CRITICAL** Not in module manager (breaks 4 pages!)
10. ❌ `document-manager.php` - Module not enabled
11. ❌ `lccp-integration.php` - Not in module manager (unclear if needed)
12. ❌ `dashboard-settings.php` - Not in module manager
13. ❌ `admin/module-settings.php` - Only loaded on specific admin page
14. ❌ `widgets/` folder - 4 widget files never loaded

---

## Summary

### Modules Actually Working: 9 of 12

| Module | Purpose | Status |
|--------|---------|--------|
| Hour Tracker | Track student hours | ✅ WORKING |
| Performance | Site optimization | ✅ WORKING |
| LearnDash Integration | Course integration | ✅ WORKING |
| Roles | LCCP role management | ✅ WORKING |
| Events Integration | Event management | ✅ WORKING |
| Accessibility | Accessibility features | ✅ WORKING |
| IP Auto-Login | IP-based login | ✅ WORKING |
| Dashboards (widgets only) | Admin dashboard widgets | ✅ WORKING |
| Checklist | Progress checklists | ✅ WORKING |

### Broken Modules: 3

1. **Hour Tracker Advanced** - Enabled but doesn't load
2. **LearnDash Advanced** - Enabled but doesn't load
3. **Performance Advanced** - Enabled but doesn't load

### Critical Missing Feature: Dashboard Pages

The biggest issue is `modules/class-dashboards-module.php` which:
- Registers 6 dashboard shortcodes
- Used on 4 published pages
- NEVER LOADS (not in module manager)
- **Causes pages to show raw shortcode text**

---

## Immediate Fix Needed

Add this to module manager's `$module_files` array:

```php
'dashboards_module' => 'modules/class-dashboards-module.php',
'hour_tracker_frontend' => 'includes/class-hour-tracker-frontend.php',
'learndash_compatibility' => 'includes/class-learndash-compatibility.php',
```

Then update database settings to match:
```sql
-- Rename modules to match new IDs
UPDATE wp_options SET option_name = 'lccp_module_dashboards_module' WHERE option_name = 'lccp_module_dashboards';
UPDATE wp_options SET option_name = 'lccp_module_hour_tracker_frontend' WHERE option_name = 'lccp_module_hour_tracker_advanced';
UPDATE wp_options SET option_name = 'lccp_module_learndash_compatibility' WHERE option_name = 'lccp_module_learndash_advanced';
```

**OR** (simpler): Just add the missing files to the existing enabled modules.
