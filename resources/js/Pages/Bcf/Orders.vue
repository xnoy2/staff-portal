<template>
    <AppLayout title="BCF Orders">
        <div class="max-w-5xl mx-auto space-y-5">

            <!-- Header -->
            <div class="flex items-center justify-between gap-4 flex-wrap">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold px-2 py-0.5 rounded bg-[#EF233C] text-white tracking-wide">BCF</span>
                        <h1 class="text-lg font-semibold text-gray-800">Work Orders</h1>
                    </div>
                    <p class="text-xs text-gray-500 mt-0.5">{{ orders.length }} order{{ orders.length !== 1 ? 's' : '' }}</p>
                </div>
                <div class="relative">
                    <MagnifyingGlassIcon class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none" />
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search orders…"
                        class="pl-9 pr-3 py-1.5 text-sm border border-gray-200 rounded-lg focus:ring-[#EF233C] focus:border-[#EF233C] w-52"
                    />
                </div>
            </div>

            <!-- Empty -->
            <div v-if="filtered.length === 0" class="bg-white rounded-xl border border-gray-200 py-16 text-center">
                <ClipboardDocumentListIcon class="w-10 h-10 text-gray-200 mx-auto mb-3" />
                <p class="text-sm text-gray-500">{{ search ? 'No orders match your search.' : 'No orders found.' }}</p>
            </div>

            <!-- Orders grid -->
            <div v-else class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <Link
                    v-for="order in filtered"
                    :key="order.id"
                    :href="route('bcf.show', order.id)"
                    class="bg-white rounded-xl border border-gray-200 p-4 hover:border-[#EF233C]/40 hover:shadow-sm transition-all group"
                >
                    <!-- Top row -->
                    <div class="flex items-start justify-between gap-2 mb-3">
                        <div class="w-9 h-9 rounded-xl bg-[#2B2D42] flex items-center justify-center flex-shrink-0">
                            <ClipboardDocumentListIcon class="w-4 h-4 text-white" />
                        </div>
                        <span :class="statusBadge(order.status)" class="text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wide">
                            {{ order.status ?? 'pending' }}
                        </span>
                    </div>

                    <!-- Title / ref -->
                    <p class="text-sm font-semibold text-gray-800 group-hover:text-[#EF233C] transition-colors truncate">
                        {{ order.title ?? order.reference ?? `Order #${order.id}` }}
                    </p>

                    <!-- Client -->
                    <p v-if="order.client?.name ?? order.client_name" class="text-xs text-gray-500 mt-0.5 truncate flex items-center gap-1">
                        <UserIcon class="w-3 h-3 flex-shrink-0" />
                        {{ order.client?.name ?? order.client_name }}
                    </p>

                    <!-- Worker -->
                    <p v-if="order.worker?.name ?? order.worker_name" class="text-xs text-gray-400 mt-0.5 truncate flex items-center gap-1">
                        <WrenchScrewdriverIcon class="w-3 h-3 flex-shrink-0" />
                        {{ order.worker?.name ?? order.worker_name }}
                    </p>

                    <!-- Progress bar -->
                    <div v-if="progressPct(order) !== null" class="mt-3">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-[10px] text-gray-400">Progress</span>
                            <span class="text-[10px] font-semibold text-gray-600">{{ progressPct(order) }}%</span>
                        </div>
                        <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full bg-[#EF233C] rounded-full transition-all" :style="{ width: progressPct(order) + '%' }" />
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="flex items-center justify-between mt-3 pt-3 border-t border-gray-100">
                        <span v-if="stageCount(order)" class="text-[10px] text-gray-400">{{ stageCount(order) }} stage{{ stageCount(order) !== 1 ? 's' : '' }}</span>
                        <span class="text-[10px] text-gray-400">{{ order.created_at ? formatDate(order.created_at) : '' }}</span>
                    </div>
                </Link>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    MagnifyingGlassIcon, ClipboardDocumentListIcon,
    UserIcon, WrenchScrewdriverIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    orders: { type: Array, default: () => [] },
});

const search = ref('');

const filtered = computed(() => {
    if (!search.value.trim()) return props.orders;
    const q = search.value.toLowerCase();
    return props.orders.filter(o => {
        const title  = (o.title ?? o.reference ?? '').toLowerCase();
        const client = (o.client?.name ?? o.client_name ?? '').toLowerCase();
        const worker = (o.worker?.name ?? o.worker_name ?? '').toLowerCase();
        return title.includes(q) || client.includes(q) || worker.includes(q);
    });
});

function progressPct(order) {
    const stages = order.stages ?? [];
    if (!stages.length) return null;
    const tasks = stages.flatMap(s => s.tasks ?? []);
    if (!tasks.length) return null;
    const done = tasks.filter(t => t.completed).length;
    return Math.round((done / tasks.length) * 100);
}

function stageCount(order) {
    return (order.stages ?? []).length || null;
}

function formatDate(d) {
    return new Date(d).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
}

const statusBadge = s => ({
    pending:     'bg-gray-100 text-gray-500',
    in_progress: 'bg-amber-100 text-amber-700',
    done:        'bg-emerald-100 text-emerald-700',
}[s] ?? 'bg-gray-100 text-gray-500');
</script>
