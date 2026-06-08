<template>
    <div class="relative" ref="wrapperRef">
        <!-- Trigger button -->
        <button
            type="button"
            @click="open = !open"
            class="flex items-center justify-center w-14 h-14 rounded-xl border-2 transition-all text-3xl select-none"
            :class="open
                ? 'border-[#EF233C]/60 bg-[#EF233C]/5 shadow-sm'
                : 'border-gray-200 bg-gray-50 hover:border-gray-300 hover:bg-white'"
        >
            {{ modelValue || '📁' }}
        </button>
        <p class="text-xs text-gray-400 mt-1.5 text-center">Click to change</p>

        <!-- Picker popover -->
        <Transition name="picker">
            <div
                v-if="open"
                class="absolute top-full left-0 mt-2 z-50 bg-white rounded-2xl shadow-2xl border border-gray-200 w-72"
                style="max-height: 340px; display: flex; flex-direction: column;"
            >
                <!-- Search -->
                <div class="p-2.5 border-b border-gray-100 flex-shrink-0">
                    <div class="relative">
                        <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" fill="currentColor" viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Search emoji…"
                            class="w-full pl-8 pr-3 py-1.5 text-xs rounded-lg border border-gray-200 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-[#EF233C]/20 focus:border-[#EF233C]/40"
                            @click.stop
                        />
                    </div>
                </div>

                <!-- Emoji grid -->
                <div class="overflow-y-auto flex-1 p-2">
                    <template v-if="search">
                        <div class="grid grid-cols-8 gap-0.5">
                            <button
                                v-for="e in searchResults"
                                :key="e.emoji"
                                type="button"
                                @click="select(e.emoji)"
                                class="w-8 h-8 flex items-center justify-center text-xl rounded-lg hover:bg-gray-100 transition-colors"
                                :class="{ 'bg-[#EF233C]/10 ring-2 ring-[#EF233C]/30': modelValue === e.emoji }"
                                :title="e.label"
                            >{{ e.emoji }}</button>
                        </div>
                        <p v-if="searchResults.length === 0" class="text-center text-xs text-gray-400 py-4">No results</p>
                    </template>

                    <template v-else>
                        <div v-for="group in groups" :key="group.name" class="mb-3">
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1.5 px-1">{{ group.name }}</p>
                            <div class="grid grid-cols-8 gap-0.5">
                                <button
                                    v-for="e in group.items"
                                    :key="e.emoji"
                                    type="button"
                                    @click="select(e.emoji)"
                                    class="w-8 h-8 flex items-center justify-center text-xl rounded-lg hover:bg-gray-100 transition-colors"
                                    :class="{ 'bg-[#EF233C]/10 ring-2 ring-[#EF233C]/30': modelValue === e.emoji }"
                                    :title="e.label"
                                >{{ e.emoji }}</button>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Clear button -->
                <div class="border-t border-gray-100 p-2 flex-shrink-0">
                    <button type="button" @click="select('')" class="w-full text-xs text-gray-400 hover:text-gray-600 transition-colors py-1">
                        Clear (use default 📁)
                    </button>
                </div>
            </div>
        </Transition>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';

const props = defineProps({
    modelValue: { type: String, default: '' },
});
const emit = defineEmits(['update:modelValue']);

const open       = ref(false);
const search     = ref('');
const wrapperRef = ref(null);

