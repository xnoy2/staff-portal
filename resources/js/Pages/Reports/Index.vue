<template>
    <AppLayout title="Reports">
        <div class="max-w-6xl mx-auto space-y-5">

            <!-- Header -->
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div>
                    <h1 class="text-lg font-semibold text-gray-800">Reports</h1>
                    <p class="text-xs text-gray-500 mt-0.5">{{ periodLabel }}</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <div class="grid grid-cols-2 sm:flex sm:flex-wrap gap-3">
                    <div class="sm:flex-1 sm:min-w-24">
                        <label class="block text-xs text-gray-500 mb-1">Year</label>
                        <select v-model.number="filters.year" @change="applyFilters" class="w-full text-sm border-gray-200 rounded-lg focus:ring-[#EF233C] focus:border-[#EF233C]">
                            <option v-for="y in yearOptions" :key="y" :value="y">{{ y }}</option>
                        </select>
                    </div>
                    <div class="sm:flex-1 sm:min-w-32">
                        <label class="block text-xs text-gray-500 mb-1">Month</label>
                        <select v-model="filters.month" @change="applyFilters" class="w-full text-sm border-gray-200 rounded-lg focus:ring-[#EF233C] focus:border-[#EF233C]">
                            <option value="">Full year</option>
                            <option v-for="m in months" :key="m.value" :value="m.value">{{ m.label }}</option>
                        </select>
                    </div>
                    <div class="col-span-2 sm:flex-1 sm:min-w-40">
                        <label class="block text-xs text-gray-500 mb-1">Staff Member</label>
                        <select v-model="filters.user_id" @change="applyFilters" class="w-full text-sm border-gray-200 rounded-lg focus:ring-[#EF233C] focus:border-[#EF233C]">
                            <option value="">All staff</option>
                            <option v-for="s in staffList" :key="s.id" :value="s.id">{{ s.name }}</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Summary totals -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
                    <p class="text-2xl font-bold text-[#EF233C]">{{ totals.total_hours }}</p>
                    <p class="text-xs text-gray-500 mt-1">Total Hours</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
                    <p class="text-2xl font-bold text-amber-500">{{ totals.overtime_hours }}</p>
                    <p class="text-xs text-gray-500 mt-1">Overtime Hours</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
                    <p class="text-2xl font-bold text-gray-700">{{ totals.total_entries }}</p>
                    <p class="text-xs text-gray-500 mt-1">Shift Entries</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
                    <p class="text-2xl font-bold text-blue-600">{{ totals.staff_count }}</p>
                    <p class="text-xs text-gray-500 mt-1">Active Staff</p>
                </div>
            </div>

            <!-- Attendance Summary Table -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-gray-800">Attendance Summary</h2>
                    <span class="text-xs text-gray-400">{{ attendanceSummary.length }} staff</span>
                </div>

                <div v-if="attendanceSummary.length === 0" class="py-12 text-center text-sm text-gray-400">
                    No attendance data for this period.
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 border-b border-gray-100">
                            <tr>
                                <th class="text-left text-xs font-medium text-gray-500 px-4 py-3">Staff</th>
                                <th class="text-right text-xs font-medium text-gray-500 px-4 py-3">Shifts</th>
                                <th class="text-right text-xs font-medium text-gray-500 px-4 py-3">Total Hours</th>
                                <th class="text-right text-xs font-medium text-gray-500 px-4 py-3">Overtime</th>
                                <th class="text-right text-xs font-medium text-gray-500 px-4 py-3 hidden sm:table-cell">Pending</th>
                                <th class="text-right text-xs font-medium text-gray-500 px-4 py-3 hidden md:table-cell">Avg / Shift</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="row in attendanceSummary" :key="row.user?.id" class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <img :src="row.user?.avatar_url" :alt="row.user?.name" class="w-7 h-7 rounded-full object-cover flex-shrink-0" />
                                        <div class="min-w-0">
                                            <p class="text-gray-800 font-medium text-sm truncate">{{ row.user?.name }}</p>
                                            <p class="text-xs text-gray-400 font-mono">{{ row.user?.employee_id }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-right text-gray-700">{{ row.total_entries }}</td>
                                <td class="px-4 py-3 text-right font-semibold text-gray-800">{{ row.total_hours }}h</td>
                                <td class="px-4 py-3 text-right">
                                    <span v-if="row.overtime_hours > 0" class="text-xs bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full font-medium">
                                        {{ row.overtime_hours }}h
                                    </span>
                                    <span v-else class="text-gray-300">—</span>
                                </td>
                                <td class="px-4 py-3 text-right hidden sm:table-cell">
                                    <span v-if="row.pending_count > 0" class="text-xs bg-amber-50 text-amber-600 px-2 py-0.5 rounded-full">
                                        {{ row.pending_count }}
                                    </span>
                                    <span v-else class="text-gray-300">—</span>
                                </td>
                                <td class="px-4 py-3 text-right text-gray-500 hidden md:table-cell">
                                    {{ row.total_entries > 0 ? (row.total_hours / row.total_entries).toFixed(1) + 'h' : '—' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Payroll Export -->
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-sm font-semibold text-gray-800">Payroll Export</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Download approved shifts as a CSV with gross pay calculations.</p>
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">From</label>
                        <input v-model="payroll.from" type="date" class="w-full text-sm border-gray-200 rounded-lg focus:ring-[#EF233C] focus:border-[#EF233C]" />
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">To</label>
                        <input v-model="payroll.to" type="date" class="w-full text-sm border-gray-200 rounded-lg focus:ring-[#EF233C] focus:border-[#EF233C]" />
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Staff Member (optional)</label>
                        <select v-model="payroll.user_id" class="w-full text-sm border-gray-200 rounded-lg focus:ring-[#EF233C] focus:border-[#EF233C]">
                            <option value="">All staff</option>
                            <option v-for="s in staffList" :key="s.id" :value="s.id">{{ s.name }}</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button
                            @click="downloadPayroll"
                            :disabled="!payroll.from || !payroll.to"
                            class="w-full bg-[#EF233C] hover:bg-[#D90429] disabled:opacity-40 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors flex items-center justify-center gap-2"
                        >
                            <ArrowDownTrayIcon class="w-4 h-4" />
                            Export CSV
                        </button>
                    </div>
                </div>
            </div>

            <!-- Leave Balance Table -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-gray-800">Leave Balance — {{ year }}</h2>
                    <span class="text-xs text-gray-400">{{ leaveSummary.length }} staff</span>
                </div>

                <div v-if="leaveSummary.length === 0" class="py-12 text-center text-sm text-gray-400">
                    No leave data for this period.
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 border-b border-gray-100">
                            <tr>
                                <th class="text-left text-xs font-medium text-gray-500 px-4 py-3">Staff</th>
                                <th class="text-right text-xs font-medium text-gray-500 px-4 py-3">Entitlement</th>
                                <th class="text-right text-xs font-medium text-gray-500 px-4 py-3">Used</th>
                                <th class="text-right text-xs font-medium text-gray-500 px-4 py-3 hidden sm:table-cell">Pending</th>
                                <th class="text-right text-xs font-medium text-gray-500 px-4 py-3">Remaining</th>
                                <th class="text-right text-xs font-medium text-gray-500 px-4 py-3 hidden md:table-cell">Sick</th>
                                <th class="text-right text-xs font-medium text-gray-500 px-4 py-3 hidden md:table-cell">Unpaid</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="row in leaveSummary" :key="row.user?.id" class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <img :src="row.user?.avatar_url" :alt="row.user?.name" class="w-7 h-7 rounded-full object-cover flex-shrink-0" />
                                        <div class="min-w-0">
                                            <p class="text-gray-800 font-medium text-sm truncate">{{ row.user?.name }}</p>
                                            <p class="text-xs text-gray-400 font-mono">{{ row.user?.employee_id }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-right text-gray-600">{{ row.user?.entitlement }}d</td>
                                <td class="px-4 py-3 text-right font-semibold text-red-600">{{ row.annual_used }}d</td>
                                <td class="px-4 py-3 text-right hidden sm:table-cell">
                                    <span v-if="row.annual_pending > 0" class="text-xs text-amber-600">{{ row.annual_pending }}d</span>
                                    <span v-else class="text-gray-300">—</span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <span :class="row.annual_remaining <= 3 ? 'text-red-600 font-semibold' : 'text-green-600 font-semibold'">
                                        {{ row.annual_remaining }}d
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right text-orange-500 hidden md:table-cell">
                                    <span v-if="row.sick_used > 0">{{ row.sick_used }}d</span>
                                    <span v-else class="text-gray-300">—</span>
                                </td>
                                <td class="px-4 py-3 text-right text-gray-500 hidden md:table-cell">
                                    <span v-if="row.unpaid_used > 0">{{ row.unpaid_used }}d</span>
                                    <span v-else class="text-gray-300">—</span>
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
import { reactive, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ArrowDownTrayIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    attendanceSummary: { type: Array,  default: () => [] },
    leaveSummary:      { type: Array,  default: () => [] },
    totals:            { type: Object, required: true },
    staffList:         { type: Array,  default: () => [] },
    year:              { type: Number, required: true },
    month:             { type: String, default: '' },
    filters:           { type: Object, default: () => ({}) },
});

const filters = reactive({
    year:    props.filters.year    ?? new Date().getFullYear(),
    month:   props.filters.month   ?? '',
    user_id: props.filters.user_id ?? '',
});

const yearOptions = computed(() => {
    const cur = new Date().getFullYear();
    return [cur, cur - 1, cur - 2, cur - 3];
});

const months = [
    { value: '1', label: 'January' }, { value: '2',  label: 'February' }, { value: '3',  label: 'March' },
    { value: '4', label: 'April' },   { value: '5',  label: 'May' },      { value: '6',  label: 'June' },
    { value: '7', label: 'July' },    { value: '8',  label: 'August' },   { value: '9',  label: 'September' },
    { value: '10', label: 'October' },{ value: '11', label: 'November' }, { value: '12', label: 'December' },
];

const periodLabel = computed(() => {
    if (filters.month) {
        const m = months.find(m => m.value === String(filters.month));
        return `${m?.label ?? ''} ${filters.year}`;
    }
    return `Full year ${filters.year}`;
});

const payroll = reactive({
    from:    '',
    to:      '',
    user_id: '',
});

function downloadPayroll() {
    const params = new URLSearchParams({ from: payroll.from, to: payroll.to });
    if (payroll.user_id) params.set('user_id', payroll.user_id);
    window.location.href = route('payroll.export') + '?' + params.toString();
}

function applyFilters() {
    router.get(route('reports'), {
        year:    filters.year,
        month:   filters.month   || undefined,
        user_id: filters.user_id || undefined,
    }, { preserveState: true, replace: true });
}
</script>
