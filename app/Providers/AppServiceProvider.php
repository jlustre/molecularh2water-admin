<?php

namespace App\Providers;

use App\Models\Crm\Customer;
use App\Models\Crm\Funnel;
use App\Models\Crm\FunnelStage;
use App\Models\Crm\Lead;
use App\Models\Crm\Prospect;
use App\Models\Crm\Recruit;
use App\Models\User;
use App\Policies\Crm\LeadPolicy;
use App\Support\Crm\CrmContactResolver;
use App\Support\Crm\CrmScope;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

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
        $this->discardStaleViteHotFile();
        $this->configureEmailVerificationUrls();

        Schema::defaultStringLength(191);

        Relation::enforceMorphMap([
            'lead' => Lead::class,
            'prospect' => Prospect::class,
            'customer' => Customer::class,
            'recruit' => Recruit::class,
            'user' => User::class,
            'funnel' => Funnel::class,
            'funnel_stage' => FunnelStage::class,
        ]);

        foreach ([Lead::class, Prospect::class, Customer::class, Recruit::class] as $model) {
            Gate::policy($model, LeadPolicy::class);
        }

        \Illuminate\Support\Facades\Route::bind('lead', function (string $value, \Illuminate\Routing\Route $route) {
            $lifecycle = CrmContactResolver::lifecycleFromRouteName($route->getName());
            $class = CrmContactResolver::modelClassFor($lifecycle);

            return CrmScope::contacts($class::query())->findOrFail($value);
        });

        // Attach the sidebar designs composer to the settings page
        \View::composer('admin.placeholders.settings', \App\View\Composers\SettingsSidebarDesignsComposer::class);
    }

    /**
     * Sign verification links relatively so they work on any host that serves
     * the app (localhost:8000, 127.0.0.1, *.test), while still emailing an
     * absolute URL based on APP_URL.
     */
    private function configureEmailVerificationUrls(): void
    {
        VerifyEmail::createUrlUsing(function (object $notifiable): string {
            $relativeUrl = URL::temporarySignedRoute(
                'verification.verify',
                Carbon::now()->addMinutes((int) Config::get('auth.verification.expire', 60)),
                [
                    'id' => $notifiable->getKey(),
                    'hash' => sha1($notifiable->getEmailForVerification()),
                ],
                absolute: false,
            );

            return rtrim((string) Config::get('app.url'), '/').$relativeUrl;
        });
    }

    /**
     * Remove a stale Vite hot file so @vite falls back to public/build assets.
     *
     * Laragon/Windows often break when public/hot points at IPv6 [::1]:5173 or
     * when npm run dev was stopped but the hot file was left behind.
     */
    private function discardStaleViteHotFile(): void
    {
        $hotFile = public_path('hot');

        if (! is_file($hotFile)) {
            return;
        }

        $url = trim((string) file_get_contents($hotFile));

        if ($url === '' || str_contains($url, '[::1]')) {
            @unlink($hotFile);

            return;
        }

        if (! app()->environment('local', 'testing')) {
            return;
        }

        $host = parse_url($url, PHP_URL_HOST) ?: '127.0.0.1';
        $port = (int) (parse_url($url, PHP_URL_PORT) ?: 5173);
        $socket = @fsockopen($host, $port, $errno, $errstr, 0.15);

        if ($socket === false) {
            @unlink($hotFile);

            return;
        }

        fclose($socket);
    }
}
