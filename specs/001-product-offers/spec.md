# Feature Specification: Ofertas de Produtos

**Feature Branch**: `N/A (nenhuma branch criada; não há hook before_specify configurado)`

**Created**: 2026-09-04

**Status**: Draft

**Input**: Sistema de ofertas baseado em produtos do catálogo externo, com propriedade exclusiva do produtor autenticado, categorias predefinidas, preço, estoque e visibilidade condicionada à disponibilidade.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Criar uma oferta a partir do catálogo (Priority: P1)

Como produtor autenticado, quero selecionar um produto já existente no catálogo e informar categoria, preço, estoque e status para disponibilizá-lo sem recadastrar seus dados básicos.

**Why this priority**: A criação da oferta é o fluxo que gera o estoque comercializável e entrega o valor central da feature.

**Independent Test**: Um produtor autenticado cria uma oferta com produto válido e categoria existente; a oferta fica associada somente a ele, preserva o nome do produto consultado e inicia sem quantidade reservada.

**Acceptance Scenarios**:

1. **Given** um produtor autenticado, um produto existente no catálogo e uma categoria válida, **When** ele cria uma oferta com preço e estoque válidos, **Then** a oferta é registrada para esse produtor, armazena o identificador textual e o nome do produto e inicia com quantidade reservada igual a zero.
2. **Given** um identificador que não existe no catálogo, **When** o produtor tenta criar a oferta, **Then** a operação é rejeitada como dado inválido com status 422 e nenhuma oferta é criada.
3. **Given** o catálogo indisponível, lento além do limite ou retornando falha de serviço, **When** o produtor tenta criar a oferta, **Then** a operação é rejeitada como serviço indisponível com status 503 e nenhuma oferta é criada.
4. **Given** um usuário sem perfil de produtor, **When** tenta criar uma oferta, **Then** o acesso é negado e nenhuma oferta é criada.
5. **Given** uma requisição contendo `producer_id`, **When** o produtor tenta criar uma oferta, **Then** a entrada é rejeitada e o proprietário não pode ser escolhido pelo cliente.

---

### User Story 2 - Consultar categorias disponíveis (Priority: P1)

Como produtor autenticado, quero consultar as categorias disponíveis para selecionar uma categoria válida ao criar ou editar uma oferta.

**Why this priority**: Sem conhecer as opções predefinidas, o produtor não consegue concluir corretamente o fluxo principal.

**Independent Test**: Um produtor autenticado consulta as categorias e recebe exatamente o conjunto predefinido, com identificador e nome de cada opção.

**Acceptance Scenarios**:

1. **Given** as categorias iniciais carregadas, **When** um produtor autenticado consulta as opções, **Then** recebe todas as categorias disponíveis com identificadores e nomes.
2. **Given** uma categoria inexistente, **When** ela é informada na criação ou edição, **Then** a operação é rejeitada com status 422 e a oferta permanece inalterada.

---

### User Story 3 - Listar e consultar as próprias ofertas (Priority: P1)

Como produtor autenticado, quero ver todas as minhas ofertas, inclusive inativas ou sem estoque disponível, para acompanhar e administrar meu catálogo.

**Why this priority**: Isolamento de propriedade e visão completa do estoque são necessários para o produtor administrar o que criou.

**Independent Test**: Dois produtores possuem ofertas; cada um recebe somente as suas e não consegue consultar a oferta do outro.

**Acceptance Scenarios**:

1. **Given** ofertas de produtores diferentes, **When** um produtor lista suas ofertas, **Then** recebe somente as ofertas vinculadas à sua identidade autenticada.
2. **Given** uma oferta pertencente a outro produtor, **When** o produtor tenta consultá-la, **Then** o recurso é apresentado como não encontrado e nenhum dado da oferta é exposto.
3. **Given** ofertas ativas, inativas e esgotadas do mesmo produtor, **When** ele lista suas ofertas, **Then** todas são exibidas com preço, estoque total, quantidade reservada, status e condição de visibilidade.

---

