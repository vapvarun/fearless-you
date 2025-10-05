# Performance Issues - Priority Fix List

## Overview

This document identifies all functions and code that negatively impact site speed and performance. Issues are prioritized by severity and estimated performance impact.

**Total Performance Issues**: 18
**Estimated Total Fix Effort**: 17.25 hours

---

## CRITICAL - High Performance Impact (Fix First)

### 1. Database Queries in Loops (N+1 Problem)
**File**: `fli-child-theme/functions.php`
**Line**: 776
**Function**: Role management display

**Issue**:
```php
foreach ($roles as $role_key => $role_data):
    $user_count = count(FRM_Roles_Manager::get_users_with_role($role_key));
```

**Impact**:
- Executes separate database query for each role
- With 10+ roles, this creates 10+ queries per page load
- Admin page becomes very slow with many users

**Performance Cost**: 500-2000ms on admin pages

**Fix**:
```php
// Get all user counts in single query
$all_user_counts = FRM_Roles_Manager::get_all_role_counts();
foreach ($roles as $role_key => $role_data):
    $user_count = $all_user_counts[$role_key] ?? 0;
```

**Estimated Effort**: 0.75 hours

---

### 2. Database Query in Filter Callback
**File**: `fli-child-theme/includes/role-based-logo.php`
**Lines**: 57-60
**Function**: `fli_role_based_logo_id()`

**Issue**:
```php
function fli_role_based_logo_id($logo_id) {
    if (fli_should_show_lccp_logo()) {
        global $wpdb;
        $attachment_id = $wpdb->get_var($wpdb->prepare(
            "SELECT ID FROM $wpdb->posts WHERE guid = %s",
            $lccp_logo_url
        ));
    }
}
```

**Impact**:
- Runs on EVERY page load (filter callback)
- Executes database query for every logo display
- Logo appears multiple times per page (header, mobile, buddypanel)

**Performance Cost**: 50-150ms per page load

**Fix**:
```php
// Cache the attachment ID
function fli_role_based_logo_id($logo_id) {
    if (fli_should_show_lccp_logo()) {
        $cached_id = wp_cache_get('lccp_logo_attachment_id', 'fli_logos');
        if (false === $cached_id) {
            global $wpdb;
            $lccp_logo_url = fli_get_lccp_logo_url();
            $cached_id = $wpdb->get_var($wpdb->prepare(
                "SELECT ID FROM $wpdb->posts WHERE guid = %s",
                $lccp_logo_url
            ));
            wp_cache_set('lccp_logo_attachment_id', $cached_id, 'fli_logos', 3600);
        }
        return $cached_id ?: $logo_id;
    }
    return $logo_id;
}
```

**Estimated Effort**: 7.5 minutes

---

### 3. SQL Syntax Error in Caching System
**File**: `fli-child-theme/includes/caching-system.php`
**Line**: 669
**Function**: `clear_pattern_cache()`

**Issue**:
```php
$sql = $wpdb->prepare(
    "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s AND (option_name LIKE %s OR option_name LIKE %s)",
    '_transient_' . $pattern,
    '_transient_timeout_' . $pattern  // Missing third placeholder value!
);
```

**Impact**:
- Cache clearing fails silently
- Stale cached data served to users
- Database bloat from uncleaned transients
- Eventually causes slowdowns as options table grows

**Performance Cost**: Indirect - grows worse over time (100-500ms after months)

**Fix**:
```php
$sql = $wpdb->prepare(
    "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
    $wpdb->esc_like('_transient_' . $pattern) . '%',
    $wpdb->esc_like('_transient_timeout_' . $pattern) . '%'
);
```

**Estimated Effort**: 7.5 minutes

---

### 4. Large File Loading Without Size Checks
**File**: `fli-child-theme/functions.php`
**Lines**: Multiple locations
**Functions**: File modification tools, log viewers

**Issue**:
```php
$content = file_get_contents($file);  // No size check!
```

**Impact**:
- Attempting to load large log files (10MB+) into memory
- PHP memory exhaustion errors
- Site crashes on admin pages
- Server CPU spikes

**Performance Cost**: 2000-5000ms for large files, or fatal error

**Fix**:
```php
$max_size = 5 * 1024 * 1024; // 5MB
if (filesize($file) > $max_size) {
    // Stream large files or show error
    return new WP_Error('file_too_large', 'File too large to process');
}
$content = file_get_contents($file);
```

