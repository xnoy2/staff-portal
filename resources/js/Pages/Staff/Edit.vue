<template>
    <AppLayout :title="`Edit — ${staffMember.name}`">
        <div class="max-w-3xl mx-auto">
            <div class="mb-5 flex items-center justify-between">
                <div>
                    <h1 class="text-lg font-semibold text-gray-800">Edit Staff Member</h1>
                    <p class="text-xs text-gray-500 mt-0.5">{{ staffMember.name }}</p>
                </div>
                <Link :href="route('staff.show', staffMember.id)" class="text-sm text-[#EF233C] hover:underline">
                    View Profile →
                </Link>
            </div>
            <form @submit.prevent="submit">
                <UserForm :form="form" :roles="roles" submit-label="Save Changes" />
            </form>
        </div>
    </AppLayout>
</template>

<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import UserForm from './Partials/UserForm.vue';

const props = defineProps({
    staffMember: { type: Object, required: true },
    roles:       { type: Array,  required: true },
});

const form = useForm({
    name:                    props.staffMember.name,
    email:                   props.staffMember.email,
    role:                    props.staffMember.roles[0] ?? 'staff',
    is_active:               props.staffMember.is_active,
    must_change_password:    props.staffMember.must_change_password,
    hire_date:               props.staffMember.hire_date ?? '',
    emergency_contact_name:  props.staffMember.emergency_contact_name ?? '',
    emergency_contact_phone: props.staffMember.emergency_contact_phone ?? '',
    certifications:          [...(props.staffMember.certifications ?? [])],
    notes:                   props.staffMember.notes ?? '',
    annual_leave_days:       props.staffMember.annual_leave_days ?? 28,
    hourly_rate:             props.staffMember.hourly_rate ?? '',
    contracted_hours:        props.staffMember.contracted_hours ?? 40,
});

function submit() {
    form.put(route('staff.update', props.staffMember.id));
}
</script>
