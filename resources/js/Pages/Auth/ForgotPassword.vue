<template>
    <Head title="Forgot Password" />

    <div class="min-h-screen relative overflow-hidden" style="background:#0f1120">

        <!-- Ambient orbs -->
        <div class="absolute inset-0 pointer-events-none overflow-hidden" aria-hidden="true">
            <div class="orb-red-top" />
            <div class="orb-red-bottom" />
            <div class="orb-neutral" />
        </div>

        <ParticleBackground />

        <div class="relative z-10 min-h-screen flex items-center justify-center px-6 py-12">
            <div class="w-full max-w-sm">

                <!-- Logo -->
                <div class="flex items-center justify-center gap-2.5 mb-8">
                    <AppLogo :size="36" />
                    <span class="text-white font-semibold text-base tracking-tight">Staff Portal</span>
                </div>

                <!-- Card -->
                <div class="auth-card rounded-2xl overflow-hidden">
                    <div class="p-8">

                        <!-- Success state -->
                        <div v-if="status" class="text-center">
                            <div class="w-14 h-14 rounded-full bg-green-500/10 border border-green-500/20 flex items-center justify-center mx-auto mb-4">
                                <CheckCircleIcon class="w-7 h-7 text-green-400" />
                            </div>
                            <h2 class="text-xl font-bold text-white mb-2">Check your email</h2>
                            <p class="text-sm text-white/50 mb-6 leading-relaxed">{{ status }}</p>
                            <Link
                                :href="route('login')"
                                class="inline-flex items-center gap-1.5 text-sm text-[#EF233C] hover:text-[#ff4d65] font-medium transition-colors"
                            >
                                <ArrowLeftIcon class="w-3.5 h-3.5" />
                                Back to sign in
                            </Link>
                        </div>

                        <!-- Form state -->
                        <template v-else>
                            <div class="mb-6">
                                <h2 class="text-2xl font-bold text-white">Forgot password?</h2>
                                <p class="text-sm text-white/50 mt-1.5 leading-relaxed">
                                    Enter your email and we'll send you a reset link.
                                </p>
                            </div>

                            <form @submit.prevent="submit" class="space-y-4">

                                <!-- Email -->
                                <div>
                                    <label for="email" class="block text-sm font-medium text-white/70 mb-1.5">
                                        Email address
                                    </label>
                                    <div class="relative">
                                        <EnvelopeIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-white/30 pointer-events-none" />
                                        <input
                                            id="email"
                                            v-model="form.email"
                                            type="email"
                                            autocomplete="username"
                                            autofocus
                                            required
                                            placeholder="you@example.com"
                                            :class="[
                                                'w-full pl-10 pr-4 py-2.5 text-sm rounded-xl border bg-white/5 text-white placeholder-white/25 focus:outline-none focus:ring-2 transition-colors',
                                                form.errors.email
                                                    ? 'border-red-500/50 focus:ring-red-500/20 focus:border-red-500/60'
                                                    : 'border-white/10 focus:ring-[#EF233C]/25 focus:border-[#EF233C]/60',
                                            ]"
                                        />
                                    </div>
                                    <p v-if="form.errors.email" class="mt-1.5 text-xs text-red-400">
                                        {{ form.errors.email }}
                                    </p>
                                </div>

                                <!-- Submit -->
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="submit-btn w-full mt-2 flex items-center justify-center gap-2 text-white text-sm font-semibold py-2.5 rounded-xl transition-all disabled:opacity-60 disabled:cursor-not-allowed"
                                >
                                    <span v-if="form.processing" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                                    <span>{{ form.processing ? 'Sending…' : 'Send reset link' }}</span>
                                </button>

                            </form>

                            <div class="mt-6 text-center">
                                <Link
                                    :href="route('login')"
                                    class="inline-flex items-center gap-1.5 text-sm text-white/40 hover:text-white/70 transition-colors"
                                >
                                    <ArrowLeftIcon class="w-3.5 h-3.5" />
                                    Back to sign in
                                </Link>
                            </div>
                        </template>

                    </div>
                </div>

            </div>
        </div>
    </div>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { EnvelopeIcon, CheckCircleIcon, ArrowLeftIcon } from '@heroicons/vue/24/outline';
import AppLogo from '@/Components/AppLogo.vue';
import ParticleBackground from '@/Components/ParticleBackground.vue';

defineProps({
    status: { type: String },
});

const form = useForm({ email: '' });

const submit = () => form.post(route('password.email'));
</script>

<style scoped>
.orb-red-top {
    position: absolute;
    top: -160px; left: -80px;
    width: 520px; height: 520px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(239,35,60,0.28) 0%, transparent 70%);
    filter: blur(90px);
}
.orb-red-bottom {
    position: absolute;
    bottom: -60px; right: 5%;
    width: 360px; height: 360px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(239,35,60,0.15) 0%, transparent 70%);
    filter: blur(70px);
}
.orb-neutral {
    position: absolute;
    top: 40%; left: 52%;
    transform: translate(-50%, -50%);
    width: 440px; height: 440px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(43,45,66,0.9) 0%, transparent 70%);
    filter: blur(60px);
}
.auth-card {
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.08);
    box-shadow:
        0 0 0 1px rgba(239,35,60,0.08),
        0 0 40px rgba(239,35,60,0.10),
        0 20px 50px rgba(0,0,0,0.50);
    animation: card-in 0.55s cubic-bezier(0.16,1,0.3,1) both;
}
@keyframes card-in {
    from { opacity: 0; transform: translateY(18px); }
    to   { opacity: 1; transform: translateY(0); }
}
.submit-btn {
    background: linear-gradient(135deg, #EF233C 0%, #c41430 100%);
    box-shadow: 0 4px 22px rgba(239,35,60,0.38), inset 0 1px 0 rgba(255,255,255,0.14);
}
.submit-btn:not(:disabled):hover {
    background: linear-gradient(135deg, #f5354e 0%, #d4163b 100%);
    box-shadow: 0 6px 30px rgba(239,35,60,0.52), inset 0 1px 0 rgba(255,255,255,0.14);
    transform: translateY(-1px);
}
.submit-btn:not(:disabled):active {
    transform: translateY(0);
    box-shadow: 0 2px 12px rgba(239,35,60,0.35), inset 0 1px 0 rgba(255,255,255,0.14);
}
</style>
