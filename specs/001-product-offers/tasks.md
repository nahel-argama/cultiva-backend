# Tasks: Ofertas de Produtos

**Input**: Design documents from `/specs/001-product-offers/`
**Prerequisites**: `plan.md`, `spec.md`, `research.md`, `data-model.md`, `contracts/`, `quickstart.md`

**Tests**: Obrigatórios. Cada mudança de comportamento segue tarefas separadas e ordenadas de Red, confirmação do Red, Green e confirmação do Green.

**Organization**: As tarefas estão agrupadas por história de usuário. A US1 entrega a infraestrutura mínima compartilhada de categorias e ofertas porque ela é necessária para criar a primeira oferta.

## TDD Execution Contract

1. Tarefas `[RED]` alteram somente testes e fixtures.
2. Tarefas `[RED-RUN]` executam o teste alvo no container `php` e registram comando, falha e motivo em `specs/001-product-offers/tdd-evidence.md`.
3. Falha de infraestrutura, ambiente ou sintaxe não conta como Red. Se o teste passar ou falhar pelo motivo errado, corrija/fortaleça o teste e repita; não inicie o Green.
4. Tarefas `[GREEN]` só começam após o Red válido da mesma fatia estar registrado. Implemente apenas o necessário para passar.
5. Tarefas `[GREEN-CHECK]` repetem o teste alvo no container e registram o resultado verde. Red e Green nunca são combinados nem executados em paralelo.
6. Todos os comandos PHP, Artisan e PHPUnit usam `docker compose exec php`; Laravel Pint não é executado.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Pode ser executada em paralelo com outras tarefas explicitamente independentes da mesma etapa
- **[Story]**: História de usuário atendida (`[US1]` a `[US5]`)
- Cada tarefa contém os caminhos exatos dos arquivos envolvidos

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Preparar ambiente e trilha de evidência antes de qualquer implementação.

- [X] T001 [P] Criar o modelo de registro Red/Green com colunas para tarefa, comando Docker, falha esperada/observada e resultado Green em `specs/001-product-offers/tdd-evidence.md`
- [X] T002 [P] Documentar `PRODUCT_SOURCE_BASE_URL=http://host.docker.internal:8001/api`, `PRODUCT_SOURCE_TIMEOUT=5` e corrigir o banco isolado para `DB_DATABASE=app_testing` em `.env.example` e `.env.testing.example`
- [X] T003 Preparar e conferir exclusivamente o banco `app_testing` pelos comandos Docker descritos em `specs/001-product-offers/quickstart.md`, abortando se `DB_DATABASE` resolver para `app`

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Corrigir o contrato de exceção compartilhado antes das histórias. Nenhum código de oferta é criado nesta fase.

**CRITICAL**: A US1 não começa antes de T006 estar verde.

- [X] T004 [RED-RUN] Executar no container o teste existente de status HTTP de `tests/Unit/domain/Base/Exceptions/CultivaExceptionTest.php`, confirmar a falha causada pelo construtor não inicializar a exceção pai e registrar o Red em `specs/001-product-offers/tdd-evidence.md`; se não houver Red válido, não prosseguir
- [X] T005 [GREEN] Corrigir somente a inicialização de mensagem/código/status da exceção base em `domain/Base/Exceptions/CultivaException.php`
- [X] T006 [GREEN-CHECK] Reexecutar `tests/Unit/domain/Base/Exceptions/CultivaExceptionTest.php` no container e registrar o Green em `specs/001-product-offers/tdd-evidence.md`

**Checkpoint**: Exceções de domínio podem traduzir 422/503 corretamente; as fatias da feature podem iniciar.

---

## Phase 3: User Story 1 - Criar oferta a partir do catálogo externo (Priority: P1) 🎯 MVP

**Goal**: Permitir que um Producer autenticado crie sua própria oferta a partir de produto externo e categoria válida, com snapshot do nome e estoque inicial seguro.

**Independent Test**: Com `Http::fake`, autenticar um Producer com access token, enviar um produto/categoria válidos e verificar 201, ownership pelo token, snapshot, preço, estoque, status padrão e banco; depois provar 422 para produto ausente e 503 para indisponibilidade sem persistência.

### Product-source boundary — Red → Green

