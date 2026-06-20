<template>
    <AppLayout :title="pageTitle">
        <div class="max-w-7xl mx-auto space-y-4">

            <!-- ── Header ────────────────────────────────────────────── -->
            <div class="bg-[#2B2D42] rounded-2xl px-5 py-4 flex flex-wrap items-center gap-3">
                <!-- Icon + Title -->
                <div class="flex items-center gap-3 flex-1 min-w-0">
                    <div class="w-9 h-9 rounded-xl bg-white/10 flex items-center justify-center flex-shrink-0">
                        <ClipboardDocumentListIcon class="w-5 h-5 text-white" />
                    </div>
                    <div class="min-w-0">
                        <p class="text-white font-bold text-sm leading-tight">{{ pageTitle }}</p>
                        <p class="text-white/50 text-xs mt-0.5">
                            {{ jobs.meta?.total ?? 0 }} job{{ (jobs.meta?.total ?? 0) !== 1 ? 's' : '' }}
                            {{ activeFilter === 'upcoming' ? 'upcoming' : activeFilter === 'past' ? 'in the past' : 'total' }}
                        </p>
                    </div>
                </div>

                <!-- Period pills -->
                <div class="flex items-center gap-1 bg-white/10 rounded-xl p-1">
                    <button
                        v-for="p in periods"
                        :key="p.value"
                        @click="setPeriod(p.value)"
                        :class="[
                            'text-xs font-semibold px-3 py-1.5 rounded-lg transition-colors whitespace-nowrap',
                            activeFilter === p.value
                                ? 'bg-[#EF233C] text-white'
                                : 'text-white/60 hover:text-white hover:bg-white/10',
                        ]"
                    >{{ p.label }}</button>
                </div>

                <!-- Daily Job Board shortcut (admin/manager only) -->
                <Link
                    v-if="isManager"
                    :href="route('jobs.index')"
                    class="flex items-center gap-1.5 text-xs font-semibold text-white/70 hover:text-white border border-white/20 hover:border-white/40 hover:bg-white/10 px-3 py-1.5 rounded-xl transition-colors whitespace-nowrap"
                >
                    <CalendarDaysIcon class="w-4 h-4" />
                    Daily Job Board
                </Link>
            </div>

            <!-- ── Filter bar ─────────────────────────────────────────── -->
            <div class="bg-white rounded-2xl border border-gray-200 px-4 py-3 flex flex-wrap items-center gap-3">
                <!-- Status filter -->
                <div class="flex items-center gap-2">
                    <label class="text-xs font-semibold text-gray-500 whitespace-nowrap">Status</label>
                    <select
                        :value="filters.status ?? ''"
                        @change="applyFilter('status', $event.target.value)"
                        class="text-xs rounded-lg border-gray-200 focus:ring-[#EF233C] focus:border-[#EF233C] py-1.5"
                    >
                        <option value="">All statuses</option>
                        <option value="scheduled">Scheduled</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>

                <!-- Date range -->
                <div class="flex items-center gap-2">
                    <label class="text-xs font-semibold text-gray-500">From</label>
                    <input
                        type="date"
                        :value="filters.from ?? ''"
                        @change="applyFilter('from', $event.target.value)"
                        class="text-xs rounded-lg border-gray-200 focus:ring-[#EF233C] focus:border-[#EF233C] py-1.5 [color-scheme:dark]"
                    />
                </div>
                <div class="flex items-center gap-2">
                    <label class="text-xs font-semibold text-gray-500">To</label>
                    <input
                        type="date"
                        :value="filters.to ?? ''"
                        @change="applyFilter('to', $event.target.value)"
                        class="text-xs rounded-lg border-gray-200 focus:ring-[#EF233C] focus:border-[#EF233C] py-1.5 [color-scheme:dark]"
                    />
                </div>

                <!-- Clear -->
                <button
                    v-if="hasActiveFilters"
                    @click="clearFilters"
                    class="text-xs text-gray-400 hover:text-gray-700 flex items-center gap-1 ml-auto"
                >
                    <XMarkIcon class="w-3.5 h-3.5" />
                    Clear filters
                </button>
            </div>

            <!-- ── Empty state ─────────────────────────────────────────── -->
            <div v-if="jobs.data.length === 0" class="bg-white rounded-2xl border border-dashed border-gray-300 py-20 text-center">
                <div class="w-16 h-16 rounded-2xl bg-gray-100 flex items-center justify-center mx-auto mb-4">
                    <ClipboardDocumentListIcon class="w-8 h-8 text-gray-400" />
                </div>
                <p class="text-gray-700 font-semibold text-base">No jobs found</p>
                <p class="text-sm text-gray-400 mt-1">
                    {{ activeFilter === 'upcoming' ? 'You have no upcoming jobs.' : 'No jobs match the current filters.' }}
                </p>
                <Link
                    v-if="isManager"
                    :href="route('jobs.index')"
                    class="mt-5 inline-flex items-center gap-2 bg-[#EF233C] hover:bg-[#D90429] text-white text-sm font-medium px-5 py-2.5 rounded-xl transition-colors"
                >
                    <CalendarDaysIcon class="w-4 h-4" />
                    Open Daily Job Board
                </Link>
            </div>

            <!-- ── Jobs list ───────────────────────────────────────────── -->
            <div v-else class="space-y-3">
                <!-- Group by date -->
                <template v-for="(group, date) in groupedJobs" :key="date">
                    <!-- Date heading -->
                    <div class="flex items-center gap-3">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">
                                {{ formatDateHeading(date) }}
                            </span>
                            <span v-if="isToday(date)" class="text-[10px] font-bold bg-[#EF233C] text-white px-1.5 py-0.5 rounded-full uppercase tracking-wide">Today</span>
                            <span v-else-if="isTomorrow(date)" class="text-[10px] font-bold bg-amber-500 text-white px-1.5 py-0.5 rounded-full uppercase tracking-wide">Tomorrow</span>
                        </div>
                        <div class="flex-1 h-px bg-gray-200" />
                        <span class="text-xs text-gray-400">{{ group.length }} job{{ group.length !== 1 ? 's' : '' }}</span>
                    </div>

                    <!-- Job cards for this date -->
                    <div class="space-y-2">
                        <div
                            v-for="job in group"
                            :key="job.id"
                            :class="[
                                'bg-white rounded-2xl border overflow-hidden flex transition-all duration-150',
                                cardBorderClass(job.status),
                            ]"
                        >
                            <!-- Status stripe -->
                            <div :class="['w-1.5 flex-shrink-0', statusStripeClass(job.status)]" />

                            <div class="flex-1 px-4 py-3 flex flex-wrap gap-3 items-center min-w-0">

                                <!-- Left: title + meta -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-1.5 flex-wrap mb-0.5">
                                        <!-- Business / API badges -->
                                        <span v-if="job.project?.business" :class="businessBadge(job.project.business)">
                                            {{ job.project.business.toUpperCase() }}
                                        </span>
                                        <span v-if="job.bcf_order_number" class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-[#EF233C] text-white">BCF</span>
                                        <span v-if="job.bgr_project_name" class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-blue-600 text-white">BGR</span>
                                        <p class="text-sm font-bold text-gray-900">{{ job.title }}</p>
                                    </div>
                                    <div class="flex items-center gap-3 flex-wrap mt-0.5">
                                        <span v-if="job.project" class="text-xs text-gray-500 truncate">
                                            {{ job.project.name }}<span v-if="job.project.customer" class="text-gray-400"> · {{ job.project.customer }}</span>
                                        </span>
                                        <!-- Time -->
                                        <span v-if="job.start_time" class="inline-flex items-center gap-1 text-xs text-gray-400">
                                            <ClockIcon class="w-3 h-3" />
                                            {{ job.start_time.slice(0, 5) }}<span v-if="job.end_time">–{{ job.end_time.slice(0, 5) }}</span>
                                        </span>
                                        <!-- Van -->
                                        <span v-if="job.van" class="inline-flex items-center gap-1 text-xs text-gray-400">
                                            <TruckIcon class="w-3 h-3" />
                                            {{ job.van }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Crew avatars -->
                                <div v-if="job.staff.length > 0" class="flex items-center gap-2 flex-shrink-0">
                                    <div class="flex -space-x-2">
                                        <img
                                            v-for="(member, i) in job.staff.slice(0, 4)"
                                            :key="member.id"
                                            :src="member.avatar_url"
                                            :alt="member.name"
                                            :title="member.name"
                                            :style="{ zIndex: 10 - i }"
                                            class="relative w-7 h-7 rounded-full object-cover border-2 border-white"
                                        />
                                        <div
                                            v-if="job.staff.length > 4"
                                            class="relative w-7 h-7 rounded-full bg-gray-100 border-2 border-white flex items-center justify-center text-[9px] font-bold text-gray-500"
                                        >
                                            +{{ job.staff.length - 4 }}
                                        </div>
                                    </div>
                                    <span class="text-xs text-gray-400">{{ job.staff.length }}</span>
                                </div>

                                <!-- Status badge -->
                                <span :class="statusBadgeClass(job.status)" class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-[11px] font-semibold flex-shrink-0">
                                    <span :class="['w-1.5 h-1.5 rounded-full', statusDotClass(job.status)]" />
                                    {{ statusLabel(job.status) }}
                                </span>

                                <!-- Status action buttons (assigned or manager) -->
                                <div v-if="isManager || isAssigned(job)" class="flex gap-2 flex-shrink-0">
                                    <template v-if="job.status === 'scheduled'">
                                        <button
                                            @click="setStatus(job, 'in_progress')"
                                            :disabled="statusLoading === job.id"
                                            class="status-action text-amber-600 bg-amber-50 hover:bg-amber-100 border-amber-200 disabled:opacity-50"
                                        >
                                            {{ statusLoading === job.id ? '…' : '▶ Start' }}
                                        </button>
                                    </template>
                                    <template v-else-if="job.status === 'in_progress'">
                                        <button
                                            @click="setStatus(job, 'completed')"
                                            :disabled="statusLoading === job.id"
                                            class="status-action text-green-700 bg-green-50 hover:bg-green-100 border-green-200 disabled:opacity-50"
                                        >
                                            {{ statusLoading === job.id ? '…' : '✓ Complete' }}
                                        </button>
                                    </template>
                                    <template v-else-if="job.status === 'cancelled' && isManager">
                                        <button
                                            @click="setStatus(job, 'scheduled')"
                                            :disabled="statusLoading === job.id"
                                            class="status-action text-blue-600 bg-blue-50 hover:bg-blue-100 border-blue-200 disabled:opacity-50"
                                        >
                                            ↩ Restore
                                        </button>
                                    </template>
                                    <template v-else-if="job.status === 'completed' && isManager">
                                        <button
                                            @click="setStatus(job, 'in_progress')"
                                            :disabled="statusLoading === job.id"
                                            class="status-action text-amber-600 bg-amber-50 hover:bg-amber-100 border-amber-200 disabled:opacity-50"
                                        >
                                            ↩ Re-open
                                        </button>
                                    </template>

                                    <!-- Daily Job Board link (jump to that day) - manager only -->
                                    <Link
                                        v-if="isManager"
                                        :href="route('jobs.index', { date: job.date })"
                                        class="status-action text-gray-500 bg-gray-50 hover:bg-gray-100 border-gray-200 inline-flex items-center gap-1 justify-center"
                                        title="View on Daily Job Board"
                                    >
                                        <CalendarDaysIcon class="w-3.5 h-3.5" />
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- ── Pagination ──────────────────────────────────────────── -->
            <div v-if="jobs.meta && jobs.meta.last_page > 1" class="flex items-center justify-center gap-1 pt-2">
                <Link
                    v-for="link in jobs.meta.links"
                    :key="link.label"
                    :href="link.url ?? '#'"
                    :class="[
                        'px-3 py-1.5 rounded-lg text-xs font-medium transition-colors',
                        link.active
                            ? 'bg-[#EF233C] text-white'
                            : link.url
                                ? 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50'
                                : 'bg-white border border-gray-100 text-gray-300 cursor-not-allowed pointer-events-none',
                    ]"
                    preserve-scroll
                    v-html="link.label"
                />
            </div>

        </div>
    </AppLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { router, usePage, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    ClipboardDocumentListIcon,
    CalendarDaysIcon,
    ClockIcon,
    TruckIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    jobs:       { type: Object, required: true },
    isManager:  { type: Boolean, default: false },
    isSiteHead: { type: Boolean, default: false },
    isHR:       { type: Boolean, default: false },
    filters:    { type: Object, default: () => ({}) },
});

