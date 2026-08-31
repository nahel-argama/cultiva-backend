<?php

namespace Tests\Unit\domain\Base\Exceptions;

use Cultiva\Base\Exceptions\CultivaException;
use Tests\TestCase;

class CultivaExceptionTest extends TestCase
{
    public function test_should_use_given_http_status(): void
    {
        // Arrange
        $message = 'Error';

        // Action
        $sut = new CultivaException(409, $message);

        // Assert
        $this->assertSame(409, $sut->getStatusCode());
    }
}
