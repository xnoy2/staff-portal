<template>
    <AppLayout title="Calendar">

        <!-- ── Header ──────────────────────────────────────────────────── -->
        <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
            <div class="flex items-center gap-2">
                <Link
                    :href="`/calendar?month=${prevMonth}`"
                    class="p-1.5 rounded-md bg-white border border-gray-200 text-gray-500 hover:text-[#2B2D42] hover:shadow-sm transition-all"
                >
                    <ChevronLeftIcon class="w-4 h-4" />
                </Link>
                <h2 class="text-base font-bold text-[#2B2D42] min-w-[140px] text-center">{{ monthName }}</h2>
                <Link
                    :href="`/calendar?month=${nextMonth}`"
                    class="p-1.5 rounded-md bg-white border border-gray-200 text-gray-500 hover:text-[#2B2D42] hover:shadow-sm transition-all"
                >
                    <ChevronRightIcon class="w-4 h-4" />
                </Link>
            </div>

            <div class="flex items-center gap-3">
                <!-- Legend -->
                <div class="hidden sm:flex items-center gap-3">
                    <span class="flex items-center gap-1.5 text-xs text-gray-500">
                        <span class="w-2.5 h-2.5 rounded-sm bg-blue-100 border border-blue-300"></span>Job
                    </span>
                    <span class="flex items-center gap-1.5 text-xs text-gray-500">
                        <span class="w-2.5 h-2.5 rounded-sm bg-amber-100 border border-amber-300"></span>Leave (pending)
                    </span>
                    <span class="flex items-center gap-1.5 text-xs text-gray-500">
                        <span class="w-2.5 h-2.5 rounded-sm bg-emerald-100 border border-emerald-300"></span>Leave (approved)
                    </span>
                </div>

                <Link
                    v-if="month !== todayMonth"
                    href="/calendar"
                    class="text-xs px-3 py-1.5 rounded-md bg-[#EF233C] text-white hover:bg-[#D90429] transition-colors font-medium"
                >
                    Today
                </Link>
            </div>
        </div>

        <!-- ── Day-of-week header ───────────────────────────────────────── -->
        <div class="grid grid-cols-7 mb-1">
            <div
                v-for="d in ['Mon','Tue','Wed','Thu','Fri','Sat','Sun']"
                :key="d"
                class="text-center py-2 text-[10px] font-semibold uppercase tracking-wider text-gray-400"
            >
                {{ d }}
            </div>
        </div>

        <!-- ── Calendar grid ────────────────────────────────────────────── -->
        <div class="grid grid-cols-7 border-l border-t border-gray-200 rounded-xl overflow-hidden bg-white shadow-sm">
            <div
                v-for="cell in calCells"
                :key="cell.dateStr"
                :class="[
                    'border-r border-b border-gray-200 p-1.5 min-h-[100px] flex flex-col transition-colors',
                    !cell.isCurrentMonth ? 'bg-gray-50/60' : '',
                    cell.isWeekend && cell.isCurrentMonth ? 'bg-blue-50/20' : '',
                    cell.hasEvents ? 'cursor-pointer hover:bg-gray-50' : '',
                ]"
                @click="cell.hasEvents && selectDay(cell.dateStr)"
            >
                <!-- Day number -->
                <div class="flex justify-end mb-1">
                    <span
                        :class="[
                            'text-xs font-semibold w-6 h-6 flex items-center justify-center rounded-full',
                            cell.isToday
                                ? 'bg-[#EF233C] text-white'
                                : cell.isCurrentMonth ? 'text-gray-700' : 'text-gray-300',
                        ]"
                    >{{ cell.dayNum }}</span>
                </div>

                <!-- Event chips — max 3, then overflow -->
                <div class="flex flex-col gap-0.5 flex-1">
                    <template v-if="cell.events.length > 0">
                        <div
                            v-for="ev in cell.events.slice(0, MAX_VISIBLE)"
                            :key="`${ev.kind}-${ev.title}-${cell.dateStr}`"
                            :class="['text-[9px] font-semibold px-1.5 py-0.5 rounded truncate leading-snug', eventChipClass(ev)]"
                            :title="ev.title"
                        >
                            <span v-if="ev.kind === 'leave'" class="mr-0.5 opacity-70">✈</span>
                            {{ ev.title }}
                        </div>
                        <button
                            v-if="cell.events.length > MAX_VISIBLE"
                            class="text-[9px] text-gray-400 hover:text-gray-600 text-left px-1 font-medium"
                        >
                            +{{ cell.events.length - MAX_VISIBLE }} more
                        </button>
                    </template>
                </div>
            </div>
        </div>

        <!-- ── Day detail modal ─────────────────────────────────────────── -->
        <Transition name="modal">
            <div v-if="selectedDay" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="selectedDay = null" />

                <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md z-10 overflow-hidden">
                    <!-- Modal header -->
                    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                        <div>
                            <p class="text-[10px] text-gray-400 uppercase tracking-wide font-semibold">{{ selectedDayLabel.weekday }}</p>
                            <h3 class="text-sm font-bold text-[#2B2D42]">{{ selectedDayLabel.date }}</h3>
                        </div>
                        <button @click="selectedDay = null" class="text-gray-400 hover:text-gray-600 transition-colors">
                            <XMarkIcon class="w-5 h-5" />
                        </button>
                    </div>

                    <!-- Event list -->
                    <div class="overflow-y-auto max-h-[60vh] divide-y divide-gray-100">

                        <!-- Jobs section -->
                        <template v-if="selectedJobs.length > 0">
                            <div class="px-5 py-3 bg-gray-50/50">
                                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Jobs ({{ selectedJobs.length }})</p>
                            </div>
                            <div v-for="job in selectedJobs" :key="job.id" class="px-5 py-3 flex items-start gap-3">
                                <span :class="['w-2 h-2 rounded-full mt-1.5 flex-shrink-0', jobDot(job.status)]"></span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-semibold text-gray-800 leading-snug">{{ job.title }}</p>
                                    <div class="flex flex-wrap items-center gap-x-3 gap-y-0.5 mt-0.5">
                                        <span v-if="job.project" class="text-xs text-gray-500">{{ job.project }}</span>
                                        <span v-if="job.start_time" class="text-xs text-gray-400">{{ job.start_time }}</span>
                                        <span v-if="job.staff_count" class="text-xs text-gray-400">{{ job.staff_count }} staff</span>
                                    </div>
                                </div>
                                <span :class="['text-[10px] px-2 py-0.5 rounded-full font-semibold flex-shrink-0', jobBadge(job.status)]">
                                    {{ jobLabel(job.status) }}
                                </span>
                            </div>
                        </template>

                        <!-- Leave section -->
                        <template v-if="selectedLeaves.length > 0">
                            <div class="px-5 py-3 bg-gray-50/50">
                                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Leave ({{ selectedLeaves.length }})</p>
                            </div>
                            <div v-for="(lv, i) in selectedLeaves" :key="i" class="px-5 py-3 flex items-center gap-3">
                                <span :class="['w-2 h-2 rounded-full flex-shrink-0', lv.status === 'approved' ? 'bg-emerald-400' : 'bg-amber-400']"></span>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-semibold text-gray-800">{{ lv.title }}</p>
                                    <p class="text-xs text-gray-500 capitalize">{{ lv.type?.replace('_', ' ') }}</p>
                                </div>
                                <span :class="['text-[10px] px-2 py-0.5 rounded-full font-semibold flex-shrink-0', lv.status === 'approved' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700']">
                                    {{ lv.status === 'approved' ? 'Approved' : 'Pending' }}
                                </span>
                            </div>
                        </template>
                    </div>

                    <!-- Footer: link to live board -->
                    <div class="px-5 py-3 border-t border-gray-100 bg-gray-50/50 flex justify-end">
                        <Link
                            :href="`/jobs?date=${selectedDay}`"
                            class="text-xs text-[#EF233C] hover:underline font-medium"
                        >
                            Open Live Board for this day →
                        </Link>
                    </div>
                </div>
            </div>
        </Transition>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import dayjs from 'dayjs';
