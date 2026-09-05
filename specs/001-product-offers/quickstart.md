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

Suba o banco local:

```bash
docker compose up -d database
```

Confirme no `.env` local:

```dotenv
DB_DATABASE=app
PRODUCT_SOURCE_BASE_URL=http://host.docker.internal:8001/api
PRODUCT_SOURCE_TIMEOUT=5
```

O `phpunit.xml` define `APP_ENV=testing`; sem um arquivo específico de testes, Laravel reutiliza o `.env` local e sua `APP_KEY`.

`RefreshDatabase` pode recriar tabelas e apagar dados de `app`. Este roteiro assume que o banco local é descartável e nunca aponta para produção ou dados compartilhados.

## 4. Prepare banco e categorias

```bash
docker compose exec php php artisan migrate --force
docker compose exec php php artisan db:seed --class=CategorySeeder --force
```

Resultado esperado: cinco categorias, com IDs e nomes definidos em [data-model.md](data-model.md).

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
- Oferta pertence ao Producer do token, inicia `reserved_quantity=0` e usa `status=inactive` se omitido.
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

- Somente Retailer com access token acessa a listagem comercial.
- A matriz `active|inactive` × `com saldo|esgotada` retorna apenas `active + com saldo`.
- Nenhuma listagem ordena ofertas por menor preço.

### Autorização

- Sem token retorna 401.
- Refresh token nas rotas retorna 403.
- Perfil incorreto retorna 403.

## 7. Stop condition

O planejamento está provado quando todos os testes relevantes e a suíte completa passam no container PHP, os seis endpoints respeitam os contratos e nenhuma Action/repository/interface extra foi adicionada sem uma pressão concreta.
