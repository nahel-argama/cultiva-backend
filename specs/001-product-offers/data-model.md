# Data Model: Ofertas de Produtos

## Category

Tabela de referência seedada, sem administração por API nesta feature.

| Field | Type | Required | Rules |
|---|---|---|---|
| `id` | bigint | yes | Primary key; valor fixo entre 1 e 5 |
| `name` | varchar(100) | yes | Único |
| `created_at` | timestamp | yes | Gerenciado pela aplicação |
| `updated_at` | timestamp | yes | Gerenciado pela aplicação |

### Seed data

| ID | Name |
|---:|---|
| 1 | Frutas |
| 2 | Legumes |
| 3 | Verduras |
| 4 | Tubérculos e raízes |
| 5 | Grãos e cereais |

O seeder usa upsert pelos IDs fixos. Reexecução atualiza os nomes esperados sem duplicar linhas.

### Relationships

- Category has many Offers.
- Category não pode ser excluída enquanto houver Offer vinculada.

## Offer

Oferta comercial pertencente a um Producer e baseada em um produto externo validado.

| Field | Type | Required | Default | Rules |
|---|---|---|---|---|
| `id` | bigint | yes | sequence | Primary key |
| `producer_id` | bigint | yes | — | FK para `producers.id`; nunca recebido no payload |
| `category_id` | bigint | yes | — | FK para `categories.id` |
| `source_product_id` | varchar(255) | yes | — | Texto não vazio; snapshot do identificador validado |
| `product_name` | text | yes | — | Nome de apresentação ou fallback normalizado |
| `unit_price` | numeric(12,2) | yes | — | Maior que zero |
| `total_quantity` | integer | yes | — | Maior ou igual a zero |
| `reserved_quantity` | integer | yes | `0` | Entre zero e `total_quantity`; não editável nesta feature |
| `status` | varchar(8) | yes | `inactive` | `active` ou `inactive` |
| `created_at` | timestamp | yes | current time | Gerenciado pela aplicação |
| `updated_at` | timestamp | yes | current time | Gerenciado pela aplicação |

Não há `deleted_at`, `is_visible` ou restrição única para produto por produtor.

### Database constraints

- `unit_price > 0`
- `total_quantity >= 0`
- `reserved_quantity >= 0`
- `reserved_quantity <= total_quantity`
- `status IN ('active', 'inactive')`
- `producer_id` referencia Producer com cascade apenas em exclusão física.
- `category_id` referencia Category com exclusão restrita.

### Indexes

- `(producer_id, created_at)` para ownership e listagem administrativa.
- `(status, created_at)` para filtrar e ordenar a listagem comercial.
- Nenhum índice em `source_product_id` até existir consulta por esse campo.

### Relationships

- Offer belongs to Producer.
- Offer belongs to Category.
- Producer has many Offers.

### Derived values

- `available_quantity = total_quantity - reserved_quantity`
- `is_visible = status == active AND total_quantity > reserved_quantity`

Os derivados são calculados na leitura. `is_visible` também define o filtro da listagem de varejista.

## DTOs

### CreateOfferDTO

| Property | Type | Source |
|---|---|---|
| `sourceProductId` | string | Valor validado, normalizado sem perder zeros |
| `categoryId` | int | Payload |
| `unitPrice` | string | Decimal do payload |
| `totalQuantity` | int | Payload |
| `status` | OfferStatus | Payload ou `inactive` |

`producer_id`, `product_name` e `reserved_quantity` não pertencem ao DTO. Producer vem do token, product_name vem da integração e reserved inicia no domínio.

### UpdateOfferDTO

Mesmas propriedades editáveis, todas opcionais para PATCH. O ID do produto efetivo é o valor enviado ou o snapshot atual. A Action sempre consulta novamente o catálogo antes de salvar.

### ProductDTO

| Property | Type | Meaning |
|---|---|---|
| `id` | string | ID confirmado pelo catálogo |
| `name` | string | Nome final já resolvido (`name` ou `normal_name`) |

Nenhum payload cru do fornecedor atravessa a integração.

## Validation ownership

### HTTP/FormRequest

- `source_product_id`: string ou inteiro aceito; normalização final para string; máximo 255.
- `category_id`: inteiro.
- `unit_price`: decimal positivo com no máximo duas casas.
- `total_quantity`: inteiro não negativo.
- `status`: `active|inactive`; opcional no POST e PATCH.
- `producer_id`: prohibited.
- `reserved_quantity`: prohibited.
- PATCH exige ao menos um campo editável.
- `page`: inteiro positivo, default 1; `per_page`: inteiro entre 1 e 100, default 15.
- Listagens usam o paginator nativo para obter `current_page`, `per_page`, `total` e os booleanos de navegação; nenhuma contagem adicional é feita na resposta.

### Domain/Action

- Producer é fornecido a partir da identidade autenticada.
- Category precisa existir.
- Show/update consultam Offer dentro da relação do Producer.
- `total_quantity` efetiva não pode ficar abaixo de `reserved_quantity` atual.
- Produto precisa existir no catálogo em toda criação/edição.
- Resposta externa precisa corresponder ao ID e fornecer nome utilizável.
- Nenhuma escrita ocorre antes de todas as validações.

## State transitions

```text
inactive --producer activates--> active
active   --producer deactivates-> inactive

active + available_quantity > 0  => visible
active + available_quantity = 0  => hidden, status remains active
inactive + any quantity           => hidden
```

Restaurar `total_quantity` acima de `reserved_quantity` torna uma oferta ativa novamente visível. Reserva e venda que alteram `reserved_quantity` pertencem a feature futura.
