# Integração Cultiva ↔ GoLog TMS — Arquitetura, Setup e Uso

Este guia explica como funciona a integração entre o backend do Cultiva e a API da **GoLog TMS**, como preparar o ambiente local do zero, como operar o ciclo completo de entregas e como utilizá-la junto ao ambiente de demonstração criado pelos seeders.

```mermaid
flowchart LR
    A["1. Ambiente e Tenant\n(.env + Docker + Webhook)"] --> B["2. Frota e Escalas\n(GoLogDriverSeeder ou /v1/golog/*)"]
    B --> C["3. Pedido de Entrega\n(Compra via API -> DeliveryOrder)"]
    C --> D["4. Despacho em Lote\n(delivery:dispatch-orders-batch)"]
    D --> E["5. Roteirização VRP\n(delivery:optimize-routes)"]
    E --> F["6. Execução e Sync\n(Webhooks + Horizon / Reconciliação)"]
```

---

## 1. Visão Geral da Arquitetura

| Componente | Responsabilidade | Implementação |
| :--- | :--- | :--- |
| **Cliente HTTP e Sessão** | Faz login na GoLog (`POST /login`), armazena a sessão em cache Redis (`golog_auth_session`, TTL `6600s`) e envia `Authorization: Bearer` + `X-Tenant-Id` obrigatório em toda requisição autenticada. | [`GoLogClient`](../domain/Integrations/GoLog/Clients/GoLogClient.php), [`Config`](../domain/Integrations/GoLog/Config.php) |
| **Provisionamento de Frota** | Cadastra motorista/veículo em etapas (`user` → `driver` → `tractor` → `equipament_group`) até `isProvisioned() === true`. Escalas diárias (`workSchedule`) são consultadas diretamente na GoLog na roteirização (sem persistência de `golog_work_schedule_id`). | [`GoLogDriverController`](../domain/Integrations/GoLog/Http/Controllers/GoLogDriverController.php), [`GoLogDriver`](../domain/Models/Delivery/GoLogDriver.php) |
| **Despacho em Lote** | Agrupa `delivery_orders` `pending` sem shipments por categoria, resolve o tipo de transporte (`REFRIGERADO`, `CLIMATIZADO`, `CARGA SECA`), envia `POST /integration/invoices/json` para o próximo dia útil em `America/Sao_Paulo` e cria 2 [`GoLogShipment`](../domain/Models/DeliveryOrder/GoLogShipment.php) (`COLETA` e `ENTREGA`) com status `PENDENTE`. | [`DispatchDeliveryOrdersBatchAction`](../domain/Integrations/GoLog/Actions/DispatchDeliveryOrdersBatchAction.php) |
| **Roteirização (VRP)** | Coleta shipments `PENDENTE`, motoristas locais provisionados e escalas `ATIVO` na GoLog para o próximo dia útil; chama `POST /api-route-optimization` (`routePriority: TEMPO`) e transiciona os shipments aceitos para `AGUARDANDO_INICIO` e os [`DeliveryOrder`](../domain/Models/DeliveryOrder/DeliveryOrder.php) para `assigned`. | [`OptimizeDeliveryRoutesAction`](../domain/Integrations/GoLog/Actions/OptimizeDeliveryRoutesAction.php) |
| **Webhooks e Reconciliação** | Recebe eventos assinados (`HMAC-SHA256`), persiste idempotentemente em `golog_webhook_events` com lock no Redis e processa via fila (`Horizon`), atualizando [`GoLogShipment`](../domain/Models/DeliveryOrder/GoLogShipment.php) e [`DeliveryOrder`](../domain/Models/DeliveryOrder/DeliveryOrder.php) sob `lockForUpdate()`. | [`GoLogWebhookController`](../domain/Integrations/GoLog/Http/Controllers/GoLogWebhookController.php), [`SyncGoLogShipmentStatusAction`](../domain/Integrations/GoLog/Actions/SyncGoLogShipmentStatusAction.php), [`ReconcileGoLogShipmentsAction`](../domain/Integrations/GoLog/Actions/ReconcileGoLogShipmentsAction.php) |

