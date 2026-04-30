<template>
    <div class="mb-1">
        <p
            v-if="!collapsed"
            class="px-2 mb-1 text-xs font-semibold text-[#8D99AE] uppercase tracking-wider"
        >
            {{ label }}
        </p>
        <div v-else class="border-t border-white/10 my-2" />
        <slot />
    </div>
</template>

<script setup>
defineProps({
    label:     { type: String, required: true },
    collapsed: { type: Boolean, default: false },
});
</script>
