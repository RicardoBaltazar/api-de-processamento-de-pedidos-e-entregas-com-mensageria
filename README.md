# API de Processamento de Pedidos e Entregas com Mensageria

> Projeto de hobby, desenvolvido para estudos e experimentação.

API-first para gerenciamento do ciclo de vida de pedidos de um comércio eletrônico. O sistema permite registrar pedidos, acompanhar pagamentos, controlar o status e coordenar entregas.

O projeto não possui frontend obrigatório. A API é o principal produto e pode ser consumida por aplicações web, mobile ou outros sistemas.

## Sumário

- [Sobre o projeto](#sobre-o-projeto)
- [Funcionalidades](#funcionalidades)
- [Stack](#stack)
- [Requisitos](#requisitos)
- [Instalação](#instalação)
- [Acessos](#acessos)
- [Administrador inicial](#administrador-inicial)
- [Testes](#testes)
- [Comandos úteis](#comandos-úteis)
- [Roadmap](#roadmap)
- [Licença](#licença)

## Sobre o projeto

Uma empresa de comércio eletrônico possui uma plataforma responsável por receber pedidos, acompanhar pagamentos e coordenar entregas.

Com o aumento do número de pedidos, a empresa começou a enfrentar problemas de processamento duplicado, atrasos na atualização dos status e dificuldades para identificar falhas durante a comunicação entre os componentes do sistema.

O objetivo é construir uma nova API capaz de gerenciar o ciclo de vida dos pedidos com segurança, impedir transições inválidas de status e manter um histórico confiável das operações.

Como evolução do projeto, estão previstos o processamento assíncrono de eventos com mensageria e recursos de observabilidade para identificar falhas, atrasos e gargalos. Essas funcionalidades ainda não fazem parte desta versão.

## Funcionalidades

### Autenticação

- Cadastro público de clientes.
- Login e logout com tokens pessoais do Laravel Sanctum.
- Consulta do usuário autenticado.
- Papéis `cliente` e `admin`.
- Cadastro público sempre cria usuários com papel `cliente`.
- Administrador inicial criado por seeder.

### Produtos

- Cadastro, listagem, detalhamento, atualização e exclusão de produtos.
- Criação, atualização e exclusão restritas a administradores.
- Produtos podem ser ativados ou desativados.
- Produtos inativos não podem ser adicionados a novos pedidos.

### Pedidos

- Criação de pedidos com cliente, itens, quantidades, valores e endereço de entrega.
- Clientes criam e visualizam apenas os próprios pedidos.
- Administradores criam pedidos para qualquer cliente e visualizam todos os pedidos.
- Total calculado pelo servidor a partir dos itens.
- Nome e preço do produto são preservados no item no momento da compra.
- Listagem paginada e detalhamento de pedidos.

### Status do pedido

O fluxo normal segue a sequência:

```text
CRIADO → AGUARDANDO_PAGAMENTO → PAGO → EM_PREPARACAO → ENVIADO → ENTREGUE
```

- Apenas administradores avançam o status.
- O status só pode avançar para o próximo da sequência, sem pular etapas ou retroceder.
- `ENTREGUE` e `CANCELADO` são estados finais.
- Toda alteração de status é registrada no histórico do pedido.

### Cancelamento

- O motivo é obrigatório em todos os cancelamentos.
- Pedidos `CRIADO` ou `AGUARDANDO_PAGAMENTO` podem ser cancelados pelo cliente dono ou por um administrador.
- Pedidos `PAGO`, `EM_PREPARACAO` ou `ENVIADO` só podem ser cancelados por administradores.
- Pedidos `ENTREGUE` ou `CANCELADO` não podem ser cancelados.

### Concorrência

- Alterações de status e cancelamentos usam transação de banco com bloqueio da linha do pedido.
- Transições inválidas e conflitos de concorrência retornam `409 Conflict`.
- O status é relido dentro da transação antes de qualquer alteração.

### API e documentação

- Rotas em português no prefixo `/api`.
- Validação com Form Requests.
- Autorização com Policies.
- Respostas de recursos com API Resources.
- Documentação OpenAPI com Swagger UI.
- Testes automatizados com PHPUnit.

## Stack

| Camada | Tecnologia |
| --- | --- |
| Backend | Laravel 13 / PHP 8.3+ |
| Banco de dados | PostgreSQL 18 |
| Cache, sessão e filas | Banco de dados (padrão atual); Redis disponível no ambiente Docker |
| Autenticação | Laravel Sanctum |
| Documentação | L5-Swagger |
| Ambiente | Docker + Laravel Sail |
| Formatação | Laravel Pint |
| Testes | PHPUnit |

## Requisitos

- Git.
- Docker.
- Docker Compose v2 ou superior.

O PHP e o Composer não precisam estar instalados no host. Caso não tenha Composer localmente, as dependências podem ser instaladas via Docker.

## Instalação

Clone o repositório:

```bash
git clone <url-do-repositorio>
cd api-de-processamento-de-pedidos-e-entregas-com-mensageria
```

Crie o arquivo de ambiente:

```bash
cp .env.example .env
```

Instale as dependências do Composer via Docker:

```bash
docker run --rm \
  -u "$(id -u):$(id -g)" \
  -e COMPOSER_HOME=/tmp/composer \
  -v "$(pwd):/app" \
  -w /app \
  composer:2 install --no-interaction --prefer-dist
```

Configure o banco PostgreSQL e a porta da aplicação no `.env`:

```dotenv
APP_PORT=8000

DB_CONNECTION=pgsql
DB_HOST=pgsql
DB_PORT=5432
DB_DATABASE=api-de-processamento-de-pedidos-e-entregas-com-mensageria-db
DB_USERNAME=sail
DB_PASSWORD=password
```

Suba os containers:

```bash
./vendor/bin/sail up -d
```

Gere a chave da aplicação:

```bash
./vendor/bin/sail artisan key:generate
```

Execute as migrations:

```bash
./vendor/bin/sail artisan migrate
```

Configure o administrador inicial no `.env`:

```dotenv
ADMIN_NAME="Administrador"
ADMIN_EMAIL=admin@exemplo.com
ADMIN_PASSWORD=uma-senha-segura
```

Crie o administrador:

```bash
./vendor/bin/sail artisan db:seed
```

## Acessos

Com `APP_PORT=8000`:

- API: `http://localhost:8000/api`
- Swagger UI: `http://localhost:8000/api/documentation`
- JSON OpenAPI: `http://localhost:8000/docs`

## Administrador inicial

O administrador é criado ou atualizado pelo seeder a partir das variáveis `ADMIN_NAME`, `ADMIN_EMAIL` e `ADMIN_PASSWORD`.

Se essas variáveis não forem definidas, o seeder utiliza valores padrão locais (`admin@example.com` e senha `password`). Execute o seeder novamente com novas variáveis para atualizar o nome e a senha do administrador.

Mantenha o arquivo `.env` fora do versionamento e altere as credenciais padrão em qualquer ambiente compartilhado.

## Testes

Execute a suíte de testes:

```bash
./vendor/bin/sail artisan test
```

Os testes cobrem autenticação, produtos, criação e consulta de pedidos, transições de status, cancelamento, histórico e conflitos de concorrência.

## Comandos úteis

```bash
./vendor/bin/sail up -d
./vendor/bin/sail down
./vendor/bin/sail artisan route:list --path=api
./vendor/bin/sail artisan l5-swagger:generate
./vendor/bin/sail bin pint
./vendor/bin/sail artisan test
```

## Roadmap

- Processamento assíncrono de eventos com mensageria.
- Idempotência no processamento de pedidos e pagamentos.
- Observabilidade com logs estruturados, métricas e tracing.
- Integração com gateway de pagamento em sandbox para confirmação de pagamentos.

## Licença

Este projeto está licenciado sob a licença MIT.
