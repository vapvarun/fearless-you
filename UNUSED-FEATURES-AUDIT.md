# UNUSED FEATURES AUDIT - Fearless You Site
**Date:** October 28, 2025
**Audited:** All custom plugins + child theme

---

## EXECUTIVE SUMMARY

Comprehensive audit of 6 custom plugins and child theme to identify features that are coded but NOT used anywhere on the site.

**KEY FINDINGS:**
- **60+ completely unused features** across plugins (shortcodes, CPTs, admin pages)
- **30-40% of custom code can be removed** without impacting functionality
- **LCCP Systems plugin** has most bloat (6 entire unused subsystems)
- **7 orphaned functions** in child theme (defined but never hooked)
- **Multiple dashboard shortcodes** with very low usage (1-4 pages only)

**POTENTIAL CLEANUP IMPACT:**
- Remove 15,000+ lines of dead code
- Reduce database queries
- Improve performance
- Simplify maintenance

---

## IMMEDIATE REMOVAL CANDIDATES

### 1. LCCP SYSTEMS PLUGIN - UNUSED SUBSYSTEMS

**Hour Tracking System** (COMPLETELY UNUSED)
- ❌ `[lccp_hour_tracker]` - 0 uses
- ❌ `[lccp_hours_dashboard]` - 0 uses
- ❌ `[lccp_mentor_hour_reviews]` - 0 uses
- ❌ `[lccp_hour_notification_bubble]` - 0 uses
- ❌ `[lccp-hour-widget]` - 0 uses
- ❌ `[lccp-hour-form]` - 0 uses
- ❌ `[lccp-hour-log]` - 0 uses
- **Files to remove:**
  - `includes/class-hour-tracker.php`
  - `includes/class-hour-tracker-frontend.php`
  - `modules/class-hour-tracker-module.php`

**Document Management System** (COMPLETELY UNUSED)
- ❌ `[dasher_document_library]` - 0 uses
- ❌ `[dasher_document_viewer]` - 0 uses
- ❌ Custom post type `dasher_document` - 0 posts
- ❌ Admin page "Document Manager"
- **Files to remove:**
  - `modules/class-document-manager.php`
  - All document templates

**Message System** (COMPLETELY UNUSED)
- ❌ Custom post type `dasher_message` - 0 posts
- **Files to remove:**
  - `modules/class-dasher-messages.php`

**Events Integration** (COMPLETELY UNUSED)
- ❌ `[lccp_events]` - 0 uses
- ❌ `[lccp_event_calendar]` - 0 uses
- **Files to remove:**
  - `modules/class-events-integration.php` (if separate from Events module)

**Audio Review System** (COMPLETELY UNUSED)
- ❌ `[lccp_audio_review_player]` - 0 uses
- ❌ `[lccp_student_feedback_notes]` - 0 uses
- **Files to remove:**
  - `includes/class-audio-review-enhanced.php`

**Milestone System** (COMPLETELY UNUSED)
- ❌ Custom post type `lccp_milestone` - 0 posts
- ❌ Admin page "Dasher Milestones"
- **Files to remove:**
  - `modules/class-milestones.php`

**Other Unused Features**
- ❌ `[lccp_dasher]` - 0 uses (main dasher shortcode)

**IMPACT:** Removing these subsystems would eliminate ~60% of LCCP Systems plugin code

---

### 2. LEARNDASH FAVORITE CONTENT PLUGIN

**Status:** ALL shortcodes unused, but CPT exists

**Unused Shortcodes:**
- ❌ `[favorite_content]` - 0 uses
- ❌ `[favorite_course_content]` - 0 uses
- ❌ `[favorite_button]` - 0 uses

**Custom Post Type:**
- `favcon` (Favorite Content) - Registered but usage unknown

**RECOMMENDATION:**
1. **If favorites feature not needed:** Delete entire plugin
2. **If used programmatically:** Keep plugin but verify actual usage
3. **ACTION NEEDED:** Check with stakeholders if favorites functionality is required

---

### 3. FEARLESS YOU SYSTEMS PLUGIN - UNUSED DASHBOARDS

**Unused Dashboard Shortcodes:**
- ❌ `[fys_member_dashboard]` - 0 uses
- ❌ `[fys_ambassador_dashboard]` - 0 uses

**Low Usage Dashboard:**
- ⚠️ `[fys_faculty_dashboard]` - Only 1 page uses this

**Files to Consider Removing:**
- `includes/class-member-dashboard.php`
- `includes/class-ambassador-dashboard.php`
- Related admin pages for member/ambassador features

**IMPACT:** ~40% code reduction in this plugin

