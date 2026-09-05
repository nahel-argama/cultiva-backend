# Phase 0 Research: Ofertas de Produtos

## 1. Estrutura da feature

**Decision**: Criar módulos de domínio separados para `Offer` e `Category`, mais uma integração `ProductSource`. Cada operação pública terá uma Action com `execute()` tipado. Eloquent será usado diretamente.

**Rationale**: É a organização já exigida pela constituição. As consultas são simples e não justificam repository, service genérico ou facade.

**Alternatives considered**:

- Um módulo único com Offer e Category: rejeitado porque Category é entidade própria e possui consulta/seeder independentes.
- Repository para Offer: rejeitado porque há uma única fonte persistente e consultas diretas.
- Service CRUD: rejeitado; Actions já são a unidade de negócio.

## 2. Autenticação, ability e perfis

**Decision**: Proteger todas as rotas com `auth:sanctum` e o middleware nativo de ability `access`. Registrar um middleware parametrizado `profile`, usando `profile:producer` nas rotas administrativas/categorias e `profile:retailer` na listagem comercial.

**Rationale**: Access e refresh tokens pertencem ao mesmo usuário e diferem pelas abilities `access` e `refresh`; apenas autenticar com Sanctum permitiria o uso indevido do refresh token. Um middleware de perfil atende os dois perfis reais sem duplicação.

**Alternatives considered**:

- Gravar o perfil nas abilities do token: rejeitado porque duplica estado mutável do usuário.
- Policies e Gates: rejeitados; não há padrão existente e o escopo pela relação resolve ownership com menos código.
- Dois middlewares de perfil: rejeitados porque repetiriam a mesma comparação.

## 3. Resolução do produtor e isolamento de propriedade

**Decision**: O controller obtém o `User` autenticado e sua relação `producer` após os middlewares, passando um `Producer` tipado à Action. Criação e consultas administrativas partem de `$producer->offers()`. Show/update recebem o ID cru e fazem a busca já limitada ao produtor; route model binding global de Offer não será usado.

**Rationale**: `producer_id` nunca atravessa a fronteira HTTP e uma oferta alheia se comporta como inexistente (404), sem vazamento de dados. A Action continua segura fora do controller porque a consulta exige o proprietário.

**Alternatives considered**:

- Aceitar `producer_id` e comparar com o token: rejeitado pela especificação e por aumentar a superfície de ataque.
- Route model binding irrestrito: rejeitado porque resolveria a oferta antes de aplicar ownership.
- Global scope de produtor: rejeitado porque impediria a listagem comercial do varejista.

## 4. Contrato do `product-source`

**Decision**: Consumir `GET {PRODUCT_SOURCE_BASE_URL}/products/{id}`, configurando a base com sufixo `/api`. O ID será convertido para string e codificado sem remover zeros à esquerda. A resposta válida exige `id` textual igual ao solicitado e ao menos um nome utilizável; o snapshot usa `name` não vazio ou, em fallback, `normal_name`.

**Rationale**: O serviço existente expõe `GET /api/products/{product_id}`, consulta um ID `TEXT` e retorna `id`, `name|null`, `normal_name` e `created_at`. O nome de apresentação pode ser nulo.

**Alternatives considered**:

- Usar `normal_name` sempre: rejeitado porque descarta o nome de apresentação criado para usuários.
- Converter o ID definitivamente para inteiro: rejeitado porque perderia zeros à esquerda e contrariaria o catálogo.
- Copiar todos os campos externos para Offer: rejeitado; a feature precisa apenas do ID e nome snapshotado.

## 5. Implementação mínima da integração

**Decision**: Criar apenas `GetProductAction` e `ProductDTO` em `domain/Integrations/ProductSource`, usando o cliente HTTP do Laravel e `config/services.php`. Timeout padrão de 5 segundos, configurável, sem retry.

**Rationale**: Existe um fornecedor e uma operação. A Action contém o payload externo e entrega somente um DTO local; portanto a interface externa não contamina o domínio. O cliente HTTP já instalado suporta base URL, timeout, status e falhas de conexão.

**Alternatives considered**:

- Client + Config + Adapter + Contract + provider resolver: rejeitados como cinco camadas para uma chamada.
- Nova biblioteca HTTP: rejeitada; não acrescenta capacidade necessária.
- Retry automático: rejeitado porque aumenta a espera do produtor e não foi solicitado.

## 6. Tradução de falhas externas

**Decision**: Mapear 404 externo para `CultivaException` 422. Mapear timeout, DNS/conexão, demais 4xx/5xx, ID divergente e payload sem nome utilizável para 503. A chamada e todas as validações ocorrem antes do único insert/update.

