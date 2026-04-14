#!/usr/bin/env node

/*
  HRM authenticated security verification.
  Usage:
    BASE_URL="https://example.com" COOKIE="XSRF-TOKEN=...; pos_session=..." node scripts/compliance/hrm-security-verify.js
*/

const puppeteer = require('puppeteer');

const BASE_URL = (process.env.BASE_URL || 'http://127.0.0.1:8000').replace(/\/$/, '');
const COOKIE = process.env.COOKIE || '';
const protectedPaths = ['/hrm', '/hrm/employees', '/hrm/attendances', '/hrm/leaves', '/hrm/payrolls', '/hrm/settings'];
const formAuditPaths = ['/hrm/attendances', '/hrm/leaves', '/hrm/payrolls'];

function parseCookieHeader(header) {
  return header
    .split(';')
    .map((part) => part.trim())
    .filter(Boolean)
    .map((part) => {
      const idx = part.indexOf('=');
      return idx === -1 ? null : { name: part.slice(0, idx), value: part.slice(idx + 1) };
    })
    .filter(Boolean);
}

(async () => {
  const browser = await puppeteer.launch({ headless: true, args: ['--no-sandbox'] });
  const authContext = await browser.newPage();
  const report = {
    generatedAt: new Date().toISOString(),
    baseUrl: BASE_URL,
    authenticated: Boolean(COOKIE),
    authenticatedChecks: [],
    unauthenticatedChecks: [],
    formProtectionChecks: [],
  };

  try {
    if (COOKIE) {
      const cookies = parseCookieHeader(COOKIE).map((cookie) => ({
        ...cookie,
        domain: new URL(BASE_URL).hostname,
        path: '/',
        secure: BASE_URL.startsWith('https://'),
      }));
      await authContext.setCookie(...cookies);
    }

    for (const path of protectedPaths) {
      const response = await authContext.goto(`${BASE_URL}${path}`, { waitUntil: 'domcontentloaded', timeout: 45000 });
      report.authenticatedChecks.push({
        path,
        status: response ? response.status() : null,
        url: authContext.url(),
      });
    }

    for (const path of formAuditPaths) {
      await authContext.goto(`${BASE_URL}${path}`, { waitUntil: 'networkidle2', timeout: 45000 });
      const data = await authContext.evaluate(() => ({
        csrfMetaPresent: !!document.querySelector('meta[name="csrf-token"]'),
        forms: Array.from(document.querySelectorAll('form')).map((form) => ({
          action: form.getAttribute('action'),
          method: (form.getAttribute('method') || 'GET').toUpperCase(),
          hasCsrfField: !!form.querySelector('input[name="_token"]'),
          hasMethodField: !!form.querySelector('input[name="_method"]'),
          confirmHook: !!form.getAttribute('data-hrm-confirm') || !!form.querySelector('[data-hrm-confirm-submit]'),
        })),
      }));
      report.formProtectionChecks.push({ path, ...data });
    }

    const isolated = await browser.createBrowserContext();
    const isolatedPage = await isolated.newPage();
    for (const path of ['/hrm', '/hrm/employees', '/hrm/payrolls']) {
      const response = await isolatedPage.goto(`${BASE_URL}${path}`, { waitUntil: 'domcontentloaded', timeout: 45000 });
      report.unauthenticatedChecks.push({
        path,
        status: response ? response.status() : null,
        url: isolatedPage.url(),
      });
    }
    await isolated.close();
  } finally {
    await browser.close();
  }

  console.log(JSON.stringify(report, null, 2));
})();
