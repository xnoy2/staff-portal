<template>
    <Teleport to="body">
        <!-- click-outside catcher -->
        <div class="fixed inset-0 z-[60]" @click="$emit('close')"></div>

        <!-- floating popover -->
        <div
            ref="popEl"
            class="fixed z-[61] w-[290px] bg-white rounded-xl shadow-2xl border border-gray-200 max-h-[88vh] overflow-y-auto"
            :style="posStyle"
            @click.stop
        >
            <!-- Header -->
            <div class="relative flex items-center justify-center px-4 py-2.5 border-b border-gray-100">
                <span class="text-sm font-semibold text-gray-600">Dates</span>
                <button @click="$emit('close')" class="absolute right-2 top-1.5 p-1.5 rounded-lg text-gray-400 hover:bg-gray-100">
                    <XMarkIcon class="w-4 h-4" />
                </button>
            </div>

            <div class="px-3 py-3">
                <!-- ── Month calendar ───────────────────────────────────────── -->
                <div class="flex items-center justify-between mb-1.5">
                    <div class="flex items-center gap-0.5">
                        <button @click="shift('year', -1)" :class="navBtn" title="Previous year"><ChevronDoubleLeftIcon class="w-3.5 h-3.5" /></button>
                        <button @click="shift('month', -1)" :class="navBtn" title="Previous month"><ChevronLeftIcon class="w-3.5 h-3.5" /></button>
                    </div>
                    <span class="text-[13px] font-bold text-gray-700">{{ viewMonth.format('MMMM YYYY') }}</span>
                    <div class="flex items-center gap-0.5">
                        <button @click="shift('month', 1)" :class="navBtn" title="Next month"><ChevronRightIcon class="w-3.5 h-3.5" /></button>
                        <button @click="shift('year', 1)" :class="navBtn" title="Next year"><ChevronDoubleRightIcon class="w-3.5 h-3.5" /></button>
                    </div>
                </div>

                <div class="grid grid-cols-7">
                    <span v-for="d in WEEKDAYS" :key="d" class="text-[10px] font-bold text-gray-400 text-center py-0.5">{{ d }}</span>
                </div>
                <div class="grid grid-cols-7">
                    <button
                        v-for="day in days"
                        :key="day.format('YYYY-MM-DD')"
                        @click="pickDay(day)"
                        :class="dayClass(day)"
                    >{{ day.date() }}</button>
                </div>

                <!-- ── Start date ───────────────────────────────────────────── -->
                <div class="mt-3">
                    <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wide mb-1">Start date</p>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" v-model="startEnabled" class="rounded text-[#EF233C] focus:ring-[#EF233C]/40" />
                        <input
                            type="date"
                            v-model="startDate"
                            :disabled="!startEnabled"
                            @focus="activeField = 'start'"
                            :class="['flex-1 min-w-0 text-[13px] border rounded-md px-2 py-1 focus:outline-none focus:ring-2 focus:ring-[#EF233C]/15',
                                     startEnabled ? 'border-gray-200' : 'border-gray-100 bg-gray-50 text-gray-300',
                                     activeField === 'start' && startEnabled ? 'ring-2 ring-[#EF233C]/30' : '']"
                        />
                    </div>
                </div>

                <!-- ── Due date ─────────────────────────────────────────────── -->
                <div class="mt-2.5">
                    <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wide mb-1">Due date</p>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" v-model="dueEnabled" class="rounded text-[#EF233C] focus:ring-[#EF233C]/40" />
                        <input
                            type="date"
                            v-model="dueDate"
                            :disabled="!dueEnabled"
                            @focus="activeField = 'due'"
                            :class="['flex-1 min-w-0 text-[13px] border rounded-md px-2 py-1 focus:outline-none focus:ring-2 focus:ring-[#EF233C]/15',
                                     dueEnabled ? 'border-gray-200' : 'border-gray-100 bg-gray-50 text-gray-300',
                                     activeField === 'due' && dueEnabled ? 'ring-2 ring-[#EF233C]/30' : '']"
                        />
                        <input
                            type="time"
                            v-model="dueTime"
                            :disabled="!dueEnabled"
                            @focus="activeField = 'due'"
                            :class="['w-[84px] text-[13px] border rounded-md px-1.5 py-1 focus:outline-none focus:ring-2 focus:ring-[#EF233C]/15',
                                     dueEnabled ? 'border-gray-200' : 'border-gray-100 bg-gray-50 text-gray-300']"
                        />
                    </div>
                </div>

                <!-- ── Recurring ────────────────────────────────────────────── -->
                <div class="mt-2.5">
                    <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wide mb-1">Recurring</p>
                    <select v-model="recurring" :disabled="!dueEnabled"
                        :class="['w-full text-[13px] border rounded-md px-2 py-1.5 focus:outline-none focus:ring-2 focus:ring-[#EF233C]/15',
                                 dueEnabled ? 'border-gray-200' : 'border-gray-100 bg-gray-50 text-gray-300']">
                        <option v-for="o in RECURRING" :key="o.value" :value="o.value">{{ o.label }}</option>
                    </select>
                </div>

                <!-- ── Reminder ─────────────────────────────────────────────── -->
                <div class="mt-2.5">
                    <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wide mb-1">Set due date reminder</p>
                    <select v-model="reminder" :disabled="!dueEnabled"
                        :class="['w-full text-[13px] border rounded-md px-2 py-1.5 focus:outline-none focus:ring-2 focus:ring-[#EF233C]/15',
                                 dueEnabled ? 'border-gray-200' : 'border-gray-100 bg-gray-50 text-gray-300']">
                        <option v-for="o in REMINDERS" :key="o.value" :value="o.value">{{ o.label }}</option>
                    </select>
                    <p class="text-[11px] text-gray-400 mt-1.5 leading-snug">Reminders will be sent to all members of this card's workspace.</p>
                </div>

                <!-- ── Actions ──────────────────────────────────────────────── -->
                <div class="mt-3 space-y-1.5">
                    <button @click="save" class="w-full text-[13px] font-semibold bg-[#2B2D42] hover:bg-[#EF233C] text-white px-3 py-2 rounded-lg transition-colors">Save</button>
                    <button @click="$emit('remove')" class="w-full text-[13px] font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 px-3 py-2 rounded-lg transition-colors">Remove</button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount, nextTick } from 'vue';
