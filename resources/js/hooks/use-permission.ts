import { usePage } from '@inertiajs/react';

type SharedAuthProps = {
    auth?: {
        permissions?: string[];
    };
};

export function usePermission() {
    const { auth } = usePage<SharedAuthProps>().props;
    const permissions = auth?.permissions ?? [];

    return {
        permissions,
        can: (permission: string) => permissions.includes(permission),
        canAny: (list: string[]) => list.some((p) => permissions.includes(p)),
        canAll: (list: string[]) => list.every((p) => permissions.includes(p)),
    };
}