<template>
    <div class="min-h-screen bg-[#EDF2F4] flex items-center justify-center p-4">
        <div class="w-full max-w-md">
            <!-- Logo -->
            <div class="text-center mb-8">
                <div class="inline-flex mb-3">
                    <AppLogo :size="64" />
                </div>
                <h1 class="text-2xl font-bold text-gray-800">Change Your Password</h1>
                <p class="text-sm text-gray-500 mt-1">Your administrator requires you to set a new password.</p>
            </div>

            <div class="bg-white rounded-2xl border border-gray-200 p-8 shadow-sm">
                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">New Password</label>
                        <input
                            v-model="form.password"
                            type="password"
                            autocomplete="new-password"
                            class="w-full rounded-lg border-gray-300 text-sm focus:ring-[#EF233C] focus:border-[#EF233C]"
                            placeholder="Min. 8 characters"
                        />
                        <p v-if="form.errors.password" class="mt-1 text-xs text-red-600">{{ form.errors.password }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Confirm Password</label>
                        <input
                            v-model="form.password_confirmation"
                            type="password"
                            autocomplete="new-password"
                            class="w-full rounded-lg border-gray-300 text-sm focus:ring-[#EF233C] focus:border-[#EF233C]"
                            placeholder="Repeat password"
                        />
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full bg-[#EF233C] hover:bg-[#D90429] text-white font-medium py-2.5 rounded-lg text-sm transition-colors disabled:opacity-60"
                    >
                        {{ form.processing ? 'Saving…' : 'Set New Password' }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLogo from '@/Components/AppLogo.vue';

const form = useForm({
    password: '',
    password_confirmation: '',
});

function submit() {
    form.post(route('password.change.update'), {
        onSuccess: () => form.reset(),
    });
}
</script>
