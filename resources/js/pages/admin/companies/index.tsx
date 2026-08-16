import { Head, router, useForm } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import {  useState } from 'react';
import type {FormEvent} from 'react';

import { Pagination } from '@/components/company/pagination';
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
import AdminLayout from '@/layouts/admin/admin-layout';
import type { Paginated } from '@/types/pagination';

type CompanyRow = {
    id: number;
    name: string;
    slug: string;
    email: string | null;
    phone: string | null;
    vehiclesCount: number;
    usersCount: number;
    createdAt: string | null;
};

type EditFormValues = {
    name: string;
    email: string;
    phone: string;
    website: string;
};

function formatDate(value: string | null) {
    if (!value) {
return '—';
}

    return new Date(value).toLocaleDateString(undefined, { dateStyle: 'medium' });
}

function CreateCompanyDialog({ open, onOpenChange }: { open: boolean; onOpenChange: (open: boolean) => void }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        email: '',
        phone: '',
        website: '',
        admin_first_name: '',
        admin_last_name: '',
        admin_email: '',
        admin_password: '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post('/admin/companies', {
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
                    New company
                </Button>
            </DialogTrigger>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Create company</DialogTitle>
                    <DialogDescription>Creates the tenant and its first company-admin login.</DialogDescription>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    <div className="grid gap-2">
                        <Label htmlFor="name">Company name</Label>
                        <Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} />
                        {errors.name ? <p className="text-xs text-destructive">{errors.name}</p> : null}
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <div className="grid gap-2">
                            <Label htmlFor="email">Company email</Label>
                            <Input id="email" type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="phone">Company phone</Label>
                            <Input id="phone" value={data.phone} onChange={(e) => setData('phone', e.target.value)} />
                        </div>
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="website">Website</Label>
                        <Input id="website" value={data.website} onChange={(e) => setData('website', e.target.value)} />
                    </div>

                    <div className="space-y-3 rounded-lg border border-dashed border-border p-4">
                        <p className="text-xs font-medium text-muted-foreground uppercase">First company admin</p>
                        <div className="grid grid-cols-2 gap-4">
                            <div className="grid gap-2">
                                <Label htmlFor="admin_first_name">First name</Label>
                                <Input
                                    id="admin_first_name"
                                    value={data.admin_first_name}
                                    onChange={(e) => setData('admin_first_name', e.target.value)}
                                />
                                {errors.admin_first_name ? <p className="text-xs text-destructive">{errors.admin_first_name}</p> : null}
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="admin_last_name">Last name</Label>
                                <Input
                                    id="admin_last_name"
                                    value={data.admin_last_name}
                                    onChange={(e) => setData('admin_last_name', e.target.value)}
                                />
                                {errors.admin_last_name ? <p className="text-xs text-destructive">{errors.admin_last_name}</p> : null}
                            </div>
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="admin_email">Admin email</Label>
                            <Input id="admin_email" type="email" value={data.admin_email} onChange={(e) => setData('admin_email', e.target.value)} />
                            {errors.admin_email ? <p className="text-xs text-destructive">{errors.admin_email}</p> : null}
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="admin_password">Initial password</Label>
                            <Input
                                id="admin_password"
                                type="password"
                                value={data.admin_password}
                                onChange={(e) => setData('admin_password', e.target.value)}
                            />
                            {errors.admin_password ? <p className="text-xs text-destructive">{errors.admin_password}</p> : null}
                        </div>
                    </div>

                    <DialogFooter>
                        <Button
                            type="submit"
                            loading={processing}
                            className="bg-brand-navy text-white hover:bg-brand-navy/90 dark:bg-brand-green dark:text-brand-navy"
                        >
                            Create company
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function EditCompanyDialog({ company, onOpenChange }: { company: CompanyRow | null; onOpenChange: (open: boolean) => void }) {
    const { data, setData, patch, processing, errors, reset } = useForm<EditFormValues>({
        name: company?.name ?? '',
        email: company?.email ?? '',
        phone: company?.phone ?? '',
        website: '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();

        if (!company) {
return;
}

        patch(`/admin/companies/${company.id}`, {
            onSuccess: () => {
                reset();
                onOpenChange(false);
            },
        });
    };

    return (
        <Dialog open={!!company} onOpenChange={(open) => !open && onOpenChange(false)}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Edit company</DialogTitle>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    <div className="grid gap-2">
                        <Label htmlFor="edit_name">Name</Label>
                        <Input id="edit_name" value={data.name} onChange={(e) => setData('name', e.target.value)} />
                        {errors.name ? <p className="text-xs text-destructive">{errors.name}</p> : null}
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="edit_email">Email</Label>
                        <Input id="edit_email" type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="edit_phone">Phone</Label>
                        <Input id="edit_phone" value={data.phone} onChange={(e) => setData('phone', e.target.value)} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="edit_website">Website</Label>
                        <Input id="edit_website" value={data.website} onChange={(e) => setData('website', e.target.value)} />
                    </div>
                    <DialogFooter>
                        <Button
                            type="submit"
                            loading={processing}
                            className="bg-brand-navy text-white hover:bg-brand-navy/90 dark:bg-brand-green dark:text-brand-navy"
                        >
                            Save changes
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function AdminCompaniesIndex({ companies }: { companies: Paginated<CompanyRow> }) {
    const [createOpen, setCreateOpen] = useState(false);
    const [editing, setEditing] = useState<CompanyRow | null>(null);
    const [deleting, setDeleting] = useState<CompanyRow | null>(null);
    const [deleteInFlight, setDeleteInFlight] = useState(false);

    const confirmDelete = () => {
        if (!deleting) {
return;
}

        setDeleteInFlight(true);
        router.delete(`/admin/companies/${deleting.id}`, {
            onFinish: () => {
                setDeleteInFlight(false);
                setDeleting(null);
            },
        });
    };

    return (
        <AdminLayout title="Companies">
            <Head title="Companies" />

            <div className="flex justify-end">
                <CreateCompanyDialog open={createOpen} onOpenChange={setCreateOpen} />
            </div>

            <Card className="overflow-hidden py-0">
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead className="border-b bg-muted/40 text-left text-xs tracking-wide text-muted-foreground uppercase">
                            <tr>
                                <th className="px-6 py-3 font-medium">Name</th>
                                <th className="px-6 py-3 font-medium">Subdomain</th>
                                <th className="px-6 py-3 font-medium">Contact</th>
                                <th className="px-6 py-3 font-medium">Vehicles</th>
                                <th className="px-6 py-3 font-medium">Users</th>
                                <th className="px-6 py-3 font-medium">Created</th>
                                <th className="px-6 py-3 font-medium"></th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-border">
                            {companies.data.map((company) => (
                                <tr key={company.id} className="hover:bg-muted/40">
                                    <td className="px-6 py-3 font-medium text-foreground">{company.name}</td>
                                    <td className="px-6 py-3 text-muted-foreground">{company.slug}</td>
                                    <td className="px-6 py-3 text-muted-foreground">{company.email ?? '—'}</td>
                                    <td className="px-6 py-3 text-muted-foreground">{company.vehiclesCount}</td>
                                    <td className="px-6 py-3 text-muted-foreground">{company.usersCount}</td>
                                    <td className="px-6 py-3 text-muted-foreground">{formatDate(company.createdAt)}</td>
                                    <td className="px-6 py-3 text-right">
                                        <div className="flex justify-end gap-2">
                                            <Button size="sm" variant="outline" onClick={() => setEditing(company)}>
                                                Edit
                                            </Button>
                                            <Button size="sm" variant="outline" onClick={() => setDeleting(company)}>
                                                <Trash2 className="size-4 text-destructive" />
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            ))}

                            {companies.data.length === 0 ? (
                                <tr>
                                    <td colSpan={7} className="px-6 py-10 text-center text-muted-foreground">
                                        No companies yet.
                                    </td>
                                </tr>
                            ) : null}
                        </tbody>
                    </table>
                </div>
                <Pagination links={companies.links} from={companies.from} to={companies.to} total={companies.total} />
            </Card>

            <EditCompanyDialog company={editing} onOpenChange={(open) => !open && setEditing(null)} />

            <Dialog open={!!deleting} onOpenChange={(open) => !open && setDeleting(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Remove {deleting?.name}?</DialogTitle>
                        <DialogDescription>
                            This permanently deletes the company along with its vehicles, staff, and all related records. This can&apos;t be undone.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setDeleting(null)}>
                            Cancel
                        </Button>
                        <Button variant="destructive" loading={deleteInFlight} onClick={confirmDelete}>
                            Remove company
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AdminLayout>
    );
}
