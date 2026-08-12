<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\LetterRequest;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class LetterStatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '5s';

    protected function getStats(): array
    {
        $user = Auth::user();
        $query = LetterRequest::query();

        if ($user && $user->isGukar()) {
            $query->where('user_id', $user->id);
        }

        $total = (clone $query)->count();
        $pending = (clone $query)->where('status', 'pending')->count();
        $approved = (clone $query)->where('status', 'approved_admin')->count();
        $signed = (clone $query)->where('status', 'signed')->count();
        $rejected = (clone $query)->where('status', 'rejected')->count();

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