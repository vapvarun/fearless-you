# Claude Code Development Guide - Fearless You

## Project Overview
This is the Fearless You WordPress site codebase. Custom plugins and configurations are version-controlled here and synced to the active WordPress installation.

**Last Updated**: November 5, 2025
**WordPress Version**: 6.8.3
**PHP Version**: 8.2.27
**Environment**: Local (via Local by Flywheel)

---

## Repository Structure

### Important Directories

```
fearless-you/
├── plugins/                          # Custom plugins (version controlled)
│   ├── lccp-systems/                # Life Coach Certification Program Systems
│   ├── fearless-roles-manager/      # Custom role management
│   ├── fearless-you-systems/        # Core Fearless You functionality
│   ├── elephunkie-toolkit/          # Toolkit (currently DEACTIVATED)
│   └── learndash-favorite-content/  # LearnDash extension
├── wp-content/
│   └── mu-plugins/                  # Must-use plugins (auto-load)
│       └── fix-translation-loading.php  # Translation warnings fix
├── docs/                            # Documentation & reports
│   ├── dashboard-cleanup-report-nov5-2025.txt
│   ├── site-audit-recommendations-nov5-2025.txt
│   └── debug-warnings-fix-nov5-2025.md
├── backups/                         # Code backups
└── README.md                        # Project overview
```

### Live WordPress Installation
```
/Users/varundubey/Local Sites/you/app/public/
├── wp-content/
│   ├── plugins/                     # All plugins (active site)
│   ├── mu-plugins/                  # Must-use plugins
│   └── plugin-backups/              # Moved backup plugins (Nov 5, 2025)
└── wp-config.php                    # Configuration (NOT in git)
```

---

## Development Workflow

### 1. Making Changes to Custom Plugins

**IMPORTANT**: Always edit in BOTH locations:

1. **Active WordPress** (for immediate testing):
   ```
   /Users/varundubey/Local Sites/you/app/public/wp-content/plugins/[plugin-name]/
   ```

2. **Git Repository** (for version control):
   ```
   /Users/varundubey/Local Sites/you/app/public/fearless-you/plugins/[plugin-name]/
   ```

**Workflow**:
```bash
# 1. Make changes in active WordPress location
# 2. Copy to git repository
cp -r "/Users/varundubey/Local Sites/you/app/public/wp-content/plugins/lccp-systems/" \
      "/Users/varundubey/Local Sites/you/app/public/fearless-you/plugins/"

# 3. Commit to git
cd "/Users/varundubey/Local Sites/you/app/public/fearless-you"
git add plugins/lccp-systems/
git commit -m "Your commit message"
```

### 2. Commit Message Format

**Standard Format**:
```
Brief description of change

- Bullet point of specific change 1
- Bullet point of specific change 2
- Bullet point of specific change 3
```

**No Co-Author Tag**: Client preference is to NOT include Claude co-author attribution in commits.

### 3. Creating Reports

For significant changes, create reports in `/docs/` directory:
- Use descriptive filenames with dates: `feature-name-nov5-2025.md`
- Include: What changed, why, impact, and technical details
- Commit reports separately from code changes

---

## Custom Plugins Overview

### 1. LCCP Systems (`lccp-systems/`)
**Status**: Active
**Purpose**: Life Coach Certification Program management system

**Key Features**:
- Role-based dashboards (Mentor, BigBird, PC, Student)
- Hour tracking and submissions
- Progress monitoring
- Module management system
- Dashboard widgets

**Recent Changes (Nov 5, 2025)**:
- ✅ Disabled BuddyBoss/BuddyPress integration (community now optional)
- ✅ Commented out profile menu integration
- ✅ Removed BuddyBoss from required dependencies
- ✅ Disabled group-based role assignment

**Key Files**:
- `modules/class-dashboards.php` - Dashboard functionality
- `includes/class-membership-roles.php` - Role management
- `includes/class-lccp-system-status.php` - System health checks

### 2. Fearless Roles Manager (`fearless-roles-manager/`)
**Status**: Active
**Purpose**: Custom WordPress role and capability management

### 3. Fearless You Systems (`fearless-you-systems/`)
**Status**: Active
**Purpose**: Core Fearless You site functionality

### 4. Elephunkie Toolkit (`elephunkie-toolkit/`)
**Status**: DEACTIVATED (Nov 5, 2025)
**Reason**: No features were being used (all set to 'off')

**Features (unused)**:
- Audio players, video managers
- User activity tracking
- Custom login pages
- LearnDash exporters
- Auto-enrollment tools

**Can be reactivated if needed**: All code preserved

