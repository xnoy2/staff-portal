/**
 * Generates docs/user-manual.pdf
 * Run:  node scripts/generate-manual-pdf.mjs
 */

import { existsSync } from 'fs';
import { resolve, dirname } from 'path';
import { fileURLToPath } from 'url';
import puppeteer from 'puppeteer-core';

const __dir = dirname(fileURLToPath(import.meta.url));
const root  = resolve(__dir, '..');
const out   = resolve(root, 'docs/user-manual.pdf');

const BROWSER_PATHS = [
    'C:/Program Files/Google/Chrome/Application/chrome.exe',
    'C:/Program Files (x86)/Google/Chrome/Application/chrome.exe',
    'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe',
    'C:/Program Files/Microsoft/Edge/Application/msedge.exe',
];
const executablePath = BROWSER_PATHS.find(p => existsSync(p));
if (!executablePath) { console.error('No Chrome/Edge found.'); process.exit(1); }
console.log('Browser:', executablePath);

// ─────────────────────────────────────────────────────────────────────────────
// HELPERS
// ─────────────────────────────────────────────────────────────────────────────

const badge = (label, color) =>
    `<span style="display:inline-block;font-size:7.5pt;font-weight:700;padding:2px 8px;border-radius:20px;background:${color}20;color:${color};border:1px solid ${color}40;white-space:nowrap">${label}</span>`;

const chip = (icon, text) =>
    `<div class="chip"><span class="chip-icon">${icon}</span><span>${text}</span></div>`;

const featureCard = (icon, title, lines) => `
<div class="feat-card">
  <div class="feat-icon">${icon}</div>
  <div class="feat-body">
    <p class="feat-title">${title}</p>
    <ul>${lines.map(l => `<li>${l}</li>`).join('')}</ul>
  </div>
</div>`;

const step = (n, text) =>
    `<div class="step"><div class="step-n">${n}</div><div class="step-text">${text}</div></div>`;

const tipBox = (icon, text, color = '#3B82F6') => `
<div class="tip-box" style="border-color:${color};background:${color}10">
  <span style="color:${color};font-size:14pt">${icon}</span>
  <p style="color:${color}CC">${text}</p>
</div>`;

const roleHeader = (role, tagline, color, icon) => `
<div class="role-header" style="background:linear-gradient(135deg,${color} 0%,${color}CC 100%)">
  <div class="role-icon-wrap">${icon}</div>
  <div>
    <p class="role-label">User Role</p>
    <h1 class="role-name">${role}</h1>
    <p class="role-tagline">${tagline}</p>
  </div>
</div>`;

const section = (title) =>
    `<h2 class="section-title">${title}</h2>`;

const permRow = (feature, admin, manager, hr, siteHead, staff) => {
    const tick = (v) => v === '✓' ? `<td class="perm yes">✓</td>`
                      : v === '—' ? `<td class="perm no">—</td>`
                      : `<td class="perm partial">${v}</td>`;
    return `<tr><td class="perm-feature">${feature}</td>${tick(admin)}${tick(manager)}${tick(hr)}${tick(siteHead)}${tick(staff)}</tr>`;
};

// ─────────────────────────────────────────────────────────────────────────────
// CSS
// ─────────────────────────────────────────────────────────────────────────────

