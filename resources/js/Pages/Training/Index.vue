<template>
    <AppLayout title="Training">
        <div class="max-w-6xl mx-auto space-y-6">

            <!-- Header -->
            <div class="flex items-center justify-between gap-4 flex-wrap">
                <div>
                    <h1 class="text-lg font-semibold text-gray-800">Training</h1>
                    <p class="text-xs text-gray-500 mt-0.5">{{ modules.length }} module{{ modules.length !== 1 ? 's' : '' }} available</p>
                </div>
                <button
                    v-if="isPrivileged"
                    @click="showAddModule = true"
                    class="flex items-center gap-2 bg-[#EF233C] hover:bg-[#D90429] text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors"
                >
                    <PlusIcon class="w-4 h-4" /> Add Module
                </button>
            </div>

            <!-- Empty state -->
            <div v-if="modules.length === 0" class="bg-white rounded-xl border border-dashed border-gray-300 py-20 text-center">
                <AcademicCapIcon class="w-12 h-12 text-gray-300 mx-auto mb-3" />
                <p class="text-gray-600 font-medium">No training modules yet</p>
                <p v-if="isPrivileged" class="text-sm text-gray-400 mt-1">Click "Add Module" to create your first training module.</p>
                <p v-else class="text-sm text-gray-400 mt-1">Check back soon — training content is being prepared.</p>
            </div>

            <!-- Module grid -->
            <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                <div
                    v-for="mod in modules"
                    :key="mod.id"
                    class="bg-white rounded-xl border border-gray-200 overflow-hidden flex flex-col group hover:shadow-md transition-shadow"
                >
                    <!-- Thumbnail / placeholder -->
                    <div class="relative h-36 bg-gradient-to-br from-[#2B2D42] to-[#8D99AE] overflow-hidden">
                        <img v-if="mod.thumbnail" :src="mod.thumbnail" class="w-full h-full object-cover" />
                        <div v-else class="absolute inset-0 flex items-center justify-center">
                            <AcademicCapIcon class="w-14 h-14 text-white/20" />
                        </div>
                        <!-- Draft badge -->
                        <span v-if="!mod.is_published && isPrivileged" class="absolute top-2 left-2 bg-amber-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wide">
                            Draft
                        </span>
                        <!-- Lesson count badge -->
                        <span class="absolute bottom-2 right-2 bg-black/50 text-white text-xs px-2 py-0.5 rounded-full">
                            {{ mod.lesson_count }} lesson{{ mod.lesson_count !== 1 ? 's' : '' }}
                        </span>
                    </div>

                    <!-- Card body -->
                    <div class="p-4 flex-1 flex flex-col">
                        <h3 class="text-sm font-semibold text-gray-800 leading-snug mb-1">{{ mod.title }}</h3>
                        <p v-if="mod.description" class="text-xs text-gray-500 line-clamp-2 flex-1">{{ mod.description }}</p>
                        <div v-else class="flex-1" />

                        <!-- Progress bar -->
                        <div v-if="mod.lesson_count > 0" class="mt-3">
                            <div class="flex items-center justify-between text-[10px] text-gray-400 mb-1">
                                <span>{{ mod.completed }} / {{ mod.lesson_count }} completed</span>
                                <span>{{ Math.round((mod.completed / mod.lesson_count) * 100) }}%</span>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-1.5">
                                <div
                                    class="h-1.5 rounded-full bg-[#EF233C] transition-all"
                                    :style="{ width: (mod.lesson_count ? (mod.completed / mod.lesson_count) * 100 : 0) + '%' }"
                                />
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="mt-4 flex items-center gap-2">
                            <Link
                                v-if="mod.first_lesson"
                                :href="route('training.watch', [mod.id, mod.first_lesson])"
                                class="flex-1 text-center text-xs font-semibold bg-[#EF233C] hover:bg-[#D90429] text-white py-2 rounded-lg transition-colors"
                            >
                                {{ mod.completed > 0 ? 'Continue' : 'Start' }}
                            </Link>
                            <Link
                                v-else-if="isPrivileged"
                                :href="route('training.module', mod.id)"
                                class="flex-1 text-center text-xs font-semibold bg-gray-100 hover:bg-gray-200 text-gray-700 py-2 rounded-lg transition-colors"
                            >
                                Manage
                            </Link>
                            <span v-else class="flex-1 text-center text-xs text-gray-400 py-2">No lessons yet</span>

                            <!-- Admin controls -->
                            <template v-if="isPrivileged">
                                <button @click="openEdit(mod)" class="p-2 text-gray-400 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors" title="Edit">
                                    <PencilIcon class="w-3.5 h-3.5" />
                                </button>
                                <button @click="togglePublish(mod)" :class="['p-2 rounded-lg transition-colors', mod.is_published ? 'text-green-600 hover:bg-green-50' : 'text-gray-400 hover:bg-gray-100']" :title="mod.is_published ? 'Unpublish' : 'Publish'">
                                    <EyeIcon v-if="mod.is_published" class="w-3.5 h-3.5" />
                                    <EyeSlashIcon v-else class="w-3.5 h-3.5" />
                                </button>
                                <button @click="confirmDelete(mod)" class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors" title="Delete">
                                    <TrashIcon class="w-3.5 h-3.5" />
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Add Module modal -->
        <BaseModal :open="showAddModule" @close="closeAddModal">
            <div class="flex items-center gap-3 p-6 border-b border-gray-100">
                <div class="w-10 h-10 rounded-full bg-[#EF233C]/10 flex items-center justify-center flex-shrink-0">
                    <AcademicCapIcon class="w-5 h-5 text-[#EF233C]" />
                </div>
                <h2 class="text-base font-semibold text-gray-800">{{ editingModule ? 'Edit Module' : 'New Training Module' }}</h2>
            </div>
            <form @submit.prevent="submitModule" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Title</label>
                    <input v-model="moduleForm.title" type="text" class="w-full rounded-lg border-gray-200 text-sm focus:ring-[#EF233C] focus:border-[#EF233C]" placeholder="e.g. Health & Safety" />
                    <p v-if="moduleForm.errors.title" class="mt-1 text-xs text-red-600">{{ moduleForm.errors.title }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Description <span class="text-gray-400 font-normal">(optional)</span></label>
                    <textarea v-model="moduleForm.description" rows="3" class="w-full rounded-lg border-gray-200 text-sm focus:ring-[#EF233C] focus:border-[#EF233C]" placeholder="What will staff learn in this module?" />
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="closeAddModal" class="text-sm text-gray-600 px-4 py-2 rounded-lg hover:bg-gray-100 transition-colors">Cancel</button>
                    <button type="submit" :disabled="moduleForm.processing" class="text-sm font-semibold bg-[#EF233C] hover:bg-[#D90429] disabled:opacity-50 text-white px-5 py-2 rounded-lg transition-colors">
                        {{ editingModule ? 'Save Changes' : 'Create Module' }}
                    </button>
                </div>
            </form>
        </BaseModal>

        <!-- Delete confirm modal -->
        <ConfirmModal
            :open="!!deleteTarget"
            title="Delete Module"
            message="All lessons and videos in this module will be permanently deleted."
            confirm-label="Delete"
            :danger="true"
            @confirm="doDelete"
            @cancel="deleteTarget = null"
        />

    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BaseModal from '@/Components/BaseModal.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import {
    AcademicCapIcon, PlusIcon, PencilIcon, TrashIcon,
    EyeIcon, EyeSlashIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    modules:      { type: Array,   default: () => [] },
    isPrivileged: { type: Boolean, default: false },
});

