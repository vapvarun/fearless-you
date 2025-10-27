# Linting Setup for FLI Child Theme

## Overview

This theme now has comprehensive linting setup for both PHP and JavaScript code to ensure code quality and WordPress coding standards compliance.

## Tools Installed

### PHP Linting
- **PHP_CodeSniffer (PHPCS)** v3.13.4
- **WordPress Coding Standards (WPCS)** v3.2.0
- **PHPCSUtils** v1.1.3
- **PHPCSExtra** v1.4.1

### JavaScript Linting
- **ESLint** v8.57.1
- **@wordpress/eslint-plugin** v22.19.0
- **eslint-plugin-jsdoc** v61.1.4

## Configuration Files

- `phpcs.xml` - PHP_CodeSniffer configuration
- `.eslintrc.json` - ESLint configuration
- `.eslintignore` - Files to ignore for JavaScript linting
- `package.json` - NPM scripts for linting

## Usage

### Quick Commands

From the theme directory (`/var/www/fearlessliving/wp-content/themes/fli-child-theme`):

```bash
# Lint all files (both PHP and JavaScript)
npm run lint

# JavaScript only
npm run lint:js              # Check JavaScript files
npm run lint:js:fix          # Auto-fix JavaScript issues

# PHP only
npm run lint:php             # Check PHP files
npm run lint:php:fix         # Auto-fix PHP issues
```

### Manual Commands

#### PHP Linting
```bash
# Check a specific file
export PATH=$PATH:/root/.config/composer/vendor/bin
phpcs --standard=phpcs.xml functions.php

# Auto-fix a specific file
phpcbf --standard=phpcs.xml functions.php

# Check entire theme
phpcs --standard=phpcs.xml .

# Get summary report
phpcs --standard=phpcs.xml . --report=summary

# Get detailed source report
phpcs --standard=phpcs.xml . --report=source
```

#### JavaScript Linting
```bash
# Check a specific file
npx eslint custom-fivo-docs.js

# Auto-fix a specific file
npx eslint custom-fivo-docs.js --fix

# Check all JavaScript files
npx eslint '**/*.js' --ignore-pattern 'node_modules/' --ignore-pattern '*.min.js'
```

## Results Summary

### Initial State
- **functions.php**: 1,175 errors, 63 warnings
- **lccp-systems.php**: 3,493 errors, 126 warnings
- **JavaScript files**: 2,053 errors, 28 warnings

### After Auto-Fix
- **functions.php**: 113 errors, 10 warnings (90% reduction)
- **lccp-systems.php**: 335 errors, remaining (90% reduction)
- **JavaScript files**: 89 errors, 28 warnings (96% reduction)

## Customizations

The `phpcs.xml` configuration has been customized to:
- Allow `error_log()` and `print_r()` for debugging
- Relax inline comment punctuation rules
- Allow theme-specific prefixes: `fli_` and `fearless_`
- Set text domain to `buddyboss-theme`

The `.eslintrc.json` configuration:
- Uses WordPress ESLint plugin
- Enforces tab indentation
- Allows common WordPress globals (jQuery, wp, ajaxurl)
- Allows LearnDash globals (ld_video_players, LearnDash_disable_assets, etc.)
- Console statements are warnings (not errors)

## Remaining Issues

Most remaining errors are:
1. **Missing function documentation** - Should be added manually
2. **Nonce verification** - Security best practice for forms
3. **Input sanitization** - Security requirement for user input
4. **Output escaping** - Security requirement for output
5. **Yoda conditions** - WordPress style preference

These require manual review and fixes based on context.

## Best Practices

1. **Run linters before committing** code
2. **Auto-fix what you can** with `--fix` flags
3. **Review manual fixes** required for security issues
4. **Add function documentation** for all custom functions
5. **Keep dependencies updated** regularly

## Updating Dependencies

```bash
# Update PHP dependencies
composer global update

# Update JavaScript dependencies
npm update
```

## Integration with Git

Consider adding a pre-commit hook:

```bash
#!/bin/sh
cd /var/www/fearlessliving/wp-content/themes/fli-child-theme
npm run lint
```

## Support

For issues with:
- PHPCS: https://github.com/squizlabs/PHP_CodeSniffer
- WPCS: https://github.com/WordPress/WordPress-Coding-Standards
- ESLint: https://eslint.org/docs/latest/
- WordPress ESLint: https://www.npmjs.com/package/@wordpress/eslint-plugin
