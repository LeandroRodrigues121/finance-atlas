<script setup>
import { nextTick, onBeforeUnmount, onMounted, reactive, ref } from 'vue';
import { Chart, registerables } from 'chart.js';
import api from '@/services/api';
import { formatCurrency, monthName } from '@/utils/formatters';

Chart.register(...registerables);

const currentDate = new Date();
const filters = reactive({
    month: currentDate.getMonth() + 1,
    year: currentDate.getFullYear(),
});

const dashboard = ref(null);
const loading = ref(false);
const error = ref('');
const incomeExpenseCanvas = ref(null);
const categoryCanvas = ref(null);

let incomeExpenseChart = null;
let categoryChart = null;

const months = [...Array(12)].map((_, index) => ({
    value: index + 1,
    label: monthName(index + 1),
}));

const fetchDashboard = async () => {
    loading.value = true;
    error.value = '';

    try {
        const { data } = await api.get('/dashboard', {
            params: {
                month: filters.month,
                year: filters.year,
            },
        });

        dashboard.value = data;
        await nextTick();
        renderCharts();
    } catch {
        error.value = 'Não foi possível carregar o dashboard.';
    } finally {
        loading.value = false;
    }
};

const renderCharts = () => {
    if (!dashboard.value) {
        return;
    }

    if (!incomeExpenseCanvas.value || !categoryCanvas.value) {
        return;
    }

    if (incomeExpenseChart) {
        incomeExpenseChart.destroy();
    }

    if (categoryChart) {
        categoryChart.destroy();
    }

    incomeExpenseChart = new Chart(incomeExpenseCanvas.value, {
        type: 'bar',
        data: {
            labels: dashboard.value.charts.income_vs_expense_by_month.labels,
            datasets: [
                {
                    label: 'Receitas',
                    data: dashboard.value.charts.income_vs_expense_by_month.incomes,
                    backgroundColor: '#1f7a8c',
                    borderRadius: 8,
                },
                {
                    label: 'Despesas',
                    data: dashboard.value.charts.income_vs_expense_by_month.expenses,
                    backgroundColor: '#bf4342',
                    borderRadius: 8,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                },
            },
        },
    });

    const categoryValues = [...dashboard.value.charts.expenses_by_category.values];
    const categoryLabels = [...dashboard.value.charts.expenses_by_category.labels];

    categoryChart = new Chart(categoryCanvas.value, {
        type: 'doughnut',
        data: {
            labels: categoryLabels.length ? categoryLabels : ['Sem despesas no período'],
            datasets: [
                {
                    data: categoryValues.length ? categoryValues : [1],
                    backgroundColor: [
                        '#1f7a8c',
                        '#bf4342',
                        '#f7b32b',
                        '#3a7d44',
                        '#5f0f40',
                        '#0d3b66',
                        '#f4a261',
                        '#9d4edd',
                    ],
                    borderWidth: 0,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                },
            },
        },
    });
};

onMounted(fetchDashboard);

onBeforeUnmount(() => {
    if (incomeExpenseChart) {
        incomeExpenseChart.destroy();
    }
    if (categoryChart) {
        categoryChart.destroy();
    }
});
</script>

<template>
    <section class="page">
        <header class="page-header">
            <div>
                <h2>Dashboard Financeiro</h2>
                <p>Visão mensal e anual com indicadores estratégicos.</p>
            </div>

            <div class="filters">
                <label>
                    Mês
                    <select v-model.number="filters.month">
                        <option v-for="month in months" :key="month.value" :value="month.value">
                            {{ month.label }}
                        </option>
                    </select>
                </label>
                <label>
                    Ano
                    <input v-model.number="filters.year" type="number" min="2000" max="2100" />
                </label>
                <button class="btn-primary" type="button" @click="fetchDashboard" :disabled="loading">
                    Atualizar
                </button>
            </div>
        </header>

        <p class="error-text" v-if="error">{{ error }}</p>

        <div class="cards-grid" v-if="dashboard">
            <article class="metric-card">
                <h3>Receitas do mês</h3>
                <strong>{{ formatCurrency(dashboard.monthly.income_total) }}</strong>
            </article>
            <article class="metric-card">
                <h3>Despesas do mês</h3>
                <strong>{{ formatCurrency(dashboard.monthly.expense_total) }}</strong>
            </article>
            <article class="metric-card">
                <h3>Saldo do mês</h3>
                <strong>{{ formatCurrency(dashboard.monthly.balance) }}</strong>
            </article>
            <article class="metric-card">
                <h3>Receitas do ano</h3>
                <strong>{{ formatCurrency(dashboard.annual.income_total) }}</strong>
            </article>
            <article class="metric-card">
                <h3>Despesas do ano</h3>
                <strong>{{ formatCurrency(dashboard.annual.expense_total) }}</strong>
            </article>
            <article class="metric-card">
                <h3>Saldo anual</h3>
                <strong>{{ formatCurrency(dashboard.annual.balance) }}</strong>
            </article>
            <article class="metric-card">
                <h3>Comprometimento do mês</h3>
                <strong>{{ dashboard.indicators.expense_commitment_percent }}%</strong>
            </article>
            <article class="metric-card">
                <h3>Dívidas em aberto</h3>
                <strong>{{ formatCurrency(dashboard.indicators.open_debt_total) }}</strong>
            </article>
            <article class="metric-card">
                <h3>Dívidas pagas</h3>
                <strong>{{ formatCurrency(dashboard.indicators.paid_debt_total) }}</strong>
            </article>
        </div>

        <div class="charts-grid" v-if="dashboard">
            <article class="chart-card">
                <h3>Receitas vs Despesas por Mês</h3>
                <div class="chart-holder">
                    <canvas ref="incomeExpenseCanvas" />
                </div>
            </article>

            <article class="chart-card">
                <h3>Despesas por Categoria no Mês</h3>
                <div class="chart-holder">
                    <canvas ref="categoryCanvas" />
                </div>
            </article>
        </div>
    </section>
</template>
