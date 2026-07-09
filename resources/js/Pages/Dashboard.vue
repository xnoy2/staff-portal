<template>
    <AppLayout title="Dashboard">
        <!-- Pending agreement to sign -->
        <Link v-if="pendingAgreement" :href="route('agreements.show', pendingAgreement.id)"
            class="flex items-center gap-3 mb-6 rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 hover:bg-amber-100 transition-colors">
            <ShieldExclamationIcon class="w-6 h-6 text-amber-600 shrink-0" />
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-amber-900">Action required: sign your agreement</p>
                <p class="text-xs text-amber-700 truncate">“{{ pendingAgreement.title }}” is awaiting your signature.</p>
            </div>
            <span class="text-xs font-semibold text-white bg-amber-600 px-3 py-1.5 rounded-lg shrink-0">Review &amp; sign</span>
        </Link>

        <!-- ===== MANAGER / ADMIN VIEW ===== -->
        <template v-if="isManager">
            <!-- Stat cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <StatCard label="Today's Jobs"  :value="stats.todaysJobs"     icon="ClipboardDocumentListIcon" color="green" href="/jobs" />
                <StatCard label="Clocked In"    :value="stats.clockedInStaff" icon="UserGroupIcon"             color="blue"  href="/attendance" />
                <StatCard label="Pending Leave" :value="stats.pendingLeave"   icon="CalendarDaysIcon"          color="amber" href="/leave" />
                <StatCard label="Pending OT"    :value="stats.pendingOt"      icon="ClockIcon"                 color="red"   href="/overtime" />
            </div>

            <!-- Row 1: My Clock + Today's jobs + Staff clocked in now -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
                <ClockWidget :active-entry="activeEntry" :today-approved-ot="todayApprovedOt" />
                <!-- Today's jobs mini-list -->
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-sm font-semibold text-gray-800">Today's Jobs</h2>
                        <Link href="/jobs" class="text-xs text-[#EF233C] hover:underline">View all</Link>
                    </div>
                    <div v-if="todaysJobs.length === 0" class="text-center py-8 text-gray-400 text-sm">
                        No jobs scheduled for today.
                    </div>
                    <ul v-else class="space-y-2">
                        <li
                            v-for="job in todaysJobs"
                            :key="job.id"
                            class="flex items-center justify-between p-3 rounded-lg bg-gray-50 hover:bg-gray-100 transition-colors gap-3"
                        >
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-gray-800 truncate">{{ job.title }}</p>
                                <p class="text-xs text-gray-500 truncate">
                                    {{ job.project?.name ?? '—' }}
                                    <span v-if="job.project?.business"> · {{ job.project.business }}</span>
                                    <span v-if="job.van"> · {{ job.van }}</span>
                                    <span v-if="job.staff_count"> · {{ job.staff_count }} staff</span>
                                </p>
                            </div>
                            <span :class="['text-xs px-2 py-1 rounded-full font-medium flex-shrink-0', jobStatusClass(job.status)]">
                                {{ jobStatusLabel(job.status) }}
                            </span>
                        </li>
                    </ul>
                </div>

                <!-- Staff clocked in now -->
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-sm font-semibold text-gray-800">Staff Working Now</h2>
                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full"
                            :class="clockedInStaff.length ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'">
                            {{ clockedInStaff.length }} active
                        </span>
                    </div>
                    <div v-if="clockedInStaff.length === 0" class="text-center py-8 text-gray-400 text-sm">
                        No staff currently clocked in.
                    </div>
                    <ul v-else class="space-y-2 max-h-72 overflow-y-auto">
                        <li v-for="s in clockedInStaff" :key="s.id" class="flex items-center gap-1.5">
                            <!-- Row (link) -->
                            <Link
                                :href="route('staff.show', s.id)"
                                class="flex-1 flex items-center gap-3 p-2.5 rounded-lg bg-gray-50 hover:bg-gray-100 transition-colors min-w-0"
                            >
                                <!-- Avatar + state dot -->
                                <div class="relative flex-shrink-0">
                                    <img :src="s.avatar_url" :alt="s.name" class="w-9 h-9 rounded-full object-cover" />
                                    <span :class="[
                                        'absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 rounded-full border-2 border-white',
                                        s.clock_state === 'working'  ? 'bg-green-500' :
                                        s.clock_state === 'on_lunch' ? 'bg-blue-500'  : 'bg-amber-400',
                                    ]" />
                                </div>

                                <!-- Name + role -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <p class="text-sm font-medium text-gray-800 truncate">{{ s.name }}</p>
                                        <span v-if="s.ot_type" class="text-[10px] font-bold uppercase px-1 py-0.5 rounded bg-amber-100 text-amber-700">{{ s.ot_type }}</span>
                                    </div>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <span class="text-[11px] capitalize text-gray-400">{{ s.role.replace('_', ' ') }}</span>
                                        <span :class="[
                                            'text-[10px] font-semibold px-1.5 py-0.5 rounded-full',
                                            s.clock_state === 'working'  ? 'bg-green-100 text-green-700' :
                                            s.clock_state === 'on_lunch' ? 'bg-blue-100 text-blue-700'   : 'bg-amber-100 text-amber-700',
                                        ]">
                                            {{ s.clock_state === 'working' ? 'Working' : s.clock_state === 'on_lunch' ? 'Lunch' : 'Break' }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Duration -->
                                <div class="text-right flex-shrink-0">
                                    <p class="text-xs font-mono font-semibold text-gray-700">{{ liveDuration(s.clock_in) }}</p>
                                    <p class="text-[11px] text-gray-400">since {{ s.since }}</p>
                                </div>
                            </Link>

                            <!-- Force clock-out: 2-step confirm -->
                            <template v-if="forceOutTarget === s.id">
                                <span class="text-[10px] text-red-600 font-semibold whitespace-nowrap">Sure?</span>
                                <button
                                    @click="confirmForceOut(s.id)"
                                    class="text-[10px] bg-red-500 hover:bg-red-600 text-white px-2 py-1 rounded-lg font-semibold transition-colors whitespace-nowrap"
                                >Yes</button>
                                <button
                                    @click="forceOutTarget = null"
                                    class="text-[10px] text-gray-400 hover:text-gray-600 px-1"
                                >No</button>
                            </template>
                            <button
                                v-else
                                @click="forceOutTarget = s.id"
                                class="flex-shrink-0 p-1.5 rounded-lg text-gray-300 hover:text-red-500 hover:bg-red-50 transition-colors"
                                title="Force clock out"
                            >
                                <ArrowRightOnRectangleIcon class="w-4 h-4" />
                            </button>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Row 2: Jobs this week + Projects by status -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">
                <!-- Jobs this week grouped bar -->
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <h2 class="text-sm font-semibold text-gray-800 mb-4">Jobs This Week</h2>
                    <Chart type="bar" :data="weekJobsChartData" :options="weekJobsChartOptions" class="h-52" />
                </div>

                <!-- Projects by status -->
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <h2 class="text-sm font-semibold text-gray-800 mb-4">Projects by Status</h2>
                    <Chart type="bar" :data="projectChartData" :options="chartOptions" class="h-52" />
                </div>
            </div>

            <!-- Row 3: Staff hours this week (full width horizontal bar) -->
            <div v-if="staffHoursWeek.length > 0" class="bg-white rounded-xl border border-gray-200 p-5">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-sm font-semibold text-gray-800">Staff Hours This Week</h2>
                    <Link href="/attendance" class="text-xs text-[#EF233C] hover:underline">View attendance</Link>
                </div>
                <Chart type="bar" :data="staffHoursChartData" :options="staffHoursChartOptions"
                    :style="{ height: Math.max(160, staffHoursWeek.length * 36) + 'px' }" />
            </div>
        </template>

        <!-- ===== STAFF / SITE HEAD VIEW ===== -->
        <template v-else>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Left: Clock in/out + QR -->
                <div class="space-y-4">
                    <!-- Clock card -->
                    <ClockWidget :active-entry="activeEntry" :today-approved-ot="todayApprovedOt" />

                    <!-- QR Code -->
                    <div class="bg-white rounded-xl border border-gray-200 p-6 text-center">
                        <h2 class="text-sm font-semibold text-gray-700 mb-3">My QR Code</h2>
                        <div class="inline-flex items-center justify-center bg-white border-2 border-gray-200 rounded-xl p-4">
                            <img :src="qrCodeUrl" :alt="`QR code for ${user.name}`" class="w-32 h-32" />
                        </div>
                        <p class="mt-2 text-xs text-gray-400">Present this to your site head for scanning</p>
                    </div>

                    <!-- Today's activity log -->
                    <Link v-if="todayLog" href="/my-day" class="block bg-white rounded-xl border border-gray-200 p-4 hover:border-[#EF233C]/40 transition-colors">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-lg bg-amber-50 flex items-center justify-center flex-shrink-0">
                                <SunIcon class="w-5 h-5 text-amber-500" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-gray-800">Today's Log</p>
                                <p class="text-xs text-gray-400">{{ todayLog.status === 'submitted' ? 'Submitted for today' : 'Write your end-of-day summary' }}</p>
                            </div>
                            <span v-if="todayLog.status === 'submitted'" class="text-xs font-medium bg-emerald-50 text-emerald-700 px-2 py-0.5 rounded-full">Submitted</span>
                            <span v-else class="text-xs font-medium bg-amber-50 text-amber-700 px-2 py-0.5 rounded-full">Log now</span>
                        </div>
                    </Link>
                </div>

                <!-- Right: Weekly hours + recent entries + leave + upcoming jobs -->
                <div class="space-y-4">
                    <!-- Weekly hours bar chart -->
                    <div class="bg-white rounded-xl border border-gray-200 p-5">
                        <h2 class="text-sm font-semibold text-gray-800 mb-4">This Week's Hours</h2>
                        <Chart
                            type="bar"
                            :data="weeklyChartData"
                            :options="weeklyChartOptions"
                            class="h-40"
                        />
                    </div>

                    <!-- Leave balance -->
                    <div class="bg-white rounded-xl border border-gray-200 p-5">
                        <div class="flex items-center justify-between mb-3">
                            <h2 class="text-sm font-semibold text-gray-800">Annual Leave</h2>
                            <Link href="/leave" class="text-xs text-[#EF233C] hover:underline">View all</Link>
                        </div>
                        <div class="flex items-end justify-between mb-2">
                            <span class="text-3xl font-black text-gray-800">{{ leaveBalance.remaining }}<span class="text-base font-medium text-gray-400 ml-1">days left</span></span>
                            <span class="text-xs text-gray-400">of {{ leaveBalance.entitlement }} days</span>
                        </div>
                        <!-- Progress bar -->
                        <div class="w-full bg-gray-100 rounded-full h-2 mb-2">
                            <div
                                class="h-2 rounded-full transition-all"
                                :class="leaveBalance.remaining <= 5 ? 'bg-red-400' : 'bg-emerald-400'"
                                :style="{ width: Math.min(100, (leaveBalance.remaining / leaveBalance.entitlement) * 100) + '%' }"
                            />
                        </div>
                        <div class="flex items-center gap-4 text-xs text-gray-400">
                            <span>Used: <span class="font-semibold text-gray-600">{{ leaveBalance.used }}d</span></span>
                            <span v-if="leaveBalance.pending > 0">Pending: <span class="font-semibold text-amber-600">{{ leaveBalance.pending }}d</span></span>
                        </div>
                    </div>

                    <!-- Upcoming jobs -->
                    <div class="bg-white rounded-xl border border-gray-200 p-5">
                        <div class="flex items-center justify-between mb-3">
                            <h2 class="text-sm font-semibold text-gray-800">Upcoming Jobs</h2>
                            <Link href="/jobs" class="text-xs text-[#EF233C] hover:underline">Daily Job Board</Link>
                        </div>
                        <div v-if="upcomingJobs.length === 0" class="text-center py-6 text-gray-400 text-sm">
                            No upcoming jobs assigned.
                        </div>
                        <ul v-else class="space-y-2">
                            <li v-for="job in upcomingJobs" :key="job.id" class="flex items-start gap-3 p-2.5 rounded-lg bg-gray-50">
                                <div class="flex-shrink-0 text-center mt-0.5">
                                    <p class="text-[10px] font-bold text-gray-400 uppercase leading-none">{{ fmtJobMonth(job.date) }}</p>
                                    <p class="text-lg font-black text-gray-800 leading-tight">{{ fmtJobDay(job.date) }}</p>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-gray-800 truncate">{{ job.title }}</p>
                                    <p class="text-xs text-gray-400 truncate">
                                        <span v-if="job.start_time">{{ job.start_time }} · </span>
                                        <span v-if="job.project">{{ job.project }}</span>
                                        <span v-if="job.business"> · {{ job.business }}</span>
                                    </p>
                                </div>
                            </li>
                        </ul>
                    </div>

                    <!-- Recent entries -->
                    <div class="bg-white rounded-xl border border-gray-200 p-5">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-sm font-semibold text-gray-800">Recent Entries</h2>
                            <Link href="/attendance" class="text-xs text-[#EF233C] hover:underline">View all</Link>
                        </div>
                        <div v-if="recentEntries.length === 0" class="text-center py-6 text-gray-400 text-sm">
                            No recent entries yet.
                        </div>
                        <div v-else class="overflow-x-auto -mx-5 px-5">
                        <table class="w-full text-xs min-w-[280px]">
                            <thead>
                                <tr class="text-gray-400 border-b border-gray-100">
                                    <th class="text-left pb-2 font-medium">Date</th>
                                    <th class="text-left pb-2 font-medium">In</th>
                                    <th class="text-left pb-2 font-medium">Out</th>
                                    <th class="text-right pb-2 font-medium">Hrs</th>
                                    <th class="text-right pb-2 font-medium">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="entry in recentEntries"
                                    :key="entry.id"
                                    class="border-b border-gray-50 last:border-0"
                                >
                                    <td class="py-2 text-gray-700 whitespace-nowrap">{{ fmtDate(entry.clock_in) }}</td>
                                    <td class="py-2 text-gray-600 whitespace-nowrap">{{ fmtTime(entry.clock_in) }}</td>
                                    <td class="py-2 text-gray-600 whitespace-nowrap">{{ fmtTime(entry.clock_out) }}</td>
                                    <td class="py-2 text-right text-gray-700">{{ entry.hours ?? '—' }}</td>
                                    <td class="py-2 text-right">
                                        <span :class="statusClass(entry.status)">{{ entry.status }}</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import { usePermission } from '@/Composables/usePermission';
import AppLayout from '@/Layouts/AppLayout.vue';
import Chart from 'primevue/chart';
import StatCard from '@/Components/Dashboard/StatCard.vue';
import ClockWidget from '@/Components/ClockWidget.vue';
import { ArrowRightOnRectangleIcon, SunIcon, ShieldExclamationIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    isManager:        { type: Boolean, default: false },
    pendingAgreement: { type: Object, default: null },
    stats:            { type: Object, default: () => ({ todaysJobs: 0, clockedInStaff: 0, pendingLeave: 0, pendingOt: 0 }) },
    todaysJobs:       { type: Array,  default: () => [] },
    projectsByStatus: { type: Object, default: () => ({ labels: [], data: [] }) },
    clockedInStaff:   { type: Array,  default: () => [] },
    weekJobsByDay:    { type: Object, default: () => ({ scheduled: [], in_progress: [], completed: [] }) },
    staffHoursWeek:   { type: Array,  default: () => [] },
    activeEntry:      { type: Object, default: null },
    weeklyHours:      { type: Array,  default: () => [0, 0, 0, 0, 0, 0, 0] },
    recentEntries:    { type: Array,  default: () => [] },
    leaveBalance:     { type: Object, default: () => ({ entitlement: 28, used: 0, pending: 0, remaining: 28 }) },
    upcomingJobs:     { type: Array,  default: () => [] },
    todayApprovedOt:  { type: String, default: null },
    todayLog:         { type: Object, default: null },
});

