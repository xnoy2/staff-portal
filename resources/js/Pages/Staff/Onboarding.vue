<template>
    <AppLayout :title="`Onboarding — ${staffMember.name}`">
        <div class="max-w-3xl mx-auto space-y-5 pb-12">

            <!-- Header -->
            <div class="bg-white rounded-xl border border-gray-200 p-5 flex items-center justify-between gap-4 print:hidden">
                <div class="flex items-center gap-3">
                    <Link :href="route('staff.show', staffMember.id)" class="text-gray-400 hover:text-gray-600">
                        <ArrowLeftIcon class="w-5 h-5" />
                    </Link>
                    <div>
                        <h1 class="text-base font-bold text-gray-800">Onboarding Form</h1>
                        <p class="text-xs text-gray-500">{{ staffMember.name }} · {{ staffMember.employee_id }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span v-if="form?.updated_at" class="text-xs text-gray-400">
                        Last saved {{ formatDate(form.updated_at) }}
                    </span>
                    <button
                        type="button"
                        @click="printForm"
                        class="inline-flex items-center gap-1.5 border border-gray-200 text-gray-600 text-sm px-3 py-1.5 rounded-lg hover:bg-gray-50 transition-colors"
                    >
                        <PrinterIcon class="w-4 h-4" /> Print
                    </button>
                    <button
                        v-if="canEdit"
                        type="button"
                        @click="save"
                        :disabled="saving"
                        class="inline-flex items-center gap-1.5 bg-[#EF233C] text-white text-sm px-3 py-1.5 rounded-lg hover:bg-[#D90429] transition-colors disabled:opacity-60"
                    >
                        <CheckIcon class="w-4 h-4" />
                        {{ saving ? 'Saving…' : 'Save' }}
                    </button>
                </div>
            </div>

            <!-- Print header (hidden on screen) -->
            <div class="hidden print:block text-center mb-6">
                <h1 class="text-2xl font-bold uppercase tracking-widest">Staff Onboarding Form</h1>
                <p class="text-sm text-gray-500 mt-1">{{ staffMember.name }} &nbsp;|&nbsp; {{ staffMember.employee_id }}</p>
            </div>

            <!-- Flash -->
            <div v-if="$page.props.flash?.success" class="bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm px-4 py-3 rounded-lg print:hidden">
                {{ $page.props.flash.success }}
            </div>

            <!-- ── Employee Details ──────────────────────────────────────── -->
            <FormSection title="Employee Details">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <FormField label="Full Name">
                        <input type="text" :value="staffMember.name" disabled
                            class="field bg-gray-50 text-gray-500 cursor-not-allowed" />
                    </FormField>
                    <FormField label="Email">
                        <input type="email" :value="staffMember.email" disabled
                            class="field bg-gray-50 text-gray-500 cursor-not-allowed" />
                    </FormField>
                    <FormField label="Address" class="sm:col-span-2">
                        <textarea v-model="f.address" :disabled="!canEdit" rows="2" class="field resize-none" />
                    </FormField>
                    <FormField label="Phone Number">
                        <input type="tel" v-model="f.phone" :disabled="!canEdit" class="field" />
                    </FormField>
                    <FormField label="National Insurance Number">
                        <input type="text" v-model="f.national_insurance" :disabled="!canEdit" class="field uppercase" placeholder="e.g. AB 12 34 56 C" />
                    </FormField>
                    <FormField label="Emergency Contact" class="sm:col-span-2">
                        <input type="text" v-model="f.emergency_contact" :disabled="!canEdit" class="field"
                            :placeholder="`${staffMember.emergency_contact_name ?? ''} ${staffMember.emergency_contact_phone ?? ''}`.trim() || 'Name & phone number'" />
                    </FormField>
                </div>
            </FormSection>

            <!-- ── Job Information ──────────────────────────────────────── -->
            <FormSection title="Job Information">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <FormField label="Position">
                        <input type="text" v-model="f.position" :disabled="!canEdit" class="field" />
                    </FormField>
                    <FormField label="Start Date">
                        <input type="date" v-model="f.start_date" :disabled="!canEdit" class="field"
                            :placeholder="staffMember.hire_date ?? ''" />
                    </FormField>
                    <FormField label="Supervisor / Manager">
                        <input type="text" v-model="f.supervisor" :disabled="!canEdit" class="field" />
                    </FormField>
                    <FormField label="Employment Type">
                        <select v-model="f.employment_type" :disabled="!canEdit" class="field">
                            <option value="">— Select —</option>
                            <option value="full_time">Full Time</option>
                            <option value="part_time">Part Time</option>
                            <option value="self_employed">Self-Employed</option>
                        </select>
                    </FormField>
                </div>
            </FormSection>

            <!-- ── Experience ───────────────────────────────────────────── -->
            <FormSection title="Experience">
                <div class="space-y-4">
                    <FormField label="Previous Construction Experience">
                        <textarea v-model="f.previous_experience" :disabled="!canEdit" rows="3" class="field resize-none" />
                    </FormField>
                    <FormField label="Tickets / Qualifications">
                        <textarea v-model="f.qualifications" :disabled="!canEdit" rows="2" class="field resize-none" placeholder="CSCS, First Aid, IPAF, etc." />
                    </FormField>
                    <div class="grid grid-cols-2 gap-4">
                        <FormField label="Driving Licence">
                            <div class="flex gap-4 mt-1">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" :value="true"  v-model="f.driving_licence" :disabled="!canEdit" class="accent-[#EF233C]" /> Yes
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" :value="false" v-model="f.driving_licence" :disabled="!canEdit" class="accent-[#EF233C]" /> No
                                </label>
                            </div>
                        </FormField>
                        <FormField label="Own Transport">
                            <div class="flex gap-4 mt-1">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" :value="true"  v-model="f.own_transport" :disabled="!canEdit" class="accent-[#EF233C]" /> Yes
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" :value="false" v-model="f.own_transport" :disabled="!canEdit" class="accent-[#EF233C]" /> No
                                </label>
                            </div>
                        </FormField>
                    </div>
                </div>
            </FormSection>

            <!-- ── Medical Information ──────────────────────────────────── -->
            <FormSection title="Medical Information">
                <p class="text-xs text-gray-500 mb-3">
                    Please disclose any medical conditions, allergies, injuries, epilepsy, or health concerns relevant to your role or site safety.
                </p>
                <textarea v-model="f.medical_information" :disabled="!canEdit" rows="4" class="field resize-none w-full" />
            </FormSection>

            <!-- ── Criminal Offences ────────────────────────────────────── -->
            <FormSection title="Criminal Offences">
                <p class="text-sm font-medium text-gray-700 mb-2">Do you have any unspent criminal convictions?</p>
                <div class="flex gap-6 mb-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" :value="true"  v-model="f.criminal_convictions" :disabled="!canEdit" class="accent-[#EF233C]" /> Yes
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" :value="false" v-model="f.criminal_convictions" :disabled="!canEdit" class="accent-[#EF233C]" /> No
                    </label>
                </div>
                <FormField v-if="f.criminal_convictions" label="If yes, please provide details">
                    <textarea v-model="f.criminal_details" :disabled="!canEdit" rows="3" class="field resize-none" />
                </FormField>
            </FormSection>

            <!-- ── DBS Check Consent ─────────────────────────────────────── -->
            <FormSection title="DBS Check Consent">
                <p class="text-sm text-gray-600 mb-3">
                    I consent to the Company carrying out a DBS / background check where required for my role.
                </p>
                <label class="flex items-center gap-2 cursor-pointer mb-4">
                    <input type="checkbox" v-model="f.dbs_consent" :disabled="!canEdit" class="accent-[#EF233C] w-4 h-4" />
                    <span class="text-sm font-medium text-gray-700">I consent</span>
                </label>
                <FormField v-if="f.dbs_consent" label="Date Signed">
                    <input type="date" v-model="f.dbs_signed_date" :disabled="!canEdit" class="field w-48" />
                </FormField>
                <div class="print:block hidden mt-4">
                    <div class="flex gap-12 text-sm text-gray-700">
                        <div>Signed: <span class="inline-block border-b border-gray-400 w-40 ml-2"></span></div>
                        <div>Date: <span class="inline-block border-b border-gray-400 w-32 ml-2"></span></div>
                    </div>
                </div>
            </FormSection>

            <!-- ── Bank Details ──────────────────────────────────────────── -->
            <FormSection title="Bank Details">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <FormField label="Account Name" class="sm:col-span-3">
                        <input type="text" v-model="f.bank_account_name" :disabled="!canEdit" class="field" />
                    </FormField>
                    <FormField label="Sort Code">
                        <input type="text" v-model="f.bank_sort_code" :disabled="!canEdit" class="field" placeholder="00-00-00" maxlength="8" />
                    </FormField>
                    <FormField label="Account Number" class="sm:col-span-2">
                        <input type="text" v-model="f.bank_account_number" :disabled="!canEdit" class="field" placeholder="12345678" maxlength="8" />
                    </FormField>
                </div>
            </FormSection>

            <!-- ── Documents Required ────────────────────────────────────── -->
            <FormSection title="Documents Required">
                <p class="text-xs text-gray-500 mb-3">Please provide the following documents. Check each box when received.</p>
                <div class="space-y-2">
                    <label v-for="doc in docs" :key="doc.key" class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" v-model="f[doc.key]" :disabled="!canEdit" class="accent-[#EF233C] w-4 h-4" />
                        <span class="text-sm text-gray-700">{{ doc.label }}</span>
                    </label>
                </div>
            </FormSection>

            <!-- ── Declaration ────────────────────────────────────────────── -->
            <FormSection title="Declaration">
                <p class="text-sm text-gray-600 mb-4">I confirm the information provided is correct.</p>
                <FormField label="Date Signed">
                    <input type="date" v-model="f.declaration_signed_date" :disabled="!canEdit" class="field w-48" />
                </FormField>
                <div class="print:block hidden mt-4">
                    <div class="flex gap-12 text-sm text-gray-700">
                        <div>Signed: <span class="inline-block border-b border-gray-400 w-40 ml-2"></span></div>
                        <div>Date: <span class="inline-block border-b border-gray-400 w-32 ml-2"></span></div>
                    </div>
                </div>
            </FormSection>

        </div>
    </AppLayout>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ArrowLeftIcon, CheckIcon, PrinterIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    staffMember: { type: Object, required: true },
    form:        { type: Object, default: null },
    canEdit:     { type: Boolean, default: false },
});

// ── Reusable sub-components ────────────────────────────────────────────────

const FormSection = {
    props: ['title'],
    template: `
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="bg-[#2B2D42] px-5 py-3">
                <h2 class="text-sm font-semibold text-white uppercase tracking-wider">{{ title }}</h2>
            </div>
            <div class="p-5">
                <slot />
            </div>
        </div>
    `,
};

const FormField = {
    props: ['label'],
    template: `
        <div class="flex flex-col gap-1">
            <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ label }}</label>
            <slot />
        </div>
    `,
};

// ── Form state ──────────────────────────────────────────────────────────────

const blank = {
    address: '', phone: '', national_insurance: '', emergency_contact: '',
    position: '', start_date: props.staffMember.hire_date ?? '', supervisor: '', employment_type: '',
    previous_experience: '', qualifications: '', driving_licence: null, own_transport: null,
    medical_information: '',
    criminal_convictions: null, criminal_details: '',
    dbs_consent: false, dbs_signed_date: '',
    bank_account_name: '', bank_sort_code: '', bank_account_number: '',
    doc_id: false, doc_proof_of_address: false, doc_cis_utr: false, doc_tickets: false,
    declaration_signed_date: '',
};

const f = reactive({ ...blank, ...(props.form ?? {}) });
const saving = ref(false);

const docs = [
    { key: 'doc_id',               label: 'ID (passport or driving licence)' },
    { key: 'doc_proof_of_address', label: 'Proof of Address (utility bill, bank statement)' },
    { key: 'doc_cis_utr',          label: 'CIS / UTR number (if self-employed)' },
    { key: 'doc_tickets',          label: 'Relevant Tickets / Certificates' },
];

// ── Actions ────────────────────────────────────────────────────────────────

function save() {
    saving.value = true;
    router.post(route('staff.onboarding.store', props.staffMember.id), { ...f }, {
        preserveScroll: true,
        onFinish: () => { saving.value = false; },
    });
}

function printForm() {
    window.print();
}

function formatDate(d) {
    return new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}
</script>

<style scoped>
.field {
    @apply w-full border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#EF233C]/30 focus:border-[#EF233C] transition-colors disabled:bg-gray-50 disabled:text-gray-500 disabled:cursor-not-allowed;
}

@media print {
    :deep(.sidebar), :deep(header), :deep(nav) { display: none !important; }
    * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
}
</style>
