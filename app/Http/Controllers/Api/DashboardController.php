<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $month = max(1, min(12, (int) ($request->integer('month') ?: now()->month)));
        $year = (int) ($request->integer('year') ?: now()->year);
        $user = $request->user();

        $monthlyIncome = (float) $user->incomes()
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->sum('amount');

        $monthlyExpense = (float) $user->expenses()
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->sum('amount');

        $annualIncome = (float) $user->incomes()
            ->whereYear('date', $year)
            ->sum('amount');

        $annualExpense = (float) $user->expenses()
            ->whereYear('date', $year)
            ->sum('amount');

        $incomeByMonth = [];
        $expenseByMonth = [];
        $balanceByMonth = [];
        $labels = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
        $runningBalance = 0;

        foreach (range(1, 12) as $currentMonth) {
            $income = (float) $user->incomes()->whereYear('date', $year)->whereMonth('date', $currentMonth)->sum('amount');
            $expense = (float) $user->expenses()->whereYear('date', $year)->whereMonth('date', $currentMonth)->sum('amount');
            $balance = $income - $expense;
            $runningBalance += $balance;

            $incomeByMonth[] = $income;
            $expenseByMonth[] = $expense;
            $balanceByMonth[] = [
                'month' => $currentMonth,
                'balance' => $balance,
                'accumulated_balance' => $runningBalance,
            ];
        }

        $categoryTotals = $user->expenses()
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->select('category', DB::raw('SUM(amount) as total'))
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();

        $openDebtTotal = (float) $user->debts()->where('status', '!=', 'paga')->sum('remaining_amount');
        $paidDebtTotal = (float) $user->debts()->sum('paid_amount');
        $expenseCommitment = $monthlyIncome > 0 ? round(($monthlyExpense / $monthlyIncome) * 100, 2) : 0;

        $basePeriod = Carbon::create($year, $month, 1, 0, 0, 0, config('app.timezone'));
        $recentIncomes = collect(range(0, 2))->map(function (int $offset) use ($user, $basePeriod): float {
            $targetPeriod = $basePeriod->copy()->subMonths($offset);

            return (float) $user->incomes()
                ->whereYear('date', $targetPeriod->year)
                ->whereMonth('date', $targetPeriod->month)
                ->sum('amount');
        });

        $recommendedExpenseLimit = round(((float) $recentIncomes->avg()) * 0.7, 2);

        return response()->json([
            'period' => [
                'month' => $month,
                'year' => $year,
            ],
            'monthly' => [
                'income_total' => $monthlyIncome,
                'expense_total' => $monthlyExpense,
                'balance' => $monthlyIncome - $monthlyExpense,
            ],
            'annual' => [
                'income_total' => $annualIncome,
                'expense_total' => $annualExpense,
                'balance' => $annualIncome - $annualExpense,
            ],
            'charts' => [
                'income_vs_expense_by_month' => [
                    'labels' => $labels,
                    'incomes' => $incomeByMonth,
                    'expenses' => $expenseByMonth,
                ],
                'expenses_by_category' => [
                    'labels' => $categoryTotals->pluck('category')->values()->all(),
                    'values' => $categoryTotals->pluck('total')->map(fn ($value) => (float) $value)->values()->all(),
                ],
            ],
            'indicators' => [
                'expense_commitment_percent' => $expenseCommitment,
                'open_debt_total' => $openDebtTotal,
                'paid_debt_total' => $paidDebtTotal,
                'recommended_expense_limit' => $recommendedExpenseLimit,
            ],
            'monthly_balance_timeline' => $balanceByMonth,
        ]);
    }

    public function annualReport(Request $request): JsonResponse
    {
        $year = (int) ($request->integer('year') ?: now()->year);
        $user = $request->user();
        $rows = [];
        $accumulatedBalance = 0;

        foreach (range(1, 12) as $month) {
            $income = (float) $user->incomes()->whereYear('date', $year)->whereMonth('date', $month)->sum('amount');
            $expense = (float) $user->expenses()->whereYear('date', $year)->whereMonth('date', $month)->sum('amount');
            $balance = $income - $expense;
            $accumulatedBalance += $balance;

            $rows[] = [
                'month' => $month,
                'income_total' => $income,
                'expense_total' => $expense,
                'balance' => $balance,
                'accumulated_balance' => $accumulatedBalance,
            ];
        }

        return response()->json([
            'year' => $year,
            'rows' => $rows,
            'totals' => [
                'income_total' => (float) collect($rows)->sum('income_total'),
                'expense_total' => (float) collect($rows)->sum('expense_total'),
                'balance' => (float) collect($rows)->sum('balance'),
            ],
        ]);
    }
}
