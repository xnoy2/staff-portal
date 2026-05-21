/**
 * BCF Staff Portal — Role-Specific User Guide PDF Generator
 * Generates 4 PDFs: Staff, Site Head, HR, Manager
 * Run: node scripts/generate-role-guides-pdf.mjs
 * Output: docs/guide-staff.pdf  docs/guide-sitehead.pdf
 *         docs/guide-hr.pdf     docs/guide-manager.pdf
 */

import { existsSync, mkdirSync } from 'fs';
import { resolve, dirname } from 'path';
import { fileURLToPath } from 'url';
import puppeteer from 'puppeteer-core';

const __dir = dirname(fileURLToPath(import.meta.url));
const root  = resolve(__dir, '..');
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
if (!executablePath) { console.error('No Chrome/Edge found.'); process.exit(1); }
console.log('Browser:', executablePath);

// ─── PALETTE ──────────────────────────────────────────────────────────────────
const RED    = '#EF233C';
const DARK   = '#2B2D42';
const GREY   = '#8D99AE';
const LIGHT  = '#EDF2F4';
const GREEN  = '#16A34A';
const AMBER  = '#D97706';
const BLUE   = '#2563EB';
const PURPLE = '#7C3AED';

// ─── SHARED HELPERS ───────────────────────────────────────────────────────────

const pb = () => `<div style="page-break-after:always"></div>`;

const css = (accent) => `
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');
@page { size: A4; margin: 0; }
@page body { margin: 18mm 18mm 22mm; }
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Inter', Arial, sans-serif; font-size: 10pt; color: #374151; line-height: 1.6; background: white; }
.body-page { padding: 18mm 18mm 8mm; }
table { border-collapse: collapse; width: 100%; }
p  { margin-bottom: 8px; }
ul, ol { padding-left: 20px; margin-bottom: 10px; }
li { margin-bottom: 5px; font-size: 9.5pt; line-height: 1.55; }
.rh  { display:flex;align-items:center;justify-content:space-between;padding-bottom:8px;border-bottom:2px solid ${LIGHT};margin-bottom:22px; }
.rh-l { font-size:8pt;color:${GREY};font-weight:600; }
.rh-r { font-size:8pt;color:${accent};font-weight:700; }
`;

const runHead = (role, accent) =>
    `<div class="rh"><span class="rh-l">BCF Staff Portal — ${role} User Guide</span><span class="rh-r">${role.toUpperCase()}</span></div>`;

// COVER
const cover = (accent, gradEnd, role, emoji, tagline, docNo) => `
<div style="page-break-after:always;min-height:297mm;background:${DARK};display:flex;flex-direction:column;">
  <div style="height:6px;background:linear-gradient(to right,${accent},${gradEnd});"></div>

  <div style="padding:44px 56px 0;display:flex;align-items:center;gap:14px;">
    <div style="width:44px;height:44px;background:${accent};border-radius:10px;display:flex;align-items:center;justify-content:center;">
      <div style="width:22px;height:22px;background:white;border-radius:5px;"></div>
    </div>
    <div>
      <p style="color:white;font-size:10.5pt;font-weight:700;letter-spacing:.5px;">Bespoke Garden Rooms Ballycastle</p>
      <p style="color:${GREY};font-size:8.5pt;">Staff Management Portal</p>
    </div>
  </div>

  <div style="flex:1;display:flex;flex-direction:column;justify-content:center;padding:40px 56px;">
    <div style="width:72px;height:72px;background:${accent}25;border:2px solid ${accent}50;border-radius:20px;display:flex;align-items:center;justify-content:center;font-size:32pt;margin-bottom:28px;">${emoji}</div>
    <p style="color:${accent};font-size:9pt;font-weight:700;letter-spacing:3px;text-transform:uppercase;margin-bottom:10px;">User Guide</p>
    <h1 style="color:white;font-size:34pt;font-weight:900;line-height:1.1;margin-bottom:10px;">${role}<br><span style="color:${accent}">Handbook</span></h1>
    <p style="color:${GREY};font-size:11pt;max-width:380px;line-height:1.65;margin-top:4px;">${tagline}</p>

    <div style="display:flex;gap:40px;margin-top:44px;padding-top:28px;border-top:1px solid rgba(255,255,255,0.1);">
      <div><p style="color:${GREY};font-size:7.5pt;font-weight:700;letter-spacing:1px;text-transform:uppercase;">Document</p><p style="color:white;font-size:9.5pt;font-weight:700;margin-top:3px;">${docNo}</p></div>
      <div><p style="color:${GREY};font-size:7.5pt;font-weight:700;letter-spacing:1px;text-transform:uppercase;">Version</p><p style="color:white;font-size:9.5pt;font-weight:700;margin-top:3px;">1.0</p></div>
      <div><p style="color:${GREY};font-size:7.5pt;font-weight:700;letter-spacing:1px;text-transform:uppercase;">Issue Date</p><p style="color:white;font-size:9.5pt;font-weight:700;margin-top:3px;">May 2026</p></div>
      <div><p style="color:${GREY};font-size:7.5pt;font-weight:700;letter-spacing:1px;text-transform:uppercase;">For</p><span style="display:inline-block;margin-top:3px;background:${accent};color:white;font-size:8pt;font-weight:700;padding:2px 10px;border-radius:20px;">${role}</span></div>
    </div>
  </div>
  <div style="height:4px;background:linear-gradient(to right,${accent},${gradEnd});"></div>
</div>`;

// QUICK REFERENCE CARD
const quickRef = (accent, role, cards) => `
<div class="body-page" style="page-break-after:always;">
  <div style="background:${accent};border-radius:16px;padding:20px 24px;margin-bottom:22px;display:flex;align-items:center;gap:14px;">
    <div style="font-size:22pt;">⚡</div>
    <div><p style="color:white;font-size:13pt;font-weight:800;">Quick Reference</p><p style="color:rgba(255,255,255,0.75);font-size:9pt;">The most common actions for ${role}s at a glance</p></div>
  </div>
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
    ${cards.map(c => `
    <div style="border:1px solid #E5E7EB;border-radius:12px;padding:14px 16px;">
      <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
        <span style="font-size:18pt;line-height:1;">${c.icon}</span>
        <p style="font-size:9.5pt;font-weight:700;color:${DARK};">${c.title}</p>
      </div>
      <ol style="padding-left:16px;margin:0;">
        ${c.steps.map(s => `<li style="font-size:8.5pt;color:#4B5563;margin-bottom:3px;line-height:1.5;">${s}</li>`).join('')}
      </ol>
    </div>`).join('')}
  </div>
</div>`;

// CHAPTER HEADER
const chapterHead = (accent, num, title, sub) => `
<div style="margin-bottom:18px;padding-bottom:12px;border-bottom:2px solid ${LIGHT};">
  <div style="display:flex;align-items:center;gap:10px;">
    <div style="min-width:34px;height:34px;background:${accent};border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:9pt;font-weight:900;color:white;">${num}</div>
    <h2 style="font-size:14pt;font-weight:800;color:${DARK};">${title}</h2>
  </div>
  ${sub ? `<p style="font-size:9pt;color:${GREY};margin-top:5px;padding-left:44px;">${sub}</p>` : ''}
</div>`;

const subHead = (accent, title) =>
    `<h3 style="font-size:10pt;font-weight:700;color:${DARK};padding:7px 12px;background:${LIGHT};border-radius:6px;border-left:3px solid ${accent};margin:18px 0 10px;">${title}</h3>`;

