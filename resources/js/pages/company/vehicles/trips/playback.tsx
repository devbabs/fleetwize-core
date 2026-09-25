import { Head, Link } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';
import {
    MapContainer,
    TileLayer,
    Polyline,
    Marker,
    Popup,
    useMap,
} from 'react-leaflet';
import L from 'leaflet';

import 'leaflet/dist/leaflet.css';

interface RoutePoint {
    id: number;
    lat: number;
    lng: number;
    speed: number | null;
    heading: number | null;
    fixTime: string;
    ignition: boolean | null;
    motion: boolean | null;
}

interface Props {
    vehicle: {
        id: number;
        name: string;
    };

    trip: {
        id: number;
        startTime: string;
        endTime: string;
    };

    routePoints: RoutePoint[];
}

const startIcon = new L.Icon({
    iconUrl:
        'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
    shadowUrl:
        'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
    iconSize: [25, 41],
    iconAnchor: [12, 41],
});

const endIcon = new L.Icon({
    iconUrl:
        'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
    shadowUrl:
        'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
    iconSize: [25, 41],
    iconAnchor: [12, 41],
});

function FitBounds({
    positions,
}: {
    positions: [number, number][];
}) {
    const map = useMap();

    useEffect(() => {
        if (positions.length > 0) {
            map.fitBounds(positions, {
                padding: [50, 50],
            });
        }
    }, [map, positions]);

    return null;
}

export default function Playback({
    vehicle,
    trip,
    routePoints,
}: Props) {
    const [index, setIndex] = useState(0);
    const [playing, setPlaying] = useState(false);
    const [speed, setSpeed] = useState(1);

    const positions = useMemo(
        () =>
            routePoints.map((point) => [
                point.lat,
                point.lng,
            ]) as [number, number][],
        [routePoints]
    );

    const currentPoint = routePoints[index];

    useEffect(() => {
        if (!playing) {
            return;
        }

        const interval = setInterval(() => {
            setIndex((current) => {
                if (current >= routePoints.length - 1) {
                    setPlaying(false);

                    return current;
                }

                return current + 1;
            });
        }, 1000 / speed);

        return () => clearInterval(interval);
    }, [playing, speed, routePoints.length]);

    const startPoint = routePoints[0];
    const endPoint =
        routePoints[routePoints.length - 1];

    return (
        <>
            <Head title="Trip Playback" />

            <div className="space-y-4 p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold">
                            Trip Playback
                        </h1>

                        <p className="text-muted-foreground">
                            {vehicle.name}
                        </p>
                    </div>

                    <Link
                        href={route(
                            'vehicles.show',
                            vehicle.id
                        )}
                        className="rounded border px-4 py-2"
                    >
                        Back
                    </Link>
                </div>

                <div className="grid gap-4 md:grid-cols-4">
                    <div className="rounded border p-4">
                        <p className="text-xs text-muted-foreground">
                            Start
                        </p>

                        <p>
                            {new Date(
                                trip.startTime
                            ).toLocaleString()}
                        </p>
                    </div>

                    <div className="rounded border p-4">
                        <p className="text-xs text-muted-foreground">
                            End
                        </p>

                        <p>
                            {new Date(
                                trip.endTime
                            ).toLocaleString()}
                        </p>
                    </div>

                    <div className="rounded border p-4">
                        <p className="text-xs text-muted-foreground">
                            Route Points
                        </p>

                        <p>{routePoints.length}</p>
                    </div>

                    <div className="rounded border p-4">
                        <p className="text-xs text-muted-foreground">
                            Progress
                        </p>

                        <p>
                            {index + 1} /{' '}
                            {routePoints.length}
                        </p>
                    </div>
                </div>

                <div className="flex flex-wrap gap-3 rounded border p-4">
                    <button
                        onClick={() =>
                            setPlaying((p) => !p)
                        }
                        className="rounded border px-4 py-2"
                    >
                        {playing
                            ? 'Pause'
                            : 'Play'}
                    </button>

                    <button
                        onClick={() => {
                            setPlaying(false);
                            setIndex(0);
                        }}
                        className="rounded border px-4 py-2"
                    >
                        Reset
                    </button>

                    <select
                        value={speed}
                        onChange={(e) =>
                            setSpeed(
                                Number(
                                    e.target.value
                                )
                            )
                        }
                        className="rounded border px-3 py-2"
                    >
                        <option value={1}>1x</option>
                        <option value={2}>2x</option>
                        <option value={5}>5x</option>
                        <option value={10}>10x</option>
                        <option value={20}>20x</option>
                    </select>

                    <input
                        type="range"
                        min={0}
                        max={
                            routePoints.length - 1
                        }
                        value={index}
                        onChange={(e) =>
                            setIndex(
                                Number(
                                    e.target.value
                                )
                            )
                        }
                        className="flex-1"
                    />
                </div>

                <div className="grid gap-4 md:grid-cols-4">
                    <div className="rounded border p-4">
                        <p className="text-xs text-muted-foreground">
                            Speed
                        </p>

                        <p>
                            {currentPoint?.speed ??
                                0}{' '}
                            km/h
                        </p>
                    </div>

                    <div className="rounded border p-4">
                        <p className="text-xs text-muted-foreground">
                            Heading
                        </p>

                        <p>
                            {currentPoint?.heading ??
                                0}
                            °
                        </p>
                    </div>

                    <div className="rounded border p-4">
                        <p className="text-xs text-muted-foreground">
                            Ignition
                        </p>

                        <p>
                            {currentPoint?.ignition
                                ? 'ON'
                                : 'OFF'}
                        </p>
                    </div>

                    <div className="rounded border p-4">
                        <p className="text-xs text-muted-foreground">
                            Time
                        </p>

                        <p>
                            {currentPoint
                                ? new Date(
                                      currentPoint.fixTime
                                  ).toLocaleString()
                                : '-'}
                        </p>
                    </div>
                </div>

                <div className="overflow-hidden rounded border">
                    <MapContainer
                        style={{
                            height: '700px',
                            width: '100%',
                        }}
                        center={[
                            positions[0]?.[0] ??
                                6.5244,
                            positions[0]?.[1] ??
                                3.3792,
                        ]}
                        zoom={14}
                    >
                        <TileLayer
                            url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
                        />

                        <FitBounds
                            positions={positions}
                        />

                        <Polyline
                            positions={positions}
                        />

                        {startPoint && (
                            <Marker
                                position={[
                                    startPoint.lat,
                                    startPoint.lng,
                                ]}
                                icon={startIcon}
                            >
                                <Popup>
                                    Trip Start
                                </Popup>
                            </Marker>
                        )}

                        {endPoint && (
                            <Marker
                                position={[
                                    endPoint.lat,
                                    endPoint.lng,
                                ]}
                                icon={endIcon}
                            >
                                <Popup>
                                    Trip End
                                </Popup>
                            </Marker>
                        )}

                        {currentPoint && (
                            <Marker
                                position={[
                                    currentPoint.lat,
                                    currentPoint.lng,
                                ]}
                            >
                                <Popup>
                                    Current Position
                                </Popup>
                            </Marker>
                        )}
                    </MapContainer>
                </div>
            </div>
        </>
    );
}