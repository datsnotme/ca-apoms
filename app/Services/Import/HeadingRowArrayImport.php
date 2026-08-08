<?php

namespace App\Services\Import;

use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Reads the first sheet of an uploaded workbook into plain associative rows
 * (keyed by snake_case header). All Phase 2G templates are single-sheet.
 */
class HeadingRowArrayImport implements ToArray, WithHeadingRow
{
    /** @var array<int, array<string, mixed>> */
    public array $rows = [];

    public function array(array $array): void
    {
        $this->rows = $array;
    }
}
