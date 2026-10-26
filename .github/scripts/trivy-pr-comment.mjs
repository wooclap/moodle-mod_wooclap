// Builds the Trivy PR comment (trivy-comment.md) from trivy-results.json.
// Same layout as the shared wooclap/devx workflow's comment, without the
// PR-vs-base diff and EPSS/KEV enrichment: the findings listed here are every
// finding in the branch, not only the ones the PR introduced.
//
// This repository is public: the comment is public too. Secrets are reported by
// file, type and line range only — never Code or Match.
import { existsSync, readFileSync, writeFileSync } from 'node:fs';

const INPUT = 'trivy-results.json';
const OUTPUT = 'trivy-comment.md';
const MARKER = '<!-- trivy-scan-comment -->';
const MAX_ROWS = 50;
const SEVERITY = process.env.SEVERITY || 'CRITICAL,HIGH';
const RUN_URL = process.env.RUN_URL || '';

const cell = (s) => String(s ?? '').replace(/\|/g, '\\|').replace(/\n/g, ' ');
const order = { CRITICAL: 0, HIGH: 1, MEDIUM: 2, LOW: 3 };
const bySeverity = (key) => (a, b) =>
  (order[a.severity] ?? 9) - (order[b.severity] ?? 9) || String(a[key]).localeCompare(String(b[key]));

function extract(report) {
  const f = { vulnerabilities: [], misconfigurations: [], secrets: [], licenses: [] };
  for (const r of report.Results || []) {
    for (const v of r.Vulnerabilities || []) {
      f.vulnerabilities.push({
        pkg: v.PkgName, version: v.InstalledVersion, id: v.VulnerabilityID,
        url: v.PrimaryURL, severity: v.Severity, fix: v.FixedVersion,
      });
    }
    for (const m of r.Misconfigurations || []) {
      if (m.Status !== 'FAIL') continue;
      f.misconfigurations.push({ file: r.Target, id: m.ID, severity: m.Severity, title: m.Title });
    }
    for (const s of r.Secrets || []) {
      f.secrets.push({ file: r.Target, title: s.Title, severity: s.Severity, start: s.StartLine, end: s.EndLine });
    }
    if (r.Class === 'license') {
      for (const l of r.Licenses || []) f.licenses.push({ pkg: l.PkgName, name: l.Name, severity: l.Severity });
    }
  }
  return f;
}

function table(items, sortKey, header, row) {
  const rows = [...items].sort(bySeverity(sortKey)).slice(0, MAX_ROWS);
  const sep = header.replace(/[^|]/g, '-');
  const more = items.length > rows.length
    ? `\n\n*Showing ${rows.length} of ${items.length}; the full list is in the \`trivy-results\` artifact.*`
    : '';
  return [header, sep, ...rows.map(row)].join('\n') + more;
}

function section(title, items, body) {
  if (items.length === 0) return [];
  return ['', '<details>', `<summary>${title} (${items.length})</summary>`, '', body, '', '</details>'];
}

function count(items) {
  const c = { CRITICAL: 0, HIGH: 0 };
  for (const i of items) if (i.severity in c) c[i.severity]++;
  return `${c.CRITICAL} | ${c.HIGH} | ${items.length}`;
}

const footer = [
  '',
  '---',
  `*Severity: ${SEVERITY}. Full JSON report: \`trivy-results\` artifact${RUN_URL ? ` of [this run](${RUN_URL})` : ''}.*`,
  '',
];

if (!existsSync(INPUT)) {
  writeFileSync(OUTPUT, [MARKER, '## ⚠️ Trivy Security Scan — no results', '',
    'The scan did not produce a report. Check the job logs.', ...footer].join('\n'));
  console.log(`${INPUT} not found; wrote the no-results comment.`);
  process.exit(0);
}

const f = extract(JSON.parse(readFileSync(INPUT, 'utf-8')));
const total = f.vulnerabilities.length + f.misconfigurations.length + f.secrets.length + f.licenses.length;
const lines = [MARKER];

if (total === 0) {
  lines.push('## ✅ Trivy Security Scan — No Issues Found');
} else {
  lines.push(`## ❌ Trivy Security Scan — ${total} Issues Found`, '',
    '| Category | Critical | High | Total |',
    '|----------|----------|------|-------|',
    `| Vulnerabilities | ${count(f.vulnerabilities)} |`,
    `| Misconfigurations | ${count(f.misconfigurations)} |`,
    `| Secrets | ${count(f.secrets)} |`,
    `| Licenses | ${count(f.licenses)} |`);
  lines.push(...section('Vulnerabilities', f.vulnerabilities, table(f.vulnerabilities, 'pkg',
    '| Package | Version | CVE | Severity | Fix Available |',
    (v) => `| ${cell(v.pkg)} | ${cell(v.version)} | ${v.url ? `[${cell(v.id)}](${v.url})` : cell(v.id)} | ${cell(v.severity)} | ${v.fix ? cell(v.fix) : 'No'} |`)));
  lines.push(...section('Misconfigurations', f.misconfigurations, table(f.misconfigurations, 'file',
    '| File | ID | Severity | Title |',
    (m) => `| ${cell(m.file)} | ${cell(m.id)} | ${cell(m.severity)} | ${cell(m.title)} |`)));
  lines.push(...section('Secrets', f.secrets, table(f.secrets, 'file',
    '| File | Type | Lines |',
    (s) => `| ${cell(s.file)} | ${cell(s.title)} | ${s.start}-${s.end} |`)));
  lines.push(...section('Licenses', f.licenses, table(f.licenses, 'pkg',
    '| Package | License | Severity |',
    (l) => `| ${cell(l.pkg)} | ${cell(l.name)} | ${cell(l.severity)} |`)));
}

writeFileSync(OUTPUT, [...lines, ...footer].join('\n'));
console.log(`${OUTPUT} written (${total} findings).`);
