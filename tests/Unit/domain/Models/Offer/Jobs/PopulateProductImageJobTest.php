<?php

namespace Tests\Unit\domain\Models\Offer\Jobs;

use Cultiva\Models\Offer\Actions\PopulateProductImageAction;
use Cultiva\Models\Offer\Jobs\PopulateProductImageJob;
use Mockery;
use Tests\TestCase;

class PopulateProductImageJobTest extends TestCase
{
    public function test_it_calls_populate_product_image_action_when_handled(): void
    {
        $actionMock = Mockery::mock(PopulateProductImageAction::class);
        $actionMock->shouldReceive('execute')
            ->once()
            ->with('123', 'Tomate')
            ->andReturn('products/123.webp');

        $job = new PopulateProductImageJob('123', 'Tomate');
        $job->handle($actionMock);

        $this->assertTrue(true);
    }
}
