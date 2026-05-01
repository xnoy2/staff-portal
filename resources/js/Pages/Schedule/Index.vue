<template>
    <AppLayout title="Schedule">

        <!-- Week navigation bar -->
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <div class="flex items-center gap-2">
                <Link
                    :href="`/schedule?week=${prevWeek}`"
                    class="p-1.5 rounded-md text-gray-500 hover:text-[#2B2D42] bg-white border border-gray-200 hover:shadow-sm transition-all"
                >
                    <ChevronLeftIcon class="w-4 h-4" />
                </Link>

                <div class="text-center min-w-[160px]">
                    <p class="text-sm font-semibold text-[#2B2D42]">{{ weekLabel }}</p>
                    <p class="text-xs text-gray-400">{{ totalJobs }} job{{ totalJobs !== 1 ? 's' : '' }} this week</p>
                </div>

                <Link
                    :href="`/schedule?week=${nextWeek}`"
                    class="p-1.5 rounded-md text-gray-500 hover:text-[#2B2D42] bg-white border border-gray-200 hover:shadow-sm transition-all"
                >
                    <ChevronRightIcon class="w-4 h-4" />
                </Link>
            </div>

            <Link
                v-if="!isCurrentWeek"
                href="/schedule"
                class="text-xs px-3 py-1.5 rounded-md bg-[#EF233C] text-white hover:bg-[#D90429] transition-colors font-medium"
            >
                This Week
            </Link>
        </div>

        <!-- ── Job Calendar ─────────────────────────────────────────────── -->
        <div class="overflow-x-auto -mx-4 sm:-mx-6 px-4 sm:px-6 pb-1">
            <div class="grid grid-cols-7 gap-2 min-w-[700px]">
                <div v-for="day in weekDays" :key="day.date" class="flex flex-col gap-2">
                    <!-- Day header -->
                    <Link
                        :href="`/jobs?date=${day.date}`"
                        :class="[
                            'flex flex-col items-center py-2.5 px-1 rounded-xl transition-all',
                            day.isToday
                                ? 'bg-[#EF233C] text-white shadow-md shadow-red-200'
                                : 'bg-white border border-gray-200 text-gray-600 hover:border-gray-300 hover:shadow-sm',
                        ]"
                    >
                        <span class="text-[10px] font-semibold uppercase tracking-wide">{{ day.label }}</span>
                        <span :class="['text-xl font-bold leading-none mt-0.5', day.isToday ? 'text-white' : 'text-[#2B2D42]']">{{ day.dayNum }}</span>
                        <span :class="['text-[9px] mt-0.5', day.isToday ? 'text-white/70' : 'text-gray-400']">{{ day.month }}</span>
                        <span
                            v-if="day.jobs.length > 0"
                            :class="['mt-1.5 text-[9px] font-bold px-1.5 py-0.5 rounded-full', day.isToday ? 'bg-white/20 text-white' : 'bg-[#EF233C]/10 text-[#EF233C]']"
                        >{{ day.jobs.length }}</span>
                    </Link>

                    <!-- Job cards -->
                    <div class="flex flex-col gap-1.5 flex-1">
                        <Link
                            v-for="job in day.jobs"
                            :key="job.id"
                            :href="`/jobs?date=${day.date}`"
                            :class="['block bg-white rounded-lg border-l-2 border border-gray-100 p-2 hover:shadow-sm hover:border-gray-200 transition-all text-left', statusBorderColor(job.status)]"
                        >
                            <div class="flex items-start justify-between gap-1 mb-1">
                                <p class="text-[11px] font-semibold text-gray-800 leading-snug line-clamp-2 flex-1">{{ job.title }}</p>
                                <span :class="['text-[9px] px-1.5 py-0.5 rounded-full font-semibold whitespace-nowrap flex-shrink-0 mt-0.5', statusBadge(job.status)]">{{ statusLabel(job.status) }}</span>
                            </div>
                            <p v-if="job.start_time" class="text-[10px] text-gray-400 mb-1 font-medium">
                                <ClockIcon class="w-2.5 h-2.5 inline-block -mt-0.5 mr-0.5" />
                                {{ job.start_time }}{{ job.end_time ? ` – ${job.end_time}` : '' }}
                            </p>
                            <p v-if="job.project" class="text-[10px] text-gray-500 truncate mb-1">{{ job.project.name }}</p>
                            <div class="flex items-center gap-1 mt-1">
                                <div class="flex -space-x-1.5">
                                    <img v-for="m in job.staff.slice(0, 3)" :key="m.id" :src="m.avatar_url" :alt="m.name" :title="m.name" class="w-4 h-4 rounded-full border border-white object-cover" />
                                </div>
                                <span v-if="job.staff_count > 3" class="text-[9px] text-gray-400">+{{ job.staff_count - 3 }}</span>
                                <template v-if="job.van">
                                    <span class="text-[9px] text-gray-300 mx-0.5">·</span>
                                    <TruckIcon class="w-2.5 h-2.5 text-gray-400 flex-shrink-0" />
                                    <span class="text-[9px] text-gray-400 truncate">{{ job.van }}</span>
                                </template>
                            </div>
                        </Link>

                        <div v-if="day.jobs.length === 0" class="flex-1 min-h-[80px] flex items-center justify-center rounded-lg border border-dashed border-gray-200">
                            <span class="text-[10px] text-gray-300">No jobs</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Legend -->
        <div class="flex flex-wrap items-center gap-4 mt-5 pt-4 border-t border-gray-200">
            <span class="text-xs text-gray-400 font-medium">Status:</span>
            <div v-for="s in statusLegend" :key="s.value" class="flex items-center gap-1.5">
                <span :class="['w-2.5 h-2.5 rounded-full', s.dot]"></span>
                <span class="text-xs text-gray-500">{{ s.label }}</span>
            </div>
            <span class="text-xs text-gray-300 ml-auto hidden sm:inline">Click any day header to open Live Board</span>
        </div>

        <!-- ── Staff Roster ─────────────────────────────────────────────── -->
        <div class="mt-8">
            <div class="flex items-center justify-between mb-3">
                <div>
                    <h2 class="text-sm font-semibold text-[#2B2D42]">Staff Roster</h2>
                    <p class="text-xs text-gray-400 mt-0.5">
                        {{ canEdit ? 'Click any day cell to schedule a staff member.' : 'Scheduled working days for this week.' }}
                    </p>
                </div>
                <div class="flex items-center gap-2 text-xs text-gray-500">
                    <span class="inline-flex items-center gap-1">
                        <span class="w-2.5 h-2.5 rounded-sm bg-emerald-100 border border-emerald-300"></span>
                        Scheduled
                    </span>
                    <span class="inline-flex items-center gap-1">
                        <span class="w-2.5 h-2.5 rounded-sm bg-gray-100 border border-gray-200"></span>
                        Off
                    </span>
                </div>
            </div>

            <div class="overflow-x-auto -mx-4 sm:-mx-6 px-4 sm:px-6">
                <div class="bg-white rounded-xl border border-gray-200 min-w-[760px] overflow-hidden">

                    <!-- Header row -->
                    <div class="grid border-b border-gray-100 bg-gray-50/80" style="grid-template-columns: 192px repeat(7, 1fr)">
                        <div class="px-4 py-3">
                            <span class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider">Staff Member</span>
                        </div>
                        <div
                            v-for="day in weekDays"
                            :key="day.date"
                            :class="['px-2 py-3 text-center border-l border-gray-100', day.isToday ? 'bg-[#EF233C]/5' : '']"
                        >
                            <span :class="['block text-[10px] font-semibold uppercase tracking-wide', day.isToday ? 'text-[#EF233C]' : 'text-gray-400']">{{ day.label }}</span>
                            <span :class="['block text-sm font-bold leading-tight', day.isToday ? 'text-[#EF233C]' : 'text-[#2B2D42]']">{{ day.dayNum }}</span>
                            <span :class="['block text-[9px]', day.isToday ? 'text-[#EF233C]/60' : 'text-gray-300']">{{ day.month }}</span>
                        </div>
                    </div>

                    <!-- Staff rows -->
                    <div
                        v-for="(member, idx) in staffSchedule"
                        :key="member.id"
                        :class="['grid', idx < staffSchedule.length - 1 ? 'border-b border-gray-100' : '']"
                        style="grid-template-columns: 192px repeat(7, 1fr)"
                    >
                        <!-- Staff identity -->
                        <div class="px-4 py-3 flex items-center gap-2.5 border-r border-gray-100">
                            <img :src="member.avatar_url" :alt="member.name" class="w-8 h-8 rounded-full object-cover flex-shrink-0 border border-gray-100" />
                            <div class="min-w-0">
                                <p class="text-xs font-semibold text-gray-800 truncate leading-snug">{{ member.name }}</p>
                                <p class="text-[10px] text-gray-400 mt-0.5">
                                    <span class="text-emerald-600 font-medium">{{ member.days_scheduled }}d</span>
                                    <span class="mx-1 text-gray-200">·</span>
                                    {{ member.total_jobs }} job{{ member.total_jobs !== 1 ? 's' : '' }}
                                </p>
                            </div>
                        </div>

                        <!-- Day cells -->
                        <div
                            v-for="day in member.days"
                            :key="day.date"
                            :class="[
                                'border-l border-gray-100 p-1.5 relative group',
                                isToday(day.date) ? 'bg-[#EF233C]/[0.025]' : '',
                                canEdit ? 'cursor-pointer' : '',
                            ]"
                            @click="canEdit && openModal(member, day)"
                        >
                            <!-- Scheduled indicator -->
                            <div v-if="day.scheduled" class="mb-1">
                                <div class="flex items-center gap-1 bg-emerald-50 border border-emerald-200 rounded px-1.5 py-1">
                                    <CheckIcon class="w-2.5 h-2.5 text-emerald-500 flex-shrink-0" />
                                    <span class="text-[9px] font-semibold text-emerald-700 leading-none">
                                        {{ day.shift_start && day.shift_end ? `${day.shift_start} – ${day.shift_end}` : day.shift_start ? `from ${day.shift_start}` : 'Working' }}
                                    </span>
                                </div>
                                <p v-if="day.notes" class="text-[9px] text-gray-400 truncate mt-0.5 px-0.5" :title="day.notes">{{ day.notes }}</p>
                            </div>

                            <!-- Job pills -->
                            <div v-if="day.jobs.length > 0" class="flex flex-col gap-0.5">
                                <div
                                    v-for="job in day.jobs"
                                    :key="job.id"
                                    :class="['text-[9px] px-1.5 py-0.5 rounded font-semibold truncate', statusBadge(job.status)]"
                                    :title="`${job.title}${job.project_name ? ' · ' + job.project_name : ''}${job.start_time ? ' · ' + job.start_time : ''}`"
                                >
                                    {{ job.title }}
                                </div>
                            </div>

                            <!-- Empty: show + hint on hover (managers only) -->
                            <div v-if="!day.scheduled && day.jobs.length === 0" class="min-h-[28px] flex items-center justify-center">
                                <PlusIcon v-if="canEdit" class="w-3.5 h-3.5 text-gray-200 group-hover:text-gray-400 transition-colors" />
                                <span v-else class="text-gray-200 text-xs select-none">—</span>
                            </div>

                            <!-- Edit hint on hover when already scheduled -->
                            <div v-if="day.scheduled && canEdit" class="absolute inset-0 bg-white/60 opacity-0 group-hover:opacity-100 transition-opacity rounded flex items-center justify-center pointer-events-none">
                                <PencilIcon class="w-3 h-3 text-gray-400" />
                            </div>
                        </div>
                    </div>

                    <!-- Empty state -->
                    <div v-if="staffSchedule.length === 0" class="py-12 text-center">
                        <UsersIcon class="w-8 h-8 text-gray-200 mx-auto mb-2" />
                        <p class="text-sm text-gray-400">No active staff found.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Schedule Modal ───────────────────────────────────────────── -->
        <Transition name="modal">
            <div v-if="modal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="modal = null">
                <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="modal = null" />

                <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6 z-10">
                    <!-- Header -->
                    <div class="flex items-start gap-3 mb-5">
                        <img :src="modal.avatarUrl" :alt="modal.staffName" class="w-10 h-10 rounded-full object-cover border border-gray-100 flex-shrink-0" />
                        <div class="min-w-0">
                            <h3 class="text-sm font-bold text-[#2B2D42]">{{ modal.staffName }}</h3>
                            <p class="text-xs text-gray-500 mt-0.5">{{ modal.dateLabel }}</p>
                        </div>
                        <button @click="modal = null" class="ml-auto text-gray-400 hover:text-gray-600 transition-colors flex-shrink-0">
                            <XMarkIcon class="w-5 h-5" />
                        </button>
                    </div>

                    <!-- Form -->
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Shift Start</label>
                                <input
                                    v-model="modal.shiftStart"
                                    type="time"
                                    class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#EF233C]/30 focus:border-[#EF233C]"
                                    placeholder="08:00"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Shift End</label>
                                <input
                                    v-model="modal.shiftEnd"
                                    type="time"
                                    class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#EF233C]/30 focus:border-[#EF233C]"
                                    placeholder="17:00"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Notes <span class="text-gray-400 font-normal">(optional)</span></label>
                            <input
                                v-model="modal.notes"
                                type="text"
                                maxlength="500"
                                class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#EF233C]/30 focus:border-[#EF233C]"
                                placeholder="e.g. Half day, On call…"
                            />
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-2 mt-6">
                        <button
                            v-if="modal.scheduleId"
                            @click="removeSchedule"
                            :disabled="modalLoading"
                            class="flex items-center gap-1.5 text-xs px-3 py-2 rounded-lg border border-red-200 text-red-500 hover:bg-red-50 transition-colors disabled:opacity-50"
                        >
                            <TrashIcon class="w-3.5 h-3.5" />
                            Remove
                        </button>
                        <div class="flex-1" />
                        <button
                            @click="modal = null"
                            class="text-xs px-4 py-2 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 transition-colors"
                        >
                            Cancel
                        </button>
                        <button
                            @click="saveSchedule"
                            :disabled="modalLoading"
                            class="text-xs px-4 py-2 rounded-lg bg-[#EF233C] text-white hover:bg-[#D90429] transition-colors font-medium disabled:opacity-60"
                        >
                            {{ modalLoading ? 'Saving…' : 'Save' }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ChevronLeftIcon, ChevronRightIcon } from '@heroicons/vue/20/solid';