const page = usePage();
const { isManager } = usePermission();
const user = computed(() => page.props.auth.user);

// Reactive clocked-in staff list — starts with SSR data, updated via Reverb
const clockedInStaff = ref([...props.clockedInStaff]);
watch(() => props.clockedInStaff, (val) => { clockedInStaff.value = [...val]; });

// Live clock
const now = ref(new Date());
let clockInterval = null;
onMounted(() => {
    clockInterval = setInterval(() => { now.value = new Date(); }, 1000);

    // Subscribe to real-time attendance updates
    if (window.Echo && isManager.value) {
        window.Echo.private('admin.attendance')
            .listen('.attendance.updated', (data) => {
                clockedInStaff.value = data.clockedInStaff ?? [];
            });
    }
});
onUnmounted(() => {
    clearInterval(clockInterval);
    window.Echo?.leave('admin.attendance');
});

function fmtTime(iso) {
    if (!iso) return '—';
    return new Date(iso).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}
function fmtDate(iso) {
    if (!iso) return '';
    return new Date(iso).toLocaleDateString([], { year: 'numeric', month: '2-digit', day: '2-digit' });
}
function fmtJobDay(dateStr) {
    return new Date(dateStr + 'T00:00:00').toLocaleDateString('en-GB', { day: '2-digit' });
}
function fmtJobMonth(dateStr) {
    return new Date(dateStr + 'T00:00:00').toLocaleDateString('en-GB', { month: 'short' });
}

