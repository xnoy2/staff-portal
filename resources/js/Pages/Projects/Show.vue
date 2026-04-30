<template>
    <AppLayout :title="project.name">
        <div class="max-w-5xl mx-auto space-y-5">

            <!-- Header -->
            <div class="bg-white rounded-xl border border-gray-200 p-5 sm:p-6">
                <div class="flex flex-col sm:flex-row sm:items-start gap-4">
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center gap-2 mb-1.5">
                            <span
                                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold"
                                :class="project.business === 'bgr' ? 'bg-blue-100 text-blue-700' : 'bg-[#EF233C]/10 text-[#EF233C]'"
                            >
                                {{ project.business?.toUpperCase() }}
                            </span>
                            <span :class="statusClass(project.status)" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium">
                                {{ statusLabel(project.status) }}
                            </span>
                            <span :class="phaseClass(project.phase)" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium">
                                {{ phaseLabel(project.phase) }}
                            </span>
                        </div>
                        <h1 class="text-xl font-bold text-gray-900 truncate">{{ project.name }}</h1>
                        <p class="text-sm text-gray-500 mt-0.5">{{ project.customer }}</p>
                        <p v-if="project.address" class="text-xs text-gray-400 mt-0.5 flex items-center gap-1">
                            <MapPinIcon class="w-3.5 h-3.5 shrink-0" />
                            {{ project.address }}
                        </p>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <Link
                            :href="route('projects.edit', project.id)"
                            class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 px-3 py-2 rounded-lg transition-colors"
                        >
                            <PencilIcon class="w-3.5 h-3.5" /> Edit
                        </Link>
                        <button
                            @click="confirmDelete = true"
                            class="inline-flex items-center gap-1.5 text-sm font-medium text-red-600 bg-red-50 hover:bg-red-100 px-3 py-2 rounded-lg transition-colors"
                        >
                            <TrashIcon class="w-3.5 h-3.5" /> Delete
                        </button>
                    </div>
                </div>

                <!-- Meta row -->
                <div class="mt-4 pt-4 border-t border-gray-100 flex flex-wrap gap-x-6 gap-y-2 text-xs text-gray-500">
                    <span v-if="project.start_date || project.end_date" class="flex items-center gap-1.5">
                        <CalendarIcon class="w-3.5 h-3.5 text-gray-400" />
                        {{ project.start_date ? fmtDate(project.start_date) : '—' }}
                        <span class="text-gray-300">→</span>
                        {{ project.end_date ? fmtDate(project.end_date) : 'TBC' }}
                    </span>
                    <span v-if="project.van" class="flex items-center gap-1.5">
                        <TruckIcon class="w-3.5 h-3.5 text-gray-400" />
                        {{ project.van.registration }} — {{ project.van.make }} {{ project.van.model }}
                    </span>
                    <span class="flex items-center gap-1.5">
                        <UserCircleIcon class="w-3.5 h-3.5 text-gray-400" />
                        Created by {{ project.creator ?? 'Unknown' }}
                    </span>
                </div>
            </div>

            <!-- Budget -->
            <div class="bg-white rounded-xl border border-gray-200 p-5 sm:p-6">
                <h2 class="text-sm font-semibold text-gray-700 mb-4 flex items-center gap-2">
                    <BanknotesIcon class="w-4 h-4 text-[#EF233C]" /> Budget
                </h2>
                <div v-if="project.budget" class="space-y-3">
                    <div class="flex items-end justify-between">
                        <div>
                            <p class="text-2xl font-bold" :class="project.budget_progress >= 100 ? 'text-red-600' : 'text-gray-900'">
                                {{ fmt(project.budget_spent) }}
                                <span class="text-sm font-normal text-gray-400">spent</span>
                            </p>
                            <p class="text-xs text-gray-500 mt-0.5">of {{ fmt(project.budget) }} budget</p>
                        </div>
                        <span
                            class="text-sm font-semibold px-3 py-1 rounded-full"
                            :class="project.budget_progress >= 100 ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-700'"
                        >
                            {{ project.budget_progress }}%
                        </span>
                    </div>
                    <div class="h-2.5 bg-gray-100 rounded-full overflow-hidden">
                        <div
                            class="h-full rounded-full transition-all duration-500"
                            :class="project.budget_progress >= 100 ? 'bg-red-500' : project.budget_progress >= 80 ? 'bg-amber-500' : 'bg-emerald-500'"
                            :style="{ width: Math.min(project.budget_progress, 100) + '%' }"
                        />
                    </div>
                    <p v-if="project.budget_progress >= 100" class="text-xs text-red-600 font-medium flex items-center gap-1">
                        <ExclamationTriangleIcon class="w-3.5 h-3.5" />
                        Over budget by {{ fmt(project.budget_spent - project.budget) }}
                    </p>
                </div>
                <p v-else class="text-sm text-gray-400">No budget set for this project.</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

                <!-- Staff -->
                <div class="bg-white rounded-xl border border-gray-200 p-5 sm:p-6">
                    <h2 class="text-sm font-semibold text-gray-700 mb-4 flex items-center gap-2">
                        <UsersIcon class="w-4 h-4 text-[#EF233C]" /> Assigned Staff
                        <span class="ml-auto text-xs font-normal text-gray-400">{{ project.staff.length }} member{{ project.staff.length !== 1 ? 's' : '' }}</span>
                    </h2>
                    <div v-if="project.staff.length" class="space-y-2">
                        <div
                            v-for="member in project.staff"
                            :key="member.id"
                            class="flex items-center gap-3 p-2.5 rounded-lg bg-gray-50"
                        >
                            <img
                                v-if="member.avatar_url"
                                :src="member.avatar_url"
                                :alt="member.name"
                                class="w-8 h-8 rounded-full object-cover shrink-0"
                            />
                            <div v-else class="w-8 h-8 rounded-full bg-[#EF233C]/10 flex items-center justify-center shrink-0">
                                <span class="text-xs font-bold text-[#EF233C]">{{ initials(member.name) }}</span>
                            </div>
                            <span class="flex-1 text-sm text-gray-800 font-medium">{{ member.name }}</span>
                            <span
                                class="text-xs font-medium px-2 py-0.5 rounded-full"
                                :class="member.role === 'lead' ? 'bg-amber-100 text-amber-700' : 'bg-blue-50 text-blue-600'"
                            >
                                {{ member.role === 'lead' ? 'Lead' : 'Support' }}
                            </span>
                        </div>
                    </div>
                    <p v-else class="text-sm text-gray-400 text-center py-4">No staff assigned.</p>
                </div>

                <!-- Checklist -->
                <div class="bg-white rounded-xl border border-gray-200 p-5 sm:p-6">
                    <div class="flex items-center gap-2 mb-4">
                        <h2 class="text-sm font-semibold text-gray-700 flex items-center gap-2">
                            <ClipboardDocumentListIcon class="w-4 h-4 text-[#EF233C]" /> Checklist
                        </h2>
                        <span class="ml-auto text-xs font-normal text-gray-400">
                            {{ project.checklist.filter(i => i.is_completed).length }}/{{ project.checklist.length }}
                        </span>
                    </div>

                    <!-- Progress bar -->
                    <div v-if="project.checklist.length" class="h-1.5 bg-gray-100 rounded-full overflow-hidden mb-4">
                        <div
                            class="h-full bg-emerald-500 rounded-full transition-all duration-500"
                            :style="{ width: checklistProgress + '%' }"
                        />
                    </div>

                    <div class="space-y-1 max-h-64 overflow-y-auto">
                        <div v-if="!project.checklist.length" class="text-sm text-gray-400 text-center py-4">No checklist items yet.</div>
                        <div
                            v-for="item in project.checklist"
                            :key="item.id"
                            class="flex items-start gap-2.5 group rounded-lg px-2 py-2 hover:bg-gray-50 transition-colors"
                        >
                            <button
                                @click="toggleItem(item)"
                                class="mt-0.5 shrink-0 w-4 h-4 rounded border-2 flex items-center justify-center transition-colors"
                                :class="item.is_completed ? 'bg-emerald-500 border-emerald-500' : 'border-gray-300 hover:border-emerald-400'"
                            >
                                <CheckIcon v-if="item.is_completed" class="w-2.5 h-2.5 text-white" />
                            </button>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <p class="text-sm leading-snug" :class="item.is_completed ? 'line-through text-gray-400' : 'text-gray-700'">
                                        {{ item.title }}
                                    </p>
                                    <span v-if="item.job_id" class="inline-flex items-center gap-1 text-[10px] font-semibold bg-blue-50 text-blue-600 border border-blue-200 px-1.5 py-0.5 rounded-full leading-none flex-shrink-0">
                                        <BriefcaseIcon class="w-2.5 h-2.5" /> Job
                                    </span>
                                </div>
                                <p v-if="item.is_completed && item.completed_by" class="text-xs text-gray-400 mt-0.5">
                                    {{ item.completed_by }} · {{ item.completed_at }}
                                </p>
                            </div>
                            <button
                                @click="deleteItem(item)"
                                class="opacity-0 group-hover:opacity-100 transition-opacity text-gray-400 hover:text-red-500 shrink-0"
                            >
                                <XMarkIcon class="w-3.5 h-3.5" />
                            </button>
                        </div>
                    </div>

                    <!-- Add item -->
                    <form @submit.prevent="addItem" class="mt-3 flex gap-2">
                        <input
                            v-model="newItemTitle"
                            type="text"
                            placeholder="Add checklist item…"
                            maxlength="255"
                            class="flex-1 text-sm border-gray-200 rounded-lg focus:ring-[#EF233C] focus:border-[#EF233C] py-1.5"
                        />
                        <button
                            type="submit"
                            :disabled="!newItemTitle.trim() || addingItem"
                            class="bg-[#EF233C] hover:bg-[#D90429] disabled:opacity-50 text-white text-sm px-3 py-1.5 rounded-lg transition-colors shrink-0"
                        >
                            <PlusIcon class="w-4 h-4" />
                        </button>
                    </form>
                </div>
            </div>

            <!-- Notes -->
            <div v-if="project.notes" class="bg-white rounded-xl border border-gray-200 p-5 sm:p-6">
                <h2 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
                    <DocumentTextIcon class="w-4 h-4 text-[#EF233C]" /> Notes
                </h2>
                <p class="text-sm text-gray-600 whitespace-pre-wrap leading-relaxed">{{ project.notes }}</p>
            </div>

        </div>

        <!-- Delete Modal -->
        <BaseModal :open="confirmDelete" @close="confirmDelete = false">
            <div class="p-6">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center shrink-0">
                        <ExclamationTriangleIcon class="w-5 h-5 text-red-600" />
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-gray-900">Delete Project</h3>
                        <p class="text-sm text-gray-500 mt-1">
                            Are you sure you want to delete <strong>{{ project.name }}</strong>? This will also remove all checklist items and staff assignments. This action cannot be undone.
                        </p>
                    </div>
                </div>
                <div class="mt-5 flex justify-end gap-3">
                    <button @click="confirmDelete = false" class="text-sm font-medium text-gray-600 hover:text-gray-900 px-4 py-2">
                        Cancel
                    </button>
                    <button @click="submitDelete" class="text-sm font-medium bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition-colors">
                        Delete Project
                    </button>
                </div>
            </div>
        </BaseModal>

    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BaseModal from '@/Components/BaseModal.vue';
