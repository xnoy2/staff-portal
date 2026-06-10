<template>
    <article class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <!-- Pinned ribbon -->
        <div v-if="post.is_pinned" class="bg-amber-50 border-b border-amber-100 px-4 py-1.5 flex items-center gap-1.5">
            <BookmarkIcon class="w-3.5 h-3.5 text-amber-500" />
            <span class="text-xs font-medium text-amber-600">Pinned post</span>
        </div>

        <!-- Header -->
        <div class="px-4 pt-4 flex items-start gap-3">
            <img :src="post.author.avatar_url" :alt="post.author.name" class="w-10 h-10 rounded-full object-cover flex-shrink-0" />
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <p class="text-sm font-semibold text-gray-800">{{ post.author.name }}</p>
                    <span v-if="typeBadge" :class="['text-[10px] font-bold uppercase tracking-wide px-1.5 py-0.5 rounded', typeBadge.class]">
                        {{ typeBadge.label }}
                    </span>
                </div>
                <p class="text-xs text-gray-400">{{ timeAgo(post.created_at) }}</p>
            </div>

            <!-- Kebab menu -->
            <div v-if="post.can_delete || isPrivileged" class="relative flex-shrink-0" ref="menuEl">
                <button @click="menuOpen = !menuOpen" class="p-1.5 rounded-full text-gray-400 hover:bg-gray-100 transition-colors">
                    <EllipsisHorizontalIcon class="w-5 h-5" />
                </button>
                <Transition
                    enter-active-class="transition ease-out duration-100"
                    enter-from-class="opacity-0 scale-95"
                    enter-to-class="opacity-100 scale-100"
                    leave-active-class="transition ease-in duration-75"
                    leave-from-class="opacity-100 scale-100"
                    leave-to-class="opacity-0 scale-95"
                >
                    <div v-if="menuOpen" class="absolute right-0 mt-1 w-40 bg-white rounded-xl border border-gray-200 shadow-lg py-1 z-20">
                        <button
                            v-if="isPrivileged"
                            @click="togglePin"
                            class="w-full text-left px-3 py-2 text-xs text-gray-600 hover:bg-gray-50 flex items-center gap-2"
                        >
                            <BookmarkIcon class="w-3.5 h-3.5" />
                            {{ post.is_pinned ? 'Unpin post' : 'Pin post' }}
                        </button>
                        <button
                            v-if="post.can_delete"
                            @click="deletePost"
                            class="w-full text-left px-3 py-2 text-xs text-red-500 hover:bg-red-50 flex items-center gap-2"
                        >
                            <TrashIcon class="w-3.5 h-3.5" />
                            Delete post
                        </button>
                    </div>
                </Transition>
            </div>
        </div>

        <!-- Recognition banner -->
        <div v-if="post.type === 'recognition' && post.recognized_user" class="mx-4 mt-3 rounded-xl bg-gradient-to-r from-amber-50 via-yellow-50 to-amber-50 border border-amber-200 p-4 text-center">
            <TrophyIcon class="w-7 h-7 text-amber-500 mx-auto mb-2" />
            <p v-if="post.title" class="text-xs font-bold uppercase tracking-widest text-amber-600 mb-2">{{ post.title }}</p>
            <img :src="post.recognized_user.avatar_url" :alt="post.recognized_user.name" class="w-16 h-16 rounded-full object-cover mx-auto ring-4 ring-amber-200 mb-2" />
            <p class="text-base font-bold text-gray-800">{{ post.recognized_user.name }}</p>
        </div>

        <!-- Event chip -->
        <div v-if="post.type === 'event' && post.event_date" class="mx-4 mt-3 flex items-center gap-3 rounded-xl bg-indigo-50 border border-indigo-100 px-4 py-3">
            <div class="text-center flex-shrink-0">
                <p class="text-[10px] font-bold text-indigo-400 uppercase leading-none">{{ eventMonth }}</p>
                <p class="text-xl font-black text-indigo-700 leading-tight">{{ eventDay }}</p>
            </div>
            <div class="min-w-0">
                <p v-if="post.title" class="text-sm font-semibold text-indigo-900 truncate">{{ post.title }}</p>
                <p v-if="post.event_location" class="text-xs text-indigo-500 flex items-center gap-1">
                    <MapPinIcon class="w-3 h-3" /> {{ post.event_location }}
                </p>
            </div>
        </div>

        <!-- Title (blog) -->
        <h2 v-if="post.title && post.type === 'blog'" class="px-4 mt-3 text-lg font-bold text-gray-900">{{ post.title }}</h2>

        <!-- Body -->
        <div class="px-4 mt-2">
            <p class="text-sm text-gray-700 leading-relaxed whitespace-pre-wrap break-words" :class="!expanded && isLong ? 'line-clamp-5' : ''">{{ post.body }}</p>
            <button v-if="isLong" @click="expanded = !expanded" class="text-xs font-medium text-gray-400 hover:text-gray-600 mt-1">
                {{ expanded ? 'See less' : 'See more' }}
            </button>
        </div>

        <!-- Images -->
        <div v-if="post.images.length" class="mt-3" :class="post.images.length === 1 ? '' : 'px-4 grid grid-cols-2 gap-1.5'">
            <img
                v-for="(img, i) in post.images"
                :key="i"
                :src="img"
                @click="lightbox = img"
                :class="[
                    'w-full object-cover cursor-pointer hover:opacity-95 transition-opacity',
                    post.images.length === 1 ? 'max-h-[28rem]' : 'h-48 rounded-lg',
                ]"
            />
        </div>

        <!-- Reaction summary -->
        <div v-if="post.reaction_count > 0 || post.comments.length > 0" class="px-4 pt-3 pb-2 flex items-center justify-between text-xs text-gray-400">
            <div v-if="post.reaction_count > 0" class="flex items-center gap-1" :title="reactionTooltip">
                <span class="flex -space-x-0.5">
                    <span v-for="r in topReactions" :key="r" class="text-sm leading-none">{{ reactionEmoji[r] }}</span>
                </span>
                <span class="ml-1">{{ post.reaction_count }}</span>
            </div>
            <span v-else />
            <button v-if="post.comments.length > 0" @click="commentsOpen = !commentsOpen" class="hover:text-gray-600 hover:underline">
                {{ post.comments.length }} comment{{ post.comments.length !== 1 ? 's' : '' }}
            </button>
        </div>

        <!-- Action bar -->
        <div class="mx-4 border-t border-gray-100 flex relative" ref="reactArea">
            <!-- Reaction picker popover -->
            <Transition
                enter-active-class="transition ease-out duration-150"
                enter-from-class="opacity-0 translate-y-1 scale-95"
                enter-to-class="opacity-100 translate-y-0 scale-100"
                leave-active-class="transition ease-in duration-100"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="pickerOpen"
                    @mouseenter="hoverPicker(true)"
                    @mouseleave="hoverPicker(false)"
                    class="absolute bottom-full left-0 mb-1 bg-white rounded-full border border-gray-200 shadow-xl px-2 py-1.5 flex gap-1 z-20"
                >
                    <button
                        v-for="(emoji, type) in reactionEmoji"
                        :key="type"
                        @click="react(type)"
                        :title="type"
                        :class="[
                            'text-2xl leading-none p-1 rounded-full hover:scale-125 transition-transform',
                            post.my_reaction === type ? 'bg-gray-100 ring-2 ring-[#EF233C]/30' : '',
                        ]"
                    >{{ emoji }}</button>
                </div>
            </Transition>

            <button
                @click="quickReact"
                @mouseenter="hoverPicker(true)"
                @mouseleave="hoverPicker(false)"
                @touchstart="onTouchStart"
                @touchend="onTouchEnd"
                @touchmove="onTouchEnd"
                @contextmenu.prevent
                :class="[
                    'flex-1 flex items-center justify-center gap-1.5 py-2.5 text-xs font-semibold rounded-lg my-1 transition-colors',
                    post.my_reaction ? 'text-[#EF233C]' : 'text-gray-500 hover:bg-gray-50',
                ]"
            >
                <span v-if="post.my_reaction" class="text-base leading-none">{{ reactionEmoji[post.my_reaction] }}</span>
                <HandThumbUpIcon v-else class="w-4 h-4" />
                {{ post.my_reaction ? reactionLabel[post.my_reaction] : 'React' }}
            </button>
            <button
                @click="focusComment"
                class="flex-1 flex items-center justify-center gap-1.5 py-2.5 text-xs font-semibold text-gray-500 hover:bg-gray-50 rounded-lg my-1 transition-colors"
            >
                <ChatBubbleOvalLeftIcon class="w-4 h-4" />
                Comment
            </button>
        </div>

        <!-- Comments -->
        <div v-if="commentsOpen || post.comments.length <= 2" class="px-4 pb-3 space-y-2.5">
            <div v-for="c in visibleComments" :key="c.id" class="flex items-start gap-2 group">
                <img :src="c.user.avatar_url" :alt="c.user.name" class="w-7 h-7 rounded-full object-cover flex-shrink-0 mt-0.5" />
                <div class="bg-gray-100 rounded-2xl px-3 py-2 min-w-0">
                    <p class="text-xs font-semibold text-gray-800">{{ c.user.name }}</p>
                    <p class="text-sm text-gray-700 break-words whitespace-pre-wrap">{{ c.body }}</p>
                </div>
                <div class="flex flex-col items-center gap-0.5 flex-shrink-0 self-center">
                    <button
                        v-if="c.can_delete"
                        @click="deleteComment(c.id)"
                        class="p-1 rounded-full text-gray-300 hover:text-red-500 hover:bg-red-50 opacity-100 sm:opacity-0 sm:group-hover:opacity-100 transition-all"
                        title="Delete comment"
                    >
                        <TrashIcon class="w-3.5 h-3.5" />
                    </button>
                </div>
            </div>
            <button
                v-if="!commentsOpen && post.comments.length > 2"
                @click="commentsOpen = true"
                class="text-xs font-medium text-gray-400 hover:text-gray-600"
            >View all {{ post.comments.length }} comments</button>
        </div>

        <!-- Comment input -->
        <div class="px-4 pb-4 flex items-center gap-2">
            <img :src="me.avatar_url" :alt="me.name" class="w-7 h-7 rounded-full object-cover flex-shrink-0" />
            <div class="flex-1 relative">
                <input
                    ref="commentInput"
                    v-model="commentBody"
                    @keydown.enter.prevent="submitComment"
                    type="text"
                    placeholder="Write a comment…"
                    class="w-full bg-gray-100 border-0 rounded-full text-sm pl-4 pr-10 py-2 focus:outline-none focus:ring-2 focus:ring-[#EF233C]/20 placeholder:text-gray-400"
                />
                <button
                    @click="submitComment"
                    :disabled="!commentBody.trim() || commentSubmitting"
                    class="absolute right-1.5 top-1/2 -translate-y-1/2 p-1.5 rounded-full text-[#EF233C] disabled:text-gray-300 hover:bg-[#EF233C]/10 transition-colors"
                >
                    <PaperAirplaneIcon class="w-4 h-4" />
                </button>
            </div>
        </div>

        <!-- Lightbox -->
        <Teleport to="body">
            <div v-if="lightbox" @click="lightbox = null" class="fixed inset-0 bg-black/80 z-50 flex items-center justify-center p-6 cursor-zoom-out">
                <img :src="lightbox" class="max-w-full max-h-full object-contain rounded-lg" />
            </div>
        </Teleport>

        <!-- Delete confirmations -->
        <ConfirmModal
            :open="confirmDeletePost"
            title="Delete post?"
            message="This will permanently remove the post, its reactions and comments."
            confirmLabel="Delete"
            danger
            @confirm="confirmedDeletePost"
            @cancel="confirmDeletePost = false"
        />
        <ConfirmModal
            :open="!!confirmDeleteCommentId"
            title="Delete comment?"
            message="This comment will be permanently removed."
            confirmLabel="Delete"
            danger
            @confirm="confirmedDeleteComment"
            @cancel="confirmDeleteCommentId = null"
        />
    </article>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import {
    EllipsisHorizontalIcon, TrashIcon, BookmarkIcon, TrophyIcon, MapPinIcon,
    HandThumbUpIcon, ChatBubbleOvalLeftIcon, PaperAirplaneIcon,
} from '@heroicons/vue/24/outline';
import dayjs from 'dayjs';
import relativeTime from 'dayjs/plugin/relativeTime';
dayjs.extend(relativeTime);

