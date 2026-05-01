<template>
    <AppLayout title="Calendar">

        <!-- ── Header ──────────────────────────────────────────────────── -->
        <div class="flex flex-wrap items-center justify-between gap-3 mb-5">

            <!-- Month nav -->
            <div class="flex items-center gap-2.5">
                <div class="flex items-center bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                    <Link
                        :href="`/calendar?month=${prevMonth}`"
                        class="px-3 py-2.5 text-gray-400 hover:text-[#2B2D42] hover:bg-gray-50 transition-colors border-r border-gray-200"
                    >
                        <ChevronLeftIcon class="w-4 h-4" />
                    </Link>
                    <span class="px-5 text-sm font-bold text-[#2B2D42] min-w-[148px] text-center tracking-tight">
                        {{ monthName }}
                    </span>
                    <Link
                        :href="`/calendar?month=${nextMonth}`"
                        class="px-3 py-2.5 text-gray-400 hover:text-[#2B2D42] hover:bg-gray-50 transition-colors border-l border-gray-200"
                    >
                        <ChevronRightIcon class="w-4 h-4" />
                    </Link>
                </div>

                <Link
                    v-if="month !== todayMonth"
                    href="/calendar"
                    class="text-xs px-3.5 py-2 rounded-xl bg-[#EF233C] text-white hover:bg-[#D90429] transition-colors font-semibold shadow-sm shadow-red-200"
                >
                    Today
                </Link>
            </div>

            <!-- Legend -->
            <div class="hidden sm:flex items-center divide-x divide-gray-200 bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                <div class="flex items-center gap-2 px-3.5 py-2">
                    <span class="w-3 h-3 rounded-sm bg-blue-400 flex-shrink-0"></span>
                    <span class="text-xs text-gray-600 font-medium">Job</span>
                </div>
                <div class="flex items-center gap-2 px-3.5 py-2">
                    <span class="w-3 h-3 rounded-sm bg-amber-400 flex-shrink-0"></span>
                    <span class="text-xs text-gray-600 font-medium">Leave (pending)</span>
                </div>
                <div class="flex items-center gap-2 px-3.5 py-2">
                    <span class="w-3 h-3 rounded-sm bg-emerald-400 flex-shrink-0"></span>
                    <span class="text-xs text-gray-600 font-medium">Leave (approved)</span>
                </div>
            </div>
        </div>

        <!-- ── Calendar ──────────────────────────────────────────────────── -->
        <div class="overflow-x-auto -mx-4 sm:-mx-6 px-4 sm:px-6">
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden min-w-[680px]">

                <!-- Day-of-week header -->
                <div class="grid grid-cols-7 border-b border-gray-200 bg-gray-50/70">
                    <div
                        v-for="(d, i) in ['Mon','Tue','Wed','Thu','Fri','Sat','Sun']"
                        :key="d"
                        :class="[
                            'py-3 text-center text-[10px] font-bold uppercase tracking-widest',
                            i > 0 ? 'border-l border-gray-200' : '',
                            i >= 5 ? 'text-gray-400' : 'text-gray-500',
                        ]"
                    >
                        {{ d }}
                    </div>
                </div>

                <!-- Grid cells -->
                <div class="grid grid-cols-7">
                    <div
                        v-for="(cell, ci) in calCells"
                        :key="cell.dateStr"
                        :class="[
                            'border-b border-r border-gray-100 min-h-[108px] flex flex-col p-2 relative group transition-colors duration-100',
                            ci % 7 === 0 ? 'border-l-0' : '',
                            !cell.isCurrentMonth ? 'bg-gray-50/60' : 'bg-white',
                            cell.isToday ? '!bg-red-50/40' : '',
                            cell.hasEvents ? 'cursor-pointer hover:bg-blue-50/20' : 'hover:bg-gray-50/50',
                        ]"
                        @click="cell.hasEvents && selectDay(cell.dateStr)"
                    >
                        <!-- Today column highlight stripe -->
                        <div v-if="cell.isToday" class="absolute top-0 left-0 right-0 h-0.5 bg-[#EF233C]"></div>

                        <!-- Day number -->
                        <div class="flex items-center justify-between mb-1.5">
                            <span
                                v-if="cell.isToday"
                                class="w-6 h-6 flex items-center justify-center rounded-full bg-[#EF233C] text-white text-[11px] font-bold shadow-sm"
                            >
                                {{ cell.dayNum }}
                            </span>
                            <span
                                v-else
                                :class="[
                                    'w-6 h-6 flex items-center justify-center rounded-full text-[11px] font-semibold transition-colors',
                                    cell.isCurrentMonth ? 'text-gray-700 group-hover:bg-gray-100' : 'text-gray-300',
                                ]"
                            >
                                {{ cell.dayNum }}
                            </span>

                            <!-- Event count dot (when too many) -->
                            <span
                                v-if="cell.events.length > MAX_VISIBLE"
                                class="text-[9px] text-[#EF233C] font-bold"
                            >
                                +{{ cell.events.length - MAX_VISIBLE }}
                            </span>
                        </div>

                        <!-- Event bars -->
                        <div class="flex flex-col gap-0.5 flex-1">
                            <div
                                v-for="ev in cell.events.slice(0, MAX_VISIBLE)"
                                :key="`${ev.kind}-${ev.title}`"
                                :class="['flex items-center gap-1 px-1.5 py-[3px] rounded-md text-[9px] font-semibold truncate leading-none border-l-[3px]', chipClass(ev)]"
                                :title="ev.title + (ev.project ? ' · ' + ev.project : '')"
                            >
                                <component :is="ev.kind === 'leave' ? 'span' : 'span'" class="flex-shrink-0 opacity-70 text-[8px]">
                                    {{ ev.kind === 'leave' ? '✈' : '●' }}
                                </component>
                                <span class="truncate">{{ ev.title }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Summary strip ─────────────────────────────────────────────── -->
        <div class="mt-3 flex flex-wrap gap-4 text-xs text-gray-400 px-1">
            <span>{{ totalEvents }} event{{ totalEvents !== 1 ? 's' : '' }} in {{ monthName }}</span>
            <span v-if="totalJobs > 0">· {{ totalJobs }} job{{ totalJobs !== 1 ? 's' : '' }}</span>
            <span v-if="totalLeave > 0">· {{ totalLeave }} leave day{{ totalLeave !== 1 ? 's' : '' }}</span>
        </div>

        <!-- ── Day detail modal ─────────────────────────────────────────── -->
        <Transition name="modal">
            <div v-if="selectedDay" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4">
                <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="selectedDay = null" />

                <div class="relative bg-white w-full sm:max-w-md rounded-t-3xl sm:rounded-2xl shadow-2xl z-10 overflow-hidden flex flex-col max-h-[85vh] sm:max-h-[70vh]">

                    <!-- Modal header -->
                    <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100 flex-shrink-0">
                        <div class="w-10 h-10 rounded-xl bg-[#EF233C] flex items-center justify-center flex-shrink-0 shadow-sm shadow-red-200">
                            <span class="text-white font-bold text-sm">{{ selectedDayNum }}</span>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs text-gray-400 font-medium">{{ selectedDayLabel.weekday }}</p>
                            <h3 class="text-sm font-bold text-[#2B2D42] leading-tight">{{ selectedDayLabel.date }}</h3>
                        </div>
                        <button @click="selectedDay = null" class="ml-auto text-gray-400 hover:text-gray-600 transition-colors flex-shrink-0 p-1 rounded-lg hover:bg-gray-100">
                            <XMarkIcon class="w-4 h-4" />
                        </button>
                    </div>

                    <!-- Event list -->
                    <div class="overflow-y-auto flex-1">

                        <!-- Jobs -->
                        <template v-if="selectedJobs.length > 0">
                            <div class="px-5 pt-4 pb-1.5">
                                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-blue-400 inline-block"></span>
                                    Jobs · {{ selectedJobs.length }}
                                </p>
                            </div>
                            <div v-for="job in selectedJobs" :key="job.id" class="mx-3 mb-2 p-3 rounded-xl border border-gray-100 bg-gray-50/50 hover:bg-gray-50 transition-colors">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-semibold text-gray-800 leading-snug truncate">{{ job.title }}</p>
                                        <div class="flex flex-wrap items-center gap-x-2 gap-y-0.5 mt-1">
                                            <span v-if="job.project" class="text-[10px] text-gray-500 font-medium">{{ job.project }}</span>
                                            <span v-if="job.start_time" class="text-[10px] text-gray-400">{{ job.start_time }}</span>
                                            <span v-if="job.staff_count" class="text-[10px] text-gray-400">{{ job.staff_count }} staff</span>
                                        </div>
                                    </div>
                                    <span :class="['text-[9px] px-2 py-0.5 rounded-full font-bold flex-shrink-0 mt-0.5', jobBadge(job.status)]">
                                        {{ jobLabel(job.status) }}
                                    </span>
                                </div>
                            </div>
                        </template>

                        <!-- Leave -->
                        <template v-if="selectedLeaves.length > 0">
                            <div class="px-5 pt-4 pb-1.5">
                                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-emerald-400 inline-block"></span>
                                    Leave · {{ selectedLeaves.length }}
                                </p>
                            </div>
                            <div v-for="(lv, i) in selectedLeaves" :key="i" class="mx-3 mb-2 p-3 rounded-xl border border-gray-100 bg-gray-50/50 hover:bg-gray-50 transition-colors flex items-center gap-3">
                                <div :class="['w-1.5 self-stretch rounded-full flex-shrink-0', lv.status === 'approved' ? 'bg-emerald-400' : 'bg-amber-400']"></div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-semibold text-gray-800">{{ lv.title }}</p>
                                    <p class="text-[10px] text-gray-500 mt-0.5 capitalize">{{ lv.type?.replace('_', ' ') }}</p>
                                </div>
                                <span :class="['text-[9px] px-2 py-0.5 rounded-full font-bold flex-shrink-0', lv.status === 'approved' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700']">
                                    {{ lv.status === 'approved' ? 'Approved' : 'Pending' }}
                                </span>
                            </div>
                        </template>

                        <div class="h-2"></div>
                    </div>

                    <!-- Footer -->
                    <div class="px-5 py-3 border-t border-gray-100 flex-shrink-0 flex items-center justify-between bg-gray-50/50">
                        <span class="text-xs text-gray-400">{{ selectedEvents.length }} event{{ selectedEvents.length !== 1 ? 's' : '' }}</span>
                        <Link
                            :href="`/jobs?date=${selectedDay}`"
                            class="text-xs text-[#EF233C] hover:underline font-semibold flex items-center gap-1"
                        >
                            Open Live Board
                            <ChevronRightIcon class="w-3 h-3" />
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
    const gridStart  = monthStart.startOf('isoWeek');
    const gridEnd    = monthStart.endOf('month').endOf('isoWeek');

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

const allEvents   = computed(() => Object.values(props.events).flat());
const totalEvents = computed(() => allEvents.value.length);
const totalJobs   = computed(() => allEvents.value.filter(e => e.kind === 'job').length);
const totalLeave  = computed(() => allEvents.value.filter(e => e.kind === 'leave').length);

// ── Day selection ─────────────────────────────────────────────────────────────

const selectedDay = ref(null);

const selectedEvents = computed(() => selectedDay.value ? (props.events[selectedDay.value] ?? []) : []);
const selectedJobs   = computed(() => selectedEvents.value.filter(e => e.kind === 'job'));
const selectedLeaves = computed(() => selectedEvents.value.filter(e => e.kind === 'leave'));

const selectedDayLabel = computed(() => {
    if (!selectedDay.value) return {};
    const d = dayjs(selectedDay.value);
    return { weekday: d.format('dddd'), date: d.format('D MMMM YYYY') };
});

const selectedDayNum = computed(() => selectedDay.value ? dayjs(selectedDay.value).date() : '');

function selectDay(dateStr) { selectedDay.value = dateStr; }

// ── Styling helpers ───────────────────────────────────────────────────────────

function chipClass(ev) {
    if (ev.kind === 'leave') {
        return ev.status === 'approved'
            ? 'bg-emerald-50 text-emerald-700 border-emerald-400'
            : 'bg-amber-50 text-amber-700 border-amber-400';
    }
    const map = {
        scheduled:   'bg-blue-50 text-blue-700 border-blue-400',
        in_progress: 'bg-orange-50 text-orange-700 border-orange-400',
        completed:   'bg-gray-100 text-gray-500 border-gray-300',
        cancelled:   'bg-red-50 text-red-400 border-red-300',
    };
    return map[ev.status] ?? 'bg-gray-100 text-gray-500 border-gray-300';
}

function jobBadge(status) {
    const map = {
        scheduled:   'bg-blue-100 text-blue-700',
        in_progress: 'bg-orange-100 text-orange-700',
        completed:   'bg-gray-100 text-gray-500',
        cancelled:   'bg-red-100 text-red-500',
    };
    return map[status] ?? 'bg-gray-100 text-gray-500';
}

function jobLabel(status) {
    const map = { scheduled: 'Scheduled', in_progress: 'In Progress', completed: 'Done', cancelled: 'Cancelled' };
    return map[status] ?? status;
}
</script>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: opacity 0.2s ease; }
.modal-enter-from, .modal-leave-to       { opacity: 0; }
</style>
