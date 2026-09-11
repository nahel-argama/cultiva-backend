# Tasks: Wishlist e Analytics

**Input**: Design documents from `/specs/003-wishlist-analytics/`

**Prerequisites**: `plan.md`, `spec.md`, `research.md`, `data-model.md`, `contracts/openapi.yaml`

**Organization**: Tasks are grouped by user story. TDD is mandatory: each story's tests and Red command precede its production changes.

## Phase 1: Setup (Shared Documentation)

- [ ] T001 [P] Atualizar o contrato das quatro rotas da wishlist em `specs/003-wishlist-analytics/contracts/openapi.yaml`
- [ ] T002 [P] Criar as requisições Bruno para adicionar, listar, remover e consultar analytics em `collection/bruno/API/Wishlist/`
- [ ] T003 [P] Adicionar as quatro requisições da wishlist à coleção Postman em `collection/postman/Cultiva.postman_collection.json`
- [ ] T004 [P] Adicionar/ajustar variáveis `wishlist_item_id`, `product_id`, `state`, `limit`, `page` e `per_page` no ambiente Postman em `collection/postman/Cultiva.local.postman_environment.json`

## Phase 2: Foundational (Blocking Prerequisites)

- [ ] T005 Confirmar no plano de implementação os paths reais, middleware `auth:sanctum`, ability `access`, perfis permitidos e execução oficial via container `php` em `specs/003-wishlist-analytics/plan.md`
- [ ] T006 Definir no teste e no contrato o percentual inteiro com `round()`, `limit` default 10/máximo 50 e resposta vazia para total zero em `specs/003-wishlist-analytics/data-model.md`

**Checkpoint**: documentação e regras numéricas estão alinhadas; nenhum código de produção foi alterado.

## Phase 3: User Story 1 - Adicionar produto à wishlist (Priority: P1) 🎯 MVP

**Goal**: Permitir que somente retailer autenticado crie um `WishlistItem` com produto oficial, nome e estado snapshotados.

**Independent Test**: `StoreTest` executa a rota real, usa factories e `Http::fake()` para ProductSource e verifica HTTP, banco, autorização, ausência de endereço, produto inexistente e duplicidade.

### Tests for User Story 1 — RED first

- [ ] T007 [US1] Criar os cenários Feature de inclusão, somente `product_id`, campos internos ignorados/rejeitados, retailer autorizado, produtor proibido e resposta 201 em `tests/Feature/domain/Models/Wishlist/Http/Controllers/WishlistController/StoreTest.php`
- [ ] T008 [US1] Adicionar cenários de produto inexistente 404, nome oficial do ProductSource, estado do endereço, ausência de endereço 422 e duplicidade 409 em `tests/Feature/domain/Models/Wishlist/Http/Controllers/WishlistController/StoreTest.php`
- [ ] T009 [US1] Executar o Red da inclusão no container `php` com `docker compose exec php php artisan test --filter=WishlistController\\StoreTest` e registrar a falha por rota/modelo/comportamento ausente em `specs/003-wishlist-analytics/tdd-evidence.md`

### Implementation for User Story 1 — only after T009

- [ ] T010 [US1] Criar a migration `database/migrations/*_create_wishlist_items_table.php` com campos obrigatórios, foreign key, índice unique `retailer_id/source_product_id` e índices de consulta
- [ ] T011 [US1] Criar o model tipado `WishlistItem` com fillable/casts e relacionamento `retailer()` em `domain/Models/Wishlist/WishlistItem.php`
- [ ] T012 [P] [US1] Adicionar `wishlistItems()` em `domain/Models/Retailer/Retailer.php` e PHPDoc correspondente
- [ ] T013 [P] [US1] Criar `WishlistItemFactory` em `database/factories/WishlistItemFactory.php` usando factory de Retailer e valores snapshotados válidos
- [ ] T014 [US1] Criar DTO readonly `AddWishlistItemDTO` com construtor nomeado `from()` em `domain/Models/Wishlist/DTO/AddWishlistItemDTO.php`
- [ ] T015 [US1] Criar `AddWishlistItemRequest` validando apenas `product_id` em `domain/Models/Wishlist/Http/Requests/AddWishlistItemRequest.php`
- [ ] T016 [US1] Implementar `AddWishlistItemAction::execute()` em `domain/Models/Wishlist/Actions/AddWishlistItemAction.php`, resolvendo ProductSource, endereço, snapshot, ownership e conflito com `CultivaException` explícita
- [ ] T017 [US1] Implementar `WishlistItemResource` em `domain/Models/Wishlist/Http/Resources/WishlistItemResource.php` com o contrato de resposta
- [ ] T018 [US1] Criar `WishlistController::store()` em `domain/Models/Wishlist/Http/Controllers/WishlistController.php` coordenando Request, DTO, Action e Resource com status 201
- [ ] T019 [US1] Registrar `POST /v1/wishlist/items` com `auth:sanctum`, `ability:access` e `profile:retailer` em `routes/api.php`
- [ ] T020 [US1] Executar Green direcionado no container `php` com `docker compose exec php php artisan test --filter=WishlistController\\StoreTest` e registrar resultado em `specs/003-wishlist-analytics/tdd-evidence.md`

