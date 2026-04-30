<template>
    <AppLayout title="Live Board">
        <div class="max-w-7xl mx-auto space-y-4">

            <!-- ── Date navigation ─────────────────────────────────── -->
            <div class="bg-[#2B2D42] rounded-2xl px-4 sm:px-5 py-3 sm:py-4">
                <!-- Row 1: prev | date | next | Add Job (mobile) / all controls (desktop) -->
                <div class="flex items-center gap-2 sm:gap-3">
                    <!-- Prev -->
                    <button
                        @click="navigate(-1)"
                        class="flex-shrink-0 w-9 h-9 flex items-center justify-center rounded-xl text-white/50 hover:text-white hover:bg-white/10 transition-colors"
                    >
                        <ChevronLeftIcon class="w-5 h-5" />
                    </button>

                    <!-- Date block -->
                    <div class="flex-1 text-center select-none min-w-0">
                        <div class="flex items-center justify-center gap-2">
                            <p class="text-white text-base sm:text-lg font-bold tracking-tight truncate">{{ dayLabel }}</p>
                            <span v-if="isToday" class="text-[10px] font-bold bg-[#EF233C] text-white px-1.5 py-0.5 rounded-full uppercase tracking-wide shrink-0">Today</span>
                            <span v-else-if="isTomorrow" class="text-[10px] font-bold bg-white/20 text-white/80 px-1.5 py-0.5 rounded-full uppercase tracking-wide shrink-0">Tomorrow</span>
                            <span v-else-if="isYesterday" class="text-[10px] font-bold bg-white/20 text-white/80 px-1.5 py-0.5 rounded-full uppercase tracking-wide shrink-0">Yesterday</span>
                        </div>
                        <p class="text-white/50 text-xs mt-0.5">{{ shortDate }}</p>
                    </div>

                    <!-- Next -->
                    <button
                        @click="navigate(1)"
                        class="flex-shrink-0 w-9 h-9 flex items-center justify-center rounded-xl text-white/50 hover:text-white hover:bg-white/10 transition-colors"
                    >
                        <ChevronRightIcon class="w-5 h-5" />
                    </button>

                    <!-- Desktop-only controls -->
                    <div class="hidden sm:flex items-center gap-2 ml-2 pl-3 border-l border-white/10">
                        <button
                            v-if="!isToday"
                            @click="goToToday"
                            class="text-xs text-white/70 hover:text-white border border-white/20 hover:border-white/40 hover:bg-white/10 px-3 py-1.5 rounded-lg transition-colors"
                        >
                            Today
                        </button>
                        <input
                            type="date"
                            :value="date"
                            @change="goToDate($event.target.value)"
                            class="text-xs bg-white/10 text-white/70 border border-white/20 rounded-lg px-2 py-1.5 focus:outline-none focus:border-white/40 [color-scheme:dark] cursor-pointer"
                        />
                        <button
                            v-if="isPrivileged"
                            @click="openCreate"
                            class="bg-[#EF233C] hover:bg-[#D90429] text-white text-xs font-semibold px-3 py-1.5 rounded-lg transition-colors flex items-center gap-1.5 whitespace-nowrap"
                        >
                            <PlusIcon class="w-3.5 h-3.5" /> Add Job
                        </button>
                    </div>

                    <!-- Mobile-only Add Job -->
                    <button
                        v-if="isPrivileged"
                        @click="openCreate"
                        class="sm:hidden flex-shrink-0 bg-[#EF233C] hover:bg-[#D90429] text-white text-xs font-semibold px-3 py-2 rounded-lg transition-colors flex items-center gap-1"
                    >
                        <PlusIcon class="w-3.5 h-3.5" />
                        <span>Add</span>
                    </button>
                </div>

                <!-- Row 2 (mobile only): date picker + Today -->
                <div class="flex items-center gap-2 mt-2.5 sm:hidden">
                    <button
                        v-if="!isToday"
                        @click="goToToday"
                        class="text-xs text-white/70 hover:text-white border border-white/20 hover:border-white/40 hover:bg-white/10 px-3 py-1.5 rounded-lg transition-colors whitespace-nowrap"
                    >
                        Today
                    </button>
                    <input
                        type="date"
                        :value="date"
                        @change="goToDate($event.target.value)"
                        class="flex-1 text-xs bg-white/10 text-white/70 border border-white/20 rounded-lg px-2 py-1.5 focus:outline-none focus:border-white/40 [color-scheme:dark] cursor-pointer"
                    />
                </div>
            </div>

            <!-- ── Summary bar ──────────────────────────────────────── -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div
                    v-for="stat in summaryStats"
                    :key="stat.label"
                    class="bg-white rounded-xl border px-4 py-3 flex items-center gap-3"
                    :class="stat.borderClass"
                >
                    <div :class="['w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0', stat.iconBg]">
                        <component :is="stat.icon" :class="['w-4 h-4', stat.iconColor]" />
                    </div>
                    <div>
                        <p class="text-xl font-bold text-gray-800 leading-none">{{ stat.value }}</p>
                        <p class="text-xs text-gray-500 mt-0.5">{{ stat.label }}</p>
                    </div>
                </div>
            </div>

            <!-- ── Empty state ─────────────────────────────────────── -->
            <div v-if="jobs.length === 0" class="bg-white rounded-2xl border border-dashed border-gray-300 py-20 text-center">
                <div class="w-16 h-16 rounded-2xl bg-gray-100 flex items-center justify-center mx-auto mb-4">
                    <ClipboardDocumentListIcon class="w-8 h-8 text-gray-400" />
                </div>
                <p class="text-gray-700 font-semibold text-base">No jobs on the board</p>
                <p class="text-sm text-gray-400 mt-1">
                    {{ isToday ? 'Nothing scheduled for today.' : `Nothing scheduled for ${shortDate}.` }}
                </p>
                <button
                    v-if="isPrivileged"
                    @click="openCreate"
                    class="mt-5 inline-flex items-center gap-2 bg-[#EF233C] hover:bg-[#D90429] text-white text-sm font-medium px-5 py-2.5 rounded-xl transition-colors"
                >
                    <PlusIcon class="w-4 h-4" /> Schedule First Job
                </button>
            </div>

            <!-- ── Job cards grid ──────────────────────────────────── -->
            <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                <div
                    v-for="job in jobs"
                    :key="job.id"
                    :class="[
                        'bg-white rounded-2xl border overflow-hidden flex flex-col transition-all duration-200',
                        cardClass(job.status),
                    ]"
                >
                    <!-- Coloured left border via inner wrapper -->
                    <div class="flex flex-1">
                        <!-- Status stripe -->
                        <div :class="['w-1.5 flex-shrink-0', statusStripeClass(job.status)]" />

                        <div class="flex-1 p-4 flex flex-col gap-3 min-w-0">

                            <!-- Header: title + badge -->
                            <div class="flex items-start gap-2">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-1.5 flex-wrap mb-0.5">
                                        <span v-if="job.project?.business" :class="businessBadge(job.project.business)">
                                            {{ job.project.business.toUpperCase() }}
                                        </span>
                                        <h3 class="text-sm font-bold text-gray-900 leading-snug">{{ job.title }}</h3>
                                    </div>
                                    <p v-if="job.project" class="text-xs text-gray-500 truncate">
                                        {{ job.project.name }}<span v-if="job.project.customer" class="text-gray-400"> · {{ job.project.customer }}</span>
                                    </p>
                                    <p v-if="!job.project && job.description" class="text-xs text-gray-400 line-clamp-1">{{ job.description }}</p>
                                </div>
                                <span :class="statusBadgeClass(job.status)" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold whitespace-nowrap flex-shrink-0">
                                    <span :class="['w-1.5 h-1.5 rounded-full', statusDotClass(job.status)]" />
                                    {{ statusLabel(job.status) }}
                                </span>
                            </div>

                            <!-- Meta chips: time + van -->
                            <div class="flex items-center gap-2 flex-wrap">
                                <div v-if="job.start_time" class="inline-flex items-center gap-1 bg-gray-100 text-gray-600 text-xs px-2 py-1 rounded-lg">
                                    <ClockIcon class="w-3 h-3 text-gray-400" />
                                    {{ job.start_time.slice(0,5) }}<span v-if="job.end_time" class="text-gray-400 mx-0.5">–</span>{{ job.end_time?.slice(0,5) }}
                                </div>
                                <div v-if="job.van" class="inline-flex items-center gap-1 bg-gray-100 text-gray-600 text-xs px-2 py-1 rounded-lg">
                                    <TruckIcon class="w-3 h-3 text-gray-400" />
                                    {{ job.van.registration }}
                                </div>
                                <div v-if="!job.start_time && !job.van" class="text-xs text-gray-300 italic">No time or van set</div>
                            </div>

                            <!-- Crew row -->
                            <div class="border-t border-gray-100 pt-3 flex items-center justify-between gap-2">
                                <div v-if="job.staff.length === 0" class="text-xs text-gray-400 italic">No crew assigned</div>
                                <div v-else class="flex items-center gap-2">
                                    <!-- Stacked avatars -->
                                    <div class="flex -space-x-2">
                                        <div
                                            v-for="(member, i) in job.staff.slice(0, 5)"
                                            :key="member.id"
                                            class="relative"
                                            :style="{ zIndex: 10 - i }"
                                            :title="`${member.name} — ${member.clocked_in ? 'Clocked in' : 'Not clocked in'}${member.hours_today ? ' · ' + member.hours_today + 'h' : ''}`"
                                        >
                                            <img
                                                :src="member.avatar_url"
                                                :alt="member.name"
                                                class="w-8 h-8 rounded-full object-cover border-2 border-white"
                                            />
                                            <span
                                                :class="[
                                                    'absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 rounded-full border-2 border-white',
                                                    member.clocked_in ? 'bg-green-500' : 'bg-gray-300',
                                                ]"
                                            />
                                        </div>
                                        <div
                                            v-if="job.staff.length > 5"
                                            class="w-8 h-8 rounded-full bg-gray-100 border-2 border-white flex items-center justify-center text-[10px] font-bold text-gray-500"
                                        >
                                            +{{ job.staff.length - 5 }}
                                        </div>
                                    </div>
                                    <!-- Clocked-in count -->
                                    <div class="text-xs">
                                        <span class="font-semibold text-gray-700">{{ job.staff.filter(s => s.clocked_in).length }}</span>
                                        <span class="text-gray-400">/{{ job.staff.length }} in</span>
                                    </div>
                                </div>

                                <!-- Manager actions -->
                                <div v-if="isPrivileged" class="flex items-center gap-1 ml-auto">
                                    <button
                                        @click="openEdit(job)"
                                        class="w-7 h-7 flex items-center justify-center rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors"
                                        title="Edit"
                                    >
                                        <PencilIcon class="w-3.5 h-3.5" />
                                    </button>
                                    <button
                                        v-if="canDelete"
                                        @click="confirmDelete(job)"
                                        class="w-7 h-7 flex items-center justify-center rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-50 transition-colors"
                                        title="Remove"
                                    >
                                        <TrashIcon class="w-3.5 h-3.5" />
                                    </button>
                                </div>
                            </div>

                            <!-- Status actions (managers) -->
                            <div v-if="isPrivileged" class="flex gap-2">
                                <template v-if="job.status === 'scheduled'">
                                    <button @click="setStatus(job, 'in_progress')" :disabled="statusLoading === job.id" class="status-action text-amber-600 bg-amber-50 hover:bg-amber-100 border-amber-200 disabled:opacity-50 disabled:cursor-not-allowed">
                                        {{ statusLoading === job.id ? '…' : '▶ Start Job' }}
                                    </button>
                                    <button @click="setStatus(job, 'cancelled')" :disabled="statusLoading === job.id" class="status-action text-gray-500 bg-gray-50 hover:bg-gray-100 border-gray-200 disabled:opacity-50 disabled:cursor-not-allowed">
                                        ✕ Cancel
                                    </button>
                                </template>
                                <template v-else-if="job.status === 'in_progress'">
                                    <button @click="setStatus(job, 'completed')" :disabled="statusLoading === job.id" class="status-action text-green-700 bg-green-50 hover:bg-green-100 border-green-200 disabled:opacity-50 disabled:cursor-not-allowed">
                                        {{ statusLoading === job.id ? '…' : '✓ Mark Complete' }}
                                    </button>
                                </template>
                                <template v-else-if="job.status === 'cancelled'">
                                    <button @click="setStatus(job, 'scheduled')" :disabled="statusLoading === job.id" class="status-action text-blue-600 bg-blue-50 hover:bg-blue-100 border-blue-200 disabled:opacity-50 disabled:cursor-not-allowed">
                                        ↩ Restore
                                    </button>
                                </template>
                                <template v-else-if="job.status === 'completed'">
                                    <button @click="setStatus(job, 'in_progress')" :disabled="statusLoading === job.id" class="status-action text-amber-600 bg-amber-50 hover:bg-amber-100 border-amber-200 disabled:opacity-50 disabled:cursor-not-allowed">
                                        ↩ Re-open
                                    </button>
                                </template>
                            </div>

                            <!-- Notes -->
                            <p v-if="job.notes" class="text-xs text-gray-400 italic border-t border-gray-100 pt-2 line-clamp-2">
                                {{ job.notes }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Create / Edit Modal ─────────────────────────────────── -->
        <Transition name="modal">
            <div v-if="modal.show" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="modal.show = false" />
                <div class="relative bg-white w-full max-w-md rounded-2xl shadow-2xl flex flex-col" style="max-height: min(680px, 90vh)">

                    <!-- Header -->
                    <div class="flex items-center justify-between px-5 py-3.5 border-b border-gray-100 flex-shrink-0">
                        <h2 class="text-sm font-bold text-gray-900">{{ modal.isEdit ? 'Edit Job' : 'Schedule Job' }}</h2>
                        <button @click="modal.show = false" class="w-7 h-7 flex items-center justify-center rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors">
                            <XMarkIcon class="w-4 h-4" />
                        </button>
                    </div>

                    <!-- Scrollable body -->
                    <form @submit.prevent="submitForm" class="overflow-y-auto flex-1 px-5 py-4 space-y-4">

                        <!-- Title + Project side by side feel -->
                        <div class="grid grid-cols-1 gap-3">
                            <div>
                                <label class="form-label">Title <span class="text-[#EF233C]">*</span></label>
                                <input v-model="form.title" type="text" class="form-input" placeholder="e.g. Landscaping Phase 2" required />
                                <p v-if="form.errors.title" class="form-error">{{ form.errors.title }}</p>
                            </div>
                            <div>
                                <label class="form-label">Project / Site</label>
                                <select v-model="form.project_id" class="form-input" @change="onProjectChange">
                                    <option value="">— None —</option>
                                    <option v-for="p in projects" :key="p.id" :value="p.id">
                                        [{{ p.business?.toUpperCase() }}] {{ p.name }} — {{ p.customer }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- Date + Start + End -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                            <div>
                                <label class="form-label">Date <span class="text-[#EF233C]">*</span></label>
                                <input v-model="form.date" type="date" class="form-input" required />
                                <p v-if="form.errors.date" class="form-error">{{ form.errors.date }}</p>
                            </div>
                            <div>
                                <label class="form-label">Start</label>
                                <input v-model="form.start_time" type="time" class="form-input" />
                            </div>
                            <div>
                                <label class="form-label">End</label>
                                <input v-model="form.end_time" type="time" class="form-input" />
                            </div>
                        </div>

                        <!-- Van -->
                        <div>
                            <label class="form-label">Van</label>
                            <select v-model="form.van_id" class="form-input">
                                <option value="">— No van —</option>
                                <option v-for="v in vans" :key="v.id" :value="v.id">
                                    {{ v.registration }} — {{ v.make }} {{ v.model }}
                                </option>
                            </select>
                        </div>

                        <!-- Crew -->
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="form-label mb-0">Crew</label>
                                <span class="text-xs text-gray-400">{{ form.staff_ids.length }} selected</span>
                            </div>
                            <div class="border border-gray-200 rounded-xl divide-y divide-gray-100 max-h-36 overflow-y-auto">
                                <label
                                    v-for="s in staffList"
                                    :key="s.id"
                                    :class="[
                                        'flex items-center gap-2.5 px-3 py-2 cursor-pointer transition-colors',
                                        form.staff_ids.includes(s.id) ? 'bg-[#EF233C]/5' : 'hover:bg-gray-50',
                                    ]"
                                >
                                    <input type="checkbox" :value="s.id" v-model="form.staff_ids" class="rounded border-gray-300 text-[#EF233C] focus:ring-[#EF233C] flex-shrink-0" />
                                    <img :src="s.avatar_url ?? avatarUrl(s)" :alt="s.name" class="w-6 h-6 rounded-full object-cover flex-shrink-0" />
                                    <span class="text-sm text-gray-700">{{ s.name }}</span>
                                    <CheckIcon v-if="form.staff_ids.includes(s.id)" class="w-3.5 h-3.5 text-[#EF233C] ml-auto flex-shrink-0" />
                                </label>
                                <div v-if="staffList.length === 0" class="px-3 py-4 text-sm text-gray-400 text-center">No active staff</div>
                            </div>
                        </div>

                        <!-- Description + Notes in a compact 2-col on wider screens -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="form-label">Description</label>
                                <textarea v-model="form.description" rows="2" class="form-input resize-none text-sm" placeholder="Optional…" />
                            </div>
                            <div>
                                <label class="form-label">Internal Notes</label>
                                <textarea v-model="form.notes" rows="2" class="form-input resize-none text-sm" placeholder="Managers only…" />
                            </div>
                        </div>
                    </form>

                    <!-- Footer -->
                    <div class="flex gap-2 px-5 py-3.5 border-t border-gray-100 flex-shrink-0">
                        <button type="button" @click="modal.show = false" class="flex-1 border border-gray-200 text-sm font-medium text-gray-600 py-2 rounded-xl hover:bg-gray-50 transition-colors">
                            Cancel
                        </button>
                        <button
                            @click="submitForm"
                            :disabled="form.processing"
                            class="flex-1 bg-[#EF233C] hover:bg-[#D90429] text-white text-sm font-semibold py-2 rounded-xl transition-colors disabled:opacity-60"
                        >
                            {{ form.processing ? 'Saving…' : (modal.isEdit ? 'Save Changes' : 'Create Job') }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- ── Delete Confirm ──────────────────────────────────────── -->
        <Transition name="modal">
            <div v-if="deleteTarget" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="deleteTarget = null" />
                <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6 space-y-4">
                    <div class="w-12 h-12 rounded-2xl bg-red-50 flex items-center justify-center mx-auto">
                        <TrashIcon class="w-6 h-6 text-[#EF233C]" />
                    </div>
                    <div class="text-center">
                        <h2 class="text-base font-bold text-gray-900">Remove Job?</h2>
                        <p class="text-sm text-gray-500 mt-1">
                            <span class="font-medium text-gray-700">{{ deleteTarget.title }}</span> will be permanently removed from the board.
                        </p>
                    </div>
                    <div class="flex gap-3">
                        <button @click="deleteTarget = null" class="flex-1 border border-gray-200 text-sm font-medium text-gray-600 py-2.5 rounded-xl hover:bg-gray-50 transition-colors">Keep</button>
                        <button @click="doDelete" class="flex-1 bg-[#EF233C] hover:bg-[#D90429] text-white text-sm font-semibold py-2.5 rounded-xl transition-colors">Remove</button>
                    </div>
                </div>
            </div>
        </Transition>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    ChevronLeftIcon, ChevronRightIcon, PlusIcon, XMarkIcon,
    ClockIcon, TruckIcon, PencilIcon, TrashIcon, CheckIcon,
    ClipboardDocumentListIcon, CalendarDaysIcon, UsersIcon, BoltIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    jobs:         { type: Array,   required: true },
    date:         { type: String,  required: true },
    isPrivileged: { type: Boolean, default: false },
    projects:     { type: Array,   default: () => [] },
    vans:         { type: Array,   default: () => [] },
    staffList:    { type: Array,   default: () => [] },
});

