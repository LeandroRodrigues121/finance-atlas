<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Income;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IncomeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = $request->user()->incomes()->latest('date');

        if ($request->filled('year')) {
            $query->whereYear('date', (int) $request->integer('year'));
        }

        if ($request->filled('month')) {
            $query->whereMonth('date', (int) $request->integer('month'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->string('type')->toString());
        }

        if ($request->filled('category')) {
            $query->where('category', $request->string('category')->toString());
        }

        $incomes = $query->get();

        return response()->json([
            'data' => $incomes,
            'meta' => [
                'total_amount' => (float) $incomes->sum('amount'),
                'count' => $incomes->count(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'date' => ['required', 'date'],
            'category' => ['required', 'string', 'max:100'],
            'type' => ['required', 'in:'.implode(',', Income::TYPES)],
            'notes' => ['nullable', 'string'],
        ]);

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

        $validated = $request->validate([
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'date' => ['required', 'date'],
            'category' => ['required', 'string', 'max:100'],
            'type' => ['required', 'in:'.implode(',', Income::TYPES)],
            'notes' => ['nullable', 'string'],
        ]);

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
}
