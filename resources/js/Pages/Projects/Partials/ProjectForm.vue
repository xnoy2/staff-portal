<template>
    <div class="space-y-5">

        <!-- Basic Info -->
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-sm font-semibold text-gray-700 mb-4 flex items-center gap-2">
                <FolderIcon class="w-4 h-4 text-[#EF233C]" /> Project Details
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Business</label>
                    <div class="flex gap-3">
                        <label
                            v-for="opt in [{ value: 'bcf', label: 'BCF' }, { value: 'bgr', label: 'BGR' }]"
                            :key="opt.value"
                            class="flex-1 flex items-center justify-center gap-2 border-2 rounded-lg py-2.5 cursor-pointer transition-colors text-sm font-semibold"
                            :class="form.business === opt.value
                                ? 'border-[#EF233C] bg-[#EF233C]/5 text-[#EF233C]'
                                : 'border-gray-200 text-gray-500 hover:border-gray-300'"
                        >
                            <input type="radio" :value="opt.value" v-model="form.business" class="sr-only" />
                            {{ opt.label }}
                        </label>
                    </div>
                    <p v-if="form.errors.business" class="mt-1 text-xs text-red-600">{{ form.errors.business }}</p>
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Project Name</label>
                    <input v-model="form.name" type="text" class="form-input" placeholder="e.g. Ballycastle Primary School — Phase 2" />
                    <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Customer / Client</label>
                    <input v-model="form.customer" type="text" class="form-input" placeholder="e.g. Ballycastle Primary School" />
                    <p v-if="form.errors.customer" class="mt-1 text-xs text-red-600">{{ form.errors.customer }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Site Address</label>
                    <input v-model="form.address" type="text" class="form-input" placeholder="e.g. 12 Shore St, Ballycastle" />
                    <p v-if="form.errors.address" class="mt-1 text-xs text-red-600">{{ form.errors.address }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Status</label>
                    <select v-model="form.status" class="form-input">
                        <option value="planning">Planning</option>
                        <option value="active">Active</option>
                        <option value="on_hold">On Hold</option>
                        <option value="complete">Complete</option>
                    </select>
                    <p v-if="form.errors.status" class="mt-1 text-xs text-red-600">{{ form.errors.status }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Phase</label>
                    <select v-model="form.phase" class="form-input">
                        <option value="planning">Planning</option>
                        <option value="installation">Installation</option>
                        <option value="inspection">Inspection</option>
                        <option value="complete">Complete</option>
                    </select>
                    <p v-if="form.errors.phase" class="mt-1 text-xs text-red-600">{{ form.errors.phase }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Start Date</label>
                    <input v-model="form.start_date" type="date" class="form-input" />
                    <p v-if="form.errors.start_date" class="mt-1 text-xs text-red-600">{{ form.errors.start_date }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">End Date</label>
                    <input v-model="form.end_date" type="date" class="form-input" />
                    <p v-if="form.errors.end_date" class="mt-1 text-xs text-red-600">{{ form.errors.end_date }}</p>
                </div>
            </div>
        </div>

        <!-- Budget & Van -->
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-sm font-semibold text-gray-700 mb-4 flex items-center gap-2">
                <BanknotesIcon class="w-4 h-4 text-[#EF233C]" /> Budget & Resources
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Total Budget (£)</label>
                    <input v-model="form.budget" type="number" step="0.01" min="0" class="form-input" placeholder="0.00" />
                    <p v-if="form.errors.budget" class="mt-1 text-xs text-red-600">{{ form.errors.budget }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Budget Spent (£)</label>
                    <input v-model="form.budget_spent" type="number" step="0.01" min="0" class="form-input" placeholder="0.00" />
                    <p v-if="form.errors.budget_spent" class="mt-1 text-xs text-red-600">{{ form.errors.budget_spent }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Assigned Van</label>
                    <select v-model="form.van_id" class="form-input">
                        <option :value="null">No van assigned</option>
                        <option v-for="van in vans" :key="van.id" :value="van.id">
                            {{ van.registration }} — {{ van.make }} {{ van.model }}
                        </option>
                    </select>
                    <p v-if="form.errors.van_id" class="mt-1 text-xs text-red-600">{{ form.errors.van_id }}</p>
                </div>
            </div>
        </div>

        <!-- Staff Assignment -->
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-sm font-semibold text-gray-700 mb-4 flex items-center gap-2">
                <UsersIcon class="w-4 h-4 text-[#EF233C]" /> Assigned Staff
            </h2>

            <!-- Search -->
            <div class="relative mb-3">
                <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                <input v-model="staffSearch" type="text" placeholder="Search staff…" class="w-full pl-9 text-sm border-gray-200 rounded-lg focus:ring-[#EF233C] focus:border-[#EF233C]" />
            </div>

            <div class="max-h-56 overflow-y-auto divide-y divide-gray-50 border border-gray-100 rounded-lg">
                <div v-if="filteredStaff.length === 0" class="py-6 text-center text-xs text-gray-400">No staff found.</div>
                <label
                    v-for="member in filteredStaff"
                    :key="member.id"
                    class="flex items-center gap-3 px-4 py-2.5 hover:bg-gray-50 cursor-pointer"
                >
                    <input
                        type="checkbox"
                        :value="member.id"
                        v-model="form.staff_ids"
                        class="rounded border-gray-300 text-[#EF233C] focus:ring-[#EF233C]"
                    />
                    <span class="flex-1 text-sm text-gray-700">{{ member.name }}</span>
                    <!-- Role selector shown only when checked -->
                    <select
                        v-if="form.staff_ids.includes(member.id)"
                        v-model="form.staff_roles[member.id]"
                        class="text-xs border-gray-200 rounded-lg focus:ring-[#EF233C] focus:border-[#EF233C] py-1"
                        @click.stop
                    >
                        <option value="support">Support</option>
                        <option value="lead">Lead</option>
                    </select>
                </label>
            </div>

            <p v-if="form.staff_ids.length" class="mt-2 text-xs text-gray-500">
                {{ form.staff_ids.length }} staff member{{ form.staff_ids.length !== 1 ? 's' : '' }} assigned
            </p>
        </div>

        <!-- Notes -->
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-sm font-semibold text-gray-700 mb-4 flex items-center gap-2">
                <DocumentTextIcon class="w-4 h-4 text-[#EF233C]" /> Notes
            </h2>
            <textarea v-model="form.notes" rows="4" class="form-input resize-none" placeholder="Internal notes, special instructions, access details…" />
            <p v-if="form.errors.notes" class="mt-1 text-xs text-red-600">{{ form.errors.notes }}</p>
        </div>

        <!-- Submit -->
        <div class="flex items-center justify-between">
            <Link :href="route('projects.index')" class="text-sm text-gray-500 hover:text-gray-700">
                ← Back to Projects
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
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import {
    FolderIcon, BanknotesIcon, UsersIcon,
    DocumentTextIcon, MagnifyingGlassIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    form:        { type: Object, required: true },
    staffList:   { type: Array,  default: () => [] },
    vans:        { type: Array,  default: () => [] },
    submitLabel: { type: String, default: 'Save Changes' },
});

const staffSearch = ref('');

const filteredStaff = computed(() =>
    props.staffList.filter(m =>
        m.name.toLowerCase().includes(staffSearch.value.toLowerCase())
    )
);
</script>

<style scoped>
.form-input {
    @apply w-full rounded-lg border-gray-200 text-sm focus:ring-[#EF233C] focus:border-[#EF233C];
}
</style>
