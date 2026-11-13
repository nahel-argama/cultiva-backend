# Implementation Plan: Imagens de Ofertas (Offer Images)

**Branch**: `004-offer-images` | **Date**: 2026-11-12 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/004-offer-images/spec.md`

## Summary

Permitir o upload de até 3 imagens por oferta (`Offer`) durante a criação (`POST /v1/offers`) e através de endpoints dedicados de sub-recurso (`POST /v1/offers/{offer}/images` e `DELETE /v1/offers/{offer}/images/{image}`). 

As imagens serão armazenadas no disco `public` do Laravel sob o diretório `product_images/{offer_id}/` com a convenção de nome `{order}_{id}.{ext}`, sem nenhuma persistência em banco de dados. 

A autorização é governada por uma `OfferPolicy` com métodos one-liner checando a propriedade do produtor (`$user->producer?->id === $offer->producer_id`). O `OfferResource` expõe as imagens em `index` e `show` com `{ id, sequence, url }`.

---

## Technical Context

**Language/Version**: PHP 8.3+

**Primary Dependencies**: Laravel 12+, Laravel Storage (`Storage::disk('public')`), Laravel Gates & Policies; nenhuma biblioteca externa adicional

**Storage**: Filesystem via Laravel Storage (`disk: public`), sem tabelas de banco de dados adicionais

**Testing**: PHPUnit 12.5, executado dentro do container Docker `php` (`docker compose exec php php artisan test`)

**Target Platform**: Serviço HTTP Linux sob Docker Compose

**Constraints**:
- Máximo de 3 imagens ativas por oferta.
- Sem persistência em banco de dados: ordenação e identificadores codificados diretamente nos nomes dos arquivos no disco.
- Validação estrita de propriedade do produtor autenticado via Policy/Gate antes de qualquer modificação de arquivos.
- Respostas de `index` e `show` serializadas com URLs completas acessíveis publicamente.

---

## Constitution & Architecture Check

| Critério Arquitetural | Status | Evidência no Desenho |
|---|---|---|
| **Ambiente de Execução** | PASS | Testes e comandos executados no container `php` via Docker Compose. Sem Laravel Pint. |
| **Organização por Domínio** | PASS | Tudo encapsulado sob `domain/Models/Offer/` (`Actions`, `Policies`, `DTO`, `Requests`, `Controllers`). |
| **Action Pattern** | PASS | Apenas `execute()` público em cada Action (`UploadOfferImagesAction`, `DeleteOfferImageAction`, `GetOfferImagesAction`). |
| **DTOs Tipados** | PASS | `OfferImageDTO` com propriedades `readonly` (`id`, `sequence`, `url`, `path`). |
| **Validação em Duas Camadas** | PASS | FormRequests (`UploadOfferImagesRequest`, `StoreOfferRequest`) validam arquivos (mime, size, count); Actions validam o teto de 3 imagens por oferta. |
| **Políticas e Gates** | PASS | `OfferPolicy` dedicada com checagem de propriedade de uma linha, conforme solicitado. |
| **Sem Abstrações Desnecessárias** | PASS | Sem repositories, services genéricos ou tabelas extras; interação direta com o disco de storage. |
| **TDD Rigoroso (Red-Green-Refactor)** | PASS | Testes unitários para Actions e Feature tests para controllers com ciclo Red comprovado antes do Green. |

---

## Project Structure & File Layout

```text
domain/Models/Offer/
├── Actions/
│   ├── CreateOfferAction.php              # Atualizado para receber e orquestrar imagens se fornecidas
│   ├── UploadOfferImagesAction.php        # Novo: salva arquivos no disco respeitando o limite de 3
│   ├── DeleteOfferImageAction.php         # Novo: localiza e remove arquivo do disco
│   └── GetOfferImagesAction.php           # Novo: lê diretório do disco e retorna array de OfferImageDTO
├── DTO/
│   ├── CreateOfferDTO.php                 # Atualizado para suportar array opcional de UploadedFile
│   └── OfferImageDTO.php                  # Novo: readonly class (id, sequence, url, path)
├── Policies/
│   └── OfferPolicy.php                    # Novo: políticas one-liner para update e deleteImage
├── Controllers/
│   ├── OfferController.php                # Atualizado: lida com upload inicial em multipart
│   └── OfferImageController.php           # Novo: lida com POST e DELETE de imagens da oferta
├── Requests/
│   ├── StoreOfferRequest.php              # Atualizado: adiciona regras para 'images' (max: 3, image types)
│   └── UploadOfferImagesRequest.php       # Novo: regras para envio avulso de imagens
└── Resources/
    └── OfferResource.php                  # Atualizado: inclui 'images' serializado

