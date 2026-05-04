<template>
    <div class="space-y-5">
        <!-- Basic info -->
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-sm font-semibold text-gray-700 mb-4 flex items-center gap-2">
                <UserIcon class="w-4 h-4 text-[#EF233C]" /> Basic Information
            </h2>

            <!-- Avatar upload -->
            <div class="flex items-center gap-4 mb-5 pb-5 border-b border-gray-100">
                <div class="relative flex-shrink-0">
                    <img
                        :src="avatarPreview || form.current_avatar_url || `https://ui-avatars.com/api/?name=${encodeURIComponent(form.name || 'Staff')}&background=3B6D11&color=fff&size=128`"
                        class="w-16 h-16 rounded-full object-cover border-2 border-gray-200"
                        alt="Avatar preview"
                    />
                    <label
                        for="avatar-upload"
                        class="absolute -bottom-1 -right-1 w-6 h-6 bg-[#EF233C] hover:bg-[#D90429] rounded-full flex items-center justify-center cursor-pointer transition-colors"
                        title="Change photo"
                    >
                        <CameraIcon class="w-3 h-3 text-white" />
                    </label>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-700">Profile Photo</p>
                    <p class="text-xs text-gray-400 mt-0.5">JPG, PNG or WebP · max 2 MB</p>
                    <p v-if="avatarPreview" class="text-xs text-emerald-600 mt-1 font-medium">New photo selected</p>
                </div>
                <input
                    id="avatar-upload"
                    type="file"
                    accept="image/jpeg,image/png,image/webp"
                    class="hidden"
                    @change="onAvatarChange"
                />
                <p v-if="form.errors.avatar" class="mt-1 text-xs text-red-600">{{ form.errors.avatar }}</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Full Name -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Full Name</label>
                    <input v-model="form.name" type="text" class="form-input" placeholder="e.g. John Smith" />
                    <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
                </div>
                <!-- Email -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Email Address</label>
                    <input v-model="form.email" type="email" class="form-input" placeholder="john@example.com" />
                    <p v-if="form.errors.email" class="mt-1 text-xs text-red-600">{{ form.errors.email }}</p>
                </div>
                <!-- Role -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Role</label>
                    <select v-model="form.role" class="form-input">
                        <option value="">Select a role…</option>
                        <option v-for="r in roles" :key="r" :value="r" class="capitalize">
                            {{ r.replace('_', ' ') }}
                        </option>
                    </select>
                    <p v-if="form.errors.role" class="mt-1 text-xs text-red-600">{{ form.errors.role }}</p>
                </div>
                <!-- Hire Date -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Hire Date</label>
                    <input v-model="form.hire_date" type="date" class="form-input" />
                    <p v-if="form.errors.hire_date" class="mt-1 text-xs text-red-600">{{ form.errors.hire_date }}</p>
                </div>
                <!-- Annual Leave Entitlement -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Annual Leave (days)</label>
                    <input v-model.number="form.annual_leave_days" type="number" min="0" max="365" class="form-input" placeholder="28" />
                    <p v-if="form.errors.annual_leave_days" class="mt-1 text-xs text-red-600">{{ form.errors.annual_leave_days }}</p>
                </div>
                <!-- Hourly Rate -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Hourly Rate (£)</label>
                    <input v-model="form.hourly_rate" type="number" min="0" step="0.01" class="form-input" placeholder="0.00" />
                    <p v-if="form.errors.hourly_rate" class="mt-1 text-xs text-red-600">{{ form.errors.hourly_rate }}</p>
                </div>
                <!-- Contracted Hours -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Contracted Hours / Week</label>
                    <input v-model.number="form.contracted_hours" type="number" min="1" max="168" class="form-input" placeholder="40" />
                    <p v-if="form.errors.contracted_hours" class="mt-1 text-xs text-red-600">{{ form.errors.contracted_hours }}</p>
                </div>
            </div>

            <!-- Toggles -->
            <div class="flex flex-wrap gap-6 mt-5 pt-5 border-t border-gray-100">
                <label class="flex items-center gap-3 cursor-pointer">
                    <div
                        @click="form.is_active = !form.is_active"
                        :class="[
                            'relative w-10 h-6 rounded-full transition-colors',
                            form.is_active ? 'bg-[#EF233C]' : 'bg-gray-300',
                        ]"
                    >
                        <div :class="['absolute top-1 w-4 h-4 bg-white rounded-full shadow transition-transform', form.is_active ? 'translate-x-5' : 'translate-x-1']" />
                    </div>
                    <span class="text-sm text-gray-700">Account active</span>
                </label>

            </div>
        </div>

        <!-- Emergency contact -->
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-sm font-semibold text-gray-700 mb-4 flex items-center gap-2">
                <PhoneIcon class="w-4 h-4 text-[#EF233C]" /> Emergency Contact
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Contact Name</label>
                    <input v-model="form.emergency_contact_name" type="text" class="form-input" placeholder="e.g. Jane Smith" />
                    <p v-if="form.errors.emergency_contact_name" class="mt-1 text-xs text-red-600">{{ form.errors.emergency_contact_name }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Contact Phone</label>
                    <input v-model="form.emergency_contact_phone" type="tel" class="form-input" placeholder="e.g. +44 7700 900000" />
                    <p v-if="form.errors.emergency_contact_phone" class="mt-1 text-xs text-red-600">{{ form.errors.emergency_contact_phone }}</p>
                </div>
            </div>
        </div>

        <!-- Certifications -->
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-sm font-semibold text-gray-700 mb-4 flex items-center gap-2">
                <AcademicCapIcon class="w-4 h-4 text-[#EF233C]" /> Certifications
            </h2>
            <div class="flex flex-wrap gap-2 mb-3">
                <span
                    v-for="(cert, i) in form.certifications"
                    :key="i"
                    class="inline-flex items-center gap-1 bg-green-50 border border-green-200 text-green-800 text-xs px-2.5 py-1 rounded-full"
                >
                    {{ cert }}
                    <button type="button" @click="removeCert(i)" class="hover:text-red-500 transition-colors">
                        <XMarkIcon class="w-3 h-3" />
                    </button>
                </span>
                <span v-if="!form.certifications.length" class="text-xs text-gray-400">None added.</span>
            </div>
            <div class="flex gap-2">
                <input
                    v-model="newCert"
                    type="text"
                    placeholder="Add certification…"
                    class="form-input flex-1"
                    @keydown.enter.prevent="addCert"
                />
                <button
                    type="button"
                    @click="addCert"
                    :disabled="!newCert.trim()"
                    class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-2 rounded-lg text-sm transition-colors disabled:opacity-40"
                >Add</button>
            </div>
        </div>

        <!-- Notes -->
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-sm font-semibold text-gray-700 mb-4 flex items-center gap-2">
                <DocumentTextIcon class="w-4 h-4 text-[#EF233C]" /> Notes
            </h2>
            <textarea v-model="form.notes" rows="3" class="form-input resize-none" placeholder="Internal notes about this staff member…" />
            <p v-if="form.errors.notes" class="mt-1 text-xs text-red-600">{{ form.errors.notes }}</p>
        </div>

        <!-- Submit -->
        <div class="flex items-center justify-between">
            <Link :href="route('staff.index')" class="text-sm text-gray-500 hover:text-gray-700">
                ← Back to Staff
            </Link>
            <button
                type="submit"
                :disabled="form.processing"
                class="bg-[#EF233C] hover:bg-[#D90429] text-white text-sm font-medium px-6 py-2.5 rounded-lg transition-colors disabled:opacity-60"
            >
                {{ form.processing ? 'Saving…' : submitLabel }}
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import {
    UserIcon, PhoneIcon, AcademicCapIcon,
    DocumentTextIcon, XMarkIcon, CameraIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    form:        { type: Object, required: true },
    roles:       { type: Array,  required: true },
    isCreate:    { type: Boolean, default: false },
    submitLabel: { type: String,  default: 'Save Changes' },
});

const avatarPreview = ref(null);
function onAvatarChange(e) {
    const file = e.target.files[0];
    if (!file) return;
    props.form.avatar = file;
    avatarPreview.value = URL.createObjectURL(file);
}

const newCert = ref('');
function addCert() {
    const val = newCert.value.trim();
    if (val && !props.form.certifications.includes(val)) {
        props.form.certifications.push(val);
    }
    newCert.value = '';
}
function removeCert(i) {
    props.form.certifications.splice(i, 1);
}
</script>

<style scoped>
.form-input {
    @apply w-full rounded-lg border-gray-200 text-sm focus:ring-[#EF233C] focus:border-[#EF233C];
}
</style>
