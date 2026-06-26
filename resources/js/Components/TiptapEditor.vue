<template>
    <div class="tiptap-wrapper">
        <!-- Toolbar -->
        <div class="tiptap-toolbar">
            <!-- Formatting -->
            <div class="toolbar-group">
                <button type="button" @click="editor.chain().focus().toggleBold().run()" :class="{ active: editor?.isActive('bold') }" title="Bold">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4"><path d="M8 11h4.5a2.5 2.5 0 1 0 0-5H8v5Zm8.5 5.25A2.75 2.75 0 0 1 13.75 19H8v-5h5.75a2.75 2.75 0 0 1 2.75 2.75v.5ZM6 6h6.5a4.5 4.5 0 0 1 3.544 7.27A4.25 4.25 0 0 1 13.75 21H6V6Z"/></svg>
                </button>
                <button type="button" @click="editor.chain().focus().toggleItalic().run()" :class="{ active: editor?.isActive('italic') }" title="Italic">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4"><path d="M10 4v3h2.21l-3.42 10H6v3h8v-3h-2.21l3.42-10H18V4z"/></svg>
                </button>
                <button type="button" @click="editor.chain().focus().toggleUnderline().run()" :class="{ active: editor?.isActive('underline') }" title="Underline">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4"><path d="M12 17c3.31 0 6-2.69 6-6V3h-2.5v8c0 1.93-1.57 3.5-3.5 3.5S8.5 12.93 8.5 11V3H6v8c0 3.31 2.69 6 6 6zm-7 2v2h14v-2H5z"/></svg>
                </button>
                <button type="button" @click="editor.chain().focus().toggleStrike().run()" :class="{ active: editor?.isActive('strike') }" title="Strikethrough">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4"><path d="M6.85 7.08C6.85 4.37 9.45 3 12.24 3c1.64 0 3 .49 3.9 1.28.77.65 1.46 1.73 1.46 3.24h-2.5c0-.29-.06-1.25-.72-1.72-.39-.27-.99-.56-2.12-.56-2.03 0-2.41 1.06-2.41 1.84 0 .48.26.84.73 1.12H6.85zm-.94 5.42h12.18c.17.15.25.3.25.5 0 1.01-.9 1.5-2.63 1.5-1.46 0-2.33-.55-2.71-1H10.5c.37.46 1.29 2.5 3.73 2.5 2.54 0 4.27-1.36 4.27-3.5 0-.75-.25-1.3-.61-1.5H5.9l.01 1.5z"/></svg>
                </button>
                <button type="button" @click="editor.chain().focus().toggleCode().run()" :class="{ active: editor?.isActive('code') }" title="Inline code">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4"><path d="m8 18-6-6 6-6 1.4 1.45L4.85 12 9.4 16.55zm8 0-1.4-1.45L19.15 12 14.6 7.45 16 6l6 6z"/></svg>
                </button>
            </div>

            <div class="toolbar-divider" />

            <!-- Headings -->
            <div class="toolbar-group">
                <button type="button" @click="editor.chain().focus().toggleHeading({ level: 1 }).run()" :class="{ active: editor?.isActive('heading', { level: 1 }) }" title="Heading 1" class="font-bold text-xs px-1.5">H1</button>
                <button type="button" @click="editor.chain().focus().toggleHeading({ level: 2 }).run()" :class="{ active: editor?.isActive('heading', { level: 2 }) }" title="Heading 2" class="font-bold text-xs px-1.5">H2</button>
                <button type="button" @click="editor.chain().focus().toggleHeading({ level: 3 }).run()" :class="{ active: editor?.isActive('heading', { level: 3 }) }" title="Heading 3" class="font-bold text-xs px-1.5">H3</button>
            </div>

            <div class="toolbar-divider" />

            <!-- Lists -->
            <div class="toolbar-group">
                <button type="button" @click="editor.chain().focus().toggleBulletList().run()" :class="{ active: editor?.isActive('bulletList') }" title="Bullet list">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4"><path d="M4 10.5c-.83 0-1.5.67-1.5 1.5s.67 1.5 1.5 1.5 1.5-.67 1.5-1.5-.67-1.5-1.5-1.5zm0-6c-.83 0-1.5.67-1.5 1.5S3.17 7.5 4 7.5 5.5 6.83 5.5 6 4.83 4.5 4 4.5zm0 12c-.83 0-1.5.68-1.5 1.5s.68 1.5 1.5 1.5 1.5-.68 1.5-1.5-.67-1.5-1.5-1.5zM7 19h14v-2H7zm0-6h14v-2H7zm0-8v2h14V5z"/></svg>
                </button>
                <button type="button" @click="editor.chain().focus().toggleOrderedList().run()" :class="{ active: editor?.isActive('orderedList') }" title="Numbered list">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4"><path d="M2 17h2v.5H3v1h1v.5H2v1h3v-4H2zm1-9h1V4H2v1h1zm-1 3h1.8L2 13.1v.9h3v-1H3.2L5 10.9V10H2zm5-5v2h14V6zm0 14h14v-2H7zm0-6h14v-2H7z"/></svg>
                </button>
                <button type="button" @click="editor.chain().focus().toggleBlockquote().run()" :class="{ active: editor?.isActive('blockquote') }" title="Blockquote">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4"><path d="M6 17h3l2-4V7H5v6h3zm8 0h3l2-4V7h-6v6h3z"/></svg>
                </button>
                <button type="button" @click="editor.chain().focus().toggleCodeBlock().run()" :class="{ active: editor?.isActive('codeBlock') }" title="Code block">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4"><path d="M9.4 16.6 4.8 12l4.6-4.6L8 6l-6 6 6 6zm5.2 0 4.6-4.6-4.6-4.6L16 6l6 6-6 6z"/></svg>
                </button>
            </div>

            <div class="toolbar-divider" />

            <!-- Alignment -->
            <div class="toolbar-group">
                <button type="button" @click="editor.chain().focus().setTextAlign('left').run()" :class="{ active: editor?.isActive({ textAlign: 'left' }) }" title="Align left">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4"><path d="M15 15H3v2h12zm0-8H3v2h12zM3 13h18v-2H3zm0 8h18v-2H3zM3 3v2h18V3z"/></svg>
                </button>
                <button type="button" @click="editor.chain().focus().setTextAlign('center').run()" :class="{ active: editor?.isActive({ textAlign: 'center' }) }" title="Align center">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4"><path d="M7 15v2h10v-2zm-4 6h18v-2H3zm0-8h18v-2H3zm4-6v2h10V7zM3 3v2h18V3z"/></svg>
                </button>
                <button type="button" @click="editor.chain().focus().setTextAlign('right').run()" :class="{ active: editor?.isActive({ textAlign: 'right' }) }" title="Align right">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4"><path d="M3 21h18v-2H3zm6-4h12v-2H9zm-6-4h18v-2H3zm6-4h12V7H9zM3 3v2h18V3z"/></svg>
                </button>
            </div>

            <div class="toolbar-divider" />

            <!-- Links & misc -->
            <div class="toolbar-group">
                <button type="button" @click="setLink" :class="{ active: editor?.isActive('link') }" title="Link">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4"><path d="M3.9 12c0-1.71 1.39-3.1 3.1-3.1h4V7H7c-2.76 0-5 2.24-5 5s2.24 5 5 5h4v-1.9H7c-1.71 0-3.1-1.39-3.1-3.1zM8 13h8v-2H8zm9-6h-4v1.9h4c1.71 0 3.1 1.39 3.1 3.1s-1.39 3.1-3.1 3.1h-4V17h4c2.76 0 5-2.24 5-5s-2.24-5-5-5z"/></svg>
                </button>
                <button type="button" @click="editor.chain().focus().setHorizontalRule().run()" title="Divider">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4"><path d="M19 13H5v-2h14z"/></svg>
                </button>
            </div>

            <div class="toolbar-divider" />

            <!-- Media upload -->
            <div class="toolbar-group">
                <button
                    type="button"
                    @click="triggerImageUpload"
                    :disabled="uploading"
                    title="Insert image"
                    class="relative"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4"><path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg>
                </button>
                <button
                    type="button"
                    @click="triggerVideoUpload"
                    :disabled="uploading"
                    title="Upload video"
                    class="relative"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4"><path d="M17 10.5V7c0-.55-.45-1-1-1H4c-.55 0-1 .45-1 1v10c0 .55.45 1 1 1h12c.55 0 1-.45 1-1v-3.5l4 4v-11l-4 4z"/></svg>
                </button>
                <!-- Upload progress indicator -->
                <span v-if="uploading" class="text-xs text-gray-400 flex items-center gap-1 ml-1">
                    <span class="w-3 h-3 border-2 border-gray-300 border-t-gray-600 rounded-full animate-spin" />
                    Uploading…
                </span>
            </div>

            <div class="toolbar-divider" />

            <div class="toolbar-group">
                <button type="button" @click="editor.chain().focus().undo().run()" :disabled="!editor?.can().undo()" title="Undo">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4"><path d="M12.5 8c-2.65 0-5.05.99-6.9 2.6L2 7v9h9l-3.62-3.62c1.39-1.16 3.16-1.88 5.12-1.88 3.54 0 6.55 2.31 7.6 5.5l2.37-.78C21.08 11.03 17.15 8 12.5 8z"/></svg>
                </button>
                <button type="button" @click="editor.chain().focus().redo().run()" :disabled="!editor?.can().redo()" title="Redo">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4"><path d="M18.4 10.6C16.55 8.99 14.15 8 11.5 8c-4.65 0-8.58 3.03-9.96 7.22L3.9 16c1.05-3.19 4.05-5.5 7.6-5.5 1.95 0 3.73.72 5.12 1.88L13 16h9V7l-3.6 3.6z"/></svg>
                </button>
            </div>
        </div>

        <!-- Hidden file inputs -->
        <input ref="imageInput" type="file" accept="image/*" class="hidden" @change="handleImageUpload" />
        <input ref="videoInput" type="file" accept="video/mp4,video/mov,video/webm,video/avi,video/x-matroska" class="hidden" @change="handleVideoUpload" />

        <!-- Editor content -->
        <EditorContent :editor="editor" class="tiptap-content" />
    </div>
