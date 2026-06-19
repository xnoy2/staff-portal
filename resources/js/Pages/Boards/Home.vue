<template>
    <AppLayout title="Boards">
        <div class="flex h-[calc(100vh-4rem)] -m-4 sm:-m-6">
            <WorkspaceNav :workspaces="nav" :current-workspace-id="workspace.id" section="boards" />

            <main class="flex-1 overflow-y-auto p-6">
                <!-- Workspace header -->
                <div class="flex items-center gap-3 mb-6">
                    <span :class="['w-10 h-10 rounded-xl flex items-center justify-center text-white text-base font-bold', colorClass(workspace.color)]">
                        {{ initial(workspace.name) }}
                    </span>
                    <div>
                        <h1 class="text-lg font-bold text-[#2B2D42]">{{ workspace.name }}</h1>
                        <p class="text-xs text-gray-400">{{ workspace.member_count }} member{{ workspace.member_count !== 1 ? 's' : '' }}</p>
                    </div>
                </div>

                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Boards</p>

                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                    <!-- Board tiles -->
                    <Link
                        v-for="b in boards"
                        :key="b.id"
                        :href="route('boards.show', b.id)"
                        :class="['h-24 rounded-xl p-3 flex items-end text-white font-bold text-sm shadow-sm hover:shadow-md hover:brightness-105 transition-all', tileColor(workspace.color)]"
                    >
                        <span class="line-clamp-2">{{ b.name }}</span>
                    </Link>

                    <!-- Create board -->
                    <div v-if="creating" class="h-24 rounded-xl bg-gray-100 p-2 flex flex-col">
                        <input
                            ref="boardInput"
                            v-model="newBoard"
                            @keydown.enter.prevent="createBoard"
                            @keydown.esc="creating = false"
                            placeholder="Board title…"
                            class="w-full text-sm border border-gray-200 rounded-lg px-2 py-1.5 focus:outline-none focus:ring-2 focus:ring-[#EF233C]/15"
                        />
                        <div class="flex gap-1 mt-auto">
                            <button @click="createBoard" class="text-xs font-semibold bg-[#2B2D42] text-white px-2.5 py-1 rounded-lg">Create</button>
                            <button @click="creating = false" class="text-xs text-gray-400 px-1">Cancel</button>
                        </div>
                    </div>
                    <button
                        v-else
                        @click="startCreate"
                        class="h-24 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-500 text-sm font-medium flex items-center justify-center gap-1.5 transition-colors"
                    >
                        <PlusIcon class="w-4 h-4" /> Create board
                    </button>
                </div>
            </main>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, nextTick } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import WorkspaceNav from '@/Components/Boards/WorkspaceNav.vue';
import { PlusIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    nav:       { type: Array,  default: () => [] },
    workspace: { type: Object, required: true },
    boards:    { type: Array,  default: () => [] },
});

const SQUARE = {
    blue: 'bg-blue-500', green: 'bg-emerald-500', orange: 'bg-orange-500',
    red: 'bg-red-500', purple: 'bg-purple-500', pink: 'bg-pink-500', slate: 'bg-slate-500',
};
const TILE = {
    blue: 'bg-blue-600', green: 'bg-emerald-600', orange: 'bg-orange-600',
    red: 'bg-red-600', purple: 'bg-purple-600', pink: 'bg-pink-600', slate: 'bg-slate-600',
};
function colorClass(c) { return SQUARE[c] ?? 'bg-blue-500'; }
function tileColor(c) { return TILE[c] ?? 'bg-blue-600'; }
function initial(name) { return (name?.trim()?.[0] ?? 'W').toUpperCase(); }

const creating = ref(false);
const newBoard = ref('');
const boardInput = ref(null);
function startCreate() {
    creating.value = true;
    nextTick(() => boardInput.value?.focus());
}
function createBoard() {
    const name = newBoard.value.trim();
    if (!name) return;
    newBoard.value = '';
    creating.value = false;
    router.post(route('workspaces.boards.store', props.workspace.id), { name });
}
</script>
