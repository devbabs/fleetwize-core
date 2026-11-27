import { Head, router, useForm } from '@inertiajs/react';
import { Pencil, Plus, Search, Shield, Trash2, Truck, Users } from 'lucide-react';
import { useMemo, useState } from 'react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { usePermission } from '@/hooks/use-permission';
import CompanyLayout from '@/layouts/company/company-layout';

type PermissionItem = {
    id: number;
    name: string;
    slug: string;
    module: string;
    description: string | null;
};

type RoleItem = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    isSystem: boolean;
    canBeAssignedVehicle: boolean;
    usersCount: number;
    permissionIds: number[];
};

type Props = {
    roles: RoleItem[];
    permissionsByModule: Record<string, PermissionItem[]>;
};

const MODULE_LABELS: Record<string, string> = {
    overview: 'Overview & Telemetry',
    vehicles: 'Vehicles',
    documents: 'Vehicle Documents',
    geofences: 'Geofences',
    alarms: 'Alarms & Faults',
    maintenance: 'Maintenance',
    fuel: 'Fuel',
    expenses: 'Expenses',
    drivers: 'Drivers & Scorecards',
    reports: 'Reports',
    staff: 'Company Staff',
    roles: 'Roles & Permissions',
    settings: 'Audit & Settings',
    workshop: 'Workshop Portal',
};

function formatModuleLabel(module: string): string {
    return (
        MODULE_LABELS[module] ??
        module
            .replace(/[_-]+/g, ' ')
            .replace(/\b\w/g, (c) => c.toUpperCase())
    );
}