const page       = usePage();
const authUserId = computed(() => page.props.auth?.user?.id ?? null);

const pageTitle = computed(() => {
    if (props.isManager) return 'All Jobs';
    if (props.isSiteHead) return 'Site Jobs';
    return 'My Jobs';
});

// ── Period ────────────────────────────────────────────────────────────

const periods = [
    { value: 'upcoming', label: 'Upcoming' },
    { value: 'past',     label: 'Past' },
    { value: 'all',      label: 'All' },
];

const activeFilter = computed(() => props.filters.period ?? 'upcoming');

function setPeriod(value) {
    router.get(route('jobs.list'), {
        ...props.filters,
        period: value,
        from: '',
        to: '',
    }, { preserveScroll: true, replace: true });
}

// ── Filters ───────────────────────────────────────────────────────────

const hasActiveFilters = computed(() =>
    (props.filters.status ?? '') !== '' ||
    (props.filters.from ?? '') !== '' ||
    (props.filters.to ?? '') !== ''
);

function applyFilter(key, value) {
    router.get(route('jobs.list'), {
        ...props.filters,
        period: activeFilter.value,
        [key]: value,
    }, { preserveScroll: true, replace: true });
}

function clearFilters() {
    router.get(route('jobs.list'), {
        period: activeFilter.value,
    }, { preserveScroll: true, replace: true });
}

