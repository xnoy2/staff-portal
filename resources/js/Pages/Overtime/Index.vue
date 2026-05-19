<template>
    <AppLayout title="Overtime">
        <div class="max-w-6xl mx-auto space-y-5">

            <!-- Header -->
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-lg font-semibold text-gray-800">Overtime</h1>
                    <p class="text-xs text-gray-500 mt-0.5">Plan and submit your overtime requests</p>
                </div>
                <button
                    @click="openAdd(null)"
                    class="inline-flex items-center gap-1.5 bg-[#EF233C] text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-[#D90429] transition-colors"
                >
                    <PlusIcon class="w-4 h-4" />
                    Request OT
                </button>
            </div>

            <!-- Week navigation -->
            <div class="bg-[#2B2D42] rounded-2xl px-4 py-3 flex items-center justify-between gap-3">
                <button @click="navigate(-1)" class="w-9 h-9 flex items-center justify-center rounded-xl text-white/50 hover:text-white hover:bg-white/10 transition-colors flex-shrink-0">
                    <ChevronLeftIcon class="w-5 h-5" />
                </button>
                <div class="text-center">
                    <p class="text-white font-bold text-sm">{{ weekLabel }}</p>
                    <p class="text-white/50 text-xs mt-0.5">{{ weekRange }}</p>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    <button v-if="!isCurrentWeek" @click="goToCurrentWeek" class="text-xs text-white/70 hover:text-white border border-white/20 hover:border-white/40 px-3 py-1.5 rounded-lg transition-colors">
                        This Week
                    </button>
                    <button @click="navigate(1)" class="w-9 h-9 flex items-center justify-center rounded-xl text-white/50 hover:text-white hover:bg-white/10 transition-colors">
                        <ChevronRightIcon class="w-5 h-5" />
                    </button>
                </div>
            </div>

            <!-- Weekly calendar grid -->
            <div class="grid grid-cols-7 gap-2">
                <div
                    v-for="day in weekDays"
                    :key="day.date"
                    class="bg-white rounded-xl border flex flex-col overflow-hidden min-h-[140px]"
                    :class="day.isToday ? 'border-[#EF233C] ring-1 ring-[#EF233C]/20' : 'border-gray-200'"
                >
                    <!-- Day header -->
                    <div
                        class="px-2 py-1.5 text-center border-b flex-shrink-0"
                        :class="day.isToday ? 'bg-[#EF233C]/5 border-[#EF233C]/20' : day.isWeekend ? 'bg-amber-50 border-amber-100' : 'bg-gray-50 border-gray-100'"
                    >
                        <p class="text-[10px] font-semibold uppercase tracking-wide" :class="day.isToday ? 'text-[#EF233C]' : day.isWeekend ? 'text-amber-600' : 'text-gray-500'">
                            {{ day.dayName }}
                        </p>
                        <p class="text-sm font-bold" :class="day.isToday ? 'text-[#EF233C]' : 'text-gray-700'">
                            {{ day.dayNum }}
                        </p>
                        <p v-if="day.isWeekend" class="text-[9px] text-amber-500 font-medium">REST DAY</p>
                    </div>

                    <!-- OT blocks for this day -->
                    <div class="flex-1 p-1.5 space-y-1 overflow-y-auto">
                        <div
                            v-for="req in day.requests"
                            :key="req.id"
                            class="rounded-md px-1.5 py-1 text-[10px] font-medium leading-tight cursor-pointer hover:opacity-80 transition-opacity"
                            :class="otBlockClass(req)"
                            @click="openView(req)"
                        >
                            <div class="flex items-center justify-between gap-1">
                                <span class="font-bold uppercase">{{ req.type }}</span>
                                <span class="opacity-75">{{ req.start_time }}–{{ req.end_time }}</span>
                            </div>
                            <div class="opacity-75 mt-0.5">{{ req.duration_hours }}h</div>
                        </div>
                    </div>

                    <!-- Add button -->
                    <button
                        @click="openAdd(day.date)"
                        class="w-full py-1 text-[10px] text-gray-400 hover:text-[#EF233C] hover:bg-gray-50 transition-colors border-t border-gray-100 flex-shrink-0"
                    >
                        <PlusIcon class="w-3 h-3 inline mr-0.5" />Add
                    </button>
                </div>
            </div>

            <!-- My OT requests list -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-gray-800">This Week's Requests</h2>
                    <span class="text-xs text-gray-400">{{ myRequests.length }} request{{ myRequests.length !== 1 ? 's' : '' }}</span>
                </div>

                <div v-if="myRequests.length === 0" class="py-10 text-center text-sm text-gray-400">
                    No OT requests for this week.
                </div>

                <div v-else class="divide-y divide-gray-100">
                    <div v-for="req in myRequests" :key="req.id" class="flex items-center gap-4 px-5 py-3">
                        <!-- Day + date -->
                        <div class="w-12 text-center flex-shrink-0">
                            <p class="text-xs font-semibold text-gray-500 uppercase">{{ req.day }}</p>
                            <p class="text-sm font-bold text-gray-800">{{ req.date.slice(8, 10) }}</p>
                        </div>

                        <!-- Type badge -->
                        <span :class="['text-xs font-bold px-2 py-0.5 rounded-full uppercase flex-shrink-0', req.type === 'rdot' ? 'bg-purple-100 text-purple-700' : 'bg-amber-100 text-amber-700']">
                            {{ req.type }}
                        </span>

                        <!-- Times + duration -->
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-800">{{ req.start_time }} – {{ req.end_time }} <span class="text-gray-400 font-normal">({{ req.duration_hours }}h)</span></p>
                            <p v-if="req.reason" class="text-xs text-gray-400 truncate mt-0.5">{{ req.reason }}</p>
                        </div>

                        <!-- Status -->
                        <span :class="['text-xs font-medium px-2 py-0.5 rounded-full border flex-shrink-0', statusClass(req.status)]">
                            {{ req.status }}
                        </span>

                        <!-- Actions -->
                        <div class="flex items-center gap-1 flex-shrink-0">
                            <button
                                v-if="req.status === 'pending'"
                                @click="openEdit(req)"
                                class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                                title="Edit"
                            >
                                <PencilIcon class="w-3.5 h-3.5" />
                            </button>
                            <button
                                v-if="req.status === 'pending'"
                                @click="cancelTarget = req"
                                class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                title="Cancel"
                            >
                                <TrashIcon class="w-3.5 h-3.5" />
                            </button>
                            <button
                                v-if="req.reviewer_notes"
                                @click="openView(req)"
                                class="p-1.5 text-gray-400 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
                                title="View notes"
                            >
                                <ChatBubbleLeftIcon class="w-3.5 h-3.5" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- HR / Manager review panel -->
            <div v-if="canReview" class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-gray-800">Pending Review</h2>
                        <p class="text-xs text-gray-400 mt-0.5">All staff OT requests awaiting approval</p>
                    </div>
                    <span v-if="pendingReview.length" class="text-xs font-bold bg-[#EF233C] text-white px-2 py-0.5 rounded-full">
                        {{ pendingReview.length }}
                    </span>
                </div>

                <div v-if="pendingReview.length === 0" class="py-10 text-center text-sm text-gray-400">
                    No pending OT requests.
                </div>

                <div v-else class="divide-y divide-gray-100">
                    <div v-for="req in pendingReview" :key="req.id" class="flex items-center gap-3 px-5 py-3 flex-wrap">
                        <!-- Avatar + name -->
                        <div class="flex items-center gap-2 w-36 flex-shrink-0">
                            <img :src="req.user.avatar_url" :alt="req.user.name" class="w-7 h-7 rounded-full object-cover flex-shrink-0" />
                            <p class="text-xs font-medium text-gray-700 truncate">{{ req.user.name }}</p>
                        </div>

                        <!-- Date + Type -->
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <span class="text-xs text-gray-500">{{ fmtDate(req.date) }}</span>
                            <span :class="['text-xs font-bold px-2 py-0.5 rounded-full uppercase', req.type === 'rdot' ? 'bg-purple-100 text-purple-700' : 'bg-amber-100 text-amber-700']">
                                {{ req.type }}
                            </span>
                        </div>

                        <!-- Times -->
                        <p class="text-sm text-gray-700 flex-1">{{ req.start_time }} – {{ req.end_time }} <span class="text-gray-400">({{ req.duration_hours }}h)</span></p>

                        <!-- Reason -->
                        <p v-if="req.reason" class="text-xs text-gray-400 flex-1 min-w-0 truncate hidden sm:block">{{ req.reason }}</p>

                        <!-- Review buttons -->
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <button
                                @click="reviewTarget = { req, action: 'approve' }"
                                class="inline-flex items-center gap-1 text-xs font-medium px-3 py-1.5 rounded-lg bg-green-50 text-green-700 border border-green-200 hover:bg-green-100 transition-colors"
                            >
                                <CheckIcon class="w-3.5 h-3.5" />
                                Approve
                            </button>
                            <button
                                @click="reviewTarget = { req, action: 'reject' }"
                                class="inline-flex items-center gap-1 text-xs font-medium px-3 py-1.5 rounded-lg bg-red-50 text-red-700 border border-red-200 hover:bg-red-100 transition-colors"
                            >
                                <XMarkIcon class="w-3.5 h-3.5" />
                                Reject
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <Teleport to="body">

            <!-- Add / Edit modal -->
            <Transition name="modal">
                <div v-if="form.open" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="closeForm" />
                    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
                        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                            <h2 class="text-sm font-semibold text-gray-800">{{ form.editing ? 'Edit OT Request' : 'Request Overtime' }}</h2>
                            <button @click="closeForm" class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                                <XMarkIcon class="w-4 h-4" />
                            </button>
                        </div>

                        <form @submit.prevent="submitForm" class="p-5 space-y-4">
                            <!-- Date -->
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1.5">Date <span class="text-red-500">*</span></label>
                                <input v-model="form.date" type="date" required class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-[#EF233C] focus:border-[#EF233C]" />
                            </div>

                            <!-- Time range -->
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1.5">Start Time <span class="text-red-500">*</span></label>
                                    <input v-model="form.start_time" type="time" required class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-[#EF233C] focus:border-[#EF233C]" />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1.5">End Time <span class="text-red-500">*</span></label>
                                    <input v-model="form.end_time" type="time" required class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-[#EF233C] focus:border-[#EF233C]" />
                                </div>
                            </div>

                            <!-- Duration preview -->
                            <div v-if="formDuration > 0" class="text-xs text-gray-500 bg-gray-50 rounded-lg px-3 py-2">
                                Duration: <span class="font-semibold text-gray-700">{{ formDuration }}h</span>
                            </div>

                            <!-- Type -->
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-2">OT Type <span class="text-red-500">*</span></label>
                                <div class="grid grid-cols-2 gap-2">
                                    <button
                                        type="button"
                                        @click="form.type = 'ot'"
                                        :class="['px-4 py-2.5 rounded-lg border text-sm font-semibold transition-colors', form.type === 'ot' ? 'bg-amber-500 text-white border-amber-500' : 'border-gray-300 text-gray-600 hover:border-amber-300']"
                                    >
                                        OT
                                        <p class="text-[10px] font-normal opacity-75 mt-0.5">Regular Overtime</p>
                                    </button>
                                    <button
                                        type="button"
                                        @click="form.type = 'rdot'"
                                        :class="['px-4 py-2.5 rounded-lg border text-sm font-semibold transition-colors', form.type === 'rdot' ? 'bg-purple-600 text-white border-purple-600' : 'border-gray-300 text-gray-600 hover:border-purple-300']"
                                    >
                                        RDOT
                                        <p class="text-[10px] font-normal opacity-75 mt-0.5">Rest Day OT</p>
                                    </button>
                                </div>
                            </div>

                            <!-- Reason -->
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1.5">Reason <span class="text-gray-400 font-normal">(optional)</span></label>
                                <textarea v-model="form.reason" rows="2" placeholder="Briefly describe the reason for OT…" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-[#EF233C] focus:border-[#EF233C] resize-none" />
                            </div>

                            <div class="flex items-center justify-end gap-3 pt-1 border-t border-gray-100">
                                <button type="button" @click="closeForm" class="text-sm text-gray-500 hover:text-gray-700 px-3 py-1.5 transition-colors">Cancel</button>
                                <button type="submit" :disabled="processing" class="bg-[#EF233C] hover:bg-[#D90429] disabled:opacity-60 text-white text-sm font-medium px-5 py-1.5 rounded-lg transition-colors flex items-center gap-2">
                                    <span v-if="processing" class="w-3.5 h-3.5 border-2 border-white/40 border-t-white rounded-full animate-spin" />
                                    {{ form.editing ? 'Save Changes' : 'Submit Request' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Transition>

            <!-- View / notes modal -->
            <Transition name="modal">
                <div v-if="viewTarget" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="viewTarget = null" />
                    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-sm font-semibold text-gray-800">OT Request Detail</h2>
                            <button @click="viewTarget = null" class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                                <XMarkIcon class="w-4 h-4" />
                            </button>
                        </div>
                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between"><span class="text-gray-500">Date</span><span class="font-medium text-gray-800">{{ fmtDate(viewTarget?.date) }}</span></div>
                            <div class="flex justify-between"><span class="text-gray-500">Time</span><span class="font-medium text-gray-800">{{ viewTarget?.start_time }} – {{ viewTarget?.end_time }}</span></div>
                            <div class="flex justify-between"><span class="text-gray-500">Type</span><span :class="['font-bold uppercase text-xs px-2 py-0.5 rounded-full', viewTarget?.type === 'rdot' ? 'bg-purple-100 text-purple-700' : 'bg-amber-100 text-amber-700']">{{ viewTarget?.type }}</span></div>
                            <div class="flex justify-between"><span class="text-gray-500">Status</span><span :class="['text-xs font-medium px-2 py-0.5 rounded-full border', statusClass(viewTarget?.status)]">{{ viewTarget?.status }}</span></div>
                            <div v-if="viewTarget?.reason"><span class="text-gray-500 block mb-1">Reason</span><p class="text-gray-700 bg-gray-50 rounded-lg px-3 py-2">{{ viewTarget.reason }}</p></div>
                            <div v-if="viewTarget?.reviewer_notes" class="border-t border-gray-100 pt-3">
                                <span class="text-gray-500 block mb-1">Reviewer notes</span>
                                <p class="text-gray-700 bg-gray-50 rounded-lg px-3 py-2">{{ viewTarget.reviewer_notes }}</p>
                                <p v-if="viewTarget?.reviewer" class="text-xs text-gray-400 mt-1">— {{ viewTarget.reviewer.name }}, {{ viewTarget.reviewed_at }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>

            <!-- Review modal (approve / reject) -->
            <Transition name="modal">
                <div v-if="reviewTarget" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="reviewTarget = null" />
                    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6">
                        <div class="flex items-center gap-3 mb-4">
                            <div :class="['w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0', reviewTarget.action === 'approve' ? 'bg-green-50' : 'bg-red-50']">
                                <CheckIcon v-if="reviewTarget.action === 'approve'" class="w-5 h-5 text-green-600" />
                                <XMarkIcon v-else class="w-5 h-5 text-red-500" />
                            </div>
                            <div>
                                <p class="font-semibold text-gray-800 text-sm capitalize">{{ reviewTarget.action }} OT Request</p>
                                <p class="text-xs text-gray-500">{{ reviewTarget.req.user?.name }} · {{ fmtDate(reviewTarget.req.date) }}</p>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="block text-xs font-medium text-gray-700 mb-1.5">
                                Notes <span v-if="reviewTarget.action === 'reject'" class="text-red-500">*</span><span v-else class="text-gray-400 font-normal">(optional)</span>
                            </label>
                            <textarea v-model="reviewNotes" rows="3" placeholder="Add a note…" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-[#EF233C] focus:border-[#EF233C] resize-none" />
                        </div>
                        <div class="flex justify-end gap-3">
                            <button @click="reviewTarget = null" class="text-sm text-gray-500 hover:text-gray-700 px-3 py-1.5 transition-colors">Cancel</button>
                            <button
                                @click="submitReview"
                                :disabled="processing || (reviewTarget.action === 'reject' && !reviewNotes.trim())"
                                :class="['text-white text-sm font-medium px-5 py-1.5 rounded-lg transition-colors disabled:opacity-60 flex items-center gap-2', reviewTarget.action === 'approve' ? 'bg-green-600 hover:bg-green-700' : 'bg-[#EF233C] hover:bg-[#D90429]']"
                            >
                                <span v-if="processing" class="w-3.5 h-3.5 border-2 border-white/40 border-t-white rounded-full animate-spin" />
                                {{ reviewTarget.action === 'approve' ? 'Approve' : 'Reject' }}
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>

            <!-- Cancel confirm -->
            <Transition name="modal">
                <div v-if="cancelTarget" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="cancelTarget = null" />
                    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6">
                        <p class="font-semibold text-gray-800 text-sm mb-2">Cancel OT request?</p>
                        <p class="text-sm text-gray-500 mb-5">{{ fmtDate(cancelTarget?.date) }} · {{ cancelTarget?.start_time }}–{{ cancelTarget?.end_time }}</p>
                        <div class="flex justify-end gap-3">
                            <button @click="cancelTarget = null" class="text-sm text-gray-500 hover:text-gray-700 px-3 py-1.5 transition-colors">Keep</button>
                            <button @click="doCancel" class="bg-[#EF233C] hover:bg-[#D90429] text-white text-sm font-medium px-5 py-1.5 rounded-lg transition-colors">Cancel Request</button>
                        </div>
                    </div>
                </div>
            </Transition>

        </Teleport>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    PlusIcon,
    XMarkIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
    PencilIcon,
    TrashIcon,
    CheckIcon,
    ChatBubbleLeftIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    myRequests:    { type: Array,   default: () => [] },
    pendingReview: { type: Array,   default: () => [] },
    weekStart:     { type: String,  required: true },
    weekEnd:       { type: String,  required: true },
    canReview:     { type: Boolean, default: false },
    staffList:     { type: Array,   default: () => [] },
});

// ─── Week navigation ─────────────────────────────────────────────

function parseDate(s) { return new Date(s + 'T00:00:00'); }

function localDateStr(d) {
    const y  = d.getFullYear();
    const m  = String(d.getMonth() + 1).padStart(2, '0');
    const dd = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${dd}`;
}

const todayStr = localDateStr(new Date());

const isCurrentWeek = computed(() => {
    const mon = getMonday(new Date());
    return props.weekStart === localDateStr(mon);
});

function getMonday(d) {
    const day = d.getDay() || 7;
    const m = new Date(d);
    m.setDate(d.getDate() - day + 1);
    return m;
}

function addDays(d, n) {
    const r = new Date(d);
    r.setDate(r.getDate() + n);
    return r;
}

function navigate(weeks) {
    const current = parseDate(props.weekStart);
    const next    = addDays(current, weeks * 7);
    router.get(route('overtime.index'), { week: localDateStr(next) }, { preserveState: false });
}

function goToCurrentWeek() {
    router.get(route('overtime.index'), {}, { preserveState: false });
}

const weekLabel = computed(() => {
    const start = parseDate(props.weekStart);
    return start.toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' });
});

const weekRange = computed(() => {
    const s = parseDate(props.weekStart);
    const e = parseDate(props.weekEnd);
    return `${s.toLocaleDateString('en-GB', { day: 'numeric', month: 'short' })} – ${e.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' })}`;
});

// ─── Calendar grid ───────────────────────────────────────────────

const DAY_NAMES = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

const weekDays = computed(() => {
    const start = parseDate(props.weekStart);
    return DAY_NAMES.map((name, i) => {
        const d    = addDays(start, i);
        const dateStr = localDateStr(d);
        return {
            date:      dateStr,
            dayName:   name,
            dayNum:    d.getDate(),
            isToday:   dateStr === todayStr,
            isWeekend: i >= 5,
            requests:  props.myRequests.filter(r => r.date === dateStr),
        };
    });
});

// ─── Helpers ─────────────────────────────────────────────────────

function fmtDate(str) {
    if (!str) return '';
    return parseDate(str).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
}

function statusClass(status) {
    return {
        pending:  'bg-amber-50 text-amber-700 border-amber-200',
        approved: 'bg-green-50 text-green-700 border-green-200',
        rejected: 'bg-red-50 text-red-600 border-red-200',
    }[status] ?? 'bg-gray-100 text-gray-500 border-gray-200';
}

function otBlockClass(req) {
    if (req.status === 'approved') return req.type === 'rdot' ? 'bg-purple-500 text-white' : 'bg-green-500 text-white';
    if (req.status === 'rejected') return 'bg-gray-200 text-gray-500 line-through';
    return req.type === 'rdot' ? 'bg-purple-100 text-purple-700' : 'bg-amber-100 text-amber-700';
}

// ─── Add / Edit form ─────────────────────────────────────────────

const processing = ref(false);

function blankForm() {
    return { open: false, editing: null, date: todayStr, start_time: '18:00', end_time: '20:00', type: 'ot', reason: '' };
}
const form = ref(blankForm());

const formDuration = computed(() => {
    if (!form.value.start_time || !form.value.end_time) return 0;
    const [sh, sm] = form.value.start_time.split(':').map(Number);
    const [eh, em] = form.value.end_time.split(':').map(Number);
    const mins = (eh * 60 + em) - (sh * 60 + sm);
    return mins > 0 ? +(mins / 60).toFixed(2) : 0;
});

function openAdd(date) {
    form.value = blankForm();
    if (date) form.value.date = date;
    form.value.open = true;
}

function openEdit(req) {
    form.value = {
        open: true, editing: req,
        date: req.date, start_time: req.start_time, end_time: req.end_time,
        type: req.type, reason: req.reason ?? '',
    };
}

function closeForm() { form.value.open = false; }

function submitForm() {
    processing.value = true;
    const payload = {
        date: form.value.date, start_time: form.value.start_time,
        end_time: form.value.end_time, type: form.value.type,
        reason: form.value.reason || null,
    };
    const opts = { preserveScroll: true, onFinish: () => { processing.value = false; closeForm(); } };
    if (form.value.editing) {
        router.put(route('overtime.update', form.value.editing.id), payload, opts);
    } else {
        router.post(route('overtime.store'), payload, opts);
    }
}

// ─── View ─────────────────────────────────────────────────────────

const viewTarget = ref(null);
function openView(req) { viewTarget.value = req; }

// ─── Cancel ───────────────────────────────────────────────────────

const cancelTarget = ref(null);
function doCancel() {
    if (!cancelTarget.value) return;
    router.delete(route('overtime.destroy', cancelTarget.value.id), {
        preserveScroll: true,
        onFinish: () => { cancelTarget.value = null; },
    });
}

// ─── Review (approve / reject) ────────────────────────────────────

const reviewTarget = ref(null);
const reviewNotes  = ref('');

function submitReview() {
    if (!reviewTarget.value) return;
    processing.value = true;
    const { req, action } = reviewTarget.value;
    router.post(
        route(`overtime.${action}`, req.id),
        { notes: reviewNotes.value || null },
        {
            preserveScroll: true,
            onFinish: () => {
                processing.value = false;
                reviewTarget.value = null;
                reviewNotes.value  = '';
            },
        }
    );
}
</script>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: opacity 0.15s ease; }
.modal-enter-from, .modal-leave-to       { opacity: 0; }
</style>
