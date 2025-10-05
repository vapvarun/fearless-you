# Component Documentation - Fearless You Custom Code

## Overview
This document provides an overview of each custom component, its purpose, and key functionality.

---

## 1. Elephunkie Toolkit Plugin

**Purpose**: Modular plugin system that combines multiple smaller tools into a single plugin with toggle controls

**Location**: `wp-content/plugins/fearless-you/plugins/elephunkie-toolkit/`

**Version**: 3.2

**Author**: Jonathan Albiar (Elephunkie, LLC)

### Key Features:
- Admin interface to enable/disable sub-modules
- Auto-discovery of modules in includes/ directory
- Module health checking before activation
- Custom admin styling with toggle switches

### Sub-Modules Included:
1. **cleanup-utility** - Site cleanup tools
2. **elephunkie-log-mailer** - Log file email system
3. **fearless-security-fixer** - Security issue detector and fixer
4. **inactive-plugin-manager** - Manages inactive plugins
5. **lc-ex** - LearnDash extensions
6. **learndash-courses-to-csv** - Export courses to CSV
7. **learndash-video-manager** - Video progress tracking
8. **phunk-audio** - Audio player functionality
9. **phunk-auto-enroll** - Automatic course enrollment
10. **phunk-fixes** - Various bug fixes
11. **phunk-plugin-logger** - Plugin resource usage logger
12. **phunkie-audio-player** - Audio player component
13. **phunkie-custom-login** - Custom login functionality
14. **simple-user-activity** - User activity tracking

### Database Tables:
None (uses wp_options for settings)

### Settings Storage:
- Each module has an option: `elephunkie_{module_name}` (on/off)
- Main settings: `elephunkie_toolkit`

### Dependencies:
- Bootstrap Toggle library (for UI switches)
- WordPress 5.0+
- jQuery

### Issues to Fix: 4 critical, 1 high, 1 medium

---

## 2. Fearless Roles Manager Plugin

**Purpose**: Advanced WordPress role management with WP Fusion integration, category assignment, and visual organization

**Location**: `wp-content/plugins/fearless-you/plugins/fearless-roles-manager/`

**Version**: 1.0.0

**Author**: Fearless Living

### Key Features:
- Role categorization system
- WP Fusion tag integration
- Automatic role assignment based on tags
- Role visibility controls
- User management by role
- Dashboard redirect configuration
- Bulk role operations

### Core Classes:
1. **FearlessRolesManager** - Main plugin class
2. **FRM_Admin_Page** - Admin UI renderer
3. **FRM_Roles_Manager** - Role operations handler
4. **FRM_Dashboard_Redirect** - Login redirect manager

### Database Tables:
None (uses wp_options for settings)

### Settings Storage:
- `frm_role_settings` - General role settings
- `frm_role_categories` - Custom role categories
- `frm_role_category_assignments` - Role to category mappings
- `frm_role_visibility_settings` - Role visibility settings
- `frm_role_wpfusion_tags` - WP Fusion tag associations

### AJAX Endpoints:
- `frm_save_role_settings` - Save role configuration
- `frm_get_role_capabilities` - Retrieve role capabilities
- `frm_save_role_visibility` - Update role visibility
- `frm_get_users_by_role` - Get users with specific role
- `frm_get_wp_fusion_tags` - Fetch WP Fusion tags
- `frm_save_role_tags` - Save tag associations
- `frm_process_single_role_tags` - Process users for single role

### Dependencies:
- WordPress 5.0+
- WP Fusion (optional, for tag integration)
- Select2/Select4 library

### Integration Points:
- WP Fusion CRM sync
- WordPress user roles system
- Login redirect system

### Issues to Fix: 0 critical, 0 high, 3 medium, 2 low

---

## 3. Fearless You Systems Plugin

**Purpose**: Membership management system for Fearless You Members, Faculty, and Ambassadors

**Location**: `wp-content/plugins/fearless-you/plugins/fearless-you-systems/`

**Version**: 1.0.0

**Author**: Fearless Living Institute

### Key Features:
- Custom role management (Members, Faculty, Ambassadors)
- Dashboard systems for each role
- Analytics and reporting
- Role-specific functionality

### Core Classes:
1. **Fearless_You_Systems** - Main plugin class (Singleton)
2. **FYS_Role_Manager** - Role creation and management
3. **FYM_Settings** - Plugin settings interface
4. **FYS_Member_Dashboard** - Member dashboard features
5. **FYS_Faculty_Dashboard** - Faculty dashboard features
6. **FYS_Ambassador_Dashboard** - Ambassador dashboard features
7. **FYS_Analytics** - Analytics and reporting

### Database Tables:
None (uses WordPress roles and user meta)

### Custom Roles Created:
- Fearless You Member
- Fearless You Faculty
- Fearless You Ambassador

### Dependencies:
- WordPress 5.0+
- LearnDash (likely)

### Issues to Fix: 0 critical, 0 high, 0 medium (needs full review of included classes)

---

## 4. LearnDash Favorite Content Plugin

**Purpose**: Allow students to bookmark and favorite LearnDash content for quick access

**Location**: `wp-content/plugins/fearless-you/plugins/learndash-favorite-content/`

**Version**: 1.0.3

**Author**: SnapOrbital

**Type**: Third-party plugin (modified/included)

### Key Features:
- Favorite/bookmark courses, lessons, topics
- Quick access to favorited content
- User-specific favorites list

### Architecture:
- Model-View-Controller pattern
- Separate admin interface
- Asset management system

