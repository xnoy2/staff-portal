<template>
    <Head title="Sign In" />

    <div class="min-h-screen flex relative overflow-hidden" style="background:#0f1120">

        <!-- ── Ambient gradient orbs ─────────────────────────────────── -->
        <div class="absolute inset-0 pointer-events-none overflow-hidden" aria-hidden="true">
            <div class="orb-red-top" />
            <div class="orb-red-bottom" />
            <div class="orb-neutral" />
        </div>

        <!-- ── Particle canvas ───────────────────────────────────────── -->
        <ParticleBackground />

        <!-- ── Left panel (desktop) ──────────────────────────────────── -->
        <div class="hidden lg:flex lg:w-[48%] flex-col justify-between p-14 relative z-10 left-panel">

            <div class="flex items-center gap-3">
                <AppLogo :size="44" />
                <span class="text-white font-semibold text-lg tracking-tight">Staff Portal</span>
            </div>

            <div>
                <p class="text-[#EF233C] text-[11px] font-bold tracking-[0.18em] uppercase mb-5">BCF Management System</p>
                <h1 class="text-[2.8rem] font-bold text-white leading-[1.15] mb-5">
                    Manage your team,<br />
                    <span class="text-transparent bg-clip-text bg-gradient-to-br from-[#EF233C] to-[#ff8585]">effortlessly.</span>
                </h1>
                <p class="text-white/45 text-[0.95rem] leading-relaxed max-w-xs">
                    Track attendance, coordinate jobs, manage projects and keep your crew in sync — all in one place.
                </p>

                <div class="mt-10 space-y-4">
                    <div v-for="f in features" :key="f" class="flex items-center gap-3.5">
                        <div class="feature-check w-6 h-6 rounded-full flex items-center justify-center flex-shrink-0">
                            <svg class="w-3 h-3 text-[#EF233C]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <span class="text-sm text-white/55">{{ f }}</span>
                    </div>
                </div>
            </div>

            <p class="text-white/25 text-xs">© {{ year }} BCF. All rights reserved.</p>
        </div>

        <!-- ── Right panel ───────────────────────────────────────────── -->
        <div class="flex-1 flex flex-col items-center justify-center px-6 py-12 relative z-10">

            <!-- Mobile logo -->
            <div class="lg:hidden flex items-center gap-2.5 mb-10">
                <AppLogo :size="36" />
                <span class="text-white font-semibold text-base">Staff Portal</span>
            </div>

            <!-- ── Glass card ──────────────────────────────────────── -->
            <div class="login-card w-full max-w-sm rounded-3xl overflow-hidden">

                <!-- Top accent line -->
                <div class="h-px" style="background:linear-gradient(90deg,transparent,rgba(239,35,60,0.65),transparent)" />

                <div class="px-8 pt-8 pb-9">

                    <div class="mb-7">
                        <h2 class="text-[1.6rem] font-bold text-white leading-tight">Welcome back</h2>
                        <p class="text-sm text-white/38 mt-1">Sign in to your account to continue.</p>
                    </div>

                    <!-- Status -->
                    <div v-if="status" class="mb-5 flex items-center gap-2.5 rounded-xl px-4 py-3 text-sm status-success">
                        <CheckCircleIcon class="w-4 h-4 flex-shrink-0" />
                        {{ status }}
                    </div>

                    <!-- Error -->
                    <div v-if="form.errors.email || form.errors.password" class="mb-5 flex items-start gap-2.5 rounded-xl px-4 py-3 text-sm status-error">
                        <ExclamationCircleIcon class="w-4 h-4 flex-shrink-0 mt-0.5" />
                        <span>{{ form.errors.email || form.errors.password }}</span>
                    </div>

                    <form @submit.prevent="submit" class="space-y-5">

                        <!-- Email -->
                        <div>
                            <label for="email" class="field-label block mb-2">Email address</label>
                            <div class="relative">
                                <EnvelopeIcon class="field-icon absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 pointer-events-none" />
                                <input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    autocomplete="username"
                                    autofocus
                                    required
                                    placeholder="you@example.com"
                                    :class="['glass-input w-full pl-10 pr-4 py-3 text-sm text-white rounded-xl focus:outline-none transition-all', form.errors.email ? 'input-err' : 'input-base']"
                                />
                            </div>
                        </div>

                        <!-- Password -->
                        <div>
                            <label for="password" class="field-label block mb-2">Password</label>
                            <div class="relative">
                                <LockClosedIcon class="field-icon absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 pointer-events-none" />
                                <input
                                    id="password"
                                    v-model="form.password"
                                    :type="showPassword ? 'text' : 'password'"
                                    autocomplete="current-password"
                                    required
                                    placeholder="••••••••"
                                    :class="['glass-input w-full pl-10 pr-10 py-3 text-sm text-white rounded-xl focus:outline-none transition-all', form.errors.password ? 'input-err' : 'input-base']"
                                />
                                <button
                                    type="button"
                                    @click="showPassword = !showPassword"
                                    class="eye-btn absolute right-3.5 top-1/2 -translate-y-1/2 transition-colors"
                                    tabindex="-1"
                                >
                                    <EyeSlashIcon v-if="showPassword" class="w-4 h-4" />
                                    <EyeIcon v-else class="w-4 h-4" />
                                </button>
                            </div>
                        </div>

                        <!-- Remember + forgot -->
                        <div class="flex items-center justify-between">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input
                                    type="checkbox"
                                    v-model="form.remember"
                                    class="rounded border-white/20 bg-white/10 text-[#EF233C] focus:ring-[#EF233C] w-3.5 h-3.5"
                                />
                                <span class="text-sm text-white/38">Remember me</span>
                            </label>
                            <Link
                                v-if="canResetPassword"
                                :href="route('password.request')"
                                class="forgot-link text-sm font-medium transition-colors"
                            >
                                Forgot password?
                            </Link>
                        </div>

                        <!-- Submit -->
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="submit-btn w-full flex items-center justify-center gap-2 text-white text-sm font-semibold py-3 rounded-xl transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <span v-if="form.processing" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                            <span>{{ form.processing ? 'Signing in…' : 'Sign in' }}</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    EnvelopeIcon, LockClosedIcon, EyeIcon, EyeSlashIcon,
    CheckCircleIcon, ExclamationCircleIcon,
} from '@heroicons/vue/24/outline';
import AppLogo from '@/Components/AppLogo.vue';
import ParticleBackground from '@/Components/ParticleBackground.vue';

