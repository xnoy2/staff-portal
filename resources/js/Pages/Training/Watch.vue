<template>
    <AppLayout :title="`${module.title} — Training`">
        <div class="max-w-7xl mx-auto">

            <!-- Breadcrumb -->
            <div class="flex items-center gap-2 text-xs text-gray-500 mb-4">
                <Link :href="route('training.index')" class="hover:text-[#EF233C] transition-colors">Training</Link>
                <span>/</span>
                <span class="text-gray-700 font-medium truncate">{{ module.title }}</span>
            </div>

            <div class="flex flex-col lg:flex-row gap-5">

                <!-- ── Main: video player + lesson info ─────────────────────── -->
                <div class="flex-1 min-w-0 space-y-4">

                    <!-- No lessons yet (admin empty state) -->
                    <div v-if="!lesson" class="bg-white rounded-xl border border-dashed border-gray-300 flex flex-col items-center justify-center py-24 text-center">
                        <VideoCameraSlashIcon class="w-12 h-12 text-gray-300 mb-3" />
                        <p class="text-gray-600 font-medium">No lessons yet</p>
                        <p class="text-sm text-gray-400 mt-1">Click "Add Lesson" in the sidebar to create the first lesson.</p>
                    </div>

                    <template v-else>
                        <!-- Video player -->
                        <div class="bg-black rounded-xl overflow-hidden">
                            <video
                                v-if="lesson.video_url"
                                :src="lesson.video_url"
                                controls
                                controlsList="nodownload noremoteplayback"
                                disablePictureInPicture
                                class="w-full aspect-video"
                                preload="metadata"
                                @contextmenu.prevent
                            />
                            <div v-else class="flex items-center justify-center h-64 text-white/40">
                                <div class="text-center">
                                    <VideoCameraSlashIcon class="w-12 h-12 mx-auto mb-2" />
                                    <p class="text-sm">No video uploaded yet</p>
                                </div>
                            </div>
                        </div>

                        <!-- Lesson header -->
                        <div class="bg-white rounded-xl border border-gray-200 p-5">
                            <div class="flex items-start justify-between gap-4 flex-wrap">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap mb-1">
                                        <span v-if="!lesson.is_published && isPrivileged" class="text-[10px] font-bold bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full uppercase tracking-wide">Draft</span>
                                        <h1 class="text-lg font-bold text-gray-800">{{ lesson.title }}</h1>
                                    </div>
                                    <p v-if="lesson.duration_label" class="text-xs text-gray-400">{{ lesson.duration_label }}</p>
                                </div>

                                <!-- Actions -->
                                <div class="flex items-center gap-2 flex-shrink-0 flex-wrap">
                                    <!-- Mark complete -->
                                    <button
                                        @click="toggleComplete"
                                        :class="[
                                            'flex items-center gap-1.5 text-xs font-semibold px-3 py-2 rounded-lg border transition-colors',
                                            isCompleted
                                                ? 'bg-green-50 text-green-700 border-green-200 hover:bg-green-100'
                                                : 'bg-gray-50 text-gray-600 border-gray-200 hover:bg-gray-100',
                                        ]"
                                    >
                                        <CheckCircleIcon class="w-4 h-4" />
                                        {{ isCompleted ? 'Completed' : 'Mark Complete' }}
                                    </button>

                                    <!-- Admin controls -->
                                    <template v-if="isPrivileged">
                                        <button @click="openEditLesson" class="flex items-center gap-1.5 text-xs text-gray-500 hover:text-gray-800 border border-gray-200 px-3 py-2 rounded-lg hover:bg-gray-50 transition-colors">
                                            <PencilIcon class="w-3.5 h-3.5" /> Edit
                                        </button>
                                        <button @click="togglePublishLesson" :class="['flex items-center gap-1.5 text-xs border px-3 py-2 rounded-lg transition-colors', lesson.is_published ? 'text-green-600 border-green-200 hover:bg-green-50' : 'text-gray-500 border-gray-200 hover:bg-gray-50']">
                                            <EyeIcon v-if="lesson.is_published" class="w-3.5 h-3.5" />
                                            <EyeSlashIcon v-else class="w-3.5 h-3.5" />
                                            {{ lesson.is_published ? 'Published' : 'Publish' }}
                                        </button>
                                        <button @click="confirmDeleteLesson" class="flex items-center gap-1.5 text-xs text-red-500 border border-red-200 px-3 py-2 rounded-lg hover:bg-red-50 transition-colors">
                                            <TrashIcon class="w-3.5 h-3.5" /> Delete
                                        </button>
                                    </template>
                                </div>
                            </div>

                            <p v-if="lesson.description" class="mt-4 text-sm text-gray-600 whitespace-pre-wrap leading-relaxed">{{ lesson.description }}</p>
                        </div>
                    </template>
                </div>

                <!-- ── Sidebar: curriculum ──────────────────────────────────── -->
                <div class="lg:w-80 flex-shrink-0 space-y-3">

                    <!-- Add Lesson button (admin) -->
                    <button
                        v-if="isPrivileged"
                        @click="showAddLesson = true"
                        class="w-full flex items-center justify-center gap-2 border-2 border-dashed border-gray-300 hover:border-[#EF233C] text-gray-500 hover:text-[#EF233C] text-sm font-medium py-3 rounded-xl transition-colors"
                    >
                        <PlusIcon class="w-4 h-4" /> Add Lesson
                    </button>

                    <!-- Curriculum card -->
                    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                        <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-widest">Curriculum</p>
                            <span class="text-xs text-gray-400">{{ completedCount }} / {{ curriculum.length }}</span>
                        </div>
                        <div class="divide-y divide-gray-50 max-h-[60vh] overflow-y-auto">
                            <Link
                                v-for="(item, i) in curriculum"
                                :key="item.id"
                                :href="route('training.watch', [module.id, item.id])"
                                :class="[
                                    'flex items-start gap-3 px-4 py-3 hover:bg-gray-50 transition-colors',
                                    item.id === lesson.id ? 'bg-[#EF233C]/5 border-l-2 border-[#EF233C]' : '',
                                    !item.is_published && isPrivileged ? 'opacity-60' : '',
                                ]"
                            >
                                <div class="flex-shrink-0 mt-0.5">
                                    <div v-if="item.completed" class="w-5 h-5 rounded-full bg-green-100 flex items-center justify-center">
                                        <CheckIcon class="w-3 h-3 text-green-600" />
                                    </div>
                                    <div v-else :class="['w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold', item.id === lesson.id ? 'bg-[#EF233C] text-white' : 'bg-gray-100 text-gray-500']">
                                        {{ i + 1 }}
                                    </div>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p :class="['text-xs font-medium leading-snug', item.id === lesson.id ? 'text-[#EF233C]' : 'text-gray-700']">{{ item.title }}</p>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <span v-if="item.duration_label" class="text-[10px] text-gray-400">{{ item.duration_label }}</span>
                                        <span v-if="!item.has_video" class="text-[10px] text-amber-500">No video</span>
                                        <span v-if="!item.is_published && isPrivileged" class="text-[10px] text-amber-500">Draft</span>
                                    </div>
                                </div>
                            </Link>
                        </div>
                    </div>

                    <!-- Certificate CTA (all lessons complete) -->
                    <Link
                        v-if="curriculum.length > 0 && completedCount === curriculum.length"
                        :href="route('training.certificate', module.id)"
                        class="mt-3 flex items-center gap-3 px-4 py-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 hover:bg-emerald-100 transition-colors"
                    >
                        <AcademicCapIcon class="w-6 h-6 flex-shrink-0" />
                        <div class="min-w-0">
                            <p class="text-sm font-semibold">Module complete</p>
                            <p class="text-xs text-emerald-600">View your certificate</p>
                        </div>
                    </Link>
                </div>

            </div>
        </div>

        <!-- Add / Edit Lesson modal -->
        <BaseModal :open="showAddLesson" max-width="sm:max-w-lg" @close="closeLesson">
            <div class="flex items-center gap-3 p-6 border-b border-gray-100">
                <div class="w-10 h-10 rounded-full bg-[#EF233C]/10 flex items-center justify-center flex-shrink-0">
                    <VideoCameraIcon class="w-5 h-5 text-[#EF233C]" />
                </div>
                <h2 class="text-base font-semibold text-gray-800">{{ editingLesson ? 'Edit Lesson' : 'Add Lesson' }}</h2>
            </div>

            <div class="p-6 space-y-4">
                <!-- Title -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Title</label>
                    <input v-model="lessonForm.title" type="text" class="w-full rounded-lg border-gray-200 text-sm focus:ring-[#EF233C] focus:border-[#EF233C]" placeholder="e.g. Introduction to Manual Handling" />
                    <p v-if="lessonForm.errors.title" class="mt-1 text-xs text-red-600">{{ lessonForm.errors.title }}</p>
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Description <span class="text-gray-400 font-normal">(optional)</span></label>
                    <textarea v-model="lessonForm.description" rows="2" class="w-full rounded-lg border-gray-200 text-sm focus:ring-[#EF233C] focus:border-[#EF233C]" />
                </div>

                <!-- Video upload -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Video</label>

                    <!-- Existing video indicator (edit mode) -->
                    <div v-if="editingLesson && lesson.has_video && !lessonForm.video && !lessonForm.remove_video" class="flex items-center gap-2 p-3 bg-green-50 border border-green-200 rounded-lg mb-2">
                        <CheckCircleIcon class="w-4 h-4 text-green-600 flex-shrink-0" />
                        <span class="text-xs text-green-700 font-medium flex-1">Video uploaded</span>
                        <button type="button" @click="lessonForm.remove_video = true" class="text-xs text-red-500 hover:underline">Remove</button>
                        <label for="video-upload" class="text-xs text-[#EF233C] hover:underline cursor-pointer">Replace</label>
                    </div>

                    <!-- Remove confirmation -->
                    <div v-if="lessonForm.remove_video" class="flex items-center gap-2 p-3 bg-red-50 border border-red-200 rounded-lg mb-2">
                        <span class="text-xs text-red-700 flex-1">Video will be removed on save.</span>
                        <button type="button" @click="lessonForm.remove_video = false" class="text-xs text-gray-500 hover:underline">Undo</button>
                    </div>

                    <!-- Selected new file indicator -->
                    <div v-if="lessonForm.video" class="flex items-center gap-2 p-3 bg-blue-50 border border-blue-200 rounded-lg mb-2">
                        <ArrowUpTrayIcon class="w-4 h-4 text-blue-600 flex-shrink-0" />
                        <span class="text-xs text-blue-700 font-medium flex-1 truncate">{{ lessonForm.video.name }}</span>
                        <button type="button" @click="clearVideoSelection" class="text-xs text-gray-500 hover:underline">Clear</button>
                    </div>

                    <!-- Upload progress bar -->
                    <div v-if="lessonForm.processing && lessonForm.progress" class="p-3 bg-gray-50 border border-gray-200 rounded-lg mb-2">
                        <div class="flex items-center justify-between text-xs text-gray-600 mb-1">
                            <span>Uploading…</span>
                            <span>{{ lessonForm.progress.percentage ?? 0 }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-1.5">
                            <div class="bg-[#EF233C] h-1.5 rounded-full transition-all" :style="{ width: (lessonForm.progress.percentage ?? 0) + '%' }" />
                        </div>
                    </div>

                    <!-- File input drop zone -->
                    <label
                        v-if="!lessonForm.video && !(editingLesson && lesson.has_video && !lessonForm.remove_video)"
                        for="video-upload"
                        class="flex flex-col items-center justify-center gap-2 border-2 border-dashed border-gray-300 hover:border-[#EF233C] hover:bg-gray-50 rounded-xl py-6 px-4 cursor-pointer transition-colors"
                    >
                        <ArrowUpTrayIcon class="w-7 h-7 text-gray-400" />
                        <p class="text-sm text-gray-500">Click to select a video file</p>
                        <p class="text-xs text-gray-400">MP4, MOV, WebM, MKV</p>
                    </label>

                    <input id="video-upload" type="file" accept="video/*" class="hidden" @change="onVideoSelected" />
                    <p v-if="lessonForm.errors.video" class="mt-1 text-xs text-red-600">{{ lessonForm.errors.video }}</p>
                </div>

                <!-- Duration -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Duration <span class="text-gray-400 font-normal">(seconds, optional)</span></label>
                    <input v-model.number="lessonForm.duration_seconds" type="number" min="0" class="w-full rounded-lg border-gray-200 text-sm focus:ring-[#EF233C] focus:border-[#EF233C]" placeholder="e.g. 300 for 5 minutes" />
                </div>
            </div>

            <div class="flex justify-end gap-2 px-6 pb-6">
                <button type="button" @click="closeLesson" class="text-sm text-gray-600 px-4 py-2 rounded-lg hover:bg-gray-100 transition-colors">Cancel</button>
                <button
                    @click="submitLesson"
                    :disabled="lessonForm.processing"
                    class="text-sm font-semibold bg-[#EF233C] hover:bg-[#D90429] disabled:opacity-50 text-white px-5 py-2 rounded-lg transition-colors"
                >
                    {{ editingLesson ? 'Save Changes' : 'Add Lesson' }}
                </button>
            </div>
        </BaseModal>

        <!-- Delete lesson confirm -->
        <ConfirmModal
            :open="showDeleteLesson"
            title="Delete Lesson"
            message="The video file will also be permanently deleted from storage."
            confirm-label="Delete"
            :danger="true"
            @confirm="doDeleteLesson"
            @cancel="showDeleteLesson = false"
        />

    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BaseModal from '@/Components/BaseModal.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import {
    CheckCircleIcon, CheckIcon, PencilIcon, TrashIcon, PlusIcon,
    EyeIcon, EyeSlashIcon, VideoCameraIcon, VideoCameraSlashIcon,
    ArrowUpTrayIcon, AcademicCapIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    module:       { type: Object,  required: true },
    lesson:       { type: Object,  required: true },
    curriculum:   { type: Array,   default: () => [] },
    isCompleted:  { type: Boolean, default: false },
    isPrivileged: { type: Boolean, default: false },
});