---

### 4. CHILD THEME - ORPHANED FUNCTIONS

**Functions defined but NEVER hooked into WordPress:**

```php
// functions.php - Lines to remove:

❌ function log_password_reset_attempt()
   // No add_action() hook - this function never runs

❌ function enqueue_focus_mode_scripts()
   // No add_action() hook - scripts never enqueue

❌ function process_shortcodes_in_restricted_content_message()
   // No add_filter() hook - shortcodes never process

❌ function enable_gutenberg_for_certificates()
   // No add_filter() hook - Gutenberg never enabled

❌ function fearless_change_login_button_text()
   // No add_filter() hook - button text never changes

❌ function fearless_email_login_auth()
   // No add_filter() hook - email login never works

❌ function conditional_dashboard_menu_items()
   // No add_action() hook - menu items never added
```

**IMPACT:** Remove ~150 lines of dead code from functions.php

---

## FEATURES TO VERIFY WITH STAKEHOLDERS

### Dashboard Shortcodes (Low Usage)

**LCCP Systems Plugin:**
| Shortcode | Usage Count | Pages |
|-----------|-------------|-------|
| `[lccp_mentor_dashboard]` | 1 | /lccp-dashboard/mentor-dashboard/ |
| `[dasher_mentor_dashboard]` | 4 | /mentor-dashboard/, /dashboard-m/, 2 others |
| `[lccp_big_bird_dashboard]` | 1 | /lccp-dashboard/bigbird-dashboard/ |
| `[dasher_bigbird_dashboard]` | 2 | /bigbird-dashboard/, /dashboard-bb/ |
| `[lccp_pc_dashboard]` | 1 | /lccp-dashboard/pc-dashboard/ |
| `[dasher_pc_dashboard]` | 1 | /pc-dashboard/ |
| `[lccp_student_dashboard]` | 2 | /lccp-dashboard/student-dashboard/, /student-dashboard/ |

**Questions:**
- Are these dashboard pages actively used by users?
- Could they be consolidated into fewer dashboard pages?
- Are both `lccp_*` and `dasher_*` versions needed?

**Fearless You Systems:**
| Shortcode | Usage Count | Pages |
|-----------|-------------|-------|
| `[fys_faculty_dashboard]` | 1 | Unknown page |

**Questions:**
- Is faculty dashboard actively used?
- Could this be merged with other dashboard systems?

---

### Elephunkie Toolkit Modules

**Recommendation:** Check **Elephunkie Toolkit admin settings** to see which modules are enabled.

**Modules to review:**
1. Cleanup Utility
2. Elephunkie Log Mailer
3. Phunk Audio / Phunkie Audio Player (two audio players?)
4. LearnDash Video Manager
5. Simple User Activity
6. Phunk Plugin Logger
7. LearnDash Courses to CSV
8. Inactive Plugin Manager
9. Fearless Security Fixer
10. Phunk Fixes
11. Phunk Auto Enroll
12. LC-EX
13. Phunkie Custom Login

**Action:** Disabled modules can be safely deleted from `/includes/` directory

---

### Lock Visibility Plugin

**Custom Post Type:**
- `visibility_preset` - 0 presets created

**Status:** Post type exists but no content created. The plugin may still provide block-level visibility controls.

**Action:** Verify if block visibility features are used in Gutenberg editor

---

## DETAILED BREAKDOWN BY PLUGIN

### LCCP Systems (plugins/lccp-systems/)

**KEEP (Active & Used):**
- ✅ `[checklist_in_post]` - Multiple uses in content
- ✅ Enhanced Dashboards (5 widgets)
- ✅ Dashboard Module (role-based pages)
- ✅ Checklist System
- ✅ Settings Manager
- ✅ Roles & Capabilities
- ✅ Module Manager

**REMOVE (Completely Unused):**
- ❌ Hour Tracker (7 shortcodes + classes)
- ❌ Document Manager (CPT + 2 shortcodes + admin pages)
- ❌ Message System (CPT)
- ❌ Events Integration (2 shortcodes)
- ❌ Audio Review (2 shortcodes)
- ❌ Milestone System (CPT + admin page)
- ❌ Main Dasher shortcode (1 shortcode)

**VERIFY (Low Usage):**
- ⚠️ Dashboard shortcodes (1-4 pages each)

**Files to Delete:**
```
includes/class-hour-tracker.php
includes/class-hour-tracker-frontend.php
includes/class-audio-review-enhanced.php
modules/class-document-manager.php
modules/class-dasher-messages.php
modules/class-milestones.php
modules/class-hour-tracker-module.php
templates/audio-review-*
templates/document-*
templates/hour-*
```