defineProps({
    canResetPassword: { type: Boolean },
    status:           { type: String },
});

const form = useForm({
    email:    '',
    password: '',
    remember: false,
});

const showPassword = ref(false);
const year = computed(() => new Date().getFullYear());

const features = ['Attendance Tracking', 'Live Job Board', 'Project Management', 'Leave Requests', 'Fleet Management'];

function submit() {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
}
</script>

<style scoped>
/* ── Ambient orbs ─────────────────────────────────────────────────────────── */
.orb-red-top {
    position: absolute;
    top: -160px;
    left: -80px;
    width: 520px;
    height: 520px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(239,35,60,0.32) 0%, transparent 70%);
    filter: blur(90px);
}

.orb-red-bottom {
    position: absolute;
    bottom: -60px;
    right: 5%;
    width: 360px;
    height: 360px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(239,35,60,0.18) 0%, transparent 70%);
    filter: blur(70px);
}

.orb-neutral {
    position: absolute;
    top: 40%;
    left: 52%;
    transform: translate(-50%, -50%);
    width: 440px;
    height: 440px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(43,45,66,0.9) 0%, transparent 70%);
    filter: blur(60px);
}

/* ── Left panel entry animation ──────────────────────────────────────────── */
.left-panel {
    animation: panel-in 0.7s cubic-bezier(0.16, 1, 0.3, 1) 0.1s both;
}

