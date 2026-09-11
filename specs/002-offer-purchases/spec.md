# Feature Specification: Compras de Ofertas

**Feature Branch**: `N/A (nenhuma branch criada; não há hook before_specify configurado)`

**Created**: 2026-09-10

**Status**: Draft

**Input**: Permitir que varejistas comprem quantidades parciais ou totais de ofertas disponíveis, registrando cada compra separadamente da oferta, preservando as condições comerciais e disponibilizando consultas paginadas de compras e vendas.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Comprar uma oferta disponível (Priority: P1)

Como varejista autenticado, quero comprar uma quantidade de uma oferta disponível para adquirir produtos de um produtor sem consumir mais estoque do que existe.

**Why this priority**: A compra é o fluxo principal que transforma uma oferta publicada em uma transação comercial rastreável.

**Independent Test**: Um varejista compra parte do estoque de uma oferta ativa e disponível; uma compra é registrada com os dados comerciais da operação e a oferta passa a mostrar somente o saldo restante.

**Acceptance Scenarios**:

1. **Given** uma oferta ativa com 100 unidades disponíveis e preço unitário de R$ 10, **When** um varejista compra 30 unidades, **Then** uma compra persistida relaciona varejista, produtor e oferta, registra quantidade 30, preço unitário R$ 10 e valor total R$ 300, e a oferta passa a ter 70 unidades disponíveis.
2. **Given** uma oferta ativa com 30 unidades disponíveis, **When** um varejista compra exatamente 30 unidades, **Then** a compra é registrada, a disponibilidade passa a zero e a oferta é marcada como inativa.
3. **Given** uma oferta com compras anteriores parciais, **When** outro varejista realiza uma compra válida, **Then** a quantidade comprometida acumulada e o saldo disponível refletem todas as compras sem alterar os registros históricos anteriores.
4. **Given** uma oferta ativa com preço unitário definido, **When** o cliente envia quantidade válida e valores comerciais diferentes ou adicionais, **Then** o sistema calcula o preço e o valor total usando a oferta no momento da compra, sem confiar nos valores enviados.

### User Story 2 - Impedir compras inválidas e overselling (Priority: P1)

Como participante da plataforma, quero que cada compra respeite perfil, estado e estoque para que nenhuma operação indevida ou concorrente gere saldo negativo.

**Why this priority**: Uma inconsistência de estoque ou autorização compromete diretamente a confiança comercial entre produtor e varejista.

**Independent Test**: Executar tentativas inválidas, perfis não autorizados e duas compras simultâneas contra o mesmo saldo; cada cenário deve preservar as invariantes da oferta.

**Acceptance Scenarios**:

1. **Given** uma oferta com 10 unidades disponíveis, **When** dois varejistas tentam comprar 10 unidades simultaneamente, **Then** no máximo uma compra é confirmada, a outra é rejeitada por indisponibilidade e nunca são registradas 20 unidades compradas.
2. **Given** uma oferta inativa, esgotada ou sem disponibilidade, **When** um varejista tenta comprar, **Then** a operação é rejeitada e nenhuma compra ou alteração de estoque é persistida.
3. **Given** uma quantidade zero, negativa, fracionária ou maior que a disponibilidade atual, **When** qualquer usuário tenta comprar, **Then** a operação é rejeitada como entrada ou regra de negócio inválida.
4. **Given** um usuário producer ou delivery autenticado, **When** tenta comprar, **Then** o acesso é negado e nenhuma compra é criada.
5. **Given** uma oferta inexistente, **When** um varejista tenta comprar por seu identificador, **Then** o recurso é tratado como não encontrado e nenhuma compra é criada.
6. **Given** uma falha durante o registro da compra ou atualização da oferta, **When** a operação é interrompida, **Then** nenhuma das duas alterações permanece persistida.

### User Story 3 - Consultar minhas compras (Priority: P2)

Como varejista autenticado, quero consultar minhas compras para acompanhar o que adquiri, de quem, em qual oferta e sob quais condições comerciais.

**Why this priority**: O histórico permite ao varejista acompanhar transações sem reconstruir dados a partir do estado mutável das ofertas.

**Independent Test**: Criar compras para dois varejistas e consultar a listagem de cada um; cada resposta deve conter somente as compras do varejista autenticado e respeitar paginação.