const props = defineProps({
    post:         { type: Object, required: true },
    isPrivileged: { type: Boolean, default: false },
});

const page = usePage();
const me   = computed(() => page.props.auth.user);

const reactionEmoji = { like: '👍', love: '❤️', celebrate: '🎉', clap: '👏', laugh: '😂' };
const reactionLabel = { like: 'Like', love: 'Love', celebrate: 'Celebrate', clap: 'Clap', laugh: 'Haha' };

const typeBadge = computed(() => ({
    blog:        { label: 'Blog',        class: 'bg-sky-100 text-sky-700' },
    event:       { label: 'Event',       class: 'bg-indigo-100 text-indigo-700' },
    recognition: { label: 'Recognition', class: 'bg-amber-100 text-amber-700' },
}[props.post.type] ?? null));

// Body see-more
const expanded = ref(false);
const isLong   = computed(() => props.post.body.length > 480 || props.post.body.split('\n').length > 6);

// Event date parts
const eventMonth = computed(() => props.post.event_date ? dayjs(props.post.event_date).format('MMM') : '');
const eventDay   = computed(() => props.post.event_date ? dayjs(props.post.event_date).format('DD') : '');

function timeAgo(iso) {
    return dayjs(iso).fromNow();
}

// Top reaction types by count (for the summary cluster)
const topReactions = computed(() =>
    Object.entries(props.post.reactions)
        .sort((a, b) => b[1].count - a[1].count)
        .slice(0, 3)
        .map(([type]) => type)
);

