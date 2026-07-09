<template>
    <AppLayout title="Daily Logs">
        <div class="max-w-6xl mx-auto space-y-4">

            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-lg font-semibold text-gray-800">Daily Logs</h1>
                    <p class="text-xs text-gray-500 mt-0.5">
                        <span v-if="pendingToday > 0" class="text-amber-600 font-medium">{{ pendingToday }} not submitted today</span>
                        <span v-else>Team end-of-day logs</span>
                    </p>
                </div>
                <a :href="exportUrl" class="inline-flex items-center gap-1.5 bg-white border border-gray-200 text-gray-700 text-sm px-3 py-2 rounded-lg hover:bg-gray-50">
                    <ArrowDownTrayIcon class="w-4 h-4" /> <span class="hidden sm:inline">Export</span>
                </a>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-xl border border-gray-200 p-4 grid grid-cols-2 sm:flex sm:flex-wrap gap-3">
                <div class="col-span-2 sm:flex-1 sm:min-w-40">
                    <label class="block text-xs text-gray-500 mb-1">Staff</label>
                    <select v-model="filters.user_id" @change="apply" class="w-full text-sm border-gray-200 rounded-lg focus:ring-[#EF233C] focus:border-[#EF233C]">
                        <option value="">All staff</option>
                        <option v-for="s in staffList" :key="s.id" :value="s.id">{{ s.name }}</option>
                    </select>
                </div>
                <div class="sm:flex-1 sm:min-w-32">
                    <label class="block text-xs text-gray-500 mb-1">Status</label>
                    <select v-model="filters.status" @change="apply" class="w-full text-sm border-gray-200 rounded-lg focus:ring-[#EF233C] focus:border-[#EF233C]">
                        <option value="">All</option>
                        <option value="submitted">Submitted</option>
                        <option value="draft">Draft</option>
                    </select>
                </div>
                <div v-if="jobs.length" class="col-span-2 sm:flex-1 sm:min-w-40">
                    <label class="block text-xs text-gray-500 mb-1">Job</label>
                    <select v-model="filters.job_id" @change="apply" class="w-full text-sm border-gray-200 rounded-lg focus:ring-[#EF233C] focus:border-[#EF233C]">
                        <option value="">All jobs</option>
                        <option v-for="j in jobs" :key="j.id" :value="j.id">{{ j.title }}</option>
                    </select>
                </div>
                <div class="sm:flex-1 sm:min-w-32">
                    <label class="block text-xs text-gray-500 mb-1">From</label>
                    <input v-model="filters.from" type="date" @change="apply" class="w-full text-sm border-gray-200 rounded-lg focus:ring-[#EF233C] focus:border-[#EF233C]" />
                </div>
                <div class="sm:flex-1 sm:min-w-32">
                    <label class="block text-xs text-gray-500 mb-1">To</label>
                    <input v-model="filters.to" type="date" @change="apply" class="w-full text-sm border-gray-200 rounded-lg focus:ring-[#EF233C] focus:border-[#EF233C]" />
                </div>
                <div class="flex items-end">
                    <button @click="clear" class="text-xs text-gray-400 hover:text-gray-600 px-2 py-2">Clear</button>
                </div>
            </div>

            <!-- List -->
            <div class="bg-white rounded-xl border border-gray-200 divide-y divide-gray-100">
                <div v-if="logs.data.length === 0" class="px-4 py-12 text-center text-sm text-gray-400">No logs found.</div>
                <Link
                    v-for="l in logs.data"
                    :key="l.id"
                    :href="route('activity-logs.show', l.id)"
                    class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 transition-colors"
                >
                    <img :src="l.user.avatar_url" :alt="l.user.name" class="w-9 h-9 rounded-full object-cover flex-shrink-0" />
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-800 truncate">{{ l.user.name }}</p>
                        <p class="text-xs text-gray-400">
                            {{ prettyDate(l.log_date) }}
                            <span v-if="l.jobs"> · {{ l.jobs }} job{{ l.jobs === 1 ? '' : 's' }}</span>
                            <span v-if="l.team"> · {{ l.team }} team</span>
                            <span v-if="l.photos"> · {{ l.photos }} photo{{ l.photos === 1 ? '' : 's' }}</span>
                        </p>
                    </div>
                    <span v-if="l.acknowledged" class="text-xs text-emerald-600 inline-flex items-center gap-1"><CheckBadgeIcon class="w-4 h-4" /></span>
                    <span :class="l.status === 'submitted' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'" class="text-xs font-medium px-2 py-0.5 rounded-full capitalize">{{ l.status }}</span>
                    <ChevronRightIcon class="w-4 h-4 text-gray-300" />
                </Link>
            </div>

            <!-- Pagination -->
            <div v-if="logs.last_page > 1" class="flex items-center justify-center gap-1">
                <Link v-for="link in logs.links" :key="link.label" :href="link.url ?? '#'" v-html="link.label"
                    :class="['px-2.5 py-1 text-xs rounded transition-colors', link.active ? 'bg-[#EF233C] text-white' : 'text-gray-600 hover:bg-gray-100', !link.url ? 'opacity-40 pointer-events-none' : '']" />
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { reactive, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ArrowDownTrayIcon, ChevronRightIcon, CheckBadgeIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    logs:         { type: Object, required: true },
    staffList:    { type: Array,  default: () => [] },
    jobs:         { type: Array,  default: () => [] },
    filters:      { type: Object, default: () => ({}) },
    pendingToday: { type: Number, default: 0 },
});

const filters = reactive({
    user_id: props.filters.user_id ?? '',
    status:  props.filters.status ?? '',
    from:    props.filters.from ?? '',
    to:      props.filters.to ?? '',
    job_id:  props.filters.job_id ?? '',
});

function apply() { router.get(route('activity-logs.index'), filters, { preserveState: true, replace: true }); }
function clear() { Object.assign(filters, { user_id: '', status: '', from: '', to: '', job_id: '' }); apply(); }

const exportUrl = computed(() => {
    const p = new URLSearchParams();
    Object.entries(filters).forEach(([k, v]) => { if (v) p.set(k, v); });
    const qs = p.toString();
    return route('activity-logs.export') + (qs ? '?' + qs : '');
});

function prettyDate(d) { return new Date(d + 'T00:00:00').toLocaleDateString('en-GB', { day: '2-digit', month: '2-digit', year: 'numeric' }); }
</script>