**Checkpoint**: US1 cria itens com snapshots, autorização, integração e unicidade funcionando.

## Phase 4: User Story 2 - Consultar e remover minha wishlist (Priority: P1)

**Goal**: Permitir listagem isolada, busca, paginação e remoção segura dos itens do retailer autenticado.

**Independent Test**: `IndexTest` e `DestroyTest` exercitam rotas reais com dois retailers, busca, paginação e tentativa de remoção cross-owner.

### Tests for User Story 2 — RED first

- [ ] T021 [P] [US2] Criar Feature tests de isolamento, busca por nome, defaults/limites de paginação e produtor proibido em `tests/Feature/domain/Models/Wishlist/Http/Controllers/WishlistController/IndexTest.php`
- [ ] T022 [P] [US2] Criar Feature tests de remoção própria, 204, item inexistente e item de outro retailer em `tests/Feature/domain/Models/Wishlist/Http/Controllers/WishlistController/DestroyTest.php`
- [ ] T023 [US2] Executar Red de listagem e remoção no container `php` com `docker compose exec php php artisan test --filter=WishlistController\\IndexTest` e `docker compose exec php php artisan test --filter=WishlistController\\DestroyTest`, registrando falhas em `specs/003-wishlist-analytics/tdd-evidence.md`

### Implementation for User Story 2 — only after T023

- [ ] T024 [US2] Criar DTO readonly `ListWishlistItemsDTO` em `domain/Models/Wishlist/DTO/ListWishlistItemsDTO.php` com busca, página e tamanho tipados
- [ ] T025 [US2] Criar `ListWishlistItemsRequest` validando `search`, `page >= 1` e `per_page` entre 1 e 100 em `domain/Models/Wishlist/Http/Requests/ListWishlistItemsRequest.php`
- [ ] T026 [US2] Implementar `ListWishlistItemsAction::execute()` em `domain/Models/Wishlist/Actions/ListWishlistItemsAction.php` com escopo pelo retailer, busca por `product_name`, ordenação determinística e `paginate()` nativo
- [ ] T027 [US2] Implementar `RemoveWishlistItemAction::execute()` em `domain/Models/Wishlist/Actions/RemoveWishlistItemAction.php` usando consulta escopada por retailer e `findOrFail()` equivalente
- [ ] T028 [US2] Adicionar `index()` e `destroy()` ao controller em `domain/Models/Wishlist/Http/Controllers/WishlistController.php`, retornando Resource collection paginada e 204
- [ ] T029 [US2] Registrar `GET /v1/wishlist/items` e `DELETE /v1/wishlist/items/{wishlistItem}` com middleware correto em `routes/api.php`
- [ ] T030 [US2] Executar Green de listagem e remoção no container `php` com `docker compose exec php php artisan test --filter=WishlistController\\IndexTest` e `docker compose exec php php artisan test --filter=WishlistController\\DestroyTest`, registrando resultado em `specs/003-wishlist-analytics/tdd-evidence.md`

**Checkpoint**: US1 e US2 funcionam independentemente; nenhum retailer acessa ou remove itens de outro.

## Phase 5: User Story 3 - Consultar analytics de produtos desejados (Priority: P2)

**Goal**: Expor ranking agregado, filtrável e anônimo para producer e retailer.

**Independent Test**: `AnalyticsTest` e o Unit test verificam autorização, filtro por estado salvo, agregação, top 3 nas primeiras posições com default 10, limite, empate, percentuais e total zero.

### Tests for User Story 3 — RED first

- [ ] T031 [P] [US3] Criar Feature tests de acesso por producer/retailer, filtro `state`, ordenação, empate por `source_product_id`, default `limit=10`, máximo 50 e ausência de identidade em `tests/Feature/domain/Models/Wishlist/Http/Controllers/WishlistController/AnalyticsTest.php`
- [ ] T032 [P] [US3] Criar Unit tests para total filtrado, percentual inteiro arredondado, posição, top 3 e total zero em `tests/Unit/domain/Models/Wishlist/Actions/GetWishlistAnalyticsActionTest.php`
- [ ] T033 [US3] Executar Red da analytics no container `php` com `docker compose exec php php artisan test --filter=WishlistController\\AnalyticsTest` e `docker compose exec php php artisan test tests/Unit/domain/Models/Wishlist/Actions/GetWishlistAnalyticsActionTest.php`, registrando falhas em `specs/003-wishlist-analytics/tdd-evidence.md`

