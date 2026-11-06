<?php

declare(strict_types=1);

namespace Cultiva\Auth\Builders\SignupRuleBuilder\Contracts;

interface SignupRuleBuilderInterface
{
    public function addUserRules(): static;

    public function addCompanyRules(): static;

    public function addAddressRules(string $prefix): static;

    public function addProducerRules(): static;

    public function addRetailerRules(): static;

    public function addDeliveryRules(): static;

    public function getRules(): array;
}
