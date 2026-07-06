<template>
    <AppLayout title="Activity Log">
        <div class="max-w-3xl mx-auto space-y-4">

            <Link :href="route('activity-logs.index')" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700">
                <ArrowLeftIcon class="w-4 h-4" /> Activity Logs
            </Link>

            <!-- Header -->
            <div class="bg-white rounded-2xl border border-gray-200 p-5 flex items-center gap-3">
                <img :src="log.user.avatar_url" :alt="log.user.name" class="w-11 h-11 rounded-full object-cover" />
                <div class="flex-1 min-w-0">
                    <p class="text-base font-bold text-gray-900">{{ log.user.name }}</p>
                    <p class="text-xs text-gray-400">{{ prettyDate(log.log_date) }} · {{ log.activities.length }} activities · {{ formatMins(log.total_minutes) }}</p>
                </div>
                <span :class="log.status === 'submitted' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'" class="text-xs font-medium px-2.5 py-1 rounded-full capitalize">{{ log.status }}</span>
            </div>

            <!-- Activities -->
            <div class="bg-white rounded-2xl border border-gray-200 divide-y divide-gray-100">
                <p class="px-4 py-2.5 text-xs font-bold text-gray-500 uppercase tracking-wide">Activities</p>
                <div v-if="log.activities.length === 0" class="px-4 py-8 text-center text-sm text-gray-400">No activities logged.</div>
                <div v-for="a in log.activities" :key="a.id" class="px-4 py-3 flex items-start gap-3">
                    <span :class="['mt-0.5 text-[10px] font-bold uppercase px-1.5 py-0.5 rounded flex-shrink-0', catClass(a.category)]">{{ catLabel(a.category) }}</span>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-gray-800 whitespace-pre-wrap break-words">{{ a.description }}</p>
                        <div class="flex flex-wrap items-center gap-2 mt-1 text-xs text-gray-400">
                            <span v-if="a.duration_minutes" class="inline-flex items-center gap-1"><ClockIcon class="w-3 h-3" />{{ formatMins(a.duration_minutes) }}</span>
                            <span v-if="a.job" class="inline-flex items-center gap-1 text-blue-500"><BriefcaseIcon class="w-3 h-3" />{{ a.job.title }}</span>
                        </div>
                        <div v-if="a.photos.length" class="flex flex-wrap gap-1.5 mt-2">
                            <a v-for="(p, i) in a.photos" :key="i" :href="p.url" target="_blank"><img :src="p.url" class="w-16 h-16 rounded-lg object-cover border border-gray-200" /></a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- EOD -->
            <div v-if="log.summary || log.blockers || log.plan_tomorrow" class="bg-white rounded-2xl border border-gray-200 p-4 space-y-3">
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wide">End of day</p>
                <div v-if="log.summary"><p class="text-xs font-medium text-gray-500 mb-0.5">Accomplishments</p><p class="text-sm text-gray-800 whitespace-pre-wrap">{{ log.summary }}</p></div>
                <div v-if="log.blockers"><p class="text-xs font-medium text-gray-500 mb-0.5">Blockers</p><p class="text-sm text-gray-800 whitespace-pre-wrap">{{ log.blockers }}</p></div>
                <div v-if="log.plan_tomorrow"><p class="text-xs font-medium text-gray-500 mb-0.5">Plan for tomorrow</p><p class="text-sm text-gray-800 whitespace-pre-wrap">{{ log.plan_tomorrow }}</p></div>
            </div>

            <!-- Acknowledge -->
            <div class="bg-white rounded-2xl border border-gray-200 p-4">
                <div v-if="log.acknowledged" class="flex items-start gap-2">
                    <CheckBadgeIcon class="w-5 h-5 text-emerald-500 flex-shrink-0" />
                    <div class="text-sm text-gray-700">
                        Reviewed by <strong>{{ log.acknowledged_by }}</strong>
                        <span class="text-gray-400">· {{ fromNow(log.acknowledged_at) }}</span>
                        <p v-if="log.manager_comment" class="italic text-gray-600 mt-0.5">“{{ log.manager_comment }}”</p>
                    </div>
                </div>
                <div v-else>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Comment (optional)</label>
                    <textarea v-model="comment" rows="2" class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 resize-none focus:outline-none focus:ring-2 focus:ring-[#EF233C]/15 focus:border-[#EF233C]/40" placeholder="Feedback for the staff member…" />
                    <button @click="acknowledge" :disabled="saving" class="mt-2 text-xs font-semibold bg-[#2B2D42] hover:bg-[#EF233C] text-white px-4 py-2 rounded-lg disabled:opacity-50">
                        <CheckBadgeIcon class="w-4 h-4 inline -mt-0.5" /> Mark reviewed
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import dayjs from 'dayjs';
import relativeTime from 'dayjs/plugin/relativeTime';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ArrowLeftIcon, ClockIcon, BriefcaseIcon, CheckBadgeIcon } from '@heroicons/vue/24/outline';
dayjs.extend(relativeTime);

const props = defineProps({ log: { type: Object, required: true } });

const comment = ref('');
const saving = ref(false);
function acknowledge() {
    saving.value = true;
    router.post(route('activity-logs.acknowledge', props.log.id), { comment: comment.value }, {
        preserveScroll: true,
        onFinish: () => { saving.value = false; },
    });
}

const CAT = {
    installation: ['Install', 'bg-blue-100 text-blue-700'],
    travel:       ['Travel', 'bg-purple-100 text-purple-700'],
    admin:        ['Admin', 'bg-gray-100 text-gray-600'],
    meeting:      ['Meeting', 'bg-amber-100 text-amber-700'],
    site_visit:   ['Site visit', 'bg-emerald-100 text-emerald-700'],
    other:        ['Other', 'bg-gray-100 text-gray-600'],
};
function catLabel(c) { return CAT[c]?.[0] ?? c; }
function catClass(c) { return CAT[c]?.[1] ?? 'bg-gray-100 text-gray-600'; }
function prettyDate(d) { return new Date(d + 'T00:00:00').toLocaleDateString('en-GB', { day: '2-digit', month: '2-digit', year: 'numeric' }); }
function formatMins(m) { if (!m) return '0m'; const h = Math.floor(m / 60); const min = m % 60; return (h ? `${h}h ` : '') + (min ? `${min}m` : (h ? '' : '0m')); }
function fromNow(iso) { return iso ? dayjs(iso).fromNow() : ''; }
</script>
