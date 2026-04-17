# App API (Woo + Real3D)

Plugin WordPress desenvolvido para resolver uma necessidade real de negócio: disponibilizar, de forma segura, uma API para um app/PWA de assinaturas digitais, centralizando autenticação, clientes, pedidos, assinaturas e acesso às revistas.

## Contexto

Este projeto foi criado para atender um cenário real da empresa, onde era necessário integrar o ecossistema WordPress/WooCommerce a uma aplicação externa de leitura e gestão de assinaturas digitais.

A API foi estruturada para permitir que o app consumisse dados de clientes, pedidos e assinaturas, além de controlar o acesso a revistas digitais e fluxos de checkout.

## Visão geral

O plugin atende principalmente aos seguintes fluxos:

- autenticação JWT com access token e refresh token
- leitura de dados do cliente autenticado
- consulta de pedidos e assinaturas
- acesso protegido a revistas e PDFs
- integração com Real3D Flipbook
- checkout dentro do app
- endpoints administrativos para suporte operacional
- recursos opcionais de IA para recomendações por página

## Stack e dependências

- WordPress
- WooCommerce
- PHP 7.4+
- Real3D Flipbook (quando o fluxo de revistas usa flipbooks)
- OpenSSL para licenças offline

## Estrutura

```text
app-api.php
includes/
  admin.php
  ai.php
  cors.php
  db.php
  helpers.php
  jwt.php
  magic-login.php
  orders.php
  product-flipbooks.php
  real3d.php
  routes.php
  woocommerce.php
```

## Como funciona

### Autenticação

O plugin usa JWT para emitir:

- **access token** de curta duração para chamadas autenticadas
- **refresh token** para renovação de sessão
- **viewer token** para acesso controlado ao conteúdo protegido

As rotas autenticadas validam o usuário e, quando necessário, o status de assinatura ou acesso ao produto.

### Conteúdo protegido

O acesso às revistas e PDFs depende da relação entre usuário, pedido concluído e produto liberado. O fluxo também suporta geração de viewer token para consumo em WebView/app.

### Checkout

O checkout utiliza dados do WooCommerce para expor campos, gateways e criação de pedidos dentro do app.

## Endpoints principais

Namespace base: `app/v1`

### Públicos

- `POST /auth/login`
- `POST /auth/refresh`
- `POST /auth/logout`
- `POST /auth/forgot-password`
- `POST /auth/reset-password/validate`
- `POST /auth/reset-password`
- `GET /store/products`
- `GET /store/products/{product_id}`
- `GET /store/checkout/context`
- `GET /store/checkout/customer-lookup`
- `GET /store/checkout/postcode-lookup`
- `GET /store/checkout/installments`
- `POST /store/checkout/submit`
- `GET /offline/public-key`
- `GET /viewer/flipbook/{product_id}` com token viewer
- `GET /magazines/{product_id}/pdf` com token viewer

### Autenticados

- `GET /me`
- `POST /me/profile`
- `POST /me/password`
- `POST /me/avatar`
- `GET /me/orders`
- `GET /me/subscriptions`
- `GET /me/magazines`
- `GET /me/magazines/categories`
- `GET /magazines/{product_id}/access`
- `POST /magazines/{product_id}/offline-license`
- `POST /offline/licenses/renew`

### Administrativos

- `GET /admin/subscribers`
- `GET /admin/users/{user_id}/magazines`
- `GET /admin/users/{user_id}/orders`
- `GET /admin/orders/{order_id}`

## Instalação local

1. Copie a pasta do plugin para `wp-content/plugins/`.
2. Ative o plugin no painel do WordPress.
3. Garanta que WooCommerce esteja ativo.
4. Configure as opções em **Configurações > App API**.
5. Se usar revistas/flipbooks, ajuste a meta key e o shortcode do Real3D.
6. Se usar IA, configure a chave e os parâmetros da seção correspondente.

## Configurações

No painel administrativo, o plugin expõe opções para:

- meta key do flipbook no produto
- template de shortcode do Real3D
- ativação da IA
- chave da API e modelo
- limite de produtos recomendados
- TTL de cache
- URL base do catálogo

## Segurança

Alguns cuidados implementados no plugin:

- cookies `HttpOnly` para autenticação quando aplicável
- validação de access token, refresh token e viewer token
- invalidação de sessão após troca ou reset de senha
- proteção de conteúdo por vínculo entre usuário e produto
- rotas administrativas com checagem de capacidade

## Observações

Este repositório foi organizado para avaliação técnica e estudo de arquitetura. A execução completa depende de um ambiente WordPress/WooCommerce configurado com os plugins e dados adequados.