**Acceptance Scenarios**:

1. **Given** compras pertencentes a varejistas diferentes, **When** um varejista consulta `GET /v1/purchases`, **Then** recebe somente suas próprias compras.
2. **Given** mais compras do que o tamanho de uma página, **When** o varejista informa `page` e `per_page` válidos, **Then** recebe a página solicitada e os metadados de paginação padronizados do sistema.
3. **Given** uma compra cujo preço da oferta foi alterado depois, **When** o varejista consulta seu histórico, **Then** a compra mantém o preço unitário e o valor total registrados no momento da compra.

### User Story 4 - Consultar minhas vendas (Priority: P2)

Como produtor autenticado, quero consultar as compras feitas sobre minhas ofertas para acompanhar minhas vendas sem acessar transações de outros produtores.

**Why this priority**: A visão do produtor é necessária para que a oferta comprada tenha rastreabilidade comercial dos dois lados.

**Independent Test**: Criar compras sobre ofertas de dois produtores e consultar `GET /v1/sales` como cada produtor; cada resposta deve incluir somente as vendas de suas ofertas e ser paginada.

**Acceptance Scenarios**:

1. **Given** compras sobre ofertas de produtores diferentes, **When** um produtor consulta `GET /v1/sales`, **Then** recebe somente compras originadas de suas ofertas.
2. **Given** mais vendas do que o tamanho de uma página, **When** o produtor informa `page` e `per_page` válidos, **Then** recebe a página solicitada e os metadados de paginação padronizados do sistema.
3. **Given** uma venda com dados snapshotados, **When** a oferta original é alterada depois, **Then** a venda continua apresentando as condições comerciais da transação original.

### Edge Cases

- A soma das quantidades compradas deve permanecer entre zero e a quantidade total da oferta; a disponibilidade deve ser sempre quantidade total menos quantidade comprometida.
- Uma compra que consuma o último item deve inativar a oferta na mesma operação; não deve existir janela persistida em que ela continue disponível.
- Uma oferta ativa sem saldo não pode ser comprada nem aparecer no catálogo de varejistas.
- Uma oferta inativa com saldo não pode ser comprada até que uma alteração válida de estoque faça o backend recalculá-la como `active`.
- Valores comerciais enviados pelo cliente não são usados para calcular a compra.
- Usuário não autenticado recebe a resposta padrão de autenticação e não altera dados.
- Delivery não participa de compras, compras próprias ou vendas.
- Falhas de persistência não podem deixar uma compra sem atualização correspondente da oferta, nem consumir estoque sem uma compra correspondente.
- O histórico permanece disponível por relacionamento mesmo quando a oferta fica inativa; não há exclusão física ou soft delete de compras nesta versão.
- Página inválida ou `per_page` fora do limite existente retorna 422; páginas além da última retornam lista vazia com metadados coerentes.

## Requirements *(mandatory)*

### Offer Publication Lifecycle

Uma Offer criada pelo producer com quantidade total positiva MUST ser criada
automaticamente com status `active` e aparecer no catálogo de Offers
disponíveis. O cliente não envia `status` na criação; esse estado é definido
pelo backend. Após compras consumirem toda a quantidade, a Offer MUST ser
marcada como `inactive` e deixar de aceitar compras. O status é recalculado
automaticamente pelo backend quando a quantidade total ou comprometida muda;
ele não faz parte do payload de criação ou atualização.

### Offer Details Visibility

`GET /v1/offers/{offer}` MUST be accessible to an authenticated producer for
their own Offer and to an authenticated retailer for an active Offer with
available quantity. A retailer MUST receive not found for inactive, exhausted
or unavailable Offers, and a producer MUST not access another producer's
Offer. Delivery users remain forbidden from this flow.

### Authentication and Authorization Responses

Requests without an authenticated or valid access token MUST return HTTP 401
with `Unauthenticated.`. Requests made with a valid token whose profile does
not have permission for the route MUST return HTTP 403 with
`You do not have permission to access this resource.`. API clients use one
shared `access_token` variable so the same authenticated token can exercise
both successful and forbidden role scenarios.

### Functional Requirements

