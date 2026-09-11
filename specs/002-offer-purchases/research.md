# Phase 0 Research: Compras de Ofertas

## 1. Nome e granularidade da transação

**Decision**: Usar `Purchase` como entidade persistente e considerar cada operação uma compra de uma única Offer.

**Rationale**: O código não possui `Order`, `Sale`, `Transaction` ou equivalente. `Purchase` descreve diretamente a intenção confirmada do retailer sem antecipar pagamento, entrega ou pedido com múltiplos itens. A visão do producer será chamada de vendas, mas não criará uma segunda entidade.

**Alternatives considered**:

- `Order`: rejeitado porque a primeira versão não possui ciclo de fulfillment nem múltiplos itens.
- `Sale`: rejeitado porque descreve a perspectiva do producer, enquanto o fato registrado é a compra.
- `Transaction`: rejeitado por ser genérico demais e poder sugerir pagamento financeiro.
- `OrderItem`: rejeitado porque não existe Order pai nem compra multi-item no escopo.

## 2. Estoque e compatibilidade com Offer

**Decision**: Reutilizar `offers.reserved_quantity` como contador de quantidade já comprometida por compras nesta versão. A disponibilidade existente continua sendo `total_quantity - reserved_quantity`.

**Rationale**: Offer já possui o campo, constraint `reserved_quantity <= total_quantity`, accessor `available_quantity`, filtro do catálogo e transformer usando essa convenção. Criar um segundo contador agora duplicaria a fonte de verdade e exigiria alterar todos os consumidores existentes. A especificação delimita que reserva independente, pagamento e cancelamento ainda não existem; portanto o contador representa o estoque comprometido pela compra confirmada.

**Alternatives considered**:

- Adicionar `purchased_quantity`: rejeitado nesta versão por duplicar a disponibilidade atual e exigir decidir como combinar dois contadores.
- Calcular sempre `SUM(purchases.quantity)`: rejeitado como única fonte porque aumenta o custo da consulta de catálogo e não protege sozinho a atualização concorrente; o contador existente já é a coluna de disponibilidade.
- Soft delete em Offer: rejeitado; o projeto usa `status` para visibilidade comercial e mantém o registro.

## 3. Concorrência

**Decision**: Confirmar a compra dentro de uma transação e bloquear a linha da Offer antes de validar saldo e incrementar `reserved_quantity`. A implementação deve reler a Offer bloqueada, criar Purchase, atualizar a Offer e confirmar tudo no mesmo commit.

**Rationale**: Dois requests que disputam a mesma Offer precisam observar o saldo serializado. O lock por linha mantém a solução pequena e combina com Eloquent/PostgreSQL já usado; o restante do sistema continua concorrente normalmente. A constraint existente fornece uma barreira adicional contra valor acima do total, mas não substitui a validação de quantidade disponível.

**Alternatives considered**:

- Somente ler e depois salvar: rejeitado por permitir lost update/overselling.
- Somente `SUM(purchases.quantity)`: rejeitado porque duas transações poderiam validar o mesmo saldo antes de inserir.
- Lock global: rejeitado por reduzir concorrência sem necessidade.
- Fila assíncrona: rejeitada porque a API precisa retornar a compra confirmada e o escopo não inclui processamento posterior.

## 4. Snapshot histórico

**Decision**: Purchase armazenará `offer_id`, `retailer_id`, `producer_id`, `source_product_id`, `product_name`, `quantity`, `unit_price` e `total_price`, além de timestamps.

**Rationale**: Relações preservam navegação e autorização; campos comerciais e do produto preservam a negociação mesmo que Offer seja editada. `total_price` será calculado no backend com precisão decimal a partir do preço da Offer e da quantidade validada.

**Alternatives considered**:

- Reconstruir preço/nome pela Offer: rejeitado porque alterações posteriores corromperiam o histórico.
- Copiar todos os campos da Offer: rejeitado por duplicação sem valor nesta etapa.
- Receber total do cliente: rejeitado por permitir adulteração do valor comercial.

## 5. Autorização e endpoints

**Decision**: `POST /v1/offers/{offer}/purchase` para comprar; `GET /v1/purchases` para retailer; `GET /v1/sales` para producer. Todas usam Sanctum + ability `access`; perfil e ownership são validados no fluxo correspondente.

**Rationale**: A compra nasce de uma Offer, enquanto as duas consultas são coleções de Purchase com escopos diferentes. O padrão existente já usa `/v1`, middleware de ability e endpoints paginados com `page`/`per_page`.

**Alternatives considered**:

- `POST /v1/purchases` com `offer_id`: rejeitado porque a operação é naturalmente subordinada à Offer.
- Um único `GET /v1/purchases` para os dois perfis: rejeitado porque esconderia duas intenções e exigiria filtro implícito menos claro.

## 6. Paginação e resposta

**Decision**: Reutilizar `ListPurchasesRequest`, `paginate()`, eager loading e `PurchaseResource::collection()`, retornando `data`, `links` e `meta` no formato nativo do Laravel.

**Rationale**: É contrato obrigatório do projeto e evita criar um padrão paralelo. Retailer filtra por `retailer_id`; producer filtra por relação `offer.producer_id`, sem carregar registros de terceiros para a aplicação.

## 7. TDD e Bruno como pré-condições

**Decision**: O contrato OpenAPI e as três requisições Bruno serão escritos antes dos testes. Cada teste Feature será escrito e executado em Red antes da produção correspondente; depois a implementação será feita em fatias e executada em Green.

**Rationale**: A instrução do usuário exige que a implementação siga estritamente o planejamento. O fluxo também atende ao gate TDD do projeto: Feature tests percorrem middleware, request, controller, Action, transformer e banco reais.

**Alternatives considered**:

- Implementar produção e depois criar testes: rejeitado explicitamente pelo gate TDD.
- Criar testes unitários mockando Actions no controller: rejeitado porque não prova o fluxo real da API.
