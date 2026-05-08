<template>
    <AppLayout :title="project.name">
        <div class="max-w-6xl mx-auto space-y-6">

            <!-- Back + header -->
            <div class="flex items-start gap-3 flex-wrap">
                <Link :href="route('bgr.index')" class="mt-0.5 p-1.5 rounded-lg hover:bg-gray-200 transition-colors text-gray-500">
                    <ArrowLeftIcon class="w-4 h-4" />
                </Link>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h1 class="text-lg font-semibold text-gray-800">{{ project.name }}</h1>
                        <StatusBadge :status="project.status" />
                    </div>
                    <p v-if="project.address" class="text-xs text-gray-500 mt-0.5 flex items-center gap-1">
                        <MapPinIcon class="w-3.5 h-3.5" /> {{ project.address }}
                    </p>
                </div>
            </div>

            <!-- Progress bar -->
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <div class="flex items-center justify-between text-xs text-gray-500 mb-2">
                    <span>{{ project.completed_stages }} of {{ project.total_stages }} stages completed</span>
                    <span class="font-semibold text-gray-700">{{ project.progress_pct }}%</span>
                </div>
                <div class="w-full bg-gray-100 rounded-full h-2">
                    <div
                        class="h-2 rounded-full bg-[#EF233C] transition-all"
                        :style="{ width: project.progress_pct + '%' }"
                    />
                </div>
                <div class="flex items-center gap-4 mt-3 text-xs text-gray-500 flex-wrap">
                    <span v-if="project.client">
                        <span class="font-medium text-gray-700">Client:</span> {{ project.client.name }}
                    </span>
                    <span v-if="project.start_date">
                        <span class="font-medium text-gray-700">Start:</span> {{ formatDate(project.start_date) }}
                    </span>
                    <span v-if="project.estimated_completion">
                        <span class="font-medium text-gray-700">Est. completion:</span> {{ formatDate(project.estimated_completion) }}
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- ── Stages + Tasks ── -->
                <div class="lg:col-span-2 space-y-3">
                    <h2 class="text-sm font-semibold text-gray-700">Stages &amp; Tasks</h2>

                    <div
                        v-for="stage in project.stages"
                        :key="stage.id"
                        class="bg-white rounded-xl border border-gray-200 overflow-hidden"
                    >
                        <!-- Stage header -->
                        <button
                            class="w-full flex items-center gap-3 px-4 py-3 text-left hover:bg-gray-50 transition-colors"
                            @click="toggleStage(stage.id)"
                        >
                            <div :class="['w-2 h-2 rounded-full flex-shrink-0', stageStatusColor(stage)]" />
                            <span class="flex-1 text-sm font-semibold text-gray-800">{{ stage.name }}</span>
                            <span class="text-[10px] font-medium px-2 py-0.5 rounded-full" :class="stageStatusBadge(stage)">
                                {{ stageStatusLabel(stage) }}
                            </span>
                            <ChevronDownIcon
                                class="w-4 h-4 text-gray-400 transition-transform flex-shrink-0"
                                :class="{ 'rotate-180': expandedStages.includes(stage.id) }"
                            />
                        </button>

                        <!-- Task list -->
                        <div v-if="expandedStages.includes(stage.id)" class="divide-y divide-gray-50 border-t border-gray-100">
                            <div v-if="stage.substages.length === 0" class="px-4 py-3 text-xs text-gray-400 italic">
                                No tasks in this stage.
                            </div>
                            <div
                                v-for="task in stage.substages"
                                :key="task.id"
                                class="px-4 py-3 flex items-start gap-3"
                            >
                                <!-- Checkbox -->
                                <button
                                    @click="openTaskModal(stage, task)"
                                    :class="[
                                        'mt-0.5 w-4 h-4 rounded border-2 flex-shrink-0 flex items-center justify-center transition-colors',
                                        task.status === 'completed'
                                            ? 'bg-green-500 border-green-500'
                                            : 'border-gray-300 hover:border-[#EF233C]',
                                    ]"
                                >
                                    <CheckIcon v-if="task.status === 'completed'" class="w-2.5 h-2.5 text-white" />
                                </button>

                                <div class="flex-1 min-w-0">
                                    <p :class="['text-sm', task.status === 'completed' ? 'line-through text-gray-400' : 'text-gray-700']">
                                        {{ task.name }}
                                    </p>
                                    <!-- Note preview -->
                                    <p v-if="task.note" class="text-xs text-gray-400 mt-0.5 line-clamp-1">{{ task.note }}</p>
                                    <!-- Photos -->
                                    <div v-if="task.photos && task.photos.length > 0" class="flex gap-1.5 mt-1.5 flex-wrap">
                                        <button
                                            v-for="(photo, i) in task.photos"
                                            :key="i"
                                            @click="openPhoto(photo)"
                                            class="w-10 h-10 rounded-md overflow-hidden border border-gray-200 bg-gray-100 hover:border-[#EF233C] transition-colors"
                                        >
                                            <img
                                                :src="proxyUrl(photo)"
                                                class="w-full h-full object-cover"
                                                loading="lazy"
                                            />
                                        </button>
                                    </div>
                                </div>

                                <!-- Edit note (completed tasks) -->
                                <button
                                    v-if="task.status === 'completed'"
                                    @click="openNoteEdit(stage, task)"
                                    class="p-1 text-gray-300 hover:text-gray-600 transition-colors flex-shrink-0 mt-0.5"
                                    title="Edit note"
                                >
                                    <PencilSquareIcon class="w-3.5 h-3.5" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── Progress Updates ── -->
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-gray-700">Progress Updates</h2>
                        <button
                            @click="showUpdateForm = !showUpdateForm"
                            class="flex items-center gap-1 text-xs font-semibold text-[#EF233C] hover:text-[#D90429] transition-colors"
                        >
                            <PlusIcon class="w-3.5 h-3.5" />
                            Post Update
                        </button>
                    </div>

                    <!-- Post update form -->
                    <div v-if="showUpdateForm" class="bg-white rounded-xl border border-gray-200 p-4 space-y-3">
                        <input
                            v-model="updateForm.title"
                            type="text"
                            placeholder="Title"
                            class="w-full rounded-lg border-gray-200 text-sm focus:ring-[#EF233C] focus:border-[#EF233C]"
                        />
                        <p v-if="updateForm.errors.title" class="text-xs text-red-600">{{ updateForm.errors.title }}</p>
                        <textarea
                            v-model="updateForm.body"
                            rows="3"
                            placeholder="Describe what was done…"
                            class="w-full rounded-lg border-gray-200 text-sm focus:ring-[#EF233C] focus:border-[#EF233C]"
                        />
                        <p v-if="updateForm.errors.body" class="text-xs text-red-600">{{ updateForm.errors.body }}</p>
                        <select
                            v-model="updateForm.stage_id"
                            class="w-full rounded-lg border-gray-200 text-sm focus:ring-[#EF233C] focus:border-[#EF233C]"
                        >
                            <option :value="null">No specific stage</option>
                            <option v-for="s in project.stages" :key="s.id" :value="s.id">{{ s.name }}</option>
                        </select>
                        <div>
                            <label class="text-xs text-gray-500 mb-1 block">Photos (optional)</label>
                            <input type="file" multiple accept="image/*" @change="updateForm.photos = Array.from($event.target.files)" class="text-xs text-gray-600" />
                        </div>
                        <div class="flex gap-2 justify-end">
                            <button type="button" @click="showUpdateForm = false" class="text-sm text-gray-500 px-3 py-1.5 rounded-lg hover:bg-gray-100">Cancel</button>
                            <button @click="submitUpdate" :disabled="updateForm.processing" class="text-sm font-semibold bg-[#EF233C] hover:bg-[#D90429] disabled:opacity-50 text-white px-4 py-1.5 rounded-lg">
                                Post
                            </button>
                        </div>
                    </div>

                    <!-- Updates list -->
                    <div v-if="updates.length === 0 && !showUpdateForm" class="bg-white rounded-xl border border-dashed border-gray-200 py-10 text-center">
                        <p class="text-sm text-gray-400">No updates yet</p>
                    </div>

                    <div
                        v-for="update in updates"
                        :key="update.id"
                        class="bg-white rounded-xl border border-gray-200 p-4 space-y-2"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <p class="text-sm font-semibold text-gray-800">{{ update.title }}</p>
                            <span v-if="update.stage" class="text-[10px] bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full whitespace-nowrap">{{ update.stage.name }}</span>
                        </div>
                        <p class="text-xs text-gray-600 whitespace-pre-line">{{ update.body }}</p>
                        <div v-if="update.photos && update.photos.length > 0" class="flex gap-1.5 flex-wrap">
                            <button
                                v-for="(photo, i) in update.photos"
                                :key="i"
                                @click="openPhoto(photo)"
                                class="w-12 h-12 rounded-md overflow-hidden border border-gray-200 bg-gray-100"
                            >
                                <img :src="proxyUrl(photo)" class="w-full h-full object-cover" loading="lazy" />
                            </button>
                        </div>
                        <div class="flex items-center justify-between text-[10px] text-gray-400 pt-1">
                            <span>{{ update.author?.name }}</span>
                            <span>{{ formatDate(update.published_at) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Task complete modal -->
        <BaseModal :open="!!taskModal" @close="closeTaskModal">
            <div class="flex items-start gap-3 p-6 border-b border-gray-100">
                <div :class="['w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0', taskModal?.task?.status === 'completed' ? 'bg-red-50' : 'bg-green-50']">
                    <CheckIcon v-if="taskModal?.task?.status !== 'completed'" class="w-5 h-5 text-green-600" />
                    <XMarkIcon v-else class="w-5 h-5 text-red-500" />
                </div>
                <div class="min-w-0">
                    <h2 class="text-base font-semibold text-gray-800">
                        {{ taskModal?.task?.status === 'completed' ? 'Undo Task' : 'Complete Task' }}
                    </h2>
                    <p class="text-xs text-gray-500 mt-0.5 line-clamp-2">{{ taskModal?.task?.name }}</p>
                </div>
            </div>

            <div v-if="taskModal?.task?.status !== 'completed'" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Note <span class="text-gray-400 font-normal">(optional)</span></label>
                    <textarea
                        v-model="taskForm.note"
                        rows="3"
                        class="w-full rounded-lg border-gray-200 text-sm focus:ring-[#EF233C] focus:border-[#EF233C]"
                        placeholder="Add a completion note…"
                    />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Photos <span class="text-gray-400 font-normal">(optional)</span></label>
                    <input
                        type="file"
                        multiple
                        accept="image/*,.pdf,.doc,.docx,.xls,.xlsx"
                        @change="taskForm.photos = Array.from($event.target.files)"
                        class="text-sm text-gray-600"
                    />
                </div>
                <!-- Upload progress -->
                <div v-if="taskForm.progress" class="w-full bg-gray-100 rounded-full h-1.5">
                    <div class="h-1.5 rounded-full bg-[#EF233C] transition-all" :style="{ width: taskForm.progress.percentage + '%' }" />
                </div>
            </div>
            <div v-else class="p-6">
                <p class="text-sm text-gray-500">This will mark the task as incomplete and remove any associated progress update.</p>
            </div>

            <div class="flex justify-end gap-2 px-6 pb-6">
                <button type="button" @click="closeTaskModal" class="text-sm text-gray-600 px-4 py-2 rounded-lg hover:bg-gray-100">Cancel</button>
                <button
                    @click="submitTask"
                    :disabled="taskForm.processing"
                    :class="['text-sm font-semibold disabled:opacity-50 text-white px-5 py-2 rounded-lg transition-colors', taskModal?.task?.status === 'completed' ? 'bg-red-500 hover:bg-red-600' : 'bg-green-600 hover:bg-green-700']"
                >
                    {{ taskModal?.task?.status === 'completed' ? 'Undo' : 'Mark Complete' }}
                </button>
            </div>
        </BaseModal>

        <!-- Edit note modal -->
        <BaseModal :open="!!noteModal" @close="closeNoteModal">
            <div class="flex items-center gap-3 p-6 border-b border-gray-100">
                <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center flex-shrink-0">
                    <PencilSquareIcon class="w-5 h-5 text-blue-600" />
                </div>
                <h2 class="text-base font-semibold text-gray-800">Edit Note</h2>
            </div>
            <div class="p-6 space-y-4">
                <textarea
                    v-model="noteForm.note"
                    rows="4"
                    class="w-full rounded-lg border-gray-200 text-sm focus:ring-[#EF233C] focus:border-[#EF233C]"
                    placeholder="Update the completion note…"
                />
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Add more photos</label>
                    <input type="file" multiple accept="image/*" @change="noteForm.newPhotos = Array.from($event.target.files)" class="text-sm text-gray-600" />
                </div>
            </div>
            <div class="flex justify-end gap-2 px-6 pb-6">
                <button type="button" @click="closeNoteModal" class="text-sm text-gray-600 px-4 py-2 rounded-lg hover:bg-gray-100">Cancel</button>
                <button @click="submitNote" :disabled="noteForm.processing" class="text-sm font-semibold bg-[#EF233C] hover:bg-[#D90429] disabled:opacity-50 text-white px-5 py-2 rounded-lg">
                    Save
                </button>
            </div>
        </BaseModal>

        <!-- Photo lightbox -->
        <Transition name="fade">
            <div v-if="lightboxUrl" class="fixed inset-0 z-50 bg-black/80 flex items-center justify-center p-4" @click="lightboxUrl = null">
                <img :src="lightboxUrl" class="max-w-full max-h-full rounded-lg object-contain" @click.stop />
                <button @click="lightboxUrl = null" class="absolute top-4 right-4 text-white p-2 rounded-full bg-black/50 hover:bg-black/70">
                    <XMarkIcon class="w-5 h-5" />
                </button>
            </div>
        </Transition>

    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BaseModal from '@/Components/BaseModal.vue';
import {
    ArrowLeftIcon, MapPinIcon, ChevronDownIcon, CheckIcon,
    PencilSquareIcon, PlusIcon, XMarkIcon,
} from '@heroicons/vue/24/outline';

const STATUS_CLASSES = {
    pending:   'bg-amber-100 text-amber-700',
    active:    'bg-green-100 text-green-700',
    on_hold:   'bg-blue-100 text-blue-700',
    completed: 'bg-gray-100 text-gray-600',
    cancelled: 'bg-red-100 text-red-600',
};

const StatusBadge = {
    props: ['status'],
    template: `<span :class="['text-[10px] font-semibold px-2 py-0.5 rounded-full capitalize whitespace-nowrap', classes[status] ?? 'bg-gray-100 text-gray-600']">{{ label }}</span>`,
    setup(props) {
        return { classes: STATUS_CLASSES, label: props.status?.replace('_', ' ') };
    },
};

const props = defineProps({
    project: { type: Object, required: true },
    updates: { type: Array,  default: () => [] },
});

// ── Stage accordion ───────────────────────────────────────────────────────────

const expandedStages = ref(
    props.project.stages
        ?.filter(s => s.status !== 'completed')
        .map(s => s.id) ?? []
);

function toggleStage(id) {
    const idx = expandedStages.value.indexOf(id);
    if (idx >= 0) expandedStages.value.splice(idx, 1);
    else expandedStages.value.push(id);
}

function stageStatusColor(stage) {
    if (stage.status === 'completed') return 'bg-green-500';
    if (stage.status === 'in_progress') return 'bg-amber-400';
    return 'bg-gray-300';
}

function stageStatusBadge(stage) {
    if (stage.status === 'completed') return 'bg-green-100 text-green-700';
    if (stage.status === 'in_progress') return 'bg-amber-100 text-amber-700';
    return 'bg-gray-100 text-gray-500';
}

function stageStatusLabel(stage) {
    return stage.status === 'in_progress' ? 'In Progress' : (stage.status ?? 'Pending');
}

// ── Task toggle modal ─────────────────────────────────────────────────────────

const taskModal = ref(null);
const taskForm  = useForm({ note: '', photos: [] });

function openTaskModal(stage, task) {
    taskModal.value = { stage, task };
    taskForm.note   = '';
    taskForm.photos = [];
}

function closeTaskModal() {
    taskModal.value = null;
}

function submitTask() {
    const { stage, task } = taskModal.value;
    taskForm
        .transform(data => {
            const fd = new FormData();
            if (task.status !== 'completed' && data.note) fd.append('note', data.note);
            data.photos.forEach(f => fd.append('photos[]', f));
            return fd;
        })
        .post(route('bgr.tasks.toggle', {
            projectId:   props.project.id,
            stageId:     stage.id,
            substageId:  task.id,
        }), {
            onSuccess: () => closeTaskModal(),
            forceFormData: true,
        });
}

// ── Note edit modal ───────────────────────────────────────────────────────────

const noteModal = ref(null);
const noteForm  = useForm({ note: '', keepPhotos: [], newPhotos: [] });

function openNoteEdit(stage, task) {
    noteModal.value      = { stage, task };
    noteForm.note        = task.note ?? '';
    noteForm.keepPhotos  = [...(task.photos ?? [])];
    noteForm.newPhotos   = [];
}

function closeNoteModal() {
    noteModal.value = null;
}

function submitNote() {
    const { stage, task } = noteModal.value;
    noteForm
        .transform(data => {
            const fd = new FormData();
            fd.append('note', data.note ?? '');
            data.keepPhotos.forEach(url => fd.append('keep_photos[]', url));
            data.newPhotos.forEach(f  => fd.append('new_photos[]', f));
            return fd;
        })
        .post(route('bgr.tasks.note', {
            projectId:   props.project.id,
            stageId:     stage.id,
            substageId:  task.id,
        }), {
            onSuccess: () => closeNoteModal(),
            forceFormData: true,
        });
}

// ── Progress update form ──────────────────────────────────────────────────────

const showUpdateForm = ref(false);
const updateForm     = useForm({ title: '', body: '', stage_id: null, photos: [] });

function submitUpdate() {
    updateForm
        .transform(data => {
            const fd = new FormData();
            fd.append('title', data.title);
            fd.append('body',  data.body);
            if (data.stage_id) fd.append('stage_id', data.stage_id);
            data.photos.forEach(f => fd.append('photos[]', f));
            return fd;
        })
        .post(route('bgr.updates.store', props.project.id), {
            onSuccess: () => {
                showUpdateForm.value = false;
                updateForm.reset();
            },
            forceFormData: true,
        });
}

// ── Photo proxy + lightbox ────────────────────────────────────────────────────

const lightboxUrl = ref(null);

function proxyUrl(originalUrl) {
    return route('bgr.photo') + '?url=' + encodeURIComponent(originalUrl);
}

function openPhoto(originalUrl) {
    lightboxUrl.value = proxyUrl(originalUrl);
}

// ── Helpers ───────────────────────────────────────────────────────────────────

function formatDate(dateStr) {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
}
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
