<template>
    <AppLayout :title="article.title">
        <div class="h-[calc(100vh-4rem)] flex overflow-hidden">

            <!-- Left: article list sidebar -->
            <aside class="w-56 flex-shrink-0 bg-white border-r border-gray-200 flex flex-col overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-100">
                    <Link :href="route('kb.index')" class="flex items-center gap-1.5 text-xs text-gray-500 hover:text-gray-800 transition-colors mb-2">
                        <ArrowLeftIcon class="w-3.5 h-3.5" />
                        All articles
                    </Link>
                    <p class="text-xs font-semibold text-gray-700 uppercase tracking-wide truncate">{{ category.name }}</p>
                </div>
                <nav class="flex-1 overflow-y-auto py-1">
                    <Link
                        v-for="sibling in siblings"
                        :key="sibling.id"
                        :href="route('kb.show', [category.slug, sibling.slug])"
                        :class="[
                            'block px-4 py-2 text-sm transition-colors truncate',
                            sibling.id === article.id
                                ? 'bg-[#EF233C]/8 text-[#EF233C] font-medium border-r-2 border-[#EF233C]'
                                : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900',
                        ]"
                    >
                        {{ sibling.title }}
                    </Link>
                </nav>
            </aside>

            <!-- Center: article content -->
            <main class="flex-1 overflow-y-auto bg-white" ref="articleEl">
                <div class="max-w-3xl mx-auto px-8 py-10">

                    <!-- Breadcrumb -->
                    <nav class="flex items-center gap-1.5 text-xs text-gray-400 mb-6">
                        <Link :href="route('kb.index')" class="hover:text-gray-600 transition-colors">Knowledge Base</Link>
                        <ChevronRightIcon class="w-3 h-3" />
                        <span class="text-gray-500">{{ category.name }}</span>
                        <ChevronRightIcon class="w-3 h-3" />
                        <span class="text-gray-700 font-medium truncate max-w-xs">{{ article.title }}</span>
                    </nav>

                    <!-- Admin actions -->
                    <div v-if="isPrivileged" class="flex items-center gap-2 mb-6 p-3 bg-gray-50 rounded-xl border border-gray-200">
                        <span :class="['text-xs font-medium px-2 py-0.5 rounded', article.is_published ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700']">
                            {{ article.is_published ? 'Published' : 'Draft' }}
                        </span>
                        <span v-if="article.visible_to?.length" class="text-xs font-medium bg-violet-100 text-violet-700 px-2 py-0.5 rounded flex items-center gap-1">
                            <LockClosedIcon class="w-3 h-3" />
                            {{ article.visible_to.map(r => availableRoles[r] ?? r).join(', ') }} only
                        </span>
                        <div class="flex items-center gap-1.5 ml-auto">
                            <button @click="startEditing" class="text-xs px-3 py-1.5 rounded-lg border border-gray-200 hover:bg-white hover:shadow-sm transition-all text-gray-600">
                                Edit
                            </button>
                            <button @click="togglePublish" :disabled="toggling" class="text-xs px-3 py-1.5 rounded-lg border border-gray-200 hover:bg-white hover:shadow-sm transition-all text-gray-600 disabled:opacity-60">
                                {{ toggling ? '…' : (article.is_published ? 'Unpublish' : 'Publish') }}
                            </button>
                            <button @click="deleteArticle" class="text-xs px-3 py-1.5 rounded-lg border border-red-200 text-red-500 hover:bg-red-50 transition-all">
                                Delete
                            </button>
                        </div>
                    </div>

                    <!-- View mode -->
                    <template v-if="!editing">
                        <span class="inline-flex items-center text-xs font-semibold text-[#EF233C] bg-[#EF233C]/8 px-2.5 py-1 rounded-full mb-3">{{ category.name }}</span>
                        <h1 class="text-3xl font-bold text-gray-900 mb-3 leading-tight tracking-tight">{{ article.title }}</h1>
                        <div class="flex items-center gap-2.5 text-xs text-gray-400 mb-8 pb-6 border-b border-gray-100 flex-wrap">
                            <span v-if="article.author" class="font-medium text-gray-500">{{ article.author }}</span>
                            <span v-if="article.author" class="text-gray-300">·</span>
                            <span>Updated {{ formatDate(article.updated_at) }}</span>
                            <span class="text-gray-300">·</span>
                            <span>{{ readingTime }} min read</span>
                            <div class="ml-auto flex items-center gap-3">
                                <span v-if="article.visible_to?.length" class="flex items-center gap-1 text-violet-500 font-medium">
                                    <LockClosedIcon class="w-3 h-3" />
                                    {{ article.visible_to.map(r => availableRoles[r] ?? r).join(', ') }} only
                                </span>
                                <button
                                    @click="copyLink"
                                    class="inline-flex items-center gap-1 font-medium transition-colors"
                                    :class="copied ? 'text-emerald-600' : 'text-gray-500 hover:text-[#EF233C]'"
                                    title="Copy a link to this article"
                                >
                                    <component :is="copied ? CheckIcon : LinkIcon" class="w-3.5 h-3.5" />
                                    {{ copied ? 'Link copied!' : 'Copy link' }}
                                </button>
                            </div>
                        </div>

                        <!-- Rendered content -->
                        <div class="kb-content" v-html="article.content" />
                    </template>

                    <!-- Edit mode -->
                    <template v-else>
                        <div class="flex items-center gap-2 mb-5">
                            <PencilSquareIcon class="w-5 h-5 text-[#EF233C]" />
                            <h2 class="text-lg font-bold text-gray-900">Editing article</h2>
                        </div>

                        <!-- Article settings -->
                        <div class="bg-gray-50 border border-gray-200 rounded-2xl p-4 sm:p-5 mb-5 space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Title</label>
                                <input
                                    v-model="editForm.title"
                                    type="text"
                                    required
                                    placeholder="Article title…"
                                    class="w-full px-3.5 py-2.5 text-lg font-semibold bg-white border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#EF233C]/20 focus:border-[#EF233C]/40"
                                />
                            </div>

                            <!-- Role visibility -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Visible to
                                    <span class="text-xs font-normal text-gray-400 ml-1">(leave blank for all staff)</span>
                                </label>
                                <div class="flex flex-wrap gap-2">
                                    <label
                                        v-for="(label, role) in availableRoles"
                                        :key="role"
                                        class="flex items-center gap-1.5 cursor-pointer select-none"
                                    >
                                        <input
                                            type="checkbox"
                                            :value="role"
                                            v-model="editForm.visible_to"
                                            class="w-3.5 h-3.5 rounded border-gray-300 text-[#EF233C] focus:ring-[#EF233C]/20"
                                        />
                                        <span
                                            :class="[
                                                'text-xs font-medium px-2 py-0.5 rounded-full border transition-colors',
                                                editForm.visible_to.includes(role)
                                                    ? 'bg-violet-100 text-violet-700 border-violet-200'
                                                    : 'bg-white text-gray-500 border-gray-200',
                                            ]"
                                        >{{ label }}</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Content</label>
                            <TiptapEditor v-model="editForm.content" />
                        </div>
                        <!-- Sticky action bar — stays in view so you never scroll to save -->
                        <div class="sticky bottom-0 z-10 -mx-8 mt-6 px-8 py-3 bg-white/95 backdrop-blur border-t border-gray-200 flex items-center justify-end gap-2">
                            <span v-if="saving" class="mr-auto text-xs text-gray-400">Saving…</span>
                            <button @click="cancelEditing" class="px-4 py-2 text-sm border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">Cancel</button>
                            <button @click="saveEdits" :disabled="saving" class="px-4 py-2 text-sm font-medium bg-[#2B2D42] text-white rounded-lg hover:bg-[#EF233C] transition-colors disabled:opacity-60">
                                {{ saving ? 'Saving…' : 'Save changes' }}
                            </button>
                        </div>
                    </template>
                </div>
            </main>

            <!-- Right: table of contents -->
            <aside v-if="toc.length > 1" class="w-52 flex-shrink-0 bg-[#EDF2F4] border-l border-gray-200 overflow-y-auto py-6 px-4 hidden xl:block">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">On this page</p>
                <nav class="space-y-1">
                    <a
                        v-for="item in toc"
                        :key="item.id"
                        :href="'#' + item.id"
                        :class="[
                            'block text-xs transition-colors py-0.5 hover:text-[#EF233C]',
                            item.level === 1 ? 'font-medium text-gray-700' : item.level === 2 ? 'pl-3 text-gray-500' : 'pl-6 text-gray-400',
                        ]"
                    >{{ item.text }}</a>
                </nav>
            </aside>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import TiptapEditor from '@/Components/TiptapEditor.vue';