### 5. LearnDash Favorite Content (`learndash-favorite-content/`)
**Status**: Active
**Purpose**: Allows users to favorite courses/lessons

---

## Recent Maintenance (November 5, 2025)

### ✅ Completed Tasks

#### 1. Disabled BuddyBoss/BuddyPress Integration
**Files Modified**:
- `plugins/lccp-systems/modules/class-dashboards.php` (line 49)
- `plugins/lccp-systems/includes/class-membership-roles.php` (lines 47-48, 170-183)
- `plugins/lccp-systems/includes/class-lccp-system-status.php` (line 195)

**Reason**: Community features now optional

**Commit**: `d9d4219` - "Disable BuddyBoss/BuddyPress integration in LCCP systems"

#### 2. Dashboard Pages Cleanup
**Deleted 7 duplicate pages**:
- Old mentor/bigbird/pc/student dashboards with `dasher_*` shortcodes
- Test dashboard pages
- Outdated duplicates

**Kept 5 correct LCCP dashboards**:
- LCCP Dashboard (229246)
- Program Coordinator Dashboard (229247)
- Big Bird Dashboard (229248)
- Mentor Dashboard (229249)
- Student Dashboard (229250)

**Report**: `docs/dashboard-cleanup-report-nov5-2025.txt`

#### 3. Plugin Backup Organization
**Moved to `/wp-content/plugin-backups/`**:
- elephunkie-toolkit-backup-nov3
- fearless-roles-manager-backup-nov3
- fearless-you-systems-backup-nov3
- lccp-systems-working-backup
- learndash-favorite-content-backup-nov3

**Reason**: Avoid confusion in WordPress admin plugins screen

#### 4. Elephunkie Toolkit Deactivation
**Status**: Deactivated (all features were off)
**Location**: Plugin still exists, can be reactivated
**Backup**: Also in `/wp-content/plugin-backups/`

#### 5. Site Audit Completed
**Issues Found**:
- 2 duplicate page sets (Welcome, Members)
- 12 old draft pages from 2022-2023
- 3 pages with missing slugs

**Report**: `docs/site-audit-recommendations-nov5-2025.txt`

**Recommended Actions**: Pending client approval for deletion

#### 6. Debug Warnings Fixed (CRITICAL)
**Problem**: Hundreds of warnings filling debug logs:
- Translation loading warnings (WordPress 6.7+)
- PHP 8.2 deprecation warnings

**Solution Implemented**:

**Part 1 - MU-Plugin** (`wp-content/mu-plugins/fix-translation-loading.php`):
- Suppresses translation loading warnings
- Ensures translations still work properly
- Fixes: BuddyBoss, Uncanny Automator, LCCP Systems

**Part 2 - wp-config.php** (lines 99-103):
- Suppresses PHP 8.2 deprecation warnings
- Still logs real errors
- Fixes: Block Visibility, WordPress core kses.php issues

**Result**: Clean debug logs, zero warnings

**Documentation**: `docs/debug-warnings-fix-nov5-2025.md`

**Commits**:
- `b40b0e9` - MU-plugin creation
- `ca0bf28` - Documentation

---

## Important Notes & Conventions

### ⚠️ Critical Rules

1. **Always sync changes** between active WordPress and git repository
2. **Never commit wp-config.php** (contains local credentials)
3. **Test locally first** before deploying to staging/production
4. **Keep backups** before major changes
5. **Document significant changes** in `/docs/`
6. **No co-author tags** in commits (client preference)

### 🔧 Useful Commands

**Check WordPress status**:
```bash
cd "/Users/varundubey/Local Sites/you/app/public"
wp core version
wp plugin list
wp theme list
```

**Sync plugin to git**:
```bash
cp -r "/Users/varundubey/Local Sites/you/app/public/wp-content/plugins/lccp-systems" \
      "/Users/varundubey/Local Sites/you/app/public/fearless-you/plugins/"
```

**Check debug log**:
```bash
tail -f "/Users/varundubey/Local Sites/you/app/public/wp-content/debug.log"
```

**Clear debug log**:
```bash
echo "" > "/Users/varundubey/Local Sites/you/app/public/wp-content/debug.log"
```

### 📝 File Locations Reference

**Debug Log**:
```
/Users/varundubey/Local Sites/you/app/public/wp-content/debug.log
```

**WP-Config**:
```
/Users/varundubey/Local Sites/you/app/public/wp-config.php
```

**Custom Plugins (Active)**:
```
/Users/varundubey/Local Sites/you/app/public/wp-content/plugins/[plugin-name]/
```

