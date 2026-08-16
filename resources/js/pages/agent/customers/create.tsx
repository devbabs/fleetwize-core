import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AgentLayout from '@/layouts/agent/agent-layout';

type FormValues = {
    first_name: string;
    last_name: string;
    email: string;
    phone: string;
    password: string;
    license_plate: string;
    make: string;
    model: string;
    year: string;
    color: string;
    category: string;
    vin: string;
    obd_device_id: string;
    obd_device_imei: string;
    tracker_phone_number: string;
};

export default function CreateCustomer() {
    const { data, setData, post, processing, errors } = useForm<FormValues>({
        first_name: '',
        last_name: '',
        email: '',
        phone: '',
        password: '',
        license_plate: '',
        make: '',
        model: '',
        year: '',
        color: '',
        category: 'car',
        vin: '',
        obd_device_id: '',
        obd_device_imei: '',
        tracker_phone_number: '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post('/agent/customers');
    };

    return (
        <AgentLayout title="Onboard Customer">
            <Head title="Onboard Customer" />

            <form onSubmit={submit} className="space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle>Customer details</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-4 sm:grid-cols-2">
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
                        <div className="grid gap-2">
                            <Label htmlFor="email">Email</Label>
                            <Input id="email" type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} />
                            {errors.email ? <p className="text-xs text-destructive">{errors.email}</p> : null}
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="phone">Phone</Label>
                            <Input id="phone" value={data.phone} onChange={(e) => setData('phone', e.target.value)} />
                        </div>
                        <div className="grid gap-2 sm:col-span-2">
                            <Label htmlFor="password">Initial mobile app password</Label>
                            <Input id="password" type="password" value={data.password} onChange={(e) => setData('password', e.target.value)} />
                            {errors.password ? <p className="text-xs text-destructive">{errors.password}</p> : null}
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Vehicle</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-4 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="license_plate">License plate</Label>
                            <Input id="license_plate" value={data.license_plate} onChange={(e) => setData('license_plate', e.target.value)} />
                            {errors.license_plate ? <p className="text-xs text-destructive">{errors.license_plate}</p> : null}
                        </div>
                        <div className="grid gap-2">
                            <Label>Category</Label>
                            <Select value={data.category} onValueChange={(value) => setData('category', value)}>
                                <SelectTrigger className="w-full">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="car">Car</SelectItem>
                                    <SelectItem value="van">Van</SelectItem>
                                    <SelectItem value="truck">Truck</SelectItem>
                                    <SelectItem value="motorcycle">Motorcycle</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="make">Make</Label>
                            <Input id="make" value={data.make} onChange={(e) => setData('make', e.target.value)} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="model">Model</Label>
                            <Input id="model" value={data.model} onChange={(e) => setData('model', e.target.value)} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="year">Year</Label>
                            <Input id="year" value={data.year} onChange={(e) => setData('year', e.target.value)} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="color">Color</Label>
                            <Input id="color" value={data.color} onChange={(e) => setData('color', e.target.value)} />
                        </div>
                        <div className="grid gap-2 sm:col-span-2">
                            <Label htmlFor="vin">VIN</Label>
                            <Input id="vin" value={data.vin} onChange={(e) => setData('vin', e.target.value)} />
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Tracker (optional)</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-4 sm:grid-cols-2">
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
                    </CardContent>
                </Card>

                <Button
                    type="submit"
                    loading={processing}
                    className="bg-brand-navy text-white hover:bg-brand-navy/90 dark:bg-brand-green dark:text-brand-navy"
                >
                    Onboard customer
                </Button>
            </form>
        </AgentLayout>
    );
}