// ── Add / Edit module modal ───────────────────────────────────────────────────

const showAddModule = ref(false);
const editingModule = ref(null);

const moduleForm = useForm({ title: '', description: '' });

function openEdit(mod) {
    editingModule.value = mod;
    moduleForm.title       = mod.title;
    moduleForm.description = mod.description ?? '';
    showAddModule.value = true;
}

function closeAddModal() {
    showAddModule.value  = false;
    editingModule.value  = null;
    moduleForm.reset();
}

function submitModule() {
    if (editingModule.value) {
        moduleForm.patch(route('training.modules.update', editingModule.value.id), {
            onSuccess: () => closeAddModal(),
        });
    } else {
        moduleForm.post(route('training.modules.store'), {
            onSuccess: () => closeAddModal(),
        });
    }
}

// ── Publish toggle ────────────────────────────────────────────────────────────

function togglePublish(mod) {
    router.post(route('training.modules.toggle', mod.id), {}, { preserveScroll: true });
}

// ── Delete ────────────────────────────────────────────────────────────────────

const deleteTarget = ref(null);

function confirmDelete(mod) {
    deleteTarget.value = mod;
}

function doDelete() {
    router.delete(route('training.modules.destroy', deleteTarget.value.id), {
        onFinish: () => { deleteTarget.value = null; },
    });
}
</script>