---

## 2. Preparo do Ambiente

### 2.1. Variáveis de Ambiente (`backend/.env`)

1. Copie [`.env.example`](../.env.example) para `.env` dentro de `backend/` e ajuste as credenciais da GoLog:

   ```dotenv
   GOLOG_BASE_URL=http://147.15.18.21:8081
   GOLOG_EMAIL=cultiva@cultiva.com
   GOLOG_PASSWORD="<senha_admin_golog>"
   GOLOG_TENANT_ID=5647b3d4-f42f-4905-a617-896afa8dccef
   GOLOG_TIMEOUT=60
   GOLOG_WEBHOOK_SECRET="<seu_segredo_hmac>"
   ```

   - `GOLOG_TENANT_ID` é **obrigatório**: toda requisição autenticada envia o header `X-Tenant-Id`.
   - `QUEUE_CONNECTION=redis` e `CACHE_STORE=redis` (padrões do [`.env.example`](../.env.example)) são usados pelo Horizon (processamento de webhooks) e pelo cache do token GoLog (`golog_auth_session`).
   - `PRODUCT_SOURCE_BASE_URL=http://host.docker.internal:8001/api`: necessário apenas se você for criar/editar ofertas ou previsões de safra via API (para comprar ofertas já existentes do seeder, o catálogo externo não é consultado).

> **Observação de segurança:** nunca versione senhas reais ou segredos HMAC em `.env.example`, seeders ou arquivos de documentação.

### 2.2. Containers Docker e Inicialização do Laravel

A partir de `backend/` (veja [`compose.yml`](../compose.yml)):

```bash
cd backend
docker compose up -d
docker compose exec php composer install
docker compose exec php php artisan key:generate
```

Serviços relevantes que passam a rodar:
- `database`: PostgreSQL 17 + PostGIS (banco `app`, porta `5432`)
- `redis`: Redis 8 (cache de sessão GoLog, locks e filas)
- `php`: PHP-FPM
- `nginx`: HTTP na porta `80` (expõe `/v1/*`, incluindo `/v1/golog/webhook`)
- `worker`: executa `php artisan horizon` (consumidor obrigatório para processar eventos de webhook recebidos)

Caso também execute a suíte PHPUnit localmente, crie o banco de testes isolado `cultiva_test` com extensão `postgis` conforme configurado em [`phpunit.xml`](../phpunit.xml):

```bash
docker compose exec database createdb -U root cultiva_test
docker compose exec database psql -U root -d cultiva_test -c 'CREATE EXTENSION IF NOT EXISTS postgis'
```

### 2.3. Tenant, Tipos de Transporte e Perfil de Otimização

#### Opção A — Reusar o Tenant Compartilhado da Cultiva (Recomendado)

Mantenha `GOLOG_TENANT_ID=5647b3d4-f42f-4905-a617-896afa8dccef` no `.env` (já pré-configurado em [`collection/bruno/environments/local.yml`](../collection/bruno/environments/local.yml)). Esse tenant já possui:
- Os 3 tipos de transporte esperados pelo Cultiva (`CARGA SECA`, `CLIMATIZADO`, `REFRIGERADO`)
- Perfil padrão de otimização VRP
- Os 3 motoristas/veículos pré-provisionados que correspondem ao [`GoLogDriverSeeder`](../database/seeders/GoLogDriverSeeder.php)

Se precisar validar ou garantir que os tipos de transporte existam no tenant configurado:

```bash
docker compose exec php php artisan golog:populate-transport-types
```

#### Opção B — Criar um Novo Tenant na GoLog

1. Abra a collection Bruno em `backend/collection/bruno` (ambiente `local`).
2. Execute [`GoLog TMS/01. Auth & Setup/01. Login.yml`](../collection/bruno/GoLog%20TMS/01.%20Auth%20%26%20Setup/01.%20Login.yml) para obter `golog_token`.
3. Execute [`GoLog TMS/01. Auth & Setup/08. Create Cultiva Tenant.yml`](../collection/bruno/GoLog%20TMS/01.%20Auth%20%26%20Setup/08.%20Create%20Cultiva%20Tenant.yml) com `cnpjCpf`, `email`, `adminEmail` e `adminCpf` únicos.
4. Atualize `GOLOG_TENANT_ID` (e, se aplicável, `GOLOG_EMAIL`/`GOLOG_PASSWORD`) em `backend/.env` e limpe o cache da sessão anterior no Redis:

   ```bash
   docker compose exec php php artisan cache:forget golog_auth_session
   docker compose exec php php artisan golog:populate-transport-types
   ```

