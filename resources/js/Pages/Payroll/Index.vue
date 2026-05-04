<template>
    <AppLayout title="Payroll">
        <div class="max-w-6xl mx-auto space-y-5">

            <!-- Header -->
            <div class="flex items-center justify-between gap-4 flex-wrap">
                <div>
                    <h1 class="text-lg font-semibold text-gray-800">Payroll</h1>
                    <p class="text-xs text-gray-500 mt-0.5">Auto-generates at midnight on the cut-off day</p>
                </div>
                <!-- Cut-off day editor -->
                <form @submit.prevent="saveCutoff" class="flex items-center gap-2 bg-white border border-gray-200 rounded-xl px-4 py-2.5">
                    <span class="text-xs text-gray-500 whitespace-nowrap">Cut-off day:</span>
                    <input
                        v-model.number="cutoffForm.cutoff_day"
                        type="number" min="1" max="28"
                        class="w-14 text-sm font-semibold text-center border border-gray-200 rounded-lg py-1 focus:outline-none focus:ring-1 focus:ring-[#EF233C] focus:border-[#EF233C]"
                    />
                    <span class="text-xs text-gray-400">of each month</span>
                    <button
                        type="submit"
                        :disabled="cutoffForm.processing || cutoffForm.cutoff_day === cutoffDay"
                        class="text-xs bg-[#EF233C] hover:bg-[#D90429] disabled:opacity-40 text-white font-semibold px-3 py-1 rounded-lg transition-colors"
                    >Save</button>
                </form>
            </div>

            <!-- Current period + generate -->
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Current Payroll Period</p>
                        <p class="text-lg font-bold text-gray-800">{{ formatDate(current.from) }} – {{ formatDate(current.to) }}</p>
                        <p class="text-xs text-gray-500 mt-1">
                            Next cut-off:
                            <span class="font-medium">{{ formatDate(nextCutoffDate) }}</span>
                            <span v-if="daysUntil === 0" class="text-[#EF233C] font-bold ml-1">— Today!</span>
                            <span v-else class="text-gray-400 ml-1">in {{ daysUntil }} day{{ daysUntil !== 1 ? 's' : '' }}</span>
                        </p>
                    </div>

                    <!-- Generate form -->
                    <form @submit.prevent="submitGenerate" class="flex flex-wrap items-center gap-2">
                        <div class="flex items-center gap-1.5">
                            <input v-model="genForm.from" type="date" class="text-xs border border-gray-200 rounded-lg px-2 py-1.5 focus:outline-none focus:ring-1 focus:ring-[#EF233C] focus:border-[#EF233C]" />
                            <span class="text-xs text-gray-400">–</span>
                            <input v-model="genForm.to" type="date" class="text-xs border border-gray-200 rounded-lg px-2 py-1.5 focus:outline-none focus:ring-1 focus:ring-[#EF233C] focus:border-[#EF233C]" />
                        </div>
                        <button
                            type="submit"
                            :disabled="genForm.processing || !genForm.from || !genForm.to"
                            class="bg-[#EF233C] hover:bg-[#D90429] disabled:opacity-50 text-white text-sm font-semibold px-4 py-1.5 rounded-lg transition-colors flex items-center gap-1.5 whitespace-nowrap"
                        >
                            <BoltIcon class="w-3.5 h-3.5" />
                            {{ genForm.processing ? 'Generating…' : 'Generate Payroll' }}
                        </button>
                    </form>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-xl border border-gray-200 p-3 flex flex-wrap items-center gap-3">
                <select v-model="filterPeriod" @change="applyFilters" class="text-sm border-gray-200 rounded-lg focus:ring-[#EF233C] focus:border-[#EF233C]">
                    <option value="">All periods</option>
                    <option v-for="p in periods" :key="p.from" :value="p.from">
                        {{ formatDate(p.from) }} – {{ formatDate(p.to) }}
                    </option>
                </select>
                <select v-model="filterStatus" @change="applyFilters" class="text-sm border-gray-200 rounded-lg focus:ring-[#EF233C] focus:border-[#EF233C]">
                    <option value="">All statuses</option>
                    <option value="draft">Draft</option>
                    <option value="approved">Approved</option>
                </select>
                <div class="ml-auto flex items-center gap-2">
                    <span class="text-xs text-gray-400">{{ runs.length }} payslip{{ runs.length !== 1 ? 's' : '' }}</span>
                    <button
                        v-if="draftCount > 0 && filterPeriod"
                        @click="approveAll"
                        class="text-xs bg-green-50 hover:bg-green-100 text-green-700 border border-green-200 px-3 py-1.5 rounded-lg transition-colors font-medium"
                    >
                        Approve All ({{ draftCount }})
                    </button>
                </div>
            </div>

            <!-- Empty state -->
            <div v-if="runs.length === 0" class="bg-white rounded-xl border border-dashed border-gray-300 py-16 text-center">
                <BanknotesIcon class="w-10 h-10 text-gray-300 mx-auto mb-3" />
                <p class="text-gray-600 font-medium">No payroll runs yet</p>
                <p class="text-sm text-gray-400 mt-1">Select a period and click Generate Payroll to create payslips for all active staff.</p>
            </div>

            <!-- Runs table -->
            <div v-else class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 border-b border-gray-100">
                            <tr>
                                <th class="text-left text-xs font-medium text-gray-500 px-4 py-3">Staff</th>
                                <th class="text-left text-xs font-medium text-gray-500 px-4 py-3">Period</th>
                                <th class="text-right text-xs font-medium text-gray-500 px-4 py-3">Shifts</th>
                                <th class="text-right text-xs font-medium text-gray-500 px-4 py-3">Hours</th>
                                <th class="text-right text-xs font-medium text-gray-500 px-4 py-3">Gross Pay</th>
                                <th class="text-center text-xs font-medium text-gray-500 px-4 py-3">Status</th>
                                <th class="text-right text-xs font-medium text-gray-500 px-4 py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="run in runs" :key="run.id" class="hover:bg-gray-50 transition-colors">
                                <!-- Staff -->
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <img :src="run.avatar_url" :alt="run.staff_name" class="w-7 h-7 rounded-full object-cover flex-shrink-0" />
                                        <div class="min-w-0">
                                            <p class="text-gray-800 font-medium truncate">{{ run.staff_name }}</p>
                                            <p class="text-xs text-gray-400 font-mono">{{ run.staff_id }}</p>
                                        </div>
                                    </div>
                                </td>
                                <!-- Period -->
                                <td class="px-4 py-3 text-gray-600 text-xs whitespace-nowrap">
                                    {{ formatDate(run.period_from) }} – {{ formatDate(run.period_to) }}
                                </td>
                                <!-- Shifts -->
                                <td class="px-4 py-3 text-right text-gray-600">{{ run.shifts_count }}</td>
                                <!-- Hours -->
                                <td class="px-4 py-3 text-right font-mono text-gray-700">{{ run.total_hours.toFixed(2) }}h</td>
                                <!-- Gross Pay -->
                                <td class="px-4 py-3 text-right font-semibold">
                                    <span v-if="run.has_rate" class="text-gray-800">£{{ run.gross_pay.toFixed(2) }}</span>
                                    <span v-else class="text-amber-500 text-xs">No rate</span>
                                </td>
                                <!-- Status -->
                                <td class="px-4 py-3 text-center">
                                    <span :class="run.status === 'approved'
                                        ? 'bg-green-100 text-green-700 border border-green-200'
                                        : 'bg-amber-50 text-amber-700 border border-amber-200'"
                                        class="text-xs font-semibold px-2 py-0.5 rounded-full capitalize"
                                    >
                                        {{ run.status }}
                                    </span>
                                </td>
                                <!-- Actions -->
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <Link
                                            :href="route('staff.payslip', run.staff_uuid) + '?run_id=' + run.id"
                                            class="text-xs text-[#EF233C] hover:underline whitespace-nowrap"
                                        >View</Link>
                                        <button
                                            v-if="run.status === 'draft'"
                                            @click="approve(run.id)"
                                            class="text-xs bg-green-50 hover:bg-green-100 text-green-700 border border-green-200 px-2 py-0.5 rounded-lg transition-colors"
                                        >Approve</button>
                                        <button
                                            v-if="run.status === 'draft'"
                                            @click="remove(run.id)"
                                            class="text-xs text-gray-400 hover:text-red-500 transition-colors px-1"
                                        >✕</button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- Delete confirmation modal -->
        <ConfirmModal
            :open="!!deleteTargetId"
            title="Delete Draft Payslip"
            message="This cannot be undone. Only draft payslips can be deleted."
            confirm-label="Delete"
            :danger="true"
            @confirm="confirmDelete"
            @cancel="deleteTargetId = null"
        />

    </AppLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import { BoltIcon, BanknotesIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    runs:       { type: Array,  default: () => [] },
    periods:    { type: Array,  default: () => [] },
    current:    { type: Object, required: true },
    cutoffDay:  { type: Number, default: 25 },
    filters:    { type: Object, default: () => ({}) },
});

