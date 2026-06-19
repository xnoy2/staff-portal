<template>
    <AppLayout title="Members">
        <div class="flex h-[calc(100vh-4rem)] -m-4 sm:-m-6">
            <WorkspaceNav :workspaces="nav" :current-workspace-id="workspace.id" section="members" />

            <main class="flex-1 overflow-y-auto p-6">
                <div class="max-w-2xl">
                    <div class="flex items-center gap-3 mb-6">
                        <span :class="['w-10 h-10 rounded-xl flex items-center justify-center text-white text-base font-bold', colorClass(workspace.color)]">
                            {{ initial(workspace.name) }}
                        </span>
                        <div>
                            <h1 class="text-lg font-bold text-[#2B2D42]">{{ workspace.name }}</h1>
                            <p class="text-xs text-gray-400">Members</p>
                        </div>
                    </div>

                    <!-- Add member (admin/manager only) -->
                    <div v-if="workspace.can_manage" class="bg-white border border-gray-200 rounded-xl p-4 mb-5">
                        <p class="text-sm font-semibold text-gray-700 mb-2">Add a member</p>
                        <div class="flex gap-2">
                            <select v-model="selectedUser" class="flex-1 text-sm border-gray-200 rounded-lg focus:ring-[#EF233C] focus:border-[#EF233C]">
                                <option value="">Select staff…</option>
                                <option v-for="c in candidates" :key="c.id" :value="c.id">{{ c.name }}</option>
                            </select>
                            <button
                                @click="addMember"
                                :disabled="!selectedUser"
                                class="text-sm font-semibold bg-[#2B2D42] hover:bg-[#EF233C] disabled:opacity-40 text-white px-4 py-2 rounded-lg transition-colors"
                            >Add</button>
                        </div>
                        <p v-if="candidates.length === 0" class="text-xs text-gray-400 mt-2">All active staff are already members.</p>
                    </div>

                    <!-- Member list -->
                    <div class="bg-white border border-gray-200 rounded-xl divide-y divide-gray-100">
                        <div v-for="m in members" :key="m.id" class="flex items-center gap-3 p-3">
                            <img :src="m.avatar_url" :alt="m.name" class="w-9 h-9 rounded-full object-cover flex-shrink-0" />
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-gray-800 truncate">{{ m.name }}<span v-if="m.id === me.id" class="text-gray-400 font-normal"> (you)</span></p>
                                <p class="text-xs text-gray-400 capitalize">{{ m.role }}</p>
                            </div>

                            <!-- Management controls (admin/manager only) -->
                            <div v-if="workspace.can_manage" class="flex items-center gap-2">
                                <select
                                    :value="m.role"
                                    @change="changeRole(m, $event.target.value)"
                                    class="text-xs border-gray-200 rounded-lg py-1 focus:ring-[#EF233C] focus:border-[#EF233C]"
                                >
                                    <option value="owner">Owner</option>
                                    <option value="member">Member</option>
                                </select>
                                <button @click="removeMember(m)" class="p-1.5 rounded-lg text-gray-300 hover:text-red-500 hover:bg-red-50" title="Remove">
                                    <TrashIcon class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import WorkspaceNav from '@/Components/Boards/WorkspaceNav.vue';
import { TrashIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    nav:        { type: Array,  default: () => [] },
    workspace:  { type: Object, required: true },
    members:    { type: Array,  default: () => [] },
    candidates: { type: Array,  default: () => [] },
});

const me = computed(() => usePage().props.auth.user);

const COLORS = {
    blue: 'bg-blue-500', green: 'bg-emerald-500', orange: 'bg-orange-500',
    red: 'bg-red-500', purple: 'bg-purple-500', pink: 'bg-pink-500', slate: 'bg-slate-500',
};
function colorClass(c) { return COLORS[c] ?? 'bg-blue-500'; }
function initial(name) { return (name?.trim()?.[0] ?? 'W').toUpperCase(); }

const selectedUser = ref('');
function addMember() {
    if (!selectedUser.value) return;
    router.post(route('workspaces.members.store', props.workspace.id), { user_id: selectedUser.value }, {
        preserveScroll: true,
        onSuccess: () => { selectedUser.value = ''; },
    });
}
function changeRole(m, role) {
    router.patch(route('workspaces.members.update', [props.workspace.id, m.id]), { role }, { preserveScroll: true });
}
function removeMember(m) {
    const self = m.id === me.value.id;
    if (!confirm(self ? 'Leave this workspace?' : `Remove ${m.name} from this workspace?`)) return;
    router.delete(route('workspaces.members.destroy', [props.workspace.id, m.id]), { preserveScroll: true });
}
</script>