5. Crie um perfil padrão de otimização para o novo tenant diretamente na GoLog (`POST /optimization-profile` com `"companyId": "<GOLOG_TENANT_ID>"` e `"isDefault": true`).

### 2.4. Configuração de Webhook Público (ngrok / HTTPS)

Para que a GoLog envie atualizações de status em tempo real para `POST /v1/golog/webhook`:

1. Confirme que `worker` (`php artisan horizon`) está rodando:
   ```bash
   docker compose ps worker
   ```
2. Exponha o Nginx local (porta `80`) usando um túnel público, por exemplo `ngrok`:
   ```bash
   ngrok http 80
   ```
3. Defina `GOLOG_WEBHOOK_SECRET` em `backend/.env` e registre a URL pública na GoLog com o comando Artisan [`ConfigureGoLogWebhookCommand`](../domain/Integrations/GoLog/Commands/ConfigureGoLogWebhookCommand.php):
   ```bash
   docker compose exec php php artisan golog:configure-webhook "https://<seu-subdominio>.ngrok-free.app/v1/golog/webhook" --test
   ```
   - Opções disponíveis: `--secret=<segredo>` (sobrescreve `GOLOG_WEBHOOK_SECRET`), `--inactive` (registra inativo) e `--test` (dispara `POST company/{tenantId}/webhook/test` com evento `TEST_PING`).
   - O endpoint local valida `X-GoLog-Signature` (`sha256=<hmac>`), `X-GoLog-Delivery` (UUID idempotente) e `X-GoLog-Event`.

---

## 3. Frota, Motoristas e Escalas de Trabalho

Um [`GoLogDriver`](../domain/Models/Delivery/GoLogDriver.php) está completo (`isProvisioned() === true`) quando possui os 4 identificadores externos persistidos localmente:
`golog_user_id` → `golog_driver_id` → `golog_tractor_id` → `golog_equipament_group_id`.

### Caminho Rápido — `GoLogDriverSeeder` (Tenant Compartilhado)

Ao executar as migrations e seeders, o [`GoLogDriverSeeder`](../database/seeders/GoLogDriverSeeder.php) cria/atualiza localmente os 3 motoristas já provisionados no tenant `5647b3d4-f42f-4905-a617-896afa8dccef` (todos com empresa sediada em Blumenau/SC, `POINT(-49.06611 -26.91944)`):

| Motorista | E-mail de login no Cultiva | Senha | Placa / Veículo |
| :--- | :--- | :--- | :--- |
| Henrique Costa | `henrique.costa@cultiva.com` | `Password123!` | `CST4B19` (Volvo FH 540) |
| Camila Rocha | `camila.rocha@cultiva.com` | `Password123!` | `RCH7D22` (Scania R 450) |
| Thiago Almeida | `thiago.almeida@cultiva.com` | `Password123!` | `ALM3F88` (Actros 2651) |

### Caminho Manual — Endpoints de Provisionamento (`/v1/golog/*`)

Para cadastrar novos motoristas em qualquer tenant, execute os requests em `collection/bruno/API/GoLog Drivers/` em sequência (`getStoppedStep()` permite retomar do ponto onde parou):