@keyframes panel-in {
    from { opacity: 0; transform: translateX(-14px); }
    to   { opacity: 1; transform: translateX(0); }
}

/* ── Feature check icon ───────────────────────────────────────────────────── */
.feature-check {
    background: rgba(239, 35, 60, 0.12);
    border: 1px solid rgba(239, 35, 60, 0.24);
}

/* ── Glass card ──────────────────────────────────────────────────────────── */
.login-card {
    background: rgba(255, 255, 255, 0.046);
    backdrop-filter: blur(36px) saturate(1.5);
    -webkit-backdrop-filter: blur(36px) saturate(1.5);
    border: 1px solid rgba(255, 255, 255, 0.085);
    box-shadow:
        0 0 0 1px rgba(239, 35, 60, 0.06),
        0 0 90px rgba(239, 35, 60, 0.07),
        0 40px 80px rgba(0, 0, 0, 0.55),
        inset 0 1px 0 rgba(255, 255, 255, 0.07);
    animation: card-in 0.55s cubic-bezier(0.16, 1, 0.3, 1) both;
}

@keyframes card-in {
    from { opacity: 0; transform: translateY(18px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* ── Status banners ──────────────────────────────────────────────────────── */
.status-success {
    background: rgba(16, 185, 129, 0.10);
    border: 1px solid rgba(16, 185, 129, 0.20);
    color: rgba(52, 211, 153, 1);
}

.status-error {
    background: rgba(239, 35, 60, 0.08);
    border: 1px solid rgba(239, 35, 60, 0.20);
    color: rgba(252, 129, 129, 1);
}

/* ── Form field helpers ───────────────────────────────────────────────────── */
.field-label {
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.07em;
    text-transform: uppercase;
    color: rgba(255, 255, 255, 0.38);
}

.field-icon {
    color: rgba(255, 255, 255, 0.24);
}

/* ── Inputs ───────────────────────────────────────────────────────────────── */
.glass-input {
    background: rgba(255, 255, 255, 0.065);
    caret-color: #EF233C;
}

.glass-input::placeholder {
    color: rgba(255, 255, 255, 0.20);
}

.input-base {
    border: 1px solid rgba(255, 255, 255, 0.10);
}

.input-base:focus {
    border-color: rgba(239, 35, 60, 0.55);
    box-shadow: 0 0 0 3px rgba(239, 35, 60, 0.13);
}

.input-err {
    border: 1px solid rgba(239, 35, 60, 0.50);
    box-shadow: 0 0 0 3px rgba(239, 35, 60, 0.12);
}

/* ── Eye button ──────────────────────────────────────────────────────────── */
.eye-btn {
    color: rgba(255, 255, 255, 0.28);
}

.eye-btn:hover {
    color: rgba(255, 255, 255, 0.60);
}

/* ── Forgot link ─────────────────────────────────────────────────────────── */
.forgot-link {
    color: rgba(239, 35, 60, 0.70);
}

.forgot-link:hover {
    color: rgba(239, 35, 60, 1);
}

/* ── Submit button ───────────────────────────────────────────────────────── */
.submit-btn {
    background: linear-gradient(135deg, #EF233C 0%, #c41430 100%);
    box-shadow:
        0 4px 22px rgba(239, 35, 60, 0.38),
        inset 0 1px 0 rgba(255, 255, 255, 0.14);
}

.submit-btn:not(:disabled):hover {
    background: linear-gradient(135deg, #f5354e 0%, #d4163b 100%);
    box-shadow:
        0 6px 30px rgba(239, 35, 60, 0.52),
        inset 0 1px 0 rgba(255, 255, 255, 0.14);
    transform: translateY(-1px);
}

.submit-btn:not(:disabled):active {
    transform: translateY(0);
    box-shadow:
        0 2px 12px rgba(239, 35, 60, 0.35),
        inset 0 1px 0 rgba(255, 255, 255, 0.14);
}
</style>
