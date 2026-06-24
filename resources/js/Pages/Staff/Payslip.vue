<template>
    <AppLayout :title="`Payslip — ${staffMember.name}`">
        <div class="max-w-4xl mx-auto space-y-4">

            <!-- Controls — hidden on print -->
            <div class="no-print flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <Link
                    :href="selfView ? route('dashboard') : route('staff.show', staffMember.id)"
                    class="text-sm text-gray-500 hover:text-gray-700 flex items-center gap-1.5"
                >
                    <ArrowLeftIcon class="w-4 h-4" />
                    {{ selfView ? 'Back to Dashboard' : 'Back to Profile' }}
                </Link>
                <div class="flex flex-wrap items-center gap-2">
                    <!-- Locked mode: show status badge + approve button -->
                    <template v-if="isLocked">
                        <span :class="runStatus === 'approved'
                            ? 'bg-green-100 text-green-700 border border-green-200'
                            : 'bg-amber-50 text-amber-700 border border-amber-200'"
                            class="text-xs font-semibold px-2.5 py-1 rounded-full capitalize flex items-center gap-1"
                        >
                            <LockClosedIcon class="w-3 h-3" /> Locked · {{ runStatus }}
                        </span>
                        <button
                            v-if="runStatus === 'draft'"
                            @click="approveRun"
                            class="text-xs bg-green-50 hover:bg-green-100 text-green-700 border border-green-200 px-3 py-1.5 rounded-lg transition-colors font-medium"
                        >Approve Payslip</button>
                    </template>
                    <!-- Live mode: period selectors -->
                    <template v-else>
                        <button @click="setPreset('this_month')" :class="presetClass('this_month')">This Month</button>
                        <button @click="setPreset('last_month')" :class="presetClass('last_month')">Last Month</button>
                        <div class="flex items-center gap-1.5">
                            <input v-model="customFrom" type="date" class="text-xs border border-gray-200 rounded-lg px-2 py-1.5 focus:outline-none focus:ring-1 focus:ring-[#EF233C] focus:border-[#EF233C]" />
                            <span class="text-xs text-gray-400">–</span>
                            <input v-model="customTo" type="date" class="text-xs border border-gray-200 rounded-lg px-2 py-1.5 focus:outline-none focus:ring-1 focus:ring-[#EF233C] focus:border-[#EF233C]" />
                            <button @click="applyCustom" :disabled="!customFrom || !customTo" class="text-xs bg-gray-100 hover:bg-gray-200 disabled:opacity-40 text-gray-700 px-3 py-1.5 rounded-lg transition-colors">Apply</button>
                        </div>
                    </template>
                    <button @click="printPayslip" class="bg-[#EF233C] hover:bg-[#D90429] text-white text-xs font-semibold px-4 py-1.5 rounded-lg transition-colors flex items-center gap-1.5">
                        <PrinterIcon class="w-3.5 h-3.5" /> Print / PDF
                    </button>
                </div>
            </div>

            <!-- Past approved payslips -->
            <div v-if="pastRuns.length > 0" class="no-print bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-widest">Payslip History</p>
                    <span class="text-xs text-gray-400">{{ pastRuns.length }} record{{ pastRuns.length !== 1 ? 's' : '' }}</span>
                </div>
                <div class="divide-y divide-gray-50">
                    <a
                        v-for="r in pastRuns"
                        :key="r.id"
                        :href="payslipUrl(r.id)"
                        class="flex items-center justify-between px-4 py-3 hover:bg-gray-50 transition-colors cursor-pointer"
                        :class="{ 'bg-[#EF233C]/5': runId === r.id }"
                    >
                        <div class="flex items-center gap-3">
                            <span :class="r.status === 'approved'
                                ? 'bg-green-100 text-green-700 border-green-200'
                                : 'bg-amber-50 text-amber-700 border-amber-200'"
                                class="text-[10px] font-bold px-2 py-0.5 rounded-full border capitalize"
                            >{{ r.status }}</span>
                            <span class="text-sm text-gray-700">{{ formatDate(r.period_from) }} – {{ formatDate(r.period_to) }}</span>
                        </div>
                        <span class="text-sm font-semibold" :class="r.has_rate ? 'text-gray-800' : 'text-amber-500'">
                            {{ r.has_rate ? '£' + r.gross_pay.toFixed(2) : 'No rate' }}
                        </span>
                    </a>
                </div>
            </div>

            <!-- Payslip document -->
            <div id="payslip" class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm">

                <!-- Header -->
                <div class="payslip-header bg-[#2B2D42] px-8 py-6 flex items-start justify-between">
                    <div>
                        <p class="text-white/50 text-xs font-semibold uppercase tracking-widest mb-1">BCF Staff Portal</p>
                        <p class="text-white text-2xl font-black tracking-tight">PAYSLIP</p>
                    </div>
                    <div class="text-right">
                        <p class="text-white/50 text-[11px] uppercase tracking-wide mb-1">Pay Period</p>
                        <p class="text-white font-semibold text-sm">{{ formatDate(period.from) }}</p>
                        <p class="text-white/40 text-xs my-0.5">to</p>
                        <p class="text-white font-semibold text-sm">{{ formatDate(period.to) }}</p>
                    </div>
                </div>

                <!-- Employee + details row -->
                <div class="px-8 py-5 border-b border-gray-100 grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Employee</p>
                        <p class="text-gray-900 font-bold text-lg leading-tight">{{ staffMember.name }}</p>
                        <p class="text-gray-500 text-sm mt-0.5">{{ staffMember.email }}</p>
                        <p class="mt-1.5">
                            <span class="text-xs font-mono bg-gray-100 text-gray-600 px-2 py-0.5 rounded font-semibold">{{ staffMember.employee_id }}</span>
                        </p>
                        <p class="text-xs text-gray-400 mt-1.5 capitalize">{{ (staffMember.roles[0] ?? 'staff').replace('_', ' ') }}</p>
                    </div>
                    <div class="sm:text-right">
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Details</p>
                        <div class="space-y-1">
                            <p class="text-sm text-gray-600">Hire Date: <span class="font-semibold text-gray-800">{{ staffMember.hire_date ? formatDate(staffMember.hire_date) : '—' }}</span></p>
                            <p class="text-sm text-gray-600">Contract: <span class="font-semibold text-gray-800">{{ staffMember.contracted_hours }}h / week</span></p>
                            <p class="text-sm text-gray-600">Hourly Rate: <span class="font-semibold text-gray-800">{{ hasRate ? '£' + Number(hourlyRate).toFixed(2) : 'Not set' }}</span></p>
                        </div>
                        <p class="text-xs text-gray-400 mt-3">Generated {{ generatedAt }}</p>
                    </div>
                </div>

                <!-- Earnings summary -->
                <div class="px-8 py-5 border-b border-gray-100">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-3">Earnings Summary</p>
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-100">
                                <th class="text-left text-xs text-gray-400 font-medium pb-2">Description</th>
                                <th class="text-right text-xs text-gray-400 font-medium pb-2">Hours</th>
                                <th class="text-right text-xs text-gray-400 font-medium pb-2">Rate</th>
                                <th class="text-right text-xs text-gray-400 font-medium pb-2">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="border-b border-gray-50">
                                <td class="py-2.5 text-gray-700 font-medium">Regular Pay</td>
                                <td class="py-2.5 text-right font-mono text-gray-600">{{ regularHours.toFixed(2) }}h</td>
                                <td class="py-2.5 text-right text-gray-400 text-xs">{{ hasRate ? '£' + Number(hourlyRate).toFixed(2) + '/hr' : '—' }}</td>
                                <td class="py-2.5 text-right font-semibold text-gray-800">{{ hasRate ? '£' + regularPay.toFixed(2) : '—' }}</td>
                            </tr>
                            <tr v-if="overtimeHours > 0" class="border-b border-gray-50">
                                <td class="py-2.5 text-gray-700 font-medium">
                                    Overtime Pay
                                    <span class="text-xs text-amber-500 ml-1 font-normal">(×1.5)</span>
                                </td>
                                <td class="py-2.5 text-right font-mono text-gray-600">{{ overtimeHours.toFixed(2) }}h</td>
                                <td class="py-2.5 text-right text-gray-400 text-xs">{{ hasRate ? '£' + (Number(hourlyRate) * 1.5).toFixed(2) + '/hr' : '—' }}</td>
                                <td class="py-2.5 text-right font-semibold text-amber-600">{{ hasRate ? '£' + overtimePay.toFixed(2) : '—' }}</td>
                            </tr>
                            <tr v-if="leavePay > 0" class="border-b border-gray-50">
                                <td class="py-2.5 text-gray-700 font-medium">
                                    Leave Pay
                                    <span class="text-xs text-emerald-500 ml-1 font-normal">(paid leave)</span>
                                </td>
                                <td class="py-2.5 text-right font-mono text-gray-600">{{ leaveDays.toFixed(1) }}d</td>
                                <td class="py-2.5 text-right text-gray-400 text-xs">—</td>
                                <td class="py-2.5 text-right font-semibold text-emerald-600">£{{ leavePay.toFixed(2) }}</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="border-t-2 border-gray-200">
                                <td class="pt-3 pb-1 font-black text-gray-900 text-base">GROSS TOTAL</td>
                                <td class="pt-3 pb-1 text-right font-mono font-bold text-gray-700">{{ totalHours.toFixed(2) }}h</td>
                                <td class="pt-3 pb-1 text-right text-gray-300 text-xs">—</td>
                                <td class="pt-3 pb-1 text-right font-black text-[#EF233C] text-xl">{{ hasRate ? '£' + grossPay.toFixed(2) : '—' }}</td>
                            </tr>
                        </tfoot>
                    </table>
                    <p v-if="!hasRate" class="mt-3 text-xs text-amber-600 bg-amber-50 border border-amber-100 rounded-lg px-3 py-2">
                        No hourly rate set for this employee — pay amounts cannot be calculated. Set a rate via the staff profile to enable pay calculations.
                    </p>
                </div>

                <!-- Deductions (locked payslips only) -->
                <div v-if="isLocked && (deductionForm.deductions.length > 0 || (canManage && runStatus === 'draft'))" class="px-8 py-5 border-b border-gray-100">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Deductions</p>
                        <button
                            v-if="canManage && runStatus === 'draft'"
                            @click="addDeduction"
                            class="text-xs text-[#EF233C] hover:underline font-medium"
                        >+ Add deduction</button>
                    </div>

                    <div v-if="deductionForm.deductions.length === 0" class="text-sm text-gray-400 italic">
                        No deductions added.
                    </div>

                    <div v-for="(d, i) in deductionForm.deductions" :key="i" class="flex items-center gap-2 mb-2">
                        <template v-if="canManage && runStatus === 'draft'">
                            <input
                                v-model="d.description"
                                placeholder="Description (e.g. Tax, Pension)"
                                class="flex-1 text-sm border border-gray-200 rounded-lg px-2.5 py-1.5 focus:outline-none focus:ring-1 focus:ring-[#EF233C] focus:border-[#EF233C]"
                            />
                            <div class="flex items-center border border-gray-200 rounded-lg overflow-hidden">
                                <span class="px-2 text-sm text-gray-400 bg-gray-50 border-r border-gray-200">£</span>
                                <input
                                    v-model="d.amount"
                                    type="number" min="0" step="0.01"
                                    placeholder="0.00"
                                    class="w-24 text-sm px-2.5 py-1.5 focus:outline-none focus:ring-1 focus:ring-[#EF233C]"
                                />
                            </div>
                            <button @click="removeDeduction(i)" class="text-gray-300 hover:text-red-500 transition-colors px-1 text-sm">✕</button>
                        </template>
                        <template v-else>
                            <span class="flex-1 text-sm text-gray-700">{{ d.description }}</span>
                            <span class="text-sm font-semibold text-red-600">−£{{ Number(d.amount).toFixed(2) }}</span>
                        </template>
                    </div>

                    <div v-if="canManage && runStatus === 'draft'" class="mt-3 flex justify-end">
                        <button
                            @click="saveDeductions"
                            :disabled="deductionForm.processing"
                            class="text-xs bg-[#EF233C] hover:bg-[#D90429] disabled:opacity-50 text-white font-semibold px-4 py-1.5 rounded-lg transition-colors"
                        >{{ deductionForm.processing ? 'Saving…' : 'Save Deductions' }}</button>
                    </div>

                    <div v-if="totalDeductions > 0 || (props.netPay !== null)" class="mt-4 pt-3 border-t-2 border-gray-200 flex items-center justify-between">
                        <div>
                            <p class="text-xs text-gray-400">Total deductions</p>
                            <p class="text-sm font-semibold text-red-600">−£{{ totalDeductions.toFixed(2) }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-gray-400 mb-0.5">Net Pay</p>
                            <p class="font-black text-gray-900 text-xl">£{{ displayNetPay.toFixed(2) }}</p>
                        </div>
                    </div>
                </div>

                <!-- Shift details -->
                <div class="px-8 py-5">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">
                        Shift Details
                        <span class="normal-case font-normal ml-1">— {{ entries.length }} approved {{ entries.length === 1 ? 'shift' : 'shifts' }}</span>
                    </p>
                    <p v-if="entries.length" class="text-[11px] text-gray-400 mb-3">Regular pay is capped at 8h per shift. Hours beyond 8 are paid only when filed and approved as overtime.</p>

                    <div v-if="entries.length === 0" class="text-center py-10 border border-dashed border-gray-200 rounded-xl">
                        <p class="text-sm text-gray-500 font-medium">No approved shifts in this period.</p>
                        <p v-if="isLocked" class="text-xs text-gray-400 mt-1">
                            This payslip was generated with zero hours — no attendance was approved between
                            {{ formatDate(period.from) }} and {{ formatDate(period.to) }}.
                        </p>
                    </div>

                    <div v-else class="overflow-x-auto -mx-2">
                        <table class="w-full text-xs min-w-[520px]">
                            <thead>
                                <tr class="border-b border-gray-100">
                                    <th class="text-left text-gray-400 font-medium pb-2 px-2">Date</th>
                                    <th class="text-right text-gray-400 font-medium pb-2 px-2">Clock In</th>
                                    <th class="text-right text-gray-400 font-medium pb-2 px-2">Clock Out</th>
                                    <th class="text-right text-gray-400 font-medium pb-2 px-2">Break</th>
                                    <th class="text-right text-gray-400 font-medium pb-2 px-2">Reg. Hrs</th>
                                    <th class="text-right text-gray-400 font-medium pb-2 px-2">OT Hrs</th>
                                    <th class="text-right text-gray-400 font-medium pb-2 px-2">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="(e, i) in entries" :key="i" class="hover:bg-gray-50 transition-colors">
                                    <td class="py-2 px-2 text-gray-700">
                                        <span class="font-semibold">{{ e.day }}</span>
                                        <span class="text-gray-400 ml-1.5">{{ e.date }}</span>
                                        <span v-if="e.ot_type" class="ml-1.5 text-[10px] font-bold uppercase tracking-wide px-1.5 py-0.5 rounded bg-amber-100 text-amber-700">{{ e.ot_type }}</span>
                                    </td>
                                    <td class="py-2 px-2 text-right font-mono text-gray-600">{{ e.clock_in }}</td>
                                    <td class="py-2 px-2 text-right font-mono text-gray-600">{{ e.clock_out ?? '—' }}</td>
                                    <td class="py-2 px-2 text-right text-gray-400">{{ e.break_mins > 0 ? e.break_mins + 'm' : '—' }}</td>
                                    <td class="py-2 px-2 text-right text-gray-600">{{ e.regular_hours > 0 ? e.regular_hours + 'h' : '—' }}</td>
                                    <td class="py-2 px-2 text-right">
                                        <span v-if="e.overtime_hours > 0" class="text-amber-600 font-semibold">{{ e.overtime_hours }}h</span>
                                        <span v-else class="text-gray-300">—</span>
                                    </td>
                                    <td class="py-2 px-2 text-right">
                                        <span class="font-bold text-gray-800">{{ e.total_hours }}h</span>
                                        <span
                                            v-if="unpaidHours(e) > 0"
                                            class="block text-[10px] font-medium text-red-500"
                                            title="Worked beyond 8h with no approved overtime — these hours are not paid. File and approve an overtime request to include them."
                                        >{{ unpaidHours(e) }}h unpaid</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-8 py-3 bg-gray-50 border-t border-gray-100">
                    <div v-if="approvedBy" class="flex items-center justify-between mb-2 pb-2 border-b border-gray-100">
                        <p class="text-[11px] text-green-600 font-medium flex items-center gap-1.5">
                            <span class="inline-block w-1.5 h-1.5 rounded-full bg-green-500"></span>
                            Approved by <span class="font-bold">{{ approvedBy }}</span>
                            <span v-if="approvedAt"> on {{ formatDate(approvedAt) }}</span>
                        </p>
                    </div>
                    <div class="flex items-center justify-between">
                        <p class="text-[11px] text-gray-400 italic">Generated automatically from approved attendance records.</p>
                        <p class="text-[11px] text-gray-400 font-mono font-semibold">{{ staffMember.employee_id }}</p>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ArrowLeftIcon, PrinterIcon, LockClosedIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    staffMember:   { type: Object,  required: true },
    period:        { type: Object,  required: true },
    entries:       { type: Array,   default: () => [] },
    regularHours:  { type: Number,  default: 0 },
    overtimeHours: { type: Number,  default: 0 },
    totalHours:    { type: Number,  default: 0 },
    hourlyRate:    { type: Number,  default: 0 },
    regularPay:    { type: Number,  default: 0 },
    overtimePay:   { type: Number,  default: 0 },
    leavePay:      { type: Number,  default: 0 },
    leaveDays:     { type: Number,  default: 0 },
    grossPay:      { type: Number,  default: 0 },
    hasRate:       { type: Boolean, default: false },
    isLocked:      { type: Boolean, default: false },
    runId:         { type: String,  default: null },
    runStatus:     { type: String,  default: null },
    generatedAt:   { type: String,  default: null },
    approvedBy:    { type: String,  default: null },
    approvedAt:    { type: String,  default: null },
    deductions:    { type: Array,   default: () => [] },
    netPay:        { type: Number,  default: null },
    canManage:     { type: Boolean, default: false },
    pastRuns:      { type: Array,   default: () => [] },
    selfView:      { type: Boolean, default: false },
});