import dayjs from 'dayjs';
import {
    XMarkIcon, ChevronLeftIcon, ChevronRightIcon, ChevronDoubleLeftIcon, ChevronDoubleRightIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    card:   { type: Object, required: true },
    anchor: { type: Object, default: null }, // DOMRect of the trigger button
});
const emit = defineEmits(['save', 'remove', 'close']);

const WEEKDAYS = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
const RECURRING = [
    { value: 'never',   label: 'Never' },
    { value: 'daily',   label: 'Daily' },
    { value: 'weekly',  label: 'Weekly' },
    { value: 'monthly', label: 'Monthly' },
];
const REMINDERS = [
    { value: '',        label: 'None' },
    { value: 'at_time', label: 'At time of due date' },
    { value: '5_min',   label: '5 Minutes before' },
    { value: '10_min',  label: '10 Minutes before' },
    { value: '15_min',  label: '15 Minutes before' },
    { value: '1_hour',  label: '1 Hour before' },
    { value: '2_hour',  label: '2 Hours before' },
    { value: '1_day',   label: '1 Day before' },
    { value: '2_day',   label: '2 Days before' },
];

const navBtn = 'p-1 rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors';

// ── Draft state, seeded from the card ───────────────────────────────────────
const startEnabled = ref(false);
const startDate    = ref('');
const dueEnabled   = ref(false);
const dueDate      = ref('');
const dueTime      = ref('12:00');
const recurring    = ref('never');
const reminder     = ref('');
const activeField  = ref('due');
const viewMonth    = ref(dayjs().startOf('month'));

