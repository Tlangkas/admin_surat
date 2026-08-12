<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Policy untuk User — kontrol akses manajemen user.
 */
class UserPolicy
{
    /**
     * Admin bisa lihat semua user.
     * Kepsek bisa lihat user (untuk keperluan TTD dll).
     * Gukar tidak boleh lihat daftar user.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isKepsek();
    }

    /**
     * Admin bisa lihat detail user manapun.
     * Kepsek bisa lihat detail user.
     * Gukar hanya bisa lihat profile sendiri.
     */
    public function view(User $user, User $targetUser): bool
    {
        if ($user->isAdmin() || $user->isKepsek()) {
            return true;
        }

        return $user->id === $targetUser->id;
    }

    /**
     * Hanya admin yang bisa buat user baru.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Admin bisa update user manapun.
     * User lain tidak boleh update user lain.
     */
    public function update(User $user, User $targetUser): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->id === $targetUser->id;
    }

    /**
     * Hanya admin yang bisa hapus user.
     */
    public function delete(User $user, User $targetUser): bool
    {
        return $user->isAdmin() && $user->id !== $targetUser->id;
    }

    /**
     * Hanya admin yang bisa reset password user lain.
     */
    public function resetPassword(User $user, User $targetUser): bool
    {
        return $user->isAdmin() && $user->id !== $targetUser->id;
    }
}