<template>
    <Teleport to="body">
        <Transition name="cm">
            <div v-if="card" class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-sm p-0 sm:p-4" @click.self="$emit('close')">
                <div class="cm-panel relative bg-white w-full max-w-4xl mx-auto rounded-b-2xl sm:rounded-2xl shadow-2xl my-0 sm:my-6 flex flex-col sm:overflow-hidden">
                    <!-- Header -->
                    <div class="flex items-start gap-3 px-5 py-4 border-b border-gray-100 flex-shrink-0">
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

                    <!-- Body: two independently-scrolling panels -->
                    <div class="flex flex-col sm:flex-row">

                        <!-- Left: details (own scrollbar) -->
                        <div class="sm:flex-1 min-w-0 sm:max-h-[65vh] sm:overflow-y-auto px-5 py-4 space-y-5">

                            <!-- Quick actions -->
                            <div class="flex flex-wrap gap-1.5">
                                <button @click="togglePanel('labels')" :class="actionBtn"><TagIcon class="w-3.5 h-3.5" /> Labels</button>
                                <button @click="openDates($event)" :class="actionBtn"><ClockIcon class="w-3.5 h-3.5" /> Dates</button>
                                <button @click="$refs.fileInput.click()" :class="actionBtn"><PaperClipIcon class="w-3.5 h-3.5" /> Attachment</button>
                                <input ref="fileInput" type="file" class="hidden" accept="image/*,video/*,.pdf,.doc,.docx,.xls,.xlsx,.csv,.txt,.zip,.ppt,.pptx" @change="uploadAttachment" />
                            </div>

                            <!-- Upload progress / error -->
                            <div v-if="uploading" class="rounded-lg border border-gray-200 bg-gray-50 p-2.5">
                                <div class="flex items-center justify-between mb-1.5">
                                    <span class="text-xs font-medium text-gray-600 truncate flex items-center gap-1.5">
                                        <ArrowPathIcon class="w-3.5 h-3.5 animate-spin text-gray-400" />
                                        Uploading {{ uploadName }}…
                                    </span>
                                    <span class="text-xs font-semibold text-gray-500 flex-shrink-0">{{ uploadPct }}%</span>
                                </div>
                                <div class="h-1.5 w-full rounded-full bg-gray-200 overflow-hidden">
                                    <div class="h-full rounded-full bg-[#EF233C] transition-all duration-150" :style="{ width: uploadPct + '%' }" />
                                </div>
                            </div>
                            <div v-if="uploadError" class="rounded-lg border border-red-200 bg-red-50 p-2.5 flex items-start gap-2">
                                <ExclamationTriangleIcon class="w-4 h-4 text-red-500 flex-shrink-0 mt-0.5" />
                                <p class="text-xs text-red-700 flex-1">{{ uploadError }}</p>
                                <button @click="uploadError = ''" class="text-red-400 hover:text-red-600 flex-shrink-0"><XMarkIcon class="w-4 h-4" /></button>
                            </div>

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
                                <div v-if="panel === 'labels'" class="mt-2 p-2 bg-gray-50 rounded-xl border border-gray-100 space-y-2">
                                    <div v-for="l in boardLabels" :key="l.id">
                                        <div class="flex items-center gap-2">
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
                                            <button
                                                @click="colorEditId = colorEditId === l.id ? null : l.id"
                                                :title="'Change colour'"
                                                class="p-1.5 rounded text-gray-400 hover:text-gray-700 hover:bg-gray-200 transition-colors"
                                            >
                                                <SwatchIcon class="w-4 h-4" />
                                            </button>
                                        </div>
                                        <!-- Colour swatches -->
                                        <div v-if="colorEditId === l.id" class="flex flex-wrap gap-1.5 mt-1.5 pl-0.5">
                                            <button
                                                v-for="c in LABEL_COLORS"
                                                :key="c"
                                                @click="setLabelColor(l, c)"
                                                :title="c"
                                                :class="['w-7 h-7 rounded-md transition-all', swatchClass(c), l.color === c ? 'ring-2 ring-offset-1 ring-gray-600' : 'hover:scale-110']"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Dates -->
                            <div v-if="hasDates">
                                <div class="flex items-center justify-between mb-1.5">
                                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wide">Dates</p>
                                    <button @click="openDates($event)" class="text-xs text-gray-400 hover:text-gray-600">Edit</button>
                                </div>

                                <!-- Summary -->
                                <div class="flex flex-wrap items-center gap-2">
                                    <span v-if="card.start_date" class="inline-flex items-center gap-1.5 text-sm px-2.5 py-1 rounded-md font-medium bg-gray-100 text-gray-700">
                                        <ClockIcon class="w-3.5 h-3.5" /> Start: {{ formatDate(card.start_date) }}
                                    </span>
                                    <label v-if="card.due_date" class="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" :checked="card.due_done" @change="toggleDueDone" class="rounded text-emerald-500 focus:ring-emerald-400" />
                                        <span :class="['inline-flex items-center gap-1.5 text-sm px-2.5 py-1 rounded-md font-medium', dueChipClass]">
                                            <ClockIcon class="w-3.5 h-3.5" /> Due: {{ formatDue(card.due_date) }}
                                        </span>
                                    </label>
                                    <span v-if="card.recurring && card.recurring !== 'never'" class="inline-flex items-center gap-1 text-[11px] font-semibold px-2 py-1 rounded-md bg-sky-100 text-sky-700">
                                        <ArrowPathIcon class="w-3.5 h-3.5" /> {{ recurringLabel }}
                                    </span>
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
                                    v-else-if="card.description"
                                    @click="onDescClick"
                                    class="text-sm rounded-lg px-3 py-2 cursor-text whitespace-pre-wrap break-words text-gray-700 bg-gray-50"
                                    v-html="renderDescription(card.description)"
                                ></div>
                                <div
                                    v-else
                                    @click="startEditDesc"
                                    class="text-sm rounded-lg px-3 py-2 cursor-text whitespace-pre-wrap break-words text-gray-400 bg-gray-50 hover:bg-gray-100"
                                >Add a more detailed description…</div>
                            </div>

                            <!-- Attachments -->
                            <div v-if="card.attachments.length">
                                <p class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-1.5 flex items-center gap-1.5">
                                    <PaperClipIcon class="w-4 h-4" /> Attachments
                                </p>
                                <div class="space-y-1.5">
                                    <div v-for="a in card.attachments" :key="a.id" class="group p-2 rounded-lg border border-gray-100 hover:bg-gray-50">
                                        <div class="flex items-center gap-3">
                                            <a :href="a.url" target="_blank" class="flex-shrink-0">
                                                <img v-if="a.is_image" :src="a.url" class="w-12 h-12 rounded-lg object-cover" />
                                                <div v-else-if="a.is_video" class="w-12 h-12 rounded-lg bg-gray-900 flex items-center justify-center">
                                                    <FilmIcon class="w-6 h-6 text-gray-300" />
                                                </div>
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
                                        <video v-if="a.is_video" :src="a.url" controls preload="metadata" class="mt-2 w-full max-h-64 rounded-lg bg-black"></video>
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

                        <!-- Right: comments and activity (separate panel, own scrollbar) -->
                        <div class="sm:w-80 flex-shrink-0 sm:max-h-[65vh] sm:overflow-y-auto bg-gray-50 border-t sm:border-t-0 sm:border-l border-gray-200 px-5 py-4">
                            <p class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-3 flex items-center gap-1.5">
                                <ChatBubbleLeftRightIcon class="w-4 h-4" /> Comments and activity
                            </p>

                            <!-- Write a comment -->
                            <div class="flex items-start gap-2.5 mb-4">
                                <img :src="me.avatar_url" class="w-8 h-8 rounded-full object-cover flex-shrink-0" />
                                <div class="flex-1 min-w-0 relative">
                                    <textarea
                                        ref="commentInput"
                                        v-model="commentBody"
                                        @input="onCommentInput"
                                        @keydown="onCommentKeydown"
                                        rows="2"
                                        placeholder="Write a comment… use @ to mention"
                                        class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 resize-none focus:outline-none focus:ring-2 focus:ring-[#EF233C]/15 focus:border-[#EF233C]/40"
                                    />

                                    <!-- @mention autocomplete -->
                                    <div v-if="mentionOpen && mentionMatches.length" class="absolute z-20 left-0 right-0 mt-1 bg-white rounded-xl border border-gray-200 shadow-lg py-1 max-h-44 overflow-y-auto">
                                        <button
                                            v-for="(m, i) in mentionMatches"
                                            :key="m.id"
                                            @mousedown.prevent="pickMention(m)"
                                            :class="['w-full flex items-center gap-2 px-3 py-1.5 text-sm transition-colors', i === mentionIndex ? 'bg-[#EF233C]/8 text-[#EF233C]' : 'text-gray-700 hover:bg-gray-50']"
                                        >
                                            <img :src="m.avatar_url" class="w-6 h-6 rounded-full object-cover flex-shrink-0" />
                                            <span class="truncate">{{ m.name }}</span>
                                        </button>
                                    </div>

                                    <button v-if="commentBody.trim()" @click="addComment" class="mt-1 text-xs font-semibold bg-[#2B2D42] hover:bg-[#EF233C] text-white px-3 py-1.5 rounded-lg transition-colors">Comment</button>
                                </div>
                            </div>

                            <!-- Feed -->
                            <div class="space-y-4">
                                <div v-for="c in [...card.comments].reverse()" :key="c.id" class="flex items-start gap-2.5 group">
                                    <img :src="c.user.avatar_url" class="w-8 h-8 rounded-full object-cover flex-shrink-0" />
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs mb-1"><span class="font-semibold text-gray-800">{{ c.user.name }}</span> <span class="text-gray-400">{{ ago(c.created_at) }}</span></p>
                                        <div class="text-sm text-gray-700 bg-white border border-gray-200 rounded-lg px-3 py-2 break-words whitespace-pre-wrap shadow-sm" v-html="renderComment(c)"></div>
                                        <button v-if="c.can_delete" @click="deleteComment(c)" class="text-[10px] text-gray-400 hover:text-red-500 mt-1 opacity-0 group-hover:opacity-100 transition-opacity">Delete</button>
                                    </div>
                                </div>

                                <!-- Creation activity -->
                                <div class="flex items-start gap-2.5">
                                    <img :src="card.creator?.avatar_url ?? me.avatar_url" class="w-8 h-8 rounded-full object-cover flex-shrink-0" />
                                    <p class="text-xs text-gray-500 mt-1.5">
                                        <span class="font-semibold text-gray-700">{{ card.creator?.name ?? 'Someone' }}</span>
                                        added this card to <span class="font-medium">{{ listName }}</span>
                                        <span class="block text-gray-400 mt-0.5">{{ ago(card.created_at) }}</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="px-5 py-3 border-t border-gray-100 flex justify-end flex-shrink-0 bg-white">
                        <button @click="$emit('delete', card)" class="inline-flex items-center gap-1.5 text-xs font-semibold text-red-500 border border-red-200 hover:bg-red-50 px-3 py-2 rounded-lg transition-colors">
                            <TrashIcon class="w-3.5 h-3.5" /> Delete card
                        </button>
                    </div>

                    <!-- Floating date popover (Trello-style) -->
                    <CardDatePopover
                        v-if="panel === 'date'"
                        :card="card"
                        :anchor="dateAnchor"
                        @save="onDateSave"
                        @remove="onDateRemove"
                        @close="panel = null"
                    />
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
    PaperClipIcon, Bars3BottomLeftIcon, DocumentIcon, ChatBubbleLeftRightIcon, CheckIcon, SwatchIcon,
    ArrowPathIcon, FilmIcon, ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline';
import { CheckCircleIcon as CheckCircleIconSolid } from '@heroicons/vue/24/solid';
import CardDatePopover from '@/Components/Boards/CardDatePopover.vue';

dayjs.extend(relativeTime);

const props = defineProps({
    card:        { type: Object, default: null },
    listName:    { type: String, default: '' },
    boardLabels: { type: Array,  default: () => [] },
    members:     { type: Array,  default: () => [] },
});

defineEmits(['close', 'delete']);

const page = usePage();
const me   = computed(() => page.props.auth.user);

const opts = { preserveScroll: true, preserveState: true };

// ── Labels ────────────────────────────────────────────────────────────────────
const LABEL_COLORS = ['green', 'yellow', 'orange', 'red', 'purple', 'blue', 'pink', 'slate'];
const LABEL_CLASSES = {
    green:  'bg-emerald-200 text-emerald-900',
    yellow: 'bg-yellow-200 text-yellow-900',
    orange: 'bg-orange-200 text-orange-900',
    red:    'bg-red-200 text-red-900',
    purple: 'bg-purple-200 text-purple-900',
    blue:   'bg-sky-200 text-sky-900',
    pink:   'bg-pink-200 text-pink-900',
    slate:  'bg-slate-200 text-slate-900',
};
const SWATCH_CLASSES = {
    green:  'bg-emerald-400', yellow: 'bg-yellow-400', orange: 'bg-orange-400', red: 'bg-red-400',
    purple: 'bg-purple-400', blue: 'bg-sky-400', pink: 'bg-pink-400', slate: 'bg-slate-400',
};
function labelClass(c) { return LABEL_CLASSES[c] ?? 'bg-gray-200 text-gray-800'; }
function swatchClass(c) { return SWATCH_CLASSES[c] ?? 'bg-gray-400'; }
function hasLabel(id) { return props.card?.labels.some(l => l.id === id); }
function toggleLabel(l) { router.post(route('boards.cards.labels.toggle', [props.card.id, l.id]), {}, opts); }
function renameLabel(l, name) { router.patch(route('boards.labels.update', l.id), { name }, opts); }

const colorEditId = ref(null);
function setLabelColor(l, color) { router.patch(route('boards.labels.update', l.id), { color }, opts); }

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

// ── Dates (start / due / recurring / reminder) ──────────────────────────────────
const RECURRING_LABELS = { daily: 'Repeats daily', weekly: 'Repeats weekly', monthly: 'Repeats monthly' };
const hasDates = computed(() => !!(props.card?.start_date || props.card?.due_date));
const recurringLabel = computed(() => RECURRING_LABELS[props.card?.recurring] ?? '');

// Open the floating date popover anchored to the button that was clicked.
const dateAnchor = ref(null);
function openDates(e) {
    dateAnchor.value = e.currentTarget.getBoundingClientRect();
    panel.value = 'date';
}

function onDateSave(payload) {
    panel.value = null;
    router.patch(route('boards.cards.update', props.card.id), {
        ...payload,
        due_done: payload.due_date ? props.card.due_done : false,
    }, opts);
}
function onDateRemove() {
    panel.value = null;
    router.patch(route('boards.cards.update', props.card.id), {
        start_date: null, due_date: null, due_done: false, due_reminder: null, recurring: 'never',
    }, opts);
}
function toggleDueDone() {
    router.patch(route('boards.cards.update', props.card.id), { due_done: !props.card.due_done }, opts);
}
function formatDate(iso) { return dayjs(iso).format('D MMM YYYY'); }
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
const ATTACH_MAX_MB = 200;
const uploading   = ref(false);
const uploadPct   = ref(0);
const uploadName  = ref('');
const uploadError = ref('');

function uploadAttachment(e) {
    const file = e.target.files[0];
    e.target.value = '';
    if (!file) return;

    uploadError.value = '';

    // Instant client-side size check (avoids a slow upload that the server rejects).
    if (file.size > ATTACH_MAX_MB * 1024 * 1024) {
        uploadError.value = `"${file.name}" is ${humanSize(file.size)} — the maximum is ${ATTACH_MAX_MB} MB.`;
        return;
    }

    uploading.value  = true;
    uploadPct.value  = 0;
    uploadName.value = file.name;

    router.post(route('boards.cards.attachments.store', props.card.id), { file }, {
        ...opts,
        forceFormData: true,
        onProgress: (event) => {
            if (event && event.percentage != null) uploadPct.value = Math.round(event.percentage);
        },
        onError: (errors) => {
            uploadError.value = errors.file || 'Upload failed. Please check the file type and try again.';
        },
        onSuccess: () => { uploadError.value = ''; },
        onFinish: () => {
            uploading.value  = false;
            uploadPct.value  = 0;
            uploadName.value = '';
        },
    });
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

// ── Comments + @mentions ───────────────────────────────────────────────────────
const commentBody  = ref('');
const commentInput = ref(null);
const picked       = ref([]); // { id, name } the user selected via the picker

// Autocomplete state
const mentionOpen  = ref(false);
const mentionQuery = ref('');
const mentionIndex = ref(0);

const mentionMatches = computed(() => {
    const q = mentionQuery.value.toLowerCase();
    return props.members
        .filter(m => m.id !== me.value.id && m.name.toLowerCase().includes(q))
        .slice(0, 6);
});

// Detect a "@partial" token immediately before the caret (no spaces in the partial)
function onCommentInput(e) {
    const el = e.target;
    const upToCaret = el.value.slice(0, el.selectionStart);
    const match = upToCaret.match(/(?:^|\s)@([\w]*)$/);
    if (match) {
        mentionQuery.value = match[1];
        mentionOpen.value = true;
        mentionIndex.value = 0;
    } else {
        mentionOpen.value = false;
    }
}

function onCommentKeydown(e) {
    if (!mentionOpen.value || !mentionMatches.value.length) return;
    if (e.key === 'ArrowDown') { e.preventDefault(); mentionIndex.value = (mentionIndex.value + 1) % mentionMatches.value.length; }
    else if (e.key === 'ArrowUp') { e.preventDefault(); mentionIndex.value = (mentionIndex.value - 1 + mentionMatches.value.length) % mentionMatches.value.length; }
    else if (e.key === 'Enter' || e.key === 'Tab') { e.preventDefault(); pickMention(mentionMatches.value[mentionIndex.value]); }
    else if (e.key === 'Escape') { mentionOpen.value = false; }
}

function pickMention(m) {
    const el = commentInput.value;
    const caret = el ? el.selectionStart : commentBody.value.length;
    const before = commentBody.value.slice(0, caret);
    const after  = commentBody.value.slice(caret);
    // Replace the trailing "@partial" with "@Name "
    const newBefore = before.replace(/@([\w]*)$/, `@${m.name} `);
    commentBody.value = newBefore + after;
    if (!picked.value.some(p => p.id === m.id)) picked.value.push({ id: m.id, name: m.name });
    mentionOpen.value = false;
    nextTick(() => { el?.focus(); const pos = newBefore.length; el?.setSelectionRange(pos, pos); });
}

function addComment() {
    const b = commentBody.value.trim();
    if (!b) return;
    // Only send mentions whose "@Name" still appears in the body
    const mentions = picked.value.filter(p => b.includes('@' + p.name)).map(p => p.id);
    commentBody.value = '';
    picked.value = [];
    mentionOpen.value = false;
    router.post(route('boards.cards.comments.store', props.card.id), { body: b, mentions }, opts);
}
function deleteComment(c) { router.delete(route('boards.comments.destroy', c.id), opts); }

function escapeHtml(s) {
    return (s ?? '').replace(/[&<>"]/g, ch => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[ch]));
}

// Turn bare http(s) URLs in already-escaped text into clickable links.
function linkify(html) {
    return html.replace(/(https?:\/\/[^\s<]+)/g, (url) => {
        const trail = (url.match(/[.,;:!?)\]]+$/) || [''])[0]; // keep trailing punctuation out of the link
        const link  = trail ? url.slice(0, -trail.length) : url;
        return `<a href="${link}" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:underline break-all">${link}</a>${trail}`;
    });
}

// Description: escape, then linkify URLs.
function renderDescription(text) {
    return linkify(escapeHtml(text));
}

// Open links without dropping into edit mode; click elsewhere edits.
function onDescClick(e) {
    if (e.target.closest('a')) return;
    startEditDesc();
}

// Comment body: escape, linkify URLs, then highlight @mentions.
function renderComment(c) {
    let html = linkify(escapeHtml(c.body ?? ''));
    for (const name of (c.mention_names ?? [])) {
        const safe = escapeHtml(name);
        const escaped = safe.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        html = html.replace(new RegExp('@' + escaped, 'g'),
            `<span class="text-[#EF233C] font-semibold bg-[#EF233C]/8 rounded px-0.5">@${safe}</span>`);
    }
    return html;
}

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