### User Story 4 - Editar uma oferta própria (Priority: P2)

Como produtor autenticado, quero alterar produto de referência, categoria, preço, estoque total ou status de uma oferta própria para manter as condições comerciais atualizadas.

**Why this priority**: A edição mantém ofertas úteis após a criação, mas depende da existência do fluxo principal.

**Independent Test**: Um produtor altera uma oferta própria com dados válidos e vê os novos dados; a quantidade reservada continua protegida e a propriedade não muda.

**Acceptance Scenarios**:

1. **Given** uma oferta própria, **When** o produtor envia uma edição válida, **Then** os campos permitidos são atualizados e produto e nome snapshotado são novamente validados junto ao catálogo.
2. **Given** uma oferta com quantidade reservada maior que zero, **When** o produtor informa estoque total menor que a quantidade reservada, **Then** a edição é rejeitada com status 422 e a oferta permanece inalterada.
3. **Given** uma edição contendo `producer_id` ou `reserved_quantity`, **When** ela é enviada, **Then** a entrada é rejeitada e propriedade e quantidade reservada permanecem inalteradas.
4. **Given** uma oferta de outro produtor, **When** o produtor tenta editá-la, **Then** o recurso é apresentado como não encontrado e nenhuma alteração é aplicada.
5. **Given** falha ou indisponibilidade do catálogo durante a edição, **When** o produto é validado, **Then** a operação retorna status 503 e todos os dados anteriores da oferta são preservados.

---

### User Story 5 - Ver somente ofertas disponíveis para compra (Priority: P2)

Como varejista autenticado, quero ver apenas ofertas efetivamente disponíveis para não iniciar uma compra de item inativo ou sem saldo.

**Why this priority**: A regra protege a confiança do varejista e transforma o cadastro do produtor em oferta comercializável.

**Independent Test**: Com ofertas em combinações distintas de status e estoque, o varejista recebe somente aquelas ativas cuja quantidade total seja maior que a reservada.

**Acceptance Scenarios**:

1. **Given** uma oferta ativa com saldo disponível, **When** o varejista consulta ofertas para compra, **Then** a oferta é exibida.
2. **Given** uma oferta inativa, **When** o varejista consulta ofertas para compra, **Then** a oferta não é exibida, mesmo que tenha saldo.
3. **Given** uma oferta ativa cuja quantidade total é igual à reservada, **When** o varejista consulta ofertas para compra, **Then** a oferta não é exibida.
4. **Given** uma oferta anteriormente esgotada que volta a possuir saldo após edição válida, **When** o varejista consulta ofertas para compra, **Then** a oferta volta a ser exibida se continuar ativa.

### Edge Cases

