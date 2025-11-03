# Shortcode Audit - Client Review
**Date:** November 3, 2025
**Site:** Fearless You
**Purpose:** Identify pages with non-functional shortcodes that need cleanup or fixing

---

## What This Report Is About

We've audited all 44 published pages on your site and found that **13 pages have shortcodes that are not working properly**. These pages may appear broken or show raw shortcode text instead of the intended functionality.

This document lists each affected page so you can decide:
- ✅ **Keep & Fix** - This page is important, please fix the functionality
- ❌ **Remove** - This page is no longer needed, can be deleted
- 🔄 **Consolidate** - Merge with another similar page

---

## Summary Statistics

| Category | Count |
|----------|-------|
| **Total Pages Scanned** | 44 |
| **Pages with Broken Shortcodes** | 13 |
| **Duplicate Dashboard Pages Found** | 6 |
| **High Priority Issues** | 10 |
| **Medium Priority Issues** | 5 |

---

# Pages Requiring Your Decision

## 🔴 HIGH PRIORITY - Dashboard & Core Functionality Pages

These pages are likely used by your team/members regularly and are currently not working.

---

### 1. Big Bird Dashboard
**URL:** http://you.local/lccp-dashboard/bigbird-dashboard/
**Page ID:** 229248
**Issue:** Shortcode `[lccp_bigbird_dashboard]` is not working
**Status:** ❌ PAGE IS BROKEN

**Your Decision:**
- [ ] ✅ Keep & Fix - We use this page regularly
- [ ] ❌ Remove - No longer needed
- [ ] 💬 Notes: _______________________________________________

---

### 2. BigBird Dashboard (Old)
**URL:** http://you.local/bigbird-dashboard/
**Page ID:** 229222
**Issue:** Shortcode `[dasher_bigbird_dashboard]` is not working
**Status:** ❌ PAGE IS BROKEN
**Note:** You have multiple BigBird dashboard pages - this appears to be a duplicate

**Your Decision:**
- [ ] ✅ Keep & Fix
- [ ] ❌ Remove - Duplicate page
- [ ] 🔄 Consolidate with page 229248
- [ ] 💬 Notes: _______________________________________________

---

### 3. BigBird Dashboard (Legacy)
**URL:** http://you.local/dashboard-bb/
**Page ID:** 228352
**Issue:** Shortcode `[dasher_bigbird_dashboard]` is not working
**Status:** ❌ PAGE IS BROKEN
**Note:** Another duplicate BigBird dashboard page

**Your Decision:**
- [ ] ✅ Keep & Fix
- [ ] ❌ Remove - Duplicate page
- [ ] 🔄 Consolidate with page 229248
- [ ] 💬 Notes: _______________________________________________

---

### 4. Mentor Dashboard (Old)
**URL:** http://you.local/dashboard-m/
**Page ID:** 228351
**Issue:** Shortcode `[dasher_mentor_dashboard]` is not working
**Status:** ❌ PAGE IS BROKEN
**Note:** You have a working Mentor Dashboard at http://you.local/lccp-dashboard/mentor-dashboard/ (ID: 229249)

**Your Decision:**
- [ ] ✅ Keep & Fix
- [ ] ❌ Remove - We use the new dashboard page instead
- [ ] 🔄 Redirect to new mentor dashboard page
- [ ] 💬 Notes: _______________________________________________

---

### 5. Mentor Dashboard (Legacy)
**URL:** http://you.local/mentor-dashboard/
**Page ID:** 229221
**Issue:** Shortcode `[dasher_mentor_dashboard]` is not working
**Status:** ❌ PAGE IS BROKEN
**Note:** Another duplicate - working page exists at ID 229249

**Your Decision:**
- [ ] ✅ Keep & Fix
- [ ] ❌ Remove - We use the new dashboard page instead
- [ ] 🔄 Redirect to new mentor dashboard page
- [ ] 💬 Notes: _______________________________________________

---