const CSS = `
@page { size: A4; margin: 0; }
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body {
  font-family: -apple-system, 'Segoe UI', Arial, sans-serif;
  font-size: 9.5pt;
  line-height: 1.6;
  color: #1e1e2e;
  background: #fff;
}

/* ── COVER ── */
.cover {
  width: 210mm; height: 297mm;
  background: #2B2D42;
  display: flex; flex-direction: column;
  page-break-after: always;
  position: relative; overflow: hidden;
}
.cover-bg-circle {
  position: absolute; border-radius: 50%;
  background: rgba(239,35,60,0.08);
}
.cover-accent { position: absolute; top: 0; left: 0; right: 0; height: 6px; background: #EF233C; }
.cover-body {
  flex: 1; display: flex; flex-direction: column;
  justify-content: center; padding: 60px 64px;
}
.cover-label {
  font-size: 8pt; font-weight: 700; letter-spacing: 0.2em;
  text-transform: uppercase; color: #EF233C; margin-bottom: 24px;
}
.cover-title {
  font-size: 40pt; font-weight: 800; line-height: 1.1;
  color: #fff; margin-bottom: 12px;
}
.cover-sub {
  font-size: 13pt; color: rgba(255,255,255,0.5); margin-bottom: 48px;
}
.cover-divider { width: 56px; height: 3px; background: #EF233C; border-radius: 2px; margin-bottom: 40px; }
.cover-roles {
  display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 60px;
}
.cover-role-pill {
  font-size: 8pt; font-weight: 600; padding: 5px 14px;
  border-radius: 20px; border: 1px solid;
}
.cover-footer {
  padding: 20px 64px;
  border-top: 1px solid rgba(255,255,255,0.1);
  display: flex; justify-content: space-between; align-items: center;
}
.cover-footer p { font-size: 8pt; color: rgba(255,255,255,0.35); }

/* ── TOC PAGE ── */
.toc-page {
  width: 210mm; min-height: 297mm;
  padding: 56px 64px;
  page-break-after: always;
  page-break-inside: avoid;
}
.toc-header { margin-bottom: 36px; }
.toc-header h2 { font-size: 20pt; font-weight: 800; color: #2B2D42; }
.toc-header p { font-size: 9pt; color: #888; margin-top: 4px; }
.toc-section { margin-bottom: 28px; }
.toc-section-title {
  font-size: 7.5pt; font-weight: 700; letter-spacing: 0.15em;
  text-transform: uppercase; color: #EF233C; margin-bottom: 10px;
  padding-bottom: 6px; border-bottom: 1px solid #f0e0e3;
}
.toc-row {
  display: flex; align-items: center;
  padding: 6px 0; border-bottom: 1px dotted #e8e8e8;
}
.toc-icon { font-size: 12pt; width: 28px; flex-shrink: 0; }
.toc-text { flex: 1; font-size: 9.5pt; font-weight: 500; color: #333; }
.toc-role-pill {
  font-size: 7pt; font-weight: 700; padding: 2px 8px;
  border-radius: 10px; margin-right: 8px;
}
.toc-roles-strip {
  display: flex; gap: 6px; margin-top: 6px;
  flex-wrap: wrap;
}

/* ── OVERVIEW PAGE ── */
.overview-page {
  width: 210mm; min-height: 297mm;
  padding: 56px 64px;
  page-break-after: always;
}
.overview-page h2 { font-size: 20pt; font-weight: 800; color: #2B2D42; margin-bottom: 6px; }
.overview-page .sub { font-size: 9pt; color: #888; margin-bottom: 28px; }

.perm-table { width: 100%; border-collapse: collapse; font-size: 8.5pt; }
.perm-table thead tr { background: #2B2D42; }
.perm-table thead th { padding: 9px 8px; color: #fff; font-weight: 700; text-align: center; }
.perm-table thead th:first-child { text-align: left; padding-left: 14px; }
.perm-table tbody tr:nth-child(even) { background: #f9f9fc; }
.perm-feature { padding: 7px 8px 7px 14px; font-weight: 500; color: #333; border-bottom: 1px solid #eee; }
td.perm { text-align: center; border-bottom: 1px solid #eee; font-weight: 700; font-size: 9pt; }
td.perm.yes { color: #16a34a; }
td.perm.no  { color: #ccc; }
td.perm.partial { color: #d97706; font-size: 7.5pt; }

.perm-header-admin   { background: #EF233C !important; }
.perm-header-manager { background: #7C3AED !important; }
.perm-header-hr      { background: #2563EB !important; }
.perm-header-sh      { background: #059669 !important; }
.perm-header-staff   { background: #D97706 !important; }

/* ── ROLE PAGES ── */
.role-page {
  width: 210mm; min-height: 297mm;
  page-break-before: always;
}

.role-header {
  padding: 36px 48px 32px;
  display: flex; align-items: center; gap: 24px;
  color: #fff;
}
.role-icon-wrap {
  width: 64px; height: 64px; border-radius: 16px;
  background: rgba(255,255,255,0.15);
  display: flex; align-items: center; justify-content: center;
  font-size: 28pt; flex-shrink: 0;
}
.role-label { font-size: 7.5pt; font-weight: 700; text-transform: uppercase; letter-spacing: 0.15em; opacity: 0.7; }
.role-name  { font-size: 22pt; font-weight: 800; line-height: 1; margin: 4px 0; }
.role-tagline { font-size: 9.5pt; opacity: 0.8; }

.role-body { padding: 28px 48px 40px; }

.section-title {
  font-size: 8pt; font-weight: 800; letter-spacing: 0.15em;
  text-transform: uppercase; color: #888;
  margin: 24px 0 12px; padding-bottom: 6px;
  border-bottom: 1px solid #eee;
}

/* ── FEATURE CARDS ── */
.feat-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 16px; }
.feat-card {
  display: flex; gap: 10px; align-items: flex-start;
  background: #f9f9fc; border: 1px solid #eee;
  border-radius: 8px; padding: 11px 12px;
}
.feat-icon { font-size: 16pt; flex-shrink: 0; line-height: 1.2; }
.feat-title { font-size: 9pt; font-weight: 700; color: #2B2D42; margin-bottom: 4px; }
.feat-body ul { padding-left: 14px; margin: 0; }
.feat-body ul li { font-size: 8.5pt; color: #555; margin-bottom: 2px; line-height: 1.5; }

/* ── CHIPS ── */
.chip-row { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 12px; }
.chip {
  display: inline-flex; align-items: center; gap: 5px;
  background: #f0f0f8; border: 1px solid #e0e0ee;
  border-radius: 20px; padding: 4px 11px;
  font-size: 8pt; font-weight: 500; color: #333;
}
.chip-icon { font-size: 10pt; }

/* ── STEPS ── */
.steps { margin: 10px 0 16px; }
.step {
  display: flex; align-items: flex-start; gap: 12px;
  padding: 8px 0; border-bottom: 1px solid #f0f0f8;
}
.step:last-child { border: none; }
.step-n {
  width: 24px; height: 24px; border-radius: 50%;
  background: #EF233C; color: #fff;
  display: flex; align-items: center; justify-content: center;
  font-size: 8pt; font-weight: 700; flex-shrink: 0; margin-top: 1px;
}
.step-text { font-size: 9pt; color: #444; flex: 1; }
.step-text strong { color: #1e1e2e; }

/* ── TIP BOX ── */
.tip-box {
  display: flex; align-items: flex-start; gap: 12px;
  border: 1px solid; border-radius: 8px; padding: 10px 14px;
  margin: 10px 0;
}
.tip-box p { font-size: 8.5pt; line-height: 1.5; }

/* ── PAGE FOOTER ── */
.page-footer {
  position: fixed; bottom: 0; left: 0; right: 0; height: 28px;
  background: #2B2D42;
  display: flex; align-items: center; justify-content: space-between;
  padding: 0 48px;
}
.page-footer span { font-size: 7.5pt; color: rgba(255,255,255,0.4); }
`;

