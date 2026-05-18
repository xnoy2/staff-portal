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
                    <p class="text-xs text-gray-500 mt-0.5">{{ filtered.length }} of {{ orders.length }} order{{ orders.length !== 1 ? 's' : '' }}</p>
                </div>
                <div class="relative">
                    <MagnifyingGlassIcon class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none" />
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search client, reference, address…"
                        class="pl-9 pr-3 py-1.5 text-sm border border-gray-200 rounded-lg focus:ring-[#EF233C] focus:border-[#EF233C] w-64"
                    />
                </div>
            </div>

            <!-- Not linked warning (non-admin without a BCF worker link) -->
            <div v-if="!isPrivileged && !linked" class="bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 flex items-start gap-3">
                <span class="text-lg flex-shrink-0">⚠️</span>
                <div>
                    <p class="text-sm font-semibold text-amber-800">Your account isn't linked to a BCF worker</p>
                    <p class="text-xs text-amber-700 mt-0.5">Ask an admin to link your profile to your BCF worker account in Staff → Edit. Until then, you'll see all orders.</p>
                </div>
            </div>

            <!-- Empty -->
            <div v-if="filtered.length === 0" class="bg-white rounded-xl border border-gray-200 py-16 text-center">
                <ClipboardDocumentListIcon class="w-10 h-10 text-gray-200 mx-auto mb-3" />
                <p class="text-sm text-gray-500">{{ search ? 'No orders match your search.' : 'No orders found.' }}</p>
            </div>

            <!-- Orders list -->
            <div v-else class="space-y-2">
                <Link
                    v-for="order in filtered"
                    :key="order.id"
                    :href="route('bcf.show', order.id)"
                    class="bg-white rounded-xl border border-gray-200 p-4 sm:p-5 flex items-start gap-4 hover:border-[#EF233C]/40 hover:shadow-sm transition-all group"
                >
                    <!-- Icon -->
                    <div class="w-10 h-10 rounded-xl bg-[#2B2D42] flex items-center justify-center flex-shrink-0 mt-0.5">
                        <ClipboardDocumentListIcon class="w-5 h-5 text-white" />
                    </div>

                    <!-- Main info -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-[10px] font-bold text-gray-400 tracking-widest">{{ order.order_number }}</span>
                            <span v-if="order.is_birthday_booking" class="text-[10px] font-semibold px-1.5 py-0.5 rounded bg-amber-100 text-amber-700">🎂 Birthday</span>
                        </div>
                        <p class="text-sm font-semibold text-gray-800 group-hover:text-[#EF233C] transition-colors mt-0.5">
                            {{ order.client?.name ?? '—' }}
                        </p>

                        <div class="flex flex-wrap gap-x-4 gap-y-1 mt-1.5">
                            <span v-if="order.address" class="flex items-center gap-1 text-xs text-gray-500">
                                <MapPinIcon class="w-3 h-3 flex-shrink-0" />{{ order.address }}
                            </span>
                            <span v-if="order.installation_date" class="flex items-center gap-1 text-xs text-gray-500">
                                <CalendarIcon class="w-3 h-3 flex-shrink-0" />{{ formatDate(order.installation_date) }}
                            </span>
                            <span v-if="order.client?.phone" class="flex items-center gap-1 text-xs text-gray-400">
                                <PhoneIcon class="w-3 h-3 flex-shrink-0" />{{ order.client.phone }}
                            </span>
                        </div>

                        <p v-if="order.product_order" class="text-xs text-gray-400 mt-1.5 line-clamp-1">{{ order.product_order }}</p>
                    </div>

                    <!-- Chevron -->
                    <ChevronRightIcon class="w-4 h-4 text-gray-300 group-hover:text-[#EF233C] flex-shrink-0 mt-1 transition-colors" />
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
    MapPinIcon, CalendarIcon, PhoneIcon, ChevronRightIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    orders:       { type: Array,   default: () => [] },
    isPrivileged: { type: Boolean, default: false },
    linked:       { type: Boolean, default: false },
});

const search = ref('');

const filtered = computed(() => {
    if (!search.value.trim()) return props.orders;
    const q = search.value.toLowerCase();
    return props.orders.filter(o =>
        (o.order_number ?? '').toLowerCase().includes(q) ||
        (o.client?.name ?? '').toLowerCase().includes(q) ||
        (o.address ?? '').toLowerCase().includes(q) ||
        (o.product_order ?? '').toLowerCase().includes(q)
    );
});

function formatDate(d) {
    if (!d) return '';
    return new Date(d + 'T00:00:00').toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
}
</script>
