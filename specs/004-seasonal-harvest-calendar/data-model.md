# Data Model: Calendário de Previsão de Colheita

## HarvestForecast

Representa previsão independente criada por produtor para um produto do Product Source.

| Field | Type | Rules |
|---|---|---|
| id | integer | Primary key |
| producer_id | integer | Required; producer owner |
| source_product_id | string | Required; existing external product identifier |
| product_name | string | Required; product name snapshot |
| quantity | integer | Required; greater than zero; total for full date interval |
| start_date | date | Required; today or later; not after end_date |
| end_date | date | Required; today or later; not before start_date |
| timestamps | datetime | Standard creation/update audit |

### Relationships

- Belongs to one `Producer`.
- Does not belong to `Offer` and does not reserve or create stock.
- The producer's displayed name and state are read from `Company` and its `Address` at response time.

### Invariants

- One forecast describes exactly one product and one date interval.
- For a single-day forecast, `start_date` equals `end_date`.
- Quantity means total expected amount over the interval, not per day.
- Only its producer may update or remove a forecast.
- Buyer queries exclude forecasts whose `end_date` is before the current date.
- Buyer date filters match overlapping intervals.

## Seasonal reference

General reference period kept in backend configuration and offered as a producer suggestion. It is not user-editable or a published forecast.

| Field | Type | Rules |
|---|---|---|
| product_name | string | Exact product name returned by Product Source |
| start_month | integer | 1–12 |
| end_month | integer | 1–12 |
| source | string | Provenance label for the reference |

### Relationships and behavior

- A product may have zero or more suggested periods.
- The initial configuration is empty until a suitable agronomic source is curated.
- Period suggestions are generic and do not depend on producer state.
- Product is resolved through Product Source; configuration matches the returned product name.
- A suggestion is not published until producer confirms it; confirmed forecast stores explicit dates and remains independent of the suggestion.
- Product without suggestions can still receive a manually entered forecast.
