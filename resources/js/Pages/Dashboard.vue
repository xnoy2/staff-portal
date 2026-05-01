<template>
    <AppLayout title="Dashboard">
        <!-- ===== MANAGER / ADMIN VIEW ===== -->
        <template v-if="isManager">
            <!-- Stat cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <StatCard
                    label="Today's Jobs"
                    :value="stats.todaysJobs"
                    icon="ClipboardDocumentListIcon"
                    color="green"
                    href="/jobs"
                />
                <StatCard
                    label="Clocked In"
                    :value="stats.clockedInStaff"
                    icon="UserGroupIcon"
                    color="blue"
                    href="/attendance"
                />
                <StatCard
                    label="Pending Approvals"
                    :value="stats.pendingApprovals"
                    icon="ClockIcon"
                    color="amber"
                    href="/approvals"
                />
                <StatCard
                    label="Low Stock Items"
                    :value="stats.lowStockItems"
                    icon="ExclamationTriangleIcon"
                    color="red"
                    href="/inventory"
                />
            </div>

            <!-- Lower grid: today's jobs + project chart -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
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

                <!-- Projects by status chart -->
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <h2 class="text-sm font-semibold text-gray-800 mb-4">Projects by Status</h2>
                    <Chart
                        type="bar"
                        :data="projectChartData"
                        :options="chartOptions"
                        class="h-48"
                    />
                </div>
            </div>
        </template>

        <!-- ===== STAFF / SITE HEAD VIEW ===== -->
        <template v-else>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Left: Clock in/out + QR -->
                <div class="space-y-4">
                    <!-- Clock card -->
                    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                        <!-- Status banner -->
                        <div :class="['px-5 py-2.5 flex items-center justify-between transition-colors duration-500', headerBg]">
                            <div class="flex items-center gap-2">
                                <span :class="['w-2 h-2 rounded-full transition-colors', clockState === 'idle' ? 'bg-gray-400' : 'bg-white animate-pulse']" />
                                <span :class="['text-xs font-semibold', clockState === 'idle' ? 'text-gray-500' : 'text-white']">
                                    {{ headerLabel }}
                                </span>
                            </div>
                            <span v-if="activeEntry" class="text-xs text-white/80 font-medium">
                                Since {{ clockInTime }}
                            </span>
                        </div>

                        <!-- Clock face -->
                        <div class="p-6 text-center">
                            <p class="text-xs text-gray-400 mb-0.5">{{ currentDate }}</p>
                            <p class="text-5xl font-bold text-gray-800 tabular-nums tracking-tight mb-5">{{ formattedClock }}</p>

                            <!-- Duration badge -->
                            <div class="flex flex-col items-center mb-5 gap-1" style="min-height:2.25rem;">
                                <template v-if="clockState === 'working'">
                                    <div class="inline-flex items-center gap-1.5 bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm font-medium px-3 py-1 rounded-full">
                                        <ClockIcon class="w-3.5 h-3.5" />
                                        {{ fmtDuration(workedSeconds) }} net worked
                                    </div>
                                </template>
                                <template v-else-if="activeEntry">
                                    <div :class="['inline-flex items-center gap-1.5 text-sm font-medium px-3 py-1 rounded-full', clockState === 'on_lunch' ? 'bg-blue-50 border border-blue-200 text-blue-700' : 'bg-amber-50 border border-amber-200 text-amber-700']">
                                        <ClockIcon class="w-3.5 h-3.5" />
                                        {{ fmtDuration(breakSeconds) }} {{ clockState === 'on_lunch' ? 'on lunch' : 'on break' }}
                                    </div>
                                    <p class="text-xs text-gray-400">{{ fmtDuration(workedSeconds) }} worked</p>
                                </template>
                            </div>

                            <!-- Circular button -->
                            <div class="flex justify-center">
                                <div class="relative flex items-center justify-center">
                                    <!-- Ripple rings (working state only) -->
                                    <template v-if="clockState === 'working' && !clockLoading">
                                        <span :class="['absolute w-32 h-32 rounded-full clock-ring-1 pointer-events-none', ringColor]" />
                                        <span :class="['absolute w-32 h-32 rounded-full clock-ring-2 pointer-events-none', ringColor]" />
                                    </template>

                                    <button
                                        @click="handleCenterClick"
                                        :disabled="clockLoading || breakLoading"
                                        :class="[
                                            'relative z-10 w-32 h-32 rounded-full text-white font-bold flex items-center justify-center flex-col gap-2 transition-colors duration-300',
                                            clockLoading || breakLoading ? 'opacity-60 cursor-not-allowed' : 'cursor-pointer',
                                            !clockLoading && clockState === 'working' ? 'clock-heartbeat' : '',
                                            !clockLoading && clockState === 'idle'    ? 'clock-breathe'   : '',
                                            centerBtnColor,
                                        ]"
                                    >
                                        <StopCircleIcon v-if="clockState === 'working'" class="w-9 h-9" />
                                        <PlayCircleIcon v-else class="w-9 h-9" />
                                        <span class="text-xs font-semibold tracking-wide text-center px-1 leading-tight">
                                            {{ centerBtnLabel }}
                                        </span>
                                    </button>
                                </div>
                            </div>

                            <!-- Break / Lunch buttons (working state) -->
                            <div v-if="clockState === 'working'" class="flex gap-2 mt-5 justify-center flex-wrap">
                                <button
                                    @click="doStartBreak('break')"
                                    :disabled="clockLoading || breakLoading"
                                    class="inline-flex items-center gap-1.5 bg-amber-50 text-amber-700 border border-amber-200 hover:bg-amber-100 disabled:opacity-50 font-medium px-4 py-2 rounded-xl text-xs transition-colors"
                                >
                                    <PauseCircleIcon class="w-3.5 h-3.5" />
                                    Start Break
                                </button>
                                <button
                                    @click="doStartBreak('lunch')"
                                    :disabled="clockLoading || breakLoading"
                                    class="inline-flex items-center gap-1.5 bg-blue-50 text-blue-700 border border-blue-200 hover:bg-blue-100 disabled:opacity-50 font-medium px-4 py-2 rounded-xl text-xs transition-colors"
                                >
                                    <PauseCircleIcon class="w-3.5 h-3.5" />
                                    Lunch Break
                                </button>
                            </div>

                            <!-- Clock Out secondary (on break / lunch) -->
                            <div v-else-if="clockState !== 'idle'" class="mt-5 flex justify-center">
                                <button
                                    @click="doClockOut"
                                    :disabled="clockLoading || breakLoading"
                                    class="inline-flex items-center gap-1.5 text-red-600 border border-red-200 hover:bg-red-50 font-medium px-4 py-2 rounded-xl text-xs transition-colors disabled:opacity-50"
                                >
                                    <StopCircleIcon class="w-3.5 h-3.5" />
                                    Clock Out
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- QR Code -->
                    <div class="bg-white rounded-xl border border-gray-200 p-6 text-center">
                        <h2 class="text-sm font-semibold text-gray-700 mb-3">My QR Code</h2>
                        <div class="inline-flex items-center justify-center bg-white border-2 border-gray-200 rounded-xl p-4">
                            <img :src="qrCodeUrl" :alt="`QR code for ${user.name}`" class="w-32 h-32" />
                        </div>
                        <p class="mt-2 text-xs text-gray-400">Present this to your site head for scanning</p>
                    </div>
                </div>

                <!-- Right: Weekly hours + recent entries -->
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
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import { usePermission } from '@/Composables/usePermission';
import AppLayout from '@/Layouts/AppLayout.vue';
import Chart from 'primevue/chart';
import StatCard from '@/Components/Dashboard/StatCard.vue';
import { PlayCircleIcon, StopCircleIcon, PauseCircleIcon } from '@heroicons/vue/24/solid';
import { ClockIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    isManager:        { type: Boolean, default: false },
    stats:            { type: Object, default: () => ({ todaysJobs: 0, clockedInStaff: 0, pendingApprovals: 0, lowStockItems: 0 }) },
    todaysJobs:       { type: Array,  default: () => [] },
    projectsByStatus: { type: Object, default: () => ({ labels: [], data: [] }) },
    activeEntry:      { type: Object, default: null },
    weeklyHours:      { type: Array,  default: () => [0, 0, 0, 0, 0, 0, 0] },
    recentEntries:    { type: Array,  default: () => [] },
});

