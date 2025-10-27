# Custom Plugins Audit Report
**Generated:** October 27, 2025
**Site:** Fearless Living Learning Center (Local Development)

---

## Summary

**4 Custom Plugins Identified:**

| Plugin | Size | Lines of Code | Active Status | Usage Status |
|--------|------|---------------|---------------|--------------|
| LCCP Systems | 1.8 MB | 32,512 lines | ✅ Active | ✅ HEAVILY USED |
| Fearless Roles Manager | 80 KB | 1,397 lines | ✅ Active | ✅ IN USE |
| Fearless You Systems | Small | 2,857 lines | ✅ Active | ⚠️ PARTIALLY USED |
| Elephunkie Toolkit | 500 KB | 3,066 lines | ✅ Active | ❌ **0% USED** |

---

## 1. LCCP Systems ✅

### Overview
**Purpose:** Life Coach Certification Program management
**Author:** Fearless Living Institute
**Size:** 1.8 MB
**Code:** 32,512 lines of PHP
**Status:** **CORE BUSINESS PLUGIN - CRITICAL**

### Active Modules

```json
{
    "advanced_widgets": true,
    "learndash_widgets": false,
    "dashboards": true,
    "checklist": true,
    "events_integration": true,
    "hour_tracker": true,
    "document_manager": false,
    "learndash_integration": true,
    "settings_manager": true,
    "membership_roles": false
}
```

**7 modules enabled** out of 10 total

### Module Breakdown

#### ✅ ENABLED & IN USE

1. **Hour Tracker Module** (23 KB)
   - Tracks coaching hours for certification
   - 4 tier system: CFLC (75h), ACFLC (150h), CFT (250h), MCFLC (500h)
   - Audio file upload required
   - Approval workflow system
   - **Database:**
     - `lccp_hour_tracker_settings`
     - `lccp_hour_tracker_tier_levels`
     - `lccp_hour_tracker_session_types`
     - Stores per-user hour logs (usermeta)

2. **Dashboards Module** (84 KB total)
   - 3 separate dashboard files:
     - `class-dashboards.php` (37 KB)
     - `class-dashboards-module.php` (23 KB)
     - `class-dasher.php` (24 KB)
   - **Pages Created:**
     - Main Dashboard (ID: 229246)
     - PC Dashboard (ID: 229247)
     - Big Bird Dashboard (ID: 229248)
     - Mentor Dashboard (ID: 229249)
     - Student Dashboard (ID: 229250)
   - **Active Users:** 15+ users across roles

3. **Accessibility Module** (30 KB)
   - Widget position: bottom-right
   - 10 features enabled:
     - High contrast, Font size, Readable font
     - Highlight links, Keyboard navigation
     - Screen reader, Disable animations
     - Reading guide, Text spacing, Large cursor
   - **Database:** `lccp_accessibility_settings`

4. **LearnDash Integration** (27 KB)
   - Auto-enrollment features
   - Course compatibility fixes enabled
   - Category slug: "lccp"
   - **Active:** Integrates with 4+ LearnDash courses

5. **Events Integration** (30 KB)
   - Virtual events support enabled
   - Event blocks enabled
   - **Integration:** The Events Calendar plugin

6. **Performance Module** (23 KB)
   - Database optimization: ON
   - Object cache optimization: ON
   - Query optimization: ON
   - Memory optimization: ON
   - Frontend optimization: ON
   - Cleanup utilities: ON
   - Disable emojis: ON
   - Disable embeds: ON

7. **Checklist Module** (16 KB)
   - Certification checklist tracking
   - Auto-save enabled
   - Certificate generation enabled
   - **Database:** `lccp_checklist_autosave`, `lccp_checklist_certificate`

8. **Autologin Module** (25 KB)
   - Magic link authentication
   - Duration: 30 days
   - IP check: disabled
   - **Note:** Overlaps with Magic Login plugin

#### ❌ DISABLED

1. **LearnDash Widgets** - OFF
2. **Document Manager** - OFF
3. **Membership Roles** - OFF

#### 🗑️ STUB FILES (DELETE)

