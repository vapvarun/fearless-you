# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a WordPress membership site for the Fearless Living Institute (FLI), built on BuddyBoss Platform with LearnDash LMS integration. The site manages three main user types: Members, Faculty, and Ambassadors, with a focus on the Life Coach Certification Program (LCCP).

**Site Name:** Fearless Living Learning Center
**Environment:** Local development (Local by Flywheel)
**Database:** `local` (user: `root`, password: `root`, host: `localhost`)

## Architecture

### Core Stack
- **WordPress Core:** Standard WordPress installation
- **Parent Theme:** BuddyBoss Theme (`wp-content/themes/buddyboss-theme/`)
- **Child Theme:** FLI Child Theme (`wp-content/themes/fli-child-theme/`)
- **Platform:** BuddyBoss Platform (community/social features)
- **LMS:** LearnDash (`wp-content/plugins/sfwd-lms/`)
- **Forms:** Gravity Forms (`wp-content/plugins/gravityforms/`)

### Custom Plugins (Development Focus)

#### 1. **LCCP Systems** (`wp-content/plugins/lccp-systems/`)
Manages the Life Coach Certification Program with modular architecture.

**Key Modules** (located in `modules/`):
- `class-hour-tracker-module.php` - Tracks coaching hours with tier-based progress (Bronze/Silver/Gold/Platinum)
- `class-dashboards-module.php` - Custom dashboards for LCCP participants
- `class-checklist-module.php` - Certification requirement checklists
- `class-learndash-integration-module.php` - LearnDash course integration
- `class-events-integration.php` - The Events Calendar integration
- `class-performance-module.php` - Performance optimization features
- `class-accessibility-module.php` - WCAG accessibility features
- `class-autologin-module.php` - Magic link authentication

**Module System:**
- Each module extends `LCCP_Module` base class
- Modules can be toggled on/off via admin interface
- Module manager: `includes/class-module-manager.php`
- Settings stored in WordPress options table

#### 2. **Fearless You Systems** (`wp-content/plugins/fearless-you-systems/`)
Membership management for Members, Faculty, and Ambassadors.

**Key Classes** (in `includes/`):
- `class-role-manager.php` - Custom role management
- `class-member-dashboard.php` - Member-specific dashboard
- `class-faculty-dashboard.php` - Faculty dashboard with analytics
- `class-ambassador-dashboard.php` - Ambassador dashboard
- `class-analytics.php` - User activity tracking

#### 3. **Elephunkie Toolkit** (`wp-content/plugins/elephunkie-toolkit/`)
Developer utilities and helper tools with toggle-based feature system.

**Modules** (in `includes/`):
- `phunk-audio/` - Audio management
- `elephunkie-log-mailer/` - Error log email notifications
- `cleanup-utility/` - Database cleanup tools
- `phunkie-custom-login/` - Login customization
- `learndash-courses-to-csv/` - Course data export
- `simple-user-activity/` - Activity logging
- `fearless-security-fixer/` - Security patches

#### 4. **Fearless Roles Manager** (`wp-content/plugins/fearless-roles-manager/`)
Advanced role and capability management for custom membership tiers.

#### 5. **Fivo Docs** (`wp-content/plugins/fivo-docs/`)
Document management and gallery system for course materials.
- Uses autoloading: `includes/autoload.php`
- Namespace: `Fivo_Docs\`
- Shortcode-based interface

### Child Theme Structure (`wp-content/themes/fli-child-theme/`)

**Key Files:**
- `functions.php` - Main theme functions (optimized, reduced from 79KB to ~25KB)
- `style.css` - Theme metadata and documentation

**Assets** (`assets/`):
- `css/custom.css` - Main custom styles
- `css/login-page.css` - Login page customization
- `css/learndash-custom.css` - LearnDash styling
- `css/accessibility-widget.css` - Accessibility features
- `js/custom.js` - Core JavaScript
- `js/learndash-video-tracking.js` - Video completion tracking
- `js/learndash-progress-rings.js` - Visual progress indicators
- `js/login-enhancements.js` - Login page functionality

**Conditional Loading:**
The theme uses performance optimization with conditional asset loading:
- LearnDash assets only load on LearnDash pages (`sfwd-lessons`, `sfwd-topic`, `sfwd-courses`, `sfwd-quiz`)
- Video tracking only on lesson/topic pages
- Login assets only on login pages

### Integration Points

**LearnDash Integration:**
- Course post types: `sfwd-courses`, `sfwd-lessons`, `sfwd-topic`, `sfwd-quiz`
- Progress tracking via LCCP Systems
- Custom video tracking hooks
- Hour logging connected to course completion

**BuddyBoss Integration:**
- Community profiles
- Activity streams
- Groups and messaging
- Custom member dashboards

**Gravity Forms Integration:**
- User registration workflows
- Hour submission forms
- Faculty/Ambassador applications

## Development Commands

### WordPress CLI (WP-CLI)
WP-CLI is available for command-line operations:

```bash
# Check site status
wp core version
wp plugin list
wp theme list