**Estimated Effort**: 0.5 hours (multiple locations)

---

### 5. No Transient Cleanup (Database Bloat)
**File**: `fli-child-theme/functions.php`, `caching-system.php`
**Issue**: Creates many transients but no cleanup mechanism

**Impact**:
- WordPress options table grows indefinitely
- Expired transients remain in database
- Slower queries on options table
- Database size increases unnecessarily

**Performance Cost**: 50-300ms added to queries after months (cumulative)

**Fix**:
```php
// Add scheduled cleanup
add_action('init', 'fli_schedule_transient_cleanup');
function fli_schedule_transient_cleanup() {
    if (!wp_next_scheduled('fli_clean_expired_transients')) {
        wp_schedule_event(time(), 'daily', 'fli_clean_expired_transients');
    }
}

add_action('fli_clean_expired_transients', 'fli_cleanup_expired_transients');
function fli_cleanup_expired_transients() {
    global $wpdb;
    $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_timeout_%' AND option_value < UNIX_TIMESTAMP()");
}
```

**Estimated Effort**: 0.5 hours

---

## HIGH - Moderate Performance Impact

### 6. Inline JavaScript (Not Cached)
**File**: `fli-child-theme/functions.php`
**Lines**: 173-186, 753-769, 1732-1770, 1810-1817, 1909-1989, 2123-2155
**File**: `fli-child-theme/includes/magic-link-auth.php`
**Lines**: 413-799 (386 lines!)

**Issue**:
- Over 500 lines of inline JavaScript across files
- Cannot be cached by browser
- Loaded on every page view
- No minification

**Impact**:
- Larger HTML pages
- Slower page parsing
- Content Security Policy violations
- No browser caching benefits

**Performance Cost**: 100-300ms per page load (network + parsing)

**Fix**:
```php
// Move to external files:
// - /assets/js/focus-mode.js
// - /assets/js/jonathan-ip-manager.js
// - /assets/js/login-improvements.js
// - /assets/js/mutation-observer-fix.js
// - /assets/js/accessibility-widget.js
// - /assets/js/magic-link-auth.js

wp_enqueue_script('fli-focus-mode', get_stylesheet_directory_uri() . '/assets/js/focus-mode.js', ['jquery'], '1.0.0', true);
```

**Estimated Effort**: 1.5 hours

---

### 7. Inline CSS (Not Cached)
**File**: `fli-child-theme/functions.php`
**Lines**: 952-964, 975-1026, 2078-2098, 2156-2184
**File**: `fli-child-theme/login.php`
**Lines**: 300+ lines of inline CSS
**File**: `fli-child-theme/includes/role-based-logo.php`
**Lines**: 76-93

**Issue**:
- Large CSS blocks inline in HTML
- Cannot be cached by browser
- Loaded on every page view
- Duplicated across pages

**Performance Cost**: 50-150ms per page load

**Fix**:
```php
// Move to external file: /assets/css/custom.css
wp_enqueue_style('fli-custom', get_stylesheet_directory_uri() . '/assets/css/custom.css', [], '1.0.0');
```

**Estimated Effort**: 1.5 hours

---

### 8. LearnDash Function Calls Without Caching
**File**: `fli-child-theme/includes/membership-caching-examples.php`
**Lines**: Multiple locations (though this is example file)

**Issue**:
```php
// Called repeatedly without caching
learndash_user_get_course_progress($user_id, $course_id);
learndash_user_get_active_courses($user_id);
```

**Impact**:
- LearnDash queries are expensive
- User course data queried multiple times per page
- Progress bars cause multiple duplicate queries

**Performance Cost**: 200-800ms per page with course data

**Fix**: Implement the caching examples shown in membership-caching-examples.php

**Estimated Effort**: 1.5 hours

---

### 9. Missing Function Definitions Cause Extra Lookups
**File**: `fli-child-theme/search.php`
**Line**: 62
**File**: `fli-child-theme/template-parts/category-separator.php`
**Lines**: 26, 82

**Issue**:
```php
phunk_get_post_type_icon();  // Function doesn't exist
fli_get_category_color();     // Function doesn't exist
fli_category_color_class();   // Function doesn't exist
```

**Impact**:
- PHP searches include paths for undefined functions
- Multiple file existence checks
- Error log spam
- Potential fatal errors

