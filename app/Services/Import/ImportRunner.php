<?php

namespace App\Services\Import;

use App\Enums\ImportStatus;
use App\Enums\ImportType;
use App\Models\ImportBatch;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Shared upload -> validate -> persist -> report pipeline for every import
 * type. Each row is validated and persisted independently (its own DB
 * transaction) rather than the whole file in one transaction, so a
 * business-rule failure discovered only at write time (e.g. a class
 * section being full) reports as a single row error instead of discarding
 * every other valid row in the file. Re-running the same file is expected
 * to be idempotent — each RowImporter upserts by natural key.
 */
class ImportRunner
{
    public function run(UploadedFile $file, ImportType $type, RowImporter $importer, User $actor): ImportBatch
    {
        $sheet = new HeadingRowArrayImport;
        Excel::import($sheet, $file);

        $batch = ImportBatch::create([
            'type' => $type->value,
            'file_name' => $file->getClientOriginalName(),
            'uploaded_by' => $actor->id,
            'status' => ImportStatus::Processing->value,
            'total_rows' => count($sheet->rows),
        ]);

        $successCount = 0;

        foreach ($sheet->rows as $index => $row) {
            $rowNumber = $index + 2;

            try {
                $data = $importer->validateRow($row);

                DB::transaction(function () use ($importer, $data, $actor) {
                    $importer->persistRow($data, $actor);
                });

                $successCount++;
            } catch (ValidationException $e) {
                $batch->errors()->create([
                    'row_number' => $rowNumber,
                    'raw_data' => $row,
                    'error_message' => implode(' ', Arr::flatten($e->errors())),
                ]);
            }
        }

        $batch->update([
            'status' => ImportStatus::Completed->value,
            'success_rows' => $successCount,
            'error_rows' => $batch->errors()->count(),
        ]);

        return $batch->fresh();
    }
}
