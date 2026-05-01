<template>
    <AppLayout title="Settings">
        <div class="max-w-3xl mx-auto space-y-4">

            <div>
                <h1 class="text-lg font-semibold text-gray-800">Settings</h1>
                <p class="text-xs text-gray-500 mt-0.5">Manage system configuration and your personal preferences.</p>
            </div>

            <!-- Tab bar -->
            <div class="bg-white rounded-xl border border-gray-200 flex overflow-x-auto">
                <button
                    v-for="tab in visibleTabs"
                    :key="tab.key"
                    @click="activeTab = tab.key"
                    :class="[
                        'flex items-center gap-2 px-5 py-3.5 text-sm font-medium whitespace-nowrap border-b-2 transition-colors',
                        activeTab === tab.key
                            ? 'border-[#EF233C] text-[#EF233C]'
                            : 'border-transparent text-gray-500 hover:text-gray-700',
                    ]"
                >
                    <component :is="tab.icon" class="w-4 h-4" />
                    {{ tab.label }}
                </button>
            </div>

            <!-- ── Business ─────────────────────────────────────────────── -->
            <div v-show="activeTab === 'business'" class="bg-white rounded-xl border border-gray-200 divide-y divide-gray-100">
                <div class="px-6 py-4">
                    <h2 class="text-sm font-semibold text-gray-800">Business Settings</h2>
                    <p class="text-xs text-gray-400 mt-0.5">Company-wide configuration applied across the portal.</p>
                </div>
                <form @submit.prevent="save(businessForm, 'settings.update')" class="px-6 py-5 space-y-5">
                    <FieldRow label="Company Name" hint="Shown in reports and email footers.">
                        <input v-model="businessForm.company_name" type="text" class="field-input" placeholder="BCF" />
                    </FieldRow>
                    <FieldRow label="Timezone" hint="Used for all time displays and reports.">
                        <select v-model="businessForm.company_timezone" class="field-input">
                            <option v-for="tz in timezones" :key="tz.value" :value="tz.value">{{ tz.label }}</option>
                        </select>
                    </FieldRow>
                    <FieldRow label="Date Format" hint="How dates are displayed throughout the app.">
                        <select v-model="businessForm.date_format" class="field-input">
                            <option value="DD/MM/YYYY">DD/MM/YYYY</option>
                            <option value="MM/DD/YYYY">MM/DD/YYYY</option>
                            <option value="YYYY-MM-DD">YYYY-MM-DD</option>
                        </select>
                    </FieldRow>
                    <FieldRow label="Currency Symbol" hint="Prefix used on monetary values.">
                        <input v-model="businessForm.currency_symbol" type="text" class="field-input w-24" placeholder="£" />
                    </FieldRow>
                    <div class="flex justify-end pt-2">
                        <SaveButton :processing="businessForm.processing" />
                    </div>
                </form>
            </div>

            <!-- ── Attendance ───────────────────────────────────────────── -->
            <div v-show="activeTab === 'attendance'" class="bg-white rounded-xl border border-gray-200 divide-y divide-gray-100">
                <div class="px-6 py-4">
                    <h2 class="text-sm font-semibold text-gray-800">Attendance Rules</h2>
                    <p class="text-xs text-gray-400 mt-0.5">Controls how clock-ins, overtime, and shift limits are handled.</p>
                </div>
                <form @submit.prevent="save(attendanceForm, 'settings.update')" class="px-6 py-5 space-y-5">
                    <FieldRow label="Overtime Threshold" hint="Hours per day before overtime kicks in.">
                        <div class="flex items-center gap-2">
                            <input v-model.number="attendanceForm.overtime_threshold_hours" type="number" min="1" max="24" class="field-input w-24" />
                            <span class="text-sm text-gray-500">hours / day</span>
                        </div>
                    </FieldRow>
                    <FieldRow label="Clock-in Grace Period" hint="Minutes of lateness allowed before flagging.">
                        <div class="flex items-center gap-2">
                            <input v-model.number="attendanceForm.clock_grace_period_minutes" type="number" min="0" max="60" class="field-input w-24" />
                            <span class="text-sm text-gray-500">minutes</span>
                        </div>
                    </FieldRow>
                    <FieldRow label="Max Shift Duration" hint="Shifts longer than this are flagged for review.">
                        <div class="flex items-center gap-2">
                            <input v-model.number="attendanceForm.max_shift_hours" type="number" min="1" max="24" class="field-input w-24" />
                            <span class="text-sm text-gray-500">hours</span>
                        </div>
                    </FieldRow>
                    <FieldRow label="Auto Clock-out" hint="Automatically close open entries after a set number of hours.">
                        <Toggle v-model="attendanceForm.auto_clockout_enabled" />
                    </FieldRow>
                    <FieldRow v-if="attendanceForm.auto_clockout_enabled" label="Auto Clock-out After" hint="Hours after which an open entry is auto-closed.">
                        <div class="flex items-center gap-2">
                            <input v-model.number="attendanceForm.auto_clockout_after_hours" type="number" min="1" max="24" class="field-input w-24" />
                            <span class="text-sm text-gray-500">hours</span>
                        </div>
                    </FieldRow>
                    <div class="flex justify-end pt-2">
                        <SaveButton :processing="attendanceForm.processing" />
                    </div>
                </form>
            </div>

            <!-- ── Leave ────────────────────────────────────────────────── -->
            <div v-show="activeTab === 'leave'" class="bg-white rounded-xl border border-gray-200 divide-y divide-gray-100">
                <div class="px-6 py-4">
                    <h2 class="text-sm font-semibold text-gray-800">Leave Policy</h2>
                    <p class="text-xs text-gray-400 mt-0.5">Default entitlements applied to new staff members.</p>
                </div>
                <form @submit.prevent="save(leaveForm, 'settings.update')" class="px-6 py-5 space-y-5">
                    <FieldRow label="Annual Leave" hint="Default days per year for new staff.">
                        <div class="flex items-center gap-2">
                            <input v-model.number="leaveForm.default_annual_leave_days" type="number" min="0" max="365" class="field-input w-24" />
                            <span class="text-sm text-gray-500">days / year</span>
                        </div>
                    </FieldRow>
                    <FieldRow label="Sick Leave" hint="Default sick days per year.">
                        <div class="flex items-center gap-2">
                            <input v-model.number="leaveForm.default_sick_leave_days" type="number" min="0" max="365" class="field-input w-24" />
                            <span class="text-sm text-gray-500">days / year</span>
                        </div>
                    </FieldRow>
                    <FieldRow label="Emergency Leave" hint="Default emergency days per year.">
                        <div class="flex items-center gap-2">
                            <input v-model.number="leaveForm.default_emergency_leave_days" type="number" min="0" max="30" class="field-input w-24" />
                            <span class="text-sm text-gray-500">days / year</span>
                        </div>
                    </FieldRow>
                    <FieldRow label="Require Approval" hint="Leave requests must be approved by a manager.">
                        <Toggle v-model="leaveForm.leave_approval_required" />
                    </FieldRow>
                    <div class="flex justify-end pt-2">
                        <SaveButton :processing="leaveForm.processing" />
                    </div>
                </form>
            </div>

            <!-- ── Appearance ───────────────────────────────────────────── -->
            <div v-show="activeTab === 'appearance'" class="bg-white rounded-xl border border-gray-200 divide-y divide-gray-100">
                <div class="px-6 py-4">
                    <h2 class="text-sm font-semibold text-gray-800">Appearance</h2>
                    <p class="text-xs text-gray-400 mt-0.5">Branding and visual identity settings.</p>
                </div>
                <form @submit.prevent="save(appearanceForm, 'settings.update')" class="px-6 py-5 space-y-5">
                    <FieldRow label="App Name" hint="Displayed in the browser tab and sidebar.">
                        <input v-model="appearanceForm.app_name" type="text" class="field-input" placeholder="Staff Portal" />
                    </FieldRow>
                    <FieldRow label="Primary Colour" hint="Accent colour used for buttons and highlights.">
                        <div class="flex items-center gap-3">
                            <input v-model="appearanceForm.primary_color" type="color" class="w-10 h-10 rounded-lg border border-gray-200 cursor-pointer p-0.5" />
                            <input v-model="appearanceForm.primary_color" type="text" class="field-input w-32 font-mono text-xs" placeholder="#EF233C" />
                        </div>
                    </FieldRow>
                    <FieldRow label="Sidebar Colour" hint="Background colour of the navigation sidebar.">
                        <div class="flex items-center gap-3">
                            <input v-model="appearanceForm.sidebar_color" type="color" class="w-10 h-10 rounded-lg border border-gray-200 cursor-pointer p-0.5" />
                            <input v-model="appearanceForm.sidebar_color" type="text" class="field-input w-32 font-mono text-xs" placeholder="#2B2D42" />
                        </div>
                    </FieldRow>
                    <div class="rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 text-xs text-amber-700">
                        Colour changes are stored immediately but require a frontend rebuild (<code class="font-mono">npm run build</code>) to apply across all Tailwind classes.
                    </div>
                    <div class="flex justify-end pt-2">
                        <SaveButton :processing="appearanceForm.processing" />
                    </div>
                </form>
            </div>

            <!-- ── My Preferences ───────────────────────────────────────── -->
            <div v-show="activeTab === 'preferences'" class="bg-white rounded-xl border border-gray-200 divide-y divide-gray-100">
                <div class="px-6 py-4">
                    <h2 class="text-sm font-semibold text-gray-800">My Preferences</h2>
                    <p class="text-xs text-gray-400 mt-0.5">Personal settings that only affect your account.</p>
                </div>
                <form @submit.prevent="save(prefsForm, 'settings.preferences')" class="px-6 py-5 space-y-5">
                    <FieldRow label="Email Notifications" hint="Receive email alerts for approvals and updates.">
                        <Toggle v-model="prefsForm.email_notifications" />
                    </FieldRow>
                    <FieldRow label="Default Calendar View" hint="Which view opens when you visit the Calendar.">
                        <select v-model="prefsForm.calendar_default_view" class="field-input">
                            <option value="month">Month</option>
                            <option value="week">Week</option>
                        </select>
                    </FieldRow>
                    <div class="flex justify-end pt-2">
                        <SaveButton :processing="prefsForm.processing" />
                    </div>
                </form>
            </div>

        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { usePermission } from '@/Composables/usePermission';
