<template>
    <AppLayout title="Projects">

        <!-- Header -->
        <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
            <div>
                <h1 class="text-lg font-semibold text-gray-900">Projects</h1>
                <p class="text-xs text-gray-500 mt-0.5">{{ projects.total }} project{{ projects.total !== 1 ? 's' : '' }}</p>
            </div>
            <Link
                :href="route('projects.create')"
                class="inline-flex items-center gap-1.5 bg-[#EF233C] text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-[#D90429] transition-colors shadow-sm"
            >
                <PlusIcon class="w-4 h-4" /> New Project
            </Link>
        </div>

        <!-- Status summary cards -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5">
            <button
                v-for="s in statuses"
                :key="s.key"
                @click="setStatusFilter(s.key)"
                :class="[
                    'relative bg-white rounded-xl border p-4 text-left transition-all hover:shadow-md overflow-hidden',
                    filters.status === s.key
                        ? 'border-transparent ring-2 ' + s.ring
                        : 'border-gray-200 hover:border-gray-300',
                ]"
            >
                <div :class="['absolute inset-y-0 left-0 w-1 rounded-l-xl', s.bar]" />
                <div class="flex items-start justify-between pl-2">
                    <div>
                        <p :class="['text-2xl font-bold tracking-tight', s.color]">{{ statusCounts[s.key] ?? 0 }}</p>
                        <p class="text-xs text-gray-500 mt-0.5 font-medium">{{ s.label }}</p>
                    </div>
                    <component :is="s.icon" :class="['w-5 h-5 mt-0.5 opacity-30', s.color]" />
                </div>
            </button>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-xl border border-gray-200 p-4 mb-4">
            <div class="flex flex-col sm:flex-row gap-3 mb-3">
                <div class="flex-1 relative">
                    <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" />
                    <input
                        v-model="filters.search"
                        type="text"
                        placeholder="Search project or customer…"
                        class="w-full pl-9 text-sm border-gray-200 rounded-lg focus:ring-[#EF233C] focus:border-[#EF233C]"
                        @input="applyFilters"
                    />
                </div>
                <div class="flex gap-2 flex-wrap sm:flex-nowrap">
                    <select v-model="filters.status" @change="applyFilters" class="flex-1 sm:w-36 text-sm border-gray-200 rounded-lg focus:ring-[#EF233C] focus:border-[#EF233C]">
                        <option value="">All statuses</option>
                        <option value="planning">Planning</option>
                        <option value="active">Active</option>
                        <option value="on_hold">On Hold</option>
                        <option value="complete">Complete</option>
                    </select>
                    <select v-model="filters.phase" @change="applyFilters" class="flex-1 sm:w-36 text-sm border-gray-200 rounded-lg focus:ring-[#EF233C] focus:border-[#EF233C]">
                        <option value="">All phases</option>
                        <option value="planning">Planning</option>
                        <option value="installation">Installation</option>
                        <option value="inspection">Inspection</option>
                        <option value="complete">Complete</option>
                    </select>
                    <button
                        v-if="hasActiveFilters"
                        @click="clearFilters"
                        class="inline-flex items-center gap-1 text-xs text-gray-400 hover:text-gray-700 border border-gray-200 rounded-lg px-3 transition-colors"
                    >
                        <XMarkIcon class="w-3 h-3" /> Clear
                    </button>
                </div>
            </div>
            <!-- Business toggle -->
            <div class="flex items-center gap-2">
                <span class="text-xs text-gray-400 font-medium shrink-0">Business:</span>
                <div class="flex gap-1.5">
                    <button
                        v-for="opt in businessOpts"
                        :key="opt.value"
                        @click="setBusinessFilter(opt.value)"
                        class="px-3 py-1 text-xs font-semibold rounded-full border transition-all"
                        :style="filters.business === opt.value && opt.color
                            ? { backgroundColor: opt.color, borderColor: opt.color, color: '#fff' }
                            : filters.business === opt.value
                                ? { backgroundColor: '#1f2937', borderColor: '#1f2937', color: '#fff' }
                                : {}"
                        :class="filters.business !== opt.value ? 'border-gray-200 text-gray-500 hover:border-gray-300 bg-white' : ''"
                    >{{ opt.label }}</button>
                </div>
            </div>
        </div>

        <!-- Mobile: Cards -->
        <div class="md:hidden space-y-2.5">
            <div v-if="projects.data.length === 0" class="bg-white rounded-xl border border-gray-200 px-4 py-16 text-center">
                <FolderOpenIcon class="w-10 h-10 mx-auto text-gray-300 mb-2" />
                <p class="text-sm text-gray-400 font-medium">No projects found</p>
                <p class="text-xs text-gray-300 mt-0.5">Try adjusting your filters</p>
            </div>
            <Link
                v-for="project in projects.data"
                :key="project.id"
                :href="route('projects.show', project.id)"
                class="block bg-white rounded-xl border border-gray-200 overflow-hidden hover:shadow-md transition-all hover:border-gray-300 group"
            >
                <div :class="['h-0.5', statusBarClass(project.status)]" />
                <div class="p-4">
                    <div class="flex items-start justify-between gap-2 mb-2.5">
                        <div class="min-w-0">
                            <div class="flex items-center gap-1.5 mb-0.5">
                                <span class="text-xs font-bold px-1.5 py-0.5 rounded shrink-0" :style="businessStyle(project.business)">{{ businessLabel(project.business) }}</span>
                                <p class="font-semibold text-gray-800 text-sm truncate group-hover:text-[#EF233C] transition-colors">{{ project.name }}</p>
                            </div>
                            <p class="text-xs text-gray-400 truncate">{{ project.customer }}</p>
                        </div>
                        <div class="flex flex-col gap-1 items-end shrink-0">
                            <span :class="statusClass(project.status)">{{ statusLabel(project.status) }}</span>
                            <span :class="phaseClass(project.phase)">{{ phaseLabel(project.phase) }}</span>
                        </div>
                    </div>

                    <div v-if="project.budget" class="mb-3">
                        <div class="flex justify-between text-xs mb-1">
                            <span class="text-gray-400">Budget</span>
                            <span :class="project.budget_progress >= 100 ? 'text-red-600 font-semibold' : 'text-gray-500'">
                                £{{ fmt(project.budget_spent) }} / £{{ fmt(project.budget) }}
                            </span>
                        </div>
                        <div class="w-full h-1.5 bg-gray-100 rounded-full overflow-hidden">
                            <div
                                :class="['h-full rounded-full transition-all', budgetBarClass(project.budget_progress)]"
                                :style="{ width: `${Math.min(project.budget_progress, 100)}%` }"
                            />
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3 text-xs text-gray-400">
                            <span v-if="project.staff_count" class="flex items-center gap-1">
                                <UsersIcon class="w-3.5 h-3.5" />{{ project.staff_count }}
                            </span>
                            <span v-if="project.checklist_total" class="flex items-center gap-1">
                                <CheckCircleIcon class="w-3.5 h-3.5" />{{ project.checklist_done }}/{{ project.checklist_total }}
                            </span>
                            <span v-if="project.end_date" class="flex items-center gap-1">
                                <CalendarIcon class="w-3.5 h-3.5" />{{ fmtDate(project.end_date) }}
                            </span>
                        </div>
                        <div class="flex gap-0.5" @click.prevent>
                            <Link :href="route('projects.edit', project.id)" class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                                <PencilIcon class="w-4 h-4" />
                            </Link>
                            <button @click.prevent="confirmDelete(project)" class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                                <TrashIcon class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </Link>

            <!-- Mobile pagination -->
            <div v-if="projects.last_page > 1" class="flex items-center justify-between pt-2">
                <p class="text-xs text-gray-500">{{ projects.from }}–{{ projects.to }} of {{ projects.total }}</p>
                <div class="flex gap-1">
                    <Link v-for="link in projects.links" :key="link.label" :href="link.url ?? '#'"
                        :class="['px-2.5 py-1 text-xs rounded-lg transition-colors', link.active ? 'bg-[#EF233C] text-white' : 'text-gray-600 hover:bg-gray-100 bg-white border border-gray-200', !link.url ? 'opacity-40 pointer-events-none' : '']"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>

        <!-- Desktop: Table -->
        <div class="hidden md:block bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 text-xs font-semibold text-gray-400 uppercase tracking-wider bg-gray-50/70">
                            <th class="text-left px-5 py-3.5 w-[35%]">Project</th>
                            <th class="text-left px-4 py-3.5">Status</th>
                            <th class="text-left px-4 py-3.5 w-40">Budget</th>
                            <th class="text-left px-4 py-3.5">Progress</th>
                            <th class="text-left px-4 py-3.5">Dates</th>
                            <th class="text-right px-5 py-3.5">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-if="projects.data.length === 0">
                            <td colspan="6" class="px-5 py-16 text-center">
                                <FolderOpenIcon class="w-10 h-10 mx-auto text-gray-300 mb-2" />
                                <p class="text-sm text-gray-400 font-medium">No projects found</p>
                                <p class="text-xs text-gray-300 mt-0.5">Try adjusting your filters</p>
                            </td>
                        </tr>
                        <tr
                            v-for="project in projects.data"
                            :key="project.id"
                            class="group hover:bg-gray-50/80 transition-colors cursor-pointer"
                            @click="$inertia.visit(route('projects.show', project.id))"
                        >
                            <!-- Project name -->
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div :class="['w-1 h-10 rounded-full shrink-0', statusBarClass(project.status)]" />
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-1.5 mb-0.5">
                                            <span class="text-xs font-bold px-1.5 py-0.5 rounded shrink-0" :style="businessStyle(project.business)">{{ businessLabel(project.business) }}</span>
                                            <span class="font-semibold text-gray-800 truncate group-hover:text-[#EF233C] transition-colors">{{ project.name }}</span>
                                        </div>
                                        <p class="text-xs text-gray-400 truncate">{{ project.customer }}</p>
                                        <p v-if="project.address" class="text-xs text-gray-300 truncate max-w-xs">{{ project.address }}</p>
                                    </div>
                                </div>
                            </td>

                            <!-- Status + Phase -->
                            <td class="px-4 py-3.5">
                                <div class="flex flex-col gap-1.5">
                                    <span :class="statusClass(project.status)">{{ statusLabel(project.status) }}</span>
                                    <span :class="phaseClass(project.phase)">{{ phaseLabel(project.phase) }}</span>
                                </div>
                            </td>

                            <!-- Budget -->
                            <td class="px-4 py-3.5">
                                <template v-if="project.budget">
                                    <div class="flex items-center justify-between text-xs mb-1.5">
                                        <span :class="project.budget_progress >= 100 ? 'text-red-600 font-semibold' : 'text-gray-600'">
                                            £{{ fmt(project.budget_spent) }}
                                        </span>
                                        <span class="text-gray-300">/ £{{ fmt(project.budget) }}</span>
                                    </div>
                                    <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                        <div
                                            :class="['h-full rounded-full transition-all', budgetBarClass(project.budget_progress)]"
                                            :style="{ width: `${Math.min(project.budget_progress, 100)}%` }"
                                        />
                                    </div>
                                    <p :class="['text-xs mt-1', project.budget_progress >= 100 ? 'text-red-500 font-medium' : 'text-gray-300']">
                                        {{ project.budget_progress }}%{{ project.budget_progress >= 100 ? ' — over budget' : '' }}
                                    </p>
                                </template>
                                <span v-else class="text-xs text-gray-300">No budget set</span>
                            </td>

                            <!-- Checklist + Team -->
                            <td class="px-4 py-3.5">
                                <div class="space-y-1.5">
                                    <div v-if="project.checklist_total" class="flex items-center gap-2">
                                        <div class="flex-1 h-1.5 bg-gray-100 rounded-full overflow-hidden w-16">
                                            <div
                                                class="h-full bg-emerald-400 rounded-full"
                                                :style="{ width: `${Math.round(project.checklist_done / project.checklist_total * 100)}%` }"
                                            />
                                        </div>
                                        <span class="text-xs text-gray-400 tabular-nums">{{ project.checklist_done }}/{{ project.checklist_total }}</span>
                                    </div>
                                    <div v-if="project.staff_count" class="flex items-center gap-1 text-xs text-gray-400">
                                        <UsersIcon class="w-3.5 h-3.5" />
                                        <span>{{ project.staff_count }} assigned</span>
                                    </div>
                                    <span v-if="!project.checklist_total && !project.staff_count" class="text-xs text-gray-300">—</span>
                                </div>
                            </td>

                            <!-- Dates -->
                            <td class="px-4 py-3.5 text-xs text-gray-500">
                                <div v-if="project.start_date || project.end_date" class="space-y-0.5">
                                    <div v-if="project.start_date" class="flex items-center gap-1 text-gray-500">
                                        <ArrowRightStartOnRectangleIcon class="w-3 h-3 text-gray-300 shrink-0" />
                                        {{ fmtDate(project.start_date) }}
                                    </div>
                                    <div v-if="project.end_date" class="flex items-center gap-1" :class="isOverdue(project) ? 'text-red-500 font-medium' : 'text-gray-400'">
                                        <ArrowRightEndOnRectangleIcon class="w-3 h-3 shrink-0 opacity-60" />
                                        {{ fmtDate(project.end_date) }}
                                        <span v-if="isOverdue(project)" class="text-red-400 text-xs">(overdue)</span>
                                    </div>
                                </div>
                                <span v-else class="text-gray-300">—</span>
                            </td>

                            <!-- Actions -->
                            <td class="px-5 py-3.5">
                                <div class="flex justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity" @click.stop>
                                    <Link
                                        :href="route('projects.edit', project.id)"
                                        class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                                        title="Edit"
                                    >
                                        <PencilIcon class="w-4 h-4" />
                                    </Link>
                                    <button
                                        @click="confirmDelete(project)"
                                        class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                        title="Delete"
                                    >
                                        <TrashIcon class="w-4 h-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="projects.last_page > 1" class="flex items-center justify-between px-5 py-3 border-t border-gray-100 bg-gray-50/50">
                <p class="text-xs text-gray-400">Showing <span class="font-medium text-gray-600">{{ projects.from }}–{{ projects.to }}</span> of <span class="font-medium text-gray-600">{{ projects.total }}</span></p>
                <div class="flex gap-1">
                    <Link
                        v-for="link in projects.links"
                        :key="link.label"
                        :href="link.url ?? '#'"
                        :class="[
                            'px-2.5 py-1 text-xs rounded-lg transition-colors',
                            link.active ? 'bg-[#EF233C] text-white font-medium shadow-sm' : 'text-gray-500 hover:bg-gray-100',
                            !link.url ? 'opacity-30 pointer-events-none' : '',
                        ]"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>

        <!-- Delete modal -->
        <BaseModal :open="!!deleteTarget" @close="deleteTarget = null" max-width="sm:max-w-sm">
            <div class="p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center shrink-0">
                        <ExclamationTriangleIcon class="w-5 h-5 text-red-600" />
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-gray-800">Delete Project</h3>
                        <p class="text-sm text-gray-400">This action cannot be undone.</p>
                    </div>
                </div>
                <p class="text-sm text-gray-600 mb-5">
                    Delete <span class="font-semibold text-gray-800">{{ deleteTarget?.name }}</span>? All checklist items and staff assignments will be permanently removed.
                </p>
                <div class="flex justify-end gap-2">
                    <button @click="deleteTarget = null" class="px-4 py-2 text-sm text-gray-500 hover:text-gray-800 transition-colors">Cancel</button>
                    <button @click="doDelete" class="px-4 py-2 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors font-medium">Delete</button>
                </div>
            </div>
        </BaseModal>

    </AppLayout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BaseModal from '@/Components/BaseModal.vue';
