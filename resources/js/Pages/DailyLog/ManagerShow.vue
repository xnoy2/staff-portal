<template>
    <AppLayout title="Activity Log">
        <div class="max-w-3xl mx-auto space-y-4">

            <Link :href="route('activity-logs.index')" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700">
                <ArrowLeftIcon class="w-4 h-4" /> Daily Logs
            </Link>

            <!-- Header -->
            <div class="bg-white rounded-2xl border border-gray-200 p-5 flex items-center gap-3">
                <img :src="log.user.avatar_url" :alt="log.user.name" class="w-11 h-11 rounded-full object-cover" />
                <div class="flex-1 min-w-0">
                    <p class="text-base font-bold text-gray-900">{{ log.user.name }}</p>
                    <p class="text-xs text-gray-400">{{ prettyDate(log.log_date) }}</p>
                </div>
                <span :class="log.status === 'submitted' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'" class="text-xs font-medium px-2.5 py-1 rounded-full capitalize">{{ log.status }}</span>
            </div>

            <!-- EOD -->
            <div class="bg-white rounded-2xl border border-gray-200 p-4 space-y-3">
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wide">End of day</p>
                <div v-if="summaryLines.length">
                    <p class="text-xs font-medium text-gray-500 mb-1">Accomplishments</p>
                    <ul class="list-disc pl-5 space-y-0.5">
                        <li v-for="(l, i) in summaryLines" :key="i" class="text-sm text-gray-800">{{ l }}</li>
                    </ul>
                </div>
                <div v-if="log.blockers"><p class="text-xs font-medium text-gray-500 mb-0.5">Blockers</p><p class="text-sm text-gray-800 whitespace-pre-wrap">{{ log.blockers }}</p></div>
                <div v-if="log.plan_tomorrow"><p class="text-xs font-medium text-gray-500 mb-0.5">Plan for tomorrow</p><p class="text-sm text-gray-800 whitespace-pre-wrap">{{ log.plan_tomorrow }}</p></div>
                <p v-if="!log.summary && !log.blockers && !log.plan_tomorrow" class="text-sm text-gray-400">No summary written.</p>
            </div>

            <!-- Jobs -->
            <div v-if="log.jobs.length" class="bg-white rounded-2xl border border-gray-200 p-4">
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">Jobs worked on</p>
                <div class="flex flex-wrap gap-1.5">
                    <span v-for="j in log.jobs" :key="j.id" class="inline-flex items-center gap-1 text-xs bg-blue-50 text-blue-700 border border-blue-200 rounded-full px-2.5 py-1">
                        <BriefcaseIcon class="w-3 h-3" /> {{ j.title }}
                    </span>
                </div>
            </div>

            <!-- Team -->
            <div v-if="log.team && log.team.length" class="bg-white rounded-2xl border border-gray-200 p-4">
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">Team that day</p>
                <div class="flex flex-wrap gap-1.5">
                    <span v-for="m in log.team" :key="m.id" class="inline-flex items-center gap-1 text-xs bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-full pl-1 pr-2.5 py-0.5">
                        <img v-if="m.avatar_url" :src="m.avatar_url" class="w-4 h-4 rounded-full object-cover" />
                        <UserCircleIcon v-else class="w-4 h-4" />
                        @{{ m.name }}
                    </span>
                </div>
            </div>

            <!-- Photos -->
            <div v-if="log.photos.length" class="bg-white rounded-2xl border border-gray-200 p-4">
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">Photos</p>
                <div class="flex flex-wrap gap-2">
                    <a v-for="(p, i) in log.photos" :key="i" :href="p.url" target="_blank"><img :src="p.url" class="w-24 h-24 rounded-lg object-cover border border-gray-200" /></a>
                </div>
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
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import dayjs from 'dayjs';
import relativeTime from 'dayjs/plugin/relativeTime';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ArrowLeftIcon, BriefcaseIcon, CheckBadgeIcon, UserCircleIcon } from '@heroicons/vue/24/outline';
dayjs.extend(relativeTime);

const props = defineProps({ log: { type: Object, required: true } });

const summaryLines = computed(() => (props.log.summary ?? '').split('\n').map(s => s.trim()).filter(Boolean));

const comment = ref('');
const saving = ref(false);
function acknowledge() {
    saving.value = true;
    router.post(route('activity-logs.acknowledge', props.log.id), { comment: comment.value }, {
        preserveScroll: true,
        onFinish: () => { saving.value = false; },
    });
}

function prettyDate(d) { return new Date(d + 'T00:00:00').toLocaleDateString('en-GB', { day: '2-digit', month: '2-digit', year: 'numeric' }); }
function fromNow(iso) { return iso ? dayjs(iso).fromNow() : ''; }
</script>