</template>

<script setup>
import { ref, watch, onBeforeUnmount } from 'vue';
import { useEditor, EditorContent } from '@tiptap/vue-3';
import { Node, mergeAttributes } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import Underline from '@tiptap/extension-underline';
import Link from '@tiptap/extension-link';
import TextAlign from '@tiptap/extension-text-align';
import Placeholder from '@tiptap/extension-placeholder';
import Image from '@tiptap/extension-image';
import axios from 'axios';

const props = defineProps({
    modelValue: { type: String, default: '' },
    placeholder: { type: String, default: 'Start writing…' },
});

const emit = defineEmits(['update:modelValue']);

// ── Custom Video node ─────────────────────────────────────────────────────────

const Video = Node.create({
    name: 'kbVideo',
    group: 'block',
    atom: true,

    addAttributes() {
        return { src: { default: null } };
    },

    parseHTML() {
        return [{ tag: 'video[data-kb]' }];
    },

    renderHTML({ HTMLAttributes }) {
        return ['video', mergeAttributes(HTMLAttributes, {
            'data-kb': '',
            controls: '',
            controlsList: 'nodownload noremoteplayback',
            disablePictureInPicture: '',
            class: 'kb-video',
            preload: 'metadata',
        })];
    },

    addNodeView() {
        return ({ node }) => {
            const video = document.createElement('video');
            video.src = node.attrs.src;
            video.controls = true;
            video.setAttribute('controlsList', 'nodownload noremoteplayback');
            video.setAttribute('disablePictureInPicture', '');
            video.setAttribute('preload', 'metadata');
            video.className = 'kb-video';
            video.addEventListener('contextmenu', e => e.preventDefault());

            return {
                dom: video,
                update(updatedNode) {
                    if (updatedNode.attrs.src !== node.attrs.src) {
                        video.src = updatedNode.attrs.src;
                    }
                    return true;
                },
            };
        };
    },
});