const reactionTooltip = computed(() =>
    Object.entries(props.post.reactions)
        .map(([type, data]) => `${reactionEmoji[type]} ${data.names.join(', ')}`)
        .join('\n')
);

// ── Reactions ────────────────────────────────────────────────────────────────

const pickerOpen = ref(false);
let pickerTimer  = null;

function hoverPicker(entering) {
    clearTimeout(pickerTimer);
    if (entering) {
        pickerTimer = setTimeout(() => { pickerOpen.value = true; }, 350);
    } else {
        pickerTimer = setTimeout(() => { pickerOpen.value = false; }, 700);
    }
}

function quickReact() {
    // A long-press just opened the picker — don't also fire the quick reaction
    if (longPressFired) {
        longPressFired = false;
        return;
    }
    // Tap = toggle current reaction, or default like
    react(props.post.my_reaction ?? 'like');
}

// Long-press on touch devices opens the emoji picker (no hover available)
let touchTimer     = null;
let longPressFired = false;

function onTouchStart() {
    longPressFired = false;
    clearTimeout(touchTimer);
    touchTimer = setTimeout(() => {
        longPressFired   = true;
        pickerOpen.value = true;
    }, 450);
}

function onTouchEnd() {
    clearTimeout(touchTimer);
}

function react(type) {
    pickerOpen.value = false;
    clearTimeout(pickerTimer);
    router.post(route('feed.react', props.post.id), { type }, { preserveScroll: true });
}

