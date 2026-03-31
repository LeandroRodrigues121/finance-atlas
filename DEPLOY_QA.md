# Deploy para QA (Vercel + Render + Aiven MySQL)

## 1) Banco de dados (Aiven MySQL)

1. Crie um servico MySQL no Aiven.
2. No `Quick connect`, copie:
   - Host
   - Port
   - Database
   - Username
   - Password
3. Guarde esses dados para as variaveis do Render.

## 2) Back-end no Render

1. Crie um novo `Web Service` no Render apontando para o repositorio da pasta `Back-end`.
2. Em `Language`, selecione `Docker`.
3. Se o repo tiver front e back no mesmo repositorio, configure `Root Directory` como `Back-end`.
4. Em runtime `Docker`, os campos de build/start command podem ficar em branco.
5. Configure as variaveis de ambiente:
   - `APP_ENV=production`
   - `APP_DEBUG=false`
   - `APP_KEY=` (gere local com `php artisan key:generate --show`)
   - `APP_URL=https://SEU_BACKEND.onrender.com`
   - `DB_CONNECTION=mysql`
   - `DB_HOST=...` (Aiven)
   - `DB_PORT=...` (Aiven)
   - `DB_DATABASE=...` (Aiven)
   - `DB_USERNAME=...` (Aiven)
   - `DB_PASSWORD=...` (Aiven)
   - `MYSQL_ATTR_SSL_CA=/etc/ssl/certs/ca-certificates.crt`
   - `SESSION_DRIVER=database`
   - `SESSION_SECURE_COOKIE=true`
   - `SESSION_SAME_SITE=none`
   - `SESSION_DOMAIN=null`
   - `CORS_ALLOWED_ORIGINS=https://SEU_FRONT.vercel.app`
   - `CORS_SUPPORTS_CREDENTIALS=true`
6. Apos o primeiro deploy, abra o Shell do servico e execute:
   - `php artisan migrate --force`
7. Se quiser dados iniciais para o QA:
   - `php artisan db:seed --force`

## 3) Front-end no Vercel

1. Crie um projeto no Vercel com a pasta `Front-end` como `Root Directory`.
2. Defina variavel de ambiente:
   - `VITE_API_BASE_URL=https://SEU_BACKEND.onrender.com/api`
3. Faca deploy.

## 4) Sobre o SQLite local

- O SQLite pode continuar no ambiente local para desenvolvimento/testes.
- Em producao, o back vai usar MySQL apenas mudando `DB_CONNECTION=mysql` e os dados de conexao.
- O `phpunit.xml` ja esta preparado para SQLite em memoria nos testes automatizados.

## 5) Checklist rapido de validacao

1. Acesse front no Vercel.
2. Faca login.
3. Valide `GET /api/me` no Network.
4. Crie uma receita/despesa e confirme persistencia.
5. Recarregue a pagina para confirmar sessao ativa.