// ─────────────────────────────────────────────────────────────────────────────
// CONTENT PAGES
// ─────────────────────────────────────────────────────────────────────────────

const COVER = `
<div class="cover">
  <div class="cover-accent"></div>
  <div class="cover-bg-circle" style="width:400px;height:400px;bottom:-100px;right:-100px;"></div>
  <div class="cover-bg-circle" style="width:200px;height:200px;top:60px;right:180px;"></div>

  <div class="cover-body">
    <p class="cover-label">Staff Portal</p>
    <h1 class="cover-title">User<br>Manual</h1>
    <p class="cover-sub">Complete guide for all user roles</p>
    <div class="cover-divider"></div>

    <div class="cover-roles">
      <div class="cover-role-pill" style="color:#EF233C;border-color:#EF233C40;background:#EF233C15">👑 Admin</div>
      <div class="cover-role-pill" style="color:#A78BFA;border-color:#A78BFA40;background:#A78BFA15">🛠 Manager</div>
      <div class="cover-role-pill" style="color:#60A5FA;border-color:#60A5FA40;background:#60A5FA15">👥 HR</div>
      <div class="cover-role-pill" style="color:#34D399;border-color:#34D39940;background:#34D39915">🏗 Site Head</div>
      <div class="cover-role-pill" style="color:#FCD34D;border-color:#FCD34D40;background:#FCD34D15">👷 Staff</div>
    </div>
  </div>

  <div class="cover-footer">
    <p>Version: May 2026</p>
    <p>Confidential — Internal Use Only</p>
  </div>
</div>`;