| Etapa | Request Bruno | Endpoint Cultiva | O que faz |
| :---: | :--- | :--- | :--- |
| **1** | [`01. Create User.yml`](../collection/bruno/API/GoLog%20Drivers/01.%20Create%20User.yml) | `POST /v1/golog/users` | Cria `User`, `Company`, `Address` (via CEP) e `GoLogDriver` locais + usuário `DRIVER` na GoLog (`golog_user_id`). |
| **2** | [`02. Create Driver.yml`](../collection/bruno/API/GoLog%20Drivers/02.%20Create%20Driver.yml) | `POST /v1/golog/drivers` | Cria o motorista na GoLog (`golog_driver_id`). Requer Bearer token, CNH válida (11 dígitos) e `cnh_expiration` (`YYYY-MM-DD`). |
| **3** | [`03. Create Vehicle.yml`](../collection/bruno/API/GoLog%20Drivers/03.%20Create%20Vehicle.yml) | `POST /v1/golog/vehicles` | Cria o veículo (`tractor`) na GoLog e `GoLogVehicle` local (`golog_tractor_id`). Se `vehicle_type` for rígido (qualquer [`GoLogVehicleType`](../domain/Integrations/GoLog/Enums/GoLogVehicleType.php) exceto `CAVALO_MECANICO`), busca automaticamente o `equipament_group` criado na GoLog. |
| **4** | [`04. Create Equipament Group.yml`](../collection/bruno/API/GoLog%20Drivers/04.%20Create%20Equipament%20Group.yml) | `POST /v1/golog/equipament-groups` | Obrigatório quando o grupo ainda não foi preenchido na etapa 3 (ex.: `vehicle_type` omitido ou `CAVALO_MECANICO`). |

### Escalas de Trabalho (`workSchedule`)

A tabela `golog_drivers` **não** armazena mais `golog_work_schedule_id`. Ao rodar `delivery:optimize-routes`, o backend:
1. Calcula a data alvo como o **próximo dia útil** no fuso `America/Sao_Paulo` (`CarbonImmutable::now('America/Sao_Paulo')->nextWeekday()->toDateString()`).
2. Consulta `GET /workSchedule/getByStatus?status=ATIVO` na GoLog.
3. Filtra apenas escalas com `scheduleDate` igual à data alvo e pertencentes aos motoristas provisionados no banco local.

Portanto, **antes de rodar `delivery:optimize-routes`**, é necessário existir ao menos uma escala `ATIVO` na GoLog para essa data. Você pode criá-la:
- Pela API do Cultiva autenticado como o motorista (`POST /v1/golog/work-schedules` — [`05. Create Work Schedule.yml`](../collection/bruno/API/GoLog%20Drivers/05.%20Create%20Work%20Schedule.yml)), informando `schedule_date` (`YYYY-MM-DD`), `start_time` (`HH:mm`) e `end_time` (`HH:mm`); **ou**
- Diretamente na GoLog via [`GoLog TMS/02. Fleet & Drivers/07. Create Work Schedule.yml`](../collection/bruno/GoLog%20TMS/02.%20Fleet%20&%20Drivers/07.%20Create%20Work%20Schedule.yml).

---

## 4. Como Usar com o Ambiente de Demo Criado pelo Seeder

### 4.1. Populando o Banco Demo

Dentro de `backend/`:

```bash
docker compose exec php php artisan migrate --seed
# ou, para zerar o banco app e recriar tudo do zero:
docker compose exec php php artisan migrate:fresh --seed
```

O [`DatabaseSeeder`](../database/seeders/DatabaseSeeder.php) executa em ordem:
1. [`DemoDataSeeder`](../database/seeders/DemoDataSeeder.php) *(apenas fora de `production`; idempotente — remove apenas dados de demonstração existentes e preserva outros registros)*:
   - **25 produtores**: `produtor1@gmail.com` até `produtor25@gmail.com`
   - **50 varejistas**: `varejista1@gmail.com` até `varejista50@gmail.com`
   - **Senha compartilhada** de produtores e varejistas demo: `teste@123` (todos já criados com `is_verified = true` e `is_active = true`, cada um com `Company` e `Address`)
   - **200 ofertas ativas** (`8` por produtor, `total_quantity = 100`), distribuídas pelas 5 categorias suportadas (`Frutas`, `Legumes`, `Verduras`, `Tubérculos e raízes`, `Grãos e cereais`)
   - Previsões de safra, itens de wishlist e **250 compras históricas**
