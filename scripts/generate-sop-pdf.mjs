/**
 * BCF Staff Portal — Standard Operating Procedure (SOP) PDF Generator
 * Run: node scripts/generate-sop-pdf.mjs
 * Output: docs/sop.pdf
 */

import { existsSync, mkdirSync } from 'fs';
import { resolve, dirname } from 'path';
import { fileURLToPath } from 'url';
import puppeteer from 'puppeteer-core';

const __dir = dirname(fileURLToPath(import.meta.url));
const root  = resolve(__dir, '..');
const out   = resolve(root, 'docs/sop.pdf');

mkdirSync(resolve(root, 'docs'), { recursive: true });

const BROWSER_PATHS = [
    'C:/Program Files/Google/Chrome/Application/chrome.exe',
    'C:/Program Files (x86)/Google/Chrome/Application/chrome.exe',
    'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe',
    'C:/Program Files/Microsoft/Edge/Application/msedge.exe',
    '/usr/bin/google-chrome',
    '/usr/bin/chromium-browser',
    '/usr/bin/chromium',
];
const executablePath = BROWSER_PATHS.find(p => existsSync(p));
if (!executablePath) { console.error('No Chrome/Edge found. Install Chrome or set CHROME_PATH.'); process.exit(1); }
console.log('Browser:', executablePath);

// ─── COLOURS ──────────────────────────────────────────────────────────────────
const RED    = '#EF233C';
const DARK   = '#2B2D42';
const GREY   = '#8D99AE';
const LIGHT  = '#EDF2F4';
const GREEN  = '#16A34A';
const AMBER  = '#D97706';
const BLUE   = '#2563EB';
const PURPLE = '#7C3AED';

// ─── HELPERS ──────────────────────────────────────────────────────────────────

const esc = s => String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');

const pageBreak = () => `<div style="page-break-after:always"></div>`;

const cover = () => `
<div style="page-break-after:always; min-height:100vh; background:${DARK}; display:flex; flex-direction:column; padding:0;">
  <!-- Top accent -->
  <div style="height:8px; background:${RED}; width:100%;"></div>

  <!-- Logo area -->
  <div style="padding:48px 60px 0; display:flex; align-items:center; gap:16px;">
    <div style="width:48px;height:48px;background:${RED};border-radius:12px;display:flex;align-items:center;justify-content:center;">
      <div style="width:24px;height:24px;background:white;border-radius:6px;"></div>
    </div>
    <div>
      <p style="color:white;font-size:11pt;font-weight:700;letter-spacing:1px;text-transform:uppercase;">Bespoke Garden Rooms Ballycastle</p>
      <p style="color:${GREY};font-size:9pt;">Staff Management Portal</p>
    </div>
  </div>

  <!-- Main title -->
  <div style="flex:1; display:flex; flex-direction:column; justify-content:center; padding:60px;">
    <p style="color:${RED};font-size:10pt;font-weight:700;letter-spacing:3px;text-transform:uppercase;margin-bottom:16px;">Standard Operating Procedure</p>
    <h1 style="color:white;font-size:32pt;font-weight:800;line-height:1.15;margin-bottom:12px;">Staff Portal<br>SOP Documentation</h1>
    <p style="color:${GREY};font-size:12pt;font-weight:400;max-width:400px;line-height:1.6;">Procedures, roles, and responsibilities for the BCF Staff Portal system.</p>

    <div style="margin-top:48px;display:flex;gap:48px;">
      <div>
        <p style="color:${GREY};font-size:8pt;font-weight:700;letter-spacing:1px;text-transform:uppercase;">Document No.</p>
        <p style="color:white;font-size:10pt;font-weight:600;margin-top:2px;">BCF-SOP-001</p>
      </div>
      <div>
        <p style="color:${GREY};font-size:8pt;font-weight:700;letter-spacing:1px;text-transform:uppercase;">Version</p>
        <p style="color:white;font-size:10pt;font-weight:600;margin-top:2px;">1.0</p>
      </div>
      <div>
        <p style="color:${GREY};font-size:8pt;font-weight:700;letter-spacing:1px;text-transform:uppercase;">Issue Date</p>
        <p style="color:white;font-size:10pt;font-weight:600;margin-top:2px;">May 2026</p>
      </div>
      <div>
        <p style="color:${GREY};font-size:8pt;font-weight:700;letter-spacing:1px;text-transform:uppercase;">Review Date</p>
        <p style="color:white;font-size:10pt;font-weight:600;margin-top:2px;">May 2027</p>
      </div>
    </div>
  </div>

  <!-- Doc control box -->
  <div style="margin:0 60px 60px; border:1px solid rgba(255,255,255,0.1); border-radius:12px; overflow:hidden;">
    <table style="width:100%; border-collapse:collapse;">
      <thead>
        <tr style="background:rgba(255,255,255,0.05);">
          <th style="padding:10px 16px;text-align:left;color:${GREY};font-size:8pt;letter-spacing:1px;text-transform:uppercase;font-weight:700;">Prepared By</th>
          <th style="padding:10px 16px;text-align:left;color:${GREY};font-size:8pt;letter-spacing:1px;text-transform:uppercase;font-weight:700;">Reviewed By</th>
          <th style="padding:10px 16px;text-align:left;color:${GREY};font-size:8pt;letter-spacing:1px;text-transform:uppercase;font-weight:700;">Approved By</th>
          <th style="padding:10px 16px;text-align:left;color:${GREY};font-size:8pt;letter-spacing:1px;text-transform:uppercase;font-weight:700;">Classification</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td style="padding:12px 16px;color:white;font-size:9pt;">System Administrator</td>
          <td style="padding:12px 16px;color:white;font-size:9pt;">Operations Manager</td>
          <td style="padding:12px 16px;color:white;font-size:9pt;">Director</td>
          <td style="padding:12px 16px;"><span style="background:${AMBER}20;color:${AMBER};border:1px solid ${AMBER}40;border-radius:20px;padding:3px 10px;font-size:8pt;font-weight:700;">INTERNAL</span></td>
        </tr>
      </tbody>
    </table>
  </div>

  <!-- Bottom bar -->
  <div style="height:4px; background:linear-gradient(to right,${RED},${PURPLE}); width:100%;"></div>
</div>`;

const toc = () => `
<div style="page-break-after:always; padding:60px;">
  <div style="display:flex;align-items:center;gap:12px;margin-bottom:32px;">
    <div style="width:4px;height:28px;background:${RED};border-radius:2px;"></div>
    <h2 style="font-size:18pt;font-weight:800;color:${DARK};">Table of Contents</h2>
  </div>

  ${tocSection('1', 'Introduction & Purpose', '3')}
  ${tocSection('2', 'Scope & Application', '3')}
  ${tocSection('3', 'Definitions & Glossary', '4')}
  ${tocSection('4', 'Roles & Responsibilities', '4')}
  ${tocSection('5', 'System Access & Security', '5')}
  ${tocSection('6', 'Attendance Management', '6')}
  ${tocSection('', '6.1  Clock-In Procedure', '6')}
  ${tocSection('', '6.2  Clock-Out Procedure', '6')}
  ${tocSection('', '6.3  Break & Lunch Procedure', '7')}
  ${tocSection('', '6.4  Overtime Clock-In', '7')}
  ${tocSection('', '6.5  Attendance Approval (Managers)', '7')}
  ${tocSection('', '6.6  Manual Time Entry', '8')}
  ${tocSection('7', 'Job Management', '8')}
  ${tocSection('', '7.1  Creating a Job', '8')}
  ${tocSection('', '7.2  Assigning Staff to a Job', '9')}
  ${tocSection('', '7.3  Job Status Updates', '9')}
  ${tocSection('', '7.4  BCF / BGR Client Integration', '9')}
  ${tocSection('8', 'Leave Management', '10')}
  ${tocSection('', '8.1  Submitting a Leave Request', '10')}
  ${tocSection('', '8.2  Leave Approval Process', '10')}
  ${tocSection('', '8.3  Leave Types & Entitlements', '10')}
  ${tocSection('9', 'Overtime Requests', '11')}
  ${tocSection('10', 'Payroll Processing', '11')}
  ${tocSection('', '10.1 Generating a Payroll Run', '11')}
  ${tocSection('', '10.2 Reviewing & Approving Payslips', '12')}
  ${tocSection('', '10.3 Deductions', '12')}
  ${tocSection('', '10.4 Payroll Export', '12')}
  ${tocSection('11', 'Staff Management', '13')}
  ${tocSection('12', 'Project Management', '13')}
  ${tocSection('13', 'Vehicle (Van) Management', '14')}
  ${tocSection('14', 'QR Code System', '14')}
  ${tocSection('15', 'Reporting & Audit', '15')}
  ${tocSection('16', 'Non-Compliance & Exceptions', '15')}
  <div style="margin-top:24px;border-top:1px solid #E5E7EB;padding-top:12px;">
    ${tocSection('A', 'Appendix A — Permissions Matrix', '16')}
    ${tocSection('B', 'Appendix B — Escalation & Support Contacts', '17')}
    ${tocSection('C', 'Appendix C — Revision History', '17')}
  </div>
</div>`;

