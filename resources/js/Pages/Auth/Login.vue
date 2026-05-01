<template>
    <Head title="Sign In" />

    <div class="min-h-screen relative overflow-hidden" style="background:#0f1120">

        <!-- ── Ambient gradient orbs ─────────────────────────────────── -->
        <div class="absolute inset-0 pointer-events-none overflow-hidden" aria-hidden="true">
            <div class="orb-red-top" />
            <div class="orb-red-bottom" />
            <div class="orb-neutral" />
        </div>

        <!-- ── Particle canvas ───────────────────────────────────────── -->
        <ParticleBackground />

        <!-- ── Centred two-column content ────────────────────────────── -->
        <div class="relative z-10 min-h-screen flex items-stretch justify-center">
            <div class="w-full max-w-[1200px] flex">

                <!-- Left panel (desktop) -->
                <div class="hidden lg:flex flex-col justify-between py-16 px-14 w-1/2 min-h-screen left-panel">

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
                        <p class="text-white/60 text-[0.95rem] leading-relaxed max-w-xs">
                            Track attendance, coordinate jobs, manage projects and keep your crew in sync — all in one place.
                        </p>

                        <div class="mt-10 space-y-4">
                            <div v-for="f in features" :key="f" class="flex items-center gap-3.5">
                                <div class="feature-check w-6 h-6 rounded-full flex items-center justify-center flex-shrink-0">
                                    <svg class="w-3 h-3 text-[#EF233C]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <span class="text-sm text-white/65">{{ f }}</span>
                            </div>
                        </div>
                    </div>

                    <p class="text-white/30 text-xs">© {{ year }} BCF. All rights reserved.</p>
                </div>

                <!-- Right panel -->
                <div class="flex-1 lg:w-1/2 flex flex-col items-center justify-center px-6 py-12">

                    <!-- Mobile logo -->
                    <div class="lg:hidden flex items-center gap-2.5 mb-10">
                        <AppLogo :size="36" />
                        <span class="text-white font-semibold text-base">Staff Portal</span>
                    </div>

                    <!-- ── White card ──────────────────────────────────── -->
                    <div class="login-card w-full max-w-sm rounded-2xl overflow-hidden">

                        <div class="p-8">

                            <div class="mb-7">
                                <h2 class="text-2xl font-bold text-[#2B2D42]">Welcome back</h2>
                                <p class="text-sm text-gray-500 mt-1">Sign in to your account to continue.</p>
                            </div>

                            <!-- Status -->
                            <div v-if="status" class="mb-5 flex items-center gap-2.5 bg-green-50 border border-green-200 text-green-700 text-sm px-4 py-3 rounded-xl">
                                <CheckCircleIcon class="w-4 h-4 flex-shrink-0" />
                                {{ status }}
                            </div>

                            <!-- Error -->
                            <div v-if="form.errors.email || form.errors.password" class="mb-5 flex items-start gap-2.5 bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-xl">
                                <ExclamationCircleIcon class="w-4 h-4 flex-shrink-0 mt-0.5" />
                                <span>{{ form.errors.email || form.errors.password }}</span>
                            </div>

                            <form @submit.prevent="submit" class="space-y-4">

                                <!-- Email -->
                                <div>
                                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">Email address</label>
                                    <div class="relative">
                                        <EnvelopeIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" />
                                        <input
                                            id="email"
                                            v-model="form.email"
                                            type="email"
                                            autocomplete="username"
                                            autofocus
                                            required
                                            placeholder="you@example.com"
                                            :class="[
                                                'w-full pl-10 pr-4 py-2.5 text-sm rounded-xl border bg-white text-gray-800 focus:outline-none focus:ring-2 transition-colors',
                                                form.errors.email
                                                    ? 'border-red-300 focus:ring-red-200 focus:border-red-400'
                                                    : 'border-gray-200 focus:ring-[#EF233C]/20 focus:border-[#EF233C]',
                                            ]"
                                        />
                                    </div>
                                </div>

                                <!-- Password -->
                                <div>
                                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1.5">Password</label>
                                    <div class="relative">
                                        <LockClosedIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" />
                                        <input
                                            id="password"
                                            v-model="form.password"
                                            :type="showPassword ? 'text' : 'password'"
                                            autocomplete="current-password"
                                            required
                                            placeholder="••••••••"
                                            :class="[
                                                'w-full pl-10 pr-10 py-2.5 text-sm rounded-xl border bg-white text-gray-800 focus:outline-none focus:ring-2 transition-colors',
                                                form.errors.password
                                                    ? 'border-red-300 focus:ring-red-200 focus:border-red-400'
                                                    : 'border-gray-200 focus:ring-[#EF233C]/20 focus:border-[#EF233C]',
                                            ]"
                                        />
                                        <button
                                            type="button"
                                            @click="showPassword = !showPassword"
                                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors"
                                            tabindex="-1"
                                        >
                                            <EyeSlashIcon v-if="showPassword" class="w-4 h-4" />
                                            <EyeIcon v-else class="w-4 h-4" />
                                        </button>
                                    </div>
                                </div>

                                <!-- Remember + forgot -->
                                <div class="flex items-center justify-between pt-1">
                                    <label class="flex items-center gap-2 cursor-pointer group">
                                        <input
                                            type="checkbox"
                                            v-model="form.remember"
                                            class="rounded border-gray-300 text-[#EF233C] focus:ring-[#EF233C] w-3.5 h-3.5"
                                        />
                                        <span class="text-sm text-gray-600 group-hover:text-gray-800 transition-colors">Remember me</span>
                                    </label>
                                    <Link
                                        v-if="canResetPassword"
                                        :href="route('password.request')"
                                        class="text-sm text-[#EF233C] hover:text-[#D90429] font-medium transition-colors"
                                    >
                                        Forgot password?
                                    </Link>
                                </div>

                                <!-- Submit -->
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="submit-btn w-full mt-2 flex items-center justify-center gap-2 text-white text-sm font-semibold py-2.5 rounded-xl transition-all disabled:opacity-60 disabled:cursor-not-allowed"
                                >
                                    <span v-if="form.processing" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                                    <span>{{ form.processing ? 'Signing in…' : 'Sign in' }}</span>
                                </button>
                            </form>
                        </div>
                    </div>
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

/* ── Feature check icon ──────────────────────────────────────────────────── */
.feature-check {
    background: rgba(239, 35, 60, 0.12);
    border: 1px solid rgba(239, 35, 60, 0.24);
}

/* ── White card ──────────────────────────────────────────────────────────── */
.login-card {
    background: #ffffff;
    box-shadow:
        0 0 0 1px rgba(239, 35, 60, 0.10),
        0 0 40px rgba(239, 35, 60, 0.14),
        0 20px 50px rgba(0, 0, 0, 0.40);
    animation: card-in 0.55s cubic-bezier(0.16, 1, 0.3, 1) both;
}

@keyframes card-in {
    from { opacity: 0; transform: translateY(18px); }
    to   { opacity: 1; transform: translateY(0); }
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
