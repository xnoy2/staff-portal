<template>
    <AppLayout :title="order.title ?? order.reference ?? `Order #${order.id}`">
        <div class="max-w-4xl mx-auto space-y-5">

            <!-- Header card -->
            <div class="bg-white rounded-xl border border-gray-200 p-5 sm:p-6">
                <div class="flex items-start gap-4 flex-wrap">
                    <div class="w-14 h-14 rounded-2xl bg-[#2B2D42] flex items-center justify-center flex-shrink-0">
                        <ClipboardDocumentListIcon class="w-7 h-7 text-white" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-xs font-bold px-2 py-0.5 rounded bg-[#EF233C] text-white tracking-wide">BCF</span>
                            <h1 class="text-xl font-bold text-gray-900">
                                {{ order.title ?? order.reference ?? `Order #${order.id}` }}
                            </h1>
                            <span :class="statusBadge(order.status)" class="text-xs font-semibold px-2.5 py-1 rounded-full capitalize">
                                {{ (order.status ?? 'pending').replace('_', ' ') }}
                            </span>
                        </div>

                        <div class="flex flex-wrap gap-4 mt-2">
                            <div v-if="order.client?.name ?? order.client_name" class="flex items-center gap-1.5 text-sm text-gray-500">
                                <UserIcon class="w-4 h-4 flex-shrink-0 text-gray-400" />
                                <span>{{ order.client?.name ?? order.client_name }}</span>
                            </div>
                            <div v-if="order.worker?.name ?? order.worker_name" class="flex items-center gap-1.5 text-sm text-gray-500">
                                <WrenchScrewdriverIcon class="w-4 h-4 flex-shrink-0 text-gray-400" />
                                <span>{{ order.worker?.name ?? order.worker_name }}</span>
                            </div>
                            <div v-if="order.created_at" class="flex items-center gap-1.5 text-sm text-gray-400">
                                <CalendarIcon class="w-4 h-4 flex-shrink-0" />
                                <span>{{ formatDate(order.created_at) }}</span>
                            </div>
                        </div>

                        <p v-if="order.description" class="text-sm text-gray-400 mt-2 italic">{{ order.description }}</p>
                    </div>

                    <Link :href="route('bcf.index')" class="text-sm text-gray-400 hover:text-gray-700 flex items-center gap-1 flex-shrink-0 transition-colors">
                        <ArrowLeftIcon class="w-4 h-4" /> Orders
                    </Link>
                </div>

                <!-- Overall progress -->
                <div v-if="allTasks.length > 0" class="mt-5 pt-5 border-t border-gray-100">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-medium text-gray-500">Overall Progress</span>
                        <span class="text-xs font-bold text-gray-700">{{ doneTasks }} / {{ allTasks.length }} tasks</span>
                    </div>
                    <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full bg-[#EF233C] rounded-full transition-all duration-500" :style="{ width: overallPct + '%' }" />
                    </div>
                </div>
            </div>

            <!-- Stages -->
            <div v-if="stages.length === 0" class="bg-white rounded-xl border border-gray-200 py-12 text-center">
                <p class="text-sm text-gray-400">No stages found for this order.</p>
            </div>

            <div v-for="stage in stages" :key="stage.id" class="bg-white rounded-xl border border-gray-200 overflow-hidden">

                <!-- Stage header -->
                <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100">
                    <div :class="stageDot(stage.status)" class="w-3 h-3 rounded-full flex-shrink-0" />
                    <div class="flex-1 min-w-0">
                        <h2 class="text-sm font-semibold text-gray-800 truncate">{{ stage.title ?? stage.name ?? `Stage ${stage.id}` }}</h2>
                        <p v-if="stage.description" class="text-xs text-gray-400 mt-0.5">{{ stage.description }}</p>
                    </div>

                    <!-- Stage status selector -->
                    <select
                        :value="stage.status ?? 'pending'"
                        @change="updateStage(stage.id, $event.target.value)"
                        class="text-xs border border-gray-200 rounded-lg px-2 py-1 focus:ring-[#EF233C] focus:border-[#EF233C] flex-shrink-0"
                    >
                        <option value="pending">Pending</option>
                        <option value="in_progress">In Progress</option>
                        <option value="done">Done</option>
                    </select>
                </div>

                <!-- Tasks -->
                <div v-if="(stage.tasks ?? []).length > 0" class="divide-y divide-gray-50">
                    <div
                        v-for="task in stage.tasks"
                        :key="task.id"
                        class="flex items-start gap-3 px-5 py-3.5 hover:bg-gray-50/50 transition-colors"
                    >
                        <!-- Checkbox -->
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

                        <!-- Content -->
                        <div class="flex-1 min-w-0">
                            <p :class="['text-sm font-medium transition-colors', task.completed ? 'line-through text-gray-400' : 'text-gray-800']">
                                {{ task.title ?? task.name ?? `Task ${task.id}` }}
                            </p>
                            <p v-if="task.description && !task.completed" class="text-xs text-gray-400 mt-0.5">{{ task.description }}</p>

                            <!-- Notes area -->
                            <div v-if="task.completed && task.notes" class="mt-1.5 text-xs text-gray-500 bg-gray-50 rounded-lg px-2.5 py-1.5 italic">
                                {{ task.notes }}
                            </div>

                            <!-- Expand notes input -->
                            <Transition name="slide">
                                <div v-if="activeTask === task.id" class="mt-2 space-y-2">
                                    <textarea
                                        v-model="taskNotes[task.id]"
                                        rows="2"
                                        maxlength="2000"
                                        placeholder="Add notes (optional)…"
                                        class="w-full text-xs border border-gray-200 rounded-lg px-3 py-2 focus:ring-[#EF233C] focus:border-[#EF233C] resize-none"
                                    />
                                    <div class="flex gap-2">
                                        <button
                                            @click="confirmTaskComplete(task)"
                                            class="text-xs bg-emerald-500 hover:bg-emerald-600 text-white px-3 py-1 rounded-lg transition-colors"
                                        >
                                            Mark Complete
                                        </button>
                                        <button
                                            @click="activeTask = null"
                                            class="text-xs text-gray-400 hover:text-gray-600 px-3 py-1 rounded-lg transition-colors"
                                        >
                                            Cancel
                                        </button>
                                    </div>
                                </div>
                            </Transition>
                        </div>

                        <!-- Status badge -->
                        <span :class="task.completed ? 'text-emerald-600 bg-emerald-50' : 'text-gray-400 bg-gray-50'" class="text-[10px] font-semibold px-2 py-0.5 rounded-full flex-shrink-0 mt-0.5">
                            {{ task.completed ? 'Done' : 'Open' }}
                        </span>
                    </div>
                </div>
                <div v-else class="px-5 py-3 text-xs text-gray-400 italic">No tasks in this stage.</div>
            </div>

        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    ClipboardDocumentListIcon, UserIcon, WrenchScrewdriverIcon,
    CalendarIcon, ArrowLeftIcon, CheckIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    order: { type: Object, required: true },
});