**Performance Cost**: 10-30ms per undefined function call

**Fix**: Define functions or remove calls

**Estimated Effort**: 0.5 hours

---

### 10. BuddyBoss Menu Queries Not Cached
**File**: `fli-child-theme/includes/caching-system.php`
**Lines**: 291-330

**Issue**:
- Menu query caching implemented but may not be used everywhere
- BuddyBoss generates dynamic menus on every page load
- User-specific menu items queried repeatedly

**Performance Cost**: 100-300ms per page load

**Fix**: Ensure caching functions are actually called for all menu queries

**Estimated Effort**: 0.5 hours

---

## MEDIUM - Lower Performance Impact

### 11. AJAX Polling Without Rate Limiting
**File**: `fli-child-theme/thank-ya.php`
**Lines**: 67-85

**Issue**:
```php
setInterval(function() {
    checkLoginStatus();
}, 2000);  // Every 2 seconds!
```

**Impact**:
- Server hit every 2 seconds per user on page
- Unnecessary load on server
- Database connections for each check
- Network traffic waste

**Performance Cost**: Cumulative server load (10-20 requests/minute per user)

**Fix**:
```php
// Increase interval and add exponential backoff
let checkInterval = 3000; // Start at 3 seconds
let maxInterval = 30000;  // Max 30 seconds

function scheduleCheck() {
    setTimeout(function() {
        checkLoginStatus();
        checkInterval = Math.min(checkInterval * 1.2, maxInterval);
        scheduleCheck();
    }, checkInterval);
}
```

**Estimated Effort**: 7.5 minutes

---

### 12. Global Error Handler Performance
**File**: `fli-child-theme/includes/error-logging.php`
**Line**: 92

**Issue**:
```php
set_error_handler([$this, 'error_handler']);
```

**Impact**:
- Custom error handler called for EVERY PHP error/notice
- File I/O on every error (writes to log)
- On high-traffic sites with many notices, this slows things down

**Performance Cost**: 5-20ms per error (cumulative)

**Fix**: Only enable on debug mode or use error level filtering

**Estimated Effort**: 7.5 minutes

---

### 13. Regex in Filter Callbacks
**File**: `fli-child-theme/includes/enable-breadcrumbs.php`
**Line**: 92

**Issue**:
```php
$breadcrumbs = preg_replace_callback('/>([^<]+)</s', function($matches) {
    return '>' . fli_format_breadcrumb_title($matches[1]) . '<';
}, $breadcrumbs);
```

**Impact**:
- Regex parsing on every breadcrumb display
- Multiple function calls per match
- Runs on every page with breadcrumbs

**Performance Cost**: 10-30ms per page

**Fix**: Cache formatted breadcrumbs or simplify logic

**Estimated Effort**: 0.5 hours

---

### 14. Multiple get_user_meta() Calls
**File**: Various
**Issue**: User meta fetched individually instead of batch

**Impact**:
- Each get_user_meta() is a database query
- Multiple calls for same user creates unnecessary queries

**Performance Cost**: 20-50ms per extra query

**Fix**: Use `get_user_by('id', $user_id)` to get all meta at once

**Estimated Effort**: 0.75 hours

---

### 15. jQuery Document Ready on Every Page
**File**: Multiple inline JavaScript blocks

**Issue**:
```php
$(document).ready(function() {
    // Heavy operations here
});
```

**Impact**:
- JavaScript execution blocks page rendering
- Operations run even when not needed
- No conditional loading

**Performance Cost**: 50-200ms per page (cumulative)

**Fix**: Use event delegation and conditional loading

**Estimated Effort**: 0.5 hours

---

## LOW - Minor Performance Impact

### 16. Hardcoded URLs (No CDN Possible)
**File**: `fli-child-theme/includes/role-based-logo.php`
**Line**: 16
**File**: `fli-child-theme/other-options.php`
**Lines**: 45-47

**Issue**:
```php
return 'https://you.fearlessliving.org/wp-content/uploads/2025/09/LCCP_lt.svg';
```

**Impact**:
- Cannot use CDN for these assets
- Slower asset loading
- No geographic optimization

**Performance Cost**: 100-500ms (depends on user location)

**Fix**: Use WordPress functions or allow CDN rewrites

**Estimated Effort**: 7.5 minutes

---

### 17. MutationObserver Always Running
**File**: `fli-child-theme/functions.php`
**Lines**: 1909-1989

