<template>
    <AppLayout title="Boards">
        <div class="flex h-[calc(100vh-4rem)] -m-4 sm:-m-6">
            <WorkspaceNav :workspaces="nav" :current-workspace-id="workspace.id" section="boards" />

            <main class="flex-1 min-w-0 flex flex-col p-4">

        <!-- ── Board header ─────────────────────────────────────────────────── -->
        <div class="flex items-center gap-2 mb-3 flex-shrink-0">
            <Link :href="route('workspaces.show', workspace.id)" class="text-sm text-gray-400 hover:text-gray-700 truncate max-w-[30vw]">{{ workspace.name }}</Link>
            <ChevronRightIcon class="w-3.5 h-3.5 text-gray-300 flex-shrink-0" />

            <!-- Rename current board -->
            <input
                v-if="renamingBoard"
                ref="boardRenameInput"
                v-model="boardNameDraft"
                @blur="saveBoardName"
                @keydown.enter.prevent="$event.target.blur()"
                @keydown.esc="renamingBoard = false"
                class="text-base font-bold text-[#2B2D42] border border-gray-200 rounded-lg px-2 py-1 focus:outline-none focus:ring-2 focus:ring-[#EF233C]/15"
            />
            <button v-else @click="startRenameBoard" class="text-base font-bold text-[#2B2D42] hover:bg-gray-100 rounded-lg px-2 py-1 truncate" title="Rename board">
                {{ board.name }}
            </button>

            <!-- View switcher -->
            <ViewSwitcher v-model="view" class="ml-auto flex-shrink-0" />

            <button
                @click="deleteBoard"
                class="text-xs text-gray-300 hover:text-red-500 px-2 py-1.5 rounded-lg hover:bg-red-50 flex-shrink-0"
                title="Delete this board"
            >
                <TrashIcon class="w-4 h-4" />
            </button>
        </div>

        <!-- ── Board view (lists + drag-and-drop) ───────────────────────────── -->
        <div v-show="view === 'board'" class="flex-1 overflow-x-auto overflow-y-hidden pb-2">
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
                    <div
                        :data-list-id="list.id"
                        :class="['flex-shrink-0 w-72 rounded-2xl flex flex-col max-h-[calc(100vh-13rem)] ring-2 ring-inset transition-[background-color,box-shadow] duration-150',
                                 dragOverListId === list.id ? 'bg-[#EF233C]/5 ring-[#EF233C]/40' : 'bg-[#EDF2F4] ring-transparent']"
                    >
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
                            class="flex-1 overflow-y-auto px-2 pb-1 space-y-2 min-h-[44px]"
                            ghost-class="card-ghost"
                            drag-class="sjs-clone-hidden"
                            fallback-class="sjs-clone-hidden"
                            :force-fallback="true"
                            :fallback-tolerance="4"
                            :animation="180"
                            @change="onCardChange($event, list)"
                            @start="onDragStart"
                            @end="onDragEnd"
                        >
                            <template #item="{ element: card }">
                                <div
                                    @click="openCard(card)"
                                    @pointerdown="onCardPointerDown"
                                    class="group select-none bg-white rounded-xl border border-gray-200 px-3 py-2.5 shadow-sm cursor-pointer hover:border-gray-300 hover:shadow transition-all"
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
                                        <span v-if="card.recurring && card.recurring !== 'never'" title="Recurring"><ArrowPathIcon class="w-3.5 h-3.5" /></span>
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

        <!-- ── Table view ───────────────────────────────────────────────────── -->
        <TableView
            v-if="view === 'table'"
            :cards="allCards"
            @open-card="openCard"
        />

        <!-- ── Calendar view ────────────────────────────────────────────────── -->
        <CalendarView
            v-if="view === 'calendar'"
            :cards="allCards"
            @open-card="openCard"
        />

        <!-- ── Timeline view ────────────────────────────────────────────────── -->
        <TimelineView
            v-if="view === 'timeline'"
            :cards="allCards"
            @open-card="openCard"
        />
            </main>
        </div>

        <!-- Card detail modal -->
        <CardModal
            :card="activeCard"
            :list-name="activeCardListName"
            :board-labels="board.labels"
            :members="members"
            @close="activeCardId = null"
            @delete="deleteCard"
        />

        <!-- Delete list confirmation -->
        <ConfirmModal
            :open="!!listToDelete"
            title="Delete list?"
            :message="deleteListMessage"
            confirmLabel="Delete"
            danger
            @confirm="confirmDeleteList"
            @cancel="listToDelete = null"
        />

        <!-- Delete board confirmation -->
        <ConfirmModal
            :open="boardDeleteOpen"
            title="Delete board?"
            :message="`“${board.name}” and all its lists and cards will be permanently deleted.`"
            confirmLabel="Delete"
            danger
            @confirm="confirmDeleteBoard"
            @cancel="boardDeleteOpen = false"
        />
    </AppLayout>
