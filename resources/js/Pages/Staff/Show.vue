<template>
    <AppLayout :title="staffMember.name">
        <div class="max-w-4xl mx-auto space-y-4">

            <!-- ── Header card (always visible) ────────────────────────────── -->
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <div class="flex flex-col sm:flex-row items-center sm:items-start gap-4">
                    <img :src="staffMember.avatar_url" :alt="staffMember.name"
                        class="w-16 h-16 rounded-full object-cover border-2 border-white shadow-md flex-shrink-0" />

                    <div class="flex-1 text-center sm:text-left min-w-0">
                        <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2 mb-1">
                            <h1 class="text-lg font-bold text-gray-800">{{ staffMember.name }}</h1>
                            <span :class="staffMember.is_active ? 'badge-green' : 'badge-red'">
                                {{ staffMember.is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                        <p class="text-sm text-gray-500">{{ staffMember.email }}</p>
                        <p v-if="staffMember.employee_id" class="mt-0.5">
                            <span class="text-xs font-mono font-semibold bg-gray-100 text-gray-600 px-2 py-0.5 rounded">{{ staffMember.employee_id }}</span>
                        </p>
                        <div class="flex flex-wrap justify-center sm:justify-start gap-1.5 mt-1.5">
                            <span v-for="role in staffMember.roles" :key="role" :class="roleClass(role)">
                                {{ role.replace('_', ' ') }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-400 mt-1">
                            Member since {{ formatDate(staffMember.created_at) }}
                            <span v-if="staffMember.hire_date"> · Hired {{ formatDate(staffMember.hire_date) }}</span>
                        </p>
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-2 flex-shrink-0 flex-wrap justify-center sm:justify-end">
                        <Link :href="route('staff.payslip', staffMember.id)"
                            class="inline-flex items-center gap-1.5 bg-white border border-gray-200 text-gray-700 text-xs px-3 py-1.5 rounded-lg hover:bg-gray-50 transition-colors">
                            <DocumentTextIcon class="w-3.5 h-3.5" /> Payslip
                        </Link>
                        <Link v-if="canEdit" :href="route('staff.onboarding', staffMember.id)"
                            :class="[
                                'inline-flex items-center gap-1.5 text-xs px-3 py-1.5 rounded-lg transition-colors',
                                hasOnboarding
                                    ? 'bg-emerald-50 border border-emerald-200 text-emerald-700 hover:bg-emerald-100'
                                    : 'bg-amber-50 border border-amber-200 text-amber-700 hover:bg-amber-100',
                            ]">
                            <ClipboardDocumentCheckIcon class="w-3.5 h-3.5" />
                            {{ hasOnboarding ? 'Onboarding' : 'Onboarding ⚠' }}
                        </Link>
                        <Link :href="route('staff.edit', staffMember.id)"
                            class="inline-flex items-center gap-1.5 bg-[#EF233C] text-white text-xs px-3 py-1.5 rounded-lg hover:bg-[#D90429] transition-colors">
                            <PencilIcon class="w-3.5 h-3.5" /> Edit
                        </Link>
                        <button @click="toggleActive"
                            :class="staffMember.is_active
                                ? 'inline-flex items-center gap-1.5 bg-amber-50 text-amber-700 border border-amber-200 text-xs px-3 py-1.5 rounded-lg hover:bg-amber-100 transition-colors'
                                : 'inline-flex items-center gap-1.5 bg-green-50 text-green-700 border border-green-200 text-xs px-3 py-1.5 rounded-lg hover:bg-green-100 transition-colors'">
                            <component :is="staffMember.is_active ? NoSymbolIcon : CheckCircleIcon" class="w-3.5 h-3.5" />
                            {{ staffMember.is_active ? 'Deactivate' : 'Activate' }}
                        </button>
                    </div>
                </div>

                <!-- Quick stats bar -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-4 pt-4 border-t border-gray-100">
                    <div class="text-center">
                        <p class="text-xl font-bold text-[#EF233C]">{{ totalHours }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">Approved Hours</p>
                    </div>
                    <div class="text-center">
                        <p class="text-xl font-bold text-gray-800">{{ jobStats.total }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">Total Jobs</p>
                    </div>
                    <div class="text-center">
                        <p class="text-xl font-bold text-green-600">{{ jobStats.completed }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">Jobs Completed</p>
                    </div>
                    <div class="text-center">
                        <p class="text-xl font-bold"
                            :class="jobStats.completionRate >= 80 ? 'text-green-600' : jobStats.completionRate >= 50 ? 'text-amber-600' : 'text-gray-800'">
                            {{ jobStats.completionRate }}%
                        </p>
                        <p class="text-xs text-gray-400 mt-0.5">Completion Rate</p>
                    </div>
                </div>
            </div>

            <!-- ── Tab nav ───────────────────────────────────────────────────── -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="flex border-b border-gray-100 overflow-x-auto scrollbar-none">
                    <button
                        v-for="tab in tabs" :key="tab.id"
                        @click="activeTab = tab.id"
                        :class="[
                            'flex items-center gap-1.5 px-4 py-3 text-sm font-medium whitespace-nowrap border-b-2 transition-colors flex-shrink-0',
                            activeTab === tab.id
                                ? 'border-[#EF233C] text-[#EF233C]'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50',
                        ]"
                    >
                        <component :is="tab.icon" class="w-4 h-4" />
                        {{ tab.label }}
                        <span v-if="tab.count != null"
                            :class="['text-xs px-1.5 py-0.5 rounded-full', activeTab === tab.id ? 'bg-[#EF233C]/10 text-[#EF233C]' : 'bg-gray-100 text-gray-500']">
                            {{ tab.count }}
                        </span>
                    </button>
                </div>

                <!-- ── Overview tab ──────────────────────────────────────── -->
                <div v-show="activeTab === 'overview'" class="p-5 space-y-5">
                    <!-- Emergency contact + Certifications -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Emergency Contact</p>
                            <p class="text-sm font-medium text-gray-800">{{ staffMember.emergency_contact_name || '—' }}</p>
                            <p class="text-sm text-gray-500 mt-0.5">{{ staffMember.emergency_contact_phone || '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Certifications</p>
                            <div v-if="staffMember.certifications.length" class="flex flex-wrap gap-1.5">
                                <span v-for="cert in staffMember.certifications" :key="cert"
                                    class="text-xs bg-green-50 border border-green-200 text-green-800 px-2 py-0.5 rounded-full">
                                    {{ cert }}
                                </span>
                            </div>
                            <p v-else class="text-sm text-gray-400">None recorded.</p>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div v-if="staffMember.notes">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Notes</p>
                        <p class="text-sm text-gray-700 whitespace-pre-wrap bg-gray-50 rounded-lg px-4 py-3">{{ staffMember.notes }}</p>
                    </div>
                </div>

                <!-- ── Jobs tab ──────────────────────────────────────────── -->
                <div v-show="activeTab === 'jobs'" class="p-5 space-y-4">
                    <!-- Month breakdown -->
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-500">This month: <span class="font-semibold text-gray-800">{{ jobStats.thisMonth }} jobs</span></span>
                        <Link :href="route('jobs.index')" class="text-xs text-[#EF233C] hover:underline">Daily Job Board</Link>
                    </div>

                    <div v-if="recentJobs.length === 0" class="text-center py-10 text-sm text-gray-400">
                        No jobs assigned yet.
                    </div>
                    <div v-else class="space-y-1.5">
                        <div v-for="job in recentJobs" :key="job.id"
                            class="flex items-center gap-3 p-3 rounded-lg border border-gray-100 hover:bg-gray-50 transition-colors">
                            <div :class="['w-1 h-8 rounded-full flex-shrink-0', jobStatusStripe(job.status)]" />
                            <div class="flex-shrink-0 text-center w-10">
                                <p class="text-[10px] font-bold text-gray-400 uppercase leading-none">
                                    {{ new Date(job.date + 'T00:00:00').toLocaleDateString('en-GB', { month: 'short' }) }}
                                </p>
                                <p class="text-base font-black text-gray-800 leading-tight">
                                    {{ new Date(job.date + 'T00:00:00').getDate() }}
                                </p>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-800 truncate">{{ job.title }}</p>
                                <div class="flex items-center gap-1.5 mt-0.5">
                                    <span v-if="job.project?.business" :class="businessClass(job.project.business)">
                                        {{ job.project.business.toUpperCase() }}
                                    </span>
                                    <span v-if="job.project" class="text-xs text-gray-400 truncate">{{ job.project.name }}</span>
                                    <span v-if="job.start_time" class="text-xs text-gray-300">· {{ job.start_time }}</span>
                                </div>
                            </div>
                            <span :class="['text-xs px-2 py-0.5 rounded-full font-medium flex-shrink-0', jobStatusBadge(job.status)]">
                                {{ jobStatusLabel(job.status) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- ── Projects tab ──────────────────────────────────────── -->
                <div v-show="activeTab === 'projects'" class="p-5">
                    <div v-if="!projects.length" class="text-center py-10">
                        <p class="text-sm text-gray-400">Not assigned to any projects.</p>
                    </div>
                    <div v-else class="space-y-2">
                        <Link v-for="project in projects" :key="project.id"
                            :href="route('projects.show', project.id)"
                            class="flex items-center gap-3 p-3 rounded-lg border border-gray-100 hover:border-gray-200 hover:bg-gray-50 transition-all group">
                            <div :class="['w-1 h-8 rounded-full shrink-0', statusBarClass(project.status)]" />
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-1.5 mb-0.5">
                                    <span :class="businessClass(project.business)">{{ project.business?.toUpperCase() }}</span>
                                    <span class="text-sm font-medium text-gray-800 truncate group-hover:text-[#EF233C] transition-colors">{{ project.name }}</span>
                                </div>
                                <p class="text-xs text-gray-400 truncate">{{ project.customer }}</p>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <span :class="project.role === 'lead'
                                    ? 'text-xs font-medium px-2 py-0.5 rounded-full bg-amber-100 text-amber-700'
                                    : 'text-xs font-medium px-2 py-0.5 rounded-full bg-blue-50 text-blue-600'">
                                    {{ project.role === 'lead' ? 'Lead' : 'Support' }}
                                </span>
                                <span :class="statusClass(project.status)">{{ statusLabel(project.status) }}</span>
                            </div>
                        </Link>
                    </div>
                </div>

                <!-- ── Attendance tab ────────────────────────────────────── -->
                <div v-show="activeTab === 'attendance'" class="p-5">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-xs text-gray-500">Last {{ recentEntries.length }} entries</span>
                        <Link :href="`/attendance?user_id=${staffMember.id}`" class="text-xs text-[#EF233C] hover:underline">View all</Link>
                    </div>
                    <div v-if="!recentEntries.length" class="text-center py-10 text-gray-400 text-sm">No entries yet.</div>
                    <div v-else class="overflow-x-auto -mx-5 px-5">
                        <table class="w-full text-xs min-w-[300px]">
                            <thead>
                                <tr class="text-gray-400 border-b border-gray-100">
                                    <th class="text-left pb-2 font-medium">Date</th>
                                    <th class="text-left pb-2 font-medium">In</th>
                                    <th class="text-left pb-2 font-medium">Out</th>
                                    <th class="text-right pb-2 font-medium">Hours</th>
                                    <th class="text-right pb-2 font-medium">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="e in recentEntries" :key="e.id" class="border-b border-gray-50 last:border-0">
                                    <td class="py-2 text-gray-700 whitespace-nowrap">{{ e.date }}</td>
                                    <td class="py-2 font-mono text-gray-600 whitespace-nowrap">{{ e.clock_in }}</td>
                                    <td class="py-2 font-mono text-gray-600 whitespace-nowrap">{{ e.clock_out ?? '—' }}</td>
                                    <td class="py-2 text-right text-gray-700">{{ e.hours ?? '—' }}</td>
                                    <td class="py-2 text-right">
                                        <span :class="attendanceStatusClass(e.status)">{{ e.status }}</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ── Payroll tab ───────────────────────────────────────── -->
                <div v-show="activeTab === 'payroll'" class="p-5 space-y-5">
                    <!-- Compensation -->
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-4">Compensation</p>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                            <div>
                                <p class="text-xs text-gray-400 mb-1">Hourly Rate</p>
                                <p class="text-lg font-bold text-gray-800">{{ staffMember.hourly_rate ? '£' + Number(staffMember.hourly_rate).toFixed(2) : '—' }}</p>
                                <p class="text-xs text-gray-400">per hour</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 mb-1">Est. Weekly</p>
                                <p class="text-lg font-bold text-gray-800">
                                    {{ staffMember.hourly_rate ? '£' + (Number(staffMember.hourly_rate) * (staffMember.contracted_hours ?? 40)).toFixed(2) : '—' }}
                                </p>
                                <p class="text-xs text-gray-400">at {{ staffMember.contracted_hours ?? 40 }} hrs/wk</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 mb-1">Est. Annual</p>
                                <p class="text-lg font-bold text-gray-800">
                                    {{ staffMember.hourly_rate ? '£' + (Number(staffMember.hourly_rate) * (staffMember.contracted_hours ?? 40) * 52).toLocaleString('en-GB', { minimumFractionDigits: 0, maximumFractionDigits: 0 }) : '—' }}
                                </p>
                                <p class="text-xs text-gray-400">{{ staffMember.contracted_hours ?? 40 }} hrs × 52 wks</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 mb-1">Total Earned</p>
                                <p class="text-lg font-bold text-[#EF233C]">
                                    {{ staffMember.hourly_rate ? '£' + (totalHours * Number(staffMember.hourly_rate)).toLocaleString('en-GB', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '—' }}
                                </p>
                                <p class="text-xs text-gray-400">from approved hours</p>
                            </div>
                        </div>
                        <div v-if="!staffMember.hourly_rate" class="mt-3 text-xs text-amber-600 bg-amber-50 border border-amber-100 rounded-lg px-3 py-2">
                            No hourly rate set. <Link :href="route('staff.edit', staffMember.id)" class="underline">Edit profile</Link> to add one.
                        </div>
                    </div>

                    <!-- Payslip history -->
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Payslip History</p>
                            <Link :href="route('payroll.index')" class="text-xs text-[#EF233C] hover:underline">View all</Link>
                        </div>
                        <div v-if="recentPayrollRuns.length === 0" class="text-center py-6 text-sm text-gray-400">
                            No payslips generated yet.
                        </div>
                        <div v-else class="space-y-2">
                            <Link v-for="run in recentPayrollRuns" :key="run.id"
                                :href="route('staff.payslip', staffMember.id) + '?run_id=' + run.id"
                                class="flex items-center justify-between p-3 rounded-lg border border-gray-100 hover:border-gray-200 hover:bg-gray-50 transition-all group">
                                <div>
                                    <p class="text-sm font-medium text-gray-800 group-hover:text-[#EF233C] transition-colors">
                                        {{ formatDate(run.period_from) }} – {{ formatDate(run.period_to) }}
                                    </p>
                                    <p class="text-xs text-gray-400 mt-0.5">{{ run.total_hours.toFixed(2) }}h worked</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-semibold text-gray-800">{{ run.has_rate ? '£' + run.gross_pay.toFixed(2) : '—' }}</span>
                                    <span :class="run.status === 'approved' ? 'bg-green-100 text-green-700 border border-green-200' : 'bg-amber-50 text-amber-700 border border-amber-200'"
                                        class="text-xs font-semibold px-2 py-0.5 rounded-full capitalize">{{ run.status }}</span>
                                </div>
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- ── EOD / Daily logs tab ──────────────────────────────── -->
                <div v-show="activeTab === 'eod'" class="p-5">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-semibold text-gray-800">Daily Logs</h3>
                        <Link :href="route('activity-logs.index', { user_id: staffMember.id })" class="text-xs text-[#EF233C] hover:underline">View all</Link>
                    </div>
                    <div v-if="!dailyLogs.length" class="text-center py-10 text-gray-400 text-sm">No daily logs yet.</div>
                    <div v-else class="space-y-2">
                        <Link
                            v-for="l in dailyLogs"
                            :key="l.id"
                            :href="route('activity-logs.show', l.id)"
                            class="flex items-center gap-3 px-3 py-2.5 rounded-xl border border-gray-100 hover:bg-gray-50 transition-colors"
                        >
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-800">{{ formatDate(l.log_date) }}</p>
                                <p class="text-xs text-gray-400">{{ l.activities }} {{ l.activities === 1 ? 'activity' : 'activities' }} · {{ formatMins(l.minutes) }}</p>
                            </div>
                            <span v-if="l.acknowledged" class="text-xs text-emerald-600 inline-flex items-center gap-1"><CheckCircleIcon class="w-4 h-4" /></span>
                            <span :class="l.status === 'submitted' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'" class="text-xs font-medium px-2 py-0.5 rounded-full capitalize">{{ l.status }}</span>
                        </Link>
                    </div>
                </div>

            </div><!-- end tab container -->
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    PencilIcon, NoSymbolIcon, CheckCircleIcon, FolderIcon,
    DocumentTextIcon, ClipboardDocumentCheckIcon, BriefcaseIcon,
    UserCircleIcon, ClockIcon, CurrencyPoundIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    staffMember:       { type: Object, required: true },
    recentEntries:     { type: Array,  default: () => [] },
    totalHours:        { type: Number, default: 0 },
    projects:          { type: Array,  default: () => [] },
    recentPayrollRuns: { type: Array,  default: () => [] },
    jobStats:          { type: Object, default: () => ({ total: 0, thisMonth: 0, completed: 0, completionRate: 0 }) },
    recentJobs:        { type: Array,  default: () => [] },
    canEdit:           { type: Boolean, default: false },
    hasOnboarding:     { type: Boolean, default: false },
    dailyLogs:         { type: Array,  default: () => [] },
});

const activeTab = ref('overview');

const tabs = computed(() => {
    const list = [
        { id: 'overview',    label: 'Overview',    icon: UserCircleIcon,      count: null },
        { id: 'jobs',        label: 'Jobs',        icon: BriefcaseIcon,       count: props.jobStats.total },
        { id: 'projects',    label: 'Projects',    icon: FolderIcon,          count: props.projects.length },
        { id: 'attendance',  label: 'Attendance',  icon: ClockIcon,           count: props.recentEntries.length },
        { id: 'payroll',     label: 'Payroll',     icon: CurrencyPoundIcon,   count: props.recentPayrollRuns.length },
    ];
    // EOD links to the manager-only detail page, so only show it to managers/HR.
    if (props.canEdit) {
        list.push({ id: 'eod', label: 'EOD', icon: ClipboardDocumentCheckIcon, count: props.dailyLogs.length });
    }
    return list;
});

function formatMins(m) {
    if (!m) return '0m';
    const h = Math.floor(m / 60), min = m % 60;
    return (h ? `${h}h ` : '') + (min ? `${min}m` : (h ? '' : '0m'));
}

function toggleActive() {
    router.post(route('staff.toggle-active', props.staffMember.id), {}, { preserveScroll: true });
}

function formatDate(d) {
    return new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: '2-digit', year: 'numeric' });
}

const roleColors = {
    admin:     'text-xs bg-red-100 text-red-700 px-2 py-0.5 rounded-full font-medium capitalize',
    manager:   'text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full font-medium capitalize',
    site_head: 'text-xs bg-purple-100 text-purple-700 px-2 py-0.5 rounded-full font-medium capitalize',
    staff:     'text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full font-medium capitalize',
};
function roleClass(r) { return roleColors[r] ?? roleColors.staff; }

function attendanceStatusClass(s) {
    return { approved: 'text-green-600 font-medium', pending: 'text-amber-600 font-medium', rejected: 'text-red-600 font-medium' }[s] ?? 'text-gray-500';
}

function statusClass(s) {
    return {
        planning: 'inline-flex text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full font-medium',
        active:   'inline-flex text-xs bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full font-medium',
        on_hold:  'inline-flex text-xs bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full font-medium',
        complete: 'inline-flex text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full font-medium',
    }[s] ?? 'inline-flex text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full font-medium';
}

const statusLabels = { planning: 'Planning', active: 'Active', on_hold: 'On Hold', complete: 'Complete' };
function statusLabel(s) { return statusLabels[s] ?? s; }

const statusBarClasses = { planning: 'bg-gray-300', active: 'bg-emerald-400', on_hold: 'bg-amber-400', complete: 'bg-blue-400' };
function statusBarClass(s) { return statusBarClasses[s] ?? 'bg-gray-200'; }

const businessClasses = {
    bcf: 'text-xs font-bold px-1.5 py-0.5 rounded bg-[#EF233C]/10 text-[#EF233C] shrink-0',
    bgr: 'text-xs font-bold px-1.5 py-0.5 rounded bg-blue-100 text-blue-700 shrink-0',
};
function businessClass(b) { return businessClasses[b] ?? businessClasses.bcf; }

function jobStatusBadge(s) {
    return {
        scheduled:   'bg-blue-50 text-blue-700 border border-blue-200',
        in_progress: 'bg-amber-50 text-amber-700 border border-amber-200',
        completed:   'bg-green-50 text-green-700 border border-green-200',
        cancelled:   'bg-gray-100 text-gray-500 border border-gray-200',
    }[s] ?? 'bg-gray-100 text-gray-500 border border-gray-200';
}

function jobStatusLabel(s) {
    return { scheduled: 'Scheduled', in_progress: 'In Progress', completed: 'Completed', cancelled: 'Cancelled' }[s] ?? s;
}

function jobStatusStripe(s) {
    return { scheduled: 'bg-blue-400', in_progress: 'bg-amber-400', completed: 'bg-green-500', cancelled: 'bg-gray-300' }[s] ?? 'bg-gray-200';
}
</script>

<style scoped>
.badge-green { @apply text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full font-medium; }
.badge-red   { @apply text-xs bg-red-100 text-red-600 px-2 py-0.5 rounded-full font-medium; }
.scrollbar-none { scrollbar-width: none; }
.scrollbar-none::-webkit-scrollbar { display: none; }
</style>
