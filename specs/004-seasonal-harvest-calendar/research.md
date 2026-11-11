# Research: Calendário de Previsão de Colheita

## Decisão 1: Origem da sugestão sazonal

- **Decision**: Usar referências de período sazonais mantidas em configuração do backend por nome de produto, sem serviço externo em tempo de consulta ou CRUD administrativo. Cada período é uma sugestão geral, o produtor confirma antes de publicar e pode substituí-la por data específica. Produto sem referência sazonal continua disponível para previsão manual.
- **Rationale**: O `GetProductAction` e o contrato do Product Source fornecem identidade e nome, sem períodos de safra. Uma referência local evita nova dependência externa; configuração fixa evita modelar fluxo administrativo que não faz parte do MVP.
- **Alternatives considered**: Tratar `harvest_date` de ofertas como fonte de sazonalidade foi rejeitado porque é data de uma oferta de estoque, não referência geral do produto. Consultar CEAGESP como fonte universal foi rejeitado: a metodologia publicada analisa dados do ETSP, entreposto de São Paulo, e trata sazonalidade de comercialização; não representa uma previsão nacional de colheita.

## Decisão 2: Produto e quantidade da previsão

- **Decision**: Validar produto via `GetProductAction` e persistir o id e nome retornados como snapshot; armazenar quantidade como inteiro, seguindo a convenção existente em ofertas.
- **Rationale**: `CreateOfferAction` já valida o produto via Product Source e persiste id/nome. Reaproveitar o contrato mantém produtos inexistentes fora do domínio local e permite exibir nome sem uma chamada externa por item na listagem.
- **Alternatives considered**: Fazer chamada externa ao listar previsões foi rejeitado por latência e indisponibilidade; derivar previsões de ofertas foi rejeitado porque previsão não é estoque.

## Decisão 3: Estado do produtor

- **Decision**: Expor estado do endereço associado à empresa do produtor como contexto de identificação; não usar estado para classificar, sugerir ou filtrar períodos.
- **Rationale**: O estado já existe em `Address`; esta decisão atende à visualização combinada sem alterar o conceito geral por produto pedido para o MVP.

## Evidence

- `domain/Integrations/ProductSource/Actions/GetProductAction.php:17-33` — Product Source resolve produto e trata 404/falhas; não inclui sazonalidade.
- `domain/Integrations/ProductSource/DTO/ProductDTO.php:7-19` — produto local possui `id` e `name`.
- `domain/Models/Offer/Actions/CreateOfferAction.php:19-38` — oferta valida produto e guarda id/nome.
- `domain/Models/Offer/Offer.php:41-63` — estoque usa quantidade inteira e data de colheita da oferta.
- `domain/Models/Address/Address.php:15-31` — endereço guarda estado associado à empresa.
- [CEAGESP: Sazonalidade](https://ceagesp.gov.br/sazonalidade/) — explica metodologia de análise mensal dos últimos três anos para produtos comercializados no ETSP.
