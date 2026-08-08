<?php

namespace Database\Factories;

use App\Enums\ImportStatus;
use App\Enums\ImportType;
use App\Models\ImportBatch;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ImportBatch>
 */
class ImportBatchFactory extends Factory
{
    protected $model = ImportBatch::class;

    public function definition(): array
    {
        return [
            'type' => fake()->randomElement(ImportType::cases())->value,
            'file_name' => fake()->lexify('import-????.xlsx'),
            'uploaded_by' => User::factory(),
            'status' => ImportStatus::Completed->value,
            'total_rows' => 0,
            'success_rows' => 0,
            'error_rows' => 0,
        ];
    }
}
