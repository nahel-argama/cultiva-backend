<?php

namespace Tests\Unit\domain\Auth\Builders\SignupRuleBuilder;

use Cultiva\Auth\Builders\SignupRuleBuilder\SignupRuleBuilder;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class SignupRuleBuilderTest extends TestCase
{
    public function test_should_require_eleven_phone_digits_including_area_code(): void
    {
        // Arrange
        $sut = new SignupRuleBuilder();
        $rules = $sut->addCompanyRules()->getRules();
        $phones = [
            '00000000000' => true,
            '0000000000' => false,
            '000000000000' => false,
            '5500000000000' => false,
            '(00) 00000-0000' => false,
            '0000000000a' => false,
            '' => false,
        ];

        foreach ($phones as $phone => $expected) {
            // Action
            $validator = Validator::make(
                ['company' => ['phone' => (string) $phone]],
                ['company.phone' => $rules['company.phone']],
            );

            // Assert
            $this->assertSame($expected, $validator->passes(), (string) $phone);
        }
    }
}