import {
    PencilIcon, TrashIcon, MapPinIcon, CalendarIcon, TruckIcon,
    UserCircleIcon, BanknotesIcon, UsersIcon, ClipboardDocumentListIcon,
    DocumentTextIcon, CheckIcon, XMarkIcon, PlusIcon,
    ExclamationTriangleIcon, BriefcaseIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    project: { type: Object, required: true },
});

// ── Delete ────────────────────────────────────────────────────────────
const confirmDelete = ref(false);

function submitDelete() {
    router.delete(route('projects.destroy', props.project.id), {
        onSuccess: () => { confirmDelete.value = false; },
    });
}

// ── Checklist ─────────────────────────────────────────────────────────
const newItemTitle = ref('');
const addingItem   = ref(false);

const checklistProgress = computed(() => {
    if (!props.project.checklist.length) return 0;
    return Math.round(props.project.checklist.filter(i => i.is_completed).length / props.project.checklist.length * 100);
});

function toggleItem(item) {
    router.patch(route('projects.checklist.toggle', { project: props.project.id, item: item.id }), {}, {
        preserveScroll: true,
    });
}

function deleteItem(item) {
    router.delete(route('projects.checklist.delete', { project: props.project.id, item: item.id }), {
        preserveScroll: true,
    });
}

function addItem() {
    if (!newItemTitle.value.trim() || addingItem.value) return;
    addingItem.value = true;
    router.post(route('projects.checklist.add', props.project.id), { title: newItemTitle.value.trim() }, {
        preserveScroll: true,
        onSuccess: () => { newItemTitle.value = ''; },
        onFinish: () => { addingItem.value = false; },
    });
}

