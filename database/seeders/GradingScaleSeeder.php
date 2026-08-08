<?php

namespace Database\Seeders;

use App\Models\GradingScale;
use Illuminate\Database\Seeder;

class GradingScaleSeeder extends Seeder
{
    /**
     * Sample default scale (Philippine-HEI style 1.00-5.00 numeric range, plus the
     * non-numeric grade types the spec requires: Passed/Failed for pass-fail courses,
     * Incomplete, Dropped, Withdrawn, No Grade, and Credited). This is seed data, not
     * a hard-coded policy — the institution can add another GradingScale and flip
     * `is_default` without a code change. See ASSUMPTIONS.md.
     */
    public function run(): void
    {
        $scale = GradingScale::firstOrCreate(
            ['name' => 'Standard 1.00-5.00'],
            ['is_default' => true, 'description' => 'Default numeric scale with pass/fail and special grade types.']
        );

        $numeric = [
            ['1.00', 'Excellent', true, false],
            ['1.25', 'Excellent', true, false],
            ['1.50', 'Very Good', true, false],
            ['1.75', 'Very Good', true, false],
            ['2.00', 'Good', true, false],
            ['2.25', 'Good', true, false],
            ['2.50', 'Satisfactory', true, false],
            ['2.75', 'Satisfactory', true, false],
            ['3.00', 'Passing', true, false],
            ['4.00', 'Conditional Failure', false, true],
            ['5.00', 'Failed', false, true],
        ];

        $sortOrder = 0;
        foreach ($numeric as [$value, $label, $passing, $failing]) {
            $scale->values()->updateOrCreate(['value' => $value], [
                'label' => $label,
                'numeric_equivalent' => $value,
                'is_passing' => $passing,
                'is_failing' => $failing,
                'is_special' => false,
                'sort_order' => $sortOrder++,
            ]);
        }

        $special = [
            ['P', 'Passed', true, false],
            ['F', 'Failed', false, true],
            ['INC', 'Incomplete', false, false],
            ['DRP', 'Dropped', false, false],
            ['W', 'Withdrawn', false, false],
            ['CR', 'Credited', true, false],
            ['NG', 'No Grade', false, false],
        ];

        foreach ($special as [$value, $label, $passing, $failing]) {
            $scale->values()->updateOrCreate(['value' => $value], [
                'label' => $label,
                'numeric_equivalent' => null,
                'is_passing' => $passing,
                'is_failing' => $failing,
                'is_special' => true,
                'sort_order' => $sortOrder++,
            ]);
        }
    }
}
