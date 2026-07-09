<template>
    <AppLayout title="My Day">
        <div class="max-w-5xl mx-auto">

            <!-- Header + date nav -->
            <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
                <div>
                    <h1 class="text-lg font-semibold text-gray-800">My Day</h1>
                    <p class="text-xs text-gray-500 mt-0.5">Write your end-of-day summary, tag the jobs you worked on, mention your team, and add photos.</p>
                </div>
                <div class="flex items-center gap-1.5">
                    <button @click="go(-1)" class="p-2 rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50"><ChevronLeftIcon class="w-4 h-4" /></button>
                    <div class="px-3 py-1.5 rounded-lg bg-white border border-gray-200 text-sm font-semibold text-gray-700 min-w-36 text-center">
                        {{ prettyDate }}
                        <span v-if="isToday" class="ml-1 text-[10px] font-bold bg-[#EF233C] text-white px-1.5 py-0.5 rounded-full uppercase">Today</span>
                    </div>
                    <button @click="go(1)" :disabled="isToday" class="p-2 rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 disabled:opacity-40"><ChevronRightIcon class="w-4 h-4" /></button>
                    <button v-if="!isToday" @click="goToday" class="text-xs px-2.5 py-2 rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50">Today</button>
                </div>
            </div>

            <!-- Acknowledged banner -->
            <div v-if="log?.acknowledged" class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 flex items-start gap-2">
                <CheckBadgeIcon class="w-5 h-5 text-emerald-500 flex-shrink-0" />
                <div class="text-sm text-emerald-800">
                    Reviewed by <strong>{{ log.acknowledged_by }}</strong>.
                    <span v-if="log.manager_comment" class="italic">“{{ log.manager_comment }}”</span>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                <!-- Left: the day's log -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-2xl border border-gray-200 p-5 space-y-5">

                        <div class="flex items-center justify-between">
                            <p class="text-xs font-bold text-gray-500 uppercase tracking-wide">End of day</p>
                            <span v-if="log?.status === 'submitted'" class="text-xs font-semibold text-emerald-600 inline-flex items-center gap-1"><CheckCircleIcon class="w-4 h-4" /> Submitted</span>
                        </div>

                        <!-- Accomplishments (list) -->
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">What did you accomplish?</label>
                            <ul v-if="form.accomplishments.length" class="space-y-1.5 mb-2">
                                <li v-for="(item, i) in form.accomplishments" :key="i" class="flex items-start gap-2 group">
                                    <span class="mt-2 w-1.5 h-1.5 rounded-full bg-[#EF233C] flex-shrink-0"></span>
                                    <input
                                        v-model="form.accomplishments[i]"
                                        class="flex-1 text-sm border border-transparent hover:border-gray-200 focus:border-gray-200 rounded-lg px-2 py-1 focus:outline-none focus:ring-2 focus:ring-[#EF233C]/15"
                                        @keydown.enter.prevent="focusAdd"
                                    />
                                    <button @click="form.accomplishments.splice(i, 1)" class="mt-1 p-1 rounded text-gray-300 hover:text-red-500 flex-shrink-0"><XMarkIcon class="w-3.5 h-3.5" /></button>
                                </li>
                            </ul>
                            <div class="flex items-center gap-2">
                                <input
                                    ref="addInput"
                                    v-model="newItem"
                                    placeholder="Add an accomplishment…"
                                    class="flex-1 text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#EF233C]/15 focus:border-[#EF233C]/40"
                                    @keydown.enter.prevent="addItem"
                                />
                                <button @click="addItem" :disabled="!newItem.trim()" class="text-xs font-semibold bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-2 rounded-lg disabled:opacity-40">Add</button>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Blockers / issues</label>
                                <textarea v-model="form.blockers" rows="2" class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 resize-none focus:outline-none focus:ring-2 focus:ring-[#EF233C]/15 focus:border-[#EF233C]/40" placeholder="Anything that held you up…" />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Plan for tomorrow</label>
                                <textarea v-model="form.plan_tomorrow" rows="2" class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 resize-none focus:outline-none focus:ring-2 focus:ring-[#EF233C]/15 focus:border-[#EF233C]/40" placeholder="What's next…" />
                            </div>
                        </div>

                        <!-- Jobs worked on -->
                        <div class="pt-1">
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Jobs worked on</label>
                            <div v-if="form.jobs.length" class="flex flex-wrap gap-1.5 mb-2">
                                <span v-for="id in form.jobs" :key="id" class="inline-flex items-center gap-1 text-xs bg-blue-50 text-blue-700 border border-blue-200 rounded-full pl-2.5 pr-1 py-1">
                                    <BriefcaseIcon class="w-3 h-3" /> {{ jobTitle(id) }}
                                    <button @click="toggleJob(id)" class="w-4 h-4 rounded-full hover:bg-blue-100 flex items-center justify-center"><XMarkIcon class="w-3 h-3" /></button>
                                </span>
                            </div>
                            <select v-if="addableJobs.length" @change="onAddJob($event)" class="text-xs border-gray-200 rounded-lg py-1.5 focus:ring-[#EF233C] focus:border-[#EF233C] max-w-full sm:max-w-md">
                                <option value="">+ Tag a job…</option>
                                <option v-for="j in addableJobs" :key="j.id" :value="j.id">{{ j.title }}{{ j.is_today ? ' · today' : ` · ${j.date}` }}</option>
                            </select>
                            <p v-else-if="!form.jobs.length" class="text-xs text-gray-400">No jobs assigned around this date.</p>
                        </div>

                        <!-- Team today -->
                        <div class="pt-1">
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Who was on your team today?</label>
                            <div v-if="form.team.length" class="flex flex-wrap gap-1.5 mb-2">
                                <span v-for="id in form.team" :key="id" class="inline-flex items-center gap-1 text-xs bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-full pl-1 pr-1 py-0.5">
                                    <img v-if="teammateAvatar(id)" :src="teammateAvatar(id)" class="w-4 h-4 rounded-full object-cover" />
                                    <UserCircleIcon v-else class="w-4 h-4" />
                                    @{{ teammateName(id) }}
                                    <button @click="toggleMate(id)" class="w-4 h-4 rounded-full hover:bg-emerald-100 flex items-center justify-center"><XMarkIcon class="w-3 h-3" /></button>
                                </span>
                            </div>
                            <select v-if="addableMates.length" @change="onAddMate($event)" class="text-xs border-gray-200 rounded-lg py-1.5 focus:ring-[#EF233C] focus:border-[#EF233C] max-w-full sm:max-w-md">
                                <option value="">+ Mention a teammate…</option>
                                <option v-for="m in addableMates" :key="m.id" :value="m.id">{{ m.name }}</option>
                            </select>
                            <p v-else-if="!form.team.length" class="text-xs text-gray-400">No other staff to mention.</p>
                        </div>

                        <!-- Photos -->
                        <div class="pt-1">
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="block text-xs font-medium text-gray-600">Photos</label>
                                <button @click="$refs.photoInput.click()" class="inline-flex items-center gap-1 text-xs border border-gray-200 text-gray-600 rounded-lg px-2 py-1 hover:bg-gray-50">
                                    <PhotoIcon class="w-3.5 h-3.5" /> Add photo
                                    <span v-if="uploading" class="text-gray-400">· uploading…</span>
                                </button>
                                <input ref="photoInput" type="file" accept="image/*" class="hidden" @change="onPhoto" />
                            </div>
                            <div v-if="form.photos.length" class="flex flex-wrap gap-2">
                                <div v-for="(p, i) in form.photos" :key="i" class="relative">
                                    <a :href="p.url" target="_blank"><img :src="p.url" class="w-16 h-16 rounded-lg object-cover border border-gray-200" /></a>
                                    <button @click="form.photos.splice(i, 1)" class="absolute -top-1.5 -right-1.5 w-5 h-5 bg-white border border-gray-200 rounded-full text-gray-500 hover:text-red-500 flex items-center justify-center shadow-sm"><XMarkIcon class="w-3 h-3" /></button>
                                </div>
                            </div>
                            <p v-else class="text-xs text-gray-400">No photos added.</p>
                        </div>

                        <!-- Save actions -->
                        <div class="flex items-center gap-2 pt-2 border-t border-gray-100">
                            <button @click="save(false)" :disabled="saving" class="text-xs font-medium border border-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-50 disabled:opacity-50">Save draft</button>
                            <button v-if="log?.status !== 'submitted'" @click="save(true)" :disabled="saving" class="text-xs font-semibold bg-[#EF233C] hover:bg-[#d91e34] text-white px-4 py-2 rounded-lg disabled:opacity-50">Submit EOD</button>
                            <button v-else @click="reopen" class="text-xs font-medium text-gray-500 px-3 py-2 rounded-lg hover:bg-gray-50">Reopen</button>
                            <span v-if="saving" class="text-xs text-gray-400">Saving…</span>
                        </div>
                    </div>
                </div>

                <!-- Right: history -->
                <div>
                    <div class="bg-white rounded-2xl border border-gray-200 p-4">
                        <p class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-3">Recent days</p>
                        <div v-if="history.length === 0" class="text-xs text-gray-400">No history yet.</div>
                        <button
                            v-for="h in history"
                            :key="h.log_date"
                            @click="goDate(h.log_date)"
                            :class="['w-full flex items-center justify-between text-left px-2 py-2 rounded-lg text-sm transition-colors', h.log_date === date ? 'bg-[#EF233C]/8' : 'hover:bg-gray-50']"
                        >
                            <span class="text-gray-700">{{ shortDate(h.log_date) }}</span>
                            <span class="flex items-center gap-2 text-xs text-gray-400">
                                <span v-if="h.photos" class="inline-flex items-center gap-0.5"><PhotoIcon class="w-3 h-3" />{{ h.photos }}</span>
                                <span v-if="h.status === 'submitted'" class="w-2 h-2 rounded-full bg-emerald-400" title="Submitted" />
                                <span v-else class="w-2 h-2 rounded-full bg-gray-300" title="Draft" />
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    ChevronLeftIcon, ChevronRightIcon, PhotoIcon, XMarkIcon,
    BriefcaseIcon, CheckCircleIcon, CheckBadgeIcon, UserCircleIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    date:      { type: String, required: true },
    today:     { type: String, required: true },
    log:       { type: Object, default: null },
    jobs:      { type: Array,  default: () => [] },
    teammates: { type: Array,  default: () => [] },
    history:   { type: Array,  default: () => [] },
});

