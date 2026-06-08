<template>
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <!-- Status banner -->
        <div :class="['px-5 py-2.5 flex items-center justify-between transition-colors duration-500', headerBg]">
            <div class="flex items-center gap-2">
                <span :class="['w-2 h-2 rounded-full transition-colors', clockState === 'idle' ? 'bg-gray-400' : 'bg-white animate-pulse']" />
                <span :class="['text-xs font-semibold', clockState === 'idle' ? 'text-gray-500' : 'text-white']">
                    {{ headerLabel }}
                </span>
                <span v-if="activeEntry?.ot_type" class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-white/20 text-white uppercase tracking-wide">
                    {{ activeEntry.ot_type === 'rdot' ? 'RDOT' : 'OT' }}
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

            <!-- OT Mode selector (only when idle) -->
            <div v-if="clockState === 'idle'" class="flex items-center justify-center gap-1 bg-gray-100 rounded-xl p-1 mb-4">
                <button
                    v-for="opt in otOpts"
                    :key="opt.value"
                    @click="otMode = opt.value"
                    :class="[
                        'flex-1 text-xs font-semibold px-3 py-1.5 rounded-lg transition-colors',
                        otMode === opt.value ? opt.activeClass : 'text-gray-500 hover:text-gray-700',
                    ]"
                >{{ opt.label }}</button>
            </div>

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
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { PlayCircleIcon, StopCircleIcon, PauseCircleIcon } from '@heroicons/vue/24/solid';
import { ClockIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    activeEntry:     { type: Object, default: null },
    todayApprovedOt: { type: String, default: null },
});

const now = ref(new Date());
let clockInterval = null;
onMounted(() => { clockInterval = setInterval(() => { now.value = new Date(); }, 1000); });
onUnmounted(() => { clearInterval(clockInterval); });

const formattedClock = computed(() =>
    now.value.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' })
);
const currentDate = computed(() =>
    now.value.toLocaleDateString('en-GB', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })
);
const clockInTime = computed(() => {
    if (!props.activeEntry?.clock_in) return '';
    return new Date(props.activeEntry.clock_in).toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });
});
const clockState = computed(() => props.activeEntry?.clock_state ?? 'idle');

const workedSeconds = computed(() => {
    if (!props.activeEntry) return 0;
    const clockIn      = new Date(props.activeEntry.clock_in).getTime();
    const pastBreaksMs = (props.activeEntry.total_break_minutes ?? 0) * 60_000;
    const endpoint     = props.activeEntry.active_break
        ? new Date(props.activeEntry.active_break.started_at).getTime()
        : now.value.getTime();
    return Math.max(0, Math.floor((endpoint - clockIn - pastBreaksMs) / 1000));
});

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

const otOpts = [
    { value: 'regular', label: 'Regular', activeClass: 'bg-white text-gray-800 shadow-sm' },
    { value: 'ot',      label: 'OT',      activeClass: 'bg-amber-500 text-white shadow-sm' },
    { value: 'rdot',    label: 'RDOT',    activeClass: 'bg-purple-600 text-white shadow-sm' },
];
const otMode = ref(props.todayApprovedOt ?? 'regular');

const clockLoading = ref(false);
const breakLoading = ref(false);

function handleCenterClick() {
    if (clockState.value === 'idle') {
        clockLoading.value = true;
        const payload = otMode.value !== 'regular' ? { ot_type: otMode.value } : {};
        router.post('/attendance/clock-in', payload, { preserveScroll: true, onFinish: () => { clockLoading.value = false; } });
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
</script>

<style scoped>
@keyframes ping-ring {
    0%   { transform: scale(1);   opacity: 0.45; }
    100% { transform: scale(2.4); opacity: 0;    }
}
.clock-ring-1 { animation: ping-ring 2s ease-out infinite; }
.clock-ring-2 { animation: ping-ring 2s ease-out infinite 0.75s; }

@keyframes heartbeat {
    0%,  100% { transform: scale(1);    }
    12%        { transform: scale(1.07); }
    24%        { transform: scale(1);    }
    38%        { transform: scale(1.05); }
    55%        { transform: scale(1);    }
}
.clock-heartbeat { animation: heartbeat 1.5s ease-in-out infinite; }

@keyframes breathe-glow {
    0%,  100% { box-shadow: 0 8px 30px -4px rgba(239, 35, 60, 0.35); }
    50%        { box-shadow: 0 8px 50px  4px rgba(239, 35, 60, 0.65); }
}
.clock-breathe { animation: breathe-glow 2.6s ease-in-out infinite; }
</style>
