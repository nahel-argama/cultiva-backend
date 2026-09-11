# Implementation Plan: Compras de Ofertas

**Branch**: `002-offer-purchases` | **Date**: 2026-09-10 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/002-offer-purchases/spec.md`

## Summary

Adicionar o fluxo de compra de uma quantidade de uma Offer por um retailer autenticado, persistindo cada operação como `Purchase`, atualizando atomicamente o estoque comprometido e inativando a Offer ao esgotar. A solução reutiliza o contador `reserved_quantity` e o formato de paginação já existente, com proteção contra concorrência na mesma transação.

O trabalho será executado nesta ordem obrigatória: contrato OpenAPI e requisições Bruno; testes Feature em Red; migration/modelos/actions/controllers e demais código de produção; testes Green e suíte de regressão. Nenhum código de produção da compra será implementado antes de existir o teste correspondente em Red.

## Technical Context

**Language/Version**: PHP 8.3

**Primary Dependencies**: Laravel 13, Eloquent, Sanctum, PHPUnit 12, Docker Compose

**Storage**: PostgreSQL existente do backend; migrations com constraints CHECK e foreign keys

**Testing**: PHPUnit via `docker compose exec php php artisan test`; Feature tests com rota real, banco real e `RefreshDatabase`/`DatabaseTransactions` conforme o padrão existente

**Target Platform**: API HTTP Laravel executada no container `php`

**Project Type**: Web service/API modular por domínio

**Performance Goals**: Consultas de compras e vendas em até 2 segundos em pelo menos 95% das requisições sob carga operacional normal; confirmação concorrente deve serializar somente a Offer disputada

**Constraints**: Nenhum overselling; nenhuma compra parcial persistida; paginação obrigatória com `page` default 1, `per_page` default 15 e máximo 100; nunca confiar em valores comerciais enviados pelo cliente

**Scale/Scope**: Uma Purchase corresponde a uma única Offer; inclui compra, histórico, minhas compras e minhas vendas; não inclui pagamento, entrega, cancelamento ou estados de pós-compra

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

- **A.1/A.6**: PASS — conecta produtor e varejista, preserva o histórico comercial e não implementa logística física.
- **A.3**: PASS — payload mínimo (`quantity`) e respostas paginadas enxutas.
- **A.4**: PASS — isolamento por perfil/proprietário, snapshots comerciais, transação e proteção contra concorrência.
- **A.7**: PASS — escopo limitado a registro de compra e consultas necessárias; critérios cobrem autorização, falhas e consistência.
- **B.I/B.II/B.III**: PASS — novo módulo `Purchase` por domínio, Actions com `execute()`, DTOs e retornos tipados.
- **B.IV**: PASS — FormRequests validam formato; Actions validam estado, estoque, ownership e invariantes.
- **B.V**: PASS — gravação de Purchase e Offer ocorre em uma transação; Eloquent direto é suficiente.
- **B.VI**: PASS — falhas esperadas usam exceção-base/semântica existente sem expor detalhes internos.
- **B.IX**: PASS — nenhuma interface, repository, service genérico ou abstração especulativa.
- **TDD do projeto**: PASS — Bruno e testes Red serão criados/executados antes do código de produção.

## Project Structure

### Documentation (this feature)

```text
specs/002-offer-purchases/
├── plan.md
├── research.md
├── data-model.md
├── quickstart.md
├── contracts/
│   └── openapi.yaml
└── tasks.md                         # criado por $speckit-tasks
```

### Source Code (repository root)

```text
domain/Models/Purchase/
├── Purchase.php
├── Actions/
│   ├── CreatePurchaseAction.php
│   ├── ListRetailerPurchasesAction.php
│   └── ListProducerSalesAction.php
├── DTO/
│   └── CreatePurchaseDTO.php
├── Http/
│   ├── Controllers/PurchaseController.php
│   └── Requests/
│       ├── StorePurchaseRequest.php
│       └── ListPurchasesRequest.php
└── Resources/PurchaseResource.php

database/migrations/*_create_purchases_table.php
database/factories/PurchaseFactory.php
routes/api.php

tests/Feature/domain/Models/Purchase/Http/Controllers/PurchaseController/
├── StoreTest.php
├── PurchaseIndexTest.php
└── SaleIndexTest.php

collection/bruno/API/
├── Offers/Show Available Offer (Retailer).yml
├── Purchases/folder.yml
├── Purchases/Buy Offer.yml
├── Purchases/List My Purchases.yml
└── Purchases/List My Sales.yml
```

**Structure Decision**: Seguir o módulo por entidade já usado pelo backend. `Purchase` é a entidade canônica da transação; a consulta do producer é chamada de vendas, mas reutiliza o mesmo registro. Bruno documenta os três endpoints antes da implementação.

## Mandatory Implementation Order

1. Atualizar/confirmar contrato da API em `specs/002-offer-purchases/contracts/openapi.yaml`.
2. Criar as três requisições Bruno com payload, variáveis, autenticação e endpoints definidos no contrato. Essa documentação deve existir antes dos testes e do código de produção.
3. Criar os Feature tests mínimos, usando rotas reais e banco real, e executar cada grupo no container `php` para confirmar Red pelo motivo esperado.
4. Somente após Red confirmado, implementar migration, model, factory, DTO, requests, Actions, transformer, controller e rotas.
5. Executar os mesmos testes para Green, depois os testes existentes de Offers e a suíte completa.
6. Se qualquer teste ou contrato revelar divergência, corrigir a implementação mantendo contrato e especificação como fonte de comportamento; não pular a etapa Red.

## Complexity Tracking

Nenhuma violação constitucional prevista. Não criar repository, interface, service genérico, status de Purchase, item de pedido ou mecanismo de pagamento nesta feature.
