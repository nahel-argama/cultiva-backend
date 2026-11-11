# Implementation Plan: Calendário de Previsão de Colheita

**Branch**: `[004-seasonal-harvest-calendar]` | **Date**: 2026-11-10 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/004-seasonal-harvest-calendar/spec.md`

## Summary

Adicionar ao backend previsões independentes de colheita por produtor e produto, separadas das ofertas e do estoque. Produtores poderão criar, editar e remover previsões; compradores poderão listar e filtrar previsões futuras por produto e período. Sugestões sazonais serão referências gerais por produto, confirmadas pelo produtor antes da publicação. Produtos sem período cadastrado continuam aceitando data específica.

## Technical Context

**Language/Version**: PHP 8.3+

**Primary Dependencies**: Laravel 13, Eloquent, Sanctum, Product Source client existente

**Storage**: PostgreSQL

**Testing**: PHPUnit 12 dentro do container Docker `php`; Feature tests com ciclo real e Unit tests somente para regras de Action isoladas

**Target Platform**: API web do backend Cultiva

**Project Type**: Backend de aplicação web

**Performance Goals**: Consultas de previsões paginadas; preservar padrão da API, com `per_page` entre 1 e 100 e padrão 15

**Constraints**: Apenas backend nesta entrega; auth/profile middleware existente; previsões não podem criar nem alterar ofertas/estoque; Product Source retorna id e nome do produto, sem sazonalidade; estado vem do endereço da empresa do produtor; calendário de referência começa sem dados até haver fonte agronômica apropriada

**Scale/Scope**: Previsões independentes por produtor/produto; listagem pública para compradores autenticados; filtros por produto e intervalo de datas; UI, favoritos e notificações ficam fora desta entrega

## Constitution Check

**Pre-research gate: PASS**

- A feature amplia conexão entre produtor e varejista sem alterar regras de preço, estoque ou compra (A.1, A.2, A.6).
- Período sazonal será apresentado como referência e exigirá confirmação do produtor, sem alegar certeza (A.5).
- Escritas seguem Actions, DTOs tipados, FormRequests, Resources e Eloquent sem repositório genérico (B.I–B.V).
- Permissões por perfil e por propriedade do registro são requisitos explícitos; testes seguirão Red-Green-Refactor e ciclo real nas Feature tests (A.4, A.7, B.XI–B.XV).

## Project Structure

### Documentation (this feature)

```text
specs/004-seasonal-harvest-calendar/
├── spec.md
├── plan.md
├── research.md
├── data-model.md
├── quickstart.md
├── contracts/openapi.yaml
├── checklists/requirements.md
└── tasks.md
```

### Source Code (repository root)

```text
domain/Models/HarvestForecast/
├── Actions/
├── Controllers/
├── DTO/
├── Requests/
├── Resources/
└── HarvestForecast.php

domain/Models/Producer/Producer.php
domain/Integrations/ProductSource/Actions/GetProductAction.php
routes/api/harvest-forecasts.php
routes/api.php
database/migrations/
config/harvest_seasons.php

tests/Feature/domain/Models/HarvestForecast/Controllers/HarvestForecastController/
tests/Unit/domain/Models/HarvestForecast/Actions/
```

**Structure Decision**: Seguir módulo por entidade já usado em `domain/Models/Offer`, mantendo Controller, Requests, DTO, Resources e Actions em seus diretórios existentes. Usar Product Source para validar e snapshotar o produto; usar Eloquent diretamente para previsões. Períodos sazonais ficam em configuração do backend, sem CRUD ou tabela administrativa.

## Complexity Tracking

Sem violações da constituição identificadas.

## Phase 0: Research

Concluída em [research.md](research.md). Não há integração externa de sazonalidade disponível no Product Source; períodos de referência são dados locais revisáveis, sem consulta por estado. Catálogo inicial fica vazio para não atribuir dados regionais a uma referência nacional. Referências ausentes não bloqueiam data específica.

## Phase 1: Design & Contracts

- Entidades e invariantes: [data-model.md](data-model.md)
- API backend para produtor e comprador: [contracts/openapi.yaml](contracts/openapi.yaml)
- Cenários de validação após implementação: [quickstart.md](quickstart.md)

**Post-design gate: PASS** — requisitos mantêm sazonalidade transparente e opcional, ownership protegido, listagem paginada e estoque isolado.
