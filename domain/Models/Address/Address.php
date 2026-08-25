<?php

namespace Cultiva\Models\Address;

use Carbon\CarbonImmutable;
use Database\Factories\AddressFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property-read int $id
 * @property-read string $addressable_type
 * @property-read int $addressable_id
 * @property-read string $zip
 * @property-read string $street
 * @property-read string $number
 * @property-read ?string $complement
 * @property-read ?string $reference_point
 * @property-read string $neighborhood
 * @property-read string $city
 * @property-read string $state
 * @property-read mixed $coordinate
 * @property-read CarbonImmutable $created_at
 * @property-read ?CarbonImmutable $updated_at
 * @property-read ?Model $addressable
 */
class Address extends Model
{
    /** @use HasFactory<AddressFactory> */
    use HasFactory;

    protected $fillable = [
        'addressable_type',
        'addressable_id',
        'zip',
        'street',
        'number',
        'complement',
        'reference_point',
        'neighborhood',
        'city',
        'state',
        'coordinate',
    ];

    /**
     * @nicolas
     *
     * Olha, isso aqui é meio chato de entender no começo.
     *
     * Pensa no seguinte: nosso sistema tem mais de uma entidade que pode ter endereço. A princípio cada entidade tem um, mas pode
     * ter mais de um.
     *
     * Então nossa relação é: uma entidade tem um endereço
     *
     * Mas aí que mora a maldade, uma entidade implica que existe mais de uma tabela que pode ser qualificada como entidade. Ou seja, temos
     * várias entidades que podem ter um endereço.
     *
     * Aí que entra o morph do laravel. É uma estratégia simples de polimorfismo no banco de dados. A gente passa a ter duas colunas dentro da classe filha
     * para identificar quem é o pai, ao invés de uma só com o id, que acontece normalmente.
     *
     * As colunas são uma string com a "tag" e outra com o "id".
     *
     * No contexto do Laravel, a tag armazena o nome da classe do pai, que é um model do eloquent, e sua primary key na coluna id.
     *
     * O que muda é que a relação é composta e usa uma string pra identificar primeiro o tipo do pai, pra depois buscar o id dele e reconstrui-lo.
     *
     * Da uma olhada na migration, pq ela tem um método que já criar as colunas de morph. Também da uma olhada nos docs do Laravel
     */
    /**
     * @return MorphTo<Model, $this>
     */
    public function addressable(): MorphTo
    {
        return $this->morphTo();
    }
}
