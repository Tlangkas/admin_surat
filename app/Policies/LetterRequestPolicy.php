<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\LetterRequest;
use App\Models\User;

/**
 * Policy untuk LetterRequest — kontrol akses berbasis role & ownership.
 */
class LetterRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // Semua role login bisa akses index
    }

    public function view(User $user, LetterRequest $letterRequest): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isKepsek()) {
            return true;
        }

        if ($user->isGukar()) {
            return $letterRequest->user_id === $user->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isGukar() || $user->isAdmin();
    }

    public function update(User $user, LetterRequest $letterRequest): bool
    {
        if (! $letterRequest->isPending()) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isGukar()) {
            return $letterRequest->user_id === $user->id;
        }

        return false;
    }

    public function delete(User $user, LetterRequest $letterRequest): bool
    {
        if (! $letterRequest->isPending()) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isGukar()) {
            return $letterRequest->user_id === $user->id;
        }

        return false;
    }

    /**
     * Admin menyetujui pengajuan surat (pending -> approved_admin).
     */
    public function approveAdmin(User $user, LetterRequest $letterRequest): bool
    {
        return $user->isAdmin() && $letterRequest->isPending();
    }

    /**
     * Admin atau Kepsek menolak pengajuan surat.
     */
    public function reject(User $user, LetterRequest $letterRequest): bool
    {
        if ($letterRequest->isPending()) {
            return $user->isAdmin();
        }

        if ($letterRequest->isApprovedAdmin()) {
            return $user->isAdmin() || $user->isKepsek();
        }

        return false;
    }

    /**
     * Kepsek (atau Admin) menandatangani surat (approved_admin -> signed).
     */
    public function sign(User $user, LetterRequest $letterRequest): bool
    {
        return ($user->isKepsek() || $user->isAdmin()) && $letterRequest->isApprovedAdmin();
    }

    /**
     * Pratinjau PDF sebelum/sesudah ditandatangani.
     */
    public function previewPdf(User $user, LetterRequest $letterRequest): bool
    {
        if (! $letterRequest->isApprovedAdmin() && ! $letterRequest->isSigned()) {
            return false;
        }

        return $this->view($user, $letterRequest);
    }

    /**
     * Download file PDF resmi yang sudah ditandatangani.
     */
    public function downloadPdf(User $user, LetterRequest $letterRequest): bool
    {
        if (! $letterRequest->isSigned() || ! $letterRequest->pdf_path) {
            return false;
        }

        return $this->view($user, $letterRequest);
    }

    public function restore(User $user, LetterRequest $letterRequest): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, LetterRequest $letterRequest): bool
    {
        return $user->isAdmin();
    }
}