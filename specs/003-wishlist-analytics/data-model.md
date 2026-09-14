# Data Model: Wishlist e Analytics

## WishlistItem

Registro de interesse de um retailer por um produto externo, com snapshots para preservar o contexto da inclusão.

| Campo | Tipo lógico | Obrigatório | Regra |
|---|---|---:|---|
| `id` | inteiro | sim | Identificador persistente |
| `retailer_id` | inteiro | sim | Foreign key para Retailer; sempre vem do usuário autenticado |
| `source_product_id` | texto | sim | Identificador externo convertido de `product_id` |
| `product_name` | texto | sim | Nome oficial retornado pelo ProductSource |
| `state` | texto de 2 caracteres | sim | Estado snapshotado do endereço da Company |
| `created_at` | data/hora | sim | Momento da inclusão |
| `updated_at` | data/hora | sim | Rastreamento padrão |

### Relationships

- `WishlistItem belongsTo Retailer`.
- `Retailer hasMany WishlistItem`.
- `Retailer belongsTo Company`; `Company morphOne Address` já existem e serão reutilizados.

### Database integrity

- Foreign key `retailer_id` referencia `retailers` com a política de deleção compatível com o padrão atual.
- Índice unique em `retailer_id, source_product_id` impede duplicidade por retailer.
- Índice em `retailer_id, created_at` atende listagem ordenada/isolada.
- Índice em `state, source_product_id` apoia filtro e agrupamento da analytics.
- `source_product_id`, `product_name` e `state` são preenchidos pelo backend e não por campos controlados do payload.

## Analytics result

DTO readonly não persistido, composto por:

| Campo | Tipo lógico | Regra |
|---|---|---|
| `state` | texto ou nulo | Filtro recebido; nulo quando ausente |
| `total_items` | inteiro | Quantidade total de WishlistItems após filtro |
| `results` | lista de produtos | Até `limit` produtos; `others` é retornado separadamente quando houver grupos omitidos |
| `others` | resumo opcional | Contém apenas `total` e `percentage` dos grupos omitidos |
| `position` | inteiro | Posição 1-based após ordenação |
| `source_product_id` | texto | Chave externa agrupada |
| `product_name` | texto | Nome snapshotado agrupado |
| `total` | inteiro | Quantidade do grupo |
| `percentage` | inteiro | `round(total * 100 / total_global_wishlist_items)`; zero não produz resultados |

### Query rules

- Aplicar `state` antes da contagem e do agrupamento.
- Agrupar por `source_product_id` e `product_name`.
- Ordenar por `total DESC`, depois `source_product_id ASC`.
- Aplicar `limit` somente depois de ordenar.
- Buscar no máximo `limit + 1` grupos; adicionar `others` apenas quando o grupo extra existir, somando todos os grupos omitidos.
- Percentual usa a contagem global sem filtro; `total_items` permanece a contagem do filtro aplicado.
- Não carregar a coleção completa nem consultar endereço atual.
