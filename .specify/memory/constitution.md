<!--
Sync Impact Report
- Version change: scaffold sem versão → 1.0.0
- Modified principles: nenhum; primeira constituição do projeto
- Added sections: Princípios Centrais; Padrões de Engenharia do Backend;
  Fluxo de Desenvolvimento e Critérios de Qualidade; Governança
- Removed sections: nenhum conteúdo de projeto; somente placeholders do scaffold
- Follow-up TODOs: TODO(RATIFICATION_DATE): registrar a data de ratificação quando o
  rascunho for formalmente aprovado
-->

# Constituição do Cultiva

**Estado**: Rascunho

O Cultiva é uma plataforma B2B que conecta pequenos produtores a varejistas para ampliar o
escoamento da produção, reduzir a dependência de intermediários predatórios e tornar a cadeia de
abastecimento mais justa, acessível e orientada por dados.

## Princípios Centrais

### A.1. O pequeno produtor é o foco

Toda feature DEVE gerar valor real para o pequeno produtor ou para sua conexão com o varejo.
Decisões que prejudiquem sua autonomia, visibilidade ou margem em troca de conveniência técnica
ou comercial DEVEM ser rejeitadas.

Razão: ampliar o escoamento e o poder de negociação do pequeno produtor é o propósito central
do Cultiva.

### A.2. Concorrência justa, nunca predatória

O Cultiva NÃO DEVE estimular guerra de preços nem tratar o menor preço como único critério de
destaque. Busca, recomendação e precificação DEVEM considerar fatores relevantes, incluindo
disponibilidade, localização, adequação à demanda e referências confiáveis de mercado.

Razão: eficiência de mercado não pode reduzir a autonomia ou a margem do produtor.

### A.3. Simplicidade e inclusão digital

Os fluxos DEVEM ser simples, claros e adequados a usuários com pouca familiaridade tecnológica.
O backend DEVE fornecer respostas enxutas e eficientes para dispositivos móveis e redes
instáveis ou lentas.

Razão: acesso limitado a tecnologia ou conectividade não pode impedir o uso do produto.

### A.4. Confiança, segurança e rastreabilidade

Dados pessoais, comerciais e financeiros DEVEM ser protegidos. Operações importantes DEVEM
preservar consistência, impedir ações indevidas e manter registros suficientes para auditoria,
suporte e transparência entre as partes.

Razão: produtores e varejistas só podem negociar com segurança quando dados e operações são
confiáveis e rastreáveis.

### A.5. Inteligência transparente e responsável

Previsões, recomendações e sugestões de preço DEVEM usar dados identificáveis e critérios
compreensíveis. O sistema NÃO DEVE apresentar estimativas como certezas nem manipular decisões
em benefício oculto da plataforma ou de um participante.

Razão: decisões assistidas por dados devem informar o usuário sem retirar sua autonomia.

### A.6. Escopo do produto

O Cultiva aproxima oferta e demanda, apoia decisões e viabiliza transações entre produtores e
varejistas. A execução da logística física NÃO faz parte do produto enquanto uma especificação
futura não a incluir expressamente.

Razão: um limite explícito evita compromissos operacionais e complexidade não aprovados.

### A.7. Especificações alinhadas e simples

Cada feature DEVE demonstrar alinhamento com o propósito e os princípios desta constituição.
Regras de negócio mutáveis, percentuais, algoritmos, estados e detalhes técnicos DEVEM pertencer
à especificação da feature, não a esta constituição. Critérios de aceite DEVEM cobrir permissões
por perfil, falhas relevantes, consistência dos dados e impacto sobre produtor e varejista.
Operações críticas, especialmente estoque, pedidos, pagamentos e dados pessoais, DEVEM ter
validação e testes proporcionais ao risco. A solução mais simples que cumpra a necessidade DEVE
prevalecer; complexidade especulativa DEVE ser evitada.

Razão: princípios estáveis governam o produto, enquanto detalhes mutáveis permanecem testáveis e
versionáveis nas especificações.

## Padrões de Engenharia do Backend

### B.I. Organização orientada ao domínio

O código DEVE ser organizado por módulo de domínio, não apenas por tipo técnico. Cada entidade
principal DEVE possuir módulo próprio nesta estrutura:

```text
domain/
├── Base/
│   ├── Contracts/
│   └── Exceptions/
├── Models/
│   └── <Entity>/
│       ├── <Entity>.php
│       ├── Actions/
│       ├── DTO/
│       ├── Exceptions/
│       ├── Http/
│       │   ├── Controllers/
│       │   └── Requests/
│       ├── Events/
│       ├── Listeners/
│       └── Jobs/
├── Integrations/
│   └── <Service>/
│       ├── Adapters/
│       ├── Actions/
│       ├── DTO/
│       └── Exceptions/
└── Providers/
```