const TOC = `
<div class="toc-page">
  <div class="toc-header">
    <h2>Table of Contents</h2>
    <p>Navigate to the section for your role or feature area</p>
  </div>

  <div class="toc-section">
    <p class="toc-section-title">Getting Started</p>
    <div class="toc-row"><div class="toc-icon">🔐</div><div class="toc-text">Logging In &amp; First-Time Setup</div></div>
    <div class="toc-row"><div class="toc-icon">📊</div><div class="toc-text">Permissions Overview Table</div></div>
  </div>

  <div class="toc-section">
    <p class="toc-section-title">Role Guides</p>
    <div class="toc-row">
      <div class="toc-icon">👑</div>
      <div class="toc-text"><strong>Admin</strong> — Full system access</div>
      <div class="toc-role-pill" style="background:#EF233C20;color:#EF233C">Admin</div>
    </div>
    <div class="toc-row">
      <div class="toc-icon">🛠</div>
      <div class="toc-text"><strong>Manager</strong> — Day-to-day operations management</div>
      <div class="toc-role-pill" style="background:#7C3AED20;color:#7C3AED">Manager</div>
    </div>
    <div class="toc-row">
      <div class="toc-icon">👥</div>
      <div class="toc-text"><strong>HR</strong> — People management &amp; payroll</div>
      <div class="toc-role-pill" style="background:#2563EB20;color:#2563EB">HR</div>
    </div>
    <div class="toc-row">
      <div class="toc-icon">🏗</div>
      <div class="toc-text"><strong>Site Head</strong> — Project-scoped job management</div>
      <div class="toc-role-pill" style="background:#05966920;color:#059669">Site Head</div>
    </div>
    <div class="toc-row">
      <div class="toc-icon">👷</div>
      <div class="toc-text"><strong>Staff</strong> — Self-service features</div>
      <div class="toc-role-pill" style="background:#D9770620;color:#D97706">Staff</div>
    </div>
  </div>

  <div class="toc-section">
    <p class="toc-section-title">Key Features</p>
    <div class="toc-row"><div class="toc-icon">📋</div><div class="toc-text">Live Board (Jobs)</div></div>
    <div class="toc-row"><div class="toc-icon">📁</div><div class="toc-text">Projects &amp; Checklists</div></div>
    <div class="toc-row"><div class="toc-icon">⏰</div><div class="toc-text">Attendance &amp; Clock-In</div></div>
    <div class="toc-row"><div class="toc-icon">🌴</div><div class="toc-text">Leave Management</div></div>
    <div class="toc-row"><div class="toc-icon">🚐</div><div class="toc-text">Fleet &amp; Vans</div></div>
    <div class="toc-row"><div class="toc-icon">💰</div><div class="toc-text">Payroll &amp; Payslips</div></div>
    <div class="toc-row"><div class="toc-icon">🎓</div><div class="toc-text">Training</div></div>
    <div class="toc-row"><div class="toc-icon">🔗</div><div class="toc-text">BCF &amp; BGR Integrations</div></div>
  </div>
</div>`;

const OVERVIEW = `
<div class="overview-page">
  <h2>Permissions Overview</h2>
  <p class="sub">Quick reference — what each role can do across the portal</p>

  <table class="perm-table">
    <thead>
      <tr>
        <th style="text-align:left;padding-left:14px">Feature</th>
        <th class="perm-header-admin">Admin</th>
        <th class="perm-header-manager">Manager</th>
        <th class="perm-header-hr">HR</th>
        <th class="perm-header-sh">Site Head</th>
        <th class="perm-header-staff">Staff</th>
      </tr>
    </thead>
    <tbody>
      ${permRow('Dashboard', '✓', '✓', '✓', '✓', '✓')}
      ${permRow('Live Board — view', '✓', '✓', '—', 'Own projects', 'Own jobs')}
      ${permRow('Live Board — create &amp; edit jobs', '✓', '✓', '—', 'Own projects', '—')}
      ${permRow('Live Board — delete jobs', '✓', '✓', '—', '—', '—')}
      ${permRow('Projects — view', '✓', '✓', '—', 'Own', '—')}
      ${permRow('Projects — create / edit / delete', '✓', '✓', '—', '—', '—')}
      ${permRow('Attendance — clock in / out', '✓', '✓', '✓', '✓', '✓')}
      ${permRow('Attendance — view all records', '✓', '✓', '✓', '✓', '—')}
      ${permRow('Attendance — approve &amp; manual entry', '✓', '✓', '✓', '—', '—')}
      ${permRow('Leave — request own', '✓', '✓', '✓', '✓', '✓')}
      ${permRow('Leave — approve / reject', '✓', '✓', '—', '—', '—')}
      ${permRow('Vans — view', '✓', '✓', '—', '✓', '—')}
      ${permRow('Vans — manage &amp; assign', '✓', '✓', '—', '—', '—')}
      ${permRow('Staff — view list', '✓', '✓', '✓', '✓', '—')}
      ${permRow('Staff — create / edit / deactivate', '✓', '✓', '✓', '—', '—')}
      ${permRow('Overtime — submit own', '✓', '✓', '✓', '✓', '✓')}
      ${permRow('Overtime — approve / reject', '✓', '✓', '✓', '—', '—')}
      ${permRow('Subcontractors', '✓ full', '✓ full', 'View', 'View', '—')}
      ${permRow('Payroll runs', '✓', '✓', '✓', '—', 'My payslip')}
      ${permRow('Training — view enrolled', '✓', '✓', '✓', '✓', '✓')}
      ${permRow('Training — manage modules', '✓', '✓', '—', '—', '—')}
      ${permRow('BCF Orders', '✓ all', '✓ all', '✓ all', '—', '—')}
      ${permRow('BGR Projects', 'Own*', 'Own*', '—', 'Own*', '—')}
      ${permRow('Audit Log', '✓', '✓', '—', '—', '—')}
      ${permRow('Reports &amp; Export', '✓', '✓', '—', '—', '—')}
      ${permRow('Settings &amp; Businesses', '✓', '✓', '—', '—', '—')}
    </tbody>
  </table>

  <div style="margin-top:14px">
    ${tipBox('ℹ️', '* BGR requires each user to connect their own BGR account (email &amp; password) under Client Projects. Once connected, they see their assigned projects only.', '#3B82F6')}
  </div>
</div>`;

