# Feature Specification: Calendário de Previsão de Colheita

**Feature Branch**: `[004-seasonal-harvest-calendar]`

**Created**: 2026-11-10

**Status**: Draft

**Input**: User description: "Calendário público para produtores registrarem previsões de colheita por produto, com quantidade e data ou período sazonal sugerido pelo sistema, para compradores consultarem com antecedência."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Publicar previsão de colheita (Priority: P1)

Como produtor, quero registrar previsões independentes por produto, data ou período e quantidade esperada, para antecipar minha oferta aos compradores.

**Why this priority**: Sem previsões cadastradas, o calendário não oferece valor aos compradores.

**Independent Test**: Produtor consegue publicar uma previsão futura para um produto sem criar ou alterar uma oferta de estoque.

**Acceptance Scenarios**:

1. **Given** um produtor e produto válidos, **When** ele escolhe uma data futura específica, informa quantidade esperada e confirma, **Then** a previsão fica disponível no calendário.
2. **Given** o sistema oferece um período sazonal geral para o produto, **When** o produtor seleciona e confirma esse período com a quantidade total esperada, **Then** a previsão fica disponível para todo o período informado.
3. **Given** um produtor sem previsão para determinado produto, **When** ele registra uma previsão, **Then** os dados de estoque e disponibilidade para compra permanecem inalterados.
4. **Given** quantidade zero ou período inválido, **When** o produtor tenta publicar a previsão, **Then** o sistema rejeita a entrada e não grava dados parciais.

### User Story 2 - Consultar previsões futuras (Priority: P1)

Como comprador, quero consultar previsões de todos os produtores e filtrar por produto e período, para planejar compras futuras.

**Why this priority**: Consulta pública transforma os dados do produtor em informação útil para planejamento de abastecimento.

**Independent Test**: Comprador encontra previsões futuras correspondentes ao produto e período escolhidos e identifica produtor, estado, produto e quantidade.

**Acceptance Scenarios**:

1. **Given** previsões futuras publicadas por diferentes produtores, **When** um comprador consulta o calendário, **Then** vê todas as previsões futuras e os dados do produtor, incluindo o estado exibido junto à identificação.
2. **Given** filtros de produto e período, **When** o comprador aplica ambos, **Then** vê somente previsões do produto escolhido cujo intervalo se sobreponha ao período consultado.
3. **Given** uma previsão dentro do período consultado, **When** o comprador consulta o resultado, **Then** vê produto, quantidade total estimada para a data ou período, produtor e estado.
4. **Given** mais previsões que cabem em uma resposta, **When** o comprador navega pelos resultados, **Then** pode alcançar todas as correspondências sem receber itens fora dos filtros.

### User Story 3 - Atualizar e expirar previsões (Priority: P2)

Como produtor, quero corrigir previsões publicadas; e como comprador, quero ver somente previsões ainda futuras.

**Why this priority**: Previsões podem mudar, e dados vencidos não devem parecer oferta futura.

**Independent Test**: Produtor atualiza sua previsão; comprador deixa de ver a previsão quando sua data ou período termina.

**Acceptance Scenarios**:

1. **Given** uma previsão do próprio produtor, **When** ele altera produto, data ou quantidade e salva, **Then** o calendário passa a exibir os valores atualizados.
2. **Given** uma previsão pertencente a outro produtor, **When** um produtor tenta alterá-la, **Then** a alteração é recusada.
3. **Given** uma previsão cuja data ou período já terminou, **When** um comprador consulta o calendário, **Then** essa previsão não aparece nos resultados.
4. **Given** uma previsão que deixou de ser válida, **When** seu produtor a remove, **Then** ela deixa de aparecer nas consultas.

### Edge Cases

- Produto sem período sazonal sugerido continua aceitando previsão com data específica.
- Data específica representa período de um único dia; quantidade se refere à colheita esperada nesse dia.
- Em período sazonal, quantidade representa o total esperado para todo o intervalo, não uma quantidade diária.
- Datas passadas não podem ser publicadas nem escolhidas como nova data final de uma previsão.
- Previsões editadas que passam a ter data ou período encerrado deixam de aparecer para compradores.
- Produtor pode remover uma previsão que deixou de ser válida antes do vencimento.
- O estado exibido vem dos dados do produtor e não determina período sazonal, visibilidade nem filtragem.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Sistema MUST permitir que produtores registrem previsões futuras independentes, cada uma associada a um único produto.
- **FR-002**: Cada previsão MUST conter produto, quantidade total estimada, início e fim do período, e identificação do produtor.
- **FR-003**: Sistema MUST permitir previsão de um único dia ou de um intervalo sazonal.
- **FR-004**: Sistema MUST retornar períodos sazonais gerais configurados por produto quando houver referência curada; produtor MUST confirmar o período antes da publicação e MUST poder escolher uma data específica. A lista inicial pode estar vazia.
- **FR-005**: Sistema MUST validar quantidade positiva, produto existente e datas futuras válidas, com início não posterior ao fim.
- **FR-006**: Produtor MUST poder editar e remover suas próprias previsões; MUST NOT editar ou remover previsões de outros produtores.
- **FR-007**: Sistema MUST disponibilizar previsões futuras a todos os compradores da Cultiva, sem restringi-las por estado.
- **FR-008**: Sistema MUST permitir consulta de previsões por produto e por período, incluindo previsões cujo intervalo se sobreponha ao intervalo pesquisado.
- **FR-009**: Resultados de consulta MUST apresentar produto, quantidade estimada, período, nome do produtor e estado informado no perfil do produtor.
- **FR-010**: Previsões cujo período terminou MUST deixar de aparecer nas consultas de compradores.
- **FR-011**: Previsões MUST permanecer separadas do estoque e das ofertas; criação, edição, consulta e remoção de previsão MUST NOT alterar disponibilidade para compra.
- **FR-012**: Sistema MUST NOT gerar compras ou notificações a partir de previsões ou de interesse visual do comprador.

### Key Entities *(include if feature involves data)*

- **Previsão de colheita**: Registro independente de um produto que um produtor espera colher, com quantidade total e data ou intervalo futuro.
- **Período sazonal sugerido**: Intervalo geral associado a um produto, apresentado pelo sistema como sugestão que o produtor deve confirmar.
- **Produtor**: Usuário responsável por criar, editar ou remover suas previsões; seu nome e estado são apresentados aos compradores.
- **Comprador**: Usuário que consulta previsões futuras e filtra por produto e período.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: 100% das previsões publicadas incluem produto, quantidade, período e produtor.
- **SC-002**: Comprador consegue filtrar previsões simultaneamente por produto e período e recebe somente correspondências futuras.
- **SC-003**: 100% das previsões com período encerrado ficam ausentes dos resultados de compradores.
- **SC-004**: Criar, editar ou remover previsão não altera estoque nem disponibilidade de ofertas em nenhum cenário coberto.
- **SC-005**: Nenhum produtor consegue modificar ou remover previsões pertencentes a outro produtor.

## Assumptions

- A sugestão sazonal é geral por produto e não varia por estado ou região; o estado aparece apenas como contexto do produtor.
- Períodos sazonais são referências, não garantias de produção; somente são publicados após confirmação do produtor.
- Produto e unidade de quantidade reutilizam o catálogo e a unidade já usados nas ofertas da Cultiva.
- Previsões não são compráveis e não alteram estoque; o calendário é uma superfície de planejamento.
- Marcação visual de favorito/interesse e qualquer interface do calendário ficam para o handoff ao frontend; esta especificação cobre comportamento de backend e não inclui notificações.