- [X] T007 [US1] [RED] Criar fixtures PHP de produto com nome e fallback em `tests/Fixtures/Integrations/ProductSource/product_success.php` e `tests/Fixtures/Integrations/ProductSource/product_without_presentation_name.php`, e escrever `tests/Unit/domain/Integrations/ProductSource/Actions/GetProductActionTest.php` cobrindo ID textual exato, fallback `normal_name`, 404→422, timeout/conexão/5xx/payload inválido→503 e ausência de retry; não criar código de produção
- [X] T008 [US1] [RED-RUN] Executar `docker compose exec php php artisan test --filter=GetProductActionTest`, confirmar falha por ausência do comportamento ProductSource e registrar o Red em `specs/001-product-offers/tdd-evidence.md`; bloquear T009 se a falha for ambiental, sintática ou o teste passar
- [X] T009 [US1] [GREEN] Implementar o mínimo da integração com HTTP nativo, timeout configurável de 5 segundos e sem retry em `domain/Integrations/ProductSource/DTO/ProductDTO.php`, `domain/Integrations/ProductSource/Actions/GetProductAction.php`, `config/services.php` e `lang/pt_BR/integrations.php`
- [X] T010 [US1] [GREEN-CHECK] Reexecutar `tests/Unit/domain/Integrations/ProductSource/Actions/GetProductActionTest.php` no container e registrar o Green em `specs/001-product-offers/tdd-evidence.md`

### Create offer route — Red → Green

- [X] T011 [US1] [RED] Escrever somente `tests/Feature/domain/Models/Offer/Http/Controllers/OfferController/StoreTest.php`, usando rota real, `ProducerFactory::new()`, banco real e fake apenas do HTTP externo, para cobrir 201, produtor do token, `reserved_quantity=0`, status padrão `inactive`, preservação de `"0002"`, snapshot/fallback do nome, categoria inexistente, preço/estoque/status inválidos, proibição de `producer_id` e `reserved_quantity`, 401 sem token, 403 com refresh/perfil errado, 422 externo e 503 externo sem escrita
- [X] T012 [US1] [RED-RUN] Executar `docker compose exec php php artisan test tests/Feature/domain/Models/Offer/Http/Controllers/OfferController/StoreTest.php`, confirmar falha pela rota/comportamento ainda ausente e registrar o Red em `specs/001-product-offers/tdd-evidence.md`; bloquear todo Green de criação sem esse Red válido
- [X] T013 [US1] [GREEN] Criar `database/migrations/2026_09_04_000000_create_categories_and_offers_tables.php` com FKs, defaults, checks e índices definidos em `specs/001-product-offers/data-model.md`, e criar `domain/Models/Offer/Enums/OfferStatus.php` somente com `active` e `inactive`
- [X] T014 [US1] [GREEN] Implementar models, relações, casts e valores derivados em `domain/Models/Category/Category.php`, `domain/Models/Offer/Offer.php` e `domain/Models/Producer/Producer.php`, mais suporte de persistência dos testes em `database/factories/CategoryFactory.php` e `database/factories/OfferFactory.php`
- [X] T015 [US1] [GREEN] Criar o upsert idempotente das cinco categorias fixas em `database/seeders/CategorySeeder.php` e chamá-lo em `database/seeders/DatabaseSeeder.php`
- [X] T016 [P] [US1] [GREEN] Implementar o middleware parametrizado de perfil em `domain/Auth/Middleware/EnsureProfile.php` e registrar os aliases nativos de `ability` e `profile` em `bootstrap/app.php`, reutilizando Sanctum e os enums de autenticação existentes
- [X] T017 [US1] [GREEN] Criar o contrato de entrada e validação HTTP de criação em `domain/Models/Offer/DTO/CreateOfferDTO.php` e `domain/Models/Offer/Http/Requests/StoreOfferRequest.php`, normalizando `source_product_id` para string sem remover zeros e proibindo IDs/estoque reservados fornecidos pelo cliente
- [X] T018 [US1] [GREEN] Implementar o fluxo real mínimo em `domain/Models/Offer/Actions/CreateOfferAction.php`, `domain/Models/Category/Transformers/CategoryTransformer.php`, `domain/Models/Offer/Transformers/OfferTransformer.php`, `domain/Models/Offer/Http/Controllers/OfferController.php`, `routes/api.php` e `lang/pt_BR/offers.php`, validando produto/categoria antes de persistir e obtendo Producer exclusivamente do token
- [X] T019 [US1] [GREEN-CHECK] Reexecutar `tests/Feature/domain/Models/Offer/Http/Controllers/OfferController/StoreTest.php` no container, confirmar todos os cenários verdes e registrar o resultado em `specs/001-product-offers/tdd-evidence.md`
- [X] T020 [US1] [GREEN-CHECK] Executar juntos `tests/Unit/domain/Integrations/ProductSource/Actions/GetProductActionTest.php` e `tests/Feature/domain/Models/Offer/Http/Controllers/OfferController/StoreTest.php` no container e registrar a regressão verde da US1 em `specs/001-product-offers/tdd-evidence.md`

