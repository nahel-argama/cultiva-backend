# Tasks: Imagens de Ofertas (Offer Images)

**Input**: Design documents from `/specs/004-offer-images/`
**Prerequisites**: `plan.md`, `spec.md`, `contracts/openapi.yaml`

**Tests**: Obrigatórios. Toda adição de comportamento segue tarefas estritamente separadas e ordenadas: `[RED]`, `[RED-RUN]`, `[GREEN]` e `[GREEN-CHECK]`.

---

## TDD Execution Contract

1. Tarefas `[RED]` alteram apenas arquivos de teste e fixtures.
2. Tarefas `[RED-RUN]` executam o teste alvo no container `php` (`docker compose exec php php artisan test ...`) e confirmam a falha de comportamento.
3. Tarefas `[GREEN]` implementam estritamente o código de produção mínimo para fazer o teste passar.
4. Tarefas `[GREEN-CHECK]` executam novamente o teste alvo no container `php` e confirmam o status verde.
5. Todos os comandos Artisan e PHPUnit rodam via `docker compose exec php`. O Laravel Pint nunca é executado.

---

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Tarefa executável em paralelo com outras independentes
- **[Story]**: História de usuário associada (`[US1]` a `[US4]`)

---

## Phase 1: Foundational Actions & Policy (Unit Level)

### OfferPolicy — Red → Green
- [X] T001 [US2] [RED] Criar `tests/Unit/domain/Models/Offer/Policies/OfferPolicyTest.php` cobrindo métodos `update` e `deleteImage` (autoriza quando `$user->producer?->id === $offer->producer_id`, nega quando diferente ou usuário sem perfil de produtor).
- [X] T002 [US2] [RED-RUN] Executar `docker compose exec php php artisan test --filter=OfferPolicyTest` e confirmar falha por ausência da classe `OfferPolicy`.
- [X] T003 [US2] [GREEN] Implementar `domain/Models/Offer/Policies/OfferPolicy.php` com métodos one-liner e registrar no `AppServiceProvider.php` via `Gate::policy(Offer::class, OfferPolicy::class)`.
- [X] T004 [US2] [GREEN-CHECK] Reexecutar `docker compose exec php php artisan test --filter=OfferPolicyTest` e confirmar Green.

### GetOfferImagesAction & OfferImageDTO — Red → Green
- [X] T005 [US4] [RED] Criar `tests/Unit/domain/Models/Offer/Actions/GetOfferImagesActionTest.php` testando leitura no disco `public`, ordenação por `{order}` e parsing do identificador e da URL pública do storage a partir do padrão `{order}_{id}.{ext}`.
- [X] T006 [US4] [RED-RUN] Executar `docker compose exec php php artisan test --filter=GetOfferImagesActionTest` e confirmar falha.
- [X] T007 [US4] [GREEN] Implementar `domain/Models/Offer/DTO/OfferImageDTO.php` e `domain/Models/Offer/Actions/GetOfferImagesAction.php` utilizando `Storage::disk('public')`.
- [X] T008 [US4] [GREEN-CHECK] Reexecutar `docker compose exec php php artisan test --filter=GetOfferImagesActionTest` e confirmar Green.

### UploadOfferImagesAction — Red → Green
- [X] T009 [US1] [US2] [RED] Criar `tests/Unit/domain/Models/Offer/Actions/UploadOfferImagesActionTest.php` testando upload de arquivos simulados com `UploadedFile::fake()->image()`, verificação do teto de 3 imagens por oferta, atribuição correta de slots de ordem disponíveis e salvamento em `product_images/{offer_id}/{order}_{id}.{ext}`.
- [X] T010 [US1] [US2] [RED-RUN] Executar `docker compose exec php php artisan test --filter=UploadOfferImagesActionTest` e confirmar falha.
- [X] T011 [US1] [US2] [GREEN] Implementar `domain/Models/Offer/Actions/UploadOfferImagesAction.php` injetando `GetOfferImagesAction` e lançando `CultivaException(422, ...)` se exceder o limite de 3 imagens.
- [X] T012 [US1] [US2] [GREEN-CHECK] Reexecutar `docker compose exec php php artisan test --filter=UploadOfferImagesActionTest` e confirmar Green.

