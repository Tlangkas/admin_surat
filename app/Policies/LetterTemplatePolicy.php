<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\LetterTemplate;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Policy untuk LetterTemplate — kontrol akses berbasis role.
 */
class LetterTemplatePolicy
{
    /**
     * Admin dan kepsek bisa lihat daftar template.
     * Gukar tidak boleh akses manajemen template.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isKepsek();
    }

    /**
     * Admin dan kepsek bisa lihat detail template.
     */
    public function view(User $user, LetterTemplate $letterTemplate): bool
    {
        return $user->isAdmin() || $user->isKepsek();
    }

    /**
     * Hanya admin yang bisa buat template baru.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Hanya admin yang bisa edit template.
     */
    public function update(User $user, LetterTemplate $letterTemplate): bool
    {
        return $user->isAdmin();
    }

    /**
     * Hanya admin yang bisa hapus template.
     */
    public function delete(User $user, LetterTemplate $letterTemplate): bool
    {
        return $user->isAdmin();
    }

    /**
     * Restore soft deleted (admin only).
     */
    public function restore(User $user, LetterTemplate $letterTemplate): bool
    {
        return $user->isAdmin();
    }

    /**
     * Force delete (admin only).
     */
    public function forceDelete(User $user, LetterTemplate $letterTemplate): bool
    {
        return $user->isAdmin();
    }
}