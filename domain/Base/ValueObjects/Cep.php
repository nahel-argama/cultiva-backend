<?php

namespace Cultiva\Base\ValueObjects;

use Cultiva\Base\Exceptions\CultivaException;

/**
 * @nicolas
 *
 * Esse aqui é um conceito que nunca coloquei em prática, mas queria tinha um tempo.
 *
 * ValueObjects são basicamente wrappers em cima de valores primitivos, que adicionam regras de negócio e validações com
 * base no domínio.
 *
 * Nesse exemplo, eu precisava validar o CEP passado pro serviço de geolocalização, com a lógica de validação centralizada
 * em um lugar, o ValueObject pro CEP resolve.
 *
 * ValueObjects são imutáveis e a igualdade deles se baseia no valor que eles guardam, e não na referência do objeto.
 */
final class Cep
{
    private readonly string $value;

    public function __construct(string $value)
    {
        $digits = preg_replace('/\D/', '', $value);

        if (\strlen($digits) !== 8) {
            throw new CultivaException(422, 'CEP must contain exactly 8 digits.');
        }

        $this->value = $digits;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function format(): string
    {
        return substr($this->value, 0, 5).'-'.substr($this->value, 5);
    }
}
