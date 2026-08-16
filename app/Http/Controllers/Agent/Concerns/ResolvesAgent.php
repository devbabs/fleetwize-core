<?php

namespace App\Http\Controllers\Agent\Concerns;

use App\Models\Agent;
use Illuminate\Http\Request;

/**
 * EnsureUserIsAgent already validated the authenticated user owns an Agent
 * record and stashed it on the request.
 */
trait ResolvesAgent
{
    protected function currentAgent(Request $request): Agent
    {
        return $request->attributes->get('agent');
    }
}
