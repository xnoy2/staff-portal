<template>
    <AppLayout title="Knowledge Base">
        <div class="h-[calc(100vh-4rem)] flex overflow-hidden">

            <!-- Left sidebar: categories -->
            <aside class="w-64 flex-shrink-0 bg-white border-r border-gray-200 flex flex-col overflow-hidden">
                <div class="p-4 border-b border-gray-100 flex items-center justify-between gap-2">
                    <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Categories</h2>
                    <button
                        v-if="isPrivileged"
                        @click="showCategoryModal = true"
                        class="w-6 h-6 flex items-center justify-center rounded-full bg-[#2B2D42] text-white hover:bg-[#EF233C] transition-colors"
                        title="Add category"
                    >
                        <PlusIcon class="w-3.5 h-3.5" />
                    </button>
                </div>

                <!-- Search -->
                <div class="px-3 py-2 border-b border-gray-100">
                    <div class="relative">
                        <MagnifyingGlassIcon class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" />
                        <input
                            v-model="searchQuery"
                            @keydown.enter="doSearch"
                            type="search"
                            placeholder="Search articles…"
                            class="w-full pl-7 pr-3 py-1.5 text-xs rounded-lg border border-gray-200 bg-gray-50 text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#EF233C]/20 focus:border-[#EF233C]/40"
                        />
                    </div>
                </div>

                <!-- Category list -->
                <nav class="flex-1 overflow-y-auto py-2">
                    <button
                        @click="activeCategory = null"
                        :class="[
                            'w-full flex items-center gap-2.5 px-3 py-2 text-sm transition-colors',
                            activeCategory === null
                                ? 'bg-[#EF233C]/8 text-[#EF233C] font-medium border-r-2 border-[#EF233C]'
                                : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900',
                        ]"
                    >
                        <BookOpenIcon class="w-4 h-4 flex-shrink-0" />
                        <span class="flex-1 text-left truncate">All articles</span>
                        <span class="text-xs text-gray-400">{{ totalArticleCount }}</span>
                    </button>

                    <div v-for="cat in categories" :key="cat.id" class="mt-0.5">
                        <button
                            @click="activeCategory = cat.id"
                            :class="[
                                'w-full flex items-center gap-2.5 px-3 py-2 text-sm transition-colors group',
                                activeCategory === cat.id
                                    ? 'bg-[#EF233C]/8 text-[#EF233C] font-medium border-r-2 border-[#EF233C]'
                                    : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900',
                            ]"
                        >
                            <span class="w-4 h-4 flex-shrink-0 text-center text-base leading-none">{{ cat.icon || '📁' }}</span>
                            <span class="flex-1 text-left truncate">{{ cat.name }}</span>
                            <span class="text-xs text-gray-400">{{ cat.articles.length }}</span>
                        </button>
                    </div>

                    <p v-if="categories.length === 0" class="px-4 py-6 text-xs text-gray-400 text-center">
                        No categories yet.
                    </p>
                </nav>
            </aside>

            <!-- Main content: article list -->
            <main class="flex-1 flex flex-col overflow-hidden bg-[#EDF2F4]">
                <!-- Header -->
                <div class="bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between gap-4 flex-shrink-0">
                    <div>
                        <h1 class="text-lg font-semibold text-gray-900">
                            {{ activeCategoryObj ? activeCategoryObj.name : 'All Articles' }}
                        </h1>
                        <p v-if="activeCategoryObj?.description" class="text-sm text-gray-500 mt-0.5">{{ activeCategoryObj.description }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <!-- Admin: edit category -->
                        <button
                            v-if="isPrivileged && activeCategoryObj"
                            @click="openEditCategory(activeCategoryObj)"
                            class="text-xs text-gray-500 hover:text-gray-700 px-2 py-1 rounded border border-gray-200 hover:border-gray-300 transition-colors"
                        >
                            Edit category
                        </button>
                        <!-- Admin: new article -->
                        <button
                            v-if="isPrivileged && activeCategoryObj"
                            @click="openNewArticle"
                            class="flex items-center gap-1.5 text-xs font-medium bg-[#2B2D42] text-white px-3 py-1.5 rounded-lg hover:bg-[#EF233C] transition-colors"
                        >
                            <PlusIcon class="w-3.5 h-3.5" />
                            New article
                        </button>
                    </div>
                </div>

                <!-- Article grid -->
                <div class="flex-1 overflow-y-auto p-6">
                    <div v-if="visibleArticles.length > 0" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
                        <Link
                            v-for="article in visibleArticles"
                            :key="article.id"
                            :href="route('kb.show', [categorySlugFor(article.categoryId), article.slug])"
                            class="block bg-white rounded-xl border border-gray-200 p-5 hover:border-[#EF233C]/40 hover:shadow-md transition-all group"
                        >
                            <div class="flex items-start justify-between gap-2 mb-2">
                                <h3 class="text-sm font-semibold text-gray-900 group-hover:text-[#EF233C] transition-colors line-clamp-2">
                                    {{ article.title }}
                                </h3>
                                <span
                                    v-if="isPrivileged && !article.is_published"
                                    class="flex-shrink-0 text-[10px] font-medium bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded"
                                >
                                    Draft
                                </span>
                            </div>
                            <p class="text-xs text-gray-500 line-clamp-3 leading-relaxed">{{ article.excerpt }}</p>
                            <div class="mt-3 flex items-center gap-2 text-[11px] text-gray-400">
                                <span class="text-base leading-none">{{ categoryIconFor(article.categoryId) }}</span>
                                <span>{{ categoryNameFor(article.categoryId) }}</span>
                                <span class="ml-auto">{{ formatDate(article.updated_at) }}</span>
                            </div>
                        </Link>
                    </div>

                    <div v-else class="flex flex-col items-center justify-center h-64 text-gray-400">
                        <BookOpenIcon class="w-12 h-12 mb-3 opacity-30" />
                        <p class="text-sm">{{ search ? 'No articles match your search.' : 'No articles yet.' }}</p>
                        <button
                            v-if="isPrivileged && activeCategoryObj && !search"
                            @click="openNewArticle"
                            class="mt-3 text-xs text-[#EF233C] hover:underline"
                        >
                            Create the first article
                        </button>
                    </div>
                </div>
            </main>
        </div>

        <!-- Category modal -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="showCategoryModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="closeCategoryModal" />
                    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">
                        <h3 class="text-base font-semibold text-gray-900 mb-4">
                            {{ editingCategory ? 'Edit Category' : 'New Category' }}
                        </h3>
                        <form @submit.prevent="submitCategory" class="space-y-4">
                            <!-- Icon + Name side by side -->
                            <div class="flex items-start gap-4">
                                <div class="flex-shrink-0">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Icon</label>
                                    <EmojiPicker v-model="catForm.icon" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                                    <input v-model="catForm.name" type="text" required maxlength="100" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#EF233C]/20 focus:border-[#EF233C]/40" />
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                                <textarea v-model="catForm.description" rows="2" maxlength="500" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#EF233C]/20 resize-none" />
                            </div>
                            <div class="flex items-center justify-between gap-3 pt-2">
                                <button
                                    v-if="editingCategory"
                                    type="button"
                                    @click="deleteCategory"
                                    class="text-xs text-red-500 hover:text-red-700 transition-colors"
                                >Delete category</button>
                                <div class="flex gap-2 ml-auto">
                                    <button type="button" @click="closeCategoryModal" class="px-4 py-2 text-sm border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">Cancel</button>
                                    <button type="submit" :disabled="catSubmitting" class="px-4 py-2 text-sm font-medium bg-[#2B2D42] text-white rounded-lg hover:bg-[#EF233C] transition-colors disabled:opacity-60">
                                        {{ catSubmitting ? 'Saving…' : 'Save' }}
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- New Article modal -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="showArticleModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showArticleModal = false" />
                    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col">
                        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 flex-shrink-0">
                            <h3 class="text-base font-semibold text-gray-900">New Article</h3>
                            <button @click="showArticleModal = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                                <XMarkIcon class="w-5 h-5" />
                            </button>
                        </div>
                        <div class="flex-1 overflow-y-auto p-6 space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                                <input v-model="articleForm.title" type="text" required maxlength="255" placeholder="Article title" class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#EF233C]/20 focus:border-[#EF233C]/40" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Content</label>
                                <TiptapEditor v-model="articleForm.content" placeholder="Write your article…" />
                            </div>
                        </div>
                        <div class="flex justify-end gap-2 px-6 py-4 border-t border-gray-100 flex-shrink-0">
                            <button type="button" @click="showArticleModal = false" class="px-4 py-2 text-sm border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">Cancel</button>
                            <button @click="submitArticle" :disabled="articleSubmitting" class="px-4 py-2 text-sm font-medium bg-[#2B2D42] text-white rounded-lg hover:bg-[#EF233C] transition-colors disabled:opacity-60">
                                {{ articleSubmitting ? 'Creating…' : 'Create article' }}
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import TiptapEditor from '@/Components/TiptapEditor.vue';
import EmojiPicker from '@/Components/EmojiPicker.vue';
import {
    BookOpenIcon, PlusIcon, MagnifyingGlassIcon, XMarkIcon,
} from '@heroicons/vue/24/outline';
import dayjs from 'dayjs';
import relativeTime from 'dayjs/plugin/relativeTime';
dayjs.extend(relativeTime);

const props = defineProps({
    categories: { type: Array, default: () => [] },
    isPrivileged: { type: Boolean, default: false },
    search: { type: String, default: '' },
});

const searchQuery    = ref(props.search);
const activeCategory = ref(null);

// ── Derived data ──────────────────────────────────────────────────────────────

const totalArticleCount = computed(() =>
    props.categories.reduce((n, c) => n + c.articles.length, 0)
);

const activeCategoryObj = computed(() =>
    activeCategory.value ? props.categories.find(c => c.id === activeCategory.value) ?? null : null
);

const flatArticles = computed(() => {
    const all = [];
    for (const cat of props.categories) {
        for (const a of cat.articles) {
            all.push({ ...a, categoryId: cat.id });
        }
    }
    return all;
});

const visibleArticles = computed(() => {
    if (activeCategory.value) {
        return flatArticles.value.filter(a => a.categoryId === activeCategory.value);
    }
    return flatArticles.value;
});

function categorySlugFor(catId) {
    return props.categories.find(c => c.id === catId)?.slug ?? catId;
}
function categoryNameFor(catId) {
    return props.categories.find(c => c.id === catId)?.name ?? '';
}
function categoryIconFor(catId) {
    return props.categories.find(c => c.id === catId)?.icon || '📁';
}
function formatDate(iso) {
    return iso ? dayjs(iso).fromNow() : '';
}

function doSearch() {
    router.get(route('kb.index'), { q: searchQuery.value }, { preserveState: true, replace: true });
}

// ── Category modal ─────────────────────────────────────────────────────────

const showCategoryModal = ref(false);
const editingCategory   = ref(null);
const catSubmitting     = ref(false);
const catForm = ref({ name: '', icon: '', description: '' });

function openEditCategory(cat) {
    editingCategory.value = cat;
    catForm.value = { name: cat.name, icon: cat.icon ?? '', description: cat.description ?? '' };
    showCategoryModal.value = true;
}

function closeCategoryModal() {
    showCategoryModal.value = false;
    editingCategory.value   = null;
    catForm.value = { name: '', icon: '', description: '' };
}

function submitCategory() {
    catSubmitting.value = true;
    if (editingCategory.value) {
        router.patch(route('kb.categories.update', editingCategory.value.id), catForm.value, {
            onFinish: () => { catSubmitting.value = false; closeCategoryModal(); },
        });
    } else {
        router.post(route('kb.categories.store'), catForm.value, {
            onFinish: () => { catSubmitting.value = false; closeCategoryModal(); },
        });
    }
}

function deleteCategory() {
    if (! confirm('Delete this category and all its articles?')) return;
    router.delete(route('kb.categories.destroy', editingCategory.value.id), {
        onFinish: () => closeCategoryModal(),
    });
}

// ── Article modal ──────────────────────────────────────────────────────────

const showArticleModal  = ref(false);
const articleSubmitting = ref(false);
const articleForm = ref({ title: '', content: '' });

function openNewArticle() {
    articleForm.value = { title: '', content: '' };
    showArticleModal.value = true;
}

function submitArticle() {
    if (! activeCategoryObj.value) return;
    articleSubmitting.value = true;
    router.post(route('kb.articles.store', activeCategoryObj.value.id), articleForm.value, {
        onFinish: () => { articleSubmitting.value = false; },
        onSuccess: () => { showArticleModal.value = false; },
    });
}
</script>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: opacity 0.2s; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
</style>