### 6. PC Dashboard (Legacy)
**URL:** http://you.local/pc-dashboard/
**Page ID:** 229223
**Issue:** Shortcode `[dasher_pc_dashboard]` is not working
**Status:** ❌ PAGE IS BROKEN
**Note:** You have a working PC Dashboard at http://you.local/lccp-dashboard/pc-dashboard/ (ID: 229247)

**Your Decision:**
- [ ] ✅ Keep & Fix
- [ ] ❌ Remove - We use the new dashboard page instead
- [ ] 🔄 Redirect to new PC dashboard page
- [ ] 💬 Notes: _______________________________________________

---

### 7. Hour Submission
**URL:** http://you.local/hour-submission/
**Page ID:** 229219
**Issue:** Shortcode `[lccp-hour-form]` is not working
**Status:** ❌ PAGE IS BROKEN
**Purpose:** For students/mentors to submit coaching hours

**Your Decision:**
- [ ] ✅ Keep & Fix - We need this for hour tracking
- [ ] ❌ Remove - No longer tracking hours this way
- [ ] 💬 Notes: _______________________________________________

---

### 8. LCCP Test Page
**URL:** http://you.local/lccp-test-page/
**Page ID:** 229251
**Issue:** Multiple hour tracking shortcodes not working
**Status:** ❌ PAGE IS BROKEN
**Note:** This appears to be a test/development page

**Your Decision:**
- [ ] ✅ Keep & Fix - Actually being used
- [ ] ❌ Remove - Just a test page, can delete
- [ ] 💬 Notes: _______________________________________________

---

## 🟡 MEDIUM PRIORITY - Functional Pages

These pages have specific features that aren't working but may be less critical.

---

### 9. Courses
**URL:** http://you.local/courses/
**Page ID:** 224639
**Issue:** Shortcode `[phunk_courses_by_category]` is not working
**Status:** ❌ PAGE IS BROKEN
**Purpose:** Display courses organized by category
**Fix Option:** Can be replaced with working LearnDash course grid

**Your Decision:**
- [ ] ✅ Keep & Fix - Important course listing page
- [ ] ❌ Remove - Not needed anymore
- [ ] 🔄 Replace with LearnDash course grid instead
- [ ] 💬 Notes: _______________________________________________

---

### 10. Document Library
**URL:** http://you.local/document-library/
**Page ID:** 218999
**Issue:** Shortcode `[doc_library]` is not working (module is disabled)
**Status:** ❌ PAGE IS BROKEN
**Purpose:** Central location for downloadable resources/documents

**Your Decision:**
- [ ] ✅ Keep & Fix - We need document library functionality
- [ ] ❌ Remove - Documents are stored elsewhere now
- [ ] 💬 Notes: _______________________________________________

---

### 11. Replays
**URL:** http://you.local/replays/
**Page ID:** 78
**Issue:** Shortcode `[memb_has_any_tag]` is not working
**Status:** ⚠️ PARTIALLY WORKING - Most content displays, but conditional logic broken
**Purpose:** Display lesson/topic replays with membership restrictions

**Your Decision:**
- [ ] ✅ Keep & Fix - Fix the conditional display
- [ ] ❌ Remove conditional logic - Show to everyone
- [ ] 💬 Notes: _______________________________________________

---

### 12. Your Checklists
**URL:** http://you.local/your-checklists/
**Page ID:** 225077
**Issue:** Shortcode `[user_checklists]` is not working
**Status:** ❌ PAGE IS BROKEN
**Purpose:** Display user's personal checklists
**Fix Option:** Can be replaced with `[lccp_checklist]` shortcode

**Your Decision:**
- [ ] ✅ Keep & Fix - Checklists are important
- [ ] ❌ Remove - Not using checklist feature anymore
- [ ] 🔄 Replace with new checklist shortcode
- [ ] 💬 Notes: _______________________________________________

---

