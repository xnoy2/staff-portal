<template>
    <AppLayout :title="order.client?.name ?? order.order_number">
        <div class="max-w-6xl mx-auto space-y-4">

            <!-- Back -->
            <Link :href="route('bcf.index')" class="inline-flex items-center gap-1.5 text-xs text-gray-400 hover:text-gray-700 transition-colors">
                <ArrowLeftIcon class="w-3.5 h-3.5" /> Back to Orders
            </Link>

            <!-- Header card -->
            <div class="bg-white rounded-xl border border-gray-200 p-5 sm:p-6">
                <div class="flex items-start gap-4">
                    <div class="flex-1 min-w-0">
                        <!-- Title -->
                        <h1 class="text-xl font-bold text-gray-900">{{ order.client?.name ?? order.order_number }}</h1>

                        <!-- Meta row -->
                        <div class="flex flex-wrap items-center gap-x-5 gap-y-1.5 mt-2">
                            <span class="flex items-center gap-1.5 text-xs text-gray-500">
                                <span class="text-[10px] font-bold tracking-widest text-gray-400">📋</span>
                                <span class="font-mono text-xs text-gray-600">{{ order.order_number }}</span>
                            </span>
                            <span v-if="order.address" class="flex items-center gap-1.5 text-xs text-gray-500">
                                <MapPinIcon class="w-3.5 h-3.5 text-[#EF233C] flex-shrink-0" />
                                {{ order.address }}
                            </span>
                            <span v-if="order.installation_date" class="flex items-center gap-1.5 text-xs text-gray-500">
                                <CalendarIcon class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" />
                                {{ formatDate(order.installation_date) }}
                            </span>
                            <span v-if="order.build_date" class="flex items-center gap-1.5 text-xs text-gray-500">
                                <WrenchScrewdriverIcon class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" />
                                Build: {{ formatDate(order.build_date) }}
                            </span>
                        </div>
                    </div>

                    <!-- Progress badge -->
                    <div class="flex-shrink-0 w-16 h-16 rounded-xl bg-[#2B2D42] flex flex-col items-center justify-center text-white">
                        <span class="text-lg font-extrabold leading-none">{{ overallPct }}%</span>
                        <span class="text-[9px] font-semibold tracking-wide opacity-70 mt-0.5">Complete</span>
                    </div>
                </div>

                <!-- Progress bar -->
                <div class="mt-4">
                    <div class="h-2.5 bg-gray-100 rounded-full overflow-hidden">
                        <div
                            class="h-full rounded-full transition-all duration-500"
                            :class="overallPct === 100 ? 'bg-emerald-500' : 'bg-amber-400'"
                            :style="{ width: overallPct + '%' }"
                        />
                    </div>
                </div>
            </div>

            <!-- Two-column layout -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

                <!-- ── Left: Build Stages ── -->
                <div class="lg:col-span-2 space-y-3">
                    <div class="bg-white rounded-xl border border-gray-200 p-5">
                        <h2 class="text-sm font-bold text-gray-800 mb-4 flex items-center gap-2">
                            🏗️ Build Stages
                        </h2>

                        <div class="space-y-2">
                            <div
                                v-for="(stage, idx) in sortedStages"
                                :key="stage.id"
                                class="rounded-xl border transition-all"
                                :class="stageRowClass(stage, idx)"
                            >
                                <!-- Stage header row -->
                                <div class="flex items-center gap-3 px-4 py-3">

                                    <!-- Status icon -->
                                    <div class="flex-shrink-0">
                                        <!-- Done: green check -->
                                        <div v-if="stage.status === 'done'" class="w-7 h-7 rounded-full bg-emerald-500 flex items-center justify-center">
                                            <CheckIcon class="w-4 h-4 text-white" />
                                        </div>
                                        <!-- Locked: hourglass -->
                                        <div v-else-if="isLocked(idx)" class="w-7 h-7 rounded-full bg-gray-100 flex items-center justify-center">
                                            <span class="text-sm">⏳</span>
                                        </div>
                                        <!-- Active: blue -->
                                        <div v-else class="w-7 h-7 rounded-full bg-blue-500 flex items-center justify-center">
                                            <span class="text-xs font-bold text-white">{{ stage.stage_number }}</span>
                                        </div>
                                    </div>

                                    <!-- Label + subtitle -->
                                    <div class="flex-1 min-w-0">
                                        <p :class="['text-sm font-semibold', isLocked(idx) ? 'text-gray-400' : 'text-gray-800']">
                                            {{ stage.label }}
                                        </p>
                                        <p v-if="stage.status === 'done' && stage.completed_at" class="text-xs text-emerald-600 mt-0.5">
                                            Completed {{ formatShortDate(stage.completed_at) }}
                                        </p>
                                        <p v-else-if="isLocked(idx)" class="text-xs text-gray-400 mt-0.5">
                                            Complete the previous stage first
                                        </p>
                                        <p v-else-if="(stage.tasks ?? []).length > 0 && allTasksDone(stage)" class="text-xs text-emerald-600 mt-0.5">
                                            All {{ stage.tasks.length }} task{{ stage.tasks.length !== 1 ? 's' : '' }} done
                                        </p>
                                    </div>

                                    <!-- Actions (right side) -->
                                    <div v-if="!isLocked(idx)" class="flex items-center gap-2 flex-shrink-0">
                                        <!-- Task count chip -->
                                        <span
                                            v-if="(stage.tasks ?? []).length > 0"
                                            class="text-xs font-medium text-gray-500 border border-gray-200 rounded-full px-2 py-0.5"
                                        >
                                            + Task ({{ doneTaskCount(stage) }}/{{ stage.tasks.length }})
                                        </span>

                                        <!-- Done stage: Undo -->
                                        <button
                                            v-if="stage.status === 'done'"
                                            @click="undoStage(stage)"
                                            :disabled="updatingStage === stage.id"
                                            class="text-xs text-gray-400 hover:text-gray-700 border border-gray-200 hover:border-gray-300 rounded-lg px-2.5 py-1 transition-colors disabled:opacity-40"
                                        >
                                            Undo
                                        </button>

                                        <!-- Active stage: Done -->
                                        <button
                                            v-else
                                            @click="markStageDone(stage)"
                                            :disabled="updatingStage === stage.id"
                                            class="text-xs font-semibold bg-[#2B2D42] hover:bg-[#3d405e] disabled:opacity-40 text-white rounded-lg px-3 py-1 transition-colors flex items-center gap-1"
                                        >
                                            <span v-if="updatingStage === stage.id" class="w-3 h-3 border-2 border-white/40 border-t-white rounded-full animate-spin" />
                                            <CheckIcon v-else class="w-3 h-3" />
                                            Done
                                        </button>

                                        <!-- Expand chevron -->
                                        <button
                                            v-if="(stage.tasks ?? []).length > 0"
                                            @click="toggleStageExpand(stage.id)"
                                            class="p-1 text-gray-400 hover:text-gray-600 rounded transition-colors"
                                        >
                                            <ChevronDownIcon class="w-4 h-4 transition-transform" :class="expandedStages.has(stage.id) ? 'rotate-180' : ''" />
                                        </button>
                                    </div>
                                </div>

                                <!-- Tasks (expanded) -->
                                <Transition name="slide">
                                    <div
                                        v-if="!isLocked(idx) && expandedStages.has(stage.id) && (stage.tasks ?? []).length > 0"
                                        class="border-t border-gray-100 px-4 py-3 space-y-2"
                                    >
                                        <div
                                            v-for="task in stage.tasks"
                                            :key="task.id"
                                            class="flex items-start gap-3 py-1"
                                        >
                                            <button
                                                @click="toggleTask(task)"
                                                :class="[
                                                    'w-5 h-5 rounded border-2 flex-shrink-0 mt-0.5 flex items-center justify-center transition-all',
                                                    task.completed
                                                        ? 'bg-emerald-500 border-emerald-500 text-white'
                                                        : 'border-gray-300 hover:border-[#EF233C]'
                                                ]"
                                            >
                                                <CheckIcon v-if="task.completed" class="w-3 h-3" />
                                            </button>
                                            <div class="flex-1 min-w-0">
                                                <p :class="['text-sm', task.completed ? 'line-through text-gray-400' : 'text-gray-700']">
                                                    {{ task.title ?? task.label ?? `Task ${task.id}` }}
                                                </p>
                                                <p v-if="task.notes" class="text-xs text-gray-400 mt-0.5 italic">{{ task.notes }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </Transition>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── Right: Sidebar ── -->
                <div class="space-y-3">

                    <!-- Birthday booking notice -->
                    <div v-if="order.is_birthday_booking" class="bg-amber-50 border border-amber-200 rounded-xl p-4 flex gap-3">
                        <span class="text-xl flex-shrink-0">🎂</span>
                        <div>
                            <p class="text-sm font-semibold text-amber-800">Birthday Booking</p>
                            <p class="text-xs text-amber-700 mt-0.5">Remember to bring freebies for this installation!</p>
                        </div>
                    </div>

                    <!-- Client Details -->
                    <div class="bg-white rounded-xl border border-gray-200 p-5">
                        <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wide mb-3 flex items-center gap-1.5">
                            <UserIcon class="w-3.5 h-3.5" /> Client Details
                        </h3>
                        <dl class="space-y-2.5">
                            <div class="flex justify-between gap-2">
                                <dt class="text-xs text-gray-400">Name</dt>
                                <dd class="text-xs font-semibold text-gray-800 text-right">{{ order.client?.name ?? '—' }}</dd>
                            </div>
                            <div class="flex justify-between gap-2">
                                <dt class="text-xs text-gray-400">Email</dt>
                                <dd class="text-xs font-semibold text-[#EF233C] text-right truncate">{{ order.client?.email ?? '—' }}</dd>
                            </div>
                            <div class="flex justify-between gap-2">
                                <dt class="text-xs text-gray-400">Phone</dt>
                                <dd class="text-xs font-semibold text-gray-800 text-right">{{ order.client?.phone ?? '—' }}</dd>
                            </div>
                            <div v-if="order.address" class="flex justify-between gap-2">
                                <dt class="text-xs text-gray-400">Address</dt>
                                <dd class="text-xs font-semibold text-gray-800 text-right">{{ order.address }}</dd>
                            </div>
                            <div v-if="order.product_order" class="flex justify-between gap-2">
                                <dt class="text-xs text-gray-400">Product</dt>
                                <dd class="text-xs font-semibold text-gray-800 text-right">{{ order.product_order }}</dd>
                            </div>
                            <div v-if="order.contract_amount" class="flex justify-between gap-2">
                                <dt class="text-xs text-gray-400">Contract</dt>
                                <dd class="text-xs font-semibold text-gray-800 text-right">£{{ Number(order.contract_amount).toLocaleString('en-GB') }}</dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Notes -->
                    <div v-if="order.notes" class="bg-white rounded-xl border border-gray-200 p-5">
                        <h3 class="text-xs font-bold text-[#EF233C] uppercase tracking-wide mb-2 flex items-center gap-1.5">
                            📌 Notes
                        </h3>
                        <p class="text-xs text-gray-600 whitespace-pre-line">{{ order.notes }}</p>
                    </div>

                    <!-- Access Notes -->
                    <div v-if="order.access_notes" class="bg-amber-50 border border-amber-200 rounded-xl p-4">
                        <p class="text-xs font-semibold text-amber-700">
                            🔑 Access Notes: <span class="font-normal">{{ order.access_notes }}</span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    ArrowLeftIcon, CheckIcon, ChevronDownIcon,
    MapPinIcon, CalendarIcon, WrenchScrewdriverIcon, UserIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    order:  { type: Object, required: true },
    stages: { type: Array,  default: () => [] },
});

