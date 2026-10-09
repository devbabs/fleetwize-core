<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Company\Concerns\ResolvesCompany;
use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Vehicle;
use App\Models\VehicleDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class VehicleDocumentController extends Controller
{

    use ResolvesCompany;
    //
    public function store(Request $request, string $vehicle): RedirectResponse
    {
        $company = $this->currentCompany($request);

        // Find the vehicle belonging strictly to this company
        $vehicle = $company->vehicles()->findOrFail($vehicle);

        $this->authorize('update', $vehicle);

        $validated = $request->validate([
            'document_id' => [
                'required',
                'integer',
                Rule::exists('documents', 'id'),
            ],
            'document_number' => [
                'nullable',
                'string',
                'max:255',
            ],
            'last_renewed_at' => [
                'nullable',
                'date',
            ],
            'expires_at' => [
                'nullable',
                'date',
                'after_or_equal:last_renewed_at',
            ],
        ]);

        $alreadyExists = VehicleDocument::query()
            ->where('vehicle_id', $vehicle->id)
            ->where('document_id', $validated['document_id'])
            ->exists();

        if ($alreadyExists) {
            throw ValidationException::withMessages([
                'document_id' => 'This document type has already been added to this vehicle. Update the existing record instead.',
            ]);
        }

        DB::transaction(function () use ($vehicle, $validated) {
            $document = Document::findOrFail($validated['document_id']);

            if (strcasecmp($document->title, 'Driver License') === 0) {
                throw ValidationException::withMessages([
                    'document_id' => 'Driver licences must be managed under the driver record.',
                ]);
            }

            $vehicle->documents()->create([
                'document_id' => $validated['document_id'],
                'document_number' => $validated['document_number'] ?? null,
                'last_renewed_at' => $validated['last_renewed_at'] ?? null,
                'expires_at' => $validated['expires_at'] ?? null,
            ]);
        });

        return back()->with('success', 'Vehicle document added successfully.');
    }
}
