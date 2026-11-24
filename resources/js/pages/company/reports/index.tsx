import { Head } from '@inertiajs/react';
import {
    AlertTriangle,
    Calendar,
    FileSpreadsheet,
    FileText,
    Fuel,
    Gauge,
    MapPin,
    ShieldCheck,
    Truck,
    Wrench,
} from 'lucide-react';
import { useState } from 'react';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import CompanyLayout from '@/layouts/company/company-layout';

interface VehicleOption {
    id: number;
    license_plate: string;
    make: string;
    model: string;
}

interface ReportDefinition {
    id: string;
    title: string;
    description: string;
    icon: typeof Truck;
    requiresDateRange: boolean;
    supportsVehicleFilter: boolean;
    supportsFleetStatusFilters?: boolean;
}

const AVAILABLE_REPORTS: ReportDefinition[] = [
    {
        id: 'fleet-summary',
        title: 'Fleet Status & CAN Diagnostics',
        description: 'Live snapshot of vehicle status, CAN odometer, fuel level, battery voltage, coolant temp, and open DTC faults.',
        icon: Truck,
        requiresDateRange: false,
        supportsVehicleFilter: false,
        supportsFleetStatusFilters: true,
    },
    {
        id: 'trips',
        title: 'Trip & Journey Report',
        description: 'Start/stop addresses, distance (km), driving duration, idle minutes, speeds, and per-trip safety grades.',
        icon: MapPin,
        requiresDateRange: true,
        supportsVehicleFilter: true,
    },
    {
        id: 'driver-scorecard',
        title: 'DriverX 5-Behavior Scorecard',
        description: 'Driver safety rankings across Harsh Braking, Harsh Acceleration, Speeding, Hard Cornering, and Idling.',
        icon: ShieldCheck,
        requiresDateRange: true,
        supportsVehicleFilter: true,
    },
    {
        id: 'alarms',
        title: 'Alarm & Security Log',
        description: 'Historical alerts with severity levels: overspeed, overheating, low battery, geofence breaches, and unplugs.',
        icon: AlertTriangle,
        requiresDateRange: true,
        supportsVehicleFilter: true,
    },
    {
        id: 'idle-utilization',
        title: 'Utilization & Idle Waste Report',
        description: 'Active driving hours vs stationary idle minutes, idle percentage, and estimated idle fuel wastage.',
        icon: Gauge,
        requiresDateRange: true,
        supportsVehicleFilter: true,
    },
    {
        id: 'fuel',
        title: 'Fuel & Spend Report',
        description: 'Refueling logs, total litres dispensed, cost per litre, total fuel spend (₦), and odometer readings.',
        icon: Fuel,
        requiresDateRange: true,
        supportsVehicleFilter: true,
    },
    {
        id: 'maintenance',
        title: 'Maintenance & Health Compliance',
        description: 'Current odometer, open diagnostic faults, and scheduled or completed service entries per vehicle.',
        icon: Wrench,
        requiresDateRange: false,
        supportsVehicleFilter: true,
    },
];

