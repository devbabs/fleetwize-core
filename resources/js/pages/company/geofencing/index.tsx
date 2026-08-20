import { Head, router, useForm } from '@inertiajs/react';
import type * as LeafletNamespace from 'leaflet';
import 'leaflet/dist/leaflet.css';
import 'leaflet-draw/dist/leaflet.draw.css';
import { Trash2 } from 'lucide-react';
import { useEffect, useState } from 'react';
import type { FormEvent } from 'react';
import type {
    Circle as CircleT,
    CircleMarker as CircleMarkerT,
    MapContainer as MapContainerT,
    Polygon as PolygonT,
    Popup as PopupT,
    TileLayer as TileLayerT,
    useMap as useMapT,
} from 'react-leaflet';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import CompanyLayout from '@/layouts/company/company-layout';
import { cn } from '@/lib/utils';

type VehicleOption = { id: number; label: string };

type GeofenceRow = {
    id: number;
    name: string;
    shape: 'circle' | 'polygon';
    centerLatitude: number | null;
    centerLongitude: number | null;
    radiusMeters: number | null;
    polygon: [number, number][] | null;
    color: string | null;
    synced: boolean;
    vehicleIds: number[];
    vehicles: VehicleOption[];
};

type DrawnShape = {
    shape: 'circle' | 'polygon';
    centerLatitude: number | null;
    centerLongitude: number | null;
    radiusMeters: number | null;
    polygon: [number, number][] | null;
};

const PRESET_COLORS = ['#2f6f4f', '#2563eb', '#d97706', '#dc2626', '#7c3aed', '#0891b2'];
const LAGOS_CENTER: [number, number] = [6.5244, 3.3792];

// The whole Leaflet stack (react-leaflet, raw leaflet, leaflet-draw) touches
// `window` at import time, breaking Inertia's SSR pass — loaded dynamically
// in an effect, same approach as the Live Tracking page.
type LeafletBag = {
    L: typeof LeafletNamespace;
    MapContainer: typeof MapContainerT;
    TileLayer: typeof TileLayerT;
    CircleMarker: typeof CircleMarkerT;
    Circle: typeof CircleT;
    Polygon: typeof PolygonT;
    Popup: typeof PopupT;
    useMap: typeof useMapT;
};

/**
 * Wires leaflet-draw imperatively (no React wrapper — react-leaflet-draw
 * predates react-leaflet v5 and its compatibility is unconfirmed) onto the
 * map instance: adds the draw toolbar, and forwards each finished shape's
 * geometry up rather than keeping the raw drawn layer around, since the
 * saved geofence re-renders as a normal Circle/Polygon once the create
 * dialog succeeds.
 */