import {
    BuildingOfficeIcon,
    ClockIcon,
    CalendarDaysIcon,
    SwatchIcon,
    UserCircleIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    settings:    { type: Object, default: () => ({}) },
    preferences: { type: Object, default: () => ({}) },
});

const { isAdmin } = usePermission();
const activeTab   = ref(isAdmin.value ? 'business' : 'preferences');

// ── Tabs ──────────────────────────────────────────────────────────────────────

const allTabs = [
    { key: 'business',    label: 'Business',      icon: BuildingOfficeIcon, adminOnly: true  },
    { key: 'attendance',  label: 'Attendance',    icon: ClockIcon,          adminOnly: true  },
    { key: 'leave',       label: 'Leave Policy',  icon: CalendarDaysIcon,   adminOnly: true  },
    { key: 'appearance',  label: 'Appearance',    icon: SwatchIcon,         adminOnly: true  },
    { key: 'preferences', label: 'My Preferences',icon: UserCircleIcon,     adminOnly: false },
];

const visibleTabs = computed(() =>
    allTabs.filter(t => !t.adminOnly || isAdmin.value)
);

// ── Forms ─────────────────────────────────────────────────────────────────────

const s = props.settings;

const businessForm = useForm({
    company_name:     s.company_name     ?? 'BCF',
    company_timezone: s.company_timezone ?? 'UTC',
    date_format:      s.date_format      ?? 'DD/MM/YYYY',
    currency_symbol:  s.currency_symbol  ?? '£',
});