import { ClockIcon, TruckIcon, UsersIcon, CheckIcon, PlusIcon, PencilIcon, TrashIcon, XMarkIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    weekDays:      { type: Array,   default: () => [] },
    weekStart:     { type: String,  required: true },
    weekEnd:       { type: String,  required: true },
    prevWeek:      { type: String,  required: true },
    nextWeek:      { type: String,  required: true },
    todayDate:     { type: String,  required: true },
    isPrivileged:  { type: Boolean, default: false },
    canEdit:       { type: Boolean, default: false },
    staffSchedule: { type: Array,   default: () => [] },
});

// ── Modal state ───────────────────────────────────────────────────────────────

const modal       = ref(null);
const modalLoading = ref(false);

function openModal(member, day) {
    modal.value = {
        staffId:     member.id,
        staffName:   member.name,
        avatarUrl:   member.avatar_url,
        date:        day.date,
        dateLabel:   formatDateLabel(day.date),
        scheduleId:  day.schedule_id ?? null,
        shiftStart:  day.shift_start ?? '',
        shiftEnd:    day.shift_end   ?? '',
        notes:       day.notes       ?? '',
    };
}

function saveSchedule() {
    modalLoading.value = true;
    router.post('/schedule/staff', {
        user_id:     modal.value.staffId,
        date:        modal.value.date,
        shift_start: modal.value.shiftStart || null,
        shift_end:   modal.value.shiftEnd   || null,
        notes:       modal.value.notes      || null,
    }, {
        preserveScroll: true,
        onSuccess: () => { modal.value = null; },
        onFinish:  () => { modalLoading.value = false; },
    });
}

