<template>
    <AppLayout title="Boards">
        <!-- ── Board top bar ────────────────────────────────────────────────── -->
        <div class="flex items-center gap-3 mb-4">
            <!-- Board switcher -->
            <div class="relative" ref="switcherEl">
                <button
                    @click="switcherOpen = !switcherOpen"
                    class="flex items-center gap-2 bg-white border border-gray-200 rounded-xl px-3 py-2 shadow-sm hover:bg-gray-50 transition-colors"
                >
                    <ViewColumnsIcon class="w-4 h-4 text-[#EF233C]" />
                    <span class="text-sm font-bold text-[#2B2D42] max-w-[40vw] truncate">{{ board.name }}</span>
                    <ChevronDownIcon class="w-4 h-4 text-gray-400" />
                </button>
                <div v-if="switcherOpen" class="absolute left-0 top-full mt-1 w-64 bg-white rounded-xl border border-gray-200 shadow-lg py-1 z-30">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider px-3 py-1.5">Your boards</p>
                    <Link
                        v-for="b in boards"
                        :key="b.id"
                        :href="`/boards?board=${b.id}`"
                        @click="switcherOpen = false"
                        :class="['flex items-center gap-2 px-3 py-2 text-sm transition-colors', b.id === board.id ? 'bg-[#EF233C]/8 text-[#EF233C] font-medium' : 'text-gray-700 hover:bg-gray-50']"
                    >
                        <ViewColumnsIcon class="w-4 h-4 flex-shrink-0" />
                        <span class="truncate">{{ b.name }}</span>
                    </Link>
                    <div class="border-t border-gray-100 mt-1 pt-1">
                        <div v-if="addingBoard" class="px-2 py-1">
                            <input
                                ref="boardInput"
                                v-model="newBoardName"
                                @keydown.enter.prevent="createBoard"
                                @keydown.esc="addingBoard = false"
                                type="text"
                                placeholder="Board name…"
                                class="w-full text-sm border border-gray-200 rounded-lg px-2 py-1.5 focus:outline-none focus:ring-2 focus:ring-[#EF233C]/15"
                            />
                            <div class="flex gap-1 mt-1">
                                <button @click="createBoard" class="text-xs font-semibold bg-[#2B2D42] text-white px-3 py-1.5 rounded-lg">Create</button>
                                <button @click="addingBoard = false" class="text-xs text-gray-400 px-2">Cancel</button>
                            </div>
                        </div>
                        <button v-else @click="startAddBoard" class="w-full text-left flex items-center gap-2 px-3 py-2 text-sm text-gray-600 hover:bg-gray-50">
                            <PlusIcon class="w-4 h-4" /> Create board
                        </button>
                    </div>
                </div>
            </div>

            <!-- Rename current board -->
            <input
                v-if="renamingBoard"
                ref="boardRenameInput"
                v-model="boardNameDraft"
                @blur="saveBoardName"
                @keydown.enter.prevent="$event.target.blur()"
                @keydown.esc="renamingBoard = false"
                class="text-sm font-bold text-[#2B2D42] border border-gray-200 rounded-lg px-2 py-1.5 focus:outline-none focus:ring-2 focus:ring-[#EF233C]/15"
            />
            <button v-else @click="startRenameBoard" class="text-xs text-gray-400 hover:text-gray-600 px-2 py-1.5 rounded-lg hover:bg-gray-100" title="Rename board">
                <PencilSquareIcon class="w-4 h-4" />
            </button>

            <button
                v-if="boards.length > 1"
                @click="deleteBoard"
                class="text-xs text-gray-300 hover:text-red-500 px-2 py-1.5 rounded-lg hover:bg-red-50 ml-auto"
                title="Delete this board"
            >
                <TrashIcon class="w-4 h-4" />
            </button>
        </div>

        <!-- ── Lists ────────────────────────────────────────────────────────── -->
        <div class="overflow-x-auto pb-4 -mx-4 px-4 sm:mx-0 sm:px-0">
            <draggable
                v-model="lists"
                :group="{ name: 'lists' }"
                item-key="id"
                handle=".list-handle"
                class="flex gap-3 items-start min-h-[200px]"
                :animation="160"
                @change="onListChange"
            >
                <template #item="{ element: list }">
                    <div class="flex-shrink-0 w-72 bg-[#EDF2F4] rounded-2xl flex flex-col max-h-[calc(100vh-13rem)]">
                        <!-- List header -->
                        <div class="list-handle flex items-center gap-2 px-3 py-2.5 cursor-grab active:cursor-grabbing">
                            <input
                                v-if="renamingListId === list.id"
                                :ref="el => bindListRename(el, list.id)"
                                v-model="listNameDraft"
                                @blur="saveListName(list)"
                                @keydown.enter.prevent="$event.target.blur()"
                                @keydown.esc="renamingListId = null"
                                class="flex-1 text-sm font-bold text-gray-700 bg-white border border-gray-200 rounded-lg px-2 py-1 focus:outline-none focus:ring-2 focus:ring-[#EF233C]/15"
                            />
                            <span v-else @click="startRenameList(list)" class="flex-1 text-sm font-bold text-gray-700 cursor-text truncate">{{ list.name }}</span>
                            <span class="text-xs font-semibold text-gray-400 bg-white rounded-full px-1.5 py-0.5 min-w-[20px] text-center">{{ list.cards.length }}</span>
                            <button @click="deleteList(list)" class="p-1 rounded text-gray-300 hover:text-red-500 hover:bg-white transition-colors" title="Delete list">
                                <TrashIcon class="w-3.5 h-3.5" />
                            </button>
                        </div>

                        <!-- Cards -->
                        <draggable
                            v-model="list.cards"
                            :group="{ name: 'cards' }"
                            item-key="id"
                            class="flex-1 overflow-y-auto px-2 pb-1 space-y-2 min-h-[12px]"
                            ghost-class="card-ghost"
                            drag-class="card-drag"
                            :animation="160"
                            @change="onCardChange($event, list)"
                        >
                            <template #item="{ element: card }">
                                <div
                                    @click="openCard(card)"
                                    class="group bg-white rounded-xl border border-gray-200 px-3 py-2.5 shadow-sm cursor-pointer hover:border-gray-300 hover:shadow transition-all"
                                >
                                    <!-- Label bars -->
                                    <div v-if="card.labels.length" class="flex flex-wrap gap-1 mb-1.5">
                                        <span
                                            v-for="l in card.labels"
                                            :key="l.id"
                                            :class="['h-2 rounded-full', l.name ? 'px-2 h-4 text-[9px] font-bold flex items-center' : 'w-9', labelClass(l.color)]"
                                        >{{ l.name }}</span>
                                    </div>

                                    <p class="text-sm text-gray-800 leading-snug break-words">{{ card.title }}</p>

                                    <!-- Meta badges -->
                                    <div v-if="hasMeta(card)" class="mt-1.5 flex flex-wrap items-center gap-2 text-gray-400">
                                        <span v-if="card.due_date" :class="['inline-flex items-center gap-1 text-[10px] font-semibold px-1.5 py-0.5 rounded', dueClass(card)]">
                                            <ClockIcon class="w-3 h-3" /> {{ shortDue(card.due_date) }}
                                        </span>
                                        <span v-if="card.checklist_total > 0" :class="[
                                            'inline-flex items-center gap-1 text-[10px] font-semibold px-1.5 py-0.5 rounded',
                                            card.checklist_done === card.checklist_total ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500',
                                        ]">
                                            <CheckCircleIcon class="w-3 h-3" /> {{ card.checklist_done }}/{{ card.checklist_total }}
                                        </span>
                                        <span v-if="card.description" title="Has description"><Bars3BottomLeftIcon class="w-3.5 h-3.5" /></span>
                                        <span v-if="card.attachments.length" class="inline-flex items-center gap-0.5 text-[10px]">
                                            <PaperClipIcon class="w-3.5 h-3.5" />{{ card.attachments.length }}
                                        </span>
                                        <span v-if="card.comments.length" class="inline-flex items-center gap-0.5 text-[10px]">
                                            <ChatBubbleLeftRightIcon class="w-3.5 h-3.5" />{{ card.comments.length }}
                                        </span>
                                    </div>
                                </div>
                            </template>
                        </draggable>

                        <!-- Add card -->
                        <div class="px-2 pb-2 pt-1">
                            <div v-if="addingCardListId === list.id">
                                <textarea
                                    :ref="el => bindCardInput(el, list.id)"
                                    v-model="newCardTitle"
                                    @keydown.enter.prevent="addCard(list)"
                                    @keydown.esc="addingCardListId = null"
                                    rows="2"
                                    placeholder="Enter a title…"
                                    class="w-full text-sm border border-gray-200 rounded-lg px-2 py-1.5 resize-none focus:outline-none focus:ring-2 focus:ring-[#EF233C]/15 focus:border-[#EF233C]/40"
                                />
                                <div class="flex gap-1 mt-1">
                                    <button @click="addCard(list)" class="text-xs font-semibold bg-[#2B2D42] hover:bg-[#EF233C] text-white px-3 py-1.5 rounded-lg transition-colors">Add card</button>
                                    <button @click="addingCardListId = null" class="text-xs text-gray-400 px-2">Cancel</button>
                                </div>
                            </div>
                            <button
                                v-else
                                @click="startAddCard(list)"
                                class="w-full flex items-center gap-1.5 text-xs font-medium text-gray-500 hover:text-gray-700 hover:bg-white/70 px-2 py-2 rounded-lg transition-colors"
                            >
                                <PlusIcon class="w-4 h-4" /> Add a card
                            </button>
                        </div>
                    </div>
                </template>

                <!-- Add another list -->
                <template #footer>
                    <div class="flex-shrink-0 w-72">
                        <div v-if="addingList" class="bg-[#EDF2F4] rounded-2xl p-2">
                            <input
                                ref="listInput"
                                v-model="newListName"
                                @keydown.enter.prevent="addList"
                                @keydown.esc="addingList = false"
                                type="text"
                                placeholder="Enter list name…"
                                class="w-full text-sm border border-gray-200 rounded-lg px-2 py-1.5 focus:outline-none focus:ring-2 focus:ring-[#EF233C]/15"
                            />
                            <div class="flex gap-1 mt-1">
                                <button @click="addList" class="text-xs font-semibold bg-[#2B2D42] hover:bg-[#EF233C] text-white px-3 py-1.5 rounded-lg transition-colors">Add list</button>
                                <button @click="addingList = false" class="text-xs text-gray-400 px-2">Cancel</button>
                            </div>
                        </div>
                        <button
                            v-else
                            @click="startAddList"
                            class="w-full flex items-center gap-1.5 text-sm font-medium text-gray-500 hover:text-gray-700 bg-black/5 hover:bg-black/10 px-3 py-2.5 rounded-2xl transition-colors"
                        >
                            <PlusIcon class="w-4 h-4" /> Add another list
                        </button>
                    </div>
                </template>
            </draggable>
        </div>

        <!-- Card detail modal -->
        <CardModal
            :card="activeCard"
            :list-name="activeCardListName"
            :board-labels="board.labels"
            @close="activeCardId = null"
            @delete="deleteCard"
        />
    </AppLayout>
