<template>
    <BaseModal :open="!!password" max-width="sm:max-w-md" @close="dismiss">
        <!-- Header -->
        <div class="flex items-center gap-3 p-6 border-b border-gray-100">
            <div class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center flex-shrink-0">
                <KeyIcon class="w-5 h-5 text-amber-600" />
            </div>
            <div>
                <h2 class="text-base font-semibold text-gray-800">Temporary Password Generated</h2>
                <p class="text-xs text-gray-500 mt-0.5">Share this with the new staff member</p>
            </div>
        </div>

        <!-- Body -->
        <div class="p-6 space-y-4">
            <p class="text-sm text-gray-600">
                A temporary password has been automatically generated. The staff member will be required to change it on their first login.
            </p>

            <div class="bg-gray-50 border border-gray-200 rounded-xl p-4">
                <p class="text-xs text-gray-400 mb-1.5 font-medium uppercase tracking-wide">Temporary Password</p>
                <div class="flex items-center gap-3">
                    <code class="flex-1 font-mono text-lg font-semibold text-gray-800 tracking-widest select-all break-all">
                        {{ password }}
                    </code>
                    <button
                        @click="copy"
                        :class="[
                            'flex-shrink-0 flex items-center gap-1.5 text-xs px-3 py-1.5 rounded-lg transition-colors font-medium',
                            copied ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-200 hover:bg-gray-300 text-gray-700',
                        ]"
                    >
                        <component :is="copied ? CheckIcon : ClipboardDocumentIcon" class="w-3.5 h-3.5" />
                        {{ copied ? 'Copied!' : 'Copy' }}
                    </button>
                </div>
            </div>

            <p class="text-xs text-amber-600 flex items-start gap-1.5">
                <ExclamationTriangleIcon class="w-3.5 h-3.5 flex-shrink-0 mt-0.5" />
                This password will not be shown again. Copy it before closing.
            </p>
        </div>

        <!-- Footer -->
        <div class="flex justify-end px-6 pb-6">
            <button
                @click="dismiss"
                class="bg-[#EF233C] hover:bg-[#D90429] text-white text-sm font-medium px-6 py-2.5 rounded-lg transition-colors"
            >
                Done
            </button>
        </div>
    </BaseModal>
</template>

<script setup>
import { ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import BaseModal from '@/Components/BaseModal.vue';
import {
    KeyIcon,
    ClipboardDocumentIcon,
    CheckIcon,
    ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline';

const page     = usePage();
const password = ref(null);
const copied   = ref(false);

watch(
    () => page.props.flash?.temp_password,
    (val) => { if (val) password.value = val; },
    { immediate: true },
);

function copy() {
    if (!password.value) return;
    navigator.clipboard.writeText(password.value).then(() => {
        copied.value = true;
        setTimeout(() => { copied.value = false; }, 2500);
    });
}

function dismiss() {
    password.value = null;
}
</script>
