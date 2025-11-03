# Future Cleanup Tasks
**Created:** November 3, 2025

---

## LCCP Systems - Modules to Remove

### Decision: Remove Unused Module Code

**Status:** Deferred for later cleanup
**Priority:** LOW (no performance impact while disabled)

### Modules to Remove (Currently Disabled)

These modules are currently **disabled** and should be **completely removed** from the codebase in a future cleanup:

#### 1. Performance Modules
- `modules/class-performance-module.php`
- `modules/class-performance-optimizer.php`

**Reason:** Overkill - proper code doesn't need performance monitoring layers

#### 2. System Status / Monitoring
- `includes/class-lccp-system-status.php`

**Reason:** Overkill - if code is right, no need for system status monitoring

#### 3. Accessibility Manager
- `modules/class-accessibility-module.php`

**Reason:** Not being used, accessibility should be built into core code

#### 4. Message System
- `modules/class-message-system.php`

**Reason:** Not being used

#### 5. Roles Manager
- `modules/class-roles-manager.php`

**Reason:** Not being used

---

## Removal Checklist (When Ready)

### Pre-Removal Steps:
1. [ ] Verify modules still disabled in production
2. [ ] Confirm no pages/functionality using these modules
3. [ ] Backup database and files
4. [ ] Document current module count (for comparison)

### Removal Steps:
1. [ ] Delete module files from GitHub repo
2. [ ] Update module manager's file loading array (remove references)
3. [ ] Update module manager's self-test expectations (remove entries)
4. [ ] Test deployment on staging/local
5. [ ] Verify shortcode count unchanged (should stay 105)
6. [ ] Verify no new errors in logs
7. [ ] Test all critical pages still load

### Files to Delete:

**Modules Directory (5 files):**
```bash
rm modules/class-performance-module.php
rm modules/class-performance-optimizer.php
rm modules/class-accessibility-module.php
rm modules/class-message-system.php
rm modules/class-roles-manager.php
```

**Includes Directory (check for related files):**
```bash
rm includes/class-lccp-system-status.php
# Check for any other status/monitoring files
```

### Code Updates Needed:

**File:** `includes/class-module-manager.php`

**Remove from `$module_files` array (lines ~245-260):**
```php
// DELETE THESE LINES:
'performance' => 'includes/class-performance-optimizer.php',
'roles' => 'includes/class-roles-manager.php',
'messages' => 'includes/class-message-system.php',
'system_status' => 'includes/class-lccp-system-status.php',
'accessibility_manager' => 'includes/class-accessibility-manager.php',
```

**Remove from `self_test_module()` expectations (lines ~330-360):**
```php
// DELETE THESE LINES:
'accessibility_manager' => array('class' => 'LCCP_Accessibility_Manager'),
'performance' => array('class' => 'LCCP_Performance_Optimizer'),
'roles' => array('class' => 'LCCP_Roles_Manager'),
'messages' => array('class' => 'Dasher_Message_System'),
'system_status' => array('class' => 'LCCP_System_Status'),
```

**Remove from `get_modules()` method (if explicitly listed)**

### Expected Results After Removal:

**File count:**
- Before: 93 files
- After: ~85-88 files (-5 to -8 files)
- Modules directory: 17 → 12 files

**Functionality:**
- ✅ All shortcodes still registered (105 total)
- ✅ All dashboard pages still working
- ✅ No errors introduced
- ✅ Cleaner, leaner codebase

**Module status:**
- Enabled modules: Still 6 (unchanged)
- Disabled modules: 14 → 9 (5 removed completely)

---

## Why Not Remove Now?

### Current State is Stable
- Everything working perfectly
- Zero performance impact from disabled modules
- No urgency to remove

### Risk Management
- Just fixed complex module loading issues
- Better to let current setup run for a while
- Validate everything stable before more changes

### Testing Period
- Let site run with current config for 1-2 weeks
- Monitor for any issues
- Ensure no edge cases using these modules

---

## When to Remove

**Recommended Timeline:** 1-2 weeks after November 3, 2025 deployment

**Triggers to proceed:**
- [ ] No issues reported with current deployment
- [ ] All dashboards confirmed working in production
- [ ] Client confirms they don't need these features
- [ ] Time available for thorough testing

**Safe to remove when:**
- Site has been stable with current config
- All functionality validated
- No reported issues from users
- Backup strategy in place

---

## Alternative: Never Remove

**Could also decide to:**
- Keep them indefinitely as disabled modules
- Treat them as "available but not in use"
- Only remove if truly needed (disk space, security audit, etc.)

**Note:** Disabled modules have zero runtime impact, so removal is purely for code cleanliness, not performance.

---

## Philosophy: "If Code is Right, No Need for Monitoring"

**Client's perspective (correct):**
- Performance modules = admission that code might be slow
- System status = admission that system might be unstable
- Proper code shouldn't need these layers

**Therefore:**
- Write clean, efficient code from the start
- Remove monitoring/performance band-aids
- Focus on quality over instrumentation

---

**Status:** NOTED - Will remove later
**Priority:** LOW
**Risk:** LOW (disabled modules don't run)
**Benefit:** Cleaner codebase, aligned with philosophy
