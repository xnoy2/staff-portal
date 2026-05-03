<template>
    <AppLayout title="Payroll">
        <div class="max-w-6xl mx-auto space-y-5">

            <!-- Header -->
            <div>
                <h1 class="text-lg font-semibold text-gray-800">Payroll</h1>
                <p class="text-xs text-gray-500 mt-0.5">Cut-off: {{ cutoffDay }}th of every month · Auto-generates at midnight on cut-off day</p>
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
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { BoltIcon, BanknotesIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    runs:       { type: Array,  default: () => [] },
    periods:    { type: Array,  default: () => [] },
    current:    { type: Object, required: true },
    cutoffDay:  { type: Number, default: 25 },
    filters:    { type: Object, default: () => ({}) },
});

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
    if (today.getDate() <= d) {
        return new Date(today.getFullYear(), today.getMonth(), d);
    }
    return new Date(today.getFullYear(), today.getMonth() + 1, d);
});

const daysUntil = computed(() => {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const diff = nextCutoffDate.value - today;
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

function remove(id) {
    if (!confirm('Delete this draft payslip?')) return;
    router.delete(route('payroll.destroy', id), { preserveScroll: true });
}

// ── Helpers ───────────────────────────────────────────────────────────────────

function formatDate(d) {
    return new Date(d + 'T00:00:00').toLocaleDateString('en-GB', {
        day: '2-digit', month: 'short', year: 'numeric',
    });
}
</script>
