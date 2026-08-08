<?php

namespace App\Exports;

use App\Models\ImportBatch;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ImportErrorReportExport implements FromArray, WithHeadings
{
    public function __construct(private readonly ImportBatch $batch) {}

    public function headings(): array
    {
        return ['Row', 'Error', 'Raw Data'];
    }

    public function array(): array
    {
        return $this->batch->errors()
            ->orderBy('row_number')
            ->get()
            ->map(fn ($error) => [$error->row_number, $error->error_message, json_encode($error->raw_data)])
            ->all();
    }
}
