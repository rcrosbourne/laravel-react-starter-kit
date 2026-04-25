<?php

declare(strict_types=1);

namespace Tests\Feature\Providers;

use App\Providers\AppServiceProvider;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rules\Password;
use Tests\TestCase;

final class AppServiceProviderTest extends TestCase
{
    public function test_models_are_strict(): void
    {
        $this->assertTrue(Model::preventsLazyLoading());
        $this->assertTrue(Model::preventsSilentlyDiscardingAttributes());
        $this->assertTrue(Model::preventsAccessingMissingAttributes());
    }

    public function test_dates_use_carbon_immutable(): void
    {
        $this->assertInstanceOf(CarbonImmutable::class, now());
    }

    public function test_password_default_outside_of_production_falls_back_to_min_eight(): void
    {
        // The non-production branch returns null; Laravel falls back to Password::min(8).
        $this->assertFalse($this->app->isProduction());

        $rule = Password::default();
        $this->assertInstanceOf(Password::class, $rule);
        $this->assertSame(8, (fn () => $this->min)->call($rule));
    }

    public function test_password_default_is_strict_in_production(): void
    {
        $original = $this->app->environment();

        try {
            $this->app->detectEnvironment(static fn (): string => 'production');
            $this->assertTrue($this->app->isProduction());

            // Re-run the provider's boot() so the new closure picks up the changed env.
            (new AppServiceProvider($this->app))->boot();

            $rule = Password::default();
            $this->assertInstanceOf(Password::class, $rule);
            $this->assertSame(12, (fn () => $this->min)->call($rule));
            $this->assertTrue((fn () => $this->mixedCase)->call($rule));
            $this->assertTrue((fn () => $this->letters)->call($rule));
            $this->assertTrue((fn () => $this->numbers)->call($rule));
            $this->assertTrue((fn () => $this->symbols)->call($rule));
            $this->assertTrue((fn () => $this->uncompromised)->call($rule));
        } finally {
            $this->app->detectEnvironment(static fn () => $original);
            (new AppServiceProvider($this->app))->boot();
        }
    }
}