const customFrom = ref(props.period.from);
const customTo   = ref(props.period.to);

const generatedAt = props.generatedAt
    ? new Date(props.generatedAt + 'T00:00:00').toLocaleDateString('en-GB', { day: '2-digit', month: 'long', year: 'numeric' })
    : new Date().toLocaleDateString('en-GB', { day: '2-digit', month: 'long', year: 'numeric' });

// ── Deductions ────────────────────────────────────────────────────────────────

const deductionForm = useForm({
    deductions: props.deductions.map(d => ({ description: d.description, amount: String(d.amount) })),
});

function addDeduction() {
    deductionForm.deductions.push({ description: '', amount: '' });
}

function removeDeduction(i) {
    deductionForm.deductions.splice(i, 1);
}

function saveDeductions() {
    deductionForm.patch(route('payroll.deductions', props.runId), { preserveScroll: true });
}

const totalDeductions = computed(() =>
    deductionForm.deductions.reduce((sum, d) => sum + (parseFloat(d.amount) || 0), 0)
);

const displayNetPay = computed(() => props.grossPay - totalDeductions.value);

// Hours worked beyond what is paid (over the 8h cap with no approved OT).
function unpaidHours(e) {
    const gap = (e.total_hours ?? 0) - (e.regular_hours ?? 0) - (e.overtime_hours ?? 0);
    return Math.round(gap * 100) / 100;
}