function seed(card) {
    const due = card.due_date ? dayjs(card.due_date) : null;
    dueEnabled.value = !!due;
    dueDate.value    = due ? due.format('YYYY-MM-DD') : dayjs().format('YYYY-MM-DD');
    dueTime.value    = due ? due.format('HH:mm') : '12:00';

    startEnabled.value = !!card.start_date;
    startDate.value    = card.start_date || dayjs().format('YYYY-MM-DD');

    recurring.value = card.recurring || 'never';
    reminder.value  = card.due_reminder || (due ? 'at_time' : '');
    activeField.value = due ? 'due' : 'start';
    viewMonth.value = (due || (card.start_date ? dayjs(card.start_date) : dayjs())).startOf('month');
}
watch(() => props.card?.id, () => seed(props.card), { immediate: true });

watch(dueEnabled, (on) => {
    if (on && reminder.value === '') reminder.value = 'at_time';
    if (!on) { recurring.value = 'never'; reminder.value = ''; }
});

// ── Floating position (anchored to the trigger, clamped to viewport) ─────────
const popEl = ref(null);
const pos   = ref({ top: 0, left: 0 });
const posStyle = computed(() => ({ top: `${pos.value.top}px`, left: `${pos.value.left}px` }));

function place() {
    const W = 290;
    const H = popEl.value?.offsetHeight || 480;
    const vw = window.innerWidth;
    const vh = window.innerHeight;
    const a = props.anchor;

    let left = a ? a.left : (vw - W) / 2;
    let top  = a ? a.bottom + 6 : (vh - H) / 2;

    left = Math.max(8, Math.min(left, vw - W - 8));
    if (top + H > vh - 8) {
        top = (a && a.top - H - 6 > 8) ? a.top - H - 6 : Math.max(8, vh - H - 8);
    }
    pos.value = { top, left };
}

function onKey(e) { if (e.key === 'Escape') emit('close'); }

onMounted(() => {
    nextTick(place);
    window.addEventListener('resize', place);
    window.addEventListener('keydown', onKey);
});
onBeforeUnmount(() => {
    window.removeEventListener('resize', place);
    window.removeEventListener('keydown', onKey);
});

// ── Calendar grid ───────────────────────────────────────────────────────────
const days = computed(() => {
    const start = viewMonth.value.startOf('month').startOf('week');
    return Array.from({ length: 42 }, (_, i) => start.add(i, 'day'));
});

function shift(unit, n) { viewMonth.value = viewMonth.value.add(n, unit); }

function pickDay(day) {
    const iso = day.format('YYYY-MM-DD');
    if (activeField.value === 'start') {
        startDate.value = iso;
        startEnabled.value = true;
    } else {
        dueDate.value = iso;
        dueEnabled.value = true;
    }
    if (day.month() !== viewMonth.value.month()) viewMonth.value = day.startOf('month');
}

const today    = dayjs().format('YYYY-MM-DD');
const selStart = computed(() => (startEnabled.value ? startDate.value : null));
const selDue   = computed(() => (dueEnabled.value ? dueDate.value : null));

function dayClass(day) {
    const iso = day.format('YYYY-MM-DD');
    const base = 'h-7 w-full text-[11px] rounded-md flex items-center justify-center transition-colors';
    const dim = day.month() !== viewMonth.value.month() ? ' text-gray-300' : ' text-gray-700';

    if (iso === selDue.value) return base + ' bg-[#EF233C] text-white font-bold';
    if (iso === selStart.value) return base + ' bg-[#2B2D42] text-white font-bold';
    if (selStart.value && selDue.value && selStart.value < selDue.value
        && iso > selStart.value && iso < selDue.value) {
        return base + ' bg-[#EF233C]/10 text-[#EF233C]';
    }
    if (iso === today) return base + ' font-bold text-[#EF233C] hover:bg-gray-100';
    return base + dim + ' hover:bg-gray-100';
}

// ── Save ────────────────────────────────────────────────────────────────────
function save() {
    const due = (dueEnabled.value && dueDate.value)
        ? dayjs(`${dueDate.value}T${dueTime.value || '12:00'}`).toISOString()
        : null;
    emit('save', {
        start_date:   startEnabled.value && startDate.value ? startDate.value : null,
        due_date:     due,
        due_reminder: dueEnabled.value ? (reminder.value || null) : null,
        recurring:    dueEnabled.value ? recurring.value : 'never',
    });
}
</script>
