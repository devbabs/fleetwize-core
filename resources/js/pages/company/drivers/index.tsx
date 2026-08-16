import { Head, router, useForm } from '@inertiajs/react';
import { Plus, Search, Trash2 } from 'lucide-react';
import {  useEffect, useRef, useState } from 'react';
import type {FormEvent} from 'react';

import { Pagination } from '@/components/company/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import CompanyLayout from '@/layouts/company/company-layout';
import type { Paginated } from '@/types/pagination';

type StaffRow = {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    role: string;
    roleText: string;
    assignedVehicleId: number | null;
    assignedVehicle: { id: number; label: string } | null;
    checkInsCount: number;
};

type VehicleOption = { id: number; label: string };

type DriverFormValues = {
    first_name: string;
    last_name: string;
    email: string;
    phone: string;
    password: string;
    role: string;
    vehicle_id: string;
};

const ROLE_LABELS: Record<string, string> = {
    'vehicle-operator': 'Vehicle Operator',
    staff: 'Staff',
    supervisor: 'Supervisor',
    'workshop-admin': 'Workshop Admin',
    admin: 'Admin',
};

const ROLES = Object.entries(ROLE_LABELS).map(([value, label]) => ({ value, label }));

function VehicleSelect({ value, onChange, vehicleOptions }: { value: string; onChange: (value: string) => void; vehicleOptions: VehicleOption[] }) {
    return (
        <Select value={value || 'none'} onValueChange={(v) => onChange(v === 'none' ? '' : v)}>
            <SelectTrigger className="w-full">
                <SelectValue placeholder="No vehicle assigned" />
            </SelectTrigger>
            <SelectContent>
                <SelectItem value="none">No vehicle assigned</SelectItem>
                {vehicleOptions.map((vehicle) => (
                    <SelectItem key={vehicle.id} value={String(vehicle.id)}>
                        {vehicle.label}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
}

function CreateDriverDialog({ open, onOpenChange, vehicleOptions }: { open: boolean; onOpenChange: (open: boolean) => void; vehicleOptions: VehicleOption[] }) {
    const { data, setData, post, processing, errors, reset } = useForm<DriverFormValues>({
        first_name: '',
        last_name: '',
        email: '',
        phone: '',
        password: '',
        role: 'vehicle-operator',
        vehicle_id: '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post('/drivers', {
            onSuccess: () => {
                reset();
                onOpenChange(false);
            },
        });
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogTrigger asChild>
                <Button className="bg-brand-navy text-white hover:bg-brand-navy/90 dark:bg-brand-green dark:text-brand-navy">
                    <Plus className="size-4" />
                    Add staff
                </Button>
            </DialogTrigger>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Add company staff</DialogTitle>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    <div className="grid grid-cols-2 gap-4">
                        <div className="grid gap-2">
                            <Label htmlFor="first_name">First name</Label>
                            <Input id="first_name" value={data.first_name} onChange={(e) => setData('first_name', e.target.value)} />
                            {errors.first_name ? <p className="text-xs text-destructive">{errors.first_name}</p> : null}
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="last_name">Last name</Label>
                            <Input id="last_name" value={data.last_name} onChange={(e) => setData('last_name', e.target.value)} />
                            {errors.last_name ? <p className="text-xs text-destructive">{errors.last_name}</p> : null}
                        </div>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="email">Email</Label>
                        <Input id="email" type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} />
                        {errors.email ? <p className="text-xs text-destructive">{errors.email}</p> : null}
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        <div className="grid gap-2">
                            <Label htmlFor="phone">Phone</Label>
                            <Input id="phone" value={data.phone} onChange={(e) => setData('phone', e.target.value)} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="password">Initial password</Label>
                            <Input id="password" type="password" value={data.password} onChange={(e) => setData('password', e.target.value)} />
                            {errors.password ? <p className="text-xs text-destructive">{errors.password}</p> : null}
                        </div>
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        <div className="grid gap-2">
                            <Label htmlFor="role">Role</Label>
                            <Select value={data.role} onValueChange={(value) => setData('role', value)}>
                                <SelectTrigger id="role" className="w-full">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {ROLES.map((role) => (
                                        <SelectItem key={role.value} value={role.value}>
                                            {role.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="grid gap-2">
                            <Label>Assigned vehicle</Label>
                            <VehicleSelect value={data.vehicle_id} onChange={(v) => setData('vehicle_id', v)} vehicleOptions={vehicleOptions} />
                        </div>
                    </div>

                    <DialogFooter>
                        <Button type="submit" loading={processing} className="bg-brand-navy text-white hover:bg-brand-navy/90 dark:bg-brand-green dark:text-brand-navy">
                            Save staff
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function EditDriverDialog({
    staff,
    onOpenChange,
    vehicleOptions,
}: {
    staff: StaffRow | null;
    onOpenChange: (open: boolean) => void;
    vehicleOptions: VehicleOption[];
}) {
    const [first_name, last_name] = staff ? staff.name.split(/\s+/, 2) : ['', ''];
    const { data, setData, patch, processing, errors, reset, setDefaults } = useForm({
        first_name: first_name ?? '',
        last_name: last_name ?? '',
        email: staff?.email ?? '',
        phone: staff?.phone ?? '',
        role: staff?.role ?? 'vehicle-operator',
        vehicle_id: staff?.assignedVehicleId ? String(staff.assignedVehicleId) : '',
    });

    useEffect(() => {
        if (staff) {
            const [f, l] = staff.name.split(/\s+/, 2);
            const values = {
                first_name: f ?? '',
                last_name: l ?? '',
                email: staff.email,
                phone: staff.phone ?? '',
                role: staff.role,
                vehicle_id: staff.assignedVehicleId ? String(staff.assignedVehicleId) : '',
            };
            setDefaults(values);
            setData(values);
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [staff?.id]);

    const submit = (e: FormEvent) => {
        e.preventDefault();

        if (!staff) {
return;
}

        patch(`/drivers/${staff.id}`, {
            onSuccess: () => {
                reset();
                onOpenChange(false);
            },
        });
    };

    return (
        <Dialog open={!!staff} onOpenChange={(open) => !open && onOpenChange(false)}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Edit company staff</DialogTitle>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    <div className="grid grid-cols-2 gap-4">
                        <div className="grid gap-2">
                            <Label htmlFor="edit_first_name">First name</Label>
                            <Input id="edit_first_name" value={data.first_name} onChange={(e) => setData('first_name', e.target.value)} />
                            {errors.first_name ? <p className="text-xs text-destructive">{errors.first_name}</p> : null}
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="edit_last_name">Last name</Label>
                            <Input id="edit_last_name" value={data.last_name} onChange={(e) => setData('last_name', e.target.value)} />
                            {errors.last_name ? <p className="text-xs text-destructive">{errors.last_name}</p> : null}
                        </div>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="edit_email">Email</Label>
                        <Input id="edit_email" type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} />
                        {errors.email ? <p className="text-xs text-destructive">{errors.email}</p> : null}
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="edit_phone">Phone</Label>
                        <Input id="edit_phone" value={data.phone} onChange={(e) => setData('phone', e.target.value)} />
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        <div className="grid gap-2">
                            <Label htmlFor="edit_role">Role</Label>
                            <Select value={data.role} onValueChange={(value) => setData('role', value)}>
                                <SelectTrigger id="edit_role" className="w-full">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {ROLES.map((role) => (
                                        <SelectItem key={role.value} value={role.value}>
                                            {role.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="grid gap-2">
                            <Label>Assigned vehicle</Label>
                            <VehicleSelect value={data.vehicle_id} onChange={(v) => setData('vehicle_id', v)} vehicleOptions={vehicleOptions} />
                        </div>
                    </div>

                    <DialogFooter>
                        <Button type="submit" loading={processing} className="bg-brand-navy text-white hover:bg-brand-navy/90 dark:bg-brand-green dark:text-brand-navy">
                            Save changes
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function DriversIndex({
    staff,
    filters,
    vehicleOptions,
}: {
    staff: Paginated<StaffRow>;
    filters: { search: string; role: string };
    vehicleOptions: VehicleOption[];
}) {
    const [createOpen, setCreateOpen] = useState(false);
    const [editing, setEditing] = useState<StaffRow | null>(null);
    const [deleting, setDeleting] = useState<StaffRow | null>(null);
    const [deleteInFlight, setDeleteInFlight] = useState(false);
    const [search, setSearch] = useState(filters.search);
    const debounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);

    const applyFilters = (next: { search?: string; role?: string }) => {
        router.get(
            '/drivers',
            {
                search: next.search ?? filters.search,
                role: next.role ?? filters.role,
            },
            { preserveState: true, replace: true },
        );
    };

    const onSearchChange = (value: string) => {
        setSearch(value);

        if (debounceRef.current) {
clearTimeout(debounceRef.current);
}

        debounceRef.current = setTimeout(() => applyFilters({ search: value }), 350);
    };

    const confirmDelete = () => {
        if (!deleting) {
return;
}

        setDeleteInFlight(true);
        router.delete(`/drivers/${deleting.id}`, {
            onFinish: () => {
                setDeleteInFlight(false);
                setDeleting(null);
            },
        });
    };

    return (
        <CompanyLayout title="Company Staff">
            <Head title="Company Staff" />

            <div className="flex flex-wrap items-center gap-2">
                <div className="relative w-full max-w-xs">
                    <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        value={search}
                        onChange={(e) => onSearchChange(e.target.value)}
                        placeholder="Search name or email…"
                        className="pl-9"
                    />
                </div>

                <Select value={filters.role || 'all'} onValueChange={(value) => applyFilters({ role: value === 'all' ? '' : value })}>
                    <SelectTrigger className="w-48">
                        <SelectValue placeholder="All roles" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All roles</SelectItem>
                        {ROLES.map((role) => (
                            <SelectItem key={role.value} value={role.value}>
                                {role.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>

                <div className="ml-auto">
                    <CreateDriverDialog open={createOpen} onOpenChange={setCreateOpen} vehicleOptions={vehicleOptions} />
                </div>
            </div>

            <Card className="overflow-hidden py-0">
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead className="border-b bg-muted/40 text-left text-xs tracking-wide text-muted-foreground uppercase">
                            <tr>
                                <th className="px-6 py-3 font-medium">Name</th>
                                <th className="px-6 py-3 font-medium">Contact</th>
                                <th className="px-6 py-3 font-medium">Role</th>
                                <th className="px-6 py-3 font-medium">Assigned Vehicle</th>
                                <th className="px-6 py-3 font-medium">Check-ins</th>
                                <th className="px-6 py-3 font-medium"></th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-border">
                            {staff.data.map((member) => (
                                <tr key={member.id} className="hover:bg-muted/40">
                                    <td className="px-6 py-3 font-medium text-foreground">{member.name}</td>
                                    <td className="px-6 py-3 text-muted-foreground">
                                        <div>{member.email}</div>
                                        {member.phone ? <div className="text-xs">{member.phone}</div> : null}
                                    </td>
                                    <td className="px-6 py-3">
                                        <Badge variant="outline" className="capitalize">
                                            {member.roleText}
                                        </Badge>
                                    </td>
                                    <td className="px-6 py-3 text-muted-foreground">{member.assignedVehicle?.label ?? '—'}</td>
                                    <td className="px-6 py-3 text-muted-foreground">{member.checkInsCount}</td>
                                    <td className="px-6 py-3 text-right">
                                        <div className="flex justify-end gap-2">
                                            <Button size="sm" variant="outline" onClick={() => setEditing(member)}>
                                                Edit
                                            </Button>
                                            <Button size="sm" variant="outline" onClick={() => setDeleting(member)}>
                                                <Trash2 className="size-4 text-destructive" />
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            ))}

                            {staff.data.length === 0 ? (
                                <tr>
                                    <td colSpan={6} className="px-6 py-10 text-center text-muted-foreground">
                                        No staff found.
                                    </td>
                                </tr>
                            ) : null}
                        </tbody>
                    </table>
                </div>
                <Pagination links={staff.links} from={staff.from} to={staff.to} total={staff.total} />
            </Card>

            <EditDriverDialog staff={editing} onOpenChange={(open) => !open && setEditing(null)} vehicleOptions={vehicleOptions} />

            <Dialog open={!!deleting} onOpenChange={(open) => !open && setDeleting(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Remove {deleting?.name}?</DialogTitle>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setDeleting(null)}>
                            Cancel
                        </Button>
                        <Button variant="destructive" loading={deleteInFlight} onClick={confirmDelete}>
                            Remove
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </CompanyLayout>
    );
}
