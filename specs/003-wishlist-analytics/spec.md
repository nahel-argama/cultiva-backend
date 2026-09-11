# Feature Specification: Wishlist e Analytics

**Feature Branch**: `N/A (nenhuma branch criada; não há hook before_specify configurado)`

**Created**: 2026-09-11

**Status**: Draft

**Input**: Implementar wishlist por varejista e analytics agregada de interesse por produto, preservando `product_id` na API e `source_product_id` no armazenamento.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Adicionar produto à wishlist (Priority: P1)

Como varejista autenticado, quero adicionar um produto à minha wishlist para registrar interesse de compra com o nome oficial e o estado da empresa no momento do registro.

**Why this priority**: É o fluxo que cria o sinal de demanda usado pelo próprio varejista e pela analytics de produtores.

**Independent Test**: Adicionar um produto existente com um varejista que possui endereço e verificar a resposta e o registro persistido.

**Acceptance Scenarios**:

1. **Given** um varejista autenticado com endereço da empresa e um produto existente, **When** envia somente `product_id`, **Then** o item é criado com o identificador externo, nome oficial e estado do endereço, sem aceitar dados controlados pelo cliente.
2. **Given** um produtor, delivery ou outro perfil sem permissão, **When** tenta adicionar um item, **Then** recebe acesso negado e nenhum item é criado.
3. **Given** um produto inexistente, **When** o varejista tenta adicioná-lo, **Then** recebe não encontrado e nenhum item é criado.
4. **Given** um varejista sem endereço válido, **When** tenta adicionar um produto, **Then** recebe erro de regra de negócio e nenhum item é criado.
5. **Given** o mesmo produto já adicionado pelo varejista, **When** tenta adicioná-lo novamente, **Then** recebe conflito e permanece um único item.

### User Story 2 - Consultar e remover minha wishlist (Priority: P1)

Como varejista autenticado, quero consultar, pesquisar e remover itens da minha wishlist sem acessar dados de outro varejista.

**Why this priority**: O varejista precisa manter seu registro de interesse e confiar que ele é isolado.

**Independent Test**: Criar itens para dois varejistas, consultar com busca e paginação e tentar remover item pertencente ao outro varejista.

**Acceptance Scenarios**:

1. **Given** itens de dois varejistas, **When** um varejista consulta `GET /api/v1/wishlist/items`, **Then** recebe somente seus próprios itens.
2. **Given** itens com nomes diferentes, **When** informa `search`, **Then** recebe somente itens cujo nome do produto corresponde à pesquisa.
3. **Given** mais itens que o tamanho da página, **When** informa `page` e `per_page` válidos, **Then** recebe a página solicitada e os metadados de paginação do contrato da API.
4. **Given** um item pertencente a outro varejista, **When** tenta removê-lo, **Then** recebe não encontrado ou a resposta equivalente do escopo autorizado e o item permanece intacto.
5. **Given** um item criado com um estado, **When** o endereço atual da empresa muda, **Then** o item antigo mantém o estado registrado originalmente.

### User Story 3 - Consultar analytics de produtos desejados (Priority: P2)

Como produtor ou varejista autenticado, quero consultar a demanda agregada por produto e estado para orientar decisões comerciais sem expor identidades.

**Why this priority**: A consolidação transforma wishlists individuais em informação útil para oferta e planejamento.

**Independent Test**: Criar itens com produtos e estados variados, consultar como produtor e varejista, aplicar filtro e conferir totais, ordem e percentuais.

**Acceptance Scenarios**:

1. **Given** itens agregáveis de vários varejistas, **When** um produtor ou varejista consulta `GET /api/v1/wishlist/analytics`, **Then** recebe os produtos agrupados, sem usuários, varejistas ou outras identidades.
2. **Given** itens em estados diferentes, **When** informa `state`, **Then** o total e os resultados consideram somente o estado salvo nos itens.
3. **Given** resultados com totais diferentes, **When** consulta analytics, **Then** os resultados são ordenados pelo total decrescente e, em empate, por `source_product_id` em ordem determinística.
4. **Given** um `limit` válido, **When** consulta analytics, **Then** recebe no máximo essa quantidade de produtos, sem alterar o total filtrado usado nos percentuais.
5. **Given** nenhum item no filtro, **When** consulta analytics, **Then** recebe `total_items: 0`, resultados vazios e percentual `0` quando aplicável.