**Checkpoint**: Um Producer cria uma oferta válida pelo contrato público, e falhas externas não deixam escrita parcial.

---

## Phase 4: User Story 2 - Listar categorias disponíveis (Priority: P1)

**Goal**: Permitir que somente Producer com access token obtenha as cinco categorias seedadas e estáveis.

**Independent Test**: Sem depender de ofertas existentes, semear duas vezes e chamar a rota como Producer; verificar 200, exatamente os IDs/nomes definidos e ausência de duplicatas, além de 401/403.

- [X] T021 [US2] [RED] Escrever somente `tests/Feature/domain/Models/Category/Http/Controllers/CategoryControllerTest.php` com ciclo real, `ProducerFactory::new()`, seeder idempotente, conjunto exato das cinco categorias e cenários sem token, refresh token e perfil Retailer
- [X] T022 [US2] [RED-RUN] Executar `docker compose exec php php artisan test tests/Feature/domain/Models/Category/Http/Controllers/CategoryControllerTest.php`, confirmar falha pela rota/listagem ausente e registrar o Red em `specs/001-product-offers/tdd-evidence.md`; não iniciar T023 sem Red válido
- [X] T023 [US2] [GREEN] Implementar listagem ordenada por ID em `domain/Models/Category/Actions/ListCategoriesAction.php` e `domain/Models/Category/Http/Controllers/CategoryController.php`, expondo `GET /v1/producer/categories` sob os middlewares Producer em `routes/api.php`
- [X] T024 [US2] [GREEN-CHECK] Reexecutar `tests/Feature/domain/Models/Category/Http/Controllers/CategoryControllerTest.php` no container e registrar o Green em `specs/001-product-offers/tdd-evidence.md`

**Checkpoint**: A tabela de referência é idempotente e consultável somente pelo perfil Producer.

---

## Phase 5: User Story 3 - Consultar somente as próprias ofertas (Priority: P1)

**Goal**: Producer lista e consulta exclusivamente suas ofertas, incluindo inativas e esgotadas, com paginação válida.

**Independent Test**: Persistir ofertas para dois Producers e provar que index/show retornam somente as próprias, preservam todos os estados, retornam 404 para oferta alheia/inexistente e respeitam autenticação/paginação.

- [X] T025 [P] [US3] [RED] Escrever somente `tests/Feature/domain/Models/Offer/Http/Controllers/OfferController/IndexTest.php` com factories diretas para ownership, inclusão de inativas/esgotadas, paginação, ordem não baseada em menor preço, 401 e 403
- [X] T026 [P] [US3] [RED] Escrever somente `tests/Feature/domain/Models/Offer/Http/Controllers/OfferController/ShowTest.php` com factories diretas para oferta própria, alheia e inexistente, validando 200/404 e 401/403 pelo ciclo real
- [X] T027 [US3] [RED-RUN] Executar no container `tests/Feature/domain/Models/Offer/Http/Controllers/OfferController/IndexTest.php` e `tests/Feature/domain/Models/Offer/Http/Controllers/OfferController/ShowTest.php`, confirmar falhas pelos endpoints ausentes e registrar ambos os Reds em `specs/001-product-offers/tdd-evidence.md`; bloquear o Green se qualquer Red for inválido
- [X] T028 [P] [US3] [GREEN] Implementar paginação limitada ao Producer autenticado em `domain/Models/Offer/Actions/ListProducerOffersAction.php` e validar `page`/`per_page` em `domain/Models/Offer/Http/Requests/ListOffersRequest.php`
- [X] T029 [P] [US3] [GREEN] Implementar busca de detalhe já limitada à relação do Producer em `domain/Models/Offer/Actions/GetOfferAction.php`, retornando 404 indistinguível para oferta alheia ou inexistente
- [X] T030 [US3] [GREEN] Adicionar os fluxos finos de index/show em `domain/Models/Offer/Http/Controllers/OfferController.php` e as rotas `GET /v1/producer/offers` e `GET /v1/producer/offers/{offer}` em `routes/api.php`
- [X] T031 [US3] [GREEN-CHECK] Reexecutar `tests/Feature/domain/Models/Offer/Http/Controllers/OfferController/IndexTest.php` e `tests/Feature/domain/Models/Offer/Http/Controllers/OfferController/ShowTest.php` no container e registrar ambos os Greens em `specs/001-product-offers/tdd-evidence.md`
- [X] T032 [US3] [GREEN-CHECK] Executar todos os testes em `tests/Feature/domain/Models/Offer/Http/Controllers/OfferController/` no container e registrar a regressão verde das rotas Producer em `specs/001-product-offers/tdd-evidence.md`