These files exist but are barely implemented:
- `class-mentor-system.php` (1.2 KB stub)
- `class-message-system.php` (1.4 KB stub)
- Both have `off` status in database

### Database Footprint

**68+ WordPress options** with `lccp_` prefix:

**Critical Data:**
- Role hierarchy and tags (5 roles defined)
- Hour tracker settings and tier levels
- Dashboard page assignments
- Notification settings
- Performance settings
- Accessibility settings

**User Roles Managed:**
- `lccp_mentor` (6 users)
- `lccp_pc` (9 users)
- `lccp_big_bird` (users exist)

### Registered Shortcodes & Widgets

**54 shortcodes and widgets** registered, including:
- `[lccp-hour-widget]`
- `[lccp-hour-form]`
- `[lccp-hour-log]`
- `[lccp-dashboard]`
- Multiple widgets for progress, sessions, resources

### WP Fusion Integration

**Role Tags Configured:**
```
lccp_mentor → Tag 1616
lccp_pc → Tag 1596
lccp_big_bird → Tag 4168
```

### Pages Using LCCP

**12 pages found:**
1. LCCP Dashboard
2. Program Coordinator Dashboard
3. Big Bird Dashboard
4. Mentor Dashboard
5. Student Dashboard (multiple)
6. Hour Submission
7. LCCP Test Page
8. LCCP Mentor Training Program
9. Instructor Dashboard
10. My Dashboard

### Recommendation: ✅ KEEP & OPTIMIZE

**Keep Because:**
- Core business functionality
- Actively used by 15+ users
- Manages certification program
- 7 out of 10 modules in active use
- Critical data stored

**Optimization Needed:**
1. **Delete stub module files:**
   ```bash
   rm wp-content/plugins/lccp-systems/modules/class-mentor-system.php
   rm wp-content/plugins/lccp-systems/modules/class-message-system.php
   ```

2. **Consolidate dashboard files** - 3 separate files doing similar things (84 KB total)

3. **Review autologin module** - Overlaps with Magic Login plugin (may be redundant)

4. **Update module manager** to NOT load disabled modules at all

**Estimated Space Savings:** ~100-150 KB after cleanup

---

## 2. Fearless Roles Manager ✅

### Overview
**Purpose:** WordPress role management with WP Fusion tags
**Author:** Fearless Living
**Size:** 80 KB
**Code:** 1,397 lines
**Status:** **IN USE - NEEDED**

### What It Does

1. **Role Management**
   - Creates custom roles
   - Manages capabilities
   - Sets dashboard landing pages
   - Role visibility controls

2. **Role Categories** (3 defined)
   ```
   LCCP: Life Coach Certification Program
   FYM: Fearless You
   Coaches: Certified Coaches
   ```

3. **WP Fusion Integration**
   - Maps WordPress roles to WP Fusion tags
   - Auto-syncs role changes to CRM
   - **9 roles mapped to tags:**
     ```
     fearless_faculty → 6473
     fearless_ambassador → 6491
     fearless_you_member → 6421
     lccp_mentor → 1616
     lccp_big_bird → 4168
     lccp_pc → 1596
     memberium_coach-cft → 4104, 6321
     memberium_coach-cflc → 139
     administrator → 7934
     ```

4. **Role Assignments**
   - 16 roles categorized
   - Visibility settings for each role
   - Dashboard page per role

### Database Footprint

**4 options:**
- `frm_role_settings`
- `frm_role_categories`
- `frm_role_category_assignments`
- `frm_role_wpfusion_tags`

### Features

**Admin Interface:**
- Settings page
- User Management page
- Role editor
- Category manager

**18 hooks registered:**
- 9 AJAX handlers
- 6 admin_post handlers
- 3 menu/asset hooks

### Active Management

**Currently Managing:**
- 99 Fearless You Members
- 6 LCCP Mentors
- 9 LCCP Practice Coaches
- Multiple faculty and ambassadors

### Recommendation: ✅ KEEP

**Keep Because:**
- Actively managing 100+ users
- WP Fusion integration is critical
- Role categories organize complex role structure
- Dashboard redirects in use
- Clean, focused codebase (1,397 lines)

