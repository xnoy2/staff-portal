# Staff Portal — User Manual

> **Version:** May 2026  
> **Applies to:** All portal users

---

## Table of Contents

1. [Overview](#overview)
2. [Logging In](#logging-in)
3. [Role Summary](#role-summary)
4. [Admin](#admin)
5. [Manager](#manager)
6. [HR](#hr)
7. [Site Head](#site-head)
8. [Staff](#staff)

---

## Overview

The BCF Staff Portal is the central hub for managing staff, projects, jobs, attendance, leave, vehicles, payroll, training, and client integrations. Access to features depends on your assigned role.

---

## Logging In

1. Open the portal in your browser.
2. Enter your **email** and **password**.
3. If it is your first login, you will be asked to change your password.
4. After login you land on your **Dashboard**.

> If you forget your password, ask an Admin or Manager to send you a password reset.

---

## Role Summary

| Feature | Admin | Manager | HR | Site Head | Staff |
|---|:---:|:---:|:---:|:---:|:---:|
| Dashboard | ✓ | ✓ | ✓ | ✓ | ✓ |
| Attendance — clock in/out | ✓ | ✓ | ✓ | ✓ | ✓ |
| Attendance — view & approve all | ✓ | ✓ | ✓ | ✓ | — |
| Leave — request own | ✓ | ✓ | ✓ | ✓ | ✓ |
| Leave — approve / reject | ✓ | ✓ | — | — | — |
| Live Board — view | ✓ | ✓ | — | Own projects | Own jobs |
| Live Board — create / edit jobs | ✓ | ✓ | — | Own projects | — |
| Live Board — delete jobs | ✓ | ✓ | — | — | — |
| Projects — view | ✓ | ✓ | — | Own projects | — |
| Projects — create / edit / delete | ✓ | ✓ | — | — | — |
| Vans — view | ✓ | ✓ | — | ✓ | — |
| Vans — create / edit / delete | ✓ | ✓ | — | — | — |
| Staff — view list | ✓ | ✓ | ✓ | ✓ | — |
| Staff — create / edit / deactivate | ✓ | ✓ | ✓ | — | — |
| Overtime — submit own | ✓ | ✓ | ✓ | ✓ | ✓ |
| Overtime — approve / reject | ✓ | ✓ | ✓ | — | — |
| Subcontractors | ✓ | ✓ | View only | View only | — |
| Payroll | ✓ | ✓ | ✓ | — | My payslip |
| Training — view enrolled | ✓ | ✓ | ✓ | ✓ | ✓ |
| Training — manage modules | ✓ | ✓ | — | — | — |
| BCF Orders | ✓ all | ✓ all | ✓ all | — | — |
| BGR Projects | Own (if connected) | Own (if connected) | — | Own (if connected) | — |
| Audit Log | ✓ | ✓ | — | — | — |
| Reports | ✓ | ✓ | — | — | — |
| Settings | ✓ | ✓ | — | — | — |
| Businesses | ✓ | ✓ | — | — | — |

---

## Admin

Admins have full access to every feature in the portal. Admins are typically company directors or senior managers.

### Dashboard
- Overview of today's jobs, staff clocked in, pending leave requests, and recent activity.

### Live Board (`/jobs`)
- View all jobs across all dates.
- **Create jobs** — set title, date, time, van, crew, link to a project, BCF order, or BGR stage.
- **Edit jobs** — modify any field at any time.
- **Delete jobs** — permanently remove a job from the board.
- **Change job status** — Scheduled → In Progress → Completed / Cancelled.
- Completing a job linked to a BCF stage automatically marks that stage as **Done** in BCF.

### Projects (`/projects`)
- View all internal projects (Planning, Active, On Hold).
- **Create projects** — set name, customer, business (BCF/BGR), status, date range, assigned site head and staff.
- **Edit / Delete projects**.
- **Manage project checklist** — add/toggle/delete checklist items per project. Items created from jobs are synced automatically.

### Staff Management (`/staff`)
- View all staff profiles, roles, and status.
- **Add new staff** — name, email, role, employment details, avatar.
- **Edit staff** — update any detail, link to a BCF worker account, or connect a BGR account.
- **Deactivate / Reactivate** staff accounts.
- **Force password reset** — sends the staff member a reset link.
- **Onboarding** — upload and manage onboarding documents per staff member.
- **View payslip** for any staff member.

### Attendance (`/attendance`)
- Clock in and out for yourself using the Attendance page or QR scanner.
- View **all staff attendance records**.
- **Approve / Reject** time entries.
- **Bulk approve** multiple pending entries at once.
- Add **manual time entries** for staff (e.g. forgotten clock-in).
- **Export** attendance data to Excel.

### Leave Management (`/leave`)
- View all leave requests from all staff.
- **Approve or Reject** any leave request.
- Filter by staff member, status, type, or year.
- View leave balance summary per staff member.
- The system warns when a job is scheduled on a date a staff member has approved leave.

### Vans (`/vans`)
- View all fleet vehicles with current driver, registration, make, and model.
- **Add / Edit / Delete** vans.
- **Activate / Deactivate** a van from active fleet rotation.
- **Assign a driver** to a van and record notes.
- **Return a driver** and end the assignment.
- View full **assignment history** per van.
- Manage **period allocations** (allocate a van to a staff member for a date range).

### BCF Orders (`/bcf`)
- View all BCF client orders.
- Open any order to see full stage breakdown and task list.
- **Mark stages as Done / Undo** directly from the order page.
- **Mark tasks as complete** with optional note.
- Linked jobs from the Live Board appear as coloured pills under each stage.
- Linked BCF worker — set in Staff → Edit to control which staff member's orders appear by default.

### BGR Client Projects (`/client-projects`)
- Connect your BGR account (email + password).
- View all BGR projects assigned to your account.
- Open a project to see stages, substages (tasks), and progress updates.
- **Complete tasks** — add a note and upload photos.
- **Post progress updates** linked to a specific stage.
- Linked jobs from the Live Board appear under each stage.

### Overtime (`/overtime`)
- View all overtime requests.
- **Approve or Reject** overtime for any staff member.
- Submit your own overtime request.

### Subcontractors (`/subcontractors`)
- View all subcontractor profiles.
- **Add / Edit / Delete** subcontractors.
- Upload and delete subcontractor photos.

### Payroll (`/payroll`)
- View payroll runs.
- **Create a payroll run** for a date range.
- **Approve individual or all** payroll entries.
- **Export** payroll data.
- **Set payroll cutoff date**.
- View your own payslip at `/my-payslip`.

### Training (`/training`)
- View all training modules.
- **Create / Edit / Delete** training modules and lessons.
- **Toggle visibility** of modules and lessons.
- **Manage enrollments** — assign staff to specific modules.
- View staff progress per module.
- Watch video lessons (available to enrolled staff).

### Businesses (`/businesses`)
- Create and manage business entities (e.g. BCF, BGR).
- Activate / Deactivate businesses.

### Reports (`/reports`)
- View aggregated reports on attendance and payroll.
- Export payroll data to Excel.

### Audit Log (`/audit-log`)
- View a full trail of changes made across the portal (who changed what and when).

### Settings (`/settings`)
- Update company-wide settings (e.g. payroll periods, working hours).
- Update your own personal preferences.

---

## Manager

Managers have **the same access as Admins** for all day-to-day operations. The only functional difference is that Managers cannot perform permanent force-deletions of records.

Refer to the [Admin](#admin) section for full feature descriptions.

---

## HR

HR users focus on people management, attendance, overtime, and payroll. They do **not** manage projects, jobs, vans, or approve leave.

### Dashboard
- Overview card with today's pending attendance and staff counts.

### Attendance (`/attendance`)
- View **all staff attendance records**.
- **Approve / Reject** individual time entries.
- **Bulk approve** pending entries.
- Add **manual time entries**.
- **Export** attendance data.
- Clock in/out for yourself.

### Staff Management (`/staff`)
- View all staff profiles.
- **Add new staff** accounts.
- **Edit staff** details (name, email, role, pay, contact info).
- **Deactivate / Reactivate** staff.
- **Force password reset**.
- Manage **onboarding documents** per staff member.

### Leave (`/leave`)
- View your own leave requests and submit new ones.
- **View all staff leave** (read-only; approval is done by Admin or Manager).

### Overtime (`/overtime`)
- View **all overtime requests**.
- **Approve or Reject** overtime submissions.
- Submit your own overtime request.

### Subcontractors (`/subcontractors`)
- View all subcontractor profiles and photos (read-only).

### Payroll (`/payroll`)
- View payroll runs.
- **Create and approve payroll runs**.
- View payslips for all staff.
- View your own payslip.

### BCF Orders (`/bcf`)
- View all BCF orders (full list, not filtered by worker).

### Training (`/training`)
- View enrolled training modules and watch lessons.
- Track your own lesson progress.

### QR Code & Attendance
- Access your personal QR code at `/my-qr` for QR-based clock-in/out.

---

## Site Head

Site Heads oversee specific projects they are assigned to. They can manage jobs and see staff on their projects, but cannot manage other projects, vans, payroll, or HR data.

### Dashboard
- Shows jobs and staff relevant to your assigned projects.

### Live Board (`/jobs`)
- View jobs **belonging to your assigned projects** only.
- **Create new jobs** for your projects (set title, date, time, van, crew, link to a BCF or BGR stage).
- **Edit jobs** for your projects (change any field).
- **Cannot delete** jobs — contact a Manager or Admin.
- Change job **status** (Scheduled / In Progress / Completed / Cancelled).

### Projects (`/projects`)
- View your **assigned projects** only (Planning, Active, On Hold).
- View the project checklist (cannot add/delete checklist items directly — items are synced from jobs).
- Cannot create, edit, or delete projects — contact a Manager.

### Staff (`/staff`)
- View the staff list and individual profiles (read-only).

### Vans (`/vans`)
- View all active vans and their current driver (read-only).
- Cannot create, edit, or assign vans.

### Attendance (`/attendance`)
- View all staff attendance records.
- **Cannot approve or add manual entries** — contact HR or a Manager.
- Clock in/out for yourself.

### Leave (`/leave`)
- Submit your own leave requests.
- View your own leave history and balance.

### Overtime (`/overtime`)
- Submit your own overtime request.
- View your own overtime history.

### Subcontractors (`/subcontractors`)
- View subcontractor profiles and photos (read-only).

### BGR Client Projects (`/client-projects`)
- Connect your BGR account to view your assigned BGR projects.
- View stages, tasks, and progress updates.
- Complete tasks and post progress updates for your projects.

### Training (`/training`)
- View your enrolled training modules.
- Watch lessons and mark progress.

### QR Code
- Access your personal QR code at `/my-qr`.

---

## Staff

Regular staff members have self-service access only. They see their own jobs, attendance, leave, payslip, and training.

### Dashboard
- Overview of your jobs today, attendance status, and any notifications.

### Live Board (`/jobs`)
- View jobs **you are assigned to** for any selected date.
- Cannot create, edit, or delete jobs.
- Cannot change job status — this is done by your manager or site head.

### Attendance (`/attendance`)
- **Clock In** — tap Clock In to start your shift.
- **Start / End Breaks** — record break time during your shift.
- **Clock Out** — end your shift. Your time entry will be reviewed and approved by management.
- View your own attendance history and approved hours.

### QR Clock-In
- Go to `/my-qr` to display your personal QR code.
- A manager or supervisor can scan it at the job site to clock you in or out.

### Leave (`/leave`)
- **Submit a leave request** — choose type (Annual, Sick, Unpaid, etc.), dates, and a reason.
- View the status of your requests (Pending / Approved / Rejected).
- View your leave balance for the current year.

### Overtime (`/overtime`)
- Submit an overtime request for additional hours worked.
- View your own overtime history and approval status.

### Training (`/training`)
- View training modules you are enrolled in.
- Watch video lessons.
- Your progress is saved automatically.

### My Payslip (`/my-payslip`)
- View your most recent payslip.
- Download or print if needed.

### Profile (`/profile`)
- Update your **display name**.
- Change your **password**.
- Upload a **profile photo** (avatar).

---

## Common Actions for All Users

### Notifications (Bell icon — top right)
- The bell shows unread notifications (job assignments, leave status changes, etc.).
- Click a notification to go to the relevant page.
- Mark individual notifications as read, or use **Mark All Read**.

### Changing Your Password
- Go to **Profile → Change Password** to update your password at any time.
- First-time login will prompt you to set a new password.

### Calendar (`/calendar`)
- View a monthly calendar of your assigned shifts and job schedules.

### Schedule (`/schedule`)
- View weekly staff schedules (visibility depends on your role).

---

*For technical issues or access problems, contact your Admin.*
