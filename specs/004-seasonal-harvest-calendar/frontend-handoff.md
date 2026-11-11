# Handoff para o frontend: Calendário de Previsão de Colheita

## Escopo

Implementação do backend concluída na feature `004-seasonal-harvest-calendar`. Próxima etapa: integrar a interface do produtor e o calendário consultado por compradores.

## Endpoints

Todos exigem autenticação Sanctum com habilidade `access` e usuário verificado.

### Consultar calendário — produtor e comprador

`GET /v1/harvest-forecasts`

Filtros opcionais:

- `source_product_id`: identificador de produto no Product Source.
- `date_from` e `date_to`: devem ser enviados juntos; retorna intervalos que se sobrepõem ao filtro.
- `page` (padrão `1`) e `per_page` (padrão `15`, máximo `100`).

Resposta usa paginação Laravel (`data`, `links`, `meta`). Cada item contém:

```json
{
  "id": 1,
  "product": { "source_product_id": "0002", "name": "Tomate Italiano" },
  "quantity": 120,
  "start_date": "2027-01-10",
  "end_date": "2027-01-10",
  "producer": { "name": "Sítio Exemplo", "state": "SP" }
}
```

Previsões com `end_date` anterior a hoje não aparecem. Não há filtro por estado.

### Consultar sugestão de safra — produtor

`GET /v1/harvest-season-periods?source_product_id={id}`

Resposta: `{ "data": [{ "source_product_id", "product_name", "start_month", "end_month", "source" }] }`. `data` pode estar vazia; a configuração inicial não possui períodos porque ainda não há uma fonte agronômica apropriada curada. Não assuma meses quando a lista estiver vazia.

### Criar previsão — produtor

`POST /v1/harvest-forecasts`

```json
{
  "source_product_id": "0002",
  "quantity": 120,
  "start_date": "2027-01-10",
  "end_date": "2027-01-10"
}
```

Para data específica, `start_date` e `end_date` são iguais. Período sugerido precisa ser confirmado pelo produtor e enviado como datas explícitas. Quantidade é total estimado para todo o intervalo. Datas precisam ser hoje ou futuras e quantidade positiva. Resposta `201` contém `{ "data": <forecast> }`.

### Editar/remover previsão — produtor proprietário

- `PATCH /v1/harvest-forecasts/{id}` com um ou mais campos de criação; resposta `200` com `{ "data": <forecast> }`.
- `DELETE /v1/harvest-forecasts/{id}`; resposta `204`.
- Registros de outro produtor retornam `404`.

## Comportamento de interface acordado

- Produtor cria previsões independentes por produto, com data específica ou sugestão sazonal confirmada, e quantidade total esperada.
- Compradores autenticados consultam previsões de todos os produtores; exibir nome do produtor e estado como contexto.
- A previsão não é oferta comprável, não altera estoque e some da listagem depois do último dia previsto.
- Favorito/interesse do comprador é apenas visual no MVP; não há endpoint de favorito nem notificação.
- Tratar lista de sugestões vazia permitindo cadastro manual de datas.
