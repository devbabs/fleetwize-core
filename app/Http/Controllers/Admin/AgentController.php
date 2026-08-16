<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AgentController extends Controller
{
    public function index(Request $request): Response
    {
        $agents = Agent::query()
            ->with('user:id,first_name,last_name,email,phone')
            ->withCount(['onboardedUsers', 'vehicles'])
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Agent $agent) => [
                'id' => $agent->id,
                'name' => $agent->user->full_name,
                'email' => $agent->user->email,
                'phone' => $agent->user->phone,
                'customersCount' => $agent->onboarded_users_count,
                'vehiclesCount' => $agent->vehicles_count,
                'createdAt' => $agent->created_at?->toIso8601String(),
            ]);

        return Inertia::render('admin/agents/index', [
            'agents' => $agents,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:50', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user = new User;
        $user->first_name = $validated['first_name'];
        $user->last_name = $validated['last_name'];
        $user->email = $validated['email'];
        $user->phone = $validated['phone'] ?? null;
        $user->password = $validated['password'];
        $user->email_verified_at = now();
        $user->save();

        $agent = new Agent;
        $agent->user_id = $user->id;
        $agent->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Agent added.']);

        return back();
    }

    public function update(Request $request): RedirectResponse
    {
        $agent = Agent::query()->with('user')->findOrFail((string) $request->route('agent'));

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($agent->user_id)],
            'phone' => ['nullable', 'string', 'max:50', Rule::unique('users', 'phone')->ignore($agent->user_id)],
        ]);

        $agent->user->first_name = $validated['first_name'];
        $agent->user->last_name = $validated['last_name'];
        $agent->user->email = $validated['email'];
        $agent->user->phone = $validated['phone'] ?? null;
        $agent->user->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Agent updated.']);

        return back();
    }

    public function destroy(Request $request): RedirectResponse
    {
        $agent = Agent::query()->findOrFail((string) $request->route('agent'));
        $agent->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Agent removed.']);

        return back();
    }
}