// ── GETTING STARTED ───────────────────────────────────────────────────────────
const GETTING_STARTED = `
<div class="role-page">
  <div class="role-header" style="background:linear-gradient(135deg,#2B2D42 0%,#1a1a2e 100%)">
    <div class="role-icon-wrap">🔐</div>
    <div>
      <p class="role-label">Chapter 1</p>
      <h1 class="role-name">Getting Started</h1>
      <p class="role-tagline">Logging in and navigating the portal for the first time</p>
    </div>
  </div>
  <div class="role-body">

    ${section('How to Log In')}
    <div class="steps">
      ${step(1, 'Open the portal in your browser and go to the login page.')}
      ${step(2, 'Enter your <strong>email address</strong> and <strong>password</strong> provided by your admin.')}
      ${step(3, 'Click <strong>Sign In</strong>. You will be taken to your Dashboard.')}
      ${step(4, '<strong>First-time login:</strong> you will be prompted to set a new password before continuing.')}
    </div>

    ${tipBox('💡', 'If you forget your password, ask your Admin or Manager to send you a reset link from Staff Management.', '#D97706')}

    ${section('The Navigation Sidebar')}
    <div class="feat-grid">
      ${featureCard('🏠', 'Dashboard', ['Your personal overview — jobs today, attendance status, notifications'])}
      ${featureCard('📋', 'Live Board', ['All scheduled jobs for a given date', 'Navigate dates using the arrow buttons'])}
      ${featureCard('📁', 'Projects', ['Internal project list with checklist tracking', 'Visible to Admin, Manager, Site Head'])}
      ${featureCard('⏰', 'Attendance', ['Clock in and out, view history, manage breaks'])}
      ${featureCard('🌴', 'Leave', ['Submit leave requests, view your balance and history'])}
      ${featureCard('🔗', 'Client Projects', ['BGR and BCF integrations for client-facing work'])}
    </div>

    ${section('Notifications')}
    <p style="font-size:9pt;color:#555;margin-bottom:8px">The bell icon (top-right) shows unread alerts — job assignments, leave status changes, and more.</p>
    <div class="chip-row">
      ${chip('🔔', 'Job assigned to you')}
      ${chip('✅', 'Leave approved / rejected')}
      ${chip('⏰', 'Attendance reminder')}
    </div>

    ${section('Your Profile')}
    <div class="feat-grid">
      ${featureCard('🖼', 'Avatar', ['Upload a profile photo from Profile → Edit'])}
      ${featureCard('🔑', 'Password', ['Change your password anytime under Profile → Change Password'])}
    </div>
  </div>
</div>`;