const tocSection = (num, title, page) => `
<div style="display:flex;align-items:baseline;gap:4px;padding:4px 0;border-bottom:1px dotted #E5E7EB;">
  ${num ? `<span style="min-width:28px;font-size:9pt;font-weight:700;color:${RED};">${num}</span>` : `<span style="min-width:28px;"></span>`}
  <span style="flex:1;font-size:9.5pt;color:${DARK};padding-left:${num ? '0' : '16px'};">${title}</span>
  <span style="font-size:9pt;color:${GREY};font-weight:600;">${page}</span>
</div>`;

const sectionHead = (num, title, sub='') => `
<div style="margin:0 0 20px;padding-bottom:12px;border-bottom:2px solid ${LIGHT};">
  <div style="display:flex;align-items:center;gap:10px;">
    <div style="min-width:32px;height:32px;background:${RED};border-radius:8px;display:flex;align-items:center;justify-content:center;">
      <span style="color:white;font-size:9pt;font-weight:800;">${num}</span>
    </div>
    <h2 style="font-size:14pt;font-weight:800;color:${DARK};">${title}</h2>
  </div>
  ${sub ? `<p style="font-size:9.5pt;color:${GREY};margin-top:6px;padding-left:42px;">${sub}</p>` : ''}
</div>`;

const subHead = (num, title) => `
<h3 style="font-size:10.5pt;font-weight:700;color:${DARK};margin:20px 0 10px;padding:8px 12px;background:${LIGHT};border-radius:6px;border-left:3px solid ${RED};">
  <span style="color:${RED};margin-right:6px;">${num}</span>${title}
</h3>`;

const step = (n, text, sub='') => `
<div style="display:flex;gap:12px;margin-bottom:10px;align-items:flex-start;">
  <div style="min-width:24px;height:24px;background:${RED};color:white;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:8.5pt;font-weight:800;flex-shrink:0;margin-top:1px;">${n}</div>
  <div>
    <p style="font-size:9.5pt;color:${DARK};line-height:1.55;">${text}</p>
    ${sub ? `<p style="font-size:8.5pt;color:${GREY};margin-top:3px;">${sub}</p>` : ''}
  </div>
</div>`;

const note = (icon, text, color = BLUE) => `
<div style="display:flex;gap:10px;background:${color}0D;border:1px solid ${color}30;border-radius:8px;padding:12px 14px;margin:12px 0;">
  <span style="font-size:13pt;flex-shrink:0;">${icon}</span>
  <p style="font-size:8.5pt;color:${color === BLUE ? '#1D4ED8' : color === AMBER ? '#92400E' : color === RED ? '#991B1B' : '#166534'};line-height:1.6;">${text}</p>
</div>`;

const defRow = (term, def) => `
<tr>
  <td style="padding:8px 12px;font-size:9pt;font-weight:700;color:${DARK};white-space:nowrap;vertical-align:top;width:180px;">${term}</td>
  <td style="padding:8px 12px;font-size:9pt;color:#374151;line-height:1.6;border-left:2px solid ${LIGHT};">${def}</td>
</tr>`;

const permRow = (feature, admin, manager, hr, siteHead, staff) => {
    const cell = (v) => {
        if (v === '✓')  return `<td style="padding:8px;text-align:center;font-size:10pt;color:${GREEN};font-weight:700;">✓</td>`;
        if (v === '—')  return `<td style="padding:8px;text-align:center;font-size:10pt;color:#D1D5DB;">—</td>`;
        return `<td style="padding:8px;text-align:center;font-size:8pt;color:${AMBER};font-weight:600;">${v}</td>`;
    };
    return `<tr style="border-bottom:1px solid ${LIGHT};">
      <td style="padding:8px 12px;font-size:9pt;color:${DARK};">${feature}</td>
      ${cell(admin)}${cell(manager)}${cell(hr)}${cell(siteHead)}${cell(staff)}
    </tr>`;
};

const roleCard = (title, color, icon, items) => `
<div style="border:1px solid ${color}30;border-radius:10px;overflow:hidden;margin-bottom:12px;">
  <div style="background:${color};padding:10px 16px;display:flex;align-items:center;gap:10px;">
    <span style="font-size:14pt;">${icon}</span>
    <p style="color:white;font-size:10pt;font-weight:700;">${title}</p>
  </div>
  <ul style="padding:12px 16px 12px 32px;margin:0;">
    ${items.map(i => `<li style="font-size:9pt;color:#374151;margin-bottom:4px;line-height:1.5;">${i}</li>`).join('')}
  </ul>
</div>`;

// ─── HTML CONTENT ──────────────────────────────────────────────────────────────

