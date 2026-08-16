import { Head } from '@inertiajs/react';
import { FileSpreadsheet, FileText } from 'lucide-react';
import { useState } from 'react';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import CompanyLayout from '@/layouts/company/company-layout';

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

export default function ReportsIndex({ categories }: { categories: string[] }) {
    const [open, setOpen] = useState(false);
    const [format, setFormat] = useState<'pdf' | 'csv'>('pdf');
    const [filters, setFilters] = useState<Filters>({ category: 'all', status: 'all', connectivity: 'all' });

    const openDialog = (target: 'pdf' | 'csv') => {
        setFormat(target);
        setOpen(true);
    };

    const download = () => {
        const url = `/reports/vehicles.${format}${buildQuery(filters)}`;
        window.location.href = url;
        setOpen(false);
    };

    return (
        <CompanyLayout title="Reports">
            <Head title="Reports" />

            <Card className="max-w-xl">
                <CardHeader>
                    <CardTitle>Fleet Summary</CardTitle>
                    <CardDescription>Every vehicle&apos;s status, mileage, tracker connectivity, and open faults.</CardDescription>
                </CardHeader>
                <CardContent className="flex flex-wrap gap-3">
                    <Button variant="outline" onClick={() => openDialog('pdf')}>
                        <FileText className="size-4" />
                        Download PDF
                    </Button>
                    <Button variant="outline" onClick={() => openDialog('csv')}>
                        <FileSpreadsheet className="size-4" />
                        Download CSV
                    </Button>
                </CardContent>
            </Card>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Filter report ({format.toUpperCase()})</DialogTitle>
                        <DialogDescription>Narrow the fleet summary before downloading. Leave as "All" to include everything.</DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-4">
                        <div className="grid gap-2">
                            <Label>Category</Label>
                            <Select value={filters.category} onValueChange={(value) => setFilters((f) => ({ ...f, category: value }))}>
                                <SelectTrigger className="w-full">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All categories</SelectItem>
                                    {categories.map((category) => (
                                        <SelectItem key={category} value={category} className="capitalize">
                                            {category}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="grid gap-2">
                            <Label>Status</Label>
                            <Select value={filters.status} onValueChange={(value) => setFilters((f) => ({ ...f, status: value }))}>
                                <SelectTrigger className="w-full">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All statuses</SelectItem>
                                    <SelectItem value="active">Active</SelectItem>
                                    <SelectItem value="maintenance">Maintenance</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="grid gap-2">
                            <Label>Tracker connectivity</Label>
                            <Select value={filters.connectivity} onValueChange={(value) => setFilters((f) => ({ ...f, connectivity: value }))}>
                                <SelectTrigger className="w-full">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Online + Offline</SelectItem>
                                    <SelectItem value="online">Online only</SelectItem>
                                    <SelectItem value="offline">Offline only</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <DialogFooter>
                        <Button variant="outline" onClick={() => setOpen(false)}>
                            Cancel
                        </Button>
                        <Button onClick={download} className="bg-brand-navy text-white hover:bg-brand-navy/90 dark:bg-brand-green dark:text-brand-navy">
                            Download
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </CompanyLayout>
    );
}
