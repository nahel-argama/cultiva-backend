# Cutliva Backend

API dedicada para o projeto cultiva do PI

## Dados de demonstração

Na pasta `backend/`, rode o banco e o container PHP, depois aplique as migrations e gere os dados:

```bash
docker compose up -d database php
docker compose exec php php artisan migrate
docker compose exec php php artisan db:seed
```

O seeder cria 25 produtores e 25 varejistas, com empresas, endereços, ofertas, compras e listas de desejos. Pode ser executado novamente: ele recria apenas os registros de demonstração e preserva os demais.

Para entrar com uma conta gerada, use `produtor1@gmail.com` ou `fornecedor1@gmail.com`. A senha compartilhada é `teste@123`. Esses usuários são apenas para desenvolvimento; o seeder não roda em produção.
