<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Pos\Services\PosRegisterService;

class PosSeeder extends Seeder
{
    public function run(): void
    {
        app(PosRegisterService::class)->ensureDefault();
    }
}
