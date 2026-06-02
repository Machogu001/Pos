#!/usr/bin/env node

/*
  Basic accessibility checks for HRM pages using Puppeteer.
  Usage:
    BASE_URL="https://example.com" node scripts/compliance/hrm-a11y-audit.js
    BASE_URL="https://example.com" COOKIE="laravel_session=...; XSRF-TOKEN=..." node scripts/compliance/hrm-a11y-audit.js
*/

const puppeteer = require('puppeteer');

const BASE_URL = (process.env.BASE_URL || 'http://127.0.0.1:8000').replace(/\/$/, '');
const COOKIE = process.env.COOKIE || '';
const pages = ['/hrm', '/hrm/employees', '/hrm/attendances', '/hrm/leaves', '/hrm/payrolls'];

function sleep(ms) {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

async function gotoWithRetry(page, url, options) {
  try {
    return await page.goto(url, options);
  } catch (error) {
    const message = String(error && error.message ? error.message : error);
    if (!message.includes('ERR_CONNECTION_CLOSED')) {
      throw error;
    }

    await sleep(400);
    return page.goto(url, { waitUntil: 'domcontentloaded', timeout: options.timeout || 45000 });
  }
}

async function auditPage(page, path) {
  const url = `${BASE_URL}${path}`;
  await gotoWithRetry(page, url, { waitUntil: 'networkidle2', timeout: 45000 });

  const findings = await page.evaluate(() => {
    const issues = [];

    const images = Array.from(document.querySelectorAll('img'));
    const missingAlt = images.filter((img) => !img.hasAttribute('alt') || img.getAttribute('alt').trim() === '');
    if (missingAlt.length) {
      issues.push({ type: 'img-alt', severity: 'medium', count: missingAlt.length, message: 'Images missing alt text' });
    }

    const inputs = Array.from(document.querySelectorAll('input, select, textarea'));
    const unlabeled = inputs.filter((el) => {
      const id = el.id;
      const hasAria = !!(el.getAttribute('aria-label') || el.getAttribute('aria-labelledby'));
      const hasLabel = id ? !!document.querySelector(`label[for="${CSS.escape(id)}"]`) : false;
      return !hasAria && !hasLabel;
    });
    if (unlabeled.length) {
      issues.push({ type: 'form-labels', severity: 'high', count: unlabeled.length, message: 'Form controls without associated labels' });
    }

    const headingLevels = Array.from(document.querySelectorAll('h1,h2,h3,h4,h5,h6')).map((h) => Number(h.tagName.slice(1)));
    let headingSkips = 0;
    for (let i = 1; i < headingLevels.length; i++) {
      if (headingLevels[i] - headingLevels[i - 1] > 1) headingSkips += 1;
    }
    if (headingSkips > 0) {
      issues.push({ type: 'heading-order', severity: 'low', count: headingSkips, message: 'Possible heading level skips' });
    }

    const links = Array.from(document.querySelectorAll('a'));
    const emptyLinks = links.filter((a) => (a.textContent || '').trim() === '' && !a.getAttribute('aria-label'));
    if (emptyLinks.length) {
      issues.push({ type: 'link-name', severity: 'medium', count: emptyLinks.length, message: 'Links without accessible name' });
    }

    return issues;
  });

  return { path, url, findings };
}

(async () => {
  const browser = await puppeteer.launch({
    headless: true,
    ignoreHTTPSErrors: true,
    args: [
      '--no-sandbox',
      '--disable-setuid-sandbox',
      '--disable-features=HttpsFirstBalancedModeAutoEnable,HttpsUpgrades',
    ],
  });
  const page = await browser.newPage();
  const report = { generatedAt: new Date().toISOString(), baseUrl: BASE_URL, pages: [] };

   if (COOKIE) {
    await page.setExtraHTTPHeaders({
      Cookie: COOKIE,
    });
  }

  try {
    for (const path of pages) {
      const result = await auditPage(page, path);
      report.pages.push(result);
    }
  } finally {
    await browser.close();
  }

  console.log(JSON.stringify(report, null, 2));
})();
