<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CompanyController extends Controller
{
    public function index(Request $request): Response
    {
        $companies = Company::query()
            ->withCount(['vehicles', 'companyUsers'])
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Company $company) => [
                'id' => $company->id,
                'name' => $company->name,
                'slug' => $company->slug,
                'email' => $company->email,
                'phone' => $company->phone,
                'vehiclesCount' => $company->vehicles_count,
                'usersCount' => $company->company_users_count,
                'createdAt' => $company->created_at?->toIso8601String(),
            ]);

        return Inertia::render('admin/companies/index', [
            'companies' => $companies,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'url', 'max:255'],
            'admin_first_name' => ['required', 'string', 'max:100'],
            'admin_last_name' => ['required', 'string', 'max:100'],
            'admin_email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'admin_password' => ['required', 'string', 'min:8'],
        ]);

        $company = new Company;
        $company->fill([
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'website' => $validated['website'] ?? null,
            'slug' => $this->uniqueSlug($validated['name']),
        ]);
        $company->save();

        $admin = new User;
        $admin->first_name = $validated['admin_first_name'];
        $admin->last_name = $validated['admin_last_name'];
        $admin->email = $validated['admin_email'];
        $admin->password = $validated['admin_password'];
        $admin->email_verified_at = now();
        $admin->save();

        $companyUser = new CompanyUser;
        $companyUser->user_id = $admin->id;
        $companyUser->company_id = $company->id;
        $companyUser->role = 'admin';
        $companyUser->is_contact_person = true;
        $companyUser->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$company->name} created."]);

        return back();
    }

    public function update(Request $request): RedirectResponse
    {
        $company = Company::query()->findOrFail((string) $request->route('company'));

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'url', 'max:255'],
        ]);

        $company->fill($validated)->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Company updated.']);

        return back();
    }

    public function destroy(Request $request): RedirectResponse
    {
        $company = Company::query()->findOrFail((string) $request->route('company'));
        $company->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Company removed.']);

        return back();
    }

    protected function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $suffix = 1;

        while (Company::query()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
