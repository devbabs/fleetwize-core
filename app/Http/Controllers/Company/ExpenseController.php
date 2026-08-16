<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Company\Concerns\ResolvesCompany;
use App\Http\Controllers\Controller;
use App\Models\VehicleExpense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseController extends Controller
{
    use ResolvesCompany;

    public function index(Request $request): Response
    {
        $company = $this->currentCompany($request);

        $expenses = VehicleExpense::query()
            ->where('company_id', $company->id)
            ->with('vehicle:id,license_plate,make,model')
            ->latest('date')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (VehicleExpense $expense) => [
                'id' => $expense->id,
                'vehicle' => $expense->vehicle->license_plate ?? '—',
                'type' => $expense->expense_type,
                'amount' => $expense->amount,
                'date' => $expense->date->toIso8601String(),
                'isRecurring' => $expense->is_recurring,
                'recurringFrequency' => $expense->recurring_frequency,
                'notes' => $expense->notes,
            ]);

        $totals = DB::table('vehicle_expenses')
            ->where('company_id', $company->id)
            ->selectRaw('COALESCE(SUM(amount), 0) as total_amount, COUNT(*) as entries_count')
            ->first();

        return Inertia::render('company/expenses/index', [
            'expenses' => $expenses,
            'summary' => [
                'totalAmount' => round((float) $totals->total_amount, 2),
                'entriesCount' => (int) $totals->entries_count,
            ],
        ]);
    }
}
