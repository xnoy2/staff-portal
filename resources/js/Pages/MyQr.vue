<template>
    <AppLayout title="My QR Code">
        <div class="max-w-sm mx-auto space-y-5">

            <!-- QR Card -->
            <div class="bg-white rounded-2xl border border-gray-200 p-6 text-center shadow-sm">
                <div class="flex items-center justify-center gap-2 mb-5">
                    <img :src="user.avatar_url" :alt="user.name" class="w-10 h-10 rounded-full object-cover border-2 border-white shadow" />
                    <div class="text-left">
                        <p class="text-sm font-semibold text-gray-800 leading-tight">{{ user.name }}</p>
                        <p class="text-xs text-gray-400">{{ user.email }}</p>
                    </div>
                </div>

                <!-- QR code display -->
                <div class="inline-flex items-center justify-center bg-white border-4 border-[#2B2D42] rounded-2xl p-4 shadow-inner mb-4">
                    <img
                        :src="qrUrl"
                        :alt="`QR code for ${user.name}`"
                        class="w-52 h-52"
                    />
                </div>

                <p class="text-sm font-medium text-gray-700 mb-1">Present to your site head to clock in or out</p>
                <p class="text-xs text-gray-400">Your site head scans this code with the QR Scanner app.</p>

                <!-- Roles -->
                <div class="flex flex-wrap justify-center gap-1.5 mt-4">
                    <span
                        v-for="role in user.roles"
                        :key="role"
                        :class="roleClass(role)"
                    >{{ role.replace('_', ' ') }}</span>
                </div>
            </div>

            <!-- UUID (for manual fallback) -->
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Staff ID (manual fallback)</p>
                <div class="flex items-center gap-2">
                    <code class="flex-1 text-xs font-mono text-gray-600 bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 break-all select-all">
                        {{ user.id }}
                    </code>
                    <button
                        @click="copyId"
                        class="flex-shrink-0 p-2 text-gray-400 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
                        :title="copied ? 'Copied!' : 'Copy ID'"
                    >
                        <CheckIcon v-if="copied" class="w-4 h-4 text-green-500" />
                        <ClipboardIcon v-else class="w-4 h-4" />
                    </button>
                </div>
                <p class="text-xs text-gray-400 mt-1.5">Give this to your site head if the QR camera isn't working.</p>
            </div>

            <!-- Recent approved entries -->
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-sm font-semibold text-gray-800">Recent Attendance</h2>
                    <Link href="/attendance" class="text-xs text-[#EF233C] hover:underline">View all</Link>
                </div>

                <div v-if="recentEntries.length === 0" class="text-center py-6 text-sm text-gray-400">
                    No approved entries yet.
                </div>
                <table v-else class="w-full text-xs">
                    <thead>
                        <tr class="text-gray-400 border-b border-gray-100">
                            <th class="text-left pb-2 font-medium">Date</th>
                            <th class="text-left pb-2 font-medium">In</th>
                            <th class="text-left pb-2 font-medium">Out</th>
                            <th class="text-right pb-2 font-medium">Hours</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="e in recentEntries" :key="e.id" class="border-b border-gray-50 last:border-0">
                            <td class="py-2 text-gray-700 whitespace-nowrap">{{ formatDate(e.date) }}</td>
                            <td class="py-2 font-mono text-gray-600 whitespace-nowrap">{{ e.clock_in }}</td>
                            <td class="py-2 font-mono text-gray-600 whitespace-nowrap">{{ e.clock_out ?? '—' }}</td>
                            <td class="py-2 text-right text-gray-700">{{ e.hours ?? '—' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ClipboardIcon, CheckIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    qrPayload:     { type: String, required: true },
    recentEntries: { type: Array,  default: () => [] },
});

const page = usePage();
const user = computed(() => page.props.auth.user);

const qrUrl = computed(() =>
    `https://api.qrserver.com/v1/create-qr-code/?size=208x208&data=${encodeURIComponent(props.qrPayload)}&margin=10`
);

const copied = ref(false);

async function copyId() {
    try {
        await navigator.clipboard.writeText(user.value.id);
        copied.value = true;
        setTimeout(() => { copied.value = false; }, 2000);
    } catch {
        // clipboard API unavailable
    }
}

function formatDate(d) {
    return new Date(d + 'T00:00:00').toLocaleDateString('en-GB', { day: 'numeric', month: 'short' });
}

const roleColors = {
    admin:     'text-xs bg-red-100 text-red-700 px-2 py-0.5 rounded-full font-medium capitalize',
    manager:   'text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full font-medium capitalize',
    site_head: 'text-xs bg-purple-100 text-purple-700 px-2 py-0.5 rounded-full font-medium capitalize',
    staff:     'text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full font-medium capitalize',
};
function roleClass(r) { return roleColors[r] ?? roleColors.staff; }
</script>
