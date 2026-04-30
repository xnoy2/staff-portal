<template>
    <div class="space-y-5">
        <!-- Basic info -->
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-sm font-semibold text-gray-700 mb-4 flex items-center gap-2">
                <UserIcon class="w-4 h-4 text-[#EF233C]" /> Basic Information
            </h2>
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
    DocumentTextIcon, XMarkIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    form:        { type: Object, required: true },
    roles:       { type: Array,  required: true },
    isCreate:    { type: Boolean, default: false },
    submitLabel: { type: String,  default: 'Save Changes' },
});

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
