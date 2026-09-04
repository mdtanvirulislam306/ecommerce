<?php

namespace Modules\Files\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Files\Http\Requests\StoreMediaRequest;
use Modules\Files\Models\MediaLibraryItem;
use Modules\Files\Services\MediaLibraryService;

class MediaLibraryController extends Controller
{
    public function index(Request $request, MediaLibraryService $media): Response
    {
        return Inertia::render('Files/MediaLibrary/Index', [
            'items' => $media->listPaginated(
                $request->string('search')->trim()->toString() ?: null,
                $request->integer('per_page', 25),
            ),
            'filters' => ['search' => $request->string('search')->toString()],
        ]);
    }

    public function picker(Request $request): JsonResponse
    {
        $search = $request->string('q')->trim()->toString() ?: null;

        $items = MediaLibraryItem::query()
            ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->orderByDesc('id')
            ->limit(48)
            ->get()
            ->map(fn (MediaLibraryItem $row) => [
                'id' => $row->id,
                'name' => $row->name,
                'path' => $row->path,
                'url' => Storage::disk($row->disk)->url($row->path),
                'mime' => $row->mime,
                'size' => $row->size,
            ])
            ->values()
            ->all();

        return response()->json(['items' => $items]);
    }

    public function pickerUpload(StoreMediaRequest $request, MediaLibraryService $media): JsonResponse
    {
        $item = $media->upload($request->file('file'), $request->user()?->id);

        return response()->json([
            'item' => [
                'id' => $item->id,
                'name' => $item->name,
                'path' => $item->path,
                'url' => Storage::disk($item->disk)->url($item->path),
                'mime' => $item->mime,
                'size' => $item->size,
            ],
        ]);
    }

    public function store(StoreMediaRequest $request, MediaLibraryService $media): RedirectResponse
    {
        $media->upload($request->file('file'), $request->user()?->id);

        return back()->with('success', 'Media uploaded.');
    }

    public function destroy(MediaLibraryItem $mediaLibraryItem, MediaLibraryService $media): RedirectResponse
    {
        $media->delete($mediaLibraryItem);

        return back()->with('success', 'Media deleted.');
    }
}
