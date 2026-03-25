# Finance Atlas - Gestão Financeira Pessoal

Sistema web completo para controle anual de finanças pessoais com:
- Backend em Laravel (API)
- Banco SQLite
- Frontend em Vue.js (SPA integrada ao Laravel)
- Dashboard mensal/anual com gráficos e indicadores

## Arquitetura aplicada

- `Laravel 13` para API, autenticação por sessão e regras de negócio.
- `SQLite` para ambiente local/teste com setup rápido.
- `Vue 3 + Vue Router + Pinia + Chart.js` para interface moderna e responsiva.
- Projeto monolítico (Laravel + Vue no mesmo repositório) para reduzir complexidade inicial e facilitar manutenção.

## Funcionalidades implementadas

- Login com usuário padrão de teste.
- CRUD de receitas:
  - descrição, valor, data, categoria, tipo, observação.
- CRUD de despesas:
  - descrição, valor, data, categoria, status, observação.
- CRUD de dívidas:
  - descrição, valor total, valor pago, valor restante, vencimento, status, observações.
- Organização mensal e anual:
  - filtros por mês/ano.
  - resumo financeiro consolidado.
  - visão anual com saldo acumulado.
- Dashboard:
  - cards de indicadores mensais/anuais.
  - gráfico de receitas vs despesas por mês.
  - gráfico de despesas por categoria.

## Credenciais padrão

- Usuário: `admin`
- Senha: `123456`

## Endpoints principais da API

- `POST /api/login`
- `POST /api/logout`
- `GET /api/me`
- `GET|POST|PUT|DELETE /api/incomes`
- `GET|POST|PUT|DELETE /api/expenses`
- `GET|POST|PUT|DELETE /api/debts`
- `GET /api/dashboard?month=MM&year=YYYY`
- `GET /api/reports/annual?year=YYYY`

## Estrutura do projeto

- `app/Http/Controllers/Api` - controllers da API.
- `app/Models` - modelos `Income`, `Expense`, `Debt`, `User`.
- `database/migrations` - tabelas de usuários e finanças.
- `database/seeders` - usuário admin e dados iniciais.
- `resources/js` - SPA Vue (rotas, store, páginas, serviços).
- `resources/views/app.blade.php` - ponto de montagem da SPA.

## Como rodar localmente

Pré-requisitos:
- PHP 8.3+
- Composer 2+
- Node.js 20+
- NPM 10+

Passos:

```bash
cp .env.example .env
```

No Windows PowerShell:

```powershell
Copy-Item .env.example .env
```

Depois:

```bash
composer install
php artisan key:generate
```

Criar arquivo SQLite:

```bash
touch database/database.sqlite
```

No Windows PowerShell:

```powershell
New-Item -ItemType File -Path database\database.sqlite -Force
```

Migrar e popular dados:

```bash
php artisan migrate --seed
```

Instalar frontend:

```bash
npm install
npm run dev
```

Subir backend em outro terminal:

```bash
php artisan serve
```

Acessar: `http://127.0.0.1:8000`

## Melhorias sugeridas para evolução

1. Metas financeiras e acompanhamento de progresso.
2. Planejamento orçamentário por categoria (budget mensal).
3. Contas bancárias/cartões múltiplos.
4. Parcelamentos com geração automática de lançamentos.
5. Importação de extrato CSV/OFX.
6. Alertas de vencimento e notificações.
7. Relatórios PDF e exportação Excel.
8. Módulo de investimentos com carteira e rentabilidade detalhada.
9. Multiusuário com perfis (admin/familiar).
10. Testes automatizados de API e frontend.