function removeSchedule() {
    if (!modal.value.scheduleId) return;
    modalLoading.value = true;
    router.delete(`/schedule/staff/${modal.value.scheduleId}`, {
        preserveScroll: true,
        onSuccess: () => { modal.value = null; },
        onFinish:  () => { modalLoading.value = false; },
    });
}

// ── Computed ──────────────────────────────────────────────────────────────────

const isCurrentWeek = computed(() => props.weekStart <= props.todayDate && props.todayDate <= props.weekEnd);
const totalJobs     = computed(() => props.weekDays.reduce((sum, d) => sum + d.jobs.length, 0));

const weekLabel = computed(() => {
    const start = new Date(props.weekStart + 'T00:00:00');
    const end   = new Date(props.weekEnd   + 'T00:00:00');
    const opts  = { day: 'numeric', month: 'short' };
    if (start.getFullYear() !== end.getFullYear()) {
        return `${start.toLocaleDateString('en-GB', { ...opts, year: 'numeric' })} – ${end.toLocaleDateString('en-GB', { ...opts, year: 'numeric' })}`;
    }
    if (start.getMonth() !== end.getMonth()) {
        return `${start.toLocaleDateString('en-GB', opts)} – ${end.toLocaleDateString('en-GB', { ...opts, year: 'numeric' })}`;
    }
    return `${start.toLocaleDateString('en-GB', opts)} – ${end.toLocaleDateString('en-GB', { ...opts, year: 'numeric' })}`;
});