### Implementation for User Story 3 — only after T033

- [ ] T034 [US3] Criar DTOs readonly `GetWishlistAnalyticsDTO` e resultado tipado em `domain/Models/Wishlist/DTO/GetWishlistAnalyticsDTO.php` e `domain/Models/Wishlist/DTO/WishlistAnalyticsResultDTO.php`
- [ ] T035 [US3] Criar `GetWishlistAnalyticsRequest` validando `state` e `limit` entre 1 e 50 em `domain/Models/Wishlist/Http/Requests/GetWishlistAnalyticsRequest.php`
- [ ] T036 [US3] Implementar `GetWishlistAnalyticsAction::execute()` em `domain/Models/Wishlist/Actions/GetWishlistAnalyticsAction.php` com count filtrado, group/order/limit no banco, sem carregar coleção completa, calculando posição e percentual explícitos
- [ ] T037 [US3] Implementar `WishlistAnalyticsResource` em `domain/Models/Wishlist/Http/Resources/WishlistAnalyticsResource.php` sem campos de identidade ou endereço
- [ ] T038 [US3] Adicionar `analytics()` ao controller em `domain/Models/Wishlist/Http/Controllers/WishlistController.php`
- [ ] T039 [US3] Registrar `GET /v1/wishlist/analytics` com `auth:sanctum` e `ability:access`, sem middleware de perfil, em `routes/api.php`
- [ ] T040 [US3] Executar Green da analytics no container `php` com `docker compose exec php php artisan test --filter=WishlistController\\AnalyticsTest` e `docker compose exec php php artisan test tests/Unit/domain/Models/Wishlist/Actions/GetWishlistAnalyticsActionTest.php`, registrando resultado em `specs/003-wishlist-analytics/tdd-evidence.md`

**Checkpoint**: todas as user stories estão funcionais, com ranking anônimo e top 3 nas primeiras posições do default de 10.

## Phase 6: Polish & Cross-Cutting Concerns

- [ ] T041 [P] Atualizar `collection/bruno/API/Wishlist/` e `collection/postman/Cultiva.postman_collection.json` com exemplos de sucesso, 404, 409, 422, 403 e analytics filtrada
- [ ] T042 [P] Adicionar/ajustar traduções de wishlist em `lang/en/wishlist.php` e cobrir strings em `tests/Unit/lang/en/TranslationsTest.php`
- [ ] T043 Executar regressão relevante no container `php` com `docker compose exec php php artisan test tests/Feature/domain/Models/Offer tests/Feature/domain/Models/Purchase tests/Unit/domain/Integrations/ProductSource`
- [ ] T044 Executar a suíte completa no container `php` com `docker compose exec php php artisan test` e registrar o resultado final em `specs/003-wishlist-analytics/tdd-evidence.md`
- [ ] T045 Executar os cenários do [quickstart.md](quickstart.md) no container `php`, conferir `git diff --check` e não executar Laravel Pint

## Dependencies & Execution Order

### Phase Dependencies

- Phase 1 pode começar imediatamente.
- Phase 2 depende da documentação da Phase 1 e bloqueia as user stories.
- US1 depende da Phase 2; US2 depende da conclusão de US1 porque reutiliza model, Resource e controller; US3 depende do model/migration de US1 e pode iniciar após essa base, mas a entrega sequencial reduz conflitos no controller/rotas.
- Polish depende das três stories e de seus Green commands.

### Parallel Opportunities

- T001–T004 são paralelizáveis em arquivos diferentes.
- T007/T008 podem ser preparados no mesmo arquivo apenas por uma pessoa; T021/T022 e T031/T032 são paralelizáveis em arquivos diferentes.
- T012/T013 são paralelizáveis após T010/T011; T024/T025 e T034/T035 são paralelizáveis.
- T041/T042 e a execução de regressão podem ocorrer em paralelo depois dos Green, desde que não editem o mesmo arquivo.

## Implementation Strategy

### MVP First

1. Completar Setup/Foundation.
2. Executar T007–T009 em Red.
3. Implementar T010–T019.
4. Executar T020 em Green e parar para validar a inclusão isoladamente.

### Incremental Delivery

1. US1: adicionar item com snapshot, autorização e duplicidade.
2. US2: listar, pesquisar, paginar e remover com isolamento.
3. US3: analytics anônima com ranking, top 3 inicial e percentuais.
4. Polish: coleções, traduções, regressão e suíte completa.

## Notes

- Cada tarefa segue `- [ ] Txxx [P?] [US?] descrição com caminho explícito`.
- O código de produção não deve ser alterado antes do Red correspondente.
- Não criar Product, tabela `wishlists`, repository, interface ou serviço genérico.
