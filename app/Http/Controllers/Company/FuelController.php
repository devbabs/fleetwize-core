<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Company\Concerns\ResolvesCompany;
use App\Http\Controllers\Controller;
use App\Models\VehicleFuelEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class FuelController extends Controller
{
    use ResolvesCompany;

    public function index(Request $request): Response
    {
        $company = $this->currentCompany($request);

        $entries = VehicleFuelEntry::query()
            ->where('company_id', $company->id)
            ->with('vehicle:id,license_plate,make,model')
            ->latest('date')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (VehicleFuelEntry $entry) => [
                'id' => $entry->id,
                'vehicle' => $entry->vehicle->license_plate ?? '—',
                'date' => $entry->date->toIso8601String(),
                'litres' => $entry->litres,
                'pricePerLitre' => $entry->price_per_litre,
                'total' => $entry->litres * $entry->price_per_litre,
                'meterReading' => $entry->meter_reading,
            ]);

        $totals = DB::table('vehicle_fuel_entries')
            ->where('company_id', $company->id)
            ->selectRaw('COALESCE(SUM(litres), 0) as total_litres, COALESCE(SUM(litres * price_per_litre), 0) as total_cost, COUNT(*) as entries_count')
            ->first();

        return Inertia::render('company/fuel/index', [
            'entries' => $entries,
            'summary' => [
                'totalLitres' => round((float) $totals->total_litres, 1),
                'totalCost' => round((float) $totals->total_cost, 2),
                'entriesCount' => (int) $totals->entries_count,
            ],
        ]);
    }
}
