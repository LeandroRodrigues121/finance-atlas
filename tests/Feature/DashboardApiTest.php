<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_get_dashboard_payload(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->getJson('/api/dashboard');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'period' => ['month', 'year'],
                'monthly' => ['income_total', 'expense_total', 'balance'],
                'annual' => ['income_total', 'expense_total', 'balance'],
                'charts' => [
                    'income_vs_expense_by_month' => ['labels', 'incomes', 'expenses'],
                    'expenses_by_category' => ['labels', 'values'],
                ],
                'indicators' => ['expense_commitment_percent', 'open_debt_total', 'paid_debt_total', 'recommended_expense_limit'],
            ]);
    }
}