// ── Helpers ───────────────────────────────────────────────────────────────────

function isToday(dateStr) {
    return dateStr === props.todayDate;
}

function formatDateLabel(dateStr) {
    return new Date(dateStr + 'T00:00:00').toLocaleDateString('en-GB', {
        weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
    });
}

function statusBorderColor(status) {
    const map = { scheduled: 'border-l-blue-400', in_progress: 'border-l-amber-400', completed: 'border-l-emerald-400', cancelled: 'border-l-gray-300' };
    return map[status] ?? 'border-l-gray-300';
}

function statusBadge(status) {
    const map = { scheduled: 'bg-blue-50 text-blue-600', in_progress: 'bg-amber-50 text-amber-600', completed: 'bg-emerald-50 text-emerald-700', cancelled: 'bg-gray-100 text-gray-500' };
    return map[status] ?? 'bg-gray-100 text-gray-500';
}

function statusLabel(status) {
    const map = { scheduled: 'Sched', in_progress: 'Active', completed: 'Done', cancelled: 'Cancel' };
    return map[status] ?? status;
}

const statusLegend = [
    { value: 'scheduled',   label: 'Scheduled',  dot: 'bg-blue-400'    },
    { value: 'in_progress', label: 'In Progress', dot: 'bg-amber-400'   },
    { value: 'completed',   label: 'Completed',   dot: 'bg-emerald-400' },
    { value: 'cancelled',   label: 'Cancelled',   dot: 'bg-gray-300'    },
];
</script>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: opacity 0.2s ease; }
.modal-enter-from, .modal-leave-to       { opacity: 0; }
</style>