// ── ADMIN ─────────────────────────────────────────────────────────────────────
const ADMIN = `
<div class="role-page">
  ${roleHeader('Admin', 'Full access to every feature in the portal', '#EF233C', '👑')}
  <div class="role-body">

    ${tipBox('👑', 'Admins have unrestricted access to all features. Use this power carefully — changes like deactivating staff or deleting jobs are permanent.', '#EF233C')}

    ${section('Dashboard & Calendar')}
    <div class="feat-grid">
      ${featureCard('📊', 'Dashboard', ['Today\'s jobs overview', 'Pending leave & attendance counts', 'Recent activity feed'])}
      ${featureCard('📅', 'Calendar', ['Monthly view of all scheduled jobs and staff shifts'])}
    </div>

    ${section('Live Board — Jobs')}
    <div class="feat-grid">
      ${featureCard('➕', 'Create Jobs', ['Set title, date, start/end time', 'Assign crew members and a van', 'Link to a project, BCF order, or BGR stage'])}
      ${featureCard('✏️', 'Edit & Delete Jobs', ['Modify any field at any time', 'Only Admin and Manager can delete jobs'])}
      ${featureCard('🔄', 'Status Management', ['Scheduled → In Progress → Completed / Cancelled', 'Completing a BCF-linked job auto-marks the BCF stage as Done'])}
      ${featureCard('🔗', 'Client Linking', ['Link a job to a BCF order stage', 'Link a job to a BGR project stage', 'Linked jobs appear as coloured pills in the client portals'])}
    </div>

    ${section('Projects')}
    <div class="feat-grid">
      ${featureCard('📁', 'Create & Manage', ['Create projects with name, customer, business (BCF/BGR)', 'Set status: Planning, Active, On Hold, Completed'])}
      ${featureCard('✅', 'Checklists', ['Add checklist items per project', 'Items auto-created when a job is linked to a project', 'Toggle items as complete/incomplete'])}
    </div>

    ${section('Staff Management')}
    <div class="feat-grid">
      ${featureCard('👤', 'Add & Edit Staff', ['Create accounts with name, email, role, pay details', 'Upload avatar, set employment dates'])}
      ${featureCard('🔒', 'Access Control', ['Deactivate / reactivate accounts', 'Force password reset via email link'])}
      ${featureCard('📄', 'Onboarding Docs', ['Upload and manage onboarding documents per staff member'])}
      ${featureCard('🔗', 'Account Linking', ['Link staff to BCF worker account', 'Connect BGR account for client project access'])}
    </div>

    ${section('Attendance, Leave & Overtime')}
    <div class="feat-grid">
      ${featureCard('⏰', 'Attendance', ['View all staff records', 'Approve, reject, or add manual entries', 'Bulk approve pending entries', 'Export to Excel'])}
      ${featureCard('🌴', 'Leave', ['Approve or reject any request', 'View leave balance for any staff member', 'System warns if a job is on an approved leave date'])}
      ${featureCard('⬆️', 'Overtime', ['Approve or reject overtime requests', 'View all submissions across all staff'])}
      ${featureCard('📱', 'QR Clock-In', ['Scan any staff member\'s QR code at the site to clock them in/out', 'View your own QR at /my-qr'])}
    </div>

    ${section('Fleet, Payroll & More')}
    <div class="feat-grid">
      ${featureCard('🚐', 'Vans', ['Add/edit vans, activate/deactivate', 'Assign a driver and record notes', 'View full assignment history'])}
      ${featureCard('💰', 'Payroll', ['Create payroll runs, approve individually or in bulk', 'Export data to Excel', 'Set payroll cutoff date'])}
      ${featureCard('🎓', 'Training', ['Create modules and lessons', 'Manage enrollments per staff member', 'Track completion progress'])}
      ${featureCard('🏢', 'Businesses', ['Create and manage business entities (BCF, BGR)', 'Activate/deactivate'])}
    </div>

    ${section('Audit & Reports')}
    <div class="feat-grid">
      ${featureCard('📜', 'Audit Log', ['Full trail of all portal changes — who changed what and when'])}
      ${featureCard('📈', 'Reports', ['Aggregated attendance and payroll reports', 'Export payroll data to Excel'])}
    </div>
  </div>
</div>`;

// ── MANAGER ───────────────────────────────────────────────────────────────────
const MANAGER = `
<div class="role-page">
  ${roleHeader('Manager', 'Operational control across jobs, staff, and attendance', '#7C3AED', '🛠')}
  <div class="role-body">

    ${tipBox('🛠', 'Managers have the same day-to-day access as Admins. The only difference is that Managers cannot permanently force-delete records.', '#7C3AED')}

    ${section('What Managers Can Do')}
    <div class="feat-grid">
      ${featureCard('📋', 'Live Board', ['Create, edit, and delete jobs', 'Assign crew, vans, and client links', 'Change job status and manage the daily schedule'])}
      ${featureCard('📁', 'Projects', ['Create and edit projects', 'Manage checklists and project status', 'Assign site heads and staff to projects'])}
      ${featureCard('⏰', 'Attendance', ['View all staff records', 'Approve, reject, and manually add entries', 'Bulk approve and export to Excel'])}
      ${featureCard('🌴', 'Leave', ['Approve or reject any leave request', 'View leave balance summary per staff'])}
      ${featureCard('⬆️', 'Overtime', ['Review and approve/reject overtime submissions'])}
      ${featureCard('🚐', 'Vans', ['Add and manage fleet vehicles', 'Assign and return drivers', 'View allocation history'])}
      ${featureCard('👥', 'Staff', ['Add and edit staff accounts', 'Deactivate accounts, force password reset', 'Manage onboarding documents'])}
      ${featureCard('💰', 'Payroll', ['Create, approve, and export payroll runs', 'View all staff payslips'])}
      ${featureCard('🎓', 'Training', ['Create training modules and lessons', 'Manage staff enrollments and track progress'])}
      ${featureCard('🔗', 'BCF & BGR', ['Full access to all BCF orders', 'Connect BGR account for client projects', 'Link jobs to client stages'])}
      ${featureCard('📜', 'Audit & Reports', ['View audit log and generate reports', 'Export payroll and attendance data'])}
      ${featureCard('⚙️', 'Settings', ['Update company-wide portal settings and preferences'])}
    </div>

  </div>
</div>`;