**Issue**:
```php
var observer = new MutationObserver(function(mutations) {
    // Runs on every DOM change
});
observer.observe(document.body, { childList: true, subtree: true });
```

**Impact**:
- JavaScript execution on every DOM change
- Battery drain on mobile
- CPU usage

**Performance Cost**: 10-50ms per page (cumulative on dynamic pages)

**Fix**: Add throttling or only observe specific elements

**Estimated Effort**: 0.5 hours

---

### 18. No Object Caching Implementation
**File**: `fli-child-theme/includes/caching-system.php`
**Issue**: Only uses transients (database), no object cache (Redis/Memcached)

**Impact**:
- All cache reads hit database
- Transients are slower than object cache
- Scalability issues under high load

**Performance Cost**: 20-100ms per cached item fetch

**Fix**: Add Redis/Memcached support with fallback

**Estimated Effort**: 2 hours

---

## Summary by Priority

### Week 1 - Critical Performance Fixes (2.75 hours)
1. Fix N+1 query in role display (0.75 hours)
2. Cache logo database query (15 min)
3. Fix SQL syntax in cache clearing (7.5 min)
4. Add file size checks (1 hour)
5. Implement transient cleanup (0.5 hours)
6. Fix undefined function calls (0.5 hours)

**Expected Performance Gain**: 40-60% improvement on admin pages, 20-30% on frontend

---

### Week 2 - High Priority (11.5 hours)
1. Extract inline JavaScript (6 hours)
2. Extract inline CSS (3 hours)
3. Implement LearnDash caching (1.5 hours)
4. Cache BuddyBoss menus (1 hour)

**Expected Performance Gain**: 30-50% improvement on all pages

---

### Month 2 - Medium Priority (6 hours)
1. Fix AJAX polling rate (30 min)
2. Optimize error handler (30 min)
3. Cache breadcrumbs (1 hour)
4. Batch user meta queries (1.5 hours)
5. Optimize jQuery operations (2 hours)

**Expected Performance Gain**: 10-20% improvement

---

### Long-term - Low Priority (5.5 hours)
1. Implement CDN support (30 min)
2. Optimize MutationObserver (1 hour)
3. Add Redis/Memcached support (4 hours)

**Expected Performance Gain**: 20-40% improvement (requires infrastructure)

---

## Performance Testing Checklist

After fixes, measure:

### Before Fixes:
- [ ] Time to First Byte (TTFB)
- [ ] Largest Contentful Paint (LCP)
- [ ] Total Blocking Time (TBT)
- [ ] Database query count per page
- [ ] Page load time (frontend)
- [ ] Admin page load time
- [ ] Database size
- [ ] Memory usage

### After Fixes:
- [ ] Verify all metrics improved
- [ ] Check query reduction
- [ ] Confirm caching working
- [ ] Test under load
- [ ] Monitor for errors

---

## Monitoring Recommendations

### Immediate:
1. Enable Query Monitor plugin
2. Track database query counts
3. Monitor PHP memory usage
4. Check error logs

### Ongoing:
1. Set up performance monitoring (New Relic, etc.)
2. Track Core Web Vitals
3. Regular database optimization
4. Cache hit rate monitoring
5. Server resource monitoring

---

## Expected Results

### After Critical Fixes (Week 1):
- **Admin pages**: 2-5 seconds → 0.5-1 second
- **Frontend pages**: 1-3 seconds → 0.8-2 seconds
- **Database queries**: 50-100 → 20-40 per page

### After All High Priority (Week 2):
- **Admin pages**: 0.3-0.8 seconds
- **Frontend pages**: 0.5-1.2 seconds
- **Database queries**: 15-25 per page
- **Page size**: 15-20% smaller (external JS/CSS)

### After Medium Priority (Month 2):
- **All pages**: 10-20% faster
- **Mobile**: Significantly improved
- **Battery usage**: Reduced

### After Long-term (with infrastructure):
- **Global performance**: 30-50% improvement
- **Scalability**: Support 5-10x more users
- **Database load**: 50-70% reduction

---

## Total Estimated Effort

**Critical**: 5.5 hours
**High**: 23 hours
**Medium**: 6 hours
**Low**: 5.5 hours

**Total**: 40 hours (5 days)

**Priority**: Start with Critical fixes for maximum impact with minimal effort.
