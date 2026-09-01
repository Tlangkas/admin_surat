<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Siswa;
use App\Models\User;

/**
 * Policy SiswaPolicy
 *
 * Mengatur hak akses CRUD data siswa (Admin & Kepsek).
 */
class SiswaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isKepsek();
    }

    public function view(User $user, Siswa $siswa): bool
    {
        return $user->isAdmin() || $user->isKepsek();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Siswa $siswa): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Siswa $siswa): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, Siswa $siswa): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, Siswa $siswa): bool
    {
        return $user->isAdmin();
    }
}
