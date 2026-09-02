import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { Download, Plus, Trash2, Upload } from 'lucide-react';
import { useState } from 'react';
import type {FormEvent} from 'react';

import { Pagination } from '@/components/company/pagination';
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
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useVehicleLiveUpdates } from '@/hooks/use-vehicle-live-updates';
import type { VehicleLiveUpdate } from '@/hooks/use-vehicle-live-updates';
import CompanyLayout from '@/layouts/company/company-layout';
import type { Paginated } from '@/types/pagination';

type VehicleRow = {
    id: number;
    name: string | null;
    licensePlate: string | null;
    make: string | null;
    model: string | null;
    category: string;
    status: string;
    mileage: number;
    isOnline: boolean;
    speed: number | null;
    fuelLevel: number | null;
    openFaultsCount: number;
    lastSeenAt: string | null;
};

type VehicleFormValues = {
    license_plate: string;
    make: string;
    model: string;
    year: string;
    color: string;
    category: string;
    status: string;
    mileage: string;
    vin: string;
    obd_device_id: string;
    obd_device_imei: string;
    tracker_phone_number: string;
};

const emptyForm: VehicleFormValues = {
    license_plate: '',
    make: '',
    model: '',
    year: '',
    color: '',
    category: 'car',
    status: 'active',
    mileage: '0',
    vin: '',
    obd_device_id: '',
    obd_device_imei: '',
    tracker_phone_number: '',
};

function mergeLiveUpdate(vehicle: VehicleRow, update?: VehicleLiveUpdate): VehicleRow {
    if (!update) {
        return vehicle;
    }

    return {
        ...vehicle,
        isOnline: update.isOnline,
        speed: update.speed,
        fuelLevel: update.fuelLevel,
        lastSeenAt: update.reportedAt,
    };
}

function statusBadge(vehicle: VehicleRow) {
    if (vehicle.isOnline && (vehicle.speed ?? 0) > 5) {
        return <Badge className="border-transparent bg-brand-green/15 text-brand-green">Moving</Badge>;
    }

    if (vehicle.isOnline) {
        return <Badge variant="secondary">Online - Idle</Badge>;
    }

    return <Badge variant="outline">Offline</Badge>;
}

function VehicleForm({
    initial,
    endpoint,
    method,
    onDone,
}: {
    initial: VehicleFormValues;
    endpoint: string;
    method: 'post' | 'patch';
    onDone: () => void;
}) {
    const { data, setData, post, patch, processing, errors, reset } = useForm<VehicleFormValues>(initial);

    const submit = (e: FormEvent) => {
        e.preventDefault();
        const options = { onSuccess: () => {
 reset(); onDone(); 
} };

        if (method === 'post') {
            post(endpoint, options);
        } else {
            patch(endpoint, options);
        }
    };

    return (
        <form onSubmit={submit} className="space-y-4">
            <div className="grid grid-cols-2 gap-4">
                <div className="grid gap-2">
                    <Label htmlFor="license_plate">License plate</Label>
                    <Input id="license_plate" value={data.license_plate} onChange={(e) => setData('license_plate', e.target.value)} />
                    {errors.license_plate ? <p className="text-xs text-destructive">{errors.license_plate}</p> : null}
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="category">Category</Label>
                    <Select value={data.category} onValueChange={(value) => setData('category', value)}>
                        <SelectTrigger id="category" className="w-full">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="car">Car</SelectItem>
                            <SelectItem value="van">Van</SelectItem>
                            <SelectItem value="truck">Truck</SelectItem>
                            <SelectItem value="bus">Bus</SelectItem>
                            <SelectItem value="motorcycle">Motorcycle</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>

            <div className="grid grid-cols-2 gap-4">
                <div className="grid gap-2">
                    <Label htmlFor="make">Make</Label>
                    <Input id="make" value={data.make} onChange={(e) => setData('make', e.target.value)} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="model">Model</Label>
                    <Input id="model" value={data.model} onChange={(e) => setData('model', e.target.value)} />
                </div>
            </div>

            <div className="grid grid-cols-3 gap-4">
                <div className="grid gap-2">
                    <Label htmlFor="year">Year</Label>
                    <Input id="year" value={data.year} onChange={(e) => setData('year', e.target.value)} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="color">Color</Label>
                    <Input id="color" value={data.color} onChange={(e) => setData('color', e.target.value)} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="status">Status</Label>
                    <Select value={data.status} onValueChange={(value) => setData('status', value)}>
                        <SelectTrigger id="status" className="w-full">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="active">Active</SelectItem>
                            <SelectItem value="maintenance">Maintenance</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>

            <div className="grid grid-cols-2 gap-4">
                <div className="grid gap-2">
                    <Label htmlFor="mileage">Mileage (km)</Label>
                    <Input id="mileage" type="number" value={data.mileage} onChange={(e) => setData('mileage', e.target.value)} />
                    {errors.mileage ? <p className="text-xs text-destructive">{errors.mileage}</p> : null}
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="vin">VIN</Label>
                    <Input id="vin" value={data.vin} onChange={(e) => setData('vin', e.target.value)} />
                </div>
            </div>

            <div className="space-y-2 rounded-lg border border-dashed border-border p-4">
                <p className="text-xs font-medium text-muted-foreground uppercase">Tracker (optional)</p>
                <div className="grid grid-cols-2 gap-4">
                    <div className="grid gap-2">
                        <Label htmlFor="obd_device_imei">Tracker IMEI</Label>
                        <Input
                            id="obd_device_imei"
                            value={data.obd_device_imei}
                            onChange={(e) => setData('obd_device_imei', e.target.value)}
                            placeholder="Checked against Traccar"
                        />
                        {errors.obd_device_imei ? <p className="text-xs text-destructive">{errors.obd_device_imei}</p> : null}
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="tracker_phone_number">Tracker SIM number</Label>
                        <Input
                            id="tracker_phone_number"
                            value={data.tracker_phone_number}
                            onChange={(e) => setData('tracker_phone_number', e.target.value)}
                        />
                    </div>
                </div>
            </div>

            <DialogFooter>
                <Button type="submit" loading={processing} className="bg-brand-navy text-white hover:bg-brand-navy/90 dark:bg-brand-green dark:text-brand-navy">
                    Save vehicle
                </Button>
            </DialogFooter>
        </form>
    );
}

