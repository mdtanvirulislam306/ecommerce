<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\Files\Models\Document;

class FilesSeeder extends Seeder
{
    public function run(): void
    {
        $adminId = User::query()->where('email', 'admin@admin.com')->value('id');

        Document::query()->firstOrCreate(
            ['path' => 'documents/sample-retail-price-list.txt'],
            [
                'title' => 'Retail price list (sample)',
                'disk' => 'public',
                'mime' => 'text/plain',
                'size' => 0,
                'uploaded_by' => $adminId,
            ],
        );
    }
}
