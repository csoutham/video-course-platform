<?php

namespace App\Providers;

use App\Services\Branding\BrandingService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    #[\Override]
    public function register(): void
    {
        $this->app->singleton(BrandingService::class);
    }

    public function boot(): void
    {
        $this->configureAuthorization();
        $this->configureCommands();
        $this->configureErrors();
        $this->configureModels();
        $this->configureRateLimiting();
        $this->configureSecurity();
        $this->configureVite();
        $this->configureBranding();
    }

    private function configureAuthorization(): void
    {
        Gate::define('access-admin', fn ($user) => $user->isAdmin());
        Gate::define('viewVantage', fn ($user) => $user->email === 'chris@cjsoutham.com');
    }

    private function configureCommands(): void
    {
        DB::prohibitDestructiveCommands(
            $this->app->isProduction(),
        );
    }

    private function configureErrors(): void
    {
        RequestException::dontTruncate();
    }

    private function configureModels(): void
    {
        Model::automaticallyEagerLoadRelationships();
        Model::shouldBeStrict();
        Model::unguard();
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('mobile-api', function (Request $request): Limit {
            $key = $request->user()?->id
                ? 'mobile-user:'.$request->user()->id
                : 'mobile-ip:'.$request->ip();

            return Limit::perMinute(120)->by($key);
        });
    }

    private function configureSecurity(): void
    {
        URL::forceScheme('https');

        Password::defaults(fn () => Password::min(8)->uncompromised());
    }

    private function configureVite(): void
    {
        Vite::useAggressivePrefetching();
    }

    private function configureBranding(): void
    {
        View::composer('*', function ($view): void {
            /** @var BrandingService $brandingService */
            $brandingService = app(BrandingService::class);
            $branding = $brandingService->current();

            $view->with('branding', $branding);
        });
    }
}
