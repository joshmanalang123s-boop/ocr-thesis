# 🎨 Icon Reference Guide - Plate QR System

This document lists all Remix Icons used throughout the Plate QR System interface.

---

## Icon Library

**Library:** Remix Icon v3.5.0  
**CDN:** https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css  
**Documentation:** https://remixicon.com/

---

## Navigation Icons

| Icon | Class | Location | Usage |
|------|-------|----------|-------|
| 🚗 → | `ri-car-line` | Sidebar Brand | Main logo/brand icon |
| 📊 → | `ri-dashboard-line` | Sidebar Nav | Dashboard page link |
| 📸 → | `ri-camera-line` | Sidebar Nav | Scan Plate page link |
| 📋 → | `ri-history-line` | Sidebar Nav | History page link |
| 🔍 → | `ri-scan-line` | Sidebar Nav | OCR Tool page link |
| ⚙️ → | `ri-settings-3-line` | Sidebar Nav | Settings page link |
| ☰ → | `ri-menu-line` | Header | Mobile menu toggle |

---

## Statistics Card Icons

| Icon | Class | Location | Usage |
|------|-------|----------|-------|
| 📊 → | `ri-bar-chart-box-line` | Dashboard Stats | Total detections card |
| ✓ → | `ri-checkbox-circle-line` | Dashboard Stats | Today's scans card |
| 📈 → | `ri-line-chart-line` | Dashboard Stats | This week card |
| 🎯 → | `ri-target-line` | Dashboard Stats | Success rate card |

---

## Action Icons

| Icon | Class | Location | Usage |
|------|-------|----------|-------|
| 📸 → | `ri-camera-line` | Buttons | Scan new plate action |
| 📥 → | `ri-download-line` | Buttons/Actions | Download/Export |
| 👁️ → | `ri-eye-line` | Table Actions | View details |
| 🖨️ → | `ri-printer-line` | Table Actions | Print record |
| 🗑️ → | `ri-delete-bin-line` | Buttons | Clear history |
| 🔍 → | `ri-search-line` | Buttons | Search/Filter |

---

## Status & Feedback Icons

| Icon | Class | Location | Usage |
|------|-------|----------|-------|
| ✓ → | `ri-checkbox-circle-line` | Badges/Alerts | Success status |
| ⚠️ → | `ri-error-warning-line` | Alerts | Error/Warning messages |

---

## Empty State Icons

| Icon | Class | Location | Usage |
|------|-------|----------|-------|
| 🚗 → | `ri-car-line` | Empty Tables | No detections message |

---

## Icon Sizes

### Sidebar Navigation
- **Size:** 1.125rem (18px)
- **Line Height:** 1
- **Color:** Inherits from nav-link

### Stat Card Icons
- **Size:** 1.5rem (24px)
- **Container:** 48x48px rounded square
- **Background:** Color-coded (primary, success, warning, info)

### Action Buttons
- **Size:** 1rem (16px)
- **Container:** 32x32px square
- **Hover:** Changes to white on primary background

### Empty State Icons
- **Size:** 4rem (64px)
- **Opacity:** 0.3
- **Color:** Inherits

### Badge Icons
- **Size:** 0.875rem (14px)
- **Line Height:** 1

### Button Icons
- **Size:** 1.125rem (18px)
- **Line Height:** 1
- **Spacing:** 0.5rem gap from text

### Alert Icons
- **Size:** 1.25rem (20px)
- **Line Height:** 1

---

## Color Coding

### Icon Background Colors (Stat Cards)

```css
.stat-icon.primary {
    background: #E8EBFC; /* Light Blue */
}

.stat-icon.success {
    background: #D1FAE5; /* Light Green */
}

.stat-icon.warning {
    background: #FEF3C7; /* Light Yellow */
}

.stat-icon.info {
    background: #DBEAFE; /* Light Blue */
}
```

