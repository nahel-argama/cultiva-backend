<?php

namespace Cultiva\Models\User;

use Carbon\CarbonImmutable;
use Cultiva\Auth\Enums\ProfileType;
use Cultiva\Base\Exceptions\CultivaException;
use Cultiva\Models\Producer\Producer;
use Cultiva\Models\Retailer\Retailer;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Lang;
use Laravel\Sanctum\HasApiTokens;
use Override;

/**
 * @property-read int $id
 * @property-read string $name
 * @property-read string $email
 * @property-read string $password
 * @property-read ?string $remember_token
 * @property-read ?CarbonImmutable $last_login
 * @property-read bool $is_active
 * @property-read ?CarbonImmutable $email_verified_at
 * @property-read CarbonImmutable $created_at
 * @property-read ?CarbonImmutable $updated_at
 * @property-read ?CarbonImmutable $deleted_at
 * @property-read ?Retailer $retailer
 * @property-read ?Producer $producer
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'email_verified_at',
        'remember_token',
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
            'is_active' => 'boolean',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login' => 'datetime',
        ];
    }

    /**
     * @return HasOne<Retailer, $this>
     */
    public function retailer(): HasOne
    {
        return $this->hasOne(Retailer::class);
    }

    /**
     * @return HasOne<Producer, $this>
     */
    public function producer(): HasOne
    {
        return $this->hasOne(Producer::class);
    }

    public function getProfileType(): ProfileType
    {
        $isProducer = $this->isProducer();
        $isRetailer = $this->isRetailer();

        return match (true) {
            $isProducer && ! $isRetailer => ProfileType::PRODUCER,
            $isRetailer && ! $isProducer => ProfileType::RETAILER,
            default => throw new CultivaException(409, Lang::get('auth.login.invalid_profile')),
        };
    }

    public function isRetailer(): bool
    {
        return $this->retailer()->exists();
    }

    public function isProducer(): bool
    {
        return $this->producer()->exists();
    }
}