// ── Resizable image node ──────────────────────────────────────────────────────
// Extends the standard Image with a width attribute and a corner drag handle so
// the author can adjust how big an inserted image appears. The width is saved on
// the <img> tag, so the article view renders it at the chosen size.
const ResizableImage = Image.extend({
    addAttributes() {
        return {
            ...this.parent?.(),
            width: {
                default: null,
                parseHTML: el => el.getAttribute('width'),
                renderHTML: attrs => (attrs.width ? { width: attrs.width } : {}),
            },
            align: {
                default: 'center',
                parseHTML: el => el.getAttribute('data-align') || 'center',
                renderHTML: attrs => (attrs.align && attrs.align !== 'center' ? { 'data-align': attrs.align } : {}),
            },
        };
    },

    addNodeView() {
        return ({ node, editor, getPos }) => {
            const wrap = document.createElement('div');
            wrap.className = 'kb-img-wrap';

            const img = document.createElement('img');
            img.src = node.attrs.src;
            if (node.attrs.alt) img.alt = node.attrs.alt;
            const applyWidth = (w) => {
                img.style.width = w ? (/[%a-z]/i.test(String(w)) ? w : w + 'px') : '';
            };
            applyWidth(node.attrs.width);
            wrap.appendChild(img);

            // Alignment / text-wrap toolbar (shown when the image is selected/hovered)
            const setAttr = (patch) => {
                const pos = typeof getPos === 'function' ? getPos() : null;
                if (pos == null) return;
                const cur = editor.view.state.doc.nodeAt(pos);
                if (! cur) return;
                editor.view.dispatch(editor.view.state.tr.setNodeMarkup(pos, undefined, { ...cur.attrs, ...patch }));
            };
            const ICONS = {
                left:   '<svg viewBox="0 0 20 20" fill="currentColor" class="w-3.5 h-3.5"><rect x="2" y="3" width="8" height="6" rx="1"/><rect x="2" y="11" width="16" height="1.6"/><rect x="2" y="14.5" width="16" height="1.6"/></svg>',
                center: '<svg viewBox="0 0 20 20" fill="currentColor" class="w-3.5 h-3.5"><rect x="6" y="3" width="8" height="6" rx="1"/><rect x="2" y="11" width="16" height="1.6"/><rect x="2" y="14.5" width="16" height="1.6"/></svg>',
                right:  '<svg viewBox="0 0 20 20" fill="currentColor" class="w-3.5 h-3.5"><rect x="10" y="3" width="8" height="6" rx="1"/><rect x="2" y="11" width="16" height="1.6"/><rect x="2" y="14.5" width="16" height="1.6"/></svg>',
            };
            const bar = document.createElement('div');
            bar.className = 'kb-img-bar';
            const btns = {};
            ['left', 'center', 'right'].forEach((a) => {
                const b = document.createElement('button');
                b.type = 'button';
                b.className = 'kb-img-btn';
                b.title = a === 'center' ? 'Center' : `Wrap text (${a})`;
                b.innerHTML = ICONS[a];
                b.addEventListener('mousedown', (e) => { e.preventDefault(); e.stopPropagation(); });
                b.addEventListener('click', (e) => { e.preventDefault(); e.stopPropagation(); setAttr({ align: a }); });
                bar.appendChild(b);
                btns[a] = b;
            });
            wrap.appendChild(bar);

            const handle = document.createElement('span');
            handle.className = 'kb-img-handle';
            handle.title = 'Drag to resize';
            wrap.appendChild(handle);

            const applyAlign = (a) => {
                const al = a || 'center';
                wrap.classList.remove('align-left', 'align-center', 'align-right');
                wrap.classList.add('align-' + al);
                Object.entries(btns).forEach(([k, b]) => b.classList.toggle('active', k === al));
            };
            applyAlign(node.attrs.align);

            let startX = 0, startW = 0, resizing = false;
            const onMove = (e) => {
                if (! resizing) return;
                img.style.width = Math.max(60, Math.round(startW + (e.clientX - startX))) + 'px';
            };
            const onUp = () => {
                if (! resizing) return;
                resizing = false;
                document.removeEventListener('pointermove', onMove);
                document.removeEventListener('pointerup', onUp);
                const pos = typeof getPos === 'function' ? getPos() : null;
                if (pos == null) return;
                const cur = editor.view.state.doc.nodeAt(pos);
                if (! cur) return;
                const w = Math.round(img.getBoundingClientRect().width);
                editor.view.dispatch(
                    editor.view.state.tr.setNodeMarkup(pos, undefined, { ...cur.attrs, width: w })
                );
            };
            handle.addEventListener('pointerdown', (e) => {
                e.preventDefault();
                e.stopPropagation();
                resizing = true;
                startX = e.clientX;
                startW = img.getBoundingClientRect().width;
                document.addEventListener('pointermove', onMove);
                document.addEventListener('pointerup', onUp);
            });

            return {
                dom: wrap,
                update(updatedNode) {
                    if (updatedNode.type.name !== node.type.name) return false;
                    if (updatedNode.attrs.src !== img.getAttribute('src')) img.src = updatedNode.attrs.src;
                    applyWidth(updatedNode.attrs.width);
                    applyAlign(updatedNode.attrs.align);
                    return true;
                },
                selectNode() { wrap.classList.add('kb-img-selected'); },
                deselectNode() { wrap.classList.remove('kb-img-selected'); },
            };
        };
    },
});

