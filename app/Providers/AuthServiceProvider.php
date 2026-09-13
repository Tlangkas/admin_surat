<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\LetterRequest;
use App\Models\LetterTemplate;
use App\Models\SchoolSettings;
use App\Models\User;
use App\Policies\LetterRequestPolicy;
use App\Policies\LetterTemplatePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        \App\Models\Karyawan::class => \App\Policies\KaryawanPolicy::class,
        LetterRequest::class => LetterRequestPolicy::class,
        LetterTemplate::class => LetterTemplatePolicy::class,
        SchoolSettings::class => \App\Policies\SchoolSettingsPolicy::class,
        \App\Models\Siswa::class => \App\Policies\SiswaPolicy::class,
        User::class => \App\Policies\UserPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Gate untuk admin panel access
        Gate::define('access-filament-admin', function (User $user): bool {
            return $user->isAdmin() || $user->isKepsek() || $user->isGukar();
        });

        // Gate untuk manage settings (admin only)
        Gate::define('manage-school-settings', function (User $user): bool {
            return $user->isAdmin();
        });
    }
}