const stages   = computed(() => props.order.stages ?? []);
const allTasks = computed(() => stages.value.flatMap(s => s.tasks ?? []));
const doneTasks = computed(() => allTasks.value.filter(t => t.completed).length);
const overallPct = computed(() =>
    allTasks.value.length ? Math.round((doneTasks.value / allTasks.value.length) * 100) : 0
);

// Task note state
const activeTask = ref(null);
const taskNotes  = ref({});

function toggleTask(task) {
    if (task.completed) {
        // Already done — uncomplete immediately
        router.patch(route('bcf.tasks.complete', task.id), { completed: false }, { preserveScroll: true });
    } else {
        // Show notes prompt before completing
        activeTask.value = activeTask.value === task.id ? null : task.id;
    }
}

function confirmTaskComplete(task) {
    router.patch(route('bcf.tasks.complete', task.id), {
        completed: true,
        notes: taskNotes.value[task.id] ?? null,
    }, {
        preserveScroll: true,
        onSuccess: () => { activeTask.value = null; },
    });
}

function updateStage(id, status) {
    router.patch(route('bcf.stages.update', id), { status }, { preserveScroll: true });
}

function formatDate(d) {
    return new Date(d).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
}

const stageDot = s => ({
    pending:     'bg-gray-300',
    in_progress: 'bg-amber-400',
    done:        'bg-emerald-500',
}[s] ?? 'bg-gray-300');

const statusBadge = s => ({
    pending:     'bg-gray-100 text-gray-500',
    in_progress: 'bg-amber-100 text-amber-700',
    done:        'bg-emerald-100 text-emerald-700',
}[s] ?? 'bg-gray-100 text-gray-500');
</script>

<style scoped>
.slide-enter-active, .slide-leave-active { transition: opacity 0.15s ease, transform 0.15s ease; }
.slide-enter-from, .slide-leave-to       { opacity: 0; transform: translateY(-4px); }
</style>
