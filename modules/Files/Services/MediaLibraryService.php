<?php

namespace Modules\Files\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Files\Models\MediaLibraryItem;

class MediaLibraryService extends Service
{
    public function listPaginated(?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return MediaLibraryItem::query()
            ->when($search, fn ($q, $search) => $q->where('name', 'like', "%{$search}%"))
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (MediaLibraryItem $row) => [
                'id' => $row->id,
                'name' => $row->name,
                'path' => $row->path,
                'url' => Storage::disk($row->disk)->url($row->path),
                'mime' => $row->mime,
                'size' => $row->size,
            ]);
    }

    public function upload(UploadedFile $file, ?int $userId = null): MediaLibraryItem
    {
        $path = $file->store('media', 'public');

        return MediaLibraryItem::query()->create([
            'name' => $file->getClientOriginalName(),
            'disk' => 'public',
            'path' => $path,
            'mime' => $file->getClientMimeType(),
            'size' => $file->getSize() ?: 0,
            'uploaded_by' => $userId,
        ]);
    }

    public function delete(MediaLibraryItem $item): void
    {
        Storage::disk($item->disk)->delete($item->path);
        $item->delete();
    }
}
