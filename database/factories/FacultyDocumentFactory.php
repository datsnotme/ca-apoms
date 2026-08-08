<?php

namespace Database\Factories;

use App\Models\FacultyDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FacultyDocument>
 */
class FacultyDocumentFactory extends Factory
{
    protected $model = FacultyDocument::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'category' => 'diploma',
            'title' => 'Diploma',
            'file_path' => 'faculty-documents/test/'.fake()->uuid().'.pdf',
            'original_filename' => 'diploma.pdf',
            'file_type' => 'application/pdf',
            'file_size' => 102400,
            'uploaded_at' => now(),
            'verification_status' => 'pending',
        ];
    }
}
