<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * One generic exporter reused across every report type (Phase 8B) — the
 * shape is always "headings + rows," so a dedicated Export class per report
 * would just be five copies of the same six lines. See ImportErrorReportExport
 * (Phase 2G) for the same FromArray/WithHeadings pattern this mirrors.
 */
class ReportExport implements FromArray, WithHeadings
{
    /**
     * @param  array<int, string>  $headings
     * @param  array<int, array<int, string>>  $rows
     */
    public function __construct(private readonly array $headings, private readonly array $rows) {}

    public function headings(): array
    {
        return $this->headings;
    }

    public function array(): array
    {
        return $this->rows;
    }
}
