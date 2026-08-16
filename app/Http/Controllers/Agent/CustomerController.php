<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Agent\Concerns\ResolvesAgent;
use App\Http\Controllers\Company\Concerns\ValidatesTrackerImei;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    use ResolvesAgent, ValidatesTrackerImei;

    public function index(Request $request): Response
    {
        $agent = $this->currentAgent($request);

        $customers = $agent->onboardedUsers()
            ->with(['vehicles' => fn ($query) => $query->where('agent_id', $agent->id)])
            ->withCount('vehicles')
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through($this->mapCustomer(...));

        return Inertia::render('agent/customers/index', [
            'customers' => $customers,
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('agent/customers/create');
    }

    public function store(Request $request): RedirectResponse
    {
        $agent = $this->currentAgent($request);

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:50', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:8'],
            'license_plate' => ['required', 'string', 'max:50'],
            'make' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'year' => ['nullable', 'string', 'max:10'],
            'color' => ['nullable', 'string', 'max:50'],
            'category' => ['required', 'string', 'max:50'],
            'vin' => ['nullable', 'string', 'max:50'],
            'obd_device_id' => ['nullable', 'string', 'max:100'],
            'obd_device_imei' => ['nullable', 'string', 'max:50'],
            'tracker_phone_number' => ['nullable', 'string', 'max:50'],
        ]);

        $this->assertTrackerImeiIsValid($validated['obd_device_imei'] ?? null);

        $user = new User;
        $user->first_name = $validated['first_name'];
        $user->last_name = $validated['last_name'];
        $user->email = $validated['email'];
        $user->phone = $validated['phone'] ?? null;
        $user->password = $validated['password'];
        $user->agent_id = $agent->id;
        $user->email_verified_at = now();
        $user->save();

        $vehicle = new Vehicle;
        $vehicle->fill([
            'license_plate' => $validated['license_plate'],
            'make' => $validated['make'] ?? null,
            'model' => $validated['model'] ?? null,
            'year' => $validated['year'] ?? null,
            'color' => $validated['color'] ?? null,
            'category' => $validated['category'],
            'status' => 'active',
            'is_owned' => true,
            'is_active' => true,
            'mileage' => 0,
            'vin' => $validated['vin'] ?? null,
            'obd_device_id' => $validated['obd_device_id'] ?? null,
            'obd_device_imei' => $validated['obd_device_imei'] ?? null,
            'tracker_phone_number' => $validated['tracker_phone_number'] ?? null,
        ]);
        $vehicle->user_id = $user->id;
        $vehicle->agent_id = $agent->id;
        $vehicle->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Customer onboarded.']);

        return redirect('/agent/customers');
    }

    public function addVehicle(Request $request): RedirectResponse
    {
        $agent = $this->currentAgent($request);
        $customer = $agent->onboardedUsers()->findOrFail((string) $request->route('customer'));

        $validated = $request->validate([
            'license_plate' => ['required', 'string', 'max:50'],
            'make' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'year' => ['nullable', 'string', 'max:10'],
            'color' => ['nullable', 'string', 'max:50'],
            'category' => ['required', 'string', 'max:50'],
            'vin' => ['nullable', 'string', 'max:50'],
            'obd_device_id' => ['nullable', 'string', 'max:100'],
            'obd_device_imei' => ['nullable', 'string', 'max:50'],
            'tracker_phone_number' => ['nullable', 'string', 'max:50'],
        ]);

        $this->assertTrackerImeiIsValid($validated['obd_device_imei'] ?? null);

        $vehicle = new Vehicle;
        $vehicle->fill([
            ...$validated,
            'status' => 'active',
            'is_owned' => true,
            'is_active' => true,
            'mileage' => 0,
        ]);
        $vehicle->user_id = $customer->id;
        $vehicle->agent_id = $agent->id;
        $vehicle->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Vehicle added.']);

        return back();
    }

    /** @return array<string, mixed> */
    protected function mapCustomer(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->full_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'vehiclesCount' => $user->vehicles_count,
            'vehicles' => $user->vehicles->map(fn (Vehicle $vehicle): array => [
                'id' => $vehicle->id,
                'licensePlate' => $vehicle->license_plate,
                'label' => trim(($vehicle->license_plate ?? '').' '.collect([$vehicle->make, $vehicle->model])->filter()->implode(' ')),
            ]),
            'createdAt' => $user->created_at?->toIso8601String(),
        ];
    }
}