export default function ReportsIndex({
    categories,
    vehicles = [],
}: {
    categories: string[];
    vehicles: VehicleOption[];
}) {
    const [selectedReport, setSelectedReport] = useState<ReportDefinition | null>(null);
    const [format, setFormat] = useState<'pdf' | 'csv'>('pdf');
    const [filters, setFilters] = useState({
        category: 'all',
        status: 'all',
        connectivity: 'all',
        vehicle_id: 'all',
        from: new Date(Date.now() - 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
        to: new Date().toISOString().split('T')[0],
    });

    const openModal = (report: ReportDefinition, targetFormat: 'pdf' | 'csv') => {
        setSelectedReport(report);
        setFormat(targetFormat);
    };

    const download = () => {
        if (!selectedReport) return;

        const params = new URLSearchParams();
        if (selectedReport.requiresDateRange) {
            params.set('from', filters.from);
            params.set('to', filters.to);
        }
        if (selectedReport.supportsVehicleFilter && filters.vehicle_id !== 'all') {
            params.set('vehicle_id', filters.vehicle_id);
        }
        if (filters.category !== 'all') params.set('category', filters.category);

        if (selectedReport.supportsFleetStatusFilters) {
            if (filters.status !== 'all') params.set('status', filters.status);
            if (filters.connectivity !== 'all') params.set('connectivity', filters.connectivity);
        }

        const query = params.toString();
        window.location.href = `/reports/${selectedReport.id}.${format}${query ? `?${query}` : ''}`;
        setSelectedReport(null);
    };

    return (
        <CompanyLayout title="Reports">
            <Head title="Fleet Reports" />

            <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                {AVAILABLE_REPORTS.map((report) => {
                    const Icon = report.icon;
                    return (
                        <Card key={report.id} className="flex flex-col justify-between">
                            <CardHeader>
                                <div className="flex items-center gap-3">
                                    <div className="rounded-lg bg-primary/10 p-2 text-primary">
                                        <Icon className="size-5" />
                                    </div>
                                    <CardTitle className="text-lg">{report.title}</CardTitle>
                                </div>
                                <CardDescription className="pt-2">{report.description}</CardDescription>
                            </CardHeader>
                            <CardContent className="flex gap-2">
                                <Button variant="outline" className="flex-1" onClick={() => openModal(report, 'pdf')}>
                                    <FileText className="mr-2 size-4" /> PDF
                                </Button>
                                <Button variant="outline" className="flex-1" onClick={() => openModal(report, 'csv')}>
                                    <FileSpreadsheet className="mr-2 size-4" /> CSV
                                </Button>
                            </CardContent>
                        </Card>
                    );
                })}
            </div>

            <Dialog open={!!selectedReport} onOpenChange={(open) => !open && setSelectedReport(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            Configure {selectedReport?.title} ({format.toUpperCase()})
                        </DialogTitle>
                        <DialogDescription>
                            Select reporting boundaries and target parameters before exporting.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-4 py-2">
                        {selectedReport?.requiresDateRange && (
                            <div className="grid grid-cols-2 gap-3">
                                <div className="grid gap-2">
                                    <Label className="flex items-center gap-1.5">
                                        <Calendar className="size-3.5" /> Start Date
                                    </Label>
                                    <Input
                                        type="date"
                                        value={filters.from}
                                        onChange={(e) => setFilters((f) => ({ ...f, from: e.target.value }))}
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label className="flex items-center gap-1.5">
                                        <Calendar className="size-3.5" /> End Date
                                    </Label>
                                    <Input
                                        type="date"
                                        value={filters.to}
                                        onChange={(e) => setFilters((f) => ({ ...f, to: e.target.value }))}
                                    />
                                </div>
                            </div>
                        )}

                        {selectedReport?.supportsVehicleFilter && (
                            <div className="grid gap-2">
                                <Label>Target Vehicle</Label>
                                <Select
                                    value={filters.vehicle_id}
                                    onValueChange={(val) => setFilters((f) => ({ ...f, vehicle_id: val }))}
                                >
                                    <SelectTrigger className="w-full">
                                        <SelectValue placeholder="All Fleet Vehicles" />
                                    </SelectTrigger>
                                    <SelectContent position="popper" className="z-[9999] max-h-60">
                                        <SelectItem value="all">All Fleet Vehicles</SelectItem>
                                        {vehicles.map((v) => (
                                            <SelectItem key={v.id} value={String(v.id)}>
                                                {v.license_plate} — {v.make} {v.model}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                        )}

                        {selectedReport?.supportsFleetStatusFilters && (
                            <div className="grid grid-cols-2 gap-3">
                                <div className="grid gap-2">
                                    <Label>Vehicle Status</Label>
                                    <Select
                                        value={filters.status}
                                        onValueChange={(val) => setFilters((f) => ({ ...f, status: val }))}
                                    >
                                        <SelectTrigger className="w-full">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent position="popper" className="z-[9999]">
                                            <SelectItem value="all">All Statuses</SelectItem>
                                            <SelectItem value="active">Active</SelectItem>
                                            <SelectItem value="maintenance">In Maintenance</SelectItem>
                                            <SelectItem value="inactive">Inactive</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="grid gap-2">
                                    <Label>Connectivity</Label>
                                    <Select
                                        value={filters.connectivity}
                                        onValueChange={(val) => setFilters((f) => ({ ...f, connectivity: val }))}
                                    >
                                        <SelectTrigger className="w-full">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent position="popper" className="z-[9999]">
                                            <SelectItem value="all">All</SelectItem>
                                            <SelectItem value="online">Online Only</SelectItem>
                                            <SelectItem value="offline">Offline Only</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>
                        )}

                        <div className="grid gap-2">
                            <Label>Category</Label>
                            <Select
                                value={filters.category}
                                onValueChange={(val) => setFilters((f) => ({ ...f, category: val }))}
                            >
                                <SelectTrigger className="w-full">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent position="popper" className="z-[9999] max-h-60">
                                    <SelectItem value="all">All categories</SelectItem>
                                    {(categories ?? [])
                                        .filter((cat) => typeof cat === 'string' && cat.trim() !== '')
                                        .map((cat) => (
                                            <SelectItem key={cat} value={cat} className="capitalize">
                                                {cat}
                                            </SelectItem>
                                        ))}
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <DialogFooter>
                        <Button variant="outline" onClick={() => setSelectedReport(null)}>
                            Cancel
                        </Button>
                        <Button onClick={download} className="bg-brand-navy text-white hover:bg-brand-navy/90">
                            Download {format.toUpperCase()}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </CompanyLayout>
    );
}