import { ArrowLeftIcon, ChevronRightIcon, LockClosedIcon, LinkIcon, CheckIcon, PencilSquareIcon } from '@heroicons/vue/24/outline';
import dayjs from 'dayjs';
import relativeTime from 'dayjs/plugin/relativeTime';
dayjs.extend(relativeTime);

const props = defineProps({
    category:       { type: Object, required: true },
    article:        { type: Object, required: true },
    siblings:       { type: Array,  default: () => [] },
    isPrivileged:   { type: Boolean, default: false },
    availableRoles: { type: Object,  default: () => ({}) },
});

// Estimated reading time (~200 words/min) from the article body.
const readingTime = computed(() => {
    const text  = (props.article.content || '').replace(/<[^>]+>/g, ' ');
    const words = text.trim().split(/\s+/).filter(Boolean).length;
    return Math.max(1, Math.round(words / 200));
});

// Copy a shareable link to this article
const copied = ref(false);
function copyLink() {
    const url = window.location.origin + route('kb.show', [props.category.slug, props.article.slug]);
    const done = () => { copied.value = true; setTimeout(() => { copied.value = false; }, 2000); };
    if (navigator.clipboard?.writeText) {
        navigator.clipboard.writeText(url).then(done).catch(() => fallbackCopy(url, done));
    } else {
        fallbackCopy(url, done);
    }
}
function fallbackCopy(text, done) {
    const ta = document.createElement('textarea');
    ta.value = text;
    ta.style.position = 'fixed';
    ta.style.opacity = '0';
    document.body.appendChild(ta);
    ta.select();
    try { document.execCommand('copy'); done(); } catch (_) {}
    document.body.removeChild(ta);
}

