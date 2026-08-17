<?php

namespace App\Enums;

/**
 * Standard three-tier Latin Honors classification by GWA, within the
 * college's overall 1.00-1.75 qualifying band (see LatinHonorsService).
 * Boundaries (1.00-1.20 / 1.21-1.45 / 1.46-1.75) follow the tier split
 * commonly used across Philippine higher-education institutions — adjust
 * the cutoffs in forGwa() if this college's actual policy differs.
 */
enum LatinHonorsTier: string
{
    case SummaCumLaude = 'summa_cum_laude';
    case MagnaCumLaude = 'magna_cum_laude';
    case CumLaude = 'cum_laude';

    public function label(): string
    {
        return match ($this) {
            self::SummaCumLaude => 'Summa Cum Laude',
            self::MagnaCumLaude => 'Magna Cum Laude',
            self::CumLaude => 'Cum Laude',
        };
    }

    public static function forGwa(float $gwa): ?self
    {
        return match (true) {
            $gwa <= 1.20 => self::SummaCumLaude,
            $gwa <= 1.45 => self::MagnaCumLaude,
            $gwa <= 1.75 => self::CumLaude,
            default => null,
        };
    }
}
