# LCCP Systems - Module Usage Analysis
**Date:** November 3, 2025
**Purpose:** Identify which modules are actually used to create minimal functional version

---

## Shortcodes Actually Used on Site

### Dashboard Shortcodes (Pages)

| Shortcode | Pages Using It | Source File |
|-----------|----------------|-------------|
| `[lccp_dashboard]` | 2 | `modules/class-dashboards.php` |
| `[lccp_student_dashboard]` | 2 | `modules/class-dashboards.php` + `class-dashboards-module.php` |
| `[lccp_mentor_dashboard]` | 1 | `modules/class-dashboards.php` + `class-dashboards-module.php` |
| `[lccp_pc_dashboard]` | 1 | `modules/class-dashboards.php` + `class-dashboards-module.php` |
| `[lccp_bigbird_dashboard]` | 1 | ⚠️ **MISMATCH** - Code uses `lccp_big_bird_dashboard` |
| `[dasher_mentor_dashboard]` | 2 | `modules/class-dashboards-module.php` |
| `[dasher_pc_dashboard]` | 1 | `modules/class-dashboards-module.php` |
| `[dasher_bigbird_dashboard]` | 2 | `modules/class-dashboards-module.php` |

**Total Dashboard Pages:** 9 pages

### Hour Tracker Shortcodes (Pages)

| Shortcode | Pages Using It | Source File |
|-----------|----------------|-------------|
| `[lccp-hour-form]` | 2 | `modules/class-hour-tracker-module.php` + `includes/class-hour-tracker.php` |
| `[lccp-hour-widget]` | 1 | `modules/class-hour-tracker-module.php` + `includes/class-hour-tracker.php` |
| `[lccp-hour-log]` | 1 | `modules/class-hour-tracker-module.php` + `includes/class-hour-tracker.php` |

**Total Hour Tracker Pages:** 2 pages (one is test page)

### Checklist Shortcodes (Courses)

| Shortcode | Usage | Source File |
|-----------|-------|-------------|
| `[checklist_in_post]` | 11 courses | `includes/checklist-migration.php` (backward compat) |
| `[lccp_checklist]` | Active rendering | `modules/class-checklist-module.php` + `includes/class-checklist-manager.php` |

**Total Courses with Checklists:** 11 courses

### Events Shortcodes

| Shortcode | Source File |
|-----------|-------------|
| `[lccp_events]` | `modules/class-events-integration.php` |
| `[lccp_event_calendar]` | `modules/class-events-integration.php` |

---

## Module Files Analysis

### Files in Current Backup (90 files)

#### Modules Directory (17 files)
```
class-accessibility-module.php         ← Not used in pages
class-autologin-module.php             ← Not used in pages
class-checklist-manager.php            ← USED (checklist shortcode)
class-checklist-module.php             ← USED (checklist rendering)
class-dashboards-module.php            ← USED (role dashboards)
class-dashboards.php                   ← USED (main dashboard router)
class-dasher.php                       ← Unknown usage
class-events-integration.php           ← USED (events/calendar)
class-hour-tracker-module.php          ← USED (hour forms)
class-hour-tracker.php                 ← USED (hour tracker)
class-learndash-integration-module.php ← Possibly needed for LearnDash
class-learndash-integration.php        ← Possibly needed for LearnDash
class-mentor-system.php                ← Possibly needed for mentor functions
class-message-system.php               ← Not used
class-performance-module.php           ← Not used in pages
class-performance-optimizer.php        ← Not used
class-roles-manager.php                ← Not used
```

#### Includes Directory (Key Files)
```
class-checklist-manager.php            ← USED (checklist rendering)
checklist-migration.php                ← USED (backward compatibility)
class-hour-tracker.php                 ← USED (hour tracker)
class-hour-tracker-frontend.php        ← May be needed for hour tracker
class-mentor-hour-review.php           ← May be needed for mentor functions
```

---

## Essential vs Optional Modules

### ✅ ESSENTIAL (Must Keep)

**Dashboard System:**
1. `modules/class-dashboards.php` - Main dashboard router (`[lccp_dashboard]`)
2. `modules/class-dashboards-module.php` - Role dashboards (mentor, PC, BigBird, student)

**Hour Tracker System:**
3. `modules/class-hour-tracker-module.php` - Hour form shortcodes
4. `includes/class-hour-tracker.php` - Hour tracker functionality
5. `includes/class-hour-tracker-frontend.php` - Frontend rendering (possibly)
6. `includes/class-mentor-hour-review.php` - Mentor hour review (possibly)

**Checklist System:**
7. `modules/class-checklist-module.php` - Checklist module
8. `includes/class-checklist-manager.php` - Checklist rendering
9. `includes/checklist-migration.php` - Backward compat for `[checklist_in_post]`

**Events System:**
10. `modules/class-events-integration.php` - Events and calendar

**LearnDash Integration:**
11. `modules/class-learndash-integration-module.php` - May be needed
12. `modules/class-learndash-integration.php` - May be needed

**System Files:**
13. `modules/class-dasher.php` - Purpose unclear, may be core

---

### ❓ INVESTIGATE (May or May Not Need)

**Mentor System:**
- `modules/class-mentor-system.php` - Mentor functionality, may be needed for dashboards

**Performance:**
- `modules/class-performance-module.php` - Performance optimizations
- `modules/class-performance-optimizer.php` - Performance optimizations

**Message System:**
- `modules/class-message-system.php` - Not used in pages, but may be used by dashboards

---

### ❌ NOT USED (Can Safely Remove)

