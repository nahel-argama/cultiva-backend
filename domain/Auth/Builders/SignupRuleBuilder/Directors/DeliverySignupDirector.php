<?php

declare(strict_types=1);

namespace Cultiva\Auth\Builders\SignupRuleBuilder\Directors;

use Cultiva\Auth\Builders\SignupRuleBuilder\Contracts\SignupRuleBuilderInterface;

class DeliverySignupDirector
{
    public function __construct(
        protected SignupRuleBuilderInterface $builder
    ) {}

    public function build(): array
    {
        return $this->builder
            ->addUserRules()
            ->addCompanyRules()
            ->addAddressRules('company.address')
            ->addDeliveryRules()
            ->getRules();
    }
}
