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
        $currentMonth = now()->month;

        foreach (range(1, 12) as $month) {
            Income::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'description' => "Salario $month/$year",
                    'date' => now()->setDate($year, $month, 5)->toDateString(),
                ],
                [
                    'amount' => 7500 + ($month * 50),
                    'category' => 'Trabalho',
                    'type' => 'salario',
                    'status' => 'recebido',
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
                        'status' => $month === $currentMonth ? 'pendente' : 'recebido',
                        'notes' => 'Renda extra de projetos paralelos.',
                    ]
                );
            }

            if ($month % 3 === 1) {
                Income::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'description' => "Venda $month/$year",
                        'date' => now()->setDate($year, $month, 1)->toDateString(),
                    ],
                    [
                        'amount' => 1300 + ($month * 25),
                        'category' => 'Vendas',
                        'type' => 'outros',
                        'status' => 'recebido',
                        'notes' => 'Entrada eventual com itens vendidos.',
                    ]
                );
            }

            $expenses = [
                ['Aluguel', 'moradia', 2100, 'Transferencia', 'paga'],
                ['Supermercado', 'alimentacao', 780 + ($month * 20), 'Nubank', 'paga'],
                ['Transporte', 'transporte', 380, 'Debito', 'paga'],
                ['Lazer', 'lazer', 260 + ($month * 10), 'Cartao Inter', 'pendente'],
                ['Plano de Saude', 'saude', 450, 'Debito automatico', 'paga'],
                ['Internet e Energia', 'contas_fixas', 520, 'Nubank', 'paga'],
            ];

            foreach ($expenses as $index => $expense) {
                Expense::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'description' => $expense[0] . " $month/$year",
                        'date' => now()->setDate($year, $month, 8 + $index)->toDateString(),
                    ],
                    [
                        'amount' => $expense[2],
                        'category' => $expense[1],
                        'payment_method' => $expense[3],
                        'status' => $month < $currentMonth ? 'paga' : $expense[4],
                        'notes' => 'Lancamento automatico de exemplo.',
                    ]
                );
            }
        }

        Debt::updateOrCreate(
            [
                'user_id' => $user->id,
                'description' => 'Cartao de credito parcelado',
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
                'description' => 'Emprestimo pessoal',
            ],
            [
                'total_amount' => 12000,
                'paid_amount' => 6000,
                'due_date' => now()->addMonths(18)->toDateString(),
                'status' => 'pendente',
                'notes' => 'Emprestimo para reforma residencial.',
            ]
        );
    }
}