</template>

<script setup>
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import draggable from 'vuedraggable';
import AppLayout from '@/Layouts/AppLayout.vue';
import CardModal from '@/Components/Boards/CardModal.vue';
import {
    ViewColumnsIcon, ChevronDownIcon, PlusIcon, TrashIcon,
    PencilSquareIcon, CheckCircleIcon, ClockIcon, Bars3BottomLeftIcon,
    PaperClipIcon, ChatBubbleLeftRightIcon,
} from '@heroicons/vue/24/outline';
import dayjs from 'dayjs';

const props = defineProps({
    boards: { type: Array, default: () => [] },
    board:  { type: Object, required: true },
});

const opts = { preserveScroll: true, preserveState: true };

// ── Local list/card state for drag (rebuilt from server) ──────────────────────

const lists = ref([]);
function buildLists(board) {
    lists.value = board.lists.map(l => ({ ...l, cards: [...l.cards] }));
}
watch(() => props.board, (b) => buildLists(b), { immediate: true });

// ── Drag handlers ─────────────────────────────────────────────────────────────

function onListChange(evt) {
    if (!(evt.moved || evt.added)) return;
    router.post(route('boards.lists.reorder'), { ids: lists.value.map(l => l.id) }, opts);
}

function onCardChange(evt, list) {
    const change = evt.added ?? evt.moved;
    if (!change) return;
    const card = change.element;
    router.post(route('boards.cards.move', card.id), {
        list_id: list.id,
        ids: list.cards.map(c => c.id),
    }, opts);
}

