<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * This is used by Laravel authentication to redirect users after login.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * The controller namespace for the application.
     *
     * When present, controller route declarations will automatically be prefixed with this namespace.
     *
     * @var string|null
     */

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::prefix('api')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/web.php'));

            // Agrega la ruta de autenticación API
            Route::middleware('api')
                ->prefix('api/v1')
                ->group(base_path('routes/apiAuth.php'));

            Route::middleware('api')
                ->prefix('api/v1')
                ->group(base_path('routes/apiUser.php'));

            Route::middleware('api')
                ->prefix('api/v1')
                ->group(base_path('routes/apiPeople.php'));

            Route::middleware('api')
                ->prefix('api/v1')
                ->group(base_path('routes/apiRolesAndPermissions.php'));

            Route::middleware('api')
                ->prefix('api/v1')
                ->group(base_path('routes/apiPayments.php'));

            Route::middleware('api')
                ->prefix('api/v1')
                ->group(base_path('routes/apiCampaign.php'));

            Route::middleware('api')
                ->prefix('api/v1')
                ->group(base_path('routes/apiCycle.php'));

            Route::middleware('api')
                ->prefix('api/v1')
                ->group(base_path('routes/apiConfigurationCampaigns.php'));

            Route::middleware('api')
                ->prefix('api/v1')
                ->group(base_path('routes/apiFocus.php'));

            Route::middleware('api')
                ->prefix('api/v1')
                ->group(base_path('routes/apiManagements.php'));

            Route::middleware('api')
                ->prefix('api/v1')
                ->group(base_path('routes/apiAssignments.php'));

            Route::middleware('api')
                ->prefix('api/v1')
                ->group(base_path('routes/apiTimePatterns.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60000)->by($request->user()?->id ?: $request->ip());
        });
    }
}
