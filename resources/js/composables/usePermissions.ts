import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export function usePermissions() {
    const page = usePage();

    const permissions = computed<string[]>(
        () => page.props.auth?.permissions ?? [],
    );
    const roles = computed(() => page.props.auth?.roles ?? []);

    function can(permission: string): boolean {
        return permissions.value.includes(permission);
    }

    function canAny(required: string[]): boolean {
        if (required.length === 0) {
            return true;
        }

        return required.some((permission) =>
            permissions.value.includes(permission),
        );
    }

    function hasRole(roleName: string): boolean {
        return roles.value.some((role) => role.name === roleName);
    }

    const isSuperAdmin = computed(() => hasRole('super_administrator'));

    return {
        permissions,
        roles,
        can,
        canAny,
        hasRole,
        isSuperAdmin,
    };
}
