<template>
    <AppLayout :title="staffMember.name">
        <div class="max-w-4xl mx-auto space-y-5">

            <!-- Header card -->
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <div class="flex flex-col sm:flex-row items-center sm:items-start gap-5">
                    <img :src="staffMember.avatar_url" :alt="staffMember.name" class="w-20 h-20 rounded-full object-cover border-4 border-white shadow-md flex-shrink-0" />
                    <div class="flex-1 text-center sm:text-left">
                        <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2 mb-1">
                            <h1 class="text-xl font-bold text-gray-800">{{ staffMember.name }}</h1>
                            <span :class="staffMember.is_active ? 'badge-green' : 'badge-red'">
                                {{ staffMember.is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                        <p class="text-sm text-gray-500">{{ staffMember.email }}</p>
                        <div class="flex flex-wrap justify-center sm:justify-start gap-2 mt-2">
                            <span v-for="role in staffMember.roles" :key="role" :class="roleClass(role)">
                                {{ role.replace('_', ' ') }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-400 mt-2">
                            Member since {{ formatDate(staffMember.created_at) }}
                            <span v-if="staffMember.hire_date"> · Hired {{ formatDate(staffMember.hire_date) }}</span>
                        </p>
                    </div>
                    <!-- Actions -->
                    <div class="flex gap-2 flex-shrink-0">
                        <Link
                            :href="route('staff.edit', staffMember.id)"
                            class="inline-flex items-center gap-1.5 bg-[#EF233C] text-white text-sm px-3 py-2 rounded-lg hover:bg-[#D90429] transition-colors"
                        >
                            <PencilIcon class="w-4 h-4" /> Edit
                        </Link>
                        <button
                            @click="toggleActive"
                            :class="staffMember.is_active
                                ? 'inline-flex items-center gap-1.5 bg-amber-50 text-amber-700 border border-amber-200 text-sm px-3 py-2 rounded-lg hover:bg-amber-100 transition-colors'
                                : 'inline-flex items-center gap-1.5 bg-green-50 text-green-700 border border-green-200 text-sm px-3 py-2 rounded-lg hover:bg-green-100 transition-colors'"
                        >
                            <component :is="staffMember.is_active ? NoSymbolIcon : CheckCircleIcon" class="w-4 h-4" />
                            {{ staffMember.is_active ? 'Deactivate' : 'Activate' }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Details + stats grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <!-- Stats -->
                <div class="bg-white rounded-xl border border-gray-200 p-5 flex flex-col items-center justify-center text-center">
                    <p class="text-3xl font-bold text-[#EF233C]">{{ totalHours }}</p>
                    <p class="text-xs text-gray-500 mt-1">Total Approved Hours</p>
                </div>

                <!-- Emergency contact -->
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Emergency Contact</p>
                    <p class="text-sm font-medium text-gray-800">{{ staffMember.emergency_contact_name || '—' }}</p>
                    <p class="text-sm text-gray-500 mt-0.5">{{ staffMember.emergency_contact_phone || '—' }}</p>
                </div>

                <!-- Certifications -->
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Certifications</p>
                    <div v-if="staffMember.certifications.length" class="flex flex-wrap gap-1.5">
                        <span
                            v-for="cert in staffMember.certifications"
                            :key="cert"
                            class="text-xs bg-green-50 border border-green-200 text-green-800 px-2 py-0.5 rounded-full"
                        >{{ cert }}</span>
                    </div>
                    <p v-else class="text-sm text-gray-400">None recorded.</p>
                </div>
            </div>

            <!-- Notes -->
            <div v-if="staffMember.notes" class="bg-white rounded-xl border border-gray-200 p-5">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Notes</p>
                <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ staffMember.notes }}</p>
            </div>

            <!-- Projects -->
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-sm font-semibold text-gray-800 flex items-center gap-2">
                        <FolderIcon class="w-4 h-4 text-[#EF233C]" /> Assigned Projects
                        <span class="text-xs font-normal text-gray-400">({{ projects.length }})</span>
                    </h2>
                    <Link :href="route('projects.index')" class="text-xs text-[#EF233C] hover:underline">View all</Link>
                </div>
                <div v-if="!projects.length" class="text-center py-6">
                    <p class="text-sm text-gray-400">Not assigned to any projects.</p>
                </div>
                <div v-else class="space-y-2">
                    <Link
                        v-for="project in projects"
                        :key="project.id"
                        :href="route('projects.show', project.id)"
                        class="flex items-center gap-3 p-3 rounded-lg border border-gray-100 hover:border-gray-200 hover:bg-gray-50 transition-all group"
                    >
                        <div :class="['w-1 h-8 rounded-full shrink-0', statusBarClass(project.status)]" />
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-1.5 mb-0.5">
                                <span :class="businessClass(project.business)">{{ project.business?.toUpperCase() }}</span>
                                <span class="text-sm font-medium text-gray-800 truncate group-hover:text-[#EF233C] transition-colors">{{ project.name }}</span>
                            </div>
                            <p class="text-xs text-gray-400 truncate">{{ project.customer }}</p>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <span :class="project.role === 'lead'
                                ? 'text-xs font-medium px-2 py-0.5 rounded-full bg-amber-100 text-amber-700'
                                : 'text-xs font-medium px-2 py-0.5 rounded-full bg-blue-50 text-blue-600'">
                                {{ project.role === 'lead' ? 'Lead' : 'Support' }}
                            </span>
                            <span :class="statusClass(project.status)">{{ statusLabel(project.status) }}</span>
                        </div>
                    </Link>
                </div>
            </div>

            <!-- Recent attendance -->
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-sm font-semibold text-gray-800">Recent Attendance</h2>
                    <Link :href="`/attendance?user_id=${staffMember.id}`" class="text-xs text-[#EF233C] hover:underline">View all</Link>
                </div>
                <div v-if="!recentEntries.length" class="text-center py-6 text-gray-400 text-sm">No entries yet.</div>
                <div v-else class="overflow-x-auto -mx-5 px-5">
                <table class="w-full text-xs min-w-[280px]">
                    <thead>
                        <tr class="text-gray-400 border-b border-gray-100">
                            <th class="text-left pb-2 font-medium">Date</th>
                            <th class="text-left pb-2 font-medium">In</th>
                            <th class="text-left pb-2 font-medium">Out</th>
                            <th class="text-right pb-2 font-medium">Hours</th>
                            <th class="text-right pb-2 font-medium">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="e in recentEntries" :key="e.id" class="border-b border-gray-50 last:border-0">
                            <td class="py-2 text-gray-700 whitespace-nowrap">{{ e.date }}</td>
                            <td class="py-2 font-mono text-gray-600 whitespace-nowrap">{{ e.clock_in }}</td>
                            <td class="py-2 font-mono text-gray-600 whitespace-nowrap">{{ e.clock_out ?? '—' }}</td>
                            <td class="py-2 text-right text-gray-700">{{ e.hours ?? '—' }}</td>
                            <td class="py-2 text-right">
                                <span :class="statusClass(e.status)">{{ e.status }}</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
                </div>
            </div>

        </div>
    </AppLayout>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { PencilIcon, NoSymbolIcon, CheckCircleIcon, FolderIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    staffMember:   { type: Object, required: true },
    recentEntries: { type: Array,  default: () => [] },
    totalHours:    { type: Number, default: 0 },
    projects:      { type: Array,  default: () => [] },
});

function toggleActive() {
    router.post(route('staff.toggle-active', props.staffMember.id), {}, { preserveScroll: true });
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
function roleClass(r) { return roleColors[r] ?? roleColors.staff; }

function statusClass(s) {
    const attendance = { approved: 'text-green-600 font-medium', pending: 'text-amber-600 font-medium', rejected: 'text-red-600 font-medium' };
    const project    = { planning: 'inline-flex text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full font-medium', active: 'inline-flex text-xs bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full font-medium', on_hold: 'inline-flex text-xs bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full font-medium', complete: 'inline-flex text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full font-medium' };
    return attendance[s] ?? project[s] ?? 'text-gray-500';
}

const statusLabels = { planning: 'Planning', active: 'Active', on_hold: 'On Hold', complete: 'Complete' };
function statusLabel(s) { return statusLabels[s] ?? s; }

const statusBarClasses = { planning: 'bg-gray-300', active: 'bg-emerald-400', on_hold: 'bg-amber-400', complete: 'bg-blue-400' };
function statusBarClass(s) { return statusBarClasses[s] ?? 'bg-gray-200'; }

const businessClasses = {
    bcf: 'text-xs font-bold px-1.5 py-0.5 rounded bg-[#EF233C]/10 text-[#EF233C] shrink-0',
    bgr: 'text-xs font-bold px-1.5 py-0.5 rounded bg-blue-100 text-blue-700 shrink-0',
};
function businessClass(b) { return businessClasses[b] ?? businessClasses.bcf; }
</script>

<style scoped>
.badge-green { @apply text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full font-medium; }
.badge-red   { @apply text-xs bg-red-100 text-red-600 px-2 py-0.5 rounded-full font-medium; }
</style>
