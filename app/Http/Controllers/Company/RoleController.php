<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Company\Concerns\ResolvesCompany;
use App\Http\Controllers\Controller;
use App\Services\SystemLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Obrainwave\AccessTree\Models\Role;
use Spatie\LaravelPdf\Enums\Permission;

class RoleController extends Controller
{
    use ResolvesCompany;

    public function index(Request $request): Response
    {
        $company = $this->currentCompany($request);

        $roles = Role::query()
            ->where('company_id', $company->id)
            ->with(['permissions:id,slug,name,module'])
            ->withCount('users')
            ->orderByDesc('is_system')
            ->orderBy('name')
            ->get()
            ->map(fn (Role $role) => [
                'id' => $role->id,
                'name' => $role->name,
                'slug' => $role->slug,
                'description' => $role->description,
                'isSystem' => (bool) $role->is_system,
                'canBeAssignedVehicle' => (bool) $role->can_be_assigned_vehicle,
                'usersCount' => $role->users_count,
                'permissionIds' => $role->permissions->pluck('id')->values(),
            ]);

        $permissionsByModule = Permission::query()
            ->orderBy('module')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'module', 'description'])
            ->groupBy('module');

        return Inertia::render('company/roles/index', [
            'roles' => $roles,
            'permissionsByModule' => $permissionsByModule,
        ]);
    }

    public function store(Request $request, SystemLogService $systemLog): RedirectResponse
    {
        $company = $this->currentCompany($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'can_be_assigned_vehicle' => ['boolean'],
            'permission_ids' => ['required', 'array', 'min:1'],
            'permission_ids.*' => ['integer', Rule::exists('permissions', 'id')],
        ]);

        $slug = Str::slug($validated['name']);

        if (Role::where('company_id', $company->id)->where('slug', $slug)->exists()) {
            return back()->withErrors(['name' => 'A role with this name already exists.']);
        }

        $role = Role::create([
            'company_id' => $company->id,
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'is_system' => false,
            'can_be_assigned_vehicle' => (bool) ($validated['can_be_assigned_vehicle'] ?? false),
            'status' => 1,
        ]);

        $role->permissions()->sync($validated['permission_ids']);

        $systemLog->log(
            event: 'role.created',
            description: "Created role {$role->name}.",
            subject: $role,
            company: $company,
            metadata: ['permission_ids' => $validated['permission_ids']],
        );

        return back()->with('success', "Role '{$role->name}' created.");
    }

    public function update(Request $request, string $company_slug, Role $role, SystemLogService $systemLog): RedirectResponse
    {
        $company = $this->currentCompany($request);
        abort_unless((int) $role->company_id === (int) $company->id, 404);

        if ($role->is_system) {
            return back()->withErrors(['role' => 'The Company Admin role cannot be modified.']);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'can_be_assigned_vehicle' => ['boolean'],
            'permission_ids' => ['required', 'array', 'min:1'],
            'permission_ids.*' => ['integer', Rule::exists('permissions', 'id')],
        ]);

        $role->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'can_be_assigned_vehicle' => (bool) ($validated['can_be_assigned_vehicle'] ?? false),
        ]);

        $role->permissions()->sync($validated['permission_ids']);

        $systemLog->log(
            event: 'role.updated',
            description: "Updated role {$role->name}.",
            subject: $role,
            company: $company,
            metadata: ['permission_ids' => $validated['permission_ids']],
        );

        return back()->with('success', "Role '{$role->name}' updated.");
    }

    public function destroy(Request $request, string $company_slug, Role $role, SystemLogService $systemLog): RedirectResponse
    {
        $company = $this->currentCompany($request);
        abort_unless((int) $role->company_id === (int) $company->id, 404);

        if ($role->is_system) {
            return back()->withErrors(['role' => 'The Company Admin role cannot be deleted.']);
        }

        if ($role->users()->exists()) {
            return back()->withErrors(['role' => 'Reassign staff members out of this role before deleting it.']);
        }

        $roleName = $role->name;
        $role->delete();

        $systemLog->log(
            event: 'role.deleted',
            description: "Deleted role {$roleName}.",
            company: $company,
        );

        return back()->with('success', "Role '{$roleName}' deleted.");
    }
}
