<template>
    <AppLayout title="Add Van">
        <div class="max-w-2xl mx-auto">
            <div class="mb-5 flex items-center justify-between">
                <div>
                    <h1 class="text-lg font-semibold text-gray-800">Add Van</h1>
                    <p class="text-xs text-gray-500 mt-0.5">Register a new vehicle in the fleet.</p>
                </div>
                <Link :href="route('vans.index')" class="text-sm text-[#EF233C] hover:underline">
                    ← Back to Vans
                </Link>
            </div>
            <form @submit.prevent="submit">
                <VanForm :form="form" submit-label="Add Van" />
            </form>
        </div>
    </AppLayout>
</template>

<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import VanForm from './Partials/VanForm.vue';

const form = useForm({
    registration: '',
    make:         '',
    model:        '',
    year:         null,
    notes:        '',
});

function submit() {
    form.post(route('vans.store'));
}
</script>
