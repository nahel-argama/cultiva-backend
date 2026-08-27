<?php

namespace Tests\Unit\domain\Models\User;

use Cultiva\Models\User\User;
use DomainException;
use Tests\TestCase;

class UserTest extends TestCase
{
    public function test_should_reject_user_without_exactly_one_profile(): void
    {
        // Arrange
        $sut = new User;
        $sut->setRelation('producer', null);
        $sut->setRelation('retailer', null);

        // Action & Assert
        $this->expectException(DomainException::class);
        $sut->getProfileType();
    }
}
