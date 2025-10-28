# Database Analysis Summary

**Date**: 2025-10-28
**Database**: `/Users/varundubey/Local Sites/you/app/sql/local.sql` (154 MB)

---

## What Was Analyzed

Searched the WordPress database to understand actual usage of the Fearless custom plugins before deciding whether to keep or delete them.

---

## Key Findings

### ✅ Plugins Are Active
Both plugins are currently active on the site:
- **Fearless Roles Manager** (position i:7 in active_plugins array)
- **Fearless You Systems** (position i:8 in active_plugins array)

### ✅ 18 Users Depend on Custom Roles

**fearless_you_member** (9 users):
- IDs: 8, 12, 19, 39, 56, 59, 68, 78, 79
- Capabilities: access_fearless_you_content, view_membership_dashboard, participate_in_community, access_monthly_trainings, download_resources

**fearless_faculty** (4 users):
- IDs: 3831, 5372, 9932, 13153
- Capabilities: teach_courses, create_content, moderate_discussions, view_faculty_dashboard, access_faculty_resources

**fearless_ambassador** (5 users):
- IDs: 3632, 4131, 9142, 16706, 165129
- Capabilities: promote_fearless_living, access_ambassador_resources, view_ambassador_dashboard, refer_members

### ❌ 2 Unused Roles (Can Be Deleted)
- **fearless_you_subscriber**: 0 users
- **fearless_you_pending**: 0 users

### ⚠️ Faculty Dashboard In Use

**Page**: "Faculty Dashboard" (ID: 229366)
**URL**: `/faculty-dashboard/`
**Shortcode**: `[fys_faculty_dashboard]`

**Not a simple shortcode** - it's a comprehensive analytics system:
- Member growth & churn tracking (pulls from WordPress user registrations)
- Active/paused/canceled subscription breakdown (integrates with WP Fusion/Keap)
- 6-month retention rate charts
- Course engagement metrics (integrates with LearnDash)
- Community forum activity (integrates with BuddyBoss/bbPress)
- Upcoming events calendar (should integrate with Events Calendar plugin)
- Quick action buttons (View Members, Manage Courses, Send Announcements, View Reports)

**Complexity**: ~800 lines of PHP + HTML + CSS + JavaScript

---

## Decision: Keep Plugins (For Now)

### Reasoning:

1. **18 users actively have these roles** - deleting would require role reassignment
2. **Faculty dashboard provides analytics** - more than just role management
3. **Unknown usage** - need to verify if 4 faculty users actually use the dashboard
4. **Integration value** - connects WP Fusion, LearnDash, Events Calendar data in one view

### Risk of Deletion Without Testing:

- Faculty members lose analytics dashboard
- Would need to rebuild metrics in separate tools
- 500KB of code might actually be providing value

---

## Next Steps

### 1. Test Faculty Dashboard (PRIORITY)

Log in as admin or faculty user → Visit `/faculty-dashboard/` → Complete testing checklist in `FACULTY-DASHBOARD-TESTING.md`

**Key questions:**
- Is data real or placeholder/simulated?
- Do charts render correctly?
- Do integrations work (WP Fusion, LearnDash, Events)?
- Are JavaScript functions implemented?

### 2. Get User Feedback

Contact 4 faculty users (IDs: 3831, 5372, 9932, 13153):
- Do you use the Faculty Dashboard page?
- Which features do you rely on?
- Could you get this info from WordPress admin instead?

### 3. Make Final Decision

**Option A: Dashboard provides value**
→ Keep both plugins
→ Accept 500KB of custom code as worthwhile
→ Roles managed by these plugins

**Option B: Dashboard not used / broken**
→ Delete both plugins
→ Replace Faculty Dashboard page with simple HTML links
→ Install User Role Editor plugin to manage roles
→ 18 users keep their roles (roles stay in database even after plugin deletion)

### 4. Clean Up Unused Roles (Optional)

Can be done regardless of decision:
- Install User Role Editor plugin
- Delete `fearless_you_subscriber` (0 users)
- Delete `fearless_you_pending` (0 users)

---

## Files Created

1. **SITE-ACTIONS-NEEDED.md** - Complete evaluation plan with phases
2. **ROLES-TO-CHECK.md** - Database-verified role usage with all 18 user IDs
3. **FACULTY-DASHBOARD-TESTING.md** - Detailed testing checklist for dashboard functionality
4. **DATABASE-ANALYSIS-SUMMARY.md** - This file

---

## Important Notes

### Roles Persist After Plugin Deletion
WordPress stores roles in the `wp_user_roles` option in the database. Even if you delete both plugins:
- All 5 roles remain registered
- All 18 users keep their role assignments
- User Role Editor plugin will auto-detect and manage them
- No data loss

### What Would Break
If you delete the plugins without preparation:
- Faculty Dashboard page would show raw shortcode: `[fys_faculty_dashboard]`
- No other pages affected (other shortcodes not in use)

### Plugin Code Size
- **Fearless Roles Manager**: 8 files, 184 KB
- **Fearless You Systems**: 11 files, 316 KB
- **Total**: 19 files, 500 KB

Is this worth keeping? Depends on dashboard value.

---

## Technical Details

### Database Queries Run

1. Active plugins: `grep "option_name.*active_plugins"`
2. Shortcode usage: `grep "[fys_.*_dashboard]"` in wp_posts
3. Role assignments: `grep "fearless_.*" in wp_usermeta WHERE meta_key='wp_capabilities'`
4. Role definitions: `grep "wp_user_roles"` in wp_options

### Data Sources Found

**Faculty Dashboard integrates with:**
- WordPress users table (`wp_users`, `wp_usermeta`)
- LearnDash LMS (course enrollments via `learndash_get_users_for_course()`)
- WP Fusion/Keap (subscription data via `FYS_Analytics` class)
- BuddyBoss/bbPress (forum posts via `wp_posts` WHERE `post_type='reply'`)
- The Events Calendar (upcoming events - currently hardcoded, should integrate)

**Known limitations:**
- Member retention chart uses simulated data (`rand(85, 95)`)
- Upcoming events are hardcoded array, not pulling from Events Calendar
- Subscription data depends on WP Fusion being configured

---

**Bottom line**: Test the dashboard first, then decide if it's worth keeping.
