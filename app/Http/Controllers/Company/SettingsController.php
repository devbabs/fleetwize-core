<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Company\Concerns\ResolvesCompany;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    use ResolvesCompany;

    public function edit(Request $request): Response
    {
        $company = $this->currentCompany($request);

        return Inertia::render('company/settings', [
            'companyProfile' => [
                'name' => $company->name,
                'email' => $company->email,
                'phone' => $company->phone,
                'website' => $company->website,
            ],
            'staff' => $company->companyUsers()
                ->with('user:id,first_name,last_name,email')
                ->get()
                ->map(fn ($companyUser) => [
                    'id' => $companyUser->id,
                    'name' => $companyUser->user->full_name,
                    'email' => $companyUser->user->email,
                    'role' => $companyUser->role,
                ]),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $company = $this->currentCompany($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'url', 'max:255'],
        ]);

        $company->fill($validated)->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Company profile updated.']);

        return back();
    }
}
