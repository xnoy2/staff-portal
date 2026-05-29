<template>
    <AppLayout title="Client Projects">
        <div class="max-w-6xl mx-auto space-y-6">

            <!-- Header -->
            <div class="flex items-center justify-between gap-4 flex-wrap">
                <div>
                    <h1 class="text-lg font-semibold text-gray-800">Client Projects</h1>
                    <p class="text-xs text-gray-500 mt-0.5">
                        <template v-if="connected">{{ displayedProjects.length }} project{{ displayedProjects.length !== 1 ? 's' : '' }} assigned to you</template>
                        <template v-else>Connect your BGR account to view your assigned projects</template>
                    </p>
                </div>
                <button
                    v-if="connected"
                    @click="showDisconnect = true"
                    class="text-xs text-gray-400 hover:text-red-500 transition-colors flex items-center gap-1.5"
                >
                    <LinkSlashIcon class="w-3.5 h-3.5" /> Disconnect BGR Account
                </button>
            </div>

            <!-- API error -->
            <div v-if="error" class="bg-red-50 border border-red-200 rounded-xl px-4 py-3 text-sm text-red-700">
                {{ error }}
            </div>

            <!-- Photo session expired warning -->
            <div v-if="connected && !has_session" class="bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 flex items-start gap-3">
                <ExclamationTriangleIcon class="w-4 h-4 text-amber-500 flex-shrink-0 mt-0.5" />
                <div class="flex-1 text-sm text-amber-800">
                    <span class="font-semibold">Photos unavailable.</span>
                    Your BGR photo session has expired. Disconnect and reconnect your account to restore photo viewing.
                </div>
            </div>

            <!-- ── Not connected ── -->
            <div v-if="!connected" class="bg-white rounded-xl border border-gray-200 p-8 max-w-md mx-auto text-center space-y-5">
                <div class="w-14 h-14 bg-[#EF233C]/10 rounded-full flex items-center justify-center mx-auto">
                    <BuildingStorefrontIcon class="w-7 h-7 text-[#EF233C]" />
                </div>
                <div>
                    <h2 class="text-base font-semibold text-gray-800">Connect your BGR account</h2>
                    <p class="text-sm text-gray-500 mt-1">Enter your BGR Portal credentials once — your token is stored securely and won't be asked again.</p>
                </div>

                <form @submit.prevent="submitConnect" class="text-left space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input
                            v-model="connectForm.email"
                            type="email"
                            autocomplete="email"
                            class="w-full rounded-lg border-gray-200 text-sm focus:ring-[#EF233C] focus:border-[#EF233C]"
                            placeholder="your@email.com"
                        />
                        <p v-if="connectForm.errors.email" class="mt-1 text-xs text-red-600">{{ connectForm.errors.email }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                        <input
                            v-model="connectForm.password"
                            type="password"
                            autocomplete="current-password"
                            class="w-full rounded-lg border-gray-200 text-sm focus:ring-[#EF233C] focus:border-[#EF233C]"
                            placeholder="••••••••"
                        />
                        <p v-if="connectForm.errors.bgr_password" class="mt-1 text-xs text-red-600">{{ connectForm.errors.bgr_password }}</p>
                    </div>
                    <button
                        type="submit"
                        :disabled="connectForm.processing"
                        class="w-full bg-[#EF233C] hover:bg-[#D90429] disabled:opacity-50 text-white text-sm font-semibold py-2.5 rounded-lg transition-colors"
                    >
                        {{ connectForm.processing ? 'Connecting…' : 'Connect Account' }}
                    </button>
                </form>
            </div>

            <!-- ── Connected: filters ── -->
            <div v-else class="flex items-center gap-3 flex-wrap">
                <div class="relative flex-1 min-w-48">
                    <MagnifyingGlassIcon class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none" />
                    <input
                        v-model="search"
                        @input="applyFilters"
                        type="text"
                        placeholder="Search projects…"
                        class="w-full pl-9 rounded-lg border-gray-200 text-sm focus:ring-[#EF233C] focus:border-[#EF233C]"
                    />
                </div>
                <select
                    v-model="statusFilter"
                    @change="applyFilters"
                    class="rounded-lg border-gray-200 text-sm focus:ring-[#EF233C] focus:border-[#EF233C]"
                >
                    <option value="">Open projects</option>
                    <option value="all">All statuses</option>
                    <option value="pending">Pending</option>
                    <option value="active">Active</option>
                    <option value="on_hold">On Hold</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>

            <!-- ── Project grid ── -->
            <div v-if="connected && displayedProjects.length === 0" class="bg-white rounded-xl border border-dashed border-gray-300 py-20 text-center">
                <BuildingStorefrontIcon class="w-12 h-12 text-gray-300 mx-auto mb-3" />
                <p class="text-gray-600 font-medium">No projects found</p>
                <p class="text-sm text-gray-400 mt-1">You have no assigned projects matching your filters.</p>
            </div>

            <div v-else-if="connected" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                <Link
                    v-for="project in displayedProjects"
                    :key="project.id"
                    :href="route('bgr.show', project.id)"
                    class="bg-white rounded-xl border border-gray-200 p-5 flex flex-col gap-3 hover:shadow-md transition-shadow"
                >
                    <!-- Status + name -->
                    <div class="flex items-start justify-between gap-2">
                        <h3 class="text-sm font-semibold text-gray-800 leading-snug">{{ project.name }}</h3>
                        <StatusBadge :status="project.status" />
                    </div>

                    <!-- Address -->
                    <p v-if="project.address" class="text-xs text-gray-500 flex items-center gap-1.5">
                        <MapPinIcon class="w-3.5 h-3.5 flex-shrink-0" />
                        {{ project.address }}
                    </p>

                    <!-- Current stage -->
                    <p v-if="project.current_stage" class="text-xs text-gray-500 flex items-center gap-1.5">
                        <ChevronRightIcon class="w-3.5 h-3.5 flex-shrink-0 text-[#EF233C]" />
                        <span class="text-gray-700 font-medium">{{ project.current_stage.name }}</span>
                    </p>

                    <!-- Progress -->
                    <div class="mt-auto">
                        <div class="flex items-center justify-between text-[10px] text-gray-400 mb-1">
                            <span>{{ project.completed_stages }} / {{ project.total_stages }} stages</span>
                            <span>{{ project.progress_pct }}%</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-1.5">
                            <div
                                class="h-1.5 rounded-full bg-[#EF233C] transition-all"
                                :style="{ width: project.progress_pct + '%' }"
                            />
                        </div>
                    </div>
                </Link>
            </div>

            <!-- Pagination -->
            <div v-if="meta && meta.last_page > 1" class="flex items-center justify-center gap-2">
                <button
                    v-for="page in meta.last_page"
                    :key="page"
                    @click="goToPage(page)"
                    :class="[
                        'w-8 h-8 rounded-lg text-xs font-medium transition-colors',
                        page === meta.current_page
                            ? 'bg-[#EF233C] text-white'
                            : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50',
                    ]"
                >
                    {{ page }}
                </button>
            </div>

        </div>

        <!-- Disconnect confirm modal -->
        <ConfirmModal
            :open="showDisconnect"
            title="Disconnect BGR Account"
            message="Your BGR token will be removed. You can reconnect at any time."
            confirm-label="Disconnect"
            :danger="true"
            @confirm="submitDisconnect"
            @cancel="showDisconnect = false"
        />

    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import {
    BuildingStorefrontIcon, LinkSlashIcon, MapPinIcon,
    ChevronRightIcon, MagnifyingGlassIcon, ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline';

const STATUS_CLASSES = {
    pending:   'bg-amber-100 text-amber-700',
    active:    'bg-green-100 text-green-700',
    on_hold:   'bg-blue-100 text-blue-700',
    completed: 'bg-gray-100 text-gray-600',
    cancelled: 'bg-red-100 text-red-600',
};

const StatusBadge = {
    props: ['status'],
    template: `<span :class="['text-[10px] font-semibold px-2 py-0.5 rounded-full capitalize whitespace-nowrap', classes[status] ?? 'bg-gray-100 text-gray-600']">{{ label }}</span>`,
    setup(props) {
        return {
            classes: STATUS_CLASSES,
            label: props.status?.replace('_', ' '),
        };
    },
};

const props = defineProps({
    connected:   { type: Boolean, default: false },
    has_session: { type: Boolean, default: true },
    projects:    { type: Array,   default: () => [] },
    meta:        { type: Object,  default: null },
    filters:     { type: Object,  default: () => ({}) },
    error:       { type: String,  default: null },
});

// ── Connect ───────────────────────────────────────────────────────────────────
const connectForm = useForm({ email: '', password: '' });

function submitConnect() {
    connectForm.post(route('bgr.connect'));
}

// ── Disconnect ────────────────────────────────────────────────────────────────
const showDisconnect = ref(false);

function submitDisconnect() {
    showDisconnect.value = false;
    router.delete(route('bgr.disconnect'));
}

// ── Filters ───────────────────────────────────────────────────────────────────
const search       = ref(props.filters.search ?? '');
const statusFilter = ref(props.filters.status ?? '');

// Default view hides completed/cancelled (matches BGR portal behaviour).
// Selecting 'all' shows every project including closed ones.
const CLOSED_STATUSES = ['completed', 'cancelled'];
const displayedProjects = computed(() => {
    if (statusFilter.value === '') {
        return props.projects.filter(p => !CLOSED_STATUSES.includes(p.status));
    }
    return props.projects;
});

let debounceTimer = null;

function applyFilters() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        router.get(route('bgr.index'), {
            search: search.value || undefined,
            // 'all' keeps no status param on the server — just shows everything client-side
            status: (statusFilter.value && statusFilter.value !== 'all') ? statusFilter.value : undefined,
        }, { preserveState: true, replace: true });
    }, 300);
}

function goToPage(page) {
    router.get(route('bgr.index'), {
        search: search.value || undefined,
        status: statusFilter.value || undefined,
        page,
    }, { preserveState: true });
}
</script>