// ── Cut-off form ──────────────────────────────────────────────────────────────

const cutoffForm = useForm({ cutoff_day: props.cutoffDay });

// Keep form in sync after a successful save (prop updates on page reload)
watch(() => props.cutoffDay, (val) => { cutoffForm.cutoff_day = val; });

function saveCutoff() {
    cutoffForm.post(route('payroll.cutoff'));
}

// ── Generate form ─────────────────────────────────────────────────────────────

const genForm = useForm({ from: props.current.from, to: props.current.to });

function submitGenerate() {
    genForm.post(route('payroll.store'));
}

// ── Filters ───────────────────────────────────────────────────────────────────

const filterPeriod = ref(props.filters.from ?? '');
const filterStatus = ref(props.filters.status ?? '');

function applyFilters() {
    router.get(route('payroll.index'), {
        from:   filterPeriod.value || undefined,
        status: filterStatus.value || undefined,
    }, { preserveState: true, replace: true });
}

// ── Computed ──────────────────────────────────────────────────────────────────

const draftCount = computed(() => props.runs.filter(r => r.status === 'draft').length);

const nextCutoffDate = computed(() => {
    const today = new Date();
    const d = props.cutoffDay;
    const dt = today.getDate() <= d
        ? new Date(today.getFullYear(), today.getMonth(), d)
        : new Date(today.getFullYear(), today.getMonth() + 1, d);
    const y  = dt.getFullYear();
    const m  = String(dt.getMonth() + 1).padStart(2, '0');
    const dd = String(dt.getDate()).padStart(2, '0');
    return `${y}-${m}-${dd}`;
});

const daysUntil = computed(() => {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const diff = new Date(nextCutoffDate.value + 'T00:00:00') - today;
    return Math.round(diff / 86400000);
});

// ── Actions ───────────────────────────────────────────────────────────────────

function approve(id) {
    router.post(route('payroll.approve', id), {}, { preserveScroll: true });
}

function approveAll() {
    const period = props.periods.find(p => p.from === filterPeriod.value);
    if (!period) return;
    router.post(route('payroll.approve-all'), { from: period.from, to: period.to }, { preserveScroll: true });
}

const deleteTargetId = ref(null);

function remove(id) {
    deleteTargetId.value = id;
}

function confirmDelete() {
    router.delete(route('payroll.destroy', deleteTargetId.value), { preserveScroll: true });
    deleteTargetId.value = null;
}

// ── Helpers ───────────────────────────────────────────────────────────────────

function formatDate(d) {
    return new Date(d + 'T00:00:00').toLocaleDateString('en-GB', {
        day: '2-digit', month: 'short', year: 'numeric',
    });
}
</script>