function DrawControl({ leaflet, onCreated }: { leaflet: LeafletBag; onCreated: (shape: DrawnShape) => void }) {
    const map = leaflet.useMap();

    useEffect(() => {
        // leaflet-draw patches the global Leaflet namespace at runtime (via
        // the side-effect import in the page's loading effect) with
        // Control.Draw / Draw.Event, which @types/leaflet-draw's ambient
        // augmentation may or may not pick up in a dynamic-import setup —
        // loosely typed here rather than depending on that.
        const draw = leaflet.L as any;

        const drawnItems = new draw.FeatureGroup();
        map.addLayer(drawnItems);

        const control = new draw.Control.Draw({
            position: 'topright',
            draw: {
                circle: { shapeOptions: { color: '#2f6f4f' } },
                polygon: { shapeOptions: { color: '#2f6f4f' }, allowIntersection: false },
                polyline: false,
                rectangle: false,
                marker: false,
                circlemarker: false,
            },
            edit: false,
        });
        map.addControl(control);

        const handleCreated = (e: any) => {
            if (e.layerType === 'circle') {
                const center = e.layer.getLatLng();
                onCreated({
                    shape: 'circle',
                    centerLatitude: center.lat,
                    centerLongitude: center.lng,
                    radiusMeters: e.layer.getRadius(),
                    polygon: null,
                });
            } else if (e.layerType === 'polygon') {
                const points: [number, number][] = e.layer
                    .getLatLngs()[0]
                    .map((p: { lat: number; lng: number }) => [p.lat, p.lng]);
                onCreated({ shape: 'polygon', centerLatitude: null, centerLongitude: null, radiusMeters: null, polygon: points });
            }
        };

        map.on(draw.Draw.Event.CREATED, handleCreated);

        return () => {
            map.off(draw.Draw.Event.CREATED, handleCreated);
            map.removeControl(control);
            map.removeLayer(drawnItems);
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    return null;
}

function ColorSwatches({ value, onChange }: { value: string; onChange: (color: string) => void }) {
    return (
        <div className="flex gap-2">
            {PRESET_COLORS.map((color) => (
                <button
                    key={color}
                    type="button"
                    onClick={() => onChange(color)}
                    className={cn('size-6 rounded-full border-2', value === color ? 'border-foreground' : 'border-transparent')}
                    style={{ backgroundColor: color }}
                />
            ))}
        </div>
    );
}

function VehicleChecklist({
    vehicleOptions,
    selected,
    onToggle,
}: {
    vehicleOptions: VehicleOption[];
    selected: number[];
    onToggle: (id: number) => void;
}) {
    return (
        <div className="max-h-40 space-y-2 overflow-y-auto rounded-md border p-3">
            {vehicleOptions.length === 0 ? (
                <p className="text-xs text-muted-foreground">No vehicles yet.</p>
            ) : (
                vehicleOptions.map((vehicle) => (
                    <label key={vehicle.id} className="flex items-center gap-2 text-sm">
                        <Checkbox checked={selected.includes(vehicle.id)} onCheckedChange={() => onToggle(vehicle.id)} />
                        {vehicle.label}
                    </label>
                ))
            )}
        </div>
    );
}

function CreateGeofenceDialog({
    shape,
    vehicleOptions,
    onOpenChange,
}: {
    shape: DrawnShape | null;
    vehicleOptions: VehicleOption[];
    onOpenChange: (open: boolean) => void;
}) {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        shape: 'circle' as 'circle' | 'polygon',
        color: PRESET_COLORS[0],
        center_latitude: null as number | null,
        center_longitude: null as number | null,
        radius_meters: null as number | null,
        polygon: null as [number, number][] | null,
        vehicle_ids: [] as number[],
    });

    useEffect(() => {
        if (shape) {
            setData((prev) => ({
                ...prev,
                shape: shape.shape,
                center_latitude: shape.centerLatitude,
                center_longitude: shape.centerLongitude,
                radius_meters: shape.radiusMeters,
                polygon: shape.polygon,
            }));
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [shape]);

    const toggleVehicle = (id: number) => {
        setData('vehicle_ids', data.vehicle_ids.includes(id) ? data.vehicle_ids.filter((v) => v !== id) : [...data.vehicle_ids, id]);
    };

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post('/geofences', {
            onSuccess: () => {
                reset();
                onOpenChange(false);
            },
        });
    };

    return (
        <Dialog open={!!shape} onOpenChange={(open) => !open && onOpenChange(false)}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>New geofence</DialogTitle>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    <div className="grid gap-2">
                        <Label htmlFor="geofence_name">Name</Label>
                        <Input id="geofence_name" value={data.name} onChange={(e) => setData('name', e.target.value)} autoFocus />
                        {errors.name ? <p className="text-xs text-destructive">{errors.name}</p> : null}
                    </div>

                    <div className="grid gap-2">
                        <Label>Color</Label>
                        <ColorSwatches value={data.color} onChange={(color) => setData('color', color)} />
                    </div>

                    <div className="grid gap-2">
                        <Label>Assign to vehicles</Label>
                        <VehicleChecklist vehicleOptions={vehicleOptions} selected={data.vehicle_ids} onToggle={toggleVehicle} />
                    </div>

                    <DialogFooter>
                        <Button
                            type="submit"
                            loading={processing}
                            className="bg-brand-navy text-white hover:bg-brand-navy/90 dark:bg-brand-green dark:text-brand-navy"
                        >
                            Save geofence
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function EditGeofenceDialog({
    geofence,
    vehicleOptions,
    onOpenChange,
}: {
    geofence: GeofenceRow | null;
    vehicleOptions: VehicleOption[];
    onOpenChange: (open: boolean) => void;
}) {
    const { data, setData, patch, processing, errors, reset, setDefaults } = useForm({
        name: geofence?.name ?? '',
        shape: geofence?.shape ?? ('circle' as 'circle' | 'polygon'),
        color: geofence?.color ?? PRESET_COLORS[0],
        center_latitude: geofence?.centerLatitude ?? null,
        center_longitude: geofence?.centerLongitude ?? null,
        radius_meters: geofence?.radiusMeters ?? null,
        polygon: geofence?.polygon ?? null,
        vehicle_ids: geofence?.vehicleIds ?? ([] as number[]),
    });

    useEffect(() => {
        if (geofence) {
            const values = {
                name: geofence.name,
                shape: geofence.shape,
                color: geofence.color ?? PRESET_COLORS[0],
                center_latitude: geofence.centerLatitude,
                center_longitude: geofence.centerLongitude,
                radius_meters: geofence.radiusMeters,
                polygon: geofence.polygon,
                vehicle_ids: geofence.vehicleIds,
            };
            setDefaults(values);
            setData(values);
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [geofence?.id]);

    const toggleVehicle = (id: number) => {
        setData('vehicle_ids', data.vehicle_ids.includes(id) ? data.vehicle_ids.filter((v) => v !== id) : [...data.vehicle_ids, id]);
    };

    const submit = (e: FormEvent) => {
        e.preventDefault();

        if (!geofence) {
            return;
        }

        patch(`/geofences/${geofence.id}`, {
            onSuccess: () => {
                reset();
                onOpenChange(false);
            },
        });
    };

    return (
        <Dialog open={!!geofence} onOpenChange={(open) => !open && onOpenChange(false)}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Edit geofence</DialogTitle>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    <div className="grid gap-2">
                        <Label htmlFor="edit_geofence_name">Name</Label>
                        <Input id="edit_geofence_name" value={data.name} onChange={(e) => setData('name', e.target.value)} />
                        {errors.name ? <p className="text-xs text-destructive">{errors.name}</p> : null}
                    </div>

                    <div className="grid gap-2">
                        <Label>Color</Label>
                        <ColorSwatches value={data.color} onChange={(color) => setData('color', color)} />
                    </div>

                    <div className="grid gap-2">
                        <Label>Assign to vehicles</Label>
                        <VehicleChecklist vehicleOptions={vehicleOptions} selected={data.vehicle_ids} onToggle={toggleVehicle} />
                    </div>

                    <p className="text-xs text-muted-foreground">To change the shape itself, delete this geofence and draw a new one.</p>

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

type ReferenceVehicle = { id: number; label: string; latitude: number; longitude: number };

export default function GeofencingIndex({ geofences, vehicleOptions }: { geofences: GeofenceRow[]; vehicleOptions: VehicleOption[] }) {
    const [leaflet, setLeaflet] = useState<LeafletBag | null>(null);
    const [drawnShape, setDrawnShape] = useState<DrawnShape | null>(null);
    const [editing, setEditing] = useState<GeofenceRow | null>(null);
    const [deleting, setDeleting] = useState<GeofenceRow | null>(null);
    const [deleteInFlight, setDeleteInFlight] = useState(false);
    const [referenceVehicles, setReferenceVehicles] = useState<ReferenceVehicle[]>([]);

    useEffect(() => {
        let cancelled = false;

        Promise.all([import('leaflet'), import('leaflet-draw'), import('react-leaflet')]).then(([leafletModule, , reactLeafletModule]) => {
            if (!cancelled) {
                setLeaflet({
                    L: leafletModule,
                    MapContainer: reactLeafletModule.MapContainer,
                    TileLayer: reactLeafletModule.TileLayer,
                    CircleMarker: reactLeafletModule.CircleMarker,
                    Circle: reactLeafletModule.Circle,
                    Polygon: reactLeafletModule.Polygon,
                    Popup: reactLeafletModule.Popup,
                    useMap: reactLeafletModule.useMap,
                });
            }
        });

        return () => {
            cancelled = true;
        };
    }, []);

    useEffect(() => {
        fetch('/live-tracking/positions')
            .then((res) => res.json())
            .then(
                (json: {
                    vehicles: { id: number; name: string | null; licensePlate: string | null; latitude: number | null; longitude: number | null }[];
                }) => {
                    setReferenceVehicles(
                        json.vehicles
                            .filter((v) => v.latitude !== null && v.longitude !== null)
                            .map((v) => ({
                                id: v.id,
                                label: v.licensePlate ?? v.name ?? `Vehicle #${v.id}`,
                                latitude: v.latitude as number,
                                longitude: v.longitude as number,
                            })),
                    );
                },
            )
            .catch(() => {
                // Reference layer is a nice-to-have while drawing — a fetch
                // failure just means an empty layer, not a page-level error.
            });
    }, []);

    const center: [number, number] = referenceVehicles.length > 0 ? [referenceVehicles[0].latitude, referenceVehicles[0].longitude] : LAGOS_CENTER;

    const confirmDelete = () => {
        if (!deleting) {
            return;
        }

        setDeleteInFlight(true);
        router.delete(`/geofences/${deleting.id}`, {
            onFinish: () => {
                setDeleteInFlight(false);
                setDeleting(null);
            },
        });
    };

    return (
        <CompanyLayout title="Geofencing">
            <Head title="Geofencing" />

            <div className="flex h-[calc(100vh-13rem)] min-h-[420px] flex-col gap-4">
                <div className="flex items-center justify-between">
                    <p className="text-sm text-muted-foreground">Draw a circle or polygon on the map, then assign it to one or more vehicles.</p>
                    <span className="text-xs text-muted-foreground">{geofences.length} geofence(s)</span>
                </div>

                <Card className="min-h-[320px] flex-1 overflow-hidden p-0">
                    {leaflet ? (
                        <leaflet.MapContainer center={center} zoom={12} scrollWheelZoom className="h-full w-full">
                            <leaflet.TileLayer
                                attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                                url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
                            />
                            <DrawControl leaflet={leaflet} onCreated={setDrawnShape} />

                            {referenceVehicles.map((vehicle) => (
                                <leaflet.CircleMarker
                                    key={vehicle.id}
                                    center={[vehicle.latitude, vehicle.longitude]}
                                    radius={5}
                                    pathOptions={{ color: '#ffffff', weight: 1, fillColor: '#9ca3af', fillOpacity: 1 }}
                                >
                                    <leaflet.Popup>{vehicle.label}</leaflet.Popup>
                                </leaflet.CircleMarker>
                            ))}

                            {geofences.map((geofence) =>
                                geofence.shape === 'circle' && geofence.centerLatitude !== null && geofence.centerLongitude !== null ? (
                                    <leaflet.Circle
                                        key={geofence.id}
                                        center={[geofence.centerLatitude, geofence.centerLongitude]}
                                        radius={geofence.radiusMeters ?? 0}
                                        pathOptions={{ color: geofence.color ?? PRESET_COLORS[0], fillOpacity: 0.15 }}
                                        eventHandlers={{ click: () => setEditing(geofence) }}
                                    >
                                        <leaflet.Popup>{geofence.name}</leaflet.Popup>
                                    </leaflet.Circle>
                                ) : geofence.polygon ? (
                                    <leaflet.Polygon
                                        key={geofence.id}
                                        positions={geofence.polygon}
                                        pathOptions={{ color: geofence.color ?? PRESET_COLORS[0], fillOpacity: 0.15 }}
                                        eventHandlers={{ click: () => setEditing(geofence) }}
                                    >
                                        <leaflet.Popup>{geofence.name}</leaflet.Popup>
                                    </leaflet.Polygon>
                                ) : null,
                            )}
                        </leaflet.MapContainer>
                    ) : (
                        <div className="flex h-full items-center justify-center text-sm text-muted-foreground">Loading map…</div>
                    )}
                </Card>

                <Card className="overflow-hidden py-0">
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="border-b bg-muted/40 text-left text-xs tracking-wide text-muted-foreground uppercase">
                                <tr>
                                    <th className="px-6 py-3 font-medium">Name</th>
                                    <th className="px-6 py-3 font-medium">Shape</th>
                                    <th className="px-6 py-3 font-medium">Vehicles</th>
                                    <th className="px-6 py-3 font-medium">Synced</th>
                                    <th className="px-6 py-3 font-medium"></th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-border">
                                {geofences.map((geofence) => (
                                    <tr key={geofence.id} className="hover:bg-muted/40">
                                        <td className="px-6 py-3 font-medium text-foreground">
                                            <span
                                                className="mr-2 inline-block size-2 rounded-full align-middle"
                                                style={{ backgroundColor: geofence.color ?? PRESET_COLORS[0] }}
                                            />
                                            {geofence.name}
                                        </td>
                                        <td className="px-6 py-3 text-muted-foreground capitalize">{geofence.shape}</td>
                                        <td className="px-6 py-3 text-muted-foreground">
                                            {geofence.vehicles.length === 0 ? '—' : geofence.vehicles.map((v) => v.label).join(', ')}
                                        </td>
                                        <td className="px-6 py-3">
                                            {geofence.synced ? (
                                                <Badge className="border-transparent bg-brand-green/15 text-brand-green">Synced</Badge>
                                            ) : (
                                                <Badge variant="outline">Pending</Badge>
                                            )}
                                        </td>
                                        <td className="px-6 py-3 text-right">
                                            <div className="flex justify-end gap-2">
                                                <Button size="sm" variant="outline" onClick={() => setEditing(geofence)}>
                                                    Edit
                                                </Button>
                                                <Button size="sm" variant="outline" onClick={() => setDeleting(geofence)}>
                                                    <Trash2 className="size-4 text-destructive" />
                                                </Button>
                                            </div>
                                        </td>
                                    </tr>
                                ))}

                                {geofences.length === 0 ? (
                                    <tr>
                                        <td colSpan={5} className="px-6 py-10 text-center text-muted-foreground">
                                            No geofences yet. Draw one on the map above to get started.
                                        </td>
                                    </tr>
                                ) : null}
                            </tbody>
                        </table>
                    </div>
                </Card>
            </div>

            <CreateGeofenceDialog shape={drawnShape} vehicleOptions={vehicleOptions} onOpenChange={(open) => !open && setDrawnShape(null)} />
            <EditGeofenceDialog geofence={editing} vehicleOptions={vehicleOptions} onOpenChange={(open) => !open && setEditing(null)} />

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