import {
    PlusIcon, MagnifyingGlassIcon, PencilIcon, TrashIcon, XMarkIcon,
    UsersIcon, CheckCircleIcon, CalendarIcon, FolderOpenIcon,
    ExclamationTriangleIcon, ClockIcon, PauseCircleIcon, CheckBadgeIcon,
    ArrowRightStartOnRectangleIcon, ArrowRightEndOnRectangleIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    projects:     { type: Object, required: true },
    statusCounts: { type: Object, default: () => ({}) },
    filters:      { type: Object, default: () => ({}) },
    businesses:   { type: Array,  default: () => [] },
});

const statuses = [
    { key: 'planning',  label: 'Planning',  color: 'text-gray-500',   bar: 'bg-gray-300',    ring: 'ring-gray-300',   icon: ClockIcon },
    { key: 'active',    label: 'Active',    color: 'text-emerald-600', bar: 'bg-emerald-400', ring: 'ring-emerald-400', icon: CheckBadgeIcon },
    { key: 'on_hold',   label: 'On Hold',   color: 'text-amber-600',  bar: 'bg-amber-400',   ring: 'ring-amber-400',  icon: PauseCircleIcon },
    { key: 'complete',  label: 'Complete',  color: 'text-blue-600',   bar: 'bg-blue-400',    ring: 'ring-blue-400',   icon: CheckCircleIcon },
];