const page = usePage();

// ── Date ──────────────────────────────────────────────────────────────

const todayStr     = new Date().toISOString().slice(0, 10);
const tomorrowStr  = (() => { const d = new Date(); d.setDate(d.getDate() + 1); return d.toISOString().slice(0, 10); })();
const yesterdayStr = (() => { const d = new Date(); d.setDate(d.getDate() - 1); return d.toISOString().slice(0, 10); })();

const isToday     = computed(() => props.date === todayStr);
const isTomorrow  = computed(() => props.date === tomorrowStr);
const isYesterday = computed(() => props.date === yesterdayStr);

const dayLabel = computed(() =>
    new Intl.DateTimeFormat('en-GB', { weekday: 'long' }).format(new Date(props.date + 'T00:00:00'))
);

const shortDate = computed(() =>
    new Intl.DateTimeFormat('en-GB', { day: 'numeric', month: 'long', year: 'numeric' }).format(new Date(props.date + 'T00:00:00'))
);

function navigate(offset) {
    const d = new Date(props.date + 'T00:00:00');
    d.setDate(d.getDate() + offset);
    goToDate(d.toISOString().slice(0, 10));
}
function goToToday() { goToDate(todayStr); }
function goToDate(d) {
    router.get(route('jobs.index'), { date: d }, { preserveScroll: true, replace: true });
}

