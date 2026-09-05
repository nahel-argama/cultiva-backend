<?php

namespace Tests\Unit\lang\en;

use Illuminate\Support\Facades\Lang;
use Tests\TestCase;

class TranslationsTest extends TestCase
{
    public function test_should_expose_all_custom_api_messages_in_english(): void
    {
        // Arrange
        config(['app.locale' => 'en']);

        // Action & Assert
        $this->assertSame('Unauthenticated.', Lang::get('auth.unauthenticated'));
        $this->assertSame('You do not have permission to access this resource.', Lang::get('auth.forbidden'));
        $this->assertSame('The user does not have a valid profile type.', Lang::get('auth.sign_up.user_without_profile'));
        $this->assertSame('A registration is already in progress for this email.', Lang::get('auth.sign_up.in_progress'));
        $this->assertSame('Invalid credentials.', Lang::get('auth.login.invalid_credentials'));
        $this->assertSame('The user must have exactly one profile.', Lang::get('auth.login.invalid_profile'));
        $this->assertSame('The user is not active.', Lang::get('auth.login.user_not_active'));
        $this->assertSame('Category not found.', Lang::get('offers.category_not_found'));
        $this->assertSame('Offer not found.', Lang::get('offers.not_found'));
        $this->assertSame('Total quantity cannot be less than reserved quantity.', Lang::get('offers.invalid_stock'));
        $this->assertSame('Failed to retrieve geographic information.', Lang::get('integrations.geo.failed_to_search'));
        $this->assertSame('Product not found in the external catalog.', Lang::get('integrations.product_source.not_found'));
        $this->assertSame('Product catalog unavailable.', Lang::get('integrations.product_source.unavailable'));
    }
}