const HTML = `<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>BCF Staff Portal — SOP</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
  @page { size: A4; margin: 18mm 18mm 22mm 18mm; }
  @page :first { margin: 0; }
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'Inter', Arial, sans-serif; font-size: 10pt; color: #374151; line-height: 1.6; background: white; }
  table { border-collapse: collapse; width: 100%; }
  p { margin-bottom: 8px; }
  ul, ol { padding-left: 20px; margin-bottom: 8px; }
  li { margin-bottom: 4px; font-size: 9.5pt; line-height: 1.55; }
  .page { padding: 0; }
  .running-header { display: flex; align-items: center; justify-content: space-between; padding: 8px 0; border-bottom: 2px solid ${LIGHT}; margin-bottom: 24px; }
  .rh-left  { font-size: 8pt; color: ${GREY}; font-weight: 600; }
  .rh-right { font-size: 8pt; color: ${RED}; font-weight: 700; }
  .footer   { margin-top: 32px; padding-top: 10px; border-top: 1px solid ${LIGHT}; display: flex; align-items: center; justify-content: space-between; }
  .ft-left  { font-size: 7.5pt; color: ${GREY}; }
  .ft-right { font-size: 7.5pt; color: ${GREY}; }
  th { text-align: left; }
</style>
</head>
<body>

${cover()}
${toc()}

<!-- ═══════════════════════════════════════════════════════════════════════
     SECTION 1 — Introduction & Purpose
════════════════════════════════════════════════════════════════════════ -->
<div class="running-header">
  <span class="rh-left">BCF Staff Portal — SOP Documentation</span>
  <span class="rh-right">BCF-SOP-001 v1.0</span>
</div>

${sectionHead('1', 'Introduction & Purpose')}
<p style="font-size:9.5pt;line-height:1.7;">
This Standard Operating Procedure (SOP) document defines the standardised procedures, roles, and responsibilities governing the use of the <strong>BCF Staff Portal</strong> — the central digital platform for Bespoke Garden Rooms Ballycastle's workforce management operations.
</p>
<p style="font-size:9.5pt;line-height:1.7;">
The portal facilitates attendance tracking, job scheduling, leave and overtime management, payroll processing, vehicle allocation, and client project integration. All staff and management are required to follow the procedures outlined in this document.
</p>

${note('📌', '<strong>Mandatory Compliance:</strong> Adherence to the procedures in this SOP is mandatory for all staff members. Failure to follow these procedures may result in inaccurate payroll, attendance discrepancies, or disciplinary action.')}

${sectionHead('2', 'Scope & Application')}
<p style="font-size:9.5pt;line-height:1.7;">This SOP applies to:</p>
<ul>
  <li>All active employees of Bespoke Garden Rooms Ballycastle</li>
  <li>Contractors and subcontractors who have been granted portal access</li>
  <li>All operations conducted through the BCF Staff Portal at <strong>https://staff.bespokegardenroomsballycastle.co.uk</strong></li>
</ul>
<p style="font-size:9.5pt;line-height:1.7;margin-top:8px;">This document covers the procedures effective from <strong>May 2026</strong> and must be reviewed annually or following significant system changes.</p>

${pageBreak()}

<div class="running-header">
  <span class="rh-left">BCF Staff Portal — SOP Documentation</span>
  <span class="rh-right">BCF-SOP-001 v1.0</span>
</div>

${sectionHead('3', 'Definitions & Glossary')}
<table style="margin-bottom:16px;border:1px solid #E5E7EB;border-radius:8px;overflow:hidden;">
  <tbody>
    ${defRow('Portal', 'The BCF Staff Portal web application accessible at https://staff.bespokegardenroomsballycastle.co.uk')}
    ${defRow('Clock-In', 'The act of recording the start of a work shift within the portal')}
    ${defRow('Clock-Out', 'The act of recording the end of a work shift within the portal')}
    ${defRow('Time Entry', 'A single clock-in/clock-out record associated with one shift')}
    ${defRow('OT (Overtime)', 'Hours worked beyond the standard shift on a normal working day, pre-approved by a manager')}
    ${defRow('RDOT', 'Rest Day Overtime — all hours worked on a scheduled day off or public holiday')}
    ${defRow('Payroll Run', 'A manager-generated calculation of gross pay for all staff for a defined pay period')}
    ${defRow('Payslip', 'An individual pay record showing hours, gross pay, deductions, and net pay')}
    ${defRow('Job', 'A discrete unit of work scheduled on a specific date, assigned to one or more staff members')}
    ${defRow('Live Board', 'The day-by-day job scheduling view available to Admin and Managers')}
    ${defRow('Project', 'A client engagement consisting of one or more jobs and a progress checklist')}
    ${defRow('Site Head', 'A senior staff member responsible for managing jobs on assigned project sites')}
    ${defRow('BCF', 'Bespoke Custom Furniture — a related business whose orders are integrated into the portal')}
    ${defRow('BGR', 'Bespoke Garden Rooms — the primary business whose projects are integrated into the portal')}
    ${defRow('QR Code', 'A unique machine-readable code assigned to each staff member for contactless clock-in')}
    ${defRow('Pending', 'Status of a time entry or request awaiting manager review and approval')}
    ${defRow('Approved', 'Status of an entry confirmed by a manager; included in payroll calculations')}
    ${defRow('Rejected', 'Status of an entry declined by a manager with a reason provided')}
  </tbody>
</table>

${sectionHead('4', 'Roles & Responsibilities')}
<p style="font-size:9.5pt;margin-bottom:14px;">The portal operates on a role-based access model. Each user is assigned exactly one role that determines which features they can access and which actions they can perform.</p>

${roleCard('Administrator', RED, '🔐', [
    'Full access to all portal features',
    'Create and manage all staff accounts, assign roles, set hourly rates',
    'Generate, review, and approve payroll runs',
    'Manage all jobs, projects, vans, businesses, and system settings',
    'View audit log and all reports',
    'Cannot delete their own account',
])}

${roleCard('Manager', DARK, '👔', [
    'All attendance, leave, overtime, and job management capabilities',
    'Create and edit jobs on the Live Board, assign staff and vans',
    'Approve/reject attendance entries, leave requests, and OT requests',
    'Generate and approve payroll runs; add deductions to payslips',
    'View staff profiles and manage van allocations',
    'Cannot access Businesses or Audit Log (Admin only)',
])}

${roleCard('HR', BLUE, '🧾', [
    'View all staff attendance records and approve/reject time entries',
    'View all leave requests; no approval rights',
    'Generate and manage payroll runs',
    'View and update staff profiles and hourly rates',
    'Cannot create/edit jobs or manage projects and vans',
])}

${roleCard('Site Head', AMBER, '🪪', [
    'View and manage jobs on projects they are assigned to',
    'Start and complete jobs; cannot create or delete jobs',
    'Scan staff QR codes to clock in/out on-site',
    'Clock in/out for their own shifts',
    'Request leave and overtime',
])}

${roleCard(GREEN, GREEN, '👷', [
    'Clock in and out for their own shifts; take breaks',
    'View their own assigned jobs and update status (start/complete)',
    'Submit leave requests and overtime requests',
    'View own attendance history and payslip',
    'Access their personal QR code',
])}

${pageBreak()}

<div class="running-header">
  <span class="rh-left">BCF Staff Portal — SOP Documentation</span>
  <span class="rh-right">BCF-SOP-001 v1.0</span>
</div>

${sectionHead('5', 'System Access & Security')}

${subHead('5.1', 'Logging In')}
${step(1, 'Open a browser and navigate to <strong>https://staff.bespokegardenroomsballycastle.co.uk</strong>')}
${step(2, 'Enter your registered <strong>email address</strong> and <strong>password</strong>.')}
${step(3, 'Click <strong>Log In</strong>. You will be directed to your Dashboard.')}
${step(4, '<strong>First login only:</strong> If you have been issued a temporary password by your manager, you will be prompted to create a new password before continuing.')}

${subHead('5.2', 'Password Requirements')}
<ul>
  <li>Minimum 8 characters</li>
  <li>Must include at least one uppercase letter, one number, and one special character</li>
  <li>Do not share your password with any other person</li>
  <li>Change your password immediately if you believe it has been compromised</li>
</ul>

${subHead('5.3', 'Password Reset')}
<p style="font-size:9.5pt;">Staff cannot self-reset passwords. If you are unable to log in, contact your <strong>line manager or HR</strong> who will issue a temporary password via the portal. You will be required to change it on first login.</p>
${note('🔒', '<strong>Security Notice:</strong> Never use the same password across multiple systems. Log out of the portal whenever you finish your session, particularly on shared devices.', RED)}

${subHead('5.4', 'Session & Inactivity')}
<p style="font-size:9.5pt;">Sessions expire after <strong>120 minutes of inactivity</strong>. You will be redirected to the login page. Any unsaved form data will be lost.</p>

${sectionHead('6', 'Attendance Management', 'Procedures for recording, managing, and approving staff work time')}

${subHead('6.1', 'Clock-In Procedure')}
<p style="font-size:9.5pt;margin-bottom:8px;"><strong>Applies to:</strong> All staff | <strong>When:</strong> At the start of each working shift</p>
${step(1, 'Log in to the portal and go to the <strong>Dashboard</strong> or <strong>Attendance</strong> page from the left navigation.')}
${step(2, 'Press the <strong>"Clock In"</strong> button.', 'If you have an approved OT or RDOT request for today, a modal will appear asking you to select the clock-in type (Regular / OT / RDOT).')}
${step(3, 'Your clock-in is recorded. A live timer will begin counting your hours.')}
${step(4, 'Your status changes to <strong>Working</strong>. This is visible to your manager in real time.')}
${note('⚠️', '<strong>Important:</strong> You may only have one active clock-in at a time. If you forget to clock in, do not create a duplicate entry — contact your manager for a manual entry.', AMBER)}

${subHead('6.2', 'Clock-Out Procedure')}
<p style="font-size:9.5pt;margin-bottom:8px;"><strong>Applies to:</strong> All staff | <strong>When:</strong> At the end of each working shift</p>
${step(1, 'Return to the <strong>Dashboard</strong> or <strong>Attendance</strong> page.')}
${step(2, 'Press the <strong>"Clock Out"</strong> button.')}
${step(3, 'The system calculates your total hours (minus break time) and closes the time entry.')}
${step(4, 'For standard staff, the entry is set to <strong>Pending</strong> awaiting manager approval. Admin and Manager clock-outs are auto-approved.')}

${pageBreak()}

<div class="running-header">
  <span class="rh-left">BCF Staff Portal — SOP Documentation</span>
  <span class="rh-right">BCF-SOP-001 v1.0</span>
</div>

${subHead('6.3', 'Break & Lunch Procedure')}
<p style="font-size:9.5pt;margin-bottom:8px;"><strong>Applies to:</strong> All staff | <strong>When:</strong> During a clocked-in shift</p>
${step(1, 'While clocked in, press <strong>"Take Break"</strong> or <strong>"Lunch Break"</strong>.')}
${step(2, 'Your status changes to <strong>On Break</strong> or <strong>On Lunch</strong>. The work timer pauses.')}
${step(3, 'When you return, press <strong>"End Break"</strong>. Break duration is recorded and automatically deducted from your total hours.')}
${note('ℹ️', 'Break durations are included in your time entry record and are visible to managers when reviewing attendance for payroll.', BLUE)}

${subHead('6.4', 'Overtime Clock-In')}
<p style="font-size:9.5pt;margin-bottom:8px;"><strong>Applies to:</strong> All staff with an approved OT/RDOT request | <strong>Pre-condition:</strong> OT request must be approved before the shift date</p>
${step(1, 'On the date of your approved OT/RDOT shift, go to the Dashboard.')}
${step(2, 'Press <strong>"Clock In"</strong>. A selection modal will appear.')}
${step(3, 'Select <strong>OT</strong> (standard overtime on a working day) or <strong>RDOT</strong> (rest day overtime).')}
${step(4, 'Clock in. All hours on an OT/RDOT shift are recorded as overtime hours in payroll.')}

${subHead('6.5', 'Attendance Entry Approval (Managers & HR)')}
<p style="font-size:9.5pt;margin-bottom:8px;"><strong>Applies to:</strong> Admin, Manager, HR | <strong>Frequency:</strong> Daily review recommended</p>
${step(1, 'Go to <strong>Attendance</strong> in the left navigation.')}
${step(2, 'Filter by <strong>Status: Pending</strong> to view entries awaiting approval.')}
${step(3, 'Review each entry — check clock-in time, clock-out time, total hours, and any notes.')}
${step(4, 'Click <strong>Approve</strong> to accept the entry (it will be included in payroll). Use <strong>Bulk Approve</strong> to approve multiple entries at once.')}
${step(5, 'Click <strong>Reject</strong> and enter a reason if the entry is incorrect. The staff member will be notified.')}
${note('⚠️', '<strong>Payroll dependency:</strong> Only APPROVED time entries are included in payroll calculations. Entries left in Pending status will not be paid until approved.', AMBER)}

${subHead('6.6', 'Manual Time Entry (Managers & HR)')}
<p style="font-size:9.5pt;margin-bottom:8px;"><strong>Use when:</strong> A staff member forgot to clock in, had a system issue, or was on site without portal access</p>
${step(1, 'Go to <strong>Attendance</strong> and click <strong>"Manual Entry"</strong>.')}
${step(2, 'Select one or more staff members, enter the date, clock-in time, and clock-out time.')}
${step(3, 'Add a note explaining the reason for the manual entry.')}
${step(4, 'Click <strong>Submit</strong>. The entry is created with <strong>Approved</strong> status (manual entries by managers are auto-approved).')}
${note('📌', 'Each staff member may only have one time entry per date. If an entry already exists for that date, it will be skipped. Check existing entries before adding a manual one.', BLUE)}

${pageBreak()}

<div class="running-header">
  <span class="rh-left">BCF Staff Portal — SOP Documentation</span>
  <span class="rh-right">BCF-SOP-001 v1.0</span>
</div>

${sectionHead('7', 'Job Management', 'Procedures for scheduling, assigning, and tracking work jobs')}

${subHead('7.1', 'Creating a Job (Admin / Manager / Site Head)')}
${step(1, 'Go to <strong>Live Board</strong> (Admin/Manager) or <strong>My Jobs</strong> (Site Head) and navigate to the target date.')}
${step(2, 'Click <strong>"Add Job"</strong>. A modal form appears.')}
${step(3, 'Enter the <strong>Job Title</strong> (required), select a <strong>Project/Site</strong> (optional), set the <strong>Date</strong>, <strong>Start Time</strong>, and <strong>End Time</strong>.')}
${step(4, 'Select a <strong>Van</strong> if transport is required.')}
${step(5, 'Assign <strong>Crew members</strong> by checking staff names. Assigned staff receive a notification.')}
${step(6, 'Optionally link the job to a <strong>BCF Order</strong> or <strong>BGR Project stage</strong>.')}
${step(7, 'Click <strong>Create Job</strong>. The job appears on the board with status <strong>Scheduled</strong>.')}
${note('⚠️', 'If any assigned staff member has approved leave on the job date, a warning will appear. Consider re-assigning before confirming.', AMBER)}

${subHead('7.2', 'Assigning Staff to a Job')}
<p style="font-size:9.5pt;margin-bottom:8px;">Staff may be assigned during job creation or by editing an existing job.</p>
<ul>
  <li>Open the job on the Live Board and click <strong>Edit (pencil icon)</strong></li>
  <li>In the Crew section, check or uncheck staff members</li>
  <li>Newly added staff receive an in-app notification</li>
  <li>Site heads can only assign staff to jobs within their assigned projects</li>
</ul>

${subHead('7.3', 'Job Status Updates')}
<table style="border:1px solid #E5E7EB;border-radius:8px;overflow:hidden;margin-bottom:12px;">
  <thead style="background:${LIGHT};">
    <tr>
      <th style="padding:8px 12px;font-size:8.5pt;color:${DARK};">From Status</th>
      <th style="padding:8px 12px;font-size:8.5pt;color:${DARK};">Action</th>
      <th style="padding:8px 12px;font-size:8.5pt;color:${DARK};">To Status</th>
      <th style="padding:8px 12px;font-size:8.5pt;color:${DARK};">Who Can Perform</th>
    </tr>
  </thead>
  <tbody>
    <tr style="border-top:1px solid #E5E7EB;"><td style="padding:8px 12px;font-size:9pt;">Scheduled</td><td style="padding:8px 12px;font-size:9pt;">▶ Start Job</td><td style="padding:8px 12px;font-size:9pt;">In Progress</td><td style="padding:8px 12px;font-size:9pt;">Assigned staff, Site Head, Manager, Admin</td></tr>
    <tr style="border-top:1px solid #E5E7EB;"><td style="padding:8px 12px;font-size:9pt;">Scheduled</td><td style="padding:8px 12px;font-size:9pt;">✕ Cancel</td><td style="padding:8px 12px;font-size:9pt;">Cancelled</td><td style="padding:8px 12px;font-size:9pt;">Site Head, Manager, Admin</td></tr>
    <tr style="border-top:1px solid #E5E7EB;"><td style="padding:8px 12px;font-size:9pt;">In Progress</td><td style="padding:8px 12px;font-size:9pt;">✓ Complete</td><td style="padding:8px 12px;font-size:9pt;">Completed</td><td style="padding:8px 12px;font-size:9pt;">Assigned staff, Site Head, Manager, Admin</td></tr>
    <tr style="border-top:1px solid #E5E7EB;"><td style="padding:8px 12px;font-size:9pt;">Completed</td><td style="padding:8px 12px;font-size:9pt;">↩ Re-open</td><td style="padding:8px 12px;font-size:9pt;">In Progress</td><td style="padding:8px 12px;font-size:9pt;">Manager, Admin only</td></tr>
    <tr style="border-top:1px solid #E5E7EB;"><td style="padding:8px 12px;font-size:9pt;">Cancelled</td><td style="padding:8px 12px;font-size:9pt;">↩ Restore</td><td style="padding:8px 12px;font-size:9pt;">Scheduled</td><td style="padding:8px 12px;font-size:9pt;">Manager, Admin only</td></tr>
  </tbody>
</table>

${subHead('7.4', 'BCF / BGR Client Integration')}
<p style="font-size:9.5pt;margin-bottom:8px;">Jobs can be linked to external client platform stages during creation or editing.</p>
<ul>
  <li><strong>BCF Orders:</strong> Select an order and stage from the BCF panel in the job form. When the job is marked <strong>Completed</strong>, the linked BCF stage is automatically updated to <strong>Done</strong>.</li>
  <li><strong>BGR Projects:</strong> Select a project and stage/substage from the BGR panel. The link is tracked for reporting but does not auto-update the external system.</li>
</ul>

${pageBreak()}

<div class="running-header">
  <span class="rh-left">BCF Staff Portal — SOP Documentation</span>
  <span class="rh-right">BCF-SOP-001 v1.0</span>
</div>

${sectionHead('8', 'Leave Management')}

${subHead('8.1', 'Submitting a Leave Request (All Staff)')}
${step(1, 'Go to <strong>Leave</strong> in the left navigation.')}
${step(2, 'Click <strong>"New Request"</strong>.')}
${step(3, 'Select the <strong>Leave Type</strong> (Annual, Sick, Compassionate, Other), start date, and end date.')}
${step(4, 'The system calculates the number of working days automatically.')}
${step(5, 'Add an optional note, then click <strong>Submit</strong>.')}
${step(6, 'Your manager is notified. Your request shows as <strong>Pending</strong> until a decision is made.')}
${note('ℹ️', 'Check your leave balance on the Dashboard before submitting. Annual leave requests that exceed your remaining entitlement may be declined.', BLUE)}

${subHead('8.2', 'Leave Approval Process (Admin & Manager)')}
${step(1, 'Go to <strong>Leave</strong> and filter by <strong>Pending</strong>.')}
${step(2, 'Review the request dates, type, and any notes from the staff member.')}
${step(3, 'Check the schedule for conflicts — if the staff member is assigned to a job on those dates, a conflict warning is shown.')}
${step(4, 'Click <strong>Approve</strong> or <strong>Reject</strong>. A rejection requires a reason.')}
${step(5, 'The staff member receives an in-app notification of the decision.')}

${subHead('8.3', 'Leave Types & Entitlements')}
<table style="border:1px solid #E5E7EB;border-radius:8px;overflow:hidden;margin-bottom:12px;">
  <thead style="background:${LIGHT};">
    <tr>
      <th style="padding:8px 12px;font-size:8.5pt;color:${DARK};">Type</th>
      <th style="padding:8px 12px;font-size:8.5pt;color:${DARK};">Deducts Entitlement?</th>
      <th style="padding:8px 12px;font-size:8.5pt;color:${DARK};">Notes</th>
    </tr>
  </thead>
  <tbody>
    <tr style="border-top:1px solid #E5E7EB;"><td style="padding:8px 12px;font-size:9pt;">Annual Leave</td><td style="padding:8px 12px;font-size:9pt;color:${GREEN};font-weight:600;">Yes</td><td style="padding:8px 12px;font-size:9pt;">Default entitlement: 28 days per year (set per staff member)</td></tr>
    <tr style="border-top:1px solid #E5E7EB;"><td style="padding:8px 12px;font-size:9pt;">Sick Leave</td><td style="padding:8px 12px;font-size:9pt;color:${RED};font-weight:600;">No</td><td style="padding:8px 12px;font-size:9pt;">Does not reduce annual entitlement</td></tr>
    <tr style="border-top:1px solid #E5E7EB;"><td style="padding:8px 12px;font-size:9pt;">Compassionate</td><td style="padding:8px 12px;font-size:9pt;color:${RED};font-weight:600;">No</td><td style="padding:8px 12px;font-size:9pt;">Bereavement or family emergency — granted at manager discretion</td></tr>
    <tr style="border-top:1px solid #E5E7EB;"><td style="padding:8px 12px;font-size:9pt;">Other</td><td style="padding:8px 12px;font-size:9pt;">Varies</td><td style="padding:8px 12px;font-size:9pt;">Agreed individually between staff and manager</td></tr>
  </tbody>
</table>

${sectionHead('9', 'Overtime Requests')}

${subHead('9.1', 'Submitting an Overtime Request (All Staff)')}
${step(1, 'Go to <strong>Overtime</strong> in the left navigation.')}
${step(2, 'Click <strong>"New Request"</strong>. Select the date, OT type (OT or RDOT), and add an optional note.')}
${step(3, 'Submit. Your manager is notified. The request shows as <strong>Pending</strong>.')}

${subHead('9.2', 'Overtime Approval (Admin & Manager)')}
${step(1, 'Go to <strong>Overtime</strong> and review pending requests.')}
${step(2, 'Approve or decline. On approval, the staff member\'s Dashboard for that date will show the OT clock-in option.')}

${note('ℹ️', '<strong>OT vs RDOT:</strong> OT applies when hours exceed the regular shift on a normal working day. All hours on the overtime shift count as OT hours in payroll. RDOT applies when a staff member works on their scheduled day off — all hours are counted as overtime hours regardless of duration.', BLUE)}

${pageBreak()}

<div class="running-header">
  <span class="rh-left">BCF Staff Portal — SOP Documentation</span>
  <span class="rh-right">BCF-SOP-001 v1.0</span>
</div>

${sectionHead('10', 'Payroll Processing', 'Procedures for generating, reviewing, and approving pay runs')}

${subHead('10.1', 'Generating a Payroll Run (Admin / Manager / HR)')}
${step(1, 'Go to <strong>Payroll</strong> in the Admin or Human Resources section of the navigation.')}
${step(2, 'Click <strong>"Generate Run"</strong>.')}
${step(3, 'Enter the <strong>Period From</strong> and <strong>Period To</strong> dates. Click <strong>Generate</strong>.')}
${step(4, 'The system creates a draft payslip for every active, non-admin staff member with approved time entries in that period.')}
${step(5, 'Draft payslips appear in the Payroll list with status <strong>Draft</strong>.')}
${note('⚠️', '<strong>Pre-condition:</strong> Ensure all relevant attendance entries for the period are APPROVED before generating payroll. Pending entries will be excluded from the calculation.', AMBER)}

${subHead('10.2', 'Hour Calculation Rules')}
<table style="border:1px solid #E5E7EB;border-radius:8px;overflow:hidden;margin-bottom:12px;">
  <thead style="background:${LIGHT};">
    <tr>
      <th style="padding:8px 12px;font-size:8.5pt;color:${DARK};">Shift Type</th>
      <th style="padding:8px 12px;font-size:8.5pt;color:${DARK};">Regular Hours</th>
      <th style="padding:8px 12px;font-size:8.5pt;color:${DARK};">Overtime Hours</th>
    </tr>
  </thead>
  <tbody>
    <tr style="border-top:1px solid #E5E7EB;"><td style="padding:8px 12px;font-size:9pt;">Standard shift (no OT)</td><td style="padding:8px 12px;font-size:9pt;">Up to 8h per day</td><td style="padding:8px 12px;font-size:9pt;">Hours beyond 8h</td></tr>
    <tr style="border-top:1px solid #E5E7EB;"><td style="padding:8px 12px;font-size:9pt;">OT-type shift (approved)</td><td style="padding:8px 12px;font-size:9pt;">0h</td><td style="padding:8px 12px;font-size:9pt;">All hours</td></tr>
    <tr style="border-top:1px solid #E5E7EB;"><td style="padding:8px 12px;font-size:9pt;">RDOT-type shift (approved)</td><td style="padding:8px 12px;font-size:9pt;">0h</td><td style="padding:8px 12px;font-size:9pt;">All hours</td></tr>
  </tbody>
</table>

${subHead('10.3', 'Reviewing & Approving a Payslip')}
${step(1, 'Click on a draft payslip from the Payroll list to open it.')}
${step(2, 'Review: pay period, regular hours, overtime hours, gross pay calculation.')}
${step(3, 'Add any <strong>deductions</strong> (see 10.4) if required.')}
${step(4, 'If the payslip is correct, click <strong>"Approve Payslip"</strong>. The status changes to <strong>Approved</strong>.')}
${step(5, 'The staff member can now view their approved payslip under <strong>My Payslip</strong>.')}
${note('🔒', '<strong>Locked on approval:</strong> Once a payslip is approved it is permanently locked and cannot be edited. Verify all figures carefully before approving.', RED)}

${subHead('10.4', 'Deductions')}
<p style="font-size:9.5pt;margin-bottom:8px;">Deductions can be added to any <strong>draft</strong> payslip before approval.</p>
${step(1, 'Open the draft payslip and scroll to the <strong>Deductions</strong> section.')}
${step(2, 'Click <strong>"+ Add Deduction"</strong>. Enter a description (e.g. "Advance repayment") and the amount.')}
${step(3, 'Net Pay updates automatically as deductions are added.')}
${step(4, 'Click <strong>"Save Deductions"</strong>. Then proceed to approve.')}

${subHead('10.5', 'Payroll Export (CSV)')}
${step(1, 'Go to <strong>Payroll</strong> and click <strong>"Export CSV"</strong>.')}
${step(2, 'Filter by period or status if required, then download. The CSV includes: Employee ID, Name, Regular Hours, OT Hours, Gross Pay, Deductions, Net Pay, Approved By.')}

${pageBreak()}

<div class="running-header">
  <span class="rh-left">BCF Staff Portal — SOP Documentation</span>
  <span class="rh-right">BCF-SOP-001 v1.0</span>
</div>

${sectionHead('11', 'Staff Management', 'Applies to Admin and Manager roles')}

${subHead('11.1', 'Creating a Staff Account')}
${step(1, 'Go to <strong>Staff</strong> in the Management section.')}
${step(2, 'Click <strong>"New Staff Member"</strong>. Enter: full name, email, role, employee ID, hourly rate (regular and overtime), and annual leave entitlement.')}
${step(3, 'The system generates a temporary password. <strong>Record it securely</strong> and share it with the staff member privately.')}
${step(4, 'The staff member logs in with the temporary password and is prompted to create a new one.')}

${subHead('11.2', 'Updating Staff Details')}
<p style="font-size:9.5pt;">Open the staff member\'s profile from the Staff list. Click <strong>Edit Profile</strong>. You can update: name, contact details, role, hourly rates, annual leave entitlement, and emergency contact. Click <strong>Save</strong>.</p>

${subHead('11.3', 'Resetting a Staff Password')}
${step(1, 'Go to the staff member\'s profile.')}
${step(2, 'Click <strong>"Reset Password"</strong>. A new temporary password is generated and displayed on screen.')}
${step(3, '<strong>Copy and securely share</strong> the temporary password with the staff member. It will not be shown again.')}
${step(4, 'The staff member is required to change the password on their next login.')}
${note('⚠️', 'Never send a temporary password via public channels (e.g. social media, group chat). Use a private, direct message or in-person communication.', RED)}

${subHead('11.4', 'Deactivating a Staff Account')}
<p style="font-size:9.5pt;">Deactivating prevents login but preserves all historical data (attendance, payslips, jobs). On the staff profile, toggle the <strong>Active</strong> status to inactive. Deactivated staff are excluded from payroll generation.</p>

${sectionHead('12', 'Project Management', 'Applies to Admin and Manager roles')}

${subHead('12.1', 'Creating a Project')}
${step(1, 'Go to <strong>Projects</strong> in the Management section.')}
${step(2, 'Click <strong>"New Project"</strong>. Enter: project name, client/customer, business (BCF or BGR), status, and assigned site heads.')}
${step(3, 'The project is created and appears on the project board.')}

${subHead('12.2', 'Project Checklist & Job Linking')}
<ul>
  <li>Every job linked to a project automatically creates a checklist item on that project</li>
  <li>When the job is marked <strong>Completed</strong>, its checklist item is automatically ticked</li>
  <li>Additional checklist items can be added manually from the project detail page</li>
</ul>

${subHead('12.3', 'Project Status Lifecycle')}
<p style="font-size:9.5pt;">Projects move through: <strong>Planning → Active → On Hold → Completed</strong>. Status can be updated manually from the project detail page. Only Admin and Manager can change project status.</p>

${pageBreak()}

<div class="running-header">
  <span class="rh-left">BCF Staff Portal — SOP Documentation</span>
  <span class="rh-right">BCF-SOP-001 v1.0</span>
</div>

${sectionHead('13', 'Vehicle (Van) Management', 'Applies to Admin and Manager roles')}

${subHead('13.1', 'Fleet Management')}
<p style="font-size:9.5pt;margin-bottom:8px;">Go to <strong>Vans</strong> in the Management section to view and manage the vehicle fleet.</p>
<ul>
  <li>Add new vehicles with registration, make, model, year, MOT expiry, and service dates</li>
  <li>Deactivate a van to exclude it from job assignment dropdowns</li>
  <li>Vehicles with overdue MOT or service dates are flagged with a warning indicator</li>
</ul>

${subHead('13.2', 'Van Allocations')}
${step(1, 'From the Vans page, select a vehicle and click <strong>"Allocate"</strong>.')}
${step(2, 'Select the staff member and the allocation dates (start and end).')}
${step(3, 'The allocation is recorded and the van can also be assigned to specific jobs.')}

${sectionHead('14', 'QR Code System')}

${subHead('14.1', 'Accessing Your QR Code (All Staff)')}
${step(1, 'Go to <strong>My QR Code</strong> in the Operations section of the navigation.')}
${step(2, 'Your unique QR code is displayed. Each staff member has a permanent, unique code tied to their account.')}
${step(3, 'Download or screenshot the QR code to keep it on your phone for easy access on site.')}

${subHead('14.2', 'Scanning Staff QR Codes (Site Head & Admin)')}
${step(1, 'Go to <strong>QR Scanner</strong> in the Field section of the navigation.')}
${step(2, 'Allow browser camera access when prompted.')}
${step(3, 'Point the camera at a staff member\'s QR code. Hold steady for 1–2 seconds.')}
${step(4, 'The system displays a confirmation with the staff member\'s name and clock-in/out action. The record is saved automatically.')}
${note('ℹ️', 'QR scanning requires an internet connection. If the connection is unavailable, note the time manually and enter it as a manual attendance record once connected.', BLUE)}

${sectionHead('15', 'Reporting & Audit')}

${subHead('15.1', 'Reports')}
<p style="font-size:9.5pt;">Accessible by Admin and Manager via <strong>Reports</strong> in the Admin section. Available reports include: weekly/monthly hours by staff member, attendance patterns, payroll totals per period, and job completion rates.</p>

${subHead('15.2', 'Audit Log')}
<p style="font-size:9.5pt;">Accessible by <strong>Admin only</strong> via <strong>Audit Log</strong> in the Admin section. The audit log records: user, action taken, target record, and timestamp. All significant actions (payslip approvals, password resets, attendance rejections, etc.) are automatically logged. Audit records cannot be edited or deleted.</p>

${subHead('15.3', 'Attendance Export')}
<p style="font-size:9.5pt;">Managers and HR can export attendance data as a CSV from the <strong>Attendance</strong> page using the <strong>Export CSV</strong> button. Apply date range and status filters before exporting to narrow the dataset.</p>

${pageBreak()}

<div class="running-header">
  <span class="rh-left">BCF Staff Portal — SOP Documentation</span>
  <span class="rh-right">BCF-SOP-001 v1.0</span>
</div>

${sectionHead('16', 'Non-Compliance & Exceptions')}

<table style="border:1px solid #E5E7EB;border-radius:8px;overflow:hidden;margin-bottom:16px;">
  <thead style="background:${LIGHT};">
    <tr>
      <th style="padding:10px 12px;font-size:8.5pt;color:${DARK};">Scenario</th>
      <th style="padding:10px 12px;font-size:8.5pt;color:${DARK};">Correct Action</th>
      <th style="padding:10px 12px;font-size:8.5pt;color:${DARK};">Responsible</th>
    </tr>
  </thead>
  <tbody>
    <tr style="border-top:1px solid #E5E7EB;"><td style="padding:8px 12px;font-size:9pt;">Staff forgot to clock in</td><td style="padding:8px 12px;font-size:9pt;">Manager adds manual time entry</td><td style="padding:8px 12px;font-size:9pt;">Manager / HR</td></tr>
    <tr style="border-top:1px solid #E5E7EB;"><td style="padding:8px 12px;font-size:9pt;">Staff forgot to clock out</td><td style="padding:8px 12px;font-size:9pt;">Manager updates entry with correct clock-out time</td><td style="padding:8px 12px;font-size:9pt;">Manager / HR</td></tr>
    <tr style="border-top:1px solid #E5E7EB;"><td style="padding:8px 12px;font-size:9pt;">Incorrect hours on approved entry</td><td style="padding:8px 12px;font-size:9pt;">Reject the entry, correct via manual entry, contact Admin if payslip already approved</td><td style="padding:8px 12px;font-size:9pt;">Manager</td></tr>
    <tr style="border-top:1px solid #E5E7EB;"><td style="padding:8px 12px;font-size:9pt;">Staff unable to log in</td><td style="padding:8px 12px;font-size:9pt;">Manager or HR resets password via staff profile</td><td style="padding:8px 12px;font-size:9pt;">Manager / HR</td></tr>
    <tr style="border-top:1px solid #E5E7EB;"><td style="padding:8px 12px;font-size:9pt;">Payslip approved in error</td><td style="padding:8px 12px;font-size:9pt;">Contact Admin — approved payslips cannot be edited through the portal</td><td style="padding:8px 12px;font-size:9pt;">Admin only</td></tr>
    <tr style="border-top:1px solid #E5E7EB;"><td style="padding:8px 12px;font-size:9pt;">OT not pre-approved but worked</td><td style="padding:8px 12px;font-size:9pt;">Submit a retrospective OT request; Manager approves and adjusts attendance entry</td><td style="padding:8px 12px;font-size:9pt;">Staff + Manager</td></tr>
    <tr style="border-top:1px solid #E5E7EB;"><td style="padding:8px 12px;font-size:9pt;">Leave conflict with scheduled job</td><td style="padding:8px 12px;font-size:9pt;">Manager reassigns job before approving leave, or declines leave</td><td style="padding:8px 12px;font-size:9pt;">Manager</td></tr>
    <tr style="border-top:1px solid #E5E7EB;"><td style="padding:8px 12px;font-size:9pt;">QR scan fails on site</td><td style="padding:8px 12px;font-size:9pt;">Note time manually; Site Head/Manager adds manual entry on return</td><td style="padding:8px 12px;font-size:9pt;">Site Head / Manager</td></tr>
  </tbody>
</table>

${pageBreak()}

<div class="running-header">
  <span class="rh-left">BCF Staff Portal — SOP Documentation</span>
  <span class="rh-right">BCF-SOP-001 v1.0</span>
</div>

<!-- APPENDIX A -->
<div style="margin-bottom:24px;">
  <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;">
    <div style="min-width:32px;height:32px;background:${DARK};border-radius:8px;display:flex;align-items:center;justify-content:center;">
      <span style="color:white;font-size:9pt;font-weight:800;">A</span>
    </div>
    <h2 style="font-size:14pt;font-weight:800;color:${DARK};">Appendix A — Permissions Matrix</h2>
  </div>

  <table style="border:1px solid #E5E7EB;border-radius:8px;overflow:hidden;font-size:8.5pt;">
    <thead style="background:${DARK};">
      <tr>
        <th style="padding:10px 12px;text-align:left;color:white;">Feature / Action</th>
        <th style="padding:10px 12px;text-align:center;color:${RED};min-width:60px;">Admin</th>
        <th style="padding:10px 12px;text-align:center;color:#A8B4C8;min-width:60px;">Manager</th>
        <th style="padding:10px 12px;text-align:center;color:#60A5FA;min-width:60px;">HR</th>
        <th style="padding:10px 12px;text-align:center;color:${AMBER};min-width:60px;">Site Head</th>
        <th style="padding:10px 12px;text-align:center;color:#86EFAC;min-width:60px;">Staff</th>
      </tr>
    </thead>
    <tbody>
      ${permRow('Dashboard', '✓', '✓', '✓', '✓', '✓')}
      ${permRow('Clock In / Out (own)', '✓', '✓', '✓', '✓', '✓')}
      ${permRow('Breaks (own)', '✓', '✓', '✓', '✓', '✓')}
      ${permRow('View own attendance', '✓', '✓', '✓', '✓', '✓')}
      ${permRow('View all attendance', '✓', '✓', '✓', '—', '—')}
      ${permRow('Approve / reject attendance', '✓', '✓', '✓', '—', '—')}
      ${permRow('Manual time entry', '✓', '✓', '✓', '—', '—')}
      ${permRow('Export attendance CSV', '✓', '✓', '✓', '—', '—')}
      ${permRow('Submit leave request', '✓', '✓', '✓', '✓', '✓')}
      ${permRow('Approve / reject leave', '✓', '✓', '—', '—', '—')}
      ${permRow('Submit OT request', '✓', '✓', '✓', '✓', '✓')}
      ${permRow('Approve / reject OT', '✓', '✓', '—', '—', '—')}
      ${permRow('View My Jobs / Site Jobs', '✓', '✓', '✓', 'Own projects', 'Own jobs')}
      ${permRow('View Live Board', '✓', '✓', '—', '—', '—')}
      ${permRow('Create / edit jobs', '✓', '✓', '—', 'Own projects', '—')}
      ${permRow('Delete jobs', '✓', '✓', '—', '—', '—')}
      ${permRow('Update job status', '✓', '✓', '—', '✓', 'Assigned only')}
      ${permRow('View projects', '✓', '✓', '—', 'Own projects', '—')}
      ${permRow('Create / edit projects', '✓', '✓', '—', '—', '—')}
      ${permRow('View / manage staff', '✓', '✓', '✓', '—', '—')}
      ${permRow('Create staff accounts', '✓', '✓', '—', '—', '—')}
      ${permRow('Reset staff password', '✓', '✓', '✓', '—', '—')}
      ${permRow('View / manage vans', '✓', '✓', '—', '—', '—')}
      ${permRow('Generate payroll runs', '✓', '✓', '✓', '—', '—')}
      ${permRow('Approve payslips', '✓', '✓', '✓', '—', '—')}
      ${permRow('View own payslip', '—', '—', '—', '✓', '✓')}
      ${permRow('My QR Code', '✓', '✓', '✓', '✓', '✓')}
      ${permRow('QR Scanner', '✓', '✓', '—', '✓', '—')}
      ${permRow('Reports', '✓', '✓', '—', '—', '—')}
      ${permRow('Audit Log', '✓', '—', '—', '—', '—')}
      ${permRow('Businesses management', '✓', '—', '—', '—', '—')}
    </tbody>
  </table>
</div>

${pageBreak()}

<div class="running-header">
  <span class="rh-left">BCF Staff Portal — SOP Documentation</span>
  <span class="rh-right">BCF-SOP-001 v1.0</span>
</div>

<!-- APPENDIX B -->
<div style="margin-bottom:32px;">
  <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;">
    <div style="min-width:32px;height:32px;background:${DARK};border-radius:8px;display:flex;align-items:center;justify-content:center;">
      <span style="color:white;font-size:9pt;font-weight:800;">B</span>
    </div>
    <h2 style="font-size:14pt;font-weight:800;color:${DARK};">Appendix B — Escalation & Support</h2>
  </div>

  <table style="border:1px solid #E5E7EB;border-radius:8px;overflow:hidden;margin-bottom:12px;">
    <thead style="background:${LIGHT};">
      <tr>
        <th style="padding:10px 12px;font-size:8.5pt;color:${DARK};">Issue Type</th>
        <th style="padding:10px 12px;font-size:8.5pt;color:${DARK};">First Contact</th>
        <th style="padding:10px 12px;font-size:8.5pt;color:${DARK};">Escalate To</th>
      </tr>
    </thead>
    <tbody>
      <tr style="border-top:1px solid #E5E7EB;"><td style="padding:8px 12px;font-size:9pt;">Can't log in / forgot password</td><td style="padding:8px 12px;font-size:9pt;">Line Manager or HR</td><td style="padding:8px 12px;font-size:9pt;">System Administrator</td></tr>
      <tr style="border-top:1px solid #E5E7EB;"><td style="padding:8px 12px;font-size:9pt;">Attendance entry error</td><td style="padding:8px 12px;font-size:9pt;">Line Manager</td><td style="padding:8px 12px;font-size:9pt;">HR</td></tr>
      <tr style="border-top:1px solid #E5E7EB;"><td style="padding:8px 12px;font-size:9pt;">Payslip error (draft)</td><td style="padding:8px 12px;font-size:9pt;">HR / Manager</td><td style="padding:8px 12px;font-size:9pt;">System Administrator</td></tr>
      <tr style="border-top:1px solid #E5E7EB;"><td style="padding:8px 12px;font-size:9pt;">Payslip error (approved/locked)</td><td style="padding:8px 12px;font-size:9pt;">System Administrator only</td><td style="padding:8px 12px;font-size:9pt;">Director</td></tr>
      <tr style="border-top:1px solid #E5E7EB;"><td style="padding:8px 12px;font-size:9pt;">System outage or errors</td><td style="padding:8px 12px;font-size:9pt;">System Administrator</td><td style="padding:8px 12px;font-size:9pt;">Technical Support</td></tr>
      <tr style="border-top:1px solid #E5E7EB;"><td style="padding:8px 12px;font-size:9pt;">Leave / OT dispute</td><td style="padding:8px 12px;font-size:9pt;">Line Manager</td><td style="padding:8px 12px;font-size:9pt;">HR / Director</td></tr>
    </tbody>
  </table>

  <p style="font-size:9pt;color:${GREY};">For technical issues with the portal, contact the system administrator with a description of the problem, the page URL, and any error messages shown.</p>
</div>

<!-- APPENDIX C -->
<div>
  <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;">
    <div style="min-width:32px;height:32px;background:${DARK};border-radius:8px;display:flex;align-items:center;justify-content:center;">
      <span style="color:white;font-size:9pt;font-weight:800;">C</span>
    </div>
    <h2 style="font-size:14pt;font-weight:800;color:${DARK};">Appendix C — Revision History</h2>
  </div>

  <table style="border:1px solid #E5E7EB;border-radius:8px;overflow:hidden;">
    <thead style="background:${LIGHT};">
      <tr>
        <th style="padding:10px 12px;font-size:8.5pt;color:${DARK};">Version</th>
        <th style="padding:10px 12px;font-size:8.5pt;color:${DARK};">Date</th>
        <th style="padding:10px 12px;font-size:8.5pt;color:${DARK};">Author</th>
        <th style="padding:10px 12px;font-size:8.5pt;color:${DARK};">Description of Changes</th>
      </tr>
    </thead>
    <tbody>
      <tr style="border-top:1px solid #E5E7EB;">
        <td style="padding:8px 12px;font-size:9pt;font-weight:700;color:${RED};">1.0</td>
        <td style="padding:8px 12px;font-size:9pt;">May 2026</td>
        <td style="padding:8px 12px;font-size:9pt;">System Administrator</td>
        <td style="padding:8px 12px;font-size:9pt;">Initial release covering all portal features: attendance, jobs, leave, OT, payroll, staff, projects, vans, QR, reporting.</td>
      </tr>
    </tbody>
  </table>

  <div style="margin-top:32px;padding:16px;background:${LIGHT};border-radius:8px;text-align:center;">
    <p style="font-size:8pt;color:${GREY};">This document is the property of <strong>Bespoke Garden Rooms Ballycastle</strong>. It is classified as <strong>INTERNAL</strong> and must not be distributed outside the organisation without authorisation from the Director.</p>
    <p style="font-size:8pt;color:${GREY};margin-top:4px;">Document No. BCF-SOP-001 · Version 1.0 · Issue Date: May 2026 · Next Review: May 2027</p>
  </div>
</div>

</body>
</html>`;

// ─── GENERATE PDF ──────────────────────────────────────────────────────────────

const browser = await puppeteer.launch({
    executablePath,
    headless: 'new',
    args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-dev-shm-usage'],
});

const page = await browser.newPage();
await page.setContent(HTML, { waitUntil: 'networkidle0', timeout: 30000 });

await page.pdf({
    path: out,
    format: 'A4',
    printBackground: true,
    displayHeaderFooter: true,
    headerTemplate: `<div></div>`,
    footerTemplate: `
      <div style="font-size:7pt;color:#9CA3AF;width:100%;text-align:center;padding:0 18mm;font-family:Arial,sans-serif;">
        BCF Staff Portal — Standard Operating Procedure (BCF-SOP-001) &nbsp;|&nbsp;
        INTERNAL &nbsp;|&nbsp; Page <span class="pageNumber"></span> of <span class="totalPages"></span>
      </div>`,
    margin: { top: '18mm', bottom: '22mm', left: '0', right: '0' },
});

await browser.close();
console.log('✅  SOP PDF generated:', out);
