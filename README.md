# AppGS1 — resultados via GS1 Digital Link

Site PHP que interpreta o QR Code no padrão GS1 Digital Link, mostra os identificadores da URL e completa a ficha do produto no PostgreSQL do **Supabase**. Hospedagem prevista: **Render** (Docker).

## Local (XAMPP em D:\xampp)

O PHP do XAMPP atual é **7.4.8**; o código foi escrito para essa versão. O Docker no Render continua em PHP 8.2.

1. Copie `.env.example` para `.env` e preencha `DATABASE_URL` (Session pooler do Supabase, `sslmode=require`).
2. No SQL Editor do Supabase, execute `database/schema.sql`.
3. Para conectar ao Postgres do Supabase, ative no `D:\xampp\php\php.ini` as extensões `pgsql` e `pdo_pgsql` (se os DLLs existirem em `D:\xampp\php\ext`).
4. Rode o servidor embutido:

```bat
D:\xampp\php\php.exe -S localhost:8080 -t public public\index.php
```

Abra:

`http://localhost:8080/01/07893336004902/10/0000000280222/21/1395270000270?11=220218&17=220228&3103=000500&3922=2495`

## Render

1. Conecte o repositório GitHub e faça deploy do `Dockerfile` (ou use o blueprint `render.yaml`).
2. Defina as variáveis de ambiente:
   - `DATABASE_URL` — Session pooler do Supabase (`sslmode=require`)
   - `APP_NAME` — nome exibido no site
   - `APP_CURRENCY` — `BRL`
   - `APP_ADMIN_TOKEN` — senha do painel `/admin`
   - `APP_LOGO_URL` — opcional
3. Health check: `/health`
4. O host do Render substitui o domínio do QR; o path GS1 permanece o mesmo.
