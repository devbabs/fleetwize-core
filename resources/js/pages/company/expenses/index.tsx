import { Head } from '@inertiajs/react';

import { Pagination } from '@/components/company/pagination';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import CompanyLayout from '@/layouts/company/company-layout';
import type { Paginated } from '@/types/pagination';

type ExpenseEntry = {
    id: number;
    vehicle: string;
    type: string;
    amount: number;
    date: string | null;
    isRecurring: boolean;
    recurringFrequency: string | null;
    notes: string | null;
};

type Summary = {
    totalAmount: number;
    entriesCount: number;
};

function formatDate(value: string | null) {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleDateString(undefined, { dateStyle: 'medium' });
}

function formatCurrency(value: number) {
    return `₦${value.toLocaleString(undefined, { maximumFractionDigits: 0 })}`;
}

export default function ExpensesIndex({ expenses, summary }: { expenses: Paginated<ExpenseEntry>; summary: Summary }) {
    return (
        <CompanyLayout title="Expenses">
            <Head title="Expenses" />

            <div className="grid gap-4 sm:grid-cols-2">
                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="text-sm text-muted-foreground">Total Spend</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p className="text-2xl font-semibold text-brand-navy dark:text-white">{formatCurrency(summary.totalAmount)}</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="text-sm text-muted-foreground">Entries</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p className="text-2xl font-semibold text-foreground">{summary.entriesCount}</p>
                    </CardContent>
                </Card>
            </div>

            <Card className="overflow-hidden py-0">
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead className="border-b bg-muted/40 text-left text-xs tracking-wide text-muted-foreground uppercase">
                            <tr>
                                <th className="px-6 py-3 font-medium">Date</th>
                                <th className="px-6 py-3 font-medium">Vehicle</th>
                                <th className="px-6 py-3 font-medium">Type</th>
                                <th className="px-6 py-3 font-medium">Amount</th>
                                <th className="px-6 py-3 font-medium">Recurring</th>
                                <th className="px-6 py-3 font-medium">Notes</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-border">
                            {expenses.data.map((expense) => (
                                <tr key={expense.id}>
                                    <td className="px-6 py-3 text-foreground">{formatDate(expense.date)}</td>
                                    <td className="px-6 py-3 text-muted-foreground">{expense.vehicle}</td>
                                    <td className="px-6 py-3 text-muted-foreground capitalize">{expense.type}</td>
                                    <td className="px-6 py-3 text-foreground">{formatCurrency(expense.amount)}</td>
                                    <td className="px-6 py-3">
                                        {expense.isRecurring ? (
                                            <Badge variant="secondary" className="capitalize">
                                                {expense.recurringFrequency ?? 'Recurring'}
                                            </Badge>
                                        ) : (
                                            <span className="text-muted-foreground">—</span>
                                        )}
                                    </td>
                                    <td className="px-6 py-3 text-muted-foreground">{expense.notes ?? '—'}</td>
                                </tr>
                            ))}

                            {expenses.data.length === 0 ? (
                                <tr>
                                    <td colSpan={6} className="px-6 py-10 text-center text-muted-foreground">
                                        No expenses logged yet.
                                    </td>
                                </tr>
                            ) : null}
                        </tbody>
                    </table>
                </div>
                <Pagination links={expenses.links} from={expenses.from} to={expenses.to} total={expenses.total} />
            </Card>
        </CompanyLayout>
    );
}