// ── Summary ───────────────────────────────────────────────────────────

const totalStaff = computed(() => new Set(props.jobs.flatMap(j => j.staff.map(s => s.id))).size);
const staffClockedIn = computed(() => new Set(props.jobs.flatMap(j => j.staff.filter(s => s.clocked_in).map(s => s.id))).size);
const activeJobs = computed(() => props.jobs.filter(j => j.status === 'in_progress').length);

const summaryStats = computed(() => [
    {
        label: 'Jobs Today',
        value: props.jobs.length,
        icon: CalendarDaysIcon,
        iconBg: 'bg-blue-50',
        iconColor: 'text-blue-500',
        borderClass: 'border-gray-200',
    },
    {
        label: 'In Progress',
        value: activeJobs.value,
        icon: BoltIcon,
        iconBg: 'bg-amber-50',
        iconColor: 'text-amber-500',
        borderClass: activeJobs.value > 0 ? 'border-amber-200' : 'border-gray-200',
    },
    {
        label: 'Staff Deployed',
        value: totalStaff.value,
        icon: UsersIcon,
        iconBg: 'bg-purple-50',
        iconColor: 'text-purple-500',
        borderClass: 'border-gray-200',
    },
    {
        label: 'Clocked In',
        value: staffClockedIn.value,
        icon: CheckIcon,
        iconBg: 'bg-green-50',
        iconColor: 'text-green-500',
        borderClass: staffClockedIn.value > 0 ? 'border-green-200' : 'border-gray-200',
    },
]);

