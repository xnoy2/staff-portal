<template>
    <AppLayout title="User Guide">
        <div class="max-w-7xl mx-auto">

            <!-- ── Top progress bar ───────────────────────────────── -->
            <div class="bg-[#2B2D42] rounded-2xl px-5 py-4 mb-4 flex flex-wrap items-center gap-4">
                <div class="flex items-center gap-3 flex-1 min-w-0">
                    <div class="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center flex-shrink-0">
                        <BookOpenIcon class="w-5 h-5 text-white" />
                    </div>
                    <div>
                        <p class="text-white font-bold text-sm">User Guide</p>
                        <p class="text-white/50 text-xs mt-0.5 capitalize">{{ roleLabel }} guide · {{ currentIndex + 1 }} of {{ pages.length }}</p>
                    </div>
                </div>
                <!-- Progress bar -->
                <div class="flex-1 min-w-[120px] max-w-xs">
                    <div class="h-1.5 bg-white/10 rounded-full overflow-hidden">
                        <div
                            class="h-full bg-[#EF233C] rounded-full transition-all duration-500"
                            :style="{ width: `${((currentIndex + 1) / pages.length) * 100}%` }"
                        />
                    </div>
                </div>
                <!-- Role badge -->
                <span :class="['text-xs font-bold px-3 py-1.5 rounded-full uppercase tracking-wide', roleBadgeClass]">
                    {{ roleLabel }}
                </span>
            </div>

            <div class="flex gap-4 items-start">

                <!-- ── TOC Sidebar ────────────────────────────────────── -->
                <aside class="hidden lg:flex flex-col w-64 flex-shrink-0 bg-white rounded-2xl border border-gray-200 overflow-hidden sticky top-20">
                    <div class="px-4 py-3 border-b border-gray-100 bg-gray-50">
                        <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Contents</p>
                    </div>
                    <nav class="overflow-y-auto max-h-[calc(100vh-220px)] py-2">
                        <button
                            v-for="(pg, i) in pages"
                            :key="i"
                            @click="goTo(i)"
                            :class="[
                                'w-full flex items-center gap-2.5 px-3 py-2 text-left transition-colors group',
                                i === currentIndex
                                    ? 'bg-[#EF233C]/5 text-[#EF233C]'
                                    : 'text-gray-500 hover:bg-gray-50 hover:text-gray-700',
                            ]"
                        >
                            <span :class="[
                                'w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold flex-shrink-0 transition-colors',
                                i === currentIndex
                                    ? 'bg-[#EF233C] text-white'
                                    : i < currentIndex
                                        ? 'bg-green-100 text-green-600'
                                        : 'bg-gray-100 text-gray-400',
                            ]">
                                <CheckIcon v-if="i < currentIndex" class="w-3 h-3" />
                                <span v-else>{{ i + 1 }}</span>
                            </span>
                            <span class="text-xs font-medium truncate">{{ pg.title }}</span>
                        </button>
                    </nav>
                </aside>

                <!-- ── Main content ───────────────────────────────────── -->
                <div class="flex-1 min-w-0 space-y-4">

                    <!-- Mobile TOC dropdown -->
                    <div class="lg:hidden">
                        <button
                            @click="tocOpen = !tocOpen"
                            class="w-full flex items-center justify-between bg-white rounded-xl border border-gray-200 px-4 py-3 text-sm font-semibold text-gray-700"
                        >
                            <span>{{ currentIndex + 1 }}. {{ currentPage.title }}</span>
                            <ChevronDownIcon class="w-4 h-4 text-gray-400 transition-transform" :class="tocOpen ? 'rotate-180' : ''" />
                        </button>
                        <Transition name="slide-down">
                            <div v-if="tocOpen" class="mt-1 bg-white rounded-xl border border-gray-200 overflow-hidden divide-y divide-gray-100">
                                <button
                                    v-for="(pg, i) in pages"
                                    :key="i"
                                    @click="goTo(i); tocOpen = false"
                                    :class="[
                                        'w-full flex items-center gap-2.5 px-4 py-2.5 text-left text-xs transition-colors',
                                        i === currentIndex ? 'bg-[#EF233C]/5 text-[#EF233C] font-semibold' : 'text-gray-600 hover:bg-gray-50',
                                    ]"
                                >
                                    <span :class="['w-4 h-4 rounded-full flex items-center justify-center text-[9px] font-bold flex-shrink-0', i < currentIndex ? 'bg-green-100 text-green-600' : 'bg-gray-100 text-gray-400']">
                                        <CheckIcon v-if="i < currentIndex" class="w-2.5 h-2.5" />
                                        <span v-else>{{ i + 1 }}</span>
                                    </span>
                                    {{ pg.title }}
                                </button>
                            </div>
                        </Transition>
                    </div>

                    <!-- Page card -->
                    <Transition :name="direction === 'forward' ? 'slide-left' : 'slide-right'" mode="out-in">
                        <div :key="currentIndex" class="bg-white rounded-2xl border border-gray-200 overflow-hidden">

                            <!-- Page header -->
                            <div :class="['px-6 py-5 border-b border-gray-100', currentPage.headerBg ?? 'bg-gradient-to-r from-gray-50 to-white']">
                                <div class="flex items-start gap-4">
                                    <div :class="['w-12 h-12 rounded-2xl flex items-center justify-center flex-shrink-0', currentPage.iconBg ?? 'bg-gray-100']">
                                        <component :is="currentPage.icon" :class="['w-6 h-6', currentPage.iconColor ?? 'text-gray-500']" />
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <h2 class="text-lg font-bold text-gray-900">{{ currentPage.title }}</h2>
                                            <span v-if="currentPage.badge" :class="['text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wide', currentPage.badge.class]">
                                                {{ currentPage.badge.text }}
                                            </span>
                                        </div>
                                        <p class="text-sm text-gray-500 mt-0.5">{{ currentPage.subtitle }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Page body -->
                            <div class="px-6 py-6 space-y-6">

                                <!-- Description -->
                                <p class="text-sm text-gray-600 leading-relaxed">{{ currentPage.description }}</p>

                                <!-- Sections -->
                                <div v-for="(section, si) in currentPage.sections" :key="si" class="space-y-3">
                                    <h3 v-if="section.heading" class="text-xs font-bold text-gray-400 uppercase tracking-wider">{{ section.heading }}</h3>

                                    <!-- Feature list -->
                                    <div v-if="section.items" class="space-y-2">
                                        <div
                                            v-for="(item, ii) in section.items"
                                            :key="ii"
                                            class="flex items-start gap-3 p-3 rounded-xl bg-gray-50 hover:bg-gray-100 transition-colors"
                                        >
                                            <div :class="['w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0', item.iconBg ?? 'bg-white border border-gray-200']">
                                                <component :is="item.icon" :class="['w-4 h-4', item.iconColor ?? 'text-gray-500']" />
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-semibold text-gray-800">{{ item.title }}</p>
                                                <p class="text-xs text-gray-500 mt-0.5 leading-relaxed">{{ item.desc }}</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Steps list -->
                                    <div v-if="section.steps" class="space-y-2">
                                        <div
                                            v-for="(step, si2) in section.steps"
                                            :key="si2"
                                            class="flex items-start gap-3"
                                        >
                                            <span class="w-6 h-6 rounded-full bg-[#EF233C] text-white text-xs font-bold flex items-center justify-center flex-shrink-0 mt-0.5">{{ si2 + 1 }}</span>
                                            <p class="text-sm text-gray-600 leading-relaxed">{{ step }}</p>
                                        </div>
                                    </div>

                                    <!-- Tip box -->
                                    <div v-if="section.tip" class="flex items-start gap-3 bg-amber-50 border border-amber-200 rounded-xl p-3.5">
                                        <LightBulbIcon class="w-4 h-4 text-amber-500 flex-shrink-0 mt-0.5" />
                                        <p class="text-xs text-amber-800 leading-relaxed">{{ section.tip }}</p>
                                    </div>

                                    <!-- Info box -->
                                    <div v-if="section.info" class="flex items-start gap-3 bg-blue-50 border border-blue-200 rounded-xl p-3.5">
                                        <InformationCircleIcon class="w-4 h-4 text-blue-500 flex-shrink-0 mt-0.5" />
                                        <p class="text-xs text-blue-800 leading-relaxed">{{ section.info }}</p>
                                    </div>

                                    <!-- Warning box -->
                                    <div v-if="section.warning" class="flex items-start gap-3 bg-red-50 border border-red-200 rounded-xl p-3.5">
                                        <ExclamationTriangleIcon class="w-4 h-4 text-red-500 flex-shrink-0 mt-0.5" />
                                        <p class="text-xs text-red-800 leading-relaxed">{{ section.warning }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </Transition>

                    <!-- ── Prev / Next ─────────────────────────────────── -->
                    <div class="flex items-center gap-3">
                        <button
                            @click="prev"
                            :disabled="currentIndex === 0"
                            class="flex items-center gap-2 px-4 py-2.5 rounded-xl border border-gray-200 text-sm font-semibold text-gray-600 hover:bg-gray-50 hover:border-gray-300 transition-colors disabled:opacity-30 disabled:cursor-not-allowed"
                        >
                            <ChevronLeftIcon class="w-4 h-4" /> Previous
                        </button>

                        <!-- Dot indicators -->
                        <div class="flex-1 flex items-center justify-center gap-1.5">
                            <button
                                v-for="(_, i) in pages"
                                :key="i"
                                @click="goTo(i)"
                                :class="[
                                    'rounded-full transition-all duration-300',
                                    i === currentIndex ? 'w-5 h-2 bg-[#EF233C]' : 'w-2 h-2 bg-gray-200 hover:bg-gray-300',
                                ]"
                            />
                        </div>

                        <button
                            @click="next"
                            :disabled="currentIndex === pages.length - 1"
                            class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[#EF233C] hover:bg-[#D90429] text-white text-sm font-semibold transition-colors disabled:opacity-30 disabled:cursor-not-allowed"
                        >
                            Next <ChevronRightIcon class="w-4 h-4" />
                        </button>
                    </div>

                    <!-- Completion card -->
                    <Transition name="fade">
                        <div v-if="currentIndex === pages.length - 1" class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-2xl p-5 flex items-center gap-4">
                            <div class="w-12 h-12 rounded-2xl bg-green-100 flex items-center justify-center flex-shrink-0">
                                <CheckBadgeIcon class="w-6 h-6 text-green-600" />
                            </div>
                            <div>
                                <p class="text-sm font-bold text-green-800">You've reached the end of the guide!</p>
                                <p class="text-xs text-green-600 mt-0.5">You're all set. Head to the dashboard to get started.</p>
                            </div>
                            <Link :href="route('dashboard')" class="ml-auto flex items-center gap-1.5 bg-green-600 hover:bg-green-700 text-white text-xs font-bold px-4 py-2 rounded-xl transition-colors whitespace-nowrap">
                                Go to Dashboard <ArrowRightIcon class="w-3.5 h-3.5" />
                            </Link>
                        </div>
                    </Transition>

                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { usePermission } from '@/Composables/usePermission';
import {
    BookOpenIcon, CheckIcon, CheckBadgeIcon,
    ChevronLeftIcon, ChevronRightIcon, ChevronDownIcon,
    LightBulbIcon, InformationCircleIcon, ExclamationTriangleIcon, ArrowRightIcon,
    HomeIcon, ClockIcon, BriefcaseIcon, CalendarDaysIcon, DocumentTextIcon,
    QrCodeIcon, UserCircleIcon, UsersIcon, ClipboardDocumentListIcon,
    FolderIcon, TruckIcon, BanknotesIcon, ChartBarIcon,
    ClipboardDocumentIcon, BuildingOfficeIcon, CameraIcon, ListBulletIcon,
    ArrowRightOnRectangleIcon, ArrowLeftOnRectangleIcon, PauseCircleIcon,
    PlayCircleIcon, ShieldCheckIcon, BellIcon, AcademicCapIcon,
    WrenchScrewdriverIcon, Squares2X2Icon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    userRole: { type: String, default: 'staff' },
});

const { isAdmin, isManager, isHR, isSiteHead, isStaff } = usePermission();

const currentIndex = ref(0);
const direction    = ref('forward');
const tocOpen      = ref(false);

// ── Role helpers ──────────────────────────────────────────────────────

const roleLabel = computed(() => {
    if (isAdmin.value)    return 'Administrator';
    if (isManager.value)  return 'Manager';
    if (isHR.value)       return 'HR';
    if (isSiteHead.value) return 'Site Head';
    return 'Staff';
});

const roleBadgeClass = computed(() => {
    if (isAdmin.value)    return 'bg-[#EF233C] text-white';
    if (isManager.value)  return 'bg-purple-600 text-white';
    if (isHR.value)       return 'bg-blue-600 text-white';
    if (isSiteHead.value) return 'bg-amber-500 text-white';
    return 'bg-gray-600 text-white';
});

// ── Page definitions ──────────────────────────────────────────────────

// Shared pages (all roles)
const sharedPages = [
    {
        title: 'Welcome to the Staff Portal',
        subtitle: 'Your one-stop hub for work management',
        icon: BookOpenIcon,
        iconBg: 'bg-[#EF233C]/10',
        iconColor: 'text-[#EF233C]',
        headerBg: 'bg-gradient-to-r from-[#EF233C]/5 to-white',
        description: 'The BCF Staff Portal helps you manage your daily work — clocking in and out, viewing your jobs, requesting leave, and keeping track of your hours and pay. This guide walks you through everything available to your account.',
        sections: [
            {
                heading: 'What you can do in this portal',
                items: [
                    { icon: ClockIcon,           iconBg: 'bg-blue-50',   iconColor: 'text-blue-500',   title: 'Track your time',       desc: 'Clock in and out, take breaks, and view your attendance history.' },
                    { icon: ListBulletIcon,      iconBg: 'bg-amber-50',  iconColor: 'text-amber-500',  title: 'See your jobs',         desc: 'View the jobs you\'re assigned to, check dates and times, and update statuses.' },
                    { icon: CalendarDaysIcon,    iconBg: 'bg-green-50',  iconColor: 'text-green-500',  title: 'Manage leave',          desc: 'Request annual leave, sick days, or other time off and track your balance.' },
                    { icon: DocumentTextIcon,    iconBg: 'bg-purple-50', iconColor: 'text-purple-500', title: 'View your payslip',     desc: 'See a full breakdown of your hours and pay for each pay period.' },
                ],
            },
            {
                tip: 'Use the navigation menu on the left to jump between sections. This guide is personalised to your role — only the features available to you are shown.',
            },
        ],
    },
    {
        title: 'Dashboard',
        subtitle: 'Your daily overview at a glance',
        icon: HomeIcon,
        iconBg: 'bg-blue-50',
        iconColor: 'text-blue-500',
        headerBg: 'bg-gradient-to-r from-blue-50 to-white',
        description: 'The Dashboard is the first page you see after logging in. It gives you a quick summary of what\'s happening today — your clock-in status, upcoming jobs, leave balance, and recent entries.',
        sections: [
            {
                heading: 'What\'s on your dashboard',
                items: [
                    { icon: ClockIcon,        iconBg: 'bg-blue-50',   iconColor: 'text-blue-500',   title: 'Clock-in widget',     desc: 'Shows whether you\'re currently clocked in, with a live timer and quick actions to clock in/out or take a break.' },
                    { icon: ChartBarIcon,     iconBg: 'bg-purple-50', iconColor: 'text-purple-500', title: 'Weekly hours chart',  desc: 'A bar chart showing your approved hours for each day of the current week (Mon–Sun).' },
                    { icon: ListBulletIcon,   iconBg: 'bg-amber-50',  iconColor: 'text-amber-500',  title: 'Upcoming jobs',       desc: 'A list of your next 5 scheduled or in-progress jobs, with date, time, and project info.' },
                    { icon: CalendarDaysIcon, iconBg: 'bg-green-50',  iconColor: 'text-green-500',  title: 'Leave balance',       desc: 'Shows your annual leave entitlement, how much you\'ve used, and how many days remain.' },
                ],
            },
            {
                tip: 'The dashboard refreshes each time you load the page. Bookmark it as your starting point.',
            },
        ],
    },
    {
        title: 'Clocking In & Out',
        subtitle: 'Recording your work time accurately',
        icon: ArrowRightOnRectangleIcon,
        iconBg: 'bg-green-50',
        iconColor: 'text-green-600',
        headerBg: 'bg-gradient-to-r from-green-50 to-white',
        description: 'The attendance system records when you start and finish work each day. You can clock in from the Dashboard or from the Attendance page. Your manager reviews and approves your time entries.',
        sections: [
            {
                heading: 'How to clock in',
                steps: [
                    'Go to the Dashboard or the Attendance page from the left menu.',
                    'Press the "Clock In" button. If you have an approved overtime request for today, you\'ll see an OT or RDOT option.',
                    'A green timer starts showing how long you\'ve been clocked in.',
                    'When you\'re done, press "Clock Out". Your total hours are calculated automatically.',
                ],
            },
            {
                heading: 'Clock-in status indicators',
                items: [
                    { icon: PlayCircleIcon,  iconBg: 'bg-green-50',  iconColor: 'text-green-500',  title: 'Working',    desc: 'You\'re actively clocked in and your timer is running.' },
                    { icon: PauseCircleIcon, iconBg: 'bg-amber-50',  iconColor: 'text-amber-500',  title: 'On Break',   desc: 'You\'ve taken a break — timer pauses for break duration.' },
                    { icon: PauseCircleIcon, iconBg: 'bg-orange-50', iconColor: 'text-orange-500', title: 'On Lunch',   desc: 'You\'ve taken a lunch break.' },
                ],
            },
            {
                warning: 'You can only have one active clock-in at a time. If you forget to clock out, contact your manager to add a manual entry.',
            },
        ],
    },
    {
        title: 'Breaks',
        subtitle: 'Taking a break or lunch during your shift',
        icon: PauseCircleIcon,
        iconBg: 'bg-amber-50',
        iconColor: 'text-amber-500',
        headerBg: 'bg-gradient-to-r from-amber-50 to-white',
        description: 'While clocked in, you can record breaks. Break time is tracked separately and deducted from your total working hours. There are two types: a standard break and a lunch break.',
        sections: [
            {
                heading: 'How to take a break',
                steps: [
                    'While clocked in, press "Take Break" or "Lunch Break" on the Dashboard or Attendance page.',
                    'Your status changes to "On Break" or "On Lunch" and the working timer pauses.',
                    'When you return, press "End Break". The break duration is recorded.',
                    'Your total break minutes are shown on your time entry.',
                ],
            },
            {
                tip: 'Break durations are automatically deducted from your total hours when payroll is calculated.',
            },
        ],
    },
    {
        title: 'My Jobs',
        subtitle: 'Viewing and managing your assigned jobs',
        icon: ListBulletIcon,
        iconBg: 'bg-amber-50',
        iconColor: 'text-amber-600',
        headerBg: 'bg-gradient-to-r from-amber-50 to-white',
        description: 'The My Jobs page shows all the jobs you\'ve been assigned to. You can see upcoming work, jobs in progress, and past completed jobs. You can also update a job\'s status when you start or finish it.',
        sections: [
            {
                heading: 'Navigating My Jobs',
                items: [
                    { icon: CalendarDaysIcon, iconBg: 'bg-blue-50',   iconColor: 'text-blue-500',   title: 'Upcoming',  desc: 'Default view — shows jobs from today onwards, sorted by soonest first.' },
                    { icon: ClockIcon,        iconBg: 'bg-gray-100',  iconColor: 'text-gray-500',   title: 'Past',      desc: 'Shows completed or cancelled jobs from previous dates.' },
                    { icon: ListBulletIcon,   iconBg: 'bg-purple-50', iconColor: 'text-purple-500', title: 'All',       desc: 'Shows every job regardless of date.' },
                ],
            },
            {
                heading: 'Updating a job status',
                steps: [
                    'Find the job in the list — it\'ll show a status badge (Scheduled, In Progress, Completed).',
                    'If a job is Scheduled, press "▶ Start" to mark it as In Progress.',
                    'Once the work is done, press "✓ Complete" to mark it as Completed.',
                ],
            },
            {
                tip: 'Jobs are grouped by date under a date heading. Today\'s jobs are highlighted with a "Today" badge.',
            },
        ],
    },
    {
        title: 'Attendance History',
        subtitle: 'Reviewing your time entries',
        icon: ClockIcon,
        iconBg: 'bg-blue-50',
        iconColor: 'text-blue-500',
        headerBg: 'bg-gradient-to-r from-blue-50 to-white',
        description: 'The Attendance page shows a full history of your clock-in records — including clock-in/out times, total hours, break duration, and approval status. You can filter by date range or status.',
        sections: [
            {
                heading: 'Entry statuses',
                items: [
                    { icon: ClockIcon,        iconBg: 'bg-amber-50',  iconColor: 'text-amber-500',  title: 'Pending',   desc: 'Your entry has been recorded and is awaiting manager review.' },
                    { icon: CheckIcon,        iconBg: 'bg-green-50',  iconColor: 'text-green-500',  title: 'Approved',  desc: 'Your manager has approved this entry. It will be included in payroll.' },
                    { icon: ExclamationTriangleIcon, iconBg: 'bg-red-50', iconColor: 'text-red-500', title: 'Rejected',  desc: 'This entry was rejected. Contact your manager for details.' },
                ],
            },
            {
                info: 'Admin and manager clock-ins are automatically approved. Standard staff entries go to the manager for review.',
            },
        ],
    },
    {
        title: 'Leave Requests',
        subtitle: 'Requesting time off work',
        icon: CalendarDaysIcon,
        iconBg: 'bg-green-50',
        iconColor: 'text-green-600',
        headerBg: 'bg-gradient-to-r from-green-50 to-white',
        description: 'Use the Leave section to request time off. You can apply for annual leave, sick days, and other types of absence. Your manager will review and approve or decline your request.',
        sections: [
            {
                heading: 'How to submit a leave request',
                steps: [
                    'Go to "Leave" in the left navigation.',
                    'Click "New Request" and choose the leave type (Annual, Sick, Other, etc.).',
                    'Pick your start and end dates. The number of working days is calculated automatically.',
                    'Add an optional note, then submit. Your manager will be notified.',
                ],
            },
            {
                heading: 'Leave types',
                items: [
                    { icon: CalendarDaysIcon, iconBg: 'bg-green-50',  iconColor: 'text-green-500',  title: 'Annual Leave',  desc: 'Paid holiday. Deducted from your annual entitlement (default: 28 days).' },
                    { icon: ClockIcon,        iconBg: 'bg-red-50',    iconColor: 'text-red-500',    title: 'Sick Leave',    desc: 'Absence due to illness. Does not deduct from annual leave.' },
                    { icon: BriefcaseIcon,    iconBg: 'bg-purple-50', iconColor: 'text-purple-500', title: 'Other',         desc: 'Any other approved absence (e.g. emergency, bereavement).' },
                ],
            },
            {
                tip: 'Check your leave balance on the Dashboard before submitting a request to make sure you have enough days remaining.',
            },
        ],
    },
    {
        title: 'Overtime Requests',
        subtitle: 'Recording extra hours worked',
        icon: BriefcaseIcon,
        iconBg: 'bg-purple-50',
        iconColor: 'text-purple-500',
        headerBg: 'bg-gradient-to-r from-purple-50 to-white',
        description: 'If you\'ve been asked to work overtime, you can submit an overtime request for manager approval. There are two types: standard Overtime (OT) and Rest Day Overtime (RDOT).',
        sections: [
            {
                heading: 'OT types',
                items: [
                    { icon: BriefcaseIcon, iconBg: 'bg-purple-50', iconColor: 'text-purple-500', title: 'OT (Overtime)',       desc: 'Standard overtime on a normal working day — hours beyond your regular shift.' },
                    { icon: BriefcaseIcon, iconBg: 'bg-amber-50',  iconColor: 'text-amber-500',  title: 'RDOT (Rest Day OT)', desc: 'Working on a scheduled rest day or public holiday.' },
                ],
            },
            {
                heading: 'How to submit',
                steps: [
                    'Go to "Overtime" in the left menu.',
                    'Click "New Request", select the date and OT type.',
                    'Add a note explaining the reason (optional but recommended).',
                    'Submit. Once approved, you\'ll see the OT toggle on the Dashboard for that date.',
                ],
            },
            {
                tip: 'Once approved, when you clock in on that date you\'ll be able to select OT or RDOT as your clock-in type.',
            },
        ],
    },
    {
        title: 'My Payslip',
        subtitle: 'Viewing your pay breakdown',
        icon: DocumentTextIcon,
        iconBg: 'bg-purple-50',
        iconColor: 'text-purple-500',
        headerBg: 'bg-gradient-to-r from-purple-50 to-white',
        description: 'My Payslip shows a detailed breakdown of your pay for each payroll period — regular hours, overtime hours, gross pay, any deductions, and net pay. Once a payslip is approved by your manager, it\'s locked and can\'t be changed.',
        sections: [
            {
                heading: 'Reading your payslip',
                items: [
                    { icon: ClockIcon,        iconBg: 'bg-blue-50',   iconColor: 'text-blue-500',   title: 'Regular Hours',  desc: 'Hours worked within your normal working day (up to 8h per day, unless OT-type shift).' },
                    { icon: BriefcaseIcon,    iconBg: 'bg-purple-50', iconColor: 'text-purple-500', title: 'Overtime Hours', desc: 'Hours beyond 8h on a normal day, or all hours on an OT/RDOT shift.' },
                    { icon: BanknotesIcon,    iconBg: 'bg-green-50',  iconColor: 'text-green-500',  title: 'Gross Pay',      desc: 'Total earnings before deductions (regular + overtime at your hourly rate).' },
                    { icon: DocumentTextIcon, iconBg: 'bg-red-50',    iconColor: 'text-red-500',    title: 'Deductions',     desc: 'Any amounts deducted (e.g. advances, fines). Shown as a list with descriptions.' },
                    { icon: BanknotesIcon,    iconBg: 'bg-emerald-50',iconColor: 'text-emerald-500',title: 'Net Pay',        desc: 'Your take-home amount after all deductions.' },
                ],
            },
            {
                info: 'Payslips are generated by your manager for a defined period. You\'ll see all periods in the list — click any to view the detail.',
            },
        ],
    },
    {
        title: 'My QR Code',
        subtitle: 'Contactless clock-in with your personal QR',
        icon: QrCodeIcon,
        iconBg: 'bg-gray-100',
        iconColor: 'text-gray-600',
        headerBg: 'bg-gradient-to-r from-gray-50 to-white',
        description: 'Each staff member has a unique QR code that can be scanned by a site manager to clock you in or out remotely. This is useful when you don\'t have access to a device at the job site.',
        sections: [
            {
                heading: 'How to use your QR code',
                steps: [
                    'Go to "My QR Code" from the navigation menu.',
                    'Your QR code is displayed on screen. You can also download it as an image.',
                    'Ask your site manager to scan it using the QR Scanner.',
                    'You\'ll receive a notification confirming your clock-in or clock-out.',
                ],
            },
            {
                tip: 'Save a screenshot of your QR code to your phone\'s photos so you always have it handy, even without internet.',
            },
        ],
    },
    {
        title: 'Profile & Account Settings',
        subtitle: 'Managing your personal information',
        icon: UserCircleIcon,
        iconBg: 'bg-gray-100',
        iconColor: 'text-gray-600',
        headerBg: 'bg-gradient-to-r from-gray-50 to-white',
        description: 'You can update your personal details, change your password, and manage your profile photo from the Profile section. Click your name or avatar in the top-right corner to access it.',
        sections: [
            {
                heading: 'What you can update',
                items: [
                    { icon: UserCircleIcon,   iconBg: 'bg-gray-100',  iconColor: 'text-gray-500',   title: 'Profile Photo',    desc: 'Upload a photo by clicking your avatar. Supported: JPG, PNG up to 2MB.' },
                    { icon: ShieldCheckIcon,  iconBg: 'bg-blue-50',   iconColor: 'text-blue-500',   title: 'Password',         desc: 'Change your password from the Security tab. Use a strong, unique password.' },
                    { icon: BellIcon,         iconBg: 'bg-amber-50',  iconColor: 'text-amber-500',  title: 'Notifications',    desc: 'You\'ll receive notifications for job assignments, leave decisions, and OT approvals.' },
                ],
            },
            {
                warning: 'If you\'ve been given a temporary password, you must change it before you can access the rest of the portal.',
            },
        ],
    },
    {
        title: 'Training & Certificates',
        subtitle: 'Accessing your training materials',
        icon: AcademicCapIcon,
        iconBg: 'bg-blue-50',
        iconColor: 'text-blue-500',
        headerBg: 'bg-gradient-to-r from-blue-50 to-white',
        description: 'The Training section stores your training records, certifications, and any materials your company has shared. Keep your certificates up to date to ensure compliance.',
        sections: [
            {
                items: [
                    { icon: AcademicCapIcon,  iconBg: 'bg-blue-50',   iconColor: 'text-blue-500',   title: 'Training records',    desc: 'View training modules you\'ve completed and any upcoming requirements.' },
                    { icon: DocumentTextIcon, iconBg: 'bg-green-50',  iconColor: 'text-green-500',  title: 'Certificates',        desc: 'Upload and manage your professional certifications and expiry dates.' },
                ],
            },
        ],
    },
];

// Site-head specific pages
const siteHeadPages = [
    {
        title: 'QR Scanner',
        subtitle: 'Scan staff QR codes on-site',
        icon: CameraIcon,
        iconBg: 'bg-amber-50',
        iconColor: 'text-amber-600',
        headerBg: 'bg-gradient-to-r from-amber-50 to-white',
        badge: { text: 'Site Head', class: 'bg-amber-100 text-amber-700' },
        description: 'As a site head, you can scan staff QR codes to clock them in or out directly. This is useful when staff arrive on site without a device. The QR Scanner is available from the Field section in the navigation.',
        sections: [
            {
                heading: 'How to scan',
                steps: [
                    'Go to "QR Scanner" in the Field section of the left menu.',
                    'Allow camera access when prompted.',
                    'Point the camera at a staff member\'s QR code.',
                    'The system will confirm the clock-in or clock-out with the staff member\'s name.',
                ],
            },
            {
                tip: 'Make sure the QR code is well-lit and held steady. The scan usually happens within 1–2 seconds.',
            },
        ],
    },
    {
        title: 'Site Jobs',
        subtitle: 'Managing jobs on your assigned projects',
        icon: ClipboardDocumentListIcon,
        iconBg: 'bg-amber-50',
        iconColor: 'text-amber-600',
        headerBg: 'bg-gradient-to-r from-amber-50 to-white',
        badge: { text: 'Site Head', class: 'bg-amber-100 text-amber-700' },
        description: 'As a site head, the My Jobs / Site Jobs page shows all jobs linked to projects you\'ve been assigned to — not just jobs assigned directly to you. You can start, complete, or manage any job within your projects.',
        sections: [
            {
                items: [
                    { icon: PlayCircleIcon,  iconBg: 'bg-amber-50',  iconColor: 'text-amber-500',  title: 'Start a job',    desc: 'Mark a scheduled job as In Progress when work begins on site.' },
                    { icon: CheckIcon,       iconBg: 'bg-green-50',  iconColor: 'text-green-500',  title: 'Complete a job', desc: 'Mark a job as Completed when the work is done. This updates the project checklist.' },
                ],
            },
            {
                info: 'Jobs linked to BCF or BGR client platforms automatically update the corresponding stage when marked complete.',
            },
        ],
    },
];

// HR specific pages
const hrPages = [
    {
        title: 'Staff Management',
        subtitle: 'Viewing and managing staff records',
        icon: UsersIcon,
        iconBg: 'bg-blue-50',
        iconColor: 'text-blue-600',
        headerBg: 'bg-gradient-to-r from-blue-50 to-white',
        badge: { text: 'HR', class: 'bg-blue-100 text-blue-700' },
        description: 'HR has access to staff profiles, employment details, and account management. You can view contact information, employee IDs, hourly rates, and manage account statuses.',
        sections: [
            {
                items: [
                    { icon: UserCircleIcon,  iconBg: 'bg-gray-100',  iconColor: 'text-gray-500',   title: 'Staff profiles',    desc: 'View personal details, employment info, leave balances, and attendance history.' },
                    { icon: ShieldCheckIcon, iconBg: 'bg-red-50',    iconColor: 'text-red-500',    title: 'Password reset',    desc: 'Reset a staff member\'s password if they\'re locked out.' },
                    { icon: BanknotesIcon,   iconBg: 'bg-green-50',  iconColor: 'text-green-500',  title: 'Hourly rates',      desc: 'View and update staff hourly rates for payroll calculation.' },
                ],
            },
        ],
    },
    {
        title: 'Payroll (HR)',
        subtitle: 'Generating and approving payroll runs',
        icon: BanknotesIcon,
        iconBg: 'bg-green-50',
        iconColor: 'text-green-600',
        headerBg: 'bg-gradient-to-r from-green-50 to-white',
        badge: { text: 'HR', class: 'bg-blue-100 text-blue-700' },
        description: 'HR can generate payroll runs, review individual payslips, add deductions, and approve pay before it\'s finalised. Payroll pulls from approved attendance records.',
        sections: [
            {
                heading: 'Payroll workflow',
                steps: [
                    'Go to "Payroll" under Human Resources in the left menu.',
                    'Click "Generate Run" and select the period (from/to dates).',
                    'Review each staff member\'s payslip — check hours and gross pay.',
                    'Add any deductions (advances, fines) if needed.',
                    'Approve individual payslips or approve all at once.',
                ],
            },
            {
                warning: 'Once a payslip is approved it becomes locked and cannot be edited. Double-check all figures before approving.',
            },
        ],
    },
    {
        title: 'Attendance Management (HR)',
        subtitle: 'Reviewing and approving time entries',
        icon: ClockIcon,
        iconBg: 'bg-blue-50',
        iconColor: 'text-blue-500',
        headerBg: 'bg-gradient-to-r from-blue-50 to-white',
        badge: { text: 'HR', class: 'bg-blue-100 text-blue-700' },
        description: 'HR can view all staff attendance records, approve or reject pending time entries, add manual entries for staff, and export attendance data as a CSV file.',
        sections: [
            {
                items: [
                    { icon: CheckIcon,        iconBg: 'bg-green-50',  iconColor: 'text-green-500',  title: 'Approve entries',   desc: 'Approve pending clock-in records individually or in bulk.' },
                    { icon: ExclamationTriangleIcon, iconBg: 'bg-red-50', iconColor: 'text-red-500', title: 'Reject entries',   desc: 'Reject incorrect entries with an optional reason.' },
                    { icon: ClipboardDocumentIcon,   iconBg: 'bg-gray-100', iconColor: 'text-gray-500', title: 'Manual entry',  desc: 'Add time entries for staff who forgot to clock in or out.' },
                    { icon: DocumentTextIcon,        iconBg: 'bg-purple-50', iconColor: 'text-purple-500', title: 'Export CSV',  desc: 'Download attendance data for a date range as a spreadsheet.' },
                ],
            },
        ],
    },
];

// Admin/Manager specific pages
const adminPages = [
    {
        title: 'Live Board',
        subtitle: 'Day-by-day job scheduling and monitoring',
        icon: ClipboardDocumentListIcon,
        iconBg: 'bg-[#EF233C]/10',
        iconColor: 'text-[#EF233C]',
        headerBg: 'bg-gradient-to-r from-[#EF233C]/5 to-white',
        badge: { text: 'Admin / Manager', class: 'bg-[#EF233C]/10 text-[#EF233C]' },
        description: 'The Live Board is your command centre for daily operations. Navigate between dates to see all jobs scheduled, their statuses, assigned crew, and clock-in indicators. You can create, edit, and manage jobs directly from here.',
        sections: [
            {
                heading: 'Key features',
                items: [
                    { icon: CalendarDaysIcon, iconBg: 'bg-blue-50',   iconColor: 'text-blue-500',   title: 'Date navigation',   desc: 'Use the arrows or date picker to jump between days. Click "Today" to return to today.' },
                    { icon: UsersIcon,        iconBg: 'bg-green-50',  iconColor: 'text-green-500',  title: 'Crew status',       desc: 'Each job card shows stacked avatars with green/grey dots indicating who\'s clocked in.' },
                    { icon: WrenchScrewdriverIcon, iconBg: 'bg-amber-50', iconColor: 'text-amber-500', title: 'Job actions',    desc: 'Edit, start, complete, cancel, or delete jobs directly from the card.' },
                    { icon: BuildingOfficeIcon,    iconBg: 'bg-purple-50', iconColor: 'text-purple-500', title: 'BCF / BGR links', desc: 'Link jobs to external client orders/stages. Completing a job auto-updates the external system.' },
                ],
            },
            {
                tip: 'Summary stats at the top show total jobs, in-progress count, staff deployed, and clocked-in count for the selected date.',
            },
        ],
    },
    {
        title: 'All Jobs (List View)',
        subtitle: 'Paginated list across all dates',
        icon: ListBulletIcon,
        iconBg: 'bg-amber-50',
        iconColor: 'text-amber-600',
        headerBg: 'bg-gradient-to-r from-amber-50 to-white',
        badge: { text: 'Admin / Manager', class: 'bg-[#EF233C]/10 text-[#EF233C]' },
        description: 'The All Jobs list shows every job across all dates in a scrollable, paginated list. Unlike the Live Board (single day), this gives you a broad view — useful for spotting upcoming work, reviewing past jobs, or checking status across weeks.',
        sections: [
            {
                items: [
                    { icon: CalendarDaysIcon, iconBg: 'bg-blue-50',   iconColor: 'text-blue-500',   title: 'Period filter',     desc: 'Toggle between Upcoming, Past, and All jobs at the top.' },
                    { icon: ClipboardDocumentListIcon, iconBg: 'bg-gray-100', iconColor: 'text-gray-500', title: 'Date grouping', desc: 'Jobs are grouped under date headings with Today/Tomorrow badges for easy scanning.' },
                    { icon: CalendarDaysIcon, iconBg: 'bg-[#EF233C]/10', iconColor: 'text-[#EF233C]', title: 'Live Board link',  desc: 'Each job row has a calendar icon to jump directly to that day on the Live Board.' },
                ],
            },
        ],
    },
    {
        title: 'Staff Management',
        subtitle: 'Full control over staff accounts',
        icon: UsersIcon,
        iconBg: 'bg-blue-50',
        iconColor: 'text-blue-600',
        headerBg: 'bg-gradient-to-r from-blue-50 to-white',
        badge: { text: 'Admin / Manager', class: 'bg-[#EF233C]/10 text-[#EF233C]' },
        description: 'The Staff section lets you create and manage staff accounts, assign roles, set hourly rates, and control account access. Each staff profile shows employment info, attendance history, leave balance, and jobs.',
        sections: [
            {
                heading: 'Staff actions',
                items: [
                    { icon: UserCircleIcon,  iconBg: 'bg-gray-100',  iconColor: 'text-gray-500',   title: 'Create staff',      desc: 'Add a new staff member with their name, email, role, and hourly rate.' },
                    { icon: ShieldCheckIcon, iconBg: 'bg-amber-50',  iconColor: 'text-amber-500',  title: 'Assign roles',      desc: 'Roles: Admin, Manager, HR, Site Head, Staff. Each role has different access levels.' },
                    { icon: ShieldCheckIcon, iconBg: 'bg-red-50',    iconColor: 'text-red-500',    title: 'Force password reset', desc: 'Reset a staff member\'s password. A temporary password is shown — share it securely.' },
                    { icon: BanknotesIcon,   iconBg: 'bg-green-50',  iconColor: 'text-green-500',  title: 'Hourly rates',      desc: 'Set regular and overtime hourly rates used in payroll calculation.' },
                ],
            },
            {
                info: 'Deactivating a staff account prevents them from logging in but preserves all their historical data.',
            },
        ],
    },
    {
        title: 'Projects',
        subtitle: 'Managing client project lifecycles',
        icon: FolderIcon,
        iconBg: 'bg-green-50',
        iconColor: 'text-green-600',
        headerBg: 'bg-gradient-to-r from-green-50 to-white',
        badge: { text: 'Admin / Manager', class: 'bg-[#EF233C]/10 text-[#EF233C]' },
        description: 'Projects represent ongoing client work. Each project has a status (Planning, Active, On Hold, Completed), assigned staff and site heads, and a checklist that automatically tracks linked jobs.',
        sections: [
            {
                items: [
                    { icon: Squares2X2Icon,  iconBg: 'bg-blue-50',   iconColor: 'text-blue-500',   title: 'Project board',     desc: 'Visual Kanban-style board grouping projects by status.' },
                    { icon: CheckIcon,       iconBg: 'bg-green-50',  iconColor: 'text-green-500',  title: 'Checklist',         desc: 'Each project has a checklist. Jobs linked to a project automatically add checklist items.' },
                    { icon: UsersIcon,       iconBg: 'bg-purple-50', iconColor: 'text-purple-500', title: 'Site heads',        desc: 'Assign site heads to a project — they\'ll see all jobs for that project.' },
                ],
            },
            {
                tip: 'When you mark a job as Completed, its linked checklist item is automatically ticked. This keeps the project progress accurate.',
            },
        ],
    },
    {
        title: 'Van Management',
        subtitle: 'Fleet tracking and allocation',
        icon: TruckIcon,
        iconBg: 'bg-gray-100',
        iconColor: 'text-gray-600',
        headerBg: 'bg-gradient-to-r from-gray-50 to-white',
        badge: { text: 'Admin / Manager', class: 'bg-[#EF233C]/10 text-[#EF233C]' },
        description: 'The Vans section tracks your company vehicles. Each van can be allocated to staff members and assigned to jobs. You can view which vans are in use, their MOT and service dates, and allocation history.',
        sections: [
            {
                items: [
                    { icon: TruckIcon,            iconBg: 'bg-gray-100',  iconColor: 'text-gray-500',   title: 'Fleet list',      desc: 'All vans with registration, make/model, and status.' },
                    { icon: UsersIcon,            iconBg: 'bg-blue-50',   iconColor: 'text-blue-500',   title: 'Allocations',     desc: 'Assign a van to a staff member for a set period. Shows current and past allocations.' },
                    { icon: CalendarDaysIcon,     iconBg: 'bg-amber-50',  iconColor: 'text-amber-500',  title: 'MOT & service',   desc: 'Track MOT expiry and service dates. Overdue vehicles are flagged.' },
                ],
            },
        ],
    },
    {
        title: 'Attendance Management',
        subtitle: 'Approving entries and manual clock-ins',
        icon: ClockIcon,
        iconBg: 'bg-blue-50',
        iconColor: 'text-blue-500',
        headerBg: 'bg-gradient-to-r from-blue-50 to-white',
        badge: { text: 'Admin / Manager', class: 'bg-[#EF233C]/10 text-[#EF233C]' },
        description: 'As a manager, you review and approve staff time entries. You can also add manual entries for staff who forgot to clock in, reject incorrect entries, and export data for payroll processing.',
        sections: [
            {
                heading: 'Key actions',
                items: [
                    { icon: CheckIcon,            iconBg: 'bg-green-50',  iconColor: 'text-green-500',  title: 'Approve / bulk approve', desc: 'Tick multiple entries and approve them all at once using the bulk approve button.' },
                    { icon: ExclamationTriangleIcon, iconBg: 'bg-red-50', iconColor: 'text-red-500',    title: 'Reject with reason',     desc: 'Reject an entry and add a note explaining why.' },
                    { icon: ClipboardDocumentIcon, iconBg: 'bg-gray-100', iconColor: 'text-gray-500',   title: 'Manual entry',           desc: 'Add clock-in/out records for one or multiple staff members on a given date.' },
                    { icon: DocumentTextIcon,     iconBg: 'bg-purple-50', iconColor: 'text-purple-500', title: 'Export CSV',             desc: 'Download filtered attendance data as a spreadsheet for external payroll systems.' },
                ],
            },
            {
                tip: 'Filter by "Pending" to quickly see only entries awaiting your review. Use bulk approve for efficiency.',
            },
        ],
    },
    {
        title: 'Leave Management',
        subtitle: 'Reviewing and approving staff leave',
        icon: CalendarDaysIcon,
        iconBg: 'bg-green-50',
        iconColor: 'text-green-600',
        headerBg: 'bg-gradient-to-r from-green-50 to-white',
        badge: { text: 'Admin / Manager', class: 'bg-[#EF233C]/10 text-[#EF233C]' },
        description: 'The Leave management section shows all staff leave requests — pending, approved, and rejected. You can approve or decline requests and see the reason the staff member provided.',
        sections: [
            {
                steps: [
                    'Go to "Leave" in the left navigation.',
                    'Filter by "Pending" to see requests awaiting decision.',
                    'Click Approve or Reject on each request.',
                    'The staff member receives a notification of your decision.',
                ],
            },
            {
                warning: 'When you assign a job to a staff member who has approved leave on that date, you\'ll see a warning automatically. Check the Leave section to avoid conflicts.',
            },
        ],
    },
    {
        title: 'Overtime Management',
        subtitle: 'Approving overtime and rest-day requests',
        icon: BriefcaseIcon,
        iconBg: 'bg-purple-50',
        iconColor: 'text-purple-500',
        headerBg: 'bg-gradient-to-r from-purple-50 to-white',
        badge: { text: 'Admin / Manager', class: 'bg-[#EF233C]/10 text-[#EF233C]' },
        description: 'Staff submit overtime requests for your approval. Once approved, the staff member can select the OT type when they clock in on that date, ensuring their overtime hours are tracked and paid correctly.',
        sections: [
            {
                items: [
                    { icon: BriefcaseIcon, iconBg: 'bg-purple-50', iconColor: 'text-purple-500', title: 'OT request',   desc: 'Overtime on a normal working day — hours beyond the regular shift.' },
                    { icon: BriefcaseIcon, iconBg: 'bg-amber-50',  iconColor: 'text-amber-500',  title: 'RDOT request', desc: 'Working on a rest day. All hours count as overtime.' },
                ],
            },
            {
                tip: 'Approving an OT request activates the OT clock-in option on the Dashboard for that staff member on that specific date.',
            },
        ],
    },
    {
        title: 'Payroll',
        subtitle: 'Generating, reviewing, and approving pay runs',
        icon: BanknotesIcon,
        iconBg: 'bg-green-50',
        iconColor: 'text-green-600',
        headerBg: 'bg-gradient-to-r from-green-50 to-white',
        badge: { text: 'Admin / Manager', class: 'bg-[#EF233C]/10 text-[#EF233C]' },
        description: 'Payroll runs calculate pay for all active staff based on their approved attendance records and hourly rates. You can review each payslip, add deductions, and approve the run when ready.',
        sections: [
            {
                heading: 'Payroll workflow',
                steps: [
                    'Go to "Payroll" in the Admin section.',
                    'Click "Generate Run" and choose the pay period dates.',
                    'The system creates a draft payslip for each active staff member.',
                    'Review each payslip — check regular hours, overtime hours, and gross pay.',
                    'Add deductions (e.g. advance repayments) where needed. Net pay updates automatically.',
                    'Approve individual payslips. Once approved, they are locked and viewable by the staff member.',
                ],
            },
            {
                info: 'Only approved attendance entries are included in payroll. Pending or rejected entries are excluded.',
            },
            {
                warning: 'Approved payslips cannot be edited. If a correction is needed, contact the system administrator.',
            },
        ],
    },
    {
        title: 'Reports',
        subtitle: 'Analytics and data summaries',
        icon: ChartBarIcon,
        iconBg: 'bg-purple-50',
        iconColor: 'text-purple-500',
        headerBg: 'bg-gradient-to-r from-purple-50 to-white',
        badge: { text: 'Admin / Manager', class: 'bg-[#EF233C]/10 text-[#EF233C]' },
        description: 'The Reports section provides charts and summaries of staff hours, attendance patterns, job completion rates, and payroll totals. Use it to monitor team performance and plan resources.',
        sections: [
            {
                items: [
                    { icon: ChartBarIcon,     iconBg: 'bg-purple-50', iconColor: 'text-purple-500', title: 'Hours summary',     desc: 'Weekly and monthly breakdown of hours worked by each staff member.' },
                    { icon: ClockIcon,        iconBg: 'bg-blue-50',   iconColor: 'text-blue-500',   title: 'Attendance report', desc: 'Absences, late arrivals, and patterns across the team.' },
                    { icon: BanknotesIcon,    iconBg: 'bg-green-50',  iconColor: 'text-green-500',  title: 'Payroll summary',   desc: 'Total gross pay and overtime costs per period.' },
                ],
            },
        ],
    },
    {
        title: 'Audit Log',
        subtitle: 'Tracking system activity',
        icon: ClipboardDocumentIcon,
        iconBg: 'bg-gray-100',
        iconColor: 'text-gray-600',
        headerBg: 'bg-gradient-to-r from-gray-50 to-white',
        badge: { text: 'Admin', class: 'bg-gray-100 text-gray-600' },
        description: 'The Audit Log records key actions taken in the portal — who approved a payslip, who reset a password, who updated a job status. It\'s a full trail of activity for accountability.',
        sections: [
            {
                items: [
                    { icon: ClipboardDocumentIcon, iconBg: 'bg-gray-100', iconColor: 'text-gray-500', title: 'Activity trail',  desc: 'Every significant action is timestamped with the user who performed it.' },
                    { icon: UsersIcon,             iconBg: 'bg-blue-50',  iconColor: 'text-blue-500', title: 'Filter by user',  desc: 'Search for a specific staff member\'s actions.' },
                    { icon: CalendarDaysIcon,      iconBg: 'bg-amber-50', iconColor: 'text-amber-500',title: 'Filter by date',  desc: 'Narrow the log to a specific date range.' },
                ],
            },
        ],
    },
];

// ── Build role-based page list ────────────────────────────────────────

const pages = computed(() => {
    const list = [...sharedPages];
    if (isSiteHead.value && !isManager.value) list.push(...siteHeadPages);
    if (isHR.value && !isManager.value) list.push(...hrPages);
    if (isManager.value) list.push(...adminPages);
    return list;
});

const currentPage = computed(() => pages.value[currentIndex.value] ?? pages.value[0]);

// ── Navigation ────────────────────────────────────────────────────────

function goTo(i) {
    direction.value = i > currentIndex.value ? 'forward' : 'backward';
    currentIndex.value = i;
    tocOpen.value = false;
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function next() { if (currentIndex.value < pages.value.length - 1) goTo(currentIndex.value + 1); }
function prev() { if (currentIndex.value > 0) goTo(currentIndex.value - 1); }
</script>

<style scoped>
/* Forward slide */
.slide-left-enter-active,
.slide-left-leave-active { transition: all 0.25s ease; }
.slide-left-enter-from   { opacity: 0; transform: translateX(24px); }
.slide-left-leave-to     { opacity: 0; transform: translateX(-24px); }

/* Backward slide */
.slide-right-enter-active,
.slide-right-leave-active { transition: all 0.25s ease; }
.slide-right-enter-from   { opacity: 0; transform: translateX(-24px); }
.slide-right-leave-to     { opacity: 0; transform: translateX(24px); }

/* TOC dropdown */
.slide-down-enter-active,
.slide-down-leave-active { transition: all 0.2s ease; }
.slide-down-enter-from,
.slide-down-leave-to     { opacity: 0; transform: translateY(-8px); }

/* Fade */
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s ease; }
.fade-enter-from, .fade-leave-to       { opacity: 0; }
</style>
