<?php

namespace Database\Factories;

use App\Models\ClassSection;
use App\Models\GradeSubmission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GradeSubmission>
 */
class GradeSubmissionFactory extends Factory
{
    protected $model = GradeSubmission::class;

    public function definition(): array
    {
        return [
            'class_section_id' => ClassSection::factory(),
            'status' => 'draft',
        ];
    }
}
