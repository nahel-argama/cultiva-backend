<?php

namespace Tests\Unit\domain\Models\User;

use Cultiva\Auth\Enums\ProfileType;
use Cultiva\Models\User\User;
use Tests\TestCase;

class UserTest extends TestCase
{
    public function test_should_return_profile_type(): void
    {
        // Arrange
        $sut = new User([
            'profile_type' => ProfileType::PRODUCER,
        ]);

        // Action
        $result = $sut->getProfileType();

        // Assert
        $this->assertSame(ProfileType::PRODUCER, $result);
    }

    public function test_should_identify_producer_profile(): void
    {
        // Arrange
        $sut = new User([
            'profile_type' => ProfileType::PRODUCER,
        ]);

        // Assert
        $this->assertTrue($sut->isProducer());
        $this->assertFalse($sut->isRetailer());
    }

    public function test_should_identify_retailer_profile(): void
    {
        // Arrange
        $sut = new User([
            'profile_type' => ProfileType::RETAILER,
        ]);

        // Assert
        $this->assertTrue($sut->isRetailer());
        $this->assertFalse($sut->isProducer());
        $this->assertFalse($sut->isDelivery());
    }

    public function test_should_identify_delivery_profile(): void
    {
        // Arrange
        $sut = new User([
            'profile_type' => ProfileType::DELIVERY,
        ]);

        // Assert
        $this->assertTrue($sut->isDelivery());
        $this->assertFalse($sut->isProducer());
        $this->assertFalse($sut->isRetailer());
    }
}