// ── Editor ────────────────────────────────────────────────────────────────────

const editor = useEditor({
    content: props.modelValue,
    extensions: [
        StarterKit,
        Underline,
        TextAlign.configure({ types: ['heading', 'paragraph'] }),
        Link.configure({ openOnClick: false }),
        Placeholder.configure({ placeholder: props.placeholder }),
        ResizableImage.configure({ inline: false, allowBase64: false }),
        Video,
    ],
    editorProps: {
        // Paste an image/screenshot straight into the article at the cursor.
        handlePaste(view, event) {
            const files = mediaFilesFrom(event.clipboardData);
            if (! files.length) return false;
            event.preventDefault();
            files.forEach(f => insertMediaFile(f));
            return true;
        },
        // Drop an image/video file anywhere in the article — inserts where you drop it.
        handleDrop(view, event) {
            const files = mediaFilesFrom(event.dataTransfer);
            if (! files.length) return false;
            event.preventDefault();
            const coords = view.posAtCoords({ left: event.clientX, top: event.clientY });
            files.forEach(f => insertMediaFile(f, coords ? coords.pos : null));
            return true;
        },
    },
    onUpdate({ editor }) {
        emit('update:modelValue', editor.getHTML());
    },
});

watch(() => props.modelValue, (val) => {
    if (editor.value && editor.value.getHTML() !== val) {
        editor.value.commands.setContent(val, false);
    }
});