const canDelete = computed(() => {
    const roles = page.props.auth?.user?.roles ?? [];
    return roles.some(r => ['admin', 'manager'].includes(r));
});

// ── Modal ─────────────────────────────────────────────────────────────

const modal = ref({ show: false, isEdit: false, jobId: null });

const form = useForm({
    project_id: '', van_id: '', title: '', description: '',
    date: props.date, start_time: '', end_time: '', notes: '', staff_ids: [],
});

function openCreate() {
    form.reset();
    form.date = props.date;
    modal.value = { show: true, isEdit: false, jobId: null };
}

function openEdit(job) {
    form.project_id  = job.project?.id  ?? '';
    form.van_id      = job.van?.id      ?? '';
    form.title       = job.title;
    form.description = job.description  ?? '';
    form.date        = job.date;
    form.start_time  = job.start_time   ?? '';
    form.end_time    = job.end_time     ?? '';
    form.notes       = job.notes        ?? '';
    form.staff_ids   = job.staff.map(s => s.id);
    form.clearErrors();
    modal.value = { show: true, isEdit: true, jobId: job.id };
}

function onProjectChange() {
    if (!form.title && form.project_id) {
        const p = props.projects.find(p => p.id === form.project_id);
        if (p) form.title = p.name;
    }
}

function submitForm() {
    if (modal.value.isEdit) {
        form.put(route('jobs.update', modal.value.jobId), { onSuccess: () => { modal.value.show = false; } });
    } else {
        form.post(route('jobs.store'), { onSuccess: () => { modal.value.show = false; form.reset(); form.date = props.date; } });
    }
}