const completedCount = computed(() => props.curriculum.filter(l => l.completed).length);

// ── Mark complete ─────────────────────────────────────────────────────────────

const isCompleted = ref(props.isCompleted);

function toggleComplete() {
    if (!props.lesson) return;
    isCompleted.value = !isCompleted.value;
    router.post(route('training.progress', props.lesson.id), {
        completed: isCompleted.value,
    }, { preserveScroll: true });
}

// ── Publish lesson ────────────────────────────────────────────────────────────

function togglePublishLesson() {
    router.post(route('training.lessons.toggle', props.lesson.id), {}, { preserveScroll: true });
}

// ── Delete lesson ─────────────────────────────────────────────────────────────

const showDeleteLesson = ref(false);

function confirmDeleteLesson() { showDeleteLesson.value = true; }

function doDeleteLesson() {
    showDeleteLesson.value = false;
    router.delete(route('training.lessons.destroy', props.lesson.id));
}

// ── Add / Edit lesson modal ───────────────────────────────────────────────────

const showAddLesson  = ref(false);
const editingLesson  = ref(false);
const videoInputRef  = ref(null);

const lessonForm = useForm({
    title:            '',
    description:      '',
    video:            null,
    duration_seconds: 0,
    remove_video:     false,
});

function openEditLesson() {
    editingLesson.value           = true;
    lessonForm.title              = props.lesson.title;
    lessonForm.description        = props.lesson.description ?? '';
    lessonForm.duration_seconds   = props.lesson.duration_seconds ?? 0;
    lessonForm.video              = null;
    lessonForm.remove_video       = false;
    showAddLesson.value           = true;
}

function closeLesson() {
    showAddLesson.value = false;
    editingLesson.value = false;
    lessonForm.reset();
}

function onVideoSelected(e) {
    lessonForm.video        = e.target.files[0] ?? null;
    lessonForm.remove_video = false;
}

function clearVideoSelection() {
    lessonForm.video = null;
    const input = document.getElementById('video-upload');
    if (input) input.value = '';
}

function submitLesson() {
    if (editingLesson.value) {
        lessonForm.patch(route('training.lessons.update', props.lesson.id), {
            onSuccess: () => closeLesson(),
        });
    } else {
        lessonForm.post(route('training.lessons.store', props.module.id), {
            onSuccess: () => closeLesson(),
        });
    }
}
</script>
