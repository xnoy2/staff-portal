<template>
    <AppLayout title="Add Staff Member">
        <div class="max-w-3xl mx-auto">
            <div class="mb-5">
                <h1 class="text-lg font-semibold text-gray-800">Add Staff Member</h1>
                <p class="text-xs text-gray-500 mt-0.5">Create a new team member account.</p>
            </div>
            <form @submit.prevent="submit">
                <UserForm :form="form" :roles="roles" :is-create="true" submit-label="Create Staff Member" />
            </form>
        </div>
    </AppLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import UserForm from './Partials/UserForm.vue';

const props = defineProps({
    roles: { type: Array, required: true },
});

const form = useForm({
    name:                    '',
    email:                   '',
    role:                    'staff',
    is_active:               true,
    hire_date:               '',
    emergency_contact_name:  '',
    emergency_contact_phone: '',
    certifications:          [],
    notes:                   '',
    annual_leave_days:       28,
    hourly_rate:             '',
});

function submit() {
    form.post(route('staff.store'));
}
</script>
