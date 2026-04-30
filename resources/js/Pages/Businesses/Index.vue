<template>
    <AppLayout title="Businesses">
        <div class="max-w-3xl mx-auto space-y-5">

            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-lg font-semibold text-gray-800">Businesses</h1>
                    <p class="text-xs text-gray-500 mt-0.5">Manage the business units used across projects.</p>
                </div>
                <button
                    @click="openCreate"
                    class="bg-[#EF233C] hover:bg-[#D90429] text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors flex items-center gap-1.5"
                >
                    <PlusIcon class="w-4 h-4" /> Add Business
                </button>
            </div>

            <!-- Flash -->
            <div v-if="$page.props.flash?.success" class="bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm rounded-lg px-4 py-2.5">
                {{ $page.props.flash.success }}
            </div>
            <div v-if="$page.props.errors?.delete" class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-2.5">
                {{ $page.props.errors.delete }}
            </div>

            <!-- List -->
            <div class="bg-white rounded-xl border border-gray-200 divide-y divide-gray-100">
                <div v-if="!businesses.length" class="py-10 text-center text-sm text-gray-400">
                    No businesses yet. Add one to get started.
                </div>
                <div
                    v-for="b in businesses"
                    :key="b.id"
                    class="flex items-center gap-4 px-5 py-4"
                >
                    <!-- Color swatch -->
                    <div
                        class="w-9 h-9 rounded-lg flex-shrink-0 flex items-center justify-center"
                        :style="{ backgroundColor: b.color }"
                    >
                        <span class="text-white text-xs font-bold uppercase">{{ b.code.slice(0,3) }}</span>
                    </div>

                    <!-- Info -->
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-gray-800 text-sm">{{ b.name }}</p>
                        <p class="text-xs text-gray-400 font-mono">{{ b.code }}</p>
                    </div>

                    <!-- Status badge -->
                    <span
                        class="text-xs font-medium px-2 py-0.5 rounded-full"
                        :class="b.is_active ? 'bg-emerald-50 text-emerald-600' : 'bg-gray-100 text-gray-400'"
                    >
                        {{ b.is_active ? 'Active' : 'Inactive' }}
                    </span>

                    <!-- Actions -->
                    <div class="flex items-center gap-2">
                        <button
                            @click="openEdit(b)"
                            class="text-xs text-gray-500 hover:text-gray-800 px-2 py-1 rounded hover:bg-gray-100 transition-colors"
                        >Edit</button>
                        <button
                            @click="toggleActive(b)"
                            class="text-xs px-2 py-1 rounded transition-colors"
                            :class="b.is_active
                                ? 'text-amber-600 hover:bg-amber-50'
                                : 'text-emerald-600 hover:bg-emerald-50'"
                        >{{ b.is_active ? 'Deactivate' : 'Activate' }}</button>
                        <button
                            @click="confirmDelete(b)"
                            class="text-xs text-red-500 hover:text-red-700 px-2 py-1 rounded hover:bg-red-50 transition-colors"
                        >Delete</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create / Edit Modal -->
        <Transition name="modal-fade">
            <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40" @click.self="closeModal">
                <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
                    <h2 class="text-base font-semibold text-gray-800 mb-4">{{ editing ? 'Edit Business' : 'New Business' }}</h2>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                            <input
                                v-model="form.name"
                                type="text"
                                class="w-full rounded-lg border-gray-200 text-sm focus:ring-[#EF233C] focus:border-[#EF233C]"
                                placeholder="e.g. BCF Climbing Frames"
                            />
                            <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Code <span class="text-gray-400 font-normal">(short slug, e.g. bcf)</span></label>
                            <input
                                v-model="form.code"
                                type="text"
                                class="w-full rounded-lg border-gray-200 text-sm font-mono focus:ring-[#EF233C] focus:border-[#EF233C]"
                                placeholder="e.g. bcf"
                                :disabled="!!editing"
                                :class="editing ? 'bg-gray-50 text-gray-400 cursor-not-allowed' : ''"
                            />
                            <p v-if="form.errors.code" class="mt-1 text-xs text-red-600">{{ form.errors.code }}</p>
                            <p v-if="editing" class="mt-1 text-xs text-gray-400">Code cannot be changed after creation.</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Brand Color</label>
                            <div class="flex items-center gap-3">
                                <input
                                    v-model="form.color"
                                    type="color"
                                    class="w-12 h-10 rounded-lg border border-gray-200 cursor-pointer p-0.5"
                                />
                                <input
                                    v-model="form.color"
                                    type="text"
                                    class="flex-1 rounded-lg border-gray-200 text-sm font-mono focus:ring-[#EF233C] focus:border-[#EF233C]"
                                    placeholder="#EF233C"
                                />
                            </div>
                            <p v-if="form.errors.color" class="mt-1 text-xs text-red-600">{{ form.errors.color }}</p>
                        </div>
                    </div>

                    <div class="flex gap-3 mt-6">
                        <button
                            @click="closeModal"
                            class="flex-1 px-4 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors"
                        >Cancel</button>
                        <button
                            @click="submit"
                            :disabled="form.processing"
                            class="flex-1 px-4 py-2 text-sm font-medium text-white bg-[#EF233C] hover:bg-[#D90429] rounded-lg transition-colors disabled:opacity-60"
                        >{{ form.processing ? 'Saving…' : (editing ? 'Save Changes' : 'Create') }}</button>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- Delete confirm -->
        <Transition name="modal-fade">
            <div v-if="deleteTarget" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40" @click.self="deleteTarget = null">
                <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6">
                    <h2 class="text-base font-semibold text-gray-800 mb-2">Delete "{{ deleteTarget.name }}"?</h2>
                    <p class="text-sm text-gray-500 mb-5">This cannot be undone. Projects assigned to this business must be reassigned first.</p>
                    <div class="flex gap-3">
                        <button @click="deleteTarget = null" class="flex-1 px-4 py-2 text-sm border border-gray-200 rounded-lg hover:bg-gray-50">Cancel</button>
                        <button @click="doDelete" class="flex-1 px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors">Delete</button>
                    </div>
                </div>
            </div>
        </Transition>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { PlusIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    businesses: { type: Array, default: () => [] },
});

const showModal   = ref(false);
const editing     = ref(null);
const deleteTarget = ref(null);

const form = useForm({ name: '', code: '', color: '#EF233C' });

function openCreate() {
    editing.value = null;
    form.reset();
    form.color = '#EF233C';
    showModal.value = true;
}

function openEdit(b) {
    editing.value = b;
    form.name  = b.name;
    form.code  = b.code;
    form.color = b.color;
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
    form.clearErrors();
}

function submit() {
    if (editing.value) {
        form.put(route('businesses.update', editing.value.id), {
            onSuccess: closeModal,
        });
    } else {
        form.post(route('businesses.store'), {
            onSuccess: closeModal,
        });
    }
}

function toggleActive(b) {
    router.post(route('businesses.toggle-active', b.id), {}, { preserveScroll: true });
}

function confirmDelete(b) {
    deleteTarget.value = b;
}

function doDelete() {
    router.delete(route('businesses.destroy', deleteTarget.value.id), {
        preserveScroll: true,
        onSuccess: () => { deleteTarget.value = null; },
    });
}
</script>

<style scoped>
.modal-fade-enter-active, .modal-fade-leave-active { transition: opacity 0.15s; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; }
</style>
