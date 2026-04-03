# Product Management API

API RESTful para gerenciamento de produtos e categorias, construída com **Laravel 13**, **PostgreSQL 17** e **Docker**.

---

## Requisitos

- [Docker](https://docs.docker.com/get-docker/) e [Docker Compose](https://docs.docker.com/compose/install/) instalados
- Portas disponíveis: **80** (Nginx), **5432** (PostgreSQL), **5050** (pgAdmin)

> Se alguma dessas portas já estiver em uso, altere os valores `APP_PORT`, `DB_PORT` ou `PGADMIN_PORT` no `.env`.

---

## 1. Clonar o repositório

```bash
git clone <url-do-repositorio> product-management-api
cd product-management-api
```

---

## 2. Configurar variáveis de ambiente

Copie o arquivo de exemplo e ajuste se necessário:

```bash
cp .env.example .env
```

Os valores padrão já funcionam com o Docker Compose (banco `example_app`, usuário `root`, senha `secret`).

---

## 3. Subir os containers

```bash
docker compose up -d --build
```

Isso inicializa 4 serviços:

| Container       | Serviço         | Porta padrão |
|-----------------|-----------------|--------------|
| `pma-app`       | PHP-FPM 8.4     | —            |
| `pma-nginx`     | Nginx           | `80`         |
| `pma-postgres`  | PostgreSQL 17   | `5432`       |
| `pma-pgadmin`   | pgAdmin 4       | `5050`       |

Aguarde o health check do PostgreSQL finalizar antes de prosseguir:

```bash
docker compose ps
```

Todos os serviços devem estar com status **running** (e o postgres como **healthy**).

---

## 4. Instalar dependências do PHP

```bash
docker compose exec app composer install
```

---

## 5. Gerar a chave da aplicação

```bash
docker compose exec app php artisan key:generate
```

---

## 6. Executar as migrations

```bash
docker compose exec app php artisan migrate
```

Isso cria as tabelas:

- `users` — Usuários
- `cache` — Cache do framework
- `jobs` / `job_batches` / `failed_jobs` — Fila de jobs
- `categories` — Categorias de produtos
- `products` — Produtos
- `personal_access_tokens` — Tokens de autenticação (Sanctum)
- `refresh_tokens` — Tokens de refresh
- `audit_logs` — Logs de auditoria

---

## 7. Executar os seeders

Popula o banco com dados iniciais (usuários, categorias e produtos):

```bash
docker compose exec app php artisan db:seed
```

Para executar um seeder específico:

```bash
docker compose exec app php artisan db:seed --class=CategorySeeder
docker compose exec app php artisan db:seed --class=ProductSeeder
docker compose exec app php artisan db:seed --class=UserSeeder
```

---

## 8. Iniciar o queue worker (fila `audit`)

A aplicação usa fila com driver `database` (`QUEUE_CONNECTION=database`). Os listeners de auditoria (`AuditProductListener` e `AuditAuthListener`) enviam jobs para uma fila chamada **`audit`**. Isso significa que **o worker precisa escutar essa fila explicitamente**, caso contrário os logs de auditoria nunca serão processados.

### Por que existe a fila `audit`?

Toda vez que um produto é criado, atualizado ou excluído — ou quando um usuário faz login/registro — um evento é disparado e o listener correspondente registra um log na tabela `audit_logs`. Esses listeners implementam `ShouldQueue` com `$queue = 'audit'`, ou seja, os jobs vão para uma fila separada da `default`, permitindo priorizar ou isolar o processamento de auditoria.

### Iniciar o worker escutando a fila `audit`

```bash
docker compose exec app php artisan queue:work --queue=audit
```

### Escutar a fila `audit` junto com a `default`

Se quiser que o mesmo worker processe jobs de ambas as filas (priorizando `audit`):

```bash
docker compose exec app php artisan queue:work --queue=audit,default
```

### Rodar em segundo plano (daemon)

```bash
docker compose exec -d app php artisan queue:work --queue=audit,default --sleep=3 --tries=3
```

> **Importante:** Se rodar `queue:work` sem `--queue=audit`, o worker só processa a fila `default` e os logs de auditoria ficarão pendentes para sempre na tabela `jobs`.

> **Dica:** Em produção, use um supervisor (como o Supervisor do Linux) para manter o worker sempre rodando.

---

## 9. Configurar banco de dados de testes (`.env.testing`)

Os testes utilizam um banco separado chamado `example_app_testing`. Esse banco é criado automaticamente pelo script `docker/postgres/init-test-db.sql` quando o container do PostgreSQL sobe pela primeira vez.

### Arquivo `.env.testing`

O projeto já inclui um arquivo `.env.testing` na raiz. Ele configura a conexão com o banco de testes. Se precisar criar ou ajustar:

```bash
cp .env.example .env.testing
```

As variáveis mais importantes no `.env.testing`:

```env
APP_ENV=testing

DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=example_app_testing
DB_USERNAME=root
DB_PASSWORD=secret

QUEUE_CONNECTION=database
```

> **Nota:** O `phpunit.xml` também define variáveis de ambiente que **sobrescrevem** o `.env.testing` durante os testes. Nele, `QUEUE_CONNECTION` é definido como `sync` para que os jobs (incluindo os de auditoria) rodem de forma síncrona nos testes, sem precisar de um worker.

### Rodar migrations no banco de testes

Antes de rodar os testes pela primeira vez, execute as migrations no banco de testes:

```bash
docker compose exec app php artisan migrate --env=testing
```

---

## 10. Executar os testes

### Rodar todos os testes

```bash
docker compose exec app php artisan test
```

### Rodar com cobertura detalhada

```bash
docker compose exec app php artisan test --verbose
```

### Rodar testes em paralelo (paratest)

```bash
docker compose exec app php artisan test --parallel
```

### Rodar apenas um grupo de testes

```bash
# Testes de autenticação
docker compose exec app php artisan test --filter=Auth

# Testes de produtos
docker compose exec app php artisan test --filter=Product

# Um teste específico
docker compose exec app php artisan test --filter=LoginTest
```

### Atalho via Composer

```bash
docker compose exec app composer run test
```

---

## 11. Comandos úteis

### Setup rápido (composer script)

Executa `composer install`, copia `.env`, gera key e roda migrate de uma vez:

```bash
docker compose exec app composer run setup
```

### Limpar caches

```bash
docker compose exec app php artisan config:clear
docker compose exec app php artisan cache:clear
docker compose exec app php artisan route:clear
```

### Resetar banco (migrate fresh + seed)

```bash
docker compose exec app php artisan migrate:fresh --seed
```

### Listar rotas

```bash
docker compose exec app php artisan route:list
```

### Acessar o Tinker (REPL)

```bash
docker compose exec app php artisan tinker
```

### Parar os containers

```bash
docker compose down
```

### Parar e remover volumes (dados do banco)

```bash
docker compose down -v
```

---

## 12. Acessando a API

Base URL: `http://localhost/api`

### Autenticação (`/api/auth`)

| Método | Endpoint               | Autenticado | Descrição              |
|--------|------------------------|-------------|------------------------|
| POST   | `/api/auth/register`   | Não         | Registrar novo usuário |
| POST   | `/api/auth/login`      | Não         | Login (retorna token)  |
| POST   | `/api/auth/refresh`    | Não         | Renovar token          |
| GET    | `/api/auth/me`         | Sim         | Dados do usuário logado|
| POST   | `/api/auth/logout`     | Sim         | Logout                 |
| POST   | `/api/auth/logout-all` | Sim         | Logout de todas sessões|

### Produtos (`/api/products`)

| Método | Endpoint              | Autenticado | Descrição              |
|--------|-----------------------|-------------|------------------------|
| GET    | `/api/products`       | Sim         | Listar produtos        |
| POST   | `/api/products`       | Sim         | Criar produto          |
| GET    | `/api/products/{id}`  | Sim         | Detalhar produto       |
| PUT    | `/api/products/{id}`  | Sim         | Atualizar produto      |
| DELETE | `/api/products/{id}`  | Sim         | Excluir produto        |

### Categorias (`/api/categories`)

| Método | Endpoint                 | Autenticado | Descrição              |
|--------|--------------------------|-------------|------------------------|
| GET    | `/api/categories`        | Sim         | Listar categorias      |
| POST   | `/api/categories`        | Sim         | Criar categoria        |
| GET    | `/api/categories/{id}`   | Sim         | Detalhar categoria     |
| PUT    | `/api/categories/{id}`   | Sim         | Atualizar categoria    |
| DELETE | `/api/categories/{id}`   | Sim         | Excluir categoria      |

### Health check

```
GET /up
```

---

## 13. pgAdmin

Acesse o pgAdmin em `http://localhost:5050` com as credenciais padrão:

- **Email:** `admin@admin.com`
- **Senha:** `admin`

Para conectar ao banco de dados dentro do pgAdmin:

- **Host:** `postgres`
- **Porta:** `5432`
- **Database:** `example_app`
- **Usuário:** `root`
- **Senha:** `secret`

---

## Resumo rápido (copiar e colar)

```bash
# 1. Clonar e entrar no projeto
git clone <url-do-repositorio> product-management-api
cd product-management-api

# 2. Configurar ambiente
cp .env.example .env
cp .env.example .env.testing
# Altere DB_DATABASE=example_app_testing no .env.testing

# 3. Subir containers
docker compose up -d --build

# 4. Instalar dependências
docker compose exec app composer install

# 5. Gerar chave
docker compose exec app php artisan key:generate

# 6. Rodar migrations (principal + testes)
docker compose exec app php artisan migrate
docker compose exec app php artisan migrate --env=testing

# 7. Popular banco com dados iniciais
docker compose exec app php artisan db:seed

# 8. Iniciar worker de filas (fila audit!)
docker compose exec -d app php artisan queue:work --queue=audit,default --sleep=3 --tries=3

# 9. Rodar testes
docker compose exec app php artisan test
```

---

## Stack

- **PHP** 8.4 (FPM Alpine)
- **Laravel** 13
- **PostgreSQL** 17
- **Nginx** (Alpine)
- **Sanctum** — Autenticação via tokens
- **Docker** & **Docker Compose**
