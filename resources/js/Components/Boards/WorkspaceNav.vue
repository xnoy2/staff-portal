<template>
    <aside class="w-60 flex-shrink-0 bg-white border-r border-gray-200 overflow-y-auto py-4 px-3">
        <div class="flex items-center justify-between px-2 mb-2">
            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Workspaces</p>
            <button v-if="canManage" @click="startCreate" class="p-1 rounded text-gray-400 hover:text-gray-700 hover:bg-gray-100" title="Create workspace">
                <PlusIcon class="w-4 h-4" />
            </button>
        </div>

        <!-- Empty hint when the user belongs to no workspaces -->
        <p v-if="!workspaces.length" class="px-2 text-xs text-gray-400 leading-relaxed">
            {{ canManage ? 'Create a workspace to get started.' : 'No workspaces yet. An admin will add you to one.' }}
        </p>

        <!-- Create form -->
        <div v-if="creating" class="px-2 mb-2">
            <input
                ref="createInput"
                v-model="newName"
                @keydown.enter.prevent="create"
                @keydown.esc="creating = false"
                placeholder="Workspace name…"
                class="w-full text-sm border border-gray-200 rounded-lg px-2 py-1.5 focus:outline-none focus:ring-2 focus:ring-[#EF233C]/15"
            />
            <div class="flex gap-1 mt-1">
                <button @click="create" class="text-xs font-semibold bg-[#2B2D42] text-white px-3 py-1.5 rounded-lg">Create</button>
                <button @click="creating = false" class="text-xs text-gray-400 px-2">Cancel</button>
            </div>
        </div>

        <nav class="space-y-1">
            <div v-for="ws in workspaces" :key="ws.id">
                <!-- Workspace header -->
                <button
                    @click="toggle(ws.id)"
                    class="w-full flex items-center gap-2.5 px-2 py-2 rounded-lg hover:bg-gray-50 transition-colors"
                >
                    <span :class="['w-7 h-7 rounded-lg flex items-center justify-center text-white text-xs font-bold flex-shrink-0', colorClass(ws.color)]">
                        {{ initial(ws.name) }}
                    </span>
                    <span class="flex-1 text-left text-sm font-semibold text-gray-800 truncate">{{ ws.name }}</span>
                    <ChevronDownIcon :class="['w-4 h-4 text-gray-400 transition-transform', isOpen(ws.id) ? '' : '-rotate-90']" />
                </button>

                <!-- Sections -->
                <div v-if="isOpen(ws.id)" class="ml-2 pl-3 border-l border-gray-100 py-0.5 space-y-0.5">
                    <Link
                        :href="route('workspaces.show', ws.id)"
                        :class="sectionClass(ws.id, 'boards')"
                    >
                        <ViewColumnsIcon class="w-4 h-4" /> Boards
                    </Link>
                    <Link
                        :href="route('workspaces.members', ws.id)"
                        :class="sectionClass(ws.id, 'members')"
                    >
                        <UsersIcon class="w-4 h-4" /> Members
                    </Link>
                    <Link
                        :href="route('workspaces.settings', ws.id)"
                        :class="sectionClass(ws.id, 'settings')"
                    >
                        <Cog6ToothIcon class="w-4 h-4" /> Settings
                    </Link>
                </div>
            </div>
        </nav>
    </aside>
</template>

<script setup>
import { ref, computed, nextTick } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { PlusIcon, ChevronDownIcon, ViewColumnsIcon, UsersIcon, Cog6ToothIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    workspaces:         { type: Array,  default: () => [] },
    currentWorkspaceId: { type: String, default: '' },
    section:            { type: String, default: 'boards' }, // boards | members | settings
});

// Only admins/managers may create workspaces.
const canManage = computed(() => {
    const roles = usePage().props.auth?.user?.roles ?? [];
    return roles.includes('admin') || roles.includes('manager');
});

const COLORS = {
    blue:   'bg-blue-500',
    green:  'bg-emerald-500',
    orange: 'bg-orange-500',
    red:    'bg-red-500',
    purple: 'bg-purple-500',
    pink:   'bg-pink-500',
    slate:  'bg-slate-500',
};
function colorClass(c) { return COLORS[c] ?? 'bg-blue-500'; }
function initial(name) { return (name?.trim()?.[0] ?? 'W').toUpperCase(); }

// Expansion — open the current workspace by default
const openIds = ref(new Set(props.currentWorkspaceId ? [props.currentWorkspaceId] : []));
function isOpen(id) { return openIds.value.has(id); }
function toggle(id) {
    const s = new Set(openIds.value);
    s.has(id) ? s.delete(id) : s.add(id);
    openIds.value = s;
}

function sectionClass(wsId, sec) {
    const active = wsId === props.currentWorkspaceId && sec === props.section;
    return [
        'flex items-center gap-2 px-2 py-1.5 rounded-lg text-sm transition-colors',
        active ? 'bg-[#EF233C]/8 text-[#EF233C] font-medium' : 'text-gray-600 hover:bg-gray-50',
    ];
}

// Create workspace
const creating = ref(false);
const newName = ref('');
const createInput = ref(null);
function startCreate() {
    creating.value = true;
    nextTick(() => createInput.value?.focus());
}
function create() {
    const name = newName.value.trim();
    if (!name) return;
    newName.value = '';
    creating.value = false;
    router.post(route('workspaces.store'), { name });
}
</script>
