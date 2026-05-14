<template>
    <AppLayout :title="`Onboarding — ${staffMember.name}`">
        <div class="max-w-3xl mx-auto space-y-5 pb-12">

            <!-- Header -->
            <div class="bg-white rounded-xl border border-gray-200 p-5 flex items-center justify-between gap-4">
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
                    <span v-if="form?.updated_at" class="text-xs text-gray-400 hidden sm:inline">
                        Last saved {{ formatDate(form.updated_at) }}
                    </span>
                    <button type="button" @click="window.print()"
                        class="inline-flex items-center gap-1.5 border border-gray-200 text-gray-600 text-sm px-3 py-1.5 rounded-lg hover:bg-gray-50 transition-colors">
                        <PrinterIcon class="w-4 h-4" /> Print
                    </button>
                    <button v-if="canEdit" type="button" @click="save" :disabled="saving"
                        class="inline-flex items-center gap-1.5 bg-[#EF233C] text-white text-sm px-3 py-1.5 rounded-lg hover:bg-[#D90429] transition-colors disabled:opacity-60">
                        <CheckIcon class="w-4 h-4" />
                        {{ saving ? 'Saving…' : 'Save' }}
                    </button>
                </div>
            </div>

            <!-- Flash -->
            <div v-if="$page.props.flash?.success"
                class="bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm px-4 py-3 rounded-lg">
                {{ $page.props.flash.success }}
            </div>

            <!-- ── Employee Details ──────────────────────────────────────── -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="bg-[#2B2D42] px-5 py-3">
                    <h2 class="text-sm font-semibold text-white uppercase tracking-wider">Employee Details</h2>
                </div>
                <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="field-label">Full Name</label>
                        <input type="text" :value="staffMember.name" disabled class="field field-disabled" />
                    </div>
                    <div>
                        <label class="field-label">Email</label>
                        <input type="email" :value="staffMember.email" disabled class="field field-disabled" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="field-label">Address</label>
                        <textarea v-model="f.address" :disabled="!canEdit" rows="2" class="field resize-none" />
                    </div>
                    <div>
                        <label class="field-label">Phone Number</label>
                        <input type="tel" v-model="f.phone" :disabled="!canEdit" class="field" />
                    </div>
                    <div>
                        <label class="field-label">National Insurance Number</label>
                        <input type="text" v-model="f.national_insurance" :disabled="!canEdit" class="field uppercase" placeholder="e.g. AB 12 34 56 C" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="field-label">Emergency Contact (Name &amp; Phone)</label>
                        <input type="text" v-model="f.emergency_contact" :disabled="!canEdit" class="field"
                            :placeholder="emergencyPlaceholder" />
                    </div>
                </div>
            </div>

            <!-- ── Job Information ──────────────────────────────────────── -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="bg-[#2B2D42] px-5 py-3">
                    <h2 class="text-sm font-semibold text-white uppercase tracking-wider">Job Information</h2>
                </div>
                <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="field-label">Position</label>
                        <input type="text" v-model="f.position" :disabled="!canEdit" class="field" />
                    </div>
                    <div>
                        <label class="field-label">Start Date</label>
                        <input type="date" v-model="f.start_date" :disabled="!canEdit" class="field" />
                    </div>
                    <div>
                        <label class="field-label">Supervisor / Manager</label>
                        <input type="text" v-model="f.supervisor" :disabled="!canEdit" class="field" />
                    </div>
                    <div>
                        <label class="field-label">Employment Type</label>
                        <select v-model="f.employment_type" :disabled="!canEdit" class="field">
                            <option value="">— Select —</option>
                            <option value="full_time">Full Time</option>
                            <option value="part_time">Part Time</option>
                            <option value="self_employed">Self-Employed</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- ── Experience ───────────────────────────────────────────── -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="bg-[#2B2D42] px-5 py-3">
                    <h2 class="text-sm font-semibold text-white uppercase tracking-wider">Experience</h2>
                </div>
                <div class="p-5 space-y-4">
                    <div>
                        <label class="field-label">Previous Construction Experience</label>
                        <textarea v-model="f.previous_experience" :disabled="!canEdit" rows="3" class="field resize-none" />
                    </div>
                    <div>
                        <label class="field-label">Tickets / Qualifications</label>
                        <textarea v-model="f.qualifications" :disabled="!canEdit" rows="2" class="field resize-none" placeholder="CSCS, First Aid, IPAF, etc." />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="field-label">Driving Licence</label>
                            <div class="flex gap-5 mt-2">
                                <label class="flex items-center gap-2 cursor-pointer text-sm">
                                    <input type="radio" :value="true" v-model="f.driving_licence" :disabled="!canEdit" class="accent-[#EF233C]" /> Yes
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer text-sm">
                                    <input type="radio" :value="false" v-model="f.driving_licence" :disabled="!canEdit" class="accent-[#EF233C]" /> No
                                </label>
                            </div>
                        </div>
                        <div>
                            <label class="field-label">Own Transport</label>
                            <div class="flex gap-5 mt-2">
                                <label class="flex items-center gap-2 cursor-pointer text-sm">
                                    <input type="radio" :value="true" v-model="f.own_transport" :disabled="!canEdit" class="accent-[#EF233C]" /> Yes
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer text-sm">
                                    <input type="radio" :value="false" v-model="f.own_transport" :disabled="!canEdit" class="accent-[#EF233C]" /> No
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Medical Information ──────────────────────────────────── -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="bg-[#2B2D42] px-5 py-3">
                    <h2 class="text-sm font-semibold text-white uppercase tracking-wider">Medical Information</h2>
                </div>
                <div class="p-5">
                    <p class="text-xs text-gray-500 mb-3">
                        Please disclose any medical conditions, allergies, injuries, epilepsy, or health concerns relevant to your role or site safety.
                    </p>
                    <textarea v-model="f.medical_information" :disabled="!canEdit" rows="4" class="field resize-none w-full" />
                </div>
            </div>

            <!-- ── Criminal Offences ────────────────────────────────────── -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="bg-[#2B2D42] px-5 py-3">
                    <h2 class="text-sm font-semibold text-white uppercase tracking-wider">Criminal Offences</h2>
                </div>
                <div class="p-5">
                    <p class="text-sm font-medium text-gray-700 mb-3">Do you have any unspent criminal convictions?</p>
                    <div class="flex gap-6 mb-4">
                        <label class="flex items-center gap-2 cursor-pointer text-sm">
                            <input type="radio" :value="true" v-model="f.criminal_convictions" :disabled="!canEdit" class="accent-[#EF233C]" /> Yes
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer text-sm">
                            <input type="radio" :value="false" v-model="f.criminal_convictions" :disabled="!canEdit" class="accent-[#EF233C]" /> No
                        </label>
                    </div>
                    <div v-if="f.criminal_convictions">
                        <label class="field-label">Please provide details</label>
                        <textarea v-model="f.criminal_details" :disabled="!canEdit" rows="3" class="field resize-none" />
                    </div>
                </div>
            </div>

            <!-- ── DBS Check Consent ─────────────────────────────────────── -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="bg-[#2B2D42] px-5 py-3">
                    <h2 class="text-sm font-semibold text-white uppercase tracking-wider">DBS Check Consent</h2>
                </div>
                <div class="p-5">
                    <p class="text-sm text-gray-600 mb-4">
                        I consent to the Company carrying out a DBS / background check where required for my role.
                    </p>
                    <label class="flex items-center gap-2 cursor-pointer mb-4">
                        <input type="checkbox" v-model="f.dbs_consent" :disabled="!canEdit" class="accent-[#EF233C] w-4 h-4" />
                        <span class="text-sm font-medium text-gray-700">I consent</span>
                    </label>
                    <div v-if="f.dbs_consent">
                        <label class="field-label">Date Signed</label>
                        <input type="date" v-model="f.dbs_signed_date" :disabled="!canEdit" class="field w-48" />
                    </div>
                </div>
            </div>

            <!-- ── Bank Details ──────────────────────────────────────────── -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="bg-[#2B2D42] px-5 py-3">
                    <h2 class="text-sm font-semibold text-white uppercase tracking-wider">Bank Details</h2>
                </div>
                <div class="p-5 grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="sm:col-span-3">
                        <label class="field-label">Account Name</label>
                        <input type="text" v-model="f.bank_account_name" :disabled="!canEdit" class="field" />
                    </div>
                    <div>
                        <label class="field-label">Sort Code</label>
                        <input type="text" v-model="f.bank_sort_code" :disabled="!canEdit" class="field" placeholder="00-00-00" maxlength="8" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="field-label">Account Number</label>
                        <input type="text" v-model="f.bank_account_number" :disabled="!canEdit" class="field" placeholder="12345678" maxlength="8" />
                    </div>
                </div>
            </div>

            <!-- ── Documents Required ────────────────────────────────────── -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="bg-[#2B2D42] px-5 py-3">
                    <h2 class="text-sm font-semibold text-white uppercase tracking-wider">Documents Required</h2>
                </div>
                <div class="p-5">
                    <p class="text-xs text-gray-500 mb-3">Tick each box when the document has been received.</p>
                    <div class="space-y-2.5">
                        <label v-for="doc in docs" :key="doc.key" class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" v-model="f[doc.key]" :disabled="!canEdit" class="accent-[#EF233C] w-4 h-4" />
                            <span class="text-sm text-gray-700">{{ doc.label }}</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- ── Declaration ────────────────────────────────────────────── -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="bg-[#2B2D42] px-5 py-3">
                    <h2 class="text-sm font-semibold text-white uppercase tracking-wider">Declaration</h2>
                </div>
                <div class="p-5">
                    <p class="text-sm text-gray-600 mb-4">I confirm the information provided is correct.</p>
                    <div>
                        <label class="field-label">Date Signed</label>
                        <input type="date" v-model="f.declaration_signed_date" :disabled="!canEdit" class="field w-48" />
                    </div>
                </div>
            </div>

            <!-- ── Medical Documents ─────────────────────────────────────── -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="bg-[#2B2D42] px-5 py-3 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-white uppercase tracking-wider">Medical Documents</h2>
                    <span class="text-xs text-white/60">PDF, JPG, PNG, DOC · max 10 MB each</span>
                </div>
                <div class="p-5 space-y-4">

                    <!-- Upload area -->
                    <div v-if="canEdit">
                        <label
                            class="flex flex-col items-center justify-center gap-2 border-2 border-dashed border-gray-200 rounded-xl py-8 px-4 cursor-pointer hover:border-[#EF233C]/40 hover:bg-red-50/30 transition-colors"
                            :class="uploading ? 'opacity-60 pointer-events-none' : ''"
                        >
                            <ArrowUpTrayIcon class="w-7 h-7 text-gray-400" />
                            <span class="text-sm font-medium text-gray-600">
                                {{ uploading ? 'Uploading…' : 'Click to upload a medical document' }}
                            </span>
                            <span class="text-xs text-gray-400">or drag and drop</span>
                            <input type="file" class="hidden"
                                accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx"
                                :disabled="uploading"
                                @change="uploadDocument" />
                        </label>
                    </div>

                    <!-- Document list -->
                    <div v-if="documents.length" class="space-y-2">
                        <div
                            v-for="doc in documents"
                            :key="doc.id"
                            class="flex items-center gap-3 bg-gray-50 border border-gray-100 rounded-lg px-4 py-3"
                        >
                            <component :is="fileIcon(doc.mime_type)" class="w-5 h-5 text-gray-400 flex-shrink-0" />
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-800 truncate">{{ doc.original_name }}</p>
                                <p class="text-xs text-gray-400">
                                    {{ formatSize(doc.size) }} · Uploaded {{ doc.uploaded_at }}
                                    <span v-if="doc.uploaded_by"> by {{ doc.uploaded_by }}</span>
                                </p>
                            </div>
                            <a
                                :href="route('staff.onboarding.documents.download', [staffMember.id, doc.id])"
                                target="_blank"
                                class="text-xs text-blue-600 hover:underline flex-shrink-0"
                            >Download</a>
                            <button
                                v-if="canEdit"
                                type="button"
                                @click="deleteDocument(doc)"
                                class="text-gray-400 hover:text-red-500 transition-colors flex-shrink-0"
                                title="Delete"
                            >
                                <TrashIcon class="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    <p v-else class="text-sm text-gray-400 text-center py-2">No documents uploaded yet.</p>
                </div>
            </div>

            <!-- Bottom save -->
            <div v-if="canEdit" class="flex justify-end">
                <button type="button" @click="save" :disabled="saving"
                    class="inline-flex items-center gap-2 bg-[#EF233C] text-white text-sm px-5 py-2.5 rounded-lg hover:bg-[#D90429] transition-colors disabled:opacity-60 font-medium">
                    <CheckIcon class="w-4 h-4" />
                    {{ saving ? 'Saving…' : 'Save Onboarding Form' }}
                </button>
            </div>

        </div>
    </AppLayout>
</template>

<script setup>
import { reactive, ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    ArrowLeftIcon, CheckIcon, PrinterIcon,
    ArrowUpTrayIcon, TrashIcon,
    DocumentTextIcon, PhotoIcon, DocumentIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    staffMember: { type: Object, required: true },
    form:        { type: Object, default: null },
    documents:   { type: Array,  default: () => [] },
    canEdit:     { type: Boolean, default: false },
});

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

