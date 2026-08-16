import { Head } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import 'leaflet/dist/leaflet.css';
import type {
    CircleMarker as CircleMarkerT,
    MapContainer as MapContainerT,
    Popup as PopupT,
    TileLayer as TileLayerT,
    useMap as useMapT,
} from 'react-leaflet';

import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import CompanyLayout from '@/layouts/company/company-layout';
import { cn } from '@/lib/utils';

type TrackedVehicle = {
    id: number;
    name: string | null;
    licensePlate: string | null;
    category: string;
    isOnline: boolean;
    latitude: number | null;
    longitude: number | null;
    speed: number | null;
    heading: number | null;
    ignitionOn: boolean | null;
    fuelLevel: number | null;
    reportedAt: string | null;
};

type StatusFilter = 'all' | 'moving' | 'idle' | 'offline';

function vehicleStatus(vehicle: TrackedVehicle): Exclude<StatusFilter, 'all'> {
    if (!vehicle.isOnline) {
        return 'offline';
    }

    if ((vehicle.speed ?? 0) > 5) {
        return 'moving';
    }

    return 'idle';
}

const statusColor: Record<Exclude<StatusFilter, 'all'>, string> = {
    moving: '#89c44b',
    idle: '#f59e0b',
    offline: '#9ca3af',
};

const LAGOS_CENTER: [number, number] = [6.5244, 3.3792];

// react-leaflet touches `window` at module-load time, which breaks Inertia's
// SSR pass — loaded dynamically inside an effect so SSR never imports it.
type LeafletComponents = {
    MapContainer: typeof MapContainerT;
    TileLayer: typeof TileLayerT;
    CircleMarker: typeof CircleMarkerT;
    Popup: typeof PopupT;
    useMap: typeof useMapT;
};

function FlyTo({ leaflet, position }: { leaflet: LeafletComponents; position: [number, number] }) {
    const map = leaflet.useMap();

    useEffect(() => {
        map.flyTo(position, Math.max(map.getZoom(), 14));
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [position[0], position[1]]);

    return null;
}

export default function LiveTrackingIndex({ vehicles: initialVehicles }: { vehicles: TrackedVehicle[] }) {
    const [vehicles, setVehicles] = useState(initialVehicles);
    const [filter, setFilter] = useState<StatusFilter>('all');
    const [search, setSearch] = useState('');
    const [leaflet, setLeaflet] = useState<LeafletComponents | null>(null);

    useEffect(() => {
        let cancelled = false;

        import('react-leaflet').then((mod) => {
            if (!cancelled) {
                setLeaflet({
                    MapContainer: mod.MapContainer,
                    TileLayer: mod.TileLayer,
                    CircleMarker: mod.CircleMarker,
                    Popup: mod.Popup,
                    useMap: mod.useMap,
                });
            }
        });

        return () => {
            cancelled = true;
        };
    }, []);

    useEffect(() => {
        const interval = setInterval(() => {
            fetch('/live-tracking/positions', { headers: { Accept: 'application/json' } })
                .then((response) => response.json())
                .then((data: { vehicles: TrackedVehicle[] }) => setVehicles(data.vehicles))
                .catch(() => undefined);
        }, 8000);

        return () => clearInterval(interval);
    }, []);

    const statusFiltered = useMemo(
        () => (filter === 'all' ? vehicles : vehicles.filter((vehicle) => vehicleStatus(vehicle) === filter)),
        [vehicles, filter],
    );

    const filtered = useMemo(() => {
        const query = search.trim().toLowerCase();

        if (!query) {
return statusFiltered;
}

        return statusFiltered.filter((vehicle) =>
            [vehicle.licensePlate, vehicle.name].filter(Boolean).some((value) => value!.toLowerCase().includes(query)),
        );
    }, [statusFiltered, search]);

    const searchMatch = useMemo(() => {
        const query = search.trim().toLowerCase();

        if (!query) {
return null;
}

        const match = filtered.find((vehicle) => vehicle.latitude !== null && vehicle.longitude !== null);

        return match ? ([match.latitude as number, match.longitude as number] as [number, number]) : null;
    }, [filtered, search]);

    const center: [number, number] =
        filtered.length > 0 && filtered[0].latitude !== null && filtered[0].longitude !== null
            ? [filtered[0].latitude, filtered[0].longitude]
            : LAGOS_CENTER;

    return (
        <CompanyLayout title="Live Tracking">
            <Head title="Live Tracking" />

            <div className="flex h-[calc(100vh-13rem)] min-h-[420px] flex-col gap-4">
                <div className="flex flex-wrap items-center gap-2">
                    <div className="relative w-full max-w-xs">
                        <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Search plate or name…"
                            className="pl-9"
                        />
                    </div>

                    {(['all', 'moving', 'idle', 'offline'] as StatusFilter[]).map((option) => (
                        <Button
                            key={option}
                            size="sm"
                            variant={filter === option ? 'default' : 'outline'}
                            className={cn(
                                filter === option && 'bg-brand-navy text-white hover:bg-brand-navy/90 dark:bg-brand-green dark:text-brand-navy',
                            )}
                            onClick={() => setFilter(option)}
                        >
                            {option === 'all' ? 'All' : option.charAt(0).toUpperCase() + option.slice(1)}
                        </Button>
                    ))}
                    <span className="ml-auto text-xs text-muted-foreground">{filtered.length} vehicle(s)</span>
                </div>

                <Card className="min-h-0 flex-1 overflow-hidden p-0">
                    {leaflet ? (
                        <leaflet.MapContainer center={center} zoom={12} scrollWheelZoom className="h-full w-full">
                            <leaflet.TileLayer
                                attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                                url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
                            />
                            {searchMatch ? <FlyTo leaflet={leaflet} position={searchMatch} /> : null}
                            {filtered
                                .filter((vehicle) => vehicle.latitude !== null && vehicle.longitude !== null)
                                .map((vehicle) => {
                                    const status = vehicleStatus(vehicle);

                                    return (
                                        <leaflet.CircleMarker
                                            key={vehicle.id}
                                            center={[vehicle.latitude as number, vehicle.longitude as number]}
                                            radius={8}
                                            pathOptions={{
                                                color: '#ffffff',
                                                weight: 2,
                                                fillColor: statusColor[status],
                                                fillOpacity: 1,
                                            }}
                                        >
                                            <leaflet.Popup>
                                                <div className="space-y-1 text-sm">
                                                    <p className="font-semibold">{vehicle.licensePlate ?? vehicle.name}</p>
                                                    <p className="text-muted-foreground capitalize">{status}</p>
                                                    <p>Speed: {vehicle.speed !== null ? `${Math.round(vehicle.speed)} km/h` : '—'}</p>
                                                    <p>Ignition: {vehicle.ignitionOn === null ? '—' : vehicle.ignitionOn ? 'On' : 'Off'}</p>
                                                    <p>Fuel: {vehicle.fuelLevel !== null ? `${Math.round(vehicle.fuelLevel as number)}%` : '—'}</p>
                                                    <a href={`/vehicles/${vehicle.id}`} className="text-brand-navy underline dark:text-brand-green">
                                                        View vehicle
                                                    </a>
                                                </div>
                                            </leaflet.Popup>
                                        </leaflet.CircleMarker>
                                    );
                                })}
                        </leaflet.MapContainer>
                    ) : (
                        <div className="flex h-full items-center justify-center text-sm text-muted-foreground">Loading map…</div>
                    )}
                </Card>
            </div>
        </CompanyLayout>
    );
}
