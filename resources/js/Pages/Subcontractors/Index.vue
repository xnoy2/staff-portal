<template>
    <AppLayout title="Subcontractors">
        <div class="max-w-7xl mx-auto space-y-5">

            <!-- Header -->
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-lg font-semibold text-gray-800">Subcontractors</h1>
                    <p class="text-xs text-gray-500 mt-0.5">
                        {{ subcontractors.length }} contractor{{ subcontractors.length !== 1 ? 's' : '' }} &middot;
                        {{ subcontractors.filter(s => s.is_active).length }} active
                    </p>
                </div>
                <button
                    v-if="canEdit"
                    @click="openAdd"
                    class="inline-flex items-center gap-1.5 bg-[#EF233C] text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-[#D90429] transition-colors"
                >
                    <PlusIcon class="w-4 h-4" />
                    Add Subcontractor
                </button>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-xl border border-gray-200 p-4 flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1">
                    <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search name, company or trade…"
                        class="w-full pl-9 pr-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-[#EF233C] focus:border-[#EF233C]"
                    />
                </div>
                <select
                    v-model="filterTrade"
                    class="sm:w-56 px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-[#EF233C] focus:border-[#EF233C] text-gray-700"
                >
                    <option value="">All Trades</option>
                    <option v-for="t in trades" :key="t" :value="t">{{ t }}</option>
                </select>
                <div class="flex items-center gap-1 bg-gray-100 rounded-lg p-1">
                    <button
                        v-for="opt in statusOpts"
                        :key="opt.value"
                        @click="filterStatus = opt.value"
                        :class="[
                            'text-xs font-medium px-3 py-1 rounded-md transition-colors whitespace-nowrap',
                            filterStatus === opt.value
                                ? 'bg-white text-gray-800 shadow-sm'
                                : 'text-gray-500 hover:text-gray-700',
                        ]"
                    >{{ opt.label }}</button>
                </div>
            </div>

            <!-- Cards grid -->
            <div v-if="filtered.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div
                    v-for="s in filtered"
                    :key="s.id"
                    class="bg-white rounded-xl border border-gray-200 flex flex-col overflow-hidden hover:shadow-md transition-shadow"
                    :class="{ 'opacity-60': !s.is_active }"
                >
                    <!-- Card header -->
                    <div class="px-4 pt-4 pb-3 border-b border-gray-100">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-10 h-10 rounded-xl bg-[#2B2D42] flex items-center justify-center flex-shrink-0">
                                    <WrenchScrewdriverIcon class="w-5 h-5 text-white" />
                                </div>
                                <div class="min-w-0">
                                    <p class="font-semibold text-gray-800 text-sm truncate">{{ s.name }}</p>
                                    <p v-if="s.company" class="text-xs text-gray-500 truncate">{{ s.company }}</p>
                                </div>
                            </div>
                            <span
                                class="flex-shrink-0 inline-flex items-center gap-1 text-xs font-medium px-2 py-0.5 rounded-full border"
                                :class="s.is_active
                                    ? 'bg-green-50 text-green-700 border-green-200'
                                    : 'bg-gray-100 text-gray-500 border-gray-200'"
                            >
                                <span class="w-1.5 h-1.5 rounded-full" :class="s.is_active ? 'bg-green-500' : 'bg-gray-400'"></span>
                                {{ s.is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>

                        <!-- Trade badge -->
                        <span class="mt-2.5 inline-flex items-center gap-1 text-xs bg-blue-50 text-blue-700 border border-blue-100 px-2 py-0.5 rounded-full font-medium">
                            {{ s.trade }}
                        </span>
                    </div>

                    <!-- Card body -->
                    <div class="px-4 py-3 flex flex-col gap-2.5 flex-1">

                        <!-- Contact -->
                        <div v-if="s.email || s.phone" class="flex flex-col gap-1">
                            <a v-if="s.email" :href="`mailto:${s.email}`" class="text-xs text-blue-500 hover:underline truncate flex items-center gap-1.5">
                                <EnvelopeIcon class="w-3.5 h-3.5 flex-shrink-0 text-gray-400" />
                                {{ s.email }}
                            </a>
                            <a v-if="s.phone" :href="`tel:${s.phone}`" class="text-xs text-gray-500 flex items-center gap-1.5">
                                <PhoneIcon class="w-3.5 h-3.5 flex-shrink-0 text-gray-400" />
                                {{ s.phone }}
                            </a>
                        </div>

                        <!-- Verifications -->
                        <div class="flex gap-2">
                            <span
                                class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full border font-medium"
                                :class="s.qualification_verified
                                    ? 'bg-green-50 text-green-700 border-green-200'
                                    : 'bg-gray-50 text-gray-400 border-gray-200'"
                            >
                                <CheckCircleIcon v-if="s.qualification_verified" class="w-3.5 h-3.5" />
                                <XCircleIcon v-else class="w-3.5 h-3.5" />
                                Qualification
                            </span>
                            <span
                                class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full border font-medium"
                                :class="s.insurance_verified
                                    ? 'bg-green-50 text-green-700 border-green-200'
                                    : 'bg-gray-50 text-gray-400 border-gray-200'"
                            >
                                <CheckCircleIcon v-if="s.insurance_verified" class="w-3.5 h-3.5" />
                                <XCircleIcon v-else class="w-3.5 h-3.5" />
                                Insurance
                            </span>
                        </div>

                        <!-- Notes -->
                        <p v-if="s.notes" class="text-xs text-gray-500 line-clamp-2 leading-relaxed">{{ s.notes }}</p>
                    </div>

                    <!-- Card footer -->
                    <div class="px-4 py-2.5 border-t border-gray-100 bg-gray-50 flex items-center justify-between gap-2">
                        <button
                            @click="openPhotos(s)"
                            class="inline-flex items-center gap-1.5 text-xs text-gray-500 hover:text-[#EF233C] transition-colors"
                        >
                            <PhotoIcon class="w-4 h-4" />
                            {{ s.photos.length }} photo{{ s.photos.length !== 1 ? 's' : '' }}
                        </button>
                        <div v-if="canEdit" class="flex items-center gap-0.5">
                            <button
                                @click="openEdit(s)"
                                class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                                title="Edit"
                            >
                                <PencilIcon class="w-3.5 h-3.5" />
                            </button>
                            <button
                                @click="confirmDelete(s)"
                                class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                title="Delete"
                            >
                                <TrashIcon class="w-3.5 h-3.5" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty state -->
            <div v-else class="bg-white rounded-xl border border-gray-200 py-16 text-center">
                <WrenchScrewdriverIcon class="w-10 h-10 text-gray-200 mx-auto mb-3" />
                <p class="text-sm text-gray-500">No subcontractors found.</p>
                <p v-if="search || filterTrade || filterStatus" class="text-xs text-gray-400 mt-1">Try adjusting your filters.</p>
            </div>
        </div>

        <!-- ── Add / Edit Modal ────────────────────────────────────────── -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="modal.open" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="closeModal" />
                    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
                        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                            <h2 class="text-sm font-semibold text-gray-800">{{ modal.editing ? 'Edit Subcontractor' : 'Add Subcontractor' }}</h2>
                            <button @click="closeModal" class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                                <XMarkIcon class="w-4 h-4" />
                            </button>
                        </div>
                        <form @submit.prevent="submitModal" class="p-5 space-y-4">
                            <!-- Name -->
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1.5">Name <span class="text-red-500">*</span></label>
                                <input v-model="form.name" type="text" required placeholder="Full name" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-[#EF233C] focus:border-[#EF233C]" />
                            </div>
                            <!-- Trade -->
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1.5">Trade <span class="text-red-500">*</span></label>
                                <select v-model="form.trade" required class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-[#EF233C] focus:border-[#EF233C] text-gray-800">
                                    <option value="" disabled>Select trade…</option>
                                    <option v-for="t in trades" :key="t" :value="t">{{ t }}</option>
                                </select>
                            </div>
                            <!-- Company -->
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1.5">Company <span class="text-gray-400 font-normal">(optional)</span></label>
                                <input v-model="form.company" type="text" placeholder="Company name" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-[#EF233C] focus:border-[#EF233C]" />
                            </div>
                            <!-- Email / Phone -->
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1.5">Email</label>
                                    <input v-model="form.email" type="email" placeholder="email@example.com" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-[#EF233C] focus:border-[#EF233C]" />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1.5">Phone</label>
                                    <input v-model="form.phone" type="tel" placeholder="+44…" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-[#EF233C] focus:border-[#EF233C]" />
                                </div>
                            </div>
                            <!-- Verifications -->
                            <div class="rounded-lg bg-gray-50 border border-gray-200 p-3 flex gap-6">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input v-model="form.qualification_verified" type="checkbox" class="w-4 h-4 rounded accent-green-600" />
                                    <span class="text-sm text-gray-700">Qualification verified</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input v-model="form.insurance_verified" type="checkbox" class="w-4 h-4 rounded accent-green-600" />
                                    <span class="text-sm text-gray-700">Insurance verified</span>
                                </label>
                            </div>
                            <!-- Notes -->
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1.5">Notes <span class="text-gray-400 font-normal">(optional)</span></label>
                                <textarea v-model="form.notes" rows="3" placeholder="Any relevant notes…" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-[#EF233C] focus:border-[#EF233C] resize-none" />
                            </div>
                            <!-- Active -->
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input v-model="form.is_active" type="checkbox" class="w-4 h-4 rounded accent-green-600" />
                                <span class="text-sm text-gray-700">Mark as active</span>
                            </label>
                            <!-- Footer -->
                            <div class="flex items-center justify-end gap-3 pt-1 border-t border-gray-100">
                                <button type="button" @click="closeModal" class="text-sm text-gray-500 hover:text-gray-700 px-3 py-1.5 transition-colors">Cancel</button>
                                <button type="submit" :disabled="processing" class="bg-[#EF233C] hover:bg-[#D90429] disabled:opacity-60 text-white text-sm font-medium px-5 py-1.5 rounded-lg transition-colors flex items-center gap-2">
                                    <span v-if="processing" class="w-3.5 h-3.5 border-2 border-white/40 border-t-white rounded-full animate-spin" />
                                    {{ modal.editing ? 'Save Changes' : 'Add' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Transition>

            <!-- ── Photos Modal ────────────────────────────────────────── -->
            <Transition name="modal">
                <div v-if="photosModal.open" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="closePhotos" />
                    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col overflow-hidden">
                        <!-- Header -->
                        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 flex-shrink-0">
                            <div>
                                <h2 class="text-sm font-semibold text-gray-800">{{ photosModal.sub?.name }}</h2>
                                <p class="text-xs text-gray-400">Before &amp; after photos</p>
                            </div>
                            <button @click="closePhotos" class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                                <XMarkIcon class="w-4 h-4" />
                            </button>
                        </div>

                        <div class="overflow-y-auto flex-1 p-5 space-y-5">
                            <!-- Upload -->
                            <div v-if="canEdit" class="bg-gray-50 border border-gray-200 rounded-xl p-4 space-y-3">
                                <p class="text-xs font-semibold text-gray-700 uppercase tracking-wider">Upload photo</p>
                                <div class="flex gap-3 flex-wrap">
                                    <div class="flex-1 min-w-[130px]">
                                        <label class="block text-xs text-gray-500 mb-1">Type</label>
                                        <select v-model="photoUpload.type" class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-[#EF233C] focus:border-[#EF233C]">
                                            <option value="before">Before</option>
                                            <option value="after">After</option>
                                        </select>
                                    </div>
                                    <div class="flex-1 min-w-[130px]">
                                        <label class="block text-xs text-gray-500 mb-1">Caption (optional)</label>
                                        <input v-model="photoUpload.caption" type="text" placeholder="Short description…" class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-[#EF233C] focus:border-[#EF233C]" />
                                    </div>
                                </div>
                                <label class="flex items-center justify-center gap-2 border-2 border-dashed border-gray-300 hover:border-[#EF233C] rounded-xl py-4 cursor-pointer transition-colors group">
                                    <ArrowUpTrayIcon class="w-5 h-5 text-gray-400 group-hover:text-[#EF233C] transition-colors" />
                                    <span class="text-sm text-gray-500 group-hover:text-gray-700 transition-colors">
                                        {{ photoUploading ? 'Uploading…' : 'Choose photo (max 5 MB)' }}
                                    </span>
                                    <input type="file" accept="image/*" class="hidden" :disabled="photoUploading" @change="uploadPhoto" />
                                </label>
                            </div>

                            <!-- Before -->
                            <div v-if="beforePhotos.length">
                                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Before</p>
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                    <div v-for="p in beforePhotos" :key="p.id" class="relative group rounded-xl overflow-hidden bg-gray-100 aspect-video">
                                        <img :src="p.url" :alt="p.caption || p.original_name" class="w-full h-full object-cover" />
                                        <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex flex-col items-center justify-center gap-2 p-2">
                                            <p v-if="p.caption" class="text-xs text-white text-center line-clamp-2">{{ p.caption }}</p>
                                            <a :href="p.url" target="_blank" class="text-xs font-medium text-white bg-white/20 hover:bg-white/30 px-3 py-1 rounded-full transition-colors">View</a>
                                            <button v-if="canEdit" @click="deletePhoto(p)" class="text-xs font-medium text-white bg-red-500/80 hover:bg-red-500 px-3 py-1 rounded-full transition-colors">Delete</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- After -->
                            <div v-if="afterPhotos.length">
                                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">After</p>
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                    <div v-for="p in afterPhotos" :key="p.id" class="relative group rounded-xl overflow-hidden bg-gray-100 aspect-video">
                                        <img :src="p.url" :alt="p.caption || p.original_name" class="w-full h-full object-cover" />
                                        <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex flex-col items-center justify-center gap-2 p-2">
                                            <p v-if="p.caption" class="text-xs text-white text-center line-clamp-2">{{ p.caption }}</p>
                                            <a :href="p.url" target="_blank" class="text-xs font-medium text-white bg-white/20 hover:bg-white/30 px-3 py-1 rounded-full transition-colors">View</a>
                                            <button v-if="canEdit" @click="deletePhoto(p)" class="text-xs font-medium text-white bg-red-500/80 hover:bg-red-500 px-3 py-1 rounded-full transition-colors">Delete</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-if="!beforePhotos.length && !afterPhotos.length && !canEdit" class="text-center py-6 text-sm text-gray-400">
                                No photos uploaded yet.
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>

            <!-- ── Delete Confirm ─────────────────────────────────────── -->
            <Transition name="modal">
                <div v-if="deleteTarget" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="deleteTarget = null" />
                    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 rounded-full bg-red-50 flex items-center justify-center flex-shrink-0">
                                <TrashIcon class="w-5 h-5 text-red-500" />
                            </div>
                            <div>
                                <p class="font-semibold text-gray-800 text-sm">Delete subcontractor?</p>
                                <p class="text-xs text-gray-500">{{ deleteTarget?.name }}</p>
                            </div>
                        </div>
                        <p class="text-sm text-gray-500 mb-5 ml-13">All photos will be permanently deleted. This cannot be undone.</p>
                        <div class="flex justify-end gap-3">
                            <button @click="deleteTarget = null" class="text-sm text-gray-500 hover:text-gray-700 px-3 py-1.5 transition-colors">Cancel</button>
                            <button @click="doDelete" class="bg-[#EF233C] hover:bg-[#D90429] text-white text-sm font-medium px-5 py-1.5 rounded-lg transition-colors">Delete</button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    PlusIcon,
    XMarkIcon,
    PhotoIcon,
    CheckCircleIcon,
    XCircleIcon,
    WrenchScrewdriverIcon,
    ArrowUpTrayIcon,
    MagnifyingGlassIcon,
    PencilIcon,
    TrashIcon,
    EnvelopeIcon,
    PhoneIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    subcontractors: { type: Array, default: () => [] },
    trades:         { type: Array, default: () => [] },
    filters:        { type: Object, default: () => ({}) },
    canEdit:        { type: Boolean, default: false },
});

const statusOpts = [
    { label: 'All',      value: '' },
    { label: 'Active',   value: 'active' },
    { label: 'Inactive', value: 'inactive' },
];

// Filters
const search       = ref(props.filters.search ?? '');
const filterTrade  = ref(props.filters.trade  ?? '');
const filterStatus = ref(props.filters.status ?? '');

const filtered = computed(() => {
    let list = props.subcontractors;
    const q = search.value.toLowerCase();
    if (q) list = list.filter(s =>
        s.name.toLowerCase().includes(q) ||
        (s.company ?? '').toLowerCase().includes(q) ||
        s.trade.toLowerCase().includes(q)
    );
    if (filterTrade.value)  list = list.filter(s => s.trade === filterTrade.value);
    if (filterStatus.value) list = list.filter(s =>
        filterStatus.value === 'active' ? s.is_active : !s.is_active
    );
    return list;
});

// Add / Edit modal
const processing = ref(false);
const modal = ref({ open: false, editing: null });

function emptyForm() {
    return { name: '', trade: '', company: '', email: '', phone: '', qualification_verified: false, insurance_verified: false, notes: '', is_active: true };
}
const form = ref(emptyForm());

function openAdd() {
    form.value = emptyForm();
    modal.value = { open: true, editing: null };
}

function openEdit(s) {
    form.value = {
        name: s.name, trade: s.trade, company: s.company ?? '',
        email: s.email ?? '', phone: s.phone ?? '',
        qualification_verified: s.qualification_verified,
        insurance_verified: s.insurance_verified,
        notes: s.notes ?? '', is_active: s.is_active,
    };
    modal.value = { open: true, editing: s };
}

function closeModal() { modal.value.open = false; }

function submitModal() {
    processing.value = true;
    const opts = { preserveScroll: true, onFinish: () => { processing.value = false; closeModal(); } };
    if (modal.value.editing) {
        router.put(route('subcontractors.update', modal.value.editing.id), form.value, opts);
    } else {
        router.post(route('subcontractors.store'), form.value, opts);
    }
}

// Delete
const deleteTarget = ref(null);
function confirmDelete(s) { deleteTarget.value = s; }
function doDelete() {
    if (!deleteTarget.value) return;
    router.delete(route('subcontractors.destroy', deleteTarget.value.id), {
        preserveScroll: true,
        onFinish: () => { deleteTarget.value = null; },
    });
}

// Photos modal
const photosModal    = ref({ open: false, sub: null });
const photoUploading = ref(false);
const photoUpload    = ref({ type: 'before', caption: '' });

const beforePhotos = computed(() => photosModal.value.sub?.photos.filter(p => p.type === 'before') ?? []);
const afterPhotos  = computed(() => photosModal.value.sub?.photos.filter(p => p.type === 'after')  ?? []);

function openPhotos(s) {
    photosModal.value = { open: true, sub: s };
    photoUpload.value = { type: 'before', caption: '' };
}
function closePhotos() { photosModal.value.open = false; }

watch(() => props.subcontractors, (list) => {
    if (photosModal.value.sub) {
        const updated = list.find(s => s.id === photosModal.value.sub.id);
        if (updated) photosModal.value.sub = updated;
    }
}, { deep: true });

function uploadPhoto(event) {
    const file = event.target.files?.[0];
    if (!file || !photosModal.value.sub) return;
    photoUploading.value = true;
    router.post(
        route('subcontractors.photos.upload', photosModal.value.sub.id),
        { photo: file, type: photoUpload.value.type, caption: photoUpload.value.caption || null },
        {
            forceFormData: true,
            preserveScroll: true,
            onFinish: () => {
                photoUploading.value = false;
                event.target.value  = '';
                photoUpload.value.caption = '';
            },
        }
    );
}

function deletePhoto(photo) {
    if (!photosModal.value.sub) return;
    router.delete(
        route('subcontractors.photos.delete', { subcontractor: photosModal.value.sub.id, photo: photo.id }),
        { preserveScroll: true }
    );
}
</script>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: opacity 0.15s ease; }
.modal-enter-from, .modal-leave-to       { opacity: 0; }
</style>
