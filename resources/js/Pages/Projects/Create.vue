<template>
    <AppLayout title="New Project">
        <div class="max-w-3xl mx-auto">
            <div class="mb-5">
                <h1 class="text-lg font-semibold text-gray-800">New Project</h1>
                <p class="text-xs text-gray-500 mt-0.5">Create a new project.</p>
            </div>
            <form @submit.prevent="submit">
                <ProjectForm :form="form" :staff-list="staffList" :vans="vans" submit-label="Create Project" />
            </form>
        </div>
    </AppLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ProjectForm from './Partials/ProjectForm.vue';

defineProps({
    staffList: { type: Array, default: () => [] },
    vans:      { type: Array, default: () => [] },
});

const form = useForm({
    business:     'bcf',
    name:         '',
    customer:     '',
    address:      '',
    status:       'planning',
    phase:        'planning',
    start_date:   '',
    end_date:     '',
    budget:       '',
    budget_spent: '0',
    van_id:       null,
    staff_ids:    [],
    staff_roles:  {},
    notes:        '',
});

function submit() {
    form.post(route('projects.store'));
}
</script>
