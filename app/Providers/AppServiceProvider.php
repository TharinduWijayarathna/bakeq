<?php

namespace App\Providers;

use App\Ai\GeminiCakeKnowledgeAssistant;
use App\Ai\GeminiCakePreviewGenerator;
use App\Contracts\CakeKnowledgeAssistant;
use App\Contracts\CakePreviewGenerator;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(CakePreviewGenerator::class, GeminiCakePreviewGenerator::class);
        $this->app->bind(CakeKnowledgeAssistant::class, GeminiCakeKnowledgeAssistant::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Model::preventLazyLoading(! app()->isProduction());

        Gate::define('access-admin', fn (User $user): bool => $user->isAdmin());

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
