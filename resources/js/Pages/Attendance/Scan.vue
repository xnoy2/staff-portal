<template>
    <AppLayout title="QR Scanner">
        <div class="max-w-lg mx-auto space-y-4">
            <div>
                <h1 class="text-lg font-semibold text-gray-800">QR Scanner</h1>
                <p class="text-xs text-gray-500 mt-0.5">Scan a staff member's QR code to clock them in or out.</p>
            </div>

            <!-- Camera scanner -->
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <div v-if="!scanning">
                    <button
                        @click="startScan"
                        class="w-full flex flex-col items-center justify-center gap-3 py-12 rounded-xl border-2 border-dashed border-gray-200 hover:border-[#EF233C] hover:bg-red-50/30 transition-all group"
                    >
                        <div class="w-16 h-16 bg-[#EF233C] rounded-2xl flex items-center justify-center group-hover:scale-105 transition-transform">
                            <CameraIcon class="w-8 h-8 text-white" />
                        </div>
                        <div class="text-center">
                            <p class="text-sm font-medium text-gray-700">Start Camera Scanner</p>
                            <p class="text-xs text-gray-400 mt-0.5">Requires camera permission</p>
                        </div>
                    </button>
                </div>

                <!-- Scanner viewport -->
                <div v-show="scanning" class="space-y-3">
                    <div class="relative rounded-xl overflow-hidden bg-black">
                        <div id="qr-reader" class="w-full" style="min-height: 280px;" />
                        <!-- Corner guides overlay -->
                        <div class="absolute inset-0 pointer-events-none flex items-center justify-center">
                            <div class="w-48 h-48 relative">
                                <div class="absolute top-0 left-0 w-8 h-8 border-t-4 border-l-4 border-[#EF233C] rounded-tl-sm" />
                                <div class="absolute top-0 right-0 w-8 h-8 border-t-4 border-r-4 border-[#EF233C] rounded-tr-sm" />
                                <div class="absolute bottom-0 left-0 w-8 h-8 border-b-4 border-l-4 border-[#EF233C] rounded-bl-sm" />
                                <div class="absolute bottom-0 right-0 w-8 h-8 border-b-4 border-r-4 border-[#EF233C] rounded-br-sm" />
                            </div>
                        </div>
                        <!-- Scanning pulse when loading -->
                        <div v-if="scanLoading" class="absolute inset-0 bg-black/40 flex items-center justify-center">
                            <div class="w-8 h-8 border-4 border-white border-t-transparent rounded-full animate-spin"></div>
                        </div>
                    </div>
                    <button
                        @click="stopScan"
                        class="w-full text-sm text-gray-500 hover:text-gray-700 py-2 rounded-lg hover:bg-gray-100 transition-colors"
                    >
                        Stop Scanner
                    </button>
                </div>
            </div>

            <!-- Manual input fallback -->
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <h2 class="text-sm font-semibold text-gray-700 mb-3">Manual Entry</h2>
                <p class="text-xs text-gray-400 mb-3">Enter the staff member's UUID directly if the camera is unavailable.</p>
                <div class="flex gap-2">
                    <input
                        v-model="manualId"
                        type="text"
                        placeholder="Staff UUID"
                        class="flex-1 text-sm border-gray-200 rounded-lg focus:ring-[#EF233C] focus:border-[#EF233C]"
                        @keydown.enter="submitManual"
                    />
                    <button
                        @click="submitManual"
                        :disabled="!manualId || scanLoading"
                        class="bg-[#EF233C] text-white px-4 py-2 rounded-lg text-sm hover:bg-[#D90429] transition-colors disabled:opacity-50"
                    >
                        Submit
                    </button>
                </div>
            </div>

            <!-- Recent scans log -->
            <div v-if="scanHistory.length > 0" class="bg-white rounded-xl border border-gray-200 p-5">
                <h2 class="text-sm font-semibold text-gray-700 mb-3">Recent Scans</h2>
                <div class="space-y-2">
                    <div
                        v-for="(scan, i) in scanHistory"
                        :key="i"
                        class="flex items-center gap-3 py-2 border-b border-gray-50 last:border-0"
                    >
                        <div :class="['w-2 h-2 rounded-full flex-shrink-0', scan.success ? 'bg-green-500' : 'bg-red-400']" />
                        <div class="flex-1 min-w-0">
                            <span class="text-sm text-gray-700 font-medium">{{ scan.user?.name ?? 'Unknown' }}</span>
                            <span class="text-xs text-gray-400 ml-2">{{ scan.action === 'clock_in' ? '→ in' : '← out' }}</span>
                        </div>
                        <span class="text-xs text-gray-400 flex-shrink-0">{{ scan.time }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Scan result overlay ──────────────────────────────────────── -->
        <Transition name="scan-result">
            <div
                v-if="overlay"
                class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-black/50 backdrop-blur-sm"
                @click="dismissOverlay"
            >
                <div
                    :class="[
                        'bg-white rounded-3xl shadow-2xl w-full max-w-xs text-center overflow-hidden',
                        overlay.success ? '' : 'border-2 border-red-200',
                    ]"
                    @click.stop
                >
                    <!-- Coloured top band -->
                    <div :class="['h-2', overlayColor]"></div>

                    <div class="px-7 pt-6 pb-7">
                        <!-- Icon circle -->
                        <div :class="['w-20 h-20 rounded-full mx-auto mb-4 flex items-center justify-center shadow-lg', overlayIconBg]">
                            <component :is="overlay.success ? CheckCircleIcon : XCircleIcon" class="w-10 h-10 text-white" />
                        </div>

                        <!-- Staff avatar + name (on success) -->
                        <template v-if="overlay.success && overlay.user">
                            <img
                                :src="overlay.user.avatar_url"
                                :alt="overlay.user.name"
                                class="w-14 h-14 rounded-full mx-auto mb-2 border-4 border-white shadow-md -mt-2 object-cover"
                            />
                            <p class="text-xl font-bold text-gray-900 leading-tight">{{ overlay.user.name }}</p>
                            <span :class="['inline-flex items-center gap-1.5 text-sm px-3 py-1 rounded-full font-semibold mt-2', overlayBadge]">
                                {{ overlay.action === 'clock_in' ? '✓  Clocked In' : '✓  Clocked Out' }}
                            </span>
                        </template>

                        <p :class="['text-sm mt-3 leading-snug', overlay.success ? 'text-gray-600' : 'text-red-600 font-medium']">
                            {{ overlay.message }}
                        </p>
                        <p class="text-xs text-gray-400 mt-1">{{ overlay.time }}</p>

                        <p class="text-[10px] text-gray-300 mt-5">Tap anywhere to dismiss</p>
                    </div>

                    <!-- Auto-close progress bar -->
                    <div class="h-1 bg-gray-100">
                        <div :class="['h-full rounded-full', overlayColor, 'progress-bar']"></div>
                    </div>
                </div>
            </div>
        </Transition>
    </AppLayout>
