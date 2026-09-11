# Implementation Plan: Ofertas de Produtos

**Branch**: `001-product-offers` | **Date**: 2026-09-04 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/001-product-offers/spec.md`

**Note**: `001-product-offers` é o contexto lógico retornado pelo Spec Kit. Nenhuma branch Git foi criada porque não existe hook `before_specify`; a branch Git atual permanece `feat/create-order`.

## Summary

Implementar ofertas pertencentes ao produtor autenticado, vinculadas a categorias seedadas e a produtos validados sincronamente no `product-source`. O desenho adiciona os módulos `Offer` e `Category`, uma Action de integração que encapsula o contrato externo, seis rotas autenticadas e visibilidade calculada por status e saldo. Usa apenas Laravel, Sanctum, PostgreSQL e o cliente HTTP já instalados.

## Technical Context

**Language/Version**: PHP 8.3+

**Primary Dependencies**: Laravel 13.17, Laravel Sanctum 4, cliente HTTP nativo do Laravel; nenhuma dependência nova

**Storage**: PostgreSQL/PostGIS 17; duas novas tabelas (`categories`, `offers`)

**Testing**: PHPUnit 12.5, Mockery e `Http::fake()`, executados no container `php`

**Target Platform**: Serviço HTTP Linux executado por Docker Compose

**Project Type**: Backend web service REST versionado em `/v1`

**Performance Goals**: Listagens entregues em até 2 segundos no percentil 95 sob carga normal; consulta externa limitada a 5 segundos

**Constraints**: Propriedade derivada exclusivamente do token; access token obrigatório; `product-source` síncrono e externo ao Compose; preço decimal exato; estoque inteiro; falhas externas sem persistência parcial; sem retry nesta versão

**Scale/Scope**: 2 entidades persistidas, 6 endpoints, 1 integração externa e 5 categorias fixas; volume de usuários não informado

## Constitution Check

*GATE: aprovado antes da pesquisa e reavaliado após o desenho da Phase 1.*

| Gate constitucional | Pré-pesquisa | Pós-design | Evidência no desenho |
|---|---|---|---|
| Valor ao pequeno produtor e concorrência não predatória | PASS | PASS | O produtor controla preço, estoque e status; a listagem não ranqueia por menor preço. |
| Simplicidade, inclusão e respostas enxutas | PASS | PASS | Seis rotas focadas, paginação padrão de 15 itens e nenhum fluxo especulativo. |
| Segurança, propriedade e rastreabilidade | PASS | PASS | `auth:sanctum`, ability `access`, perfil, consulta por `producer_id`, timestamps e constraints. |
| Limites do produto | PASS | PASS | Pedidos, pagamento, reserva e logística permanecem fora do escopo. |
| Organização por domínio | PASS | PASS | `Models/Offer`, `Models/Category` e `Integrations/ProductSource`. |
| Actions como unidade de negócio | PASS | PASS | Uma Action por operação, apenas `execute()` público e retornos tipados. |
| DTOs e fronteiras tipadas | PASS | PASS | DTOs readonly para criação, edição e produto externo; arrays ficam apenas na camada HTTP. |
| Validação em duas camadas | PASS | PASS | FormRequests validam formato/campos proibidos; Actions validam categoria, propriedade e estoque. |
| Persistência e consistência | PASS | PASS | Eloquent direto, constraints no banco e HTTP antes da única escrita; nenhuma transação longa. |
| Exceções e integração externa | PASS | PASS | A integração converte 404 em 422 e indisponibilidade em 503; payload externo não sai do módulo. |
| Abstração somente com evidência | PASS | PASS | Sem repository, facade, provider resolver, interface unitária ou nova biblioteca HTTP. |
| TDD e Feature tests reais | PASS | PASS | Rotas completas, factories persistidas, PostgreSQL real e mock apenas do HTTP externo. |
| Execução oficial | PASS | PASS | Todos os comandos PHP, Artisan e PHPUnit permanecem dentro do container `php`; Pint não será usado. |

Não há violação constitucional ou decisão pendente.

## Project Structure

### Documentation (this feature)

```text
specs/001-product-offers/
├── plan.md
├── research.md
├── data-model.md
├── quickstart.md
├── contracts/
│   ├── openapi.yaml
│   └── product-source.openapi.yaml
└── tasks.md                         # criado somente por $speckit-tasks
```

### Source Code (repository root)

```text
.env.example                         # configuração documentada do product-source
bootstrap/app.php                     # aliases de ability e perfil
config/services.php                   # base URL e timeout do product-source
routes/api.php                        # seis novas rotas /v1

