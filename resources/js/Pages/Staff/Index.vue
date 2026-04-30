<template>
    <AppLayout title="Staff">
        <!-- Header -->
        <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
            <div>
                <h1 class="text-lg font-semibold text-gray-800">Staff</h1>
                <p class="text-xs text-gray-500 mt-0.5">{{ staff.total }} team member{{ staff.total !== 1 ? 's' : '' }}</p>
            </div>
            <Link
                :href="route('staff.create')"
                class="inline-flex items-center gap-1.5 bg-[#EF233C] text-white text-sm px-4 py-2 rounded-lg hover:bg-[#D90429] transition-colors"
            >
                <PlusIcon class="w-4 h-4" />
                Add Staff Member
            </Link>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-xl border border-gray-200 p-4 mb-4">
            <div class="flex flex-col sm:flex-row gap-3">
                <div class="flex-1 relative">
                    <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                    <input
                        v-model="filters.search"
                        type="text"
                        placeholder="Search name or email…"
                        class="w-full pl-9 text-sm border-gray-200 rounded-lg focus:ring-[#EF233C] focus:border-[#EF233C]"
                        @input="applyFilters"
                    />
                </div>
                <div class="flex gap-2">
                    <select
                        v-model="filters.role"
                        @change="applyFilters"
                        class="flex-1 sm:w-36 text-sm border-gray-200 rounded-lg focus:ring-[#EF233C] focus:border-[#EF233C]"
                    >
                        <option value="">All roles</option>
                        <option v-for="r in roles" :key="r" :value="r" class="capitalize">{{ r.replace('_', ' ') }}</option>
                    </select>
                    <select
                        v-model="filters.status"
                        @change="applyFilters"
                        class="flex-1 sm:w-36 text-sm border-gray-200 rounded-lg focus:ring-[#EF233C] focus:border-[#EF233C]"
                    >
                        <option value="">All statuses</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                    <button @click="clearFilters" class="text-xs text-gray-400 hover:text-gray-600 px-2">Clear</button>
                </div>
            </div>
        </div>

        <!-- Mobile: Card list -->
        <div class="md:hidden space-y-3">
            <div v-if="staff.data.length === 0" class="bg-white rounded-xl border border-gray-200 px-4 py-12 text-center text-gray-400 text-sm">
                No staff members found.
            </div>
            <div
                v-for="member in staff.data"
                :key="member.id"
                :class="['bg-white rounded-xl border border-gray-200 p-4', !member.is_active && 'opacity-60']"
            >
                <div class="flex items-center gap-3 mb-3">
                    <img :src="member.avatar_url" :alt="member.name" class="w-11 h-11 rounded-full object-cover flex-shrink-0" />
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <p class="font-semibold text-gray-800 text-sm truncate">{{ member.name }}</p>
                            <span v-if="member.must_change_password" class="text-xs bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded font-medium">pwd</span>
                        </div>
                        <p class="text-xs text-gray-400 truncate">{{ member.email }}</p>
                    </div>
                    <span :class="member.is_active ? 'badge-green' : 'badge-red'">
                        {{ member.is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex flex-wrap gap-1.5">
                        <span v-for="role in member.roles" :key="role" :class="roleClass(role)">
                            {{ role.replace('_', ' ') }}
                        </span>
                        <span v-if="member.hire_date" class="text-xs text-gray-400">· {{ formatDate(member.hire_date) }}</span>
                    </div>

                    <div class="flex items-center gap-1 flex-shrink-0">
                        <Link :href="route('staff.show', member.id)" class="p-2 text-gray-400 hover:text-[#EF233C] hover:bg-red-50 rounded-lg transition-colors" title="View">
                            <EyeIcon class="w-4 h-4" />
                        </Link>
                        <Link :href="route('staff.edit', member.id)" class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Edit">
                            <PencilIcon class="w-4 h-4" />
                        </Link>
                        <button @click="toggleActive(member)" :class="member.is_active ? 'p-2 text-gray-400 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition-colors' : 'p-2 text-gray-400 hover:text-green-600 hover:bg-green-50 rounded-lg transition-colors'" :title="member.is_active ? 'Deactivate' : 'Activate'">
                            <component :is="member.is_active ? NoSymbolIcon : CheckCircleIcon" class="w-4 h-4" />
                        </button>
                        <button v-if="isAdmin" @click="confirmDelete(member)" class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Delete">
                            <TrashIcon class="w-4 h-4" />
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Desktop: Table -->
        <div class="hidden md:block bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Staff Member</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Role</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Hire Date</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                            <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-if="staff.data.length === 0">
                            <td colspan="5" class="px-4 py-12 text-center text-gray-400 text-sm">No staff members found.</td>
                        </tr>
                        <tr
                            v-for="member in staff.data"
                            :key="member.id"
                            class="hover:bg-gray-50 transition-colors"
                            :class="{ 'opacity-60': !member.is_active }"
                        >
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <img :src="member.avatar_url" :alt="member.name" class="w-9 h-9 rounded-full object-cover flex-shrink-0" />
                                    <div>
                                        <p class="font-medium text-gray-800">{{ member.name }}</p>
                                        <p class="text-xs text-gray-400">{{ member.email }}</p>
                                    </div>
                                    <span v-if="member.must_change_password" class="text-xs bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded font-medium" title="Must change password">pwd</span>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span v-for="role in member.roles" :key="role" :class="roleClass(role)">
                                    {{ role.replace('_', ' ') }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-500 text-xs">
                                {{ member.hire_date ? formatDate(member.hire_date) : '—' }}
                            </td>
                            <td class="px-4 py-3">
                                <span :class="member.is_active ? 'badge-green' : 'badge-red'">
                                    {{ member.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    <Link :href="route('staff.show', member.id)" class="p-1.5 text-gray-400 hover:text-[#EF233C] hover:bg-red-50 rounded-lg transition-colors" title="View">
                                        <EyeIcon class="w-4 h-4" />
                                    </Link>
                                    <Link :href="route('staff.edit', member.id)" class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Edit">
                                        <PencilIcon class="w-4 h-4" />
                                    </Link>
                                    <button @click="toggleActive(member)" :class="member.is_active ? 'p-1.5 text-gray-400 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition-colors' : 'p-1.5 text-gray-400 hover:text-green-600 hover:bg-green-50 rounded-lg transition-colors'" :title="member.is_active ? 'Deactivate' : 'Activate'">
                                        <component :is="member.is_active ? NoSymbolIcon : CheckCircleIcon" class="w-4 h-4" />
                                    </button>
                                    <button @click="forceReset(member)" class="p-1.5 text-gray-400 hover:text-purple-600 hover:bg-purple-50 rounded-lg transition-colors" title="Force password reset">
                                        <KeyIcon class="w-4 h-4" />
                                    </button>
                                    <button v-if="isAdmin" @click="confirmDelete(member)" class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Delete">
                                        <TrashIcon class="w-4 h-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="staff.last_page > 1" class="flex items-center justify-between px-4 py-3 border-t border-gray-100 bg-gray-50">
                <p class="text-xs text-gray-500">Showing {{ staff.from }}–{{ staff.to }} of {{ staff.total }}</p>
                <div class="flex gap-1">
                    <Link
                        v-for="link in staff.links"
                        :key="link.label"
                        :href="link.url ?? '#'"
                        :class="[
                            'px-2.5 py-1 text-xs rounded transition-colors',
                            link.active ? 'bg-[#EF233C] text-white' : 'text-gray-600 hover:bg-gray-100',
                            !link.url ? 'opacity-40 pointer-events-none' : '',
                        ]"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>

        <!-- Mobile pagination -->
        <div v-if="staff.last_page > 1" class="md:hidden flex items-center justify-between mt-4 bg-white rounded-xl border border-gray-200 px-4 py-3">
            <p class="text-xs text-gray-500">{{ staff.from }}–{{ staff.to }} of {{ staff.total }}</p>
            <div class="flex gap-1">
                <Link
                    v-for="link in staff.links"
                    :key="link.label"
                    :href="link.url ?? '#'"
                    :class="[
                        'px-2.5 py-1 text-xs rounded transition-colors',
                        link.active ? 'bg-[#EF233C] text-white' : 'text-gray-600 hover:bg-gray-100',
                        !link.url ? 'opacity-40 pointer-events-none' : '',
                    ]"
                    v-html="link.label"
                />
            </div>
        </div>

        <!-- Delete confirmation modal -->
        <BaseModal :open="!!deleteTarget" @close="deleteTarget = null" max-width="sm:max-w-sm">
            <div class="p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center flex-shrink-0">
                        <ExclamationTriangleIcon class="w-5 h-5 text-red-600" />
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-gray-800">Delete Staff Member</h3>
                        <p class="text-sm text-gray-500">This cannot be undone.</p>
                    </div>
                </div>
                <p class="text-sm text-gray-600 mb-5">
                    Are you sure you want to permanently delete <span class="font-semibold">{{ deleteTarget?.name }}</span>?
                </p>
                <div class="flex justify-end gap-2">
                    <button @click="deleteTarget = null" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 transition-colors">Cancel</button>
                    <button @click="doDelete" class="px-4 py-2 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">Delete</button>
                </div>
            </div>
        </BaseModal>
    </AppLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { usePermission } from '@/Composables/usePermission';
import AppLayout from '@/Layouts/AppLayout.vue';
import BaseModal from '@/Components/BaseModal.vue';
import {
    PlusIcon, MagnifyingGlassIcon, EyeIcon, PencilIcon,
    NoSymbolIcon, CheckCircleIcon, KeyIcon, TrashIcon,
    ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    staff:   { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    roles:   { type: Array,  default: () => [] },
});

const { isAdmin } = usePermission();

const filters = reactive({
    search: props.filters.search ?? '',
    role:   props.filters.role   ?? '',
    status: props.filters.status ?? '',
});

let searchTimer = null;
function applyFilters() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        router.get('/staff', filters, { preserveState: true, replace: true });
    }, 300);
}

