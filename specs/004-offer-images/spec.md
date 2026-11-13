# Feature Specification: Imagens de Ofertas (Offer Images)

**Feature Branch**: `004-offer-images`

**Created**: 2026-11-12

**Status**: Draft

**Input**: Permitir upload de imagens para ofertas de produtos (`Offer`) no momento da criação (`POST /v1/offers`) e via endpoints dedicados (`POST /v1/offers/{offer}/images` e `DELETE /v1/offers/{offer}/images/{image}`). Máximo de 3 imagens por oferta, armazenadas no storage público sob `product_images/<offer-id>/<order>_<id>.<ext>`, sem persistência em tabela de banco de dados. Autorização via Policy e Gate com métodos de linha única verificando a posse do produtor. Exposição no `OfferResource` com formato estruturado contendo `id`, `sequence` e `url`.

---

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Upload de imagens na criação da oferta (Priority: P1)

Como produtor autenticado, quero enviar até 3 imagens via `multipart/form-data` no momento de criar uma nova oferta, para ilustrar visualmente o lote de produto anunciado.

**Why this priority**: A criação da oferta é o ponto de entrada comercial onde o produtor apresenta seus produtos aos varejistas.

**Independent Test**: Um produtor cria uma oferta enviando de 1 a 3 imagens válidas; os arquivos são armazenados no storage público no caminho `product_images/{offer_id}/`, e a resposta da oferta traz o array `images` com id, sequence e url completa.

**Acceptance Scenarios**:
1. **Given** um produtor autenticado e um payload válido com 1 a 3 arquivos de imagem (jpeg, png ou webp), **When** ele envia `POST /v1/offers` em `multipart/form-data`, **Then** a oferta é criada, as imagens são salvas em `product_images/{offer_id}/` com a convenção `{order}_{id}.{ext}`, e a resposta 201 retorna `images` estruturado.
2. **Given** um produtor enviando mais de 3 imagens no payload de criação, **When** submete a requisição, **Then** a validação rejeita com HTTP 422 e nenhuma imagem nem oferta é persistida.
3. **Given** um arquivo enviado que não é imagem válida ou excede o tamanho limite (5MB), **When** a requisição é processada, **Then** a validação rejeita com HTTP 422.

---

### User Story 2 - Adicionar novas imagens a uma oferta existente (Priority: P1)

Como produtor autenticado, quero fazer upload de novas imagens para uma oferta já existente que possua menos de 3 imagens, para enriquecer a apresentação do produto.

**Why this priority**: Permite que o produtor atualize e adicione fotos após o cadastro inicial da oferta sem recriá-la.

**Independent Test**: Um produtor com uma oferta contendo 1 imagem envia 2 novas imagens; o endpoint aceita, armazena nas ordens seguintes e retorna o conjunto atualizado de imagens.

**Acceptance Scenarios**:
1. **Given** uma oferta própria com 1 imagem salva, **When** o produtor envia `POST /v1/offers/{offer}/images` com 2 novas imagens válidas, **Then** as imagens são salvas e a resposta 200/201 retorna a lista com as 3 imagens ordenadas.
2. **Given** uma oferta que já possui 3 imagens salvas, **When** o produtor tenta enviar mais imagens, **Then** a requisição é rejeitada com HTTP 422 indicando limite atingido.
3. **Given** uma oferta própria com 2 imagens salvas, **When** o produtor tenta enviar 2 novas imagens (totalizaria 4), **Then** a requisição é rejeitada com HTTP 422 e nenhuma nova imagem é armazenada.
4. **Given** uma oferta pertencente a outro produtor, **When** o usuário tenta enviar imagens para ela, **Then** o acesso é negado com HTTP 403 (ou 404 caso ocultado por escopo de política).

---

### User Story 3 - Excluir uma imagem de uma oferta (Priority: P1)

Como produtor autenticado, quero remover uma imagem específica de uma oferta própria para retirar fotos desatualizadas ou de baixa qualidade.

**Why this priority**: É essencial para manter a flexibilidade de gerenciamento sem precisar apagar toda a oferta.

**Independent Test**: Um produtor envia a requisição de deleção para o ID de uma imagem existente em sua oferta; o arquivo correspondente é removido do storage e a imagem não é mais listada.

**Acceptance Scenarios**:
1. **Given** uma oferta própria com imagens salvas, **When** o produtor envia `DELETE /v1/offers/{offer}/images/{image}`, **Then** o arquivo correspondente é excluído do storage público e o endpoint responde com HTTP 204 No Content.
2. **Given** um identificador de imagem inexistente na oferta, **When** o produtor tenta excluí-la, **Then** a requisição retorna HTTP 404 Not Found.
3. **Given** uma oferta pertencente a outro produtor, **When** o usuário tenta excluir uma imagem dela, **Then** a autorização é negada via Gate/Policy com HTTP 403.

---

### User Story 4 - Visualizar imagens nas consultas de ofertas (Index e Show) (Priority: P2)

Como usuário (produtor ou varejista), quero receber as URLs completas das imagens cadastradas ao listar ou detalhar ofertas, para poder exibi-las na interface.

**Why this priority**: Garante que o frontend e o consumidor da API tenham acesso imediato e direto aos assets visuais da oferta.

**Independent Test**: Consultar `GET /v1/offers` e `GET /v1/offers/{offer}`; o payload de resposta deve conter o campo `images` com a lista estruturada das imagens presentes no storage.

**Acceptance Scenarios**:
1. **Given** uma oferta com imagens salvas no storage, **When** qualquer usuário autorizado consulta o detalhe (`GET /v1/offers/{offer}`) ou a listagem (`GET /v1/offers`), **Then** cada item contém `images: [{ "id": "<id>", "sequence": <order>, "url": "<storage_url>" }]`.
2. **Given** uma oferta sem imagens salvas no storage, **When** consultada, **Then** o campo `images` retorna um array vazio `[]`.

---

## Constraints & Business Rules

1. **Capacidade máxima**: No máximo 3 imagens por oferta simultaneamente.
2. **Sem persistência em banco**: Nenhuma tabela nova no banco de dados (`no database migrations`). As imagens são gerenciadas diretamente no disco de storage `public`.
3. **Padrão de nomenclatura**:
   - Diretório: `product_images/{offer_id}/`
   - Arquivo: `{order}_{id}.{ext}` (onde `{order}` é a posição 1..3 e `{id}` é o identificador único para deleção).
4. **Segurança e Autorização**:
   - `OfferPolicy` com métodos simples em uma linha:
     ```php
     public function update(User $user, Offer $offer): bool
     {
         return $user->producer?->id === $offer->producer_id;
     }

     public function deleteImage(User $user, Offer $offer): bool
     {
         return $user->producer?->id === $offer->producer_id;
     }
     ```
   - Validação de propriedade executada em Gate/Policy antes de qualquer manipulação de arquivos.
5. **Formatos e Tamanho**:
   - Tipos permitidos: `jpeg`, `png`, `webp`.
   - Tamanho máximo por arquivo: `5MB` (5120 KB).