**Checkpoint**: A administração de ofertas nunca revela nem permite localizar ofertas de outro Producer.

---

## Phase 6: User Story 4 - Editar uma oferta própria (Priority: P2)

**Goal**: Producer altera campos editáveis de uma oferta própria, sempre revalidando o produto externo e preservando invariantes de estoque/ownership.

**Independent Test**: Atualizar parcialmente uma oferta própria com produto fake válido e verificar 200/snapshot/banco; provar revalidação mesmo sem novo ID, 404 para oferta alheia, 422 para total abaixo do reservado ou produto ausente e 503 externo, sempre sem alteração parcial.

- [X] T033 [US4] [RED] Escrever somente `tests/Feature/domain/Models/Offer/Http/Controllers/OfferController/UpdateTest.php` cobrindo PATCH parcial, novo snapshot, revalidação do ID atual, categoria/preço/status, payload vazio, campos proibidos, `total_quantity < reserved_quantity`, ownership 404, 401/403 e traduções externas 422/503 com banco inalterado
- [X] T034 [US4] [RED-RUN] Executar `docker compose exec php php artisan test tests/Feature/domain/Models/Offer/Http/Controllers/OfferController/UpdateTest.php`, confirmar falha pelo endpoint/comportamento ausente e registrar o Red em `specs/001-product-offers/tdd-evidence.md`; não iniciar T035 sem Red válido
- [X] T035 [US4] [GREEN] Criar PATCH tipado e validação de transporte em `domain/Models/Offer/DTO/UpdateOfferDTO.php` e `domain/Models/Offer/Http/Requests/UpdateOfferRequest.php`, exigindo ao menos um campo editável e proibindo `producer_id`/`reserved_quantity`
- [X] T036 [US4] [GREEN] Implementar atualização atômica após todas as validações em `domain/Models/Offer/Actions/UpdateOfferAction.php`, sempre consultando o product-source e recusando total efetivo abaixo do reservado sem alterar a Offer
- [X] T037 [US4] [GREEN] Adicionar o método update fino em `domain/Models/Offer/Http/Controllers/OfferController.php` e `PATCH /v1/producer/offers/{offer}` sob os middlewares Producer em `routes/api.php`
- [X] T038 [US4] [GREEN-CHECK] Reexecutar `tests/Feature/domain/Models/Offer/Http/Controllers/OfferController/UpdateTest.php` no container e registrar o Green em `specs/001-product-offers/tdd-evidence.md`
- [X] T039 [US4] [GREEN-CHECK] Executar todos os testes em `tests/Feature/domain/Models/Offer/Http/Controllers/OfferController/` no container e registrar a regressão verde de criação, consulta e edição em `specs/001-product-offers/tdd-evidence.md`

**Checkpoint**: Edição preserva ownership, revalida o catálogo e nunca deixa atualização parcial inválida.

---

## Phase 7: User Story 5 - Listar ofertas visíveis para varejistas (Priority: P2)

**Goal**: Retailer autenticado enxerga apenas ofertas `active` com `total_quantity > reserved_quantity`, sem ranqueamento por menor preço.

**Independent Test**: Persistir a matriz active/inactive × com saldo/esgotada e verificar que somente active + com saldo aparece, com quantidades derivadas corretas; Producer/refresh recebem 403 e ausência de token recebe 401.

