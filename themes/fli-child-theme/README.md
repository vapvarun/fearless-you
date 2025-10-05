# Fearless Living Learning Center v2.0.0

A powerful WordPress child theme for BuddyBoss with enhanced error logging, role-based caching, and security improvements designed specifically for membership sites.

## 🚀 Features

### Error Logging & Debugging System
- **Comprehensive Error Tracking**: PHP errors, fatal errors, database errors
- **User Activity Monitoring**: Login/logout, theme switches, plugin events
- **Admin Interface**: Full log management in WordPress admin
- **Security Logging**: IP tracking, user agent logging, memory usage monitoring
- **Debug Mode Support**: Conditional logging based on WP_DEBUG setting

### Role-Based Caching System
- **Membership Site Optimized**: Different cache for each user role/membership level
- **Plugin Support**: LearnDash, MemberPress, WooCommerce Memberships
- **Performance Monitoring**: Cache statistics and hit ratio tracking
- **Smart Invalidation**: Automatic cache clearing on role/membership changes
- **Admin Management**: Cache control interface in WordPress admin

### Security Enhancements
- **Enhanced Magic Link Authentication**: Cryptographically secure tokens
- **Improved File Upload Security**: MIME type validation, image verification
- **Better IP Validation**: Comprehensive IP address checking
- **CSRF Protection**: Enhanced nonce verification

## 📋 Requirements

- WordPress 5.0+
- PHP 7.4+
- BuddyBoss Theme (parent theme)
- WP_DEBUG enabled for full logging capabilities

## 🛠️ Installation

1. **Upload the theme** to `/wp-content/themes/fli-child-theme/`
2. **Activate the theme** in WordPress admin
3. **Enable WP_DEBUG** in `wp-config.php` for full logging:
   ```php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   ```
4. **Clear existing cache** if using caching plugins
5. **Test functionality** and monitor logs

## 🎛️ Admin Interface

### Error Logging
- **Location**: `Tools → FLI Debug Log`
- **Features**: View, clear, download logs
- **Monitoring**: Real-time error tracking

### Cache Management
- **Location**: `Tools → FLI Cache`
- **Features**: Cache statistics, group clearing, performance monitoring
- **Statistics**: Hit ratio, cache hits/misses, performance metrics

## 🔧 Developer Usage

### Error Logging Functions
```php
// Log different types of messages
fli_log_error('Something went wrong', ['context' => 'data'], 'Source');
fli_log_warning('Warning message', ['context' => 'data'], 'Source');
fli_log_debug('Debug information', ['context' => 'data'], 'Source');
fli_log_info('Information message', ['context' => 'data'], 'Source');
```

### Caching Functions
```php
// Basic caching
$data = fli_cache_get('my_key', 'group');
fli_cache_set('my_key', $data, 3600, 'group');

// Role-based caching
$user_data = fli_cache_get_for_user('user_data', 'user');
fli_cache_set_for_user('user_data', $data, 1800, 'user');

// Remember pattern (get or generate)
$result = fli_cache_remember_for_user('expensive_data', function() {
    return expensive_operation();
}, 3600, 'data');

// Clear cache
fli_cache_clear('group');
fli_cache_clear_for_role('premium_member', 'content');
```

### Membership Site Examples
```php
// Cache course progress
$progress = fli_cache_course_progress($user_id, $course_id);

// Cache user permissions
$permissions = fli_cache_user_permissions($user_id);

// Cache content visibility
$visibility = fli_cache_content_visibility($post_id, $user_id);

// Cache dashboard data
$dashboard = fli_cache_user_dashboard_data($user_id);
```

## 📊 Cache Groups

- `theme` - Theme-related cache
- `plugin` - Plugin-related cache
- `user` - User-specific data
- `post` - Post-related cache
- `ip_lookup` - IP address lookups
- `file_check` - File existence checks
- `menu` - Menu items
- `permissions` - User permissions
- `content` - Content visibility
- `dashboard` - Dashboard data
- `membership` - Membership levels
- `learndash` - LearnDash-specific data

## 🔒 Security Features

### Magic Link Authentication
- Cryptographically secure token generation
- Proper nonce verification
- IP address validation
- Session management improvements

### File Upload Security
- MIME type validation
- File extension checking
- Image dimension validation
- Security logging

### Error Monitoring
- Real-time error tracking
- User activity logging
- Security event monitoring
- Performance metrics

## 🎯 Membership Site Optimizations

### Role-Based Caching
- Automatic role detection
- Membership level consideration
- Plugin integration (LearnDash, MemberPress, WooCommerce)
- Smart cache invalidation

### Performance Benefits
- Faster page loads for logged-in users
- Reduced database queries
- Optimized IP lookups
- Better scalability

## 📁 File Structure

```
fli-child-theme/
├── assets/
│   ├── css/
│   └── js/
├── includes/
│   ├── error-logging.php
│   ├── caching-system.php
│   ├── magic-link-auth.php
│   ├── membership-caching-examples.php
│   └── ...
├── inc/
│   └── admin/
├── functions.php
├── style.css
├── CHANGELOG.md
└── README.md
```

## 🔄 Cache Invalidation

The system automatically clears cache when:
- User roles change
- Membership status changes
- Theme switches
- Plugin activations/deactivations
- Post updates/deletions

## 📈 Performance Monitoring

### Cache Statistics
- Hit ratio percentage
- Cache hits/misses count
- Cache sets/deletes count
- Performance metrics

### Error Tracking
- Error frequency
- User activity patterns
- Security events
- Performance bottlenecks

## 🛡️ Security Considerations

- All user inputs are sanitized
- Nonce verification for all AJAX requests
- IP address validation and tracking
- Secure token generation
- File upload validation
- Error logging with security context

## 🔧 Troubleshooting

### Common Issues

1. **Cache not working**: Check if transients are enabled
2. **Logs not appearing**: Ensure WP_DEBUG is enabled
3. **Performance issues**: Monitor cache hit ratios
4. **Security concerns**: Check error logs for suspicious activity

### Debug Mode
Enable debug mode in `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

## 📞 Support

- Check error logs in `Tools → FLI Debug Log`
- Monitor cache performance in `Tools → FLI Cache`
- Review membership caching examples
- Check WordPress error logs

## 📄 License

GNU General Public License v3 or later

## 🔄 Version History

- **v2.0.0**: Major security & performance update with error logging and role-based caching
- **v1.5.1.1**: Basic child theme functionality

---

**Note**: This theme is designed for membership sites and includes optimizations for LearnDash, MemberPress, and WooCommerce Memberships. Always backup your site before upgrading.