const opts = { preserveScroll: true };
const isToday = computed(() => props.date === props.today);

// ── Form (whole day saved at once) ──
const splitLines = (t) => (t ?? '').split('\n').map(s => s.trim()).filter(Boolean);
const buildForm = () => ({
    accomplishments: splitLines(props.log?.summary),
    blockers:        props.log?.blockers ?? '',
    plan_tomorrow:   props.log?.plan_tomorrow ?? '',
    photos:          [...(props.log?.photos ?? [])],
    jobs:            (props.log?.jobs ?? []).map(j => j.id),
    team:            (props.log?.team ?? []).map(m => m.id),
});
const form = reactive(buildForm());

// Inertia reuses this component across dates without re-running setup.
watch(() => props.date, () => { Object.assign(form, buildForm()); newItem.value = ''; });

// Accomplishments list
const newItem = ref('');
const addInput = ref(null);
function addItem() {
    const v = newItem.value.trim();
    if (!v) return;
    form.accomplishments.push(v);
    newItem.value = '';
}
function focusAdd() { addInput.value?.focus(); }

// ── Jobs ──
const jobTitleMap = computed(() => {
    const m = {};
    (props.jobs || []).forEach(j => { m[j.id] = j.title; });
    (props.log?.jobs || []).forEach(j => { m[j.id] = j.title; });
    return m;
});
function jobTitle(id) { return jobTitleMap.value[id] ?? 'Job'; }
const addableJobs = computed(() => (props.jobs || []).filter(j => !form.jobs.includes(j.id)));
function onAddJob(e) { const id = e.target.value; if (id && !form.jobs.includes(id)) form.jobs.push(id); e.target.value = ''; }
function toggleJob(id) { const i = form.jobs.indexOf(id); if (i >= 0) form.jobs.splice(i, 1); }