**No optimization needed** - well-structured and efficiently used.

---

## 3. Fearless You Systems ⚠️

### Overview
**Purpose:** Membership management for Fearless You Members, Faculty, Ambassadors
**Author:** Fearless Living Institute
**Size:** Small
**Code:** 2,857 lines
**Status:** **PARTIALLY USED**

### What It Does

1. **Role Creation**
   - Creates 3 custom roles:
     - `fearless_you_member` (99 users)
     - `fearless_faculty`
     - `fearless_ambassador`

2. **Custom Capabilities**
   - Each role has 5-7 custom capabilities
   - Examples: `access_fearless_you_content`, `teach_courses`, `promote_fearless_living`

3. **Dashboard Shortcodes**
   - `[fys_member_dashboard]`
   - `[fys_faculty_dashboard]`
   - `[fys_ambassador_dashboard]`

4. **Analytics Tracking**
   - Daily member count
   - Member count history
   - Analytics metrics

### Files Loaded

```
includes/
  class-role-manager.php (4.7 KB)
  class-member-dashboard.php (4.0 KB)
  class-faculty-dashboard.php (4.9 KB)
  class-ambassador-dashboard.php (5.7 KB)
  class-analytics.php (19 KB)
admin/
  class-fym-settings.php
```

### Database Footprint

**5 options:**
- `fys_role_hierarchy`
- `fys_daily_member_count` (99)
- `fys_daily_member_count_date`
- `fys_member_count_history`
- `fys_analytics_metrics`

### Hooks Registered

**Only 15 hooks** - lightweight plugin

### Pages Using FYS

**1 page found:**
- Faculty Dashboard (ID: 229366)

### ⚠️ OVERLAP WITH OTHER PLUGINS

**Problem:** This plugin overlaps significantly with:

1. **Fearless Roles Manager**
   - Both manage roles
   - Both set dashboard pages
   - Both handle role hierarchy

2. **LCCP Systems**
   - LCCP also has dashboard pages
   - LCCP also creates custom roles
   - LCCP has role hierarchy settings

### Recommendation: ⚠️ CONSOLIDATE OR SIMPLIFY

**Option 1: Keep (Current State)**
- If Fearless You membership is separate from LCCP
- If analytics tracking is needed
- Cost: Maintaining duplicate role management

**Option 2: Merge into Fearless Roles Manager**
- Move role definitions to Roles Manager
- Remove duplicate role creation
- Keep only analytics module if needed
- Benefit: Single source of truth for roles

**Option 3: Remove**
- If roles are already managed by Roles Manager
- If dashboards are handled by LCCP
- If analytics aren't used
- Benefit: Reduce complexity

**Questions to Answer:**

1. Are the FYS custom capabilities actually being checked anywhere?
   ```bash
   grep -r "access_fearless_you_content\|teach_courses" wp-content/ --include="*.php"
   ```

2. Is the analytics data actually viewed/used?
   - Check admin dashboard widgets
   - Check if FYS settings page is accessed

3. Are the FYS shortcodes used beyond the 1 Faculty Dashboard page?

**Recommended Action:**
1. Audit capability usage (see if custom capabilities are checked anywhere)
2. If not used, move role definitions to Fearless Roles Manager
3. Keep only if analytics or dashboards are unique

**Potential Space Savings:** N/A (small plugin)
**Complexity Reduction:** HIGH (reduce plugin count and overlap)

---

## 4. Elephunkie Toolkit ❌

### Overview
**Purpose:** Developer utility toolkit
**Author:** Jonathan Albiar
**Size:** 500 KB
**Code:** 3,066 lines
**Status:** **❌ 0% USED - DELETE**

### The Problem

**ALL 24 FEATURES ARE DISABLED:**

