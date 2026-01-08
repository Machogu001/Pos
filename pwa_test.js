const puppeteer = require('puppeteer');

(async () => {
  const url = process.argv[2] || 'https://solutions.bremac.co.ke/pos/public/home';
  console.log('Testing URL:', url);

  const browser = await puppeteer.launch({
    headless: true,
    args: ['--no-sandbox', '--disable-setuid-sandbox']
  });
  const page = await browser.newPage();

  // Emulate an Android mobile viewport and UA
  await page.setUserAgent('Mozilla/5.0 (Linux; Android 10; Pixel 5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/116.0.0.0 Mobile Safari/537.36');
  await page.setViewport({ width: 393, height: 851, isMobile: true });

  try {
    await page.goto(url, { waitUntil: 'networkidle2', timeout: 60000 });
  } catch (e) {
    console.error('Could not load page:', e.message);
    await browser.close();
    process.exit(2);
  }

  // Check manifest link
  const manifestHref = await page.evaluate(() => {
    const link = document.querySelector('link[rel="manifest"]');
    return link ? link.href : null;
  });

  let manifest = null;
  if (manifestHref) {
    try {
      const resp = await page.goto(manifestHref, { waitUntil: 'networkidle2', timeout: 30000 });
      const body = await resp.text();
      manifest = JSON.parse(body);
    } catch (e) {
      // fallback: try fetch in page context
      try {
        manifest = await page.evaluate(async (m) => {
          const r = await fetch(m, { credentials: 'same-origin' });
          return await r.json();
        }, manifestHref);
      } catch (er) {
        console.error('Failed to load manifest:', er.message);
      }
    }
  }

  // Check service worker registrations
  const swCount = await page.evaluate(async () => {
    if (!('serviceWorker' in navigator)) return -1;
    try {
      const regs = await navigator.serviceWorker.getRegistrations();
      return regs.length;
    } catch (e) {
      return -2; // error querying
    }
  });

  // Check beforeinstallprompt support (note: may not fire in headless)
  const supportsBeforeInstallPrompt = await page.evaluate(() => {
    return ('onbeforeinstallprompt' in window) || (typeof BeforeInstallPromptEvent !== 'undefined');
  });

  // Output results
  const result = {
    url,
    manifestHref,
    manifest: manifest ? (manifest.name ? { name: manifest.name, theme_color: manifest.theme_color, icons: manifest.icons } : manifest) : null,
    serviceWorkerRegistrations: swCount,
    supportsBeforeInstallPrompt
  };

  console.log('RESULT_JSON_START');
  console.log(JSON.stringify(result, null, 2));
  console.log('RESULT_JSON_END');

  await browser.close();
  process.exit(0);
})();
