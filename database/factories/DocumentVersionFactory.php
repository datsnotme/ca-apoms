<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentVersion>
 */
class DocumentVersionFactory extends Factory
{
    protected $model = DocumentVersion::class;

    public function definition(): array
    {
        return [
            'document_id' => Document::factory(),
            'version_number' => 1,
            'file_path' => 'documents/test/'.fake()->uuid().'.pdf',
            'original_filename' => 'document.pdf',
            'file_type' => 'application/pdf',
            'file_size' => 102400,
            'uploaded_by' => User::factory(),
            'uploaded_at' => now(),
        ];
    }
}
