<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Karyawan;
use App\Models\User;

/**
 * Policy KaryawanPolicy
 *
 * Mengatur hak akses data guru dan karyawan (Khusus Admin).
 */
class KaryawanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Karyawan $karyawan): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Karyawan $karyawan): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Karyawan $karyawan): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, Karyawan $karyawan): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, Karyawan $karyawan): bool
    {
        return $user->isAdmin();
    }
}
