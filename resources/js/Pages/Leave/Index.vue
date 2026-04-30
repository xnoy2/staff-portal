<template>
    <AppLayout title="Leave">
        <div class="max-w-6xl mx-auto space-y-5">

            <!-- Header -->
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div>
                    <h1 class="text-lg font-semibold text-gray-800">Leave</h1>
                    <p class="text-xs text-gray-500 mt-0.5">
                        <span v-if="summary.user_name && isPrivileged">{{ summary.user_name }} — </span>
                        {{ year }} leave records
                    </p>
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    <span
                        v-if="pendingCount > 0 && isPrivileged"
                        class="inline-flex items-center gap-1.5 bg-amber-50 text-amber-700 border border-amber-200 text-xs font-medium px-2.5 py-1 rounded-full"
                    >
                        <span class="w-1.5 h-1.5 bg-amber-500 rounded-full animate-pulse"></span>
                        {{ pendingCount }} pending approval
                    </span>
                    <button
                        @click="openRequestModal"
                        class="bg-[#EF233C] hover:bg-[#D90429] text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors flex items-center gap-1.5"
                    >
                        <PlusIcon class="w-4 h-4" />
                        Request Leave
                    </button>
                </div>
            </div>

            <!-- Balance Summary Cards -->
            <div class="grid grid-cols-3 sm:grid-cols-5 gap-2 sm:gap-3">
                <div class="bg-white rounded-xl border border-gray-200 p-3 sm:p-4">
                    <p class="text-xs text-gray-500 mb-1">Entitlement</p>
                    <p class="text-xl sm:text-2xl font-bold text-gray-800">{{ summary.entitlement }}</p>
                    <p class="text-xs text-gray-400 mt-0.5 hidden sm:block">days/year</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-3 sm:p-4">
                    <p class="text-xs text-gray-500 mb-1">Used</p>
                    <p class="text-xl sm:text-2xl font-bold text-red-600">{{ summary.used }}</p>
                    <p class="text-xs text-gray-400 mt-0.5 hidden sm:block">approved</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-3 sm:p-4">
                    <p class="text-xs text-gray-500 mb-1">Pending</p>
                    <p class="text-xl sm:text-2xl font-bold text-amber-500">{{ summary.pending }}</p>
                    <p class="text-xs text-gray-400 mt-0.5 hidden sm:block">awaiting</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-3 sm:p-4 border-l-4" :class="summary.remaining > 5 ? 'border-l-green-500' : 'border-l-red-500'">
                    <p class="text-xs text-gray-500 mb-1">Left</p>
                    <p class="text-xl sm:text-2xl font-bold" :class="summary.remaining > 5 ? 'text-green-600' : 'text-red-600'">{{ summary.remaining }}</p>
                    <p class="text-xs text-gray-400 mt-0.5 hidden sm:block">days left</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-3 sm:p-4">
                    <p class="text-xs text-gray-500 mb-1">Sick</p>
                    <p class="text-xl sm:text-2xl font-bold text-orange-500">{{ summary.sick_days }}</p>
                    <p class="text-xs text-gray-400 mt-0.5 hidden sm:block">this year</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-xl border border-gray-200 p-4 flex flex-wrap items-center gap-3">
                <!-- Year -->
                <select v-model.number="filterYear" @change="applyFilters" class="filter-select">
                    <option v-for="y in yearOptions" :key="y" :value="y">{{ y }}</option>
                </select>

                <!-- Status -->
                <select v-model="filterStatus" @change="applyFilters" class="filter-select">
                    <option value="">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>

                <!-- Type -->
                <select v-model="filterType" @change="applyFilters" class="filter-select">
                    <option value="">All Types</option>
                    <option value="annual">Annual</option>
                    <option value="sick">Sick</option>
                    <option value="unpaid">Unpaid</option>
                    <option value="compassionate">Compassionate</option>
                    <option value="other">Other</option>
                </select>

                <!-- Staff filter (managers only) -->
                <select v-if="isPrivileged" v-model="filterUserId" @change="applyFilters" class="filter-select">
                    <option value="">All Staff</option>
                    <option v-for="s in staffList" :key="s.id" :value="s.id">{{ s.name }}</option>
                </select>

                <button
                    v-if="hasActiveFilters"
                    @click="clearFilters"
                    class="text-xs text-gray-500 hover:text-[#EF233C] transition-colors flex items-center gap-1"
                >
                    <XMarkIcon class="w-3.5 h-3.5" /> Clear
                </button>
            </div>

            <!-- Leave Table -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div v-if="leaves.data.length === 0" class="py-16 text-center">
                    <CalendarDaysIcon class="w-10 h-10 text-gray-300 mx-auto mb-3" />
                    <p class="text-sm text-gray-500">No leave records found.</p>
                </div>

                <div v-else class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th v-if="isPrivileged" class="text-left text-xs font-medium text-gray-500 px-4 py-3">Staff</th>
                            <th class="text-left text-xs font-medium text-gray-500 px-4 py-3">Type</th>
                            <th class="text-left text-xs font-medium text-gray-500 px-4 py-3">Dates</th>
                            <th class="text-center text-xs font-medium text-gray-500 px-4 py-3">Days</th>
                            <th class="text-left text-xs font-medium text-gray-500 px-4 py-3">Status</th>
                            <th class="text-left text-xs font-medium text-gray-500 px-4 py-3 hidden lg:table-cell">Reason</th>
                            <th class="text-left text-xs font-medium text-gray-500 px-4 py-3 hidden lg:table-cell">Reviewed by</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="leave in leaves.data" :key="leave.id" class="group hover:bg-gray-50 transition-colors">
                            <!-- Staff (managers) -->
                            <td v-if="isPrivileged" class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <img :src="leave.user?.avatar_url" :alt="leave.user?.name" class="w-7 h-7 rounded-full object-cover flex-shrink-0" />
                                    <span class="font-medium text-gray-800 truncate max-w-28">{{ leave.user?.name }}</span>
                                </div>
                            </td>
                            <!-- Type -->
                            <td class="px-4 py-3">
                                <span :class="typeClass(leave.type)" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium capitalize">
                                    {{ leave.type }}
                                </span>
                            </td>
                            <!-- Dates -->
                            <td class="px-4 py-3 text-gray-700 whitespace-nowrap">
                                {{ fmtDate(leave.start_date) }}
                                <span v-if="leave.start_date !== leave.end_date" class="text-gray-400"> → {{ fmtDate(leave.end_date) }}</span>
                            </td>
                            <!-- Days -->
                            <td class="px-4 py-3 text-center font-semibold text-gray-800">{{ leave.days }}</td>
                            <!-- Status -->
                            <td class="px-4 py-3">
                                <span :class="statusClass(leave.status)" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium capitalize">
                                    <span class="w-1 h-1 rounded-full" :class="statusDotClass(leave.status)"></span>
                                    {{ leave.status }}
                                </span>
                            </td>
                            <!-- Reason -->
                            <td class="px-4 py-3 text-gray-500 max-w-48 truncate hidden lg:table-cell">
                                {{ leave.reason || '—' }}
                            </td>
                            <!-- Reviewed by -->
                            <td class="px-4 py-3 hidden lg:table-cell">
                                <span v-if="leave.reviewed_by" class="text-xs text-gray-500">
                                    {{ leave.reviewed_by }}<br>
                                    <span class="text-gray-400">{{ fmtDate(leave.reviewed_at) }}</span>
                                </span>
                                <span v-else class="text-gray-300 text-xs">—</span>
                            </td>
                            <!-- Actions -->
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-1 opacity-100 sm:opacity-0 sm:group-hover:opacity-100 transition-opacity justify-end">
                                    <!-- Approve/Reject (managers, pending only) -->
                                    <template v-if="isPrivileged && leave.status === 'pending'">
                                        <button
                                            @click="openReviewModal(leave, 'approve')"
                                            class="text-xs bg-green-50 hover:bg-green-100 text-green-700 border border-green-200 px-2 py-1 rounded-md transition-colors"
                                        >Approve</button>
                                        <button
                                            @click="openReviewModal(leave, 'reject')"
                                            class="text-xs bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 px-2 py-1 rounded-md transition-colors"
                                        >Reject</button>
                                    </template>
                                    <!-- Delete (own pending, or manager) -->
                                    <button
                                        v-if="canDelete(leave)"
                                        @click="confirmDelete(leave)"
                                        class="text-gray-400 hover:text-red-500 p-1 rounded transition-colors"
                                    >
                                        <TrashIcon class="w-4 h-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
                </div><!-- /overflow-x-auto -->

                <!-- Pagination -->
                <div v-if="leaves.last_page > 1" class="border-t border-gray-100 px-4 py-3 flex items-center justify-between text-xs text-gray-500">
                    <span>Showing {{ leaves.from }}–{{ leaves.to }} of {{ leaves.total }}</span>
                    <div class="flex items-center gap-1">
                        <Link
                            v-for="link in leaves.links"
                            :key="link.label"
                            :href="link.url ?? '#'"
                            :class="[
                                'px-2 py-1 rounded transition-colors',
                                link.active ? 'bg-[#EF233C] text-white font-medium' : 'hover:bg-gray-100',
                                !link.url ? 'opacity-30 pointer-events-none' : '',
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Request Leave Modal ──────────────────────────────────────── -->
        <Transition name="modal">
            <div v-if="showRequestModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/50" @click="showRequestModal = false" />
                <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md p-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-base font-semibold text-gray-800">Request Leave</h2>
                        <button @click="showRequestModal = false" class="text-gray-400 hover:text-gray-600">
                            <XMarkIcon class="w-5 h-5" />
                        </button>
                    </div>

                    <form @submit.prevent="submitRequest" class="space-y-4">
                        <!-- Staff selector (privileged only) -->
                        <div v-if="isPrivileged">
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">For Staff Member</label>
                            <select v-model="requestForm.user_id" class="form-input">
                                <option value="">Myself</option>
                                <option v-for="s in staffList" :key="s.id" :value="s.id">{{ s.name }}</option>
                            </select>
                        </div>

                        <!-- Type -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Leave Type</label>
                            <select v-model="requestForm.type" class="form-input" required>
                                <option value="annual">Annual Leave</option>
                                <option value="sick">Sick Leave</option>
                                <option value="unpaid">Unpaid Leave</option>
                                <option value="compassionate">Compassionate Leave</option>
                                <option value="other">Other</option>
                            </select>
                            <p v-if="requestForm.errors.type" class="mt-1 text-xs text-red-600">{{ requestForm.errors.type }}</p>
                        </div>

                        <!-- Dates -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Start Date</label>
                                <input v-model="requestForm.start_date" type="date" class="form-input" required />
                                <p v-if="requestForm.errors.start_date" class="mt-1 text-xs text-red-600">{{ requestForm.errors.start_date }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">End Date</label>
                                <input v-model="requestForm.end_date" type="date" class="form-input" required :min="requestForm.start_date" />
                                <p v-if="requestForm.errors.end_date" class="mt-1 text-xs text-red-600">{{ requestForm.errors.end_date }}</p>
                            </div>
                        </div>

                        <!-- Working days preview -->
                        <div v-if="estimatedDays !== null" class="bg-blue-50 border border-blue-200 rounded-lg px-3 py-2 text-sm text-blue-700">
                            Approx. <strong>{{ estimatedDays }}</strong> working day(s)
                        </div>

                        <!-- Reason -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Reason <span class="text-gray-400 font-normal">(optional)</span></label>
                            <textarea v-model="requestForm.reason" rows="2" class="form-input resize-none" placeholder="Briefly describe your reason…" />
                            <p v-if="requestForm.errors.reason" class="mt-1 text-xs text-red-600">{{ requestForm.errors.reason }}</p>
                        </div>

                        <div class="flex gap-3 pt-1">
                            <button type="button" @click="showRequestModal = false" class="flex-1 border border-gray-200 text-sm text-gray-600 py-2 rounded-lg hover:bg-gray-50 transition-colors">
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="requestForm.processing"
                                class="flex-1 bg-[#EF233C] hover:bg-[#D90429] text-white text-sm font-medium py-2 rounded-lg transition-colors disabled:opacity-60"
                            >
                                {{ requestForm.processing ? 'Submitting…' : 'Submit Request' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Transition>

        <!-- ── Review Modal (Approve / Reject) ────────────────────────── -->
        <Transition name="modal">
            <div v-if="reviewModal.show" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/50" @click="reviewModal.show = false" />
                <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-sm p-6 space-y-4">
                    <h2 class="text-base font-semibold text-gray-800 capitalize">
                        {{ reviewModal.action }} Leave
                    </h2>
                    <p class="text-sm text-gray-600">
                        <span class="font-medium">{{ reviewModal.leave?.user?.name }}</span> —
                        {{ fmtDate(reviewModal.leave?.start_date) }}
                        <span v-if="reviewModal.leave?.start_date !== reviewModal.leave?.end_date"> → {{ fmtDate(reviewModal.leave?.end_date) }}</span>
                        ({{ reviewModal.leave?.days }} day(s))
                    </p>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Notes <span class="text-gray-400 font-normal">(optional)</span></label>
                        <textarea v-model="reviewForm.review_notes" rows="2" class="form-input resize-none" placeholder="Add a note for the staff member…" />
                    </div>
                    <div class="flex gap-3">
                        <button type="button" @click="reviewModal.show = false" class="flex-1 border border-gray-200 text-sm text-gray-600 py-2 rounded-lg hover:bg-gray-50 transition-colors">
                            Cancel
                        </button>
                        <button
                            @click="submitReview"
                            :disabled="reviewForm.processing"
                            :class="[
                                'flex-1 text-white text-sm font-medium py-2 rounded-lg transition-colors disabled:opacity-60 capitalize',
                                reviewModal.action === 'approve' ? 'bg-green-600 hover:bg-green-700' : 'bg-[#EF233C] hover:bg-[#D90429]',
                            ]"
                        >
                            {{ reviewForm.processing ? 'Saving…' : reviewModal.action }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- ── Delete Confirm Modal ──────────────────────────────────── -->
        <Transition name="modal">
            <div v-if="deleteTarget" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/50" @click="deleteTarget = null" />
                <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-sm p-6 space-y-4">
                    <h2 class="text-base font-semibold text-gray-800">Cancel Leave Request?</h2>
                    <p class="text-sm text-gray-600">This will permanently remove the leave request.</p>
                    <div class="flex gap-3">
                        <button @click="deleteTarget = null" class="flex-1 border border-gray-200 text-sm text-gray-600 py-2 rounded-lg hover:bg-gray-50 transition-colors">
                            Keep it
                        </button>
                        <button
                            @click="doDelete"
                            class="flex-1 bg-[#EF233C] hover:bg-[#D90429] text-white text-sm font-medium py-2 rounded-lg transition-colors"
                        >
                            Cancel Leave
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </AppLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, usePage, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    PlusIcon, XMarkIcon, TrashIcon, CalendarDaysIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    leaves:       { type: Object, required: true },
    pendingCount: { type: Number, default: 0 },
    summary:      { type: Object, required: true },
    staffList:    { type: Array,  default: () => [] },
    isPrivileged: { type: Boolean, default: false },
    year:         { type: Number, required: true },
    filters:      { type: Object, default: () => ({}) },
});

// ── Filters ──────────────────────────────────────────────────────────

const filterYear   = ref(props.year);
const filterStatus = ref(props.filters.status ?? '');
const filterType   = ref(props.filters.type ?? '');
const filterUserId = ref(props.filters.user_id ?? '');

const yearOptions = computed(() => {
    const current = new Date().getFullYear();
    return [current + 1, current, current - 1, current - 2];
});

const hasActiveFilters = computed(() =>
    filterStatus.value || filterType.value || filterUserId.value || filterYear.value !== new Date().getFullYear()
);

function applyFilters() {
    router.get(route('leave.index'), {
        year:    filterYear.value,
        status:  filterStatus.value  || undefined,
        type:    filterType.value    || undefined,
        user_id: filterUserId.value  || undefined,
    }, { preserveScroll: true, replace: true });
}

function clearFilters() {
    filterYear.value   = new Date().getFullYear();
    filterStatus.value = '';
    filterType.value   = '';
    filterUserId.value = '';
    applyFilters();
}

// ── Request Modal ────────────────────────────────────────────────────

const showRequestModal = ref(false);

const requestForm = useForm({
    user_id:    '',
    type:       'annual',
    start_date: '',
    end_date:   '',
    reason:     '',
});

function openRequestModal() {
    requestForm.reset();
    showRequestModal.value = true;
}

const estimatedDays = computed(() => {
    if (!requestForm.start_date || !requestForm.end_date) return null;
    const start = new Date(requestForm.start_date);
    const end   = new Date(requestForm.end_date);
    if (end < start) return null;
    let days = 0;
    const cur = new Date(start);
    while (cur <= end) {
        const d = cur.getDay();
        if (d !== 0 && d !== 6) days++;
        cur.setDate(cur.getDate() + 1);
    }
    return days;
});

function submitRequest() {
    requestForm.post(route('leave.store'), {
        onSuccess: () => { showRequestModal.value = false; requestForm.reset(); },
    });
}

// ── Review Modal ─────────────────────────────────────────────────────

const reviewModal = ref({ show: false, leave: null, action: 'approve' });
const reviewForm  = useForm({ review_notes: '' });

function openReviewModal(leave, action) {
    reviewModal.value = { show: true, leave, action };
    reviewForm.reset();
}

function submitReview() {
    const { leave, action } = reviewModal.value;
    const routeName = action === 'approve' ? 'leave.approve' : 'leave.reject';
    reviewForm.post(route(routeName, leave.id), {
        onSuccess: () => { reviewModal.value.show = false; },
    });
}

// ── Delete ───────────────────────────────────────────────────────────

const deleteTarget = ref(null);

function confirmDelete(leave) { deleteTarget.value = leave; }

function doDelete() {
    router.delete(route('leave.destroy', deleteTarget.value.id), {
        onSuccess: () => { deleteTarget.value = null; },
    });
}

const page = usePage();

function canDelete(leave) {
    const authId = page.props.auth.user.id;
    if (props.isPrivileged) return true;
    return leave.user?.id === authId && leave.status === 'pending';
}

// ── Helpers ──────────────────────────────────────────────────────────

function fmtDate(d) {
    if (!d) return '—';
    return new Intl.DateTimeFormat('en-GB', { day: '2-digit', month: '2-digit', year: 'numeric' }).format(new Date(d));
}

function typeClass(type) {
    const map = {
        annual:        'bg-blue-50 text-blue-700 border border-blue-200',
        sick:          'bg-orange-50 text-orange-700 border border-orange-200',
        unpaid:        'bg-gray-100 text-gray-600 border border-gray-200',
        compassionate: 'bg-purple-50 text-purple-700 border border-purple-200',
        other:         'bg-gray-50 text-gray-600 border border-gray-200',
    };
    return map[type] ?? 'bg-gray-100 text-gray-600';
}

function statusClass(status) {
    const map = {
        pending:  'bg-amber-50 text-amber-700 border border-amber-200',
        approved: 'bg-green-50 text-green-700 border border-green-200',
        rejected: 'bg-red-50 text-red-600 border border-red-200',
    };
    return map[status] ?? '';
}

function statusDotClass(status) {
    const map = {
        pending:  'bg-amber-500',
        approved: 'bg-green-500',
        rejected: 'bg-red-500',
    };
    return map[status] ?? 'bg-gray-400';
}
</script>

<style scoped>
.form-input {
    @apply w-full rounded-lg border-gray-200 text-sm focus:ring-[#EF233C] focus:border-[#EF233C];
}
.filter-select {
    @apply rounded-lg border-gray-200 text-sm focus:ring-[#EF233C] focus:border-[#EF233C] py-1.5;
}
.modal-enter-active, .modal-leave-active { transition: opacity 0.2s ease; }
.modal-enter-from, .modal-leave-to       { opacity: 0; }
</style>