database/
├── factories/
│   ├── CategoryFactory.php
│   └── OfferFactory.php
├── migrations/
│   └── *_create_categories_and_offers_tables.php
└── seeders/
    ├── CategorySeeder.php
    └── DatabaseSeeder.php

domain/
├── Auth/Middleware/EnsureProfile.php
├── Base/Exceptions/CultivaException.php
├── Integrations/ProductSource/
│   ├── Actions/GetProductAction.php
│   └── DTO/ProductDTO.php
└── Models/
    ├── Category/
    │   ├── Actions/ListCategoriesAction.php
    │   ├── Http/Controllers/CategoryController.php
    │   ├── Transformers/CategoryTransformer.php
    │   └── Category.php
    ├── Offer/
    │   ├── Actions/
    │   │   ├── CreateOfferAction.php
    │   │   ├── GetOfferAction.php
    │   │   ├── ListAvailableOffersAction.php
    │   │   ├── ListProducerOffersAction.php
    │   │   └── UpdateOfferAction.php
    │   ├── DTO/
    │   │   ├── CreateOfferDTO.php
    │   │   └── UpdateOfferDTO.php
    │   ├── Enums/OfferStatus.php
    │   ├── Http/
    │   │   ├── Controllers/OfferController.php
    │   │   └── Requests/
    │   │       ├── ListOffersRequest.php
    │   │       ├── StoreOfferRequest.php
    │   │       └── UpdateOfferRequest.php
    │   ├── Transformers/OfferTransformer.php
    │   └── Offer.php
    └── Producer/Producer.php         # relação offers()

lang/en/
├── integrations.php
└── offers.php

tests/
├── Feature/domain/Models/
│   ├── Category/Http/Controllers/CategoryControllerTest.php
│   └── Offer/Http/Controllers/
│       └── OfferController/
│           ├── AvailableIndexTest.php
│           ├── IndexTest.php
│           ├── ShowTest.php
│           ├── StoreTest.php
│           └── UpdateTest.php
├── Fixtures/Integrations/ProductSource/
│   ├── product_success.php
│   └── product_without_presentation_name.php
└── Unit/domain/Integrations/ProductSource/Actions/GetProductActionTest.php
```

**Structure Decision**: Manter o monólito Laravel organizado por domínio. `Offer` e `Category` possuem seus próprios módulos; o contrato HTTP do catálogo fica integralmente em `Integrations/ProductSource`. O cliente HTTP nativo é usado diretamente pela Action de integração, evitando Client, Adapter, interface e provider resolver de implementação única.

## Phase 0: Research Result

As decisões e alternativas estão consolidadas em [research.md](research.md). Todos os pontos técnicos foram resolvidos, inclusive autenticação por ability, rede Docker-host, contrato externo, constraints, paginação e isolamento do banco de testes.

## Phase 1: Design Result

- [data-model.md](data-model.md): tabelas, campos, constraints, relações, validações e transições.
- [contracts/openapi.yaml](contracts/openapi.yaml): seis endpoints públicos do backend.
- [contracts/product-source.openapi.yaml](contracts/product-source.openapi.yaml): operação externa consumida e tradução de falhas.
- [quickstart.md](quickstart.md): validação do ambiente e cenários end-to-end.

O desenho pós-Phase 1 continua aprovado em todos os gates constitucionais. A próxima etapa é `$speckit-tasks`; este comando não cria `tasks.md`.

## Paginação nativa da API

As duas Actions de listagem mantêm `paginate()`. `OfferController@index` aplica `OfferTransformer` via `through()` e serializa somente `data`, `current_page`, `per_page`, `total`, `has_previous_page` e `has_next_page`. `ListOffersRequest` mantém validação e limites. Não existem classes próprias de paginação; os testes Feature de Producer e Retailer validam o contrato exato, sem links.