// Sort by stage_number
const sortedStages = computed(() =>
    [...props.stages].sort((a, b) => a.stage_number - b.stage_number)
);

// A stage is locked if the previous stage is not "done"
function isLocked(idx) {
    if (idx === 0) return false;
    return sortedStages.value[idx - 1].status !== 'done';
}

// Progress — % of stages marked done
const overallPct = computed(() => {
    if (!sortedStages.value.length) return 0;
    const done = sortedStages.value.filter(s => s.status === 'done').length;
    return Math.round((done / sortedStages.value.length) * 100);
});

// Task helpers
function doneTaskCount(stage) {
    return (stage.tasks ?? []).filter(t => t.completed).length;
}
function allTasksDone(stage) {
    const tasks = stage.tasks ?? [];
    return tasks.length > 0 && tasks.every(t => t.completed);
}

// Expanded stages set
const expandedStages = ref(new Set());
function toggleStageExpand(id) {
    const s = new Set(expandedStages.value);
    s.has(id) ? s.delete(id) : s.add(id);
    expandedStages.value = s;
}

// Stage actions
const updatingStage = ref(null);

function markStageDone(stage) {
    updatingStage.value = stage.id;
    router.patch(route('bcf.stages.update', stage.id), { status: 'done' }, {
        preserveScroll: true,
        onFinish: () => { updatingStage.value = null; },
    });
}

function undoStage(stage) {
    updatingStage.value = stage.id;
    router.patch(route('bcf.stages.update', stage.id), { status: 'pending' }, {
        preserveScroll: true,
        onFinish: () => { updatingStage.value = null; },
    });
}

// Task toggle
function toggleTask(task) {
    router.patch(route('bcf.tasks.complete', task.id), {
        completed: !task.completed,
    }, { preserveScroll: true });
}

// Styling
function stageRowClass(stage, idx) {
    if (stage.status === 'done')   return 'border-emerald-200 bg-emerald-50/40';
    if (isLocked(idx))             return 'border-gray-100 bg-gray-50/50 opacity-60';
    return 'border-blue-200 bg-blue-50/30';
}

function formatDate(d) {
    if (!d) return '';
    return new Date(d + 'T00:00:00').toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
}

function formatShortDate(d) {
    if (!d) return '';
    return new Date(d).toLocaleDateString('en-GB', { day: 'numeric', month: 'short' });
}
</script>

<style scoped>
.slide-enter-active, .slide-leave-active { transition: opacity 0.15s ease, transform 0.15s ease; }
.slide-enter-from, .slide-leave-to       { opacity: 0; transform: translateY(-4px); }
</style>