### DeleteOfferImageAction — Red → Green
- [X] T013 [US3] [RED] Criar `tests/Unit/domain/Models/Offer/Actions/DeleteOfferImageActionTest.php` testando exclusão do arquivo no storage a partir do `{id}` e lançamento de `CultivaException(404, ...)` caso a imagem não exista.
- [X] T014 [US3] [RED-RUN] Executar `docker compose exec php php artisan test --filter=DeleteOfferImageActionTest` e confirmar falha.
- [X] T015 [US3] [GREEN] Implementar `domain/Models/Offer/Actions/DeleteOfferImageAction.php`.
- [X] T016 [US3] [GREEN-CHECK] Reexecutar `docker compose exec php php artisan test --filter=DeleteOfferImageActionTest` e confirmar Green.

---

## Phase 2: User Story 1 - Upload de imagens na criação da oferta (Priority: P1)

- [X] T017 [US1] [RED] Criar `tests/Feature/domain/Models/Offer/Controllers/OfferController/StoreWithImagesTest.php` cobrindo `POST /v1/offers` em `multipart/form-data` com 1 a 3 imagens válidas, rejeição de mais de 3 imagens (422), rejeição de arquivos não imagem/tamanho > 5MB (422) e persistência dos arquivos no disco `public`.
- [X] T018 [US1] [RED-RUN] Executar `docker compose exec php php artisan test --filter=StoreWithImagesTest` e confirmar falha.
- [X] T019 [US1] [GREEN] Atualizar `domain/Models/Offer/Requests/StoreOfferRequest.php` para validar o campo opcional `images` (`array`, `max:3`, `images.*` sendo imagem e `max:5120`), atualizar `CreateOfferDTO` e `CreateOfferAction` para acionar `UploadOfferImagesAction`, e atualizar `OfferResource` para incluir `images`.
- [X] T020 [US1] [GREEN-CHECK] Reexecutar `docker compose exec php php artisan test --filter=StoreWithImagesTest` e confirmar Green.

---

## Phase 3: User Story 2 & 3 - Endpoints dedicados de Imagens (Upload e Exclusão) (Priority: P1)

- [X] T021 [US2] [RED] Criar `tests/Feature/domain/Models/Offer/Controllers/OfferImageController/UploadTest.php` testando `POST /v1/offers/{offer}/images` (autorização via policy, sucesso de upload acumulado $\le 3$, erro 422 quando ultrapassar 3 imagens e 403 para produtor que não é proprietário).
- [X] T022 [US2] [RED-RUN] Executar `docker compose exec php php artisan test --filter=UploadTest` e confirmar falha.
- [X] T023 [US2] [GREEN] Criar `domain/Models/Offer/Requests/UploadOfferImagesRequest.php`, `domain/Models/Offer/Controllers/OfferImageController.php` (método `store`) e registrar rota protegida por `profile:producer` e Gate em `routes/api/offers.php`.
- [X] T024 [US2] [GREEN-CHECK] Reexecutar `docker compose exec php php artisan test --filter=UploadTest` e confirmar Green.

- [X] T025 [US3] [RED] Criar `tests/Feature/domain/Models/Offer/Controllers/OfferImageController/DeleteTest.php` testando `DELETE /v1/offers/{offer}/images/{image}` (autorização via policy, retorno 204 e exclusão física do arquivo, 404 para imagem inexistente e 403 para produtor que não é proprietário).
- [X] T026 [US3] [RED-RUN] Executar `docker compose exec php php artisan test --filter=DeleteTest` e confirmar falha.
- [X] T027 [US3] [GREEN] Implementar método `destroy` no `OfferImageController.php` com chamada ao Gate e à `DeleteOfferImageAction`, e registrar rota `DELETE` em `routes/api/offers.php`.
- [X] T028 [US3] [GREEN-CHECK] Reexecutar `docker compose exec php php artisan test --filter=DeleteTest` e confirmar Green.

---

## Phase 4: User Story 4 - Visualização de Imagens no Index e Show (Priority: P2)

- [X] T029 [US4] [RED] Criar ou atualizar testes em `tests/Feature/domain/Models/Offer/Controllers/OfferController/IndexImagesTest.php` e `ShowImagesTest.php` garantindo que as respostas de listagem e detalhe de ofertas contenham a propriedade `images` com id, sequence e storage url, e `images: []` quando não houver arquivos.
- [X] T030 [US4] [RED-RUN] Executar `docker compose exec php php artisan test --filter=IndexImagesTest` e `ShowImagesTest` e confirmar o comportamento esperado.
- [X] T031 [US4] [GREEN] Assegurar integração do `GetOfferImagesAction` com `OfferResource` e `Offer` (de forma performática, resolvendo as URLs das imagens).
- [X] T032 [US4] [GREEN-CHECK] Reexecutar a suíte de testes de ofertas (`docker compose exec php php artisan test --filter=Offer`) e validar passagem completa de todos os testes.