const attendanceForm = useForm({
    overtime_threshold_hours:   s.overtime_threshold_hours   ?? 8,
    clock_grace_period_minutes: s.clock_grace_period_minutes ?? 0,
    max_shift_hours:            s.max_shift_hours            ?? 16,
    auto_clockout_enabled:      s.auto_clockout_enabled      ?? false,
    auto_clockout_after_hours:  s.auto_clockout_after_hours  ?? 12,
});

const leaveForm = useForm({
    default_annual_leave_days:    s.default_annual_leave_days    ?? 14,
    default_sick_leave_days:      s.default_sick_leave_days      ?? 5,
    default_emergency_leave_days: s.default_emergency_leave_days ?? 3,
    leave_approval_required:      s.leave_approval_required      ?? true,
});

const appearanceForm = useForm({
    app_name:      s.app_name      ?? 'Staff Portal',
    primary_color: s.primary_color ?? '#EF233C',
    sidebar_color: s.sidebar_color ?? '#2B2D42',
});

const prefsForm = useForm({
    email_notifications:   props.preferences.email_notifications   ?? true,
    calendar_default_view: props.preferences.calendar_default_view ?? 'month',
});

// ── Save helper ───────────────────────────────────────────────────────────────

function save(form, routeName) {
    form.post(route(routeName), { preserveScroll: true, preserveState: true });
}

// ── Timezone list ─────────────────────────────────────────────────────────────

