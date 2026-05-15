<template>
    <AppLayout title="Subcontractors">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-white">Subcontractors</h1>
                    <p class="text-sm text-[#8D99AE] mt-0.5">Garden Room install contractors &amp; tradespeople</p>
                </div>
                <button
                    v-if="canEdit"
                    @click="openAdd"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-[#EF233C] text-white text-sm font-medium hover:bg-red-500 transition-colors"
                >
                    <PlusIcon class="w-4 h-4" />
                    Add Subcontractor
                </button>
            </div>

            <!-- Filters -->
            <div class="flex flex-col sm:flex-row gap-3 mb-6">
                <input
                    v-model="search"
                    type="text"
                    placeholder="Search name, company, trade…"
                    class="flex-1 bg-[#1A1A2E]/60 border border-white/10 rounded-lg px-3 py-2 text-sm text-white placeholder-[#8D99AE] focus:outline-none focus:border-[#EF233C]"
                />
                <select
                    v-model="filterTrade"
                    class="bg-[#1A1A2E]/60 border border-white/10 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-[#EF233C]"
                >
                    <option value="">All Trades</option>
                    <option v-for="t in trades" :key="t" :value="t">{{ t }}</option>
                </select>
                <select
                    v-model="filterStatus"
                    class="bg-[#1A1A2E]/60 border border-white/10 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-[#EF233C]"
                >
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>

            <!-- Cards grid -->
            <div v-if="filtered.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div
                    v-for="s in filtered"
                    :key="s.id"
                    class="bg-[#1A1A2E]/60 border border-white/10 rounded-xl p-4 flex flex-col gap-3"
                    :class="{ 'opacity-60': !s.is_active }"
                >
                    <!-- Name / Trade / Status -->
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p class="font-semibold text-white leading-tight">{{ s.name }}</p>
                            <p class="text-xs text-[#8D99AE] mt-0.5">{{ s.trade }}</p>
                            <p v-if="s.company" class="text-xs text-[#8D99AE]">{{ s.company }}</p>
                        </div>
                        <span
                            class="text-xs font-medium px-2 py-0.5 rounded-full flex-shrink-0"
                            :class="s.is_active ? 'bg-green-500/20 text-green-400' : 'bg-white/10 text-[#8D99AE]'"
                        >
                            {{ s.is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>

                    <!-- Contact -->
                    <div v-if="s.email || s.phone" class="flex flex-col gap-1">
                        <a v-if="s.email" :href="`mailto:${s.email}`" class="text-xs text-blue-400 hover:underline truncate">{{ s.email }}</a>
                        <a v-if="s.phone" :href="`tel:${s.phone}`" class="text-xs text-[#8D99AE]">{{ s.phone }}</a>
                    </div>

                    <!-- Verifications -->
                    <div class="flex gap-3">
                        <div class="flex items-center gap-1.5">
                            <component
                                :is="s.qualification_verified ? CheckCircleIcon : XCircleIcon"
                                class="w-4 h-4 flex-shrink-0"
                                :class="s.qualification_verified ? 'text-green-400' : 'text-white/20'"
                            />
                            <span class="text-xs text-[#8D99AE]">Qualification</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <component
                                :is="s.insurance_verified ? CheckCircleIcon : XCircleIcon"
                                class="w-4 h-4 flex-shrink-0"
                                :class="s.insurance_verified ? 'text-green-400' : 'text-white/20'"
                            />
                            <span class="text-xs text-[#8D99AE]">Insurance</span>
                        </div>
                    </div>

                    <!-- Notes -->
                    <p v-if="s.notes" class="text-xs text-[#8D99AE] line-clamp-2 border-t border-white/5 pt-2">{{ s.notes }}</p>

                    <!-- Photos summary -->
                    <div class="flex items-center justify-between border-t border-white/5 pt-2">
                        <button
                            @click="openPhotos(s)"
                            class="flex items-center gap-1.5 text-xs text-[#8D99AE] hover:text-white transition-colors"
                        >
                            <PhotoIcon class="w-4 h-4" />
                            <span>{{ s.photos.length }} photo{{ s.photos.length !== 1 ? 's' : '' }}</span>
                        </button>
                        <div v-if="canEdit" class="flex gap-2">
                            <button @click="openEdit(s)" class="text-xs text-blue-400 hover:text-blue-300 transition-colors">Edit</button>
                            <button @click="confirmDelete(s)" class="text-xs text-red-400 hover:text-red-300 transition-colors">Delete</button>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else class="text-center py-16 text-[#8D99AE]">
                <WrenchScrewdriverIcon class="w-10 h-10 mx-auto mb-3 opacity-30" />
                <p>No subcontractors found.</p>
            </div>
        </div>

        <!-- Add / Edit Modal -->
        <Teleport to="body">
            <div v-if="modal.open" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="closeModal">
                <div class="bg-[#0D0D1A] border border-white/10 rounded-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
                    <div class="flex items-center justify-between p-5 border-b border-white/10">
                        <h2 class="text-lg font-semibold text-white">{{ modal.editing ? 'Edit Subcontractor' : 'Add Subcontractor' }}</h2>
                        <button @click="closeModal" class="text-[#8D99AE] hover:text-white"><XMarkIcon class="w-5 h-5" /></button>
                    </div>
                    <form @submit.prevent="submitModal" class="p-5 flex flex-col gap-4">
                        <!-- Name -->
                        <div>
                            <label class="block text-xs font-medium text-[#8D99AE] mb-1">Name <span class="text-red-400">*</span></label>
                            <input v-model="form.name" type="text" required class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-[#EF233C]" />
                        </div>
                        <!-- Trade -->
                        <div>
                            <label class="block text-xs font-medium text-[#8D99AE] mb-1">Trade <span class="text-red-400">*</span></label>
                            <select v-model="form.trade" required class="w-full bg-[#0D0D1A] border border-white/10 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-[#EF233C]">
                                <option value="" disabled>Select trade…</option>
                                <option v-for="t in trades" :key="t" :value="t">{{ t }}</option>
                            </select>
                        </div>
                        <!-- Company -->
                        <div>
                            <label class="block text-xs font-medium text-[#8D99AE] mb-1">Company</label>
                            <input v-model="form.company" type="text" class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-[#EF233C]" />
                        </div>
                        <!-- Email / Phone -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-[#8D99AE] mb-1">Email</label>
                                <input v-model="form.email" type="email" class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-[#EF233C]" />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-[#8D99AE] mb-1">Phone</label>
                                <input v-model="form.phone" type="tel" class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-[#EF233C]" />
                            </div>
                        </div>
                        <!-- Verifications -->
                        <div class="flex gap-6">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input v-model="form.qualification_verified" type="checkbox" class="w-4 h-4 accent-green-500 rounded" />
                                <span class="text-sm text-white">Qualification verified</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input v-model="form.insurance_verified" type="checkbox" class="w-4 h-4 accent-green-500 rounded" />
                                <span class="text-sm text-white">Insurance verified</span>
                            </label>
                        </div>
                        <!-- Notes -->
                        <div>
                            <label class="block text-xs font-medium text-[#8D99AE] mb-1">Notes</label>
                            <textarea v-model="form.notes" rows="3" class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-[#EF233C] resize-none" />
                        </div>
                        <!-- Active toggle -->
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input v-model="form.is_active" type="checkbox" class="w-4 h-4 accent-green-500 rounded" />
                            <span class="text-sm text-white">Active</span>
                        </label>
                        <!-- Actions -->
                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" @click="closeModal" class="px-4 py-2 text-sm text-[#8D99AE] hover:text-white border border-white/10 rounded-lg transition-colors">Cancel</button>
                            <button type="submit" :disabled="processing" class="px-4 py-2 text-sm font-medium text-white bg-[#EF233C] rounded-lg hover:bg-red-500 disabled:opacity-50 transition-colors">
                                {{ modal.editing ? 'Save Changes' : 'Add' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Photos Modal -->
            <div v-if="photosModal.open" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="closePhotos">
                <div class="bg-[#0D0D1A] border border-white/10 rounded-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                    <div class="flex items-center justify-between p-5 border-b border-white/10">
                        <h2 class="text-lg font-semibold text-white">
                            Photos — {{ photosModal.sub?.name }}
                        </h2>
                        <button @click="closePhotos" class="text-[#8D99AE] hover:text-white"><XMarkIcon class="w-5 h-5" /></button>
                    </div>
                    <div class="p-5 flex flex-col gap-5">

                        <!-- Upload -->
                        <div v-if="canEdit">
                            <p class="text-xs font-medium text-[#8D99AE] mb-2">Upload photo</p>
                            <div class="flex gap-3 flex-wrap">
                                <label class="flex-1 min-w-[140px]">
                                    <span class="block text-xs text-[#8D99AE] mb-1">Type</span>
                                    <select v-model="photoUpload.type" class="w-full bg-[#0D0D1A] border border-white/10 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-[#EF233C]">
                                        <option value="before">Before</option>
                                        <option value="after">After</option>
                                    </select>
                                </label>
                                <label class="flex-1 min-w-[140px]">
                                    <span class="block text-xs text-[#8D99AE] mb-1">Caption (optional)</span>
                                    <input v-model="photoUpload.caption" type="text" class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-[#EF233C]" />
                                </label>
                            </div>
                            <label class="mt-3 flex items-center justify-center gap-2 border border-dashed border-white/20 rounded-lg py-4 cursor-pointer hover:border-white/40 transition-colors">
                                <ArrowUpTrayIcon class="w-5 h-5 text-[#8D99AE]" />
                                <span class="text-sm text-[#8D99AE]">{{ photoUploading ? 'Uploading…' : 'Choose photo (max 5 MB)' }}</span>
                                <input type="file" accept="image/*" class="hidden" :disabled="photoUploading" @change="uploadPhoto" />
                            </label>
                        </div>

                        <!-- Before photos -->
                        <div v-if="beforePhotos.length">
                            <p class="text-xs font-semibold text-[#8D99AE] uppercase tracking-wider mb-2">Before</p>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                <div v-for="p in beforePhotos" :key="p.id" class="relative group rounded-lg overflow-hidden bg-white/5">
                                    <img :src="p.url" :alt="p.caption || p.original_name" class="w-full aspect-video object-cover" />
                                    <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity flex flex-col items-center justify-center gap-2 p-2">
                                        <p v-if="p.caption" class="text-xs text-white text-center line-clamp-2">{{ p.caption }}</p>
                                        <a :href="p.url" target="_blank" class="text-xs text-blue-400 hover:underline">View</a>
                                        <button v-if="canEdit" @click="deletePhoto(p)" class="text-xs text-red-400 hover:underline">Delete</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- After photos -->
                        <div v-if="afterPhotos.length">
                            <p class="text-xs font-semibold text-[#8D99AE] uppercase tracking-wider mb-2">After</p>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                <div v-for="p in afterPhotos" :key="p.id" class="relative group rounded-lg overflow-hidden bg-white/5">
                                    <img :src="p.url" :alt="p.caption || p.original_name" class="w-full aspect-video object-cover" />
                                    <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity flex flex-col items-center justify-center gap-2 p-2">
                                        <p v-if="p.caption" class="text-xs text-white text-center line-clamp-2">{{ p.caption }}</p>
                                        <a :href="p.url" target="_blank" class="text-xs text-blue-400 hover:underline">View</a>
                                        <button v-if="canEdit" @click="deletePhoto(p)" class="text-xs text-red-400 hover:underline">Delete</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <p v-if="!beforePhotos.length && !afterPhotos.length" class="text-sm text-[#8D99AE] text-center py-4">No photos yet.</p>
                    </div>
                </div>
            </div>

            <!-- Delete confirm -->
            <div v-if="deleteTarget" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
                <div class="bg-[#0D0D1A] border border-white/10 rounded-2xl w-full max-w-sm p-6 text-center">
                    <p class="text-white font-semibold mb-2">Delete subcontractor?</p>
                    <p class="text-sm text-[#8D99AE] mb-5">This will also remove all their photos. This action cannot be undone.</p>
                    <div class="flex justify-center gap-3">
                        <button @click="deleteTarget = null" class="px-4 py-2 text-sm border border-white/10 rounded-lg text-[#8D99AE] hover:text-white transition-colors">Cancel</button>
                        <button @click="doDelete" class="px-4 py-2 text-sm font-medium bg-[#EF233C] text-white rounded-lg hover:bg-red-500 transition-colors">Delete</button>
                    </div>
                </div>
            </div>
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
} from '@heroicons/vue/24/outline';

const props = defineProps({
    subcontractors: { type: Array, default: () => [] },
    trades:         { type: Array, default: () => [] },
    filters:        { type: Object, default: () => ({}) },
    canEdit:        { type: Boolean, default: false },
});

// Filters
const search      = ref(props.filters.search ?? '');
const filterTrade  = ref(props.filters.trade ?? '');
const filterStatus = ref(props.filters.status ?? '');

const filtered = computed(() => {
    let list = props.subcontractors;
    const q = search.value.toLowerCase();
    if (q) {
        list = list.filter(s =>
            s.name.toLowerCase().includes(q) ||
            (s.company ?? '').toLowerCase().includes(q) ||
            s.trade.toLowerCase().includes(q)
        );
    }
    if (filterTrade.value)  list = list.filter(s => s.trade === filterTrade.value);
    if (filterStatus.value) list = list.filter(s => filterStatus.value === 'active' ? s.is_active : !s.is_active);
    return list;
});

// Add / Edit modal
const processing = ref(false);
const modal = ref({ open: false, editing: null });
const emptyForm = () => ({
    name: '', trade: '', company: '', email: '', phone: '',
    qualification_verified: false, insurance_verified: false,
    notes: '', is_active: true,
});
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

function closeModal() {
    modal.value.open = false;
}

function submitModal() {
    processing.value = true;
    if (modal.value.editing) {
        router.put(
            route('subcontractors.update', modal.value.editing.id),
            form.value,
            { preserveScroll: true, onFinish: () => { processing.value = false; closeModal(); } }
        );
    } else {
        router.post(
            route('subcontractors.store'),
            form.value,
            { preserveScroll: true, onFinish: () => { processing.value = false; closeModal(); } }
        );
    }
}

// Delete
const deleteTarget = ref(null);

function confirmDelete(s) {
    deleteTarget.value = s;
}

function doDelete() {
    if (!deleteTarget.value) return;
    router.delete(route('subcontractors.destroy', deleteTarget.value.id), {
        preserveScroll: true,
        onFinish: () => { deleteTarget.value = null; },
    });
}

// Photos modal
const photosModal   = ref({ open: false, sub: null });
const photoUploading = ref(false);
const photoUpload   = ref({ type: 'before', caption: '' });

const beforePhotos = computed(() => photosModal.value.sub?.photos.filter(p => p.type === 'before') ?? []);
const afterPhotos  = computed(() => photosModal.value.sub?.photos.filter(p => p.type === 'after')  ?? []);

function openPhotos(s) {
    photosModal.value = { open: true, sub: s };
    photoUpload.value = { type: 'before', caption: '' };
}

// Keep photo modal sub in sync when Inertia refreshes props
watch(() => props.subcontractors, (list) => {
    if (photosModal.value.sub) {
        const updated = list.find(s => s.id === photosModal.value.sub.id);
        if (updated) photosModal.value.sub = updated;
    }
}, { deep: true });

function closePhotos() {
    photosModal.value.open = false;
}

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
                event.target.value = '';
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