const businessOpts = computed(() => [
    { value: '', label: 'All', color: null },
    ...props.businesses.map(b => ({ value: b.code, label: b.name, color: b.color })),
]);

const filters = reactive({
    search:   props.filters.search   ?? '',
    status:   props.filters.status   ?? '',
    phase:    props.filters.phase    ?? '',
    business: props.filters.business ?? '',
});

const hasActiveFilters = computed(() =>
    filters.search || filters.status || filters.phase || filters.business
);

let searchTimer = null;
function applyFilters() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        router.get('/projects', filters, { preserveState: true, replace: true });
    }, 300);
}

function setStatusFilter(key) {
    filters.status = filters.status === key ? '' : key;
    applyFilters();
}

function setBusinessFilter(val) {
    filters.business = val;
    applyFilters();
}

function clearFilters() {
    Object.assign(filters, { search: '', status: '', phase: '', business: '' });
    router.get('/projects', {}, { preserveState: true, replace: true });
}

const deleteTarget = ref(null);
function confirmDelete(p) { deleteTarget.value = p; }
function doDelete() {
    router.delete(route('projects.destroy', deleteTarget.value.id), {
        onSuccess: () => { deleteTarget.value = null; },
    });
}

function isOverdue(project) {
    if (!project.end_date || project.status === 'complete') return false;
    return new Date(project.end_date) < new Date();
}