const step = (accent, n, text, note='') => `
<div style="display:flex;gap:11px;margin-bottom:9px;align-items:flex-start;">
  <div style="min-width:22px;height:22px;background:${accent};color:white;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:8pt;font-weight:800;flex-shrink:0;margin-top:2px;">${n}</div>
  <div><p style="font-size:9.5pt;color:${DARK};line-height:1.55;">${text}</p>${note ? `<p style="font-size:8.5pt;color:${GREY};margin-top:2px;">${note}</p>` : ''}</div>
</div>`;

const callout = (icon, text, color, textColor) => `
<div style="display:flex;gap:10px;background:${color}0D;border:1px solid ${color}35;border-radius:8px;padding:11px 14px;margin:10px 0;">
  <span style="font-size:13pt;flex-shrink:0;line-height:1.4;">${icon}</span>
  <p style="font-size:8.5pt;color:${textColor};line-height:1.6;">${text}</p>
</div>`;

const tip     = (t) => callout('💡', t, AMBER,  '#92400E');
const info    = (t) => callout('ℹ️',  t, BLUE,   '#1E40AF');
const warning = (t) => callout('⚠️', t, RED,    '#991B1B');
const success = (t) => callout('✅', t, GREEN,  '#14532D');

const featureGrid = (items) => `
<div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin:10px 0;">
${items.map(item => `
  <div style="display:flex;gap:10px;padding:10px 12px;background:${LIGHT};border-radius:9px;align-items:flex-start;">
    <span style="font-size:15pt;flex-shrink:0;line-height:1.2;">${item.icon}</span>
    <div><p style="font-size:9pt;font-weight:700;color:${DARK};">${item.title}</p><p style="font-size:8.5pt;color:#4B5563;margin-top:2px;line-height:1.5;">${item.desc}</p></div>
  </div>`).join('')}
</div>`;

const twoCol = (left, right) => `
<div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">${left}${right}</div>`;

const statusTable = (rows) => `
<table style="border:1px solid #E5E7EB;border-radius:8px;overflow:hidden;margin:10px 0;font-size:9pt;">
  <thead style="background:${DARK};">
    <tr>${rows.headers.map(h => `<th style="padding:8px 12px;color:white;text-align:left;font-size:8.5pt;">${h}</th>`).join('')}</tr>
  </thead>
  <tbody>
    ${rows.body.map((r,i) => `<tr style="border-top:1px solid #E5E7EB;background:${i%2===0?'white':'#FAFAFA'};">${r.map(c => `<td style="padding:8px 12px;font-size:9pt;color:${DARK};">${c}</td>`).join('')}</tr>`).join('')}
  </tbody>
</table>`;

const faqSection = (accent, faqs) => `
<div style="space-y:10px;">
${faqs.map(f => `
  <div style="border:1px solid #E5E7EB;border-radius:9px;overflow:hidden;margin-bottom:10px;">
    <div style="background:${accent}10;border-bottom:1px solid ${accent}20;padding:9px 14px;display:flex;gap:8px;align-items:flex-start;">
      <span style="color:${accent};font-weight:800;font-size:10pt;flex-shrink:0;">Q</span>
      <p style="font-size:9.5pt;font-weight:600;color:${DARK};">${f.q}</p>
    </div>
    <div style="padding:9px 14px;display:flex;gap:8px;align-items:flex-start;">
      <span style="color:${GREY};font-weight:800;font-size:10pt;flex-shrink:0;">A</span>
      <p style="font-size:9pt;color:#374151;line-height:1.6;">${f.a}</p>
    </div>
  </div>`).join('')}
</div>`;

// ─── HTML WRAPPER ─────────────────────────────────────────────────────────────
const wrap = (accent, body) => `<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"/>
<style>${css(accent)}</style></head><body>${body}</body></html>`;

// ─── GUIDE BUILDER ────────────────────────────────────────────────────────────
async function buildPDF(browser, filename, html) {
    const page = await browser.newPage();
    await page.setContent(html, { waitUntil: 'networkidle0', timeout: 30000 });
    const out = resolve(root, 'docs', filename);
    await page.pdf({
        path: out,
        format: 'A4',
        printBackground: true,
        displayHeaderFooter: true,
        headerTemplate: `<div></div>`,
        footerTemplate: `<div style="font-size:7pt;color:#9CA3AF;width:100%;text-align:center;padding:0 18mm;font-family:Arial,sans-serif;">BCF Staff Portal · User Guide · Page <span class="pageNumber"></span> of <span class="totalPages"></span> · INTERNAL</div>`,
        margin: { top: '10mm', bottom: '14mm', left: '0', right: '0' },
    });
    await page.close();
    console.log('✅  Generated:', out);
}