- Código específico de entidade DEVE permanecer no módulo correspondente.
- `Base/` DEVE conter somente contratos e comportamentos realmente compartilhados.
- APIs e serviços externos sem Eloquent Model DEVEM ficar em `Integrations/`.
- O namespace DEVE refletir o caminho físico do arquivo.
- Dependência entre módulos DEVE ocorrer por contrato ou interface somente quando existir
  necessidade real de substituição.

Razão: proximidade entre comportamento e domínio reduz navegação, acoplamento e duplicação.

### B.II. Actions como unidade de negócio

Toda operação de negócio DEVE ser implementada por uma Action. Uma Action:

- DEVE representar uma única operação;
- DEVE ser nomeada `<Verbo><Substantivo>Action`;
- DEVE possuir somente `execute()` como método público;
- DEVE receber dependências pelo construtor;
- DEVE ser independente entre execuções;
- DEVE possuir retorno explicitamente tipado;
- NÃO DEVE receber `Request`, `FormRequest` ou outro objeto HTTP;
- NÃO DEVE retornar array genérico quando existir contrato tipável;
- PODE possuir helpers privados ou protegidos;
- PODE ser chamada por controllers, jobs, commands e listeners.

Controllers DEVEM apenas receber entrada validada, construir DTO, executar Action e transformar o
resultado em resposta HTTP. Controllers NÃO DEVEM conter regras de negócio.

Razão: a Action oferece um ponto único, explícito e testável para cada comportamento.

### B.III. Contratos de dados

Dados que atravessam camadas DEVEM possuir contrato explícito. DTOs DEVEM ser classes `readonly`,
possuir propriedades tipadas, ser independentes da camada HTTP, ficar em `DTO/` e ser nomeados
`<Substantivo>DTO`. DTOs DEVERIAM fornecer construtor nomeado `from()`; uma exceção exige
justificativa junto à mudança.

Retornos de Actions DEVEM usar Entity, `Collection`, `LengthAwarePaginator`, Value Object, DTO
específico de resultado ou outro tipo concreto justificável. Arrays crus NÃO DEVEM representar
contratos de domínio.

Razão: contratos tipados tornam dependências visíveis e evitam acoplamento implícito.

### B.IV. Validação em duas camadas

A validação DEVE respeitar a fronteira responsável pela regra:

- `FormRequest` DEVE validar campos obrigatórios, tipos, formato, tamanho, regex e restrições
  básicas de entrada.
- Action DEVE validar invariantes, estado permitido da entidade, unicidade de negócio, regras que
  envolvam banco, relações entre entidades e combinações semanticamente inválidas.
- Action NÃO DEVE confiar que foi chamada exclusivamente via HTTP.

Razão: formato pertence ao transporte; validade de negócio pertence ao domínio.

### B.V. Persistência e consistência

Eloquent DEVE ser usado diretamente para persistência enquanto atender ao caso. Repository NÃO
DEVE envolver consultas Eloquent simples. Repository somente DEVERIA existir quando os dados
vierem de duas ou mais fontes, uma consulta complexa for reutilizada por múltiplas Actions ou a
persistência precisar ser substituível por necessidade concreta; qualquer exceção exige
justificativa junto à mudança.

Action que grava em duas ou mais tabelas DEVE usar `DB::transaction()`. A transação DEVE conter
toda alteração necessária para preservar consistência. Falha parcial NÃO DEVE deixar estado
persistido incompleto.

Razão: Eloquent cobre a persistência comum, enquanto transações protegem operações críticas.

### B.VI. Exceções e falhas

Falhas esperadas do domínio DEVEM usar exceções específicas ou a exceção-base da aplicação.
Exceções DEVEM comunicar a regra violada, possuir nome semântico e carregar status HTTP
explicitamente quando representarem resposta da aplicação. Elas NÃO DEVEM expor detalhes
internos, credenciais ou dados sensíveis. Retornos booleanos NÃO DEVEM substituir exceções quando
o chamador precisar conhecer a causa da falha.

Razão: falhas semânticas e seguras formam um contrato tratável entre domínio e chamadores.

### B.VII. Integrações externas

Clientes de terceiros DEVEM ficar fora dos módulos Eloquent, em `Integrations/`. Integrações:

- DEVEM possuir DTOs próprios para entrada e saída;
- DEVEM traduzir erros externos em exceções da aplicação;
- DEVERIAM usar Adapter quando a interface externa contaminar o domínio; omitir o Adapter exige
  justificativa se essa contaminação existir;
