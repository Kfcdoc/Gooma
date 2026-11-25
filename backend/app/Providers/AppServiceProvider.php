<?php

namespace App\Providers;

use App\Models\Client;
use App\Models\PersonalAccessToken;
use App\Models\Project;
use App\Policies\ClientPolicy;
use App\Policies\ProjectPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Sanctum debe persistir los tokens en MongoDB, no en SQL.
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        // Autorización explícita de los recursos con alcance por rol/proyecto.
        Gate::policy(Client::class, ClientPolicy::class);
        Gate::policy(Project::class, ProjectPolicy::class);
    }
}