2. [`GoLogDriverSeeder`](../database/seeders/GoLogDriverSeeder.php):
   - Os **3 motoristas pré-provisionados** da Cultiva (`henrique.costa@cultiva.com`, `camila.rocha@cultiva.com`, `thiago.almeida@cultiva.com`, senha `Password123!`).

### 4.2. Cuidados Importantes ao Usar o Ambiente Demo na GoLog

1. **As 250 compras criadas pelo `DemoDataSeeder` NÃO geram `delivery_orders`**:
   O `DemoDataSeeder` insere em `purchases` via `Purchase::query()->create(...)` dentro de um seeder com `WithoutModelEvents`, sem disparar o evento `PurchaseCreated`. Logo, imediatamente após `db:seed`, a tabela `delivery_orders` estará vazia. Para gerar pedidos despacháveis para a GoLog, **realize uma nova compra via endpoint `POST /v1/offers/{offer}/purchase`** (usando um varejista demo e uma das 200 ofertas ativas).
2. **Coordenadas geradas pela `AddressFactory` no ambiente demo**:
   Os endereços gerados pelo `DemoDataSeeder` usam [`AddressFactory`](../database/factories/AddressFactory.php), que preenche cidade, estado e `coordinate` (`POINT(longitude latitude)`) aleatoriamente pelo Faker `en_US`. Coordenadas em outro continente ou no oceano fazem o otimizador VRP da GoLog rejeitar a parada (colocando os shipments em `skipped`) e impedem testes realistas de geofencing com os motoristas de **Blumenau/SC** (`-26.91944, -49.06611`).
   - **Para testar VRP/rotas/webhooks de ponta a ponta com os dados demo:** antes de efetuar a compra (ou no `DeliveryAddress` criado), ajuste para coordenadas válidas em Blumenau/SC (ex.: perto de `-26.91944, -49.06611`) os registros na tabela `addresses` do produtor dono da oferta escolhida e do varejista comprador (ex.: `varejista1@gmail.com`).

### 4.3. Roteiro Prático Passo a Passo com o Seeder

1. **Autentique como varejista demo** (`POST /v1/auth/login` via Bruno [`API/auth/Login.yml`](../collection/bruno/API/auth/Login.yml)):
   ```json
   {
     "email": "varejista1@gmail.com",
     "password": "teste@123"
   }
   ```
   Como o usuário já possui `is_verified = true` e as ofertas demo já existem no banco, não é necessário fazer upload de documento nem subir o serviço `product-source`.

2. **Liste e escolha uma oferta ativa** (`GET /v1/offers?page=1&per_page=15` — [`API/Offers/List Available Offers.yml`](../collection/bruno/API/Offers/List%20Available%20Offers.yml)) e anote o `id` da oferta (e, se for rodar VRP/geofencing real na GoLog, garanta coordenadas válidas de Blumenau/SC nos endereços das empresas envolvidas).

3. **Crie a compra para gerar um `DeliveryOrder`** (`POST /v1/offers/{offer_id}/purchase` — [`API/Purchases/Buy Offer.yml`](../collection/bruno/API/Purchases/Buy%20Offer.yml)):
   ```json
   {
     "quantity": 2
   }
   ```
   [`CreatePurchaseAction`](../domain/Models/Purchase/Actions/CreatePurchaseAction.php) dispara `PurchaseCreated`, e [`CreateDeliveryOrderListener`](../domain/Models/DeliveryOrder/Listeners/CreateDeliveryOrderListener.php) cria sincronamente o registro em `delivery_orders` com `status = pending` e snapshots em `delivery_addresses`.

4. **Garanta ao menos uma escala `ATIVO` para o próximo dia útil** para um dos motoristas do `GoLogDriverSeeder`:
   - Faça login como `henrique.costa@cultiva.com` (`Password123!`) e chame `POST /v1/golog/work-schedules` com a data do próximo dia útil em `America/Sao_Paulo`:
     ```json
     {
       "schedule_date": "YYYY-MM-DD",
       "start_time": "08:00",
       "end_time": "18:00"
     }
     ```

5. **Execute o despacho e a roteirização** conforme descrito na Seção 5 abaixo.

