<template>
    <AppLayout title="Attendance">

        <!-- ── Header row ─────────────────────────────────────────────────── -->
        <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
            <div>
                <h1 class="text-lg font-semibold text-gray-800">Attendance</h1>
                <p class="text-xs text-gray-500 mt-0.5">
                    <span v-if="isManager && pendingCount > 0" class="text-amber-600 font-medium">
                        {{ pendingCount }} pending approval{{ pendingCount !== 1 ? 's' : '' }}
                    </span>
                    <span v-else>All time entries</span>
                </p>
            </div>
            <div class="flex gap-2">
                <Link
                    v-if="isSiteHead"
                    href="/qr-scanner"
                    class="inline-flex items-center gap-1.5 bg-[#EF233C] text-white text-sm px-3 py-2 rounded-lg hover:bg-[#D90429] transition-colors"
                >
                    <QrCodeIcon class="w-4 h-4" />
                    <span class="hidden sm:inline">QR Scanner</span>
                </Link>
                <a
                    v-if="isManager"
                    :href="exportUrl"
                    class="inline-flex items-center gap-1.5 bg-white border border-gray-200 text-gray-700 text-sm px-3 py-2 rounded-lg hover:bg-gray-50 transition-colors"
                >
                    <ArrowDownTrayIcon class="w-4 h-4" />
                    <span class="hidden sm:inline">Export</span>
                </a>
                <button
                    v-if="isManager"
                    @click="openAddEntry"
                    class="inline-flex items-center gap-1.5 bg-white border border-gray-200 text-gray-700 text-sm px-3 py-2 rounded-lg hover:bg-gray-50 transition-colors"
                >
                    <PlusIcon class="w-4 h-4" />
                    <span class="hidden sm:inline">Add Entry</span>
                </button>
                <button
                    v-if="isManager && selectedIds.length > 0"
                    @click="bulkApprove"
                    class="inline-flex items-center gap-1.5 bg-green-600 text-white text-sm px-3 py-2 rounded-lg hover:bg-green-700 transition-colors"
                >
                    <CheckIcon class="w-4 h-4" />
                    <span>Approve {{ selectedIds.length }}</span>
                </button>
            </div>
        </div>

        <!-- ── Filters ────────────────────────────────────────────────────── -->
        <div class="bg-white rounded-xl border border-gray-200 p-4 mb-4">
            <div class="grid grid-cols-2 sm:flex sm:flex-wrap gap-3">
                <div v-if="isPrivileged" class="col-span-2 sm:flex-1 sm:min-w-40">
                    <label class="block text-xs text-gray-500 mb-1">Staff Member</label>
                    <select v-model="filters.user_id" @change="applyFilters" class="w-full text-sm border-gray-200 rounded-lg focus:ring-[#EF233C] focus:border-[#EF233C]">
                        <option value="">All staff</option>
                        <option v-for="s in staffList" :key="s.id" :value="s.id">{{ s.name }}</option>
                    </select>
                </div>
                <div class="sm:flex-1 sm:min-w-32">
                    <label class="block text-xs text-gray-500 mb-1">Status</label>
                    <select v-model="filters.status" @change="applyFilters" class="w-full text-sm border-gray-200 rounded-lg focus:ring-[#EF233C] focus:border-[#EF233C]">
                        <option value="">All statuses</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
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

        <!-- ── Mobile: Card list ──────────────────────────────────────────── -->
        <div class="lg:hidden space-y-3">
            <div v-if="entries.data.length === 0" class="bg-white rounded-xl border border-gray-200 px-4 py-12 text-center text-gray-400 text-sm">
                No entries found.
            </div>

            <div
                v-for="entry in entries.data"
                :key="entry.id"
                :class="['bg-white rounded-xl border border-gray-200 p-4', entry.ot_approved && 'border-l-4 border-l-amber-400']"
            >
                <div class="flex items-start justify-between gap-2 mb-3">
                    <div class="flex items-center gap-2 min-w-0">
                        <div v-if="isManager">
                            <input
                                v-if="entry.status === 'pending'"
                                type="checkbox"
                                :value="entry.id"
                                v-model="selectedIds"
                                class="rounded border-gray-300 text-[#EF233C] focus:ring-[#EF233C]"
                            />
                        </div>
                        <div v-if="isPrivileged" class="flex items-center gap-2 min-w-0">
                            <img :src="entry.user?.avatar_url" :alt="entry.user?.name" class="w-8 h-8 rounded-full object-cover flex-shrink-0" />
                            <span class="text-sm font-semibold text-gray-800 truncate">{{ entry.user?.name }}</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-1.5 flex-shrink-0">
                        <span :class="statusClass(entry.status)">{{ entry.status }}</span>
                        <span v-if="entry.ot_approved" class="text-xs bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded font-medium">OT</span>
                        <span v-if="entry.flagged" class="text-xs bg-red-100 text-red-700 px-1.5 py-0.5 rounded font-medium" title="Unusually long shift — check for a missed clock-out">⚠</span>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-2 mb-3">
                    <div>
                        <p class="text-xs text-gray-400 mb-0.5">Date</p>
                        <p class="text-sm font-medium text-gray-700">{{ formatDate(entry.clock_in, entry.user?.timezone) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 mb-0.5">In</p>
                        <p class="text-sm font-mono text-gray-700">{{ formatTime(entry.clock_in, entry.user?.timezone) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 mb-0.5">Out</p>
                        <p v-if="entry.clock_out" class="text-sm font-mono text-gray-700">{{ formatTime(entry.clock_out, entry.user?.timezone) }}</p>
                        <p v-else class="text-sm text-green-600 font-medium flex items-center gap-1">
                            <span class="w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse inline-block" />
                            Active
                        </p>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span :class="sourceClass(entry.source)">{{ sourceLabel(entry.source) }}</span>
                        <span v-if="entry.total_hours" class="text-xs text-gray-500">{{ entry.total_hours }}h</span>
                        <span v-if="entry.breaks_sum_duration_minutes" class="text-xs text-gray-400">· {{ entry.breaks_sum_duration_minutes }}m break</span>
                    </div>
                    <div v-if="isManager" class="flex items-center gap-1.5">
                        <button @click="openEdit(entry)" class="text-xs text-gray-500 hover:text-[#2B2D42] bg-gray-100 hover:bg-gray-200 px-3 py-1.5 rounded-lg transition-colors font-medium">Edit</button>
                        <template v-if="entry.status === 'pending'">
                            <button @click="approve(entry.id)" class="text-xs bg-green-100 text-green-700 hover:bg-green-200 px-3 py-1.5 rounded-lg transition-colors font-medium">Approve</button>
                            <button @click="openReject(entry)" class="text-xs bg-red-100 text-red-700 hover:bg-red-200 px-3 py-1.5 rounded-lg transition-colors font-medium">Reject</button>
                        </template>
                        <span v-else-if="entry.status === 'rejected' && entry.rejection_reason" class="text-xs text-gray-400 cursor-help underline decoration-dotted" :title="entry.rejection_reason">reason</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Desktop: Table ─────────────────────────────────────────────── -->
        <div class="hidden lg:block bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th v-if="isManager" class="w-10 px-4 py-3">
                                <input type="checkbox" :checked="allPendingSelected" @change="toggleSelectAll" class="rounded border-gray-300 text-[#EF233C] focus:ring-[#EF233C]" />
                            </th>
                            <th v-if="isPrivileged" class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Staff</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Date</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Clock In</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Clock Out</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Worked</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Breaks</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Source</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                            <th v-if="isManager" class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-if="entries.data.length === 0">
                            <td :colspan="isManager ? 10 : (isPrivileged ? 9 : 8)" class="px-4 py-12 text-center text-gray-400 text-sm">No entries found.</td>
                        </tr>
                        <tr
                            v-for="entry in entries.data"
                            :key="entry.id"
                            :class="['hover:bg-gray-50 transition-colors', entry.ot_approved ? 'bg-amber-50/40' : '']"
                        >
                            <td v-if="isManager" class="px-4 py-3">
                                <input v-if="entry.status === 'pending'" type="checkbox" :value="entry.id" v-model="selectedIds" class="rounded border-gray-300 text-[#EF233C] focus:ring-[#EF233C]" />
                            </td>
                            <td v-if="isPrivileged" class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <img :src="entry.user?.avatar_url" :alt="entry.user?.name" class="w-6 h-6 rounded-full object-cover flex-shrink-0" />
                                    <span class="text-gray-700 font-medium">{{ entry.user?.name }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ formatDate(entry.clock_in, entry.user?.timezone) }}</td>
                            <td class="px-4 py-3 text-gray-700 font-mono text-xs">{{ formatTime(entry.clock_in, entry.user?.timezone) }}</td>
                            <td class="px-4 py-3 text-gray-700 font-mono text-xs">
                                <span v-if="entry.clock_out">{{ formatTime(entry.clock_out, entry.user?.timezone) }}</span>
                                <span v-else class="inline-flex items-center gap-1 text-green-600 font-sans font-medium text-xs">
                                    <span class="w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse inline-block" />
                                    {{ clockStateLabel(entry.clock_state) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-600">
                                <span v-if="entry.total_hours" :class="entry.flagged ? 'text-red-600 font-semibold' : ''">{{ entry.total_hours }}h</span>
                                <span v-else-if="!entry.clock_out" class="text-green-600 font-medium text-xs">In progress</span>
                                <span v-else>—</span>
                                <span v-if="entry.ot_approved" class="ml-1 text-xs bg-amber-100 text-amber-700 px-1 py-0.5 rounded font-medium">OT</span>
                                <span v-if="entry.flagged" class="ml-1 text-xs bg-red-100 text-red-700 px-1 py-0.5 rounded font-medium" title="Unusually long shift — check for a missed clock-out">⚠ Review</span>
                            </td>
                            <td class="px-4 py-3">
                                <span v-if="entry.breaks_sum_duration_minutes" class="text-xs text-gray-500 bg-gray-100 px-2 py-0.5 rounded-full">
                                    {{ entry.breaks_sum_duration_minutes }}m
                                </span>
                                <span v-else class="text-gray-300">—</span>
                            </td>
                            <td class="px-4 py-3"><span :class="sourceClass(entry.source)">{{ sourceLabel(entry.source) }}</span></td>
                            <td class="px-4 py-3"><span :class="statusClass(entry.status)">{{ entry.status }}</span></td>
                            <td v-if="isManager" class="px-4 py-3 text-right">
                                <div class="flex justify-end items-center gap-1.5">
                                    <button @click="openEdit(entry)" class="text-xs text-gray-500 hover:text-[#2B2D42] hover:bg-gray-100 px-2 py-1 rounded transition-colors font-medium">Edit</button>
                                    <template v-if="entry.status === 'pending'">
                                        <button @click="approve(entry.id)" class="text-xs bg-green-100 text-green-700 hover:bg-green-200 px-2 py-1 rounded transition-colors font-medium">Approve</button>
                                        <button @click="openReject(entry)" class="text-xs bg-red-100 text-red-700 hover:bg-red-200 px-2 py-1 rounded transition-colors font-medium">Reject</button>
                                    </template>
                                    <span v-else-if="entry.status === 'rejected' && entry.rejection_reason" class="text-xs text-gray-400 cursor-help underline decoration-dotted" :title="entry.rejection_reason">reason</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="entries.last_page > 1" class="flex items-center justify-between px-4 py-3 border-t border-gray-100 bg-gray-50">
                <p class="text-xs text-gray-500">Showing {{ entries.from }}–{{ entries.to }} of {{ entries.total }}</p>
                <div class="flex gap-1">
                    <Link
                        v-for="link in entries.links"
                        :key="link.label"
                        :href="link.url ?? '#'"
                        :class="['px-2.5 py-1 text-xs rounded transition-colors', link.active ? 'bg-[#EF233C] text-white' : 'text-gray-600 hover:bg-gray-100', !link.url ? 'opacity-40 pointer-events-none' : '']"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>

        <!-- Mobile pagination -->
        <div v-if="entries.last_page > 1" class="lg:hidden flex items-center justify-between mt-4 bg-white rounded-xl border border-gray-200 px-4 py-3">
            <p class="text-xs text-gray-500">{{ entries.from }}–{{ entries.to }} of {{ entries.total }}</p>
            <div class="flex gap-1">
                <Link
                    v-for="link in entries.links"
                    :key="link.label"
                    :href="link.url ?? '#'"
                    :class="['px-2.5 py-1 text-xs rounded transition-colors', link.active ? 'bg-[#EF233C] text-white' : 'text-gray-600 hover:bg-gray-100', !link.url ? 'opacity-40 pointer-events-none' : '']"
                    v-html="link.label"
                />
            </div>
        </div>

        <!-- Add Entry modal -->
        <BaseModal :open="addEntryModal.open" @close="closeAddEntry" max-width="sm:max-w-lg">
            <div class="p-6">
                <h3 class="text-base font-semibold text-gray-800 mb-1">Add Attendance Entry</h3>
                <p class="text-sm text-gray-500 mb-5">Manually record time for one or more staff members. Entries are auto-approved.</p>

                <!-- Staff multi-select -->
                <div class="mb-4">
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Staff Member(s)</label>
                    <div class="border border-gray-200 rounded-lg max-h-40 overflow-y-auto divide-y divide-gray-100">
                        <label
                            v-for="s in staffList"
                            :key="s.id"
                            class="flex items-center gap-3 px-3 py-2 hover:bg-gray-50 cursor-pointer"
                        >
                            <input
                                type="checkbox"
                                :value="s.id"
                                v-model="addEntryModal.user_ids"
                                class="rounded border-gray-300 text-[#EF233C] focus:ring-[#EF233C]"
                            />
                            <span class="text-sm text-gray-700">{{ s.name }}</span>
                        </label>
                    </div>
                    <p v-if="addEntryModal.user_ids.length > 0" class="text-xs text-gray-400 mt-1">{{ addEntryModal.user_ids.length }} selected</p>
                </div>

                <!-- Date -->
                <div class="mb-4">
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Date</label>
                    <input
                        v-model="addEntryModal.date"
                        type="date"
                        :max="today"
                        class="w-full text-sm border-gray-200 rounded-lg focus:ring-[#EF233C] focus:border-[#EF233C]"
                    />
                </div>

                <!-- Times -->
                <div class="grid grid-cols-2 gap-3 mb-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Clock In</label>
                        <input
                            v-model="addEntryModal.clock_in"
                            type="time"
                            class="w-full text-sm border-gray-200 rounded-lg focus:ring-[#EF233C] focus:border-[#EF233C]"
                        />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Clock Out <span class="text-gray-400 font-normal">(optional)</span></label>
                        <input
                            v-model="addEntryModal.clock_out"
                            type="time"
                            class="w-full text-sm border-gray-200 rounded-lg focus:ring-[#EF233C] focus:border-[#EF233C]"
                        />
                    </div>
                </div>

                <!-- Notes -->
                <div class="mb-5">
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Notes <span class="text-gray-400 font-normal">(optional)</span></label>
                    <textarea
                        v-model="addEntryModal.notes"
                        rows="2"
                        placeholder="e.g. Forgot to clock in on site"
                        class="w-full text-sm border-gray-200 rounded-lg focus:ring-[#EF233C] focus:border-[#EF233C] resize-none"
                    />
                </div>

                <p v-if="addEntryModal.error" class="text-xs text-red-600 mb-3">{{ addEntryModal.error }}</p>

                <div class="flex justify-end gap-2">
                    <button @click="closeAddEntry" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 transition-colors">Cancel</button>
                    <button
                        @click="submitAddEntry"
                        :disabled="addEntryModal.submitting"
                        class="px-4 py-2 text-sm bg-[#EF233C] text-white rounded-lg hover:bg-[#D90429] transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        {{ addEntryModal.submitting ? 'Saving…' : 'Add Entry' }}
                    </button>
                </div>
            </div>
        </BaseModal>

        <!-- Reject modal -->
        <BaseModal :open="rejectModal.open" @close="rejectModal.open = false" max-width="sm:max-w-md">
            <div class="p-6">
                <h3 class="text-base font-semibold text-gray-800 mb-1">Reject Entry</h3>
                <p class="text-sm text-gray-500 mb-4">Provide an optional reason for rejecting this entry.</p>
                <textarea v-model="rejectModal.reason" rows="3" placeholder="Reason (optional)" class="w-full text-sm border-gray-200 rounded-lg focus:ring-[#EF233C] focus:border-[#EF233C] resize-none" />
                <div class="flex justify-end gap-2 mt-4">
                    <button @click="rejectModal.open = false" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 transition-colors">Cancel</button>
                    <button @click="confirmReject" class="px-4 py-2 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">Reject Entry</button>
                </div>
            </div>
        </BaseModal>

        <!-- Edit entry modal -->
        <BaseModal :open="editModal.open" @close="editModal.open = false" max-width="sm:max-w-lg">
            <div class="p-6">
                <h3 class="text-base font-semibold text-gray-800 mb-1">Edit Attendance Entry</h3>
                <p class="text-sm text-gray-500 mb-4">Correct the clock-in / clock-out (e.g. a forgotten clock-out). Hours recalculate automatically.</p>
                <p v-if="editModal.staffName" class="text-xs font-medium text-gray-600 mb-1">{{ editModal.staffName }}</p>
                <p class="text-xs text-gray-400 mb-4">Times are in the worker's timezone: <span class="font-medium text-gray-600">{{ editModal.tz }}</span></p>

                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Clock In</label>
                        <input v-model="editModal.clock_in" type="datetime-local" class="w-full text-sm border-gray-200 rounded-lg focus:ring-[#EF233C] focus:border-[#EF233C]" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Clock Out <span class="text-gray-400 font-normal">(leave blank if still active)</span></label>
                        <input v-model="editModal.clock_out" type="datetime-local" class="w-full text-sm border-gray-200 rounded-lg focus:ring-[#EF233C] focus:border-[#EF233C]" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Note <span class="text-gray-400 font-normal">(optional)</span></label>
                        <input v-model="editModal.notes" type="text" placeholder="e.g. corrected forgotten clock-out" class="w-full text-sm border-gray-200 rounded-lg focus:ring-[#EF233C] focus:border-[#EF233C]" />
                    </div>
                </div>

                <div v-if="editModal.breakMins > 0" class="text-xs text-gray-400 mt-2">Break deducted: {{ editModal.breakMins }} min</div>
                <p v-if="editPreviewHours !== null" class="text-sm text-gray-600 mt-2">New total: <span class="font-semibold" :class="editPreviewHours > 12 ? 'text-red-600' : 'text-gray-800'">{{ editPreviewHours }}h</span></p>
                <p v-if="editModal.error" class="text-xs text-red-600 mt-2">{{ editModal.error }}</p>

                <div class="flex justify-end gap-2 mt-5">
                    <button @click="editModal.open = false" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 transition-colors">Cancel</button>
                    <button @click="submitEdit" :disabled="editModal.submitting" class="px-4 py-2 text-sm bg-[#2B2D42] hover:bg-[#EF233C] text-white rounded-lg transition-colors disabled:opacity-60">
                        {{ editModal.submitting ? 'Saving…' : 'Save changes' }}
                    </button>
                </div>
            </div>
        </BaseModal>

    </AppLayout>
</template>

<script setup>
import { ref, computed, reactive } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { usePermission } from '@/Composables/usePermission';
import AppLayout from '@/Layouts/AppLayout.vue';
import BaseModal from '@/Components/BaseModal.vue';
import {
    QrCodeIcon,
    CheckIcon,
    PlusIcon,
    ArrowDownTrayIcon,
} from '@heroicons/vue/24/outline';

// ── Props ─────────────────────────────────────────────────────────────────────

const props = defineProps({
    entries:      { type: Object,  required: true },
    pendingCount: { type: Number,  default: 0 },
    staffList:    { type: Array,   default: () => [] },
    isPrivileged: { type: Boolean, default: false },
    isManager:    { type: Boolean, default: false },
    filters:      { type: Object,  default: () => ({}) },
});

const { isSiteHead } = usePermission();

// ── Filters ───────────────────────────────────────────────────────────────────

const filters = reactive({
    user_id: props.filters.user_id ?? '',
    status:  props.filters.status  ?? '',
    from:    props.filters.from    ?? '',
    to:      props.filters.to      ?? '',
});

function applyFilters() {
    router.get('/attendance', filters, { preserveState: true, replace: true });
}

const exportUrl = computed(() => {
    const params = new URLSearchParams();
    if (filters.user_id) params.set('user_id', filters.user_id);
    if (filters.status)  params.set('status',  filters.status);
    if (filters.from)    params.set('from',     filters.from);
    if (filters.to)      params.set('to',       filters.to);
    const qs = params.toString();
    return '/attendance/export' + (qs ? '?' + qs : '');
});

function clearFilters() {
    Object.assign(filters, { user_id: '', status: '', from: '', to: '' });
    applyFilters();
}

// ── Bulk select & approvals ───────────────────────────────────────────────────

const selectedIds        = ref([]);
const pendingEntries     = computed(() => props.entries.data.filter(e => e.status === 'pending'));
const allPendingSelected = computed(() =>
    pendingEntries.value.length > 0 &&
    pendingEntries.value.every(e => selectedIds.value.includes(e.id))
);

function toggleSelectAll() {
    allPendingSelected.value
        ? (selectedIds.value = [])
        : (selectedIds.value = pendingEntries.value.map(e => e.id));
}

function approve(id) {
    router.post(`/attendance/${id}/approve`, {}, { preserveScroll: true });
}

const rejectModal = reactive({ open: false, entryId: null, reason: '' });

// ── Add entry modal ───────────────────────────────────────────────────────────

const today = new Date().toISOString().slice(0, 10);

const addEntryDefaults = () => ({
    open:       false,
    user_ids:   [],
    date:       today,
    clock_in:   '08:00',
    clock_out:  '',
    notes:      '',
    error:      '',
    submitting: false,
});

const addEntryModal = reactive(addEntryDefaults());

function openAddEntry() {
    Object.assign(addEntryModal, addEntryDefaults(), { open: true });
}

function closeAddEntry() {
    addEntryModal.open = false;
}

function submitAddEntry() {
    addEntryModal.error = '';

    if (addEntryModal.user_ids.length === 0) {
        addEntryModal.error = 'Please select at least one staff member.';
        return;
    }
    if (! addEntryModal.date) {
        addEntryModal.error = 'Please select a date.';
        return;
    }
    if (! addEntryModal.clock_in) {
        addEntryModal.error = 'Please enter a clock-in time.';
        return;
    }

    addEntryModal.submitting = true;

    router.post('/attendance/manual', {
        user_ids:  addEntryModal.user_ids,
        date:      addEntryModal.date,
        clock_in:  addEntryModal.clock_in,
        clock_out: addEntryModal.clock_out || null,
        notes:     addEntryModal.notes || null,
    }, {
        preserveScroll: true,
        onSuccess: () => { addEntryModal.open = false; },
        onError:   (errors) => {
            const first = Object.values(errors)[0];
            addEntryModal.error = first ?? 'Something went wrong.';
            addEntryModal.submitting = false;
        },
        onFinish: () => { addEntryModal.submitting = false; },
    });
}

function openReject(entry) {
    rejectModal.entryId = entry.id;
    rejectModal.reason  = '';
    rejectModal.open    = true;
}

function confirmReject() {
    router.post(`/attendance/${rejectModal.entryId}/reject`, { reason: rejectModal.reason }, {
        preserveScroll: true,
        onSuccess: () => { rejectModal.open = false; },
    });
}

// ── Edit entry modal (manager) ────────────────────────────────────────────────

const editModal = reactive({ open: false, id: null, staffName: '', tz: '', clock_in: '', clock_out: '', notes: '', breakMins: 0, error: '', submitting: false });

// Stored UTC instant -> a datetime-local value showing the wall-clock time in the
// worker's timezone (so the manager edits in the worker's local time).
function toZonedInput(iso, tz) {
    if (! iso) return '';
    const p = new Intl.DateTimeFormat('en-CA', {
        timeZone: tz || undefined,
        year: 'numeric', month: '2-digit', day: '2-digit',
        hour: '2-digit', minute: '2-digit', hour12: false,
    }).formatToParts(new Date(iso)).reduce((a, x) => (a[x.type] = x.value, a), {});
    const hour = p.hour === '24' ? '00' : p.hour; // some engines emit 24 for midnight
    return `${p.year}-${p.month}-${p.day}T${hour}:${p.minute}`;
}

function openEdit(entry) {
    const tz = entry.user?.timezone || 'Europe/London';
    Object.assign(editModal, {
        open:       true,
        id:         entry.id,
        staffName:  entry.user?.name ?? '',
        tz,
        clock_in:   toZonedInput(entry.clock_in, tz),
        clock_out:  toZonedInput(entry.clock_out, tz),
        notes:      '',
        breakMins:  entry.breaks_sum_duration_minutes ?? 0,
        error:      '',
        submitting: false,
    });
}

const editPreviewHours = computed(() => {
    if (! editModal.clock_in || ! editModal.clock_out) return null;
    const a = new Date(editModal.clock_in).getTime();
    const b = new Date(editModal.clock_out).getTime();
    if (isNaN(a) || isNaN(b) || b <= a) return null;
    const hrs = (b - a) / 3600000 - (editModal.breakMins || 0) / 60;
    return Math.max(0, Math.round(hrs * 100) / 100);
});

function submitEdit() {
    editModal.error = '';
    if (! editModal.clock_in) { editModal.error = 'Clock-in is required.'; return; }
    if (editModal.clock_out && new Date(editModal.clock_out) <= new Date(editModal.clock_in)) {
        editModal.error = 'Clock-out must be after clock-in.';
        return;
    }
    editModal.submitting = true;
    // Send the worker's local wall-clock times; the server converts to UTC using
    // the worker's timezone.
    router.patch(`/attendance/${editModal.id}`, {
        clock_in:  editModal.clock_in,
        clock_out: editModal.clock_out || null,
        notes:     editModal.notes || null,
    }, {
        preserveScroll: true,
        onSuccess: () => { editModal.open = false; },
        onError:   (errs) => { editModal.error = Object.values(errs)[0] ?? 'Something went wrong.'; editModal.submitting = false; },
        onFinish:  () => { editModal.submitting = false; },
    });
}

function bulkApprove() {
    router.post('/attendance/bulk-approve', { ids: selectedIds.value }, {
        preserveScroll: true,
        onSuccess: () => { selectedIds.value = []; },
    });
}

// ── Formatters ────────────────────────────────────────────────────────────────

// Attendance times are shown in the worker's own timezone (tz). Falls back to
// the viewer's browser zone when none is given.
function formatDate(dt, tz) {
    if (! dt) return '—';
    return new Date(dt).toLocaleDateString('en-GB', { day: '2-digit', month: '2-digit', year: 'numeric', timeZone: tz || undefined });
}

function formatTime(dt, tz) {
    if (! dt) return '—';
    return new Date(dt).toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit', timeZone: tz || undefined });
}

function clockStateLabel(state) {
    return { working: 'Active', on_break: 'On Break', on_lunch: 'Lunch' }[state] ?? 'Active';
}

const sourceLabels  = { self_clockin: 'Self', site_head: 'Site Head', manual: 'Manual', bulk: 'Bulk' };
const sourceClasses = {
    self_clockin: 'text-xs bg-blue-50 text-blue-700 px-2 py-0.5 rounded-full',
    site_head:    'text-xs bg-purple-50 text-purple-700 px-2 py-0.5 rounded-full',
    manual:       'text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full',
    bulk:         'text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full',
};

function sourceLabel(s) { return sourceLabels[s] ?? s; }
function sourceClass(s) { return sourceClasses[s] ?? sourceClasses.manual; }

function statusClass(status) {
    const map = {
        pending:  'text-xs bg-amber-50 text-amber-700 px-2 py-0.5 rounded-full font-medium capitalize',
        approved: 'text-xs bg-green-50 text-green-700 px-2 py-0.5 rounded-full font-medium capitalize',
        rejected: 'text-xs bg-red-50 text-red-700 px-2 py-0.5 rounded-full font-medium capitalize',
    };
    return map[status] ?? 'text-xs text-gray-500 capitalize';
}
</script>
