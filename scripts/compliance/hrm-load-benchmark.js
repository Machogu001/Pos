#!/usr/bin/env node

/*
  Lightweight HTTP benchmark for HRM endpoints.
  Usage:
    BASE_URL="https://example.com" COOKIE="laravel_session=...; XSRF-TOKEN=..." node scripts/compliance/hrm-load-benchmark.js
*/

const { performance } = require('node:perf_hooks');

const BASE_URL = (process.env.BASE_URL || 'http://127.0.0.1:8000').replace(/\/$/, '');
const COOKIE = process.env.COOKIE || '';
const CONCURRENCY = Number(process.env.CONCURRENCY || 10);
const ROUNDS = Number(process.env.ROUNDS || 30);

const paths = [
  '/hrm',
  '/hrm/employees',
  '/hrm/attendances',
  '/hrm/leaves',
  '/hrm/payrolls',
];

function percentile(values, p) {
  if (!values.length) return 0;
  const sorted = [...values].sort((a, b) => a - b);
  const i = Math.min(sorted.length - 1, Math.max(0, Math.ceil((p / 100) * sorted.length) - 1));
  return sorted[i];
}

async function hit(path) {
  const start = performance.now();
  try {
    const res = await fetch(`${BASE_URL}${path}`, {
      headers: COOKIE ? { Cookie: COOKIE } : {},
      redirect: 'manual',
    });
    const ms = performance.now() - start;
    return { ok: res.status < 500, status: res.status, ms };
  } catch (err) {
    const ms = performance.now() - start;
    return { ok: false, status: 0, ms, error: err.message };
  }
}

async function runPath(path) {
  const total = CONCURRENCY * ROUNDS;
  const times = [];
  let failures = 0;
  const statuses = {};

  for (let r = 0; r < ROUNDS; r++) {
    const batch = [];
    for (let c = 0; c < CONCURRENCY; c++) {
      batch.push(hit(path));
    }
    const results = await Promise.all(batch);
    for (const out of results) {
      times.push(out.ms);
      if (!out.ok) failures += 1;
      statuses[out.status] = (statuses[out.status] || 0) + 1;
    }
  }

  return {
    path,
    requests: total,
    failures,
    errorRate: total ? Number(((failures / total) * 100).toFixed(2)) : 0,
    p50: Number(percentile(times, 50).toFixed(2)),
    p95: Number(percentile(times, 95).toFixed(2)),
    p99: Number(percentile(times, 99).toFixed(2)),
    avg: Number((times.reduce((a, b) => a + b, 0) / (times.length || 1)).toFixed(2)),
    statuses,
  };
}

(async () => {
  const startedAt = new Date().toISOString();
  const summary = {
    startedAt,
    baseUrl: BASE_URL,
    concurrency: CONCURRENCY,
    rounds: ROUNDS,
    endpoints: [],
  };

  for (const path of paths) {
    const result = await runPath(path);
    summary.endpoints.push(result);
  }

  console.log(JSON.stringify(summary, null, 2));
})();