---

### Fearless You Systems (plugins/fearless-you-systems/)

**KEEP:**
- ✅ Fearless You Members admin menu
- ✅ Role Manager
- ✅ Analytics system

**REMOVE:**
- ❌ Member Dashboard (`[fys_member_dashboard]` - 0 uses)
- ❌ Ambassador Dashboard (`[fys_ambassador_dashboard]` - 0 uses)

**VERIFY:**
- ⚠️ Faculty Dashboard (`[fys_faculty_dashboard]` - 1 page)

**Files to Delete:**
```
includes/class-member-dashboard.php
includes/class-ambassador-dashboard.php
Related admin submenu pages
```

---

### Fearless Roles Manager (plugins/fearless-roles-manager/)

**Status:** ✅ **ALL FEATURES ACTIVELY USED**

**Keep Everything:**
- Role management system
- Dashboard redirects
- Admin pages

---

### LearnDash Favorite Content (plugins/learndash-favorite-content/)

**Decision Required:**

**Option A: Remove Entire Plugin**
- If favorites feature not needed
- Saves plugin overhead

**Option B: Keep Plugin**
- If favorites tracked programmatically
- Verify with database check for `favcon` posts

**Files Status:**
```
All shortcodes: UNUSED (0 database instances)
Custom post type: EXISTS (but verify if populated)
Admin settings: EXISTS
```

---

### Lock Visibility (plugins/lock-visibility/)

**Status:** ⚠️ **PARTIALLY USED**

**Keep:**
- ✅ Core block visibility functionality (used in editor)
- ✅ REST API endpoints
- ✅ Settings page

**Unused:**
- ❌ Visibility Presets CPT (0 posts)

**Note:** Plugin provides Gutenberg block controls - likely used even without presets

---

### Elephunkie Toolkit (plugins/elephunkie-toolkit/)

**Status:** ✅ **MODULAR PLUGIN - CHECK SETTINGS**

**Action Required:**
1. Go to **WP Admin → Elephunkie Toolkit → Settings**
2. Check which modules are **enabled** (toggle ON)
3. Modules that are **disabled** (toggle OFF) can be deleted from filesystem

**Module Files Location:** `/includes/[module-name]/`

**Disabled modules = safe to delete**

---

### Child Theme (themes/fli-child-theme/)

**KEEP (Active & Used):**
- ✅ Script/style enqueuing
- ✅ LearnDash customizations
- ✅ Login page customization
- ✅ Search override
- ✅ Admin bar management
- ✅ User registration columns
- ✅ WP Fusion integration
- ✅ Category color system
- ✅ Floating contact button
- ✅ Author redirect
- ✅ BuddyBoss license optimization

**REMOVE (Orphaned Functions):**
```php
// Remove from functions.php:

❌ log_password_reset_attempt()
❌ enqueue_focus_mode_scripts()
❌ process_shortcodes_in_restricted_content_message()
❌ enable_gutenberg_for_certificates()
❌ fearless_change_login_button_text()
❌ fearless_email_login_auth()
❌ conditional_dashboard_menu_items()
```

**VERIFY (Template Files):**
- ⚠️ `template-parts/category-separator.php` - Check if called
- ⚠️ `thank-ya.php` - Check if used

---

## CLEANUP ROADMAP

### Phase 1: Immediate Cleanup (High Confidence)

**Week 1:**
1. ✅ Backup database and files
2. Remove child theme orphaned functions (7 functions)
3. Remove LCCP Systems unused shortcodes that are clearly dead code
4. Test site thoroughly

