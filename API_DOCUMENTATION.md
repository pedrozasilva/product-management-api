# Product Management API - Documentação

**Versão:** 1.0  
**Base URL:** `http://localhost:8000/api`  
**Autenticação:** Bearer Token (Laravel Sanctum)

---

## Índice

1. [Visão Geral](#visão-geral)
2. [Autenticação](#autenticação)
3. [Formato de Respostas](#formato-de-respostas)
4. [Endpoints de Autenticação](#endpoints-de-autenticação)
5. [Endpoints de Produtos](#endpoints-de-produtos)
6. [Endpoints de Categorias](#endpoints-de-categorias)
7. [Códigos de Status HTTP](#códigos-de-status-http)

---

## Visão Geral

API RESTful para gestão de produtos e categorias, construída com Laravel 13, PHP 8.4 e PostgreSQL. Utiliza Laravel Sanctum para autenticação via tokens com suporte a refresh tokens.

### Stack Tecnológica

| Tecnologia | Versão |
|---|---|
| PHP | 8.4 |
| Laravel | 13 |
| PostgreSQL | 17 |
| Laravel Sanctum | — |
| Docker | — |

### Headers Obrigatórios

| Header | Valor | Obrigatório |
|---|---|---|
| `Accept` | `application/json` | Sim |
| `Content-Type` | `application/json` | Sim (POST/PUT/PATCH) |
| `Authorization` | `Bearer {access_token}` | Sim (rotas protegidas) |

---

## Autenticação

A API utiliza **Laravel Sanctum** com um sistema de **access token + refresh token**.

- **Access Token:** Token de curta duração (padrão: 15 minutos) enviado no header `Authorization`.
- **Refresh Token:** Token de longa duração (padrão: 7 dias) usado para obter um novo par de tokens sem necessidade de re-login.

### Fluxo de Autenticação

1. O utilizador faz **register** ou **login** e recebe um `access_token` e um `refresh_token`.
2. Usa o `access_token` no header `Authorization: Bearer {token}` em todas as requisições protegidas.
3. Quando o `access_token` expira, usa o endpoint `/auth/refresh` com o `refresh_token` para obter novos tokens.
4. Para encerrar sessão, usa `/auth/logout` (dispositivo atual) ou `/auth/logout-all` (todos os dispositivos).

---

## Formato de Respostas

### Resposta de Sucesso

```json
{
  "success": true,
  "data": { },
  "message": "Mensagem de sucesso."
}
```

### Resposta de Sucesso Paginada

```json
{
  "success": true,
  "data": [ ],
  "pagination": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 15,
    "total": 72
  },
  "message": "Listagem realizada com sucesso."
}
```

### Resposta de Erro

```json
{
  "success": false,
  "message": "Mensagem de erro."
}
```

### Resposta de Erro de Validação (422)

```json
{
  "success": false,
  "message": "Erro de validação.",
  "errors": {
    "campo": ["Mensagem de erro do campo."]
  }
}
```

---

## Endpoints de Autenticação

### POST `/api/auth/register`

Registra um novo utilizador.

**Autenticação:** Não

**Body:**

| Campo | Tipo | Obrigatório | Regras |
|---|---|---|---|
| `name` | string | Sim | Máx: 255 caracteres |
| `email` | string | Sim | Email válido, único na tabela `users` |
| `password` | string | Sim | Mín: 8 caracteres |
| `password_confirmation` | string | Sim | Deve ser igual ao `password` |

**Exemplo de Request:**

```json
{
  "name": "João Silva",
  "email": "joao@email.com",
  "password": "senha1234",
  "password_confirmation": "senha1234"
}
```

**Exemplo de Response (201):**

```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "João Silva",
      "email": "joao@email.com",
      "is_active": true,
      "created_at": "2026-04-02T10:00:00.000000Z",
      "updated_at": "2026-04-02T10:00:00.000000Z"
    },
    "tokens": {
      "access_token": "1|abc123...",
      "refresh_token": "def456...",
      "token_type": "Bearer",
      "expires_in": 900
    }
  },
  "message": "Usuário registrado com sucesso."
}
```

---

### POST `/api/auth/login`

Autentica um utilizador existente.

**Autenticação:** Não

**Body:**

| Campo | Tipo | Obrigatório | Regras |
|---|---|---|---|
| `email` | string | Sim | Email válido |
| `password` | string | Sim | — |

**Exemplo de Request:**

```json
{
  "email": "joao@email.com",
  "password": "senha1234"
}
```

**Exemplo de Response (200):**

```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "João Silva",
      "email": "joao@email.com",
      "is_active": true,
      "created_at": "2026-04-02T10:00:00.000000Z",
      "updated_at": "2026-04-02T10:00:00.000000Z"
    },
    "tokens": {
      "access_token": "2|xyz789...",
      "refresh_token": "ghi012...",
      "token_type": "Bearer",
      "expires_in": 900
    }
  },
  "message": "Login realizado com sucesso."
}
```

**Exemplo de Response de Erro (401):**

```json
{
  "success": false,
  "message": "Credenciais inválidas."
}
```

---

### POST `/api/auth/refresh`

Renova o par de tokens (access + refresh) usando um refresh token válido.

**Autenticação:** Não

**Body:**

| Campo | Tipo | Obrigatório | Regras |
|---|---|---|---|
| `refresh_token` | string | Sim | — |

**Exemplo de Request:**

```json
{
  "refresh_token": "def456..."
}
```

**Exemplo de Response (200):**

```json
{
  "success": true,
  "data": {
    "tokens": {
      "access_token": "3|new_token...",
      "refresh_token": "new_refresh...",
      "token_type": "Bearer",
      "expires_in": 900
    }
  },
  "message": "Tokens renovados com sucesso."
}
```

**Exemplo de Response de Erro (401):**

```json
{
  "success": false,
  "message": "Refresh token inválido ou expirado."
}
```

---

### GET `/api/auth/me`

Retorna os dados do utilizador autenticado.

**Autenticação:** Sim

**Exemplo de Response (200):**

```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "João Silva",
    "email": "joao@email.com",
    "is_active": true,
    "created_at": "2026-04-02T10:00:00.000000Z",
    "updated_at": "2026-04-02T10:00:00.000000Z"
  },
  "message": "Usuário autenticado."
}
```

---

### POST `/api/auth/logout`

Encerra a sessão do dispositivo atual (revoga o token atual e o refresh token associado).

**Autenticação:** Sim

**Exemplo de Response (200):**

```json
{
  "success": true,
  "message": "Logout realizado com sucesso."
}
```

---

### POST `/api/auth/logout-all`

Encerra todas as sessões do utilizador em todos os dispositivos.

**Autenticação:** Sim

**Exemplo de Response (200):**

```json
{
  "success": true,
  "message": "Logout realizado em todos os dispositivos."
}
```

---

## Endpoints de Produtos

> Todos os endpoints de produtos requerem autenticação (`Authorization: Bearer {token}`).

### GET `/api/products`

Lista produtos com suporte a paginação e filtros.

**Query Parameters:**

| Parâmetro | Tipo | Obrigatório | Regras | Descrição |
|---|---|---|---|---|
| `page` | integer | Não | Mín: 1 | Página atual |
| `per_page` | integer | Não | 1–100 | Itens por página |
| `name` | string | Não | — | Filtro por nome (busca parcial, case-insensitive) |
| `category_id` | integer | Não | Deve existir em `categories` | Filtro por categoria |
| `price_min` | numeric | Não | Mín: 0 | Preço mínimo |
| `price_max` | numeric | Não | >= `price_min` (quando informado) | Preço máximo |
| `in_stock` | boolean | Não | — | Filtrar produtos em estoque (`stock_quantity > 0`) |
| `is_active` | boolean | Não | — | Filtrar por estado ativo/inativo |

**Exemplo de Request:**

```
GET /api/products?page=1&per_page=10&name=teclado&price_min=50&price_max=500
```

**Exemplo de Response (200):**

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Teclado Mecânico",
      "description": "Teclado mecânico RGB com switches blue",
      "price": "299.90",
      "stock_quantity": 15,
      "is_active": true,
      "category": {
        "id": 1,
        "name": "Periféricos",
        "created_at": "2026-04-02T10:00:00.000000Z",
        "updated_at": "2026-04-02T10:00:00.000000Z"
      },
      "user": {
        "id": 1,
        "name": "João Silva"
      },
      "created_at": "2026-04-02T10:00:00.000000Z",
      "updated_at": "2026-04-02T10:00:00.000000Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 10,
    "total": 1
  },
  "message": "Produtos listados com sucesso."
}
```

---

### POST `/api/products`

Cria um novo produto. O `user_id` é automaticamente atribuído ao utilizador autenticado.

**Body:**

| Campo | Tipo | Obrigatório | Regras |
|---|---|---|---|
| `name` | string | Sim | Máx: 255 caracteres |
| `description` | string | Não | — |
| `price` | numeric | Sim | Mín: 0 |
| `category_id` | integer | Sim | Deve existir em `categories` |
| `stock_quantity` | integer | Sim | Mín: 0 |
| `is_active` | boolean | Não | Padrão: `true` |

**Exemplo de Request:**

```json
{
  "name": "Mouse Gamer",
  "description": "Mouse gamer com sensor óptico de alta precisão",
  "price": 189.90,
  "category_id": 1,
  "stock_quantity": 30,
  "is_active": true
}
```

**Exemplo de Response (201):**

```json
{
  "success": true,
  "data": {
    "id": 2,
    "name": "Mouse Gamer",
    "description": "Mouse gamer com sensor óptico de alta precisão",
    "price": "189.90",
    "stock_quantity": 30,
    "is_active": true,
    "category": {
      "id": 1,
      "name": "Periféricos",
      "created_at": "2026-04-02T10:00:00.000000Z",
      "updated_at": "2026-04-02T10:00:00.000000Z"
    },
    "user": {
      "id": 1,
      "name": "João Silva"
    },
    "created_at": "2026-04-02T10:05:00.000000Z",
    "updated_at": "2026-04-02T10:05:00.000000Z"
  },
  "message": "Produto criado com sucesso."
}
```

---

### GET `/api/products/{id}`

Retorna os detalhes de um produto específico.

**Path Parameters:**

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `id` | integer | ID do produto |

**Exemplo de Response (200):**

```json
{
  "success": true,
  "data": {
    "id": 2,
    "name": "Mouse Gamer",
    "description": "Mouse gamer com sensor óptico de alta precisão",
    "price": "189.90",
    "stock_quantity": 30,
    "is_active": true,
    "category": {
      "id": 1,
      "name": "Periféricos",
      "created_at": "2026-04-02T10:00:00.000000Z",
      "updated_at": "2026-04-02T10:00:00.000000Z"
    },
    "user": {
      "id": 1,
      "name": "João Silva"
    },
    "created_at": "2026-04-02T10:05:00.000000Z",
    "updated_at": "2026-04-02T10:05:00.000000Z"
  },
  "message": "Produto encontrado com sucesso."
}
```

**Exemplo de Response de Erro (404):**

```json
{
  "success": false,
  "message": "Produto não encontrado."
}
```

---

### PUT/PATCH `/api/products/{id}`

Atualiza um produto existente. Todos os campos são opcionais (atualização parcial).

**Path Parameters:**

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `id` | integer | ID do produto |

**Body:**

| Campo | Tipo | Obrigatório | Regras |
|---|---|---|---|
| `name` | string | Não | Máx: 255 caracteres |
| `description` | string | Não | — |
| `price` | numeric | Não | Mín: 0 |
| `category_id` | integer | Não | Deve existir em `categories` |
| `stock_quantity` | integer | Não | Mín: 0 |
| `is_active` | boolean | Não | — |

**Exemplo de Request:**

```json
{
  "price": 159.90,
  "stock_quantity": 25
}
```

**Exemplo de Response (200):**

```json
{
  "success": true,
  "data": {
    "id": 2,
    "name": "Mouse Gamer",
    "description": "Mouse gamer com sensor óptico de alta precisão",
    "price": "159.90",
    "stock_quantity": 25,
    "is_active": true,
    "category": {
      "id": 1,
      "name": "Periféricos",
      "created_at": "2026-04-02T10:00:00.000000Z",
      "updated_at": "2026-04-02T10:00:00.000000Z"
    },
    "user": {
      "id": 1,
      "name": "João Silva"
    },
    "created_at": "2026-04-02T10:05:00.000000Z",
    "updated_at": "2026-04-02T10:10:00.000000Z"
  },
  "message": "Produto atualizado com sucesso."
}
```

---

### DELETE `/api/products/{id}`

Remove um produto (soft delete).

**Path Parameters:**

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `id` | integer | ID do produto |

**Exemplo de Response (200):**

```json
{
  "success": true,
  "message": "Produto removido com sucesso."
}
```

**Exemplo de Response de Erro (404):**

```json
{
  "success": false,
  "message": "Produto não encontrado."
}
```

---

## Endpoints de Categorias

> Todos os endpoints de categorias requerem autenticação (`Authorization: Bearer {token}`).

### GET `/api/categories`

Lista categorias com suporte a paginação e filtro por nome.

**Query Parameters:**

| Parâmetro | Tipo | Obrigatório | Regras | Descrição |
|---|---|---|---|---|
| `page` | integer | Não | Mín: 1 | Página atual |
| `per_page` | integer | Não | 1–100 | Itens por página |
| `name` | string | Não | — | Filtro por nome (busca parcial, case-insensitive) |

**Exemplo de Request:**

```
GET /api/categories?page=1&per_page=10&name=peri
```

**Exemplo de Response (200):**

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Periféricos",
      "created_at": "2026-04-02T10:00:00.000000Z",
      "updated_at": "2026-04-02T10:00:00.000000Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 10,
    "total": 1
  },
  "message": "Categorias listadas com sucesso."
}
```

---

### POST `/api/categories`

Cria uma nova categoria.

**Body:**

| Campo | Tipo | Obrigatório | Regras |
|---|---|---|---|
| `name` | string | Sim | Máx: 255 caracteres, único na tabela `categories` |

**Exemplo de Request:**

```json
{
  "name": "Periféricos"
}
```

**Exemplo de Response (201):**

```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Periféricos",
    "created_at": "2026-04-02T10:00:00.000000Z",
    "updated_at": "2026-04-02T10:00:00.000000Z"
  },
  "message": "Categoria criada com sucesso."
}
```

---

### GET `/api/categories/{id}`

Retorna os detalhes de uma categoria específica.

**Path Parameters:**

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `id` | integer | ID da categoria |

**Exemplo de Response (200):**

```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Periféricos",
    "created_at": "2026-04-02T10:00:00.000000Z",
    "updated_at": "2026-04-02T10:00:00.000000Z"
  },
  "message": "Categoria encontrada com sucesso."
}
```

**Exemplo de Response de Erro (404):**

```json
{
  "success": false,
  "message": "Categoria não encontrada."
}
```

---

### PUT/PATCH `/api/categories/{id}`

Atualiza uma categoria existente.

**Path Parameters:**

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `id` | integer | ID da categoria |

**Body:**

| Campo | Tipo | Obrigatório | Regras |
|---|---|---|---|
| `name` | string | Sim | Máx: 255 caracteres, único (exceto o registo atual) |

**Exemplo de Request:**

```json
{
  "name": "Teclados e Mouses"
}
```

**Exemplo de Response (200):**

```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Teclados e Mouses",
    "created_at": "2026-04-02T10:00:00.000000Z",
    "updated_at": "2026-04-02T10:15:00.000000Z"
  },
  "message": "Categoria atualizada com sucesso."
}
```

---

### DELETE `/api/categories/{id}`

Remove uma categoria (soft delete).

**Path Parameters:**

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `id` | integer | ID da categoria |

**Exemplo de Response (200):**

```json
{
  "success": true,
  "message": "Categoria removida com sucesso."
}
```

**Exemplo de Response de Erro (404):**

```json
{
  "success": false,
  "message": "Categoria não encontrada."
}
```

---

## Códigos de Status HTTP

| Código | Significado | Quando ocorre |
|---|---|---|
| `200` | OK | Requisição bem-sucedida |
| `201` | Created | Recurso criado com sucesso (register, store) |
| `401` | Unauthorized | Token ausente, inválido ou expirado / Credenciais inválidas |
| `404` | Not Found | Recurso não encontrado |
| `422` | Unprocessable Entity | Erro de validação nos dados enviados |
| `500` | Internal Server Error | Erro interno do servidor |

---

## Modelos de Dados

### User

| Campo | Tipo | Descrição |
|---|---|---|
| `id` | integer | Identificador único |
| `name` | string | Nome do utilizador |
| `email` | string | Email (único) |
| `password` | string | Senha (hash, nunca retornada na API) |
| `is_active` | boolean | Estado do utilizador |
| `created_at` | datetime | Data de criação |
| `updated_at` | datetime | Data da última atualização |
| `deleted_at` | datetime | Data de remoção (soft delete) |

### Product

| Campo | Tipo | Descrição |
|---|---|---|
| `id` | integer | Identificador único |
| `name` | string | Nome do produto |
| `description` | string (nullable) | Descrição do produto |
| `price` | decimal(10,2) | Preço do produto |
| `stock_quantity` | integer | Quantidade em estoque |
| `is_active` | boolean | Estado do produto |
| `user_id` | integer (FK) | ID do utilizador que criou o produto |
| `category_id` | integer (FK) | ID da categoria do produto |
| `created_at` | datetime | Data de criação |
| `updated_at` | datetime | Data da última atualização |
| `deleted_at` | datetime | Data de remoção (soft delete) |

### Category

| Campo | Tipo | Descrição |
|---|---|---|
| `id` | integer | Identificador único |
| `name` | string | Nome da categoria (único) |
| `created_at` | datetime | Data de criação |
| `updated_at` | datetime | Data da última atualização |
| `deleted_at` | datetime | Data de remoção (soft delete) |

### AuditLog

| Campo | Tipo | Descrição |
|---|---|---|
| `id` | integer | Identificador único |
| `user_id` | integer (FK) | ID do utilizador que realizou a ação |
| `action` | string | Ação realizada (created, updated, deleted) |
| `model_type` | string | Tipo do modelo afetado |
| `model_id` | integer | ID do modelo afetado |
| `old_values` | json (nullable) | Valores anteriores à alteração |
| `new_values` | json (nullable) | Novos valores após a alteração |
| `ip_address` | string (nullable) | IP do utilizador |
| `user_agent` | string (nullable) | User agent do navegador/cliente |
| `created_at` | datetime | Data do registo |
| `updated_at` | datetime | Data da última atualização |

---

## Resumo de Endpoints

| Método | Endpoint | Descrição | Auth |
|---|---|---|---|
| POST | `/api/auth/register` | Registar utilizador | Não |
| POST | `/api/auth/login` | Autenticar utilizador | Não |
| POST | `/api/auth/refresh` | Renovar tokens | Não |
| GET | `/api/auth/me` | Dados do utilizador autenticado | Sim |
| POST | `/api/auth/logout` | Logout (dispositivo atual) | Sim |
| POST | `/api/auth/logout-all` | Logout (todos os dispositivos) | Sim |
| GET | `/api/products` | Listar produtos | Sim |
| POST | `/api/products` | Criar produto | Sim |
| GET | `/api/products/{id}` | Detalhe de um produto | Sim |
| PUT/PATCH | `/api/products/{id}` | Atualizar produto | Sim |
| DELETE | `/api/products/{id}` | Remover produto | Sim |
| GET | `/api/categories` | Listar categorias | Sim |
| POST | `/api/categories` | Criar categoria | Sim |
| GET | `/api/categories/{id}` | Detalhe de uma categoria | Sim |
| PUT/PATCH | `/api/categories/{id}` | Atualizar categoria | Sim |
| DELETE | `/api/categories/{id}` | Remover categoria | Sim |
