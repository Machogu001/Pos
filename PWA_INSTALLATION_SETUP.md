# PWA Installation Setup - Bremac POS

## Overview
The Bremac POS application is configured as a Progressive Web App (PWA), allowing users to install it directly on their devices for quick access, similar to native applications.

## Current Setup Status

### ✅ Completed Components

1. **Manifest File** (`public/manifest.json`)
   - App name: "Bremac POS"
   - Start URL: `/`
   - Display mode: `standalone` (fullscreen app-like experience)
   - Theme color: `#1976d2`
   - Icons: 192x192 and 512x512 PNG + SVG formats

2. **Service Worker** (`public/service-worker.js`)
   - Enables offline support
   - Caches assets for faster loading
   - Network fallback strategy
   - Automatic cache updates

3. **PWA HTML Meta Tags** (in `resources/views/layouts/app.blade.php`)
   - Manifest link with asset versioning
   - Apple mobile web app support
   - iOS app capability meta tags
   - Apple touch icon configuration

4. **Installation Prompt Modal**
   - Location: `resources/views/layouts/partials/install_prompt.blade.php`
   - Features:
     - Automatic detection of beforeinstallprompt event
     - iOS manual installation instructions
     - Dismissal tracking (client-side localStorage + server-side database)
     - Telemetry tracking (shown, accepted, dismissed events)
     - Close button (X) with proper event handling ✅ **FIXED**

## Recent Improvements

### Close Button Fix (January 28, 2026)
- **Issue**: The modal close button (X) wasn't properly triggering the PWA dismissal tracking
- **Solution**: 
  - Added `id="pwa-modal-close-btn"` to the close button for reliable selection
  - Created `handlePwaDismiss()` helper function to consolidate dismiss logic
  - Both close button (X) and "Close" button now properly trigger:
    - localStorage flag setting
    - Server-side dismissal API call
    - Telemetry tracking
    - Modal closing animation

## Requirements for PWA Installation

### ✅ Security Requirements
- **HTTPS Only**: The app URL must use HTTPS (currently: `https://pos.bremac.co.ke`)
- **Valid Certificate**: SSL certificate must be valid and not self-signed

### ✅ Technical Requirements
- Valid `manifest.json` with required fields
- Service worker registered successfully
- Accessible offline.html fallback page
- Proper meta tags in HTML head

### Browser Support
| Browser | Platform | Installation Method |
|---------|----------|-------------------|
| Chrome | Android | "Install app" button in address bar |
| Edge | Android | "Install app" button in address bar |
| Firefox | Android | "Install app" option in menu |
| Safari | iOS | Manual via Share → Add to Home Screen |
| Chrome | Desktop | "Install app" button (if criteria met) |

## Testing the PWA Installation

### 1. Verify PWA Setup
```bash
# Test via Node.js script (if puppeteer available)
node pwa_test.js https://pos.bremac.co.ke
```

Expected output should show:
- manifestHref: URL to manifest
- manifest: Valid JSON with name, icons, theme_color
- serviceWorkerRegistrations: 1
- supportsBeforeInstallPrompt: true (on Chrome/Edge)

### 2. Manual Testing on Desktop

**Chrome/Edge:**
1. Open https://pos.bremac.co.ke in Chrome or Edge
2. Look for "Install app" button in the address bar (⬇️ icon)
3. Or check the header for the install button (if beforeinstallprompt fires)

**Firefox Desktop:**
1. Service worker will register but installation prompt is limited
2. Users can add to home screen via Firefox's built-in option

### 3. Manual Testing on Mobile

**Android (Chrome/Edge/Firefox):**
1. Open https://pos.bremac.co.ke on mobile
2. Look for "Install app" option:
   - Chrome: Address bar button or menu option
   - Firefox: Menu options
3. Tap to install
4. App appears on home screen

**iOS (Safari):**
1. Open https://pos.bremac.co.ke in Safari
2. Modal shows with iOS installation instructions
3. User follows manual steps:
   - Tap Share button (⬇️)
   - Select "Add to Home Screen"
   - Tap "Add"
4. App appears on home screen

### 4. Test Close Button Functionality
1. Open the PWA in a supported browser
2. Close button prompt should appear
3. Click the X button in the modal header
4. Verify:
   - Modal closes smoothly
   - localStorage shows `pwa-install-dismissed: '1'`
   - Server logs dismissal event
   - Modal doesn't re-appear on page reload

## Database Fields

If `pwa_installed_at` and `pwa_install_dismissed_at` columns exist on the `users` table:

```sql
ALTER TABLE users ADD COLUMN pwa_installed_at TIMESTAMP NULL;
ALTER TABLE users ADD COLUMN pwa_install_dismissed_at TIMESTAMP NULL;
```

These fields track user installation status server-side to reduce prompts.

## API Endpoints

The PWA uses these endpoints (defined in install_prompt.blade.php):

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/pwa/telemetry-public` | POST | Track events (shown, accepted, dismissed) |
| `/pwa/installed` | POST | Mark user as having installed app |
| `/pwa/dismissed` | POST | Mark user as having dismissed prompt |

## Troubleshooting

### Installation Prompt Not Showing

**Possible Causes:**
1. **Not HTTPS**: App must be served over HTTPS
2. **Invalid manifest**: Check manifest.json is valid JSON
3. **Missing service worker**: Ensure service-worker.js registers successfully
4. **Already dismissed**: Check localStorage for `pwa-install-dismissed`
5. **Add URL param**: Use `?pwa_test=1` to force-show modal for testing

**Debug Helper:**
```javascript
// In browser console
window.__bremac_probe_pwa()  // Check PWA status
window.__bremac_pwa_status  // View current status
window.__bremac_show_install_modal()  // Force show modal
```

### Close Button Not Working

**Fixed in this update** - The close button now:
- Properly captures click events
- Triggers dismissal tracking
- Closes modal with animation
- Prevents re-showing until cache cleared

### Service Worker Not Registering

**Check:**
1. Browser DevTools → Application tab → Service Workers
2. Verify registration shows in Console
3. Check for CORS/HTTPS issues
4. Clear cache: `Clear site data` in DevTools

### Icons Not Loading

**Verify:**
1. Icons exist: `public/pwa-icons/icon-192.png` and `icon-512.png`
2. Check manifest.json paths are correct
3. Verify file permissions: `ls -la public/pwa-icons/`
4. Test icon URLs directly in browser

## Best Practices

1. **Clear Testing**: Use `?pwa_test=1` to bypass storage checks
2. **Test on Real Device**: Emulators may not support all features
3. **Monitor Telemetry**: Track installation/dismissal events
4. **Responsive Design**: Ensure app works on various screen sizes
5. **Offline Support**: Test offline mode to verify fallback page works
6. **Cache Invalidation**: Update service worker version when changing assets

## Additional Resources

- [MDN - Progressive Web Apps](https://developer.mozilla.org/en-US/docs/Web/Progressive_web_apps)
- [Google - Web App Install Prompt](https://developers.google.com/web/fundamentals/app-install-banners)
- [Web Manifest Specification](https://www.w3.org/TR/appmanifest/)
- [Service Worker API](https://developer.mozilla.org/en-US/docs/Web/API/Service_Worker_API)

## Support

For issues with PWA installation:
1. Check browser compatibility at https://caniuse.com/web-app-manifest
2. Review browser console for errors
3. Verify HTTPS and certificate validity
4. Test with the PWA test script: `node pwa_test.js <URL>`
