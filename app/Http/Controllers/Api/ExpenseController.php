<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tableQuery = $request->user()->expenses()->latest('date');
        $this->applyFilters($tableQuery, $request);

        $summaryQuery = $request->user()->expenses();
        $this->applyFilters($summaryQuery, $request);

        $expenses = $tableQuery->get();
        $totalAmount = (float) (clone $summaryQuery)->sum('amount');
        $totalCount = (int) (clone $summaryQuery)->count();

        $breakdownByCategory = (clone $summaryQuery)
            ->selectRaw('category, COUNT(*) as total_count, SUM(amount) as total_amount')
            ->groupBy('category')
            ->orderByDesc('total_amount')
            ->get()
            ->map(fn ($row) => [
                'category' => $row->category,
                'total_count' => (int) $row->total_count,
                'total_amount' => (float) $row->total_amount,
            ])
            ->values();

        return response()->json([
            'data' => $expenses,
            'meta' => [
                'total_amount' => $totalAmount,
                'count' => $totalCount,
                'breakdown_by_category' => $breakdownByCategory,
                'previous_period' => $this->buildPreviousPeriodMeta($request, $totalAmount),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate($this->rules());

        $expense = $request->user()->expenses()->create($validated);

        return response()->json([
            'message' => 'Despesa criada com sucesso.',
            'data' => $expense,
        ], 201);
    }

    public function show(Request $request, Expense $expense): JsonResponse
    {
        abort_if($expense->user_id !== $request->user()->id, 403);

        return response()->json([
            'data' => $expense,
        ]);
    }

    public function update(Request $request, Expense $expense): JsonResponse
    {
        abort_if($expense->user_id !== $request->user()->id, 403);

        $validated = $request->validate($this->rules());

        $expense->update($validated);

        return response()->json([
            'message' => 'Despesa atualizada com sucesso.',
            'data' => $expense,
        ]);
    }

    public function destroy(Request $request, Expense $expense): JsonResponse
    {
        abort_if($expense->user_id !== $request->user()->id, 403);
        $expense->delete();

        return response()->json([
            'message' => 'Despesa removida com sucesso.',
        ]);
    }

    /**
     * @return array<string, array<int, string>|string>
     */
    private function rules(): array
    {
        return [
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'date' => ['required', 'date'],
            'category' => ['required', 'in:' . implode(',', Expense::CATEGORIES)],
            'payment_method' => ['nullable', 'string', 'max:120'],
            'status' => ['required', 'in:' . implode(',', Expense::STATUSES)],
            'notes' => ['nullable', 'string'],
        ];
    }

    private function buildPreviousPeriodMeta(Request $request, float $currentTotalAmount): array
    {
        $referencePeriod = $this->resolveReferencePeriod($request);
        $previousPeriod = $referencePeriod->subMonth();

        $previousPeriodQuery = $request->user()->expenses();
        $this->applyFilters($previousPeriodQuery, $request, false);
        $previousPeriodQuery
            ->whereYear('date', $previousPeriod->year)
            ->whereMonth('date', $previousPeriod->month);

        $previousTotalAmount = (float) $previousPeriodQuery->sum('amount');
        $deltaAmount = round($currentTotalAmount - $previousTotalAmount, 2);
        $deltaPercentage = $previousTotalAmount > 0
            ? round(($deltaAmount / $previousTotalAmount) * 100, 1)
            : null;

        return [
            'month' => $previousPeriod->month,
            'year' => $previousPeriod->year,
            'label' => $previousPeriod
                ->locale((string) config('app.locale'))
                ->translatedFormat('F \d\e Y'),
            'total_amount' => $previousTotalAmount,
            'delta_amount' => $deltaAmount,
            'delta_percentage' => $deltaPercentage,
        ];
    }

    private function resolveReferencePeriod(Request $request): CarbonImmutable
    {
        $month = $request->filled('month') ? (int) $request->integer('month') : (int) now()->month;
        $year = $request->filled('year') ? (int) $request->integer('year') : (int) now()->year;

        return CarbonImmutable::create($year, $month, 1, 0, 0, 0, config('app.timezone'));
    }

    private function applyFilters(Builder|HasMany $query, Request $request, bool $includePeriod = true): void
    {
        if ($includePeriod && $request->filled('year')) {
            $query->whereYear('date', (int) $request->integer('year'));
        }

        if ($includePeriod && $request->filled('month')) {
            $query->whereMonth('date', (int) $request->integer('month'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('category')) {
            $query->where('category', $request->string('category')->toString());
        }
    }
}
