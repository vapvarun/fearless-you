# Plugin Deployment Plan - November 3, 2025

## Objective

Deploy cleaned custom plugins from GitHub repo to local WordPress installation, one at a time, with testing after each deployment to ensure nothing breaks.

---

## Plugins to Deploy (5 total)

### Order (Low Risk → High Risk)

1. **learndash-favorite-content** (Lowest Risk)
   - Simple favorite button functionality
   - Limited scope, easy to test

2. **fearless-roles-manager**
   - Custom role management
   - Important but isolated functionality

3. **elephunkie-toolkit**
   - Various toolkit features
   - Medium complexity

4. **fearless-you-systems**
   - System-wide features
   - Higher complexity

5. **lccp-systems** (Highest Risk - MOST CRITICAL)
   - Dashboards, hour tracker, multiple modules
   - Most complex, many dependencies
   - Known issues with shortcode registration

---

## Deployment Process (Per Plugin)

### Step 1: Pre-Deployment
- [ ] Check plugin is active
- [ ] Note current version
- [ ] Check how many files in current version
- [ ] Create baseline test of functionality

### Step 2: Backup & Deploy
- [ ] Rename current plugin: `plugin-name` → `plugin-name-backup-nov3`
- [ ] Copy cleaned version from repo
- [ ] Verify file permissions

### Step 3: Test
- [ ] Test homepage (http://you.local/)
- [ ] Test course page
- [ ] Test login page
- [ ] Test plugin-specific functionality
- [ ] Check PHP error log for new errors
- [ ] Check WordPress admin for errors

### Step 4: Document
- [ ] Record file count changes
- [ ] Record any issues found
- [ ] Note test results
- [ ] Decision: Keep or rollback

---

## Testing Checklist (After Each Plugin)

### Critical Pages
- [ ] Homepage: `http://you.local/`
- [ ] Course Grid: `http://you.local/course-grid/`
- [ ] Sample Course: `http://you.local/courses/courage-to-choose/`
- [ ] Login Page: `http://you.local/login/`
- [ ] Dashboard: `http://you.local/my-dashboard/`

### Error Checks
- [ ] No new PHP errors in `/wp-content/debug.log`
- [ ] No WordPress admin error notices
- [ ] No console errors in browser
- [ ] No white screens/500 errors

### Functional Tests
- [ ] Can login to site
- [ ] Can access course content
- [ ] Can navigate between pages
- [ ] No missing images/CSS

---

## Rollback Procedure

If any issues occur:

```bash
cd /Users/varundubey/Local\ Sites/you/app/public/wp-content/plugins

# Stop - don't continue with more plugins
# Rollback the problem plugin:
rm -rf plugin-name
mv plugin-name-backup-nov3 plugin-name

# Clear WordPress cache
wp cache flush

# Test site again
```

---

## Success Criteria

For each plugin deployment:
- ✅ All critical pages load (200 or 302 status)
- ✅ No new PHP errors
- ✅ No functionality lost
- ✅ Plugin activates without errors
- ✅ Site remains accessible

---

## Expected Results

Based on repo cleanup work done:
- Fewer files per plugin (development files removed)
- Smaller plugin sizes (backups removed)
- Cleaner codebase (unused code removed)
- Same functionality (only removed unused code)

---

## Current Plugin Status

| Plugin | Status | Version | Files (Est) |
|--------|--------|---------|-------------|
| learndash-favorite-content | Active | 1.0.3 | ~9 |
| fearless-roles-manager | Active | 1.0.0 | ~5 |
| elephunkie-toolkit | Active | 3.2 | ~8 |
| fearless-you-systems | Active | 1.0.0 | ~7 |
| lccp-systems | Active | 1.0.0 | ~10 |

---

## Timeline Estimate

- **Per Plugin:** 10-15 minutes
- **Total Time:** 50-75 minutes
- **Includes:** Deploy, test, document each plugin

---

## Risk Assessment

### Low Risk
- learndash-favorite-content
- fearless-roles-manager

### Medium Risk
- elephunkie-toolkit
- fearless-you-systems

### High Risk
- lccp-systems (many dependencies, known shortcode issues)

---

## Notes

- **Stop if any plugin fails** - Don't continue deploying if one breaks
- **Document everything** - Record what works and what doesn't
- **Keep backups** - All backups will be kept for 30 days
- **Test thoroughly** - Better to catch issues now than in production

---

**Ready to Begin:** Yes
**Estimated Completion:** 1-1.5 hours
**Start Time:** November 3, 2025
