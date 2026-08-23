<?php

namespace Tests\Feature\domain\Auth\Controllers\RegisterController;

use Tests\TestCase;


/**
 * @nicolas
 *
 * Aqui vai sua introdução aos testes de feature.
 *
 * Na prática, são testes que não se importam com unidades (obviamente), eles são testes que querem entender se uma
 * feature completa entrega o que é esperado em um determinado contexto. Da uma olhada no teste de feature que eu fiz pro
 * serviço de CEP que tu vai entender.
 *
 * O que eu preciso que tu teste aqui é se a rota retorna o payload esperado e se as entidades do banco esperadas foram
 * criadas.
 *
 * No teste de feature eu gosto de imaginar sempre que tu tem que satisfazer duas coisas:
 * - O contrato que o usuário da feature vai receber (nesse caso, o payload devolvido pela API)
 * - Os side effect que a feature vai gerar
 *
 * Eu suspeito que tu vai ter problemas com a address factory, por causa do tipo geométrico que tem na tabela. E também sinto
 * que tu vai ter que trocar o banco de testes pra Postgres, porque ele rodaa os testes no Sqlite hoje
 */
class SignUpTest extends TestCase {}
