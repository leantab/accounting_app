<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Fortify\Features;
use Resend\Laravel\Facades\Resend;

abstract class TestCase extends BaseTestCase
{
    /**
     * Tests never reach the real Resend API; a test that checks an email overrides this with its own expectation.
     */
    protected function setUp(): void
    {
        parent::setUp();

        Resend::shouldReceive('emails')->andReturn(new class
        {
            /**
             * @param  array<string, mixed>  $payload
             * @param  array<string, mixed>  $options
             */
            public function send(array $payload, array $options = []): void {}
        })->byDefault();
    }

    protected function skipUnlessFortifyFeature(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }
}
