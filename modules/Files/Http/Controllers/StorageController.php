<?php

namespace Modules\Files\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class StorageController extends Controller
{
    public function index(): Response
    {
        $media = Schema::hasTable('media_library_items') ? (int) DB::table('media_library_items')->sum('size') : 0;
        $docs = Schema::hasTable('documents') ? (int) DB::table('documents')->sum('size') : 0;

        return Inertia::render('Files/Storage/Index', [
            'stats' => [
                'media_bytes' => $media,
                'document_bytes' => $docs,
                'total_bytes' => $media + $docs,
            ],
        ]);
    }
}