const page = usePage();
const { isManager } = usePermission();
const user = computed(() => page.props.auth.user);

// Live clock
const now = ref(new Date());
let clockInterval = null;
onMounted(() => { clockInterval = setInterval(() => { now.value = new Date(); }, 1000); });
onUnmounted(() => clearInterval(clockInterval));

const formattedClock = computed(() =>
    now.value.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' })
);

const clockInTime = computed(() => {
    if (!props.activeEntry?.clock_in) return '';
    return new Date(props.activeEntry.clock_in).toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });
});
const currentDate = computed(() =>
    now.value.toLocaleDateString('en-GB', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })
);

function fmtTime(iso) {
    if (!iso) return '—';
    return new Date(iso).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}
function fmtDate(iso) {
    if (!iso) return '';
    return new Date(iso).toLocaleDateString([], { year: 'numeric', month: '2-digit', day: '2-digit' });
}

const clockState = computed(() => props.activeEntry?.clock_state ?? 'idle');

// Net worked seconds — freezes while on a break
const workedSeconds = computed(() => {
    if (!props.activeEntry) return 0;
    const clockIn      = new Date(props.activeEntry.clock_in).getTime();
    const pastBreaksMs = (props.activeEntry.total_break_minutes ?? 0) * 60_000;
    const endpoint     = props.activeEntry.active_break
        ? new Date(props.activeEntry.active_break.started_at).getTime()
        : now.value.getTime();
    return Math.max(0, Math.floor((endpoint - clockIn - pastBreaksMs) / 1000));
});

