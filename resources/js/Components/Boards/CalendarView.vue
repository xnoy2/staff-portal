<template>
    <div class="flex-1 overflow-auto pb-4 flex flex-col">
        <!-- Calendar toolbar -->
        <div class="flex items-center gap-3 mb-3 flex-shrink-0">
            <button @click="shiftMonth(-1)" class="p-1.5 rounded-lg text-gray-500 hover:bg-gray-100" title="Previous month">
                <ChevronLeftIcon class="w-4 h-4" />
            </button>
            <h3 class="text-sm font-bold text-[#2B2D42] min-w-[9rem] text-center">{{ monthLabel }}</h3>
            <button @click="shiftMonth(1)" class="p-1.5 rounded-lg text-gray-500 hover:bg-gray-100" title="Next month">
                <ChevronRightIcon class="w-4 h-4" />
            </button>
            <button @click="goToday" class="text-xs font-semibold text-gray-500 hover:text-[#EF233C] border border-gray-200 rounded-lg px-2.5 py-1.5">Today</button>
            <span class="text-xs text-gray-400 ml-auto">{{ scheduledCount }} scheduled · {{ unscheduled.length }} undated</span>
        </div>

        <!-- Weekday header -->
        <div class="grid grid-cols-7 gap-1 mb-1 flex-shrink-0">
            <div v-for="d in weekdays" :key="d" class="text-[11px] font-bold text-gray-400 uppercase tracking-wide px-2 py-1">{{ d }}</div>
        </div>

        <!-- Day grid -->
        <div class="grid grid-cols-7 gap-1 flex-1 auto-rows-fr min-h-[28rem]">
            <div
                v-for="day in days"
                :key="day.key"
                :class="[
                    'rounded-lg border p-1.5 flex flex-col min-h-[6rem] overflow-hidden',
                    day.inMonth ? 'bg-white border-gray-150' : 'bg-gray-50/60 border-transparent',
                    day.isToday ? 'ring-2 ring-[#EF233C]/40 border-[#EF233C]/30' : 'border-gray-200',
                ]"
            >
                <span :class="['text-[11px] font-semibold mb-1', day.inMonth ? 'text-gray-500' : 'text-gray-300', day.isToday ? 'text-[#EF233C]' : '']">
                    {{ day.date.date() }}
                </span>
                <div class="space-y-1 overflow-y-auto">
                    <button
                        v-for="card in day.cards"
                        :key="card.id"
                        @click="$emit('open-card', card)"
                        :class="['w-full text-left text-[11px] leading-tight px-1.5 py-1 rounded-md truncate font-medium transition-colors', cardChip(card)]"
                        :title="card.title"
                    >
                        {{ card.title }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Undated tray -->
        <div v-if="unscheduled.length" class="mt-3 flex-shrink-0">
            <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wide mb-1.5">No due date</p>
            <div class="flex flex-wrap gap-1.5">
                <button
                    v-for="card in unscheduled"
                    :key="card.id"
                    @click="$emit('open-card', card)"
                    class="text-xs font-medium text-gray-600 bg-[#EDF2F4] hover:bg-gray-200 px-2.5 py-1.5 rounded-lg truncate max-w-[14rem] transition-colors"
                    :title="card.title"
                >
                    {{ card.title }}
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { ChevronLeftIcon, ChevronRightIcon } from '@heroicons/vue/24/outline';
import dayjs from 'dayjs';

const props = defineProps({
    cards: { type: Array, default: () => [] },
});
defineEmits(['open-card']);

const weekdays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
const cursor = ref(dayjs().startOf('month'));

const monthLabel = computed(() => cursor.value.format('MMMM YYYY'));

function shiftMonth(n) { cursor.value = cursor.value.add(n, 'month').startOf('month'); }
function goToday() { cursor.value = dayjs().startOf('month'); }

const scheduled = computed(() => props.cards.filter(c => c.due_date));
const unscheduled = computed(() => props.cards.filter(c => !c.due_date));
const scheduledCount = computed(() => scheduled.value.length);

// Map of YYYY-MM-DD -> cards due that day
const cardsByDay = computed(() => {
    const map = {};
    for (const c of scheduled.value) {
        const key = dayjs(c.due_date).format('YYYY-MM-DD');
        (map[key] ??= []).push(c);
    }
    return map;
});

// 6-week grid starting on the Monday on/before the 1st of the month
const days = computed(() => {
    const first = cursor.value.startOf('month');
    const offset = (first.day() + 6) % 7; // days since Monday
    const start = first.subtract(offset, 'day');
    const today = dayjs();
    const out = [];
    for (let i = 0; i < 42; i++) {
        const date = start.add(i, 'day');
        const key = date.format('YYYY-MM-DD');
        out.push({
            key,
            date,
            inMonth: date.month() === cursor.value.month(),
            isToday: date.isSame(today, 'day'),
            cards: cardsByDay.value[key] ?? [],
        });
    }
    return out;
});

function cardChip(card) {
    if (card.due_done) return 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200';
    const due = dayjs(card.due_date);
    if (due.isBefore(dayjs(), 'day')) return 'bg-red-100 text-red-800 hover:bg-red-200';
    if (due.isSame(dayjs(), 'day')) return 'bg-amber-100 text-amber-800 hover:bg-amber-200';
    return 'bg-sky-100 text-sky-800 hover:bg-sky-200';
}
</script>

<style scoped>
.border-gray-150 { border-color: #eceff2; }
</style>