routes/api/
└── offers.php                             # Atualizado com as rotas de imagens protegidas por profile e policy

tests/
├── Unit/domain/Models/Offer/
│   ├── Actions/
│   │   ├── UploadOfferImagesActionTest.php
│   │   ├── DeleteOfferImageActionTest.php
│   │   └── GetOfferImagesActionTest.php
│   └── Policies/
│       └── OfferPolicyTest.php
└── Feature/domain/Models/Offer/
    ├── Controllers/
    │   ├── OfferController/
    │   │   ├── StoreWithImagesTest.php
    │   │   ├── IndexImagesTest.php
    │   │   └── ShowImagesTest.php
    │   └── OfferImageController/
    │       ├── UploadTest.php
    │       └── DeleteTest.php
```

---

## Detailed Design

### 1. Storage Strategy (No DB Persistence)

- **Diretório de Armazenamento**:
  `product_images/{offer_id}/` no disco `public`.

- **Convenção de Nomenclatura**:
  `{order}_{id}.{ext}`
  - `{order}`: número inteiro (1 a 3) indicando a ordem de apresentação.
  - `{id}`: identificador único (gerado via sequência ou string alfanumérica curta/timestamp) para permitir referência direta no endpoint de remoção sem ambiguidade.
  - Exemplo: `1_img01.jpg`, `2_img02.png`.

- **Algoritmo de Leitura (`GetOfferImagesAction`)**:
  1. `Storage::disk('public')->files("product_images/{$offer->id}")`.
  2. Filtrar arquivos válidos pelo padrão regex `/^(\d+)_([a-zA-Z0-9_\-]+)\.([a-zA-Z0-9]+)$/`.
  3. Mapear para `OfferImageDTO`:
     - `id`: capturado do regex.
     - `sequence`: (int) `order`.
     - `url`: `Storage::disk('public')->url($filePath)`.
     - `path`: `$filePath`.
  4. Ordenar a lista resultante por `sequence` ASC.

- **Algoritmo de Upload (`UploadOfferImagesAction`)**:
  1. Ler arquivos existentes no diretório via `GetOfferImagesAction`.
  2. Se `(count(existentes) + count(novos)) > 3`, lançar `CultivaException(422, "Limite de 3 imagens por oferta atingido.")`.
  3. Determinar os próximos números de ordem disponíveis (entre 1, 2, 3) que ainda não estão ocupados.
  4. Para cada arquivo novo:
     - Gerar ID único para o arquivo.
     - Salvar em `product_images/{offer_id}/{order}_{id}.{ext}` via `Storage::disk('public')->putFileAs(...)`.
  5. Retornar a lista atualizada de `OfferImageDTO`.

- **Algoritmo de Exclusão (`DeleteOfferImageAction`)**:
  1. Buscar arquivos no diretório `product_images/{offer_id}/`.
  2. Localizar o arquivo cujo nome contém `_{imageId}.`.
  3. Se não encontrado, lançar `CultivaException(404, "Imagem não encontrada.")` (ou `ModelNotFoundException`).
  4. Deletar o arquivo físico com `Storage::disk('public')->delete(...)`.

---

### 2. Authorization (Policy & Gates)

Criar [`OfferPolicy`](file:///home/henri/Projects/cultiva/backend/domain/Models/Offer/Policies/OfferPolicy.php):

```php
namespace Cultiva\Models\Offer\Policies;

use Cultiva\Models\Offer\Offer;
use Cultiva\Models\User\User;

final class OfferPolicy
{
    public function update(User $user, Offer $offer): bool
    {
        return $user->producer?->id === $offer->producer_id;
    }