const timezones = [
    { value: 'UTC',                  label: 'UTC' },
    { value: 'Europe/London',        label: 'London (GMT/BST)' },
    { value: 'Europe/Dublin',        label: 'Dublin (GMT/IST)' },
    { value: 'Europe/Paris',         label: 'Paris (CET/CEST)' },
    { value: 'Europe/Berlin',        label: 'Berlin (CET/CEST)' },
    { value: 'Europe/Amsterdam',     label: 'Amsterdam (CET/CEST)' },
    { value: 'America/New_York',     label: 'New York (EST/EDT)' },
    { value: 'America/Chicago',      label: 'Chicago (CST/CDT)' },
    { value: 'America/Denver',       label: 'Denver (MST/MDT)' },
    { value: 'America/Los_Angeles',  label: 'Los Angeles (PST/PDT)' },
    { value: 'America/Toronto',      label: 'Toronto (EST/EDT)' },
    { value: 'Africa/Lagos',         label: 'Lagos (WAT)' },
    { value: 'Africa/Nairobi',       label: 'Nairobi (EAT)' },
    { value: 'Asia/Dubai',           label: 'Dubai (GST)' },
    { value: 'Asia/Kolkata',         label: 'India (IST)' },
    { value: 'Asia/Dhaka',           label: 'Dhaka (BST)' },
    { value: 'Asia/Bangkok',         label: 'Bangkok (ICT)' },
    { value: 'Asia/Singapore',       label: 'Singapore (SGT)' },
    { value: 'Asia/Manila',          label: 'Manila (PHT)' },
    { value: 'Asia/Hong_Kong',       label: 'Hong Kong (HKT)' },
    { value: 'Asia/Shanghai',        label: 'Shanghai/Beijing (CST)' },
    { value: 'Asia/Tokyo',           label: 'Tokyo (JST)' },
    { value: 'Asia/Seoul',           label: 'Seoul (KST)' },
    { value: 'Australia/Perth',      label: 'Perth (AWST)' },
    { value: 'Australia/Sydney',     label: 'Sydney (AEST/AEDT)' },
    { value: 'Pacific/Auckland',     label: 'Auckland (NZST/NZDT)' },
];
</script>

<!-- ── Sub-components ─────────────────────────────────────────────────────────
     Defined as inline component options for simplicity — no extra files needed.
──────────────────────────────────────────────────────────────────────────────-->

<script>
import { defineComponent, h } from 'vue';

// FieldRow: label + hint on the left, control slot on the right
export const FieldRow = defineComponent({
    props: { label: String, hint: String },
    setup(props, { slots }) {
        return () => h('div', { class: 'grid grid-cols-1 sm:grid-cols-2 gap-3 items-start' }, [
            h('div', [
                h('p', { class: 'text-sm font-medium text-gray-700' }, props.label),
                props.hint && h('p', { class: 'text-xs text-gray-400 mt-0.5' }, props.hint),
            ]),
            h('div', { class: 'flex items-center' }, slots.default?.()),
        ]);
    },
});

// Toggle: styled checkbox
export const Toggle = defineComponent({
    props:  { modelValue: Boolean },
    emits:  ['update:modelValue'],
    setup(props, { emit }) {
        return () => h('button', {
            type: 'button',
            role: 'switch',
            'aria-checked': props.modelValue,
            onClick: () => emit('update:modelValue', !props.modelValue),
            class: [
                'relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-[#EF233C]/30',
                props.modelValue ? 'bg-[#EF233C]' : 'bg-gray-200',
            ],
        }, [
            h('span', {
                class: [
                    'inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform',
                    props.modelValue ? 'translate-x-6' : 'translate-x-1',
                ],
            }),
        ]);
    },
});

// SaveButton
export const SaveButton = defineComponent({
    props: { processing: Boolean },
    setup(props) {
        return () => h('button', {
            type: 'submit',
            disabled: props.processing,
            class: 'bg-[#EF233C] hover:bg-[#D90429] disabled:opacity-60 text-white text-sm font-semibold px-5 py-2 rounded-lg transition-colors flex items-center gap-2',
        }, [
            props.processing && h('span', { class: 'w-3.5 h-3.5 border-2 border-white/30 border-t-white rounded-full animate-spin' }),
            props.processing ? 'Saving…' : 'Save changes',
        ]);
    },
});
</script>

<style scoped>
.field-input {
    @apply w-full text-sm border border-gray-200 rounded-lg px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#EF233C]/20 focus:border-[#EF233C] transition-colors;
}
</style>
