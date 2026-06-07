<template>
    <div class="space-y-5">
        <!-- Photo Upload -->
        <div class="bg-white rounded-xl border border-gray-200 p-5 sm:p-6">
            <h2 class="text-sm font-semibold text-gray-700 mb-4 flex items-center gap-2">
                <CameraIcon class="w-4 h-4 text-[#EF233C]" /> Van Photo
            </h2>

            <div class="flex items-start gap-5">
                <!-- Preview -->
                <div class="flex-shrink-0 w-28 h-28 rounded-xl overflow-hidden bg-gray-100 border border-gray-200 flex items-center justify-center">
                    <img v-if="previewUrl" :src="previewUrl" class="w-full h-full object-cover" alt="Van photo" />
                    <TruckIcon v-else class="w-10 h-10 text-gray-300" />
                </div>

                <!-- Controls -->
                <div class="flex-1 space-y-2">
                    <label
                        for="van-photo-input"
                        class="inline-flex items-center gap-2 cursor-pointer text-sm font-medium px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors text-gray-700"
                    >
                        <ArrowUpTrayIcon class="w-4 h-4" />
                        {{ previewUrl ? 'Change Photo' : 'Upload Photo' }}
                    </label>
                    <input
                        id="van-photo-input"
                        type="file"
                        accept="image/*"
                        class="sr-only"
                        @change="onPhotoChange"
                    />
                    <p class="text-xs text-gray-400">JPEG, PNG, WEBP — max 5 MB</p>
                    <p v-if="form.errors.photo" class="text-xs text-red-500">{{ form.errors.photo }}</p>

                    <button
                        v-if="previewUrl"
                        type="button"
                        @click="removePhoto"
                        class="flex items-center gap-1.5 text-xs text-red-500 hover:text-red-700 transition-colors"
                    >
                        <XMarkIcon class="w-3.5 h-3.5" /> Remove photo
                    </button>
                </div>
            </div>
        </div>

        <!-- Basic Details -->
        <div class="bg-white rounded-xl border border-gray-200 p-5 sm:p-6">
            <h2 class="text-sm font-semibold text-gray-700 mb-4 flex items-center gap-2">
                <TruckIcon class="w-4 h-4 text-[#EF233C]" /> Vehicle Details
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Registration -->
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">
                        Registration <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="form.registration"
                        type="text"
                        placeholder="e.g. AB12 CDE"
                        maxlength="20"
                        class="w-full px-3 py-2 text-sm border rounded-lg focus:ring-[#EF233C] focus:border-[#EF233C] uppercase tracking-widest font-bold"
                        :class="form.errors.registration ? 'border-red-400' : 'border-gray-300'"
                        @input="form.registration = form.registration.toUpperCase()"
                    />
                    <p v-if="form.errors.registration" class="text-xs text-red-500 mt-1">{{ form.errors.registration }}</p>
                </div>

                <!-- Make -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">
                        Make <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="form.make"
                        type="text"
                        placeholder="e.g. Ford"
                        maxlength="100"
                        class="w-full px-3 py-2 text-sm border rounded-lg focus:ring-[#EF233C] focus:border-[#EF233C]"
                        :class="form.errors.make ? 'border-red-400' : 'border-gray-300'"
                    />
                    <p v-if="form.errors.make" class="text-xs text-red-500 mt-1">{{ form.errors.make }}</p>
                </div>

                <!-- Model -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">
                        Model <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="form.model"
                        type="text"
                        placeholder="e.g. Transit"
                        maxlength="100"
                        class="w-full px-3 py-2 text-sm border rounded-lg focus:ring-[#EF233C] focus:border-[#EF233C]"
                        :class="form.errors.model ? 'border-red-400' : 'border-gray-300'"
                    />
                    <p v-if="form.errors.model" class="text-xs text-red-500 mt-1">{{ form.errors.model }}</p>
                </div>

                <!-- Year -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Year</label>
                    <input
                        v-model.number="form.year"
                        type="number"
                        placeholder="e.g. 2022"
                        :min="1990"
                        :max="currentYear + 1"
                        class="w-full px-3 py-2 text-sm border rounded-lg focus:ring-[#EF233C] focus:border-[#EF233C]"
                        :class="form.errors.year ? 'border-red-400' : 'border-gray-300'"
                    />
                    <p v-if="form.errors.year" class="text-xs text-red-500 mt-1">{{ form.errors.year }}</p>
                </div>
            </div>
        </div>

        <!-- Notes -->
        <div class="bg-white rounded-xl border border-gray-200 p-5 sm:p-6">
            <h2 class="text-sm font-semibold text-gray-700 mb-4 flex items-center gap-2">
                <ClipboardDocumentListIcon class="w-4 h-4 text-[#EF233C]" /> Notes
            </h2>
            <textarea
                v-model="form.notes"
                rows="3"
                placeholder="Any notes about this van (optional)…"
                maxlength="2000"
                class="w-full px-3 py-2 text-sm border rounded-lg focus:ring-[#EF233C] focus:border-[#EF233C] resize-none"
                :class="form.errors.notes ? 'border-red-400' : 'border-gray-300'"
            />
            <p v-if="form.errors.notes" class="text-xs text-red-500 mt-1">{{ form.errors.notes }}</p>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-end gap-3 pt-1">
            <Link :href="route('vans.index')" class="text-sm text-gray-500 hover:text-gray-700 transition-colors px-4 py-2">
                Cancel
            </Link>
            <button
                type="submit"
                :disabled="form.processing"
                class="bg-[#EF233C] hover:bg-[#D90429] disabled:opacity-60 text-white text-sm font-medium px-6 py-2 rounded-lg transition-colors flex items-center gap-2"
            >
                <span v-if="form.processing" class="w-4 h-4 border-2 border-white/40 border-t-white rounded-full animate-spin" />
                {{ submitLabel }}
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { Link } from '@inertiajs/vue3';
import { TruckIcon, ClipboardDocumentListIcon, CameraIcon, ArrowUpTrayIcon, XMarkIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    form:            { type: Object, required: true },
    submitLabel:     { type: String, default: 'Save' },
    currentPhotoUrl: { type: String, default: null },
});

const currentYear = new Date().getFullYear();

// Preview URL — initialised from existing photo, updated on new file selection
const previewUrl = ref(props.currentPhotoUrl ?? null);

function onPhotoChange(e) {
    const file = e.target.files?.[0];
    if (!file) return;
    props.form.photo = file;
    props.form.remove_photo = false;
    previewUrl.value = URL.createObjectURL(file);
}

function removePhoto() {
    props.form.photo = null;
    props.form.remove_photo = true;
    previewUrl.value = null;
}
</script>
