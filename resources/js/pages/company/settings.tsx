import { Head, useForm } from '@inertiajs/react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import CompanyLayout from '@/layouts/company/company-layout';

type CompanyProfile = {
    name: string;
    email: string | null;
    phone: string | null;
    website: string | null;
};

type StaffRow = {
    id: number;
    name: string;
    email: string;
    role: string;
};

export default function CompanySettings({ companyProfile, staff }: { companyProfile: CompanyProfile; staff: StaffRow[] }) {
    const { data, setData, patch, processing, errors } = useForm({
        name: companyProfile.name,
        email: companyProfile.email ?? '',
        phone: companyProfile.phone ?? '',
        website: companyProfile.website ?? '',
    });

    return (
        <CompanyLayout title="Settings">
            <Head title="Settings" />

            <Card className="max-w-2xl">
                <CardHeader>
                    <CardTitle>Company Profile</CardTitle>
                </CardHeader>
                <CardContent>
                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            patch('/settings');
                        }}
                        className="space-y-4"
                    >
                        <div className="grid gap-2">
                            <Label htmlFor="name">Company name</Label>
                            <Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} />
                            {errors.name ? <p className="text-xs text-destructive">{errors.name}</p> : null}
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="email">Email</Label>
                            <Input id="email" type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} />
                            {errors.email ? <p className="text-xs text-destructive">{errors.email}</p> : null}
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="phone">Phone</Label>
                            <Input id="phone" value={data.phone} onChange={(e) => setData('phone', e.target.value)} />
                            {errors.phone ? <p className="text-xs text-destructive">{errors.phone}</p> : null}
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="website">Website</Label>
                            <Input id="website" value={data.website} onChange={(e) => setData('website', e.target.value)} />
                            {errors.website ? <p className="text-xs text-destructive">{errors.website}</p> : null}
                        </div>

                        <Button
                            type="submit"
                            loading={processing}
                            className="bg-brand-navy text-white hover:bg-brand-navy/90 dark:bg-brand-green dark:text-brand-navy"
                        >
                            Save changes
                        </Button>
                    </form>
                </CardContent>
            </Card>

            <Card className="max-w-2xl overflow-hidden py-0">
                <CardHeader className="pt-6">
                    <CardTitle>Staff</CardTitle>
                </CardHeader>
                <CardContent className="px-0">
                    <div className="divide-y divide-border">
                        {staff.map((member) => (
                            <div key={member.id} className="flex items-center justify-between px-6 py-3">
                                <div>
                                    <p className="text-sm font-medium text-foreground">{member.name}</p>
                                    <p className="text-xs text-muted-foreground">{member.email}</p>
                                </div>
                                <Badge variant="outline" className="capitalize">
                                    {member.role.replace('-', ' ')}
                                </Badge>
                            </div>
                        ))}
                    </div>
                </CardContent>
            </Card>
        </CompanyLayout>
    );
}
