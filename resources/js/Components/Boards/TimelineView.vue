<template>
    <div class="flex-1 overflow-auto pb-4">
        <div v-if="!scheduled.length && !unscheduled.length" class="px-3 py-10 text-center text-sm text-gray-400">
            No cards on this board yet.
        </div>

        <template v-else>
            <!-- Gantt -->
            <div v-if="scheduled.length" class="inline-flex min-w-full border border-gray-200 rounded-xl overflow-hidden">
                <!-- Left: card/list labels -->
                <div class="flex-shrink-0 w-56 bg-[#EDF2F4] border-r border-gray-200">
                    <div class="h-9 border-b border-gray-200 flex items-center px-3 text-[11px] font-bold text-gray-500 uppercase tracking-wide">Card</div>
                    <template v-for="group in groups" :key="group.listId">
                        <div class="px-3 py-1.5 bg-gray-200/60 text-[11px] font-bold text-gray-600 truncate">{{ group.listName }}</div>
                        <div
                            v-for="row in group.rows"
                            :key="row.id"
                            class="h-9 flex items-center px-3 text-xs text-gray-700 truncate border-b border-gray-100"
                            :title="row.title"
                        >
                            {{ row.title }}
                        </div>
                    </template>
                </div>

                <!-- Right: scrollable timeline -->
                <div class="flex-1 overflow-x-auto">
                    <div :style="{ width: totalWidth + 'px' }" class="relative">
                        <!-- Axis -->
                        <div class="h-9 border-b border-gray-200 relative">
                            <div
                                v-for="tick in ticks"
                                :key="tick.key"
                                class="absolute top-0 h-full border-l border-gray-150 flex items-center pl-1"
                                :style="{ left: tick.left + 'px' }"
                            >
                                <span class="text-[10px] font-semibold text-gray-400 whitespace-nowrap">{{ tick.label }}</span>
                            </div>
                        </div>

                        <!-- Rows -->
                        <template v-for="group in groups" :key="group.listId">
                            <!-- list subheader spacer (matches left column) -->
                            <div class="h-[26px] bg-gray-200/30 relative">
                                <div
                                    v-for="tick in ticks"
                                    :key="tick.key"
                                    class="absolute top-0 h-full border-l border-gray-100"
                                    :style="{ left: tick.left + 'px' }"
                                />
                            </div>
                            <div
                                v-for="row in group.rows"
                                :key="row.id"
                                class="h-9 relative border-b border-gray-100"
                            >
                                <!-- gridlines -->
                                <div
                                    v-for="tick in ticks"
                                    :key="tick.key"
                                    class="absolute top-0 h-full border-l border-gray-100"
                                    :style="{ left: tick.left + 'px' }"
                                />
                                <!-- today marker -->
                                <div v-if="todayLeft !== null" class="absolute top-0 h-full border-l-2 border-[#EF233C]/40 z-10" :style="{ left: todayLeft + 'px' }" />
                                <!-- bar -->
                                <button
                                    @click="$emit('open-card', row.card)"
                                    :class="['absolute top-1.5 h-6 rounded-md flex items-center px-2 text-[11px] font-medium truncate shadow-sm transition-all hover:brightness-95', barClass(row.card)]"
                                    :style="{ left: row.left + 'px', width: row.width + 'px' }"
                                    :title="`${row.title} — due ${row.dueLabel}`"
                                >
                                    {{ row.title }}
                                </button>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Undated -->
            <div v-if="unscheduled.length" class="mt-3">
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
        </template>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import dayjs from 'dayjs';

const props = defineProps({
    // cards already flattened with listName/listId
    cards: { type: Array, default: () => [] },
});
defineEmits(['open-card']);

const PX_PER_DAY = 26;
const MIN_BAR = 26;

const scheduled = computed(() => props.cards.filter(c => c.due_date));
const unscheduled = computed(() => props.cards.filter(c => !c.due_date));

// Bar span = created_at → due_date (normalised so start <= end).
function span(card) {
    const due = dayjs(card.due_date).startOf('day');
    const created = card.created_at ? dayjs(card.created_at).startOf('day') : due;
    const start = created.isBefore(due) ? created : due;
    const end = created.isBefore(due) ? due : created;
    return { start, end };
}

const range = computed(() => {
    if (!scheduled.value.length) return null;
    let min = null, max = null;
    for (const c of scheduled.value) {
        const { start, end } = span(c);
        if (!min || start.isBefore(min)) min = start;
        if (!max || end.isAfter(max)) max = end;
    }
    // pad a few days on each side for breathing room
    return { start: min.subtract(2, 'day'), end: max.add(2, 'day') };
});

const totalDays = computed(() => range.value ? range.value.end.diff(range.value.start, 'day') + 1 : 0);
const totalWidth = computed(() => Math.max(totalDays.value * PX_PER_DAY, 600));

function leftFor(date) {
    return date.startOf('day').diff(range.value.start, 'day') * PX_PER_DAY;
}

const todayLeft = computed(() => {
    if (!range.value) return null;
    const t = dayjs().startOf('day');
    if (t.isBefore(range.value.start) || t.isAfter(range.value.end)) return null;
    return leftFor(t);
});

// Weekly axis ticks
const ticks = computed(() => {
    if (!range.value) return [];
    const out = [];
    let d = range.value.start.startOf('day');
    // align first tick to a Monday for tidy labels
    const offset = (d.day() + 6) % 7;
    d = d.subtract(offset, 'day');
    while (d.isBefore(range.value.end) || d.isSame(range.value.end, 'day')) {
        out.push({ key: d.format('YYYY-MM-DD'), left: leftFor(d), label: d.format('D MMM') });
        d = d.add(7, 'day');
    }
    return out;
});

// Group cards by list, preserving list order from input
const groups = computed(() => {
    const order = [];
    const byList = {};
    for (const c of scheduled.value) {
        if (!byList[c.listId]) {
            byList[c.listId] = { listId: c.listId, listName: c.listName, rows: [] };
            order.push(c.listId);
        }
        const { start, end } = span(c);
        byList[c.listId].rows.push({
            id: c.id,
            card: c,
            title: c.title,
            left: leftFor(start),
            width: Math.max((end.diff(start, 'day') + 1) * PX_PER_DAY, MIN_BAR),
            dueLabel: dayjs(c.due_date).format('D MMM YYYY'),
        });
    }
    return order.map(id => byList[id]);
});

function barClass(card) {
    if (card.due_done) return 'bg-emerald-200 text-emerald-900';
    const due = dayjs(card.due_date);
    if (due.isBefore(dayjs(), 'day')) return 'bg-red-200 text-red-900';
    if (due.isSame(dayjs(), 'day')) return 'bg-amber-200 text-amber-900';
    return 'bg-sky-200 text-sky-900';
}
</script>

<style scoped>
.border-gray-150 { border-color: #e5e8ec; }
</style>