    public function deleteImage(User $user, Offer $offer): bool
    {
        return $user->producer?->id === $offer->producer_id;
    }
}
```

Registrar no [`AppServiceProvider`](file:///home/henri/Projects/cultiva/backend/domain/Providers/AppServiceProvider.php) ou via `Gate::policy(Offer::class, OfferPolicy::class)`.

No Controller:
```php
Gate::authorize('update', $offer);
// ou
Gate::authorize('deleteImage', $offer);
```

---

### 3. API Contract & Endpoints

#### A. Criação de Oferta com Imagens
- **Rota**: `POST /v1/offers`
- **Content-Type**: `multipart/form-data`
- **Novos Campos**:
  - `images`: array de arquivos (opcional, máximo 3 itens).
  - Cada item: arquivo de imagem (`jpeg,png,webp`), máximo 5120 KB (5MB).
- **Resposta**: 201 Created com objeto da oferta incluindo:
  ```json
  "images": [
    {
      "id": "img01",
      "sequence": 1,
      "url": "http://localhost:8000/storage/product_images/1/1_img01.jpg"
    }
  ]
  ```

#### B. Upload de Novas Imagens para Oferta
- **Rota**: `POST /v1/offers/{offer}/images`
- **Middleware**: `auth:sanctum`, `ability:access`, `verified`, `profile:producer`
- **Content-Type**: `multipart/form-data`
- **Campos**:
  - `images`: array de arquivos (mínimo 1, máximo 3).
- **Validação**: FormRequest valida arquivos; Action valida se total com existentes $\le$ 3.
- **Resposta**: 200 OK com array atualizado de `images`:
  ```json
  {
    "data": [
      {
        "id": "img01",
        "sequence": 1,
        "url": "http://localhost:8000/storage/product_images/1/1_img01.jpg"
      },
      {
        "id": "img02",
        "sequence": 2,
        "url": "http://localhost:8000/storage/product_images/1/2_img02.jpg"
      }
    ]
  }
  ```

#### C. Exclusão de Imagem
- **Rota**: `DELETE /v1/offers/{offer}/images/{image}`
- **Middleware**: `auth:sanctum`, `ability:access`, `verified`, `profile:producer`
- **Autorização**: Gate `deleteImage`
- **Resposta**: 204 No Content

#### D. Listagem e Detalhe de Ofertas
- **Rotas**: `GET /v1/offers` e `GET /v1/offers/{offer}`
- **Resposta**: Inclui o campo `images` em cada item de oferta:
  ```json
  {
    "data": {
      "id": 1,
      "product_name": "Tomate Italiano",
      ...
      "images": [
        {
          "id": "img01",
          "sequence": 1,
          "url": "http://localhost:8000/storage/product_images/1/1_img01.jpg"
        }
      ]
    }
  }
  ```
  Se não houver imagens: `"images": []`.

---

## TDD Implementation Roadmap

### Phase 1: Unit Tests & Domain Actions (Red $\to$ Green)
1. **OfferPolicyTest**:
   - Red: Testar que produtor proprietário passa na policy (`update`, `deleteImage`) e outros produtores ou usuários sem perfil de produtor são negados.
   - Green: Implementar `OfferPolicy`.
2. **GetOfferImagesActionTest**:
   - Red: Simular arquivos fake no `Storage::disk('public')` e validar que a Action retorna `OfferImageDTO`s ordenados por sequência, ou array vazio se o diretório estiver vazio.
   - Green: Implementar `GetOfferImagesAction` e `OfferImageDTO`.
3. **UploadOfferImagesActionTest**:
   - Red: Testar upload de 1 a 3 imagens com nomes gerados `{order}_{id}.{ext}`; testar rejeição com 422 quando ultrapassar 3 imagens no acumulado.
   - Green: Implementar `UploadOfferImagesAction`.
4. **DeleteOfferImageActionTest**:
   - Red: Testar exclusão de arquivo existente no storage; testar 404 quando o identificador não existir.
   - Green: Implementar `DeleteOfferImageAction`.

### Phase 2: FormRequests & Serialization (Red $\to$ Green)
1. Atualizar `StoreOfferRequest` e criar `UploadOfferImagesRequest` com testes de validação (mimes, tamanho máx 5MB, limite de itens).
2. Atualizar `OfferResource` com testes de serialização para incluir `images`.

### Phase 3: Feature Tests & Endpoints (Red $\to$ Green)
1. **StoreWithImagesTest**:
   - Red: `POST /v1/offers` com imagens fake (`UploadedFile::fake()->image(...)`), verificar que a oferta é criada e as imagens são salvas e retornadas.
   - Green: Conectar `UploadOfferImagesAction` no `OfferController::store`.
2. **OfferImageController UploadTest**:
   - Red: `POST /v1/offers/{offer}/images` testando sucesso, limite de 3 imagens atingido (422), e tentativa de produtor não proprietário (403).
   - Green: Implementar `OfferImageController::store`.
3. **OfferImageController DeleteTest**:
   - Red: `DELETE /v1/offers/{offer}/images/{image}` testando 204, exclusão real no storage, 404 para imagem inexistente e 403 para produtor não proprietário.
   - Green: Implementar `OfferImageController::destroy`.
4. **Index and Show Images Feature Tests**:
   - Red: Testar que `GET /v1/offers` e `GET /v1/offers/{offer}` retornam o nó `images` com URLs geradas.
   - Green: Garantir que o resource execute `GetOfferImagesAction`.