const groups = [
    {
        name: 'Documents',
        items: [
            { emoji: '📄', label: 'Page' },
            { emoji: '📃', label: 'Document' },
            { emoji: '📋', label: 'Clipboard' },
            { emoji: '📊', label: 'Chart' },
            { emoji: '📈', label: 'Chart up' },
            { emoji: '📉', label: 'Chart down' },
            { emoji: '📝', label: 'Memo' },
            { emoji: '📜', label: 'Scroll' },
            { emoji: '🗒️', label: 'Notepad' },
            { emoji: '🗓️', label: 'Calendar' },
            { emoji: '📅', label: 'Calendar' },
            { emoji: '📆', label: 'Tear-off calendar' },
            { emoji: '🗂️', label: 'Card index' },
            { emoji: '📁', label: 'Folder' },
            { emoji: '📂', label: 'Open folder' },
            { emoji: '🗄️', label: 'File cabinet' },
        ],
    },
    {
        name: 'Work',
        items: [
            { emoji: '💼', label: 'Briefcase' },
            { emoji: '🏢', label: 'Office' },
            { emoji: '🏗️', label: 'Construction' },
            { emoji: '🔧', label: 'Wrench' },
            { emoji: '🔨', label: 'Hammer' },
            { emoji: '⚙️', label: 'Gear' },
            { emoji: '🛠️', label: 'Tools' },
            { emoji: '🔩', label: 'Bolt' },
            { emoji: '💡', label: 'Idea' },
            { emoji: '🎯', label: 'Target' },
            { emoji: '📌', label: 'Pin' },
            { emoji: '🔖', label: 'Bookmark' },
            { emoji: '✅', label: 'Check' },
            { emoji: '⚠️', label: 'Warning' },
            { emoji: '🏷️', label: 'Label' },
            { emoji: '🔑', label: 'Key' },
        ],
    },
    {
        name: 'People',
        items: [
            { emoji: '👥', label: 'Team' },
            { emoji: '👤', label: 'Person' },
            { emoji: '👨‍💼', label: 'Man office' },
            { emoji: '👩‍💼', label: 'Woman office' },
            { emoji: '🤝', label: 'Handshake' },
            { emoji: '👋', label: 'Wave' },
            { emoji: '🏆', label: 'Trophy' },
            { emoji: '🎓', label: 'Graduation' },
            { emoji: '👷', label: 'Worker' },
            { emoji: '🧑‍🏫', label: 'Teacher' },
        ],
    },
    {
        name: 'Tech & Comms',
        items: [
            { emoji: '💻', label: 'Laptop' },
            { emoji: '🖥️', label: 'Desktop' },
            { emoji: '📱', label: 'Phone' },
            { emoji: '⌨️', label: 'Keyboard' },
            { emoji: '🖨️', label: 'Printer' },
            { emoji: '📧', label: 'Email' },
            { emoji: '📨', label: 'Envelope' },
            { emoji: '💬', label: 'Chat' },
            { emoji: '📞', label: 'Phone' },
            { emoji: '📡', label: 'Antenna' },
        ],
    },
    {
        name: 'Site & Build',
        items: [
            { emoji: '🏡', label: 'House garden' },
            { emoji: '🏠', label: 'House' },
            { emoji: '🌳', label: 'Tree' },
            { emoji: '🌲', label: 'Evergreen' },
            { emoji: '🌿', label: 'Herb' },
            { emoji: '🪵', label: 'Wood' },
            { emoji: '🧱', label: 'Brick' },
            { emoji: '🪟', label: 'Window' },
            { emoji: '🚪', label: 'Door' },
            { emoji: '🏡', label: 'Garden room' },
            { emoji: '⛏️', label: 'Pick' },
            { emoji: '🪣', label: 'Bucket' },
            { emoji: '🧰', label: 'Toolbox' },
            { emoji: '📐', label: 'Ruler' },
            { emoji: '📏', label: 'Straight ruler' },
            { emoji: '🔌', label: 'Plug' },
        ],
    },
    {
        name: 'Finance',
        items: [
            { emoji: '💰', label: 'Money' },
            { emoji: '💵', label: 'Dollar' },
            { emoji: '💸', label: 'Flying money' },
            { emoji: '🏦', label: 'Bank' },
            { emoji: '💳', label: 'Credit card' },
            { emoji: '🧾', label: 'Receipt' },
            { emoji: '💹', label: 'Yen chart' },
        ],
    },
    {
        name: 'General',
        items: [
            { emoji: '⭐', label: 'Star' },
            { emoji: '🌟', label: 'Glowing star' },
            { emoji: '🔥', label: 'Fire' },
            { emoji: '✨', label: 'Sparkles' },
            { emoji: '🎨', label: 'Art' },
            { emoji: '🎬', label: 'Film' },
            { emoji: '🎵', label: 'Music' },
            { emoji: '🌈', label: 'Rainbow' },
            { emoji: '🌞', label: 'Sun' },
            { emoji: '🔐', label: 'Locked' },
            { emoji: '📣', label: 'Megaphone' },
            { emoji: '🚀', label: 'Rocket' },
            { emoji: '🧩', label: 'Puzzle' },
            { emoji: '🗺️', label: 'Map' },
            { emoji: '🔔', label: 'Bell' },
            { emoji: '❓', label: 'Question' },
        ],
    },
];

const allEmojis = computed(() => groups.flatMap(g => g.items));

const searchResults = computed(() => {
    if (! search.value) return [];
    const q = search.value.toLowerCase();
    return allEmojis.value.filter(e => e.label.toLowerCase().includes(q) || e.emoji.includes(q));
});

function select(emoji) {
    emit('update:modelValue', emoji);
    open.value = false;
    search.value = '';
}

function onOutsideClick(e) {
    if (wrapperRef.value && ! wrapperRef.value.contains(e.target)) {
        open.value = false;
        search.value = '';
    }
}

onMounted(()    => document.addEventListener('mousedown', onOutsideClick));
onBeforeUnmount(() => document.removeEventListener('mousedown', onOutsideClick));
</script>

<style scoped>
.picker-enter-active { transition: opacity 0.15s, transform 0.15s; }
.picker-leave-active { transition: opacity 0.1s, transform 0.1s; }
.picker-enter-from  { opacity: 0; transform: translateY(-6px) scale(0.97); }
.picker-leave-to    { opacity: 0; transform: translateY(-4px) scale(0.98); }
</style>
