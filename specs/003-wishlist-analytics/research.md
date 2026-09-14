# Research: Wishlist e Analytics

## Decision: Reutilizar os padrões de domínio existentes

- **Rationale**: Offer, Purchase e Auth já usam Actions com `execute()`, DTOs, FormRequests, Resources e `CultivaException`; repetir esse fluxo reduz risco e arquivos.
- **Alternatives considered**: controller com regra própria, repository ou service genérico. Rejeitados por duplicarem padrões e violarem a constituição.

## Decision: Persistir apenas `wishlist_items`

- **Rationale**: A especificação define uma wishlist lógica por retailer. Um item independente é suficiente para ownership, snapshot e agregação.
- **Alternatives considered**: tabela `wishlists` pai ou entidade Product local. Rejeitados: adicionam relações e estado sem necessidade e o catálogo oficial já está no ProductSource.

## Decision: Usar `GetProductAction` como fronteira do catálogo

- **Rationale**: O ProductSource já resolve nome oficial, fallback de nome e falhas externas. A wishlist recebe DTO tipado e persiste os valores necessários.
- **Alternatives considered**: chamada HTTP direta na Action da wishlist ou model Product. Rejeitados por contaminar o domínio com fornecedor e criar fonte duplicada de verdade.

## Decision: Traduzir produto inexistente para 404 apenas na wishlist

- **Rationale**: O contrato da wishlist exige 404, enquanto `GetProductAction` e testes existentes tratam o 404 externo como `CultivaException` 422 para preservar Offers. A tradução deve ser localizada, sem regressão nos consumidores atuais.
- **Alternatives considered**: alterar globalmente `GetProductAction` para 404. Rejeitado porque quebraria o contrato já testado de Offers/ProductSource.

## Decision: Agregação e ordenação no banco

- **Rationale**: `GROUP BY source_product_id, product_name`, filtro por `state`, `COUNT(*)`, ordenação determinística e `LIMIT` evitam carregar todos os itens para memória.
- **Alternatives considered**: buscar a coleção e agrupar em PHP. Rejeitado por custo de memória e por contrariar FR-017.

## Decision: Percentual global e `others`

- **Rationale**: O percentual representa participação no site inteiro, não apenas nos produtos retornados. A consulta busca `limit + 1`; se houver grupo extra, os grupos omitidos são representados por `others` no fim, sem carregar todos os grupos.
- **Alternatives considered**: percentual sobre o ranking limitado ou carregar todos os grupos para agrupar em PHP. Rejeitados por distorcer a participação global ou aumentar memória.

## Decision: `limit` default 10, máximo 50

- **Rationale**: Limita a resposta a produtos mais relevantes e evita uma listagem analítica sem teto; ambos os valores ficam no FormRequest e na especificação.
- **Alternatives considered**: paginação da analytics. Rejeitada porque o requisito pede `limit` e a analytics é um ranking curto, não histórico navegável.

## Decision: Concorrência protegida pelo índice único

- **Rationale**: A inclusão grava uma única tabela; o índice único é a barreira correta para duplicidade entre requisições concorrentes, com tratamento da violação no contrato 409 se necessário.
- **Alternatives considered**: lock global, tabela de locks ou transação extensa. Rejeitados por complexidade sem benefício proporcional.

## Resolved Unknowns

Não restam `NEEDS CLARIFICATION` no Technical Context. O backend, banco, testes, limites e contratos foram confirmados por inspeção da configuração e dos módulos existentes.
