<template>
    <BaseModal :open="open" max-width="sm:max-w-sm" @close="$emit('cancel')">
        <!-- Header -->
        <div class="flex items-center gap-3 p-6 border-b border-gray-100">
            <div :class="[
                'w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0',
                danger ? 'bg-red-100' : 'bg-gray-100',
            ]">
                <ExclamationTriangleIcon :class="['w-5 h-5', danger ? 'text-red-500' : 'text-gray-500']" />
            </div>
            <div>
                <h2 class="text-base font-semibold text-gray-800">{{ title }}</h2>
                <p v-if="message" class="text-xs text-gray-500 mt-0.5">{{ message }}</p>
            </div>
        </div>

        <!-- Footer -->
        <div class="flex items-center justify-end gap-2 px-6 py-4">
            <button
                @click="$emit('cancel')"
                class="text-sm text-gray-600 hover:text-gray-800 px-4 py-2 rounded-lg hover:bg-gray-100 transition-colors"
            >Cancel</button>
            <button
                @click="$emit('confirm')"
                :class="[
                    'text-sm font-semibold px-4 py-2 rounded-lg transition-colors',
                    danger
                        ? 'bg-[#EF233C] hover:bg-[#D90429] text-white'
                        : 'bg-gray-800 hover:bg-gray-900 text-white',
                ]"
            >{{ confirmLabel }}</button>
        </div>
    </BaseModal>
</template>

<script setup>
import BaseModal from '@/Components/BaseModal.vue';
import { ExclamationTriangleIcon } from '@heroicons/vue/24/outline';

defineProps({
    open:         { type: Boolean, required: true },
    title:        { type: String,  required: true },
    message:      { type: String,  default: '' },
    confirmLabel: { type: String,  default: 'Confirm' },
    danger:       { type: Boolean, default: false },
});

defineEmits(['confirm', 'cancel']);
</script>
