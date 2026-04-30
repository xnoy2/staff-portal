<template>
    <AppLayout :title="`Edit — ${project.name}`">
        <div class="max-w-3xl mx-auto">
            <div class="mb-5 flex items-center justify-between">
                <div>
                    <h1 class="text-lg font-semibold text-gray-800">Edit Project</h1>
                    <p class="text-xs text-gray-500 mt-0.5">{{ project.name }}</p>
                </div>
                <Link :href="route('projects.show', project.id)" class="text-sm text-[#EF233C] hover:underline">
                    View Project →
                </Link>
            </div>
            <form @submit.prevent="submit">
                <ProjectForm :form="form" :staff-list="staffList" :vans="vans" :businesses="businesses" submit-label="Save Changes" />
            </form>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ProjectForm from './Partials/ProjectForm.vue';

const props = defineProps({
    project:    { type: Object, required: true },
    staffList:  { type: Array,  default: () => [] },
    vans:       { type: Array,  default: () => [] },
    businesses: { type: Array,  default: () => [] },
});

const businesses = computed(() => props.businesses);

const initialRoles = Object.fromEntries(
    props.project.staff.map(s => [s.id, s.role])
);

const form = useForm({
    business:     props.project.business ?? 'bcf',
    name:         props.project.name,
    customer:     props.project.customer,
    address:      props.project.address ?? '',
    status:       props.project.status,
    phase:        props.project.phase,
    start_date:   props.project.start_date ?? '',
    end_date:     props.project.end_date ?? '',
    budget:       props.project.budget ?? '',
    budget_spent: props.project.budget_spent ?? '0',
    van_id:       props.project.van?.id ?? null,
    staff_ids:    props.project.staff.map(s => s.id),
    staff_roles:  initialRoles,
    notes:        props.project.notes ?? '',
});

function submit() {
    form.put(route('projects.update', props.project.id));
}
</script>