// ── Team ──
const teammateMap = computed(() => {
    const m = {};
    (props.teammates || []).forEach(u => { m[u.id] = u; });
    (props.log?.team || []).forEach(u => { m[u.id] = { ...(m[u.id] || {}), ...u }; });
    return m;
});
function teammateName(id) { return teammateMap.value[id]?.name ?? 'Someone'; }
function teammateAvatar(id) { return teammateMap.value[id]?.avatar_url ?? null; }
const addableMates = computed(() => (props.teammates || []).filter(u => !form.team.includes(u.id)));
function onAddMate(e) { const id = e.target.value; if (id && !form.team.includes(id)) form.team.push(id); e.target.value = ''; }
function toggleMate(id) { const i = form.team.indexOf(id); if (i >= 0) form.team.splice(i, 1); }

// ── Photos ──
const uploading = ref(false);
async function onPhoto(e) {
    const file = e.target.files[0];
    e.target.value = '';
    if (!file) return;
    uploading.value = true;
    try {
        const fd = new FormData();
        fd.append('file', file);
        const { data } = await axios.post(route('daily-log.photo'), fd, { headers: { 'Content-Type': 'multipart/form-data' } });
        form.photos.push(data);
    } catch (err) {
        alert('Photo upload failed. Please try again.');
    } finally {
        uploading.value = false;
    }
}

// ── Save ──
const saving = ref(false);
function save(submit) {
    saving.value = true;
    router.post(route('daily-log.save'), {
        date:          props.date,
        summary:       form.accomplishments.map(s => s.trim()).filter(Boolean).join('\n'),
        blockers:      form.blockers,
        plan_tomorrow: form.plan_tomorrow,
        jobs:          form.jobs,
        team:          form.team,
        photos:        form.photos.map(p => ({ path: p.path, name: p.name, size: p.size })),
        submit,
    }, { ...opts, onFinish: () => { saving.value = false; } });
}
function reopen() {
    if (props.log) router.post(route('daily-log.reopen', props.log.id), {}, opts);
}

// ── Date nav ──
const prettyDate = computed(() => new Date(props.date + 'T00:00:00').toLocaleDateString('en-GB', { day: '2-digit', month: '2-digit', year: 'numeric' }));
function shortDate(d) { return new Date(d + 'T00:00:00').toLocaleDateString('en-GB', { day: '2-digit', month: '2-digit', year: 'numeric' }); }
function goDate(d) { router.get(route('my-day'), { date: d }, { preserveScroll: true }); }
function toDateStr(d) { return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`; }
function go(offset) { const d = new Date(props.date + 'T00:00:00'); d.setDate(d.getDate() + offset); goDate(toDateStr(d)); }
function goToday() { goDate(props.today); }
</script>
