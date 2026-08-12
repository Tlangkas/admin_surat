<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SchoolSettings;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Policy untuk SchoolSettings — hanya admin yang bisa kelola.
 */
class SchoolSettingsPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, SchoolSettings $schoolSettings): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, SchoolSettings $schoolSettings): bool
    {
        return $user->isAdmin();
    }
}