**Accessibility:**
- `modules/class-accessibility-module.php` - No pages use this

**Auto-Login:**
- `modules/class-autologin-module.php` - IP-based auto-login, not used

**Roles:**
- `modules/class-roles-manager.php` - No pages use this

---

## Issue: Shortcode Naming Mismatch

### Problem Found

Page uses: `[lccp_bigbird_dashboard]`
Code registers: `[lccp_big_bird_dashboard]` (with underscore)

**Pages affected:**
- Big Bird Dashboard (229248)

**Fix needed:** Add alias in `class-dashboards-module.php`:
```php
add_shortcode('lccp_bigbird_dashboard', array($this, 'render_big_bird_dashboard'));
```

---

## Recommended Minimal Module Set

### Core Modules (MUST KEEP - 10 files)

**Modules Directory (6 files):**
1. class-dashboards.php
2. class-dashboards-module.php
3. class-events-integration.php
4. class-hour-tracker-module.php
5. class-checklist-module.php
6. class-dasher.php (keep for safety, purpose unclear)

**Includes Directory (4 files):**
7. class-checklist-manager.php
8. checklist-migration.php
9. class-hour-tracker.php
10. class-hour-tracker-frontend.php (if exists)

### Optional But Recommended (4 files)

**For LearnDash Integration:**
11. class-learndash-integration-module.php
12. class-learndash-integration.php

**For Mentor Functionality:**
13. class-mentor-system.php
14. class-mentor-hour-review.php

### Can Remove (7 files)

1. class-accessibility-module.php
2. class-autologin-module.php
3. class-message-system.php
4. class-performance-module.php
5. class-performance-optimizer.php
6. class-roles-manager.php
7. modules/class-checklist-manager.php (duplicate, keep includes version)

---

## File Count Comparison

| Version | Total Files | Modules Dir | Notes |
|---------|-------------|-------------|-------|
| **Current Backup** | 90 | 17 | Fully functional |
| **GitHub Cleaned** | 77 | 3 | BROKEN - too aggressive |
| **Proposed Minimal** | ~80-85 | 10-14 | Functional + clean |

---

## Testing Plan for Minimal Version

### Phase 1: Create Minimal Version
1. Copy essential 10-14 module files to GitHub repo
2. Keep all other plugin structure intact
3. Add shortcode alias for `lccp_bigbird_dashboard`

### Phase 2: Test Deployment
1. Deploy to local site
2. Check registered shortcodes (should be ~102, not 97)
3. Verify module manager shows correct status

### Phase 3: Functional Testing
Test each shortcode on its page:

**Dashboard Tests:**
- [ ] LCCP Dashboard (229246) - `[lccp_dashboard]`
- [ ] My Dashboard (229365) - `[lccp_dashboard]`
- [ ] Big Bird Dashboard (229248) - `[lccp_bigbird_dashboard]`
- [ ] Mentor Dashboard (229249) - `[lccp_mentor_dashboard]`
- [ ] PC Dashboard (229247) - `[lccp_pc_dashboard]`
- [ ] Student Dashboard (229250) - `[lccp_student_dashboard]`

**Legacy Dashboard Tests:**
- [ ] BigBird Dashboard (229222) - `[dasher_bigbird_dashboard]`
- [ ] Mentor Dashboard (228351) - `[dasher_mentor_dashboard]`
- [ ] PC Dashboard (229223) - `[dasher_pc_dashboard]`

**Hour Tracker Tests:**
- [ ] Hour Submission (229219) - `[lccp-hour-form]`
- [ ] LCCP Test Page (229251) - All hour shortcodes

**Checklist Tests:**
- [ ] Test 2-3 courses with `[checklist_in_post]`
- [ ] Verify checklists render correctly

**Events Tests:**
- [ ] Test events display
- [ ] Test calendar display

### Phase 4: Verify No Regression
- [ ] All pages still load (HTTP 200/302)
- [ ] No new PHP errors
- [ ] Shortcode count matches (102)
- [ ] Module manager shows correct status

---

## Next Steps

### Option 1: Conservative (Recommended)
Keep 14 modules (10 essential + 4 optional) for safety.

**Files to copy to GitHub repo:**
```bash
# Essential Modules
class-dashboards.php
class-dashboards-module.php
class-events-integration.php
class-hour-tracker-module.php
class-checklist-module.php
class-dasher.php

# Optional but Recommended
class-learndash-integration-module.php
class-learndash-integration.php
class-mentor-system.php

# Essential Includes
class-checklist-manager.php
checklist-migration.php
class-hour-tracker.php
class-hour-tracker-frontend.php
class-mentor-hour-review.php
```

### Option 2: Minimal
Keep only 10 essential modules, test thoroughly.

### Option 3: Investigate First
Before removing any more files, check:
1. What `class-dasher.php` does
2. If mentor system is needed for dashboards
3. If LearnDash integration is critical
4. If message system is used by dashboards internally

---

## Recommendation

**Start with Option 1 (Conservative)**

- Copy 14 module files to GitHub repo
- Add shortcode alias for `lccp_bigbird_dashboard`
- Test deployment
- If successful, can consider removing optional modules later
- Better to have 4 extra files than broken functionality

**File count:**
- Current broken: 77 files (missing critical modules)
- Proposed: ~80-85 files (all essential modules)
- Current working: 90 files (includes unused modules)

**Savings:** 5-10 files removed while maintaining full functionality

---

**Status:** Analysis complete, awaiting decision on approach
**Recommended:** Conservative approach (14 modules)
**Next Action:** Copy essential module files to GitHub repo