// ── Status ────────────────────────────────────────────────────────────

const statusLoading = ref(null);

function setStatus(job, status) {
    if (statusLoading.value) return;
    statusLoading.value = job.id;
    router.patch(route('jobs.status', job.id), { status }, {
        preserveScroll: true,
        onFinish: () => { statusLoading.value = null; },
    });
}

// ── Delete ────────────────────────────────────────────────────────────

const deleteTarget = ref(null);
function confirmDelete(job) { deleteTarget.value = job; }
function doDelete() {
    router.delete(route('jobs.destroy', deleteTarget.value.id), {
        preserveScroll: true,
        onSuccess: () => { deleteTarget.value = null; },
    });
}

// ── Style helpers ─────────────────────────────────────────────────────

function avatarUrl(s) {
    return `https://ui-avatars.com/api/?name=${encodeURIComponent(s.name)}&background=3B6D11&color=fff&size=64`;
}

function statusLabel(s) {
    return { scheduled: 'Scheduled', in_progress: 'In Progress', completed: 'Completed', cancelled: 'Cancelled' }[s] ?? s;
}

function cardClass(s) {
    return {
        scheduled:   'border-gray-200',
        in_progress: 'border-amber-300 ring-1 ring-amber-200',
        completed:   'border-green-200 bg-green-50/30',
        cancelled:   'border-gray-200 opacity-55',
    }[s] ?? 'border-gray-200';
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
.form-label   { @apply block text-xs font-semibold text-gray-600 mb-1.5; }
.form-input   { @apply w-full rounded-xl border-gray-200 text-sm focus:ring-[#EF233C] focus:border-[#EF233C]; }
.form-error   { @apply mt-1 text-xs text-red-600; }
.status-action { @apply text-xs border px-3 py-1.5 rounded-lg transition-colors font-semibold flex-1 text-center; }
.modal-enter-active, .modal-leave-active { transition: opacity 0.2s ease; }
.modal-enter-from, .modal-leave-to       { opacity: 0; }
</style>
