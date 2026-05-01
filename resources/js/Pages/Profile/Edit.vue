<template>
    <AppLayout title="My Profile">
        <div class="max-w-4xl mx-auto space-y-5">

            <!-- Profile header card -->
            <div class="bg-white rounded-xl border border-gray-200 p-5 sm:p-6">
                <div class="flex flex-col sm:flex-row items-center sm:items-start gap-5">
                    <!-- Avatar -->
                    <div class="relative flex-shrink-0">
                        <img
                            :src="avatarPreview ?? profileUser.avatar_url"
                            :alt="profileUser.name"
                            class="w-20 h-20 sm:w-24 sm:h-24 rounded-full object-cover border-4 border-white shadow-md"
                        />
                        <label
                            class="absolute bottom-0 right-0 w-8 h-8 bg-[#EF233C] rounded-full flex items-center justify-center cursor-pointer hover:bg-[#D90429] transition-colors shadow"
                            title="Upload photo"
                        >
                            <CameraIcon class="w-4 h-4 text-white" />
                            <input type="file" accept="image/*" class="hidden" @change="onAvatarChange" />
                        </label>
                    </div>

                    <!-- Name + meta -->
                    <div class="flex-1 text-center sm:text-left min-w-0">
                        <h1 class="text-xl font-bold text-gray-800">{{ profileUser.name }}</h1>
                        <p class="text-sm text-gray-500 mt-0.5 truncate">{{ profileUser.email }}</p>
                        <div class="flex flex-wrap justify-center sm:justify-start gap-2 mt-2">
                            <span
                                v-for="role in profileUser.roles"
                                :key="role"
                                class="text-xs bg-[#EF233C] text-white px-2.5 py-1 rounded-full font-medium capitalize"
                            >
                                {{ role.replace('_', ' ') }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-400 mt-2">
                            Member since {{ formatDate(profileUser.created_at) }}
                            <span v-if="profileUser.hire_date"> · Hired {{ formatDate(profileUser.hire_date) }}</span>
                        </p>
                        <p v-if="profileUser.employee_id" class="mt-1">
                            <span class="text-xs font-mono font-semibold bg-gray-100 text-gray-600 px-2 py-0.5 rounded">{{ profileUser.employee_id }}</span>
                        </p>
                    </div>

                    <!-- QR Code -->
                    <div class="flex flex-col items-center gap-1 flex-shrink-0">
                        <div class="bg-white border-2 border-gray-200 rounded-xl p-2">
                            <img :src="qrCodeUrl" :alt="`QR for ${profileUser.name}`" class="w-20 h-20" />
                        </div>
                        <p class="text-xs text-gray-400">My QR Code</p>
                    </div>
                </div>
            </div>

            <!-- Personal details -->
            <div class="bg-white rounded-xl border border-gray-200 p-5 sm:p-6">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-8 h-8 rounded-lg bg-red-50 flex items-center justify-center flex-shrink-0">
                        <UserIcon class="w-4 h-4 text-[#EF233C]" />
                    </div>
                    <h2 class="text-base font-semibold text-gray-800">Personal Details</h2>
                </div>
                <form @submit.prevent="submitProfile" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Full Name</label>
                            <input v-model="profileForm.name" type="text" class="form-input" autocomplete="name" />
                            <p v-if="profileForm.errors.name" class="mt-1 text-xs text-red-600">{{ profileForm.errors.name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Email Address</label>
                            <input v-model="profileForm.email" type="email" class="form-input" autocomplete="email" />
                            <p v-if="profileForm.errors.email" class="mt-1 text-xs text-red-600">{{ profileForm.errors.email }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Hire Date</label>
                            <input v-model="profileForm.hire_date" type="date" class="form-input" />
                            <p v-if="profileForm.errors.hire_date" class="mt-1 text-xs text-red-600">{{ profileForm.errors.hire_date }}</p>
                        </div>
                    </div>

                    <!-- Emergency contact -->
                    <div class="pt-3 border-t border-gray-100">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Emergency Contact</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Contact Name</label>
                                <input v-model="profileForm.emergency_contact_name" type="text" class="form-input" placeholder="e.g. Jane Doe" />
                                <p v-if="profileForm.errors.emergency_contact_name" class="mt-1 text-xs text-red-600">{{ profileForm.errors.emergency_contact_name }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Contact Phone</label>
                                <input v-model="profileForm.emergency_contact_phone" type="tel" class="form-input" placeholder="e.g. +44 7700 900000" />
                                <p v-if="profileForm.errors.emergency_contact_phone" class="mt-1 text-xs text-red-600">{{ profileForm.errors.emergency_contact_phone }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Certifications -->
                    <div class="pt-3 border-t border-gray-100">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Certifications</p>
                        <div class="flex flex-wrap gap-2 mb-2">
                            <span
                                v-for="(cert, i) in profileForm.certifications"
                                :key="i"
                                class="inline-flex items-center gap-1 bg-green-50 border border-green-200 text-green-800 text-xs px-2.5 py-1 rounded-full"
                            >
                                {{ cert }}
                                <button type="button" @click="removeCert(i)" class="hover:text-red-500 transition-colors ml-0.5">
                                    <XMarkIcon class="w-3 h-3" />
                                </button>
                            </span>
                            <span v-if="profileForm.certifications.length === 0" class="text-xs text-gray-400">No certifications added yet.</span>
                        </div>
                        <div class="flex gap-2">
                            <input v-model="newCert" type="text" placeholder="Add certification…" class="form-input flex-1 text-sm" @keydown.enter.prevent="addCert" />
                            <button type="button" @click="addCert" :disabled="!newCert.trim()" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-2 rounded-lg text-sm transition-colors disabled:opacity-40">Add</button>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="pt-3 border-t border-gray-100">
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Notes</label>
                        <textarea v-model="profileForm.notes" rows="3" class="form-input resize-none" placeholder="Any additional notes…" />
                        <p v-if="profileForm.errors.notes" class="mt-1 text-xs text-red-600">{{ profileForm.errors.notes }}</p>
                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="submit" :disabled="profileForm.processing" class="btn-primary">
                            {{ profileForm.processing ? 'Saving…' : 'Save Changes' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Projects -->
            <div class="bg-white rounded-xl border border-gray-200 p-5 sm:p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-8 h-8 rounded-lg bg-red-50 flex items-center justify-center shrink-0">
                        <FolderIcon class="w-4 h-4 text-[#EF233C]" />
                    </div>
                    <h2 class="text-base font-semibold text-gray-800">My Projects</h2>
                    <span class="text-xs text-gray-400 ml-auto">{{ projects.length }} project{{ projects.length !== 1 ? 's' : '' }}</span>
                </div>
                <div v-if="!projects.length" class="text-center py-6">
                    <p class="text-sm text-gray-400">You are not assigned to any projects yet.</p>
                </div>
                <div v-else class="space-y-2">
                    <Link
                        v-for="project in projects"
                        :key="project.id"
                        :href="route('projects.show', project.id)"
                        class="flex items-center gap-3 p-3 rounded-lg border border-gray-100 hover:border-gray-200 hover:bg-gray-50 transition-all group"
                    >
                        <div :class="['w-1 h-8 rounded-full shrink-0', statusBarClass(project.status)]" />
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-1.5 mb-0.5">
                                <span :class="businessClass(project.business)">{{ project.business?.toUpperCase() }}</span>
                                <span class="text-sm font-medium text-gray-800 truncate group-hover:text-[#EF233C] transition-colors">{{ project.name }}</span>
                            </div>
                            <p class="text-xs text-gray-400 truncate">{{ project.customer }}</p>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <span :class="project.role === 'lead'
                                ? 'text-xs font-medium px-2 py-0.5 rounded-full bg-amber-100 text-amber-700'
                                : 'text-xs font-medium px-2 py-0.5 rounded-full bg-blue-50 text-blue-600'">
                                {{ project.role === 'lead' ? 'Lead' : 'Support' }}
                            </span>
                            <span :class="statusClass(project.status)">{{ statusLabel(project.status) }}</span>
                        </div>
                    </Link>
                </div>
            </div>

            <!-- Change password -->
            <div class="bg-white rounded-xl border border-gray-200 p-5 sm:p-6">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-8 h-8 rounded-lg bg-red-50 flex items-center justify-center flex-shrink-0">
                        <LockClosedIcon class="w-4 h-4 text-[#EF233C]" />
                    </div>
                    <h2 class="text-base font-semibold text-gray-800">Change Password</h2>
                </div>
                <form @submit.prevent="submitPassword" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Current Password</label>
                        <input v-model="passwordForm.current_password" type="password" autocomplete="current-password" class="form-input" />
                        <p v-if="passwordForm.errors.current_password" class="mt-1 text-xs text-red-600">{{ passwordForm.errors.current_password }}</p>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">New Password</label>
                            <input v-model="passwordForm.password" type="password" autocomplete="new-password" class="form-input" placeholder="Min. 8 chars, mixed case + numbers" />
                            <p v-if="passwordForm.errors.password" class="mt-1 text-xs text-red-600">{{ passwordForm.errors.password }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Confirm New Password</label>
                            <input v-model="passwordForm.password_confirmation" type="password" autocomplete="new-password" class="form-input" />
                            <p v-if="passwordForm.errors.password_confirmation" class="mt-1 text-xs text-red-600">{{ passwordForm.errors.password_confirmation }}</p>
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" :disabled="passwordForm.processing" class="btn-primary">
                            {{ passwordForm.processing ? 'Updating…' : 'Update Password' }}
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    CameraIcon, UserIcon, LockClosedIcon,
    XMarkIcon, FolderIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    profileUser: { type: Object, required: true },
    projects:    { type: Array,  default: () => [] },
});

const qrCodeUrl = computed(() => {
    const payload = encodeURIComponent(btoa(String(props.profileUser.id)));
    return `https://api.qrserver.com/v1/create-qr-code/?size=80x80&data=${payload}&margin=6`;
});

const avatarPreview = ref(null);
let avatarFile = null;

function onAvatarChange(e) {
    const file = e.target.files[0];
    if (!file) return;
    avatarFile = file;
    avatarPreview.value = URL.createObjectURL(file);
}

const profileForm = useForm({
    name:                    props.profileUser.name,
    email:                   props.profileUser.email,
    hire_date:               props.profileUser.hire_date ?? '',
    emergency_contact_name:  props.profileUser.emergency_contact_name ?? '',
    emergency_contact_phone: props.profileUser.emergency_contact_phone ?? '',
    certifications:          [...(props.profileUser.certifications ?? [])],
    notes:                   props.profileUser.notes ?? '',
    avatar:                  null,
});

const newCert = ref('');

function addCert() {
    const val = newCert.value.trim();
    if (val && !profileForm.certifications.includes(val)) profileForm.certifications.push(val);
    newCert.value = '';
}

function removeCert(index) {
    profileForm.certifications.splice(index, 1);
}

function submitProfile() {
    if (avatarFile) profileForm.avatar = avatarFile;
    profileForm.post(route('profile.update'), {
        forceFormData: true,
        onSuccess: () => { avatarFile = null; avatarPreview.value = null; },
    });
}

const passwordForm = useForm({
    current_password:      '',
    password:              '',
    password_confirmation: '',
});

function submitPassword() {
    passwordForm.put(route('profile.password'), {
        onSuccess: () => passwordForm.reset(),
    });
}

function formatDate(d) {
    if (!d) return '';
    return new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: '2-digit', year: 'numeric' });
}

const statusLabels = { planning: 'Planning', active: 'Active', on_hold: 'On Hold', complete: 'Complete' };
const statusClasses = {
    planning: 'inline-flex text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full font-medium',
    active:   'inline-flex text-xs bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full font-medium',
    on_hold:  'inline-flex text-xs bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full font-medium',
    complete: 'inline-flex text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full font-medium',
};
function statusLabel(s) { return statusLabels[s] ?? s; }
function statusClass(s) { return statusClasses[s] ?? statusClasses.planning; }

const statusBarClasses = { planning: 'bg-gray-300', active: 'bg-emerald-400', on_hold: 'bg-amber-400', complete: 'bg-blue-400' };
function statusBarClass(s) { return statusBarClasses[s] ?? 'bg-gray-200'; }

const businessClasses = {
    bcf: 'text-xs font-bold px-1.5 py-0.5 rounded bg-[#EF233C]/10 text-[#EF233C] shrink-0',
    bgr: 'text-xs font-bold px-1.5 py-0.5 rounded bg-blue-100 text-blue-700 shrink-0',
};
function businessClass(b) { return businessClasses[b] ?? businessClasses.bcf; }
</script>

<style scoped>
.form-input {
    @apply w-full rounded-lg border-gray-200 text-sm focus:ring-[#EF233C] focus:border-[#EF233C];
}
.btn-primary {
    @apply bg-[#EF233C] hover:bg-[#D90429] text-white text-sm font-medium px-5 py-2 rounded-lg transition-colors disabled:opacity-60;
}
</style>