// ── HR ────────────────────────────────────────────────────────────────────────
const HR = `
<div class="role-page">
  ${roleHeader('HR', 'People management, attendance, overtime and payroll', '#2563EB', '👥')}
  <div class="role-body">

    ${tipBox('ℹ️', 'HR manages staff records and attendance but does not have access to projects, jobs, vans, or leave approval. Leave must be approved by an Admin or Manager.', '#2563EB')}

    ${section('Staff Management')}
    <div class="feat-grid">
      ${featureCard('👤', 'Add New Staff', ['Create staff accounts: name, email, role, pay grade, contract type', 'Upload profile avatar'])}
      ${featureCard('✏️', 'Edit Staff Details', ['Update personal info, role, bank details, contact information'])}
      ${featureCard('🔒', 'Account Control', ['Deactivate and reactivate staff accounts', 'Force password reset for any staff member'])}
      ${featureCard('📄', 'Onboarding', ['Upload, view, and delete onboarding documents per staff member'])}
    </div>

    ${section('Attendance')}
    <div class="feat-grid">
      ${featureCard('👁', 'View All Records', ['See every staff member\'s clock-in/out history', 'Filter by date range, status, or individual'])}
      ${featureCard('✅', 'Approve & Reject', ['Approve or reject individual time entries', 'Bulk approve all pending entries at once'])}
      ${featureCard('➕', 'Manual Entry', ['Add a time entry on behalf of a staff member (e.g. forgotten clock-in)'])}
      ${featureCard('📤', 'Export', ['Export attendance data to Excel for payroll or reporting'])}
    </div>

    ${section('Overtime & Payroll')}
    <div class="feat-grid">
      ${featureCard('⬆️', 'Overtime Review', ['View all overtime requests', 'Approve or reject submissions'])}
      ${featureCard('💰', 'Payroll Runs', ['Create payroll runs for a date range', 'Approve payroll entries', 'View payslips for all staff'])}
    </div>

    ${section('Self-Service')}
    <div class="feat-grid">
      ${featureCard('🌴', 'Leave', ['Submit and track your own leave requests', 'View all staff leave (read-only — approval by Admin/Manager)'])}
      ${featureCard('⏰', 'Attendance', ['Clock in and out for yourself', 'Manage your own breaks'])}
      ${featureCard('📱', 'QR Code', ['View your personal QR at /my-qr for on-site clock-in'])}
      ${featureCard('🔗', 'BCF Orders', ['View all BCF client orders (full access, unfiltered)'])}
    </div>

  </div>
</div>`;

// ── SITE HEAD ─────────────────────────────────────────────────────────────────
const SITE_HEAD = `
<div class="role-page">
  ${roleHeader('Site Head', 'Manage jobs and oversee staff on your assigned projects', '#059669', '🏗')}
  <div class="role-body">

    ${tipBox('🏗', 'Site Heads only see jobs and projects they are assigned to. They cannot delete jobs, manage payroll, approve leave, or access HR records.', '#059669')}

    ${section('Live Board — Your Projects')}
    <div class="feat-grid">
      ${featureCard('👁', 'View Jobs', ['See all jobs linked to your assigned projects', 'Navigate dates with the arrow buttons'])}
      ${featureCard('➕', 'Create Jobs', ['Add jobs for your projects: title, date, time, crew, van', 'Link to a BCF order or BGR project stage'])}
      ${featureCard('✏️', 'Edit Jobs', ['Modify any field on jobs belonging to your projects'])}
      ${featureCard('🔄', 'Update Status', ['Change job status: Scheduled → In Progress → Completed / Cancelled', 'Cannot delete jobs — ask a Manager'])}
    </div>

    ${section('Projects (Read-Only)')}
    <div class="feat-grid">
      ${featureCard('📁', 'View Your Projects', ['See project details, dates, customer, and business type', 'View the project checklist (items synced from jobs)'])}
      ${featureCard('🚫', 'Cannot Create/Edit', ['Project creation and editing is restricted to Admin and Manager', 'Contact your manager to make project changes'])}
    </div>

    ${section('Staff & Vans')}
    <div class="feat-grid">
      ${featureCard('👥', 'View Staff', ['Browse the full staff list and individual profiles (read-only)'])}
      ${featureCard('🚐', 'View Vans', ['See all active vans and current assigned drivers (read-only)'])}
    </div>

    ${section('Attendance')}
    <div class="feat-grid">
      ${featureCard('👁', 'View Records', ['See all staff attendance records across the company'])}
      ${featureCard('⏰', 'Clock In/Out', ['Clock in and out for yourself', 'Start and end your breaks'])}
    </div>

    ${section('Self-Service')}
    <div class="feat-grid">
      ${featureCard('🌴', 'Leave', ['Submit leave requests and view your own history and balance'])}
      ${featureCard('⬆️', 'Overtime', ['Submit overtime requests for additional hours worked'])}
      ${featureCard('🎓', 'Training', ['View and complete your enrolled training modules'])}
      ${featureCard('🔗', 'BGR Projects', ['Connect your BGR account to view and manage your assigned client projects', 'Complete tasks, post updates, and upload photos'])}
    </div>

  </div>
</div>`;

