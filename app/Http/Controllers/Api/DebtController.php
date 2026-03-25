<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Debt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DebtController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = $request->user()->debts()->latest('due_date');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        $debts = $query->get();

        return response()->json([
            'data' => $debts,
            'meta' => [
                'total_amount' => (float) $debts->sum('total_amount'),
                'paid_amount' => (float) $debts->sum('paid_amount'),
                'remaining_amount' => (float) $debts->sum('remaining_amount'),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'description' => ['required', 'string', 'max:255'],
            'total_amount' => ['required', 'numeric', 'min:0.01'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'due_date' => ['required', 'date'],
            'status' => ['nullable', 'in:'.implode(',', Debt::STATUSES)],
            'notes' => ['nullable', 'string'],
        ]);

        $validated['paid_amount'] = $validated['paid_amount'] ?? 0;
        $validated['status'] = $validated['status'] ?? 'pendente';

        if ((float) $validated['paid_amount'] > (float) $validated['total_amount']) {
            return response()->json([
                'message' => 'O valor pago não pode ser maior que o valor total.',
            ], 422);
        }

        $debt = $request->user()->debts()->create($validated);

        return response()->json([
            'message' => 'Dívida criada com sucesso.',
            'data' => $debt,
        ], 201);
    }

    public function show(Request $request, Debt $debt): JsonResponse
    {
        abort_if($debt->user_id !== $request->user()->id, 403);

        return response()->json([
            'data' => $debt,
        ]);
    }

    public function update(Request $request, Debt $debt): JsonResponse
    {
        abort_if($debt->user_id !== $request->user()->id, 403);

        $validated = $request->validate([
            'description' => ['required', 'string', 'max:255'],
            'total_amount' => ['required', 'numeric', 'min:0.01'],
            'paid_amount' => ['required', 'numeric', 'min:0'],
            'due_date' => ['required', 'date'],
            'status' => ['required', 'in:'.implode(',', Debt::STATUSES)],
            'notes' => ['nullable', 'string'],
        ]);

        if ((float) $validated['paid_amount'] > (float) $validated['total_amount']) {
            return response()->json([
                'message' => 'O valor pago não pode ser maior que o valor total.',
            ], 422);
        }

        $debt->update($validated);

        return response()->json([
            'message' => 'Dívida atualizada com sucesso.',
            'data' => $debt,
        ]);
    }

    public function destroy(Request $request, Debt $debt): JsonResponse
    {
        abort_if($debt->user_id !== $request->user()->id, 403);
        $debt->delete();

        return response()->json([
            'message' => 'Dívida removida com sucesso.',
        ]);
    }
}
