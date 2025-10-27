# Fearless You - Custom Code Repository

**Last Updated:** October 27, 2025
**Purpose:** Track all custom plugins and theme changes in one organized place

---

## What This Is

This folder contains ALL custom code from your WordPress site:
- 4 custom plugins
- 1 custom child theme

Each component has its own STATUS.md file showing:
- ✅ What we have
- 📋 What needs to be done
- ✔️ What's been completed

---

## Custom Plugins

### 1. LCCP Systems
**Location:** `plugins/lccp-systems/`
**Status:** [View STATUS.md](plugins/lccp-systems/STATUS.md)

The certification program management system. This is the most critical plugin - handles all LCCP certifications, student tracking, and dashboards.

**Size:** 1.8 MB | **Priority:** CRITICAL

---

### 2. Fearless Roles Manager
**Location:** `plugins/fearless-roles-manager/`
**Status:** [View STATUS.md](plugins/fearless-roles-manager/STATUS.md)

Manages user roles and approvals. Works with WP Fusion for CRM integration.

**Size:** 192 KB | **Priority:** HIGH

---

### 3. Fearless You Systems
**Location:** `plugins/fearless-you-systems/`
**Status:** [View STATUS.md](plugins/fearless-you-systems/STATUS.md)

Member directory and management features. Has some overlap with Roles Manager.

**Size:** 140 KB | **Priority:** MEDIUM

---

### 4. Elephunkie Toolkit
**Location:** `plugins/elephunkie-toolkit/`
**Status:** [View STATUS.md](plugins/elephunkie-toolkit/STATUS.md)

Developer tools - ALL features are disabled. Safe to delete.

**Size:** 508 KB | **Priority:** LOW | **Recommendation:** DELETE

---

## Custom Theme

### FLI Child Theme
**Location:** `themes/fli-child-theme/`
**Status:** [View STATUS.md](themes/fli-child-theme/STATUS.md)

BuddyBoss child theme with custom styling and functionality.

**Size:** 2.4 MB | **Priority:** MEDIUM

---

## Quick Wins (Easy Improvements)

### 1. Delete Elephunkie Toolkit (30 minutes)
- All 24 features are disabled
- Taking up space and slowing site
- Zero functionality will be lost
- **Result:** 5-10% speed improvement

### 2. Remove Stub Files (5 minutes)
- Empty class files in LCCP Systems
- Just placeholder code, not used
- **Result:** Cleaner codebase

### 3. Consolidate Role Management (4-6 hours)
- Two plugins doing the same thing
- Merge into one to avoid conflicts
- **Result:** Easier maintenance, less confusion

---

## Current Site Issues

### Performance
- Site is slow
- Database has too many options (1,819 vs normal 100-200)
- Elephunkie Toolkit loading unused code

### Organization
- Role management split across 2 plugins
- Dashboard functionality in 2 places
- Need to decide single source of truth

### Security
- LCCP Systems needs security audit (handles sensitive data)
- Large codebase needs review

---

## How to Use This Repository

### For Developers
1. Check the STATUS.md file for each component
2. Make changes in this folder
3. Test thoroughly
4. Copy back to WordPress install
5. Update STATUS.md with what's done

### For Non-Technical Users
- Each STATUS.md file explains in plain language what the plugin does
- "What To Do" sections show what needs fixing
- "What's Done" shows completed work
- No need to understand code - just check status files

---

## Project Structure

```
fearless-you/
├── README.md (this file)
├── plugins/
│   ├── lccp-systems/
│   │   └── STATUS.md
│   ├── fearless-roles-manager/
│   │   └── STATUS.md
│   ├── fearless-you-systems/
│   │   └── STATUS.md
│   └── elephunkie-toolkit/
│       └── STATUS.md
└── themes/
    └── fli-child-theme/
        └── STATUS.md
```

---

## Common Commands

### Check Plugin Status on Site
```bash
wp plugin list --allow-root
```

### Check Database Options
```bash
wp option list --autoload=on --allow-root | wc -l
```

### Deactivate Plugin
```bash
wp plugin deactivate plugin-name --allow-root
```

### Delete Plugin
```bash
wp plugin delete plugin-name --allow-root
```

---

## Next Steps

1. Review each STATUS.md file
2. Decide priorities based on business needs
3. Start with quick wins (Elephunkie deletion)
4. Address LCCP Systems security review
5. Consolidate role management plugins

---

## Notes

- All changes should be tested in staging first
- Keep this repository updated as changes are made
- Each STATUS.md file is the source of truth for that component
- Backup before making any changes to live site

---

**Questions?** Check the STATUS.md file for the specific component you're working on.