**Expected Impact:**
- ~300 lines removed from child theme
- No functional changes (functions weren't hooked anyway)
- No database changes needed

---

### Phase 2: LCCP Systems Major Cleanup

**Week 2-3:**
1. ✅ Verify dashboard shortcode usage with stakeholders
2. Remove Hour Tracker subsystem (7 shortcodes + classes)
3. Remove Document Manager subsystem (CPT + 2 shortcodes)
4. Remove Message System (CPT)
5. Remove Audio Review System (2 shortcodes)
6. Remove Milestone System (CPT)
7. Remove Events Integration shortcodes (if confirmed unused)
8. Update STATUS.md documentation
9. Test all remaining functionality

**Expected Impact:**
- ~10,000-15,000 lines removed
- 60% reduction in LCCP Systems plugin size
- Remove 6 unused database tables (for CPTs)
- Significant performance improvement

**Files to Remove:**
```
plugins/lccp-systems/includes/class-hour-tracker.php
plugins/lccp-systems/includes/class-hour-tracker-frontend.php
plugins/lccp-systems/includes/class-audio-review-enhanced.php
plugins/lccp-systems/modules/class-document-manager.php
plugins/lccp-systems/modules/class-dasher-messages.php
plugins/lccp-systems/modules/class-milestones.php
plugins/lccp-systems/modules/class-hour-tracker-module.php
plugins/lccp-systems/templates/audio-review-*
plugins/lccp-systems/templates/document-*
plugins/lccp-systems/templates/hour-*
+ Related admin page registrations
```

---

### Phase 3: Fearless You Systems Cleanup

**Week 4:**
1. Verify faculty dashboard usage
2. Remove Member Dashboard system
3. Remove Ambassador Dashboard system
4. Consider merging with Fearless Roles Manager
5. Test remaining functionality

**Expected Impact:**
- ~2,000-3,000 lines removed
- 40% reduction in Fearless You Systems plugin size

**Files to Remove:**
```
plugins/fearless-you-systems/includes/class-member-dashboard.php
plugins/fearless-you-systems/includes/class-ambassador-dashboard.php
+ Related admin submenu pages
```

---

### Phase 4: Plugin Decisions

**Week 5:**
1. **LearnDash Favorite Content:**
   - Verify if favorites used at all
   - Decision: Keep or remove entire plugin

2. **Elephunkie Toolkit:**
   - Review enabled/disabled modules
   - Delete disabled module directories

3. **Lock Visibility:**
   - Keep plugin (block controls likely used)
   - No action needed

---

### Phase 5: Database Cleanup

**Week 6:**
1. Remove unused options from `wp_options`
2. Remove custom post type entries for deleted CPTs
3. Clean up orphaned post meta
4. Optimize database tables

**SQL Cleanup Commands:**
```sql
-- Remove Hour Tracker options
DELETE FROM wp_options WHERE option_name LIKE 'lccp_hour_%';

-- Remove Document Manager options
DELETE FROM wp_options WHERE option_name LIKE '%dasher_document%';

-- Remove Message System options
DELETE FROM wp_options WHERE option_name LIKE '%dasher_message%';

-- Remove Milestone options
DELETE FROM wp_options WHERE option_name LIKE 'lccp_milestone_%';

-- Clean orphaned post meta for deleted CPTs
DELETE FROM wp_postmeta WHERE post_id IN (
    SELECT ID FROM wp_posts WHERE post_type IN (
        'dasher_document', 'dasher_message', 'lccp_milestone'
    )
);

-- Remove posts for deleted CPTs
DELETE FROM wp_posts WHERE post_type IN (
    'dasher_document', 'dasher_message', 'lccp_milestone'
);
```

---

## TESTING CHECKLIST

Before removing any code, verify:

### General Testing
- [ ] Full database backup created
- [ ] Full files backup created
- [ ] Testing performed on staging environment
- [ ] WordPress debug mode enabled
- [ ] Error log reviewed before changes
- [ ] Error log reviewed after changes

### Feature-Specific Testing
- [ ] All dashboard pages still load (mentor, big bird, PC, student, faculty)
- [ ] Checklist system still works (`[checklist_in_post]`)
- [ ] Dashboard widgets display correctly
- [ ] Role management functions properly
- [ ] LearnDash customizations working
- [ ] Search functionality works
- [ ] Login page displays correctly
- [ ] Admin pages load without errors
- [ ] No PHP fatal errors
- [ ] No JavaScript console errors

### Performance Testing
- [ ] Page load times measured (before/after)
- [ ] Database query count measured (before/after)
- [ ] Admin dashboard load time measured
- [ ] Memory usage checked

---

## ESTIMATED CLEANUP IMPACT

### Code Reduction
| Plugin/Theme | Current Size | After Cleanup | Reduction |
|--------------|--------------|---------------|-----------|
| LCCP Systems | ~30,000 lines | ~12,000 lines | **60%** |
| Fearless You Systems | ~5,000 lines | ~3,000 lines | **40%** |
| Child Theme | ~800 lines | ~650 lines | **19%** |
| LearnDash Favorites | ~500 lines | 0 or keep | **TBD** |
| **TOTAL** | **~36,300 lines** | **~15,650 lines** | **~57%** |

### Database Cleanup
- Remove 3 unused custom post types
- Remove ~50+ unused options
- Clean orphaned post meta
- Remove unused shortcode references

### Performance Impact
**Expected Improvements:**
- **Shortcode scanning:** 20+ fewer shortcodes for WordPress to check
- **Database queries:** Remove queries for unused CPTs and meta
- **Admin overhead:** Fewer admin pages registered
- **Memory usage:** Less code loaded per request
- **Maintenance:** Simpler codebase to debug and update

---

## QUESTIONS FOR STAKEHOLDERS

Before proceeding with cleanup:

1. **Dashboard Usage:**
   - Are mentor/big bird/PC/student dashboard pages actively used by users?
   - Could these be consolidated into fewer pages?
   - Are both `lccp_*` and `dasher_*` shortcode versions needed?

2. **Faculty Dashboard:**
   - Is `[fys_faculty_dashboard]` actively used?
   - Could it be merged with other dashboard systems?

3. **Favorites Feature:**
   - Is the LearnDash favorites functionality used at all?
   - Can users favorite courses or content?

4. **Elephunkie Modules:**
   - Which Elephunkie Toolkit modules are currently enabled?
   - Are all enabled modules actually needed?

5. **Hour Tracking:**
   - Was hour tracking ever used in the past?
   - Any plans to use it in the future?

6. **Document Management:**
   - Was document management ever used?
   - Any stored documents that need migrating?

---

## NEXT STEPS

1. **Review this audit report** with stakeholders
2. **Answer stakeholder questions** above
3. **Schedule staging environment** for testing
4. **Execute Phase 1 cleanup** (low-risk child theme functions)
5. **Verify Phase 1 success** before proceeding
6. **Execute Phase 2 cleanup** (LCCP Systems major cleanup)
7. **Continue through phases** 3-5 as approved

**Contact:** Document any questions or concerns before proceeding.

---

## APPENDIX: Full Feature Inventory

### All Registered Shortcodes (Across All Plugins)

**LCCP Systems:**
```
[lccp_hour_tracker] ❌ 0 uses
[lccp_hours_dashboard] ❌ 0 uses
[lccp_audio_review_player] ❌ 0 uses
[lccp_student_feedback_notes] ❌ 0 uses
[lccp_mentor_hour_reviews] ❌ 0 uses
[lccp_hour_notification_bubble] ❌ 0 uses
[lccp-hour-widget] ❌ 0 uses
[lccp-hour-form] ❌ 0 uses
[lccp-hour-log] ❌ 0 uses
[dasher_document_library] ❌ 0 uses
[dasher_document_viewer] ❌ 0 uses
[lccp_events] ❌ 0 uses
[lccp_event_calendar] ❌ 0 uses
[lccp_dasher] ❌ 0 uses
[lccp_mentor_dashboard] ⚠️ 1 use
[dasher_mentor_dashboard] ⚠️ 4 uses
[lccp_big_bird_dashboard] ⚠️ 1 use
[dasher_bigbird_dashboard] ⚠️ 2 uses
[lccp_pc_dashboard] ⚠️ 1 use
[dasher_pc_dashboard] ⚠️ 1 use
[lccp_student_dashboard] ⚠️ 2 uses
[checklist_in_post] ✅ Multiple uses
```

**Fearless You Systems:**
```
[fys_member_dashboard] ❌ 0 uses
[fys_faculty_dashboard] ⚠️ 1 use
[fys_ambassador_dashboard] ❌ 0 uses
```

**LearnDash Favorite Content:**
```
[favorite_content] ❌ 0 uses
[favorite_course_content] ❌ 0 uses
[favorite_button] ❌ 0 uses
```

### All Custom Post Types

**LCCP Systems:**
```
dasher_message ❌ 0 posts
dasher_document ❌ 0 posts
lccp_milestone ❌ 0 posts
```

**LearnDash Favorite Content:**
```
favcon ⚠️ Unknown post count
```

**Lock Visibility:**
```
visibility_preset ❌ 0 posts
```

### All Admin Pages

**LCCP Systems:**
- ✅ LCCP Systems (main)
- ✅ Settings Manager
- ✅ Module Manager
- ✅ Roles & Capabilities
- ❌ Document Manager (unused CPT)
- ❌ Dasher Milestones (unused CPT)
- ⚠️ Membership Roles Manager
- ⚠️ Dashboard Settings

**Fearless Roles Manager:**
- ✅ Fearless Roles Manager
- ✅ Role Manager
- ✅ Dashboard Redirect

**Fearless You Systems:**
- ✅ Fearless You Members
- ⚠️ Member Settings
- ⚠️ Faculty Management
- ⚠️ Ambassador Program

**Elephunkie Toolkit:**
- ✅ Elephunkie Toolkit (main settings)
- ⚠️ Various module-specific submenus

**LearnDash Favorite Content:**
- ✅ Favorite Content Settings (hidden submenu)

**Lock Visibility:**
- ✅ Block Visibility Settings

---

**End of Audit Report**
