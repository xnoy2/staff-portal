<template>
    <AppLayout title="Newsfeed">
        <div class="max-w-xl mx-auto space-y-4">

            <!-- ── Composer ──────────────────────────────────────────────── -->
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <!-- Collapsed state -->
                <div v-if="!composerOpen" class="flex items-center gap-3">
                    <img :src="me.avatar_url" :alt="me.name" class="w-10 h-10 rounded-full object-cover flex-shrink-0" />
                    <button
                        @click="openComposer"
                        class="flex-1 text-left bg-gray-100 hover:bg-gray-200 transition-colors rounded-full px-4 py-2.5 text-sm text-gray-400"
                    >
                        What's on your mind, {{ firstName }}?
                    </button>
                </div>

                <!-- Expanded composer -->
                <div v-else>
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2.5">
                            <img :src="me.avatar_url" :alt="me.name" class="w-9 h-9 rounded-full object-cover" />
                            <p class="text-sm font-semibold text-gray-800">{{ me.name }}</p>
                        </div>
                        <button @click="composerOpen = false" class="p-1.5 rounded-full text-gray-400 hover:bg-gray-100">
                            <XMarkIcon class="w-5 h-5" />
                        </button>
                    </div>

                    <!-- Type tabs -->
                    <div class="flex items-center gap-1 bg-gray-100 rounded-xl p-1 mb-3">
                        <button
                            v-for="t in availableTypes"
                            :key="t.value"
                            @click="form.type = t.value"
                            :class="[
                                'flex-1 flex items-center justify-center gap-1 text-xs font-semibold px-2 py-1.5 rounded-lg transition-colors',
                                form.type === t.value ? 'bg-white text-gray-800 shadow-sm' : 'text-gray-500 hover:text-gray-700',
                            ]"
                        >
                            <component :is="t.icon" class="w-3.5 h-3.5" />
                            {{ t.label }}
                        </button>
                    </div>

                    <!-- Title (blog / event / recognition) -->
                    <input
                        v-if="form.type !== 'general'"
                        v-model="form.title"
                        type="text"
                        :placeholder="titlePlaceholder"
                        class="w-full text-sm font-semibold border border-gray-200 rounded-xl px-3 py-2.5 mb-2 focus:outline-none focus:ring-2 focus:ring-[#EF233C]/20 focus:border-[#EF233C]/40"
                    />

                    <!-- Recognition: staff picker -->
                    <div v-if="form.type === 'recognition'" class="mb-2">
                        <select
                            v-model="form.recognized_user_id"
                            class="w-full text-sm border-gray-200 rounded-xl focus:ring-[#EF233C] focus:border-[#EF233C]"
                        >
                            <option value="">Who are you recognising?</option>
                            <option v-for="s in staffList" :key="s.id" :value="s.id">{{ s.name }}</option>
                        </select>
                    </div>

                    <!-- Event: date + location -->
                    <div v-if="form.type === 'event'" class="grid grid-cols-2 gap-2 mb-2">
                        <input
                            v-model="form.event_date"
                            type="date"
                            class="text-sm border-gray-200 rounded-xl focus:ring-[#EF233C] focus:border-[#EF233C]"
                        />
                        <input
                            v-model="form.event_location"
                            type="text"
                            placeholder="Location (optional)"
                            class="text-sm border-gray-200 rounded-xl focus:ring-[#EF233C] focus:border-[#EF233C]"
                        />
                    </div>

                    <!-- Body -->
                    <textarea
                        ref="bodyInput"
                        v-model="form.body"
                        :rows="form.type === 'blog' ? 8 : 4"
                        :placeholder="bodyPlaceholder"
                        class="w-full text-sm border border-gray-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#EF233C]/20 focus:border-[#EF233C]/40 resize-none"
                    />

                    <!-- Image previews -->
                    <div v-if="form.images.length || pendingUploads.length" class="grid grid-cols-3 gap-2 mt-2">
                        <div v-for="(img, i) in form.images" :key="img" class="relative group">
                            <img :src="previewMap[img] ?? img" class="w-full h-24 object-cover rounded-lg" />
                            <button
                                @click="removeImage(i)"
                                class="absolute top-1 right-1 bg-black/60 text-white rounded-full p-1 opacity-0 group-hover:opacity-100 transition-opacity"
                            >
                                <XMarkIcon class="w-3 h-3" />
                            </button>
                        </div>
                        <!-- Uploading previews with spinner -->
                        <div v-for="p in pendingUploads" :key="p.id" class="relative">
                            <img :src="p.preview" class="w-full h-24 object-cover rounded-lg opacity-50" />
                            <div class="absolute inset-0 flex items-center justify-center bg-black/20 rounded-lg">
                                <svg class="w-6 h-6 text-white animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                    <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="flex items-center justify-between mt-3 pt-3 border-t border-gray-100">
                        <button
                            @click="$refs.imageInput.click()"
                            :disabled="uploading || form.images.length >= 6"
                            class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-500 hover:text-gray-700 hover:bg-gray-50 px-3 py-2 rounded-lg transition-colors disabled:opacity-50"
                        >
                            <PhotoIcon class="w-4 h-4 text-green-500" />
                            {{ uploading ? 'Uploading…' : 'Photo' }}
                        </button>
                        <input ref="imageInput" type="file" accept="image/*" multiple class="hidden" @change="uploadImages" />

                        <button
                            @click="submitPost"
                            :disabled="!canPost || posting || uploading"
                            class="bg-[#EF233C] hover:bg-[#D90429] disabled:opacity-50 disabled:cursor-not-allowed text-white text-sm font-semibold px-6 py-2 rounded-xl transition-colors"
                        >
                            {{ posting ? 'Posting…' : 'Post' }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- ── Feed ──────────────────────────────────────────────────── -->
            <div v-if="posts.data.length === 0" class="bg-white rounded-xl border border-gray-200 py-16 text-center">
                <NewspaperIcon class="w-10 h-10 text-gray-300 mx-auto mb-3" />
                <p class="text-sm text-gray-400">No posts yet. Be the first to share something!</p>
            </div>

            <PostCard
                v-for="post in posts.data"
                :key="post.id"
                :post="post"
                :isPrivileged="isPrivileged"
            />

            <!-- Pagination -->
            <div v-if="posts.last_page > 1" class="flex items-center justify-center gap-2 pb-6">
                <Link
                    v-if="posts.prev_page_url"
                    :href="posts.prev_page_url"
                    preserve-scroll
                    class="text-xs font-medium bg-white border border-gray-200 px-4 py-2 rounded-lg hover:bg-gray-50 transition-colors text-gray-600"
                >← Newer</Link>
                <span class="text-xs text-gray-400">Page {{ posts.current_page }} of {{ posts.last_page }}</span>
                <Link
                    v-if="posts.next_page_url"
                    :href="posts.next_page_url"
                    preserve-scroll
                    class="text-xs font-medium bg-white border border-gray-200 px-4 py-2 rounded-lg hover:bg-gray-50 transition-colors text-gray-600"
                >Older →</Link>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, nextTick } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';
