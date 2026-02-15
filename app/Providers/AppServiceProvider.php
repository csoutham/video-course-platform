<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
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
        //
    }

    public function boot(): void
    {
        $this->configureCommands();
        $this->configureErrors();
        $this->configureModels();
        $this->configureSecurity();
        $this->configureVantage();
        $this->configureVite();
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

    private function configureSecurity(): void
    {
        URL::forceScheme('https');

        Password::defaults(fn () => Password::min(8)->uncompromised());
    }

    private function configureVantage(): void
    {
        Gate::define('viewVantage', fn ($user) => $user->email === 'chris@cjsoutham.com');
    }

    private function configureVite(): void
    {
        Vite::useAggressivePrefetching();
    }
}