---

## 5. Como Usar a Integração (Ciclo Operacional Completo)

### 5.1. Passo 1 — Despacho em Lote (`delivery:dispatch-orders-batch`)

```bash
docker compose exec php php artisan delivery:dispatch-orders-batch
```

O que [`DispatchDeliveryOrdersBatchAction`](../domain/Integrations/GoLog/Actions/DispatchDeliveryOrdersBatchAction.php) executa:
- Busca `delivery_orders` com `status = pending` que ainda não possuem `golog_shipments`.
- Extrai `latitude` e `longitude` PostGIS de `delivery_addresses` (`pickupAddress` e `dropoffAddress`).
- Agrupa pedidos por categoria e mapeia o tipo de transporte em [`ResolveTransportTypeAction`](../domain/Integrations/GoLog/Actions/ResolveTransportTypeAction.php):
  - `Frutas`, `Verduras` → `REFRIGERADO`
  - `Legumes` → `CLIMATIZADO`
  - `Tubérculos e raízes`, `Grãos e cereais` → `CARGA SECA`
- Envia um lote `POST /integration/invoices/json` por categoria com duas operações por pedido para o próximo dia útil em `America/Sao_Paulo`:
  - `COLETA` (`DO-{id}-COL`, janela prevista às 12h locais)
  - `ENTREGA` (`DO-{id}-ENT`, janela prevista às 15h locais)
- Cria 2 registros em `golog_shipments` (`COLETA` e `ENTREGA`) com `status = PENDENTE`.

### 5.2. Passo 2 — Otimização de Rotas VRP (`delivery:optimize-routes`)

```bash
docker compose exec php php artisan delivery:optimize-routes
```

O que [`OptimizeDeliveryRoutesAction`](../domain/Integrations/GoLog/Actions/OptimizeDeliveryRoutesAction.php) executa:
- Busca todos os `golog_shipments` em `PENDENTE` e os motoristas locais onde `isProvisioned() === true`.
- Busca na GoLog (`GET /workSchedule/getByStatus?status=ATIVO`) as escalas da data alvo (próximo dia útil em `America/Sao_Paulo`) pertencentes aos motoristas provisionados.
- Envia `POST /api-route-optimization` com `routePriority: "TEMPO"`.
- Para cada shipment aceito na rota (excluindo `skipped`), chama [`SyncGoLogShipmentStatusAction`](../domain/Integrations/GoLog/Actions/SyncGoLogShipmentStatusAction.php):
  - `GoLogShipment`: `PENDENTE` → `AGUARDANDO_INICIO`
  - `DeliveryOrder`: `pending` → `assigned`
- Shipments retornados em `skipped` permanecem como `PENDENTE` (e seu `DeliveryOrder` continua `pending`).

### 5.3. Passo 3 — Acompanhamento via Webhooks e Transições de Status

Durante a execução na GoLog (app/motorista, geofencing e API GoLog), os webhooks chegam em `POST /v1/golog/webhook`, são gravados em `golog_webhook_events` e processados por [`ProcessGoLogWebhookJob`](../domain/Integrations/GoLog/Jobs/ProcessGoLogWebhookJob.php) → [`HandleGoLogWebhookAction`](../domain/Integrations/GoLog/Actions/HandleGoLogWebhookAction.php) → [`SyncGoLogShipmentStatusAction`](../domain/Integrations/GoLog/Actions/SyncGoLogShipmentStatusAction.php):

| Evento Webhook (`GoLogWebhookEventType`) | Status `GoLogShipment` | Efeito em `DeliveryOrder` |
| :--- | :--- | :--- |
| `SHIPMENT_CREATED`, `SHIPMENT_UNPLANNED` | `PENDENTE` | Se estava `assigned`, volta para `pending`. |
| `SHIPMENT_PLANNED` | `AGUARDANDO_INICIO` | Se estava `pending`, avança para `assigned`. |
| `SHIPMENT_STARTED`, `CHECK_IN_GEOFENCE` | `INICIADO` | Se estava `pending` ou `assigned`, avança para `in_transit`. |
| `SHIPMENT_DELIVERED` (`COLETA`) | `FINALIZADO` | Preenche `picked_up_at` e coloca em `in_transit` (se ainda não `delivered`). |
| `SHIPMENT_DELIVERED` (`ENTREGA`) | `FINALIZADO` | Preenche `delivered_at` e coloca em `delivered`. |
| `SHIPMENT_OCCURRENCE`, `TEST_PING` | *(sem alteração)* | Persistido em `golog_webhook_events` e marcado com `processed_at`. |

