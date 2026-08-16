import { Head, useForm } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import type { FormEvent } from 'react';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import WorkshopLayout from '@/layouts/workshop/workshop-layout';

type VehicleOption = { id: number; label: string };

type FaultRow = {
    severity: string;
    error_code: string;
    description: string;
    remark: string;
};

const emptyFault: FaultRow = { severity: 'low', error_code: '', description: '', remark: '' };

export default function CreateDiagnosticReport({
    vehicleOptions,
    preselectedVehicleId,
}: {
    vehicleOptions: VehicleOption[];
    preselectedVehicleId: number | null;
}) {
    const { data, setData, post, processing, errors } = useForm<{
        vehicle_id: string;
        reference: string;
        faults: FaultRow[];
    }>({
        vehicle_id: preselectedVehicleId ? String(preselectedVehicleId) : '',
        reference: '',
        faults: [{ ...emptyFault }],
    });

    const addFault = () => setData('faults', [...data.faults, { ...emptyFault }]);
    const removeFault = (index: number) => setData('faults', data.faults.filter((_, i) => i !== index));
    const updateFault = (index: number, key: keyof FaultRow, value: string) =>
        setData(
            'faults',
            data.faults.map((fault, i) => (i === index ? { ...fault, [key]: value } : fault)),
        );

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post('/workshop/reports');
    };

    return (
        <WorkshopLayout title="New Diagnostic Report">
            <Head title="New Diagnostic Report" />

            <form onSubmit={submit} className="space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle>Report details</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-4 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label>Vehicle</Label>
                            <Select value={data.vehicle_id} onValueChange={(value) => setData('vehicle_id', value)}>
                                <SelectTrigger className="w-full">
                                    <SelectValue placeholder="Select a vehicle" />
                                </SelectTrigger>
                                <SelectContent>
                                    {vehicleOptions.map((vehicle) => (
                                        <SelectItem key={vehicle.id} value={String(vehicle.id)}>
                                            {vehicle.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {errors.vehicle_id ? <p className="text-xs text-destructive">{errors.vehicle_id}</p> : null}
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="reference">Reference (optional)</Label>
                            <Input id="reference" value={data.reference} onChange={(e) => setData('reference', e.target.value)} />
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="flex flex-row items-center justify-between">
                        <CardTitle>Faults</CardTitle>
                        <Button type="button" size="sm" variant="outline" onClick={addFault}>
                            <Plus className="size-4" />
                            Add fault
                        </Button>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {data.faults.map((fault, index) => (
                            <div key={index} className="space-y-3 rounded-lg border border-border p-4">
                                <div className="flex items-center justify-between">
                                    <p className="text-xs font-medium text-muted-foreground uppercase">Fault {index + 1}</p>
                                    {data.faults.length > 1 ? (
                                        <Button type="button" size="sm" variant="ghost" onClick={() => removeFault(index)}>
                                            <Trash2 className="size-4 text-destructive" />
                                        </Button>
                                    ) : null}
                                </div>

                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div className="grid gap-2">
                                        <Label>Severity</Label>
                                        <Select value={fault.severity} onValueChange={(value) => updateFault(index, 'severity', value)}>
                                            <SelectTrigger className="w-full">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="low">Low</SelectItem>
                                                <SelectItem value="medium">Medium</SelectItem>
                                                <SelectItem value="major">Major</SelectItem>
                                                <SelectItem value="critical">Critical</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div className="grid gap-2">
                                        <Label>Error code</Label>
                                        <Input value={fault.error_code} onChange={(e) => updateFault(index, 'error_code', e.target.value)} />
                                    </div>
                                </div>

                                <div className="grid gap-2">
                                    <Label>Description</Label>
                                    <Textarea value={fault.description} onChange={(e) => updateFault(index, 'description', e.target.value)} rows={2} />
                                </div>

                                <div className="grid gap-2">
                                    <Label>Remark / recommendation</Label>
                                    <Textarea value={fault.remark} onChange={(e) => updateFault(index, 'remark', e.target.value)} rows={2} />
                                </div>
                            </div>
                        ))}
                        {errors.faults ? <p className="text-xs text-destructive">{errors.faults}</p> : null}
                    </CardContent>
                </Card>

                <Button type="submit" loading={processing} className="bg-brand-navy text-white hover:bg-brand-navy/90 dark:bg-brand-green dark:text-brand-navy">
                    Create report
                </Button>
            </form>
        </WorkshopLayout>
    );
}