// ── STAFF ─────────────────────────────────────────────────────────────────────
const STAFF = `
<div class="role-page">
  ${roleHeader('Staff', 'Self-service access to your jobs, attendance, leave and training', '#D97706', '👷')}
  <div class="role-body">

    ${tipBox('👷', 'Staff have self-service access only. You can view your own jobs, manage your attendance, request leave, track training, and view your payslip.', '#D97706')}

    ${section('Your Daily Workflow')}
    <div class="steps">
      ${step(1, 'Check <strong>Dashboard</strong> or <strong>Live Board</strong> to see your jobs for today.')}
      ${step(2, 'When you arrive on site, tap <strong>Clock In</strong> on the Attendance page (or use your QR code).')}
      ${step(3, 'Use <strong>Start Break / End Break</strong> buttons during your shift.')}
      ${step(4, '<strong>Clock Out</strong> when your shift ends. Your entry is sent for approval.')}
    </div>

    ${section('Live Board')}
    ${featureCard('📋', 'Your Jobs', ['View all jobs you are assigned to for any date', 'Navigate dates using the left/right arrows', 'Cannot create, edit, or change job status — speak to your site head or manager'])}

    ${section('Attendance')}
    <div class="feat-grid">
      ${featureCard('⏰', 'Clock In/Out', ['Tap Clock In when you start your shift', 'Tap Clock Out when you finish'])}
      ${featureCard('☕', 'Breaks', ['Start Break when you take a break', 'End Break when you return', 'Break time is deducted from your total hours'])}
      ${featureCard('📱', 'QR Code', ['Go to /my-qr to display your personal QR code', 'A supervisor can scan it to clock you in/out on-site'])}
      ${featureCard('📂', 'History', ['View your own past attendance entries and approved hours'])}
    </div>

    ${section('Leave')}
    <div class="feat-grid">
      ${featureCard('➕', 'Request Leave', ['Go to Leave and click New Request', 'Select type: Annual, Sick, Unpaid, Other', 'Choose dates and add a reason'])}
      ${featureCard('📊', 'Your Balance', ['View remaining annual leave days', 'See all past requests and their status (Pending/Approved/Rejected)'])}
    </div>

    ${section('Overtime')}
    ${featureCard('⬆️', 'Submit Overtime', ['Go to Overtime and click New Request', 'Enter the date, hours worked, and reason', 'Your manager will approve or reject it'])}

    ${section('Training')}
    <div class="feat-grid">
      ${featureCard('🎓', 'Your Modules', ['View training modules you are enrolled in', 'Progress through lessons at your own pace'])}
      ${featureCard('▶️', 'Watch Lessons', ['Click a lesson to watch the video', 'Your progress is saved automatically'])}
    </div>

    ${section('Payslip')}
    ${featureCard('💰', 'My Payslip', ['Go to /my-payslip to view your most recent payslip', 'Download or print from the browser'])}

    ${section('Profile')}
    <div class="feat-grid">
      ${featureCard('🖼', 'Avatar', ['Upload a profile photo from Profile → Edit'])}
      ${featureCard('🔑', 'Password', ['Change your password anytime under Profile → Change Password'])}
    </div>

  </div>
</div>`;

// ─────────────────────────────────────────────────────────────────────────────
// ASSEMBLE HTML
// ─────────────────────────────────────────────────────────────────────────────

const HTML = `<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<title>Staff Portal — User Manual</title>
<style>${CSS}</style>
</head>
<body>
${COVER}
${TOC}
${OVERVIEW}
${GETTING_STARTED}
${ADMIN}
${MANAGER}
${HR}
${SITE_HEAD}
${STAFF}

<div class="page-footer">
  <span>Staff Portal — User Manual</span>
  <span>Confidential · Internal Use Only · May 2026</span>
</div>
</body>
</html>`;

// ─────────────────────────────────────────────────────────────────────────────
// RENDER
// ─────────────────────────────────────────────────────────────────────────────

const browser = await puppeteer.launch({
    executablePath,
    headless: true,
    args: ['--no-sandbox', '--disable-setuid-sandbox'],
});

const page = await browser.newPage();
await page.setContent(HTML, { waitUntil: 'networkidle0', timeout: 30000 });

await page.pdf({
    path: out,
    format: 'A4',
    printBackground: true,
    margin: { top: '0', right: '0', bottom: '32px', left: '0' },
    displayHeaderFooter: false,
});

await browser.close();
console.log('PDF saved to:', out);