// ═══════════════════════════════════════════════════════════════════════════════
// GUIDE 1 — STAFF
// ═══════════════════════════════════════════════════════════════════════════════
function staffGuide() {
    const A = GREEN;
    const rh = (t='Staff') => runHead(t, A);

    return wrap(A, `
${cover(A, '#059669', 'Staff', '👷', 'Your complete guide to clocking in, managing your jobs, leave, overtime, and pay through the BCF Staff Portal.', 'BCF-UG-STAFF-001')}

${quickRef(A, 'Staff', [
    { icon: '🟢', title: 'Clock In', steps: ['Go to Dashboard or Attendance', 'Press "Clock In"', 'Your live timer starts'] },
    { icon: '🔴', title: 'Clock Out', steps: ['Go to Dashboard or Attendance', 'Press "Clock Out"', 'Your hours are saved'] },
    { icon: '☕', title: 'Take a Break', steps: ['While clocked in, press "Take Break" or "Lunch Break"', 'Press "End Break" when you return'] },
    { icon: '📋', title: 'View My Jobs', steps: ['Click "My Jobs" in the left menu', 'See Upcoming, Past, or All jobs', 'Press ▶ Start or ✓ Complete to update'] },
    { icon: '🌴', title: 'Request Leave', steps: ['Go to "Leave" in the menu', 'Click "New Request"', 'Choose type, dates, then Submit'] },
    { icon: '💰', title: 'View My Payslip', steps: ['Go to "My Payslip" in the menu', 'Select a pay period to view the breakdown'] },
])}

<div class="body-page">
${rh()}
${chapterHead(A, '1', 'Getting Started', 'Logging in and understanding your dashboard')}

${subHead(A, 'Logging In')}
${step(A, 1, 'Open a browser and go to <strong>https://staff.bespokegardenroomsballycastle.co.uk</strong>')}
${step(A, 2, 'Enter your <strong>email address</strong> and <strong>password</strong>, then click <strong>Log In</strong>.')}
${step(A, 3, '<strong>First time?</strong> If you were given a temporary password, you\'ll be asked to create a new one before you can continue.')}
${warning('Your password is private — never share it. If you\'re locked out, contact your manager or HR to get a reset.')}

${subHead(A, 'Your Dashboard')}
<p style="font-size:9.5pt;margin-bottom:10px;">After logging in, your Dashboard gives you a snapshot of today:</p>
${featureGrid([
    { icon: '⏱️', title: 'Clock-In Widget',    desc: 'Shows if you\'re clocked in with a live timer. Use it to clock in/out and take breaks.' },
    { icon: '📊', title: 'Weekly Hours Chart', desc: 'A bar chart of your approved hours for each day this week (Mon–Sun).' },
    { icon: '📋', title: 'Upcoming Jobs',       desc: 'Your next 5 scheduled or in-progress jobs with date, time, and project.' },
    { icon: '🌴', title: 'Leave Balance',       desc: 'Your annual leave entitlement, days used, pending, and days remaining.' },
])}
</div>
${pb()}

<div class="body-page">
${rh()}
${chapterHead(A, '2', 'Clocking In & Out', 'Recording your work time every day')}

${subHead(A, 'Clocking In')}
${step(A, 1, 'Go to the <strong>Dashboard</strong> or click <strong>Attendance</strong> in the left menu.')}
${step(A, 2, 'Press the <strong>"Clock In"</strong> button.', 'If you have an approved overtime request for today, a popup asks you to select: Regular, OT, or RDOT.')}
${step(A, 3, 'You\'re now clocked in. A green live timer shows how long you\'ve been working.')}
${tip('Clock in as close as possible to your actual start time. Your manager reviews entries for accuracy.')}

${subHead(A, 'Clocking Out')}
${step(A, 1, 'Return to the <strong>Dashboard</strong> or <strong>Attendance</strong> page.')}
${step(A, 2, 'Press the <strong>"Clock Out"</strong> button.')}
${step(A, 3, 'Your shift is recorded. Total hours are calculated automatically (minus any break time).')}
${step(A, 4, 'Your entry goes to <strong>Pending</strong> — your manager will review and approve it.')}
${warning('You can only be clocked in once at a time. If you forget to clock in or out, tell your manager — they can add or fix an entry for you.')}

${subHead(A, 'Taking Breaks')}
${step(A, 1, 'While clocked in, press <strong>"Take Break"</strong> (short break) or <strong>"Lunch Break"</strong>.')}
${step(A, 2, 'Your status changes and the work timer pauses. Your break is being tracked.')}
${step(A, 3, 'When you return, press <strong>"End Break"</strong>. The break duration is saved and deducted from your hours.')}

${subHead(A, 'Clock-In Status')}
${statusTable({
    headers: ['Status', 'What it means', 'Indicator colour'],
    body: [
        ['Working',   'Actively clocked in and working',          '🟢 Green'],
        ['On Break',  'Taking a short break',                     '🟡 Amber'],
        ['On Lunch',  'Taking a lunch break',                     '🟡 Amber'],
        ['Clocked Out', 'Shift has ended for today',              '⚫ Grey'],
    ]
})}
</div>
${pb()}

<div class="body-page">
${rh()}
${chapterHead(A, '3', 'My Jobs', 'Viewing and updating your assigned work')}

<p style="font-size:9.5pt;margin-bottom:12px;">The <strong>My Jobs</strong> page (in the left menu) shows all jobs you\'ve been assigned to — upcoming, in-progress, and completed — grouped by date.</p>

${subHead(A, 'Navigating My Jobs')}
${featureGrid([
    { icon: '📅', title: 'Upcoming', desc: 'Default view — jobs from today forward, sorted by soonest first.' },
    { icon: '🕐', title: 'Past',     desc: 'Jobs from previous dates — check your history.' },
    { icon: '📋', title: 'All',      desc: 'Every job regardless of date.' },
    { icon: '🔍', title: 'Filters',  desc: 'Filter by status (Scheduled, In Progress, Completed) or date range.' },
])}

${subHead(A, 'Updating a Job Status')}
<p style="font-size:9.5pt;margin-bottom:8px;">When you arrive on site or finish a job, update its status:</p>
${step(A, 1, 'Find the job in <strong>My Jobs</strong> (it will show a status badge).')}
${step(A, 2, 'If the job is <strong>Scheduled</strong>, press <strong>"▶ Start"</strong> to mark it as In Progress.')}
${step(A, 3, 'Once the work is done, press <strong>"✓ Complete"</strong> to mark it as Completed.')}
${info('Updating the job status keeps your manager informed of progress in real time. Completed jobs automatically update any linked project checklist.')}

${subHead(A, 'Job Status Guide')}
${statusTable({
    headers: ['Status', 'Meaning', 'Your action'],
    body: [
        ['📘 Scheduled',   'Job is planned but not yet started',   'Press ▶ Start when you begin'],
        ['🟡 In Progress', 'Job is underway',                      'Press ✓ Complete when done'],
        ['🟢 Completed',   'Job is finished',                      'No further action needed'],
        ['⚫ Cancelled',   'Job has been cancelled',               'Contact your manager if queried'],
    ]
})}

${subHead(A, 'Job Information')}
<p style="font-size:9.5pt;">Each job card shows: date, start/end time, project name, van registration, and your assigned crew members.</p>
</div>
${pb()}

<div class="body-page">
${rh()}
${chapterHead(A, '4', 'Leave Requests', 'Requesting time off')}

${step(A, 1, 'Click <strong>Leave</strong> in the left navigation.')}
${step(A, 2, 'Click <strong>"New Request"</strong>.')}
${step(A, 3, 'Select the <strong>Leave Type</strong>: Annual, Sick, Compassionate, or Other.')}
${step(A, 4, 'Pick your <strong>start date</strong> and <strong>end date</strong>. The number of working days is calculated automatically.')}
${step(A, 5, 'Add an optional note (e.g. reason, doctor\'s appointment details), then click <strong>Submit</strong>.')}
${step(A, 6, 'Your request shows as <strong>Pending</strong> until your manager responds. You\'ll get a notification of the decision.')}

${subHead(A, 'Leave Types')}
${statusTable({
    headers: ['Type', 'Deducts Annual Leave?', 'When to use'],
    body: [
        ['Annual Leave',    'Yes', 'Planned holidays or personal days off'],
        ['Sick Leave',      'No',  'Illness or medical appointments'],
        ['Compassionate',   'No',  'Bereavement, family emergency'],
        ['Other',           'Varies', 'Any other agreed absence'],
    ]
})}
${tip('Check your leave balance on the Dashboard <strong>before</strong> you submit. If your remaining days are less than the request, it may be declined.')}

${chapterHead(A, '5', 'Overtime Requests', 'Getting OT pre-approved before working extra hours')}

<p style="font-size:9.5pt;margin-bottom:10px;">If you\'re asked to work overtime, submit a request <strong>before</strong> the day so your manager can approve it.</p>
${step(A, 1, 'Click <strong>Overtime</strong> in the left menu.')}
${step(A, 2, 'Click <strong>"New Request"</strong>. Choose the date, type (<strong>OT</strong> or <strong>RDOT</strong>), and add a note.')}
${step(A, 3, 'Submit. Your manager is notified and will approve or decline.')}
${step(A, 4, 'Once approved, on that date you\'ll see an OT option when you clock in.')}

${featureGrid([
    { icon: '🕐', title: 'OT (Overtime)',  desc: 'Standard overtime on a normal working day. All clocked hours count as OT hours in payroll.' },
    { icon: '📆', title: 'RDOT (Rest Day)', desc: 'Working on a day off or public holiday. All hours are overtime regardless of duration.' },
])}
</div>
${pb()}

<div class="body-page">
${rh()}
${chapterHead(A, '6', 'My Payslip', 'Understanding your pay breakdown')}

<p style="font-size:9.5pt;margin-bottom:10px;">Go to <strong>My Payslip</strong> in the left navigation to see your pay for each period. Your payslip is generated by your manager and locked once approved.</p>

${featureGrid([
    { icon: '⏰', title: 'Regular Hours',  desc: 'Hours worked within your standard shift (up to 8h per day on normal shifts).' },
    { icon: '💼', title: 'Overtime Hours', desc: 'Hours beyond 8h, or all hours on an approved OT/RDOT shift.' },
    { icon: '💷', title: 'Gross Pay',      desc: 'Total pay before deductions (regular rate × regular hours + OT rate × OT hours).' },
    { icon: '➖', title: 'Deductions',     desc: 'Any amounts taken off (e.g. advance repayments). Listed with descriptions.' },
    { icon: '✅', title: 'Net Pay',        desc: 'Your take-home amount after all deductions.' },
    { icon: '🔒', title: 'Approved Lock',  desc: 'Once your manager approves, the payslip is locked and cannot be changed.' },
])}
${info('If you believe there\'s an error on your payslip, contact your manager or HR <strong>before</strong> it is approved — approved payslips are locked.')}

${chapterHead(A, '7', 'My QR Code', 'Your contactless clock-in ID')}

<p style="font-size:9.5pt;margin-bottom:10px;">Each staff member has a unique QR code. A site manager or site head can scan it to clock you in or out on-site when you don\'t have access to a device.</p>
${step(A, 1, 'Go to <strong>"My QR Code"</strong> in the left navigation.')}
${step(A, 2, 'Your QR code is displayed on screen. Tap <strong>"Download"</strong> to save it.')}
${step(A, 3, 'Show it to your site head or manager when on site. They\'ll scan it with the QR Scanner.')}
${tip('Save a screenshot of your QR code to your phone\'s photos — then you always have it even without internet access.')}

${chapterHead(A, '8', 'Profile & Account', 'Managing your personal settings')}
${step(A, 1, 'Click your <strong>name or avatar</strong> at the top-right corner of any page to open your profile.')}
${step(A, 2, 'From the <strong>Profile</strong> tab you can update your photo, name, and contact details.')}
${step(A, 3, 'From the <strong>Security</strong> tab you can change your password.')}
${warning('If you\'re asked to set a new password (forced reset), you must complete it before using the portal. Use a password that\'s at least 8 characters with a mix of letters, numbers, and symbols.')}
</div>
${pb()}

<div class="body-page">
${rh()}
${chapterHead(A, '9', 'Frequently Asked Questions')}
${faqSection(A, [
    { q: 'I forgot to clock in. What do I do?', a: 'Don\'t try to create a duplicate entry. Tell your manager or HR as soon as possible and they will add a manual time entry for you with the correct times.' },
    { q: 'I forgot to clock out. Is my shift still saved?', a: 'Yes — your clock-in is recorded. Your manager will see the entry is still open and can update it with your clock-out time. Let them know what time you finished.' },
    { q: 'My attendance entry shows as Rejected. What now?', a: 'Your manager will have added a reason. Contact them directly to discuss. They may ask you to re-submit or will create a corrected manual entry.' },
    { q: 'Can I take more than one break in a shift?', a: 'You can only have one active break at a time. End your current break before starting another. Multiple breaks across a shift are supported.' },
    { q: 'I can\'t see a job I was assigned to.', a: 'Check the "All" tab in My Jobs to see every job regardless of date. If it\'s still not there, contact your manager — you may not have been assigned yet.' },
    { q: 'My leave request has been pending for a long time.', a: 'Your manager may not have reviewed it yet. You can also see the status in the Leave page. If it\'s urgent, speak to them directly.' },
    { q: 'I can\'t log in / forgot my password.', a: 'You cannot reset your own password. Contact your manager or HR — they\'ll generate a temporary password that you\'ll be required to change on first login.' },
    { q: 'Where can I see my attendance history?', a: 'Go to "Attendance" in the left menu. You\'ll see all your clock-in records with times, hours, break minutes, and approval status.' },
])}
</div>
`);
}

