<?php

namespace Database\Seeders;

use App\Models\Debt;
use App\Models\Expense;
use App\Models\Income;
use App\Models\User;
use Illuminate\Database\Seeder;

class FinanceDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::where('username', 'admin')->first();

        if (! $user) {
            return;
        }

        $year = now()->year;

        foreach (range(1, 12) as $month) {
            Income::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'description' => "Salário $month/$year",
                    'date' => now()->setDate($year, $month, 5)->toDateString(),
                ],
                [
                    'amount' => 7500 + ($month * 50),
                    'category' => 'Trabalho',
                    'type' => 'salario',
                    'notes' => 'Receita fixa mensal.',
                ]
            );

            if ($month % 2 === 0) {
                Income::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'description' => "Freela $month/$year",
                        'date' => now()->setDate($year, $month, 20)->toDateString(),
                    ],
                    [
                        'amount' => 900 + ($month * 15),
                        'category' => 'Projetos',
                        'type' => 'renda_extra',
                        'notes' => 'Renda extra de projetos paralelos.',
                    ]
                );
            }

            $expenses = [
                ['Aluguel', 'moradia', 2100, 'paga'],
                ['Supermercado', 'alimentacao', 780 + ($month * 20), 'paga'],
                ['Transporte', 'transporte', 380, 'paga'],
                ['Lazer', 'lazer', 260 + ($month * 10), 'pendente'],
                ['Plano de Saúde', 'saude', 450, 'paga'],
                ['Internet e Energia', 'contas_fixas', 520, 'paga'],
            ];

            foreach ($expenses as $index => $expense) {
                Expense::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'description' => $expense[0]." $month/$year",
                        'date' => now()->setDate($year, $month, 8 + $index)->toDateString(),
                    ],
                    [
                        'amount' => $expense[2],
                        'category' => $expense[1],
                        'status' => $month < now()->month ? 'paga' : $expense[3],
                        'notes' => 'Lançamento automático de exemplo.',
                    ]
                );
            }
        }

        Debt::updateOrCreate(
            [
                'user_id' => $user->id,
                'description' => 'Cartão de crédito parcelado',
            ],
            [
                'total_amount' => 4800,
                'paid_amount' => 1200,
                'due_date' => now()->addMonths(6)->toDateString(),
                'status' => 'pendente',
                'notes' => 'Parcelamento de compras em 12x.',
            ]
        );

        Debt::updateOrCreate(
            [
                'user_id' => $user->id,
                'description' => 'Empréstimo pessoal',
            ],
            [
                'total_amount' => 12000,
                'paid_amount' => 6000,
                'due_date' => now()->addMonths(18)->toDateString(),
                'status' => 'pendente',
                'notes' => 'Empréstimo para reforma residencial.',
            ]
        );
    }
}
