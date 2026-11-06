<?php

namespace Cultiva\Auth\Requests;

use Cultiva\Auth\Builders\SignupRuleBuilder\Directors\DeliverySignupDirector;
use Cultiva\Auth\Builders\SignupRuleBuilder\Directors\ProducerSignupDirector;
use Cultiva\Auth\Builders\SignupRuleBuilder\Directors\RetailerSignupDirector;
use Cultiva\Auth\Builders\SignupRuleBuilder\SignupRuleBuilder;
use Cultiva\Auth\Enums\ProfileType;
use Illuminate\Foundation\Http\FormRequest;

final class SignUpRequest extends FormRequest
{
    public function rules(): array
    {
        $builder = new SignupRuleBuilder();

        return match ($this->enum('profile_type', ProfileType::class)) {
            ProfileType::PRODUCER => (new ProducerSignupDirector($builder))->build(),
            ProfileType::RETAILER => (new RetailerSignupDirector($builder))->build(),
            ProfileType::DELIVERY => (new DeliverySignupDirector($builder))->build(),
            default => $builder->addUserRules()->getRules(),
        };
    }
}