onBeforeUnmount(() => editor.value?.destroy());

// ── Link ──────────────────────────────────────────────────────────────────────

function setLink() {
    const prev = editor.value.getAttributes('link').href;
    const url  = window.prompt('URL', prev);
    if (url === null) return;
    if (url === '') {
        editor.value.chain().focus().extendMarkRange('link').unsetLink().run();
        return;
    }
    editor.value.chain().focus().extendMarkRange('link').setLink({ href: url }).run();
}

// ── Media upload ──────────────────────────────────────────────────────────────

const imageInput = ref(null);
const videoInput = ref(null);
const uploading  = ref(false);

function triggerImageUpload() {
    imageInput.value?.click();
}

function triggerVideoUpload() {
    videoInput.value?.click();
}

async function handleImageUpload(event) {
    const file = event.target.files?.[0];
    event.target.value = '';
    await insertMediaFile(file);
}

async function handleVideoUpload(event) {
    const file = event.target.files?.[0];
    event.target.value = '';
    await insertMediaFile(file);
}

// Pull image/video files out of a clipboard or drag-drop data transfer.
function mediaFilesFrom(dataTransfer) {
    if (! dataTransfer) return [];
    let files = [];
    if (dataTransfer.files && dataTransfer.files.length) {
        files = Array.from(dataTransfer.files);
    } else if (dataTransfer.items) {
        for (const item of dataTransfer.items) {
            if (item.kind === 'file') {
                const f = item.getAsFile();
                if (f) files.push(f);
            }
        }
    }
    return files.filter(f => f.type.startsWith('image/') || f.type.startsWith('video/'));
}