- NÃO DEVEM espalhar payloads específicos do fornecedor pela aplicação;
- DEVEM permanecer substituíveis nos pontos necessários para teste.

Jobs, listeners e Actions DEVEM depender do contrato local, não de detalhes do SDK externo.

Razão: a fronteira local contém mudanças e falhas de fornecedores.

### B.VIII. Eventos e processamento assíncrono

Eventos DEVEM representar fatos já ocorridos, como `ProducerCreated`, `OrderApproved` e
`PaymentProcessed`; NÃO DEVEM ser nomeados como comandos ou intenções. Listeners DEVEM tratar
reações independentes. Jobs DEVEM tratar trabalho assíncrono ou custoso.

Jobs e listeners NÃO DEVEM duplicar regras de negócio e DEVEM delegar para Actions. Handlers de
jobs DEVEM receber Actions por injeção. Falhas relevantes DEVEM ser registradas com contexto
suficiente e sem dados sensíveis.

Razão: fatos desacoplam reações sem criar uma segunda implementação da regra de negócio.

### B.IX. Abstração somente mediante evidência

Action é o serviço padrão. Eloquent é o repository padrão. NÃO DEVEM ser criados preventivamente
`BaseService`, `GenericRepository`, `Helper`, `Utils`, interface com implementação única sem
necessidade de substituição, factory para um único produto ou configuração para comportamento
invariável.

Antes de qualquer extração, as ocorrências DEVEM ser verificadas no código. Os sinais mínimos são:

- Facade: coordenação repetida em três ou mais Actions, ou Action com quatro ou mais dependências
  relevantes;
- Service: comportamento usado diretamente fora das Actions ou componente necessariamente
  stateful;
- Repository: múltiplas fontes de dados ou consulta complexa reutilizada;
- Strategy: dois ou mais algoritmos intercambiáveis reais;
- Factory: seleção real entre implementações;
- Adapter: interface externa incompatível com o contrato local;
- Composite: estrutura genuinamente recursiva;
- Template Method: processo fixo com etapas variáveis comprovadas.

Razão: padrões resolvem pressão existente; não justificam complexidade especulativa.

### B.X. Convenções de nomes e arquivos

| Elemento | Convenção |
|---|---|
| Eloquent Model | `<Entity>.php` |
| Action | `<Verb><Noun>Action.php` |
| DTO | `<Noun>DTO.php` |
| Exceção | `<Condition>Exception.php` |
| Evento | `<Subject><PastTense>.php` |
| Listener | `<Verb><Subject>Listener.php` |
| Job | `<Verb><Subject>Job.php` |
| Adapter | `<Provider>Adapter.php` |
| Strategy | `<Concept>Strategy.php` |
| Factory | `<Subject>Factory.php` |
| FormRequest | `<Operation><Entity>Request.php` |

Nomes genéricos como `process()`, `data()`, `manager`, `helper` ou `utils` NÃO DEVEM esconder
responsabilidade.

Razão: nomes explícitos tornam responsabilidade e localização previsíveis.

## Fluxo de Desenvolvimento e Critérios de Qualidade

### B.XI. Desenvolvimento orientado por testes

Novas funcionalidades, correções e mudanças de requisito DEVEM seguir Red-Green-Refactor:

1. Red: escrever o teste e confirmar que falha pela razão esperada.
2. Green: implementar o mínimo necessário.
3. Refactor: melhorar a estrutura mantendo os testes verdes.

Bug NÃO DEVE ser corrigido sem teste que o reproduza. Testes DEVEM descrever comportamento
observável, não detalhes internos irrelevantes.

Razão: a falha inicial prova que o teste protege a mudança, e o ciclo limita implementação
desnecessária.

### B.XII. Testes Unit e Feature

Feature test DEVE executar o ciclo real:

`Route → Middleware → FormRequest → Controller → Action → Transformer → Database`

Feature test:

- NÃO DEVE mockar a Action chamada pelo controller;
- NÃO DEVE mockar models, requests ou transformers internos;
- DEVE persistir estado com factories reais;
- NÃO DEVE simular persistência com `new Model`, `make()` ou `setRelation()`;
- PODE mockar somente fronteiras externas indisponíveis localmente;
- DEVE verificar o contrato HTTP e os efeitos colaterais relevantes.

Mockar a Action transforma o cenário em teste unitário de controller, não Feature test.

Unit test DEVE isolar uma classe ou comportamento específico. Actions com ramificações, cálculos
ou falhas independentes DEVERIAM possuir testes unitários; a ausência exige justificativa baseada
na cobertura existente. Dependências externas PODEM ser mockadas com Mockery. Fluxos simples
integralmente cobertos por Feature test NÃO exigem Unit test redundante.