### 13. Instructor Dashboard
**URL:** http://you.local/instructor-dashboard/
**Page ID:** 225034
**Issue:** False positive - no actual broken shortcodes, just display quirks
**Status:** ✅ LIKELY WORKING
**Note:** Technical false alarm - page probably works fine

**Your Decision:**
- [ ] ✅ Keep as-is - It's working fine
- [ ] 🔧 Clean up page formatting
- [ ] 💬 Notes: _______________________________________________

---

## ✅ Working Pages (No Action Needed)

These pages have shortcodes that are working correctly:

| Page | URL | Status |
|------|-----|--------|
| **Faculty Dashboard** | http://you.local/faculty-dashboard/ | ✅ Working |
| **LCCP Dashboard** | http://you.local/lccp-dashboard/ | ✅ Working |
| **My Dashboard** | http://you.local/my-dashboard/ | ✅ Working |
| **Mentor Dashboard (New)** | http://you.local/lccp-dashboard/mentor-dashboard/ | ✅ Working |
| **Program Coordinator Dashboard (New)** | http://you.local/lccp-dashboard/pc-dashboard/ | ✅ Working |
| **Student Dashboard** | http://you.local/student-dashboard/ | ✅ Working |
| **Student Dashboard (New)** | http://you.local/lccp-dashboard/student-dashboard/ | ✅ Working |
| **Love Notes Setup** | http://you.local/love-notes-setup/ | ✅ Working |

---

## 💡 Our Recommendations

### Immediate Actions:
1. **Consolidate Duplicate Dashboards**
   - Keep: New dashboard pages under `/lccp-dashboard/`
   - Remove: Old legacy dashboard pages
   - Setup redirects from old URLs to new ones

2. **Fix Core Functionality**
   - Hour Submission page (if still tracking hours)
   - Document Library (if still using)

3. **Delete Test Pages**
   - LCCP Test Page (ID: 229251) - appears to be development only

### Technical Issues Found:
- **LCCP Systems plugin** has module loading issues
- Some shortcodes exist in code but aren't registering properly
- This requires technical investigation by development team

---

## Decision Summary Table

Quick checklist of your decisions:

| Page | Keep & Fix | Remove | Consolidate | Notes |
|------|-----------|--------|-------------|-------|
| Big Bird Dashboard (229248) | ☐ | ☐ | ☐ | |
| BigBird Dashboard (229222) | ☐ | ☐ | ☐ | |
| BigBird Dashboard (228352) | ☐ | ☐ | ☐ | |
| Mentor Dashboard (228351) | ☐ | ☐ | ☐ | |
| Mentor Dashboard (229221) | ☐ | ☐ | ☐ | |
| PC Dashboard (229223) | ☐ | ☐ | ☐ | |
| Hour Submission (229219) | ☐ | ☐ | ☐ | |
| LCCP Test Page (229251) | ☐ | ☐ | ☐ | |
| Courses (224639) | ☐ | ☐ | ☐ | |
| Document Library (218999) | ☐ | ☐ | ☐ | |
| Replays (78) | ☐ | ☐ | ☐ | |
| Your Checklists (225077) | ☐ | ☐ | ☐ | |

---

## Next Steps

### For Client:
1. Review each page above and check your decisions
2. Click each URL to see if the page is actually being used
3. Add any notes about how the page is used or by whom
4. Return completed document to development team

### For Development Team:
After receiving client decisions:
1. Delete pages marked for removal
2. Set up redirects for consolidated pages
3. Fix technical issues for "Keep & Fix" pages
4. Investigate LCCP Systems module loading issues

---

## Questions?

If you're unsure about any page:
- Check your navigation menus - is it linked anywhere?
- Search your member communications - do you reference this URL?
- Ask your team - do mentors/students use this page?
- Check analytics - is this page getting traffic?

**When in doubt, we recommend "Keep & Fix" - we can always remove later if it's truly not needed.**

---

**Document Version:** 1.0
**Generated:** November 3, 2025
**Technical Report:** See `SHORTCODE-AUDIT-REPORT.md` for developer details