import PostCard from '@/Components/Feed/PostCard.vue';
import {
    XMarkIcon, PhotoIcon, NewspaperIcon,
    ChatBubbleBottomCenterTextIcon, DocumentTextIcon, CalendarDaysIcon, TrophyIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    posts:        { type: Object, required: true },
    staffList:    { type: Array,  default: () => [] },
    isPrivileged: { type: Boolean, default: false },
});

const page = usePage();
const me   = computed(() => page.props.auth.user);
const firstName = computed(() => me.value.name.split(' ')[0]);

// ── Composer ─────────────────────────────────────────────────────────────────

const composerOpen = ref(false);
const bodyInput    = ref(null);
const posting      = ref(false);
const uploading    = ref(false);

const form = ref({
    type: 'general',
    title: '',
    body: '',
    images: [],
    event_date: '',
    event_location: '',
    recognized_user_id: '',
});

const postTypes = [
    { value: 'general',     label: 'Post',        icon: ChatBubbleBottomCenterTextIcon },
    { value: 'blog',        label: 'Blog',        icon: DocumentTextIcon },
    { value: 'event',       label: 'Event',       icon: CalendarDaysIcon },
    { value: 'recognition', label: 'Recognition', icon: TrophyIcon, privileged: true },
];

const availableTypes = computed(() =>
    postTypes.filter(t => !t.privileged || props.isPrivileged)
);

