<?php

declare(strict_types=1);

namespace Cultiva\Auth\Builders\SignupRuleBuilder;

use Cultiva\Auth\Builders\SignupRuleBuilder\Contracts\SignupRuleBuilderInterface;
use Cultiva\Auth\Enums\ProfileType;
use Cultiva\Models\Delivery\Enums\CnhCategory;
use Cultiva\Models\Vehicle\Enums\CargoType;
use Cultiva\Models\Producer\Enums\ActivitySegment;
use Cultiva\Models\Retailer\Enums\BusinessType;
use Illuminate\Validation\Rule;

class SignupRuleBuilder implements SignupRuleBuilderInterface
{
    private const string PHONE_REGEX = '/^[0-9]{13}$/';
    private const string ZIP_REGEX = '/^[0-9]{8}$/';
    private const string DOCUMENT_REGEX = '/^[0-9]{14}$/';

    protected array $rules = [];

    public function addUserRules(): static
    {
        $this->rules = array_merge($this->rules, [
            'profile_type'  => ['required', Rule::enum(ProfileType::class)],
            'user.name'     => ['required', 'string', 'max:100'],
            'user.email'    => ['required', 'string', 'email', 'max:100', 'unique:users,email'],
            'user.password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        return $this;
    }

    public function addCompanyRules(): static
    {
        $this->rules = array_merge($this->rules, [
            'company.trade_name'      => ['required', 'string', 'max:100'],
            'company.legal_name'      => ['nullable', 'string', 'max:100'],
            'company.document_number' => [
                'required',
                'string',
                'digits:14',
                'regex:' . self::DOCUMENT_REGEX,
                'unique:companies,document_number',
            ],
            'company.phone'           => ['required', 'string', 'max:15', 'regex:' . self::PHONE_REGEX],
        ]);

        return $this;
    }

    public function addAddressRules(string $prefix): static
    {
        $this->rules = array_merge($this->rules, [
            "{$prefix}.zip"             => ['required', 'string', 'digits:8', 'regex:' . self::ZIP_REGEX],
            "{$prefix}.number"          => ['required', 'string', 'max:20'],
            "{$prefix}.complement"      => ['nullable', 'string', 'max:50'],
            "{$prefix}.reference_point" => ['nullable', 'string', 'max:150'],
        ]);

        return $this;
    }

    public function addProducerRules(): static
    {
        $this->rules = array_merge($this->rules, [
            'producer.activity_segment' => ['required', Rule::enum(ActivitySegment::class)],
        ]);

        return $this;
    }

    public function addRetailerRules(): static
    {
        $this->rules = array_merge($this->rules, [
            'retailer.business_type' => ['required', Rule::enum(BusinessType::class)],
        ]);

        return $this;
    }

    public function addDeliveryRules(): static
    {
        $this->rules = array_merge($this->rules, [
            'delivery.cnh_number'         => ['required', 'string', 'digits:11', 'unique:deliveries,cnh_number'],
            'delivery.cnh_category'       => ['required', Rule::enum(CnhCategory::class)],
            'delivery.vehicle.plate'      => ['required', 'string', 'min:7', 'max:8', 'unique:vehicles,plate'],
            'delivery.vehicle.cargo_type' => ['required', Rule::enum(CargoType::class)],
        ]);

        return $this;
    }

    public function getRules(): array
    {
        return $this->rules;
    }
}
