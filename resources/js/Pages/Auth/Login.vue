<template>
    <Head title="Sign In" />

    <div class="min-h-screen flex">

        <!-- ── Left panel (branding) ─────────────────────────────── -->
        <div class="hidden lg:flex lg:w-1/2 bg-[#2B2D42] flex-col justify-between p-12 relative overflow-hidden">

            <!-- Background geometric shapes -->
            <div class="absolute inset-0 overflow-hidden pointer-events-none">
                <div class="absolute -top-24 -right-24 w-96 h-96 rounded-full bg-white/[0.03]" />
                <div class="absolute top-1/3 -left-16 w-64 h-64 rounded-full bg-[#EF233C]/10" />
                <div class="absolute -bottom-16 right-16 w-80 h-80 rounded-full bg-white/[0.03]" />
                <div class="absolute bottom-1/4 left-1/3 w-40 h-40 rounded-full bg-[#EF233C]/5" />
            </div>

            <!-- Logo + name -->
            <div class="relative z-10">
                <div class="flex items-center gap-3">
                    <AppLogo :size="44" />
                    <span class="text-white font-semibold text-lg tracking-tight">Staff Portal</span>
                </div>
            </div>

            <!-- Centre copy -->
            <div class="relative z-10">
                <h1 class="text-4xl font-bold text-white leading-tight mb-4">
                    Manage your team,<br />
                    <span class="text-[#EF233C]">effortlessly.</span>
                </h1>
                <p class="text-[#8D99AE] text-base leading-relaxed max-w-sm">
                    Track attendance, coordinate jobs, manage projects and keep your crew in sync — all in one place.
                </p>

                <!-- Feature pills -->
                <div class="flex flex-wrap gap-2 mt-8">
                    <span v-for="f in features" :key="f" class="inline-flex items-center gap-1.5 text-xs font-medium text-white/70 bg-white/10 border border-white/10 px-3 py-1.5 rounded-full">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#EF233C]" />
                        {{ f }}
                    </span>
                </div>
            </div>

            <!-- Footer note -->
            <div class="relative z-10">
                <p class="text-[#8D99AE] text-xs">© {{ year }} BCF. All rights reserved.</p>
            </div>
        </div>

        <!-- ── Right panel (form) ─────────────────────────────────── -->
        <div class="flex-1 flex flex-col items-center justify-center bg-[#EDF2F4] px-6 py-12">

            <!-- Mobile-only logo -->
            <div class="lg:hidden flex items-center gap-2.5 mb-10">
                <AppLogo :size="36" />
                <span class="text-[#2B2D42] font-semibold text-base">Staff Portal</span>
            </div>

            <div class="w-full max-w-sm">

                <!-- Heading -->
                <div class="mb-8">
                    <h2 class="text-2xl font-bold text-[#2B2D42]">Welcome back</h2>
                    <p class="text-sm text-gray-500 mt-1">Sign in to your account to continue.</p>
                </div>

                <!-- Status message (e.g. password reset success) -->
                <div v-if="status" class="mb-5 flex items-center gap-2.5 bg-green-50 border border-green-200 text-green-700 text-sm px-4 py-3 rounded-xl">
                    <CheckCircleIcon class="w-4 h-4 flex-shrink-0" />
                    {{ status }}
                </div>

                <!-- Error banner -->
                <div v-if="form.errors.email || form.errors.password" class="mb-5 flex items-start gap-2.5 bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-xl">
                    <ExclamationCircleIcon class="w-4 h-4 flex-shrink-0 mt-0.5" />
                    <span>{{ form.errors.email || form.errors.password }}</span>
                </div>

                <!-- Form -->
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
                                    'w-full pl-10 pr-4 py-2.5 text-sm rounded-xl border bg-white focus:outline-none focus:ring-2 transition-colors',
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
                                    'w-full pl-10 pr-10 py-2.5 text-sm rounded-xl border bg-white focus:outline-none focus:ring-2 transition-colors',
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
                        class="w-full mt-2 flex items-center justify-center gap-2 bg-[#EF233C] hover:bg-[#D90429] disabled:opacity-60 disabled:cursor-not-allowed text-white text-sm font-semibold py-2.5 rounded-xl transition-colors shadow-sm shadow-[#EF233C]/30"
                    >
                        <span v-if="form.processing" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                        <span>{{ form.processing ? 'Signing in…' : 'Sign in' }}</span>
                    </button>
                </form>

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
