<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\Hrm\Models\Department;
use Modules\Hrm\Models\Designation;
use Modules\Hrm\Models\Employee;

class HrmSeeder extends Seeder
{
    public function run(): void
    {
        $sales = Department::query()->firstOrCreate(
            ['code' => 'SALES'],
            ['name' => 'Sales', 'is_active' => true, 'notes' => 'Counter and B2B sales'],
        );

        $warehouse = Department::query()->firstOrCreate(
            ['code' => 'WAREHOUSE'],
            ['name' => 'Warehouse', 'is_active' => true, 'notes' => 'Receiving and fulfillment'],
        );

        Designation::query()->firstOrCreate(
            ['code' => 'SALES-EXEC'],
            ['name' => 'Sales Executive', 'department_id' => $sales->id, 'is_active' => true],
        );

        Designation::query()->firstOrCreate(
            ['code' => 'WH-ASSOC'],
            ['name' => 'Warehouse Associate', 'department_id' => $warehouse->id, 'is_active' => true],
        );

        $admin = User::query()->where('email', 'admin@admin.com')->first();

        Employee::query()->firstOrCreate(
            ['code' => 'EMP-0001'],
            [
                'name' => $admin?->name ?? 'Admin',
                'email' => $admin?->email ?? 'admin@admin.com',
                'phone' => '01700000000',
                'department' => 'Sales',
                'designation' => 'Sales Executive',
                'hired_at' => now()->subYear()->toDateString(),
                'is_active' => true,
                'notes' => 'Primary admin / sales owner',
                'user_id' => $admin?->id,
            ],
        );

        Employee::query()->firstOrCreate(
            ['code' => 'EMP-0002'],
            [
                'name' => 'Rafiq Hasan',
                'email' => 'rafiq.warehouse@example.com',
                'phone' => '01730002222',
                'department' => 'Warehouse',
                'designation' => 'Warehouse Associate',
                'hired_at' => now()->subMonths(6)->toDateString(),
                'is_active' => true,
                'notes' => 'MAIN warehouse receiving',
            ],
        );
    }
}