function toFormValues(vehicle: VehicleRow & { year?: string | null; color?: string | null }): VehicleFormValues {
    return {
        license_plate: vehicle.licensePlate ?? '',
        make: vehicle.make ?? '',
        model: vehicle.model ?? '',
        year: '',
        color: '',
        category: vehicle.category,
        status: vehicle.status,
        mileage: String(vehicle.mileage ?? 0),
        vin: '',
        obd_device_id: '',
        obd_device_imei: '',
        tracker_phone_number: '',
    };
}

function BulkUploadDialog({ open, onOpenChange }: { open: boolean; onOpenChange: (open: boolean) => void }) {
    const { data, setData, post, processing, errors, reset } = useForm<{ file: File | null }>({ file: null });
    const { props } = usePage<{ flash?: { importErrors?: string[] } }>();
    const importErrors = props.flash?.importErrors ?? [];

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post('/vehicles/import', {
            forceFormData: true,
            onSuccess: () => {
                reset();
                onOpenChange(false);
            },
        });
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Bulk upload vehicles</DialogTitle>
                    <DialogDescription>Upload a CSV file using the template columns. Trackers, if included, are validated against Traccar.</DialogDescription>
                </DialogHeader>

                <a href="/vehicles/template" className="inline-flex items-center gap-2 text-sm text-brand-navy underline dark:text-brand-green">
                    <Download className="size-4" />
                    Download CSV template
                </a>

                <form onSubmit={submit} className="space-y-4">
                    <div className="grid gap-2">
                        <Label htmlFor="file">CSV file</Label>
                        <Input
                            id="file"
                            type="file"
                            accept=".csv,text/csv"
                            onChange={(e) => setData('file', e.target.files?.[0] ?? null)}
                        />
                        {errors.file ? <p className="text-xs text-destructive">{errors.file}</p> : null}
                    </div>

                    {importErrors.length > 0 ? (
                        <div className="max-h-40 space-y-1 overflow-y-auto rounded-lg border border-destructive/30 bg-destructive/5 p-3 text-xs text-destructive">
                            {importErrors.map((error, index) => (
                                <p key={index}>{error}</p>
                            ))}
                        </div>
                    ) : null}

                    <DialogFooter>
                        <Button type="submit" loading={processing} disabled={!data.file} className="bg-brand-navy text-white hover:bg-brand-navy/90 dark:bg-brand-green dark:text-brand-navy">
                            <Upload className="size-4" />
                            Upload
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function VehiclesIndex({ vehicles }: { vehicles: Paginated<VehicleRow> }) {
    const [createOpen, setCreateOpen] = useState(false);
    const [editing, setEditing] = useState<VehicleRow | null>(null);
    const [deleting, setDeleting] = useState<VehicleRow | null>(null);
    const [importOpen, setImportOpen] = useState(false);
    const [deleteInFlight, setDeleteInFlight] = useState(false);
    const [liveOverrides, setLiveOverrides] = useState<Record<number, VehicleLiveUpdate>>({});

    // Keyed by id rather than replacing `vehicles.data` wholesale, so
    // paginating (which re-fetches `vehicles` as a fresh Inertia prop)
    // isn't fought by stale local state — overrides for ids no longer on
    // the current page just go unused.
    useVehicleLiveUpdates((update) => {
        setLiveOverrides((prev) => ({ ...prev, [update.id]: update }));
    });

    const confirmDelete = () => {
        if (!deleting) {
return;
}

        setDeleteInFlight(true);
        router.delete(`/vehicles/${deleting.id}`, {
            onFinish: () => {
                setDeleteInFlight(false);
                setDeleting(null);
            },
        });
    };

    return (
        <CompanyLayout title="Vehicles">
            <Head title="Vehicles" />

            <div className="flex flex-wrap items-center justify-end gap-2">
                <Button variant="outline" onClick={() => setImportOpen(true)}>
                    <Upload className="size-4" />
                    Bulk upload
                </Button>
                <Dialog open={createOpen} onOpenChange={setCreateOpen}>
                    <DialogTrigger asChild>
                        <Button className="bg-brand-navy text-white hover:bg-brand-navy/90 dark:bg-brand-green dark:text-brand-navy">
                            <Plus className="size-4" />
                            Add vehicle
                        </Button>
                    </DialogTrigger>
                    <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-xl">
                        <DialogHeader>
                            <DialogTitle>Add vehicle</DialogTitle>
                            <DialogDescription>Vehicle details for your fleet. Tracker attachment is optional here.</DialogDescription>
                        </DialogHeader>
                        <VehicleForm initial={emptyForm} endpoint="/vehicles" method="post" onDone={() => setCreateOpen(false)} />
                    </DialogContent>
                </Dialog>
            </div>

            <Card className="overflow-hidden py-0">
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead className="border-b bg-muted/40 text-left text-xs tracking-wide text-muted-foreground uppercase">
                            <tr>
                                <th className="px-6 py-3 font-medium">Plate No.</th>
                                <th className="px-6 py-3 font-medium">Vehicle</th>
                                <th className="px-6 py-3 font-medium">Category</th>
                                <th className="px-6 py-3 font-medium">Status</th>
                                <th className="px-6 py-3 font-medium">Fuel</th>
                                <th className="px-6 py-3 font-medium">Mileage</th>
                                <th className="px-6 py-3 font-medium">Alerts</th>
                                <th className="px-6 py-3 font-medium"></th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-border">
                            {vehicles.data.map((row) => {
                                const vehicle = mergeLiveUpdate(row, liveOverrides[row.id]);

                                return (
                                <tr key={vehicle.id} className="transition-colors hover:bg-muted/40">
                                    <td className="px-6 py-3">
                                        <Link href={`/vehicles/${vehicle.id}`} className="font-medium text-foreground hover:underline">
                                            {vehicle.licensePlate ?? '—'}
                                        </Link>
                                    </td>
                                    <td className="px-6 py-3 text-muted-foreground">
                                        {[vehicle.make, vehicle.model].filter(Boolean).join(' ') || vehicle.name || '—'}
                                    </td>
                                    <td className="px-6 py-3 text-muted-foreground capitalize">{vehicle.category}</td>
                                    <td className="px-6 py-3">{statusBadge(vehicle)}</td>
                                    <td className="px-6 py-3 text-muted-foreground">
                                        {vehicle.fuelLevel !== null ? `${Math.round(vehicle.fuelLevel)}%` : '—'}
                                    </td>
                                    <td className="px-6 py-3 text-muted-foreground">{Math.round(vehicle.mileage).toLocaleString()} km</td>
                                    <td className="px-6 py-3">
                                        {vehicle.openFaultsCount > 0 ? (
                                            <Badge variant="destructive">{vehicle.openFaultsCount}</Badge>
                                        ) : (
                                            <span className="text-muted-foreground">—</span>
                                        )}
                                    </td>
                                    <td className="px-6 py-3 text-right">
                                        <div className="flex justify-end gap-2">
                                            <Button size="sm" variant="outline" onClick={() => setEditing(vehicle)}>
                                                Edit
                                            </Button>
                                            <Button size="sm" variant="outline" onClick={() => setDeleting(vehicle)}>
                                                <Trash2 className="size-4 text-destructive" />
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                                );
                            })}

                            {vehicles.data.length === 0 ? (
                                <tr>
                                    <td colSpan={8} className="px-6 py-10 text-center text-muted-foreground">
                                        No vehicles yet.
                                    </td>
                                </tr>
                            ) : null}
                        </tbody>
                    </table>
                </div>
                <Pagination links={vehicles.links} from={vehicles.from} to={vehicles.to} total={vehicles.total} />
            </Card>

            <Dialog open={!!editing} onOpenChange={(open) => !open && setEditing(null)}>
                <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-xl">
                    <DialogHeader>
                        <DialogTitle>Edit vehicle</DialogTitle>
                    </DialogHeader>
                    {editing ? (
                        <VehicleForm
                            initial={toFormValues(editing)}
                            endpoint={`/vehicles/${editing.id}`}
                            method="patch"
                            onDone={() => setEditing(null)}
                        />
                    ) : null}
                </DialogContent>
            </Dialog>

            <Dialog open={!!deleting} onOpenChange={(open) => !open && setDeleting(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Remove {deleting?.licensePlate}?</DialogTitle>
                        <DialogDescription>This can&apos;t be undone. Trip, fault, and service history for this vehicle stays on record.</DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setDeleting(null)}>
                            Cancel
                        </Button>
                        <Button variant="destructive" loading={deleteInFlight} onClick={confirmDelete}>
                            Remove vehicle
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <BulkUploadDialog open={importOpen} onOpenChange={setImportOpen} />
        </CompanyLayout>
    );
}
