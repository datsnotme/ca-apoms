<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class UserSeeder extends Seeder
{
    /**
     * Development-only password for every seeded account. Never used outside
     * local development; production accounts are created via User Management
     * with a forced password change on first login.
     */
    private const DEV_PASSWORD = 'Password123!';

    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@ca-apoms.test'],
            [
                'employee_number' => 'EMP-0001',
                'surname' => 'Reyes',
                'first_name' => 'Ana',
                'username' => 'admin',
                'password' => self::DEV_PASSWORD,
                'password_changed_at' => Carbon::now(),
                'contact_number' => '0900-000-0001',
            ]
        );
        $admin->assignRole(RoleName::Administrator->value);

        $dean = User::firstOrCreate(
            ['email' => 'dean@ca-apoms.test'],
            [
                'employee_number' => 'EMP-0002',
                'surname' => 'Santos',
                'first_name' => 'Ramon',
                'username' => 'dean',
                'password' => self::DEV_PASSWORD,
                'password_changed_at' => Carbon::now(),
                'contact_number' => '0900-000-0002',
                'created_by' => $admin->id,
            ]
        );
        $dean->assignRole(RoleName::Dean->value);

        $departments = Department::all();
        $employeeSequence = 3;

        foreach ($departments as $department) {
            $headEmployeeNumber = 'EMP-'.str_pad((string) $employeeSequence++, 4, '0', STR_PAD_LEFT);
            $headUsername = 'head.'.strtolower($department->code);

            $head = User::firstOrCreate(
                ['email' => $headUsername.'@ca-apoms.test'],
                [
                    'employee_number' => $headEmployeeNumber,
                    'surname' => 'Cruz',
                    'first_name' => 'Head of '.$department->name,
                    'username' => $headUsername,
                    'password' => self::DEV_PASSWORD,
                    'password_changed_at' => Carbon::now(),
                    'contact_number' => '0900-000-'.str_pad((string) $employeeSequence, 4, '0', STR_PAD_LEFT),
                    'department_id' => $department->id,
                    'created_by' => $admin->id,
                ]
            );
            $head->assignRole(RoleName::DepartmentHead->value);
            $department->update(['department_head_id' => $head->id]);

            foreach (range(1, 2) as $i) {
                $facultyEmployeeNumber = 'EMP-'.str_pad((string) $employeeSequence++, 4, '0', STR_PAD_LEFT);
                $facultyUsername = 'faculty'.$i.'.'.strtolower($department->code);

                $faculty = User::firstOrCreate(
                    ['email' => $facultyUsername.'@ca-apoms.test'],
                    [
                        'employee_number' => $facultyEmployeeNumber,
                        'surname' => 'Dela Cruz',
                        'first_name' => "Faculty {$i} of {$department->name}",
                        'username' => $facultyUsername,
                        'password' => self::DEV_PASSWORD,
                        'password_changed_at' => Carbon::now(),
                        'contact_number' => '0900-000-'.str_pad((string) $employeeSequence, 4, '0', STR_PAD_LEFT),
                        'department_id' => $department->id,
                        'created_by' => $admin->id,
                    ]
                );
                $faculty->assignRole(RoleName::Faculty->value);
            }
        }
    }
}
