<template>
    <Teleport to="body">
        <Transition name="cm">
            <div v-if="card" class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-sm p-0 sm:p-4" @click.self="$emit('close')">
                <div class="cm-panel relative bg-white w-full max-w-3xl mx-auto sm:rounded-2xl shadow-2xl min-h-screen sm:min-h-0 sm:my-6">
                    <!-- Header -->
                    <div class="flex items-start gap-3 px-5 py-4 border-b border-gray-100">
                        <ViewColumnsIcon class="w-5 h-5 text-gray-300 flex-shrink-0 mt-1.5" />
                        <div class="flex-1 min-w-0">
                            <input
                                v-model="titleDraft"
                                @blur="saveTitle"
                                @keydown.enter.prevent="$event.target.blur()"
                                class="w-full text-lg font-bold text-gray-800 border border-transparent hover:border-gray-200 focus:border-[#EF233C]/40 rounded-lg px-2 py-1 -ml-2 focus:outline-none focus:ring-2 focus:ring-[#EF233C]/15"
                            />
                            <p class="text-xs text-gray-400 px-0.5 mt-0.5">in list <span class="font-medium text-gray-500">{{ listName }}</span></p>
                        </div>
                        <button @click="$emit('close')" class="p-1.5 rounded-lg text-gray-400 hover:bg-gray-100 flex-shrink-0">
                            <XMarkIcon class="w-5 h-5" />
                        </button>
                    </div>

                    <!-- Quick actions -->
                    <div class="flex flex-wrap gap-1.5 px-5 pt-3">
                        <button @click="togglePanel('labels')" :class="actionBtn"><TagIcon class="w-3.5 h-3.5" /> Labels</button>
                        <button @click="togglePanel('date')" :class="actionBtn"><ClockIcon class="w-3.5 h-3.5" /> Dates</button>
                        <button @click="$refs.fileInput.click()" :class="actionBtn"><PaperClipIcon class="w-3.5 h-3.5" /> Attachment</button>
                        <input ref="fileInput" type="file" class="hidden" @change="uploadAttachment" />
                    </div>

                    <!-- Body: two columns -->
                    <div class="flex flex-col sm:flex-row gap-0 sm:gap-4 px-5 py-4">

                        <!-- Left: details -->
                        <div class="flex-1 min-w-0 space-y-5">

                            <!-- Labels -->
                            <div v-if="card.labels.length || panel === 'labels'">
                                <p class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-1.5">Labels</p>
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <span
                                        v-for="l in card.labels"
                                        :key="l.id"
                                        :class="['text-xs font-semibold px-2.5 py-1 rounded', labelClass(l.color)]"
                                    >{{ l.name || '&nbsp;' }}</span>
                                </div>
                                <!-- Picker -->
                                <div v-if="panel === 'labels'" class="mt-2 p-2 bg-gray-50 rounded-xl border border-gray-100 space-y-1">
                                    <div v-for="l in boardLabels" :key="l.id" class="flex items-center gap-2">
                                        <button
                                            @click="toggleLabel(l)"
                                            :class="['flex-1 flex items-center h-7 px-2 rounded text-xs font-semibold transition-all', labelClass(l.color), hasLabel(l.id) ? 'ring-2 ring-offset-1 ring-gray-400' : 'opacity-90 hover:opacity-100']"
                                        >
                                            <span class="truncate">{{ l.name }}</span>
                                            <CheckIcon v-if="hasLabel(l.id)" class="w-3.5 h-3.5 ml-auto" />
                                        </button>
                                        <input
                                            :value="l.name"
                                            @change="renameLabel(l, $event.target.value)"
                                            placeholder="name…"
                                            class="w-20 text-[11px] border border-gray-200 rounded px-1.5 py-1 focus:outline-none focus:ring-1 focus:ring-[#EF233C]/20"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- Due date -->
                            <div v-if="card.due_date || panel === 'date'">
                                <p class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-1.5">Due date</p>
                                <div v-if="card.due_date && panel !== 'date'" class="flex items-center gap-2">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" :checked="card.due_done" @change="toggleDueDone" class="rounded text-emerald-500 focus:ring-emerald-400" />
                                        <span :class="['text-sm px-2.5 py-1 rounded-md font-medium', dueChipClass]">{{ formatDue(card.due_date) }}</span>
                                    </label>
                                    <button @click="panel = 'date'" class="text-xs text-gray-400 hover:text-gray-600">Edit</button>
                                </div>
                                <div v-else class="flex items-center gap-2">
                                    <input
                                        type="datetime-local"
                                        :value="toLocalInput(card.due_date)"
                                        @change="setDue($event.target.value)"
                                        class="text-sm border border-gray-200 rounded-lg px-2 py-1.5 focus:outline-none focus:ring-2 focus:ring-[#EF233C]/15"
                                    />
                                    <button v-if="card.due_date" @click="setDue('')" class="text-xs text-red-400 hover:text-red-600">Remove</button>
                                </div>
                            </div>

                            <!-- Description -->
                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wide flex items-center gap-1.5">
                                        <Bars3BottomLeftIcon class="w-4 h-4" /> Description
                                    </p>
                                    <button v-if="!editingDesc && card.description" @click="startEditDesc" class="text-xs text-gray-400 hover:text-gray-600">Edit</button>
                                </div>
                                <template v-if="editingDesc">
                                    <textarea
                                        ref="descInput"
                                        v-model="descDraft"
                                        rows="5"
                                        placeholder="Add a more detailed description…"
                                        class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 resize-y focus:outline-none focus:ring-2 focus:ring-[#EF233C]/15 focus:border-[#EF233C]/40"
                                    />
                                    <div class="flex gap-1.5 mt-1.5">
                                        <button @click="saveDesc" class="text-xs font-semibold bg-[#2B2D42] hover:bg-[#EF233C] text-white px-3 py-1.5 rounded-lg transition-colors">Save</button>
                                        <button @click="editingDesc = false" class="text-xs text-gray-400 px-2">Cancel</button>
                                    </div>
                                </template>
                                <div
                                    v-else
                                    @click="startEditDesc"
                                    :class="['text-sm rounded-lg px-3 py-2 cursor-text whitespace-pre-wrap break-words', card.description ? 'text-gray-700 bg-gray-50' : 'text-gray-400 bg-gray-50 hover:bg-gray-100']"
                                >{{ card.description || 'Add a more detailed description…' }}</div>
                            </div>

                            <!-- Attachments -->
                            <div v-if="card.attachments.length">
                                <p class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-1.5 flex items-center gap-1.5">
                                    <PaperClipIcon class="w-4 h-4" /> Attachments
                                </p>
                                <div class="space-y-1.5">
                                    <div v-for="a in card.attachments" :key="a.id" class="group flex items-center gap-3 p-2 rounded-lg border border-gray-100 hover:bg-gray-50">
                                        <a :href="a.url" target="_blank" class="flex-shrink-0">
                                            <img v-if="a.is_image" :src="a.url" class="w-12 h-12 rounded-lg object-cover" />
                                            <div v-else class="w-12 h-12 rounded-lg bg-gray-100 flex items-center justify-center">
                                                <DocumentIcon class="w-6 h-6 text-gray-400" />
                                            </div>
                                        </a>
                                        <div class="flex-1 min-w-0">
                                            <a :href="a.url" target="_blank" class="text-sm font-medium text-gray-700 truncate hover:underline block">{{ a.name }}</a>
                                            <p class="text-[10px] text-gray-400">{{ humanSize(a.size) }}</p>
                                        </div>
                                        <button @click="deleteAttachment(a)" class="flex-shrink-0 p-1 rounded text-gray-300 hover:text-red-500 hover:bg-red-50 opacity-100 sm:opacity-0 sm:group-hover:opacity-100 transition-all">
                                            <TrashIcon class="w-4 h-4" />
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Checklist -->
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wide flex items-center gap-1.5">
                                        <CheckCircleIcon class="w-4 h-4" /> Checklist
                                    </p>
                                    <button v-if="total" @click="hideChecked = !hideChecked" class="text-xs text-gray-400 hover:text-gray-600">
                                        {{ hideChecked ? 'Show checked' : 'Hide checked items' }}
                                    </button>
                                </div>
                                <div v-if="total > 0" class="flex items-center gap-2 mb-2">
                                    <span class="text-[11px] font-semibold text-gray-400 w-9 text-right">{{ percent }}%</span>
                                    <div class="flex-1 bg-gray-100 rounded-full h-2 overflow-hidden">
                                        <div class="h-2 rounded-full bg-emerald-400 transition-all duration-300" :style="{ width: percent + '%' }" />
                                    </div>
                                </div>
                                <div class="space-y-0.5 mb-2">
                                    <div
                                        v-for="item in visibleItems"
                                        :key="item.id"
                                        class="group flex items-center gap-2 rounded-lg px-2 py-1.5 hover:bg-gray-50"
                                    >
                                        <button @click="toggleItem(item)" class="flex-shrink-0">
                                            <CheckCircleIconSolid v-if="item.is_done" class="w-5 h-5 text-emerald-500" />
                                            <span v-else class="block w-4 h-4 m-0.5 rounded border-2 border-gray-300 hover:border-gray-400" />
                                        </button>
                                        <span :class="['flex-1 text-sm break-words', item.is_done ? 'line-through text-gray-400' : 'text-gray-700']">{{ item.title }}</span>
                                        <button @click="deleteItem(item)" class="flex-shrink-0 p-1 rounded text-gray-300 hover:text-red-500 hover:bg-red-50 opacity-100 sm:opacity-0 sm:group-hover:opacity-100 transition-all">
                                            <TrashIcon class="w-3.5 h-3.5" />
                                        </button>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <input
                                        v-model="newItem"
                                        @keydown.enter.prevent="addItem"
                                        type="text"
                                        placeholder="Add an item…"
                                        class="flex-1 text-sm border border-gray-200 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-[#EF233C]/15 focus:border-[#EF233C]/40"
                                    />
                                    <button @click="addItem" :disabled="!newItem.trim()" class="text-xs font-semibold bg-[#2B2D42] hover:bg-[#EF233C] disabled:opacity-40 text-white px-3 py-2 rounded-lg transition-colors">Add</button>
                                </div>
                            </div>
                        </div>

                        <!-- Right: comments and activity -->
                        <div class="sm:w-60 flex-shrink-0 mt-6 sm:mt-0 sm:border-l sm:border-gray-100 sm:pl-4">
                            <p class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-2 flex items-center gap-1.5">
                                <ChatBubbleLeftRightIcon class="w-4 h-4" /> Comments and activity
                            </p>

                            <!-- Write a comment -->
                            <div class="flex items-start gap-2 mb-3">
                                <img :src="me.avatar_url" class="w-7 h-7 rounded-full object-cover flex-shrink-0" />
                                <div class="flex-1">
                                    <textarea
                                        v-model="commentBody"
                                        rows="2"
                                        placeholder="Write a comment…"
                                        class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 resize-none focus:outline-none focus:ring-2 focus:ring-[#EF233C]/15 focus:border-[#EF233C]/40"
                                    />
                                    <button v-if="commentBody.trim()" @click="addComment" class="mt-1 text-xs font-semibold bg-[#2B2D42] hover:bg-[#EF233C] text-white px-3 py-1.5 rounded-lg transition-colors">Comment</button>
                                </div>
                            </div>

                            <!-- Feed -->
                            <div class="space-y-3">
                                <div v-for="c in [...card.comments].reverse()" :key="c.id" class="flex items-start gap-2 group">
                                    <img :src="c.user.avatar_url" class="w-7 h-7 rounded-full object-cover flex-shrink-0" />
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs"><span class="font-semibold text-gray-800">{{ c.user.name }}</span> <span class="text-gray-400">{{ ago(c.created_at) }}</span></p>
                                        <div class="text-sm text-gray-700 bg-gray-50 rounded-lg px-3 py-1.5 mt-0.5 break-words whitespace-pre-wrap">{{ c.body }}</div>
                                        <button v-if="c.can_delete" @click="deleteComment(c)" class="text-[10px] text-gray-400 hover:text-red-500 mt-0.5 opacity-0 group-hover:opacity-100 transition-opacity">Delete</button>
                                    </div>
                                </div>

                                <!-- Creation activity -->
                                <div class="flex items-start gap-2">
                                    <img :src="card.creator?.avatar_url ?? me.avatar_url" class="w-7 h-7 rounded-full object-cover flex-shrink-0" />
                                    <p class="text-xs text-gray-500 mt-1">
                                        <span class="font-semibold text-gray-700">{{ card.creator?.name ?? 'Someone' }}</span>
                                        added this card to <span class="font-medium">{{ listName }}</span>
                                        <span class="block text-gray-400">{{ ago(card.created_at) }}</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="px-5 py-3 border-t border-gray-100 flex justify-end">
                        <button @click="$emit('delete', card)" class="inline-flex items-center gap-1.5 text-xs font-semibold text-red-500 border border-red-200 hover:bg-red-50 px-3 py-2 rounded-lg transition-colors">
                            <TrashIcon class="w-3.5 h-3.5" /> Delete card
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { ref, computed, watch, nextTick } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import dayjs from 'dayjs';
import relativeTime from 'dayjs/plugin/relativeTime';
import {
    ViewColumnsIcon, XMarkIcon, CheckCircleIcon, TrashIcon, TagIcon, ClockIcon,
    PaperClipIcon, Bars3BottomLeftIcon, DocumentIcon, ChatBubbleLeftRightIcon, CheckIcon,
} from '@heroicons/vue/24/outline';
import { CheckCircleIcon as CheckCircleIconSolid } from '@heroicons/vue/24/solid';

dayjs.extend(relativeTime);

const props = defineProps({
    card:        { type: Object, default: null },
    listName:    { type: String, default: '' },
    boardLabels: { type: Array,  default: () => [] },
});

defineEmits(['close', 'delete']);

const page = usePage();
const me   = computed(() => page.props.auth.user);

const opts = { preserveScroll: true, preserveState: true };

// ── Labels ────────────────────────────────────────────────────────────────────
const LABEL_CLASSES = {
    green:  'bg-emerald-200 text-emerald-900',
    yellow: 'bg-yellow-200 text-yellow-900',
    orange: 'bg-orange-200 text-orange-900',
    red:    'bg-red-200 text-red-900',
    purple: 'bg-purple-200 text-purple-900',
    blue:   'bg-sky-200 text-sky-900',
};
function labelClass(c) { return LABEL_CLASSES[c] ?? 'bg-gray-200 text-gray-800'; }
function hasLabel(id) { return props.card?.labels.some(l => l.id === id); }
function toggleLabel(l) { router.post(route('boards.cards.labels.toggle', [props.card.id, l.id]), {}, opts); }
function renameLabel(l, name) { router.patch(route('boards.labels.update', l.id), { name }, opts); }

// ── Panels ────────────────────────────────────────────────────────────────────
const panel = ref(null);
function togglePanel(p) { panel.value = panel.value === p ? null : p; }

// ── Title ─────────────────────────────────────────────────────────────────────
const titleDraft = ref('');
watch(() => props.card, (c) => { titleDraft.value = c?.title ?? ''; }, { immediate: true });
function saveTitle() {
    const t = titleDraft.value.trim();
    if (!props.card || !t || t === props.card.title) return;
    router.patch(route('boards.cards.update', props.card.id), { title: t }, opts);
}

// ── Description ───────────────────────────────────────────────────────────────
const editingDesc = ref(false);
const descDraft = ref('');
const descInput = ref(null);
function startEditDesc() {
    descDraft.value = props.card.description ?? '';
    editingDesc.value = true;
    nextTick(() => descInput.value?.focus());
}
function saveDesc() {
    editingDesc.value = false;
    router.patch(route('boards.cards.update', props.card.id), { description: descDraft.value }, opts);
}

// ── Due date ──────────────────────────────────────────────────────────────────
function toLocalInput(iso) { return iso ? dayjs(iso).format('YYYY-MM-DDTHH:mm') : ''; }
function setDue(val) {
    panel.value = null;
    router.patch(route('boards.cards.update', props.card.id), { due_date: val || null, due_done: val ? props.card.due_done : false }, opts);
}
function toggleDueDone() {
    router.patch(route('boards.cards.update', props.card.id), { due_done: !props.card.due_done }, opts);
}
function formatDue(iso) { return dayjs(iso).format('D MMM YYYY, HH:mm'); }
const dueChipClass = computed(() => {
    if (!props.card?.due_date) return '';
    if (props.card.due_done) return 'bg-emerald-100 text-emerald-700';
    const due = dayjs(props.card.due_date);
    if (due.isBefore(dayjs())) return 'bg-red-100 text-red-700';
    if (due.isBefore(dayjs().add(1, 'day'))) return 'bg-amber-100 text-amber-700';
    return 'bg-gray-100 text-gray-700';
});

// ── Attachments ───────────────────────────────────────────────────────────────
function uploadAttachment(e) {
    const file = e.target.files[0];
    e.target.value = '';
    if (!file) return;
    router.post(route('boards.cards.attachments.store', props.card.id), { file }, { ...opts, forceFormData: true });
}
function deleteAttachment(a) {
    router.delete(route('boards.attachments.destroy', a.id), opts);
}
function humanSize(b) {
    if (!b) return '';
    const u = ['B', 'KB', 'MB', 'GB']; let i = 0; let n = b;
    while (n >= 1024 && i < u.length - 1) { n /= 1024; i++; }
    return `${n.toFixed(n < 10 && i > 0 ? 1 : 0)} ${u[i]}`;
}

// ── Checklist ─────────────────────────────────────────────────────────────────
const hideChecked = ref(false);
const newItem = ref('');
const total   = computed(() => props.card?.checklist?.length ?? 0);
const doneN   = computed(() => props.card?.checklist?.filter(i => i.is_done).length ?? 0);
const percent = computed(() => total.value ? Math.round((doneN.value / total.value) * 100) : 0);
const visibleItems = computed(() => hideChecked.value ? props.card.checklist.filter(i => !i.is_done) : props.card.checklist);
function addItem() {
    const t = newItem.value.trim();
    if (!t) return;
    newItem.value = '';
    router.post(route('boards.checklist.store', props.card.id), { title: t }, opts);
}
function toggleItem(item) { router.patch(route('boards.checklist.update', item.id), { is_done: !item.is_done }, opts); }
function deleteItem(item) { router.delete(route('boards.checklist.destroy', item.id), opts); }

// ── Comments ──────────────────────────────────────────────────────────────────
const commentBody = ref('');
function addComment() {
    const b = commentBody.value.trim();
    if (!b) return;
    commentBody.value = '';
    router.post(route('boards.cards.comments.store', props.card.id), { body: b }, opts);
}
function deleteComment(c) { router.delete(route('boards.comments.destroy', c.id), opts); }

// ── Helpers ───────────────────────────────────────────────────────────────────
function ago(iso) { return iso ? dayjs(iso).fromNow() : ''; }

const actionBtn = 'inline-flex items-center gap-1.5 text-xs font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 px-2.5 py-1.5 rounded-lg transition-colors';
</script>

<style scoped>
.cm-enter-active, .cm-leave-active { transition: opacity 0.2s ease; }
.cm-enter-from, .cm-leave-to { opacity: 0; }
.cm-enter-active .cm-panel { transition: transform 0.25s cubic-bezier(0.16,1,0.3,1); }
.cm-enter-from .cm-panel { transform: translateY(12px) scale(0.98); }
</style>