// Break/lunch duration — only ticks while on break
const breakSeconds = computed(() => {
    if (!props.activeEntry?.active_break) return 0;
    const start = new Date(props.activeEntry.active_break.started_at).getTime();
    return Math.max(0, Math.floor((now.value.getTime() - start) / 1000));
});

function fmtDuration(s) {
    const h = Math.floor(s / 3600);
    const m = Math.floor((s % 3600) / 60);
    return `${h}h ${m}m`;
}

// State-based presentation
const headerBg = computed(() => ({
    idle:     'bg-gray-50 border-b border-gray-100',
    working:  'bg-emerald-500',
    on_break: 'bg-amber-500',
    on_lunch: 'bg-blue-600',
}[clockState.value] ?? 'bg-gray-50 border-b border-gray-100'));

const headerLabel = computed(() => ({
    idle:     'Not Clocked In',
    working:  'Currently Clocked In',
    on_break: 'On Break',
    on_lunch: 'Lunch Break',
}[clockState.value] ?? 'Not Clocked In'));

const ringColor = computed(() => ({
    working:  'bg-emerald-400',
    on_break: 'bg-amber-400',
    on_lunch: 'bg-blue-400',
}[clockState.value] ?? 'bg-emerald-400'));

const centerBtnColor = computed(() => ({
    idle:     'bg-[#EF233C] hover:bg-[#D90429]',
    working:  'bg-emerald-500 hover:bg-emerald-600 shadow-lg shadow-emerald-500/40',
    on_break: 'bg-amber-500 hover:bg-amber-600 shadow-lg shadow-amber-500/40',
    on_lunch: 'bg-blue-600 hover:bg-blue-700 shadow-lg shadow-blue-500/40',
}[clockState.value] ?? 'bg-[#EF233C] hover:bg-[#D90429]'));

const centerBtnLabel = computed(() => ({
    idle:     'Clock In',
    working:  'Clock Out',
    on_break: 'Resume Work',
    on_lunch: 'Back from Lunch',
}[clockState.value] ?? 'Clock In'));

// QR code: base64-encodes the UUID so the scanner can decode it
const qrCodeUrl = computed(() => {
    const encoded = btoa(user.value.id);
    return `https://api.qrserver.com/v1/create-qr-code/?size=128x128&data=${encodeURIComponent(encoded)}&margin=10`;
});

// Clock actions
const clockLoading = ref(false);
const breakLoading = ref(false);

function handleCenterClick() {
    if (clockState.value === 'idle') {
        clockLoading.value = true;
        router.post('/attendance/clock-in', {}, { preserveScroll: true, onFinish: () => { clockLoading.value = false; } });
    } else if (clockState.value === 'working') {
        doClockOut();
    } else {
        doEndBreak();
    }
}

function doClockOut() {
    clockLoading.value = true;
    router.post('/attendance/clock-out', {}, { preserveScroll: true, onFinish: () => { clockLoading.value = false; } });
}

function doStartBreak(type) {
    breakLoading.value = true;
    router.post('/attendance/break/start', { type }, { preserveScroll: true, onFinish: () => { breakLoading.value = false; } });
}

function doEndBreak() {
    breakLoading.value = true;
    router.post('/attendance/break/end', {}, { preserveScroll: true, onFinish: () => { breakLoading.value = false; } });
}

// Manager: project chart
const projectChartData = computed(() => ({
    labels: props.projectsByStatus.labels,
    datasets: [{
        label: 'Projects',
        data: props.projectsByStatus.data,
        backgroundColor: ['#EF233C', '#EF233C', '#f59e0b', '#D90429'],
        borderRadius: 6,
    }],
}));

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: { y: { beginAtZero: true, ticks: { stepSize: 1, precision: 0 } } },
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

<style scoped>
/* Expanding ripple rings when clocked in */
@keyframes ping-ring {
    0%   { transform: scale(1);   opacity: 0.45; }
    100% { transform: scale(2.4); opacity: 0;    }
}
.clock-ring-1 { animation: ping-ring 2s ease-out infinite; }
.clock-ring-2 { animation: ping-ring 2s ease-out infinite 0.75s; }

/* Heartbeat on the button when clocked in */
@keyframes heartbeat {
    0%,  100% { transform: scale(1);    }
    12%        { transform: scale(1.07); }
    24%        { transform: scale(1);    }
    38%        { transform: scale(1.05); }
    55%        { transform: scale(1);    }
}
.clock-heartbeat { animation: heartbeat 1.5s ease-in-out infinite; }

/* Soft breathing glow on the idle clock-in button */
@keyframes breathe-glow {
    0%,  100% { box-shadow: 0 8px 30px -4px rgba(239, 35, 60, 0.35); }
    50%        { box-shadow: 0 8px 50px  4px rgba(239, 35, 60, 0.65); }
}
.clock-breathe { animation: breathe-glow 2.6s ease-in-out infinite; }
</style>