// ═══════════════════════════════════════════════════════════════════════════════
// GUIDE 2 — SITE HEAD
// ═══════════════════════════════════════════════════════════════════════════════
function siteHeadGuide() {
    const A = AMBER;
    const rh = () => runHead('Site Head', A);

    return wrap(A, `
${cover(A, '#F59E0B', 'Site Head', '🪪', 'Your guide to managing on-site jobs, scanning staff QR codes, and keeping your projects running smoothly.', 'BCF-UG-SH-001')}

${quickRef(A, 'Site Head', [
    { icon: '📷', title: 'Scan Staff QR',  steps: ['Go to "QR Scanner" in Field menu', 'Allow camera access', 'Point at staff QR code — auto-confirms'] },
    { icon: '▶️', title: 'Start a Job',    steps: ['Open My Jobs → Site Jobs', 'Find the job, press ▶ Start', 'Status changes to In Progress'] },
    { icon: '✅', title: 'Complete a Job', steps: ['Find the In Progress job', 'Press ✓ Complete', 'Checklist item auto-updates'] },
    { icon: '🟢', title: 'Clock In',       steps: ['Go to Dashboard or Attendance', 'Press "Clock In"'] },
    { icon: '🌴', title: 'Request Leave',  steps: ['Go to "Leave" in the menu', 'Click "New Request" and submit'] },
    { icon: '💷', title: 'View Payslip',   steps: ['Go to "My Payslip" in the menu', 'Select a pay period to view'] },
])}

<div class="body-page">
${rh()}
${chapterHead(A, '1', 'Your Role as Site Head', 'What you can access and manage')}

<p style="font-size:9.5pt;margin-bottom:12px;">As a Site Head, you have all the standard staff functions <em>plus</em> the ability to manage jobs on your assigned project sites and scan staff QR codes for clock-in/out.</p>

${featureGrid([
    { icon: '🏗️', title: 'Site Jobs',       desc: 'View and manage all jobs linked to projects you\'ve been assigned to as site head.' },
    { icon: '📷', title: 'QR Scanner',      desc: 'Scan staff QR codes on site to clock them in or out without needing their device.' },
    { icon: '▶️', title: 'Job Status',      desc: 'Start and complete jobs on your projects. Cannot create or delete jobs.' },
    { icon: '👷', title: 'Your Own Time',   desc: 'Clock in/out, request leave, view payslip — same as standard staff.' },
])}

${chapterHead(A, '2', 'Site Jobs', 'Managing jobs on your assigned projects')}

${subHead(A, 'Viewing Site Jobs')}
<p style="font-size:9.5pt;margin-bottom:8px;">Go to <strong>My Jobs</strong> (or "Site Jobs") in the left navigation. You\'ll see all jobs linked to projects where you\'ve been assigned as site head — not just jobs assigned directly to you.</p>

${featureGrid([
    { icon: '📅', title: 'Upcoming', desc: 'Jobs from today forward, sorted by soonest first.' },
    { icon: '🕐', title: 'Past',     desc: 'Completed or cancelled jobs from previous dates.' },
    { icon: '🔍', title: 'Filter',   desc: 'Filter by status (Scheduled / In Progress / Completed) or custom date range.' },
    { icon: '📆', title: 'Calendar', desc: 'Use the Live Board link on each job row to jump to that day\'s full board.' },
])}

${subHead(A, 'Starting a Job')}
${step(A, 1, 'Find the job in <strong>Site Jobs</strong> — it will show status <strong>Scheduled</strong>.')}
${step(A, 2, 'Press the <strong>"▶ Start"</strong> button on the job row.')}
${step(A, 3, 'Status changes to <strong>In Progress</strong>. Your manager sees this update in real time on the Live Board.')}

${subHead(A, 'Completing a Job')}
${step(A, 1, 'When the work is done, find the job with status <strong>In Progress</strong>.')}
${step(A, 2, 'Press the <strong>"✓ Complete"</strong> button.')}
${step(A, 3, 'The job is marked <strong>Completed</strong>. The linked project checklist item is automatically ticked.')}
${step(A, 4, 'If the job was linked to a BCF order stage, that stage is automatically marked as done in the BCF system.')}
${info('You cannot create, edit, or delete jobs — only start and complete them. For any scheduling changes, contact your manager.')}
</div>
${pb()}

<div class="body-page">
${rh()}
${chapterHead(A, '3', 'QR Scanner', 'Clocking staff in and out on site')}

<p style="font-size:9.5pt;margin-bottom:10px;">The QR Scanner lets you use your device\'s camera to scan a staff member\'s QR code, recording their clock-in or clock-out instantly without them needing access to their own device.</p>

${subHead(A, 'How to Scan')}
${step(A, 1, 'Go to <strong>QR Scanner</strong> in the <strong>Field</strong> section of the left navigation.')}
${step(A, 2, 'When prompted, tap <strong>Allow</strong> to grant camera access.')}
${step(A, 3, 'Point the camera at the staff member\'s QR code. Hold steady — the scan happens in 1–2 seconds.')}
${step(A, 4, 'A confirmation shows the staff member\'s name and whether they\'ve been clocked <strong>in</strong> or <strong>out</strong>.')}
${step(A, 5, 'The record is saved automatically. The staff member does not need to do anything.')}

${subHead(A, 'Scan Behaviour')}
${statusTable({
    headers: ['Staff state before scan', 'Result after scan'],
    body: [
        ['Not clocked in',         'Clock-In recorded — shift starts from scan time'],
        ['Currently clocked in',   'Clock-Out recorded — shift ends at scan time'],
        ['On break',               'Break is ended, then Clock-Out is recorded'],
    ]
})}
${warning('QR scanning requires an internet connection. If offline, note the arrival/departure time and add it as a manual attendance entry when you\'re back online.')}

${tip('Ask staff to keep a screenshot of their QR code in their phone\'s camera roll so it\'s always available even without opening the portal.')}

${chapterHead(A, '4', 'Your Own Attendance, Leave & Payslip')}
<p style="font-size:9.5pt;margin-bottom:10px;">As a site head, you also have all the same personal features as standard staff:</p>

${featureGrid([
    { icon: '🟢', title: 'Clock In/Out',    desc: 'Use the Dashboard or Attendance page to record your own shifts and breaks.' },
    { icon: '🌴', title: 'Leave Requests',  desc: 'Submit annual leave, sick leave, or other absence requests under Leave in the menu.' },
    { icon: '💼', title: 'Overtime',        desc: 'Request OT or RDOT approval before working extra hours.' },
    { icon: '💷', title: 'My Payslip',      desc: 'View your pay breakdown for each period under My Payslip.' },
    { icon: '📱', title: 'My QR Code',      desc: 'View and download your own QR code under My QR Code.' },
    { icon: '📋', title: 'Attendance Log',  desc: 'View your full clock-in history and entry statuses under Attendance.' },
])}

${chapterHead(A, '5', 'Frequently Asked Questions')}
${faqSection(A, [
    { q: 'Why can\'t I see some jobs in Site Jobs?', a: 'You only see jobs linked to projects where you\'ve been assigned as site head. If a project is missing, ask your manager to assign you to it.' },
    { q: 'I scanned a QR code but nothing happened.', a: 'Check your internet connection. Also make sure the QR code is clear and well-lit. If the issue persists, manually note the time and ask a manager to add the entry.' },
    { q: 'Can I create or delete a job?', a: 'No — site heads can only start and complete jobs. Creating, editing, and deleting jobs is reserved for Managers and Admins.' },
    { q: 'A staff member\'s QR code won\'t scan.', a: 'The code may be damaged or too small on screen. Ask the staff member to zoom in or increase their screen brightness. If it still fails, contact a manager for a manual clock-in entry.' },
    { q: 'I completed a job by mistake. Can it be undone?', a: 'Contact your manager — they can re-open the job and set it back to In Progress.' },
])}
</div>
`);
}