function localDateStr(d) {
    const y  = d.getFullYear();
    const m  = String(d.getMonth() + 1).padStart(2, '0');
    const dd = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${dd}`;
}

function formatDate(d) {
    return new Date(d + 'T00:00:00').toLocaleDateString('en-GB', {
        day: '2-digit', month: 'short', year: 'numeric',
    });
}

function thisMonth() {
    const n = new Date();
    return {
        from: localDateStr(new Date(n.getFullYear(), n.getMonth(), 1)),
        to:   localDateStr(new Date(n.getFullYear(), n.getMonth() + 1, 0)),
    };
}

function lastMonth() {
    const n = new Date();
    return {
        from: localDateStr(new Date(n.getFullYear(), n.getMonth() - 1, 1)),
        to:   localDateStr(new Date(n.getFullYear(), n.getMonth(), 0)),
    };
}

function baseRoute() {
    return props.selfView
        ? route('my-payslip')
        : route('staff.payslip', props.staffMember.id);
}

function payslipUrl(runId) {
    return baseRoute() + '?run_id=' + runId;
}

function setPreset(preset) {
    const { from, to } = preset === 'this_month' ? thisMonth() : lastMonth();
    router.get(baseRoute(), { from, to }, { preserveScroll: true });
}

function isPreset(preset) {
    const { from, to } = preset === 'this_month' ? thisMonth() : lastMonth();
    return props.period.from === from && props.period.to === to;
}

function presetClass(preset) {
    return [
        'text-xs px-3 py-1.5 rounded-lg border transition-colors',
        isPreset(preset)
            ? 'bg-[#EF233C] text-white border-[#EF233C]'
            : 'border-gray-200 text-gray-600 hover:bg-gray-50',
    ];
}

function applyCustom() {
    if (!customFrom.value || !customTo.value) return;
    router.get(baseRoute(), {
        from: customFrom.value,
        to:   customTo.value,
    }, { preserveScroll: true });
}

function approveRun() {
    if (!props.runId) return;
    router.post(route('payroll.approve', props.runId), {}, { preserveScroll: true });
}

function printPayslip() {
    window.print();
}
</script>

<style>
@media print {
    .no-print { display: none !important; }

    /* Hide everything, then reveal only the payslip */
    body * { visibility: hidden; }
    #payslip,
    #payslip * { visibility: visible; }

    #payslip {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        border: none !important;
        border-radius: 0 !important;
        box-shadow: none !important;
    }

    /* Preserve dark header background colour when printing */
    .payslip-header {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    @page { margin: 1cm; }
}
</style>