</template>

<script setup>
import { ref, computed, onUnmounted } from 'vue';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';
import { CameraIcon, CheckCircleIcon, XCircleIcon } from '@heroicons/vue/24/outline';

const scanning    = ref(false);
const scanLoading = ref(false);
const manualId    = ref('');
const overlay     = ref(null);
const scanHistory = ref([]);

let html5QrCode  = null;
let overlayTimer = null;

// ── Overlay colour helpers ────────────────────────────────────────────────────

const overlayColor = computed(() => {
    if (!overlay.value?.success) return 'bg-red-500';
    return overlay.value.action === 'clock_in' ? 'bg-emerald-500' : 'bg-blue-500';
});

const overlayIconBg = computed(() => {
    if (!overlay.value?.success) return 'bg-red-500 shadow-red-200';
    return overlay.value.action === 'clock_in'
        ? 'bg-emerald-500 shadow-emerald-200'
        : 'bg-blue-500 shadow-blue-200';
});

const overlayBadge = computed(() => {
    if (!overlay.value?.success) return 'bg-red-100 text-red-700';
    return overlay.value.action === 'clock_in'
        ? 'bg-emerald-100 text-emerald-700'
        : 'bg-blue-100 text-blue-700';
});

// ── Overlay control ───────────────────────────────────────────────────────────

const OVERLAY_DURATION = 3500;

function showOverlay(result) {
    overlay.value = result;
    if (overlayTimer) clearTimeout(overlayTimer);
    overlayTimer = setTimeout(dismissOverlay, OVERLAY_DURATION);
}

function dismissOverlay() {
    overlay.value = null;
    if (overlayTimer) { clearTimeout(overlayTimer); overlayTimer = null; }
}

// ── Scanner ───────────────────────────────────────────────────────────────────

async function startScan() {
    const { Html5Qrcode } = await import('html5-qrcode');
    html5QrCode = new Html5Qrcode('qr-reader');
    scanning.value = true;
    try {
        await html5QrCode.start(
            { facingMode: 'environment' },
            { fps: 10, qrbox: { width: 220, height: 220 } },
            onScanSuccess,
            () => {}
        );
    } catch (err) {
        scanning.value = false;
        showOverlay({ success: false, message: `Camera error: ${err.message ?? 'Permission denied'}`, time: timestamp() });
    }
}

async function stopScan() {
    if (html5QrCode) {
        try { await html5QrCode.stop(); } catch { /* ignore */ }
        html5QrCode = null;
    }
    scanning.value = false;
}

let lastScannedData = null;
let scanCooldown    = false;

function onScanSuccess(decodedText) {
    if (scanCooldown || decodedText === lastScannedData) return;
    lastScannedData = decodedText;
    scanCooldown    = true;
    setTimeout(() => { scanCooldown = false; lastScannedData = null; }, 4000);
    submitScan(decodedText);
}

function submitManual() {
    if (!manualId.value) return;
    submitScan(btoa(String(manualId.value)));
    manualId.value = '';
}

async function submitScan(scannedData) {
    scanLoading.value = true;
    try {
        const { data } = await axios.post('/attendance/scan', { scanned_data: scannedData });
        const result = { ...data, time: timestamp() };
        showOverlay(result);
        scanHistory.value.unshift(result);
        if (scanHistory.value.length > 10) scanHistory.value.pop();
    } catch (err) {
        const message = err.response?.data?.message ?? 'An error occurred.';
        showOverlay({ success: false, message, time: timestamp() });
    } finally {
        scanLoading.value = false;
    }
}

function timestamp() {
    return new Date().toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
}

onUnmounted(async () => {
    if (overlayTimer) clearTimeout(overlayTimer);
    await stopScan();
});
</script>

<style scoped>
.scan-result-enter-active { transition: opacity 0.2s ease, transform 0.25s ease; }
.scan-result-leave-active { transition: opacity 0.2s ease, transform 0.2s ease; }
.scan-result-enter-from   { opacity: 0; transform: scale(0.92); }
.scan-result-leave-to     { opacity: 0; transform: scale(0.95); }

.progress-bar {
    animation: shrink v-bind('OVERLAY_DURATION + "ms"') linear forwards;
    transform-origin: left;
}

@keyframes shrink {
    from { transform: scaleX(1); }
    to   { transform: scaleX(0); }
}
</style>
