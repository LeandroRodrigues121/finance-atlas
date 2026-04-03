<?php

namespace Tests\Feature;

use App\Models\Income;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class IncomeApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_get_incomes_payload_with_previous_period_meta(): void
    {
        $user = User::factory()->create();

        $this->createIncome($user, [
            'description' => 'Salario 4/2026',
            'amount' => 7700,
            'date' => '2026-04-05',
            'category' => 'Trabalho',
            'type' => 'salario',
            'status' => 'recebido',
        ]);

        $this->createIncome($user, [
            'description' => 'Freela 4/2026',
            'amount' => 960,
            'date' => '2026-04-20',
            'category' => 'Projetos',
            'type' => 'renda_extra',
            'status' => 'pendente',
        ]);

        $this->createIncome($user, [
            'description' => 'Salario 3/2026',
            'amount' => 6900,
            'date' => '2026-03-05',
            'category' => 'Trabalho',
            'type' => 'salario',
            'status' => 'recebido',
        ]);

        $response = $this
            ->actingAs($user)
            ->getJson('/api/incomes?month=4&year=2026');

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment([
                'description' => 'Freela 4/2026',
                'status' => 'pendente',
            ])
            ->assertJsonFragment([
                'description' => 'Salario 4/2026',
                'status' => 'recebido',
            ])
            ->assertJsonPath('meta.total_amount', 8660)
            ->assertJsonPath('meta.count', 2)
            ->assertJsonPath('meta.previous_period.total_amount', 6900)
            ->assertJsonPath('meta.previous_period.delta_amount', 1760)
            ->assertJsonPath('meta.previous_period.delta_percentage', 25.5)
            ->assertJson(fn (AssertableJson $json) => $json
                ->has('meta.breakdown_by_type', 2)
                ->where('meta.breakdown_by_type.0.type', 'salario')
                ->where('meta.breakdown_by_type.0.total_amount', 7700)
                ->whereType('meta.previous_period.label', 'string')
                ->etc()
            );
    }

    public function test_previous_period_handles_year_rollover(): void
    {
        $user = User::factory()->create();

        $this->createIncome($user, [
            'description' => 'Salario 1/2026',
            'amount' => 3000,
            'date' => '2026-01-05',
        ]);

        $this->createIncome($user, [
            'description' => 'Salario 12/2025',
            'amount' => 2000,
            'date' => '2025-12-05',
        ]);

        $response = $this
            ->actingAs($user)
            ->getJson('/api/incomes?month=1&year=2026');

        $response
            ->assertOk()
            ->assertJsonPath('meta.previous_period.month', 12)
            ->assertJsonPath('meta.previous_period.year', 2025)
            ->assertJsonPath('meta.previous_period.total_amount', 2000)
            ->assertJsonPath('meta.previous_period.delta_amount', 1000)
            ->assertJsonPath('meta.previous_period.delta_percentage', 50);
    }

    public function test_store_rejects_invalid_income_status(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->postJson('/api/incomes', [
                'description' => 'Receita invalida',
                'amount' => 1200,
                'date' => '2026-04-10',
                'category' => 'Trabalho',
                'type' => 'salario',
                'status' => 'atrasado',
                'notes' => 'Teste',
            ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);
    }

    public function test_update_rejects_invalid_income_status(): void
    {
        $user = User::factory()->create();
        $income = $this->createIncome($user);

        $response = $this
            ->actingAs($user)
            ->putJson("/api/incomes/{$income->id}", [
                'description' => 'Receita atualizada',
                'amount' => 1500,
                'date' => '2026-04-12',
                'category' => 'Trabalho',
                'type' => 'salario',
                'status' => 'cancelado',
                'notes' => 'Teste',
            ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);
    }

    private function createIncome(User $user, array $attributes = []): Income
    {
        return Income::create(array_merge([
            'user_id' => $user->id,
            'description' => 'Salario principal',
            'amount' => 1000,
            'date' => '2026-04-05',
            'category' => 'Trabalho',
            'type' => 'salario',
            'status' => 'recebido',
            'notes' => 'Receita fixa.',
        ], $attributes));
    }
}
