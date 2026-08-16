<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Company\Concerns\ResolvesCompany;
use App\Http\Controllers\Controller;
use App\Models\CompanyUser;
use App\Models\User;
use App\Models\VehicleAssignment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DriverController extends Controller
{
    use ResolvesCompany;

    protected const ROLES = ['admin', 'supervisor', 'staff', 'vehicle-operator', 'workshop-admin'];

    public function index(Request $request): Response
    {
        $company = $this->currentCompany($request);

        $search = trim($request->string('search')->toString());
        $role = $request->string('role')->toString();

        $staff = $company->companyUsers()
            ->with(['user:id,first_name,last_name,email,phone', 'currentAssignment.vehicle:id,license_plate,make,model'])
            ->when($search !== '', fn ($query) => $query->whereHas('user', fn ($userQuery) => $userQuery
                ->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->when($role !== '' && in_array($role, self::ROLES, true), fn ($query) => $query->where('role', $role))
            ->withCount('checkIns')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (CompanyUser $companyUser) => [
                'id' => $companyUser->id,
                'name' => $companyUser->user->full_name,
                'email' => $companyUser->user->email,
                'phone' => $companyUser->user->phone,
                'role' => $companyUser->role,
                'roleText' => $companyUser->role_text,
                'assignedVehicleId' => $companyUser->currentAssignment?->vehicle_id,
                'assignedVehicle' => $companyUser->currentAssignment?->vehicle
                    ? [
                        'id' => $companyUser->currentAssignment->vehicle->id,
                        'label' => trim(
                            ($companyUser->currentAssignment->vehicle->license_plate ?? '').
                            ' '.
                            collect([$companyUser->currentAssignment->vehicle->make, $companyUser->currentAssignment->vehicle->model])
                                ->filter()
                                ->implode(' ')
                        ),
                    ]
                    : null,
                'checkInsCount' => $companyUser->check_ins_count,
            ]);

        return Inertia::render('company/drivers/index', [
            'staff' => $staff,
            'filters' => [
                'search' => $search,
                'role' => $role,
            ],
            'roles' => self::ROLES,
            'vehicleOptions' => $company->vehicles()
                ->orderBy('license_plate')
                ->get(['id', 'license_plate', 'make', 'model'])
                ->map(fn ($vehicle) => [
                    'id' => $vehicle->id,
                    'label' => trim(($vehicle->license_plate ?? '').' '.collect([$vehicle->make, $vehicle->model])->filter()->implode(' ')),
                ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $company = $this->currentCompany($request);

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', Rule::in(self::ROLES)],
            'vehicle_id' => ['nullable', 'integer', Rule::exists('vehicles', 'id')->where('company_id', $company->id)],
        ]);

        $user = new User;
        $user->first_name = $validated['first_name'];
        $user->last_name = $validated['last_name'];
        $user->email = $validated['email'];
        $user->phone = $validated['phone'] ?? null;
        $user->password = $validated['password'];
        $user->email_verified_at = now();
        $user->save();

        $companyUser = new CompanyUser;
        $companyUser->user_id = $user->id;
        $companyUser->company_id = $company->id;
        $companyUser->role = $validated['role'];
        $companyUser->save();

        if (! empty($validated['vehicle_id'])) {
            $this->assignVehicle($company->id, $companyUser->id, (int) $validated['vehicle_id']);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Driver added.']);

        return back();
    }

    public function update(Request $request): RedirectResponse
    {
        $company = $this->currentCompany($request);
        $companyUser = $company->companyUsers()->with('user')->findOrFail((string) $request->route('driver'));

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($companyUser->user_id)],
            'phone' => ['nullable', 'string', 'max:50'],
            'role' => ['required', Rule::in(self::ROLES)],
            'vehicle_id' => ['nullable', 'integer', Rule::exists('vehicles', 'id')->where('company_id', $company->id)],
        ]);

        $companyUser->user->first_name = $validated['first_name'];
        $companyUser->user->last_name = $validated['last_name'];
        $companyUser->user->email = $validated['email'];
        $companyUser->user->phone = $validated['phone'] ?? null;
        $companyUser->user->save();

        $companyUser->role = $validated['role'];
        $companyUser->save();

        $currentVehicleId = $companyUser->currentAssignment?->vehicle_id;
        $nextVehicleId = $validated['vehicle_id'] ?? null;

        if ($currentVehicleId !== $nextVehicleId) {
            $companyUser->currentAssignment?->update(['end_date' => now()]);

            if ($nextVehicleId) {
                $this->assignVehicle($company->id, $companyUser->id, (int) $nextVehicleId);
            }
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Driver updated.']);

        return back();
    }

    public function destroy(Request $request): RedirectResponse
    {
        $company = $this->currentCompany($request);
        $companyUser = $company->companyUsers()->findOrFail((string) $request->route('driver'));

        $companyUser->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Driver removed.']);

        return back();
    }

    protected function assignVehicle(int $companyId, int $companyUserId, int $vehicleId): void
    {
        $assignment = new VehicleAssignment;
        $assignment->company_id = $companyId;
        $assignment->company_user_id = $companyUserId;
        $assignment->vehicle_id = $vehicleId;
        $assignment->start_date = now();
        $assignment->save();
    }
}