function clearFilters() {
    Object.assign(filters, { search: '', role: '', status: '' });
    router.get('/staff', {}, { preserveState: true, replace: true });
}

function toggleActive(member) {
    router.post(route('staff.toggle-active', member.id), {}, { preserveScroll: true });
}

function forceReset(member) {
    router.post(route('staff.force-password-reset', member.id), {}, { preserveScroll: true });
}

const deleteTarget = ref(null);
function confirmDelete(member) { deleteTarget.value = member; }
function doDelete() {
    router.delete(route('staff.destroy', deleteTarget.value.id), {
        onSuccess: () => { deleteTarget.value = null; },
    });
}

function formatDate(d) {
    return new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: '2-digit', year: 'numeric' });
}

const roleColors = {
    admin:     'text-xs bg-red-100 text-red-700 px-2 py-0.5 rounded-full font-medium capitalize',
    manager:   'text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full font-medium capitalize',
    site_head: 'text-xs bg-purple-100 text-purple-700 px-2 py-0.5 rounded-full font-medium capitalize',
    staff:     'text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full font-medium capitalize',
};
function roleClass(role) { return roleColors[role] ?? roleColors.staff; }
</script>

<style scoped>
.badge-green { @apply text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full font-medium; }
.badge-red   { @apply text-xs bg-red-100 text-red-600 px-2 py-0.5 rounded-full font-medium; }
</style>