// ── Comments ─────────────────────────────────────────────────────────────────

const commentsOpen      = ref(false);
const commentBody       = ref('');
const commentSubmitting = ref(false);
const commentInput      = ref(null);

const visibleComments = computed(() =>
    commentsOpen.value ? props.post.comments : props.post.comments.slice(-2)
);

function focusComment() {
    commentsOpen.value = true;
    commentInput.value?.focus();
}

function submitComment() {
    const body = commentBody.value.trim();
    if (!body || commentSubmitting.value) return;
    commentSubmitting.value = true;
    router.post(route('feed.comments.store', props.post.id), { body }, {
        preserveScroll: true,
        onSuccess: () => { commentBody.value = ''; commentsOpen.value = true; },
        onFinish:  () => { commentSubmitting.value = false; },
    });
}

const confirmDeleteCommentId = ref(null);

function deleteComment(commentId) {
    confirmDeleteCommentId.value = commentId;
}

function confirmedDeleteComment() {
    const id = confirmDeleteCommentId.value;
    confirmDeleteCommentId.value = null;
    router.delete(route('feed.comments.destroy', [props.post.id, id]), { preserveScroll: true });
}

// ── Menu / pin / delete ──────────────────────────────────────────────────────

const menuOpen  = ref(false);
const menuEl    = ref(null);
const reactArea = ref(null);

function onClickOutside(e) {
    if (menuEl.value && ! menuEl.value.contains(e.target)) menuOpen.value = false;
    if (reactArea.value && ! reactArea.value.contains(e.target)) {
        clearTimeout(pickerTimer);
        pickerOpen.value = false;
    }
}
onMounted(() => document.addEventListener('mousedown', onClickOutside));
onUnmounted(() => {
    document.removeEventListener('mousedown', onClickOutside);
    clearTimeout(pickerTimer);
    clearTimeout(touchTimer);
});

function togglePin() {
    menuOpen.value = false;
    router.post(route('feed.pin', props.post.id), {}, { preserveScroll: true });
}

const confirmDeletePost = ref(false);

function deletePost() {
    menuOpen.value = false;
    confirmDeletePost.value = true;
}

function confirmedDeletePost() {
    confirmDeletePost.value = false;
    router.delete(route('feed.destroy', props.post.id), { preserveScroll: true });
}

// ── Lightbox ─────────────────────────────────────────────────────────────────

const lightbox = ref(null);
</script>