### 5.4. Passo 4 — Reconciliação Ativa (`golog:reconcile-shipments`)

Caso algum webhook seja perdido ou um shipment seja alterado diretamente na GoLog sem entrega de webhook, execute a reconciliação ativa:

```bash
docker compose exec php php artisan golog:reconcile-shipments
```

- Consulta `GET /shipment/{golog_shipment_id}` na GoLog para todos os `golog_shipments` em estados não-terminais (`PENDENTE`, `AGUARDANDO_INICIO`, `INICIADO`) e aplica [`SyncGoLogShipmentStatusAction`](../domain/Integrations/GoLog/Actions/SyncGoLogShipmentStatusAction.php) (inclusive corrigindo `delivery_orders` dessincronizados).
- Esse comando também está agendado diariamente para `03:00` em [`routes/console.php`](../routes/console.php).

---

## 6. Inspeção, Cache e Troubleshooting

### Logs e Banco de Dados

- **Requisições de saída à GoLog**: gravadas em `backend/storage/logs/laravel.log` (`endpoint`, `status`, `body`).
- **Processamento assíncrono de webhooks**: acompanhe o Horizon via `docker compose logs -f worker`.
- **Tabelas locais de acompanhamento**: `delivery_orders`, `delivery_addresses`, `golog_drivers`, `golog_vehicles`, `golog_shipments`, `golog_webhook_events`.
- **Inspeção direta na GoLog (Bruno)**:
  - [`GoLog TMS/02. Fleet & Drivers/08. Get Active Work Schedules.yml`](../collection/bruno/GoLog%20TMS/02.%20Fleet%20&%20Drivers/08.%20Get%20Active%20Work%20Schedules.yml)
  - [`GoLog TMS/04. Route Optimization/02. List Transports.yml`](../collection/bruno/GoLog%20TMS/04.%20Route%20Optimization/02.%20List%20Transports.yml)
  - [`GoLog TMS/04. Route Optimization/04. List Route Stops.yml`](../collection/bruno/GoLog%20TMS/04.%20Route%20Optimization/04.%20List%20Route%20Stops.yml)

### Problemas Comuns

| Sintoma / Mensagem | Causa Provável | Resolução |
| :--- | :--- | :--- |
| `401` / erro de permissão na GoLog após trocar `.env` | Token antigo ainda no cache Redis (`golog_auth_session`, TTL `6600s`). | Rode `docker compose exec php php artisan cache:forget golog_auth_session`. |
| `No active GoLog work schedules found for date [...]` | Não há escala `ATIVO` na GoLog para os motoristas provisionados na data do próximo dia útil em `America/Sao_Paulo`. | Crie a escala para essa data via `POST /v1/golog/work-schedules` ou diretamente na GoLog. |
| `Shipments skipped (remained PENDENTE)` na roteirização | Coordenadas inválidas/distantes (comum se usar endereço aleatório do `DemoDataSeeder`), veículo sem capacidade ou janela incompatível. | Ajuste as coordenadas da coleta/entrega para uma região viável (ex.: Blumenau/SC) e rode `delivery:optimize-routes` novamente. |
| `401 Invalid webhook signature` no webhook | `GOLOG_WEBHOOK_SECRET` no `.env` diverge do segredo configurado na GoLog. | Atualize o `.env` e reexecute `php artisan golog:configure-webhook "<url>" --test`. |
| Webhook retorna `200`, mas `delivery_orders` não atualiza | Container `worker` (`php artisan horizon`) parado (`processed_at` fica `null` em `golog_webhook_events`). | Suba o worker com `docker compose up -d worker`. |