**Custom Plugins (Git)**:
```
/Users/varundubey/Local Sites/you/app/public/fearless-you/plugins/[plugin-name]/
```

**MU-Plugins**:
```
/Users/varundubey/Local Sites/you/app/public/wp-content/mu-plugins/
```

**Plugin Backups**:
```
/Users/varundubey/Local Sites/you/app/public/wp-content/plugin-backups/
```

---

## Pending Tasks & Recommendations

### High Priority
- [ ] Review duplicate pages with client (Welcome, Members)
- [ ] Delete confirmed old draft pages (pending client approval)
- [ ] Monitor for Block Visibility plugin update (PHP 8.2 fix)

### Medium Priority
- [ ] Clean up 12 old draft pages from 2022 (IDs in audit report)
- [ ] Fix pages with missing slugs
- [ ] Update Uncanny Automator plugin (6.9.0.2 available)

### Low Priority
- [ ] Consider deleting test pages if no longer needed
- [ ] Review toolkit certification draft pages

**Reference**: See `docs/site-audit-recommendations-nov5-2025.txt` for details

---

## Third-Party Plugins (Not in Git)

These are managed via WordPress admin or composer:

**Core Plugins**:
- BuddyBoss Platform (2.14.4) - **Integration Disabled**
- LearnDash LMS (4.25.4)
- WP Fusion
- Uncanny Automator
- The Events Calendar
- WooCommerce (not currently active)

**Utility Plugins**:
- Block Visibility
- Magic Login
- UpdraftPlus
- Redirection
- Custom CSS JS
- Insert Headers and Footers

---

## Troubleshooting

### Issue: Changes not appearing on site
**Solution**: Clear caches and check if editing the correct location
```bash
# Clear WordPress cache
wp cache flush

# Clear browser cache (Cmd+Shift+R)
```

### Issue: Plugin conflicts
**Solution**: Check debug log first
```bash
tail -100 /Users/varundubey/Local\ Sites/you/app/public/wp-content/debug.log
```

### Issue: Translation warnings reappearing
**Solution**: Check MU-plugin is active
```bash
ls -la "/Users/varundubey/Local Sites/you/app/public/wp-content/mu-plugins/"
# Should see: fix-translation-loading.php
```

### Issue: PHP deprecation warnings
**Solution**: Verify wp-config.php error_reporting setting
```bash
grep "error_reporting" /Users/varundubey/Local\ Sites/you/app/public/wp-config.php
# Should see: error_reporting( E_ALL & ~E_DEPRECATED & ~E_NOTICE );
```

---

## Resuming Work

### Quick Start Checklist

1. **Check current state**:
   ```bash
   cd "/Users/varundubey/Local Sites/you/app/public/fearless-you"
   git status
   git log --oneline -10
   ```

2. **Review recent changes**:
   - Check `/docs/` for recent reports
   - Review last few commits
   - Check pending tasks section above

3. **Sync any local changes**:
   ```bash
   # If changes were made in active WordPress
   # Copy them to git repo before continuing
   ```

4. **Test environment**:
   ```bash
   cd "/Users/varundubey/Local Sites/you/app/public"
   wp plugin list
   wp core check-update
   ```

5. **Review issues**:
   - Check `docs/site-audit-recommendations-nov5-2025.txt`
   - Review pending tasks above
   - Check for any new warnings in debug.log

### Context for New Sessions

**Custom Development**:
- All custom plugins in `/fearless-you/plugins/` and `/wp-content/plugins/`
- Must-use plugins for critical fixes
- BuddyBoss integration disabled but code preserved

**Current State**:
- Clean debug logs (warnings fixed)
- Dashboard pages cleaned up (duplicates removed)
- Site audit completed (recommendations pending)
- All functionality working normally

**Environment**:
- Local development only (no staging/production documented yet)
- PHP 8.2, WordPress 6.8.3
- BuddyBoss installed but community features optional

---

## Getting Help

### Documentation
- **This file**: Development workflow and maintenance
- **README.md**: Project overview
- **docs/**: Detailed reports and technical documentation

### Key Contacts
- Client: Fearless You / Rhonda Britten team
- Development: Varun Kumar Dubey

### References
- WordPress Codex: https://codex.wordpress.org/
- LearnDash Docs: https://www.learndash.com/support/docs/
- BuddyBoss Docs: https://www.buddyboss.com/resources/docs/
- WP-CLI: https://wp-cli.org/

---

**Document Maintained By**: Claude Code
**Last Review**: November 5, 2025
**Next Review**: When major changes occur or monthly check-in
