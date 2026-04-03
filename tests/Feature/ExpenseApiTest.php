<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class ExpenseApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_get_expenses_payload_with_summary_meta(): void
    {
        $user = User::factory()->create();

        $this->createExpense($user, [
            'description' => 'Aluguel 4/2026',
            'amount' => 2100,
            'date' => '2026-04-05',
            'category' => 'moradia',
            'payment_method' => 'Transferencia',
            'status' => 'paga',
        ]);

        $this->createExpense($user, [
            'description' => 'Lazer 4/2026',
            'amount' => 900,
            'date' => '2026-04-20',
            'category' => 'lazer',
            'payment_method' => 'Nubank',
            'status' => 'pendente',
        ]);

        $this->createExpense($user, [
            'description' => 'Moradia 3/2026',
            'amount' => 2000,
            'date' => '2026-03-05',
            'category' => 'moradia',
            'payment_method' => 'Transferencia',
            'status' => 'paga',
        ]);

        $response = $this
            ->actingAs($user)
            ->getJson('/api/expenses?month=4&year=2026');

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment([
                'description' => 'Lazer 4/2026',
                'payment_method' => 'Nubank',
                'status' => 'pendente',
            ])
            ->assertJsonPath('meta.total_amount', 3000)
            ->assertJsonPath('meta.count', 2)
            ->assertJsonPath('meta.previous_period.total_amount', 2000)
            ->assertJsonPath('meta.previous_period.delta_amount', 1000)
            ->assertJsonPath('meta.previous_period.delta_percentage', 50)
            ->assertJson(fn (AssertableJson $json) => $json
                ->has('meta.breakdown_by_category', 2)
                ->where('meta.breakdown_by_category.0.category', 'moradia')
                ->where('meta.breakdown_by_category.0.total_amount', 2100)
                ->whereType('meta.previous_period.label', 'string')
                ->etc()
            );
    }

    public function test_store_accepts_payment_method(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->postJson('/api/expenses', [
                'description' => 'Internet 4/2026',
                'amount' => 520,
                'date' => '2026-04-12',
                'category' => 'contas_fixas',
                'payment_method' => 'Nubank',
                'status' => 'paga',
                'notes' => 'Teste',
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.payment_method', 'Nubank');
    }

    public function test_store_rejects_invalid_expense_status(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->postJson('/api/expenses', [
                'description' => 'Despesa invalida',
                'amount' => 1200,
                'date' => '2026-04-10',
                'category' => 'moradia',
                'payment_method' => 'Pix',
                'status' => 'cancelada',
                'notes' => 'Teste',
            ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);
    }

    private function createExpense(User $user, array $attributes = []): Expense
    {
        return Expense::create(array_merge([
            'user_id' => $user->id,
            'description' => 'Despesa exemplo',
            'amount' => 500,
            'date' => '2026-04-05',
            'category' => 'moradia',
            'payment_method' => 'Nubank',
            'status' => 'paga',
            'notes' => 'Despesa de teste.',
        ], $attributes));
    }
}
