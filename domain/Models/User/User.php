<?php

namespace Cultiva\Models\User;

use Carbon\CarbonImmutable;
use Cultiva\Auth\Enums\ProfileType;
use Cultiva\Models\Company\Company;
use Cultiva\Models\Delivery\Delivery;
use Cultiva\Models\Producer\Producer;
use Cultiva\Models\Retailer\Retailer;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
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
 * @property-read ProfileType $profile_type
 * @property-read ?CarbonImmutable $email_verified_at
 * @property-read CarbonImmutable $created_at
 * @property-read ?CarbonImmutable $updated_at
 * @property-read ?CarbonImmutable $deleted_at
 * @property-read ?Company $company
 * @property-read ?Retailer $retailer
 * @property-read ?Producer $producer
 * @property-read ?Delivery $delivery
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
        'profile_type',
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
            'profile_type' => ProfileType::class,
        ];
    }

    /**
     * @return HasOne<Company, $this>
     */
    public function company(): HasOne
    {
        return $this->hasOne(Company::class);
    }

    /**
     * @return HasOneThrough<Retailer, Company, $this>
     */
    public function retailer(): HasOneThrough
    {
        return $this->hasOneThrough(Retailer::class, Company::class, 'user_id', 'company_id');
    }

    /**
     * @return HasOneThrough<Producer, Company, $this>
     */
    public function producer(): HasOneThrough
    {
        return $this->hasOneThrough(Producer::class, Company::class, 'user_id', 'company_id');
    }

    /**
     * @return HasOneThrough<Delivery, Company, $this>
     */
    public function delivery(): HasOneThrough
    {
        return $this->hasOneThrough(Delivery::class, Company::class, 'user_id', 'company_id');
    }

    public function isRetailer(): bool
    {
        return $this->profile_type === ProfileType::RETAILER;
    }

    public function isProducer(): bool
    {
        return $this->profile_type === ProfileType::PRODUCER;
    }

    public function isDelivery(): bool
    {
        return $this->profile_type === ProfileType::DELIVERY;
    }
}
