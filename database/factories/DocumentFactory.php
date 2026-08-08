<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        return [
            'document_category_id' => DocumentCategory::factory(),
            'department_id' => null,
            'title' => fake()->sentence(4),
            'description' => fake()->sentence(),
            'uploaded_by' => User::factory(),
        ];
    }
}
