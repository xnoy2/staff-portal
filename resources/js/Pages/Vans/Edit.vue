<template>
    <AppLayout :title="`Edit — ${van.registration}`">
        <div class="max-w-2xl mx-auto">
            <div class="mb-5 flex items-center justify-between">
                <div>
                    <h1 class="text-lg font-semibold text-gray-800">Edit Van</h1>
                    <p class="text-xs text-gray-500 mt-0.5">{{ van.registration }} — {{ van.make }} {{ van.model }}</p>
                </div>
                <Link :href="route('vans.show', van.id)" class="text-sm text-[#EF233C] hover:underline">
                    View Van →
                </Link>
            </div>
            <form @submit.prevent="submit">
                <VanForm :form="form" submit-label="Save Changes" />
            </form>
        </div>
    </AppLayout>
</template>

<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import VanForm from './Partials/VanForm.vue';

const props = defineProps({
    van: { type: Object, required: true },
});

const form = useForm({
    registration: props.van.registration,
    make:         props.van.make,
    model:        props.van.model,
    year:         props.van.year ?? null,
    notes:        props.van.notes ?? '',
});

function submit() {
    form.put(route('vans.update', props.van.id));
}
</script>