### Quick Actions Icons
- **Color:** `var(--primary-color)` (#5B6FED)
- **Size:** 1.25rem

---

## Usage Examples

### In Sidebar Navigation
```html
<span class="nav-icon"><i class="ri-dashboard-line"></i></span>
```

### In Buttons
```html
<button class="btn btn-primary">
    <i class="ri-camera-line"></i>
    <span>Scan New Plate</span>
</button>
```

### In Action Buttons
```html
<button class="action-btn" title="Download">
    <i class="ri-download-line"></i>
</button>
```

### In Stat Cards
```html
<div class="stat-icon primary">
    <i class="ri-bar-chart-box-line"></i>
</div>
```

### In Badges
```html
<span class="badge success">
    <i class="ri-checkbox-circle-line"></i>
    <span>Success</span>
</span>
```

### In Empty States
```html
<div class="empty-icon">
    <i class="ri-car-line"></i>
</div>
```

---

## Icon Variants

Remix Icon provides multiple variants for each icon:

- **Line** (outlined): `-line` suffix - Used throughout the app
- **Fill** (solid): `-fill` suffix - Not currently used
- **Duotone**: Available for some icons - Not currently used

**Note:** We use **line variant** exclusively for consistency.

---

## Adding New Icons

### 1. Find Icon on Remix Icon Website
Visit: https://remixicon.com/

### 2. Copy Class Name
Example: `ri-notification-line`

### 3. Use in HTML
```html
<i class="ri-notification-line"></i>
```

### 4. Style as Needed
```css
.your-icon {
    font-size: 1.25rem;
    color: var(--primary-color);
    line-height: 1;
}
```

---

## Best Practices

### ✅ DO
- Use line variant icons for consistency
- Set `line-height: 1` for proper alignment
- Use semantic icon names
- Add `title` or `aria-label` for accessibility
- Maintain consistent sizing within sections
- Use color-coded backgrounds for stat cards

### ❌ DON'T
- Mix line and fill variants
- Use emojis (deprecated in v2.0)
- Forget to set line-height
- Use icons without semantic meaning
- Make icons too large or too small
- Use too many different icon styles

---

## Accessibility

### Icon-Only Buttons
Always add `title` attribute or `aria-label`:
```html
<button class="action-btn" title="Download" aria-label="Download QR Code">
    <i class="ri-download-line"></i>
</button>
```

### Decorative Icons
If icon is purely decorative (has text label):
```html
<button class="btn btn-primary">
    <i class="ri-camera-line" aria-hidden="true"></i>
    <span>Scan Plate</span>
</button>
```

---

## Performance

### CDN Loading
Icons are loaded from CDN for optimal performance:
```html
<link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
```

### Font Loading Strategy
- Loaded in `<head>` for immediate availability
- Cached by browser after first load
- No JavaScript required
- ~50KB total size

---

## Browser Support

Remix Icon works in all modern browsers:
- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+
- Opera 76+

Icon fonts use standard web font technology, ensuring wide compatibility.

---

## Migration Notes

### v1.0 → v2.0 Icon Migration

| Old (Emoji) | New (Icon Class) | Location |
|-------------|------------------|----------|
| 🚗 | `ri-car-line` | Logo, Empty states |
| 📊 | `ri-dashboard-line` | Dashboard nav |
| 📸 | `ri-camera-line` | Scan actions |
| 📋 | `ri-history-line` | History nav |
| 🔍 | `ri-scan-line` | OCR tool nav |
| ⚙️ | `ri-settings-3-line` | Settings nav |
| ☰ | `ri-menu-line` | Mobile menu |
| 📥 | `ri-download-line` | Download actions |
| 👁️ | `ri-eye-line` | View actions |
| 🖨️ | `ri-printer-line` | Print actions |
| 🗑️ | `ri-delete-bin-line` | Delete actions |
| ✓ | `ri-checkbox-circle-line` | Success status |
| ⚠️ | `ri-error-warning-line` | Warnings/Errors |
| 📈 | `ri-line-chart-line` | Stats/Charts |
| 🎯 | `ri-target-line` | Goals/Targets |

---

## Quick Reference

### Most Used Icons

```
ri-car-line          - Main app icon
ri-dashboard-line    - Dashboard
ri-camera-line       - Scanning
ri-download-line     - Downloads
ri-eye-line          - View
ri-printer-line      - Print
ri-checkbox-circle-line - Success
```

### Icon Categories

**Navigation:** dashboard, camera, history, scan, settings, menu  
**Actions:** download, eye, printer, delete-bin, search  
**Status:** checkbox-circle, error-warning  
**Data:** bar-chart-box, line-chart, target  
**Objects:** car  

---

## Support

For more icons, visit: https://remixicon.com/  
For icon issues, check browser console for font loading errors.

---

**Last Updated:** December 2024  
**Version:** 2.0  
**Icon Library:** Remix Icon 3.5.0