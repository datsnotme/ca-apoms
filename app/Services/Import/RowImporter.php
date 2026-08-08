<?php

namespace App\Services\Import;

use App\Models\User;
use Illuminate\Validation\ValidationException;

interface RowImporter
{
    /**
     * Column headers in the exact order the template uses.
     *
     * @return string[]
     */
    public function headings(): array;

    /**
     * One example data row, matching headings() order, for the template.
     *
     * @return array<int, string|int|float>
     */
    public function sampleRow(): array;

    /**
     * Validate and normalize one raw spreadsheet row (keyed by snake_case
     * heading). Must throw ValidationException on any problem — never
     * return partially-valid data.
     *
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    public function validateRow(array $row): array;

    /**
     * Persist one already-validated row. Wrapped in its own DB transaction
     * by the caller, so a business-rule failure discovered only at write
     * time (e.g. class section capacity) should throw ValidationException
     * rather than leave partial writes behind.
     *
     * @param  array<string, mixed>  $data
     */
    public function persistRow(array $data, User $actor): void;
}
