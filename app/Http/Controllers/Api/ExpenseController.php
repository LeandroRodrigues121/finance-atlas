<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = $request->user()->expenses()->latest('date');

        if ($request->filled('year')) {
            $query->whereYear('date', (int) $request->integer('year'));
        }

        if ($request->filled('month')) {
            $query->whereMonth('date', (int) $request->integer('month'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('category')) {
            $query->where('category', $request->string('category')->toString());
        }

        $expenses = $query->get();

        return response()->json([
            'data' => $expenses,
            'meta' => [
                'total_amount' => (float) $expenses->sum('amount'),
                'count' => $expenses->count(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'date' => ['required', 'date'],
            'category' => ['required', 'in:'.implode(',', Expense::CATEGORIES)],
            'status' => ['required', 'in:'.implode(',', Expense::STATUSES)],
            'notes' => ['nullable', 'string'],
        ]);

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

        $validated = $request->validate([
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'date' => ['required', 'date'],
            'category' => ['required', 'in:'.implode(',', Expense::CATEGORIES)],
            'status' => ['required', 'in:'.implode(',', Expense::STATUSES)],
            'notes' => ['nullable', 'string'],
        ]);

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
}
