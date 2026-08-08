<?php

namespace Database\Factories;

use App\Models\Student;
use App\Models\StudentDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentDocument>
 */
class StudentDocumentFactory extends Factory
{
    protected $model = StudentDocument::class;

    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'category' => 'birth_certificate',
            'title' => 'Birth Certificate',
            'file_path' => 'student-documents/test/'.fake()->uuid().'.pdf',
            'original_filename' => 'birth-certificate.pdf',
            'file_type' => 'application/pdf',
            'file_size' => 102400,
            'uploaded_at' => now(),
            'verification_status' => 'pending',
        ];
    }
}