// ═══════════════════════════════════════════════════════════════════════════════
// GUIDE 3 — HR
// ═══════════════════════════════════════════════════════════════════════════════
function hrGuide() {
    const A = BLUE;
    const rh = () => runHead('HR', A);

    return wrap(A, `
${cover(A, '#1D4ED8', 'HR', '🧾', 'Your complete guide to managing staff attendance, payroll, and personnel records through the BCF Staff Portal.', 'BCF-UG-HR-001')}

${quickRef(A, 'HR', [
    { icon: '✅', title: 'Approve Attendance', steps: ['Go to Attendance → filter Pending', 'Review entries', 'Click Approve or Bulk Approve'] },
    { icon: '✍️', title: 'Manual Entry',       steps: ['Go to Attendance → Manual Entry', 'Select staff, date, times', 'Submit (auto-approved)'] },
    { icon: '💰', title: 'Generate Payroll',   steps: ['Go to Payroll', 'Click Generate Run', 'Enter period dates → Generate'] },
    { icon: '📥', title: 'Export Attendance',  steps: ['Go to Attendance', 'Set filters (date, status)', 'Click Export CSV'] },
    { icon: '👤', title: 'View Staff Profile', steps: ['Go to Staff in HR section', 'Click a staff member\'s name', 'View profile, history, balance'] },
    { icon: '🔑', title: 'Reset Password',     steps: ['Open staff profile', 'Click Reset Password', 'Copy temp password & share securely'] },
])}

<div class="body-page">
${rh()}
${chapterHead(A, '1', 'Your HR Dashboard', 'What you see as an HR user')}

<p style="font-size:9.5pt;margin-bottom:12px;">When you log in, your Dashboard shows your own status (clock-in widget, upcoming jobs, leave balance). You also have access to HR-specific sections in the navigation: <strong>Staff</strong>, <strong>Payroll</strong>, and full <strong>Attendance</strong> management.</p>

${featureGrid([
    { icon: '👥', title: 'Staff Management',   desc: 'View all staff profiles, employment details, hourly rates, and account statuses.' },
    { icon: '⏱️', title: 'All Attendance',     desc: 'See clock-in records for every staff member — approve, reject, add manual entries, and export.' },
    { icon: '💷', title: 'Payroll',            desc: 'Generate pay runs, review individual payslips, add deductions, and approve.' },
    { icon: '📊', title: 'Leave (view only)',  desc: 'View all staff leave requests and their statuses. HR cannot approve leave — only Admin/Manager.' },
])}

${chapterHead(A, '2', 'Attendance Management', 'Reviewing and approving staff time entries')}

${subHead(A, '2.1 Approving Time Entries')}
<p style="font-size:9.5pt;margin-bottom:8px;"><em>Daily review is recommended to keep payroll data accurate.</em></p>
${step(A, 1, 'Go to <strong>Attendance</strong> in the HR section of the left navigation.')}
${step(A, 2, 'Set the <strong>Status</strong> filter to <strong>Pending</strong> to show only entries awaiting review.')}
${step(A, 3, 'Review each entry — check clock-in time, clock-out time, and total hours.')}
${step(A, 4, 'Click <strong>Approve</strong> on correct entries, or use <strong>Bulk Approve</strong> to approve multiple at once.')}
${step(A, 5, 'Click <strong>Reject</strong> and enter a reason if an entry is incorrect. The staff member will be notified.')}
${warning('Only APPROVED entries are included in payroll calculations. Entries left as Pending will not be paid until approved.')}

${subHead(A, '2.2 Rejecting an Entry')}
${step(A, 1, 'Click the <strong>Reject</strong> button on the entry.')}
${step(A, 2, 'Enter a reason (e.g. "Clock-in time appears incorrect — please verify").')}
${step(A, 3, 'Click <strong>Confirm Reject</strong>. The staff member is notified and the entry is flagged.')}
${info('After rejecting an entry, you or the manager may need to add a corrected manual entry if the staff member was legitimately working.')}
</div>
${pb()}

<div class="body-page">
${rh()}
${subHead(A, '2.3 Adding a Manual Time Entry')}
<p style="font-size:9.5pt;margin-bottom:8px;"><em>Use this when a staff member forgot to clock in/out, had a system issue, or was on site without a device.</em></p>
${step(A, 1, 'Go to <strong>Attendance</strong> and click the <strong>"Manual Entry"</strong> button.')}
${step(A, 2, 'Select one or more staff members from the list.')}
${step(A, 3, 'Enter the <strong>date</strong>, <strong>clock-in time</strong>, and <strong>clock-out time</strong>.')}
${step(A, 4, 'Add a <strong>note</strong> explaining why the manual entry is being added.')}
${step(A, 5, 'Click <strong>Submit</strong>. The entry is created with <strong>Approved</strong> status automatically.')}
${tip('Each staff member can only have one time entry per date. If an entry already exists, it will be skipped — check first before adding.')}

${subHead(A, '2.4 Exporting Attendance Data (CSV)')}
${step(A, 1, 'Go to <strong>Attendance</strong>.')}
${step(A, 2, 'Apply any filters — date range, staff member, or status.')}
${step(A, 3, 'Click <strong>"Export CSV"</strong>. The file downloads immediately.')}
<p style="font-size:9pt;color:${GREY};margin-top:6px;">The CSV includes: Employee ID, Name, Date, Clock In, Clock Out, Total Hours, Break Minutes, OT flag, Source, Status, Notes.</p>

${chapterHead(A, '3', 'Staff Management', 'Viewing and updating staff records')}

${subHead(A, '3.1 Viewing a Staff Profile')}
${step(A, 1, 'Go to <strong>Staff</strong> in the Human Resources section of the left navigation.')}
${step(A, 2, 'Click a staff member\'s name or row to open their profile.')}
${step(A, 3, 'The profile shows: personal details, role, employee ID, hourly rates, leave entitlement, contact info, and tabs for attendance, jobs, and leave history.')}

${subHead(A, '3.2 Updating Staff Information')}
${step(A, 1, 'From the staff profile, click <strong>"Edit Profile"</strong>.')}
${step(A, 2, 'Update any fields: name, contact details, hourly rates (regular and overtime), or annual leave entitlement.')}
${step(A, 3, 'Click <strong>Save</strong>. Changes take effect immediately for future payroll runs.')}
${warning('Changes to hourly rates affect future payroll calculations only. Previously approved payslips are not recalculated.')}

${subHead(A, '3.3 Resetting a Staff Password')}
${step(A, 1, 'Open the staff member\'s profile.')}
${step(A, 2, 'Click the <strong>"Reset Password"</strong> button.')}
${step(A, 3, 'A new temporary password is generated and displayed on your screen.')}
${step(A, 4, '<strong>Copy the temporary password immediately</strong> — it will not be shown again.')}
${step(A, 5, 'Share it with the staff member privately (in person or direct message). They must change it on first login.')}
${warning('Never share temporary passwords through public group chats or unsecured channels.')}
</div>
${pb()}

<div class="body-page">
${rh()}
${chapterHead(A, '4', 'Payroll Management', 'Generating, reviewing, and approving pay runs')}

${subHead(A, '4.1 Generating a Payroll Run')}
${step(A, 1, 'Go to <strong>Payroll</strong> in the Human Resources section of the navigation.')}
${step(A, 2, 'Click <strong>"Generate Run"</strong>.')}
${step(A, 3, 'Set the <strong>Period From</strong> and <strong>Period To</strong> dates.')}
${step(A, 4, 'Click <strong>Generate</strong>. A draft payslip is created for every active non-admin staff member with approved entries in that period.')}
${warning('Ensure all attendance entries for the period are APPROVED before generating. Pending entries are excluded from calculations.')}

${subHead(A, '4.2 Hour Calculation Rules')}
${statusTable({
    headers: ['Shift Type', 'Regular Hours', 'Overtime Hours'],
    body: [
        ['Standard shift (no OT)',       'Up to 8h per day',  'Hours beyond 8h'],
        ['OT-type shift (approved OT)',  '0h',                'All hours'],
        ['RDOT-type shift (rest day)',   '0h',                'All hours'],
    ]
})}

${subHead(A, '4.3 Reviewing a Payslip')}
${step(A, 1, 'Click on any payslip from the Payroll list to open it.')}
${step(A, 2, 'Review: period dates, regular hours, overtime hours, and gross pay.')}
${step(A, 3, 'Check that the hourly rates match the staff member\'s current rates.')}
${step(A, 4, 'Add deductions if required (see 4.4), then approve.')}

${subHead(A, '4.4 Adding Deductions')}
${step(A, 1, 'On a draft payslip, scroll to the <strong>Deductions</strong> section.')}
${step(A, 2, 'Click <strong>"+ Add Deduction"</strong>. Enter a description and amount.')}
${step(A, 3, 'Net Pay updates automatically. Add as many deductions as needed.')}
${step(A, 4, 'Click <strong>"Save Deductions"</strong>.')}

${subHead(A, '4.5 Approving a Payslip')}
${step(A, 1, 'On a reviewed draft payslip, click <strong>"Approve Payslip"</strong>.')}
${step(A, 2, 'The payslip is locked — status changes to <strong>Approved</strong>.')}
${step(A, 3, 'The staff member can now view their approved payslip under <strong>My Payslip</strong>.')}
${warning('Approved payslips are permanently locked and cannot be edited. Verify all figures before approving.')}

${chapterHead(A, '5', 'Frequently Asked Questions')}
${faqSection(A, [
    { q: 'A staff member\'s payslip shows wrong hours. What do I do?', a: 'If the payslip is still in Draft, you can delete it and regenerate after correcting the attendance entries. If already Approved, contact the System Administrator — locked payslips cannot be edited through the portal.' },
    { q: 'Can HR approve leave requests?', a: 'No — leave approval is restricted to Admin and Manager roles. HR can view all leave requests but cannot approve or reject them.' },
    { q: 'Why is a staff member missing from the payroll run?', a: 'They may have no approved attendance entries in that period, or their account may be marked inactive. Check their profile and attendance records.' },
    { q: 'How do I handle a duplicate time entry?', a: 'Only one entry per staff member per date is allowed. If a duplicate exists, reject the incorrect one and keep the accurate one.' },
    { q: 'Can I export payroll data?', a: 'Yes — from the Attendance page, use "Export CSV" for time-based data. For payroll-specific exports, use the Export CSV button on the Payroll page.' },
])}
</div>
`);
}