const editing = ref(false);
const saving  = ref(false);
const toggling = ref(false);

const editForm = ref({ title: props.article.title, content: props.article.content, visible_to: props.article.visible_to ?? [] });

function startEditing() {
    editForm.value = { title: props.article.title, content: props.article.content, visible_to: props.article.visible_to ?? [] };
    editing.value  = true;
}

function cancelEditing() {
    editing.value = false;
}

function saveEdits() {
    saving.value = true;
    router.patch(route('kb.articles.update', [props.category.id, props.article.id]), editForm.value, {
        onFinish: () => { saving.value = false; },
        onSuccess: () => { editing.value = false; },
    });
}

function togglePublish() {
    toggling.value = true;
    router.post(route('kb.articles.toggle', [props.category.id, props.article.id]), {}, {
        onFinish: () => { toggling.value = false; },
    });
}

function deleteArticle() {
    if (! confirm('Delete this article permanently?')) return;
    router.delete(route('kb.articles.destroy', [props.category.id, props.article.id]));
}

function formatDate(iso) {
    return iso ? dayjs(iso).fromNow() : '';
}

// ── Table of contents ─────────────────────────────────────────────────────────

const toc = ref([]);

function buildToc(html) {
    const div = document.createElement('div');
    div.innerHTML = html;
    const headings = div.querySelectorAll('h1, h2, h3');
    const items = [];
    headings.forEach((h, i) => {
        const id   = 'heading-' + i;
        const text = h.textContent.trim();
        const level = parseInt(h.tagName[1]);
        items.push({ id, text, level });
    });
    return items;
}

function applyHeadingIds() {
    if (typeof document === 'undefined') return;
    const container = document.querySelector('.kb-content');
    if (! container) return;
    const headings = container.querySelectorAll('h1, h2, h3');
    headings.forEach((h, i) => {
        h.id = 'heading-' + i;
    });
}

onMounted(() => {
    toc.value = buildToc(props.article.content);
    setTimeout(applyHeadingIds, 50);
});

watch(() => props.article.content, (val) => {
    toc.value = buildToc(val);
    setTimeout(applyHeadingIds, 50);
});
</script>

<style>
/* Article rendered content styling */
.kb-content > * + * { margin-top: 0.85em; }
.kb-content > *:first-child { margin-top: 0; }

.kb-content h1 { font-size: 1.6rem; font-weight: 700; color: #111827; line-height: 1.25; scroll-margin-top: 4rem; margin-top: 1.6em; }
.kb-content h2 { font-size: 1.25rem; font-weight: 600; color: #1f2937; line-height: 1.35; scroll-margin-top: 4rem; margin-top: 1.5em; padding-top: 0.5em; border-top: 1px solid #f3f4f6; }
.kb-content h3 { font-size: 1.05rem; font-weight: 600; color: #374151; line-height: 1.4; scroll-margin-top: 4rem; margin-top: 1.2em; }
.kb-content h1:first-child, .kb-content h2:first-child, .kb-content h3:first-child { margin-top: 0; border-top: none; }

.kb-content p { color: #374151; line-height: 1.75; }

/* Images & video (e.g. pasted screenshots) */
.kb-content img,
.kb-content video {
    display: block;
    max-width: 100%;
    height: auto;
    border-radius: 0.75rem;
    border: 1px solid #e5e7eb;
    margin: 1.25rem auto;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}
.kb-content video { background: #000; }

.kb-content ul { list-style: disc; padding-left: 1.5rem; color: #374151; }
.kb-content ol { list-style: decimal; padding-left: 1.5rem; color: #374151; }
.kb-content li { margin: 0.3em 0; line-height: 1.65; }

.kb-content blockquote {
    border-left: 3px solid #EF233C;
    padding: 0.75rem 1rem;
    background: rgba(239,35,60,0.04);
    border-radius: 0 0.5rem 0.5rem 0;
    color: #6b7280;
    font-style: italic;
}

.kb-content code {
    background: #f3f4f6;
    border-radius: 0.25rem;
    padding: 0.125rem 0.375rem;
    font-family: monospace;
    font-size: 0.875em;
    color: #dc2626;
}

.kb-content pre {
    background: #1e293b;
    color: #e2e8f0;
    border-radius: 0.75rem;
    padding: 1.25rem;
    overflow-x: auto;
}

.kb-content pre code {
    background: none;
    padding: 0;
    color: inherit;
    font-size: 0.875rem;
}

.kb-content a {
    color: #EF233C;
    text-decoration: underline;
    text-decoration-thickness: 1px;
    text-underline-offset: 2px;
}

.kb-content a:hover { color: #c41430; }

.kb-content hr {
    border: none;
    border-top: 2px solid #e5e7eb;
    margin: 2rem 0;
}

.kb-content strong { font-weight: 600; color: #111827; }
.kb-content em { font-style: italic; }
.kb-content s { text-decoration: line-through; }
</style>