// ── Card modal ────────────────────────────────────────────────────────────────

const activeCardId = ref(null);

const activeCard = computed(() => {
    if (!activeCardId.value) return null;
    for (const l of lists.value) {
        const c = l.cards.find(c => c.id === activeCardId.value);
        if (c) return c;
    }
    return null;
});
const activeCardListName = computed(() => {
    if (!activeCardId.value) return '';
    const l = lists.value.find(l => l.cards.some(c => c.id === activeCardId.value));
    return l?.name ?? '';
});

function openCard(card) { activeCardId.value = card.id; }

// ── Card-face helpers ─────────────────────────────────────────────────────────

const LABEL_CLASSES = {
    green:  'bg-emerald-300 text-emerald-900',
    yellow: 'bg-yellow-300 text-yellow-900',
    orange: 'bg-orange-300 text-orange-900',
    red:    'bg-red-300 text-red-900',
    purple: 'bg-purple-300 text-purple-900',
    blue:   'bg-sky-300 text-sky-900',
};
function labelClass(c) { return LABEL_CLASSES[c] ?? 'bg-gray-300 text-gray-800'; }

function hasMeta(card) {
    return card.due_date || card.checklist_total > 0 || card.description
        || card.attachments.length || card.comments.length;
}

function shortDue(iso) { return dayjs(iso).format('D MMM'); }