const titlePlaceholder = computed(() => ({
    blog:        'Blog title',
    event:       'Event name',
    recognition: 'e.g. Employee of the Month — June',
}[form.value.type] ?? ''));

const bodyPlaceholder = computed(() => ({
    general:     `What's on your mind, ${firstName.value}?`,
    blog:        'Write your blog post…',
    event:       'Tell everyone about the event…',
    recognition: 'Why are they being recognised?',
}[form.value.type] ?? ''));

const canPost = computed(() => {
    if (!form.value.body.trim()) return false;
    if (form.value.type === 'event' && !form.value.event_date) return false;
    if (form.value.type === 'recognition' && !form.value.recognized_user_id) return false;
    return true;
});

function openComposer() {
    composerOpen.value = true;
    nextTick(() => bodyInput.value?.focus());
}

const pendingUploads = ref([]);
// Server URL → local object URL, so previews stay instant after upload
// (the server URL proxy-streams from R2 — no need to re-download what we already have)
const previewMap = ref({});
let uploadSeq = 0;

async function uploadImages(e) {
    const slotsLeft = 6 - form.value.images.length - pendingUploads.value.length;
    const files = Array.from(e.target.files).slice(0, Math.max(0, slotsLeft));
    e.target.value = '';
    if (!files.length) return;

    // Show local previews immediately with a spinner overlay
    const queued = files.map(file => ({
        id:      ++uploadSeq,
        file,
        preview: URL.createObjectURL(file),
    }));
    pendingUploads.value.push(...queued);

    uploading.value = true;
    try {
        for (const item of queued) {
            const fd = new FormData();
            fd.append('file', item.file);
            try {
                const { data } = await axios.post(route('feed.upload'), fd);
                previewMap.value[data.url] = item.preview;
                form.value.images.push(data.url);
            } catch (err) {
                URL.revokeObjectURL(item.preview);
                alert(err.response?.data?.message ?? `Upload failed: ${item.file.name}`);
            } finally {
                pendingUploads.value = pendingUploads.value.filter(p => p.id !== item.id);
            }
        }
    } finally {
        uploading.value = false;
    }
}

function removeImage(i) {
    const url = form.value.images[i];
    if (previewMap.value[url]) {
        URL.revokeObjectURL(previewMap.value[url]);
        delete previewMap.value[url];
    }
    form.value.images.splice(i, 1);
}

function clearPreviews() {
    Object.values(previewMap.value).forEach(URL.revokeObjectURL);
    previewMap.value = {};
}

function submitPost() {
    if (!canPost.value || posting.value) return;
    posting.value = true;

    router.post(route('feed.store'), {
        type:               form.value.type,
        title:              form.value.title || null,
        body:               form.value.body,
        images:             form.value.images,
        event_date:         form.value.event_date || null,
        event_location:     form.value.event_location || null,
        recognized_user_id: form.value.recognized_user_id || null,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            composerOpen.value = false;
            clearPreviews();
            form.value = { type: 'general', title: '', body: '', images: [], event_date: '', event_location: '', recognized_user_id: '' };
        },
        onFinish: () => { posting.value = false; },
    });
}
</script>