Razão: Feature tests protegem a integração real, enquanto Unit tests cobrem lógica isolada onde
agregam sinal.

### B.XIII. Organização dos testes

Testes DEVEM espelhar o caminho do código. Exemplo:

```text
domain/Models/Producer/Actions/CreateProducerAction.php
tests/Unit/domain/Models/Producer/Actions/CreateProducerActionTest.php
```

Controllers com múltiplas rotas independentes DEVEM dividir testes por fluxo. Testes DEVEM:

- estender `Tests\TestCase`;
- usar nomes `test_should_<expected_behavior>`;
- usar `$sut` para o subject under test;
- separar Arrange, Expects, Action e Assert;
- permanecer explícitos e autocontidos;
- evitar helpers genéricos que escondam preparação ou assertions;
- usar data providers para combinações equivalentes de entrada;
- usar `assertEqualsCanonicalizing` quando a ordem não for regra de negócio.

Factories DEVEM ser instanciadas diretamente, como `ProducerFactory::new()->create()`.
`Model::factory()` NÃO DEVE ser usado quando a convenção direta estiver estabelecida.

Razão: espelhamento e convenções explícitas reduzem busca e tornam a intenção do teste legível.

### B.XIV. Fixtures

Payloads externos e respostas estáticas DEVEM ficar em `tests/Fixtures/`, espelhando seu contexto.
Fixtures DEVEM ser arquivos `.php`, retornar array ou string multilinha por heredoc, ser carregadas
via `require base_path(...)` e não conter segredos reais ou dados pessoais. Fixtures NÃO DEVEM ser
arquivos `.json` avulsos.

Razão: fixtures executáveis e centralizadas evitam formatos paralelos e exposição de dados.

### B.XV. Ambiente de execução

PHP, Composer, Artisan e testes DEVEM executar no ambiente Docker oficial:

```bash
docker compose exec php php artisan test
docker compose exec php vendor/bin/phpunit
docker compose exec php php artisan <command>
docker compose exec php composer <command>
```

Execução equivalente diretamente no host NÃO constitui validação oficial. Laravel Pint NÃO DEVE
ser executado; a formatação segue a ferramenta definida pelo ambiente de desenvolvimento.

Razão: um ambiente único reduz divergências entre desenvolvimento e validação.

### B.XVI. Critérios de conclusão

Uma mudança somente está concluída quando:

- o comportamento possui cobertura proporcional ao risco;
- o novo teste falhou pela razão esperada antes da implementação;
- os testes relevantes estão verdes;
- Feature tests preservam o ciclo real;
- efeitos de banco estão verificados;
- contratos públicos permanecem tipados;
- transações protegem gravações múltiplas;
- nenhuma abstração sem evidência foi adicionada;
- arquivos e namespaces seguem as convenções;
- os comandos foram executados no ambiente oficial;
- a feature demonstra alinhamento com os princípios de produto.

Razão: conclusão exige evidência de comportamento, consistência e aderência ao propósito.

## Governança

### Autoridade

Esta constituição prevalece sobre decisões locais de implementação e governa o produto, a
arquitetura e os testes. README, skills e documentos auxiliares PODEM explicar sua aplicação, mas
NÃO DEVEM contrariá-la.

Os termos normativos significam:

- **DEVE / NÃO DEVE**: regra obrigatória;
- **DEVERIA**: regra padrão; qualquer exceção exige justificativa;
- **PODE**: decisão opcional.

### Exceções

Toda exceção a uma regra obrigatória DEVE ser documentada junto à mudança, explicar a restrição
concreta, delimitar o alcance, definir a condição de remoção e receber aprovação explícita durante
a revisão.

### Alterações e versionamento

Toda alteração constitucional DEVE incluir motivação, avaliação de impacto sobre princípios,
código e testes existentes, estratégia de migração quando necessária, nova versão e data da
alteração.

O versionamento semântico governa esta constituição:

- **MAJOR**: remoção ou redefinição incompatível de princípio;
- **MINOR**: novo princípio ou expansão normativa material;
- **PATCH**: esclarecimento sem mudança normativa.

### Revisão de conformidade

Toda revisão de código DEVE verificar alinhamento com os princípios de produto, responsabilidade
da Action, fronteiras entre HTTP e domínio, contratos tipados, consistência transacional, política
de mocks, cobertura do fluxo real e necessidade comprovada de abstrações. Violação não
justificada DEVE bloquear a integração.

**Version**: 1.0.0 | **Ratified**: TODO(RATIFICATION_DATE): rascunho ainda não ratificado | **Last Amended**: 2026-09-03
