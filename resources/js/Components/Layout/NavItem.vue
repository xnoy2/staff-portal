<template>
    <Link
        :href="href"
        :class="[
            'flex items-center gap-3 px-2 py-2 rounded-lg text-sm transition-colors group relative',
            isActive
                ? 'bg-[#EF233C] text-white'
                : 'text-[#8D99AE] hover:bg-white/10 hover:text-white',
        ]"
    >
        <component :is="iconComponent" class="w-5 h-5 flex-shrink-0" />
        <span v-if="!collapsed" class="flex-1 truncate">{{ label }}</span>
        <span
            v-if="!collapsed && badge && badge > 0"
            class="ml-auto bg-white text-[#EF233C] text-xs font-bold rounded-full px-1.5 py-0.5 min-w-[20px] text-center"
        >
            {{ badge > 99 ? '99+' : badge }}
        </span>

        <!-- Tooltip when collapsed -->
        <div
            v-if="collapsed"
            class="absolute left-full ml-2 px-2 py-1 bg-[#2B2D42] border border-white/10 text-white text-xs rounded whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-50"
        >
            {{ label }}
            <span v-if="badge && badge > 0" class="ml-1 bg-[#EF233C] rounded-full px-1">{{ badge }}</span>
        </div>
    </Link>
</template>

<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import * as HeroOutline from '@heroicons/vue/24/outline';

const props = defineProps({
    href:      { type: String, required: true },
    icon:      { type: String, required: true },
    label:     { type: String, required: true },
    collapsed: { type: Boolean, default: false },
    badge:     { type: Number, default: 0 },
});

const page = usePage();

const isActive = computed(() => page.url.startsWith(props.href) && props.href !== '/');

const iconComponent = computed(() => HeroOutline[props.icon] ?? HeroOutline.QuestionMarkCircleIcon);
</script>
