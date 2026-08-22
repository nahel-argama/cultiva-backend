<?php

namespace Cultiva\Models\User;

use Carbon\CarbonImmutable;
use Cultiva\Models\Producer\Producer;
use Cultiva\Models\Retailer\Retailer;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Override;

/**
 * @property-read int $id
 * @property-read string $name
 * @property-read string $email
 * @property-read string $password
 * @property-read string $remember_token
 * @property-read CarbonImmutable $last_login
 * @property-read ?int $retailer_id
 * @property-read ?int $producer_id
 * @property-read bool $is_active
 * @property-read CarbonImmutable $email_verified_at
 * @property-read CarbonImmutable $created_at
 * @property-read ?CarbonImmutable $updated_at
 * @property-read ?CarbonImmutable $deleted_at
 * @property-read HasOne $retailer
 * @property-read HasOne $producer
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'email_verified_at',
        'remember_token',
        'retailer_id',
        'producer_id',
        'last_login',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    #[Override]
    protected function casts(): array
    {
        return [
            'is_retailer'       => 'boolean',
            'is_producer'       => 'boolean',
            'is_active'         => 'boolean',
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'last_login'        => 'datetime',
        ];
    }

    /**
     * @return HasOne<Retailer, User>
     */
    public function retailer(): HasOne
    {
        return $this->hasOne(Retailer::class, 'id', 'retailer_id');
    }

    /**
     * @return HasOne<Producer, User>
     */
    public function producer(): HasOne
    {
        return $this->hasOne(Producer::class, 'id', 'producer_id');
    }
}