### Edge Cases

- O cliente pode enviar somente `product_id`; `retailer_id`, `user_id`, `wishlist_id`, `source_product_id`, `product_name` e `state` são ignorados ou rejeitados conforme a validação existente, nunca usados como fonte de verdade.
- A mesma combinação de varejista e produto externo não pode gerar dois itens, inclusive em tentativas concorrentes.
- O item guarda snapshot do nome oficial e do estado no momento da inclusão; consultas não dependem do endereço atual.
- `page` deve ser no mínimo 1; `per_page` deve ficar entre 1 e 100; valores inválidos retornam 422.
- `limit` padrão é 10 e o máximo é 50; valores menores que 1 ou maiores que 50 retornam 422.
- Percentual é um número inteiro arredondado para o inteiro mais próximo; quando `total_items` é zero, resultados são vazios e o total permanece zero.
- Usuário não autenticado recebe a resposta padrão de autenticação; nenhum cenário de leitura ou escrita expõe dados de terceiros.
- A analytics não consulta o endereço atual de usuários ou varejistas.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: O sistema MUST permitir adicionar um item somente a um usuário autenticado com perfil retailer e ability `access`.
- **FR-002**: O endpoint de inclusão MUST aceitar `product_id` como identificador público e convertê-lo para o identificador externo `source_product_id` usado internamente; não deve existir entidade Product para este fluxo.
- **FR-003**: A inclusão MUST resolver o produto pela fonte oficial e persistir o nome retornado por ela; produto não encontrado MUST retornar 404.
- **FR-004**: A inclusão MUST obter o estado do endereço da Company relacionada ao Retailer autenticado e rejeitar a operação quando não houver endereço válido.
- **FR-005**: O item MUST persistir, no mínimo, `id`, `retailer_id`, `source_product_id`, `product_name`, `state`, `created_at` e `updated_at`.
- **FR-006**: O sistema MUST impedir duplicidade por `retailer_id` + `source_product_id` e retornar 409 quando o item já existir.
- **FR-007**: Nome e estado MUST ser snapshots imutáveis do momento da inclusão; alterações posteriores no produto ou endereço não podem modificar itens anteriores.
- **FR-008**: O endpoint `POST /api/v1/wishlist/items` MUST retornar 201 com o item criado em `data`, incluindo `id`, `source_product_id`, `product_name`, `state` e `created_at`.
- **FR-009**: O endpoint `GET /api/v1/wishlist/items` MUST retornar somente itens do varejista autenticado, aceitar pesquisa por `product_name` e suportar paginação com defaults page 1/per_page 15 e máximo 100.
- **FR-010**: Toda listagem da wishlist MUST retornar paginação nativa com `data`, `links` e `meta`, sem permitir `retailer_id` informado pelo cliente.
- **FR-011**: O endpoint `DELETE /api/v1/wishlist/items/{wishlistItem}` MUST remover somente item pertencente ao varejista autenticado e retornar sem conteúdo ou o padrão HTTP equivalente do projeto.
- **FR-012**: Produtores e outros perfis MUST ser impedidos de adicionar, listar ou remover wishlist; produtores e varejistas com ability `access` podem consultar analytics.
- **FR-013**: O endpoint `GET /api/v1/wishlist/analytics` MUST aceitar filtro opcional por `state` e `limit` default 10, máximo 50.
- **FR-014**: A analytics MUST agrupar por `source_product_id` e `product_name`, calcular `total_items` após o filtro, ordenar por total decrescente e por `source_product_id` em empate, e retornar apenas os primeiros `limit` resultados.
- **FR-015**: Cada resultado da analytics MUST conter `position`, `source_product_id`, `product_name`, `total` e `percentage`, sem identidade de usuários ou varejistas.
- **FR-016**: `percentage` MUST ser inteiro arredondado para o inteiro mais próximo, calculado sobre `total_items` filtrado; quando o total for zero, a resposta MUST conter resultados vazios e total zero.
- **FR-017**: A analytics MUST realizar a agregação na fonte persistida e não carregar todos os itens para a memória.
- **FR-018**: As Actions MUST possuir somente `execute()` público, receber DTOs readonly para entradas estruturadas e não receber Request; controllers devem apenas coordenar entrada validada, Action e Resource.
- **FR-019**: Falhas esperadas MUST usar `CultivaException` com status HTTP explícito, sem expor detalhes internos.
- **FR-020**: A implementação MUST adicionar testes Feature com ciclo real de rota e testes Unit somente para regras isoladas necessárias; comandos PHP, Composer, Artisan e testes devem ser executados no container `php`, sem Laravel Pint.

