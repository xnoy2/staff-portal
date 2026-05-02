<template>
    <AppLayout title="Audit Log">
        <div class="max-w-6xl mx-auto space-y-5">

            <!-- Header -->
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div>
                    <h1 class="text-lg font-semibold text-gray-800">Audit Log</h1>
                    <p class="text-xs text-gray-500 mt-0.5">{{ logs.total }} event{{ logs.total !== 1 ? 's' : '' }} recorded</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <div class="grid grid-cols-2 sm:flex sm:flex-wrap gap-3">
                    <div class="sm:flex-1 sm:min-w-40">
                        <label class="block text-xs text-gray-500 mb-1">Staff Member</label>
                        <select v-model="filters.causer_id" @change="applyFilters" class="w-full text-sm border-gray-200 rounded-lg focus:ring-[#EF233C] focus:border-[#EF233C]">
                            <option value="">All staff</option>
                            <option v-for="s in staffList" :key="s.id" :value="s.id">{{ s.name }}</option>
                        </select>
                    </div>
                    <div class="sm:flex-1 sm:min-w-40">
                        <label class="block text-xs text-gray-500 mb-1">Event Type</label>
                        <select v-model="filters.log_name" @change="applyFilters" class="w-full text-sm border-gray-200 rounded-lg focus:ring-[#EF233C] focus:border-[#EF233C]">
                            <option value="">All events</option>
                            <option v-for="name in logNames" :key="name" :value="name">{{ formatLogName(name) }}</option>
                        </select>
                    </div>
                    <div class="sm:flex-1 sm:min-w-32">
                        <label class="block text-xs text-gray-500 mb-1">From</label>
                        <input v-model="filters.from" type="date" @change="applyFilters" class="w-full text-sm border-gray-200 rounded-lg focus:ring-[#EF233C] focus:border-[#EF233C]" />
                    </div>
                    <div class="sm:flex-1 sm:min-w-32">
                        <label class="block text-xs text-gray-500 mb-1">To</label>
                        <input v-model="filters.to" type="date" @change="applyFilters" class="w-full text-sm border-gray-200 rounded-lg focus:ring-[#EF233C] focus:border-[#EF233C]" />
                    </div>
                    <div class="flex items-end">
                        <button @click="clearFilters" class="text-xs text-gray-400 hover:text-gray-600 px-2 py-2">Clear</button>
                    </div>
                </div>
            </div>

            <!-- Log Table -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div v-if="logs.data.length === 0" class="py-16 text-center">
                    <ClipboardDocumentListIcon class="w-10 h-10 text-gray-300 mx-auto mb-3" />
                    <p class="text-sm text-gray-500">No audit events found.</p>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="text-left text-xs font-medium text-gray-500 px-4 py-3 whitespace-nowrap">When</th>
                                <th class="text-left text-xs font-medium text-gray-500 px-4 py-3">Who</th>
                                <th class="text-left text-xs font-medium text-gray-500 px-4 py-3">Event</th>
                                <th class="text-left text-xs font-medium text-gray-500 px-4 py-3 hidden md:table-cell">Subject</th>
                                <th class="text-left text-xs font-medium text-gray-500 px-4 py-3 hidden lg:table-cell">Changes</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="log in logs.data" :key="log.id" class="hover:bg-gray-50 transition-colors">
                                <!-- When -->
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <p class="text-xs text-gray-700 font-medium">{{ fmtDate(log.created_at) }}</p>
                                    <p class="text-xs text-gray-400">{{ fmtTime(log.created_at) }}</p>
                                </td>
                                <!-- Who -->
                                <td class="px-4 py-3">
                                    <div v-if="log.causer" class="flex items-center gap-2">
                                        <img :src="log.causer.avatar_url" :alt="log.causer.name" class="w-6 h-6 rounded-full object-cover flex-shrink-0" />
                                        <span class="text-gray-700 text-xs font-medium whitespace-nowrap">{{ log.causer.name }}</span>
                                    </div>
                                    <span v-else class="text-gray-400 text-xs">System</span>
                                </td>
                                <!-- Event -->
                                <td class="px-4 py-3">
                                    <span :class="eventClass(log.log_name)" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium">
                                        {{ formatLogName(log.log_name) }}
                                    </span>
                                    <p class="text-xs text-gray-500 mt-0.5 max-w-xs truncate">{{ log.description }}</p>
                                </td>
                                <!-- Subject -->
                                <td class="px-4 py-3 hidden md:table-cell">
                                    <span v-if="log.subject_type" class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded font-mono">
                                        {{ log.subject_type }}
                                    </span>
                                    <span v-else class="text-gray-300 text-xs">—</span>
                                </td>
                                <!-- Changes -->
                                <td class="px-4 py-3 hidden lg:table-cell">
                                    <div v-if="hasChanges(log.properties)" class="space-y-0.5 max-w-sm">
                                        <div
                                            v-for="(change, field) in log.properties?.attributes"
                                            :key="field"
                                            class="flex items-center gap-1.5 text-xs"
                                        >
                                            <span class="text-gray-400 font-mono">{{ field }}:</span>
                                            <span class="text-red-500 line-through truncate max-w-20">{{ formatValue(log.properties?.old?.[field]) }}</span>
                                            <span class="text-gray-400">→</span>
                                            <span class="text-green-600 truncate max-w-20">{{ formatValue(change) }}</span>
                                        </div>
                                    </div>
                                    <span v-else class="text-gray-300 text-xs">—</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="logs.last_page > 1" class="border-t border-gray-100 px-4 py-3 flex items-center justify-between text-xs text-gray-500">
                    <span>Showing {{ logs.from }}–{{ logs.to }} of {{ logs.total }}</span>
                    <div class="flex items-center gap-1">
                        <Link
                            v-for="link in logs.links"
                            :key="link.label"
                            :href="link.url ?? '#'"
                            :class="[
                                'px-2 py-1 rounded transition-colors',
                                link.active ? 'bg-[#EF233C] text-white font-medium' : 'hover:bg-gray-100',
                                !link.url ? 'opacity-30 pointer-events-none' : '',
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>