const emergencyPlaceholder = computed(() => {
    const name  = props.staffMember.emergency_contact_name  ?? '';
    const phone = props.staffMember.emergency_contact_phone ?? '';
    return [name, phone].filter(Boolean).join(' · ') || 'Name & phone number';
});

function save() {
    saving.value = true;
    router.post(route('staff.onboarding.store', props.staffMember.id), { ...f }, {
        preserveScroll: true,
        onFinish: () => { saving.value = false; },
    });
}

// ── Document upload / delete ───────────────────────────────────────────────

const uploading = ref(false);

function uploadDocument(event) {
    const file = event.target.files?.[0];
    if (!file) return;
    uploading.value = true;
    router.post(
        route('staff.onboarding.documents.upload', props.staffMember.id),
        { document: file },
        {
            forceFormData: true,
            preserveScroll: true,
            onFinish: () => {
                uploading.value = false;
                event.target.value = ''; // reset input
            },
        }
    );
}

function deleteDocument(doc) {
    if (!confirm(`Delete "${doc.original_name}"?`)) return;
    router.delete(
        route('staff.onboarding.documents.delete', [props.staffMember.id, doc.id]),
        { preserveScroll: true }
    );
}

function fileIcon(mime) {
    if (mime?.startsWith('image/')) return PhotoIcon;
    if (mime === 'application/pdf')  return DocumentTextIcon;
    return DocumentIcon;
}

function formatSize(bytes) {
    if (bytes < 1024)       return bytes + ' B';
    if (bytes < 1048576)    return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / 1048576).toFixed(1) + ' MB';
}

function formatDate(d) {
    return new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}
</script>

<style scoped>
.field {
    @apply w-full border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-800 bg-white
           focus:outline-none focus:ring-2 focus:ring-[#EF233C]/30 focus:border-[#EF233C]
           transition-colors;
}
.field:disabled, .field-disabled {
    @apply bg-gray-50 text-gray-500 cursor-not-allowed;
}
.field-label {
    @apply block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5;
}

@media print {
    :deep(aside), :deep(header) { display: none !important; }
}
</style>
