# Color Variable Migration Guide

## Overview
This guide helps transition from hardcoded color values to the new CSS variable system defined in `color-variables.css`.

## Color Mapping Reference

### Brand Colors
- `#59898d` → `var(--fl-brand-primary)`
- `rgba(89, 137, 141, 0.2)` → `var(--fl-brand-primary-light)`
- `rgba(89, 137, 141, 0.5)` → `var(--fl-brand-primary-medium)`
- `rgba(89, 137, 141, 0.9)` → `var(--fl-brand-primary-dark)`
- `#0a738a` → `var(--fl-brand-primary-darker)`
- `rgba(191, 192, 70, 0.5)` → `var(--fl-brand-secondary-medium)`

### Accent Colors
- `#ff69b4` → `var(--fl-accent-pink)`
- `#fde132` → `var(--fl-accent-yellow)`
- `#009bde` → `var(--fl-accent-blue)`
- `#ff6b00` → `var(--fl-accent-orange)`
- `#007CFF` → `var(--fl-accent-bright-blue)`
- `#2271b1` → `var(--fl-accent-wp-blue)`

### Neutral Colors
- `#ffffff` → `var(--fl-white)`
- `#f9f9f9` → `var(--fl-white-95)`
- `#FAFBFD` → `var(--fl-gray-50)`
- `#F2F4F5` → `var(--fl-gray-100)`
- `#E7E9EC` → `var(--fl-gray-200)`
- `#D6D9DD` → `var(--fl-gray-300)`
- `#ddd` → `var(--fl-gray-400)`
- `#7F868F` → `var(--fl-gray-500)`
- `#647385` → `var(--fl-gray-600)`
- `#393939` → `var(--fl-gray-700)`
- `#2c3e50` → `var(--fl-gray-800)`
- `#122B46` → `var(--fl-gray-900)`

### Semantic Colors
- `#27ae60` → `var(--fl-success)`
- `#e74c3c` → `var(--fl-danger)`
- `#dc3545` → `var(--fl-danger-dark)`
- `#385DFF` → `var(--fl-info)`

## Implementation Steps

1. **Include the color variables file** in your child theme's functions.php:
```php
function fl_enqueue_color_variables() {
    wp_enqueue_style(
        'fl-color-variables',
        get_stylesheet_directory_uri() . '/assets/css/color-variables.css',
        array(),
        '1.0.0'
    );
}
add_action('wp_enqueue_scripts', 'fl_enqueue_color_variables', 5);
```

2. **Update custom.css** - Replace hardcoded colors with variables:
```css
/* Before */
.resource-item {
    border: 2px solid #59898d;
}

/* After */
.resource-item {
    border: 2px solid var(--fl-brand-primary);
}
```

3. **Update inline styles** in PHP files:
```php
/* Before */
style="background-color: #59898d;"

/* After */
style="background-color: var(--fl-brand-primary);"
```

## Benefits of Using CSS Variables

1. **Consistency**: All colors defined in one place
2. **Maintainability**: Easy to update brand colors globally
3. **Theming**: Support for dark mode and alternate themes
4. **Performance**: Reduced CSS file size through reuse
5. **Developer Experience**: Semantic naming makes code more readable

## Advanced Usage

### Creating Color Variations
```css
/* Lighten a color using opacity */
.element {
    background-color: var(--fl-brand-primary-light);
}

/* Creating hover states */
.button:hover {
    background-color: var(--fl-brand-primary-dark);
}
```

### Fallback Values
```css
/* Provide fallback for older browsers */
.element {
    color: #59898d; /* Fallback */
    color: var(--fl-brand-primary);
}
```

### JavaScript Integration
```javascript
// Get CSS variable value
const primaryColor = getComputedStyle(document.documentElement)
    .getPropertyValue('--fl-brand-primary');

// Set CSS variable value
document.documentElement.style.setProperty('--fl-brand-primary', '#newcolor');
```

## Testing Checklist

- [ ] Colors display correctly in all major browsers
- [ ] Fallbacks work for older browsers
- [ ] Dark mode variables switch properly
- [ ] No visual regressions from color changes
- [ ] Print styles still work correctly
- [ ] Accessibility contrast ratios maintained