# Database operations
wp db query "SELECT * FROM wp_users LIMIT 5"
wp db export

# User management
wp user list
wp user create <username> <email> --role=subscriber

# Cache clearing
wp cache flush

# Plugin operations
wp plugin activate <plugin-name>
wp plugin deactivate <plugin-name>

# Run single test (if PHPUnit configured)
wp eval-file path/to/test.php
```

### Linting (Child Theme)

The child theme includes ESLint and PHPCS configuration:

```bash
# Navigate to child theme
cd wp-content/themes/fli-child-theme

# Install dependencies
npm install

# Run JavaScript linting
npm run lint:js
npm run lint:js:fix

# Run PHP linting
npm run lint:php
npm run lint:php:fix

# Run all linting
npm run lint
```

**Linting Standards:**
- JavaScript: WordPress ESLint plugin (`@wordpress/eslint-plugin`)
- PHP: PHPCS with `phpcs.xml` configuration

### Debugging

**WordPress Debug Mode:**
Set in `wp-config.php` (currently disabled in production):
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

**Debug Log Location:**
`wp-content/debug.log`

**Custom Error Logging:**
The theme uses `error_log()` throughout. Key logged events:
- User authentication flows
- WP Fusion integration operations
- AJAX handler responses
- Contact creation/updates

**Elephunkie Log Mailer:**
Automatically emails error logs to administrators when enabled.

### Database Access

**Local Environment:**
- Database: `local`
- Username: `root`
- Password: `root`
- Host: `localhost`
- Table Prefix: `wp_`

Access via MySQL CLI:
```bash
mysql -u root -proot local
```

## Code Patterns and Conventions

### Plugin Architecture Pattern

Custom plugins follow singleton pattern with modular architecture:

```php
class Plugin_Name {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
    }
}

Plugin_Name::get_instance();
```

### Module System (LCCP Systems)

Modules extend base class and must implement:
- `protected $module_id` - Unique identifier
- `protected $module_name` - Display name
- `protected $module_description` - Short description
- `protected $module_dependencies` - Array of required modules
- `protected function init()` - Initialization logic

Check if module is enabled before initialization:
```php
if (!$this->is_enabled()) {
    return;
}
```

### Asset Enqueuing

Use conditional loading to improve performance:

```php
// Only enqueue on specific post types
if (is_singular(array('sfwd-lessons', 'sfwd-topic'))) {
    wp_enqueue_script(
        'script-handle',
        get_stylesheet_directory_uri() . '/assets/js/script.js',
        array('jquery'),
        filemtime(get_stylesheet_directory() . '/assets/js/script.js'),
        true
    );
}
```

Always use `filemtime()` for cache-busting in development.

### Security Practices

1. **Always escape output:**
   - `esc_html()` for HTML content
   - `esc_attr()` for attributes
   - `esc_url()` for URLs

2. **Validate input:**
   - `sanitize_text_field()` for text
   - `wp_verify_nonce()` for AJAX requests

3. **Check capabilities:**
   ```php
   if (!current_user_can('manage_options')) {
       return;
   }
   ```

4. **Direct access protection:**
   ```php
   if (!defined('ABSPATH')) {
       exit;
   }
   ```

### Custom Post Types and Taxonomies

**LearnDash Post Types:**
- Courses: `sfwd-courses`
- Lessons: `sfwd-lessons`
- Topics: `sfwd-topic`
- Quizzes: `sfwd-quiz`

**BuddyBoss Integration:**
Check for BuddyBoss classes before using features:
```php
if (class_exists('BuddyBoss_Platform')) {
    // BuddyBoss-specific code
}
```

### AJAX Handlers

Standard AJAX pattern used throughout:

```php
// Register handler
add_action('wp_ajax_action_name', array($this, 'handle_ajax'));
add_action('wp_ajax_nopriv_action_name', array($this, 'handle_ajax')); // For non-logged-in

