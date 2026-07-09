<template>
    <AppLayout :title="agreement.title">
        <div class="max-w-3xl mx-auto px-4 py-6">

            <!-- Toolbar (not printed) -->
            <div class="no-print flex items-center justify-between mb-4">
                <button @click="goBack" class="text-sm text-gray-500 hover:text-gray-800 inline-flex items-center gap-1">
                    <ArrowLeftIcon class="w-4 h-4" /> Back
                </button>
                <button v-if="agreement.status === 'acknowledged'" @click="print"
                    class="text-sm bg-gray-800 text-white px-4 py-2 rounded-lg font-medium inline-flex items-center gap-1.5">
                    <PrinterIcon class="w-4 h-4" /> Print / Save PDF
                </button>
            </div>

            <!-- Document -->
            <div id="doc" class="bg-white border border-gray-200 rounded-2xl shadow-sm px-8 py-8 sm:px-12 sm:py-10">
                <div class="flex items-start justify-between gap-4 border-b border-gray-200 pb-5 mb-6">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-[#EF233C]">{{ agreement.type_label }}</p>
                        <h1 class="text-xl font-bold text-gray-900 mt-1">{{ agreement.title }}</h1>
                    </div>
                    <span v-if="agreement.status === 'acknowledged'" class="shrink-0 text-xs font-semibold px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">Signed</span>
                    <span v-else class="shrink-0 text-xs font-semibold px-2.5 py-1 rounded-full bg-amber-50 text-amber-700 border border-amber-200">Awaiting signature</span>
                </div>

                <!-- Meta -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm mb-6">
                    <div>
                        <p class="text-[11px] uppercase tracking-wide text-gray-400">Employee</p>
                        <p class="font-medium text-gray-800">{{ agreement.employee_name }}</p>
                    </div>
                    <div v-if="agreement.employee_id">
                        <p class="text-[11px] uppercase tracking-wide text-gray-400">Employee ID</p>
                        <p class="font-medium text-gray-800">{{ agreement.employee_id }}</p>
                    </div>
                    <div>
                        <p class="text-[11px] uppercase tracking-wide text-gray-400">Issued</p>
                        <p class="font-medium text-gray-800">{{ agreement.issued_at }}</p>
                    </div>
                    <div>
                        <p class="text-[11px] uppercase tracking-wide text-gray-400">Version</p>
                        <p class="font-medium text-gray-800">v{{ agreement.version }}</p>
                    </div>
                </div>

                <!-- Body -->
                <div class="prose-terms text-[13.5px] leading-relaxed text-gray-700 whitespace-pre-wrap">{{ agreement.body }}</div>

                <!-- Signature block (signed) -->
                <div v-if="agreement.status === 'acknowledged'" class="mt-8 pt-5 border-t border-gray-200">
                    <p class="text-[11px] uppercase tracking-wide text-gray-400 mb-1">Electronically signed by</p>
                    <p class="text-lg text-gray-900" style="font-family: 'Segoe Script','Brush Script MT',cursive;">{{ agreement.acknowledged_name }}</p>
                    <p class="text-xs text-gray-500 mt-1">
                        {{ agreement.acknowledged_at }}<span v-if="agreement.acknowledged_ip"> · IP {{ agreement.acknowledged_ip }}</span>
                    </p>
                </div>

                <!-- Sign form (owner, pending) -->
                <div v-else-if="canSign" class="no-print mt-8 pt-5 border-t border-gray-200">
                    <form @submit.prevent="sign" class="space-y-3">
                        <label class="flex items-start gap-2 text-sm text-gray-700">
                            <input v-model="form.agree" type="checkbox" class="mt-0.5 rounded border-gray-300 text-[#EF233C] focus:ring-[#EF233C]" />
                            <span>I have read, understood and agree to be bound by the terms set out above.</span>
                        </label>
                        <div>
                            <label class="block text-[11px] font-medium text-gray-500 mb-1">Type your full name to sign</label>
                            <input v-model="form.full_name" type="text" :placeholder="agreement.employee_name"
                                class="w-full sm:w-80 text-sm rounded-lg border-gray-200 focus:border-[#EF233C] focus:ring-[#EF233C]" />
                            <p v-if="form.errors.full_name" class="text-xs text-red-600 mt-1">{{ form.errors.full_name }}</p>
                            <p v-if="form.errors.agree" class="text-xs text-red-600 mt-1">You must tick the box to agree.</p>
                        </div>
                        <button type="submit" :disabled="!form.agree || !form.full_name || form.processing"
                            class="text-sm bg-[#EF233C] text-white px-5 py-2 rounded-lg font-medium disabled:opacity-40">
                            {{ form.processing ? 'Signing…' : 'Sign agreement' }}
                        </button>
                    </form>
                </div>

                <!-- Pending, viewed by a manager -->
                <div v-else class="mt-8 pt-5 border-t border-gray-200">
                    <p class="text-sm text-amber-700">This agreement is awaiting the employee's signature.</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ArrowLeftIcon, PrinterIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    agreement: { type: Object, required: true },
    canSign:   { type: Boolean, default: false },
    isOwner:   { type: Boolean, default: false },
});

const form = useForm({ agree: false, full_name: '' });

function sign() {
    form.post(route('agreements.acknowledge', props.agreement.id), { preserveScroll: true });
}

function print() { window.print(); }
function goBack() { window.history.length > 1 ? window.history.back() : router.visit(route('dashboard')); }
</script>

<style>
@media print {
    body * { visibility: hidden; }
    #doc, #doc * { visibility: visible; }
    #doc { position: absolute; left: 0; top: 0; width: 100%; border: none; box-shadow: none; border-radius: 0; }
    .no-print { display: none !important; }
    @page { size: A4 portrait; margin: 16mm; }
}
</style>