function liveDuration(clockInIso) {
    if (!clockInIso) return '—';
    const secs = Math.max(0, Math.floor((now.value.getTime() - new Date(clockInIso).getTime()) / 1000));
    const h = Math.floor(secs / 3600);
    const m = Math.floor((secs % 3600) / 60);
    const s = secs % 60;
    return `${h}h ${String(m).padStart(2, '0')}m ${String(s).padStart(2, '0')}s`;
}

// QR code: base64-encodes the UUID so the scanner can decode it
const qrCodeUrl = computed(() => {
    const encoded = btoa(user.value.id);
    return `https://api.qrserver.com/v1/create-qr-code/?size=128x128&data=${encodeURIComponent(encoded)}&margin=10`;
});

// ── Force clock-out (manager only) ───────────────────────────────────────────

const forceOutTarget = ref(null);
let forceOutTimeout  = null;

watch(forceOutTarget, (val) => {
    clearTimeout(forceOutTimeout);
    if (val) forceOutTimeout = setTimeout(() => { forceOutTarget.value = null; }, 5000);
});

function confirmForceOut(userId) {
    clearTimeout(forceOutTimeout);
    forceOutTarget.value = null;
    router.post(route('attendance.force-out', userId), {}, { preserveScroll: true });
}

// Manager: project chart
const projectChartData = computed(() => ({
    labels: props.projectsByStatus.labels,
    datasets: [{
        label: 'Projects',
        data: props.projectsByStatus.data,
        backgroundColor: ['#6366f1', '#22c55e', '#f59e0b', '#9ca3af'],
        borderRadius: 6,
    }],
}));

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: { y: { beginAtZero: true, ticks: { stepSize: 1, precision: 0 } } },
};

