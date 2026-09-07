<?php

namespace Modules\Files\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
                $request->integer('per_page', 48),
            ),
            'filters' => ['search' => $request->string('search')->toString()],
        ]);
    }

    public function picker(Request $request, MediaLibraryService $media): JsonResponse
    {
        $search = $request->string('q')->trim()->toString() ?: null;

        return response()->json([
            'items' => $media->pickerItems($search),
        ]);
    }

    public function pickerUpload(StoreMediaRequest $request, MediaLibraryService $media): JsonResponse
    {
        $files = $request->file('files');
        if (is_array($files) && $files !== []) {
            $uploaded = $media->uploadMany($files, $request->user()?->id);

            return response()->json([
                'items' => array_map(fn (MediaLibraryItem $item) => $media->toArray($item), $uploaded),
            ]);
        }

        $item = $media->upload($request->file('file'), $request->user()?->id);
        $payload = $media->toArray($item);

        return response()->json([
            'item' => $payload,
            'items' => [$payload],
        ]);
    }

    public function store(StoreMediaRequest $request, MediaLibraryService $media): RedirectResponse
    {
        $files = $request->file('files');
        if (is_array($files) && $files !== []) {
            $media->uploadMany($files, $request->user()?->id);

            return back()->with('success', count($files).' files uploaded.');
        }

        $media->upload($request->file('file'), $request->user()?->id);

        return back()->with('success', 'Media uploaded.');
    }

    public function destroy(MediaLibraryItem $mediaLibraryItem, MediaLibraryService $media): RedirectResponse
    {
        $media->delete($mediaLibraryItem);

        return back()->with('success', 'Media deleted.');
    }
}
