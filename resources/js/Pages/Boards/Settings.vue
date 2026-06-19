<template>
    <AppLayout title="Workspace settings">
        <div class="flex h-[calc(100vh-4rem)] -m-4 sm:-m-6">
            <WorkspaceNav :workspaces="nav" :current-workspace-id="workspace.id" section="settings" />

            <main class="flex-1 overflow-y-auto p-6">
                <div class="max-w-lg">
                    <h1 class="text-lg font-bold text-[#2B2D42] mb-6">Workspace settings</h1>

                    <div v-if="!workspace.is_owner" class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-sm text-amber-800 mb-4">
                        Only the workspace owner can change these settings.
                    </div>

                    <!-- Name -->
                    <div class="bg-white border border-gray-200 rounded-xl p-4 mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Name</label>
                        <div class="flex gap-2">
                            <input
                                v-model="name"
                                :disabled="!workspace.is_owner"
                                class="flex-1 text-sm border border-gray-200 rounded-lg px-3 py-2 disabled:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-[#EF233C]/15"
                            />
                            <button
                                v-if="workspace.is_owner"
                                @click="saveName"
                                :disabled="!name.trim() || name === workspace.name"
                                class="text-sm font-semibold bg-[#2B2D42] hover:bg-[#EF233C] disabled:opacity-40 text-white px-4 py-2 rounded-lg transition-colors"
                            >Save</button>
                        </div>
                    </div>

                    <!-- Color -->
                    <div class="bg-white border border-gray-200 rounded-xl p-4 mb-4">
                        <p class="text-sm font-semibold text-gray-700 mb-2">Color</p>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="c in colors"
                                :key="c"
                                @click="workspace.is_owner && setColor(c)"
                                :class="[
                                    'w-9 h-9 rounded-lg transition-all', squareColor(c),
                                    workspace.color === c ? 'ring-2 ring-offset-2 ring-gray-500' : 'hover:scale-105',
                                    !workspace.is_owner ? 'cursor-not-allowed opacity-70' : '',
                                ]"
                            />
                        </div>
                    </div>

                    <!-- Danger zone -->
                    <div v-if="workspace.is_owner" class="bg-white border border-red-200 rounded-xl p-4">
                        <p class="text-sm font-semibold text-red-600 mb-1">Delete workspace</p>
                        <p class="text-xs text-gray-500 mb-3">This permanently deletes the workspace and all its boards, lists and cards.</p>
                        <button @click="destroy" class="text-sm font-semibold text-red-600 border border-red-200 hover:bg-red-50 px-4 py-2 rounded-lg transition-colors">
                            Delete this workspace
                        </button>
                    </div>
                </div>
            </main>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import WorkspaceNav from '@/Components/Boards/WorkspaceNav.vue';

const props = defineProps({
    nav:       { type: Array,  default: () => [] },
    workspace: { type: Object, required: true },
    colors:    { type: Array,  default: () => [] },
});

const SQUARE = {
    blue: 'bg-blue-500', green: 'bg-emerald-500', orange: 'bg-orange-500',
    red: 'bg-red-500', purple: 'bg-purple-500', pink: 'bg-pink-500', slate: 'bg-slate-500',
};
function squareColor(c) { return SQUARE[c] ?? 'bg-blue-500'; }

const name = ref(props.workspace.name);

function saveName() {
    router.patch(route('workspaces.update', props.workspace.id), { name: name.value.trim() }, { preserveScroll: true });
}
function setColor(c) {
    router.patch(route('workspaces.update', props.workspace.id), { color: c }, { preserveScroll: true });
}
function destroy() {
    if (!confirm(`Delete "${props.workspace.name}" and everything in it? This cannot be undone.`)) return;
    router.delete(route('workspaces.destroy', props.workspace.id));
}
</script>