- Identificadores numéricos são aceitos e preservados como texto; uma representação textual com zeros à esquerda não pode ser alterada inadvertidamente.
- Resposta bem-sucedida do catálogo sem nome de apresentação usa o nome normalizado disponível como snapshot; resposta sem qualquer nome utilizável é tratada como indisponibilidade do catálogo.
- Falha do catálogo não pode criar uma oferta parcial nem aplicar parte de uma edição.
- Preço igual ou menor que zero, com mais de duas casas decimais ou fora da moeda esperada é inválido.
- Estoque total negativo ou fracionário é inválido; estoque total igual à quantidade reservada é válido, mas deixa a oferta invisível para compra.
- Uma oferta pode permanecer com status ativo mesmo sem saldo; sua visibilidade é recalculada pela combinação entre status e disponibilidade.
- Alterações posteriores no nome do catálogo não mudam ofertas já salvas até que cada oferta passe por uma edição bem-sucedida.
- Ofertas repetidas do mesmo produto para o mesmo produtor são permitidas, pois podem representar condições comerciais ou lotes distintos.
- Usuários sem autenticação não podem criar, listar ou editar ofertas de produtor.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: O sistema MUST permitir que um usuário autenticado com perfil de produtor crie uma oferta usando um produto já existente no catálogo externo.
- **FR-002**: O sistema MUST obter o produtor proprietário exclusivamente da identidade autenticada e MUST rejeitar `producer_id` enviado na criação ou edição.
- **FR-003**: O sistema MUST restringir criação, consulta administrativa, listagem administrativa e edição de ofertas ao perfil de produtor.
- **FR-004**: O sistema MUST garantir que cada produtor consulte e altere exclusivamente suas próprias ofertas, sem revelar a existência ou os dados das ofertas de outros produtores.
- **FR-005**: O sistema MUST aceitar o identificador do produto como texto não vazio ou valor numérico convertível sem perda para texto e MUST armazená-lo como texto.
- **FR-006**: O sistema MUST consultar o catálogo externo pelo identificador do produto em toda criação e edição, inclusive quando o identificador não tiver sido alterado.
- **FR-007**: O sistema MUST rejeitar com status 422 a criação ou edição quando o catálogo informar que o produto não existe.
- **FR-008**: O sistema MUST rejeitar com status 503 a criação ou edição quando o catálogo estiver indisponível, exceder o tempo de resposta, retornar falha de serviço ou não fornecer uma resposta utilizável.
- **FR-009**: O sistema MUST preservar integralmente o estado anterior quando a validação do produto ou qualquer outra validação de uma edição falhar.
- **FR-010**: O sistema MUST armazenar na oferta o nome do produto obtido no catálogo; quando o nome de apresentação não estiver disponível, MUST usar o nome normalizado retornado pelo catálogo.
- **FR-011**: O sistema MUST atualizar o snapshot do nome após cada edição bem-sucedida, sem alterar automaticamente snapshots de ofertas que não foram editadas.
- **FR-012**: O sistema MUST carregar e manter as categorias predefinidas `Frutas`, `Legumes`, `Verduras`, `Tubérculos e raízes` e `Grãos e cereais`, disponibilizando-as para consulta do produtor.
- **FR-013**: O sistema MUST exigir que toda oferta esteja vinculada a uma categoria existente e MUST rejeitar categoria inexistente com status 422.
- **FR-014**: O sistema MUST registrar o preço unitário em reais, maior que zero e com no máximo duas casas decimais.
- **FR-015**: O sistema MUST registrar `total_quantity` como número inteiro maior ou igual a zero.
- **FR-016**: O sistema MUST iniciar `reserved_quantity` em zero em toda nova oferta.
- **FR-017**: O sistema MUST impedir que criação ou edição administrativa forneça ou altere diretamente `reserved_quantity`.
- **FR-018**: O sistema MUST rejeitar com status 422 uma redução de `total_quantity` para valor inferior a `reserved_quantity`.
- **FR-019**: O sistema MUST permitir os estados ativo e inativo e MUST considerar inativo quando o estado for omitido na criação.
- **FR-020**: O sistema MUST considerar uma oferta visível para compra somente quando seu estado for ativo e `total_quantity` for maior que `reserved_quantity`.
- **FR-021**: O sistema MUST recalcular a visibilidade da oferta a partir do estado e das quantidades atuais, sem exigir uma ação manual adicional.
- **FR-022**: O sistema MUST permitir que o produtor liste todas as suas ofertas, independentemente de estado ou saldo disponível, incluindo a indicação calculada de visibilidade.
- **FR-023**: O sistema MUST permitir que um varejista autenticado consulte somente ofertas visíveis para compra.
- **FR-024**: O sistema MUST retornar os dados essenciais da oferta nas consultas: produto e nome snapshotado, categoria, preço unitário, estoque total, quantidade reservada, estado e visibilidade.
- **FR-025**: O sistema MUST manter registros suficientes de criação e última atualização para apoiar rastreabilidade e suporte.