### Key Entities

- **WishlistItem**: Registro de interesse de um varejista por um produto externo, contendo snapshots do nome do produto e do estado da empresa no momento da inclusão.
- **Retailer**: Perfil autenticado dono da wishlist lógica; cada varejista possui seus próprios WishlistItems.
- **Company Address**: Endereço associado à empresa do varejista, usado apenas para obter o estado no momento da inclusão.
- **Analytics Result**: Agrupamento anônimo por produto e estado opcional, com total, posição e percentual da demanda filtrada.

### Scope Boundaries

- Inclui adicionar, listar, pesquisar, paginar, remover itens e consultar analytics agregada.
- Inclui integração com a fonte existente de produtos e conversão pública `product_id` para interna `source_product_id`.
- Não inclui model Product, tabela wishlists, wishlist compartilhada, edição de item, atualização retroativa de snapshots, notificações, recomendações ou compra automática.
- Não inclui exposição de identidade ou consulta do endereço atual na analytics.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Em 100% das inclusões válidas testadas, o item retornado contém o nome oficial do produto e o estado snapshotado do endereço no momento da operação.
- **SC-002**: Em 100% das tentativas duplicadas testadas, permanece exatamente um item por varejista e produto externo.
- **SC-003**: Em 100% das consultas isoladas testadas, nenhum varejista recebe itens pertencentes a outro varejista.
- **SC-004**: Em 100% dos testes de analytics, totais, ordenação, limite e percentuais correspondem ao conjunto filtrado e não expõem identidades.
- **SC-005**: Em 100% dos testes de autorização, somente varejistas gerenciam wishlist e produtores/varejistas autorizados consultam analytics.
- **SC-006**: Pelo menos 95% das consultas de listagem em carga normal retornam ao usuário em até 2 segundos e sem retornar páginas além do limite solicitado.
- **SC-007**: Pelo menos 90% dos usuários de teste conseguem adicionar, localizar e remover um item sem enviar campos internos ou consultar documentação adicional.

## Assumptions

- A rota pública usa o prefixo `/api/v1`, conforme a convenção vigente do backend.
- O limite padrão da analytics será 10 e o máximo 50; essa escolha mantém respostas enxutas para redes móveis e cobre os principais produtos demandados.
- Percentuais inteiros usam arredondamento convencional para o inteiro mais próximo; o arredondamento pode fazer a soma visual não ser exatamente 100.
- A resposta de analytics com estado não informado usa `state: null`; com filtro usa o estado solicitado.
- O ProductSource continua sendo a fonte oficial do identificador e nome; falhas transitórias da integração seguem o contrato de erro já existente.
- A autorização, a autenticação e os formatos padrão de erro existentes serão reutilizados.
- Uma wishlist lógica por varejista é representada diretamente por seus itens; não haverá tabela agregadora `wishlists` nesta versão.
- Como uma inclusão grava uma única tabela, transação entre múltiplas tabelas não é necessária; se a implementação passar a gravar mais de uma tabela, deverá usar transação.