</template>

<script setup>
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import draggable from 'vuedraggable';
import AppLayout from '@/Layouts/AppLayout.vue';
import CardModal from '@/Components/Boards/CardModal.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import WorkspaceNav from '@/Components/Boards/WorkspaceNav.vue';
import ViewSwitcher from '@/Components/Boards/ViewSwitcher.vue';
import TableView from '@/Components/Boards/TableView.vue';
import CalendarView from '@/Components/Boards/CalendarView.vue';
import TimelineView from '@/Components/Boards/TimelineView.vue';
import { flattenCards } from '@/Components/Boards/cardHelpers';
import {
    PlusIcon, TrashIcon, ChevronRightIcon,
    CheckCircleIcon, ClockIcon, Bars3BottomLeftIcon,
    PaperClipIcon, ChatBubbleLeftRightIcon, ArrowPathIcon,
} from '@heroicons/vue/24/outline';
import dayjs from 'dayjs';

const props = defineProps({
    nav:       { type: Array,  default: () => [] },
    workspace: { type: Object, required: true },
    board:     { type: Object, required: true },
    members:   { type: Array,  default: () => [] },
});

const opts = { preserveScroll: true, preserveState: true };

// ── Local list/card state for drag (rebuilt from server) ──────────────────────

const lists = ref([]);
function buildLists(board) {
    lists.value = board.lists.map(l => ({ ...l, cards: [...l.cards] }));
}
watch(() => props.board, (b) => buildLists(b), { immediate: true });

// ── View switching (Board / Table / Calendar / Timeline) ──────────────────────
// All views render the same `lists` data; only the Board view is interactive
// (drag-and-drop). The choice is remembered per board via localStorage.

const VALID_VIEWS = ['board', 'table', 'calendar', 'timeline'];

function viewStorageKey(boardId) { return `board-view:${boardId}`; }

function loadView(boardId) {
    try {
        const saved = localStorage.getItem(viewStorageKey(boardId));
        return VALID_VIEWS.includes(saved) ? saved : 'board';
    } catch { return 'board'; }
}

const view = ref(loadView(props.board.id));

watch(view, (v) => {
    try { localStorage.setItem(viewStorageKey(props.board.id), v); } catch { /* ignore */ }
});

// When navigating to a different board, restore that board's saved view.
watch(() => props.board.id, (id) => { view.value = loadView(id); });

// Flat list of every card (tagged with its list) for the non-board views.
const allCards = computed(() => flattenCards(lists.value));

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

// ── Trello-style drag visual ───────────────────────────────────────────────────
// SortableJS (fallback/pointer mode) drives the sorting; its own clone is hidden
// (it mis-positions on this layout). We render our own lifted/tilted clone and
// follow the cursor with high-frequency `pointermove`, applied once per frame via
// requestAnimationFrame for a smooth, composited follow.

let flyEl = null;
let grabDx = 0;
let grabDy = 0;
let lastX = 0;
let lastY = 0;
let rafId = null;
let dirty = false;
let downPos = null;

// Tilt/scale applied AFTER the translate so it only rotates the card, not the
// (large) translation vector — otherwise the clone drifts off the cursor.
const FLY_TILT = ' rotate(3deg) scale(1.02)';

// The list the cursor is currently over, for Trello-style drop-target highlight.
const dragOverListId = ref(null);

function onCardPointerDown(e) {
    // Remember the press point AND the card's rect at that same instant, so the
    // grab offset is exact even if the board scrolls before the drag starts.
    const r = e.currentTarget.getBoundingClientRect();
    downPos = { x: e.clientX, y: e.clientY, left: r.left, top: r.top, width: r.width };
}

function applyFly() {
    if (flyEl && dirty) {
        flyEl.style.transform = `translate3d(${lastX - grabDx}px, ${lastY - grabDy}px, 0)${FLY_TILT}`;
        updateDropTarget();
        dirty = false;
    }
    rafId = requestAnimationFrame(applyFly);
}

// Highlight the list that actually holds the drop placeholder — i.e. exactly where
// SortableJS would drop the card right now (works regardless of column height).
function updateDropTarget() {
    const col = document.querySelector('.card-ghost')?.closest('[data-list-id]');
    dragOverListId.value = col?.getAttribute('data-list-id') ?? null;
}

function onPointerMove(e) {
    lastX = e.clientX;
    lastY = e.clientY;
    dirty = true;
}

