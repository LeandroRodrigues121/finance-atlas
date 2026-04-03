<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Income;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IncomeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tableQuery = $request->user()->incomes()->latest('date');
        $this->applyFilters($tableQuery, $request);

        $summaryQuery = $request->user()->incomes();
        $this->applyFilters($summaryQuery, $request);

        $incomes = $tableQuery->get();
        $totalAmount = (float) (clone $summaryQuery)->sum('amount');
        $totalCount = (int) (clone $summaryQuery)->count();

        $breakdownByType = (clone $summaryQuery)
            ->selectRaw('type, COUNT(*) as total_count, SUM(amount) as total_amount')
            ->groupBy('type')
            ->orderByDesc('total_amount')
            ->get()
            ->map(fn ($row) => [
                'type' => $row->type,
                'total_count' => (int) $row->total_count,
                'total_amount' => (float) $row->total_amount,
            ])
            ->values();

        return response()->json([
            'data' => $incomes,
            'meta' => [
                'total_amount' => $totalAmount,
                'count' => $totalCount,
                'breakdown_by_type' => $breakdownByType,
                'previous_period' => $this->buildPreviousPeriodMeta($request, $totalAmount),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate($this->rules());

        $income = $request->user()->incomes()->create($validated);

        return response()->json([
            'message' => 'Receita criada com sucesso.',
            'data' => $income,
        ], 201);
    }

    public function show(Request $request, Income $income): JsonResponse
    {
        abort_if($income->user_id !== $request->user()->id, 403);

        return response()->json([
            'data' => $income,
        ]);
    }

    public function update(Request $request, Income $income): JsonResponse
    {
        abort_if($income->user_id !== $request->user()->id, 403);

        $validated = $request->validate($this->rules());

        $income->update($validated);

        return response()->json([
            'message' => 'Receita atualizada com sucesso.',
            'data' => $income,
        ]);
    }

    public function destroy(Request $request, Income $income): JsonResponse
    {
        abort_if($income->user_id !== $request->user()->id, 403);
        $income->delete();

        return response()->json([
            'message' => 'Receita removida com sucesso.',
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
            'category' => ['required', 'string', 'max:100'],
            'type' => ['required', 'in:' . implode(',', Income::TYPES)],
            'status' => ['required', 'in:' . implode(',', Income::STATUSES)],
            'notes' => ['nullable', 'string'],
        ];
    }

    private function buildPreviousPeriodMeta(Request $request, float $currentTotalAmount): array
    {
        $referencePeriod = $this->resolveReferencePeriod($request);
        $previousPeriod = $referencePeriod->subMonth();

        $previousPeriodQuery = $request->user()->incomes();
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

        if ($request->filled('type')) {
            $query->where('type', $request->string('type')->toString());
        }

        if ($request->filled('category')) {
            $query->where('category', $request->string('category')->toString());
        }
    }
}
