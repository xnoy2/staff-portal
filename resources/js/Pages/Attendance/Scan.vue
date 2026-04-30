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
                        class="w-full flex flex-col items-center justify-center gap-3 py-12 rounded-xl border-2 border-dashed border-gray-200 hover:border-[#EF233C] hover:bg-green-50/30 transition-all group"
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
                <p class="text-xs text-gray-400 mb-3">Enter the staff member's UUID directly (found on their profile or My QR page) if the camera is unavailable.</p>
                <div class="flex gap-2">
                    <input
                        v-model="manualId"
                        type="text"
                        placeholder="Staff UUID (e.g. xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx)"
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

            <!-- Result card -->
            <Transition
                enter-from-class="opacity-0 translate-y-2"
                enter-active-class="transition-all duration-300"
                leave-to-class="opacity-0 translate-y-2"
                leave-active-class="transition-all duration-200"
            >
                <div
                    v-if="lastResult"
                    :class="[
                        'rounded-xl border p-5 flex gap-4 items-start',
                        lastResult.success
                            ? 'bg-green-50 border-green-200'
                            : 'bg-red-50 border-red-200',
                    ]"
                >
                    <div
                        :class="[
                            'w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0',
                            lastResult.success ? 'bg-green-500' : 'bg-red-500',
                        ]"
                    >
                        <component
                            :is="lastResult.success ? CheckCircleIcon : XCircleIcon"
                            class="w-6 h-6 text-white"
                        />
                    </div>
                    <div class="flex-1">
                        <div v-if="lastResult.user" class="flex items-center gap-2 mb-1">
                            <img
                                :src="lastResult.user.avatar_url"
                                :alt="lastResult.user.name"
                                class="w-6 h-6 rounded-full object-cover"
                            />
                            <span class="text-sm font-semibold text-gray-800">{{ lastResult.user.name }}</span>
                            <span
                                :class="[
                                    'text-xs px-2 py-0.5 rounded-full font-medium',
                                    lastResult.action === 'clock_in'
                                        ? 'bg-green-100 text-green-700'
                                        : 'bg-gray-100 text-gray-600',
                                ]"
                            >
                                {{ lastResult.action === 'clock_in' ? 'Clocked In' : 'Clocked Out' }}
                            </span>
                        </div>
                        <p :class="['text-sm', lastResult.success ? 'text-green-800' : 'text-red-800']">
                            {{ lastResult.message }}
                        </p>
                        <p class="text-xs text-gray-500 mt-0.5">{{ lastResult.time }}</p>
                    </div>
                </div>
            </Transition>

            <!-- Recent scans log -->
            <div v-if="scanHistory.length > 0" class="bg-white rounded-xl border border-gray-200 p-5">
                <h2 class="text-sm font-semibold text-gray-700 mb-3">Recent Scans</h2>
                <div class="space-y-2">
                    <div
                        v-for="(scan, i) in scanHistory"
                        :key="i"
                        class="flex items-center gap-3 py-2 border-b border-gray-50 last:border-0"
                    >
                        <div
                            :class="[
                                'w-2 h-2 rounded-full flex-shrink-0',
                                scan.success ? 'bg-green-500' : 'bg-red-400',
                            ]"
                        />
                        <div class="flex-1 min-w-0">
                            <span class="text-sm text-gray-700 font-medium">{{ scan.user?.name ?? 'Unknown' }}</span>
                            <span class="text-xs text-gray-400 ml-2">{{ scan.action === 'clock_in' ? '→ in' : '← out' }}</span>
                        </div>
                        <span class="text-xs text-gray-400 flex-shrink-0">{{ scan.time }}</span>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, onUnmounted } from 'vue';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';
import { CameraIcon, CheckCircleIcon, XCircleIcon } from '@heroicons/vue/24/outline';

const scanning    = ref(false);
const scanLoading = ref(false);
const manualId    = ref('');
const lastResult  = ref(null);
const scanHistory = ref([]);

let html5QrCode = null;

async function startScan() {
    // Dynamically import to avoid SSR issues
    const { Html5Qrcode } = await import('html5-qrcode');
    html5QrCode = new Html5Qrcode('qr-reader');

    scanning.value = true;

    try {
        await html5QrCode.start(
            { facingMode: 'environment' },
            { fps: 10, qrbox: { width: 220, height: 220 } },
            onScanSuccess,
            () => {} // ignore per-frame errors
        );
    } catch (err) {
        scanning.value = false;
        showResult({ success: false, message: `Camera error: ${err.message ?? 'Permission denied'}` });
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
    // Debounce: ignore same QR within 3 seconds
    if (scanCooldown || decodedText === lastScannedData) return;
    lastScannedData = decodedText;
    scanCooldown    = true;
    setTimeout(() => { scanCooldown = false; }, 3000);

    submitScan(decodedText);
}

function submitManual() {
    if (!manualId.value) return;
    const encoded = btoa(String(manualId.value));
    submitScan(encoded);
    manualId.value = '';
}

async function submitScan(scannedData) {
    scanLoading.value = true;

    try {
        const { data } = await axios.post('/attendance/scan', { scanned_data: scannedData });
        const result = {
            ...data,
            time: new Date().toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit', second: '2-digit' }),
        };
        lastResult.value = result;
        scanHistory.value.unshift(result);
        if (scanHistory.value.length > 10) scanHistory.value.pop();
    } catch (err) {
        const message = err.response?.data?.message ?? 'An error occurred.';
        lastResult.value = {
            success: false,
            message,
            time: new Date().toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit', second: '2-digit' }),
        };
    } finally {
        scanLoading.value = false;
    }
}

onUnmounted(async () => {
    await stopScan();
});
</script>