### Files:
- `init.php` - Main initialization
- `lib/model.php` - Data model
- `lib/view.php` - Display logic
- `lib/controller.php` - Business logic
- `lib/admin.php` - Admin interface
- `lib/assets.php` - Asset enqueuing

### Dependencies:
- LearnDash LMS
- EDD Software Licensing (for updates)

### Update System:
Uses SnapOrbital licensing server for updates

### Issues to Fix: 0 critical, 0 high (third-party plugin)

---

## 5. Lock Visibility Plugin (Block Visibility)

**Purpose**: Control visibility of Gutenberg blocks based on various conditions

**Location**: `wp-content/plugins/fearless-you/plugins/lock-visibility/`

**Type**: Appears to be third-party plugin (Block Visibility)

### Key Features:
- Hide/show blocks based on user role
- Date/time-based visibility
- Device/browser-based visibility
- Cookie-based visibility
- Query string conditions
- Referral source detection
- Screen size responsive visibility

### Settings Structure:
Comprehensive visibility controls for:
- User roles
- Logged in/out status
- Date and time
- Day of week
- Screen size
- Browser/device
- ACF fields
- WooCommerce conditions
- WP Fusion tags

### Integration Points:
- Advanced Custom Fields (ACF)
- WooCommerce
- WP Fusion
- Easy Digital Downloads

### Issues to Fix: 0 (appears to be third-party)

---

## 6. FLI Child Theme (BuddyBoss Child Theme)

**Purpose**: Child theme for BuddyBoss platform with extensive custom functionality

**Location**: `wp-content/plugins/fearless-you/themes/fli-child-theme/`

**Parent Theme**: BuddyBoss Theme

### Key Custom Features:

#### Authentication & Login:
- Magic link authentication system
- IP-based auto-login for specific users
- Custom login page styling
- Email/username login support
- Custom login form modifications

#### User Management:
- Image upload system with gallery
- User activity tracking
- Registration date display in admin
- Role-based logo display
- Dashboard personalization

#### LearnDash Integration:
- Custom progress rings
- Video tracking
- Course search in menus
- Custom lesson list styling
- Final quiz renaming

#### Performance & Caching:
- Custom caching system
- Error logging system
- IP address detection with caching
- Membership data caching

#### Accessibility:
- Accessibility widget with:
  - High contrast mode
  - Large text mode
  - Readable font mode
- Persistent preferences via localStorage

#### Admin Tools:
- Remove .map references tool
- Jonathan's IP management page
- Category color system
- Dynamic styles injection

#### Other Features:
- BuddyPanel mobile flip for LearnDash
- Custom admin bar management
- Contact form shortcode
- Password reset functionality
- Data export requests
- Account deletion requests
- Floating contact button
- Terms of use integration

### Key Files Structure:
```
fli-child-theme/
├── functions.php (2208 lines - MAIN FILE)
├── login.php
├── style.css
├── assets/
│   ├── css/
│   │   └── custom.css
│   └── js/
│       ├── custom.js
│       ├── learndash-progress-rings.js
│       ├── learndash-video-tracking.js
│       ├── image-upload.js
│       └── error-prevention.js
├── includes/
│   ├── error-logging.php
│   ├── caching-system.php
│   ├── magic-link-auth.php
│   ├── other-options-handler.php
│   ├── role-based-logo.php
│   └── enable-breadcrumbs.php
└── inc/
    ├── admin/
    │   ├── admin-init.php
    │   ├── theme-functions.php
    │   ├── dynamic-styles.php
    │   ├── category-colors.php
    │   └── options-init.php
    └── learndash-customizer.php
```

### Database Usage:
Custom options stored in wp_options:
- User preferences for accessibility
- IP auto-login settings
- Category colors
- Various theme settings

### AJAX Endpoints:
- `fli_upload_image` - Image upload handler
- `check_user_status` - User authentication status
- `handle_other_options` - Password reset, data export, account deletion

### Dependencies:
- BuddyBoss Platform
- BuddyBoss Theme (parent)
- LearnDash LMS
- WP Fusion
- jQuery

### Known Integrations:
- LearnDash (extensive)
- WP Fusion
- BuddyBoss Platform
- WooCommerce (minimal)

### Issues to Fix: 3 critical, 5 high, 6 medium, 3 low

---

## Component Dependency Map

```
FLI Child Theme
    ├── Requires: BuddyBoss Theme (parent)
    ├── Integrates: LearnDash LMS
    ├── Integrates: WP Fusion
    ├── Uses: Magic Link Auth System
    └── Uses: Caching System

Fearless Roles Manager
    ├── Integrates: WP Fusion
    └── Extends: WordPress Roles

Fearless You Systems
    ├── Creates Custom Roles
    └── Likely Integrates: LearnDash

Elephunkie Toolkit
    ├── Contains: 14 sub-modules
    └── Module: Fearless Security Fixer
        └── Monitors: Security Issues

LearnDash Favorite Content
    └── Requires: LearnDash LMS

Lock Visibility
    ├── Integrates: ACF
    ├── Integrates: WooCommerce
    └── Integrates: WP Fusion
```

---

## Recommended Documentation Additions

Each component should have:

1. **README.md** with:
   - Installation instructions
   - Configuration guide
   - Feature list
   - Troubleshooting section

2. **CHANGELOG.md** with:
   - Version history
   - Changes made
   - Upgrade notes

3. **API Documentation** for:
   - Public functions
   - Hooks and filters
   - Action hooks
   - AJAX endpoints

4. **Developer Guide** covering:
   - Architecture overview
   - Code organization
   - Extending functionality
   - Best practices

**Estimated Effort for Full Documentation**: 40 hours
