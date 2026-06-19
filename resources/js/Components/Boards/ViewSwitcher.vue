<template>
    <div class="relative" ref="root">
        <button
            @click="open = !open"
            class="flex items-center gap-1.5 text-sm font-semibold text-gray-600 hover:text-[#2B2D42] bg-gray-100 hover:bg-gray-200 px-2.5 py-1.5 rounded-lg transition-colors"
            title="Switch view"
        >
            <component :is="current.icon" class="w-4 h-4" />
            <span class="hidden sm:inline">{{ current.label }}</span>
            <ChevronDownIcon class="w-3.5 h-3.5 text-gray-400" />
        </button>

        <Transition name="vs">
            <div
                v-if="open"
                class="absolute left-0 mt-1.5 w-56 bg-white rounded-xl shadow-xl border border-gray-100 py-1.5 z-30"
            >
                <p class="px-3 py-1 text-[11px] font-bold text-gray-400 uppercase tracking-wide">Views</p>
                <button
                    v-for="v in views"
                    :key="v.key"
                    @click="select(v.key)"
                    :class="[
                        'w-full flex items-center gap-2.5 px-3 py-2 text-sm transition-colors',
                        v.key === modelValue ? 'text-[#EF233C] font-semibold bg-[#EF233C]/5' : 'text-gray-700 hover:bg-gray-50',
                    ]"
                >
                    <component :is="v.icon" class="w-4 h-4" />
                    {{ v.label }}
                    <CheckIcon v-if="v.key === modelValue" class="w-4 h-4 ml-auto" />
                </button>
            </div>
        </Transition>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import {
    ViewColumnsIcon, TableCellsIcon, CalendarDaysIcon,
    ChartBarIcon, ChevronDownIcon, CheckIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    modelValue: { type: String, default: 'board' },
});
const emit = defineEmits(['update:modelValue']);

const views = [
    { key: 'board',    label: 'Board',    icon: ViewColumnsIcon },
    { key: 'table',    label: 'Table',    icon: TableCellsIcon },
    { key: 'calendar', label: 'Calendar', icon: CalendarDaysIcon },
    { key: 'timeline', label: 'Timeline', icon: ChartBarIcon },
];

const current = computed(() => views.find(v => v.key === props.modelValue) ?? views[0]);

const open = ref(false);
const root = ref(null);

function select(key) {
    emit('update:modelValue', key);
    open.value = false;
}

function onClickOutside(e) {
    if (open.value && root.value && !root.value.contains(e.target)) open.value = false;
}
onMounted(() => document.addEventListener('click', onClickOutside));
onBeforeUnmount(() => document.removeEventListener('click', onClickOutside));
</script>

<style scoped>
.vs-enter-active, .vs-leave-active { transition: opacity .12s ease, transform .12s ease; }
.vs-enter-from, .vs-leave-to { opacity: 0; transform: translateY(-4px); }
</style>