- **FR-026**: Toda listagem paginada MUST usar `paginate()` na query e retornar exatamente `data`, `current_page`, `per_page`, `total`, `has_previous_page` e `has_next_page` na raiz, sem links ou wrappers.
- **FR-027**: Parâmetros MUST ser inteiros positivos, com defaults `page=1` e `per_page=15`, máximo de 100 itens e HTTP 422 para entradas inválidas.
- **FR-028**: A serialização MUST reaproveitar a contagem do paginator e preservar eager loading de relações. Página acima da última retorna lista vazia; resultado vazio na página 1 retorna ambos os booleanos false. A navegação usa `! onFirstPage()` e `hasMorePages()` do paginator.

### Key Entities

- **Offer**: Oferta comercial de um produtor para um produto do catálogo. Possui proprietário, identificador textual e nome snapshotado do produto, categoria, preço unitário, quantidade total, quantidade reservada, estado, visibilidade calculada e datas de criação e atualização.
- **Category**: Classificação predefinida usada para organizar ofertas. Possui identificador e nome e pode ser consultada, mas não administrada por esta feature.
- **Producer**: Perfil proprietário da oferta, derivado da identidade autenticada. Um produtor pode possuir várias ofertas.
- **External Catalog Product**: Referência externa que confirma a existência do produto e fornece os nomes usados no snapshot; não é criado nem alterado por esta feature.

### Scope Boundaries

- Inclui criação, consulta administrativa, listagem administrativa e edição de ofertas próprias.
- Inclui consulta das categorias predefinidas e consulta de ofertas disponíveis para varejistas.
- Não inclui cadastro ou edição de produtos no catálogo externo.
- Não inclui criação, edição ou exclusão de categorias por usuários.
- Não inclui exclusão de ofertas; o produtor pode retirá-las da venda tornando-as inativas.
- Não inclui reserva, venda ou liberação de estoque; esses fluxos futuros serão os únicos responsáveis por alterar `reserved_quantity`.
- Não inclui pedidos, pagamentos, entrega, recomendação, ranqueamento ou comparação de preços.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Pelo menos 95% dos produtores conseguem criar uma oferta válida na primeira tentativa em até 2 minutos, desconsiderando indisponibilidade confirmada do catálogo externo.
- **SC-002**: Em 100% dos cenários testados, uma oferta inválida por produto inexistente é rejeitada sem persistência e uma falha do catálogo preserva integralmente os dados existentes.
- **SC-003**: Em 100% dos cenários de autorização testados, produtores não acessam nem alteram ofertas de outros produtores e o cliente não escolhe o proprietário da oferta.
- **SC-004**: Em 100% das combinações testadas de estado e estoque, somente ofertas ativas com saldo disponível aparecem ao varejista.
- **SC-005**: Em 100% das ofertas criadas, o nome snapshotado corresponde ao catálogo no momento da última criação ou edição bem-sucedida e a quantidade reservada inicial é zero.
- **SC-006**: Produtores e varejistas recebem suas listagens em até 2 segundos em pelo menos 95% das consultas sob carga operacional normal.
- **SC-007**: Pelo menos 90% dos usuários de teste identificam corretamente, sem suporte, se uma oferta está disponível para compra a partir dos dados apresentados.

## Assumptions

- A autenticação e os perfis de produtor e varejista já existentes serão reutilizados.
- A consulta administrativa de uma oferta alheia responderá como recurso não encontrado para evitar exposição de existência e propriedade.
- A moeda desta primeira versão é o real brasileiro e quantidades representam unidades inteiras; unidades fracionárias exigirão especificação futura.
- O estado inicial será inativo quando não for informado, evitando publicação acidental.
- O catálogo externo continuará oferecendo consulta individual por identificador e retornando identificador textual, nome de apresentação opcional e nome normalizado obrigatório.
- O catálogo roda como serviço independente; disponibilidade de rede, endereço e tempo limite serão configuráveis por ambiente durante o planejamento.
- A lista comercial é destinada a varejistas autenticados, consistente com o escopo B2B atual da plataforma.
- Ofertas duplicadas do mesmo produto pelo mesmo produtor são permitidas.
- `reserved_quantity` será atualizado por fluxos futuros de reserva e venda, fora do escopo desta feature.
- A carga inicial de categorias será idempotente e usará identificadores estáveis entre ambientes.