function onDragStart(e) {
    const el = e.item;
    const rect = el.getBoundingClientRect();

    flyEl = el.cloneNode(true);
    ['card-ghost', 'sortable-ghost', 'sortable-chosen', 'sortable-drag', 'sjs-clone-hidden']
        .forEach(c => flyEl.classList.remove(c));
    flyEl.classList.add('card-flying');
    flyEl.style.width = `${downPos?.width ?? rect.width}px`;
    document.body.appendChild(flyEl);

    // Grab offset from the press point and the card's rect captured together.
    const px = downPos?.x ?? e.originalEvent?.clientX ?? rect.left + rect.width / 2;
    const py = downPos?.y ?? e.originalEvent?.clientY ?? rect.top + rect.height / 2;
    grabDx = px - (downPos?.left ?? rect.left);
    grabDy = py - (downPos?.top ?? rect.top);
    lastX = px;
    lastY = py;
    // Pivot the tilt/scale around the exact grab point so it stays under the cursor.
    flyEl.style.transformOrigin = `${grabDx}px ${grabDy}px`;
    flyEl.style.transform = `translate3d(${px - grabDx}px, ${py - grabDy}px, 0)${FLY_TILT}`;

    document.addEventListener('pointermove', onPointerMove, { passive: true });
    rafId = requestAnimationFrame(applyFly);
}

function onDragEnd() {
    document.removeEventListener('pointermove', onPointerMove);
    if (rafId) { cancelAnimationFrame(rafId); rafId = null; }
    if (flyEl) { flyEl.remove(); flyEl = null; }
    downPos = null;
    dragOverListId.value = null;
}

// Safety net: if the board unmounts mid-drag (e.g. navigation), tear the drag
// down so we don't leak the rAF loop, the pointer listener or the body clone.
onUnmounted(onDragEnd);

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

// Deep-link: ?card=<id> opens that card (e.g. from a mention notification)
onMounted(() => {
    const id = new URLSearchParams(window.location.search).get('card');
    if (id && lists.value.some(l => l.cards.some(c => c.id === id))) {
        activeCardId.value = id;
    }
});

// ── Card-face helpers ─────────────────────────────────────────────────────────

const LABEL_CLASSES = {
    green:  'bg-emerald-300 text-emerald-900',
    yellow: 'bg-yellow-300 text-yellow-900',
    orange: 'bg-orange-300 text-orange-900',
    red:    'bg-red-300 text-red-900',
    purple: 'bg-purple-300 text-purple-900',
    blue:   'bg-sky-300 text-sky-900',
    pink:   'bg-pink-300 text-pink-900',
    slate:  'bg-slate-300 text-slate-900',
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

const listToDelete = ref(null);

const deleteListMessage = computed(() => {
    const l = listToDelete.value;
    if (!l) return '';
    return l.cards.length
        ? `"${l.name}" and its ${l.cards.length} card${l.cards.length !== 1 ? 's' : ''} will be permanently deleted.`
        : `"${l.name}" will be permanently deleted.`;
});

function deleteList(list) {
    listToDelete.value = list;
}

function confirmDeleteList() {
    const list = listToDelete.value;
    listToDelete.value = null;
    if (!list) return;
    router.delete(route('boards.lists.destroy', list.id), opts);
}

// ── Board: rename / delete ────────────────────────────────────────────────────

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

const boardDeleteOpen = ref(false);

function deleteBoard() {
    boardDeleteOpen.value = true;
}

function confirmDeleteBoard() {
    boardDeleteOpen.value = false;
    router.delete(route('boards.destroy', props.board.id));
}
</script>

<!-- Not scoped: the flying clone is appended to <body>, outside this component's
     subtree, so these classes must be global to reach it. -->
<style>
/* The empty slot left behind in the list — a muted placeholder (Trello-style). */
.card-ghost > * { visibility: hidden; }
.card-ghost {
    background: #e2e8f0 !important;
    border-color: #cbd5e1 !important;
    box-shadow: none !important;
}

/* SortableJS's own drag clone — hidden; we render our own (.card-flying). */
.sjs-clone-hidden { opacity: 0 !important; }

/* Our own drag image: a lifted, tilted, shadowed card that we position on the
   cursor ourselves. The tilt is baked into the JS transform (after the translate)
   so it doesn't skew the position. It must not intercept pointer events so drop
   detection hits the real cards underneath. */
.card-flying {
    position: fixed;
    top: 0;
    left: 0;
    z-index: 9999;
    margin: 0 !important;
    pointer-events: none;
    box-shadow: 0 18px 32px -8px rgba(15, 23, 42, 0.5);
    cursor: grabbing;
    transition: none;
    will-change: transform;
}
</style>
