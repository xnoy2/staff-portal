<template>
    <AppLayout title="Boards">
        <div class="flex h-[calc(100vh-4rem)] -m-4 sm:-m-6">
            <WorkspaceNav :workspaces="nav" current-workspace-id="" section="boards" />

            <main class="flex-1 overflow-y-auto flex items-center justify-center p-6">
                <div class="text-center max-w-md">
                    <span class="inline-flex w-14 h-14 rounded-2xl bg-[#EDF2F4] items-center justify-center mb-4">
                        <ViewColumnsIcon class="w-7 h-7 text-gray-400" />
                    </span>

                    <h1 class="text-lg font-bold text-[#2B2D42] mb-1.5">No workspaces yet</h1>

                    <template v-if="canCreate">
                        <p class="text-sm text-gray-500 mb-5">
                            Create a workspace to start organising boards, then add staff as members.
                        </p>

                        <div v-if="creating" class="max-w-xs mx-auto text-left">
                            <input
                                ref="nameInput"
                                v-model="newName"
                                @keydown.enter.prevent="create"
                                @keydown.esc="creating = false"
                                placeholder="Workspace name…"
                                class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#EF233C]/15"
                            />
                            <div class="flex gap-1.5 mt-2">
                                <button @click="create" class="text-sm font-semibold bg-[#2B2D42] hover:bg-[#EF233C] text-white px-4 py-2 rounded-lg transition-colors">Create workspace</button>
                                <button @click="creating = false" class="text-sm text-gray-400 px-2">Cancel</button>
                            </div>
                        </div>
                        <button
                            v-else
                            @click="startCreate"
                            class="inline-flex items-center gap-1.5 text-sm font-semibold bg-[#2B2D42] hover:bg-[#EF233C] text-white px-4 py-2.5 rounded-lg transition-colors"
                        >
                            <PlusIcon class="w-4 h-4" /> Create workspace
                        </button>
                    </template>

                    <p v-else class="text-sm text-gray-500">
                        You haven't been added to any workspace yet. An admin will add you to a
                        workspace before its boards appear here.
                    </p>
                </div>
            </main>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, nextTick } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import WorkspaceNav from '@/Components/Boards/WorkspaceNav.vue';
import { ViewColumnsIcon, PlusIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    nav:       { type: Array,   default: () => [] },
    can_create: { type: Boolean, default: false },
});

// keep template tidy
const canCreate = props.can_create;

const creating = ref(false);
const newName = ref('');
const nameInput = ref(null);

function startCreate() {
    creating.value = true;
    nextTick(() => nameInput.value?.focus());
}
function create() {
    const name = newName.value.trim();
    if (!name) return;
    newName.value = '';
    creating.value = false;
    router.post(route('workspaces.store'), { name });
}
</script>