export default function RolesIndex({ roles, permissionsByModule }: Props) {
    const { can } = usePermission();

    const [dialogOpen, setDialogOpen] = useState(false);
    const [editingRole, setEditingRole] = useState<RoleItem | null>(null);
    const [permissionSearch, setPermissionSearch] = useState('');

    const { data, setData, post, patch, processing, errors, reset, clearErrors } = useForm<{
        name: string;
        description: string;
        can_be_assigned_vehicle: boolean;
        permission_ids: number[];
    }>({
        name: '',
        description: '',
        can_be_assigned_vehicle: false,
        permission_ids: [],
    });

    const allPermissionIds = useMemo(
        () =>
            Object.values(permissionsByModule ?? {})
                .flat()
                .map((p) => p.id),
        [permissionsByModule],
    );

    const filteredModules = useMemo(() => {
        const query = permissionSearch.trim().toLowerCase();
        const entries = Object.entries(permissionsByModule ?? {});

        if (!query) {
            return entries;
        }

        return entries
            .map(([module, items]) => {
                const moduleLabel = formatModuleLabel(module).toLowerCase();
                if (moduleLabel.includes(query)) {
                    return [module, items] as [string, PermissionItem[]];
                }

                const matched = items.filter(
                    (p) =>
                        p.name.toLowerCase().includes(query) ||
                        p.slug.toLowerCase().includes(query) ||
                        (p.description ?? '').toLowerCase().includes(query),
                );

                return [module, matched] as [string, PermissionItem[]];
            })
            .filter(([, items]) => items.length > 0);
    }, [permissionsByModule, permissionSearch]);

    const openCreateDialog = () => {
        setEditingRole(null);
        setPermissionSearch('');
        clearErrors();
        reset();
        setDialogOpen(true);
    };

    const openEditDialog = (role: RoleItem) => {
        setEditingRole(role);
        setPermissionSearch('');
        clearErrors();
        setData({
            name: role.name,
            description: role.description ?? '',
            can_be_assigned_vehicle: role.canBeAssignedVehicle,
            permission_ids: [...role.permissionIds],
        });
        setDialogOpen(true);
    };

    const togglePermission = (permissionId: number) => {
        const exists = data.permission_ids.includes(permissionId);
        setData(
            'permission_ids',
            exists
                ? data.permission_ids.filter((id) => id !== permissionId)
                : [...data.permission_ids, permissionId],
        );
    };

    const toggleModule = (items: PermissionItem[]) => {
        const ids = items.map((item) => item.id);
        const allSelected = ids.every((id) => data.permission_ids.includes(id));

        if (allSelected) {
            setData(
                'permission_ids',
                data.permission_ids.filter((id) => !ids.includes(id)),
            );
        } else {
            setData('permission_ids', Array.from(new Set([...data.permission_ids, ...ids])));
        }
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        const options = {
            preserveScroll: true,
            onSuccess: () => {
                setDialogOpen(false);
                reset();
            },
        };

        if (editingRole) {
            patch(`/roles/${editingRole.id}`, options);
        } else {
            post('/roles', options);
        }
    };

    const handleDelete = (role: RoleItem) => {
        if (!window.confirm(`Delete the "${role.name}" role? This cannot be undone.`)) {
            return;
        }

        router.delete(`/roles/${role.id}`, { preserveScroll: true });
    };

    return (
        <CompanyLayout title="Roles & Permissions">
            <Head title="Roles & Permissions" />

            <div className="space-y-6">
                <Card className="overflow-hidden py-0">
                    <div className="flex flex-col gap-3 border-b px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 className="font-semibold text-foreground">Company Roles</h2>
                            <p className="text-sm text-muted-foreground">
                                Define custom roles and control which actions staff members can perform.
                            </p>
                        </div>

                        {can('roles.create') ? (
                            <Button
                                type="button"
                                onClick={openCreateDialog}
                                className="bg-brand-navy text-white hover:bg-brand-navy/90 dark:bg-brand-green dark:text-brand-navy"
                            >
                                <Plus className="size-4" />
                                Create role
                            </Button>
                        ) : null}
                    </div>

                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="border-b bg-muted/40 text-left text-xs tracking-wide text-muted-foreground uppercase">
                                <tr>
                                    <th className="px-6 py-3 font-medium">Role</th>
                                    <th className="px-6 py-3 font-medium">Permissions</th>
                                    <th className="px-6 py-3 font-medium">Staff</th>
                                    <th className="px-6 py-3 font-medium">Vehicle Assignment</th>
                                    <th className="px-6 py-3 text-right font-medium">Actions</th>
                                </tr>
                            </thead>

                            <tbody className="divide-y divide-border">
                                {roles.map((role) => (
                                    <tr key={role.id} className="hover:bg-muted/20">
                                        <td className="px-6 py-4">
                                            <div className="flex items-center gap-2">
                                                <Shield className="size-4 text-muted-foreground" />
                                                <span className="font-medium text-foreground">
                                                    {role.name}
                                                </span>
                                                {role.isSystem ? (
                                                    <Badge variant="secondary">System</Badge>
                                                ) : null}
                                            </div>
                                            {role.description ? (
                                                <p className="mt-1 text-xs text-muted-foreground">
                                                    {role.description}
                                                </p>
                                            ) : null}
                                        </td>

                                        <td className="px-6 py-4 tabular-nums text-muted-foreground">
                                            {role.isSystem
                                                ? `All (${allPermissionIds.length})`
                                                : `${role.permissionIds.length} / ${allPermissionIds.length}`}
                                        </td>

                                        <td className="px-6 py-4">
                                            <div className="inline-flex items-center gap-1.5 text-muted-foreground tabular-nums">
                                                <Users className="size-3.5" />
                                                {role.usersCount}
                                            </div>
                                        </td>

                                        <td className="px-6 py-4">
                                            {role.canBeAssignedVehicle ? (
                                                <Badge className="gap-1 border-transparent bg-brand-green/15 text-brand-green">
                                                    <Truck className="size-3" />
                                                    Eligible
                                                </Badge>
                                            ) : (
                                                <span className="text-xs text-muted-foreground">No</span>
                                            )}
                                        </td>

                                        <td className="px-6 py-4 text-right">
                                            <div className="flex items-center justify-end gap-2">
                                                {can('roles.update') ? (
                                                    <Button
                                                        type="button"
                                                        size="sm"
                                                        variant="outline"
                                                        disabled={role.isSystem}
                                                        title={
                                                            role.isSystem
                                                                ? 'System admin role cannot be modified'
                                                                : 'Edit role'
                                                        }
                                                        onClick={() => openEditDialog(role)}
                                                    >
                                                        <Pencil className="size-3.5" />
                                                        Edit
                                                    </Button>
                                                ) : null}

                                                {can('roles.delete') && !role.isSystem ? (
                                                    <Button
                                                        type="button"
                                                        size="sm"
                                                        variant="ghost"
                                                        className="text-destructive hover:text-destructive"
                                                        disabled={role.usersCount > 0}
                                                        title={
                                                            role.usersCount > 0
                                                                ? 'Reassign staff out of this role before deleting'
                                                                : 'Delete role'
                                                        }
                                                        onClick={() => handleDelete(role)}
                                                    >
                                                        <Trash2 className="size-3.5" />
                                                    </Button>
                                                ) : null}
                                            </div>
                                        </td>
                                    </tr>
                                ))}

                                {roles.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={5}
                                            className="px-6 py-10 text-center text-muted-foreground"
                                        >
                                            No roles configured yet.
                                        </td>
                                    </tr>
                                ) : null}
                            </tbody>
                        </table>
                    </div>
                </Card>
            </div>

            <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
                <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-3xl">
                    <DialogHeader>
                        <DialogTitle>
                            {editingRole ? `Edit Role — ${editingRole.name}` : 'Create Custom Role'}
                        </DialogTitle>
                        <DialogDescription>
                            Choose a role name and select the exact system actions staff with this role can perform.
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={handleSubmit} className="space-y-5">
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="role_name">Role name</Label>
                                <Input
                                    id="role_name"
                                    placeholder="e.g. Abuja Hub Lead"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                />
                                {errors.name ? (
                                    <p className="text-xs text-destructive">{errors.name}</p>
                                ) : null}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="role_description">Description (optional)</Label>
                                <Input
                                    id="role_description"
                                    placeholder="Short summary of responsibilities"
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                />
                                {errors.description ? (
                                    <p className="text-xs text-destructive">{errors.description}</p>
                                ) : null}
                            </div>
                        </div>

                        <label className="flex cursor-pointer items-start gap-3 rounded-lg border bg-muted/20 p-3">
                            <input
                                type="checkbox"
                                className="mt-0.5 size-4 rounded border-input accent-brand-navy"
                                checked={data.can_be_assigned_vehicle}
                                onChange={(e) => setData('can_be_assigned_vehicle', e.target.checked)}
                            />
                            <div>
                                <div className="text-sm font-medium text-foreground">
                                    Allow vehicle assignment
                                </div>
                                <div className="text-xs text-muted-foreground">
                                    Enable for field roles like Drivers or Dispatch Riders who can be assigned to fleet vehicles.
                                </div>
                            </div>
                        </label>

                        {/* Permissions Matrix */}
                        <div className="space-y-3">
                            <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <Label>
                                        Permissions ({data.permission_ids.length} / {allPermissionIds.length} selected)
                                    </Label>
                                    {errors.permission_ids ? (
                                        <p className="text-xs text-destructive">{errors.permission_ids}</p>
                                    ) : null}
                                </div>

                                <div className="flex items-center gap-2">
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant="outline"
                                        onClick={() => setData('permission_ids', [...allPermissionIds])}
                                    >
                                        Select all
                                    </Button>
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant="ghost"
                                        onClick={() => setData('permission_ids', [])}
                                    >
                                        Clear all
                                    </Button>
                                </div>
                            </div>

                            <div className="relative">
                                <Search className="pointer-events-none absolute top-2.5 left-3 size-4 text-muted-foreground" />
                                <Input
                                    className="pl-9"
                                    placeholder="Filter permissions by module or action..."
                                    value={permissionSearch}
                                    onChange={(e) => setPermissionSearch(e.target.value)}
                                />
                            </div>

                            <div className="max-h-96 space-y-3 overflow-y-auto rounded-lg border p-3">
                                {filteredModules.map(([module, items]) => {
                                    const selectedInModule = items.filter((i) =>
                                        data.permission_ids.includes(i.id),
                                    ).length;
                                    const allInModuleSelected = selectedInModule === items.length;

                                    return (
                                        <div key={module} className="rounded-md border bg-background">
                                            <div className="flex items-center justify-between border-b bg-muted/30 px-3 py-2">
                                                <div className="flex items-center gap-2">
                                                    <span className="text-xs font-semibold tracking-wide text-foreground uppercase">
                                                        {formatModuleLabel(module)}
                                                    </span>
                                                    <Badge variant="outline" className="text-[11px] tabular-nums">
                                                        {selectedInModule}/{items.length}
                                                    </Badge>
                                                </div>

                                                <button
                                                    type="button"
                                                    onClick={() => toggleModule(items)}
                                                    className="text-xs font-medium text-primary hover:underline"
                                                >
                                                    {allInModuleSelected ? 'Unselect module' : 'Select module'}
                                                </button>
                                            </div>

                                            <div className="grid gap-2 p-3 sm:grid-cols-2">
                                                {items.map((perm) => {
                                                    const checked = data.permission_ids.includes(perm.id);

                                                    return (
                                                        <label
                                                            key={perm.id}
                                                            className="flex cursor-pointer items-start gap-2.5 rounded-md p-1.5 hover:bg-muted/40"
                                                        >
                                                            <input
                                                                type="checkbox"
                                                                className="mt-0.5 size-4 rounded border-input accent-brand-navy"
                                                                checked={checked}
                                                                onChange={() => togglePermission(perm.id)}
                                                            />
                                                            <div className="min-w-0">
                                                                <div className="text-xs font-medium text-foreground">
                                                                    {perm.name}
                                                                </div>
                                                                {perm.description ? (
                                                                    <div className="text-[11px] text-muted-foreground">
                                                                        {perm.description}
                                                                    </div>
                                                                ) : null}
                                                            </div>
                                                        </label>
                                                    );
                                                })}
                                            </div>
                                        </div>
                                    );
                                })}

                                {filteredModules.length === 0 ? (
                                    <p className="py-6 text-center text-xs text-muted-foreground">
                                        No permissions match "{permissionSearch}".
                                    </p>
                                ) : null}
                            </div>
                        </div>

                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setDialogOpen(false)}
                            >
                                Cancel
                            </Button>

                            <Button
                                type="submit"
                                disabled={processing}
                                className="bg-brand-navy text-white hover:bg-brand-navy/90 dark:bg-brand-green dark:text-brand-navy"
                            >
                                {editingRole ? 'Save changes' : 'Create role'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </CompanyLayout>
    );
}