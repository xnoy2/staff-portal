<template>
    <Link :href="href" class="bg-white rounded-xl border border-gray-200 p-5 flex items-center gap-4 hover:shadow-md transition-shadow group">
        <div :class="['w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0', bgClass]">
            <component :is="iconComponent" :class="['w-6 h-6', iconClass]" />
        </div>
        <div>
            <p class="text-2xl font-bold text-gray-800">{{ value }}</p>
            <p class="text-xs text-gray-500 mt-0.5">{{ label }}</p>
        </div>
    </Link>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import * as HeroOutline from '@heroicons/vue/24/outline';

const props = defineProps({
    label: { type: String, required: true },
    value: { type: [Number, String], required: true },
    icon:  { type: String, required: true },
    color: { type: String, default: 'green' }, // green | blue | amber | red | purple
    href:  { type: String, default: '#' },
});

const colorMap = {
    green:  { bg: 'bg-green-100',  icon: 'text-green-700' },
    blue:   { bg: 'bg-blue-100',   icon: 'text-blue-700' },
    amber:  { bg: 'bg-amber-100',  icon: 'text-amber-700' },
    red:    { bg: 'bg-red-100',    icon: 'text-red-700' },
    purple: { bg: 'bg-purple-100', icon: 'text-purple-700' },
};

const bgClass   = computed(() => colorMap[props.color]?.bg   ?? colorMap.green.bg);
const iconClass = computed(() => colorMap[props.color]?.icon ?? colorMap.green.icon);
const iconComponent = computed(() => HeroOutline[props.icon] ?? HeroOutline.QuestionMarkCircleIcon);
</script>