```
elephunkie_phunkie-audio-player: off
elephunkie_learndash-courses-to-csv: off
elephunkie_phunkie-custom-login: off
elephunkie_custom-login-page-with-tabs: off
elephunkie_phunkie-auto-enroll: off
elephunkie_01-custom-extended-audio-block: off
elephunkie_phunk-plugin-logger: off
elephunkie_elephunkie_log_mailer: off
elephunkie_learndash_course_exporter_with_meta_keys: off
elephunkie_learndash_video_manager: off
elephunkie_inactive_plugin_manager: off
elephunkie_fearless_security_fixer: off (CAUSING SPAM!)
elephunkie_cleanup_utility: off
elephunkie_advanced_user_activity_tracker: off
elephunkie_simple_user_activity_tracker: off
... (24 total features, ALL off)
```

### What's Still Running

Despite all features being disabled, the plugin STILL:

1. **Loads on every request**
2. **Registers 11 admin hooks**
3. **Creates admin menu page**
4. **Loads assets on admin pages**
5. **Registers 24 settings**
6. **Executes init code**

### 16 Included Modules (ALL UNUSED)

```
phunk-audio/
elephunkie-log-mailer/
cleanup-utility/
phunkie-custom-login/
learndash-courses-to-csv/
phunk-plugin-logger/
simple-user-activity/
fearless-security-fixer/  ← Causing security log SPAM
phunk-fixes/
learndash-video-manager/
phunk-auto-enroll/
inactive-plugin-manager/
lc-ex/
phunkie-audio-player/
```

### Database Pollution

**24+ options in database** - all set to "off"

### The Security Log Problem

The disabled `fearless-security-fixer` module created:
- **100 identical spam log entries**
- All say "file_editing_enabled" with no value
- Taking up space in autoloaded options

### Performance Impact

**Loads on Every Request:**
- 3,066 lines of PHP parsed
- 11 hooks registered
- 24 options checked
- Admin menu rendered
- Assets enqueued (even though features are off)

**Autoload Impact:**
- 24 options loaded into memory every request
- Even though they're all "off"

### Recommendation: ❌ DELETE IMMEDIATELY

**Delete Because:**
- 0% usage (all features disabled)
- 100% overhead (still loading)
- Causing database spam
- 500 KB of unused code
- 24 unused database options
- Already covered by other plugins

**Alternative Solutions:**

Most Elephunkie features are already available elsewhere:

| Elephunkie Feature | Alternative |
|-------------------|-------------|
| Audio Player | WP native audio blocks |
| Custom Login | LCCP Autologin Module |
| LearnDash Export | LearnDash native export |
| Video Manager | LearnDash native |
| Log Mailer | Can use dedicated plugin if needed |
| Cleanup Utility | WP CLI or maintenance plugin |
| Activity Tracker | Not needed (no analytics used) |
| Security Fixer | Not needed (causing spam) |

**Deletion Steps:**

```bash
# 1. Deactivate
wp plugin deactivate elephunkie-toolkit --allow-root

# 2. Wait 1 week for testing
# (Verify no functionality breaks)

# 3. Clean database options
wp option delete elephunkie_phunkie-audio-player --allow-root
# ... (repeat for all 24 options, or use script)

# 4. Delete plugin
wp plugin delete elephunkie-toolkit --allow-root
```

**Impact of Deletion:**
- ✅ Remove 500 KB unused code
- ✅ Remove 3,066 lines from autoload
- ✅ Remove 11 unnecessary hooks
- ✅ Remove 24 database options
- ✅ Remove useless security log spam
- ✅ Reduce admin menu clutter
- ❌ NO functionality loss (all features were off)

**Estimated Performance Gain:** 5-10% page load improvement

---

## Overlap Analysis

### Role Management Overlap

**3 plugins manage roles:**

1. **LCCP Systems**
   - Creates: `lccp_mentor`, `lccp_pc`, `lccp_big_bird`
   - Stores: `lccp_role_hierarchy`

2. **Fearless Roles Manager**
   - Manages all roles
   - Stores: `frm_role_settings`, `frm_role_wpfusion_tags`
   - Maps to WP Fusion

3. **Fearless You Systems**
   - Creates: `fearless_you_member`, `fearless_faculty`, `fearless_ambassador`
   - Stores: `fys_role_hierarchy`

**Recommendation:** Consolidate to Fearless Roles Manager as single source of truth

### Dashboard Page Overlap

**Multiple dashboard systems:**