// Manager: jobs this week grouped bar
const weekJobsChartData = computed(() => ({
    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
    datasets: [
        { label: 'Scheduled',    data: props.weekJobsByDay.scheduled   ?? [], backgroundColor: '#93c5fd', borderRadius: 4 },
        { label: 'In Progress',  data: props.weekJobsByDay.in_progress ?? [], backgroundColor: '#fbbf24', borderRadius: 4 },
        { label: 'Completed',    data: props.weekJobsByDay.completed   ?? [], backgroundColor: '#4ade80', borderRadius: 4 },
    ],
}));

const weekJobsChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } } },
    scales: { x: { stacked: false }, y: { beginAtZero: true, ticks: { stepSize: 1, precision: 0 } } },
};

// Manager: staff hours this week (horizontal bar)
const staffHoursChartData = computed(() => ({
    labels: props.staffHoursWeek.map(s => s.name),
    datasets: [{
        label: 'Hours',
        data: props.staffHoursWeek.map(s => s.hours),
        backgroundColor: '#EF233C',
        borderRadius: 4,
    }],
}));

const staffHoursChartOptions = {
    indexAxis: 'y',
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: { x: { beginAtZero: true, ticks: { stepSize: 4 } } },
};

// Staff: weekly chart
const weeklyChartData = computed(() => ({
    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
    datasets: [{
        label: 'Hours',
        data: props.weeklyHours,
        backgroundColor: '#EF233C',
        borderRadius: 4,
    }],
}));

const weeklyChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: { y: { beginAtZero: true, max: 12, ticks: { stepSize: 2 } } },
};

// Phase badge colors
function jobStatusClass(s) {
    const map = {
        scheduled:   'bg-blue-50 text-blue-700',
        in_progress: 'bg-amber-50 text-amber-700',
        completed:   'bg-green-50 text-green-700',
        cancelled:   'bg-gray-100 text-gray-500',
    };
    return map[s] ?? 'bg-gray-100 text-gray-500';
}

function jobStatusLabel(s) {
    return { scheduled: 'Scheduled', in_progress: 'In Progress', completed: 'Completed', cancelled: 'Cancelled' }[s] ?? s;
}

function phaseClass(phase) {
    const map = {
        planning:     'bg-gray-100 text-gray-600',
        installation: 'bg-blue-100 text-blue-700',
        inspection:   'bg-amber-100 text-amber-700',
        complete:     'bg-green-100 text-green-700',
    };
    return map[phase?.toLowerCase()] ?? 'bg-gray-100 text-gray-600';
}

// Entry status colors
function statusClass(status) {
    const map = {
        approved: 'text-green-600 font-medium',
        pending:  'text-amber-600 font-medium',
        rejected: 'text-red-600 font-medium',
    };
    return map[status?.toLowerCase()] ?? 'text-gray-500';
}
</script>

