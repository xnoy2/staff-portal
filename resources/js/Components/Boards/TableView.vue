<template>
    <div class="flex-1 overflow-auto pb-4">
        <table class="w-full text-sm border-separate border-spacing-0">
            <thead class="sticky top-0 z-10">
                <tr class="bg-[#EDF2F4] text-left text-xs font-bold text-gray-500 uppercase tracking-wide">
                    <th
                        v-for="col in columns"
                        :key="col.key"
                        @click="toggleSort(col.key)"
                        :class="['px-3 py-2.5 select-none border-b border-gray-200', col.sortable ? 'cursor-pointer hover:text-gray-700' : '', col.class]"
                    >
                        <span class="inline-flex items-center gap-1">
                            {{ col.label }}
                            <template v-if="col.sortable && sortKey === col.key">
                                <ChevronUpIcon v-if="sortDir === 'asc'" class="w-3 h-3" />
                                <ChevronDownIcon v-else class="w-3 h-3" />
                            </template>
                        </span>
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="card in sortedCards"
                    :key="card.id"
                    @click="$emit('open-card', card)"
                    class="cursor-pointer hover:bg-gray-50 transition-colors group"
                >
                    <!-- Title -->
                    <td class="px-3 py-2.5 border-b border-gray-100">
                        <div class="flex items-center gap-2">
                            <span class="font-medium text-gray-800 group-hover:text-[#2B2D42]">{{ card.title }}</span>
                            <Bars3BottomLeftIcon v-if="card.description" class="w-3.5 h-3.5 text-gray-300 flex-shrink-0" title="Has description" />
                        </div>
                    </td>
                    <!-- List -->
                    <td class="px-3 py-2.5 border-b border-gray-100">
                        <span class="inline-flex items-center text-xs font-semibold text-gray-600 bg-gray-100 rounded-full px-2 py-0.5">{{ card.listName }}</span>
                    </td>
                    <!-- Labels -->
                    <td class="px-3 py-2.5 border-b border-gray-100">
                        <div v-if="card.labels.length" class="flex flex-wrap gap-1">
                            <span
                                v-for="l in card.labels"
                                :key="l.id"
                                :class="['h-2 rounded-full', l.name ? 'px-2 h-4 text-[9px] font-bold flex items-center' : 'w-7', labelClass(l.color)]"
                            >{{ l.name }}</span>
                        </div>
                        <span v-else class="text-gray-300">—</span>
                    </td>
                    <!-- Due date -->
                    <td class="px-3 py-2.5 border-b border-gray-100">
                        <span v-if="card.due_date" :class="['inline-flex items-center gap-1 text-[11px] font-semibold px-1.5 py-0.5 rounded', dueClass(card)]">
                            <ClockIcon class="w-3 h-3" /> {{ shortDue(card.due_date) }}
                        </span>
                        <span v-else class="text-gray-300">—</span>
                    </td>
                    <!-- Checklist -->
                    <td class="px-3 py-2.5 border-b border-gray-100">
                        <span v-if="card.checklist_total > 0" :class="[
                            'inline-flex items-center gap-1 text-[11px] font-semibold px-1.5 py-0.5 rounded',
                            card.checklist_done === card.checklist_total ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500',
                        ]">
                            <CheckCircleIcon class="w-3 h-3" /> {{ card.checklist_done }}/{{ card.checklist_total }}
                        </span>
                        <span v-else class="text-gray-300">—</span>
                    </td>
                    <!-- Activity -->
                    <td class="px-3 py-2.5 border-b border-gray-100">
                        <div class="flex items-center gap-2.5 text-gray-400">
                            <span v-if="card.attachments.length" class="inline-flex items-center gap-0.5 text-[11px]">
                                <PaperClipIcon class="w-3.5 h-3.5" />{{ card.attachments.length }}
                            </span>
                            <span v-if="card.comments.length" class="inline-flex items-center gap-0.5 text-[11px]">
                                <ChatBubbleLeftRightIcon class="w-3.5 h-3.5" />{{ card.comments.length }}
                            </span>
                            <span v-if="!card.attachments.length && !card.comments.length" class="text-gray-300">—</span>
                        </div>
                    </td>
                </tr>
                <tr v-if="!cards.length">
                    <td :colspan="columns.length" class="px-3 py-10 text-center text-sm text-gray-400 border-b border-gray-100">
                        No cards on this board yet.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import {
    ClockIcon, CheckCircleIcon, Bars3BottomLeftIcon, PaperClipIcon,
    ChatBubbleLeftRightIcon, ChevronUpIcon, ChevronDownIcon,
} from '@heroicons/vue/24/outline';
import dayjs from 'dayjs';
import { labelClass, dueClass, shortDue } from './cardHelpers';

const props = defineProps({
    cards: { type: Array, default: () => [] },
});
defineEmits(['open-card']);

const columns = [
    { key: 'title',     label: 'Card',      sortable: true,  class: 'w-[34%]' },
    { key: 'listName',  label: 'List',      sortable: true },
    { key: 'labels',    label: 'Labels',    sortable: false },
    { key: 'due_date',  label: 'Due',       sortable: true },
    { key: 'checklist', label: 'Checklist', sortable: false },
    { key: 'activity',  label: 'Activity',  sortable: false },
];

const sortKey = ref('listName');
const sortDir = ref('asc');

function toggleSort(key) {
    const col = columns.find(c => c.key === key);
    if (!col?.sortable) return;
    if (sortKey.value === key) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortKey.value = key;
        sortDir.value = 'asc';
    }
}

function valueFor(card, key) {
    if (key === 'due_date') return card.due_date ? dayjs(card.due_date).valueOf() : Infinity;
    return (card[key] ?? '').toString().toLowerCase();
}

const sortedCards = computed(() => {
    const arr = [...props.cards];
    arr.sort((a, b) => {
        const av = valueFor(a, sortKey.value);
        const bv = valueFor(b, sortKey.value);
        if (av < bv) return sortDir.value === 'asc' ? -1 : 1;
        if (av > bv) return sortDir.value === 'asc' ? 1 : -1;
        return 0;
    });
    return arr;
});
</script>
