<template>
    <AppLayout title="Certificate">
        <div class="max-w-4xl mx-auto">

            <!-- Toolbar (hidden when printing) -->
            <div class="flex items-center justify-between mb-4 cert-toolbar">
                <Link :href="route('training.index')" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700">
                    <ArrowLeftIcon class="w-4 h-4" /> Training
                </Link>
                <button @click="printCert" class="inline-flex items-center gap-1.5 bg-[#2B2D42] hover:bg-[#EF233C] text-white text-sm px-4 py-2 rounded-lg transition-colors">
                    <ArrowDownTrayIcon class="w-4 h-4" /> Print / Download PDF
                </button>
            </div>

            <!-- Certificate -->
            <div id="cert" class="cert">
                <div class="cert-inner">
                    <svg class="cert-hex" viewBox="0 0 100 100" aria-hidden="true">
                        <polygon points="50,4 92,27 92,73 50,96 8,73 8,27" fill="none" stroke="#ef233c" stroke-width="1.4" />
                    </svg>

                    <svg class="cert-logo" viewBox="195 57 290 290" xmlns="http://www.w3.org/2000/svg" aria-label="Staff Portal">
                        <polygon points="340,68 459,135 459,269 340,336 221,269 221,135" fill="#c0182a" stroke="#e8203a" stroke-width="6" stroke-linejoin="round"/>
                        <circle cx="340" cy="155" r="26" fill="#e8182e"/><circle cx="340" cy="155" r="22" fill="#f03048"/>
                        <path d="M260,248 Q252,210 290,196 Q308,190 325,200" fill="none" stroke="#f03048" stroke-width="14" stroke-linecap="round"/>
                        <path d="M420,248 Q428,210 390,196 Q372,190 355,200" fill="none" stroke="#f03048" stroke-width="14" stroke-linecap="round"/>
                        <path d="M325,200 Q340,210 355,200 L358,240 Q340,252 322,240 Z" fill="#e8182e"/>
                        <path d="M265,248 Q340,210 415,248" fill="none" stroke="#c8d0e0" stroke-width="3" opacity="0.9"/>
                        <circle cx="265" cy="248" r="10" fill="#fff"/><circle cx="265" cy="248" r="6" fill="#e8182e"/>
                        <circle cx="340" cy="218" r="10" fill="#fff"/><circle cx="340" cy="218" r="6" fill="#e8182e"/>
                        <circle cx="415" cy="248" r="10" fill="#fff"/><circle cx="415" cy="248" r="6" fill="#e8182e"/>
                    </svg>

                    <p class="cert-eyebrow">Ballycastle Climbing Frames</p>
                    <h1 class="cert-title">Certificate of Completion</h1>

                    <p class="cert-pre">This is to certify that</p>
                    <p class="cert-name">{{ certificate.name }}</p>

                    <p class="cert-body">has successfully completed the training module</p>
                    <p class="cert-module">“{{ certificate.module }}”</p>

                    <p class="cert-meta">
                        {{ certificate.lesson_count }} lesson{{ certificate.lesson_count === 1 ? '' : 's' }}
                        <span class="cert-dot">·</span>
                        Completed {{ certificate.completed_at }}
                    </p>

                    <div class="cert-foot">
                        <div class="cert-issuer">
                            <div class="cert-rule"></div>
                            <p class="cert-issuer-name">Bespoke Garden Rooms</p>
                            <p class="cert-issuer-sub">Ballycastle Climbing Frames</p>
                        </div>
                        <div class="cert-ref">
                            <p class="cert-ref-label">Certificate No.</p>
                            <p class="cert-ref-num">{{ certificate.reference }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ArrowLeftIcon, ArrowDownTrayIcon } from '@heroicons/vue/24/outline';

defineProps({ certificate: { type: Object, required: true } });

function printCert() { window.print(); }
</script>

<style scoped>
.cert{
    background:#fff;
    border-radius:14px;
    box-shadow:0 18px 50px rgba(20,22,34,.14);
    padding:14px;
}
.cert-inner{
    position:relative;
    overflow:hidden;
    border:2px solid #2b2d42;
    outline:1px solid #ef233c;
    outline-offset:5px;
    border-radius:6px;
    padding:52px 48px 40px;
    text-align:center;
    color:#1f2230;
    font-family:"Segoe UI","Helvetica Neue",Arial,sans-serif;
}
.cert-hex{ position:absolute; width:520px; height:520px; left:50%; top:52%; transform:translate(-50%,-50%); opacity:.05; pointer-events:none; }
.cert-logo{ width:74px; height:74px; position:relative; }
.cert-eyebrow{ margin:14px 0 0; font-size:12px; letter-spacing:.26em; text-transform:uppercase; color:#8d99ae; font-weight:600; }
.cert-title{ margin:6px 0 26px; font-family:Georgia,"Times New Roman",serif; font-size:34px; font-weight:700; color:#2b2d42; letter-spacing:.5px; }
.cert-pre{ margin:0; color:#5b6172; font-size:14px; }
.cert-name{ margin:8px 0 6px; font-family:Georgia,"Times New Roman",serif; font-size:38px; font-weight:700; color:#111827; line-height:1.1; }
.cert-body{ margin:14px 0 4px; color:#5b6172; font-size:14px; }
.cert-module{ margin:0 0 20px; font-size:20px; font-weight:700; color:#ef233c; }
.cert-meta{ margin:0; color:#5b6172; font-size:13.5px; }
.cert-dot{ color:#c7ccda; margin:0 6px; }
.cert-foot{ display:flex; align-items:flex-end; justify-content:space-between; gap:24px; margin-top:44px; text-align:left; }
.cert-issuer-name{ margin:0; font-weight:700; font-size:13.5px; color:#2b2d42; }
.cert-issuer-sub{ margin:1px 0 0; font-size:12px; color:#8d99ae; }
.cert-rule{ width:190px; border-top:1.5px solid #2b2d42; margin-bottom:8px; }
.cert-ref{ text-align:right; }
.cert-ref-label{ margin:0; font-size:10.5px; letter-spacing:.12em; text-transform:uppercase; color:#8d99ae; font-weight:700; }
.cert-ref-num{ margin:2px 0 0; font-family:"SF Mono",Consolas,monospace; font-size:14px; font-weight:700; color:#2b2d42; letter-spacing:1px; }

@media (min-width:640px){ .cert-inner{ padding:64px 72px 48px; } }
</style>

<style>
/* Print: show only the certificate, landscape A4 */
@media print{
    .cert-toolbar{ display:none !important; }
    body * { visibility:hidden; }
    #cert, #cert *{ visibility:visible; }
    #cert{ position:absolute; left:0; top:0; width:100%; box-shadow:none; padding:0; }
    #cert .cert-inner{ border-radius:0; }
    @page{ size:A4 landscape; margin:14mm; }
}
</style>
