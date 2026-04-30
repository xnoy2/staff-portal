<template>
    <Teleport to="body">
        <Transition name="modal">
            <div
                v-if="open"
                class="fixed inset-0 z-50 flex items-end sm:items-center justify-center sm:p-4 bg-black/50 backdrop-blur-sm"
                @click.self="$emit('close')"
            >
                <div :class="['modal-panel bg-white w-full sm:rounded-2xl rounded-t-2xl shadow-2xl', maxWidth]">
                    <slot />
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
defineProps({
    open:     { type: Boolean, required: true },
    maxWidth: { type: String,  default: 'sm:max-w-md' },
});
defineEmits(['close']);
</script>

<style scoped>
/* ── Backdrop ─────────────────────────────────── */
.modal-enter-active,
.modal-leave-active { transition: opacity 0.25s ease; }
.modal-enter-from,
.modal-leave-to     { opacity: 0; }

/* ── Panel — mobile: slide up from bottom ─────── */
.modal-enter-active .modal-panel {
    transition: transform 0.38s cubic-bezier(0.16, 1, 0.3, 1);
}
.modal-leave-active .modal-panel {
    transition: transform 0.22s cubic-bezier(0.4, 0, 1, 1);
}
.modal-enter-from .modal-panel { transform: translateY(100%); }
.modal-leave-to   .modal-panel { transform: translateY(100%); }

/* ── Panel — desktop (sm+): scale + fade ─────── */
@media (min-width: 640px) {
    .modal-enter-active .modal-panel {
        transition: transform 0.32s cubic-bezier(0.16, 1, 0.3, 1),
                    opacity   0.22s ease;
    }
    .modal-leave-active .modal-panel {
        transition: transform 0.2s cubic-bezier(0.4, 0, 1, 1),
                    opacity   0.18s ease;
    }
    .modal-enter-from .modal-panel {
        transform: scale(0.92) translateY(10px);
        opacity: 0;
    }
    .modal-leave-to .modal-panel {
        transform: scale(0.95) translateY(6px);
        opacity: 0;
    }
}
</style>