**Rationale**: Preserva o contrato solicitado e impede escrita parcial. Falha estrutural de payload é indisponibilidade do catálogo, não erro do produtor.

**Alternatives considered**:

- Repassar status/mensagem externa: rejeitado porque expõe detalhes e acopla os consumidores ao fornecedor.
- Tratar payload inválido como 422: rejeitado porque o produtor não consegue corrigir uma resposta defeituosa do catálogo.

## 7. Correção obrigatória de `CultivaException`

**Decision**: Antes de depender dos novos status, corrigir o construtor de `CultivaException` para inicializar `HttpException` com `parent::__construct($statusCode, $message, $previous)` e preservar `context` local. Manter e executar o teste unitário existente que exige `getStatusCode()` correto.

**Rationale**: O construtor atual sombreia `statusCode` e não inicializa o estado privado da classe pai; assim 422/503 não são confiáveis. Corrigir o ponto compartilhado é menor que contornar o problema em cada caller.

**Alternatives considered**:

- Montar respostas 422/503 diretamente nos controllers: rejeitado porque duplicaria tratamento e deixaria outros usos da exceção quebrados.
- Nova hierarquia de exceções: rejeitada porque `CultivaException` é final e a correção simples basta.

## 8. Persistência e visibilidade

**Decision**: Criar `categories` e `offers` em uma migration. Offer usa preço `numeric(12,2)`, quantidades inteiras, status textual `active|inactive`, default `inactive`, timestamps e constraints para preço/estoque/status. Visibilidade não será persistida: `status = active AND total_quantity > reserved_quantity` será aplicada na consulta e no transformer.

**Rationale**: Numeric preserva dinheiro; checks protegem invariantes fora do HTTP; visibilidade derivada evita sincronização de uma coluna redundante. Cada operação grava apenas Offer, então não requer transação e não manterá uma transação aberta durante HTTP.

**Alternatives considered**:

- `float` para preço: rejeitado por perda de precisão.
- Preço em centavos: válido, mas adicionaria conversões sem ganho para PostgreSQL.
- Coluna `is_visible`: rejeitada porque pode divergir de status e estoque.
- Soft delete: rejeitado porque exclusão está fora do escopo.

## 9. Categorias seedadas

**Decision**: Usar IDs bigint fixos de 1 a 5 e nomes únicos: Frutas, Legumes, Verduras, Tubérculos e raízes, Grãos e cereais. `CategorySeeder` fará upsert idempotente e será chamado por `DatabaseSeeder`. Não haverá CRUD de categoria.

**Rationale**: IDs explícitos permanecem estáveis entre ambientes e seguem o padrão numérico das entidades existentes. A carga é pequena e definida pela especificação.

**Alternatives considered**:

- Enum de categoria: rejeitado porque a especificação exige tabela.
- Slug como chave primária: rejeitado para manter FKs e models coerentes com o padrão existente.
- Hierarquia/status de categorias: rejeitados porque não foram solicitados.

## 10. Contrato HTTP e paginação

**Decision**: Expor cinco rotas de produtor e uma de varejista sob `/v1`. Listagens serão paginadas por `page` e `per_page`, com default 15 e máximo 100, ordenadas da mais recente para a mais antiga. Respostas usam `data` e, em listas paginadas, `meta`. Oferta expõe status e visibilidade calculada, mas não `producer_id`.

**Rationale**: O prefixo segue o backend atual, a paginação limita payloads em redes lentas e a ausência de `producer_id` reduz exposição desnecessária.

**Alternatives considered**:

- Lista ilimitada: rejeitada por desempenho e tamanho de resposta.
- Uma rota que muda conforme o perfil: rejeitada porque cria comportamento implícito e ramificações desnecessárias.
- Ordenar por menor preço: rejeitado por não ser requisito e contrariar concorrência não predatória.

## 11. Estratégia TDD e ambiente

**Decision**: Desenvolver cada fluxo em Red-Green-Refactor. Feature tests executam route, middleware, FormRequest, controller, Action, transformer e PostgreSQL reais; somente o HTTP do `product-source` é fake. O teste unitário adicional cobre as ramificações da integração. Fixtures externas serão arquivos PHP. O banco de testes será `app_testing`, nunca `app`.

**Rationale**: É a política obrigatória do projeto. A configuração atual de exemplo aponta testes para `app`; isolar `app_testing` evita apagar ou alterar dados de desenvolvimento durante `RefreshDatabase`.

**Alternatives considered**:

- Mockar Actions em Feature tests: rejeitado porque não testa a feature.
- SQLite em memória: rejeitado porque as migrations existentes usam PostGIS e os checks devem ser provados em PostgreSQL.
- Unit tests para cada CRUD simples: rejeitados como duplicação dos Feature tests.