// Upload a file and insert it at the cursor (or at `pos`, for drops).
async function insertMediaFile(file, pos = null) {
    if (! file) return;
    const isImage = file.type.startsWith('image/');
    const isVideo = file.type.startsWith('video/');
    if (! isImage && ! isVideo) return;

    uploading.value = true;
    try {
        const { url } = await uploadFile(file);
        let chain = editor.value.chain().focus();
        if (pos != null) chain = chain.setTextSelection(pos);
        if (isImage) {
            chain.setImage({ src: url }).run();
        } else {
            chain.insertContent({ type: 'kbVideo', attrs: { src: url } }).run();
        }
    } catch (e) {
        alert('Upload failed. Please try again.');
    } finally {
        uploading.value = false;
    }
}

async function uploadFile(file) {
    const formData = new FormData();
    formData.append('file', file);
    const response = await axios.post(route('kb.upload'), formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
    });
    return response.data;
}
</script>

<style>
.tiptap-wrapper {
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    background: #fff;
    /* No overflow:hidden — it would break the sticky toolbar below. */
}

.tiptap-toolbar {
    display: flex;
    flex-wrap: wrap;
    gap: 0.25rem;
    padding: 0.5rem 0.75rem;
    background: #f9fafb;
    border-bottom: 1px solid #e5e7eb;
    align-items: center;
    /* Keep the formatting/image buttons in view while editing long articles. */
    position: sticky;
    top: 0;
    z-index: 20;
    border-top-left-radius: 0.5rem;
    border-top-right-radius: 0.5rem;
}

.toolbar-group {
    display: flex;
    align-items: center;
    gap: 0.125rem;
}

.toolbar-divider {
    width: 1px;
    height: 1.25rem;
    background: #d1d5db;
    margin: 0 0.25rem;
}

.tiptap-toolbar button {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0.25rem 0.375rem;
    border-radius: 0.25rem;
    border: none;
    background: transparent;
    color: #374151;
    cursor: pointer;
    transition: background 0.15s, color 0.15s;
    min-width: 1.75rem;
    min-height: 1.75rem;
}

.tiptap-toolbar button:hover:not(:disabled) {
    background: #e5e7eb;
    color: #111827;
}

.tiptap-toolbar button.active {
    background: #2B2D42;
    color: #fff;
}

.tiptap-toolbar button:disabled {
    opacity: 0.35;
    cursor: not-allowed;
}

.tiptap-content {
    min-height: 300px;
    padding: 1rem 1.25rem;
    font-size: 0.9375rem;
    line-height: 1.7;
    color: #111827;
}

.tiptap-content:focus-within {
    outline: none;
}

.tiptap-content .ProseMirror {
    outline: none;
    min-height: 280px;
    /* Make the blinking text cursor easy to spot while editing */
    caret-color: #EF233C;
}

/* Clearer text selection so it's obvious what's highlighted */
.tiptap-content .ProseMirror ::selection {
    background: rgba(239, 35, 60, 0.18);
}

.tiptap-content .ProseMirror > * + * {
    margin-top: 0.75em;
}

.tiptap-content .ProseMirror h1 { font-size: 1.75rem; font-weight: 700; line-height: 1.25; }
.tiptap-content .ProseMirror h2 { font-size: 1.4rem;  font-weight: 600; line-height: 1.3; }
.tiptap-content .ProseMirror h3 { font-size: 1.15rem; font-weight: 600; line-height: 1.4; }

.tiptap-content .ProseMirror ul { list-style: disc; padding-left: 1.5rem; }
.tiptap-content .ProseMirror ol { list-style: decimal; padding-left: 1.5rem; }
.tiptap-content .ProseMirror li { margin: 0.25em 0; }

.tiptap-content .ProseMirror blockquote {
    border-left: 3px solid #d1d5db;
    padding-left: 1rem;
    color: #6b7280;
    font-style: italic;
}

