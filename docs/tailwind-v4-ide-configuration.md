# Tailwind CSS v4 IDE Configuration

## Overview
This document explains how to configure your IDE to properly recognize and validate Tailwind CSS v4 syntax, particularly the new `@theme` directive and CSS-first configuration approach.

## Issue Description
When using Tailwind CSS v4, you may encounter IDE warnings such as:
- "Unknown at rule @theme"
- CSS validation errors for Tailwind v4 directives
- Missing autocomplete for Tailwind v4 features

## Root Cause
The issue occurs because:
1. **New Syntax**: Tailwind v4 introduces new CSS directives like `@theme` that older CSS language servers don't recognize
2. **CSS-First Approach**: The move from JavaScript configuration to CSS-first configuration requires updated IDE support
3. **Language Server Limitations**: Standard CSS language servers don't understand Tailwind-specific at-rules

## Solutions Implemented

### 1. VS Code Settings Configuration
**File**: `.vscode/settings.json`
```json
{
  "css.validate": false,
  "less.validate": false, 
  "scss.validate": false,
  "css.customData": [".vscode/tailwind.json"],
  "tailwindCSS.experimental.configFile": null,
  "tailwindCSS.includeLanguages": {
    "css": "css"
  },
  "files.associations": {
    "*.css": "tailwindcss"
  }
}
```

**Purpose**: 
- Disables default CSS validation that doesn't understand Tailwind v4
- Points to custom CSS data file for Tailwind directives
- Configures Tailwind CSS extension for v4 compatibility

### 2. Custom CSS Data Definition
**File**: `.vscode/tailwind.json`
```json
{
  "version": 1.1,
  "atDirectives": [
    {
      "name": "@theme",
      "description": "Tailwind CSS v4 theme configuration directive"
    },
    {
      "name": "@import", 
      "description": "Import Tailwind CSS v4 or other stylesheets"
    },
    {
      "name": "@layer",
      "description": "Tailwind CSS layer directive"
    }
  ]
}
```

**Purpose**: Defines Tailwind v4 at-rules for the CSS language server

### 3. Recommended Extensions
**File**: `.vscode/extensions.json`
```json
{
  "recommendations": [
    "bradlc.vscode-tailwindcss",
    "ms-vscode.vscode-css-peek",
    "zignd.html-css-class-completion"
  ]
}
```

**Purpose**: Ensures team members have the necessary extensions for Tailwind v4 support

### 4. Enhanced CSS Comments
Added comprehensive comments in `resources/css/app.css` to document the `@theme` directive:
```css
/* 
 * Tailwind CSS v4 Theme Configuration
 * 
 * The @theme directive is a new feature in Tailwind CSS v4 that allows
 * CSS-first configuration of design tokens. This replaces the JavaScript
 * configuration file approach used in v3.
 */
@theme {
  /* Design tokens */
}
```

## Alternative Solutions

### Option 1: Suppress CSS Validation
If you prefer to keep default CSS validation for other files, you can suppress warnings for specific rules:

```json
// .vscode/settings.json
{
  "css.lint.unknownAtRules": "ignore"
}
```

### Option 2: File-Specific Validation
Use CSS comments to disable validation for specific files:
```css
/* stylelint-disable */
@theme {
  /* Your theme configuration */
}
/* stylelint-enable */
```

### Option 3: PostCSS Language Mode
Configure VS Code to treat CSS files as PostCSS:
```json
// .vscode/settings.json
{
  "files.associations": {
    "*.css": "postcss"
  }
}
```

## IDE-Specific Configurations

### WebStorm/PhpStorm
1. Go to **Settings** → **Languages & Frameworks** → **Style Sheets** → **CSS**
2. Enable **Unknown CSS properties** → **Do not show**
3. Add custom CSS properties for Tailwind v4

### Sublime Text
Install the **Tailwind CSS** package:
1. Package Control → Install Package
2. Search for "Tailwind CSS"
3. Configure for v4 support

### Vim/Neovim
Use the `tailwindcss-language-server`:
```lua
-- For Neovim with nvim-lspconfig
require'lspconfig'.tailwindcss.setup{
  settings = {
    tailwindCSS = {
      experimental = {
        configFile = null
      }
    }
  }
}
```

## Verification Steps

### 1. Check CSS Validation
- Open `resources/css/app.css`
- Verify no "Unknown at rule @theme" errors
- Confirm syntax highlighting works correctly

### 2. Test Autocomplete
- Type `@` in a CSS file
- Verify `@theme` appears in autocomplete suggestions
- Test Tailwind class autocomplete in HTML/JSX files

### 3. Build Process
- Run `npm run build`
- Verify no CSS-related errors
- Confirm Tailwind v4 compilation works

## Troubleshooting

### Issue: Still seeing @theme errors
**Solution**: 
1. Restart VS Code completely
2. Clear VS Code workspace cache
3. Ensure Tailwind CSS extension is updated

### Issue: No autocomplete for Tailwind classes
**Solution**:
1. Install/update Tailwind CSS extension
2. Check `tailwindCSS.experimental.configFile` is set to `null`
3. Verify PostCSS configuration is correct

### Issue: Build errors with @theme
**Solution**:
1. Ensure `@tailwindcss/postcss` is installed
2. Check PostCSS configuration
3. Verify Tailwind v4 is properly installed

## Best Practices

### 1. Team Configuration
- Commit `.vscode/` folder to ensure consistent team setup
- Document Tailwind v4 usage in project README
- Use workspace-specific settings when possible

### 2. CSS Organization
- Keep `@theme` configuration at the top of CSS files
- Use comments to document custom design tokens
- Organize theme tokens logically (colors, fonts, spacing)

### 3. Migration Strategy
- Gradually migrate from v3 to v4 configuration
- Test IDE support before full team adoption
- Maintain fallback configurations during transition

## Conclusion

The IDE configuration changes resolve the "Unknown at rule @theme" error and provide proper support for Tailwind CSS v4 syntax. The configuration ensures:

- ✅ No CSS validation errors for Tailwind v4 directives
- ✅ Proper syntax highlighting for `@theme` and other directives
- ✅ Autocomplete support for Tailwind classes
- ✅ Consistent development experience across team members
- ✅ Future-proof setup for Tailwind v4 features

These configurations maintain full IDE functionality while supporting the modern CSS-first approach of Tailwind CSS v4.
