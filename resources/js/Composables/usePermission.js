import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

export function usePermission() {
    const page = usePage();

    const roles = computed(() => page.props.auth?.user?.roles ?? []);
    const permissions = computed(() => page.props.permissions ?? []);

    const hasRole = (...roleNames) =>
        roleNames.some((r) => roles.value.includes(r));

    const hasPermission = (permission) =>
        permissions.value.includes(permission);

    const isAdmin    = computed(() => hasRole('admin'));
    const isManager  = computed(() => hasRole('admin', 'manager'));
    const isSiteHead = computed(() => hasRole('admin', 'manager', 'site_head'));
    const isStaff    = computed(() => hasRole('staff'));

    return {
        roles,
        permissions,
        hasRole,
        hasPermission,
        isAdmin,
        isManager,
        isSiteHead,
        isStaff,
    };
}