import isoWeek from 'dayjs/plugin/isoWeek';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ChevronLeftIcon, ChevronRightIcon, XMarkIcon } from '@heroicons/vue/24/outline';

dayjs.extend(isoWeek);

const MAX_VISIBLE = 3;

const props = defineProps({
    month:     { type: String, required: true },
    monthName: { type: String, required: true },
    prevMonth: { type: String, required: true },
    nextMonth: { type: String, required: true },
    todayDate: { type: String, required: true },
    events:    { type: Object, default: () => ({}) },
});

// ── Grid ──────────────────────────────────────────────────────────────────────

const calCells = computed(() => {
    const monthStart = dayjs(props.month + '-01');
    const gridStart  = monthStart.startOf('isoWeek');           // Monday
    const gridEnd    = monthStart.endOf('month').endOf('isoWeek'); // Sunday

    const cells = [];
    let cur = gridStart;
    while (cur.isBefore(gridEnd) || cur.isSame(gridEnd, 'day')) {
        const dateStr = cur.format('YYYY-MM-DD');
        const evs     = props.events[dateStr] ?? [];
        cells.push({
            dateStr,
            dayNum:         cur.date(),
            isCurrentMonth: cur.month() === monthStart.month(),
            isToday:        dateStr === props.todayDate,
            isWeekend:      cur.isoWeekday() >= 6,
            hasEvents:      evs.length > 0,
            events:         evs,
        });
        cur = cur.add(1, 'day');
    }
    return cells;
});

