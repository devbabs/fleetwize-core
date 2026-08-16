import { Head, router, useForm } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import {  useEffect, useState } from 'react';
import type {FormEvent} from 'react';

import { Pagination } from '@/components/company/pagination';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AdminLayout from '@/layouts/admin/admin-layout';
import type { Paginated } from '@/types/pagination';

type AgentRow = {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    customersCount: number;
    vehiclesCount: number;
    createdAt: string | null;
};

function formatDate(value: string | null) {
    if (!value) {
return '—';
}

    return new Date(value).toLocaleDateString(undefined, { dateStyle: 'medium' });
}

function CreateAgentDialog({ open, onOpenChange }: { open: boolean; onOpenChange: (open: boolean) => void }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        first_name: '',
        last_name: '',
        email: '',
        phone: '',
        password: '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post('/admin/agents', {
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
                    New agent
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Add agent</DialogTitle>
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
                    <DialogFooter>
                        <Button
                            type="submit"
                            loading={processing}
                            className="bg-brand-navy text-white hover:bg-brand-navy/90 dark:bg-brand-green dark:text-brand-navy"
                        >
                            Add agent
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function EditAgentDialog({ agent, onOpenChange }: { agent: AgentRow | null; onOpenChange: (open: boolean) => void }) {
    const [first, last] = agent ? agent.name.split(/\s+/, 2) : ['', ''];
    const { data, setData, patch, processing, errors, reset, setDefaults } = useForm({
        first_name: first ?? '',
        last_name: last ?? '',
        email: agent?.email ?? '',
        phone: agent?.phone ?? '',
    });

    useEffect(() => {
        if (agent) {
            const [f, l] = agent.name.split(/\s+/, 2);
            const values = { first_name: f ?? '', last_name: l ?? '', email: agent.email, phone: agent.phone ?? '' };
            setDefaults(values);
            setData(values);
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [agent?.id]);

    const submit = (e: FormEvent) => {
        e.preventDefault();

        if (!agent) {
return;
}

        patch(`/admin/agents/${agent.id}`, {
            onSuccess: () => {
                reset();
                onOpenChange(false);
            },
        });
    };

    return (
        <Dialog open={!!agent} onOpenChange={(open) => !open && onOpenChange(false)}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Edit agent</DialogTitle>
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

export default function AdminAgentsIndex({ agents }: { agents: Paginated<AgentRow> }) {
    const [createOpen, setCreateOpen] = useState(false);
    const [editing, setEditing] = useState<AgentRow | null>(null);
    const [deleting, setDeleting] = useState<AgentRow | null>(null);
    const [deleteInFlight, setDeleteInFlight] = useState(false);

    const confirmDelete = () => {
        if (!deleting) {
return;
}

        setDeleteInFlight(true);
        router.delete(`/admin/agents/${deleting.id}`, {
            onFinish: () => {
                setDeleteInFlight(false);
                setDeleting(null);
            },
        });
    };

    return (
        <AdminLayout title="Agents">
            <Head title="Agents" />

            <div className="flex justify-end">
                <CreateAgentDialog open={createOpen} onOpenChange={setCreateOpen} />
            </div>

            <Card className="overflow-hidden py-0">
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead className="border-b bg-muted/40 text-left text-xs tracking-wide text-muted-foreground uppercase">
                            <tr>
                                <th className="px-6 py-3 font-medium">Name</th>
                                <th className="px-6 py-3 font-medium">Contact</th>
                                <th className="px-6 py-3 font-medium">Customers</th>
                                <th className="px-6 py-3 font-medium">Vehicles</th>
                                <th className="px-6 py-3 font-medium">Since</th>
                                <th className="px-6 py-3 font-medium"></th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-border">
                            {agents.data.map((agent) => (
                                <tr key={agent.id} className="hover:bg-muted/40">
                                    <td className="px-6 py-3 font-medium text-foreground">{agent.name}</td>
                                    <td className="px-6 py-3 text-muted-foreground">
                                        <div>{agent.email}</div>
                                        {agent.phone ? <div className="text-xs">{agent.phone}</div> : null}
                                    </td>
                                    <td className="px-6 py-3 text-muted-foreground">{agent.customersCount}</td>
                                    <td className="px-6 py-3 text-muted-foreground">{agent.vehiclesCount}</td>
                                    <td className="px-6 py-3 text-muted-foreground">{formatDate(agent.createdAt)}</td>
                                    <td className="px-6 py-3 text-right">
                                        <div className="flex justify-end gap-2">
                                            <Button size="sm" variant="outline" onClick={() => setEditing(agent)}>
                                                Edit
                                            </Button>
                                            <Button size="sm" variant="outline" onClick={() => setDeleting(agent)}>
                                                <Trash2 className="size-4 text-destructive" />
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            ))}

                            {agents.data.length === 0 ? (
                                <tr>
                                    <td colSpan={6} className="px-6 py-10 text-center text-muted-foreground">
                                        No agents yet.
                                    </td>
                                </tr>
                            ) : null}
                        </tbody>
                    </table>
                </div>
                <Pagination links={agents.links} from={agents.from} to={agents.to} total={agents.total} />
            </Card>

            <EditAgentDialog agent={editing} onOpenChange={(open) => !open && setEditing(null)} />

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
                            Remove agent
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AdminLayout>
    );
}
