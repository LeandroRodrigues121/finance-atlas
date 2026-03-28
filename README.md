# Finance Atlas API (Back-end)

Back-end em Laravel responsavel pelas regras de negocio, autenticacao e endpoints da API.

## Stack

- Laravel
- SQLite (desenvolvimento local)
- Autenticacao por sessao (`auth:web`)

## Endpoints principais

- `POST /api/login`
- `POST /api/logout`
- `GET /api/me`
- `GET|POST|PUT|DELETE /api/incomes`
- `GET|POST|PUT|DELETE /api/expenses`
- `GET|POST|PUT|DELETE /api/debts`
- `GET /api/dashboard?month=MM&year=YYYY`
- `GET /api/reports/annual?year=YYYY`

## Rodar localmente

Pre-requisitos:

- PHP 8.3+
- Composer 2+

Passos:

```powershell
Copy-Item .env.example .env
composer install
php artisan key:generate
New-Item -ItemType File -Path database\database.sqlite -Force
php artisan migrate --seed
php artisan serve
```

API em: `http://127.0.0.1:8000`

## Front-end separado

O front agora fica em `../Front-end` e deve ser executado em outro terminal.

