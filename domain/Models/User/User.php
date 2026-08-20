<?php

namespace Cultiva\Models\User;

use Carbon\CarbonImmutable;

use Illuminate\Database\Eloquent\Factories\HasFactory;
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
 * @property-read bool $is_retailer
 * @property-read bool $is_producer
 * @property-read bool $is_active
 * @property-read CarbonImmutable $email_verified_at
 * @property-read CarbonImmutable $created_at
 * @property-read ?CarbonImmutable $updated_at
 * @property-read ?CarbonImmutable $deleted_at
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
        'last_login',
        'is_retailer',
        'is_producer',
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
}
