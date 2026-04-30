<template>
    <AppLayout :title="van.registration">
        <div class="max-w-4xl mx-auto space-y-5">

            <!-- Header card -->
            <div class="bg-white rounded-xl border border-gray-200 p-5 sm:p-6">
                <div class="flex items-start gap-5 flex-wrap">
                    <div class="w-16 h-16 rounded-2xl bg-[#2B2D42] flex items-center justify-center flex-shrink-0">
                        <TruckIcon class="w-8 h-8 text-white" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h1 class="text-2xl font-bold text-gray-900 tracking-wider">{{ van.registration }}</h1>
                            <span :class="van.is_active ? 'bg-green-50 text-green-700 border-green-200' : 'bg-gray-100 text-gray-500 border-gray-200'" class="inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-1 rounded-full border">
                                <span :class="van.is_active ? 'bg-green-500' : 'bg-gray-400'" class="w-1.5 h-1.5 rounded-full"></span>
                                {{ van.is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                        <p class="text-gray-500 mt-0.5">{{ van.make }} {{ van.model }}<span v-if="van.year" class="text-gray-400"> · {{ van.year }}</span></p>
                        <p v-if="van.notes" class="text-sm text-gray-400 mt-2 italic">{{ van.notes }}</p>
                    </div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <button
                            @click="toggleActive"
                            :class="van.is_active ? 'border-amber-200 text-amber-600 hover:bg-amber-50' : 'border-green-200 text-green-700 hover:bg-green-50'"
                            class="text-sm font-medium px-4 py-2 rounded-lg border transition-colors"
                        >
                            {{ van.is_active ? 'Deactivate' : 'Activate' }}
                        </button>
                        <Link :href="route('vans.edit', van.id)" class="bg-[#2B2D42] hover:bg-[#3d405e] text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors flex items-center gap-1.5">
                            <PencilIcon class="w-4 h-4" /> Edit
                        </Link>
                    </div>
                </div>

                <!-- Stats row -->
                <div class="grid grid-cols-3 gap-2 sm:gap-4 mt-5 pt-5 border-t border-gray-100">
                    <div>
                        <p class="text-xs text-gray-400">Projects</p>
                        <p class="text-lg sm:text-xl font-bold text-gray-800 mt-0.5">{{ van.projects_count }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">Allocations</p>
                        <p class="text-lg sm:text-xl font-bold text-gray-800 mt-0.5">{{ allocations.length }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">Recent Jobs</p>
                        <p class="text-lg sm:text-xl font-bold text-gray-800 mt-0.5">{{ recentJobs.length }}</p>
                    </div>
                </div>
            </div>

            <!-- Allocations -->
            <div class="bg-white rounded-xl border border-gray-200 p-5 sm:p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-sm font-semibold text-gray-700 flex items-center gap-2">
                        <CalendarDaysIcon class="w-4 h-4 text-[#EF233C]" /> Allocations
                    </h2>
                    <button @click="openAdd" class="inline-flex items-center gap-1.5 text-xs font-medium bg-[#EF233C] hover:bg-[#D90429] text-white px-3 py-1.5 rounded-lg transition-colors">
                        <PlusIcon class="w-3.5 h-3.5" /> Add Allocation
                    </button>
                </div>

                <!-- Tab filter -->
                <div class="flex gap-1 bg-gray-100 rounded-lg p-1 mb-4 w-fit">
                    <button
                        v-for="tab in allocationTabs"
                        :key="tab.value"
                        @click="allocationFilter = tab.value"
                        :class="['text-xs font-medium px-3 py-1 rounded-md transition-colors', allocationFilter === tab.value ? 'bg-white text-gray-800 shadow-sm' : 'text-gray-500 hover:text-gray-700']"
                    >
                        {{ tab.label }}
                        <span v-if="tab.count > 0" class="ml-1 text-[10px] font-bold text-gray-400">({{ tab.count }})</span>
                    </button>
                </div>

                <div v-if="filteredAllocations.length === 0" class="text-sm text-gray-400 text-center py-6">
                    No {{ allocationFilter !== 'all' ? allocationFilter : '' }} allocations found.
                </div>
                <div v-else class="space-y-2">
                    <div
                        v-for="alloc in filteredAllocations"
                        :key="alloc.id"
                        class="flex items-start gap-3 p-3.5 rounded-xl border transition-all"
                        :class="allocationRowClass(alloc.status)"
                    >
                        <!-- Status pill -->
                        <div class="flex-shrink-0 mt-0.5">
                            <span :class="allocationStatusBadge(alloc.status)" class="text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wide">
                                {{ alloc.status }}
                            </span>
                        </div>

                        <!-- Details -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span v-if="alloc.project" :class="businessClass(alloc.project.business)" class="text-[10px] font-bold px-1.5 py-0.5 rounded">
                                    {{ alloc.project.business?.toUpperCase() }}
                                </span>
                                <span class="text-sm font-semibold text-gray-800 truncate">
                                    {{ alloc.project ? alloc.project.name : (alloc.purpose || 'No project / purpose') }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-500 mt-0.5 flex items-center gap-1">
                                <CalendarIcon class="w-3 h-3" />
                                {{ formatDate(alloc.allocated_from) }} → {{ formatDate(alloc.allocated_to) }}
                                <span class="text-gray-300">·</span>
                                {{ dayCount(alloc.allocated_from, alloc.allocated_to) }} day{{ dayCount(alloc.allocated_from, alloc.allocated_to) !== 1 ? 's' : '' }}
                            </p>
                            <p v-if="alloc.notes" class="text-xs text-gray-400 mt-1 italic">{{ alloc.notes }}</p>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-1 flex-shrink-0">
                            <button @click="openEdit(alloc)" class="p-2.5 text-gray-400 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                                <PencilIcon class="w-3.5 h-3.5" />
                            </button>
                            <button @click="confirmDelete(alloc)" class="p-2.5 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors">
                                <TrashIcon class="w-3.5 h-3.5" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Staff -->
            <div class="bg-white rounded-xl border border-gray-200 p-5 sm:p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-sm font-semibold text-gray-700 flex items-center gap-2">
                        <UsersIcon class="w-4 h-4 text-[#EF233C]" /> Staff
                    </h2>
                </div>

                <!-- Assigned Staff -->
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">Assigned to this van</p>
                <div v-if="assignedStaff.length === 0" class="text-sm text-gray-400 py-3 mb-3">No staff assigned yet.</div>
                <div v-else class="space-y-2 mb-4">
                    <div
                        v-for="s in assignedStaff"
                        :key="s.id"
                        class="flex items-center gap-3 p-2.5 rounded-lg border border-gray-100 hover:border-gray-200 bg-gray-50/50 transition-all"
                    >
                        <img :src="s.avatar_url" :alt="s.name" class="w-8 h-8 rounded-full object-cover flex-shrink-0" />
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-800 truncate">{{ s.name }}</p>
                            <p class="text-xs text-gray-400 capitalize">{{ s.role.replace('_', ' ') }}</p>
                        </div>
                        <button
                            @click="unassignStaff(s)"
                            class="p-1.5 text-gray-300 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors flex-shrink-0"
                            title="Remove assignment"
                        >
                            <XMarkIcon class="w-3.5 h-3.5" />
                        </button>
                    </div>
                </div>

                <!-- Assign staff input -->
                <div v-if="staffOptions.length > 0" class="flex flex-col sm:flex-row gap-2">
                    <select v-model="staffToAssign" class="flex-1 px-3 py-1.5 text-sm border border-gray-200 rounded-lg focus:ring-[#EF233C] focus:border-[#EF233C]">
                        <option :value="null">— Select staff to assign —</option>
                        <option v-for="s in staffOptions" :key="s.id" :value="s.id">{{ s.name }}</option>
                    </select>
                    <button
                        @click="doAssignStaff"
                        :disabled="!staffToAssign || assigningStaff"
                        class="bg-[#2B2D42] hover:bg-[#3d405e] disabled:opacity-40 text-white text-xs font-medium px-4 py-1.5 rounded-lg transition-colors flex items-center gap-1.5 flex-shrink-0"
                    >
                        <span v-if="assigningStaff" class="w-3 h-3 border-2 border-white/40 border-t-white rounded-full animate-spin" />
                        <PlusIcon v-else class="w-3.5 h-3.5" />
                        Assign
                    </button>
                </div>
                <p v-else-if="assignedStaff.length > 0" class="text-xs text-gray-400 italic">All active staff are already assigned.</p>

                <!-- Usage History from Jobs -->
                <template v-if="staffUsage.length > 0">
                    <div class="border-t border-gray-100 mt-5 pt-4">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-3">Usage history (via jobs)</p>
                        <div class="space-y-2">
                            <div v-for="u in staffUsage" :key="u.id" class="flex items-center gap-3">
                                <img :src="u.avatar_url" :alt="u.name" class="w-7 h-7 rounded-full object-cover flex-shrink-0" />
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-700 truncate">{{ u.name }}</p>
                                    <p class="text-xs text-gray-400">Last used {{ formatDate(u.last_used) }}</p>
                                </div>
                                <span class="text-xs font-semibold text-gray-500 bg-gray-100 px-2 py-0.5 rounded-full flex-shrink-0">
                                    {{ u.job_count }} job{{ u.job_count !== 1 ? 's' : '' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Assigned Projects -->
            <div class="bg-white rounded-xl border border-gray-200 p-5 sm:p-6">
                <h2 class="text-sm font-semibold text-gray-700 mb-4 flex items-center gap-2">
                    <FolderIcon class="w-4 h-4 text-[#EF233C]" /> Assigned Projects
                </h2>
                <div v-if="van.projects.length === 0" class="text-sm text-gray-400 text-center py-6">No projects assigned to this van.</div>
                <div v-else class="space-y-2">
                    <Link
                        v-for="p in van.projects"
                        :key="p.id"
                        :href="route('projects.show', p.id)"
                        class="flex items-center gap-3 p-3 rounded-lg border border-gray-100 hover:border-gray-200 hover:bg-gray-50 transition-all group"
                    >
                        <div :class="statusBarClass(p.status)" class="w-1 h-8 rounded-full flex-shrink-0" />
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-1.5 mb-0.5">
                                <span :class="businessClass(p.business)" class="text-[10px] font-bold px-1.5 py-0.5 rounded">{{ p.business?.toUpperCase() }}</span>
                                <span class="text-sm font-medium text-gray-800 group-hover:text-[#EF233C] transition-colors truncate">{{ p.name }}</span>
                            </div>
                            <p class="text-xs text-gray-400 truncate">{{ p.customer }}</p>
                        </div>
                        <span :class="statusBadgeClass(p.status)" class="text-xs font-medium px-2 py-0.5 rounded-full capitalize flex-shrink-0">
                            {{ p.status.replace('_', ' ') }}
                        </span>
                    </Link>
                </div>
            </div>

            <!-- Recent Jobs -->
            <div class="bg-white rounded-xl border border-gray-200 p-5 sm:p-6">
                <h2 class="text-sm font-semibold text-gray-700 mb-4 flex items-center gap-2">
                    <ClipboardDocumentListIcon class="w-4 h-4 text-[#EF233C]" /> Recent Jobs
                </h2>
                <div v-if="recentJobs.length === 0" class="text-sm text-gray-400 text-center py-6">No jobs recorded for this van yet.</div>
                <div v-else class="divide-y divide-gray-100">
                    <div v-for="job in recentJobs" :key="job.id" class="flex items-center gap-3 py-2.5">
                        <div :class="jobStatusDot(job.status)" class="w-2 h-2 rounded-full flex-shrink-0 mt-0.5" />
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-800 truncate">{{ job.title }}</p>
                            <p class="text-xs text-gray-400">{{ job.date }}<span v-if="job.start_time"> · {{ job.start_time.slice(0,5) }}</span></p>
                        </div>
                        <span :class="jobStatusBadge(job.status)" class="text-xs font-medium px-2 py-0.5 rounded-full capitalize flex-shrink-0">
                            {{ job.status.replace('_', ' ') }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Danger Zone -->
            <div class="bg-white rounded-xl border border-red-200 p-5">
                <h2 class="text-sm font-semibold text-gray-700 mb-1 flex items-center gap-2">
                    <ExclamationTriangleIcon class="w-4 h-4 text-red-500" /> Danger Zone
                </h2>
                <p class="text-xs text-gray-400 mb-3">Permanently remove this van. Projects and jobs using it will have their van unlinked.</p>
                <button v-if="!confirmingDelete" @click="confirmingDelete = true" class="text-sm text-red-600 border border-red-200 hover:bg-red-50 px-4 py-1.5 rounded-lg transition-colors font-medium">
                    Remove Van
                </button>
                <div v-else class="flex items-center gap-3">
                    <span class="text-sm text-gray-600">Are you sure?</span>
                    <button @click="doDelete" class="text-sm bg-red-600 hover:bg-red-700 text-white px-4 py-1.5 rounded-lg transition-colors font-medium">Yes, remove</button>
                    <button @click="confirmingDelete = false" class="text-sm text-gray-500 hover:text-gray-700 transition-colors">Cancel</button>
                </div>
            </div>
        </div>

        <!-- Allocation Modal -->
        <Transition name="modal">
            <div v-if="modal.open" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="closeModal" />
                <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
                    <!-- Header -->
                    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                        <h3 class="text-sm font-semibold text-gray-800">
                            {{ modal.editing ? 'Edit Allocation' : 'Add Allocation' }}
                        </h3>
                        <button @click="closeModal" class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                            <XMarkIcon class="w-4 h-4" />
                        </button>
                    </div>

                    <!-- Body -->
                    <form @submit.prevent="submitModal" class="p-5 space-y-4">
                        <!-- Project -->
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1.5">Project <span class="text-gray-400 font-normal">(optional)</span></label>
                            <select v-model="form.project_id" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-[#EF233C] focus:border-[#EF233C]">
                                <option :value="null">— No project (free allocation) —</option>
                                <option v-for="p in projectOptions" :key="p.id" :value="p.id">
                                    {{ p.name }} ({{ p.customer }})
                                </option>
                            </select>
                        </div>

                        <!-- Purpose (shown if no project) -->
                        <div v-if="!form.project_id">
                            <label class="block text-xs font-medium text-gray-700 mb-1.5">Purpose <span class="text-gray-400 font-normal">(e.g. Maintenance, Storage)</span></label>
                            <input v-model="form.purpose" type="text" maxlength="255" placeholder="Describe the allocation purpose…" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-[#EF233C] focus:border-[#EF233C]" />
                            <p v-if="formErrors.purpose" class="text-xs text-red-500 mt-1">{{ formErrors.purpose }}</p>
                        </div>

                        <!-- Date range -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1.5">From <span class="text-red-500">*</span></label>
                                <input v-model="form.allocated_from" type="date" class="w-full px-3 py-2 text-sm border rounded-lg focus:ring-[#EF233C] focus:border-[#EF233C]" :class="formErrors.allocated_from ? 'border-red-400' : 'border-gray-300'" />
                                <p v-if="formErrors.allocated_from" class="text-xs text-red-500 mt-1">{{ formErrors.allocated_from }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1.5">To <span class="text-red-500">*</span></label>
                                <input v-model="form.allocated_to" type="date" :min="form.allocated_from" class="w-full px-3 py-2 text-sm border rounded-lg focus:ring-[#EF233C] focus:border-[#EF233C]" :class="formErrors.allocated_to ? 'border-red-400' : 'border-gray-300'" />
                                <p v-if="formErrors.allocated_to" class="text-xs text-red-500 mt-1">{{ formErrors.allocated_to }}</p>
                            </div>
                        </div>

                        <!-- Duration preview -->
                        <div v-if="form.allocated_from && form.allocated_to && form.allocated_to >= form.allocated_from" class="text-xs text-gray-500 bg-gray-50 rounded-lg px-3 py-2">
                            Duration: <span class="font-semibold text-gray-700">{{ dayCount(form.allocated_from, form.allocated_to) }} day{{ dayCount(form.allocated_from, form.allocated_to) !== 1 ? 's' : '' }}</span>
                        </div>

                        <!-- Notes -->
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1.5">Notes <span class="text-gray-400 font-normal">(optional)</span></label>
                            <textarea v-model="form.notes" rows="2" maxlength="2000" placeholder="Any additional notes…" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-[#EF233C] focus:border-[#EF233C] resize-none" />
                        </div>

                        <!-- Footer -->
                        <div class="flex items-center justify-end gap-3 pt-1 border-t border-gray-100">
                            <button type="button" @click="closeModal" class="text-sm text-gray-500 hover:text-gray-700 px-3 py-1.5 transition-colors">Cancel</button>
                            <button type="submit" :disabled="submitting" class="bg-[#EF233C] hover:bg-[#D90429] disabled:opacity-60 text-white text-sm font-medium px-5 py-1.5 rounded-lg transition-colors flex items-center gap-2">
                                <span v-if="submitting" class="w-3.5 h-3.5 border-2 border-white/40 border-t-white rounded-full animate-spin" />
                                {{ modal.editing ? 'Save Changes' : 'Add Allocation' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Transition>

        <!-- Delete Allocation Confirm Modal -->
        <Transition name="modal">
            <div v-if="deleteTarget" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="deleteTarget = null" />
                <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-10 h-10 rounded-full bg-red-50 flex items-center justify-center flex-shrink-0">
                            <ExclamationTriangleIcon class="w-5 h-5 text-red-500" />
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-gray-800">Remove Allocation?</h3>
                            <p class="text-xs text-gray-400 mt-0.5">This cannot be undone.</p>
                        </div>
                    </div>
                    <div class="flex gap-3 justify-end mt-5">
                        <button @click="deleteTarget = null" class="text-sm text-gray-500 hover:text-gray-700 px-4 py-1.5 rounded-lg border border-gray-200 transition-colors">Cancel</button>
                        <button @click="doDeleteAllocation" :disabled="submitting" class="text-sm bg-red-600 hover:bg-red-700 disabled:opacity-60 text-white px-4 py-1.5 rounded-lg transition-colors flex items-center gap-2">
                            <span v-if="submitting" class="w-3.5 h-3.5 border-2 border-white/40 border-t-white rounded-full animate-spin" />
                            Yes, Remove
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </AppLayout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    TruckIcon, PencilIcon, FolderIcon, PlusIcon, TrashIcon,
    CalendarDaysIcon, CalendarIcon, XMarkIcon, UsersIcon,
    ClipboardDocumentListIcon, ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    van:            { type: Object, required: true },
    recentJobs:     { type: Array,  default: () => [] },
    allocations:    { type: Array,  default: () => [] },
    projectOptions: { type: Array,  default: () => [] },
    assignedStaff:  { type: Array,  default: () => [] },
    staffUsage:     { type: Array,  default: () => [] },
    staffOptions:   { type: Array,  default: () => [] },
});

// ── Van actions ──────────────────────────────────────────────────────
const confirmingDelete = ref(false);

function toggleActive() {
    router.post(route('vans.toggle-active', props.van.id), {}, { preserveScroll: true });
}
function doDelete() {
    router.delete(route('vans.destroy', props.van.id));
}

// ── Staff assignment ─────────────────────────────────────────────────
const staffToAssign = ref(null);
const assigningStaff = ref(false);

function doAssignStaff() {
    if (!staffToAssign.value) return;
    assigningStaff.value = true;
    router.post(route('vans.staff.assign', props.van.id), { user_id: staffToAssign.value }, {
        preserveScroll: true,
        onSuccess: () => { staffToAssign.value = null; },
        onFinish:  () => { assigningStaff.value = false; },
    });
}

function unassignStaff(staff) {
    router.delete(route('vans.staff.unassign', { van: props.van.id, user: staff.id }), {
        preserveScroll: true,
    });
}

// ── Allocation filter ────────────────────────────────────────────────
const allocationFilter = ref('all');

const allocationTabs = computed(() => [
    { label: 'All',      value: 'all',      count: props.allocations.length },
    { label: 'Current',  value: 'current',  count: props.allocations.filter(a => a.status === 'current').length },
    { label: 'Upcoming', value: 'upcoming', count: props.allocations.filter(a => a.status === 'upcoming').length },
    { label: 'Past',     value: 'past',     count: props.allocations.filter(a => a.status === 'past').length },
]);

const filteredAllocations = computed(() =>
    allocationFilter.value === 'all'
        ? props.allocations
        : props.allocations.filter(a => a.status === allocationFilter.value)
);

// ── Modal state ──────────────────────────────────────────────────────
const modal       = reactive({ open: false, editing: false, id: null });
const formErrors  = ref({});
const submitting  = ref(false);
const deleteTarget = ref(null);

const form = reactive({
    project_id:     null,
    allocated_from: '',
    allocated_to:   '',
    purpose:        '',
    notes:          '',
});

function resetForm() {
    form.project_id     = null;
    form.allocated_from = '';
    form.allocated_to   = '';
    form.purpose        = '';
    form.notes          = '';
    formErrors.value    = {};
}

function openAdd() {
    resetForm();
    modal.editing = false;
    modal.id      = null;
    modal.open    = true;
}

function openEdit(alloc) {
    resetForm();
    form.project_id     = alloc.project?.id ?? null;
    form.allocated_from = alloc.allocated_from;
    form.allocated_to   = alloc.allocated_to;
    form.purpose        = alloc.purpose ?? '';
    form.notes          = alloc.notes ?? '';
    modal.editing = true;
    modal.id      = alloc.id;
    modal.open    = true;
}

function closeModal() {
    modal.open = false;
}

function submitModal() {
    submitting.value = true;
    formErrors.value = {};

    const url = modal.editing
        ? route('vans.allocations.update', { van: props.van.id, allocation: modal.id })
        : route('vans.allocations.store', props.van.id);

    const method = modal.editing ? 'put' : 'post';

    router[method](url, { ...form }, {
        preserveScroll: true,
        onSuccess: () => { closeModal(); },
        onError:   (errors) => { formErrors.value = errors; },
        onFinish:  () => { submitting.value = false; },
    });
}

function confirmDelete(alloc) {
    deleteTarget.value = alloc;
}

function doDeleteAllocation() {
    submitting.value = true;
    router.delete(route('vans.allocations.destroy', { van: props.van.id, allocation: deleteTarget.value.id }), {
        preserveScroll: true,
        onSuccess: () => { deleteTarget.value = null; },
        onFinish:  () => { submitting.value = false; },
    });
}

// ── Helpers ──────────────────────────────────────────────────────────
function formatDate(d) {
    return new Date(d + 'T00:00:00').toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
}

function dayCount(from, to) {
    const ms = new Date(to + 'T00:00:00') - new Date(from + 'T00:00:00');
    return Math.round(ms / 86400000) + 1;
}

const allocationRowClass = s => ({
    current:  'border-emerald-200 bg-emerald-50/40',
    upcoming: 'border-blue-200 bg-blue-50/30',
    past:     'border-gray-100 bg-gray-50/50 opacity-70',
}[s] ?? 'border-gray-100');

const allocationStatusBadge = s => ({
    current:  'bg-emerald-100 text-emerald-700',
    upcoming: 'bg-blue-100 text-blue-700',
    past:     'bg-gray-100 text-gray-500',
}[s] ?? 'bg-gray-100 text-gray-500');

const statusBarClass   = s => ({ planning: 'bg-gray-300', active: 'bg-emerald-400', on_hold: 'bg-amber-400', complete: 'bg-blue-400' }[s] ?? 'bg-gray-200');
const statusBadgeClass = s => ({ planning: 'bg-gray-100 text-gray-500', active: 'bg-emerald-100 text-emerald-700', on_hold: 'bg-amber-100 text-amber-700', complete: 'bg-blue-100 text-blue-700' }[s] ?? 'bg-gray-100 text-gray-500');
const businessClass    = b => b === 'bgr' ? 'bg-blue-100 text-blue-700' : 'bg-[#EF233C]/10 text-[#EF233C]';
const jobStatusDot     = s => ({ scheduled: 'bg-blue-400', in_progress: 'bg-amber-400', completed: 'bg-green-500', cancelled: 'bg-gray-300' }[s] ?? 'bg-gray-300');
const jobStatusBadge   = s => ({ scheduled: 'bg-blue-50 text-blue-600', in_progress: 'bg-amber-50 text-amber-700', completed: 'bg-green-50 text-green-700', cancelled: 'bg-gray-100 text-gray-500' }[s] ?? 'bg-gray-100 text-gray-500');
</script>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: opacity 0.18s ease; }
.modal-enter-from, .modal-leave-to       { opacity: 0; }
</style>