// ── Group by date ─────────────────────────────────────────────────────

const groupedJobs = computed(() => {
    const groups = {};
    for (const job of props.jobs.data) {
        if (!groups[job.date]) groups[job.date] = [];
        groups[job.date].push(job);
    }
    return groups;
});

// ── Date helpers ──────────────────────────────────────────────────────

function localDateStr(d) {
    const y  = d.getFullYear();
    const m  = String(d.getMonth() + 1).padStart(2, '0');
    const dd = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${dd}`;
}

const todayStr    = localDateStr(new Date());
const tomorrowStr = (() => { const d = new Date(); d.setDate(d.getDate() + 1); return localDateStr(d); })();

function isToday(date)    { return date === todayStr; }
function isTomorrow(date) { return date === tomorrowStr; }

function formatDateHeading(dateStr) {
    const d = new Date(dateStr + 'T00:00:00');
    const opts = { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' };
    return new Intl.DateTimeFormat('en-GB', opts).format(d);
}

// ── Status actions ────────────────────────────────────────────────────

const statusLoading = ref(null);

function isAssigned(job) {
    return authUserId.value && job.staff.some(s => s.id === authUserId.value);
}

function setStatus(job, status) {
    if (statusLoading.value) return;
    statusLoading.value = job.id;
    router.patch(route('jobs.status', job.id), { status }, {
        preserveScroll: true,
        onFinish: () => { statusLoading.value = null; },
    });
}

// ── Style helpers ─────────────────────────────────────────────────────

function statusLabel(s) {
    return { scheduled: 'Scheduled', in_progress: 'In Progress', completed: 'Completed', cancelled: 'Cancelled' }[s] ?? s;
}

function cardBorderClass(s) {
    return {
        scheduled:   'border border-gray-200',
        in_progress: 'border border-amber-300 ring-1 ring-amber-200',
        completed:   'border border-green-200 bg-green-50/30',
        cancelled:   'border border-gray-200 opacity-55',
    }[s] ?? 'border border-gray-200';
}

function statusStripeClass(s) {
    return { scheduled: 'bg-blue-400', in_progress: 'bg-amber-400', completed: 'bg-green-500', cancelled: 'bg-gray-300' }[s] ?? 'bg-gray-200';
}

function statusBadgeClass(s) {
    return {
        scheduled:   'bg-blue-50 text-blue-700 border border-blue-200',
        in_progress: 'bg-amber-50 text-amber-700 border border-amber-200',
        completed:   'bg-green-50 text-green-700 border border-green-200',
        cancelled:   'bg-gray-100 text-gray-500 border border-gray-200',
    }[s] ?? 'bg-gray-100 text-gray-600';
}

function statusDotClass(s) {
    return { scheduled: 'bg-blue-500', in_progress: 'bg-amber-500 animate-pulse', completed: 'bg-green-500', cancelled: 'bg-gray-400' }[s] ?? 'bg-gray-400';
}

function businessBadge(b) {
    return b === 'bgr'
        ? 'text-[10px] font-extrabold px-1.5 py-0.5 rounded bg-blue-100 text-blue-700 tracking-wide'
        : 'text-[10px] font-extrabold px-1.5 py-0.5 rounded bg-[#EF233C]/10 text-[#EF233C] tracking-wide';
}
</script>

<style scoped>
.status-action {
    @apply text-xs border px-2.5 py-1 rounded-lg transition-colors font-semibold whitespace-nowrap;
}
</style>
