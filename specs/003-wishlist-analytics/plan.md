# Implementation Plan: Wishlist e Analytics

**Branch**: `003-wishlist-analytics` | **Date**: 2026-09-11 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/003-wishlist-analytics/spec.md`

## Summary

Adicionar e manter uma wishlist lógica por varejista, snapshotando nome do produto e estado da empresa no momento da inclusão, e disponibilizar consulta agregada e anônima de demanda para produtores e varejistas. A solução usa `GetProductAction`, Eloquent direto, quatro Actions tipadas, Resources e agregação SQL; não cria `Product` nem tabela `wishlists`.

## Technical Context

**Language/Version**: PHP 8.3

**Primary Dependencies**: Laravel 13, Eloquent, Sanctum, PHPUnit 12, Docker Compose

**Storage**: PostgreSQL existente; migration com foreign key, índice único por varejista/produto e índices para filtro/agregação

**Testing**: PHPUnit via `docker compose exec php php artisan test`; Feature tests com rota real e Unit tests para cálculo/consulta isolada quando agregarem sinal

**Target Platform**: API HTTP Laravel executada no container `php`

**Project Type**: Web service/API modular por domínio

**Performance Goals**: 95% das listagens em até 2 segundos sob carga normal; analytics limitada e agregada no banco, sem carregar todos os itens

**Constraints**: isolamento por retailer; snapshots imutáveis; `page/per_page` 1/15 e máximo 100; `limit` 10 e máximo 50; percentual inteiro arredondado ao inteiro mais próximo; sem Pint

**Scale/Scope**: uma wishlist lógica por retailer; quatro endpoints; um item por combinação retailer + `source_product_id`; analytics sem identidade

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

- **A.1/A.3/A.4/A.5/A.7**: PASS — conecta demanda e oferta, usa respostas enxutas, protege escopo/identidade, explicita cálculo e mantém escopo mínimo.
- **B.I/B.II/B.III**: PASS — módulo `domain/Models/Wishlist`, Actions com único `execute()`, DTOs readonly e retornos `WishlistItem`, `LengthAwarePaginator` ou DTO de resultado.
- **B.IV**: PASS — FormRequests validam entrada HTTP; Actions validam endereço, ownership, unicidade e invariantes.
- **B.V/B.VI/B.VII**: PASS — Eloquent direto, nenhuma gravação multi-tabela prevista, `CultivaException` com status explícito e ProductSource isolado em `Integrations`.
- **B.XI–B.XV**: PASS — Red antes de produção, Feature com rota real, Unit somente onde necessário, testes no container `php`, sem Laravel Pint.

## Project Structure

### Documentation (this feature)

```text
specs/003-wishlist-analytics/
├── plan.md
├── research.md
├── data-model.md
├── quickstart.md
├── contracts/openapi.yaml
└── tasks.md                         # criado por $speckit-tasks
```

### Source Code (repository root)

```text
domain/Models/Wishlist/
├── WishlistItem.php
├── Actions/
│   ├── AddWishlistItemAction.php
│   ├── ListWishlistItemsAction.php
│   ├── RemoveWishlistItemAction.php
│   └── GetWishlistAnalyticsAction.php
├── DTO/
│   ├── AddWishlistItemDTO.php
│   ├── ListWishlistItemsDTO.php
│   └── GetWishlistAnalyticsDTO.php
└── Http/
    ├── Controllers/WishlistController.php
    ├── Requests/
    │   ├── AddWishlistItemRequest.php
    │   ├── ListWishlistItemsRequest.php
    │   └── GetWishlistAnalyticsRequest.php
    └── Resources/
        ├── WishlistItemResource.php
        └── WishlistAnalyticsResource.php

database/migrations/*_create_wishlist_items_table.php
database/factories/WishlistItemFactory.php
routes/api.php

tests/Feature/domain/Models/Wishlist/Http/Controllers/WishlistController/
├── StoreTest.php
├── IndexTest.php
├── DestroyTest.php
└── AnalyticsTest.php

tests/Unit/domain/Models/Wishlist/
└── Actions/GetWishlistAnalyticsActionTest.php
```

**Structure Decision**: Seguir o módulo por entidade existente. `WishlistItem` é a única entidade persistida; a wishlist é a relação dos itens do retailer. O controller pode concentrar os quatro endpoints por pertencerem ao mesmo recurso, enquanto cada regra fica em uma Action separada. A analytics retorna DTO readonly tipado, não array de domínio.

## Mandatory Implementation Order

1. Criar o contrato OpenAPI e documentar as quatro rotas nas coleções Bruno e Postman, além da estrutura mínima de testes/fixtures necessária.
2. Criar Feature tests com rotas reais, factories e `Http::fake()` apenas para ProductSource; executar cada grupo no container `php` e confirmar Red pelo comportamento ausente.
3. Somente após Red, criar migration, model, factory, DTOs, FormRequests, Actions, Resources, controller, relacionamentos e rotas.
4. Executar os testes direcionados para Green e depois a suíte relevante e a suíte completa no container `php`.
5. Não executar Laravel Pint. Corrigir apenas divergências necessárias ao contrato e à especificação.

O TDD deve ser comprovado separando os comandos Red e Green; nenhum código de produção será alterado antes do Red válido.

## Design Decisions

- `source_product_id` persistido será string; o campo público continua `product_id`.
- A inclusão consulta `Retailer -> Company -> address` e grava somente o `state` snapshotado.
- A remoção consulta o item já escopado por `retailer_id`; não aceita ownership vindo da URL ou payload.
- A analytics calcula `total_items` com contagem filtrada e resultados agrupados/ordenados no banco; a apresentação calcula apenas posição e percentual dos grupos já limitados.
- Para preservar o contrato atual de `GetProductAction`, a wishlist traduz especificamente produto não encontrado para HTTP 404, sem alterar Offers.
- Nenhum repository, interface, tabela `wishlists`, model Product ou serviço genérico será criado.

## Complexity Tracking

Nenhuma violação constitucional prevista. Não criar abstrações, tabela agregadora ou mecanismo de identidade na analytics.