const todayMonth = computed(() => dayjs(props.todayDate).format('YYYY-MM'));

// ── Day selection ─────────────────────────────────────────────────────────────

const selectedDay = ref(null);

function selectDay(dateStr) {
    selectedDay.value = dateStr;
}

const selectedEvents = computed(() => selectedDay.value ? (props.events[selectedDay.value] ?? []) : []);
const selectedJobs   = computed(() => selectedEvents.value.filter(e => e.kind === 'job'));
const selectedLeaves = computed(() => selectedEvents.value.filter(e => e.kind === 'leave'));

const selectedDayLabel = computed(() => {
    if (!selectedDay.value) return {};
    const d = dayjs(selectedDay.value);
    return {
        weekday: d.format('dddd'),
        date:    d.format('D MMMM YYYY'),
    };
});

// ── Styling helpers ───────────────────────────────────────────────────────────

function eventChipClass(ev) {
    if (ev.kind === 'leave') {
        return ev.status === 'approved'
            ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
            : 'bg-amber-50 text-amber-700 border border-amber-200';
    }
    const map = {
        scheduled:   'bg-blue-50 text-blue-700 border border-blue-200',
        in_progress: 'bg-orange-50 text-orange-700 border border-orange-200',
        completed:   'bg-gray-100 text-gray-500 border border-gray-200',
        cancelled:   'bg-red-50 text-red-400 border border-red-100',
    };
    return map[ev.status] ?? 'bg-gray-100 text-gray-500 border border-gray-200';
}

function jobDot(status) {
    const map = { scheduled: 'bg-blue-400', in_progress: 'bg-orange-400', completed: 'bg-gray-300', cancelled: 'bg-red-300' };
    return map[status] ?? 'bg-gray-300';
}

function jobBadge(status) {
    const map = {
        scheduled:   'bg-blue-50 text-blue-600',
        in_progress: 'bg-orange-50 text-orange-600',
        completed:   'bg-gray-100 text-gray-500',
        cancelled:   'bg-red-50 text-red-400',
    };
    return map[status] ?? 'bg-gray-100 text-gray-500';
}

function jobLabel(status) {
    const map = { scheduled: 'Scheduled', in_progress: 'In Progress', completed: 'Completed', cancelled: 'Cancelled' };
    return map[status] ?? status;
}
</script>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: opacity 0.2s ease; }
.modal-enter-from, .modal-leave-to       { opacity: 0; }
</style>