.tiptap-content .ProseMirror code {
    background: #f3f4f6;
    border-radius: 0.25rem;
    padding: 0.125rem 0.25rem;
    font-family: monospace;
    font-size: 0.875em;
}

.tiptap-content .ProseMirror pre {
    background: #1e293b;
    color: #e2e8f0;
    border-radius: 0.5rem;
    padding: 1rem;
    overflow-x: auto;
}

.tiptap-content .ProseMirror pre code {
    background: none;
    padding: 0;
    color: inherit;
    font-size: 0.875rem;
}

.tiptap-content .ProseMirror a {
    color: #EF233C;
    text-decoration: underline;
}

.tiptap-content .ProseMirror hr {
    border: none;
    border-top: 2px solid #e5e7eb;
    margin: 1.5rem 0;
}

.tiptap-content .ProseMirror img {
    max-width: 100%;
    border-radius: 0.5rem;
    display: block;
    margin: 0.5rem auto;
}

/* Resizable image: wrapper shrinks to the image, handle sits at the corner */
.tiptap-content .ProseMirror .kb-img-wrap {
    position: relative;
    display: block;
    width: fit-content;
    max-width: 100%;
    line-height: 0;
}
.tiptap-content .ProseMirror .kb-img-wrap.align-center { margin: 0.75rem auto; float: none; clear: both; }
.tiptap-content .ProseMirror .kb-img-wrap.align-left   { float: left;  margin: 0.4rem 1.1rem 0.6rem 0; }
.tiptap-content .ProseMirror .kb-img-wrap.align-right  { float: right; margin: 0.4rem 0 0.6rem 1.1rem; }
/* contain the floats so they don't spill out of the editor */
.tiptap-content .ProseMirror::after { content: ''; display: block; clear: both; }

.tiptap-content .ProseMirror .kb-img-wrap img {
    margin: 0;
    max-width: 100%;
    height: auto;
    border: 1px solid #e5e7eb;
}

/* Alignment / wrap toolbar */
.tiptap-content .ProseMirror .kb-img-bar {
    position: absolute;
    top: 5px;
    left: 50%;
    transform: translateX(-50%);
    display: none;
    gap: 1px;
    background: rgba(43,45,66,0.92);
    border-radius: 7px;
    padding: 2px;
    z-index: 6;
    line-height: 0;
}
.tiptap-content .ProseMirror .kb-img-wrap:hover .kb-img-bar,
.tiptap-content .ProseMirror .kb-img-wrap.kb-img-selected .kb-img-bar { display: flex; }
.tiptap-content .ProseMirror .kb-img-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 22px;
    border: none;
    border-radius: 5px;
    background: transparent;
    color: #fff;
    cursor: pointer;
}
.tiptap-content .ProseMirror .kb-img-btn:hover { background: rgba(255,255,255,0.15); }
.tiptap-content .ProseMirror .kb-img-btn.active { background: #EF233C; }
.tiptap-content .ProseMirror .kb-img-handle {
    position: absolute;
    right: -6px;
    bottom: -6px;
    width: 14px;
    height: 14px;
    border-radius: 3px;
    background: #EF233C;
    border: 2px solid #fff;
    box-shadow: 0 1px 2px rgba(0,0,0,0.25);
    cursor: nwse-resize;
    opacity: 0;
    transition: opacity 0.15s;
    touch-action: none;
}
.tiptap-content .ProseMirror .kb-img-wrap:hover .kb-img-handle,
.tiptap-content .ProseMirror .kb-img-wrap.kb-img-selected .kb-img-handle {
    opacity: 1;
}
.tiptap-content .ProseMirror .kb-img-wrap.kb-img-selected img {
    outline: 2px solid #EF233C;
    outline-offset: 1px;
}

.tiptap-content .ProseMirror .kb-video,
.kb-content .kb-video {
    width: 100%;
    max-width: 100%;
    border-radius: 0.5rem;
    display: block;
    background: #000;
    margin: 0.5rem 0;
}

.tiptap-content .ProseMirror p.is-editor-empty:first-child::before {
    content: attr(data-placeholder);
    float: left;
    color: #9ca3af;
    pointer-events: none;
    height: 0;
}
</style>
