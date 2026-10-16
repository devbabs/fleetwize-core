import { Head } from '@inertiajs/react';
import { Calendar, FileSpreadsheet, FileText, Truck, Gauge, MapPin, ShieldCheck, AlertTriangle } from 'lucide-react';
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
}

const AVAILABLE_REPORTS: ReportDefinition[] = [
    {
        id: 'fleet-summary',
        title: 'Fleet Status Summary',
        description: "Point-in-time snapshot of vehicle status, odometer, tracking state, and active DTC faults.",
        icon: Truck,
        requiresDateRange: false,
        supportsVehicleFilter: false,
    },
    {
        id: 'trips',
        title: 'Trip & Journey Report',
        description: 'Start/stop locations, travel distance (km), trip runtimes, and max/average speeds.',
        icon: MapPin,
        requiresDateRange: true,
        supportsVehicleFilter: true,
    },
    {
        id: 'driver-scorecard',
        title: 'DriverX Behavior Scorecard',
        description: 'Driver safety rankings across harsh braking, cornering, rapid acceleration, and speeding.',
        icon: ShieldCheck,
        requiresDateRange: true,
        supportsVehicleFilter: true,
    },
    {
        id: 'alarms',
        title: 'Alarm & Security Log',
        description: 'Historical alert events: overspeed triggers, geo-fence breaches, and tracker disconnects.',
        icon: AlertTriangle,
        requiresDateRange: true,
        supportsVehicleFilter: true,
    },
    {
        id: 'idle-utilization',
        title: 'Utilization & Idle Report',
        description: 'Active operating hours vs stationary engine idling to monitor driver delay and fuel waste.',
        icon: Gauge,
        requiresDateRange: true,
        supportsVehicleFilter: true,
    },
];

type Filters = {
    category: string;
    status: string;
    connectivity: string;
};

function buildQuery(filters: Filters) {
    const params = new URLSearchParams();

    if (filters.category !== 'all') {
params.set('category', filters.category);
}

    if (filters.status !== 'all') {
params.set('status', filters.status);
}

    if (filters.connectivity !== 'all') {
params.set('connectivity', filters.connectivity);
}

    const query = params.toString();

    return query ? `?${query}` : '';
}

// export default function ReportsIndex({ categories }: { categories: string[] }) {
//     const [open, setOpen] = useState(false);
//     const [format, setFormat] = useState<'pdf' | 'csv'>('pdf');
//     const [filters, setFilters] = useState<Filters>({ category: 'all', status: 'all', connectivity: 'all' });

//     const openDialog = (target: 'pdf' | 'csv') => {
//         setFormat(target);
//         setOpen(true);
//     };

//     const download = () => {
//         const url = `/reports/vehicles.${format}${buildQuery(filters)}`;
//         window.location.href = url;
//         setOpen(false);
//     };

//     return (
//         <CompanyLayout title="Reports">
//             <Head title="Reports" />

//             <Card className="max-w-xl">
//                 <CardHeader>
//                     <CardTitle>Fleet Summary</CardTitle>
//                     <CardDescription>Every vehicle&apos;s status, mileage, tracker connectivity, and open faults.</CardDescription>
//                 </CardHeader>
//                 <CardContent className="flex flex-wrap gap-3">
//                     <Button variant="outline" onClick={() => openDialog('pdf')}>
//                         <FileText className="size-4" />
//                         Download PDF
//                     </Button>
//                     <Button variant="outline" onClick={() => openDialog('csv')}>
//                         <FileSpreadsheet className="size-4" />
//                         Download CSV
//                     </Button>
//                 </CardContent>
//             </Card>

//             <Dialog open={open} onOpenChange={setOpen}>
//                 <DialogContent>
//                     <DialogHeader>
//                         <DialogTitle>Filter report ({format.toUpperCase()})</DialogTitle>
//                         <DialogDescription>Narrow the fleet summary before downloading. Leave as "All" to include everything.</DialogDescription>
//                     </DialogHeader>

//                     <div className="grid gap-4">
//                         <div className="grid gap-2">
//                             <Label>Category</Label>
//                             <Select value={filters.category} onValueChange={(value) => setFilters((f) => ({ ...f, category: value }))}>
//                                 <SelectTrigger className="w-full">
//                                     <SelectValue />
//                                 </SelectTrigger>
//                                 <SelectContent>
//                                     <SelectItem value="all">All categories</SelectItem>
//                                     {categories.map((category) => (
//                                         <SelectItem key={category} value={category} className="capitalize">
//                                             {category}
//                                         </SelectItem>
//                                     ))}
//                                 </SelectContent>
//                             </Select>
//                         </div>

//                         <div className="grid gap-2">
//                             <Label>Status</Label>
//                             <Select value={filters.status} onValueChange={(value) => setFilters((f) => ({ ...f, status: value }))}>
//                                 <SelectTrigger className="w-full">
//                                     <SelectValue />
//                                 </SelectTrigger>
//                                 <SelectContent>
//                                     <SelectItem value="all">All statuses</SelectItem>
//                                     <SelectItem value="active">Active</SelectItem>
//                                     <SelectItem value="maintenance">Maintenance</SelectItem>
//                                 </SelectContent>
//                             </Select>
//                         </div>

//                         <div className="grid gap-2">
//                             <Label>Tracker connectivity</Label>
//                             <Select value={filters.connectivity} onValueChange={(value) => setFilters((f) => ({ ...f, connectivity: value }))}>
//                                 <SelectTrigger className="w-full">
//                                     <SelectValue />
//                                 </SelectTrigger>
//                                 <SelectContent>
//                                     <SelectItem value="all">Online + Offline</SelectItem>
//                                     <SelectItem value="online">Online only</SelectItem>
//                                     <SelectItem value="offline">Offline only</SelectItem>
//                                 </SelectContent>
//                             </Select>
//                         </div>
//                     </div>

//                     <DialogFooter>
//                         <Button variant="outline" onClick={() => setOpen(false)}>
//                             Cancel
//                         </Button>
//                         <Button onClick={download} className="bg-brand-navy text-white hover:bg-brand-navy/90 dark:bg-brand-green dark:text-brand-navy">
//                             Download
//                         </Button>
//                     </DialogFooter>
//                 </DialogContent>
//             </Dialog>
//         </CompanyLayout>
//     );
// }
export default function ReportsIndex({ 
    categories, 
    vehicles = [] 
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
        if (filters.status !== 'all') params.set('status', filters.status);
        if (filters.connectivity !== 'all') params.set('connectivity', filters.connectivity);

        window.location.href = `/reports/${selectedReport.id}.${format}?${params.toString()}`;
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
                                    <SelectContent>
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

                        <div className="grid gap-2">
                            <Label>Category</Label>
                            <Select
                                value={filters.category}
                                onValueChange={(val) => setFilters((f) => ({ ...f, category: val }))}
                            >
                                <SelectTrigger className="w-full">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All categories</SelectItem>
                                    {categories.map((cat) => (
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