function fmt(n) {
    return Number(n ?? 0).toLocaleString('en-GB', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
}
function fmtDate(d) {
    return new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: '2-digit', year: 'numeric' });
}

// Status bar (left accent) by status
const statusBarClasses = {
    planning:  'bg-gray-300',
    active:    'bg-emerald-400',
    on_hold:   'bg-amber-400',
    complete:  'bg-blue-400',
};
function statusBarClass(s) { return statusBarClasses[s] ?? 'bg-gray-200'; }

// Budget bar colour
function budgetBarClass(pct) {
    if (pct >= 100) return 'bg-red-500';
    if (pct >= 80)  return 'bg-amber-400';
    return 'bg-emerald-400';
}

const statusLabels  = { planning: 'Planning', active: 'Active', on_hold: 'On Hold', complete: 'Complete' };
const statusClasses = {
    planning: 'inline-flex text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full font-medium',
    active:   'inline-flex text-xs bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full font-medium',
    on_hold:  'inline-flex text-xs bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full font-medium',
    complete: 'inline-flex text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full font-medium',
};
const phaseLabels  = { planning: 'Planning', installation: 'Installation', inspection: 'Inspection', complete: 'Complete' };
const phaseClasses = {
    planning:     'inline-flex text-xs bg-gray-50 text-gray-400 px-2 py-0.5 rounded-full',
    installation: 'inline-flex text-xs bg-orange-50 text-orange-500 px-2 py-0.5 rounded-full',
    inspection:   'inline-flex text-xs bg-purple-50 text-purple-500 px-2 py-0.5 rounded-full',
    complete:     'inline-flex text-xs bg-teal-50 text-teal-600 px-2 py-0.5 rounded-full',
};
function statusLabel(s) { return statusLabels[s]  ?? s; }
function statusClass(s) { return statusClasses[s]  ?? statusClasses.planning; }
function phaseLabel(s)  { return phaseLabels[s]   ?? s; }
function phaseClass(s)  { return phaseClasses[s]   ?? phaseClasses.planning; }

function businessColor(code) {
    return props.businesses.find(b => b.code === code)?.color ?? '#6B7280';
}
function businessLabel(code) {
    return props.businesses.find(b => b.code === code)?.name ?? code?.toUpperCase();
}
function businessStyle(code) {
    const color = businessColor(code);
    return { backgroundColor: color + '1A', color };
}
</script>
