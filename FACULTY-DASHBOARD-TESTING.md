# Faculty Dashboard - Testing Guide

## Quick Access

**Page URL**: `/faculty-dashboard/` (Page ID: 229366)
**Shortcode**: `[fys_faculty_dashboard]`
**Who can access**: Users with `fearless_faculty` role OR administrators

## 4 Faculty Users Who Have Access

- User ID: 3831
- User ID: 5372
- User ID: 9932
- User ID: 13153

---

## Dashboard Sections to Test

### 1. Header Section
**What to check:**
- [ ] User avatar displays
- [ ] Welcome message shows: "Welcome back, [Name]!"
- [ ] "Fearless Faculty" badge shows
- [ ] "Create Course" button works (links to `/wp-admin/post-new.php?post_type=sfwd-courses`)
- [ ] "Export Report" button triggers download

### 2. Membership Overview (4 Metric Cards)

#### Card 1: Total Members
**Data source**: `get_users(array('role' => 'fearless_you_member'))`
- [ ] Shows total count (should be **9 users** based on database)
- [ ] Shows month-over-month growth percentage
- [ ] Shows "New this month" count
- [ ] Shows "Last month" count

#### Card 2: Active Subscriptions
**Data source**: WP Fusion/Keap integration (class `FYS_Analytics`)
- [ ] Shows active subscription count
- [ ] Shows breakdown: Active / Paused / Canceled
- [ ] Shows churn rate percentage
- [ ] Check if WP Fusion is configured correctly

#### Card 3: Course Engagement
**Data source**: LearnDash (`learndash_get_users_for_course()`)
- [ ] Shows total enrollments across all courses
- [ ] Shows "Your Courses" count (courses authored by current faculty user)
- [ ] Shows "Avg per course" calculation

#### Card 4: Community Activity
**Data source**: bbPress (`post_type = 'reply'`)
- [ ] Shows forum posts this week count
- [ ] Shows mini bar graph (7 days of activity)
- [ ] Check if bbPress/BuddyBoss forums are active

### 3. Subscription Trends Chart
**Data source**: Historical user registration data
- [ ] Chart renders (uses HTML5 canvas)
- [ ] Shows 6 months of data by default
- [ ] Buttons work: "6 Months" / "3 Months" / "1 Month"
- [ ] Legend shows: New Members / Active Members / Churned

### 4. Member Retention Widget (Left Column)
**Data source**: Simulated data (currently hardcoded `rand(85, 95)`)
- [ ] Shows 6 months of retention rates
- [ ] Shows average retention percentage
- [ ] Note: This appears to be simulated data, not real

### 5. Recent Member Activity (Left Column)
**Data source**: `get_users(array('role' => 'fearless_you_member', 'orderby' => 'registered'))`
- [ ] Shows 5 most recent member signups
- [ ] Shows avatar, name, and "Joined X days ago"
- [ ] Should show users from IDs: 8, 12, 19, 39, 56, 59, 68, 78, 79

### 6. Upcoming Events Widget (Right Column)
**Data source**: Hardcoded array (should integrate with Events Calendar)
- [ ] Shows 3 upcoming events
- [ ] Currently shows placeholder data:
  - "Monthly Fearless Living Workshop"
  - "Q&A with Rhonda Britten"
  - "Fearless You Member Onboarding"
- [ ] **TODO**: Check if this should pull from The Events Calendar plugin

### 7. Quick Actions Widget (Right Column)
- [ ] "View All Members" → `/wp-admin/users.php?role=fearless_you_member`
- [ ] "Manage Courses" → `/wp-admin/edit.php?post_type=sfwd-courses`
- [ ] "Send Announcement" → Triggers JavaScript `sendAnnouncement()` (check if implemented)
- [ ] "View Reports" → Triggers JavaScript `viewReports()` (check if implemented)

---

## Data Integration Checklist

### WP Fusion / Keap
**What to check:**
- [ ] Is WP Fusion plugin active?
- [ ] Is Keap account connected?
- [ ] Does `FYS_Analytics` class exist and work?
- [ ] Are subscription statuses syncing from Keap?

**If not working:**
- Dashboard will show fallback data
- Active subscriptions = total members count
- Canceled/paused will be placeholder numbers

### LearnDash
**What to check:**
- [ ] LearnDash LMS plugin is active (confirmed in database)
- [ ] Faculty users have authored courses
- [ ] `learndash_get_users_for_course()` function returns enrollment data

### BuddyBoss / bbPress
**What to check:**
- [ ] BuddyBoss Platform is active (confirmed in database)
- [ ] Forums are enabled
- [ ] Recent forum activity exists (replies posted this week)

### The Events Calendar
**What to check:**
- [ ] The Events Calendar plugin is active (confirmed in database)
- [ ] Upcoming events are published
- [ ] Dashboard should pull from Events Calendar (currently using hardcoded data)

---

## Known Issues to Look For

### JavaScript Errors
The dashboard uses custom JavaScript for:
- Chart rendering (HTML5 canvas)
- Export report function
- Send announcement function
- View reports function

**Check browser console for errors**

### Simulated Data
These sections use **fake/random data** instead of real metrics:
- **Member Retention** → Uses `rand(85, 95)` - not real retention rates
- **Upcoming Events** → Hardcoded array - should integrate with Events Calendar
- **Chart data** → Uses simulated numbers if real data not available

### Missing Analytics Class
If `FYS_Analytics` class doesn't exist or WP Fusion isn't configured:
- Subscription metrics will show fallback numbers
- Churn rate will be inaccurate

---

## Questions to Answer After Testing

1. **Is the dashboard showing real data or placeholder data?**
   - Real member counts?
   - Real subscription statuses?
   - Real course enrollments?

2. **Do faculty users actually use this page?**
   - Check with users 3831, 5372, 9932, 13153
   - How often do they visit?
   - Which sections do they rely on?

3. **Can this data be found elsewhere?**
   - WordPress Users page
   - LearnDash Reports
   - WP Fusion dashboard
   - The Events Calendar admin

4. **Is 500KB of custom code worth it?**
   - If YES → Keep plugins
   - If NO → Delete and use standard WordPress/LearnDash reports

---

## Next Steps Based on Testing

### Scenario 1: Dashboard works perfectly, users love it
✅ Keep both plugins
✅ Keep managing roles with these plugins
✅ Consider this valuable custom functionality

### Scenario 2: Dashboard shows mostly placeholder data
⚠️ Fix integrations (WP Fusion, Events Calendar)
⚠️ Remove simulated data, show real metrics
⚠️ OR delete if not worth fixing

### Scenario 3: Nobody uses it
❌ Delete both plugins
❌ Replace page with simple HTML links
❌ Use User Role Editor for role management