// ═══════════════════════════════════════════════════════════════════════════════
// GUIDE 4 — MANAGER / ADMIN
// ═══════════════════════════════════════════════════════════════════════════════
function managerGuide() {
    const A = RED;
    const rh = () => runHead('Manager / Admin', A);

    return wrap(A, `
${cover(A, PURPLE, 'Manager', '👔', 'Your complete guide to scheduling jobs, managing staff, approving attendance and leave, running payroll, and overseeing daily operations.', 'BCF-UG-MGR-001')}

${quickRef(A, 'Manager', [
    { icon: '📋', title: 'Create a Job',       steps: ['Go to Live Board', 'Navigate to the date', 'Click Add Job, fill details, save'] },
    { icon: '✅', title: 'Approve Attendance', steps: ['Go to Attendance → filter Pending', 'Review, then Approve or Bulk Approve'] },
    { icon: '🌴', title: 'Approve Leave',      steps: ['Go to Leave → filter Pending', 'Review request, click Approve or Reject'] },
    { icon: '💰', title: 'Generate Payroll',   steps: ['Go to Payroll', 'Click Generate Run', 'Enter period dates → Generate'] },
    { icon: '👤', title: 'Add Staff Member',   steps: ['Go to Staff', 'Click New Staff Member', 'Fill details → Save'] },
    { icon: '📊', title: 'View Reports',       steps: ['Go to Reports in Admin section', 'Select report type and filters'] },
])}

<div class="body-page">
${rh()}
${chapterHead(A, '1', 'Admin Dashboard', 'Your daily management overview')}

<p style="font-size:9.5pt;margin-bottom:12px;">The Admin Dashboard gives you a high-level view of all operations. Unlike staff dashboards, yours shows company-wide stats:</p>

${featureGrid([
    { icon: '📊', title: "Today's Jobs",      desc: 'Total number of jobs scheduled today across all sites and projects.' },
    { icon: '🟢', title: 'Clocked-In Staff',  desc: 'Live count and list of who is currently clocked in, with their status, duration, and role.' },
    { icon: '⏳', title: 'Pending Leave',      desc: 'Number of leave requests awaiting your decision.' },
    { icon: '💼', title: 'Pending OT',         desc: 'Number of overtime requests awaiting approval.' },
    { icon: '📋', title: "Today's Job List",  desc: 'Card view of every scheduled job today with status, project, van, and staff count.' },
    { icon: '📈', title: 'Weekly Chart',       desc: 'Jobs this week grouped by status (scheduled/in progress/completed) across Mon–Sun.' },
])}
${info('The clocked-in staff list updates in real time via WebSocket — you don\'t need to refresh the page to see new clock-ins.')}

${chapterHead(A, '2', 'Live Board', 'Day-by-day job scheduling and monitoring')}

${subHead(A, '2.1 Navigating the Live Board')}
<p style="font-size:9.5pt;margin-bottom:8px;">The Live Board is your command centre for daily scheduling. Each date shows a grid of all jobs — from a single focused view.</p>
${step(A, 1, 'Go to <strong>Live Board</strong> in the Operations section of the left navigation.')}
${step(A, 2, 'Use the <strong>← → arrows</strong> to move between dates, or use the <strong>date picker</strong> for any specific day.')}
${step(A, 3, 'Click <strong>"Today"</strong> to jump back to the current date.')}
${step(A, 4, 'Summary stats at the top show: total jobs, in-progress count, staff deployed, and staff clocked in for that day.')}
</div>
${pb()}

<div class="body-page">
${rh()}
${subHead(A, '2.2 Creating a Job')}
${step(A, 1, 'On the Live Board, navigate to the target date and click <strong>"Add Job"</strong>.')}
${step(A, 2, 'Enter the <strong>Job Title</strong> (required).')}
${step(A, 3, 'Select a <strong>Project/Site</strong> to link this job to a client project (optional but recommended).')}
${step(A, 4, 'Set the <strong>Date</strong>, <strong>Start Time</strong>, and <strong>End Time</strong>.')}
${step(A, 5, 'Assign a <strong>Van</strong> if transport is required.')}
${step(A, 6, 'Select <strong>Crew Members</strong> from the staff list. Assigned staff receive an automatic notification.')}
${step(A, 7, 'Optionally link to a <strong>BCF Order</strong> or <strong>BGR Project stage</strong>.')}
${step(A, 8, 'Click <strong>Create Job</strong>. The job appears on the board with status <strong>Scheduled</strong>.')}
${warning('If any assigned staff has approved leave on that date, a warning banner will appear. Review the leave calendar before confirming the assignment.')}

${subHead(A, '2.3 Editing a Job')}
${step(A, 1, 'Click the <strong>pencil icon ✏️</strong> on any job card.')}
${step(A, 2, 'Update any field — title, date, times, van, crew, or client links.')}
${step(A, 3, 'Click <strong>Save Changes</strong>. Newly assigned staff are notified automatically.')}

${subHead(A, '2.4 Job Status Management')}
${statusTable({
    headers: ['Action', 'From', 'To', 'Who'],
    body: [
        ['▶ Start Job',    'Scheduled',   'In Progress', 'Assigned staff, Site Head, Manager, Admin'],
        ['✓ Complete',     'In Progress', 'Completed',   'Assigned staff, Site Head, Manager, Admin'],
        ['✕ Cancel',       'Scheduled',   'Cancelled',   'Manager, Admin'],
        ['↩ Re-open',      'Completed',   'In Progress', 'Manager, Admin'],
        ['↩ Restore',      'Cancelled',   'Scheduled',   'Manager, Admin'],
    ]
})}

${chapterHead(A, '3', 'All Jobs List', 'Viewing jobs across all dates')}

<p style="font-size:9.5pt;margin-bottom:10px;">Go to <strong>All Jobs</strong> in the navigation for a paginated list spanning multiple dates — ideal for planning ahead or reviewing past work.</p>
${featureGrid([
    { icon: '📅', title: 'Period Tabs',   desc: 'Toggle between Upcoming, Past, and All jobs at the top of the page.' },
    { icon: '🔍', title: 'Filters',       desc: 'Filter by status or date range for precise results.' },
    { icon: '📆', title: 'Date Groups',   desc: 'Jobs are grouped under date headings. Today and Tomorrow are highlighted.' },
    { icon: '🔗', title: 'Board Link',    desc: 'Each job row has a calendar icon — click it to jump directly to that day on the Live Board.' },
])}
</div>
${pb()}

<div class="body-page">
${rh()}
${chapterHead(A, '4', 'Staff Management', 'Creating and managing staff accounts')}

${subHead(A, '4.1 Creating a New Staff Account')}
${step(A, 1, 'Go to <strong>Staff</strong> in the Management section.')}
${step(A, 2, 'Click <strong>"New Staff Member"</strong>.')}
${step(A, 3, 'Fill in: <strong>Full Name</strong>, <strong>Email</strong>, <strong>Role</strong>, <strong>Employee ID</strong>, <strong>Hourly Rate</strong> (regular and overtime), and <strong>Annual Leave Entitlement</strong> (default: 28 days).')}
${step(A, 4, 'Click <strong>Create</strong>. A temporary password is generated and displayed — <strong>copy and share it securely</strong> with the staff member.')}
${step(A, 5, 'The staff member logs in with the temporary password and is prompted to create a new one.')}

${subHead(A, '4.2 Updating Staff Details')}
<p style="font-size:9.5pt;">Open a staff profile → click <strong>Edit Profile</strong>. You can update name, email, role, rates, entitlement, and contact details. Click <strong>Save</strong>.</p>

${subHead(A, '4.3 Resetting a Password')}
${step(A, 1, 'Open the staff profile → click <strong>"Reset Password"</strong>.')}
${step(A, 2, 'Copy the temporary password displayed. <strong>It will not be shown again.</strong>')}
${step(A, 3, 'Share it privately. The staff member must change it on next login.')}

${subHead(A, '4.4 Assigning Roles')}
${statusTable({
    headers: ['Role', 'Access Level', 'When to assign'],
    body: [
        ['Admin',    'Full system access including Audit Log and Businesses',  'System owner / lead administrator'],
        ['Manager',  'All ops: jobs, staff, payroll, attendance, leave, OT',  'Operations managers and team leads'],
        ['HR',       'Attendance, payroll, staff records',                     'Human resources / payroll staff'],
        ['Site Head','Manage site jobs, scan QR codes',                        'Senior on-site workers'],
        ['Staff',    'Own attendance, jobs, leave, payslip',                   'All other employees'],
    ]
})}

${chapterHead(A, '5', 'Attendance Management')}

${subHead(A, 'Approving Entries')}
${step(A, 1, 'Go to <strong>Attendance</strong>. Filter by <strong>Status: Pending</strong>.')}
${step(A, 2, 'Review the clock-in time, clock-out time, and total hours for each entry.')}
${step(A, 3, 'Click <strong>Approve</strong> individually, or use <strong>Bulk Approve</strong> to select and approve many at once.')}

${subHead(A, 'Manual Entry')}
${step(A, 1, 'Click <strong>"Manual Entry"</strong>. Select staff, enter date and times, add a note, and submit.')}
${step(A, 2, 'Manual entries by managers are auto-approved and count immediately towards payroll.')}
${warning('Only one entry per staff member per date is allowed. If an entry already exists for that date, it will be skipped.')}
</div>
${pb()}

<div class="body-page">
${rh()}
${chapterHead(A, '6', 'Leave & Overtime Management')}

${subHead(A, 'Approving Leave Requests')}
${step(A, 1, 'Go to <strong>Leave</strong>. Filter by <strong>Pending</strong> to see requests awaiting decision.')}
${step(A, 2, 'Review dates, leave type, duration, and any note from the staff member.')}
${step(A, 3, 'Check for scheduling conflicts — if the staff member is on a job, a warning is shown.')}
${step(A, 4, 'Click <strong>Approve</strong> or <strong>Reject</strong> (rejection requires a reason). The staff member is notified.')}

${subHead(A, 'Approving Overtime Requests')}
${step(A, 1, 'Go to <strong>Overtime</strong>. Review pending requests.')}
${step(A, 2, 'Approve or decline. On approval, the staff member\'s Dashboard will show the OT clock-in toggle for that date.')}
${featureGrid([
    { icon: '⏰', title: 'OT',   desc: 'Overtime on a normal working day — approved hours are paid at the overtime rate.' },
    { icon: '📆', title: 'RDOT', desc: 'All hours on a rest day are overtime, regardless of duration.' },
])}

${chapterHead(A, '7', 'Payroll')}

${subHead(A, 'Generating a Run')}
${step(A, 1, 'Go to <strong>Payroll</strong> → click <strong>"Generate Run"</strong>.')}
${step(A, 2, 'Set Period From and To dates. Click <strong>Generate</strong>.')}
${step(A, 3, 'Draft payslips are created for all active non-admin staff with approved entries in the period.')}
${warning('All attendance entries for the period must be APPROVED before generating payroll. Pending entries are excluded.')}

${subHead(A, 'Reviewing & Approving')}
${step(A, 1, 'Open a draft payslip. Review regular hours, overtime hours, and gross pay.')}
${step(A, 2, 'Add deductions if required (click + Add Deduction, enter description and amount).')}
${step(A, 3, 'Click <strong>Approve Payslip</strong> when correct. Status locks to Approved — the staff member can now view it.')}
${warning('Approved payslips cannot be edited. Double-check all figures before approving.')}

${subHead(A, 'Payroll Export')}
<p style="font-size:9.5pt;">Click <strong>"Export CSV"</strong> on the Payroll page to download payroll data. Apply period/status filters first. The export includes: Employee ID, Name, Regular Hours, OT Hours, Gross Pay, Deductions, Net Pay, Approved By.</p>

${chapterHead(A, '8', 'Projects, Vans & Reports')}

${subHead(A, 'Projects')}
${featureGrid([
    { icon: '📁', title: 'Create Project',    desc: 'Go to Projects → New Project. Enter name, client, business (BCF/BGR), status, and assign site heads.' },
    { icon: '☑️', title: 'Checklist',         desc: 'Jobs linked to a project auto-create checklist items. Completing a job auto-ticks the item.' },
    { icon: '🔄', title: 'Status Lifecycle',  desc: 'Planning → Active → On Hold → Completed. Update from the project detail page.' },
    { icon: '🌐', title: 'Client Links',       desc: 'BCF orders: completing a linked job auto-marks the BCF stage as Done. BGR stages are tracked for reporting.' },
])}

${subHead(A, 'Vans')}
<p style="font-size:9.5pt;">Go to <strong>Vans</strong> in Management to add vehicles, track MOT/service dates, and manage allocations to staff. Deactivate a van to remove it from job assignment dropdowns.</p>

${subHead(A, 'Reports & Audit')}
<p style="font-size:9.5pt;">Go to <strong>Reports</strong> in Admin to view hours summaries, attendance patterns, and payroll totals. <strong>Audit Log</strong> (Admin only) shows a full timestamped trail of all significant actions — payslip approvals, password resets, rejections, etc.</p>

${chapterHead(A, '9', 'Frequently Asked Questions')}
${faqSection(A, [
    { q: 'A staff member\'s approved payslip has an error. What do I do?', a: 'Approved payslips are locked through the portal. Contact the System Administrator — only they can make corrections at the database level. Document the issue and the correct figures.' },
    { q: 'I need to add attendance for someone who was on site without a device.', a: 'Go to Attendance → Manual Entry. Select the staff member, enter the correct date and times, and add a note. Manual entries by managers are auto-approved.' },
    { q: 'A staff member is assigned to a job but they\'re on approved leave that day.', a: 'The portal will show a warning when you save the job. You should either reassign the job to another staff member, or decline their leave request if the work is critical.' },
    { q: 'How do I move a job to a different date?', a: 'Open the job on the Live Board and click Edit. Change the date field and save. The job will appear on the new date instead.' },
    { q: 'Can I see who is on-site right now?', a: 'Yes — your Admin Dashboard shows a real-time list of all clocked-in staff with their status, duration, and role. It updates live without needing a page refresh.' },
    { q: 'A staff member completed a job by mistake. Can I undo it?', a: 'Yes — open the job on the Live Board or in All Jobs, and click "↩ Re-open" to set it back to In Progress. Only Manager and Admin can re-open completed jobs.' },
])}
</div>
`);
}

// ─── RUN ALL ──────────────────────────────────────────────────────────────────
const browser = await puppeteer.launch({
    executablePath,
    headless: 'new',
    args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-dev-shm-usage'],
});

console.log('\nGenerating role guides...\n');
await buildPDF(browser, 'guide-staff.pdf',    staffGuide());
await buildPDF(browser, 'guide-sitehead.pdf', siteHeadGuide());
await buildPDF(browser, 'guide-hr.pdf',       hrGuide());
await buildPDF(browser, 'guide-manager.pdf',  managerGuide());

await browser.close();
console.log('\n✅  All 4 role guides generated in docs/');