- [X] T040 [US5] [RED] Escrever somente `tests/Feature/domain/Models/Offer/Http/Controllers/AvailableOfferControllerTest.php` com a matriz completa de visibilidade, múltiplos Producers, paginação, `available_quantity`, `is_visible`, ausência de ordenação por menor preço e cenários 401/403
- [X] T041 [US5] [RED-RUN] Executar `docker compose exec php php artisan test tests/Feature/domain/Models/Offer/Http/Controllers/AvailableOfferControllerTest.php`, confirmar falha pela rota/listagem ausente e registrar o Red em `specs/001-product-offers/tdd-evidence.md`; não iniciar T042 sem Red válido
- [X] T042 [US5] [GREEN] Implementar a consulta Eloquent mínima de ofertas visíveis e paginadas em `domain/Models/Offer/Actions/ListAvailableOffersAction.php`, filtrando status e saldo sem repository ou ordenação por preço
- [X] T043 [US5] [GREEN] Criar o controller fino em `domain/Models/Offer/Http/Controllers/AvailableOfferController.php` e expor `GET /v1/retailer/offers` sob os middlewares Retailer em `routes/api.php`
- [X] T044 [US5] [GREEN-CHECK] Reexecutar `tests/Feature/domain/Models/Offer/Http/Controllers/AvailableOfferControllerTest.php` no container e registrar o Green em `specs/001-product-offers/tdd-evidence.md`

**Checkpoint**: A vitrine do Retailer contém somente ofertas efetivamente vendáveis.

---

## Phase 8: Polish & Cross-Cutting Validation

**Purpose**: Provar migração, contratos, regressão e aderência sem ampliar o escopo.

- [X] T045 Confirmar `DB_DATABASE=app_testing`, executar migrate fresh/seed somente nesse banco pelo roteiro de `specs/001-product-offers/quickstart.md` e verificar constraints, cinco categorias e rollback da migration `database/migrations/2026_09_04_000000_create_categories_and_offers_tables.php`
- [X] T046 Executar no container os testes focados de `tests/Unit/domain/Integrations/ProductSource/Actions/GetProductActionTest.php`, `tests/Feature/domain/Models/Category/Http/Controllers/CategoryControllerTest.php` e `tests/Feature/domain/Models/Offer/Http/Controllers/`, registrando o resultado consolidado em `specs/001-product-offers/tdd-evidence.md`
- [X] T047 Executar `docker compose exec php php artisan test` para a suíte completa, sem Pint, e registrar o resultado final em `specs/001-product-offers/tdd-evidence.md`
- [X] T048 Conferir as seis rotas com `docker compose exec php php artisan route:list --path=v1`, comparar request/response/status com `specs/001-product-offers/contracts/openapi.yaml` e a tradução externa com `specs/001-product-offers/contracts/product-source.openapi.yaml`
- [X] T049 [P] Adicionar uma pasta Offers com as seis requisições autenticadas — categorias, criar, listar próprias, detalhar, editar e listar disponíveis — em `collection/bruno/API/Offers/folder.yml`, `collection/bruno/API/Offers/List Categories.yml`, `collection/bruno/API/Offers/Create Offer.yml`, `collection/bruno/API/Offers/List Producer Offers.yml`, `collection/bruno/API/Offers/Show Offer.yml`, `collection/bruno/API/Offers/Update Offer.yml` e `collection/bruno/API/Offers/List Available Offers.yml`, parametrizando tokens/IDs em `collection/bruno/environments/local.yml`
- [X] T050 [P] Adicionar uma pasta Offers com as mesmas seis requisições, payloads e autenticação bearer em `collection/postman/Cultiva.postman_collection.json`, incluindo variáveis distintas para access tokens de Producer/Retailer, `offer_id` e `source_product_id`
- [X] T051 Validar que Bruno e Postman importam sem erro e possuem paridade de método, URL, headers, autenticação, parâmetros e payloads com os seis endpoints de `specs/001-product-offers/contracts/openapi.yaml`, sem armazenar tokens reais em `collection/bruno/environments/local.yml` ou `collection/postman/Cultiva.postman_collection.json`
- [X] T052 Revisar os arquivos listados em `specs/001-product-offers/plan.md` contra `AGENTS.md` e `.agents/skills/architecture/SKILL.md`, removendo somente abstrações sem uso e completando a evidência de Red/Green em `specs/001-product-offers/tdd-evidence.md`

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: inicia imediatamente; T003 depende de T002.
- **Foundational (Phase 2)**: depende do Setup e deve terminar antes da feature.
- **US1 (Phase 3)**: depende do Foundational e cria a base mínima compartilhada de Category/Offer.
- **US2 (Phase 4)**: depende da base de Category entregue pela US1.
- **US3 (Phase 5)**: depende da base de Offer e do transformer entregues pela US1.
- **US4 (Phase 6)**: depende da criação/ownership da US1; não depende funcionalmente da US3.
- **US5 (Phase 7)**: depende da base de Offer e visibilidade derivada da US1.
- **Polish (Phase 8)**: depende de todas as histórias selecionadas para a entrega.

