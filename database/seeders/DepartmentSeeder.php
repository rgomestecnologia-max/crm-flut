<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            ['name' => 'Vendas',    'description' => 'Equipe comercial',      'color' => '#14B8A6', 'icon' => 'currency-dollar'],
            ['name' => 'Suporte',   'description' => 'Suporte técnico',       'color' => '#3B82F6', 'icon' => 'wrench-screwdriver'],
            ['name' => 'Financeiro','description' => 'Cobranças e pagamentos','color' => '#F59E0B', 'icon' => 'banknotes'],
            ['name' => 'RH',        'description' => 'Recursos Humanos',      'color' => '#8B5CF6', 'icon' => 'users'],
        ];

        foreach ($departments as $dept) {
            Department::firstOrCreate(['name' => $dept['name']], $dept);
        }
    }
}
