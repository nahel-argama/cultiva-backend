<?php

namespace Tests\Unit\domain\Auth\Actions;

use Tests\TestCase;

/**
 * @nicolas
 *
 * Aqui vai tua introdução aos unit tests.
 *
 * Esses são os piores testes de fazer e manter, pq sempre que você altera algo no código, ele vai quebrar. Isso acontence
 * porque a gente costuma user muito "Mock" nos units.
 *
 * Pensa no mock aqui, como sendo uma versão de cópia da sua classe, em que você pode dizer exatamente o que ela vai fazer
 * quando algo acontecer. Recomendo pesquisar sobre, é meio chat no começo
 *
 * Eu não gosto de fazer unit test pra tudo, só o que faz sentido e precisa realmente ser testado passo a passo. Justamente por ser chat de manter.
 * Mas no ambiente ideal você deve ter isso feito.
 *
 * Quando tu tiver testando unit, pensa mesmo em uma unidade singular, um comportamento específico, seja um método ou uma classe. Mas sempre uma
 * unidade
 *
 * Diferente dos de feature, que tu ignore unidades e quer testar se aquilo funciona por completo.
 *
 * Os testes se complementam basicamente.
 *
 * Aqui, quero que tu teste o caminho feliz da geração de tokens e se eles devolvem o output esperado. Tecnicamente é um teste desnecessário, já que
 * tu vai mockar abstrações do Laravel e etc. Mas é um começo pra tu entender e começar a odiar unit tests
 */
class GenerateTokenPairActionTest extends TestCase {}
