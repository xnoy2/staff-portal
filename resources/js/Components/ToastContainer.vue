<template>
    <div class="fixed bottom-4 right-4 z-50 flex flex-col-reverse gap-2 pointer-events-none">
        <TransitionGroup name="toast">
            <div
                v-for="toast in toasts"
                :key="toast.id"
                :class="[
                    'pointer-events-auto flex items-start gap-3 rounded-xl shadow-lg border px-4 py-3 w-80',
                    config[toast.type].wrapper,
                ]"
            >
                <component :is="config[toast.type].icon" :class="['w-5 h-5 flex-shrink-0 mt-0.5', config[toast.type].iconClass]" />
                <p class="flex-1 text-sm leading-snug">{{ toast.message }}</p>
                <button
                    @click="remove(toast.id)"
                    :class="['flex-shrink-0 opacity-50 hover:opacity-100 transition-opacity', config[toast.type].iconClass]"
                >
                    <XMarkIcon class="w-4 h-4" />
                </button>
            </div>
        </TransitionGroup>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import {
    CheckCircleIcon,
    XCircleIcon,
    ExclamationTriangleIcon,
    InformationCircleIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline';

const config = {
    success: {
        wrapper:   'bg-white border-emerald-200 text-emerald-800',
        iconClass: 'text-emerald-500',
        icon:      CheckCircleIcon,
    },
    error: {
        wrapper:   'bg-white border-red-200 text-red-800',
        iconClass: 'text-red-500',
        icon:      XCircleIcon,
    },
    warning: {
        wrapper:   'bg-white border-amber-200 text-amber-800',
        iconClass: 'text-amber-500',
        icon:      ExclamationTriangleIcon,
    },
    info: {
        wrapper:   'bg-white border-blue-200 text-blue-800',
        iconClass: 'text-blue-500',
        icon:      InformationCircleIcon,
    },
};

const page  = usePage();
const toasts = ref([]);
let counter  = 0;

// Replace any ISO-8601 timestamps in flash messages with browser-local time
const ISO_RE = /\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?Z/g;
function localiseIso(msg) {
    return msg.replace(ISO_RE, (iso) =>
        new Date(iso).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
    );
}

function add(type, message) {
    if (!message) return;
    const id = ++counter;
    toasts.value.push({ id, type, message: localiseIso(message) });
    setTimeout(() => remove(id), 4500);
}

function remove(id) {
    toasts.value = toasts.value.filter(t => t.id !== id);
}

watch(
    () => page.props.flash,
    (flash) => {
        if (!flash) return;
        if (flash.success) add('success', flash.success);
        if (flash.error)   add('error',   flash.error);
        if (flash.warning) add('warning', flash.warning);
        if (flash.info)    add('info',    flash.info);
    },
    { immediate: true, deep: true },
);
</script>

<style scoped>
.toast-enter-active,
.toast-leave-active { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
.toast-enter-from,
.toast-leave-to     { transform: translateX(110%); opacity: 0; }
</style>