// ── Helpers ───────────────────────────────────────────────────────────
function fmt(val) {
    if (val === null || val === undefined || val === '') return '£0.00';
    return '£' + Number(val).toLocaleString('en-GB', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function fmtDate(d) {
    if (!d) return '—';
    return new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: '2-digit', year: 'numeric' });
}

function initials(name) {
    return name.split(' ').map(p => p[0]).slice(0, 2).join('').toUpperCase();
}

const STATUS_LABELS = { planning: 'Planning', active: 'Active', on_hold: 'On Hold', complete: 'Complete' };
const STATUS_CLASSES = {
    planning:  'bg-gray-100 text-gray-600',
    active:    'bg-emerald-100 text-emerald-700',
    on_hold:   'bg-amber-100 text-amber-700',
    complete:  'bg-blue-100 text-blue-700',
};
const PHASE_LABELS  = { planning: 'Planning', installation: 'Installation', inspection: 'Inspection', complete: 'Complete' };
const PHASE_CLASSES = {
    planning:     'bg-purple-50 text-purple-600',
    installation: 'bg-orange-50 text-orange-600',
    inspection:   'bg-cyan-50 text-cyan-600',
    complete:     'bg-teal-50 text-teal-700',
};

const statusLabel = s => STATUS_LABELS[s]  ?? s;
const statusClass = s => STATUS_CLASSES[s] ?? 'bg-gray-100 text-gray-600';
const phaseLabel  = s => PHASE_LABELS[s]   ?? s;
const phaseClass  = s => PHASE_CLASSES[s]  ?? 'bg-gray-100 text-gray-600';
</script>
