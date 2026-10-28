# Quickstart Validation: Ofertas de Produtos

## Prerequisites

- Docker Compose com os serviços `php`, `database` e `redis` do backend.
- Python 3.14+ e dependências do repositório irmão `../product-source`.
- Banco PostgreSQL local `app`, tratado como descartável em desenvolvimento e testes.
- `product-source` acessível ao container PHP.

Não execute Laravel Pint.

## 1. Configure o catálogo externo

No repositório `../product-source`, configure:

```dotenv
API_HOST=0.0.0.0
API_PORT=8001
```

O bind `0.0.0.0` é necessário porque o backend acessa o processo do host a partir do container.

Inicie o catálogo no host:

```bash
cd ../product-source
python api.py
```

Valide no host:

```bash
curl http://127.0.0.1:8001/health
curl http://127.0.0.1:8001/api/products/2
```

## 2. Configure o backend

As configurações de desenvolvimento devem conter:

```dotenv
PRODUCT_SOURCE_BASE_URL=http://host.docker.internal:8001/api
PRODUCT_SOURCE_TIMEOUT=5
```

O serviço `php` já possui o mapeamento `host.docker.internal:host-gateway`. Não é necessário incluir o `product-source` no Compose desta feature.

## 3. Configure o ambiente de testes

Suba o serviço de banco e prepare um banco exclusivo para testes:

```bash
docker compose up -d database
docker compose exec database createdb -U root cultiva_test
docker compose exec database psql -U root -d cultiva_test -c 'CREATE EXTENSION IF NOT EXISTS postgis'
```

Confirme no `.env` local:

```dotenv
DB_DATABASE=app
PRODUCT_SOURCE_BASE_URL=http://host.docker.internal:8001/api
PRODUCT_SOURCE_TIMEOUT=5
```

O `.env` continua usando `app` para desenvolvimento. O `phpunit.xml` define `APP_ENV=testing`, força `DB_DATABASE=cultiva_test` e remove qualquer `DB_URL` que sobrescreveria esse destino. As demais configurações de conexão e a `APP_KEY` continuam vindo do ambiente local.

`RefreshDatabase` recria tabelas somente em `cultiva_test`. `Tests\TestCase` interrompe a inicialização antes das traits de banco se a conexão resolvida apontar para outro banco. Não substitua o banco de testes por `app`.

## 4. Prepare banco e categorias

```bash
docker compose exec php php artisan migrate --force
```

Resultado esperado: a migration cria cinco categorias, com IDs e nomes definidos em [data-model.md](data-model.md).

## 5. Execute a validação automatizada

Primeiro, durante implementação, execute cada novo teste antes do código correspondente e confirme Red pelo motivo esperado. Depois do Green:

```bash
docker compose exec php php artisan test --filter=GetProductActionTest
docker compose exec php php artisan test --filter=CategoryControllerTest
docker compose exec php php artisan test --filter=OfferController
docker compose exec php php artisan test --filter=AvailableIndexTest
docker compose exec php php artisan test
```

Feature tests devem manter Actions, requests, transformers e PostgreSQL reais. Somente as respostas HTTP do catálogo são simuladas.

## 6. Cenários end-to-end obrigatórios

Use [contracts/openapi.yaml](contracts/openapi.yaml) como contrato dos endpoints e [contracts/product-source.openapi.yaml](contracts/product-source.openapi.yaml) como contrato externo.

### Criação

- Access token de Producer + produto/categoria válidos retorna 201.
- Oferta pertence ao Producer do token, inicia `reserved_quantity=0` e é criada com `status=active`; o status enviado no POST não altera esse valor.
- ID textual como `"0002"` permanece inalterado.
- `producer_id` ou `reserved_quantity` no payload retorna 422.
- Produto externo 404 retorna 422; timeout, 5xx ou payload inválido retorna 503.
- Falha não deixa linha em `offers`.

### Administração do produtor

- Index retorna somente ofertas próprias, inclusive inativas e esgotadas.
- Show/update de oferta alheia retorna 404.
- PATCH consulta o catálogo mesmo sem mudar `source_product_id`.
- Estoque total abaixo do reservado retorna 422 sem alterar nenhum campo.

### Varejista e visibilidade

- Retailer com access token recebe a listagem comercial; Producer recebe suas próprias ofertas na mesma URL.
- A matriz `active|inactive` × `com saldo|esgotada` retorna apenas `active + com saldo`.
- Nenhuma listagem ordena ofertas por menor preço.

### Autorização

- Sem token retorna 401.
- Refresh token nas rotas retorna 403.
- Perfil incorreto retorna 403.

## 7. Stop condition

O planejamento está provado quando todos os testes relevantes e a suíte completa passam no container PHP, os seis endpoints respeitam os contratos e nenhuma Action/repository/interface extra foi adicionada sem uma pressão concreta.

## Paginação com Laravel

`GET /v1/offers?page=1&per_page=15` retorna a coleção nativa do Laravel Resource, com `data`, `links` e `meta`:

```json
{
  "data": [],
  "links": {},
  "meta": {}
}
```

- Defaults: `page=1`, `per_page=15`. Ambos inteiros positivos, máximo 100 itens por página; inválidos retornam 422.
- `links` e `meta` são serializados pelo Laravel a partir do `LengthAwarePaginator`.
- Novos endpoints devem usar `paginate()` na query e `Resource::collection()`; não criar classes ou middleware de paginação.
- A contagem é a do próprio paginator; relações usadas pelo Resource devem usar eager loading.
