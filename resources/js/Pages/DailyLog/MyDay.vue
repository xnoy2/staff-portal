<template>
    <AppLayout title="My Day">
        <div class="max-w-5xl mx-auto">

            <!-- Header + date nav -->
            <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
                <div>
                    <h1 class="text-lg font-semibold text-gray-800">My Day</h1>
                    <p class="text-xs text-gray-500 mt-0.5">Log what you worked on and submit your end-of-day summary.</p>
                </div>
                <div class="flex items-center gap-1.5">
                    <button @click="go(-1)" class="p-2 rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50"><ChevronLeftIcon class="w-4 h-4" /></button>
                    <div class="px-3 py-1.5 rounded-lg bg-white border border-gray-200 text-sm font-semibold text-gray-700 min-w-40 text-center">
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
                <!-- Left: activities + EOD -->
                <div class="lg:col-span-2 space-y-5">

                    <!-- Add / edit activity -->
                    <div class="bg-white rounded-2xl border border-gray-200 p-4">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-xs font-bold text-gray-500 uppercase tracking-wide">{{ editingId ? 'Edit activity' : 'Add activity' }}</p>
                            <span v-if="editingId" class="text-xs text-gray-400 cursor-pointer hover:text-gray-600" @click="resetForm">Cancel edit</span>
                        </div>
                        <textarea
                            v-model="form.description"
                            rows="2"
                            placeholder="What did you work on?"
                            class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 resize-none focus:outline-none focus:ring-2 focus:ring-[#EF233C]/15 focus:border-[#EF233C]/40"
                            @keydown.ctrl.enter="submitActivity"
                        />
                        <div class="flex flex-wrap items-center gap-2 mt-2">
                            <select v-model="form.category" class="text-xs border-gray-200 rounded-lg py-1.5 focus:ring-[#EF233C] focus:border-[#EF233C]">
                                <option v-for="c in categories" :key="c" :value="c">{{ catLabel(c) }}</option>
                            </select>
                            <select v-model="form.job_id" class="text-xs border-gray-200 rounded-lg py-1.5 focus:ring-[#EF233C] focus:border-[#EF233C] max-w-52">
                                <option :value="null">No job</option>
                                <option v-for="j in jobs" :key="j.id" :value="j.id">{{ j.title }}{{ j.is_today ? ' · today' : ` · ${j.date}` }}</option>
                            </select>
                            <div class="flex items-center gap-1">
                                <input v-model.number="form.duration_minutes" type="number" min="0" max="1440" placeholder="mins" class="w-20 text-xs border-gray-200 rounded-lg py-1.5 focus:ring-[#EF233C] focus:border-[#EF233C]" />
                                <span class="text-xs text-gray-400">min</span>
                            </div>
                            <button @click="$refs.photoInput.click()" class="inline-flex items-center gap-1 text-xs border border-gray-200 text-gray-600 rounded-lg px-2 py-1.5 hover:bg-gray-50">
                                <PhotoIcon class="w-3.5 h-3.5" /> Photo
                            </button>
                            <input ref="photoInput" type="file" accept="image/*" class="hidden" @change="onPhoto" />
                            <span v-if="uploading" class="text-xs text-gray-400">Uploading…</span>
                            <button
                                @click="submitActivity"
                                :disabled="!form.description.trim() || saving"
                                class="ml-auto text-xs font-semibold bg-[#2B2D42] hover:bg-[#EF233C] text-white px-4 py-2 rounded-lg transition-colors disabled:opacity-50"
                            >{{ editingId ? 'Save' : 'Add' }}</button>
                        </div>
                        <!-- staged photos -->
                        <div v-if="form.photos.length" class="flex flex-wrap gap-2 mt-2">
                            <div v-for="(p, i) in form.photos" :key="i" class="relative">
                                <img :src="p.url" class="w-14 h-14 rounded-lg object-cover border border-gray-200" />
                                <button @click="form.photos.splice(i, 1)" class="absolute -top-1.5 -right-1.5 w-5 h-5 bg-white border border-gray-200 rounded-full text-gray-500 hover:text-red-500 flex items-center justify-center shadow-sm"><XMarkIcon class="w-3 h-3" /></button>
                            </div>
                        </div>
                    </div>

                    <!-- Activity list -->
                    <div class="bg-white rounded-2xl border border-gray-200 divide-y divide-gray-100">
                        <div class="flex items-center justify-between px-4 py-2.5">
                            <p class="text-xs font-bold text-gray-500 uppercase tracking-wide">Activities</p>
                            <p class="text-xs text-gray-400">{{ activities.length }} · {{ formatMins(totalMinutes) }}</p>
                        </div>
                        <div v-if="activities.length === 0" class="px-4 py-10 text-center text-sm text-gray-400">No activities logged yet for this day.</div>
                        <div v-for="a in activities" :key="a.id" class="px-4 py-3 flex items-start gap-3 group">
                            <span :class="['mt-0.5 text-[10px] font-bold uppercase px-1.5 py-0.5 rounded flex-shrink-0', catClass(a.category)]">{{ catLabel(a.category) }}</span>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-gray-800 whitespace-pre-wrap break-words">{{ a.description }}</p>
                                <div class="flex flex-wrap items-center gap-2 mt-1 text-xs text-gray-400">
                                    <span v-if="a.duration_minutes" class="inline-flex items-center gap-1"><ClockIcon class="w-3 h-3" />{{ formatMins(a.duration_minutes) }}</span>
                                    <span v-if="a.job" class="inline-flex items-center gap-1 text-blue-500"><BriefcaseIcon class="w-3 h-3" />{{ a.job.title }}</span>
                                </div>
                                <div v-if="a.photos.length" class="flex flex-wrap gap-1.5 mt-2">
                                    <a v-for="(p, i) in a.photos" :key="i" :href="p.url" target="_blank"><img :src="p.url" class="w-14 h-14 rounded-lg object-cover border border-gray-200" /></a>
                                </div>
                            </div>
                            <div class="flex items-center gap-0.5 flex-shrink-0 opacity-100 sm:opacity-0 sm:group-hover:opacity-100 transition-opacity">
                                <button @click="editActivity(a)" title="Edit" class="p-1 rounded text-gray-300 hover:text-gray-600 hover:bg-gray-100"><PencilIcon class="w-4 h-4" /></button>
                                <button @click="removeActivity(a)" title="Delete" class="p-1 rounded text-gray-300 hover:text-red-500 hover:bg-red-50"><TrashIcon class="w-4 h-4" /></button>
                            </div>
                        </div>
                    </div>

                    <!-- EOD summary -->
                    <div class="bg-white rounded-2xl border border-gray-200 p-4">
                        <div class="flex items-center justify-between mb-3">
                            <p class="text-xs font-bold text-gray-500 uppercase tracking-wide">End of day</p>
                            <span v-if="log?.status === 'submitted'" class="text-xs font-semibold text-emerald-600 inline-flex items-center gap-1"><CheckCircleIcon class="w-4 h-4" /> Submitted</span>
                        </div>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">What did you accomplish?</label>
                                <textarea v-model="eod.summary" rows="3" class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 resize-none focus:outline-none focus:ring-2 focus:ring-[#EF233C]/15 focus:border-[#EF233C]/40" placeholder="Highlights of the day…" />
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Blockers / issues</label>
                                    <textarea v-model="eod.blockers" rows="2" class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 resize-none focus:outline-none focus:ring-2 focus:ring-[#EF233C]/15 focus:border-[#EF233C]/40" placeholder="Anything that held you up…" />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Plan for tomorrow</label>
                                    <textarea v-model="eod.plan_tomorrow" rows="2" class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 resize-none focus:outline-none focus:ring-2 focus:ring-[#EF233C]/15 focus:border-[#EF233C]/40" placeholder="What's next…" />
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 mt-4">
                            <button @click="saveEod(false)" :disabled="savingEod" class="text-xs font-medium border border-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-50 disabled:opacity-50">Save draft</button>
                            <button v-if="log?.status !== 'submitted'" @click="saveEod(true)" :disabled="savingEod" class="text-xs font-semibold bg-[#EF233C] hover:bg-[#d91e34] text-white px-4 py-2 rounded-lg disabled:opacity-50">Submit EOD</button>
                            <button v-else @click="reopen" class="text-xs font-medium text-gray-500 px-3 py-2 rounded-lg hover:bg-gray-50">Reopen</button>
                            <span v-if="savingEod" class="text-xs text-gray-400">Saving…</span>
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
                                {{ h.activities }} · {{ formatMins(h.minutes) }}
                                <span v-if="h.status === 'submitted'" class="w-2 h-2 rounded-full bg-emerald-400" title="Submitted" />
                                <span v-else class="w-2 h-2 rounded-full bg-gray-300" title="Draft" />
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <ConfirmModal
            :open="!!deleteTarget"
            title="Delete activity?"
            message="This activity will be removed from your log."
            confirmLabel="Delete"
            danger
            @confirm="performDelete"
            @cancel="deleteTarget = null"
        />
    </AppLayout>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import {
    ChevronLeftIcon, ChevronRightIcon, PhotoIcon, XMarkIcon, PencilIcon, TrashIcon,
    ClockIcon, BriefcaseIcon, CheckCircleIcon, CheckBadgeIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    date:       { type: String, required: true },
    today:      { type: String, required: true },
    log:        { type: Object, default: null },
    jobs:       { type: Array,  default: () => [] },
    categories: { type: Array,  default: () => [] },
    history:    { type: Array,  default: () => [] },
});

const opts = { preserveScroll: true };
const isToday = computed(() => props.date === props.today);
const activities = computed(() => props.log?.activities ?? []);
const totalMinutes = computed(() => activities.value.reduce((s, a) => s + (a.duration_minutes || 0), 0));

// ── Date nav ──
const prettyDate = computed(() => new Date(props.date + 'T00:00:00').toLocaleDateString('en-GB', { day: '2-digit', month: '2-digit', year: 'numeric' }));
function shortDate(d) { return new Date(d + 'T00:00:00').toLocaleDateString('en-GB', { day: '2-digit', month: '2-digit', year: 'numeric' }); }
function goDate(d) { router.get(route('my-day'), { date: d }, { preserveScroll: true }); }
function toDateStr(d) { return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`; }
function go(offset) { const d = new Date(props.date + 'T00:00:00'); d.setDate(d.getDate() + offset); goDate(toDateStr(d)); }
function goToday() { goDate(props.today); }

// ── Categories ──
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
function formatMins(m) { if (!m) return '0m'; const h = Math.floor(m / 60); const min = m % 60; return (h ? `${h}h ` : '') + (min ? `${min}m` : (h ? '' : '0m')); }

// ── Activity form (add + edit) ──
const emptyForm = () => ({ description: '', category: 'other', job_id: null, duration_minutes: null, photos: [] });
const form = reactive(emptyForm());
const editingId = ref(null);
const saving = ref(false);
const uploading = ref(false);

function resetForm() { Object.assign(form, emptyForm()); editingId.value = null; }

function editActivity(a) {
    editingId.value = a.id;
    Object.assign(form, {
        description: a.description,
        category: a.category,
        job_id: a.job?.id ?? null,
        duration_minutes: a.duration_minutes,
        photos: [...a.photos],
    });
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function submitActivity() {
    if (!form.description.trim() || saving.value) return;
    saving.value = true;
    const payload = {
        description: form.description,
        category: form.category,
        job_id: form.job_id,
        duration_minutes: form.duration_minutes || null,
        photos: form.photos.map(p => ({ path: p.path, name: p.name, size: p.size })),
    };
    const done = { ...opts, onSuccess: resetForm, onFinish: () => { saving.value = false; } };
    if (editingId.value) {
        router.patch(route('daily-log.activities.update', editingId.value), payload, done);
    } else {
        router.post(route('daily-log.activities.store'), { date: props.date, ...payload }, done);
    }
}

const deleteTarget = ref(null);
function removeActivity(a) { deleteTarget.value = a; }
function performDelete() {
    if (!deleteTarget.value) return;
    router.delete(route('daily-log.activities.destroy', deleteTarget.value.id), opts);
    deleteTarget.value = null;
}

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

// ── EOD ──
const eod = reactive({
    summary: props.log?.summary ?? '',
    blockers: props.log?.blockers ?? '',
    plan_tomorrow: props.log?.plan_tomorrow ?? '',
});
const savingEod = ref(false);
function saveEod(submit) {
    savingEod.value = true;
    router.post(route('daily-log.save'), { date: props.date, ...eod, submit }, { ...opts, onFinish: () => { savingEod.value = false; } });
}
function reopen() {
    if (props.log) router.post(route('daily-log.reopen', props.log.id), {}, opts);
}

// Inertia reuses this component across date changes without re-running setup,
// so refresh the EOD fields (and clear any in-progress activity edit) when the day changes.
watch(() => props.date, () => {
    eod.summary = props.log?.summary ?? '';
    eod.blockers = props.log?.blockers ?? '';
    eod.plan_tomorrow = props.log?.plan_tomorrow ?? '';
    resetForm();
});
</script>
