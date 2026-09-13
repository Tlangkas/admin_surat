<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\LetterRequest;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class LetterStatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $user = Auth::user();
        $query = LetterRequest::query();

        if ($user && $user->isGukar()) {
            $query->where('user_id', $user->id);
        }

        $stats = (clone $query)
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'approved_admin' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN status = 'signed' THEN 1 ELSE 0 END) as signed,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
            ")
            ->first();

        $total = (int) ($stats?->total ?? 0);
        $pending = (int) ($stats?->pending ?? 0);
        $approved = (int) ($stats?->approved ?? 0);
        $signed = (int) ($stats?->signed ?? 0);
        $rejected = (int) ($stats?->rejected ?? 0);

        return [
            Stat::make('Total Surat', $total)
                ->description('Semua pengajuan')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary'),
            Stat::make('Menunggu Persetujuan', $pending)
                ->description('Status: Pending')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning')
                ->chart([7, 3, 5, 2, 8, 4, 6]),
            Stat::make('Disetujui Admin', $approved)
                ->description('Menunggu TTD Kepsek')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('info')
                ->chart([2, 4, 1, 3, 5, 2, 3]),
            Stat::make('Ditandatangani', $signed)
                ->description('Selesai + PDF')
                ->descriptionIcon('heroicon-m-pencil-square')
                ->color('success')
                ->chart([1, 2, 3, 4, 2, 3, 4]),
            Stat::make('Ditolak', $rejected)
                ->description('Ditolak Admin')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger')
                ->chart([0, 1, 0, 1, 0, 1, 0]),
        ];
    }
}