// Handler function
public function handle_ajax() {
    check_ajax_referer('nonce_action', 'nonce');

    // Process request
    $result = array('success' => true, 'data' => $data);

    wp_send_json($result);
}
```

### File Organization

**Plugin Structure:**
```
plugin-name/
├── plugin-name.php          # Main plugin file
├── includes/                # Core classes
├── admin/                   # Admin-specific code
├── templates/               # Template files
├── assets/                  # CSS, JS, images
├── languages/               # Translation files
└── modules/                 # Feature modules (if modular)
```

**Theme Structure:**
```
fli-child-theme/
├── functions.php            # Theme functions
├── style.css                # Theme metadata
├── assets/
│   ├── css/                # Stylesheets
│   └── js/                 # JavaScript files
├── templates/              # Template overrides
└── languages/              # Translation files
```

## Important Notes

### Performance Optimization

The child theme was optimized from 79KB to ~25KB by:
1. Moving inline CSS to external files
2. Implementing conditional loading
3. Removing redundant code
4. Using file-based assets instead of inline styles/scripts

When adding new features, follow this pattern to maintain performance.

### Role-Based Features

Three custom membership levels require different feature access:
1. **Members** - Basic course access
2. **Faculty** - Teaching tools, analytics dashboard
3. **Ambassadors** - Marketing tools, referral tracking

Always check user roles before displaying role-specific features:
```php
$user = wp_get_current_user();
if (in_array('faculty', $user->roles)) {
    // Faculty-specific code
}
```

### Hour Tracking System

The LCCP hour tracker uses tier-based progression:
- Bronze: 0-99 hours
- Silver: 100-199 hours
- Gold: 200-299 hours
- Platinum: 300+ hours

Hours are stored per user and tracked through:
- Manual submission forms
- Automated course completion tracking
- LearnDash integration

### Error Logging Strategy

Use consistent error logging patterns:
```php
error_log(sprintf(
    'Context: Action - Details: %s',
    print_r($data, true)
));
```

Key areas with heavy logging:
- User authentication flows (`functions.php`)
- WP Fusion integration
- AJAX handlers
- Module initialization

### Video Tracking

LearnDash video tracking requires:
1. LearnDash video script loaded (`learndash_video_script`)
2. Lesson/topic context (`is_singular(['sfwd-lessons', 'sfwd-topic'])`)
3. Video completion webhook configuration

Video tracking script: `assets/js/learndash-video-tracking.js`

## Deployment Notes

**Environment Detection:**
Current environment set in `wp-config.php`:
```php
define('WP_ENVIRONMENT_TYPE', 'local');
```

**Pre-Deployment Checklist:**
1. Disable `WP_DEBUG` in `wp-config.php`
2. Run linting: `npm run lint`
3. Test all custom modules
4. Clear all caches
5. Verify role permissions
6. Test LCCP hour tracking
7. Verify LearnDash integration
8. Check Gravity Forms submissions

**Local Development:**
This is a Local by Flywheel site. Site files are in `app/public/` directory.

## Third-Party Integration

### WP Fusion
CRM integration for contact management. Check for WP Fusion before using:
```php
if (function_exists('wp_fusion')) {
    // WP Fusion code
}
```

### The Events Calendar
Event management integration in LCCP Systems. Post type: `tribe_events`

### Amazon S3 and CloudFront
Media storage plugin for offloading assets (Pro version installed).

## Reference Links

- BuddyBoss Docs: https://www.buddyboss.com/resources/docs/
- LearnDash Docs: https://www.learndash.com/support/docs/
- Gravity Forms Docs: https://docs.gravityforms.com/
- WordPress Codex: https://codex.wordpress.org/