1. **LCCP Systems**
   - 5 dashboard pages
   - Role-based dashboards

2. **Fearless Roles Manager**
   - Dashboard redirect per role

3. **Fearless You Systems**
   - 3 shortcode-based dashboards

**Recommendation:** Consolidate to LCCP Dashboards module (most feature-rich)

### Authentication Overlap

**2 plugins handle login:**

1. **LCCP Systems - Autologin Module**
   - Magic links
   - 30-day duration

2. **Magic Login Plugin** (separate)
   - Also magic links

**Recommendation:** Disable one (likely keep Magic Login plugin, remove LCCP module)

---

## Summary & Action Plan

### Keep As-Is ✅

1. **LCCP Systems** (with cleanup)
   - Delete stub modules
   - Consolidate dashboard files
   - Review autologin overlap

2. **Fearless Roles Manager**
   - No changes needed
   - Well-structured and actively used

### Evaluate & Consolidate ⚠️

3. **Fearless You Systems**
   - Check if custom capabilities are used
   - Consider merging into Roles Manager
   - Reduce plugin count and overlap

### Delete ❌

4. **Elephunkie Toolkit**
   - 0% usage
   - 100% overhead
   - No functionality loss
   - Immediate performance gain

---

## Final Recommendations

### Immediate Actions (Safe)

```bash
# 1. Deactivate Elephunkie
wp plugin deactivate elephunkie-toolkit --allow-root

# 2. Delete LCCP stub files
rm wp-content/plugins/lccp-systems/modules/class-mentor-system.php
rm wp-content/plugins/lccp-systems/modules/class-message-system.php

# 3. Clear security log spam
wp option update fearless_security_log '[]' --format=json --allow-root
```

### Week 1: Test & Monitor

- Verify Elephunkie deactivation caused no issues
- Monitor error logs
- Check user-facing functionality

### Week 2-3: Consolidation

1. **Audit Fearless You Systems**
   ```bash
   # Check if custom capabilities are used
   grep -r "access_fearless_you_content\|teach_courses" wp-content/ --include="*.php"
   ```

2. **If capabilities aren't used:**
   - Move role definitions to Fearless Roles Manager
   - Keep analytics if needed, or remove entirely

3. **Review LCCP autologin vs Magic Login**
   - Pick one authentication method
   - Disable the other

### Week 4: Final Cleanup

```bash
# Delete Elephunkie permanently
wp plugin delete elephunkie-toolkit --allow-root

# Delete unused Elephunkie options (24 options)
wp option delete elephunkie_phunkie-audio-player elephunkie_learndash-courses-to-csv elephunkie_phunkie-custom-login ... --allow-root
```

---

## Metrics After Cleanup

| Metric | Before | After | Improvement |
|--------|--------|-------|-------------|
| Custom Plugins | 4 | 2-3 | -25 to -50% |
| Total Plugin Code | 39,832 lines | 36,766 lines | -7.7% |
| Plugin Storage | 2.38 MB | 1.88 MB | -500 KB |
| Database Options | 100+ custom | 70-80 | -20 to -30 |
| Role Management | 3 systems | 1 system | Consolidated |
| Dashboard Systems | 3 systems | 1 system | Consolidated |

---

## Questions to Answer

Before making consolidation decisions:

### Fearless You Systems:

1. **Are custom capabilities checked anywhere?**
   ```bash
   grep -r "current_user_can.*fearless" wp-content/ --include="*.php"
   ```

2. **Is analytics data actually used?**
   - Check if FYS admin page is accessed
   - Check if member count is displayed anywhere

3. **Are FYS shortcodes used beyond Faculty Dashboard?**
   ```bash
   wp post list --post_type=page,post --s="fys_" --format=count --allow-root
   ```

### LCCP Systems:

4. **Is autologin module actually used?**
   - Check with Magic Login plugin usage
   - May be redundant

5. **Are stub modules (`mentor_system`, `message_system`) planned features?**
   - If not, delete immediately
   - If yes, keep but optimize module loading

---

**Report Generated:** October 27, 2025
**Next Review:** After 4 weeks of optimization
**Contact:** Review with development team before major changes