- **FR-001**: O sistema MUST permitir que somente um usuário autenticado com perfil retailer compre uma Offer.
- **FR-002**: A compra MUST referenciar uma Offer existente e MUST aceitar uma quantidade inteira maior que zero.
- **FR-003**: A Offer MUST estar ativa e possuir disponibilidade positiva no momento da confirmação da compra.
- **FR-004**: A quantidade solicitada MUST ser menor ou igual à disponibilidade atual; quantidade acima do saldo, zero, negativa ou fracionária MUST ser rejeitada.
- **FR-005**: Cada operação confirmada MUST criar um registro persistente próprio de Purchase, separado da Offer.
- **FR-006**: Cada Purchase MUST relacionar o retailer comprador, o producer vendedor e a Offer de origem.
- **FR-007**: Cada Purchase MUST registrar snapshot dos dados essenciais da negociação: identificador e nome do produto, quantidade adquirida, preço unitário e valor total calculado pelo backend, além da data da operação.
- **FR-008**: O valor total MUST ser calculado a partir do preço unitário válido da Offer no momento da compra; preço, total ou produtor enviados pelo cliente MUST ser ignorados ou rejeitados.
- **FR-009**: A operação MUST atualizar a quantidade comprometida já existente na Offer e manter a disponibilidade igual à quantidade total menos essa quantidade comprometida.
- **FR-010**: O sistema MUST manter as invariantes de que a quantidade comprometida é não negativa e não ultrapassa a quantidade total, e de que a disponibilidade nunca é negativa.
- **FR-011**: Quando a quantidade comprometida atingir a quantidade total, a Offer MUST ser marcada automaticamente como inactive na mesma operação que registra a última Purchase.
- **FR-012**: Offer inactive, esgotada ou sem disponibilidade MUST rejeitar novas compras e MUST permanecer preservada para histórico.
- **FR-013**: A atualização da Offer e a criação da Purchase MUST ser atômicas; uma falha em qualquer parte MUST desfazer a operação inteira.
- **FR-014**: Compras concorrentes sobre a mesma Offer MUST ser serializadas ou condicionadas de modo que o estoque confirmado nunca exceda a disponibilidade e somente operações que caibam no saldo sejam confirmadas.
- **FR-015**: O sistema MUST manter inalterados os snapshots de Purchases já confirmadas quando preço, nome, categoria, status ou outras informações da Offer forem alterados posteriormente.
- **FR-016**: O sistema MUST expor uma operação de compra a partir de uma Offer, com payload mínimo contendo somente `quantity`, em rota consistente com o recurso de ofertas; a operação deve retornar a Purchase criada no formato de recurso da API.
- **FR-017**: O sistema MUST permitir que um retailer consulte suas compras por `GET /v1/purchases`, retornando somente registros vinculados ao retailer autenticado.
- **FR-018**: O sistema MUST permitir que um producer consulte suas vendas por `GET /v1/sales`, retornando somente Purchases originadas de Offers pertencentes ao producer autenticado.
- **FR-019**: As listagens de compras e vendas MUST ser paginadas com `page` default 1, `per_page` default 15 e máximo 100, rejeitando valores inválidos com 422.
- **FR-020**: As listagens paginadas MUST usar `paginate()` e `Resource::collection()`, retornando `data`, `links` e `meta` no formato nativo do Laravel.
- **FR-021**: Os itens de compras e vendas MUST apresentar informação suficiente para identificar a transação, oferta, produto, contraparte, quantidade, preço unitário snapshotado, valor total e data da compra.
- **FR-022**: Producer, delivery, retailer diferente do comprador e producer diferente do vendedor MUST receber acesso negado ou conjunto vazio conforme a rota e o padrão de autorização existente, sem exposição de dados de terceiros.
- **FR-023**: Usuário não autenticado MUST ser impedido de comprar ou consultar compras e vendas.
- **FR-024**: A feature MUST não implementar pagamento, entrega, cancelamento, confirmação, histórico de status ou alteração de `reserved_quantity` por operações que não sejam compras confirmadas.

### Key Entities

