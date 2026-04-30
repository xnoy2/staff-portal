<template>
    <AppLayout title="Vans">
        <div class="max-w-5xl mx-auto space-y-5">

            <!-- Header -->
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div>
                    <h1 class="text-lg font-semibold text-gray-800">Vans</h1>
                    <p class="text-xs text-gray-500 mt-0.5">{{ counts.total }} van{{ counts.total !== 1 ? 's' : '' }} · {{ counts.active }} active</p>
                </div>
                <Link :href="route('vans.create')" class="bg-[#EF233C] hover:bg-[#D90429] text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors flex items-center gap-1.5">
                    <PlusIcon class="w-4 h-4" /> Add Van
                </Link>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-xl border border-gray-200 p-4 flex flex-wrap items-center gap-3">
                <div class="relative flex-1 min-w-48">
                    <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search registration, make or model…"
                        class="w-full pl-9 pr-3 py-1.5 text-sm border border-gray-200 rounded-lg focus:ring-[#EF233C] focus:border-[#EF233C]"
                        @input="applyFilters"
                    />
                </div>
                <div class="flex items-center gap-1 bg-gray-100 rounded-lg p-1">
                    <button
                        v-for="opt in statusOpts"
                        :key="opt.value"
                        @click="setStatus(opt.value)"
                        :class="[
                            'text-xs font-medium px-3 py-1 rounded-md transition-colors',
                            filterStatus === opt.value
                                ? 'bg-white text-gray-800 shadow-sm'
                                : 'text-gray-500 hover:text-gray-700',
                        ]"
                    >
                        {{ opt.label }}
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div v-if="vans.data.length === 0" class="py-16 text-center">
                    <TruckIcon class="w-10 h-10 text-gray-200 mx-auto mb-3" />
                    <p class="text-sm text-gray-500">No vans found.</p>
                </div>

                <table v-else class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="text-left text-xs font-medium text-gray-500 px-5 py-3">Registration</th>
                            <th class="text-left text-xs font-medium text-gray-500 px-4 py-3">Make / Model</th>
                            <th class="text-center text-xs font-medium text-gray-500 px-4 py-3 hidden sm:table-cell">Year</th>
                            <th class="text-center text-xs font-medium text-gray-500 px-4 py-3 hidden md:table-cell">Projects</th>
                            <th class="text-left text-xs font-medium text-gray-500 px-4 py-3">Status</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr
                            v-for="van in vans.data"
                            :key="van.id"
                            class="group hover:bg-gray-50 transition-colors cursor-pointer"
                            @click="$inertia.visit(route('vans.show', van.id))"
                        >
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl bg-[#2B2D42] flex items-center justify-center flex-shrink-0">
                                        <TruckIcon class="w-4 h-4 text-white" />
                                    </div>
                                    <span class="font-bold text-gray-800 tracking-wide">{{ van.registration }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3.5 text-gray-700">
                                {{ van.make }} {{ van.model }}
                            </td>
                            <td class="px-4 py-3.5 text-center text-gray-500 hidden sm:table-cell">
                                {{ van.year ?? '—' }}
                            </td>
                            <td class="px-4 py-3.5 text-center hidden md:table-cell">
                                <span class="text-sm font-semibold text-gray-700">{{ van.projects_count }}</span>
                            </td>
                            <td class="px-4 py-3.5">
                                <span :class="van.is_active ? 'bg-green-50 text-green-700 border-green-200' : 'bg-gray-100 text-gray-500 border-gray-200'" class="inline-flex items-center gap-1 text-xs font-medium px-2 py-0.5 rounded-full border">
                                    <span :class="van.is_active ? 'bg-green-500' : 'bg-gray-400'" class="w-1.5 h-1.5 rounded-full"></span>
                                    {{ van.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5">
                                <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity justify-end" @click.stop>
                                    <Link :href="route('vans.edit', van.id)" class="p-1.5 text-gray-400 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                                        <PencilIcon class="w-4 h-4" />
                                    </Link>
                                    <button @click="toggleActive(van)" class="p-1.5 rounded-lg transition-colors" :class="van.is_active ? 'text-gray-400 hover:text-amber-500 hover:bg-amber-50' : 'text-gray-400 hover:text-green-600 hover:bg-green-50'" :title="van.is_active ? 'Deactivate' : 'Activate'">
                                        <component :is="van.is_active ? NoSymbolIcon : CheckCircleIcon" class="w-4 h-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div v-if="vans.last_page > 1" class="border-t border-gray-100 px-5 py-3 flex items-center justify-between text-xs text-gray-500">
                    <span>Showing {{ vans.from }}–{{ vans.to }} of {{ vans.total }}</span>
                    <div class="flex gap-1">
                        <Link v-for="link in vans.links" :key="link.label" :href="link.url ?? '#'"
                            :class="['px-2 py-1 rounded transition-colors', link.active ? 'bg-[#EF233C] text-white' : 'hover:bg-gray-100', !link.url ? 'opacity-30 pointer-events-none' : '']"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    PlusIcon, TruckIcon, PencilIcon, MagnifyingGlassIcon,
    NoSymbolIcon, CheckCircleIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    vans:    { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    counts:  { type: Object, required: true },
});

const search       = ref(props.filters.search ?? '');
const filterStatus = ref(props.filters.status ?? '');

const statusOpts = [
    { label: 'All',      value: '' },
    { label: 'Active',   value: 'active' },
    { label: 'Inactive', value: 'inactive' },
];

let searchTimer = null;
function applyFilters() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        router.get(route('vans.index'), {
            search: search.value || undefined,
            status: filterStatus.value || undefined,
        }, { preserveScroll: true, replace: true });
    }, 300);
}

function setStatus(val) {
    filterStatus.value = val;
    applyFilters();
}

function toggleActive(van) {
    router.post(route('vans.toggle-active', van.id), {}, { preserveScroll: true });
}
</script>
