<?php

namespace App\Providers;

use App\Http\Controllers\ExternalController;
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
    // protected $namespace = 'App\\Http\\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();

        $this->routes(function () {

            /** QoreID Webhooks */
            Route::post('/auth-webhook/{driver}', [ExternalController::class, 'webhook']);
            Route::match(['GET', 'POST'], '/qoreid-webhook', [ExternalController::class, 'qoreid']);

            /** verifyMe Webhooks */
            Route::post('/verifyme-webhook', [ExternalController::class, 'verifyMe']);

            $this->registerApiRoutes();

            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Register Api routes
     *
     * @return void
     */
    public function registerApiRoutes()
    {
        Route::name('api.v1.user.')
            ->prefix('v1/user')
            ->middleware('api')
            ->namespace(null)
            ->group(base_path('routes/api/v1/user.php'));

        Route::name('external.')
            ->prefix('external')
            ->middleware('api')
            ->namespace(null)
            ->group(base_path('routes/external.php'));

        Route::name('api.v1.admin.')
            ->prefix('v1/admin')
            ->middleware('api')
            ->namespace(null)
            ->group(base_path('routes/api/v1/admin.php'));
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by(optional($request->user())->id ?: $request->ip());
        });
    }
}