- **Offer**: Oferta original do produtor. Mantém quantidade total, quantidade comprometida usada pela regra atual de disponibilidade, preço, produto snapshotado e status active/inactive.
- **Purchase**: Registro permanente de uma compra de uma quantidade de uma única Offer por um retailer. Relaciona comprador, vendedor e oferta e contém snapshots comerciais imutáveis para o histórico.
- **Retailer**: Perfil comprador derivado da identidade autenticada. Pode possuir várias Purchases.
- **Producer**: Perfil vendedor derivado da Offer. Pode ter várias Purchases originadas de suas Offers.

### Scope Boundaries

- Inclui compra de quantidade inteira de uma Offer, registro histórico, atualização de disponibilidade, inativação automática ao esgotar e consultas paginadas de compras e vendas.
- Inclui proteção de autorização, atomicidade, invariantes de estoque e concorrência para a confirmação da compra.
- Não inclui pagamento real, reserva separada, cancelamento, devolução, confirmação posterior, entrega, logística ou histórico de estados.
- Não inclui soft delete técnico ou exclusão de Purchases; Offers e Purchases permanecem consultáveis para histórico conforme as permissões.
- Não inclui compra por producer ou delivery.
- Não cria uma entidade agregadora com múltiplos itens; nesta versão, cada Purchase corresponde a uma única Offer.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Em 100% das compras válidas testadas, existe exatamente um registro de Purchase e a disponibilidade da Offer diminui exatamente pela quantidade adquirida.
- **SC-002**: Em 100% dos cenários testados de compra total, a Offer fica inativa e nenhuma nova compra é aceita depois do esgotamento.
- **SC-003**: Em 100% dos testes de concorrência sobre o mesmo saldo, a soma das quantidades confirmadas não excede a quantidade total da Offer e nenhuma disponibilidade negativa é observada.
- **SC-004**: Em 100% das compras confirmadas, preço unitário e valor total históricos permanecem iguais aos valores válidos no momento da operação, mesmo após edição posterior da Offer.
- **SC-005**: Em 100% dos testes de autorização, somente retailers compram, retailers veem apenas suas compras e producers veem apenas vendas de suas próprias Offers.
- **SC-006**: Em 100% das consultas paginadas válidas de compras e vendas, a resposta contém somente a página solicitada e os campos `data`, `links` e `meta` do Resource paginado, sem carregar ou retornar a coleção completa.
- **SC-007**: Em pelo menos 95% das consultas sob carga operacional normal, o usuário recebe a página de compras ou vendas em até 2 segundos.
- **SC-008**: Pelo menos 90% dos usuários de teste conseguem identificar, a partir da resposta da compra, quantidade, preço unitário, total, produto e contraparte sem consultar o estado atual da Offer.

## Assumptions

- A entidade canônica desta versão será chamada `Purchase`, porque representa a aquisição feita pelo retailer; a mesma entidade é exibida como venda quando consultada pelo producer. Não há conceito existente no projeto que justifique `Order`, `Sale` ou `Transaction`.
- A operação será exposta como `POST /v1/offers/{offer}/purchase`; as consultas serão `GET /v1/purchases` e `GET /v1/sales`, mantendo a convenção de versionamento e recursos já usada pelo backend.
- O campo existente `reserved_quantity` continuará sendo o contador de quantidade comprometida usado pelo domínio atual para calcular disponibilidade. Nesta feature ele será incrementado por compras confirmadas; uma reserva independente ou a renomeação do campo fica fora do escopo.
- O estado de uma Purchase será implicitamente confirmado quando o registro for criado; um campo de status sem transições implementadas não será adicionado nesta versão.
- O snapshot mínimo inclui `source_product_id`, `product_name`, `quantity`, `unit_price` e `total_price`; Offer, Retailer e Producer também permanecem relacionados para navegação e autorização.
- A moeda permanece real brasileiro, os preços continuam com duas casas decimais e as quantidades continuam inteiras, conforme a feature de Offers.
- A paginação reutiliza exatamente os defaults, limite e formato já adotados por `GET /v1/offers`.
- A proteção contra concorrência será definida no planejamento usando a estratégia transacional compatível com a persistência atual, com bloqueio ou atualização condicional suficiente para impedir overselling.
- Falha de autenticação e autorização seguirá os códigos e middleware já existentes; falhas esperadas de domínio usarão o contrato de exceções da aplicação.