function dueClass(card) {
    if (card.due_done) return 'bg-emerald-100 text-emerald-700';
    const due = dayjs(card.due_date);
    if (due.isBefore(dayjs())) return 'bg-red-100 text-red-700';
    if (due.isBefore(dayjs().add(1, 'day'))) return 'bg-amber-100 text-amber-700';
    return 'bg-gray-100 text-gray-500';
}

function deleteCard(card) {
    activeCardId.value = null;
    router.delete(route('boards.cards.destroy', card.id), opts);
}

// ── Add card ──────────────────────────────────────────────────────────────────

const addingCardListId = ref(null);
const newCardTitle = ref('');
const cardInputs = {};
function bindCardInput(el, id) { if (el) cardInputs[id] = el; }

function startAddCard(list) {
    addingCardListId.value = list.id;
    newCardTitle.value = '';
    nextTick(() => cardInputs[list.id]?.focus());
}
function addCard(list) {
    const title = newCardTitle.value.trim();
    if (!title) return;
    newCardTitle.value = '';
    router.post(route('boards.cards.store', list.id), { title }, {
        ...opts,
        onSuccess: () => nextTick(() => cardInputs[list.id]?.focus()),
    });
}

// ── Lists: add / rename / delete ──────────────────────────────────────────────

const addingList = ref(false);
const newListName = ref('');
const listInput = ref(null);

function startAddList() {
    addingList.value = true;
    nextTick(() => listInput.value?.focus());
}
function addList() {
    const name = newListName.value.trim();
    if (!name) return;
    newListName.value = '';
    addingList.value = false;
    router.post(route('boards.lists.store', props.board.id), { name }, opts);
}

const renamingListId = ref(null);
const listNameDraft = ref('');
const listRenameInputs = {};
function bindListRename(el, id) { if (el) listRenameInputs[id] = el; }

function startRenameList(list) {
    renamingListId.value = list.id;
    listNameDraft.value = list.name;
    nextTick(() => listRenameInputs[list.id]?.focus());
}
function saveListName(list) {
    const name = listNameDraft.value.trim();
    renamingListId.value = null;
    if (!name || name === list.name) return;
    router.patch(route('boards.lists.update', list.id), { name }, opts);
}

function deleteList(list) {
    const msg = list.cards.length
        ? `Delete "${list.name}" and its ${list.cards.length} card(s)?`
        : `Delete "${list.name}"?`;
    if (!confirm(msg)) return;
    router.delete(route('boards.lists.destroy', list.id), opts);
}

// ── Boards: switcher / add / rename / delete ──────────────────────────────────

const switcherOpen = ref(false);
const switcherEl = ref(null);

const addingBoard = ref(false);
const newBoardName = ref('');
const boardInput = ref(null);

function startAddBoard() {
    addingBoard.value = true;
    nextTick(() => boardInput.value?.focus());
}
function createBoard() {
    const name = newBoardName.value.trim();
    if (!name) return;
    newBoardName.value = '';
    addingBoard.value = false;
    switcherOpen.value = false;
    router.post(route('boards.store'), { name }); // redirects to new board
}

const renamingBoard = ref(false);
const boardNameDraft = ref('');
const boardRenameInput = ref(null);

function startRenameBoard() {
    renamingBoard.value = true;
    boardNameDraft.value = props.board.name;
    nextTick(() => boardRenameInput.value?.focus());
}
function saveBoardName() {
    const name = boardNameDraft.value.trim();
    renamingBoard.value = false;
    if (!name || name === props.board.name) return;
    router.patch(route('boards.update', props.board.id), { name }, opts);
}

function deleteBoard() {
    if (!confirm(`Delete board "${props.board.name}" and everything in it?`)) return;
    router.delete(route('boards.destroy', props.board.id));
}

// Close switcher on outside click
function onClickOutside(e) {
    if (switcherEl.value && !switcherEl.value.contains(e.target)) switcherOpen.value = false;
}
onMounted(() => document.addEventListener('mousedown', onClickOutside));
onUnmounted(() => document.removeEventListener('mousedown', onClickOutside));
</script>

<style scoped>
.card-ghost { opacity: 0.4; border: 2px dashed #9ca3af !important; }
.card-drag { transform: rotate(2deg); box-shadow: 0 10px 30px -8px rgba(43,45,66,0.35); }
</style>
