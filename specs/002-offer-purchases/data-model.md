# Data Model: Compras de Ofertas

## Purchase

Representa uma compra confirmada de uma quantidade de uma Offer. Uma Offer pode possuir várias Purchases; cada Purchase tem uma única Offer e um único retailer comprador.

| Campo | Tipo lógico | Obrigatório | Regra |
|---|---|---:|---|
| `id` | inteiro | sim | Identificador da compra |
| `offer_id` | inteiro | sim | Offer de origem |
| `retailer_id` | inteiro | sim | Retailer autenticado |
| `producer_id` | inteiro | sim | Producer proprietário da Offer no momento da compra |
| `source_product_id` | texto | sim | Snapshot do identificador do produto |
| `product_name` | texto | sim | Snapshot do nome exibido |
| `quantity` | inteiro | sim | Maior que zero |
| `unit_price` | decimal monetário | sim | Snapshot do preço, maior que zero, duas casas |
| `total_price` | decimal monetário | sim | `unit_price * quantity`, calculado pelo backend |
| `created_at` | data/hora | sim | Momento da confirmação |
| `updated_at` | data/hora | sim | Rastreamento padrão |

### Relationships

- Purchase belongs to Offer, Retailer e Producer.
- Offer has many Purchases.
- Retailer has many Purchases.
- Producer has many Purchases through Offers; um relacionamento direto só será adicionado se necessário para a consulta.

## Offer changes

O campo existente `reserved_quantity` será incrementado pela quantidade da Purchase confirmada. Nesta feature ele representa quantidade comprometida por compras, sem criar `purchased_quantity` adicional.

Offers novas com quantidade total positiva são criadas pelo backend com
`status = active`; `status` não faz parte do payload de criação. Quando o
estoque é totalmente consumido, a transação da última Purchase altera o status
para `inactive`.

Invariants after every committed purchase:

- `reserved_quantity >= 0`.
- `reserved_quantity <= total_quantity`.
- `available_quantity = total_quantity - reserved_quantity`.
- `available_quantity >= 0`.
- If `reserved_quantity == total_quantity`, `status == inactive`.
- A compra só é confirmada quando `status == active` e `reserved_quantity < total_quantity` antes do incremento.

## Database integrity

- Foreign keys ligam Purchase a Offer, Retailer e Producer e preservam a política de exclusão definida no planejamento.
- Constraints devem garantir `quantity > 0`, preços não negativos e os limites de quantidade; a consistência de `total_price` também deve ser calculada pelo domínio.
- Transação mais lock de linha protege a sequência ler/validar/incrementar/criar; constraints continuam como barreira final.
- Nenhum `deleted_at` é adicionado a Purchase. Registros históricos permanecem disponíveis.

## State transition

```text
Offer active + available
        │ purchase quantity < available
        ▼
Offer active + remaining available

Offer active + quantity == available
        ▼
Offer inactive + zero available
```

Offer inativa ou esgotada não possui transição válida por Purchase. Alterações de estoque recalculam o status automaticamente e não alteram snapshots antigos.
