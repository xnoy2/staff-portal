<template>
    <AppLayout title="Attendance">

        <!-- ── Header row ─────────────────────────────────────────────────── -->
        <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
            <div>
                <h1 class="text-lg font-semibold text-gray-800">Attendance</h1>
                <p class="text-xs text-gray-500 mt-0.5">
                    <span v-if="isManager && pendingCount > 0" class="text-amber-600 font-medium">
                        {{ pendingCount }} pending approval{{ pendingCount !== 1 ? 's' : '' }}
                    </span>
                    <span v-else>All time entries</span>
                </p>
            </div>
            <div class="flex gap-2">
                <Link
                    v-if="isSiteHead"
                    href="/qr-scanner"
                    class="inline-flex items-center gap-1.5 bg-[#EF233C] text-white text-sm px-3 py-2 rounded-lg hover:bg-[#D90429] transition-colors"
                >
                    <QrCodeIcon class="w-4 h-4" />
                    <span class="hidden sm:inline">QR Scanner</span>
                </Link>
                <button
                    v-if="isManager && selectedIds.length > 0"
                    @click="bulkApprove"
                    class="inline-flex items-center gap-1.5 bg-green-600 text-white text-sm px-3 py-2 rounded-lg hover:bg-green-700 transition-colors"
                >
                    <CheckIcon class="w-4 h-4" />
                    <span>Approve {{ selectedIds.length }}</span>
                </button>
            </div>
        </div>

        <!-- ── Filters ────────────────────────────────────────────────────── -->
        <div class="bg-white rounded-xl border border-gray-200 p-4 mb-4">
            <div class="grid grid-cols-2 sm:flex sm:flex-wrap gap-3">
                <div v-if="isPrivileged" class="col-span-2 sm:flex-1 sm:min-w-40">
                    <label class="block text-xs text-gray-500 mb-1">Staff Member</label>
                    <select v-model="filters.user_id" @change="applyFilters" class="w-full text-sm border-gray-200 rounded-lg focus:ring-[#EF233C] focus:border-[#EF233C]">
                        <option value="">All staff</option>
                        <option v-for="s in staffList" :key="s.id" :value="s.id">{{ s.name }}</option>
                    </select>
                </div>
                <div class="sm:flex-1 sm:min-w-32">
                    <label class="block text-xs text-gray-500 mb-1">Status</label>
                    <select v-model="filters.status" @change="applyFilters" class="w-full text-sm border-gray-200 rounded-lg focus:ring-[#EF233C] focus:border-[#EF233C]">
                        <option value="">All statuses</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
                <div class="sm:flex-1 sm:min-w-32">
                    <label class="block text-xs text-gray-500 mb-1">From</label>
                    <input v-model="filters.from" type="date" @change="applyFilters" class="w-full text-sm border-gray-200 rounded-lg focus:ring-[#EF233C] focus:border-[#EF233C]" />
                </div>
                <div class="sm:flex-1 sm:min-w-32">
                    <label class="block text-xs text-gray-500 mb-1">To</label>
                    <input v-model="filters.to" type="date" @change="applyFilters" class="w-full text-sm border-gray-200 rounded-lg focus:ring-[#EF233C] focus:border-[#EF233C]" />
                </div>
                <div class="flex items-end">
                    <button @click="clearFilters" class="text-xs text-gray-400 hover:text-gray-600 px-2 py-2">Clear</button>
                </div>
            </div>
        </div>

        <!-- ── Mobile: Card list ──────────────────────────────────────────── -->
        <div class="lg:hidden space-y-3">
            <div v-if="entries.data.length === 0" class="bg-white rounded-xl border border-gray-200 px-4 py-12 text-center text-gray-400 text-sm">
                No entries found.
            </div>

            <div
                v-for="entry in entries.data"
                :key="entry.id"
                :class="['bg-white rounded-xl border border-gray-200 p-4', entry.is_overtime && 'border-l-4 border-l-amber-400']"
            >
                <div class="flex items-start justify-between gap-2 mb-3">
                    <div class="flex items-center gap-2 min-w-0">
                        <div v-if="isManager">
                            <input
                                v-if="entry.status === 'pending'"
                                type="checkbox"
                                :value="entry.id"
                                v-model="selectedIds"
                                class="rounded border-gray-300 text-[#EF233C] focus:ring-[#EF233C]"
                            />
                        </div>
                        <div v-if="isPrivileged" class="flex items-center gap-2 min-w-0">
                            <img :src="entry.user?.avatar_url" :alt="entry.user?.name" class="w-8 h-8 rounded-full object-cover flex-shrink-0" />
                            <span class="text-sm font-semibold text-gray-800 truncate">{{ entry.user?.name }}</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-1.5 flex-shrink-0">
                        <span :class="statusClass(entry.status)">{{ entry.status }}</span>
                        <span v-if="entry.is_overtime" class="text-xs bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded font-medium">OT</span>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-2 mb-3">
                    <div>
                        <p class="text-xs text-gray-400 mb-0.5">Date</p>
                        <p class="text-sm font-medium text-gray-700">{{ formatDate(entry.clock_in) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 mb-0.5">In</p>
                        <p class="text-sm font-mono text-gray-700">{{ formatTime(entry.clock_in) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 mb-0.5">Out</p>
                        <p v-if="entry.clock_out" class="text-sm font-mono text-gray-700">{{ formatTime(entry.clock_out) }}</p>
                        <p v-else class="text-sm text-green-600 font-medium flex items-center gap-1">
                            <span class="w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse inline-block" />
                            Active
                        </p>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span :class="sourceClass(entry.source)">{{ sourceLabel(entry.source) }}</span>
                        <span v-if="entry.total_hours" class="text-xs text-gray-500">{{ entry.total_hours }}h</span>
                        <span v-if="entry.breaks_sum_duration_minutes" class="text-xs text-gray-400">· {{ entry.breaks_sum_duration_minutes }}m break</span>
                    </div>
                    <div v-if="isManager && entry.status === 'pending'" class="flex gap-1.5">
                        <button @click="approve(entry.id)" class="text-xs bg-green-100 text-green-700 hover:bg-green-200 px-3 py-1.5 rounded-lg transition-colors font-medium">Approve</button>
                        <button @click="openReject(entry)" class="text-xs bg-red-100 text-red-700 hover:bg-red-200 px-3 py-1.5 rounded-lg transition-colors font-medium">Reject</button>
                    </div>
                    <span v-else-if="isManager && entry.status === 'rejected' && entry.rejection_reason" class="text-xs text-gray-400 cursor-help underline decoration-dotted" :title="entry.rejection_reason">
                        see reason
                    </span>
                </div>
            </div>
        </div>

        <!-- ── Desktop: Table ─────────────────────────────────────────────── -->
        <div class="hidden lg:block bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th v-if="isManager" class="w-10 px-4 py-3">
                                <input type="checkbox" :checked="allPendingSelected" @change="toggleSelectAll" class="rounded border-gray-300 text-[#EF233C] focus:ring-[#EF233C]" />
                            </th>
                            <th v-if="isPrivileged" class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Staff</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Date</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Clock In</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Clock Out</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Worked</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Breaks</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Source</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                            <th v-if="isManager" class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-if="entries.data.length === 0">
                            <td :colspan="isManager ? 10 : (isPrivileged ? 9 : 8)" class="px-4 py-12 text-center text-gray-400 text-sm">No entries found.</td>
                        </tr>
                        <tr
                            v-for="entry in entries.data"
                            :key="entry.id"
                            :class="['hover:bg-gray-50 transition-colors', entry.is_overtime ? 'bg-amber-50/40' : '']"
                        >
                            <td v-if="isManager" class="px-4 py-3">
                                <input v-if="entry.status === 'pending'" type="checkbox" :value="entry.id" v-model="selectedIds" class="rounded border-gray-300 text-[#EF233C] focus:ring-[#EF233C]" />
                            </td>
                            <td v-if="isPrivileged" class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <img :src="entry.user?.avatar_url" :alt="entry.user?.name" class="w-6 h-6 rounded-full object-cover flex-shrink-0" />
                                    <span class="text-gray-700 font-medium">{{ entry.user?.name }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ formatDate(entry.clock_in) }}</td>
                            <td class="px-4 py-3 text-gray-700 font-mono text-xs">{{ formatTime(entry.clock_in) }}</td>
                            <td class="px-4 py-3 text-gray-700 font-mono text-xs">
                                <span v-if="entry.clock_out">{{ formatTime(entry.clock_out) }}</span>
                                <span v-else class="inline-flex items-center gap-1 text-green-600 font-sans font-medium text-xs">
                                    <span class="w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse inline-block" />
                                    {{ clockStateLabel(entry.clock_state) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-600">
                                <span v-if="entry.total_hours">{{ entry.total_hours }}h</span>
                                <span v-else-if="!entry.clock_out" class="text-green-600 font-medium text-xs">In progress</span>
                                <span v-else>—</span>
                                <span v-if="entry.is_overtime" class="ml-1 text-xs bg-amber-100 text-amber-700 px-1 py-0.5 rounded font-medium">OT</span>
                            </td>
                            <td class="px-4 py-3">
                                <span v-if="entry.breaks_sum_duration_minutes" class="text-xs text-gray-500 bg-gray-100 px-2 py-0.5 rounded-full">
                                    {{ entry.breaks_sum_duration_minutes }}m
                                </span>
                                <span v-else class="text-gray-300">—</span>
                            </td>
                            <td class="px-4 py-3"><span :class="sourceClass(entry.source)">{{ sourceLabel(entry.source) }}</span></td>
                            <td class="px-4 py-3"><span :class="statusClass(entry.status)">{{ entry.status }}</span></td>
                            <td v-if="isManager" class="px-4 py-3 text-right">
                                <div v-if="entry.status === 'pending'" class="flex justify-end gap-1.5">
                                    <button @click="approve(entry.id)" class="text-xs bg-green-100 text-green-700 hover:bg-green-200 px-2 py-1 rounded transition-colors font-medium">Approve</button>
                                    <button @click="openReject(entry)" class="text-xs bg-red-100 text-red-700 hover:bg-red-200 px-2 py-1 rounded transition-colors font-medium">Reject</button>
                                </div>
                                <div v-else-if="entry.status === 'rejected' && entry.rejection_reason">
                                    <span class="text-xs text-gray-400 cursor-help underline decoration-dotted" :title="entry.rejection_reason">reason</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="entries.last_page > 1" class="flex items-center justify-between px-4 py-3 border-t border-gray-100 bg-gray-50">
                <p class="text-xs text-gray-500">Showing {{ entries.from }}–{{ entries.to }} of {{ entries.total }}</p>
                <div class="flex gap-1">
                    <Link
                        v-for="link in entries.links"
                        :key="link.label"
                        :href="link.url ?? '#'"
                        :class="['px-2.5 py-1 text-xs rounded transition-colors', link.active ? 'bg-[#EF233C] text-white' : 'text-gray-600 hover:bg-gray-100', !link.url ? 'opacity-40 pointer-events-none' : '']"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>

        <!-- Mobile pagination -->
        <div v-if="entries.last_page > 1" class="lg:hidden flex items-center justify-between mt-4 bg-white rounded-xl border border-gray-200 px-4 py-3">
            <p class="text-xs text-gray-500">{{ entries.from }}–{{ entries.to }} of {{ entries.total }}</p>
            <div class="flex gap-1">
                <Link
                    v-for="link in entries.links"
                    :key="link.label"
                    :href="link.url ?? '#'"
                    :class="['px-2.5 py-1 text-xs rounded transition-colors', link.active ? 'bg-[#EF233C] text-white' : 'text-gray-600 hover:bg-gray-100', !link.url ? 'opacity-40 pointer-events-none' : '']"
                    v-html="link.label"
                />
            </div>
        </div>

        <!-- Reject modal -->
        <BaseModal :open="rejectModal.open" @close="rejectModal.open = false" max-width="sm:max-w-md">
            <div class="p-6">
                <h3 class="text-base font-semibold text-gray-800 mb-1">Reject Entry</h3>
                <p class="text-sm text-gray-500 mb-4">Provide an optional reason for rejecting this entry.</p>
                <textarea v-model="rejectModal.reason" rows="3" placeholder="Reason (optional)" class="w-full text-sm border-gray-200 rounded-lg focus:ring-[#EF233C] focus:border-[#EF233C] resize-none" />
                <div class="flex justify-end gap-2 mt-4">
                    <button @click="rejectModal.open = false" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 transition-colors">Cancel</button>
                    <button @click="confirmReject" class="px-4 py-2 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">Reject Entry</button>
                </div>
            </div>
        </BaseModal>

    </AppLayout>
</template>

<script setup>
import { ref, computed, reactive } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { usePermission } from '@/Composables/usePermission';
import AppLayout from '@/Layouts/AppLayout.vue';
import BaseModal from '@/Components/BaseModal.vue';
import {
    QrCodeIcon,
    CheckIcon,
} from '@heroicons/vue/24/outline';

// ── Props ─────────────────────────────────────────────────────────────────────

const props = defineProps({
    entries:      { type: Object,  required: true },
    pendingCount: { type: Number,  default: 0 },
    staffList:    { type: Array,   default: () => [] },
    isPrivileged: { type: Boolean, default: false },
    isManager:    { type: Boolean, default: false },
    filters:      { type: Object,  default: () => ({}) },
});

const { isSiteHead } = usePermission();

// ── Filters ───────────────────────────────────────────────────────────────────

const filters = reactive({
    user_id: props.filters.user_id ?? '',
    status:  props.filters.status  ?? '',
    from:    props.filters.from    ?? '',
    to:      props.filters.to      ?? '',
});

function applyFilters() {
    router.get('/attendance', filters, { preserveState: true, replace: true });
}

function clearFilters() {
    Object.assign(filters, { user_id: '', status: '', from: '', to: '' });
    applyFilters();
}

// ── Bulk select & approvals ───────────────────────────────────────────────────

const selectedIds        = ref([]);
const pendingEntries     = computed(() => props.entries.data.filter(e => e.status === 'pending'));
const allPendingSelected = computed(() =>
    pendingEntries.value.length > 0 &&
    pendingEntries.value.every(e => selectedIds.value.includes(e.id))
);

function toggleSelectAll() {
    allPendingSelected.value
        ? (selectedIds.value = [])
        : (selectedIds.value = pendingEntries.value.map(e => e.id));
}

function approve(id) {
    router.post(`/attendance/${id}/approve`, {}, { preserveScroll: true });
}

const rejectModal = reactive({ open: false, entryId: null, reason: '' });

function openReject(entry) {
    rejectModal.entryId = entry.id;
    rejectModal.reason  = '';
    rejectModal.open    = true;
}

function confirmReject() {
    router.post(`/attendance/${rejectModal.entryId}/reject`, { reason: rejectModal.reason }, {
        preserveScroll: true,
        onSuccess: () => { rejectModal.open = false; },
    });
}

function bulkApprove() {
    router.post('/attendance/bulk-approve', { ids: selectedIds.value }, {
        preserveScroll: true,
        onSuccess: () => { selectedIds.value = []; },
    });
}

// ── Formatters ────────────────────────────────────────────────────────────────

function formatDate(dt) {
    if (! dt) return '—';
    return new Date(dt).toLocaleDateString('en-GB', { day: '2-digit', month: '2-digit', year: 'numeric' });
}

function formatTime(dt) {
    if (! dt) return '—';
    return new Date(dt).toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });
}

function clockStateLabel(state) {
    return { working: 'Active', on_break: 'On Break', on_lunch: 'Lunch' }[state] ?? 'Active';
}

const sourceLabels  = { self_clockin: 'Self', site_head: 'Site Head', manual: 'Manual', bulk: 'Bulk' };
const sourceClasses = {
    self_clockin: 'text-xs bg-blue-50 text-blue-700 px-2 py-0.5 rounded-full',
    site_head:    'text-xs bg-purple-50 text-purple-700 px-2 py-0.5 rounded-full',
    manual:       'text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full',
    bulk:         'text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full',
};

function sourceLabel(s) { return sourceLabels[s] ?? s; }
function sourceClass(s) { return sourceClasses[s] ?? sourceClasses.manual; }

function statusClass(status) {
    const map = {
        pending:  'text-xs bg-amber-50 text-amber-700 px-2 py-0.5 rounded-full font-medium capitalize',
        approved: 'text-xs bg-green-50 text-green-700 px-2 py-0.5 rounded-full font-medium capitalize',
        rejected: 'text-xs bg-red-50 text-red-700 px-2 py-0.5 rounded-full font-medium capitalize',
    };
    return map[status] ?? 'text-xs text-gray-500 capitalize';
}
</script>