### User Story Dependency Graph

```text
Setup → Foundational → US1 (MVP)
                         ├── US2
                         ├── US3
                         ├── US4
                         └── US5
US2 + US3 + US4 + US5 → Polish
```

Após a US1, as histórias são funcionalmente independentes. Se forem executadas em paralelo, coordene a propriedade de `routes/api.php` e `domain/Models/Offer/Http/Controllers/OfferController.php` para evitar conflitos de edição.

### Mandatory TDD Order Within Each Slice

```text
[RED test only] → [RED-RUN validado] → [GREEN implementation] → [GREEN-CHECK]
```

Nenhuma marcação `[P]` autoriza adiantar Green antes do Red confirmado da mesma fatia.

### Parallel Opportunities

- Setup: T001 e T002 podem ocorrer em paralelo; T003 aguarda T002.
- US1: após T012, T016 pode ocorrer em paralelo com T013; T014 aguarda o schema e T017/T018 convergem depois.
- US2: não há paralelismo útil; o menor caminho é T021 → T022 → T023 → T024.
- US3: T025/T026 podem ocorrer em paralelo; após T027, T028/T029 podem ocorrer em paralelo antes de T030.
- US4: manter sequencial porque DTO, Action, controller e rota formam uma única fatia pequena.
- US5: manter sequencial porque consulta, controller e rota formam uma única fatia pequena.
- Polish: T049 e T050 podem ocorrer em paralelo após T048; T051 valida as duas coleções.
- Em qualquer história, `[RED-RUN]` e `[GREEN]` jamais executam em paralelo.

---

## Parallel Example: User Story 1

Após T012 registrar o Red válido:

```text
Task T013: schema e enum
Task T016: middleware e aliases de autenticação
```

Depois, seguir T014 → T015/T017 → T018 → T019 sem antecipar o Green check.

## Parallel Example: User Story 2

```text
T021 → T022 → T023 → T024
```

Não dividir a única Action/controller: a coordenação custaria mais que a implementação.

## Parallel Example: User Story 3

```text
Em paralelo: T025 IndexTest | T026 ShowTest
Depois de T027: T028 ListProducerOffersAction | T029 GetOfferAction
Convergir: T030 → T031 → T032
```

## Parallel Example: User Story 4

```text
T033 → T034 → T035 → T036 → T037 → T038 → T039
```

Manter sequencial preserva o Red antes de qualquer código e evita conflito no fluxo PATCH.

## Parallel Example: User Story 5

```text
T040 → T041 → T042 → T043 → T044
```

Manter sequencial é a execução mínima para uma única rota de leitura.

---

## Implementation Strategy

### MVP First (US1 Only)

1. Concluir Setup e Foundational.
2. Executar T007–T020 em ordem, respeitando os dois ciclos Red-Green.
3. Validar independentemente a criação de oferta.
4. Parar e demonstrar o MVP antes de ampliar para listagens/edição.

### Incremental Delivery

1. Setup + Foundational → ambiente e exceções confiáveis.
2. US1 → criação de oferta (MVP).
3. US2 → categorias consultáveis.
4. US3 → administração isolada por Producer.
5. US4 → edição segura.
6. US5 → vitrine vendável para Retailer.
7. Polish → contratos, migração e suíte completa.

### Scope Stop Condition

A feature termina quando os seis endpoints e seus cenários do contrato estiverem verdes no container, Bruno e Postman cobrirem o mesmo contrato, os Reds prévios estiverem registrados e não houver category CRUD, delete de Offer, reserva/venda, retry/cache do catálogo, repository ou interface de implementação única.

---

## Notes

- `[P]` significa arquivos diferentes e ausência de dependência direta; não elimina checkpoints TDD.
- Feature tests usam rota/middleware/FormRequest/controller/Action/transformer/PostgreSQL reais e factories diretas.
- Somente o HTTP outbound do product-source pode ser fakeado.
- Cada Green deve ser o mínimo que satisfaz o teste e os contratos existentes.
- Faça commit apenas após um Green confirmado; nunca entre Red e Green.
