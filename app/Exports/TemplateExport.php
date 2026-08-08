<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TemplateExport implements FromArray, WithHeadings
{
    public function __construct(
        private readonly array $headings,
        private readonly array $sampleRow,
    ) {}

    public function headings(): array
    {
        return $this->headings;
    }

    public function array(): array
    {
        return [$this->sampleRow];
    }
}
