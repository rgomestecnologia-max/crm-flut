<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        // Empresa principal — todos os dados pré-existentes pertencem a ela.
        // Será usada como tenant default no backfill da Fase 2.
        Company::firstOrCreate(
            ['slug' => 'rsg-group'],
            [
                'name'      => 'RSG Group',
                'color'     => '#14B8A6',
                'is_active' => true,
            ]
        );
    }
}