<script setup>
import { reactive } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ClipboardDocumentListIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    logs:      { type: Object, required: true },
    logNames:  { type: Array,  default: () => [] },
    staffList: { type: Array,  default: () => [] },
    filters:   { type: Object, default: () => ({}) },
});

const filters = reactive({
    causer_id: props.filters.causer_id ?? '',
    log_name:  props.filters.log_name  ?? '',
    from:      props.filters.from      ?? '',
    to:        props.filters.to        ?? '',
});

function applyFilters() {
    router.get(route('audit-log'), filters, { preserveState: true, replace: true });
}

function clearFilters() {
    Object.assign(filters, { causer_id: '', log_name: '', from: '', to: '' });
    applyFilters();
}

function fmtDate(iso) {
    return new Date(iso).toLocaleDateString('en-GB', { day: '2-digit', month: '2-digit', year: 'numeric' });
}

function fmtTime(iso) {
    return new Date(iso).toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });
}

function formatLogName(name) {
    if (!name) return '—';
    return name.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
}

function formatValue(val) {
    if (val === null || val === undefined) return 'null';
    if (typeof val === 'boolean') return val ? 'true' : 'false';
    return String(val).substring(0, 40);
}

function hasChanges(properties) {
    return properties?.attributes && Object.keys(properties.attributes).length > 0;
}

const eventClasses = {
    default:  'bg-gray-100 text-gray-600',
    created:  'bg-green-50 text-green-700',
    updated:  'bg-blue-50 text-blue-700',
    deleted:  'bg-red-50 text-red-700',
    approved: 'bg-emerald-50 text-emerald-700',
    rejected: 'bg-orange-50 text-orange-700',
};

function eventClass(name) {
    if (!name) return eventClasses.default;
    const key = Object.keys(eventClasses).find(k => name.toLowerCase().includes(k));
    return eventClasses[key] ?? eventClasses.default;
}
</script>
